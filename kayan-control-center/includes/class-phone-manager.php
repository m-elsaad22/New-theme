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
}
