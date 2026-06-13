<?php
/**
 * Plugin Name: Surtilec — Schema (JSON-LD)
 * Description: Emits Organization + LocalBusiness (site-wide), Product (single, without price) and BreadcrumbList (product/category) JSON-LD, deduplicated against AIOSEO. FAQPage is emitted by the child theme on category pages.
 * Version:     0.1.0
 * Author:      Surtilec
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SURTILEC_SCHEMA_WA = '+573204499026';

/**
 * Canonical Surtilec entity sentence — single source of truth.
 *
 * Used verbatim by BOTH the Organization JSON-LD (below) and the homepage trust
 * band, so the two can never drift apart (required for AEO/entity consistency).
 * Defined in a mu-plugin so it is available to the theme as well.
 */
if ( ! function_exists( 'surtilec_entity_sentence' ) ) {
	function surtilec_entity_sentence() {
		return 'Surtilec — distribuidor colombiano de cables de control, THHN, cables para variadores (VFD), cables especiales y productos de automatización industrial (variadores de frecuencia, PLC, HMI). Despachos a toda Colombia desde Bogotá.';
	}
}

/**
 * Output the combined JSON-LD graph in the head.
 */
add_action( 'wp_head', 'surtilec_schema_output', 20 );
function surtilec_schema_output() {
	$graph = array(
		surtilec_schema_organization(),
		surtilec_schema_localbusiness(),
	);

	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = surtilec_schema_product();
		if ( $product ) {
			$graph[] = $product;
		}
		$crumbs = surtilec_schema_breadcrumb_product();
		if ( $crumbs ) {
			$graph[] = $crumbs;
		}
	} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$crumbs = surtilec_schema_breadcrumb_category();
		if ( $crumbs ) {
			$graph[] = $crumbs;
		}
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => array_values( array_filter( $graph ) ),
	);

	echo "\n" . '<script type="application/ld+json">'
		. wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
		. "</script>\n";
}

/**
 * Organization (site-wide).
 *
 * @return array
 */
function surtilec_schema_organization() {
	return array(
		'@type'       => 'Organization',
		'@id'         => home_url( '/#organization' ),
		'name'        => 'Surtilec',
		'url'         => home_url( '/' ),
		'description' => surtilec_entity_sentence(),
		'sameAs'      => array(),
	);
}

/**
 * LocalBusiness (site-wide), city-level only — no street address invented.
 *
 * @return array
 */
function surtilec_schema_localbusiness() {
	return array(
		'@type'      => 'LocalBusiness',
		'@id'        => home_url( '/#localbusiness' ),
		'name'       => 'Surtilec',
		'url'        => home_url( '/' ),
		'telephone'  => SURTILEC_SCHEMA_WA,
		'areaServed' => array(
			'@type' => 'Country',
			'name'  => 'Colombia',
		),
		'address'    => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => 'Bogotá',
			'addressCountry'  => 'CO',
		),
	);
}

/**
 * Product (single product) — WITHOUT offers.
 *
 * schema.org Offer requires a price/priceSpecification and Google flags a
 * priceless Offer as invalid; this is a quote-only catalog, so we emit a valid
 * Product with no `offers` node.
 *
 * @return array|null
 */
function surtilec_schema_product() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return null;
	}

	$schema = array(
		'@type' => 'Product',
		'@id'   => get_permalink( $product->get_id() ) . '#product',
		'name'  => $product->get_name(),
		'url'   => get_permalink( $product->get_id() ),
	);

	$sku = $product->get_sku();
	if ( $sku ) {
		$schema['sku'] = $sku;
	}

	$description = wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() );
	if ( $description ) {
		$schema['description'] = $description;
	}

	$brands = wc_get_product_terms( $product->get_id(), 'pa_marca', array( 'fields' => 'names' ) );
	if ( ! empty( $brands ) ) {
		$schema['brand'] = array(
			'@type' => 'Brand',
			'name'  => $brands[0],
		);
	}

	$image = wp_get_attachment_url( $product->get_image_id() );
	if ( $image ) {
		$schema['image'] = $image;
	}

	return $schema;
}

/**
 * Build a BreadcrumbList from a list of [name, url] pairs (Inicio first).
 *
 * @param array<int,array{0:string,1:string}> $trail Crumb pairs.
 * @return array|null
 */
function surtilec_schema_breadcrumb( $trail ) {
	if ( empty( $trail ) ) {
		return null;
	}
	$items    = array();
	$position = 1;
	foreach ( $trail as $crumb ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => $crumb[0],
			'item'     => $crumb[1],
		);
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

/**
 * Category-term ancestors as crumb pairs (does not include Inicio).
 *
 * @param int $term_id Product category term id.
 * @return array<int,array{0:string,1:string}>
 */
function surtilec_schema_term_trail( $term_id ) {
	$trail     = array();
	$ancestors = array_reverse( get_ancestors( $term_id, 'product_cat' ) );
	foreach ( $ancestors as $ancestor_id ) {
		$term = get_term( $ancestor_id, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$trail[] = array( $term->name, get_term_link( $term ) );
		}
	}
	return $trail;
}

/**
 * BreadcrumbList for a single product.
 *
 * @return array|null
 */
function surtilec_schema_breadcrumb_product() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return null;
	}
	$trail = array( array( 'Inicio', home_url( '/' ) ) );

	$cat_ids = $product->get_category_ids();
	if ( ! empty( $cat_ids ) ) {
		$primary = (int) $cat_ids[0];
		$trail   = array_merge( $trail, surtilec_schema_term_trail( $primary ) );
		$term    = get_term( $primary, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$trail[] = array( $term->name, get_term_link( $term ) );
		}
	}

	$trail[] = array( $product->get_name(), get_permalink( $product->get_id() ) );
	return surtilec_schema_breadcrumb( $trail );
}

/**
 * BreadcrumbList for a product category archive.
 *
 * @return array|null
 */
function surtilec_schema_breadcrumb_category() {
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return null;
	}
	$trail   = array( array( 'Inicio', home_url( '/' ) ) );
	$trail   = array_merge( $trail, surtilec_schema_term_trail( $term->term_id ) );
	$trail[] = array( $term->name, get_term_link( $term ) );
	return surtilec_schema_breadcrumb( $trail );
}

/**
 * Dedup: strip AIOSEO's Organization + BreadcrumbList nodes (we emit our own).
 * Keeps AIOSEO's WebSite / WebPage (ItemPage/CollectionPage) nodes.
 */
add_filter(
	'aioseo_schema_output',
	function ( $graph ) {
		if ( ! is_array( $graph ) ) {
			return $graph;
		}
		return array_values(
			array_filter(
				$graph,
				function ( $node ) {
					$types = (array) ( isset( $node['@type'] ) ? $node['@type'] : '' );
					return ! array_intersect( $types, array( 'Organization', 'BreadcrumbList' ) );
				}
			)
		);
	}
);
