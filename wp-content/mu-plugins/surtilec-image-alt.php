<?php
/**
 * Plugin Name: Surtilec — Image Alt Text
 * Description: Generates safe alt text for product images when the alt is empty.
 * Version:     0.1.0
 * Author:      Surtilec
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clean text for use in attachment alt fields.
 *
 * @param string $text Raw text.
 * @return string
 */
function surtilec_image_alt_clean_text( $text ) {
	$charset = get_bloginfo( 'charset' ) ? get_bloginfo( 'charset' ) : 'UTF-8';
	$text    = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, $charset );
	$text    = preg_replace( '/\s+/', ' ', $text );

	return trim( (string) $text );
}

/**
 * Case-insensitive contains helper with accent folding.
 *
 * @param string $haystack Full text.
 * @param string $needle   Text to find.
 * @return bool
 */
function surtilec_image_alt_contains( $haystack, $needle ) {
	$haystack = remove_accents( strtolower( surtilec_image_alt_clean_text( $haystack ) ) );
	$needle   = remove_accents( strtolower( surtilec_image_alt_clean_text( $needle ) ) );

	return '' !== $needle && false !== strpos( $haystack, $needle );
}

/**
 * Detect empty or obviously generic alt text.
 *
 * @param string $alt Attachment alt.
 * @return bool
 */
function surtilec_image_alt_is_generic( $alt ) {
	$alt = remove_accents( strtolower( surtilec_image_alt_clean_text( $alt ) ) );
	$alt = preg_replace( '/[^a-z0-9]+/', ' ', $alt );
	$alt = trim( (string) $alt );

	return in_array(
		$alt,
		array(
			'',
			'image',
			'imagen',
			'photo',
			'foto',
			'product',
			'producto',
			'cable',
			'surtilec',
			'placeholder',
			'woocommerce placeholder',
		),
		true
	);
}

/**
 * Generate alt text from real WooCommerce product data.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function surtilec_product_featured_image_alt_text( $product_id ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}

	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return '';
	}

	$name = surtilec_image_alt_clean_text( $product->get_name() );
	if ( '' === $name ) {
		return '';
	}

	$alt    = $name;
	$brands = wc_get_product_terms( $product_id, 'pa_marca', array( 'fields' => 'names' ) );
	$brand  = ( ! is_wp_error( $brands ) && ! empty( $brands ) ) ? surtilec_image_alt_clean_text( $brands[0] ) : '';
	$sku    = surtilec_image_alt_clean_text( $product->get_sku() );

	if ( '' !== $brand && ! surtilec_image_alt_contains( $alt, $brand ) ) {
		$alt .= ' marca ' . $brand;
	}

	if ( '' !== $sku ) {
		$alt .= ', referencia ' . $sku;
	}

	return surtilec_image_alt_clean_text( $alt . ' - Surtilec' );
}

/**
 * Resolve safe alt text for known attachment contexts.
 *
 * @param int $attachment_id Attachment ID.
 * @return string
 */
function surtilec_attachment_contextual_alt_text( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		return '';
	}

	$custom_logo = (int) get_theme_mod( 'custom_logo' );
	if ( $custom_logo === $attachment_id ) {
		return 'Logo de Surtilec';
	}

	$title = surtilec_image_alt_clean_text( get_the_title( $attachment_id ) );
	$file  = surtilec_image_alt_clean_text( wp_basename( (string) get_attached_file( $attachment_id ) ) );
	if ( surtilec_image_alt_contains( $title . ' ' . $file, 'woocommerce-placeholder' ) ) {
		return 'Imagen de producto no disponible - Surtilec';
	}

	global $wpdb;

	$product_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} thumb
				ON thumb.post_id = p.ID
				AND thumb.meta_key = '_thumbnail_id'
				AND CAST(thumb.meta_value AS UNSIGNED) = %d
			WHERE p.post_type = 'product'
				AND p.post_status NOT IN ('trash', 'auto-draft')
			ORDER BY FIELD(p.post_status, 'publish', 'draft', 'pending', 'private'), p.ID DESC
			LIMIT 1",
			$attachment_id
		)
	);

	if ( $product_id ) {
		return surtilec_product_featured_image_alt_text( $product_id );
	}

	$term_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT tm.term_id
			FROM {$wpdb->termmeta} tm
			INNER JOIN {$wpdb->term_taxonomy} tt
				ON tt.term_id = tm.term_id
				AND tt.taxonomy = 'product_cat'
			WHERE tm.meta_key = 'thumbnail_id'
				AND CAST(tm.meta_value AS UNSIGNED) = %d
			LIMIT 1",
			$attachment_id
		)
	);

	if ( $term_id ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			return 'Categoría ' . surtilec_image_alt_clean_text( $term->name ) . ' en Surtilec';
		}
	}

	if ( surtilec_image_alt_contains( $title . ' ' . $file, 'logo' ) ) {
		return 'Logo de Surtilec';
	}

	return '';
}

/**
 * Fill product featured-image alt when a thumbnail is assigned in WP Admin/imports.
 *
 * @param int    $meta_id    Meta ID.
 * @param int    $object_id  Post ID.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Meta value.
 */
function surtilec_maybe_fill_product_featured_image_alt( $meta_id, $object_id, $meta_key, $meta_value ) {
	unset( $meta_id );

	if ( '_thumbnail_id' !== $meta_key || 'product' !== get_post_type( $object_id ) ) {
		return;
	}

	$attachment_id = (int) $meta_value;
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		return;
	}

	$current_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	if ( '' !== trim( (string) $current_alt ) ) {
		return;
	}

	$alt = surtilec_product_featured_image_alt_text( (int) $object_id );
	if ( '' !== $alt ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	}
}

add_action( 'added_post_meta', 'surtilec_maybe_fill_product_featured_image_alt', 10, 4 );
add_action( 'updated_post_meta', 'surtilec_maybe_fill_product_featured_image_alt', 10, 4 );
