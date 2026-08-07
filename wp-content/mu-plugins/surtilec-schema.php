<?php
/**
 * Plugin Name: Surtilec — Schema (JSON-LD)
 * Description: Emits the Surtilec entity (Organization + LocalBusiness in one node), Product with machine-readable specs (no price), FAQPage from the product Q&A copy, BreadcrumbList (product/category) and AboutPage (Nosotros) JSON-LD, deduplicated against AIOSEO. FAQPage is also emitted by the child theme on category pages and articles.
 * Version:     0.3.0
 * Author:      Surtilec
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SURTILEC_SCHEMA_WA      = '+573219932050';
const SURTILEC_LEGAL_NAME     = 'Grupo Gerson S.A.S.';
const SURTILEC_TAX_ID         = '901526407';
const SURTILEC_STREET_ADDRESS = 'Carrera 12 # 17-99';
const SURTILEC_CITY           = 'Bogotá';
const SURTILEC_REGION         = 'Bogotá D.C.';
const SURTILEC_COUNTRY_CODE   = 'CO';
const SURTILEC_COUNTRY_NAME   = 'Colombia';
const SURTILEC_LOCALE         = 'es-CO';

/**
 * Canonical brand assets, shipped with this mu-plugin so they deploy with it.
 *
 * Google will not accept an SVG for Organization.logo and no social platform
 * renders one in a share card, so both are rasters.
 */
function surtilec_logo_url() {
	return content_url( 'mu-plugins/assets/brand/surtilec-logo-512.png' );
}
function surtilec_og_default_url() {
	return content_url( 'mu-plugins/assets/brand/surtilec-og-default.png' );
}

/**
 * Canonical URL of the current request, matching what AIOSEO uses to mint its
 * node @ids. Anything building a node that AIOSEO references must agree with
 * this or the graph link dangles.
 *
 * @return string Trailing-slashed URL, or '' when it cannot be resolved.
 */
function surtilec_canonical_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		$url = get_permalink( get_queried_object_id() );
		return $url ? trailingslashit( $url ) : '';
	}
	if ( is_category() || is_tax() || is_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$url = get_term_link( $term );
			return is_wp_error( $url ) ? '' : trailingslashit( $url );
		}
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$shop_id = wc_get_page_id( 'shop' );
		$url     = $shop_id ? get_permalink( $shop_id ) : '';
		return $url ? trailingslashit( $url ) : '';
	}
	return '';
}

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
 * Resolve the current WooCommerce product early enough for wp_head.
 *
 * The global `$product` is not always populated when our JSON-LD runs, so using
 * the queried object keeps Product/Breadcrumb schema reliable on single-product
 * pages without depending on WooCommerce template timing.
 *
 * @return WC_Product|null
 */
function surtilec_schema_current_product() {
	if ( ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'wc_get_product' ) ) {
		return null;
	}

	global $product;
	if ( $product instanceof WC_Product ) {
		return $product;
	}

	$product_id = get_queried_object_id();
	if ( ! $product_id ) {
		return null;
	}

	$resolved = wc_get_product( $product_id );
	return ( $resolved instanceof WC_Product ) ? $resolved : null;
}

/**
 * Output the combined JSON-LD graph in the head.
 */
add_action( 'wp_head', 'surtilec_schema_output', 20 );
function surtilec_schema_output() {
	$graph = array( surtilec_schema_organization() );

	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = surtilec_schema_product();
		if ( $product ) {
			$graph[] = $product;
		}
		$faq = surtilec_schema_product_faq();
		if ( $faq ) {
			$graph[] = $faq;
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
	} elseif ( is_page( 'nosotros' ) ) {
		$graph[] = surtilec_schema_aboutpage();
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
 * The Surtilec entity (site-wide).
 *
 * Organization and LocalBusiness describe the SAME company, so they are one
 * node with a compound @type rather than two unlinked nodes competing to be
 * the site's publisher. The @id stays `#organization` because AIOSEO's WebSite
 * node, our AboutPage and the article Article schema all reference it.
 *
 * @return array
 */
function surtilec_schema_organization() {
	return array(
		'@type'        => array( 'Organization', 'LocalBusiness' ),
		'@id'          => home_url( '/#organization' ),
		'name'         => 'Surtilec',
		'legalName'    => SURTILEC_LEGAL_NAME,
		'taxID'        => SURTILEC_TAX_ID,
		'url'          => home_url( '/' ),
		'telephone'    => SURTILEC_SCHEMA_WA,
		'description'  => surtilec_entity_sentence(),
		'address'      => surtilec_schema_postal_address(),
		'areaServed'   => array(
			'@type' => 'Country',
			'name'  => SURTILEC_COUNTRY_NAME,
		),
		'logo'         => array(
			'@type'  => 'ImageObject',
			'@id'    => home_url( '/#logo' ),
			'url'    => surtilec_logo_url(),
			'width'  => 512,
			'height' => 512,
			'caption' => 'Surtilec',
		),
		'image'        => array( '@id' => home_url( '/#logo' ) ),
		'contactPoint' => array(
			array(
				'@type'             => 'ContactPoint',
				'contactType'       => 'sales',
				'telephone'         => SURTILEC_SCHEMA_WA,
				'areaServed'        => SURTILEC_COUNTRY_CODE,
				'availableLanguage' => array( 'Spanish' ),
			),
		),
		'knowsAbout'   => array(
			'Cables de control',
			'Cables THHN/THWN-2',
			'Cables para variadores de frecuencia (VFD)',
			'Cables de instrumentación',
			'Cables apantallados',
			'Cable para bandeja (tray cable)',
			'Automatización industrial',
			'Variadores de frecuencia',
			'PLC',
			'HMI',
		),
		'sameAs'       => array(),
	);
}

/**
 * Postal address from confirmed business data.
 *
 * @return array
 */
function surtilec_schema_postal_address() {
	return array(
		'@type'           => 'PostalAddress',
		'streetAddress'   => SURTILEC_STREET_ADDRESS,
		'addressLocality' => SURTILEC_CITY,
		'addressRegion'   => SURTILEC_REGION,
		'addressCountry'  => SURTILEC_COUNTRY_CODE,
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
	$product = surtilec_schema_current_product();
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
		$schema['mpn'] = $sku; // The SKU is the manufacturer reference in this catalog.
	}

	$description = wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() );
	if ( $description ) {
		$schema['description'] = $description;
	}

	$brands = wc_get_product_terms( $product->get_id(), 'pa_marca', array( 'fields' => 'names' ) );
	if ( ! empty( $brands ) ) {
		$schema['brand']        = array(
			'@type' => 'Brand',
			'name'  => $brands[0],
		);
		$schema['manufacturer'] = array(
			'@type' => 'Organization',
			'name'  => $brands[0],
		);
	}

	$categories = wc_get_product_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
	if ( ! empty( $categories ) ) {
		$schema['category'] = implode( ' > ', array_reverse( $categories ) );
	}

	$image = wp_get_attachment_url( $product->get_image_id() );
	if ( $image ) {
		$schema['image'] = $image;
	}

	// The spec table rendered as machine-readable properties. This is what an
	// answer engine reads to say "18 AWG, 1 conductor, shielded" — the HTML
	// table alone leaves it guessing.
	$specs = surtilec_schema_product_specs( $product );
	if ( ! empty( $specs ) ) {
		$schema['additionalProperty'] = $specs;
	}

	$schema['inLanguage'] = SURTILEC_LOCALE;
	$schema['seller']     = array( '@id' => home_url( '/#organization' ) );

	return $schema;
}

/**
 * Visible global (pa_*) attributes as schema.org PropertyValue entries.
 *
 * Mirrors the on-page spec table exactly — no derived or inferred values, so a
 * product can never advertise a spec its WordPress record does not hold.
 *
 * @param WC_Product $product Product.
 * @return array
 */
function surtilec_schema_product_specs( $product ) {
	$specs = array();

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->get_visible() || ! $attribute->is_taxonomy() ) {
			continue;
		}
		$taxonomy = $attribute->get_taxonomy();
		$terms    = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) );
		if ( empty( $terms ) ) {
			continue;
		}
		$specs[] = array(
			'@type'      => 'PropertyValue',
			'propertyID' => str_replace( 'pa_', '', $taxonomy ),
			'name'       => wc_attribute_label( $taxonomy ),
			'value'      => implode( ', ', $terms ),
		);
	}

	return $specs;
}

/**
 * FAQPage built from the product description's own question headings.
 *
 * Every product already carries "¿Qué producto es?", "¿Para qué sirve?" and
 * "¿Qué debo confirmar antes de comprar?" as H2s with an answer paragraph
 * underneath. That copy was written to be answered by an assistant, but with no
 * markup only the HTML says so. We read back the rendered description rather
 * than templating a second copy, so schema and page can never disagree.
 *
 * @return array|null
 */
function surtilec_schema_product_faq() {
	$product = surtilec_schema_current_product();
	if ( ! $product instanceof WC_Product || ! class_exists( 'DOMDocument' ) ) {
		return null;
	}

	$html = $product->get_description();
	if ( '' === trim( (string) $html ) ) {
		return null;
	}

	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8"?><div id="su-root">' . wpautop( $html ) . '</div>' );
	libxml_clear_errors();

	$root = $dom->getElementById( 'su-root' );
	if ( ! $root ) {
		return null;
	}

	$entities = array();
	$pending  = null;
	foreach ( $root->childNodes as $node ) {
		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			continue;
		}
		$tag  = strtolower( $node->nodeName );
		$text = trim( preg_replace( '/\s+/u', ' ', $node->textContent ) );

		if ( in_array( $tag, array( 'h2', 'h3' ), true ) ) {
			// Only question headings become FAQ entries.
			$pending = ( '' !== $text && false !== mb_strpos( $text, '¿' ) ) ? $text : null;
			continue;
		}
		if ( null !== $pending && in_array( $tag, array( 'p', 'ul', 'ol' ), true ) && '' !== $text ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $pending,
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $text,
				),
			);
			$pending = null;
		}
	}

	if ( empty( $entities ) ) {
		return null;
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink( $product->get_id() ) . '#faq',
		'inLanguage' => SURTILEC_LOCALE,
		'mainEntity' => $entities,
	);
}

/**
 * AboutPage (Nosotros) — enlaza a la Organization site-wide como mainEntity.
 *
 * @return array
 */
function surtilec_schema_aboutpage() {
	return array(
		'@type'       => 'AboutPage',
		'@id'         => get_permalink() . '#aboutpage',
		'url'         => get_permalink(),
		'name'        => wp_get_document_title(),
		'description' => surtilec_entity_sentence(),
		'mainEntity'  => array( '@id' => home_url( '/#organization' ) ),
		'isPartOf'    => array( '@id' => home_url( '/#organization' ) ),
	);
}

/**
 * Build a BreadcrumbList from a list of [name, url] pairs (Inicio first).
 *
 * The @id is not decoration: AIOSEO's WebPage/ItemPage/CollectionPage node
 * emits `breadcrumb: {"@id": "<canonical>#breadcrumblist"}`, and we delete
 * AIOSEO's own BreadcrumbList below. Reusing that exact @id keeps the graph
 * connected instead of leaving a reference pointing at a node we removed.
 *
 * @param array<int,array{0:string,1:string}> $trail    Crumb pairs.
 * @param string                              $page_url Canonical URL of the current page.
 * @return array|null
 */
function surtilec_schema_breadcrumb( $trail, $page_url = '' ) {
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

	$schema = array( '@type' => 'BreadcrumbList' );
	if ( $page_url ) {
		$schema['@id'] = trailingslashit( $page_url ) . '#breadcrumblist';
	}
	$schema['itemListElement'] = $items;

	return $schema;
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
	$product = surtilec_schema_current_product();
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
	return surtilec_schema_breadcrumb( $trail, get_permalink( $product->get_id() ) );
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
	$trail    = array_merge( $trail, surtilec_schema_term_trail( $term->term_id ) );
	$term_url = get_term_link( $term );
	$trail[]  = array( $term->name, $term_url );
	return surtilec_schema_breadcrumb( $trail, is_wp_error( $term_url ) ? '' : $term_url );
}

/**
 * Reconcile AIOSEO's graph with ours.
 *
 * - Strips AIOSEO's Organization + BreadcrumbList (we emit richer versions).
 * - Rewrites inLanguage: AIOSEO derives es-ES from the WordPress locale, but
 *   this is a Colombian catalog and es-ES tells search engines it targets Spain.
 * - Adds the sitelinks SearchAction to WebSite; the site has a real product
 *   search and was declaring no potentialAction at all.
 */
add_filter(
	'aioseo_schema_output',
	function ( $graph ) {
		if ( ! is_array( $graph ) ) {
			return $graph;
		}

		$kept = array();
		foreach ( $graph as $node ) {
			if ( ! is_array( $node ) ) {
				$kept[] = $node;
				continue;
			}

			$types = (array) ( isset( $node['@type'] ) ? $node['@type'] : '' );
			if ( array_intersect( $types, array( 'Organization', 'BreadcrumbList' ) ) ) {
				continue;
			}

			if ( isset( $node['inLanguage'] ) ) {
				$node['inLanguage'] = SURTILEC_LOCALE;
			}

			// AIOSEO points every WebPage at `<canonical>#breadcrumblist`, but we
			// delete the node it was describing and only some page types get a
			// replacement (products and categories here, a few templates via
			// surtilec_breadcrumbs). Rather than chase every template, drop the
			// reference: a standalone BreadcrumbList is valid on its own, and a
			// reference to a node that does not exist is not.
			unset( $node['breadcrumb'] );

			if ( in_array( 'WebSite', $types, true ) && ! isset( $node['potentialAction'] ) ) {
				$node['potentialAction'] = array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => home_url( '/?s={search_term_string}' ),
					),
					'query-input' => 'required name=search_term_string',
				);
			}

			$kept[] = $node;
		}

		return array_values( $kept );
	}
);

/**
 * Drop WooCommerce's own BreadcrumbList structured data.
 *
 * WooCommerce prints a second, differently-shaped BreadcrumbList in the footer
 * (`item` as an object with @id rather than a URL string). Two breadcrumb trails
 * per page is a conflicting signal, and ours is the one wired into the graph via
 * the AIOSEO WebPage `breadcrumb` reference.
 */
add_filter( 'woocommerce_structured_data_breadcrumblist', '__return_empty_array', 99 );

/**
 * Social share image, for pages that would otherwise have none.
 *
 * There was no og:image anywhere on the site while `twitter:card` claimed
 * `summary_large_image` — every WhatsApp and LinkedIn share of a product link
 * rendered blank, which matters for a business that sells through WhatsApp.
 * Products fall back to their featured image, everything else to the brand card.
 *
 * @return string
 */
function surtilec_social_image_fallback() {
	if ( is_singular() ) {
		$thumbnail = get_the_post_thumbnail_url( get_queried_object_id(), 'full' );
		if ( $thumbnail ) {
			return $thumbnail;
		}
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term     = get_queried_object();
		$thumb_id = ( $term instanceof WP_Term ) ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0;
		if ( $thumb_id ) {
			$thumbnail = wp_get_attachment_image_url( $thumb_id, 'full' );
			if ( $thumbnail ) {
				return $thumbnail;
			}
		}
	}

	return surtilec_og_default_url();
}

/**
 * Fill og:image when AIOSEO resolved none.
 *
 * AIOSEO builds its social meta as a keyed array and only adds the image keys
 * when it found an image, so we add them here rather than filtering a value
 * that is never emitted.
 *
 * @param array $meta Open Graph meta tags.
 * @return array
 */
add_filter(
	'aioseo_facebook_tags',
	function ( $meta ) {
		if ( ! is_array( $meta ) || ! empty( $meta['og:image'] ) ) {
			return $meta;
		}

		$image                       = surtilec_social_image_fallback();
		$meta['og:image']            = $image;
		$meta['og:image:secure_url'] = is_ssl() ? $image : '';

		if ( surtilec_og_default_url() === $image ) {
			$meta['og:image:width']  = 1200;
			$meta['og:image:height'] = 630;
			$meta['og:image:alt']    = 'Surtilec — cables especiales y automatización industrial';
		}

		return $meta;
	},
	20
);

/**
 * Same fallback for the Twitter card, which declares summary_large_image.
 *
 * @param array $meta Twitter card meta tags.
 * @return array
 */
add_filter(
	'aioseo_twitter_tags',
	function ( $meta ) {
		if ( ! is_array( $meta ) || ! empty( $meta['twitter:image'] ) ) {
			return $meta;
		}
		$meta['twitter:image'] = surtilec_social_image_fallback();
		return $meta;
	},
	20
);

/**
 * AIOSEO reports og:locale from the WordPress locale (es_ES). Colombia is the
 * market; keep the social locale consistent with the schema inLanguage.
 *
 * @return string
 */
add_filter(
	'aioseo_og_locale',
	function () {
		return 'es_CO';
	},
	20
);
