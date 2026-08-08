# Image Rights Workflow

For the current project state and authoritative image counts, read
`docs/llm-project-context.md` first. This document defines the operating rules.

The image queue is a rights-control register, not an image scraper. Images may
be researched online to identify the exact product, but they are not published
as Surtilec assets until ownership or commercial permission is documented.

## Queue

Generate the current queue with:

First export the live state so products that already have a featured image are
not requeued:

```bash
ssh -p 65002 u528798895@147.93.37.202 \
  "cd domains/surtilec.com/public_html && wp eval-file -" \
  < scripts/export-live-image-status.php \
  > data/live-product-image-status.csv
node scripts/build-image-rights-queue.js
```

The output is `data/product-image-rights-queue.csv`. It includes published
products without a live featured image and the hidden staging products. Each
row must have an exact SKU match before an image can be assigned.

## Approved states

- `pendiente`: no public image yet.
- `autorizada`: written commercial permission is recorded.
- `propia`: photographed or created by Surtilec.
- `rechazada`: source or match failed review.

`autorizada` requires a permission reference, permission date, image holder and
source URL. `propia` requires the photographer/owner and a file hash.

## Research versus publication

Manufacturer pages and PDFs may be used as private evidence to verify the
product name, reference, construction, ratings and packaging. For image work,
the candidate source is the literal `enlace_imagen_directa` value from the
queue; technical pages are not substituted as image sources. A public image
URL is not, by itself, permission to copy or re-host the image. Candidate
images therefore remain private until Surtilec has a supplier-provided asset,
written commercial permission, or a photo created by Surtilec.

The public product page must use Surtilec URLs only:

- Product canonical: `https://surtilec.com/producto/.../`
- Image URL: `https://surtilec.com/wp-content/uploads/...`
- Alt and media title: generated from the verified Surtilec product data.

External manufacturer or supplier URLs belong only in the private source
register. They must not be copied into public alt text, image filenames,
captions, product descriptions or schema. A technical source may be linked
publicly only when Surtilec intentionally chooses to cite it; otherwise the
source remains internal evidence.

## Exact-match review

Confirm manufacturer, reference, conductor count, gauge, jacket/insulation,
voltage and product presentation. A similar family image is not accepted as an
exact product image.

The authorized batch is also audited for reused source files across different
variants. If the manifest does not say `coincidencia_exacta_revisada`, the image
must not appear as the product thumbnail. Run the private audit with:

```bash
ruby scripts/build-image-exact-match-audit.rb
```

Before publication, quarantine the non-exact rows. This removes only the
featured-image assignment from staging products; it does not delete the media
attachment, its rights record or its local alt/title metadata:

```bash
bash scripts/quarantine-nonexact-product-images.sh --dry-run
bash scripts/quarantine-nonexact-product-images.sh --live
```

Only draft products are accepted by the live command. A replacement image must
be reviewed against the exact variant before it is assigned again.

For a source inventory with multiple variants, also compare the downloaded
file hash. A page title can be exact while the supplier still serves one
generic image for several calibres or constructions. The Surtilec flow is:

```bash
node scripts/build-exact-image-candidates.js
ruby scripts/download-exact-image-candidate-batch.rb
ruby scripts/build-verified-exact-image-manifest.rb
```

Only the verified manifest is imported. Rows marked
`fuente_visual_reutilizada_entre_variantes` remain without a featured image;
they need a supplier-approved asset specific to that variant.

## SEO metadata

Use the generated alt and media title only after the product match and image
rights are confirmed:

- Alt: `{product name}, marca {brand}, referencia {SKU} - Surtilec`
- Media title: `{product name} - {brand}`

Do not add keyword lists, competitor names, source-site URLs or unsupported
technical claims.
