<?php
require_once __DIR__ . '/../config/load-env.php';
require_once __DIR__ . '/../db_config.php';

$db = getDB();

echo "Starting cleanup of duplicate activity logs and payment history...\n";

// 1. Cleanup payment_history duplicates
// Keep only the one with the lowest ID for each stripe_invoice_id
$stmt = $db->query("
    DELETE p1 FROM payment_history p1
    INNER JOIN payment_history p2 
    WHERE p1.id > p2.id AND p1.stripe_invoice_id = p2.stripe_invoice_id AND p1.stripe_invoice_id != ''
");
echo "Deleted " . $stmt->rowCount() . " duplicate payment_history entries.\n";

// 2. Cleanup activity_log duplicates
// Keep only the one with the lowest ID for each fb_user_id, action, and detail
$stmt = $db->query("
    DELETE a1 FROM activity_log a1
    INNER JOIN activity_log a2 
    WHERE a1.id > a2.id 
    AND a1.fb_user_id = a2.fb_user_id 
    AND a1.action = a2.action 
    AND a1.detail = a2.detail
");
echo "Deleted " . $stmt->rowCount() . " duplicate activity_log entries.\n";

echo "Cleanup complete.\n";
