<?php
/**
 * Form engine and lead capture.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Forms;

use MahmoudElsaad\Core\Support\Logger;

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
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_shortcode( 'mes_form', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Public form scripts (conditional visibility).
	 */
	public static function assets(): void {
		wp_enqueue_script(
			'mes-forms',
			MES_CORE_URL . 'assets/public/js/forms.js',
			array(),
			MES_CORE_VERSION,
			true
		);
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
				Repository::save_settings( (int) $id, array() );
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
	 * Render a form by type (legacy) or by ID via args.
	 *
	 * @param string               $type Type.
	 * @param array<string, mixed> $args Args.
	 */
	public static function render( string $type, array $args = array() ): string {
		$form = ! empty( $args['id'] ) ? get_post( (int) $args['id'] ) : self::get_form( $type );
		if ( ! $form || 'mes_form' !== $form->post_type ) {
			return '';
		}
		return self::render_id( (int) $form->ID, $args );
	}

	/**
	 * Render a persisted form by ID.
	 *
	 * @param int                  $form_id Form ID.
	 * @param array<string, mixed> $args Args.
	 */
	public static function render_id( int $form_id, array $args = array() ): string {
		$form = get_post( $form_id );
		if ( ! $form || 'mes_form' !== $form->post_type ) {
			return '';
		}
		$fields   = Repository::fields( $form_id );
		$settings = Repository::settings( $form_id );
		if ( ! $fields ) {
			$fields = self::default_fields( (string) get_post_meta( $form_id, '_mes_form_type', true ) ?: 'contact' );
		}
		$success = isset( $_GET['mes_sent'] ) && (string) $_GET['mes_sent'] === (string) $form_id;
		$error   = isset( $_GET['mes_error'] ) && (string) $_GET['mes_error'] === (string) $form_id;
		$has_file = false;
		foreach ( $fields as $field ) {
			if ( 'file' === ( $field['type'] ?? '' ) ) {
				$has_file = true;
				break;
			}
		}

		ob_start();
		$enctype = $has_file ? ' enctype="multipart/form-data"' : '';
		echo '<form class="form-card mes-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" novalidate' . $enctype . '>';
		echo '<input type="hidden" name="action" value="mes_submit_form" />';
		echo '<input type="hidden" name="mes_form_id" value="' . esc_attr( (string) $form_id ) . '" />';
		echo '<input type="hidden" name="mes_source" value="' . esc_attr( (string) ( $args['source'] ?? get_permalink() ) ) . '" />';
		wp_nonce_field( 'mes_submit_form_' . $form_id, 'mes_form_nonce' );
		echo '<p class="mes-hp" style="position:absolute;left:-9999px;"><label>' . esc_html__( 'Leave empty', 'mahmoud-elsaad-core' ) . ' <input type="text" name="mes_hp" tabindex="-1" autocomplete="off" /></label></p>';
		if ( $error ) {
			echo '<div class="mes-form-error" role="alert"><p>' . esc_html( (string) $settings['error_message'] ) . '</p></div>';
		}
		echo '<div class="form-grid">';
		foreach ( $fields as $field ) {
			echo self::field_html( $field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
		$submit = $args['submit'] ?? ( $settings['submit_label'] ?: __( 'Send request', 'mahmoud-elsaad-core' ) );
		echo '<button class="btn btn-call" type="submit">' . esc_html( $submit ) . '</button>';
		if ( $success ) {
			echo '<div class="mes-form-success" id="bkSent"><p>' . esc_html( (string) $settings['success_message'] ) . '</p></div>';
		}
		echo '</form>';
		return (string) ob_get_clean();
	}

	/**
	 * Field markup.
	 *
	 * @param array<string, mixed> $field Field.
	 */
	public static function field_html( array $field ): string {
		$id    = sanitize_key( $field['id'] ?? 'field' );
		$label = (string) ( $field['label'] ?? $id );
		$type  = (string) ( $field['type'] ?? 'text' );
		$req   = ! empty( $field['required'] ) ? ' required' : '';
		$ph    = (string) ( $field['placeholder'] ?? '' );
		$help  = (string) ( $field['help'] ?? '' );
		$val   = is_array( $field['validation'] ?? null ) ? $field['validation'] : array();
		$cond  = is_array( $field['conditional'] ?? null ) ? $field['conditional'] : array();
		$name  = 'mes_field[' . $id . ']';
		$width = ( 'half' === ( $field['width'] ?? '' ) ) ? ' mes-fld-half' : '';
		$attrs = '';
		if ( $ph ) {
			$attrs .= ' placeholder="' . esc_attr( $ph ) . '"';
		}
		if ( ! empty( $val['min'] ) ) {
			$attrs .= ' min="' . esc_attr( (string) $val['min'] ) . '"';
		}
		if ( ! empty( $val['max'] ) ) {
			$attrs .= ' max="' . esc_attr( (string) $val['max'] ) . '"';
		}
		if ( ! empty( $val['minLength'] ) ) {
			$attrs .= ' minlength="' . esc_attr( (string) $val['minLength'] ) . '"';
		}
		if ( ! empty( $val['maxLength'] ) ) {
			$attrs .= ' maxlength="' . esc_attr( (string) $val['maxLength'] ) . '"';
		}
		if ( ! empty( $val['pattern'] ) ) {
			$attrs .= ' pattern="' . esc_attr( (string) $val['pattern'] ) . '"';
		}
		$wrap = 'class="fld' . $width . '"';
		if ( ! empty( $cond['field'] ) ) {
			$wrap .= ' data-mes-cond-field="' . esc_attr( (string) $cond['field'] ) . '" data-mes-cond-op="' . esc_attr( (string) ( $cond['op'] ?? 'equals' ) ) . '" data-mes-cond-value="' . esc_attr( (string) ( $cond['value'] ?? '' ) ) . '" hidden';
		}
		$html = '<label ' . $wrap . '><span>' . esc_html( $label ) . '</span>';

		if ( in_array( $type, array( 'service', 'city', 'country' ), true ) ) {
			$ptype = array(
				'service' => 'service',
				'city'    => 'mes_city',
				'country' => 'mes_country',
			)[ $type ];
			$items = get_posts(
				array(
					'post_type'      => $ptype,
					'posts_per_page' => 200,
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
			$html .= '<textarea name="' . esc_attr( $name ) . '" rows="4"' . $req . $attrs . '></textarea>';
		} elseif ( 'select' === $type ) {
			$html .= '<select class="sel" name="' . esc_attr( $name ) . '"' . $req . '>';
			$html .= '<option value="">' . esc_html__( 'Select', 'mahmoud-elsaad-core' ) . '</option>';
			foreach ( (array) ( $field['options'] ?? array() ) as $opt ) {
				$html .= '<option value="' . esc_attr( (string) $opt ) . '">' . esc_html( (string) $opt ) . '</option>';
			}
			$html .= '</select>';
		} elseif ( 'radio' === $type ) {
			$html .= '<span class="mes-choices">';
			foreach ( (array) ( $field['options'] ?? array() ) as $opt ) {
				$html .= '<label class="mes-choice"><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $opt ) . '"' . $req . ' /> ' . esc_html( (string) $opt ) . '</label>';
			}
			$html .= '</span>';
		} elseif ( 'checkbox' === $type ) {
			$html .= '<span class="mes-choices">';
			foreach ( (array) ( $field['options'] ?? array() ) as $i => $opt ) {
				$html .= '<label class="mes-choice"><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( (string) $opt ) . '" /> ' . esc_html( (string) $opt ) . '</label>';
			}
			$html .= '</span>';
		} else {
			$input = in_array( $type, array( 'email', 'number', 'date', 'time', 'file' ), true ) ? $type : 'text';
			if ( 'phone' === $type ) {
				$input = 'tel';
			}
			$html .= '<input type="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '"' . $req . $attrs . ' />';
		}
		if ( $help ) {
			$html .= '<small class="mes-field-help">' . esc_html( $help ) . '</small>';
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

		$schema   = Repository::fields( $form_id );
		$settings = Repository::settings( $form_id );
		$raw      = isset( $_POST['mes_field'] ) && is_array( $_POST['mes_field'] ) ? wp_unslash( $_POST['mes_field'] ) : array();
		$clean    = array();
		$errors   = array();

		foreach ( $schema as $field ) {
			$fid   = sanitize_key( (string) ( $field['id'] ?? '' ) );
			$type  = (string) ( $field['type'] ?? 'text' );
			$value = $raw[ $fid ] ?? '';
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'sanitize_text_field', $value ) );
			} else {
				$value = sanitize_text_field( (string) $value );
			}
			if ( ! empty( $field['required'] ) && '' === $value && 'file' !== $type ) {
				$errors[] = $fid;
			}
			if ( 'email' === $type && '' !== $value && ! is_email( $value ) ) {
				$errors[] = $fid;
			}
			$rules = is_array( $field['validation'] ?? null ) ? $field['validation'] : array();
			if ( '' !== $value && ! empty( $rules['pattern'] ) && ! preg_match( '/' . str_replace( '/', '\\/', (string) $rules['pattern'] ) . '/', $value ) ) {
				$errors[] = $fid;
			}
			if ( '' !== $value && ! empty( $rules['minLength'] ) && strlen( $value ) < (int) $rules['minLength'] ) {
				$errors[] = $fid;
			}
			if ( '' !== $value && ! empty( $rules['maxLength'] ) && strlen( $value ) > (int) $rules['maxLength'] ) {
				$errors[] = $fid;
			}
			$clean[ $fid ] = $value;
		}

		if ( $errors ) {
			$redirect = add_query_arg( 'mes_error', $form_id, wp_get_referer() ?: home_url( '/' ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		$files = self::handle_files( $form_id, $schema );
		if ( is_wp_error( $files ) ) {
			$redirect = add_query_arg( 'mes_error', $form_id, wp_get_referer() ?: home_url( '/' ) );
			wp_safe_redirect( $redirect );
			exit;
		}
		foreach ( $files as $fid => $url ) {
			$clean[ $fid ] = $url;
		}

		$lead_id = 0;
		if ( ! empty( $settings['save_lead'] ) ) {
			$name    = $clean['name'] ?? __( 'Lead', 'mahmoud-elsaad-core' );
			$lead_id = wp_insert_post(
				array(
					'post_type'    => 'mes_lead',
					'post_status'  => 'private',
					'post_title'   => $name . ' — ' . wp_date( 'Y-m-d H:i' ),
					'post_content' => wp_json_encode( $clean ),
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
			do_action( 'mes_lead_created', $lead_id, $clean );
		}

		if ( ! empty( $settings['email'] ) ) {
			self::notify_email( $lead_id, $clean, $settings );
		}
		if ( ! empty( $settings['webhook'] ) && ! empty( $settings['webhook_url'] ) ) {
			if ( self::webhook_allowed( (string) $settings['webhook_url'] ) ) {
				self::notify_webhook( (string) $settings['webhook_url'], $lead_id, $clean );
			} else {
				Logger::log( 'forms', 'Webhook blocked', array( 'reason' => 'ssrf' ) );
			}
		}

		Logger::log( 'forms', 'Form submitted', array( 'form_id' => $form_id, 'lead_id' => $lead_id ) );

		if ( ! empty( $settings['whatsapp'] ) && ! empty( $settings['whatsapp_redirect'] ) && ! empty( $settings['whatsapp_number'] ) ) {
			$digits = preg_replace( '/\D+/', '', (string) $settings['whatsapp_number'] );
			$text   = rawurlencode( sprintf( __( 'New request from %s', 'mahmoud-elsaad-core' ), $clean['name'] ?? '' ) );
			wp_redirect( 'https://wa.me/' . $digits . '?text=' . $text );
			exit;
		}

		$redirect = add_query_arg( 'mes_sent', $form_id, wp_get_referer() ?: home_url( '/' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle file fields.
	 *
	 * @param int                        $form_id Form ID.
	 * @param list<array<string, mixed>> $schema Fields.
	 * @return array<string, string>|\WP_Error
	 */
	private static function handle_files( int $form_id, array $schema ) {
		$out = array();
		if ( empty( $_FILES['mes_field'] ) || ! is_array( $_FILES['mes_field'] ) ) {
			return $out;
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$bag = $_FILES['mes_field'];
		foreach ( $schema as $field ) {
			if ( 'file' !== ( $field['type'] ?? '' ) ) {
				continue;
			}
			$fid = sanitize_key( (string) $field['id'] );
			if ( empty( $bag['name'][ $fid ] ) ) {
				if ( ! empty( $field['required'] ) ) {
					return new \WP_Error( 'mes_file', 'Required file missing' );
				}
				continue;
			}
			$file = array(
				'name'     => $bag['name'][ $fid ],
				'type'     => $bag['type'][ $fid ] ?? '',
				'tmp_name' => $bag['tmp_name'][ $fid ] ?? '',
				'error'    => $bag['error'][ $fid ] ?? UPLOAD_ERR_NO_FILE,
				'size'     => $bag['size'][ $fid ] ?? 0,
			);
			$allowed = apply_filters(
				'mes_form_upload_mimes',
				array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'png'          => 'image/png',
					'gif'          => 'image/gif',
					'webp'         => 'image/webp',
					'pdf'          => 'application/pdf',
				)
			);
			$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed );
			if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
				return new \WP_Error( 'mes_file', 'File type not allowed' );
			}
			$uploaded = wp_handle_upload(
				$file,
				array(
					'test_form' => false,
					'mimes'     => $allowed,
				)
			);
			if ( isset( $uploaded['error'] ) ) {
				return new \WP_Error( 'mes_file', (string) $uploaded['error'] );
			}
			$out[ $fid ] = esc_url_raw( (string) ( $uploaded['url'] ?? '' ) );
		}
		unset( $form_id );
		return $out;
	}

	/**
	 * Email notification.
	 *
	 * @param int                  $lead_id Lead ID.
	 * @param array<string, mixed> $data Data.
	 * @param array<string, mixed> $settings Settings.
	 */
	private static function notify_email( int $lead_id, array $data, array $settings ): void {
		$to = sanitize_email( (string) ( $settings['notify_email'] ?? '' ) );
		if ( ! $to ) {
			$to = (string) get_option( 'admin_email' );
		}
		$subject = sprintf( /* translators: %d lead id */ __( 'New lead #%d', 'mahmoud-elsaad-core' ), $lead_id ?: 0 );
		$body    = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		if ( ! empty( $settings['whatsapp'] ) && ! empty( $settings['whatsapp_number'] ) ) {
			$body .= "\n\nWhatsApp: " . $settings['whatsapp_number'];
		}
		wp_mail( $to, $subject, (string) $body );
	}

	/**
	 * Webhook notification.
	 *
	 * @param string               $url URL.
	 * @param int                  $lead_id Lead ID.
	 * @param array<string, mixed> $data Data.
	 */
	/**
	 * Allow only http(s) webhooks that are not metadata/link-local targets.
	 */
	public static function webhook_allowed( string $url ): bool {
		$url = esc_url_raw( $url );
		$parts = wp_parse_url( $url );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}
		if ( ! in_array( strtolower( (string) $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
			return false;
		}
		$host = strtolower( (string) $parts['host'] );
		if ( in_array( $host, array( 'metadata.google.internal', 'metadata.google.com' ), true ) ) {
			return false;
		}
		$ips = array();
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			$ips[] = $host;
		} else {
			$resolved = gethostbynamel( $host );
			$ips      = is_array( $resolved ) ? $resolved : array();
		}
		if ( array() === $ips ) {
			return false;
		}
		foreach ( $ips as $ip ) {
			if ( '169.254.169.254' === $ip || '0.0.0.0' === $ip ) {
				return false;
			}
			$flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
			$local_ok = apply_filters( 'mes_webhook_allow_loopback', defined( 'WP_DEBUG' ) && WP_DEBUG );
			if ( $local_ok && ( '127.0.0.1' === $ip || '::1' === $ip ) ) {
				continue;
			}
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, $flags ) ) {
				return false;
			}
		}
		return (bool) apply_filters( 'mes_webhook_url_allowed', true, $url );
	}

	private static function notify_webhook( string $url, int $lead_id, array $data ): void {
		wp_remote_post(
			$url,
			array(
				'timeout' => 8,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'lead_id' => $lead_id,
						'data'    => $data,
					)
				),
			)
		);
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
	 * Shortcode [mes_form type="contact"] or [mes_form id="12"].
	 *
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'type' => 'contact',
				'id'   => '',
			),
			(array) $atts,
			'mes_form'
		);
		if ( $atts['id'] ) {
			return self::render_id( absint( $atts['id'] ) );
		}
		return self::render( sanitize_key( $atts['type'] ) );
	}
}
