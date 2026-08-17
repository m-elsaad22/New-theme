<?php
/**
 * Testimonials.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec reviews"<?php echo mes_theme_visual_attrs( 'section:home.reviews' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="shead rv"><span class="tag"><?php esc_html_e( 'Reviews', 'mahmoud-elsaad' ); ?></span><h2<?php echo mes_theme_visual_attrs( 'element:home.reviews.heading' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'What clients say', 'mahmoud-elsaad' ); ?></h2></div>
		<div class="review-grid" id="rvTrack"<?php echo mes_theme_visual_attrs( 'component:home.reviews.list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			$q = new WP_Query( array( 'post_type' => 'mes_review', 'posts_per_page' => 6 ) );
			while ( $q->have_posts() ) :
				$q->the_post();
				$rating = (int) get_post_meta( get_the_ID(), '_mes_rating', true );
				?>
				<article class="rvcard2">
					<strong><?php echo esc_html( (string) get_post_meta( get_the_ID(), '_mes_customer_name', true ) ?: get_the_title() ); ?></strong>
					<div><?php echo esc_html( str_repeat( '★', max( 1, min( 5, $rating ?: 5 ) ) ) ); ?></div>
					<p><?php echo esc_html( get_the_excerpt() ); ?></p>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
