<?php
/**
 * Template Name: Surtilec — Nosotros
 *
 * Página "Nosotros" full-bleed (dirección híbrida): hero oscuro con migas +
 * stat bar, presentación clara, cobertura (split) y banda CTA. El cuerpo se
 * edita por ACF (inc/about-fields.php) con defaults seguros. La frase de
 * entidad se comparte con el schema Organization (surtilec_entity_sentence()).
 *
 * @package Surtilec
 */

get_header();

// Defaults explícitos en plantilla (ACF default_value no se aplica en frontend
// para campos sin guardar — mismo patrón que surtilec_home_field en la portada).
$intro_default = "Surtilec es un distribuidor colombiano especializado en cables especiales (control, THHN/THWN-2, variadores VFD, instrumentación, encauchetados y apantallados) y en automatización industrial (variadores de frecuencia, PLC, HMI y sensores).\n\nTrabajamos con distribuidores, integradores, contratistas y áreas de mantenimiento que necesitan el producto correcto, con respaldo técnico y tiempos de respuesta cortos.";
$bullets_default = "Despachos a todo el país\nAsesoría técnica para selección de producto\nGestión de listados y cotizaciones por volumen";
$stat_defaults   = array(
	array( 'Bogotá', 'Centro de distribución' ),
	array( 'Nacional', 'Cobertura de despacho' ),
	array( '< 1 h', 'Respuesta en horario hábil' ),
	array( 'B2B', 'Industria, proyectos e integradores' ),
);

$eyebrow = surtilec_about_field( 'about_eyebrow', 'Nosotros' );
$heading = surtilec_about_field( 'about_heading', 'Distribuidor industrial de cables especiales y automatización' );
$lead    = surtilec_about_field( 'about_lead', 'Atendemos a la industria colombiana con producto técnico, asesoría y despachos a todo el país desde Bogotá.' );
$intro   = surtilec_about_field( 'about_intro', $intro_default );
$cov_t   = surtilec_about_field( 'about_coverage_title', 'Cobertura nacional desde Bogotá' );
$cov_x   = surtilec_about_field( 'about_coverage_text', 'Operamos desde Bogotá y despachamos a toda Colombia. Cotizamos en menos de 1 hora hábil y coordinamos el envío según tu proyecto.' );
$bullets = preg_split( '/\r\n|\r|\n/', (string) surtilec_about_field( 'about_coverage_bullets', $bullets_default ) );

$stats = array();
for ( $n = 1; $n <= 4; $n++ ) {
	$num = surtilec_about_field( "about_stat{$n}_num", $stat_defaults[ $n - 1 ][0] );
	if ( '' === $num ) {
		continue;
	}
	$stats[] = array(
		'num'   => $num,
		'label' => surtilec_about_field( "about_stat{$n}_label", $stat_defaults[ $n - 1 ][1] ),
	);
}
?>

<main id="primary" class="surtilec-page surtilec-about">

	<section class="su-section su-page-hero">
		<div class="su-inner">
			<?php surtilec_breadcrumbs( array( array( 'label' => 'Nosotros' ) ) ); ?>
			<p class="su-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<h1><?php echo esc_html( $heading ); ?></h1>
			<p class="su-page-sub"><?php echo esc_html( $lead ); ?></p>
			<?php surtilec_stat_bar( $stats ); ?>
		</div>
	</section>

	<section class="su-section su-band-light">
		<div class="su-inner su-prose">
			<?php echo wp_kses_post( wpautop( $intro ) ); ?>
			<p class="su-entity-line"><?php echo esc_html( surtilec_entity_sentence() ); ?></p>
		</div>
	</section>

	<section class="su-section su-band-gray">
		<div class="su-inner su-split">
			<div>
				<p class="su-eyebrow">Cobertura</p>
				<h2><?php echo esc_html( $cov_t ); ?></h2>
				<p><?php echo esc_html( $cov_x ); ?></p>
				<?php if ( array_filter( $bullets ) ) : ?>
					<ul class="su-checklist">
						<?php foreach ( $bullets as $b ) : ?>
							<?php if ( '' !== trim( $b ) ) : ?>
								<li><?php echo esc_html( trim( $b ) ); ?></li>
							<?php endif; ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<a class="su-btn su-btn-primary" href="<?php echo esc_url( home_url( '/cotizar/solicitud/' ) ); ?>">Cotizar ahora</a>
			</div>
			<div class="su-split-media" aria-hidden="true"></div>
		</div>
	</section>

	<?php
	surtilec_cta_band(
		array(
			'title'         => '¿Trabajamos juntos?',
			'text'          => 'Cuéntanos qué necesitas y recibe cotización en menos de 1 hora hábil.',
			'primary_label' => 'Cotizar ahora',
		)
	);
	?>

</main>

<?php
get_footer();
