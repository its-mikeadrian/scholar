<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
if (!empty($_SESSION['auth_user_id'])) {
    $r = auth_role();
    if ($r === 'admin' || $r === 'superadmin') {
        header('Location: ' . route_url('admin/menu-1'));
        exit;
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
    <title>Administrator Login</title>
</head>

<body>
    <main class="page" id="page">
        <section class="card" role="region" aria-labelledby="admin-title">
            <img src="<?= htmlspecialchars(asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Institution logo" class="brand-logo">
            <div class="brand-block">
                <div class="game-title" aria-hidden="false">
                    <span class="rp">Republic of the Philippines</span>
                    <span class="main">ISKOLAR NANG LUIS</span>
                    <span class="sub">MUNICIPALITY OF SAN LUIS, PAMPANGA</span>
                </div>
            </div>
            <h1 id="admin-title">Administrator Login</h1>

            <?php $page_error = $_SESSION['error'] ?? null; $page_success = $_SESSION['success'] ?? null; $page_info = $_SESSION['info'] ?? null; unset($_SESSION['error'], $_SESSION['success'], $_SESSION['info']); ?>
            <script>
                window.__messages = {
                    error: <?php echo json_encode($page_error); ?>,
                    success: <?php echo json_encode($page_success); ?>,
                    info: <?php echo json_encode($page_info); ?>
                };
            </script>

            <form id="admin-login-form" method="POST" action="<?= route_url('admin/process-login') ?>" novalidate autocomplete="on">
                <?= csrf_input(); ?>

                <div class="field">
                    <label for="admin-id">Username</label>
                    <input type="text" id="admin-id" name="username" required aria-required="true" autocomplete="username" inputmode="email" placeholder="Enter username" />
                    
                </div>

                <div class="field password-field">
                    <label for="admin-password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="admin-password" name="password" required aria-required="true" autocomplete="current-password" placeholder="Enter password" />
                        <button type="button" class="toggle" id="toggle-pass" aria-label="Show password" aria-pressed="false">
                            <img src="<?= htmlspecialchars(asset_url('admin/assets/eye-off.svg'), ENT_QUOTES, 'UTF-8'); ?>" alt="Toggle password visibility" width="22" height="22" />
                        </button>
                    </div>
                    
                </div>

                <div class="row">
                    <label class="checkbox">
                        <input type="checkbox" id="remember" name="remember_me" value="1" />
                        <span>Remember me</span>
                    </label>
                    <a href="#" id="forgot-link" class="forgot">Forgot password?</a>
                </div>

                <button type="submit" id="login-btn" class="btn" aria-busy="false">Login</button>
            </form>
        </section>

        <div class="modal-overlay" id="admin-forgot-overlay" aria-hidden="true">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="forgot-title">
                <button type="button" class="close-btn" id="close-reset" aria-label="Close">&times;</button>
                <img src="<?= htmlspecialchars(asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Institution logo" class="brand-logo">
                <div class="game-title" aria-hidden="false">
                    <span class="rp">Republic of the Philippines</span>
                    <span class="main">ISKOLAR NANG LUIS</span>
                    <span class="sub">MUNICIPALITY OF SAN LUIS, PAMPANGA</span>
                </div>
                <h2 id="forgot-title">Reset Administrator Password</h2>
                <p>Enter your email address to receive a reset link.</p>
                <form id="admin-forgot-form" method="POST" action="<?= route_url('admin/forgot-password') ?>" novalidate autocomplete="off">
                    <?= csrf_input(); ?>
                    <input type="email" id="forgot-email" name="email" placeholder="Email Address" inputmode="email" autocomplete="email" aria-label="Email address for password reset" required>
                    <div class="modal-actions">
                        <button type="submit" id="send-reset">Send Reset Link</button>
                        <button type="button" class="secondary" id="cancel-reset">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="<?= htmlspecialchars(asset_url('admin/script.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>

</html>
