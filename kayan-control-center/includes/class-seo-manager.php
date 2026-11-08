<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SEO Title Manager – reads/writes Rank Math SEO titles via the official meta keys.
 *
 * Rank Math stores the SEO title in post meta: `rank_math_title`
 * It falls back to the pattern set in Rank Math → Titles & Meta if the meta is empty.
 *
 * We NEVER output our own <title> tag – we only write to Rank Math's meta key so it
 * controls the final output (ensuring no duplication).
 */
class KayanCC_SEO_Manager {

    const RANK_MATH_TITLE_KEY = 'rank_math_title';
    const KAYAN_CC_BACKUP_KEY = 'kayan_cc_seo_title_backup';

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }

    // ─── Check ───────────────────────────────────────────────────────────────

    public function is_rank_math_active(): bool {
        return defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' );
    }

    // ─── Read ────────────────────────────────────────────────────────────────

    public function get_seo_title( int $post_id ): ?string {
        return get_post_meta( $post_id, self::RANK_MATH_TITLE_KEY, true ) ?: null;
    }

    public function get_post_data_for_seo( int $post_id ): array {
        $post       = get_post( $post_id );
        $seo_title  = $this->get_seo_title( $post_id );
        $categories = wp_get_object_terms( $post_id, get_object_taxonomies( $post->post_type ), [ 'fields' => 'names' ] );

        return [
            'post_id'   => $post_id,
            'title'     => $post->post_title,
            'seo_title' => $seo_title,
            'edit_url'  => get_edit_post_link( $post_id ),
            'post_url'  => get_permalink( $post_id ),
            'post_type' => $post->post_type,
            'categories'=> is_wp_error( $categories ) ? [] : $categories,
        ];
    }

    // ─── Write ───────────────────────────────────────────────────────────────

    /**
     * Set a SEO title for a single post.
     * Backs up the old value first.
     */
    public function set_seo_title( int $post_id, string $title ): bool|\WP_Error {
        if ( ! $this->is_rank_math_active() ) {
            return new \WP_Error( 'rank_math_inactive', 'إضافة Rank Math غير مفعلة. لن يتم حفظ عنوان SEO.' );
        }

        $old_title = $this->get_seo_title( $post_id );
        // Backup only once (don't overwrite the original backup)
        if ( null !== $old_title && ! get_post_meta( $post_id, self::KAYAN_CC_BACKUP_KEY, true ) ) {
            update_post_meta( $post_id, self::KAYAN_CC_BACKUP_KEY, $old_title );
        }

        $sanitized = sanitize_text_field( $title );
        update_post_meta( $post_id, self::RANK_MATH_TITLE_KEY, $sanitized );
        KayanCC_Activity_Log::record( 'set_seo_title', 'seo', $post_id, [ 'seo_title' => $old_title ], [ 'seo_title' => $sanitized ], true );

        return true;
    }

    /**
     * Clear the custom SEO title (revert to Rank Math default).
     */
    public function clear_seo_title( int $post_id ): bool {
        $old_title = $this->get_seo_title( $post_id );
        delete_post_meta( $post_id, self::RANK_MATH_TITLE_KEY );
        delete_post_meta( $post_id, self::KAYAN_CC_BACKUP_KEY );
        KayanCC_Activity_Log::record( 'clear_seo_title', 'seo', $post_id, [ 'seo_title' => $old_title ], null, true );
        return true;
    }

    /**
     * Rollback to the backed-up SEO title.
     */
    public function rollback_seo_title( int $post_id ): bool|\WP_Error {
        $backup = get_post_meta( $post_id, self::KAYAN_CC_BACKUP_KEY, true );
        if ( false === $backup || '' === $backup ) {
            return new \WP_Error( 'no_backup', 'لا توجد نسخة احتياطية لهذا العنوان.' );
        }
        update_post_meta( $post_id, self::RANK_MATH_TITLE_KEY, $backup );
        delete_post_meta( $post_id, self::KAYAN_CC_BACKUP_KEY );
        KayanCC_Activity_Log::record( 'rollback_seo_title', 'seo', $post_id, null, [ 'seo_title' => $backup ] );
        return true;
    }

    // ─── Bulk operations ─────────────────────────────────────────────────────

    /**
     * Preview bulk SEO title application using a template.
     */
    public function preview_bulk( array $post_ids, string $template, array $template_vars = [] ): array {
        $previews = [];
        foreach ( $post_ids as $post_id ) {
            $post_id = (int) $post_id;
            $post    = get_post( $post_id );
            if ( ! $post ) continue;

            $resolved = $this->resolve_template( $template, $post_id );
            $old      = $this->get_seo_title( $post_id );
            $warnings = $this->validate_title( $resolved, $post_ids );

            $previews[] = [
                'post_id'       => $post_id,
                'post_title'    => $post->post_title,
                'current_seo'   => $old,
                'new_seo'       => $resolved,
                'warnings'      => $warnings,
            ];
        }
        return $previews;
    }

    /**
     * Apply a title template to a list of posts (after preview confirmed).
     */
    public function bulk_apply( array $post_ids, string $template ): array {
        $results = [ 'success' => 0, 'failed' => 0, 'errors' => [] ];
        foreach ( $post_ids as $post_id ) {
            $post_id  = (int) $post_id;
            $resolved = $this->resolve_template( $template, $post_id );
            $result   = $this->set_seo_title( $post_id, $resolved );
            if ( is_wp_error( $result ) ) {
                $results['failed']++;
                $results['errors'][] = [ 'post_id' => $post_id, 'error' => $result->get_error_message() ];
            } else {
                $results['success']++;
            }
        }
        return $results;
    }

    // ─── Template resolution ─────────────────────────────────────────────────

    /**
     * Replace template variables with real post data.
     * Supported vars: {post_title}, {city}, {service}, {category}, {site_name}
     */
    public function resolve_template( string $template, int $post_id ): string {
        $post      = get_post( $post_id );
        $site_name = get_bloginfo( 'name' );

        // City / service come from post taxonomies or meta
        $city    = $this->extract_meta_or_term( $post_id, [ 'city', 'المدينة', '_city', 'location' ], 'city' );
        $service = $this->extract_meta_or_term( $post_id, [ 'service', 'الخدمة', '_service' ], 'service' );
        $category= $this->extract_meta_or_term( $post_id, [], 'category' );

        $map = [
            '{post_title}' => $post->post_title,
            '{city}'       => $city,
            '{service}'    => $service,
            '{category}'   => $category,
            '{site_name}'  => $site_name,
        ];

        return str_replace( array_keys( $map ), array_values( $map ), $template );
    }

    private function extract_meta_or_term( int $post_id, array $meta_keys, string $type ): string {
        // Try meta keys first
        foreach ( $meta_keys as $key ) {
            $val = get_post_meta( $post_id, $key, true );
            if ( $val ) return sanitize_text_field( $val );
        }

        // Try taxonomy terms
        if ( $type === 'city' ) {
            $terms = wp_get_object_terms( $post_id, [ 'city', 'cities', 'location', 'المدن' ], [ 'fields' => 'names', 'number' => 1 ] );
            if ( ! is_wp_error( $terms ) && $terms ) return $terms[0];
        }
        if ( $type === 'service' ) {
            $terms = wp_get_object_terms( $post_id, [ 'service', 'services', 'الخدمات' ], [ 'fields' => 'names', 'number' => 1 ] );
            if ( ! is_wp_error( $terms ) && $terms ) return $terms[0];
        }
        if ( $type === 'category' ) {
            $terms = wp_get_object_terms( $post_id, [ 'category', 'service_category' ], [ 'fields' => 'names', 'number' => 1 ] );
            if ( ! is_wp_error( $terms ) && $terms ) return $terms[0];
        }

        return '';
    }

    // ─── Validation ──────────────────────────────────────────────────────────

    public function validate_title( string $title, array $all_post_ids ): array {
        $warnings = [];

        if ( empty( trim( $title ) ) ) {
            $warnings[] = 'العنوان فارغ.';
        }
        if ( mb_strlen( $title ) > 70 ) {
            $warnings[] = sprintf( 'العنوان طويل (%d حرف). يُنصح بأقل من 60 حرف.', mb_strlen( $title ) );
        }
        if ( preg_match( '/\{[^}]+\}/', $title ) ) {
            $warnings[] = 'يحتوي العنوان على متغير لم يُحلّ: ' . $title;
        }

        return $warnings;
    }

    // ─── List posts for admin UI ──────────────────────────────────────────────

    public function get_posts_list( array $args = [] ): array {
        $query_args = [
            'post_type'      => $args['post_type'] ?? get_post_types( [ 'public' => true ] ),
            'post_status'    => [ 'publish', 'draft' ],
            'posts_per_page' => (int) ( $args['per_page'] ?? 20 ),
            'paged'          => (int) ( $args['page']     ?? 1 ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( $args['search'] );
        }
        if ( ! empty( $args['tax_query'] ) ) {
            $query_args['tax_query'] = $args['tax_query'];
        }

        $query = new WP_Query( $query_args );
        $posts = [];
        foreach ( $query->posts as $post ) {
            $posts[] = $this->get_post_data_for_seo( $post->ID );
        }

        return [
            'posts'       => $posts,
            'total'       => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'current_page'=> (int) ( $args['page'] ?? 1 ),
        ];
    }
}
