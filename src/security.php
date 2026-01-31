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

function session_cookie_path_for_portal(string $portal): string
{
    if (PHP_SAPI === 'cli-server') {
        return '/';
    }

    $defaultStudent = env_get('STUDENT_SESSION_COOKIE_PATH', '/students');
    $defaultAdmin = env_get('ADMIN_SESSION_COOKIE_PATH', '/');
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $uriPath = is_string($uriPath) ? $uriPath : '';

    if ($portal === 'student') {
        if (function_exists('app_base_path')) {
            $base = (string) app_base_path();
            if ($base === '/index.php') {
                return '/';
            }
            return rtrim($base, '/') . '/students';
        }
        $pos = strpos($uriPath, '/students');
        if ($pos !== false) {
            return substr($uriPath, 0, $pos + strlen('/students'));
        }
        return $defaultStudent;
    }

    if (function_exists('app_base_path')) {
        $base = (string) app_base_path();
        if ($base === '/index.php') {
            return '/';
        }
        return $base !== '' ? $base : $defaultAdmin;
    }

    return $defaultAdmin;
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
    $path = session_cookie_path_for_portal($portal);
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

function ensure_student_profiles_table(mysqli $conn): void
{
    $hasStudent = false;
    $res = $conn->query("SHOW TABLES LIKE 'student_profiles'");
    if ($res instanceof mysqli_result) {
        $hasStudent = $res->num_rows > 0;
        $res->free();
    }

    $hasUser = false;
    $res2 = $conn->query("SHOW TABLES LIKE 'user_profiles'");
    if ($res2 instanceof mysqli_result) {
        $hasUser = $res2->num_rows > 0;
        $res2->free();
    }

    if (!$hasStudent && $hasUser) {
        $conn->query("RENAME TABLE user_profiles TO student_profiles");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS student_profiles (
  user_id INT UNSIGNED NOT NULL PRIMARY KEY,
  first_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  address VARCHAR(255) NULL,
  photo_path VARCHAR(255) NULL,
  is_completed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $hasCompletedCol = false;
    $col = $conn->query("SHOW COLUMNS FROM student_profiles LIKE 'is_completed'");
    if ($col instanceof mysqli_result) {
        $hasCompletedCol = $col->num_rows > 0;
        $col->free();
    }
    if (!$hasCompletedCol) {
        $conn->query("ALTER TABLE student_profiles ADD COLUMN is_completed TINYINT(1) NOT NULL DEFAULT 0");
    }
}

function student_profile_completed(mysqli $conn, int $userId): bool
{
    if ($userId <= 0) {
        return false;
    }

    ensure_student_profiles_table($conn);

    $stmt = $conn->prepare('SELECT first_name, last_name, photo_path, is_completed FROM student_profiles WHERE user_id = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return false;
    }

    $explicit = isset($row['is_completed']) ? (int) $row['is_completed'] : 0;
    if ($explicit === 1) {
        return true;
    }

    $first = trim((string)($row['first_name'] ?? ''));
    $last = trim((string)($row['last_name'] ?? ''));
    $photo = trim((string)($row['photo_path'] ?? ''));
    $complete = ($first !== '' && $last !== '' && $photo !== '');

    if ($complete) {
        $upd = $conn->prepare('UPDATE student_profiles SET is_completed = 1 WHERE user_id = ?');
        if ($upd) {
            $upd->bind_param('i', $userId);
            $upd->execute();
            $upd->close();
        }
    }

    return $complete;
}

function enforce_student_profile_completed(mysqli $conn): void
{
    $uid = auth_user_id();
    if (!$uid || auth_role() !== 'student') {
        $loginUrl = function_exists('route_url') ? route_url('students/login') : '/students/login';
        header('Location: ' . $loginUrl);
        exit;
    }

    if (!student_profile_completed($conn, (int)$uid)) {
        $setupUrl = function_exists('route_url') ? route_url('students/profile-setup') : '/students/profile-setup';
        header('Location: ' . $setupUrl);
        exit;
    }
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
    if ($role === 'student') {
        header('Location: ' . route_url('students/home'));
    } else {
        header('Location: ' . route_url('admin/menu-1'));
    }
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
