<?php
/**
 * Case studies from portfolio.
 *
 * @package MahmoudElsaad\Theme
 */
$q = new WP_Query(
	array(
		'post_type'      => 'mes_portfolio',
		'posts_per_page' => 3,
		'meta_key'       => '_mes_featured',
		'meta_value'     => '1',
	)
);
if ( ! $q->have_posts() ) {
	$q = new WP_Query( array( 'post_type' => 'mes_portfolio', 'posts_per_page' => 3 ) );
}
if ( ! $q->have_posts() ) {
	return;
}
?>
<section class="sec" id="cases">
	<div class="wrap">
		<div class="shead rv">
			<span class="tag"><?php esc_html_e( 'Success stories', 'mahmoud-elsaad' ); ?></span>
			<h2><?php esc_html_e( 'Real project outcomes', 'mahmoud-elsaad' ); ?></h2>
		</div>
		<div class="cs-grid">
			<?php
			while ( $q->have_posts() ) :
				$q->the_post();
				$results = (string) get_post_meta( get_the_ID(), '_mes_results', true );
				?>
				<article class="cs rv">
					<div class="cs-top"><i class="fas fa-briefcase"></i><div><b><?php the_title(); ?></b><small><?php echo esc_html( get_the_excerpt() ); ?></small></div></div>
					<div class="cs-body">
						<div class="cs-block"><div class="k"><?php esc_html_e( 'Summary', 'mahmoud-elsaad' ); ?></div><?php the_excerpt(); ?></div>
						<?php if ( $results ) : ?>
							<div class="cs-result"><i class="fas fa-circle-check"></i> <?php echo esc_html( $results ); ?></div>
						<?php endif; ?>
						<a class="btn btn-soft" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View project', 'mahmoud-elsaad' ); ?></a>
					</div>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
