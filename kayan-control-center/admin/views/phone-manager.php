<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/header.php'; ?>

<h2 class="kayan-cc-page-title">📞 إدارة الأرقام</h2>

<div class="kayan-cc-toolbar">
    <div class="kayan-cc-toolbar__search">
        <input type="text" id="kayan-cc-search-numbers" placeholder="بحث عن رقم أو اسم..." class="kayan-cc-input">
        <select id="kayan-cc-filter-status" class="kayan-cc-input">
            <option value="">جميع الحالات</option>
            <option value="active">نشط</option>
            <option value="inactive">متوقف</option>
        </select>
    </div>
    <div class="kayan-cc-toolbar__actions">
        <div class="kayan-cc-per-page">
            <label for="kayan-cc-numbers-per-page">عرض:</label>
            <select id="kayan-cc-numbers-per-page" class="kayan-cc-input">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>في الصفحة</span>
        </div>
        <button id="kayan-cc-export-csv" class="button">⬇ CSV</button>
        <button id="kayan-cc-add-number" class="button button-primary">+ إضافة رقم</button>
    </div>
</div>

<!-- Desktop table -->
<div class="kayan-cc-table-scroll-wrap">
    <div id="kayan-cc-numbers-table">
        <p class="kayan-cc-loading">جارٍ التحميل...</p>
    </div>
</div>

<!-- Mobile cards -->
<div id="kayan-cc-numbers-cards" class="kayan-cc-mobile-cards" style="display:none"></div>

<div id="kayan-cc-numbers-pagination" class="kayan-cc-pagination"></div>

<!-- ── Add / Edit Modal ─────────────────────────────────────── -->
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

<!-- ── Number Locations Modal ───────────────────────────────── -->
<div id="kayan-cc-locations-modal" class="kayan-cc-modal kayan-cc-modal--wide" style="display:none">
    <div class="kayan-cc-modal__overlay"></div>
    <div class="kayan-cc-modal__dialog">
        <div class="kayan-cc-modal__header">
            <h3>📍 أماكن الظهور: <span id="kayan-cc-loc-number-name"></span></h3>
            <button class="kayan-cc-modal__close">✕</button>
        </div>
        <div class="kayan-cc-modal__body">
            <div id="kayan-cc-locations-summary" class="kayan-cc-summary-box" style="margin-bottom:14px"></div>

            <div class="kayan-cc-locations-tabs">
                <button class="kayan-cc-tab-btn kayan-cc-tab-btn--active" data-tab="loc-posts">المقالات</button>
                <button class="kayan-cc-tab-btn" data-tab="loc-meta">الحقول</button>
                <button class="kayan-cc-tab-btn" data-tab="loc-options">الإعدادات</button>
            </div>

            <div id="loc-posts"   class="kayan-cc-tab-pane"><p class="kayan-cc-loading">جارٍ التحميل...</p></div>
            <div id="loc-meta"    class="kayan-cc-tab-pane" style="display:none"><p class="kayan-cc-loading">جارٍ التحميل...</p></div>
            <div id="loc-options" class="kayan-cc-tab-pane" style="display:none"><p class="kayan-cc-loading">جارٍ التحميل...</p></div>

            <div class="kayan-cc-notice kayan-cc-notice--warning" style="margin-top:16px">
                <strong>تحذير:</strong> الحذف من مكان محدد يزيل الرقم من محتوى تلك المقالة/الحقل فقط.
                "الحذف الكامل" يحذف الرقم من قاعدة البيانات ومن جميع أماكن ظهوره في الموقع.
            </div>
        </div>
        <div class="kayan-cc-modal__footer">
            <button id="kayan-cc-hard-delete-number" class="button kayan-cc-btn--danger">
                🗑 حذف الرقم نهائياً من كل مكان
            </button>
            <button class="kayan-cc-modal__close button">إغلاق</button>
        </div>
    </div>
</div>

<!-- ── Hide confirmation modal ─────────────────────────────── -->
<div id="kayan-cc-hide-confirm-modal" class="kayan-cc-modal" style="display:none">
    <div class="kayan-cc-modal__overlay"></div>
    <div class="kayan-cc-modal__dialog">
        <div class="kayan-cc-modal__header">
            <h3>إخفاء الرقم</h3>
            <button class="kayan-cc-modal__close">✕</button>
        </div>
        <div class="kayan-cc-modal__body">
            <p>هل تريد إخفاء الرقم؟ سيتوقف عن الظهور في الموقع لكن بياناته ستبقى محفوظة.</p>
            <input type="hidden" id="kayan-cc-hide-number-id">
        </div>
        <div class="kayan-cc-modal__footer">
            <button id="kayan-cc-hide-confirm-btn" class="button button-primary">إخفاء</button>
            <button class="kayan-cc-modal__close button">إلغاء</button>
        </div>
    </div>
</div>

<?php include KAYAN_CC_PLUGIN_DIR . 'admin/partials/footer.php'; ?>
