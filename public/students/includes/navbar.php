<?php
// Navbar: show login button when not authenticated
require_once __DIR__ . '/../../../config/bootstrap.php';
require_once __DIR__ . '/../../../src/security.php';
secure_session_start();
$__auth_id = auth_user_id();
require_once __DIR__ . '/../../../src/db.php';
?>
<header>
    <div class="container header-container">
        <div style="display: flex; align-items: center; gap: 10px;">
            <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="mainNav" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div class="logo-icon"></div>
                <div class="logo-text">
                    <h1>ISKOLAR NANG LUIS</h1>
                    <p>MUNICIPALITY OF SAN LUIS, PAMPANGA</p>
                </div>
            </div>
        </div>
        <nav class="nav-links" id="mainNav" aria-label="Main Navigation">
            <a href="#" id="homeBtn">Home</a>
            <a href="#mission-vision" class="scroll-link">About Us</a>
            <a href="#requirements" class="scroll-link">Requirements</a>
            <?php if ($__auth_id): ?>
                <a href="<?php echo htmlspecialchars(route_url('students/application'), ENT_QUOTES); ?>">My Application</a>
            <?php endif; ?>
        </nav>
        <div class="profile-dropdown">
            <?php if ($__auth_id): ?>
                <?php
                $photo = null;
                try {
                    $pstmt = $conn->prepare('SELECT photo_path FROM user_profiles WHERE user_id = ? LIMIT 1');
                    if ($pstmt) {
                        $pstmt->bind_param('i', $__auth_id);
                        $pstmt->execute();
                        $pres = $pstmt->get_result();
                        $prow = $pres ? $pres->fetch_assoc() : null;
                        if ($prow && !empty($prow['photo_path'])) {
                            $photo = $prow['photo_path'];
                        }
                        $pstmt->close();
                    }
                } catch (Throwable $e) {
                }
                ?>
                <div class="user-profile" id="userProfileBtn">
                    <?php if ($photo): ?>
                        <img src="<?php echo htmlspecialchars(asset_url($photo), ENT_QUOTES); ?>" alt="Profile" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" />
                    <?php else: ?>
                        <div style="width:40px;height:40px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#374151;"> <i class="fas fa-user"></i></div>
                    <?php endif; ?>
                </div>
                <div class="dropdown-menu" id="profileDropdown">
                    <a href="<?php echo htmlspecialchars(route_url('students/profile-setup'), ENT_QUOTES); ?>">My Profile</a>
                    <form method="POST" action="<?php echo htmlspecialchars(route_url('logout'), ENT_QUOTES); ?>" style="display:inline;" id="logoutForm">
                        <?php echo csrf_input(); ?>
                        <button type="submit" class="logout">Logout</button>
                    </form>
                </div>
            <?php else: ?>
                <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars(route_url('students/login'), ENT_QUOTES); ?>">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>