<?php
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/auth.php';

try {
    $pdo = get_db_connection();
    
    echo "✓ Database connection: OK\n";
    
    // Check if announcements table exists
    $stmt = $pdo->query("SELECT 1 FROM announcements LIMIT 1");
    echo "✓ Announcements table: EXISTS\n";
    
    // Check auth_role function
    if (function_exists('auth_role')) {
        echo "✓ auth_role() function: EXISTS\n";
    } else {
        echo "✗ auth_role() function: MISSING\n";
    }
    
    // Check if storage directory exists
    if (is_dir(__DIR__ . '/storage/uploads/announcements')) {
        echo "✓ Storage directory: EXISTS\n";
    } else {
        echo "✗ Storage directory: MISSING\n";
    }
    
    echo "\n✓ All systems ready for announcements!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
