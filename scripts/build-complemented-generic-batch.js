#!/usr/bin/env node

/**
 * Build a safe, generic product batch from the image/product queue.
 *
 * The queue names are treated as observable product data. Manufacturer names
 * and references are not inferred from a similar family. Each row receives an
 * official family-level research source for internal review, while the public
 * copy is generated only from facts visible in the product name.
 */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const queuePath = path.join(root, 'data/product-image-rights-queue.csv');
const outputPath = path.join(root, 'data/products-complemented-generic.csv');
const registerPath = path.join(root, 'data/product-complemented-source-register.csv');
const reportPath = path.join(root, 'data/product-complemented-research-report.csv');
const verifiedAt = '2026-08-03';

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
      if (row.some((value) => value !== '')) rows.push(row);
      row = [];
      field = '';
    } else {
      field += char;
    }
  }
  if (field || row.length) {
    row.push(field);
    if (row.some((value) => value !== '')) rows.push(row);
  }
  const headers = rows.shift() || [];
  return rows.map((values) => Object.fromEntries(headers.map((header, i) => [header, values[i] || ''])));
}

function csv(value) {
  const text = String(value ?? '');
  return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

function normalize(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
}

function cleanName(value) {
  return String(value || '')
    .replace(/^pendiente de verificación\s*-\s*/i, '')
    .replace(/\s+/g, ' ')
    .trim();
}

function stripSourceMarkers(value) {
  return String(value || '')
    .replace(/cables\s*colombia(?:\.com)?/gi, '')
    .replace(/multimedia02\.s3[^\s,]*/gi, '')
    .replace(/\s+/g, ' ')
    .trim();
}

function firstMatch(value, pattern) {
  const match = String(value || '').match(pattern);
  return match ? match[1] : '';
}

function titleCase(value) {
  return String(value || '').trim();
}

const familySources = [
  {
    test: /media[ -]?tensi[oó]n(?!.*aluminio).*cobre.*35\s*k[vV]/i,
    family: 'Media tensión cobre XLPE 35 kV',
    data: 'https://www.nexans.co/es/products/Redes-de-Transmisi%C3%B3n-y-Distribuci%C3%B3n-de-Energ%C3%ADa/Cables-de-Media-Tensi%C3%B3n/Cables-de-Cobre-Media-Tensi%C3%B3n/MV90CuXLPE-PVC35kV133.html',
  },
  {
    test: /media[ -]?tensi[oó]n.*aluminio.*35\s*k[vV]/i,
    family: 'Media tensión aluminio XLPE 35 kV',
    data: 'https://www.nexans.co/es/products/Redes-de-Transmisi%C3%B3n-y-Distribuci%C3%B3n-de-Energ%C3%ADa/Cables-de-Media-Tensi%C3%B3n/Cable-de-Aluminio-Media-Tensi%C3%B3n/MVAlXLPE-PVC35kV133.html',
  },
  {
    test: /media[ -]?tensi[oó]n(?!.*aluminio).*cobre.*15\s*k[vV]/i,
    family: 'Media tensión cobre XLPE 15 kV',
    data: 'https://www.nexans.co/es/products/Redes-de-Transmisi%C3%B3n-y-Distribuci%C3%B3n-de-Energ%C3%ADa/Cables-de-Media-Tensi%C3%B3n/Cables-de-Cobre-Media-Tensi%C3%B3n/MV90CuXLPE-PVC15kV133N33.html',
  },
  {
    test: /media[ -]?tensi[oó]n.*aluminio.*15\s*k[vV]/i,
    family: 'Media tensión aluminio XLPE 15 kV',
    data: 'https://www.nexans.co/es/products/Redes-de-Transmisi%C3%B3n-y-Distribuci%C3%B3n-de-Energ%C3%ADa/Cables-de-Media-Tensi%C3%B3n/Cable-de-Aluminio-Media-Tensi%C3%B3n/MTAlXLPE-TR-PE15kV.html',
  },
  {
    test: /solar|fotovoltaico/i,
    family: 'Cable fotovoltaico PV',
    data: 'https://co.prysmian.com/sites/default/files/atoms/files/FT-CABLE-PV-FOTOVOLTAICO_0.pdf',
  },
  {
    test: /fibra/i,
    family: 'Cable de fibra óptica drop/LSZH',
    data: 'https://www.commscope.com/product-type/cables/fiber-cables/drop-cables/item2160164-1/',
  },
  {
    test: /utp|cat[ -]?5e|red/i,
    family: 'Cable de red Cat 5e',
    data: 'https://www.belden.com/products/industrial-networking-cybersecurity/accessories/industrial-ethernet-cable/74001e',
  },
  {
    test: /aluminio.*(serie|s8000)|s8000/i,
    family: 'Aluminio serie 8000 THHN/THWN-2',
    data: 'https://www.nexans.co/es/products/Construcci%C3%B3n/Cables-de-Aluminio-de-Baja-Tensi%C3%B3n-Aislados/Cable-Aluminio-S8000-THWN-2/Single-Cor24584.html',
  },
  {
    test: /control|semaforizacion/i,
    family: 'Cable de control Termoflex MP',
    data: 'https://co.prysmian.com/es/termoflex-y-termoflex-multiprop%C3%B3sito',
  },
  {
    test: /encauchetado|duplex|bateria|soldador/i,
    family: 'Cable flexible de cobre',
    data: 'https://co.prysmian.com/es/termoflex-y-termoflex-multiprop%C3%B3sito',
  },
  {
    test: /libre.*halogeno|lszh/i,
    family: 'Cable de baja emisión de humos y halógenos',
    data: 'https://co.prysmian.com/sites/co.prysmian.com/files/media/documents/30%20FT_EXZHELLENT_GREEN_COBRE.pdf',
  },
  {
    test: /thhn|thw|thhw|aislado/i,
    family: 'Alambre/cable THHN/THWN-2 600 V',
    data: 'https://www.nexans.co/es/products/Construcci%C3%B3n/Cables-de-Cobre-de-Baja-Tensi%C3%B3n-Aislados/Cable-THHN-THWN-2/Cable-Type24144.html',
  },
  {
    test: /acsr|desnudo.*aluminio|aluminio/i,
    family: 'Conductor de aluminio o ACSR',
    data: 'https://www.nexans.co/es/products/Construcci%C3%B3n/Cables-de-Aluminio-de-Baja-Tensi%C3%B3n-Aislados.html',
  },
  {
    test: /desnudo.*cobre|cobre.*desnudo/i,
    family: 'Conductor de cobre desnudo',
    data: 'https://co.prysmian.com/en/products',
  },
  {
    test: /.*/,
    family: 'Cable industrial de construcción',
    data: 'https://co.prysmian.com/en/products',
  },
];

function sourceFor(name) {
  return familySources.find((entry) => entry.test.test(name)) || familySources[familySources.length - 1];
}

function categoryFor(name) {
  const value = normalize(name);
  if (value.includes('fibra')) return ['Cables de fibra óptica', ''];
  if (value.includes('utp') || value.includes('cat-5e')) return ['Cables de red', ''];
  if (value.includes('solar') || value.includes('fotovoltaico')) return ['Cables solares', ''];
  if (value.includes('media-tension')) return ['Cables de distribución', ''];
  if (value.includes('control') || value.includes('semaforizacion')) return ['Cables de control', ''];
  if (value.includes('soldador')) return ['Cable de soldadura', ''];
  if (value.includes('encauchetado') || value.includes('duplex') || value.includes('bateria')) return ['Cable flexible', ''];
  if (value.includes('acsr') || value.includes('aluminio')) return ['Cables de aluminio', ''];
  if (value.includes('desnudo')) return ['Conductores desnudos', ''];
  if (value.includes('libre-de-halogeno')) return ['Cables especiales', ''];
  if (value.includes('thhn') || value.includes('thw') || value.includes('aislado')) return ['Cable THHN / THWN-2', ''];
  return ['Cables especiales', ''];
}

function parseSpecs(name) {
  const value = normalize(name);
  const specs = [];
  const configuration = firstMatch(name, /(\d+\s*[xX]\s*\d+(?:\s*[-/+ ]\s*\d+){0,3}(?:\s*AWG)?)/i);
  const configurationCalibre = configuration ? firstMatch(configuration, /[xX]\s*(\d+(?:\/0|\s*[-/]\s*0)?)/i).replace(/\s*[-/]\s*0$/, '/0') : '';
  const calibre = configurationCalibre || firstMatch(name, /(?:N|No\.?\s*)?(\d+(?:\/0)?)\s*AWG/i) || firstMatch(name, /(\d+(?:\/0)?)\s*AWG/i);
  const kcmil = firstMatch(name, /(\d+(?:\.\d+)?)\s*KCMIL/i);
  const mm2 = firstMatch(name, /(\d+(?:\.\d+)?)\s*MM2/i);
  const conductors = configuration;
  const voltageMatch = name.match(/(\d+(?:\.\d+)?)\s*(KV|V)\b/i);
  const voltage = voltageMatch ? `${voltageMatch[1]} ${voltageMatch[2].toUpperCase()}` : '';
  const temperature = firstMatch(name, /(\d{2,3})\s*°?\s*C\b/i);
  const colors = ['amarillo', 'azul', 'blanco', 'negro', 'rojo', 'verde', 'morado', 'cristal'];
  const color = colors.find((candidate) => value.includes(candidate)) || '';
  const material = value.includes('cobre') || /\bcu\b/.test(value) ? 'cobre' : value.includes('aluminio') || /\bal\b/.test(value) ? 'aluminio' : value.includes('acero') ? 'acero' : '';
  const insulationParts = [];
  if (value.includes('xlpe')) insulationParts.push('XLPE');
  if (value.includes('pvc')) insulationParts.push('PVC');
  if (value.includes('lszh') || value.includes('libre de halogeno')) insulationParts.push('LSZH / libre de halógenos');
  if (!insulationParts.length && (value.includes('thhn') || value.includes('thw') || value.includes('thhw'))) insulationParts.push('termoplástico');
  const insulation = insulationParts.join(' / ');
  const presentation = value.includes('rollo') ? 'rollo' : value.includes('carrete') ? 'carrete' : value.includes('metro') ? 'metro' : value.includes('2 km') || value.includes('2km') ? 'rollo de 2 km' : value.includes('100m') ? 'rollo de 100 m' : '';

  if (calibre) specs.push(`Calibre: ${calibre} AWG`);
  if (kcmil) specs.push(`Sección: ${kcmil} kcmil`);
  if (mm2) specs.push(`Sección nominal: ${mm2} mm²`);
  if (conductors) specs.push(`Configuración indicada: ${conductors.replace(/\s*AWG$/i, '')}`);
  if (voltage) specs.push(`Tensión indicada: ${voltage.replace('KV', 'kV')}`);
  if (temperature) specs.push(`Temperatura indicada: ${temperature} °C`);
  if (color) specs.push(`Color indicado: ${color}`);
  if (material) specs.push(`Material indicado: ${material}`);
  if (insulation) specs.push(`Aislamiento o chaqueta indicada: ${insulation}`);
  if (value.includes('apantallado') || value.includes('pantalla')) specs.push('Construcción apantallada indicada');
  if (presentation) specs.push(`Presentación indicada: ${presentation}`);

  return {
    calibre: calibre ? `${calibre} AWG` : (mm2 ? `${mm2} mm²` : (kcmil ? `${kcmil} kcmil` : '')),
    conductors: conductors ? conductors.replace(/\s*AWG$/i, '') : '',
    voltage: voltage.replace('KV', 'kV'),
    temperature: temperature ? `${temperature} °C` : '',
    color,
    material,
    insulation,
    presentation,
    shielded: value.includes('apantallado') || value.includes('pantalla') ? 'Sí' : '',
    specs,
  };
}

function applicationFor(name) {
  const value = normalize(name);
  if (value.includes('fibra')) return 'Enlaces de fibra óptica y redes de telecomunicaciones.';
  if (value.includes('utp') || value.includes('cat-5e')) return 'Cableado estructurado y transmisión de datos.';
  if (value.includes('solar') || value.includes('fotovoltaico')) return 'Interconexión de módulos y equipos en sistemas fotovoltaicos.';
  if (value.includes('media-tension')) return 'Distribución eléctrica de media tensión; selección e instalación según proyecto.';
  if (value.includes('control') || value.includes('semaforizacion')) return 'Señales, automatización y control industrial.';
  if (value.includes('soldador')) return 'Alimentación de equipos de soldadura y conexiones de alta flexibilidad.';
  if (value.includes('bateria')) return 'Conexiones de batería y circuitos de corriente continua.';
  if (value.includes('desnudo') || value.includes('acsr')) return 'Puesta a tierra, distribución aérea o conexiones eléctricas según el tipo de conductor.';
  if (value.includes('thhn') || value.includes('thw') || value.includes('aislado')) return 'Alambrado en ductos, tableros y circuitos de baja tensión, sujeto a la ficha exacta.';
  return 'Aplicaciones eléctricas e industriales según la configuración seleccionada.';
}

function longDescription(name, sku, family, specs, application, sourceUrl) {
  const bullets = specs.length ? `<ul>${specs.map((item) => `<li>${item}</li>`).join('')}</ul>` : '<p>La configuración comercial se identifica en el nombre del producto.</p>';
  const source = sourceUrl ? `<p class="surtilec-source-note"><strong>Fuente técnica consultada:</strong> <a href="${sourceUrl}" target="_blank" rel="noopener">ficha de la familia técnica</a>. Esta fuente se utiliza para contrastar la familia y no sustituye la ficha exacta del producto suministrado.</p>` : '';
  const applicationText = String(application || '').replace(/[.\s]+$/, '');
  return [
    `<p><strong>${titleCase(name)}</strong> es una opción para ${applicationText.toLowerCase()}.</p>`,
    `<h2>¿Qué producto es?</h2><p>Esta ficha corresponde a un producto genérico Surtilec identificado comercialmente por el SKU <strong>${sku}</strong>. El nombre, la configuración y los atributos visibles son los datos publicados para solicitar una cotización.</p>`,
    `<h2>Especificaciones identificadas</h2>${bullets}`,
    `<h2>¿Para qué sirve?</h2><p>${applicationText}. La selección final depende del sistema, la instalación, la compatibilidad y la ficha técnica de la referencia disponible.</p>`,
    `<h2>¿Qué debo confirmar antes de comprar?</h2><p>Solicita a Surtilec la marca, referencia de fabricante, norma aplicable, longitud o presentación, disponibilidad y confirmación de la variante de imagen. No uses esta ficha como sustituto del diseño eléctrico o de la ficha técnica del fabricante.</p>`,
    `<h2>¿Cómo solicitar la cotización?</h2><p>Envía el SKU <strong>${sku}</strong>, la cantidad, la unidad requerida y el uso previsto. Así podremos validar la referencia disponible y responder con la alternativa correcta.</p>`,
    `<p class="surtilec-family-note">Familia técnica contrastada: ${family}. La marca y referencia exactas se confirman por SKU de proveedor antes del despacho.</p>`,
    source,
  ].join('');
}

const queue = parseCsv(fs.readFileSync(queuePath, 'utf8'), ';');
const imageManifestPath = '/private/tmp/surtilec-authorized-image-batch-20260803/manifest.csv';
const imageManifest = fs.existsSync(imageManifestPath) ? parseCsv(fs.readFileSync(imageManifestPath, 'utf8')) : [];
const imageBySku = new Map(imageManifest.map((row) => [row.sku, row.image_file]));

const productHeader = [
  'sku', 'nombre', 'categoria', 'subcategoria', 'marca', 'calibre_awg', 'num_conductores', 'voltaje', 'apantallado', 'chaqueta', 'norma', 'aplicacion', 'potencia_hp', 'voltaje_entrada', 'serie', 'descripcion_corta', 'imagen', 'descripcion_larga', 'unidad_venta', 'temperatura_maxima',
];
const sourceHeader = [
  'sku', 'referencia_fabricante', 'marca', 'proveedor', 'fuente_datos_url', 'ficha_tecnica_url', 'fuente_imagen_url', 'estado_fuente', 'estado_imagen', 'verificado_en', 'notas',
];
const reportHeader = ['sku', 'estado_investigacion', 'familia_contrastada', 'fuente_interna', 'datos_publicables', 'datos_pendientes', 'estado_imagen'];
const products = [];
const sources = [];
const report = [];

for (const row of queue) {
  const name = stripSourceMarkers(cleanName(row.nombre_tecnico_seo));
  const specs = parseSpecs(name);
  const family = sourceFor(name);
  const [category, subcategory] = categoryFor(name);
  const application = applicationFor(name);
  const image = imageBySku.get(row.sku) || '';
  const short = `Cotiza ${titleCase(name)} en Surtilec. SKU ${row.sku}. ${application} Confirma referencia, presentación y disponibilidad antes de instalar.`;
  products.push({
    sku: row.sku,
    nombre: titleCase(name),
    categoria: category,
    subcategoria: subcategory,
    marca: 'Producto genérico Surtilec',
    calibre_awg: specs.calibre,
    num_conductores: specs.conductors,
    voltaje: specs.voltage,
    apantallado: specs.shielded,
    chaqueta: specs.insulation,
    norma: '',
    aplicacion: application,
    potencia_hp: '',
    voltaje_entrada: '',
    serie: family.family,
    descripcion_corta: short,
    imagen: image,
    descripcion_larga: longDescription(name, row.sku, family.family, specs.specs, application, family.data),
    unidad_venta: specs.presentation,
    temperatura_maxima: specs.temperature,
  });
  sources.push({
    sku: row.sku,
    referencia_fabricante: '',
    marca: 'Producto genérico Surtilec',
    proveedor: 'Surtilec - investigación técnica de familia',
    fuente_datos_url: family.data,
    ficha_tecnica_url: family.data,
    fuente_imagen_url: '',
    estado_fuente: 'generica_verificada',
    estado_imagen: row.estado_derechos_imagen === 'autorizada' ? 'autorizada' : row.estado_derechos_imagen,
    verificado_en: verifiedAt,
    notas: `Se contrastó la familia técnica ${family.family}. No se asigna una referencia ni marca de fabricante por semejanza. El SKU interno ${row.sku} es la referencia comercial de Surtilec hasta confirmar la referencia suministrada.`,
  });
  report.push({
    sku: row.sku,
    estado_investigacion: 'familia_verificada_no_referencia_exacta',
    familia_contrastada: family.family,
    fuente_interna: family.data,
    datos_publicables: specs.specs.join(' | '),
    datos_pendientes: 'Marca y referencia de fabricante; ficha exacta; norma de la referencia; presentación disponible; coincidencia visual de variante.',
    estado_imagen: row.image_match_status || 'revisar_variante_antes_de_publicar',
  });
}

function writeCsv(filePath, header, rows) {
  const output = [header.join(',')];
  for (const row of rows) output.push(header.map((field) => csv(row[field])).join(','));
  fs.writeFileSync(filePath, `${output.join('\n')}\n`, 'utf8');
}

writeCsv(outputPath, productHeader, products);
writeCsv(registerPath, sourceHeader, sources);
writeCsv(reportPath, reportHeader, report);

const familyCounts = report.reduce((acc, row) => {
  acc[row.familia_contrastada] = (acc[row.familia_contrastada] || 0) + 1;
  return acc;
}, {});
console.log(`Productos complementados: ${products.length}`);
console.log(`Imágenes enlazadas al lote: ${products.filter((row) => row.imagen).length}`);
console.log(`Familias contrastadas: ${Object.keys(familyCounts).length}`);
console.log(JSON.stringify(familyCounts, null, 2));
console.log(`Producto CSV: ${outputPath}`);
console.log(`Registro interno: ${registerPath}`);
console.log(`Reporte: ${reportPath}`);
