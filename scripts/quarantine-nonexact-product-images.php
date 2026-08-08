<?php
/**
 * Remove unproven batch images from staging product cards without deleting media.
 *
 * Only products in draft status are accepted. A row is retained only when the
 * manifest explicitly says that its image was reviewed as an exact match.
 * Non-exact images remain as unattached-from-card media for later review.
 *
 * Usage:
 *   wp eval-file - <manifest.csv> dry
 *   wp eval-file - <manifest.csv> live
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$manifest_path = isset( $args[0] ) ? $args[0] : '';
$mode          = isset( $args[1] ) ? $args[1] : 'dry';
$live          = 'live' === $mode;
$expected      = array( 'sku', 'image_file', 'image_title', 'alt_text', 'source_url', 'rights_status', 'rights_reference', 'image_match_status', 'sha256' );

if ( ! $manifest_path || ! file_exists( $manifest_path ) ) {
	WP_CLI::error( "No se encontro el manifiesto: $manifest_path" );
}

$fh     = fopen( $manifest_path, 'r' );
$header = fgetcsv( $fh );
if ( $header !== $expected ) {
	WP_CLI::error( 'Encabezado de imagenes no coincide: ' . implode( ',', $expected ) );
}

$rows   = array();
$errors = array();
$line   = 1;

while ( ( $data = fgetcsv( $fh ) ) !== false ) {
	++$line;
	if ( 1 === count( $data ) && '' === trim( (string) $data[0] ) ) {
		continue;
	}
	if ( count( $data ) !== count( $expected ) ) {
		$errors[] = "Fila $line: columnas incorrectas.";
		continue;
	}

	$row = array_combine( $expected, array_map( 'trim', $data ) );
	if ( '' === $row['sku'] || '' === $row['image_file'] ) {
		$errors[] = "Fila $line: SKU y archivo son obligatorios.";
		continue;
	}

	$product_id = wc_get_product_id_by_sku( $row['sku'] );
	if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
		$errors[] = "Fila $line: no existe producto para '{$row['sku']}'.";
		continue;
	}
	if ( 'draft' !== get_post_status( $product_id ) ) {
		$errors[] = "Fila $line: '{$row['sku']}' no esta en borrador; se rechaza para proteger productos publicos.";
		continue;
	}

	$row['_line']       = $line;
	$row['_product_id'] = (int) $product_id;
	$rows[]             = $row;
}
fclose( $fh );

if ( $errors ) {
	foreach ( $errors as $error ) {
		WP_CLI::log( '  - ' . $error );
	}
	WP_CLI::error( 'Cuarentena abortada: hay filas inseguras.' );
}

$stats = array(
	'total_rows'                    => count( $rows ),
	'exact_match_kept'              => 0,
	'nonexact_rows'                 => 0,
	'nonexact_featured_images_found' => 0,
	'nonexact_featured_images_removed' => 0,
	'nonexact_without_featured_image' => 0,
	'nonexact_thumbnail_mismatch'   => 0,
	);

foreach ( $rows as $row ) {
	$product_id = (int) $row['_product_id'];
	$is_exact   = 'coincidencia_exacta_revisada' === $row['image_match_status'];

	if ( $is_exact ) {
		$stats['exact_match_kept']++;
		continue;
	}

	$stats['nonexact_rows']++;
	$thumbnail_id = (int) get_post_thumbnail_id( $product_id );
	$batch_file   = $thumbnail_id ? (string) get_post_meta( $thumbnail_id, '_surtilec_batch_image_file', true ) : '';
	$matches_batch_image = $thumbnail_id > 0 && $batch_file === $row['image_file'];

	if ( $thumbnail_id > 0 ) {
		$stats['nonexact_featured_images_found']++;
		if ( ! $matches_batch_image ) {
			$stats['nonexact_thumbnail_mismatch']++;
			WP_CLI::warning( "{$row['sku']}: la miniatura no coincide con el archivo del manifiesto; no se retiro." );
			continue;
		}
	} else {
		$stats['nonexact_without_featured_image']++;
	}

	if ( ! $live ) {
		continue;
	}

	if ( $matches_batch_image ) {
		delete_post_thumbnail( $product_id );
		update_post_meta( $thumbnail_id, '_surtilec_image_match_status', 'revisar_variante_antes_de_publicar' );
		update_post_meta( $thumbnail_id, '_surtilec_image_quarantined', '1' );
		update_post_meta( $thumbnail_id, '_surtilec_image_quarantine_reason', 'coincidencia_exacta_no_demostrada' );
		$stats['nonexact_featured_images_removed']++;
	}

	delete_post_meta( $product_id, '_surtilec_image_src' );
	update_post_meta( $product_id, '_surtilec_image_match_status', 'revisar_variante_antes_de_publicar' );
	update_post_meta( $product_id, '_surtilec_image_quarantined', '1' );
	update_post_meta( $product_id, '_surtilec_image_quarantine_reason', 'coincidencia_exacta_no_demostrada' );
}

if ( $live ) {
	delete_transient( 'surtilec_no_image_product_ids' );
	wp_cache_flush();
}

WP_CLI::log( '== Cuarentena de imagenes no exactas ==' );
foreach ( $stats as $key => $value ) {
	WP_CLI::log( "$key: $value" );
}

if ( ! $live ) {
	WP_CLI::success( 'Dry-run OK. No se modificaron productos.' );
} else {
	WP_CLI::success( 'Cuarentena aplicada. Las imagenes no exactas siguen en Media, pero ya no se muestran como miniatura del producto.' );
}
