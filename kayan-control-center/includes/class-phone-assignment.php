<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Manages the assignment of phone numbers to posts / post types / taxonomy terms.
 *
 * Priority order (highest first):
 *  1. Specific post       (scope_type = 'post')
 *  2. Taxonomy term       (scope_type = 'taxonomy_term')
 *  3. Post type           (scope_type = 'post_type')
 *  4. Site default        (scope_type = 'site_default')
 */
class KayanCC_Phone_Assignment {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    // ─── Assign ──────────────────────────────────────────────────────────────

    /**
     * Assign a number to a scope, replacing any existing assignment for that scope.
     */
    public function assign( int $number_id, string $scope_type, int $scope_id = 0, string $scope_taxonomy = '' ): bool|\WP_Error {
        global $wpdb;

        if ( ! KayanCC_Phone_Manager::get_instance()->get( $number_id ) ) {
            return new \WP_Error( 'invalid_number', 'رقم الهاتف غير موجود.' );
        }

        $valid_scopes = [ 'post', 'post_type', 'taxonomy_term', 'site_default' ];
        if ( ! in_array( $scope_type, $valid_scopes, true ) ) {
            return new \WP_Error( 'invalid_scope', 'نوع النطاق غير صحيح.' );
        }

        $table = KayanCC_Database::get_assignments_table();

        // Remove existing assignment for this scope
        $wpdb->delete( // phpcs:ignore
            $table,
            [
                'scope_type'     => $scope_type,
                'scope_id'       => $scope_id,
                'scope_taxonomy' => $scope_taxonomy,
            ],
            [ '%s', '%d', '%s' ]
        );

        $priority_map = [
            'post'          => 1,
            'taxonomy_term' => 2,
            'post_type'     => 3,
            'site_default'  => 4,
        ];

        $wpdb->insert( // phpcs:ignore
            $table,
            [
                'number_id'      => $number_id,
                'scope_type'     => $scope_type,
                'scope_id'       => $scope_id,
                'scope_taxonomy' => $scope_taxonomy,
                'priority'       => $priority_map[ $scope_type ],
            ],
            [ '%d', '%s', '%d', '%s', '%d' ]
        );

        KayanCC_Activity_Log::record( 'assign_number', 'assignment', (int) $wpdb->insert_id, null, compact( 'number_id', 'scope_type', 'scope_id', 'scope_taxonomy' ) );
        wp_cache_delete( 'kayan_cc_assignment_' . $scope_type . '_' . $scope_id . '_' . $scope_taxonomy, 'kayan_cc' );
        return true;
    }

    public function remove_assignment( int $assignment_id ): bool {
        global $wpdb;
        $table = KayanCC_Database::get_assignments_table();
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $assignment_id ), ARRAY_A ); // phpcs:ignore
        if ( ! $row ) return false;
        $wpdb->delete( $table, [ 'id' => $assignment_id ], [ '%d' ] ); // phpcs:ignore
        KayanCC_Activity_Log::record( 'remove_assignment', 'assignment', $assignment_id, $row, null );
        return true;
    }

    // ─── Resolve ─────────────────────────────────────────────────────────────

    /**
     * Resolve which phone number to use for a given post.
     * Returns array of [ 'number' => row, 'conflicts' => [] ] or null if nothing assigned.
     */
    public function resolve_for_post( int $post_id ): ?array {
        $cache_key = 'kayan_cc_resolve_post_' . $post_id;
        $cached    = wp_cache_get( $cache_key, 'kayan_cc' );
        if ( false !== $cached ) return $cached;

        global $wpdb;
        $assign_table = KayanCC_Database::get_assignments_table();
        $num_table    = KayanCC_Database::get_numbers_table();

        $candidates = [];

        // 1. Post-specific assignment
        $row = $wpdb->get_row( // phpcs:ignore
            $wpdb->prepare(
                "SELECT a.*, n.* FROM {$assign_table} a
                 JOIN {$num_table} n ON n.id = a.number_id
                 WHERE a.scope_type = 'post' AND a.scope_id = %d AND n.status = 'active'
                 LIMIT 1",
                $post_id
            ),
            ARRAY_A
        );
        if ( $row ) $candidates[] = [ 'priority' => 1, 'row' => $row ];

        // 2. Taxonomy term assignments
        $taxonomies = get_object_taxonomies( get_post_type( $post_id ) );
        foreach ( $taxonomies as $tax ) {
            $terms = wp_get_object_terms( $post_id, $tax, [ 'fields' => 'ids' ] );
            if ( is_wp_error( $terms ) || empty( $terms ) ) continue;
            foreach ( $terms as $term_id ) {
                $row = $wpdb->get_row( // phpcs:ignore
                    $wpdb->prepare(
                        "SELECT a.*, n.* FROM {$assign_table} a
                         JOIN {$num_table} n ON n.id = a.number_id
                         WHERE a.scope_type = 'taxonomy_term'
                           AND a.scope_id = %d
                           AND a.scope_taxonomy = %s
                           AND n.status = 'active'
                         LIMIT 1",
                        $term_id,
                        $tax
                    ),
                    ARRAY_A
                );
                if ( $row ) $candidates[] = [ 'priority' => 2, 'row' => $row, 'term_id' => $term_id, 'taxonomy' => $tax ];
            }
        }

        // 3. Post type assignment
        $post_type = get_post_type( $post_id );
        if ( $post_type ) {
            $row = $wpdb->get_row( // phpcs:ignore
                $wpdb->prepare(
                    "SELECT a.*, n.* FROM {$assign_table} a
                     JOIN {$num_table} n ON n.id = a.number_id
                     WHERE a.scope_type = 'post_type' AND a.scope_taxonomy = %s AND n.status = 'active'
                     LIMIT 1",
                    $post_type
                ),
                ARRAY_A
            );
            if ( $row ) $candidates[] = [ 'priority' => 3, 'row' => $row ];
        }

        // 4. Site default
        $row = $wpdb->get_row( // phpcs:ignore
            "SELECT a.*, n.* FROM {$assign_table} a
             JOIN {$num_table} n ON n.id = a.number_id
             WHERE a.scope_type = 'site_default' AND n.status = 'active'
             LIMIT 1",
            ARRAY_A
        );
        if ( $row ) $candidates[] = [ 'priority' => 4, 'row' => $row ];

        if ( empty( $candidates ) ) {
            wp_cache_set( $cache_key, null, 'kayan_cc', 300 );
            return null;
        }

        // Sort by priority (lowest number = highest priority)
        usort( $candidates, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

        // Detect taxonomy conflicts (same priority level, different numbers)
        $tax_candidates = array_filter( $candidates, fn( $c ) => $c['priority'] === 2 );
        $conflicts      = [];
        if ( count( $tax_candidates ) > 1 ) {
            $unique_numbers = array_unique( array_column( array_column( array_values( $tax_candidates ), 'row' ), 'number_id' ) );
            if ( count( $unique_numbers ) > 1 ) {
                $conflicts = array_values( $tax_candidates );
            }
        }

        $result = [
            'number'    => $candidates[0]['row'],
            'conflicts' => $conflicts,
        ];

        wp_cache_set( $cache_key, $result, 'kayan_cc', 300 );
        return $result;
    }

    // ─── Listing ─────────────────────────────────────────────────────────────

    public function get_all_assignments( array $args = [] ): array {
        global $wpdb;
        $table  = KayanCC_Database::get_assignments_table();
        $where  = [];
        $params = [];

        if ( ! empty( $args['number_id'] ) ) {
            $where[]  = 'a.number_id = %d';
            $params[] = (int) $args['number_id'];
        }
        if ( ! empty( $args['scope_type'] ) ) {
            $where[]  = 'a.scope_type = %s';
            $params[] = $args['scope_type'];
        }

        $num_table = KayanCC_Database::get_numbers_table();
        $where_sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

        return $wpdb->get_results( // phpcs:ignore
            $params
                ? $wpdb->prepare( "SELECT a.*, n.name, n.phone_number, n.whatsapp_number FROM {$table} a JOIN {$num_table} n ON n.id = a.number_id {$where_sql} ORDER BY a.priority, a.id", $params )
                : "SELECT a.*, n.name, n.phone_number, n.whatsapp_number FROM {$table} a JOIN {$num_table} n ON n.id = a.number_id {$where_sql} ORDER BY a.priority, a.id",
            ARRAY_A
        );
    }

    // ─── Bulk assign ─────────────────────────────────────────────────────────

    /**
     * Assign a number to multiple posts at once.
     */
    public function bulk_assign_posts( int $number_id, array $post_ids ): array {
        $results = [ 'success' => 0, 'failed' => 0 ];
        foreach ( $post_ids as $post_id ) {
            $r = $this->assign( $number_id, 'post', (int) $post_id );
            is_wp_error( $r ) ? $results['failed']++ : $results['success']++;
        }
        return $results;
    }

    /**
     * Assign a number to all posts in a taxonomy term.
     */
    public function assign_to_taxonomy_term( int $number_id, int $term_id, string $taxonomy ): bool|\WP_Error {
        return $this->assign( $number_id, 'taxonomy_term', $term_id, $taxonomy );
    }

    public function set_site_default( int $number_id ): bool|\WP_Error {
        return $this->assign( $number_id, 'site_default', 0, '' );
    }
}
