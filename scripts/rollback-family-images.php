<?php
/**
 * Undo one family-image batch.
 *
 * Selects products by the `_surtilec_imagen_lote` handle the importer writes,
 * removes the featured-image assignment and the family meta, and leaves the
 * media files in place so a batch can be re-imported without re-uploading.
 *
 * Optionally scoped to a single family, which is the usual case: one family
 * turned out to be mapped wrong and the rest of the batch is fine.
 *
 * Usage:
 *   wp eval-file - <lote> dry
 *   wp eval-file - <lote> live
 *   wp eval-file - <lote> live familia=vntc-bandeja
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$batch_id = isset( $args[0] ) ? trim( (string) $args[0] ) : '';
$mode     = isset( $args[1] ) ? $args[1] : 'dry';
$live     = 'live' === $mode;

$family = '';
foreach ( $args as $arg ) {
	if ( 0 === strpos( (string) $arg, 'familia=' ) ) {
		$family = substr( (string) $arg, 8 );
	}
}

if ( '' === $batch_id ) {
	WP_CLI::error( 'Falta el identificador de lote. Uso: wp eval-file - <lote> dry|live [familia=<slug>]' );
}

$meta_query = array(
	array(
		'key'   => '_surtilec_imagen_lote',
		'value' => $batch_id,
	),
);
if ( '' !== $family ) {
	$meta_query[] = array(
		'key'   => '_surtilec_imagen_familia',
		'value' => $family,
	);
}

$product_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'meta_query'     => $meta_query,
	)
);

WP_CLI::log( '== Reversion de imagenes de familia ==' );
WP_CLI::log( 'Lote: ' . $batch_id . ( '' !== $family ? " / familia: $family" : '' ) );
WP_CLI::log( 'Productos encontrados: ' . count( $product_ids ) );

if ( empty( $product_ids ) ) {
	WP_CLI::success( 'Nada que revertir.' );
	return;
}

if ( ! $live ) {
	foreach ( array_slice( $product_ids, 0, 20 ) as $product_id ) {
		$sku = get_post_meta( $product_id, '_sku', true );
		WP_CLI::log( "  - #$product_id $sku" );
	}
	WP_CLI::success( 'Dry-run OK. ' . count( $product_ids ) . ' productos se revertirian.' );
	return;
}

$reverted = 0;
foreach ( $product_ids as $product_id ) {
	$product_id = (int) $product_id;
	delete_post_thumbnail( $product_id );
	delete_post_meta( $product_id, '_surtilec_image_src' );
	delete_post_meta( $product_id, '_surtilec_image_license_status' );
	delete_post_meta( $product_id, '_surtilec_image_license_reference' );
	delete_post_meta( $product_id, '_surtilec_image_match_status' );
	delete_post_meta( $product_id, '_surtilec_imagen_referencia' );
	delete_post_meta( $product_id, '_surtilec_imagen_familia' );
	delete_post_meta( $product_id, '_surtilec_imagen_lote' );
	++$reverted;
}

wp_cache_flush();
WP_CLI::success( "Reversion terminada: $reverted productos sin imagen destacada. Los archivos de medios se conservan." );
