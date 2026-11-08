<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * CRUD operations for the phone numbers registry.
 */
class KayanCC_Phone_Manager {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    // ─── Get ─────────────────────────────────────────────────────────────────

    public function get_all( array $args = [] ): array {
        global $wpdb;
        $table  = KayanCC_Database::get_numbers_table();
        $where  = [];
        $params = [];

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $params[] = $args['status'];
        }
        if ( ! empty( $args['search'] ) ) {
            $search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[]  = '(name LIKE %s OR phone_number LIKE %s OR phone_e164 LIKE %s OR whatsapp_number LIKE %s)';
            $params   = array_merge( $params, [ $search, $search, $search, $search ] );
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
        $page      = max( 1, (int) ( $args['page'] ?? 1 ) );
        $per_page  = max( 1, (int) ( $args['per_page'] ?? 20 ) );

        $total = (int) $wpdb->get_var( // phpcs:ignore
            $params
                ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where_sql}", $params )
                : "SELECT COUNT(*) FROM {$table} {$where_sql}"
        );

        $paging = KayanCC_Helpers::paginate( $total, $per_page, $page );

        $sql_params   = array_merge( $params, [ $paging['per_page'], $paging['offset'] ] );
        $rows         = $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare(
                "SELECT * FROM {$table} {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
                $sql_params
            ),
            ARRAY_A
        );

        return [ 'paging' => $paging, 'numbers' => $rows ];
    }

    public function get( int $id ): ?array {
        global $wpdb;
        $table = KayanCC_Database::get_numbers_table();
        return $wpdb->get_row( // phpcs:ignore
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
            ARRAY_A
        ) ?: null;
    }

    // ─── Create ──────────────────────────────────────────────────────────────

    public function create( array $data ): int|\WP_Error {
        global $wpdb;
        $clean = KayanCC_Helpers::sanitize_phone_input( $data );

        if ( empty( $clean['phone_number'] ) && empty( $clean['whatsapp_number'] ) ) {
            return new \WP_Error( 'missing_number', 'يجب إدخال رقم الاتصال أو رقم واتساب على الأقل.' );
        }

        $table = KayanCC_Database::get_numbers_table();
        $wpdb->insert( $table, $clean, array_fill( 0, count( $clean ), '%s' ) ); // phpcs:ignore
        $new_id = (int) $wpdb->insert_id;

        KayanCC_Activity_Log::record( 'create_number', 'number', $new_id, null, $clean );
        wp_cache_delete( 'kayan_cc_active_numbers', 'kayan_cc' );
        return $new_id;
    }

    // ─── Update ──────────────────────────────────────────────────────────────

    public function update( int $id, array $data ): bool|\WP_Error {
        global $wpdb;
        $existing = $this->get( $id );
        if ( ! $existing ) return new \WP_Error( 'not_found', 'الرقم غير موجود.' );

        $clean = KayanCC_Helpers::sanitize_phone_input( array_merge( $existing, $data ) );
        $table = KayanCC_Database::get_numbers_table();
        $wpdb->update( $table, $clean, [ 'id' => $id ], array_fill( 0, count( $clean ), '%s' ), [ '%d' ] ); // phpcs:ignore

        KayanCC_Activity_Log::record( 'update_number', 'number', $id, $existing, $clean );
        wp_cache_delete( 'kayan_cc_active_numbers', 'kayan_cc' );
        return true;
    }

    // ─── Delete ──────────────────────────────────────────────────────────────

    public function delete( int $id ): bool {
        global $wpdb;
        $existing = $this->get( $id );
        if ( ! $existing ) return false;

        $table = KayanCC_Database::get_numbers_table();
        $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] ); // phpcs:ignore

        // Remove all assignments for this number
        $assign_table = KayanCC_Database::get_assignments_table();
        $wpdb->delete( $assign_table, [ 'number_id' => $id ], [ '%d' ] ); // phpcs:ignore

        KayanCC_Activity_Log::record( 'delete_number', 'number', $id, $existing, null );
        wp_cache_delete( 'kayan_cc_active_numbers', 'kayan_cc' );
        return true;
    }

    // ─── Toggle status ────────────────────────────────────────────────────────

    public function toggle_status( int $id ): bool {
        $existing = $this->get( $id );
        if ( ! $existing ) return false;
        $new_status = $existing['status'] === 'active' ? 'inactive' : 'active';
        return $this->update( $id, [ 'status' => $new_status ] ) === true;
    }

    // ─── Get all active numbers (cached) ─────────────────────────────────────

    public function get_active_numbers(): array {
        $cached = wp_cache_get( 'kayan_cc_active_numbers', 'kayan_cc' );
        if ( false !== $cached ) return $cached;

        global $wpdb;
        $table = KayanCC_Database::get_numbers_table();
        $rows  = $wpdb->get_results( // phpcs:ignore
            "SELECT * FROM {$table} WHERE status = 'active' ORDER BY id",
            ARRAY_A
        );

        wp_cache_set( 'kayan_cc_active_numbers', $rows, 'kayan_cc', 3600 );
        return $rows;
    }

    // ─── Usage stats ─────────────────────────────────────────────────────────

    public function get_usage_count( int $number_id ): int {
        global $wpdb;
        $table = KayanCC_Database::get_assignments_table();
        return (int) $wpdb->get_var( // phpcs:ignore
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE number_id = %d", $number_id )
        );
    }

    /**
     * Get occurrence stats for a number from the scan results table.
     * Returns ['total_occurrences' => int, 'unique_posts' => int]
     */
    public function get_occurrence_stats( string $phone_e164, string $phone_number = '' ): array {
        global $wpdb;
        $table = KayanCC_Database::get_scan_table();

        if ( ! $phone_e164 && ! $phone_number ) return [ 'total_occurrences' => 0, 'unique_posts' => 0 ];

        $where_parts = [];
        $params      = [];

        if ( $phone_e164 ) {
            $where_parts[] = 'normalized = %s';
            $params[]      = $phone_e164;
        }
        if ( $phone_number && $phone_number !== $phone_e164 ) {
            $where_parts[] = 'raw_value LIKE %s';
            $params[]      = '%' . $wpdb->esc_like( $phone_number ) . '%';
        }

        $where = implode( ' OR ', $where_parts );
        $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $params ) ); // phpcs:ignore
        $posts = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT source_id) FROM {$table} WHERE ({$where}) AND source_id > 0", $params ) ); // phpcs:ignore

        return [ 'total_occurrences' => $total, 'unique_posts' => $posts ];
    }

    /**
     * Hard delete: remove number from the registry AND all post content/meta.
     */
    public function hard_delete( int $id ): array|\WP_Error {
        $number = $this->get( $id );
        if ( ! $number ) return new \WP_Error( 'not_found', 'الرقم غير موجود.' );

        $replacer = new KayanCC_Phone_Replacement();
        $results  = [];

        if ( $number['phone_number'] ) {
            $r = $replacer->execute( $number['phone_number'], '', [ 'type' => 'all' ] );
            if ( ! is_wp_error( $r ) ) $results['phone'] = $r;
        }

        if ( $number['whatsapp_number'] && $number['whatsapp_number'] !== $number['phone_number'] ) {
            $r = $replacer->execute( $number['whatsapp_number'], '', [ 'type' => 'all' ] );
            if ( ! is_wp_error( $r ) ) $results['whatsapp'] = $r;
        }

        $this->delete( $id );
        KayanCC_Activity_Log::record( 'hard_delete_number', 'number', $id, $number, [ 'replacement_results' => $results ] );

        return [ 'success' => true, 'number' => $number, 'replacement_results' => $results ];
    }

    /**
     * Delete a number occurrence from a specific post content or meta field.
     */
    public function delete_from_location( string $phone_e164, string $source_type, int $source_id, string $source_field ): bool|\WP_Error {
        if ( ! $source_id ) return new \WP_Error( 'invalid_source', 'معرف المصدر غير صحيح.' );

        $variants = array_filter( [ $phone_e164, $this->e164_to_local( $phone_e164 ) ] );

        if ( in_array( $source_type, [ 'post_content', 'post_title' ], true ) ) {
            $post        = get_post( $source_id );
            $new_content = $post->post_content;
            foreach ( $variants as $v ) {
                $new_content = preg_replace( '/(?<![0-9])' . preg_quote( $v, '/' ) . '(?![0-9])/u', '', $new_content ) ?? $new_content;
            }
            wp_update_post( [ 'ID' => $source_id, 'post_content' => $new_content ] );
            KayanCC_Activity_Log::record( 'delete_from_location', 'post', $source_id, [ 'field' => $source_type ], [ 'number' => $phone_e164 ] );
            return true;
        }

        if ( $source_type === 'post_meta' ) {
            $meta_key = str_replace( 'postmeta:', '', $source_field );
            $meta_val = get_post_meta( $source_id, $meta_key, true );
            if ( is_string( $meta_val ) ) {
                foreach ( $variants as $v ) {
                    $meta_val = preg_replace( '/(?<![0-9])' . preg_quote( $v, '/' ) . '(?![0-9])/u', '', $meta_val ) ?? $meta_val;
                }
                update_post_meta( $source_id, $meta_key, $meta_val );
                KayanCC_Activity_Log::record( 'delete_from_location', 'post_meta', $source_id, [ 'meta_key' => $meta_key ], [ 'number' => $phone_e164 ] );
            }
            return true;
        }

        return new \WP_Error( 'unsupported_source', 'نوع المصدر غير مدعوم للحذف المباشر.' );
    }

    private function e164_to_local( string $e164 ): string {
        if ( strpos( $e164, '+971' ) === 0 ) {
            return '0' . substr( ltrim( $e164, '+' ), 3 );
        }
        return '';
    }
}
