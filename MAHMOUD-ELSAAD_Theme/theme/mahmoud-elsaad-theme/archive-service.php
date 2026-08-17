<?php
/**
 * Service archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"<?php echo mes_theme_visual_attrs( 'section:archive-service.hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><div class="wrap"<?php echo mes_theme_visual_attrs( 'component:archive-service.hero.copy' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><h1<?php echo mes_theme_visual_attrs( 'element:archive-service.hero.title' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php post_type_archive_title(); ?></h1></div></section>
<section class="sec"><div class="wrap services-grid">
<?php
while ( have_posts() ) {
	the_post();
	get_template_part( 'template-parts/cards/service' );
}
?>
</div></section>
<?php
get_footer();
