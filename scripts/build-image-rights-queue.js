#!/usr/bin/env node

/**
 * Build a rights-controlled image queue for products without a local image.
 * This file records work to do; it never downloads or publishes an image.
 */

const fs = require('fs');

function parseCsv(text) {
  const rows = [];
  let row = [];
  let field = '';
  let quoted = false;
  for (let i = 0; i < text.length; i += 1) {
    const char = text[i];
    const next = text[i + 1];
    if (char === '"' && quoted && next === '"') {
      field += '"';
      i += 1;
      continue;
    }
    if (char === '"') {
      quoted = !quoted;
      continue;
    }
    if (char === ',' && !quoted) {
      row.push(field);
      field = '';
      continue;
    }
    if ((char === '\n' || char === '\r') && !quoted) {
      if (char === '\r' && next === '\n') i += 1;
      row.push(field);
      if (row.some((value) => value !== '')) rows.push(row);
      row = [];
      field = '';
      continue;
    }
    field += char;
  }
  if (field || row.length) {
    row.push(field);
    if (row.some((value) => value !== '')) rows.push(row);
  }
  const header = rows.shift() || [];
  return rows.map((values) => Object.fromEntries(header.map((key, index) => [key, values[index] || ''])));
}

function csv(value) {
  const text = value === undefined || value === null ? '' : String(value);
  return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

function read(path) {
  return parseCsv(fs.readFileSync(path, 'utf8'));
}

const products = [
  ...read('data/products-master.csv'),
  ...read('data/products-verified-pilot.csv'),
];
const staging = read('data/cablescolombia-staging.csv');
const liveStatus = fs.existsSync('data/live-product-image-status.csv')
  ? read('data/live-product-image-status.csv')
  : [];
const liveStatusBySku = new Map(liveStatus.filter((row) => row.sku).map((row) => [row.sku, row]));
const seen = new Set();
const rows = [];

for (const product of products) {
  if (!product.sku || seen.has(product.sku) || product.imagen) continue;
  const live = liveStatusBySku.get(product.sku);
  if (live && live.has_featured_image === '1') continue;
  seen.add(product.sku);
  const brand = product.marca || '';
  const reference = product.sku;
  rows.push({
    sku: product.sku,
    nombre_producto: product.nombre,
    marca: brand,
    referencia_fabricante: '',
    fuente_candidata_url: '',
    titular_imagen: '',
    estado_derechos: 'pendiente',
    referencia_permiso: '',
    fecha_permiso: '',
    tipo_asset: '',
    nombre_archivo: '',
    sha256: '',
    coincidencia_exacta: 'pendiente',
    alt_text: `${product.nombre}${brand ? `, marca ${brand}` : ''}, referencia ${reference} - Surtilec`,
    titulo_media: `${product.nombre}${brand ? ` - ${brand}` : ''}`,
    revisado_en: '',
    notas: 'Buscar primero en fabricante o proveedor autorizado. No publicar sin permiso o foto propia.',
  });
}

for (const product of staging) {
  if (!product.sku || seen.has(product.sku)) continue;
  seen.add(product.sku);
  rows.push({
    sku: product.sku,
    nombre_producto: product.nombre,
    marca: '',
    referencia_fabricante: '',
    fuente_candidata_url: '',
    titular_imagen: '',
    estado_derechos: 'pendiente',
    referencia_permiso: '',
    fecha_permiso: '',
    tipo_asset: '',
    nombre_archivo: '',
    sha256: '',
    coincidencia_exacta: 'pendiente',
    alt_text: `${product.nombre}, referencia ${product.sku} - Surtilec`,
    titulo_media: `${product.nombre} - Surtilec`,
    revisado_en: '',
    notas: `Producto en staging (${product.estado_staging}). Validar fabricante, referencia y derechos antes de publicar.`,
  });
}

const header = [
  'sku',
  'nombre_producto',
  'marca',
  'referencia_fabricante',
  'fuente_candidata_url',
  'titular_imagen',
  'estado_derechos',
  'referencia_permiso',
  'fecha_permiso',
  'tipo_asset',
  'nombre_archivo',
  'sha256',
  'coincidencia_exacta',
  'alt_text',
  'titulo_media',
  'revisado_en',
  'notas',
];
const output = [header.join(',')];
for (const row of rows) output.push(header.map((field) => csv(row[field])).join(','));
fs.writeFileSync('data/product-image-rights-queue.csv', `${output.join('\n')}\n`);
console.log(`Image rights queue: ${rows.length} products.`);
