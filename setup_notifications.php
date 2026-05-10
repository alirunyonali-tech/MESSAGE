<?php
/*
 * setup_notifications.php
 * Run this once to create notifications table
 * Access: https://yoursite.com/setup_notifications.php?token=admin123
 */

$setupToken = 'admin123';
$providedToken = trim($_GET['token'] ?? '');

if (!hash_equals($setupToken, $providedToken)) {
    http_response_code(403);
    exit('Forbidden - Invalid token');
}

require_once __DIR__ . '/config/load-env.php';
require_once __DIR__ . '/db_config.php';

try {
    $db = getDB();

    // Create notifications table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `notifications` (
          `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `fb_user_id`       VARCHAR(50)  NOT NULL,
          `title`            VARCHAR(255) NOT NULL,
          `message`          TEXT        NOT NULL,
          `type`             VARCHAR(20)  NOT NULL DEFAULT 'info',
          `is_read`          TINYINT(1)   NOT NULL DEFAULT 0,
          `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_notif_user` (`fb_user_id`),
          INDEX `idx_notif_read` (`is_read`),
          INDEX `idx_notif_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    echo "✅ Notifications table created successfully!";
    echo "<br><br><a href='index.php'>Go to Home</a>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}