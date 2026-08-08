<?php
/**
 * Open two crawl/AEO surfaces that were switched off.
 *
 * 1. llms.txt was disabled, so /llms.txt and /llms-full.txt returned 404. It is
 *    the most direct surface an answer engine reads, and the site already
 *    writes for it: every product carries a "¿Qué producto es?" / "¿Para qué
 *    sirve?" answer structure. Enabling it also brings
 *    surtilec_aioseo_llms_exclude_products_without_images back into play, which
 *    is why that filter was removed alongside the image gate.
 *
 * 2. Paginated archives were `noindex, nofollow`. Keeping page 2+ out of the
 *    index is correct; adding `nofollow` is not, because it stops Googlebot
 *    following the product links those pages exist to expose. With 10 products
 *    per page, "Cable para bandeja" alone spans about 40 pages whose links were
 *    all dead ends. Switches to `noindex, follow`.
 *
 * AIOSEO keeps a second, flat "localized" copy of some settings that wins at
 * render time, so anything changed here is checked against that overlay too.
 * Neither key below currently lives in the overlay, but the check keeps this
 * script honest if that changes.
 *
 * WP-CLI parses `--flags` itself, so the mode is a positional argument.
 *
 * Usage:
 *   scripts/wp.sh eval-file - dry-run < scripts/apply-aeo-crawl-surface.php
 *   scripts/wp.sh eval-file - live    < scripts/apply-aeo-crawl-surface.php
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

$changes_wanted = array(
	array(
		'path'  => array( 'sitemap', 'llms', 'enable' ),
		'value' => true,
		'why'   => 'llms.txt disponible para motores de respuesta',
	),
	// The per-section link cap defaults to 1000, which truncated the Productos
	// section at 1000 of the 1,385 published products. Raise it so the whole
	// catalogue is listed now that none of it is noindexed.
	array(
		'path'  => array( 'sitemap', 'llms', 'advancedSettings', 'linksPerPostTax' ),
		'value' => 2000,
		'why'   => 'llms.txt lista los 1.385 productos, no solo 1.000',
	),
	array(
		'path'  => array( 'searchAppearance', 'advanced', 'globalRobotsMeta', 'nofollowPaginated' ),
		'value' => false,
		'why'   => 'paginación pasa a noindex, follow',
	),

	// `default` means "use AIOSEO's defaults for every global robots flag", and
	// AIOSEO short-circuits on it:
	//
	//   if ( globalRobotsMeta->default || globalRobotsMeta->nofollowPaginated )
	//
	// so while it is true the paginated checkboxes above are ignored entirely
	// and page 2+ always gets `noindex, nofollow`. Turning it off is what makes
	// the individual flags take effect.
	//
	// Every other branch that reads `default` was checked against this site's
	// current values and is unchanged by the flip:
	//   - feeds  (Robots.php)          `! default && ! noindexFeed`  -> still noindex, noindexFeed is true
	//   - sitemap root (Root.php)      `default || ! noindex`        -> still true, noindex is false
	//   - sitemap helpers (Helpers.php) `! default && noindex`       -> still false, noindex is false
	array(
		'path'  => array( 'searchAppearance', 'advanced', 'globalRobotsMeta', 'default' ),
		'value' => false,
		'why'   => 'deja de anular las casillas de paginación',
	),
);

$raw     = get_option( 'aioseo_options' );
$options = is_array( $raw ) ? $raw : json_decode( (string) $raw, true );

if ( ! is_array( $options ) ) {
	WP_CLI::error( 'No se pudo leer aioseo_options.' );
}

$changes = array();

foreach ( $changes_wanted as $wanted ) {
	$cursor = &$options;
	$ok     = true;

	foreach ( $wanted['path'] as $key ) {
		if ( ! is_array( $cursor ) || ! array_key_exists( $key, $cursor ) ) {
			WP_CLI::warning( 'Ruta ausente: ' . implode( '.', $wanted['path'] ) );
			$ok = false;
			break;
		}
		$cursor = &$cursor[ $key ];
	}

	if ( $ok && $cursor !== $wanted['value'] ) {
		$changes[] = sprintf(
			'%s: %s -> %s  (%s)',
			implode( '.', $wanted['path'] ),
			var_export( $cursor, true ),
			var_export( $wanted['value'], true ),
			$wanted['why']
		);
		$cursor = $wanted['value'];
	}

	unset( $cursor );
}

// Guard against the localized overlay silently winning at render time.
$overlay = get_option( 'aioseo_options_dynamic_localized' );
if ( is_array( $overlay ) ) {
	foreach ( array( 'sitemap_llms_enable', 'searchAppearance_advanced_globalRobotsMeta_nofollowPaginated' ) as $flat ) {
		if ( array_key_exists( $flat, $overlay ) ) {
			WP_CLI::warning( "El overlay localizado también define $flat; revísalo a mano." );
		}
	}
}

if ( empty( $changes ) ) {
	WP_CLI::success( 'Sin cambios: la configuración ya está correcta.' );
	return;
}

foreach ( $changes as $change ) {
	WP_CLI::log( '  ' . $change );
}

if ( ! $live ) {
	WP_CLI::success( sprintf( 'Simulación: %d cambios pendientes, no se escribió nada.', count( $changes ) ) );
	return;
}

update_option( 'aioseo_options', wp_json_encode( $options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

if ( function_exists( 'aioseo' ) && isset( aioseo()->core->cache ) ) {
	aioseo()->core->cache->clear();
}
wp_cache_flush();

WP_CLI::success( sprintf( '%d cambios aplicados.', count( $changes ) ) );
