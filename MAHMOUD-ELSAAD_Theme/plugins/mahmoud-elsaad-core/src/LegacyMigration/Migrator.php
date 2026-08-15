<?php
/**
 * Legacy migration pipeline: Detect → Map → Transform → Validate.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\LegacyMigration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migrator {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'admin_post_mes_run_migration', array( __CLASS__, 'admin_run' ) );
	}

	/**
	 * REST entry.
	 *
	 * @param \WP_REST_Request $request Request.
	 */
	public static function rest_run( \WP_REST_Request $request ) {
		if ( ! wp_verify_nonce( (string) $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new \WP_Error( 'mes_bad_nonce', 'Invalid nonce', array( 'status' => 403 ) );
		}
		return rest_ensure_response( self::run() );
	}

	/**
	 * Admin POST fallback.
	 */
	public static function admin_run(): void {
		check_admin_referer( 'mes_run_migration' );
		self::run();
		wp_safe_redirect( admin_url( 'admin.php?page=mes-control-center&view=migration&done=1' ) );
		exit;
	}

	/**
	 * Execute migration.
	 *
	 * @return array<string, mixed>
	 */
	public static function run(): array {
		$report = array(
			'backup'    => Backup::create(),
			'faq'       => 0,
			'works'     => 0,
			'price'     => 0,
			'cities'    => 0,
			'services'  => 0,
			'options'   => 0,
			'skipped'   => array(),
		);

		$report['faq']      = self::copy_cpt( 'faq', 'mes_faq' );
		$report['works']    = self::copy_cpt( 'works', 'mes_portfolio', array( 'client__name' => '_mes_client', 'services__rate' => '_mes_rating' ) );
		$report['price']    = self::copy_cpt( 'price', 'mes_offer', array( 'price_text' => '_mes_price', 'offer' => '_mes_discount' ) );
		$report['cities']   = self::terms_to_cpt( 'city', 'mes_city' );
		$report['services'] = self::categories_to_services();
		$report['options']  = self::map_options();

		update_option( 'mes_migration_state', $report );
		return $report;
	}

	/**
	 * Copy a public CPT.
	 *
	 * @param string                $from From type.
	 * @param string                $to To type.
	 * @param array<string, string> $meta Meta map.
	 */
	private static function copy_cpt( string $from, string $to, array $meta = array() ): int {
		if ( ! post_type_exists( $from ) ) {
			return 0;
		}
		$count = 0;
		$posts = get_posts(
			array(
				'post_type'      => $from,
				'posts_per_page' => 200,
				'post_status'    => 'any',
			)
		);
		foreach ( $posts as $post ) {
			if ( get_post_meta( $post->ID, '_mes_migrated_to', true ) ) {
				continue;
			}
			$new = wp_insert_post(
				array(
					'post_type'    => $to,
					'post_status'  => $post->post_status,
					'post_title'   => $post->post_title,
					'post_content' => $post->post_content,
					'post_excerpt' => $post->post_excerpt,
					'post_name'    => $post->post_name,
				)
			);
			if ( is_wp_error( $new ) ) {
				continue;
			}
			$thumb = get_post_thumbnail_id( $post );
			if ( $thumb ) {
				set_post_thumbnail( $new, $thumb );
			}
			foreach ( $meta as $old => $new_key ) {
				$val = get_post_meta( $post->ID, $old, true );
				if ( '' !== $val && false !== $val ) {
					update_post_meta( $new, $new_key, $val );
				}
			}
			update_post_meta( $post->ID, '_mes_migrated_to', $new );
			update_post_meta( $new, '_mes_migrated_from', $post->ID );
			update_post_meta( $new, '_mes_language', 'ar' );
			++$count;
		}
		return $count;
	}

	/**
	 * Convert taxonomy terms to CPT.
	 *
	 * @param string $tax Taxonomy.
	 * @param string $type CPT.
	 */
	private static function terms_to_cpt( string $tax, string $type ): int {
		if ( ! taxonomy_exists( $tax ) ) {
			return 0;
		}
		$count = 0;
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return 0;
		}
		foreach ( $terms as $term ) {
			$existing = get_page_by_path( $term->slug, OBJECT, $type );
			if ( $existing ) {
				update_term_meta( $term->term_id, '_mes_migrated_to', $existing->ID );
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => $type,
					'post_status'  => 'publish',
					'post_title'   => $term->name,
					'post_name'    => $term->slug,
					'post_content' => $term->description,
				)
			);
			if ( ! is_wp_error( $id ) ) {
				update_term_meta( $term->term_id, '_mes_migrated_to', $id );
				update_post_meta( $id, '_mes_language', 'ar' );
				++$count;
			}
		}
		return $count;
	}

	/**
	 * Map service-like categories to service CPT.
	 */
	private static function categories_to_services(): int {
		$count = 0;
		$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return 0;
		}
		foreach ( $terms as $term ) {
			if ( (int) $term->term_id === (int) get_option( 'default_category' ) ) {
				continue;
			}
			$existing = get_page_by_path( $term->slug, OBJECT, 'service' );
			if ( $existing ) {
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'service',
					'post_status'  => 'publish',
					'post_title'   => $term->name,
					'post_name'    => $term->slug,
					'post_content' => $term->description,
					'post_excerpt' => wp_trim_words( wp_strip_all_tags( $term->description ), 24 ),
				)
			);
			if ( is_wp_error( $id ) ) {
				continue;
			}
			$icon = get_term_meta( $term->term_id, 'icon', true );
			if ( $icon ) {
				update_post_meta( $id, '_mes_icon', $icon );
			}
			update_term_meta( $term->term_id, '_mes_migrated_to', $id );
			update_post_meta( $id, '_mes_language', 'ar' );
			++$count;
		}
		return $count;
	}

	/**
	 * Map known legacy options into namespaced settings. Never copies API keys.
	 */
	private static function map_options(): int {
		$brand   = get_option( 'mes_brand_settings', array() );
		$contact = get_option( 'mes_contact_settings', array() );
		$mapped  = 0;

		$sitename = get_option( 'sitename' );
		if ( $sitename && empty( $brand['name'] ) ) {
			$brand['name'] = sanitize_text_field( (string) $sitename );
			++$mapped;
		}
		$phone = get_option( 'phonenumber' );
		if ( $phone ) {
			$contact['phones'][] = array(
				'number'  => sanitize_text_field( (string) $phone ),
				'primary' => true,
			);
			++$mapped;
		}
		$wa = get_option( 'whatsapp_number' );
		if ( $wa ) {
			$contact['whatsapps'][] = array(
				'number'  => sanitize_text_field( (string) $wa ),
				'primary' => true,
			);
			++$mapped;
		}
		foreach ( array( 'facebook', 'instagram', 'youtube', 'linkedin', 'telegram' ) as $net ) {
			$val = get_option( $net );
			if ( $val ) {
				$contact['social'][ $net ] = esc_url_raw( (string) $val );
				++$mapped;
			}
		}
		$mail = get_option( 'company__mail' );
		if ( $mail ) {
			$contact['email'] = sanitize_email( (string) $mail );
			++$mapped;
		}
		$addr = get_option( 'company__adress' );
		if ( $addr ) {
			$contact['address'] = sanitize_text_field( (string) $addr );
			++$mapped;
		}

		update_option( 'mes_brand_settings', $brand );
		update_option( 'mes_contact_settings', $contact );
		return $mapped;
	}
}
