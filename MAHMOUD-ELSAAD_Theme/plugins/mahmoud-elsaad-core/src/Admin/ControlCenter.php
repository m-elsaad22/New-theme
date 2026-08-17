<?php
/**
 * MAHMOUD-ELSAAD Control Center.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Admin;

use MahmoudElsaad\Core\Support\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ControlCenter {
	/**
	 * Init.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * Menu.
	 */
	public static function menu(): void {
		add_menu_page(
			__( 'Control Center', 'mahmoud-elsaad-core' ),
			__( 'MAHMOUD-ELSAAD', 'mahmoud-elsaad-core' ),
			'manage_options',
			'mes-control-center',
			array( __CLASS__, 'render' ),
			'dashicons-shield',
			2
		);

		$subs = array(
			'dashboard'   => __( 'Dashboard', 'mahmoud-elsaad-core' ),
			'content'     => __( 'Content', 'mahmoud-elsaad-core' ),
			'services'    => __( 'Services', 'mahmoud-elsaad-core' ),
			'cities'      => __( 'Cities', 'mahmoud-elsaad-core' ),
			'countries'   => __( 'Countries', 'mahmoud-elsaad-core' ),
			'offers'      => __( 'Offers', 'mahmoud-elsaad-core' ),
			'reviews'     => __( 'Reviews', 'mahmoud-elsaad-core' ),
			'portfolio'   => __( 'Portfolio', 'mahmoud-elsaad-core' ),
			'team'        => __( 'Team', 'mahmoud-elsaad-core' ),
			'partners'    => __( 'Partners', 'mahmoud-elsaad-core' ),
			'forms'       => __( 'Forms', 'mahmoud-elsaad-core' ),
			'leads'       => __( 'Leads', 'mahmoud-elsaad-core' ),
			'landings'    => __( 'Service × City', 'mahmoud-elsaad-core' ),
			'analytics'   => __( 'Analytics', 'mahmoud-elsaad-core' ),
			'design'      => __( 'Design', 'mahmoud-elsaad-core' ),
			'seo'         => __( 'SEO', 'mahmoud-elsaad-core' ),
			'ai'          => __( 'AI', 'mahmoud-elsaad-core' ),
			'performance' => __( 'Performance', 'mahmoud-elsaad-core' ),
			'security'    => __( 'Security', 'mahmoud-elsaad-core' ),
			'settings'    => __( 'Settings', 'mahmoud-elsaad-core' ),
			'migration'   => __( 'Migration', 'mahmoud-elsaad-core' ),
		);

		foreach ( $subs as $slug => $label ) {
			if ( 'dashboard' === $slug ) {
				continue;
			}
			add_submenu_page(
				'mes-control-center',
				$label,
				$label,
				'manage_options',
				'mes-cc-' . $slug,
				array( __CLASS__, 'render' )
			);
		}
	}

	/**
	 * Current view slug.
	 */
	public static function current_view(): string {
		$page = sanitize_key( wp_unslash( $_GET['page'] ?? 'mes-control-center' ) );
		$view = sanitize_key( wp_unslash( $_GET['view'] ?? '' ) );
		if ( $view ) {
			return $view;
		}
		if ( 0 === strpos( $page, 'mes-cc-' ) ) {
			return substr( $page, 7 );
		}
		return 'dashboard';
	}

	/**
	 * Assets only on our screens.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( string $hook ): void {
		if ( false === strpos( $hook, 'mes-control-center' ) && false === strpos( $hook, 'mes-cc-' ) ) {
			return;
		}
		wp_enqueue_style( 'mes-admin', MES_CORE_URL . 'assets/admin/css/control-center.css', array(), MES_CORE_VERSION );
		wp_enqueue_style( 'mes-visual-editor', MES_CORE_URL . 'assets/admin/css/visual-editor.css', array( 'mes-admin' ), MES_CORE_VERSION );
		wp_enqueue_script( 'mes-admin', MES_CORE_URL . 'assets/admin/js/control-center.js', array(), MES_CORE_VERSION, true );
		wp_enqueue_script( 'mes-visual-editor', MES_CORE_URL . 'assets/admin/js/visual-editor.js', array( 'mes-admin' ), MES_CORE_VERSION, true );
		wp_enqueue_script( 'mes-form-builder', MES_CORE_URL . 'assets/admin/js/form-builder.js', array( 'mes-admin' ), MES_CORE_VERSION, true );
		wp_localize_script(
			'mes-admin',
			'mesAdmin',
			array(
				'root'  => esc_url_raw( rest_url( 'mes/v1/' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'view'  => self::current_view(),
				'i18n'  => array(
					'saved' => __( 'Saved', 'mahmoud-elsaad-core' ),
					'error' => __( 'Error', 'mahmoud-elsaad-core' ),
				),
			)
		);
	}

	/**
	 * Body class.
	 *
	 * @param string $classes Classes.
	 */
	public static function body_class( string $classes ): string {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$id     = $screen ? (string) $screen->id : '';
		if ( false !== strpos( $id, 'mes-control-center' ) || false !== strpos( $id, 'mes-cc-' ) ) {
			$classes .= ' mes-cc-app';
		}
		return $classes;
	}

	/**
	 * App shell.
	 */
	public static function render(): void {
		if ( ! Capabilities::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission.', 'mahmoud-elsaad-core' ) );
		}
		$view  = self::current_view();
		$items = array(
			'dashboard'   => __( 'Dashboard', 'mahmoud-elsaad-core' ),
			'content'     => __( 'Content', 'mahmoud-elsaad-core' ),
			'services'    => __( 'Services', 'mahmoud-elsaad-core' ),
			'cities'      => __( 'Cities', 'mahmoud-elsaad-core' ),
			'landings'    => __( 'Service × City', 'mahmoud-elsaad-core' ),
			'leads'       => __( 'Leads', 'mahmoud-elsaad-core' ),
			'forms'       => __( 'Forms', 'mahmoud-elsaad-core' ),
			'analytics'   => __( 'Analytics', 'mahmoud-elsaad-core' ),
			'design'      => __( 'Design', 'mahmoud-elsaad-core' ),
			'seo'         => __( 'SEO', 'mahmoud-elsaad-core' ),
			'ai'          => __( 'AI', 'mahmoud-elsaad-core' ),
			'performance' => __( 'Performance', 'mahmoud-elsaad-core' ),
			'security'    => __( 'Security', 'mahmoud-elsaad-core' ),
			'settings'    => __( 'Settings', 'mahmoud-elsaad-core' ),
			'migration'   => __( 'Migration', 'mahmoud-elsaad-core' ),
		);

		echo '<div class="mes-cc" dir="rtl" id="mes-cc" data-view="' . esc_attr( $view ) . '">';
		echo '<aside class="mes-cc-nav" aria-label="' . esc_attr__( 'Control Center', 'mahmoud-elsaad-core' ) . '">';
		echo '<div class="mes-cc-brand">MAHMOUD-ELSAAD</div>';
		echo '<nav><ul>';
		foreach ( $items as $slug => $label ) {
			$url = 'dashboard' === $slug
				? admin_url( 'admin.php?page=mes-control-center' )
				: admin_url( 'admin.php?page=mes-cc-' . $slug );
			$on  = $view === $slug ? ' aria-current="page"' : '';
			echo '<li><a href="' . esc_url( $url ) . '"' . $on . '>' . esc_html( $label ) . '</a></li>';
		}
		echo '</ul></nav></aside>';
		echo '<main class="mes-cc-main"><header class="mes-cc-top">';
		echo '<button type="button" class="mes-cc-cmd" data-mes-cmd>Ctrl/⌘ K</button>';
		echo '</header><section class="mes-cc-view" id="mes-cc-view">';
		Views::render( $view );
		echo '</section></main>';
		echo '<nav class="mes-cc-bottom" aria-label="' . esc_attr__( 'Mobile', 'mahmoud-elsaad-core' ) . '">';
		foreach ( array( 'dashboard' => __( 'Home', 'mahmoud-elsaad-core' ), 'leads' => __( 'Leads', 'mahmoud-elsaad-core' ), 'analytics' => __( 'Stats', 'mahmoud-elsaad-core' ), 'settings' => __( 'Settings', 'mahmoud-elsaad-core' ) ) as $slug => $label ) {
			$url = 'dashboard' === $slug ? admin_url( 'admin.php?page=mes-control-center' ) : admin_url( 'admin.php?page=mes-cc-' . $slug );
			echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</nav></div>';
	}
}
