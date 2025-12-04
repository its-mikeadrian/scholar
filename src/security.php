<?php
// security.php: session hardening and CSRF utilities
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_handler.php';
require_once __DIR__ . '/remember_me.php';

function request_portal(): string
{
    $uri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
    $script = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
    $p = $uri !== '' ? $uri : $script;
    return (strpos($p, '/students/') !== false) ? 'student' : 'admin';
}

function secure_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Harden session behavior
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Configure cookie params from environment
    $domain = env_get('SESSION_COOKIE_DOMAIN', '');
    $portal = request_portal();
    $path = $portal === 'student' ? env_get('STUDENT_SESSION_COOKIE_PATH', '/students') : env_get('ADMIN_SESSION_COOKIE_PATH', '/');
    $secure = $isHttps || filter_var(env_get('SESSION_COOKIE_SECURE', ''), FILTER_VALIDATE_BOOLEAN);
    $samesite = env_get('SESSION_COOKIE_SAMESITE', 'Strict');
    $name = $portal === 'student'
        ? env_get('STUDENT_SESSION_NAME', env_get('SESSION_NAME', 'STEELSYNCSESSID') . '_STUD')
        : env_get('ADMIN_SESSION_NAME', env_get('SESSION_NAME', 'STEELSYNCSESSID') . '_ADM');
    session_name($name);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $path,
        'domain' => $domain,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => $samesite,
    ]);

    // Register encrypted DB-backed handler
    $pdo = get_db_connection();
    $handler = new DbSessionHandler($pdo);
    session_set_save_handler($handler, true);

    session_start();

    $_SESSION['portal'] = $portal;

    // Opportunistic auto-login via Remember Me
    if (empty($_SESSION['auth_user_id'])) {
        remember_me_auto_login();
    }

    // Apply rolling cookie for student sessions (1 week by default)
    try {
        $uid = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : 0;
        if ($uid > 0) {
            $role = isset($_SESSION['auth_role']) ? normalize_role((string)$_SESSION['auth_role']) : 'student';
            if ($role === 'student') {
                $studentTtl = (int) env_get('STUDENT_SESSION_LIFETIME_SECONDS', '604800');
                $params = session_get_cookie_params();
                setcookie(session_name(), session_id(), [
                    'expires' => time() + $studentTtl,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => $params['secure'] ?? false,
                    'httponly' => $params['httponly'] ?? true,
                    'samesite' => $params['samesite'] ?? 'Strict',
                ]);
            }
        }
    } catch (Throwable $e) {
    }
}

function refresh_session_cookie_role_ttl(): void
{
    secure_session_start();
    try {
        $uid = isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : 0;
        if ($uid > 0) {
            $role = isset($_SESSION['auth_role']) ? normalize_role((string)$_SESSION['auth_role']) : 'student';
            if ($role === 'student') {
                $studentTtl = (int) env_get('STUDENT_SESSION_LIFETIME_SECONDS', '604800');
                $params = session_get_cookie_params();
                setcookie(session_name(), session_id(), [
                    'expires' => time() + $studentTtl,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => $params['secure'] ?? false,
                    'httponly' => $params['httponly'] ?? true,
                    'samesite' => $params['samesite'] ?? 'Strict',
                ]);
            }
        }
    } catch (Throwable $e) {
    }
}

function csrf_token(): string
{
    secure_session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_validate(): bool
{
    secure_session_start();
    $expected = $_SESSION['csrf_token'] ?? '';
    $provided = $_POST['csrf_token'] ?? '';
    if ($expected === '' || $provided === '') {
        return false;
    }
    // Do NOT rotate token here to avoid invalidation for subsequent AJAX calls
    // Keep token stable during session; rotate only on explicit logout/login.
    return hash_equals($expected, $provided);
}

function auth_user_id(): ?int
{
    secure_session_start();
    return isset($_SESSION['auth_user_id']) ? (int)$_SESSION['auth_user_id'] : null;
}

function auth_role(): string
{
    secure_session_start();
    $role = $_SESSION['auth_role'] ?? null;
    if ($role) {
        return normalize_role($role);
    }
    $uid = auth_user_id();
    if ($uid) {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$uid]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $role = normalize_role($row['role'] ?? 'student');
            // Persist normalized role to DB if different
            if (!empty($row['role']) && $row['role'] !== $role) {
                $upd = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
                $upd->execute([$role, $uid]);
            }
            $_SESSION['auth_role'] = $role;
            return $role;
        } catch (Throwable $e) {
        }
    }
    return 'student';
}

function require_role(array $allowed): void
{
    $role = auth_role();
    foreach ($allowed as $al) {
        if ($role === $al) {
            return;
        }
    }
    $_SESSION['error'] = 'Insufficient permissions to access this page.';
    header('Location: ' . route_url('menu-1'));
    exit;
}

function user_can_manage_role(string $currentRole, string $targetRole): bool
{
    if ($currentRole === 'superadmin') {
        return in_array($targetRole, ['student', 'admin', 'superadmin'], true);
    }
    if ($currentRole === 'admin') {
        return $targetRole === 'student';
    }
    return false;
}

function session_timeout_warn_script(int $warnBeforeSeconds = 60): string
{
    $ttl = (int) env_get('SESSION_LIFETIME_SECONDS', '1800');
    $warnAt = max(1, $ttl - $warnBeforeSeconds);
    return '<script>(function(){var ttl=' . $ttl . ',warnAt=' . $warnAt . ';var t=0;function reset(){t=0;}function tick(){t++;if(t===warnAt){var el=document.createElement("div");el.className="fixed top-3 left-1/2 -translate-x-1/2 rounded-md bg-yellow-100 text-yellow-800 px-4 py-2 shadow";el.textContent="Your session will expire soon due to inactivity.";document.body.appendChild(el);}if(t>=ttl){window.location.href="logout.php";}}["mousemove","keydown","click","scroll"].forEach(function(e){window.addEventListener(e,reset,{passive:true});});setInterval(tick,1000);}());</script>';
}
function normalize_role(string $role): string
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
