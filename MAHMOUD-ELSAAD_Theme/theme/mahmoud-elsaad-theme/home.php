<?php
/**
 * Blog index.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
?>
<section class="phero compact">
	<div class="wrap">
		<nav class="crumb"><?php echo wp_kses_post( function_exists( 'mes_breadcrumbs' ) ? mes_breadcrumbs() : '' ); ?></nav>
		<h1><?php esc_html_e( 'Blog', 'mahmoud-elsaad' ); ?></h1>
	</div>
</section>
<section class="sec">
	<div class="wrap">
		<div class="toolbar">
			<form class="search-box" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="search" name="s" placeholder="<?php esc_attr_e( 'Search articles', 'mahmoud-elsaad' ); ?>" />
			</form>
		</div>
		<div class="blog-grid">
			<?php
			if ( have_posts() ) {
				while ( have_posts() ) {
					the_post();
					get_template_part( 'template-parts/cards/article' );
				}
			}
			?>
		</div>
		<div class="pager"><?php the_posts_pagination(); ?></div>
	</div>
</section>
<?php
get_footer();
