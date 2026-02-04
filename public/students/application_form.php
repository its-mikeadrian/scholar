<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
require_once __DIR__ . '/../../src/db.php';

enforce_student_profile_completed($conn);

// Check if user already has an existing application
$user_id = auth_user_id();
$editing = false;
$edit_application = null;
if ($user_id) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE user_id = ? ORDER BY submission_date DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $existing_application = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_application) {
            $status_lc = strtolower((string)($existing_application['status'] ?? ''));
            if ($status_lc === 'incomplete') {
                $editing = true;
                $edit_application = $existing_application;
            } else {
                header('Location: ' . route_url('students/my-application'));
                exit;
            }
        }
    } catch (Exception $e) {
        error_log('Error checking existing application: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iskolar Nang Luis - EDUCATIONAL ASSISTANCE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="includes/header.css">
    <link rel="stylesheet" href="includes/footer.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1e88e5;
            --primary-dark: #293D82;
            --secondary: #4caf50;
            --secondary-dark: #2e7d32;
            --accent: #ff9800;
            --light: #f0f7ff;
            --dark: #212121;
            --warning: #ff8f00;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        html,
        body {
            width: 100%;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
        }

        .container {
            width: calc(100% - 0px);
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        .apply-btn {
            background-color: #ffeb3b;
            color: var(--dark);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .apply-btn:hover {
            background-color: #ffd740;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
        }

        .hero {
            background: linear-gradient(#1e88e59d, #293c82ce), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'), url('img/sanluis.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 60px;
        }

        .hero h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 3rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            white-space: nowrap;
        }

        .hero p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto 40px;
            opacity: 0.9;
        }

        /* Hero inner layout: image left, text center, image right */
        .hero .hero-inner {
            display: flex;
            align-items: center;
            gap: 32px;
            justify-content: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero .hero-image img {
            width: 170px;
            height: 170px;
            border-radius: 12px;
            display: block;
            object-fit: cover;
            padding: 8px;
            flex-shrink: 0;
        }

        .hero .hero-text {
            text-align: center;
            flex: 0 0 auto;
        }

        .hero .hero-image-right img {
            width: 250px;
            height: 250px;
            border-radius: 12px;
            display: block;
            object-fit: cover;
            padding: 8px;
            flex-shrink: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {

            .hero {
                padding: 60px 0;
            }

            .hero .hero-inner {

                padding-top: 100px;
                gap: 20px;
            }

            /* para mawala ang img */
            .hero .hero-image img {
                transition: all 1.3s ease;
                width: 0px;
                height: 0px;

            }

            .hero .hero-image-right img {
                transition: all 1.3s ease;
                width: 0px;
                height: 0px;

            }

            /* clossing langs*/
            .hero h2 {
                font-size: 2.1rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .scholarship-form-requirements {
                flex-direction: column;
            }

            .section-title {
                font-size: 1.8rem;
            }
        }

        /* Scholarship Application Form Section Styles */
        .scholarship-form-section {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 0;
        }

        .scholarship-form-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 40px 32px;
            max-width: 850px;
            width: 100%;
            margin: 0 auto;
        }

        .scholarship-form-title {
            text-align: center;
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            color: #293D82;
            margin-bottom: 24px;
            letter-spacing: 1px;
        }

        .scholarship-form-form {
            width: 100%;
        }

        .scholarship-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .scholarship-form-label {
            font-size: 13px;
            font-weight: 500;
        }

        .scholarship-form-select,
        .scholarship-form-input {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .scholarship-form-select.error,
        .scholarship-form-input.error {
            border-color: #f44336;
            background-color: #ffebee;
            box-shadow: 0 0 0 3px rgba(244, 67, 54, 0.1);
        }

        .scholarship-form-select.error:focus,
        .scholarship-form-input.error:focus {
            outline: none;
            border-color: #f44336;
            box-shadow: 0 0 0 3px rgba(244, 67, 54, 0.2);
        }

        .error-message {
            color: #f44336;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .scholarship-form-radio-group {
            grid-column: 1/3;
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .scholarship-form-radio {
            margin-left: 10px;
        }

        .scholarship-form-radio-label {
            margin-right: 10px;
        }

        .scholarship-form-requirements {
            margin-top: 32px;
            display: flex;
            gap: 24px;
        }

        .scholarship-form-req-box h3 {
            font-size: 1.3rem;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .scholarship-form-req-content {
            flex: 1;
        }

        .scholarship-form-req-list {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .scholarship-form-req-item {
            background: #f0f7ff;
            border-radius: 10px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .scholarship-form-req-item-content {
            flex: 1;
        }

        .scholarship-form-upload-box {
            flex: 0 0 120px;
            background: white;
            border: 2px dashed #1e88e5;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .scholarship-form-upload-box .upload-preview img {
            width: 100%;
            height: 72px;
            object-fit: cover;
            border-radius: 6px;
            display: block;
        }

        .scholarship-form-upload-box .upload-preview .file-name {
            font-size: 12px;
            color: #333;
            margin-top: 6px;
            display: block;
            word-break: break-word;
        }

        .scholarship-form-upload-box:hover {
            background: #e3f2fd;
            border-color: #293D82;
        }

        .scholarship-form-upload-box i {
            font-size: 24px;
            color: #1e88e5;
            display: block;
            margin-bottom: 4px;
        }

        .scholarship-form-upload-box span {
            font-size: 11px;
            color: #1e88e5;
            font-weight: 600;
            display: block;
        }

        .scholarship-form-upload-box input {
            display: none;
        }

        .scholarship-form-submit {
            display: flex;
            justify-content: center;
            margin-top: 32px;
        }

        .scholarship-form-btn {
            background: #ff3fa4;
            color: white;
            border: none;
            padding: 12px 48px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
            transition: all 0.3s;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 999;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            cursor: pointer;
        }

        .modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close-btn {
            background: #e0e0e0;
            color: #333;
            border: none;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close-btn:hover {
            background: #d0d0d0;
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-inner">
                <div class="hero-image">
                    <img src="img/sanluislogo.png" alt="San Luis Logo">
                </div>
                <div class="hero-text">
                    <h2>ISKOLAR NANG LUIS</h2>
                    <p>EDUCATIONAL ASSISTANCE</p>
                </div>
                <div class="hero-image-right">
                    <img src="img/oncall.png" alt="On Call">
                </div>
            </div>
        </div>
    </section>

    <!-- Scholarship Application Form Section -->
    <section id="application-form-section" class="scholarship-form-section">
        <div class="scholarship-form-container">
            <h2 class="scholarship-form-title">SCHOLARSHIP APPLICATION FORM</h2>
            <?php if ($editing && $edit_application): ?>
                <div style="margin: 16px 0; padding: 14px 16px; border-radius: 12px; background: #fff8e1; border: 1px solid #ffe0b2; color: #5d4037;">
                    <strong>Incomplete application:</strong> You can update your details and re-submit for review.
                    <?php if (!empty($edit_application['incomplete_reason'])): ?>
                        <div style="margin-top: 8px; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($edit_application['incomplete_reason'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <form class="scholarship-form-form" method="POST" action="<?php echo htmlspecialchars(route_url('students/process-application'), ENT_QUOTES); ?>" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>

                <!-- Page 1: Personal Information -->
                <div class="form-page" id="page1" style="display: block;">
                    <div class="scholarship-form-grid">
                        <div>
                            <label class="scholarship-form-label">Academic Level</label>
                            <select name="academic_level" class="scholarship-form-select" required>
                                <option value="">-- Select Academic Level --</option>
                                <option value="1st Year" <?php echo ($editing && (($edit_application['academic_level'] ?? '') === '1st Year')) ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2nd Year" <?php echo ($editing && (($edit_application['academic_level'] ?? '') === '2nd Year')) ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3rd Year" <?php echo ($editing && (($edit_application['academic_level'] ?? '') === '3rd Year')) ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4th Year" <?php echo ($editing && (($edit_application['academic_level'] ?? '') === '4th Year')) ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="scholarship-form-label">Semester</label>
                            <input type="text" name="semester" placeholder="Semester" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['semester'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        </div>
                        <input type="text" name="first_name" placeholder="First Name" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['first_name'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="text" name="last_name" placeholder="Last Name" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['last_name'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="text" name="middle_name" placeholder="Middle Name" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['middle_name'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="date" name="date_of_birth" placeholder="dd/mm/yyyy" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['date_of_birth'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="text" name="age" placeholder="Age" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['age'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="text" name="cellphone_number" placeholder="Cellphone Number" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['cellphone_number'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <div class="scholarship-form-radio-group">
                            <label class="scholarship-form-label">Sex</label>
                            <input type="radio" id="male" name="sex" value="Male" class="scholarship-form-radio" <?php echo ($editing && (($edit_application['sex'] ?? '') === 'Male')) ? 'checked' : ''; ?> /> <label for="male" class="scholarship-form-radio-label">Male</label>
                            <input type="radio" id="female" name="sex" value="Female" class="scholarship-form-radio" <?php echo ($editing && (($edit_application['sex'] ?? '') === 'Female')) ? 'checked' : ''; ?> /> <label for="female" class="scholarship-form-radio-label">Female</label>
                        </div>
                        <input type="text" name="mothers_maiden_name" placeholder="Mother's Maiden Name" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['mothers_maiden_name'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="text" name="mothers_occupation" placeholder="Occupation" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['mothers_occupation'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="text" name="fathers_name" placeholder="Father's Name" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['fathers_name'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="text" name="fathers_occupation" placeholder="Occupation" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['fathers_occupation'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="text" name="street_address" placeholder="Street Address" class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['street_address'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <input type="text" name="house_number" placeholder="House no./Bldg no." class="scholarship-form-input" value="<?php echo htmlspecialchars($editing ? ($edit_application['house_number'] ?? '') : '', ENT_QUOTES, 'UTF-8'); ?>" required />
                        <div>
                            <label class="scholarship-form-label">Barangay</label>
                            <select name="barangay" class="scholarship-form-select" required>
                                <option value="">-- Select Barangay --</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'San Agustin')) ? 'selected' : ''; ?>>San Agustin</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'San Carlos')) ? 'selected' : ''; ?>>San Carlos</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'San Isidro')) ? 'selected' : ''; ?>>San Isidro</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'San Jose')) ? 'selected' : ''; ?>>San Jose</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'San Juan')) ? 'selected' : ''; ?>>San Juan</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'San Nicolas')) ? 'selected' : ''; ?>>San Nicolas</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'San Roque')) ? 'selected' : ''; ?>>San Roque</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'San Sebastian')) ? 'selected' : ''; ?>>San Sebastian</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'Santa Catalina')) ? 'selected' : ''; ?>>Santa Catalina</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'Santa Cruz Pambilog')) ? 'selected' : ''; ?>>Santa Cruz Pambilog</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'Santa Cruz Poblacion')) ? 'selected' : ''; ?>>Santa Cruz Poblacion</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'Santa Lucia')) ? 'selected' : ''; ?>>Santa Lucia</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'Santa Monica')) ? 'selected' : ''; ?>>Santa Monica</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'Santa Rita')) ? 'selected' : ''; ?>>Santa Rita</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'Santo Niño')) ? 'selected' : ''; ?>>Santo Niño</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'Santo Rosario')) ? 'selected' : ''; ?>>Santo Rosario</option>
                                <option <?php echo ($editing && (($edit_application['barangay'] ?? '') === 'Santo Tomas')) ? 'selected' : ''; ?>>Santo Tomas</option>
                            </select>
                        </div>
                        <div>
                            <label class="scholarship-form-label">Municipality</label>
                            <input type="text" name="municipality" class="scholarship-form-input" value="San Luis" readonly />
                        </div>

                    </div>
                    <div class="scholarship-form-submit" style="margin-top: 32px;">
                        <button type="button" class="scholarship-form-btn" id="nextBtn" style="background: #1e88e5;">Next</button>
                    </div>
                </div>

                <!-- Page 2: Requirements -->
                <div class="form-page" id="page2" style="display: none;">
                    <h3 style="text-align: center; margin-bottom: 24px; color: #293D82; font-size: 1.3rem;">Requirements</h3>
                    <div class="scholarship-form-requirements">
                        <div class="scholarship-form-req-content">
                            <div class="scholarship-form-req-list">
                                <div class="scholarship-form-req-item">
                                    <label class="scholarship-form-upload-box">
                                        <div class="upload-preview">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Upload</span>
                                        </div>
                                        <input type="file" name="cor_coe_file" accept="image/*,.pdf" <?php echo $editing ? '' : 'required'; ?>>
                                    </label>
                                    <i class="fas fa-file-alt" style="font-size:20px;color:#1e88e5;"></i>
                                    <div class="scholarship-form-req-item-content">
                                        <span>Certificate of Registration (COR) / Certificate of Enrollment (COE) / Assessment Form 1st Semester</span>
                                    </div>
                                </div>
                                <div class="scholarship-form-req-item">
                                    <label class="scholarship-form-upload-box">
                                        <div class="upload-preview">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Upload</span>
                                        </div>
                                        <input type="file" name="cert_grades_file" accept="image/*,.pdf" <?php echo $editing ? '' : 'required'; ?>>
                                    </label>
                                    <i class="fas fa-file-alt" style="font-size:20px;color:#1e88e5;"></i>
                                    <div class="scholarship-form-req-item-content">
                                        <span>2nd Semester Certificate of Grades</span>
                                    </div>
                                </div>
                                <div class="scholarship-form-req-item">
                                    <label class="scholarship-form-upload-box">
                                        <div class="upload-preview">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Upload</span>
                                        </div>
                                        <input type="file" name="barangay_indigency_file" accept="image/*,.pdf" <?php echo $editing ? '' : 'required'; ?>>
                                    </label>
                                    <i class="fas fa-file-alt" style="font-size:20px;color:#1e88e5;"></i>
                                    <div class="scholarship-form-req-item-content">
                                        <span>Original Barangay Indigency of Student</span>
                                    </div>
                                </div>
                                <div class="scholarship-form-req-item">
                                    <label class="scholarship-form-upload-box">
                                        <div class="upload-preview">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Upload</span>
                                        </div>
                                        <input type="file" name="voters_cert_file" accept="image/*,.pdf" <?php echo $editing ? '' : 'required'; ?>>
                                    </label>
                                    <i class="fas fa-file-alt" style="font-size:20px;color:#1e88e5;"></i>
                                    <div class="scholarship-form-req-item-content">
                                        <span>Voters Certification</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="scholarship-form-submit" style="margin-top: 32px; display: flex; gap: 12px; justify-content: center;">
                        <button type="button" class="scholarship-form-btn" id="prevBtn" style="background: #888;">Previous</button>
                        <button type="submit" class="scholarship-form-btn" id="continueBtn" style="background: #ff3fa4;">Submit Application</button>
                    </div>
                </div>
            </form>
        </div>
    </section>


    <script>
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {

            // Page navigation with criteria modal
            const page1 = document.getElementById('page1');
            const page2 = document.getElementById('page2');
            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            const criteriaModal = document.getElementById('criteriaModal');
            const criteriaOverlay = document.getElementById('criteriaOverlay');
            const acknowledgeCriteria = document.getElementById('acknowledgeCriteria');

            function openCriteriaModal() {
                if (criteriaModal) {
                    criteriaModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            }

            function closeCriteriaModal() {
                if (criteriaModal) {
                    criteriaModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }

            function showPage2() {
                page1.style.display = 'none';
                page2.style.display = 'block';
                window.scrollTo(0, 0);
                openCriteriaModal();
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    // Validate page 1 form
                    const form = document.querySelector('.scholarship-form-form');
                    let isValid = true;

                    // Clear previous error states
                    const allInputs = form.querySelectorAll('input[type="text"], input[type="date"], select');
                    allInputs.forEach(input => {
                        input.classList.remove('error');
                    });

                    // Check required fields and add error class
                    allInputs.forEach(input => {
                        if (!input.value.trim()) {
                            input.classList.add('error');
                            isValid = false;
                        }
                    });

                    if (!isValid) {
                        alert('Please fill in all required fields.');
                        return;
                    }

                    // Check if sex is selected
                    const sexRadios = document.querySelectorAll('input[name="sex"]');
                    const sexSelected = Array.from(sexRadios).some(radio => radio.checked);
                    if (!sexSelected) {
                        alert('Please select your sex.');
                        return;
                    }

                    showPage2();
                });
            }

            // Add event listeners to remove error class when user starts typing
            const allInputFields = document.querySelectorAll('.scholarship-form-input, .scholarship-form-select');
            allInputFields.forEach(field => {
                field.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.remove('error');
                    }
                });
                field.addEventListener('change', function() {
                    if (this.value.trim()) {
                        this.classList.remove('error');
                    }
                });
            });

            if (acknowledgeCriteria) {
                acknowledgeCriteria.addEventListener('click', function() {
                    closeCriteriaModal();
                });
            }

            if (criteriaOverlay) {
                criteriaOverlay.addEventListener('click', function() {
                    closeCriteriaModal();
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    page2.style.display = 'none';
                    page1.style.display = 'block';
                    window.scrollTo(0, 0);
                });
            }

            // File upload preview handlers
            const uploadInputs = document.querySelectorAll('.scholarship-form-upload-box input');
            uploadInputs.forEach(input => {
                const label = input.closest('.scholarship-form-upload-box');
                const preview = label.querySelector('.upload-preview');
                input.addEventListener('change', function() {
                    const file = this.files && this.files[0];
                    if (!file) return;
                    if (file.type && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.innerHTML = '<img src="' + e.target.result + '" alt="preview">';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.innerHTML = '<div class="file-name">' + file.name + '</div>';
                    }
                });
                // double-click to clear selection
                label.addEventListener('dblclick', function() {
                    input.value = '';
                    preview.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Upload</span>';
                });
            });

            // Escape key handler for criteria modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && criteriaModal && criteriaModal.classList.contains('active')) {
                    closeCriteriaModal();
                }
            });

            // Continue button - submit form
            const continueBtn = document.getElementById('continueBtn');
            const form = document.querySelector('.scholarship-form-form');
            if (continueBtn && form) {
                continueBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    // Validate all required files are uploaded
                    const requiredFiles = ['cor_coe_file', 'cert_grades_file', 'barangay_indigency_file', 'voters_cert_file'];
                    let allFilesUploaded = true;

                    requiredFiles.forEach(fieldName => {
                        const fileInput = form.querySelector(`input[name="${fieldName}"]`);
                        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                            allFilesUploaded = false;
                        }
                    });

                    if (!allFilesUploaded) {
                        alert('Please upload all required documents before submitting.');
                        return;
                    }

                    // Show confirmation before submit
                    const confirmModal = document.getElementById('confirmModal');
                    if (confirmModal) {
                        confirmModal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }
                });
            }
        });
    </script>

    <?php include 'includes/footer.php'; ?>
    <script src="includes/script.js"></script>

    <!-- Document Criteria Modal -->
    <div id="criteriaModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="criteriaTitle">
        <div id="criteriaOverlay" class="modal-overlay"></div>
        <div class="modal-content" style="max-width: 600px; font-size: 14px;">
            <h2 id="criteriaTitle" style="margin: 0 0 16px; font-size: 22px; color: #293D82;">Criteria is for Document uploading</h2>
            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #ff3fa4;">
                <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
                    <li>Make sure the contents are visible</li>
                    <li>Make sure its authentic</li>
                    <li>Make sure it is original copy</li>
                    <li>Xerox copy is invalid</li>
                    <li>Make sure the official seals of school/barangay/institute are visible</li>
                    <li>Make sure the signature are visible</li>
                </ul>
            </div>
            <div style="display:flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="scholarship-form-btn" id="acknowledgeCriteria" style="background:#1e88e5; padding: 10px 30px;">I Understand</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div id="confirmOverlay" class="modal-overlay"></div>
        <div class="modal-content" style="max-width: 520px; font-size: 14px;">
            <h2 id="confirmTitle" style="margin: 0 0 12px; font-size: 24px;">Submit Application?</h2>
            <p style="color:#555; margin-bottom: 18px;">Are you sure you want to submit your scholarship application? Please review your information before submitting.</p>
            <div style="display:flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="close-btn" id="cancelConfirm">Cancel</button>
                <button type="button" class="apply-btn" id="proceedConfirm" style="background:#293D82; color:#fff;">Submit</button>
            </div>
        </div>
    </div>

    <script>
        // Confirmation modal behavior for submission
        (function() {
            const confirmModal = document.getElementById('confirmModal');
            const cancelConfirmBtn = document.getElementById('cancelConfirm');
            const proceedConfirmBtn = document.getElementById('proceedConfirm');
            const confirmOverlay = document.getElementById('confirmOverlay');
            const form = document.querySelector('.scholarship-form-form');

            function closeConfirmModal() {
                if (confirmModal) {
                    confirmModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }

            if (cancelConfirmBtn) {
                cancelConfirmBtn.addEventListener('click', function() {
                    closeConfirmModal();
                });
            }
            if (confirmOverlay) {
                confirmOverlay.addEventListener('click', function() {
                    closeConfirmModal();
                });
            }
            if (proceedConfirmBtn && form) {
                proceedConfirmBtn.addEventListener('click', function() {
                    closeConfirmModal();
                    form.submit();
                });
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && confirmModal && confirmModal.classList.contains('active')) {
                    closeConfirmModal();
                }
            });
        })();
    </script>
</body>

</html>