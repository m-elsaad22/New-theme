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
		self::strip_prefix();
		add_action( 'parse_request', array( __CLASS__, 'parse' ), 1 );
		add_action( 'parse_query', array( __CLASS__, 'parse_query' ) );
		add_filter( 'locale', array( __CLASS__, 'filter_locale' ) );
		add_action( 'wp_head', array( __CLASS__, 'hreflang' ), 2 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_query' ) );
		add_filter( 'get_canonical_url', array( __CLASS__, 'canonical' ), 10, 2 );
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
	 * Strip /ar/ or /en/ from REQUEST_URI so core permalinks keep working.
	 */
	public static function strip_prefix(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$uri  = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
		$path = wp_parse_url( $uri, PHP_URL_PATH );
		$path = is_string( $path ) ? $path : '/';
		if ( preg_match( '#/(wp-admin|wp-login\.php|wp-json|wp-cron\.php)#', $path ) ) {
			return;
		}

		$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		$rel  = trim( $path, '/' );
		if ( $home && 0 === strpos( $rel, $home ) ) {
			$rel = ltrim( substr( $rel, strlen( $home ) ), '/' );
		}

		$parts = $rel === '' ? array() : explode( '/', $rel );
		$first = $parts[0] ?? '';
		if ( ! in_array( $first, self::supported(), true ) ) {
			$GLOBALS['mes_language']           = self::default_code();
			$GLOBALS['mes_language_prefixed']  = false;
			$GLOBALS['mes_language_rest']      = $rel;
			return;
		}

		array_shift( $parts );
		$GLOBALS['mes_language']          = $first;
		$GLOBALS['mes_language_prefixed'] = true;
		$GLOBALS['mes_language_rest']     = implode( '/', $parts );

		$new_path = '/' . ( $home ? $home . '/' : '' ) . implode( '/', $parts );
		$new_path = $new_path === '//' ? '/' : $new_path;
		if ( '/' !== substr( $new_path, -1 ) && '' !== implode( '/', $parts ) ) {
			$new_path = trailingslashit( $new_path );
		}
		$query = wp_parse_url( $uri, PHP_URL_QUERY );
		$_SERVER['REQUEST_URI'] = $new_path . ( $query ? '?' . $query : '' );
		if ( isset( $_SERVER['PATH_INFO'] ) ) {
			$_SERVER['PATH_INFO'] = $new_path === '/' ? '' : $new_path;
		}
	}

	/**
	 * Detect language after strip.
	 *
	 * @param \WP $wp WP object.
	 */
	public static function parse( \WP $wp ): void {
		$pagename = (string) ( $wp->query_vars['pagename'] ?? $wp->query_vars['name'] ?? '' );
		if ( in_array( $pagename, self::supported(), true ) && empty( $GLOBALS['mes_language_rest'] ) ) {
			$GLOBALS['mes_language'] = $pagename;
			unset( $wp->query_vars['pagename'], $wp->query_vars['name'], $wp->query_vars['error'] );
		}
		if ( ! empty( $GLOBALS['mes_language'] ) ) {
			$wp->query_vars['mes_lang'] = (string) $GLOBALS['mes_language'];
			if ( ! empty( $GLOBALS['mes_language_prefixed'] ) && '' === (string) ( $GLOBALS['mes_language_rest'] ?? '' ) ) {
				unset( $wp->query_vars['pagename'], $wp->query_vars['name'], $wp->query_vars['error'] );
			}
			return;
		}
		$GLOBALS['mes_language'] = self::default_code();
	}

	/**
	 * Language-only URLs are the homepage, not a 404.
	 *
	 * @param \WP_Query $query Query.
	 */
	public static function parse_query( \WP_Query $query ): void {
		if ( ! $query->is_main_query() || is_admin() ) {
			return;
		}
		$lang_only = ! empty( $GLOBALS['mes_language_prefixed'] ) && $query->get( 'mes_lang' ) && ! $query->get( 'mes_service_city' ) && ! $query->get( 'p' ) && ! $query->get( 'page_id' );
		$rest      = (string) ( $GLOBALS['mes_language_rest'] ?? '' );
		if ( $lang_only && '' === $rest ) {
			$query->is_home       = true;
			$query->is_front_page = true;
			$query->is_404        = false;
			$query->is_page       = false;
			$query->is_singular   = false;
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
	 * Prefix a path with a language code.
	 *
	 * @param string      $path Path.
	 * @param string|null $lang Language.
	 */
	public static function url( string $path = '', ?string $lang = null ): string {
		$lang = $lang ? sanitize_key( $lang ) : self::current();
		$path = ltrim( $path, '/' );
		return home_url( '/' . $lang . '/' . $path );
	}

	/**
	 * hreflang tags for the current path.
	 */
	public static function hreflang(): void {
		$rest = isset( $GLOBALS['mes_language_rest'] ) ? (string) $GLOBALS['mes_language_rest'] : '';
		foreach ( self::supported() as $code ) {
			printf(
				'<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
				esc_attr( $code ),
				esc_url( self::url( $rest, $code ) )
			);
		}
		printf( '<link rel="alternate" hreflang="x-default" href="%s" />' . "\n", esc_url( self::url( $rest, self::default_code() ) ) );
	}

	/**
	 * Canonical with language prefix.
	 *
	 * @param string       $canonical Canonical.
	 * @param \WP_Post|null $post Post.
	 */
	public static function canonical( string $canonical, $post ): string {
		unset( $post );
		if ( class_exists( '\\MahmoudElsaad\\Core\\SEO\\RankMath' ) && \MahmoudElsaad\Core\SEO\RankMath::active() ) {
			return $canonical;
		}
		$rest = isset( $GLOBALS['mes_language_rest'] ) ? (string) $GLOBALS['mes_language_rest'] : '';
		if ( '' === $rest && $canonical ) {
			$path = (string) wp_parse_url( $canonical, PHP_URL_PATH );
			$rest = trim( $path, '/' );
		}
		return self::url( $rest, self::current() );
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
	 * Restrict public queries to the current language, including untagged content.
	 *
	 * @param \WP_Query $query Query.
	 */
	public static function filter_query( \WP_Query $query ): void {
		if ( is_admin() || $query->is_singular() || $query->is_search() || $query->is_home() || $query->is_front_page() ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		$type = $query->get( 'post_type' );
		$ok   = array( 'service', 'mes_city', 'mes_country', 'mes_offer', 'mes_review', 'mes_portfolio', 'mes_team', 'mes_partner', 'mes_faq', 'post', 'page' );
		if ( $type && ! in_array( $type, $ok, true ) && ! ( is_array( $type ) && array_intersect( (array) $type, $ok ) ) ) {
			return;
		}
		$lang_q   = self::query_args( array() )['meta_query'][0];
		$existing = $query->get( 'meta_query' );
		if ( empty( $existing ) ) {
			$query->set( 'meta_query', $lang_q );
			return;
		}
		$query->set(
			'meta_query',
			array(
				'relation' => 'AND',
				is_array( $existing ) ? $existing : array(),
				$lang_q,
			)
		);
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

	/**
	 * Sibling translation URL for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $lang Target language.
	 */
	public static function translation_url( int $post_id, string $lang ): string {
		$group = (string) get_post_meta( $post_id, '_mes_translation_group', true );
		if ( ! $group ) {
			return self::url( '', $lang );
		}
		$found = get_posts(
			array(
				'post_type'      => get_post_type( $post_id ) ?: 'any',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'   => '_mes_translation_group',
						'value' => $group,
					),
					array(
						'key'   => '_mes_language',
						'value' => $lang,
					),
				),
			)
		);
		if ( ! $found ) {
			return self::url( '', $lang );
		}
		$path = (string) wp_parse_url( get_permalink( $found[0] ), PHP_URL_PATH );
		return self::url( trim( $path, '/' ), $lang );
	}
}
