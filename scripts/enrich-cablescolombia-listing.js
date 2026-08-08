#!/usr/bin/env node

/**
 * Enrich the sitemap inventory with fields exposed in the public catalog page.
 *
 * The listing page contains source SKU, product title, category URL and a
 * thumbnail URL. This script does not download images, copy descriptions or
 * import products into WordPress.
 */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const inputPath = path.join(root, 'data/cablescolombia-discovery.csv');
const listingUrl = 'https://cablescolombia.com/productos';

function decodeHtml(value) {
  return String(value || '')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#39;|&apos;/g, "'")
    .replace(/&nbsp;/g, ' ')
    .replace(/&deg;/g, '°')
    .replace(/&#(\d+);/g, (_, code) => String.fromCodePoint(Number(code)))
    .replace(/&#x([0-9a-f]+);/gi, (_, code) => String.fromCodePoint(parseInt(code, 16)))
    .replace(/\s+/g, ' ')
    .trim();
}

function parseCsv(text) {
  const rows = [];
  let row = [];
  let field = '';
  let quoted = false;
  for (let i = 0; i < text.length; i += 1) {
    const char = text[i];
    const next = text[i + 1];
    if (quoted && char === '"' && next === '"') {
      field += '"';
      i += 1;
    } else if (char === '"') {
      quoted = !quoted;
    } else if (!quoted && char === ',') {
      row.push(field);
      field = '';
    } else if (!quoted && (char === '\n' || char === '\r')) {
      if (char === '\r' && next === '\n') i += 1;
      row.push(field);
      field = '';
      if (row.some((value) => value !== '')) rows.push(row);
      row = [];
    } else {
      field += char;
    }
  }
  if (field || row.length) {
    row.push(field);
    rows.push(row);
  }
  const headers = rows.shift() || [];
  return { headers, rows: rows.map((values) => Object.fromEntries(headers.map((header, index) => [header, values[index] || '']))) };
}

function csv(value) {
  const text = String(value ?? '');
  return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

async function main() {
  const response = await fetch(listingUrl, {
    signal: AbortSignal.timeout(60000),
    headers: {
      'user-agent': 'SurtilecCatalogResearch/1.0 (+https://surtilec.com/)',
      accept: 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.1',
    },
  });
  if (!response.ok) throw new Error(`Catalog request failed: ${response.status} ${response.statusText}`);

  const html = await response.text();
  const chunks = html.split('<div class="product-default products-dest').slice(1);
  const products = new Map();

  for (const chunk of chunks) {
    const productUrlMatch = chunk.match(/href="(https:\/\/cablescolombia\.com\/producto\/[^"?#]+)"/i);
    if (!productUrlMatch) continue;
    const productUrl = productUrlMatch[1];
    const skuMatch = chunk.match(/<div class="label-group">[\s\S]*?<em[^>]*>([\s\S]*?)<\/em>/i);
    const titleMatch = chunk.match(/<h3[^>]*class="product-title"[^>]*>[\s\S]*?<a[^>]*>([\s\S]*?)<\/a>/i);
    const categoryMatch = chunk.match(/<div class="category-list">[\s\S]*?<a[^>]*href="([^"]+)"/i);
    const imageMatch = chunk.match(/<img[^>]+src="([^"]+)"/i);
    products.set(productUrl, {
      sku: decodeHtml(skuMatch ? skuMatch[1] : ''),
      title: decodeHtml(titleMatch ? titleMatch[1] : ''),
      categoryUrl: categoryMatch ? categoryMatch[1] : '',
      imageUrl: imageMatch ? imageMatch[1] : '',
    });
  }

  if (products.size === 0) throw new Error('No product cards found in the public catalog page.');

  const { headers, rows } = parseCsv(fs.readFileSync(inputPath, 'utf8'));
  const extraHeaders = ['sku_fuente', 'nombre_catalogo', 'categoria_url_fuente', 'imagen_thumbnail_url'];
  const mergedHeaders = [...headers, ...extraHeaders.filter((header) => !headers.includes(header))];
  let matched = 0;
  let missing = 0;
  const outputRows = rows.map((row) => {
    const product = products.get(row.producto_url);
    if (!product) {
      missing += 1;
      return mergedHeaders.map((header) => row[header] || '');
    }
    matched += 1;
    return mergedHeaders.map((header) => {
      if (header === 'sku_fuente') return product.sku;
      if (header === 'nombre_catalogo') return product.title;
      if (header === 'categoria_url_fuente') return product.categoryUrl;
      if (header === 'imagen_thumbnail_url') return product.imageUrl;
      return row[header] || '';
    });
  });

  fs.writeFileSync(inputPath, [mergedHeaders, ...outputRows].map((row) => row.map(csv).join(',')).join('\n') + '\n', 'utf8');
  console.log(`Product cards found: ${products.size}`);
  console.log(`Discovery rows enriched: ${matched}`);
  console.log(`Discovery rows without listing match: ${missing}`);
  console.log(`Updated: ${inputPath}`);
  console.log('No images downloaded and no WordPress changes made.');
}

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
