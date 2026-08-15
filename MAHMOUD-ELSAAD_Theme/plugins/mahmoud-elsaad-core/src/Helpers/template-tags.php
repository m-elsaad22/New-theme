<?php
/**
 * Theme-facing helpers. Loaded by the core plugin.
 *
 * @package MahmoudElsaad\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracked phone button.
 *
 * @param array<string, mixed> $args Args.
 */
function mes_render_phone_button( array $args = array() ): string {
	return \MahmoudElsaad\Core\Helpers\Contact::render_button( 'phone', $args );
}

/**
 * Tracked WhatsApp button.
 *
 * @param array<string, mixed> $args Args.
 */
function mes_render_whatsapp_button( array $args = array() ): string {
	return \MahmoudElsaad\Core\Helpers\Contact::render_button( 'whatsapp', $args );
}

/**
 * Brand name from settings.
 */
function mes_brand_name(): string {
	$brand = \MahmoudElsaad\Core\Support\Options::get( 'mes_brand_settings', array() );
	$name  = trim( (string) ( $brand['name'] ?? '' ) );
	if ( $name ) {
		return $name;
	}
	return (string) get_bloginfo( 'name' );
}

/**
 * Brand tagline.
 */
function mes_brand_tagline(): string {
	$brand = \MahmoudElsaad\Core\Support\Options::get( 'mes_brand_settings', array() );
	$tag   = trim( (string) ( $brand['tagline'] ?? '' ) );
	return $tag !== '' ? $tag : (string) get_bloginfo( 'description' );
}

/**
 * Get a namespaced option.
 *
 * @param string $key Key.
 * @param mixed  $default Default.
 * @return mixed
 */
function mes_get_option( string $key, $default = array() ) {
	return \MahmoudElsaad\Core\Support\Options::get( $key, $default );
}
