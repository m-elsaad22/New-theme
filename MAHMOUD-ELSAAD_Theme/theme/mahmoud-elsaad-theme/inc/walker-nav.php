<?php
/**
 * Accessible walker for the simple HTML nav.
 *
 * @package MahmoudElsaad\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MES_Nav_Walker extends Walker_Nav_Menu {
	/**
	 * Start element.
	 *
	 * @param string   $output Output.
	 * @param WP_Post  $item Item.
	 * @param int      $depth Depth.
	 * @param stdClass $args Args.
	 * @param int      $id ID.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$active  = in_array( 'current-menu-item', $classes, true ) ? ' aria-current="page"' : '';
		$output .= '<a href="' . esc_url( $item->url ) . '"' . $active . '>' . esc_html( $item->title ) . '</a>';
	}

	/**
	 * End element — links only, no wrapping li for the HTML design.
	 *
	 * @param string   $output Output.
	 * @param WP_Post  $item Item.
	 * @param int      $depth Depth.
	 * @param stdClass $args Args.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}
