<?php
/**
 * Plugin Name: Surtilec — Search (suggest + relevance)
 * Description: Custom weighted product search. Exposes a REST suggest endpoint for the header autocomplete and upgrades the WP search results page (SKU + relevance ranking, trimmed query). No external search plugin.
 * Version:     0.2.0
 * Author:      Surtilec
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SURTILEC_SEARCH_NS      = 'surtilec/v1';
const SURTILEC_SEARCH_MIN     = 2;  // Min query length before we match.
const SURTILEC_SEARCH_MAX_LEN = 60; // Hard cap on query length.

/* =============================================================
   Shared helpers
   ============================================================= */

/**
 * Normalise a raw query into lowercase tokens.
 *
 * Punctuation is treated as a separator and dropped, so noise like "#" in
 * "cable # 12" does not become a token that can never match. Single-character
 * tokens are dropped unless numeric (gauge numbers like "2" stay).
 *
 * @param string $q Raw query.
 * @return string[] Tokens (may be empty).
 */
function surtilec_search_tokens( $q ) {
	$q = trim( wp_strip_all_tags( (string) $q ) );
	$q = mb_substr( $q, 0, SURTILEC_SEARCH_MAX_LEN );
	if ( '' === $q ) {
		return array();
	}
	// Non-alphanumeric (unicode-aware) becomes a separator.
	$q     = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', mb_strtolower( $q ) );
	$parts = preg_split( '/\s+/u', (string) $q, -1, PREG_SPLIT_NO_EMPTY );

	$tokens = array();
	foreach ( (array) $parts as $p ) {
		if ( ctype_digit( $p ) || mb_strlen( $p ) >= 2 ) {
			$tokens[] = $p;
		}
	}
	return $tokens;
}

/**
 * The query as a single normalised phrase (punctuation collapsed to spaces),
 * for full-phrase relevance bonuses.
 *
 * @param string $q Raw query.
 * @return string
 */
function surtilec_search_phrase( $q ) {
	$q = mb_strtolower( trim( wp_strip_all_tags( (string) $q ) ) );
	$q = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', $q );
	return trim( preg_replace( '/\s+/u', ' ', (string) $q ) );
}

/* =============================================================
   Matcher — used by the REST suggest endpoint
   ============================================================= */

/**
 * "Most similar" product match against title + SKU.
 *
 * A row qualifies if it matches AT LEAST ONE token (OR), then rows are ranked by
 * a relevance score: per-token credit + a bonus when ALL tokens match (so precise
 * multi-word hits stay on top) + full-phrase / prefix / exact / SKU bonuses. This
 * always surfaces the closest products instead of an all-or-nothing empty result.
 * Accent-insensitivity comes from the DB collation (utf8mb4_*_ci): "optica"
 * matches "óptica".
 *
 * @param string $q     Raw query.
 * @param int    $limit Max rows.
 * @return array<int,object> Rows with ID, post_title, sku, score.
 */
function surtilec_search_products( $q, $limit = 6 ) {
	global $wpdb;

	$tokens = surtilec_search_tokens( $q );
	if ( empty( $tokens ) ) {
		return array();
	}

	$phrase      = surtilec_search_phrase( $q );
	$like_full   = '%' . $wpdb->esc_like( $phrase ) . '%';
	$prefix_full = $wpdb->esc_like( $phrase ) . '%';

	// Build the score (per-token credit), the OR filter, and the all-tokens
	// bonus together so the prepare() placeholders stay in textual order.
	$score_terms  = array();
	$score_params = array();
	$or           = array();
	$or_params    = array();
	$all          = array();

	foreach ( $tokens as $t ) {
		$like = '%' . $wpdb->esc_like( $t ) . '%';

		$score_terms[]  = 'CASE WHEN p.post_title LIKE %s THEN 12 ELSE 0 END';
		$score_params[] = $like;
		$score_terms[]  = 'CASE WHEN su_sku.meta_value LIKE %s THEN 14 ELSE 0 END';
		$score_params[] = $like;

		$or[]        = '( p.post_title LIKE %s OR su_sku.meta_value LIKE %s )';
		$or_params[] = $like;
		$or_params[] = $like;

		$all[] = '( p.post_title LIKE %s OR su_sku.meta_value LIKE %s )';
	}

	// Full-phrase bonuses (after the per-token terms, before the all-tokens CASE).
	$score_terms[]  = 'CASE WHEN LOWER(p.post_title) = %s THEN 100 ELSE 0 END';
	$score_params[] = $phrase;
	$score_terms[]  = 'CASE WHEN LOWER(p.post_title) LIKE %s THEN 50 ELSE 0 END';
	$score_params[] = $prefix_full;
	$score_terms[]  = 'CASE WHEN LOWER(p.post_title) LIKE %s THEN 25 ELSE 0 END';
	$score_params[] = $like_full;
	$score_terms[]  = 'CASE WHEN su_sku.meta_value LIKE %s THEN 45 ELSE 0 END';
	$score_params[] = $like_full;

	// All-tokens-present bonus (placeholders appended in textual order).
	$score_terms[] = 'CASE WHEN ( ' . implode( ' AND ', $all ) . ' ) THEN 40 ELSE 0 END';
	foreach ( $tokens as $t ) {
		$like           = '%' . $wpdb->esc_like( $t ) . '%';
		$score_params[] = $like;
		$score_params[] = $like;
	}

	$score = '( ' . implode( ' + ', $score_terms ) . ' )';

	// Exclude products flagged exclude-from-search (WooCommerce visibility).
	$exclude = "p.ID NOT IN (
		SELECT tr.object_id FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		INNER JOIN {$wpdb->terms} te ON te.term_id = tt.term_id
		WHERE tt.taxonomy = 'product_visibility' AND te.slug = 'exclude-from-search'
	)";

	$sql = "SELECT p.ID, p.post_title, su_sku.meta_value AS sku, {$score} AS score
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->postmeta} su_sku ON ( su_sku.post_id = p.ID AND su_sku.meta_key = '_sku' )
		WHERE p.post_type = 'product' AND p.post_status = 'publish'
		AND ( " . implode( ' OR ', $or ) . " )
		AND {$exclude}
		ORDER BY score DESC, p.post_title ASC
		LIMIT %d";

	$params = array_merge( $score_params, $or_params, array( (int) $limit ) );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built above, values via prepare().
	return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
}

/**
 * Matching product categories (name/slug LIKE), for the dropdown shortcut.
 *
 * @param string $q     Raw query.
 * @param int    $limit Max terms.
 * @return array<int,array> List of { name, url, count }.
 */
function surtilec_search_categories( $q, $limit = 2 ) {
	$q = trim( wp_strip_all_tags( (string) $q ) );
	if ( mb_strlen( $q ) < SURTILEC_SEARCH_MIN ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => (int) $limit,
			'search'     => $q,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$out = array();
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$out[] = array(
			'name'  => $term->name,
			'url'   => $link,
			'count' => (int) $term->count,
		);
	}
	return $out;
}

/* =============================================================
   REST suggest endpoint
   ============================================================= */

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			SURTILEC_SEARCH_NS,
			'/suggest',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'callback'            => 'surtilec_search_suggest_rest',
			)
		);
	}
);

/**
 * Suggest endpoint: returns up to 4 products + up to 2 category shortcuts.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function surtilec_search_suggest_rest( $request ) {
	$q     = trim( (string) $request->get_param( 'q' ) );
	$empty = array(
		'q'          => $q,
		'products'   => array(),
		'categories' => array(),
	);

	if ( mb_strlen( $q ) < SURTILEC_SEARCH_MIN ) {
		return rest_ensure_response( $empty );
	}

	$cache_key = 'su_sg_' . md5( mb_strtolower( $q ) );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return rest_ensure_response( $cached );
	}

	$products = array();
	foreach ( surtilec_search_products( $q, 6 ) as $row ) {
		$cats      = get_the_terms( $row->ID, 'product_cat' );
		$cat_name  = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';
		$thumb     = get_the_post_thumbnail_url( $row->ID, 'woocommerce_gallery_thumbnail' );
		$products[] = array(
			'title' => $row->post_title,
			'url'   => get_permalink( $row->ID ),
			'sku'   => (string) $row->sku,
			'cat'   => $cat_name,
			'thumb' => $thumb ? $thumb : '',
		);
	}

	$data = array(
		'q'          => $q,
		'products'   => array_slice( $products, 0, 4 ),
		'categories' => surtilec_search_categories( $q, 2 ),
	);

	set_transient( $cache_key, $data, 10 * MINUTE_IN_SECONDS );
	return rest_ensure_response( $data );
}

/* =============================================================
   Results page — SKU + relevance on the main product search query
   ============================================================= */

add_action(
	'pre_get_posts',
	function ( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
			return;
		}
		if ( 'product' !== $query->get( 'post_type' ) ) {
			return;
		}
		// Trim the search term (default WP keeps the trailing space).
		$query->set( 's', trim( (string) $query->get( 's' ) ) );
		// Flag for the clause filters below.
		$query->set( 'surtilec_psearch', true );
	}
);

/**
 * Replace the default search WHERE with title-OR-SKU per token (precise).
 */
add_filter(
	'posts_search',
	function ( $search, $query ) {
		if ( ! $query->get( 'surtilec_psearch' ) ) {
			return $search;
		}
		global $wpdb;
		$tokens = surtilec_search_tokens( $query->get( 's' ) );
		if ( empty( $tokens ) ) {
			return $search;
		}
		$clauses = array();
		foreach ( $tokens as $t ) {
			$like      = '%' . $wpdb->esc_like( $t ) . '%';
			$clauses[] = $wpdb->prepare( "( {$wpdb->posts}.post_title LIKE %s OR su_sku.meta_value LIKE %s )", $like, $like );
		}
		return ' AND ' . implode( ' AND ', $clauses );
	},
	10,
	2
);

/**
 * Join the SKU meta for the search + ordering.
 */
add_filter(
	'posts_join',
	function ( $join, $query ) {
		if ( ! $query->get( 'surtilec_psearch' ) ) {
			return $join;
		}
		global $wpdb;
		return $join . " LEFT JOIN {$wpdb->postmeta} su_sku ON ( su_sku.post_id = {$wpdb->posts}.ID AND su_sku.meta_key = '_sku' ) ";
	},
	10,
	2
);

/**
 * Order results by the same relevance score as the suggest endpoint.
 *
 * No GROUP BY is added: the _sku join is 1:1, and grouping would collide with
 * ORDER BY su_sku.meta_value under MySQL ONLY_FULL_GROUP_BY.
 */
add_filter(
	'posts_orderby',
	function ( $orderby, $query ) {
		if ( ! $query->get( 'surtilec_psearch' ) ) {
			return $orderby;
		}
		global $wpdb;
		$full      = mb_strtolower( trim( (string) $query->get( 's' ) ) );
		if ( '' === $full ) {
			return $orderby;
		}
		$like_full = '%' . $wpdb->esc_like( $full ) . '%';
		$prefix    = $wpdb->esc_like( $full ) . '%';
		$score     = $wpdb->prepare(
			"( CASE WHEN LOWER({$wpdb->posts}.post_title) = %s THEN 100 ELSE 0 END"
			. " + CASE WHEN LOWER({$wpdb->posts}.post_title) LIKE %s THEN 50 ELSE 0 END"
			. " + CASE WHEN LOWER({$wpdb->posts}.post_title) LIKE %s THEN 20 ELSE 0 END"
			. " + CASE WHEN su_sku.meta_value LIKE %s THEN 40 ELSE 0 END )",
			$full,
			$prefix,
			$like_full,
			$like_full
		);
		return "{$score} DESC, {$wpdb->posts}.post_title ASC";
	},
	10,
	2
);
