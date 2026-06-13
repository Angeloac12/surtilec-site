# Performance results (después)

Fecha: 2026-06-13. Sesión `feat/performance`. Comparar contra [perf-baseline.md](perf-baseline.md).

## Nota sobre la medición after

Los números TTFB "after" **no se capturaron limpiamente**. La re-medición automatizada
(curl autenticado con cookie de admin vía SSH al servidor) resultó inestable: el script
se colgaba a mitad de la primera página pese a `curl --max-time 15` por petición y un
tope global de 240 s. Se abandonó la re-medición automatizada para no bloquear el cierre
de la sesión.

En su lugar, los cambios se confirman **desplegados y activos** consultando los valores
en vivo del servidor (`wp litespeed-option get`), abajo. Re-medir manualmente con
PageSpeed Insights / WebPageTest tras conectar `surtilec.com` y apagar Coming Soon.

## Optimizaciones confirmadas en vivo (`litespeed-option get`)

| Clave LiteSpeed | antes | ahora | Cambio |
|---|---|---|---|
| `cache` (page cache) | 1 | `1` | sin cambio (ya ON) |
| `cache-browser` (browser cache) | **OFF** | `1` | **activado** |
| `optm-css_min` (minify CSS) | **OFF** | `1` | **activado** |
| `optm-js_min` (minify JS) | **OFF** | `1` | **activado** |
| `optm-js_defer` (defer JS) | **OFF** | `1` | **activado** |
| `media-lazy` (lazyload imágenes) | **OFF** | `1` | **activado** |
| `optm-css_comb` (combine CSS) | 0 | `0` | sin cambio (deliberado: evita romper orden) |
| `optm-js_comb` (combine JS) | 0 | `0` | sin cambio |
| `optm-html_min` (minify HTML) | 0 | `0` | sin cambio |
| `optm-qs_rm` (quitar query strings) | 0 | `0` | sin cambio |
| `object` (object cache) | 0 | `0` | sin dropin (Hostinger sin Redis/Memcached dedicado) |

## Cambio en el child theme (tema)

`inc/catalog-templates.php`: tiles de categoría/subcategoría y conteos ahora cachean
`get_terms()` en transients de 12 h con clave versionada (`surtilec_tiles_ver`). La
versión se incrementa en `save_post_product`, `deleted_post` (product),
`created/edited/delete_product_cat`, así los conteos quedan correctos tras cambios y se
elimina la query repetida de términos en cada carga de archivo/tienda.

## Pendiente

- Re-medir TTFB/Lighthouse manualmente tras lanzar `surtilec.com` (Coming Soon OFF),
  con PageSpeed Insights — entorno real anónimo, sin admin bar inflando assets/HTML.
- Evaluar `optm-qs_rm` y combine CSS/JS una vez estable el catálogo.
