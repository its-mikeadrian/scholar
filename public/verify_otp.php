<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/db.php';

require_once __DIR__ . '/../config/env.php';

if (!isset($_SESSION['pending_user_id'])) {
    header('Location: ' . route_url('admin'));
    exit;
}

$userId = (int) $_SESSION['pending_user_id'];
$username = isset($_SESSION['pending_username']) ? $_SESSION['pending_username'] : '';
$email = isset($_SESSION['pending_email']) ? $_SESSION['pending_email'] : '';

$feedback = '';

// Compute resend cooldown remaining seconds (server-side) using DB time to avoid TZ mismatch
$cooldownRemaining = 0;
$cooldownWindow = 60; // resend cooldown seconds
$cooldownStmt = $conn->prepare("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS elapsed FROM login_otp WHERE user_id = ? AND is_used = 0 ORDER BY id DESC LIMIT 1");
$cooldownStmt->bind_param('i', $userId);
$cooldownStmt->execute();
$cooldownRes = $cooldownStmt->get_result();
$cooldownRow = $cooldownRes ? $cooldownRes->fetch_assoc() : null;
$cooldownStmt->close();
if ($cooldownRow && isset($cooldownRow['elapsed'])) {
    $elapsed = (int) $cooldownRow['elapsed'];
    if ($elapsed < 0) {
        $elapsed = 0;
    }
    $cooldownRemaining = max(0, $cooldownWindow - $elapsed);
}



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
    // CSRF validation
    if (!csrf_validate()) {
        $feedback = 'Invalid request. Please refresh and try again.';
    } else {
        $otpInput = isset($_POST['otp']) ? preg_replace('/\D+/', '', trim($_POST['otp'])) : '';
        if ($otpInput === '') {
            $feedback = 'Please enter the OTP sent to your email.';
        } else {
            // Find matching, unused, unexpired OTP
            $sql = "SELECT id, otp, expires_at, is_used, attempt_count FROM login_otp WHERE user_id = ? AND is_used = 0 ORDER BY id DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $otpRow = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if (!$otpRow) {
                $feedback = 'No active OTP found. Please login again to request a new code.';
            } else {
                $now = time();
                $expiresTs = strtotime($otpRow['expires_at']);
                if ((int)$otpRow['attempt_count'] >= 5) {
                    audit_login_event($conn, $userId, $username, 'otp_attempt_limit', null, $ipAddr, $ua);
                    $feedback = 'Too many incorrect attempts. Please login again to request a new code.';
                } elseif ($expiresTs !== false && $expiresTs < $now) {
                    audit_login_event($conn, $userId, $username, 'otp_expired', null, $ipAddr, $ua);
                    $feedback = 'OTP has expired. Please login again to request a new code.';
                } elseif (!password_verify($otpInput, $otpRow['otp'])) {

                    $attempts = (int) $otpRow['attempt_count'] + 1;
                    $upd = $conn->prepare('UPDATE login_otp SET attempt_count = ? WHERE id = ?');
                    $upd->bind_param('ii', $attempts, $otpRow['id']);
                    $upd->execute();
                    $upd->close();
                    audit_login_event($conn, $userId, $username, 'otp_invalid', null, $ipAddr, $ua);
                    $feedback = 'Incorrect OTP. Please try again.';
                } else {

                    // Proceed to login after correct email OTP
                    $upd = $conn->prepare('UPDATE login_otp SET is_used = 1 WHERE id = ?');
                    $upd->bind_param('i', $otpRow['id']);
                    $upd->execute();
                    $upd->close();

                    // Regenerate session ID to prevent fixation, set auth and clear pending
                    session_regenerate_id(true);
                    $rs = $conn->prepare('SELECT role, is_active FROM users WHERE id = ? LIMIT 1');
                    if ($rs) {
                        $rs->bind_param('i', $userId);
                        $rs->execute();
                        $rr = $rs->get_result();
                        $rowr = $rr ? $rr->fetch_assoc() : null;
                        $roleNow = normalize_role((string)($rowr['role'] ?? 'student'));
                        $activeFlag = isset($rowr['is_active']) ? (int)$rowr['is_active'] : 1;
                        if ($activeFlag !== 1 || !in_array($roleNow, ['admin', 'superadmin'], true)) {
                            audit_login_event($conn, $userId, $username, 'role_denied_post_otp', $roleNow, $ipAddr, $ua);
                            $_SESSION['error'] = 'Invalid username or password.';
                            $rs->close();
                            unset($_SESSION['pending_user_id'], $_SESSION['pending_username'], $_SESSION['pending_email']);
                            unset($_SESSION['pending_role']);
                            if (!empty($_SESSION['pending_remember_me'])) {
                                unset($_SESSION['pending_remember_me']);
                            }
                            header('Location: ' . route_url('admin'));
                            exit;
                        }
                        $_SESSION['auth_user_id'] = $userId;
                        $_SESSION['auth_role'] = $roleNow;
                        $rs->close();
                    }
                    unset($_SESSION['pending_user_id'], $_SESSION['pending_username'], $_SESSION['pending_email']);
                    unset($_SESSION['pending_role']);
                    if (!empty($_SESSION['pending_remember_me'])) {
                        remember_me_set($userId);
                        unset($_SESSION['pending_remember_me']);
                    }

                    audit_login_event($conn, $userId, $username, 'otp_verified', $_SESSION['auth_role'] ?? null, $ipAddr, $ua);
                    $_SESSION['success'] = 'Login successful!';
                    session_write_close();
                    header('Location: ' . route_url('admin/menu-1'));
                    exit;
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('admin/style.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <title>Verify OTP</title>

</head>

<body>
    <main class="page" id="page">
        <section class="card" role="region" aria-labelledby="otp-title">
            <img src="<?= htmlspecialchars(asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Institution logo" class="brand-logo">
            <div class="brand-block">
                <div class="game-title" aria-hidden="false">
                    <span class="rp">Republic of the Philippines</span>
                    <span class="main">ISKOLAR NANG LUIS</span>
                    <span class="sub">MUNICIPALITY OF SAN LUIS, PAMPANGA</span>
                </div>
            </div>
            <h1 id="otp-title">Verify OTP</h1>

            <?php $page_error = !empty($feedback) ? $feedback : ($_SESSION['error'] ?? null);
            $page_success = $_SESSION['success'] ?? null;
            unset($_SESSION['error'], $_SESSION['success']); ?>
            <script>
                (function() {
                    if (window.showToast) {
                        var m = {
                            error: <?php echo json_encode($page_error); ?>,
                            success: <?php echo json_encode($page_success); ?>
                        };
                        if (m.error) window.showToast('error', m.error);
                        if (m.success) window.showToast('success', m.success);
                    }
                })();
            </script>

            <div class="field otp-field" id="admin-otp-field">
                <label for="admin-otp">One-Time Password</label>
                <div class="row" style="align-items:center; gap:10px;">
                    <form method="POST" id="otpForm" action="<?= route_url('admin/verify-otp') ?>" class="otp-inline" style="flex:1;">
                        <?php echo csrf_input(); ?>
                        <div class="input-wrap" style="display:flex;">
                            <input type="text" name="otp" id="admin-otp" inputmode="numeric" maxlength="6" pattern="\d{6}" required placeholder="Enter 6-digit code" aria-label="One-time password" style="width:100%;" />
                        </div>
                    </form>
                    <form method="POST" action="<?= route_url('admin/resend-otp') ?>" id="resendForm">
                        <?php echo csrf_input(); ?>
                        <button type="submit" id="admin-resend-otp" class="btn secondary" data-remaining="<?php echo (int)$cooldownRemaining; ?>" <?php echo ($cooldownRemaining > 0) ? 'disabled' : ''; ?>>Resend<?php echo ($cooldownRemaining > 0) ? ' (' . (int)$cooldownRemaining . 's)' : ''; ?></button>
                    </form>
                </div>

            </div>
            <button type="submit" class="btn" aria-busy="false" form="otpForm" style="display:block; margin:20px auto 0;">Verify</button>

            <div class="mt-3" style="text-align:center; margin-top:20px;">
                <a href="<?= route_url('admin') ?>" class="forgot">Back to Login</a>
            </div>
        </section>
    </main>

    <script src="<?= htmlspecialchars(asset_url('admin/script.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('admin-resend-otp');
            var remaining = 0;
            if (btn) {
                var d = btn.getAttribute('data-remaining');
                remaining = parseInt(d || '0', 10);
                if (isNaN(remaining) || remaining < 0) remaining = 0;
            }

            function renderBtn() {
                if (!btn) return;
                if (remaining > 0) {
                    btn.setAttribute('disabled', 'disabled');
                    btn.textContent = 'Resend (' + remaining + 's)';
                } else {
                    btn.removeAttribute('disabled');
                    btn.textContent = 'Resend';
                }
            }

            renderBtn();
            if (remaining > 0) {
                var timer = setInterval(function() {
                    remaining = remaining - 1;
                    if (remaining <= 0) {
                        remaining = 0;
                        clearInterval(timer);
                    }
                    renderBtn();
                }, 1000);
            }

            var otpInput = document.getElementById('admin-otp');


            function sanitizeOtp() {
                if (!otpInput) return;
                otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 6);
            }

            function isValidOtp(v) {
                return /^\d{6}$/.test(v);
            }
            if (otpInput) {
                otpInput.addEventListener('input', function() {
                    sanitizeOtp();
                    if (isValidOtp(otpInput.value)) {
                        // valid; no-op
                    }
                });
                otpInput.addEventListener('keydown', function(e) {
                    if (e.key && e.key.length === 1 && !/\d/.test(e.key)) e.preventDefault();
                });
            }
            var form = document.getElementById('otpForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    sanitizeOtp();
                    if (!otpInput || !isValidOtp(otpInput.value)) {
                        e.preventDefault();
                        if (window.showToast) window.showToast('error', 'Enter 6 digits');
                    }
                });
            }
        });
    </script>
</body>

</html>