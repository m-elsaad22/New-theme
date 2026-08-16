<?php
/**
 * Header — HTML source of truth.
 *
 * @package MahmoudElsaad\Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?> data-mes-theme="<?php echo esc_attr( mes_theme_mode() ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="<?php echo esc_attr( function_exists( 'mes_get_option' ) ? ( mes_get_option( 'mes_brand_settings', array() )['primary_color'] ?? '#0A1F4E' ) : '#0A1F4E' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="mes-skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'mahmoud-elsaad' ); ?></a>
<div id="loader" aria-hidden="true">
	<div class="ld-logo"><?php echo esc_html( function_exists( 'mes_brand_name' ) ? mes_brand_name() : get_bloginfo( 'name' ) ); ?></div>
	<div class="ld-bar"><i></i></div>
</div>
<header id="hdr">
	<div class="wrap nav">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="logo">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="mark" aria-hidden="true"><i class="fas fa-shield-halved"></i></span>
				<?php echo esc_html( function_exists( 'mes_brand_name' ) ? mes_brand_name() : get_bloginfo( 'name' ) ); ?>
			<?php endif; ?>
		</a>
		<nav class="menu" aria-label="<?php esc_attr_e( 'Primary', 'mahmoud-elsaad' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'items_wrap'     => '%3$s',
					'walker'         => new MES_Nav_Walker(),
					'fallback_cb'    => 'mes_fallback_menu',
					'depth'          => 2,
				)
			);
			?>
		</nav>
		<div class="nav-cta">
			<button class="icon-btn" type="button" data-mes-search aria-label="<?php esc_attr_e( 'Search', 'mahmoud-elsaad' ); ?>"><i class="fas fa-search"></i></button>
			<button class="icon-btn ham" type="button" onclick="toggleMob(true)" aria-label="<?php esc_attr_e( 'Menu', 'mahmoud-elsaad' ); ?>"><span></span><span></span><span></span></button>
		</div>
	</div>
</header>
<nav class="mob" id="mob" aria-label="<?php esc_attr_e( 'Mobile', 'mahmoud-elsaad' ); ?>">
	<button class="mob-close" type="button" onclick="toggleMob(false)" aria-label="<?php esc_attr_e( 'Close', 'mahmoud-elsaad' ); ?>"><i class="fas fa-xmark"></i></button>
	<div class="lang-item">
		<span class="ll"><i class="fas fa-earth-americas"></i> <?php esc_html_e( 'Language', 'mahmoud-elsaad' ); ?></span>
		<select onchange="if(this.value) location.href=this.value" aria-label="<?php esc_attr_e( 'Language', 'mahmoud-elsaad' ); ?>">
			<option value="<?php echo esc_url( function_exists( 'mes_language_url' ) ? mes_language_url( '', 'ar' ) : home_url( '/ar/' ) ); ?>" <?php selected( function_exists( 'mes_html_lang' ) ? mes_html_lang() : 'ar', 'ar' ); ?>><?php esc_html_e( 'Arabic', 'mahmoud-elsaad' ); ?></option>
			<option value="<?php echo esc_url( function_exists( 'mes_language_url' ) ? mes_language_url( '', 'en' ) : home_url( '/en/' ) ); ?>" <?php selected( function_exists( 'mes_html_lang' ) ? mes_html_lang() : 'ar', 'en' ); ?>><?php esc_html_e( 'English', 'mahmoud-elsaad' ); ?></option>
		</select>
	</div>
	<?php
	wp_nav_menu(
		array(
			'theme_location' => 'mobile',
			'container'      => false,
			'items_wrap'     => '%3$s',
			'walker'         => new MES_Nav_Walker(),
			'fallback_cb'    => 'mes_fallback_menu',
		)
	);
	echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'mobile', 'class' => 'btn btn-wa' ) ) : '';
	echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'mobile', 'class' => 'btn btn-call' ) ) : '';
	?>
</nav>
<div id="mes-search" class="mes-search" hidden>
	<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="mes-q"><?php esc_html_e( 'Search', 'mahmoud-elsaad' ); ?></label>
		<input id="mes-q" type="search" name="s" placeholder="<?php esc_attr_e( 'Search services, cities, articles…', 'mahmoud-elsaad' ); ?>" />
		<button class="btn btn-call" type="submit"><?php esc_html_e( 'Search', 'mahmoud-elsaad' ); ?></button>
	</form>
	<div id="mes-search-live" class="mes-search-live" aria-live="polite"></div>
</div>
<main id="content">
