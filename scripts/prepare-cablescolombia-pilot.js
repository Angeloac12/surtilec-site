#!/usr/bin/env node

/**
 * Prepare a review-only pilot from the sitemap discovery inventory.
 *
 * It selects 50 high-priority cable references, maps their source taxonomy to
 * Surtilec's taxonomy, and creates review-only Surtilec names/descriptions from
 * explicit tokens in each reference name. It does not invent manufacturer
 * references/specs, download images, or import products.
 */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const discoveryPath = path.join(root, 'data/cablescolombia-discovery.csv');
const currentPath = path.join(root, 'data/products-master.csv');
const outputPath = path.join(root, 'data/cablescolombia-pilot-review.csv');

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

function parseReferenceName(name, sourceCategory) {
  const raw = String(name || '').replace(/[º°]/g, '°').replace(/\s+/g, ' ').trim();
  const lower = raw.toLowerCase();
  const facts = [];
  const pending = [
    'SKU o referencia del fabricante',
    'marca',
    'ficha técnica y norma aplicable',
  ];
  const configMatch = raw.match(/\b(\d+)\s*[xX]\s*(\d{1,2})(?:\s*\+\s*(\d{1,2}))?(?:\s*(AWG))?\b/i);
  const singleGaugeMatch = raw.match(/\bN\s*(\d{1,2})\b/i);
  const awgMatch = raw.match(/\b(\d{1,2})\s*AWG\b/i);
  const colorMatch = raw.match(/\b(amarillo|azul|blanco|negro|rojo|verde)\b/i);
  const unitMatch = raw.match(/\bX\s*(rollo|metro)\b/i);
  const voltageMatch = raw.match(/\b(\d+(?:\.\d+)?)\s*V\b/i);
  const temperatureMatch = raw.match(/\b(\d+)\s*°?C\b/i);
  const hasCopper = /\bcu\b/i.test(raw);
  const hasPvc = /\bpvc\b/i.test(raw);
  const hasTc = /\btc\b/i.test(raw);
  const hasLs = /\bls\b/i.test(raw);
  const hasPc = /\bpc\b/i.test(raw);

  const gauge = singleGaugeMatch?.[1] || awgMatch?.[1] || configMatch?.[2] || '';
  const config = configMatch
    ? `${configMatch[1]} x ${configMatch[2]}${configMatch[3] ? ` + ${configMatch[3]}` : ''}${configMatch[4] ? ' AWG' : ''}`
    : gauge
      ? `${gauge} AWG`
      : '';
  const color = colorMatch ? colorMatch[1].toLowerCase() : '';
  const unit = unitMatch ? unitMatch[1].toLowerCase() : '';

  if (config) facts.push(`configuración: ${config}`);
  if (color) facts.push(`color: ${color}`);
  if (unit) facts.push(`unidad indicada: ${unit}`);
  if (hasCopper) facts.push('conductor indicado: cobre');
  if (hasPvc) facts.push('aislamiento indicado: PVC');
  if (voltageMatch) facts.push(`tensión indicada: ${voltageMatch[1]} V`);
  if (temperatureMatch) facts.push(`temperatura indicada: ${temperatureMatch[1]} °C`);
  if (hasTc) facts.push('clasificación indicada: TC');
  if (hasLs) facts.push('clasificación indicada: LS');
  if (hasPc) pending.push('significado de la sigla PC');

  if (sourceCategory === 'alambre-cable-thhn-thw-cu') {
    pending.push('tipo exacto de aislamiento (THHN/THWN-2 u otro)', 'capacidad de corriente', 'norma y tensión nominal');
  } else if (sourceCategory === 'cable-control') {
    pending.push('aplicación autorizada', 'tensión y temperatura confirmadas en ficha');
  } else if (sourceCategory === 'cable-encauchetado') {
    pending.push('tipo de chaqueta', 'tensión, temperatura y aplicación confirmadas en ficha');
  }

  return { raw, config, gauge, color, unit, voltage: voltageMatch?.[1] || '', temperature: temperatureMatch?.[1] || '', hasCopper, hasPvc, hasTc, hasLs, hasPc, facts, pending };
}

function buildSurtilecCopy(name, sourceCategory) {
  const ref = parseReferenceName(name, sourceCategory);
  let sku = 'SRT-POR-VERIFICAR';
  let title = 'Producto eléctrico por verificar';
  let short = 'Referencia en revisión. Se publicará únicamente después de validar la ficha técnica, la referencia del fabricante y la imagen autorizada.';

  if (sourceCategory === 'alambre-cable-thhn-thw-cu') {
    const unitLabel = ref.unit === 'rollo' ? 'por rollo' : ref.unit === 'metro' ? 'por metro' : '';
    const suffix = [ref.color, unitLabel].filter(Boolean).join(', ');
    title = `Alambre eléctrico ${ref.gauge ? `${ref.gauge} AWG` : 'por verificar'}${suffix ? `, ${suffix}` : ''}`;
    sku = `SRT-ALAMBRE-${ref.gauge || 'VERIFICAR'}${ref.color ? `-${ref.color.toUpperCase()}` : ''}${ref.unit ? `-${ref.unit.toUpperCase()}` : ''}`;
    short = `Alambre eléctrico${ref.gauge ? ` calibre ${ref.gauge} AWG` : ''}${ref.color ? ` en color ${ref.color}` : ''}${unitLabel ? `, con venta indicada ${unitLabel}` : ''}. Confirma aislamiento, norma, tensión y capacidad en la ficha técnica.`;
  } else if (sourceCategory === 'cable-control') {
    const construction = [ref.hasCopper ? 'cobre' : '', ref.hasPvc ? 'PVC' : ''].filter(Boolean).join(', ');
    title = `Cable de control ${ref.config || 'configuración por verificar'}${construction ? `, ${construction}` : ''}${ref.voltage ? `, ${ref.voltage} V` : ''}`;
    sku = `SRT-CTRL-${(ref.config || 'VERIFICAR').replace(/\s+/g, '').replace(/\+/g, '-')}${ref.hasCopper ? '-CU' : ''}${ref.hasPvc ? '-PVC' : ''}${ref.voltage ? `-${ref.voltage}V` : ''}`;
    short = `Cable de control con ${ref.config || 'configuración por verificar'}${construction ? `, ${construction}` : ''}${ref.voltage ? ` y tensión indicada de ${ref.voltage} V` : ''}. Confirma clasificación, temperatura, aplicación y norma en la ficha técnica.`;
  } else if (sourceCategory === 'cable-encauchetado') {
    title = `Cable encauchetado ${ref.config || 'configuración por verificar'}${ref.hasCopper ? ', cobre' : ''}`;
    sku = `SRT-ENCAU-${(ref.config || 'VERIFICAR').replace(/\s+/g, '').replace(/\+/g, '-')}${ref.hasCopper ? '-CU' : ''}`;
    short = `Cable encauchetado con ${ref.config || 'configuración por verificar'}${ref.hasCopper ? ' y conductores de cobre' : ''}. Confirma chaqueta, tensión, temperatura, aplicación y norma en la ficha técnica.`;
  }

  const long = [
    `<p>${short}</p>`,
    '<p>La ficha técnica del fabricante es la fuente final para confirmar las condiciones de selección e instalación.</p>',
  ].join('');

  return {
    ref,
    sku,
    title,
    short,
    long,
    confirmed: ref.facts.join('; '),
    pending: [...new Set(ref.pending)].join('; '),
  };
}

const categoryMap = {
  'alambre-cable-thhn-thw-cu': ['Cable THHN / THWN-2', ''],
  'cable-control': ['Cables de control', ''],
  'cable-encauchetado': ['Cables especiales', 'Cable encauchetado'],
};

const discovery = parseCsv(fs.readFileSync(discoveryPath, 'utf8'));
const current = parseCsv(fs.readFileSync(currentPath, 'utf8'));
const currentNames = new Set(current.map((row) => normalize(row.nombre)).filter(Boolean));

const priority = [
  ['cable-control', 12],
  ['cable-encauchetado', 20],
  ['alambre-cable-thhn-thw-cu', 18],
];
const selected = [];

for (const [sourceCategory, limit] of priority) {
  const matches = discovery.filter((row) => row.categoria_fuente_slug === sourceCategory).slice(0, limit);
  selected.push(...matches);
}

const headers = [
  'sku_surtilec_candidato',
  'referencia_fuente_interna',
  'nombre_fuente_interno',
  'nombre_surtilec_propuesto',
  'descripcion_corta_propuesta',
  'descripcion_larga_propuesta',
  'categoria_surtilec',
  'subcategoria_surtilec',
  'unidad_venta_propuesta',
  'datos_confirmados_por_referencia',
  'datos_pendientes_de_verificacion',
  'coincidencia_catalogo_actual',
  'estado_fuente',
  'estado_imagen',
  'evidencia_interna_url',
  'imagen_interna_url',
  'campos_pendientes',
];

const rows = selected.map((row) => {
  const mapped = categoryMap[row.categoria_fuente_slug] || ['', ''];
  const copy = buildSurtilecCopy(row.nombre_fuente, row.categoria_fuente_slug);
  const exactMatch = currentNames.has(normalize(copy.title));
  return [
    copy.sku,
    row.sku_fuente,
    row.nombre_fuente,
    copy.title,
    copy.short,
    copy.long,
    mapped[0],
    mapped[1],
    copy.ref.unit,
    copy.confirmed,
    copy.pending,
    exactMatch ? 'coincidencia_exacta_revisar' : 'no_coincidencia_exacta',
    'pendiente_verificacion',
    'pendiente_permiso',
    row.producto_url,
    row.imagen_url_externa,
    'referencia fabricante; marca; ficha técnica; validación de especificaciones; permiso de imagen',
  ];
});

const output = [headers, ...rows].map((row) => row.map(csv).join(',')).join('\n') + '\n';
fs.writeFileSync(outputPath, output, 'utf8');

console.log(`Pilot rows: ${rows.length}`);
console.log(`Exact current-catalog name matches: ${rows.filter((row) => row[11] === 'coincidencia_exacta_revisar').length}`);
console.log(`Output: ${outputPath}`);
console.log('Review-only output. Public Surtilec copy was generated from explicit reference-name tokens only.');
console.log('No products or images were imported.');
