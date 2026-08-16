<?php
/**
 * WordPress runtime smoke tests. Run: wp eval-file this file.
 *
 * @package MahmoudElsaad
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Must run via WP-CLI eval-file.\n" );
	exit( 1 );
}

$results = array();
$fail    = 0;

function mes_t( string $name, bool $ok, string $detail = '' ): void {
	$GLOBALS['mes_test_results'][] = array( $name, $ok, $detail );
	if ( ! $ok ) {
		$GLOBALS['mes_test_fail'] = (int) ( $GLOBALS['mes_test_fail'] ?? 0 ) + 1;
	}
	echo ( $ok ? 'PASS' : 'FAIL' ) . "\t" . $name . ( $detail ? "\t" . $detail : '' ) . "\n";
}
$GLOBALS['mes_test_results'] = array();
$GLOBALS['mes_test_fail']    = 0;

flush_rewrite_rules( false );

$http = static function ( string $url, array $args = array() ) {
	return wp_remote_request(
		$url,
		array_merge(
			array(
				'timeout'     => 20,
				'redirection' => 5,
				'sslverify'   => false,
			),
			$args
		)
	);
};

global $wpdb;
foreach ( array( 'mes_clicks', 'mes_service_city', 'mes_revisions', 'mes_logs' ) as $t ) {
	$full = $wpdb->prefix . $t;
	$wpdb->last_error = '';
	$wpdb->get_var( "SELECT COUNT(*) FROM {$full}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	mes_t( 'table:' . $t, '' === (string) $wpdb->last_error, $wpdb->last_error );
}

foreach ( array( 'service', 'mes_city', 'mes_country', 'mes_offer', 'mes_review', 'mes_portfolio', 'mes_team', 'mes_partner', 'mes_faq', 'mes_lead', 'mes_form' ) as $cpt ) {
	mes_t( 'cpt:' . $cpt, post_type_exists( $cpt ) );
}
foreach ( array( 'mes_service_cat', 'mes_faq_topic', 'mes_location_type' ) as $tax ) {
	mes_t( 'tax:' . $tax, taxonomy_exists( $tax ) );
}

mes_t( 'plugin_active', function_exists( 'is_plugin_active' ) ? is_plugin_active( 'mahmoud-elsaad-core/mahmoud-elsaad-core.php' ) : class_exists( '\\MahmoudElsaad\\Core\\Plugin' ) );
mes_t( 'theme_active', 'mahmoud-elsaad-theme' === get_template() );

$svc  = get_posts( array( 'post_type' => 'service', 'posts_per_page' => 1 ) );
$city = get_posts( array( 'post_type' => 'mes_city', 'posts_per_page' => 1 ) );
mes_t( 'has_service', ! empty( $svc ) );
mes_t( 'has_city', ! empty( $city ) );
mes_t( 'has_forms', (int) ( wp_count_posts( 'mes_form' )->publish ?? 0 ) >= 1 );

$paths = array(
	'home'     => '/',
	'ar'       => '/ar/',
	'en'       => '/en/',
	'missing'  => '/this-page-does-not-exist-mes-404/',
	'search'   => '/?s=service',
	'services' => '/services/',
	'cities'   => '/cities/',
);
foreach ( $paths as $key => $path ) {
	$res  = $http( home_url( $path ) );
	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = (string) wp_remote_retrieve_body( $res );
	$ok   = ! is_wp_error( $res ) && $code > 0 && false === strpos( $body, 'Fatal error' ) && false === strpos( $body, 'Uncaught' );
	if ( 'missing' === $key ) {
		$ok = 404 === (int) $code;
	} else {
		$ok = $ok && $code >= 200 && $code < 400;
	}
	mes_t( 'http:' . $key, $ok, ( is_wp_error( $res ) ? $res->get_error_message() : 'HTTP ' . $code ) );
}

if ( $svc && $city ) {
	foreach ( array( 'service' => get_permalink( $svc[0] ), 'city' => get_permalink( $city[0] ) ) as $key => $url ) {
		$res  = $http( $url );
		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = (string) wp_remote_retrieve_body( $res );
		mes_t( 'http:' . $key, $code >= 200 && $code < 400 && false === strpos( $body, 'Fatal error' ), 'HTTP ' . $code . ' ' . $url );
	}
	$pair = home_url( '/services/' . $svc[0]->post_name . '/' . $city[0]->post_name . '/' );
	$res  = $http( $pair );
	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = (string) wp_remote_retrieve_body( $res );
	mes_t( 'http:service_city', $code >= 200 && $code < 400 && false === strpos( $body, 'Fatal error' ), 'HTTP ' . $code . ' ' . $pair );
	$res  = $http( home_url( '/ar/services/' . $svc[0]->post_name . '/' . $city[0]->post_name . '/' ) );
	$code = (int) wp_remote_retrieve_response_code( $res );
	mes_t( 'http:service_city_ar', $code >= 200 && $code < 500, 'HTTP ' . $code );
}

$home_body = (string) wp_remote_retrieve_body( $http( home_url( '/' ) ) );
mes_t( 'visual_nodes', false !== strpos( $home_body, 'data-mes-node="' ) );
mes_t( 'schema_jsonld', false !== strpos( $home_body, 'application/ld+json' ) || false !== strpos( $home_body, 'ld+json' ) );
mes_t( 'rank_math_class', class_exists( '\\MahmoudElsaad\\Core\\SEO\\RankMath' ) );
$rm_active = \MahmoudElsaad\Core\SEO\RankMath::active();
$defer_rm  = ! empty( \MahmoudElsaad\Core\Support\Options::get( 'mes_seo_settings', array() )['defer_to_rank_math'] );
$mes_emit  = (bool) apply_filters( 'mes_schema_should_emit', true );
if ( $rm_active && $defer_rm ) {
	mes_t( 'rank_math_compat', false === $mes_emit, 'Rank Math active; MES schema deferred' );
} else {
	mes_t( 'rank_math_compat', true === $mes_emit, $rm_active ? 'Rank Math active but defer off' : 'Rank Math inactive; MES emits' );
}
mes_t( 'tracking_js', false !== strpos( $home_body, 'tracking.js' ) || false !== strpos( $home_body, 'mesFront' ) );

$contact = \MahmoudElsaad\Core\Forms\Engine::render( 'contact' );
mes_t( 'form_render', false !== strpos( $contact, 'mes_submit_form' ) && false !== strpos( $contact, 'mes_form_nonce' ) );

$form = \MahmoudElsaad\Core\Forms\Engine::get_form( 'contact' );
mes_t( 'form_seeded', $form instanceof WP_Post );
if ( $form ) {
	$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='mes_lead'" );
	$city_id = $city ? (int) $city[0]->ID : 0;
	$res    = $http(
		admin_url( 'admin-post.php' ),
		array(
			'method'      => 'POST',
			'redirection' => 0,
			'body'        => array(
				'action'         => 'mes_submit_form',
				'mes_form_id'    => $form->ID,
				'mes_form_nonce' => wp_create_nonce( 'mes_submit_form_' . $form->ID ),
				'mes_hp'         => '',
				'mes_source'     => home_url( '/' ),
				'mes_field'      => array(
					'name'    => 'Runtime Tester',
					'phone'   => '+10000000000',
					'email'   => 'runtime@example.com',
					'city'    => (string) $city_id,
					'message' => 'smoke',
				),
			),
		)
	);
	$code = (int) wp_remote_retrieve_response_code( $res );
	wp_cache_flush();
	clean_post_cache( 0 );
	$after = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='mes_lead'" );
	$loc   = (string) wp_remote_retrieve_header( $res, 'location' );
	mes_t( 'form_submit_http', $code >= 200 && $code < 400 || 302 === $code, 'HTTP ' . $code . ' ' . $loc );
	mes_t( 'lead_created', $after > $before, "before={$before} after={$after}" );
}

$nonce = wp_create_nonce( 'mes_track_click' );
$track = \MahmoudElsaad\Core\Tracking\Clicks::persist(
	array(
		'event_type' => 'call',
		'placement'  => 'runtime',
		'number'     => '+10000000000',
		'nonce'      => $nonce,
	)
);
mes_t( 'click_persist', ! is_wp_error( $track ) );

$ajax = $http(
	admin_url( 'admin-ajax.php' ),
	array(
		'method' => 'POST',
		'body'   => array(
			'action'     => 'mes_track_click',
			'event_type' => 'whatsapp',
			'placement'  => 'runtime_ajax',
			'number'     => '+10000000000',
			'nonce'      => wp_create_nonce( 'mes_track_click' ),
		),
	)
);
$ajax_code = (int) wp_remote_retrieve_response_code( $ajax );
$ajax_body = (string) wp_remote_retrieve_body( $ajax );
mes_t( 'ajax_track_click', $ajax_code >= 200 && $ajax_code < 400 && ( false !== strpos( $ajax_body, 'ok' ) || false !== strpos( $ajax_body, 'success' ) ), 'HTTP ' . $ajax_code );

$beacon = $http(
	rest_url( 'mes/v1/track-click' ),
	array(
		'method'  => 'POST',
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode(
			array(
				'event_type' => 'call',
				'placement'  => 'runtime_beacon',
				'number'     => '+10000000000',
				'nonce'      => wp_create_nonce( 'mes_track_click' ),
			)
		),
	)
);
mes_t( 'rest_track_click', (int) wp_remote_retrieve_response_code( $beacon ) < 500, 'HTTP ' . wp_remote_retrieve_response_code( $beacon ) . ' ' . wp_remote_retrieve_body( $beacon ) );

$tree = \MahmoudElsaad\Core\Visual\Tree::get();
mes_t( 'visual_tree', ! empty( $tree['id'] ) && 'global' === $tree['id'] );
$css = \MahmoudElsaad\Core\Visual\Compiler::compile( $tree );
mes_t( 'visual_compile', false !== strpos( $css, 'mes-visual-ready' ) && false !== strpos( $css, 'prefers-reduced-motion' ) );

$tree['props']['desktop']['color'] = '#112233';
$apply = static function ( &$n ) use ( &$apply ) {
	if ( ( $n['id'] ?? '' ) === 'element:home.hero.title' ) {
		$n['props']['mobile']['font-size'] = '28px';
		return;
	}
	foreach ( $n['children'] as $i => $c ) {
		$apply( $n['children'][ $i ] );
	}
};
$apply( $tree );
$saved = \MahmoudElsaad\Core\Visual\Tree::save( $tree );
$css2  = \MahmoudElsaad\Core\Visual\Compiler::compile( $saved );
mes_t( 'visual_element_override', false !== strpos( $css2, 'element:home.hero.title' ) && false !== strpos( $css2, '28px' ) && false !== strpos( $css2, '640px' ) );
$resolved_global = \MahmoudElsaad\Core\Visual\Tree::resolve( 'global', 'desktop' );
$resolved_hero   = \MahmoudElsaad\Core\Visual\Tree::resolve( 'element:home.hero.title', 'mobile' );
mes_t( 'visual_inherit', ( $resolved_global['color'] ?? '' ) === '#112233' && ( $resolved_hero['color'] ?? '' ) === '#112233' && ( $resolved_hero['font-size'] ?? '' ) === '28px' );
mes_t( 'no_yourcolor_css', false === stripos( $css2, 'yourcolor' ) && false === strpos( $css2, 'YC_' ) );

$created = \MahmoudElsaad\Core\Forms\Repository::create(
	array(
		'title'  => 'Runtime Form',
		'type'   => 'custom',
		'fields' => array(
			array( 'id' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true ),
			array( 'id' => 'email', 'type' => 'email', 'label' => 'Email' ),
		),
	)
);
mes_t( 'form_create', is_array( $created ) && ! empty( $created['id'] ) );
if ( is_array( $created ) ) {
	$dup = \MahmoudElsaad\Core\Forms\Repository::duplicate( (int) $created['id'] );
	mes_t( 'form_duplicate', is_array( $dup ) && ! empty( $dup['id'] ) && (int) $dup['id'] !== (int) $created['id'] );
	$upd = \MahmoudElsaad\Core\Forms\Repository::update( (int) $created['id'], array( 'title' => 'Runtime Form Renamed' ) );
	mes_t( 'form_rename', is_array( $upd ) && 'Runtime Form Renamed' === $upd['title'] );
	if ( is_array( $dup ) ) {
		mes_t( 'form_delete', \MahmoudElsaad\Core\Forms\Repository::delete( (int) $dup['id'] ) );
	}
}

wp_set_current_user( 1 );
foreach ( array(
	'rest_health'  => '/mes/v1/health',
	'rest_visual'  => '/mes/v1/visual/tree',
	'rest_forms'   => '/mes/v1/forms',
	'rest_brand'   => '/mes/v1/settings/brand_settings',
) as $name => $route ) {
	$req = new WP_REST_Request( 'GET', $route );
	$res = rest_do_request( $req );
	mes_t( $name, ! $res->is_error(), $res->is_error() ? $res->as_error()->get_error_message() : 'ok' );
}

$req = new WP_REST_Request( 'POST', '/mes/v1/settings/contact_settings' );
$req->set_body_params(
	array(
		'phones'    => array( array( 'number' => '+10000000001', 'primary' => true ) ),
		'whatsapps' => array( array( 'number' => '+10000000002', 'primary' => true ) ),
	)
);
$res = rest_do_request( $req );
mes_t( 'rest_contact_save', ! $res->is_error(), $res->is_error() ? $res->as_error()->get_error_message() : 'ok' );

$req = new WP_REST_Request( 'POST', '/mes/v1/settings/seo_settings' );
$req->set_body_params( array( 'emit_schema' => true, 'defer_to_rank_math' => true ) );
$res = rest_do_request( $req );
mes_t( 'rest_seo_save', ! $res->is_error() );

$req = new WP_REST_Request( 'POST', '/mes/v1/settings/ai_settings' );
$req->set_body_params( array( 'enabled' => false, 'provider' => 'openai' ) );
$res = rest_do_request( $req );
mes_t( 'rest_ai_save', ! $res->is_error() );

$req = new WP_REST_Request( 'POST', '/mes/v1/settings/homepage_sections' );
$req->set_body_params( array( 'hero' => array( 'enabled' => true, 'order' => 1 ) ) );
$res = rest_do_request( $req );
mes_t( 'rest_homepage_sections', ! $res->is_error() );

$req = new WP_REST_Request( 'POST', '/mes/v1/migration/run' );
$res = rest_do_request( $req );
mes_t( 'rest_migration', ! $res->is_error(), $res->is_error() ? $res->as_error()->get_error_message() : 'ok' );

$sub = get_user_by( 'login', 'mes_runtime_sub' );
if ( ! $sub ) {
	$id  = wp_create_user( 'mes_runtime_sub', 'subpass', 'sub@example.com' );
	$sub = ! is_wp_error( $id ) ? get_user_by( 'id', $id ) : null;
	if ( $sub ) {
		$sub->set_role( 'subscriber' );
	}
}
if ( $sub ) {
	wp_set_current_user( $sub->ID );
	$req = new WP_REST_Request( 'GET', '/mes/v1/health' );
	$res = rest_do_request( $req );
	mes_t( 'rest_denied_subscriber', $res->is_error() || (int) $res->get_status() >= 400, 'status ' . $res->get_status() );
	wp_set_current_user( 1 );
}

foreach ( array( 'dashboard', 'design', 'forms', 'settings', 'seo', 'ai', 'analytics', 'migration' ) as $view ) {
	ob_start();
	try {
		\MahmoudElsaad\Core\Admin\Views::render( $view );
		$html = ob_get_clean();
		$ok   = $html && false === strpos( $html, 'Fatal error' );
		if ( 'design' === $view ) {
			$ok = $ok && false !== strpos( $html, 'mes-visual' ) && false !== strpos( $html, 'mes-visual-frame' );
		}
		if ( 'forms' === $view ) {
			$ok = $ok && false !== strpos( $html, 'mes-fb' );
		}
		mes_t( 'cc:' . $view, $ok );
	} catch ( \Throwable $e ) {
		ob_end_clean();
		mes_t( 'cc:' . $view, false, $e->getMessage() );
	}
}

mes_t( 'csv_export_bound', has_action( 'admin_post_mes_export_clicks' ) > 0 );
mes_t( 'media_helper', function_exists( 'mes_media' ) );
mes_t( 'preview_url', false !== strpos( \MahmoudElsaad\Core\Visual\Preview::url( '/' ), 'mes_preview=1' ) );

$brand_hits = 0;
foreach ( array( MES_CORE_PATH, get_template_directory() ) as $dir ) {
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) );
	foreach ( $it as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}
		$ext = strtolower( $file->getExtension() );
		if ( ! in_array( $ext, array( 'php', 'js', 'css' ), true ) ) {
			continue;
		}
		$src = (string) file_get_contents( $file->getPathname() );
		if ( preg_match( '/YourColor|YC_|\\byc_/i', $src ) ) {
			$brand_hits++;
		}
	}
}
mes_t( 'brand_audit_runtime', 0 === $brand_hits, 'hits=' . $brand_hits );

echo "\nTOTAL\t" . count( $GLOBALS['mes_test_results'] ) . "\nFAIL\t" . (int) $GLOBALS['mes_test_fail'] . "\n";
if ( $GLOBALS['mes_test_fail'] ) {
	exit( 1 );
}
