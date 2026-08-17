<?php
/**
 * Offers archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"><div class="wrap"><h1><?php post_type_archive_title(); ?></h1></div></section>
<section class="sec"><div class="wrap offer-grid">
<?php
while ( have_posts() ) {
	the_post();
	$price = get_post_meta( get_the_ID(), '_mes_price', true );
	$disc  = get_post_meta( get_the_ID(), '_mes_discount', true );
	echo '<article class="offer rv"><h3>' . esc_html( get_the_title() ) . '</h3>';
	if ( $disc ) {
		echo '<p class="disc">' . esc_html( $disc ) . '%</p>';
	}
	if ( $price ) {
		echo '<p>' . esc_html( $price ) . '</p>';
	}
	echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'offer' ) ) : '';
	echo '</article>';
}
?>
</div></section>
<?php get_footer();