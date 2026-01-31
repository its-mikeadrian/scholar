<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
require_once __DIR__ . '/../../src/db.php';

enforce_student_profile_completed($conn);

// Fetch user's application data
$user_id = auth_user_id();
$application = null;
$application_status = 'pending';

if ($user_id) {
    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE user_id = ? ORDER BY submission_date DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($application) {
            $application_status = $application['status'];
        }
    } catch (Exception $e) {
        error_log('Error fetching application: ' . $e->getMessage());
    }
}

// Helper function to format date
function format_date($date_string) {
    if (empty($date_string)) return 'N/A';
    $date = new DateTime($date_string);
    return $date->format('F d, Y');
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
    <link rel="stylesheet" href="includes/footer.css">
    <link rel="stylesheet" href="includes/header.css">
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
            height: 100%;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        .container {
            width: calc(100% - 0px);
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            box-sizing: border-box;
        }


        .tracker-container {
            background: white;
            border-radius: 24px;
            padding: 32px 48px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            width: 100%;
        }

        .progress-tracker {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .progress-line {
            position: absolute;
            top: 28px;
            left: 0;
            right: 0;
            height: 3px;
            background: #e0e0e0;
            z-index: 0;
        }

        .progress-line-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            background: linear-gradient(90deg, #e91e63 0%, #ec407a 100%);
            width: 33.33%;
            transition: width 0.4s ease;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
            flex: 1;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .step:hover .step-icon {
            transform: scale(1.1);
        }

        .step:active {
            transform: scale(0.95);
        }

        .step-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            position: relative;
        }

        .step-number,
        .checkmark {
            position: absolute;
            transition: all 0.3s ease;
        }

        .step-number {
            opacity: 1;
            transform: scale(1);
        }

        .checkmark {
            opacity: 0;
            transform: scale(0);
            font-size: 28px;
        }

        .step.completed .step-number {
            opacity: 0;
            transform: scale(0);
        }

        .step.completed .checkmark {
            opacity: 1;
            transform: scale(1);
        }

        .step.completed .step-icon {
            background: linear-gradient(135deg, #e91e63 0%, #ec407a 100%);
            color: white;
        }

        .step.active .step-icon {
            background: linear-gradient(135deg, #e91e63 0%, #ec407a 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(233, 30, 99, 0.4);
        }

        .step.pending .step-icon {
            background: #f0f0f0;
            color: #999;
        }

        .step-label {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            text-align: center;
        }

        .step.pending .step-label {
            color: #999;
        }

        .checkmark {
            font-size: 20px;
        }

        @media (max-width: 600px) {
            .logo-text h1 {
                font-size: 1rem;
            }

            .tracker-container {
                padding: 24px 20px;
                margin-top: 10px;
            }

            .step-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
            }

            .step-label {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .logo-text h1 {
                font-size: 0.9rem;
            }

            .tracker-container {
                padding: 20px 12px;
                margin-top: 10px;
            }

            .step-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }

            .step-label {
                font-size: 10px;
                max-width: 70px;
            }

            .progress-line {
                top: 20px;
            }
        }

        /* Submitted card */
        .submitted-card {
            margin-top: 22px;
        }

        .submitted-card .card-inner {
            background: white;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
        }

        .submitted-card h3 {
            font-size: 1.05rem;
            margin-bottom: 14px;
            font-weight: 800;
        }

        .form-grid {
            display: flex;
            gap: 22px;
            align-items: flex-start;
        }

        .form-grid .col {
            flex: 1;
        }

        .form-grid .address-col {
            flex: 1.2;
        }

        .muted {
            display: block;
            color: #666;
            font-size: 0.86rem;
            margin-top: 8px;
        }

        .value {
            font-size: 0.98rem;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .docs-row {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }

        .doc-placeholder {
            width: 84px;
            height: 110px;
            background: #e6e6e6;
            border-radius: 6px;
            box-shadow: inset 0 0 0 4px #ddd;
        }

        @media (max-width:950px) {
            .tracker-step {
                min-width: unset;
                justify-content: center;
                flex-direction: column;
                padding: 8px;
            }

            .tracker-step .step-label {
                font-size: 0.82rem;
                text-align: center;
            }

            .progress-line-bg {
                left: 40px;
                right: 40px;
            }

            .progress-line-container {
                height: 3px;
            }
        }



        /* Tabs for Submitted / Renewal */
        .tabs {
            margin-top: 16px;
        }

        .tab-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 14px;
        }

        .tab-button {
            background: #fff;
            border-radius: 30px;
            padding: 8px 18px;
            border: 1px solid #e6e6e6;
            font-weight: 700;
            cursor: pointer;
            color: #555;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .tab-button[aria-selected="true"] {
            background: linear-gradient(90deg, #ffebf7, #fff);
            color: #d81b60;
            border-color: #ffb6d8;
            transform: translateY(-2px);
        }

        .tab-panels {
            background: transparent;
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        @media (max-width:600px) {
            .tab-buttons {
                gap: 8px;
            }

            .tab-button {
                padding: 8px 12px;
                font-size: 0.92rem;
            }
        }

        /* Document Modal Styles */
        .document-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .document-modal.active {
            display: flex;
        }

        .document-modal-content {
            background: white;
            border-radius: 12px;
            max-width: 90%;
            max-height: 90vh;
            position: relative;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
        }

        .document-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: #f9f9f9;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .document-modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }

        .zoom-controls-footer {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
            background: transparent;
        }

        .document-modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #666;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .document-modal-close:hover {
            color: #d32f2f;
            transform: scale(1.2);
        }

        .document-modal-body {
            padding: 20px;
            text-align: center;
            overflow-y: auto;
            overflow-x: auto;
            flex: 1;
            position: relative;
        }

        .document-modal-body img {
            cursor: grab;
            user-select: none;
            display: block;
            margin: 0 auto;
            max-width: 100%;
            height: auto;
            object-fit: contain;
            transition: transform 0.2s ease;
        }
        
        .document-modal-body img.zoomed {
            cursor: grabbing;
        }

        .document-modal-body img.dragging {
            cursor: grabbing;
        }

        .zoom-controls {
            position: absolute;
            top: 20px;
            right: 60px;
            z-index: 20;
            display: flex;
            gap: 8px;
        }

        .zoom-btn {
            background: rgba(30, 136, 229, 0.8);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 18px;
        }

        .zoom-btn:hover {
            background: #1565c0;
            transform: scale(1.1);
        }

        .zoom-level {
            background: transparent;
            color: #333;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            min-width: 50px;
        }

        .document-modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            background: transparent;
            text-align: center;
            flex-shrink: 0;
            position: sticky;
            bottom: 0;
            z-index: 10;
        }

        .document-modal-download {
            background: #1e88e5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .document-modal-download:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }

        .zoom-controls-footer {
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: center;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0 0 12px 12px;
            flex-shrink: 0;
            position: sticky;
            bottom: 0;
            z-index: 10;
        }

        .zoom-btn-footer {
            background: rgba(30, 136, 229, 0.6);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 18px;
            backdrop-filter: blur(4px);
        }

        .zoom-btn-footer:hover {
            background: rgba(30, 136, 229, 0.8);
            transform: scale(1.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .zoom-level-footer {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            min-width: 60px;
            text-align: center;
            backdrop-filter: blur(4px);
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <!-- Main content: Application Tracker + Submitted Form -->
    <main style="padding: 120px 0 40px;">
        <div class="container">

            <!-- Centered Application Tracker -->
            <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 32px;">

                <div class="tracker-container">
                    <div class="progress-tracker">
                        <div class="progress-line">
                            <div class="progress-line-fill" id="progressFill"></div>
                        </div>

                        <div class="step completed" onclick="setStep(1)" style="cursor: pointer;">
                            <div class="step-icon">
                                <span class="step-number">1</span>
                                <span class="checkmark">✓</span>
                            </div>
                            <div class="step-label">Submission</div>
                        </div>

                        <div class="step active" onclick="setStep(2)" style="cursor: pointer;">
                            <div class="step-icon">
                                <span class="step-number">2</span>
                                <span class="checkmark">✓</span>
                            </div>
                            <div class="step-label">Under Review</div>
                        </div>

                        <div class="step pending" onclick="setStep(3)" style="cursor: pointer;">
                            <div class="step-icon">
                                <span class="step-number">3</span>
                                <span class="checkmark">✓</span>
                            </div>
                            <div class="step-label">Result</div>
                        </div>

                        <div class="step pending" onclick="setStep(4)" style="cursor: pointer;">
                            <div class="step-icon">
                                <span class="step-number">4</span>
                                <span class="checkmark">✓</span>
                            </div>
                            <div class="step-label">Disbursement</div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- Tabbed forms: Submitted / Renewal -->
            <section class="submitted-card fade-in delay-2">
                <div class="card-inner">
                    <div class="tabs">
                        <div class="tab-buttons" role="tablist" aria-label="Application tabs">
                            <button class="tab-button" role="tab" aria-selected="true" data-target="panel-submitted" id="tab-submitted">Submitted Form</button>
                            <button class="tab-button" role="tab" aria-selected="false" data-target="panel-renewal" id="tab-renewal">Renewal Form</button>
                        </div>

                        <div class="tab-panels">
                            <div id="panel-submitted" class="tab-panel active" role="tabpanel" aria-labelledby="tab-submitted">
                                <h3>Submitted Form</h3>
                                <?php if ($application): ?>
                                <div class="form-grid">
                                    <div class="col">
                                        <label class="muted">Academic Level:</label>
                                        <div class="value"><?php echo htmlspecialchars($application['academic_level'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($application['semester'] ?? 'N/A'); ?></div>

                                        <label class="muted">Name:</label>
                                        <div class="value"><strong><?php echo htmlspecialchars($application['last_name'] ?? ''); ?>, <?php echo htmlspecialchars($application['first_name'] ?? ''); ?> <?php echo htmlspecialchars($application['middle_name'] ?? ''); ?></strong></div>

                                        <label class="muted">Mother's Maiden Name:</label>
                                        <div class="value"><?php echo htmlspecialchars($application['mothers_maiden_name'] ?? 'N/A'); ?></div>

                                        <label class="muted">Father's Name:</label>
                                        <div class="value"><?php echo htmlspecialchars($application['fathers_name'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="col">
                                        <label class="muted">Age:</label>
                                        <div class="value"><?php echo htmlspecialchars($application['age'] ?? 'N/A'); ?></div>

                                        <label class="muted">Date of Birth:</label>
                                        <div class="value"><?php echo format_date($application['date_of_birth'] ?? ''); ?></div>

                                        <label class="muted">Sex:</label>
                                        <div class="value"><?php echo htmlspecialchars($application['sex'] ?? 'N/A'); ?></div>

                                        <label class="muted">Cellphone No.:</label>
                                        <div class="value"><?php echo htmlspecialchars($application['cellphone_number'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="col address-col">
                                        <label class="muted">Home Address:</label>
                                        <div class="value"><?php echo htmlspecialchars($application['house_number'] ?? ''); ?> <?php echo htmlspecialchars($application['street_address'] ?? ''); ?> <?php echo htmlspecialchars($application['barangay'] ?? ''); ?> <?php echo htmlspecialchars($application['municipality'] ?? ''); ?></div>

                                        <label class="muted">Submission Date:</label>
                                        <div class="value"><?php echo format_date($application['submission_date'] ?? ''); ?></div>

                                        <label class="muted">Status:</label>
                                        <div class="value" style="text-transform: capitalize; color: <?php echo $application['status'] === 'approved' ? '#2e7d32' : ($application['status'] === 'rejected' ? '#d32f2f' : '#ff9800'); ?>;">
                                            <strong><?php echo htmlspecialchars($application['status'] ?? 'N/A'); ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-top:18px;">
                                    <label class="muted">Documents Submitted:</label>
                                    <div class="docs-row">
                                        <?php 
                                        $documents = [
                                            ['field' => 'cor_coe_file', 'title' => 'COR/COE', 'name' => 'Certificate of Registration'],
                                            ['field' => 'cert_grades_file', 'title' => 'Cert of Grades', 'name' => '2nd Semester Grades'],
                                            ['field' => 'barangay_indigency_file', 'title' => 'Barangay Indigency', 'name' => 'Barangay Indigency'],
                                            ['field' => 'voters_cert_file', 'title' => 'Voters Cert', 'name' => 'Voters Certification']
                                        ];
                                        
                                        foreach ($documents as $doc) {
                                            if (!empty($application[$doc['field']])) {
                                                $file_path = $application[$doc['field']];
                                                $full_url = '../' . htmlspecialchars($file_path);
                                                $is_image = preg_match('/\.(jpg|jpeg|png|gif)$/i', $file_path);
                                                echo '<div class="doc-placeholder" style="display: flex; align-items: center; justify-content: center; cursor: pointer; background: #f0f7ff; border: 2px solid #1e88e5; transition: all 0.3s ease;" onclick="openDocumentModal(\'' . $full_url . '\', \'' . ($is_image ? 'image' : 'pdf') . '\', \'' . htmlspecialchars($doc['name']) . '\')" onmouseover="this.style.background=\'#e3f2fd\'; this.style.boxShadow=\'0 4px 12px rgba(30, 136, 229, 0.3)\';" onmouseout="this.style.background=\'#f0f7ff\'; this.style.boxShadow=\'inset 0 0 0 4px #ddd\'">';
                                                if ($is_image) {
                                                    echo '<img src="' . $full_url . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;" alt="' . htmlspecialchars($doc['name']) . '">';
                                                } else {
                                                    echo '<i class="fas fa-file-pdf" style="color: #d32f2f; font-size: 32px;"></i>';
                                                }
                                                echo '</div>';
                                            } else {
                                                echo '<div class="doc-placeholder" style="background: #f5f5f5; display: flex; align-items: center; justify-content: center;"><span style="color: #999; font-size: 12px; text-align: center;">No file</span></div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                    <p>No application submitted yet.</p>
                                    <a href="<?php echo route_url('students/application'); ?>" style="color: #1e88e5; text-decoration: none; font-weight: 600;">Submit your application</a>
                                </div>
                                <?php endif; ?>

                            </div>

                            <div id="panel-renewal" class="tab-panel" role="tabpanel" aria-labelledby="tab-renewal">
                                <h3>Renewal Form</h3>
                                <?php if ($application): ?>
                                <form id="renewalForm">
                                    <div class="form-grid">
                                        <div class="col">
                                            <label class="muted">Name:</label>
                                            <div class="value"><?php echo htmlspecialchars($application['last_name'] ?? ''); ?>, <?php echo htmlspecialchars($application['first_name'] ?? ''); ?> <?php echo htmlspecialchars($application['middle_name'] ?? ''); ?></div>

                                            <label class="muted">Course / Year:</label>
                                            <input type="text" name="course" placeholder="e.g., BS Computer Science - 2nd Year" style="width:100%; padding:8px; margin-top:6px; border-radius:6px; border:1px solid #ddd;">

                                        </div>
                                        <div class="col">
                                            <label class="muted">Contact No.:</label>
                                            <input type="text" name="contact" placeholder="09012345678" value="<?php echo htmlspecialchars($application['cellphone_number'] ?? ''); ?>" style="width:100%; padding:8px; margin-top:6px; border-radius:6px; border:1px solid #ddd;">

                                            <label class="muted">Upload Grades (optional):</label>
                                            <input type="file" name="grades" style="margin-top:6px;">
                                        </div>
                                        <div class="col address-col">
                                            <label class="muted">Home Address:</label>
                                            <textarea name="address" rows="4" style="width:100%; padding:8px; margin-top:6px; border-radius:6px; border:1px solid #ddd;"><?php echo htmlspecialchars($application['house_number'] ?? ''); ?> <?php echo htmlspecialchars($application['street_address'] ?? ''); ?> <?php echo htmlspecialchars($application['barangay'] ?? ''); ?> <?php echo htmlspecialchars($application['municipality'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <div style="text-align:center; margin-top:14px;">
                                        <button type="submit" class="apply-btn" style="background:#ff6fb2; color:white;">Submit Renewal</button>
                                    </div>
                                </form>
                                <?php else: ?>
                                <div style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                    <p>Renewal form is only available after submitting your initial application.</p>
                                    <a href="<?php echo route_url('students/application'); ?>" style="color: #1e88e5; text-decoration: none; font-weight: 600;">Submit your application</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    <script src="includes/script.js"></script>

    <script>
        // Tabs: Submitted / Renewal
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanels = document.querySelectorAll('.tab-panel');

        function activateTab(button) {
            const target = button.getAttribute('data-target');
            tabButtons.forEach(b => b.setAttribute('aria-selected', 'false'));
            button.setAttribute('aria-selected', 'true');
            tabPanels.forEach(p => p.classList.remove('active'));
            const panel = document.getElementById(target);
            if (panel) panel.classList.add('active');
            // persist
            try {
                localStorage.setItem('app_active_tab', target);
            } catch (e) {}
        }
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => activateTab(btn));
        });
        // restore last tab
        try {
            const saved = localStorage.getItem('app_active_tab');
            if (saved) {
                const btn = document.querySelector(`.tab-button[data-target="${saved}"]`);
                if (btn) activateTab(btn);
            }
        } catch (e) {}

        // handle renewal form submission (demo)
        const renewalForm = document.getElementById('renewalForm');
        if (renewalForm) {
            renewalForm.addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Renewal form submitted (demo).');
            });
        }

        // Application tracker step click
        let currentStep = 2; // Start at step 2 (Under Review)

        // Function to set a specific step
        function setStep(stepNumber) {
            currentStep = stepNumber;
            updateProgress(stepNumber);
        }

        // Function to update the progress display
        function updateProgress(currentStep) {
            const steps = document.querySelectorAll('.step');
            const progressFill = document.getElementById('progressFill');

            // Calculate progress percentage
            const progressPercent = ((currentStep - 1) / (steps.length - 1)) * 100;
            progressFill.style.width = progressPercent + '%';

            // Update step states
            steps.forEach((step, index) => {
                step.classList.remove('completed', 'active', 'pending');

                if (index < currentStep - 1) {
                    step.classList.add('completed');
                } else if (index === currentStep - 1) {
                    step.classList.add('active');
                } else {
                    step.classList.add('pending');
                }
            });
        }

        // Make setStep globally accessible
        window.setStep = setStep;

        // Initialize on page load
        updateProgress(currentStep);

        // Zoom functionality
        let currentZoom = 100;
        const minZoom = 50;
        const maxZoom = 300;
        const zoomStep = 10;

        function zoomIn() {
            if (currentZoom < maxZoom) {
                currentZoom += zoomStep;
                applyZoom();
            }
        }

        function zoomOut() {
            if (currentZoom > minZoom) {
                currentZoom -= zoomStep;
                applyZoom();
            }
        }

        function applyZoom() {
            const img = document.querySelector('.document-modal-body img');
            const zoomLevel = document.getElementById('zoomLevel');
            if (img) {
                img.style.transform = `scale(${currentZoom / 100})`;
            }
            if (zoomLevel) {
                zoomLevel.textContent = currentZoom + '%';
            }
        }

        function resetZoom() {
            currentZoom = 100;
            applyZoom();
        }

        // Make zoom functions globally accessible
        window.zoomIn = zoomIn;
        window.zoomOut = zoomOut;
        window.resetZoom = resetZoom;

        // Image drag functionality
        let isDragging = false;
        let startX, startY, translateX = 0, translateY = 0;

        function startDrag(e) {
            const img = document.querySelector('.document-modal-body img');
            if (!img) return;
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            img.style.cursor = 'grabbing';
        }

        function drag(e) {
            if (!isDragging) return;
            const img = document.querySelector('.document-modal-body img');
            if (!img) return;
            e.preventDefault();
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${currentZoom / 100})`;
        }

        function stopDrag() {
            isDragging = false;
            const img = document.querySelector('.document-modal-body img');
            if (img) {
                img.style.cursor = 'grab';
            }
        }
        
        function openDocumentModal(url, type, title) {
            const modal = document.getElementById('documentModal');
            const modalBody = document.getElementById('documentBody');
            const modalTitle = document.getElementById('documentTitle');

            modalTitle.textContent = title;

            if (type === 'image') {
                modalBody.innerHTML = '<img src="' + url + '" alt="' + title + '">';
                const img = modalBody.querySelector('img');
                if (img) {
                    img.addEventListener('mousedown', startDrag);
                    img.addEventListener('mousemove', drag);
                    img.addEventListener('mouseup', stopDrag);
                    img.addEventListener('mouseleave', stopDrag);
                }
            } else if (type === 'pdf') {
                modalBody.innerHTML = '<iframe src="' + url + '" type="application/pdf"></iframe>';
            }

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            document.addEventListener('mousemove', drag);
            document.addEventListener('mouseup', stopDrag);
        }

        function closeDocumentModal() {
            const modal = document.getElementById('documentModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
            resetZoom();
            isDragging = false;
            translateX = 0;
            translateY = 0;
            document.removeEventListener('mousemove', drag);
            document.removeEventListener('mouseup', stopDrag);
        }

        // Close modal when clicking outside
        document.getElementById('documentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDocumentModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDocumentModal();
            }
        });
    </script>

    <!-- Document Preview Modal -->
    <div id="documentModal" class="document-modal">
        <div class="document-modal-content">
            <div class="document-modal-header">
                <h3 id="documentTitle">Document Preview</h3>
                <div class="zoom-controls-header">
                    <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">−</button>
                    <span class="zoom-level" id="zoomLevel">100%</span>
                    <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">+</button>
                </div>
                <button class="document-modal-close" onclick="closeDocumentModal()">&times;</button>
            </div>
            <div class="document-modal-body" id="documentBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</body>

</html>