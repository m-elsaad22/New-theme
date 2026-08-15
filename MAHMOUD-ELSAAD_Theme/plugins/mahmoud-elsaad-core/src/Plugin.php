<?php
/**
 * Plugin kernel.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core;

use MahmoudElsaad\Core\Admin\ControlCenter;
use MahmoudElsaad\Core\Admin\Metaboxes;
use MahmoudElsaad\Core\AI\Manager as AIManager;
use MahmoudElsaad\Core\API\Rest;
use MahmoudElsaad\Core\Content\Registrar as ContentRegistrar;
use MahmoudElsaad\Core\Database\Schema;
use MahmoudElsaad\Core\Forms\Engine as FormEngine;
use MahmoudElsaad\Core\Helpers\Contact;
use MahmoudElsaad\Core\LegacyMigration\Migrator;
use MahmoudElsaad\Core\Localization\Language;
use MahmoudElsaad\Core\Performance\Front as PerformanceFront;
use MahmoudElsaad\Core\Relations\ServiceCity;
use MahmoudElsaad\Core\Routing\Rewrites;
use MahmoudElsaad\Core\Security\Hardening;
use MahmoudElsaad\Core\SEO\Meta;
use MahmoudElsaad\Core\SEO\RankMath;
use MahmoudElsaad\Core\SEO\SchemaGraph;
use MahmoudElsaad\Core\Support\Options;
use MahmoudElsaad\Core\Tracking\Clicks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Boot services.
	 */
	public function boot(): void {
		Schema::maybe_upgrade();
		Options::register_defaults();

		ContentRegistrar::init();
		Language::init();
		Rewrites::init();
		ServiceCity::init();
		FormEngine::init();
		Clicks::init();
		Contact::init();
		Meta::init();
		RankMath::init();
		SchemaGraph::init();
		AIManager::init();
		Rest::init();
		ControlCenter::init();
		Metaboxes::init();
		Migrator::init();
		PerformanceFront::init();
		Hardening::init();

		add_filter( 'mes_core_ready', '__return_true' );
		do_action( 'mes_core_booted', $this );
	}
}
