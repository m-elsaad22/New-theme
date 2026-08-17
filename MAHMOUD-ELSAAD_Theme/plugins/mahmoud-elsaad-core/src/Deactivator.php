<?php
/**
 * Deactivation.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivator {
	/**
	 * Run on plugin deactivation.
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'mes_daily_health' );
		flush_rewrite_rules();
	}
}
