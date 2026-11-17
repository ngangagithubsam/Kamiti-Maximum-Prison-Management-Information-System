<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();
requireRole(['Administrator', 'Warden', 'Officer']);

$pdo = getDB();
$pdo->exec("CREATE TABLE IF NOT EXISTS prisoner_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prisoner_id INT NOT NULL,
    assessment_date DATE NOT NULL,
    category VARCHAR(40) NOT NULL,
    activity VARCHAR(200) NOT NULL,
    previous_score DECIMAL(5,2) DEFAULT NULL,
    current_score DECIMAL(5,2) DEFAULT NULL,
    change_summary VARCHAR(255) DEFAULT '',
    comments TEXT DEFAULT NULL,
    recommendations TEXT DEFAULT NULL,
    next_review_date DATE DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_progress_prisoner_date (prisoner_id, assessment_date),
    CONSTRAINT fk_progress_prisoner FOREIGN KEY (prisoner_id) REFERENCES prisoners(id),
    CONSTRAINT fk_progress_creator FOREIGN KEY (created_by) REFERENCES staff(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$columns = $pdo->query("SHOW COLUMNS FROM prisoner_progress LIKE 'updated_at'")->fetch(PDO::FETCH_ASSOC);
if (!$columns) {
    $pdo->exec('ALTER TABLE prisoner_progress ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
}

$body = jsonBody();
$action = $body['action'] ?? ($_GET['action'] ?? 'list');

if ($action === 'list') {
    $prisonerId = (int)($body['prisoner_id'] ?? $_GET['prisoner_id'] ?? 0);
    if ($prisonerId <= 0) jsonError('Prisoner ID is required');
    $prisonerStmt = $pdo->prepare('SELECT id FROM prisoners WHERE id = ?');
    $prisonerStmt->execute([$prisonerId]);
    if ($prisonerId <= 0 || !$prisonerStmt->fetchColumn()) jsonError('Valid prisoner is required');
    $stmt = $pdo->prepare('SELECT pp.*, CONCAT(s.firstname, " ", s.lastname) AS created_by_name FROM prisoner_progress pp LEFT JOIN staff s ON s.id = pp.created_by WHERE pp.prisoner_id = ? ORDER BY pp.assessment_date DESC, pp.id DESC');
    $stmt->execute([$prisonerId]);
    jsonOK($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if (in_array($action, ['create', 'update'], true)) {
    validateCsrfToken($body['csrf_token'] ?? '');
    $record = $body['record'] ?? [];
    $prisonerId = (int)($record['prisoner_id'] ?? 0);
    $category = trim((string)($record['category'] ?? ''));
    $activity = trim((string)($record['activity'] ?? ''));
    $allowedCategories = ['Behaviour', 'Education', 'Vocational', 'Rehabilitation', 'Counselling', 'Achievement', 'Disciplinary'];
    $date = trim((string)($record['assessment_date'] ?? ''));
    $nextReview = trim((string)($record['next_review_date'] ?? ''));
    $prisonerStmt = $pdo->prepare('SELECT id FROM prisoners WHERE id = ?');
    $prisonerStmt->execute([$prisonerId]);
    if ($prisonerId <= 0 || !$prisonerStmt->fetchColumn()) jsonError('Valid prisoner is required');
    if (!in_array($category, $allowedCategories, true) || $activity === '') jsonError('Category and activity are required');
    if (!$date || !DateTime::createFromFormat('!Y-m-d', $date) || DateTime::createFromFormat('!Y-m-d', $date)->format('Y-m-d') !== $date) jsonError('A valid assessment date is required');
    if ($nextReview !== '' && (!DateTime::createFromFormat('!Y-m-d', $nextReview) || DateTime::createFromFormat('!Y-m-d', $nextReview)->format('Y-m-d') !== $nextReview)) jsonError('Next review date is invalid');
    if (strlen($activity) > 200) jsonError('Assessment activity is too long');

    $previous = $record['previous_score'] === '' || !isset($record['previous_score']) ? null : (float)$record['previous_score'];
    $current = $record['current_score'] === '' || !isset($record['current_score']) ? null : (float)$record['current_score'];
    if ($current === null) jsonError('Current score is required');
    foreach (['previous' => $previous, 'current' => $current] as $label => $score) {
        if ($score !== null && ($score < 0 || $score > 100)) jsonError(ucfirst($label) . ' score must be between 0 and 100');
    }
    $change = trim((string)($record['change_summary'] ?? ''));
    if ($previous !== null && $current !== null) {
        $delta = round($current - $previous, 2);
        $change = ($delta >= 0 ? '+' : '') . $delta . ' points';
    }
    $comments = trim((string)($record['comments'] ?? ''));
    $recommendations = trim((string)($record['recommendations'] ?? ''));
    if (strlen($change) > 255 || strlen($comments) > 10000 || strlen($recommendations) > 10000) jsonError('Assessment text is too long');
    $creator = (int)(currentUser()['id'] ?? 0);
    if ($action === 'create') {
        $stmt = $pdo->prepare('INSERT INTO prisoner_progress (prisoner_id, assessment_date, category, activity, previous_score, current_score, change_summary, comments, recommendations, next_review_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$prisonerId, $date, $category, $activity, $previous, $current, $change, $comments, $recommendations, $nextReview ?: null, $creator]);
        $id = (int)$pdo->lastInsertId();
        auditLog(trim((currentUser()['firstname'] ?? '') . ' ' . (currentUser()['lastname'] ?? '')), 'CREATE', 'prisoner_progress', "Created progress record $id for prisoner $prisonerId");
        jsonOK(['id' => $id], 'Progress assessment recorded successfully.');
    }

    $id = (int)($record['id'] ?? 0);
    if ($id <= 0) jsonError('Valid assessment ID is required');
    $stmt = $pdo->prepare('SELECT id, prisoner_id FROM prisoner_progress WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing || (int)$existing['prisoner_id'] !== $prisonerId) jsonError('Assessment not found', 404);
    $stmt = $pdo->prepare('UPDATE prisoner_progress SET assessment_date = ?, category = ?, activity = ?, previous_score = ?, current_score = ?, change_summary = ?, comments = ?, recommendations = ?, next_review_date = ? WHERE id = ? AND prisoner_id = ?');
    $stmt->execute([$date, $category, $activity, $previous, $current, $change, $comments, $recommendations, $nextReview ?: null, $id, $prisonerId]);
    auditLog(trim((currentUser()['firstname'] ?? '') . ' ' . (currentUser()['lastname'] ?? '')), 'UPDATE', 'prisoner_progress', "Updated progress record $id for prisoner $prisonerId");
    jsonOK(['id' => $id], 'Progress assessment updated successfully.');
}

jsonError('Unknown action', 400);
