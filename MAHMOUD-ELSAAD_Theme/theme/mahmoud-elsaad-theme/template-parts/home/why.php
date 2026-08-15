<?php
/**
 * Why us.
 *
 * @package MahmoudElsaad\Theme
 */
?>
<section class="sec" id="why">
	<div class="wrap why">
		<div class="why-time rv">
			<div class="inner">
				<h2><?php esc_html_e( 'How we work', 'mahmoud-elsaad' ); ?></h2>
				<p><?php esc_html_e( 'A clear path from request to written warranty.', 'mahmoud-elsaad' ); ?></p>
				<div class="tline">
					<div class="tl"><span class="dot">1</span><div><b><?php esc_html_e( 'Request', 'mahmoud-elsaad' ); ?></b><small><?php esc_html_e( 'Call, WhatsApp, or booking form.', 'mahmoud-elsaad' ); ?></small></div></div>
					<div class="tl"><span class="dot">2</span><div><b><?php esc_html_e( 'Inspect', 'mahmoud-elsaad' ); ?></b><small><?php esc_html_e( 'On-site assessment.', 'mahmoud-elsaad' ); ?></small></div></div>
					<div class="tl"><span class="dot">3</span><div><b><?php esc_html_e( 'Execute', 'mahmoud-elsaad' ); ?></b><small><?php esc_html_e( 'Certified technicians.', 'mahmoud-elsaad' ); ?></small></div></div>
					<div class="tl"><span class="dot">4</span><div><b><?php esc_html_e( 'Guarantee', 'mahmoud-elsaad' ); ?></b><small><?php esc_html_e( 'Written warranty.', 'mahmoud-elsaad' ); ?></small></div></div>
				</div>
			</div>
		</div>
		<div class="why-cards">
			<?php
			$feats = array(
				__( 'Written warranty', 'mahmoud-elsaad' ),
				__( 'Modern equipment', 'mahmoud-elsaad' ),
				__( 'Transparent pricing', 'mahmoud-elsaad' ),
				__( 'Emergency cover', 'mahmoud-elsaad' ),
			);
			foreach ( $feats as $feat ) {
				echo '<article class="feat rv"><div class="fic"><i class="fas fa-circle-check"></i></div><h3>' . esc_html( $feat ) . '</h3></article>';
			}
			?>
		</div>
	</div>
</section>
