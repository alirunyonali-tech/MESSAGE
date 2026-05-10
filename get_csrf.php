<?php
/*
 * get_csrf.php — Returns a CSRF token for the current session.
 * Called by the frontend on page load; token is included in
 * POST requests to API endpoints.
 *
 * IMPORTANT: This endpoint does NOT require existing CSRF token
 * because it's used to ESTABLISH a session for new users.
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Signal to load-env.php to throw exceptions instead of dying
define('FBCAST_PAGE_CONTEXT', true);

// Enable error reporting for logging only
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Enable SameSite=None for cross-origin requests (needed for some setups)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
}

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET, OPTIONS');
    die(json_encode(['error' => 'Method not allowed']));
}

try {
    require_once __DIR__ . '/config/load-env.php';
    $token = getCsrfToken();
    echo json_encode([
        'token' => $token,
        'session_id' => session_id() ?: null
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    logger('error', 'CSRF token generation failed: ' . $e->getMessage());
    $env = defined('APP_ENV') ? APP_ENV : 'development';
    $debugMsg = ($env !== 'production') ? $e->getMessage() : null;
    $response = ['error' => 'Failed to generate security token'];
    if ($debugMsg) $response['debug'] = $debugMsg;
    die(json_encode($response));
}
