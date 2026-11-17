<?php
// ============================================================
// includes/config.php  —  Database connection
// Edit DB_PASS if your XAMPP MySQL has a root password set
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // default XAMPP = no password
define('DB_NAME', 'securecell');

// ── Session timeout (in seconds) ──────────────────────────
define('SESSION_TIMEOUT', 1200);  // 20 minutes

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
