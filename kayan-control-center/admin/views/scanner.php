<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<h2 class="kayan-cc-page-title">🔍 اكتشاف الأرقام</h2>

<div class="kayan-cc-notice kayan-cc-notice--info">
    <strong>مهم:</strong> الفحص يقرأ المحتوى فقط ولا يعدّل أي بيانات.
    يتم الفحص على دفعات لتجنب استهلاك الموارد.
</div>

<div class="kayan-cc-scanner-controls">
    <button id="kayan-cc-start-scan" class="button button-primary button-large">
        🔍 بدء فحص الموقع
    </button>
    <span id="kayan-cc-scan-status-text" class="kayan-cc-scan-status">
        <?php
        $status = get_option( 'kayan_cc_scan_status', 'idle' );
        $labels = [ 'idle' => 'لم يبدأ الفحص بعد', 'running' => 'جارٍ الفحص...', 'complete' => 'اكتمل الفحص' ];
        echo esc_html( $labels[ $status ] ?? $status );
        ?>
    </span>
</div>

<div id="kayan-cc-scan-progress" class="kayan-cc-progress" style="display:none">
    <div class="kayan-cc-progress__bar">
        <div class="kayan-cc-progress__fill" id="kayan-cc-progress-fill"></div>
    </div>
    <p id="kayan-cc-progress-text">جارٍ الفحص...</p>
</div>

<?php if ( $status === 'complete' ) : ?>
<div id="kayan-cc-scan-import-banner">
    <?php
    global $wpdb;
    $scan_table  = KayanCC_Database::get_scan_table();
    $confirmed   = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT normalized) FROM {$scan_table} WHERE result_type IN ('phone_confirmed','whatsapp_confirmed') AND normalized != ''" );
    $all_results = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$scan_table}" );
    ?>
    <div class="kayan-cc-import-banner">
        <div>
            <h3>✅ اكتمل الفحص</h3>
            <p>تم اكتشاف <strong><?php echo esc_html( $confirmed ); ?></strong> رقم مؤكد من أصل <?php echo esc_html( $all_results ); ?> نتيجة إجمالية.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <button id="kayan-cc-import-to-manager" class="button">
                📥 استيراد الأرقام إلى مدير الأرقام
            </button>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=kayan-cc-phone-manager' ) ); ?>" class="button">
                📞 فتح مدير الأرقام
            </a>
        </div>
    </div>
    <div id="kayan-cc-import-result" class="kayan-cc-import-results" style="display:none"></div>
</div>
<?php else : ?>
<div id="kayan-cc-scan-import-banner" style="display:none">
    <!-- populated by JS after scan completes -->
</div>
<?php endif; ?>

<div id="kayan-cc-scan-results-wrap" style="<?php echo $status !== 'complete' ? 'display:none' : ''; ?>">
    <div class="kayan-cc-results-header">
        <h3>نتائج الفحص</h3>
        <div class="kayan-cc-results-filters">
            <select id="kayan-cc-filter-type">
                <option value="">جميع الأنواع</option>
                <option value="phone_confirmed">أرقام هاتف مؤكدة</option>
                <option value="whatsapp_confirmed">أرقام واتساب مؤكدة</option>
                <option value="phone_probable">أرقام محتملة</option>
                <option value="call_link">روابط اتصال</option>
                <option value="whatsapp_link">روابط واتساب</option>
                <option value="duplicate">مكررة</option>
            </select>
        </div>
    </div>

    <div id="kayan-cc-scan-results-table">
        <!-- Loaded via JS -->
        <p class="kayan-cc-loading">جارٍ تحميل النتائج...</p>
    </div>
    <div id="kayan-cc-scan-pagination" class="kayan-cc-pagination"></div>
</div>

<script type="text/template" id="tmpl-scan-results">
<table class="kayan-cc-table kayan-cc-table--scan">
    <thead>
        <tr>
            <th>القيمة المكتشفة</th>
            <th>الرقم الموحد</th>
            <th>النوع</th>
            <th>المصدر</th>
            <th>رقم المقالة</th>
        </tr>
    </thead>
    <tbody>
        {{#rows}}
        <tr>
            <td class="kayan-cc-monospace">{{raw_value}}</td>
            <td class="kayan-cc-monospace">{{normalized}}</td>
            <td><span class="kayan-cc-badge kayan-cc-badge--{{result_type}}">{{result_type_label}}</span></td>
            <td>{{source_field}}</td>
            <td>{{source_id}}</td>
        </tr>
        {{/rows}}
    </tbody>
</table>
</script>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
