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
			'dashboard'  => __( 'Dashboard', 'mahmoud-elsaad-core' ),
			'content'    => __( 'Content', 'mahmoud-elsaad-core' ),
			'services'   => __( 'Services', 'mahmoud-elsaad-core' ),
			'cities'     => __( 'Cities', 'mahmoud-elsaad-core' ),
			'countries'  => __( 'Countries', 'mahmoud-elsaad-core' ),
			'offers'     => __( 'Offers', 'mahmoud-elsaad-core' ),
			'reviews'    => __( 'Reviews', 'mahmoud-elsaad-core' ),
			'portfolio'  => __( 'Portfolio', 'mahmoud-elsaad-core' ),
			'team'       => __( 'Team', 'mahmoud-elsaad-core' ),
			'partners'   => __( 'Partners', 'mahmoud-elsaad-core' ),
			'forms'      => __( 'Forms', 'mahmoud-elsaad-core' ),
			'leads'      => __( 'Leads', 'mahmoud-elsaad-core' ),
			'analytics'  => __( 'Analytics', 'mahmoud-elsaad-core' ),
			'design'     => __( 'Design', 'mahmoud-elsaad-core' ),
			'seo'        => __( 'SEO', 'mahmoud-elsaad-core' ),
			'ai'         => __( 'AI', 'mahmoud-elsaad-core' ),
			'performance'=> __( 'Performance', 'mahmoud-elsaad-core' ),
			'security'   => __( 'Security', 'mahmoud-elsaad-core' ),
			'settings'   => __( 'Settings', 'mahmoud-elsaad-core' ),
			'migration'  => __( 'Migration', 'mahmoud-elsaad-core' ),
		);

		foreach ( $subs as $slug => $label ) {
			add_submenu_page(
				'mes-control-center',
				$label,
				$label,
				'manage_options',
				'mes-control-center&view=' . $slug,
				array( __CLASS__, 'render' )
			);
		}
	}

	/**
	 * Assets only on our screens.
	 *
	 * @param string $hook Hook.
	 */
	public static function assets( string $hook ): void {
		if ( false === strpos( $hook, 'mes-control-center' ) ) {
			return;
		}
		wp_enqueue_style( 'mes-admin', MES_CORE_URL . 'assets/admin/css/control-center.css', array(), MES_CORE_VERSION );
		wp_enqueue_script( 'mes-admin', MES_CORE_URL . 'assets/admin/js/control-center.js', array(), MES_CORE_VERSION, true );
		wp_localize_script(
			'mes-admin',
			'mesAdmin',
			array(
				'root'  => esc_url_raw( rest_url( 'mes/v1/' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'view'  => sanitize_key( wp_unslash( $_GET['view'] ?? 'dashboard' ) ),
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
		if ( $screen && false !== strpos( (string) $screen->id, 'mes-control-center' ) ) {
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
		$view = sanitize_key( wp_unslash( $_GET['view'] ?? 'dashboard' ) );
		echo '<div class="mes-cc" dir="rtl" id="mes-cc" data-view="' . esc_attr( $view ) . '">';
		echo '<aside class="mes-cc-nav" aria-label="' . esc_attr__( 'Control Center', 'mahmoud-elsaad-core' ) . '">';
		echo '<div class="mes-cc-brand">MAHMOUD-ELSAAD</div>';
		$items = array(
			'dashboard'   => array( 'icon' => 'grid', 'label' => __( 'Dashboard', 'mahmoud-elsaad-core' ) ),
			'services'    => array( 'icon' => 'tool', 'label' => __( 'Services', 'mahmoud-elsaad-core' ) ),
			'cities'      => array( 'icon' => 'map', 'label' => __( 'Cities', 'mahmoud-elsaad-core' ) ),
			'leads'       => array( 'icon' => 'inbox', 'label' => __( 'Leads', 'mahmoud-elsaad-core' ) ),
			'analytics'   => array( 'icon' => 'chart', 'label' => __( 'Analytics', 'mahmoud-elsaad-core' ) ),
			'design'      => array( 'icon' => 'pen', 'label' => __( 'Design', 'mahmoud-elsaad-core' ) ),
			'seo'         => array( 'icon' => 'search', 'label' => __( 'SEO', 'mahmoud-elsaad-core' ) ),
			'ai'          => array( 'icon' => 'spark', 'label' => __( 'AI', 'mahmoud-elsaad-core' ) ),
			'settings'    => array( 'icon' => 'cog', 'label' => __( 'Settings', 'mahmoud-elsaad-core' ) ),
			'migration'   => array( 'icon' => 'box', 'label' => __( 'Migration', 'mahmoud-elsaad-core' ) ),
		);
		echo '<nav><ul>';
		foreach ( $items as $slug => $item ) {
			$url = admin_url( 'admin.php?page=mes-control-center&view=' . $slug );
			$on  = $view === $slug ? ' aria-current="page"' : '';
			echo '<li><a href="' . esc_url( $url ) . '"' . $on . '>' . esc_html( $item['label'] ) . '</a></li>';
		}
		echo '</ul></nav></aside>';
		echo '<main class="mes-cc-main"><header class="mes-cc-top">';
		echo '<button type="button" class="mes-cc-cmd" data-mes-cmd>Ctrl/⌘ K</button>';
		echo '</header><section class="mes-cc-view" id="mes-cc-view">';
		self::view( $view );
		echo '</section></main>';
		echo '<nav class="mes-cc-bottom" aria-label="' . esc_attr__( 'Mobile', 'mahmoud-elsaad-core' ) . '">';
		foreach ( array( 'dashboard', 'leads', 'analytics', 'settings' ) as $slug ) {
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=mes-control-center&view=' . $slug ) ) . '">' . esc_html( ucfirst( $slug ) ) . '</a>';
		}
		echo '</nav></div>';
	}

	/**
	 * View body.
	 *
	 * @param string $view View.
	 */
	private static function view( string $view ): void {
		$map = array(
			'services'  => 'edit.php?post_type=service',
			'cities'    => 'edit.php?post_type=mes_city',
			'countries' => 'edit.php?post_type=mes_country',
			'offers'    => 'edit.php?post_type=mes_offer',
			'reviews'   => 'edit.php?post_type=mes_review',
			'portfolio' => 'edit.php?post_type=mes_portfolio',
			'team'      => 'edit.php?post_type=mes_team',
			'partners'  => 'edit.php?post_type=mes_partner',
			'forms'     => 'edit.php?post_type=mes_form',
			'leads'     => 'edit.php?post_type=mes_lead',
		);
		if ( isset( $map[ $view ] ) ) {
			echo '<div class="mes-cc-card"><h1>' . esc_html( ucfirst( $view ) ) . '</h1>';
			echo '<p><a class="mes-cc-btn" href="' . esc_url( admin_url( $map[ $view ] ) ) . '">' . esc_html__( 'Open list', 'mahmoud-elsaad-core' ) . '</a></p></div>';
			return;
		}
		if ( 'dashboard' === $view ) {
			$counts = array(
				'service'       => wp_count_posts( 'service' )->publish ?? 0,
				'mes_city'      => wp_count_posts( 'mes_city' )->publish ?? 0,
				'mes_lead'      => wp_count_posts( 'mes_lead' )->private ?? 0,
				'mes_portfolio' => wp_count_posts( 'mes_portfolio' )->publish ?? 0,
			);
			echo '<div class="mes-cc-grid">';
			foreach ( $counts as $type => $n ) {
				echo '<article class="mes-cc-card"><h2>' . esc_html( $type ) . '</h2><p class="mes-cc-num">' . esc_html( (string) $n ) . '</p></article>';
			}
			echo '</div>';
			echo '<div class="mes-cc-card"><h2>' . esc_html__( 'Quick actions', 'mahmoud-elsaad-core' ) . '</h2><p>';
			echo '<a class="mes-cc-btn" href="' . esc_url( admin_url( 'post-new.php?post_type=service' ) ) . '">' . esc_html__( 'Add service', 'mahmoud-elsaad-core' ) . '</a> ';
			echo '<a class="mes-cc-btn" href="' . esc_url( admin_url( 'admin.php?page=mes-control-center&view=design' ) ) . '">' . esc_html__( 'Design', 'mahmoud-elsaad-core' ) . '</a>';
			echo '</p></div>';
			return;
		}
		if ( 'design' === $view ) {
			echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Design tokens', 'mahmoud-elsaad-core' ) . '</h1>';
			echo '<form id="mes-design-form" class="mes-cc-form">';
			echo '<label>' . esc_html__( 'Primary', 'mahmoud-elsaad-core' ) . '<input type="color" name="primary_color" value="#0A1F4E" /></label>';
			echo '<label>' . esc_html__( 'Secondary', 'mahmoud-elsaad-core' ) . '<input type="color" name="secondary_color" value="#2E9DF7" /></label>';
			echo '<label>' . esc_html__( 'Accent', 'mahmoud-elsaad-core' ) . '<input type="color" name="accent_color" value="#C9A227" /></label>';
			echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save', 'mahmoud-elsaad-core' ) . '</button>';
			echo '</form></div>';
			return;
		}
		if ( 'analytics' === $view ) {
			echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Call & WhatsApp tracking', 'mahmoud-elsaad-core' ) . '</h1>';
			echo '<div id="mes-analytics" data-mes-analytics></div></div>';
			return;
		}
		if ( 'ai' === $view ) {
			echo '<div class="mes-cc-card"><h1>' . esc_html__( 'AI providers', 'mahmoud-elsaad-core' ) . '</h1>';
			echo '<form id="mes-ai-form" class="mes-cc-form">';
			echo '<label>' . esc_html__( 'Provider', 'mahmoud-elsaad-core' ) . '<select name="provider"><option value="openai">OpenAI</option><option value="anthropic">Anthropic</option><option value="gemini">Google Gemini</option><option value="mistral">Mistral</option><option value="openrouter">OpenRouter</option><option value="compatible">OpenAI-compatible</option></select></label>';
			echo '<label>' . esc_html__( 'API key', 'mahmoud-elsaad-core' ) . '<input type="password" name="api_key" autocomplete="new-password" /></label>';
			echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save', 'mahmoud-elsaad-core' ) . '</button>';
			echo '</form></div>';
			return;
		}
		if ( 'migration' === $view ) {
			echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Legacy migration', 'mahmoud-elsaad-core' ) . '</h1>';
			echo '<p>' . esc_html__( 'Backup runs first. No secrets are copied.', 'mahmoud-elsaad-core' ) . '</p>';
			echo '<button class="mes-cc-btn" type="button" id="mes-run-migration">' . esc_html__( 'Run migration', 'mahmoud-elsaad-core' ) . '</button>';
			echo '<pre id="mes-migration-log"></pre></div>';
			return;
		}
		if ( 'settings' === $view ) {
			echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Brand & contact', 'mahmoud-elsaad-core' ) . '</h1>';
			echo '<form id="mes-brand-form" class="mes-cc-form">';
			echo '<label>' . esc_html__( 'Brand name', 'mahmoud-elsaad-core' ) . '<input type="text" name="name" /></label>';
			echo '<label>' . esc_html__( 'Tagline', 'mahmoud-elsaad-core' ) . '<input type="text" name="tagline" /></label>';
			echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save', 'mahmoud-elsaad-core' ) . '</button>';
			echo '</form></div>';
			return;
		}
		echo '<div class="mes-cc-card"><h1>' . esc_html( ucfirst( $view ) ) . '</h1><p>' . esc_html__( 'Use the navigation to manage this area.', 'mahmoud-elsaad-core' ) . '</p></div>';
	}
}
