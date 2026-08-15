<?php
/**
 * Comments.
 *
 * @package MahmoudElsaad\Theme
 */
if ( post_password_required() ) {
	return;
}
?>
<section class="sec"><div class="wrap">
<?php
if ( have_comments() ) {
	echo '<h2>' . esc_html__( 'Comments', 'mahmoud-elsaad' ) . '</h2>';
	wp_list_comments( array( 'style' => 'div' ) );
}
comment_form();
?>
</div></section>
