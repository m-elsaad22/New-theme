<?php
/**
 * Service finder.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec finder-sec" id="finder"<?php echo mes_theme_visual_attrs( 'section:home.finder' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="shead rv">
			<span class="tag"><?php esc_html_e( 'Start now', 'mahmoud-elsaad' ); ?></span>
			<h2><?php esc_html_e( 'Which service do you need?', 'mahmoud-elsaad' ); ?></h2>
		</div>
		<div class="finder rv">
			<div class="finder-step">
				<label><span class="snum">1</span> <?php esc_html_e( 'Service', 'mahmoud-elsaad' ); ?></label>
				<div class="sel"><i class="fas fa-screwdriver-wrench"></i>
					<select id="fnSvc" aria-label="<?php esc_attr_e( 'Service', 'mahmoud-elsaad' ); ?>">
						<?php foreach ( get_posts( array( 'post_type' => 'service', 'posts_per_page' => 50 ) ) as $svc ) : ?>
							<option value="<?php echo esc_attr( $svc->post_name ); ?>" data-id="<?php echo esc_attr( (string) $svc->ID ); ?>" data-slug="<?php echo esc_attr( $svc->post_name ); ?>"><?php echo esc_html( get_the_title( $svc ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="finder-step">
				<label><span class="snum">2</span> <?php esc_html_e( 'City', 'mahmoud-elsaad' ); ?></label>
				<div class="sel"><i class="fas fa-location-dot"></i>
					<select id="fnCity" aria-label="<?php esc_attr_e( 'City', 'mahmoud-elsaad' ); ?>">
						<?php foreach ( get_posts( array( 'post_type' => 'mes_city', 'posts_per_page' => 50 ) ) as $city ) : ?>
							<option value="<?php echo esc_attr( $city->post_name ); ?>" data-slug="<?php echo esc_attr( $city->post_name ); ?>" data-time="<?php echo esc_attr( (string) get_post_meta( $city->ID, '_mes_response_time', true ) ); ?>"><?php echo esc_html( get_the_title( $city ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="finder-step finder-go">
				<button class="btn btn-quote" id="fnBtn" type="button"><i class="fas fa-bolt"></i> <?php esc_html_e( 'Get a quote', 'mahmoud-elsaad' ); ?></button>
			</div>
		</div>
		<div class="finder-result" id="fnResult" hidden>
			<div class="fr-lead"><b id="frTitle"></b><small id="frSub"></small></div>
			<div class="fr-stat"><i class="fas fa-clock"></i><b id="frTime"></b></div>
		</div>
		<div style="text-align:center;margin-top:18px" class="rv">
			<?php echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'finder' ) ) : ''; ?>
		</div>
	</div>
</section>
