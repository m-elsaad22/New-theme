<?php
/**
 * JSON-LD graph with duplicate detection.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\SEO;

use MahmoudElsaad\Core\Helpers\Contact as ContactHelper;
use MahmoudElsaad\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SchemaGraph {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'print' ), 30 );
	}

	/**
	 * Print JSON-LD.
	 */
	public static function print(): void {
		if ( ! apply_filters( 'mes_schema_should_emit', true ) ) {
			return;
		}
		$graph = self::graph();
		if ( ! $graph ) {
			return;
		}
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}

	/**
	 * Build graph.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function graph(): array {
		$brand   = Options::get( 'mes_brand_settings', array() );
		$contact = Options::get( 'mes_contact_settings', array() );
		$name    = $brand['name'] ?: get_bloginfo( 'name' );
		$graph   = array();

		$org = array(
			'@type' => array( 'Organization', 'LocalBusiness' ),
			'@id'   => home_url( '/#organization' ),
			'name'  => $name,
			'url'   => home_url( '/' ),
		);
		if ( ! empty( $contact['email'] ) ) {
			$org['email'] = $contact['email'];
		}
		$phone = ContactHelper::phone();
		if ( $phone ) {
			$org['telephone'] = $phone;
		}
		$graph[] = $org;
		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => $name,
			'publisher'       => array( '@id' => home_url( '/#organization' ) ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);

		if ( is_singular( 'service' ) ) {
			$graph[] = array(
				'@type'       => 'Service',
				'name'        => get_the_title(),
				'description' => wp_strip_all_tags( get_the_excerpt() ),
				'provider'    => array( '@id' => home_url( '/#organization' ) ),
			);
		}
		if ( get_query_var( 'mes_service_city' ) && class_exists( '\\MahmoudElsaad\\Core\\Relations\\ServiceCity' ) ) {
			$row = \MahmoudElsaad\Core\Relations\ServiceCity::resolve(
				sanitize_title( (string) get_query_var( 'mes_service_slug' ) ),
				sanitize_title( (string) get_query_var( 'mes_city_slug' ) )
			);
			if ( $row ) {
				$landing = \MahmoudElsaad\Core\Relations\ServiceCity::landing( $row );
				$graph[] = array(
					'@type'       => 'Service',
					'name'        => $landing['title'],
					'description' => wp_strip_all_tags( $landing['excerpt'] ),
					'areaServed'  => get_the_title( $landing['city'] ),
					'provider'    => array( '@id' => home_url( '/#organization' ) ),
				);
			}
		}
		if ( is_singular( 'post' ) ) {
			$graph[] = array(
				'@type'         => array( 'Article', 'BlogPosting' ),
				'headline'      => get_the_title(),
				'datePublished' => get_the_date( 'c' ),
				'dateModified'  => get_the_modified_date( 'c' ),
			);
		}
		if ( is_post_type_archive( 'mes_faq' ) || is_singular( 'mes_faq' ) ) {
			$graph[] = array( '@type' => 'FAQPage', 'name' => __( 'FAQ', 'mahmoud-elsaad-core' ) );
		}
		if ( is_page_template( 'page-templates/contact.php' ) ) {
			$graph[] = array( '@type' => 'ContactPage', 'url' => get_permalink() );
		}
		if ( is_page_template( 'page-templates/about.php' ) ) {
			$graph[] = array( '@type' => 'AboutPage', 'url' => get_permalink() );
		}

		$seen  = array();
		$clean = array();
		foreach ( $graph as $node ) {
			$key = wp_json_encode( $node['@type'] ) . ( $node['@id'] ?? $node['name'] ?? '' );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$clean[]      = $node;
		}
		return $clean;
	}
}
