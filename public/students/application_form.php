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
            <form class="scholarship-form-form" method="POST" action="<?php echo htmlspecialchars(route_url('students/process-application'), ENT_QUOTES); ?>" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <div class="scholarship-form-grid">
                    <div>
                        <label class="scholarship-form-label">Academic Level</label>
                        <select class="scholarship-form-select">
                            <option>1st Year</option>
                            <option>2nd Year</option>
                            <option>3rd Year</option>
                            <option>4th Year</option>
                        </select>
                    </div>
                    <div>
                        <label class="scholarship-form-label">Semester</label>
                        <input type="text" placeholder="Semester" class="scholarship-form-input" />
                    </div>
                    <input type="text" placeholder="First Name" class="scholarship-form-input" />
                    <input type="text" placeholder="Last Name" class="scholarship-form-input" />
                    <input type="text" placeholder="Middle Name" class="scholarship-form-input" />
                    <input type="date" placeholder="dd/mm/yyyy" class="scholarship-form-input" />
                    <input type="text" placeholder="Age" class="scholarship-form-input" />
                    <input type="text" placeholder="Cellphone Number" class="scholarship-form-input" />
                    <div class="scholarship-form-radio-group">
                        <label class="scholarship-form-label">Sex</label>
                        <input type="radio" id="male" name="sex" value="Male" class="scholarship-form-radio" /> <label for="male" class="scholarship-form-radio-label">Male</label>
                        <input type="radio" id="female" name="sex" value="Female" class="scholarship-form-radio" /> <label for="female" class="scholarship-form-radio-label">Female</label>
                    </div>
                    <input type="text" placeholder="Mother's Maiden Name" class="scholarship-form-input" />
                    <input type="text" placeholder="Occupation" class="scholarship-form-input" />
                    <input type="text" placeholder="Father's Name" class="scholarship-form-input" />
                    <input type="text" placeholder="Occupation" class="scholarship-form-input" />
                    <input type="text" placeholder="Street Address" class="scholarship-form-input" />
                    <input type="text" placeholder="House no./Bldg no." class="scholarship-form-input" />
                    <input type="text" placeholder="Barangay" class="scholarship-form-input" />
                    <input type="text" placeholder="Municipality" class="scholarship-form-input" />
                    <input type="text" placeholder="City/Province" class="scholarship-form-input" />
                    <input type="text" placeholder="Zip Code" class="scholarship-form-input" />
                </div>
                <div class="scholarship-form-requirements">

                    <div class="scholarship-form-req-content">
                        <label class="scholarship-form-req-label" style="display: block; margin-bottom: 16px; font-size: 14px; font-weight: 600;">Requirements:</label>
                        <div class="scholarship-form-req-list">
                            <div class="scholarship-form-req-item">
                                <label class="scholarship-form-upload-box">
                                    <div class="upload-preview">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <span>Upload</span>
                                    </div>
                                    <input type="file" accept="image/*,.pdf">
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
                                    <input type="file" accept="image/*,.pdf">
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
                                    <input type="file" accept="image/*,.pdf">
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
                                    <input type="file" accept="image/*,.pdf">
                                </label>
                                <i class="fas fa-file-alt" style="font-size:20px;color:#1e88e5;"></i>
                                <div class="scholarship-form-req-item-content">
                                    <span>Voters Certification</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scholarship-form-submit">
                    <button type="submit" class="scholarship-form-btn">Continue</button>
                </div>
            </form>
        </div>
    </section>


    <script>
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', function() {

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
        });
    </script>

    <?php include 'includes/footer.php'; ?>
    <script src="includes/script.js"></script>
</body>

</html>