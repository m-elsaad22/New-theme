<?php
/**
 * Pretty permalinks for service × city landings.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Routing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rewrites {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'vars' ) );
		add_filter( 'template_include', array( __CLASS__, 'template' ) );
	}

	/**
	 * Extra query vars.
	 *
	 * @param string[] $vars Vars.
	 * @return string[]
	 */
	public static function vars( array $vars ): array {
		$vars[] = 'mes_lang';
		$vars[] = 'mes_service_city';
		$vars[] = 'mes_service_slug';
		$vars[] = 'mes_city_slug';
		return $vars;
	}

	/**
	 * Rewrite rules. Language prefix is optional.
	 */
	public static function rules(): void {
		add_rewrite_tag( '%mes_service_city%', '1' );
		add_rewrite_tag( '%mes_service_slug%', '([^/]+)' );
		add_rewrite_tag( '%mes_city_slug%', '([^/]+)' );
		add_rewrite_tag( '%mes_lang%', '(ar|en)' );

		add_rewrite_rule(
			'^(ar|en)/?$',
			'index.php?mes_lang=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^(ar|en)/services/([^/]+)/([^/]+)/?$',
			'index.php?mes_lang=$matches[1]&mes_service_city=1&mes_service_slug=$matches[2]&mes_city_slug=$matches[3]',
			'top'
		);
		add_rewrite_rule(
			'^services/([^/]+)/([^/]+)/?$',
			'index.php?mes_service_city=1&mes_service_slug=$matches[1]&mes_city_slug=$matches[2]',
			'top'
		);
	}

	/**
	 * Load the service-city template.
	 *
	 * @param string $template Template.
	 */
	public static function template( string $template ): string {
		if ( ! get_query_var( 'mes_service_city' ) ) {
			return $template;
		}
		$located = locate_template( array( 'templates/service-city.php', 'service-city.php' ) );
		return $located ? $located : $template;
	}
}
