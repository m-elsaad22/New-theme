<?php
/**
 * Generic archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"><div class="wrap"><h1><?php the_archive_title(); ?></h1></div></section>
<section class="sec"><div class="wrap services-grid">
<?php
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		if ( 'service' === get_post_type() ) {
			get_template_part( 'template-parts/cards/service' );
		} else {
			get_template_part( 'template-parts/cards/article' );
		}
	}
}
?>
</div></section>
<?php
get_footer();
