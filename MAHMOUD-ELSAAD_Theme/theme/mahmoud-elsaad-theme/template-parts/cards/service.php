<?php
/**
 * Service card.
 *
 * @package MahmoudElsaad\Theme
 */
$benefits = json_decode( (string) get_post_meta( get_the_ID(), '_mes_benefits', true ), true );
$icon = (string) get_post_meta( get_the_ID(), '_mes_icon', true );
$icon = $icon ?: 'fa-screwdriver-wrench';
if ( 0 !== strpos( $icon, 'fa-' ) ) {
	$icon = 'fa-screwdriver-wrench';
}
?>
<article class="svc rv">
	<div class="svc-ic"><i class="fas <?php echo esc_attr( $icon ); ?>"></i></div>
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
