<?php
require_once __DIR__ . '/src/db.php';

try {
    $pdo = get_db_connection();
    
    // Create announcements table if it doesn't exist
    $sql = "
    CREATE TABLE IF NOT EXISTS `announcements` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `user_id` int(10) UNSIGNED NOT NULL,
      `title` varchar(255) NOT NULL,
      `content` longtext NOT NULL,
      `image_path` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
      INDEX `idx_created_at` (`created_at`),
      INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "✓ Announcements table created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
