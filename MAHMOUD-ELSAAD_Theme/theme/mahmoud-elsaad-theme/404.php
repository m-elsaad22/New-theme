<?php
/**
 * 404 — real page, no homepage redirect.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="err-wrap">
	<div class="wrap">
		<p class="err-code">404</p>
		<h1><?php esc_html_e( 'Page not found', 'mahmoud-elsaad' ); ?></h1>
		<p><?php esc_html_e( 'The page you requested does not exist.', 'mahmoud-elsaad' ); ?></p>
		<p>
			<a class="btn btn-call" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'mahmoud-elsaad' ); ?></a>
			<?php echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => '404' ) ) : ''; ?>
		</p>
	</div>
</section>
<?php
get_footer();
