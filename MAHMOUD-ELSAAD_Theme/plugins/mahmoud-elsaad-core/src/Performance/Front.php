<?php
/**
 * Optional frontend performance hardening.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Performance;

use MahmoudElsaad\Core\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Front {
	/**
	 * Init.
	 */
	public static function init(): void {
		$settings = Options::get( 'mes_performance_settings', array() );
		if ( ! empty( $settings['disable_emojis'] ) ) {
			add_action( 'init', array( __CLASS__, 'disable_emojis' ) );
		}
		if ( ! empty( $settings['disable_embeds'] ) ) {
			add_action( 'init', array( __CLASS__, 'disable_embeds' ), 99 );
		}
	}

	/**
	 * Remove emoji scripts/styles.
	 */
	public static function disable_emojis(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	}

	/**
	 * Disable WP embeds.
	 */
	public static function disable_embeds(): void {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	}
}
