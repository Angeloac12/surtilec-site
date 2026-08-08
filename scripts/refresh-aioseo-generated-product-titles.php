<?php
/**
 * Refresh only product SEO titles that match the previous generated format.
 * Manual titles are left untouched.
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

$updated = 0;
$checked = 0;
$now     = current_time( 'mysql', true );

foreach ( $posts as $post ) {
	$name = trim( wp_strip_all_tags( $post->post_title ) );
	$row  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE post_id = %d LIMIT 1", $post->ID ), ARRAY_A );
	if ( ! $row ) {
		continue;
	}

	++$checked;
	$old_title = surtilec_refresh_old_product_seo_title( $name );
	$new_title = surtilec_refresh_new_product_seo_title( $name );
	if ( $old_title === $new_title ) {
		continue;
	}

	$updates = array();
	foreach ( array( 'title', 'og_title', 'twitter_title' ) as $field ) {
		if ( trim( (string) $row[ $field ] ) === $old_title ) {
			$updates[ $field ] = $new_title;
		}
	}
	if ( $updates ) {
		$updates['updated'] = $now;
		$wpdb->update( $table, $updates, array( 'id' => (int) $row['id'] ) );
		++$updated;
	}
}

wp_cache_flush();
WP_CLI::log( "checked: $checked" );
WP_CLI::log( "updated: $updated" );
WP_CLI::success( 'Títulos SEO generados actualizados por límite de palabra; títulos manuales intactos.' );

function surtilec_refresh_old_product_seo_title( $name ) {
	$brand = ' | Surtilec';
	$limit = 60 - strlen( $brand );
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $name ) > $limit ) {
		return trim( mb_substr( $name, 0, $limit - 3 ) ) . '...' . $brand;
	}
	if ( strlen( $name ) > $limit ) {
		return trim( substr( $name, 0, $limit - 3 ) ) . '...' . $brand;
	}
	return $name . $brand;
}

function surtilec_refresh_new_product_seo_title( $name ) {
	$brand = ' | Surtilec';
	$limit = 60 - strlen( $brand );
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $name ) > $limit ) {
		return surtilec_refresh_word_boundary( $name, $limit - 3 ) . '...' . $brand;
	}
	if ( strlen( $name ) > $limit ) {
		return surtilec_refresh_word_boundary( $name, $limit - 3 ) . '...' . $brand;
	}
	return $name . $brand;
}

function surtilec_refresh_word_boundary( $value, $length ) {
	if ( function_exists( 'mb_substr' ) ) {
		$cut = mb_substr( $value, 0, $length );
		$pos = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );
		return trim( false !== $pos && $pos > 10 ? mb_substr( $cut, 0, $pos ) : $cut );
	}
	$cut = substr( $value, 0, $length );
	$pos = strrpos( $cut, ' ' );
	return trim( false !== $pos && $pos > 10 ? substr( $cut, 0, $pos ) : $cut );
}
