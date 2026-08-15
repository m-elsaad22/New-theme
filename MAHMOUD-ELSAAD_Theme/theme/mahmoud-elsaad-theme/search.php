<?php
/**
 * Search.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"><div class="wrap"><h1><?php echo esc_html( get_search_query() ); ?></h1></div></section>
<section class="sec"><div class="wrap blog-grid">
<?php
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/cards/article' );
	}
} else {
	echo '<p>' . esc_html__( 'No results.', 'mahmoud-elsaad' ) . '</p>';
}
?>
</div></section>
<?php
get_footer();
