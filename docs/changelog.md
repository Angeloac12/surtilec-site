# Changelog

Notable changes to the Surtilec project. Newest first.

## Unreleased
- `scripts/backup.sh`: exports the server DB to `~/backups` (keeps newest 5) and downloads a copy to `./backups/` (gitignored). Run before every deploy.
- GeneratePress child theme `surtilec-child`: `style.css` (child header, industrial CSS color vars, system font stack) + `functions.php` (parent/child style enqueue, es-CO text domain, base theme supports). Activated on the server.
- Catalog-mode mu-plugin `surtilec-catalog-mode.php` (gated by `SURTILEC_CATALOG_MODE`): prices → "Precio: solicitar cotización", removes loop/single add-to-cart and quantity, redirects cart/checkout to `/cotizar/` or home, dequeues `wc-cart-fragments`. YITH quote button preserved.
- Initial repo scaffold: child theme, mu-plugins, scripts (`wp.sh`, `deploy.sh`).
