/**
 * Legacy mega menu adapter. Not loaded on the default frontend.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\LegacyMigration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MegaMenuAdapter {
	/**
	 * Whether the operator enabled the legacy mega menu.
	 */
	public static function enabled(): bool {
		$nav = get_option( 'mes_nav_settings', array() );
		return ! empty( $nav['legacy_mega_menu'] );
	}
}
