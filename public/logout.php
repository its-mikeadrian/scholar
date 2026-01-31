<?php
require_once __DIR__ . '/../src/security.php';
secure_session_start();

// Enforce CSRF-protected POST for logout
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid logout request. Please try again.';
    header('Location: ' . route_url('admin'));
    exit;
}

if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request token. Please refresh and try again.';
    header('Location: ' . route_url('admin'));
    exit;
}

// Clear Remember Me token and cookie
remember_me_clear();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], true);
}
session_destroy();
header('Location: ' . route_url('admin'));
exit;
