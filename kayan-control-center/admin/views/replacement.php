<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<h2 class="kayan-cc-page-title">🔄 استبدال الأرقام</h2>

<div class="kayan-cc-notice kayan-cc-notice--warning">
    <strong>تحذير:</strong> يجب مراجعة المعاينة أولاً قبل التنفيذ.
    يتم حفظ نسخة احتياطية تلقائياً تسمح بالتراجع عن العملية من سجل التعديلات.
</div>

<div class="kayan-cc-replacement-form">
    <h3>بيانات الاستبدال</h3>
    <div class="kayan-cc-form-row kayan-cc-form-row--2col">
        <div>
            <label>الرقم القديم <span class="required">*</span></label>
            <input type="text" id="kayan-cc-old-number" class="kayan-cc-input kayan-cc-input--large" placeholder="0501234567 أو +971501234567">
        </div>
        <div>
            <label>الرقم الجديد <span class="required">*</span></label>
            <input type="text" id="kayan-cc-new-number" class="kayan-cc-input kayan-cc-input--large" placeholder="+971521234567">
        </div>
    </div>

    <div class="kayan-cc-form-row">
        <label>نطاق الاستبدال</label>
        <select id="kayan-cc-replace-scope" class="kayan-cc-input">
            <option value="all">جميع المقالات</option>
            <option value="taxonomy">تصنيف محدد</option>
            <option value="posts">مقالات محددة</option>
        </select>
    </div>

    <!-- Shown for taxonomy scope -->
    <div id="kayan-cc-replace-taxonomy-row" class="kayan-cc-form-row" style="display:none">
        <label>التصنيف</label>
        <select id="kayan-cc-replace-taxonomy" class="kayan-cc-input">
            <?php foreach ( get_taxonomies( [ 'public' => true ], 'objects' ) as $tax ) : ?>
                <option value="<?php echo esc_attr( $tax->name ); ?>"><?php echo esc_html( $tax->label ); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="kayan-cc-replace-term" class="kayan-cc-input" style="margin-top:8px">
            <option value="">-- اختر القسم --</option>
        </select>
        <label class="kayan-cc-checkbox" style="margin-top:8px">
            <input type="checkbox" id="kayan-cc-replace-include-children">
            تضمين التصنيفات الفرعية
        </label>
    </div>

    <div class="kayan-cc-form-actions">
        <button id="kayan-cc-replace-preview-btn" class="button button-primary button-large">
            🔍 معاينة قبل التنفيذ
        </button>
    </div>
</div>

<!-- Preview Results -->
<div id="kayan-cc-replace-preview-wrap" style="display:none">
    <div class="kayan-cc-section">
        <h3>نتائج المعاينة</h3>
        <div id="kayan-cc-replace-preview-summary" class="kayan-cc-summary-box"></div>
        <div id="kayan-cc-replace-preview-table"></div>

        <div class="kayan-cc-form-actions" style="margin-top:20px">
            <button id="kayan-cc-replace-execute-btn" class="button button-primary button-large kayan-cc-btn--danger">
                ⚠ تنفيذ الاستبدال
            </button>
            <span class="kayan-cc-note">لن يتم التنفيذ إلا بعد الضغط على هذا الزر</span>
        </div>
    </div>
</div>

<!-- Executed Result -->
<div id="kayan-cc-replace-result" style="display:none">
    <div class="kayan-cc-notice kayan-cc-notice--success">
        <strong>تم تنفيذ الاستبدال بنجاح.</strong>
        <span id="kayan-cc-replace-result-text"></span>
        يمكنك التراجع عن العملية من
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=kayan-cc-activity-log' ) ); ?>">سجل التعديلات</a>.
    </div>
</div>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
