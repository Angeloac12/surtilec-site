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

/**
 * Tile-cache version. Bumped on product/category changes so the version-keyed
 * tile transients invalidate at once (counts stay correct).
 */
function surtilec_tiles_ver() {
	return (int) get_option( 'surtilec_tiles_ver', 1 );
}
function surtilec_bump_tiles_ver() {
	update_option( 'surtilec_tiles_ver', surtilec_tiles_ver() + 1 );
}
add_action( 'save_post_product', 'surtilec_bump_tiles_ver' );
add_action(
	'deleted_post',
	function ( $post_id ) {
		if ( 'product' === get_post_type( $post_id ) ) {
			surtilec_bump_tiles_ver();
		}
	}
);
foreach ( array( 'created_product_cat', 'edited_product_cat', 'delete_product_cat' ) as $surtilec_cat_hook ) {
	add_action( $surtilec_cat_hook, 'surtilec_bump_tiles_ver' );
}

/**
 * get_terms wrapped in a version-keyed transient (12h).
 *
 * @param string $key_suffix Cache key suffix.
 * @param array  $args       get_terms args.
 * @return array
 */
function surtilec_cached_terms( $key_suffix, $args ) {
	$key   = 'surtilec_tiles_' . $key_suffix . '_' . surtilec_tiles_ver();
	$terms = get_transient( $key );
	if ( false === $terms ) {
		$terms = get_terms( $args );
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		set_transient( $key, $terms, 12 * HOUR_IN_SECONDS );
	}
	return $terms;
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

/**
 * Key-spec chips under the product title (instant value, modern).
 * Prio 6 = right after the title (prio 5), before the price (prio 10).
 */
add_action( 'woocommerce_single_product_summary', 'surtilec_product_chips', 6 );
function surtilec_product_chips() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	// Short, high-signal attributes only (norma is long → goes in the spec table).
	$keys  = array( 'pa_marca', 'pa_calibre-awg', 'pa_numero-conductores', 'pa_voltaje', 'pa_apantallado' );
	$chips = '';
	foreach ( $keys as $tax ) {
		$terms = wc_get_product_terms( $product->get_id(), $tax, array( 'fields' => 'names' ) );
		if ( empty( $terms ) ) {
			continue;
		}
		$chips .= '<span class="su-pchip"><span class="su-pchip-k">' . esc_html( wc_attribute_label( $tax ) ) . '</span> '
			. esc_html( implode( ', ', $terms ) ) . '</span>';
	}
	if ( '' !== $chips ) {
		echo '<div class="su-pchips">' . $chips . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Trust microcopy under the CTAs (value + reassurance). Prio 36 = after the
 * inline WhatsApp CTA (prio 35).
 */
add_action( 'woocommerce_single_product_summary', 'surtilec_product_trust', 36 );
function surtilec_product_trust() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	echo '<ul class="su-ptrust">'
		. '<li>' . esc_html__( 'Despacho a toda Colombia desde Bogotá', 'surtilec' ) . '</li>'
		. '<li>' . esc_html__( 'Cotización en menos de 1 hora hábil', 'surtilec' ) . '</li>'
		. '<li>' . esc_html__( 'Asesoría técnica para elegir el producto correcto', 'surtilec' ) . '</li>'
		. '</ul>';
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
 * Category intro (native term description). Rendered on woocommerce_archive_description
 * so it lands BELOW the category H1 (inside the products-header, after the title) yet
 * still shows on zero-product categories (the header is not loop-guarded). We remove
 * WooCommerce's default description output to avoid double rendering.
 */
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
add_action( 'woocommerce_archive_description', 'surtilec_category_intro', 8 );
function surtilec_category_intro() {
	if ( ! is_product_category() ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || '' === trim( (string) $term->description ) ) {
		return;
	}
	echo '<div class="surtilec-cat-intro term-description">' . wc_format_content( $term->description ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Subcategory tiles when the category has children. On woocommerce_archive_description
 * (below the H1, after the intro) so the title comes first and empty categories still
 * show them.
 */
add_action( 'woocommerce_archive_description', 'surtilec_subcategory_tiles', 12 );
function surtilec_subcategory_tiles() {
	if ( ! is_product_category() ) {
		return;
	}
	$term     = get_queried_object();
	$children = surtilec_cached_terms(
		'subcat_' . $term->term_id,
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $term->term_id,
			'hide_empty' => false,
		)
	);
	if ( empty( $children ) ) {
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
 * Priority 5 (before the wrapper-end at 10) so it renders INSIDE the content
 * wrapper at full width, not in the sidebar slot. Shows on empty categories too.
 */
add_action( 'woocommerce_after_main_content', 'surtilec_category_faq', 5 );
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
 * Priority 6 (before the wrapper-end at 10, after the FAQ at 5) so it renders
 * full-width inside the content wrapper, not in the sidebar slot.
 */
add_action( 'woocommerce_after_main_content', 'surtilec_category_cta', 6 );
function surtilec_category_cta() {
	if ( ! is_product_category() ) {
		return;
	}
	surtilec_render_cta_block();
}

/**
 * Replace WooCommerce's bare "no products found" message on category archives:
 * - category WITH children -> nothing (the subcategory tiles are the content).
 * - leaf category -> a friendly WhatsApp prompt.
 */
remove_action( 'woocommerce_no_products_found', 'wc_no_products_found' );
add_action( 'woocommerce_no_products_found', 'surtilec_no_products_found' );
function surtilec_no_products_found() {
	if ( ! is_product_category() ) {
		wc_print_notice( esc_html__( 'No se encontraron productos.', 'surtilec' ), 'notice' );
		return;
	}
	$term     = get_queried_object();
	$children = ( $term instanceof WP_Term ) ? get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $term->term_id,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	) : array();

	if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
		return; // tiles already shown above; no message needed.
	}

	echo '<div class="surtilec-empty-leaf">';
	echo '<p>' . esc_html__( 'Aún no hay productos publicados en esta categoría. Escríbenos por WhatsApp y te cotizamos lo que necesites.', 'surtilec' ) . '</p>';
	echo '<a class="surtilec-wa-btn" href="' . esc_url( surtilec_wa_link( 'Hola Surtilec, busco productos de esta categoría para cotizar.' ) ) . '" target="_blank" rel="noopener">'
		. esc_html__( 'Cotizar por WhatsApp', 'surtilec' ) . '</a>';
	echo '</div>';
}

/* =============================================================
   PART C — Catalog archive (/productos/)
   ============================================================= */

/**
 * Pillar category tiles on the shop page. On woocommerce_archive_description
 * (below the H1) so the page title comes first, independent of the loop.
 */
add_action( 'woocommerce_archive_description', 'surtilec_pillar_tiles', 12 );
function surtilec_pillar_tiles() {
	if ( ! is_shop() ) {
		return;
	}
	$parents = surtilec_cached_terms(
		'pillars',
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
			'exclude'    => array( (int) get_option( 'default_product_cat' ) ), // Uncategorized.
		)
	);
	if ( empty( $parents ) ) {
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
