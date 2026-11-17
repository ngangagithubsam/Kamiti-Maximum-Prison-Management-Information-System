<?php
// ============================================================
// api/data.php  —  Generic CRUD + new modules
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();

$body   = jsonBody();
$action = $body['action'] ?? ($_GET['action'] ?? 'get');
$store  = $body['store']  ?? ($_GET['store']  ?? '');

$TABLE_MAP = [
    // Original
    'prisoners'            => 'prisoners',
    'cells'                => 'cells',
    'visitations'          => 'visitations',
    'incidents'            => 'incidents',
    'movements'            => 'movements',
    'court'                => 'court',
    'reports'              => 'reports',
    'medical'              => 'medical',
    'staff'                => 'staff',
    'audit'                => 'audit_log',
    'audit_log'            => 'audit_log',
    'dashboard_audit'      => 'audit_log',
    // New modules
    'shifts'               => 'shifts',
    'shift_swaps'          => 'shift_swaps',
    'inventory'            => 'inventory_items',
    'inventory_items'      => 'inventory_items',
    'inventory_transactions' => 'inventory_transactions',
    'rehab_programs'       => 'rehab_programs',
    'rehab_enrollments'    => 'rehab_enrollments',
    'release_requests'     => 'release_requests',
    'transfer_requests'    => 'transfer_requests',
];

function resolveTable(string $store): string {
    global $TABLE_MAP;
    $tbl = $TABLE_MAP[$store] ?? '';
    if (!$tbl) jsonError("Unknown store: $store");
    return $tbl;
}

function adjustCellOccupancy(PDO $pdo, string $cellNumber, int $delta): void {
    $stmt = $pdo->prepare('SELECT id, occupied, capacity FROM cells WHERE cellNumber = ? LIMIT 1');
    $stmt->execute([$cellNumber]);
    $cell = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cell) return;
    $occupied = max(0, (int)$cell['occupied'] + $delta);
    $status = $occupied >= (int)$cell['capacity'] ? 'Full' : ($occupied > 0 ? 'Occupied' : 'Available');
    $pdo->prepare('UPDATE cells SET occupied = ?, status = ? WHERE id = ?')->execute([$occupied, $status, $cell['id']]);
}

function ensureReleaseRequestsTable(PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS release_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            requestId VARCHAR(20) NOT NULL UNIQUE,
            prisonerId VARCHAR(20) NOT NULL,
            prisonerName VARCHAR(200) DEFAULT \'\',
            releaseType VARCHAR(50) DEFAULT \'Sentence Complete\',
            releaseDate DATE DEFAULT NULL,
            reason TEXT DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            status VARCHAR(30) DEFAULT \'Pending\',
            requestedBy VARCHAR(200) DEFAULT \'\',
            requestDate DATE DEFAULT NULL,
            decisionBy VARCHAR(200) DEFAULT \'\',
            decisionDate DATE DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )'
    );
}

function ensureTransferRequestsTable(PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS transfer_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inmate_id INT NOT NULL,
            from_facility VARCHAR(150) NOT NULL DEFAULT \'Kamiti Maximum Security Prison\',
            to_facility VARCHAR(150) NOT NULL,
            reason TEXT DEFAULT NULL,
            status ENUM(\'Pending\',\'Approved\',\'Declined\') DEFAULT \'Pending\',
            requested_by INT NOT NULL,
            requested_on DATETIME DEFAULT CURRENT_TIMESTAMP,
            reviewed_by INT DEFAULT NULL,
            reviewed_on DATETIME DEFAULT NULL,
            decline_reason TEXT DEFAULT NULL,
            FOREIGN KEY (inmate_id) REFERENCES prisoners(id) ON DELETE CASCADE,
            FOREIGN KEY (requested_by) REFERENCES staff(id),
            FOREIGN KEY (reviewed_by) REFERENCES staff(id)
        )'
    );
}

function ensureReportsTable(PDO $pdo): void {

    function ensureIncidentHistoryTable(PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS incident_status_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            incident_id INT NOT NULL,
            old_status VARCHAR(30) DEFAULT NULL,
            new_status VARCHAR(30) NOT NULL,
            changed_by INT NOT NULL,
            changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_incident_history (incident_id, changed_at),
            FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES staff(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='reports' AND TABLE_SCHEMA=DATABASE()")
            ->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('status', $cols, true)) {
            $pdo->exec("ALTER TABLE reports ADD COLUMN status VARCHAR(20) DEFAULT 'Pending'");
        }
    } catch (Exception $e) {
        // ignore schema migration failures; analytics will fallback
    }
}

function ensurePrisonerPhotoColumns(PDO $pdo): void {
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='prisoners' AND TABLE_SCHEMA=DATABASE()")
            ->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('photo', $cols, true)) {
            $pdo->exec("ALTER TABLE prisoners ADD COLUMN photo MEDIUMTEXT DEFAULT NULL");
        }
        if (!in_array('caseNumber', $cols, true)) {
            $pdo->exec("ALTER TABLE prisoners ADD COLUMN caseNumber VARCHAR(100) DEFAULT ''");
        }
    } catch (Throwable $e) {
        error_log('Prisoner photo migration error: ' . $e->getMessage());
    }
}

$pdo      = getDB();
ensurePrisonerPhotoColumns($pdo);
if ($store === 'release_requests') {
    ensureReleaseRequestsTable($pdo);
}
if ($store === 'transfer_requests') {
    ensureTransferRequestsTable($pdo);
}
if ($store === 'reports') {
    ensureReportsTable($pdo);
}
if ($store === 'incidents') {
    ensureIncidentHistoryTable($pdo);
}
$cu       = currentUser();
$userName = trim(($cu['firstname'] ?? '') . ' ' . ($cu['lastname'] ?? ''));

requirePermission($action, $store);

if (in_array($action, ['save', 'delete'], true)) {
    validateCsrfToken($body['csrf_token'] ?? '');
}

switch ($action) {

// ── GET ALL ──────────────────────────────────────────────────
case 'get':
    $tbl  = resolveTable($store);
    $query = $store === 'dashboard_audit'
        ? "SELECT * FROM `$tbl` ORDER BY ts DESC, id DESC LIMIT 10"
        : "SELECT * FROM `$tbl` ORDER BY id DESC";
    $rows = $pdo->query($query)->fetchAll();
    if ($tbl === 'prisoners') {
        foreach ($rows as &$row) $row['photo_url'] = resolvePrisonerPhoto($row['photo'] ?? null);
        unset($row);
    }
    if ($tbl === 'staff') {
        foreach ($rows as &$r) unset($r['password']);
    }
    jsonOK($rows);


// ── SAVE (INSERT / UPDATE) ────────────────────────────────────
case 'save':
    $tbl    = resolveTable($store);
    $record = $body['record'] ?? [];
    if (!$record) jsonError('No record provided');

    $id    = (int)($record['id'] ?? 0);
    $isNew = ($id === 0);
    $oldIncidentStatus = null;
    if ($isNew) unset($record['id']);

    switch ($tbl) {
        case 'staff':
            if (!empty($record['password'])) {
                $passErrors = validatePassword($record['password']);
                if (!empty($passErrors)) {
                    jsonError(implode('; ', $passErrors));
                }
                $record['password'] = password_hash($record['password'], PASSWORD_BCRYPT);
            } elseif ($isNew) {
                $record['password'] = password_hash('Secure@2024', PASSWORD_BCRYPT);
            } else {
                unset($record['password']);
            }
            if ($isNew) $record['staffId'] = nextId('staff', 'STF-', 'staffId');
            break;

        case 'prisoners':
            if ($isNew) $record['prisonerId'] = nextId('prisoners', 'PRI-', 'prisonerId');
            $newCell = trim($record['cell'] ?? '');
            $oldCell = '';
            if (!$isNew && $id) {
                $current = $pdo->prepare('SELECT cell FROM prisoners WHERE id = ?');
                $current->execute([$id]);
                $oldCell = trim((string)$current->fetchColumn());
            }
            if ($oldCell !== $newCell) {
                if ($oldCell !== '') adjustCellOccupancy($pdo, $oldCell, -1);
                if ($newCell !== '') adjustCellOccupancy($pdo, $newCell, 1);
            }
            break;

        case 'visitations':
            if ($isNew) $record['visitId'] = nextId('visitations', 'VIS-', 'visitId');
            if (isset($record['date'])) { $record['visitDate'] = $record['date']; unset($record['date']); }
            break;

        case 'incidents':
            if ($isNew) $record['incidentId'] = nextId('incidents', 'INC-', 'incidentId');
            if (isset($record['date'])) { $record['incidentDate'] = $record['date']; unset($record['date']); }
            $allowedStatuses = ['Open', 'Reported', 'Under Review', 'Investigation', 'Action Taken', 'Resolved', 'Closed'];
            if (isset($record['status']) && !in_array($record['status'], $allowedStatuses, true)) {
                jsonError('Invalid incident status');
            }
            if (!$isNew && $id) {
                $statusStmt = $pdo->prepare('SELECT status FROM incidents WHERE id = ? LIMIT 1');
                $statusStmt->execute([$id]);
                $oldIncidentStatus = $statusStmt->fetchColumn();
                if (isset($record['status']) && $record['status'] !== $oldIncidentStatus) {
                    $transitions = [
                        'Open' => ['Under Review', 'Reported'], 'Reported' => ['Under Review'],
                        'Under Review' => ['Investigation', 'Action Taken'], 'Investigation' => ['Action Taken'],
                        'Action Taken' => ['Resolved'], 'Resolved' => ['Closed'], 'Closed' => []
                    ];
                    if (!in_array($record['status'], $transitions[$oldIncidentStatus] ?? [], true)) {
                        jsonError('Incident status transition is not permitted');
                    }
                }
            }
            break;

        case 'movements':
            if ($isNew) $record['logId'] = nextId('movements', 'MOV-', 'logId');
            if (isset($record['date'])) { $record['moveDate'] = $record['date']; unset($record['date']); }
            break;

        case 'court':
            if ($isNew) $record['caseId'] = nextId('court', 'CRT-', 'caseId');
            break;

        case 'reports':
            if ($isNew) { $record['reportId'] = nextId('reports', 'RPT-', 'reportId'); $record['reportDate'] = date('Y-m-d H:i:s'); }
            if (isset($record['date'])) { $record['reportDate'] = $record['date']; unset($record['date']); }
            break;

        case 'medical':
            if ($isNew) $record['recordId'] = nextId('medical', 'MED-', 'recordId');
            if (isset($record['date'])) { $record['medDate'] = $record['date']; unset($record['date']); }
            break;

        case 'release_requests':
            if ($isNew) $record['requestId'] = nextId('release_requests', 'RLS-', 'requestId');
            if (isset($record['requestDate'])) { $record['requestDate'] = $record['requestDate']; }
            if (isset($record['decisionDate'])) { $record['decisionDate'] = $record['decisionDate']; }
            break;

        // ── SHIFTS ──────────────────────────────────────────
        case 'shifts':
            if ($isNew) $record['shiftId'] = nextId('shifts', 'SHF-', 'shiftId');
            if (isset($record['date'])) { $record['shiftDate'] = $record['date']; unset($record['date']); }
            break;

        case 'shift_swaps':
            if ($isNew) $record['swapId'] = nextId('shift_swaps', 'SWP-', 'swapId');
            break;

        // ── INVENTORY ────────────────────────────────────────
        case 'inventory_items':
            if ($isNew) $record['itemCode'] = nextId('inventory_items', 'INV-', 'itemCode');
            // Auto-set status based on quantity thresholds
            $qty = (int)($record['quantity'] ?? 0);
            $min = (int)($record['minQuantity'] ?? 5);
            if ($qty === 0) $record['status'] = 'Out of Stock';
            elseif ($qty <= $min) $record['status'] = 'Low Stock';
            else $record['status'] = 'Available';
            break;

        case 'inventory_transactions':
            if ($isNew) {
                $record['txnId'] = nextId('inventory_transactions', 'TXN-', 'txnId');
                // Update item quantity
                $itemId = (int)($record['itemId'] ?? 0);
                if ($itemId) {
                    $item = $pdo->prepare("SELECT quantity, minQuantity FROM inventory_items WHERE id = ?");
                    $item->execute([$itemId]);
                    $it = $item->fetch();
                    if ($it) {
                        $type     = $record['type'] ?? 'Issue';
                        $qty      = (int)($record['quantity'] ?? 0);
                        $newQty   = $it['quantity'];
                        if (in_array($type, ['Restock','Return'])) $newQty += $qty;
                        else $newQty = max(0, $newQty - $qty);

                        $record['balanceAfter'] = $newQty;
                        $newStatus = $newQty === 0 ? 'Out of Stock' : ($newQty <= $it['minQuantity'] ? 'Low Stock' : 'Available');
                        $pdo->prepare("UPDATE inventory_items SET quantity = ?, status = ? WHERE id = ?")
                            ->execute([$newQty, $newStatus, $itemId]);
                    }
                }
            }
            break;

        // ── REHAB ────────────────────────────────────────────
        case 'rehab_programs':
            if ($isNew) $record['programId'] = nextId('rehab_programs', 'PRG-', 'programId');
            break;

        case 'rehab_enrollments':
            if ($isNew) {
                $record['enrollId'] = nextId('rehab_enrollments', 'ENR-', 'enrollId');
                // Increment enrolled count on the program
                $progId = (int)($record['programId'] ?? 0);
                if ($progId) {
                    $pdo->prepare("UPDATE rehab_programs SET enrolled = enrolled + 1 WHERE id = ?")->execute([$progId]);
                }
            }
            break;
    }

    // Filter to valid columns only
    $cols   = array_column($pdo->query("SHOW COLUMNS FROM `$tbl`")->fetchAll(), 'Field');
    $record = array_intersect_key($record, array_flip($cols));

    if ($isNew) {
        $fields       = implode(', ', array_map(fn($k) => "`$k`", array_keys($record)));
        $placeholders = implode(', ', array_fill(0, count($record), '?'));
        $pdo->prepare("INSERT INTO `$tbl` ($fields) VALUES ($placeholders)")->execute(array_values($record));
        $newId = (int)$pdo->lastInsertId();
        if ($tbl === 'incidents') {
            $newStatus = $record['status'] ?? 'Open';
            $pdo->prepare('INSERT INTO incident_status_history (incident_id, old_status, new_status, changed_by) VALUES (?, NULL, ?, ?)')
                ->execute([$newId, $newStatus, (int)($cu['id'] ?? 0)]);
        }
        auditLog($userName, 'CREATE', $store, "Inserted record ID $newId in $tbl");
        jsonOK(['id' => $newId], 'Record created');
    } else {
        $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($record)));
        $vals = array_values($record);
        $vals[] = $id;
        $pdo->prepare("UPDATE `$tbl` SET $sets WHERE id = ?")->execute($vals);
        if ($tbl === 'incidents' && isset($record['status']) && $record['status'] !== $oldIncidentStatus) {
            $pdo->prepare('INSERT INTO incident_status_history (incident_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)')
                ->execute([$id, $oldIncidentStatus, $record['status'], (int)($cu['id'] ?? 0)]);
            auditLog($userName, 'STATUS_CHANGE', 'incidents', "Incident ID $id changed from $oldIncidentStatus to {$record['status']}");
        }
        auditLog($userName, 'UPDATE', $store, "Updated record ID $id in $tbl");
        jsonOK(['id' => $id], 'Record updated');
    }


// ── DELETE ────────────────────────────────────────────────────
case 'delete':
    $tbl = resolveTable($store);
    $id  = (int)($body['id'] ?? 0);
    if (!$id) jsonError('No id provided');

    if ($tbl === 'staff' && $id === (int)($cu['id'] ?? 0)) {
        jsonError('Cannot delete your own account');
    }

    // If deleting a rehab enrollment, decrement program count
    if ($tbl === 'rehab_enrollments') {
        $enr = $pdo->prepare("SELECT programId FROM rehab_enrollments WHERE id = ?");
        $enr->execute([$id]);
        $row = $enr->fetch();
        if ($row) {
            $pdo->prepare("UPDATE rehab_programs SET enrolled = GREATEST(enrolled - 1, 0) WHERE id = ?")
                ->execute([$row['programId']]);
        }
    }

    if ($tbl === 'prisoners') {
        $pr = $pdo->prepare('SELECT cell FROM prisoners WHERE id = ?');
        $pr->execute([$id]);
        $cell = trim((string)$pr->fetchColumn());
        if ($cell !== '') {
            adjustCellOccupancy($pdo, $cell, -1);
        }
    }

    $pdo->prepare("DELETE FROM `$tbl` WHERE id = ?")->execute([$id]);
    auditLog($userName, 'DELETE', $store, "Deleted record ID $id from $tbl");
    jsonOK([], 'Record deleted');


// ── DASHBOARD STATS ───────────────────────────────────────────
case 'stats':
    $prisoners    = (int)$pdo->query("SELECT COUNT(*) FROM prisoners")->fetchColumn();
    $active       = (int)$pdo->query("SELECT COUNT(*) FROM prisoners WHERE status='Active'")->fetchColumn();
    $incidents30  = (int)$pdo->query("SELECT COUNT(*) FROM incidents WHERE incidentDate >= DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetchColumn();
    $visitsToday  = (int)$pdo->query("SELECT COUNT(*) FROM visitations WHERE DATE(visitDate)=CURDATE()")->fetchColumn();
    $totalCap     = (int)($pdo->query("SELECT SUM(capacity) FROM cells")->fetchColumn() ?: 0);
    $avgSentence  = (float)($pdo->query("SELECT AVG(sentence) FROM prisoners")->fetchColumn() ?: 0);
    $totalVisits  = (int)$pdo->query("SELECT COUNT(*) FROM visitations")->fetchColumn();
    $incTotal     = (int)$pdo->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
    $shiftsToday  = (int)$pdo->query("SELECT COUNT(*) FROM shifts WHERE shiftDate=CURDATE()")->fetchColumn();
    $lowStock     = (int)$pdo->query("SELECT COUNT(*) FROM inventory_items WHERE status IN ('Low Stock','Out of Stock')")->fetchColumn();
    $activeRehab  = (int)$pdo->query("SELECT COUNT(*) FROM rehab_enrollments WHERE status='Active'")->fetchColumn();
    $pendingReleases = (int)$pdo->query("SELECT COUNT(*) FROM release_requests WHERE status='Pending'")->fetchColumn();
    $recentActivity = $pdo->query('SELECT id, `user`, action, module, details, ts FROM audit_log ORDER BY ts DESC, id DESC LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);

    jsonOK([
        'total_prisoners'  => $prisoners,
        'active_prisoners' => $active,
        'incidents_30d'    => $incidents30,
        'visits_today'     => $visitsToday,
        'total_capacity'   => $totalCap,
        'avg_sentence'     => round($avgSentence),
        'occupancy_rate'   => $totalCap ? round($active / $totalCap * 100) : 0,
        'incident_rate'    => $prisoners ? round($incTotal / $prisoners, 1) : 0,
        'total_visits'     => $totalVisits,
        'shifts_today'     => $shiftsToday,
        'low_stock_items'  => $lowStock,
        'active_rehab'     => $activeRehab,
        'pending_release_requests' => $pendingReleases,
        'recent_activity' => $recentActivity,
    ]);




default:
    jsonError("Unknown action: $action");
}
