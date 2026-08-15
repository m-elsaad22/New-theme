<?php
/**
 * Presentation helpers.
 *
 * @package MahmoudElsaad\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme mode attribute.
 */
function mes_theme_mode(): string {
	$design = function_exists( 'mes_get_option' ) ? mes_get_option( 'mes_design_system', array() ) : array();
	$mode   = $design['theme_mode'] ?? 'system';
	return in_array( $mode, array( 'light', 'dark', 'system' ), true ) ? $mode : 'system';
}

/**
 * Placeholder media.
 *
 * @param string $label Label.
 */
function mes_placeholder( string $label = '' ): string {
	$label = $label ?: __( 'Image', 'mahmoud-elsaad' );
	return '<div class="mes-placeholder imgph" role="img" aria-label="' . esc_attr( $label ) . '">' . esc_html( $label ) . '</div>';
}

/**
 * Featured image or placeholder.
 *
 * @param int    $post_id Post ID.
 * @param string $size Size.
 */
function mes_media( int $post_id, string $size = 'mes-card' ): string {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail( $post_id, $size, array( 'loading' => 'lazy', 'decoding' => 'async' ) );
	}
	return mes_placeholder( get_the_title( $post_id ) );
}

/**
 * Language/dir for html element.
 */
function mes_html_dir(): string {
	if ( class_exists( '\\MahmoudElsaad\\Core\\Localization\\Language' ) ) {
		return \MahmoudElsaad\Core\Localization\Language::dir();
	}
	return is_rtl() ? 'rtl' : 'ltr';
}

/**
 * Current language code.
 */
function mes_html_lang(): string {
	if ( class_exists( '\\MahmoudElsaad\\Core\\Localization\\Language' ) ) {
		return \MahmoudElsaad\Core\Localization\Language::current();
	}
	return 'ar';
}

/**
 * Visual node attributes (theme fallback if the plugin helper is missing).
 *
 * @param string $node_id Node id.
 */
function mes_theme_visual_attrs( string $node_id ): string {
	if ( function_exists( 'mes_visual_attrs' ) ) {
		return mes_visual_attrs( $node_id );
	}
	return ' data-mes-node="' . esc_attr( $node_id ) . '"';
}

/**
 * Simple breadcrumb list.
 */
function mes_breadcrumbs(): string {
	$items   = array();
	$items[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'mahmoud-elsaad' ) . '</a>';
	if ( is_singular() ) {
		$items[] = esc_html( get_the_title() );
	} elseif ( is_archive() ) {
		$items[] = esc_html( wp_strip_all_tags( get_the_archive_title() ) );
	}
	return '<ol class="crumb">' . implode( ' / ', $items ) . '</ol>';
}
