#!/usr/bin/env node

/**
 * Match staging products to the exact source title in the private discovery
 * inventory. This produces an internal review queue; it does not download or
 * assign images.
 */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const queuePath = path.join(root, 'data/product-image-rights-queue.csv');
const discoveryPath = path.join(root, 'data/cablescolombia-discovery.csv');
const manifestPath = process.argv[2] || '/private/tmp/surtilec-authorized-image-batch-20260803/manifest.csv';
const outputPath = process.argv[3] || path.join(root, 'data/product-exact-image-candidates.csv');

function parseCsv(text, delimiter = ',') {
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
    } else if (!quoted && char === delimiter) {
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
  return rows.map((values) => Object.fromEntries(headers.map((header, index) => [header, values[index] || ''])));
}

function normalize(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();
}

function csv(value) {
  const text = String(value ?? '');
  return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

if (!fs.existsSync(queuePath) || !fs.existsSync(discoveryPath)) {
  throw new Error('Falta la cola o el inventario de descubrimiento.');
}

const queue = parseCsv(fs.readFileSync(queuePath, 'utf8'), ';');
const discovery = parseCsv(fs.readFileSync(discoveryPath, 'utf8'));
const currentManifest = fs.existsSync(manifestPath)
  ? parseCsv(fs.readFileSync(manifestPath, 'utf8'))
  : [];
const currentBySku = new Map(currentManifest.map((row) => [row.sku, row]));
const discoveryByName = new Map();

for (const row of discovery) {
  const name = normalize(row.nombre_fuente);
  if (!name || !row.imagen_url_externa) continue;
  if (!discoveryByName.has(name)) discoveryByName.set(name, []);
  discoveryByName.get(name).push(row);
}

const headers = [
  'sku',
  'nombre_surtilec',
  'nombre_fuente_coincidencia_exacta',
  'producto_url_fuente',
  'imagen_url_fuente',
  'metodo_coincidencia',
  'confianza',
  'estado_imagen_actual',
  'accion_recomendada',
];

const rows = queue.map((row) => {
  const sourceName = String(row.nombre_provisional_original || '')
    .replace(/^Pendiente de verificación\s*-\s*/i, '')
    .trim();
  const matches = discoveryByName.get(normalize(sourceName)) || [];
  const uniqueUrls = [...new Set(matches.map((match) => match.imagen_url_externa).filter(Boolean))];
  const current = currentBySku.get(row.sku) || {};
  const currentStatus = current.image_match_status || 'sin_manifest_actual';
  const queueProductUrl = String(row.enlace_producto_oficial || '').replace(/\/$/, '');
  const urlMatch = matches.find((match) => String(match.producto_url || '').replace(/\/$/, '') === queueProductUrl);
  const exact = Boolean(urlMatch || (matches.length > 0 && uniqueUrls.length === 1));
  const keepCurrent = currentStatus === 'coincidencia_exacta_revisada';
  const selected = exact ? (urlMatch || matches[0]) : null;
  const method = urlMatch
    ? 'nombre_original_fuente_exacto_y_url_fuente'
    : exact
      ? 'nombre_original_fuente_exacto'
      : matches.length
        ? 'nombre_ambiguo_varias_imagenes'
        : 'sin_nombre_fuente_exacto';

  return [
    row.sku,
    row.nombre_tecnico_seo,
    exact ? sourceName : '',
    selected?.producto_url || '',
    uniqueUrls[0] || '',
    method,
    exact ? 'alta' : 'pendiente',
    currentStatus,
    keepCurrent ? 'conservar_imagen_actual' : exact ? 'descargar_y_asignar_imagen_exacta' : 'mantener_sin_miniatura',
  ];
});

fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, `${headers.join(',')}\n${rows.map((row) => row.map(csv).join(',')).join('\n')}\n`);

const counts = new Map();
for (const row of rows) counts.set(row[6], (counts.get(row[6]) || 0) + 1);
console.log(`Candidatos escritos: ${outputPath}`);
for (const [status, count] of counts) console.log(`${status}: ${count}`);
