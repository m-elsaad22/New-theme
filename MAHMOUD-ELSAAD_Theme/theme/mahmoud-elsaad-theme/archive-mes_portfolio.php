<?php
/**
 * Portfolio archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"><div class="wrap"><h1><?php post_type_archive_title(); ?></h1></div></section>
<section class="sec"><div class="wrap gal-grid">
<?php
while ( have_posts() ) {
	the_post();
	echo '<a class="gal-item" href="' . esc_url( get_permalink() ) . '">' . mes_media( get_the_ID() ) . '<span>' . esc_html( get_the_title() ) . '</span></a>';
}
?>
</div></section>
<?php get_footer();