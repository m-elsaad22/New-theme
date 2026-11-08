<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<div class="kayan-cc-dashboard">
    <h2 class="kayan-cc-page-title">لوحة المعلومات</h2>

    <div class="kayan-cc-cards">
        <?php
        global $wpdb;
        $num_table    = KayanCC_Database::get_numbers_table();
        $assign_table = KayanCC_Database::get_assignments_table();
        $log_table    = KayanCC_Database::get_log_table();

        $total_numbers   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$num_table}" );
        $active_numbers  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$num_table} WHERE status='active'" );
        $total_assign    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$assign_table}" );
        $total_log       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$log_table}" );
        $scan_status     = get_option( 'kayan_cc_scan_status', 'idle' );
        $rm_active       = KayanCC_SEO_Manager::get_instance()->is_rank_math_active();
        ?>

        <div class="kayan-cc-card">
            <div class="kayan-cc-card__icon">📞</div>
            <div class="kayan-cc-card__title">إجمالي الأرقام</div>
            <div class="kayan-cc-card__value"><?php echo esc_html( $total_numbers ); ?></div>
            <div class="kayan-cc-card__sub"><?php echo esc_html( $active_numbers ); ?> نشط</div>
        </div>

        <div class="kayan-cc-card">
            <div class="kayan-cc-card__icon">📋</div>
            <div class="kayan-cc-card__title">التعيينات</div>
            <div class="kayan-cc-card__value"><?php echo esc_html( $total_assign ); ?></div>
            <div class="kayan-cc-card__sub">تعيين نشط</div>
        </div>

        <div class="kayan-cc-card">
            <div class="kayan-cc-card__icon">🔍</div>
            <div class="kayan-cc-card__title">حالة الفحص</div>
            <div class="kayan-cc-card__value">
                <?php
                $scan_labels = [ 'idle' => 'لم يبدأ', 'running' => 'جارٍ...', 'complete' => 'مكتمل' ];
                echo esc_html( $scan_labels[ $scan_status ] ?? $scan_status );
                ?>
            </div>
            <div class="kayan-cc-card__sub">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kayan-cc-scanner' ) ); ?>">ابدأ الفحص</a>
            </div>
        </div>

        <div class="kayan-cc-card">
            <div class="kayan-cc-card__icon">🔎</div>
            <div class="kayan-cc-card__title">Rank Math</div>
            <div class="kayan-cc-card__value" style="color: <?php echo $rm_active ? '#4CAF50' : '#f44336'; ?>">
                <?php echo $rm_active ? 'مفعل' : 'غير مفعل'; ?>
            </div>
            <div class="kayan-cc-card__sub"><?php echo $rm_active ? 'متوافق مع SEO Manager' : 'تأكد من تثبيت Rank Math'; ?></div>
        </div>

        <div class="kayan-cc-card">
            <div class="kayan-cc-card__icon">📋</div>
            <div class="kayan-cc-card__title">سجل التعديلات</div>
            <div class="kayan-cc-card__value"><?php echo esc_html( $total_log ); ?></div>
            <div class="kayan-cc-card__sub">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kayan-cc-activity-log' ) ); ?>">عرض السجل</a>
            </div>
        </div>
    </div>

    <div class="kayan-cc-quick-actions">
        <h3>إجراءات سريعة</h3>
        <div class="kayan-cc-quick-actions__buttons">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=kayan-cc-scanner' ) ); ?>" class="button button-primary">🔍 فحص أرقام الموقع</a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=kayan-cc-phone-manager' ) ); ?>" class="button button-secondary">+ إضافة رقم جديد</a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=kayan-cc-seo-manager' ) ); ?>" class="button button-secondary">✏ إدارة عناوين SEO</a>
        </div>
    </div>

    <div class="kayan-cc-recent-log">
        <h3>آخر التعديلات</h3>
        <?php
        $recent = KayanCC_Activity_Log::get_list( [ 'per_page' => 5 ] );
        if ( empty( $recent['rows'] ) ) : ?>
            <p class="kayan-cc-empty">لا توجد تعديلات حتى الآن.</p>
        <?php else : ?>
            <table class="kayan-cc-table">
                <thead>
                    <tr>
                        <th>العملية</th><th>المستخدم</th><th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $recent['rows'] as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( KayanCC_Activity_Log::get_action_label( $row['action'] ) ); ?></td>
                        <td><?php echo esc_html( $row['user_display'] ); ?></td>
                        <td><?php echo esc_html( $row['created_at'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
