<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/remember_me.php';

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

function is_hex_of_len(string $value, int $len): bool
{
    if (strlen($value) !== $len) {
        return false;
    }
    return (bool) preg_match('/\A[0-9a-f]+\z/i', $value);
}

$ipAddr = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null;
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : null;

try {
    $pdo = get_db_connection();
    ensure_password_reset_tokens_table($pdo);
} catch (Throwable $e) {
    $_SESSION['error'] = 'Server error. Please try again later.';
    header('Location: ' . route_url('admin'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
        header('Location: ' . route_url('admin'));
        exit;
    }

    $selector = isset($_POST['selector']) ? trim((string)$_POST['selector']) : '';
    $token = isset($_POST['token']) ? trim((string)$_POST['token']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $confirm = isset($_POST['password_confirm']) ? (string)$_POST['password_confirm'] : '';

    if (!is_hex_of_len($selector, 16) || !is_hex_of_len($token, 64)) {
        $_SESSION['error'] = 'Invalid or expired reset link.';
        header('Location: ' . route_url('admin'));
        exit;
    }

    if ($password === '' || strlen($password) < 12) {
        $_SESSION['error'] = 'Password must be at least 12 characters.';
        header('Location: ' . route_url('admin/reset-password') . '?selector=' . rawurlencode($selector) . '&token=' . rawurlencode($token));
        exit;
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $_SESSION['error'] = 'Password must include at least one letter and one number.';
        header('Location: ' . route_url('admin/reset-password') . '?selector=' . rawurlencode($selector) . '&token=' . rawurlencode($token));
        exit;
    }

    if (!hash_equals($password, $confirm)) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: ' . route_url('admin/reset-password') . '?selector=' . rawurlencode($selector) . '&token=' . rawurlencode($token));
        exit;
    }

    $row = null;
    try {
        $st = $pdo->prepare('SELECT id, user_id, validator_hash, expires_at, used_at FROM password_reset_tokens WHERE selector = ? LIMIT 1');
        $st->execute([$selector]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $row = null;
    }

    if (!$row || !empty($row['used_at']) || (strtotime((string)($row['expires_at'] ?? '')) ?: 0) <= time()) {
        $_SESSION['error'] = 'Invalid or expired reset link.';
        header('Location: ' . route_url('admin'));
        exit;
    }

    $calc = hash('sha256', $token);
    if (!hash_equals((string)$row['validator_hash'], $calc)) {
        $_SESSION['error'] = 'Invalid or expired reset link.';
        header('Location: ' . route_url('admin'));
        exit;
    }

    $userId = (int)$row['user_id'];
    $tokenId = (int)$row['id'];
    $username = '';
    $role = null;

    try {
        $stU = $pdo->prepare('SELECT username, role, is_active FROM users WHERE id = ? LIMIT 1');
        $stU->execute([$userId]);
        $u = $stU->fetch(PDO::FETCH_ASSOC) ?: null;
        $username = $u ? (string)($u['username'] ?? '') : '';
        $role = $u ? normalize_role((string)($u['role'] ?? 'student')) : null;
        $active = $u && array_key_exists('is_active', $u) ? ((int)$u['is_active'] === 1) : false;
        $adminRole = $role !== null && in_array($role, ['admin', 'superadmin'], true);
        if (!$active || !$adminRole) {
            $_SESSION['error'] = 'Invalid or expired reset link.';
            header('Location: ' . route_url('admin'));
            exit;
        }
    } catch (Throwable $e) {
        $_SESSION['error'] = 'Server error. Please try again later.';
        header('Location: ' . route_url('admin'));
        exit;
    }

    $newHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $userId]);
        $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([$tokenId]);
        $pdo->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
        $pdo->commit();
    } catch (Throwable $e) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Throwable $ignored) {
        }
        $_SESSION['error'] = 'Server error. Please try again later.';
        header('Location: ' . route_url('admin'));
        exit;
    }

    try {
        remember_me_clear();
    } catch (Throwable $e) {
    }

    audit_event($conn, $userId, $username !== '' ? $username : (string)$userId, $role, 'pw_reset_completed', $ipAddr, $ua);

    $_SESSION['success'] = 'Password reset successful. You can now log in.';
    header('Location: ' . route_url('admin'));
    exit;
}

$selector = isset($_GET['selector']) ? trim((string)$_GET['selector']) : '';
$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

if (!is_hex_of_len($selector, 16) || !is_hex_of_len($token, 64)) {
    $_SESSION['error'] = 'Invalid or expired reset link.';
    header('Location: ' . route_url('admin'));
    exit;
}

$row = null;
try {
    $st = $pdo->prepare('SELECT id, user_id, validator_hash, expires_at, used_at FROM password_reset_tokens WHERE selector = ? LIMIT 1');
    $st->execute([$selector]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    $row = null;
}

if (!$row || !empty($row['used_at']) || (strtotime((string)($row['expires_at'] ?? '')) ?: 0) <= time()) {
    $_SESSION['error'] = 'Invalid or expired reset link.';
    header('Location: ' . route_url('admin'));
    exit;
}

$calc = hash('sha256', $token);
if (!hash_equals((string)$row['validator_hash'], $calc)) {
    $_SESSION['error'] = 'Invalid or expired reset link.';
    header('Location: ' . route_url('admin'));
    exit;
}

$page_error = $_SESSION['error'] ?? null;
$page_success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('admin/style.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <title>Reset Administrator Password</title>
</head>
<body>
    <main class="page" id="page">
        <section class="card" role="region" aria-labelledby="reset-title">
            <img src="<?= htmlspecialchars(asset_url('images/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Institution logo" class="brand-logo">
            <div class="brand-block">
                <div class="game-title" aria-hidden="false">
                    <span class="rp">Republic of the Philippines</span>
                    <span class="main">ISKOLAR NANG LUIS</span>
                    <span class="sub">MUNICIPALITY OF SAN LUIS, PAMPANGA</span>
                </div>
            </div>
            <h1 id="reset-title">Reset Administrator Password</h1>

            <?php if ($page_error): ?>
                <div class="alert error"><?= htmlspecialchars((string)$page_error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($page_success): ?>
                <div class="alert success"><?= htmlspecialchars((string)$page_success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= route_url('admin/reset-password') ?>" novalidate autocomplete="off">
                <?= csrf_input(); ?>
                <input type="hidden" name="selector" value="<?= htmlspecialchars($selector, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="field password-field">
                    <label for="new-password">New Password</label>
                    <div class="input-wrap">
                        <input type="password" id="new-password" name="password" required aria-required="true" autocomplete="new-password" placeholder="Enter new password" />
                    </div>
                </div>

                <div class="field password-field">
                    <label for="new-password-confirm">Confirm New Password</label>
                    <div class="input-wrap">
                        <input type="password" id="new-password-confirm" name="password_confirm" required aria-required="true" autocomplete="new-password" placeholder="Re-enter new password" />
                    </div>
                </div>

                <button type="submit" class="btn">Set New Password</button>
            </form>
        </section>
    </main>
</body>
</html>

