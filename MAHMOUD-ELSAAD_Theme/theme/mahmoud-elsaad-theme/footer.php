<?php
/**
 * Footer — HTML source of truth.
 *
 * @package MahmoudElsaad\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$contact = function_exists( 'mes_get_option' ) ? mes_get_option( 'mes_contact_settings', array() ) : array();
$social  = $contact['social'] ?? array();
?>
</main>
<footer>
	<div class="wrap">
		<div class="fgrid">
			<div class="fcol">
				<div class="flogo">
					<span class="mark" aria-hidden="true"><i class="fas fa-shield-halved"></i></span>
					<?php echo esc_html( function_exists( 'mes_brand_name' ) ? mes_brand_name() : get_bloginfo( 'name' ) ); ?>
				</div>
				<p><?php echo esc_html( function_exists( 'mes_brand_tagline' ) ? mes_brand_tagline() : get_bloginfo( 'description' ) ); ?></p>
				<div class="fcontact">
					<?php
					echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'footer', 'class' => '' ) ) : '';
					echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'footer', 'class' => '' ) ) : '';
					if ( ! empty( $contact['address'] ) ) {
						echo '<span><i class="fas fa-location-dot"></i> ' . esc_html( $contact['address'] ) . '</span>';
					}
					?>
				</div>
				<div class="fsocial">
					<?php
					$icons = array(
						'instagram' => 'fab fa-instagram',
						'facebook'  => 'fab fa-facebook-f',
						'youtube'   => 'fab fa-youtube',
						'tiktok'    => 'fab fa-tiktok',
						'linkedin'  => 'fab fa-linkedin-in',
						'x'         => 'fab fa-x-twitter',
						'telegram'  => 'fab fa-telegram',
						'snapchat'  => 'fab fa-snapchat',
					);
					foreach ( $icons as $net => $icon ) {
						if ( empty( $social[ $net ] ) ) {
							continue;
						}
						printf(
							'<a href="%s" aria-label="%s" rel="noopener" target="_blank"><i class="%s"></i></a>',
							esc_url( $social[ $net ] ),
							esc_attr( $net ),
							esc_attr( $icon )
						);
					}
					?>
				</div>
			</div>
			<div class="fcol">
				<h3><?php esc_html_e( 'Services', 'mahmoud-elsaad' ); ?></h3>
				<ul>
					<?php
					$services = get_posts( array( 'post_type' => 'service', 'posts_per_page' => 6 ) );
					foreach ( $services as $svc ) {
						echo '<li><a href="' . esc_url( get_permalink( $svc ) ) . '"><i class="fas fa-chevron-left"></i> ' . esc_html( get_the_title( $svc ) ) . '</a></li>';
					}
					?>
				</ul>
			</div>
			<div class="fcol">
				<h3><?php esc_html_e( 'Cities', 'mahmoud-elsaad' ); ?></h3>
				<ul>
					<?php
					$cities = get_posts( array( 'post_type' => 'mes_city', 'posts_per_page' => 7 ) );
					foreach ( $cities as $city ) {
						echo '<li><a href="' . esc_url( get_permalink( $city ) ) . '"><i class="fas fa-chevron-left"></i> ' . esc_html( get_the_title( $city ) ) . '</a></li>';
					}
					?>
				</ul>
			</div>
			<div class="fcol">
				<h3><?php esc_html_e( 'Quick links', 'mahmoud-elsaad' ); ?></h3>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'items_wrap'     => '<ul>%3$s</ul>',
						'fallback_cb'    => false,
					)
				);
				echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'footer', 'class' => 'btn btn-quote', 'label' => __( 'Talk to an expert', 'mahmoud-elsaad' ), 'icon' => 'fas fa-headset' ) ) : '';
				?>
			</div>
		</div>
		<?php if ( ! empty( $contact['map_embed'] ) ) : ?>
			<div class="fmap">
				<div class="fmap-addr-row">
					<i class="fas fa-location-dot"></i>
					<div><b><?php esc_html_e( 'Headquarters', 'mahmoud-elsaad' ); ?></b><span><?php echo esc_html( $contact['address'] ?? '' ); ?></span></div>
				</div>
				<?php echo wp_kses_post( $contact['map_embed'] ); ?>
			</div>
		<?php endif; ?>
		<div class="fbottom">
			© <?php echo esc_html( (string) wp_date( 'Y' ) ); ?> <?php echo esc_html( function_exists( 'mes_brand_name' ) ? mes_brand_name() : get_bloginfo( 'name' ) ); ?>.
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'legal',
					'container'      => false,
					'items_wrap'     => ' %3$s',
					'fallback_cb'    => false,
					'depth'          => 1,
				)
			);
			?>
		</div>
	</div>
</footer>
<div class="fab-stack" id="fabStack">
	<?php
	echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'floating', 'class' => 'fab-btn fab-call', 'label' => '', 'icon' => 'fas fa-phone' ) ) : '';
	echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'floating', 'class' => 'fab-btn fab-wa', 'label' => '', 'icon' => 'fab fa-whatsapp' ) ) : '';
	?>
</div>
<?php wp_footer(); ?>
</body>
</html>
