<?php
/**
 * Export the live featured-image state used by the internal image-rights queue.
 *
 * Usage:
 *   wp eval-file - < scripts/export-live-image-status.php > live-image-status.csv
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

fputcsv( STDOUT, array( 'sku', 'post_title', 'has_featured_image' ) );

foreach ( $query->posts as $product_id ) {
	$product_id = (int) $product_id;
	$sku        = trim( (string) get_post_meta( $product_id, '_sku', true ) );
	$title      = get_the_title( $product_id );
	$thumbnail  = (int) get_post_thumbnail_id( $product_id );

	fputcsv(
		STDOUT,
		array(
			$sku,
			$title,
			$thumbnail > 0 ? '1' : '0',
		)
	);
}
