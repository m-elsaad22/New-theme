<?php
/**
 * FAQ teaser.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec" id="faq"<?php echo mes_theme_visual_attrs( 'section:home.faq' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="shead rv"><span class="tag"><?php esc_html_e( 'FAQ', 'mahmoud-elsaad' ); ?></span><h2<?php echo mes_theme_visual_attrs( 'element:home.faq.heading' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Questions', 'mahmoud-elsaad' ); ?></h2></div>
		<div class="faq-wrap"<?php echo mes_theme_visual_attrs( 'component:home.faq.list' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			$q = new WP_Query( array( 'post_type' => 'mes_faq', 'posts_per_page' => 6 ) );
			while ( $q->have_posts() ) :
				$q->the_post();
				?>
				<div class="faq-item rv">
					<div class="faq-q" onclick="faqT(this)"><span><?php the_title(); ?></span><i class="fas fa-chevron-down"></i></div>
					<div class="faq-a"><?php the_content(); ?></div>
				</div>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
