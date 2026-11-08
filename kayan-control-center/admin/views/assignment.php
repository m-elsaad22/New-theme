<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<h2 class="kayan-cc-page-title">📋 توزيع الأرقام</h2>

<div class="kayan-cc-notice kayan-cc-notice--info">
    <strong>قواعد الأولوية:</strong>
    مقالة محددة ← تصنيف محدد ← نوع محتوى ← الرقم الافتراضي للموقع
</div>

<div class="kayan-cc-assignment-layout">
    <!-- Left Panel: Assign Form -->
    <div class="kayan-cc-panel">
        <h3>إضافة تعيين جديد</h3>
        <form id="kayan-cc-assign-form">
            <div class="kayan-cc-form-row">
                <label>الرقم</label>
                <select name="number_id" id="kayan-cc-assign-number" class="kayan-cc-input">
                    <option value="">-- اختر الرقم --</option>
                    <?php
                    $numbers = KayanCC_Phone_Manager::get_instance()->get_active_numbers();
                    foreach ( $numbers as $num ) :
                    ?>
                        <option value="<?php echo esc_attr( $num['id'] ); ?>">
                            <?php echo esc_html( $num['name'] . ' (' . $num['phone_number'] . ')' ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="kayan-cc-form-row">
                <label>نوع التعيين</label>
                <select name="scope_type" id="kayan-cc-assign-scope-type" class="kayan-cc-input">
                    <option value="site_default">الرقم الافتراضي للموقع</option>
                    <option value="post_type">نوع محتوى كامل</option>
                    <option value="taxonomy_term">تصنيف محدد</option>
                    <option value="post">مقالة محددة</option>
                </select>
            </div>

            <!-- Shown for post_type -->
            <div class="kayan-cc-form-row" id="kayan-cc-scope-post-type" style="display:none">
                <label>نوع المحتوى</label>
                <select name="scope_taxonomy" id="kayan-cc-assign-post-type" class="kayan-cc-input">
                    <?php foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pt ) : ?>
                        <option value="<?php echo esc_attr( $pt->name ); ?>"><?php echo esc_html( $pt->label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Shown for taxonomy_term -->
            <div class="kayan-cc-form-row" id="kayan-cc-scope-taxonomy" style="display:none">
                <label>التصنيف</label>
                <select name="scope_taxonomy_name" id="kayan-cc-assign-taxonomy" class="kayan-cc-input">
                    <?php foreach ( get_taxonomies( [ 'public' => true ], 'objects' ) as $tax ) : ?>
                        <option value="<?php echo esc_attr( $tax->name ); ?>"><?php echo esc_html( $tax->label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="scope_id" id="kayan-cc-assign-term" class="kayan-cc-input" style="margin-top:8px">
                    <option value="">-- اختر القسم --</option>
                </select>
            </div>

            <!-- Shown for post -->
            <div class="kayan-cc-form-row" id="kayan-cc-scope-post" style="display:none">
                <label>بحث عن مقالة</label>
                <input type="text" id="kayan-cc-post-search" class="kayan-cc-input" placeholder="ابدأ الكتابة للبحث...">
                <input type="hidden" name="scope_id" id="kayan-cc-assign-post-id">
                <div id="kayan-cc-post-search-results" class="kayan-cc-autocomplete"></div>
            </div>

            <div class="kayan-cc-form-row">
                <button type="submit" class="button button-primary">تعيين الرقم</button>
            </div>
        </form>
    </div>

    <!-- Right Panel: Current Assignments -->
    <div class="kayan-cc-panel">
        <h3>التعيينات الحالية</h3>
        <div id="kayan-cc-assignments-list">
            <p class="kayan-cc-loading">جارٍ التحميل...</p>
        </div>
    </div>
</div>

<!-- Bulk Assign Section -->
<div class="kayan-cc-section">
    <h3>التعيين الجماعي</h3>
    <div class="kayan-cc-form-row kayan-cc-form-row--inline">
        <select id="kayan-cc-bulk-number" class="kayan-cc-input">
            <option value="">-- اختر الرقم --</option>
            <?php foreach ( $numbers as $num ) : ?>
                <option value="<?php echo esc_attr( $num['id'] ); ?>">
                    <?php echo esc_html( $num['name'] . ' (' . $num['phone_number'] . ')' ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button id="kayan-cc-bulk-assign-btn" class="button button-primary">تعيين على المقالات المختارة</button>
    </div>
</div>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
