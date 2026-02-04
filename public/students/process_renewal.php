<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
require_once __DIR__ . '/../../src/db.php';

$user_id = auth_user_id();
$role = auth_role();

if (!$user_id || $role !== 'student') {
    http_response_code(403);
    $_SESSION['error_message'] = 'You must be logged in as a student to submit a renewal.';
    header('Location: ' . route_url('home'));
    exit;
}

if (!csrf_validate()) {
    http_response_code(403);
    $_SESSION['error_message'] = 'Security validation failed. Please try again.';
    header('Location: ' . route_url('students/my_application'));
    exit;
}

$upload_dir = __DIR__ . '/../uploads/renewals/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        $_SESSION['error_message'] = 'Failed to create upload directory.';
        header('Location: ' . route_url('students/my_application'));
        exit;
    }
}

function renewal_upload_file(string $file_key, string $upload_dir, int $user_id): ?string
{
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$file_key];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types, true)) {
        return null;
    }

    $max_size = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $max_size) {
        return null;
    }

    $ext = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
    $filename = 'renewal_' . $user_id . '_' . $file_key . '_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return null;
    }

    return 'uploads/renewals/' . $filename;
}

try {
    $pdo = get_db_connection();

    $stmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE user_id = ? ORDER BY submission_date DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        $_SESSION['error_message'] = 'Renewal is only available after submitting an initial application.';
        header('Location: ' . route_url('students/my_application'));
        exit;
    }

    $application_id = (int)($application['id'] ?? 0);
    $is_paid = (int)($application['is_paid'] ?? 0);
    if ($is_paid !== 1) {
        $_SESSION['error_message'] = 'Renewal is only available after payout is marked as paid.';
        header('Location: ' . route_url('students/my_application'));
        exit;
    }

    $create_table_sql = "CREATE TABLE IF NOT EXISTS scholarship_renewals (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        application_id INT UNSIGNED NOT NULL,
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
        is_paid TINYINT(1) NOT NULL DEFAULT 0,
        paid_date DATE DEFAULT NULL,
        resubmitted_from_incomplete TINYINT(1) NOT NULL DEFAULT 0,
        submission_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_user (user_id),
        KEY idx_application (application_id),
        KEY idx_status (status),
        KEY idx_submission_date (submission_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($create_table_sql);

    $stmt = $pdo->prepare("SELECT id FROM scholarship_renewals WHERE user_id = ? AND application_id = ? AND status = 'pending' ORDER BY submission_date DESC LIMIT 1");
    $stmt->execute([$user_id, $application_id]);
    $existing_pending = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing_pending) {
        $_SESSION['error_message'] = 'You already submitted a renewal. Please wait for review.';
        header('Location: ' . route_url('students/my_application'));
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM scholarship_renewals WHERE user_id = ? AND application_id = ? ORDER BY submission_date DESC LIMIT 1");
    $stmt->execute([$user_id, $application_id]);
    $latest_renewal = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $is_edit_incomplete = $latest_renewal && strtolower((string)($latest_renewal['status'] ?? '')) === 'incomplete';

    $cor_coe_file = renewal_upload_file('cor_coe_file', $upload_dir, $user_id);
    $cert_grades_file = renewal_upload_file('cert_grades_file', $upload_dir, $user_id);
    $barangay_indigency_file = renewal_upload_file('barangay_indigency_file', $upload_dir, $user_id);
    $voters_cert_file = renewal_upload_file('voters_cert_file', $upload_dir, $user_id);

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

    if ($academic_level === '' || $semester === '' || $first_name === '' || $last_name === '') {
        $_SESSION['error_message'] = 'Please fill in all required fields.';
        header('Location: ' . route_url('students/my_application'));
        exit;
    }

    if ($is_edit_incomplete) {
        $renewal_id = (int)($latest_renewal['id'] ?? 0);
        $cor_coe_file = $cor_coe_file ?: ($latest_renewal['cor_coe_file'] ?? null);
        $cert_grades_file = $cert_grades_file ?: ($latest_renewal['cert_grades_file'] ?? null);
        $barangay_indigency_file = $barangay_indigency_file ?: ($latest_renewal['barangay_indigency_file'] ?? null);
        $voters_cert_file = $voters_cert_file ?: ($latest_renewal['voters_cert_file'] ?? null);

        $sql = "UPDATE scholarship_renewals SET
            academic_level = :academic_level,
            semester = :semester,
            first_name = :first_name,
            middle_name = :middle_name,
            last_name = :last_name,
            date_of_birth = :date_of_birth,
            age = :age,
            cellphone_number = :cellphone_number,
            sex = :sex,
            mothers_maiden_name = :mothers_maiden_name,
            mothers_occupation = :mothers_occupation,
            fathers_name = :fathers_name,
            fathers_occupation = :fathers_occupation,
            street_address = :street_address,
            house_number = :house_number,
            barangay = :barangay,
            municipality = :municipality,
            cor_coe_file = :cor_coe_file,
            cert_grades_file = :cert_grades_file,
            barangay_indigency_file = :barangay_indigency_file,
            voters_cert_file = :voters_cert_file,
            status = 'pending',
            is_paid = 0,
            paid_date = NULL,
            resubmitted_from_incomplete = 1,
            submission_date = NOW(),
            updated_at = NOW()
        WHERE id = :id AND user_id = :user_id AND application_id = :application_id";
        $params = [
            ':user_id' => $user_id,
            ':application_id' => $application_id,
            ':id' => $renewal_id,
            ':academic_level' => $academic_level,
            ':semester' => $semester,
            ':first_name' => $first_name,
            ':middle_name' => $middle_name !== '' ? $middle_name : null,
            ':last_name' => $last_name,
            ':date_of_birth' => $date_of_birth !== '' ? $date_of_birth : null,
            ':age' => $age ?: null,
            ':cellphone_number' => $cellphone_number !== '' ? $cellphone_number : null,
            ':sex' => $sex !== '' ? $sex : null,
            ':mothers_maiden_name' => $mothers_maiden_name !== '' ? $mothers_maiden_name : null,
            ':mothers_occupation' => $mothers_occupation !== '' ? $mothers_occupation : null,
            ':fathers_name' => $fathers_name !== '' ? $fathers_name : null,
            ':fathers_occupation' => $fathers_occupation !== '' ? $fathers_occupation : null,
            ':street_address' => $street_address !== '' ? $street_address : null,
            ':house_number' => $house_number !== '' ? $house_number : null,
            ':barangay' => $barangay !== '' ? $barangay : null,
            ':municipality' => $municipality !== '' ? $municipality : null,
            ':cor_coe_file' => $cor_coe_file,
            ':cert_grades_file' => $cert_grades_file,
            ':barangay_indigency_file' => $barangay_indigency_file,
            ':voters_cert_file' => $voters_cert_file,
        ];
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $ex) {
            try {
                $pdo->exec("ALTER TABLE scholarship_renewals ADD COLUMN resubmitted_from_incomplete TINYINT(1) NOT NULL DEFAULT 0");
            } catch (Exception $ex2) {
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
    } else {
        $sql = "INSERT INTO scholarship_renewals (
            user_id, application_id, academic_level, semester, first_name, middle_name, last_name,
            date_of_birth, age, cellphone_number, sex, mothers_maiden_name, mothers_occupation,
            fathers_name, fathers_occupation, street_address, house_number, barangay, municipality,
            cor_coe_file, cert_grades_file, barangay_indigency_file, voters_cert_file, status
        ) VALUES (
            :user_id, :application_id, :academic_level, :semester, :first_name, :middle_name, :last_name,
            :date_of_birth, :age, :cellphone_number, :sex, :mothers_maiden_name, :mothers_occupation,
            :fathers_name, :fathers_occupation, :street_address, :house_number, :barangay, :municipality,
            :cor_coe_file, :cert_grades_file, :barangay_indigency_file, :voters_cert_file, 'pending'
        )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':application_id' => $application_id,
            ':academic_level' => $academic_level,
            ':semester' => $semester,
            ':first_name' => $first_name,
            ':middle_name' => $middle_name !== '' ? $middle_name : null,
            ':last_name' => $last_name,
            ':date_of_birth' => $date_of_birth !== '' ? $date_of_birth : null,
            ':age' => $age ?: null,
            ':cellphone_number' => $cellphone_number !== '' ? $cellphone_number : null,
            ':sex' => $sex !== '' ? $sex : null,
            ':mothers_maiden_name' => $mothers_maiden_name !== '' ? $mothers_maiden_name : null,
            ':mothers_occupation' => $mothers_occupation !== '' ? $mothers_occupation : null,
            ':fathers_name' => $fathers_name !== '' ? $fathers_name : null,
            ':fathers_occupation' => $fathers_occupation !== '' ? $fathers_occupation : null,
            ':street_address' => $street_address !== '' ? $street_address : null,
            ':house_number' => $house_number !== '' ? $house_number : null,
            ':barangay' => $barangay !== '' ? $barangay : null,
            ':municipality' => $municipality !== '' ? $municipality : null,
            ':cor_coe_file' => $cor_coe_file,
            ':cert_grades_file' => $cert_grades_file,
            ':barangay_indigency_file' => $barangay_indigency_file,
            ':voters_cert_file' => $voters_cert_file,
        ]);
    }

    $_SESSION['success_message'] = 'Renewal submitted successfully.';
    header('Location: ' . route_url('students/my_application'));
    exit;
} catch (Exception $e) {
    error_log('Renewal submission error: ' . $e->getMessage());
    $_SESSION['error_message'] = 'Server error while submitting renewal.';
    header('Location: ' . route_url('students/my_application'));
    exit;
}
