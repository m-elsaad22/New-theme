<?php
/**
 * Blog teaser.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec" id="blog">
	<div class="wrap">
		<div class="shead rv"><span class="tag"><?php esc_html_e( 'Blog', 'mahmoud-elsaad' ); ?></span><h2><?php esc_html_e( 'Latest articles', 'mahmoud-elsaad' ); ?></h2></div>
		<div class="blog-grid">
			<?php
			$q = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 3 ) );
			while ( $q->have_posts() ) :
				$q->the_post();
				get_template_part( 'template-parts/cards/article' );
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
