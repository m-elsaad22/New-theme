<?php
/**
 * Form engine and lead capture.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Engine {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'maybe_seed_forms' ), 30 );
		add_action( 'admin_post_nopriv_mes_submit_form', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_mes_submit_form', array( __CLASS__, 'handle' ) );
		add_shortcode( 'mes_form', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Seed default forms once.
	 */
	public static function maybe_seed_forms(): void {
		if ( get_option( 'mes_default_forms_seeded' ) ) {
			return;
		}
		$defs = array(
			'contact'  => __( 'Contact form', 'mahmoud-elsaad-core' ),
			'booking'  => __( 'Booking form', 'mahmoud-elsaad-core' ),
			'service'  => __( 'Service request', 'mahmoud-elsaad-core' ),
			'quote'    => __( 'Quote request', 'mahmoud-elsaad-core' ),
			'callback' => __( 'Callback request', 'mahmoud-elsaad-core' ),
		);
		foreach ( $defs as $type => $title ) {
			$id = wp_insert_post(
				array(
					'post_type'   => 'mes_form',
					'post_status' => 'publish',
					'post_title'  => $title,
				)
			);
			if ( $id && ! is_wp_error( $id ) ) {
				update_post_meta( $id, '_mes_form_type', $type );
				update_post_meta( $id, '_mes_fields', wp_json_encode( self::default_fields( $type ) ) );
				update_post_meta( $id, '_mes_success_message', __( 'Your request was received. We will contact you shortly.', 'mahmoud-elsaad-core' ) );
			}
		}
		update_option( 'mes_default_forms_seeded', 1 );
	}

	/**
	 * Default field sets.
	 *
	 * @param string $type Form type.
	 * @return array<int, array<string, mixed>>
	 */
	public static function default_fields( string $type ): array {
		$fields = array(
			array( 'id' => 'name', 'type' => 'text', 'label' => __( 'Full name', 'mahmoud-elsaad-core' ), 'required' => true ),
			array( 'id' => 'phone', 'type' => 'phone', 'label' => __( 'Phone', 'mahmoud-elsaad-core' ), 'required' => true ),
			array( 'id' => 'email', 'type' => 'email', 'label' => __( 'Email', 'mahmoud-elsaad-core' ), 'required' => false ),
			array( 'id' => 'service', 'type' => 'service', 'label' => __( 'Service', 'mahmoud-elsaad-core' ), 'required' => true ),
			array( 'id' => 'city', 'type' => 'city', 'label' => __( 'City', 'mahmoud-elsaad-core' ), 'required' => true ),
			array( 'id' => 'message', 'type' => 'textarea', 'label' => __( 'Details', 'mahmoud-elsaad-core' ), 'required' => false ),
		);
		if ( 'booking' === $type ) {
			$fields[] = array( 'id' => 'date', 'type' => 'date', 'label' => __( 'Preferred date', 'mahmoud-elsaad-core' ), 'required' => false );
			$fields[] = array( 'id' => 'time', 'type' => 'time', 'label' => __( 'Preferred time', 'mahmoud-elsaad-core' ), 'required' => false );
			$fields[] = array( 'id' => 'property', 'type' => 'select', 'label' => __( 'Property type', 'mahmoud-elsaad-core' ), 'options' => array( 'villa', 'apartment', 'office' ) );
		}
		if ( 'contact' === $type ) {
			unset( $fields[3] );
		}
		return array_values( $fields );
	}

	/**
	 * Render a form by type.
	 *
	 * @param string               $type Type.
	 * @param array<string, mixed> $args Args.
	 */
	public static function render( string $type, array $args = array() ): string {
		$form = self::get_form( $type );
		if ( ! $form ) {
			return '';
		}
		$fields  = json_decode( (string) get_post_meta( $form->ID, '_mes_fields', true ), true );
		$fields  = is_array( $fields ) ? $fields : self::default_fields( $type );
		$success = isset( $_GET['mes_sent'] ) && (string) $_GET['mes_sent'] === (string) $form->ID;

		ob_start();
		echo '<form class="form-card mes-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" novalidate>';
		echo '<input type="hidden" name="action" value="mes_submit_form" />';
		echo '<input type="hidden" name="mes_form_id" value="' . esc_attr( (string) $form->ID ) . '" />';
		echo '<input type="hidden" name="mes_source" value="' . esc_attr( (string) ( $args['source'] ?? get_permalink() ) ) . '" />';
		wp_nonce_field( 'mes_submit_form_' . $form->ID, 'mes_form_nonce' );
		echo '<p class="mes-hp" style="position:absolute;left:-9999px;"><label>' . esc_html__( 'Leave empty', 'mahmoud-elsaad-core' ) . ' <input type="text" name="mes_hp" tabindex="-1" autocomplete="off" /></label></p>';
		echo '<div class="form-grid">';
		foreach ( $fields as $field ) {
			echo self::field_html( $field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
		$submit = $args['submit'] ?? __( 'Send request', 'mahmoud-elsaad-core' );
		echo '<button class="btn btn-call" type="submit">' . esc_html( $submit ) . '</button>';
		if ( $success ) {
			echo '<div class="mes-form-success" id="bkSent"><p>' . esc_html( (string) get_post_meta( $form->ID, '_mes_success_message', true ) ) . '</p></div>';
		}
		echo '</form>';
		return (string) ob_get_clean();
	}

	/**
	 * Field markup.
	 *
	 * @param array<string, mixed> $field Field.
	 */
	private static function field_html( array $field ): string {
		$id    = sanitize_key( $field['id'] ?? 'field' );
		$label = (string) ( $field['label'] ?? $id );
		$type  = (string) ( $field['type'] ?? 'text' );
		$req   = ! empty( $field['required'] ) ? ' required' : '';
		$name  = 'mes_field[' . $id . ']';
		$html  = '<label class="fld"><span>' . esc_html( $label ) . '</span>';

		if ( in_array( $type, array( 'service', 'city' ), true ) ) {
			$ptype = 'service' === $type ? 'service' : 'mes_city';
			$items = get_posts(
				array(
					'post_type'      => $ptype,
					'posts_per_page' => 100,
					'post_status'    => 'publish',
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);
			$html .= '<select class="sel" name="' . esc_attr( $name ) . '"' . $req . '><option value="">' . esc_html__( 'Select', 'mahmoud-elsaad-core' ) . '</option>';
			foreach ( $items as $item ) {
				$html .= '<option value="' . esc_attr( (string) $item->ID ) . '">' . esc_html( get_the_title( $item ) ) . '</option>';
			}
			$html .= '</select>';
		} elseif ( 'textarea' === $type ) {
			$html .= '<textarea name="' . esc_attr( $name ) . '" rows="4"' . $req . '></textarea>';
		} elseif ( 'select' === $type ) {
			$html .= '<select class="sel" name="' . esc_attr( $name ) . '"' . $req . '>';
			foreach ( (array) ( $field['options'] ?? array() ) as $opt ) {
				$html .= '<option value="' . esc_attr( (string) $opt ) . '">' . esc_html( (string) $opt ) . '</option>';
			}
			$html .= '</select>';
		} else {
			$input = in_array( $type, array( 'email', 'number', 'date', 'time', 'file', 'tel' ), true ) ? $type : 'text';
			if ( 'phone' === $type ) {
				$input = 'tel';
			}
			$html .= '<input type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '"' . $req . ' />';
		}
		$html .= '</label>';
		return $html;
	}

	/**
	 * Handle submission.
	 */
	public static function handle(): void {
		$form_id = absint( $_POST['mes_form_id'] ?? 0 );
		if ( ! $form_id || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mes_form_nonce'] ?? '' ) ), 'mes_submit_form_' . $form_id ) ) {
			wp_die( esc_html__( 'Invalid form submission.', 'mahmoud-elsaad-core' ), 400 );
		}
		if ( ! empty( $_POST['mes_hp'] ) ) {
			wp_safe_redirect( wp_get_referer() ?: home_url( '/' ) );
			exit;
		}

		$fields = isset( $_POST['mes_field'] ) && is_array( $_POST['mes_field'] ) ? wp_unslash( $_POST['mes_field'] ) : array();
		$clean  = array();
		foreach ( $fields as $key => $value ) {
			$clean[ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value );
		}

		$name    = $clean['name'] ?? __( 'Lead', 'mahmoud-elsaad-core' );
		$lead_id = wp_insert_post(
			array(
				'post_type'   => 'mes_lead',
				'post_status' => 'private',
				'post_title'  => $name . ' — ' . wp_date( 'Y-m-d H:i' ),
				'post_content'=> wp_json_encode( $clean ),
			),
			true
		);

		if ( is_wp_error( $lead_id ) ) {
			wp_die( esc_html( $lead_id->get_error_message() ) );
		}

		update_post_meta( $lead_id, '_mes_form_id', $form_id );
		update_post_meta( $lead_id, '_mes_phone', $clean['phone'] ?? '' );
		update_post_meta( $lead_id, '_mes_email', sanitize_email( $clean['email'] ?? '' ) );
		update_post_meta( $lead_id, '_mes_service_id', absint( $clean['service'] ?? 0 ) );
		update_post_meta( $lead_id, '_mes_city_id', absint( $clean['city'] ?? 0 ) );
		update_post_meta( $lead_id, '_mes_source_url', esc_url_raw( wp_unslash( $_POST['mes_source'] ?? '' ) ) );
		update_post_meta( $lead_id, '_mes_channel', 'form' );

		self::notify( $lead_id, $clean );

		$redirect = add_query_arg( 'mes_sent', $form_id, wp_get_referer() ?: home_url( '/' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Email notification. Webhooks via action.
	 *
	 * @param int                  $lead_id Lead ID.
	 * @param array<string, mixed> $data Data.
	 */
	private static function notify( int $lead_id, array $data ): void {
		$to      = get_option( 'admin_email' );
		$subject = sprintf( /* translators: %d lead id */ __( 'New lead #%d', 'mahmoud-elsaad-core' ), $lead_id );
		$body    = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		wp_mail( $to, $subject, (string) $body );
		do_action( 'mes_lead_created', $lead_id, $data );
	}

	/**
	 * Get form by type.
	 *
	 * @param string $type Type.
	 */
	public static function get_form( string $type ): ?\WP_Post {
		$posts = get_posts(
			array(
				'post_type'      => 'mes_form',
				'posts_per_page' => 1,
				'meta_key'       => '_mes_form_type',
				'meta_value'     => sanitize_key( $type ),
			)
		);
		return $posts[0] ?? null;
	}

	/**
	 * Shortcode [mes_form type="contact"].
	 *
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts( array( 'type' => 'contact' ), (array) $atts, 'mes_form' );
		return self::render( sanitize_key( $atts['type'] ) );
	}
}
