<?php
/**
 * Reviews archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"><div class="wrap"><h1><?php post_type_archive_title(); ?></h1></div></section>
<section class="sec"><div class="wrap review-grid">
<?php
while ( have_posts() ) {
	the_post();
	echo '<article class="rvcard2"><h3>' . esc_html( get_the_title() ) . '</h3><p>' . esc_html( get_the_excerpt() ) . '</p></article>';
}
?>
</div></section>
<?php get_footer();