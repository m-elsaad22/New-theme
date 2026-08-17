<?php
/**
 * Live preview of the real frontend inside the Control Center.
 *
 * The iframe loads the actual homepage. The editor posts compiled CSS
 * via postMessage; this listener injects it. No mock canvas.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Visual;

use MahmoudElsaad\Core\Support\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Preview {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_enable' ) );
	}

	/**
	 * Whether this request is a visual preview.
	 */
	public static function is_preview(): bool {
		return isset( $_GET['mes_preview'] ) && '1' === (string) $_GET['mes_preview'];
	}

	/**
	 * Preview URL for the editor iframe (real homepage).
	 */
	public static function url( string $path = '/' ): string {
		return add_query_arg(
			array(
				'mes_preview' => '1',
				'_wpnonce'    => wp_create_nonce( 'mes_visual_preview' ),
			),
			home_url( $path )
		);
	}

	/**
	 * Gate preview mode and inject the CSS listener.
	 */
	public static function maybe_enable(): void {
		if ( ! self::is_preview() ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );
		if ( ! Capabilities::can_manage() || ! wp_verify_nonce( $nonce, 'mes_visual_preview' ) ) {
			wp_die( esc_html__( 'Preview is restricted to platform managers.', 'mahmoud-elsaad-core' ), 403 );
		}
		show_admin_bar( false );
		add_action( 'wp_footer', array( __CLASS__, 'listener' ), 99 );
	}

	/**
	 * Receive compiled CSS from the parent editor and apply it live.
	 */
	public static function listener(): void {
		?>
		<script id="mes-preview-listener">
		(function () {
			function apply(css) {
				var el = document.getElementById('mes-preview-css');
				if (!el) {
					el = document.createElement('style');
					el.id = 'mes-preview-css';
					document.head.appendChild(el);
				}
				el.textContent = css || '';
			}
			window.addEventListener('message', function (e) {
				if (e.origin !== window.location.origin) return;
				var data = e.data || {};
				if (data.type === 'mes-visual-css' && typeof data.css === 'string') {
					apply(data.css);
				}
				if (data.type === 'mes-visual-select' && typeof data.node === 'string') {
					document.querySelectorAll('[data-mes-node].mes-visual-focus').forEach(function (n) {
						n.classList.remove('mes-visual-focus');
					});
					var hit = document.querySelector('[data-mes-node="' + data.node + '"]');
					if (hit) {
						hit.classList.add('mes-visual-focus');
						hit.scrollIntoView({ block: 'center', behavior: 'smooth' });
					}
				}
			});
			document.addEventListener('click', function (e) {
				var node = e.target && e.target.closest ? e.target.closest('[data-mes-node]') : null;
				if (!node || !window.parent) return;
				window.parent.postMessage({ type: 'mes-visual-click', node: node.getAttribute('data-mes-node') }, window.location.origin);
			}, true);
			if (window.parent) {
				window.parent.postMessage({ type: 'mes-visual-ready' }, window.location.origin);
			}
		})();
		</script>
		<style>.mes-visual-focus{outline:2px dashed #2E9DF7;outline-offset:4px}</style>
		<?php
	}
}
