<?php
/**
 * Service card.
 *
 * @package MahmoudElsaad\Theme
 */
$benefits = json_decode( (string) get_post_meta( get_the_ID(), '_mes_benefits', true ), true );
?>
<article class="svc rv">
	<div class="svc-ic"><i class="fas fa-screwdriver-wrench"></i></div>
	<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
	<p class="desc"><?php echo esc_html( get_the_excerpt() ); ?></p>
	<?php if ( is_array( $benefits ) ) : ?>
		<ul>
			<?php foreach ( array_slice( $benefits, 0, 4 ) as $item ) : ?>
				<li><i class="fas fa-check"></i> <?php echo esc_html( is_array( $item ) ? ( $item['text'] ?? '' ) : (string) $item ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<a class="svc-cta" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View service', 'mahmoud-elsaad' ); ?> <i class="fas fa-arrow-left"></i></a>
</article>
