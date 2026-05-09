<?php
// upload_image.php — Secure image upload for broadcast attachments
define('FBCAST_PAGE_CONTEXT', true);

$config_file = __DIR__ . '/config/load-env.php';
if (file_exists($config_file)) {
    require_once $config_file;
} else {
    // If we're in a subdirectory or something, try one level up
    $config_file_alt = __DIR__ . '/../config/load-env.php';
    if (file_exists($config_file_alt)) {
        require_once $config_file_alt;
    }
}

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// CSRF validation
if (function_exists('verifyCsrfToken') && !verifyCsrfToken()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['image'];
$maxSize = 5 * 1024 * 1024; // 5 MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File too large.',
        UPLOAD_ERR_PARTIAL    => 'File upload incomplete.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server temp dir missing.',
        UPLOAD_ERR_CANT_WRITE => 'Server write error.',
    ];
    echo json_encode(['success' => false, 'error' => $errMap[$file['error']] ?? 'Upload error.']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum 5 MB.']);
    exit;
}

// Validate real MIME type
$mime = '';
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} elseif (function_exists('mime_content_type')) {
    $mime = mime_content_type($file['tmp_name']);
} else {
    // Fallback to file extension or provided type if finfo is missing
    $mime = $file['type'];
}

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Received: ' . htmlspecialchars($mime)]);
    exit;
}

$extMap = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
$ext = $extMap[$mime] ?? 'jpg';

// Create uploads dir if missing
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create uploads directory. Please check permissions.']);
        exit;
    }
    // Prevent directory listing and script execution for security
    $htaccess = "Options -Indexes\n";
    $htaccess .= "<Files ~ \"\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">\n";
    $htaccess .= "  Order allow,deny\n";
    $htaccess .= "  Deny from all\n";
    $htaccess .= "</Files>";
    @file_put_contents($uploadDir . '.htaccess', $htaccess);
}

$filename = bin2hex(random_bytes(16)) . '.' . $ext;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'error' => 'Could not save file on server.']);
    exit;
}

// Build public URL
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? '';
$siteUrl = (defined('SITE_URL') && SITE_URL) ? rtrim(SITE_URL, '/') : ($host ? "$scheme://$host" : '');

// If siteUrl is still empty (CLI or missing host), try to build from current script path
if (!$siteUrl && $host) {
    $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $siteUrl = "$scheme://$host$dir";
} else if ($siteUrl && !preg_match('~^https?://~i', $siteUrl)) {
    $siteUrl = "$scheme://" . ltrim($siteUrl, '/');
}

$url = rtrim($siteUrl, '/') . '/uploads/' . $filename;

echo json_encode(['success' => true, 'url' => $url, 'filename' => $filename]);
