<?php
/**
 * Pricing guides from offers.
 *
 * @package MahmoudElsaad\Theme
 */
$q = new WP_Query( array( 'post_type' => 'mes_offer', 'posts_per_page' => 6 ) );
if ( ! $q->have_posts() ) {
	return;
}
?>
<section class="sec" id="pricing">
	<div class="wrap">
		<div class="shead rv">
			<span class="tag"><?php esc_html_e( 'Pricing', 'mahmoud-elsaad' ); ?></span>
			<h2><?php esc_html_e( 'Price guides', 'mahmoud-elsaad' ); ?></h2>
		</div>
		<div class="price-grid">
			<?php
			while ( $q->have_posts() ) :
				$q->the_post();
				$price = (string) get_post_meta( get_the_ID(), '_mes_price', true );
				?>
				<article class="pcard rv">
					<div class="pic"><i class="fas fa-tag"></i></div>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php if ( $price ) : ?>
						<div class="range"><b><?php echo esc_html( $price ); ?></b></div>
					<?php endif; ?>
					<a class="read" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read guide', 'mahmoud-elsaad' ); ?> <i class="fas fa-arrow-left"></i></a>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
