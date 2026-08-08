<?php
/**
 * Plugin Name: Surtilec — Site Icon
 * Description: Adds a compact Surtilec browser-tab icon when no WordPress Site Icon is configured.
 * Version:     0.1.0
 * Author:      Surtilec
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Output a lightweight SVG favicon fallback.
 *
 * If a formal Site Icon is uploaded later in Appearance > Customize, WordPress
 * takes over and this fallback stays silent.
 */
add_action( 'wp_head', 'surtilec_site_icon_fallback', 2 );
function surtilec_site_icon_fallback() {
	if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
		return;
	}

	$icon_url = content_url( 'mu-plugins/assets/brand/surtilec-site-icon.svg' );

	echo '<link rel="icon" href="' . esc_url( $icon_url ) . '" type="image/svg+xml">' . "\n";
}
