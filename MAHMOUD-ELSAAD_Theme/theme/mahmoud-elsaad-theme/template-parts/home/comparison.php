<?php
/**
 * Comparison.
 *
 * @package MahmoudElsaad\Theme
 */
$name = function_exists( 'mes_brand_name' ) ? mes_brand_name() : get_bloginfo( 'name' );
?>
<section class="sec" id="compare" style="background:var(--white)">
	<div class="wrap">
		<div class="shead rv"><span class="tag"><?php esc_html_e( 'Compare', 'mahmoud-elsaad' ); ?></span><h2><?php echo esc_html( $name ); ?></h2></div>
		<div class="cmp rv">
			<div class="cmp-row cmp-head"><div class="ch"><?php esc_html_e( 'Criteria', 'mahmoud-elsaad' ); ?></div><div class="ch rk"><?php echo esc_html( $name ); ?></div><div class="ch"><?php esc_html_e( 'Others', 'mahmoud-elsaad' ); ?></div></div>
			<?php
			$rows = array( __( 'Written warranty', 'mahmoud-elsaad' ), __( '24/7 support', 'mahmoud-elsaad' ), __( 'Transparent pricing', 'mahmoud-elsaad' ) );
			foreach ( $rows as $row ) {
				echo '<div class="cmp-row"><div class="cc lbl">' . esc_html( $row ) . '</div><div class="cc val rk"><i class="fas fa-circle-check"></i></div><div class="cc val ot"><i class="fas fa-circle-xmark"></i></div></div>';
			}
			?>
		</div>
	</div>
</section>
