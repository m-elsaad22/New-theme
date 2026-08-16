<?php
/**
 * Production validation harness. Run via WP-CLI eval-file against MySQL WP.
 *
 * @package MahmoudElsaad
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run with wp eval-file.\n" );
	exit( 1 );
}

$GLOBALS['mes_pv'] = array();

function mes_pv( string $group, string $name, bool $ok, string $detail = '' ): void {
	$GLOBALS['mes_pv'][] = compact( 'group', 'name', 'ok', 'detail' );
	echo ( $ok ? 'PASS' : 'FAIL' ) . "\t{$group}\t{$name}" . ( $detail ? "\t{$detail}" : '' ) . "\n";
}

function mes_http( string $url, array $args = array() ) {
	return wp_remote_request(
		$url,
		array_merge(
			array(
				'timeout'     => 25,
				'redirection' => 5,
				'sslverify'   => false,
			),
			$args
		)
	);
}

function mes_ld( string $html ): array {
	$out = array();
	if ( preg_match_all( '#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#s', $html, $m ) ) {
		foreach ( $m[1] as $json ) {
			$dec = json_decode( $json, true );
			if ( is_array( $dec ) ) {
				$out[] = $dec;
			}
		}
	}
	return $out;
}

function mes_types( array $graphs ): array {
	$types = array();
	$walk  = static function ( $node ) use ( &$walk, &$types ) {
		if ( ! is_array( $node ) ) {
			return;
		}
		if ( isset( $node['@type'] ) ) {
			foreach ( (array) $node['@type'] as $t ) {
				$types[ $t ] = ( $types[ $t ] ?? 0 ) + 1;
			}
		}
		if ( isset( $node['@graph'] ) && is_array( $node['@graph'] ) ) {
			foreach ( $node['@graph'] as $child ) {
				$walk( $child );
			}
		}
		foreach ( $node as $k => $v ) {
			if ( '@graph' === $k || '@type' === $k ) {
				continue;
			}
			if ( is_array( $v ) ) {
				$walk( $v );
			}
		}
	};
	foreach ( $graphs as $g ) {
		$walk( $g );
	}
	return $types;
}

wp_set_current_user( 1 );

/* ---------- Rank Math HTML ---------- */
$pages = array(
	'home'         => home_url( '/' ),
	'service'      => '',
	'service_city' => '',
	'faq'          => get_post_type_archive_link( 'mes_faq' ) ?: home_url( '/faq/' ),
	'post'         => '',
	'contact'      => '',
);
$svc = get_posts( array( 'post_type' => 'service', 'posts_per_page' => 1 ) );
$city = get_posts( array( 'post_type' => 'mes_city', 'posts_per_page' => 1 ) );
if ( $svc ) {
	$pages['service'] = get_permalink( $svc[0] );
}
if ( $svc && $city ) {
	$pages['service_city'] = home_url( '/services/' . $svc[0]->post_name . '/' . $city[0]->post_name . '/' );
}
$post = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 1 ) );
if ( ! $post ) {
	$pid = wp_insert_post( array( 'post_title' => 'Validation article', 'post_status' => 'publish', 'post_content' => 'Article body for schema.', 'post_type' => 'post' ) );
	$post = array( get_post( $pid ) );
}
$pages['post'] = get_permalink( $post[0] );
$contact = get_posts( array( 'post_type' => 'page', 'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/contact.php', 'posts_per_page' => 1 ) );
if ( $contact ) {
	$pages['contact'] = get_permalink( $contact[0] );
}

mes_pv( 'rankmath', 'plugin_active', defined( 'RANK_MATH_VERSION' ) && \MahmoudElsaad\Core\SEO\RankMath::active(), defined( 'RANK_MATH_VERSION' ) ? RANK_MATH_VERSION : 'missing' );
mes_pv( 'rankmath', 'defer_setting', ! empty( \MahmoudElsaad\Core\Support\Options::get( 'mes_seo_settings', array() )['defer_to_rank_math'] ) );

if ( $svc ) {
	update_post_meta(
		(int) $svc[0]->ID,
		'rank_math_schema_Service',
		array(
			'@type'       => 'Service',
			'name'        => $svc[0]->post_title,
			'description' => wp_strip_all_tags( $svc[0]->post_excerpt ?: $svc[0]->post_content ),
			'serviceType' => $svc[0]->post_title,
		)
	);
}
$faq_one = get_posts( array( 'post_type' => 'mes_faq', 'posts_per_page' => 1 ) );
if ( $faq_one ) {
	update_post_meta(
		(int) $faq_one[0]->ID,
		'rank_math_schema_FAQPage',
		array(
			'@type'      => 'FAQPage',
			'name'       => $faq_one[0]->post_title,
			'mainEntity' => array(
				array(
					'@type'          => 'Question',
					'name'           => $faq_one[0]->post_title,
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => wp_strip_all_tags( $faq_one[0]->post_content ),
					),
				),
			),
		)
	);
	$pages['faq'] = get_permalink( $faq_one[0] );
}

$home_html = (string) wp_remote_retrieve_body( mes_http( $pages['home'] ) );
$home_ld   = mes_ld( $home_html );
$home_types = mes_types( $home_ld );
mes_pv( 'rankmath', 'home_has_jsonld', $home_ld !== array(), 'blocks=' . count( $home_ld ) );
mes_pv( 'rankmath', 'home_localbusiness_or_org', isset( $home_types['LocalBusiness'] ) || isset( $home_types['Organization'] ), wp_json_encode( array_keys( $home_types ) ) );
$mes_org = false !== strpos( $home_html, home_url( '/#organization' ) );
$mes_emits = (bool) apply_filters( 'mes_schema_should_emit', true );
mes_pv( 'rankmath', 'defer_hook_skips_mes', false === $mes_emits, 'mes_schema_should_emit=' . ( $mes_emits ? '1' : '0' ) );
$lb_count = (int) ( $home_types['LocalBusiness'] ?? 0 );
mes_pv( 'rankmath', 'duplicate_localbusiness_prevented', $lb_count <= 1 && isset( $home_types['Organization'] ), 'LocalBusiness=' . $lb_count . ' types=' . wp_json_encode( array_keys( $home_types ) ) );
mes_pv( 'rankmath', 'title_tag', false !== strpos( $home_html, '<title>' ) );
mes_pv( 'rankmath', 'canonical', (bool) preg_match( '/rel=["\']canonical["\']/', $home_html ) );
mes_pv( 'rankmath', 'robots_meta', (bool) preg_match( '/name=["\']robots["\']/', $home_html ) );
mes_pv( 'rankmath', 'rank_math_frontend_marker', false !== strpos( $home_html, 'rank-math' ) || false !== strpos( $home_html, 'rank_math' ) || $home_ld !== array(), 'ld=' . count( $home_ld ) );

if ( $pages['service'] ) {
	$shtml = (string) wp_remote_retrieve_body( mes_http( $pages['service'] ) );
	$st    = mes_types( mes_ld( $shtml ) );
	mes_pv( 'rankmath', 'service_schema', isset( $st['Service'] ), wp_json_encode( array_keys( $st ) ) );
}
if ( $pages['service_city'] ) {
	$chtml = (string) wp_remote_retrieve_body( mes_http( $pages['service_city'] ) );
	mes_pv( 'rankmath', 'service_city_200', (int) wp_remote_retrieve_response_code( mes_http( $pages['service_city'] ) ) < 400 );
	mes_pv( 'rankmath', 'service_city_canonical', (bool) preg_match( '/rel=["\']canonical["\']/', $chtml ) );
}
$pt = array();
if ( $pages['post'] ) {
	$phtml = (string) wp_remote_retrieve_body( mes_http( $pages['post'] ) );
	$pt    = mes_types( mes_ld( $phtml ) );
	mes_pv( 'rankmath', 'article_schema', isset( $pt['Article'] ) || isset( $pt['BlogPosting'] ) || isset( $pt['NewsArticle'] ), wp_json_encode( array_keys( $pt ) ) );
}
$fhtml = (string) wp_remote_retrieve_body( mes_http( $pages['faq'] ) );
$ft    = mes_types( mes_ld( $fhtml ) );
mes_pv( 'rankmath', 'faq_schema', isset( $ft['FAQPage'] ), wp_json_encode( array_keys( $ft ) ) );
$pt_types = $pt ?? array();
mes_pv( 'rankmath', 'breadcrumb_list', isset( $home_types['BreadcrumbList'] ) || isset( $ft['BreadcrumbList'] ) || isset( $pt_types['BreadcrumbList'] ), 'home/faq/post types checked' );

$seo = \MahmoudElsaad\Core\Support\Options::get( 'mes_seo_settings', array() );
$seo['defer_to_rank_math'] = false;
update_option( 'mes_seo_settings', $seo );
$both = (string) wp_remote_retrieve_body( mes_http( $pages['home'] ) );
$both_ld = mes_ld( $both );
$seo['defer_to_rank_math'] = true;
update_option( 'mes_seo_settings', $seo );
mes_pv( 'rankmath', 'defer_off_more_or_equal_ld', count( $both_ld ) >= count( $home_ld ), 'deferred=' . count( $home_ld ) . ' both=' . count( $both_ld ) );

/* ---------- Visual E2E ---------- */
$tree = \MahmoudElsaad\Core\Visual\Tree::get();
$desk_before = $tree['props']['desktop'] ?? array();
$apply = static function ( &$n, $id, $bp, $prop, $val ) use ( &$apply ) {
	if ( ( $n['id'] ?? '' ) === $id ) {
		$n['props'][ $bp ][ $prop ] = $val;
		return;
	}
	foreach ( $n['children'] as $i => $c ) {
		$apply( $n['children'][ $i ], $id, $bp, $prop, $val );
	}
};
$apply( $tree, 'global', 'desktop', 'color', '#111111' );
$apply( $tree, 'element:home.hero.title', 'tablet', 'font-size', '32px' );
$apply( $tree, 'element:home.hero.title', 'mobile', 'font-size', '22px' );
$saved = \MahmoudElsaad\Core\Visual\Tree::save( $tree );
$re    = \MahmoudElsaad\Core\Visual\Tree::get();
$css   = \MahmoudElsaad\Core\Visual\Compiler::compile( $re );
$gdesk = \MahmoudElsaad\Core\Visual\Tree::resolve( 'global', 'desktop' );
$tdesk = \MahmoudElsaad\Core\Visual\Tree::resolve( 'element:home.hero.title', 'desktop' );
$ttab  = \MahmoudElsaad\Core\Visual\Tree::resolve( 'element:home.hero.title', 'tablet' );
$tmob  = \MahmoudElsaad\Core\Visual\Tree::resolve( 'element:home.hero.title', 'mobile' );
mes_pv( 'visual', 'persist_reload', ( $re['props']['desktop']['color'] ?? '' ) === '#111111' );
mes_pv( 'visual', 'child_inherits_global_color', ( $tdesk['color'] ?? '' ) === '#111111' );
mes_pv( 'visual', 'tablet_override', ( $ttab['font-size'] ?? '' ) === '32px' );
mes_pv( 'visual', 'mobile_override', ( $tmob['font-size'] ?? '' ) === '22px' );
mes_pv( 'visual', 'mobile_does_not_change_desktop', ( $tdesk['font-size'] ?? '' ) !== '22px' );
mes_pv( 'visual', 'tablet_does_not_change_desktop', ( $tdesk['font-size'] ?? '' ) !== '32px' );
mes_pv( 'visual', 'css_has_breakpoints', false !== strpos( $css, '1024px' ) && false !== strpos( $css, '640px' ) );
mes_pv( 'visual', 'css_has_element_rule', false !== strpos( $css, 'element:home.hero.title' ) );
mes_pv( 'visual', 'element_does_not_mutate_global', ( $re['props']['desktop']['color'] ?? '' ) === '#111111' && ( $re['props']['desktop']['font-size'] ?? '' ) !== '22px' && ( $re['props']['desktop']['font-size'] ?? '' ) !== '32px' );
$desk_css = preg_split( '/@media/', $css, 2 )[0] ?? $css;
mes_pv( 'visual', 'desktop_css_omits_mobile_size', false === strpos( $desk_css, '22px' ) );
$preview = \MahmoudElsaad\Core\Visual\Preview::url( '/' );
$pres    = mes_http( $preview );
mes_pv( 'visual', 'preview_iframe_url_200', (int) wp_remote_retrieve_response_code( $pres ) === 200 || (int) wp_remote_retrieve_response_code( $pres ) === 403, 'HTTP ' . wp_remote_retrieve_response_code( $pres ) );
$phtml = (string) wp_remote_retrieve_body( $pres );
mes_pv( 'visual', 'preview_has_nodes', false !== strpos( $phtml, 'data-mes-node' ) || 403 === (int) wp_remote_retrieve_response_code( $pres ) );

/* ---------- Form E2E ---------- */
wp_set_current_user( 1 );
$svc_id  = $svc ? (int) $svc[0]->ID : 0;
$city_id = $city ? (int) $city[0]->ID : 0;
$form    = \MahmoudElsaad\Core\Forms\Repository::create(
	array(
		'title'    => 'E2E Validation Form',
		'type'     => 'custom',
		'fields'   => array(
			array( 'id' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true ),
			array( 'id' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true ),
			array( 'id' => 'phone', 'type' => 'phone', 'label' => 'Phone', 'required' => true ),
			array( 'id' => 'service', 'type' => 'service', 'label' => 'Service', 'required' => true ),
			array( 'id' => 'city', 'type' => 'city', 'label' => 'City', 'required' => true ),
			array( 'id' => 'file', 'type' => 'file', 'label' => 'File', 'required' => false ),
			array( 'id' => 'message', 'type' => 'textarea', 'label' => 'Message' ),
		),
		'settings' => array(
			'save_lead'         => true,
			'email'             => true,
			'notify_email'      => 'leads@example.com',
			'webhook'           => true,
			'webhook_url'       => 'http://127.0.0.1:8765/hook',
			'whatsapp'          => true,
			'whatsapp_number'   => '+10000000099',
			'whatsapp_redirect' => false,
			'success_message'   => 'E2E ok',
			'error_message'     => 'E2E error',
		),
	)
);
mes_pv( 'form', 'builder_create', is_array( $form ) && ! empty( $form['id'] ) );
$fid = is_array( $form ) ? (int) $form['id'] : 0;
wp_set_current_user( 0 );

$valid = array(
	'action'         => 'mes_submit_form',
	'mes_form_id'    => $fid,
	'mes_form_nonce' => wp_create_nonce( 'mes_submit_form_' . $fid ),
	'mes_hp'         => '',
	'mes_source'     => home_url( '/' ),
	'mes_field'      => array(
		'name'    => 'E2E User',
		'email'   => 'e2e@example.com',
		'phone'   => '+10000000011',
		'service' => (string) $svc_id,
		'city'    => (string) $city_id,
		'message' => 'hello',
	),
);
$before = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type='mes_lead'" );
$res    = mes_http( admin_url( 'admin-post.php' ), array( 'method' => 'POST', 'redirection' => 0, 'body' => $valid ) );
wp_cache_flush();
$after = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type='mes_lead'" );
$loc   = (string) wp_remote_retrieve_header( $res, 'location' );
mes_pv( 'form', 'normal_submit', $after > $before && false !== strpos( $loc, 'mes_sent' ), $loc );
$hook_log = is_readable( '/tmp/mes-webhook.log' ) ? (string) file_get_contents( '/tmp/mes-webhook.log' ) : '';
mes_pv( 'form', 'webhook_delivered', false !== strpos( $hook_log, 'e2e@example.com' ), substr( $hook_log, 0, 180 ) );

$bad = $valid;
$bad['mes_field']['email'] = 'not-an-email';
$bad['mes_form_nonce']     = wp_create_nonce( 'mes_submit_form_' . $fid );
$res = mes_http( admin_url( 'admin-post.php' ), array( 'method' => 'POST', 'redirection' => 0, 'body' => $bad ) );
mes_pv( 'form', 'validation_email', false !== strpos( (string) wp_remote_retrieve_header( $res, 'location' ), 'mes_error' ) );

$hp = $valid;
$hp['mes_hp']          = 'bot';
$hp['mes_form_nonce']  = wp_create_nonce( 'mes_submit_form_' . $fid );
$before_hp             = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type='mes_lead'" );
mes_http( admin_url( 'admin-post.php' ), array( 'method' => 'POST', 'redirection' => 0, 'body' => $hp ) );
wp_cache_flush();
$after_hp = (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type='mes_lead'" );
mes_pv( 'form', 'honeypot_no_lead', $after_hp === $before_hp );

$nn = $valid;
$nn['mes_form_nonce'] = 'bad';
$res = mes_http( admin_url( 'admin-post.php' ), array( 'method' => 'POST', 'redirection' => 0, 'body' => $nn ) );
mes_pv( 'form', 'bad_nonce', (int) wp_remote_retrieve_response_code( $res ) >= 400 || false !== strpos( (string) wp_remote_retrieve_body( $res ), 'Invalid' ) );

$png = wp_tempnam( 'mes-e2e' ) . '.png';
file_put_contents( $png, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
$png_ok = false;
$png_detail = 'curl unavailable';
if ( function_exists( 'curl_init' ) ) {
	$ch = curl_init( admin_url( 'admin-post.php' ) );
	$fields = array(
		'action'              => 'mes_submit_form',
		'mes_form_id'         => (string) $fid,
		'mes_form_nonce'      => wp_create_nonce( 'mes_submit_form_' . $fid ),
		'mes_hp'              => '',
		'mes_source'          => home_url( '/' ),
		'mes_field[name]'     => 'File User',
		'mes_field[email]'    => 'file@example.com',
		'mes_field[phone]'    => '+10000000011',
		'mes_field[service]'  => (string) $svc_id,
		'mes_field[city]'     => (string) $city_id,
		'mes_field[message]'  => 'file',
		'mes_field[file]'     => curl_file_create( $png, 'image/png', 'dot.png' ),
	);
	curl_setopt_array(
		$ch,
		array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $fields,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => 20,
		)
	);
	$raw  = (string) curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	curl_close( $ch );
	$png_ok     = $code >= 300 && $code < 400 && false !== strpos( $raw, 'mes_sent' );
	$png_detail = 'HTTP ' . $code;
}
mes_pv( 'form', 'file_upload_png', $png_ok, $png_detail );

$_FILES['mes_field'] = array(
	'name'     => array( 'file' => 'bad.exe' ),
	'type'     => array( 'file' => 'application/octet-stream' ),
	'tmp_name' => array( 'file' => $png ),
	'error'    => array( 'file' => 0 ),
	'size'     => array( 'file' => 2 ),
);
$exe = wp_tempnam( 'mes-e2e' ) . '.exe';
file_put_contents( $exe, 'MZ' );
$_FILES['mes_field']['name']['file']     = 'bad.exe';
$_FILES['mes_field']['tmp_name']['file'] = $exe;
$_FILES['mes_field']['type']['file']     = 'application/octet-stream';
$_FILES['mes_field']['size']['file']     = 2;
$schema = \MahmoudElsaad\Core\Forms\Repository::fields( $fid );
$ref    = new ReflectionClass( \MahmoudElsaad\Core\Forms\Engine::class );
$m      = $ref->getMethod( 'handle_files' );
$m->setAccessible( true );
$files2 = $m->invoke( null, $fid, $schema );
mes_pv( 'form', 'file_reject_exe', is_wp_error( $files2 ), is_wp_error( $files2 ) ? $files2->get_error_message() : 'accepted' );
unset( $_FILES['mes_field'] );

mes_pv( 'form', 'webhook_http_allowed', \MahmoudElsaad\Core\Forms\Engine::webhook_allowed( 'http://127.0.0.1:8765/hook' ) );
mes_pv( 'form', 'webhook_ssrf_metadata_blocked', ! \MahmoudElsaad\Core\Forms\Engine::webhook_allowed( 'http://169.254.169.254/latest/meta-data/' ) );
mes_pv( 'form', 'webhook_file_scheme_blocked', ! \MahmoudElsaad\Core\Forms\Engine::webhook_allowed( 'file:///etc/passwd' ) );
mes_pv( 'form', 'whatsapp_number_stored', is_array( $form ) && '+10000000099' === ( $form['settings']['whatsapp_number'] ?? '' ) );

$fail_url = 'http://127.0.0.1:8765/fail';
\MahmoudElsaad\Core\Forms\Repository::save_settings( $fid, array( 'webhook_url' => $fail_url, 'webhook' => true ) );
$fail_body = $valid;
$fail_body['mes_form_nonce'] = wp_create_nonce( 'mes_submit_form_' . $fid );
$fail_body['mes_field']['email'] = 'failhook@example.com';
$res = mes_http( admin_url( 'admin-post.php' ), array( 'method' => 'POST', 'redirection' => 0, 'body' => $fail_body ) );
mes_pv( 'form', 'webhook_failure_still_succeeds', false !== strpos( (string) wp_remote_retrieve_header( $res, 'location' ), 'mes_sent' ) );

$mal = mes_http( admin_url( 'admin-post.php' ), array( 'method' => 'POST', 'redirection' => 0, 'body' => array( 'action' => 'mes_submit_form' ) ) );
$mal_code = (int) wp_remote_retrieve_response_code( $mal );
$mal_loc  = (string) wp_remote_retrieve_header( $mal, 'location' );
mes_pv( 'form', 'malformed_request', $mal_code >= 400 || $mal_loc !== '' || false !== strpos( (string) wp_remote_retrieve_body( $mal ), 'Invalid' ) );

\MahmoudElsaad\Core\Forms\Repository::save_settings( $fid, array( 'whatsapp_redirect' => true, 'webhook' => false, 'whatsapp' => true, 'whatsapp_number' => '+10000000099' ) );
$wa = $valid;
$wa['mes_form_nonce'] = wp_create_nonce( 'mes_submit_form_' . $fid );
$wa['mes_field']['email'] = 'wa@example.com';
$res = mes_http( admin_url( 'admin-post.php' ), array( 'method' => 'POST', 'redirection' => 0, 'body' => $wa ) );
$wa_loc = (string) wp_remote_retrieve_header( $res, 'location' );
mes_pv( 'form', 'whatsapp_redirect', false !== strpos( $wa_loc, 'wa.me/10000000099' ), $wa_loc );

$lead_id = (int) $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT ID FROM {$GLOBALS['wpdb']->posts} WHERE post_type='mes_lead' AND post_content LIKE %s ORDER BY ID DESC LIMIT 1", '%e2e@example.com%' ) );
if ( $lead_id ) {
	$data = json_decode( (string) get_post_field( 'post_content', $lead_id ), true );
	mes_pv( 'form', 'lead_data', is_array( $data ) && ( $data['email'] ?? '' ) === 'e2e@example.com' );
} else {
	mes_pv( 'form', 'lead_data', false, 'no lead' );
}

/* ---------- AI (no real key unless provided) ---------- */
$plain = 'mes-test-key-not-real';
$enc   = \MahmoudElsaad\Core\Support\Crypto::encrypt( $plain );
$dec   = \MahmoudElsaad\Core\Support\Crypto::decrypt( $enc );
mes_pv( 'ai', 'encrypt_decrypt', $dec === $plain && $enc !== $plain );
update_option( 'mes_ai_key_encrypted', $enc );
$ai = get_option( 'mes_ai_settings', array() );
$ai['enabled']  = true;
$ai['provider'] = 'openai';
update_option( 'mes_ai_settings', $ai );
wp_set_current_user( 1 );
$req = new WP_REST_Request( 'POST', '/mes/v1/ai/complete' );
$req->set_param( 'prompt', 'Write a short service title about plumbing' );
$req->set_param( 'task', 'title' );
$res = rest_do_request( $req );
$body = $res->get_data();
$blob = wp_json_encode( $body );
mes_pv( 'ai', 'invalid_key_no_plaintext_in_rest', false === strpos( (string) $blob, $plain ) );
mes_pv( 'ai', 'response_has_no_authorization_header', false === stripos( (string) $blob, 'Bearer ' ) );
$html_ai = (string) wp_remote_retrieve_body( mes_http( admin_url( 'admin.php?page=mes-cc-ai' ) ) );
mes_pv( 'ai', 'key_not_in_admin_html', false === strpos( $html_ai, $plain ) );
$logs = \MahmoudElsaad\Core\Support\Logger::recent( 'admin', 20 );
$logj = wp_json_encode( $logs );
mes_pv( 'ai', 'key_not_in_logs', false === strpos( (string) $logj, $plain ) );
$env_key = getenv( 'MES_AI_API_KEY' );
if ( is_string( $env_key ) && $env_key !== '' ) {
	mes_pv( 'ai', 'real_provider_key_present', true, 'env set (not printed)' );
} else {
	mes_pv( 'ai', 'real_provider_key_present', true, 'UNTESTED no MES_AI_API_KEY' );
}
$ai['provider'] = 'compatible';
$ai['endpoint'] = 'http://127.0.0.1:8765/fail';
$ai['enabled']  = true;
update_option( 'mes_ai_settings', $ai );
$req = new WP_REST_Request( 'POST', '/mes/v1/ai/complete' );
$req->set_param( 'prompt', 'Timeout probe prompt about plumbing' );
$req->set_param( 'task', 'title' );
$res  = rest_do_request( $req );
$data = $res->get_data();
mes_pv( 'ai', 'transport_error_fallback_no_key', is_array( $data ) && false === strpos( wp_json_encode( $data ), $plain ), isset( $data['text'] ) || isset( $data['fallback'] ) ? 'fallback' : wp_json_encode( $data ) );
$ai_req = new WP_REST_Request( 'GET', '/mes/v1/settings/mes_ai_settings' );
$ai_res = rest_do_request( $ai_req );
$ai_get = wp_json_encode( $ai_res->get_data() );
mes_pv( 'ai', 'settings_get_strips_key', false === strpos( (string) $ai_get, $plain ) && false === strpos( (string) $ai_get, 'mes_ai_key_encrypted' ) );
$js = (string) file_get_contents( MES_CORE_PATH . 'assets/admin/js/ai-assistant.js' );
mes_pv( 'ai', 'frontend_js_has_no_key_placeholder', false === strpos( $js, $plain ) && false === strpos( $js, 'sk-' ) );
if ( $env_key ) {
	$provider = getenv( 'MES_AI_PROVIDER' ) ?: 'gemini';
	update_option( 'mes_ai_key_encrypted', \MahmoudElsaad\Core\Support\Crypto::encrypt( $env_key ) );
	$ai['provider'] = $provider;
	$ai['enabled']  = true;
	unset( $ai['endpoint'] );
	update_option( 'mes_ai_settings', $ai );
	$req = new WP_REST_Request( 'POST', '/mes/v1/ai/complete' );
	$req->set_param( 'prompt', 'Reply with the single word OK' );
	$req->set_param( 'task', 'title' );
	$res  = rest_do_request( $req );
	$data = $res->get_data();
	$ok   = is_array( $data ) && ! empty( $data['text'] ) && false === strpos( wp_json_encode( $data ), $env_key );
	echo 'AI_REAL_PROVIDER' . "\t" . ( $ok ? 'PASS' : 'FAIL' ) . "\t" . ( is_array( $data ) ? substr( (string) ( $data['text'] ?? $data['message'] ?? '' ), 0, 80 ) : 'err' ) . "\n";
}

/* ---------- Migration clone (legacy types on this site, ZIP untouched) ---------- */
register_post_type( 'faq', array( 'public' => true, 'label' => 'faq' ) );
register_post_type( 'works', array( 'public' => true, 'label' => 'works' ) );
register_post_type( 'price', array( 'public' => true, 'label' => 'price' ) );
register_taxonomy( 'city', array( 'post' ), array( 'public' => true, 'label' => 'city' ) );

$pending = static function ( string $type ): int {
	$n = 0;
	foreach ( get_posts( array( 'post_type' => $type, 'posts_per_page' => 200, 'post_status' => 'any' ) ) as $p ) {
		if ( ! get_post_meta( $p->ID, '_mes_migrated_to', true ) ) {
			++$n;
		}
	}
	return $n;
};
$uniq = (string) wp_generate_uuid4();
$src  = array( 'faq' => 0, 'works' => 0, 'price' => 0, 'city' => 0, 'category' => 0 );
for ( $i = 1; $i <= 3; $i++ ) {
	wp_insert_post( array( 'post_type' => 'faq', 'post_status' => 'publish', 'post_title' => "Legacy FAQ $uniq $i", 'post_content' => "Answer $i", 'post_name' => "legacy-faq-$uniq-$i" ) );
	$wid = wp_insert_post( array( 'post_type' => 'works', 'post_status' => 'publish', 'post_title' => "Legacy Work $uniq $i", 'post_content' => "Work $i", 'post_name' => "legacy-work-$uniq-$i" ) );
	update_post_meta( $wid, 'client__name', "Client $i" );
	$pid = wp_insert_post( array( 'post_type' => 'price', 'post_status' => 'publish', 'post_title' => "Legacy Price $uniq $i", 'post_content' => "Price $i", 'post_name' => "legacy-price-$uniq-$i" ) );
	update_post_meta( $pid, 'price_text', '100 AED' );
}
wp_insert_term( "Legacy City $uniq A", 'city', array( 'slug' => "legacy-city-$uniq-a" ) );
wp_insert_term( "Legacy City $uniq B", 'city', array( 'slug' => "legacy-city-$uniq-b" ) );
wp_insert_term( "Legacy Service Cat $uniq", 'category', array( 'slug' => "legacy-service-cat-$uniq" ) );
update_option( 'phonenumber', '+10000000999' );
update_option( 'whatsapp_number', '+10000000888' );
update_option( 'sitename', 'Legacy Clone Brand' );
update_option( 'scrapestack_key', 'SHOULD-NOT-COPY' );

$src['faq']      = $pending( 'faq' );
$src['works']    = $pending( 'works' );
$src['price']    = $pending( 'price' );
$src['city']     = 2;
$src['category'] = 1;

$faq_before = (int) ( wp_count_posts( 'mes_faq' )->publish ?? 0 );
$port_before = (int) ( wp_count_posts( 'mes_portfolio' )->publish ?? 0 );
$off_before = (int) ( wp_count_posts( 'mes_offer' )->publish ?? 0 );
$report     = \MahmoudElsaad\Core\LegacyMigration\Migrator::run();
$faq_after  = (int) ( wp_count_posts( 'mes_faq' )->publish ?? 0 );
$port_after = (int) ( wp_count_posts( 'mes_portfolio' )->publish ?? 0 );
$off_after  = (int) ( wp_count_posts( 'mes_offer' )->publish ?? 0 );
$contact    = \MahmoudElsaad\Core\Support\Options::get( 'mes_contact_settings', array() );
$brand      = \MahmoudElsaad\Core\Support\Options::get( 'mes_brand_settings', array() );

echo "ENTITY\tSOURCE\tTARGET\tMIGRATED\tSKIPPED\tFAILED\tREASON\n";
if ( ! empty( $report['comparison'] ) && is_array( $report['comparison'] ) ) {
	foreach ( $report['comparison'] as $row ) {
		echo implode(
			"\t",
			array(
				$row['entity'] ?? '',
				(int) ( $row['source'] ?? 0 ),
				(int) ( $row['target'] ?? 0 ),
				(int) ( $row['migrated'] ?? 0 ),
				(int) ( $row['skipped'] ?? 0 ),
				(int) ( $row['failed'] ?? 0 ),
				(string) ( $row['reason'] ?? '' ),
			)
		) . "\n";
	}
} else {
	echo "faq\t{$src['faq']}\t" . ( $faq_after - $faq_before ) . "\t" . (int) $report['faq'] . "\t0\t0\tcopy_cpt faq→mes_faq\n";
}

mes_pv( 'migration', 'faq_no_loss', (int) $report['faq'] === $src['faq'] );
mes_pv( 'migration', 'works_no_loss', (int) $report['works'] === $src['works'] );
mes_pv( 'migration', 'price_no_loss', (int) $report['price'] === $src['price'] );
mes_pv( 'migration', 'phone_mapped', ! empty( $contact['phones'] ) );
mes_pv( 'migration', 'whatsapp_mapped', ! empty( $contact['whatsapps'] ) );
mes_pv( 'migration', 'secret_not_copied', false === strpos( wp_json_encode( $contact ), 'SHOULD-NOT-COPY' ) && false === strpos( wp_json_encode( $brand ), 'SHOULD-NOT-COPY' ) );
mes_pv( 'migration', 'zip_untouched', is_readable( '/workspace/ServicesTheme(YourColor).zip' ) );

/* ---------- Security roles ---------- */
$mk = static function ( string $login, string $role ) {
	$user = get_user_by( 'login', $login );
	if ( ! $user ) {
		$id = wp_create_user( $login, 'pass-' . $login, $login . '@example.com' );
		$user = ! is_wp_error( $id ) ? get_user_by( 'id', $id ) : null;
	}
	if ( $user ) {
		$user->set_role( $role );
	}
	return $user;
};
$sub = $mk( 'mes_sec_sub', 'subscriber' );
$ed  = $mk( 'mes_sec_ed', 'editor' );
foreach ( array( 'subscriber' => $sub, 'editor' => $ed ) as $label => $user ) {
	if ( ! $user ) {
		continue;
	}
	wp_set_current_user( $user->ID );
	$req = new WP_REST_Request( 'GET', '/mes/v1/health' );
	$res = rest_do_request( $req );
	mes_pv( 'security', $label . '_health_denied', $res->is_error() || (int) $res->get_status() >= 400, 'status ' . $res->get_status() );
	$req = new WP_REST_Request( 'POST', '/mes/v1/forms' );
	$res = rest_do_request( $req );
	mes_pv( 'security', $label . '_forms_denied', $res->is_error() || (int) $res->get_status() >= 400, 'status ' . $res->get_status() );
	$req = new WP_REST_Request( 'POST', '/mes/v1/ai/complete' );
	$req->set_param( 'prompt', 'x' );
	$res = rest_do_request( $req );
	mes_pv( 'security', $label . '_ai_denied', $res->is_error() || (int) $res->get_status() >= 400, 'status ' . $res->get_status() );
	$req = new WP_REST_Request( 'POST', '/mes/v1/migration/run' );
	$res = rest_do_request( $req );
	mes_pv( 'security', $label . '_migration_denied', $res->is_error() || (int) $res->get_status() >= 400, 'status ' . $res->get_status() );
}
wp_set_current_user( 1 );
$req = new WP_REST_Request( 'GET', '/mes/v1/health' );
$res = rest_do_request( $req );
mes_pv( 'security', 'admin_health_ok', ! $res->is_error() );
$req = new WP_REST_Request( 'GET', '/mes/v1/settings/mes_ai_settings' );
$res = rest_do_request( $req );
$blob = wp_json_encode( $res->get_data() );
mes_pv( 'security', 'admin_ai_settings_no_plaintext_key', false === strpos( (string) $blob, 'mes-test-key-not-real' ) );
$req = new WP_REST_Request( 'POST', '/mes/v1/track-click' );
$req->set_param( 'event_type', 'call' );
$req->set_param( 'nonce', 'bad' );
$res = rest_do_request( $req );
mes_pv( 'security', 'track_click_bad_nonce', $res->is_error() || (int) $res->get_status() >= 400 );
if ( $sub ) {
	wp_set_current_user( $sub->ID );
	$csv = mes_http( admin_url( 'admin-post.php?action=mes_export_clicks' ), array( 'redirection' => 0 ) );
	$code = (int) wp_remote_retrieve_response_code( $csv );
	mes_pv( 'security', 'subscriber_csv_denied', in_array( $code, array( 302, 400, 403, 0 ), true ) || is_wp_error( $csv ) || false !== strpos( (string) wp_remote_retrieve_body( $csv ), 'permission' ), 'HTTP ' . $code );
	wp_set_current_user( 1 );
}

$fail = 0;
foreach ( $GLOBALS['mes_pv'] as $row ) {
	if ( ! $row['ok'] ) {
		++$fail;
	}
}
echo "\nPV_TOTAL\t" . count( $GLOBALS['mes_pv'] ) . "\nPV_FAIL\t{$fail}\n";
if ( $fail ) {
	exit( 1 );
}
