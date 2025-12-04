<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/db.php';

function remember_normalize_role(string $role): string
{
    switch ($role) {
        case 'employee':
            return 'student';
        case 'support_admin':
        case 'hr_admin':
            return 'admin';
        case 'super_admin':
            return 'superadmin';
        case 'student':
        case 'admin':
        case 'superadmin':
            return $role;
        default:
            return 'student';
    }
}

function remember_cookie_name(): string
{
    return env_get('REMEMBER_ME_COOKIE_NAME', 'STEELSYNC_REMEMBER');
}

function remember_cookie_params(): array
{
    $domain = env_get('SESSION_COOKIE_DOMAIN', '');
    $path = env_get('SESSION_COOKIE_PATH', '/');
    $secure = filter_var(env_get('SESSION_COOKIE_SECURE', ''), FILTER_VALIDATE_BOOLEAN);
    $samesite = env_get('REMEMBER_ME_SAMESITE', env_get('SESSION_COOKIE_SAMESITE', 'Strict'));
    return [
        'expires' => time() + ((int)env_get('REMEMBER_ME_DURATION_DAYS', '30')) * 86400,
        'path' => $path,
        'domain' => $domain,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => $samesite,
    ];
}

function remember_me_set(int $userId): void
{
    $pdo = get_db_connection();
    $selector = bin2hex(random_bytes(8)); // 16 chars
    $validator = bin2hex(random_bytes(32)); // client secret
    $hash = hash('sha256', $validator);
    $expiresAt = date('Y-m-d H:i:s', time() + ((int)env_get('REMEMBER_ME_DURATION_DAYS', '30')) * 86400);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

    $stmt = $pdo->prepare('INSERT INTO `remember_tokens` (`user_id`, `selector`, `validator_hash`, `expires_at`, `ip_address`, `user_agent`) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $selector, $hash, $expiresAt, $ip, $ua]);

    $cookieName = remember_cookie_name();
    $cookieVal = base64_encode($selector . ':' . $validator);
    $params = remember_cookie_params();
    setcookie($cookieName, $cookieVal, $params);
}

function remember_me_clear(): void
{
    $pdo = get_db_connection();
    $cookieName = remember_cookie_name();
    if (!isset($_COOKIE[$cookieName])) {
        return;
    }
    [$selector] = explode(':', base64_decode($_COOKIE[$cookieName] ?? ''), 2);
    if (!empty($selector)) {
        $stmt = $pdo->prepare('DELETE FROM `remember_tokens` WHERE `selector` = ?');
        $stmt->execute([$selector]);
    }
    $params = remember_cookie_params();
    setcookie($cookieName, '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

function remember_me_auto_login(): void
{
    secure_session_start();
    if (!empty($_SESSION['auth_user_id'])) {
        return; // already logged in
    }
    $cookieName = remember_cookie_name();
    if (empty($_COOKIE[$cookieName])) {
        return;
    }
    $pdo = get_db_connection();
    $raw = base64_decode($_COOKIE[$cookieName]);
    if (!$raw || strpos($raw, ':') === false) {
        return;
    }
    [$selector, $validator] = explode(':', $raw, 2);
    $stmt = $pdo->prepare('SELECT `user_id`, `validator_hash`, `expires_at` FROM `remember_tokens` WHERE `selector` = ? LIMIT 1');
    $stmt->execute([$selector]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return;
    }
    if (strtotime($row['expires_at']) <= time()) {
        // expired, cleanup
        $pdo->prepare('DELETE FROM `remember_tokens` WHERE `selector` = ?')->execute([$selector]);
        return;
    }
    $calc = hash('sha256', $validator);
    if (!hash_equals($row['validator_hash'], $calc)) {
        // possible theft, wipe all tokens for this user
        $pdo->prepare('DELETE FROM `remember_tokens` WHERE `user_id` = ?')->execute([$row['user_id']]);
        return;
    }
    // Token rotation
    $newValidator = bin2hex(random_bytes(32));
    $newHash = hash('sha256', $newValidator);
    $pdo->prepare('UPDATE `remember_tokens` SET `validator_hash` = ?, `last_used_at` = NOW() WHERE `selector` = ?')->execute([$newHash, $selector]);

    $roleRow = null;
    try {
        $st2 = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $st2->execute([$row['user_id']]);
        $roleRow = $st2->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
    }
    $role = remember_normalize_role((string)($roleRow['role'] ?? 'student'));
    $uri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
    $script = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
    $p = $uri !== '' ? $uri : $script;
    $isStudentPortal = (strpos($p, '/students/') !== false);
    if (($isStudentPortal && $role !== 'student') || (!$isStudentPortal && $role === 'student')) {
        error_log('Remember Me auto-login blocked due to portal-role mismatch');
        return;
    }

    // Establish session
    $_SESSION['auth_user_id'] = (int)$row['user_id'];
    $_SESSION['info'] = 'Logged in via Remember Me.';
    session_regenerate_id(true);

    $cookieVal = base64_encode($selector . ':' . $newValidator);
    $params = remember_cookie_params();
    setcookie($cookieName, $cookieVal, $params);
}
