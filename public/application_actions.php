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
        $source = trim($_POST['source'] ?? 'application');
        $target_table = ($source === 'renewal') ? 'scholarship_renewals' : 'scholarship_applications';

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

        $stmt = $pdo->prepare("SELECT id FROM {$target_table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Application not found']);
            exit;
        }

        // If rejected/incomplete, attempt to store reason as well. If the column doesn't exist, add it and retry.
        if ($dbStatus === 'rejected') {
            try {
                $upd = $pdo->prepare("UPDATE {$target_table} SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$dbStatus, $reason, $id]);
            } catch (Exception $ex) {
                error_log('application_actions: failed to update rejection_reason, trying to add column: ' . $ex->getMessage());
                try {
                    $pdo->exec("ALTER TABLE {$target_table} ADD COLUMN rejection_reason TEXT NULL");
                    $upd = $pdo->prepare("UPDATE {$target_table} SET status = ?, rejection_reason = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$dbStatus, $reason, $id]);
                } catch (Exception $ex2) {
                    error_log('application_actions: failed to add column or update reason: ' . $ex2->getMessage());
                    // fallback: update only status
                    $upd = $pdo->prepare("UPDATE {$target_table} SET status = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$dbStatus, $id]);
                }
            }
        } elseif ($dbStatus === 'incomplete') {
            try {
                $upd = $pdo->prepare("UPDATE {$target_table} SET status = ?, incomplete_reason = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$dbStatus, $reason, $id]);
            } catch (Exception $ex) {
                error_log('application_actions: failed to update incomplete_reason, trying to add column: ' . $ex->getMessage());
                try {
                    $pdo->exec("ALTER TABLE {$target_table} ADD COLUMN incomplete_reason TEXT NULL");
                    $upd = $pdo->prepare("UPDATE {$target_table} SET status = ?, incomplete_reason = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$dbStatus, $reason, $id]);
                } catch (Exception $ex2) {
                    error_log('application_actions: failed to add column or update incomplete reason: ' . $ex2->getMessage());
                    $upd = $pdo->prepare("UPDATE {$target_table} SET status = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$dbStatus, $id]);
                }
            }
        } else {
            $upd = $pdo->prepare("UPDATE {$target_table} SET status = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$dbStatus, $id]);
        }

        error_log('application_actions: status updated by user=' . ($user_id ?? 'null') . ' source=' . $source . ' id=' . $id . ' -> ' . $dbStatus);

        echo json_encode(['success' => true, 'message' => 'Status updated', 'status' => $dbStatus]);
        exit;
    }

    if ($action === 'mark_paid') {
        $id = (int)($_POST['id'] ?? 0);
        $source = trim($_POST['source'] ?? 'application');
        $target_table = ($source === 'renewal') ? 'scholarship_renewals' : 'scholarship_applications';
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing id']);
            exit;
        }

        if ($role === 'student') {
            error_log('application_actions: Forbidden role attempted mark_paid. user=' . ($user_id ?? 'null') . ' role=' . $role);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM {$target_table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Application not found']);
            exit;
        }

        try {
            $upd = $pdo->prepare("UPDATE {$target_table} SET is_paid = 1, paid_date = CURDATE(), updated_at = NOW() WHERE id = ?");
            $upd->execute([$id]);
        } catch (Exception $ex) {
            error_log('application_actions: failed to update is_paid/paid_date, trying to add columns: ' . $ex->getMessage());
            try {
                $pdo->exec("ALTER TABLE {$target_table} ADD COLUMN is_paid TINYINT(1) NOT NULL DEFAULT 0");
            } catch (Exception $ex2) {
            }
            try {
                $pdo->exec("ALTER TABLE {$target_table} ADD COLUMN paid_date DATE NULL");
            } catch (Exception $ex3) {
            }
            $upd = $pdo->prepare("UPDATE {$target_table} SET is_paid = 1, paid_date = CURDATE(), updated_at = NOW() WHERE id = ?");
            $upd->execute([$id]);
        }

        $stmt = $pdo->prepare("SELECT is_paid, paid_date FROM {$target_table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Marked as paid',
            'id' => $id,
            'source' => $source,
            'is_paid' => (int)($row['is_paid'] ?? 1),
            'paid_date' => $row['paid_date'] ?? null,
        ]);
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
