<?php
/*
 * notifications_api.php
 * Handle notification operations: send, get, mark read
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'])) {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

require_once __DIR__ . '/config/load-env.php';
require_once __DIR__ . '/db_config.php';

$action = $_GET['action'] ?? '';

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Admin sending notification to user(s)
    requireCsrfToken();

    $body = json_decode(get_raw_input(), true) ?: [];
    $fbUserId = trim($body['fb_user_id'] ?? '');
    $title = trim($body['title'] ?? '');
    $message = trim($body['message'] ?? '');
    $type = trim($body['type'] ?? 'info');

    if (!$fbUserId || !$title || !$message) {
        http_response_code(400);
        die(json_encode(['error' => 'Missing required fields: fb_user_id, title, message']));
    }

    if (!in_array($type, ['info', 'warning', 'success', 'error'])) {
        $type = 'info';
    }

    $db = getDB();
    $db->prepare(
        "INSERT INTO notifications (fb_user_id, title, message, type) VALUES (?, ?, ?, ?)"
    )->execute([$fbUserId, $title, $message, $type]);

    echo json_encode(['success' => true, 'message' => 'Notification sent']);
    exit;
}

if ($action === 'send_bulk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send to multiple users
    requireCsrfToken();

    $body = json_decode(get_raw_input(), true) ?: [];
    $fbUserIds = $body['fb_user_ids'] ?? [];
    $title = trim($body['title'] ?? '');
    $message = trim($body['message'] ?? '');
    $type = trim($body['type'] ?? 'info');

    if (empty($fbUserIds) || !$title || !$message) {
        http_response_code(400);
        die(json_encode(['error' => 'Missing required fields: fb_user_ids (array), title, message']));
    }

    if (!in_array($type, ['info', 'warning', 'success', 'error'])) {
        $type = 'info';
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (fb_user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $count = 0;
    foreach ($fbUserIds as $fbId) {
        $stmt->execute([trim($fbId), $title, $message, $type]);
        $count++;
    }

    echo json_encode(['success' => true, 'count' => $count, 'message' => "Sent to $count users"]);
    exit;
}

if ($action === 'send_all' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Send to ALL users
    requireCsrfToken();

    $body = json_decode(get_raw_input(), true) ?: [];
    $title = trim($body['title'] ?? '');
    $message = trim($body['message'] ?? '');
    $type = trim($body['type'] ?? 'info');

    if (!$title || !$message) {
        http_response_code(400);
        die(json_encode(['error' => 'Missing required fields: title, message']));
    }

    if (!in_array($type, ['info', 'warning', 'success', 'error'])) {
        $type = 'info';
    }

    $db = getDB();

    // Get all users
    $users = $db->query("SELECT fb_user_id FROM users")->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $db->prepare("INSERT INTO notifications (fb_user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $count = 0;
    foreach ($users as $fbId) {
        $stmt->execute([$fbId, $title, $message, $type]);
        $count++;
    }

    echo json_encode(['success' => true, 'count' => $count, 'message' => "Sent to $count users"]);
    exit;
}

if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // User fetching their notifications
    $fbUserId = $_GET['fb_user_id'] ?? '';

    if (!$fbUserId) {
        http_response_code(400);
        die(json_encode(['error' => 'Missing fb_user_id']));
    }

    $db = getDB();
    $limit = min((int)($_GET['limit'] ?? 20), 100);

    $stmt = $db->prepare(
        "SELECT id, title, message, type, is_read, created_at
         FROM notifications
         WHERE fb_user_id = ?
         ORDER BY created_at DESC
         LIMIT ?"
    );
    $stmt->bindValue(1, $fbUserId, PDO::PARAM_STR);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unread count
    $unreadStmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE fb_user_id = ? AND is_read = 0");
    $unreadStmt->execute([$fbUserId]);
    $unreadCount = $unreadStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)$unreadCount
    ]);
    exit;
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark notification as read
    requireCsrfToken();

    $body = json_decode(get_raw_input(), true) ?: [];
    $notificationId = (int)($body['id'] ?? 0);
    $fbUserId = trim($body['fb_user_id'] ?? '');

    if (!$notificationId || !$fbUserId) {
        http_response_code(400);
        die(json_encode(['error' => 'Missing id or fb_user_id']));
    }

    $db = getDB();
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND fb_user_id = ?")
       ->execute([$notificationId, $fbUserId]);

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'mark_all_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark all as read for a user
    requireCsrfToken();

    $body = json_decode(get_raw_input(), true) ?: [];
    $fbUserId = trim($body['fb_user_id'] ?? '');

    if (!$fbUserId) {
        http_response_code(400);
        die(json_encode(['error' => 'Missing fb_user_id']));
    }

    $db = getDB();
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE fb_user_id = ? AND is_read = 0")
       ->execute([$fbUserId]);

    echo json_encode(['success' => true]);
    exit;
}

// Default: list all notifications (admin only - would need auth check)
http_response_code(400);
die(json_encode(['error' => 'Invalid action']));