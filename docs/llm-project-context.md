# Surtilec: LLM Project Context

> **Canonical handoff.** Read this file first when continuing work on Surtilec.
> Last verified: **2026-08-08**. Older entries in `docs/changelog.md` and
> `docs/backlog.md` are historical unless this file says otherwise.

## Project

- **Site:** Surtilec, Spanish B2B catalog and quote-request website for Colombia.
- **Production domain:** `https://surtilec.com`.
- **Stack:** WordPress, GeneratePress child theme, WooCommerce catalog mode,
  YITH Request a Quote, Contact Form 7, Turnstile, AIOSEO, LiteSpeed Cache,
  ACF and WP Mail SMTP.
- **Hosting:** Hostinger Premium.
- **Remote WordPress path:** `domains/surtilec.com/public_html`.
- **Language:** Spanish (`es-CO`).
- **Business model:** catalog and quotation requests; do not add prices or
  checkout behavior unless explicitly requested.

## Current State

These are the authoritative counts from the latest remote verification:

| Area | Current state |
| --- | --- |
| Published products | `1,385` |
| Published products indexable | `1,385` — the photo gate was removed, see Indexation below |
| Published products still without a featured image | `1,068` |
| Products in the XML sitemap | `1,385` (`product-sitemap.xml` + `product-sitemap2.xml`) |
| Product SEO titles / descriptions cut mid-word | `0` (was `628` / `1,321`) |
| Duplicate product title groups | `0` (was `25`) |
| Product categories | `43`, all with a meta description; `29` still have no visible intro |
| `llms.txt` | Live, `423 KB`, lists all `1,385` products and `36` categories |
| Paginated archives | `noindex, follow` (was `noindex, nofollow`) |
| Target generic staging products | `499`, `draft/hidden` |
| SEO/AEO description template | Applied to `1,385/1,385` published products and `499/499` target drafts with SKU |
| Target staging products with exact featured image | `76` (`12` original + `64` newly verified) |
| Target staging variants still blocked for image | `423` |
| Draft products with review status, including two older staging records | `425` |
| Draft products with exact status and a thumbnail | `76/76` |
| Draft products with review status and a thumbnail | `0` |
| Source-marker leakage in public WP content/meta/AIOSEO | `0` |

## Indexation

The photo gate is gone. `1,068` published products were previously `noindex`,
excluded from the XML sitemap and excluded from the LLM files for one reason:
no featured image. A product page carrying an SKU, brand, a full attribute spec
table and the Q&A copy is worth indexing without a photo, so the gate was
released and all `1,385` are now indexable and listed.

Do not reintroduce it. `scripts/aioseo-product-image-readiness.php` is now a
read-only report; `scripts/aioseo-release-image-gated-noindex.php` is what
cleared the gate and only ever touches rows carrying this project's own
`_surtilec_aioseo_noindex_reason` marker, so a noindex set by hand in AIOSEO is
never overwritten.

Product schema omits the `image` key when there is no photo, which stays valid.
The blocked-image rules below still govern which *drafts* may be published and
which images may be assigned; they no longer govern indexation.

## AIOSEO gotchas found the hard way

- **Settings are stored twice.** Alongside `aioseo_options` and
  `aioseo_options_dynamic`, AIOSEO keeps `aioseo_options_dynamic_localized`:
  a flat array keyed by the same path joined with underscores. Where a key
  exists in the overlay, the overlay is what renders. Writing only the nested
  option changes the admin screen while the live page keeps its old value.
- **`globalRobotsMeta.default` overrides the individual robots checkboxes.**
  AIOSEO evaluates `if ( default || nofollowPaginated )`, so while `default` is
  true the paginated settings in the UI do nothing at all.
- **llms.txt is a generated file, not a route.** Enabling
  `sitemap.llms.enable` only schedules an Action Scheduler job. Trigger
  `aioseo()->llms->generateLlmsTxt()` to produce it immediately.
- **Always verify against rendered output**, not against the option you just
  wrote. Each of the above passed a database read and still failed on the page.

The `423` blocked target variants intentionally have no featured image. They
must not receive a family image, a different color, a different calibre, or an
image reused by multiple constructions. They remain drafts and must not be
published or indexed as complete product pages.

**This ban covers the drafts only.** Published products follow a separate,
deliberate family-image policy, documented under Family Images below.

## Image Truth Model

The first authorized batch contained one downloaded image per SKU, but many
source URLs reused a family image. A page title alone is not enough proof: the
same visual file can be served for several calibres, colors, conductors or
voltage constructions.

An image is assignable only when all of these are true:

1. The product SKU exists in WordPress and is a staging draft.
2. The source title/product record matches the intended variant.
3. Manufacturer/reference, conductor configuration, gauge, color, insulation,
   voltage and presentation do not conflict with the image candidate.
4. The downloaded file hash is not reused by a different variant in the same
   batch or by an already verified different variant.
5. Rights/permission is recorded privately.
6. Alt text and media title use Surtilec product data only.

Current image verification files:

- `data/product-image-rights-queue.csv`: original private rights/source queue.
- `data/product-image-exact-match-audit.csv`: first mismatch audit; historical
  record showing why the original 487 assignments were quarantined.
- `data/product-exact-image-candidates.csv`: source-title candidate mapping for
  all `499` staging variants.
- `data/product-exact-image-verification.csv`: final hash verification for the
  `487` newly downloaded candidates: `64` assignable and `423` blocked.
- Private downloaded assets, when still available:
  `/private/tmp/surtilec-exact-image-batch-20260804/`.

The 423 blocked candidate files were not uploaded to WordPress. Six duplicate
Media attachments from an interrupted import were marked with
`_surtilec_image_quarantined=1`; their files were preserved for recovery and
are not active product thumbnails.

## Family Images

Published products carry a family image where no exact photo exists. The
catalog's image-less products collapse onto `38` cable constructions, so one
reviewed image per construction serves the whole group, and the product page
always says so.

The rules that make this honest rather than a shortcut:

- The family must be registered in `data/product-image-family-rights.csv` as
  `propia` or `autorizada` with a reference. `scripts/build-family-image-manifest.rb`
  aborts otherwise, so the register gates the import instead of describing it.
- The row carries `image_match_status=imagen_de_familia_referencia`. The
  importer accepts that alongside `coincidencia_exacta_revisada` and still
  quarantines everything else.
- The page renders *"Imagen de referencia. El producto puede variar en calibre,
  color y presentación según la referencia solicitada."* from
  `_surtilec_imagen_referencia`.
- Each SKU gets its own copy of the file, `familia-<slug>-<sku>.webp`. One
  shared attachment would leave a whole family wearing the last product's alt.

The first batch is `lote=20260811`: `10` Surtilec-owned renders covering the
`10` largest families. Roll a batch or a single family back with
`scripts/rollback-family-images.php`; it drops the thumbnail and the family
meta and keeps the media files.

Families still without an image are listed as `pendiente` in the rights CSV.
They need the same treatment before their products get a photo.

## Product Content State

All target staging products have a unified SEO/AEO description structure:

1. Product identification.
2. Explicitly known specifications.
3. Intended application with qualification where exact data is pending.
4. `¿Qué producto es?`
5. `¿Para qué sirve?`
6. Confirmation questions before purchase.
7. `¿Cómo solicitar la cotización?`
8. Technical family source when available.

The copy uses `Producto genérico Surtilec` when the exact manufacturer
reference is not verified. It must not invent a brand, reference, voltage,
standard, availability, image match or delivery promise.

## Safe Continuation Workflow

For a new authorized image batch:

```bash
# 1. Build source-title candidates; review the CSV before downloading.
node scripts/build-exact-image-candidates.js

# 2. Download into the private temporary batch directory.
ruby scripts/download-exact-image-candidate-batch.rb

# 3. Compare hashes and create the verified upload manifest.
ruby scripts/build-verified-exact-image-manifest.rb

# 4. Validate remotely without changing WordPress.
bash scripts/import-verified-exact-images.sh --dry-run

# 5. Only after backup and review, assign verified rows to staging drafts.
bash scripts/import-verified-exact-images.sh --live
```

The normal image importer is guarded: in live mode it accepts only
`coincidencia_exacta_revisada`. The quarantine script accepts only draft
products and removes a featured-image assignment without deleting the Media
file:

```bash
bash scripts/quarantine-nonexact-product-images.sh --dry-run
bash scripts/quarantine-nonexact-product-images.sh --live
```

For a new family image (published products), see Family Images above:

```bash
node scripts/build-image-family-map.js
bash scripts/import-family-images.sh --family=<slug> --dry-run
bash scripts/import-family-images.sh --family=<slug> --live
```

One family per run. LiteSpeed serves the old page until it is purged, so
`wp litespeed-purge all` before checking rendered output — the database was
already correct in testing while the page still showed no image.

After every image batch, verify remotely that:

- exact-status products have a thumbnail;
- review-status products have no thumbnail;
- published product counts did not change;
- `scripts/audit-source-leakage.php` reports zero;
- alt text is present and contains no source-site marker.

## Source and Privacy Rules

- Source URLs, source names and research notes stay in local/private CSVs or
  private manifests.
- WordPress public titles, descriptions, alt text, filenames, captions, schema
  and AIOSEO fields must contain Surtilec data only.
- Never place competitor/source-site names or source URLs in public metadata.
- A public image URL is not proof of permission. Keep the private rights
  reference with the batch.
- Never publish a product merely because a similar source page exists.

## Launch Gate

The production domain is connected and the published catalog is operating, but
the `499` target staging products are not launch-ready as a group. Before
publishing any target draft, confirm:

- exact manufacturer/reference or an explicitly approved generic status;
- exact technical fields and presentation;
- exact, rights-approved image or an approved Surtilec placeholder policy;
- alt/title and AIOSEO metadata;
- no source leakage;
- quote flow and WhatsApp source label;
- no unintended indexation for incomplete products.

Do not bulk-publish the 499 staging products until the blocked-image decision is
resolved product by product.

## Backups and Continuation Notes

- Latest full backup: `backups/surtilec-20260808-124817.sql`, taken before the
  product SEO copy rebuild.
- Earlier reference point, before the final image cleanup:
  `backups/surtilec-20260804-110346.sql`.
- SEO/AEO work since 2026-08-07 lives on branch `feat/seo-aeo-hardening` and is
  not merged. `scripts/wp.sh` on this project passes arguments through the
  shell unquoted, so anything with quotes or parentheses must go through
  `eval-file` rather than `eval`.
- `scripts/deploy.sh` deploys only the child theme and mu-plugins.
- `scripts/wp.sh` runs WP-CLI on Hostinger over SSH.
- Do not edit WordPress core, third-party plugins or server files directly.

## Next Highest-Value Work

1. Visible category intros. `29` of `43` categories render no on-page copy,
   including Cable para bandeja (`393` products) and Cables apantallados
   (`317`). The meta description is generated, but the page itself is thin.
   This is buyer-facing copy and wants a human.
2. Real photography for the `1,068` published products without one. They are
   indexed now, but a product page with an image still converts better.
3. Products per page is `10`, so large categories span dozens of pages. Raising
   it shortens the crawl path now that pagination is followed.
4. `LocalBusiness` still has no `openingHours`, `geo` or `priceRange`. That data
   is not recorded anywhere in the project; it was deliberately left out rather
   than invented.
5. The homepage and regular pages emit no `BreadcrumbList`. The AIOSEO
   `breadcrumb` reference is stripped so nothing dangles, but a real trail
   would be better.

## Files to Read Next

1. This file: `docs/llm-project-context.md`.
2. `docs/backlog.md` for remaining work.
3. `docs/image-rights-workflow.md` for image controls.
4. `docs/product-ingestion.md` for CSV/import rules.
5. `docs/changelog.md` for chronological history.
