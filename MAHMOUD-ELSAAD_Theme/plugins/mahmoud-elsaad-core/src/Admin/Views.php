<?php
/**
 * Control Center views — real data, not placeholders.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Admin;

use MahmoudElsaad\Core\Forms\Repository as FormRepository;
use MahmoudElsaad\Core\Relations\ServiceCity;
use MahmoudElsaad\Core\SEO\RankMath;
use MahmoudElsaad\Core\Support\Logger;
use MahmoudElsaad\Core\Support\Options;
use MahmoudElsaad\Core\Tracking\Clicks;
use MahmoudElsaad\Core\Visual\Preview;
use MahmoudElsaad\Core\Visual\Schema as VisualSchema;
use MahmoudElsaad\Core\Visual\Tree as VisualTree;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Views {
	/**
	 * Dispatch.
	 *
	 * @param string $view View.
	 */
	public static function render( string $view ): void {
		$map = array(
			'services'  => 'edit.php?post_type=service',
			'cities'    => 'edit.php?post_type=mes_city',
			'countries' => 'edit.php?post_type=mes_country',
			'offers'    => 'edit.php?post_type=mes_offer',
			'reviews'   => 'edit.php?post_type=mes_review',
			'portfolio' => 'edit.php?post_type=mes_portfolio',
			'team'      => 'edit.php?post_type=mes_team',
			'partners'  => 'edit.php?post_type=mes_partner',
			'leads'     => 'edit.php?post_type=mes_lead',
		);

		if ( 'dashboard' === $view ) {
			self::dashboard();
			return;
		}
		if ( 'content' === $view ) {
			self::content();
			return;
		}
		if ( 'design' === $view ) {
			self::design();
			return;
		}
		if ( 'settings' === $view ) {
			self::settings();
			return;
		}
		if ( 'analytics' === $view ) {
			self::analytics();
			return;
		}
		if ( 'ai' === $view ) {
			self::ai();
			return;
		}
		if ( 'seo' === $view ) {
			self::seo();
			return;
		}
		if ( 'performance' === $view ) {
			self::performance();
			return;
		}
		if ( 'security' === $view ) {
			self::security();
			return;
		}
		if ( 'migration' === $view ) {
			self::migration();
			return;
		}
		if ( 'landings' === $view ) {
			self::landings();
			return;
		}
		if ( 'forms' === $view ) {
			self::forms();
			return;
		}
		if ( isset( $map[ $view ] ) ) {
			self::cpt( $view, $map[ $view ] );
			return;
		}
		echo '<div class="mes-cc-card"><h1>' . esc_html( ucfirst( $view ) ) . '</h1></div>';
	}

	/**
	 * Dashboard.
	 */
	private static function dashboard(): void {
		$counts = array(
			__( 'Services', 'mahmoud-elsaad-core' )  => (int) ( wp_count_posts( 'service' )->publish ?? 0 ),
			__( 'Cities', 'mahmoud-elsaad-core' )    => (int) ( wp_count_posts( 'mes_city' )->publish ?? 0 ),
			__( 'Leads', 'mahmoud-elsaad-core' )     => (int) ( wp_count_posts( 'mes_lead' )->private ?? 0 ),
			__( 'Projects', 'mahmoud-elsaad-core' )  => (int) ( wp_count_posts( 'mes_portfolio' )->publish ?? 0 ),
		);
		$clicks = Clicks::summary();
		$health = array(
			'PHP ' . PHP_VERSION,
			'WordPress ' . get_bloginfo( 'version' ),
			is_ssl() ? __( 'HTTPS on', 'mahmoud-elsaad-core' ) : __( 'HTTPS off', 'mahmoud-elsaad-core' ),
			get_option( 'permalink_structure' ) ? __( 'Permalinks on', 'mahmoud-elsaad-core' ) : __( 'Permalinks off', 'mahmoud-elsaad-core' ),
			RankMath::active() ? __( 'Rank Math detected', 'mahmoud-elsaad-core' ) : __( 'Internal SEO active', 'mahmoud-elsaad-core' ),
		);

		echo '<div class="mes-cc-grid">';
		foreach ( $counts as $label => $n ) {
			echo '<article class="mes-cc-card"><h2>' . esc_html( $label ) . '</h2><p class="mes-cc-num">' . esc_html( (string) $n ) . '</p></article>';
		}
		echo '<article class="mes-cc-card"><h2>' . esc_html__( 'Calls (30d)', 'mahmoud-elsaad-core' ) . '</h2><p class="mes-cc-num">' . esc_html( (string) $clicks['counts']['call'] ) . '</p></article>';
		echo '<article class="mes-cc-card"><h2>' . esc_html__( 'WhatsApp (30d)', 'mahmoud-elsaad-core' ) . '</h2><p class="mes-cc-num">' . esc_html( (string) $clicks['counts']['whatsapp'] ) . '</p></article>';
		echo '</div>';

		echo '<div class="mes-cc-card"><h2>' . esc_html__( 'Website health', 'mahmoud-elsaad-core' ) . '</h2><ul class="mes-cc-list">';
		foreach ( $health as $item ) {
			echo '<li>' . esc_html( $item ) . '</li>';
		}
		echo '</ul></div>';

		echo '<div class="mes-cc-card"><h2>' . esc_html__( 'Quick actions', 'mahmoud-elsaad-core' ) . '</h2><p class="mes-cc-actions">';
		$actions = array(
			admin_url( 'post-new.php?post_type=service' ) => __( 'Add service', 'mahmoud-elsaad-core' ),
			admin_url( 'post-new.php' ) => __( 'Add post', 'mahmoud-elsaad-core' ),
			admin_url( 'admin.php?page=mes-cc-design' ) => __( 'Theme design', 'mahmoud-elsaad-core' ),
			admin_url( 'admin.php?page=mes-cc-ai' ) => __( 'AI assistant', 'mahmoud-elsaad-core' ),
			admin_url( 'admin.php?page=mes-cc-seo' ) => __( 'SEO', 'mahmoud-elsaad-core' ),
			admin_url( 'upload.php' ) => __( 'Media', 'mahmoud-elsaad-core' ),
			admin_url( 'admin.php?page=mes-cc-settings' ) => __( 'Settings', 'mahmoud-elsaad-core' ),
		);
		foreach ( $actions as $url => $label ) {
			echo '<a class="mes-cc-btn" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a> ';
		}
		echo '</p></div>';

		echo '<div class="mes-cc-card"><h2>' . esc_html__( 'Sample content', 'mahmoud-elsaad-core' ) . '</h2>';
		echo '<p>' . esc_html__( 'Creates generic services, cities are already pre-seeded. No brand name or phone number is hardcoded.', 'mahmoud-elsaad-core' ) . '</p>';
		echo '<button class="mes-cc-btn" type="button" id="mes-seed-demo">' . esc_html__( 'Seed sample content', 'mahmoud-elsaad-core' ) . '</button>';
		echo '<pre id="mes-demo-log"></pre></div>';
	}

	/**
	 * Content overview.
	 */
	private static function content(): void {
		$types = array(
			'service'       => __( 'Services', 'mahmoud-elsaad-core' ),
			'mes_city'      => __( 'Cities', 'mahmoud-elsaad-core' ),
			'mes_country'   => __( 'Countries', 'mahmoud-elsaad-core' ),
			'mes_offer'     => __( 'Offers', 'mahmoud-elsaad-core' ),
			'mes_review'    => __( 'Reviews', 'mahmoud-elsaad-core' ),
			'mes_portfolio' => __( 'Portfolio', 'mahmoud-elsaad-core' ),
			'mes_team'      => __( 'Team', 'mahmoud-elsaad-core' ),
			'mes_partner'   => __( 'Partners', 'mahmoud-elsaad-core' ),
			'mes_faq'       => __( 'FAQs', 'mahmoud-elsaad-core' ),
			'mes_form'      => __( 'Forms', 'mahmoud-elsaad-core' ),
			'mes_lead'      => __( 'Leads', 'mahmoud-elsaad-core' ),
			'post'          => __( 'Posts', 'mahmoud-elsaad-core' ),
			'page'          => __( 'Pages', 'mahmoud-elsaad-core' ),
		);
		echo '<div class="mes-cc-grid">';
		foreach ( $types as $type => $label ) {
			$obj = wp_count_posts( $type );
			$n   = (int) ( $obj->publish ?? 0 ) + (int) ( $obj->private ?? 0 );
			echo '<article class="mes-cc-card"><h2>' . esc_html( $label ) . '</h2><p class="mes-cc-num">' . esc_html( (string) $n ) . '</p>';
			echo '<a class="mes-cc-btn" href="' . esc_url( admin_url( 'edit.php?post_type=' . $type ) ) . '">' . esc_html__( 'Open', 'mahmoud-elsaad-core' ) . '</a></article>';
		}
		echo '</div>';
	}

	/**
	 * CPT shortcut with recent items.
	 *
	 * @param string $view View.
	 * @param string $url Admin URL.
	 */
	private static function cpt( string $view, string $url ): void {
		$type  = sanitize_key( str_replace( 'edit.php?post_type=', '', $url ) );
		$items = get_posts( array( 'post_type' => $type, 'posts_per_page' => 8, 'post_status' => array( 'publish', 'private', 'draft' ) ) );
		echo '<div class="mes-cc-card"><h1>' . esc_html( ucfirst( $view ) ) . '</h1>';
		echo '<p><a class="mes-cc-btn" href="' . esc_url( admin_url( $url ) ) . '">' . esc_html__( 'Open list', 'mahmoud-elsaad-core' ) . '</a> ';
		echo '<a class="mes-cc-btn" href="' . esc_url( admin_url( 'post-new.php?post_type=' . $type ) ) . '">' . esc_html__( 'Add new', 'mahmoud-elsaad-core' ) . '</a></p>';
		echo '<ul class="mes-cc-list">';
		foreach ( $items as $item ) {
			echo '<li><a href="' . esc_url( get_edit_post_link( $item ) ) . '">' . esc_html( get_the_title( $item ) ) . '</a></li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Design tokens + visual tree editor + homepage sections.
	 */
	private static function design(): void {
		$brand    = Options::get( 'mes_brand_settings', array() );
		$design   = Options::get( 'mes_design_system', array() );
		$sections = Options::get( 'mes_homepage_sections', array() );
		$flat     = VisualTree::flatten();
		$groups   = VisualSchema::groups();
		$props    = VisualSchema::properties();

		echo '<div class="mes-cc-card mes-visual-shell"><h1>' . esc_html__( 'Visual control', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Global → Page → Section → Component → Element. Desktop values inherit to tablet and mobile unless overridden. Changing a child does not change the parent.', 'mahmoud-elsaad-core' ) . '</p>';
		echo '<div class="mes-visual" id="mes-visual" data-preview="' . esc_url( Preview::url( '/' ) ) . '">';
		echo '<div class="mes-visual-tree" role="tree">';
		foreach ( $flat as $row ) {
			echo '<button type="button" class="mes-visual-node" role="treeitem" data-node="' . esc_attr( $row['id'] ) . '" data-type="' . esc_attr( $row['type'] ) . '" style="padding-inline-start:' . esc_attr( (string) ( 8 + ( 16 * (int) $row['depth'] ) ) ) . 'px">';
			echo '<span class="mes-visual-type">' . esc_html( $row['type'] ) . '</span> ' . esc_html( $row['label'] );
			echo '</button>';
		}
		echo '</div>';
		echo '<div class="mes-visual-props">';
		echo '<div class="mes-visual-bps">';
		foreach ( array( 'desktop' => __( 'Desktop', 'mahmoud-elsaad-core' ), 'tablet' => __( 'Tablet', 'mahmoud-elsaad-core' ), 'mobile' => __( 'Mobile', 'mahmoud-elsaad-core' ) ) as $bp => $label ) {
			echo '<button type="button" class="mes-cc-btn mes-bp" data-bp="' . esc_attr( $bp ) . '">' . esc_html( $label ) . '</button>';
		}
		echo '</div>';
		echo '<p class="mes-visual-current" id="mes-visual-current"></p>';
		echo '<form id="mes-visual-form" class="mes-cc-form mes-cc-form-wide">';
		foreach ( $groups as $gkey => $glabel ) {
			echo '<fieldset class="mes-visual-group"><legend>' . esc_html( $glabel ) . '</legend>';
			foreach ( $props as $pkey => $meta ) {
				if ( ( $meta['group'] ?? '' ) !== $gkey ) {
					continue;
				}
				echo '<label>' . esc_html( $pkey );
				if ( 'select' === ( $meta['type'] ?? '' ) ) {
					echo '<select name="' . esc_attr( $pkey ) . '"><option value=""></option>';
					foreach ( (array) ( $meta['options'] ?? array() ) as $opt ) {
						echo '<option value="' . esc_attr( (string) $opt ) . '">' . esc_html( (string) $opt ) . '</option>';
					}
					echo '</select>';
				} elseif ( 'color' === ( $meta['type'] ?? '' ) ) {
					echo '<input type="text" name="' . esc_attr( $pkey ) . '" placeholder="#0A1F4E or var(--mes-navy)" />';
				} elseif ( 'toggle' === ( $meta['type'] ?? '' ) ) {
					echo '<select name="' . esc_attr( $pkey ) . '"><option value=""></option><option value="none">' . esc_html__( 'Hide', 'mahmoud-elsaad-core' ) . '</option></select>';
				} else {
					echo '<input type="text" name="' . esc_attr( $pkey ) . '" />';
				}
				echo '</label>';
			}
			echo '</fieldset>';
		}
		echo '<button class="mes-cc-btn" type="button" id="mes-visual-clear">' . esc_html__( 'Clear this breakpoint', 'mahmoud-elsaad-core' ) . '</button> ';
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Publish visual CSS', 'mahmoud-elsaad-core' ) . '</button>';
		echo '</form></div>';
		echo '<div class="mes-visual-preview"><iframe id="mes-visual-frame" title="' . esc_attr__( 'Live homepage preview', 'mahmoud-elsaad-core' ) . '" src="' . esc_url( Preview::url( '/' ) ) . '"></iframe></div>';
		echo '</div></div>';

		echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Design tokens', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<form id="mes-design-form" class="mes-cc-form" data-group="brand_settings">';
		echo '<label>' . esc_html__( 'Primary', 'mahmoud-elsaad-core' ) . '<input type="color" name="primary_color" value="' . esc_attr( $brand['primary_color'] ?? '#0A1F4E' ) . '" /></label>';
		echo '<label>' . esc_html__( 'Secondary', 'mahmoud-elsaad-core' ) . '<input type="color" name="secondary_color" value="' . esc_attr( $brand['secondary_color'] ?? '#2E9DF7' ) . '" /></label>';
		echo '<label>' . esc_html__( 'Accent', 'mahmoud-elsaad-core' ) . '<input type="color" name="accent_color" value="' . esc_attr( $brand['accent_color'] ?? '#C9A227' ) . '" /></label>';
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save colors', 'mahmoud-elsaad-core' ) . '</button>';
		echo '</form></div>';

		echo '<div class="mes-cc-card"><h2>' . esc_html__( 'Theme mode', 'mahmoud-elsaad-core' ) . '</h2>';
		echo '<form id="mes-mode-form" class="mes-cc-form" data-group="design_system">';
		echo '<label>' . esc_html__( 'Mode', 'mahmoud-elsaad-core' ) . '<select name="theme_mode">';
		foreach ( array( 'system', 'light', 'dark' ) as $mode ) {
			echo '<option value="' . esc_attr( $mode ) . '"' . selected( $design['theme_mode'] ?? 'system', $mode, false ) . '>' . esc_html( $mode ) . '</option>';
		}
		echo '</select></label>';
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save', 'mahmoud-elsaad-core' ) . '</button></form></div>';

		echo '<div class="mes-cc-card"><h2>' . esc_html__( 'Homepage sections', 'mahmoud-elsaad-core' ) . '</h2>';
		echo '<form id="mes-home-form" class="mes-cc-form mes-cc-form-wide" data-group="homepage_sections">';
		foreach ( $sections as $key => $cfg ) {
			$on = ! empty( $cfg['enabled'] );
			echo '<label class="mes-cc-check"><input type="checkbox" name="' . esc_attr( $key ) . '[enabled]" value="1"' . checked( $on, true, false ) . ' /> ';
			echo esc_html( $key );
			echo ' <input type="number" name="' . esc_attr( $key ) . '[order]" value="' . esc_attr( (string) ( $cfg['order'] ?? 0 ) ) . '" /></label>';
		}
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save sections', 'mahmoud-elsaad-core' ) . '</button>';
		echo '</form></div>';

		echo '<div class="mes-cc-card"><h2>' . esc_html__( 'Revisions', 'mahmoud-elsaad-core' ) . '</h2><ul class="mes-cc-list">';
		foreach ( Options::revisions( 8 ) as $rev ) {
			echo '<li>' . esc_html( $rev['option_group'] . ' — ' . $rev['created_at'] ) . ' <button type="button" class="mes-cc-btn mes-restore" data-id="' . esc_attr( (string) $rev['id'] ) . '">' . esc_html__( 'Restore', 'mahmoud-elsaad-core' ) . '</button></li>';
		}
		echo '</ul></div>';
	}

	/**
	 * Brand + contact.
	 */
	private static function settings(): void {
		$brand   = Options::get( 'mes_brand_settings', array() );
		$contact = Options::get( 'mes_contact_settings', array() );
		$phones  = $contact['phones'] ?? array();
		$was     = $contact['whatsapps'] ?? array();
		$social  = $contact['social'] ?? array();

		echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Brand', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<form id="mes-brand-form" class="mes-cc-form" data-group="brand_settings">';
		echo '<label>' . esc_html__( 'Brand name', 'mahmoud-elsaad-core' ) . '<input type="text" name="name" value="' . esc_attr( $brand['name'] ?? '' ) . '" /></label>';
		echo '<label>' . esc_html__( 'Tagline', 'mahmoud-elsaad-core' ) . '<input type="text" name="tagline" value="' . esc_attr( $brand['tagline'] ?? '' ) . '" /></label>';
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save brand', 'mahmoud-elsaad-core' ) . '</button></form></div>';

		echo '<div class="mes-cc-card"><h2>' . esc_html__( 'Contact', 'mahmoud-elsaad-core' ) . '</h2>';
		echo '<form id="mes-contact-form" class="mes-cc-form mes-cc-form-wide" data-group="contact_settings">';
		echo '<label>' . esc_html__( 'Primary phone', 'mahmoud-elsaad-core' ) . '<input type="text" name="phones[0][number]" value="' . esc_attr( $phones[0]['number'] ?? '' ) . '" /></label>';
		echo '<input type="hidden" name="phones[0][primary]" value="1" />';
		echo '<label>' . esc_html__( 'Secondary phone', 'mahmoud-elsaad-core' ) . '<input type="text" name="phones[1][number]" value="' . esc_attr( $phones[1]['number'] ?? '' ) . '" /></label>';
		echo '<label>' . esc_html__( 'Primary WhatsApp', 'mahmoud-elsaad-core' ) . '<input type="text" name="whatsapps[0][number]" value="' . esc_attr( $was[0]['number'] ?? '' ) . '" /></label>';
		echo '<input type="hidden" name="whatsapps[0][primary]" value="1" />';
		echo '<label>' . esc_html__( 'Email', 'mahmoud-elsaad-core' ) . '<input type="email" name="email" value="' . esc_attr( $contact['email'] ?? '' ) . '" /></label>';
		echo '<label>' . esc_html__( 'Address', 'mahmoud-elsaad-core' ) . '<input type="text" name="address" value="' . esc_attr( $contact['address'] ?? '' ) . '" /></label>';
		echo '<label>' . esc_html__( 'Map embed', 'mahmoud-elsaad-core' ) . '<textarea name="map_embed" rows="3" placeholder="https://www.google.com/maps/embed?pb=…">' . esc_textarea( $contact['map_embed'] ?? '' ) . '</textarea></label>';
		echo '<p class="description">' . esc_html__( 'Paste a Google Maps or OpenStreetMap embed URL, or an iframe from those hosts. Only the HTTPS src is stored. Other HTML is discarded.', 'mahmoud-elsaad-core' ) . '</p>';
		foreach ( array( 'facebook', 'instagram', 'tiktok', 'youtube', 'linkedin', 'x', 'snapchat', 'telegram' ) as $net ) {
			echo '<label>' . esc_html( ucfirst( $net ) ) . '<input type="url" name="social[' . esc_attr( $net ) . ']" value="' . esc_attr( $social[ $net ] ?? '' ) . '" /></label>';
		}
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save contact', 'mahmoud-elsaad-core' ) . '</button></form></div>';
	}

	/**
	 * Analytics.
	 */
	private static function analytics(): void {
		$data = Clicks::summary();
		echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Call & WhatsApp tracking', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<p>' . esc_html( $data['from'] . ' — ' . $data['to'] ) . '</p>';
		echo '<div class="mes-cc-grid">';
		echo '<article class="mes-cc-card"><h2>' . esc_html__( 'Calls', 'mahmoud-elsaad-core' ) . '</h2><p class="mes-cc-num">' . esc_html( (string) $data['counts']['call'] ) . '</p></article>';
		echo '<article class="mes-cc-card"><h2>' . esc_html__( 'WhatsApp', 'mahmoud-elsaad-core' ) . '</h2><p class="mes-cc-num">' . esc_html( (string) $data['counts']['whatsapp'] ) . '</p></article>';
		echo '</div>';
		echo '<p><a class="mes-cc-btn" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mes_export_clicks' ), 'mes_export_clicks' ) ) . '">' . esc_html__( 'Export CSV', 'mahmoud-elsaad-core' ) . '</a></p>';
		echo '<h2>' . esc_html__( 'Top pages', 'mahmoud-elsaad-core' ) . '</h2><table class="mes-cc-table"><thead><tr><th>' . esc_html__( 'Page', 'mahmoud-elsaad-core' ) . '</th><th>Call</th><th>WhatsApp</th><th>' . esc_html__( 'Total', 'mahmoud-elsaad-core' ) . '</th></tr></thead><tbody>';
		foreach ( $data['top'] as $row ) {
			echo '<tr><td>' . esc_html( $row['title'] ) . '</td><td>' . esc_html( (string) $row['call'] ) . '</td><td>' . esc_html( (string) $row['whatsapp'] ) . '</td><td>' . esc_html( (string) $row['total'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<h2>' . esc_html__( 'By placement', 'mahmoud-elsaad-core' ) . '</h2><table class="mes-cc-table"><thead><tr><th>' . esc_html__( 'Type', 'mahmoud-elsaad-core' ) . '</th><th>' . esc_html__( 'Placement', 'mahmoud-elsaad-core' ) . '</th><th>' . esc_html__( 'Number', 'mahmoud-elsaad-core' ) . '</th><th>' . esc_html__( 'Total', 'mahmoud-elsaad-core' ) . '</th></tr></thead><tbody>';
		foreach ( (array) $data['rows'] as $row ) {
			echo '<tr><td>' . esc_html( $row['event_type'] ) . '</td><td>' . esc_html( $row['placement'] ) . '</td><td>' . esc_html( $row['phone'] ) . '</td><td>' . esc_html( (string) $row['total'] ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	/**
	 * AI.
	 */
	private static function ai(): void {
		$ai = Options::get( 'mes_ai_settings', array() );
		echo '<div class="mes-cc-card"><h1>' . esc_html__( 'AI providers', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<form id="mes-ai-form" class="mes-cc-form" data-group="ai_settings">';
		echo '<label>' . esc_html__( 'Provider', 'mahmoud-elsaad-core' ) . '<select name="provider">';
		foreach ( array( 'openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'gemini' => 'Google Gemini', 'mistral' => 'Mistral', 'openrouter' => 'OpenRouter', 'compatible' => 'OpenAI-compatible' ) as $val => $label ) {
			echo '<option value="' . esc_attr( $val ) . '"' . selected( $ai['provider'] ?? 'openai', $val, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label>';
		echo '<label>' . esc_html__( 'Model', 'mahmoud-elsaad-core' ) . '<input type="text" name="model" value="' . esc_attr( $ai['model'] ?? '' ) . '" /></label>';
		echo '<label class="mes-cc-check"><input type="checkbox" name="enabled" value="1"' . checked( ! empty( $ai['enabled'] ), true, false ) . ' /> ' . esc_html__( 'Enable remote provider', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label>' . esc_html__( 'API key', 'mahmoud-elsaad-core' ) . '<input type="password" name="api_key" autocomplete="new-password" placeholder="' . esc_attr__( 'Leave blank to keep existing key', 'mahmoud-elsaad-core' ) . '" /></label>';
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save', 'mahmoud-elsaad-core' ) . '</button>';
		echo '</form></div>';
	}

	/**
	 * SEO.
	 */
	private static function seo(): void {
		$seo = Options::get( 'mes_seo_settings', array() );
		echo '<div class="mes-cc-card"><h1>' . esc_html__( 'SEO', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<p>' . ( RankMath::active() ? esc_html__( 'Rank Math is active. Rank Math owns Article, BreadcrumbList, Organization, WebSite, canonical, title, and robots. MAHMOUD Core adds LocalBusiness, Service, FAQPage, and Service×City only when those types are missing from Rank Math’s graph.', 'mahmoud-elsaad-core' ) : esc_html__( 'Rank Math is not active. Internal schema/meta are used.', 'mahmoud-elsaad-core' ) ) . '</p>';
		echo '<form id="mes-seo-form" class="mes-cc-form" data-group="seo_settings">';
		echo '<label class="mes-cc-check"><input type="checkbox" name="emit_schema" value="1"' . checked( ! empty( $seo['emit_schema'] ), true, false ) . ' /> ' . esc_html__( 'Emit JSON-LD', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label class="mes-cc-check"><input type="checkbox" name="defer_to_rank_math" value="1"' . checked( ! empty( $seo['defer_to_rank_math'] ), true, false ) . ' /> ' . esc_html__( 'Defer Rank Math–owned types; inject missing Service / LocalBusiness / FAQ only', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label class="mes-cc-check"><input type="checkbox" name="language_prefix" value="1"' . checked( ! empty( $seo['language_prefix'] ), true, false ) . ' /> ' . esc_html__( 'Language URL prefixes', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save', 'mahmoud-elsaad-core' ) . '</button></form></div>';
	}

	/**
	 * Performance.
	 */
	private static function performance(): void {
		$p = Options::get( 'mes_performance_settings', array() );
		echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Performance', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<form id="mes-perf-form" class="mes-cc-form" data-group="performance_settings">';
		echo '<label class="mes-cc-check"><input type="checkbox" name="disable_emojis" value="1"' . checked( ! empty( $p['disable_emojis'] ), true, false ) . ' /> ' . esc_html__( 'Disable emoji scripts', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label class="mes-cc-check"><input type="checkbox" name="disable_embeds" value="1"' . checked( ! empty( $p['disable_embeds'] ), true, false ) . ' /> ' . esc_html__( 'Disable oEmbed scripts', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save', 'mahmoud-elsaad-core' ) . '</button></form></div>';
	}

	/**
	 * Security + logs.
	 */
	private static function security(): void {
		$s = Options::get( 'mes_security_settings', array() );
		echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Security', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<form id="mes-sec-form" class="mes-cc-form" data-group="security_settings">';
		echo '<label class="mes-cc-check"><input type="checkbox" name="hide_versions" value="1"' . checked( ! empty( $s['hide_versions'] ), true, false ) . ' /> ' . esc_html__( 'Hide WordPress version', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label class="mes-cc-check"><input type="checkbox" name="disable_xmlrpc" value="1"' . checked( ! empty( $s['disable_xmlrpc'] ), true, false ) . ' /> ' . esc_html__( 'Disable XML-RPC', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label class="mes-cc-check"><input type="checkbox" name="logging" value="1"' . checked( ! empty( $s['logging'] ), true, false ) . ' /> ' . esc_html__( 'Enable logs', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save', 'mahmoud-elsaad-core' ) . '</button></form></div>';
		echo '<div class="mes-cc-card"><h2>' . esc_html__( 'Recent logs', 'mahmoud-elsaad-core' ) . '</h2><pre>';
		echo esc_html( wp_json_encode( Logger::recent( '', 20 ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
		echo '</pre></div>';
	}

	/**
	 * Migration.
	 */
	private static function migration(): void {
		echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Legacy migration', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Backup runs first. No secrets are copied.', 'mahmoud-elsaad-core' ) . '</p>';
		echo '<button class="mes-cc-btn" type="button" id="mes-run-migration">' . esc_html__( 'Run migration', 'mahmoud-elsaad-core' ) . '</button>';
		echo '<pre id="mes-migration-log"></pre></div>';
	}

	/**
	 * Persistent form builder.
	 */
	private static function forms(): void {
		$forms = FormRepository::all();
		$types = FormRepository::field_types();
		echo '<div class="mes-cc-card mes-fb-shell"><h1>' . esc_html__( 'Form builder', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Forms are stored as mes_form posts. Field order, settings, and submit actions persist in the database.', 'mahmoud-elsaad-core' ) . '</p>';
		echo '<div class="mes-fb" id="mes-fb" data-forms="' . esc_attr( (string) wp_json_encode( $forms ) ) . '" data-types="' . esc_attr( (string) wp_json_encode( $types ) ) . '">';
		echo '<div class="mes-fb-list">';
		echo '<button type="button" class="mes-cc-btn" id="mes-fb-new">' . esc_html__( 'Create form', 'mahmoud-elsaad-core' ) . '</button>';
		echo '<ul id="mes-fb-forms">';
		foreach ( $forms as $form ) {
			echo '<li><button type="button" class="mes-fb-open" data-id="' . esc_attr( (string) $form['id'] ) . '">' . esc_html( $form['title'] ) . '</button></li>';
		}
		echo '</ul></div>';
		echo '<div class="mes-fb-editor" hidden>';
		echo '<div class="mes-fb-toolbar">';
		echo '<input type="text" id="mes-fb-title" />';
		echo '<button type="button" class="mes-cc-btn" id="mes-fb-save">' . esc_html__( 'Save', 'mahmoud-elsaad-core' ) . '</button>';
		echo '<button type="button" class="mes-cc-btn" id="mes-fb-dup">' . esc_html__( 'Duplicate', 'mahmoud-elsaad-core' ) . '</button>';
		echo '<button type="button" class="mes-cc-btn" id="mes-fb-del">' . esc_html__( 'Delete', 'mahmoud-elsaad-core' ) . '</button>';
		echo '</div>';
		echo '<div class="mes-fb-palette">';
		foreach ( $types as $type ) {
			echo '<button type="button" class="mes-fb-add" data-type="' . esc_attr( $type ) . '" draggable="true">' . esc_html( $type ) . '</button>';
		}
		echo '</div>';
		echo '<ol id="mes-fb-fields" class="mes-fb-fields"></ol>';
		echo '<div id="mes-fb-field-settings" class="mes-fb-field-settings"></div>';
		echo '<fieldset class="mes-visual-group"><legend>' . esc_html__( 'Submit actions', 'mahmoud-elsaad-core' ) . '</legend>';
		echo '<label class="mes-cc-check"><input type="checkbox" id="mes-fb-save-lead" checked /> ' . esc_html__( 'Save Lead', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label class="mes-cc-check"><input type="checkbox" id="mes-fb-email" checked /> ' . esc_html__( 'Email notification', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label>' . esc_html__( 'Notify email', 'mahmoud-elsaad-core' ) . '<input type="email" id="mes-fb-notify" /></label>';
		echo '<label class="mes-cc-check"><input type="checkbox" id="mes-fb-webhook" /> ' . esc_html__( 'Webhook', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label>' . esc_html__( 'Webhook URL', 'mahmoud-elsaad-core' ) . '<input type="url" id="mes-fb-webhook-url" /></label>';
		echo '<label class="mes-cc-check"><input type="checkbox" id="mes-fb-wa" /> ' . esc_html__( 'WhatsApp action', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label>' . esc_html__( 'WhatsApp number', 'mahmoud-elsaad-core' ) . '<input type="text" id="mes-fb-wa-number" /></label>';
		echo '<label class="mes-cc-check"><input type="checkbox" id="mes-fb-wa-redirect" /> ' . esc_html__( 'Redirect visitor to WhatsApp', 'mahmoud-elsaad-core' ) . '</label>';
		echo '<label>' . esc_html__( 'Success message', 'mahmoud-elsaad-core' ) . '<textarea id="mes-fb-success"></textarea></label>';
		echo '<label>' . esc_html__( 'Error message', 'mahmoud-elsaad-core' ) . '<textarea id="mes-fb-error"></textarea></label>';
		echo '</fieldset></div></div></div>';
	}

	/**
	 * Service × city overrides.
	 */
	private static function landings(): void {
		$services = get_posts( array( 'post_type' => 'service', 'posts_per_page' => 100 ) );
		$cities   = get_posts( array( 'post_type' => 'mes_city', 'posts_per_page' => 100 ) );
		$rows     = ServiceCity::list( 50 );
		echo '<div class="mes-cc-card"><h1>' . esc_html__( 'Service × City landings', 'mahmoud-elsaad-core' ) . '</h1>';
		echo '<form id="mes-pair-form" class="mes-cc-form mes-cc-form-wide">';
		echo '<label>' . esc_html__( 'Service', 'mahmoud-elsaad-core' ) . '<select name="service_id">';
		foreach ( $services as $svc ) {
			echo '<option value="' . esc_attr( (string) $svc->ID ) . '">' . esc_html( get_the_title( $svc ) ) . '</option>';
		}
		echo '</select></label>';
		echo '<label>' . esc_html__( 'City', 'mahmoud-elsaad-core' ) . '<select name="city_id">';
		foreach ( $cities as $city ) {
			echo '<option value="' . esc_attr( (string) $city->ID ) . '">' . esc_html( get_the_title( $city ) ) . '</option>';
		}
		echo '</select></label>';
		echo '<label>' . esc_html__( 'Title override', 'mahmoud-elsaad-core' ) . '<input type="text" name="title" /></label>';
		echo '<label>' . esc_html__( 'SEO title', 'mahmoud-elsaad-core' ) . '<input type="text" name="seo_title" /></label>';
		echo '<label>' . esc_html__( 'SEO description', 'mahmoud-elsaad-core' ) . '<textarea name="seo_description"></textarea></label>';
		echo '<label>' . esc_html__( 'Phone override', 'mahmoud-elsaad-core' ) . '<input type="text" name="phone" /></label>';
		echo '<label>' . esc_html__( 'WhatsApp override', 'mahmoud-elsaad-core' ) . '<input type="text" name="whatsapp" /></label>';
		echo '<label>' . esc_html__( 'Status', 'mahmoud-elsaad-core' ) . '<select name="status"><option value="publish">publish</option><option value="inherit">inherit</option><option value="draft">draft</option></select></label>';
		echo '<button class="mes-cc-btn" type="submit">' . esc_html__( 'Save landing', 'mahmoud-elsaad-core' ) . '</button></form>';
		echo '<table class="mes-cc-table"><thead><tr><th>ID</th><th>' . esc_html__( 'Service', 'mahmoud-elsaad-core' ) . '</th><th>' . esc_html__( 'City', 'mahmoud-elsaad-core' ) . '</th><th>' . esc_html__( 'Title', 'mahmoud-elsaad-core' ) . '</th><th>' . esc_html__( 'Status', 'mahmoud-elsaad-core' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( (string) $row['id'] ) . '</td><td>' . esc_html( get_the_title( (int) $row['service_id'] ) ) . '</td><td>' . esc_html( get_the_title( (int) $row['city_id'] ) ) . '</td><td>' . esc_html( (string) $row['title'] ) . '</td><td>' . esc_html( (string) $row['status'] ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
