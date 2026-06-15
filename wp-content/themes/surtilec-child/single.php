<?php
/**
 * Surtilec — entrada de Recursos (artículo). Layout 70/30: cuerpo + sidebar
 * sticky con tabla de contenidos (generada por surtilec-toc.js) y mini-CTA.
 * Migas + meta semántica (<time>) + Article JSON-LD. Look híbrido full-bleed.
 *
 * @package Surtilec
 */

get_header();

while ( have_posts() ) :
	the_post();

	$cats        = get_the_category();
	$tag         = ! empty( $cats ) ? $cats[0] : null;
	$mins        = surtilec_read_time();
	$author      = get_the_author();
	$recursos_id = (int) get_option( 'page_for_posts' );
	$recursos_url = $recursos_id ? get_permalink( $recursos_id ) : home_url( '/recursos/' );
	?>

	<main id="primary" class="surtilec-page surtilec-article">

		<section class="su-section su-band-light">
			<div class="su-inner">

				<?php
				surtilec_breadcrumbs(
					array(
						array( 'label' => 'Recursos', 'url' => $recursos_url ),
						array( 'label' => get_the_title() ),
					)
				);
				?>

				<header class="su-article-head">
					<?php if ( $tag ) : ?>
						<a class="su-post-card-tag" href="<?php echo esc_url( get_category_link( $tag ) ); ?>"><?php echo esc_html( $tag->name ); ?></a>
					<?php endif; ?>
					<h1 class="su-article-title"><?php the_title(); ?></h1>
					<p class="su-article-meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<span aria-hidden="true">·</span>
						<span><?php echo esc_html( $author ); ?></span>
						<span aria-hidden="true">·</span>
						<span><?php echo esc_html( $mins ); ?> min de lectura</span>
					</p>
				</header>

				<div class="su-article-layout">
					<article class="su-article-body" data-toc-root>
						<?php
						if ( has_post_thumbnail() ) {
							echo '<figure class="su-article-hero">' . get_the_post_thumbnail( get_the_ID(), 'large', array( 'loading' => 'eager' ) ) . '</figure>';
						}
						the_content();
						?>
					</article>

					<aside class="su-article-aside">
						<nav class="su-toc" data-toc aria-label="Contenido del artículo" hidden>
							<p class="su-toc-head">En este artículo</p>
							<ol class="su-toc-list"></ol>
						</nav>
						<div class="su-toc-cta">
							<p class="su-toc-cta-text">¿Necesitas cotizar?</p>
							<a class="su-btn su-btn-primary" href="<?php echo esc_url( home_url( '/cotizar/solicitud/' ) ); ?>">Cotizar ahora</a>
						</div>
					</aside>
				</div>

			</div>
		</section>

		<?php
		// "También te puede interesar" — hasta 3 entradas relacionadas.
		$related = array();
		if ( $tag ) {
			$related = get_posts(
				array(
					'category'       => $tag->term_id,
					'post__not_in'   => array( get_the_ID() ),
					'posts_per_page' => 3,
					'orderby'        => 'date',
				)
			);
		}
		if ( ! empty( $related ) ) :
			?>
			<section class="su-section su-band-gray">
				<div class="su-inner">
					<h2>También te puede interesar</h2>
					<div class="su-post-grid">
						<?php
						foreach ( $related as $rel ) :
							setup_postdata( $GLOBALS['post'] = $rel ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
							get_template_part( 'partials/resource-card' );
						endforeach;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
			<?php
		endif;

		surtilec_cta_band(
			array(
				'title'         => '¿Listo para cotizar?',
				'text'          => 'Envíanos tu solicitud y recibe precios y disponibilidad en menos de 1 hora hábil.',
				'primary_label' => 'Cotizar ahora',
			)
		);

		// Article JSON-LD (publisher = Organization site-wide del mu-plugin).
		$article_schema = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'Article',
			'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => get_permalink() ),
			'headline'         => get_the_title(),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'author'           => array( '@type' => 'Person', 'name' => $author ),
			'publisher'        => array( '@id' => home_url( '/#organization' ) ),
		);
		$excerpt = wp_strip_all_tags( get_the_excerpt() );
		if ( $excerpt ) {
			$article_schema['description'] = $excerpt;
		}
		if ( has_post_thumbnail() ) {
			$img = wp_get_attachment_image_url( get_post_thumbnail_id(), 'large' );
			if ( $img ) {
				$article_schema['image'] = $img;
			}
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $article_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
		?>

	</main>

	<?php
endwhile;

get_footer();
