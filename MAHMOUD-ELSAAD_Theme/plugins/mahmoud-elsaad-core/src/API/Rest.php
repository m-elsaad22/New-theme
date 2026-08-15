<?php
/**
 * REST API surface for the Control Center and public helpers.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\API;

use MahmoudElsaad\Core\AI\Manager as AIManager;
use MahmoudElsaad\Core\Demo\Content as DemoContent;
use MahmoudElsaad\Core\LegacyMigration\Migrator;
use MahmoudElsaad\Core\Relations\ServiceCity;
use MahmoudElsaad\Core\Support\Capabilities;
use MahmoudElsaad\Core\Support\Logger;
use MahmoudElsaad\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rest {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Routes.
	 */
	public static function routes(): void {
		register_rest_route(
			'mes/v1',
			'/settings/(?P<group>[a-z0-9_\-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_settings' ),
					'permission_callback' => static fn() => Capabilities::can_manage(),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'save_settings' ),
					'permission_callback' => static fn() => Capabilities::can_manage(),
				),
			)
		);

		register_rest_route(
			'mes/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'search' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'mes/v1',
			'/ai/complete',
			array(
				'methods'             => 'POST',
				'callback'            => array( AIManager::class, 'complete' ),
				'permission_callback' => static fn() => current_user_can( 'mes_manage_ai' ) || Capabilities::can_manage(),
			)
		);

		register_rest_route(
			'mes/v1',
			'/migration/run',
			array(
				'methods'             => 'POST',
				'callback'            => array( Migrator::class, 'rest_run' ),
				'permission_callback' => static fn() => current_user_can( 'mes_run_migration' ) || Capabilities::can_manage(),
			)
		);

		register_rest_route(
			'mes/v1',
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'health' ),
				'permission_callback' => static fn() => Capabilities::can_manage(),
			)
		);

		register_rest_route(
			'mes/v1',
			'/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'export_settings' ),
				'permission_callback' => static fn() => Capabilities::can_manage(),
			)
		);

		register_rest_route(
			'mes/v1',
			'/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'import_settings' ),
				'permission_callback' => static fn() => Capabilities::can_manage(),
			)
		);

		register_rest_route(
			'mes/v1',
			'/demo/seed',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'seed_demo' ),
				'permission_callback' => static fn() => Capabilities::can_manage(),
			)
		);

		register_rest_route(
			'mes/v1',
			'/service-city',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'list_pairs' ),
					'permission_callback' => static fn() => Capabilities::can_manage(),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'save_pair' ),
					'permission_callback' => static fn() => Capabilities::can_manage(),
				),
			)
		);

		register_rest_route(
			'mes/v1',
			'/revisions',
			array(
				'methods'             => 'GET',
				'callback'            => static fn() => rest_ensure_response( Options::revisions() ),
				'permission_callback' => static fn() => Capabilities::can_manage(),
			)
		);

		register_rest_route(
			'mes/v1',
			'/revisions/(?P<id>\d+)/restore',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'restore_revision' ),
				'permission_callback' => static fn() => Capabilities::can_manage(),
			)
		);

		register_rest_route(
			'mes/v1',
			'/logs',
			array(
				'methods'             => 'GET',
				'callback'            => static fn() => rest_ensure_response( Logger::recent() ),
				'permission_callback' => static fn() => Capabilities::can_manage(),
			)
		);
	}

	/**
	 * Allowed setting groups.
	 *
	 * @return string[]
	 */
	private static function groups(): array {
		return array(
			'mes_brand_settings',
			'mes_contact_settings',
			'mes_design_system',
			'mes_homepage_sections',
			'mes_ai_settings',
			'mes_seo_settings',
			'mes_system_settings',
			'mes_i18n_settings',
			'mes_nav_settings',
			'mes_performance_settings',
			'mes_security_settings',
			'mes_stats_settings',
		);
	}

	/**
	 * Get settings.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function get_settings( \WP_REST_Request $request ) {
		$group = self::normalize_group( (string) $request['group'] );
		if ( ! $group ) {
			return new \WP_Error( 'mes_bad_group', 'Unknown group', array( 'status' => 400 ) );
		}
		$data = Options::get( $group, array() );
		if ( 'mes_ai_settings' === $group ) {
			unset( $data['api_key'], $data['api_key_encrypted'] );
			$data['has_key'] = ! empty( get_option( 'mes_ai_key_encrypted' ) );
		}
		return rest_ensure_response( $data );
	}

	/**
	 * Save settings.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function save_settings( \WP_REST_Request $request ) {
		$group = self::normalize_group( (string) $request['group'] );
		if ( ! $group ) {
			return new \WP_Error( 'mes_bad_group', 'Unknown group', array( 'status' => 400 ) );
		}
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return new \WP_Error( 'mes_bad_json', 'Invalid payload', array( 'status' => 400 ) );
		}
		if ( 'mes_ai_settings' === $group && ! empty( $params['api_key'] ) ) {
			update_option( 'mes_ai_key_encrypted', \MahmoudElsaad\Core\Support\Crypto::encrypt( sanitize_text_field( (string) $params['api_key'] ) ) );
			unset( $params['api_key'] );
		}
		$existing = Options::get( $group, array() );
		$clean    = self::sanitize_deep( $params );
		if ( 'mes_contact_settings' === $group && ! empty( $params['map_embed'] ) ) {
			$clean['map_embed'] = wp_kses_post( (string) $params['map_embed'] );
		}
		if ( is_array( $existing ) ) {
			$clean = array_replace_recursive( $existing, $clean );
		}
		Options::update( $group, $clean );
		Logger::log( 'admin', 'Settings saved', array( 'group' => $group ) );
		return rest_ensure_response( array( 'ok' => true ) );
	}

	/**
	 * Public search across content types.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function search( \WP_REST_Request $request ) {
		$q = sanitize_text_field( (string) $request->get_param( 'q' ) );
		if ( strlen( $q ) < 2 ) {
			return rest_ensure_response( array() );
		}
		$types = array( 'post', 'page', 'service', 'mes_city', 'mes_portfolio', 'mes_offer' );
		$query = new \WP_Query(
			array(
				's'              => $q,
				'post_type'      => $types,
				'posts_per_page' => 12,
				'post_status'    => 'publish',
			)
		);
		$out = array();
		foreach ( $query->posts as $post ) {
			$out[] = array(
				'id'    => $post->ID,
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
				'type'  => $post->post_type,
			);
		}
		return rest_ensure_response( $out );
	}

	/**
	 * System health.
	 */
	public static function health() {
		return rest_ensure_response(
			array(
				'php'        => PHP_VERSION,
				'wp'         => get_bloginfo( 'version' ),
				'https'      => is_ssl(),
				'permalinks' => (bool) get_option( 'permalink_structure' ),
				'rank_math'  => defined( 'RANK_MATH_VERSION' ),
				'theme'      => get_template(),
				'core'       => MES_CORE_VERSION,
				'counts'     => array(
					'service'       => (int) ( wp_count_posts( 'service' )->publish ?? 0 ),
					'mes_city'      => (int) ( wp_count_posts( 'mes_city' )->publish ?? 0 ),
					'mes_lead'      => (int) ( wp_count_posts( 'mes_lead' )->private ?? 0 ),
					'mes_portfolio' => (int) ( wp_count_posts( 'mes_portfolio' )->publish ?? 0 ),
				),
			)
		);
	}

	/**
	 * Export settings JSON.
	 */
	public static function export_settings() {
		$payload = array(
			'version' => MES_CORE_VERSION,
			'groups'  => array(),
		);
		foreach ( self::groups() as $group ) {
			$data = Options::get( $group, array() );
			if ( 'mes_ai_settings' === $group ) {
				unset( $data['api_key'], $data['api_key_encrypted'] );
			}
			$payload['groups'][ $group ] = $data;
		}
		return rest_ensure_response( $payload );
	}

	/**
	 * Import settings JSON.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function import_settings( \WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( empty( $params['groups'] ) || ! is_array( $params['groups'] ) ) {
			return new \WP_Error( 'mes_bad_import', 'Invalid import', array( 'status' => 400 ) );
		}
		foreach ( $params['groups'] as $group => $value ) {
			if ( in_array( $group, self::groups(), true ) && is_array( $value ) ) {
				Options::update( $group, self::sanitize_deep( $value ) );
			}
		}
		return rest_ensure_response( array( 'ok' => true ) );
	}

	/**
	 * Seed sample content.
	 */
	public static function seed_demo() {
		return rest_ensure_response( DemoContent::seed() );
	}

	/**
	 * List service×city rows.
	 */
	public static function list_pairs() {
		return rest_ensure_response( ServiceCity::list() );
	}

	/**
	 * Save a service×city override.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function save_pair( \WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return new \WP_Error( 'mes_bad_json', 'Invalid payload', array( 'status' => 400 ) );
		}
		$id = ServiceCity::upsert( $params );
		if ( ! $id ) {
			return new \WP_Error( 'mes_bad_pair', 'Service and city are required', array( 'status' => 400 ) );
		}
		return rest_ensure_response( array( 'ok' => true, 'id' => $id ) );
	}

	/**
	 * Restore a settings revision.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function restore_revision( \WP_REST_Request $request ) {
		$id = absint( $request['id'] );
		$ok = Options::restore_revision( $id );
		return $ok
			? rest_ensure_response( array( 'ok' => true ) )
			: new \WP_Error( 'mes_missing', 'Revision not found', array( 'status' => 404 ) );
	}

	/**
	 * Accept `brand_settings` or `mes_brand_settings`.
	 *
	 * @param string $group Group.
	 */
	private static function normalize_group( string $group ): string {
		$group = sanitize_key( $group );
		if ( 0 !== strpos( $group, 'mes_' ) ) {
			$group = 'mes_' . $group;
		}
		return in_array( $group, self::groups(), true ) ? $group : '';
	}

	/**
	 * Recursive sanitizer that keeps arrays and booleans.
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private static function sanitize_deep( $value ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $key => $item ) {
				$out[ sanitize_key( (string) $key ) ] = self::sanitize_deep( $item );
			}
			return $out;
		}
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( is_int( $value ) || is_float( $value ) ) {
			return $value;
		}
		if ( 'true' === $value ) {
			return true;
		}
		if ( 'false' === $value ) {
			return false;
		}
		return sanitize_textarea_field( (string) $value );
	}
}
