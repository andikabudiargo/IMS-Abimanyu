{{-- table row untuk di clone--}}
<div id="new_row" name="new_row[]" class="d-none">
    <div id="baru" class="tanda-baris">
        <div class="form-row d-flex align-items-center">

            {{-- ARTICLE --}}
            <div class="col-md-4 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Article Code</label>
                    <select class="form-control" data-placeholder="-- Choose Article --"
                        id="articleId" name="articleId[]" data-dependent="articleId" disabled></select>
                </div>
            </div>

            {{-- MAX FG (readonly, info) --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Max FG</label>
                    <div class="input-group">
                        <input type="text" class="form-control text-right font-weight-bold"
                            id="maxFg" name="maxFg[]" readonly tabindex="-1" />
                        <div class="input-group-append">
                            <a href="javascript:;" class="btn btn-outline-secondary btn-info-fg"
                               tabindex="-1"
                               title="Cek detail: kebutuhan BOM vs stock RM, dan stock FG di gudang WIP">
                                <i data-feather="info" style="width:14px;height:14px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- QTY FRESH (input manual) --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">QTY Fresh</label>
                    <input type="text" class="form-control numeral-mask-digit text-right"
                        id="qtyFresh" name="qtyFresh[]" maxlength="10" placeholder="0" />
                </div>
            </div>

            {{-- QTY REPAINT (input manual) --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">QTY Repaint</label>
                    <input type="text" class="form-control numeral-mask-digit text-right"
                        id="qtyRepaint" name="qtyRepaint[]" maxlength="10" placeholder="0" />
                </div>
            </div>

            {{-- TOTAL QTY (readonly, otomatis) --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Total</label>
                    <input type="text" class="form-control text-right font-weight-bold bg-light"
                        id="qtyTotal" name="qty[]" readonly tabindex="-1"
                        title="Total = Fresh + Repaint" />
                </div>
            </div>

            {{-- UOM --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Uom</label>
                    <select class="form-control" id="uom" name="uom[]">
                        <option>PCS</option>
                    </select>
                </div>
            </div>

            {{-- NOTE --}}
            <div class="col-md-2 col-12">
                <div class="form-group margin-nol">
                    <label class="d-block d-md-none">Note</label>
                    <input type="text" class="form-control" id="note" name="note[]" maxlength="150">
                </div>
            </div>

            {{-- DELETE --}}
            <div class="col-md-1 col-12">
                <div class="form-group margin-nol text-center">
                    <a onmouseover="this.style.cursor='pointer'"
                       onclick="$(this).parents('.tanda-baris').remove(); sumData(); lockChosenArticles();">
                        <i data-feather="trash-2" class="remove_button feather-24"></i>
                    </a>
                </div>
            </div>
        </div>

        {{-- Warning row --}}
        <div class="form-row">
            <div class="col-12 fg-warning text-danger d-none" style="font-size:11.5px;">
                <i data-feather="alert-triangle" style="width:12px;height:12px;vertical-align:-1px;"></i>
                <span class="fg-warning-text"></span>
            </div>
        </div>
    </div>
</div>
{{-- \.table row --}}

<style>
    .mb-03{ margin-bottom: 0.3rem; }
    label.titik-dua::after{ content: ":"; position: absolute; right: 1px; }
    .margin-nol{ margin-bottom: 0.5rem; }

   .qty-error,
input.qty-error,
.form-control.qty-error{
    background-color:#f8d7da !important;
    border:1px solid #dc3545 !important;
    color:#842029 !important;
    font-weight:600 !important;
    box-shadow:0 0 0 0.15rem rgba(220,53,69,.25) !important;
}

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
    let n = parseFloat(String(v).replace(/,/g,''));
    if (isNaN(n)) return '0';
    return parseFloat(n.toFixed(2)).toString();
}
function formatStock(v){
    let n = parseFloat(String(v).replace(/,/g,''));
    if (isNaN(n)) return '0';
    return parseFloat(n.toFixed(4)).toString();
}

let cloneCount  = 0;
let dataArticle = "";

// ============================================================
// LOAD ARTICLE LIST BY SPRAY BOOTH
// ============================================================
function isiArticleBySprayBooth(locationCode){
    if (!locationCode){ dataArticle = ""; return; }
    $.ajax({
        url   : "{{ route('production.actualLoading.articleBySprayBooth') }}",
        method: "GET",
        data  : { location_code: locationCode },
        success: function(result){
            let options = '<option value=""></option>';
            $.each(result, function(i, val){
                options += `<option value="${val.article_code}"
                                data-uom="${val.uom}"
                                data-uom-member="${val.uom_member ?? ''}"
                                data-max-fg="${val.max_fg ?? 0}"
                                data-stock-fresh="${val.stock_rm_fresh ?? 0}"
                                data-stock-repaint="${val.stock_fg_repaint ?? 0}">
                                ${val.article_alternative_code} — ${val.article_desc}
                            </option>`;
            });
            dataArticle = options;
        },
        error: function(){
            dataArticle = "";
            Swal.fire("Warning","Gagal mengambil daftar article untuk Spray Booth ini","warning");
        }
    });
}

function changeselect(obj, article){
    $('#'+obj).attr('disabled','disabled');
    $('#'+obj).html(dataArticle);
    $('#'+obj).select2();
    $('#'+obj).val(article).trigger('change');
    $('#'+obj).removeAttr('disabled');
}

function reloadPage(){ window.location.reload(); }
$("#cmdCancel,#cmdNew").click(function(){ reloadPage(); });

// ============================================================
// KUNCI ARTICLE YANG SUDAH DIPILIH DI BARIS LAIN
// ============================================================
function lockChosenArticles(){
    // kumpulkan semua article_code yang sudah dipilih
    let chosen = [];
    $('#article_row select[name="articleId[]"]').each(function(){
        let v = $(this).val();
        if (v) chosen.push(v);
    });

    // untuk tiap select, disable option yang dipakai select LAIN
    $('#article_row select[name="articleId[]"]').each(function(){
        let $sel    = $(this);
        let selfVal = $sel.val();

        $sel.find('option').each(function(){
            let optVal = $(this).val();
            if (!optVal) return; // skip placeholder kosong

            // disable kalau dipakai baris lain (bukan baris ini sendiri)
            let dipakaiLain = chosen.includes(optVal) && optVal !== selfVal;
            $(this).prop('disabled', dipakaiLain);
        });

        // refresh tampilan select2 supaya perubahan disabled kelihatan
        if ($sel.hasClass('select2-hidden-accessible')){
            $sel.trigger('change.select2');
        }
    });
}

// ============================================================
// ISI MAX FG SAAT ARTICLE DIPILIH
// ============================================================
$(document).on('change', '#article_row select[name="articleId[]"]', function(){
    let $row = $(this).closest('.tanda-baris');
    let $opt = $(this).find('option:selected');

    // DEBUG
    console.log('ARTICLE CHANGE →', {
        selectedVal : $(this).val(),
        optText     : $opt.text(),
        dataMaxFg   : $opt.attr('data-max-fg'),
        dataMaxFgJq : $opt.data('max-fg')
    });

    let maxFg = parseFloat($opt.attr('data-max-fg') || 0) || 0;
    $row.find('input[name="maxFg[]"]').val(formatStock(maxFg));

    $row.find('input[name="qtyFresh[]"]').val('');
    $row.find('input[name="qtyRepaint[]"]').val('');
    $row.find('input[name="qty[]"]').val('');

    let uom = $opt.attr('data-uom');
    if (uom){
        $row.find('select[name="uom[]"]').html(`<option>${uom}</option>`).val(uom).trigger('change');
    }
    checkQtyRow($row);
    lockChosenArticles();
});

// ============================================================
// HITUNG TOTAL OTOMATIS SAAT FRESH / REPAINT BERUBAH
// ============================================================
// ============================================================
// HITUNG TOTAL OTOMATIS SAAT FRESH / REPAINT BERUBAH
// ============================================================
function recalcTotal($row){
    let fresh   = parseFloat(String($row.find('input[name="qtyFresh[]"]').val()   || '0').replace(/,/g,'')) || 0;
    let repaint = parseFloat(String($row.find('input[name="qtyRepaint[]"]').val() || '0').replace(/,/g,'')) || 0;
    let total   = fresh + repaint;
    $row.find('input[name="qty[]"]').val(total > 0 ? formatQty(total) : '');
}

function onQtyChange($row){
    // setTimeout supaya Cleave/Inputmask selesai dulu format value-nya
    setTimeout(function(){
        recalcTotal($row);
        checkQtyRow($row);
        sumData();
    }, 50);
}

// SATU listener saja, pakai keyup juga supaya cover semua cara input
$(document).on('input keyup change', '#article_row input[name="qtyFresh[]"], #article_row input[name="qtyRepaint[]"]', function(){
    let $row = $(this).closest('.tanda-baris');
    onQtyChange($row);
});

// ============================================================
// VALIDASI: total (fresh + repaint) tidak melebihi maxFg
// ============================================================
// ============================================================
function checkQtyRow($row){
    // apakah article sudah dipilih di baris ini?
    let articleChosen = !!$row.find('select[name="articleId[]"]').val();

    let maxFg   = parseFloat(String($row.find('input[name="maxFg[]"]').val()     || '0').replace(/,/g,'')) || 0;
    let fresh   = parseFloat(String($row.find('input[name="qtyFresh[]"]').val()  || '0').replace(/,/g,'')) || 0;
    let repaint = parseFloat(String($row.find('input[name="qtyRepaint[]"]').val()|| '0').replace(/,/g,'')) || 0;
    let total   = fresh + repaint;

    // Validasi hanya kalau article sudah dipilih.
    // maxFg = 0 berarti article TIDAK BISA diproduksi → input apapun = over.
    let freshOver   = articleChosen && fresh   > maxFg;
    let repaintOver = articleChosen && repaint > maxFg;
    let totalOver   = articleChosen && total   > maxFg;

    $row.find('input[name="qtyFresh[]"]').toggleClass('qty-error', freshOver || totalOver);
    $row.find('input[name="qtyRepaint[]"]').toggleClass('qty-error', repaintOver || totalOver);
    $row.find('input[name="qty[]"]').toggleClass('qty-error', totalOver);

    let messages = [];
    if (maxFg <= 0 && articleChosen && total > 0){
        // kasus khusus: kapasitas 0
        messages.push(`Article ini <b>tidak bisa diproduksi</b> (Max FG = 0). Stok RM di booth maupun FG di WIP tidak tersedia.`);
    } else {
        if (freshOver)   messages.push(`QTY Fresh (${formatStock(fresh)}) sudah melebihi Max FG (${formatStock(maxFg)})`);
        if (repaintOver) messages.push(`QTY Repaint (${formatStock(repaint)}) sudah melebihi Max FG (${formatStock(maxFg)})`);
        if (!freshOver && !repaintOver && totalOver)
            messages.push(`Total QTY Fresh + Repaint (${formatStock(total)}) melebihi Max FG (${formatStock(maxFg)})`);
    }

    let $warn = $row.find('.fg-warning');
    if (messages.length > 0){
        $warn.removeClass('d-none').find('.fg-warning-text').html(messages.join('<br>'));
        if (typeof feather !== 'undefined') feather.replace();
    } else {
        $warn.addClass('d-none');
    }

    return freshOver || repaintOver || totalOver;
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
    let idx    = cloneCount;
    let $last  = $("#article_row .tanda-baris").last();

    $last.attr('id', 'new_row' + idx);
    $last.find('#articleId').attr('id',  'articleId'  + idx);
    $last.find('#maxFg').attr('id',      'maxFg'      + idx);
    $last.find('#qtyFresh').attr('id',   'qtyFresh'   + idx);
    $last.find('#qtyRepaint').attr('id', 'qtyRepaint' + idx);
    $last.find('#qtyTotal').attr('id',   'qtyTotal'   + idx);
    $last.find('#uom').attr('id',        'uom'        + idx);
    $last.find('#note').attr('id',       'note'       + idx);
    return idx;
}

function add_new_row(){
    let idx = appendRow();
    changeselect('articleId' + idx, '');
    $('#remove_button').tooltip();
    sumData();
    mask_thousand_digit(numberOfDecimalDigit);
    lockChosenArticles();   // ← tambah ini
}

// ============================================================
// TOMBOL INFO: DETAIL RM (BOM) + STOCK FG DI GUDANG WIP
// ============================================================
$(document).on('click', '#article_row .btn-info-fg', function(){
    let $row        = $(this).closest('.tanda-baris');
    let $selectArt  = $row.find('select[name="articleId[]"]');
    let articleCode = $selectArt.val();
    let locationCode= $('#sprayBooth').val();

    if (!articleCode) { Swal.fire("Info","Silakan pilih Article terlebih dahulu.","info"); return; }
    if (!locationCode){ Swal.fire("Info","Silakan pilih Spray Booth terlebih dahulu.","info"); return; }

    $.ajax({
        url   : "{{ route('production.actualLoading.rmDetailBySprayBooth') }}",
        method: "GET",
        data  : { location_code: locationCode, article_code: articleCode },
        success: function(res){
            renderFgDetailModal(
                res,
                $selectArt.find(':selected').text(),
                $('#sprayBooth option:selected').text()
            );
        },
        error: function(){ Swal.fire("Warning","Gagal mengambil detail stock.","warning"); }
    });
});

function renderFgDetailModal(res, articleLabel, boothLabel){
    let rows     = res.rows      || [];
    let wipRows  = res.wip_rows  || [];
    let fresh    = parseFloat(res.max_fg_fresh) || 0;
    let wipTotal = parseFloat(res.wip_total)    || 0;
    let total    = parseFloat(res.max_fg_total) || 0;

    if (rows.length === 0 && wipRows.length === 0){
        Swal.fire("Info","Tidak ada data BOM/RM maupun stock WIP untuk article ini.","info");
        return;
    }

    let rmHtml = '';
    if (rows.length === 0){
        rmHtml = `<tr><td colspan="6" class="text-center text-muted py-2">
                    Tidak ada komponen RM (BOM aktif) untuk article ini.
                  </td></tr>`;
    } else {
        rows.forEach(function(r){
            let rowStyle = r.is_critical ? 'style="background-color:#fdecea;"' : '';
            let statusBadge;
            if (r.is_critical){
                statusBadge = `<span class="badge badge-danger" style="font-size:11px;">
                                 <i data-feather="alert-triangle" style="width:11px;height:11px;vertical-align:-1px;"></i>
                                 ${fresh > 0 ? 'Penghambat' : 'Stock kurang'}
                               </span>`;
            } else if (r.is_limiting){
                statusBadge = `<span class="badge badge-info" style="font-size:11px;">
                                 <i data-feather="minimize-2" style="width:11px;height:11px;vertical-align:-1px;"></i>
                                 Penentu batas
                               </span>`;
            } else {
                statusBadge = `<span class="badge badge-success" style="font-size:11px;">
                                 <i data-feather="check" style="width:11px;height:11px;vertical-align:-1px;"></i>
                                 Cukup
                               </span>`;
            }

            let selisih;
            if (r.deficit_qty !== null && r.deficit_qty !== undefined){
                selisih = `<span class="text-danger font-weight-bold">− ${formatStock(r.deficit_fg)} FG</span>
                           <div class="text-muted" style="font-size:11px;">butuh +${formatStock(r.deficit_qty)} ${r.uom}</div>`;
            } else {
                selisih = `<span class="text-success font-weight-bold">sisa ${formatStock(r.surplus_qty)} ${r.uom}</span>`;
            }

            rmHtml += `
                <tr ${rowStyle}>
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
    }

    let wipHtml = '';
    if (wipRows.length === 0){
        wipHtml = `<tr><td colspan="2" class="text-center text-muted py-2">
                     Tidak ada stock FG di gudang WIP.
                   </td></tr>`;
    } else {
        wipRows.forEach(function(r){
            wipHtml += `
                <tr>
                    <td class="text-left align-middle">
                        <div class="font-weight-bold">${r.location_name ?? r.location_code}</div>
                        <div class="text-muted" style="font-size:11.5px;">${r.location_code}</div>
                    </td>
                    <td class="text-right align-middle font-weight-bold">${formatStock(r.qty)} ${r.uom ?? ''}</td>
                </tr>`;
        });
    }

    let summaryClass = total > 0 ? 'alert-success' : 'alert-danger';
    let summaryIcon  = total > 0 ? 'check-circle'  : 'x-circle';
    let summaryText  = total > 0
        ? `Bisa dibuat <b>${formatStock(total)} FG</b> &mdash;
           <b>${formatStock(fresh)}</b> dari RM fresh di booth +
           <b>${formatStock(wipTotal)}</b> dari FG di gudang WIP (repaint)`
        : `<b>Belum bisa</b> membuat 1 FG pun: RM fresh maupun stock WIP tidak mencukupi`;

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
            <div class="alert ${summaryClass} d-flex align-items-center py-2 px-3 mb-2" style="font-size:14px;">
                <i data-feather="${summaryIcon}" class="mr-1" style="width:18px;height:18px;flex-shrink:0;"></i>
                <div>${summaryText}</div>
            </div>
            <div class="font-weight-bold text-muted mb-1" style="font-size:12px;">
                A. KEBUTUHAN RM (FRESH) &mdash; kapasitas ${formatStock(fresh)} FG
            </div>
            <table class="table table-sm table-hover mb-2" style="font-size:13px;">
                <thead style="background-color:#f8f9fa;">
                    <tr>
                        <th class="text-left"  style="width:26%;">Raw Material</th>
                        <th class="text-right" style="width:14%;">Qty BOM</th>
                        <th class="text-right" style="width:14%;">Stock Booth</th>
                        <th class="text-right" style="width:10%;">Max FG</th>
                        <th class="text-center"style="width:14%;">Status</th>
                        <th class="text-left"  style="width:22%;">Selisih</th>
                    </tr>
                </thead>
                <tbody>${rmHtml}</tbody>
            </table>
            <div class="font-weight-bold text-muted mb-1 mt-2" style="font-size:12px;">
                B. STOCK FG DI GUDANG WIP (REPAINT) &mdash; total ${formatStock(wipTotal)}
            </div>
            <table class="table table-sm table-hover mb-2" style="font-size:13px;">
                <thead style="background-color:#f8f9fa;">
                    <tr>
                        <th class="text-left"  style="width:70%;">Gudang (WIP)</th>
                        <th class="text-right" style="width:30%;">Qty</th>
                    </tr>
                </thead>
                <tbody>${wipHtml}</tbody>
            </table>
            <div class="text-muted" style="font-size:11.5px;">
                <i data-feather="info" style="width:12px;height:12px;vertical-align:-1px;"></i>
                <b>Max FG = kapasitas RM fresh + stock FG di WIP.</b>
                Saat disimpan, RM fresh dipakai dulu, sisanya diambil dari gudang WIP (repaint).
                Baris merah = RM yang menahan kapasitas fresh dibanding RM lain.
            </div>
        </div>`;

    Swal.fire({
        title: false,
        html : html,
        width: 900,
        showConfirmButton: true,
        confirmButtonText: 'Tutup',
        didOpen: () => { if (typeof feather !== 'undefined') feather.replace(); }
    });
}

// ============================================================
// SAVE
// ============================================================
$('#cmdSave').on('click', function(){
    let $rows = $('#article_row .tanda-baris');
    if ($rows.length === 0){
        Swal.fire("Info","Belum ada artikel yang ditambahkan.","info"); return;
    }

    let sprayBooth  = $('#sprayBooth').val();
    let loadingDate = $('#loadingDate').val();
    let headerNote  = $('#note').val();

    if (!sprayBooth) { Swal.fire("Info","Spray Booth wajib dipilih.","info"); return; }
    if (!loadingDate){ Swal.fire("Info","Tanggal wajib diisi.","info"); return; }

    let articles = [];
    let invalid  = false;
    let anyOver  = false;

    $rows.each(function(){
        let $r = $(this);
        if (checkQtyRow($r)) anyOver = true;

        let articleCode = $r.find('select[name="articleId[]"]').val();
        let uom         = $r.find('select[name="uom[]"]').val();
        let qtyFresh    = parseFloat(String($r.find('input[name="qtyFresh[]"]').val()   || '0').replace(/,/g,'')) || 0;
        let qtyRepaint  = parseFloat(String($r.find('input[name="qtyRepaint[]"]').val() || '0').replace(/,/g,'')) || 0;
        let qtyTotal    = qtyFresh + qtyRepaint;
        let note        = $r.find('input[name="note[]"]').val();

        if (!articleCode) { invalid = true; return; }
        if (qtyTotal <= 0){ invalid = true; return; }

        articles.push({
            article_code: articleCode,
            uom         : uom,
            qty_fresh   : qtyFresh,
            qty_repaint : qtyRepaint,
            qty         : qtyTotal,
            note        : note
        });
    });

    // invalid (tanpa article / qty 0) tetap DIBLOKIR — ini bukan sekadar over
    if (invalid){
        Swal.fire("Info","Ada baris tanpa Article atau Total QTY ≤ 0. Lengkapi dulu.","info"); return;
    }
    if (articles.length === 0){
        Swal.fire("Info","Belum ada baris valid untuk disimpan.","info"); return;
    }

    // ── Over hanya jadi PERINGATAN, bukan blokir ──
    if (anyOver){
        Swal.fire({
            icon: 'warning',
            title: 'QTY melebihi Max FG',
            html: 'Ada baris dengan QTY melebihi kapasitas (kolom merah).<br>'
                + 'Stok RM/WIP bisa jadi <b>minus</b> setelah disimpan.<br><br>'
                + 'Tetap lanjutkan simpan?',
            showCancelButton: true,
            confirmButtonText: 'Ya, tetap simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d33'
        }).then(function(result){
            if (result.isConfirmed){
                doSaveActualLoading(articles, sprayBooth, loadingDate, headerNote);
            }
        });
        return;
    }

    // tidak ada yang over → langsung simpan
    doSaveActualLoading(articles, sprayBooth, loadingDate, headerNote);
});

// ── fungsi simpan dipisah supaya bisa dipanggil dari 2 jalur (over & normal) ──
function doSaveActualLoading(articles, sprayBooth, loadingDate, headerNote){
    let $btn         = $('#cmdSave');
    let originalHtml = $btn.html();
    $btn.prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span>Saving...');

    $.ajax({
        url   : "{{ route('production.actualLoading.store') }}",
        method: "POST",
        data  : {
            articles   : JSON.stringify(articles),
            loadingDate: loadingDate,
            sprayBooth : sprayBooth,
            reference  : $('#reference').val(),
            note       : headerNote
        },
        success: function(res){
            if (res.status == 1){
                Swal.fire({ icon:'success', title:res.title, text:res.message })
                    .then(() => reloadPage());
            } else {
                let msg = Array.isArray(res.message) ? res.message.flat().join('<br>') : res.message;
                Swal.fire({ icon:'error', title:res.title||'Error', html:msg });
            }
        },
        error: function(xhr){
            Swal.fire("Error","Gagal menyimpan. "+(xhr.responseJSON?.message||xhr.statusText||''),"error");
        },
        complete: function(){ $btn.prop('disabled',false).html(originalHtml); }
    });
}

$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
});
</script>