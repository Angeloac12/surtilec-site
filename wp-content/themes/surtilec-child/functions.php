<?php
/**
 * Surtilec child theme functions.
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the Spanish text domain for the child theme.
 */
add_action(
	'after_setup_theme',
	function () {
		load_child_theme_textdomain( 'surtilec', get_stylesheet_directory() . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'responsive-embeds' );
	}
);

/**
 * Enqueue parent and child styles.
 *
 * The child style depends on the parent ('generate-style') so it always
 * loads after it and can override cleanly.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'generate-style',
			get_template_directory_uri() . '/style.css',
			array(),
			wp_get_theme( get_template() )->get( 'Version' )
		);

		wp_enqueue_style(
			'surtilec-child-style',
			get_stylesheet_uri(),
			array( 'generate-style' ),
			wp_get_theme()->get( 'Version' )
		);
	},
	20
);
