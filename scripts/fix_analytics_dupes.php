<?php
require_once __DIR__ . '/../config/load-env.php';
require_once __DIR__ . '/../db_config.php';

$db = getDB();

echo "Starting cleanup of duplicate analytics logs...\n";

// 1. Delete duplicate subscription/payment logs for the same user on the same day with the same plan
// We keep the first one (lowest ID)
$sql = "DELETE a1 FROM activity_log a1
INNER JOIN activity_log a2 
WHERE a1.id > a2.id 
AND a1.fb_user_id = a2.fb_user_id 
AND a1.action IN ('payment', 'subscription')
AND a2.action IN ('payment', 'subscription')
AND DATE(a1.created_at) = DATE(a2.created_at)
AND (
    (a1.detail LIKE '%starter%' AND a2.detail LIKE '%starter%') OR
    (a1.detail LIKE '%bronze%' AND a2.detail LIKE '%bronze%') OR
    (a1.detail LIKE '%silver%' AND a2.detail LIKE '%silver%') OR
    (a1.detail LIKE '%gold%' AND a2.detail LIKE '%gold%') OR
    (a1.detail LIKE '%sapphire%' AND a2.detail LIKE '%sapphire%') OR
    (a1.detail LIKE '%platinum%' AND a2.detail LIKE '%platinum%')
)
AND ABS(TIMESTAMPDIFF(SECOND, a1.created_at, a2.created_at)) < 3600";

$stmt = $db->query($sql);
echo "Deleted " . $stmt->rowCount() . " duplicate activity logs.\n";

echo "Cleanup complete.\n";
