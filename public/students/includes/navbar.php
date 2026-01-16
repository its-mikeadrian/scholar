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
            <?php
                // Determine current path and script
                $request_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
                $script_base = basename($_SERVER['SCRIPT_NAME'] ?? '');

                // Detect if we are currently on the Home page (file or routed path)
                $on_home = ($script_base === 'Homepage.php' || preg_match('#/students/home(?:$|/)#', $request_path));

                // Home: on Home page, scroll to top without reload; otherwise navigate to Home route
                if ($on_home) {
                    $home_href = '#';
                    $home_class = '';
                } else {
                    $home_href = htmlspecialchars(route_url('students/home'), ENT_QUOTES);
                    $home_class = '';
                }

                // About/Requirements should be in-page anchors when on Home, otherwise link to Home with fragments
                if ($on_home) {
                    $about_href = '#mission-vision';
                    $req_href = '#requirements';
                    $about_class = 'scroll-link';
                    $req_class = 'scroll-link';
                } else {
                    $about_href = htmlspecialchars(route_url('students/home') . '#mission-vision', ENT_QUOTES);
                    $req_href = htmlspecialchars(route_url('students/home') . '#requirements', ENT_QUOTES);
                    $about_class = '';
                    $req_class = '';
                }
            ?>
            <a href="<?php echo $home_href; ?>" id="homeBtn" class="<?php echo $home_class; ?>">Home</a>
            <a href="<?php echo $about_href; ?>" class="<?php echo $about_class; ?>">About Us</a>
            <a href="<?php echo $req_href; ?>" class="<?php echo $req_class; ?>">Requirements</a>
            <?php if ($__auth_id): ?>
                <a href="<?php echo htmlspecialchars(route_url('students/application'), ENT_QUOTES); ?>">My Application</a>
            <?php endif; ?>
        </nav>

        <?php if (isset($_SESSION['success'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast('<?php echo htmlspecialchars($_SESSION['success'], ENT_QUOTES); ?>', 'success');
                });
            </script>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    showToast('<?php echo htmlspecialchars($_SESSION['error'], ENT_QUOTES); ?>', 'error');
                });
            </script>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="profile-dropdown">
            <?php if ($__auth_id): ?>
                <?php
                $photo = null;
                $userProfile = null;
                try {
                    ensure_student_profiles_table($conn);
                    $pstmt = $conn->prepare('SELECT p.first_name, p.last_name, p.address, p.photo_path, u.username FROM users u LEFT JOIN student_profiles p ON u.id = p.user_id WHERE u.id = ? LIMIT 1');
                    if ($pstmt) {
                        $pstmt->bind_param('i', $__auth_id);
                        $pstmt->execute();
                        $pres = $pstmt->get_result();
                        $userProfile = $pres ? $pres->fetch_assoc() : null;
                        if ($userProfile && !empty($userProfile['photo_path'])) {
                            $photo = $userProfile['photo_path'];
                        }
                        $pstmt->close();
                    }
                } catch (Throwable $e) {
                }
                ?>
                <div class="user-profile" id="userProfileBtn">
                    <?php if ($photo && file_exists(__DIR__ . '/../../' . $photo)): ?>
                        <img src="<?php echo htmlspecialchars(asset_url($photo), ENT_QUOTES); ?>" alt="Profile" style="width:40px;height:40px;border-radius:50%;object-fit:cover;" />
                    <?php else: ?>
                        <div style="width:40px;height:40px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#374151;"> <i class="fas fa-user"></i></div>
                    <?php endif; ?>
                </div>
                <div class="dropdown-menu" id="profileDropdown">
                    <a href="#" id="openProfileModal">My Profile</a>
                    <a href="#" id="History">History</a>
                    <form method="POST" action="<?php echo htmlspecialchars(route_url('students/logout'), ENT_QUOTES); ?>" style="display:inline;" id="logoutForm">
                        <?php echo csrf_input(); ?>
                        <button type="submit" class="logout">Logout</button>
                    </form>
                </div>

                <!-- Profile Modal -->
                <div id="profileModal" class="modal">
                    <div class="modal-content">
                        <span class="close-modal">&times;</span>
                        <h2>Edit Profile</h2>
                        <form action="<?php echo htmlspecialchars(route_url('students/update_profile_modal.php'), ENT_QUOTES); ?>" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_input(); ?>
                            <div class="form-group">
                                <label>Profile Photo</label>
                                <div class="profile-preview-container">
                                    <?php if ($photo): ?>
                                        <img src="<?php echo htmlspecialchars(asset_url($photo), ENT_QUOTES); ?>" alt="Current Photo" class="profile-preview-img">
                                    <?php else: ?>
                                        <div class="profile-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="photo" accept="image/jpeg,image/png" class="file-input">
                            </div>
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($userProfile['first_name'] ?? '', ENT_QUOTES); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($userProfile['last_name'] ?? '', ENT_QUOTES); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($userProfile['address'] ?? '', ENT_QUOTES); ?>">
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($userProfile['username'] ?? '', ENT_QUOTES); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>New Password (leave blank to keep current)</label>
                                <input type="password" name="password" placeholder="New Password">
                            </div>
                            <div style="text-align: right; margin-top: 20px;">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars(route_url('students/login'), ENT_QUOTES); ?>">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>
