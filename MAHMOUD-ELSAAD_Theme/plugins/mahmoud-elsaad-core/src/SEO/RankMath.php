<?php
/**
 * Rank Math integration — never replace, never duplicate schema.
 *
 * Ownership when Rank Math is active and defer_to_rank_math is on:
 * Rank Math owns Article/BlogPosting, BreadcrumbList, WebSite, Organization,
 * canonical, title, robots, description.
 * MAHMOUD Core injects LocalBusiness, Service, FAQPage, Service×City (Service + areaServed)
 * into Rank Math's json_ld graph ONLY when Rank Math did not already output that @type.
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
	 * Types Rank Math owns whenever it is active. MES never injects these.
	 *
	 * @var array<int, string>
	 */
	private const RANK_MATH_OWNED = array(
		'Organization',
		'WebSite',
		'WebPage',
		'Article',
		'BlogPosting',
		'NewsArticle',
		'BreadcrumbList',
		'SearchAction',
		'ImageObject',
		'Person',
	);

	/**
	 * Types MES may add when Rank Math's current graph lacks them.
	 *
	 * @var array<int, string>
	 */
	private const MES_OWNED_IF_ABSENT = array(
		'LocalBusiness',
		'Service',
		'FAQPage',
		'ContactPage',
		'AboutPage',
	);

	/**
	 * Init.
	 */
	public static function init(): void {
		add_filter( 'mes_schema_should_emit', array( __CLASS__, 'should_emit' ) );
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'inject' ), 99, 2 );
	}

	/**
	 * Whether Rank Math is active.
	 */
	public static function active(): bool {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( '\\RankMath' );
	}

	/**
	 * Skip the standalone MES JSON-LD script when Rank Math owns the graph.
	 * MES pieces are merged via rank_math/json_ld instead (no second script tag).
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

	/**
	 * Inject MES-owned types into Rank Math's graph when they are missing.
	 *
	 * @param array<string, mixed> $data   Rank Math json_ld pieces.
	 * @param mixed                $jsonld JsonLD instance (unused).
	 * @return array<string, mixed>
	 */
	public static function inject( $data, $jsonld = null ): array {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		if ( ! self::active() ) {
			return $data;
		}
		$settings = Options::get( 'mes_seo_settings', array() );
		if ( empty( $settings['defer_to_rank_math'] ) ) {
			return $data;
		}

		$existing = self::types_in( $data );
		foreach ( SchemaGraph::graph() as $entity ) {
			$types = array_values( array_filter( array_map( 'strval', (array) ( $entity['@type'] ?? array() ) ) ) );
			if ( ! $types ) {
				continue;
			}
			if ( self::should_skip_entity( $types, $existing ) ) {
				continue;
			}
			$key          = 'mes-' . strtolower( implode( '-', $types ) );
			$suffix       = 1;
			$original_key = $key;
			while ( isset( $data[ $key ] ) ) {
				$key = $original_key . '-' . $suffix;
				++$suffix;
			}
			$data[ $key ] = $entity;
			foreach ( $types as $t ) {
				$existing[ strtolower( $t ) ] = true;
			}
		}
		return $data;
	}

	/**
	 * @param array<int, string>   $types    Entity types.
	 * @param array<string, bool>  $existing Lowercased types already in the graph.
	 */
	private static function should_skip_entity( array $types, array $existing ): bool {
		foreach ( $types as $t ) {
			if ( in_array( $t, self::RANK_MATH_OWNED, true ) ) {
				return true;
			}
			if ( isset( $existing[ strtolower( $t ) ] ) ) {
				return true;
			}
			if ( ! in_array( $t, self::MES_OWNED_IF_ABSENT, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Collect @type values already present in Rank Math's data.
	 *
	 * @param array<string, mixed> $data Data.
	 * @return array<string, bool>
	 */
	private static function types_in( array $data ): array {
		$types = array();
		$walk  = static function ( $node ) use ( &$walk, &$types ): void {
			if ( ! is_array( $node ) ) {
				return;
			}
			if ( isset( $node['@type'] ) ) {
				foreach ( (array) $node['@type'] as $t ) {
					$types[ strtolower( (string) $t ) ] = true;
				}
			}
			foreach ( $node as $v ) {
				if ( is_array( $v ) ) {
					$walk( $v );
				}
			}
		};
		$walk( $data );
		return $types;
	}
}
