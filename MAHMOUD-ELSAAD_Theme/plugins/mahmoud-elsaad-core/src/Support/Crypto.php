<?php
/**
 * Credential encryption helpers. Keys never leave the server.
 *
 * @package MahmoudElsaad\Core
 */

namespace MahmoudElsaad\Core\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Crypto {
	/**
	 * Encrypt a secret for storage.
	 *
	 * @param string $plain Plain text.
	 */
	public static function encrypt( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}
		$key = self::key();
		$iv  = random_bytes( 16 );
		$raw = openssl_encrypt( $plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		return base64_encode( $iv . $raw );
	}

	/**
	 * Decrypt a stored secret.
	 *
	 * @param string $stored Stored payload.
	 */
	public static function decrypt( string $stored ): string {
		if ( '' === $stored ) {
			return '';
		}
		$bin = base64_decode( $stored, true );
		if ( ! $bin || strlen( $bin ) < 17 ) {
			return '';
		}
		$iv  = substr( $bin, 0, 16 );
		$raw = substr( $bin, 16 );
		$out = openssl_decrypt( $raw, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv );
		return is_string( $out ) ? $out : '';
	}

	/**
	 * Derive an encryption key from WordPress salts.
	 */
	private static function key(): string {
		$material = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : 'mes' ) . ( defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : 'core' );
		return hash( 'sha256', $material, true );
	}
}
