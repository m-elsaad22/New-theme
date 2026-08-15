<?php
/**
 * Cities.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec" id="areas">
	<div class="wrap">
		<div class="shead rv"><span class="tag"><?php esc_html_e( 'Coverage', 'mahmoud-elsaad' ); ?></span><h2><?php esc_html_e( 'Cities we serve', 'mahmoud-elsaad' ); ?></h2></div>
		<div class="area-cards">
			<?php
			$q = new WP_Query( array( 'post_type' => 'mes_city', 'posts_per_page' => 8 ) );
			while ( $q->have_posts() ) :
				$q->the_post();
				?>
				<a class="acard rv" href="<?php the_permalink(); ?>">
					<div class="ah"><i class="fas fa-city"></i><div><b><?php the_title(); ?></b><small><?php echo esc_html( get_the_excerpt() ); ?></small></div></div>
				</a>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
