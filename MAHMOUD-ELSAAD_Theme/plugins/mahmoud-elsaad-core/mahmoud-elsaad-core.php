<?php
/**
 * Plugin Name: MAHMOUD-ELSAAD Core
 * Plugin URI:
 * Description: Business/data layer for the MAHMOUD-ELSAAD platform — services, cities, forms, leads, SEO, schema, AI, analytics, and legacy migration.
 * Version: 2027.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Author: MAHMOUD ELSAAD
 * Text Domain: mahmoud-elsaad-core
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 *
 * @package MahmoudElsaad\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MES_CORE_VERSION', '2027.0.0' );
define( 'MES_CORE_FILE', __FILE__ );
define( 'MES_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'MES_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'MES_CORE_BASENAME', plugin_basename( __FILE__ ) );

require_once MES_CORE_PATH . 'src/Autoloader.php';
MahmoudElsaad\Core\Autoloader::register();

register_activation_hook( __FILE__, array( 'MahmoudElsaad\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MahmoudElsaad\\Core\\Deactivator', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'mahmoud-elsaad-core', false, dirname( MES_CORE_BASENAME ) . '/languages' );
		MahmoudElsaad\Core\Plugin::instance()->boot();
	}
);
