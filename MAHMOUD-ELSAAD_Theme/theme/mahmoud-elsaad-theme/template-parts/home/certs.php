<?php
/**
 * Certifications — generic labels, no demo brand.
 *
 * @package MahmoudElsaad\Theme
 */
$certs = apply_filters(
	'mes_certifications',
	array(
		array( 'icon' => 'fa-file-signature', 'title' => __( 'Trade license', 'mahmoud-elsaad' ), 'text' => __( 'Active commercial license for the listed activities.', 'mahmoud-elsaad' ) ),
		array( 'icon' => 'fa-building-columns', 'title' => __( 'Commercial record', 'mahmoud-elsaad' ), 'text' => __( 'Registered with the relevant authorities.', 'mahmoud-elsaad' ) ),
		array( 'icon' => 'fa-receipt', 'title' => __( 'Tax registration', 'mahmoud-elsaad' ), 'text' => __( 'Official invoices when tax registration is enabled.', 'mahmoud-elsaad' ) ),
		array( 'icon' => 'fa-medal', 'title' => __( 'Quality process', 'mahmoud-elsaad' ), 'text' => __( 'Documented execution and handover steps.', 'mahmoud-elsaad' ) ),
		array( 'icon' => 'fa-helmet-safety', 'title' => __( 'Safety', 'mahmoud-elsaad' ), 'text' => __( 'On-site safety procedures for field teams.', 'mahmoud-elsaad' ) ),
		array( 'icon' => 'fa-shield-halved', 'title' => __( 'Written warranty', 'mahmoud-elsaad' ), 'text' => __( 'Warranty terms are written into the quote before work starts.', 'mahmoud-elsaad' ) ),
	)
);
?>
<section class="sec" id="certs" style="background:var(--white)"<?php echo mes_theme_visual_attrs( 'section:home.certs' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap">
		<div class="shead rv">
			<span class="tag"><?php esc_html_e( 'Trust', 'mahmoud-elsaad' ); ?></span>
			<h2><?php esc_html_e( 'Licenses and certifications', 'mahmoud-elsaad' ); ?></h2>
		</div>
		<div class="cert-grid">
			<?php foreach ( $certs as $cert ) : ?>
				<article class="cert rv">
					<div class="cert-ic"><i class="fas <?php echo esc_attr( $cert['icon'] ); ?>"></i></div>
					<h3><?php echo esc_html( $cert['title'] ); ?></h3>
					<p><?php echo esc_html( $cert['text'] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
