{{-- adjustment/stockAdjustment/addArticle.blade.php --}}

{{-- ══════════════════════════════════════════════════════
     TEMPLATE ROW  (hidden, cloned by JS)
     Direction ditentukan di header — qty selalu positif
══════════════════════════════════════════════════════════ --}}
<div id="new_row" class="d-none">
    <div id="baru" class="tanda-baris mb-50">
        <div class="form-row d-flex align-items-center">

            {{-- Article select --}}
            <div class="col-md-3 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Article</label>
                    <select class="form-control" id="articleId" name="articleId[]"></select>
                </div>
            </div>

            {{-- UOM --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">UOM</label>
                    <select class="form-control" id="uom" name="uom[]"></select>
                </div>
            </div>

            {{-- Stock Before (readonly) --}}
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Stock Before</label>
                    <input type="text"
                        class="form-control text-right bg-light"
                        id="stockBefore" name="stockBefore[]"
                        value="0" readonly tabindex="-1" />
                </div>
            </div>

            {{-- Qty Adjustment (always positive) --}}
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Qty Adj</label>
                    <input type="text"
                        class="form-control text-right tombol-panah adj-qty-input"
                        id="qtyAdj" name="qtyAdj[]"
                        placeholder="0"
                        maxlength="12"
                        data-type-el-kiri="select"
                        data-nama-el-kiri="articleId"
                        data-type-el-kanan="input"
                        data-nama-el-kanan="notesRow" />
                </div>
            </div>

            {{-- Stock After (readonly) --}}
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Stock After</label>
                    <input type="text"
                        class="form-control text-right bg-light"
                        id="stockAfter" name="stockAfter[]"
                        value="0" readonly tabindex="-1" />
                </div>
            </div>

            {{-- Notes --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Notes</label>
                    <input type="text" class="form-control tombol-panah"
                        id="notesRow" name="notesRow[]"
                        maxlength="150"
                        data-type-el-kiri="input"
                        data-nama-el-kiri="qtyAdj" />
                </div>
            </div>

            {{-- Delete --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol text-center">
                    <a style="cursor:pointer"
                        onclick="$(this).parents('.tanda-baris').remove(); hitungGrandTotal();">
                        <i data-feather="trash-2" class="feather-24 text-danger"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
{{-- /.new_row --}}


<style>
    .margin-nol  { margin-bottom: 0.5rem; }
    .mb-50       { margin-bottom: 0.5rem; }
    .mb-03       { margin-bottom: 0.3rem; }

    label.titik-dua::after { content:":"; position:absolute; right:1px; }

    /* qty input colour based on active direction */
    body.dir-plus  .adj-qty-input:not([value=""]):not([value="0"]) { border-color:#28c76f; color:#28c76f; }
    body.dir-minus .adj-qty-input:not([value=""]):not([value="0"]) { border-color:#ea5455; color:#ea5455; }

    /* direction toggle solid active state */
    #directionToggle .btn-outline-success.active { background:#28c76f !important; color:#fff !important; border-color:#28c76f !important; }
    #directionToggle .btn-outline-danger.active  { background:#ea5455 !important; color:#fff !important; border-color:#ea5455 !important; }

    /* stock-after goes red when negative */
    .stock-after-negative { border-color:#ea5455 !important; background:#fff5f5 !important; color:#ea5455 !important; }

    @media screen and (min-width:1200px) and (max-width:1600px) {
        .lebar-list-item     { width:100%; }
        .container-list-item { max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
    @media only screen and (min-width:600px) and (max-width:1200px) {
        .lebar-list-item     { width:200%; }
        .container-list-item { max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
</style>


<script type="text/javascript">

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   GLOBALS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
const currentDate = "{{ $currentDateValue }}";
let   dataArticle = null;   // null = belum selesai load; "" / [] = kosong
let   cloneCount  = 0;

$(function () {
    // Flatpickr
    const dp = $('#adjDate');
    if (dp.length) dp.flatpickr({ dateFormat: 'd-m-Y' });

    // Direction toggle
    $('input[name="direction"]').on('change', function () {
        applyDirectionClass();
        recomputeAllRows();
    });
    applyDirectionClass();
});

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   DIRECTION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function getDirection() {
    return $('input[name="direction"]:checked').val() || '+';
}
function applyDirectionClass() {
    let dir = getDirection();
    $('body').toggleClass('dir-plus',  dir === '+')
             .toggleClass('dir-minus', dir === '-');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ARTICLE LOADER
   ─ Menggunakan key yang sama dengan transferOut ('trArticle')
     agar dynamic.dependent mengembalikan option HTML yang
     sudah berisi data-uom, data-uom-member, dsb.
   ─ Sesuaikan key jika di DynamicDependentController
     sudah ada entry khusus untuk adjustment.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function isiArticle(dependent) {
    $.ajax({
        url:    "{{ route('dynamic.dependent') }}",
        method: "POST",
        data:   { dependent: dependent },
        success: function (result) {
            dataArticle = result;   // HTML string of <option> tags
        },
        error: function () {
            dataArticle = '';       // mark as done even on error
            console.error('Gagal load daftar artikel');
        }
    });
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STOCK BEFORE FETCH
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function fetchStockBefore(articleCode, locationCode, rowId) {
    if (!articleCode || !locationCode) { setStockBefore(rowId, 0); return; }
    $.ajax({
        url:    "{{ route('stockAdjustment.stockBefore') }}",
        method: "GET",
        data:   { article_code: articleCode, location_code: locationCode },
        success: function (data) { setStockBefore(rowId, data.stock ?? 0); },
        error:   function ()     { setStockBefore(rowId, 0); }
    });
}
function setStockBefore(rowId, stock) {
    $('#stockBefore' + rowId).val(humanizeNumber(stock)).data('raw', stock);
    recomputeRow(rowId);
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ROW RECALC
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function recomputeRow(rowId) {
    let dir         = getDirection();
    let stockBefore = parseFloat($('#stockBefore' + rowId).data('raw')) || 0;
    let qty         = Math.abs(parseFloat(String($('#qtyAdj' + rowId).val()).replace(/,/g,'')) || 0);
    let stockAfter  = dir === '+' ? stockBefore + qty : stockBefore - qty;

    let saEl = $('#stockAfter' + rowId);
    saEl.val(humanizeNumber(stockAfter));
    saEl.toggleClass('stock-after-negative', stockAfter < 0);
}
function recomputeAllRows() {
    $('#article_row .tanda-baris').each(function () {
        recomputeRow($(this).attr('id').replace('new_row',''));
    });
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   QTY EVENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function bindQtyEvents(n) {
    $('#qtyAdj' + n).on('input keyup', function () {
        // strip minus — direction dari toggle header
        let v = $(this).val().replace(/-/g, '');
        if ($(this).val() !== v) $(this).val(v);
        recomputeRow(n);
        hitungGrandTotal();
    });
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ADD ROW — manual (tunggu dataArticle siap)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function add_new_row() {
    if (dataArticle === null) {
        // Artikel belum selesai di-load — tunggu lalu retry
        Swal.fire({ toast:true, position:'top-end', icon:'info',
            title:'Memuat daftar artikel...', timer:1200, showConfirmButton:false });
        setTimeout(add_new_row, 1200);
        return;
    }
    _doAddRow('', null, null, null, '');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ADD ROW — programmatic (import / edit)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function add_new_row_edit(articleCode, qtyAdjValue, uom, uomMember, notes, stockBeforeVal, direction) {
    // Sinkronkan direction toggle ke arah dari data (hanya baris pertama)
    if (direction && cloneCount === 0) {
        let $radio = $('input[name="direction"][value="' + direction + '"]');
        $radio.prop('checked', true);
        $radio.closest('label').addClass('active').siblings('label').removeClass('active');
        applyDirectionClass();
    }
    _doAddRow(articleCode, qtyAdjValue, uom, uomMember, notes, stockBeforeVal);
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CORE ADD ROW (private)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function _doAddRow(articleCode, qtyAdjValue, uom, uomMember, notes, stockBeforeVal) {
    cloneCount++;
    let n = cloneCount;

    // Clone inner HTML dari template, wrap dengan div sementara,
    // ambil .tanda-baris dari dalamnya, lalu append ke article_row
    let $template = $($('#new_row').clone().html());
    let $row = $template.filter('.tanda-baris').length
                ? $template.filter('.tanda-baris')
                : $template.find('.tanda-baris').first();

    $row.attr('id', 'new_row' + n);
    _wireIds($row, n);
    $('#article_row').append($row);

    // ── Populate article select ──────────────────────────────────────
    // Inject HTML options, init select2, lalu set value
    let $sel = $('#articleId' + n);
    $sel.html('<option value=""></option>' + dataArticle);
    $sel.select2({ width: '100%', placeholder: 'Pilih artikel...' });

    if (articleCode) {
        $sel.val(articleCode).trigger('change');
        // UOM override dari parameter (import/edit)
        let uomOpts = _buildUomOptions(uomMember, uom);
        $('#uom' + n).html(uomOpts).val(uom).trigger('change');
    }

    // ── Bind article change event ────────────────────────────────────
    $sel.on('change', function () {
        let artCode   = $(this).val();
        let locCode   = $('#location').val();
        let $opt      = $(this).find(':selected');
        let uomMember = $opt.data('uom-member');
        let uomBase   = $opt.data('uom');
        $('#uom' + n).html(_buildUomOptions(uomMember, uomBase)).val(uomBase).trigger('change');
        fetchStockBefore(artCode, locCode, n);
        setTimeout(() => { $('#qtyAdj' + n).focus().select(); }, 10);
    });

    // ── Stock before & qty ───────────────────────────────────────────
    let sbRaw = parseFloat(stockBeforeVal) || 0;
    $('#stockBefore' + n).val(humanizeNumber(sbRaw)).data('raw', sbRaw);
    if (qtyAdjValue) $('#qtyAdj' + n).val(Math.abs(parseFloat(qtyAdjValue) || 0));
    if (notes)       $('#notesRow' + n).val(notes);

    // Fetch fresh stock jika sudah ada article & location
    if (articleCode && $('#location').val()) {
        fetchStockBefore(articleCode, $('#location').val(), n);
    } else {
        recomputeRow(n);
    }

    bindQtyEvents(n);
    hitungGrandTotal();
    feather.replace();
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   WIRE IDs (replace generic template IDs with indexed ones)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function _wireIds(row, n) {
    row.find('#articleId').attr('id',   'articleId'   + n);
    row.find('#uom').attr('id',         'uom'         + n);
    row.find('#stockBefore').attr('id', 'stockBefore' + n);
    row.find('#qtyAdj').attr('id',      'qtyAdj'      + n);
    row.find('#stockAfter').attr('id',  'stockAfter'  + n);
    row.find('#notesRow').attr('id',    'notesRow'    + n);
}

function _buildUomOptions(uomMember, uomBase) {
    let opts = '';
    if (uomMember) {
        uomMember.toString().split(',').forEach(u => { opts += `<option value="${u.trim()}">${u.trim()}</option>`; });
    } else if (uomBase) {
        opts = `<option value="${uomBase}">${uomBase}</option>`;
    }
    return opts;
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   LOCATION CHANGE → refresh semua stock before
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function refreshStockOnRows() {
    let locCode = $('#location').val();
    $('#article_row .tanda-baris').each(function () {
        let rowId   = $(this).attr('id').replace('new_row', '');
        let artCode = $('#articleId' + rowId).val();
        fetchStockBefore(artCode, locCode, rowId);
    });
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ROW COUNT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function hitungGrandTotal() {
    $('#totalRow').val($('#article_row .tanda-baris').length);
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   SAVE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
simpanData = (oEdit) => {
    // ── Validasi manual field select2 (tidak ter-detect checkValidity) ──
    let manualErrors = [];
    if (!$('#adjDate').val())    manualErrors.push('Adjustment Date harus diisi.');
    if (!$('#adjType').val())    manualErrors.push('Adjustment Type harus dipilih.');
    if (!$('#location').val())   manualErrors.push('Location harus dipilih.');
    if (!$('#periode').val())    manualErrors.push('Periode harus dipilih.');
    if (manualErrors.length > 0) {
        Swal.fire({ title: 'Validation Error', html: manualErrors.join('<br>'), icon: 'warning' });
        return;
    }

    let dir      = getDirection();
    let articles = [];
    let errors   = [];

    $('#article_row .tanda-baris').each(function () {
        let rowId    = $(this).attr('id').replace('new_row', '');
        let artCode  = $('#articleId' + rowId).val();
        if (!artCode) return;

        let artLabel   = $('#articleId' + rowId).find(':selected').text();
        let uom        = $('#uom'         + rowId).val();
        let sbRaw      = parseFloat($('#stockBefore' + rowId).data('raw')) || 0;
        let qty        = Math.abs(parseFloat(String($('#qtyAdj' + rowId).val()).replace(/,/g,'')) || 0);
        let stockAfter = dir === '+' ? sbRaw + qty : sbRaw - qty;
        let notes      = $('#notesRow' + rowId).val();

        if (qty === 0) {
            errors.push(`Qty Adjustment untuk <b>${artLabel}</b> tidak boleh 0.`);
            return;
        }
        if (stockAfter < 0) {
            errors.push(`Stock after untuk <b>${artLabel}</b> akan negatif (${stockAfter}). Kurangi qty.`);
            return;
        }

        articles.push({ article_code: artCode, uom, direction: dir,
                        stock_before: sbRaw, qty_adjustment: qty,
                        stock_after: stockAfter, notes });
    });

    if (articles.length === 0) errors.push('Artikel harus diisi.');
    if (errors.length > 0) {
        Swal.fire({ title:'Validation Error', html: errors.join('<br>'), icon:'warning' });
        return;
    }

    let url = oEdit ? "{{ route('stockAdjustment.update') }}"
                    : "{{ route('stockAdjustment.store')  }}";

    $.ajax({
        type: "POST", url,
        data: {
            adjCode:     $('#adjCode').val(),
            adjDate:     $('#adjDate').val(),
            adjType:     $('#adjType').val(),
            periode:     $('#periode').val(),
            location:    $('#location').val(),
            direction:   dir,
            description: $('#description').val(),
            articles:    JSON.stringify(articles),
        },
        dataType: "json",
        success: function (data) {
            if (data.status == 0) {
                data.message.forEach(m => show_msg(data.title, m, data.alert));
            } else {
                show_msg(data.title, data.message, data.alert);
                $('#adjCode').val(data.adjCode);
                $('#oEdit').val(data.oEdit);
                if (!oEdit) window.location.href = "{{ route('stockAdjustment.create') }}";
            }
        },
        error: function (err) { console.error(err); }
    });
};

$("input[type='text']").on('click', function () { $(this).select(); });

</script>