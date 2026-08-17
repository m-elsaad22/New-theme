<?php
/**
 * Custom tables.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Schema {
	public const VERSION = 1;

	/**
	 * Install or upgrade tables.
	 */
	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$clicks  = $wpdb->prefix . 'mes_clicks';
		$pairs   = $wpdb->prefix . 'mes_service_city';
		$revs    = $wpdb->prefix . 'mes_revisions';
		$logs    = $wpdb->prefix . 'mes_logs';

		$sql = "CREATE TABLE {$clicks} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_type varchar(20) NOT NULL,
			phone varchar(40) NOT NULL DEFAULT '',
			post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			source_url text NULL,
			content_type varchar(40) NOT NULL DEFAULT '',
			placement varchar(40) NOT NULL DEFAULT '',
			device varchar(20) NOT NULL DEFAULT '',
			referrer text NULL,
			utm varchar(255) NOT NULL DEFAULT '',
			session_hash varchar(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY created_at (created_at),
			KEY event_type (event_type)
		) {$charset};

		CREATE TABLE {$pairs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			service_id bigint(20) unsigned NOT NULL,
			city_id bigint(20) unsigned NOT NULL,
			language_code varchar(8) NOT NULL DEFAULT 'ar',
			status varchar(20) NOT NULL DEFAULT 'publish',
			title text NULL,
			excerpt text NULL,
			content longtext NULL,
			faq longtext NULL,
			image_id bigint(20) unsigned NOT NULL DEFAULT 0,
			phone varchar(40) NOT NULL DEFAULT '',
			whatsapp varchar(40) NOT NULL DEFAULT '',
			seo_title text NULL,
			seo_description text NULL,
			schema_json longtext NULL,
			cta_label varchar(120) NOT NULL DEFAULT '',
			sort_order int(11) NOT NULL DEFAULT 0,
			updated_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY service_city_lang (service_id, city_id, language_code),
			KEY city_id (city_id),
			KEY status (status)
		) {$charset};

		CREATE TABLE {$revs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			option_group varchar(80) NOT NULL,
			payload longtext NOT NULL,
			created_at datetime NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY option_group (option_group)
		) {$charset};

		CREATE TABLE {$logs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			channel varchar(40) NOT NULL,
			message text NOT NULL,
			context longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY channel (channel)
		) {$charset};";

		dbDelta( $sql );
		update_option( 'mes_schema_version', self::VERSION );
	}

	/**
	 * Upgrade if needed.
	 */
	public static function maybe_upgrade(): void {
		$current = (int) get_option( 'mes_schema_version', 0 );
		if ( $current < self::VERSION ) {
			self::install();
		}
	}
}
