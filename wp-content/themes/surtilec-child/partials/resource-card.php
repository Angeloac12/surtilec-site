<?php
/**
 * Tarjeta de recurso (entrada) — usada en el índice de Recursos y en
 * "También te puede interesar". Espera estar dentro del loop.
 *
 * @package Surtilec
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cats = get_the_category();
$tag  = ! empty( $cats ) ? $cats[0] : null;
?>
<article class="su-post-card">
	<a class="su-post-card-link" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<span class="su-post-card-img"><?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?></span>
		<?php endif; ?>
		<span class="su-post-card-body">
			<?php if ( $tag ) : ?>
				<span class="su-post-card-tag"><?php echo esc_html( $tag->name ); ?></span>
			<?php endif; ?>
			<span class="su-post-card-title"><?php the_title(); ?></span>
			<span class="su-post-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></span>
			<span class="su-post-card-meta">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<span aria-hidden="true">·</span>
				<span><?php echo esc_html( surtilec_read_time() ); ?> min de lectura</span>
			</span>
		</span>
	</a>
</article>
