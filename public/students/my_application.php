<?php
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
if (empty($_SESSION['auth_user_id'])) {
    header('Location: ' . route_url('students/login'));
    exit;
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
                                <div class="form-grid">
                                    <div class="col">
                                        <label class="muted">Academic Level:</label>
                                        <div class="value">1st Year - 1st Semester</div>

                                        <label class="muted">Name:</label>
                                        <div class="value"><strong>Dela Cruz, Juan Cruz</strong></div>

                                        <label class="muted">Mother's Maiden Name:</label>
                                        <div class="value">Gina San Juan</div>

                                        <label class="muted">Father's Name:</label>
                                        <div class="value">Pedro Dela Cruz</div>
                                    </div>
                                    <div class="col">
                                        <label class="muted">GWA</label>
                                        <div class="value">1.25</div>

                                        <label class="muted">Age:</label>
                                        <div class="value">21</div>

                                        <label class="muted">Occupation (Mother):</label>
                                        <div class="value">Housewife</div>

                                        <label class="muted">Occupation (Father):</label>
                                        <div class="value">Driver</div>
                                    </div>
                                    <div class="col address-col">
                                        <label class="muted">Home Address:</label>
                                        <div class="value">194 Sto. Nino Street Poblacion Bustos Bulacan</div>

                                        <label class="muted">Cellphone No.:</label>
                                        <div class="value">09012345678</div>
                                    </div>
                                </div>

                                <div style="margin-top:18px;">
                                    <label class="muted">Documents Submitted:</label>
                                    <div class="docs-row">
                                        <div class="doc-placeholder"></div>
                                        <div class="doc-placeholder"></div>
                                        <div class="doc-placeholder"></div>
                                        <div class="doc-placeholder"></div>
                                    </div>
                                </div>


                            </div>

                            <div id="panel-renewal" class="tab-panel" role="tabpanel" aria-labelledby="tab-renewal">
                                <h3>Renewal Form</h3>
                                <form id="renewalForm">
                                    <div class="form-grid">
                                        <div class="col">
                                            <label class="muted">Name:</label>
                                            <div class="value">Dela Cruz, Juan Cruz</div>

                                            <label class="muted">Course / Year:</label>
                                            <input type="text" name="course" placeholder="e.g., BS Computer Science - 2nd Year" style="width:100%; padding:8px; margin-top:6px; border-radius:6px; border:1px solid #ddd;">

                                        </div>
                                        <div class="col">
                                            <label class="muted">Contact No.:</label>
                                            <input type="text" name="contact" placeholder="09012345678" style="width:100%; padding:8px; margin-top:6px; border-radius:6px; border:1px solid #ddd;">

                                            <label class="muted">Upload Grades (optional):</label>
                                            <input type="file" name="grades" style="margin-top:6px;">
                                        </div>
                                        <div class="col address-col">
                                            <label class="muted">Home Address:</label>
                                            <textarea name="address" rows="4" style="width:100%; padding:8px; margin-top:6px; border-radius:6px; border:1px solid #ddd;">194 Sto. Nino Street Poblacion Bustos Bulacan</textarea>
                                        </div>
                                    </div>

                                    <div style="text-align:center; margin-top:14px;">
                                        <button type="submit" class="apply-btn" style="background:#ff6fb2; color:white;">Submit Renewal</button>
                                    </div>
                                </form>
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
    </script>
</body>

</html>