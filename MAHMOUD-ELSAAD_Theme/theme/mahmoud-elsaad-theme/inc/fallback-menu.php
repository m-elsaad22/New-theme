<?php
/**
 * Fallback primary links matching the HTML sitemap.
 *
 * @package MahmoudElsaad\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mes_fallback_menu(): void {
	$links = array(
		home_url( '/services/' )  => __( 'Services', 'mahmoud-elsaad' ),
		home_url( '/cities/' )    => __( 'Cities', 'mahmoud-elsaad' ),
		home_url( '/portfolio/' ) => __( 'Projects', 'mahmoud-elsaad' ),
		get_permalink( get_option( 'page_for_posts' ) ) ?: home_url( '/blog/' ) => __( 'Blog', 'mahmoud-elsaad' ),
		home_url( '/about/' )     => __( 'About', 'mahmoud-elsaad' ),
	);
	foreach ( $links as $url => $label ) {
		echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
}
