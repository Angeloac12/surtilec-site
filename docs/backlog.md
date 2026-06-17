# Backlog

Pendientes que no están en el alcance de la sesión actual. Más reciente primero.

## Páginas / contenido

- **Fase 4 — Página Servicios (diferida 2026-06-15):** plantilla `page-servicios.php` con secciones por servicio (anclas + layout alternado), tabla resumen y banda CTA. **Bloqueada por contenido:** falta la **lista real de servicios** de Surtilec (no es procesador de cable tipo DWC; posibles: corte a medida, asesoría técnica, programación PLC/variadores, despacho nacional, gestión de listados/BOM). Construir sólo cuando el cliente confirme los servicios reales (regla: no inventar).
- **Ticker metales — precios USD reales / API:** hoy el USD/lb de cobre/aluminio es semilla editable (`surtilec_metals_usd`). Conectar una API real (filtro `surtilec_metals_usd`) o cargar los reales por opción. La TRM ya es en vivo.
- **Header móvil — paso compacto extra:** logo izquierda + hamburguesa derecha en la misma fila (reposicionar el toggle de GP) para borrar la fila "Menú" separada.

## Catálogo (tras la carga de 1.356 productos)

- **Dropdown "Catálogo":** sumar las 9 líneas nuevas (Cable flexible, Cables de red, baja tensión, aluminio, distribución, acometida, fibra óptica, solares, Conductores desnudos) como ítems hijos del menú (hoy solo están las 5 originales). El home/footer ya las muestran (taxonomía).
- **Iconos/tagline de portada:** `front-page.php` `$pillar_meta` solo tiene icono+tagline para las 5 líneas originales; las 9 nuevas usan icono por defecto y sin tagline. Añadir.
- **Nombres en inglés:** varios productos Belden quedaron con nombre crudo en inglés (filas tardías del CSV). Refinar en `data/products-master.csv` y re-importar (idempotente).
- **Imágenes:** la columna `imagen` viene vacía → productos sin foto. Cargar imágenes en `data/images/` y re-importar.
- **SKUs duplicados:** 10 quedaron con sufijo `-2/-3`; revisar si eran productos distintos o duplicados reales.

## Polish (cosméticos)

- **Logo:** subir el logo real en Apariencia → Personalizar → Identidad del sitio → Logotipo (soporte `custom-logo` ya activo). Mientras tanto se muestra el wordmark "Surtilec." con punto naranja.
- **Footer — social/correo:** añadir URL de LinkedIn y un correo de contacto público (p. ej. `ventas@surtilec.com`) cuando existan, para sumarlos al footer.
- **CSS muerto:** quedan reglas `.surtilec-mega*` inertes (mega retirado); limpiar en un pase futuro.
- **Post EJEMPLO:** borrar la entrada de demostración (id 75) antes del lanzamiento.

### Resueltos
- ~~Footer — legal (Privacidad/Términos)~~ (2026-06-15): páginas creadas (ids 56/57, slugs `politica-de-privacidad` y `terminos`); el footer ya las enlaza.
- ~~Category pages — orden del H1~~ (2026-06-13): intro + mosaicos movidos a `woocommerce_archive_description` (debajo del H1, no loop-guarded → categorías vacías siguen mostrando mosaicos).
- ~~Bloque CTA — ancho~~ (2026-06-13): FAQ y CTA bajados a prioridad 5/6 en `woocommerce_after_main_content` (antes 10/12 → caían tras el cierre del wrapper, en el slot de sidebar). Ahora ancho completo dentro del contenido.
