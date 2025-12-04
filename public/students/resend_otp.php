<?php
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . route_url('students/login'));
    exit;
}

if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
    header('Location: ' . route_url('students/login'));
    exit;
}

$minIntervalSec = 60;
$maxSendsWindowMin = 15;
$maxSendsCount = 5;

// Determine context: registration or login
if (isset($_SESSION['pending_registration_id'])) {
    $regId = (int) $_SESSION['pending_registration_id'];
    // Rate limiting: check when the pending registration was last updated (use created_at as last-send timestamp)
    $lastStmt = $conn->prepare('SELECT created_at FROM pending_registrations WHERE id = ? LIMIT 1');
    $lastStmt->bind_param('i', $regId);
    $lastStmt->execute();
    $lastRes = $lastStmt->get_result();
    $lastRow = $lastRes ? $lastRes->fetch_assoc() : null;
    $lastStmt->close();

    if ($lastRow && isset($lastRow['created_at'])) {
        $elapsedStmt = $conn->prepare('SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS elapsed FROM pending_registrations WHERE id = ? LIMIT 1');
        $elapsedStmt->bind_param('i', $regId);
        $elapsedStmt->execute();
        $elapsedRes = $elapsedStmt->get_result();
        $elapsedRow = $elapsedRes ? $elapsedRes->fetch_assoc() : null;
        $elapsedStmt->close();
        $elapsed = isset($elapsedRow['elapsed']) ? max(0, (int)$elapsedRow['elapsed']) : 0;
        $remaining = $minIntervalSec - $elapsed;
        if ($remaining > 0) {
            $_SESSION['error'] = 'Please wait ' . $remaining . ' seconds before requesting another code.';
            header('Location: ' . route_url('students/login'));
            exit;
        }
    }

    // create and send new registration OTP by updating pending_registrations
    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + 5 * 60);
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

    $upd = $conn->prepare('UPDATE pending_registrations SET otp = ?, expires_at = ?, is_used = 0, attempt_count = 0, ip_address = ?, created_at = NOW() WHERE id = ?');
    if ($upd) {
        $upd->bind_param('sssi', $otpHash, $expiresAt, $ip, $regId);
        $upd->execute();
        $upd->close();
    }

    $s = $conn->prepare('SELECT username, email FROM pending_registrations WHERE id = ? LIMIT 1');
    $s->bind_param('i', $regId);
    $s->execute();
    $sr = $s->get_result();
    $reg = $sr ? $sr->fetch_assoc() : null;
    $s->close();

    if (!$reg) {
        $_SESSION['error'] = 'No pending request. Please login or register.';
        header('Location: ' . route_url('students/login'));
        exit;
    }

    try {
        $mail = new PHPMailer(true);
        configureMailer($mail);
        applyFromAddress($mail, 'Register');
        $mail->addAddress($reg['email'], $reg['username']);
        $mail->Subject = 'Your Registration OTP (Resent)';
        $mail->isHTML(true);
        $mail->Body = '<p>Hi ' . htmlspecialchars($reg['username']) . ',</p><p>Your one-time password (OTP) is: <strong>' . htmlspecialchars($otp) . '</strong></p><p>This code will expire in 5 minutes.</p>';
        $mail->AltBody = "Your OTP is: $otp (expires in 5 minutes)";
        $mail->send();
        $_SESSION['success'] = 'A new OTP has been sent to your email.';
    } catch (Throwable $e) {
        error_log('Failed to send resent OTP email (registration): ' . $e->getMessage());
        $_SESSION['error'] = 'Unable to send OTP email. Please try again later.';
    }

    header('Location: ' . route_url('students/login'));
    exit;
} elseif (isset($_SESSION['pending_student_user_id'])) {
    $userId = (int) $_SESSION['pending_student_user_id'];
    $lastStmt = $conn->prepare('SELECT created_at, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS elapsed FROM login_otp WHERE user_id = ? ORDER BY id DESC LIMIT 1');
    $lastStmt->bind_param('i', $userId);
    $lastStmt->execute();
    $lastRes = $lastStmt->get_result();
    $lastRow = $lastRes ? $lastRes->fetch_assoc() : null;
    $lastStmt->close();

    if ($lastRow && isset($lastRow['elapsed'])) {
        $elapsed = max(0, (int)$lastRow['elapsed']);
        $remaining = $minIntervalSec - $elapsed;
        if ($remaining > 0) {
            $_SESSION['error'] = 'Please wait ' . $remaining . ' seconds before requesting another code.';
            header('Location: ' . route_url('students/login'));
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
        header('Location: ' . route_url('students/login'));
        exit;
    }

    // create and send new login OTP
    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + 5 * 60);
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

    $ins = $conn->prepare('INSERT INTO login_otp (user_id, email, otp, expires_at, is_used, ip_address, attempt_count) VALUES (?, (SELECT email FROM users WHERE id = ?), ?, ?, 0, ?, 0)');
    $ins->bind_param('iisss', $userId, $userId, $otpHash, $expiresAt, $ip);
    $ins->execute();
    $ins->close();

    // send email
    $email = $_SESSION['pending_student_email'] ?? null;
    $uname = $_SESSION['pending_student_username'] ?? null;
    try {
        $mail = new PHPMailer(true);
        configureMailer($mail);
        applyFromAddress($mail, 'Login');
        $mail->addAddress($email, $uname);
        $mail->Subject = 'Your Login OTP (Resent)';
        $mail->isHTML(true);
        $mail->Body = '<p>Hi ' . htmlspecialchars($uname) . ',</p><p>Your one-time password (OTP) is: <strong>' . htmlspecialchars($otp) . '</strong></p><p>This code will expire in 5 minutes.</p>';
        $mail->AltBody = "Your OTP is: $otp (expires in 5 minutes)";
        $mail->send();
        $_SESSION['success'] = 'A new OTP has been sent to your email.';
    } catch (Throwable $e) {
        error_log('Failed to send resent OTP email (student): ' . $e->getMessage());
        $_SESSION['error'] = 'Unable to send OTP email. Please try again later.';
    }

    header('Location: ' . route_url('students/login'));
    exit;
}

// Fallback
$_SESSION['error'] = 'No pending request. Please login or register.';
header('Location: ' . route_url('students/login'));
exit;
