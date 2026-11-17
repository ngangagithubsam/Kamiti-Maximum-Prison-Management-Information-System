<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();

$body = jsonBody();
$action = $body['action'] ?? ($_GET['action'] ?? 'list');
$pdo = getDB();
$cu = currentUser();
$userName = trim(($cu['firstname'] ?? '') . ' ' . ($cu['lastname'] ?? ''));

// Ensure table exists (DDL) with all required columns
function initializeReleaseRequestsTable($pdo) {
  $requiredCols = [
    'id', 'requestId', 'prisoner_id', 'request_type', 
    'requested_release_date', 'reason', 'status', 'requested_by',
    'requested_on', 'approved_by', 'approved_on', 'decline_reason',
    'supporting_document', 'contact_phone', 'bond_amount'
  ];
  
  // First, check if table exists
  $tableExists = false;
  $existingCols = [];
  try {
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='release_requests' AND TABLE_SCHEMA=DATABASE()");
    if ($result) {
      $existingCols = $result->fetchAll(PDO::FETCH_COLUMN);
      $tableExists = count($existingCols) > 0;
    }
  } catch (Exception $e) {
    $tableExists = false;
  }

  if (!$tableExists) {
    // Create the table fresh
    $pdo->exec("CREATE TABLE release_requests (
      id INT AUTO_INCREMENT PRIMARY KEY,
      requestId VARCHAR(32) NOT NULL UNIQUE,
      prisoner_id INT NOT NULL,
      request_type VARCHAR(50) NOT NULL DEFAULT 'Sentence Complete',
      requested_release_date DATE DEFAULT NULL,
      reason LONGTEXT,
      status VARCHAR(20) NOT NULL DEFAULT 'Pending',
      requested_by INT DEFAULT NULL,
      requested_on DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      approved_by INT DEFAULT NULL,
      approved_on DATETIME DEFAULT NULL,
      decline_reason LONGTEXT,
      supporting_document VARCHAR(255) DEFAULT NULL,
      contact_phone VARCHAR(20) DEFAULT NULL,
      bond_amount DECIMAL(10,2) DEFAULT NULL,
      INDEX idx_prisoner (prisoner_id),
      INDEX idx_status (status),
      INDEX idx_requested_on (requested_on)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
  } else {
    // Table exists, ensure all required columns are present
    $missingCols = array_diff($requiredCols, $existingCols);
    
    foreach ($missingCols as $col) {
      $type = 'VARCHAR(255)';
      $default = ' DEFAULT NULL';
      
      if ($col === 'reason' || $col === 'decline_reason') { $type = 'LONGTEXT'; $default = ''; }
      if ($col === 'requested_release_date') { $type = 'DATE'; }
      if ($col === 'requested_on' || $col === 'approved_on') { $type = 'DATETIME'; }
      if ($col === 'bond_amount') { $type = 'DECIMAL(10,2)'; }
      if ($col === 'prisoner_id' || $col === 'requested_by' || $col === 'approved_by') { $type = 'INT'; }
      if ($col === 'id') { continue; } // Skip id, it's already there
      if ($col === 'status') { $type = "VARCHAR(20)"; $default = " DEFAULT 'Pending'"; }
      if ($col === 'request_type') { $type = "VARCHAR(50)"; $default = " DEFAULT 'Sentence Complete'"; }
      if ($col === 'requestId') { $type = "VARCHAR(32) NOT NULL UNIQUE"; $default = ''; }
      
      try {
        $pdo->exec("ALTER TABLE release_requests ADD COLUMN `$col` $type$default");
      } catch (Exception $e) {
        // Column might already exist or there's another issue
        error_log("Error adding column $col: " . $e->getMessage());
      }
    }
  }
}

// Initialize table on script start
initializeReleaseRequestsTable($pdo);

function genRequestId($pdo) {
  $mx = (int)$pdo->query('SELECT IFNULL(MAX(id),0) as mx FROM release_requests')->fetch()['mx'];
  return 'RLS-' . str_pad($mx+1,3,'0',STR_PAD_LEFT);
}

// Helper: determine which date column name to use (requested_on, requestDate, or created_at)
function getDateColumn($pdo) {
  static $dateCol = null;
  if ($dateCol !== null) return $dateCol;
  try {
    $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='release_requests' AND TABLE_SCHEMA=DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('requested_on', $cols)) { $dateCol = 'requested_on'; return $dateCol; }
    if (in_array('requestDate', $cols)) { $dateCol = 'requestDate'; return $dateCol; }
    if (in_array('created_at', $cols)) { $dateCol = 'created_at'; return $dateCol; }
  } catch (Exception $e) { }
  $dateCol = 'requested_on'; // default fallback
  return $dateCol;
}

// Helper: determine which prisoner column name to use (snake_case or camelCase)
function getPrisonerJoinColumn($pdo) {
  static $col = null;
  if ($col !== null) return $col; // cache result
  try {
    $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='release_requests' AND TABLE_SCHEMA=DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('prisoner_id', $cols)) { $col = 'prisoner_id'; return $col; }
    if (in_array('prisonerId', $cols)) { $col = 'prisonerId'; return $col; }
  } catch (Exception $e) { }
  $col = 'prisoner_id'; // default fallback
  return $col;
}

switch($action) {
  case 'list':
    $filters = $body['filters'] ?? [];
    $where = [];
    $params = [];
    $dateCol = getDateColumn($pdo);
    if (!empty($filters['status'])) { $where[] = 'r.status = ?'; $params[] = $filters['status']; }
    if (!empty($filters['type'])) { $where[] = 'r.request_type = ?'; $params[] = $filters['type']; }
    if (!empty($filters['from'])) { $where[] = "r.$dateCol >= ?"; $params[] = $filters['from'] . ' 00:00:00'; }
    if (!empty($filters['to'])) { $where[] = "r.$dateCol <= ?"; $params[] = $filters['to'] . ' 23:59:59'; }
    if (!empty($filters['q'])) { $where[] = '(r.requestId LIKE ? OR p.firstname LIKE ? OR p.lastname LIKE ? OR p.prisonerId LIKE ?)'; $q = '%'.$filters['q'].'%'; $params = array_merge($params, [$q,$q,$q,$q]); }

    $prisonerCol = getPrisonerJoinColumn($pdo);
    $sql = "SELECT r.*, r.$dateCol AS requested_on, p.prisonerId AS prisonerIdStr, p.firstname, p.lastname, CONCAT(p.firstname,' ',p.lastname) AS prisonerName FROM release_requests r LEFT JOIN prisoners p ON p.id = r.$prisonerCol";
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= " ORDER BY r.$dateCol DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
      $r['prisonerName'] = $r['prisonerName'] ?? ($r['prisonerIdStr'] ?? '');
    }
    jsonOK($rows);

  case 'get':
    $id = (int)($_GET['id'] ?? $body['id'] ?? 0);
    if (!$id) jsonError('No id');
    $prisonerCol = getPrisonerJoinColumn($pdo);
    $dateCol = getDateColumn($pdo);
    $stmt = $pdo->prepare("SELECT r.*, r.$dateCol AS requested_on, p.prisonerId AS prisonerIdStr, p.firstname, p.lastname, CONCAT(p.firstname, ' ', p.lastname) AS prisonerName FROM release_requests r LEFT JOIN prisoners p ON p.id = r.$prisonerCol WHERE r.id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonError('Not found');
    $row['prisonerName'] = $row['prisonerName'] ?? ($row['prisonerIdStr'] ?? '');
    jsonOK($row);

  case 'create':
    validateCsrfToken($body['csrf_token'] ?? '');
    $rec = $body['record'] ?? [];
    if (empty($rec['prisoner_id']) || empty($rec['request_type']) || empty($rec['reason'])) jsonError('Missing required fields');
    if (!empty($rec['requested_release_date']) && $rec['request_type'] !== 'Death') {
      $d = strtotime($rec['requested_release_date']);
      if ($d < strtotime(date('Y-m-d'))) jsonError('Requested release date cannot be in the past');
    }
    $reqId = genRequestId($pdo);
    $supporting = $rec['supporting_document'] ?? null;
    $prisonerCol = getPrisonerJoinColumn($pdo);
    $stmt = $pdo->prepare("INSERT INTO release_requests (requestId, $prisonerCol, request_type, requested_release_date, reason, status, requested_by, supporting_document, contact_phone, bond_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$reqId, $rec['prisoner_id'], $rec['request_type'], $rec['requested_release_date'] ?: null, $rec['reason'], 'Pending', $cu['id'] ?? null, $supporting, $rec['contact_phone'] ?? null, $rec['bond_amount'] ?? null]);
    $newId = (int)$pdo->lastInsertId();
    auditLog($userName, 'CREATE', 'release_requests', "Created release request $reqId for prisoner ID {$rec['prisoner_id']}");
    jsonOK(['id' => $newId, 'requestId' => $reqId], 'Created');

  case 'approve':
    validateCsrfToken($body['csrf_token'] ?? '');
    requireRole(['admin','system administrator','warden']);
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonError('No id');
    $pdo->beginTransaction();
    try {
      $stmt = $pdo->prepare('UPDATE release_requests SET status = ?, approved_by = ?, approved_on = NOW() WHERE id = ?');
      $stmt->execute(['Approved', $cu['id'] ?? null, $id]);

      $prisonerCol = getPrisonerJoinColumn($pdo);
      $pr = $pdo->prepare("SELECT p.id, p.cell FROM prisoners p JOIN release_requests r ON p.id = r.$prisonerCol WHERE r.id = ? LIMIT 1");
      $pr->execute([$id]);
      $prow = $pr->fetch(PDO::FETCH_ASSOC);
      if ($prow && !empty($prow['id'])) {
        $cellNumber = trim($prow['cell'] ?? '');
        if ($cellNumber !== '') {
          $cellStmt = $pdo->prepare('SELECT id, occupied, capacity FROM cells WHERE cellNumber = ? LIMIT 1');
          $cellStmt->execute([$cellNumber]);
          $cellRow = $cellStmt->fetch(PDO::FETCH_ASSOC);
          if ($cellRow) {
            $newOccupied = max(0, (int)$cellRow['occupied'] - 1);
            $newStatus = $newOccupied >= (int)$cellRow['capacity'] ? 'Full' : ($newOccupied > 0 ? 'Occupied' : 'Available');
            $pdo->prepare('UPDATE cells SET occupied = ?, status = ? WHERE id = ?')->execute([$newOccupied, $newStatus, $cellRow['id']]);
          }
        }
        $pdo->prepare('UPDATE prisoners SET status = ?, cell = ? WHERE id = ?')->execute(['Released', '', $prow['id']]);
      }

      auditLog($userName, 'APPROVE', 'release_requests', "Approved release request ID $id");
      $pdo->commit();
      jsonOK([], 'Approved');
    } catch (Exception $e) {
      $pdo->rollBack();
      jsonError('Failed to approve release request');
    }

  case 'decline':
    validateCsrfToken($body['csrf_token'] ?? '');
    requireRole(['admin','system administrator','warden']);
    $id = (int)($body['id'] ?? 0);
    $reason = trim($body['reason'] ?? '');
    if (!$id || $reason === '') jsonError('Id and reason required');
    $stmt = $pdo->prepare('UPDATE release_requests SET status = ?, decline_reason = ? WHERE id = ?');
    $stmt->execute(['Declined', $reason, $id]);
    auditLog($userName, 'DECLINE', 'release_requests', "Declined release request ID $id: $reason");
    jsonOK([], 'Declined');

  case 'delete':
    validateCsrfToken($body['csrf_token'] ?? '');
    $id = (int)($body['id'] ?? 0);
    if (!$id) jsonError('No id');
    $r = $pdo->prepare('SELECT status FROM release_requests WHERE id = ?'); $r->execute([$id]); $row = $r->fetch();
    if ($row && $row['status'] !== 'Pending') jsonError('Can only delete pending requests');
    $pdo->prepare('DELETE FROM release_requests WHERE id = ?')->execute([$id]);
    auditLog($userName, 'DELETE', 'release_requests', "Deleted release request ID $id");
    jsonOK([], 'Deleted');

  case 'export':
    $filters = $body['filters'] ?? [];
    $where = [];
    $params = [];
    $dateCol = getDateColumn($pdo);
    if (!empty($filters['status'])) { $where[] = 'r.status = ?'; $params[] = $filters['status']; }
    if (!empty($filters['type'])) { $where[] = 'r.request_type = ?'; $params[] = $filters['type']; }
    if (!empty($filters['from'])) { $where[] = "r.$dateCol >= ?"; $params[] = $filters['from'] . ' 00:00:00'; }
    if (!empty($filters['to'])) { $where[] = "r.$dateCol <= ?"; $params[] = $filters['to'] . ' 23:59:59'; }
    if (!empty($filters['q'])) { $where[] = '(r.requestId LIKE ? OR p.firstname LIKE ? OR p.lastname LIKE ? OR p.prisonerId LIKE ?)'; $q = '%'.$filters['q'].'%'; $params = array_merge($params, [$q,$q,$q,$q]); }
    $prisonerCol = getPrisonerJoinColumn($pdo);
    $sql = "SELECT r.requestId, CONCAT(p.firstname,' ',p.lastname) AS prisonerName, r.request_type, r.requested_release_date, r.status, r.$dateCol AS requestedOn FROM release_requests r LEFT JOIN prisoners p ON p.id = r.$prisonerCol";
    if ($where) $sql .= ' WHERE '.implode(' AND ', $where);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonOK($rows);

  default:
    jsonError('Unknown action');
}
