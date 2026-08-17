<?php
/**
 * Call and WhatsApp click tracking.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Tracking;

use MahmoudElsaad\Core\Support\Capabilities;
use MahmoudElsaad\Core\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Clicks {
	/**
	 * Init REST and Ajax fallback.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'wp_ajax_mes_track_click', array( __CLASS__, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_mes_track_click', array( __CLASS__, 'ajax' ) );
		add_action( 'admin_post_mes_export_clicks', array( __CLASS__, 'admin_export' ) );
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
	 * Merge JSON body into request params.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	private static function hydrate( \WP_REST_Request $request ): void {
		$json = $request->get_json_params();
		if ( ! is_array( $json ) ) {
			$raw = $request->get_body();
			if ( $raw ) {
				$decoded = json_decode( $raw, true );
				$json    = is_array( $decoded ) ? $decoded : array();
			}
		}
		foreach ( (array) $json as $key => $value ) {
			if ( null === $request->get_param( $key ) || '' === $request->get_param( $key ) ) {
				$request->set_param( $key, $value );
			}
		}
	}

	/**
	 * Store a click.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function store( \WP_REST_Request $request ) {
		self::hydrate( $request );
		return self::persist( $request->get_params() );
	}

	/**
	 * Ajax fallback for sendBeacon/FormData.
	 */
	public static function ajax(): void {
		$params = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$result = self::persist( is_array( $params ) ? $params : array() );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message(), (int) $result->get_error_data()['status'] ?? 400 );
		}
		wp_send_json_success( array( 'ok' => true ) );
	}

	/**
	 * Persist a click event.
	 *
	 * @param array<string, mixed> $params Params.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function persist( array $params ) {
		$nonce = (string) ( $params['nonce'] ?? '' );
		if ( ! wp_verify_nonce( $nonce, 'mes_track_click' ) ) {
			return new \WP_Error( 'mes_bad_nonce', __( 'Invalid nonce.', 'mahmoud-elsaad-core' ), array( 'status' => 403 ) );
		}

		$ip  = (string) ( $_SERVER['REMOTE_ADDR'] ?? '0' );
		$key = 'mes_track_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n > 40 ) {
			return new \WP_Error( 'mes_rate', __( 'Too many events.', 'mahmoud-elsaad-core' ), array( 'status' => 429 ) );
		}
		set_transient( $key, $n + 1, MINUTE_IN_SECONDS );

		$type = sanitize_key( (string) ( $params['event_type'] ?? '' ) );
		if ( ! in_array( $type, array( 'call', 'whatsapp' ), true ) ) {
			return new \WP_Error( 'mes_bad_type', __( 'Invalid event.', 'mahmoud-elsaad-core' ), array( 'status' => 400 ) );
		}

		$session = sanitize_text_field( (string) ( $params['session'] ?? '' ) );
		if ( ! $session ) {
			$session = wp_generate_uuid4();
		}

		$utm = sanitize_text_field( (string) ( $params['utm'] ?? '' ) );
		if ( ! $utm && ! empty( $params['source_url'] ) ) {
			$query = wp_parse_url( (string) $params['source_url'], PHP_URL_QUERY );
			if ( is_string( $query ) && $query ) {
				parse_str( $query, $qs );
				$bits = array();
				foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign' ) as $k ) {
					if ( ! empty( $qs[ $k ] ) ) {
						$bits[] = $k . '=' . sanitize_text_field( (string) $qs[ $k ] );
					}
				}
				$utm = implode( '&', $bits );
			}
		}

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mes_clicks',
			array(
				'event_type'   => $type,
				'phone'        => sanitize_text_field( (string) ( $params['number'] ?? '' ) ),
				'post_id'      => absint( $params['post_id'] ?? 0 ),
				'source_url'   => esc_url_raw( (string) ( $params['source_url'] ?? '' ) ),
				'content_type' => sanitize_key( (string) ( $params['content_type'] ?? '' ) ),
				'placement'    => sanitize_key( (string) ( $params['placement'] ?? 'content' ) ),
				'device'       => sanitize_key( (string) ( $params['device'] ?? '' ) ),
				'referrer'     => esc_url_raw( (string) ( $params['referrer'] ?? '' ) ),
				'utm'          => $utm,
				'session_hash' => hash( 'sha256', $session . wp_salt( 'nonce' ) ),
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		do_action( 'mes_contact_click', $type, $params );
		Logger::log( 'clicks', 'Contact click stored', array( 'type' => $type, 'placement' => $params['placement'] ?? '' ) );

		return rest_ensure_response( array( 'ok' => true ) );
	}

	/**
	 * Query aggregates.
	 *
	 * @param string $from From date.
	 * @param string $to To date.
	 * @return array<string, mixed>
	 */
	public static function summary( string $from = '', string $to = '' ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'mes_clicks';
		$from  = $from ?: gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$to    = $to ?: gmdate( 'Y-m-d' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, placement, post_id, phone, COUNT(*) AS total FROM {$table} WHERE created_at BETWEEN %s AND %s GROUP BY event_type, placement, post_id, phone ORDER BY total DESC LIMIT 200",
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

		$counts = array(
			'call'     => 0,
			'whatsapp' => 0,
		);
		foreach ( (array) $rows as $row ) {
			$type = $row['event_type'] ?? '';
			if ( isset( $counts[ $type ] ) ) {
				$counts[ $type ] += (int) $row['total'];
			}
		}

		$by_page = array();
		foreach ( (array) $rows as $row ) {
			$pid = (int) $row['post_id'];
			if ( ! isset( $by_page[ $pid ] ) ) {
				$by_page[ $pid ] = array(
					'post_id'  => $pid,
					'title'    => $pid ? get_the_title( $pid ) : __( 'Unknown / global', 'mahmoud-elsaad-core' ),
					'call'     => 0,
					'whatsapp' => 0,
					'total'    => 0,
				);
			}
			$by_page[ $pid ][ $row['event_type'] ] = ( $by_page[ $pid ][ $row['event_type'] ] ?? 0 ) + (int) $row['total'];
			$by_page[ $pid ]['total']             += (int) $row['total'];
		}
		usort(
			$by_page,
			static fn( $a, $b ) => $b['total'] <=> $a['total']
		);

		return array(
			'from'    => $from,
			'to'      => $to,
			'counts'  => $counts,
			'rows'    => $rows,
			'series'  => $totals,
			'top'     => array_slice( array_values( $by_page ), 0, 10 ),
		);
	}

	/**
	 * Aggregated report.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function report( \WP_REST_Request $request ) {
		return rest_ensure_response(
			self::summary(
				sanitize_text_field( (string) $request->get_param( 'from' ) ),
				sanitize_text_field( (string) $request->get_param( 'to' ) )
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

	/**
	 * Admin CSV download with cookie nonce.
	 */
	public static function admin_export(): void {
		if ( ! \MahmoudElsaad\Core\Support\Capabilities::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission.', 'mahmoud-elsaad-core' ) );
		}
		check_admin_referer( 'mes_export_clicks' );
		$request = new \WP_REST_Request( 'GET' );
		$request->set_param( 'from', sanitize_text_field( wp_unslash( $_GET['from'] ?? '' ) ) );
		$request->set_param( 'to', sanitize_text_field( wp_unslash( $_GET['to'] ?? '' ) ) );
		$response = self::export_csv( $request );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="mes-clicks.csv"' );
		echo $response->get_data(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
