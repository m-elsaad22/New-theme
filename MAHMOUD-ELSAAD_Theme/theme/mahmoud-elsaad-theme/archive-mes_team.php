<?php
/**
 * Team archive.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact"><div class="wrap"><h1><?php post_type_archive_title(); ?></h1></div></section>
<section class="sec"><div class="wrap team-grid">
<?php
while ( have_posts() ) {
	the_post();
	$role = (string) get_post_meta( get_the_ID(), '_mes_position', true );
	echo '<article class="tcard rv"><div class="tav">' . esc_html( substr( get_the_title(), 0, 2 ) ) . '</div><h3>' . esc_html( get_the_title() ) . '</h3>';
	if ( $role ) {
		echo '<div class="role">' . esc_html( $role ) . '</div>';
	}
	echo '<p class="spec">' . esc_html( get_the_excerpt() ) . '</p></article>';
}
?>
</div></section>
<?php
get_footer();
