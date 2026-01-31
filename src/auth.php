<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';
secure_session_start();

if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url(''));
    exit;
}

function current_role_for_auth(): string
{
    $role = $_SESSION['auth_role'] ?? null;
    if ($role) return normalize_role((string)$role);
    $uid = (int)($_SESSION['auth_user_id'] ?? 0);
    if ($uid <= 0) return 'student';
    global $conn;
    $stmt = $conn->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if ($row && isset($row['role'])) {
            $norm = normalize_role((string)$row['role']);
            $_SESSION['auth_role'] = $norm;
            return $norm;
        }
    }
    return 'student';
}

function enforce_auth_for_page(string $pageBaseName): void
{
    $permissions = [
        'superadmin' => ['welcome.php', 'account_manager.php', 'menu_1.php', 'menu_2.php', 'menu_3.php', 'menu_4.php', 'menu_5.php', 'menu_6.php'],
        'admin' => ['welcome.php', 'account_manager.php', 'menu_1.php', 'menu_2.php', 'menu_3.php', 'menu_4.php', 'menu_5.php', 'menu_6.php'],
        'student' => ['application_form.php'],
    ];
    $role = current_role_for_auth();
    $allowed = $permissions[$role] ?? [];
    if (!in_array($pageBaseName, $allowed, true)) {
        if ($role === 'student') {
            header('Location: ' . route_url('students/home'));
        } else {
            header('Location: ' . route_url('admin/menu-1'));
        }
        exit;
    }
}
