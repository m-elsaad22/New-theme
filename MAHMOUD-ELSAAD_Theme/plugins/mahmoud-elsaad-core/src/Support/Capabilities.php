<?php
/**
 * Capabilities.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Capabilities {
	/**
	 * Add plugin capabilities to administrators.
	 */
	public static function install(): void {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}
		foreach ( array( 'mes_manage_platform', 'mes_manage_leads', 'mes_manage_ai', 'mes_run_migration' ) as $cap ) {
			$role->add_cap( $cap );
		}
	}

	/**
	 * Whether the current user can manage the platform.
	 */
	public static function can_manage(): bool {
		return current_user_can( 'mes_manage_platform' ) || current_user_can( 'manage_options' );
	}
}
