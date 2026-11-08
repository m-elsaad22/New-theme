<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Safely replaces phone numbers in post content and meta.
 *
 * Rules:
 *  - Always preview before executing.
 *  - Handle serialized and JSON data correctly.
 *  - Never touch theme files or plugin PHP files.
 *  - Store a backup before replacement for rollback.
 */
class KayanCC_Phone_Replacement {

    /**
     * Build a preview of what would change.
     *
     * @param string $old_number   Raw old number as entered by user.
     * @param string $new_number   Raw new number.
     * @param array  $scope        ['type' => 'all'|'posts'|'taxonomy'|'specific', 'ids' => [], 'taxonomy' => '', 'term_id' => 0]
     */
    public function preview( string $old_number, string $new_number, array $scope ): array {
        $old_variants = $this->get_number_variants( $old_number );
        if ( empty( $old_variants ) ) {
            return [ 'error' => 'لم يتم التعرف على الرقم القديم كرقم هاتف صحيح.' ];
        }
        // new_number can be empty (deletion mode)

        $posts = $this->get_scope_posts( $scope );

        $preview_items = [];
        foreach ( $posts as $post ) {
            $matches = $this->find_matches_in_post( $post->ID, $old_variants );
            if ( $matches ) {
                $preview_items[] = [
                    'post_id'    => $post->ID,
                    'post_title' => $post->post_title,
                    'post_url'   => get_permalink( $post->ID ),
                    'edit_url'   => get_edit_post_link( $post->ID ),
                    'matches'    => $matches,
                ];
            }
        }

        return [
            'old_number'     => $old_number,
            'old_variants'   => $old_variants,
            'new_number'     => $new_number,
            'affected_posts' => count( $preview_items ),
            'total_matches'  => array_sum( array_map( fn($p) => array_sum( array_column( $p['matches'], 'count' ) ), $preview_items ) ),
            'items'          => $preview_items,
        ];
    }

    /**
     * Execute the replacement after preview has been shown and confirmed.
     * Returns log entry ID.
     */
    public function execute( string $old_number, string $new_number, array $scope ): array|\WP_Error {
        $old_variants = $this->get_number_variants( $old_number );
        if ( empty( $old_variants ) ) {
            return new \WP_Error( 'invalid_old', 'الرقم القديم غير صحيح.' );
        }
        // Allow empty new_number (means deletion / removal of the old number)
        $new_e164 = trim( $new_number ) !== '' ? KayanCC_Helpers::normalize_phone( $new_number ) : '';
        if ( trim( $new_number ) !== '' && ! $new_e164 ) {
            return new \WP_Error( 'invalid_new', 'الرقم الجديد غير صحيح.' );
        }

        $posts      = $this->get_scope_posts( $scope );
        $backup     = [];
        $changed    = 0;

        foreach ( $posts as $post ) {
            $post_backup = $this->backup_post( $post->ID );
            $changed_post = $this->replace_in_post( $post->ID, $old_variants, $new_number );
            if ( $changed_post ) {
                $backup[ $post->ID ] = $post_backup;
                $changed++;
            }
        }

        $log_id = KayanCC_Activity_Log::record(
            'replace_number',
            'replacement',
            0,
            [
                'old_number'  => $old_number,
                'old_variants'=> $old_variants,
                'scope'       => $scope,
                'backup'      => $backup,
            ],
            [
                'new_number'      => $new_number,
                'new_e164'        => $new_e164,
                'posts_changed'   => $changed,
            ],
            true // is_reversible
        );

        return [
            'success'       => true,
            'posts_changed' => $changed,
            'log_id'        => $log_id,
        ];
    }

    /**
     * Rollback a previously executed replacement.
     */
    public function rollback( int $log_id ): bool|\WP_Error {
        $log = KayanCC_Activity_Log::get( $log_id );
        if ( ! $log ) return new \WP_Error( 'not_found', 'سجل العملية غير موجود.' );
        if ( $log['action'] !== 'replace_number' ) return new \WP_Error( 'wrong_type', 'هذه العملية لا تدعم التراجع.' );
        if ( ! $log['is_reversible'] ) return new \WP_Error( 'not_reversible', 'هذه العملية لا يمكن التراجع عنها.' );
        if ( $log['reverted_by'] ) return new \WP_Error( 'already_reverted', 'تم التراجع عن هذه العملية مسبقاً.' );

        $before = json_decode( $log['data_before'], true );
        if ( empty( $before['backup'] ) ) return new \WP_Error( 'no_backup', 'لا توجد نسخة احتياطية للتراجع.' );

        global $wpdb;
        $restored = 0;
        foreach ( $before['backup'] as $post_id => $backup_data ) {
            // Restore post content
            if ( isset( $backup_data['post_content'] ) ) {
                $wpdb->update( // phpcs:ignore
                    $wpdb->posts,
                    [ 'post_content' => $backup_data['post_content'] ],
                    [ 'ID' => (int) $post_id ],
                    [ '%s' ],
                    [ '%d' ]
                );
            }
            // Restore meta
            if ( ! empty( $backup_data['meta'] ) ) {
                foreach ( $backup_data['meta'] as $meta_id => $meta_value ) {
                    $wpdb->update( // phpcs:ignore
                        $wpdb->postmeta,
                        [ 'meta_value' => $meta_value ],
                        [ 'meta_id' => (int) $meta_id ],
                        [ '%s' ],
                        [ '%d' ]
                    );
                }
            }
            clean_post_cache( (int) $post_id );
            $restored++;
        }

        // Mark log as reverted
        KayanCC_Activity_Log::mark_reverted( $log_id );
        KayanCC_Activity_Log::record( 'rollback_replacement', 'replacement', 0, $log, [ 'restored_posts' => $restored ] );

        return true;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Generate all plausible textual variants of a phone number so we can find them in content.
     */
    private function get_number_variants( string $raw ): array {
        $e164 = KayanCC_Helpers::normalize_phone( $raw );
        if ( ! $e164 ) return [];

        $digits = ltrim( $e164, '+' ); // e.g. 9715XXXXXXXX

        $variants = [
            $raw,        // as entered
            $e164,       // +971...
            '00' . $digits, // 00971...
            $digits,        // 971...
        ];

        // Local format: 05XXXXXXXX
        if ( strpos( $digits, '971' ) === 0 ) {
            $local = '0' . substr( $digits, 3 );
            $variants[] = $local;
            // With spaces: 05X XXX XXXX
            $variants[] = substr( $local, 0, 3 ) . ' ' . substr( $local, 3, 3 ) . ' ' . substr( $local, 6 );
            $variants[] = substr( $local, 0, 3 ) . '-' . substr( $local, 3, 3 ) . '-' . substr( $local, 6 );
        }

        // WhatsApp links
        $variants[] = 'wa.me/' . $digits;
        $variants[] = 'wa.me/+' . $digits;
        $variants[] = 'api.whatsapp.com/send?phone=' . $digits;
        $variants[] = 'whatsapp.com/send?phone=' . $digits;

        return array_unique( $variants );
    }

    private function get_scope_posts( array $scope ): array {
        $args = [
            'posts_per_page' => -1,
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'fields'         => 'all',
        ];

        switch ( $scope['type'] ?? 'all' ) {
            case 'all':
                $args['post_type'] = get_post_types( [ 'public' => true ] );
                break;
            case 'posts':
                $args['post_type'] = 'any';
                $args['post__in']  = array_map( 'intval', $scope['ids'] ?? [] );
                break;
            case 'taxonomy':
                $args['post_type'] = 'any';
                $args['tax_query'] = [[
                    'taxonomy' => $scope['taxonomy'],
                    'field'    => 'term_id',
                    'terms'    => [ (int) ( $scope['term_id'] ?? 0 ) ],
                    'include_children' => ! empty( $scope['include_children'] ),
                ]];
                break;
        }

        return get_posts( $args );
    }

    private function find_matches_in_post( int $post_id, array $variants ): array {
        $post    = get_post( $post_id );
        $matches = [];

        $content_count = 0;
        foreach ( $variants as $v ) {
            $content_count += substr_count( $post->post_content, $v );
        }
        if ( $content_count ) $matches[] = [ 'location' => 'محتوى المقالة', 'count' => $content_count ];

        // Post meta
        global $wpdb;
        $meta_rows = $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $post_id ),
            ARRAY_A
        );
        foreach ( $meta_rows as $row ) {
            $val = KayanCC_Helpers::safe_unserialize( $row['meta_value'] );
            $text = is_string( $val ) ? $val : wp_json_encode( $val );
            $cnt  = 0;
            foreach ( $variants as $v ) $cnt += substr_count( $text, $v );
            if ( $cnt ) $matches[] = [ 'location' => 'حقل: ' . $row['meta_key'], 'count' => $cnt ];
        }

        return $matches;
    }

    private function backup_post( int $post_id ): array {
        global $wpdb;
        $post  = get_post( $post_id );
        $metas = $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare( "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $post_id ),
            ARRAY_A
        );
        $meta_backup = [];
        foreach ( $metas as $m ) $meta_backup[ $m['meta_id'] ] = $m['meta_value'];

        return [
            'post_content' => $post->post_content,
            'meta'         => $meta_backup,
        ];
    }

    private function replace_in_post( int $post_id, array $old_variants, string $new_number ): bool {
        global $wpdb;
        $post    = get_post( $post_id );
        $changed = false;

        // Sort variants longest-first to avoid partial replacements
        usort( $old_variants, fn( $a, $b ) => strlen( $b ) - strlen( $a ) );
        $new_content = $post->post_content;

        // When deleting (empty new_number), do a URL pre-pass first:
        // strip entire WhatsApp/tel link attributes that contain any number variant
        if ( $new_number === '' ) {
            $new_content = $this->strip_contact_urls( $new_content, $old_variants );
        }

        foreach ( $old_variants as $variant ) {
            $new_content = $this->smart_replace( $new_content, $variant, $new_number );
        }
        if ( $new_content !== $post->post_content ) {
            wp_update_post( [ 'ID' => $post_id, 'post_content' => $new_content ] );
            $changed = true;
        }

        // Replace in post meta (safely handling serialized/JSON)
        $meta_rows = $wpdb->get_results( // phpcs:ignore
            $wpdb->prepare( "SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $post_id ),
            ARRAY_A
        );
        foreach ( $meta_rows as $row ) {
            $new_value = $this->replace_in_meta_value( $row['meta_value'], $old_variants, $new_number );
            if ( $new_value !== $row['meta_value'] ) {
                $wpdb->update( // phpcs:ignore
                    $wpdb->postmeta,
                    [ 'meta_value' => $new_value ],
                    [ 'meta_id' => (int) $row['meta_id'] ],
                    [ '%s' ],
                    [ '%d' ]
                );
                $changed = true;
            }
        }

        if ( $changed ) clean_post_cache( $post_id );
        return $changed;
    }

    /**
     * Replace in a meta value, handling serialized PHP and JSON safely.
     */
    private function replace_in_meta_value( string $raw, array $old_variants, string $new_number ): string {
        if ( is_serialized( $raw ) ) {
            $unserialized = @unserialize( $raw ); // phpcs:ignore
            if ( false === $unserialized ) return $raw; // Can't safely unserialize
            $modified = $this->replace_in_value( $unserialized, $old_variants, $new_number );
            $reserialized = serialize( $modified );
            return $reserialized;
        }

        if ( KayanCC_Helpers::is_json( $raw ) ) {
            $decoded  = json_decode( $raw, true );
            $modified = $this->replace_in_value( $decoded, $old_variants, $new_number );
            return wp_json_encode( $modified );
        }

        return $this->smart_replace( $raw, implode( '|', array_map( 'preg_quote', $old_variants, array_fill( 0, count( $old_variants ), '/' ) ) ), $new_number, true );
    }

    private function replace_in_value( $value, array $old_variants, string $new_number ) {
        if ( is_string( $value ) ) {
            foreach ( $old_variants as $v ) {
                $value = $this->smart_replace( $value, $v, $new_number );
            }
            return $value;
        }
        if ( is_array( $value ) ) {
            return array_map( fn( $v ) => $this->replace_in_value( $v, $old_variants, $new_number ), $value );
        }
        return $value;
    }

    /**
     * Strip full contact URL structures (wa.me, whatsapp.com, tel:) that contain any of the number variants.
     * Called as a pre-pass before individual variant replacement in deletion mode.
     */
    private function strip_contact_urls( string $content, array $variants ): string {
        foreach ( $variants as $variant ) {
            $escaped = preg_quote( $variant, '/' );
            // Full href="https://wa.me/..." or https://api.whatsapp.com/... containing the number
            $content = preg_replace( '/(?:href=["\'])?https?:\/\/(?:api\.whatsapp\.com|wa\.me|whatsapp\.com)[^\s"\'<>]*' . $escaped . '[^\s"\'<>]*(?:["\'])?/u', '', $content ) ?? $content;
            // tel: links
            $content = preg_replace( '/(?:href=["\'])?tel:[+\s\-.()\d]*' . $escaped . '[+\s\-.()\d]*(?:["\'])?/u', '', $content ) ?? $content;
        }
        return $content;
    }

    /**
     * String replacement ensuring we only replace whole-number occurrences (not fragments).
     */
    private function smart_replace( string $haystack, string $needle, string $replacement, bool $is_regex = false ): string {
        if ( $is_regex ) {
            return preg_replace( '/' . $needle . '/u', $replacement, $haystack ) ?? $haystack;
        }

        $escaped = preg_quote( $needle, '/' );
        // Replace standalone number occurrences (not embedded in larger numbers)
        $pattern = '/(?<![0-9+])' . $escaped . '(?![0-9])/u';
        return preg_replace( $pattern, $replacement, $haystack ) ?? $haystack;
    }
}
