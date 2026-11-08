<?php
/**
 * Plugin Name: KAYAN Control Center
 * Plugin URI:  https://www.rukn-eltatawer.com/
 * Description: مركز تحكم متكامل لإدارة أرقام الاتصال وعناوين SEO لموقع ركن التطور - يدعم Rank Math SEO وقالب KAYAN
 * Version:     1.0.0
 * Author:      Rukn Eltatawer
 * Author URI:  https://www.rukn-eltatawer.com/
 * Text Domain: kayan-cc
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License:     GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'KAYAN_CC_VERSION',   '1.0.0' );
define( 'KAYAN_CC_PLUGIN_FILE', __FILE__ );
define( 'KAYAN_CC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KAYAN_CC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KAYAN_CC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// ─── Autoload ────────────────────────────────────────────────────────────────
spl_autoload_register( function ( $class ) {
    $prefix = 'KayanCC_';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }
    $relative = str_replace( '_', '-', substr( $class, strlen( $prefix ) ) );
    $file = KAYAN_CC_PLUGIN_DIR . 'includes/class-' . strtolower( $relative ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// ─── Bootstrap ───────────────────────────────────────────────────────────────
function kayan_cc_init() {
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-database.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-helpers.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-phone-scanner.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-phone-manager.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-phone-assignment.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-phone-replacement.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-seo-manager.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-seo-templates.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-activity-log.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-ajax-handler.php';
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-rest-api.php';

    if ( is_admin() ) {
        require_once KAYAN_CC_PLUGIN_DIR . 'admin/class-admin.php';
        KayanCC_Admin::get_instance();
    }

    // Front-end: inject assigned phone numbers into KAYAN theme hooks
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-frontend.php';
    KayanCC_Frontend::get_instance();

    KayanCC_Ajax_Handler::get_instance();
    KayanCC_REST_API::get_instance();
}
add_action( 'plugins_loaded', 'kayan_cc_init' );

// ─── Activation / Deactivation ───────────────────────────────────────────────
register_activation_hook( __FILE__, function () {
    require_once KAYAN_CC_PLUGIN_DIR . 'includes/class-database.php';
    KayanCC_Database::install();
    // Do NOT run scanner or replace any data on activation
} );

register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'kayan_cc_batch_scan_cron' );
} );
