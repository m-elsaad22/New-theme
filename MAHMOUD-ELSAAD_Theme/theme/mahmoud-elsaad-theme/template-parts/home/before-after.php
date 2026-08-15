<?php
/**
 * Before / after.
 *
 * @package MahmoudElsaad\Theme
 */
$items = get_posts( array( 'post_type' => 'mes_portfolio', 'posts_per_page' => 2, 'meta_key' => '_mes_before_id' ) );
if ( ! $items ) {
	return;
}
?>
<section class="sec" id="results" style="background:var(--white)">
	<div class="wrap">
		<div class="shead rv"><span class="tag"><?php esc_html_e( 'Results', 'mahmoud-elsaad' ); ?></span><h2><?php esc_html_e( 'Before and after', 'mahmoud-elsaad' ); ?></h2></div>
		<div class="ba-wrap">
			<?php foreach ( $items as $item ) : ?>
				<div class="ba rv">
					<div class="ba-stage" data-ba>
						<div class="ba-img ba-after"><?php echo wp_get_attachment_image( (int) get_post_meta( $item->ID, '_mes_after_id', true ), 'mes-hero' ); ?></div>
						<div class="ba-img ba-before"><?php echo wp_get_attachment_image( (int) get_post_meta( $item->ID, '_mes_before_id', true ), 'mes-hero' ); ?></div>
						<div class="ba-handle"><span class="grip"><i class="fas fa-arrows-left-right"></i></span></div>
					</div>
					<div class="ba-info"><div><b><?php echo esc_html( get_the_title( $item ) ); ?></b></div></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
