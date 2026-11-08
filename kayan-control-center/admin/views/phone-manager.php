<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<h2 class="kayan-cc-page-title">📞 إدارة الأرقام</h2>

<div class="kayan-cc-toolbar">
    <div class="kayan-cc-toolbar__search">
        <input type="text" id="kayan-cc-search-numbers" placeholder="بحث عن رقم أو اسم..." class="kayan-cc-input">
        <select id="kayan-cc-filter-status">
            <option value="">جميع الحالات</option>
            <option value="active">نشط</option>
            <option value="inactive">متوقف</option>
        </select>
    </div>
    <div class="kayan-cc-toolbar__actions">
        <button id="kayan-cc-export-csv" class="button">⬇ تصدير CSV</button>
        <button id="kayan-cc-add-number" class="button button-primary">+ إضافة رقم جديد</button>
    </div>
</div>

<div id="kayan-cc-numbers-table">
    <p class="kayan-cc-loading">جارٍ التحميل...</p>
</div>
<div id="kayan-cc-numbers-pagination" class="kayan-cc-pagination"></div>

<!-- Add / Edit Modal -->
<div id="kayan-cc-number-modal" class="kayan-cc-modal" style="display:none">
    <div class="kayan-cc-modal__overlay"></div>
    <div class="kayan-cc-modal__dialog">
        <div class="kayan-cc-modal__header">
            <h3 id="kayan-cc-modal-title">إضافة رقم جديد</h3>
            <button class="kayan-cc-modal__close" aria-label="إغلاق">✕</button>
        </div>
        <div class="kayan-cc-modal__body">
            <form id="kayan-cc-number-form">
                <input type="hidden" name="id" id="kayan-cc-number-id">

                <div class="kayan-cc-form-row">
                    <label>الاسم التعريفي <span class="required">*</span></label>
                    <input type="text" name="name" id="kayan-cc-field-name" class="kayan-cc-input" required placeholder="مثال: خدمة العملاء دبي">
                </div>

                <div class="kayan-cc-form-row kayan-cc-form-row--2col">
                    <div>
                        <label>رقم الاتصال</label>
                        <input type="text" name="phone_number" id="kayan-cc-field-phone" class="kayan-cc-input" placeholder="+971501234567">
                    </div>
                    <div>
                        <label>رقم WhatsApp <small>(إذا كان مختلفاً)</small></label>
                        <input type="text" name="whatsapp_number" id="kayan-cc-field-whatsapp" class="kayan-cc-input" placeholder="+971501234567">
                    </div>
                </div>

                <div class="kayan-cc-form-row kayan-cc-form-row--2col">
                    <div>
                        <label>الدولة</label>
                        <input type="text" name="country" id="kayan-cc-field-country" class="kayan-cc-input" value="الإمارات العربية المتحدة">
                    </div>
                    <div>
                        <label>كود الدولة</label>
                        <input type="text" name="country_code" id="kayan-cc-field-country-code" class="kayan-cc-input" value="+971">
                    </div>
                </div>

                <div class="kayan-cc-form-row kayan-cc-form-row--2col">
                    <div>
                        <label>نص زر الاتصال</label>
                        <input type="text" name="call_label" id="kayan-cc-field-call-label" class="kayan-cc-input" value="اتصل بنا">
                    </div>
                    <div>
                        <label>نص زر WhatsApp</label>
                        <input type="text" name="wa_label" id="kayan-cc-field-wa-label" class="kayan-cc-input" value="واتساب">
                    </div>
                </div>

                <div class="kayan-cc-form-row kayan-cc-form-row--checkboxes">
                    <label class="kayan-cc-checkbox">
                        <input type="checkbox" name="call_enabled" id="kayan-cc-field-call-enabled" value="1" checked>
                        تفعيل زر الاتصال
                    </label>
                    <label class="kayan-cc-checkbox">
                        <input type="checkbox" name="wa_enabled" id="kayan-cc-field-wa-enabled" value="1" checked>
                        تفعيل زر WhatsApp
                    </label>
                </div>

                <div class="kayan-cc-form-row">
                    <label>الحالة</label>
                    <select name="status" id="kayan-cc-field-status" class="kayan-cc-input">
                        <option value="active">نشط</option>
                        <option value="inactive">متوقف</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="kayan-cc-modal__footer">
            <button id="kayan-cc-save-number" class="button button-primary">حفظ</button>
            <button class="kayan-cc-modal__close button">إلغاء</button>
        </div>
    </div>
</div>

<script type="text/template" id="tmpl-numbers-table">
<table class="kayan-cc-table">
    <thead>
        <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>رقم الاتصال</th>
            <th>رقم واتساب</th>
            <th>الدولة</th>
            <th>الاستخدامات</th>
            <th>الحالة</th>
            <th>الإجراءات</th>
        </tr>
    </thead>
    <tbody>
        {{#numbers}}
        <tr data-id="{{id}}">
            <td>{{id}}</td>
            <td><strong>{{name}}</strong></td>
            <td class="kayan-cc-monospace">{{phone_number}}</td>
            <td class="kayan-cc-monospace">{{whatsapp_number}}</td>
            <td>{{country}}</td>
            <td>{{usage_count}}</td>
            <td>
                <span class="kayan-cc-badge kayan-cc-badge--{{status}}">
                    {{status_label}}
                </span>
            </td>
            <td class="kayan-cc-actions">
                <button class="button button-small kayan-cc-edit-number" data-id="{{id}}">تعديل</button>
                <button class="button button-small kayan-cc-toggle-number" data-id="{{id}}">
                    {{toggle_label}}
                </button>
                <button class="button button-small button-link-delete kayan-cc-delete-number" data-id="{{id}}">حذف</button>
            </td>
        </tr>
        {{/numbers}}
    </tbody>
</table>
</script>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
