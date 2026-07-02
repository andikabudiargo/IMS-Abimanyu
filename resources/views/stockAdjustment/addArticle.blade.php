{{-- adjustment/stockAdjustment/addArticle.blade.php --}}

{{-- ══════════════════════════════════════════════════════
     TEMPLATE ROW  (hidden, cloned by JS)
     User isi SALDO AKHIR (balance) — sistem hitung selisih
     dan arahnya (+/-) otomatis dibanding stock before
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

            {{-- New Balance (yang diisi user = saldo akhir seharusnya) --}}
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Saldo Akhir</label>
                    <input type="text"
                        class="form-control text-right tombol-panah balance-input"
                        id="balanceQty" name="balanceQty[]"
                        placeholder="0"
                        maxlength="12"
                        data-type-el-kiri="select"
                        data-nama-el-kiri="articleId"
                        data-type-el-kanan="input"
                        data-nama-el-kanan="notesRow" />
                </div>
            </div>

            {{-- Adjustment (readonly, computed: balance - stockBefore) --}}
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Adjustment</label>
                    <input type="text"
                        class="form-control text-right bg-light adj-qty-output"
                        id="adjQty" name="adjQty[]"
                        value="0" readonly tabindex="-1" />
                </div>
            </div>

            {{-- Notes --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <input type="text" class="form-control tombol-panah"
                        id="notesRow" name="notesRow[]"
                        maxlength="150"
                        data-type-el-kiri="input"
                        data-nama-el-kiri="balanceQty" />
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

    /* adjustment output colour based on computed sign */
    .adj-qty-output.adj-positive { border-color:#28c76f !important; color:#28c76f !important; background:#f0fdf6 !important; }
    .adj-qty-output.adj-negative { border-color:#ea5455 !important; color:#ea5455 !important; background:#fff5f5 !important; }

    /* balance-input highlight kalau beda dari stock before */
    .balance-input.balance-changed { border-color:#ff9f43; }

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
let articleMeta  = {};   // code -> {label, uom, uomMember}
let   cloneCount  = 0;

$(function () {
    const dp = $('#adjDate');
    if (dp.length) {
        dp.flatpickr({
            dateFormat: 'd-m-Y',
            onChange: function () {
                // tanggal adjustment berubah → stock before semua baris harus dihitung ulang
                if (typeof refreshStockOnRows === 'function') refreshStockOnRows();
            }
        });
    }
    // fallback kalau value adjDate berubah tanpa lewat flatpickr (mis. old('adjDate') / set via JS lain)
    $('#adjDate').on('change', function () {
        if (typeof refreshStockOnRows === 'function') refreshStockOnRows();
    });
});

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ARTICLE LOADER
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function isiArticle(dependent) {
    $.ajax({
        url:    "{{ route('dynamic.dependent') }}",
        method: "POST",
        data:   { dependent: dependent },
        success: function (result) {
            dataArticle = result;
            _buildArticleMeta(result);
        },
        error: function () {
            dataArticle = '';
            console.error('Gagal load daftar artikel');
        }
    });
}

function _buildArticleMeta(html) {
    articleMeta = {};
    $('<select>').html(html).find('option').each(function () {
        let $o  = $(this);
        let val = $o.val();
        if (!val) return;
        articleMeta[val] = {
            label:     $o.text(),
            uom:       $o.data('uom'),
            uomMember: $o.data('uom-member')
        };
    });
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   STOCK BEFORE FETCH
   Selalu ikutkan adjDate — stock before dihitung dari saldo
   HISTORIS pada tanggal adjustment (bukan saldo current),
   supaya konsisten dengan validasi backdate di server.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function fetchStockBefore(articleCode, locationCode, rowId) {
    let adjDate = $('#adjDate').val();

    if (!articleCode || !locationCode) { setStockBefore(rowId, 0); return; }

    if (!adjDate) {
        setStockBefore(rowId, 0);
        Swal.fire({ toast:true, position:'top-end', icon:'warning',
            title:'Isi Adjustment Date terlebih dahulu agar Stock Before akurat.',
            timer:2000, showConfirmButton:false });
        return;
    }

    $.ajax({
        url:    "{{ route('stockAdjustment.stockBefore') }}",
        method: "GET",
        data:   { article_code: articleCode, location_code: locationCode, adjDate: adjDate },
        success: function (data) { setStockBefore(rowId, data.stock ?? 0); },
        error:   function ()     { setStockBefore(rowId, 0); }
    });
}
function setStockBefore(rowId, stock) {
    let $sb = $('#stockBefore' + rowId);
    $sb.val(humanizeNumber(stock)).data('raw', stock);

    // Prefill saldo akhir = stock saat ini kalau user belum isi apa-apa
    // supaya default adjustment = 0, tinggal diedit kalau memang beda.
    let $bal = $('#balanceQty' + rowId);
    if ($bal.val() === '' || $bal.data('autofilled')) {
        $bal.val(humanizeNumber(stock)).data('autofilled', true);
    }
    recomputeRow(rowId);
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ROW RECALC — inti dari perubahan: balance -> selisih otomatis
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function recomputeRow(rowId) {
    let stockBefore = parseFloat($('#stockBefore' + rowId).data('raw')) || 0;
    let balance     = parseFloat(String($('#balanceQty' + rowId).val()).replace(/,/g,'')) || 0;
    let diff        = balance - stockBefore; // + berarti nambah stok, - berarti kurangi

    let $adj = $('#adjQty' + rowId);
    let sign = diff > 0 ? '+' : (diff < 0 ? '' : ''); // humanizeNumber biasanya sudah handle minus
    $adj.val(sign + humanizeNumber(diff));
    $adj.removeClass('adj-positive adj-negative');
    if (diff > 0) $adj.addClass('adj-positive');
    if (diff < 0) $adj.addClass('adj-negative');

    $('#balanceQty' + rowId).toggleClass('balance-changed', diff !== 0);
}
function recomputeAllRows() {
    $('#article_row .tanda-baris').each(function () {
        recomputeRow($(this).attr('id').replace('new_row',''));
    });
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   BALANCE INPUT EVENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function bindQtyEvents(n) {
    $('#balanceQty' + n).on('input keyup', function () {
        // saldo akhir tidak boleh minus
        let v = $(this).val().replace(/-/g, '');
        if ($(this).val() !== v) $(this).val(v);
        $(this).data('autofilled', false); // user sudah edit manual
        recomputeRow(n);
        hitungGrandTotal();
    });
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ADD ROW — manual (tunggu dataArticle siap)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function add_new_row() {
    if (!$('#adjDate').val()) {
        Swal.fire({ toast:true, position:'top-end', icon:'warning',
            title:'Isi Adjustment Date terlebih dahulu.', timer:1500, showConfirmButton:false });
        return;
    }
    if (dataArticle === null) {
        Swal.fire({ toast:true, position:'top-end', icon:'info',
            title:'Memuat daftar artikel...', timer:1200, showConfirmButton:false });
        setTimeout(add_new_row, 1200);
        return;
    }
    _doAddRow('', null, null, null, '');
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   ADD ROW — programmatic (import / edit)
   balanceValue = saldo akhir yang seharusnya untuk artikel ini
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function add_new_row_edit(articleCode, balanceValue, uom, uomMember, notes, stockBeforeVal, opts) {
    _doAddRow(articleCode, balanceValue, uom, uomMember, notes, stockBeforeVal, opts);
}

function _doAddRow(articleCode, balanceValue, uom, uomMember, notes, stockBeforeVal, opts) {
    opts = opts || {};
    cloneCount++;
    let n = cloneCount;

    let $template = $($('#new_row').clone().html());
    let $row = $template.filter('.tanda-baris').length
                ? $template.filter('.tanda-baris')
                : $template.find('.tanda-baris').first();

    $row.attr('id', 'new_row' + n);
    _wireIds($row, n);
    $('#article_row').append($row);

    let $sel = $('#articleId' + n);

    if (opts.lazySelect && articleCode) {
        let meta = articleMeta[articleCode] || {};
        $sel.html(
            '<option value="' + articleCode + '" selected>' +
            (meta.label || articleCode) + '</option>'
        );
        let uomOpts = _buildUomOptions(uomMember || meta.uomMember, uom || meta.uom);
        $('#uom' + n).html(uomOpts).val(uom || meta.uom);
        _bindLazySelect2($sel, n);
    } else {
        $sel.html('<option value=""></option>' + dataArticle);
        $sel.select2({ width: '100%', placeholder: 'Pilih artikel...' });
        if (articleCode) {
            $sel.val(articleCode).trigger('change');
            let uomOpts = _buildUomOptions(uomMember, uom);
            $('#uom' + n).html(uomOpts).val(uom).trigger('change');
        }
    }

    $sel.on('change', function () {
        let artCode = $(this).val();
        let locCode = $('#location').val();
        let $opt      = $(this).find(':selected');
        let uomMember = $opt.data('uom-member');
        let uomBase   = $opt.data('uom');
        $('#uom' + n).html(_buildUomOptions(uomMember, uomBase)).val(uomBase).trigger('change');
        $('#balanceQty' + n).val('').data('autofilled', false);
        fetchStockBefore(artCode, locCode, n);
        setTimeout(() => { $('#balanceQty' + n).focus().select(); }, 10);
    });

    let sbRaw = parseFloat(stockBeforeVal) || 0;
    $('#stockBefore' + n).val(humanizeNumber(sbRaw)).data('raw', sbRaw);

    if (balanceValue !== null && balanceValue !== undefined && balanceValue !== '') {
        $('#balanceQty' + n).val(Math.max(0, parseFloat(balanceValue) || 0)).data('autofilled', false);
    }
    if (notes) $('#notesRow' + n).val(notes);

    if (articleCode && $('#location').val() && !opts.skipFetch) {
        fetchStockBefore(articleCode, $('#location').val(), n);
    } else {
        recomputeRow(n);
    }

    bindQtyEvents(n);
    hitungGrandTotal();

    if (!opts.skipFeather) feather.replace();
}

function _bindLazySelect2($sel, n) {
    $sel.on('mousedown.lazyInit focus.lazyInit', function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;
        let current = $(this).val();
        $(this).off('.lazyInit');
        $(this).html('<option value=""></option>' + dataArticle);
        $(this).val(current);
        $(this).select2({ width: '100%', placeholder: 'Pilih artikel...' });
    });
}

/* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   WIRE IDs
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
function _wireIds(row, n) {
    row.find('#articleId').attr('id',   'articleId'   + n);
    row.find('#uom').attr('id',         'uom'         + n);
    row.find('#stockBefore').attr('id', 'stockBefore' + n);
    row.find('#balanceQty').attr('id',  'balanceQty'  + n);
    row.find('#adjQty').attr('id',      'adjQty'      + n);
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
   LOCATION / DATE CHANGE → refresh semua stock before
   (balance yang sudah diisi manual TIDAK ditimpa, hanya yang autofilled)
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
    let manualErrors = [];
    if (!$('#adjDate').val())    manualErrors.push('Adjustment Date harus diisi.');
    if (!$('#adjType').val())    manualErrors.push('Adjustment Type harus dipilih.');
    if (!$('#location').val())   manualErrors.push('Location harus dipilih.');
    if (!$('#periode').val())    manualErrors.push('Periode harus dipilih.');
    if (manualErrors.length > 0) {
        Swal.fire({ title: 'Validation Error', html: manualErrors.join('<br>'), icon: 'warning' });
        return;
    }

    let articles = [];
    let errors   = [];
    let skipped  = 0;

    $('#article_row .tanda-baris').each(function () {
        let rowId   = $(this).attr('id').replace('new_row', '');
        let artCode = $('#articleId' + rowId).val();
        if (!artCode) return;

        let artLabel = $('#articleId' + rowId).find(':selected').text();
        let uom      = $('#uom' + rowId).val();
        let sbRaw    = parseFloat($('#stockBefore' + rowId).data('raw')) || 0;
        let balance  = parseFloat(String($('#balanceQty' + rowId).val()).replace(/,/g,'')) || 0;
        let notes    = $('#notesRow' + rowId).val();

        if (balance < 0) {
            errors.push(`Saldo akhir untuk <b>${artLabel}</b> tidak boleh negatif.`);
            return;
        }

        let diff = balance - sbRaw;
        if (diff === 0) {
            // tidak ada perubahan untuk artikel ini — lewati, bukan error
            skipped++;
            return;
        }

        let direction = diff > 0 ? '+' : '-';
        let qty       = Math.abs(diff);

        articles.push({
            article_code:   artCode,
            uom,
            direction,
            stock_before:   sbRaw,
            qty_adjustment: qty,
            stock_after:    balance,
            notes
        });
    });

    if (articles.length === 0) {
        errors.push(skipped > 0
            ? 'Tidak ada artikel yang saldonya berubah. Ubah saldo akhir minimal 1 artikel.'
            : 'Artikel harus diisi.');
    }
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