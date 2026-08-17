<?php
/**
 * Service × city landing.
 *
 * @package MahmoudElsaad\Theme
 */

$service_slug = sanitize_title( (string) get_query_var( 'mes_service_slug' ) );
$city_slug    = sanitize_title( (string) get_query_var( 'mes_city_slug' ) );
$row          = class_exists( '\\MahmoudElsaad\\Core\\Relations\\ServiceCity' )
	? \MahmoudElsaad\Core\Relations\ServiceCity::resolve( $service_slug, $city_slug )
	: null;

if ( ! $row ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	return;
}

$data = \MahmoudElsaad\Core\Relations\ServiceCity::landing( $row );
get_header();
?>
<section class="phero compact"<?php echo mes_theme_visual_attrs( 'section:service-city.hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap"<?php echo mes_theme_visual_attrs( 'component:service-city.hero.copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<h1<?php echo mes_theme_visual_attrs( 'element:service-city.hero.title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $data['title'] ); ?></h1>
		<p><?php echo esc_html( $data['excerpt'] ); ?></p>
		<div class="hero-ctas">
			<?php
			echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'service-city', 'number' => $data['whatsapp'] ) ) : '';
			echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'service-city', 'number' => $data['phone'] ) ) : '';
			?>
		</div>
	</div>
</section>
<section class="sec">
	<div class="wrap article-layout">
		<div class="article-body prose"><?php echo wp_kses_post( wpautop( $data['content'] ) ); ?></div>
	</div>
</section>
<?php
get_footer();
