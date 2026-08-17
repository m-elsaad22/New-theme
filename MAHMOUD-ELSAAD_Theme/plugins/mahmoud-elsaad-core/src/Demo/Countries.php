<?php
/**
 * Pre-configured countries. Dynamic — not hardcoded into theme logic.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Demo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Countries {
	/**
	 * Seed seven countries once.
	 */
	public static function seed(): void {
		if ( get_option( 'mes_countries_seeded' ) ) {
			return;
		}
		$list = array(
			array( 'ae', 'الإمارات', 'United Arab Emirates', '+971' ),
			array( 'sa', 'السعودية', 'Saudi Arabia', '+966' ),
			array( 'qa', 'قطر', 'Qatar', '+974' ),
			array( 'om', 'عمان', 'Oman', '+968' ),
			array( 'bh', 'البحرين', 'Bahrain', '+973' ),
			array( 'kw', 'الكويت', 'Kuwait', '+965' ),
			array( 'eg', 'مصر', 'Egypt', '+20' ),
		);
		foreach ( $list as $row ) {
			$id = wp_insert_post(
				array(
					'post_type'   => 'mes_country',
					'post_status' => 'publish',
					'post_title'  => $row[1],
					'post_name'   => $row[0],
					'post_excerpt'=> $row[2],
				)
			);
			if ( ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_mes_iso', strtoupper( $row[0] ) );
				update_post_meta( $id, '_mes_phone_prefix', $row[3] );
				update_post_meta( $id, '_mes_language', 'ar' );
			}
		}
		update_option( 'mes_countries_seeded', 1 );
	}
}
