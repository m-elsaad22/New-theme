<?php
/**
 * Article card.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<article class="bcard rv">
	<a href="<?php the_permalink(); ?>">
		<?php echo mes_media( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<h3><?php the_title(); ?></h3>
		<p><?php echo esc_html( get_the_excerpt() ); ?></p>
	</a>
</article>
