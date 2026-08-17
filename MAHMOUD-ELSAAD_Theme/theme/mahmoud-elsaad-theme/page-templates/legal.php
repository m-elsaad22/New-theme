<?php
/**
 * Template Name: Legal
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
?>
<section class="phero compact"><div class="wrap"><h1><?php the_title(); ?></h1></div></section>
<section class="sec"><div class="wrap article-layout">
	<div class="article-body prose">
		<?php the_content(); ?>
	</div>
</div></section>
<?php
get_footer();
