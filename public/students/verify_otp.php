<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../config/env.php';

if (!isset($_SESSION['pending_student_user_id']) && !isset($_SESSION['pending_registration_id'])) {
    header('Location: ' . route_url('students/login'));
    exit;
}

$feedback = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $feedback = 'Invalid request. Please refresh and try again.';
    } else {
        $otpInput = isset($_POST['otp']) ? preg_replace('/\D+/', '', trim((string)$_POST['otp'])) : '';
        if ($otpInput === '') {
            $feedback = 'Please enter the OTP sent to your email.';
        } else {
            // Check which OTP flow is active: student login or registration
            if (isset($_SESSION['pending_student_user_id'])) {
                // Login flow for students
                $userId = (int) $_SESSION['pending_student_user_id'];
                $pendingUsername = (string) ($_SESSION['pending_student_username'] ?? '');
                $stmt = $conn->prepare('SELECT id, otp, expires_at, is_used, attempt_count FROM login_otp WHERE user_id = ? AND is_used = 0 ORDER BY id DESC LIMIT 1');
                $stmt->bind_param('i', $userId);
                $stmt->execute();
                $res = $stmt->get_result();
                $otpRow = $res ? $res->fetch_assoc() : null;
                $stmt->close();

                if (!$otpRow) {
                    $feedback = 'No active OTP found. Please login again to request a new code.';
                } else {
                    if ((int)$otpRow['attempt_count'] >= 5) {
                        audit_login_event($conn, $userId, $pendingUsername, 'otp_attempt_limit', null, $ipAddr, $ua);
                        $feedback = 'Too many incorrect attempts. Please login again to request a new code.';
                    } else {
                        $expiresTs = strtotime($otpRow['expires_at']);
                        if ($expiresTs !== false && $expiresTs < time()) {
                            audit_login_event($conn, $userId, $pendingUsername, 'otp_expired', null, $ipAddr, $ua);
                            $feedback = 'OTP has expired. Please login again to request a new code.';
                        } elseif (!password_verify($otpInput, $otpRow['otp'])) {
                            $attempts = (int) $otpRow['attempt_count'] + 1;
                            $upd = $conn->prepare('UPDATE login_otp SET attempt_count = ? WHERE id = ?');
                            $upd->bind_param('ii', $attempts, $otpRow['id']);
                            $upd->execute();
                            $upd->close();
                            audit_login_event($conn, $userId, $pendingUsername, 'otp_invalid', null, $ipAddr, $ua);
                            $feedback = 'Incorrect OTP. Please try again.';
                        } else {
                            // mark used and login
                            $upd = $conn->prepare('UPDATE login_otp SET is_used = 1 WHERE id = ?');
                            $upd->bind_param('i', $otpRow['id']);
                            $upd->execute();
                            $upd->close();

                            $rs = $conn->prepare('SELECT role, is_active FROM users WHERE id = ? LIMIT 1');
                            if ($rs) {
                                $rs->bind_param('i', $userId);
                                $rs->execute();
                                $rr = $rs->get_result();
                                $rowr = $rr ? $rr->fetch_assoc() : null;
                                $roleNow = normalize_role((string)($rowr['role'] ?? 'student'));
                                $activeFlag = isset($rowr['is_active']) ? (int)$rowr['is_active'] : 1;
                                if ($activeFlag !== 1 || $roleNow !== 'student') {
                                    audit_login_event($conn, $userId, $pendingUsername, 'role_denied_post_otp', $roleNow, $ipAddr, $ua);
                                    $_SESSION['error'] = 'Invalid username or password.';
                                    $rs->close();
                                    unset($_SESSION['pending_student_user_id'], $_SESSION['pending_student_username'], $_SESSION['pending_student_email']);
                                    header('Location: ' . route_url('students/login'));
                                    exit;
                                }
                                // Establish authenticated student session
                                $_SESSION['auth_user_id'] = $userId;
                                $_SESSION['auth_role'] = 'student';
                                session_regenerate_id(true);
                                refresh_session_cookie_role_ttl();
                                $rs->close();
                            }
                            unset($_SESSION['pending_student_user_id'], $_SESSION['pending_student_username'], $_SESSION['pending_student_email']);
                            if (!empty($_SESSION['pending_remember_me'])) {
                                remember_me_set($userId);
                                unset($_SESSION['pending_remember_me']);
                            }

                            $target = student_profile_completed($conn, $userId) ? route_url('students/home') : route_url('students/profile-setup');
                            audit_login_event($conn, $userId, $pendingUsername, 'otp_verified', $_SESSION['auth_role'] ?? null, $ipAddr, $ua);
                            $_SESSION['success'] = 'Login successful!';
                            session_write_close();
                            header('Location: ' . $target);
                            exit;
                        }
                    }
                }
            } elseif (isset($_SESSION['pending_registration_id'])) {
                $regId = (int) $_SESSION['pending_registration_id'];
                $pendingRegUsername = (string) ($_SESSION['pending_registration_username'] ?? '');
                $conn->query("CREATE TABLE IF NOT EXISTS pending_registrations (id INT UNSIGNED NOT NULL AUTO_INCREMENT, username VARCHAR(50) NOT NULL, email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, otp VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, is_used TINYINT(1) NOT NULL DEFAULT 0, attempt_count INT UNSIGNED NOT NULL DEFAULT 0, ip_address VARCHAR(45) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uniq_pending_email (email), UNIQUE KEY uniq_pending_username (username)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

                $s = $conn->prepare('SELECT id, username, email, password_hash, otp, expires_at, is_used, attempt_count FROM pending_registrations WHERE id = ? LIMIT 1');
                $s->bind_param('i', $regId);
                $s->execute();
                $sr = $s->get_result();
                $reg = $sr ? $sr->fetch_assoc() : null;
                $s->close();
                if (!$reg) {
                    $feedback = 'No pending registration found. Please sign up again.';
                } else {
                    if ((int)$reg['attempt_count'] >= 5) {
                        audit_login_event($conn, null, $pendingRegUsername, 'reg_otp_attempt_limit', null, $ipAddr, $ua);
                        $feedback = 'Too many incorrect attempts. Please sign up again to request a new code.';
                    } else {
                        $expiresTs = strtotime($reg['expires_at']);
                        if ($expiresTs !== false && $expiresTs < time()) {
                            audit_login_event($conn, null, $pendingRegUsername, 'reg_otp_expired', null, $ipAddr, $ua);
                            $feedback = 'OTP has expired. Please sign up again to request a new code.';
                        } elseif (!password_verify($otpInput, $reg['otp'])) {
                            $attempts = (int)$reg['attempt_count'] + 1;
                            $upd = $conn->prepare('UPDATE pending_registrations SET attempt_count = ? WHERE id = ?');
                            $upd->bind_param('ii', $attempts, $regId);
                            $upd->execute();
                            $upd->close();
                            audit_login_event($conn, null, $pendingRegUsername, 'reg_otp_invalid', null, $ipAddr, $ua);
                            $feedback = 'Incorrect OTP. Please try again.';
                        } else {
                            $username = (string)$reg['username'];
                            $email = (string)$reg['email'];
                            $pwdHash = (string)$reg['password_hash'];
                            $dupU = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
                            $dupU->bind_param('s', $username);
                            $dupU->execute();
                            $dupU->store_result();
                            if ($dupU->num_rows > 0) {
                                $dupU->close();
                                $feedback = 'Username is already taken. Please choose a different one.';
                            } else {
                                $dupU->close();
                                $dupE = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                                $dupE->bind_param('s', $email);
                                $dupE->execute();
                                $dupE->store_result();
                                if ($dupE->num_rows > 0) {
                                    $dupE->close();
                                    $feedback = 'Email is already registered. Please use a different email.';
                                } else {
                                    $dupE->close();
                                    $ok = true;
                                    $conn->begin_transaction();
                                    $insU = $conn->prepare('INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)');
                                    if (!$insU) {
                                        $ok = false;
                                    }
                                    if ($ok) {
                                        $roleNow = 'student';
                                        $insU->bind_param('ssss', $username, $email, $pwdHash, $roleNow);
                                        if (!$insU->execute()) {
                                            $ok = false;
                                        }
                                        $newUserId = (int)$insU->insert_id;
                                        $insU->close();
                                        if ($newUserId <= 0) {
                                            $ok = false;
                                        }
                                        $mk = $conn->prepare('UPDATE pending_registrations SET is_used = 1 WHERE id = ?');
                                        if ($mk) {
                                            $mk->bind_param('i', $regId);
                                            if (!$mk->execute()) {
                                                $ok = false;
                                            }
                                            $mk->close();
                                        }
                                        if ($ok) {
                                            $conn->commit();
                                            $_SESSION['auth_user_id'] = $newUserId;
                                            $_SESSION['auth_role'] = 'student';
                                            session_regenerate_id(true);
                                            refresh_session_cookie_role_ttl();
                                            unset($_SESSION['pending_registration_id'], $_SESSION['pending_registration_username'], $_SESSION['pending_registration_email']);
                                            audit_login_event($conn, $newUserId, $username, 'reg_otp_verified', 'student', $ipAddr, $ua);
                                            $_SESSION['success'] = 'Account created and logged in!';
                                            session_write_close();
                                            header('Location: ' . route_url('students/profile-setup'));
                                            exit;
                                        }
                                        $conn->rollback();
                                        $feedback = 'Server error. Please try again later.';
                                    } else {
                                        $conn->rollback();
                                        $feedback = 'Server error. Please try again later.';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
// For student login flow, keep OTP inline on the login page
if (isset($_SESSION['pending_student_user_id'])) {
    if ($feedback !== '') {
        $_SESSION['error'] = $feedback;
    }
    header('Location: ' . route_url('students/login'));
    exit;
}

if (isset($_SESSION['pending_registration_id'])) {
    if ($feedback !== '') {
        $_SESSION['error'] = $feedback;
    }
    header('Location: ' . route_url('students/login'));
    exit;
}
