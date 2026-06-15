<?php
/**
 * Template Name: Surtilec — Industria (landing)
 *
 * Landing de un sector (full-bleed): hero con migas, rejilla de categorías de
 * producto relevantes (desde ACF; vacío = todas las líneas) y bloque "Por qué
 * Surtilec". Schema ItemList (categorías) + BreadcrumbList (vía breadcrumbs).
 *
 * @package Surtilec
 */

get_header();

$name    = get_the_title();
$subhead = surtilec_industria_field( 'industria_subhead', sprintf( 'Producto técnico, asesoría y despacho nacional para el sector %s.', $name ) );
$intro   = surtilec_industria_field( 'industria_intro', sprintf( 'Estas son las líneas de cables especiales y automatización que más se utilizan en %s. ¿No encuentras lo que buscas? Escríbenos y te cotizamos.', $name ) );

$why_title   = surtilec_industria_field( 'industria_why_title', 'Por qué Surtilec para tu sector' );
$why_default = "Producto técnico para las exigencias del sector\nAsesoría en selección de calibre, norma y aplicación\nCotización en menos de 1 hora hábil y despacho a toda Colombia";
$why_bullets = preg_split( '/\r\n|\r|\n/', (string) surtilec_industria_field( 'industria_why_bullets', $why_default ) );

/* Categorías relevantes (ACF). Vacío => todas las líneas pilar reales. */
$cat_ids = function_exists( 'get_field' ) ? get_field( 'industria_categorias' ) : array();
if ( ! empty( $cat_ids ) ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'include'    => array_map( 'intval', (array) $cat_ids ),
			'hide_empty' => false,
			'orderby'    => 'include',
		)
	);
} else {
	$terms = function_exists( 'surtilec_cached_terms' )
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
}
if ( is_wp_error( $terms ) ) {
	$terms = array();
}
?>

<main id="primary" class="surtilec-page surtilec-industria">

	<section class="su-section su-page-hero">
		<div class="su-inner">
			<?php
			surtilec_breadcrumbs(
				array(
					array( 'label' => 'Industrias', 'url' => home_url( '/industrias/' ) ),
					array( 'label' => $name ),
				)
			);
			?>
			<p class="su-eyebrow">Industria</p>
			<h1><?php echo esc_html( $name ); ?></h1>
			<p class="su-page-sub"><?php echo esc_html( $subhead ); ?></p>
			<a class="su-btn su-btn-primary" href="<?php echo esc_url( home_url( '/cotizar/solicitud/' ) ); ?>">Cotizar para <?php echo esc_html( $name ); ?></a>
		</div>
	</section>

	<section class="su-section su-band-light">
		<div class="su-inner">
			<p class="su-eyebrow">Productos para el sector</p>
			<h2>Cables y equipos para <?php echo esc_html( $name ); ?></h2>
			<p class="su-prose"><?php echo esc_html( $intro ); ?></p>
			<?php if ( ! empty( $terms ) ) : ?>
				<ul class="surtilec-tiles surtilec-pillar-tiles">
					<?php
					$schema_items = array();
					foreach ( $terms as $term ) {
						surtilec_render_term_tile( $term );
						$schema_items[] = array( 'name' => $term->name, 'url' => get_term_link( $term ) );
					}
					?>
				</ul>
				<?php surtilec_itemlist_schema( $schema_items ); ?>
			<?php endif; ?>
		</div>
	</section>

	<section class="su-section su-band-gray">
		<div class="su-inner su-split">
			<div>
				<p class="su-eyebrow">Respaldo</p>
				<h2><?php echo esc_html( $why_title ); ?></h2>
				<?php if ( array_filter( $why_bullets ) ) : ?>
					<ul class="su-checklist">
						<?php foreach ( $why_bullets as $b ) : ?>
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
			'title'         => sprintf( '¿Listo para cotizar en %s?', $name ),
			'text'          => 'Envíanos tu solicitud o tu listado y recibe precios y disponibilidad en menos de 1 hora hábil.',
			'primary_label' => 'Cotizar ahora',
		)
	);
	?>

</main>

<?php
get_footer();
