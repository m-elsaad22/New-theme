<?php
/**
 * FAQ archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"><div class="wrap"><h1><?php post_type_archive_title(); ?></h1></div></section>
<section class="sec"><div class="wrap faq-wrap">
<?php
while ( have_posts() ) {
	the_post();
	echo '<div class="faq-item"><div class="faq-q" onclick="faqT(this)"><span>' . esc_html( get_the_title() ) . '</span><i class="fas fa-chevron-down"></i></div><div class="faq-a">';
	the_content();
	echo '</div></div>';
}
?>
</div></section>
<?php get_footer();