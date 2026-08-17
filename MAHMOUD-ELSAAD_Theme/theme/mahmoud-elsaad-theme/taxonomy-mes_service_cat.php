<?php
/**
 * Service category archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"><div class="wrap"><h1><?php single_term_title(); ?></h1><p class="psub"><?php echo wp_kses_post( term_description() ); ?></p></div></section>
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
