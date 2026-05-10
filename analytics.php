<?php
/*
 * analytics.php
 * Endpoint for syncing frontend tracking events.
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/config/load-env.php';
require_once 'db_config.php';
require_once __DIR__ . '/config/rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Rate limit: 60 requests per minute per IP for analytics
$_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!rateLimitCheck('analytics:' . $_ip, 60, 60)) {
    http_response_code(429);
    die(json_encode(['error' => 'Too many requests']));
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || empty($data['events'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid events data']));
}

$db = getDB();
$fbUserId = $_SESSION['fb_user_id'] ?? null;
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// User Agent parsing (simple)
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$browser = 'Unknown';
$os = 'Unknown';
if (preg_match('/MSIE/i', $ua) && !preg_match('/Opera/i', $ua)) { $browser = 'Internet Explorer'; }
elseif (preg_match('/Firefox/i', $ua)) { $browser = 'Firefox'; }
elseif (preg_match('/Chrome/i', $ua)) { $browser = 'Chrome'; }
elseif (preg_match('/Safari/i', $ua)) { $browser = 'Safari'; }
elseif (preg_match('/Opera/i', $ua)) { $browser = 'Opera'; }

if (preg_match('/windows|win32/i', $ua)) { $os = 'Windows'; }
elseif (preg_match('/macintosh|mac os x/i', $ua)) { $os = 'Mac OS'; }
elseif (preg_match('/linux/i', $ua)) { $os = 'Linux'; }
elseif (preg_match('/iphone|ipad|ipod/i', $ua)) { $os = 'iOS'; }
elseif (preg_match('/android/i', $ua)) { $os = 'Android'; }

$deviceType = 'Desktop';
if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $ua)) {
    $deviceType = 'Mobile';
}

$stmt = $db->prepare("
    INSERT INTO user_tracking 
    (fb_user_id, session_id, event_name, page_url, referrer, device_type, browser, os, screen_res, ip_address, event_data) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$successCount = 0;
foreach ($data['events'] as $event) {
    $name = $event['name'] ?? 'unknown';
    $sessionId = $event['sessionId'] ?? 'unknown';
    $path = $event['path'] ?? '/';
    $props = $event['props'] ?? [];
    $screenRes = $props['screen_res'] ?? null;
    $referrer = $props['referrer'] ?? null;
    
    // Remove screen_res and referrer from props to avoid duplication in JSON
    unset($props['screen_res'], $props['referrer']);
    
    $eventDataJson = !empty($props) ? json_encode($props) : null;

    try {
        $stmt->execute([
            $fbUserId,
            $sessionId,
            $name,
            $path,
            $referrer,
            $deviceType,
            $browser,
            $os,
            $screenRes,
            $ip,
            $eventDataJson
        ]);
        $successCount++;
    } catch (Exception $e) {
        // Log error and continue
    }
}

echo json_encode(['success' => true, 'count' => $successCount]);
