<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();
requirePermission('get', 'prisoners');

$pdo = getDB();
$body = jsonBody();
$prisonerId = (int)($body['prisoner_id'] ?? $_GET['prisoner_id'] ?? 0);
if ($prisonerId <= 0) jsonError('Valid prisoner ID is required');

$stmt = $pdo->prepare('SELECT id, prisonerId, firstname, middlename, lastname, dob, gender, natid, nationality, address, emname, emphone, admdate, cell, sentence, `release`, offense, security, status, prisonerType, caseNumber, photo, notes, created_at FROM prisoners WHERE id = ? LIMIT 1');
$stmt->execute([$prisonerId]);
$prisoner = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$prisoner) jsonError('Prisoner not found', 404);
$prisoner['photo_url'] = resolvePrisonerPhoto($prisoner['photo'] ?? null);

$assessments = [];
try {
    $stmt = $pdo->prepare('SELECT pp.id, pp.prisoner_id, pp.assessment_date, pp.category, pp.activity, pp.previous_score, pp.current_score, pp.change_summary, pp.comments, pp.recommendations, pp.next_review_date, pp.created_by, pp.created_at, pp.updated_at, CONCAT(s.firstname, " ", s.lastname) AS created_by_name FROM prisoner_progress pp LEFT JOIN staff s ON s.id = pp.created_by WHERE pp.prisoner_id = ? ORDER BY pp.assessment_date DESC, pp.id DESC');
    $stmt->execute([$prisonerId]);
    $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $error) {
    $assessments = [];
}

jsonOK(['prisoner' => $prisoner, 'assessments' => $assessments]);
