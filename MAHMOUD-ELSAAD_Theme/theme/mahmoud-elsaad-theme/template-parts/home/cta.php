<?php
/**
 * Final CTA.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec fcta" id="contact">
	<div class="wrap">
		<h2 class="rv"><?php esc_html_e( 'Ready when you are — 24/7', 'mahmoud-elsaad' ); ?></h2>
		<p class="rv"><?php echo esc_html( function_exists( 'mes_brand_tagline' ) ? mes_brand_tagline() : '' ); ?></p>
		<div class="fcta-btns rv">
			<?php
			echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'cta' ) ) : '';
			echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'cta' ) ) : '';
			?>
		</div>
	</div>
</section>
