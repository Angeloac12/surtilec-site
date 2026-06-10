# Decisions

Architecture decision log for the Surtilec project. Newest first.

### 2026-06-09 — Catalog mode via template-action removal, not `is_purchasable`
- **Context:** Site is a quote catalog, not a store. Prices and add-to-cart must disappear, but YITH "Solicitar cotización" must keep working.
- **Decision:** Implemented in `mu-plugins/surtilec-catalog-mode.php`, gated by the `SURTILEC_CATALOG_MODE` constant. Remove the loop/single add-to-cart template actions and filter `woocommerce_get_price_html`. Deliberately did **not** set `woocommerce_is_purchasable = false`, because that can hide the YITH quote button.
- **Consequences:** Flipping `SURTILEC_CATALOG_MODE` to `false` re-enables commerce. Cart/checkout redirect to `/cotizar/` (falls back to home until that page exists). `wc-cart-fragments` dequeued for performance. Note: WooCommerce's built-in "Coming Soon" mode (`woocommerce_coming_soon=yes`) currently hides the front-end for logged-out visitors — verify catalog behavior while logged in as admin.

## Template

### YYYY-MM-DD — Title
- **Context:** why a decision was needed
- **Decision:** what was chosen
- **Consequences:** trade-offs, follow-ups
