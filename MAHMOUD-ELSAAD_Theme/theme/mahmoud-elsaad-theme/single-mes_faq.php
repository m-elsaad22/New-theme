<?php
/**
 * Single FAQ.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
?>
<section class="phero compact"><div class="wrap"><h1><?php the_title(); ?></h1></div></section>
<section class="sec"><div class="wrap prose"><?php the_content(); ?></div></section>
<?php
get_footer();
