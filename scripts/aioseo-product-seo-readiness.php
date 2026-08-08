<?php
/**
 * Fill blank product AIOSEO fields from existing product data.
 *
 * This never overwrites manual SEO fields or changes product content, robots,
 * schema, price, attributes, or image state.
 *
 * Usage:
 *   ssh ... "cd <wp> && wp eval-file -" < scripts/aioseo-product-seo-readiness.php
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

global $wpdb;

$table = $wpdb->prefix . 'aioseo_posts';
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
	WP_CLI::error( "No existe la tabla AIOSEO esperada: $table" );
}

$posts = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

$stats = array(
	'published_products'    => count( $posts ),
	'titles_created'        => 0,
	'descriptions_created'  => 0,
	'social_fields_created' => 0,
	'rows_inserted'         => 0,
	'rows_updated'          => 0,
	'blocked_source_markers' => 0,
);
$now = current_time( 'mysql', true );

foreach ( $posts as $post ) {
	$name = trim( wp_strip_all_tags( $post->post_title ) );
	$base = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ) ) );

	if ( preg_match( '/cables\s*colombia|cablescolombia(?:\.com)?/i', $name . ' ' . $base ) ) {
		++$stats['blocked_source_markers'];
		continue;
	}

	$seo_title = surtilec_product_seo_title( $name );
	$seo_desc  = surtilec_product_seo_description( $base, $name );
	$row       = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE post_id = %d LIMIT 1", $post->ID ), ARRAY_A );

	if ( $row ) {
		$updates = array();
		if ( '' === trim( (string) $row['title'] ) ) {
			$updates['title'] = $seo_title;
			++$stats['titles_created'];
		}
		if ( '' === trim( (string) $row['description'] ) ) {
			$updates['description'] = $seo_desc;
			++$stats['descriptions_created'];
		}
		foreach ( array( 'og_title', 'twitter_title' ) as $field ) {
			if ( '' === trim( (string) $row[ $field ] ) ) {
				$updates[ $field ] = $seo_title;
				++$stats['social_fields_created'];
			}
		}
		foreach ( array( 'og_description', 'twitter_description' ) as $field ) {
			if ( '' === trim( (string) $row[ $field ] ) ) {
				$updates[ $field ] = $seo_desc;
				++$stats['social_fields_created'];
			}
		}
		if ( $updates ) {
			$updates['updated'] = $now;
			$wpdb->update( $table, $updates, array( 'id' => (int) $row['id'] ) );
			++$stats['rows_updated'];
		}
		continue;
	}

	$thumbnail_id = (int) get_post_thumbnail_id( $post->ID );
	$has_image    = $thumbnail_id && 'attachment' === get_post_type( $thumbnail_id );
	$wpdb->insert(
		$table,
		array(
			'post_id'                 => $post->ID,
			'title'                   => $seo_title,
			'description'             => $seo_desc,
			'og_title'                => $seo_title,
			'og_description'          => $seo_desc,
			'twitter_title'           => $seo_title,
			'twitter_description'     => $seo_desc,
			'robots_default'          => 1,
			'robots_noindex'          => $has_image ? 0 : 1,
			'robots_nofollow'         => 0,
			'robots_noimageindex'     => 0,
			'robots_max_imagepreview' => 'large',
			'seo_score'               => 0,
			'schema_type'             => 'default',
			'created'                 => $now,
			'updated'                 => $now,
		)
	);
	++$stats['rows_inserted'];
	++$stats['titles_created'];
	++$stats['descriptions_created'];
	$stats['social_fields_created'] += 4;
}

foreach ( $stats as $key => $value ) {
	WP_CLI::log( "$key: $value" );
}

if ( $stats['blocked_source_markers'] > 0 ) {
	WP_CLI::error( 'No se aplicaron metadatos a productos con marcadores de fuente no permitidos.' );
}

WP_CLI::success( 'AIOSEO product SEO readiness aplicado sin sobrescribir campos manuales.' );

/**
 * @param string $name Product name.
 * @return string
 */
function surtilec_product_seo_title( $name ) {
	$brand = ' | Surtilec';
	$limit = 60 - strlen( $brand );
	$name  = trim( (string) $name );
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $name ) > $limit ) {
		$name = surtilec_product_seo_word_boundary( $name, $limit - 3 ) . '...';
	} elseif ( strlen( $name ) > $limit ) {
		$name = surtilec_product_seo_word_boundary( $name, $limit - 3 ) . '...';
	}
	return $name . $brand;
}

/**
 * Trim generated SEO copy at a word boundary when possible.
 *
 * @param string $value Text to trim.
 * @param int    $length Maximum length before the ellipsis.
 * @return string
 */
function surtilec_product_seo_word_boundary( $value, $length ) {
	if ( function_exists( 'mb_substr' ) ) {
		$cut = mb_substr( $value, 0, $length );
		$pos = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );
		return trim( false !== $pos && $pos > 10 ? mb_substr( $cut, 0, $pos ) : $cut );
	}
	$cut = substr( $value, 0, $length );
	$pos = strrpos( $cut, ' ' );
	return trim( false !== $pos && $pos > 10 ? substr( $cut, 0, $pos ) : $cut );
}

/**
 * @param string $description Existing product description.
 * @param string $name Product name fallback.
 * @return string
 */
function surtilec_product_seo_description( $description, $name ) {
	$description = trim( preg_replace( '/\s+/u', ' ', (string) $description ) );
	if ( '' === $description ) {
		$description = $name . '. Solicita disponibilidad y cotización en Surtilec para despacho en Colombia.';
	}
	$limit = 155;
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $description ) > $limit ) {
		return trim( mb_substr( $description, 0, $limit - 3 ) ) . '...';
	}
	if ( strlen( $description ) > $limit ) {
		return trim( substr( $description, 0, $limit - 3 ) ) . '...';
	}
	return $description;
}
