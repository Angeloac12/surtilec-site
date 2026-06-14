<?php
/**
 * Surtilec — header chrome: top utility bar, mega-menu under "Catálogo",
 * self-hosted font preloading, and front-page title suppression.
 *
 * The mega menu is built from the live category taxonomy (cached) via a custom
 * nav walker, so it needs NO WordPress menu editing: the existing "Principal"
 * menu item that points at /productos/ ("Catálogo") gets the panel injected.
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SURTILEC_PHONE_DISPLAY = '+57 320 449 9026';
const SURTILEC_PHONE_TEL     = '+573204499026';

/* =============================================================
   Self-hosted fonts — preload the above-the-fold woff2.
   @font-face lives in style.css; here we only add resource hints.
   ============================================================= */
add_action(
	'wp_head',
	function () {
		$dir = get_stylesheet_directory_uri() . '/assets/fonts/';
		foreach ( array( 'archivo-var.woff2', 'plexsans-var.woff2', 'plexmono-400.woff2' ) as $f ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( $dir . $f )
			);
		}
	},
	1
);

/* =============================================================
   Top utility bar — the "real company" signal above the header.
   ============================================================= */
add_action( 'generate_before_header', 'surtilec_utility_bar' );
function surtilec_utility_bar() {
	$wa = function_exists( 'surtilec_wa_link' )
		? surtilec_wa_link( 'Hola Surtilec, quiero una cotización.' )
		: 'https://wa.me/573204499026';
	?>
	<div class="surtilec-utilitybar">
		<div class="surtilec-utilitybar-inner">
			<a class="su-util-item su-util-phone" href="tel:<?php echo esc_attr( SURTILEC_PHONE_TEL ); ?>">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
				<?php echo esc_html( SURTILEC_PHONE_DISPLAY ); ?>
			</a>
			<a class="su-util-item su-util-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.3-1.38a9.9 9.9 0 0 0 4.74 1.2c5.46 0 9.9-4.44 9.9-9.9S17.5 2 12.04 2zm0 18.04c-1.5 0-2.97-.4-4.25-1.16l-.3-.18-3.15.82.84-3.07-.2-.32a8.2 8.2 0 0 1-1.26-4.36c0-4.54 3.7-8.23 8.24-8.23 4.54 0 8.23 3.69 8.23 8.23 0 4.54-3.69 8.27-8.2 8.27zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.25-.64.8-.78.97-.14.16-.29.18-.54.06-.25-.12-1.05-.39-2-1.23-.74-.66-1.24-1.47-1.38-1.72-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.13-.14.17-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.16 0-.43.06-.66.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.16 1.75 2.67 4.24 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.1-.22-.16-.47-.28z"/></svg>
				WhatsApp
			</a>
			<span class="su-util-item su-util-sep" aria-hidden="true"></span>
			<span class="su-util-item su-util-claim su-util-strong">Cotización en menos de 1 hora hábil</span>
			<span class="su-util-item su-util-claim">Despachos a toda Colombia</span>
		</div>
	</div>
	<?php
}

/* =============================================================
   Mega menu under "Catálogo" (the menu item linking to /productos/).
   ============================================================= */

/**
 * Attach the custom walker to the primary menu only.
 */
add_filter(
	'wp_nav_menu_args',
	function ( $args ) {
		if ( isset( $args['theme_location'] ) && 'primary' === $args['theme_location'] ) {
			$args['walker'] = new Surtilec_Mega_Walker();
		}
		return $args;
	}
);

/**
 * Walker that injects a taxonomy-driven mega panel into the "Catálogo" item.
 */
class Surtilec_Mega_Walker extends Walker_Nav_Menu {

	/**
	 * Is this the catalog top-level item? Matches the /productos/ shop link.
	 */
	protected function is_catalog( $item, $depth ) {
		if ( 0 !== (int) $depth ) {
			return false;
		}
		$path = trim( (string) wp_parse_url( (string) $item->url, PHP_URL_PATH ), '/' );
		return 'productos' === $path || 'catalogo' === sanitize_title( (string) $item->title );
	}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$buf = '';
		parent::start_el( $buf, $item, $depth, $args, $id );

		if ( $this->is_catalog( $item, $depth ) ) {
			// Mark the <li> as a dropdown parent and append the panel.
			if ( false === strpos( $buf, 'menu-item-has-children' ) ) {
				$buf = preg_replace( '/class="/', 'class="menu-item-has-children surtilec-has-mega ', $buf, 1 );
			} else {
				$buf = preg_replace( '/class="/', 'class="surtilec-has-mega ', $buf, 1 );
			}
			$buf .= surtilec_mega_panel_html();
		}

		$output .= $buf;
	}
}

/**
 * Build the mega-panel markup from the cached pillar taxonomy.
 *
 * @return string
 */
function surtilec_mega_panel_html() {
	$pillars = function_exists( 'surtilec_cached_terms' )
		? surtilec_cached_terms(
			'pillars',
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => false,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			)
		)
		: array();

	if ( empty( $pillars ) ) {
		return '';
	}

	ob_start();
	echo '<ul class="sub-menu surtilec-mega" role="menu">';
	echo '<li class="surtilec-mega-inner">';
	echo '<div class="surtilec-mega-grid">';

	foreach ( $pillars as $pillar ) {
		$children = surtilec_cached_terms(
			'subcat_' . $pillar->term_id,
			array(
				'taxonomy'   => 'product_cat',
				'parent'     => $pillar->term_id,
				'hide_empty' => false,
			)
		);

		echo '<div class="surtilec-mega-col">';
		echo '<a class="surtilec-mega-head" href="' . esc_url( get_term_link( $pillar ) ) . '">'
			. esc_html( $pillar->name ) . '</a>';

		if ( ! empty( $children ) ) {
			echo '<ul class="surtilec-mega-list">';
			foreach ( $children as $child ) {
				echo '<li><a href="' . esc_url( get_term_link( $child ) ) . '">'
					. esc_html( $child->name ) . '</a></li>';
			}
			echo '</ul>';
		} else {
			echo '<a class="surtilec-mega-all" href="' . esc_url( get_term_link( $pillar ) ) . '">Ver productos →</a>';
		}
		echo '</div>';
	}

	echo '</div>'; // grid
	echo '<a class="surtilec-mega-cta" href="' . esc_url( home_url( '/productos/' ) ) . '">Ver todo el catálogo →</a>';
	echo '</li>';
	echo '</ul>';
	return ob_get_clean();
}

/* =============================================================
   Header primary CTA — always-visible solid-orange "Cotizar" button.
   Injected into the navigation; placed at the far right via CSS.
   ============================================================= */
add_action( 'generate_inside_navigation', 'surtilec_header_cta', 20 );
function surtilec_header_cta() {
	echo '<a class="su-header-cta" href="' . esc_url( home_url( '/cotizar/solicitud/' ) ) . '">'
		. '<span>Cotizar</span>'
		. '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>'
		. '</a>';
}

/* =============================================================
   Condense-on-scroll — tiny vanilla JS (no jQuery), in footer.
   ============================================================= */
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_script(
			'surtilec-header',
			get_stylesheet_directory_uri() . '/assets/js/surtilec-header.js',
			array(),
			wp_get_theme()->get( 'Version' ),
			true
		);
	}
);

/* =============================================================
   Front page: suppress the page title (the hero is the opener).
   ============================================================= */
add_filter(
	'generate_show_title',
	function ( $show ) {
		return is_front_page() ? false : $show;
	}
);
