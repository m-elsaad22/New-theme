<?php
/**
 * Provider-based AI engine. Keys never reach the frontend.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\AI;

use MahmoudElsaad\Core\Support\Crypto;
use MahmoudElsaad\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Manager {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor' ) );
	}

	/**
	 * REST complete.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function complete( \WP_REST_Request $request ) {
		$prompt = sanitize_textarea_field( (string) $request->get_param( 'prompt' ) );
		$task   = sanitize_key( (string) $request->get_param( 'task' ) );
		if ( '' === $prompt ) {
			return new \WP_Error( 'mes_empty', __( 'Prompt required.', 'mahmoud-elsaad-core' ), array( 'status' => 400 ) );
		}

		$settings = Options::get( 'mes_ai_settings', array() );
		if ( empty( $settings['enabled'] ) && empty( get_option( 'mes_ai_key_encrypted' ) ) ) {
			return rest_ensure_response(
				array(
					'ok'      => false,
					'message' => __( 'No AI provider is configured. Add a key in Control Center → AI.', 'mahmoud-elsaad-core' ),
					'fallback'=> self::local_fallback( $task, $prompt ),
				)
			);
		}

		$key = Crypto::decrypt( (string) get_option( 'mes_ai_key_encrypted' ) );
		if ( ! $key ) {
			return rest_ensure_response(
				array(
					'ok'       => false,
					'message'  => __( 'AI key missing.', 'mahmoud-elsaad-core' ),
					'fallback' => self::local_fallback( $task, $prompt ),
				)
			);
		}

		$provider = sanitize_key( $settings['provider'] ?? 'openai' );
		$text     = Providers\Registry::complete( $provider, $key, $prompt, $task );
		return rest_ensure_response( array( 'ok' => true, 'text' => $text ) );
	}

	/**
	 * Local deterministic fallback so the feature is real without a vendor.
	 *
	 * @param string $task Task.
	 * @param string $prompt Prompt.
	 */
	public static function local_fallback( string $task, string $prompt ): string {
		$prompt = wp_strip_all_tags( $prompt );
		switch ( $task ) {
			case 'title':
				return wp_trim_words( $prompt, 8, '' );
			case 'excerpt':
			case 'meta':
				return wp_trim_words( $prompt, 28 );
			case 'faq':
				return "Q: " . wp_trim_words( $prompt, 10 ) . "\nA: " . wp_trim_words( $prompt, 40 );
			default:
				return wp_trim_words( $prompt, 40 );
		}
	}

	/**
	 * Editor assistant button.
	 */
	public static function editor(): void {
		wp_enqueue_script(
			'mes-ai-editor',
			MES_CORE_URL . 'assets/admin/js/ai-assistant.js',
			array( 'wp-element', 'wp-data', 'wp-plugins', 'wp-edit-post' ),
			MES_CORE_VERSION,
			true
		);
		wp_localize_script(
			'mes-ai-editor',
			'mesAI',
			array(
				'root'  => esc_url_raw( rest_url( 'mes/v1/' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
