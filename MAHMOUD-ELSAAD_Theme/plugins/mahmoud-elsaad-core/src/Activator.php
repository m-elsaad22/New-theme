<?php
/**
 * Activation.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core;

use MahmoudElsaad\Core\Content\Registrar;
use MahmoudElsaad\Core\Database\Schema;
use MahmoudElsaad\Core\Demo\Cities;
use MahmoudElsaad\Core\Demo\Countries;
use MahmoudElsaad\Core\Support\Capabilities;
use MahmoudElsaad\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {
	/**
	 * Run on plugin activation.
	 */
	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			deactivate_plugins( MES_CORE_BASENAME );
			wp_die( esc_html__( 'MAHMOUD-ELSAAD Core requires PHP 8.2 or newer.', 'mahmoud-elsaad-core' ) );
		}

		Schema::install();
		Options::register_defaults();
		Capabilities::install();
		Registrar::register();
		Registrar::register_meta();
		Countries::seed();
		Cities::seed();
		flush_rewrite_rules();
		update_option( 'mes_core_activated_at', gmdate( 'c' ) );
	}
}
