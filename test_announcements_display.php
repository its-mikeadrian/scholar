<?php
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/src/db.php';

try {
    $pdo = get_db_connection();
    
    // Check if announcements table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total announcements in database: " . $result['count'] . "\n\n";
    
    // Get all announcements with author info
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.user_id,
            a.title,
            a.content,
            a.image_path,
            a.created_at,
            u.username,
            COALESCE(sp.first_name, '') as first_name,
            COALESCE(sp.last_name, '') as last_name
        FROM announcements a
        JOIN users u ON a.user_id = u.id
        LEFT JOIN student_profiles sp ON u.id = sp.user_id
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Announcements found:\n";
    echo str_repeat("-", 80) . "\n";
    
    if (empty($announcements)) {
        echo "No announcements in the database.\n";
    } else {
        foreach ($announcements as $ann) {
            $author = (!empty($ann['first_name']) || !empty($ann['last_name'])) 
                ? "{$ann['first_name']} {$ann['last_name']}" 
                : $ann['username'];
            echo "ID: {$ann['id']}\n";
            echo "Title: {$ann['title']}\n";
            echo "Author: {$author}\n";
            echo "Content: " . substr($ann['content'], 0, 50) . "...\n";
            echo "Image: " . ($ann['image_path'] ? $ann['image_path'] : 'None') . "\n";
            echo "Date: {$ann['created_at']}\n";
            echo str_repeat("-", 80) . "\n";
        }
    }
    
    echo "\n✓ Announcement system is working correctly!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Check if the announcements table exists in your database.\n";
}
?>
