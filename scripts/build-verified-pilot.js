#!/usr/bin/env node

/**
 * Build the first manufacturer-verified product batch.
 *
 * The batch is intentionally generated only from exact manufacturer matches.
 * Unmatched pilot references are kept in the internal crosswalk and are not
 * converted into products until a manufacturer publishes the exact build.
 */

const fs = require('fs');

const date = '2026-08-03';
const nexansPage = 'https://www.nexans.co/es/products/Construcci%C3%B3n/Cables-de-Cobre-de-Baja-Tensi%C3%B3n-Aislados/Alambre-THHN-THWN-2/Wire-Type-24145.html';
const nexansPdf = {
  12: 'https://www.nexans.co/.rest/catalog/v1/product/pdf/200303',
  14: 'https://www.nexans.co/.rest/catalog/v1/product/pdf/200300',
};
const procablesPage = 'https://co.prysmian.com/es/termoflex-y-termoflex-multiprop%C3%B3sito';
const procablesPdf = 'https://co.prysmian.com/sites/co.prysmian.com/files/media/documents/FT%20TERMOFLEX%20MP_COL_1.pdf';
const pendingControlSource = 'https://co.prysmian.com/sites/co.prysmian.com/files/media/documents/FT-ALAMBRES-THHN.pdf';

const colors = ['amarillo', 'azul', 'blanco', 'negro', 'rojo', 'verde'];
const pilot = [];
const sources = [];
const crosswalk = [];

function csv(value) {
  const text = value === undefined || value === null ? '' : String(value);
  return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

function addCrosswalk(row) {
  crosswalk.push(row);
}

function addProduct(product, source) {
  pilot.push(product);
  sources.push(source);
  addCrosswalk({
    sku: product.sku,
    estado: 'verificada',
    familia_revisada: source.familia,
    marca_confirmada: source.marca,
    referencia_fabricante: source.referencia,
    fuente_oficial: source.fuente,
    motivo: 'Coincidencia exacta de calibre, conductores y construcción publicada por el fabricante.',
    siguiente_paso: 'Importar en lote verificado; imagen propia o autorizada pendiente.',
  });
}

function thhnProduct(awg, color, unidad) {
  const ref = awg === 12 ? '200303' : '200300';
  const skuUnit = unidad === 'metro' ? 'METRO' : 'ROLLO';
  const sku = `SRT-ALAMBRE-${awg}-${color.toUpperCase()}-${skuUnit}`;
  const nombreUnidad = unidad === 'metro' ? 'por metro' : 'rollo 100 m';
  const nombre = `Alambre THHN/THWN-2 Centelsa ${awg} AWG ${color} - ${nombreUnidad}`;
  const descripcionCorta = `Alambre de cobre sólido ${awg} AWG con aislamiento PVC y cubierta de nylon. 600 V y 90 °C para sitios secos, húmedos y mojados.`;
  const descripcionLarga = `<p>Conductor de cobre suave sólido ${awg} AWG con aislamiento de PVC retardante a la llama y cubierta de nylon. La ficha oficial indica 600 V y 90 °C para sitios secos, húmedos y mojados.</p><p>Aplicación documentada: alambrado de edificaciones, circuitos alimentadores y ramales. Normas publicadas: UL 83 y NTC 1332. Esta variante se identifica por aislamiento ${color}.</p>`;
  addProduct(
    {
      sku,
      nombre,
      categoria: 'Cable THHN / THWN-2',
      subcategoria: '',
      marca: 'Centelsa by Nexans',
      calibre_awg: String(awg),
      num_conductores: '1',
      voltaje: '600 V',
      apantallado: 'No',
      chaqueta: 'Nylon',
      norma: 'UL 83; NTC 1332',
      aplicacion: 'Alambrado de edificaciones; circuitos alimentadores y ramales',
      potencia_hp: '',
      voltaje_entrada: '',
      serie: 'THHN/THWN-2 600 V',
      descripcion_corta: descripcionCorta,
      imagen: '',
      descripcion_larga: descripcionLarga,
      unidad_venta: unidad === 'metro' ? 'metro' : 'rollo de 100 m',
      temperatura_maxima: '90 °C',
    },
    {
      sku,
      referencia_fabricante: ref,
      marca: 'Centelsa by Nexans',
      proveedor: 'Centelsa by Nexans Colombia',
      fuente_datos_url: nexansPage,
      ficha_tecnica_url: nexansPdf[awg],
      fuente_imagen_url: '',
      estado_fuente: 'verificada',
      estado_imagen: 'sin_imagen',
      verificado_en: date,
      notas: `Referencia Nexans ${ref}; ficha oficial ${awg} AWG; empaque publicado: rollo de 100 m. La unidad metro es una modalidad comercial de Surtilec y queda sujeta a disponibilidad.`,
      familia: 'Alambres THHN/THWN-2 600 V RoHS',
      fuente: nexansPage,
      referencia: ref,
      marca: 'Centelsa by Nexans',
    },
  );
}

for (const color of colors) {
  thhnProduct(12, color, 'rollo');
}
for (const color of colors) {
  thhnProduct(14, color, 'metro');
}
for (const color of colors) {
  thhnProduct(12, color, 'metro');
}

const procablesMatches = [
  ['SRT-ENCAU-5x8AWG-CU', 5, 8, '31370269501'],
  ['SRT-ENCAU-5x10AWG-CU', 5, 10, '31370249501'],
  ['SRT-ENCAU-5x12AWG-CU', 5, 12, '31370229501'],
  ['SRT-ENCAU-5x14AWG-CU', 5, 14, '31371392501'],
  ['SRT-ENCAU-2x8-CU', 2, 8, '31370269201'],
  ['SRT-ENCAU-2x10-CU', 2, 10, '31370249201'],
  ['SRT-ENCAU-2x12-CU', 2, 12, '31370229201'],
  ['SRT-ENCAU-2x14-CU', 2, 14, '31371392001'],
  ['SRT-ENCAU-2x16-CU', 2, 16, '31371391001'],
  ['SRT-ENCAU-2x18-CU', 2, 18, '31371390001'],
  ['SRT-ENCAU-3x8-CU', 3, 8, '31370269301'],
];

for (const [sku, conductors, awg, ref] of procablesMatches) {
  const nombre = `Cable multipropósito Termoflex MP Procables ${conductors} x ${awg} AWG`;
  const descripcionCorta = `Cable flexible de cobre ${conductors} x ${awg} AWG con aislamiento y cubierta de PVC. 600 V y 90 °C; construcción publicada para instalaciones eléctricas multipropósito.`;
  const descripcionLarga = `<p>Cable multipropósito Termoflex MP con ${conductors} conductores de cobre flexible ${awg} AWG, aislamiento PVC, cubierta intermedia de nylon y chaqueta exterior PVC.</p><p>La ficha oficial indica 600 V, 90 °C y temperatura mínima de operación de -25 °C. La familia publica uso en instalaciones fijas de potencia, bandejas portacables, enterrado directo, bombas sumergibles y extensiones o herramientas pesadas, según la selección de instalación.</p><p>Normas y certificaciones publicadas: UL 1277, NTC 5916, UL 83, UL 758 y UL 1063. Entrega en carrete según acuerdo comercial.</p>`;
  addProduct(
    {
      sku,
      nombre,
      categoria: 'Cable flexible',
      subcategoria: 'Cable encauchetado',
      marca: 'Procables',
      calibre_awg: String(awg),
      num_conductores: String(conductors),
      voltaje: '600 V',
      apantallado: 'No',
      chaqueta: 'PVC',
      norma: 'UL 1277; NTC 5916; UL 83; UL 758; UL 1063; RETIE',
      aplicacion: 'Instalaciones fijas de potencia; bandejas; enterrado directo; bombas sumergibles; extensiones y herramientas pesadas',
      potencia_hp: '',
      voltaje_entrada: '',
      serie: 'Termoflex MP',
      descripcion_corta: descripcionCorta,
      imagen: '',
      descripcion_larga: descripcionLarga,
      unidad_venta: 'carrete',
      temperatura_maxima: '90 °C',
    },
    {
      sku,
      referencia_fabricante: ref,
      marca: 'Procables',
      proveedor: 'Procables / Prysmian Colombia',
      fuente_datos_url: procablesPage,
      ficha_tecnica_url: procablesPdf,
      fuente_imagen_url: '',
      estado_fuente: 'verificada',
      estado_imagen: 'sin_imagen',
      verificado_en: date,
      notas: `Coincidencia exacta con el código ${ref} en la tabla oficial Termoflex MP para ${conductors} conductores de ${awg} AWG. El fabricante indica entrega en carretes según acuerdo comercial.`,
      familia: 'Termoflex MP',
      fuente: procablesPdf,
      referencia: ref,
      marca: 'Procables',
    },
  );
}

const pending = [
  ['SRT-CTRL-7x12AWG-CU-PVC', 'Cable de control 7 x 12 AWG, cobre, PVC', pendingControlSource, 'Configuración 7 x 12 AWG sin referencia de fabricante confirmada.'],
  ['SRT-CTRL-2x14-20AWG-CU-PVC-600V', 'Cable de control 2 x 14 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-4x14-20AWG-CU-PVC-600V', 'Cable de control 4 x 14 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-6x14-20AWG-CU-PVC-600V', 'Cable de control 6 x 14 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-9x14-20AWG-CU-PVC-600V', 'Cable de control 9 x 14 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-2x12-20AWG-CU-PVC-600V', 'Cable de control 2 x 12 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-4x12-20AWG-CU-PVC-600V', 'Cable de control 4 x 12 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-6x12-20AWG-CU-PVC-600V', 'Cable de control 6 x 12 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-9x12-20AWG-CU-PVC-600V', 'Cable de control 9 x 12 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-2x10-20AWG-CU-PVC-600V', 'Cable de control 2 x 10 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-4x10-20AWG-CU-PVC-600V', 'Cable de control 4 x 10 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-CTRL-9x10-20AWG-CU-PVC-600V', 'Cable de control 9 x 10 + 20 AWG, cobre, PVC, 600 V', pendingControlSource, 'La composición incluye un conductor adicional de 20 AWG que no aparece en la ficha revisada.'],
  ['SRT-ENCAU-7x16AWG-CU', 'Cable encauchetado 7 x 16 AWG, cobre', procablesPdf, 'La tabla Termoflex MP revisada no publica 7 conductores en 16 AWG.'],
  ['SRT-ENCAU-7x14AWG-CU', 'Cable encauchetado 7 x 14 AWG, cobre', procablesPdf, 'La tabla Termoflex MP revisada no publica 7 conductores en 14 AWG.'],
  ['SRT-ENCAU-7x10AWG-CU', 'Cable encauchetado 7 x 10 AWG, cobre', procablesPdf, 'La tabla Termoflex MP revisada no publica 7 conductores en 10 AWG.'],
  ['SRT-ENCAU-6x18AWG-CU', 'Cable encauchetado 6 x 18 AWG, cobre', procablesPdf, 'La tabla Termoflex MP revisada no publica 6 conductores en 18 AWG.'],
  ['SRT-ENCAU-6x16AWG-CU', 'Cable encauchetado 6 x 16 AWG, cobre', procablesPdf, 'La tabla Termoflex MP revisada no publica 6 conductores en 16 AWG.'],
  ['SRT-ENCAU-6x14AWG-CU', 'Cable encauchetado 6 x 14 AWG, cobre', procablesPdf, 'La tabla Termoflex MP revisada no publica 6 conductores en 14 AWG.'],
  ['SRT-ENCAU-6x12AWG-CU', 'Cable encauchetado 6 x 12 AWG, cobre', procablesPdf, 'La tabla Termoflex MP revisada no publica 6 conductores en 12 AWG.'],
  ['SRT-ENCAU-5x18AWG-CU', 'Cable encauchetado 5 x 18 AWG, cobre', procablesPdf, 'La tabla Termoflex MP revisada no publica 5 conductores en 18 AWG.'],
  ['SRT-ENCAU-5x16AWG-CU', 'Cable encauchetado 5 x 16 AWG, cobre', procablesPdf, 'La tabla Termoflex MP revisada no publica 5 conductores en 16 AWG.'],
];

for (const [sku, nombre, fuente, motivo] of pending) {
  addCrosswalk({
    sku,
    estado: 'pendiente',
    familia_revisada: sku.startsWith('SRT-CTRL') ? 'Cable de control 600 V' : 'Termoflex MP',
    marca_confirmada: '',
    referencia_fabricante: '',
    fuente_oficial: fuente,
    motivo,
    siguiente_paso: 'Conseguir ficha oficial que publique exactamente la misma composición antes de importar.',
  });
}

const productHeader = ['sku', 'nombre', 'categoria', 'subcategoria', 'marca', 'calibre_awg', 'num_conductores', 'voltaje', 'apantallado', 'chaqueta', 'norma', 'aplicacion', 'potencia_hp', 'voltaje_entrada', 'serie', 'descripcion_corta', 'imagen', 'descripcion_larga', 'unidad_venta', 'temperatura_maxima'];
const sourceHeader = ['sku', 'referencia_fabricante', 'marca', 'proveedor', 'fuente_datos_url', 'ficha_tecnica_url', 'fuente_imagen_url', 'estado_fuente', 'estado_imagen', 'verificado_en', 'notas'];
const crosswalkHeader = ['sku_surtilec', 'estado', 'familia_revisada', 'marca_confirmada', 'referencia_fabricante', 'fuente_oficial', 'motivo', 'siguiente_paso'];

function writeCsv(path, header, rows, map) {
  const output = [header.join(',')];
  for (const row of rows) {
    output.push(header.map((field) => csv(map(row, field))).join(','));
  }
  fs.writeFileSync(path, `${output.join('\n')}\n`);
}

writeCsv('data/products-verified-pilot.csv', productHeader, pilot, (row, field) => row[field]);
writeCsv('data/product-source-register-verified-pilot.csv', sourceHeader, sources, (row, field) => row[field]);
writeCsv('data/surtilec-pilot-manufacturer-crosswalk.csv', crosswalkHeader, crosswalk, (row, field) => field === 'sku_surtilec' ? row.sku : row[field]);

console.log(`Generated ${pilot.length} verified products and ${crosswalk.length} crosswalk rows.`);
console.log(`Verified: ${pilot.length}; pending: ${crosswalk.filter((row) => row.estado === 'pendiente').length}.`);
