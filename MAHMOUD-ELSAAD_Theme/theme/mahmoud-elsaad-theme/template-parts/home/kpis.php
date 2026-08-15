<?php
/**
 * KPI strip.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<div class="wrap atb-wrap"<?php echo mes_theme_visual_attrs( 'section:home.kpis' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="atb rv">
		<div class="atb-item"><i class="fas fa-briefcase"></i><div><b data-count="<?php echo esc_attr( (string) wp_count_posts( 'mes_portfolio' )->publish ); ?>">0</b><small><?php esc_html_e( 'Projects', 'mahmoud-elsaad' ); ?></small></div></div>
		<div class="atb-item"><i class="fas fa-users"></i><div><b data-count="<?php echo esc_attr( (string) wp_count_posts( 'mes_review' )->publish ); ?>">0</b><small><?php esc_html_e( 'Reviews', 'mahmoud-elsaad' ); ?></small></div></div>
		<div class="atb-item"><i class="fas fa-map-location-dot"></i><div><b><?php echo esc_html( (string) wp_count_posts( 'mes_city' )->publish ); ?></b><small><?php esc_html_e( 'Cities', 'mahmoud-elsaad' ); ?></small></div></div>
		<div class="atb-item"><i class="fas fa-screwdriver-wrench"></i><div><b><?php echo esc_html( (string) wp_count_posts( 'service' )->publish ); ?></b><small><?php esc_html_e( 'Services', 'mahmoud-elsaad' ); ?></small></div></div>
		<div class="atb-item"><i class="fas fa-headset"></i><div><b>24/7</b><small><?php esc_html_e( 'Support', 'mahmoud-elsaad' ); ?></small></div></div>
	</div>
</div>
