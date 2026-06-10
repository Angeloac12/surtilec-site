# Guía del CSV maestro de productos (`data/products-master.csv`)

Este archivo es la **única fuente** para cargar productos al catálogo. Una fila = un producto.

> **Regla de oro:** las especificaciones (calibre, conductores, voltaje, normas, potencia, etc.) se toman **únicamente de las fichas técnicas del proveedor**. **Nunca se inventan.** Si un dato no está en la ficha, se deja vacío.

Las 3 filas con SKU que empieza por `EJEMPLO-` son **plantillas de muestra**: sirven para ver el formato. **No se publican** y deben borrarse antes de la importación real.

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
