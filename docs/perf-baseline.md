# Performance baseline (antes)

Fecha: 2026-06-13. Medido **sin** apagar Coming Soon: petición autenticada con cookie de admin (`wp_generate_auth_cookie`) vía `curl`, que omite la página coming-soon y entrega la página real.

- **TTFB miss** = render sin caché (tras `litespeed-purge all`) — la métrica a reducir.
- **TTFB warm** = segundo hit (caché privada de LiteSpeed para usuario logueado).
- El peso de HTML y 3 de los assets (`admin-bar.min.css`, `dashicons.min.css`, `admin-bar.min.js`) están inflados por la barra de administración (solo logueado); un visitante real no los carga.

| Página | TTFB miss | TTFB warm | Assets CSS+JS | Peso assets | HTML (con admin bar) |
|---|---|---|---|---|---|
| Home `/` | 2.60 s | 0.38 s | 23 | 246 KB | 641 KB |
| Categoría `/categoria/cables-de-control/` | 2.56 s | 0.41 s | 23 | 246 KB | 645 KB |
| Producto `/producto/producto-de-prueba/` | 2.73 s | 0.42 s | 28 | 320 KB | 653 KB |

## Assets > 50 KB
- `jquery.min.js` — **85 KB** en todas las páginas. Lo cargan WooCommerce/plugins (YITH, etc.), **no** nuestro código. Candidato a defer.

## Estado LiteSpeed antes
- Page cache: ON · Browser cache: **OFF** · Minify CSS/JS: **OFF** · Combine: OFF · JS defer: **OFF** · Lazyload: **OFF** · Object cache: **no dropin** · TTL público: 7 d.

## Notas
- `blog_public=0` (desalentar motores) confirmado ON.
- Sin webfonts; nuestro código no añade jQuery.
- Método de medición sin ventana pública (cookie de admin); no se apagó Coming Soon.
