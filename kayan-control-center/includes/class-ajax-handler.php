<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles all WordPress AJAX calls for the admin UI.
 */
class KayanCC_Ajax_Handler {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->register_hooks();
        }
        return self::$instance;
    }

    private function register_hooks(): void {
        $actions = [
            // Phone Manager
            'kayan_cc_get_numbers',
            'kayan_cc_add_number',
            'kayan_cc_update_number',
            'kayan_cc_delete_number',
            'kayan_cc_toggle_number',
            'kayan_cc_hard_delete_number',
            'kayan_cc_get_number_locations',
            'kayan_cc_delete_from_location',
            // Scanner
            'kayan_cc_start_scan',
            'kayan_cc_scan_batch',
            'kayan_cc_scan_status',
            'kayan_cc_get_scan_results',
            'kayan_cc_import_from_scan',
            // Assignment
            'kayan_cc_get_assignments',
            'kayan_cc_assign_number',
            'kayan_cc_remove_assignment',
            'kayan_cc_bulk_assign',
            'kayan_cc_set_site_default',
            // Replacement
            'kayan_cc_replace_preview',
            'kayan_cc_replace_execute',
            'kayan_cc_replace_rollback',
            // SEO
            'kayan_cc_get_seo_posts',
            'kayan_cc_set_seo_title',
            'kayan_cc_clear_seo_title',
            'kayan_cc_rollback_seo_title',
            'kayan_cc_seo_bulk_preview',
            'kayan_cc_seo_bulk_apply',
            // SEO Templates
            'kayan_cc_get_seo_templates',
            'kayan_cc_add_seo_template',
            'kayan_cc_update_seo_template',
            'kayan_cc_delete_seo_template',
            'kayan_cc_apply_template_preview',
            'kayan_cc_apply_template_execute',
            // Activity Log
            'kayan_cc_get_activity_log',
            // Export
            'kayan_cc_export_numbers_csv',
        ];

        foreach ( $actions as $action ) {
            add_action( 'wp_ajax_' . $action, [ $this, 'handle_' . $action ] );
        }
    }

    // ─── Utility ──────────────────────────────────────────────────────────────

    private function verify_nonce(): void {
        check_ajax_referer( 'kayan_cc_nonce', 'nonce' );
        if ( ! KayanCC_Helpers::current_user_can_manage() ) {
            wp_send_json_error( [ 'message' => 'غير مصرح لك بهذا الإجراء.' ], 403 );
        }
    }

    private function success( $data ): void {
        wp_send_json_success( $data );
    }

    private function error( string $msg, int $code = 400 ): void {
        wp_send_json_error( [ 'message' => $msg ], $code );
    }

    private function wp_error( \WP_Error $e ): void {
        $this->error( $e->get_error_message() );
    }

    // ─── Phone Manager ────────────────────────────────────────────────────────

    public function handle_kayan_cc_get_numbers(): void {
        $this->verify_nonce();
        $per_page = min( 200, max( 5, (int) ( $_GET['per_page'] ?? 20 ) ) );
        $args = [
            'page'     => (int) ( $_GET['page'] ?? 1 ),
            'per_page' => $per_page,
            'search'   => sanitize_text_field( $_GET['search'] ?? '' ),
            'status'   => sanitize_text_field( $_GET['status'] ?? '' ),
        ];
        $manager = KayanCC_Phone_Manager::get_instance();
        $result  = $manager->get_all( $args );
        foreach ( $result['numbers'] as &$n ) {
            $n['usage_count']   = $manager->get_usage_count( (int) $n['id'] );
            $stats              = $manager->get_occurrence_stats( $n['phone_e164'], $n['phone_number'] );
            $n['occurrences']   = $stats['total_occurrences'];
            $n['unique_posts']  = $stats['unique_posts'];
        }
        $this->success( $result );
    }

    public function handle_kayan_cc_hard_delete_number(): void {
        $this->verify_nonce();
        $id     = (int) ( $_POST['id'] ?? 0 );
        $result = KayanCC_Phone_Manager::get_instance()->hard_delete( $id );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( $result );
    }

    public function handle_kayan_cc_get_number_locations(): void {
        $this->verify_nonce();
        $number_id = (int) ( $_GET['number_id'] ?? 0 );
        $number    = KayanCC_Phone_Manager::get_instance()->get( $number_id );
        if ( ! $number ) { $this->error( 'الرقم غير موجود.' ); return; }

        $scanner   = new KayanCC_Phone_Scanner();
        $locations = $scanner->get_number_locations( $number['phone_e164'] );

        // Also check whatsapp if different
        if ( $number['whatsapp_e164'] && $number['whatsapp_e164'] !== $number['phone_e164'] ) {
            $wa_locs = $scanner->get_number_locations( $number['whatsapp_e164'] );
            foreach ( $wa_locs as $type => $rows ) {
                if ( isset( $locations[ $type ] ) ) {
                    $locations[ $type ] = array_merge( $locations[ $type ], $rows );
                } else {
                    $locations[ $type ] = $rows;
                }
            }
        }

        $this->success( [
            'number'    => $number,
            'locations' => $locations,
        ] );
    }

    public function handle_kayan_cc_delete_from_location(): void {
        $this->verify_nonce();
        $number_id   = (int) ( $_POST['number_id']   ?? 0 );
        $source_type = sanitize_text_field( $_POST['source_type'] ?? '' );
        $source_id   = (int) ( $_POST['source_id']   ?? 0 );
        $source_field= sanitize_text_field( $_POST['source_field'] ?? '' );

        $number = KayanCC_Phone_Manager::get_instance()->get( $number_id );
        if ( ! $number ) { $this->error( 'الرقم غير موجود.' ); return; }

        $result = KayanCC_Phone_Manager::get_instance()->delete_from_location(
            $number['phone_e164'],
            $source_type,
            $source_id,
            $source_field
        );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'deleted' => true ] );
    }

    public function handle_kayan_cc_import_from_scan(): void {
        $this->verify_nonce();
        $scanner = new KayanCC_Phone_Scanner();
        $result  = $scanner->import_to_manager();
        $this->success( $result );
    }

    public function handle_kayan_cc_add_number(): void {
        $this->verify_nonce();
        $data   = array_map( 'sanitize_text_field', $_POST );
        $result = KayanCC_Phone_Manager::get_instance()->create( $data );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'id' => $result ] );
    }

    public function handle_kayan_cc_update_number(): void {
        $this->verify_nonce();
        $id     = (int) ( $_POST['id'] ?? 0 );
        $data   = array_map( 'sanitize_text_field', $_POST );
        $result = KayanCC_Phone_Manager::get_instance()->update( $id, $data );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'updated' => true ] );
    }

    public function handle_kayan_cc_delete_number(): void {
        $this->verify_nonce();
        $id     = (int) ( $_POST['id'] ?? 0 );
        $result = KayanCC_Phone_Manager::get_instance()->delete( $id );
        $result ? $this->success( [ 'deleted' => true ] ) : $this->error( 'الرقم غير موجود.' );
    }

    public function handle_kayan_cc_toggle_number(): void {
        $this->verify_nonce();
        $id     = (int) ( $_POST['id'] ?? 0 );
        $result = KayanCC_Phone_Manager::get_instance()->toggle_status( $id );
        $result ? $this->success( [ 'toggled' => true ] ) : $this->error( 'الرقم غير موجود.' );
    }

    // ─── Scanner ──────────────────────────────────────────────────────────────

    public function handle_kayan_cc_start_scan(): void {
        $this->verify_nonce();
        $scanner  = new KayanCC_Phone_Scanner();
        $batch_id = $scanner->start_scan();
        $this->success( [ 'batch_id' => $batch_id ] );
    }

    public function handle_kayan_cc_scan_batch(): void {
        $this->verify_nonce();
        $scanner = new KayanCC_Phone_Scanner();
        $done    = $scanner->process_batch();
        $this->success( [ 'done' => $done, 'status' => $scanner->get_status() ] );
    }

    public function handle_kayan_cc_scan_status(): void {
        $this->verify_nonce();
        $scanner = new KayanCC_Phone_Scanner();
        $this->success( $scanner->get_status() );
    }

    public function handle_kayan_cc_get_scan_results(): void {
        $this->verify_nonce();
        $scanner = new KayanCC_Phone_Scanner();
        $result  = $scanner->get_results(
            (int) ( $_GET['page']     ?? 1 ),
            (int) ( $_GET['per_page'] ?? 50 )
        );
        $this->success( $result );
    }

    // ─── Assignment ───────────────────────────────────────────────────────────

    public function handle_kayan_cc_get_assignments(): void {
        $this->verify_nonce();
        $args   = [ 'number_id' => (int) ( $_GET['number_id'] ?? 0 ), 'scope_type' => sanitize_text_field( $_GET['scope_type'] ?? '' ) ];
        $result = KayanCC_Phone_Assignment::get_instance()->get_all_assignments( $args );
        $this->success( $result );
    }

    public function handle_kayan_cc_assign_number(): void {
        $this->verify_nonce();
        $result = KayanCC_Phone_Assignment::get_instance()->assign(
            (int) ( $_POST['number_id']      ?? 0 ),
            sanitize_text_field( $_POST['scope_type']     ?? '' ),
            (int) ( $_POST['scope_id']       ?? 0 ),
            sanitize_text_field( $_POST['scope_taxonomy'] ?? '' )
        );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'assigned' => true ] );
    }

    public function handle_kayan_cc_remove_assignment(): void {
        $this->verify_nonce();
        $id     = (int) ( $_POST['assignment_id'] ?? 0 );
        $result = KayanCC_Phone_Assignment::get_instance()->remove_assignment( $id );
        $result ? $this->success( [ 'removed' => true ] ) : $this->error( 'التعيين غير موجود.' );
    }

    public function handle_kayan_cc_bulk_assign(): void {
        $this->verify_nonce();
        $number_id = (int) ( $_POST['number_id'] ?? 0 );
        $post_ids  = array_map( 'intval', (array) ( $_POST['post_ids'] ?? [] ) );
        $result    = KayanCC_Phone_Assignment::get_instance()->bulk_assign_posts( $number_id, $post_ids );
        $this->success( $result );
    }

    public function handle_kayan_cc_set_site_default(): void {
        $this->verify_nonce();
        $number_id = (int) ( $_POST['number_id'] ?? 0 );
        $result    = KayanCC_Phone_Assignment::get_instance()->set_site_default( $number_id );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'set' => true ] );
    }

    // ─── Replacement ──────────────────────────────────────────────────────────

    public function handle_kayan_cc_replace_preview(): void {
        $this->verify_nonce();
        $old_number = sanitize_text_field( $_POST['old_number'] ?? '' );
        $new_number = sanitize_text_field( $_POST['new_number'] ?? '' );
        $scope      = $this->parse_scope( $_POST );
        $result     = ( new KayanCC_Phone_Replacement() )->preview( $old_number, $new_number, $scope );
        isset( $result['error'] ) ? $this->error( $result['error'] ) : $this->success( $result );
    }

    public function handle_kayan_cc_replace_execute(): void {
        $this->verify_nonce();
        $old_number = sanitize_text_field( $_POST['old_number'] ?? '' );
        $new_number = sanitize_text_field( $_POST['new_number'] ?? '' );
        $scope      = $this->parse_scope( $_POST );
        $result     = ( new KayanCC_Phone_Replacement() )->execute( $old_number, $new_number, $scope );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( $result );
    }

    public function handle_kayan_cc_replace_rollback(): void {
        $this->verify_nonce();
        $log_id = (int) ( $_POST['log_id'] ?? 0 );
        $result = ( new KayanCC_Phone_Replacement() )->rollback( $log_id );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'rolled_back' => true ] );
    }

    private function parse_scope( array $data ): array {
        return [
            'type'             => sanitize_text_field( $data['scope_type'] ?? 'all' ),
            'ids'              => array_map( 'intval', (array) ( $data['post_ids'] ?? [] ) ),
            'taxonomy'         => sanitize_text_field( $data['taxonomy'] ?? '' ),
            'term_id'          => (int) ( $data['term_id'] ?? 0 ),
            'include_children' => ! empty( $data['include_children'] ),
        ];
    }

    // ─── SEO ──────────────────────────────────────────────────────────────────

    public function handle_kayan_cc_get_seo_posts(): void {
        $this->verify_nonce();
        $per_page = min( 200, max( 5, (int) ( $_GET['per_page'] ?? 20 ) ) );
        $args   = [
            'page'      => (int) ( $_GET['page']      ?? 1 ),
            'per_page'  => $per_page,
            'search'    => sanitize_text_field( $_GET['search'] ?? '' ),
            'post_type' => sanitize_text_field( $_GET['post_type'] ?? '' ) ?: null,
        ];
        $result = KayanCC_SEO_Manager::get_instance()->get_posts_list( $args );
        $this->success( $result );
    }

    public function handle_kayan_cc_set_seo_title(): void {
        $this->verify_nonce();
        $post_id = (int) ( $_POST['post_id'] ?? 0 );
        $title   = sanitize_text_field( $_POST['seo_title'] ?? '' );
        $result  = KayanCC_SEO_Manager::get_instance()->set_seo_title( $post_id, $title );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'saved' => true ] );
    }

    public function handle_kayan_cc_clear_seo_title(): void {
        $this->verify_nonce();
        $post_id = (int) ( $_POST['post_id'] ?? 0 );
        KayanCC_SEO_Manager::get_instance()->clear_seo_title( $post_id );
        $this->success( [ 'cleared' => true ] );
    }

    public function handle_kayan_cc_rollback_seo_title(): void {
        $this->verify_nonce();
        $post_id = (int) ( $_POST['post_id'] ?? 0 );
        $result  = KayanCC_SEO_Manager::get_instance()->rollback_seo_title( $post_id );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'rolled_back' => true ] );
    }

    public function handle_kayan_cc_seo_bulk_preview(): void {
        $this->verify_nonce();
        $post_ids = array_map( 'intval', (array) ( $_POST['post_ids'] ?? [] ) );
        $template = sanitize_text_field( $_POST['template'] ?? '' );
        $result   = KayanCC_SEO_Manager::get_instance()->preview_bulk( $post_ids, $template );
        $this->success( $result );
    }

    public function handle_kayan_cc_seo_bulk_apply(): void {
        $this->verify_nonce();
        $post_ids = array_map( 'intval', (array) ( $_POST['post_ids'] ?? [] ) );
        $template = sanitize_text_field( $_POST['template'] ?? '' );
        $result   = KayanCC_SEO_Manager::get_instance()->bulk_apply( $post_ids, $template );
        $this->success( $result );
    }

    // ─── SEO Templates ────────────────────────────────────────────────────────

    public function handle_kayan_cc_get_seo_templates(): void {
        $this->verify_nonce();
        $this->success( KayanCC_SEO_Templates::get_instance()->get_all() );
    }

    public function handle_kayan_cc_add_seo_template(): void {
        $this->verify_nonce();
        $result = KayanCC_SEO_Templates::get_instance()->create( $_POST );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'id' => $result ] );
    }

    public function handle_kayan_cc_update_seo_template(): void {
        $this->verify_nonce();
        $id     = (int) ( $_POST['id'] ?? 0 );
        $result = KayanCC_SEO_Templates::get_instance()->update( $id, $_POST );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( [ 'updated' => true ] );
    }

    public function handle_kayan_cc_delete_seo_template(): void {
        $this->verify_nonce();
        $id     = (int) ( $_POST['id'] ?? 0 );
        $result = KayanCC_SEO_Templates::get_instance()->delete( $id );
        $result ? $this->success( [ 'deleted' => true ] ) : $this->error( 'القالب غير موجود.' );
    }

    public function handle_kayan_cc_apply_template_preview(): void {
        $this->verify_nonce();
        $template_id = (int) ( $_POST['template_id'] ?? 0 );
        $post_ids    = array_map( 'intval', (array) ( $_POST['post_ids'] ?? [] ) );
        $result      = KayanCC_SEO_Templates::get_instance()->apply_template_preview( $template_id, $post_ids );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( $result );
    }

    public function handle_kayan_cc_apply_template_execute(): void {
        $this->verify_nonce();
        $template_id = (int) ( $_POST['template_id'] ?? 0 );
        $post_ids    = array_map( 'intval', (array) ( $_POST['post_ids'] ?? [] ) );
        $result      = KayanCC_SEO_Templates::get_instance()->apply_template_execute( $template_id, $post_ids );
        is_wp_error( $result ) ? $this->wp_error( $result ) : $this->success( $result );
    }

    // ─── Activity Log ─────────────────────────────────────────────────────────

    public function handle_kayan_cc_get_activity_log(): void {
        $this->verify_nonce();
        $args   = [
            'page'        => (int) ( $_GET['page']        ?? 1 ),
            'per_page'    => (int) ( $_GET['per_page']    ?? 30 ),
            'action'      => sanitize_text_field( $_GET['action_filter'] ?? '' ),
            'object_type' => sanitize_text_field( $_GET['object_type']   ?? '' ),
        ];
        $result = KayanCC_Activity_Log::get_list( $args );
        foreach ( $result['rows'] as &$row ) {
            $row['action_label'] = KayanCC_Activity_Log::get_action_label( $row['action'] );
        }
        $this->success( $result );
    }

    // ─── Export ───────────────────────────────────────────────────────────────

    public function handle_kayan_cc_export_numbers_csv(): void {
        $this->verify_nonce();
        $manager = KayanCC_Phone_Manager::get_instance();
        $all     = $manager->get_all( [ 'per_page' => 9999 ] )['numbers'];

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=kayan-cc-numbers-' . date( 'Y-m-d' ) . '.csv' );
        $out = fopen( 'php://output', 'w' );
        fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ); // UTF-8 BOM
        fputcsv( $out, [ 'الاسم', 'رقم الاتصال', 'E164 الاتصال', 'رقم واتساب', 'E164 واتساب', 'الدولة', 'الحالة', 'تاريخ الإضافة' ] );
        foreach ( $all as $row ) {
            fputcsv( $out, [
                $row['name'], $row['phone_number'], $row['phone_e164'],
                $row['whatsapp_number'], $row['whatsapp_e164'],
                $row['country'], $row['status'], $row['created_at'],
            ] );
        }
        fclose( $out );
        exit;
    }
}
