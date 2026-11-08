<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Scans WordPress content (posts, meta, options, widgets, menus, blocks)
 * to discover UAE phone numbers and WhatsApp/tel links.
 *
 * Runs on demand only – never on front-end page loads.
 */
class KayanCC_Phone_Scanner {

    // Batch size to avoid memory exhaustion
    const BATCH_SIZE = 50;

    // Patterns for UAE phone number detection
    // Deliberately NOT matching < 8 digits to avoid prices/dates
    private static $phone_patterns = [
        // +971 formats
        '/(?<!\d)(\+971|00971|971)[\s\-.]?([0-9]{1,2})[\s\-.]?([0-9]{3,4})[\s\-.]?([0-9]{4})(?!\d)/u',
        // Local 05X format
        '/(?<!\d)(0[2-9])[\s\-.]?([0-9]{3})[\s\-.]?([0-9]{4})(?!\d)/u',
    ];

    // Patterns for WhatsApp / tel links
    private static $link_patterns = [
        'tel_link'      => '/tel:(\+?[0-9\s\-\.]+)/i',
        'wa_me'         => '/wa\.me\/(\+?[0-9]+)/i',
        'api_whatsapp'  => '/api\.whatsapp\.com\/send[?&]phone=(\+?[0-9]+)/i',
        'whatsapp_send' => '/whatsapp\.com\/send[?&]phone=(\+?[0-9]+)/i',
    ];

    // Strings/patterns that indicate the digits are NOT a phone number
    private static $exclusion_patterns = [
        // Prices: digits preceded/followed by currency indicators
        '/(?:AED|درهم|USD|\$|€)\s*[\d,\.]+/u',
        '/[\d,\.]+\s*(?:AED|درهم)/u',
        // Typical coordinate formats
        '/\d{1,3}\.\d{4,}/u',
        // Year ranges  2000-2030
        '/\b(19|20)\d{2}\b/u',
        // License plate / DLD ref (alphanumeric)
        '/[A-Z]{1,3}[\-\s]?\d{4,}/u',
    ];

    /** @var string Current scan batch ID */
    private $batch_id;

    public function __construct() {
        $this->batch_id = wp_generate_uuid4();
    }

    // ─── Public API ──────────────────────────────────────────────────────────

    /**
     * Start a fresh scan (clears old results first).
     * Returns scan batch ID.
     */
    public function start_scan(): string {
        global $wpdb;
        $table = KayanCC_Database::get_scan_table();
        $wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $this->batch_id = wp_generate_uuid4();
        update_option( 'kayan_cc_scan_batch_id',     $this->batch_id );
        update_option( 'kayan_cc_scan_status',        'running' );
        update_option( 'kayan_cc_scan_posts_offset',  0 );
        update_option( 'kayan_cc_scan_phase',         'posts' );
        return $this->batch_id;
    }

    /**
     * Process one batch of posts. Returns true when done.
     */
    public function process_batch(): bool {
        $phase  = get_option( 'kayan_cc_scan_phase', 'posts' );
        $offset = (int) get_option( 'kayan_cc_scan_posts_offset', 0 );

        switch ( $phase ) {
            case 'posts':
                $done = $this->scan_posts_batch( $offset );
                if ( $done ) {
                    update_option( 'kayan_cc_scan_phase', 'options' );
                    update_option( 'kayan_cc_scan_posts_offset', 0 );
                }
                break;

            case 'options':
                $this->scan_options();
                update_option( 'kayan_cc_scan_phase', 'widgets' );
                break;

            case 'widgets':
                $this->scan_widgets();
                update_option( 'kayan_cc_scan_phase', 'menus' );
                break;

            case 'menus':
                $this->scan_menus();
                update_option( 'kayan_cc_scan_phase', 'done' );
                break;

            case 'done':
                $this->mark_duplicates();
                update_option( 'kayan_cc_scan_status', 'complete' );
                return true;
        }
        return false;
    }

    /**
     * Get current scan progress info.
     */
    public function get_status(): array {
        return [
            'status'   => get_option( 'kayan_cc_scan_status', 'idle' ),
            'phase'    => get_option( 'kayan_cc_scan_phase', '' ),
            'batch_id' => get_option( 'kayan_cc_scan_batch_id', '' ),
        ];
    }

    /**
     * Retrieve all scan results (paginated).
     */
    public function get_results( int $page = 1, int $per_page = 50 ): array {
        global $wpdb;
        $table  = KayanCC_Database::get_scan_table();
        $total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore
        $paging = KayanCC_Helpers::paginate( $total, $per_page, $page );
        $rows   = $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY result_type, normalized LIMIT %d OFFSET %d",
                $paging['per_page'],
                $paging['offset']
            ),
            ARRAY_A
        );
        return [ 'paging' => $paging, 'results' => $rows ];
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Scan a batch of posts and their post_meta.
     * Returns true when all posts have been processed.
     */
    private function scan_posts_batch( int $offset ): bool {
        global $wpdb;

        $post_types = get_post_types( [ 'public' => true ] );
        $post_types = array_merge( $post_types, get_post_types( [ 'public' => false, 'show_ui' => true ] ) );
        $post_types = array_unique( $post_types );

        $placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
        $args         = array_merge(
            $post_types,
            [ self::BATCH_SIZE, $offset ]
        );

        $posts = $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare(
                "SELECT ID, post_title, post_content, post_type FROM {$wpdb->posts}
                 WHERE post_type IN ({$placeholders})
                   AND post_status IN ('publish','draft','private')
                 ORDER BY ID
                 LIMIT %d OFFSET %d",
                $args
            ),
            ARRAY_A
        );

        if ( empty( $posts ) ) {
            return true; // done
        }

        foreach ( $posts as $post ) {
            $this->extract_from_text( $post['post_content'], 'post_content', (int) $post['ID'] );
            $this->extract_from_text( $post['post_title'],   'post_title',   (int) $post['ID'] );
            $this->scan_post_meta( (int) $post['ID'] );
        }

        update_option( 'kayan_cc_scan_posts_offset', $offset + self::BATCH_SIZE );
        return false;
    }

    private function scan_post_meta( int $post_id ): void {
        global $wpdb;
        $rows = $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
                $post_id
            ),
            ARRAY_A
        );

        foreach ( $rows as $row ) {
            $value = KayanCC_Helpers::safe_unserialize( $row['meta_value'] );
            if ( is_string( $value ) ) {
                $this->extract_from_text( $value, 'postmeta:' . $row['meta_key'], $post_id );
            } elseif ( is_array( $value ) ) {
                $this->extract_from_text( wp_json_encode( $value ), 'postmeta:' . $row['meta_key'], $post_id );
            }
        }
    }

    private function scan_options(): void {
        $option_names = [
            'blogname', 'blogdescription',
            // KAYAN theme options (common names – discovered dynamically)
        ];

        // Pull all theme mod / KAYAN options generically
        global $wpdb;
        $rows = $wpdb->get_results( // phpcs:ignore
            "SELECT option_name, option_value FROM {$wpdb->options}
             WHERE option_name LIKE '%phone%'
                OR option_name LIKE '%whatsapp%'
                OR option_name LIKE '%mobile%'
                OR option_name LIKE '%tel%'
                OR option_name LIKE '%contact%'
                OR option_name LIKE 'theme_mods_%'
                OR option_name LIKE 'kayan%'
             LIMIT 500",
            ARRAY_A
        );

        foreach ( $rows as $row ) {
            $value = KayanCC_Helpers::safe_unserialize( $row['option_value'] );
            $text  = is_string( $value ) ? $value : wp_json_encode( $value );
            if ( $text ) {
                $this->extract_from_text( $text, 'option:' . $row['option_name'], 0 );
            }
        }
    }

    private function scan_widgets(): void {
        global $wpdb;
        $rows = $wpdb->get_results( // phpcs:ignore
            "SELECT option_name, option_value FROM {$wpdb->options}
             WHERE option_name LIKE 'widget_%'",
            ARRAY_A
        );
        foreach ( $rows as $row ) {
            $value = KayanCC_Helpers::safe_unserialize( $row['option_value'] );
            $text  = is_string( $value ) ? $value : wp_json_encode( $value );
            if ( $text ) {
                $this->extract_from_text( $text, 'widget:' . $row['option_name'], 0 );
            }
        }
    }

    private function scan_menus(): void {
        $menus = wp_get_nav_menus();
        foreach ( $menus as $menu ) {
            $items = wp_get_nav_menu_items( $menu->term_id );
            if ( ! $items ) continue;
            foreach ( $items as $item ) {
                $this->extract_from_text( $item->url,   'menu_item_url',   $item->ID );
                $this->extract_from_text( $item->title, 'menu_item_title', $item->ID );
            }
        }
    }

    /**
     * Extract phone numbers and links from a block of text.
     */
    private function extract_from_text( string $text, string $source_field, int $source_id ): void {
        if ( empty( $text ) ) return;

        // ── WhatsApp / tel links first (highest confidence) ──────────────────
        foreach ( self::$link_patterns as $type => $pattern ) {
            if ( preg_match_all( $pattern, $text, $matches, PREG_SET_ORDER ) ) {
                foreach ( $matches as $match ) {
                    $raw  = $match[0];
                    $num  = $match[1] ?? '';
                    $norm = KayanCC_Helpers::normalize_phone( $num );
                    $result_type = strpos( $type, 'wa' ) !== false || strpos( $type, 'whatsapp' ) !== false
                        ? 'whatsapp_link' : 'call_link';
                    $this->store_result( $raw, $norm ?: $num, $result_type, $source_field, $source_id );
                }
            }
        }

        // ── Plain phone numbers ───────────────────────────────────────────────
        foreach ( self::$phone_patterns as $pattern ) {
            if ( preg_match_all( $pattern, $text, $matches, PREG_SET_ORDER ) ) {
                foreach ( $matches as $match ) {
                    $raw = $match[0];

                    // Skip if this match is part of an exclusion
                    if ( $this->is_excluded( $raw, $text ) ) continue;

                    $norm = KayanCC_Helpers::normalize_phone( $raw );
                    if ( ! $norm ) {
                        $result_type = 'phone_probable';
                    } else {
                        $result_type = strpos( $norm, '+971' ) === 0 ? 'phone_confirmed' : 'phone_probable';
                    }
                    $this->store_result( $raw, $norm ?: $raw, $result_type, $source_field, $source_id );
                }
            }
        }
    }

    private function is_excluded( string $match, string $context ): bool {
        foreach ( self::$exclusion_patterns as $pattern ) {
            if ( preg_match( $pattern, $match ) ) return true;
        }

        // Avoid matching 4-digit years as phone fragments
        if ( preg_match( '/^(19|20)\d{2}$/', trim( $match ) ) ) return true;

        // Avoid pure decimal numbers (prices like 1500.00)
        if ( preg_match( '/^\d+\.\d+$/', trim( $match ) ) ) return true;

        return false;
    }

    private function store_result( string $raw, string $normalized, string $result_type, string $source_field, int $source_id ): void {
        global $wpdb;
        $table = KayanCC_Database::get_scan_table();

        // Avoid duplicate rows for exact same raw+source combination in this batch
        $exists = $wpdb->get_var( // phpcs:ignore
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE raw_value = %s AND source_field = %s AND source_id = %d AND scan_batch = %s LIMIT 1",
                $raw, $source_field, $source_id, $this->batch_id
            )
        );
        if ( $exists ) return;

        $wpdb->insert( // phpcs:ignore
            $table,
            [
                'raw_value'   => substr( $raw, 0, 500 ),
                'normalized'  => substr( $normalized, 0, 100 ),
                'result_type' => $result_type,
                'source_type' => $this->detect_source_type( $source_field ),
                'source_id'   => $source_id,
                'source_field'=> substr( $source_field, 0, 255 ),
                'scan_batch'  => $this->batch_id,
            ],
            [ '%s','%s','%s','%s','%d','%s','%s' ]
        );
    }

    private function detect_source_type( string $field ): string {
        if ( strpos( $field, 'post_content' ) !== false ) return 'post_content';
        if ( strpos( $field, 'post_title' )   !== false ) return 'post_title';
        if ( strpos( $field, 'postmeta' )     !== false ) return 'post_meta';
        if ( strpos( $field, 'option' )        !== false ) return 'option';
        if ( strpos( $field, 'widget' )        !== false ) return 'widget';
        if ( strpos( $field, 'menu' )          !== false ) return 'menu';
        return 'other';
    }

    /**
     * Mark entries with same normalized number as duplicates (except first occurrence).
     */
    private function mark_duplicates(): void {
        global $wpdb;
        $table = KayanCC_Database::get_scan_table();

        $dupes = $wpdb->get_results( // phpcs:ignore
            "SELECT normalized FROM {$table}
             WHERE result_type IN ('phone_confirmed','phone_probable','whatsapp_confirmed')
             GROUP BY normalized HAVING COUNT(*) > 1",
            ARRAY_A
        );

        foreach ( $dupes as $dupe ) {
            if ( empty( $dupe['normalized'] ) ) continue;
            $wpdb->query( // phpcs:ignore
                $wpdb->prepare(
                    "UPDATE {$table} SET result_type = 'duplicate'
                     WHERE normalized = %s
                       AND id NOT IN (
                           SELECT id FROM (
                               SELECT MIN(id) as id FROM {$table} WHERE normalized = %s
                           ) AS t
                       )",
                    $dupe['normalized'],
                    $dupe['normalized']
                )
            );
        }
    }
}
