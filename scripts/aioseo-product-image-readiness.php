<?php
/**
 * Report which published products still lack a usable featured image.
 *
 * This script used to APPLY a noindex to those products. It no longer writes
 * anything: hiding a product because it has no photo cost the catalog 1,068 of
 * 1,385 published pages, and an SKU + spec table + Q&A copy is worth indexing
 * without one. See scripts/aioseo-release-image-gated-noindex.php for the
 * change that released them. Keep this as the readiness report so image work
 * can still be prioritised.
 *
 * Usage:
 *   scripts/wp.sh eval-file - < scripts/aioseo-product-image-readiness.php
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$product_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	)
);

$stats = array(
	'published_products'                    => count( $product_ids ),
	'products_with_featured_image'          => 0,
	'products_without_featured_image'       => 0,
	'image_ready_missing_sku'               => 0,
	'image_ready_missing_short_description' => 0,
	'image_ready_missing_alt'               => 0,
);

$no_image_sample = array();
$ready_sample    = array();

foreach ( $product_ids as $product_id ) {
	$product_id   = (int) $product_id;
	$post         = get_post( $product_id );
	$thumbnail_id = (int) get_post_thumbnail_id( $product_id );
	$has_image    = surtilec_readiness_product_has_real_featured_image( $product_id );

	if ( $has_image ) {
		$stats['products_with_featured_image']++;

		if ( '' === trim( (string) get_post_meta( $product_id, '_sku', true ) ) ) {
			$stats['image_ready_missing_sku']++;
		}

		if ( ! $post || '' === trim( (string) $post->post_excerpt ) ) {
			$stats['image_ready_missing_short_description']++;
		}

		if ( '' === trim( (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) ) ) {
			$stats['image_ready_missing_alt']++;
		}

		if ( count( $ready_sample ) < 5 ) {
			$ready_sample[] = surtilec_readiness_product_label( $product_id );
		}

		continue;
	}

	$stats['products_without_featured_image']++;

	if ( count( $no_image_sample ) < 20 ) {
		$no_image_sample[] = surtilec_readiness_product_label( $product_id );
	}
}

WP_CLI::success( 'Reporte de imágenes de producto (solo lectura).' );

foreach ( $stats as $key => $value ) {
	WP_CLI::log( "$key: $value" );
}

WP_CLI::log( 'sample_without_image: ' . ( $no_image_sample ? implode( ' | ', $no_image_sample ) : '0' ) );
WP_CLI::log( 'sample_with_image: ' . ( $ready_sample ? implode( ' | ', $ready_sample ) : '0' ) );

/**
 * Determine if a product has a usable featured image attachment.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function surtilec_readiness_product_has_real_featured_image( $product_id ) {
	$thumbnail_id = (int) get_post_thumbnail_id( $product_id );

	return $thumbnail_id > 0
		&& 'attachment' === get_post_type( $thumbnail_id )
		&& 'trash' !== get_post_status( $thumbnail_id );
}

/**
 * Compact product label for CLI reports.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function surtilec_readiness_product_label( $product_id ) {
	$sku   = trim( (string) get_post_meta( $product_id, '_sku', true ) );
	$title = get_the_title( $product_id );

	return '#' . $product_id . ( $sku ? ' SKU ' . $sku : '' ) . ' - ' . $title;
}
