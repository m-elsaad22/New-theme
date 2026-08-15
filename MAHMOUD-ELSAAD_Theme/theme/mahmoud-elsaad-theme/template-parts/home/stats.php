<?php
/**
 * Stats.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec stats">
	<div class="wrap">
		<div class="shead rv"><h2><?php esc_html_e( 'Numbers that matter', 'mahmoud-elsaad' ); ?></h2></div>
		<div class="stats-grid">
			<div class="stat rv"><i class="fas fa-users"></i><div class="num" data-count="<?php echo esc_attr( (string) wp_count_posts( 'mes_review' )->publish ); ?>">0</div><div class="lbl"><?php esc_html_e( 'Reviews', 'mahmoud-elsaad' ); ?></div></div>
			<div class="stat rv"><i class="fas fa-briefcase"></i><div class="num" data-count="<?php echo esc_attr( (string) wp_count_posts( 'mes_portfolio' )->publish ); ?>">0</div><div class="lbl"><?php esc_html_e( 'Projects', 'mahmoud-elsaad' ); ?></div></div>
			<div class="stat rv"><i class="fas fa-map"></i><div class="num" data-count="<?php echo esc_attr( (string) wp_count_posts( 'mes_city' )->publish ); ?>">0</div><div class="lbl"><?php esc_html_e( 'Cities', 'mahmoud-elsaad' ); ?></div></div>
		</div>
	</div>
</section>
