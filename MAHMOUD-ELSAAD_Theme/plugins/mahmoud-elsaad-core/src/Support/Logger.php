<?php
/**
 * Optional structured logs.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Logger {
	/**
	 * Write a log row when logging is enabled.
	 *
	 * @param string               $channel Channel.
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Context.
	 */
	public static function log( string $channel, string $message, array $context = array() ): void {
		$system = Options::get( 'mes_system_settings', array() );
		$sec    = Options::get( 'mes_security_settings', array() );
		if ( empty( $system['logging'] ) && empty( $sec['logging'] ) ) {
			return;
		}

		if ( isset( $context['api_key'] ) ) {
			unset( $context['api_key'] );
		}

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'mes_logs',
			array(
				'channel'    => sanitize_key( $channel ),
				'message'    => sanitize_textarea_field( $message ),
				'context'    => wp_json_encode( self::redact( $context ) ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Recent rows.
	 *
	 * @param string $channel Channel or empty.
	 * @param int    $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function recent( string $channel = '', int $limit = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'mes_logs';
		$limit = max( 1, min( 200, $limit ) );
		if ( $channel ) {
			return (array) $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE channel = %s ORDER BY id DESC LIMIT %d",
					sanitize_key( $channel ),
					$limit
				),
				ARRAY_A
			);
		}
		return (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ),
			ARRAY_A
		);
	}

	/**
	 * Redact secret-looking keys.
	 *
	 * @param array<string, mixed> $context Context.
	 * @return array<string, mixed>
	 */
	private static function redact( array $context ): array {
		foreach ( $context as $key => $value ) {
			$k = strtolower( (string) $key );
			if ( is_array( $value ) ) {
				$context[ $key ] = self::redact( $value );
				continue;
			}
			if ( false !== strpos( $k, 'key' ) || false !== strpos( $k, 'secret' ) || false !== strpos( $k, 'token' ) || false !== strpos( $k, 'password' ) ) {
				$context[ $key ] = '[redacted]';
			}
		}
		return $context;
	}
}
