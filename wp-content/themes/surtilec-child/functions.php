<?php
/**
 * Surtilec child theme functions.
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Catalog templates: single product spec table, category FAQ/tiles, shop tiles.
require_once get_stylesheet_directory() . '/inc/catalog-templates.php';

// Header chrome: utility bar, mega menu, font preload, front-page title hide.
require_once get_stylesheet_directory() . '/inc/header.php';

// Custom industrial footer (replaces the GeneratePress site-info bar).
require_once get_stylesheet_directory() . '/inc/footer.php';

// Homepage editable fields (ACF) + helper.
require_once get_stylesheet_directory() . '/inc/homepage-fields.php';

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

/**
 * Force no sidebar on the front page and all WooCommerce pages.
 *
 * The homepage is a full-bleed template (front-page.php); a sidebar column
 * would shrink the content column and leave an empty gutter. Filters become
 * later (faceted catalog); for now front page + product/shop/taxonomy and
 * cart/checkout use the full content width.
 */
add_filter(
	'generate_sidebar_layout',
	function ( $layout ) {
		if ( is_front_page() ) {
			return 'no-sidebar';
		}

		if ( ! function_exists( 'is_woocommerce' ) ) {
			return $layout;
		}

		if ( is_woocommerce() || is_shop() || is_product_taxonomy() || is_cart() || is_checkout() ) {
			return 'no-sidebar';
		}

		return $layout;
	}
);

/**
 * Load the CF7 form helper script only on pages that render a CF7 form.
 * Fires when Contact Form 7 enqueues its own assets.
 */
add_action(
	'wpcf7_enqueue_scripts',
	function () {
		wp_enqueue_script(
			'surtilec-forms',
			get_stylesheet_directory_uri() . '/assets/js/surtilec-forms.js',
			array(),
			wp_get_theme()->get( 'Version' ),
			true
		);
	}
);

/**
 * Joinchat: on single products, prefill the WhatsApp message with the product
 * name via the built-in {PRODUCT} variable (resolved by Joinchat's WooCommerce
 * integration).
 */
add_filter(
	'joinchat_settings',
	function ( $settings ) {
		if ( function_exists( 'is_product' ) && is_product() ) {
			$settings['message_send'] = 'Hola Surtilec, quiero cotizar: {PRODUCT} — {URL}';
		}
		return $settings;
	}
);

/**
 * Persistent product search in the header navigation.
 *
 * A WooCommerce-scoped search (post_type=product) with a Spanish placeholder,
 * injected inside the primary navigation so it stays visible on every page.
 */
add_action(
	'generate_inside_navigation',
	function () {
		?>
		<div class="surtilec-nav-search">
			<form role="search" method="get" class="surtilec-product-search"
				action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="surtilec-search-field"><?php esc_html_e( 'Buscar producto', 'surtilec' ); ?></label>
				<input type="search" id="surtilec-search-field" name="s"
					placeholder="<?php esc_attr_e( 'Buscar producto, ej: cable THHN 12 AWG', 'surtilec' ); ?>"
					value="<?php echo esc_attr( get_search_query() ); ?>" />
				<input type="hidden" name="post_type" value="product" />
				<button type="submit" aria-label="<?php esc_attr_e( 'Buscar', 'surtilec' ); ?>">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
						<circle cx="11" cy="11" r="7" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
					</svg>
				</button>
			</form>
		</div>
		<?php
	}
);
