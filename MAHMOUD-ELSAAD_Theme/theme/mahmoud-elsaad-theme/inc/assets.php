<?php
/**
 * Frontend assets. Conditional, no admin CSS on the front.
 *
 * @package MahmoudElsaad\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style(
			'mes-fonts',
			'https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;700;800;900&display=swap',
			array(),
			null
		);
		wp_enqueue_style(
			'mes-fa',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
			array(),
			'6.5.0'
		);
		wp_enqueue_style( 'mes-main', MES_THEME_URL . '/assets/css/main.css', array( 'mes-fonts' ), MES_THEME_VERSION );
		wp_enqueue_script( 'mes-theme', MES_THEME_URL . '/assets/js/theme.js', array(), MES_THEME_VERSION, true );
		wp_enqueue_script( 'mes-track', MES_THEME_URL . '/assets/js/tracking.js', array(), MES_THEME_VERSION, true );

		$brand = function_exists( 'mes_get_option' ) ? mes_get_option( 'mes_brand_settings', array() ) : array();
		$css   = ':root{';
		if ( ! empty( $brand['primary_color'] ) ) {
			$css .= '--mes-navy:' . sanitize_hex_color( $brand['primary_color'] ) . ';';
		}
		if ( ! empty( $brand['secondary_color'] ) ) {
			$css .= '--mes-turq:' . sanitize_hex_color( $brand['secondary_color'] ) . ';';
		}
		if ( ! empty( $brand['accent_color'] ) ) {
			$css .= '--mes-gold:' . sanitize_hex_color( $brand['accent_color'] ) . ';';
		}
		$css .= '}';
		wp_add_inline_style( 'mes-main', $css );

		wp_localize_script(
			'mes-track',
			'mesFront',
			array(
				'root'  => esc_url_raw( rest_url( 'mes/v1/' ) ),
				'ajax'  => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'nonce' => wp_create_nonce( 'mes_track_click' ),
				'post'  => get_queried_object_id(),
				'type'  => get_post_type() ?: ( is_front_page() ? 'home' : 'page' ),
				'home'  => esc_url_raw( home_url( '/' ) ),
				'lang'  => function_exists( 'mes_html_lang' ) ? mes_html_lang() : 'ar',
			)
		);
	}
);

add_filter(
	'wp_resource_hints',
	static function ( $urls, $relation ) {
		if ( 'preconnect' === $relation ) {
			$urls[] = 'https://fonts.googleapis.com';
			$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => true );
		}
		return $urls;
	},
	10,
	2
);
