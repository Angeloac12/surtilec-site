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
		if ( is_page_template( $fullbleed_templates ) || is_home() || is_singular( 'post' ) || is_category() || is_tag() ) {
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

/**
 * Iconos SVG en línea reutilizables (24x24, stroke=currentColor).
 * Disponible en todas las plantillas (a diferencia de surtilec_home_icon,
 * que sólo se carga en la portada).
 *
 * @param string $name Clave del icono.
 * @return string
 */
function surtilec_icon( $name ) {
	$p = array(
		'construccion' => '<path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-3"/><path d="M9 9v0M9 12v0M9 15v0"/>',
		'manufactura'  => '<path d="M2 20h20"/><path d="M4 20V9l5 4V9l5 4V6l6 4v10"/>',
		'oil-gas'      => '<path d="M12 2s6 6 6 11a6 6 0 0 1-12 0c0-5 6-11 6-11z"/>',
		'mineria'      => '<path d="M14 3l7 7"/><path d="M3 21l9-9"/><path d="M9 7l8 8"/><path d="M5 11l2-4 4-2"/>',
		'agroindustria'=> '<path d="M12 22V8"/><path d="M12 8c0-3 2-5 5-5 0 3-2 5-5 5z"/><path d="M12 12c0-3-2-5-5-5 0 3 2 5 5 5z"/>',
		'oem'          => '<rect x="7" y="7" width="10" height="10" rx="1"/><path d="M10 2v3M14 2v3M10 19v3M14 19v3M2 10h3M2 14h3M19 10h3M19 14h3"/>',
	);
	$d = isset( $p[ $name ] ) ? $p[ $name ] : '';
	return '<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/**
 * Extrae pares P/R de un artículo: busca el H2 "Preguntas frecuentes" y toma
 * cada H3 (pregunta) + el primer párrafo siguiente (respuesta), hasta el
 * próximo H2. Pensado para nuestro contenido controlado.
 *
 * @param string $html Contenido renderizado (the_content).
 * @return array<int,array{q:string,a:string}>
 */
function surtilec_faq_pairs_from_html( $html ) {
	if ( '' === trim( (string) $html ) || ! class_exists( 'DOMDocument' ) ) {
		return array();
	}
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8"?><div id="su-root">' . $html . '</div>' );
	libxml_clear_errors();
	$root = $dom->getElementById( 'su-root' );
	if ( ! $root ) {
		return array();
	}
	$pairs   = array();
	$in_faq  = false;
	$pending = null;
	foreach ( $root->childNodes as $node ) {
		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			continue;
		}
		$tag = strtolower( $node->nodeName );
		if ( 'h2' === $tag ) {
			$in_faq  = ( false !== mb_stripos( $node->textContent, 'preguntas frecuentes' ) );
			$pending = null;
			continue;
		}
		if ( ! $in_faq ) {
			continue;
		}
		if ( 'h3' === $tag ) {
			$pending = trim( $node->textContent );
		} elseif ( 'p' === $tag && null !== $pending && '' !== trim( $node->textContent ) ) {
			$pairs[] = array( 'q' => $pending, 'a' => trim( $node->textContent ) );
			$pending = null;
		}
	}
	return $pairs;
}

/**
 * Emite FAQPage JSON-LD a partir de pares P/R (si hay).
 *
 * @param array $pairs array{q,a}.
 * @return void
 */
function surtilec_faqpage_schema( $pairs ) {
	if ( empty( $pairs ) ) {
		return;
	}
	$entities = array();
	foreach ( $pairs as $p ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $p['q'],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $p['a'] ),
		);
	}
	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}

/**
 * Tiempo estimado de lectura en minutos (~200 palabras/min).
 *
 * @param int|WP_Post|null $post Post o ID (default: actual).
 * @return int Minutos (mínimo 1).
 */
function surtilec_read_time( $post = null ) {
	$post  = get_post( $post );
	$words = $post ? str_word_count( wp_strip_all_tags( $post->post_content ) ) : 0;
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Emite un ItemList JSON-LD a partir de pares {name,url}.
 *
 * @param array $items Lista de array{name:string,url:string}.
 * @return void
 */
function surtilec_itemlist_schema( $items ) {
	if ( empty( $items ) ) {
		return;
	}
	$els = array();
	$i   = 1;
	foreach ( $items as $it ) {
		if ( empty( $it['name'] ) ) {
			continue;
		}
		$els[] = array(
			'@type'    => 'ListItem',
			'position' => $i++,
			'name'     => $it['name'],
			'url'      => isset( $it['url'] ) ? esc_url_raw( $it['url'] ) : '',
		);
	}
	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'ItemList',
		'itemListElement' => $els,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
}
