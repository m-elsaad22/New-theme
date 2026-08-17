<?php
/**
 * Entity field metaboxes.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Admin;

use MahmoudElsaad\Core\Support\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Metaboxes {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * Register boxes.
	 */
	public static function boxes(): void {
		$types = array(
			'service'       => __( 'Service details', 'mahmoud-elsaad-core' ),
			'mes_city'      => __( 'City details', 'mahmoud-elsaad-core' ),
			'mes_country'   => __( 'Country details', 'mahmoud-elsaad-core' ),
			'mes_offer'     => __( 'Offer details', 'mahmoud-elsaad-core' ),
			'mes_review'    => __( 'Review details', 'mahmoud-elsaad-core' ),
			'mes_portfolio' => __( 'Project details', 'mahmoud-elsaad-core' ),
			'mes_team'      => __( 'Member details', 'mahmoud-elsaad-core' ),
			'mes_partner'   => __( 'Partner details', 'mahmoud-elsaad-core' ),
			'mes_faq'       => __( 'FAQ details', 'mahmoud-elsaad-core' ),
			'mes_form'      => __( 'Form schema', 'mahmoud-elsaad-core' ),
			'post'          => __( 'Language', 'mahmoud-elsaad-core' ),
			'page'          => __( 'Language', 'mahmoud-elsaad-core' ),
		);
		foreach ( $types as $type => $label ) {
			add_meta_box( 'mes-entity', $label, array( __CLASS__, 'render' ), $type, 'normal', 'high' );
		}
	}

	/**
	 * Fields per type.
	 *
	 * @param string $type Type.
	 * @return array<string, string>
	 */
	private static function fields( string $type ): array {
		$map = array(
			'service'       => array( 'icon' => 'Icon class', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'price' => 'Price', 'video' => 'Video', 'cta_label' => 'CTA label', 'benefits' => 'Benefits (JSON or lines)', 'steps' => 'Steps (JSON or lines)' ),
			'mes_city'      => array( 'country_code' => 'Country ISO', 'emirate' => 'Emirate', 'lat' => 'Latitude', 'lng' => 'Longitude', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'response_time' => 'Response time' ),
			'mes_country'   => array( 'iso' => 'ISO', 'phone_prefix' => 'Phone prefix', 'phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'domain' => 'Domain', 'flag' => 'Flag' ),
			'mes_offer'     => array( 'price' => 'Price', 'old_price' => 'Old price', 'discount' => 'Discount', 'start_date' => 'Start', 'end_date' => 'End', 'terms' => 'Terms', 'cta_label' => 'CTA' ),
			'mes_review'    => array( 'rating' => 'Rating', 'customer_name' => 'Customer', 'source' => 'Source', 'verified' => 'Verified', 'featured' => 'Featured', 'service_id' => 'Service ID', 'city_id' => 'City ID' ),
			'mes_portfolio' => array( 'client' => 'Client', 'service_id' => 'Service ID', 'city_id' => 'City ID', 'rating' => 'Rating', 'results' => 'Results', 'video' => 'Video', 'featured' => 'Featured' ),
			'mes_team'      => array( 'position' => 'Position', 'skills' => 'Skills', 'featured' => 'Featured' ),
			'mes_partner'   => array( 'url' => 'URL', 'featured' => 'Featured', 'sort_order' => 'Sort' ),
			'mes_faq'       => array( 'relates_to' => 'Relates to', 'relates_id' => 'Related ID', 'featured' => 'Featured' ),
			'mes_form'      => array( 'form_type' => 'Type', 'fields' => 'Fields JSON', 'notify_email' => 'Notify email', 'success_message' => 'Success message' ),
		);
		$base = array(
			'language'          => 'Language (ar/en)',
			'translation_group' => 'Translation group',
		);
		return array_merge( $base, $map[ $type ] ?? array() );
	}

	/**
	 * Render box.
	 *
	 * @param \WP_Post $post Post.
	 */
	public static function render( \WP_Post $post ): void {
		wp_nonce_field( 'mes_save_meta_' . $post->ID, 'mes_meta_nonce' );
		echo '<div class="mes-meta-grid" style="display:grid;gap:12px">';
		foreach ( self::fields( $post->post_type ) as $key => $label ) {
			$meta = (string) get_post_meta( $post->ID, '_mes_' . $key, true );
			$area = in_array( $key, array( 'benefits', 'steps', 'fields', 'terms', 'skills', 'success_message' ), true );
			echo '<p><label><strong>' . esc_html( $label ) . '</strong><br />';
			if ( $area ) {
				echo '<textarea name="mes_meta[' . esc_attr( $key ) . ']" rows="4" style="width:100%">' . esc_textarea( $meta ) . '</textarea>';
			} else {
				echo '<input type="text" name="mes_meta[' . esc_attr( $key ) . ']" value="' . esc_attr( $meta ) . '" style="width:100%" />';
			}
			echo '</label></p>';
		}
		echo '</div>';
	}

	/**
	 * Save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post.
	 */
	public static function save( int $post_id, \WP_Post $post ): void {
		if ( ! Capabilities::can_manage() && ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['mes_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mes_meta_nonce'] ) ), 'mes_save_meta_' . $post_id ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$fields = isset( $_POST['mes_meta'] ) && is_array( $_POST['mes_meta'] ) ? wp_unslash( $_POST['mes_meta'] ) : array();
		foreach ( $fields as $key => $value ) {
			$key = sanitize_key( (string) $key );
			update_post_meta( $post_id, '_mes_' . $key, sanitize_textarea_field( (string) $value ) );
		}
	}
}
