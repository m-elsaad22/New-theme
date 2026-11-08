<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin menu, pages, and asset enqueuing.
 */
class KayanCC_Admin {

    private static ?self $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init(): void {
        add_action( 'admin_menu',            [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    // ─── Menu registration ────────────────────────────────────────────────────

    public function register_menus(): void {
        add_menu_page(
            'KAYAN Control Center',
            'KAYAN CC',
            'manage_options',
            'kayan-control-center',
            [ $this, 'render_dashboard' ],
            'dashicons-phone',
            30
        );

        $subpages = [
            [ 'kayan-control-center',          'لوحة المعلومات',     'render_dashboard'      ],
            [ 'kayan-cc-scanner',              'اكتشاف الأرقام',     'render_scanner'        ],
            [ 'kayan-cc-phone-manager',        'إدارة الأرقام',      'render_phone_manager'  ],
            [ 'kayan-cc-phone-assignment',     'توزيع الأرقام',      'render_assignment'     ],
            [ 'kayan-cc-phone-replacement',    'استبدال الأرقام',    'render_replacement'    ],
            [ 'kayan-cc-seo-manager',          'عناوين SEO',         'render_seo_manager'    ],
            [ 'kayan-cc-seo-templates',        'قوالب SEO',          'render_seo_templates'  ],
            [ 'kayan-cc-activity-log',         'سجل التعديلات',      'render_activity_log'   ],
            [ 'kayan-cc-settings',             'الإعدادات',          'render_settings'       ],
        ];

        foreach ( $subpages as [ $slug, $title, $callback ] ) {
            add_submenu_page(
                'kayan-control-center',
                $title . ' - KAYAN CC',
                $title,
                'manage_options',
                $slug,
                [ $this, $callback ]
            );
        }
    }

    // ─── Assets ───────────────────────────────────────────────────────────────

    public function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'kayan-cc' ) === false && strpos( $hook, 'kayan-control-center' ) === false ) return;

        wp_enqueue_style(
            'kayan-cc-admin',
            KAYAN_CC_PLUGIN_URL . 'assets/css/admin.css',
            [],
            KAYAN_CC_VERSION
        );

        wp_enqueue_script(
            'kayan-cc-admin',
            KAYAN_CC_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery', 'wp-i18n' ],
            KAYAN_CC_VERSION,
            true
        );

        wp_localize_script( 'kayan-cc-admin', 'KayanCC', [
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'kayan_cc_nonce' ),
            'rank_math_active'=> KayanCC_SEO_Manager::get_instance()->is_rank_math_active() ? 1 : 0,
            'i18n'            => [
                'confirm_delete'      => 'هل أنت متأكد من الحذف؟',
                'confirm_replace'     => 'هل تريد تنفيذ الاستبدال؟ لا يمكن التراجع إلا من سجل التعديلات.',
                'confirm_bulk_seo'    => 'هل تريد تطبيق عناوين SEO على جميع المقالات المختارة؟',
                'loading'             => 'جارٍ التحميل...',
                'saved'               => 'تم الحفظ بنجاح.',
                'deleted'             => 'تم الحذف بنجاح.',
                'error'               => 'حدث خطأ، يرجى المحاولة مجدداً.',
                'scan_started'        => 'بدأ الفحص...',
                'scan_complete'       => 'اكتمل الفحص.',
                'no_results'          => 'لا توجد نتائج.',
            ],
        ] );
    }

    // ─── Page renderers ───────────────────────────────────────────────────────

    private function render_page( string $view ): void {
        if ( ! KayanCC_Helpers::current_user_can_manage() ) {
            wp_die( 'غير مصرح لك.' );
        }
        $file = KAYAN_CC_PLUGIN_DIR . 'admin/views/' . $view . '.php';
        if ( file_exists( $file ) ) {
            include $file;
        }
    }

    public function render_dashboard():     void { $this->render_page( 'dashboard' ); }
    public function render_scanner():       void { $this->render_page( 'scanner' ); }
    public function render_phone_manager(): void { $this->render_page( 'phone-manager' ); }
    public function render_assignment():    void { $this->render_page( 'assignment' ); }
    public function render_replacement():   void { $this->render_page( 'replacement' ); }
    public function render_seo_manager():   void { $this->render_page( 'seo-manager' ); }
    public function render_seo_templates(): void { $this->render_page( 'seo-templates' ); }
    public function render_activity_log():  void { $this->render_page( 'activity-log' ); }
    public function render_settings():      void { $this->render_page( 'settings' ); }
}
