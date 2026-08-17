<?php
/**
 * Cities archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"<?php echo mes_theme_visual_attrs( 'section:archive-city.hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><div class="wrap"<?php echo mes_theme_visual_attrs( 'component:archive-city.hero.copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><h1<?php echo mes_theme_visual_attrs( 'element:archive-city.hero.title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php post_type_archive_title(); ?></h1></div></section>
<section class="sec"><div class="wrap"><div class="area-cards">
<?php
while ( have_posts() ) {
	the_post();
	echo '<a class="citycard rv" href="' . esc_url( get_permalink() ) . '"><h3>' . esc_html( get_the_title() ) . '</h3><p>' . esc_html( get_the_excerpt() ) . '</p></a>';
}
?>
</div></div></section>
<?php get_footer();