{{-- table row untuk di clone--}}
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris" >
        <div class="form-row d-flex align-items-center">
            <div class="col-md-4 col-12">
                <div class="form-group margin-nol">
                    <label for="articleId" class="d-block d-md-none">Article Code</label>
                    <select class="form-control" id="articleId" name="articleId[]" data-dependent="articleId" disabled></select>
                </div>
            </div>
            <div class="col-md-1 col-12">
    <div class="form-group margin-nol">
        <label for="stockFresh" class="d-block d-md-none">RM Fresh</label>
        <div class="input-group">
            <input type="text" class="form-control text-right font-weight-bold"
                id="stockFresh" name="stockFresh[]" readonly tabindex="-1" />
            <div class="input-group-append">
                <a href="javascript:;" class="btn btn-outline-secondary btn-info-rm" 
                   tabindex="-1" title="Cek detail stock RM vs kebutuhan BOM">
                    <i data-feather="info" style="width:14px;height:14px;"></i>
                </a>
            </div>
        </div>
    </div>
</div>
            <div class="col-md-1 col-12">
    <div class="form-group margin-nol">
        <label for="stockRepaint" class="d-block d-md-none">RM Repaint</label>
        <div class="input-group">
            <input type="text" class="form-control text-right font-weight-bold"
                id="stockRepaint" name="stockRepaint[]" readonly tabindex="-1" />
            <div class="input-group-append">
                <a href="javascript:;" class="btn btn-outline-secondary btn-info-repaint"
                   tabindex="-1" title="Cek detail stock Repaint per gudang">
                    <i data-feather="info" style="width:14px;height:14px;"></i>
                </a>
            </div>
        </div>
    </div>
</div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="qty" class="d-block d-md-none">QTY</label>
                    <input type="text" class="form-control numeral-mask-digit text-right"
                        id="qty" name="qty[]" maxlength="10" />
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label for="uom" class="d-block d-md-none">Uom</label>
                    <select class="form-control" id="uom" name="uom[]">
                        <option>PCS</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label for="note" class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control" id="note" name="note[]" maxlength="150">
                </div>
            </div>
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol text-center">
                   <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();sumData();applyArticleAvailability();">
                        <i data-feather="trash-2" class="remove_button feather-24"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- \.table row --}}

<style>
    .mb-03{ margin-bottom: 0.3rem; }
    label.titik-dua::after{ content: ":"; position: absolute; right: 1px; }
    .margin-nol{ margin-bottom: 0.5rem; }

    @media screen and (min-device-width: 1200px) and (max-device-width: 1600px) and (-webkit-min-device-pixel-ratio: 1) {
        .lebar-list-item{ width: 100%; }
        .container-list-item{ max-width: 100%; overflow-x: auto; scrollbar-width: thin; margin-top: 7px; }
    }
    @media only screen and (min-width: 600px) and (max-width: 1200px){
        .lebar-list-item{ width: 200%; }
        .container-list-item{ max-width: 100%; overflow-x: auto; scrollbar-width: thin; margin-top: 7px; }
    }
</style>

<script type="text/javascript">
function formatQty(v){
    let n = parseFloat(String(v).replace(/,/g, ''));
    if (isNaN(n)) return '0';
    return parseFloat(n.toFixed(2)).toString();
}

function formatStock(v){
    let n = parseFloat(String(v).replace(/,/g, ''));
    if (isNaN(n)) return '0';
    return parseFloat(n.toFixed(4)).toString();
}

let cloneCount = 0;
let dataArticle = ""; // diisi via isiArticleBySprayBooth, dipakai select2 tiap baris

function isiArticleBySprayBooth(locationCode) {
    if (!locationCode) {
        dataArticle = "";
        return;
    }
    $.ajax({
        url: "{{ route('production.actualLoading.articleBySprayBooth') }}",
        method: "GET",
        data: { location_code: locationCode },
        success: function (result) {
            let options = '<option value=""></option>';
            $.each(result, function (i, val) {
                options += `<option value="${val.article_code}"
                                data-uom="${val.uom}"
                                data-uom-member="${val.uom_member ?? ''}"
                                data-stock-fresh="${val.stock_rm_fresh ?? 0}"
                                data-stock-repaint="${val.stock_fg_repaint ?? 0}">
                                ${val.article_alternative_code} — ${val.article_desc}
                            </option>`;
            });
            dataArticle = options;
        },
        error: function () {
            dataArticle = "";
            Swal.fire("Warning", "Gagal mengambil daftar article untuk Spray Booth ini", "warning");
        }
    });
}

function changeselect(obj, article) {
    $('#' + obj).attr('disabled', 'disabled');
    $('#' + obj).html(dataArticle);
    $('#' + obj).select2();
    $('#' + obj).val(article).trigger('change');
    $('#' + obj).removeAttr('disabled');
}

function reloadPage(){
    window.location.reload();
}
$("#cmdCancel,#cmdNew").click(function(){
    reloadPage();
});

$(document).on('change', '#article_row select[name="articleId[]"]', function(){
    let $this = $(this);
    let val   = $this.val();

    // ── Cegah artikel yang sama dipilih di baris berbeda ──
    if (val) {
        let duplicate = false;
        $('#article_row select[name="articleId[]"]').each(function(){
            if (this !== $this[0] && $(this).val() === val) duplicate = true;
        });
        if (duplicate) {
            Swal.fire({
                icon: 'warning',
                title: 'Article sudah dipilih',
                text: 'Article ini sudah ada di baris lain. Silakan pilih article yang berbeda, atau ubah qty di baris yang sudah ada.'
            });
            $this.val('').trigger('change.select2');
            applyArticleAvailability();
            sumData();
            return;
        }
    }

    let objArticle      = $('#article_row select[name="articleId[]"]');
    let objStockFresh   = $('#article_row input[name="stockFresh[]"]');
    let objStockRepaint = $('#article_row input[name="stockRepaint[]"]');
    let objUom          = $('#article_row select[name="uom[]"]');

    let idx  = objArticle.index(this);
    let $opt = $(this).find(':selected');

    objStockFresh.eq(idx).val(formatStock($opt.data('stock-fresh') || 0));
    objStockRepaint.eq(idx).val(formatStock($opt.data('stock-repaint') || 0));

    let uom = $opt.data('uom');
    if (uom) {
        objUom.eq(idx).html(`<option>${uom}</option>`).val(uom).trigger('change');
    }

    applyArticleAvailability();
});

// ============================================================
// ISI STOCK FRESH/REPAINT SAAT ARTICLE DIPILIH (delegated)
// ============================================================
$(document).on('change', '#article_row select[name="articleId[]"]', function(){
    let objArticle      = $('#article_row select[name="articleId[]"]');
    let objStockFresh   = $('#article_row input[name="stockFresh[]"]');
    let objStockRepaint = $('#article_row input[name="stockRepaint[]"]');
    let objUom          = $('#article_row select[name="uom[]"]');

    let idx  = objArticle.index(this);
    let $opt = $(this).find(':selected');

    objStockFresh.eq(idx).val(formatStock($opt.data('stock-fresh') || 0));
    objStockRepaint.eq(idx).val(formatStock($opt.data('stock-repaint') || 0));

    let uom = $opt.data('uom');
    if (uom) {
        objUom.eq(idx).html(`<option>${uom}</option>`).val(uom).trigger('change');
    }
});

// ============================================================
// CEGAH DUPLIKAT ARTICLE ANTAR BARIS
// ============================================================

/**
 * Disable opsi article yang sudah dipakai di baris lain, di semua dropdown.
 * Dipanggil tiap kali: baris ditambah, baris dihapus, atau pilihan berubah.
 */
function applyArticleAvailability(){
    let $selects = $('#article_row select[name="articleId[]"]');
    let allUsed  = $selects.map(function(){ return $(this).val(); }).get().filter(v => v);

    $selects.each(function(){
        let $s    = $(this);
        let myVal = $s.val();
        let changedAny = false;

        $s.find('option').each(function(){
            let v = $(this).val();
            if (!v) return;
            let shouldDisable = (v !== myVal && allUsed.includes(v));
            if ($(this).prop('disabled') !== shouldDisable) changedAny = true;
            $(this).prop('disabled', shouldDisable);
        });

        // select2 merender opsi disabled hanya kalau instance-nya di-refresh
        if (changedAny && $s.hasClass('select2-hidden-accessible')) {
            $s.select2('destroy').select2();
        }
    });
}

// ============================================================
// GRAND TOTAL
// ============================================================
function sumData(){
    let objArticle = $('#article_row select[name="articleId[]"]');
    let objQty     = $('#article_row input[name="qty[]"]');
    let qty        = objQty.map(function(){ return $(this).val(); }).get();
    $("#totalRow").val(objArticle.length);
    $("#totalQty").val(humanizeNumber(sumFromArray(qty)));
}

// ============================================================
// ADD ROW
// ============================================================
function appendRow(){
    $("#article_row").append($("#new_row").clone().html());
    cloneCount++;
    let idx = cloneCount;
    $("#article_row").find('#baru').attr('id', 'new_row' + idx);
    $("#new_row"+idx).find('#articleId').attr('id', 'articleId'+idx);
    $("#new_row"+idx).find('#stockFresh').attr('id', 'stockFresh'+idx);
    $("#new_row"+idx).find('#stockRepaint').attr('id', 'stockRepaint'+idx);
    $("#new_row"+idx).find('#qty').attr('id', 'qty'+idx);
    $("#new_row"+idx).find('#uom').attr('id', 'uom'+idx);
    $("#new_row"+idx).find('#note').attr('id', 'note'+idx);
    return idx;
}

function add_new_row() {
    let idx = appendRow();
    changeselect('articleId' + idx, '');
    $('#remove_button').tooltip();
    sumData();
    mask_thousand_digit(numberOfDecimalDigit);
    applyArticleAvailability();
}

// ============================================================
// TOMBOL INFO: CEK DETAIL STOCK RM VS KEBUTUHAN BOM
// ============================================================
$(document).on('click', '#article_row .btn-info-rm', function(){
    let $row         = $(this).closest('.tanda-baris');
    let $selectArt   = $row.find('select[name="articleId[]"]');
    let articleCode  = $selectArt.val();
    let locationCode = $('#sprayBooth').val();

    if (!articleCode) {
        Swal.fire("Info", "Silakan pilih Article terlebih dahulu.", "info");
        return;
    }
    if (!locationCode) {
        Swal.fire("Info", "Silakan pilih Spray Booth terlebih dahulu.", "info");
        return;
    }

    $.ajax({
        url: "{{ route('production.actualLoading.rmDetailBySprayBooth') }}",
        method: "GET",
        data: { location_code: locationCode, article_code: articleCode },
        success: function (res) {
            renderRmDetailModal(res, $selectArt.find(':selected').text(), $('#sprayBooth option:selected').text());
        },
        error: function () {
            Swal.fire("Warning", "Gagal mengambil detail stock RM.", "warning");
        }
    });
});

function renderRmDetailModal(res, articleLabel, boothLabel){
    if (!res.rows || res.rows.length === 0) {
        Swal.fire("Info", "Tidak ada data BOM/RM untuk article ini.", "info");
        return;
    }

    let bottleneckCount = res.rows.filter(r => r.is_bottleneck).length;

    let rowsHtml = '';
    res.rows.forEach(function(r){
        let rowClass = r.is_bottleneck ? 'style="background-color:#fdecea;"' : '';

        let statusBadge = r.is_bottleneck
            ? `<span class="badge badge-danger" style="font-size:11px;">
                 <i data-feather="alert-triangle" style="width:11px;height:11px;vertical-align:-1px;"></i>
                 Bottleneck
               </span>`
            : `<span class="badge badge-success" style="font-size:11px;">
                 <i data-feather="check" style="width:11px;height:11px;vertical-align:-1px;"></i>
                 Cukup
               </span>`;

        let selisih = r.is_bottleneck
            ? `<span class="text-danger font-weight-bold">
                 − ${formatStock(r.deficit_fg)} FG
               </span>
               <div class="text-muted" style="font-size:11px;">butuh +${formatStock(r.deficit_qty)} ${r.uom}</div>`
            : `<span class="text-success font-weight-bold">
                 sisa ${formatStock(r.surplus_qty)} ${r.uom}
               </span>`;

        // progress bar kecil: stock vs kebutuhan utk max_fg keseluruhan
        let pct = r.qty_per_fg > 0
            ? Math.min(100, (r.stock_qty / (r.qty_per_fg * (bottleneckCount ? Math.max(res.max_fg,1) : res.max_fg || 1))) * 100)
            : 0;

        rowsHtml += `
            <tr ${rowClass}>
                <td class="text-left align-middle">
                    <div class="font-weight-bold">${r.article_alternative_code}</div>
                    <div class="text-muted" style="font-size:11.5px;">${r.article_desc}</div>
                </td>
                <td class="text-right align-middle">${formatStock(r.qty_per_fg)} ${r.uom}</td>
                <td class="text-right align-middle">${formatStock(r.stock_qty)} ${r.uom}</td>
                <td class="text-right align-middle font-weight-bold">${formatStock(r.max_fg)}</td>
                <td class="text-center align-middle">${statusBadge}</td>
                <td class="text-left align-middle">${selisih}</td>
            </tr>`;
    });

    let summaryAlertClass = res.max_fg > 0 ? 'alert-success' : 'alert-danger';
    let summaryIcon       = res.max_fg > 0 ? 'check-circle' : 'x-circle';
    let summaryText       = res.max_fg > 0
        ? `Bisa dibuat <b>${formatStock(res.max_fg)} FG</b> dengan stock RM saat ini`
        : `<b>Belum bisa</b> membuat 1 FG pun dengan stock RM saat ini`;

    let html = `
        <div class="text-left">

            <div class="d-flex justify-content-between align-items-center mb-2 pb-2" style="border-bottom:1px solid #e9ecef;">
                <div>
                    <div class="text-muted" style="font-size:12px;">ARTICLE FG</div>
                    <div class="font-weight-bold" style="font-size:15px;">${articleLabel}</div>
                </div>
                <div class="text-right">
                    <div class="text-muted" style="font-size:12px;">SPRAY BOOTH</div>
                    <div class="font-weight-bold" style="font-size:15px;">${boothLabel || '-'}</div>
                </div>
            </div>

            <div class="alert ${summaryAlertClass} d-flex align-items-center py-2 px-3 mb-2" style="font-size:14px;">
                <i data-feather="${summaryIcon}" class="mr-1" style="width:18px;height:18px;flex-shrink:0;"></i>
                <div>${summaryText}${bottleneckCount > 0 ? ` &mdash; dibatasi oleh <b>${bottleneckCount}</b> RM di bawah` : ''}</div>
            </div>

            <table class="table table-sm table-hover mb-2" style="font-size:13px;">
                <thead style="background-color:#f8f9fa;">
                    <tr>
                        <th class="text-left" style="width:26%;">Raw Material</th>
                        <th class="text-right" style="width:14%;">Butuh / FG</th>
                        <th class="text-right" style="width:14%;">Stock Booth</th>
                        <th class="text-right" style="width:10%;">Max FG</th>
                        <th class="text-center" style="width:14%;">Status</th>
                        <th class="text-left" style="width:22%;">Selisih</th>
                    </tr>
                </thead>
                <tbody>${rowsHtml}</tbody>
            </table>

            <div class="text-muted" style="font-size:11.5px;">
                <i data-feather="info" style="width:12px;height:12px;vertical-align:-1px;"></i>
                Baris merah = RM yang jadi <b>batasan utama</b> (paling sedikit stoknya relatif terhadap kebutuhan BOM).
            </div>
        </div>
    `;

    Swal.fire({
        title: false,
        html: html,
        width: 900,
        showConfirmButton: true,
        confirmButtonText: 'Tutup',
        didOpen: () => {
            if (typeof feather !== 'undefined') feather.replace();
        }
    });
}

// ============================================================
// TOMBOL INFO REPAINT: BREAKDOWN STOCK FG PER GUDANG WIP
// ============================================================
$(document).on('click', '#article_row .btn-info-repaint', function(){
    let $row        = $(this).closest('.tanda-baris');
    let $selectArt  = $row.find('select[name="articleId[]"]');
    let articleCode = $selectArt.val();

    if (!articleCode) {
        Swal.fire("Info", "Silakan pilih Article terlebih dahulu.", "info");
        return;
    }

    $.ajax({
        url: "{{ route('production.actualLoading.repaintDetailByArticle') }}",
        method: "GET",
        data: { article_code: articleCode },
        success: function (res) {
            renderRepaintDetailModal(res, $selectArt.find(':selected').text());
        },
        error: function () {
            Swal.fire("Warning", "Gagal mengambil detail stock Repaint.", "warning");
        }
    });
});

function renderRepaintDetailModal(res, articleLabel){
    if (!res.rows || res.rows.length === 0) {
        Swal.fire("Info", "Tidak ada stock RM Repaint di lokasi WIP untuk article ini.", "info");
        return;
    }

    let rowsHtml = '';
    res.rows.forEach(function(r){
        rowsHtml += `
            <tr>
                <td class="text-left align-middle">
                    <div class="font-weight-bold">${r.location_name ?? r.location_code}</div>
                    <div class="text-muted" style="font-size:11.5px;">${r.location_code}</div>
                </td>
                <td class="text-right align-middle font-weight-bold">
                    ${formatStock(r.qty)} ${r.uom ?? ''}
                </td>
            </tr>`;
    });

    let html = `
        <div class="text-left">
            <div class="mb-2 pb-2" style="border-bottom:1px solid #e9ecef;">
                <div class="text-muted" style="font-size:12px;">ARTICLE FG</div>
                <div class="font-weight-bold" style="font-size:15px;">${articleLabel}</div>
            </div>

            <div class="alert alert-info d-flex align-items-center py-2 px-3 mb-2" style="font-size:14px;">
                <i data-feather="layers" class="mr-1" style="width:18px;height:18px;flex-shrink:0;"></i>
                <div>
                    Total stock Repaint: <b>${formatStock(res.total)}</b>
                    tersebar di <b>${res.rows.length}</b> gudang
                </div>
            </div>

            <table class="table table-sm table-hover mb-2" style="font-size:13px;">
                <thead style="background-color:#f8f9fa;">
                    <tr>
                        <th class="text-left" style="width:70%;">Gudang (WIP)</th>
                        <th class="text-right" style="width:30%;">Qty</th>
                    </tr>
                </thead>
                <tbody>${rowsHtml}</tbody>
            </table>

            <div class="text-muted" style="font-size:11.5px;">
                <i data-feather="info" style="width:12px;height:12px;vertical-align:-1px;"></i>
                Stock FG jadi (siap di-repaint) yang ada di gudang ber-type WIP,
                dijumlahkan dari semua lokasi terlepas dari Spray Booth yang dipilih.
            </div>
        </div>
    `;

    Swal.fire({
        title: false,
        html: html,
        width: 600,
        showConfirmButton: true,
        confirmButtonText: 'Tutup',
        didOpen: () => {
            if (typeof feather !== 'undefined') feather.replace();
        }
    });
}

// ============================================================
// SAVE
// ============================================================
$('#cmdSave').on('click', function(){
    let $rows = $('#article_row .tanda-baris');
    if ($rows.length === 0) {
        Swal.fire("Info", "Belum ada artikel yang ditambahkan.", "info");
        return;
    }

    let sprayBooth  = $('#sprayBooth').val();
    let loadingDate = $('#loadingDate').val();
    let headerNote  = $('#note').val();

    if (!sprayBooth)  { Swal.fire("Info", "Spray Booth wajib dipilih.", "info"); return; }
    if (!loadingDate) { Swal.fire("Info", "Tanggal wajib diisi.", "info"); return; }

    let articles = [];
    let invalid  = false;

    $rows.each(function(){
        let $r           = $(this);
        let articleCode  = $r.find('select[name="articleId[]"]').val();
        let uom          = $r.find('select[name="uom[]"]').val();
        let qty          = parseFloat(String($r.find('input[name="qty[]"]').val()          || '0').replace(/,/g,'')) || 0;
        let stockFresh   = parseFloat(String($r.find('input[name="stockFresh[]"]').val()   || '0').replace(/,/g,'')) || 0;
        let stockRepaint = parseFloat(String($r.find('input[name="stockRepaint[]"]').val() || '0').replace(/,/g,'')) || 0;
        let note         = $r.find('input[name="note[]"]').val();

        if (!articleCode) { invalid = true; return; }
        if (qty <= 0)     { invalid = true; return; }

       articles.push({
    article_code : articleCode,
    uom          : uom,
    qty          : qty,            // total; backend yg pecah fresh->repaint
    stock_fresh  : stockFresh,     // info snapshot (max FG dari RM fresh)
    stock_repaint: stockRepaint,   // info snapshot (FG di WIP)
    note         : note
});
    });

    if (invalid) {
        Swal.fire("Info", "Ada baris tanpa Article atau QTY ≤ 0. Lengkapi dulu.", "info");
        return;
    }
    if (articles.length === 0) {
        Swal.fire("Info", "Belum ada baris valid untuk disimpan.", "info");
        return;
    }

    let $btn      = $(this);
let originalHtml = $btn.html();

$btn.prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>Saving...');

$.ajax({
    url: "{{ route('production.actualLoading.store') }}",
    method: "POST",
    data: {
        articles   : JSON.stringify(articles),
        loadingDate: loadingDate,
        sprayBooth : sprayBooth,
        note       : headerNote
    },
    success: function(res){
        if (res.status == 1) {
            Swal.fire({ icon:'success', title: res.title, text: res.message })
                .then(() => reloadPage());   // halaman reload, tombol ga perlu dibalikin
        } else {
            let msg = Array.isArray(res.message) ? res.message.flat().join('<br>') : res.message;
            Swal.fire({ icon:'error', title: res.title || 'Error', html: msg });
        }
    },
    error: function(xhr){
        Swal.fire("Error", "Gagal menyimpan. " + (xhr.responseJSON?.message || xhr.statusText || ''), "error");
    },
    complete: function(){
        // balikin tombol HANYA kalau ga sukses (kalau sukses halaman udah reload)
        $btn.prop('disabled', false).html(originalHtml);
    }
});
});

$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});
</script>