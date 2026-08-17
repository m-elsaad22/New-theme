<?php
/**
 * Single service.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
?>
<section class="phero compact"<?php echo mes_theme_visual_attrs( 'section:single-service.hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap"<?php echo mes_theme_visual_attrs( 'component:single-service.hero.copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<h1<?php echo mes_theme_visual_attrs( 'element:single-service.hero.title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php the_title(); ?></h1>
		<div class="hero-ctas">
			<?php
			echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'service' ) ) : '';
			echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'service' ) ) : '';
			?>
		</div>
	</div>
</section>
<section class="sec">
	<div class="wrap article-layout">
		<div class="article-body prose">
			<?php echo mes_media( get_the_ID(), 'mes-hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php the_content(); ?>
		</div>
		<aside class="side-w">
			<?php echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'service-sidebar' ) ) : ''; ?>
		</aside>
	</div>
</section>
<?php
get_footer();
