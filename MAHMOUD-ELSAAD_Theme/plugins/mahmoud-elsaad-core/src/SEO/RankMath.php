<?php
/**
 * Rank Math integration — never replace, never duplicate schema.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\SEO;

use MahmoudElsaad\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RankMath {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_filter( 'mes_schema_should_emit', array( __CLASS__, 'should_emit' ) );
	}

	/**
	 * Whether Rank Math is active.
	 */
	public static function active(): bool {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( '\\RankMath' );
	}

	/**
	 * Skip internal JSON-LD when Rank Math already outputs graph types.
	 *
	 * @param bool $emit Current.
	 */
	public static function should_emit( bool $emit ): bool {
		if ( ! self::active() ) {
			return $emit;
		}
		$settings = Options::get( 'mes_seo_settings', array() );
		if ( ! empty( $settings['defer_to_rank_math'] ) ) {
			return false;
		}
		return $emit;
	}
}
