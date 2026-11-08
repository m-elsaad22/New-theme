<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Optional REST API endpoints (for future use / headless integrations).
 * Secured with manage_options capability.
 */
class KayanCC_REST_API {

    private static ?self $instance = null;

    const NAMESPACE = 'kayan-cc/v1';

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        // Numbers
        register_rest_route( self::NAMESPACE, '/numbers', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_numbers' ],
            'permission_callback' => [ $this, 'check_permission' ],
        ] );

        // Resolve number for a post (used for front-end debugging)
        register_rest_route( self::NAMESPACE, '/resolve/(?P<post_id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'resolve_for_post' ],
            'permission_callback' => [ $this, 'check_permission' ],
            'args'                => [ 'post_id' => [ 'validate_callback' => 'is_numeric' ] ],
        ] );
    }

    public function check_permission(): bool {
        return KayanCC_Helpers::current_user_can_manage();
    }

    public function get_numbers( \WP_REST_Request $request ): \WP_REST_Response {
        $result = KayanCC_Phone_Manager::get_instance()->get_all([
            'page'     => (int) $request->get_param( 'page' ),
            'per_page' => (int) $request->get_param( 'per_page' ),
        ]);
        return new \WP_REST_Response( $result );
    }

    public function resolve_for_post( \WP_REST_Request $request ): \WP_REST_Response {
        $post_id  = (int) $request->get_param( 'post_id' );
        $resolved = KayanCC_Phone_Assignment::get_instance()->resolve_for_post( $post_id );
        return new \WP_REST_Response( $resolved ?? [ 'number' => null, 'conflicts' => [] ] );
    }
}
