# Guía del CSV maestro de productos (`data/products-master.csv`)

Este archivo es la **única fuente** para cargar productos al catálogo. Una fila = un producto.

> **Regla de oro:** las especificaciones (calibre, conductores, voltaje, normas, potencia, etc.) se toman **únicamente de las fichas técnicas del proveedor**. **Nunca se inventan.** Si un dato no está en la ficha, se deja vacío.

Las 3 filas con SKU que empieza por `EJEMPLO-` son **plantillas de muestra**: sirven para ver el formato. **No se publican** y deben borrarse antes de la importación real.

El formato de 17 columnas existente sigue siendo válido para mantener el catálogo
actual. Los nuevos lotes deben usar `data/products-batch-template.csv` y el
registro de fuentes `data/product-source-register.csv`.

## Columnas

| Columna | Descripción | Obligatoria |
|---|---|---|
| `sku` | Código único del producto. Sin espacios. | **Sí** (todos) |
| `nombre` | Nombre comercial del producto. | **Sí** (todos) |
| `categoria` | Categoría principal. Debe coincidir con una categoría existente (ver lista abajo). | **Sí** (todos) |
| `subcategoria` | Subcategoría (hijo de la categoría). Vacío si la categoría es de primer nivel. | Solo si aplica |
| `marca` | Marca / fabricante. | Recomendada |
| `calibre_awg` | Calibre en AWG (p. ej. `12`, `2/0`). | **Cables: sí** · Automatización: vacío |
| `num_conductores` | Número de conductores. | **Cables: sí** · Automatización: vacío |
| `voltaje` | Tensión nominal del cable (p. ej. `600 V`). | Cables: recomendada |
| `apantallado` | Exactamente `Sí` o `No`. | Cables: recomendada |
| `chaqueta` | Material de la chaqueta (PVC, Nylon/PVC, etc.). | Cables: opcional |
| `norma` | Norma / certificación (UL 1277, NTC 2050, IEC…). | Recomendada |
| `aplicacion` | Uso principal. | Opcional |
| `potencia_hp` | Potencia en HP. | **Variadores/automatización: sí** · Cables: vacío |
| `voltaje_entrada` | Tensión de entrada (p. ej. `220 V`). | **Variadores: sí** · Cables: vacío |
| `serie` | Serie / línea del fabricante. | Automatización: opcional |
| `descripcion_corta` | Descripción breve. Encerrar entre comillas si lleva comas. | Recomendada |
| `imagen` | Nombre del archivo de imagen (p. ej. `cable-thhn-12.jpg`). | Opcional |

## Columnas adicionales para lotes nuevos

El formato de lote añade estas columnas después de `imagen`:

| Columna | Descripción | Obligatoria |
|---|---|---|
| `descripcion_larga` | Descripción editorial propia basada en la ficha técnica. | Recomendada |
| `unidad_venta` | Unidad o empaque confirmado (metro, rollo, carrete, unidad). | Si aplica |
| `temperatura_maxima` | Temperatura nominal confirmada por la ficha. | Si aplica |

Un lote nuevo sólo se acepta cuando cada SKU existe en el registro de fuentes y
su `estado_fuente` es `verificada`. No se publican especificaciones inventadas.

## Registro de fuentes (`data/product-source-register.csv`)

Debe existir una fila por cada SKU de un lote nuevo:

| Columna | Descripción |
|---|---|
| `sku` | Debe coincidir exactamente con el CSV de productos. |
| `referencia_fabricante` | Referencia original del fabricante, si existe. |
| `marca` | Marca confirmada. |
| `proveedor` | Proveedor o fabricante que entregó la información. |
| `fuente_datos_url` | URL pública del catálogo o fuente principal, si existe. |
| `ficha_tecnica_url` | URL del datasheet o ficha técnica, si existe. |
| `fuente_imagen_url` | Origen documentado de la imagen, cuando se carga una. |
| `estado_fuente` | Debe ser `verificada` para importar un lote nuevo. |
| `estado_imagen` | `autorizada`, `propia` o `sin_imagen`. |
| `verificado_en` | Fecha de verificación, formato `AAAA-MM-DD`. |
| `notas` | Permiso, alcance o aclaración interna. No se publica. |

Las imágenes descubiertas en otros catálogos no se reutilizan automáticamente.
Sólo se aceptan imágenes propias o assets de fabricante/proveedor con permiso documentado.
La URL de origen se guarda como auditoría interna y no se usa para hotlinking.
El importador rechaza imágenes o fichas que apunten al distribuidor de referencia.
Cuando el proveedor entrega archivos locales sin URL pública, documenta el
nombre del archivo y el permiso en `notas`, junto con `proveedor`.

## Requisitos por tipo de producto

- **Cables** (control, THHN/THWN-2, VFD, especiales): obligatorio `calibre_awg` y `num_conductores`. Dejar `potencia_hp`, `voltaje_entrada` y `serie` vacíos.
- **Automatización industrial** (variadores, PLC, HMI, sensores, arrancadores): obligatorio `potencia_hp` y `voltaje_entrada` cuando la ficha los indique. Dejar `calibre_awg` y `num_conductores` vacíos.

## Valores válidos para `categoria` / `subcategoria`

Deben coincidir **exactamente** (nombre) con la taxonomía del sitio:

- **Cables de control** (sin subcategoría)
- **Cable THHN / THWN-2** (sin subcategoría)
- **Cables para variadores VFD** (sin subcategoría)
- **Cables especiales** → subcategoría: Cables de instrumentación · Cable encauchetado · Cables apantallados · Cable para bandeja / tray cable
- **Automatización industrial** → subcategoría: Variadores de frecuencia · PLC · HMI · Sensores industriales · Arrancadores suaves

`apantallado` solo acepta `Sí` o `No` (coincide con los términos del atributo `pa_apantallado`).

## Imágenes (`data/images/`)

- Pon cada imagen en `data/images/` con el nombre exacto de la columna `imagen`.
- **Formato:** WebP o JPG.
- **Tamaño:** máximo 1200 px de lado, objetivo **< 150 KB** por archivo.
- Las imágenes se versionan en git (`data/images/`). Revisamos pasar a git LFS o `.gitignore` solo si el repo se acerca a ~500 MB.
- Si falta la imagen referenciada, la importación avisa y omite la imagen (no falla la fila).

## Cómo importar

> **Backup obligatorio.** `scripts/import-products.sh` hace backup solo en importación real (no en dry-run); `--skip-backup` lo salta.

1. **Prepara** el CSV del lote y su registro de fuentes por SKU.
2. **Valida** sin tocar la base: `scripts/import-products.sh --csv data/products-batch-001.csv --source-register data/product-source-register-001.csv --dry-run`.
3. **Corrige errores** que reporte (SKU duplicado, categoría desconocida, campos obligatorios, columnas mal). En el formato base, una imagen faltante es advertencia; en lotes nuevos, una imagen declarada pero no entregada bloquea el lote. Un producto sin imagen se declara explícitamente con `estado_imagen=sin_imagen`.
4. **Respalda e importa**: `scripts/import-products.sh --csv data/products-batch-001.csv --source-register data/product-source-register-001.csv`.
5. **Audita** alt text e imágenes después del lote y ejecuta el readiness de AIOSEO.
6. **Verifica** en el navegador: ficha de producto con tabla de especificaciones, categoría correcta, sin precio y WhatsApp con origen Surtilec.

Reglas: upsert por `sku` (no duplica), idempotente (re-importar el mismo CSV = 0 cambios), nunca pone precio. Los metadatos de fuente quedan en WordPress como campos internos `_surtilec_*`; no se muestran automáticamente al visitante.
