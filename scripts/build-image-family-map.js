#!/usr/bin/env node

/**
 * Map every published product without a featured image onto a visual family.
 *
 * The 1,068 image-less products are not 1,068 distinct photographs: they
 * collapse onto a few dozen cable constructions. This file decides which
 * construction each product belongs to so one reviewed family image can serve
 * the whole group.
 *
 * Rules are ORDERED and first match wins. Order is load-bearing:
 *   - `iluminacion-led` runs first because those rows are not cable at all;
 *   - `vntc-apantallado` must beat `vntc-bandeja`;
 *   - `minero-tipo-g-ggc` must beat `tipo-w-epdm` (both say "tipo G/W");
 *   - the greedy catch-alls at the end are marked `media` and need review.
 *
 * The script exits non-zero if any row is left unclassified. Nothing may reach
 * the importer without a family.
 *
 * Usage: node scripts/build-image-family-map.js
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

/**
 * slug: file/meta identifier. etiqueta: human-readable, used in media titles.
 * confianza `media` means the rule matched on a generic token and a human must
 * confirm the group before it is imported.
 */
const FAMILIES = [
  ['iluminacion-led', 'Iluminación LED', /\bLED\b|\bA19\b|\bBR30\b|\bE26\b/i, 'alta'],
  ['fibra-optica', 'Cable de fibra óptica', /\bFO\b|fibra [oó]ptica|\bOM[1-5]\b|\bSM\b/i, 'alta'],
  ['coaxial', 'Cable coaxial', /coaxial|\bRG-?\d/i, 'alta'],
  // `cat\s?[56]\b` misses CAT5E/CAT6A — the trailing letter eats the boundary.
  ['red-utp', 'Cable de red UTP/FTP', /\bUTP\b|\bFTP\b|\bLAN\b|\bcat\s?-?[56][ae]?\b/i, 'alta'],
  ['termopar', 'Cable para termopar', /termopar|\bJX\b|\bKX\b|\bTX\b|\bEX\b/i, 'alta'],
  ['acsr-aluminio', 'Conductor de aluminio ACSR', /\bACSR\b|aa8000|aluminio/i, 'alta'],
  ['cobre-desnudo', 'Conductor de cobre desnudo', /desnudo/i, 'alta'],
  ['soldadura', 'Cable para soldadura', /soldadura|clase k/i, 'alta'],
  ['dlo', 'Cable DLO', /\bDLO\b/i, 'alta'],
  ['minero-tipo-g-ggc', 'Cable minero tipo G/GGC', /tipo g{1,2}c?\b|\bGGC\b/i, 'alta'],
  ['tipo-w-epdm', 'Cable tipo W EPDM', /tipo w\b|\bEPDM\b/i, 'alta'],
  ['soow-portatil', 'Cordón portátil SOOW encauchetado', /\bS[JO]?OOW\b|cord[oó]n port[aá]til|encauchetado/i, 'alta'],
  ['sdn-neopreno', 'Multiconductor SDN en neopreno', /\bSDN\b/i, 'alta'],
  ['srml-srg-k', 'Cable SRML/SRG-K de silicona', /\bSRML\b|\bSRG-?K\b|\bSF-?2\b|\bSFF-?2\b/i, 'alta'],
  ['tfe-mil', 'Cable TFE-E MIL-16878', /\bTFE-?E\b|MIL-?16878/i, 'alta'],
  ['fep-tipo-k', 'Cable FEP tipo K', /FEP tipo K|\bFEP\b/i, 'alta'],
  ['alta-temperatura', 'Cable de alta temperatura', /\bPTFE\b|tefl[oó]n|alta temperatura|silicona|fibra de vidrio|mica/i, 'alta'],
  ['gto-alta-tension', 'Cable GTO de alta tensión', /\bGTO\b/i, 'alta'],
  ['sumergible-bomba', 'Cable plano sumergible para bomba', /sumergible|bomba/i, 'alta'],
  ['bus-bajante', 'Cable bus bajante', /bus bajante/i, 'alta'],
  ['lszh-tc-er', 'Cable LSZH TC-ER', /smoke zero halogen|\bLSZH\b|\bTC-ER\b/i, 'alta'],
  ['armado-mc-aia', 'Cable armado MC/AIA', /\bAIA\b|metal clad|\barmor\b/i, 'alta'],
  ['multiconductor-datos-audio', 'Multiconductor de datos y audio', /multiconductor|databus|fieldbus|profibus|audio comercial|datalene|\bSHLD\b/i, 'alta'],
  ['antifraude', 'Cable antifraude TSEC', /antifraude|\bTSEC\b/i, 'alta'],
  ['feston', 'Cable festón', /fest[oó]n/i, 'alta'],
  ['solar-pv', 'Cable solar fotovoltaico', /solar|\bPV\b/i, 'alta'],
  ['spt-duplex-triplex', 'Cable SPT dúplex/tríplex', /\bSPT\b|d[uú]plex|tr[ií]plex|cu[aá]druplex/i, 'alta'],
  ['vntc-apantallado', 'Cable VNTC apantallado', /vntc.*apantall|apantall.*vntc/i, 'alta'],
  ['vntc-bandeja', 'Cable VNTC para bandeja', /\bVNTC\b/i, 'alta'],
  ['frep-cpe', 'Cable FREP/CPE', /\bFREP\b|\bCPE\b/i, 'alta'],
  ['xlpe-xptc', 'Cable XLPE/XPTC', /\bXLPE\b|\bXPTC\b|\bXHHW\b|\bUSE-?2\b/i, 'alta'],
  ['thhn-thwn-tffn', 'Alambre THHN/THWN-2', /\bTHHN\b|\bTHWN\b|\bTHHW\b|\bTFFN\b|\bTFN\b/i, 'alta'],
  ['termoflex-multiproposito', 'Cable Termoflex multipropósito', /termoflex|multiprop[oó]sito/i, 'alta'],
  ['exzhellent', 'Cable Exzhellent', /exzhellent/i, 'alta'],
  ['cobre-estanado-hookup', 'Alambre de cobre estañado', /cobre estañado|UL 1007|m[aá]quina herramienta|\bSIS\b/i, 'alta'],
  // Greedy catch-alls below. Everything specific has already matched.
  ['pltc-tc-instrumentacion', 'Cable PLTC/TC de instrumentación', /\bPLTC\b|\bTC\b|\bSPOS\b|\bSTOS\b|\bOS\b|\d+\s?PR\b|\d+\s?TR\b/i, 'media'],
  ['instrumentacion-apantallado', 'Cable apantallado de instrumentación', /apantall|instrumentaci[oó]n|\bpar\b|tripleta/i, 'media'],
  ['superflex-flexible', 'Cable flexible Superflex', /superflex|flexible/i, 'media'],
  ['control', 'Cable de control', /control/i, 'media'],
  ['puente-epr', 'Cable puente EPR', /puente|\bEPR\b/i, 'media'],
];

const INPUT = 'data/product-image-gap.csv';
const OUTPUT = 'data/product-image-family-map.csv';

if (!fs.existsSync(INPUT)) {
  console.error(`No se encontró ${INPUT}. Genera primero el export con scripts/export-missing-image-detail.php.`);
  process.exit(1);
}

const products = parseCsv(fs.readFileSync(INPUT, 'utf8'));
const rows = [];
const counts = new Map();
const unclassified = [];

for (const product of products) {
  const title = product.titulo || '';
  const match = FAMILIES.find(([, , pattern]) => pattern.test(title));

  if (!match) {
    unclassified.push(product);
    continue;
  }

  const [slug, label, pattern, confidence] = match;
  counts.set(slug, (counts.get(slug) || 0) + 1);
  rows.push({
    id: product.id,
    sku: product.sku,
    titulo: title,
    familia: slug,
    familia_etiqueta: label,
    confianza: confidence,
    regla: pattern.source,
    marca: product.marca || '',
    categorias: product.categorias || '',
  });
}

const header = ['id', 'sku', 'titulo', 'familia', 'familia_etiqueta', 'confianza', 'regla', 'marca', 'categorias'];
const output = [header.join(',')]
  .concat(rows.map((row) => header.map((key) => csv(row[key])).join(',')))
  .join('\n');
fs.writeFileSync(OUTPUT, `${output}\n`);

const ordered = [...counts.entries()].sort((a, b) => b[1] - a[1]);
console.log(`${rows.length} productos mapeados en ${ordered.length} familias → ${OUTPUT}`);
for (const [slug, count] of ordered) {
  const family = FAMILIES.find(([candidate]) => candidate === slug);
  console.log(`${String(count).padStart(5)}  ${slug.padEnd(30)} ${family[3]}`);
}

if (unclassified.length) {
  console.error(`\n${unclassified.length} productos sin familia. Añade una regla antes de continuar:`);
  for (const product of unclassified.slice(0, 20)) {
    console.error(`  ${product.sku}  ${product.titulo}`);
  }
  process.exit(1);
}
