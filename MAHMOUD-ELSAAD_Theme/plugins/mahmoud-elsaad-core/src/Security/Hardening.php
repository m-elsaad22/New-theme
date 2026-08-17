<?php
/**
 * Optional security hardening.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Security;

use MahmoudElsaad\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Hardening {
	/**
	 * Init.
	 */
	public static function init(): void {
		$settings = Options::get( 'mes_security_settings', array() );
		if ( ! empty( $settings['hide_versions'] ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
		}
		if ( ! empty( $settings['disable_xmlrpc'] ) ) {
			add_filter( 'xmlrpc_enabled', '__return_false' );
		}
	}
}
