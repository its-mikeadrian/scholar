<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url(''));
    exit;
}

$currentRole = auth_role();
enforce_auth_for_page(basename(__FILE__));

$userId = (int) $_SESSION['auth_user_id'];
$stmt = $conn->prepare('SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $me = $res ? $res->fetch_assoc() : null;
    $stmt->close();
}
$username = $me['username'] ?? 'User';
$email = $me['email'] ?? '';
$role = $me['role'] ?? $currentRole;

$rolesList = ($currentRole === 'superadmin') ? "('student','admin')" : "('student')";
$listStmt = $conn->prepare("SELECT id, username, email, role, is_active, created_at FROM users WHERE role IN $rolesList ORDER BY created_at DESC");
$listStmt->execute();
$listRes = $listStmt->get_result();
$users = $listRes ? $listRes->fetch_all(MYSQLI_ASSOC) : [];
$listStmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-[#f0f7ff]">
    <?php require __DIR__ . '/header.php'; ?>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="pt-14 lg:pl-16" id="appMain">
        <main id="app-content" class="max-w-7xl mx-auto px-4 py-6">

            <div class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-[#212121]">Account Manager</h2>
                    <?php if (in_array($currentRole, ['admin', 'superadmin'], true)): ?>
                        <button class="rounded-xl bg-[#1e88e5] px-4 py-2 text-white" onclick="document.getElementById('createModal').classList.remove('hidden')">Create Account</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border bg-white p-6">
                <h2 class="text-lg font-semibold text-[#212121]">Users</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b text-[#293D82]">
                            <tr class="text-left">
                                <th class="px-3 py-2">ID</th>
                                <th class="px-3 py-2">Username</th>
                                <th class="px-3 py-2">Email</th>
                                <th class="px-3 py-2">Role</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr class="border-b">
                                    <td class="px-3 py-2 text-[#212121]<?= '' ?>"><?= (int)$u['id']; ?></td>
                                    <td class="px-3 py-2 text-[#212121]<?= '' ?>"><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-3 py-2 text-[#212121]<?= '' ?>"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-3 py-2 text-[#212121]<?= '' ?>"><?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="px-3 py-2"><?= ((int)$u['is_active'] === 1) ? '<span class="rounded bg-green-100 px-2 py-0.5 text-green-700">Active</span>' : '<span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700">Inactive</span>'; ?></td>
                                    <td class="px-3 py-2">
                                        <?php if (in_array($currentRole, ['admin', 'superadmin'], true)): ?>
                                            <button class="rounded-xl px-3 py-1 text-[#293D82] hover:bg-[#e3f2fd] text-xs" onclick="openEditModal(<?= (int)$u['id']; ?>,'<?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?>','<?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?>','<?= htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?>')" aria-label="Edit user <?= (int)$u['id']; ?>">Edit</button>
                                        <?php else: ?>
                                            <span class="text-gray-500 text-xs">View only</span>
                                        <?php endif; ?>
                                        <?php $canToggle = ($currentRole === 'superadmin') || ($currentRole === 'admin' && $u['role'] === 'student'); ?>
                                        <?php if ($canToggle && (int)$u['id'] !== $userId): ?>
                                            <form method="POST" action="<?= route_url('account/manager/action') ?>" class="inline" onsubmit="return submitWithLoading(this)" aria-label="Toggle active">
                                                <?= csrf_input(); ?>
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                                                <input type="hidden" name="active" value="<?= ((int)$u['is_active'] === 1) ? '0' : '1'; ?>">
                                                <button type="submit" class="ml-2 rounded-md px-2 py-1 text-xs <?= ((int)$u['is_active'] === 1) ? 'bg-red-600 text-white' : 'bg-green-600 text-white' ?>"><?= ((int)$u['is_active'] === 1) ? 'Deactivate' : 'Activate' ?></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="createModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/40" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
                    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-lg">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <h3 class="text-lg font-semibold">Create Account</h3>
                            <button class="rounded-xl p-2 hover:bg-gray-100" aria-label="Close" onclick="document.getElementById('createModal').classList.add('hidden')">✕</button>
                        </div>
                        <div class="p-4">
                            <form method="POST" action="<?= route_url('account/manager/action') ?>" onsubmit="return submitWithLoading(this)" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="create">
                                <label class="block text-sm font-medium text-gray-700">Username<input type="text" name="username" required minlength="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></label>
                                <label class="block text-sm font-medium text-gray-700">Email<input type="email" name="email" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></label>
                                <label class="block text-sm font-medium text-gray-700">Password<input type="password" name="password" required minlength="6" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></label>
                                <label class="block text-sm font-medium text-gray-700">Role
                                    <select name="role" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                                        <?php if ($currentRole === 'superadmin'): ?>
                                            <option value="student">Student</option>
                                            <option value="admin">Admin</option>
                                        <?php else: ?>
                                            <option value="student">Student</option>
                                        <?php endif; ?>
                                    </select>
                                </label>
                                <div class="sm:col-span-2 flex justify-end gap-2">
                                    <button type="button" class="rounded-xl border border-gray-300 px-3 py-2" onclick="document.getElementById('createModal').classList.add('hidden')">Cancel</button>
                                    <button type="submit" class="rounded-xl bg-[#1e88e5] px-3 py-2 text-white">Create</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div id="editModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/40" onclick="document.getElementById('editModal').classList.add('hidden')"></div>
                    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-lg">
                        <div class="flex items-center justify-between border-b px-4 py-3">
                            <h3 class="text-lg font-semibold">Edit Account</h3>
                            <button class="rounded-xl p-2 hover:bg-gray-100" aria-label="Close" onclick="document.getElementById('editModal').classList.add('hidden')">✕</button>
                        </div>
                        <div class="p-4">
                            <form id="editForm" method="POST" action="<?= route_url('account/manager/action') ?>" onsubmit="return submitWithLoading(this)" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <?= csrf_input(); ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="user_id" id="edit_id" value="0">
                                <label class="block text-sm font-medium text-gray-700">Username<input type="text" name="username" id="edit_username" required minlength="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></label>
                                <label class="block text-sm font-medium text-gray-700">Email<input type="email" name="email" id="edit_email" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></label>
                                <label class="block text-sm font-medium text-gray-700">New Password<input type="password" name="password" minlength="6" placeholder="Leave blank to keep" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"></label>
                                <label class="block text-sm font-medium text-gray-700">Role
                                    <select name="role" id="edit_role" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                                        <?php if ($currentRole === 'superadmin'): ?>
                                            <option value="student">Student</option>
                                            <option value="admin">Admin</option>
                                        <?php else: ?>
                                            <option value="student">Student</option>
                                        <?php endif; ?>
                                    </select>
                                </label>
                                <div class="sm:col-span-2 flex justify-end gap-2">
                                    <button type="button" class="rounded-xl border border-gray-300 px-3 py-2" onclick="document.getElementById('editModal').classList.add('hidden')">Cancel</button>
                                    <button type="submit" class="rounded-xl bg-[#1e88e5] px-3 py-2 text-white">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script data-page-script="true">
                function submitWithLoading(form) {
                    var btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.classList.add('opacity-60');
                    }
                    return true;
                }

                function openEditModal(id, username, email, role) {
                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit_username').value = username;
                    document.getElementById('edit_email').value = email;
                    var sel = document.getElementById('edit_role');
                    if (sel) {
                        for (var i = 0; i < sel.options.length; i++) {
                            sel.options[i].selected = (sel.options[i].value === role);
                        }
                    }
                    document.getElementById('editModal').classList.remove('hidden');
                }
            </script>
        </main>
    </div>
</body>

</html>