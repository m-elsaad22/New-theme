<?php
/**
 * Persist form definitions on mes_form posts.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Repository {
	/**
	 * Allowed field types.
	 *
	 * @return string[]
	 */
	public static function field_types(): array {
		return array(
			'text',
			'email',
			'phone',
			'number',
			'textarea',
			'select',
			'radio',
			'checkbox',
			'date',
			'time',
			'file',
			'country',
			'city',
			'service',
		);
	}

	/**
	 * Default submit actions.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_settings(): array {
		return array(
			'success_message'    => __( 'Your request was received. We will contact you shortly.', 'mahmoud-elsaad-core' ),
			'error_message'      => __( 'Please check the highlighted fields and try again.', 'mahmoud-elsaad-core' ),
			'save_lead'          => true,
			'email'              => true,
			'notify_email'       => '',
			'webhook'            => false,
			'webhook_url'        => '',
			'whatsapp'           => false,
			'whatsapp_number'    => '',
			'whatsapp_redirect'  => false,
			'submit_label'       => __( 'Send request', 'mahmoud-elsaad-core' ),
		);
	}

	/**
	 * List forms.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all(): array {
		$posts = get_posts(
			array(
				'post_type'      => 'mes_form',
				'posts_per_page' => 100,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		return array_map( array( __CLASS__, 'to_array' ), $posts );
	}

	/**
	 * Get one form.
	 *
	 * @param int $id Form ID.
	 * @return array<string, mixed>|null
	 */
	public static function get( int $id ): ?array {
		$post = get_post( $id );
		if ( ! $post || 'mes_form' !== $post->post_type ) {
			return null;
		}
		return self::to_array( $post );
	}

	/**
	 * Create a form.
	 *
	 * @param array<string, mixed> $data Data.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function create( array $data ) {
		$title = sanitize_text_field( (string) ( $data['title'] ?? __( 'Untitled form', 'mahmoud-elsaad-core' ) ) );
		$id    = wp_insert_post(
			array(
				'post_type'   => 'mes_form',
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		$type = sanitize_key( (string) ( $data['type'] ?? 'custom' ) );
		update_post_meta( (int) $id, '_mes_form_type', $type ?: 'custom' );
		$fields = isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : Engine::default_fields( 'contact' );
		self::save_fields( (int) $id, $fields );
		self::save_settings( (int) $id, is_array( $data['settings'] ?? null ) ? $data['settings'] : array() );
		return self::get( (int) $id );
	}

	/**
	 * Update a form.
	 *
	 * @param int                  $id Form ID.
	 * @param array<string, mixed> $data Data.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function update( int $id, array $data ) {
		$post = get_post( $id );
		if ( ! $post || 'mes_form' !== $post->post_type ) {
			return new \WP_Error( 'mes_missing_form', 'Form not found', array( 'status' => 404 ) );
		}
		$update = array( 'ID' => $id );
		if ( isset( $data['title'] ) ) {
			$update['post_title'] = sanitize_text_field( (string) $data['title'] );
		}
		if ( isset( $data['status'] ) ) {
			$update['post_status'] = sanitize_key( (string) $data['status'] );
		}
		wp_update_post( $update );
		if ( isset( $data['type'] ) ) {
			update_post_meta( $id, '_mes_form_type', sanitize_key( (string) $data['type'] ) );
		}
		if ( isset( $data['fields'] ) && is_array( $data['fields'] ) ) {
			self::save_fields( $id, $data['fields'] );
		}
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			self::save_settings( $id, $data['settings'] );
		}
		return self::get( $id );
	}

	/**
	 * Duplicate a form.
	 *
	 * @param int $id Form ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function duplicate( int $id ) {
		$src = self::get( $id );
		if ( ! $src ) {
			return new \WP_Error( 'mes_missing_form', 'Form not found', array( 'status' => 404 ) );
		}
		$src['title'] = $src['title'] . ' ' . __( '(copy)', 'mahmoud-elsaad-core' );
		unset( $src['id'] );
		return self::create( $src );
	}

	/**
	 * Delete a form.
	 *
	 * @param int $id Form ID.
	 */
	public static function delete( int $id ): bool {
		$post = get_post( $id );
		if ( ! $post || 'mes_form' !== $post->post_type ) {
			return false;
		}
		return (bool) wp_delete_post( $id, true );
	}

	/**
	 * Persist fields JSON.
	 *
	 * @param int                        $id Form ID.
	 * @param list<array<string, mixed>> $fields Fields.
	 */
	public static function save_fields( int $id, array $fields ): void {
		$clean = array();
		foreach ( $fields as $field ) {
			if ( is_array( $field ) ) {
				$san = self::sanitize_field( $field );
				if ( $san ) {
					$clean[] = $san;
				}
			}
		}
		update_post_meta( $id, '_mes_fields', wp_json_encode( $clean ) );
	}

	/**
	 * Persist settings.
	 *
	 * @param int                  $id Form ID.
	 * @param array<string, mixed> $settings Settings.
	 */
	public static function save_settings( int $id, array $settings ): void {
		$base = self::settings( $id );
		$next = array_merge( $base, $settings );
		$next['success_message']   = sanitize_textarea_field( (string) $next['success_message'] );
		$next['error_message']     = sanitize_textarea_field( (string) $next['error_message'] );
		$next['notify_email']      = sanitize_email( (string) $next['notify_email'] );
		$next['webhook_url']       = esc_url_raw( (string) $next['webhook_url'] );
		$next['whatsapp_number']   = sanitize_text_field( (string) $next['whatsapp_number'] );
		$next['submit_label']      = sanitize_text_field( (string) $next['submit_label'] );
		$next['save_lead']         = ! empty( $next['save_lead'] );
		$next['email']             = ! empty( $next['email'] );
		$next['webhook']           = ! empty( $next['webhook'] );
		$next['whatsapp']          = ! empty( $next['whatsapp'] );
		$next['whatsapp_redirect'] = ! empty( $next['whatsapp_redirect'] );
		update_post_meta( $id, '_mes_form_settings', wp_json_encode( $next ) );
		update_post_meta( $id, '_mes_success_message', $next['success_message'] );
	}

	/**
	 * Settings for a form.
	 *
	 * @param int $id Form ID.
	 * @return array<string, mixed>
	 */
	public static function settings( int $id ): array {
		$raw = json_decode( (string) get_post_meta( $id, '_mes_form_settings', true ), true );
		$out = array_merge( self::default_settings(), is_array( $raw ) ? $raw : array() );
		$legacy = (string) get_post_meta( $id, '_mes_success_message', true );
		if ( $legacy && empty( $raw['success_message'] ) ) {
			$out['success_message'] = $legacy;
		}
		return $out;
	}

	/**
	 * Fields for a form.
	 *
	 * @param int $id Form ID.
	 * @return list<array<string, mixed>>
	 */
	public static function fields( int $id ): array {
		$raw = json_decode( (string) get_post_meta( $id, '_mes_fields', true ), true );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $field ) {
			if ( is_array( $field ) ) {
				$san = self::sanitize_field( $field );
				if ( $san ) {
					$out[] = $san;
				}
			}
		}
		return $out;
	}

	/**
	 * @param \WP_Post $post Post.
	 * @return array<string, mixed>
	 */
	public static function to_array( \WP_Post $post ): array {
		return array(
			'id'       => $post->ID,
			'title'    => get_the_title( $post ),
			'type'     => (string) get_post_meta( $post->ID, '_mes_form_type', true ),
			'status'   => $post->post_status,
			'fields'   => self::fields( $post->ID ),
			'settings' => self::settings( $post->ID ),
		);
	}

	/**
	 * @param array<string, mixed> $field Field.
	 * @return array<string, mixed>|null
	 */
	public static function sanitize_field( array $field ): ?array {
		$id   = sanitize_key( (string) ( $field['id'] ?? '' ) );
		$type = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		if ( '' === $id ) {
			$id = 'field_' . substr( md5( wp_json_encode( $field ) ), 0, 8 );
		}
		if ( ! in_array( $type, self::field_types(), true ) ) {
			$type = 'text';
		}
		$options = array();
		foreach ( (array) ( $field['options'] ?? array() ) as $opt ) {
			$opt = sanitize_text_field( (string) $opt );
			if ( '' !== $opt ) {
				$options[] = $opt;
			}
		}
		$validation = is_array( $field['validation'] ?? null ) ? $field['validation'] : array();
		$cond       = is_array( $field['conditional'] ?? null ) ? $field['conditional'] : array();
		return array(
			'id'          => $id,
			'type'        => $type,
			'label'       => sanitize_text_field( (string) ( $field['label'] ?? $id ) ),
			'placeholder' => sanitize_text_field( (string) ( $field['placeholder'] ?? '' ) ),
			'help'        => sanitize_text_field( (string) ( $field['help'] ?? '' ) ),
			'required'    => ! empty( $field['required'] ),
			'width'       => in_array( ( $field['width'] ?? 'full' ), array( 'full', 'half' ), true ) ? $field['width'] : 'full',
			'options'     => $options,
			'validation'  => array(
				'min'       => sanitize_text_field( (string) ( $validation['min'] ?? '' ) ),
				'max'       => sanitize_text_field( (string) ( $validation['max'] ?? '' ) ),
				'minLength' => sanitize_text_field( (string) ( $validation['minLength'] ?? '' ) ),
				'maxLength' => sanitize_text_field( (string) ( $validation['maxLength'] ?? '' ) ),
				'pattern'   => sanitize_text_field( (string) ( $validation['pattern'] ?? '' ) ),
			),
			'conditional' => array(
				'field' => sanitize_key( (string) ( $cond['field'] ?? '' ) ),
				'op'    => in_array( ( $cond['op'] ?? 'equals' ), array( 'equals', 'not_equals', 'contains' ), true ) ? $cond['op'] : 'equals',
				'value' => sanitize_text_field( (string) ( $cond['value'] ?? '' ) ),
			),
		);
	}
}
