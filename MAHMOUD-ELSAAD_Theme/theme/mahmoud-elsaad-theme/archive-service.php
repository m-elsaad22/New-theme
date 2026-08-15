<?php
/**
 * Service archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"><div class="wrap"><h1><?php post_type_archive_title(); ?></h1></div></section>
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
