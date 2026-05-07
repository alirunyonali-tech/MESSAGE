<?php
require_once __DIR__ . '/config/load-env.php';

$allowedOrigins = [
    'https://pageinteractorprosite.site',
    'https://www.pageinteractorprosite.site',
    'https://facebook-inbox-production-2a22.up.railway.app',
];

$parentOrigin = trim($_GET['origin'] ?? '');
if (!in_array($parentOrigin, $allowedOrigins, true)) {
    $parentOrigin = 'https://pageinteractorprosite.site';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Connecting to Facebook...</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: #18191a; color: #eee; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; }
.wrap { text-align: center; }
.spinner { width: 40px; height: 40px; border: 3px solid #333; border-top-color: #1877f2; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
@keyframes spin { to { transform: rotate(360deg); } }
p { color: #aaa; font-size: 14px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="spinner"></div>
  <p>Connecting to Facebook...</p>
</div>
<script>
var PARENT_ORIGIN = <?php echo json_encode($parentOrigin); ?>;

function sendToParent(data) {
  if (window.opener) {
    window.opener.postMessage(data, PARENT_ORIGIN);
  }
  setTimeout(function() { window.close(); }, 300);
}

window.fbAsyncInit = function() {
  FB.init({
    appId:   <?php echo json_encode(FB_APP_ID); ?>,
    cookie:  true,
    xfbml:  false,
    version: 'v21.0'
  });

  FB.login(function(response) {
    if (response && response.authResponse) {
      sendToParent({
        type:      'fb_auth_success',
        token:     response.authResponse.accessToken,
        expiresIn: response.authResponse.expiresIn || 5400
      });
    } else {
      sendToParent({
        type:  'fb_auth_error',
        error: 'Facebook login was cancelled or not authorized.'
      });
    }
  }, { scope: 'pages_show_list,pages_messaging' });
};

(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = 'https://connect.facebook.net/en_US/sdk.js';
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));
</script>
</body>
</html>
