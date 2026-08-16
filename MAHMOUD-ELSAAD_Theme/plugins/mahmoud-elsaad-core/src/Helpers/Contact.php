<?php
/**
 * Central contact rendering — every phone/WhatsApp button must go through here.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Helpers;

use MahmoudElsaad\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Contact {
	/**
	 * Init template tags.
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'expose' ) );
	}

	/**
	 * Expose procedural helpers for the theme.
	 */
	public static function expose(): void {
		if ( ! function_exists( 'mes_render_phone_button' ) ) {
			require_once MES_CORE_PATH . 'src/Helpers/template-tags.php';
		}
	}

	/**
	 * Primary phone.
	 *
	 * @param string $country Optional ISO.
	 */
	public static function phone( string $country = '' ): string {
		$settings = Options::get( 'mes_contact_settings', array() );
		$phones   = $settings['phones'] ?? array();
		return self::pick( $phones, $country );
	}

	/**
	 * Primary WhatsApp.
	 *
	 * @param string $country Optional ISO.
	 */
	public static function whatsapp( string $country = '' ): string {
		$settings = Options::get( 'mes_contact_settings', array() );
		$list     = $settings['whatsapps'] ?? array();
		return self::pick( $list, $country );
	}

	/**
	 * Pick a numbered contact.
	 *
	 * @param array<int, array<string, mixed>> $list List.
	 * @param string                           $country Country.
	 */
	private static function pick( array $list, string $country ): string {
		if ( empty( $list ) || ! is_array( $list ) ) {
			return '';
		}
		$primary = '';
		foreach ( $list as $item ) {
			$number = isset( $item['number'] ) ? (string) $item['number'] : '';
			if ( '' === $number ) {
				continue;
			}
			if ( $country && ! empty( $item['country'] ) && strtoupper( (string) $item['country'] ) === strtoupper( $country ) ) {
				return $number;
			}
			if ( ! empty( $item['primary'] ) || '' === $primary ) {
				$primary = $number;
			}
		}
		return $primary;
	}

	/**
	 * Tel href.
	 *
	 * @param string $number Number.
	 */
	public static function tel_href( string $number ): string {
		$digits = preg_replace( '/[^0-9+]/', '', $number );
		return $digits ? 'tel:' . $digits : '';
	}

	/**
	 * WhatsApp href.
	 *
	 * @param string $number Number.
	 * @param string $text   Prefill.
	 */
	public static function wa_href( string $number, string $text = '' ): string {
		$digits = preg_replace( '/[^0-9]/', '', $number );
		if ( ! $digits ) {
			return '';
		}
		$url = 'https://wa.me/' . $digits;
		if ( $text ) {
			$url = add_query_arg( 'text', rawurlencode( $text ), $url );
		}
		return $url;
	}

	/**
	 * Render a tracked button.
	 *
	 * @param string               $type phone|whatsapp.
	 * @param array<string, mixed> $args Args.
	 */
	public static function render_button( string $type, array $args = array() ): string {
		$number    = $args['number'] ?? ( 'whatsapp' === $type ? self::whatsapp() : self::phone() );
		$placement = sanitize_key( $args['placement'] ?? 'content' );
		$default   = 'whatsapp' === $type ? __( 'WhatsApp', 'mahmoud-elsaad-core' ) : __( 'Call', 'mahmoud-elsaad-core' );
		$label     = array_key_exists( 'label', $args ) ? (string) $args['label'] : $default;
		$aria      = (string) ( $args['aria'] ?? $args['aria-label'] ?? '' );
		if ( '' === $aria ) {
			$aria = '' !== $label ? $label : $default;
		}
		$class = $args['class'] ?? ( 'whatsapp' === $type ? 'btn btn-wa' : 'btn btn-call' );
		$icon  = $args['icon'] ?? ( 'whatsapp' === $type ? 'fab fa-whatsapp' : 'fas fa-phone' );
		$href  = 'whatsapp' === $type ? self::wa_href( $number, (string) ( $args['text'] ?? '' ) ) : self::tel_href( $number );

		if ( ! $href ) {
			return '';
		}

		$attrs = array(
			'href'               => $href,
			'class'              => $class . ' mes-track-contact',
			'data-mes-type'      => $type,
			'data-mes-number'    => $number,
			'data-mes-placement' => $placement,
			'data-mes-post'      => (string) get_queried_object_id(),
			'rel'                => 'noopener nofollow',
			'aria-label'         => $aria,
		);
		if ( 'whatsapp' === $type ) {
			$attrs['target'] = '_blank';
		}

		$html = '<a';
		foreach ( $attrs as $k => $v ) {
			$html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}
		$html .= '><i class="' . esc_attr( $icon ) . '" aria-hidden="true"></i>';
		if ( '' !== $label ) {
			$html .= ' ' . esc_html( $label );
		}
		$html .= '</a>';
		return $html;
	}
}
