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
    header('Location: ' . route_url('students/home'));
    exit;
}

if (!csrf_validate()) {
    $_SESSION['error'] = 'Invalid request. Please refresh and try again.';
    header('Location: ' . route_url('students/home'));
    exit;
}

$userId = (int) $_SESSION['auth_user_id'];
$first = isset($_POST['first_name']) ? trim((string)$_POST['first_name']) : '';
$last = isset($_POST['last_name']) ? trim((string)$_POST['last_name']) : '';
$address = isset($_POST['address']) ? trim((string)$_POST['address']) : '';
$username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

$errors = [];
if ($first === '') $errors[] = 'First name required';
if ($last === '') $errors[] = 'Last name required';
if ($username === '') $errors[] = 'Username required';

ensure_student_profiles_table($conn);

// Check if username is taken by another user
$stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
if ($stmt) {
    $stmt->bind_param('si', $username, $userId);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $errors[] = 'Username already taken.';
    }
    $stmt->close();
}

// Handle photo upload
$relpath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];
    $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png'];

    if (!array_key_exists($file['type'], $allowed)) {
        $errors[] = 'Invalid image type. Only JPG and PNG allowed.';
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $errors[] = 'File too large (max 2MB).';
    } else {
        $ext = $allowed[$file['type']];
        // Change upload directory to public/uploads so it is accessible via URL
        $uploaddir = __DIR__ . '/../uploads/students/' . $userId;
        if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
        $filename = 'profile_' . time() . $ext; // Add timestamp to avoid caching issues
        $dest = $uploaddir . DIRECTORY_SEPARATOR . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $relpath = 'uploads/students/' . $userId . '/' . $filename;
        } else {
            $errors[] = 'Failed to save uploaded file.';
        }
    }
}

if (!empty($errors)) {
    $_SESSION['error'] = implode('; ', $errors);
    header('Location: ' . route_url('students/home'));
    exit;
}

// Check if profile exists
$check = $conn->prepare('SELECT user_id FROM student_profiles WHERE user_id = ?');
$check->bind_param('i', $userId);
$check->execute();
$exists = $check->get_result()->fetch_assoc();
$check->close();

if ($exists) {
    if ($relpath) {
        $up = $conn->prepare('UPDATE student_profiles SET first_name = ?, last_name = ?, address = ?, photo_path = ? WHERE user_id = ?');
        $up->bind_param('ssssi', $first, $last, $address, $relpath, $userId);
    } else {
        $up = $conn->prepare('UPDATE student_profiles SET first_name = ?, last_name = ?, address = ? WHERE user_id = ?');
        $up->bind_param('sssi', $first, $last, $address, $userId);
    }
} else {
    // Insert new profile
    $photoVal = $relpath ?? '';
    $up = $conn->prepare('INSERT INTO student_profiles (user_id, first_name, last_name, address, photo_path, is_completed) VALUES (?, ?, ?, ?, ?, 1)');
    $up->bind_param('issss', $userId, $first, $last, $address, $photoVal);
}

if (!$up->execute()) {
    error_log('Failed to update profile: ' . $up->error);
    $_SESSION['error'] = 'Failed to update profile details.';
    header('Location: ' . route_url('students/home'));
    exit;
}
$up->close();

// Update users table (username and password)
if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $uStmt = $conn->prepare('UPDATE users SET username = ?, password = ? WHERE id = ?');
    $uStmt->bind_param('ssi', $username, $hashed, $userId);
} else {
    $uStmt = $conn->prepare('UPDATE users SET username = ? WHERE id = ?');
    $uStmt->bind_param('si', $username, $userId);
}

if (!$uStmt->execute()) {
    error_log('Failed to update user credentials: ' . $uStmt->error);
    $_SESSION['error'] = 'Failed to update login credentials.';
    header('Location: ' . route_url('students/home'));
    exit;
}
$uStmt->close();

$_SESSION['success'] = 'Profile updated successfully.';
header('Location: ' . route_url('students/home'));
exit;
