# Decisions

Architecture decision log for the Surtilec project. Newest first.

### 2026-06-10 — Spanish permalinks + custom schema mu-plugin (deduped vs AIOSEO)
- **Context:** Empty categories rendered nothing useful; URLs were English; §6 schema needed (Product without price, LocalBusiness, deduped against AIOSEO).
- **Decision — empty-category fix:** all category furniture (intro, subcategory/pillar tiles, FAQ, CTA) hooks into `woocommerce_before/after_main_content` (always fire), not the loop-guarded `*_shop_loop` hooks. Default `woocommerce_taxonomy_archive_description` removed; we render the intro ourselves for consistency on empty/non-empty. Custom `woocommerce_no_products_found`: silent for parents-with-children, WhatsApp prompt on leaves.
- **Decision — permalinks:** `producto` / `productos` / `filtro`. Confirmed via rewrite-rule resolution that category base `productos` does **not** conflict with the `/productos/` shop page — WooCommerce registers a specific `productos/page/N` rule so shop pagination wins over the generic category rule. (Fallback `categoria` was prepared but not needed.) Site is pre-indexing, so no redirects added; internal links are dynamic.
- **Decision — schema (`mu-plugins/surtilec-schema.php`):** emit Organization, LocalBusiness (city-level, no invented street), Product **without `offers`** (a priceless `Offer` is invalid for Google rich results → omit offers, keep a valid Product), and BreadcrumbList. Dedup via the `aioseo_schema_output` filter (remove AIOSEO's `Organization` + `BreadcrumbList` nodes; keep its `WebSite` + page types). FAQPage stays in the child theme (emitted once).
- **Consequences:** Post-dedup AIOSEO emits only `ItemPage`+`WebSite` (product) / `CollectionPage`+`WebSite` (category); ours adds the rest. Permalinks/test-data are server state; code is in repo. Validate live with Google Rich Results Test once Coming Soon is lifted (or via an authenticated fetch).

### 2026-06-10 — Catalog templates via hooks; AIOSEO schema baseline documented
- **Context:** No catalog templates existed. Needed the single-product spec table (SEO asset), category template (intro/FAQ/CTA/subcategory tiles), and shop pillar tiles, ready for real products.
- **Decision:** All output via WooCommerce hooks in `wp-content/themes/surtilec-child/inc/catalog-templates.php` (no template-file overrides → survives WC updates). Spec table = global `pa_*` attributes only, rows with values. Category intro = **native term description** (no ACF needed). FAQ = a single **ACF free term textarea** parsed into Q/A pairs + **FAQPage JSON-LD** (ACF free has no repeater; a FAQ CPT + relations is overkill for low volume). FAQ accordion uses native `<details>` (no JS).
- **Consequences:** Example data (product 35 attributes; term 21 description + FAQ) is server state, clearly EJEMPLO-marked. The ACF field group is registered in PHP (version-controlled).
- **AIOSEO free JSON-LD baseline** (captured via `wp eval` → `aioseo()->schema->get()`):
  - Product page `@graph`: `BreadcrumbList`, `ItemPage`, `Organization`, `WebSite` — **no `Product` schema**.
  - Category page `@graph`: `BreadcrumbList`, `CollectionPage`, `Organization`, `WebSite`.
- **Planned schema session (§6):** a custom mu-plugin to add `Organization`, `LocalBusiness`, `Product` (without price), `BreadcrumbList` — **deduplicated against AIOSEO**, which already emits `Organization`, `WebSite`, `BreadcrumbList` (so the mu-plugin must replace or skip those, and primarily add the missing `Product`/`LocalBusiness`). Our FAQPage JSON-LD stays as built. See [[surtilec-es-co-locale-fallback]] for the no-plugin-edits pattern.

### 2026-06-10 — Lead capture: CF7 forms via WP-CLI, enqueued helper JS
- **Context:** Needed two quote forms (global + BOM upload), notification + Spanish auto-reply emails, and page/menu wiring (project §7). SMTP + Turnstile already configured.
- **Decision:** CF7 forms created via `wp post create` + serialized meta (`_form`/`_mail`/`_mail_2`/`_messages`/`_locale`), normalized with `WPCF7_ContactForm::get_instance()->save()`. Turnstile is left to the plugin's global CF7 auto-inject (`cfturnstile_cf7_all=on`) — no `[cf7-simple-turnstile]` tag in markup (would double-render). UTM/page_url capture + Ciudad↔datalist link via an **enqueued** child-theme asset (`assets/js/surtilec-forms.js`, loaded on `wpcf7_enqueue_scripts`), not inline — repo rule: custom code is version-controlled and survives form recreation. CF7 can't read query params server-side, so UTMs are filled client-side; `[_url]` is also in the mail as a server-side source backup.
- **Consequences:** Forms/pages/menu are server state (documented in changelog), not in repo; only the JS, enqueue, and gettext additions are version-controlled. Turnstile blocks automated submits (verified: `invalid_fields: ['cf-turnstile']`), so end-to-end email delivery must be tested by a human in the browser.

### 2026-06-10 — Catalog taxonomy, attributes, CSV source-of-truth, YITH Spanish
- **Context:** Catalog had only leftover test categories and no attributes; YITH quote flow leaked English strings to visitors.
- **Decision:** Built the real `product_cat` tree (5 parents, 2 levels max) and 11 global `pa_*` attributes via WP-CLI (server state, not in repo). `data/products-master.csv` is the single source for product imports; specs come **only from supplier datasheets, never invented** (see `docs/csv-guia.md`). English YITH strings translated via a `gettext` filter scoped to the `yith-woocommerce-request-a-quote` domain in the catalog-mode mu-plugin (no plugin edits). Root cause of the leak: plugin ships `es_ES` but site locale is `es_CO`, so its `.mo` never loads.
- **Consequences:** Categories/attributes live in the DB (documented here + changelog), not version-controlled. The gettext map must be extended if YITH adds visitor-facing strings.
- **Future / fallback:** If **more** English strings leak from other plugins for the same `es_CO` vs `es_ES` reason, evaluate loading the plugin's `es_ES` translation files as a **locale fallback** (e.g. via `load_textdomain`/`locale`/`plugin_locale` filters or copying `es_ES` → `es_CO` `.mo`) instead of growing per-string gettext maps indefinitely.

### 2026-06-10 — WhatsApp via Joinchat, product search, CSS sticky header
- **Context:** Needed a floating WhatsApp quote CTA (per-product message), a persistent product search in the header, and a base visual identity.
- **Decision:** Use Joinchat free. Store config in the `joinchat` option (partial keys; plugin merges `defaults()` at runtime — `class-joinchat-common.php:167`). Per-product prefill via the `joinchat_settings` filter + Joinchat's built-in `{PRODUCT}` WooCommerce variable (no template edits). Header search = a small WooCommerce-scoped form on the `generate_inside_navigation` hook (full control of Spanish placeholder vs GP's generic nav search). Sticky header via child-theme CSS `position: sticky` (GP free lacks native sticky — Premium Menu Plus only), with `.admin-bar` top offset.
- **Consequences:** Joinchat config is server-side option data (not in repo) — documented; only the filter + search markup + CSS live in the child theme. Phone `573204499026` is final. Coming Soon ON → verify floating button/search/sticky logged in.

### 2026-06-10 — Quote price hiding, slugs, and no-sidebar layout
- **Context:** YITH quote list leaked product totals; shop/quote slugs and site identity were placeholders; default sidebar showed stock widgets.
- **Decision:** Hide quote prices with the native `ywraq_hide_price=yes` option (gates both the "Total" column header and subtotals in `request-quote-view.php`) rather than a custom filter/CSS. Slugs: quote page → `/cotizar/`, shop → `/productos/` (product singles stay on `product_base=/catalogo`). Force `no-sidebar` on WooCommerce pages via the `generate_sidebar_layout` filter in the child theme. Reviews off via option + `woocommerce_product_tabs`/`comments_open` belt-and-suspenders in the mu-plugin.
- **Consequences:** All YITH/identity/structure changes are WP-CLI config (server state, not in repo) except the two code hooks. Renaming slugs required `wp rewrite flush`. WC Cart/Checkout/My account pages kept (needed for `is_cart()`/`is_checkout()` redirect detection) but excluded from the menu. Coming Soon stays ON until launch.

### 2026-06-09 — Catalog mode via template-action removal, not `is_purchasable`
- **Context:** Site is a quote catalog, not a store. Prices and add-to-cart must disappear, but YITH "Solicitar cotización" must keep working.
- **Decision:** Implemented in `mu-plugins/surtilec-catalog-mode.php`, gated by the `SURTILEC_CATALOG_MODE` constant. Remove the loop/single add-to-cart template actions and filter `woocommerce_get_price_html`. Deliberately did **not** set `woocommerce_is_purchasable = false`, because that can hide the YITH quote button.
- **Consequences:** Flipping `SURTILEC_CATALOG_MODE` to `false` re-enables commerce. Cart/checkout redirect to `/cotizar/` (falls back to home until that page exists). `wc-cart-fragments` dequeued for performance. Note: WooCommerce's built-in "Coming Soon" mode (`woocommerce_coming_soon=yes`) currently hides the front-end for logged-out visitors — verify catalog behavior while logged in as admin.

## Deferred decisions

- **SSO / social login (Nextend Social Login + Google OAuth) — deferred to Phase 3.** Trigger: when client portal / quote history is built or repeat customers request account features. Quote flow stays accountless until then.

## Template

### YYYY-MM-DD — Title
- **Context:** why a decision was needed
- **Decision:** what was chosen
- **Consequences:** trade-offs, follow-ups
