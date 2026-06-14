<?php
/**
 * Surtilec — custom industrial footer (replaces the GeneratePress site-info bar).
 *
 * GP's default copyright bar ("Creado con GeneratePress") is emptied via the
 * generate_copyright filter and hidden in CSS; our multi-column footer renders
 * on generate_before_footer.
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Empty GP's copyright bar (removes "Creado con GeneratePress" + default text).
add_filter( 'generate_copyright', '__return_empty_string' );

add_action( 'generate_before_footer', 'surtilec_footer' );

/**
 * Render the site footer.
 */
function surtilec_footer() {
	$phone_tel  = defined( 'SURTILEC_PHONE_TEL' ) ? SURTILEC_PHONE_TEL : '+573204499026';
	$phone_disp = defined( 'SURTILEC_PHONE_DISPLAY' ) ? SURTILEC_PHONE_DISPLAY : '+57 320 449 9026';
	$wa         = function_exists( 'surtilec_wa_link' ) ? surtilec_wa_link( 'Hola Surtilec, quiero una cotización.' ) : 'https://wa.me/573204499026';
	$name       = get_bloginfo( 'name' );

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

	// Legal links only if the pages actually exist (no 404s).
	$legal = array();
	foreach ( array(
		'politica-de-privacidad' => 'Política de privacidad',
		'terminos'               => 'Términos',
	) as $slug => $label ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$legal[] = '<a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $label ) . '</a>';
		}
	}
	?>
	<footer class="surtilec-footer" role="contentinfo">

		<?php if ( ! is_front_page() ) : ?>
		<div class="su-foot-cta">
			<div class="su-foot-cta-inner">
				<div>
					<p class="su-foot-cta-title">¿Listo para cotizar?</p>
					<p class="su-foot-cta-sub">Respuesta en menos de 1 hora hábil.</p>
				</div>
				<a class="su-btn su-btn-primary" href="<?php echo esc_url( home_url( '/cotizar/solicitud/' ) ); ?>">Cotizar ahora</a>
			</div>
		</div>
		<?php endif; ?>

		<div class="su-foot-main">
			<div class="su-foot-grid">

				<div class="su-foot-brand">
					<span class="su-foot-logo"><?php echo esc_html( $name ); ?></span>
					<p class="su-foot-tagline">Distribuidor colombiano de cables especiales y automatización industrial.</p>
					<p class="su-foot-nap">Bogotá, Colombia · Despachos a todo el país.</p>
					<div class="su-foot-social">
						<a class="su-foot-soc" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.3-1.38a9.9 9.9 0 0 0 4.74 1.2c5.46 0 9.9-4.44 9.9-9.9S17.5 2 12.04 2zm0 18.04c-1.5 0-2.97-.4-4.25-1.16l-.3-.18-3.15.82.84-3.07-.2-.32a8.2 8.2 0 0 1-1.26-4.36c0-4.54 3.7-8.23 8.24-8.23 4.54 0 8.23 3.69 8.23 8.23 0 4.54-3.69 8.27-8.2 8.27zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.13-.16.25-.64.8-.78.97-.14.16-.29.18-.54.06-.25-.12-1.05-.39-2-1.23-.74-.66-1.24-1.47-1.38-1.72-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.13-.14.17-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.16 0-.43.06-.66.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.16 1.75 2.67 4.24 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.1-.22-.16-.47-.28z"/></svg>
						</a>
					</div>
				</div>

				<nav class="su-foot-col" aria-label="Líneas">
					<h3 class="su-foot-head">Líneas</h3>
					<ul>
						<?php foreach ( $pillars as $pillar ) : ?>
							<li><a href="<?php echo esc_url( get_term_link( $pillar ) ); ?>"><?php echo esc_html( $pillar->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>

				<nav class="su-foot-col" aria-label="Empresa">
					<h3 class="su-foot-head">Empresa</h3>
					<ul>
						<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Inicio</a></li>
						<li><a href="<?php echo esc_url( home_url( '/productos/' ) ); ?>">Catálogo</a></li>
						<li><a href="<?php echo esc_url( home_url( '/cotizar/' ) ); ?>">Cotizar</a></li>
						<li><a href="<?php echo esc_url( home_url( '/cotizar/subir-listado/' ) ); ?>">Subir listado</a></li>
						<li><a href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">Contacto</a></li>
					</ul>
				</nav>

				<div class="su-foot-col su-foot-contact">
					<h3 class="su-foot-head">Contacto</h3>
					<ul>
						<li><a href="tel:<?php echo esc_attr( $phone_tel ); ?>"><?php echo esc_html( $phone_disp ); ?></a></li>
						<li><a href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
						<li>Bogotá, Colombia</li>
						<li>Despachos a todo el país</li>
					</ul>
					<a class="su-btn su-btn-whatsapp su-foot-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">Escríbenos</a>
				</div>

			</div>
		</div>

		<div class="su-foot-legal">
			<div class="su-foot-legal-inner">
				<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $name ); ?> · Todos los derechos reservados.</span>
				<?php if ( ! empty( $legal ) ) : ?>
					<span class="su-foot-legal-links"><?php echo implode( ' · ', $legal ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<?php endif; ?>
			</div>
		</div>

	</footer>
	<?php
}
