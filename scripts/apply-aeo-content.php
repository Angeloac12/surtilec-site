<?php
/**
 * Apply source-backed AEO Q&A content to existing Surtilec URLs.
 *
 * Usage:
 *   ssh ... "cd <wp> && wp eval-file -" < scripts/apply-aeo-content.php
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$sources = array(
	'belden_vfd'       => '<a href="https://www.belden.com/products/cable/vfd-cable">Belden, VFD Cable</a>',
	'rockwell_vfd'     => '<a href="https://www.rockwellautomation.com/en-us/company/news/the-journal/why-proper-vfd-cable-termination-is-crucial.html">Rockwell Automation, VFD cable termination</a>',
	'southwire_thhn'   => '<a href="https://www.southwire.com/wire-cable/building-wire/simpull-sup-sup-thhn-thwn-2-copper/p/SPEC10000">Southwire, SIMpull THHN/THWN-2 Copper</a>',
	'southwire_soow'   => '<a href="https://www.southwire.com/wire-cable/flexible-cord/soow/p/PCORD11">Southwire, SOOW Flexible Cord</a>',
	'belden_control'   => '<a href="https://www.belden.com/products/cable/control-cable">Belden, Control Cable</a>',
	'belden_instr'     => '<a href="https://www.belden.com/products/cable/instrumentation-cable">Belden, Instrumentation Cable</a>',
	'belden_tray'      => '<a href="https://www.belden.com/products/cable/tray-tc-cable">Belden, Tray & TC Cable</a>',
	'minenergia_retie' => '<a href="https://www.minenergia.gov.co/es/misional/energia-electrica-2/reglamentos-tecnicos/reglamento-t%C3%A9cnico-de-instalaciones-el%C3%A9ctricas-retie/">Ministerio de Minas y Energía, RETIE</a>',
	'surtilec_quote'   => '<a href="/cotizar/solicitud/">Surtilec, solicitud de cotización</a>',
	'surtilec_upload'  => '<a href="/cotizar/subir-listado/">Surtilec, subir listado de materiales</a>',
);

$updated_posts = 0;
$updated_terms = 0;
$skipped       = array();

/**
 * Gutenberg paragraph block.
 *
 * @param string $html Paragraph HTML.
 * @return string
 */
function surtilec_aeo_p( $html ) {
	return "<!-- wp:paragraph --><p>$html</p><!-- /wp:paragraph -->\n";
}

/**
 * Gutenberg heading block.
 *
 * @param string $text  Heading text.
 * @param int    $level Heading level.
 * @return string
 */
function surtilec_aeo_h( $text, $level = 2 ) {
	if ( 3 === (int) $level ) {
		return '<!-- wp:heading {"level":3} --><h3>' . $text . "</h3><!-- /wp:heading -->\n";
	}

	return '<!-- wp:heading --><h2>' . $text . "</h2><!-- /wp:heading -->\n";
}

/**
 * Build the visible FAQ section. Each answer carries its source in the same
 * paragraph so the existing Article FAQ parser includes it in FAQPage JSON-LD.
 *
 * @param array<int,array{q:string,a:string,source:string}> $items FAQ items.
 * @return string
 */
function surtilec_aeo_faq_blocks( $items ) {
	$html = surtilec_aeo_h( 'Preguntas frecuentes' );
	foreach ( $items as $item ) {
		$html .= surtilec_aeo_h( $item['q'], 3 );
		$html .= surtilec_aeo_p( $item['a'] . '<br><strong>Fuente:</strong> ' . $item['source'] . '.' );
	}
	return $html;
}

/**
 * Replace a post's FAQ section from the first "Preguntas frecuentes" H2 onward.
 *
 * @param string $content Original post_content.
 * @param string $faq     New FAQ block HTML.
 * @param string $after   Optional content after the FAQ.
 * @return string
 */
function surtilec_aeo_replace_faq_section( $content, $faq, $after = '' ) {
	$marker = '<!-- wp:heading --><h2>Preguntas frecuentes</h2><!-- /wp:heading -->';
	$pos    = strpos( $content, $marker );
	if ( false === $pos ) {
		return rtrim( $content ) . "\n" . $faq . $after;
	}

	return rtrim( substr( $content, 0, $pos ) ) . "\n" . $faq . $after;
}

/**
 * Update a published post by ID.
 *
 * @param int    $post_id Post ID.
 * @param string $content New post_content.
 * @return bool
 */
function surtilec_aeo_update_post_content( $post_id, $content ) {
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return false;
	}

	$result = wp_update_post(
		wp_slash(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		),
		true
	);

	return ! is_wp_error( $result );
}

/**
 * Format category FAQ for the existing ACF textarea parser.
 *
 * @param array<int,array{q:string,a:string,source:string}> $items FAQ items.
 * @return string
 */
function surtilec_aeo_term_faq( $items ) {
	$blocks = array();
	foreach ( $items as $item ) {
		$blocks[] = 'P: ' . $item['q'] . "\n" . 'R: ' . $item['a'] . '<br><strong>Fuente:</strong> ' . $item['source'] . '.';
	}

	return implode( "\n\n", $blocks );
}

$hub_faq = array(
	array(
		'q'      => '¿Qué información debo enviar para cotizar un cable industrial?',
		'a'      => 'Envía referencia o nombre del cable, calibre AWG o sección, número de conductores o pares, tensión, longitud, marca preferida, ambiente de instalación y ciudad de entrega. Si tienes varias líneas, es mejor subir el listado para revisar cantidades y equivalencias sin cambiar la especificación.',
		'source' => $sources['surtilec_upload'],
	),
	array(
		'q'      => '¿Puedo cotizar si solo tengo una referencia Belden, Centelsa, Nexans, Procables u otra marca?',
		'a'      => 'Sí. La referencia ayuda a identificar familia, construcción y aplicación. Si no está completa, Surtilec puede pedir datos adicionales de ficha técnica antes de proponer una opción equivalente.',
		'source' => $sources['surtilec_quote'],
	),
	array(
		'q'      => '¿Qué cable se recomienda para un variador de frecuencia?',
		'a'      => 'Para el tramo entre variador y motor se revisa normalmente un cable VFD apantallado, porque el variador genera ruido eléctrico y frentes de tensión rápidos. La selección depende de potencia, tensión, longitud del recorrido, ambiente y recomendaciones del fabricante del variador.',
		'source' => $sources['belden_vfd'] . '; ' . $sources['rockwell_vfd'],
	),
	array(
		'q'      => '¿THHN y THWN-2 son lo mismo?',
		'a'      => 'No son exactamente lo mismo; son designaciones de uso/temperatura que pueden aparecer en un mismo conductor. En familias THHN/THWN-2, la parte THWN-2 se asocia con uso en lugares húmedos o secos hasta 90 °C, sujeto al código y a la ficha del fabricante.',
		'source' => $sources['southwire_thhn'],
	),
	array(
		'q'      => '¿Cuál es la diferencia entre cable de control y cable de instrumentación?',
		'a'      => 'El cable de control se usa para mando, señales discretas y circuitos de automatización. El cable de instrumentación se enfoca en señales de medición o control más sensibles, como señales analógicas, pares o triadas, donde la pantalla y la capacitancia pueden ser críticas.',
		'source' => $sources['belden_control'] . '; ' . $sources['belden_instr'],
	),
	array(
		'q'      => '¿Cuándo necesito cable apantallado?',
		'a'      => 'Conviene revisar cable apantallado cuando hay variadores, motores, bandejas compartidas, señales sensibles, instrumentación o riesgo de interferencia electromagnética. El tipo de pantalla se confirma según aplicación y ficha técnica.',
		'source' => $sources['belden_vfd'] . '; ' . $sources['belden_instr'],
	),
	array(
		'q'      => '¿Qué es cable TC o TC-ER para bandeja portacable?',
		'a'      => 'TC significa tray cable, pensado para instalación en bandejas, ductos o conduit según su certificación. TC-ER identifica cables aptos para exposed run cuando la ficha y la instalación lo permiten.',
		'source' => $sources['belden_tray'],
	),
	array(
		'q'      => '¿Cómo elegir el calibre AWG correcto?',
		'a'      => 'El calibre no se elige solo por nombre de equipo. Se revisa corriente, tensión, distancia, caída de tensión, temperatura, canalización, agrupamiento, material conductor y requisitos del proyecto. Para una compra segura, envía esos datos con el listado.',
		'source' => '<a href="/como-elegir-calibre-awg/">Surtilec, guía de calibre AWG</a>; ' . $sources['minenergia_retie'],
	),
	array(
		'q'      => '¿Qué debo confirmar para cumplir RETIE en Colombia?',
		'a'      => 'Debes confirmar que producto, instalación y documentación correspondan al uso del proyecto. RETIE regula instalaciones eléctricas en Colombia; por eso conviene indicar tensión, uso, ambiente, norma solicitada y certificaciones requeridas desde la cotización.',
		'source' => $sources['minenergia_retie'],
	),
	array(
		'q'      => '¿Surtilec despacha a toda Colombia?',
		'a'      => 'Sí. Surtilec opera desde Bogotá y coordina despachos a Colombia según la cotización, cantidades, disponibilidad y condiciones del proyecto.',
		'source' => $sources['surtilec_quote'],
	),
	array(
		'q'      => '¿Por qué algunos productos no tienen precio publicado?',
		'a'      => 'En cables industriales el precio puede depender de metales, marca, referencia, cantidad, disponibilidad y condiciones de entrega. Por eso el flujo correcto es cotizar con datos técnicos y cantidades reales.',
		'source' => $sources['surtilec_quote'],
	),
	array(
		'q'      => '¿Qué hago si no encuentro la referencia exacta en el catálogo?',
		'a'      => 'Envía la referencia, foto de placa, ficha técnica o listado de materiales. Surtilec revisa la aplicación y puede orientar la búsqueda sin inventar especificaciones ni cambiar requisitos del proyecto.',
		'source' => $sources['surtilec_upload'],
	),
);

$post_faq = array(
	1447 => array(
		array(
			'q'      => '¿Cuándo debo pedir cable de control en vez de instrumentación?',
			'a'      => 'Pide cable de control para mando, señales discretas, tableros, solenoides, actuadores y automatización general. Si la señal es de medición, analógica o sensible al ruido, revisa cable de instrumentación.',
			'source' => $sources['belden_control'] . '; ' . $sources['belden_instr'],
		),
		array(
			'q'      => '¿Qué datos separan una cotización de control de una de instrumentación?',
			'a'      => 'Para control: calibre, número de conductores, tensión y ambiente. Para instrumentación: número de pares/triadas, pantalla individual o global, tipo de señal, capacitancia requerida y certificación solicitada.',
			'source' => $sources['belden_instr'] . '; ' . $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿El cable de instrumentación siempre debe ser apantallado?',
			'a'      => 'No siempre, pero en señales sensibles o ambientes con ruido eléctrico se revisa pantalla individual, global o ambas. La decisión depende del tipo de señal, recorrido y equipos cercanos.',
			'source' => $sources['belden_instr'],
		),
		array(
			'q'      => '¿Puedo pedir una equivalencia sin cambiar la especificación?',
			'a'      => 'Sí, pero la equivalencia debe comparar construcción, tensión, temperatura, pantalla, cantidad de conductores/pares, cubierta y normas aplicables. Si falta un dato, se confirma antes de cotizar.',
			'source' => $sources['surtilec_upload'],
		),
	),
	1448 => array(
		array(
			'q'      => '¿THWN-2 sirve para lugares húmedos?',
			'a'      => 'En la familia Southwire THHN/THWN-2, la designación THWN-2 se indica para lugares húmedos o secos hasta 90 °C, sujeto al uso correcto y a códigos aplicables.',
			'source' => $sources['southwire_thhn'],
		),
		array(
			'q'      => '¿Qué significa 600 V en un conductor THHN/THWN-2?',
			'a'      => 'Es el régimen de tensión del producto. No define por sí solo la corriente admisible; para eso se revisan calibre, instalación, temperatura, agrupamiento y norma aplicable.',
			'source' => $sources['southwire_thhn'] . '; ' . $sources['minenergia_retie'],
		),
		array(
			'q'      => '¿Qué datos debo enviar para cotizar THHN/THWN-2?',
			'a'      => 'Envía calibre AWG o kcmil, color, material conductor, longitud, tensión, marca requerida, ciudad de entrega y si el proyecto exige certificación específica.',
			'source' => $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿THHN/THWN-2 reemplaza un cable VFD?',
			'a'      => 'No debe asumirse como reemplazo directo. THHN/THWN-2 es conductor de instalación; para tramos variador-motor se revisan cables VFD apantallados por ruido eléctrico y recomendaciones del fabricante.',
			'source' => $sources['southwire_thhn'] . '; ' . $sources['belden_vfd'],
		),
	),
	1449 => array(
		array(
			'q'      => '¿Qué cable se recomienda entre variador y motor?',
			'a'      => 'Se revisa cable VFD apantallado con construcción adecuada para PWM, EMI y condiciones industriales. La selección final depende de tensión, potencia, longitud y ambiente.',
			'source' => $sources['belden_vfd'] . '; ' . $sources['rockwell_vfd'],
		),
		array(
			'q'      => '¿Por qué importa la terminación del blindaje?',
			'a'      => 'El blindaje solo funciona bien si se conecta correctamente. Las guías de variadores recomiendan terminaciones que mantengan continuidad y reduzcan la impedancia del camino de ruido.',
			'source' => $sources['rockwell_vfd'],
		),
		array(
			'q'      => '¿La longitud del cable VFD cambia la cotización?',
			'a'      => 'Sí. La longitud impacta caída de tensión, ruido, recomendaciones del fabricante y posibles filtros o reactores. Por eso debe incluirse en la solicitud de cotización.',
			'source' => $sources['rockwell_vfd'] . '; ' . $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿Qué datos envío para cotizar cable VFD?',
			'a'      => 'Envía potencia del motor, tensión, longitud, número de conductores, tierra, ambiente, canalización, marca/modelo del variador y referencia deseada si existe.',
			'source' => $sources['surtilec_quote'],
		),
	),
	1450 => array(
		array(
			'q'      => '¿Qué significa TC en cable para bandeja?',
			'a'      => 'TC significa tray cable. Son cables diseñados para instalación en bandejas, ductos o conduit de acuerdo con su ficha técnica y normas aplicables.',
			'source' => $sources['belden_tray'],
		),
		array(
			'q'      => '¿Qué significa TC-ER?',
			'a'      => 'TC-ER identifica tray cable apto para exposed run cuando la certificación, la ficha técnica y la instalación lo permiten. No debe asumirse sin revisar el producto específico.',
			'source' => $sources['belden_tray'],
		),
		array(
			'q'      => '¿Dónde se usa cable de bandeja?',
			'a'      => 'Se usa en instalaciones industriales con bandejas, ductos o conduit, especialmente donde se busca ordenar rutas de potencia, control o instrumentación según diseño del proyecto.',
			'source' => $sources['belden_tray'],
		),
		array(
			'q'      => '¿Qué datos necesito para cotizar TC o TC-ER?',
			'a'      => 'Envía calibre, número de conductores, tensión, tipo de aislamiento/cubierta, si requiere TC-ER, ambiente, longitud y norma o marca solicitada.',
			'source' => $sources['surtilec_quote'],
		),
	),
	1451 => array(
		array(
			'q'      => '¿El calibre AWG se elige solo por amperaje?',
			'a'      => 'No. Además de corriente se revisan tensión, distancia, caída de tensión, temperatura, canalización, agrupamiento, material conductor y norma aplicable.',
			'source' => $sources['minenergia_retie'],
		),
		array(
			'q'      => '¿Qué pasa si aumento la distancia del cable?',
			'a'      => 'A mayor distancia puede aumentar la caída de tensión. Por eso la longitud del recorrido es un dato clave antes de confirmar calibre y producto.',
			'source' => '<a href="/como-elegir-calibre-awg/">Surtilec, guía de calibre AWG</a>',
		),
		array(
			'q'      => '¿Puedo pedir ayuda si solo tengo la carga o potencia?',
			'a'      => 'Sí. Envía potencia o corriente, tensión, distancia, ambiente, forma de instalación y ciudad. Con esos datos se orienta la cotización sin inventar especificaciones.',
			'source' => $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿El material conductor cambia la selección?',
			'a'      => 'Sí. Cobre y aluminio tienen propiedades distintas, por lo que no se deben intercambiar sin revisar diseño, terminales, norma y condiciones del proyecto.',
			'source' => $sources['minenergia_retie'],
		),
	),
	1452 => array(
		array(
			'q'      => '¿RETIE define qué cable comprar?',
			'a'      => 'RETIE establece requisitos técnicos para instalaciones eléctricas en Colombia. La compra debe alinearse con el diseño, certificación, uso previsto y documentación del producto.',
			'source' => $sources['minenergia_retie'],
		),
		array(
			'q'      => '¿Qué debo pedir en una cotización para proyecto RETIE?',
			'a'      => 'Indica aplicación, tensión, calibre, material, aislamiento, marca o norma requerida, ambiente, cantidad, ciudad de entrega y si necesitas soporte documental del producto.',
			'source' => $sources['minenergia_retie'] . '; ' . $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿Una ficha técnica reemplaza el diseño eléctrico?',
			'a'      => 'No. La ficha técnica describe el producto; el diseño y la instalación deben validarse por el responsable del proyecto conforme a normativa aplicable.',
			'source' => $sources['minenergia_retie'],
		),
		array(
			'q'      => '¿Surtilec puede confirmar compatibilidad normativa?',
			'a'      => 'Surtilec puede orientar la cotización según la información recibida y solicitar ficha técnica o certificación, pero los requisitos finales deben venir del diseño y del responsable técnico.',
			'source' => $sources['surtilec_quote'],
		),
	),
);

$term_faq = array(
	'cables-para-variadores-vfd' => array(
		array(
			'q'      => '¿Qué cable se recomienda para un variador de frecuencia?',
			'a'      => 'Para el tramo variador-motor se revisa normalmente cable VFD apantallado, diseñado para ayudar a controlar EMI, dV/dt y condiciones industriales. La selección depende de potencia, tensión, longitud, ambiente y recomendación del fabricante del variador.',
			'source' => $sources['belden_vfd'] . '; ' . $sources['rockwell_vfd'],
		),
		array(
			'q'      => '¿Cuándo usar cable VFD apantallado?',
			'a'      => 'Úsalo cuando el cable va entre variador y motor, cuando comparte ruta con señales sensibles o cuando el proyecto necesita reducir interferencia electromagnética. El tipo de pantalla se confirma con la ficha técnica.',
			'source' => $sources['belden_vfd'],
		),
		array(
			'q'      => '¿Qué datos necesito para cotizar cable VFD?',
			'a'      => 'Potencia del motor, tensión, longitud, número de conductores, tierra, ambiente, canalización, marca/modelo del variador y referencia deseada si existe.',
			'source' => $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿La longitud del cable afecta la selección?',
			'a'      => 'Sí. La longitud puede afectar ruido, caída de tensión y necesidad de accesorios como filtros o reactores según el fabricante del variador.',
			'source' => $sources['rockwell_vfd'],
		),
		array(
			'q'      => '¿Puedo usar THHN entre variador y motor?',
			'a'      => 'No debe asumirse como reemplazo directo. THHN/THWN-2 es conductor de instalación; para variadores se revisan cables VFD por apantallamiento y control de EMI.',
			'source' => $sources['southwire_thhn'] . '; ' . $sources['belden_vfd'],
		),
	),
	'cable-thhn-thwn'             => array(
		array(
			'q'      => '¿Qué significa THHN/THWN-2?',
			'a'      => 'Son designaciones de un conductor con aislamiento termoplástico y chaqueta de nylon. En la familia THHN/THWN-2, Southwire indica 600 V y usos húmedos o secos hasta 90 °C para THWN-2, sujeto a códigos aplicables.',
			'source' => $sources['southwire_thhn'],
		),
		array(
			'q'      => '¿Sirve THHN/THWN-2 para lugares húmedos?',
			'a'      => 'Cuando el conductor está marcado THWN-2, el fabricante lo describe para lugares húmedos o secos hasta 90 °C. La instalación debe validarse con la norma y el diseño del proyecto.',
			'source' => $sources['southwire_thhn'] . '; ' . $sources['minenergia_retie'],
		),
		array(
			'q'      => '¿Qué datos envío para elegir calibre AWG?',
			'a'      => 'Corriente o potencia, tensión, distancia, caída de tensión permitida, canalización, temperatura, agrupamiento, material conductor, color y ciudad de entrega.',
			'source' => '<a href="/como-elegir-calibre-awg/">Surtilec, guía de calibre AWG</a>',
		),
		array(
			'q'      => '¿Qué significa 600 V 90 °C?',
			'a'      => 'Son límites de la familia de producto, no una autorización automática para cualquier instalación. La corriente admisible depende de calibre, canalización, temperatura y código aplicable.',
			'source' => $sources['southwire_thhn'],
		),
		array(
			'q'      => '¿Qué debo confirmar antes de comprar THHN/THWN-2?',
			'a'      => 'Confirma calibre, color, longitud, material conductor, marca, certificación requerida, uso húmedo/seco, canalización y datos de entrega.',
			'source' => $sources['surtilec_quote'],
		),
	),
	'cables-de-control'           => array(
		array(
			'q'      => '¿Qué es un cable de control?',
			'a'      => 'Es un cable usado para mando, señales discretas, tableros, maquinaria y automatización. Belden describe estos cables para aplicaciones de control industrial y automatización.',
			'source' => $sources['belden_control'],
		),
		array(
			'q'      => '¿Cuándo usar cable de control apantallado?',
			'a'      => 'Cuando hay ruido eléctrico, variadores, motores, recorridos compartidos o señales sensibles. El apantallamiento se confirma según ambiente y ficha técnica.',
			'source' => $sources['belden_control'] . '; ' . $sources['belden_instr'],
		),
		array(
			'q'      => '¿Qué diferencia hay entre control e instrumentación?',
			'a'      => 'Control suele manejar mando y automatización general; instrumentación se orienta a señales de medición o control más sensibles, con requisitos de pares, triadas o pantalla.',
			'source' => $sources['belden_control'] . '; ' . $sources['belden_instr'],
		),
		array(
			'q'      => '¿Qué datos envío para cotizar cable multiconductor?',
			'a'      => 'Calibre, número de conductores, tensión, apantallamiento, cubierta, ambiente, longitud, marca o referencia y ciudad de entrega.',
			'source' => $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿Puedo pedir equivalencia de una referencia?',
			'a'      => 'Sí, pero debe compararse construcción, tensión, cubierta, calibre, número de conductores, pantalla y normas. Si falta información, se solicita antes de cotizar.',
			'source' => $sources['surtilec_upload'],
		),
	),
	'cables-de-instrumentacion'   => array(
		array(
			'q'      => '¿Qué es un cable de instrumentación?',
			'a'      => 'Es un cable para señales de medición, control o automatización donde la integridad de señal es importante. Puede requerir pares, triadas, pantalla individual o global.',
			'source' => $sources['belden_instr'],
		),
		array(
			'q'      => '¿Cuándo usar pantalla individual o global?',
			'a'      => 'Se revisa según tipo de señal, ruido del entorno, recorrido y separación de circuitos. En señales sensibles puede requerirse pantalla individual, global o ambas.',
			'source' => $sources['belden_instr'],
		),
		array(
			'q'      => '¿Qué cable usar para señales analógicas?',
			'a'      => 'Para señales analógicas se revisan pares o triadas, calibre, capacitancia, pantalla, tensión y ambiente. No se debe elegir solo por cantidad de conductores.',
			'source' => $sources['belden_instr'],
		),
		array(
			'q'      => '¿Qué datos necesita Surtilec para cotizar instrumentación?',
			'a'      => 'Tipo de señal, número de pares o triadas, calibre, pantalla, tensión, cubierta, ambiente, longitud, marca/referencia y requisitos de norma.',
			'source' => $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿Instrumentación y control pueden ir en la misma bandeja?',
			'a'      => 'Depende del diseño, separación, ruido eléctrico y normas del proyecto. Se debe validar con el responsable técnico antes de instalar.',
			'source' => $sources['minenergia_retie'],
		),
	),
	'cable-bandeja'               => array(
		array(
			'q'      => '¿Qué significa cable para bandeja o tray cable?',
			'a'      => 'Es un cable diseñado para instalación en bandejas, ductos o conduit según su certificación. Belden describe TC Cable para rutas industriales y de proceso.',
			'source' => $sources['belden_tray'],
		),
		array(
			'q'      => '¿Qué diferencia hay entre TC y TC-ER?',
			'a'      => 'TC identifica tray cable. TC-ER indica aptitud para exposed run cuando la ficha técnica y la instalación lo permiten. No debe asumirse sin validar el producto específico.',
			'source' => $sources['belden_tray'],
		),
		array(
			'q'      => '¿Cuándo conviene cable de bandeja?',
			'a'      => 'En plantas, tableros, procesos industriales y rutas donde el diseño usa bandejas para potencia, control o instrumentación con protección y orden de cableado.',
			'source' => $sources['belden_tray'],
		),
		array(
			'q'      => '¿Qué datos envío para cotizar cable de bandeja?',
			'a'      => 'Calibre, número de conductores, tensión, cubierta, si requiere TC-ER, ambiente, longitud, marca/referencia y norma aplicable.',
			'source' => $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿Todos los cables pueden instalarse en bandeja?',
			'a'      => 'No. Debe revisarse que el cable tenga la clasificación adecuada para bandeja y que la instalación cumpla el diseño y normativa aplicable.',
			'source' => $sources['belden_tray'] . '; ' . $sources['minenergia_retie'],
		),
	),
	'cables-apantallados'         => array(
		array(
			'q'      => '¿Qué significa cable apantallado?',
			'a'      => 'Es un cable con blindaje o pantalla para ayudar a controlar interferencia electromagnética. Puede ser malla, lámina, o combinaciones según aplicación.',
			'source' => $sources['belden_vfd'] . '; ' . $sources['belden_instr'],
		),
		array(
			'q'      => '¿Cuándo necesito apantallamiento?',
			'a'      => 'En variadores, instrumentación, señales sensibles, tableros con ruido eléctrico o recorridos compartidos con potencia. La pantalla exacta se confirma con ficha técnica.',
			'source' => $sources['belden_vfd'] . '; ' . $sources['belden_instr'],
		),
		array(
			'q'      => '¿La pantalla elimina todo el ruido?',
			'a'      => 'No por sí sola. También importan puesta a tierra, terminación, separación de rutas y diseño de instalación.',
			'source' => $sources['rockwell_vfd'],
		),
		array(
			'q'      => '¿Qué datos envío para cotizar cable apantallado?',
			'a'      => 'Aplicación, tipo de señal o carga, calibre, número de conductores/pares, tensión, tipo de pantalla si se conoce, cubierta, longitud y ambiente.',
			'source' => $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿Apantallado es lo mismo que armado?',
			'a'      => 'No. Apantallado se orienta a control de interferencia; armado se orienta a protección mecánica. Algunas familias pueden combinar características, pero se valida por ficha.',
			'source' => $sources['belden_instr'],
		),
	),
	'cable-encauchetado'          => array(
		array(
			'q'      => '¿Cuándo usar cable encauchetado o SOOW?',
			'a'      => 'Se revisa para equipos portátiles, herramientas, extensiones industriales, ambientes con humedad, aceite o uso exigente, siempre según ficha técnica y norma aplicable.',
			'source' => $sources['southwire_soow'],
		),
		array(
			'q'      => '¿SOOW es resistente a aceite y humedad?',
			'a'      => 'Southwire describe SOOW como flexible, 600 V, 90 °C, resistente a calor, humedad, aceite y clima. La selección debe confirmar calibre, conductores y aplicación.',
			'source' => $sources['southwire_soow'],
		),
		array(
			'q'      => '¿Qué datos envío para cotizar cable encauchetado?',
			'a'      => 'Calibre, número de conductores, tensión, longitud, uso del equipo, ambiente, color/cubierta, marca o referencia y ciudad de entrega.',
			'source' => $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿Puedo usar cable encauchetado en minería o industria pesada?',
			'a'      => 'Depende del tipo de cable, certificación, ambiente y exigencia mecánica. En esos casos se revisa ficha técnica y requisitos del proyecto antes de cotizar.',
			'source' => $sources['southwire_soow'] . '; ' . $sources['minenergia_retie'],
		),
		array(
			'q'      => '¿Encauchetado reemplaza un cable de instalación fija?',
			'a'      => 'No debe asumirse. Los cables flexibles y los de instalación fija tienen usos distintos; confirma la aplicación antes de comprar.',
			'source' => $sources['southwire_soow'],
		),
	),
	'cables-especiales'           => array(
		array(
			'q'      => '¿Qué se considera un cable especial?',
			'a'      => 'Es una familia amplia que puede incluir cables apantallados, VFD, instrumentación, bandeja, encauchetados, alta temperatura, red, coaxiales o aplicaciones específicas.',
			'source' => '<a href="/productos/">Surtilec, catálogo</a>',
		),
		array(
			'q'      => '¿Cómo cotizar si no conozco el nombre técnico del cable?',
			'a'      => 'Envía foto, referencia, ficha, aplicación, equipo conectado, tensión, cantidad, longitud y ciudad. Con esos datos se orienta la búsqueda sin inventar especificaciones.',
			'source' => $sources['surtilec_upload'],
		),
		array(
			'q'      => '¿Puedo pedir una equivalencia de marca?',
			'a'      => 'Sí, siempre comparando construcción, material, aislamiento, cubierta, tensión, temperatura, pantalla, normas y aplicación. La equivalencia debe validarse antes de comprar.',
			'source' => $sources['surtilec_quote'],
		),
		array(
			'q'      => '¿Qué datos aceleran una cotización de cables especiales?',
			'a'      => 'Referencia exacta, marca, ficha técnica, cantidad, longitud, uso, norma requerida, ciudad y fecha objetivo. Si hay varias líneas, usa el formulario de listado.',
			'source' => $sources['surtilec_upload'],
		),
		array(
			'q'      => '¿Surtilec puede revisar cables para automatización?',
			'a'      => 'Sí. Envía el listado o aplicación: variadores, PLC, HMI, sensores, instrumentación o tableros. Se revisan referencias y datos técnicos disponibles.',
			'source' => $sources['surtilec_quote'],
		),
	),
);

$hub_content  = surtilec_aeo_p( 'Resolvemos las dudas que normalmente tiene un comprador industrial antes de cotizar cables, conductores y productos de automatización. Cada respuesta incluye una fuente o una referencia de verificación para evitar asumir especificaciones.' );
$hub_content .= surtilec_aeo_faq_blocks( $hub_faq );
$hub_content .= surtilec_aeo_p( '<a href="/cotizar/solicitud/">Solicita tu cotización aquí</a> o <a href="/cotizar/subir-listado/">sube tu listado de materiales</a> si manejas varias referencias.' );

if ( surtilec_aeo_update_post_content( 1453, $hub_content ) ) {
	$updated_posts++;
} else {
	$skipped[] = 'post:1453';
}

$article_cta = surtilec_aeo_p( '<a href="/cotizar/solicitud/">Cotiza con Surtilec</a> o <a href="/cotizar/subir-listado/">sube tu listado de materiales</a> para revisar referencias y cantidades.' );
foreach ( $post_faq as $post_id => $items ) {
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		$skipped[] = 'post:' . $post_id;
		continue;
	}
	$content = surtilec_aeo_replace_faq_section( $post->post_content, surtilec_aeo_faq_blocks( $items ), $article_cta );
	if ( surtilec_aeo_update_post_content( $post_id, $content ) ) {
		$updated_posts++;
	} else {
		$skipped[] = 'post:' . $post_id;
	}
}

foreach ( $term_faq as $slug => $items ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term ) {
		$skipped[] = 'term:' . $slug;
		continue;
	}

	update_term_meta( $term->term_id, 'surtilec_faq', surtilec_aeo_term_faq( $items ) );
	update_term_meta( $term->term_id, '_surtilec_faq', 'field_surtilec_faq' );
	$updated_terms++;
}

wp_cache_flush();

WP_CLI::success( 'AEO source-backed content applied.' );
WP_CLI::log( 'posts_updated: ' . $updated_posts );
WP_CLI::log( 'terms_updated: ' . $updated_terms );
WP_CLI::log( 'skipped: ' . ( $skipped ? implode( ',', $skipped ) : '0' ) );
