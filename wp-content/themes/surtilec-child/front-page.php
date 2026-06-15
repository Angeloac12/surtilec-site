<?php
/**
 * Surtilec — front page (homepage) template. Direction "Acero" (dark industrial).
 *
 * Full-bleed sections rendered by the theme for design control; editable copy,
 * CTAs and the hero background come from ACF fields (see inc/homepage-fields.php)
 * with safe defaults. The entity sentence is pulled from the shared
 * surtilec_entity_sentence() so it stays verbatim with the Organization schema.
 *
 * @package Surtilec
 */

get_header();

/* ---- editable values (ACF + defaults) ---- */
$eyebrow  = surtilec_home_field( 'su_hero_eyebrow', 'Distribuidor industrial' );
$heading  = surtilec_home_field( 'su_hero_heading', 'Cables especiales y automatización industrial para la industria colombiana' );
$subline  = surtilec_home_field( 'su_hero_subline', 'Despachos a toda Colombia desde Bogotá. Respuesta en menos de 1 hora hábil.' );
$cta1_l   = surtilec_home_field( 'su_cta1_label', 'Cotizar ahora' );
$cta1_u   = surtilec_home_field( 'su_cta1_url', '/cotizar/solicitud/' );
$cta2_l   = surtilec_home_field( 'su_cta2_label', 'Ver catálogo' );
$cta2_u   = surtilec_home_field( 'su_cta2_url', '/productos/' );
$bg_id    = function_exists( 'get_field' ) ? get_field( 'su_hero_bg' ) : 0;
$bg_url   = $bg_id ? wp_get_attachment_image_url( (int) $bg_id, 'full' ) : '';

$wa_link  = function_exists( 'surtilec_wa_link' ) ? surtilec_wa_link( 'Hola Surtilec, quiero una cotización.' ) : 'https://wa.me/573204499026';

/* ---- pillar enrichment: icon + tagline by slug ---- */
$pillar_meta = array(
	'cables-de-control'          => array( 'tag' => 'Multiconductor para control y mando industrial.', 'icon' => 'control' ),
	'cable-thhn-thwn'            => array( 'tag' => 'Conductores para instalaciones y tableros.', 'icon' => 'bolt' ),
	'cables-para-variadores-vfd' => array( 'tag' => 'Apantallados para variadores de frecuencia.', 'icon' => 'wave' ),
	'cables-especiales'          => array( 'tag' => 'Encauchetados, instrumentación y apantallados.', 'icon' => 'shield' ),
	'automatizacion-industrial'  => array( 'tag' => 'Variadores, PLC, HMI y sensores.', 'icon' => 'chip' ),
);

/**
 * Tiny inline-SVG icon library for the homepage (24x24, stroke=currentColor).
 *
 * @param string $name Icon key.
 * @return string
 */
function surtilec_home_icon( $name ) {
	$p = array(
		'control' => '<line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="8" cy="6" r="2" fill="currentColor"/><circle cx="15" cy="12" r="2" fill="currentColor"/><circle cx="10" cy="18" r="2" fill="currentColor"/>',
		'bolt'    => '<path d="M13 2 4 14h6l-1 8 9-12h-6z" fill="currentColor" stroke="none"/>',
		'wave'    => '<path d="M3 12c2-4 4-4 6 0s4 4 6 0 4-4 6 0"/><path d="M3 17c2-4 4-4 6 0s4 4 6 0 4-4 6 0"/>',
		'shield'  => '<path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/>',
		'chip'    => '<rect x="7" y="7" width="10" height="10" rx="1"/><path d="M10 2v3M14 2v3M10 19v3M14 19v3M2 10h3M2 14h3M19 10h3M19 14h3"/>',
		'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'truck'   => '<path d="M1 4h13v11H1z"/><path d="M14 8h4l3 3v4h-7z"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
		'support' => '<path d="M4 12a8 8 0 0 1 16 0"/><rect x="2" y="12" width="4" height="7" rx="1"/><rect x="18" y="12" width="4" height="7" rx="1"/><path d="M20 19a4 4 0 0 1-4 4h-3"/>',
		'badge'   => '<path d="M12 2 4 5v6c0 5 3.4 8.5 8 11 4.6-2.5 8-6 8-11V5z"/><path d="m9 12 2 2 4-4"/>',
	);
	$d = isset( $p[ $name ] ) ? $p[ $name ] : '';
	return '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

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
?>

<main id="primary" class="surtilec-home-acero">

	<!-- 1. HERO -->
	<section class="su-section su-hero<?php echo $bg_url ? ' has-photo' : ''; ?>"
		<?php if ( $bg_url ) : ?>style="--su-hero-bg:url('<?php echo esc_url( $bg_url ); ?>')"<?php endif; ?>>
		<div class="su-inner su-hero-inner">
			<p class="su-eyebrow su-reveal"><?php echo esc_html( $eyebrow ); ?></p>
			<h1 class="su-hero-title su-reveal" style="--d:1"><?php echo esc_html( $heading ); ?></h1>
			<p class="su-hero-sub su-reveal" style="--d:2"><?php echo esc_html( $subline ); ?></p>
			<div class="su-hero-cta su-reveal" style="--d:3">
				<a class="su-btn su-btn-primary" href="<?php echo esc_url( $cta1_u ); ?>"><?php echo esc_html( $cta1_l ); ?></a>
				<a class="su-btn su-btn-ghost" href="<?php echo esc_url( $cta2_u ); ?>"><?php echo esc_html( $cta2_l ); ?></a>
			</div>
			<div class="su-reveal" style="--d:4">
				<?php
				// Claims ya establecidos (no cifras inventadas) — editables a futuro.
				surtilec_stat_bar(
					array(
						array( 'num' => '< 1 h', 'label' => 'Respuesta a tu cotización (horario hábil)' ),
						array( 'num' => 'Toda Colombia', 'label' => 'Cobertura de despacho' ),
						array( 'num' => 'Bogotá', 'label' => 'Centro de distribución' ),
					)
				);
				?>
			</div>
		</div>
	</section>

	<!-- 2. VALUE PROPS -->
	<section class="su-section su-band-light su-valueprops">
		<div class="su-inner su-vp-grid">
			<?php
			$vps = array(
				array( 'clock', 'Respuesta rápida', 'Cotización en menos de 1 hora hábil.' ),
				array( 'truck', 'Despacho nacional', 'Enviamos a toda Colombia desde Bogotá.' ),
				array( 'support', 'Asesoría técnica', 'Te ayudamos a elegir el producto correcto.' ),
				array( 'badge', 'Marcas reconocidas', 'Producto de fabricantes confiables.' ),
			);
			foreach ( $vps as $vp ) :
				?>
				<div class="su-vp">
					<span class="su-vp-icon"><?php echo surtilec_home_icon( $vp[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h3 class="su-vp-title"><?php echo esc_html( $vp[1] ); ?></h3>
					<p class="su-vp-text"><?php echo esc_html( $vp[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- 3. PILLARS -->
	<section class="su-section su-pillars">
		<div class="su-inner">
			<p class="su-eyebrow su-eyebrow-center">01 — Nuestras líneas</p>
			<h2 class="su-h2 su-h2-center">Catálogo por especialidad</h2>
			<div class="su-pillar-grid">
				<?php
				foreach ( $pillars as $pillar ) :
					$slug = $pillar->slug;
					$meta = isset( $pillar_meta[ $slug ] ) ? $pillar_meta[ $slug ] : array( 'tag' => '', 'icon' => 'control' );
					?>
					<a class="su-pillar" href="<?php echo esc_url( get_term_link( $pillar ) ); ?>">
						<span class="su-pillar-icon"><?php echo surtilec_home_icon( $meta['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span class="su-pillar-name"><?php echo esc_html( $pillar->name ); ?></span>
						<span class="su-pillar-desc"><?php echo esc_html( $meta['tag'] ); ?></span>
						<span class="su-pillar-go" aria-hidden="true">Ver productos →</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- 4. CÓMO COTIZAR -->
	<section class="su-section su-band-gray su-steps">
		<div class="su-inner">
			<p class="su-eyebrow su-eyebrow-center">02 — Proceso</p>
			<h2 class="su-h2 su-h2-center">Cómo cotizar</h2>
			<ol class="su-step-grid">
				<li class="su-step">
					<span class="su-step-num">1</span>
					<h3 class="su-step-title">Busca o sube tu listado</h3>
					<p class="su-step-text">Encuentra el producto en el catálogo o súbenos tu lista.</p>
				</li>
				<li class="su-step">
					<span class="su-step-num">2</span>
					<h3 class="su-step-title">Recibe cotización en menos de 1 hora hábil</h3>
					<p class="su-step-text">Te respondemos con precios y disponibilidad.</p>
				</li>
				<li class="su-step">
					<span class="su-step-num">3</span>
					<h3 class="su-step-title">Despachamos a toda Colombia</h3>
					<p class="su-step-text">Coordinamos el envío desde Bogotá.</p>
				</li>
			</ol>
		</div>
	</section>

	<!-- 5. MARCAS -->
	<section class="su-section su-band-paper su-brands">
		<div class="su-inner">
			<p class="su-eyebrow su-eyebrow-center su-eyebrow-dark">Marcas que distribuimos</p>
			<ul class="su-brand-row">
				<?php
				// Neutral placeholders until real logos are supplied.
				$brands = array( 'Procables', 'Centelsa', 'Siemens', 'Schneider', 'WEG' );
				foreach ( $brands as $b ) :
					?>
					<li class="su-brand"><span class="su-brand-ph"><?php echo esc_html( $b ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<!-- 6. INDUSTRIAS -->
	<section class="su-section su-industries">
		<div class="su-inner">
			<p class="su-eyebrow su-eyebrow-center">03 — Sectores</p>
			<h2 class="su-h2 su-h2-center">Industrias que atendemos</h2>
			<ul class="su-industry-row">
				<?php
				$inds = array( 'Construcción', 'Manufactura', 'Oil &amp; gas', 'Minería', 'Agroindustria', 'OEM / integradores' );
				foreach ( $inds as $i ) :
					echo '<li class="su-industry">' . wp_kses( $i, array() ) . '</li>';
				endforeach;
				?>
			</ul>
		</div>
	</section>

	<!-- 7. TRUST / ENTITY -->
	<section class="su-section su-band-navy su-trust">
		<div class="su-inner su-trust-inner">
			<span class="su-trust-keyline" aria-hidden="true"></span>
			<p class="su-entity"><?php echo esc_html( surtilec_entity_sentence() ); ?></p>
			<div class="su-trust-cta">
				<a class="su-btn su-btn-whatsapp" href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener">Escríbenos por WhatsApp</a>
				<a class="su-btn su-btn-ghost" href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">Contáctanos</a>
			</div>
		</div>
	</section>

	<!-- 8. FINAL CTA -->
	<section class="su-section su-band-accent su-finalcta">
		<div class="su-inner">
			<h2 class="su-finalcta-title">¿Listo para cotizar?</h2>
			<p class="su-finalcta-text">Envíanos tu solicitud y recibe precios y disponibilidad en menos de 1 hora hábil.</p>
			<div class="su-finalcta-buttons">
				<a class="su-btn su-btn-dark" href="<?php echo esc_url( home_url( '/cotizar/solicitud/' ) ); ?>">Cotizar ahora</a>
				<a class="su-btn su-btn-ghost-dark" href="<?php echo esc_url( $wa_link ); ?>" target="_blank" rel="noopener">WhatsApp</a>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();
