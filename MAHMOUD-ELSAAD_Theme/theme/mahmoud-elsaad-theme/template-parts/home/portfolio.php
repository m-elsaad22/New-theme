<?php
/**
 * Portfolio teaser.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec" id="projects"<?php echo mes_theme_visual_attrs( 'section:home.portfolio' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="shead rv"><span class="tag"><?php esc_html_e( 'Portfolio', 'mahmoud-elsaad' ); ?></span><h2><?php esc_html_e( 'Recent work', 'mahmoud-elsaad' ); ?></h2></div>
		<div class="gal-grid">
			<?php
			$q = new WP_Query( array( 'post_type' => 'mes_portfolio', 'posts_per_page' => 6 ) );
			while ( $q->have_posts() ) :
				$q->the_post();
				?>
				<a class="gal-item filt" href="<?php the_permalink(); ?>">
					<?php echo mes_media( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><?php the_title(); ?></span>
				</a>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
