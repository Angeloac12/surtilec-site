<?php
/**
 * Template Name: Surtilec — Industrias (índice)
 *
 * Índice del eje "Industrias" (full-bleed): hero oscuro + rejilla de tarjetas
 * por sector que enrutan a las landings /industrias/{slug}/. Schema ItemList +
 * BreadcrumbList (vía surtilec_breadcrumbs).
 *
 * @package Surtilec
 */

get_header();

/* Sectores atendidos (coinciden con las landings hijas y la portada). */
$industries = array(
	array( 'construccion', 'Construcción', 'Cableado y conducción para obra e infraestructura eléctrica.' ),
	array( 'manufactura', 'Manufactura', 'Control, potencia y automatización para planta y maquinaria.' ),
	array( 'oil-gas', 'Oil & gas', 'Cables para ambientes exigentes e instrumentación de proceso.' ),
	array( 'mineria', 'Minería', 'Encauchetados y cables robustos para operación pesada.' ),
	array( 'agroindustria', 'Agroindustria', 'Energía, control y automatización para procesos agroindustriales.' ),
	array( 'oem', 'OEM / integradores', 'Suministro técnico para tableristas e integradores de equipos.' ),
);
?>

<main id="primary" class="surtilec-page surtilec-industrias">

	<section class="su-section su-page-hero">
		<div class="su-inner">
			<?php surtilec_breadcrumbs( array( array( 'label' => 'Industrias' ) ) ); ?>
			<p class="su-eyebrow">Industrias</p>
			<h1>Soluciones por sector industrial</h1>
			<p class="su-page-sub">Cables especiales y automatización para los sectores que mueven a Colombia. Encuentra el producto correcto para tu industria.</p>
		</div>
	</section>

	<section class="su-section su-band-light">
		<div class="su-inner">
			<div class="su-card-grid cols-3">
				<?php
				$schema_items = array();
				foreach ( $industries as $ind ) :
					$url            = home_url( '/industrias/' . $ind[0] . '/' );
					$schema_items[] = array( 'name' => $ind[1], 'url' => $url );
					?>
					<a class="su-card" href="<?php echo esc_url( $url ); ?>">
						<span class="su-card-icon"><?php echo surtilec_icon( $ind[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<h2 class="su-card-title"><?php echo esc_html( $ind[1] ); ?></h2>
						<p class="su-card-text"><?php echo esc_html( $ind[2] ); ?></p>
						<span class="su-card-go" aria-hidden="true">Ver soluciones →</span>
					</a>
				<?php endforeach; ?>
			</div>
			<?php surtilec_itemlist_schema( $schema_items ); ?>
		</div>
	</section>

	<?php
	surtilec_cta_band(
		array(
			'title'         => '¿No ves tu sector?',
			'text'          => 'Cuéntanos tu aplicación y te asesoramos con el producto adecuado.',
			'primary_label' => 'Cotizar ahora',
		)
	);
	?>

</main>

<?php
get_footer();
