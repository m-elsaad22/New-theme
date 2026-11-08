<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<h2 class="kayan-cc-page-title">📋 سجل التعديلات</h2>

<div class="kayan-cc-toolbar">
    <div class="kayan-cc-toolbar__search">
        <select id="kayan-cc-log-action-filter" class="kayan-cc-input">
            <option value="">جميع العمليات</option>
            <?php
            $actions = [
                'create_number','update_number','delete_number',
                'assign_number','remove_assignment',
                'replace_number','rollback_replacement',
                'set_seo_title','clear_seo_title','rollback_seo_title',
                'create_seo_template','update_seo_template','delete_seo_template',
            ];
            foreach ( $actions as $action ) :
            ?>
                <option value="<?php echo esc_attr( $action ); ?>">
                    <?php echo esc_html( KayanCC_Activity_Log::get_action_label( $action ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="kayan-cc-log-type-filter" class="kayan-cc-input">
            <option value="">جميع الأنواع</option>
            <option value="number">الأرقام</option>
            <option value="assignment">التعيينات</option>
            <option value="replacement">الاستبدال</option>
            <option value="seo">SEO</option>
            <option value="seo_template">قوالب SEO</option>
        </select>
    </div>
</div>

<div id="kayan-cc-log-table">
    <p class="kayan-cc-loading">جارٍ التحميل...</p>
</div>
<div id="kayan-cc-log-pagination" class="kayan-cc-pagination"></div>

<!-- Rollback Replacement Modal -->
<div id="kayan-cc-rollback-modal" class="kayan-cc-modal" style="display:none">
    <div class="kayan-cc-modal__overlay"></div>
    <div class="kayan-cc-modal__dialog">
        <div class="kayan-cc-modal__header">
            <h3>تأكيد التراجع</h3>
            <button class="kayan-cc-modal__close">✕</button>
        </div>
        <div class="kayan-cc-modal__body">
            <p>هل تريد التراجع عن هذه العملية؟ سيتم استعادة البيانات السابقة.</p>
            <input type="hidden" id="kayan-cc-rollback-log-id">
            <input type="hidden" id="kayan-cc-rollback-type">
        </div>
        <div class="kayan-cc-modal__footer">
            <button id="kayan-cc-rollback-confirm" class="button button-primary">تأكيد التراجع</button>
            <button class="kayan-cc-modal__close button">إلغاء</button>
        </div>
    </div>
</div>

<script type="text/template" id="tmpl-log-table">
<table class="kayan-cc-table kayan-cc-table--log">
    <thead>
        <tr>
            <th>#</th>
            <th>العملية</th>
            <th>النوع</th>
            <th>المستخدم</th>
            <th>البيانات قبل</th>
            <th>البيانات بعد</th>
            <th>التاريخ</th>
            <th>التراجع</th>
        </tr>
    </thead>
    <tbody>
        {{#rows}}
        <tr>
            <td>{{id}}</td>
            <td><span class="kayan-cc-badge kayan-cc-badge--action">{{action_label}}</span></td>
            <td>{{object_type}}</td>
            <td>{{user_display}}</td>
            <td>
                {{#data_before}}
                <button class="button button-small kayan-cc-view-data" data-content="{{data_before_escaped}}">عرض</button>
                {{/data_before}}
            </td>
            <td>
                {{#data_after}}
                <button class="button button-small kayan-cc-view-data" data-content="{{data_after_escaped}}">عرض</button>
                {{/data_after}}
            </td>
            <td><small>{{created_at}}</small></td>
            <td>
                {{#is_reversible}}
                {{^reverted_by}}
                <button class="button button-small kayan-cc-rollback-btn" data-id="{{id}}" data-type="{{action}}">
                    ↩ تراجع
                </button>
                {{/reverted_by}}
                {{#reverted_by}}
                <span class="kayan-cc-badge kayan-cc-badge--reverted">تم التراجع</span>
                {{/reverted_by}}
                {{/is_reversible}}
            </td>
        </tr>
        {{/rows}}
    </tbody>
</table>
</script>

<!-- Data Viewer Modal -->
<div id="kayan-cc-data-modal" class="kayan-cc-modal" style="display:none">
    <div class="kayan-cc-modal__overlay"></div>
    <div class="kayan-cc-modal__dialog">
        <div class="kayan-cc-modal__header">
            <h3>بيانات السجل</h3>
            <button class="kayan-cc-modal__close">✕</button>
        </div>
        <div class="kayan-cc-modal__body">
            <pre id="kayan-cc-data-content" class="kayan-cc-pre"></pre>
        </div>
        <div class="kayan-cc-modal__footer">
            <button class="kayan-cc-modal__close button">إغلاق</button>
        </div>
    </div>
</div>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
