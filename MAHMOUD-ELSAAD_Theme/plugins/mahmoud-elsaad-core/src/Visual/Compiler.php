<?php
/**
 * Compiles a visual tree into CSS. Only emits a node's own overrides.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Visual;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Compiler {
	/**
	 * Compile tree to CSS.
	 *
	 * @param array<string, mixed>|null $tree Tree.
	 */
	public static function compile( ?array $tree = null ): string {
		$tree = $tree ?? Tree::get();
		$bps  = Schema::breakpoints();
		$parts = array(
			'/* MAHMOUD ELSAAD visual tree — generated. Do not edit. */',
			':root{--mes-visual-ready:1}',
			'@keyframes mes-fade-in{from{opacity:0}to{opacity:1}}',
			'@keyframes mes-slide-up{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}',
			'[data-mes-node]:hover{transform:var(--mes-hover-transform,none)}',
		);

		$walk = static function ( array $node ) use ( &$walk, &$parts, $bps ): void {
			$id = (string) ( $node['id'] ?? '' );
			if ( '' !== $id ) {
				$sel     = '[data-mes-node="' . $id . '"]';
				$own     = $node['props'] ?? array();
				$desktop = Schema::sanitize_props( $own['desktop'] ?? array() );
				$tablet  = Schema::sanitize_props( $own['tablet'] ?? array() );
				$mobile  = Schema::sanitize_props( $own['mobile'] ?? array() );

				$d = self::decls( $desktop );
				if ( '' !== $d ) {
					$parts[] = $sel . '{' . $d . '}';
				}
				$t = self::decls( $tablet );
				if ( '' !== $t ) {
					$parts[] = '@media (max-width:' . (int) $bps['tablet'] . 'px){' . $sel . '{' . $t . '}}';
				}
				$m = self::decls( $mobile );
				if ( '' !== $m ) {
					$parts[] = '@media (max-width:' . (int) $bps['mobile'] . 'px){' . $sel . '{' . $m . '}}';
				}
			}
			foreach ( $node['children'] ?? array() as $child ) {
				if ( is_array( $child ) ) {
					$walk( $child );
				}
			}
		};
		$walk( $tree );

		$parts[] = '@media (prefers-reduced-motion: reduce){[data-mes-node]{animation:none!important;transition:none!important}}';

		return implode( "\n", $parts );
	}

	/**
	 * CSS declarations for one breakpoint's own props.
	 *
	 * @param array<string, string> $props Props.
	 */
	public static function decls( array $props ): string {
		$allowed = Schema::properties();
		$out     = '';
		foreach ( $props as $key => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			$map = $allowed[ $key ] ?? null;
			if ( ! $map ) {
				continue;
			}
			$css = (string) $map['css'];
			if ( 'hide' === $key ) {
				$out .= 'display:none;';
				continue;
			}
			if ( 'hover-transform' === $key ) {
				$out .= '--mes-hover-transform:' . $value . ';';
				$out .= 'transition:transform var(--mes-duration,200ms) var(--mes-ease,ease);';
				continue;
			}
			if ( 'animation' === $key && false === strpos( $value, ' ' ) ) {
				$named = array(
					'fade-in'  => 'mes-fade-in 600ms ease',
					'slide-up' => 'mes-slide-up 600ms ease',
				);
				$value = $named[ $value ] ?? $value;
			}
			if ( 'blur' === $key && false === strpos( $value, 'blur(' ) ) {
				$value = 'blur(' . $value . ')';
			}
			$out .= $css . ':' . $value . ';';
		}
		return $out;
	}

	/**
	 * Persist compiled CSS.
	 *
	 * @param array<string, mixed>|null $tree Tree.
	 */
	public static function persist( ?array $tree = null ): string {
		$css = self::compile( $tree );
		update_option( 'mes_compiled_css', $css, false );
		update_option( 'mes_compiled_css_ver', (string) time(), false );
		return $css;
	}
}
