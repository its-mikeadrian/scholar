<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../config/env.php';

if (!isset($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url(''));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . route_url('menu-1'));
    exit;
}

// CSRF protection
if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
    $ret = isset($_POST['return_to']) ? (string)$_POST['return_to'] : route_url('menu-1');
    header('Location: ' . $ret);
    exit;
}

$userId = (int) $_SESSION['auth_user_id'];
$username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';

$errors = [];
$old = [
    'username' => $username,
    'email' => $email,
];
// Preserve return target
$returnTo = isset($_POST['return_to']) ? (string)$_POST['return_to'] : route_url('menu-1');
if (!is_string($returnTo) || $returnTo === '') { $returnTo = route_url('menu-1'); }

// Validation
if ($username === '' || strlen($username) < 3) {
    $errors['username'] = 'Username must be at least 3 characters.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please provide a valid email address.';
}
if ($password !== '' && strlen($password) < 6) {
    $errors['password'] = 'Password must be at least 6 characters.';
}

// Early return for basic validation errors
if (!empty($errors)) {
    $_SESSION['errors_account'] = $errors;
    $_SESSION['old_account'] = $old;
    $_SESSION['error'] = reset($errors) ?: 'Please fix the highlighted fields.';
    header('Location: ' . $returnTo);
    exit;
}

// Uniqueness checks (exclude current user)
$dupUserStmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
if ($dupUserStmt) {
    $dupUserStmt->bind_param('si', $username, $userId);
    $dupUserStmt->execute();
    $dupUserStmt->store_result();
    if ($dupUserStmt->num_rows > 0) {
        $_SESSION['errors_account'] = ['username' => 'Username is already taken.'];
        $_SESSION['old_account'] = $old;
        $_SESSION['error'] = 'Username is already taken.';
        $dupUserStmt->close();
        header('Location: ' . $returnTo);
        exit;
    }
    $dupUserStmt->close();
}

$dupEmailStmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
if ($dupEmailStmt) {
    $dupEmailStmt->bind_param('si', $email, $userId);
    $dupEmailStmt->execute();
    $dupEmailStmt->store_result();
    if ($dupEmailStmt->num_rows > 0) {
        $_SESSION['errors_account'] = ['email' => 'Email is already in use.'];
        $_SESSION['old_account'] = $old;
        $_SESSION['error'] = 'Email is already in use.';
        $dupEmailStmt->close();
        header('Location: ' . $returnTo);
        exit;
    }
    $dupEmailStmt->close();
}

// Build update query
if ($password !== '') {
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $upd = $conn->prepare('UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?');
    if (!$upd) {
        $_SESSION['error'] = 'Server error. Please try again later.';
        header('Location: ' . $returnTo);
        exit;
    }
    $upd->bind_param('sssi', $username, $email, $newHash, $userId);
    $upd->execute();
    $upd->close();
} else {
    $upd = $conn->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
    if (!$upd) {
        $_SESSION['error'] = 'Server error. Please try again later.';
        header('Location: ' . $returnTo);
        exit;
    }
    $upd->bind_param('ssi', $username, $email, $userId);
    $upd->execute();
    $upd->close();
}

$_SESSION['success'] = 'Account settings updated successfully.';
$_SESSION['account_settings_success'] = true;
header('Location: ' . $returnTo);
exit;