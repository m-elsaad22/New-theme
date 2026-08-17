<?php
/**
 * Namespaced options with defaults.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Options {
	/**
	 * Default option map.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'mes_brand_settings'     => array(
				'name'            => '',
				'tagline'         => '',
				'logo_id'         => 0,
				'favicon_id'      => 0,
				'primary_color'   => '#0A1F4E',
				'secondary_color' => '#2E9DF7',
				'accent_color'    => '#C9A227',
			),
			'mes_contact_settings'   => array(
				'phones'    => array(),
				'whatsapps' => array(),
				'email'     => '',
				'address'   => '',
				'map_embed' => '',
				'social'    => array(
					'facebook'  => '',
					'instagram' => '',
					'tiktok'    => '',
					'youtube'   => '',
					'linkedin'  => '',
					'x'         => '',
					'snapchat'  => '',
					'telegram'  => '',
				),
			),
			'mes_design_system'      => array(
				'theme_mode'      => 'system',
				'container'       => '1400px',
				'section_spacing' => '120px',
				'overrides'       => array(),
			),
			'mes_homepage_sections'  => array(
				'hero'            => array( 'enabled' => true, 'order' => 10 ),
				'trust'           => array( 'enabled' => true, 'order' => 20 ),
				'kpis'            => array( 'enabled' => true, 'order' => 30 ),
				'finder'          => array( 'enabled' => true, 'order' => 40 ),
				'services'        => array( 'enabled' => true, 'order' => 50 ),
				'why'             => array( 'enabled' => true, 'order' => 60 ),
				'team'            => array( 'enabled' => true, 'order' => 70 ),
				'stats'           => array( 'enabled' => true, 'order' => 80 ),
				'comparison'      => array( 'enabled' => true, 'order' => 90 ),
				'cities'          => array( 'enabled' => true, 'order' => 100 ),
				'before_after'    => array( 'enabled' => true, 'order' => 110 ),
				'case_studies'    => array( 'enabled' => true, 'order' => 120 ),
				'portfolio'       => array( 'enabled' => true, 'order' => 130 ),
				'certs'           => array( 'enabled' => true, 'order' => 140 ),
				'pricing'         => array( 'enabled' => true, 'order' => 150 ),
				'testimonials'    => array( 'enabled' => true, 'order' => 160 ),
				'partners'        => array( 'enabled' => true, 'order' => 170 ),
				'knowledge'       => array( 'enabled' => true, 'order' => 180 ),
				'blog'            => array( 'enabled' => true, 'order' => 190 ),
				'faq'             => array( 'enabled' => true, 'order' => 200 ),
				'cta'             => array( 'enabled' => true, 'order' => 210 ),
			),
			'mes_ai_settings'        => array(
				'provider' => 'openai',
				'model'    => '',
				'enabled'  => false,
			),
			'mes_seo_settings'       => array(
				'emit_schema'           => true,
				'defer_to_rank_math'    => true,
				'language_prefix'       => true,
				'default_language'      => 'ar',
			),
			'mes_system_settings'    => array(
				'purge_on_uninstall' => false,
				'logging'            => false,
			),
			'mes_i18n_settings'      => array(
				'languages'        => array( 'ar', 'en' ),
				'default'          => 'ar',
				'url_mode'         => 'prefix',
			),
			'mes_nav_settings'       => array(
				'legacy_mega_menu' => false,
			),
			'mes_performance_settings' => array(
				'disable_emojis' => true,
				'disable_embeds' => false,
			),
			'mes_security_settings'    => array(
				'hide_versions'  => true,
				'disable_xmlrpc' => true,
				'logging'        => false,
			),
			'mes_stats_settings'       => array(
				'customers'    => '',
				'jobs'         => '',
				'rating'       => '',
				'years'        => '',
				'technicians'  => '',
				'satisfaction' => '',
			),
		);
	}

	/**
	 * Register defaults without overwriting existing values.
	 */
	public static function register_defaults(): void {
		foreach ( self::defaults() as $key => $value ) {
			if ( false === get_option( $key, false ) ) {
				add_option( $key, $value, '', false );
			}
		}
	}

	/**
	 * Get a namespaced option.
	 *
	 * @param string $key Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public static function get( string $key, $default = array() ) {
		$value    = get_option( $key, null );
		$defaults = self::defaults();
		$base     = $defaults[ $key ] ?? $default;
		if ( null === $value ) {
			return $base;
		}
		if ( is_array( $base ) && is_array( $value ) ) {
			return array_replace_recursive( $base, $value );
		}
		return $value;
	}

	/**
	 * Update option and store a revision.
	 *
	 * @param string               $key Option key.
	 * @param array<string, mixed> $value Value.
	 */
	public static function update( string $key, array $value ): bool {
		$previous = get_option( $key );
		$ok       = update_option( $key, $value );
		if ( $ok ) {
			self::store_revision( $key, is_array( $previous ) ? $previous : array() );
		}
		return $ok;
	}

	/**
	 * Store a settings revision.
	 *
	 * @param string               $group Group.
	 * @param array<string, mixed> $payload Payload.
	 */
	public static function store_revision( string $group, array $payload ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mes_revisions',
			array(
				'option_group' => sanitize_key( $group ),
				'payload'      => wp_json_encode( $payload ),
				'created_at'   => current_time( 'mysql', true ),
				'user_id'      => get_current_user_id(),
			),
			array( '%s', '%s', '%s', '%d' )
		);
	}

	/**
	 * Recent revisions.
	 *
	 * @param int $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function revisions( int $limit = 20 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'mes_revisions';
		return (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT id, option_group, created_at, user_id FROM {$table} ORDER BY id DESC LIMIT %d", max( 1, min( 100, $limit ) ) ),
			ARRAY_A
		);
	}

	/**
	 * Restore a revision by id.
	 *
	 * @param int $id Revision ID.
	 */
	public static function restore_revision( int $id ): bool {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'mes_revisions WHERE id = %d', $id ),
			ARRAY_A
		);
		if ( ! $row ) {
			return false;
		}
		$payload = json_decode( (string) $row['payload'], true );
		if ( ! is_array( $payload ) ) {
			return false;
		}
		return self::update( (string) $row['option_group'], $payload );
	}
}
