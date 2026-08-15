<?php
/**
 * Final CTA.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec fcta" id="contact"<?php echo mes_theme_visual_attrs( 'section:home.cta' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap"<?php echo mes_theme_visual_attrs( 'component:home.cta.box' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<h2 class="rv"<?php echo mes_theme_visual_attrs( 'element:home.cta.heading' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php esc_html_e( 'Ready when you are — 24/7', 'mahmoud-elsaad' ); ?></h2>
		<p class="rv"><?php echo esc_html( function_exists( 'mes_brand_tagline' ) ? mes_brand_tagline() : '' ); ?></p>
		<div class="fcta-btns rv">
			<?php
			echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'cta' ) ) : '';
			echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'cta' ) ) : '';
			?>
		</div>
	</div>
</section>
