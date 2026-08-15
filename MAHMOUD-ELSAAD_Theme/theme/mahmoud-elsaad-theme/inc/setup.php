<?php
/**
 * Theme supports and menus.
 *
 * @package MahmoudElsaad\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	static function () {
		load_theme_textdomain( 'mahmoud-elsaad', MES_THEME_PATH . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'custom-logo', array( 'height' => 92, 'width' => 92, 'flex-width' => true, 'flex-height' => true ) );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'wp-block-styles' );
		register_nav_menus(
			array(
				'primary'   => __( 'Primary', 'mahmoud-elsaad' ),
				'footer'    => __( 'Footer', 'mahmoud-elsaad' ),
				'legal'     => __( 'Legal', 'mahmoud-elsaad' ),
				'mobile'    => __( 'Mobile', 'mahmoud-elsaad' ),
			)
		);
		add_image_size( 'mes-card', 720, 480, true );
		add_image_size( 'mes-hero', 1600, 900, true );
	}
);

add_action(
	'widgets_init',
	static function () {
		register_sidebar(
			array(
				'name'          => __( 'Blog sidebar', 'mahmoud-elsaad' ),
				'id'            => 'mes-blog',
				'before_widget' => '<section class="side-w">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3>',
				'after_title'   => '</h3>',
			)
		);
	}
);
