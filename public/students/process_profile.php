<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
require_once __DIR__ . '/../../src/db.php';

if (empty($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url('students/login'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . route_url('students/start'));
    exit;
}

if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
    header('Location: ' . route_url('students/start'));
    exit;
}

$userId = (int) $_SESSION['auth_user_id'];
$first = isset($_POST['first_name']) ? trim((string)$_POST['first_name']) : '';
$last = isset($_POST['last_name']) ? trim((string)$_POST['last_name']) : '';
$address = isset($_POST['address']) ? trim((string)$_POST['address']) : '';

$errors = [];
if ($first === '') $errors[] = 'First name required';
if ($last === '') $errors[] = 'Last name required';

ensure_student_profiles_table($conn);

// handle photo
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Profile photo is required.';
}

if (!empty($errors)) {
    $_SESSION['error'] = implode('; ', $errors);
    header('Location: ' . route_url('students/start'));
    exit;
}

$file = $_FILES['photo'];
$allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png'];
if (!array_key_exists($file['type'], $allowed)) {
    $_SESSION['error'] = 'Invalid image type. Only JPG and PNG allowed.';
    header('Location: ' . route_url('students/start'));
    exit;
}
if ($file['size'] > 2 * 1024 * 1024) {
    $_SESSION['error'] = 'File too large (max 2MB).';
    header('Location: ' . route_url('students/start'));
    exit;
}

$ext = $allowed[$file['type']];
// Change upload directory to public/uploads so it is accessible via URL
$uploaddir = __DIR__ . '/../../public/uploads/students/' . $userId;
if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
$filename = 'profile_' . time() . $ext; // Add timestamp to avoid caching issues
$dest = $uploaddir . DIRECTORY_SEPARATOR . $filename;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    $_SESSION['error'] = 'Failed to save uploaded file.';
    header('Location: ' . route_url('students/start'));
    exit;
}

$relpath = 'uploads/students/' . $userId . '/' . $filename;

// insert or update profile
$up = $conn->prepare('INSERT INTO student_profiles (user_id, first_name, last_name, address, photo_path, is_completed) VALUES (?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), address = VALUES(address), photo_path = VALUES(photo_path), is_completed = 1');
$up->bind_param('issss', $userId, $first, $last, $address, $relpath);
if (!$up->execute()) {
    error_log('Failed to save profile: ' . $up->error);
    $_SESSION['error'] = 'Failed to save profile. Please try again.';
    header('Location: ' . route_url('students/start'));
    exit;
}
$up->close();

$_SESSION['success'] = 'Profile saved successfully.';
header('Location: ' . route_url('students/home'));
exit;
