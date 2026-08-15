<?php
/**
 * Call and WhatsApp click tracking.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Tracking;

use MahmoudElsaad\Core\Support\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Clicks {
	/**
	 * Init REST.
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
			'/track-click',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'store' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'event_type' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'nonce'      => array(
						'required' => true,
					),
				),
			)
		);
		register_rest_route(
			'mes/v1',
			'/analytics/clicks',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'report' ),
				'permission_callback' => static fn() => Capabilities::can_manage(),
			)
		);
		register_rest_route(
			'mes/v1',
			'/analytics/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'export_csv' ),
				'permission_callback' => static fn() => Capabilities::can_manage(),
			)
		);
	}

	/**
	 * Store a click.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function store( \WP_REST_Request $request ) {
		if ( ! wp_verify_nonce( (string) $request->get_param( 'nonce' ), 'mes_track_click' ) ) {
			return new \WP_Error( 'mes_bad_nonce', __( 'Invalid nonce.', 'mahmoud-elsaad-core' ), array( 'status' => 403 ) );
		}
		$type = (string) $request->get_param( 'event_type' );
		if ( ! in_array( $type, array( 'call', 'whatsapp' ), true ) ) {
			return new \WP_Error( 'mes_bad_type', __( 'Invalid event.', 'mahmoud-elsaad-core' ), array( 'status' => 400 ) );
		}

		$session = sanitize_text_field( (string) $request->get_param( 'session' ) );
		if ( ! $session ) {
			$session = wp_generate_uuid4();
		}

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mes_clicks',
			array(
				'event_type'   => $type,
				'phone'        => sanitize_text_field( (string) $request->get_param( 'number' ) ),
				'post_id'      => absint( $request->get_param( 'post_id' ) ),
				'source_url'   => esc_url_raw( (string) $request->get_param( 'source_url' ) ),
				'content_type' => sanitize_key( (string) $request->get_param( 'content_type' ) ),
				'placement'    => sanitize_key( (string) $request->get_param( 'placement' ) ),
				'device'       => sanitize_key( (string) $request->get_param( 'device' ) ),
				'referrer'     => esc_url_raw( (string) $request->get_param( 'referrer' ) ),
				'utm'          => sanitize_text_field( (string) $request->get_param( 'utm' ) ),
				'session_hash' => hash( 'sha256', $session . wp_salt( 'nonce' ) ),
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		do_action( 'mes_contact_click', $type, $request->get_params() );

		return rest_ensure_response( array( 'ok' => true ) );
	}

	/**
	 * Aggregated report.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function report( \WP_REST_Request $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'mes_clicks';
		$from  = sanitize_text_field( (string) $request->get_param( 'from' ) ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$to    = sanitize_text_field( (string) $request->get_param( 'to' ) ) ?: gmdate( 'Y-m-d' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, placement, post_id, COUNT(*) AS total FROM {$table} WHERE created_at BETWEEN %s AND %s GROUP BY event_type, placement, post_id ORDER BY total DESC LIMIT 200",
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			),
			ARRAY_A
		);

		$totals = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) d, event_type, COUNT(*) total FROM {$table} WHERE created_at BETWEEN %s AND %s GROUP BY d, event_type ORDER BY d ASC",
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			),
			ARRAY_A
		);

		return rest_ensure_response(
			array(
				'from'   => $from,
				'to'     => $to,
				'rows'   => $rows,
				'series' => $totals,
			)
		);
	}

	/**
	 * CSV export.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function export_csv( \WP_REST_Request $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'mes_clicks';
		$from  = sanitize_text_field( (string) $request->get_param( 'from' ) ) ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$to    = sanitize_text_field( (string) $request->get_param( 'to' ) ) ?: gmdate( 'Y-m-d' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE created_at BETWEEN %s AND %s ORDER BY id DESC LIMIT 5000",
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			),
			ARRAY_A
		);

		$fh = fopen( 'php://temp', 'w+' );
		if ( $rows ) {
			fputcsv( $fh, array_keys( $rows[0] ) );
			foreach ( $rows as $row ) {
				fputcsv( $fh, $row );
			}
		}
		rewind( $fh );
		$csv = stream_get_contents( $fh );
		fclose( $fh );

		return new \WP_REST_Response(
			$csv,
			200,
			array(
				'Content-Type'        => 'text/csv; charset=utf-8',
				'Content-Disposition' => 'attachment; filename="mes-clicks.csv"',
			)
		);
	}
}
