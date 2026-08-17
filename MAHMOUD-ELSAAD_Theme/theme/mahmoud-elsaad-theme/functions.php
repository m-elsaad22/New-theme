<?php
/**
 * MAHMOUD-ELSAAD Theme bootstrap.
 *
 * @package MahmoudElsaad\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MES_THEME_VERSION', '2027.0.0' );
define( 'MES_THEME_PATH', get_template_directory() );
define( 'MES_THEME_URL', get_template_directory_uri() );

require_once MES_THEME_PATH . '/inc/setup.php';
require_once MES_THEME_PATH . '/inc/assets.php';
require_once MES_THEME_PATH . '/inc/template-tags.php';
require_once MES_THEME_PATH . '/inc/walker-nav.php';
require_once MES_THEME_PATH . '/inc/fallback-menu.php';

add_action(
	'after_setup_theme',
	static function () {
		if ( ! function_exists( 'mes_core_ready' ) && ! class_exists( '\\MahmoudElsaad\\Core\\Plugin' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-warning"><p>' . esc_html__( 'Activate MAHMOUD-ELSAAD Core for services, forms, tracking, and Control Center.', 'mahmoud-elsaad' ) . '</p></div>';
				}
			);
		}
	}
);
