<?php
/**
 * Frontend compiled visual CSS.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Visual;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Front {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 30 );
	}

	/**
	 * Print compiled CSS after the theme stylesheet.
	 */
	public static function enqueue(): void {
		$css = (string) get_option( 'mes_compiled_css', '' );
		if ( '' === $css ) {
			$css = Compiler::persist();
		}
		$ver = (string) get_option( 'mes_compiled_css_ver', MES_CORE_VERSION );
		wp_register_style( 'mes-visual', false, array(), $ver );
		wp_enqueue_style( 'mes-visual' );
		wp_add_inline_style( 'mes-visual', $css );
	}
}
