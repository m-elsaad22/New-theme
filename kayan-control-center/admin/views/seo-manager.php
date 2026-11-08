<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<h2 class="kayan-cc-page-title">🔎 إدارة عناوين SEO</h2>

<?php if ( ! KayanCC_SEO_Manager::get_instance()->is_rank_math_active() ) : ?>
    <div class="kayan-cc-notice kayan-cc-notice--error">
        <strong>تحذير:</strong> إضافة Rank Math غير مفعلة.
        لا يمكن حفظ عناوين SEO حتى يتم تفعيلها.
    </div>
<?php else : ?>
    <div class="kayan-cc-notice kayan-cc-notice--success">
        ✓ العناوين تُحفظ في حقل Rank Math الرسمي (<code>rank_math_title</code>) دون التأثير على إعدادات Rank Math الأخرى.
    </div>
<?php endif; ?>

<div class="kayan-cc-toolbar">
    <div class="kayan-cc-toolbar__search">
        <input type="text" id="kayan-cc-seo-search" placeholder="بحث عن مقالة..." class="kayan-cc-input">
        <select id="kayan-cc-seo-post-type" class="kayan-cc-input">
            <option value="">جميع أنواع المحتوى</option>
            <?php foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pt ) : ?>
                <option value="<?php echo esc_attr( $pt->name ); ?>"><?php echo esc_html( $pt->label ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="kayan-cc-toolbar__actions">
        <div class="kayan-cc-per-page">
            <label for="kayan-cc-seo-per-page">عرض:</label>
            <input type="number" id="kayan-cc-seo-per-page" class="kayan-cc-input kayan-cc-per-page__input"
                   value="20" min="5" max="2000" placeholder="20">
            <div class="kayan-cc-per-page__presets">
                <button class="kayan-cc-preset-btn" data-target="kayan-cc-seo-per-page" data-val="10">10</button>
                <button class="kayan-cc-preset-btn" data-target="kayan-cc-seo-per-page" data-val="20">20</button>
                <button class="kayan-cc-preset-btn" data-target="kayan-cc-seo-per-page" data-val="50">50</button>
                <button class="kayan-cc-preset-btn" data-target="kayan-cc-seo-per-page" data-val="100">100</button>
                <button class="kayan-cc-preset-btn" data-target="kayan-cc-seo-per-page" data-val="500">500</button>
            </div>
        </div>
        <input type="text" id="kayan-cc-seo-bulk-template" class="kayan-cc-input" placeholder="قالب: {post_title} | {site_name}">
        <button id="kayan-cc-seo-bulk-apply" class="button button-primary" disabled>
            تطبيق على المحددة
        </button>
    </div>
</div>

<div id="kayan-cc-seo-bulk-preview-wrap" class="kayan-cc-section" style="display:none">
    <h3>معاينة التطبيق الجماعي</h3>
    <div id="kayan-cc-seo-bulk-preview-table"></div>
    <div class="kayan-cc-form-actions">
        <button id="kayan-cc-seo-bulk-confirm" class="button button-primary">✓ تأكيد التطبيق</button>
        <button id="kayan-cc-seo-bulk-cancel" class="button">إلغاء</button>
    </div>
</div>

<div class="kayan-cc-table-scroll-wrap">
    <div id="kayan-cc-seo-posts-table">
        <p class="kayan-cc-loading">جارٍ التحميل...</p>
    </div>
</div>
<div id="kayan-cc-seo-pagination" class="kayan-cc-pagination"></div>

<script type="text/template" id="tmpl-seo-posts-table">
<table class="kayan-cc-table kayan-cc-table--seo">
    <thead>
        <tr>
            <th><input type="checkbox" id="kayan-cc-seo-select-all"></th>
            <th>عنوان المقالة</th>
            <th>عنوان SEO الحالي (Rank Math)</th>
            <th>النوع</th>
            <th>الإجراءات</th>
        </tr>
    </thead>
    <tbody>
        {{#posts}}
        <tr data-post-id="{{post_id}}">
            <td><input type="checkbox" class="kayan-cc-seo-select-post" value="{{post_id}}"></td>
            <td>
                <a href="{{edit_url}}" target="_blank">{{title}}</a>
            </td>
            <td>
                <div class="kayan-cc-seo-title-cell">
                    <span class="kayan-cc-seo-title-display">{{seo_title_display}}</span>
                    <input type="text" class="kayan-cc-seo-title-input kayan-cc-input" value="{{seo_title}}" style="display:none">
                </div>
            </td>
            <td><small>{{post_type}}</small></td>
            <td class="kayan-cc-actions">
                <button class="button button-small kayan-cc-seo-edit-btn" data-id="{{post_id}}">تعديل</button>
                <button class="button button-small kayan-cc-seo-save-btn" data-id="{{post_id}}" style="display:none">حفظ</button>
                <button class="button button-small kayan-cc-seo-rollback-btn" data-id="{{post_id}}" {{#has_backup}}{{/has_backup}}>تراجع</button>
                <button class="button button-small kayan-cc-seo-clear-btn" data-id="{{post_id}}">مسح</button>
            </td>
        </tr>
        {{/posts}}
    </tbody>
</table>
</script>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
