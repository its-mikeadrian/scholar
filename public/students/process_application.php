<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
require_once __DIR__ . '/../../src/db.php';

// Check if user is logged in and is a student
$user_id = auth_user_id();
$role = auth_role();

if (!$user_id || $role !== 'student') {
    error_log('Application submission failed: user_id=' . ($user_id ?? 'null') . ', role=' . $role);
    http_response_code(403);
    $_SESSION['error_message'] = 'You must be logged in as a student to submit an application.';
    header('Location: ' . route_url('home'));
    exit;
}

// Verify CSRF token
if (!csrf_validate()) {
    error_log('Application submission failed: Invalid or missing CSRF token');
    http_response_code(403);
    $_SESSION['error_message'] = 'Security validation failed. Please fill the form again.';
    header('Location: ' . route_url('students/application_form'));
    exit;
}

$errors = [];
$uploaded_files = [];

// Create upload directory if it doesn't exist
$upload_dir = __DIR__ . '/../uploads/requirements/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        error_log('Failed to create upload directory: ' . $upload_dir);
        $_SESSION['error_message'] = 'Failed to create upload directory.';
        header('Location: ' . route_url('students/application_form'));
        exit;
    }
}

// Function to safely upload file
function upload_file($file_key, $upload_dir, $user_id) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$file_key];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log('File upload error for ' . $file_key . ': Error code ' . $file['error']);
        return null;
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        error_log('Invalid file type for ' . $file_key . ': ' . $file['type']);
        return null;
    }

    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        error_log('File too large for ' . $file_key . ': ' . $file['size'] . ' bytes');
        return null;
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'app_' . $user_id . '_' . $file_key . '_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        error_log('File uploaded successfully: ' . $filename);
        return 'uploads/requirements/' . $filename;
    } else {
        error_log('Failed to move uploaded file for ' . $file_key);
        return null;
    }
}

// Upload files
$cor_coe_file = upload_file('cor_coe_file', $upload_dir, $user_id);
$cert_grades_file = upload_file('cert_grades_file', $upload_dir, $user_id);
$barangay_indigency_file = upload_file('barangay_indigency_file', $upload_dir, $user_id);
$voters_cert_file = upload_file('voters_cert_file', $upload_dir, $user_id);

try {
    // Get PDO connection
    $pdo = get_db_connection();
    
    // First, ensure the table exists
    $create_table_sql = "CREATE TABLE IF NOT EXISTS scholarship_applications (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        academic_level VARCHAR(50) NOT NULL,
        semester VARCHAR(50) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100) DEFAULT NULL,
        last_name VARCHAR(100) NOT NULL,
        date_of_birth DATE DEFAULT NULL,
        age INT(3) DEFAULT NULL,
        cellphone_number VARCHAR(20) DEFAULT NULL,
        sex ENUM('Male','Female','Other') DEFAULT NULL,
        mothers_maiden_name VARCHAR(100) DEFAULT NULL,
        mothers_occupation VARCHAR(100) DEFAULT NULL,
        fathers_name VARCHAR(100) DEFAULT NULL,
        fathers_occupation VARCHAR(100) DEFAULT NULL,
        street_address VARCHAR(255) DEFAULT NULL,
        house_number VARCHAR(50) DEFAULT NULL,
        barangay VARCHAR(100) DEFAULT NULL,
        municipality VARCHAR(100) DEFAULT 'San Luis',
        cor_coe_file VARCHAR(255) DEFAULT NULL,
        cert_grades_file VARCHAR(255) DEFAULT NULL,
        barangay_indigency_file VARCHAR(255) DEFAULT NULL,
        voters_cert_file VARCHAR(255) DEFAULT NULL,
        status ENUM('pending','approved','rejected','incomplete') NOT NULL DEFAULT 'pending',
        submission_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        notes TEXT DEFAULT NULL,
        KEY fk_applications_user (user_id),
        KEY idx_status (status),
        KEY idx_submission_date (submission_date),
        CONSTRAINT fk_applications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($create_table_sql);
    error_log('Scholarship applications table created or already exists');
    
    // Prepare data with proper sanitization
    $academic_level = htmlspecialchars($_POST['academic_level'] ?? '', ENT_QUOTES, 'UTF-8');
    $semester = htmlspecialchars($_POST['semester'] ?? '', ENT_QUOTES, 'UTF-8');
    $first_name = htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $last_name = htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $middle_name = htmlspecialchars($_POST['middle_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $date_of_birth = htmlspecialchars($_POST['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8');
    $age = (int)($_POST['age'] ?? 0);
    $cellphone_number = htmlspecialchars($_POST['cellphone_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $sex = htmlspecialchars($_POST['sex'] ?? '', ENT_QUOTES, 'UTF-8');
    $mothers_maiden_name = htmlspecialchars($_POST['mothers_maiden_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $mothers_occupation = htmlspecialchars($_POST['mothers_occupation'] ?? '', ENT_QUOTES, 'UTF-8');
    $fathers_name = htmlspecialchars($_POST['fathers_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $fathers_occupation = htmlspecialchars($_POST['fathers_occupation'] ?? '', ENT_QUOTES, 'UTF-8');
    $street_address = htmlspecialchars($_POST['street_address'] ?? '', ENT_QUOTES, 'UTF-8');
    $house_number = htmlspecialchars($_POST['house_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $barangay = htmlspecialchars($_POST['barangay'] ?? '', ENT_QUOTES, 'UTF-8');
    $municipality = htmlspecialchars($_POST['municipality'] ?? 'San Luis', ENT_QUOTES, 'UTF-8');

    // Validate required fields
    if (empty($academic_level) || empty($first_name) || empty($last_name)) {
        throw new Exception('Required fields are missing');
    }

    // Insert into database
    $sql = "INSERT INTO scholarship_applications (
        user_id, academic_level, semester, first_name, middle_name, last_name,
        date_of_birth, age, cellphone_number, sex, mothers_maiden_name,
        mothers_occupation, fathers_name, fathers_occupation, street_address,
        house_number, barangay, municipality, cor_coe_file, cert_grades_file,
        barangay_indigency_file, voters_cert_file, status
    ) VALUES (
        :user_id, :academic_level, :semester, :first_name, :middle_name, :last_name,
        :date_of_birth, :age, :cellphone_number, :sex, :mothers_maiden_name,
        :mothers_occupation, :fathers_name, :fathers_occupation, :street_address,
        :house_number, :barangay, :municipality, :cor_coe_file, :cert_grades_file,
        :barangay_indigency_file, :voters_cert_file, 'pending'
    )";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':user_id' => $user_id,
        ':academic_level' => $academic_level,
        ':semester' => $semester,
        ':first_name' => $first_name,
        ':middle_name' => $middle_name,
        ':last_name' => $last_name,
        ':date_of_birth' => !empty($date_of_birth) ? $date_of_birth : null,
        ':age' => $age,
        ':cellphone_number' => $cellphone_number,
        ':sex' => $sex,
        ':mothers_maiden_name' => $mothers_maiden_name,
        ':mothers_occupation' => $mothers_occupation,
        ':fathers_name' => $fathers_name,
        ':fathers_occupation' => $fathers_occupation,
        ':street_address' => $street_address,
        ':house_number' => $house_number,
        ':barangay' => $barangay,
        ':municipality' => $municipality,
        ':cor_coe_file' => $cor_coe_file,
        ':cert_grades_file' => $cert_grades_file,
        ':barangay_indigency_file' => $barangay_indigency_file,
        ':voters_cert_file' => $voters_cert_file
    ]);

    if (!$result) {
        throw new Exception('Failed to execute insert statement');
    }

    $application_id = $pdo->lastInsertId();
    error_log('Application submitted successfully: ID=' . $application_id . ', User=' . $user_id);

    $_SESSION['success_message'] = 'Application submitted successfully!';
    header('Location: ' . route_url('students/my_application'));
    exit;

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    header('Location: ' . route_url('students/application_form'));
    exit;
} catch (Exception $e) {
    error_log('Application submission error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Error submitting application: ' . $e->getMessage();
    header('Location: ' . route_url('students/application_form'));
    exit;
}
?>
