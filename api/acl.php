<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
$path = __DIR__ . '/../data/permissions.json';
$body = jsonBody();

// Only admins may change ACLs
if ($method === 'POST') {
    $cu = currentUser();
    if (roleCategory($cu['role'] ?? '') !== 'admin') {
        jsonError('Only administrators may modify permissions', 403);
    }
    validateCsrfToken($body['csrf_token'] ?? '');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) jsonError('Invalid JSON payload', 400);
    $ok = @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($ok === false) jsonError('Failed to write ACL file', 500);
    jsonOK([], 'Permissions saved');
}

// GET: return ACL
if ($method === 'GET') {
    if (!file_exists($path)) jsonOK([]);
    $raw = @file_get_contents($path);
    $data = json_decode($raw, true);
    jsonOK($data ?? []);
}

jsonError('Unsupported method', 405);
