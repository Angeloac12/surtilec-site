#!/usr/bin/env node

/**
 * Build a review-only product inventory from Cables Colombia's public sitemap.
 *
 * This intentionally requests only the sitemap. The site's robots.txt asks
 * for a 60-second crawl delay on page requests, so product-page crawling is a
 * separate, permission-dependent phase. This script does not download images,
 * copy descriptions, or import anything into WordPress.
 *
 * Usage:
 *   node scripts/scrape-cablescolombia-sitemap.js
 *   node scripts/scrape-cablescolombia-sitemap.js data/cablescolombia-discovery.csv
 */

const fs = require('fs');
const path = require('path');

const siteUrl = 'https://cablescolombia.com';
const sitemapUrl = `${siteUrl}/sitemap.xml`;
const outputPath = path.resolve(process.argv[2] || 'data/cablescolombia-discovery.csv');

function decodeXml(value) {
  return String(value || '')
    .replace(/<!\[CDATA\[([\s\S]*?)\]\]>/g, '$1')
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#39;|&apos;/g, "'")
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .trim();
}

function tagValue(block, tag) {
  const escapedTag = tag.replace(':', '\\:');
  const match = block.match(new RegExp(`<${escapedTag}[^>]*>([\\s\\S]*?)</${escapedTag}>`, 'i'));
  return match ? decodeXml(match[1]) : '';
}

function slugTitle(url) {
  const slug = decodeURIComponent(url.split('/').filter(Boolean).pop() || '');
  return slug
    .replace(/[-_]+/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
    .trim();
}

function csv(value) {
  const text = String(value ?? '');
  return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

async function main() {
  const response = await fetch(sitemapUrl, {
    headers: {
      'user-agent': 'SurtilecCatalogResearch/1.0 (+https://surtilec.com/)',
      accept: 'application/xml,text/xml;q=0.9,*/*;q=0.1',
    },
  });

  if (!response.ok) {
    throw new Error(`Sitemap request failed: ${response.status} ${response.statusText}`);
  }

  const xml = await response.text();
  const blocks = [...xml.matchAll(/<url\b[^>]*>([\s\S]*?)<\/url>/gi)].map((match) => match[1]);
  const rows = [];
  const seen = new Set();
  let category = { parent: '', child: '' };

  for (const block of blocks) {
    const url = tagValue(block, 'loc');
    if (!url || seen.has(url)) continue;
    seen.add(url);

    const relative = url.replace(siteUrl, '');
    if (relative.startsWith('/categoria/')) {
      const parts = relative.replace(/^\/categoria\//, '').split('/').filter(Boolean);
      category = { parent: parts[0] || '', child: parts[1] || '' };
      continue;
    }
    if (!relative.startsWith('/producto/')) continue;

    const slug = relative.replace(/^\/producto\//, '').replace(/\/$/, '');
    const imageUrl = tagValue(block, 'image:loc');
    const imageTitle = tagValue(block, 'image:title');
    rows.push({
      skuCandidate: `CC-${slug.toUpperCase().replace(/[^A-Z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 80)}`,
      sourceName: imageTitle || slugTitle(url),
      category: category.parent,
      subcategory: category.child,
      productUrl: url,
      imageUrl,
      imageTitle,
      lastmod: tagValue(block, 'lastmod'),
      contentStatus: 'requiere_revision',
      imageStatus: imageUrl ? 'requiere_permiso' : 'sin_imagen_en_sitemap',
      notes: 'Inventario desde sitemap público. Validar SKU, ficha técnica, descripción propia, categoría Surtilec y permiso de imagen antes de importar.',
    });
  }

  const header = [
    'sku_candidato',
    'nombre_fuente',
    'categoria_fuente_slug',
    'subcategoria_fuente_slug',
    'producto_url',
    'imagen_url_externa',
    'titulo_imagen_fuente',
    'ultima_actualizacion_fuente',
    'estado_contenido',
    'estado_imagen',
    'notas',
  ];
  const output = [header, ...rows.map((row) => [
    row.skuCandidate,
    row.sourceName,
    row.category,
    row.subcategory,
    row.productUrl,
    row.imageUrl,
    row.imageTitle,
    row.lastmod,
    row.contentStatus,
    row.imageStatus,
    row.notes,
  ])].map((row) => row.map(csv).join(',')).join('\n') + '\n';

  fs.mkdirSync(path.dirname(outputPath), { recursive: true });
  fs.writeFileSync(outputPath, output, 'utf8');
  console.log(`Productos descubiertos: ${rows.length}`);
  console.log(`Con imagen referenciada en sitemap: ${rows.filter((row) => row.imageUrl).length}`);
  console.log(`Archivo: ${outputPath}`);
  console.log('No se descargaron imágenes ni se modificó WordPress.');
}

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
