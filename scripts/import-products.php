<?php
/**
 * Surtilec product importer / validator.
 *
 * Run via WP-CLI eval-file with stdin:
 *   wp eval-file - <csv-path> <dry|live>   < scripts/import-products.php
 *
 * Reads the master CSV (single source of truth), validates it, and in `live`
 * mode upserts products by SKU. Never sets a price. Idempotent: a re-run with
 * the same CSV produces zero changes.
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$csv_path = isset( $args[0] ) ? $args[0] : '';
$mode     = isset( $args[1] ) ? $args[1] : 'dry';
$live     = ( 'live' === $mode );
$img_dir  = rtrim( dirname( $csv_path ), '/' ) . '/images';

if ( ! $csv_path || ! file_exists( $csv_path ) ) {
	WP_CLI::error( "No se encontró el CSV: $csv_path" );
}

$expected = array( 'sku', 'nombre', 'categoria', 'subcategoria', 'marca', 'calibre_awg', 'num_conductores', 'voltaje', 'apantallado', 'chaqueta', 'norma', 'aplicacion', 'potencia_hp', 'voltaje_entrada', 'serie', 'descripcion_corta', 'imagen' );

// Spec column -> global attribute taxonomy.
$attr_map = array(
	'marca'           => 'pa_marca',
	'calibre_awg'     => 'pa_calibre-awg',
	'num_conductores' => 'pa_numero-conductores',
	'voltaje'         => 'pa_voltaje',
	'apantallado'     => 'pa_apantallado',
	'chaqueta'        => 'pa_chaqueta',
	'norma'           => 'pa_norma',
	'aplicacion'      => 'pa_aplicacion',
	'potencia_hp'     => 'pa_potencia-hp',
	'voltaje_entrada' => 'pa_voltaje-entrada',
	'serie'           => 'pa_serie',
);

$cable_families = array( 'cables de control', 'cable thhn / thwn-2', 'cables para variadores vfd', 'cables especiales' );

// --- Load product_cat terms (name + slug lookups, lowercased). ---
$cat_by_key = array();
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $t ) {
	$cat_by_key[ mb_strtolower( $t->name ) ] = $t;
	$cat_by_key[ mb_strtolower( $t->slug ) ] = $t;
}

/**
 * Resolve a category/subcategory string to a WP_Term, or null.
 */
$resolve_cat = function ( $value ) use ( $cat_by_key ) {
	$value = mb_strtolower( trim( $value ) );
	return ( '' !== $value && isset( $cat_by_key[ $value ] ) ) ? $cat_by_key[ $value ] : null;
};

// --- Read CSV. ---
$fh = fopen( $csv_path, 'r' );
$header = fgetcsv( $fh );
if ( $header !== $expected ) {
	WP_CLI::error( "Encabezado del CSV no coincide con el formato esperado.\nEsperado: " . implode( ',', $expected ) );
}

$rows   = array();
$line   = 1;
$errors = array();
$warns  = array();
$seen_sku = array();

while ( ( $data = fgetcsv( $fh ) ) !== false ) {
	++$line;
	if ( 1 === count( $data ) && '' === trim( (string) $data[0] ) ) {
		continue; // blank line.
	}
	if ( count( $data ) !== count( $expected ) ) {
		$errors[] = "Fila $line: número de columnas incorrecto (" . count( $data ) . ' de ' . count( $expected ) . ').';
		continue;
	}
	$row = array_combine( $expected, array_map( 'trim', $data ) );

	if ( '' === $row['sku'] || '' === $row['nombre'] ) {
		$errors[] = "Fila $line: 'sku' y 'nombre' son obligatorios.";
		continue;
	}
	if ( isset( $seen_sku[ $row['sku'] ] ) ) {
		$errors[] = "Fila $line: SKU duplicado '{$row['sku']}' (ya en fila {$seen_sku[ $row['sku'] ]}).";
		continue;
	}
	$seen_sku[ $row['sku'] ] = $line;

	// Category resolution.
	$parent = $resolve_cat( $row['categoria'] );
	if ( ! $parent ) {
		$errors[] = "Fila $line: categoría desconocida '{$row['categoria']}'.";
		continue;
	}
	$child = null;
	if ( '' !== $row['subcategoria'] ) {
		$child = $resolve_cat( $row['subcategoria'] );
		if ( ! $child ) {
			$errors[] = "Fila $line: subcategoría desconocida '{$row['subcategoria']}'.";
			continue;
		}
		if ( (int) $child->parent !== (int) $parent->term_id ) {
			$errors[] = "Fila $line: '{$row['subcategoria']}' no es subcategoría de '{$row['categoria']}'.";
			continue;
		}
	}

	// Required by type.
	$is_cable    = in_array( mb_strtolower( $row['categoria'] ), $cable_families, true );
	$is_variador = ( 'variadores de frecuencia' === mb_strtolower( $row['subcategoria'] ) );
	if ( $is_cable && ( '' === $row['calibre_awg'] || '' === $row['num_conductores'] ) ) {
		$errors[] = "Fila $line: cable requiere 'calibre_awg' y 'num_conductores'.";
		continue;
	}
	if ( $is_variador && ( '' === $row['potencia_hp'] || '' === $row['voltaje_entrada'] ) ) {
		$errors[] = "Fila $line: variador requiere 'potencia_hp' y 'voltaje_entrada'.";
		continue;
	}

	// Image (warning only).
	if ( '' !== $row['imagen'] && ! file_exists( "$img_dir/{$row['imagen']}" ) ) {
		$warns[] = "Fila $line: imagen '{$row['imagen']}' no encontrada en data/images/ (se omitirá).";
	}

	$row['_line']   = $line;
	$row['_parent'] = $parent;
	$row['_child']  = $child;
	$rows[]         = $row;
}
fclose( $fh );

// --- Report. ---
WP_CLI::log( '== Validación ==' );
WP_CLI::log( 'Filas válidas: ' . count( $rows ) );
if ( $warns ) {
	WP_CLI::log( "\nAdvertencias (" . count( $warns ) . '):' );
	foreach ( $warns as $w ) {
		WP_CLI::log( '  - ' . $w );
	}
}
if ( $errors ) {
	WP_CLI::log( "\nERRORES (" . count( $errors ) . '):' );
	foreach ( $errors as $e ) {
		WP_CLI::log( '  - ' . $e );
	}
	WP_CLI::error( 'Importación abortada: corrige los errores.' );
}

if ( ! $live ) {
	WP_CLI::success( 'Dry-run OK. ' . count( $rows ) . ' filas listas para importar.' );
	return;
}

// =========================  LIVE IMPORT  =========================
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Ensure an attribute term exists; return its term_id. Logs newly created.
 */
$ensure_term = function ( $taxonomy, $value ) {
	$existing = get_term_by( 'name', $value, $taxonomy );
	if ( $existing ) {
		return (int) $existing->term_id;
	}
	$res = wp_insert_term( $value, $taxonomy );
	if ( is_wp_error( $res ) ) {
		return 0;
	}
	WP_CLI::log( "  TERM_CREADO $taxonomy #{$res['term_id']} '$value'" );
	return (int) $res['term_id'];
};

$created = 0;
$updated = 0;
$same    = 0;

foreach ( $rows as $row ) {
	$id      = wc_get_product_id_by_sku( $row['sku'] );
	$is_new  = ! $id;
	$product = $is_new ? new WC_Product_Simple() : wc_get_product( $id );

	$product->set_name( $row['nombre'] );
	$product->set_sku( $row['sku'] );
	$product->set_short_description( $row['descripcion_corta'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );

	// Categories (parent + child), only set if different.
	$cat_ids = array( (int) $row['_parent']->term_id );
	if ( $row['_child'] ) {
		$cat_ids[] = (int) $row['_child']->term_id;
	}
	sort( $cat_ids );
	$current_cats = $product->get_category_ids();
	sort( $current_cats );
	if ( $cat_ids !== $current_cats ) {
		$product->set_category_ids( $cat_ids );
	}

	// Attributes from spec columns.
	$attributes  = array();
	$position    = 0;
	foreach ( $attr_map as $col => $taxonomy ) {
		if ( '' === $row[ $col ] ) {
			continue;
		}
		$term_id = $ensure_term( $taxonomy, $row[ $col ] );
		if ( ! $term_id ) {
			continue;
		}
		$attr = new WC_Product_Attribute();
		$attr->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
		$attr->set_name( $taxonomy );
		$attr->set_options( array( $term_id ) );
		$attr->set_visible( true );
		$attr->set_variation( false );
		$attr->set_position( $position++ );
		$attributes[ $taxonomy ] = $attr;
	}
	// Compare attribute term-id sets to avoid needless change.
	$desired_sig = array();
	foreach ( $attributes as $tax => $a ) {
		$desired_sig[ $tax ] = $a->get_options();
	}
	$current_sig = array();
	foreach ( $product->get_attributes() as $tax => $a ) {
		if ( $a->is_taxonomy() ) {
			$current_sig[ $tax ] = $a->get_options();
		}
	}
	ksort( $desired_sig );
	ksort( $current_sig );
	if ( $desired_sig !== $current_sig ) {
		$product->set_attributes( $attributes );
	}

	$changed = $is_new || ! empty( $product->get_changes() );
	$product->save();
	$id = $product->get_id();

	// Featured image (idempotent via _surtilec_image_src meta).
	$img_changed = false;
	if ( '' !== $row['imagen'] && file_exists( "$img_dir/{$row['imagen']}" ) ) {
		if ( get_post_meta( $id, '_surtilec_image_src', true ) !== $row['imagen'] || ! has_post_thumbnail( $id ) ) {
			$tmp  = wp_tempnam( $row['imagen'] );
			copy( "$img_dir/{$row['imagen']}", $tmp );
			$file_array = array(
				'name'     => $row['imagen'],
				'tmp_name' => $tmp,
			);
			$att_id = media_handle_sideload( $file_array, $id );
			if ( ! is_wp_error( $att_id ) ) {
				set_post_thumbnail( $id, $att_id );
				update_post_meta( $id, '_surtilec_image_src', $row['imagen'] );
				$img_changed = true;
			} else {
				@unlink( $tmp );
				WP_CLI::warning( "SKU {$row['sku']}: no se pudo subir la imagen." );
			}
		}
	}

	if ( $is_new ) {
		++$created;
		WP_CLI::log( "  CREADO  {$row['sku']} (#$id) — {$row['nombre']}" );
	} elseif ( $changed || $img_changed ) {
		++$updated;
		WP_CLI::log( "  ACTUALIZADO  {$row['sku']} (#$id)" );
	} else {
		++$same;
	}
}

WP_CLI::success( "Importación terminada — creados: $created, actualizados: $updated, sin cambios: $same." );
