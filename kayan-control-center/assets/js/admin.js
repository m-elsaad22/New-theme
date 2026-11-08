/**
 * KAYAN Control Center – Admin JavaScript
 * Handles all AJAX interactions for the admin UI.
 */
/* global jQuery, KayanCC, ajaxurl */

( function( $ ) {
    'use strict';

    var CC = KayanCC;

    /* ── Utilities ─────────────────────────────────────────────── */

    function ajax( action, data, method ) {
        method = method || 'POST';
        var params = $.extend( {}, data, { action: action, nonce: CC.nonce } );
        return $.ajax( {
            url:    CC.ajax_url,
            method: method,
            data:   params,
        } );
    }

    function ajaxGet( action, data ) {
        return ajax( action, data, 'GET' );
    }

    function toast( msg, type ) {
        type = type || 'success';
        var $t = $( '<div class="kayan-cc-toast kayan-cc-toast--' + type + '">' + msg + '</div>' );
        $( 'body' ).append( $t );
        setTimeout( function() { $t.fadeOut( 400, function() { $t.remove(); } ); }, 3500 );
    }

    function confirm( msg ) {
        return window.confirm( msg );
    }

    function renderPagination( $container, paging, onPageClick ) {
        if ( paging.total_pages <= 1 ) { $container.empty(); return; }
        var html = '';
        for ( var i = 1; i <= paging.total_pages; i++ ) {
            html += '<button class="page-btn' + ( i === paging.current_page ? ' active' : '' ) +
                    '" data-page="' + i + '">' + i + '</button>';
        }
        $container.html( html );
        $container.find( '.page-btn' ).on( 'click', function() {
            onPageClick( parseInt( $( this ).data( 'page' ), 10 ) );
        } );
    }

    function statusLabel( status ) {
        return status === 'active' ? 'نشط' : 'متوقف';
    }

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

    /* ── Modals ─────────────────────────────────────────────────── */

    $( document ).on( 'click', '.kayan-cc-modal__overlay, .kayan-cc-modal__close', function() {
        $( this ).closest( '.kayan-cc-modal' ).hide();
    } );

    /* ── Toasts on WP admin notices ─────────────────────────────── */
    // (standard notices still work; toasts are for AJAX feedback)

    /* ════════════════════════════════════════════════════════════
       PHONE MANAGER
    ════════════════════════════════════════════════════════════ */
    var $numbersTable  = $( '#kayan-cc-numbers-table' );
    var $numbersPaging = $( '#kayan-cc-numbers-pagination' );
    var numbersPage    = 1;
    var numbersSearch  = '';
    var numbersStatus  = '';

    function loadNumbers() {
        if ( ! $numbersTable.length ) return;
        ajaxGet( 'kayan_cc_get_numbers', { page: numbersPage, search: numbersSearch, status: numbersStatus } )
            .done( function( res ) {
                if ( ! res.success ) { toast( CC.i18n.error, 'error' ); return; }
                renderNumbersTable( res.data );
            } )
            .fail( function() { toast( CC.i18n.error, 'error' ); } );
    }

    function renderNumbersTable( data ) {
        if ( ! data.numbers.length ) {
            $numbersTable.html( '<p class="kayan-cc-empty">' + CC.i18n.no_results + '</p>' );
            $numbersPaging.empty();
            return;
        }
        var rows = data.numbers.map( function( n ) {
            return '<tr data-id="' + n.id + '">' +
                '<td>' + n.id + '</td>' +
                '<td><strong>' + escHtml( n.name ) + '</strong></td>' +
                '<td class="kayan-cc-monospace">' + escHtml( n.phone_number ) + '</td>' +
                '<td class="kayan-cc-monospace">' + escHtml( n.whatsapp_number ) + '</td>' +
                '<td>' + escHtml( n.country ) + '</td>' +
                '<td>' + n.usage_count + '</td>' +
                '<td><span class="kayan-cc-badge kayan-cc-badge--' + n.status + '">' + statusLabel( n.status ) + '</span></td>' +
                '<td class="kayan-cc-actions">' +
                    '<button class="button button-small kayan-cc-edit-number" data-id="' + n.id + '">تعديل</button> ' +
                    '<button class="button button-small kayan-cc-toggle-number" data-id="' + n.id + '">' + ( n.status === 'active' ? 'تعطيل' : 'تفعيل' ) + '</button> ' +
                    '<button class="button button-small button-link-delete kayan-cc-delete-number" data-id="' + n.id + '">حذف</button>' +
                '</td>' +
                '</tr>';
        } ).join( '' );

        $numbersTable.html(
            '<table class="kayan-cc-table"><thead><tr>' +
            '<th>#</th><th>الاسم</th><th>رقم الاتصال</th><th>رقم واتساب</th><th>الدولة</th><th>الاستخدامات</th><th>الحالة</th><th>الإجراءات</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table>'
        );
        renderPagination( $numbersPaging, data.paging, function( p ) { numbersPage = p; loadNumbers(); } );
    }

    // Search & filter
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

    // Add
    $( document ).on( 'click', '#kayan-cc-add-number', function() {
        resetNumberForm();
        $( '#kayan-cc-modal-title' ).text( 'إضافة رقم جديد' );
        $( '#kayan-cc-number-modal' ).show();
    } );

    // Edit
    $( document ).on( 'click', '.kayan-cc-edit-number', function() {
        var id = $( this ).data( 'id' );
        ajax( 'kayan_cc_get_numbers', { page: 1, per_page: 1, search: id } )
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
                $( '#kayan-cc-modal-title' ).text( 'تعديل الرقم' );
                $( '#kayan-cc-number-modal' ).show();
            } );
    } );

    // Save
    $( document ).on( 'click', '#kayan-cc-save-number', function() {
        var id   = $( '#kayan-cc-number-id' ).val();
        var form = $( '#kayan-cc-number-form' ).serializeArray();
        var data = {};
        $.each( form, function( i, f ) { data[ f.name ] = f.value; } );
        if ( ! $( '#kayan-cc-field-call-enabled' ).is( ':checked' ) ) data.call_enabled = 0;
        if ( ! $( '#kayan-cc-field-wa-enabled' ).is( ':checked' ) ) data.wa_enabled = 0;

        var action = id ? 'kayan_cc_update_number' : 'kayan_cc_add_number';
        ajax( action, data )
            .done( function( res ) {
                if ( res.success ) {
                    toast( CC.i18n.saved );
                    $( '#kayan-cc-number-modal' ).hide();
                    loadNumbers();
                } else {
                    toast( res.data.message || CC.i18n.error, 'error' );
                }
            } )
            .fail( function() { toast( CC.i18n.error, 'error' ); } );
    } );

    // Toggle
    $( document ).on( 'click', '.kayan-cc-toggle-number', function() {
        var id = $( this ).data( 'id' );
        ajax( 'kayan_cc_toggle_number', { id: id } )
            .done( function( res ) {
                if ( res.success ) { loadNumbers(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    // Delete
    $( document ).on( 'click', '.kayan-cc-delete-number', function() {
        if ( ! confirm( CC.i18n.confirm_delete ) ) return;
        var id = $( this ).data( 'id' );
        ajax( 'kayan_cc_delete_number', { id: id } )
            .done( function( res ) {
                if ( res.success ) { toast( CC.i18n.deleted ); loadNumbers(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    // Export CSV
    $( document ).on( 'click', '#kayan-cc-export-csv', function() {
        window.location.href = CC.ajax_url + '?action=kayan_cc_export_numbers_csv&nonce=' + CC.nonce;
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
        if ( ! confirm( 'سيبدأ فحص شامل للموقع. هذا قد يستغرق دقيقة أو أكثر. هل تريد المتابعة؟' ) ) return;
        ajax( 'kayan_cc_start_scan', {} )
            .done( function( res ) {
                if ( ! res.success ) return;
                $scanProgress.show();
                $scanResults.hide();
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
                $( '#kayan-cc-progress-text' ).text( 'مرحلة: ' + phase );

                if ( res.data.done ) {
                    $scanStatus.text( 'اكتمل الفحص ✓' );
                    $( '#kayan-cc-progress-fill' ).css( 'width', '100%' );
                    setTimeout( function() { $scanProgress.hide(); }, 600 );
                    $scanResults.show();
                    loadScanResults( 1 );
                } else {
                    setTimeout( processScanBatch, 200 );
                }
            } )
            .fail( function() { $scanStatus.text( 'حدث خطأ أثناء الفحص.' ); } );
    }

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
            return '<tr>' +
                '<td class="kayan-cc-monospace">' + escHtml( r.raw_value ) + '</td>' +
                '<td class="kayan-cc-monospace">' + escHtml( r.normalized ) + '</td>' +
                '<td><span class="kayan-cc-badge kayan-cc-badge--' + r.result_type + '">' + resultTypeLabel( r.result_type ) + '</span></td>' +
                '<td><small>' + escHtml( r.source_field ) + '</small></td>' +
                '<td>' + ( r.source_id > 0 ? '<a href="' + escHtml( window.location.origin + '/wp-admin/post.php?post=' + r.source_id + '&action=edit' ) + '" target="_blank">' + r.source_id + '</a>' : '-' ) + '</td>' +
                '</tr>';
        } ).join( '' );

        $( '#kayan-cc-scan-results-table' ).html(
            '<table class="kayan-cc-table">' +
            '<thead><tr><th>القيمة</th><th>الرقم الموحد</th><th>النوع</th><th>المصدر</th><th>رقم المقالة</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table>'
        );
        renderPagination( $( '#kayan-cc-scan-pagination' ), data.paging, loadScanResults );
    }

    $( document ).on( 'change', '#kayan-cc-filter-type', function() { loadScanResults( 1 ); } );

    /* ════════════════════════════════════════════════════════════
       ASSIGNMENT
    ════════════════════════════════════════════════════════════ */
    var $assignScopeType = $( '#kayan-cc-assign-scope-type' );

    $assignScopeType.on( 'change', function() {
        var val = $( this ).val();
        $( '#kayan-cc-scope-post-type' ).toggle( val === 'post_type' );
        $( '#kayan-cc-scope-taxonomy' ).toggle( val === 'taxonomy_term' );
        $( '#kayan-cc-scope-post' ).toggle( val === 'post' );
    } );

    // Load terms when taxonomy changes
    $( document ).on( 'change', '#kayan-cc-assign-taxonomy', function() {
        loadTermsFor( $( this ).val(), $( '#kayan-cc-assign-term' ) );
    } );

    function loadTermsFor( taxonomy, $select ) {
        $select.html( '<option value="">جارٍ التحميل...</option>' );
        $.get( ajaxurl, { action: 'kayan_cc_get_assignments', nonce: CC.nonce, _type: 'terms', taxonomy: taxonomy } )
            .done( function() {
                // Use WP REST API for terms
                $.get( window.location.origin + '/wp-json/wp/v2/' + taxonomy + '?per_page=100&_fields=id,name' )
                    .done( function( terms ) {
                        var opts = '<option value="">-- اختر القسم --</option>';
                        $.each( terms, function( i, t ) {
                            opts += '<option value="' + t.id + '">' + escHtml( t.name ) + '</option>';
                        } );
                        $select.html( opts );
                    } )
                    .fail( function() { $select.html( '<option value="">تعذر التحميل</option>' ); } );
            } );
    }

    // Post search autocomplete
    var postSearchTimeout;
    $( document ).on( 'input', '#kayan-cc-post-search', function() {
        var query = $( this ).val();
        clearTimeout( postSearchTimeout );
        if ( query.length < 2 ) { $( '#kayan-cc-post-search-results' ).empty(); return; }
        postSearchTimeout = setTimeout( function() {
            $.get( window.location.origin + '/wp-json/wp/v2/posts?search=' + encodeURIComponent( query ) + '&per_page=10&_fields=id,title' )
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

    // Submit assign form
    $( document ).on( 'submit', '#kayan-cc-assign-form', function( e ) {
        e.preventDefault();
        var formData = $( this ).serializeArray();
        var data = {};
        $.each( formData, function( i, f ) { data[ f.name ] = f.value; } );
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
        var html = '<table class="kayan-cc-table"><thead><tr><th>الرقم</th><th>النطاق</th><th>التفاصيل</th><th>حذف</th></tr></thead><tbody>';
        $.each( rows, function( i, r ) {
            html += '<tr>' +
                '<td><strong>' + escHtml( r.name ) + '</strong><br><small>' + escHtml( r.phone_number ) + '</small></td>' +
                '<td>' + escHtml( r.scope_type ) + '</td>' +
                '<td>' + escHtml( r.scope_id > 0 ? '#' + r.scope_id : r.scope_taxonomy || 'الموقع' ) + '</td>' +
                '<td><button class="button button-small kayan-cc-remove-assignment" data-id="' + r.id + '">حذف</button></td>' +
                '</tr>';
        } );
        html += '</tbody></table>';
        $( '#kayan-cc-assignments-list' ).html( html );
    }

    $( document ).on( 'click', '.kayan-cc-remove-assignment', function() {
        if ( ! confirm( CC.i18n.confirm_delete ) ) return;
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

        var data = {
            old_number: old_number,
            new_number: new_number,
            scope_type: $( '#kayan-cc-replace-scope' ).val(),
            taxonomy:   $( '#kayan-cc-replace-taxonomy' ).val(),
            term_id:    $( '#kayan-cc-replace-term' ).val(),
            include_children: $( '#kayan-cc-replace-include-children' ).is( ':checked' ) ? 1 : 0,
        };

        $( '#kayan-cc-replace-preview-btn' ).prop( 'disabled', true ).text( 'جارٍ المعاينة...' );

        ajax( 'kayan_cc_replace_preview', data )
            .done( function( res ) {
                $( '#kayan-cc-replace-preview-btn' ).prop( 'disabled', false ).text( '🔍 معاينة قبل التنفيذ' );
                if ( ! res.success ) { toast( res.data.message, 'error' ); return; }
                renderReplacePreview( res.data );
            } )
            .fail( function() {
                $( '#kayan-cc-replace-preview-btn' ).prop( 'disabled', false ).text( '🔍 معاينة قبل التنفيذ' );
                toast( CC.i18n.error, 'error' );
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
                return '<tr>' +
                    '<td><a href="' + escHtml( item.edit_url ) + '" target="_blank">' + escHtml( item.post_title ) + '</a> <small>#' + item.post_id + '</small></td>' +
                    '<td>' + locs + '</td>' +
                    '</tr>';
            } ).join( '' );
            $( '#kayan-cc-replace-preview-table' ).html(
                '<table class="kayan-cc-table"><thead><tr><th>المقالة</th><th>أماكن الظهور</th></tr></thead><tbody>' + rows + '</tbody></table>'
            );
        }

        $( '#kayan-cc-replace-preview-wrap' ).show();
        $( '#kayan-cc-replace-result' ).hide();

        // store data for execute
        $( '#kayan-cc-replace-execute-btn' ).data( 'preview', data );
    }

    $( document ).on( 'click', '#kayan-cc-replace-execute-btn', function() {
        if ( ! confirm( CC.i18n.confirm_replace ) ) return;

        var data = {
            old_number: $( '#kayan-cc-old-number' ).val().trim(),
            new_number: $( '#kayan-cc-new-number' ).val().trim(),
            scope_type: $( '#kayan-cc-replace-scope' ).val(),
            taxonomy:   $( '#kayan-cc-replace-taxonomy' ).val(),
            term_id:    $( '#kayan-cc-replace-term' ).val(),
            include_children: $( '#kayan-cc-replace-include-children' ).is( ':checked' ) ? 1 : 0,
        };

        $( this ).prop( 'disabled', true ).text( 'جارٍ التنفيذ...' );

        ajax( 'kayan_cc_replace_execute', data )
            .done( function( res ) {
                $( '#kayan-cc-replace-execute-btn' ).prop( 'disabled', false ).text( '⚠ تنفيذ الاستبدال' );
                if ( ! res.success ) { toast( res.data.message, 'error' ); return; }
                $( '#kayan-cc-replace-result-text' ).text( ' تم تغيير ' + res.data.posts_changed + ' مقالة. ' );
                $( '#kayan-cc-replace-result' ).show();
                $( '#kayan-cc-replace-preview-wrap' ).hide();
                toast( 'تم تنفيذ الاستبدال بنجاح.' );
            } )
            .fail( function() {
                $( '#kayan-cc-replace-execute-btn' ).prop( 'disabled', false ).text( '⚠ تنفيذ الاستبدال' );
                toast( CC.i18n.error, 'error' );
            } );
    } );

    /* ════════════════════════════════════════════════════════════
       SEO MANAGER
    ════════════════════════════════════════════════════════════ */
    var seoPage = 1;

    function loadSeoPosts() {
        if ( ! $( '#kayan-cc-seo-posts-table' ).length ) return;
        var args = {
            page:      seoPage,
            search:    $( '#kayan-cc-seo-search' ).val(),
            post_type: $( '#kayan-cc-seo-post-type' ).val(),
        };
        ajaxGet( 'kayan_cc_get_seo_posts', args )
            .done( function( res ) {
                if ( ! res.success ) return;
                renderSeoTable( res.data );
            } );
    }

    function renderSeoTable( data ) {
        if ( ! data.posts.length ) {
            $( '#kayan-cc-seo-posts-table' ).html( '<p class="kayan-cc-empty">لا توجد مقالات.</p>' );
            return;
        }
        var rows = data.posts.map( function( p ) {
            var seoDisplay = p.seo_title ? escHtml( p.seo_title ) : '<em style="color:#999">غير محدد</em>';
            return '<tr data-post-id="' + p.post_id + '">' +
                '<td><input type="checkbox" class="kayan-cc-seo-select-post" value="' + p.post_id + '"></td>' +
                '<td><a href="' + escHtml( p.edit_url ) + '" target="_blank">' + escHtml( p.title ) + '</a></td>' +
                '<td><div class="kayan-cc-seo-title-cell">' +
                    '<span class="kayan-cc-seo-title-display">' + seoDisplay + '</span>' +
                    '<input type="text" class="kayan-cc-seo-title-input kayan-cc-input" value="' + escHtml( p.seo_title || '' ) + '" style="display:none">' +
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
            '<table class="kayan-cc-table kayan-cc-table--seo">' +
            '<thead><tr><th><input type="checkbox" id="kayan-cc-seo-select-all"></th>' +
            '<th>عنوان المقالة</th><th>عنوان SEO</th><th>النوع</th><th>الإجراءات</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table>'
        );

        renderPagination( $( '#kayan-cc-seo-pagination' ), { total: data.total, per_page: 20, current_page: seoPage, total_pages: data.total_pages }, function( p ) { seoPage = p; loadSeoPosts(); } );
        updateBulkBtnState();
    }

    // Select all
    $( document ).on( 'change', '#kayan-cc-seo-select-all', function() {
        $( '.kayan-cc-seo-select-post' ).prop( 'checked', $( this ).is( ':checked' ) );
        updateBulkBtnState();
    } );

    $( document ).on( 'change', '.kayan-cc-seo-select-post', updateBulkBtnState );

    function updateBulkBtnState() {
        var any = $( '.kayan-cc-seo-select-post:checked' ).length > 0;
        $( '#kayan-cc-seo-bulk-apply' ).prop( 'disabled', ! any );
    }

    // Inline edit
    $( document ).on( 'click', '.kayan-cc-seo-edit-btn', function() {
        var $row = $( this ).closest( 'tr' );
        $row.find( '.kayan-cc-seo-title-display' ).hide();
        $row.find( '.kayan-cc-seo-title-input' ).show().focus();
        $( this ).hide();
        $row.find( '.kayan-cc-seo-save-btn' ).show();
    } );

    $( document ).on( 'click', '.kayan-cc-seo-save-btn', function() {
        var id    = $( this ).data( 'id' );
        var $row  = $( this ).closest( 'tr' );
        var title = $row.find( '.kayan-cc-seo-title-input' ).val();
        ajax( 'kayan_cc_set_seo_title', { post_id: id, seo_title: title } )
            .done( function( res ) {
                if ( res.success ) { toast( CC.i18n.saved ); loadSeoPosts(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    $( document ).on( 'click', '.kayan-cc-seo-rollback-btn', function() {
        if ( ! confirm( 'هل تريد التراجع إلى العنوان السابق؟' ) ) return;
        var id = $( this ).data( 'id' );
        ajax( 'kayan_cc_rollback_seo_title', { post_id: id } )
            .done( function( res ) {
                if ( res.success ) { toast( 'تم التراجع.', 'success' ); loadSeoPosts(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    $( document ).on( 'click', '.kayan-cc-seo-clear-btn', function() {
        if ( ! confirm( 'هل تريد مسح عنوان SEO المخصص والعودة للعنوان الافتراضي؟' ) ) return;
        var id = $( this ).data( 'id' );
        ajax( 'kayan_cc_clear_seo_title', { post_id: id } )
            .done( function( res ) {
                if ( res.success ) { toast( 'تم المسح.', 'success' ); loadSeoPosts(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    // Bulk apply SEO
    $( document ).on( 'click', '#kayan-cc-seo-bulk-apply', function() {
        var ids      = $( '.kayan-cc-seo-select-post:checked' ).map( function() { return $( this ).val(); } ).get();
        var template = $( '#kayan-cc-seo-bulk-template' ).val();
        if ( ! template ) { toast( 'أدخل قالب العنوان أولاً.', 'warning' ); return; }
        if ( ! ids.length ) { toast( 'اختر مقالة واحدة على الأقل.', 'warning' ); return; }

        ajax( 'kayan_cc_seo_bulk_preview', { post_ids: ids, template: template } )
            .done( function( res ) {
                if ( ! res.success ) { toast( res.data.message, 'error' ); return; }
                renderSeoBulkPreview( res.data, ids, template );
            } );
    } );

    function renderSeoBulkPreview( previews, ids, template ) {
        var rows = previews.map( function( p ) {
            var warnings = p.warnings.length ? '<br><small style="color:#e67e22">⚠ ' + p.warnings.join( ' | ' ) + '</small>' : '';
            return '<tr><td>' + escHtml( p.post_title ) + '</td>' +
                '<td>' + escHtml( p.current_seo || '' ) + '</td>' +
                '<td>' + escHtml( p.new_seo ) + warnings + '</td></tr>';
        } ).join( '' );

        $( '#kayan-cc-seo-bulk-preview-table' ).html(
            '<table class="kayan-cc-table"><thead><tr><th>المقالة</th><th>العنوان الحالي</th><th>العنوان الجديد</th></tr></thead><tbody>' + rows + '</tbody></table>'
        );
        $( '#kayan-cc-seo-bulk-preview-wrap' ).show().data( { ids: ids, template: template } );
        $( '#kayan-cc-seo-bulk-confirm' ).prop( 'disabled', false );
    }

    $( document ).on( 'click', '#kayan-cc-seo-bulk-confirm', function() {
        if ( ! confirm( CC.i18n.confirm_bulk_seo ) ) return;
        var $wrap = $( '#kayan-cc-seo-bulk-preview-wrap' );
        var ids   = $wrap.data( 'ids' );
        var tpl   = $wrap.data( 'template' );
        ajax( 'kayan_cc_seo_bulk_apply', { post_ids: ids, template: tpl } )
            .done( function( res ) {
                if ( res.success ) {
                    toast( 'تم التطبيق: ' + res.data.success + ' مقالة.' );
                    $wrap.hide();
                    loadSeoPosts();
                } else { toast( res.data.message, 'error' ); }
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
            .done( function( res ) {
                if ( ! res.success ) return;
                renderTemplates( res.data );
            } );
    }

    function renderTemplates( templates ) {
        if ( ! templates.length ) {
            $( '#kayan-cc-templates-list' ).html( '<p class="kayan-cc-empty">لا توجد قوالب محفوظة.</p>' );
            return;
        }
        var rows = templates.map( function( t ) {
            return '<tr>' +
                '<td><strong>' + escHtml( t.name ) + '</strong></td>' +
                '<td class="kayan-cc-monospace">' + escHtml( t.template ) + '</td>' +
                '<td>' + escHtml( t.scope_type ) + '</td>' +
                '<td class="kayan-cc-actions">' +
                    '<button class="button button-small kayan-cc-edit-template" data-id="' + t.id + '">تعديل</button> ' +
                    '<button class="button button-small kayan-cc-apply-template" data-id="' + t.id + '" data-name="' + escHtml( t.name ) + '">تطبيق</button> ' +
                    '<button class="button button-small button-link-delete kayan-cc-delete-template" data-id="' + t.id + '">حذف</button>' +
                '</td></tr>';
        } ).join( '' );
        $( '#kayan-cc-templates-list' ).html(
            '<table class="kayan-cc-table"><thead><tr><th>الاسم</th><th>القالب</th><th>النطاق</th><th>الإجراءات</th></tr></thead><tbody>' + rows + '</tbody></table>'
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
        var pos    = $input[ 0 ].selectionStart;
        var val    = $input.val();
        $input.val( val.slice( 0, pos ) + $( this ).data( 'var' ) + val.slice( pos ) );
        $input.focus();
    } );

    $( document ).on( 'change', '#kayan-cc-template-scope-type', function() {
        var val = $( this ).val();
        $( '#kayan-cc-template-scope-post-type' ).toggle( val === 'post_type' );
        $( '#kayan-cc-template-scope-term' ).toggle( val === 'taxonomy_term' );
    } );

    $( document ).on( 'change', '#kayan-cc-template-taxonomy', function() {
        loadTermsFor( $( this ).val(), $( '#kayan-cc-template-term' ) );
    } );

    $( document ).on( 'click', '#kayan-cc-save-template', function() {
        var id   = $( '#kayan-cc-template-id' ).val();
        var data = {};
        $( '#kayan-cc-template-form' ).serializeArray().forEach( function( f ) { data[ f.name ] = f.value; } );
        var action = id ? 'kayan_cc_update_seo_template' : 'kayan_cc_add_seo_template';
        ajax( action, data )
            .done( function( res ) {
                if ( res.success ) { toast( CC.i18n.saved ); $( '#kayan-cc-template-modal' ).hide(); loadTemplates(); }
                else { toast( res.data.message, 'error' ); }
            } );
    } );

    $( document ).on( 'click', '.kayan-cc-delete-template', function() {
        if ( ! confirm( CC.i18n.confirm_delete ) ) return;
        var id = $( this ).data( 'id' );
        ajax( 'kayan_cc_delete_seo_template', { id: id } )
            .done( function( res ) {
                if ( res.success ) { toast( CC.i18n.deleted ); loadTemplates(); }
            } );
    } );

    $( document ).on( 'click', '.kayan-cc-apply-template', function() {
        var id   = $( this ).data( 'id' );
        var name = $( this ).data( 'name' );
        $( '#kayan-cc-apply-template-id' ).val( id );
        $( '#kayan-cc-apply-template-name' ).text( name );
        $( '#kayan-cc-apply-template-preview' ).html( '<p class="kayan-cc-loading">جارٍ التحميل...</p>' );
        $( '#kayan-cc-apply-template-confirm' ).prop( 'disabled', true );
        $( '#kayan-cc-apply-template-modal' ).show();

        // Get all post IDs for this template scope
        ajax( 'kayan_cc_apply_template_preview', { template_id: id, post_ids: [] } )
            .done( function( res ) {
                if ( ! res.success ) { $( '#kayan-cc-apply-template-preview' ).html( '<p>' + res.data.message + '</p>' ); return; }
                renderApplyTemplatePreview( res.data );
                $( '#kayan-cc-apply-template-confirm' ).prop( 'disabled', false ).data( 'post-ids', res.data.map( function( p ) { return p.post_id; } ) );
            } );
    } );

    function renderApplyTemplatePreview( previews ) {
        if ( ! previews.length ) {
            $( '#kayan-cc-apply-template-preview' ).html( '<p class="kayan-cc-empty">لا توجد مقالات في نطاق هذا القالب.</p>' );
            return;
        }
        var rows = previews.map( function( p ) {
            return '<tr><td>' + escHtml( p.post_title ) + '</td><td>' + escHtml( p.new_seo ) + '</td></tr>';
        } ).join( '' );
        $( '#kayan-cc-apply-template-preview' ).html(
            '<table class="kayan-cc-table"><thead><tr><th>المقالة</th><th>العنوان الجديد</th></tr></thead><tbody>' + rows + '</tbody></table>'
        );
    }

    $( document ).on( 'click', '#kayan-cc-apply-template-confirm', function() {
        if ( ! confirm( CC.i18n.confirm_bulk_seo ) ) return;
        var template_id = $( '#kayan-cc-apply-template-id' ).val();
        var post_ids    = $( this ).data( 'post-ids' ) || [];
        ajax( 'kayan_cc_apply_template_execute', { template_id: template_id, post_ids: post_ids } )
            .done( function( res ) {
                if ( res.success ) {
                    toast( 'تم التطبيق: ' + res.data.success + ' مقالة.' );
                    $( '#kayan-cc-apply-template-modal' ).hide();
                } else { toast( res.data.message, 'error' ); }
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
        if ( ! data.rows.length ) {
            $( '#kayan-cc-log-table' ).html( '<p class="kayan-cc-empty">لا توجد سجلات.</p>' );
            return;
        }
        var rows = data.rows.map( function( r ) {
            var rollbackBtn = '';
            if ( r.is_reversible == 1 && ! parseInt( r.reverted_by ) ) {
                rollbackBtn = '<button class="button button-small kayan-cc-rollback-btn" data-id="' + r.id + '" data-type="' + r.action + '">↩ تراجع</button>';
            } else if ( parseInt( r.reverted_by ) ) {
                rollbackBtn = '<span class="kayan-cc-badge kayan-cc-badge--reverted">تم التراجع</span>';
            }
            var beforeBtn = r.data_before ? '<button class="button button-small kayan-cc-view-data" data-content="' + escAttr( r.data_before ) + '">عرض</button>' : '-';
            var afterBtn  = r.data_after  ? '<button class="button button-small kayan-cc-view-data" data-content="' + escAttr( r.data_after ) + '">عرض</button>'  : '-';
            return '<tr>' +
                '<td>' + r.id + '</td>' +
                '<td><span class="kayan-cc-badge kayan-cc-badge--action">' + escHtml( r.action_label ) + '</span></td>' +
                '<td>' + escHtml( r.object_type ) + '</td>' +
                '<td>' + escHtml( r.user_display ) + '</td>' +
                '<td>' + beforeBtn + '</td>' +
                '<td>' + afterBtn + '</td>' +
                '<td><small>' + escHtml( r.created_at ) + '</small></td>' +
                '<td>' + rollbackBtn + '</td>' +
                '</tr>';
        } ).join( '' );

        $( '#kayan-cc-log-table' ).html(
            '<table class="kayan-cc-table"><thead><tr>' +
            '<th>#</th><th>العملية</th><th>النوع</th><th>المستخدم</th><th>قبل</th><th>بعد</th><th>التاريخ</th><th>التراجع</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table>'
        );
        renderPagination( $( '#kayan-cc-log-pagination' ), data.paging, function( p ) { logPage = p; loadActivityLog(); } );
    }

    $( document ).on( 'change', '#kayan-cc-log-action-filter, #kayan-cc-log-type-filter', function() {
        logPage = 1; loadActivityLog();
    } );

    $( document ).on( 'click', '.kayan-cc-rollback-btn', function() {
        var id   = $( this ).data( 'id' );
        var type = $( this ).data( 'type' );
        $( '#kayan-cc-rollback-log-id' ).val( id );
        $( '#kayan-cc-rollback-type' ).val( type );
        $( '#kayan-cc-rollback-modal' ).show();
    } );

    $( document ).on( 'click', '#kayan-cc-rollback-confirm', function() {
        var id   = $( '#kayan-cc-rollback-log-id' ).val();
        var type = $( '#kayan-cc-rollback-type' ).val();
        var action = type === 'replace_number' ? 'kayan_cc_replace_rollback' : null;

        if ( ! action ) {
            toast( 'هذا النوع من التراجع غير مدعوم حتى الآن.', 'warning' );
            $( '#kayan-cc-rollback-modal' ).hide();
            return;
        }

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
        try { pretty = JSON.stringify( JSON.parse( raw ), null, 2 ); } catch (e) { pretty = raw; }
        $( '#kayan-cc-data-content' ).text( pretty );
        $( '#kayan-cc-data-modal' ).show();
    } );

    /* ════════════════════════════════════════════════════════════
       SETTINGS
    ════════════════════════════════════════════════════════════ */
    $( document ).on( 'click', '#kayan-cc-reinstall-db', function() {
        var $btn = $( this );
        $btn.prop( 'disabled', true );
        ajax( 'kayan_cc_reinstall_db', {} )
            .done( function( res ) {
                $btn.prop( 'disabled', false );
                $( '#kayan-cc-reinstall-db-result' ).text( res.success ? 'تم بنجاح.' : res.data.message );
            } )
            .fail( function() { $btn.prop( 'disabled', false ); } );
    } );

    /* ════════════════════════════════════════════════════════════
       INIT
    ════════════════════════════════════════════════════════════ */
    $( function() {
        loadNumbers();
        loadScanResults( 1 );
        loadAssignments();
        loadSeoPosts();
        loadTemplates();
        loadActivityLog();

        // If scan was already complete on page load, show results
        if ( $( '#kayan-cc-scan-results-wrap' ).length && $( '#kayan-cc-scan-results-wrap' ).is( ':visible' ) ) {
            loadScanResults( 1 );
        }
    } );

    /* ── Helpers ──────────────────────────────────────────────── */

    function escHtml( str ) {
        return String( str ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
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

} )( jQuery );
