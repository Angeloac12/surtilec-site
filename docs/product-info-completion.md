# Product Info Intake

The editable file is `data/product-info-to-complete.csv`. It contains only the
499 products currently held as hidden staging drafts. The generated research
batch is `data/products-complemented-generic.csv`, with the internal source
register in `data/product-complemented-source-register.csv` and the review
report in `data/product-complemented-research-report.csv`.

The complemented batch is already imported as hidden drafts. It uses
`Producto genérico Surtilec` only where an exact manufacturer reference was not
confirmed. It must not be treated as an exact manufacturer match.

## Fill these columns

- `marca`: confirmed manufacturer or brand.
- `referencia_fabricante`: exact manufacturer reference.
- `enlace_producto_oficial`: exact product page on the manufacturer or
  authorized supplier website.
- `enlace_ficha_tecnica`: official technical PDF or product datasheet.
- `enlace_imagen_directa`: direct image URL, only when the image is owned or
  authorized for Surtilec.
- `estado_derechos_imagen`: `propia`, `autorizada`, `pendiente` or `rechazada`.
- `referencia_permiso_imagen`: email, document or supplier reference proving
  permission. Leave blank when the status is `pendiente`.
- `datos_confirmados`: use `si` only after the reference and technical data
  match the official source.
- `observaciones`: optional clarification.

Do not change `sku`, `nombre_provisional` or `estado_staging`. Do not add
competitor URLs, source-site names or search-result URLs. Save the completed
file as UTF-8 CSV and return it for validation. Surtilec will generate the
Spanish name, description, SEO/AEO fields, image alt and media title after the
reference is verified.

To regenerate a clean blank copy:

```bash
node scripts/build-product-info-template.js
```
