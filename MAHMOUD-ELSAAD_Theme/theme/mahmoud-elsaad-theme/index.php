<?php
/**
 * Default fallback.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
if ( have_posts() ) {
	echo '<section class="sec"><div class="wrap blog-grid">';
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/cards/article' );
	}
	echo '</div></section>';
} else {
	echo '<section class="sec"><div class="wrap"><p>' . esc_html__( 'Nothing found.', 'mahmoud-elsaad' ) . '</p></div></section>';
}
get_footer();
