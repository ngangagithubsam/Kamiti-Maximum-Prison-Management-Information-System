<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();
requirePermission('get', 'prisoners');

$action = strtolower(trim($_GET['action'] ?? 'get'));
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($action !== 'get' || !$id) {
    jsonError('A valid prisoner ID is required', 400);
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'SELECT p.*, c.block AS cellBlock, c.capacity AS cellCapacity, c.occupied AS cellOccupied, c.status AS cellStatus
         FROM prisoners p
         LEFT JOIN cells c ON c.cellNumber = p.cell
         WHERE p.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $prisoner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prisoner) {
        jsonError('Prisoner record not found.', 404);
    }
    $prisoner['photo_url'] = resolvePrisonerPhoto($prisoner['photo'] ?? null);

    $transfers = [];
    if ($pdo->query("SHOW TABLES LIKE 'transfer_requests'")->fetchColumn()) {
        $transferStmt = $pdo->prepare(
            'SELECT id, from_facility, to_facility, reason, status, requested_on, reviewed_on, decline_reason
             FROM transfer_requests
             WHERE inmate_id = ?
             ORDER BY requested_on DESC'
        );
        $transferStmt->execute([$id]);
        $transfers = $transferStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $releases = [];
    if ($pdo->query("SHOW TABLES LIKE 'release_requests'")->fetchColumn()) {
        $releaseColumns = $pdo->query('SHOW COLUMNS FROM release_requests')->fetchAll(PDO::FETCH_COLUMN);
        $prisonerColumn = in_array('prisoner_id', $releaseColumns, true) ? 'prisoner_id' : (in_array('prisonerId', $releaseColumns, true) ? 'prisonerId' : null);
        if ($prisonerColumn) {
            $releaseFields = ['id'];
            foreach (['requestId', 'status', 'request_type', 'releaseType', 'requested_release_date', 'releaseDate', 'reason', 'requestDate', 'requested_on', 'decisionDate', 'decision_date'] as $field) {
                if (in_array($field, $releaseColumns, true)) $releaseFields[] = $field;
            }
            $releaseSelect = implode(', ', array_map(fn($field) => "`$field`", $releaseFields));
            $releaseStmt = $pdo->prepare(
                "SELECT $releaseSelect FROM release_requests WHERE `$prisonerColumn` IN (?, ?) ORDER BY id DESC"
            );
            $releaseStmt->execute([$id, $prisoner['prisonerId']]);
            $releases = $releaseStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    jsonOK([
        'prisoner' => $prisoner,
        'transfers' => $transfers,
        'releases' => $releases,
    ]);
} catch (Throwable $error) {
    error_log('Prisoner detail error: ' . $error->getMessage());
    jsonError('Unable to load prisoner record. Please try again.', 500);
}