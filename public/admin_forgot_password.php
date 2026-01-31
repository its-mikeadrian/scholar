<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../src/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . route_url('admin'));
    exit;
}

if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
    header('Location: ' . route_url('admin'));
    exit;
}

$email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Enter a valid email address.';
    header('Location: ' . route_url('admin'));
    exit;
}

$genericOk = 'If an administrator account exists for that email, a reset link has been sent.';
$ipAddr = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null;
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

function ensure_password_reset_tokens_table(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `selector` char(16) NOT NULL,
  `validator_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_selector` (`selector`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function ensure_login_audit_table(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS login_audit (id INT UNSIGNED NOT NULL AUTO_INCREMENT, user_id INT UNSIGNED NULL, username VARCHAR(64) NOT NULL, role VARCHAR(32) NULL, outcome VARCHAR(32) NOT NULL, ip_address VARCHAR(45) NULL, user_agent VARCHAR(255) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function audit_event(mysqli $conn, ?int $userId, string $usernameOrEmail, ?string $role, string $outcome, ?string $ip, ?string $ua): void
{
    ensure_login_audit_table($conn);
    $s = $conn->prepare('INSERT INTO login_audit (user_id, username, role, outcome, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
    if ($s) {
        $s->bind_param('isssss', $userId, $usernameOrEmail, $role, $outcome, $ip, $ua);
        $s->execute();
        $s->close();
    }
}

function rate_limited_for_reset(PDO $pdo, string $email, ?int $userId, ?string $ip): bool
{
    $minIntervalSeconds = 60;
    $windowMinutes = 15;
    $maxPerWindowIp = 8;
    $maxPerWindowUser = 4;

    if ($ip) {
        $st = $pdo->prepare('SELECT COUNT(*) AS c, MAX(created_at) AS last_at FROM password_reset_tokens WHERE ip_address = ? AND created_at >= (NOW() - INTERVAL ' . $windowMinutes . ' MINUTE)');
        $st->execute([$ip]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $c = isset($row['c']) ? (int)$row['c'] : 0;
        $lastAt = isset($row['last_at']) ? (string)$row['last_at'] : '';
        if ($c >= $maxPerWindowIp) {
            return true;
        }
        if ($lastAt !== '') {
            $ts = strtotime($lastAt);
            if ($ts !== false && (time() - $ts) < $minIntervalSeconds) {
                return true;
            }
        }
    }

    if ($userId) {
        $st = $pdo->prepare('SELECT COUNT(*) AS c, MAX(created_at) AS last_at FROM password_reset_tokens WHERE user_id = ? AND created_at >= (NOW() - INTERVAL ' . $windowMinutes . ' MINUTE)');
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        $c = isset($row['c']) ? (int)$row['c'] : 0;
        $lastAt = isset($row['last_at']) ? (string)$row['last_at'] : '';
        if ($c >= $maxPerWindowUser) {
            return true;
        }
        if ($lastAt !== '') {
            $ts = strtotime($lastAt);
            if ($ts !== false && (time() - $ts) < $minIntervalSeconds) {
                return true;
            }
        }
    }

    return false;
}

try {
    $pdo = get_db_connection();
    ensure_password_reset_tokens_table($pdo);
} catch (Throwable $e) {
    $_SESSION['success'] = $genericOk;
    header('Location: ' . route_url('admin'));
    exit;
}

$user = null;
try {
    $st = $pdo->prepare('SELECT id, username, email, role, is_active FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    $user = null;
}

$userId = $user && isset($user['id']) ? (int)$user['id'] : null;
$role = $user && isset($user['role']) ? normalize_role((string)$user['role']) : null;
$isActive = $user && array_key_exists('is_active', $user) ? ((int)$user['is_active'] === 1) : false;
$isAdminRole = $role !== null && in_array($role, ['admin', 'superadmin'], true);

audit_event($conn, $userId, $email, $role, 'pw_reset_requested', $ipAddr, $ua);

if (!$userId || !$isActive || !$isAdminRole) {
    $_SESSION['success'] = $genericOk;
    header('Location: ' . route_url('admin'));
    exit;
}

if (rate_limited_for_reset($pdo, $email, $userId, $ipAddr)) {
    $_SESSION['success'] = $genericOk;
    header('Location: ' . route_url('admin'));
    exit;
}

try {
    $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);
} catch (Throwable $e) {
}

$selector = bin2hex(random_bytes(8));
$validator = bin2hex(random_bytes(32));
$validatorHash = hash('sha256', $validator);
$expiresAt = date('Y-m-d H:i:s', time() + 30 * 60);

try {
    $stmt = $pdo->prepare('INSERT INTO password_reset_tokens (user_id, selector, validator_hash, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $selector, $validatorHash, $expiresAt, $ipAddr, $ua]);
} catch (Throwable $e) {
    $_SESSION['success'] = $genericOk;
    header('Location: ' . route_url('admin'));
    exit;
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? (string)$_SERVER['HTTP_HOST'] : '';
$resetPath = route_url('admin/reset-password');
$resetUrl = $host !== ''
    ? ($scheme . '://' . $host . $resetPath . '?selector=' . rawurlencode($selector) . '&token=' . rawurlencode($validator))
    : ($resetPath . '?selector=' . rawurlencode($selector) . '&token=' . rawurlencode($validator));

try {
    $mail = new PHPMailer(true);
    configureMailer($mail);
    applyFromAddress($mail, 'Password Reset');
    $mail->addAddress($email, (string)($user['username'] ?? 'Admin'));
    $mail->Subject = 'Reset your administrator password';
    $mail->isHTML(true);
    $mail->Body =
        '<p>We received a request to reset your administrator password.</p>' .
        '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Click here to reset your password</a></p>' .
        '<p>This link expires in 30 minutes and can only be used once.</p>' .
        '<p>If you did not request this, you can ignore this email.</p>';
    $mail->AltBody = "Reset your administrator password:\n$resetUrl\n\nThis link expires in 30 minutes.";
    $mail->send();
    audit_event($conn, $userId, (string)($user['username'] ?? $email), $role, 'pw_reset_sent', $ipAddr, $ua);
} catch (Throwable $e) {
    audit_event($conn, $userId, (string)($user['username'] ?? $email), $role, 'pw_reset_email_failed', $ipAddr, $ua);
}

$_SESSION['success'] = $genericOk;
header('Location: ' . route_url('admin'));
exit;

