<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<h2 class="kayan-cc-page-title">📝 قوالب SEO</h2>

<div class="kayan-cc-notice kayan-cc-notice--info">
    المتغيرات المدعومة:
    <code>{post_title}</code> <code>{city}</code> <code>{service}</code>
    <code>{category}</code> <code>{site_name}</code>
</div>

<div class="kayan-cc-section-actions">
    <button id="kayan-cc-add-template" class="button button-primary">+ إضافة قالب جديد</button>
</div>

<div id="kayan-cc-templates-list">
    <p class="kayan-cc-loading">جارٍ التحميل...</p>
</div>

<!-- Add / Edit Template Modal -->
<div id="kayan-cc-template-modal" class="kayan-cc-modal" style="display:none">
    <div class="kayan-cc-modal__overlay"></div>
    <div class="kayan-cc-modal__dialog">
        <div class="kayan-cc-modal__header">
            <h3 id="kayan-cc-template-modal-title">إضافة قالب جديد</h3>
            <button class="kayan-cc-modal__close">✕</button>
        </div>
        <div class="kayan-cc-modal__body">
            <form id="kayan-cc-template-form">
                <input type="hidden" name="id" id="kayan-cc-template-id">
                <div class="kayan-cc-form-row">
                    <label>اسم القالب <span class="required">*</span></label>
                    <input type="text" name="name" id="kayan-cc-template-name" class="kayan-cc-input" required placeholder="مثال: خدمات المدن الإماراتية">
                </div>
                <div class="kayan-cc-form-row">
                    <label>نص القالب <span class="required">*</span></label>
                    <input type="text" name="template" id="kayan-cc-template-text" class="kayan-cc-input kayan-cc-input--large" required
                           placeholder="{service} في {city} | شركة ركن التطور">
                    <div class="kayan-cc-form-hint">
                        <button type="button" class="kayan-cc-insert-var" data-var="{post_title}">{post_title}</button>
                        <button type="button" class="kayan-cc-insert-var" data-var="{city}">{city}</button>
                        <button type="button" class="kayan-cc-insert-var" data-var="{service}">{service}</button>
                        <button type="button" class="kayan-cc-insert-var" data-var="{category}">{category}</button>
                        <button type="button" class="kayan-cc-insert-var" data-var="{site_name}">{site_name}</button>
                    </div>
                </div>
                <div class="kayan-cc-form-row">
                    <label>تطبيق تلقائي على</label>
                    <select name="scope_type" id="kayan-cc-template-scope-type" class="kayan-cc-input">
                        <option value="manual">يدوي (اختيار يدوي)</option>
                        <option value="post_type">نوع محتوى كامل</option>
                        <option value="taxonomy_term">تصنيف محدد</option>
                    </select>
                </div>
                <div id="kayan-cc-template-scope-post-type" class="kayan-cc-form-row" style="display:none">
                    <label>نوع المحتوى</label>
                    <select name="scope_taxonomy" id="kayan-cc-template-post-type" class="kayan-cc-input">
                        <?php foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pt ) : ?>
                            <option value="<?php echo esc_attr( $pt->name ); ?>"><?php echo esc_html( $pt->label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="kayan-cc-template-scope-term" class="kayan-cc-form-row" style="display:none">
                    <label>التصنيف والقسم</label>
                    <select name="scope_taxonomy" id="kayan-cc-template-taxonomy" class="kayan-cc-input">
                        <?php foreach ( get_taxonomies( [ 'public' => true ], 'objects' ) as $tax ) : ?>
                            <option value="<?php echo esc_attr( $tax->name ); ?>"><?php echo esc_html( $tax->label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="scope_id" id="kayan-cc-template-term" class="kayan-cc-input" style="margin-top:8px">
                        <option value="">-- اختر القسم --</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="kayan-cc-modal__footer">
            <button id="kayan-cc-save-template" class="button button-primary">حفظ القالب</button>
            <button class="kayan-cc-modal__close button">إلغاء</button>
        </div>
    </div>
</div>

<!-- Apply Template Modal -->
<div id="kayan-cc-apply-template-modal" class="kayan-cc-modal kayan-cc-modal--wide" style="display:none">
    <div class="kayan-cc-modal__overlay"></div>
    <div class="kayan-cc-modal__dialog">
        <div class="kayan-cc-modal__header">
            <h3>تطبيق القالب: <span id="kayan-cc-apply-template-name"></span></h3>
            <button class="kayan-cc-modal__close">✕</button>
        </div>
        <div class="kayan-cc-modal__body">
            <input type="hidden" id="kayan-cc-apply-template-id">
            <div id="kayan-cc-apply-template-preview">
                <p class="kayan-cc-loading">جارٍ تحميل المعاينة...</p>
            </div>
        </div>
        <div class="kayan-cc-modal__footer">
            <button id="kayan-cc-apply-template-confirm" class="button button-primary" disabled>تطبيق على المقالات</button>
            <button class="kayan-cc-modal__close button">إلغاء</button>
        </div>
    </div>
</div>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
