<?php
require_once 'config/load-env.php';
require_once 'db_config.php';

try {
    $db = getDB();
    
    // Create site_settings table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `site_settings` (
          `setting_key`   VARCHAR(100) PRIMARY KEY,
          `setting_value` TEXT NULL,
          `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Insert default support settings if they don't exist
    $defaults = [
        'support_whatsapp' => '',
        'support_messenger' => '',
        'support_email' => ''
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    foreach ($defaults as $key => $val) {
        $stmt->execute([$key, $val]);
    }

    echo "Table site_settings created and defaults inserted successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
