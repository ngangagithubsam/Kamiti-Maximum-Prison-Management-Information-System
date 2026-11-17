<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();

$body = jsonBody();
action:
$action = trim(strtolower($body['action'] ?? ($_GET['action'] ?? '')));
$pdo = getDB();
ensureTransferRequestsTable($pdo);
ensurePrisonerFacilityColumn($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'pending_count') {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM transfer_requests WHERE status = 'Pending'")->fetchColumn();
        jsonOK(['pending' => $count]);
    }
    if ($action === 'list') {
        $stmt = $pdo->query(
            'SELECT tr.*, CONCAT(p.firstname, " ", p.lastname) AS prisonerName, p.facility AS current_facility, r.firstname AS reviewed_by_firstname, r.lastname AS reviewed_by_lastname, s.firstname AS requested_by_firstname, s.lastname AS requested_by_lastname
             FROM transfer_requests tr
             LEFT JOIN prisoners p ON p.id = tr.inmate_id
             LEFT JOIN staff s ON s.id = tr.requested_by
             LEFT JOIN staff r ON r.id = tr.reviewed_by
             ORDER BY tr.requested_on DESC'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['prisonerName'] = $row['prisonerName'] ?? trim(($row['requested_by_firstname'] ?? '') . ' ' . ($row['requested_by_lastname'] ?? ''));
            $row['reviewedByName'] = trim(($row['reviewed_by_firstname'] ?? '') . ' ' . ($row['reviewed_by_lastname'] ?? '')) ?: null;
            unset($row['requested_by_firstname'], $row['requested_by_lastname'], $row['reviewed_by_firstname'], $row['reviewed_by_lastname']);
        }
        jsonOK($rows);
    }
    jsonError('Unknown action', 400);
}

$currentUser = $_SESSION['user'] ?? [];
$currentUserId = (int)($currentUser['id'] ?? $_SESSION['user_id'] ?? 0);
$currentUserName = trim(($currentUser['firstname'] ?? '') . ' ' . ($currentUser['lastname'] ?? '')) ?: ($currentUser['username'] ?? 'Unknown');
$currentUserRole = strtolower(trim($currentUser['role'] ?? $_SESSION['role'] ?? ''));

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

function ensurePrisonerFacilityColumn(PDO $pdo): void {
    $column = $pdo->query("SHOW COLUMNS FROM prisoners LIKE 'facility'")->fetch();
    if (!$column) {
        $pdo->exec("ALTER TABLE prisoners ADD COLUMN facility VARCHAR(150) DEFAULT 'Kamiti Maximum Security Prison'");
    }
}

function insertAudit(PDO $pdo, string $user, string $action, string $details): void {
    $stmt = $pdo->prepare('INSERT INTO audit_log (`user`, action, module, details, ip) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$user, $action, 'transfers', $details, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
}

switch ($action) {
    case 'create':
        validateCsrfToken($body['csrf_token'] ?? '');
        $inmateId = (int)($body['inmate_id'] ?? 0);
        $fromFacility = trim($body['from_facility'] ?? 'Kamiti Maximum Security Prison');
        $toFacility = trim($body['to_facility'] ?? '');
        $reason = trim($body['reason'] ?? '');

        if ($inmateId <= 0 || $toFacility === '') {
            jsonError('Inmate and destination facility are required');
        }

        $stmt = $pdo->prepare('SELECT id, firstname, lastname, facility, status FROM prisoners WHERE id = ? LIMIT 1');
        $stmt->execute([$inmateId]);
        $inmate = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$inmate) {
            jsonError('Inmate record not found');
        }
        if (strtolower((string)($inmate['status'] ?? '')) === 'released') {
            jsonError('Released prisoners cannot be transferred');
        }

        $insert = $pdo->prepare('INSERT INTO transfer_requests (inmate_id, from_facility, to_facility, reason, status, requested_by) VALUES (?, ?, ?, ?, ?, ?)');
        $insert->execute([$inmateId, $fromFacility, $toFacility, $reason, 'Pending', $currentUserId]);
        $requestId = (int)$pdo->lastInsertId();

        insertAudit($pdo, $currentUserName, 'Submitted transfer request', sprintf('Inmate %s (%s) → %s [request_id:%d]', trim($inmate['firstname'] . ' ' . $inmate['lastname']), $inmateId, $toFacility, $requestId));

        jsonOK(['request_id' => $requestId], 'Transfer request submitted successfully');

    case 'approve':
        validateCsrfToken($body['csrf_token'] ?? '');
        requireRole('Administrator');
        $requestId = (int)($body['request_id'] ?? 0);
        if ($requestId <= 0) jsonError('Request ID is required');

        $stmt = $pdo->prepare('SELECT tr.*, p.facility AS current_facility, p.status AS inmate_status, p.firstname, p.lastname FROM transfer_requests tr JOIN prisoners p ON p.id = tr.inmate_id WHERE tr.id = ? LIMIT 1');
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request) jsonError('Transfer request not found');
        if ($request['status'] !== 'Pending') jsonError('Only pending requests can be approved');

        ensurePrisonerFacilityColumn($pdo);
        $newStatus = $request['to_facility'] !== ($request['current_facility'] ?: 'Kamiti Maximum Security Prison') ? 'Transferred' : $request['inmate_status'];

        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare('UPDATE transfer_requests SET status = ?, reviewed_by = ?, reviewed_on = NOW() WHERE id = ?');
            $update->execute(['Approved', $currentUserId, $requestId]);

            $updatePrisoner = $pdo->prepare('UPDATE prisoners SET facility = ?, status = ? WHERE id = ?');
            $updatePrisoner->execute([$request['to_facility'], $newStatus, $request['inmate_id']]);

            insertAudit($pdo, $currentUserName, 'Approved transfer request', sprintf('Approved transfer of %s (%d) to %s [request_id:%d]', trim($request['firstname'] . ' ' . $request['lastname']), $request['inmate_id'], $request['to_facility'], $requestId));
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            jsonError('Failed to approve transfer request: ' . $e->getMessage(), 500);
        }

        jsonOK([], 'Transfer approved');

    case 'decline':
        validateCsrfToken($body['csrf_token'] ?? '');
        requireRole('Administrator');
        $requestId = (int)($body['request_id'] ?? 0);
        $declineReason = trim($body['decline_reason'] ?? '');
        if ($requestId <= 0) jsonError('Request ID is required');
        if ($declineReason === '') jsonError('Decline reason is required');

        $stmt = $pdo->prepare('SELECT tr.id, p.firstname, p.lastname, p.id AS prisoner_id FROM transfer_requests tr JOIN prisoners p ON p.id = tr.inmate_id WHERE tr.id = ? LIMIT 1');
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request) jsonError('Transfer request not found');
        if ($request['status'] !== 'Pending') jsonError('Only pending requests can be declined');

        $update = $pdo->prepare('UPDATE transfer_requests SET status = ?, reviewed_by = ?, reviewed_on = NOW(), decline_reason = ? WHERE id = ?');
        $update->execute(['Declined', $currentUserId, $declineReason, $requestId]);

        insertAudit($pdo, $currentUserName, 'Declined transfer request', sprintf('Declined transfer request for %s (%d): %s [request_id:%d]', trim($request['firstname'] . ' ' . $request['lastname']), $request['prisoner_id'], $declineReason, $requestId));

        jsonOK([], 'Transfer declined');

    default:
        jsonError('Unknown action', 400);
}
