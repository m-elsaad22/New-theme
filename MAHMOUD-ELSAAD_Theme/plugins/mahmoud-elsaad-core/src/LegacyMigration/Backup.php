<?php
/**
 * Settings and content backup before migration.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\LegacyMigration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Backup {
	/**
	 * Create a JSON backup in uploads.
	 *
	 * @return array<string, mixed>
	 */
	public static function create(): array {
		$dir = wp_upload_dir();
		$folder = trailingslashit( $dir['basedir'] ) . 'mes-backups';
		wp_mkdir_p( $folder );

		$payload = array(
			'created_at' => gmdate( 'c' ),
			'options'    => array(
				'sitename'        => get_option( 'sitename' ),
				'phonenumber'     => get_option( 'phonenumber' ),
				'whatsapp_number' => get_option( 'whatsapp_number' ),
				'company__mail'   => get_option( 'company__mail' ),
			),
			'note'       => 'Secrets and third-party API keys are intentionally omitted.',
		);

		$file = $folder . '/backup-' . gmdate( 'Ymd-His' ) . '.json';
		file_put_contents( $file, wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );

		return array(
			'file' => $file,
			'ok'   => is_readable( $file ),
		);
	}
}
