<?php
/**
 * Template Name: Surtilec — Servicios
 *
 * Landing y subpáginas SEO para servicios confirmados: comercialización,
 * distribución e importación. El contenido se mantiene en plantilla para
 * asegurar consistencia, schema y CTAs sin depender de bloques duplicados.
 *
 * @package Surtilec
 */

get_header();

/**
 * Datos de servicios confirmados por el cliente.
 *
 * @return array<string,array>
 */
function surtilec_services_catalog() {
	return array(
		'comercializacion' => array(
			'name'        => 'Comercialización',
			'eyebrow'     => 'Comercialización técnica',
			'title'       => 'Comercialización de cables especiales y automatización industrial',
			'lead'        => 'Centralizamos la solicitud, comparación y cotización de referencias técnicas para proyectos eléctricos, industriales y de automatización.',
			'description' => 'Comercialización B2B de cables especiales, conductores eléctricos, cables para variadores VFD, cableado de control y productos de automatización industrial.',
			'url'         => home_url( '/servicios/comercializacion/' ),
			'bullets'     => array(
				'Cotización por referencia, SKU, ficha técnica o listado de materiales.',
				'Apoyo para organizar necesidades por línea, marca, calibre, voltaje o aplicación.',
				'Confirmación de disponibilidad, equivalencias comerciales y cantidades por cotización.',
				'Atención a contratistas, integradores, mantenimiento, compras y proyectos industriales.',
			),
			'process'     => array(
				'Recibimos la referencia o listado de productos requeridos.',
				'Revisamos la información técnica disponible y organizamos la solicitud.',
				'Cotizamos producto, disponibilidad y condiciones comerciales aplicables.',
				'Acompañamos ajustes de referencia, cantidades o alternativas cuando el proyecto lo requiere.',
			),
			'inputs'      => array(
				'Referencia, SKU o descripción del producto.',
				'Cantidad requerida y ciudad de entrega.',
				'Marca preferida o ficha técnica, si existe.',
				'Restricciones de norma, voltaje, calibre, chaqueta o aplicación.',
			),
			'links'       => array(
				array( 'Cables de control', home_url( '/categoria/cables-de-control/' ) ),
				array( 'Cables para variadores VFD', home_url( '/categoria/cables-para-variadores-vfd/' ) ),
				array( 'Automatización industrial', home_url( '/categoria/automatizacion-industrial/' ) ),
			),
			'faq'         => array(
				array( 'q' => '¿Puedo cotizar si solo tengo una referencia o foto del producto?', 'a' => 'Sí. Puedes enviar referencia, foto, ficha técnica o listado. La cotización final confirma disponibilidad, cantidades y condiciones comerciales.' ),
				array( 'q' => '¿La comercialización incluye asesoría técnica?', 'a' => 'Incluye apoyo para organizar la solicitud y revisar datos técnicos disponibles. La selección final depende de la aplicación, norma y especificación del proyecto.' ),
				array( 'q' => '¿Cotizan productos sin precio publicado en la web?', 'a' => 'Sí. Surtilec funciona como catálogo de cotización: los precios y disponibilidad se confirman formalmente por solicitud.' ),
			),
		),
		'distribucion'    => array(
			'name'        => 'Distribución',
			'eyebrow'     => 'Distribución nacional',
			'title'       => 'Distribución de cables y productos industriales en Colombia',
			'lead'        => 'Coordinamos despachos nacionales para productos eléctricos e industriales cotizados, con foco en atención B2B y proyectos técnicos.',
			'description' => 'Distribución nacional de cables especiales, conductores y productos de automatización industrial para empresas, integradores y proyectos en Colombia.',
			'url'         => home_url( '/servicios/distribucion/' ),
			'bullets'     => array(
				'Despachos coordinados desde Bogotá hacia ciudades de Colombia.',
				'Atención a compras por proyecto, reposición, mantenimiento o listados de materiales.',
				'Cotizaciones con cantidades, referencias y condiciones de entrega confirmadas.',
				'Soporte para consolidar solicitudes de varias líneas de producto en una misma gestión.',
			),
			'process'     => array(
				'Recibimos productos, cantidades y ciudad de destino.',
				'Validamos disponibilidad y condiciones de despacho con la cotización.',
				'Coordinamos la entrega según lo aprobado por el cliente.',
				'Mantenemos comunicación sobre la solicitud por los canales de contacto definidos.',
			),
			'inputs'      => array(
				'Ciudad y dirección o zona de entrega.',
				'Listado de referencias y cantidades.',
				'Datos de facturación o empresa para la cotización.',
				'Requisitos de entrega, empaque o documentación si aplican.',
			),
			'links'       => array(
				array( 'Cable THHN / THWN-2', home_url( '/categoria/cable-thhn-thwn/' ) ),
				array( 'Cables de baja tensión', home_url( '/categoria/cables-de-baja-tension/' ) ),
				array( 'Cables de fibra óptica', home_url( '/categoria/cables-de-fibra-optica/' ) ),
			),
			'faq'         => array(
				array( 'q' => '¿Distribuyen fuera de Bogotá?', 'a' => 'Sí. Surtilec coordina despachos a Colombia desde Bogotá. La ciudad, plazo y condiciones se confirman en la cotización.' ),
				array( 'q' => '¿Puedo enviar un listado grande de materiales?', 'a' => 'Sí. Puedes usar el formulario de subir listado o enviarlo por los canales de contacto para organizar la cotización.' ),
				array( 'q' => '¿La distribución garantiza disponibilidad inmediata?', 'a' => 'No se publica disponibilidad automática. Cada solicitud confirma inventario, tiempos y condiciones antes de cerrar la cotización.' ),
			),
		),
		'importacion'    => array(
			'name'        => 'Importación',
			'eyebrow'     => 'Importación bajo solicitud',
			'title'       => 'Importación de referencias técnicas para cables y automatización',
			'lead'        => 'Gestionamos solicitudes de importación para referencias especializadas cuando el proyecto requiere marcas, series o productos específicos no disponibles de forma inmediata.',
			'description' => 'Gestión de importación bajo solicitud para referencias técnicas de cables especiales, cableado industrial y productos de automatización.',
			'url'         => home_url( '/servicios/importacion/' ),
			'bullets'     => array(
				'Búsqueda y cotización de referencias técnicas por marca, serie, SKU o ficha de fabricante.',
				'Gestión orientada a productos especiales, proyectos y reposiciones industriales.',
				'Confirmación formal de tiempos, cantidades mínimas y condiciones en la cotización.',
				'Acompañamiento para organizar documentación técnica antes de solicitar el producto.',
			),
			'process'     => array(
				'Recibimos la referencia exacta, ficha técnica o necesidad del proyecto.',
				'Validamos la información disponible para cotizar la solicitud de importación.',
				'Presentamos condiciones comerciales, tiempos estimados y cantidades aplicables.',
				'Coordinamos el proceso una vez el cliente aprueba la cotización.',
			),
			'inputs'      => array(
				'Marca, referencia o número de parte.',
				'Ficha técnica, foto de placa o documento del fabricante.',
				'Cantidad requerida y fecha objetivo del proyecto.',
				'Ciudad de entrega y datos de contacto de compras/proyecto.',
			),
			'links'       => array(
				array( 'Cables especiales', home_url( '/categoria/cables-especiales/' ) ),
				array( 'Cables de red', home_url( '/categoria/cables-de-red/' ) ),
				array( 'Cables solares', home_url( '/categoria/cables-solares/' ) ),
			),
			'faq'         => array(
				array( 'q' => '¿Qué datos necesito para una importación?', 'a' => 'Lo ideal es enviar marca, referencia exacta, ficha técnica, cantidad y ciudad de entrega. Con esos datos se confirma viabilidad y condiciones.' ),
				array( 'q' => '¿La importación tiene tiempos fijos?', 'a' => 'No. Los tiempos dependen de referencia, proveedor, cantidades y logística. Cada solicitud se confirma por cotización.' ),
				array( 'q' => '¿Pueden importar cualquier producto?', 'a' => 'La gestión se revisa caso a caso según la referencia, disponibilidad del proveedor y condiciones aplicables.' ),
			),
		),
	);
}

/**
 * Emit Service JSON-LD for a service page.
 *
 * @param array $service Service data.
 * @return void
 */
function surtilec_service_schema( $service ) {
	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Service',
		'@id'         => $service['url'] . '#service',
		'name'        => $service['title'],
		'serviceType' => $service['name'],
		'description' => $service['description'],
		'url'         => $service['url'],
		'provider'    => array( '@id' => home_url( '/#organization' ) ),
		'areaServed'  => array( '@type' => 'Country', 'name' => 'Colombia' ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}

$services     = surtilec_services_catalog();
$current_slug = get_post_field( 'post_name', get_queried_object_id() );
$is_detail    = isset( $services[ $current_slug ] );
$current      = $is_detail ? $services[ $current_slug ] : null;

$stats = array(
	array( 'num' => '3', 'label' => 'Servicios confirmados' ),
	array( 'num' => 'B2B', 'label' => 'Empresas, proyectos e industria' ),
	array( 'num' => 'Colombia', 'label' => 'Cobertura de atención' ),
	array( 'num' => '< 1 h', 'label' => 'Respuesta en horario hábil' ),
);
?>

<main id="primary" class="surtilec-page surtilec-services">

	<?php if ( $is_detail ) : ?>
		<section class="su-section su-page-hero">
			<div class="su-inner">
				<?php
				surtilec_breadcrumbs(
					array(
						array( 'label' => 'Servicios', 'url' => home_url( '/servicios/' ) ),
						array( 'label' => $current['name'] ),
					)
				);
				?>
				<p class="su-eyebrow"><?php echo esc_html( $current['eyebrow'] ); ?></p>
				<h1><?php echo esc_html( $current['title'] ); ?></h1>
				<p class="su-page-sub"><?php echo esc_html( $current['lead'] ); ?></p>
				<?php surtilec_stat_bar( $stats ); ?>
			</div>
		</section>

		<section class="su-section su-band-light">
			<div class="su-inner">
				<div class="su-section-head">
					<p class="su-eyebrow">Alcance</p>
					<h2>Qué cubre este servicio</h2>
					<p>El objetivo es convertir una necesidad técnica en una solicitud clara de cotización, con datos suficientes para confirmar producto, cantidades y condiciones.</p>
				</div>
				<div class="su-card-grid cols-2">
					<?php foreach ( $current['bullets'] as $item ) : ?>
						<article class="su-card">
							<h3 class="su-card-title"><?php echo esc_html( $item ); ?></h3>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="su-section su-band-gray">
			<div class="su-inner su-service-columns">
				<div>
					<p class="su-eyebrow">Proceso</p>
					<h2>Cómo trabajamos</h2>
					<ol class="su-service-steps">
						<?php foreach ( $current['process'] as $step ) : ?>
							<li><?php echo esc_html( $step ); ?></li>
						<?php endforeach; ?>
					</ol>
				</div>
				<div>
					<p class="su-eyebrow">Para cotizar</p>
					<h2>Qué información enviar</h2>
					<ul class="su-checklist">
						<?php foreach ( $current['inputs'] as $input ) : ?>
							<li><?php echo esc_html( $input ); ?></li>
						<?php endforeach; ?>
					</ul>
					<a class="su-btn su-btn-primary" href="<?php echo esc_url( home_url( '/cotizar/solicitud/' ) ); ?>">Solicitar cotización</a>
				</div>
			</div>
		</section>

		<section class="su-section su-band-light">
			<div class="su-inner">
				<div class="su-section-head">
					<p class="su-eyebrow">Catálogo relacionado</p>
					<h2>Líneas que suelen entrar en este servicio</h2>
				</div>
				<div class="su-card-grid cols-3">
					<?php foreach ( $current['links'] as $link ) : ?>
						<a class="su-card" href="<?php echo esc_url( $link[1] ); ?>">
							<h3 class="su-card-title"><?php echo esc_html( $link[0] ); ?></h3>
							<p class="su-card-text">Ver productos y referencias disponibles para cotización.</p>
							<span class="su-card-go">Ver línea</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="su-section su-band-gray">
			<div class="su-inner">
				<div class="su-section-head">
					<p class="su-eyebrow">FAQ</p>
					<h2>Preguntas frecuentes</h2>
				</div>
				<div class="su-faq-list">
					<?php foreach ( $current['faq'] as $faq ) : ?>
						<article class="su-faq-item">
							<h3><?php echo esc_html( $faq['q'] ); ?></h3>
							<p><?php echo esc_html( $faq['a'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<?php
		surtilec_service_schema( $current );
		surtilec_faqpage_schema( $current['faq'] );
		surtilec_cta_band(
			array(
				'title'      => '¿Necesitas este servicio?',
				'text'       => 'Envíanos la referencia, listado o necesidad del proyecto y organizamos la cotización.',
				'wa_message' => 'Hola Surtilec, quiero información sobre ' . $current['name'] . '.',
			)
		);
		?>

	<?php else : ?>
		<section class="su-section su-page-hero">
			<div class="su-inner">
				<?php surtilec_breadcrumbs( array( array( 'label' => 'Servicios' ) ) ); ?>
				<p class="su-eyebrow">Servicios</p>
				<h1>Comercialización, distribución e importación para proyectos industriales</h1>
				<p class="su-page-sub">Surtilec atiende solicitudes B2B de cables especiales, conductores eléctricos y automatización industrial con cotización técnica y cobertura en Colombia.</p>
				<?php surtilec_stat_bar( $stats ); ?>
			</div>
		</section>

		<section class="su-section su-band-light">
			<div class="su-inner">
				<div class="su-section-head">
					<p class="su-eyebrow">Servicios confirmados</p>
					<h2>Cómo podemos apoyar tu compra técnica</h2>
					<p>Trabajamos sobre referencias reales, listados de materiales y necesidades de proyecto. Los precios, disponibilidad y condiciones se confirman por cotización formal.</p>
				</div>
				<div class="su-card-grid cols-3">
					<?php foreach ( $services as $service ) : ?>
						<a class="su-card" href="<?php echo esc_url( $service['url'] ); ?>">
							<h3 class="su-card-title"><?php echo esc_html( $service['name'] ); ?></h3>
							<p class="su-card-text"><?php echo esc_html( $service['description'] ); ?></p>
							<span class="su-card-go">Ver servicio</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="su-section su-band-gray">
			<div class="su-inner su-service-columns">
				<div>
					<p class="su-eyebrow">Método</p>
					<h2>De la necesidad técnica a la cotización</h2>
					<ol class="su-service-steps">
						<li>Envías referencia, ficha técnica, listado de materiales o descripción del producto.</li>
						<li>Organizamos la solicitud por línea, marca, cantidad y aplicación.</li>
						<li>Confirmamos condiciones comerciales, disponibilidad y entrega por cotización.</li>
						<li>Acompañamos ajustes o alternativas cuando el proyecto necesita precisión técnica.</li>
					</ol>
				</div>
				<div>
					<p class="su-eyebrow">Ideal para</p>
					<h2>Compras industriales y proyectos</h2>
					<ul class="su-checklist">
						<li>Contratistas eléctricos e integradores.</li>
						<li>Áreas de mantenimiento, compras y proyectos.</li>
						<li>Empresas que requieren referencias específicas o listados por volumen.</li>
						<li>Solicitudes de cables especiales, conductores y automatización industrial.</li>
					</ul>
				</div>
			</div>
		</section>

		<section class="su-section su-band-light">
			<div class="su-inner">
				<div class="su-section-head">
					<p class="su-eyebrow">FAQ</p>
					<h2>Preguntas frecuentes sobre servicios</h2>
				</div>
				<div class="su-faq-list">
					<?php
					$landing_faq = array(
						array( 'q' => '¿Surtilec vende directamente por la web?', 'a' => 'No. El sitio funciona como catálogo de cotización. La compra se gestiona después de confirmar precios, disponibilidad y condiciones.' ),
						array( 'q' => '¿Puedo enviar un archivo con varias referencias?', 'a' => 'Sí. Puedes subir un listado de materiales o enviar la información por contacto para organizar una cotización.' ),
						array( 'q' => '¿Los servicios aplican para toda Colombia?', 'a' => 'La atención y despacho se coordinan para Colombia desde Bogotá, según condiciones confirmadas en la cotización.' ),
					);
					foreach ( $landing_faq as $faq ) :
						?>
						<article class="su-faq-item">
							<h3><?php echo esc_html( $faq['q'] ); ?></h3>
							<p><?php echo esc_html( $faq['a'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<?php
		surtilec_itemlist_schema(
			array_map(
				function ( $service ) {
					return array( 'name' => $service['title'], 'url' => $service['url'] );
				},
				$services
			)
		);
		surtilec_faqpage_schema( $landing_faq );
		surtilec_cta_band(
			array(
				'title' => '¿Tienes un listado o referencia para cotizar?',
				'text'  => 'Envíanos la información y la organizamos según el servicio que aplique.',
			)
		);
		?>
	<?php endif; ?>
</main>

<?php
get_footer();
