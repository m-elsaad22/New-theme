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
		$service = self::by_slug( $service_slug, 'service' );
		$city    = self::by_slug( $city_slug, 'mes_city' );
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

		if ( isset( $row['status'] ) && 'draft' === $row['status'] ) {
			return null;
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
	 * Upsert an override row.
	 *
	 * @param array<string, mixed> $data Data.
	 */
	public static function upsert( array $data ): int {
		global $wpdb;
		$service_id = absint( $data['service_id'] ?? 0 );
		$city_id    = absint( $data['city_id'] ?? 0 );
		$lang       = sanitize_key( $data['language_code'] ?? Language::current() );
		if ( ! $service_id || ! $city_id ) {
			return 0;
		}

		$row = array(
			'service_id'      => $service_id,
			'city_id'         => $city_id,
			'language_code'   => $lang,
			'status'          => sanitize_key( $data['status'] ?? 'publish' ),
			'title'           => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'excerpt'         => sanitize_textarea_field( (string) ( $data['excerpt'] ?? '' ) ),
			'content'         => wp_kses_post( (string) ( $data['content'] ?? '' ) ),
			'faq'             => sanitize_textarea_field( (string) ( $data['faq'] ?? '' ) ),
			'image_id'        => absint( $data['image_id'] ?? 0 ),
			'phone'           => sanitize_text_field( (string) ( $data['phone'] ?? '' ) ),
			'whatsapp'        => sanitize_text_field( (string) ( $data['whatsapp'] ?? '' ) ),
			'seo_title'       => sanitize_text_field( (string) ( $data['seo_title'] ?? '' ) ),
			'seo_description' => sanitize_textarea_field( (string) ( $data['seo_description'] ?? '' ) ),
			'cta_label'       => sanitize_text_field( (string) ( $data['cta_label'] ?? '' ) ),
			'updated_at'      => current_time( 'mysql', true ),
		);

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . self::table() . ' WHERE service_id = %d AND city_id = %d AND language_code = %s',
				$service_id,
				$city_id,
				$lang
			)
		);

		if ( $existing ) {
			$wpdb->update( self::table(), $row, array( 'id' => (int) $existing ) );
			return (int) $existing;
		}

		$wpdb->insert( self::table(), $row );
		return (int) $wpdb->insert_id;
	}

	/**
	 * List overrides.
	 *
	 * @param int $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list( int $limit = 100 ): array {
		global $wpdb;
		$limit = max( 1, min( 500, $limit ) );
		return (array) $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY updated_at DESC LIMIT %d', $limit ),
			ARRAY_A
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
	 * Public landing URL.
	 *
	 * @param \WP_Post $service Service.
	 * @param \WP_Post $city City.
	 */
	public static function url( \WP_Post $service, \WP_Post $city ): string {
		return Language::url( 'services/' . $service->post_name . '/' . $city->post_name . '/' );
	}

	/**
	 * Resolve a post by slug, preferring the current language.
	 *
	 * @param string $slug Slug.
	 * @param string $type Type.
	 */
	private static function by_slug( string $slug, string $type ): ?\WP_Post {
		$posts = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => $type,
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'no_found_rows'  => true,
			)
		);
		if ( ! $posts ) {
			return null;
		}
		$lang = Language::current();
		foreach ( $posts as $post ) {
			$meta = (string) get_post_meta( $post->ID, '_mes_language', true );
			if ( $meta === $lang || ( '' === $meta && $lang === Language::default_code() ) ) {
				return $post;
			}
		}
		return $posts[0];
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
