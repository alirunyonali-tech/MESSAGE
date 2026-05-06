<?php
/* ─────────────────────────────────────────────────────────
   setup_db.php — Run ONCE to create database tables.
   Access via Hostinger File Manager or SSH — NOT via browser.
   This file is blocked by .htaccess in production.
   After running, DELETE this file from the server!
   ───────────────────────────────────────────────────────── */

$setupToken = getenv('SETUP_ACCESS_TOKEN') ?: '';
if (php_sapi_name() !== 'cli') {
    $providedToken = trim($_GET['token'] ?? '');
    if ($setupToken === '' || !hash_equals($setupToken, $providedToken)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

require_once 'db_config.php';

try {
    $db = getDB();

    // Plan ENUM: free / basic / pro  (matches STRIPE_PLANS in config/load-env.php)
    $db->exec("
        CREATE TABLE IF NOT EXISTS `users` (
          `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `fb_user_id`           VARCHAR(50)  NOT NULL UNIQUE,
          `fb_name`              VARCHAR(255) NOT NULL DEFAULT '',
          `email`                VARCHAR(255) NULL DEFAULT NULL,
          `plan`                 ENUM('free','basic','pro') NOT NULL DEFAULT 'free',
          `messages_used`        INT UNSIGNED NOT NULL DEFAULT 0,
          `messages_limit`       INT UNSIGNED NOT NULL DEFAULT 2000,
          `trial_used`           TINYINT(1)   NOT NULL DEFAULT 1,
          `first_login`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `last_login`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `ip_address`           VARCHAR(45)  NOT NULL DEFAULT '',
          `subscription_expires`    DATETIME     NULL,
          `stripe_customer_id`      VARCHAR(255) NULL DEFAULT NULL,
          `stripe_subscription_id`  VARCHAR(255) NULL DEFAULT NULL,
          INDEX `idx_fb` (`fb_user_id`),
          INDEX `idx_email` (`email`),
          INDEX `idx_plan` (`plan`),
          INDEX `idx_stripe_cust` (`stripe_customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
          `setting_key`   VARCHAR(50) PRIMARY KEY,
          `setting_value` TEXT        NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Admin password is stored as bcrypt hash.
    // Use ADMIN_BOOTSTRAP_PASSWORD from env if provided, otherwise generate a random bootstrap password.
    $bootstrapPassword = getenv('ADMIN_BOOTSTRAP_PASSWORD');
    if ($bootstrapPassword === false || trim($bootstrapPassword) === '') {
        $bootstrapPassword = bin2hex(random_bytes(8)); // 16-char random temporary password
    }
    $defaultHash = password_hash($bootstrapPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    // INSERT IGNORE = don't overwrite if already set
    $stmt = $db->prepare("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
    $stmt->execute(['free_limit',     '2000']);
    $stmt->execute(['admin_password', $defaultHash]);
    $stmt->execute(['site_name',      'FBCast Pro']);
    $stmt->execute(['announcement_enabled', '0']);
    $stmt->execute(['announcement_type', 'text']);
    $stmt->execute(['announcement_text', '']);
    $stmt->execute(['announcement_media_url', '']);
    $stmt->execute(['announcement_link_url', '']);

    $db->exec("
        CREATE TABLE IF NOT EXISTS `activity_log` (
          `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `fb_user_id` VARCHAR(50) NOT NULL,
          `action`     VARCHAR(50) NOT NULL,
          `detail`     TEXT,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_user`   (`fb_user_id`),
          INDEX `idx_time`   (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `payment_history` (
          `id`               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `fb_user_id`       VARCHAR(50)  NOT NULL,
          `stripe_invoice_id` VARCHAR(255) NOT NULL DEFAULT '',
          `plan`             ENUM('free','basic','pro','unknown') NOT NULL DEFAULT 'unknown',
          `amount_cents`     INT UNSIGNED NOT NULL DEFAULT 0,
          `status`           ENUM('succeeded','failed','pending') NOT NULL DEFAULT 'pending',
          `billing_reason`   VARCHAR(80)  NOT NULL DEFAULT '',
          `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_payment_user` (`fb_user_id`),
          INDEX `idx_payment_invoice` (`stripe_invoice_id`),
          INDEX `idx_payment_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `webhook_events` (
          `id`               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `event_id`         VARCHAR(255) NOT NULL UNIQUE,
          `event_type`       VARCHAR(120) NOT NULL,
          `status`           ENUM('processing','processed','failed') NOT NULL DEFAULT 'processing',
          `attempts`         INT UNSIGNED NOT NULL DEFAULT 1,
          `payload`          MEDIUMTEXT NULL,
          `last_error`       TEXT NULL,
          `received_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `last_seen_at`     DATETIME NULL DEFAULT NULL,
          `processed_at`     DATETIME NULL DEFAULT NULL,
          INDEX `idx_webhook_status` (`status`),
          INDEX `idx_webhook_type` (`event_type`),
          INDEX `idx_webhook_received` (`received_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>body{font-family:monospace;background:#070b14;color:#e4e6eb;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}
    .box{background:#0d1220;border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:48px;max-width:520px;text-align:center;}
    h2{color:#22c55e;font-size:28px;margin-bottom:16px;}
    p{color:#94a3b8;line-height:1.7;margin-bottom:8px;}
    .warn{color:#f59e0b;font-weight:bold;margin-top:24px;font-size:18px;}
    code{background:#1f2937;padding:3px 8px;border-radius:5px;color:#60a5fa;}
    </style></head><body>
    <div class="box">
      <h2>&#x2705; Database Setup Complete!</h2>
      <p>All tables created successfully.</p>
      <p>Bootstrap admin password: <code>' . htmlspecialchars($bootstrapPassword, ENT_QUOTES, 'UTF-8') . '</code></p>
      <p>Change it immediately in <strong>/admin.php &rarr; Settings</strong></p>
      <p class="warn">&#x26A0;&#xFE0F; DELETE setup_db.php from your server NOW!</p>
    </div></body></html>';

} catch (Exception $e) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>body{font-family:monospace;background:#070b14;color:#e4e6eb;padding:40px;}h2{color:#ef4444;}</style>
    </head><body><h2>&#x274C; Setup Failed</h2><pre>' . htmlspecialchars($e->getMessage()) . '</pre>
    <p>Check your .env credentials.</p></body></html>';
}
