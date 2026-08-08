<?php
/**
 * Assign an authorized local image batch to WooCommerce products by SKU.
 *
 * Usage:
 *   wp eval-file - <manifest.csv> <dry|live> < scripts/import-authorized-product-images.php
 *
 * The source URL is used only in the private manifest. It is deliberately not
 * copied into WordPress metadata or public product fields.
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$manifest_path = isset( $args[0] ) ? $args[0] : '';
$mode          = isset( $args[1] ) ? $args[1] : 'dry';
$create_missing = isset( $args[2] ) && 'create-missing' === $args[2];
$live          = 'live' === $mode;
$image_dir     = $manifest_path ? dirname( $manifest_path ) . '/images' : '';
$expected      = array( 'sku', 'image_file', 'image_title', 'alt_text', 'source_url', 'rights_status', 'rights_reference', 'image_match_status', 'sha256' );

if ( ! $manifest_path || ! file_exists( $manifest_path ) ) {
	WP_CLI::error( "No se encontro el manifiesto: $manifest_path" );
}

$fh     = fopen( $manifest_path, 'r' );
$header = fgetcsv( $fh );
if ( $header !== $expected ) {
	WP_CLI::error( 'Encabezado de imagenes no coincide: ' . implode( ',', $expected ) );
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

	if ( '' === $row['sku'] || '' === $row['image_file'] || '' === $row['alt_text'] ) {
		$errors[] = "Fila $line: SKU, archivo y alt_text son obligatorios.";
		continue;
	}
	if ( isset( $seen_sku[ $row['sku'] ] ) ) {
		$errors[] = "Fila $line: SKU duplicado '{$row['sku']}'.";
		continue;
	}
	$seen_sku[ $row['sku'] ] = $line;
	if ( ! in_array( $row['rights_status'], array( 'autorizada', 'propia' ), true ) ) {
		$errors[] = "Fila $line: estado de derechos no permitido para '{$row['sku']}'.";
	}
	if ( '' === $row['rights_reference'] ) {
		$errors[] = "Fila $line: falta referencia de derechos para '{$row['sku']}'.";
	}
	if ( basename( $row['image_file'] ) !== $row['image_file'] || ! preg_match( '/\.(?:jpe?g|webp)$/i', $row['image_file'] ) ) {
		$errors[] = "Fila $line: formato de archivo no permitido para '{$row['sku']}'.";
	}
	if ( preg_match( '/cables\s*colombia|cablescolombia(?:\.com)?|multimedia02\.s3/i', $row['image_title'] . ' ' . $row['alt_text'] ) ) {
		$errors[] = "Fila $line: marcador de fuente en metadatos publicos de '{$row['sku']}'.";
	}
	$file_path = $image_dir . '/' . $row['image_file'];
	if ( ! file_exists( $file_path ) ) {
		$errors[] = "Fila $line: no existe la imagen '{$row['image_file']}'.";
	} else {
		$image_info = @getimagesize( $file_path );
		if ( ! is_array( $image_info ) || ! in_array( $image_info['mime'], array( 'image/jpeg', 'image/webp' ), true ) ) {
			$errors[] = "Fila $line: imagen invalida '{$row['image_file']}'.";
		}
	}

	$product_id = wc_get_product_id_by_sku( $row['sku'] );
	if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
		if ( $create_missing && 0 === strpos( $row['sku'], 'SRT-CTRL-' ) ) {
			$row['_create_missing'] = true;
		} else {
			$errors[] = "Fila $line: no existe producto para '{$row['sku']}'.";
		}
	} elseif ( has_post_thumbnail( $product_id ) && get_post_meta( $product_id, '_surtilec_image_src', true ) !== $row['image_file'] ) {
		$errors[] = "Fila $line: '{$row['sku']}' ya tiene otra imagen destacada.";
	}

	$row['_line']       = $line;
	$row['_product_id'] = (int) $product_id;
	$row['_file_path']  = $file_path;
	$rows[]             = $row;
}
fclose( $fh );

WP_CLI::log( '== Validacion de imagenes ==' );
WP_CLI::log( 'Filas validas: ' . count( $rows ) );
if ( $errors ) {
	foreach ( $errors as $error ) {
		WP_CLI::log( '  - ' . $error );
	}
	WP_CLI::error( 'Importacion de imagenes abortada: corrige los errores.' );
}

if ( ! $live ) {
	WP_CLI::success( 'Dry-run OK. ' . count( $rows ) . ' imagenes listas.' );
	return;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$created = 0;
$reused  = 0;
$created_products = 0;
$blocked_review = 0;

foreach ( $rows as $row ) {
	$product_id = (int) $row['_product_id'];
	if ( 'coincidencia_exacta_revisada' !== $row['image_match_status'] ) {
		++$blocked_review;
		if ( $product_id ) {
			$current_thumbnail = (int) get_post_thumbnail_id( $product_id );
			$current_batch_file = $current_thumbnail ? (string) get_post_meta( $current_thumbnail, '_surtilec_batch_image_file', true ) : '';
			if ( $current_thumbnail && $current_batch_file === $row['image_file'] ) {
				delete_post_thumbnail( $product_id );
				update_post_meta( $current_thumbnail, '_surtilec_image_match_status', 'revisar_variante_antes_de_publicar' );
				update_post_meta( $current_thumbnail, '_surtilec_image_quarantined', '1' );
				update_post_meta( $current_thumbnail, '_surtilec_image_quarantine_reason', 'coincidencia_exacta_no_demostrada' );
			}
			delete_post_meta( $product_id, '_surtilec_image_src' );
			update_post_meta( $product_id, '_surtilec_image_match_status', 'revisar_variante_antes_de_publicar' );
			update_post_meta( $product_id, '_surtilec_image_quarantined', '1' );
			update_post_meta( $product_id, '_surtilec_image_quarantine_reason', 'coincidencia_exacta_no_demostrada' );
		}
		WP_CLI::log( "  OMITIDA {$row['sku']}: imagen sin coincidencia exacta revisada." );
		continue;
	}
	if ( ! $product_id && ! empty( $row['_create_missing'] ) ) {
		$product_name = preg_replace( '/\s+-\s+Surtilec$/', '', $row['image_title'] );
		$product      = new WC_Product_Simple();
		$product->set_name( $product_name );
		$product->set_sku( $row['sku'] );
		$product->set_status( 'draft' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_short_description( '' );
		$product->set_description( '' );
		$product->save();
		$product_id = $product->get_id();
		update_post_meta( $product_id, '_surtilec_staging_state', 'pendiente_piloto' );
		update_post_meta( $product_id, '_surtilec_staging_source_key', 'image-queue-2026-08-03' );
		update_post_meta( $product_id, '_surtilec_staging_note', 'Validar ficha tecnica, referencia de fabricante y coincidencia de imagen antes de publicar.' );
		++$created_products;
	}
	$attachment = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_surtilec_batch_image_file',
			'meta_value'     => $row['image_file'],
		)
	);
	$attachment_id = ! empty( $attachment ) ? (int) $attachment[0] : 0;

	if ( ! $attachment_id ) {
		$tmp = wp_tempnam( $row['image_file'] );
		if ( ! $tmp || ! copy( $row['_file_path'], $tmp ) ) {
			WP_CLI::warning( "{$row['sku']}: no se pudo preparar el archivo." );
			continue;
		}
		$file_array = array(
			'name'     => $row['image_file'],
			'tmp_name' => $tmp,
		);
		$attachment_id = media_handle_sideload( $file_array, $product_id );
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp );
			WP_CLI::warning( "{$row['sku']}: {$attachment_id->get_error_message()}" );
			continue;
		}
		++$created;
	} else {
		++$reused;
	}

	wp_update_post(
		array(
			'ID'           => $attachment_id,
			'post_title'   => sanitize_text_field( $row['image_title'] ),
			'post_excerpt' => '',
			'post_content' => '',
		)
	);
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $row['alt_text'] ) );
	update_post_meta( $attachment_id, '_surtilec_batch_image_file', $row['image_file'] );
	update_post_meta( $attachment_id, '_surtilec_image_license_status', $row['rights_status'] );
	update_post_meta( $attachment_id, '_surtilec_image_license_reference', sanitize_text_field( $row['rights_reference'] ) );
	update_post_meta( $attachment_id, '_surtilec_image_match_status', sanitize_text_field( $row['image_match_status'] ) );

	set_post_thumbnail( $product_id, $attachment_id );
	update_post_meta( $product_id, '_surtilec_image_src', $row['image_file'] );
	update_post_meta( $product_id, '_surtilec_image_license_status', $row['rights_status'] );
	update_post_meta( $product_id, '_surtilec_image_license_reference', sanitize_text_field( $row['rights_reference'] ) );
	update_post_meta( $product_id, '_surtilec_image_match_status', sanitize_text_field( $row['image_match_status'] ) );
	WP_CLI::log( "  IMAGEN {$row['sku']} (#$product_id)" );
}

wp_cache_flush();
WP_CLI::success( "Imagenes terminadas - productos staging creados: $created_products, imagenes nuevas: $created, reutilizadas: $reused, filas bloqueadas por revision: $blocked_review." );
