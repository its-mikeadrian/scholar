<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
require_once __DIR__ . '/../../src/db.php';

// Fetch announcements from database
$announcements = [];
try {
    $stmt = $conn->prepare('SELECT id, title, content, image_path, user_id, created_at FROM announcements ORDER BY created_at DESC');
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result) {
        $announcements = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
} catch (Exception $e) {
    error_log('Error fetching announcements: ' . $e->getMessage());
    $announcements = [];
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

        .hero {
            background: linear-gradient(#1e88e59d,rgba(25, 38, 85, 0.92)), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'), url('img/sanluis.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 0;
            margin-bottom: 60px;
            margin-top: 90px;
        }

        .hero h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 3.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto 40px;
            opacity: 0.9;
        }

        .hero-btn {
            background-color: #ffeb3b;
            color: var(--dark);
            border: none;
            padding: 18px 45px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        .hero-btn:hover {
            background-color: #ffd740;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
        }

        /* Announcement Section - Matching vaccination card style - NOW 90% WIDTH */
        .announcement {
            background-color: white;
            border-radius: 15px;
            padding: 40px;
            margin: 0 auto 60px;
            box-shadow: var(--shadow);
            border-top: 8px solid var(--primary);
            width: 90%;
            max-width: 1200px;
        }

        .section-title {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary-dark);
            font-size: 2.2rem;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background-color: var(--primary);
        }

        .announcement-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .announcement-card {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            transition: var(--transition);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border-left: 5px solid var(--primary);
            position: relative;
        }

        .announcement-clickable {
            cursor: pointer;
            overflow: hidden;
        }

        .announcement-clickable::before {
            content: '\f065';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            font-size: 3rem;
            color: white;
            z-index: 10;
            transition: transform 0.3s ease;
        }

        .announcement-clickable::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(30, 136, 229, 0.15);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 5;
            border-radius: 10px;
        }

        .announcement-clickable:hover {
            box-shadow: 0 12px 30px rgba(30, 136, 229, 0.25);
            transform: translateY(-2px);
        }

        .announcement-clickable:hover::before {
            transform: translate(-50%, -50%) scale(1);
        }

        .announcement-clickable:hover::after {
            opacity: 1;
        }



        .announcement-card h3 {
            color: var(--primary-dark);
            font-size: 1.5rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .announcement-card h3 i {
            color: var(--primary);
        }

        .highlight-box {
            background-color: #e8f5e9;
            border-radius: 10px;
            padding: 25px;
            margin-top: 25px;
            border-left: 5px solid var(--secondary);
        }

        .highlight-box p {
            font-style: italic;
            color: var(--dark);
            font-size: 1.1rem;
        }

        /* Program Info Section - Matching vaccination details style */
        .program-info {
            margin-bottom: 60px;
        }

        .program-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .program-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-top: 8px solid var(--primary-d);
        }

        .program-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 25px;
            text-align: center;
        }

        .card-header i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }

        .card-body {
            padding: 25px;
        }

        .card-body ul {
            list-style-type: none;
        }

        .card-body li {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .card-body li i {
            color: var(--secondary);
            margin-top: 5px;
        }

        /* Schedule Update Section - Matching vaccination location style */
        .schedule-update {

            border-top: 8px solid var(--secondary);
            background-color: white;
            border-radius: 15px;
            padding-top: 30px;
            padding-bottom: 30px;
            margin: 0 auto 60px;
            box-shadow: var(--shadow);
            width: 90%;
            max-width: 1200px;
        }


        .schedule-box {
            background-color: white;
            border-radius: 10px;
            padding: 30px;
            margin-top: 25px;

            box-sizing: border-box;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .schedule-box p {
            font-size: 1.2rem;
            line-height: 1.8;
            color: #555;
        }

        .important-note {
            background-color: #fff8e1;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
            border-left: 5px solid var(--accent);
        }

        .important-note h4 {
            color: var(--accent);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* CTA Section - Hero-like background boxed */
        .cta-section {
            background: linear-gradient(#1e88e59d,rgba(25, 38, 85, 0.92)), url('https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80'), url('img/sanluis.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 0;   
            margin: 0 auto;
            border-radius: 15px;
            margin-bottom: 60px;
            box-shadow: var(--shadow);
            width: 90%;
            max-width: 1200px;
        }

        .cta-section h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 3rem;
            margin-bottom: 20px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .cta-section p {
            font-size: 1.3rem;
            max-width: 800px;
            margin: 0 auto 40px;
            opacity: 0.9;
        }


        /* Detail Items - Matching vaccination detail items */
        .detail-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            border-top: 8px solid var(--secondary);
        }

        .detail-icon {
            background-color: #e3f2fd;
            color: var(--primary);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.5rem;
        }

        /* Mission & Vision section styles */
        .mission-vision {
            background-color: white;
            border-radius: 15px;
            padding: 40px;
            margin: 40px auto;
            box-shadow: var(--shadow);
            border-top: 8px solid var(--primary);
            width: 90%;
            max-width: 1200px;
        }

        .mv-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 20px;
            align-items: start;
        }

        .mv-card h3 {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary-dark);
            font-size: 1.6rem;
            margin-bottom: 12px;
        }

        .mv-card p {
            color: #444;
            line-height: 1.7;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .mv-grid {
                grid-template-columns: 1fr;
                text-align: left;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 900px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-height: 85vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover,
        .close:focus {
            color: var(--primary);
        }

        .modal-title {
            font-family: 'Montserrat', sans-serif;
            color: var(--primary-dark);
            font-size: 2rem;
            margin-bottom: 20px;
            margin-top: 20px;
        }

        .modal-announcements {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }

        .see-more-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 20px;
            display: inline-block;
        }

        .see-more-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(30, 136, 229, 0.3);
        }
    </style>
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>ISKOLAR NANG LUIS</h2>
            <p>EDUCATIONAL ASSISTANCE - Empowering the youth through education and financial support for a brighter future.</p>
            <button class="hero-btn" id="heroApplyBtn">
                <i class="fas fa-rocket"></i> APPLY FOR SCHOLARSHIP
            </button>
        </div>
    </section>

    <!-- Announcement Section - NOW 90% WIDTH -->
    <div class="announcement">
        <div class="section-title">ANNOUNCEMENT</div>
        <div class="announcement-content">
            <?php if (empty($announcements)): ?>
                <div style="grid-column: 1/-1; padding: 40px; text-align: center; color: #999;">
                    <p>No announcements yet</p>
                </div>
            <?php else: ?>
                <!-- Show up to 2 most recent announcements -->
                <?php $displayCount = min(2, count($announcements)); ?>
                <?php for ($i = 0; $i < $displayCount; $i++): ?>
                    <?php $announcement = $announcements[$i]; ?>
                    <div class="announcement-card fade-in announcement-clickable" onclick="viewAnnouncementDetail(<?php echo htmlspecialchars(json_encode($announcement)); ?>)">
                        <?php if (!empty($announcement['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars('../' . $announcement['image_path']); ?>" alt="<?php echo htmlspecialchars($announcement['title']); ?>" style="width: 100%; border-radius: 10px; max-height: 250px; object-fit: cover; margin-bottom: 15px;">
                        <?php endif; ?>
                        <p style="color: #999; font-size: 0.85rem; margin-bottom: 10px;">
                            <strong>Date:</strong> <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                        </p>
                        <h3><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($announcement['title']); ?></h3>
                        
                        <p style="margin-bottom: 15px; color: #555; font-size: 0.95rem; line-height: 1.5;">
                            <?php 
                            $content = htmlspecialchars($announcement['content']);
                            echo strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                            ?>
                        </p>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
        
        <?php if (count($announcements) > 2): ?>
            <div style="text-align: center; margin-top: 25px;">
                <button class="see-more-btn" id="seeMoreBtn">
                    <i class="fas fa-eye"></i> See All Announcements (<?php echo count($announcements) - 2; ?> more)
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal for other announcements -->
    <div id="announcementModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">More Announcements</h2>
            <div class="modal-announcements">
                <?php if (count($announcements) > 1): ?>
                    <?php for ($i = 1; $i < count($announcements); $i++): ?>
                        <?php $announcement = $announcements[$i]; ?>
                        <div class="announcement-card fade-in announcement-clickable" onclick="viewAnnouncementDetail(<?php echo htmlspecialchars(json_encode($announcement)); ?>)">
                            <?php if (!empty($announcement['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars('../' . $announcement['image_path']); ?>" alt="<?php echo htmlspecialchars($announcement['title']); ?>" style="width: 100%; border-radius: 10px; max-height: 250px; object-fit: cover; margin-bottom: 15px;">
                                <p style="color: #999; font-size: 0.9rem; margin-bottom: 15px;">
                               <!-- <strong>Posted by User ID:</strong> <?php echo htmlspecialchars($announcement['user_id']); ?> • --> 
                                <strong>Date:</strong> <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                            </p>
                            <?php endif; ?>
                            <h3><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($announcement['title']); ?></h3>
                            
                            <p style="margin-bottom: 15px;"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                        </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal for viewing announcement detail -->
    <div id="announcementDetailModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close close-detail">&times;</span>
            <div id="announcementDetailContent">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Program Information -->
    <section id="requirements" class="program-info">
        <div class="container">
            <h2 class="section-title">SCHOLARSHIP PROGRAM DETAILS</h2>

            <div class="detail-items">
                <div class="detail-item fade-in">
                    <div class="detail-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <h3>Eligibility Requirements</h3>
                        <p>Residents of Luis municipality, currently enrolled students with satisfactory academic standing and demonstrated financial need.</p>
                    </div>
                </div>

                <div class="detail-item fade-in delay-1">
                    <div class="detail-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <div>
                        <h3>Comprehensive Benefits</h3>
                        <p>Tuition assistance, monthly stipend, book allowance, transportation support, and mentorship programs.</p>
                    </div>
                </div>

                <div class="detail-item fade-in delay-2">
                    <div class="detail-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <h3>Document Requirements</h3>
                        <p>Application form, certificate of enrollment, grade transcript, residency proof, income documents, and personal essay.</p>
                    </div>
                </div>
            </div>

            <div class="program-cards">
                <div class="program-card fade-in">
                    <div class="card-header">
                        <i class="fas fa-university"></i>
                        <h3>Eligibility</h3>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li><i class="fas fa-check-circle"></i> Resident of Luis municipality</li>
                            <li><i class="fas fa-check-circle"></i> Currently enrolled in an accredited institution</li>
                            <li><i class="fas fa-check-circle"></i> Maintains a satisfactory academic standing</li>
                            <li><i class="fas fa-check-circle"></i> Demonstrated financial need</li>
                            <li><i class="fas fa-check-circle"></i> Active in community service or extracurricular activities</li>
                        </ul>
                    </div>
                </div>

                <div class="program-card fade-in delay-1">
                    <div class="card-header">
                        <i class="fas fa-award"></i>
                        <h3>Benefits</h3>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li><i class="fas fa-check-circle"></i> Tuition and miscellaneous fee assistance</li>
                            <li><i class="fas fa-check-circle"></i> Monthly stipend for educational expenses</li>
                            <li><i class="fas fa-check-circle"></i> Book and learning material allowance</li>
                            <li><i class="fas fa-check-circle"></i> Transportation assistance</li>
                            <li><i class="fas fa-check-circle"></i> Mentorship and career guidance programs</li>
                        </ul>
                    </div>
                </div>

                <div class="program-card fade-in delay-2">
                    <div class="card-header">
                        <i class="fas fa-file-alt"></i>
                        <h3>Requirements</h3>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li><i class="fas fa-check-circle"></i> Certificate of Registration (COR) / Certificate of Enrollment (COE) / Assessment Form — 1st Semester</li>
                            <li><i class="fas fa-check-circle"></i> Form 137 (certified true copy with dry seal from school)</li>
                            <li><i class="fas fa-check-circle"></i> 1st/2nd Semester Certificate of Grades</li>
                            <li><i class="fas fa-check-circle"></i> Voters Certification</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Schedule Update -->
    <section class="schedule-update">
        <div class="container">
            <h2 class="section-title">NEW SCHEDULE UPDATES</h2>
            <div class="schedule-box">
                <p><strong>PAUMANHIN PO SA MAGKAKAROON NG PAG BABAGO SA SHEDULE NG DISTRIBUTION OF EDUCATIONAL ASSISTANCE UPANG BIGYAN NG DAAN ANG PAYOUT PARA SA ATING MGA MAHAL NA SENIOR CITIZENS.</strong></p>


                <div class="important-note">
                    <h4><i class="fas fa-exclamation-circle"></i> Important Notice</h4>
                    <p>Due to recent adjustments in our processing system, we have updated the distribution schedule for educational assistance. All applicants will be notified of their specific schedule via email and SMS. Please ensure your contact information is up to date in your application.</p>
                </div>

                <div class="detail-items" style="margin-top: 30px;">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <h3>Application Deadline</h3>
                            <p>April 15, 2023 - All applications must be submitted by this date</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3>Processing Time</h3>
                            <p>Applications will be processed within 4-6 weeks after submission</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2>READY TO APPLY?</h2>
            <p>Join hundreds of students who have benefited from the Iskolar Nang Luis scholarship program. Take the first step towards achieving your academic dreams with financial support tailored to your needs.</p>
            <button class="hero-btn" id="ctaApplyBtn">
                <i class="fas fa-paper-plane"></i> SUBMIT YOUR APPLICATION NOW
            </button>
        </div>
    </section>

    <section id="mission-vision" class="mission-vision">
        <div class="container">
            <h2 class="section-title">MISSION & VISION</h2>
            <div class="mv-grid">
                <div class="mv-card">
                    <h3>Mission</h3>
                    <p>To improve the quality of life of the citizenry through efficient and effective delivery of basic services, strengthening people’s organization and improvement of revenue and investments towards a safe and progressive community.</p>
                </div>

                <div class="mv-card">
                    <h3>Vision</h3>
                    <p>An agricultural community in a competitive economy with God-loving, resilient, and empowered citizenry living in a peaceful and healthy environment under a competent leadership.</p>
                </div>
            </div>
        </div>
    </section>


    <?php include 'includes/footer.php'; ?>
    <script src="includes/script.js"></script>

    <script>
        // Utility function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Helper function to convert newlines to <br> tags
        function nl2br(text) {
            return text.replace(/\n/g, '<br>');
        }

        // Modal functionality
        const modal = document.getElementById('announcementModal');
        const detailModal = document.getElementById('announcementDetailModal');
        const seeMoreBtn = document.getElementById('seeMoreBtn');
        const closeBtn = document.querySelector('.close');
        const closeDetailBtn = document.querySelector('.close-detail');

        if (seeMoreBtn) {
            seeMoreBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                modal.style.display = 'block';
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }

        if (closeDetailBtn) {
            closeDetailBtn.addEventListener('click', function() {
                detailModal.style.display = 'none';
            });
        }

        // Close modals when clicking outside of them
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
            if (event.target === detailModal) {
                detailModal.style.display = 'none';
            }
        });

        // Function to view announcement detail
        function viewAnnouncementDetail(announcement) {
            const detailContent = document.getElementById('announcementDetailContent');
            const imageHtml = announcement.image_path ? `
                <img src="../${announcement.image_path}" alt="${escapeHtml(announcement.title)}" style="width: 100%; border-radius: 10px; max-height: 500px; object-fit: cover; margin-bottom: 20px;">
            ` : '';
            
            const date = new Date(announcement.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            
            detailContent.innerHTML = `
                <div style="text-align: center;">
                    ${imageHtml}
                    <h2 style="color: #293D82; font-size: 2rem; margin-bottom: 10px; text-align: left;">${escapeHtml(announcement.title)}</h2>
                    <p style="color: #999; font-size: 0.95rem; margin-bottom: 20px; text-align: left;">
                        <strong>Date:</strong> ${date}
                    </p>
                    <div style="text-align: left; line-height: 1.8; color: #333; font-size: 1.05rem;">
                        ${nl2br(escapeHtml(announcement.content))}
                    </div>
                </div>
            `;
            detailModal.style.display = 'block';
        }
    </script>
