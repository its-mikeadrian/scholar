<?php
require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/db.php';
secure_session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (empty($_SESSION['auth_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate CSRF
if (empty($_POST['csrf_token']) || ($_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? ''))) {
    error_log('application_actions: CSRF validation failed. provided_len=' . strlen($_POST['csrf_token'] ?? '') . ' session_len=' . strlen($_SESSION['csrf_token'] ?? '') . ' user=' . ($user_id ?? 'null'));
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['auth_user_id'] ?? null;
$role = auth_role();

try {
    $pdo = get_db_connection();

    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if ($id <= 0 || $status === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing id or status']);
            exit;
        }

        // Only allow admins or managers to change status
        if ($role === 'student') {
            error_log('application_actions: Forbidden role attempted status update. user=' . ($user_id ?? 'null') . ' role=' . $role);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        // Map display status to DB status values
        $map = [
            'For Review' => 'pending',
            'Accepted' => 'approved',
            'Rejected' => 'rejected',
            'Incomplete' => 'incomplete',
            'pending' => 'pending',
            'approved' => 'approved',
            'rejected' => 'rejected',
            'incomplete' => 'incomplete'
        ];
        $dbStatus = $map[$status] ?? null;
        if (!$dbStatus) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM scholarship_applications WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Application not found']);
            exit;
        }

        // If rejected, attempt to store reason as well. If the column doesn't exist, add it and retry.
        if ($dbStatus === 'rejected') {
            try {
                $upd = $pdo->prepare('UPDATE scholarship_applications SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?');
                $upd->execute([$dbStatus, $reason, $id]);
            } catch (Exception $ex) {
                error_log('application_actions: failed to update rejection_reason, trying to add column: ' . $ex->getMessage());
                try {
                    $pdo->exec("ALTER TABLE scholarship_applications ADD COLUMN rejection_reason TEXT NULL");
                    $upd = $pdo->prepare('UPDATE scholarship_applications SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?');
                    $upd->execute([$dbStatus, $reason, $id]);
                } catch (Exception $ex2) {
                    error_log('application_actions: failed to add column or update reason: ' . $ex2->getMessage());
                    // fallback: update only status
                    $upd = $pdo->prepare('UPDATE scholarship_applications SET status = ?, updated_at = NOW() WHERE id = ?');
                    $upd->execute([$dbStatus, $id]);
                }
            }
        } else {
            $upd = $pdo->prepare('UPDATE scholarship_applications SET status = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$dbStatus, $id]);
        }

        error_log('application_actions: status updated by user=' . ($user_id ?? 'null') . ' id=' . $id . ' -> ' . $dbStatus);

        echo json_encode(['success' => true, 'message' => 'Status updated', 'status' => $dbStatus]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    error_log('Application actions error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

?>