<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shared utility helpers.
 */
class KayanCC_Helpers {

    /**
     * Normalise a UAE (or general Gulf-region) phone number to E.164 format.
     * Returns empty string if normalisation fails.
     */
    public static function normalize_phone( string $raw ): string {
        // Strip everything except digits and leading +
        $cleaned = preg_replace( '/[^\d+]/', '', $raw );
        $cleaned = ltrim( $cleaned, '+' );

        // 00971... → 971...
        if ( strpos( $cleaned, '00971' ) === 0 ) {
            $cleaned = substr( $cleaned, 2 ); // remove leading 00
        }

        // 971XXXXXXXX (10 digits after country code)
        if ( preg_match( '/^971[0-9]{8,9}$/', $cleaned ) ) {
            return '+' . $cleaned;
        }

        // 05XXXXXXXX → +9715XXXXXXXX
        if ( preg_match( '/^0(5[0-9]{8})$/', $cleaned, $m ) ) {
            return '+971' . $m[1];
        }

        // 04XXXXXXX → Dubai landline +97142XXXXXX (etc.)  — just keep as-is with UAE prefix
        if ( preg_match( '/^0([2-9][0-9]{6,8})$/', $cleaned, $m ) ) {
            return '+971' . $m[1];
        }

        return '';
    }

    /**
     * Build a WhatsApp wa.me link from a phone number.
     */
    public static function build_whatsapp_link( string $phone ): string {
        $e164 = self::normalize_phone( $phone );
        if ( ! $e164 ) return '';
        $digits = ltrim( $e164, '+' );
        return 'https://wa.me/' . $digits;
    }

    /**
     * Build a tel: link from a phone number.
     */
    public static function build_tel_link( string $phone ): string {
        $e164 = self::normalize_phone( $phone );
        return $e164 ? 'tel:' . $e164 : '';
    }

    /**
     * Validate that a string looks like a UAE phone number (loose check).
     */
    public static function is_uae_phone( string $raw ): bool {
        return self::normalize_phone( $raw ) !== '';
    }

    /**
     * Sanitise and validate the number before DB write.
     */
    public static function sanitize_phone_input( array $data ): array {
        return [
            'name'            => sanitize_text_field( $data['name'] ?? '' ),
            'phone_number'    => sanitize_text_field( $data['phone_number'] ?? '' ),
            'phone_e164'      => self::normalize_phone( $data['phone_number'] ?? '' ),
            'whatsapp_number' => sanitize_text_field( $data['whatsapp_number'] ?? $data['phone_number'] ?? '' ),
            'whatsapp_e164'   => self::normalize_phone( $data['whatsapp_number'] ?? $data['phone_number'] ?? '' ),
            'country'         => sanitize_text_field( $data['country'] ?? 'الإمارات العربية المتحدة' ),
            'country_code'    => sanitize_text_field( $data['country_code'] ?? '+971' ),
            'call_enabled'    => isset( $data['call_enabled'] ) ? (int) $data['call_enabled'] : 1,
            'wa_enabled'      => isset( $data['wa_enabled'] )   ? (int) $data['wa_enabled']   : 1,
            'call_label'      => sanitize_text_field( $data['call_label'] ?? 'اتصل بنا' ),
            'wa_label'        => sanitize_text_field( $data['wa_label']   ?? 'واتساب' ),
            'status'          => in_array( $data['status'] ?? 'active', ['active','inactive'], true ) ? $data['status'] : 'active',
        ];
    }

    /**
     * Safely unserialize WordPress meta values.
     */
    public static function safe_unserialize( $value ) {
        if ( is_serialized( $value ) ) {
            return @unserialize( $value ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        }
        return $value;
    }

    /**
     * Check whether a value appears to be JSON.
     */
    public static function is_json( string $value ): bool {
        if ( '' === trim( $value ) ) return false;
        json_decode( $value );
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check capabilities.
     */
    public static function current_user_can_manage(): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Pagination helper – returns array with offset, total_pages, etc.
     */
    public static function paginate( int $total, int $per_page, int $current_page ): array {
        $total_pages  = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;
        $current_page = max( 1, min( $current_page, $total_pages ) );
        return [
            'total'        => $total,
            'per_page'     => $per_page,
            'current_page' => $current_page,
            'total_pages'  => $total_pages,
            'offset'       => ( $current_page - 1 ) * $per_page,
        ];
    }
}
