<?php
/**
 * Rebuild product SEO titles and meta descriptions so they stop cutting mid-word.
 *
 * The existing values are the product title hard-sliced at 60 and 155
 * characters, which lands mid-token and throws away the part that mattered:
 *
 *   title: "Belden 9947 — Cable de control 22 AWG, 15... | Surtilec"
 *   desc:  "... línea Conductores desnudos / Alambre desnudo. Especificaciones regi..."
 *
 * The conductor count is cut off; the description promises specifications and
 * is severed before naming one. 628 titles and 1,321 descriptions are affected.
 *
 * This composes instead of slicing. Titles keep the product name, which is
 * already front-loaded with the manufacturer, reference and key specs, and are
 * trimmed only at a word boundary. The " | Surtilec" suffix is added only when
 * it fits, because the domain already appears above the title in the SERP and
 * those 11 characters are worth more spent on specs.
 *
 * Descriptions are assembled from clauses and drop whole clauses, lowest value
 * first, until the result fits. Every value comes from the product's own
 * WooCommerce attributes, SKU, brand and category, so nothing is asserted that
 * the catalogue does not record.
 *
 * WP-CLI parses `--flags` itself, so the mode is a positional argument.
 *
 * Usage:
 *   scripts/wp.sh eval-file - dry-run < scripts/rebuild-product-seo-copy.php
 *   scripts/wp.sh eval-file - live    < scripts/rebuild-product-seo-copy.php
 *
 * @package Surtilec
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

$argv = isset( $args ) && is_array( $args ) ? $args : array();
$live = in_array( 'live', $argv, true );

if ( ! $live && ! in_array( 'dry-run', $argv, true ) ) {
	WP_CLI::error( 'Pasa "dry-run" o "live" como argumento posicional.' );
}

const SU_TITLE_LIMIT  = 60;
const SU_DESC_LIMIT   = 155;
const SU_DESC_FLOOR   = 70;
const SU_TITLE_SUFFIX = ' | Surtilec';

/**
 * Collapse whitespace and strip markup from a stored value.
 *
 * @param string $value Raw value.
 * @return string
 */
function su_clean( $value ) {
	$value = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES, 'UTF-8' );
	return trim( preg_replace( '/\s+/u', ' ', $value ) );
}

/**
 * Trim to a length at a word boundary, never mid-token and never with an ellipsis.
 *
 * @param string $text  Text to trim.
 * @param int    $limit Maximum length.
 * @return string
 */
function su_trim_words( $text, $limit ) {
	if ( mb_strlen( $text ) <= $limit ) {
		return $text;
	}

	$window = mb_substr( $text, 0, $limit );
	$space  = mb_strrpos( $window, ' ' );
	if ( false === $space ) {
		return '';
	}

	// Drop trailing punctuation left dangling by the cut, including the em dash
	// used as the separator in product names.
	return rtrim( mb_substr( $window, 0, $space ), " \t,;:.-–—/(" );
}

/**
 * The most specific product category, i.e. the deepest in the tree.
 *
 * @param int $product_id Product ID.
 * @return string
 */
function su_primary_category( $product_id ) {
	$terms = wp_get_post_terms( $product_id, 'product_cat' );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	$best  = null;
	$depth = -1;
	foreach ( $terms as $term ) {
		$ancestors = count( get_ancestors( $term->term_id, 'product_cat' ) );
		if ( $ancestors > $depth ) {
			$depth = $ancestors;
			$best  = $term;
		}
	}

	return $best ? su_clean( $best->name ) : '';
}

/**
 * Attribute values for a product, keyed by the bare attribute slug.
 *
 * @param WC_Product $product Product.
 * @return array<string,string>
 */
function su_attributes( $product ) {
	$values = array();
	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->is_taxonomy() ) {
			continue;
		}
		$taxonomy = $attribute->get_taxonomy();
		$terms    = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) );
		if ( empty( $terms ) ) {
			continue;
		}
		$values[ str_replace( 'pa_', '', $taxonomy ) ] = su_clean( implode( ', ', $terms ) );
	}
	return $values;
}

/**
 * Compose the SEO title.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function su_build_title( $product ) {
	$name = su_clean( $product->get_name() );
	if ( '' === $name ) {
		return '';
	}

	// Best case: the whole name plus the brand suffix fits.
	if ( mb_strlen( $name . SU_TITLE_SUFFIX ) <= SU_TITLE_LIMIT ) {
		return $name . SU_TITLE_SUFFIX;
	}

	// Next best: the whole name, without spending 11 characters on a brand the
	// SERP already shows as the domain.
	if ( mb_strlen( $name ) <= SU_TITLE_LIMIT ) {
		return $name;
	}

	// Otherwise keep as much of the front-loaded name as fits on a word break.
	$trimmed = su_trim_words( $name, SU_TITLE_LIMIT );
	return '' !== $trimmed ? $trimmed : mb_substr( $name, 0, SU_TITLE_LIMIT );
}

/**
 * Compose the meta description from whole clauses.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function su_build_description( $product ) {
	$attributes = su_attributes( $product );
	$sku        = su_clean( $product->get_sku() );
	$brand      = isset( $attributes['marca'] ) ? $attributes['marca'] : '';
	$category   = su_primary_category( $product->get_id() );

	// Identity. Product names are written "Belden 29502 — Cable para variador…",
	// so the segment before the em dash is the manufacturer reference buyers
	// actually search. The stored SKU is often the internal distributor code
	// ("566-29502-100-10"), which nobody types, so prefer the name segment and
	// fall back to the SKU only when there is no dash-delimited reference.
	$name  = su_clean( $product->get_name() );
	$parts = preg_split( '/\s+[—–]\s+/u', $name, 2 );
	$ref   = ( is_array( $parts ) && count( $parts ) === 2 ) ? trim( $parts[0] ) : '';

	if ( '' !== $ref && mb_strlen( $ref ) > 40 ) {
		$ref = ''; // Not a reference, just a long name that happens to contain a dash.
	}

	if ( '' === $ref ) {
		if ( '' !== $brand && '' !== $sku ) {
			$ref = $brand . ' ' . $sku;
		} elseif ( '' !== $sku ) {
			$ref = 'Ref. ' . $sku;
		} elseif ( '' !== $brand ) {
			$ref = $brand;
		}
	}

	$head = $ref;
	if ( '' !== $category ) {
		$head = '' !== $head ? $head . ' — ' . $category : $category;
	}
	if ( '' === $head ) {
		$head = su_trim_words( su_clean( $product->get_name() ), 60 );
	}
	if ( '' === $head ) {
		return '';
	}

	// Specs, most distinguishing first so the least useful drops off the end.
	$specs = array();
	if ( isset( $attributes['calibre-awg'] ) ) {
		$specs[] = $attributes['calibre-awg'] . ' AWG';
	}
	if ( isset( $attributes['numero-conductores'] ) ) {
		$count   = $attributes['numero-conductores'];
		$specs[] = $count . ( '1' === $count ? ' conductor' : ' conductores' );
	}
	if ( isset( $attributes['voltaje'] ) ) {
		$specs[] = $attributes['voltaje'];
	}
	if ( isset( $attributes['apantallado'] ) ) {
		$specs[] = ( 0 === strcasecmp( $attributes['apantallado'], 'Sí' ) ) ? 'apantallado' : 'sin apantallar';
	}
	if ( isset( $attributes['chaqueta'] ) ) {
		// "PVC - Polyvinyl Chloride" reads better as just the short code.
		$jacket  = trim( explode( '-', $attributes['chaqueta'] )[0] );
		$specs[] = 'chaqueta ' . $jacket;
	}

	$tails = array(
		'Cotiza disponibilidad y despacho a toda Colombia desde Bogotá.',
		'Cotiza disponibilidad y despacho a toda Colombia.',
		'Cotiza disponibilidad en Surtilec.',
	);

	// Drop specs from the end, then shorten the closing promise, until it fits.
	foreach ( $tails as $tail ) {
		for ( $keep = count( $specs ); $keep >= 0; $keep-- ) {
			$body      = $keep > 0 ? $head . ': ' . implode( ', ', array_slice( $specs, 0, $keep ) ) . '.' : $head . '.';
			$candidate = $body . ' ' . $tail;

			if ( mb_strlen( $candidate ) <= SU_DESC_LIMIT && mb_strlen( $candidate ) >= SU_DESC_FLOOR ) {
				return $candidate;
			}
		}
	}

	// Nothing fit with a tail; fall back to identity plus specs alone.
	for ( $keep = count( $specs ); $keep >= 0; $keep-- ) {
		$body = $keep > 0 ? $head . ': ' . implode( ', ', array_slice( $specs, 0, $keep ) ) . '.' : $head . '.';
		if ( mb_strlen( $body ) <= SU_DESC_LIMIT ) {
			return $body;
		}
	}

	return '';
}

// ---------------------------------------------------------------------------

global $wpdb;

$table = $wpdb->prefix . 'aioseo_posts';
$now   = current_time( 'mysql', true );

$product_ids = $wpdb->get_col(
	"SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY ID ASC"
);

$stats = array(
	'products'        => count( $product_ids ),
	'title_changed'   => 0,
	'desc_changed'    => 0,
	'unchanged'       => 0,
	'skipped_no_copy' => 0,
	'rows_inserted'   => 0,
	'rows_updated'    => 0,
);

$title_seen   = array();
$desc_seen    = array();
$title_dupes  = 0;
$desc_dupes   = 0;
$title_lens   = array();
$desc_lens    = array();
$samples      = array();
$ellipsis_out = 0;

foreach ( $product_ids as $product_id ) {
	$product_id = (int) $product_id;
	$product    = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product ) {
		continue;
	}

	$title = su_build_title( $product );
	$desc  = su_build_description( $product );

	// A handful of products share an identical name across two SKUs, which
	// would put the same title on two indexed URLs and let Google pick one.
	// Disambiguate with the SKU, dropping the brand suffix first to make room.
	if ( '' !== $title && isset( $title_seen[ $title ] ) ) {
		$sku_suffix = su_clean( $product->get_sku() );
		if ( '' !== $sku_suffix ) {
			$base = $title;
			if ( SU_TITLE_SUFFIX === mb_substr( $base, -mb_strlen( SU_TITLE_SUFFIX ) ) ) {
				$base = mb_substr( $base, 0, -mb_strlen( SU_TITLE_SUFFIX ) );
			}
			$candidate = $base . ' ' . $sku_suffix;
			if ( mb_strlen( $candidate ) > SU_TITLE_LIMIT ) {
				$room      = SU_TITLE_LIMIT - mb_strlen( $sku_suffix ) - 1;
				$base      = su_trim_words( $base, max( $room, 1 ) );
				$candidate = '' !== $base ? $base . ' ' . $sku_suffix : $sku_suffix;
			}
			if ( mb_strlen( $candidate ) <= SU_TITLE_LIMIT ) {
				$title = $candidate;
			}
		}
	}

	if ( '' === $title || '' === $desc ) {
		$stats['skipped_no_copy']++;
		WP_CLI::warning( "Sin copia utilizable: #$product_id " . $product->get_name() );
		continue;
	}

	if ( mb_strlen( $title ) > SU_TITLE_LIMIT || mb_strlen( $desc ) > SU_DESC_LIMIT ) {
		$stats['skipped_no_copy']++;
		WP_CLI::warning( sprintf( '#%d excede límites (%d/%d). Omitido.', $product_id, mb_strlen( $title ), mb_strlen( $desc ) ) );
		continue;
	}

	if ( false !== mb_strpos( $title, '...' ) || false !== mb_strpos( $desc, '...' ) ) {
		$ellipsis_out++;
	}

	$title_lens[] = mb_strlen( $title );
	$desc_lens[]  = mb_strlen( $desc );

	if ( isset( $title_seen[ $title ] ) ) {
		$title_dupes++;
	}
	if ( isset( $desc_seen[ $desc ] ) ) {
		$desc_dupes++;
	}
	$title_seen[ $title ] = 1;
	$desc_seen[ $desc ]   = 1;

	$row = $wpdb->get_row(
		$wpdb->prepare( "SELECT id, title, description FROM {$table} WHERE post_id = %d LIMIT 1", $product_id ),
		ARRAY_A
	);

	$same = $row && (string) $row['title'] === $title && (string) $row['description'] === $desc;
	if ( $same ) {
		$stats['unchanged']++;
		continue;
	}

	if ( ! $row || (string) $row['title'] !== $title ) {
		$stats['title_changed']++;
	}
	if ( ! $row || (string) $row['description'] !== $desc ) {
		$stats['desc_changed']++;
	}

	if ( count( $samples ) < 6 ) {
		$samples[] = array(
			'id'          => $product_id,
			'title_from'  => $row ? (string) $row['title'] : '(sin override)',
			'title_to'    => $title,
			'desc_from'   => $row ? (string) $row['description'] : '(sin override)',
			'desc_to'     => $desc,
		);
	}

	if ( ! $live ) {
		$stats[ $row ? 'rows_updated' : 'rows_inserted' ]++;
		continue;
	}

	if ( $row ) {
		$wpdb->update(
			$table,
			array(
				'title'       => $title,
				'description' => $desc,
				'updated'     => $now,
			),
			array( 'id' => (int) $row['id'] )
		);
		$stats['rows_updated']++;
	} else {
		$wpdb->insert(
			$table,
			array(
				'post_id'     => $product_id,
				'title'       => $title,
				'description' => $desc,
				'created'     => $now,
				'updated'     => $now,
			)
		);
		$stats['rows_inserted']++;
	}
}

if ( $live ) {
	if ( function_exists( 'aioseo' ) && isset( aioseo()->core->cache ) ) {
		aioseo()->core->cache->clear();
	}
	wp_cache_flush();
}

foreach ( $samples as $sample ) {
	WP_CLI::log( '── #' . $sample['id'] );
	WP_CLI::log( '   título antes: ' . $sample['title_from'] );
	WP_CLI::log( '   título ahora: ' . $sample['title_to'] . '  [' . mb_strlen( $sample['title_to'] ) . ']' );
	WP_CLI::log( '   desc   antes: ' . $sample['desc_from'] );
	WP_CLI::log( '   desc   ahora: ' . $sample['desc_to'] . '  [' . mb_strlen( $sample['desc_to'] ) . ']' );
	WP_CLI::log( '' );
}

sort( $title_lens );
sort( $desc_lens );

WP_CLI::success( $live ? 'Copia SEO de productos regenerada.' : 'Simulación: no se escribió nada.' );
foreach ( $stats as $key => $value ) {
	WP_CLI::log( "$key: $value" );
}
if ( $title_lens ) {
	WP_CLI::log( sprintf( 'título  min/mediana/max: %d / %d / %d', $title_lens[0], $title_lens[ intdiv( count( $title_lens ), 2 ) ], end( $title_lens ) ) );
	WP_CLI::log( sprintf( 'desc    min/mediana/max: %d / %d / %d', $desc_lens[0], $desc_lens[ intdiv( count( $desc_lens ), 2 ) ], end( $desc_lens ) ) );
}
WP_CLI::log( "títulos duplicados: $title_dupes" );
WP_CLI::log( "descripciones duplicadas: $desc_dupes" );
WP_CLI::log( "con puntos suspensivos: $ellipsis_out" );
