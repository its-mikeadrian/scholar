<?php
require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/db.php';
secure_session_start();

header('Content-Type: application/json');

// Only require auth for POST/DELETE operations
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !isset($_SESSION['auth_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Verify CSRF token for POST operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'] ?? '') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['auth_user_id'] ?? null;

try {
    $pdo = get_db_connection();

    if ($action === 'post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';

        // Validate inputs
        if (empty($title) || empty($content)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Title and content are required']);
            exit;
        }

        $image_path = null;

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($file['type'], $allowed_types)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid image type']);
                exit;
            }

            if ($file['size'] > $max_size) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Image size too large (max 5MB)']);
                exit;
            }

            // Create upload directory in public folder
            $upload_dir = __DIR__ . '/uploads/announcements';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'announcement_' . time() . '_' . uniqid() . '.' . $ext;
            $file_path = $upload_dir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Store path relative to public directory (accessible via HTTP)
                $image_path = 'uploads/announcements/' . $filename;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
                exit;
            }
        }

        // Insert announcement
        $stmt = $pdo->prepare("
            INSERT INTO announcements (user_id, title, content, image_path, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([$user_id, $title, $content, $image_path]);
        $announcement_id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Announcement posted successfully',
            'announcement_id' => $announcement_id
        ]);

    } elseif ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            // Get announcements with pagination
            $page = (int)($_GET['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // Query to get all announcements - simplified
            $stmt = $pdo->prepare("
                SELECT 
                    a.id,
                    a.user_id,
                    a.title,
                    a.content,
                    a.image_path,
                    a.created_at,
                    a.updated_at,
                    u.username,
                    COALESCE(sp.first_name, '') as first_name,
                    COALESCE(sp.last_name, '') as last_name
                FROM announcements a
                JOIN users u ON a.user_id = u.id
                LEFT JOIN student_profiles sp ON u.id = sp.user_id
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements");
            $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $total = (int)$count_result['count'];

            echo json_encode([
                'success' => true,
                'announcements' => $announcements,
                'total' => $total,
                'page' => $page,
                'total_pages' => ($total > 0) ? ceil($total / $limit) : 1
            ]);
        } catch (Exception $e) {
            error_log('Get announcements error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to retrieve announcements: ' . $e->getMessage()
            ]);
        }

    } elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $announcement_id = (int)($_POST['id'] ?? 0);

        // Check ownership
        $stmt = $pdo->prepare("SELECT user_id, image_path FROM announcements WHERE id = ?");
        $stmt->execute([$announcement_id]);
        $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$announcement) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Announcement not found']);
            exit;
        }

        if ($announcement['user_id'] !== $user_id && auth_role() !== 'superadmin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You cannot delete this announcement']);
            exit;
        }

        // Delete image if exists
        if ($announcement['image_path']) {
            $image_file = __DIR__ . '/../' . $announcement['image_path'];
            if (file_exists($image_file)) {
                unlink($image_file);
            }
        }

        // Delete announcement
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$announcement_id]);

        echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully']);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }

} catch (Exception $e) {
    error_log('Announcement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
