<?php
/**
 * Template Name: Contact
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
?>
<section class="phero compact"><div class="wrap"><h1><?php the_title(); ?></h1></div></section>
<section class="sec"><div class="wrap contact-layout">
	<div class="cinfo-card">
		<?php
		echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'contact' ) ) : '';
		echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'contact' ) ) : '';
		?>
	</div>
	<div>
		<?php
		if ( class_exists( '\\MahmoudElsaad\\Core\\Forms\\Engine' ) ) {
			echo \MahmoudElsaad\Core\Forms\Engine::render( 'contact', array( 'submit' => __( 'Send', 'mahmoud-elsaad' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
</div></section>
<?php get_footer();