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
    header('Location: ' . route_url('verify-otp'));
    exit;
}

if (!isset($_SESSION['pending_user_id'])) {
    $_SESSION['error'] = 'No pending login. Please login again.';
    header('Location: ' . route_url(''));
    exit;
}

if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
    header('Location: ' . route_url('verify-otp'));
    exit;
}

$userId = (int) $_SESSION['pending_user_id'];
$email = isset($_SESSION['pending_email']) ? $_SESSION['pending_email'] : '';
$username = isset($_SESSION['pending_username']) ? $_SESSION['pending_username'] : '';

// Rate limiting: 60s minimum interval and 5 sends per 15 minutes
$minIntervalSec = 60;
$maxSendsWindowMin = 15;
$maxSendsCount = 5;

$lastStmt = $conn->prepare('SELECT created_at, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS elapsed FROM login_otp WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$lastStmt->bind_param('i', $userId);
$lastStmt->execute();
$lastRes = $lastStmt->get_result();
$lastRow = $lastRes ? $lastRes->fetch_assoc() : null;
$lastStmt->close();

if ($lastRow && isset($lastRow['elapsed'])) {
    $elapsed = (int) $lastRow['elapsed'];
    $elapsed = max(0, $elapsed);
    $remaining = $minIntervalSec - $elapsed;
    if ($remaining > 0) {
        $_SESSION['error'] = 'Please wait ' . $remaining . ' seconds before requesting another code.';
        header('Location: ' . route_url('verify-otp'));
        exit;
    }
}

$windowStmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM login_otp WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)');
$windowStmt->bind_param('ii', $userId, $maxSendsWindowMin);
$windowStmt->execute();
$windowRes = $windowStmt->get_result();
$windowRow = $windowRes ? $windowRes->fetch_assoc() : ['cnt' => 0];
$windowStmt->close();

if ((int)$windowRow['cnt'] >= $maxSendsCount) {
    $_SESSION['error'] = 'Too many OTP requests. Please try again in ' . $maxSendsWindowMin . ' minutes.';
    header('Location: ' . route_url('verify-otp'));
    exit;
}

// Generate and store new OTP
$otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);
$expiresAt = date('Y-m-d H:i:s', time() + 5 * 60);
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

$insertSql = 'INSERT INTO login_otp (user_id, email, otp, expires_at, is_used, ip_address, attempt_count) VALUES (?, ?, ?, ?, 0, ?, 0)';
$ins = $conn->prepare($insertSql);
if (!$ins) {
    $_SESSION['error'] = 'Server error (OTP). Please try again later.';
    header('Location: ' . route_url('verify-otp'));
    exit;
}
$ins->bind_param('issss', $userId, $email, $otpHash, $expiresAt, $ip);
$ins->execute();
$ins->close();

// Send email
try {
    $mail = new PHPMailer(true);
    configureMailer($mail);
    applyFromAddress($mail, 'Login');
    $mail->addAddress($email, $username);
    $mail->Subject = 'Your Login OTP (Resent)';
    $mail->isHTML(true);
    $mail->Body = '<p>Hi ' . htmlspecialchars($username) . ',</p>' .
        '<p>Your one-time password (OTP) is: <strong>' . htmlspecialchars($otp) . '</strong></p>' .
        '<p>This code will expire in 5 minutes.</p>' .
        '<p>If you did not initiate this, you can ignore this message.</p>';
    $mail->AltBody = "Your OTP is: $otp (expires in 5 minutes)";
    $mail->send();
    $_SESSION['success'] = 'A new OTP has been sent to your email.';
} catch (Throwable $e) {
    error_log('Failed to send resent OTP email: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to send OTP email. Please try again later.';
}

header('Location: ' . route_url('verify-otp'));
exit;
