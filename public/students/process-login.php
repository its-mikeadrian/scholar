<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../src/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (!empty($_SESSION['auth_user_id']) && auth_role() === 'student') {
    $userId = (int) $_SESSION['auth_user_id'];
    $target = student_profile_completed($conn, $userId) ? route_url('students/home') : route_url('students/profile-setup');
    header('Location: ' . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . route_url('students/login'));
    exit;
}

if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
    header('Location: ' . route_url('students/login'));
    exit;
}

$username = isset($_POST['signin-username']) ? trim((string)$_POST['signin-username']) : '';
$password = isset($_POST['signin-password']) ? (string)$_POST['signin-password'] : '';

if ($username === '' || $password === '') {
    $_SESSION['error'] = 'Username and password are required.';
    header('Location: ' . route_url('students/login'));
    exit;
}

$sql = "SELECT id, username, email, password, role, is_active FROM users WHERE username = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    $_SESSION['error'] = 'Server error. Please try again later.';
    header('Location: ' . route_url('students/login'));
    exit;
}
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

function audit_login_event(mysqli $conn, ?int $userId, string $username, string $outcome, ?string $role, ?string $ip, ?string $ua): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS login_audit (id INT UNSIGNED NOT NULL AUTO_INCREMENT, user_id INT UNSIGNED NULL, username VARCHAR(64) NOT NULL, role VARCHAR(32) NULL, outcome VARCHAR(32) NOT NULL, ip_address VARCHAR(45) NULL, user_agent VARCHAR(255) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $s = $conn->prepare('INSERT INTO login_audit (user_id, username, role, outcome, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
    if ($s) {
        $s->bind_param('isssss', $userId, $username, $role, $outcome, $ip, $ua);
        $s->execute();
        $s->close();
    }
}

$ipAddr = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null;
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null;

if (!$user) {
    password_verify($password, password_hash('dummy', PASSWORD_DEFAULT));
    audit_login_event($conn, null, $username, 'invalid_credentials', null, $ipAddr, $ua);
    $_SESSION['error'] = 'Invalid username or password.';
    header('Location: ' . route_url('students/login'));
    exit;
}

// Load current attempt state
$attemptRow = null;
$attemptStmt = $conn->prepare('SELECT failed_count, last_failed_at, locked_until FROM login_attempts WHERE user_id = ? LIMIT 1');
if ($attemptStmt) {
    $attemptStmt->bind_param('i', $user['id']);
    $attemptStmt->execute();
    $attemptRes = $attemptStmt->get_result();
    $attemptRow = $attemptRes ? $attemptRes->fetch_assoc() : null;
    $attemptStmt->close();
}

if ($attemptRow && !empty($attemptRow['locked_until'])) {
    $lockedTs = strtotime($attemptRow['locked_until']);
    if ($lockedTs !== false && $lockedTs > time()) {
        $mins = max(1, (int) ceil(($lockedTs - time()) / 60));
        audit_login_event($conn, (int)$user['id'], (string)$user['username'], 'locked', normalize_role((string)($user['role'] ?? 'student')), $ipAddr, $ua);
        $_SESSION['error'] = 'Account locked due to multiple failed attempts. Try again in ' . $mins . ' minute(s).';
        header('Location: ' . route_url('students/login'));
        exit;
    }
}

$hashed = $user['password'] ?? '';
if (!$hashed || !password_verify($password, $hashed)) {
    $failed = ($attemptRow ? (int)$attemptRow['failed_count'] : 0) + 1;
    $lockThreshold = 5;
    $lockMinutes = 15;
    $lockedUntil = null;
    if ($failed >= $lockThreshold) {
        $lockedUntil = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
    }

    $upsert = $conn->prepare('INSERT INTO login_attempts (user_id, failed_count, last_failed_at, locked_until) VALUES (?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE failed_count = VALUES(failed_count), last_failed_at = VALUES(last_failed_at), locked_until = VALUES(locked_until)');
    if ($upsert) {
        $upsert->bind_param('iis', $user['id'], $failed, $lockedUntil);
        $upsert->execute();
        $upsert->close();
    }

    audit_login_event($conn, (int)$user['id'], (string)$user['username'], 'invalid_credentials', normalize_role((string)($user['role'] ?? 'student')), $ipAddr, $ua);
    if ($lockedUntil) {
        $_SESSION['error'] = 'Too many failed login attempts. Your account is locked for ' . $lockMinutes . ' minutes.';
    } else {
        $_SESSION['error'] = 'Invalid username or password.';
    }
    header('Location: ' . route_url('students/login'));
    exit;
}

$clear = $conn->prepare('DELETE FROM login_attempts WHERE user_id = ?');
if ($clear) {
    $clear->bind_param('i', $user['id']);
    $clear->execute();
    $clear->close();
}

if (password_needs_rehash($hashed, PASSWORD_DEFAULT)) {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $rehashStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    if ($rehashStmt) {
        $rehashStmt->bind_param('si', $newHash, $user['id']);
        $rehashStmt->execute();
        $rehashStmt->close();
    }
}

$normalizedRole = normalize_role((string)($user['role'] ?? 'student'));
$activeFlag = isset($user['is_active']) ? (int)$user['is_active'] : 1;
if ($activeFlag !== 1) {
    audit_login_event($conn, (int)$user['id'], (string)$user['username'], 'inactive', $normalizedRole, $ipAddr, $ua);
    $_SESSION['error'] = 'Account is deactivated. Contact support.';
    header('Location: ' . route_url('students/login'));
    exit;
}
// Deny non-students
if (!in_array($normalizedRole, ['student'], true)) {
    audit_login_event($conn, (int)$user['id'], (string)$user['username'], 'role_denied', $normalizedRole, $ipAddr, $ua);
    $_SESSION['error'] = 'Invalid username or password.';
    header('Location: ' . route_url('students/login'));
    exit;
}

// Generate OTP for student login
$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);
$expiresAt = date('Y-m-d H:i:s', time() + 5 * 60);
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

$insertSql = "INSERT INTO login_otp (user_id, email, otp, expires_at, is_used, ip_address, attempt_count) VALUES (?, ?, ?, ?, 0, ?, 0)";
$ins = $conn->prepare($insertSql);
if (!$ins) {
    $_SESSION['error'] = 'Server error (OTP). Please try again later.';
    header('Location: ' . route_url('students/login'));
    exit;
}
$ins->bind_param('issss', $user['id'], $user['email'], $otpHash, $expiresAt, $ip);
$ins->execute();
$ins->close();

audit_login_event($conn, (int)$user['id'], (string)$user['username'], 'otp_sent', $normalizedRole, $ipAddr, $ua);

// Send email OTP
try {
    $mail = new PHPMailer(true);
    configureMailer($mail);
    applyFromAddress($mail, 'Login');
    $mail->addAddress($user['email'], $user['username']);
    $mail->Subject = 'Your Login OTP';
    $mail->isHTML(true);
    $mail->Body = '<p>Hi ' . htmlspecialchars($user['username']) . ',</p>' .
        '<p>Your one-time password (OTP) is: <strong>' . htmlspecialchars($otp) . '</strong></p>' .
        '<p>This code will expire in 5 minutes.</p>' .
        '<p>If you did not initiate this login, you can ignore this message.</p>';
    $mail->AltBody = "Your OTP is: $otp (expires in 5 minutes)";
    $mail->send();
} catch (Throwable $e) {
    error_log('Failed to send OTP email (student): ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to send OTP email. Please try again later.';
    header('Location: ' . route_url('students/login'));
    exit;
}

$_SESSION['pending_student_user_id'] = (int) $user['id'];
$_SESSION['pending_student_username'] = $user['username'];
$_SESSION['pending_student_email'] = $user['email'];
if (!empty($_POST['remember_me'])) {
    $_SESSION['pending_remember_me'] = true;
}
$_SESSION['success'] = 'OTP sent to your email. Please check and verify.';

header('Location: ' . route_url('students/login'));
exit;
