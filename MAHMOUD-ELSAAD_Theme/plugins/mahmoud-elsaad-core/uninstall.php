<?php
/**
 * Uninstall handler. Content is kept unless the operator opted in.
 *
 * @package MahmoudElsaad\Core
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'mes_system_settings', array() );
if ( empty( $settings['purge_on_uninstall'] ) ) {
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'mes_clicks',
	$wpdb->prefix . 'mes_service_city',
	$wpdb->prefix . 'mes_revisions',
	$wpdb->prefix . 'mes_logs',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$options = array(
	'mes_theme_settings',
	'mes_brand_settings',
	'mes_contact_settings',
	'mes_design_system',
	'mes_ai_settings',
	'mes_seo_settings',
	'mes_system_settings',
	'mes_homepage_sections',
	'mes_schema_version',
	'mes_migration_state',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

wp_cache_flush();
