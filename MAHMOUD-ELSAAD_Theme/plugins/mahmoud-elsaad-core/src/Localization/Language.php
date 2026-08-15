<?php
/**
 * Language resolver. Independent of country.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Localization;

use MahmoudElsaad\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Language {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'parse_request', array( __CLASS__, 'parse' ), 1 );
		add_filter( 'locale', array( __CLASS__, 'filter_locale' ) );
		add_action( 'wp_head', array( __CLASS__, 'hreflang' ), 2 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Supported languages.
	 *
	 * @return string[]
	 */
	public static function supported(): array {
		$settings = Options::get( 'mes_i18n_settings', array() );
		$langs    = $settings['languages'] ?? array( 'ar', 'en' );
		return array_values( array_unique( array_map( 'sanitize_key', (array) $langs ) ) );
	}

	/**
	 * Default language.
	 */
	public static function default_code(): string {
		$settings = Options::get( 'mes_i18n_settings', array() );
		return sanitize_key( $settings['default'] ?? 'ar' );
	}

	/**
	 * Current request language.
	 */
	public static function current(): string {
		$code = get_query_var( 'mes_lang' );
		if ( $code && in_array( $code, self::supported(), true ) ) {
			return $code;
		}
		if ( ! empty( $GLOBALS['mes_language'] ) ) {
			return (string) $GLOBALS['mes_language'];
		}
		return self::default_code();
	}

	/**
	 * Detect /ar/ or /en/ prefix.
	 *
	 * @param \WP $wp WP object.
	 */
	public static function parse( \WP $wp ): void {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
		$path = is_string( $path ) ? trim( $path, '/' ) : '';
		$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		if ( $home && 0 === strpos( $path, $home ) ) {
			$path = ltrim( substr( $path, strlen( $home ) ), '/' );
		}
		$first = explode( '/', $path )[0] ?? '';
		if ( in_array( $first, self::supported(), true ) ) {
			$GLOBALS['mes_language'] = $first;
			$wp->query_vars['mes_lang'] = $first;
		} else {
			$GLOBALS['mes_language'] = self::default_code();
		}
	}

	/**
	 * Filter locale.
	 *
	 * @param string $locale Locale.
	 */
	public static function filter_locale( string $locale ): string {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return $locale;
		}
		return 'en' === self::current() ? 'en_US' : 'ar';
	}

	/**
	 * Direction.
	 */
	public static function dir(): string {
		return 'en' === self::current() ? 'ltr' : 'rtl';
	}

	/**
	 * hreflang tags.
	 */
	public static function hreflang(): void {
		$url = home_url( '/' );
		foreach ( self::supported() as $code ) {
			printf(
				'<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
				esc_attr( $code ),
				esc_url( trailingslashit( $url ) . $code . '/' )
			);
		}
		printf( '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n", esc_url( $url ) );
	}

	/**
	 * Body class.
	 *
	 * @param string[] $classes Classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		$classes[] = 'mes-lang-' . self::current();
		$classes[] = 'mes-dir-' . self::dir();
		return $classes;
	}

	/**
	 * Query posts in the current language.
	 *
	 * @param array<string, mixed> $args WP_Query args.
	 * @return array<string, mixed>
	 */
	public static function query_args( array $args ): array {
		$args['meta_query']   = $args['meta_query'] ?? array();
		$args['meta_query'][] = array(
			'relation' => 'OR',
			array(
				'key'   => '_mes_language',
				'value' => self::current(),
			),
			array(
				'key'     => '_mes_language',
				'compare' => 'NOT EXISTS',
			),
		);
		return $args;
	}
}
