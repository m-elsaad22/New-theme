<?php
/**
 * Single post.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
?>
<article class="article-single">
	<section class="phero compact">
		<div class="wrap">
			<nav class="crumb"><?php echo wp_kses_post( function_exists( 'mes_breadcrumbs' ) ? mes_breadcrumbs() : '' ); ?></nav>
			<h1><?php the_title(); ?></h1>
			<div class="phero-meta"><?php echo esc_html( get_the_date() ); ?></div>
		</div>
	</section>
	<section class="sec">
		<div class="wrap article-layout">
			<div class="article-body prose">
				<?php the_content(); ?>
				<div class="article-tags"><?php the_tags( '', ' ' ); ?></div>
			</div>
			<aside class="side-w">
				<?php echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'sidebar' ) ) : ''; ?>
			</aside>
		</div>
	</section>
</article>
<?php
if ( comments_open() || get_comments_number() ) {
	comments_template();
}
get_footer();
