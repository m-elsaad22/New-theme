<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Activity Log – records all plugin operations and supports rollback.
 */
class KayanCC_Activity_Log {

    // ─── Record ──────────────────────────────────────────────────────────────

    /**
     * @return int  Inserted log row ID.
     */
    public static function record(
        string $action,
        string $object_type,
        int    $object_id,
        ?array $before,
        ?array $after,
        bool   $is_reversible = false
    ): int {
        global $wpdb;
        $table = KayanCC_Database::get_log_table();

        $wpdb->insert( // phpcs:ignore
            $table,
            [
                'action'       => substr( $action, 0, 100 ),
                'object_type'  => substr( $object_type, 0, 100 ),
                'object_id'    => $object_id,
                'user_id'      => get_current_user_id(),
                'data_before'  => $before  ? wp_json_encode( $before )  : null,
                'data_after'   => $after   ? wp_json_encode( $after )   : null,
                'is_reversible'=> $is_reversible ? 1 : 0,
            ],
            [ '%s','%s','%d','%d','%s','%s','%d' ]
        );

        return (int) $wpdb->insert_id;
    }

    // ─── Read ────────────────────────────────────────────────────────────────

    public static function get( int $id ): ?array {
        global $wpdb;
        $table = KayanCC_Database::get_log_table();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ) ?: null; // phpcs:ignore
    }

    public static function get_list( array $args = [] ): array {
        global $wpdb;
        $table  = KayanCC_Database::get_log_table();
        $where  = [];
        $params = [];

        if ( ! empty( $args['action'] ) ) {
            $where[]  = 'action = %s';
            $params[] = $args['action'];
        }
        if ( ! empty( $args['object_type'] ) ) {
            $where[]  = 'object_type = %s';
            $params[] = $args['object_type'];
        }
        if ( ! empty( $args['user_id'] ) ) {
            $where[]  = 'user_id = %d';
            $params[] = (int) $args['user_id'];
        }

        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
        $page      = max( 1, (int) ( $args['page'] ?? 1 ) );
        $per_page  = max( 1, (int) ( $args['per_page'] ?? 30 ) );

        $total = (int) $wpdb->get_var( // phpcs:ignore
            $params
                ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where_sql}", $params )
                : "SELECT COUNT(*) FROM {$table} {$where_sql}"
        );

        $paging   = KayanCC_Helpers::paginate( $total, $per_page, $page );
        $sql_args = array_merge( $params, [ $paging['per_page'], $paging['offset'] ] );

        $rows = $wpdb->get_results( // phpcs:ignore
            $params
                ? $wpdb->prepare( "SELECT * FROM {$table} {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d", $sql_args )
                : $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $paging['per_page'], $paging['offset'] ),
            ARRAY_A
        );

        // Enrich with user display name
        foreach ( $rows as &$row ) {
            $user = get_userdata( (int) $row['user_id'] );
            $row['user_display'] = $user ? $user->display_name : __( 'غير معروف', 'kayan-cc' );
        }
        unset( $row );

        return [ 'paging' => $paging, 'rows' => $rows ];
    }

    // ─── Mark reverted ───────────────────────────────────────────────────────

    public static function mark_reverted( int $log_id ): void {
        global $wpdb;
        $table = KayanCC_Database::get_log_table();
        $wpdb->update( // phpcs:ignore
            $table,
            [ 'reverted_by' => get_current_user_id() ?: 1 ],
            [ 'id' => $log_id ],
            [ '%d' ],
            [ '%d' ]
        );
    }

    // ─── Action labels (for display) ─────────────────────────────────────────

    public static function get_action_label( string $action ): string {
        $labels = [
            'create_number'        => 'إضافة رقم',
            'update_number'        => 'تعديل رقم',
            'delete_number'        => 'حذف رقم',
            'assign_number'        => 'تعيين رقم',
            'remove_assignment'    => 'إلغاء تعيين رقم',
            'replace_number'       => 'استبدال رقم',
            'rollback_replacement' => 'التراجع عن استبدال',
            'set_seo_title'        => 'تعيين عنوان SEO',
            'clear_seo_title'      => 'مسح عنوان SEO',
            'rollback_seo_title'   => 'التراجع عن عنوان SEO',
            'create_seo_template'  => 'إنشاء قالب SEO',
            'update_seo_template'  => 'تعديل قالب SEO',
            'delete_seo_template'  => 'حذف قالب SEO',
        ];
        return $labels[ $action ] ?? $action;
    }
}
