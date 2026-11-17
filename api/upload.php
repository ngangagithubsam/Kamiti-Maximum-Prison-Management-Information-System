<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();

validateCsrfToken($_POST['csrf_token'] ?? '');

$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

if (empty($_FILES['file'])) {
    jsonError('No file uploaded', 400);
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonError('Upload error', 400);
}

if ((int)$file['size'] > 10 * 1024 * 1024) {
    jsonError('File is too large. Maximum size is 10 MB', 400);
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$allowed = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
];
if (!isset($allowed[$mime])) {
    jsonError('Unsupported file type', 400);
}

$name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
$dest = $uploadDir . '/' . $name;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    jsonError('Failed to save file', 500);
}

// return relative path
$rel = 'uploads/' . $name;
jsonOK(['path' => $rel], 'Uploaded');
