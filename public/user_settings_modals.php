<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/db.php';

$uid = (int)($_SESSION['auth_user_id'] ?? 0);
$username = 'User';
$email = '';
$hasErrorsAcc = !empty($_SESSION['errors_account']);
$hasAccSuccess = !empty($_SESSION['account_settings_success']);
if ($uid > 0) {
    $stmt = $conn->prepare('SELECT username, email FROM users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row) {
            $username = (string)$row['username'];
            $email = (string)$row['email'];
        }
    }
}
?>

<div id="accountSettingsModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="accountSettingsLabel">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/40" onclick="document.getElementById('accountSettingsModal').classList.add('hidden')"></div>
        <div class="relative w-full max-w-md rounded-lg bg-white shadow-lg">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 id="accountSettingsLabel" class="text-lg font-semibold">Account Settings</h3>
                <button class="rounded-md p-2 hover:bg-gray-100" aria-label="Close" onclick="document.getElementById('accountSettingsModal').classList.add('hidden')">✕</button>
            </div>
            <div class="p-4">
                <?php $oldAcc = $_SESSION['old_account'] ?? [];
                $errorsAcc = $_SESSION['errors_account'] ?? [];
                unset($_SESSION['old_account'], $_SESSION['errors_account'], $_SESSION['account_settings_success']); ?>
                <?php $page_success = $_SESSION['success'] ?? null;
                unset($_SESSION['success']); ?>
                <form id="accountForm" method="POST" action="<?= route_url('admin/account/settings') ?>">
                    <?= csrf_input(); ?>
                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? route_url('admin/menu-1'), ENT_QUOTES, 'UTF-8'); ?>">
                    <label class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="username" name="username" required minlength="3" value="<?php echo htmlspecialchars($oldAcc['username'] ?? $username, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 <?php echo isset($errorsAcc['username']) ? 'ring-2 ring-red-500' : ''; ?>">

                    <label class="mt-3 block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($oldAcc['email'] ?? $email, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 <?php echo isset($errorsAcc['email']) ? 'ring-2 ring-red-500' : ''; ?>">

                    <label class="mt-3 block text-sm font-medium text-gray-700">New Password</label>
                    <input type="password" id="password" name="password" minlength="6" placeholder="Leave blank to keep current" autocomplete="new-password" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 <?php echo isset($errorsAcc['password']) ? 'ring-2 ring-red-500' : ''; ?>">

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="rounded-md border border-gray-300 px-3 py-2 transition-colors hover:bg-gray-100" onclick="document.getElementById('accountSettingsModal').classList.add('hidden')">Cancel</button>
                        <button type="submit" class="rounded-md bg-indigo-600 text-white px-3 py-2 transition-colors hover:bg-indigo-700">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<script>
    function openAccountSettingsModal() {
        document.getElementById('accountSettingsModal').classList.remove('hidden');
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('accountSettingsModal')?.classList.add('hidden');
        }
    });

    const CSRF = '<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>';




    (function() {
        var openAcc = <?php echo ($hasErrorsAcc || $hasAccSuccess) ? 'true' : 'false'; ?>;
        if (openAcc) {
            openAccountSettingsModal();
        }
        var errs = <?php echo json_encode($errorsAcc); ?>;
        var succ = <?php echo json_encode($page_success); ?>;
        (function ensureToast() {
            var c = document.getElementById('toast-container');
            if (!c) {
                c = document.createElement('div');
                c.id = 'toast-container';
                c.className = 'toast-container';
                document.body.appendChild(c);
            }
            if (!window.showToast) {
                window.showToast = function(type, msg, d) {
                    var t = document.createElement('div');
                    t.className = 'toast ' + (type || 'info');
                    t.setAttribute('role', 'alert');
                    var icon = '';
                    if (type === 'success') icon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>';
                    else if (type === 'error') icon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v6"/><circle cx="12" cy="16" r="1"/></svg>';
                    else if (type === 'warning') icon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>';
                    t.innerHTML = icon + '<div class="message"></div>';
                    t.querySelector('.message').textContent = String(msg || '');
                    c.appendChild(t);
                    void t.offsetHeight;
                    t.classList.add('show');
                    setTimeout(function() {
                        t.classList.remove('show');
                        setTimeout(function() {
                            t.remove();
                        }, 250);
                    }, d || 5000);
                };
            }
        })();
        if (succ) window.showToast('success', succ);
        if (errs && typeof errs === 'object') {
            Object.values(errs).forEach(function(msg) {
                if (msg) window.showToast('error', msg);
            });
        }
    })();
</script>
