<?php
/**
 * Knowledge hub from blog posts.
 *
 * @package MahmoudElsaad\Theme
 */
$cats = get_categories( array( 'number' => 5, 'hide_empty' => true ) );
$q    = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 6 ) );
if ( ! $q->have_posts() && empty( $cats ) ) {
	return;
}
?>
<section class="sec" id="hub"<?php echo mes_theme_visual_attrs( 'section:home.knowledge' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="shead rv">
			<span class="tag"><?php esc_html_e( 'Knowledge hub', 'mahmoud-elsaad' ); ?></span>
			<h2><?php esc_html_e( 'Guides and articles', 'mahmoud-elsaad' ); ?></h2>
		</div>
		<div class="hub-grid">
			<?php if ( $cats ) : ?>
				<?php foreach ( $cats as $cat ) : ?>
					<article class="hub rv">
						<div class="hub-ic"><i class="fas fa-book"></i></div>
						<h3><?php echo esc_html( $cat->name ); ?></h3>
						<ul>
							<?php
							$posts = get_posts( array( 'cat' => $cat->term_id, 'posts_per_page' => 3 ) );
							foreach ( $posts as $post ) {
								echo '<li><a href="' . esc_url( get_permalink( $post ) ) . '"><i class="fas fa-chevron-left"></i> ' . esc_html( get_the_title( $post ) ) . '</a></li>';
							}
							?>
						</ul>
					</article>
				<?php endforeach; ?>
			<?php else : ?>
				<?php
				while ( $q->have_posts() ) :
					$q->the_post();
					?>
					<article class="hub rv">
						<div class="hub-ic"><i class="fas fa-book"></i></div>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			<?php endif; ?>
		</div>
	</div>
</section>
