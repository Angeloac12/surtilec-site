# Flujo de carga de productos por lotes

Este flujo permite ampliar el catálogo de Surtilec a partir de catálogos de
fabricantes y proveedores sin copiar contenido de otro distribuidor ni publicar
datos técnicos no verificados.

## Inventario desde Cables Colombia

Para crear un inventario inicial de referencias públicas, ejecutar:

```bash
node scripts/scrape-cablescolombia-sitemap.js
```

El resultado queda en `data/cablescolombia-discovery.csv` e incluye URL del
producto, nombre visible en el sitemap, categoría de origen, última actualización
e imagen externa referenciada. Es un archivo de descubrimiento, no un CSV listo
para importar: cada fila todavía requiere SKU confirmado, ficha técnica, nombre
Surtilec, descripción propia y decisión documentada sobre la imagen.

El scraper sólo solicita `sitemap.xml`, respeta el `Crawl-delay: 60` indicado por
el sitio para páginas y no descarga imágenes ni descripciones del competidor.

Para crear el primer lote de revisión de 50 referencias:

```bash
node scripts/prepare-cablescolombia-pilot.js
```

El archivo `data/cablescolombia-pilot-review.csv` queda deliberadamente en
estado de revisión. Sus columnas `*_interno`, `evidencia_interna_url` e
`imagen_interna_url` son sólo evidencia de trabajo local y no son campos para
publicar en WordPress. Las columnas `nombre_surtilec_propuesto`,
`descripcion_*_propuesta` y `datos_*` son el borrador editorial de Surtilec.

El borrador editorial sólo usa hechos explícitos en el nombre de cada
referencia: calibre/configuración, color, cobre, PVC, tensión o unidad cuando
aparecen allí. No convierte el nombre de origen en el nombre final, no atribuye
una marca que no esté confirmada y no afirma una aplicación, norma o capacidad
que no tenga ficha técnica. Los campos pendientes deben validarse con el
fabricante o proveedor antes de importar.

Después de regenerar el inventario desde el sitemap, se puede completar el SKU
que el catálogo público expone en sus tarjetas:

```bash
node scripts/enrich-cablescolombia-listing.js
node scripts/prepare-cablescolombia-pilot.js
```

## Orden de trabajo

1. Seleccionar una familia prioritaria: THHN/THWN-2, control, encauchetado,
   VFD, instrumentación o bandeja.
2. Recibir la lista de referencias, las fichas técnicas y los assets de imagen.
3. Normalizar nombres en español y mapear cada referencia a una categoría
   existente de Surtilec. El nombre público no debe conservar la marca, URL o
   SKU de otro distribuidor.
4. Completar `products-batch-template.csv` y una fila equivalente en
   `product-source-register.csv`.
5. Marcar la fuente técnica como `verificada` sólo después de revisar la ficha.
6. Marcar cada imagen como `autorizada`, `propia` o `sin_imagen`.
7. Ejecutar el dry-run, corregir todos los errores y revisar una muestra manual.
8. Ejecutar la importación real con backup automático.
9. Auditar alt text, imagen destacada, SEO y enlaces de cotización.

## Reglas de identidad

- El SKU de Surtilec es la llave de actualización y no debe cambiar por una
  diferencia de nombre.
- La referencia del fabricante se conserva en el registro de fuentes y no se
  reemplaza por el SKU de otro distribuidor.
- Variaciones de calibre, color, número de conductores, tensión o empaque son
  productos distintos sólo cuando la fuente confirma que son referencias
  distintas.
- Los nombres y descripciones se redactan para Surtilec a partir de hechos
  verificados. No se copia el texto del sitio de referencia.
- Una referencia descubierta no equivale a una referencia verificada. Si sólo
  existe el nombre público, el producto permanece como borrador y no se
  publica.
- La fuente técnica de un lote importable debe ser el fabricante, el proveedor
  autorizado o una evidencia local documentada. El importador rechaza URLs del
  distribuidor de referencia y marcadores de su marca en campos públicos.

## Imágenes

No se debe enlazar remotamente una imagen de otro sitio. El archivo aprobado se
sube a `data/images/` con el nombre exacto de la columna `imagen`; el importador
lo convierte en imagen destacada de WordPress y el mu-plugin de alt text completa
el texto alternativo cuando está vacío.

Si la imagen todavía no está autorizada, deja `imagen` vacío y usa
`estado_imagen=sin_imagen`. El producto puede servir para cotización, pero el
control de AIOSEO lo mantiene fuera del índice hasta que exista una imagen real.

## Primer lote recomendado

Comenzar con 25 a 50 referencias de las familias THHN/THWN-2, control,
encauchetado y VFD. El lote piloto debe probar nombres, atributos, unidades de
venta, imágenes, schema, enlaces relacionados y mensajes de WhatsApp antes de
expandirse al resto de categorías.

## Inventario completo en staging

Para procesar todas las referencias descubiertas sin publicarlas como productos
verificados:

```bash
node scripts/build-cablescolombia-full-staging.js
bash scripts/import-product-staging.sh --dry-run
bash scripts/import-product-staging.sh --live
```

El resultado `data/cablescolombia-full-crosswalk.csv` contiene una fila por
referencia y su estado final de investigación. Los productos en
`data/cablescolombia-staging.csv` se crean como borradores ocultos, sin imagen,
categoría ni especificaciones públicas. Las referencias ya importadas como
verificadas no se duplican.
