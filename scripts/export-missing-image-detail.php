<?php
/**
 * Export detail for published products that still have no featured image.
 *
 * Companion to scripts/export-live-image-status.php, which only reports the
 * has/has-not flag. This one carries the fields the family-image mapper needs:
 * category, brand and the registered attribute keys.
 *
 * Usage:
 *   ssh ... "cd <wp> && wp eval-file -" \
 *     < scripts/export-missing-image-detail.php \
 *     > data/product-image-gap.csv
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$query = new WP_Query(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

fputcsv( STDOUT, array( 'id', 'sku', 'titulo', 'categorias', 'marca', 'atributos' ) );

foreach ( $query->posts as $product_id ) {
	$product_id = (int) $product_id;
	if ( (int) get_post_thumbnail_id( $product_id ) > 0 ) {
		continue;
	}

	$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
	$categories = is_wp_error( $categories ) ? array() : $categories;

	// The brand lives in a different taxonomy depending on how the row was
	// imported, so take the first taxonomy that actually resolves a term.
	$brand = '';
	foreach ( array( 'pa_marca', 'product_brand', 'pa_brand' ) as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		$terms = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$brand = implode( '|', $terms );
			break;
		}
	}

	$attribute_keys = array();
	$product        = wc_get_product( $product_id );
	if ( $product ) {
		foreach ( $product->get_attributes() as $key => $attribute ) {
			$attribute_keys[] = $key;
		}
	}

	fputcsv(
		STDOUT,
		array(
			$product_id,
			trim( (string) get_post_meta( $product_id, '_sku', true ) ),
			html_entity_decode( get_the_title( $product_id ), ENT_QUOTES, 'UTF-8' ),
			implode( '|', $categories ),
			$brand,
			implode( '|', $attribute_keys ),
		)
	);
}
