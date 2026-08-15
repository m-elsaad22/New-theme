<?php
/**
 * Single service.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
?>
<section class="phero compact">
	<div class="wrap">
		<h1><?php the_title(); ?></h1>
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
