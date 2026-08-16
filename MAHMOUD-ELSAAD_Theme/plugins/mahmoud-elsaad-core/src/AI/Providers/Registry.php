<?php
/**
 * AI provider registry.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\AI\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry {
	/**
	 * Last HTTP status from the provider (0 on transport error).
	 *
	 * @var int
	 */
	public static $last_status = 0;

	/**
	 * Last error token (never includes secrets).
	 *
	 * @var string
	 */
	public static $last_error = '';

	/**
	 * Dispatch completion.
	 *
	 * @param string $provider Provider id.
	 * @param string $key API key.
	 * @param string $prompt Prompt.
	 * @param string $task Task.
	 */
	public static function complete( string $provider, string $key, string $prompt, string $task ): string {
		self::$last_status = 0;
		self::$last_error  = '';
		$map = array(
			'openai'      => 'https://api.openai.com/v1/chat/completions',
			'openrouter'  => 'https://openrouter.ai/api/v1/chat/completions',
			'compatible'  => '',
			'mistral'     => 'https://api.mistral.ai/v1/chat/completions',
		);

		if ( 'anthropic' === $provider ) {
			return self::anthropic( $key, $prompt );
		}
		if ( 'gemini' === $provider ) {
			return self::gemini( $key, $prompt );
		}

		$endpoint = $map[ $provider ] ?? $map['openai'];
		$settings = get_option( 'mes_ai_settings', array() );
		if ( 'compatible' === $provider && ! empty( $settings['endpoint'] ) ) {
			$endpoint = esc_url_raw( $settings['endpoint'] );
		}
		if ( ! $endpoint ) {
			self::$last_error = 'empty';
			return \MahmoudElsaad\Core\AI\Manager::local_fallback( $task, $prompt );
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'    => $settings['model'] ?: 'gpt-4o-mini',
						'messages' => array(
							array( 'role' => 'system', 'content' => 'You are an assistant for a service business CMS.' ),
							array( 'role' => 'user', 'content' => $prompt ),
						),
					)
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			self::$last_error = (string) $response->get_error_code();
			\MahmoudElsaad\Core\Support\Logger::log( 'ai', 'Provider transport error', array( 'provider' => $provider, 'code' => $response->get_error_code() ) );
			return \MahmoudElsaad\Core\AI\Manager::local_fallback( $task, $prompt );
		}
		$code              = (int) wp_remote_retrieve_response_code( $response );
		self::$last_status = $code;
		$raw               = (string) wp_remote_retrieve_body( $response );
		if ( $code >= 400 ) {
			self::$last_error = 'http';
			\MahmoudElsaad\Core\Support\Logger::log( 'ai', 'Provider HTTP error', array( 'provider' => $provider, 'status' => $code ) );
			return \MahmoudElsaad\Core\AI\Manager::local_fallback( $task, $prompt );
		}
		$body = json_decode( $raw, true );
		if ( '' !== $raw && null === $body ) {
			self::$last_error = 'malformed';
			\MahmoudElsaad\Core\Support\Logger::log( 'ai', 'Provider malformed response', array( 'provider' => $provider, 'status' => $code ) );
			return \MahmoudElsaad\Core\AI\Manager::local_fallback( $task, $prompt );
		}
		$text = (string) ( $body['choices'][0]['message']['content'] ?? '' );
		if ( '' === trim( $text ) ) {
			self::$last_error = 'empty';
			\MahmoudElsaad\Core\Support\Logger::log( 'ai', 'Provider empty response', array( 'provider' => $provider, 'status' => $code ) );
			return \MahmoudElsaad\Core\AI\Manager::local_fallback( $task, $prompt );
		}
		return $text;
	}

	/**
	 * Anthropic.
	 *
	 * @param string $key Key.
	 * @param string $prompt Prompt.
	 */
	private static function anthropic( string $key, string $prompt ): string {
		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 45,
				'headers' => array(
					'x-api-key'         => $key,
					'anthropic-version' => '2023-06-01',
					'content-type'      => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => 'claude-3-haiku-20240307',
						'max_tokens' => 512,
						'messages'   => array( array( 'role' => 'user', 'content' => $prompt ) ),
					)
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return $prompt;
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return (string) ( $body['content'][0]['text'] ?? $prompt );
	}

	/**
	 * Gemini.
	 *
	 * @param string $key Key.
	 * @param string $prompt Prompt.
	 */
	private static function gemini( string $key, string $prompt ): string {
		$response = wp_remote_post(
			'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent',
			array(
				'timeout' => 45,
				'headers' => array(
					'Content-Type'   => 'application/json',
					'x-goog-api-key' => $key,
				),
				'body'    => wp_json_encode( array( 'contents' => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ) ) ),
			)
		);
		if ( is_wp_error( $response ) ) {
			self::$last_error = (string) $response->get_error_code();
			\MahmoudElsaad\Core\Support\Logger::log( 'ai', 'Provider transport error', array( 'provider' => 'gemini', 'code' => $response->get_error_code() ) );
			return \MahmoudElsaad\Core\AI\Manager::local_fallback( 'title', $prompt );
		}
		$code              = (int) wp_remote_retrieve_response_code( $response );
		self::$last_status = $code;
		$raw               = (string) wp_remote_retrieve_body( $response );
		if ( $code >= 400 ) {
			self::$last_error = 'http';
			\MahmoudElsaad\Core\Support\Logger::log( 'ai', 'Provider HTTP error', array( 'provider' => 'gemini', 'status' => $code ) );
			return \MahmoudElsaad\Core\AI\Manager::local_fallback( 'title', $prompt );
		}
		$body = json_decode( $raw, true );
		if ( '' !== $raw && null === $body ) {
			self::$last_error = 'malformed';
			\MahmoudElsaad\Core\Support\Logger::log( 'ai', 'Provider malformed response', array( 'provider' => 'gemini', 'status' => $code ) );
			return \MahmoudElsaad\Core\AI\Manager::local_fallback( 'title', $prompt );
		}
		$text = (string) ( $body['candidates'][0]['content']['parts'][0]['text'] ?? '' );
		if ( '' === trim( $text ) ) {
			self::$last_error = 'empty';
			\MahmoudElsaad\Core\Support\Logger::log( 'ai', 'Provider empty response', array( 'provider' => 'gemini', 'status' => $code ) );
			return \MahmoudElsaad\Core\AI\Manager::local_fallback( 'title', $prompt );
		}
		return $text;
	}
}
