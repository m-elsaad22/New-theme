<?php
/**
 * Post types and taxonomies.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registrar {
	/**
	 * Hook registrations.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_meta' ), 12 );
	}

	/**
	 * Register CPTs and taxonomies.
	 */
	public static function register(): void {
		$public = array(
			'public'             => true,
			'show_in_rest'       => true,
			'has_archive'        => true,
			'show_in_menu'       => false,
			'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
		);

		register_post_type(
			'service',
			array_merge(
				$public,
				array(
					'labels'       => self::labels( __( 'Services', 'mahmoud-elsaad-core' ), __( 'Service', 'mahmoud-elsaad-core' ) ),
					'rewrite'      => array( 'slug' => 'services', 'with_front' => false ),
					'menu_icon'    => 'dashicons-admin-tools',
					'has_archive'  => 'services',
				)
			)
		);

		register_post_type(
			'mes_city',
			array_merge(
				$public,
				array(
					'labels'      => self::labels( __( 'Cities', 'mahmoud-elsaad-core' ), __( 'City', 'mahmoud-elsaad-core' ) ),
					'rewrite'     => array( 'slug' => 'cities', 'with_front' => false ),
					'has_archive' => 'cities',
				)
			)
		);

		register_post_type(
			'mes_country',
			array_merge(
				$public,
				array(
					'labels'      => self::labels( __( 'Countries', 'mahmoud-elsaad-core' ), __( 'Country', 'mahmoud-elsaad-core' ) ),
					'rewrite'     => array( 'slug' => 'countries', 'with_front' => false ),
					'has_archive' => false,
					'public'      => true,
				)
			)
		);

		register_post_type(
			'mes_offer',
			array_merge(
				$public,
				array(
					'labels'      => self::labels( __( 'Offers', 'mahmoud-elsaad-core' ), __( 'Offer', 'mahmoud-elsaad-core' ) ),
					'rewrite'     => array( 'slug' => 'offers', 'with_front' => false ),
					'has_archive' => 'offers',
				)
			)
		);

		register_post_type(
			'mes_review',
			array_merge(
				$public,
				array(
					'labels'      => self::labels( __( 'Reviews', 'mahmoud-elsaad-core' ), __( 'Review', 'mahmoud-elsaad-core' ) ),
					'rewrite'     => array( 'slug' => 'reviews', 'with_front' => false ),
					'has_archive' => 'reviews',
				)
			)
		);

		register_post_type(
			'mes_portfolio',
			array_merge(
				$public,
				array(
					'labels'      => self::labels( __( 'Portfolio', 'mahmoud-elsaad-core' ), __( 'Project', 'mahmoud-elsaad-core' ) ),
					'rewrite'     => array( 'slug' => 'portfolio', 'with_front' => false ),
					'has_archive' => 'portfolio',
					'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
				)
			)
		);

		register_post_type(
			'mes_team',
			array_merge(
				$public,
				array(
					'labels'      => self::labels( __( 'Team', 'mahmoud-elsaad-core' ), __( 'Member', 'mahmoud-elsaad-core' ) ),
					'rewrite'     => array( 'slug' => 'team', 'with_front' => false ),
					'has_archive' => 'team',
					'supports'    => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'custom-fields' ),
				)
			)
		);

		register_post_type(
			'mes_partner',
			array_merge(
				$public,
				array(
					'labels'      => self::labels( __( 'Partners', 'mahmoud-elsaad-core' ), __( 'Partner', 'mahmoud-elsaad-core' ) ),
					'has_archive' => false,
					'public'      => true,
					'supports'    => array( 'title', 'thumbnail', 'page-attributes', 'custom-fields', 'excerpt' ),
				)
			)
		);

		register_post_type(
			'mes_faq',
			array_merge(
				$public,
				array(
					'labels'      => self::labels( __( 'FAQs', 'mahmoud-elsaad-core' ), __( 'FAQ', 'mahmoud-elsaad-core' ) ),
					'rewrite'     => array( 'slug' => 'faq', 'with_front' => false ),
					'has_archive' => 'faq',
					'supports'    => array( 'title', 'editor', 'page-attributes', 'custom-fields' ),
				)
			)
		);

		register_post_type(
			'mes_lead',
			array(
				'labels'            => self::labels( __( 'Leads', 'mahmoud-elsaad-core' ), __( 'Lead', 'mahmoud-elsaad-core' ) ),
				'public'            => false,
				'show_ui'           => true,
				'show_in_menu'      => false,
				'show_in_rest'      => false,
				'capability_type'   => 'post',
				'map_meta_cap'      => true,
				'supports'          => array( 'title', 'editor', 'custom-fields' ),
			)
		);

		register_post_type(
			'mes_form',
			array(
				'labels'          => self::labels( __( 'Forms', 'mahmoud-elsaad-core' ), __( 'Form', 'mahmoud-elsaad-core' ) ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'show_in_rest'    => false,
				'supports'        => array( 'title', 'revisions', 'custom-fields' ),
			)
		);

		register_taxonomy(
			'mes_service_cat',
			array( 'service' ),
			array(
				'labels'            => self::labels( __( 'Service categories', 'mahmoud-elsaad-core' ), __( 'Service category', 'mahmoud-elsaad-core' ) ),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'service-category', 'with_front' => false ),
			)
		);

		register_taxonomy(
			'mes_faq_topic',
			array( 'mes_faq' ),
			array(
				'labels'            => self::labels( __( 'FAQ topics', 'mahmoud-elsaad-core' ), __( 'FAQ topic', 'mahmoud-elsaad-core' ) ),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'faq-topic', 'with_front' => false ),
			)
		);

		register_taxonomy(
			'mes_location_type',
			array( 'mes_city' ),
			array(
				'labels'       => self::labels( __( 'Location types', 'mahmoud-elsaad-core' ), __( 'Location type', 'mahmoud-elsaad-core' ) ),
				'hierarchical' => true,
				'public'       => true,
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Registered meta used by translation and entities.
	 */
	public static function register_meta(): void {
		$types = array( 'service', 'mes_city', 'mes_country', 'mes_offer', 'mes_review', 'mes_portfolio', 'mes_team', 'mes_partner', 'mes_faq', 'post', 'page' );
		foreach ( $types as $type ) {
			register_post_meta(
				$type,
				'_mes_language',
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => 'ar',
				)
			);
			register_post_meta(
				$type,
				'_mes_translation_group',
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
		}

		$fields = array(
			'service'       => array( 'icon', 'phone', 'whatsapp', 'video', 'price', 'benefits', 'steps', 'gallery', 'cta_label', 'cta_url' ),
			'mes_city'      => array( 'country_code', 'emirate', 'lat', 'lng', 'phone', 'whatsapp', 'response_time' ),
			'mes_country'   => array( 'iso', 'phone_prefix', 'phone', 'whatsapp', 'domain', 'flag' ),
			'mes_offer'     => array( 'price', 'old_price', 'discount', 'start_date', 'end_date', 'terms', 'cta_label' ),
			'mes_review'    => array( 'rating', 'customer_name', 'source', 'verified', 'featured', 'service_id', 'city_id' ),
			'mes_portfolio' => array( 'client', 'service_id', 'city_id', 'rating', 'results', 'video', 'before_id', 'after_id', 'featured' ),
			'mes_team'      => array( 'position', 'skills', 'featured', 'social' ),
			'mes_partner'   => array( 'url', 'featured', 'sort_order' ),
			'mes_faq'       => array( 'relates_to', 'relates_id', 'featured' ),
			'mes_lead'      => array( 'form_id', 'service_id', 'city_id', 'phone', 'email', 'source_url', 'channel' ),
			'mes_form'      => array( 'form_type', 'fields', 'notify_email', 'success_message' ),
		);

		foreach ( $fields as $type => $keys ) {
			foreach ( $keys as $key ) {
				register_post_meta(
					$type,
					'_mes_' . $key,
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => current_user_can( 'edit_posts' ),
						'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
						'sanitize_callback' => 'sanitize_textarea_field',
					)
				);
			}
		}
	}

	/**
	 * Build labels.
	 *
	 * @param string $plural Plural.
	 * @param string $singular Singular.
	 * @return array<string, string>
	 */
	private static function labels( string $plural, string $singular ): array {
		return array(
			'name'          => $plural,
			'singular_name' => $singular,
			'add_new_item'  => sprintf( /* translators: %s singular name */ __( 'Add %s', 'mahmoud-elsaad-core' ), $singular ),
			'edit_item'     => sprintf( /* translators: %s singular name */ __( 'Edit %s', 'mahmoud-elsaad-core' ), $singular ),
			'view_item'     => sprintf( /* translators: %s singular name */ __( 'View %s', 'mahmoud-elsaad-core' ), $singular ),
			'search_items'  => sprintf( /* translators: %s plural name */ __( 'Search %s', 'mahmoud-elsaad-core' ), $plural ),
			'not_found'     => __( 'Nothing found.', 'mahmoud-elsaad-core' ),
			'menu_name'     => $plural,
		);
	}
}
