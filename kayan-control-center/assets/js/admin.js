/**
 * KAYAN Control Center – Admin JavaScript
 */
/* global jQuery, KayanCC */

( function( $ ) {
    'use strict';

    var CC = KayanCC;

    /* ── Utilities ─────────────────────────────────────────────── */

    function ajax( action, data, method ) {
        method = method || 'POST';
        return $.ajax( {
            url:    CC.ajax_url,
            method: method,
            data:   $.extend( {}, data, { action: action, nonce: CC.nonce } ),
        } );
    }
    function ajaxGet( action, data ) { return ajax( action, data, 'GET' ); }

    function toast( msg, type ) {
        type = type || 'success';
        var $t = $( '<div class="kayan-cc-toast kayan-cc-toast--' + type + '">' + escHtml( msg ) + '</div>' );
        $( 'body' ).append( $t );
        setTimeout( function() { $t.fadeOut( 400, function() { $t.remove(); } ); }, 3500 );
    }

    function escHtml( str ) {
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' )
            .replace( /"/g, '&quot;' );
    }
    function escAttr( str ) {
        return String( str ).replace( /"/g, '&quot;' ).replace( /'/g, '&#039;' );
    }

    function debounce( fn, delay ) {
        var t;
        return function() {
            var ctx = this, args = arguments;
            clearTimeout( t );
            t = setTimeout( function() { fn.apply( ctx, args ); }, delay );
        };
    }

    function isMobile() { return window.innerWidth <= 782; }

    function syncPresetActive( $input ) {
        var v = parseInt( $input.val(), 10 );
        $input.closest( '.kayan-cc-per-page' )
              .find( '.kayan-cc-preset-btn' )
              .each( function() {
                  $( this ).toggleClass( 'active', parseInt( $( this ).data( 'val' ), 10 ) === v );
              } );
    }

    function statusLabel( s ) { return s === 'active' ? 'نشط' : 'متوقف'; }

    function resultTypeLabel( type ) {
        var labels = {
            phone_confirmed:    'هاتف مؤكد',
            whatsapp_confirmed: 'واتساب مؤكد',
            phone_probable:     'محتمل',
            call_link:          'رابط اتصال',
            whatsapp_link:      'رابط واتساب',
            duplicate:          'مكرر',
        };
        return labels[ type ] || type;
    }

    function renderPagination( $container, paging, onPageClick ) {
        if ( ! paging || paging.total_pages <= 1 ) { $container.empty(); return; }
        var total      = paging.total || 0;
        var totalPages = paging.total_pages || 1;
        var cur        = paging.current_page || 1;
        var prevPage   = Math.max( 1, cur - 1 );
        var nextPage   = Math.min( totalPages, cur + 1 );

        var html = '<div class="kayan-cc-pag-wrap">';
        html += '<span class="kayan-cc-pag-info">' + total + ' عنصر</span>';
        html += '<button class="page-btn page-btn--nav"' +
                ( cur <= 1 ? ' disabled' : '' ) +
                ' data-page="' + prevPage + '">&#x2190; السابق</button>';
        html += '<span class="kayan-cc-pag-indicator">' + cur + ' / ' + totalPages + '</span>';
        html += '<button class="page-btn page-btn--nav"' +
                ( cur >= totalPages ? ' disabled' : '' ) +
                ' data-page="' + nextPage + '">التالي &#x2192;</button>';
        html += '</div>';

        $container.html( html );
        $container.find( '.page-btn:not([disabled])' ).on( 'click', function() {
            onPageClick( parseInt( $( this ).data( 'page' ), 10 ) );
        } );
    }

    /* ── Per-page preset buttons ─────────────────────────────────── */
    $( document ).on( 'click', '.kayan-cc-preset-btn', function() {
        var target = $( this ).data( 'target' );
        var val    = $( this ).data( 'val' );
        var $input = $( '#' + target );
        $input.val( val ).trigger( 'change' );
        // Update active state
        $( this ).closest( '.kayan-cc-per-page__presets' )
                 .find( '.kayan-cc-preset-btn' ).removeClass( 'active' );
        $( this ).addClass( 'active' );
    } );

    /* ── Modals ─────────────────────────────────────────────────── */
    $( document ).on( 'click', '.kayan-cc-modal__overlay, .kayan-cc-modal__close', function() {
        $( this ).closest( '.kayan-cc-modal' ).hide();
    } );

    /* ── Tabs ───────────────────────────────────────────────────── */
    $( document ).on( 'click', '.kayan-cc-tab-btn', function() {
        var $modal = $( this ).closest( '.kayan-cc-modal__dialog' );
        $modal.find( '.kayan-cc-tab-btn' ).removeClass( 'kayan-cc-tab-btn--active' );
        $( this ).addClass( 'kayan-cc-tab-btn--active' );
        $modal.find( '.kayan-cc-tab-pane' ).hide();
        $( '#' + $( this ).data( 'tab' ) ).show();
    } );

    /* ════════════════════════════════════════════════════════════
       PHONE MANAGER
    ════════════════════════════════════════════════════════════ */
    var numbersPage    = 1;
    var numbersPerPage = 20;
    var numbersSearch  = '';
    var numbersStatus  = '';

    function loadNumbers() {
        if ( ! $( '#kayan-cc-numbers-table' ).length ) return;
        ajaxGet( 'kayan_cc_get_numbers', {
            page:     numbersPage,
            per_page: numbersPerPage,
            search:   numbersSearch,
            status:   numbersStatus,
        } ).done( function( res ) {
            if ( ! res.success ) { toast( CC.i18n.error, 'error' ); return; }
            if ( isMobile() ) {
                renderNumberCards( res.data );
            } else {
                renderNumbersTable( res.data );
            }
        } ).fail( function() { toast( CC.i18n.error, 'error' ); } );
    }

    /* ── Desktop table ── */
    function renderNumbersTable( data ) {
        $( '#kayan-cc-numbers-cards' ).hide();
        $( '#kayan-cc-numbers-table' ).show();

        if ( ! data.numbers.length ) {
            $( '#kayan-cc-numbers-table' ).html( '<p class="kayan-cc-empty">' + CC.i18n.no_results + '</p>' );
            $( '#kayan-cc-numbers-pagination' ).empty();
            return;
        }

        var rows = data.numbers.map( function( n ) {
            var occBadge = n.occurrences > 0
                ? '<button class="kayan-cc-occurrences-badge kayan-cc-open-locations" data-id="' + n.id + '">' +
                  '📍 ' + n.occurrences + ' مرة</button>' +
                  '<br><span class="kayan-cc-posts-count">' + n.unique_posts + ' مقالة</span>'
                : '<span style="color:#ccc">-</span>';

            return '<tr data-id="' + n.id + '">' +
                '<td>' + n.id + '</td>' +
                '<td><strong>' + escHtml( n.name ) + '</strong></td>' +
                '<td class="kayan-cc-monospace">' + escHtml( n.phone_number ) + '</td>' +
                '<td class="kayan-cc-monospace">' + escHtml( n.whatsapp_number ) + '</td>' +
                '<td>' + escHtml( n.country ) + '</td>' +
                '<td style="text-align:center">' + n.usage_count + '</td>' +
                '<td style="text-align:center">' + occBadge + '</td>' +
                '<td><span class="kayan-cc-badge kayan-cc-badge--' + n.status + '">' + statusLabel( n.status ) + '</span></td>' +
                '<td class="kayan-cc-actions">' +
                    '<button class="button button-small kayan-cc-edit-number" data-id="' + n.id + '">تعديل</button> ' +
                    '<button class="button button-small kayan-cc-hide-number" data-id="' + n.id + '">' + ( n.status === 'active' ? 'إخفاء' : 'إظهار' ) + '</button> ' +
                    '<button class="button button-small button-link-delete kayan-cc-delete-number" data-id="' + n.id + '">حذف</button>' +
                '</td>' +
                '</tr>';
        } ).join( '' );

        $( '#kayan-cc-numbers-table' ).html(
            '<table class="kayan-cc-table kayan-cc-table--responsive">' +
            '<thead><tr>' +
            '<th>#</th><th>الاسم</th><th>رقم الاتصال</th><th>رقم واتساب</th>' +
            '<th>الدولة</th><th>التعيينات</th><th>الظهورات</th><th>الحالة</th><th>الإجراءات</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table>'
        );
        renderPagination( $( '#kayan-cc-numbers-pagination' ), data.paging, function( p ) { numbersPage = p; loadNumbers(); } );
    }

    /* ── Mobile cards ── */
    function renderNumberCards( data ) {
        $( '#kayan-cc-numbers-table' ).hide();
        var $cards = $( '#kayan-cc-numbers-cards' );

        if ( ! data.numbers.length ) {
            $cards.html( '<p class="kayan-cc-empty">' + CC.i18n.no_results + '</p>' ).show();
            $( '#kayan-cc-numbers-pagination' ).empty();
            return;
        }

        var html = data.numbers.map( function( n ) {
            var occStr = n.occurrences > 0 ? n.occurrences + ' مرة في ' + n.unique_posts + ' مقالة' : 'لا ظهورات مسجلة';
            return '<div class="kayan-cc-number-card ' + ( n.status === 'inactive' ? 'inactive' : '' ) + '" data-id="' + n.id + '">' +
                '<div class="kayan-cc-number-card__header">' +
                    '<div class="kayan-cc-number-card__name">' + escHtml( n.name ) + '</div>' +
                    '<span class="kayan-cc-badge kayan-cc-badge--' + n.status + '">' + statusLabel( n.status ) + '</span>' +
                '</div>' +
                '<div class="kayan-cc-number-card__nums">' +
                    ( n.phone_number ? '📞 ' + escHtml( n.phone_number ) + '<br>' : '' ) +
                    ( n.whatsapp_number ? '💬 ' + escHtml( n.whatsapp_number ) : '' ) +
                '</div>' +
                '<div class="kayan-cc-number-card__stats">' +
                    ( n.occurrences > 0
                        ? '<button class="kayan-cc-occurrences-badge kayan-cc-open-locations" data-id="' + n.id + '">📍 ' + occStr + '</button>'
                        : '<span style="font-size:12px;color:#aaa">لا ظهورات</span>' ) +
                '</div>' +
                '<div class="kayan-cc-number-card__actions">' +
                    '<button class="button button-small kayan-cc-edit-number" data-id="' + n.id + '">تعديل</button>' +
                    '<button class="button button-small kayan-cc-hide-number" data-id="' + n.id + '">' + ( n.status === 'active' ? 'إخفاء' : 'إظهار' ) + '</button>' +
                    '<button class="button button-small button-link-delete kayan-cc-delete-number" data-id="' + n.id + '">حذف</button>' +
                '</div>' +
                '</div>';
        } ).join( '' );

        $cards.html( html ).show();
        renderPagination( $( '#kayan-cc-numbers-pagination' ), data.paging, function( p ) { numbersPage = p; loadNumbers(); } );
    }

    /* ── Filters & search ── */
    $( document ).on( 'input', '#kayan-cc-search-numbers', debounce( function() {
        numbersSearch = $( this ).val();
        numbersPage   = 1;
        loadNumbers();
    }, 400 ) );

    $( document ).on( 'change', '#kayan-cc-filter-status', function() {
        numbersStatus = $( this ).val();
        numbersPage   = 1;
        loadNumbers();
    } );

    $( document ).on( 'change', '#kayan-cc-numbers-per-page', function() {
        var v = parseInt( $( this ).val(), 10 );
        if ( isNaN( v ) || v < 5 ) v = 5;
        if ( v > 2000 ) v = 2000;
        $( this ).val( v );
        syncPresetActive( $( this ) );
        numbersPerPage = v;
        numbersPage    = 1;
        loadNumbers();
    } );

    $( document ).on( 'keydown', '#kayan-cc-numbers-per-page', function( e ) {
        if ( e.key === 'Enter' ) { $( this ).trigger( 'change' ); }
    } );

    /* ── Add ── */
    $( document ).on( 'click', '#kayan-cc-add-number', function() {
        resetNumberForm();
        $( '#kayan-cc-modal-title' ).text( 'إضافة رقم جديد' );
        $( '#kayan-cc-number-modal' ).show();
    } );

    /* ── Edit ── */
    $( document ).on( 'click', '.kayan-cc-edit-number', function() {
        var id = $( this ).data( 'id' );
        ajaxGet( 'kayan_cc_get_numbers', { page: 1, per_page: 1, search: id } )
            .done( function( res ) {
                var n = res.data && res.data.numbers && res.data.numbers[0];
                if ( ! n ) return;
                $( '#kayan-cc-number-id' ).val( n.id );
                $( '#kayan-cc-field-name' ).val( n.name );
                $( '#kayan-cc-field-phone' ).val( n.phone_number );
                $( '#kayan-cc-field-whatsapp' ).val( n.whatsapp_number );
                $( '#kayan-cc-field-country' ).val( n.country );
                $( '#kayan-cc-field-country-code' ).val( n.country_code );
                $( '#kayan-cc-field-call-label' ).val( n.call_label );
                $( '#kayan-cc-field-wa-label' ).val( n.wa_label );
                $( '#kayan-cc-field-call-enabled' ).prop( 'checked', n.call_enabled == 1 );
                $( '#kayan-cc-field-wa-enabled' ).prop( 'checked', n.wa_enabled == 1 );
                $( '#kayan-cc-field-status' ).val( n.status );
                $( '#kayan-cc-modal-title' ).text( 'تعديل الرقم: ' + n.name );
                $( '#kayan-cc-number-modal' ).show();
            } );
    } );

    /* ── Save ── */
    $( document ).on( 'click', '#kayan-cc-save-number', function() {
        var id   = $( '#kayan-cc-number-id' ).val();
        var data = {};
        $( '#kayan-cc-number-form' ).serializeArray().forEach( function( f ) { data[ f.name ] = f.value; } );
        if ( ! $( '#kayan-cc-field-call-enabled' ).is( ':checked' ) ) data.call_enabled = 0;
        if ( ! $( '#kayan-cc-field-wa-enabled' ).is( ':checked' ) )   data.wa_enabled   = 0;
        ajax( id ? 'kayan_cc_update_number' : 'kayan_cc_add_number', data )
            .done( function( res ) {
                if ( res.success ) { toast( CC.i18n.saved ); $( '#kayan-cc-number-modal' ).hide(); loadNumbers(); }
                else { toast( res.data.message || CC.i18n.error, 'error' ); }
            } );
    } );

    /* ── Hide (toggle status) ── */
    $( document ).on( 'click', '.kayan-cc-hide-number', function() {
        var id = $( this ).data( 'id' );
        $( '#kayan-cc-hide-number-id' ).val( id );
        $( '#kayan-cc-hide-confirm-modal' ).show();
    } );

    $( document ).on( 'click', '#kayan-cc-hide-confirm-btn', function() {
        var id = $( '#kayan-cc-hide-number-id' ).val();
        ajax( 'kayan_cc_toggle_number', { id: id } )
            .done( function( res ) {
                $( '#kayan-cc-hide-confirm-modal' ).hide();
                if ( res.success ) { loadNumbers(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    /* ── Soft delete (DB only) ── */
    $( document ).on( 'click', '.kayan-cc-delete-number', function() {
        var id = $( this ).data( 'id' );
        if ( ! window.confirm( 'هل تريد حذف الرقم من قاعدة البيانات فقط (دون حذفه من محتوى الموقع)؟\nللحذف الكامل من الموقع استخدم زر "حذف الرقم نهائياً" في صفحة الأماكن.' ) ) return;
        ajax( 'kayan_cc_delete_number', { id: id } )
            .done( function( res ) {
                if ( res.success ) { toast( CC.i18n.deleted ); loadNumbers(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    /* ── Export CSV ── */
    $( document ).on( 'click', '#kayan-cc-export-csv', function() {
        window.location.href = CC.ajax_url + '?action=kayan_cc_export_numbers_csv&nonce=' + CC.nonce;
    } );

    /* ── Open Locations Modal ── */
    $( document ).on( 'click', '.kayan-cc-open-locations', function() {
        var id = $( this ).data( 'id' );
        $( '#kayan-cc-hard-delete-number' ).data( 'id', id );
        $( '#kayan-cc-locations-modal' ).show();
        $( '#kayan-cc-loc-number-name' ).text( CC.i18n.loading );
        $( '#kayan-cc-locations-summary' ).text( CC.i18n.loading );
        $( '#loc-posts, #loc-meta, #loc-options' ).html( '<p class="kayan-cc-loading">جارٍ التحميل...</p>' );

        // Reset tabs
        $( '.kayan-cc-tab-btn' ).removeClass( 'kayan-cc-tab-btn--active' ).first().addClass( 'kayan-cc-tab-btn--active' );
        $( '.kayan-cc-tab-pane' ).hide();
        $( '#loc-posts' ).show();

        ajaxGet( 'kayan_cc_get_number_locations', { number_id: id } )
            .done( function( res ) {
                if ( ! res.success ) { toast( res.data.message, 'error' ); return; }
                renderLocations( res.data );
            } );
    } );

    function renderLocations( data ) {
        var n         = data.number;
        var locations = data.locations || {};

        $( '#kayan-cc-loc-number-name' ).text( n.name + ' (' + n.phone_number + ')' );

        // Summary
        var total = 0;
        $.each( locations, function( k, v ) { total += v.length; } );
        $( '#kayan-cc-locations-summary' ).html(
            '<strong>الرقم:</strong> ' + escHtml( n.phone_e164 ) + ' &nbsp; ' +
            '<strong>إجمالي الظهورات:</strong> ' + total
        );

        // Posts tab (post_content + post_title)
        var postRows = [];
        $.each( [ 'post_content', 'post_title' ], function( i, type ) {
            if ( locations[ type ] ) {
                if ( postRows.length ) postRows.push( '<div class="kayan-cc-loc-group-header">' + ( type === 'post_content' ? 'محتوى المقالات' : 'عنوان المقالات' ) + '</div>' );
                $.each( locations[ type ], function( j, row ) {
                    postRows.push( buildLocItem( row, n.id, type ) );
                } );
            }
        } );
        $( '#loc-posts' ).html( postRows.length ? postRows.join( '' ) : '<p class="kayan-cc-empty">لا ظهورات في محتوى المقالات.</p>' );

        // Meta tab
        var metaRows = [];
        if ( locations['post_meta'] ) {
            $.each( locations['post_meta'], function( j, row ) {
                metaRows.push( buildLocItem( row, n.id, 'post_meta' ) );
            } );
        }
        $( '#loc-meta' ).html( metaRows.length ? metaRows.join( '' ) : '<p class="kayan-cc-empty">لا ظهورات في الحقول المخصصة.</p>' );

        // Options tab (widget, option, menu)
        var optRows = [];
        $.each( [ 'option', 'widget', 'menu' ], function( i, type ) {
            if ( locations[ type ] ) {
                $.each( locations[ type ], function( j, row ) {
                    optRows.push( buildLocItem( row, n.id, type ) );
                } );
            }
        } );
        $( '#loc-options' ).html( optRows.length ? optRows.join( '' ) : '<p class="kayan-cc-empty">لا ظهورات في الإعدادات.</p>' );
    }

    function buildLocItem( row, numberId, sourceType ) {
        var canDelete = row.source_id > 0 && ( sourceType === 'post_content' || sourceType === 'post_title' || sourceType === 'post_meta' );
        var editLink  = row.edit_url ? '<a href="' + escHtml( row.edit_url ) + '" target="_blank" class="button button-small">✏ تعديل</a>' : '';
        var delBtn    = canDelete
            ? '<button class="button button-small button-link-delete kayan-cc-delete-loc" ' +
              'data-number-id="' + numberId + '" ' +
              'data-source-type="' + escAttr( sourceType ) + '" ' +
              'data-source-id="' + row.source_id + '" ' +
              'data-source-field="' + escAttr( row.source_field ) + '">' +
              'حذف من هنا</button>'
            : '<small style="color:#aaa">لا يمكن الحذف تلقائياً</small>';

        return '<div class="kayan-cc-loc-item">' +
            '<div class="kayan-cc-loc-item__info">' +
                '<div class="kayan-cc-loc-item__title">' + escHtml( row.post_title || row.source_field ) + '</div>' +
                '<div class="kayan-cc-loc-item__meta">' +
                    escHtml( row.source_field ) + ( row.source_id > 0 ? ' &nbsp;|&nbsp; #' + row.source_id : '' ) +
                '</div>' +
                '<div class="kayan-cc-location-value">' + escHtml( row.raw_value.substring( 0, 80 ) ) + '</div>' +
            '</div>' +
            '<div class="kayan-cc-loc-item__actions">' + editLink + ' ' + delBtn + '</div>' +
            '</div>';
    }

    /* ── Delete from specific location ── */
    $( document ).on( 'click', '.kayan-cc-delete-loc', function() {
        if ( ! window.confirm( 'هل تريد حذف الرقم من هذا المكان فقط؟' ) ) return;
        var $btn = $( this );
        ajax( 'kayan_cc_delete_from_location', {
            number_id:    $btn.data( 'number-id' ),
            source_type:  $btn.data( 'source-type' ),
            source_id:    $btn.data( 'source-id' ),
            source_field: $btn.data( 'source-field' ),
        } ).done( function( res ) {
            if ( res.success ) {
                toast( 'تم الحذف من هذا المكان.' );
                $btn.closest( '.kayan-cc-loc-item' ).fadeOut( 300 );
            } else {
                toast( res.data.message, 'error' );
            }
        } );
    } );

    /* ── Hard delete (everywhere + DB) ── */
    $( document ).on( 'click', '#kayan-cc-hard-delete-number', function() {
        if ( ! window.confirm( 'تحذير: سيتم حذف الرقم نهائياً من قاعدة البيانات ومن جميع أماكن ظهوره في الموقع.\nهذه العملية قابلة للتراجع من سجل التعديلات.\nهل أنت متأكد؟' ) ) return;
        var id = $( this ).data( 'id' );
        ajax( 'kayan_cc_hard_delete_number', { id: id } )
            .done( function( res ) {
                if ( res.success ) {
                    toast( 'تم الحذف الكامل. ' + ( res.data.replacement_results && res.data.replacement_results.phone ? res.data.replacement_results.phone.posts_changed + ' مقالة تأثرت.' : '' ) );
                    $( '#kayan-cc-locations-modal' ).hide();
                    loadNumbers();
                } else {
                    toast( res.data.message, 'error' );
                }
            } );
    } );

    function resetNumberForm() {
        $( '#kayan-cc-number-form' )[ 0 ] && $( '#kayan-cc-number-form' )[ 0 ].reset();
        $( '#kayan-cc-number-id' ).val( '' );
        $( '#kayan-cc-field-call-enabled, #kayan-cc-field-wa-enabled' ).prop( 'checked', true );
        $( '#kayan-cc-field-status' ).val( 'active' );
        $( '#kayan-cc-field-country' ).val( 'الإمارات العربية المتحدة' );
        $( '#kayan-cc-field-country-code' ).val( '+971' );
        $( '#kayan-cc-field-call-label' ).val( 'اتصل بنا' );
        $( '#kayan-cc-field-wa-label' ).val( 'واتساب' );
    }

    /* ════════════════════════════════════════════════════════════
       PHONE SCANNER
    ════════════════════════════════════════════════════════════ */
    var $scanProgress = $( '#kayan-cc-scan-progress' );
    var $scanStatus   = $( '#kayan-cc-scan-status-text' );
    var $scanResults  = $( '#kayan-cc-scan-results-wrap' );
    var scanPhases    = { posts: 20, options: 60, widgets: 75, menus: 85, done: 100 };

    $( document ).on( 'click', '#kayan-cc-start-scan', function() {
        if ( ! window.confirm( 'سيبدأ فحص شامل للموقع. هذا قد يستغرق دقيقة أو أكثر. هل تريد المتابعة؟' ) ) return;
        ajax( 'kayan_cc_start_scan', {} )
            .done( function( res ) {
                if ( ! res.success ) return;
                $scanProgress.show();
                $scanResults.hide();
                $( '#kayan-cc-scan-import-banner' ).hide();
                $scanStatus.text( 'جارٍ الفحص...' );
                processScanBatch();
            } );
    } );

    function processScanBatch() {
        ajax( 'kayan_cc_scan_batch', {} )
            .done( function( res ) {
                if ( ! res.success ) return;
                var phase    = res.data.status.phase || 'posts';
                var progress = scanPhases[ phase ] || 10;
                $( '#kayan-cc-progress-fill' ).css( 'width', progress + '%' );
                $( '#kayan-cc-progress-text' ).text( 'المرحلة: ' + phase );

                if ( res.data.done ) {
                    $scanStatus.text( 'اكتمل الفحص ✓' );
                    $( '#kayan-cc-progress-fill' ).css( 'width', '100%' );
                    setTimeout( function() { $scanProgress.hide(); }, 600 );
                    $scanResults.show();
                    loadScanResults( 1 );
                    showImportBanner();
                } else {
                    setTimeout( processScanBatch, 200 );
                }
            } )
            .fail( function() { $scanStatus.text( 'حدث خطأ أثناء الفحص.' ); } );
    }

    function showImportBanner() {
        // Fetch count of confirmed numbers from scan
        ajaxGet( 'kayan_cc_get_scan_results', { page: 1, per_page: 1 } )
            .done( function() {
                var $banner = $( '#kayan-cc-scan-import-banner' );
                if ( $banner.children().length ) { $banner.show(); return; }
                $banner.html(
                    '<div class="kayan-cc-import-banner">' +
                        '<div><h3>✅ اكتمل الفحص</h3>' +
                        '<p>يمكنك الآن استيراد الأرقام المكتشفة إلى مدير الأرقام.</p></div>' +
                        '<div style="display:flex;gap:10px;flex-wrap:wrap">' +
                        '<button id="kayan-cc-import-to-manager" class="button">📥 استيراد الأرقام</button>' +
                        '<a href="' + escHtml( window.location.origin + '/wp-admin/admin.php?page=kayan-cc-phone-manager' ) + '" class="button">📞 مدير الأرقام</a>' +
                        '</div></div>' +
                        '<div id="kayan-cc-import-result" class="kayan-cc-import-results" style="display:none"></div>'
                ).show();
            } );
    }

    /* ── Import scan results to manager ── */
    $( document ).on( 'click', '#kayan-cc-import-to-manager', function() {
        var $btn = $( this );
        $btn.prop( 'disabled', true ).text( 'جارٍ الاستيراد...' );
        ajax( 'kayan_cc_import_from_scan', {} )
            .done( function( res ) {
                $btn.prop( 'disabled', false ).text( '📥 استيراد الأرقام' );
                if ( ! res.success ) { toast( CC.i18n.error, 'error' ); return; }
                var d = res.data;
                $( '#kayan-cc-import-result' ).html(
                    '<div class="kayan-cc-notice kayan-cc-notice--success">' +
                    '<span class="kcc-new">✓ تم استيراد ' + d.imported + ' رقم جديد.</span> ' +
                    '<span class="kcc-skip">(' + d.skipped + ' رقم موجود مسبقاً)</span> ' +
                    '<a href="' + escHtml( window.location.origin + '/wp-admin/admin.php?page=kayan-cc-phone-manager' ) + '">فتح مدير الأرقام</a>' +
                    '</div>'
                ).show();
            } )
            .fail( function() {
                $btn.prop( 'disabled', false ).text( '📥 استيراد الأرقام' );
                toast( CC.i18n.error, 'error' );
            } );
    } );

    var scanResultsPage = 1;
    function loadScanResults( page ) {
        if ( ! $( '#kayan-cc-scan-results-table' ).length ) return;
        scanResultsPage = page;
        ajaxGet( 'kayan_cc_get_scan_results', { page: page } )
            .done( function( res ) {
                if ( ! res.success ) return;
                renderScanResults( res.data );
            } );
    }

    function renderScanResults( data ) {
        if ( ! data.results || ! data.results.length ) {
            $( '#kayan-cc-scan-results-table' ).html( '<p class="kayan-cc-empty">لا توجد نتائج.</p>' );
            return;
        }
        var rows = data.results.map( function( r ) {
            var postLink = r.source_id > 0
                ? '<a href="' + escHtml( '/wp-admin/post.php?post=' + r.source_id + '&action=edit' ) + '" target="_blank">' + r.source_id + '</a>'
                : '-';
            return '<tr>' +
                '<td class="kayan-cc-monospace">' + escHtml( r.raw_value ) + '</td>' +
                '<td class="kayan-cc-monospace">' + escHtml( r.normalized ) + '</td>' +
                '<td><span class="kayan-cc-badge kayan-cc-badge--' + r.result_type + '">' + resultTypeLabel( r.result_type ) + '</span></td>' +
                '<td><small>' + escHtml( r.source_field ) + '</small></td>' +
                '<td>' + postLink + '</td>' +
                '</tr>';
        } ).join( '' );

        $( '#kayan-cc-scan-results-table' ).html(
            '<div class="kayan-cc-table-scroll-wrap"><table class="kayan-cc-table"><thead><tr>' +
            '<th>القيمة</th><th>الرقم الموحد</th><th>النوع</th><th>المصدر</th><th>المقالة</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table></div>'
        );
        renderPagination( $( '#kayan-cc-scan-pagination' ), data.paging, loadScanResults );
    }

    $( document ).on( 'change', '#kayan-cc-filter-type', function() { loadScanResults( 1 ); } );

    /* ════════════════════════════════════════════════════════════
       ASSIGNMENT
    ════════════════════════════════════════════════════════════ */
    $( document ).on( 'change', '#kayan-cc-assign-scope-type', function() {
        var val = $( this ).val();
        $( '#kayan-cc-scope-post-type' ).toggle( val === 'post_type' );
        $( '#kayan-cc-scope-taxonomy' ).toggle( val === 'taxonomy_term' );
        $( '#kayan-cc-scope-post' ).toggle( val === 'post' );
    } );

    $( document ).on( 'change', '#kayan-cc-assign-taxonomy', function() {
        loadTermsFor( $( this ).val(), $( '#kayan-cc-assign-term' ) );
    } );

    function loadTermsFor( taxonomy, $select ) {
        $select.html( '<option value="">جارٍ التحميل...</option>' );
        $.get( '/wp-json/wp/v2/' + taxonomy + '?per_page=100&_fields=id,name' )
            .done( function( terms ) {
                var opts = '<option value="">-- اختر القسم --</option>';
                $.each( terms, function( i, t ) { opts += '<option value="' + t.id + '">' + escHtml( t.name ) + '</option>'; } );
                $select.html( opts );
            } )
            .fail( function() {
                // Try as taxonomy slug
                $.get( '/wp-json/wp/v2/' + taxonomy + 's?per_page=100&_fields=id,name' )
                    .done( function( terms ) {
                        var opts = '<option value="">-- اختر القسم --</option>';
                        $.each( terms, function( i, t ) { opts += '<option value="' + t.id + '">' + escHtml( t.name ) + '</option>'; } );
                        $select.html( opts );
                    } )
                    .fail( function() { $select.html( '<option value="">تعذر التحميل</option>' ); } );
            } );
    }

    var postSearchTimeout;
    $( document ).on( 'input', '#kayan-cc-post-search', function() {
        var query = $( this ).val();
        clearTimeout( postSearchTimeout );
        if ( query.length < 2 ) { $( '#kayan-cc-post-search-results' ).empty(); return; }
        postSearchTimeout = setTimeout( function() {
            $.get( '/wp-json/wp/v2/posts?search=' + encodeURIComponent( query ) + '&per_page=10&_fields=id,title' )
                .done( function( posts ) {
                    var html = posts.map( function( p ) {
                        return '<div class="kcc-ac-item" data-id="' + p.id + '">' + escHtml( p.title.rendered ) + '</div>';
                    } ).join( '' );
                    $( '#kayan-cc-post-search-results' ).html( html );
                } );
        }, 300 );
    } );

    $( document ).on( 'click', '#kayan-cc-post-search-results .kcc-ac-item', function() {
        $( '#kayan-cc-assign-post-id' ).val( $( this ).data( 'id' ) );
        $( '#kayan-cc-post-search' ).val( $( this ).text() );
        $( '#kayan-cc-post-search-results' ).empty();
    } );

    $( document ).on( 'submit', '#kayan-cc-assign-form', function( e ) {
        e.preventDefault();
        var data = {};
        $( this ).serializeArray().forEach( function( f ) { data[ f.name ] = f.value; } );
        var scopeType = data.scope_type;
        if ( scopeType === 'taxonomy_term' ) {
            data.scope_id       = $( '#kayan-cc-assign-term' ).val();
            data.scope_taxonomy = $( '#kayan-cc-assign-taxonomy' ).val();
        } else if ( scopeType === 'post_type' ) {
            data.scope_id       = 0;
            data.scope_taxonomy = $( '#kayan-cc-assign-post-type' ).val();
        } else if ( scopeType === 'post' ) {
            data.scope_id = $( '#kayan-cc-assign-post-id' ).val();
        }
        ajax( 'kayan_cc_assign_number', data )
            .done( function( res ) {
                if ( res.success ) { toast( 'تم تعيين الرقم بنجاح.' ); loadAssignments(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    function loadAssignments() {
        if ( ! $( '#kayan-cc-assignments-list' ).length ) return;
        ajaxGet( 'kayan_cc_get_assignments', {} )
            .done( function( res ) {
                if ( ! res.success ) return;
                renderAssignments( res.data );
            } );
    }

    function renderAssignments( rows ) {
        if ( ! rows.length ) {
            $( '#kayan-cc-assignments-list' ).html( '<p class="kayan-cc-empty">لا توجد تعيينات حتى الآن.</p>' );
            return;
        }
        var html = '<div class="kayan-cc-table-scroll-wrap"><table class="kayan-cc-table"><thead><tr><th>الرقم</th><th>النطاق</th><th>التفاصيل</th><th>حذف</th></tr></thead><tbody>';
        $.each( rows, function( i, r ) {
            html += '<tr><td><strong>' + escHtml( r.name ) + '</strong><br><small>' + escHtml( r.phone_number ) + '</small></td>' +
                '<td>' + escHtml( r.scope_type ) + '</td>' +
                '<td>' + escHtml( r.scope_id > 0 ? '#' + r.scope_id : r.scope_taxonomy || 'الموقع' ) + '</td>' +
                '<td><button class="button button-small kayan-cc-remove-assignment" data-id="' + r.id + '">حذف</button></td></tr>';
        } );
        html += '</tbody></table></div>';
        $( '#kayan-cc-assignments-list' ).html( html );
    }

    $( document ).on( 'click', '.kayan-cc-remove-assignment', function() {
        if ( ! window.confirm( CC.i18n.confirm_delete ) ) return;
        var id = $( this ).data( 'id' );
        ajax( 'kayan_cc_remove_assignment', { assignment_id: id } )
            .done( function( res ) {
                if ( res.success ) { toast( CC.i18n.deleted ); loadAssignments(); }
            } );
    } );

    /* ════════════════════════════════════════════════════════════
       PHONE REPLACEMENT
    ════════════════════════════════════════════════════════════ */
    $( document ).on( 'change', '#kayan-cc-replace-scope', function() {
        $( '#kayan-cc-replace-taxonomy-row' ).toggle( $( this ).val() === 'taxonomy' );
    } );

    $( document ).on( 'change', '#kayan-cc-replace-taxonomy', function() {
        loadTermsFor( $( this ).val(), $( '#kayan-cc-replace-term' ) );
    } );

    $( document ).on( 'click', '#kayan-cc-replace-preview-btn', function() {
        var old_number = $( '#kayan-cc-old-number' ).val().trim();
        var new_number = $( '#kayan-cc-new-number' ).val().trim();
        if ( ! old_number || ! new_number ) { toast( 'يرجى إدخال الرقم القديم والجديد.', 'warning' ); return; }
        $( this ).prop( 'disabled', true ).text( 'جارٍ المعاينة...' );
        ajax( 'kayan_cc_replace_preview', {
            old_number: old_number, new_number: new_number,
            scope_type: $( '#kayan-cc-replace-scope' ).val(),
            taxonomy: $( '#kayan-cc-replace-taxonomy' ).val(),
            term_id:  $( '#kayan-cc-replace-term' ).val(),
            include_children: $( '#kayan-cc-replace-include-children' ).is( ':checked' ) ? 1 : 0,
        } ).done( function( res ) {
            $( '#kayan-cc-replace-preview-btn' ).prop( 'disabled', false ).text( '🔍 معاينة قبل التنفيذ' );
            if ( ! res.success ) { toast( res.data.message, 'error' ); return; }
            renderReplacePreview( res.data );
        } ).fail( function() {
            $( '#kayan-cc-replace-preview-btn' ).prop( 'disabled', false ).text( '🔍 معاينة قبل التنفيذ' );
        } );
    } );

    function renderReplacePreview( data ) {
        $( '#kayan-cc-replace-preview-summary' ).html(
            '<strong>الرقم القديم:</strong> ' + escHtml( data.old_number ) + ' &nbsp; ' +
            '<strong>الرقم الجديد:</strong> ' + escHtml( data.new_number ) + '<br>' +
            '<strong>المقالات المتأثرة:</strong> ' + data.affected_posts + ' &nbsp; ' +
            '<strong>مجموع الظهورات:</strong> ' + data.total_matches
        );
        if ( ! data.items.length ) {
            $( '#kayan-cc-replace-preview-table' ).html( '<p class="kayan-cc-empty">لم يُعثر على الرقم في النطاق المحدد.</p>' );
        } else {
            var rows = data.items.map( function( item ) {
                var locs = item.matches.map( function( m ) { return escHtml( m.location ) + ' (' + m.count + ')'; } ).join( ', ' );
                return '<tr><td><a href="' + escHtml( item.edit_url ) + '" target="_blank">' + escHtml( item.post_title ) + '</a> <small>#' + item.post_id + '</small></td><td>' + locs + '</td></tr>';
            } ).join( '' );
            $( '#kayan-cc-replace-preview-table' ).html(
                '<div class="kayan-cc-table-scroll-wrap"><table class="kayan-cc-table"><thead><tr><th>المقالة</th><th>أماكن الظهور</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
            );
        }
        $( '#kayan-cc-replace-preview-wrap' ).show();
        $( '#kayan-cc-replace-result' ).hide();
        $( '#kayan-cc-replace-execute-btn' ).data( 'preview', data );
    }

    $( document ).on( 'click', '#kayan-cc-replace-execute-btn', function() {
        if ( ! window.confirm( CC.i18n.confirm_replace ) ) return;
        $( this ).prop( 'disabled', true ).text( 'جارٍ التنفيذ...' );
        ajax( 'kayan_cc_replace_execute', {
            old_number: $( '#kayan-cc-old-number' ).val().trim(),
            new_number: $( '#kayan-cc-new-number' ).val().trim(),
            scope_type: $( '#kayan-cc-replace-scope' ).val(),
            taxonomy:   $( '#kayan-cc-replace-taxonomy' ).val(),
            term_id:    $( '#kayan-cc-replace-term' ).val(),
            include_children: $( '#kayan-cc-replace-include-children' ).is( ':checked' ) ? 1 : 0,
        } ).done( function( res ) {
            $( '#kayan-cc-replace-execute-btn' ).prop( 'disabled', false ).text( '⚠ تنفيذ الاستبدال' );
            if ( ! res.success ) { toast( res.data.message, 'error' ); return; }
            $( '#kayan-cc-replace-result-text' ).text( ' تم تغيير ' + res.data.posts_changed + ' مقالة. ' );
            $( '#kayan-cc-replace-result' ).show();
            $( '#kayan-cc-replace-preview-wrap' ).hide();
            toast( 'تم تنفيذ الاستبدال بنجاح.' );
        } ).fail( function() {
            $( '#kayan-cc-replace-execute-btn' ).prop( 'disabled', false ).text( '⚠ تنفيذ الاستبدال' );
        } );
    } );

    /* ════════════════════════════════════════════════════════════
       SEO MANAGER
    ════════════════════════════════════════════════════════════ */
    var seoPage    = 1;
    var seoPerPage = 20;

    function loadSeoPosts() {
        if ( ! $( '#kayan-cc-seo-posts-table' ).length ) return;
        ajaxGet( 'kayan_cc_get_seo_posts', {
            page:      seoPage,
            per_page:  seoPerPage,
            search:    $( '#kayan-cc-seo-search' ).val(),
            post_type: $( '#kayan-cc-seo-post-type' ).val(),
        } ).done( function( res ) {
            if ( ! res.success ) return;
            renderSeoTable( res.data );
        } );
    }

    function renderSeoTable( data ) {
        if ( ! data.posts.length ) {
            $( '#kayan-cc-seo-posts-table' ).html( '<p class="kayan-cc-empty">لا توجد مقالات.</p>' );
            $( '#kayan-cc-seo-pagination' ).empty();
            return;
        }
        var rows = data.posts.map( function( p ) {
            var seoDisplay = p.seo_title ? escHtml( p.seo_title ) : '<em style="color:#999">غير محدد (سيستخدم Rank Math الافتراضي)</em>';
            return '<tr data-post-id="' + p.post_id + '">' +
                '<td><input type="checkbox" class="kayan-cc-seo-select-post" value="' + p.post_id + '"></td>' +
                '<td><a href="' + escHtml( p.edit_url ) + '" target="_blank">' + escHtml( p.title ) + '</a></td>' +
                '<td><div class="kayan-cc-seo-title-cell">' +
                    '<span class="kayan-cc-seo-title-display">' + seoDisplay + '</span>' +
                    '<input type="text" class="kayan-cc-seo-title-input kayan-cc-input" value="' + escAttr( p.seo_title || '' ) + '" style="display:none">' +
                '</div></td>' +
                '<td><small>' + escHtml( p.post_type ) + '</small></td>' +
                '<td class="kayan-cc-actions">' +
                    '<button class="button button-small kayan-cc-seo-edit-btn" data-id="' + p.post_id + '">تعديل</button> ' +
                    '<button class="button button-small kayan-cc-seo-save-btn" data-id="' + p.post_id + '" style="display:none">حفظ</button> ' +
                    '<button class="button button-small kayan-cc-seo-rollback-btn" data-id="' + p.post_id + '">تراجع</button> ' +
                    '<button class="button button-small kayan-cc-seo-clear-btn" data-id="' + p.post_id + '">مسح</button>' +
                '</td>' +
                '</tr>';
        } ).join( '' );

        $( '#kayan-cc-seo-posts-table' ).html(
            '<table class="kayan-cc-table kayan-cc-table--seo kayan-cc-table--responsive">' +
            '<thead><tr>' +
            '<th><input type="checkbox" id="kayan-cc-seo-select-all"></th>' +
            '<th>عنوان المقالة</th><th>عنوان SEO (Rank Math)</th><th>النوع</th><th>الإجراءات</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table>'
        );

        renderPagination(
            $( '#kayan-cc-seo-pagination' ),
            { total: data.total, per_page: seoPerPage, current_page: seoPage, total_pages: data.total_pages },
            function( p ) { seoPage = p; loadSeoPosts(); }
        );
        updateBulkBtnState();
    }

    $( document ).on( 'change', '#kayan-cc-seo-per-page', function() {
        var v = parseInt( $( this ).val(), 10 );
        if ( isNaN( v ) || v < 5 ) v = 5;
        if ( v > 2000 ) v = 2000;
        $( this ).val( v );
        syncPresetActive( $( this ) );
        seoPerPage = v;
        seoPage    = 1;
        loadSeoPosts();
    } );

    $( document ).on( 'keydown', '#kayan-cc-seo-per-page', function( e ) {
        if ( e.key === 'Enter' ) { $( this ).trigger( 'change' ); }
    } );

    $( document ).on( 'change', '#kayan-cc-seo-select-all', function() {
        $( '.kayan-cc-seo-select-post' ).prop( 'checked', $( this ).is( ':checked' ) );
        updateBulkBtnState();
    } );
    $( document ).on( 'change', '.kayan-cc-seo-select-post', updateBulkBtnState );

    function updateBulkBtnState() {
        $( '#kayan-cc-seo-bulk-apply' ).prop( 'disabled', ! $( '.kayan-cc-seo-select-post:checked' ).length );
    }

    $( document ).on( 'click', '.kayan-cc-seo-edit-btn', function() {
        var $row = $( this ).closest( 'tr' );
        $row.find( '.kayan-cc-seo-title-display' ).hide();
        $row.find( '.kayan-cc-seo-title-input' ).show().focus();
        $( this ).hide();
        $row.find( '.kayan-cc-seo-save-btn' ).show();
    } );

    $( document ).on( 'click', '.kayan-cc-seo-save-btn', function() {
        var id    = $( this ).data( 'id' );
        var title = $( this ).closest( 'tr' ).find( '.kayan-cc-seo-title-input' ).val();
        ajax( 'kayan_cc_set_seo_title', { post_id: id, seo_title: title } )
            .done( function( res ) {
                if ( res.success ) { toast( CC.i18n.saved ); loadSeoPosts(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    $( document ).on( 'click', '.kayan-cc-seo-rollback-btn', function() {
        if ( ! window.confirm( 'هل تريد التراجع إلى العنوان السابق؟' ) ) return;
        ajax( 'kayan_cc_rollback_seo_title', { post_id: $( this ).data( 'id' ) } )
            .done( function( res ) {
                if ( res.success ) { toast( 'تم التراجع.', 'success' ); loadSeoPosts(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    $( document ).on( 'click', '.kayan-cc-seo-clear-btn', function() {
        if ( ! window.confirm( 'هل تريد مسح عنوان SEO المخصص والعودة للعنوان الافتراضي لـ Rank Math؟' ) ) return;
        ajax( 'kayan_cc_clear_seo_title', { post_id: $( this ).data( 'id' ) } )
            .done( function( res ) {
                if ( res.success ) { toast( 'تم المسح.', 'success' ); loadSeoPosts(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    $( document ).on( 'click', '#kayan-cc-seo-bulk-apply', function() {
        var ids      = $( '.kayan-cc-seo-select-post:checked' ).map( function() { return $( this ).val(); } ).get();
        var template = $( '#kayan-cc-seo-bulk-template' ).val();
        if ( ! template ) { toast( 'أدخل قالب العنوان أولاً.', 'warning' ); return; }
        ajax( 'kayan_cc_seo_bulk_preview', { post_ids: ids, template: template } )
            .done( function( res ) {
                if ( ! res.success ) { toast( res.data.message, 'error' ); return; }
                renderSeoBulkPreview( res.data, ids, template );
            } );
    } );

    function renderSeoBulkPreview( previews, ids, template ) {
        var rows = previews.map( function( p ) {
            var warnings = p.warnings.length ? '<br><small style="color:#e67e22">⚠ ' + p.warnings.join( ' | ' ) + '</small>' : '';
            return '<tr><td>' + escHtml( p.post_title ) + '</td><td>' + escHtml( p.current_seo || '' ) + '</td><td>' + escHtml( p.new_seo ) + warnings + '</td></tr>';
        } ).join( '' );
        $( '#kayan-cc-seo-bulk-preview-table' ).html(
            '<div class="kayan-cc-table-scroll-wrap"><table class="kayan-cc-table"><thead><tr><th>المقالة</th><th>الحالي</th><th>الجديد</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
        );
        $( '#kayan-cc-seo-bulk-preview-wrap' ).show().data( { ids: ids, template: template } );
        $( '#kayan-cc-seo-bulk-confirm' ).prop( 'disabled', false );
    }

    $( document ).on( 'click', '#kayan-cc-seo-bulk-confirm', function() {
        if ( ! window.confirm( CC.i18n.confirm_bulk_seo ) ) return;
        var $w = $( '#kayan-cc-seo-bulk-preview-wrap' );
        ajax( 'kayan_cc_seo_bulk_apply', { post_ids: $w.data( 'ids' ), template: $w.data( 'template' ) } )
            .done( function( res ) {
                if ( res.success ) { toast( 'تم التطبيق: ' + res.data.success + ' مقالة.' ); $w.hide(); loadSeoPosts(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    $( document ).on( 'click', '#kayan-cc-seo-bulk-cancel', function() { $( '#kayan-cc-seo-bulk-preview-wrap' ).hide(); } );

    $( document ).on( 'input', '#kayan-cc-seo-search', debounce( function() { seoPage = 1; loadSeoPosts(); }, 400 ) );
    $( document ).on( 'change', '#kayan-cc-seo-post-type', function() { seoPage = 1; loadSeoPosts(); } );

    /* ════════════════════════════════════════════════════════════
       SEO TEMPLATES
    ════════════════════════════════════════════════════════════ */
    function loadTemplates() {
        if ( ! $( '#kayan-cc-templates-list' ).length ) return;
        ajaxGet( 'kayan_cc_get_seo_templates', {} )
            .done( function( res ) { if ( res.success ) renderTemplates( res.data ); } );
    }

    function renderTemplates( templates ) {
        if ( ! templates.length ) { $( '#kayan-cc-templates-list' ).html( '<p class="kayan-cc-empty">لا توجد قوالب محفوظة.</p>' ); return; }
        var rows = templates.map( function( t ) {
            return '<tr><td><strong>' + escHtml( t.name ) + '</strong></td>' +
                '<td class="kayan-cc-monospace">' + escHtml( t.template ) + '</td>' +
                '<td>' + escHtml( t.scope_type ) + '</td>' +
                '<td class="kayan-cc-actions">' +
                    '<button class="button button-small kayan-cc-edit-template" data-id="' + t.id + '">تعديل</button> ' +
                    '<button class="button button-small kayan-cc-apply-template" data-id="' + t.id + '" data-name="' + escAttr( t.name ) + '">تطبيق</button> ' +
                    '<button class="button button-small button-link-delete kayan-cc-delete-template" data-id="' + t.id + '">حذف</button>' +
                '</td></tr>';
        } ).join( '' );
        $( '#kayan-cc-templates-list' ).html(
            '<div class="kayan-cc-table-scroll-wrap"><table class="kayan-cc-table"><thead><tr><th>الاسم</th><th>القالب</th><th>النطاق</th><th>الإجراءات</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
        );
    }

    $( document ).on( 'click', '#kayan-cc-add-template', function() {
        $( '#kayan-cc-template-id' ).val( '' );
        $( '#kayan-cc-template-form' )[ 0 ] && $( '#kayan-cc-template-form' )[ 0 ].reset();
        $( '#kayan-cc-template-modal-title' ).text( 'إضافة قالب جديد' );
        $( '#kayan-cc-template-modal' ).show();
    } );

    $( document ).on( 'click', '.kayan-cc-insert-var', function() {
        var $input = $( '#kayan-cc-template-text' );
        var pos = $input[ 0 ].selectionStart;
        var val = $input.val();
        $input.val( val.slice( 0, pos ) + $( this ).data( 'var' ) + val.slice( pos ) ).focus();
    } );

    $( document ).on( 'change', '#kayan-cc-template-scope-type', function() {
        var val = $( this ).val();
        $( '#kayan-cc-template-scope-post-type' ).toggle( val === 'post_type' );
        $( '#kayan-cc-template-scope-term' ).toggle( val === 'taxonomy_term' );
    } );

    $( document ).on( 'change', '#kayan-cc-template-taxonomy', function() { loadTermsFor( $( this ).val(), $( '#kayan-cc-template-term' ) ); } );

    $( document ).on( 'click', '#kayan-cc-save-template', function() {
        var id   = $( '#kayan-cc-template-id' ).val();
        var data = {};
        $( '#kayan-cc-template-form' ).serializeArray().forEach( function( f ) { data[ f.name ] = f.value; } );
        ajax( id ? 'kayan_cc_update_seo_template' : 'kayan_cc_add_seo_template', data )
            .done( function( res ) {
                if ( res.success ) { toast( CC.i18n.saved ); $( '#kayan-cc-template-modal' ).hide(); loadTemplates(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    $( document ).on( 'click', '.kayan-cc-delete-template', function() {
        if ( ! window.confirm( CC.i18n.confirm_delete ) ) return;
        ajax( 'kayan_cc_delete_seo_template', { id: $( this ).data( 'id' ) } )
            .done( function( res ) { if ( res.success ) { toast( CC.i18n.deleted ); loadTemplates(); } } );
    } );

    $( document ).on( 'click', '.kayan-cc-apply-template', function() {
        var id = $( this ).data( 'id' );
        $( '#kayan-cc-apply-template-id' ).val( id );
        $( '#kayan-cc-apply-template-name' ).text( $( this ).data( 'name' ) );
        $( '#kayan-cc-apply-template-preview' ).html( '<p class="kayan-cc-loading">جارٍ التحميل...</p>' );
        $( '#kayan-cc-apply-template-confirm' ).prop( 'disabled', true );
        $( '#kayan-cc-apply-template-modal' ).show();
        ajax( 'kayan_cc_apply_template_preview', { template_id: id, post_ids: [] } )
            .done( function( res ) {
                if ( ! res.success ) { $( '#kayan-cc-apply-template-preview' ).html( '<p>' + res.data.message + '</p>' ); return; }
                renderApplyTemplatePreview( res.data );
                $( '#kayan-cc-apply-template-confirm' ).prop( 'disabled', false ).data( 'post-ids', res.data.map( function( p ) { return p.post_id; } ) );
            } );
    } );

    function renderApplyTemplatePreview( previews ) {
        if ( ! previews.length ) { $( '#kayan-cc-apply-template-preview' ).html( '<p class="kayan-cc-empty">لا توجد مقالات في نطاق هذا القالب.</p>' ); return; }
        var rows = previews.map( function( p ) { return '<tr><td>' + escHtml( p.post_title ) + '</td><td>' + escHtml( p.new_seo ) + '</td></tr>'; } ).join( '' );
        $( '#kayan-cc-apply-template-preview' ).html(
            '<div class="kayan-cc-table-scroll-wrap"><table class="kayan-cc-table"><thead><tr><th>المقالة</th><th>العنوان الجديد</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
        );
    }

    $( document ).on( 'click', '#kayan-cc-apply-template-confirm', function() {
        if ( ! window.confirm( CC.i18n.confirm_bulk_seo ) ) return;
        ajax( 'kayan_cc_apply_template_execute', { template_id: $( '#kayan-cc-apply-template-id' ).val(), post_ids: $( this ).data( 'post-ids' ) || [] } )
            .done( function( res ) {
                if ( res.success ) { toast( 'تم التطبيق: ' + res.data.success + ' مقالة.' ); $( '#kayan-cc-apply-template-modal' ).hide(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    /* ════════════════════════════════════════════════════════════
       ACTIVITY LOG
    ════════════════════════════════════════════════════════════ */
    var logPage = 1;

    function loadActivityLog() {
        if ( ! $( '#kayan-cc-log-table' ).length ) return;
        ajaxGet( 'kayan_cc_get_activity_log', {
            page:          logPage,
            action_filter: $( '#kayan-cc-log-action-filter' ).val(),
            object_type:   $( '#kayan-cc-log-type-filter' ).val(),
        } ).done( function( res ) {
            if ( ! res.success ) return;
            renderLogTable( res.data );
        } );
    }

    function renderLogTable( data ) {
        if ( ! data.rows.length ) { $( '#kayan-cc-log-table' ).html( '<p class="kayan-cc-empty">لا توجد سجلات.</p>' ); return; }
        var rows = data.rows.map( function( r ) {
            var rollbackBtn = '';
            if ( r.is_reversible == 1 && ! parseInt( r.reverted_by ) ) {
                rollbackBtn = '<button class="button button-small kayan-cc-rollback-btn" data-id="' + r.id + '" data-type="' + r.action + '">↩ تراجع</button>';
            } else if ( parseInt( r.reverted_by ) ) {
                rollbackBtn = '<span class="kayan-cc-badge kayan-cc-badge--reverted">تم التراجع</span>';
            }
            return '<tr>' +
                '<td>' + r.id + '</td>' +
                '<td><span class="kayan-cc-badge kayan-cc-badge--action">' + escHtml( r.action_label ) + '</span></td>' +
                '<td>' + escHtml( r.object_type ) + '</td>' +
                '<td>' + escHtml( r.user_display ) + '</td>' +
                '<td>' + ( r.data_before ? '<button class="button button-small kayan-cc-view-data" data-content="' + escAttr( r.data_before ) + '">عرض</button>' : '-' ) + '</td>' +
                '<td>' + ( r.data_after  ? '<button class="button button-small kayan-cc-view-data" data-content="' + escAttr( r.data_after  ) + '">عرض</button>' : '-' ) + '</td>' +
                '<td><small>' + escHtml( r.created_at ) + '</small></td>' +
                '<td>' + rollbackBtn + '</td>' +
                '</tr>';
        } ).join( '' );
        $( '#kayan-cc-log-table' ).html(
            '<div class="kayan-cc-table-scroll-wrap"><table class="kayan-cc-table kayan-cc-table--log"><thead><tr>' +
            '<th>#</th><th>العملية</th><th>النوع</th><th>المستخدم</th><th>قبل</th><th>بعد</th><th>التاريخ</th><th>التراجع</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table></div>'
        );
        renderPagination( $( '#kayan-cc-log-pagination' ), data.paging, function( p ) { logPage = p; loadActivityLog(); } );
    }

    $( document ).on( 'change', '#kayan-cc-log-action-filter, #kayan-cc-log-type-filter', function() { logPage = 1; loadActivityLog(); } );

    $( document ).on( 'click', '.kayan-cc-rollback-btn', function() {
        $( '#kayan-cc-rollback-log-id' ).val( $( this ).data( 'id' ) );
        $( '#kayan-cc-rollback-type' ).val( $( this ).data( 'type' ) );
        $( '#kayan-cc-rollback-modal' ).show();
    } );

    $( document ).on( 'click', '#kayan-cc-rollback-confirm', function() {
        var id   = $( '#kayan-cc-rollback-log-id' ).val();
        var type = $( '#kayan-cc-rollback-type' ).val();
        var action = type === 'replace_number' ? 'kayan_cc_replace_rollback' : null;
        if ( ! action ) { toast( 'هذا النوع من التراجع غير مدعوم حتى الآن.', 'warning' ); $( '#kayan-cc-rollback-modal' ).hide(); return; }
        ajax( action, { log_id: id } )
            .done( function( res ) {
                $( '#kayan-cc-rollback-modal' ).hide();
                if ( res.success ) { toast( 'تم التراجع بنجاح.' ); loadActivityLog(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    $( document ).on( 'click', '.kayan-cc-view-data', function() {
        var raw = $( this ).data( 'content' );
        var pretty = '';
        try { pretty = JSON.stringify( JSON.parse( raw ), null, 2 ); } catch ( e ) { pretty = raw; }
        $( '#kayan-cc-data-content' ).text( pretty );
        $( '#kayan-cc-data-modal' ).show();
    } );

    /* ── Auto-discover on phone manager page load ───────────────── */
    // 1. Check if scan results exist
    // 2a. If yes → import them silently, then load numbers
    // 2b. If no  → run a full background scan, then import, then load numbers
    function autoImportOnLoad() {
        if ( ! $( '#kayan-cc-numbers-table' ).length ) return;

        ajaxGet( 'kayan_cc_get_scan_results', { page: 1, per_page: 1 } )
            .done( function( res ) {
                var hasScanData = res.success && res.data && res.data.paging && res.data.paging.total > 0;
                if ( hasScanData ) {
                    // Already have scan data → import silently
                    ajax( 'kayan_cc_import_from_scan', {} )
                        .done( function( imp ) {
                            if ( imp.success && imp.data.imported > 0 ) {
                                toast( '✅ تم استيراد ' + imp.data.imported + ' رقم جديد.', 'success' );
                            }
                            loadNumbers();
                        } )
                        .fail( function() { loadNumbers(); } );
                } else {
                    // No scan data → run full auto-scan
                    runAutoScan();
                }
            } )
            .fail( function() { loadNumbers(); } );
    }

    function runAutoScan() {
        if ( rescanRunning ) return;
        rescanRunning = true;
        $( '#kayan-cc-rescan-btn' ).prop( 'disabled', true ).text( '⏳ جارٍ الاكتشاف...' );
        $( '#kayan-cc-rescan-progress' ).show();
        $( '#kayan-cc-rescan-fill' ).css( 'width', '5%' );
        $( '#kayan-cc-rescan-status' ).text( 'جارٍ اكتشاف الأرقام تلقائياً...' );

        ajax( 'kayan_cc_start_scan', {} )
            .done( function( res ) {
                if ( ! res.success ) { endRescan( 'خطأ في بدء الفحص.' ); loadNumbers(); return; }
                processRescanBatch( 1, 0 );
            } )
            .fail( function() { endRescan( 'تعذّر الاتصال.' ); loadNumbers(); } );
    }

    /* ── Inline Rescan (phone manager toolbar button) ────────────── */
    var rescanRunning = false;

    $( document ).on( 'click', '#kayan-cc-rescan-btn', function() {
        if ( rescanRunning ) return;
        $( '#kayan-cc-rescan-status' ).text( 'بدء الفحص...' );
        runAutoScan();
    } );

    $( document ).on( 'click', '#kayan-cc-rescan-cancel', function() {
        endRescan( 'تم إلغاء الفحص.' );
    } );

    function processRescanBatch( batchNum, found ) {
        if ( ! rescanRunning ) return;
        ajax( 'kayan_cc_scan_batch', {} )
            .done( function( res ) {
                if ( ! res.success ) { endRescan( 'خطأ في دفعة الفحص.' ); return; }
                var d       = res.data;
                found       = ( d.found_in_batch || 0 ) + found;
                var pct     = d.done ? 100 : Math.min( 90, 5 + batchNum * 8 );
                $( '#kayan-cc-rescan-fill' ).css( 'width', pct + '%' );
                var phaseLabel = { posts: 'المقالات', options: 'الإعدادات', widgets: 'الودجات', menus: 'القوائم' };
                var phaseName  = d.status && d.status.phase ? ( phaseLabel[ d.status.phase ] || d.status.phase ) : '';
                $( '#kayan-cc-rescan-status' ).text(
                    d.done ? 'اكتمل الفحص — جارٍ الاستيراد...'
                           : ( phaseName ? 'جارٍ فحص ' + phaseName + '...' : 'جارٍ الفحص...' )
                );
                if ( d.done ) {
                    importAfterRescan();
                } else {
                    setTimeout( function() { processRescanBatch( batchNum + 1, found ); }, 150 );
                }
            } )
            .fail( function() { endRescan( 'انقطع الاتصال.' ); } );
    }

    function importAfterRescan() {
        ajax( 'kayan_cc_import_from_scan', {} )
            .done( function( res ) {
                var msg = res.success
                    ? '✅ اكتمل الفحص — استيراد ' + res.data.imported + ' رقم جديد'
                    : 'اكتمل الفحص.';
                endRescan( msg );
                if ( res.success && res.data.imported > 0 ) {
                    toast( 'تم استيراد ' + res.data.imported + ' رقم جديد.', 'success' );
                } else {
                    toast( 'الفحص اكتمل — لا أرقام جديدة.', 'success' );
                }
                loadNumbers();
            } )
            .fail( function() { endRescan( 'اكتمل الفحص.' ); loadNumbers(); } );
    }

    function endRescan( msg ) {
        rescanRunning = false;
        $( '#kayan-cc-rescan-btn' ).prop( 'disabled', false ).text( '🔄 فحص وتحديث' );
        $( '#kayan-cc-rescan-fill' ).css( 'width', '100%' );
        $( '#kayan-cc-rescan-status' ).text( msg || 'اكتمل.' );
        setTimeout( function() { $( '#kayan-cc-rescan-progress' ).fadeOut( 400 ); }, 3000 );
    }

    /* ════════════════════════════════════════════════════════════
       INIT
    ════════════════════════════════════════════════════════════ */
    $( function() {
        autoImportOnLoad();   // silently import from last scan, then loadNumbers()
        loadScanResults( 1 );
        loadAssignments();
        loadSeoPosts();
        loadTemplates();
        loadActivityLog();

        if ( $( '#kayan-cc-scan-results-wrap' ).is( ':visible' ) ) loadScanResults( 1 );

        // Handle window resize (switch between table and card view)
        $( window ).on( 'resize', debounce( function() { loadNumbers(); }, 500 ) );
    } );

} )( jQuery );
