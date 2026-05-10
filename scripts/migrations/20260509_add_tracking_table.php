<?php
require_once 'config/load-env.php';
require_once 'db_config.php';

try {
    $db = getDB();
    
    // 1. Create user_tracking table for advanced analytics
    $db->exec("
        CREATE TABLE IF NOT EXISTS `user_tracking` (
          `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `fb_user_id`     VARCHAR(50) NULL,
          `session_id`     VARCHAR(100) NOT NULL,
          `event_name`     VARCHAR(100) NOT NULL,
          `page_url`       VARCHAR(255) NOT NULL,
          `referrer`       VARCHAR(255) NULL,
          `device_type`    VARCHAR(50) NULL,
          `browser`        VARCHAR(50) NULL,
          `os`             VARCHAR(50) NULL,
          `screen_res`     VARCHAR(20) NULL,
          `ip_address`     VARCHAR(45) NOT NULL,
          `country`        VARCHAR(100) NULL,
          `city`           VARCHAR(100) NULL,
          `event_data`     JSON NULL,
          `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_track_user` (`fb_user_id`),
          INDEX `idx_track_session` (`session_id`),
          INDEX `idx_track_event` (`event_name`),
          INDEX `idx_track_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Table user_tracking created successfully.\n";

    // 2. Add some useful indexes if they don't exist
    try {
        $db->exec("ALTER TABLE activity_log ADD INDEX IF NOT EXISTS idx_action (action)");
    } catch (Exception $e) {
        // Index might already exist or DB doesn't support IF NOT EXISTS on ALTER
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
