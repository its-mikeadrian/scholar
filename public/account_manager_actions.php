<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url(''));
    exit;
}

$currentRole = auth_role();
require_role(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: ' . route_url('account/manager'));
    exit;
}

if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
    header('Location: ' . route_url('account/manager'));
    exit;
}

$action = $_POST['action'] ?? '';

function find_user(mysqli $conn, int $id): ?array
{
    $s = $conn->prepare('SELECT id, username, email, role, is_active FROM users WHERE id = ? LIMIT 1');
    if (!$s) return null;
    $s->bind_param('i', $id);
    if (!$s->execute()) {
        $s->close();
        return null;
    }
    $s->store_result();
    if ($s->num_rows === 0) {
        $s->close();
        return null;
    }
    $s->bind_result($rid, $rusername, $remail, $rrole, $ractive);
    $s->fetch();
    $s->close();
    return [
        'id' => (int)$rid,
        'username' => (string)$rusername,
        'email' => (string)$remail,
        'role' => (string)$rrole,
        'is_active' => (int)$ractive,
    ];
}

if ($action === 'create') {
    $username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $role = isset($_POST['role']) ? (string)$_POST['role'] : 'student';

    if ($username === '' || strlen($username) < 3) {
        $_SESSION['error'] = 'Username must be at least 3 characters.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please provide a valid email.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    if ($password === '' || strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    if (!in_array($role, ['student', 'admin', 'superadmin'], true)) {
        $role = 'student';
    }
    if (!user_can_manage_role($currentRole, $role)) {
        $_SESSION['error'] = 'You are not allowed to assign the selected role.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }

    $dupU = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $dupU->bind_param('s', $username);
    $dupU->execute();
    $dupU->store_result();
    if ($dupU->num_rows > 0) {
        $_SESSION['error'] = 'Username is already taken.';
        $dupU->close();
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $dupU->close();

    $dupE = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $dupE->bind_param('s', $email);
    $dupE->execute();
    $dupE->store_result();
    if ($dupE->num_rows > 0) {
        $_SESSION['error'] = 'Email is already registered.';
        $dupE->close();
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $dupE->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $conn->prepare('INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)');
    if (!$ins) {
        $_SESSION['error'] = 'Server error.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $ins->bind_param('ssss', $username, $email, $hash, $role);
    if (!$ins->execute()) {
        $_SESSION['error'] = 'Failed to create user.';
        $ins->close();
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $ins->close();
    $_SESSION['success'] = 'User created successfully.';
    header('Location: ' . route_url('account/manager'));
    exit;
}

if ($action === 'update') {
    $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $row = $id ? find_user($conn, $id) : null;
    if (!$row) {
        $_SESSION['error'] = 'User not found.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }

    $username = isset($_POST['username']) ? trim((string)$_POST['username']) : $row['username'];
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : $row['email'];
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $newRole = isset($_POST['role']) ? (string)$_POST['role'] : $row['role'];

    if (!user_can_manage_role($currentRole, $newRole)) {
        $_SESSION['error'] = 'You are not allowed to assign the selected role.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    if ($username === '' || strlen($username) < 3) {
        $_SESSION['error'] = 'Username must be at least 3 characters.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please provide a valid email.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }

    $dupU = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
    $dupU->bind_param('si', $username, $id);
    $dupU->execute();
    $dupU->store_result();
    if ($dupU->num_rows > 0) {
        $_SESSION['error'] = 'Username is already taken.';
        $dupU->close();
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $dupU->close();

    $dupE = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $dupE->bind_param('si', $email, $id);
    $dupE->execute();
    $dupE->store_result();
    if ($dupE->num_rows > 0) {
        $_SESSION['error'] = 'Email is already in use.';
        $dupE->close();
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $dupE->close();

    if ($password !== '') {
        if (strlen($password) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters.';
            header('Location: ' . route_url('account/manager'));
            exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $conn->prepare('UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?');
        if (!$upd) {
            $_SESSION['error'] = 'Server error.';
            header('Location: ' . route_url('account/manager'));
            exit;
        }
        $upd->bind_param('ssssi', $username, $email, $hash, $newRole, $id);
    } else {
        $upd = $conn->prepare('UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?');
        if (!$upd) {
            $_SESSION['error'] = 'Server error.';
            header('Location: ' . route_url('account/manager'));
            exit;
        }
        $upd->bind_param('sssi', $username, $email, $newRole, $id);
    }
    if (!$upd->execute()) {
        $_SESSION['error'] = 'Failed to update user.';
        $upd->close();
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $upd->close();
    $_SESSION['success'] = 'User updated successfully.';
    header('Location: ' . route_url('account/manager'));
    exit;
}

if ($action === 'toggle_active') {
    $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $active = isset($_POST['active']) ? (int)$_POST['active'] : 1;
    if ($id === (int)$_SESSION['auth_user_id']) {
        $_SESSION['error'] = 'You cannot change your own activation status.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $row = $id ? find_user($conn, $id) : null;
    if (!$row) {
        $_SESSION['error'] = 'User not found.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    if (!user_can_manage_role($currentRole, (string)$row['role'])) {
        $_SESSION['error'] = 'Insufficient permissions to modify this user.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $upd = $conn->prepare('UPDATE users SET is_active = ? WHERE id = ?');
    if (!$upd) {
        $_SESSION['error'] = 'Server error.';
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $upd->bind_param('ii', $active, $id);
    if (!$upd->execute()) {
        $_SESSION['error'] = 'Failed to update status.';
        $upd->close();
        header('Location: ' . route_url('account/manager'));
        exit;
    }
    $upd->close();
    $_SESSION['success'] = ($active === 1) ? 'User activated.' : 'User deactivated.';
    header('Location: ' . route_url('account/manager'));
    exit;
}

$_SESSION['error'] = 'Unknown action.';
header('Location: ' . route_url('account/manager'));
exit;
