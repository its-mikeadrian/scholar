<?php
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/security.php';
secure_session_start();
require_once __DIR__ . '/../../src/db.php';

if (empty($_SESSION['auth_user_id']) || auth_role() !== 'student') {
    header('Location: ' . route_url('students/login'));
    exit;
}

$userId = (int) $_SESSION['auth_user_id'];

if (student_profile_completed($conn, $userId)) {
    header('Location: ' . route_url('students/home'));
    exit;
}

// Render profile setup page
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete Your Profile</title>
    <link rel="stylesheet" href="../assets/bootstrap/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            padding: 40px;
            background: #f7fafc
        }

        .card {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08)
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>Welcome — Complete Your Profile</h2>
        <p>Please provide your personal details and upload a profile photo to finish account setup.</p>
        <?php $page_error = $_SESSION['error'] ?? null;
        $page_success = $_SESSION['success'] ?? null;
        unset($_SESSION['error'], $_SESSION['success']); ?>
        <script>
            (function() {
                var c = document.getElementById('toast-container');
                if (!c) {
                    c = document.createElement('div');
                    c.id = 'toast-container';
                    c.className = 'toast-container';
                    document.body.appendChild(c);
                }

                function showToast(type, msg, d) {
                    var t = document.createElement('div');
                    t.className = 'toast ' + (type || 'info');
                    t.setAttribute('role', 'alert');
                    var icon = '';
                    if (type === 'success') icon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>';
                    else if (type === 'error') icon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v6"/><circle cx="12" cy="16" r="1"/></svg>';
                    else if (type === 'warning') icon = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>';
                    t.innerHTML = icon + '<div class="message"></div>';
                    t.querySelector('.message').textContent = String(msg || '');
                    c.appendChild(t);
                    void t.offsetHeight;
                    t.classList.add('show');
                    setTimeout(function() {
                        t.classList.remove('show');
                        setTimeout(function() {
                            t.remove();
                        }, 250);
                    }, d || 5000);
                }
                var m = {
                    error: <?php echo json_encode($page_error); ?>,
                    success: <?php echo json_encode($page_success); ?>
                };
                if (m.error) showToast('error', m.error);
                if (m.success) showToast('success', m.success);
            })();
        </script>

        <form method="POST" action="<?= route_url('students/process-profile') ?>" enctype="multipart/form-data">
            <?php echo csrf_input(); ?>
            <div class="mb-3">
                <label for="first_name" class="form-label">First name</label>
                <input type="text" name="first_name" id="first_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="last_name" class="form-label">Last name</label>
                <input type="text" name="last_name" id="last_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" name="address" id="address" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label d-block text-center">Profile Photo</label>
                <div id="preview-container" style="width: 120px; height: 120px; margin: 0 auto 15px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <svg style="width: 48px; height: 48px; color: #adb5bd;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <label for="photo" class="form-label visually-hidden">Upload Photo</label>
                <input type="file" name="photo" id="photo" class="form-control" accept="image/jpeg,image/png" required>
                <div class="form-text text-center">Allowed: JPG, PNG (Max 2MB)</div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Save and Continue</button>
        </form>
    </div>

    <script>
        document.getElementById('photo').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                var file = e.target.files[0];
                if (file.size > 2 * 1024 * 1024) {
                    showToast('error', 'File too large (max 2MB)');
                    this.value = '';
                    return;
                }
                var reader = new FileReader();
                reader.onload = function(e) {
                    var container = document.getElementById('preview-container');
                    container.innerHTML = '<img src="' + e.target.result + '" style="width: 100%; height: 100%; object-fit: cover;">';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

<style>
    .toast-container {
        position: fixed;
        top: 20%;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 3000;
        pointer-events: none
    }

    .toast {
        min-width: 280px;
        max-width: 520px;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fff;
        color: #111;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .18);
        padding: 12px 14px;
        border-left: 4px solid #6b7280;
        opacity: 0;
        transform: translateY(-8px);
        transition: opacity .25s ease, transform .25s ease
    }

    .toast.show {
        opacity: 1;
        transform: translateY(0)
    }

    .toast .icon {
        width: 18px;
        height: 18px
    }

    .toast.success {
        border-left-color: #16a34a
    }

    .toast.error {
        border-left-color: #dc2626
    }

    .toast.warning {
        border-left-color: #f59e0b
    }
</style>

</html>