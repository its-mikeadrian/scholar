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
    header('Location: ' . route_url('students/home'));
    exit;
}
// Allow exiting OTP mode when user clicks Back
if (isset($_GET['cancel'])) {
    unset(
        $_SESSION['pending_student_user_id'],
        $_SESSION['pending_student_username'],
        $_SESSION['pending_student_email'],
        $_SESSION['pending_remember_me']
    );
}
if (isset($_GET['cancel_reg'])) {
    unset(
        $_SESSION['pending_registration_id'],
        $_SESSION['pending_registration_username'],
        $_SESSION['pending_registration_email']
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
        header('Location: ' . route_url('students/login'));
        exit;
    }
    if (isset($_POST['signup-username']) || isset($_POST['signup-email']) || isset($_POST['signup-password']) || isset($_POST['signup-confirm'])) {
        $username = isset($_POST['signup-username']) ? (string)trim($_POST['signup-username']) : '';
        $email = isset($_POST['signup-email']) ? (string)trim($_POST['signup-email']) : '';
        $password = isset($_POST['signup-password']) ? (string)$_POST['signup-password'] : '';
        $confirm = isset($_POST['signup-confirm']) ? (string)$_POST['signup-confirm'] : '';
        $_SESSION['old'] = [
            'signup-username' => $username,
            'email' => $email,
        ];
        $errors = [];
        if ($username === '' || strlen($username) < 3) {
            $errors['signup-username'] = 'Username must be at least 3 characters.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['signup-email'] = 'Please provide a valid email address.';
        }
        if ($password === '' || strlen($password) < 6) {
            $errors['signup-password'] = 'Password must be at least 6 characters.';
        }
        if ($confirm === '' || $confirm !== $password) {
            $errors['signup-confirm'] = 'Passwords do not match.';
        }
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['error'] = 'Please correct the highlighted fields.';
            header('Location: ' . route_url('students/login'));
            exit;
        }

        $dupU = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $dupU->bind_param('s', $username);
        $dupU->execute();
        $dupU->store_result();
        if ($dupU->num_rows > 0) {
            $dupU->close();
            $_SESSION['error'] = 'Username is already taken.';
            header('Location: ' . route_url('students/login'));
            exit;
        }
        $dupU->close();

        $dupE = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $dupE->bind_param('s', $email);
        $dupE->execute();
        $dupE->store_result();
        if ($dupE->num_rows > 0) {
            $dupE->close();
            $_SESSION['error'] = 'Email is already registered.';
            header('Location: ' . route_url('students/login'));
            exit;
        }
        $dupE->close();

        $conn->query("CREATE TABLE IF NOT EXISTS pending_registrations (id INT UNSIGNED NOT NULL AUTO_INCREMENT, username VARCHAR(50) NOT NULL, email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, otp VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, is_used TINYINT(1) NOT NULL DEFAULT 0, attempt_count INT UNSIGNED NOT NULL DEFAULT 0, ip_address VARCHAR(45) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (id), UNIQUE KEY uniq_pending_email (email), UNIQUE KEY uniq_pending_username (username)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);
        $pwdHash = password_hash($password, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + 5 * 60);
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

        $sel = $conn->prepare('SELECT id FROM pending_registrations WHERE email = ? OR username = ? LIMIT 1');
        $sel->bind_param('ss', $email, $username);
        $sel->execute();
        $resSel = $sel->get_result();
        $existing = $resSel ? $resSel->fetch_assoc() : null;
        $sel->close();

        if ($existing) {
            $upd = $conn->prepare('UPDATE pending_registrations SET password_hash = ?, otp = ?, expires_at = ?, is_used = 0, attempt_count = 0, ip_address = ? WHERE id = ?');
            $upd->bind_param('ssssi', $pwdHash, $otpHash, $expiresAt, $ip, $existing['id']);
            $upd->execute();
            $upd->close();
            $regId = (int)$existing['id'];
        } else {
            $ins = $conn->prepare('INSERT INTO pending_registrations (username, email, password_hash, otp, expires_at, is_used, attempt_count, ip_address) VALUES (?, ?, ?, ?, ?, 0, 0, ?)');
            if (!$ins) {
                $_SESSION['error'] = 'Server error. Please try again later.';
                header('Location: ' . route_url('students/login'));
                exit;
            }
            $ins->bind_param('ssssss', $username, $email, $pwdHash, $otpHash, $expiresAt, $ip);
            $ins->execute();
            $regId = (int)$ins->insert_id;
            $ins->close();
        }

        try {
            $mail = new PHPMailer(true);
            configureMailer($mail);
            applyFromAddress($mail, 'Register');
            $mail->addAddress($email, $username);
            $mail->Subject = 'Your Registration OTP';
            $mail->isHTML(true);
            $mail->Body = '<p>Hi ' . htmlspecialchars($username) . ',</p>' .
                '<p>Your one-time password (OTP) is: <strong>' . htmlspecialchars($otp) . '</strong></p>' .
                '<p>This code will expire in 5 minutes.</p>' .
                '<p>If you did not initiate this registration, you can ignore this message.</p>';
            $mail->AltBody = "Your OTP is: $otp (expires in 5 minutes)";
            $mail->send();
        } catch (Throwable $e) {
            error_log('Failed to send registration OTP email: ' . $e->getMessage());
            $_SESSION['error'] = 'Unable to send OTP email. Please try again later.';
            header('Location: ' . route_url('students/login'));
            exit;
        }

        $_SESSION['pending_registration_id'] = $regId;
        $_SESSION['pending_registration_username'] = $username;
        $_SESSION['pending_registration_email'] = $email;
        $_SESSION['success'] = 'OTP sent to your email. Please check and verify.';
        header('Location: ' . route_url('students/login'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars(asset_url('assets/bootstrap/bootstrap.min.css'), ENT_QUOTES); ?>">
    <link rel="stylesheet" href="style.css">
    <title>Modern Login Page | AsmrProg</title>
</head>

<body>

    <div class="container" id="container">
        <?php $old = $_SESSION['old'] ?? [];
        $errors = $_SESSION['errors'] ?? [];
        $page_error = $_SESSION['error'] ?? null;
        $page_success = $_SESSION['success'] ?? null;
        unset($_SESSION['old'], $_SESSION['errors'], $_SESSION['error'], $_SESSION['success']); ?>
        <script>
            window.__messages = {
                error: <?php echo json_encode($page_error); ?>,
                success: <?php echo json_encode($page_success); ?>,
                errors: <?php echo json_encode($errors); ?>
            };
        </script>
        <div class="form-container sign-up">
            <?php $regOtpMode = !empty($_SESSION['pending_registration_id']); ?>
            <form method="POST" action="<?php echo htmlspecialchars($regOtpMode ? route_url('students/verify-otp') : route_url('students/login'), ENT_QUOTES); ?>" autocomplete="off">
                <img src="../images/logo.png" alt="Logo" class="brand-logo">
                <div class="game-title">
                    <span class="rp">Republic of the Philippines</span>
                    <span class="main">ISKOLAR NANG LUIS</span>
                    <span class="sub">MUNICIPALITY OF SAN LUIS, PAMPANGA</span>
                </div>
                <h1><?php echo $regOtpMode ? 'Verify OTP' : 'Create Account'; ?></h1>
                <span id="signup-subtext"><?php echo $regOtpMode ? 'We have sent you a code' : 'Register with your details'; ?></span>
                <input type="text" placeholder="Username" id="signup-username" name="signup-username" <?php echo $regOtpMode ? 'disabled' : 'required'; ?> value="<?php echo htmlspecialchars($old['signup-username'] ?? '', ENT_QUOTES); ?>" style="<?php echo $regOtpMode ? 'display:none' : ''; ?>">
                <input type="email" placeholder="Email" id="signup-email" name="signup-email" <?php echo $regOtpMode ? 'disabled' : 'required'; ?> value="<?php echo htmlspecialchars($old['email'] ?? '', ENT_QUOTES); ?>" style="<?php echo $regOtpMode ? 'display:none' : ''; ?>">
                <input type="password" placeholder="Password" id="signup-password" name="signup-password" <?php echo $regOtpMode ? 'disabled' : 'required'; ?> style="<?php echo $regOtpMode ? 'display:none' : ''; ?>">
                <input type="password" placeholder="Confirm Password" id="signup-confirm" name="signup-confirm" <?php echo $regOtpMode ? 'disabled' : 'required'; ?> style="<?php echo $regOtpMode ? 'display:none' : ''; ?>">
                <?php echo csrf_input(); ?>
                <div id="signup-otp-row" style="display: <?php echo $regOtpMode ? 'flex' : 'none'; ?>; gap:10px; align-items:center; margin:10px 0;">
                    <input type="text" id="signup-otp" name="otp" placeholder="Enter 6-digit code" inputmode="numeric" maxlength="6" pattern="\d{6}" <?php echo $regOtpMode ? 'required' : ''; ?> style="flex:1;">
                    <button type="button" id="signup-resend-otp" class="secondary">Resend OTP</button>
                </div>
                <button type="submit"><?php echo $regOtpMode ? 'Verify' : 'Sign Up'; ?></button>
                <button type="button" id="signup-otp-back" class="text-button" style="display: <?php echo $regOtpMode ? 'inline-block' : 'none'; ?>; background:none; border:none; color:#1e88e5; padding:6px 0; cursor:pointer;">Back</button>
                <a href="#" id="login-inline" class="switch-link">Go to Sign In</a>
            </form>
        </div>
        <div class="form-container sign-in">
            <?php $otpMode = !empty($_SESSION['pending_student_user_id']); ?>
            <form method="POST" action="<?php echo htmlspecialchars($otpMode ? route_url('students/verify-otp') : route_url('students/process-login'), ENT_QUOTES); ?>">
                <img src="../images/logo.png" alt="Logo" class="brand-logo">
                <div class="game-title">
                    <span class="rp">Republic of the Philippines</span>
                    <span class="main">ISKOLAR NANG LUIS</span>
                    <span class="sub">MUNICIPALITY OF SAN LUIS, PAMPANGA</span>
                </div>
                <h1><?php echo $otpMode ? 'Verify OTP' : 'Sign In'; ?></h1>

                <span id="signin-subtext"><?php echo $otpMode ? 'We have sent you a code' : 'Enter your username and password'; ?></span>
                <input type="text" placeholder="Username" id="signin-username" name="signin-username" <?php echo $otpMode ? 'disabled' : 'required'; ?> value="<?php echo htmlspecialchars($old['signin-username'] ?? '', ENT_QUOTES); ?>" style="<?php echo $otpMode ? 'display:none' : ''; ?>">
                <input type="password" placeholder="Password" id="signin-password" name="signin-password" <?php echo $otpMode ? 'disabled' : 'required'; ?> style="<?php echo $otpMode ? 'display:none' : ''; ?>">
                <?php echo csrf_input(); ?>
                <a href="#" id="forgot-link" style="<?php echo $otpMode ? 'display:none' : ''; ?>">Forget Your Password?</a>
                <div id="signin-otp-row" style="display: <?php echo $otpMode ? 'flex' : 'none'; ?>; gap:10px; align-items:center; margin:10px 0;">
                    <input type="text" id="signin-otp" name="otp" placeholder="Enter 6-digit code" inputmode="numeric" maxlength="6" pattern="\d{6}" <?php echo $otpMode ? 'required' : ''; ?> style="flex:1;">
                    <button type="button" id="signin-resend-otp" class="secondary">Resend OTP</button>
                </div>
                <button type="submit"><?php echo $otpMode ? 'Verify' : 'Log In'; ?></button>
                <button type="button" id="otp-back" class="text-button" style="display: <?php echo $otpMode ? 'inline-block' : 'none'; ?>; background:none; border:none; color:#1e88e5; padding:6px 0; cursor:pointer;">Back</button>
                <a href="#" id="register-inline" class="switch-link">Create new account</a>
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome back!</h1>
                    <p>Please click Sign In to access your account.</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Welcome back!</h1>
                    <p>Click Sign Up to create your account.</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="forgot-overlay" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="forgot-title">
            <button type="button" class="close-btn" id="close-reset" aria-label="Close">&times;</button>


            <h2 id="forgot-title">Reset Password</h2>
            <p>Enter your email address to receive a reset link.</p>
            <input type="email" id="forgot-email" placeholder="Email Address" inputmode="email">
            <div class="modal-actions">
                <button type="button" id="send-reset">Send Reset Link</button>
                <button type="button" class="secondary" id="cancel-reset">Cancel</button>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        (function() {
            const signInForm = document.querySelector('.form-container.sign-in form');
            const heading = signInForm.querySelector('h1');
            const subtext = signInForm.querySelector('#signin-subtext');
            const username = signInForm.querySelector('#signin-username');
            const password = signInForm.querySelector('#signin-password');
            const forgotLink = signInForm.querySelector('#forgot-link');
            const otpRow = signInForm.querySelector('#signin-otp-row');
            const otpInput = signInForm.querySelector('#signin-otp');
            const resendBtn = signInForm.querySelector('#signin-resend-otp');
            const signUpForm = document.querySelector('.form-container.sign-up form');
            const signUpHeading = signUpForm.querySelector('h1');
            const signUpSubtext = signUpForm.querySelector('#signup-subtext');
            const signUpUsername = signUpForm.querySelector('#signup-username');
            const signUpEmail = signUpForm.querySelector('#signup-email');
            const signUpPassword = signUpForm.querySelector('#signup-password');
            const signUpConfirm = signUpForm.querySelector('#signup-confirm');
            const signUpOtpRow = signUpForm.querySelector('#signup-otp-row');
            const signUpOtpInput = document.querySelector('#signup-otp');
            const signUpResendBtn = document.querySelector('#signup-resend-otp');
            const signUpSubmitBtn = signUpForm.querySelector('button[type="submit"]');
            const signUpBackBtn = document.querySelector('#signup-otp-back');
            let otpMode = <?php echo $otpMode ? 'true' : 'false'; ?>;
            let signupOtpMode = <?php echo $regOtpMode ? 'true' : 'false'; ?>;

            function enterOtpMode() {
                otpMode = true;
                heading.textContent = 'Verify OTP';
                subtext.textContent = 'We have sent you a code';
                username.style.display = 'none';
                password.style.display = 'none';
                forgotLink.style.display = 'none';
                otpRow.style.display = 'flex';
                otpInput.focus();
            }

            function isValidOtp(value) {
                return /^\d{6}$/.test(value);
            }

            function sanitizeOtp() {
                otpInput.value = otpInput.value.replace(/\D/g, '').slice(0, 6);
            }
            if (otpInput) {
                otpInput.addEventListener('input', function() {
                    sanitizeOtp();
                    if (otpInput.value.length === 6 && isValidOtp(otpInput.value)) {
                        otpInput.setCustomValidity('');
                    }
                });
                otpInput.addEventListener('keydown', function(e) {
                    if (e.key && e.key.length === 1 && !/\d/.test(e.key)) e.preventDefault();
                });
            }
            if (signUpOtpInput) {
                signUpOtpInput.addEventListener('input', function() {
                    signUpOtpInput.value = signUpOtpInput.value.replace(/\D/g, '').slice(0, 6);
                    if (signUpOtpInput.value.length === 6 && isValidOtp(signUpOtpInput.value)) {
                        signUpOtpInput.setCustomValidity('');
                    }
                });
                signUpOtpInput.addEventListener('keydown', function(e) {
                    if (e.key && e.key.length === 1 && !/\d/.test(e.key)) e.preventDefault();
                });
            }
            if (resendBtn) {
                resendBtn.addEventListener('click', function() {
                    if (resendBtn.disabled) return;
                    resendBtn.disabled = true;
                    const originalText = resendBtn.textContent;
                    resendBtn.textContent = 'Sent';
                    const csrfInput = signInForm.querySelector('input[name="csrf_token"]');
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = '<?php echo htmlspecialchars(route_url('students/resend-otp'), ENT_QUOTES); ?>';
                    const t = document.createElement('input');
                    t.type = 'hidden';
                    t.name = 'csrf_token';
                    t.value = csrfInput ? csrfInput.value : '';
                    f.appendChild(t);
                    document.body.appendChild(f);
                    f.submit();
                    setTimeout(function() {
                        resendBtn.disabled = false;
                        resendBtn.textContent = originalText;
                    }, 30000);
                });
            }

            function enterSignUpOtpMode() {
                signupOtpMode = true;
                signUpHeading.textContent = 'Verify OTP';
                signUpSubtext.textContent = 'We have sent you a code';
                [signUpUsername, signUpEmail, signUpPassword, signUpConfirm].forEach(function(el) {
                    if (!el) return;
                    el.style.display = 'none';
                    el.disabled = true;
                    el.required = false;
                });
                if (signUpOtpRow) signUpOtpRow.style.display = 'flex';
                if (signUpOtpInput) {
                    signUpOtpInput.required = true;
                    signUpOtpInput.focus();
                }
                if (signUpSubmitBtn) signUpSubmitBtn.textContent = 'Verify';
                if (signUpBackBtn) signUpBackBtn.style.display = 'inline-block';
            }

            function exitSignUpOtpMode() {
                signupOtpMode = false;
                signUpHeading.textContent = 'Create Account';
                signUpSubtext.textContent = 'Register with your details';
                [signUpUsername, signUpEmail, signUpPassword, signUpConfirm].forEach(function(el) {
                    if (!el) return;
                    el.style.display = '';
                    el.disabled = false;
                    el.required = true;
                });
                if (signUpOtpRow) signUpOtpRow.style.display = 'none';
                if (signUpOtpInput) signUpOtpInput.required = false;
                if (signUpSubmitBtn) signUpSubmitBtn.textContent = 'Sign Up';
                if (signUpBackBtn) signUpBackBtn.style.display = 'none';
            }
            if (signUpForm) {
                signUpForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!signupOtpMode) {
                        var u = signUpUsername ? signUpUsername.value.trim() : '';
                        var em = signUpEmail ? signUpEmail.value.trim() : '';
                        var p = signUpPassword ? signUpPassword.value : '';
                        var c = signUpConfirm ? signUpConfirm.value : '';
                        if (!u || !em || !p || !c || p !== c) {
                            if (signUpUsername && !u) signUpUsername.reportValidity();
                            if (signUpEmail && !em) signUpEmail.reportValidity();
                            if (signUpPassword && !p) signUpPassword.reportValidity();
                            if (signUpConfirm && (!c || p !== c)) signUpConfirm.setCustomValidity('Passwords do not match');
                            if (signUpConfirm) signUpConfirm.reportValidity();
                            return;
                        }
                        if (signUpConfirm) signUpConfirm.setCustomValidity('');
                        // Submit the form so the server creates a pending registration and sends the OTP email.
                        // After the server redirects back the page will show the OTP verification UI.
                        signUpForm.submit();
                        return;
                    }
                    var v = signUpOtpInput ? signUpOtpInput.value.trim() : '';
                    if (!isValidOtp(v)) {
                        if (signUpOtpInput) {
                            signUpOtpInput.setCustomValidity('Enter 6 digits');
                            signUpOtpInput.reportValidity();
                        }
                        return;
                    }
                    if (signUpOtpInput) signUpOtpInput.setCustomValidity('');
                    // Submit signup OTP to server for verification
                    signUpForm.submit();
                });
            }
            if (signUpResendBtn) {
                signUpResendBtn.addEventListener('click', function() {
                    if (signUpResendBtn.disabled) return;
                    signUpResendBtn.disabled = true;
                    var originalText = signUpResendBtn.textContent;
                    signUpResendBtn.textContent = 'Sent';
                    // Post to server to resend registration OTP
                    const csrfInput = signUpForm.querySelector('input[name="csrf_token"]');
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = '<?php echo htmlspecialchars(route_url('students/resend-otp'), ENT_QUOTES); ?>';
                    const t = document.createElement('input');
                    t.type = 'hidden';
                    t.name = 'csrf_token';
                    t.value = csrfInput ? csrfInput.value : '';
                    f.appendChild(t);
                    document.body.appendChild(f);
                    f.submit();
                    setTimeout(function() {
                        signUpResendBtn.disabled = false;
                        signUpResendBtn.textContent = originalText;
                    }, 30000);
                });
            }
            // If the server rendered the page in registration OTP mode, ensure JS state/UI matches
            if (signupOtpMode) {
                enterSignUpOtpMode();
            }
            const backBtn = signInForm.querySelector('#otp-back');
            if (backBtn) {
                backBtn.addEventListener('click', function() {
                    window.location.href = '<?php echo htmlspecialchars(route_url('students/login'), ENT_QUOTES); ?>?cancel=1';
                });
            }
            const regBackBtn = document.querySelector('#signup-otp-back');
            if (regBackBtn) {
                regBackBtn.addEventListener('click', function() {
                    // Ask server to cancel pending registration so user can start over
                    window.location.href = '<?php echo htmlspecialchars(route_url('students/login'), ENT_QUOTES); ?>?cancel_reg=1';
                });
            }
            // Let forms submit to backend endpoints. OTP is handled server-side.
        })();
    </script>
</body>

</html>
