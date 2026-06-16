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
			<?php
			if ( function_exists( 'surtilec_metals_ticker' ) ) {
				echo surtilec_metals_ticker(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — escaped inside.
			}
			?>
			<span class="su-util-item su-util-sep" aria-hidden="true"></span>
			<span class="su-util-item su-util-claim su-util-strong">Cotización en menos de 1 hora hábil</span>
			<span class="su-util-item su-util-claim">Despachos a toda Colombia</span>
		</div>
	</div>
	<?php
}

/* =============================================================
   Menú "Catálogo": dropdown NATIVO de GeneratePress (no mega custom),
   para que sea idéntico en color y organización al dropdown de
   "Industrias". Las 5 líneas pilar se cargan como ítems hijos reales
   del menú (estado de servidor, vía WP-CLI). Estilo compartido para
   ambos dropdowns en style.css (.main-navigation .sub-menu).
   ============================================================= */

/* =============================================================
   Row 1 header tools (Graybar/Nassau two-row pattern): prominent
   product search + WhatsApp + solid-orange "Cotizar" button, placed
   beside the logo via generate_after_logo. The category menu lives in
   Row 2 (GP nav set to "below header").
   ============================================================= */
// generate_after_logo only fires when a logo IMAGE exists; use the header-content
// hook so the tools render whether the brand is a logo or the text site title.
add_action( 'generate_after_header_content', 'surtilec_header_tools' );
function surtilec_header_tools() {
	$wa = function_exists( 'surtilec_wa_link' )
		? surtilec_wa_link( 'Hola Surtilec, quiero una cotización.' )
		: 'https://wa.me/573204499026';
	?>
	<div class="su-header-tools">
		<div class="surtilec-nav-search">
			<form role="search" method="get" class="surtilec-product-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
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
		<a class="su-header-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.3-1.38a9.9 9.9 0 0 0 4.74 1.2c5.46 0 9.9-4.44 9.9-9.9S17.5 2 12.04 2zm0 18.04c-1.5 0-2.97-.4-4.25-1.16l-.3-.18-3.15.82.84-3.07-.2-.32a8.2 8.2 0 0 1-1.26-4.36c0-4.54 3.7-8.23 8.24-8.23 4.54 0 8.23 3.69 8.23 8.23 0 4.54-3.69 8.27-8.2 8.27z"/></svg>
		</a>
		<a class="su-header-cta" href="<?php echo esc_url( home_url( '/cotizar/solicitud/' ) ); ?>">
			<span>Cotizar</span>
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
		</a>
	</div>
	<?php
}

/**
 * De-duplicate "Cotizar": drop the top-level "Cotizar" menu item (and its
 * children) from the primary menu — the orange Row 1 button is the single
 * quote entry point. "Subir listado" stays accessible from the footer and
 * the /cotizar/ page.
 */
add_filter(
	'wp_nav_menu_objects',
	function ( $items, $args ) {
		if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
			return $items;
		}
		$drop = array();
		foreach ( $items as $item ) {
			if ( 0 === (int) $item->menu_item_parent && 'cotizar' === sanitize_title( $item->title ) ) {
				$drop[] = (int) $item->ID;
			}
		}
		if ( empty( $drop ) ) {
			return $items;
		}
		return array_filter(
			$items,
			function ( $item ) use ( $drop ) {
				return ! in_array( (int) $item->ID, $drop, true )
					&& ! in_array( (int) $item->menu_item_parent, $drop, true );
			}
		);
	},
	10,
	2
);

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
