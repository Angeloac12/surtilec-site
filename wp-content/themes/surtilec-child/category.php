<?php
/**
 * Surtilec — archivo de categoría de Recursos (blog). Misma identidad que el
 * índice (home.php): hero híbrido con migas, pestañas de categoría y rejilla
 * de tarjetas. Reemplaza la plantilla genérica de GeneratePress (que no traía
 * migas ni estilo de marca).
 *
 * @package Surtilec
 */

get_header();

$term         = get_queried_object();
$recursos_id  = (int) get_option( 'page_for_posts' );
$recursos_url = $recursos_id ? get_permalink( $recursos_id ) : home_url( '/recursos/' );

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
			<?php
			surtilec_breadcrumbs(
				array(
					array( 'label' => 'Recursos', 'url' => $recursos_url ),
					array( 'label' => $term instanceof WP_Term ? $term->name : single_cat_title( '', false ) ),
				)
			);
			?>
			<p class="su-eyebrow">Recursos</p>
			<h1><?php echo esc_html( $term instanceof WP_Term ? $term->name : single_cat_title( '', false ) ); ?></h1>
			<?php if ( $term instanceof WP_Term && '' !== trim( (string) $term->description ) ) : ?>
				<p class="su-page-sub"><?php echo esc_html( wp_strip_all_tags( $term->description ) ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="su-section su-band-light">
		<div class="su-inner">

			<?php if ( ! empty( $cats ) ) : ?>
				<nav class="su-blog-tabs" aria-label="Categorías de recursos">
					<a class="su-blog-tab" href="<?php echo esc_url( $recursos_url ); ?>">Todos</a>
					<?php
					foreach ( $cats as $cat ) :
						$is_active = ( $term instanceof WP_Term && (int) $cat->term_id === (int) $term->term_id );
						?>
						<a class="su-blog-tab<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
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
					<p>Aún no hay artículos en esta categoría.</p>
					<a class="su-btn su-btn-primary" href="<?php echo esc_url( $recursos_url ); ?>">Ver todos los recursos</a>
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
