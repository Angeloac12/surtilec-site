Project: Surtilec — Spanish B2B quote-generation catalog website (WordPress, Colombia).
Niche: cables especiales (control, THHN/THWN-2, VFD, instrumentación, encauchetado, apantallados) + automatización industrial (variadores de frecuencia, PLC, HMI, sensores).
Stack: WordPress + GeneratePress (free) child theme + WooCommerce (catalog mode, checkout disabled) + YITH Request a Quote free + Contact Form 7 (+ CF7 to Webhook + Turnstile) + All in One SEO free + LiteSpeed Cache + ACF free + WP Mail SMTP. Hosting: Hostinger Premium. PHP 8.2.
Temp domain = staging: ghostwhite-cormorant-218810.hostingersite.com (surtilec.com will be connected at launch; URLs migrate via wp search-replace).
Remote access: scripts/wp.sh runs WP-CLI on the server over SSH (key: ~/.ssh/id_ed25519_hostinger). scripts/deploy.sh deploys ONLY child theme + mu-plugins.
Rules:
- ALWAYS plan before coding; show me the plan and wait for approval.
- All custom code lives in wp-content/themes/surtilec-child or wp-content/mu-plugins. Never edit WP core or third-party plugins. Never edit files directly on the server — deploy only via scripts/deploy.sh.
- Work on a feature branch; small commits; conventional commit messages.
- Site language is Spanish (es-CO): ALL user-facing strings in Spanish.
- Performance: no jQuery in new code, no render-blocking assets, Lighthouse mobile ≥ 85.
- Templates output valid JSON-LD per docs/decisions.md.
- Never invent product specs; only use data/products-master.csv.
- After each task: list files changed, how to test it, and update docs/changelog.md.
