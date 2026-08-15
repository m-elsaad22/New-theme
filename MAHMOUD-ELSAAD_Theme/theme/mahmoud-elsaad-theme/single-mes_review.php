<?php
/**
 * Single review.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
$rating = (string) get_post_meta( get_the_ID(), '_mes_rating', true );
$name   = (string) get_post_meta( get_the_ID(), '_mes_customer_name', true );
?>
<section class="phero compact"><div class="wrap"><h1><?php the_title(); ?></h1></div></section>
<section class="sec"><div class="wrap">
	<article class="rcard">
		<?php if ( $rating ) : ?><p class="rstars"><?php echo esc_html( str_repeat( '★', max( 1, min( 5, (int) $rating ) ) ) ); ?></p><?php endif; ?>
		<div class="prose"><?php the_content(); ?></div>
		<?php if ( $name ) : ?><p><b><?php echo esc_html( $name ); ?></b></p><?php endif; ?>
	</article>
</div></section>
<?php
get_footer();
