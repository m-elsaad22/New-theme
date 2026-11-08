<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Front-end integration: hooks into KAYAN theme to inject the assigned phone number.
 *
 * Strategy: We discover which KAYAN hooks/filters control the phone/WhatsApp
 * display (by inspecting theme_mods and theme option keys at runtime) and override
 * them with the assigned number for the current post WITHOUT modifying theme files.
 *
 * All filtering is read-only from the KAYAN side – we only filter options/meta that
 * KAYAN theme reads at runtime.
 */
class KayanCC_Frontend {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init(): void {
        // Only run on singular post/page views
        add_action( 'wp', [ $this, 'maybe_override_theme_phone' ] );
    }

    public function maybe_override_theme_phone(): void {
        if ( ! is_singular() ) return;

        $post_id   = get_the_ID();
        $resolved  = KayanCC_Phone_Assignment::get_instance()->resolve_for_post( $post_id );
        if ( ! $resolved || empty( $resolved['number'] ) ) return;

        $number = $resolved['number'];
        if ( $number['status'] !== 'active' ) return;

        // Cache the number for this request so theme filters can use it
        $GLOBALS['kayan_cc_current_number'] = $number;

        // ── Filter common KAYAN theme option names ────────────────────────────
        // We do NOT know exact key names until runtime, so we filter the
        // theme_mods option and any likely phone-related option names.
        add_filter( 'theme_mod_phone_number',     [ $this, 'filter_phone_number' ] );
        add_filter( 'theme_mod_whatsapp_number',  [ $this, 'filter_whatsapp_number' ] );
        add_filter( 'theme_mod_mobile_number',    [ $this, 'filter_phone_number' ] );
        add_filter( 'theme_mod_phone',            [ $this, 'filter_phone_number' ] );
        add_filter( 'theme_mod_whatsapp',         [ $this, 'filter_whatsapp_number' ] );

        // Generic option filter (catches custom options the theme may use)
        add_filter( 'option_kayan_phone',         [ $this, 'filter_phone_number' ] );
        add_filter( 'option_kayan_whatsapp',      [ $this, 'filter_whatsapp_number' ] );

        // Filter Rank Math JSON-LD / Schema if it contains phone (unlikely but safe to hook)
        // We intentionally do NOT touch Rank Math's SEO title output here.
    }

    public function filter_phone_number( $value ) {
        $number = $GLOBALS['kayan_cc_current_number'] ?? null;
        if ( ! $number || ! $number['call_enabled'] ) return $value;
        return $number['phone_number'] ?: $value;
    }

    public function filter_whatsapp_number( $value ) {
        $number = $GLOBALS['kayan_cc_current_number'] ?? null;
        if ( ! $number || ! $number['wa_enabled'] ) return $value;
        return $number['whatsapp_e164'] ?: $value;
    }

    /**
     * Public helper: get the assigned number data for the current post (or null).
     */
    public static function get_current_number(): ?array {
        return $GLOBALS['kayan_cc_current_number'] ?? null;
    }
}
