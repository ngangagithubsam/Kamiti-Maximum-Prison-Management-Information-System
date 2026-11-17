<?php
// ============================================================
// api/auth.php  —  Login / Logout / Status
// ============================================================
require_once __DIR__ . '/../includes/helpers.php';

startSecureSession();
setCORSHeaders();

$body = jsonBody();
$action = $_GET['action'] ?? $body['action'] ?? '';

switch ($action) {

    // ── LOGIN ─────────────────────────────────────────────────
    case 'login':
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        if (!$username || !$password) {
            jsonError('Invalid login credentials', 401);
        }

        $pdo  = getDB();
        $stmt = $pdo->prepare(
            'SELECT * FROM staff WHERE LOWER(username) = LOWER(?) AND status = "Active" LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Update last login
        $pdo->prepare('UPDATE staff SET lastlogin = NOW() WHERE id = ?')
            ->execute([$user['id']]);

        // Store in session (never store raw password)
        unset($user['password']);
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        auditLog($user['firstname'].' '.$user['lastname'], 'LOGIN', 'Auth', 'Successful login');
        jsonOK([
            'name'    => $user['firstname'] . ' ' . $user['lastname'],
            'role'    => $user['role'],
            'staffId' => $user['staffId'],
        ], 'Login successful');


    // ── LOGOUT ────────────────────────────────────────────────
    case 'logout':
        $u = currentUser();
        $name = ($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? '');
        auditLog(trim($name), 'LOGOUT', 'Auth', 'User logged out');
        session_destroy();
        jsonOK([], 'Logged out');


    // ── STATUS ────────────────────────────────────────────────
    case 'status':
        if (!empty($_SESSION['user'])) {
            $u = $_SESSION['user'];
            jsonOK([
                'authenticated' => true,
                'name'    => $u['firstname'] . ' ' . $u['lastname'],
                'role'    => $u['role'],
                'staffId' => $u['staffId'],
            ]);
        }
        jsonOK(['authenticated' => false]);


    default:
        jsonError('Unknown action');
}
