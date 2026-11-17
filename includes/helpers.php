<?php
// ============================================================
// includes/helpers.php  —  Shared utilities
// ============================================================
require_once __DIR__ . '/config.php';

// ── Session ──────────────────────────────────────────────────
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Lax');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', 1);
        }
        session_start();
    }
    checkSessionTimeout();
}

function checkSessionTimeout(): void {
    // Only check timeout if user is logged in
    if (empty($_SESSION['user'])) {
        return;
    }

    $now = time();
    $lastActivity = $_SESSION['last_activity'] ?? $now;
    $timeout = SESSION_TIMEOUT;

    // If last activity was more than timeout seconds ago, destroy session
    if ($now - $lastActivity > $timeout) {
        session_destroy();
        jsonError('Session expired. Please log in again.', 401);
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = $now;
}

// ── CORS (for fetch() calls from same-origin HTML) ───────────
function setCORSHeaders(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($origin !== '' && parse_url($origin, PHP_URL_HOST) === $host) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// ── JSON output ───────────────────────────────────────────────
function jsonOut(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonOK(mixed $data = [], string $msg = 'ok'): never {
    jsonOut(['success' => true, 'msg' => $msg, 'data' => $data]);
}

function jsonError(string $msg, int $code = 400): never {
    jsonOut(['success' => false, 'msg' => $msg], $code);
}

function jsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

function auditLog(string $user, string $action, string $module, string $details): void {
    try {
        $pdo = getDB();
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $pdo->prepare(
            'INSERT INTO audit_log (`user`, action, module, details, ip) VALUES (?,?,?,?,?)'
        )->execute([$user, $action, $module, $details, $ip]);
    } catch (Throwable) {}
}

function resolvePrisonerPhoto(?string $photo): ?string {
    $photo = trim((string)$photo);
    if ($photo === '') return null;

    if (preg_match('/^data:image\/[a-z0-9.+-]+;base64,/i', $photo)) return $photo;
    if (preg_match('/^https?:\/\//i', $photo)) return $photo;

    $relative = ltrim(str_replace('\\', '/', $photo), '/');
    if (preg_match('/^(?:\.\/|\/)?uploads\/.+/i', $relative)) {
        $root = realpath(__DIR__ . '/..');
        $file = realpath($root . DIRECTORY_SEPARATOR . $relative);
        if ($root && $file && is_file($file) && strncmp($file, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) === 0) {
            return $relative;
        }
        return $relative;
    }

    if (preg_match('/^(?:\.\/|\/)?[A-Za-z0-9_./-]+\.(?:jpe?g|png|gif|webp|bmp)$/i', $relative)) {
        $root = realpath(__DIR__ . '/..');
        $file = realpath($root . DIRECTORY_SEPARATOR . $relative);
        if ($root && $file && is_file($file) && strncmp($file, $root . DIRECTORY_SEPARATOR, strlen($root) + 1) === 0) {
            return $relative;
        }
    }

    return $photo;
}

// ── Auth guard ────────────────────────────────────────────────
function requireAuth(): void {
    startSecureSession();
    if (empty($_SESSION['user'])) {
        jsonError('Not authenticated', 401);
    }
}

function csrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): void {
    startSecureSession();
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonError('Invalid CSRF token', 403);
    }
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

function currentUserRole(): string {
    return normalizeRole(currentUser()['role'] ?? 'Guest');
}

function normalizeRole(string $role): string {
    $role = trim($role);
    if ($role === '') return 'Guest';
    return ucwords(strtolower(preg_replace('/[^a-z0-9 ]+/i', '', $role)));
}

function roleCategory(string $role = ''): string {
    $role = strtolower($role ?: currentUserRole());
    if (str_contains($role, 'system') || str_contains($role, 'administrator') || $role === 'admin') return 'admin';
    if (in_array($role, ['warden', 'officer', 'medical', 'clerk', 'user', 'staff'], true)) return 'user';
    return 'other';
}

function hasRole(array|string $allowed): bool {
    $allowedRoles = array_map('strtolower', (array)$allowed);
    $currentRole = strtolower(currentUserRole());
    if (in_array($currentRole, $allowedRoles, true)) return true;
    return in_array('admin', $allowedRoles, true) && roleCategory($currentRole) === 'admin';
}

function requireRole(array|string $allowed): void {
    if (!hasRole($allowed)) jsonError('Access denied for this role', 403);
}

// ── Password validation helper ───────────────────────────────
function validatePassword(string $password): array {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[!@#$%^&*()\-_=+\[\]{};:,.<>?]/', $password)) {
        $errors[] = 'Password must contain at least one special character (!@#$%^&* etc.)';
    }
    
    return $errors;
}
// ── Next padded ID helper ─────────────────────────────────────
function nextId(string $table, string $prefix, string $col): string {
    $pdo  = getDB();
    $stmt = $pdo->query("SELECT MAX(id) AS mx FROM `$table`");
    $max  = (int)($stmt->fetch()['mx'] ?? 0);
    return $prefix . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

// ── ACL (file-backed) helpers ─────────────────────────────────
function aclPath(): string {
    return __DIR__ . '/../data/permissions.json';
}

function loadACL(): array {
    $path = aclPath();
    if (!file_exists($path)) return [];
    $raw = @file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function saveACL(array $acl): bool {
    $path = aclPath();
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return (bool)@file_put_contents($path, json_encode($acl, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function roleHasACLPermission(string $role, string $store, string $action): ?bool {
    $acl = loadACL();
    $role = normalizeRole($role);
    if (!isset($acl[$role])) return null; // no custom ACL for this role
    $rules = $acl[$role] ?? [];
    if (isset($rules['*'])) {
        $allow = $rules['*'];
        return isset($allow[$action]) ? (bool)$allow[$action] : null;
    }
    if (!isset($rules[$store])) return null;
    $perm = $rules[$store];
    return isset($perm[$action]) ? (bool)$perm[$action] : null;
}

// Update requirePermission to consult ACL first when present
function requirePermission(string $action, string $store = ''): void {
    $category = roleCategory('');
    $store = strtolower(trim($store));
    $action = strtolower(trim($action));

    if ($category === 'admin') return;

    if ($store === 'medical' && !hasRole(['medical'])) {
        jsonError('Medical records require medical or administrator access', 403);
    }

    // Check file-backed ACL for an explicit allow/deny
    $role = currentUserRole();
    $aclDecision = roleHasACLPermission($role, $store, $action);
    if ($aclDecision === true) return;
    if ($aclDecision === false) jsonError('Your role cannot perform this action', 403);

    // Fallback to legacy behavior
    $storeRules = [
        'prisoners'            => ['get', 'save', 'delete'],
        'cells'                => ['get', 'save', 'delete'],
        'visitations'          => ['get', 'save', 'delete'],
        'incidents'            => ['get', 'save', 'delete'],
        'movements'            => ['get', 'save', 'delete'],
        'court'                => ['get', 'save', 'delete'],
        'reports'              => ['get', 'save', 'delete'],
        'medical'              => ['get', 'save', 'delete'],
        'shifts'               => ['get', 'save', 'delete'],
        'shift_swaps'          => ['get', 'save', 'delete'],
        'inventory_items'      => ['get'],
        'inventory_transactions' => ['get', 'save', 'delete'],
        'rehab_programs'       => ['get', 'save', 'delete'],
        'rehab_enrollments'    => ['get', 'save', 'delete'],
        'release_requests'     => ['get', 'save'],
        'transfer_requests'    => ['get', 'save'],
        'staff'                => [],
        'audit_log'            => [],
    ];

    $publicGetStores = ['prisoners', 'visitations', 'reports', 'dashboard_audit'];

    if ($category === 'other') {
        if ($action !== 'get' || !in_array($store, $publicGetStores, true)) {
            jsonError('Your role cannot perform this action', 403);
        }
        return;
    }

    if ($category === 'user') {
        if ($store === 'inventory_items' || $store === 'staff' || $store === 'audit_log') {
            jsonError('Your role cannot access this resource', 403);
        }
        if ($store === 'dashboard_audit' && $action !== 'get') {
            jsonError('Your role cannot perform this action', 403);
        }
        if ($store !== '' && isset($storeRules[$store]) && $action !== 'get' && !in_array($action, $storeRules[$store], true)) {
            jsonError('Your role is not permitted to perform this action', 403);
        }
        return;
    }
}
