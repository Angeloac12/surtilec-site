<?php
/**
 * Release the image-gated noindex from published products.
 *
 * Published products were marked noindex purely because they had no featured
 * image, which hid 1,068 of 1,385 products (77% of the catalog) from search.
 * A product page carrying an SKU, brand, a full attribute spec table and the
 * SEO/AEO Q&A copy is worth indexing whether or not a photo exists yet; the
 * Product schema simply omits the `image` key, which stays valid.
 *
 * Only rows this project marked itself are touched: a product is released only
 * when it still carries the `_surtilec_aioseo_noindex_reason=no_featured_image`
 * marker, so a manual noindex set in the AIOSEO UI is never overwritten.
 *
 * WP-CLI parses `--flags` itself, so the mode is a positional argument.
 *
 * Usage:
 *   scripts/wp.sh eval-file - dry-run < scripts/aioseo-release-image-gated-noindex.php
 *   scripts/wp.sh eval-file - live    < scripts/aioseo-release-image-gated-noindex.php
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

global $wpdb;

$aioseo_table = $wpdb->prefix . 'aioseo_posts';
$marker_key   = '_surtilec_aioseo_noindex_reason';
$marker_value = 'no_featured_image';
$now          = current_time( 'mysql', true );

$argv = isset( $args ) && is_array( $args ) ? $args : array();
$live = in_array( 'live', $argv, true );

if ( ! $live && ! in_array( 'dry-run', $argv, true ) ) {
	WP_CLI::error( 'Pasa "dry-run" o "live" como argumento posicional.' );
}

$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $aioseo_table ) );
if ( $table_exists !== $aioseo_table ) {
	WP_CLI::error( "No existe la tabla AIOSEO esperada: $aioseo_table" );
}

$marked_ids = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT pm.post_id
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE pm.meta_key = %s
		   AND pm.meta_value = %s
		   AND p.post_type = 'product'
		   AND p.post_status = 'publish'
		 ORDER BY p.ID ASC",
		$marker_key,
		$marker_value
	)
);

$stats = array(
	'marked_products'        => count( $marked_ids ),
	'released'               => 0,
	'already_indexable'      => 0,
	'skipped_manual_noindex' => 0,
	'markers_removed'        => 0,
);

$sample = array();

foreach ( $marked_ids as $product_id ) {
	$product_id = (int) $product_id;

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT id, robots_default, robots_noindex FROM {$aioseo_table} WHERE post_id = %d LIMIT 1",
			$product_id
		),
		ARRAY_A
	);

	if ( ! $row ) {
		$stats['already_indexable']++;
	} elseif ( 1 === (int) $row['robots_default'] || 0 === (int) $row['robots_noindex'] ) {
		// Someone already put it back to default/indexable.
		$stats['already_indexable']++;
	} else {
		if ( $live ) {
			$wpdb->update(
				$aioseo_table,
				array(
					'robots_default'  => 1,
					'robots_noindex'  => 0,
					'robots_nofollow' => 0,
					'updated'         => $now,
				),
				array( 'id' => (int) $row['id'] )
			);
		}
		$stats['released']++;

		if ( count( $sample ) < 10 ) {
			$sku      = trim( (string) get_post_meta( $product_id, '_sku', true ) );
			$sample[] = '#' . $product_id . ( $sku ? ' SKU ' . $sku : '' ) . ' - ' . get_the_title( $product_id );
		}
	}

	if ( $live ) {
		delete_post_meta( $product_id, $marker_key, $marker_value );
		$stats['markers_removed']++;
	}
}

if ( $live ) {
	delete_transient( 'surtilec_no_image_product_ids' );
	wp_cache_flush();
}

WP_CLI::success( $live ? 'Noindex por falta de imagen liberado.' : 'Simulación (no se escribió nada).' );

foreach ( $stats as $key => $value ) {
	WP_CLI::log( "$key: $value" );
}

WP_CLI::log( 'sample_released: ' . ( $sample ? implode( ' | ', $sample ) : '0' ) );
