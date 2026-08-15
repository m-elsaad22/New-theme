<?php
/**
 * Visual property schema and CSS mapping.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Visual;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Schema {
	/**
	 * Breakpoints. Desktop is the base; tablet/mobile inherit then override.
	 *
	 * @return array<string, int>
	 */
	public static function breakpoints(): array {
		return array(
			'desktop' => 0,
			'tablet'  => 1024,
			'mobile'  => 640,
		);
	}

	/**
	 * Editable properties.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function properties(): array {
		return array(
			'font-family'     => array( 'group' => 'typography', 'css' => 'font-family', 'type' => 'text', 'inherit' => true ),
			'font-size'       => array( 'group' => 'typography', 'css' => 'font-size', 'type' => 'size', 'inherit' => true ),
			'font-weight'     => array( 'group' => 'typography', 'css' => 'font-weight', 'type' => 'select', 'options' => array( '400', '500', '600', '700', '800', '900' ), 'inherit' => true ),
			'line-height'     => array( 'group' => 'typography', 'css' => 'line-height', 'type' => 'text', 'inherit' => true ),
			'letter-spacing'  => array( 'group' => 'typography', 'css' => 'letter-spacing', 'type' => 'size', 'inherit' => true ),
			'text-transform'  => array( 'group' => 'typography', 'css' => 'text-transform', 'type' => 'select', 'options' => array( 'none', 'uppercase', 'lowercase', 'capitalize' ), 'inherit' => true ),
			'text-align'      => array( 'group' => 'typography', 'css' => 'text-align', 'type' => 'select', 'options' => array( 'start', 'center', 'end', 'justify' ), 'inherit' => true ),
			'color'           => array( 'group' => 'colors', 'css' => 'color', 'type' => 'color', 'inherit' => true ),
			'background'      => array( 'group' => 'colors', 'css' => 'background', 'type' => 'text', 'inherit' => false ),
			'border-color'    => array( 'group' => 'colors', 'css' => 'border-color', 'type' => 'color', 'inherit' => false ),
			'accent'          => array( 'group' => 'colors', 'css' => '--mes-node-accent', 'type' => 'color', 'inherit' => true ),
			'margin'          => array( 'group' => 'spacing', 'css' => 'margin', 'type' => 'box', 'inherit' => false ),
			'padding'         => array( 'group' => 'spacing', 'css' => 'padding', 'type' => 'box', 'inherit' => false ),
			'gap'             => array( 'group' => 'spacing', 'css' => 'gap', 'type' => 'size', 'inherit' => false ),
			'width'           => array( 'group' => 'layout', 'css' => 'width', 'type' => 'size', 'inherit' => false ),
			'max-width'       => array( 'group' => 'layout', 'css' => 'max-width', 'type' => 'size', 'inherit' => false ),
			'height'          => array( 'group' => 'layout', 'css' => 'height', 'type' => 'size', 'inherit' => false ),
			'min-height'      => array( 'group' => 'layout', 'css' => 'min-height', 'type' => 'size', 'inherit' => false ),
			'display'         => array( 'group' => 'layout', 'css' => 'display', 'type' => 'select', 'options' => array( 'block', 'flex', 'grid', 'inline-flex', 'none' ), 'inherit' => false ),
			'align-items'     => array( 'group' => 'layout', 'css' => 'align-items', 'type' => 'select', 'options' => array( 'stretch', 'center', 'flex-start', 'flex-end' ), 'inherit' => false ),
			'justify-content' => array( 'group' => 'layout', 'css' => 'justify-content', 'type' => 'select', 'options' => array( 'flex-start', 'center', 'flex-end', 'space-between' ), 'inherit' => false ),
			'position'        => array( 'group' => 'layout', 'css' => 'position', 'type' => 'select', 'options' => array( 'relative', 'absolute', 'sticky', 'static' ), 'inherit' => false ),
			'border-width'    => array( 'group' => 'borders', 'css' => 'border-width', 'type' => 'size', 'inherit' => false ),
			'border-style'    => array( 'group' => 'borders', 'css' => 'border-style', 'type' => 'select', 'options' => array( 'none', 'solid', 'dashed' ), 'inherit' => false ),
			'border-radius'   => array( 'group' => 'borders', 'css' => 'border-radius', 'type' => 'size', 'inherit' => false ),
			'box-shadow'      => array( 'group' => 'effects', 'css' => 'box-shadow', 'type' => 'text', 'inherit' => false ),
			'opacity'         => array( 'group' => 'effects', 'css' => 'opacity', 'type' => 'text', 'inherit' => false ),
			'blur'            => array( 'group' => 'effects', 'css' => 'backdrop-filter', 'type' => 'text', 'inherit' => false ),
			'hide'            => array( 'group' => 'visibility', 'css' => 'display', 'type' => 'toggle', 'inherit' => false ),
			'animation'       => array( 'group' => 'animation', 'css' => 'animation', 'type' => 'text', 'inherit' => false ),
			'transition'      => array( 'group' => 'animation', 'css' => 'transition', 'type' => 'text', 'inherit' => false ),
			'hover-transform' => array( 'group' => 'animation', 'css' => '--mes-hover-transform', 'type' => 'text', 'inherit' => false ),
		);
	}

	/**
	 * Sanitize a property map.
	 *
	 * @param array<string, mixed> $props Props.
	 * @return array<string, string>
	 */
	public static function sanitize_props( array $props ): array {
		$allowed = self::properties();
		$clean   = array();
		foreach ( $props as $key => $value ) {
			$key = str_replace( '_', '-', (string) $key );
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}
			$value = trim( (string) $value );
			if ( '' === $value ) {
				continue;
			}
			if ( 'hide' === $key && in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
				$value = 'none';
			}
			$clean[ $key ] = sanitize_text_field( $value );
		}
		return $clean;
	}

	/**
	 * Property groups for the editor UI.
	 *
	 * @return array<string, string>
	 */
	public static function groups(): array {
		return array(
			'typography'  => __( 'Typography', 'mahmoud-elsaad-core' ),
			'colors'      => __( 'Colors', 'mahmoud-elsaad-core' ),
			'spacing'     => __( 'Spacing', 'mahmoud-elsaad-core' ),
			'layout'      => __( 'Layout', 'mahmoud-elsaad-core' ),
			'borders'     => __( 'Borders', 'mahmoud-elsaad-core' ),
			'effects'     => __( 'Effects', 'mahmoud-elsaad-core' ),
			'visibility'  => __( 'Visibility', 'mahmoud-elsaad-core' ),
			'animation'   => __( 'Animation', 'mahmoud-elsaad-core' ),
		);
	}
}
