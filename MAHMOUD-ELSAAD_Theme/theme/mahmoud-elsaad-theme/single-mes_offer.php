<?php
/**
 * Single offer.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
$price = (string) get_post_meta( get_the_ID(), '_mes_price', true );
$old   = (string) get_post_meta( get_the_ID(), '_mes_old_price', true );
?>
<section class="phero compact">
	<div class="wrap">
		<h1><?php the_title(); ?></h1>
		<?php if ( $price ) : ?><p class="psub"><?php echo esc_html( $price ); ?><?php echo $old ? ' — ' . esc_html( $old ) : ''; ?></p><?php endif; ?>
		<div class="hero-ctas">
			<?php
			echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'offer' ) ) : '';
			echo function_exists( 'mes_render_phone_button' ) ? mes_render_phone_button( array( 'placement' => 'offer' ) ) : '';
			?>
		</div>
	</div>
</section>
<section class="sec"><div class="wrap article-layout"><div class="article-body prose"><?php the_content(); ?></div></div></section>
<?php
get_footer();
