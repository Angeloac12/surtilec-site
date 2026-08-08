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
 * Missing-image products are no longer hidden from search.
 *
 * Two filters used to live here: one excluded image-less published products
 * from the XML product sitemap, the other excluded them from AIOSEO's LLM
 * files. Together with the AIOSEO noindex they hid 1,068 of 1,385 published
 * products — 77% of the catalog. A product page with an SKU, brand, attribute
 * spec table and the SEO/AEO Q&A copy is worth both crawling and answering
 * from, so the photo is no longer a gate. See
 * scripts/aioseo-release-image-gated-noindex.php for the matching data change.
 */

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
 * Meta description for product category archives that have no term description.
 *
 * 29 of the 43 product categories carry no description, including the largest:
 * Cable para bandeja (393 products), Cables apantallados (317) and Cable
 * encauchetado (99). AIOSEO's taxonomy template is `#taxonomy_description`, so
 * those archives reached Google with nothing under the title.
 *
 * Generated at render time rather than written into the terms, so the visible
 * category intro stays empty and obvious as work still to do, instead of being
 * quietly filled with copy nobody wrote. Uses only the category name, its
 * parent and how many products it holds — no technical claim is made that the
 * catalogue does not record.
 *
 * @param string $description Description resolved so far.
 * @return string
 */
function surtilec_product_cat_meta_description( $description ) {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return $description;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return $description;
	}

	$limit = 155;

	// A real term description always wins on substance; it only needs to be cut
	// to fit the SERP. Prefer a sentence boundary, then a word boundary, never
	// mid-word. The visible intro on the page keeps its full length.
	$existing = trim( html_entity_decode( wp_strip_all_tags( (string) $description ), ENT_QUOTES, 'UTF-8' ) );
	if ( '' !== $existing ) {
		if ( mb_strlen( $existing ) <= $limit ) {
			return $existing;
		}

		$window = mb_substr( $existing, 0, $limit );

		$stop = (int) mb_strrpos( $window, '. ' );
		if ( $stop > 80 ) {
			return rtrim( mb_substr( $window, 0, $stop + 1 ) );
		}

		$space = mb_strrpos( $window, ' ' );
		if ( false !== $space && $space > 80 ) {
			return rtrim( mb_substr( $window, 0, $space ), " ,;:-–" ) . '…';
		}

		return $existing;
	}
	$name  = trim( html_entity_decode( wp_strip_all_tags( $term->name ), ENT_QUOTES, 'UTF-8' ) );
	if ( '' === $name ) {
		return $description;
	}

	$parent = '';
	if ( $term->parent ) {
		$parent_term = get_term( $term->parent, 'product_cat' );
		if ( $parent_term instanceof WP_Term ) {
			$parent = trim( html_entity_decode( wp_strip_all_tags( $parent_term->name ), ENT_QUOTES, 'UTF-8' ) );
		}
	}

	// Skip the parent clause when one name contains the other, which reads as
	// "Cables apantallados en Cables apantallados".
	$parent_useful = '' !== $parent
		&& 0 !== strcasecmp( $parent, $name )
		&& false === mb_stripos( $parent, $name )
		&& false === mb_stripos( $name, $parent );

	$count = (int) $term->count;

	$candidates = array();

	if ( $parent_useful && $count > 0 ) {
		$candidates[] = sprintf(
			'%s para %s: %d referencias en catálogo. Consulta especificaciones y cotiza con despacho a toda Colombia desde Bogotá.',
			$name,
			$parent,
			$count
		);
	}
	if ( $count > 0 ) {
		$candidates[] = sprintf(
			'%s: %d referencias en catálogo. Consulta calibres, conductores y especificaciones, y cotiza con despacho a toda Colombia.',
			$name,
			$count
		);
		$candidates[] = sprintf(
			'%s: %d referencias. Consulta especificaciones y cotiza con despacho a toda Colombia desde Bogotá.',
			$name,
			$count
		);
	}
	if ( $parent_useful ) {
		$candidates[] = sprintf(
			'%s para %s. Consulta especificaciones y cotiza con despacho a toda Colombia desde Bogotá.',
			$name,
			$parent
		);
	}
	$candidates[] = sprintf(
		'%s. Consulta especificaciones y cotiza con despacho a toda Colombia desde Bogotá.',
		$name
	);
	$candidates[] = sprintf( '%s. Cotiza con despacho a toda Colombia desde Bogotá.', $name );

	foreach ( $candidates as $candidate ) {
		if ( mb_strlen( $candidate ) <= $limit ) {
			return $candidate;
		}
	}

	return $description;
}
add_filter( 'aioseo_description', 'surtilec_product_cat_meta_description', 20 );

/**
 * Determine whether a product has a real featured image attachment.
 *
 * Still used by the image-readiness reporting script.
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
