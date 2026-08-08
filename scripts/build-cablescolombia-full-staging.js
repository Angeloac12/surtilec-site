#!/usr/bin/env node

/**
 * Build a complete internal crosswalk from the 528 discovery rows.
 *
 * Only the already verified pilot rows are treated as imported. Every other
 * row becomes a hidden staging draft candidate without public technical data
 * or an image.
 */

const crypto = require('crypto');
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

function normalize(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();
}

function cleanStagingName(value) {
  const cleaned = String(value || '')
    .replace(/https?:\/\/[^\s]+/gi, '')
    .replace(/cables\s*colombia(?:\.com)?/gi, '')
    .replace(/multimedia02\.s3[^\s,]*/gi, '')
    .replace(/\s+/g, ' ')
    .trim();
  return cleaned || 'Referencia de producto pendiente de verificación';
}

function stablePendingSku(sourceKey, name) {
  const digest = crypto.createHash('sha1').update(`${sourceKey}|${name}`).digest('hex').slice(0, 12).toUpperCase();
  return `SRT-PENDING-${digest}`;
}

function readRows(path) {
  return parseCsv(fs.readFileSync(path, 'utf8'));
}

const discovery = readRows('data/cablescolombia-discovery.csv');
const pilot = readRows('data/cablescolombia-pilot-review.csv');
const pilotCrosswalk = readRows('data/surtilec-pilot-manufacturer-crosswalk.csv');
const master = readRows('data/products-master.csv');

const pilotByName = new Map(pilot.map((row) => [normalize(row.nombre_fuente_interno), row]));
const crosswalkBySku = new Map(pilotCrosswalk.map((row) => [row.sku_surtilec, row]));
const masterBySku = new Map(master.map((row) => [row.sku, row]));
const masterByName = new Map();
for (const row of master) {
  const key = normalize(row.nombre);
  if (key && !masterByName.has(key)) masterByName.set(key, row);
}

const crosswalk = [];
const staging = [];
const seenStagingSku = new Set();

for (const row of discovery) {
  const sourceKey = row.sku_candidato || `DISCOVERY-${crypto.createHash('sha1').update(row.producto_url || row.nombre_fuente).digest('hex').slice(0, 12).toUpperCase()}`;
  const sourceName = row.nombre_fuente || row.nombre_catalogo;
  const normalizedName = normalize(sourceName);
  const pilotRow = pilotByName.get(normalizedName);
  let sku = '';
  let state = 'pendiente_investigacion';
  let brand = '';
  let manufacturerReference = '';
  let officialSource = '';
  let reason = 'Referencia descubierta; ficha oficial exacta, marca e imagen todavía pendientes.';

  if (pilotRow) {
    sku = pilotRow.sku_surtilec_candidato;
    const verified = crosswalkBySku.get(sku);
    if (verified && verified.estado === 'verificada') {
      state = 'importado_verificado';
      brand = verified.marca_confirmada;
      manufacturerReference = verified.referencia_fabricante;
      officialSource = verified.fuente_oficial;
      reason = 'Producto ya importado mediante lote verificado; no crear una segunda ficha.';
    } else if (verified) {
      state = 'pendiente_piloto';
      reason = verified.motivo;
      officialSource = verified.fuente_oficial;
    }
  }

  if (!sku && row.sku_fuente && masterBySku.has(row.sku_fuente)) {
    sku = row.sku_fuente;
    state = 'duplicado_existente';
    reason = 'El SKU de la fuente coincide con un SKU publicado existente; revisar antes de enriquecer.';
  }

  if (!sku && masterByName.has(normalizedName)) {
    sku = masterByName.get(normalizedName).sku;
    state = 'duplicado_existente';
    reason = 'El nombre normalizado coincide con un producto publicado existente; revisar antes de enriquecer.';
  }

  if (!sku) sku = stablePendingSku(sourceKey, sourceName);

  const safeName = cleanStagingName(sourceName);
  const stagingName = `Pendiente de verificación - ${safeName}`;
  const needsStaging = state !== 'importado_verificado';
  if (needsStaging && !seenStagingSku.has(sku)) {
    seenStagingSku.add(sku);
    staging.push({
      sku,
      nombre: stagingName,
      estado_staging: state,
      source_key: sourceKey,
      nota_staging: reason,
    });
  }

  crosswalk.push({
    source_key: sourceKey,
    nombre_fuente_interno: sourceName,
    categoria_fuente_slug: row.categoria_fuente_slug,
    subcategoria_fuente_slug: row.subcategoria_fuente_slug,
    producto_url_interno: row.producto_url,
    sku_surtilec: sku,
    estado: state,
    marca_confirmada: brand,
    referencia_fabricante: manufacturerReference,
    fuente_tecnica_oficial: officialSource,
    estado_imagen: 'sin_imagen',
    motivo: reason,
  });
}

const crosswalkHeader = [
  'source_key',
  'nombre_fuente_interno',
  'categoria_fuente_slug',
  'subcategoria_fuente_slug',
  'producto_url_interno',
  'sku_surtilec',
  'estado',
  'marca_confirmada',
  'referencia_fabricante',
  'fuente_tecnica_oficial',
  'estado_imagen',
  'motivo',
];
const stagingHeader = ['sku', 'nombre', 'estado_staging', 'source_key', 'nota_staging'];

function writeCsv(path, header, rows) {
  const output = [header.join(',')];
  for (const row of rows) output.push(header.map((field) => csv(row[field])).join(','));
  fs.writeFileSync(path, `${output.join('\n')}\n`);
}

writeCsv('data/cablescolombia-full-crosswalk.csv', crosswalkHeader, crosswalk);
writeCsv('data/cablescolombia-staging.csv', stagingHeader, staging);

const counts = crosswalk.reduce((result, row) => {
  result[row.estado] = (result[row.estado] || 0) + 1;
  return result;
}, {});
console.log(`Crosswalk rows: ${crosswalk.length}`);
console.log(`Staging drafts: ${staging.length}`);
console.log(JSON.stringify(counts));
