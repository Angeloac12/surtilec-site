<?php
/**
 * Surtilec — catalog templates (single product, category, shop archive).
 *
 * All output via WooCommerce hooks (no template-file overrides).
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SURTILEC_WA_NUMBER = '573204499026';

/**
 * Build a wa.me link with a prefilled message.
 *
 * @param string $message Plain text message.
 * @return string
 */
function surtilec_wa_link( $message ) {
	return 'https://wa.me/' . SURTILEC_WA_NUMBER . '?text=' . rawurlencode( $message );
}

/* =============================================================
   PART A — Single product
   ============================================================= */

/**
 * Spec table from the product's GLOBAL attributes (rows with values only).
 * This is the SEO ranking asset.
 */
add_action( 'woocommerce_after_single_product_summary', 'surtilec_spec_table', 5 );
function surtilec_spec_table() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$rows = '';
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->get_visible() || ! $attribute->is_taxonomy() ) {
			continue; // global (pa_*) attributes only.
		}
		$taxonomy = $attribute->get_taxonomy();
		$terms    = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) );
		if ( empty( $terms ) ) {
			continue;
		}
		$rows .= '<tr><th scope="row">' . esc_html( wc_attribute_label( $taxonomy ) ) . '</th>'
			. '<td>' . esc_html( implode( ', ', $terms ) ) . '</td></tr>';
	}

	if ( '' === $rows ) {
		return;
	}

	echo '<section class="surtilec-specs">';
	echo '<h2>' . esc_html__( 'Especificaciones', 'surtilec' ) . '</h2>';
	echo '<table class="surtilec-spec-table"><tbody>' . $rows . '</tbody></table>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p class="surtilec-availability">' . esc_html__( 'Disponible en toda Colombia — despachos desde Bogotá.', 'surtilec' ) . '</p>';
	echo '</section>';
}

/**
 * Inline secondary WhatsApp CTA in the product summary (the floating Joinchat
 * button stays as is). YITH "Añadir a cotización" is the orange primary CTA.
 */
add_action( 'woocommerce_single_product_summary', 'surtilec_product_wa_cta', 35 );
function surtilec_product_wa_cta() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$message = sprintf(
		'Hola Surtilec, quiero cotizar: %1$s — %2$s',
		$product->get_name(),
		get_permalink( $product->get_id() )
	);
	echo '<a class="surtilec-wa-btn" href="' . esc_url( surtilec_wa_link( $message ) ) . '" target="_blank" rel="noopener">'
		. esc_html__( 'Cotizar por WhatsApp', 'surtilec' ) . '</a>';
}

/* =============================================================
   PART B — Category page
   ============================================================= */

/**
 * Register the per-category FAQ field (ACF free — no repeater, so a single
 * structured textarea parsed into Q/A pairs).
 */
add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		acf_add_local_field_group(
			array(
				'key'      => 'group_surtilec_cat',
				'title'    => 'Surtilec — Categoría',
				'fields'   => array(
					array(
						'key'          => 'field_surtilec_faq',
						'label'        => 'Preguntas frecuentes',
						'name'         => 'surtilec_faq',
						'type'         => 'textarea',
						'instructions' => "Un par por bloque, separados por una línea en blanco:\nP: ¿pregunta?\nR: respuesta",
						'rows'         => 10,
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => 'product_cat',
						),
					),
				),
			)
		);
	}
);

/**
 * Parse the FAQ textarea into Q/A pairs.
 *
 * @param string $raw Raw textarea value.
 * @return array<int,array{q:string,a:string}>
 */
function surtilec_parse_faq( $raw ) {
	$pairs  = array();
	$blocks = preg_split( '/\n\s*\n/', trim( (string) $raw ) );
	foreach ( $blocks as $block ) {
		if ( preg_match( '/P:\s*(.+?)\s*R:\s*(.+)/s', $block, $m ) ) {
			$pairs[] = array(
				'q' => trim( $m[1] ),
				'a' => trim( $m[2] ),
			);
		}
	}
	return $pairs;
}

/**
 * Subcategory tiles above the grid when the category has children.
 */
add_action( 'woocommerce_before_shop_loop', 'surtilec_subcategory_tiles', 5 );
function surtilec_subcategory_tiles() {
	if ( ! is_product_category() ) {
		return;
	}
	$term     = get_queried_object();
	$children = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $term->term_id,
			'hide_empty' => false,
		)
	);
	if ( empty( $children ) || is_wp_error( $children ) ) {
		return;
	}
	echo '<ul class="surtilec-tiles surtilec-subcat-tiles">';
	foreach ( $children as $child ) {
		surtilec_render_term_tile( $child );
	}
	echo '</ul>';
}

/**
 * FAQ accordion + FAQPage JSON-LD below the grid.
 */
add_action( 'woocommerce_after_shop_loop', 'surtilec_category_faq', 20 );
function surtilec_category_faq() {
	if ( ! is_product_category() || ! function_exists( 'get_field' ) ) {
		return;
	}
	$term  = get_queried_object();
	$pairs = surtilec_parse_faq( get_field( 'surtilec_faq', $term ) );
	if ( empty( $pairs ) ) {
		return;
	}

	echo '<section class="surtilec-faq"><h2>' . esc_html__( 'Preguntas frecuentes', 'surtilec' ) . '</h2>';
	foreach ( $pairs as $pair ) {
		echo '<details><summary>' . esc_html( $pair['q'] ) . '</summary>'
			. '<div class="surtilec-faq-a">' . wp_kses_post( wpautop( $pair['a'] ) ) . '</div></details>';
	}
	echo '</section>';

	$entities = array();
	foreach ( $pairs as $pair ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $pair['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $pair['a'],
			),
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
 * CTA block below the grid on category pages.
 */
add_action( 'woocommerce_after_shop_loop', 'surtilec_category_cta', 30 );
function surtilec_category_cta() {
	if ( ! is_product_category() ) {
		return;
	}
	surtilec_render_cta_block();
}

/* =============================================================
   PART C — Catalog archive (/productos/)
   ============================================================= */

/**
 * Pillar category tiles above the product grid on the shop page.
 */
add_action( 'woocommerce_before_shop_loop', 'surtilec_pillar_tiles', 5 );
function surtilec_pillar_tiles() {
	if ( ! is_shop() ) {
		return;
	}
	$parents = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ), // Uncategorized.
		)
	);
	if ( empty( $parents ) || is_wp_error( $parents ) ) {
		return;
	}
	echo '<ul class="surtilec-tiles surtilec-pillar-tiles">';
	foreach ( $parents as $parent ) {
		surtilec_render_term_tile( $parent );
	}
	echo '</ul>';
}

/* =============================================================
   Shared render helpers
   ============================================================= */

/**
 * A category tile: thumbnail + name + product count.
 *
 * @param WP_Term $term Product category term.
 */
function surtilec_render_term_tile( $term ) {
	$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
	$image    = $thumb_id ? wp_get_attachment_image( $thumb_id, 'woocommerce_thumbnail' ) : '';

	echo '<li class="surtilec-tile"><a href="' . esc_url( get_term_link( $term ) ) . '">';
	if ( $image ) {
		echo '<span class="surtilec-tile-img">' . $image . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '<span class="surtilec-tile-name">' . esc_html( $term->name ) . '</span>';
	echo '<span class="surtilec-tile-count">' . sprintf(
		/* translators: %d: product count */
		esc_html( _n( '%d producto', '%d productos', (int) $term->count, 'surtilec' ) ),
		(int) $term->count
	) . '</span>';
	echo '</a></li>';
}

/**
 * The "no encuentras lo que buscas" CTA block.
 */
function surtilec_render_cta_block() {
	echo '<section class="surtilec-cta-block">';
	echo '<p class="surtilec-cta-text">' . esc_html__( '¿No encuentras lo que buscas?', 'surtilec' ) . '</p>';
	echo '<div class="surtilec-cta-buttons">';
	echo '<a class="surtilec-cta-primary" href="' . esc_url( home_url( '/cotizar/solicitud/' ) ) . '">'
		. esc_html__( 'Cotiza aquí', 'surtilec' ) . '</a>';
	echo '<a class="surtilec-wa-btn" href="' . esc_url( surtilec_wa_link( 'Hola Surtilec, necesito ayuda para cotizar.' ) ) . '" target="_blank" rel="noopener">'
		. esc_html__( 'WhatsApp', 'surtilec' ) . '</a>';
	echo '</div></section>';
}
