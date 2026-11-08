<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SEO Title Templates – save, list, and apply reusable title templates.
 */
class KayanCC_SEO_Templates {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function create( array $data ): int|\WP_Error {
        global $wpdb;
        $table = KayanCC_Database::get_templates_table();

        if ( empty( $data['name'] ) || empty( $data['template'] ) ) {
            return new \WP_Error( 'missing_fields', 'اسم القالب والنص مطلوبان.' );
        }

        $clean = [
            'name'          => sanitize_text_field( $data['name'] ),
            'template'      => sanitize_text_field( $data['template'] ),
            'scope_type'    => in_array( $data['scope_type'] ?? '', [ 'post_type', 'taxonomy_term', 'site_default', 'manual' ], true ) ? $data['scope_type'] : 'manual',
            'scope_id'      => (int) ( $data['scope_id'] ?? 0 ),
            'scope_taxonomy'=> sanitize_text_field( $data['scope_taxonomy'] ?? '' ),
        ];

        $wpdb->insert( $table, $clean, [ '%s','%s','%s','%d','%s' ] ); // phpcs:ignore
        $id = (int) $wpdb->insert_id;
        KayanCC_Activity_Log::record( 'create_seo_template', 'seo_template', $id, null, $clean );
        return $id;
    }

    public function update( int $id, array $data ): bool|\WP_Error {
        global $wpdb;
        $table    = KayanCC_Database::get_templates_table();
        $existing = $this->get( $id );
        if ( ! $existing ) return new \WP_Error( 'not_found', 'القالب غير موجود.' );

        $clean = [
            'name'          => sanitize_text_field( $data['name'] ?? $existing['name'] ),
            'template'      => sanitize_text_field( $data['template'] ?? $existing['template'] ),
            'scope_type'    => in_array( $data['scope_type'] ?? '', [ 'post_type', 'taxonomy_term', 'site_default', 'manual' ], true ) ? $data['scope_type'] : $existing['scope_type'],
            'scope_id'      => (int) ( $data['scope_id'] ?? $existing['scope_id'] ),
            'scope_taxonomy'=> sanitize_text_field( $data['scope_taxonomy'] ?? $existing['scope_taxonomy'] ),
        ];

        $wpdb->update( $table, $clean, [ 'id' => $id ], [ '%s','%s','%s','%d','%s' ], [ '%d' ] ); // phpcs:ignore
        KayanCC_Activity_Log::record( 'update_seo_template', 'seo_template', $id, $existing, $clean );
        return true;
    }

    public function delete( int $id ): bool {
        global $wpdb;
        $table    = KayanCC_Database::get_templates_table();
        $existing = $this->get( $id );
        if ( ! $existing ) return false;
        $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] ); // phpcs:ignore
        KayanCC_Activity_Log::record( 'delete_seo_template', 'seo_template', $id, $existing, null );
        return true;
    }

    public function get( int $id ): ?array {
        global $wpdb;
        $table = KayanCC_Database::get_templates_table();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ) ?: null; // phpcs:ignore
    }

    public function get_all(): array {
        global $wpdb;
        $table = KayanCC_Database::get_templates_table();
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A ) ?: []; // phpcs:ignore
    }

    // ─── Apply template ──────────────────────────────────────────────────────

    /**
     * Apply a template to a set of posts (with preview step built-in).
     * Returns preview data.
     */
    public function apply_template_preview( int $template_id, array $post_ids ): array|\WP_Error {
        $template = $this->get( $template_id );
        if ( ! $template ) return new \WP_Error( 'not_found', 'القالب غير موجود.' );

        $seo_manager = KayanCC_SEO_Manager::get_instance();
        return $seo_manager->preview_bulk( $post_ids, $template['template'] );
    }

    public function apply_template_execute( int $template_id, array $post_ids ): array|\WP_Error {
        $template = $this->get( $template_id );
        if ( ! $template ) return new \WP_Error( 'not_found', 'القالب غير موجود.' );

        $seo_manager = KayanCC_SEO_Manager::get_instance();
        return $seo_manager->bulk_apply( $post_ids, $template['template'] );
    }

    /**
     * Get all posts that a template's scope would apply to.
     */
    public function get_scope_posts( array $template ): array {
        $args = [ 'posts_per_page' => -1, 'post_status' => [ 'publish', 'draft' ], 'fields' => 'ids' ];

        switch ( $template['scope_type'] ) {
            case 'post_type':
                $args['post_type'] = $template['scope_taxonomy'] ?: 'post';
                break;
            case 'taxonomy_term':
                $args['post_type'] = 'any';
                $args['tax_query'] = [[
                    'taxonomy' => $template['scope_taxonomy'],
                    'field'    => 'term_id',
                    'terms'    => [ (int) $template['scope_id'] ],
                ]];
                break;
            default:
                $args['post_type'] = get_post_types( [ 'public' => true ] );
        }

        return get_posts( $args );
    }
}
