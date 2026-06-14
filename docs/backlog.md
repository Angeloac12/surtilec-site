# Backlog

Pendientes que no están en el alcance de la sesión actual. Más reciente primero.

## Polish (cosméticos)

- **Logo:** subir el logo real en Apariencia → Personalizar → Identidad del sitio → Logotipo (soporte `custom-logo` ya activo). Mientras tanto se muestra el wordmark "Surtilec." con punto naranja.
- **Footer — legal:** crear páginas "Política de privacidad" (`/politica-de-privacidad/`) y "Términos" (`/terminos/`); el footer enlaza esos slugs automáticamente cuando existan.
- **Footer — social/correo:** añadir URL de LinkedIn y un correo de contacto público (p. ej. `ventas@surtilec.com`) cuando existan, para sumarlos al footer.

### Resueltos
- ~~Category pages — orden del H1~~ (2026-06-13): intro + mosaicos movidos a `woocommerce_archive_description` (debajo del H1, no loop-guarded → categorías vacías siguen mostrando mosaicos).
- ~~Bloque CTA — ancho~~ (2026-06-13): FAQ y CTA bajados a prioridad 5/6 en `woocommerce_after_main_content` (antes 10/12 → caían tras el cierre del wrapper, en el slot de sidebar). Ahora ancho completo dentro del contenido.
