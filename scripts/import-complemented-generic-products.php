<?php
/**
 * Import researched generic product fichas without assigning an unverified
 * manufacturer. The default live mode keeps products as hidden drafts.
 *
 * Usage:
 *   wp eval-file <script> <products.csv> <sources.csv> <dry|live> [publish]
 *
 * `publish` is an explicit second gate. It is intentionally separate from
 * the normal live import because family-level research is not an exact model
 * match and the image queue still contains variant-review flags.
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$products_path = isset( $args[0] ) ? $args[0] : '';
$sources_path  = isset( $args[1] ) ? $args[1] : '';
$mode          = isset( $args[2] ) ? $args[2] : 'dry';
$publish       = isset( $args[3] ) && 'publish' === $args[3];
$live          = 'live' === $mode;

$expected_products = array( 'sku', 'nombre', 'categoria', 'subcategoria', 'marca', 'calibre_awg', 'num_conductores', 'voltaje', 'apantallado', 'chaqueta', 'norma', 'aplicacion', 'potencia_hp', 'voltaje_entrada', 'serie', 'descripcion_corta', 'imagen', 'descripcion_larga', 'unidad_venta', 'temperatura_maxima' );
$expected_sources  = array( 'sku', 'referencia_fabricante', 'marca', 'proveedor', 'fuente_datos_url', 'ficha_tecnica_url', 'fuente_imagen_url', 'estado_fuente', 'estado_imagen', 'verificado_en', 'notas' );

if ( ! $products_path || ! file_exists( $products_path ) ) {
	WP_CLI::error( "No se encontró el CSV de productos: $products_path" );
}
if ( ! $sources_path || ! file_exists( $sources_path ) ) {
	WP_CLI::error( "No se encontró el registro de fuentes: $sources_path" );
}

$read_csv = function ( $path, $expected ) {
	$fh     = fopen( $path, 'r' );
	$header = fgetcsv( $fh );
	if ( $header !== $expected ) {
		WP_CLI::error( 'Encabezado incorrecto en ' . basename( $path ) . ': ' . implode( ',', $expected ) );
	}
	$rows = array();
	$line = 1;
	while ( ( $data = fgetcsv( $fh ) ) !== false ) {
		++$line;
		if ( 1 === count( $data ) && '' === trim( (string) $data[0] ) ) {
			continue;
		}
		if ( count( $data ) !== count( $expected ) ) {
			WP_CLI::error( basename( $path ) . " fila $line: número de columnas incorrecto." );
		}
		$rows[] = array_combine( $expected, array_map( 'trim', $data ) );
	}
	fclose( $fh );
	return $rows;
};

$products = $read_csv( $products_path, $expected_products );
$sources  = $read_csv( $sources_path, $expected_sources );
$source_by_sku = array();
$errors = array();

foreach ( $sources as $source ) {
	if ( '' === $source['sku'] || isset( $source_by_sku[ $source['sku'] ] ) ) {
		$errors[] = "Fuente duplicada o sin SKU '{$source['sku']}'.";
		continue;
	}
	if ( ! in_array( $source['estado_fuente'], array( 'generica_verificada', 'verificada' ), true ) ) {
		$errors[] = "Fuente '{$source['sku']}' no tiene estado de investigación permitido.";
	}
	if ( ! preg_match( '#^https?://#i', $source['fuente_datos_url'] ) || ! preg_match( '#^https?://#i', $source['ficha_tecnica_url'] ) ) {
		$errors[] = "Fuente '{$source['sku']}' necesita URLs técnicas oficiales.";
	}
	if ( preg_match( '/cables\s*colombia|cablescolombia(?:\.com)?|multimedia02\.s3/i', implode( ' ', $source ) ) ) {
		$errors[] = "Fuente '{$source['sku']}' contiene marcador de origen no permitido.";
	}
	$source_by_sku[ $source['sku'] ] = $source;
}

$seen = array();
foreach ( $products as $product ) {
	$sku = $product['sku'];
	if ( '' === $sku || isset( $seen[ $sku ] ) ) {
		$errors[] = "Producto duplicado o sin SKU '$sku'.";
		continue;
	}
	$seen[ $sku ] = true;
	if ( ! isset( $source_by_sku[ $sku ] ) ) {
		$errors[] = "Falta fuente interna para '$sku'.";
	}
	$public = array( 'nombre', 'marca', 'norma', 'aplicacion', 'serie', 'descripcion_corta', 'descripcion_larga' );
	foreach ( $public as $field ) {
		if ( preg_match( '/cables\s*colombia|cablescolombia(?:\.com)?|multimedia02\.s3/i', (string) $product[ $field ] ) ) {
			$errors[] = "Producto '$sku' contiene marcador de origen en '$field'.";
		}
	}
}

WP_CLI::log( '== Validación de fichas genéricas ==' );
WP_CLI::log( 'Productos: ' . count( $products ) );
WP_CLI::log( 'Fuentes técnicas: ' . count( $sources ) );
if ( $errors ) {
	foreach ( $errors as $error ) {
		WP_CLI::log( '  - ' . $error );
	}
	WP_CLI::error( 'Importación abortada: corrige la cola complementada.' );
}

if ( ! $live ) {
	WP_CLI::success( 'Dry-run OK. Las fichas se importarían como borradores ocultos.' );
	return;
}

$cat_by_key = array();
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $term ) {
	$cat_by_key[ mb_strtolower( $term->name ) ] = $term;
	$cat_by_key[ mb_strtolower( $term->slug ) ] = $term;
}
$resolve_cat = function ( $value ) use ( $cat_by_key ) {
	$key = mb_strtolower( trim( (string) $value ) );
	return '' !== $key && isset( $cat_by_key[ $key ] ) ? $cat_by_key[ $key ] : null;
};
$attr_map = array(
	'marca'           => 'pa_marca',
	'calibre_awg'     => 'pa_calibre-awg',
	'num_conductores' => 'pa_numero-conductores',
	'voltaje'         => 'pa_voltaje',
	'apantallado'     => 'pa_apantallado',
	'chaqueta'        => 'pa_chaqueta',
	'norma'           => 'pa_norma',
	'aplicacion'      => 'pa_aplicacion',
	'serie'           => 'pa_serie',
);
$ensure_term = function ( $taxonomy, $value ) {
	if ( '' === trim( (string) $value ) || ! taxonomy_exists( $taxonomy ) ) {
		return 0;
	}
	$term = get_term_by( 'name', $value, $taxonomy );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$created = wp_insert_term( $value, $taxonomy );
	return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
};

$created = 0;
$updated = 0;
$skipped = 0;
foreach ( $products as $row ) {
	$id      = wc_get_product_id_by_sku( $row['sku'] );
	$is_new  = ! $id;
	$product = $is_new ? new WC_Product_Simple() : wc_get_product( $id );
	if ( ! $product ) {
		WP_CLI::warning( "No se pudo cargar {$row['sku']}." );
		++$skipped;
		continue;
	}

	$product->set_name( $row['nombre'] );
	$product->set_sku( $row['sku'] );
	$product->set_short_description( wp_kses_post( $row['descripcion_corta'] ) );
	$product->set_description( wp_kses_post( $row['descripcion_larga'] ) );
	$product->set_status( $publish ? 'publish' : 'draft' );
	$product->set_catalog_visibility( $publish ? 'visible' : 'hidden' );

	$parent = $resolve_cat( $row['categoria'] );
	if ( $parent ) {
		$product->set_category_ids( array( (int) $parent->term_id ) );
	}

	$attributes = array();
	$position = 0;
	foreach ( $attr_map as $column => $taxonomy ) {
		if ( '' === trim( (string) $row[ $column ] ) ) {
			continue;
		}
		$term_id = $ensure_term( $taxonomy, $row[ $column ] );
		if ( ! $term_id ) {
			continue;
		}
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array( $term_id ) );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$attribute->set_position( $position++ );
		$attributes[ $taxonomy ] = $attribute;
	}
	$product->set_attributes( $attributes );
	$product->save();
	$id = $product->get_id();

	$source = $source_by_sku[ $row['sku'] ];
	$meta = array(
		'_surtilec_generic_product'          => '1',
		'_surtilec_generic_research_status'  => 'familia_verificada_no_referencia_exacta',
		'_surtilec_generic_verified_at'      => $source['verificado_en'],
		'_surtilec_public_reference'         => $row['sku'],
		'_surtilec_source_status'            => $source['estado_fuente'],
		'_surtilec_manufacturer_reference'   => $source['referencia_fabricante'],
		'_surtilec_source_brand'             => $source['marca'],
		'_surtilec_supplier'                 => $source['proveedor'],
		'_surtilec_source_url'               => $source['fuente_datos_url'],
		'_surtilec_datasheet_url'            => $source['ficha_tecnica_url'],
		'_surtilec_source_verified_at'       => $source['verificado_en'],
		'_surtilec_source_notes'             => $source['notas'],
		'_surtilec_unidad_venta'             => $row['unidad_venta'],
		'_surtilec_temperatura_maxima'       => $row['temperatura_maxima'],
	);
	foreach ( $meta as $key => $value ) {
		if ( '' === trim( (string) $value ) ) {
			delete_post_meta( $id, $key );
		} else {
			update_post_meta( $id, $key, sanitize_text_field( $value ) );
		}
	}

	if ( $is_new ) {
		++$created;
	} else {
		++$updated;
	}
}

wp_cache_flush();
WP_CLI::success( "Fichas terminadas: creadas $created, actualizadas $updated, omitidas $skipped. Publicadas: " . ( $publish ? 'sí' : 'no; permanecen ocultas' ) . '.' );
