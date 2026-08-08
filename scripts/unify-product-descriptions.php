<?php
/**
 * Apply one SEO/AEO description structure to all catalog products.
 *
 * The script uses only the current WooCommerce name, SKU, categories,
 * attributes and already stored source metadata. It preserves product status,
 * prices, images and short descriptions. Existing long descriptions are
 * replaced so the catalog has one coherent structure.
 *
 * Usage:
 *   wp eval-file unify-product-descriptions.php dry
 *   wp eval-file unify-product-descriptions.php live [offset] [limit]
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$mode = isset( $args[0] ) ? $args[0] : 'dry';
$live = 'live' === $mode;
$offset = isset( $args[1] ) ? max( 0, (int) $args[1] ) : 0;
$limit  = isset( $args[2] ) ? max( 1, (int) $args[2] ) : -1;
$template_version = '2026-08-03-v1';
$posts = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => $limit,
		'offset'         => $offset,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

$stats = array(
	'total'       => count( $posts ),
	'publish'     => 0,
	'draft'       => 0,
	'with_specs'  => 0,
	'with_source' => 0,
	'updated'     => 0,
	'blocked'     => 0,
	'skipped'     => 0,
	'unchanged'   => 0,
);

foreach ( $posts as $post ) {
	$product = wc_get_product( $post->ID );
	if ( ! $product ) {
		continue;
	}
	if ( 'publish' === $post->post_status ) {
		++$stats['publish'];
	} elseif ( 'draft' === $post->post_status ) {
		++$stats['draft'];
	}

	$name = trim( wp_strip_all_tags( $product->get_name() ) );
	$sku  = trim( (string) $product->get_sku() );
	if ( '' === $name || '' === $sku ) {
		if ( 'publish' === $post->post_status ) {
			++$stats['blocked'];
			WP_CLI::warning( "Producto {$post->ID}: producto publicado con nombre o SKU vacío." );
		} else {
			++$stats['skipped'];
			WP_CLI::warning( "Producto {$post->ID}: nombre o SKU vacío; se conserva sin cambios." );
		}
		continue;
	}
	if ( preg_match( '/cables\s*colombia|cablescolombia(?:\.com)?|multimedia02\.s3/i', $name . ' ' . $product->get_short_description() ) ) {
		++$stats['blocked'];
		WP_CLI::warning( "Producto {$sku}: marcador de fuente no permitido; se omitió." );
		continue;
	}

	$specs = surtilec_unified_product_specs( $product );
	if ( ! empty( $specs ) ) {
		++$stats['with_specs'];
	}

	$source_url    = trim( (string) get_post_meta( $post->ID, '_surtilec_datasheet_url', true ) );
	$source_status = trim( (string) get_post_meta( $post->ID, '_surtilec_source_status', true ) );
	$source_host   = $source_url ? strtolower( (string) wp_parse_url( $source_url, PHP_URL_HOST ) ) : '';
	$source_ok     = preg_match( '#^https?://[^[:space:]]+$#i', $source_url )
		&& $source_host
		&& 'cablescolombia.com' !== $source_host
		&& ! preg_match( '/\.cablescolombia\.com$/i', $source_host );
	if ( $source_ok ) {
		++$stats['with_source'];
	}

	$category = surtilec_unified_product_category( $post->ID );
	$application = surtilec_unified_product_application( $product, $category );
	$content = surtilec_unified_product_content( $product, $category, $application, $specs, $source_ok ? $source_url : '', $source_status );

	if ( $live ) {
		if ( get_post_meta( $post->ID, '_surtilec_description_template_version', true ) === $template_version && wp_kses_post( $content ) === (string) $post->post_content ) {
			++$stats['unchanged'];
			continue;
		}
		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => wp_kses_post( $content ),
			)
		);
		update_post_meta( $post->ID, '_surtilec_description_template_version', $template_version );
		++$stats['updated'];
	}
}

foreach ( $stats as $key => $value ) {
	WP_CLI::log( "$key: $value" );
}
if ( $stats['blocked'] > 0 ) {
	WP_CLI::error( 'La unificación fue bloqueada para productos con datos críticos o marcadores de fuente.' );
}
if ( ! $live ) {
	WP_CLI::success( 'Dry-run OK. Se aplicaría la plantilla SEO/AEO sin cambiar estados, precios, imágenes ni descripciones cortas.' );
	return;
}
wp_cache_flush();
WP_CLI::success( 'Plantilla SEO/AEO unificada en el catálogo.' );

/**
 * Build visible product specification rows from product attributes and batch
 * metadata. Values are not guessed; empty fields are skipped.
 *
 * @param WC_Product $product Product object.
 * @return array<string,string>
 */
function surtilec_unified_product_specs( $product ) {
	$specs = array();
	$attributes = $product->get_attributes();
	foreach ( $attributes as $attribute ) {
		$label = wc_attribute_label( $attribute->get_name() );
		$values = array();
		if ( $attribute->is_taxonomy() ) {
			$values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
		} else {
			$values = $attribute->get_options();
		}
		$values = array_values( array_filter( array_map( 'trim', array_map( 'wp_strip_all_tags', (array) $values ) ) ) );
		if ( '' !== trim( (string) $label ) && ! empty( $values ) ) {
			$specs[ sanitize_key( $label ) ] = array( 'label' => $label, 'value' => implode( ', ', $values ) );
		}
	}

	$unit = trim( (string) get_post_meta( $product->get_id(), '_surtilec_unidad_venta', true ) );
	$temp = trim( (string) get_post_meta( $product->get_id(), '_surtilec_temperatura_maxima', true ) );
	if ( '' !== $unit ) {
		$specs['unidad-venta'] = array( 'label' => 'Unidad de venta', 'value' => $unit );
	}
	if ( '' !== $temp ) {
		$specs['temperatura-maxima'] = array( 'label' => 'Temperatura máxima', 'value' => $temp );
	}
	return $specs;
}

/**
 * Get the first useful catalog category.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function surtilec_unified_product_category( $product_id ) {
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return 'aplicaciones eléctricas e industriales';
	}
	$names = array_values( array_filter( array_map( function ( $term ) {
		return trim( wp_strip_all_tags( $term->name ) );
	}, $terms ) ) );
	return $names ? implode( ', ', array_slice( $names, 0, 2 ) ) : 'aplicaciones eléctricas e industriales';
}

/**
 * Select an existing application attribute or a restrained category-based
 * description. The fallback does not claim a specific installation rating.
 *
 * @param WC_Product $product Product object.
 * @param string     $category Product category.
 * @return string
 */
function surtilec_unified_product_application( $product, $category ) {
	$terms = wc_get_product_terms( $product->get_id(), 'pa_aplicacion', array( 'fields' => 'names' ) );
	$terms = array_values( array_filter( array_map( 'trim', (array) $terms ) ) );
	if ( $terms ) {
		return implode( '; ', array_slice( $terms, 0, 3 ) );
	}
	return 'soluciones de ' . mb_strtolower( $category );
}

/**
 * Render the single product description structure used by published and
 * staging products.
 *
 * @param WC_Product $product Product object.
 * @param string     $category Category text.
 * @param string     $application Application text.
 * @param array      $specs Specification map.
 * @param string     $source_url Official source URL, if available.
 * @param string     $source_status Source status.
 * @return string
 */
function surtilec_unified_product_content( $product, $category, $application, $specs, $source_url, $source_status ) {
	$name = trim( wp_strip_all_tags( $product->get_name() ) );
	$sku  = trim( (string) $product->get_sku() );
	$short = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $product->get_short_description() ) ) );
	$short = $short ? $short : "$name para $category.";
	$spec_rows = array();
	foreach ( $specs as $spec ) {
		$spec_rows[] = '<li><strong>' . esc_html( $spec['label'] ) . ':</strong> ' . esc_html( $spec['value'] ) . '</li>';
	}
	if ( empty( $spec_rows ) ) {
		$spec_rows[] = '<li>La ficha comercial se confirma según la referencia y disponibilidad del proveedor.</li>';
	}
	$source_note = '';
	if ( $source_url ) {
		$is_family = 'generica_verificada' === $source_status || 'familia_verificada_no_referencia_exacta' === get_post_meta( $product->get_id(), '_surtilec_generic_research_status', true );
		$label = $is_family ? 'Fuente técnica de familia' : 'Ficha técnica asociada';
		$link  = $is_family ? 'Consultar ficha de familia' : 'Consultar ficha técnica';
		$source_note = '<p class="surtilec-source-note"><strong>' . esc_html( $label ) . ':</strong> <a href="' . esc_url( $source_url ) . '" target="_blank" rel="noopener">' . esc_html( $link ) . '</a>.</p>';
	}

	return implode(
		'',
		array(
			'<p><strong>', esc_html( $name ), '</strong> corresponde a ', esc_html( $short ), '</p>',
			'<h2>¿Qué producto es?</h2><p>Es una referencia de la categoría <strong>' . esc_html( $category ) . '</strong>, identificada para cotización mediante el SKU <strong>' . esc_html( $sku ) . '</strong>.</p>',
			'<h2>Especificaciones identificadas</h2><ul>', implode( '', $spec_rows ), '</ul>',
			'<h2>¿Para qué sirve?</h2><p>' . esc_html( $application ) . '. La selección final depende del sistema, la instalación, la compatibilidad y la ficha técnica de la referencia disponible.</p>',
			'<h2>¿Qué debo confirmar antes de comprar?</h2><p>Confirma con Surtilec la marca, referencia de fabricante, norma aplicable, presentación, disponibilidad y compatibilidad con tu instalación. No uses esta descripción como sustituto del diseño eléctrico o de la ficha técnica.</p>',
			'<h2>¿Cómo solicitar la cotización?</h2><p>Envía el SKU <strong>' . esc_html( $sku ) . '</strong>, la cantidad, la unidad requerida y el uso previsto. Te ayudaremos a validar la referencia correcta antes del despacho.</p>',
			$source_note,
		)
	);
}
