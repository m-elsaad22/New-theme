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
 * Trusted homepage map iframe from Control Center configuration.
 */
function mes_render_map(): string {
	return \MahmoudElsaad\Core\Helpers\Contact::render_map();
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

/**
 * Whether the core plugin is active.
 */
function mes_core_ready(): bool {
	return true;
}

/**
 * Render a form by type.
 *
 * @param string               $type Type.
 * @param array<string, mixed> $args Args.
 */
function mes_render_form( string $type, array $args = array() ): string {
	if ( class_exists( '\\MahmoudElsaad\\Core\\Forms\\Engine' ) ) {
		return \MahmoudElsaad\Core\Forms\Engine::render( $type, $args );
	}
	return '';
}

/**
 * Language-prefixed URL.
 *
 * @param string      $path Path.
 * @param string|null $lang Language.
 */
function mes_language_url( string $path = '', ?string $lang = null ): string {
	if ( class_exists( '\\MahmoudElsaad\\Core\\Localization\\Language' ) ) {
		return \MahmoudElsaad\Core\Localization\Language::url( $path, $lang );
	}
	return home_url( '/' . ltrim( $path, '/' ) );
}

/**
 * Visual tree node attributes for inheritance/overrides.
 *
 * @param string $node_id Node id such as section:home.hero.
 */
function mes_visual_attrs( string $node_id ): string {
	return ' data-mes-node="' . esc_attr( $node_id ) . '"';
}
