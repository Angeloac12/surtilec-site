# Backlog

Pendientes que no están en el alcance de la sesión actual. Más reciente primero.

## Polish (cosméticos)

- **Category pages — orden del H1:** el título H1 de la categoría se renderiza **debajo** de los mosaicos de subcategorías; debería ir **primero** (título, luego mosaicos). Probablemente reordenar el hook del intro/título vs. `surtilec_subcategory_tiles` en `inc/catalog-templates.php`.
- **Bloque CTA oscuro — ancho:** el bloque CTA se renderiza **a la derecha como una barra lateral** en la parte superior del contenido, en vez de **ancho completo debajo** del contenido. Revisar el hook/markup/CSS de `surtilec_render_cta_block` (probable: contenedor/float de WooCommerce o el hook `woocommerce_after_main_content` cayendo dentro del wrapper de contenido).
