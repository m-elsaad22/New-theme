<?php
/**
 * Team.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec" id="team"<?php echo mes_theme_visual_attrs( 'section:home.team' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="shead rv"><span class="tag"><?php esc_html_e( 'Team', 'mahmoud-elsaad' ); ?></span><h2<?php echo mes_theme_visual_attrs( 'element:home.team.heading' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Experts you can trust', 'mahmoud-elsaad' ); ?></h2></div>
		<div class="team-grid"<?php echo mes_theme_visual_attrs( 'component:home.team.grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			$q = new WP_Query( array( 'post_type' => 'mes_team', 'posts_per_page' => 5 ) );
			while ( $q->have_posts() ) :
				$q->the_post();
				?>
				<article class="tcard rv">
					<?php echo mes_media( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<h3><?php the_title(); ?></h3>
					<p><?php echo esc_html( (string) get_post_meta( get_the_ID(), '_mes_position', true ) ); ?></p>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
