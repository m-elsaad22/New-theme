<?php
/**
 * Service × city relationships and overrides.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Relations;

use MahmoudElsaad\Core\Localization\Language;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ServiceCity {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'save_post_service', array( __CLASS__, 'touch' ) );
	}

	/**
	 * Table name.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mes_service_city';
	}

	/**
	 * Resolve a pair from slugs.
	 *
	 * @param string $service_slug Service slug.
	 * @param string $city_slug City slug.
	 * @return array<string, mixed>|null
	 */
	public static function resolve( string $service_slug, string $city_slug ): ?array {
		$service = get_page_by_path( $service_slug, OBJECT, 'service' );
		$city    = get_page_by_path( $city_slug, OBJECT, 'mes_city' );
		if ( ! $service || ! $city ) {
			return null;
		}

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . ' WHERE service_id = %d AND city_id = %d AND language_code IN (%s, %s) ORDER BY language_code = %s DESC LIMIT 1',
				$service->ID,
				$city->ID,
				Language::current(),
				Language::default_code(),
				Language::current()
			),
			ARRAY_A
		);

		if ( ! $row ) {
			$row = array(
				'service_id' => $service->ID,
				'city_id'    => $city->ID,
				'status'     => 'inherit',
			);
		}

		$row['service'] = $service;
		$row['city']    = $city;
		return $row;
	}

	/**
	 * Inherited landing data with overrides.
	 *
	 * @param array<string, mixed> $row Pair row.
	 * @return array<string, mixed>
	 */
	public static function landing( array $row ): array {
		$service = $row['service'];
		$city    = $row['city'];

		$title = $row['title'] ?: sprintf(
			/* translators: 1: service, 2: city */
			__( '%1$s in %2$s', 'mahmoud-elsaad-core' ),
			get_the_title( $service ),
			get_the_title( $city )
		);

		$content = $row['content'] ?: get_post_field( 'post_content', $service );
		$excerpt = $row['excerpt'] ?: ( $service->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $content ), 32 ) );

		return array(
			'title'       => $title,
			'content'     => $content,
			'excerpt'     => $excerpt,
			'image_id'    => (int) ( $row['image_id'] ?? 0 ) ?: get_post_thumbnail_id( $service ),
			'phone'       => $row['phone'] ?: get_post_meta( $service->ID, '_mes_phone', true ),
			'whatsapp'    => $row['whatsapp'] ?: get_post_meta( $service->ID, '_mes_whatsapp', true ),
			'seo_title'   => $row['seo_title'] ?: $title,
			'seo_desc'    => $row['seo_description'] ?: $excerpt,
			'service'     => $service,
			'city'        => $city,
			'is_override' => ! empty( $row['id'] ),
		);
	}

	/**
	 * Cities attached to a service.
	 *
	 * @param int $service_id Service ID.
	 * @return int[]
	 */
	public static function cities_for_service( int $service_id ): array {
		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT city_id FROM ' . self::table() . ' WHERE service_id = %d AND status != %s',
				$service_id,
				'draft'
			)
		);
		return array_map( 'intval', $ids );
	}

	/**
	 * Placeholder hook.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function touch( int $post_id ): void {
		clean_post_cache( $post_id );
	}
}
