<?php
define('FBCAST_PAGE_CONTEXT', true);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/config/load-env.php';
require_once __DIR__ . '/db_config.php';

if (empty($_SESSION['fb_user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Not authenticated']));
}

$fbUserId = $_SESSION['fb_user_id'];
$action   = $_GET['action'] ?? '';
$method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$body = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?: [];
}

try {
    switch ($action) {
        case 'conversations': getConversationsList($fbUserId); break;
        case 'sync':          syncFromFacebook($fbUserId, $body); break;
        case 'messages':      getMessages($fbUserId); break;
        case 'send':          sendMessage($fbUserId, $body); break;
        case 'mark_read':     markRead($fbUserId); break;
        default:
            http_response_code(400);
            die(json_encode(['error' => 'Invalid action']));
    }
} catch (Throwable $e) {
    logger('error', 'inbox_api error', ['action' => $action, 'msg' => $e->getMessage()]);
    http_response_code(500);
    die(json_encode(['error' => $e->getMessage()]));
}

/* ── Facebook API helpers ─────────────────────────────── */

function fbGet(string $path, string $token): array {
    $sep = strpos($path, '?') !== false ? '&' : '?';
    $ch  = curl_init('https://graph.facebook.com/v21.0' . $path . $sep . 'access_token=' . urlencode($token));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res ?: '{}', true) ?: [];
}

function fbPost(string $path, array $params, string $token): array {
    $params['access_token'] = $token;
    $ch = curl_init('https://graph.facebook.com/v21.0' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($params),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res ?: '{}', true) ?: [];
}

/* ── Action handlers ──────────────────────────────────── */

function getConversationsList(string $fbUserId): void {
    $db     = getDB();
    $pageId = $_GET['page_id'] ?? '';
    $search = trim($_GET['search'] ?? '');

    if (!$pageId) { http_response_code(400); die(json_encode(['error' => 'page_id required'])); }

    if ($search !== '') {
        $stmt = $db->prepare("
            SELECT * FROM inbox_conversations
            WHERE fb_user_id=? AND page_id=? AND customer_name LIKE ?
            ORDER BY last_message_at DESC LIMIT 60
        ");
        $stmt->execute([$fbUserId, $pageId, '%' . $search . '%']);
    } else {
        $stmt = $db->prepare("
            SELECT * FROM inbox_conversations
            WHERE fb_user_id=? AND page_id=?
            ORDER BY last_message_at DESC LIMIT 60
        ");
        $stmt->execute([$fbUserId, $pageId]);
    }

    echo json_encode(['conversations' => $stmt->fetchAll()]);
}

function syncFromFacebook(string $fbUserId, array $body): void {
    $db         = getDB();
    $pageId     = $body['page_id']    ?? '';
    $pageToken  = $body['page_token'] ?? '';

    if (!$pageId || !$pageToken) {
        http_response_code(400);
        die(json_encode(['error' => 'page_id and page_token required']));
    }

    $data = fbGet("/{$pageId}/conversations?fields=id,participants,updated_time,unread_count&platform=messenger&limit=25", $pageToken);

    if (isset($data['error'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Facebook: ' . ($data['error']['message'] ?? 'API error')]));
    }

    $conversations = $data['data'] ?? [];
    $synced = 0;

    $upsertConv = $db->prepare("
        INSERT INTO inbox_conversations
            (fb_user_id, page_id, customer_psid, customer_name, customer_avatar,
             last_message, last_direction, last_message_at, unread_count)
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            customer_name    = VALUES(customer_name),
            customer_avatar  = VALUES(customer_avatar),
            last_message     = VALUES(last_message),
            last_direction   = VALUES(last_direction),
            last_message_at  = VALUES(last_message_at),
            unread_count     = VALUES(unread_count),
            updated_at       = NOW()
    ");

    $insertMsg = $db->prepare("
        INSERT IGNORE INTO inbox_messages
            (conversation_id, fb_message_id, direction, message_text,
             attachment_url, attachment_type, sent_at)
        VALUES (?,?,?,?,?,?,?)
    ");

    $getConvId = $db->prepare("
        SELECT id FROM inbox_conversations WHERE page_id=? AND customer_psid=?
    ");

    foreach ($conversations as $conv) {
        $participants = $conv['participants']['data'] ?? [];
        $customerPsid = $customerName = $customerAvatar = '';

        foreach ($participants as $p) {
            if ($p['id'] !== $pageId) {
                $customerPsid   = $p['id'];
                $customerName   = $p['name'] ?? 'Unknown';
                $customerAvatar = '';
                break;
            }
        }
        if (!$customerPsid) continue;

        // Fetch messages for this conversation
        $msgData  = fbGet("/{$conv['id']}/messages?fields=id,message,from,attachments{type,image_data,file_url},created_time&limit=30", $pageToken);
        $messages = array_reverse($msgData['data'] ?? []);
        if (empty($messages)) continue;

        $last      = end($messages);
        $lastText  = $last['message'] ?? '📎 Attachment';
        $lastDir   = ($last['from']['id'] === $pageId) ? 'out' : 'in';
        $lastAt    = date('Y-m-d H:i:s', strtotime($last['created_time']));
        $unread    = (int)($conv['unread_count'] ?? 0);

        $upsertConv->execute([$fbUserId, $pageId, $customerPsid, $customerName,
                              $customerAvatar, $lastText, $lastDir, $lastAt, $unread]);

        $getConvId->execute([$pageId, $customerPsid]);
        $convDbId = $getConvId->fetchColumn();
        if (!$convDbId) continue;

        foreach ($messages as $msg) {
            $dir       = ($msg['from']['id'] === $pageId) ? 'out' : 'in';
            $text      = $msg['message'] ?? '';
            $sentAt    = date('Y-m-d H:i:s', strtotime($msg['created_time']));
            $attachUrl = null;
            $attachTyp = null;

            $attachments = $msg['attachments']['data'] ?? [];
            if (!empty($attachments)) {
                $a         = $attachments[0];
                $attachTyp = $a['type'] ?? 'file';
                $attachUrl = $a['image_data']['url'] ?? $a['file_url'] ?? null;
                if (!$text) $text = '📎 ' . ucfirst($attachTyp);
            }

            $insertMsg->execute([$convDbId, $msg['id'], $dir, $text, $attachUrl, $attachTyp, $sentAt]);
        }
        $synced++;
    }

    echo json_encode(['synced' => $synced, 'total' => count($conversations)]);
}

function getMessages(string $fbUserId): void {
    $db     = getDB();
    $convId = (int)($_GET['conv_id'] ?? 0);
    $since  = $_GET['since'] ?? null; // ISO datetime for polling new msgs only

    if (!$convId) { http_response_code(400); die(json_encode(['error' => 'conv_id required'])); }

    $stmt = $db->prepare("SELECT id FROM inbox_conversations WHERE id=? AND fb_user_id=?");
    $stmt->execute([$convId, $fbUserId]);
    if (!$stmt->fetch()) { http_response_code(403); die(json_encode(['error' => 'Forbidden'])); }

    if ($since) {
        $stmt = $db->prepare("SELECT * FROM inbox_messages WHERE conversation_id=? AND sent_at > ? ORDER BY sent_at ASC LIMIT 50");
        $stmt->execute([$convId, $since]);
    } else {
        $stmt = $db->prepare("SELECT * FROM inbox_messages WHERE conversation_id=? ORDER BY sent_at ASC LIMIT 100");
        $stmt->execute([$convId]);
    }

    echo json_encode(['messages' => $stmt->fetchAll()]);
}

function sendMessage(string $fbUserId, array $body): void {
    $db        = getDB();
    $convId    = (int)($body['conv_id']    ?? 0);
    $text      = trim($body['message']     ?? '');
    $pageToken = $body['page_token']       ?? '';
    $pageId    = $body['page_id']          ?? '';

    if (!$convId || !$text || !$pageToken || !$pageId) {
        http_response_code(400);
        die(json_encode(['error' => 'Missing required fields']));
    }
    if (mb_strlen($text) > 2000) {
        http_response_code(400);
        die(json_encode(['error' => 'Message too long (max 2000 chars)']));
    }

    $stmt = $db->prepare("SELECT customer_psid FROM inbox_conversations WHERE id=? AND fb_user_id=?");
    $stmt->execute([$convId, $fbUserId]);
    $conv = $stmt->fetch();
    if (!$conv) { http_response_code(403); die(json_encode(['error' => 'Forbidden'])); }

    $result = fbPost("/{$pageId}/messages", [
        'recipient'      => ['id' => $conv['customer_psid']],
        'message'        => ['text' => $text],
        'messaging_type' => 'RESPONSE',
    ], $pageToken);

    if (isset($result['error'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Facebook: ' . ($result['error']['message'] ?? 'Send failed')]));
    }

    $msgId = $result['message_id'] ?? ('local_' . uniqid());
    $now   = date('Y-m-d H:i:s');

    $db->prepare("INSERT IGNORE INTO inbox_messages (conversation_id,fb_message_id,direction,message_text,sent_at) VALUES (?,?,'out',?,?)")
       ->execute([$convId, $msgId, $text, $now]);

    $db->prepare("UPDATE inbox_conversations SET last_message=?,last_direction='out',last_message_at=?,unread_count=0 WHERE id=?")
       ->execute([$text, $now, $convId]);

    echo json_encode(['success' => true, 'message_id' => $msgId, 'sent_at' => $now]);
}

function markRead(string $fbUserId): void {
    $db     = getDB();
    $convId = (int)($_GET['conv_id'] ?? 0);
    if (!$convId) { die(json_encode(['error' => 'conv_id required'])); }
    $db->prepare("UPDATE inbox_conversations SET unread_count=0 WHERE id=? AND fb_user_id=?")
       ->execute([$convId, $fbUserId]);
    echo json_encode(['success' => true]);
}
