# Changelog

Notable changes to the Surtilec project. Newest first.

## Unreleased
- Catalog taxonomy + attributes + CSV + YITH Spanish:
  - `product_cat` tree (server-side): 5 parents — Cables de control, Cable THHN / THWN-2, Cables para variadores VFD, Cables especiales (4 hijos), Automatización industrial (5 hijos). Removed leftover test categories ("Pruebas" + bogus "18"); test product 35 moved to Cables de control.
  - 11 global attributes (`pa_*`): calibre-awg, numero-conductores, voltaje, apantallado (terms Sí/No), chaqueta, norma, marca, aplicacion, potencia-hp, voltaje-entrada, serie.
  - `data/products-master.csv` template (header + 3 `EJEMPLO-` rows) and `docs/csv-guia.md` authoring guide. Rule: specs only from supplier datasheets, never invented.
  - YITH front-end strings translated to Spanish via a `gettext` filter scoped to the `yith-woocommerce-request-a-quote` domain in the catalog-mode mu-plugin (es_CO vs shipped es_ES). 12 strings: Add to Quote, Product added/already, No products in list, Your list is empty (+ long variant), Browse the list, Notes on your request…, Quote request, Send the request, Send Your Request, request sent successfully.
- WhatsApp + header + visual identity pass:
  - Joinchat (free, `creame-whatsapp-me`) installed/activated. Floating button bottom-right, all pages, phone `573204499026`. Chat CTA "¿Necesitas una cotización? Escríbenos"; prefill "Hola Surtilec, quiero una cotización. Vengo de: {URL}". On single products the prefill becomes "Hola Surtilec, quiero cotizar: {PRODUCT} — {URL}" via a `joinchat_settings` filter in the child theme (uses Joinchat's built-in WooCommerce `{PRODUCT}` variable). Change the number later: `scripts/wp.sh option patch update joinchat telephone <NUMERO>` or WP Admin → Joinchat → Telephone.
  - Header: persistent product-scoped search (`post_type=product`) injected via `generate_inside_navigation`, placeholder "Buscar producto, ej: cable THHN 12 AWG". Sticky header via child-theme CSS (`position: sticky`), with WP admin-bar offset (32px / 46px mobile) — GP free has no native sticky.
  - Visual identity (child `style.css`): orange (`--color-accent`) primary CTAs (Añadir a cotización, single add-to-cart, CF7 submit, `.button.alt`), dark-blue secondary buttons; typographic scale + spacing, ~1200px content width; "Precio: solicitar cotización" styled as a subtle badge; mobile-first search/menu.
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
