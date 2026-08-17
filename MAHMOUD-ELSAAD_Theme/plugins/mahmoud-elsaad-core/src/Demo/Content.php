<?php
/**
 * Optional sample content. Never hardcodes a brand name or phone number.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Demo;

use MahmoudElsaad\Core\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Content {
	/**
	 * Seed sample entities once.
	 *
	 * @return array<string, int>
	 */
	public static function seed(): array {
		$report = array(
			'services'  => 0,
			'faqs'      => 0,
			'team'      => 0,
			'partners'  => 0,
			'reviews'   => 0,
			'offers'    => 0,
			'portfolio' => 0,
			'pages'     => 0,
		);

		$report['services']  = self::seed_services();
		$report['faqs']      = self::seed_faqs();
		$report['team']      = self::seed_team();
		$report['partners']  = self::seed_partners();
		$report['reviews']   = self::seed_reviews();
		$report['offers']    = self::seed_offers();
		$report['portfolio'] = self::seed_portfolio();
		$report['pages']     = self::seed_pages();

		self::link_service_cities();
		update_option( 'mes_demo_seeded', 1 );
		Logger::log( 'demo', 'Sample content seeded', $report );
		return $report;
	}

	/**
	 * Services.
	 */
	private static function seed_services(): int {
		$items = array(
			array( 'كشف تسربات المياه', 'leak-detection', 'تحديد مصدر التسرب بدون تكسير.', array( 'كاميرا حرارية', 'بدون تكسير', 'تقرير مصور', 'إصلاح فوري' ), 'fa-droplet' ),
			array( 'عزل الأسطح', 'roof-insulation', 'حماية من الحرارة وتسرب المياه.', array( 'عزل فوم', 'أغشية بيتومينية', 'طلاء عاكس', 'ضمان مكتوب' ), 'fa-layer-group' ),
			array( 'صيانة التكييف', 'ac-maintenance', 'تنظيف وإصلاح شحن الفريون.', array( 'تنظيف الفلاتر', 'إصلاح الأعطال', 'شحن الفريون', 'قطع غيار' ), 'fa-snowflake' ),
			array( 'التنظيف والتعقيم', 'cleaning', 'تنظيف عميق وتعقيم آمن.', array( 'تنظيف شامل', 'تعقيم بالبخار', 'إزالة البقع', 'مواد آمنة' ), 'fa-spray-can-sparkles' ),
			array( 'أعمال السباكة', 'plumbing', 'إصلاح وتركيب شبكات المياه.', array( 'إصلاح الأنابيب', 'تركيب الصنابير', 'تسليك المجاري', 'فحص الشبكة' ), 'fa-wrench' ),
			array( 'مكافحة الحشرات', 'pest-control', 'إبادة آمنة مع متابعة.', array( 'مواد مرخصة', 'إبادة فورية', 'متابعة', 'آمن للأطفال' ), 'fa-bug-slash' ),
		);
		$count = 0;
		foreach ( $items as $item ) {
			if ( self::exists( 'service', $item[1] ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'service',
					'post_status'  => 'publish',
					'post_title'   => $item[0],
					'post_name'    => $item[1],
					'post_excerpt' => $item[2],
					'post_content' => $item[2],
				)
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_mes_language', 'ar' );
			update_post_meta( $id, '_mes_translation_group', wp_generate_uuid4() );
			update_post_meta( $id, '_mes_icon', $item[4] );
			update_post_meta( $id, '_mes_benefits', wp_json_encode( $item[3] ) );
			++$count;
		}
		return $count;
	}

	/**
	 * FAQs.
	 */
	private static function seed_faqs(): int {
		$items = array(
			array( 'هل المعاينة مجانية؟', 'نعم، يمكن ترتيب معاينة قبل التنفيذ حسب نوع الخدمة والمنطقة.' ),
			array( 'هل كشف التسرب يحتاج تكسير؟', 'نعتمد تقنيات غير إتلافية قدر الإمكان، ثم نصلح الموضع المحدد فقط.' ),
			array( 'هل تتوفر خدمة طوارئ؟', 'يمكن تفعيل الدعم على مدار الساعة من إعدادات التواصل في لوحة التحكم.' ),
			array( 'ما مدة الضمان؟', 'مدة الضمان تُحدد لكل خدمة وتُكتب في عرض السعر قبل التنفيذ.' ),
			array( 'هل تغطون أكثر من مدينة؟', 'نعم. المدن تُدار ككيانات مستقلة ويمكن إضافة أي مدينة من لوحة التحكم.' ),
			array( 'كيف أطلب عرض سعر؟', 'استخدم نموذج الحجز أو الطلب في الموقع، ويُحفظ الطلب كـ Lead مع إشعار بالبريد.' ),
		);
		$count = 0;
		foreach ( $items as $i => $item ) {
			$slug = 'faq-' . ( $i + 1 );
			if ( self::exists( 'mes_faq', $slug ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'mes_faq',
					'post_status'  => 'publish',
					'post_title'   => $item[0],
					'post_name'    => $slug,
					'post_content' => $item[1],
					'menu_order'   => $i,
				)
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_mes_language', 'ar' );
			update_post_meta( $id, '_mes_featured', '1' );
			++$count;
		}
		return $count;
	}

	/**
	 * Team.
	 */
	private static function seed_team(): int {
		$items = array(
			array( 'مدير العمليات', 'operations' ),
			array( 'خبير كشف التسربات', 'leak-expert' ),
			array( 'مشرف العزل', 'insulation-lead' ),
			array( 'مشرف الصيانة', 'maintenance-lead' ),
		);
		$count = 0;
		foreach ( $items as $item ) {
			if ( self::exists( 'mes_team', $item[1] ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'   => 'mes_team',
					'post_status' => 'publish',
					'post_title'  => $item[0],
					'post_name'   => $item[1],
					'post_content'=> $item[0],
				)
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_mes_position', $item[0] );
			update_post_meta( $id, '_mes_language', 'ar' );
			++$count;
		}
		return $count;
	}

	/**
	 * Partners.
	 */
	private static function seed_partners(): int {
		$names = array( 'Sika', 'Fosroc', 'Jotun', 'Mapei', 'Weber' );
		$count = 0;
		foreach ( $names as $i => $name ) {
			$slug = sanitize_title( $name );
			if ( self::exists( 'mes_partner', $slug ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'   => 'mes_partner',
					'post_status' => 'publish',
					'post_title'  => $name,
					'post_name'   => $slug,
					'menu_order'  => $i,
				)
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_mes_featured', '1' );
			update_post_meta( $id, '_mes_sort_order', (string) $i );
			++$count;
		}
		return $count;
	}

	/**
	 * Reviews.
	 */
	private static function seed_reviews(): int {
		$items = array(
			array( 'عميل 1', 'تنفيذ دقيق والتزام بالموعد.' ),
			array( 'عميل 2', 'الفريق واضح في التسعير والمتابعة.' ),
			array( 'عميل 3', 'الاستجابة كانت سريعة والنتيجة واضحة.' ),
			array( 'عميل 4', 'عمل نظيف مع شرح للضمان.' ),
		);
		$count = 0;
		foreach ( $items as $i => $item ) {
			$slug = 'review-' . ( $i + 1 );
			if ( self::exists( 'mes_review', $slug ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'mes_review',
					'post_status'  => 'publish',
					'post_title'   => $item[0],
					'post_name'    => $slug,
					'post_content' => $item[1],
				)
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_mes_customer_name', $item[0] );
			update_post_meta( $id, '_mes_rating', '5' );
			update_post_meta( $id, '_mes_source', 'manual' );
			update_post_meta( $id, '_mes_verified', '1' );
			update_post_meta( $id, '_mes_featured', '1' );
			++$count;
		}
		return $count;
	}

	/**
	 * Offers.
	 */
	private static function seed_offers(): int {
		if ( self::exists( 'mes_offer', 'inspection-offer' ) ) {
			return 0;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'mes_offer',
				'post_status'  => 'publish',
				'post_title'   => 'معاينة مجانية',
				'post_name'    => 'inspection-offer',
				'post_excerpt' => 'معاينة ميدانية قبل التنفيذ حسب التوفر.',
				'post_content' => 'تُحدد الشروط من لوحة التحكم لكل عرض.',
			)
		);
		if ( is_wp_error( $id ) ) {
			return 0;
		}
		update_post_meta( $id, '_mes_cta_label', 'اطلب المعاينة' );
		update_post_meta( $id, '_mes_language', 'ar' );
		return 1;
	}

	/**
	 * Portfolio samples.
	 */
	private static function seed_portfolio(): int {
		$items = array(
			array( 'مشروع عزل سطح', 'roof-project' ),
			array( 'مشروع كشف تسرب', 'leak-project' ),
			array( 'مشروع صيانة تكييف', 'ac-project' ),
		);
		$count = 0;
		foreach ( $items as $item ) {
			if ( self::exists( 'mes_portfolio', $item[1] ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'mes_portfolio',
					'post_status'  => 'publish',
					'post_title'   => $item[0],
					'post_name'    => $item[1],
					'post_excerpt' => $item[0],
					'post_content' => $item[0],
				)
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_mes_featured', '1' );
			update_post_meta( $id, '_mes_language', 'ar' );
			++$count;
		}
		return $count;
	}

	/**
	 * Utility pages with templates.
	 */
	private static function seed_pages(): int {
		$pages = array(
			array( 'من نحن', 'about', 'page-templates/about.php' ),
			array( 'تواصل معنا', 'contact', 'page-templates/contact.php' ),
			array( 'الحجز', 'booking', 'page-templates/booking.php' ),
			array( 'طلب عرض سعر', 'quote', 'page-templates/quote.php' ),
			array( 'سياسة الخصوصية', 'privacy', 'page-templates/legal.php' ),
			array( 'الشروط والأحكام', 'terms', 'page-templates/legal.php' ),
		);
		$count = 0;
		foreach ( $pages as $page ) {
			if ( self::exists( 'page', $page[1] ) ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page[0],
					'post_name'    => $page[1],
					'post_content' => $page[0],
				)
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			update_post_meta( $id, '_wp_page_template', $page[2] );
			update_post_meta( $id, '_mes_language', 'ar' );
			++$count;
		}
		return $count;
	}

	/**
	 * Publish inherit rows so service×city URLs resolve.
	 */
	private static function link_service_cities(): void {
		$services = get_posts( array( 'post_type' => 'service', 'posts_per_page' => 20, 'fields' => 'ids' ) );
		$cities   = get_posts(
			array(
				'post_type'      => 'mes_city',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'meta_key'       => '_mes_language',
				'meta_value'     => 'ar',
			)
		);
		global $wpdb;
		$table = $wpdb->prefix . 'mes_service_city';
		foreach ( $services as $sid ) {
			foreach ( $cities as $cid ) {
				$wpdb->query(
					$wpdb->prepare(
						"INSERT IGNORE INTO {$table} (service_id, city_id, language_code, status, updated_at) VALUES (%d, %d, %s, %s, %s)",
						$sid,
						$cid,
						'ar',
						'inherit',
						current_time( 'mysql', true )
					)
				);
			}
		}
	}

	/**
	 * Slug exists.
	 *
	 * @param string $type Type.
	 * @param string $slug Slug.
	 */
	private static function exists( string $type, string $slug ): bool {
		$found = get_posts(
			array(
				'post_type'      => $type,
				'name'           => $slug,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		return ! empty( $found );
	}
}
