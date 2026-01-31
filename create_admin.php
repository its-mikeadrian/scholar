<?php
require_once __DIR__ . '/src/db.php';

try {
    $pdo = get_db_connection();
    
    $username = 'geloadmin';
    $email = 'mostdevil24@gmail.com';
    $password_hash = '$2y$10$2ttbT0NHbXWJs/RcYxzG3Onhey37Z9XNKP3i/5gFCUq55NtKKD5eq';
    $role = 'superadmin';
    
    // Insert the user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role, is_active, created_at)
        VALUES (?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->execute([$username, $email, $password_hash, $role]);
    
    echo "✓ Superadmin account created successfully!\n";
    echo "  Username: {$username}\n";
    echo "  Email: {$email}\n";
    echo "  Role: {$role}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
