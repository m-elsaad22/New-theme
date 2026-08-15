<?php
/**
 * Titles and meta when no SEO plugin owns them.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Meta {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_filter( 'document_title_parts', array( __CLASS__, 'title_parts' ) );
		add_action( 'wp_head', array( __CLASS__, 'description' ), 3 );
	}

	/**
	 * Title parts.
	 *
	 * @param array<string, string> $parts Parts.
	 * @return array<string, string>
	 */
	public static function title_parts( array $parts ): array {
		if ( RankMath::active() ) {
			return $parts;
		}
		if ( get_query_var( 'mes_service_city' ) ) {
			$parts['title'] = get_query_var( 'mes_service_slug' ) . ' — ' . get_query_var( 'mes_city_slug' );
		}
		return $parts;
	}

	/**
	 * Meta description fallback.
	 */
	public static function description(): void {
		if ( RankMath::active() ) {
			return;
		}
		$desc = '';
		if ( is_singular() ) {
			$desc = get_the_excerpt();
		}
		if ( $desc ) {
			echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '" />' . "\n";
		}
	}
}
