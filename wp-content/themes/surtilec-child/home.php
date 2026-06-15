<?php
/**
 * Surtilec — índice de Recursos (blog). Usado por la página de entradas
 * (page_for_posts = "Recursos"). Hero + pestañas de categoría + rejilla de
 * tarjetas + paginación numerada. Look híbrido (full-bleed vía su-fullbleed,
 * añadido por el filtro body_class en inc/parts.php).
 *
 * @package Surtilec
 */

get_header();

$cats = get_categories(
	array(
		'hide_empty' => false,
		'exclude'    => array( (int) get_option( 'default_category' ) ),
		'number'     => 6,
	)
);
?>

<main id="primary" class="surtilec-page surtilec-blog">

	<section class="su-section su-page-hero">
		<div class="su-inner">
			<?php surtilec_breadcrumbs( array( array( 'label' => 'Recursos' ) ) ); ?>
			<p class="su-eyebrow">Recursos</p>
			<h1>Guías y referencia técnica</h1>
			<p class="su-page-sub">Comparativas de producto, normativa y criterios de selección para distribuidores, integradores y áreas de mantenimiento.</p>
		</div>
	</section>

	<section class="su-section su-band-light">
		<div class="su-inner">

			<?php if ( ! empty( $cats ) ) : ?>
				<nav class="su-blog-tabs" aria-label="Categorías de recursos">
					<a class="su-blog-tab is-active" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>">Todos</a>
					<?php foreach ( $cats as $cat ) : ?>
						<a class="su-blog-tab" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>
				<div class="su-post-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'partials/resource-card' );
					endwhile;
					?>
				</div>

				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 1,
						'prev_text' => '← Anteriores',
						'next_text' => 'Siguientes →',
						'class'     => 'su-pagination',
					)
				);
				?>

			<?php else : ?>
				<div class="su-blog-empty">
					<p>Pronto publicaremos guías y material técnico. Mientras tanto, ¿tienes una consulta puntual?</p>
					<a class="su-btn su-btn-primary" href="<?php echo esc_url( home_url( '/cotizar/solicitud/' ) ); ?>">Escríbenos</a>
				</div>
			<?php endif; ?>

		</div>
	</section>

	<?php
	surtilec_cta_band(
		array(
			'title'         => '¿Necesitas asesoría para tu proyecto?',
			'text'          => 'Cotiza con nuestro equipo técnico y recibe respuesta en menos de 1 hora hábil.',
			'primary_label' => 'Cotizar ahora',
		)
	);
	?>

</main>

<?php
get_footer();
