#!/usr/bin/env node

/** Build the small, user-editable intake sheet for the 499 staging products. */

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

const staging = parseCsv(fs.readFileSync('data/cablescolombia-staging.csv', 'utf8'));
const header = [
  'sku',
  'nombre_provisional',
  'estado_staging',
  'marca',
  'referencia_fabricante',
  'enlace_producto_oficial',
  'enlace_ficha_tecnica',
  'enlace_imagen_directa',
  'estado_derechos_imagen',
  'referencia_permiso_imagen',
  'datos_confirmados',
  'observaciones',
];

const output = [header.join(',')];
for (const product of staging) {
  const row = {
    sku: product.sku,
    nombre_provisional: product.nombre,
    estado_staging: product.estado_staging,
    marca: '',
    referencia_fabricante: '',
    enlace_producto_oficial: '',
    enlace_ficha_tecnica: '',
    enlace_imagen_directa: '',
    estado_derechos_imagen: 'pendiente',
    referencia_permiso_imagen: '',
    datos_confirmados: 'pendiente',
    observaciones: '',
  };
  output.push(header.map((field) => csv(row[field])).join(','));
}

fs.writeFileSync('data/product-info-to-complete.csv', `${output.join('\n')}\n`);
console.log(`Product info template: ${staging.length} products.`);
