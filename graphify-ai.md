# Graphify AI Context

Generated at (UTC): 2026-05-10T00:26:49+00:00

Use this file before scanning the full repository.

## Quick Summary
- Total tracked files: 70
- PHP entrypoints: 23
- Types: php=37, js=7, css=3, docs=14, html=9

## Primary Entrypoints
- activate_subscription.php
- admin.php
- analytics.php
- check_setup.php
- create_checkout.php
- db_config.php
- exchange_token.php
- fb_auth_proxy.php
- fb_proxy.php
- get_csrf.php
- graphify.php
- health_check.php
- index.php
- payment_status.php
- payment_success.php

## Detected Relations (from -> to)
- [calls] UI_INTEGRATION_EXAMPLES.js -> create_checkout.php
- [calls] UI_INTEGRATION_EXAMPLES.js -> fb_proxy.php
- [include] activate_subscription.php -> config/load-env.php
- [include] activate_subscription.php -> db_config.php
- [include] admin.php -> config/load-env.php
- [include] admin.php -> config/validators.php
- [include] admin.php -> db_config.php
- [calls] admin.php -> admin.php
- [include] analytics.php -> db_config.php
- [calls] assets/js/index-page.js -> analytics.php
- [calls] assets/js/index-page.js -> track_user.php
- [calls] assets/js/index-page.js -> admin.php
- [calls] assets/js/index-page.js -> get_csrf.php
- [calls] assets/js/index-page.js -> create_checkout.php
- [include] config/rate_limit.php -> config/rate_limit.php
- [calls] create_checkout.php -> payment_success.php
- [calls] fb_api.js -> exchange_token.php
- [calls] fb_api.js -> update_quota.php
- [include] health_check.php -> config/load-env.php
- [include] health_check.php -> db_config.php
- [calls] payment_status.php -> activate_subscription.php
- [include] payment_success.php -> config/load-env.php
- [include] payment_success.php -> db_config.php
- [include] scripts/migrations/20260509_add_tracking_table.php -> config/load-env.php
- [include] scripts/migrations/20260509_add_tracking_table.php -> db_config.php
- [include] setup_db.php -> config/load-env.php
- [include] setup_db.php -> db_config.php
- [calls] stripe_webhook_retry.php -> stripe_webhook.php
- [include] track_user.php -> db_config.php
- [include] update_quota.php -> db_config.php
- [calls] web_ui.js -> upload_image.php

## External Hosts
- api.stripe.com
- cdnjs.cloudflare.com
- fonts.googleapis.com
- cdn.jsdelivr.net
- example.com
- your-offer-page.com
- graph.facebook.com
- checkout.stripe.com
- facebook-inbox-production-2a22.up.railway.app
- castmepro.com
- www.castmepro.com
- pageinteractorprosite.site
- www.pageinteractorprosite.site
- connect.facebook.net
- yourdomain.com
- js.stripe.com
- connect.facebook.net;
- fonts.googleapis.com;
- fonts.gstatic.com
- www.facebook.com
- drive.google.com
- staticxx.facebook.com;
- www.w3.org
- developers.facebook.com
- transparency.fb.com
- localhost:${port}`
- localhost:${port}
- yoursite.com

## Usage For AI Agents
- Start with AGENTS.md and this file.
- Open only files related to requested feature/bug from entrypoints/relations above.
- Regenerate after structural changes: `php scripts/generate_graphify.php`.
