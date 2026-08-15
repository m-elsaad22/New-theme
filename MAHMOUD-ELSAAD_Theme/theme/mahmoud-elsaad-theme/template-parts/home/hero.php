<?php
/**
 * Hero.
 *
 * @package MahmoudElsaad\Theme
 */

$name = function_exists( 'mes_brand_name' ) ? mes_brand_name() : get_bloginfo( 'name' );
$tag  = function_exists( 'mes_brand_tagline' ) ? mes_brand_tagline() : get_bloginfo( 'description' );
?>
<section class="hero" id="home"<?php echo mes_theme_visual_attrs( 'section:home.hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="hero-grid-bg"></div>
	<div id="particles"></div>
	<div class="wrap">
		<div class="hero-copy"<?php echo mes_theme_visual_attrs( 'component:home.hero.copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<h1<?php echo mes_theme_visual_attrs( 'element:home.hero.title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $name ); ?> — <?php echo $tag ? wp_kses_post( $tag ) : esc_html__( 'Integrated home services', 'mahmoud-elsaad' ); ?></h1>
			<p class="sub"<?php echo mes_theme_visual_attrs( 'element:home.hero.lead' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( get_the_excerpt() ?: __( 'Certified teams, modern equipment, and written warranties.', 'mahmoud-elsaad' ) ); ?></p>
			<div class="hero-ctas"<?php echo mes_theme_visual_attrs( 'element:home.hero.cta' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php
				echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'hero' ) ) : '';
				echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'hero' ) ) : '';
				?>
				<a href="#contact" class="btn btn-quote"><i class="fas fa-file-invoice-dollar"></i> <?php esc_html_e( 'Request a quote', 'mahmoud-elsaad' ); ?></a>
			</div>
		</div>
		<div class="dash rv-l"<?php echo mes_theme_visual_attrs( 'component:home.hero.media' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="dash-top">
				<span class="ttl"><i class="fas fa-chart-line" style="color:var(--aqua)"></i> <?php echo esc_html( $name ); ?></span>
				<span class="live"><b></b> <?php esc_html_e( 'Live', 'mahmoud-elsaad' ); ?></span>
			</div>
			<div class="dash-mini">
				<?php
				$services = get_posts( array( 'post_type' => 'service', 'posts_per_page' => 6 ) );
				foreach ( $services as $svc ) {
					echo '<div class="mini"><i class="fas fa-check"></i><span>' . esc_html( get_the_title( $svc ) ) . '</span></div>';
				}
				?>
			</div>
		</div>
	</div>
</section>
