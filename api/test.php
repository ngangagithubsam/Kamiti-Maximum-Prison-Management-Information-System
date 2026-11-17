<?php
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();
requireAuth();
requireRole('Administrator');

try {
    require_once __DIR__ . '/../includes/config.php';
    $pdo = getDB();
    
    // Test 1: Database connection
    $result = $pdo->query("SELECT 1")->fetch();
    if (!$result) throw new Exception("Database query failed");
    
    // Test 2: Check staff table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'staff'")->fetchAll();
    if (empty($tables)) throw new Exception("Staff table does not exist");
    
    // Test 3: Check staff records
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM staff");
    $row = $stmt->fetch();
    $staffCount = $row['cnt'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'database' => 'Connected',
        'staff_count' => $staffCount,
        'php_version' => phpversion()
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Diagnostic check failed'
    ], JSON_PRETTY_PRINT);
}
