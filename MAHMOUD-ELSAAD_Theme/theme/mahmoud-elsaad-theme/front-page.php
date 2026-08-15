<?php
/**
 * Front page — homepage sections from HTML, data from the core plugin.
 *
 * @package MahmoudElsaad\Theme
 */

get_header();

$sections = function_exists( 'mes_get_option' ) ? mes_get_option( 'mes_homepage_sections', array() ) : array();
uasort(
	$sections,
	static function ( $a, $b ) {
		return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 );
	}
);

$map = array(
	'hero'         => 'template-parts/home/hero',
	'trust'        => 'template-parts/home/trust',
	'kpis'         => 'template-parts/home/kpis',
	'finder'       => 'template-parts/home/finder',
	'services'     => 'template-parts/home/services',
	'why'          => 'template-parts/home/why',
	'team'         => 'template-parts/home/team',
	'stats'        => 'template-parts/home/stats',
	'comparison'   => 'template-parts/home/comparison',
	'cities'       => 'template-parts/home/cities',
	'before_after' => 'template-parts/home/before-after',
	'portfolio'    => 'template-parts/home/portfolio',
	'testimonials' => 'template-parts/home/testimonials',
	'partners'     => 'template-parts/home/partners',
	'blog'         => 'template-parts/home/blog',
	'faq'          => 'template-parts/home/faq',
	'cta'          => 'template-parts/home/cta',
	'case_studies' => 'template-parts/home/case-studies',
	'certs'        => 'template-parts/home/certs',
	'pricing'      => 'template-parts/home/pricing',
	'knowledge'    => 'template-parts/home/knowledge',
);

foreach ( $sections as $key => $cfg ) {
	if ( empty( $cfg['enabled'] ) || empty( $map[ $key ] ) ) {
		continue;
	}
	get_template_part( $map[ $key ] );
}

get_footer();
