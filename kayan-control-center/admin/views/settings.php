<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<h2 class="kayan-cc-page-title">⚙ إعدادات الإضافة</h2>

<?php
if ( isset( $_POST['kayan_cc_settings_nonce'] ) && wp_verify_nonce( $_POST['kayan_cc_settings_nonce'], 'kayan_cc_settings' ) ) {
    if ( KayanCC_Helpers::current_user_can_manage() ) {
        update_option( 'kayan_cc_enable_frontend_filter', ! empty( $_POST['enable_frontend_filter'] ) ? 1 : 0 );
        update_option( 'kayan_cc_debug_mode', ! empty( $_POST['debug_mode'] ) ? 1 : 0 );
        echo '<div class="kayan-cc-notice kayan-cc-notice--success">تم حفظ الإعدادات.</div>';
    }
}
$enable_filter = (int) get_option( 'kayan_cc_enable_frontend_filter', 1 );
$debug_mode    = (int) get_option( 'kayan_cc_debug_mode', 0 );
?>

<form method="post">
    <?php wp_nonce_field( 'kayan_cc_settings', 'kayan_cc_settings_nonce' ); ?>

    <div class="kayan-cc-section">
        <h3>إعدادات الواجهة الأمامية</h3>

        <div class="kayan-cc-form-row">
            <label class="kayan-cc-checkbox">
                <input type="checkbox" name="enable_frontend_filter" value="1" <?php checked( $enable_filter, 1 ); ?>>
                تفعيل تصفية أرقام الهاتف في الواجهة الأمامية
            </label>
            <p class="kayan-cc-form-hint">
                عند التفعيل، تقوم الإضافة بتصفية أرقام الهاتف التي يعرضها قالب KAYAN وفق التعيينات المحددة.
                إذا لم تكن التصفية تعمل بشكل صحيح مع قالبك، يمكنك إيقافها.
            </p>
        </div>
    </div>

    <div class="kayan-cc-section">
        <h3>إعدادات التطوير</h3>
        <div class="kayan-cc-form-row">
            <label class="kayan-cc-checkbox">
                <input type="checkbox" name="debug_mode" value="1" <?php checked( $debug_mode, 1 ); ?>>
                وضع التصحيح (Debug Mode)
            </label>
            <p class="kayan-cc-form-hint">
                يُظهر رقم الاتصال المُحلّ لكل مقالة في الواجهة الأمامية لمستخدمي الأدمن فقط.
                أوقفه في الإنتاج.
            </p>
        </div>
    </div>

    <div class="kayan-cc-section">
        <h3>معلومات البيئة</h3>
        <table class="kayan-cc-table kayan-cc-table--info">
            <tbody>
                <tr><td>إصدار WordPress</td><td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td></tr>
                <tr><td>إصدار PHP</td><td><?php echo esc_html( PHP_VERSION ); ?></td></tr>
                <tr><td>إصدار KAYAN CC</td><td><?php echo esc_html( KAYAN_CC_VERSION ); ?></td></tr>
                <tr>
                    <td>Rank Math</td>
                    <td>
                        <?php
                        if ( KayanCC_SEO_Manager::get_instance()->is_rank_math_active() ) {
                            echo esc_html( 'مفعل - ' . ( defined( 'RANK_MATH_VERSION' ) ? RANK_MATH_VERSION : 'إصدار غير معروف' ) );
                        } else {
                            echo 'غير مفعل';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>إصدار القالب</td>
                    <td><?php $theme = wp_get_theme(); echo esc_html( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) ); ?></td>
                </tr>
                <tr><td>حالة قاعدة البيانات</td><td><?php echo esc_html( get_option( KayanCC_Database::DB_VERSION_OPTION, 'غير مثبتة' ) ); ?></td></tr>
            </tbody>
        </table>
    </div>

    <div class="kayan-cc-section">
        <h3>إدارة قاعدة البيانات</h3>
        <p class="kayan-cc-form-hint">
            إذا كانت الجداول تالفة أو بحاجة إلى تحديث، انقر على الزر التالي لإعادة تثبيت الجداول.
            لن يتم حذف أي بيانات موجودة.
        </p>
        <button type="button" id="kayan-cc-reinstall-db" class="button button-secondary">
            إعادة تثبيت جداول قاعدة البيانات
        </button>
        <span id="kayan-cc-reinstall-db-result" style="margin-right:10px"></span>
    </div>

    <div class="kayan-cc-form-actions">
        <input type="submit" class="button button-primary button-large" value="حفظ الإعدادات">
    </div>
</form>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
