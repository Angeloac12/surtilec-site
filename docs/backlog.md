# Backlog

> **Estado actual:** leer primero [`docs/llm-project-context.md`](llm-project-context.md).
> Este archivo conserva tareas y contexto histórico; los conteos vigentes están
> en el handoff canónico.

Pendientes que no están en el alcance de la sesión actual. Más reciente primero.

## SEO / post-lanzamiento

- **Crecimiento orgánico y medición:** implementados eventos de búsqueda, WhatsApp, cotización, formularios y subida de listados; la portada muestra recursos recientes y las guías/categorías tienen enlaces internos cruzados. Operación semanal documentada en `docs/organic-growth.md`. Pendiente operativo: revisar Search Console/Analytics y publicar una guía técnica validada por semana.
- **Lote de imagenes autorizado:** 499 variantes fueron cruzadas con el inventario fuente. Se descargaron 487 candidatos nuevos y el control de hash dejó 64 imágenes nuevas realmente únicas para su variante; sumadas a las 12 coincidencias originales, `76` productos draft tienen miniatura exacta asignada. Las otras 423 variantes del lote siguen sin miniatura porque la fuente reutiliza el mismo archivo entre calibres o configuraciones. El detalle queda en `data/product-exact-image-verification.csv`; no publicar esas filas hasta recibir un asset específico.
- **Complementación de fichas staging:** 499 fichas ya tienen nombre propio, descripción corta/larga, atributos identificables, categoría, familia técnica contrastada y registro interno de fuente. Permanecen `draft/hidden` porque la investigación es de familia y no de referencia exacta: confirmar fabricante, presentación, disponibilidad y variante visual antes de abrirlas a indexación.
- **QA de copy antes de publicar:** completado el 2026-08-03 para las 499 fichas genéricas. Se revisaron configuraciones, kV, familias 15/35 kV, preguntas AEO, CTA y enlaces de fuente; queda pendiente solo la validación comercial y visual antes de publicar.
- **Unificación de descripciones del catálogo:** completada para los productos con SKU el 2026-08-03. La plantilla SEO/AEO ahora es coherente entre catálogo publicado y staging; el único producto excluido es el borrador de prueba sin SKU.

- **Monitoreo SSL / redes restringidas:** certificado público verificado el 2026-07-31: Let's Encrypt `YE1`, SAN `surtilec.com` + `www.surtilec.com`, válido hasta 2026-10-29. Si una red muestra "Esta conexión no es privada", documentar proveedor/red/dispositivo y probar en datos móviles; causa probable: inspección TLS/captive portal/DNS filtrado de la red, no el certificado público. Revalidar con `curl -sI https://surtilec.com/` y `openssl s_client -servername surtilec.com -connect surtilec.com:443`.
- **Google Search Console:** crear/verificar propiedad para `https://surtilec.com`, enviar `https://surtilec.com/sitemap.xml`, inspeccionar Home, Catálogo, una categoría, un producto con imagen y un artículo. Confirmar que Google ve `max-image-preview:large` en páginas indexables y `noindex` en productos sin imagen.
- **QA final de metadatos SEO:** Home, Catálogo, Nosotros, Contacto, Cotizar, Industrias, Recursos, `/cotizar/solicitud/`, `/cotizar/subir-listado/` y los 7 artículos de Recursos ya tienen titles/descriptions manuales en AIOSEO (2026-07-21). Las 14 categorías pilar tienen descripciones reales. Antes de indexar, revisar el copy contra la oferta final del cliente y el dominio definitivo.
- **SEO de producto:** `scripts/aioseo-product-seo-readiness.php` completa títulos, meta descriptions y campos sociales de productos sólo cuando están vacíos, usando el nombre y descripción corta existentes. No sobrescribe copy manual, robots, schema, precio ni atributos.
- **SEO de producto — títulos generados:** el truncado automático corta por límite de palabra; `scripts/refresh-aioseo-generated-product-titles.php` actualiza sólo títulos sociales que coinciden con el formato generado anterior y deja intacto el copy manual.
- **Auditoría de fuente:** `scripts/audit-source-leakage.php` comprueba posts, metadatos, taxonomías, opciones y AIOSEO para evitar que una fuente de investigación termine en WordPress.
- **AEO — endurecer enlaces fuente:** el primer pase AEO ya está publicado en URLs existentes. Como algunos enlaces oficiales de Belden pueden devolver challenge/403 a verificaciones automatizadas, añadir fuentes secundarias crawlables cuando existan (PDFs oficiales, fichas de fabricante o páginas técnicas accesibles) sin cambiar las respuestas visibles ni inventar especificaciones.
- **Social SEO:** configurar imagen social global (`og:image`/`twitter:image`), logo real de Organization y perfiles `sameAs` cuando existan (LinkedIn, Google Business Profile u otros oficiales).
- **Validación final:** probar Home, categoría, producto y artículo en Rich Results Test, inspección de URL en Search Console y PageSpeed móvil. Verificar que fichas de producto tengan `Product` JSON-LD sin `offers` y breadcrumbs válidas.
- **Contenido largo de productos top:** las descripciones cortas ya están completas para los 1.356 productos. Para más fuerza SEO después de dominio, priorizar descripciones largas/manuales o datasheets en los SKUs/categorías con más intención comercial; no generar texto largo masivo duplicado.

## Mejoras de página post-launch

- **Home — confianza comercial arriba del fold:** añadir señales concretas sin inventar: operador/NIT, Bogotá/Colombia, marcas/categorías principales, despacho nacional y CTA fuerte a WhatsApp/cotización. Mantener diseño sobrio B2B, no landing genérica.
- **Cotización — reducción de fricción:** revisar `/cotizar/`, `/cotizar/solicitud/` y `/cotizar/subir-listado/` en móvil; priorizar campos mínimos, mensajes claros de respuesta en horario hábil, subida de BOM/listado y fallback a WhatsApp.
- **Categorías prioritarias — enlaces internos:** reforzar desde Home/Catálogo hacia categorías con intención alta (`cables-de-control`, `thhn-thwn-2`, `vfd`, `instrumentacion`, `automatizacion`) y desde Recursos hacia categorías/productos relevantes.
- **Producto — estado visual para productos sin imagen:** mientras sigan noindex, mejorar el placeholder/estado visual para que el usuario entienda que puede cotizar aunque falte foto. No abrir indexación hasta asignar imagen real.

## Páginas / contenido

- **Fase 4 — Página Servicios (diferida 2026-06-15):** plantilla `page-servicios.php` con secciones por servicio (anclas + layout alternado), tabla resumen y banda CTA. **Bloqueada por contenido:** falta la **lista real de servicios** de Surtilec (no es procesador de cable tipo DWC; posibles: corte a medida, asesoría técnica, programación PLC/variadores, despacho nacional, gestión de listados/BOM). Construir sólo cuando el cliente confirme los servicios reales (regla: no inventar).
- **Ticker metales — precios USD reales / API:** hoy el USD/lb de cobre/aluminio es semilla editable (`surtilec_metals_usd`). Conectar una API real (filtro `surtilec_metals_usd`) o cargar los reales por opción. La TRM ya es en vivo.
- **Header móvil — paso compacto extra:** logo izquierda + hamburguesa derecha en la misma fila (reposicionar el toggle de GP) para borrar la fila "Menú" separada.

## Recursos / contenido

- **Más artículos AEO (pool ampliable):** apantallamiento (lámina vs malla), cobre vs aluminio, encauchetado/SO/SOOW usos, fibra óptica para industria, cable solar (TÜV/PV), cómo cotizar por volumen, variador: elegir potencia/voltaje, PLC vs relé. Misma estructura (H2 + FAQ → FAQPage).

## Catálogo (tras la carga de 1.356 productos)

- **Carga por lotes con fuentes:** infraestructura implementada (2026-08-03) para importar CSV extendidos con registro por SKU, fuentes técnicas verificadas, estado de licencia de imagen y metadatos internos. Pendiente: recibir el primer lote real de 25–50 referencias con fichas y assets autorizados.
- **Piloto Cables Colombia:** inventario público generado (2026-08-03) con 528 referencias; archivo review-only de 50 referencias priorizadas en `data/cablescolombia-pilot-review.csv`. Pendiente: completar SKU real, fichas técnicas, descripciones propias y permisos de imagen antes de convertirlo en lote importable.
- **Matriz de fabricante del piloto:** investigación oficial completada (2026-08-03) para las 50 referencias priorizadas. `data/products-verified-pilot.csv` contiene 29 coincidencias exactas listas para dry-run; `data/surtilec-pilot-manufacturer-crosswalk.csv` conserva 21 pendientes sin marca ni referencia asignada. Las fichas y criterios están documentados en `docs/manufacturer-research.md`.
- **Staging completo de referencias:** las 528 filas descubiertas ya tienen matriz en `data/cablescolombia-full-crosswalk.csv`; 499 referencias no importadas se crearon como borradores ocultos (478 sin investigación y 21 pendientes del piloto). Los 29 productos verificados existentes no se duplicaron. El catálogo publicado permanece en 1.385.
- **Cola de derechos de imagen:** `scripts/export-live-image-status.php` y `scripts/build-image-rights-queue.js` generan `data/live-product-image-status.csv` y `data/product-image-rights-queue.csv` para los productos sin imagen publicada y el staging oculto, con permiso, titular, coincidencia exacta, hash, alt y título multimedia. La cola no descarga ni publica imágenes sin permiso o fotografía propia.
- **Archivo fácil para completar información:** `scripts/build-product-info-template.js` genera `data/product-info-to-complete.csv` con los 499 productos staging y solo los campos necesarios para confirmar marca, referencia, ficha oficial e imagen.
- **Nombres en inglés:** varios productos Belden quedaron con nombre crudo en inglés (filas tardías del CSV). Refinar en `data/products-master.csv` y re-importar (idempotente).
- **Imágenes reales:** 317 productos tienen imagen destacada y alt; 1.068 productos siguen sin imagen y quedaron noindex en AIOSEO (2026-07-22) para no abrir fichas incompletas a Google cuando llegue el dominio. Se pueden subir/asignar directamente en WordPress Admin; el mu-plugin completa alt vacío al asignar imagen destacada y los scripts `audit-image-alt.php`/`fix-image-alt.php` auditan cada lote. Después de cada lote, rerun `scripts/aioseo-product-image-readiness.php` para reabrir automáticamente los productos que ya tengan imagen. Pendiente real: conseguir imágenes autorizadas del proveedor/fabricante o fotos propias.
- **SKUs duplicados:** 10 quedaron con sufijo `-2/-3`; revisar si eran productos distintos o duplicados reales.

## Polish (cosméticos)

- **Logo:** subir el logo real en Apariencia → Personalizar → Identidad del sitio → Logotipo (soporte `custom-logo` ya activo). Mientras tanto se muestra el wordmark "Surtilec." con punto naranja.
- **Footer — social/correo:** añadir URL de LinkedIn y un correo de contacto público (p. ej. `ventas@surtilec.com`) cuando existan, para sumarlos al footer.
- **CSS muerto:** quedan reglas `.surtilec-mega*` inertes (mega retirado); limpiar en un pase futuro.

### Resueltos
- ~~AEO fase 1 en URLs existentes~~ (2026-07-31): script `scripts/apply-aeo-content.php` aplicado en servidor con backup `surtilec-20260731-153926.sql`; 7 posts de Recursos y 8 categorías de producto recibieron preguntas/respuestas visibles con fuente debajo. Verificado `FAQPage` JSON-LD en hub FAQ, artículos técnicos y categorías prioritarias. No se crearon páginas nuevas.
- ~~Launch dominio e indexación~~ (2026-07-31): `surtilec.com` quedó conectado, HTTPS activo, `www`/HTTP redirigen al dominio canónico, CDN browser challenge desactivado, `woocommerce_coming_soon=no`, `blog_public=1`, sitemap final en robots.txt, Home/producto con imagen indexables, productos sin imagen siguen noindex y `product-sitemap.xml` queda en 317 URLs.
- ~~Sitemap AIOSEO post-dominio~~ (2026-07-31): sitemap final usa `surtilec.com`, no quedan referencias al dominio temporal y `product_cat-sitemap.xml`/`category-sitemap.xml` mantienen 0 fechas `1970-01-01`.
- ~~Sitemap taxonomy `lastmod` y archivos thin~~ (2026-07-21): `surtilec-seo.php` corrige `product_cat`/`category` sitemap dates desde contenido publicado; autor/fecha quedaron ocultos/noindex en AIOSEO; sitemaps de taxonomía muestran 0 fechas `1970-01-01`.
- ~~Alt text y descripciones cortas de producto~~ (2026-07-17): 0 imágenes/featured images sin alt; placeholder WooCommerce con alt; 1.356 productos publicados con descripción corta en WordPress y en `data/products-master.csv`.
- ~~Flujo futuro de alt text~~ (2026-07-18): automatización para no dejar alt vacío al asignar imágenes destacadas de producto, auditoría/reparación WP-CLI y guía `docs/image-alt-workflow.md`.
- ~~Datos legales visibles y schema~~ (2026-07-17): footer, Contacto, Privacidad, Términos y JSON-LD `Organization`/`LocalBusiness` incluyen Grupo Gerson S.A.S., NIT 901526407 y Carrera 12 # 17-99 donde corresponde.
- ~~Contenido de prueba antes de lanzar~~ (2026-07-17): el producto `Producto de prueba` id 35 quedó en `draft`; la URL pública `/producto/producto-de-prueba/` responde 404 y el catálogo publicado queda en 1.356 productos reales.
- ~~Categorías — limpiar contenido EJEMPLO~~ (2026-07-16): descripciones/FAQ de `product_cat` revisadas; `cables-de-control` dejó de usar FAQ de ejemplo y no quedan términos con `EJEMPLO`.
- ~~Post EJEMPLO~~ (2026-07-16): la entrada de demostración id 75 ya no existe en WordPress.
- ~~Footer — legal (Privacidad/Términos)~~ (2026-06-15): páginas creadas (ids 56/57, slugs `politica-de-privacidad` y `terminos`); el footer ya las enlaza.
- ~~Category pages — orden del H1~~ (2026-06-13): intro + mosaicos movidos a `woocommerce_archive_description` (debajo del H1, no loop-guarded → categorías vacías siguen mostrando mosaicos).
- ~~Bloque CTA — ancho~~ (2026-06-13): FAQ y CTA bajados a prioridad 5/6 en `woocommerce_after_main_content` (antes 10/12 → caían tras el cierre del wrapper, en el slot de sidebar). Ahora ancho completo dentro del contenido.
