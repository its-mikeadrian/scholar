<?php
require_once __DIR__ . '/../src/security.php';
require_once __DIR__ . '/../src/db.php';
secure_session_start();

header('Content-Type: application/json');

try {
    $pdo = get_db_connection();
    
    // Test 1: Check if announcements table exists and has data
    $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements");
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total = (int)$count_result['count'];
    
    echo json_encode([
        'test' => 'announcements_count',
        'count' => $total,
        'success' => true
    ]);
    
    // Test 2: Get all announcements with full query
    if ($total > 0) {
        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.user_id,
                a.title,
                a.content,
                a.image_path,
                a.created_at,
                u.username
            FROM announcements a
            JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'test' => 'announcements_full_query',
            'count' => count($announcements),
            'announcements' => $announcements,
            'success' => true
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
