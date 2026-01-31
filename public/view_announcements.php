<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../src/db.php';

try {
    // Get all announcements from database
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT * FROM announcements ORDER BY created_at DESC');
    $stmt->execute();
    $announcements = $stmt->fetchAll();
} catch (Exception $e) {
    die('Error fetching announcements: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <link href="assets/bootstrap/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .container {
            max-width: 900px;
            margin-top: 30px;
        }
        .announcement-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        .announcement-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .announcement-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
        }
        .announcement-body {
            padding: 20px;
        }
        .announcement-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .announcement-meta {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }
        .announcement-content {
            color: #666;
            line-height: 1.6;
            word-wrap: break-word;
        }
        .no-announcements {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📢 Announcements</h1>
        
        <?php if (empty($announcements)): ?>
            <div class="no-announcements">
                <p>No announcements yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-card">
                    <?php if (!empty($announcement['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($announcement['image_path']); ?>" alt="<?php echo htmlspecialchars($announcement['title']); ?>" class="announcement-image">
                    <?php endif; ?>
                    <div class="announcement-body">
                        <div class="announcement-title">
                            <?php echo htmlspecialchars($announcement['title']); ?>
                        </div>
                        <div class="announcement-meta">
                            <strong>Posted by User ID:</strong> <?php echo htmlspecialchars($announcement['user_id']); ?> | 
                            <strong>Date:</strong> <?php echo date('F d, Y - g:i A', strtotime($announcement['created_at'])); ?>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
