# Backlog

Pendientes que no están en el alcance de la sesión actual. Más reciente primero.

## SEO / lanzamiento

- **Checklist SEO de lanzamiento (bloqueado hasta tener dominio final):** cuando `surtilec.com` esté conectado, ejecutar en este orden: backup, search-replace del dominio temporal al dominio final, revisar `home`/`siteurl`, apagar `woocommerce_coming_soon`, activar indexación (`blog_public=1`), regenerar permalinks, purgar caché, revisar `robots.txt`, validar sitemap en el dominio final y enviar a Google Search Console. No activar indexación mientras el sitio siga en `ghostwhite-cormorant-218810.hostingersite.com`.
- **QA final de metadatos SEO:** Home, Catálogo, Nosotros, Contacto, Cotizar, Industrias, Recursos y 14 categorías pilar ya tienen titles/descriptions manuales en AIOSEO (2026-07-16). Antes de indexar, revisar el copy contra la oferta final del cliente y el dominio definitivo.
- **Sitemap AIOSEO:** después del dominio final, revisar `product_cat-sitemap.xml` para corregir prioridades/fechas (`lastmod` en 1970) y excluir taxonomías vacías o poco útiles si aparecen.
- **Social SEO:** configurar imagen social global (`og:image`/`twitter:image`), logo real de Organization y perfiles `sameAs` cuando existan (LinkedIn, Google Business Profile u otros oficiales).
- **Validación final:** probar Home, categoría, producto y artículo en Rich Results Test, inspección de URL en Search Console y PageSpeed móvil. Verificar que fichas de producto tengan `Product` JSON-LD sin `offers` y breadcrumbs válidas.
- **Contenido largo de productos top:** las descripciones cortas ya están completas para los 1.356 productos. Para más fuerza SEO después de dominio, priorizar descripciones largas/manuales o datasheets en los SKUs/categorías con más intención comercial; no generar texto largo masivo duplicado.

## Páginas / contenido

- **Fase 4 — Página Servicios (diferida 2026-06-15):** plantilla `page-servicios.php` con secciones por servicio (anclas + layout alternado), tabla resumen y banda CTA. **Bloqueada por contenido:** falta la **lista real de servicios** de Surtilec (no es procesador de cable tipo DWC; posibles: corte a medida, asesoría técnica, programación PLC/variadores, despacho nacional, gestión de listados/BOM). Construir sólo cuando el cliente confirme los servicios reales (regla: no inventar).
- **Ticker metales — precios USD reales / API:** hoy el USD/lb de cobre/aluminio es semilla editable (`surtilec_metals_usd`). Conectar una API real (filtro `surtilec_metals_usd`) o cargar los reales por opción. La TRM ya es en vivo.
- **Header móvil — paso compacto extra:** logo izquierda + hamburguesa derecha en la misma fila (reposicionar el toggle de GP) para borrar la fila "Menú" separada.

## Recursos / contenido

- **Más artículos AEO (pool ampliable):** apantallamiento (lámina vs malla), cobre vs aluminio, encauchetado/SO/SOOW usos, fibra óptica para industria, cable solar (TÜV/PV), cómo cotizar por volumen, variador: elegir potencia/voltaje, PLC vs relé. Misma estructura (H2 + FAQ → FAQPage).

## Catálogo (tras la carga de 1.356 productos)

- **Nombres en inglés:** varios productos Belden quedaron con nombre crudo en inglés (filas tardías del CSV). Refinar en `data/products-master.csv` y re-importar (idempotente).
- **Imágenes reales:** 317 productos tienen imagen destacada y alt; 1.039 productos siguen con placeholder. Se pueden subir/asignar directamente en WordPress Admin; el mu-plugin completa alt vacío al asignar imagen destacada y los scripts `audit-image-alt.php`/`fix-image-alt.php` auditan cada lote. Pendiente real: conseguir imágenes autorizadas del proveedor/fabricante o fotos propias.
- **SKUs duplicados:** 10 quedaron con sufijo `-2/-3`; revisar si eran productos distintos o duplicados reales.

## Polish (cosméticos)

- **Logo:** subir el logo real en Apariencia → Personalizar → Identidad del sitio → Logotipo (soporte `custom-logo` ya activo). Mientras tanto se muestra el wordmark "Surtilec." con punto naranja.
- **Footer — social/correo:** añadir URL de LinkedIn y un correo de contacto público (p. ej. `ventas@surtilec.com`) cuando existan, para sumarlos al footer.
- **CSS muerto:** quedan reglas `.surtilec-mega*` inertes (mega retirado); limpiar en un pase futuro.

### Resueltos
- ~~Alt text y descripciones cortas de producto~~ (2026-07-17): 0 imágenes/featured images sin alt; placeholder WooCommerce con alt; 1.356 productos publicados con descripción corta en WordPress y en `data/products-master.csv`.
- ~~Flujo futuro de alt text~~ (2026-07-18): automatización para no dejar alt vacío al asignar imágenes destacadas de producto, auditoría/reparación WP-CLI y guía `docs/image-alt-workflow.md`.
- ~~Datos legales visibles y schema~~ (2026-07-17): footer, Contacto, Privacidad, Términos y JSON-LD `Organization`/`LocalBusiness` incluyen Grupo Gerson S.A.S., NIT 901526407 y Carrera 12 # 17-99 donde corresponde.
- ~~Contenido de prueba antes de lanzar~~ (2026-07-17): el producto `Producto de prueba` id 35 quedó en `draft`; la URL pública `/producto/producto-de-prueba/` responde 404 y el catálogo publicado queda en 1.356 productos reales.
- ~~Categorías — limpiar contenido EJEMPLO~~ (2026-07-16): descripciones/FAQ de `product_cat` revisadas; `cables-de-control` dejó de usar FAQ de ejemplo y no quedan términos con `EJEMPLO`.
- ~~Post EJEMPLO~~ (2026-07-16): la entrada de demostración id 75 ya no existe en WordPress.
- ~~Footer — legal (Privacidad/Términos)~~ (2026-06-15): páginas creadas (ids 56/57, slugs `politica-de-privacidad` y `terminos`); el footer ya las enlaza.
- ~~Category pages — orden del H1~~ (2026-06-13): intro + mosaicos movidos a `woocommerce_archive_description` (debajo del H1, no loop-guarded → categorías vacías siguen mostrando mosaicos).
- ~~Bloque CTA — ancho~~ (2026-06-13): FAQ y CTA bajados a prioridad 5/6 en `woocommerce_after_main_content` (antes 10/12 → caían tras el cierre del wrapper, en el slot de sidebar). Ahora ancho completo dentro del contenido.
