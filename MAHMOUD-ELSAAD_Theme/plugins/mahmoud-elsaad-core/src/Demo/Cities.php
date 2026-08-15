<?php
/**
 * Pre-seed capital/emirate cities for the seven launch countries.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Demo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cities {
	/**
	 * Seed once.
	 */
	public static function seed(): void {
		if ( get_option( 'mes_cities_seeded' ) ) {
			return;
		}

		foreach ( self::catalog() as $row ) {
			$group = wp_generate_uuid4();
			$ar_id = self::insert_city(
				$row['ar'],
				$row['slug'],
				$row['en'],
				$row['iso'],
				'ar',
				$group
			);
			$en_id = self::insert_city(
				$row['en'],
				$row['slug'] . '-en',
				$row['ar'],
				$row['iso'],
				'en',
				$group
			);
			if ( $ar_id && $en_id ) {
				update_post_meta( $ar_id, '_mes_translation_peer', (string) $en_id );
				update_post_meta( $en_id, '_mes_translation_peer', (string) $ar_id );
			}
		}

		update_option( 'mes_cities_seeded', 1 );
	}

	/**
	 * Catalog — geographic entities, not demo branding.
	 *
	 * @return array<int, array<string, string>>
	 */
	private static function catalog(): array {
		return array(
			array( 'iso' => 'AE', 'slug' => 'dubai', 'ar' => 'دبي', 'en' => 'Dubai' ),
			array( 'iso' => 'AE', 'slug' => 'abu-dhabi', 'ar' => 'أبوظبي', 'en' => 'Abu Dhabi' ),
			array( 'iso' => 'AE', 'slug' => 'sharjah', 'ar' => 'الشارقة', 'en' => 'Sharjah' ),
			array( 'iso' => 'AE', 'slug' => 'ajman', 'ar' => 'عجمان', 'en' => 'Ajman' ),
			array( 'iso' => 'AE', 'slug' => 'ras-al-khaimah', 'ar' => 'رأس الخيمة', 'en' => 'Ras Al Khaimah' ),
			array( 'iso' => 'AE', 'slug' => 'fujairah', 'ar' => 'الفجيرة', 'en' => 'Fujairah' ),
			array( 'iso' => 'AE', 'slug' => 'umm-al-quwain', 'ar' => 'أم القيوين', 'en' => 'Umm Al Quwain' ),
			array( 'iso' => 'SA', 'slug' => 'riyadh', 'ar' => 'الرياض', 'en' => 'Riyadh' ),
			array( 'iso' => 'SA', 'slug' => 'jeddah', 'ar' => 'جدة', 'en' => 'Jeddah' ),
			array( 'iso' => 'SA', 'slug' => 'dammam', 'ar' => 'الدمام', 'en' => 'Dammam' ),
			array( 'iso' => 'QA', 'slug' => 'doha', 'ar' => 'الدوحة', 'en' => 'Doha' ),
			array( 'iso' => 'OM', 'slug' => 'muscat', 'ar' => 'مسقط', 'en' => 'Muscat' ),
			array( 'iso' => 'BH', 'slug' => 'manama', 'ar' => 'المنامة', 'en' => 'Manama' ),
			array( 'iso' => 'KW', 'slug' => 'kuwait-city', 'ar' => 'مدينة الكويت', 'en' => 'Kuwait City' ),
			array( 'iso' => 'EG', 'slug' => 'cairo', 'ar' => 'القاهرة', 'en' => 'Cairo' ),
			array( 'iso' => 'EG', 'slug' => 'alexandria', 'ar' => 'الإسكندرية', 'en' => 'Alexandria' ),
		);
	}

	/**
	 * Insert one city post.
	 *
	 * @param string $title Title.
	 * @param string $slug Slug.
	 * @param string $excerpt Excerpt.
	 * @param string $iso Country ISO.
	 * @param string $lang Language.
	 * @param string $group Translation group.
	 */
	private static function insert_city( string $title, string $slug, string $excerpt, string $iso, string $lang, string $group ): int {
		$existing = get_posts(
			array(
				'post_type'      => 'mes_city',
				'name'           => $slug,
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'mes_city',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_excerpt' => $excerpt,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		update_post_meta( $id, '_mes_country_code', strtoupper( $iso ) );
		update_post_meta( $id, '_mes_language', $lang );
		update_post_meta( $id, '_mes_translation_group', $group );
		return (int) $id;
	}
}
