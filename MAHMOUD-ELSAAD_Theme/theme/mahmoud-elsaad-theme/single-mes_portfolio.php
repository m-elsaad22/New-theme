<?php
/**
 * Single portfolio project.
 *
 * @package MahmoudElsaad\Theme
 */
get_header();
the_post();
$client  = (string) get_post_meta( get_the_ID(), '_mes_client', true );
$results = (string) get_post_meta( get_the_ID(), '_mes_results', true );
?>
<section class="phero compact">
	<div class="wrap">
		<h1><?php the_title(); ?></h1>
		<?php if ( $client ) : ?><p class="psub"><?php echo esc_html( $client ); ?></p><?php endif; ?>
	</div>
</section>
<section class="sec"><div class="wrap article-layout">
	<div class="article-body prose">
		<?php echo mes_media( get_the_ID(), 'mes-hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php the_content(); ?>
		<?php if ( $results ) : ?><p class="cs-result"><i class="fas fa-circle-check"></i> <?php echo esc_html( $results ); ?></p><?php endif; ?>
	</div>
	<aside class="side-w">
		<?php echo function_exists( 'mes_render_whatsapp_button' ) ? mes_render_whatsapp_button( array( 'placement' => 'portfolio' ) ) : ''; ?>
	</aside>
</div></section>
<?php
get_footer();
