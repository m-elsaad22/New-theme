<?php
/**
 * Template Name: Booking
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
?>
<section class="phero compact"><div class="wrap"><h1><?php the_title(); ?></h1></div></section>
<section class="sec"><div class="wrap">
<?php
if ( class_exists( '\\MahmoudElsaad\\Core\\Forms\\Engine' ) ) {
	echo \MahmoudElsaad\Core\Forms\Engine::render( 'booking', array( 'submit' => __( 'Book now', 'mahmoud-elsaad' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
?>
</div></section>
<?php get_footer();