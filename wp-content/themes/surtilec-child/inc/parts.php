<?php
/**
 * Surtilec — partes de plantilla reutilizables (cross-page).
 *
 * Componentes compartidos por la portada y las páginas internas
 * (Nosotros, Industrias, Servicios): migas de pan + JSON-LD, barra de
 * estadísticas semántica y banda CTA full-bleed.
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marca las plantillas de página full-bleed de Surtilec con la clase
 * body `su-fullbleed`, para liberar el contenedor 1200 de GeneratePress
 * (igual que body.home en la portada) y permitir bandas a todo el ancho.
 */
add_filter(
	'body_class',
	function ( $classes ) {
		$fullbleed_templates = array(
			'page-nosotros.php',
			'page-industrias.php',
			'template-industria.php',
			'page-servicios.php',
		);
		if ( is_page_template( $fullbleed_templates ) ) {
			$classes[] = 'su-fullbleed';
		}
		return $classes;
	}
);

/**
 * Migas de pan accesibles + BreadcrumbList JSON-LD.
 *
 * "Inicio" se antepone siempre. El último elemento es la página actual
 * (sin enlace). El JSON-LD se puede desactivar en contextos donde otro
 * componente ya lo emite (p. ej. categorías/producto en surtilec-schema.php).
 *
 * @param array $trail Lista de pasos (sin Inicio): array{label:string,url?:string}.
 *                     El último suele ir sin 'url' (página actual).
 * @param array $args  Opciones: ['schema' => bool] (default true).
 * @return void
 */
function surtilec_breadcrumbs( $trail = array(), $args = array() ) {
	$args  = wp_parse_args( $args, array( 'schema' => true ) );
	$items = array_merge(
		array( array( 'label' => 'Inicio', 'url' => home_url( '/' ) ) ),
		is_array( $trail ) ? $trail : array()
	);
	$total = count( $items );

	echo '<nav class="su-breadcrumb" aria-label="Migas de pan"><ol>';
	foreach ( $items as $i => $it ) {
		$is_last = ( $i === $total - 1 );
		$label   = isset( $it['label'] ) ? $it['label'] : '';
		echo '<li' . ( $is_last ? ' aria-current="page"' : '' ) . '>';
		if ( ! $is_last && ! empty( $it['url'] ) ) {
			echo '<a href="' . esc_url( $it['url'] ) . '">' . esc_html( $label ) . '</a>';
		} else {
			echo '<span>' . esc_html( $label ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';

	if ( ! $args['schema'] ) {
		return;
	}

	$list = array();
	foreach ( $items as $i => $it ) {
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => isset( $it['label'] ) ? $it['label'] : '',
		);
		if ( ! empty( $it['url'] ) ) {
			$entry['item'] = esc_url_raw( $it['url'] );
		}
		$list[] = $entry;
	}
	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}

/**
 * Barra de estadísticas (<dl>/<dt>/<dd> — mejor parseo semántico).
 *
 * No inventar cifras: el llamador pasa valores reales o claims ya
 * establecidos (respuesta < 1 h, cobertura nacional, etc.).
 *
 * @param array $stats Lista de array{num:string,label:string}.
 * @return void
 */
function surtilec_stat_bar( $stats ) {
	if ( empty( $stats ) || ! is_array( $stats ) ) {
		return;
	}
	echo '<dl class="su-statbar">';
	foreach ( $stats as $s ) {
		if ( empty( $s['num'] ) ) {
			continue;
		}
		echo '<div class="su-stat">';
		echo '<dt>' . esc_html( $s['num'] ) . '</dt>';
		echo '<dd>' . esc_html( isset( $s['label'] ) ? $s['label'] : '' ) . '</dd>';
		echo '</div>';
	}
	echo '</dl>';
}

/**
 * Banda CTA full-bleed reutilizable (páginas internas).
 *
 * Variante por defecto = naranja (igual que la banda final de la portada).
 * El bloque CTA dentro del contenido del catálogo (surtilec_render_cta_block)
 * es un componente distinto y se mantiene aparte.
 *
 * @param array $args Opciones de copy/enlaces. Ver wp_parse_args abajo.
 * @return void
 */
function surtilec_cta_band( $args = array() ) {
	$a = wp_parse_args(
		$args,
		array(
			'variant'       => 'accent', // accent | dark
			'title'         => '¿Listo para cotizar?',
			'text'          => 'Envíanos tu solicitud y recibe precios y disponibilidad en menos de 1 hora hábil.',
			'primary_label' => 'Cotizar ahora',
			'primary_url'   => home_url( '/cotizar/solicitud/' ),
			'wa_label'      => 'WhatsApp',
			'wa_message'    => 'Hola Surtilec, quiero una cotización.',
		)
	);

	$wa = function_exists( 'surtilec_wa_link' )
		? surtilec_wa_link( $a['wa_message'] )
		: 'https://wa.me/573204499026';

	$band_class = ( 'dark' === $a['variant'] ) ? 'su-band-navy' : 'su-band-accent';
	$btn_class  = ( 'dark' === $a['variant'] ) ? 'su-btn-primary' : 'su-btn-dark';
	$ghost      = ( 'dark' === $a['variant'] ) ? 'su-btn-ghost' : 'su-btn-ghost-dark';
	?>
	<section class="su-section <?php echo esc_attr( $band_class ); ?> su-finalcta">
		<div class="su-inner">
			<h2 class="su-finalcta-title"><?php echo esc_html( $a['title'] ); ?></h2>
			<p class="su-finalcta-text"><?php echo esc_html( $a['text'] ); ?></p>
			<div class="su-finalcta-buttons">
				<a class="su-btn <?php echo esc_attr( $btn_class ); ?>" href="<?php echo esc_url( $a['primary_url'] ); ?>"><?php echo esc_html( $a['primary_label'] ); ?></a>
				<a class="su-btn <?php echo esc_attr( $ghost ); ?>" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $a['wa_label'] ); ?></a>
			</div>
		</div>
	</section>
	<?php
}
