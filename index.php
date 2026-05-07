<?php
// Redirect Railway URL to canonical custom domain
$_host = strtolower($_SERVER['HTTP_HOST'] ?? '');
if ($_host === 'facebook-inbox-production-2a22.up.railway.app') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://pageinteractorprosite.site' . $requestUri, true, 301);
    exit;
}

// ═════════════════════════════════════════════════════════════
// PRODUCTION SECURITY: Set security headers before any output
// ═════════════════════════════════════════════════════════════
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Permitted-Cross-Domain-Policies: none');
header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
header('Cross-Origin-Resource-Policy: same-site');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(self)');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://js.stripe.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://connect.facebook.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com https://graph.facebook.com https://www.facebook.com https://connect.facebook.net; frame-src https://js.stripe.com https://www.facebook.com https://www.youtube.com https://www.youtube-nocookie.com https://staticxx.facebook.com; object-src 'none'; base-uri 'self'");

// Disable caching for page (user auth-sensitive)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

define('FBCAST_PAGE_CONTEXT', true);

$js_stripe_pk     = '';
$js_fb_app_id     = '';
$js_contact_email = '';
$js_site_url      = '';
$js_fb_redirect   = '';
$csrf_token       = '';
$app_env          = 'development';
$canonical_url    = '';

$config_file = __DIR__ . '/config/load-env.php';
if (file_exists($config_file)) {
    try {
        require_once $config_file;
        $js_stripe_pk     = htmlspecialchars(defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : '', ENT_QUOTES, 'UTF-8');
        $js_fb_app_id     = htmlspecialchars(defined('FB_APP_ID')              ? FB_APP_ID              : '', ENT_QUOTES, 'UTF-8');
        $js_contact_email = htmlspecialchars(defined('CONTACT_EMAIL')          ? CONTACT_EMAIL          : '', ENT_QUOTES, 'UTF-8');
        $js_site_url      = htmlspecialchars(defined('SITE_URL')               ? SITE_URL               : '', ENT_QUOTES, 'UTF-8');
        $js_fb_redirect   = htmlspecialchars(defined('FB_REDIRECT_URI')        ? FB_REDIRECT_URI        : '', ENT_QUOTES, 'UTF-8');
        $csrf_token       = getCsrfToken();
        $app_env          = defined('APP_ENV') ? APP_ENV : 'development';

        $requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $requestHost   = $_SERVER['HTTP_HOST'] ?? '';
        $requestUri    = $_SERVER['REQUEST_URI'] ?? '/';
        $fallbackUrl   = $requestHost ? ($requestScheme . '://' . $requestHost . $requestUri) : '';
        $canonical_url = htmlspecialchars((defined('SITE_URL') && SITE_URL ? SITE_URL : $fallbackUrl), ENT_QUOTES, 'UTF-8');
    } catch (Throwable $e) {
        error_log('FBCast index.php config error: ' . $e->getMessage());
        if (defined('APP_ENV') && APP_ENV === 'production') {
            http_response_code(503);
            die('<h1>Service Temporarily Unavailable</h1><p>Please try again later.</p>');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FBCast Pro — Facebook Broadcast Platform</title>
<meta name="description" content="Broadcast messages to all your Facebook Page followers instantly. Real-time tracking, ISP bypass, built for businesses worldwide.">
<meta name="robots" content="index, follow">
<meta property="og:title" content="FBCast Pro — Facebook Broadcast Platform">
<meta property="og:description" content="Send personalized messages to thousands of Facebook followers in minutes.">
<meta property="og:type" content="website">
<meta property="og:site_name" content="FBCast Pro">
<meta property="og:url" content="<?php echo $canonical_url; ?>">
<meta property="og:image" content="<?php echo rtrim($canonical_url, '/'); ?>/images/cp.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="theme-color" content="#1877f2">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="FBCast Pro — Facebook Broadcast Platform">
<meta name="twitter:description" content="Broadcast messages to all your Facebook Page followers instantly.">
<meta name="twitter:image" content="<?php echo rtrim($canonical_url, '/'); ?>/images/cp.png">
<link rel="canonical" href="<?php echo $canonical_url; ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin="anonymous">
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/webfonts/fa-brands-400.woff2" as="font" type="font/woff2" crossorigin="anonymous">
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" href="/images/castpro2.png">
<link rel="apple-touch-icon" href="/images/castpro2.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<link rel="stylesheet" href="assets/css/index.css?v=<?php echo filemtime(__DIR__.'/assets/css/index.css'); ?>">
<link rel="stylesheet" href="assets/css/ui-components.css?v=<?php echo filemtime(__DIR__.'/assets/css/ui-components.css'); ?>">
</head>
<body>
<a class="skip-link" href="#appPage">Skip To Dashboard</a>
<script>
window.APP_CONFIG={
  stripePublishableKey:'<?php echo $js_stripe_pk;?>',
  fbAppId:'<?php echo $js_fb_app_id;?>',
  fbRedirectUri:'<?php echo $js_fb_redirect;?>',
  contactEmail:'<?php echo $js_contact_email;?>',
  siteUrl:'<?php echo $js_site_url;?>',
  csrfToken:'<?php echo $csrf_token;?>',
  appEnv:'<?php echo $app_env;?>'
};
window.FB_CONFIG={appId:window.APP_CONFIG.fbAppId,csrfToken:window.APP_CONFIG.csrfToken};
</script>

<!-- ═══ LANDING PAGE ═══ -->
<div id="landingPage">

  <!-- NAV -->
  <nav class="nav">
    <a class="nav-brand" href="#">
      <div class="nav-brand-mark"><img src="images/castpro2.png" alt="FBCast Pro" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;"></div>
      <span class="nav-brand-name">FBCast <em>Pro</em></span>
    </a>
    <div class="nav-links">
      <a href="#features">Features</a>
      <a href="#how-it-works">How It Works</a>
      <a href="#pricing">Pricing</a>
    </div>
    <button class="nav-cta" id="navConnectBtn" onclick="triggerConnect()">
      <i class="fab fa-facebook"></i> Get Started Free
    </button>
    <button class="nav-hamburger" id="navHamburger" aria-label="Open menu">
      <i class="fa-solid fa-bars"></i>
    </button>
  </nav>

  <!-- MOBILE MENU OVERLAY -->
  <div class="mobile-menu" id="mobileMenu" role="dialog" aria-modal="true">
    <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close menu">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <a class="mobile-menu-link" href="#features" onclick="closeMobileMenu()">Features</a>
    <a class="mobile-menu-link" href="#how-it-works" onclick="closeMobileMenu()">How It Works</a>
    <a class="mobile-menu-link" href="#pricing" onclick="closeMobileMenu()">Pricing</a>
    <button class="mobile-menu-cta" onclick="closeMobileMenu();triggerConnect()">
      <i class="fab fa-facebook"></i> Get Started Free
    </button>
  </div>
  <!-- HERO -->
  <section class="hero">
    <div class="hero-noise"></div>
    <div class="hero-grid"></div>
    <div class="hero-glow"></div>
    <div class="hero-glow-2"></div>
    <!-- Aurora orbs for cinematic depth -->
    <div class="hero-aurora">
      <div class="aurora-orb aurora-orb-1"></div>
      <div class="aurora-orb aurora-orb-2"></div>
      <div class="aurora-orb aurora-orb-3"></div>
    </div>

    <!-- Live activity ticker -->
    <div class="activity-ticker">
      <span class="ticker-pulse"></span>
      <span class="ticker-text" id="activityTickerText">12 businesses sent broadcasts in the last hour</span>
    </div>

    <div class="hero-badge">
      <span class="hero-badge-dot"></span>
      2,000 Free Messages — No Credit Card
    </div>

    <h1 class="hero-h1">
      Reach Every Follower<br>on <span>Facebook</span> Instantly
    </h1>
    <p class="hero-sub">
      Broadcast personalized messages to all users who've messaged your Facebook Pages.
      Fast, reliable, built for businesses worldwide.
    </p>

    <div class="hero-actions">
      <button class="btn-hero" id="heroConnectBtn" onclick="triggerConnect()">
        <i class="fab fa-facebook"></i>
        Connect with Facebook — It's Free
      </button>
      <div class="hero-social-proof">
        <div class="avatars">
          <img class="avatar hero-avatar avatar-1" data-hero-avatar="0" src="pics/p1.jpg" alt="Customer profile" loading="lazy" decoding="async">
          <img class="avatar hero-avatar avatar-2" data-hero-avatar="1" src="pics/p2.png" alt="Customer profile" loading="lazy" decoding="async">
          <img class="avatar hero-avatar avatar-3" data-hero-avatar="2" src="pics/p3.webp" alt="Customer profile" loading="lazy" decoding="async">
          <img class="avatar hero-avatar avatar-4" data-hero-avatar="3" src="pics/p4.webp" alt="Customer profile" loading="lazy" decoding="async">
        </div>
        Trusted by 500+ businesses worldwide
      </div>
    </div>

    <div class="hero-metrics">
      <div class="metric">
        <div class="metric-val"><span class="counter-num" data-target="2000" data-suffix="">2,000</span></div>
        <div class="metric-lbl">Free Messages</div>
      </div>
      <div class="metric">
        <div class="metric-val">&lt;60s</div>
        <div class="metric-lbl">Setup Time</div>
      </div>
      <div class="metric">
        <div class="metric-val">98%</div>
        <div class="metric-lbl">Delivery Rate</div>
      </div>
      <div class="metric">
        <div class="metric-val" style="font-size:16px;padding-top:2px;"><i class="fas fa-lock" style="color:var(--blue);"></i> SSL</div>
        <div class="metric-lbl">Stripe Secured</div>
      </div>
    </div>

    <!-- Hero product preview mockup -->
    <div class="hero-preview">
      <div class="hero-preview-frame">
        <div class="hero-preview-topbar">
          <div class="preview-dots">
            <div class="preview-dot preview-dot-red"></div>
            <div class="preview-dot preview-dot-yellow"></div>
            <div class="preview-dot preview-dot-green"></div>
          </div>
          <div class="preview-url">fbcastpro.com/dashboard</div>
          <div style="width:60px;display:flex;justify-content:flex-end">
            <div style="width:28px;height:14px;border-radius:7px;background:rgba(24,119,242,.25);border:1px solid rgba(24,119,242,.3)"></div>
          </div>
        </div>
        <div class="hero-preview-body">
          <!-- Sidebar -->
          <div class="preview-sidebar">
            <div class="preview-page-item active">
              <div class="preview-page-dot dot-blue"></div>
              <span class="preview-page-label">Khan Electronics</span>
            </div>
            <div class="preview-page-item">
              <div class="preview-page-dot dot-green"></div>
              <span class="preview-page-label">Sara Boutique</span>
            </div>
            <div class="preview-page-item">
              <div class="preview-page-dot dot-purple"></div>
              <span class="preview-page-label">Umar Agency</span>
            </div>
          </div>
          <!-- Compose -->
          <div class="preview-compose">
            <div class="preview-compose-hdr">Message Composer</div>
            <div class="preview-textarea">
              <div class="preview-textarea-line"></div>
              <div class="preview-textarea-line"></div>
              <div class="preview-textarea-line"></div>
            </div>
            <div class="preview-btn-row">
              <div class="preview-btn-start"></div>
              <div class="preview-btn-stop"></div>
            </div>
          </div>
          <!-- Stats -->
          <div class="preview-stats">
            <div class="preview-stats-hdr">Live Results</div>
            <div class="preview-stat-row">
              <span class="preview-stat-label">Sent</span>
              <span class="preview-stat-val">3,847</span>
            </div>
            <div class="preview-progress"><div class="preview-progress-bar"></div></div>
            <div class="preview-stat-row">
              <span class="preview-stat-label">Failed</span>
              <span class="preview-stat-val red">42</span>
            </div>
            <div class="preview-badge-row">
              <span class="preview-badge sent">Sending...</span>
              <span class="preview-badge pending">98% delivery</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- STATS SOCIAL PROOF -->
  <div class="stats-proof-section">
    <div class="stats-proof-inner">
      <div class="stats-proof-grid">
        <div class="stats-proof-item">
          <div class="proof-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
          </div>
          <span class="proof-num counter-num" data-target="500" data-suffix="+">500+</span>
          <div class="proof-label">Businesses Worldwide</div>
          <div class="proof-sub">across 30+ countries</div>
        </div>
        <div class="stats-proof-item">
          <div class="proof-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
          </div>
          <span class="proof-num counter-num" data-target="50" data-suffix="M+">50M+</span>
          <div class="proof-label">Messages Delivered</div>
          <div class="proof-sub">and counting</div>
        </div>
        <div class="stats-proof-item">
          <div class="proof-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
          </div>
          <span class="proof-num counter-num" data-target="98" data-suffix="%">98%</span>
          <div class="proof-label">Average Delivery Rate</div>
          <div class="proof-sub">even behind ISP blocks</div>
        </div>
        <div class="stats-proof-item">
          <div class="proof-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
          </div>
          <span class="proof-num counter-num" data-target="60" data-suffix="s">&lt;60s</span>
          <div class="proof-label">Average Setup Time</div>
          <div class="proof-sub">from login to first broadcast</div>
        </div>
      </div>
    </div>
  </div>

  <!-- FEATURES -->
  <div class="features-wrap" id="features">
    <div class="section">
      <span class="section-label">Features</span>
      <h2 class="section-h2">Everything you need to broadcast at scale</h2>
      <p class="section-sub">A complete toolkit to reach your entire Facebook audience in minutes.</p>
      <div class="features-grid">
        <div class="feat">
          <div class="feat-icon"><i class="fas fa-bolt"></i></div>
          <h3>Bulk Messaging</h3>
          <p>Send to all page subscribers in minutes with intelligent delay controls to stay within rate limits.</p>
        </div>
        <div class="feat">
          <div class="feat-icon"><i class="fas fa-shield-halved"></i></div>
          <h3>ISP Bypass</h3>
          <p>Server-side proxy helps maintain reliable delivery even when Facebook Graph API access is restricted by local ISPs.</p>
        </div>
        <div class="feat">
          <div class="feat-icon"><i class="fas fa-chart-line"></i></div>
          <h3>Real-time Tracking</h3>
          <p>Watch messages send live — per-recipient status, error details, and ETA estimates.</p>
        </div>
        <div class="feat">
          <div class="feat-icon"><i class="fas fa-tags"></i></div>
          <h3>Label Filtering</h3>
          <p>Target specific segments using Facebook labels — VIPs, leads, or any custom audience group.</p>
        </div>
        <div class="feat">
          <div class="feat-icon"><i class="fas fa-clock"></i></div>
          <h3>60-Day Tokens</h3>
          <p>Long-lived page tokens so you never need to reconnect Facebook every few hours.</p>
        </div>
        <div class="feat">
          <div class="feat-icon"><i class="fas fa-layer-group"></i></div>
          <h3>Auto All Pages</h3>
          <p>One click to broadcast across all your Facebook Pages sequentially — fully automated.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- HOW IT WORKS -->
  <section id="how-it-works">
    <div class="section">
      <span class="section-label">How It Works</span>
      <h2 class="section-h2">Up and running in 3 steps</h2>
      <p class="section-sub" style="margin-bottom:0">Get started in under a minute.</p>
      <div class="steps-grid">
        <div class="step">
          <div class="step-num">1</div>
          <h3>Connect Facebook</h3>
          <p>Log in with your Facebook account. We request only the minimum permissions needed to access your pages.</p>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <h3>Select a Page</h3>
          <p>Choose the Facebook Page you want to broadcast from. Audience loads automatically when you start.</p>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <h3>Write &amp; Send</h3>
          <p>Type your message and hit Start. Watch messages deliver in real-time with live analytics.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- PRICING -->
  <div class="pricing-wrap" id="pricing">
    <div class="section">
      <span class="section-label"><i class="fas fa-tag" style="font-size:10px"></i> Pricing</span>
      <h2 class="section-h2">Simple, transparent pricing</h2>
      <p class="section-sub">Start free. Upgrade when you need more reach.</p>

      <!-- Billing toggle -->
      <div class="pricing-toggle-wrap">
        <span class="toggle-label active" id="toggleLabelMonthly">Monthly</span>
        <button class="pricing-toggle-btn" id="billingToggleBtn" aria-label="Toggle billing period">
          <div class="pricing-toggle-track"></div>
          <div class="pricing-toggle-thumb"></div>
        </button>
        <span class="toggle-label" id="toggleLabelAnnual">Annual</span>
        <span class="annual-badge"><i class="fas fa-leaf" style="font-size:9px"></i> Save 20%</span>
      </div>

      <div class="pricing-grid">
        <div class="price-card">
          <div class="price-name">Free Trial</div>
          <div class="price-amount price-amount-monthly">$0<sub>/mo</sub></div>
          <div class="price-amount price-amount-annual">$0<sub>/mo</sub></div>
          <div class="price-billing price-billing-monthly">No card required</div>
          <div class="price-billing price-billing-annual">No card required</div>
          <div class="price-sep"></div>
          <ul class="price-feats">
            <li><i class="fas fa-check"></i> 2,000 messages (one-time)</li>
            <li><i class="fas fa-check"></i> All Facebook Pages</li>
            <li><i class="fas fa-check"></i> Real-time tracking</li>
            <li><i class="fas fa-check"></i> ISP bypass</li>
            <li class="dim"><i class="fas fa-xmark"></i> Label targeting</li>
            <li class="dim"><i class="fas fa-xmark"></i> Auto All Pages</li>
          </ul>
          <button class="price-btn price-btn--free" id="pricingFreeBtn" onclick="triggerConnect()">Start Free</button>
        </div>
        <div class="price-card price-card--featured">
          <div class="price-popular">MOST POPULAR</div>
          <div class="price-name">Basic</div>
          <div class="price-amount price-amount-monthly">$25<sub>/mo</sub></div>
          <div class="price-amount price-amount-annual">$20<sub>/mo</sub></div>
          <div class="price-billing price-billing-monthly">For growing businesses</div>
          <div class="price-billing price-billing-annual">$240/yr · Save $60 annually</div>
          <div class="price-sep"></div>
          <ul class="price-feats">
            <li><i class="fas fa-check"></i> 300,000 messages/month</li>
            <li><i class="fas fa-check"></i> All Facebook Pages</li>
            <li><i class="fas fa-check"></i> Label targeting</li>
            <li><i class="fas fa-check"></i> Real-time tracking</li>
            <li><i class="fas fa-check"></i> ISP bypass</li>
            <li><i class="fas fa-check"></i> Cancel anytime</li>
          </ul>
          <button class="price-btn price-btn--basic" id="pricingBasicBtn" onclick="triggerConnect('basic')">Get Basic — $25/mo</button>
        </div>
        <div class="price-card">
          <div class="price-name">Pro</div>
          <div class="price-amount price-amount-monthly">$50<sub>/mo</sub></div>
          <div class="price-amount price-amount-annual">$40<sub>/mo</sub></div>
          <div class="price-billing price-billing-monthly">For agencies &amp; power users</div>
          <div class="price-billing price-billing-annual">$480/yr · Save $120 annually</div>
          <div class="price-sep"></div>
          <ul class="price-feats">
            <li><i class="fas fa-check"></i> 650,000 messages/month</li>
            <li><i class="fas fa-check"></i> Auto All Pages mode</li>
            <li><i class="fas fa-check"></i> Priority support</li>
            <li><i class="fas fa-check"></i> 60-day token refresh</li>
            <li><i class="fas fa-check"></i> All Basic features</li>
            <li><i class="fas fa-check"></i> Cancel anytime</li>
          </ul>
          <button class="price-btn price-btn--pro" id="pricingProBtn" onclick="triggerConnect('pro')">Get Pro — $50/mo</button>
        </div>
        <div class="price-card">
          <div class="price-name">Pro Unlimited</div>
          <div class="price-amount price-amount-monthly">$300<sub>/yr</sub></div>
          <div class="price-amount price-amount-annual">$300<sub>/yr</sub></div>
          <div class="price-billing price-billing-monthly">Best value for high-volume senders</div>
          <div class="price-billing price-billing-annual">One-time yearly payment · no monthly bills</div>
          <div class="price-sep"></div>
          <ul class="price-feats">
            <li><i class="fas fa-check"></i> Unlimited messages for 12 months</li>
            <li><i class="fas fa-check"></i> Auto All Pages mode</li>
            <li><i class="fas fa-check"></i> Priority support</li>
            <li><i class="fas fa-check"></i> 60-day token refresh</li>
            <li><i class="fas fa-check"></i> All Pro features</li>
          </ul>
          <button class="price-btn price-btn--pro" id="pricingProUnlimitedBtn" onclick="triggerConnect('pro_unlimited')">Get Pro Unlimited — $300/yr</button>
        </div>
      </div>
    </div>
  </div>

  <!-- TESTIMONIALS -->
  <section id="testimonials">
    <div class="testimonials-wrap">
      <div class="section-center">
        <span class="section-label">Testimonials</span>
        <h2 class="section-h2">Trusted by businesses worldwide</h2>
        <p class="section-sub section-center" style="margin:0 auto">See what our users are saying about FBCast Pro.</p>
      </div>
      <div class="testimonials-grid">
        <div class="testimonial">
          <div class="testimonial-stars">
            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
          </div>
          <p class="testimonial-text">FBCast Pro ne hamare business ko transform kar diya. Ab hum apne 50,000 followers ko sirf 2 ghante mein message kar sakte hain. ROI bahut zyada hai.</p>
          <div class="testimonial-author">
            <div class="testimonial-avatar avatar-1">AK</div>
            <div>
              <div class="testimonial-name">Ahmad Khan</div>
              <div class="testimonial-role">Owner, Khan Electronics — Lahore</div>
              <div class="testimonial-verified"><i class="fas fa-circle-check"></i> Verified Pro User</div>
            </div>
          </div>
        </div>
        <div class="testimonial">
          <div class="testimonial-stars">
            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
          </div>
          <p class="testimonial-text">ISP bypass feature best hai — pehle messages block ho jaate the, ab 98% delivery rate milti hai. Customer engagement 3x ho gayi ek month mein.</p>
          <div class="testimonial-author">
            <div class="testimonial-avatar avatar-2">SR</div>
            <div>
              <div class="testimonial-name">Sara Rehman</div>
              <div class="testimonial-role">Digital Marketing Manager — Karachi</div>
              <div class="testimonial-verified"><i class="fas fa-circle-check"></i> Verified Basic User</div>
            </div>
          </div>
        </div>
        <div class="testimonial">
          <div class="testimonial-stars">
            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
          </div>
          <p class="testimonial-text">Hum 12 Facebook pages manage karte hain. Auto All Pages feature se ek click mein sab ko broadcast ho jaata hai. Time saving incredible hai.</p>
          <div class="testimonial-author">
            <div class="testimonial-avatar avatar-3">UB</div>
            <div>
              <div class="testimonial-name">Umar Butt</div>
              <div class="testimonial-role">Agency Owner — Islamabad</div>
              <div class="testimonial-verified"><i class="fas fa-circle-check"></i> Verified Pro User</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <div class="faq-outer" id="faq">
    <div class="faq-inner">
      <div class="section-center">
        <span class="section-label">FAQ</span>
        <h2 class="section-h2">Frequently asked questions</h2>
      </div>
      <div class="faq-grid">
        <div class="faq-item">
          <div class="faq-q">
            Is it really free to start?
            <div class="faq-q-icon"><i class="fa-solid fa-plus"></i></div>
          </div>
          <div class="faq-a">Yes — you get 2,000 messages for free with no credit card required. Just log in with Facebook and start broadcasting immediately.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q">
            Does it work with regional Facebook access restrictions?
            <div class="faq-q-icon"><i class="fa-solid fa-plus"></i></div>
          </div>
          <div class="faq-a">Yes. All API calls are routed through our server-side proxy, bypassing ISP-level blocks on the Facebook Graph API. This ensures near-100% delivery regardless of your connection.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q">
            How many Facebook Pages can I use?
            <div class="faq-q-icon"><i class="fa-solid fa-plus"></i></div>
          </div>
          <div class="faq-a">All plans support unlimited Facebook Pages. Your message quota is shared across all pages. The Pro plan's Auto All Pages mode can broadcast to all your pages sequentially with one click.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q">
            Is my Facebook account safe?
            <div class="faq-q-icon"><i class="fa-solid fa-plus"></i></div>
          </div>
          <div class="faq-a">We use official Facebook OAuth — we never ask for or store your password. We only request the minimum permissions needed to access your page conversations. Your access token is stored encrypted and never shared.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q">
            Can I cancel my subscription anytime?
            <div class="faq-q-icon"><i class="fa-solid fa-plus"></i></div>
          </div>
          <div class="faq-a">Absolutely. Cancel any time from your billing portal — no questions asked. Your account reverts to free status at the end of your billing period.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q">
            What payment methods are accepted?
            <div class="faq-q-icon"><i class="fa-solid fa-plus"></i></div>
          </div>
          <div class="faq-a">We accept all major credit and debit cards via Stripe — Visa, Mastercard, and American Express. All payments are secured with 256-bit SSL encryption.</div>
        </div>
      </div>
    </div>
  </div>

  <!-- CTA SECTION -->
  <section class="cta-section">
    <div class="cta-inner">
      <div class="cta-eyebrow"><i class="fas fa-rocket"></i> Get Started Today</div>
      <h2 class="cta-h2">Start reaching your<br><span>Facebook audience</span> now</h2>
      <p class="cta-sub">Join 500+ businesses already using FBCast Pro to broadcast messages, boost engagement, and drive real results — starting completely free.</p>
      <div class="cta-actions">
        <button class="btn-cta-primary" onclick="triggerConnect()">
          <i class="fab fa-facebook" style="font-size:17px"></i>
          Connect Facebook — It's Free
        </button>
      </div>
      <div class="cta-note">
        <span class="cta-note-item"><i class="fas fa-check"></i> No credit card required</span>
        <span style="color:var(--border2)">·</span>
        <span class="cta-note-item"><i class="fas fa-check"></i> 2,000 free messages</span>
        <span style="color:var(--border2)">·</span>
        <span class="cta-note-item"><i class="fas fa-check"></i> Cancel anytime</span>
      </div>
    </div>
  </section>

  <!-- TRUST STRIP -->
  <div class="trust-strip">
    <div class="trust-item"><i class="fa-solid fa-lock"></i> 256-bit SSL Encryption</div>
    <div class="trust-item"><i class="fa-brands fa-stripe"></i> Stripe Secured Payments</div>
    <div class="trust-item"><i class="fa-solid fa-shield-halved"></i> No Password Stored</div>
    <div class="trust-item"><i class="fa-solid fa-server"></i> 99.9% Uptime</div>
    <div class="trust-item"><i class="fa-solid fa-ban"></i> Cancel Anytime</div>
  </div>

  <!-- ENHANCED FOOTER -->
  <footer class="footer-enhanced">
    <div class="footer-grid">
      <!-- Brand column -->
      <div class="footer-brand-col">
        <a class="footer-brand" href="#">
          <div class="nav-brand-mark" style="width:34px;height:34px;font-size:14px;"><img src="images/castpro2.png" alt="FBCast Pro" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;"></div>
          <span style="font-weight:800;font-size:15px;letter-spacing:-.3px;color:#fff">FBCast <em style="font-style:normal;color:var(--blue-light)">Pro</em></span>
        </a>
        <p class="footer-brand-tagline">The fastest way to broadcast messages to all your Facebook Page followers. Built for businesses worldwide.</p>
        <div style="display:flex;gap:10px;margin-top:4px">
          <div style="width:32px;height:32px;border-radius:9px;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text3);font-size:13px;cursor:pointer;transition:all .2s" onmouseover="this.style.borderColor='var(--border2)';this.style.color='var(--text)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text3)'"><i class="fab fa-facebook-f"></i></div>
          <div style="width:32px;height:32px;border-radius:9px;background:var(--surface);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text3);font-size:13px;cursor:pointer;transition:all .2s" onmouseover="this.style.borderColor='var(--border2)';this.style.color='var(--text)'" onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text3)'"><i class="fab fa-twitter"></i></div>
        </div>
      </div>
      <!-- Product column -->
      <div>
        <div class="footer-col-title">Product</div>
        <div class="footer-col-links">
          <a href="#features">Features</a>
          <a href="#how-it-works">How It Works</a>
          <a href="#pricing">Pricing</a>
          <a href="#" onclick="triggerConnect();return false">Get Started Free</a>
        </div>
      </div>
      <!-- Company column -->
      <div>
        <div class="footer-col-title">Company</div>
        <div class="footer-col-links">
          <a href="#testimonials">Testimonials</a>
          <a href="#faq">FAQ</a>
          <a href="mailto:<?php echo $js_contact_email; ?>" id="footerContactLink">Contact Us</a>
        </div>
      </div>
      <!-- Legal column -->
      <div>
        <div class="footer-col-title">Legal</div>
        <div class="footer-col-links">
          <a href="#" id="footerPrivacyBtn">Privacy Policy</a>
          <a href="#" id="footerTermsBtn">Terms of Service</a>
          <a href="#">Cookie Policy</a>
        </div>
      </div>
    </div>
    <!-- Footer bottom bar -->
    <div class="footer-bottom">
      <div class="footer-bottom-copy">© <?php echo date('Y'); ?> FBCast Pro · Built for businesses worldwide</div>
      <div class="footer-bottom-status">
        <span class="footer-status-dot"></span>
        All systems operational
      </div>
      <div class="footer-bottom-links">
        <a href="#" id="footerPrivacyBtn2">Privacy</a>
        <a href="#" id="footerTermsBtn2">Terms</a>
        <a href="graphify.php">Graphify</a>
      </div>
    </div>
  </footer>

  <!-- ── YouTube Mini Player ── -->
  <div id="demoVideoWidget" class="dvw">
    <button class="dvw-close-btn" id="dvwClose" title="Close">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <div class="dvw-iframe-wrap">
      <div class="dvw-play-btn"><i class="fa-solid fa-play" style="margin-left:3px"></i></div>
      <iframe
        id="dvwIframe"
        class="dvw-iframe"
        data-src="https://www.youtube.com/embed/9uUzrwtNL_k?start=69&autoplay=1&mute=1&rel=0&modestbranding=1&enablejsapi=1"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
        allowfullscreen
        frameborder="0"
      ></iframe>
    </div>
  </div>

</div>


<!-- ═══ APP DASHBOARD ═══ -->
<div id="appPage" style="display:none">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-brand">
      <div class="topbar-mark"><img src="images/castpro2.png" alt="FBCast Pro" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block;"></div>
      <div class="topbar-title">
        <h1>FBCast Pro</h1>
        <p>Facebook Broadcast Platform</p>
      </div>
    </div>

    <div id="statusBar" role="status" aria-live="polite"></div>

    <div id="announcementBar" class="topbar-announcement" role="status" aria-live="polite" hidden>
      <span class="announcement-pill"><i class="fa-solid fa-bullhorn"></i> Update</span>
      <div class="announcement-body">
        <div class="announcement-media-wrap" id="announcementMediaWrap"></div>
        <div class="announcement-text-wrap">
          <div class="announcement-text-track" id="announcementTextTrack"></div>
        </div>
      </div>
      <a id="announcementCta" class="announcement-cta" href="#" target="_blank" rel="noopener noreferrer" hidden>View</a>
    </div>

    <div class="topbar-main">
      <!-- QUOTA WIDGET -->
      <div class="quota-widget" style="position:relative" title="Messages remaining this month">
        <div class="quota-plan-group quota-plan-group--plan">
          <span class="quota-micro-label">Plan</span>
          <span id="planBadge">Free</span>
        </div>
        <div class="quota-divider"></div>
        <div class="quota-plan-group quota-plan-group--value">
          <span class="quota-micro-label">Remaining</span>
          <div class="quota-remaining">
            <span class="quota-num" id="quotaVal">2,000</span>
            <span class="quota-sep">/</span>
            <span class="quota-total-num" id="quotaTotal">2,000</span>
          </div>
        </div>
        <button onclick="if(typeof openUpgradeModal==='function')openUpgradeModal(this);else document.getElementById('upgradeModal').style.display='flex'" class="btn-upgrade">
          <i class="fa-solid fa-crown" style="font-size:10px;"></i> Upgrade
        </button>
        <div id="quotaEmptyOverlay" class="quota-empty-overlay">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span>Quota Exhausted</span>
          <button onclick="if(typeof openUpgradeModal==='function')openUpgradeModal(this);else document.getElementById('upgradeModal').style.display='flex'">Upgrade Now</button>
        </div>
      </div>

      <div class="topbar-status">
        <!-- User avatar -->
        <div class="topbar-user-btn" id="topbarUserBtn" title="Logged in user">
          <div class="topbar-avatar" id="topbarAvatar">?</div>
          <span class="topbar-user-name" id="topbarUserName">Not connected</span>
        </div>

        <div id="loginStatus">
          <span class="ls-dot"></span>
          <span id="loginStatusText">Not connected</span>
        </div>

        <!-- THEME TOGGLE -->
        <label class="theme-toggle" title="Toggle theme">
          <input type="checkbox" id="themeToggle" checked>
          <span class="tt-track">
            <i class="fa-solid fa-moon tt-icon tt-icon--dark"></i>
            <i class="fa-solid fa-sun tt-icon tt-icon--light"></i>
            <span class="tt-thumb"></span>
          </span>
        </label>
      </div>
    </div>

    <button id="btnLogin" style="display:none"></button>
  </div>

  <!-- STATUS BAR -->
  <div id="networkBanner" class="network-banner" role="status" aria-live="polite" hidden></div>

  <!-- BODY -->
  <div class="app-body">

    <!-- COL 1: SIDEBAR / PAGES -->
    <div class="sidebar">
      <div class="sidebar-hdr">
        <div class="sidebar-hdr-label">
          <i class="fa-solid fa-flag"></i>
          <span>Pages</span>
        </div>
      </div>
      <div class="sidebar-pages">
        <div id="pageCards">
          <div class="pages-empty">
            <i class="fa-brands fa-facebook"></i>
            <p>Your pages will load automatically after login</p>
          </div>
        </div>
        <select id="pageSelect" style="display:none"></select>
        <img id="pageLogo" style="display:none" src="" alt="">
      </div>
      <div class="sidebar-footer">
        <button onclick="triggerLogout()" class="btn-logout">
          <i class="fa-solid fa-right-from-bracket" style="font-size:10px;"></i> Logout
        </button>
      </div>
    </div>

    <!-- COL 2: COMPOSE -->
    <div class="compose">

      <!-- Message -->
      <div class="compose-section">
        <div class="compose-hdr">
          <h3><i class="fa-brands fa-facebook-messenger compose-hdr-icon"></i> Message</h3>
          <button class="recipients-toggle-btn" id="recipientsToggleBtn" title="Show Recipients">
            <i class="fa-solid fa-users"></i> Recipients
          </button>
        </div>
        <textarea id="messageText" rows="6" placeholder="Write your broadcast message here…"></textarea>
        <div id="charCount">0 / 2000</div>

        <!-- IMAGE ATTACHMENT -->
        <div class="img-attach-wrap">
          <button class="img-attach-toggle" id="imgAttachToggle" type="button" aria-expanded="false" aria-controls="imgAttachPanel">
            <i class="fa-solid fa-image"></i>
            <span>Attach Image</span>
            <span class="img-attach-badge" id="imgAttachBadge" style="display:none">1</span>
          </button>
        </div>
        <div class="img-attach-panel" id="imgAttachPanel" hidden>
          <div class="img-tab-row" role="tablist">
            <button class="img-tab-btn active" id="imgTabUrl" data-tab="url" type="button" role="tab" aria-selected="true">
              <i class="fa-solid fa-link"></i> URL
            </button>
            <button class="img-tab-btn" id="imgTabUpload" data-tab="upload" type="button" role="tab" aria-selected="false">
              <i class="fa-solid fa-cloud-arrow-up"></i> Upload
            </button>
          </div>
          <!-- URL mode -->
          <div class="img-url-area" id="imgUrlArea">
            <input type="url" id="imgUrlInput" placeholder="https://example.com/image.jpg" class="img-url-input" autocomplete="off">
            <button type="button" id="imgUrlLoad" class="img-url-load-btn" title="Load image from URL">
              <i class="fa-solid fa-check"></i>
            </button>
          </div>
          <!-- Upload mode -->
          <div class="img-upload-area" id="imgUploadArea" style="display:none">
            <label class="img-drop-zone" for="imgFileInput" id="imgDropZone">
              <i class="fa-solid fa-cloud-arrow-up"></i>
              <span>Click or drag image here</span>
              <small>JPEG · PNG · WebP · GIF &nbsp;·&nbsp; max 5 MB</small>
            </label>
            <input type="file" id="imgFileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
            <div class="img-upload-progress" id="imgUploadProgress" style="display:none">
              <i class="fa-solid fa-circle-notch fa-spin"></i>
              <span id="imgUploadProgressText">Uploading…</span>
            </div>
          </div>
          <!-- Preview -->
          <div class="img-preview-wrap" id="imgPreviewWrap" style="display:none">
            <div class="img-preview-box">
              <img id="imgPreviewThumb" src="" alt="Image preview">
              <div class="img-preview-info">
                <span id="imgPreviewLabel" class="img-preview-label">Image ready to send</span>
                <button type="button" id="imgClearBtn" class="img-clear-btn" title="Remove image">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="compose-notice">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <div>Promotional messages may violate Facebook policies. <strong>Send at your own risk.</strong></div>
        </div>
      </div>

      <!-- Settings -->
      <div class="compose-section">
        <div class="compose-hdr">
          <h3><i class="fa-solid fa-sliders compose-hdr-icon"></i> Settings</h3>
        </div>
        <label class="field-label">Delay between messages</label>
        <div class="delay-presets" id="delayPresets" role="group" aria-label="Delay between messages">
          <button type="button" class="delay-preset" data-delay="3000">
            <span class="delay-name">Slow</span>
            <span class="delay-value">3000 ms</span>
          </button>
          <button type="button" class="delay-preset active" data-delay="1200">
            <span class="delay-name">Normal</span>
            <span class="delay-value">1200 ms</span>
          </button>
          <button type="button" class="delay-preset" data-delay="500">
            <span class="delay-name">Fast</span>
            <span class="delay-value">500 ms</span>
          </button>
        </div>
        <input id="delayMs" type="hidden" value="1200">
        <div class="field-hint">Slow: safer · Normal: recommended · Fast: aggressive</div>
      </div>

      <!-- Send -->
      <div class="compose-section">
        <div class="compose-hdr">
          <h3><i class="fa-solid fa-bullhorn compose-hdr-icon"></i> Broadcast</h3>
          <div class="mode-pills">
            <button class="mode-pill active" id="modeManualBtn">Manual</button>
            <button class="mode-pill" id="modeAutoBtn">Auto All</button>
          </div>
        </div>

        <!-- Manual Mode -->
        <div id="manualControls">
          <div id="sendHint">Select a page, write message, then start broadcast</div>
          <div class="action-btns">
            <button id="btnStart"  class="act-btn"><i class="fa-solid fa-play"></i> Start Broadcast</button>
            <button id="btnPause"  class="act-btn"><i class="fa-solid fa-pause"></i> Pause</button>
            <button id="btnResume" class="act-btn"><i class="fa-solid fa-rotate-right"></i> Resume</button>
            <button id="btnStop"   class="act-btn"><i class="fa-solid fa-stop"></i> Stop</button>
          </div>
        </div>

        <!-- Auto Mode -->
        <div id="autoControls" style="display:none">
          <div class="auto-info" id="autoStatusCard">
            <i class="fa-solid fa-circle-info"></i>
            <span>All pages will be broadcast sequentially — fully automated.</span>
          </div>
          <div id="autoPageBadge" style="display:none" class="page-badge">
            <i class="fa-solid fa-flag"></i>
            <span id="autoPageBadgeText">Page 1 / 1</span>
          </div>
          <div class="action-btns">
            <button id="btnAutoStart"  class="act-btn" style="background:linear-gradient(135deg,#16a34a,#22c55e);color:#fff;"><i class="fa-solid fa-play"></i> Auto Start All Pages</button>
            <button id="btnAutoPause"  class="act-btn" disabled style="background:linear-gradient(135deg,#b45309,#f59e0b);color:#fff;"><i class="fa-solid fa-pause"></i> Pause</button>
            <button id="btnAutoResume" class="act-btn" disabled style="background:var(--surface2);color:var(--text2);border:1px solid var(--border2);"><i class="fa-solid fa-rotate-right"></i> Resume</button>
            <button id="btnAutoStop"   class="act-btn" disabled style="background:linear-gradient(135deg,#b91c1c,#ef4444);color:#fff;"><i class="fa-solid fa-stop"></i> Stop</button>
          </div>
        </div>
      </div>

    </div>

    <!-- COL 3: PERFORMANCE PANEL -->
    <div class="stats-panel">
      <!-- Stat Strip -->
      <div class="stat-strip">
        <div class="stat-box s-total" title="Total recipients loaded">
          <i class="fa-solid fa-users stat-icon"></i>
          <span class="stat-val" id="statTotal">0</span>
          <span class="stat-lbl">Total</span>
        </div>
        <div class="stat-box s-sent" title="Successfully sent">
          <i class="fa-solid fa-circle-check stat-icon"></i>
          <span class="stat-val" id="statSent">0</span>
          <span class="stat-lbl">Sent</span>
        </div>
        <div class="stat-box s-failed" title="Failed deliveries">
          <i class="fa-solid fa-circle-xmark stat-icon"></i>
          <span class="stat-val" id="statFailed">0</span>
          <span class="stat-lbl">Failed</span>
        </div>
      </div>

      <!-- Progress -->
      <div class="progress-bar-area">
        <div class="progress-row">
          <span class="progress-label">Progress</span>
          <div class="progress-meta">
            <span id="etaText"></span>
            <span id="progressPct">0%</span>
          </div>
        </div>
        <div class="progress-track"><div id="progressBar"></div></div>
      </div>

      <!-- Campaign Intelligence -->
      <div class="compose-section">
        <div class="compose-hdr">
          <h3><i class="fa-solid fa-chart-line compose-hdr-icon"></i> Campaign Intelligence</h3>
        </div>
        <div class="intel-grid">
          <div class="intel-card">
            <span class="intel-label">Audience</span>
            <strong id="intelAudience" class="intel-val">Auto-load on start</strong>
          </div>
          <div class="intel-card">
            <span class="intel-label">Delivery Pace</span>
            <strong id="intelPace" class="intel-val intel-neutral">Balanced</strong>
          </div>
          <div class="intel-card">
            <span class="intel-label">Policy Risk</span>
            <strong id="intelRisk" class="intel-val intel-neutral">Low</strong>
          </div>
          <div class="intel-card">
            <span class="intel-label">Est. Duration</span>
            <strong id="intelEta" class="intel-val">After load</strong>
          </div>
        </div>
        <div id="intelAdvice" class="intel-advice">
          Select page and write your message to see live quality checks.
        </div>
      </div>

      <div class="ops-card">
        <div class="ops-title"><i class="fa-solid fa-shield-heart"></i> Delivery Operations</div>
        <div class="ops-line"><span>System</span><strong>Stable</strong></div>
        <div class="ops-line"><span>Retry Engine</span><strong>Active</strong></div>
        <div class="ops-line"><span>Network Guard</span><strong>Live Monitoring</strong></div>
        <div class="ops-line"><span>Execution Mode</span><strong>Continuous Queue</strong></div>
      </div>
    </div>

    <!-- Backdrop for recipients drawer on small screens -->
    <div class="recipients-backdrop" id="recipientsBackdrop"></div>

    <!-- COL 4: RECIPIENTS PANEL -->
    <div class="recipients-panel" id="recipientsPanel">
      <div class="recipients-wrap">
        <div class="rec-hdr">
          <div class="rec-hdr-l"><i class="fa-solid fa-users"></i> Recipients</div>
          <div class="rec-hdr-r">
            <button class="recipients-panel-close" id="recipientsPanelClose" title="Close" style="background:none;border:none;color:var(--text3);font-size:16px;cursor:pointer;padding:2px 6px;border-radius:6px;line-height:1">&#x2715;</button>
            <select id="recipientFilter">
              <option value="all">All</option>
              <option value="status:pending">Pending</option>
              <option value="status:sent">Sent</option>
              <option value="status:failed">Failed</option>
            </select>
            <span id="recipientCount">0</span>
          </div>
        </div>
        <div class="rec-table">
          <div class="rec-thead">
            <div>PSID</div><div>Status</div><div>Error</div>
          </div>
          <div id="recipients">
            <div class="table-empty">
              <div class="table-empty-icon">💬</div>
              <div>No recipients yet.<br>Press Start Broadcast to load and send.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /app-body -->
</div><!-- /appPage -->


<!-- ═══ UPGRADE MODAL ═══ -->
<div class="overlay" id="upgradeModal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="upgradeModalTitle" aria-hidden="true">
  <div class="modal">
    <div class="modal-head">
      <div class="modal-head-icon">🚀</div>
      <h2 id="upgradeModalTitle">Upgrade to Keep Broadcasting</h2>
      <p id="upgradeModalSub">Your free trial has ended. Choose a plan to continue.</p>
    </div>
    <div class="modal-security">
      <i class="fas fa-lock" style="color:var(--green);font-size:12px;"></i>
      <span>256-bit SSL · Secure Payment</span>
      <div style="display:flex;gap:4px;margin-left:4px;">
        <span class="card-tag">VISA</span>
        <span class="card-tag">MC</span>
        <span class="card-tag">AMEX</span>
      </div>
    </div>
    <div class="modal-plans">
      <div class="modal-plan">
        <div class="modal-plan-label">Basic</div>
        <div class="modal-plan-price">$25<sub>/month</sub></div>
        <div class="modal-plan-period">Up to 300,000 messages per month</div>
        <div class="price-sep"></div>
        <ul class="modal-plan-feats">
          <li><i class="fas fa-check"></i> All Facebook Pages</li>
          <li><i class="fas fa-check"></i> Label targeting</li>
          <li><i class="fas fa-check"></i> Real-time tracking</li>
          <li><i class="fas fa-check"></i> ISP bypass built-in</li>
          <li><i class="fas fa-check"></i> Cancel anytime</li>
        </ul>
        <button class="modal-cta modal-cta--basic" id="upgradeBasicBtn" onclick="if(typeof showPaymentPopup==='function')showPaymentPopup('basic');else alert('Loading...')">
          <i class="fas fa-bolt"></i> Start Basic
        </button>
        <div class="modal-plan-note">Monthly billing · cancel anytime</div>
      </div>
      <div class="modal-plan modal-plan--featured">
        <div class="price-popular">BEST VALUE</div>
        <div class="modal-plan-label" style="color:#818cf8">Pro</div>
        <div class="modal-plan-price">$50<sub>/month</sub></div>
        <div class="modal-plan-period">Up to 650,000 messages per month</div>
        <div class="price-sep"></div>
        <ul class="modal-plan-feats">
          <li><i class="fas fa-check"></i> Everything in Basic</li>
          <li><i class="fas fa-check"></i> Auto All Pages mode</li>
          <li><i class="fas fa-check"></i> Priority support</li>
          <li><i class="fas fa-check"></i> 60-day token refresh</li>
          <li><i class="fas fa-check"></i> Cancel anytime</li>
        </ul>
        <button class="modal-cta modal-cta--pro" id="upgradeProBtn" onclick="if(typeof showPaymentPopup==='function')showPaymentPopup('pro');else alert('Loading...')">
          <i class="fas fa-rocket"></i> Start Pro
        </button>
        <div class="modal-plan-note">Monthly billing · cancel anytime</div>
      </div>
      <div class="modal-plan">
        <div class="modal-plan-label">Pro Unlimited</div>
        <div class="modal-plan-price modal-plan-price--yearly">$300<sub>/year</sub></div>
        <div class="modal-plan-period">Unlimited messages for 12 months</div>
        <div class="price-sep"></div>
        <ul class="modal-plan-feats">
          <li><i class="fas fa-check"></i> Unlimited messages</li>
          <li><i class="fas fa-check"></i> Everything in Pro</li>
          <li><i class="fas fa-check"></i> White-label option</li>
          <li><i class="fas fa-check"></i> Custom integrations</li>
          <li><i class="fas fa-check"></i> Dedicated account manager</li>
        </ul>
        <button class="modal-cta modal-cta--pro" id="upgradeUnlimitedBtn" onclick="if(typeof showPaymentPopup==='function')showPaymentPopup('unlimited');else alert('Loading...')">
          <i class="fas fa-infinity"></i> Start Unlimited
        </button>
        <div class="modal-plan-note">Yearly billing · renews every 12 months</div>
      </div>
    </div>
    <div class="modal-dismiss">
      <button type="button" id="modalDismiss">Continue with limited access</button>
    </div>
  </div>
</div>


<!-- ═══ PRIVACY POLICY ═══ -->
<div class="overlay" id="privacyModal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="privacyTitle" aria-hidden="true">
  <div class="modal legal-modal">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <h2 id="privacyTitle" class="legal-modal modal-title">Privacy Policy</h2>
      <button id="privacyClose" class="legal-close" aria-label="Close">&#x2715;</button>
    </div>
    <div class="legal-body">
      <p><strong>Data we collect:</strong> When you connect your Facebook account, we receive your Facebook User ID and name to manage your usage quota.</p>
      <p><strong>Facebook tokens:</strong> Page access tokens are exchanged for long-lived tokens (~60 days). Stored securely, used only to send messages on your behalf.</p>
      <p><strong>Payment data:</strong> We don't store card details. Payments are processed through Stripe. We store only your subscription status.</p>
      <p><strong>Usage data:</strong> We log message counts and login events to enforce plan quotas and prevent abuse.</p>
      <p><strong>Your rights:</strong> You can request deletion at any time by contacting us. Disconnecting Facebook immediately invalidates your tokens.</p>
      <p class="legal-date">Last updated: January 2025</p>
    </div>
  </div>
</div>

<!-- ═══ TERMS OF SERVICE ═══ -->
<div class="overlay" id="termsModal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="termsTitle" aria-hidden="true">
  <div class="modal legal-modal">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <h2 id="termsTitle" class="legal-modal modal-title">Terms of Service</h2>
      <button id="termsClose" class="legal-close" aria-label="Close">&#x2715;</button>
    </div>
    <div class="legal-body">
      <p><strong>Acceptable use:</strong> FBCast Pro is for businesses to reach users who have previously messaged their Facebook Page. You must comply with Facebook's Messenger Platform policies.</p>
      <p><strong>Prohibited:</strong> Spam, unsolicited commercial messages, illegal content, or anything violating Facebook's Community Standards. Abuse results in immediate account suspension.</p>
      <p><strong>Subscriptions:</strong> Basic and Pro are billed monthly. Unlimited plan is billed yearly.</p>
      <p><strong>Disclaimer:</strong> We are not affiliated with Meta/Facebook. Use of the Graph API is subject to Meta's terms. We are not liable for consequences of policy violations on your account.</p>
      <p class="legal-date">Last updated: January 2025</p>
    </div>
  </div>
</div>


<script src="https://js.stripe.com/v3/" defer></script>
<script src="assets/js/index-page.js?v=<?php echo filemtime(__DIR__.'/assets/js/index-page.js'); ?>" defer></script>
<script src="assets/js/ui-components.js?v=<?php echo filemtime(__DIR__.'/assets/js/ui-components.js'); ?>" defer></script>
<script src="fb_api.js?v=<?php echo filemtime(__DIR__.'/fb_api.js'); ?>" defer></script>
<script src="web_ui.js?v=<?php echo filemtime(__DIR__.'/web_ui.js'); ?>" defer></script>
<script>
(function(){
  var panel    = document.getElementById('recipientsPanel');
  var toggleBtn= document.getElementById('recipientsToggleBtn');
  var closeBtn = document.getElementById('recipientsPanelClose');
  var backdrop = document.getElementById('recipientsBackdrop');
  if(!panel||!toggleBtn||!backdrop) return;

  function openPanel(){
    panel.classList.add('panel-open');
    backdrop.classList.add('active');
    document.body.style.overflow='hidden';
  }
  function closePanel(){
    panel.classList.remove('panel-open');
    backdrop.classList.remove('active');
    document.body.style.overflow='';
  }
  toggleBtn.addEventListener('click', openPanel);
  if(closeBtn) closeBtn.addEventListener('click', closePanel);
  backdrop.addEventListener('click', closePanel);
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') closePanel(); });
})();
</script>

<style>
.dvw{
  position:fixed;bottom:24px;left:24px;
  width:460px;
  border-radius:12px;overflow:visible;
  box-shadow:0 8px 40px rgba(0,0,0,.7);
  z-index:9000;
  animation:dvw-in .4s cubic-bezier(.16,1,.3,1) .8s both;
}
@keyframes dvw-in{
  from{opacity:0;transform:translateY(20px) scale(.95)}
  to  {opacity:1;transform:translateY(0)    scale(1)}
}
.dvw-iframe-wrap{
  position:relative;width:100%;padding-top:56.25%;
  background:#000 url('https://img.youtube.com/vi/9uUzrwtNL_k/maxresdefault.jpg') center/cover no-repeat;
  border-radius:12px;overflow:hidden;
}
.dvw-play-btn{
  position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
  width:52px;height:52px;border-radius:50%;
  background:rgba(255,0,0,.85);border:none;
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:18px;cursor:pointer;
  pointer-events:none;z-index:5;
  transition:opacity .3s;
}
.dvw-loaded .dvw-play-btn{opacity:0;}
.dvw-iframe{
  position:absolute;top:0;left:0;
  width:100%;height:100%;
  border:none;display:block;
}
.dvw-close-btn{
  position:absolute;top:-10px;right:-10px;z-index:10;
  width:26px;height:26px;border-radius:50%;
  background:#111;border:1px solid rgba(255,255,255,.15);
  color:rgba(255,255,255,.8);font-size:12px;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  transition:all .15s;line-height:1;
}
.dvw-close-btn:hover{background:#ef4444;border-color:#ef4444;color:#fff}
@media(max-width:400px){
  .dvw{width:calc(100vw - 32px);left:16px;bottom:16px}
}
</style>

<script>
(function(){
  var widget = document.getElementById('demoVideoWidget');
  var iframe = document.getElementById('dvwIframe');
  var loaded = false;

  // Load YouTube only when widget enters viewport
  function loadVideo(){
    if(loaded) return;
    iframe.src = iframe.dataset.src;
    loaded = true;
    setTimeout(function(){ widget.classList.add('dvw-loaded'); }, 1000);
  }

  if('IntersectionObserver' in window){
    new IntersectionObserver(function(entries, obs){
      if(entries[0].isIntersecting){ loadVideo(); obs.disconnect(); }
    },{threshold:0.1}).observe(widget);
  } else {
    setTimeout(loadVideo, 2000);
  }

  document.getElementById('dvwClose').addEventListener('click',function(){
    iframe.src='';
    loaded=false;
    widget.style.display='none';
  });
})();
</script>
</body>
</html>
