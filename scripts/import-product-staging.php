<?php
/**
 * Import hidden WooCommerce staging drafts for unverified references.
 *
 * Usage:
 *   wp eval-file - <csv-path> <dry|live> < scripts/import-product-staging.php
 *
 * Staging products have no public technical claims, image or category. They
 * are hidden drafts and are not included in the public catalog or sitemap.
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$csv_path = isset( $args[0] ) ? $args[0] : '';
$mode     = isset( $args[1] ) ? $args[1] : 'dry';
$live     = 'live' === $mode;
$expected = array( 'sku', 'nombre', 'estado_staging', 'source_key', 'nota_staging' );

if ( ! $csv_path || ! file_exists( $csv_path ) ) {
	WP_CLI::error( "No se encontró el CSV: $csv_path" );
}

$fh     = fopen( $csv_path, 'r' );
$header = fgetcsv( $fh );
if ( $header !== $expected ) {
	WP_CLI::error( 'Encabezado de staging no coincide: ' . implode( ',', $expected ) );
}

$rows     = array();
$errors   = array();
$seen_sku = array();
$line     = 1;

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
	if ( '' === $row['sku'] || '' === $row['nombre'] ) {
		$errors[] = "Fila $line: SKU y nombre son obligatorios.";
		continue;
	}
	if ( isset( $seen_sku[ $row['sku'] ] ) ) {
		$errors[] = "Fila $line: SKU duplicado '{$row['sku']}'.";
		continue;
	}
	$seen_sku[ $row['sku'] ] = $line;
	if ( ! in_array( $row['estado_staging'], array( 'pendiente_piloto', 'pendiente_investigacion', 'duplicado_existente' ), true ) ) {
		$errors[] = "Fila $line: estado de staging no permitido '{$row['estado_staging']}'.";
	}
	if ( preg_match( '/cables\s*colombia|cablescolombia(?:\.com)?|multimedia02\.s3/i', $row['nombre'] . ' ' . $row['nota_staging'] ) ) {
		$errors[] = "Fila $line: marcador de fuente no permitido en el borrador.";
	}
	$row['_line'] = $line;
	$rows[]       = $row;
}
fclose( $fh );

WP_CLI::log( '== Validación staging ==' );
WP_CLI::log( 'Filas válidas: ' . count( $rows ) );
if ( $errors ) {
	foreach ( $errors as $error ) {
		WP_CLI::log( '  - ' . $error );
	}
	WP_CLI::error( 'Staging abortado: corrige los errores.' );
}

if ( ! $live ) {
	WP_CLI::success( 'Dry-run staging OK. ' . count( $rows ) . ' borradores listos.' );
	return;
}

$created = 0;
$updated = 0;
$same    = 0;

foreach ( $rows as $row ) {
	$id      = wc_get_product_id_by_sku( $row['sku'] );
	$is_new  = ! $id;
	$product = $is_new ? new WC_Product_Simple() : wc_get_product( $id );
	if ( ! $product ) {
		WP_CLI::warning( "SKU {$row['sku']}: no se pudo cargar el producto." );
		continue;
	}

	$product->set_name( $row['nombre'] );
	$product->set_sku( $row['sku'] );
	$product->set_status( 'draft' );
	$product->set_catalog_visibility( 'hidden' );
	$product->set_short_description( '' );
	$product->set_description( '' );
	$product->save();
	$id = $product->get_id();

	$meta = array(
		'_surtilec_staging_state'     => $row['estado_staging'],
		'_surtilec_staging_source_key' => $row['source_key'],
		'_surtilec_staging_note'       => $row['nota_staging'],
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $id, $key, sanitize_text_field( $value ) );
	}

	if ( $is_new ) {
		++$created;
		WP_CLI::log( "  BORRADOR CREADO {$row['sku']} (#$id)" );
	} else {
		++$updated;
	}
}

wp_cache_flush();
WP_CLI::success( "Staging terminado — creados: $created, actualizados: $updated, sin cambios: $same." );
