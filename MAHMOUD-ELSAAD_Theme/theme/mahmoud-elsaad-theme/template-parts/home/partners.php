<?php
/**
 * Partners.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec brands"<?php echo mes_theme_visual_attrs( 'section:home.partners' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="shead rv"><h2<?php echo mes_theme_visual_attrs( 'element:home.partners.heading' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Partners', 'mahmoud-elsaad' ); ?></h2></div>
		<div class="brand-row"<?php echo mes_theme_visual_attrs( 'component:home.partners.row' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			$q = new WP_Query( array( 'post_type' => 'mes_partner', 'posts_per_page' => 12 ) );
			while ( $q->have_posts() ) :
				$q->the_post();
				echo '<div class="brand">' . mes_media( get_the_ID() ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
