<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Manages plugin database tables using dbDelta.
 */
class KayanCC_Database {

    const DB_VERSION_OPTION = 'kayan_cc_db_version';
    const DB_VERSION        = '1.0.0';

    public static function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── Phone numbers registry ────────────────────────────────────────────
        $sql[] = "CREATE TABLE {$wpdb->prefix}kayan_cc_numbers (
            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name           VARCHAR(255)    NOT NULL DEFAULT '',
            phone_number   VARCHAR(50)     NOT NULL DEFAULT '',
            phone_e164     VARCHAR(50)     NOT NULL DEFAULT '',
            whatsapp_number VARCHAR(50)    NOT NULL DEFAULT '',
            whatsapp_e164  VARCHAR(50)     NOT NULL DEFAULT '',
            country        VARCHAR(100)    NOT NULL DEFAULT '',
            country_code   VARCHAR(10)     NOT NULL DEFAULT '',
            call_enabled   TINYINT(1)      NOT NULL DEFAULT 1,
            wa_enabled     TINYINT(1)      NOT NULL DEFAULT 1,
            call_label     VARCHAR(255)    NOT NULL DEFAULT '',
            wa_label       VARCHAR(255)    NOT NULL DEFAULT '',
            status         ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";

        // ── Phone → Post / Taxonomy assignments ──────────────────────────────
        $sql[] = "CREATE TABLE {$wpdb->prefix}kayan_cc_assignments (
            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            number_id      BIGINT UNSIGNED NOT NULL,
            scope_type     ENUM('post','post_type','taxonomy_term','site_default') NOT NULL,
            scope_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
            scope_taxonomy VARCHAR(100)    NOT NULL DEFAULT '',
            priority       INT             NOT NULL DEFAULT 10,
            created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY number_id (number_id),
            KEY scope_type (scope_type),
            KEY scope_id (scope_id)
        ) $charset_collate;";

        // ── SEO templates ─────────────────────────────────────────────────────
        $sql[] = "CREATE TABLE {$wpdb->prefix}kayan_cc_seo_templates (
            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name           VARCHAR(255)    NOT NULL DEFAULT '',
            template       TEXT            NOT NULL,
            scope_type     ENUM('post_type','taxonomy_term','site_default','manual') NOT NULL DEFAULT 'manual',
            scope_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
            scope_taxonomy VARCHAR(100)    NOT NULL DEFAULT '',
            created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // ── Activity log ──────────────────────────────────────────────────────
        $sql[] = "CREATE TABLE {$wpdb->prefix}kayan_cc_activity_log (
            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            action         VARCHAR(100)    NOT NULL DEFAULT '',
            object_type    VARCHAR(100)    NOT NULL DEFAULT '',
            object_id      BIGINT UNSIGNED NOT NULL DEFAULT 0,
            user_id        BIGINT UNSIGNED NOT NULL DEFAULT 0,
            data_before    LONGTEXT,
            data_after     LONGTEXT,
            is_reversible  TINYINT(1)      NOT NULL DEFAULT 0,
            reverted_by    BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        // ── Scanner results cache ─────────────────────────────────────────────
        $sql[] = "CREATE TABLE {$wpdb->prefix}kayan_cc_scan_results (
            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            raw_value      VARCHAR(500)    NOT NULL DEFAULT '',
            normalized     VARCHAR(100)    NOT NULL DEFAULT '',
            result_type    ENUM('phone_confirmed','whatsapp_confirmed','phone_probable','call_link','whatsapp_link','duplicate') NOT NULL,
            source_type    VARCHAR(100)    NOT NULL DEFAULT '',
            source_id      BIGINT UNSIGNED NOT NULL DEFAULT 0,
            source_field   VARCHAR(255)    NOT NULL DEFAULT '',
            scan_batch     VARCHAR(64)     NOT NULL DEFAULT '',
            created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY normalized (normalized),
            KEY scan_batch (scan_batch)
        ) $charset_collate;";

        foreach ( $sql as $query ) {
            dbDelta( $query );
        }

        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    public static function get_numbers_table()      { global $wpdb; return $wpdb->prefix . 'kayan_cc_numbers'; }
    public static function get_assignments_table()  { global $wpdb; return $wpdb->prefix . 'kayan_cc_assignments'; }
    public static function get_templates_table()    { global $wpdb; return $wpdb->prefix . 'kayan_cc_seo_templates'; }
    public static function get_log_table()          { global $wpdb; return $wpdb->prefix . 'kayan_cc_activity_log'; }
    public static function get_scan_table()         { global $wpdb; return $wpdb->prefix . 'kayan_cc_scan_results'; }
}
