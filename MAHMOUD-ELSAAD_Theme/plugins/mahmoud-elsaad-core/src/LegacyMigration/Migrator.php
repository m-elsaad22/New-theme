<?php
/**
 * Legacy migration pipeline: Detect → Map → Transform → Validate.
 *
 * Native WordPress posts, pages, comments, menus, and attachments remain native.
 * Source CPTs are copied, not deleted. Unmapped meta stays on the source object.
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
		$nonce = (string) $request->get_header( 'X-WP-Nonce' );
		if ( $nonce && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
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
	 * Inventory of source/target counts before or after a run.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function inventory(): array {
		$native_reason = 'Native WordPress; remains valid. Not converted.';
		$rows          = array();

		$rows['posts'] = self::row( 'posts', self::count_posts( 'post' ), self::count_posts( 'post' ), 0, self::count_posts( 'post' ), 0, $native_reason );
		$rows['pages'] = self::row( 'pages', self::count_posts( 'page' ), self::count_posts( 'page' ), 0, self::count_posts( 'page' ), 0, $native_reason );
		$rows['comments'] = self::row( 'comments', self::count_comments(), self::count_comments(), 0, self::count_comments(), 0, $native_reason );
		$rows['menus'] = self::row( 'menus', self::count_menus(), self::count_menus(), 0, self::count_menus(), 0, $native_reason );
		$rows['media'] = self::row(
			'media attachments',
			self::count_posts( 'attachment' ),
			self::count_posts( 'attachment' ),
			0,
			self::count_posts( 'attachment' ),
			0,
			$native_reason . ' Featured-image IDs are re-linked on copied CPTs.'
		);

		$rows['faq'] = self::cpt_pair( 'faq', 'mes_faq' );
		$rows['works'] = self::cpt_pair( 'works', 'mes_portfolio' );
		$rows['prices'] = self::cpt_pair( 'price', 'mes_offer' );
		$rows['categories'] = array(
			'entity'   => 'categories',
			'source'   => self::count_terms( 'category' ),
			'target'   => self::count_posts( 'service' ),
			'migrated' => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'reason'   => 'Non-default category terms map to service CPT. Default category is skipped. Terms themselves remain.',
		);
		$rows['city_terms'] = array(
			'entity'   => 'city terms',
			'source'   => taxonomy_exists( 'city' ) ? self::count_terms( 'city' ) : 0,
			'target'   => self::count_posts( 'mes_city' ),
			'migrated' => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'reason'   => taxonomy_exists( 'city' ) ? 'city taxonomy → mes_city CPT. Terms remain.' : 'city taxonomy not registered; nothing to migrate.',
		);

		$opt_keys = array( 'sitename', 'phonenumber', 'whatsapp_number', 'company__mail', 'company__adress', 'company__map_code', 'site_color', 'facebook', 'twitter', 'telegram', 'youtube', 'linkedin', 'instagram', 'threads' );
		$opt_src  = 0;
		foreach ( $opt_keys as $key ) {
			$val = get_option( $key, null );
			if ( null !== $val && false !== $val && '' !== $val ) {
				++$opt_src;
			}
		}
		$rows['theme_options'] = self::row( 'theme options', $opt_src, $opt_src, 0, 0, 0, 'Mapped into mes_brand_settings / mes_contact_settings. API keys are never copied.' );
		$rows['contact_options'] = self::row( 'contact options', (int) (bool) get_option( 'company__mail' ) + (int) (bool) get_option( 'company__adress' ), 0, 0, 0, 0, 'company__mail → email; company__adress → address; company__map_code → map_embed.' );
		$rows['phone'] = self::row( 'phone', (int) (bool) get_option( 'phonenumber' ), 0, 0, 0, 0, 'phonenumber → mes_contact_settings.phones (deduped).' );
		$rows['whatsapp'] = self::row( 'whatsapp', (int) (bool) get_option( 'whatsapp_number' ), 0, 0, 0, 0, 'whatsapp_number → mes_contact_settings.whatsapps (deduped).' );
		$rows['schema'] = self::row( 'schema-related data', 0, 0, 0, 0, 0, 'No legacy schema CPT. Rank Math / MES schema is generated at runtime from migrated content.' );
		$rows['images'] = self::row( 'images', self::count_posts( 'attachment' ), self::count_posts( 'attachment' ), 0, self::count_posts( 'attachment' ), 0, 'Files stay in uploads. Thumbnails and _wp_attachment_metadata remain native.' );
		$rows['post_meta'] = self::row( 'post meta', self::count_postmeta(), self::count_postmeta(), 0, 0, 0, 'Known legacy keys are remapped onto copies. Other keys remain on the source post (source is not deleted).' );
		$rows['term_meta'] = self::row( 'term meta', self::count_termmeta(), self::count_termmeta(), 0, 0, 0, 'city/category icon copied when present. Other term meta remains on the source term.' );

		return $rows;
	}

	/**
	 * Execute migration.
	 *
	 * @return array<string, mixed>
	 */
	public static function run(): array {
		$before = self::inventory();
		$report = array(
			'backup'      => Backup::create(),
			'faq'         => 0,
			'works'       => 0,
			'price'       => 0,
			'cities'      => 0,
			'services'    => 0,
			'options'     => 0,
			'skipped'     => array(),
			'failed'      => array(),
			'inventory'   => $before,
			'comparison'  => array(),
		);

		$faq = self::copy_cpt(
			'faq',
			'mes_faq',
			array()
		);
		$report['faq']     = $faq['migrated'];
		$report['skipped'] = array_merge( $report['skipped'], $faq['skipped'] );
		$report['failed']  = array_merge( $report['failed'], $faq['failed'] );

		$works = self::copy_cpt(
			'works',
			'mes_portfolio',
			array(
				'client__name'   => '_mes_client',
				'services__rate' => '_mes_rating',
				'services__type' => '_mes_service_type',
				'works_gallery'  => '_mes_gallery',
			)
		);
		$report['works']   = $works['migrated'];
		$report['skipped'] = array_merge( $report['skipped'], $works['skipped'] );
		$report['failed']  = array_merge( $report['failed'], $works['failed'] );

		$price = self::copy_cpt(
			'price',
			'mes_offer',
			array(
				'price_text'    => '_mes_price',
				'offer'         => '_mes_discount',
				'btn_title'     => '_mes_btn_title',
				'price_icon'    => '_mes_icon',
				'services_text' => '_mes_services_text',
				'categories'    => '_mes_legacy_categories',
			)
		);
		$report['price']   = $price['migrated'];
		$report['skipped'] = array_merge( $report['skipped'], $price['skipped'] );
		$report['failed']  = array_merge( $report['failed'], $price['failed'] );

		$cities = self::terms_to_cpt( 'city', 'mes_city' );
		$report['cities']  = $cities['migrated'];
		$report['skipped'] = array_merge( $report['skipped'], $cities['skipped'] );
		$report['failed']  = array_merge( $report['failed'], $cities['failed'] );

		$services = self::categories_to_services();
		$report['services'] = $services['migrated'];
		$report['skipped']  = array_merge( $report['skipped'], $services['skipped'] );
		$report['failed']   = array_merge( $report['failed'], $services['failed'] );

		$opts = self::map_options();
		$report['options'] = $opts['mapped'];
		$report['skipped'] = array_merge( $report['skipped'], $opts['skipped'] );

		$after = self::inventory();
		$report['comparison'] = self::comparison( $before, $after, $report );
		update_option( 'mes_migration_state', $report );
		return $report;
	}

	/**
	 * Build ENTITY | SOURCE | TARGET | MIGRATED | SKIPPED | FAILED | REASON rows.
	 *
	 * @param array<string, mixed> $before Before inventory.
	 * @param array<string, mixed> $after  After inventory.
	 * @param array<string, mixed> $report Run report.
	 * @return array<int, array<string, mixed>>
	 */
	public static function comparison( array $before, array $after, array $report ): array {
		$fail_n = static function ( array $failed, string $needle ): int {
			$n = 0;
			foreach ( $failed as $row ) {
				if ( false !== strpos( (string) ( $row['entity'] ?? $row['reason'] ?? '' ), $needle ) ) {
					++$n;
				}
			}
			return $n;
		};

		$rows   = array();
		$rows[] = array(
			'entity'   => 'Posts',
			'source'   => (int) ( $before['posts']['source'] ?? 0 ),
			'target'   => (int) ( $after['posts']['target'] ?? 0 ),
			'migrated' => 0,
			'skipped'  => (int) ( $before['posts']['source'] ?? 0 ),
			'failed'   => 0,
			'reason'   => 'Native WordPress posts remain posts. Not converted.',
		);
		$rows[] = array(
			'entity'   => 'Pages',
			'source'   => (int) ( $before['pages']['source'] ?? 0 ),
			'target'   => (int) ( $after['pages']['target'] ?? 0 ),
			'migrated' => 0,
			'skipped'  => (int) ( $before['pages']['source'] ?? 0 ),
			'failed'   => 0,
			'reason'   => 'Native WordPress pages remain pages. Not converted.',
		);
		$rows[] = array(
			'entity'   => 'Categories',
			'source'   => (int) ( $before['categories']['source'] ?? 0 ),
			'target'   => (int) ( $after['categories']['target'] ?? 0 ),
			'migrated' => (int) $report['services'],
			'skipped'  => max( 0, (int) ( $before['categories']['source'] ?? 0 ) - (int) $report['services'] ),
			'failed'   => $fail_n( $report['failed'], 'category' ),
			'reason'   => 'Non-default categories → service CPT. Default Uncategorized skipped. Category terms remain.',
		);
		$rows[] = array(
			'entity'   => 'City terms',
			'source'   => (int) ( $before['city_terms']['source'] ?? 0 ),
			'target'   => (int) ( $after['city_terms']['target'] ?? 0 ),
			'migrated' => (int) $report['cities'],
			'skipped'  => max( 0, (int) ( $before['city_terms']['source'] ?? 0 ) - (int) $report['cities'] ),
			'failed'   => $fail_n( $report['failed'], 'city' ),
			'reason'   => 'city taxonomy → mes_city. Already-migrated slugs skipped. Terms remain.',
		);
		$rows[] = array(
			'entity'   => 'FAQ',
			'source'   => (int) ( $before['faq']['source'] ?? 0 ),
			'target'   => (int) ( $after['faq']['target'] ?? 0 ),
			'migrated' => (int) $report['faq'],
			'skipped'  => max( 0, (int) ( $before['faq']['source'] ?? 0 ) - (int) $report['faq'] ),
			'failed'   => $fail_n( $report['failed'], 'faq' ),
			'reason'   => 'faq → mes_faq. Source posts kept. Already-migrated skipped via _mes_migrated_to.',
		);
		$rows[] = array(
			'entity'   => 'Works',
			'source'   => (int) ( $before['works']['source'] ?? 0 ),
			'target'   => (int) ( $after['works']['target'] ?? 0 ),
			'migrated' => (int) $report['works'],
			'skipped'  => max( 0, (int) ( $before['works']['source'] ?? 0 ) - (int) $report['works'] ),
			'failed'   => $fail_n( $report['failed'], 'works' ),
			'reason'   => 'works → mes_portfolio with client/rating/type/gallery meta.',
		);
		$rows[] = array(
			'entity'   => 'Prices',
			'source'   => (int) ( $before['prices']['source'] ?? 0 ),
			'target'   => (int) ( $after['prices']['target'] ?? 0 ),
			'migrated' => (int) $report['price'],
			'skipped'  => max( 0, (int) ( $before['prices']['source'] ?? 0 ) - (int) $report['price'] ),
			'failed'   => $fail_n( $report['failed'], 'price' ),
			'reason'   => 'price → mes_offer with price/discount/icon meta.',
		);
		$rows[] = array(
			'entity'   => 'Comments',
			'source'   => (int) ( $before['comments']['source'] ?? 0 ),
			'target'   => (int) ( $after['comments']['target'] ?? 0 ),
			'migrated' => 0,
			'skipped'  => (int) ( $before['comments']['source'] ?? 0 ),
			'failed'   => 0,
			'reason'   => 'Native comments remain on their posts. Not converted.',
		);
		$rows[] = array(
			'entity'   => 'Menus',
			'source'   => (int) ( $before['menus']['source'] ?? 0 ),
			'target'   => (int) ( $after['menus']['target'] ?? 0 ),
			'migrated' => 0,
			'skipped'  => (int) ( $before['menus']['source'] ?? 0 ),
			'failed'   => 0,
			'reason'   => 'Native nav menus remain. Theme locations may need reassignment in WP Admin.',
		);
		$rows[] = array(
			'entity'   => 'Media attachments',
			'source'   => (int) ( $before['media']['source'] ?? 0 ),
			'target'   => (int) ( $after['media']['target'] ?? 0 ),
			'migrated' => 0,
			'skipped'  => (int) ( $before['media']['source'] ?? 0 ),
			'failed'   => 0,
			'reason'   => 'Attachments stay native (files, metadata, thumbnails). Featured images re-linked on copied CPTs.',
		);
		$rows[] = array(
			'entity'   => 'Post meta',
			'source'   => (int) ( $before['post_meta']['source'] ?? 0 ),
			'target'   => (int) ( $after['post_meta']['target'] ?? 0 ),
			'migrated' => (int) $report['faq'] + (int) $report['works'] + (int) $report['price'],
			'skipped'  => 0,
			'failed'   => 0,
			'reason'   => 'Mapped keys copied onto new posts. Unmapped keys remain on source (not deleted).',
		);
		$rows[] = array(
			'entity'   => 'Term meta',
			'source'   => (int) ( $before['term_meta']['source'] ?? 0 ),
			'target'   => (int) ( $after['term_meta']['target'] ?? 0 ),
			'migrated' => (int) $report['cities'] + (int) $report['services'],
			'skipped'  => 0,
			'failed'   => 0,
			'reason'   => 'icon → _mes_icon when present. Other term meta remains on the term.',
		);
		$rows[] = array(
			'entity'   => 'Theme options',
			'source'   => (int) ( $before['theme_options']['source'] ?? 0 ),
			'target'   => (int) ( $before['theme_options']['source'] ?? 0 ),
			'migrated' => (int) $report['options'],
			'skipped'  => 0,
			'failed'   => 0,
			'reason'   => 'sitename/color/social mapped. Third-party API keys never copied.',
		);
		$rows[] = array(
			'entity'   => 'Contact options',
			'source'   => (int) ( $before['contact_options']['source'] ?? 0 ),
			'target'   => (int) ( $before['contact_options']['source'] ?? 0 ),
			'migrated' => (int) $report['options'],
			'skipped'  => 0,
			'failed'   => 0,
			'reason'   => 'Mail, address, map embed mapped into mes_contact_settings.',
		);
		$rows[] = array(
			'entity'   => 'Phone',
			'source'   => (int) ( $before['phone']['source'] ?? 0 ),
			'target'   => 1,
			'migrated' => (int) ( $before['phone']['source'] ?? 0 ),
			'skipped'  => 0,
			'failed'   => 0,
			'reason'   => 'phonenumber → phones[]. Duplicate numbers are not appended.',
		);
		$rows[] = array(
			'entity'   => 'WhatsApp',
			'source'   => (int) ( $before['whatsapp']['source'] ?? 0 ),
			'target'   => 1,
			'migrated' => (int) ( $before['whatsapp']['source'] ?? 0 ),
			'skipped'  => 0,
			'failed'   => 0,
			'reason'   => 'whatsapp_number → whatsapps[]. Duplicate numbers are not appended.',
		);
		$rows[] = array(
			'entity'   => 'Schema-related data',
			'source'   => 0,
			'target'   => 0,
			'migrated' => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'reason'   => 'No legacy schema store. Runtime JSON-LD is generated from migrated content + Rank Math.',
		);
		$rows[] = array(
			'entity'   => 'Images',
			'source'   => (int) ( $before['images']['source'] ?? 0 ),
			'target'   => (int) ( $after['images']['target'] ?? 0 ),
			'migrated' => 0,
			'skipped'  => (int) ( $before['images']['source'] ?? 0 ),
			'failed'   => 0,
			'reason'   => 'Original files and thumbnails stay in wp-content/uploads. Not duplicated.',
		);
		return $rows;
	}

	/**
	 * Copy a public CPT.
	 *
	 * @param string                $from From type.
	 * @param string                $to To type.
	 * @param array<string, string> $meta Meta map.
	 * @return array{migrated:int,skipped:array<int,array<string,mixed>>,failed:array<int,array<string,mixed>>}
	 */
	private static function copy_cpt( string $from, string $to, array $meta = array() ): array {
		$out = array( 'migrated' => 0, 'skipped' => array(), 'failed' => array() );
		if ( ! post_type_exists( $from ) ) {
			$out['skipped'][] = array( 'entity' => $from, 'reason' => 'Source post type not registered.' );
			return $out;
		}
		$posts = get_posts(
			array(
				'post_type'      => $from,
				'posts_per_page' => 500,
				'post_status'    => 'any',
			)
		);
		foreach ( $posts as $post ) {
			if ( get_post_meta( $post->ID, '_mes_migrated_to', true ) ) {
				$out['skipped'][] = array( 'entity' => $from, 'id' => $post->ID, 'reason' => 'Already migrated (_mes_migrated_to).' );
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
				$out['failed'][] = array( 'entity' => $from, 'id' => $post->ID, 'reason' => $new->get_error_message() );
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
			++$out['migrated'];
		}
		return $out;
	}

	/**
	 * Convert taxonomy terms to CPT.
	 *
	 * @param string $tax Taxonomy.
	 * @param string $type CPT.
	 * @return array{migrated:int,skipped:array<int,array<string,mixed>>,failed:array<int,array<string,mixed>>}
	 */
	private static function terms_to_cpt( string $tax, string $type ): array {
		$out = array( 'migrated' => 0, 'skipped' => array(), 'failed' => array() );
		if ( ! taxonomy_exists( $tax ) ) {
			$out['skipped'][] = array( 'entity' => $tax, 'reason' => 'Taxonomy not registered.' );
			return $out;
		}
		$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			$out['failed'][] = array( 'entity' => $tax, 'reason' => $terms->get_error_message() );
			return $out;
		}
		foreach ( $terms as $term ) {
			$existing = get_page_by_path( $term->slug, OBJECT, $type );
			if ( $existing ) {
				update_term_meta( $term->term_id, '_mes_migrated_to', $existing->ID );
				$icon = get_term_meta( $term->term_id, 'icon', true );
				if ( $icon && ! get_post_meta( $existing->ID, '_mes_icon', true ) ) {
					update_post_meta( $existing->ID, '_mes_icon', $icon );
				}
				$out['skipped'][] = array( 'entity' => $tax, 'id' => $term->term_id, 'reason' => 'Slug already exists on target CPT.' );
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
			if ( is_wp_error( $id ) ) {
				$out['failed'][] = array( 'entity' => $tax, 'id' => $term->term_id, 'reason' => $id->get_error_message() );
				continue;
			}
			update_term_meta( $term->term_id, '_mes_migrated_to', $id );
			update_post_meta( $id, '_mes_language', 'ar' );
			$icon = get_term_meta( $term->term_id, 'icon', true );
			if ( $icon ) {
				update_post_meta( $id, '_mes_icon', $icon );
			}
			++$out['migrated'];
		}
		return $out;
	}

	/**
	 * Map service-like categories to service CPT.
	 *
	 * @return array{migrated:int,skipped:array<int,array<string,mixed>>,failed:array<int,array<string,mixed>>}
	 */
	private static function categories_to_services(): array {
		$out = array( 'migrated' => 0, 'skipped' => array(), 'failed' => array() );
		$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			$out['failed'][] = array( 'entity' => 'category', 'reason' => $terms->get_error_message() );
			return $out;
		}
		foreach ( $terms as $term ) {
			if ( (int) $term->term_id === (int) get_option( 'default_category' ) ) {
				$out['skipped'][] = array( 'entity' => 'category', 'id' => $term->term_id, 'reason' => 'Default Uncategorized category is not a service.' );
				continue;
			}
			$existing = get_page_by_path( $term->slug, OBJECT, 'service' );
			if ( $existing ) {
				update_term_meta( $term->term_id, '_mes_migrated_to', $existing->ID );
				$out['skipped'][] = array( 'entity' => 'category', 'id' => $term->term_id, 'reason' => 'Service slug already exists.' );
				continue;
			}
			$id = wp_insert_post(
				array(
					'post_type'    => 'service',
					'post_status'  => 'publish',
					'post_title'   => $term->name,
					'post_content' => $term->description,
					'post_excerpt' => wp_trim_words( wp_strip_all_tags( $term->description ), 24 ),
					'post_name'    => $term->slug,
				)
			);
			if ( is_wp_error( $id ) ) {
				$out['failed'][] = array( 'entity' => 'category', 'id' => $term->term_id, 'reason' => $id->get_error_message() );
				continue;
			}
			$icon = get_term_meta( $term->term_id, 'icon', true );
			if ( $icon ) {
				update_post_meta( $id, '_mes_icon', $icon );
			}
			update_term_meta( $term->term_id, '_mes_migrated_to', $id );
			update_post_meta( $id, '_mes_language', 'ar' );
			++$out['migrated'];
		}
		return $out;
	}

	/**
	 * Map known legacy options into namespaced settings. Never copies API keys.
	 *
	 * @return array{mapped:int,skipped:array<int,array<string,mixed>>}
	 */
	private static function map_options(): array {
		$brand   = get_option( 'mes_brand_settings', array() );
		$contact = get_option( 'mes_contact_settings', array() );
		$mapped  = 0;
		$skipped = array();

		$sitename = get_option( 'sitename' );
		if ( $sitename && empty( $brand['name'] ) ) {
			$brand['name'] = sanitize_text_field( (string) $sitename );
			++$mapped;
		} elseif ( $sitename ) {
			$skipped[] = array( 'entity' => 'sitename', 'reason' => 'mes_brand_settings.name already set; legacy sitename left in source option.' );
		}

		$color = get_option( 'site_color' );
		if ( $color && empty( $brand['primary_color'] ) ) {
			$hex = sanitize_hex_color( (string) $color );
			if ( $hex ) {
				$brand['primary_color'] = $hex;
				++$mapped;
			}
		}

		$phone = get_option( 'phonenumber' );
		if ( $phone ) {
			if ( self::append_unique_number( $contact, 'phones', (string) $phone ) ) {
				++$mapped;
			} else {
				$skipped[] = array( 'entity' => 'phonenumber', 'reason' => 'Number already present in mes_contact_settings.phones.' );
			}
		}
		$wa = get_option( 'whatsapp_number' );
		if ( $wa ) {
			if ( self::append_unique_number( $contact, 'whatsapps', (string) $wa ) ) {
				++$mapped;
			} else {
				$skipped[] = array( 'entity' => 'whatsapp_number', 'reason' => 'Number already present in mes_contact_settings.whatsapps.' );
			}
		}
		foreach ( array( 'facebook', 'instagram', 'youtube', 'linkedin', 'telegram', 'twitter', 'threads' ) as $net ) {
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
		$map = get_option( 'company__map_code' );
		if ( $map && empty( $contact['map_embed'] ) ) {
			$contact['map_embed'] = wp_kses_post( (string) $map );
			++$mapped;
		}

		$secret_keys = array( 'scrapestack_key', 'api_key', 'openai_key', 'gemini_key' );
		foreach ( $secret_keys as $secret ) {
			if ( get_option( $secret ) ) {
				$skipped[] = array( 'entity' => $secret, 'reason' => 'Secret/API key intentionally not copied.' );
			}
		}

		update_option( 'mes_brand_settings', $brand );
		update_option( 'mes_contact_settings', $contact );
		return array( 'mapped' => $mapped, 'skipped' => $skipped );
	}

	/**
	 * Append a phone/WhatsApp number only if it is not already stored.
	 *
	 * @param array<string, mixed> $contact Contact settings (by ref).
	 * @param string               $key     phones|whatsapps.
	 * @param string               $number  Raw number.
	 */
	private static function append_unique_number( array &$contact, string $key, string $number ): bool {
		$number = sanitize_text_field( $number );
		$digits = preg_replace( '/\D+/', '', $number );
		$list   = is_array( $contact[ $key ] ?? null ) ? $contact[ $key ] : array();
		foreach ( $list as $item ) {
			$existing = preg_replace( '/\D+/', '', (string) ( $item['number'] ?? '' ) );
			if ( $existing && $existing === $digits ) {
				return false;
			}
		}
		$list[]           = array(
			'number'  => $number,
			'primary' => empty( $list ),
		);
		$contact[ $key ]  = $list;
		return true;
	}

	/**
	 * @param string $entity Entity.
	 * @param int    $source Source count.
	 * @param int    $target Target count.
	 * @param int    $migrated Migrated.
	 * @param int    $skipped Skipped.
	 * @param int    $failed Failed.
	 * @param string $reason Reason.
	 * @return array<string, mixed>
	 */
	private static function row( string $entity, int $source, int $target, int $migrated, int $skipped, int $failed, string $reason ): array {
		return compact( 'entity', 'source', 'target', 'migrated', 'skipped', 'failed', 'reason' );
	}

	/**
	 * @param string $from Source CPT.
	 * @param string $to Target CPT.
	 * @return array<string, mixed>
	 */
	private static function cpt_pair( string $from, string $to ): array {
		$src = post_type_exists( $from ) ? self::count_posts( $from ) : 0;
		$tgt = post_type_exists( $to ) ? self::count_posts( $to ) : 0;
		return self::row( $from, $src, $tgt, 0, 0, 0, $from . ' → ' . $to );
	}

	private static function count_posts( string $type ): int {
		if ( ! post_type_exists( $type ) && 'attachment' !== $type ) {
			return 0;
		}
		$counts = wp_count_posts( $type );
		if ( ! is_object( $counts ) ) {
			return 0;
		}
		$total = 0;
		foreach ( get_object_vars( $counts ) as $n ) {
			$total += (int) $n;
		}
		return $total;
	}

	private static function count_terms( string $tax ): int {
		if ( ! taxonomy_exists( $tax ) ) {
			return 0;
		}
		$n = wp_count_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
		return is_wp_error( $n ) ? 0 : (int) $n;
	}

	private static function count_comments(): int {
		$c = wp_count_comments();
		return (int) ( $c->total_comments ?? 0 );
	}

	private static function count_menus(): int {
		$menus = wp_get_nav_menus();
		return is_array( $menus ) ? count( $menus ) : 0;
	}

	private static function count_postmeta(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta}" );
	}

	private static function count_termmeta(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->termmeta}" );
	}
}
