# Changelog

Notable changes to the Surtilec project. Newest first.

## Unreleased
- Cleanup + Spanish identity pass:
  - YITH quote list no longer leaks prices (`ywraq_hide_price=yes` hides the Total column header + subtotals). Add-to-quote button relabeled "Añadir a cotización" (`ywraq_show_btn_link_text`).
  - Quote page → title "Solicitar cotización", slug `/cotizar/` (id 12). Shop page → "Catálogo", slug `/productos/` (id 6); product singles stay at `/catalogo/…/`.
  - Site identity: `blogname` "Surtilec", `blogdescription` set. Static homepage = page "Inicio" (id 30).
  - Trashed 7 leftover pages (recoverable). Created "Contacto" page (id 38).
  - Menu "Principal" on `primary` location: Inicio, Catálogo, Cotizar, Contacto (no Cart/Checkout/My account).
  - Product reviews disabled (`woocommerce_enable_reviews=no`); mu-plugin also unsets the `reviews` product tab and closes comments on products.
  - No sidebar on shop/product/Woo pages via `generate_sidebar_layout` filter (child theme); stock sidebar widgets removed.
- `scripts/backup.sh`: exports the server DB to `~/backups` (keeps newest 5) and downloads a copy to `./backups/` (gitignored). Run before every deploy.
- GeneratePress child theme `surtilec-child`: `style.css` (child header, industrial CSS color vars, system font stack) + `functions.php` (parent/child style enqueue, es-CO text domain, base theme supports). Activated on the server.
- Catalog-mode mu-plugin `surtilec-catalog-mode.php` (gated by `SURTILEC_CATALOG_MODE`): prices → "Precio: solicitar cotización", removes loop/single add-to-cart and quantity, redirects cart/checkout to `/cotizar/` or home, dequeues `wc-cart-fragments`. YITH quote button preserved.
- Initial repo scaffold: child theme, mu-plugins, scripts (`wp.sh`, `deploy.sh`).
