<?php
/**
 * Plugin Name: Surtilec — SEO Integrations
 * Description: Small SEO glue for launch readiness, including AIOSEO sitemap term dates, incomplete product exclusions and Colombian locale signals.
 * Version:     0.3.0
 * Author:      Surtilec
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare the document language as Colombian Spanish.
 *
 * The WordPress locale stays es_ES so every plugin keeps its Spanish
 * translation (there is no complete es_CO pack for WooCommerce/AIOSEO, and
 * switching would silently drop strings back to English). Only the public
 * language signal changes, which is the part search engines read for
 * geo-targeting.
 *
 * @param string $output Existing language attributes.
 * @return string
 */
add_filter(
	'language_attributes',
	function ( $output ) {
		return preg_replace( '/lang="[^"]*"/', 'lang="es-CO"', (string) $output, 1 );
	},
	20
);

/**
 * AIOSEO can emit 1970-01-01 for taxonomy sitemap lastmod values when the term
 * itself has no useful modified timestamp. Use the newest published object in
 * the term tree instead: products for product_cat, posts for blog category.
 *
 * AIOSEO documents the first 3 args; recent versions pass a 4th context arg.
 *
 * @param array  $entry    Sitemap entry data.
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 * @param string $type     Optional AIOSEO context.
 * @return array
 */
function surtilec_aioseo_sitemap_term_lastmod( $entry, $term_id, $taxonomy, $type = '' ) {
	unset( $type );

	if ( ! is_array( $entry ) || ! in_array( $taxonomy, array( 'category', 'product_cat' ), true ) ) {
		return $entry;
	}

	$lastmod = surtilec_aioseo_term_lastmod_from_content( (int) $term_id, $taxonomy );
	if ( $lastmod ) {
		$entry['lastmod'] = $lastmod;
	}

	return $entry;
}
add_filter( 'aioseo_sitemap_term', 'surtilec_aioseo_sitemap_term_lastmod', 10, 4 );

/**
 * Keep products that are still missing a real featured image out of AIOSEO XML
 * product sitemaps. They can remain public for quoting, but should not be fed
 * to search engines until the image batch is complete.
 *
 * @param array  $ids  Post IDs already excluded by AIOSEO.
 * @param string $type Sitemap type/post type.
 * @return array
 */
function surtilec_aioseo_exclude_products_without_images_from_sitemap( $ids, $type = '' ) {
	if ( ! is_array( $ids ) ) {
		$ids = array();
	}

	if ( 0 !== strpos( (string) $type, 'product' ) ) {
		return $ids;
	}

	$missing_image_ids = get_transient( 'surtilec_no_image_product_ids' );
	if ( ! is_array( $missing_image_ids ) ) {
		$missing_image_ids = surtilec_get_published_product_ids_without_featured_image();
		set_transient( 'surtilec_no_image_product_ids', $missing_image_ids, 15 * MINUTE_IN_SECONDS );
	}

	return array_values( array_unique( array_merge( array_map( 'intval', $ids ), array_map( 'intval', $missing_image_ids ) ) ) );
}
add_filter( 'aioseo_sitemap_exclude_posts', 'surtilec_aioseo_exclude_products_without_images_from_sitemap', 10, 2 );

/**
 * Exclude incomplete products from AIOSEO LLM files as well. AIOSEO excludes a
 * post from llms.txt/llms-full.txt when its title filter returns an empty value.
 *
 * @param string  $title Current LLM title.
 * @param WP_Post $post  Current post object.
 * @return string
 */
function surtilec_aioseo_llms_exclude_products_without_images( $title, $post ) {
	if ( $post instanceof WP_Post && 'product' === $post->post_type && ! surtilec_product_has_real_featured_image( $post->ID ) ) {
		return '';
	}

	return $title;
}
add_filter( 'aioseo_llms_post_title', 'surtilec_aioseo_llms_exclude_products_without_images', 10, 2 );

/**
 * Resolve a term sitemap lastmod from the newest published content assigned to
 * the term or any descendant term.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy Taxonomy name.
 * @return string ISO-8601 UTC timestamp or empty string.
 */
function surtilec_aioseo_term_lastmod_from_content( $term_id, $taxonomy ) {
	static $cache = array();

	$term_id = (int) $term_id;
	$key     = $taxonomy . ':' . $term_id;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$post_type = 'product_cat' === $taxonomy ? 'product' : 'post';
	$term_ids  = array( $term_id );
	$children  = get_term_children( $term_id, $taxonomy );
	if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
		$term_ids = array_merge( $term_ids, array_map( 'intval', $children ) );
	}
	$term_ids = array_values( array_unique( array_filter( array_map( 'intval', $term_ids ) ) ) );

	if ( empty( $term_ids ) ) {
		$cache[ $key ] = '';
		return '';
	}

	global $wpdb;

	$placeholders = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );
	$sql          = $wpdb->prepare(
		"SELECT MAX(
			CASE
				WHEN p.post_modified_gmt <> '0000-00-00 00:00:00'
					AND p.post_modified_gmt >= p.post_date_gmt
					THEN p.post_modified_gmt
				WHEN p.post_date_gmt <> '0000-00-00 00:00:00'
					THEN p.post_date_gmt
				ELSE p.post_modified
			END
		)
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr
			ON tr.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} tt
			ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE p.post_status = 'publish'
			AND p.post_type = %s
			AND tt.taxonomy = %s
			AND tt.term_id IN ($placeholders)",
		array_merge( array( $post_type, $taxonomy ), $term_ids )
	);

	$mysql_date = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$timestamp  = $mysql_date ? strtotime( $mysql_date . ' UTC' ) : false;

	$cache[ $key ] = $timestamp ? gmdate( 'Y-m-d\TH:i:s+00:00', $timestamp ) : '';
	return $cache[ $key ];
}

/**
 * Get published products that lack a valid featured image attachment.
 *
 * @return int[]
 */
function surtilec_get_published_product_ids_without_featured_image() {
	global $wpdb;

	$sql = "
		SELECT p.ID
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} thumb
			ON thumb.post_id = p.ID
			AND thumb.meta_key = '_thumbnail_id'
		LEFT JOIN {$wpdb->posts} attachment
			ON attachment.ID = CAST(thumb.meta_value AS UNSIGNED)
			AND attachment.post_type = 'attachment'
			AND attachment.post_status <> 'trash'
		WHERE p.post_type = 'product'
			AND p.post_status = 'publish'
			AND (
				thumb.meta_id IS NULL
				OR CAST(thumb.meta_value AS UNSIGNED) = 0
				OR attachment.ID IS NULL
			)
	";

	return array_map( 'intval', $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Determine whether a product has a real featured image attachment.
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function surtilec_product_has_real_featured_image( $product_id ) {
	$thumbnail_id = (int) get_post_thumbnail_id( $product_id );

	return $thumbnail_id > 0
		&& 'attachment' === get_post_type( $thumbnail_id )
		&& 'trash' !== get_post_status( $thumbnail_id );
}
