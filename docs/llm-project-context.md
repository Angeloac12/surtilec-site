# Surtilec: LLM Project Context

> **Canonical handoff.** Read this file first when continuing work on Surtilec.
> Last verified: **2026-08-05**. Older entries in `docs/changelog.md` and
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
| Target generic staging products | `499`, `draft/hidden` |
| SEO/AEO description template | Applied to `1,385/1,385` published products and `499/499` target drafts with SKU |
| Target staging products with exact featured image | `76` (`12` original + `64` newly verified) |
| Target staging variants still blocked for image | `423` |
| Draft products with review status, including two older staging records | `425` |
| Draft products with exact status and a thumbnail | `76/76` |
| Draft products with review status and a thumbnail | `0` |
| Source-marker leakage in public WP content/meta/AIOSEO | `0` |

The `423` blocked target variants intentionally have no featured image. They
must not receive a family image, a different color, a different calibre, or an
image reused by multiple constructions. They remain drafts and must not be
published or indexed as complete product pages.

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

- Latest full backup before the final image cleanup:
  `backups/surtilec-20260804-110346.sql`.
- The current worktree is intentionally dirty with prior project changes. Do
  not revert unrelated files.
- `scripts/deploy.sh` deploys only the child theme and mu-plugins.
- `scripts/wp.sh` runs WP-CLI on Hostinger over SSH.
- Do not edit WordPress core, third-party plugins or server files directly.

## Files to Read Next

1. This file: `docs/llm-project-context.md`.
2. `docs/backlog.md` for remaining work.
3. `docs/image-rights-workflow.md` for image controls.
4. `docs/product-ingestion.md` for CSV/import rules.
5. `docs/changelog.md` for chronological history.
