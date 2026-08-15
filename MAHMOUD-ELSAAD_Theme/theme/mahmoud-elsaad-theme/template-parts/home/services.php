<?php
/**
 * Services grid.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec" id="services">
	<div class="wrap">
		<div class="shead rv">
			<span class="tag"><?php esc_html_e( 'Services', 'mahmoud-elsaad' ); ?></span>
			<h2><?php esc_html_e( 'Our services', 'mahmoud-elsaad' ); ?></h2>
		</div>
		<div class="services-grid">
			<?php
			$q = new WP_Query( array( 'post_type' => 'service', 'posts_per_page' => 6 ) );
			while ( $q->have_posts() ) :
				$q->the_post();
				get_template_part( 'template-parts/cards/service' );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
