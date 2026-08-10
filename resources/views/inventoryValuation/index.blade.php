@extends('layouts.app')
@section('title', 'Inventory Valuation Report')
@section('content')
@include('layouts.breadcrumb')

{{-- Loading Overlay --}}
<div id="loadingOverlay">
    <div class="text-center">
        <div class="spinner-border mb-2" style="width:2.5rem;height:2.5rem;color:#3b4a5a;" role="status"></div>
        <div id="loadingText" class="font-weight-bold text-secondary">Memuat data...</div>
    </div>
</div>

<section id="inventory-valuation-index">

    {{-- ── Filter Card ── --}}
    <div class="card filter-card mb-1">
        <div class="card-body py-1">
            <div class="row align-items-end">
                <div class="col-md-3 col-12 mb-1">
                    <label class="form-label mb-25 font-weight-bold text-muted" style="font-size:0.78rem;">
                        <i data-feather="calendar" class="mr-25" style="width:14px;height:14px;"></i>
                        PERIODE
                    </label>
                    <input type="text" id="rangeDate" name="rangeDate" class="form-control flatpickr-range"
                           placeholder="DD-MM-YYYY to DD-MM-YYYY" autocomplete="off">
                </div>

                <div class="col-md-4 col-12 mb-1">
                    <label class="form-label mb-25 font-weight-bold text-muted" style="font-size:0.78rem;">
                        <i data-feather="map-pin" class="mr-25" style="width:14px;height:14px;"></i>
                        LOCATION
                    </label>
                    <select id="selLocations" name="selLocations" class="select2 form-control" multiple>
                        @foreach($locations as $code => $label)
                            <option value="{{ $code }}" selected>{{ $code }} - {{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5 col-12 mb-1 d-flex align-items-end flex-wrap" style="gap:.5rem;">
                    <button id="btnLoad" type="button" class="btn btn-dark btn-sm">
                        <i data-feather="search" class="mr-25" style="width:14px;height:14px;"></i>
                        Show
                    </button>
                    <button id="btnExportXlsx" type="button" class="btn btn-outline-secondary btn-sm" disabled>
                        <i data-feather="download" class="mr-25" style="width:14px;height:14px;"></i>
                        Export Excel
                    </button>
                    <button id="btnExpandAll" type="button" class="btn btn-outline-secondary btn-sm" disabled>
                        <i data-feather="maximize-2" class="mr-25" style="width:14px;height:14px;"></i>
                        Expand
                    </button>
                    <button id="btnCollapseAll" type="button" class="btn btn-outline-secondary btn-sm" disabled>
                        <i data-feather="minimize-2" class="mr-25" style="width:14px;height:14px;"></i>
                        Collapse
                    </button>
                </div>
            </div>

            <div id="infoBar" class="d-none">
                <hr class="my-50">
                <div class="row">
                    <div class="col-12 text-muted" style="font-size:0.82rem;">
                        <span id="badgePeriode" class="mr-2"></span>
                        <span id="badgeLokasi" class="mr-2"></span>
                        <span id="badgeJumlahArtikel"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── View Switcher ── --}}
    <div class="mb-1 d-none" id="viewSwitcher">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-outline-dark view-tab active" data-view="detail">
                Detail
            </button>
            <button type="button" class="btn btn-sm btn-outline-dark view-tab" data-view="summary">
                Summary
            </button>
        </div>
    </div>

    {{-- ── Detail Table ── --}}
    <div class="card" id="cardDetail">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tblValuation" class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" style="min-width:220px;">Article</th>
                            <th rowspan="2" style="min-width:55px;">UOM</th>
                            <th rowspan="2" style="min-width:80px;">Date</th>
                            <th rowspan="2" style="min-width:130px;">No. Ref</th>
                            <th rowspan="2" style="min-width:110px;">Type</th>
                            <th colspan="3" class="text-center grp-saldo-awal">OPENING</th>
                            <th colspan="3" class="text-center grp-masuk">INCOMING (DEBIT)</th>
                            <th colspan="3" class="text-center grp-keluar">OUTGOING (CREDIT)</th>
                            <th colspan="3" class="text-center grp-saldo-akhir">ON HAND (BALANCE)</th>
                        </tr>
                        <tr>
                            <th class="col-qty grp-saldo-awal">Qty</th>
                            <th class="col-money grp-saldo-awal">Price</th>
                            <th class="col-money grp-saldo-awal">Value</th>

                            <th class="col-qty grp-masuk">Qty</th>
                            <th class="col-money grp-masuk">Price</th>
                            <th class="col-money grp-masuk">Value</th>

                            <th class="col-qty grp-keluar">Qty</th>
                            <th class="col-money grp-keluar">Price</th>
                            <th class="col-money grp-keluar">Value</th>

                            <th class="col-qty grp-saldo-akhir">Qty</th>
                            <th class="col-money grp-saldo-akhir">Price</th>
                            <th class="col-money grp-saldo-akhir">Value</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyValuation">
                        <tr class="no-data-row">
                            <td colspan="17">
                                <i data-feather="info" class="mr-1"></i>
                                Pilih filter dan klik <strong>Tampilkan</strong> untuk memuat data.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot id="tfootValuation" class="d-none">
                        <tr class="tfoot-total">
                            <td colspan="5" class="text-right">GRAND TOTAL</td>
                            <td class="col-qty"   id="ftSaldoAwalQty"></td>
                            <td class="col-money"></td>
                            <td class="col-money" id="ftSaldoAwalValue"></td>
                            <td class="col-qty"   id="ftTotalQtyIn"></td>
                            <td class="col-money"></td>
                            <td class="col-money" id="ftTotalValueIn"></td>
                            <td class="col-qty"   id="ftTotalQtyOut"></td>
                            <td class="col-money"></td>
                            <td class="col-money" id="ftTotalValueOut"></td>
                            <td class="col-qty"   id="ftSaldoAkhirQty"></td>
                            <td class="col-money"></td>
                            <td class="col-money" id="ftSaldoAkhirValue"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Summary per Lokasi Table ── --}}
    <div class="card d-none" id="cardSummary">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tblSummary" class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" style="min-width:170px;">Location</th>
                            <th rowspan="2" class="text-center" style="min-width:90px;">Articles</th>
                            <th colspan="2" class="text-center grp-saldo-awal">OPENING</th>
                            <th colspan="2" class="text-center grp-masuk">INCOMING (DEBIT)</th>
                            <th colspan="2" class="text-center grp-keluar">OUTGOING (CREDIT)</th>
                            <th colspan="2" class="text-center grp-saldo-akhir">ON HAND (BALANCE)</th>
                        </tr>
                        <tr>
                            <th class="col-qty grp-saldo-awal">Qty</th>
                            <th class="col-money grp-saldo-awal">Value</th>
                            <th class="col-qty grp-masuk">Qty</th>
                            <th class="col-money grp-masuk">Value</th>
                            <th class="col-qty grp-keluar">Qty</th>
                            <th class="col-money grp-keluar">Value</th>
                            <th class="col-qty grp-saldo-akhir">Qty</th>
                            <th class="col-money grp-saldo-akhir">Value</th>
                        </tr>
                    </thead>
                    <tbody id="tbodySummary">
                        <tr class="no-data-row">
                            <td colspan="10">
                                <i data-feather="info" class="mr-1"></i>
                                Pilih filter dan klik <strong>Tampilkan</strong> untuk memuat data.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot id="tfootSummary" class="d-none">
                        <tr class="tfoot-total">
                            <td class="text-right">GRAND TOTAL</td>
                            <td class="text-center" id="fsJumlahArtikel"></td>
                            <td class="col-qty"   id="fsSaldoAwalQty"></td>
                            <td class="col-money" id="fsSaldoAwalValue"></td>
                            <td class="col-qty"   id="fsQtyIn"></td>
                            <td class="col-money" id="fsValueIn"></td>
                            <td class="col-qty"   id="fsQtyOut"></td>
                            <td class="col-money" id="fsValueOut"></td>
                            <td class="col-qty"   id="fsAkhirQty"></td>
                            <td class="col-money" id="fsAkhirValue"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</section>
@endsection

{{-- ════════════════════════════════════════════════
     TIDAK ADA @section('vendor-script') di sini.
     flatpickr & select2 SUDAH di-load global oleh
     layouts.app. Satu-satunya library yang belum ada
     secara global adalah SheetJS (xlsx), jadi itu saja
     yang dimuat di section 'scripts'.
════════════════════════════════════════════════ --}}

@section('styles')
<style>
    /*
     * ── Palet enterprise / netral ──
     * Tidak ada warna-warni: hanya abu-abu, putih, dan satu warna
     * aksen (navy) untuk header. Grup kolom dibedakan lewat garis
     * pemisah tegas + label header, bukan lewat background warna.
     */
    :root{
        --iv-ink:      #1f2937;
        --iv-muted:    #6b7280;
        --iv-border:   #dfe3e8;
        --iv-header:   #2b3648;
        --iv-header2:  #3d4a60;
        --iv-row-alt:  #f7f8fa;
        --iv-accent:   #2b3648;
    }

    .filter-card { border-left: 3px solid var(--iv-accent); }

    .view-tab.active { background-color: var(--iv-accent); color:#fff; border-color: var(--iv-accent); }

    #tblValuation, #tblSummary { font-size: 0.82rem; color: var(--iv-ink); }

    #tblValuation th, #tblSummary th {
        background-color: var(--iv-header);
        color: #fff;
        white-space: nowrap;
        font-weight: 600;
        vertical-align: middle;
        border-color: #4a5670 !important;
    }
    /* Header baris kedua (Qty/Harga/Nilai) sedikit lebih terang supaya grup terlihat */
    #tblValuation thead tr:nth-child(2) th,
    #tblSummary thead tr:nth-child(2) th { background-color: var(--iv-header2); font-weight: 500; }

    #tblValuation .col-money, #tblSummary .col-money { text-align: right; white-space: nowrap; }
    #tblValuation .col-qty,   #tblSummary .col-qty   { text-align: right; }

    /* Pemisah tegas antar grup Saldo Awal | Masuk | Keluar | Saldo Akhir */
    .grp-masuk        { border-left: 2px solid #9aa5b1 !important; }
    .grp-keluar        { border-left: 2px solid #9aa5b1 !important; }
    .grp-saldo-akhir  { border-left: 2px solid #9aa5b1 !important; }

    /* ── Baris lokasi (grup header per lokasi) ── */
    .row-lokasi-header td {
        background-color: var(--iv-ink) !important;
        color: #fff;
        font-weight: 700;
        letter-spacing: .02em;
        border-top: none !important;
    }

    /* ── Baris artikel (klik untuk expand) ── */
    .row-artikel-header {
        background-color: var(--iv-row-alt) !important;
        font-weight: 600;
        cursor: pointer;
    }
    .row-artikel-header td { border-top: 1px solid var(--iv-border) !important; }
    .row-artikel-header:hover { background-color: #eef1f4 !important; }

    /* ── Baris detail (saldo awal / in / out) — netral, dibedakan lewat teks kecil ── */
    .row-detail td:first-child { padding-left: 2.25rem; color: var(--iv-muted); }
    .row-detail { font-size: 0.8rem; }
    .row-detail-label { color: var(--iv-muted); font-style: italic; }

    /* ── Baris rekonsiliasi per artikel: satu-satunya baris yang menampilkan
         ke-4 grup sekaligus (Saldo Awal → Masuk → Keluar → Saldo Akhir),
         supaya jelas terlihat cara perhitungannya tanpa ambigu. ── */
    .row-reconcile { background-color: #eef2f6 !important; font-weight: 600; font-size: 0.82rem; }
    .row-reconcile td { border-top: 1px dashed #9aa5b1 !important; border-bottom: 2px solid var(--iv-border) !important; }

    .toggle-icon { transition: transform 0.15s; display: inline-block; color: var(--iv-muted); }
    .collapsed .toggle-icon { transform: rotate(-90deg); }

    /* Badge tipe movement — netral, tidak warna-warni. Hanya beda border kiri tipis untuk IN/OUT. */
    .badge-mv {
        background-color: #fff;
        color: var(--iv-ink);
        border: 1px solid var(--iv-border);
        font-weight: 500;
        font-size: 0.7rem;
    }
    .badge-mv.mv-in  { border-left: 3px solid #4b5563; }
    .badge-mv.mv-out { border-left: 3px solid #94a3b8; }

    #loadingOverlay {
        display: none;
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255,255,255,0.75); z-index: 9999;
        align-items: center; justify-content: center;
    }
    #loadingOverlay.show { display: flex; }

    .tfoot-total td { font-weight: 700; background-color: var(--iv-header) !important; color: #fff; }

    .no-data-row td { text-align: center; color: var(--iv-muted); padding: 2rem !important; }
</style>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script type="text/javascript">
// ============================================================
// STATE
// ============================================================
let reportData    = null;  // { "005": [artikel...], "006": [...] }
let summaryData   = null;  // [ { location, label, ... }, ... ]
let reportMeta    = null;  // from_date, to_date, loc_labels, locations
let expandedRows  = {};    // { artikel_code: true/false }
let currentView   = 'detail';

let rangeDate      = document.querySelector('#rangeDate');
let btnLoad         = document.querySelector('#btnLoad');
let btnExportXlsx    = document.querySelector('#btnExportXlsx');
let btnExpandAll     = document.querySelector('#btnExpandAll');
let btnCollapseAll   = document.querySelector('#btnCollapseAll');

initDatePicker(rangeDate, {
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
});

document.addEventListener('DOMContentLoaded', function () {
    const now = new Date();
    const dd  = String(now.getDate()).padStart(2, '0');
    const mm  = String(now.getMonth() + 1).padStart(2, '0');
    const yyyy = now.getFullYear();
    const todayStr    = `${dd}-${mm}-${yyyy}`;
    const firstDayStr = `01-${mm}-${yyyy}`;
    rangeDate.value = `${firstDayStr} to ${todayStr}`;

    if (typeof feather !== 'undefined') feather.replace();
});

// ============================================================
// EVENTS
// ============================================================
btnLoad.addEventListener('click', loadReport);
btnExportXlsx.addEventListener('click', exportXlsx);
btnExpandAll.addEventListener('click', () => toggleAllRows(true));
btnCollapseAll.addEventListener('click', () => toggleAllRows(false));

$(document).on('click', '.view-tab', function () {
    currentView = $(this).data('view');
    $('.view-tab').removeClass('active');
    $(this).addClass('active');
    if (currentView === 'detail') {
        $('#cardDetail').removeClass('d-none');
        $('#cardSummary').addClass('d-none');
    } else {
        $('#cardDetail').addClass('d-none');
        $('#cardSummary').removeClass('d-none');
    }
});

// ============================================================
// LOAD REPORT
// ============================================================
function loadReport() {
    const rangeVal  = (rangeDate.value || '').trim();
    const locations = $('#selLocations').val();

    if (!rangeVal) {
        Swal.fire('Perhatian', 'Pilih range tanggal terlebih dahulu.', 'warning');
        return;
    }

    const parts    = rangeVal.split(' to ');
    const fromDate = parts[0].trim();
    const toDate   = parts.length > 1 ? parts[1].trim() : parts[0].trim();

    if (!locations || locations.length === 0) {
        Swal.fire('Perhatian', 'Pilih minimal satu lokasi gudang.', 'warning');
        return;
    }

    showLoading('Mengambil data dari server...');

    $.ajax({
        url    : '{{ route("inventoryValuation.getData") }}',
        method : 'GET',
        data   : { from_date: fromDate, to_date: toDate, locations: locations },
        success: function (res) {
            reportData  = res.data;
            summaryData = res.summary_per_lokasi;
            reportMeta  = { from_date: res.from_date, to_date: res.to_date, loc_labels: res.loc_labels, locations: res.locations };
            expandedRows = {};
            renderDetailTable();
            renderSummaryTable();
            updateInfoBar();
            $('#viewSwitcher').removeClass('d-none');
            hideLoading();
        },
        error: function (xhr) {
            hideLoading();
            console.error('Gagal load data inventoryValuation.getData:', xhr.status, xhr.responseText);
            Swal.fire('Error', 'Gagal memuat data (' + xhr.status + '): ' + (xhr.responseJSON?.error || xhr.statusText), 'error');
        }
    });
}

// ============================================================
// RENDER DETAIL TABLE (dikelompokkan per lokasi, lalu per artikel)
// ============================================================
function renderDetailTable() {
    const tbody = $('#tbodyValuation');
    const tfoot = $('#tfootValuation');
    tbody.empty();

    const locs = reportMeta.locations || [];
    const hasAnyArticle = locs.some(loc => (reportData[loc] || []).length > 0);

    if (!hasAnyArticle) {
        tbody.html('<tr class="no-data-row"><td colspan="17"><i data-feather="info" class="mr-1"></i>Tidak ada data untuk filter yang dipilih.</td></tr>');
        tfoot.addClass('d-none');
        feather.replace();
        updateBtnState(false);
        return;
    }

    let gSaldoAwalQty = 0, gSaldoAwalValue = 0;
    let gQtyIn = 0, gValueIn = 0;
    let gQtyOut = 0, gValueOut = 0;
    let gAkhirQty = 0, gAkhirValue = 0;

    locs.forEach(function (loc) {
        const articles = reportData[loc] || [];
        if (articles.length === 0) return;

        const label = (reportMeta.loc_labels && reportMeta.loc_labels[loc]) ? reportMeta.loc_labels[loc] : loc;
        tbody.append(`
            <tr class="row-lokasi-header">
                <td colspan="17">${loc} — ${escHtml(label)} <span class="font-weight-normal" style="opacity:.75;">(${articles.length} artikel)</span></td>
            </tr>
        `);

        articles.forEach(function (art) {
            const artCode = art.artikel_code;
            const rowKey = loc + '|' + artCode;
            const isExpanded = expandedRows[rowKey] !== false; // default expanded
            const arrowClass = isExpanded ? '' : 'collapsed';
            const detailClass = isExpanded ? '' : 'd-none';

            // Baris header artikel: hanya ringkasan saldo akhir, TIDAK menumpuk saldo awal
            // di kolom "Masuk" (ini penyebab tampilan lama membingungkan).
            tbody.append(`
                <tr class="row-artikel-header ${arrowClass}" data-rowkey="${rowKey}">
                    <td>
                        <span class="toggle-icon mr-50">&#9660;</span>
                        <strong>${artCode}</strong> &mdash; ${escHtml(art.artikel_desc)}
                    </td>
                    <td>${art.uom}</td>
                    <td colspan="3"></td>
                    <td colspan="3" class="grp-saldo-awal"></td>
                    <td colspan="3" class="grp-masuk"></td>
                    <td colspan="3" class="grp-keluar"></td>
                    <td class="col-qty grp-saldo-akhir">${fmt(art.summary.saldo_akhir_qty)}</td>
                    <td class="col-money">${fmtRp(art.summary.avg_price_akhir)}</td>
                    <td class="col-money">${fmtRp(art.summary.saldo_akhir_value)}</td>
                </tr>
            `);

            // Baris Saldo Awal (kolom Saldo Awal saja yang terisi)
            tbody.append(`
                <tr class="row-detail detail-row-${CSS.escape(rowKey)} ${detailClass}">
                    <td class="row-detail-label">Saldo Awal</td>
                    <td>${art.uom}</td>
                    <td>—</td><td>—</td><td>—</td>
                    <td class="col-qty grp-saldo-awal">${fmt(art.saldo_awal.qty)}</td>
                    <td class="col-money">${fmtRp(art.saldo_awal.avg_price)}</td>
                    <td class="col-money">${fmtRp(art.saldo_awal.value)}</td>
                    <td colspan="3" class="grp-masuk"></td>
                    <td colspan="3" class="grp-keluar"></td>
                    <td colspan="3" class="grp-saldo-akhir"></td>
                </tr>
            `);

            // Baris Masuk (kolom Masuk saja yang terisi)
            art.transaksi_in.forEach(function (t) {
                tbody.append(`
                    <tr class="row-detail detail-row-${CSS.escape(rowKey)} ${detailClass}">
                        <td>&nbsp;</td>
                        <td>${art.uom}</td>
                        <td>${t.tanggal}</td>
                        <td><small>${escHtml(t.doc_number)}</small></td>
                        <td>${movBadge(t.movement_type, 'in')}</td>
                        <td colspan="3" class="grp-saldo-awal"></td>
                        <td class="col-qty grp-masuk">${fmt(t.qty)}</td>
                        <td class="col-money">${fmtRp(t.price)}</td>
                        <td class="col-money">${fmtRp(t.total_value)}</td>
                        <td colspan="3" class="grp-keluar"></td>
                        <td colspan="3" class="grp-saldo-akhir"></td>
                    </tr>
                `);
            });

            // Baris Keluar (kolom Keluar saja yang terisi)
            art.transaksi_out.forEach(function (t) {
                tbody.append(`
                    <tr class="row-detail detail-row-${CSS.escape(rowKey)} ${detailClass}">
                        <td>&nbsp;</td>
                        <td>${art.uom}</td>
                        <td>${t.tanggal}</td>
                        <td><small>${escHtml(t.doc_number)}</small></td>
                        <td>${movBadge(t.movement_type, 'out')}</td>
                        <td colspan="3" class="grp-saldo-awal"></td>
                        <td colspan="3" class="grp-masuk"></td>
                        <td class="col-qty grp-keluar">${fmt(t.qty)}</td>
                        <td class="col-money">${fmtRp(t.price)}</td>
                        <td class="col-money">${fmtRp(t.total_value)}</td>
                        <td colspan="3" class="grp-saldo-akhir"></td>
                    </tr>
                `);
            });

            // Baris Rekonsiliasi: menampilkan Saldo Awal | Masuk | Keluar | Saldo Akhir
            // sekaligus dalam satu baris, sehingga terlihat jelas:
            // Saldo Akhir = Saldo Awal + Masuk − Keluar
            tbody.append(`
                <tr class="row-reconcile detail-row-${CSS.escape(rowKey)} ${detailClass}">
                    <td class="text-right" colspan="5">${escHtml(art.artikel_desc)} — Subtotal </td>
                    <td class="col-qty grp-saldo-awal">${fmt(art.saldo_awal.qty)}</td>
                    <td class="col-money"></td>
                    <td class="col-money">${fmtRp(art.saldo_awal.value)}</td>
                    <td class="col-qty grp-masuk">${fmt(art.summary.total_qty_in)}</td>
                    <td class="col-money"></td>
                    <td class="col-money">${fmtRp(art.summary.total_value_in)}</td>
                    <td class="col-qty grp-keluar">${fmt(art.summary.total_qty_out)}</td>
                    <td class="col-money"></td>
                    <td class="col-money">${fmtRp(art.summary.total_value_out)}</td>
                    <td class="col-qty grp-saldo-akhir">${fmt(art.summary.saldo_akhir_qty)}</td>
                    <td class="col-money">${fmtRp(art.summary.avg_price_akhir)}</td>
                    <td class="col-money">${fmtRp(art.summary.saldo_akhir_value)}</td>
                </tr>
            `);

            gSaldoAwalQty   += art.saldo_awal.qty;
            gSaldoAwalValue += art.saldo_awal.value;
            gQtyIn          += art.summary.total_qty_in;
            gValueIn        += art.summary.total_value_in;
            gQtyOut         += art.summary.total_qty_out;
            gValueOut       += art.summary.total_value_out;
            gAkhirQty       += art.summary.saldo_akhir_qty;
            gAkhirValue     += art.summary.saldo_akhir_value;
        });
    });

    $('#ftSaldoAwalQty').text(fmt(gSaldoAwalQty));
    $('#ftSaldoAwalValue').text(fmtRp(gSaldoAwalValue));
    $('#ftTotalQtyIn').text(fmt(gQtyIn));
    $('#ftTotalValueIn').text(fmtRp(gValueIn));
    $('#ftTotalQtyOut').text(fmt(gQtyOut));
    $('#ftTotalValueOut').text(fmtRp(gValueOut));
    $('#ftSaldoAkhirQty').text(fmt(gAkhirQty));
    $('#ftSaldoAkhirValue').text(fmtRp(gAkhirValue));

    tfoot.removeClass('d-none');
    feather.replace();
    updateBtnState(true);

    $(document).off('click', '.row-artikel-header').on('click', '.row-artikel-header', function () {
        const rowKey     = $(this).data('rowkey');
        const isExpanded = !$(this).hasClass('collapsed');
        const sel = `.detail-row-${CSS.escape(rowKey)}`;

        if (isExpanded) {
            $(this).addClass('collapsed');
            $(sel).addClass('d-none');
            expandedRows[rowKey] = false;
        } else {
            $(this).removeClass('collapsed');
            $(sel).removeClass('d-none');
            expandedRows[rowKey] = true;
        }
    });
}

// ============================================================
// RENDER SUMMARY PER LOKASI (tanpa breakdown artikel)
// ============================================================
function renderSummaryTable() {
    const tbody = $('#tbodySummary');
    const tfoot = $('#tfootSummary');
    tbody.empty();

    if (!summaryData || summaryData.length === 0) {
        tbody.html('<tr class="no-data-row"><td colspan="10"><i data-feather="info" class="mr-1"></i>Tidak ada data untuk filter yang dipilih.</td></tr>');
        tfoot.addClass('d-none');
        feather.replace();
        return;
    }

    let tJumlah = 0;
    let tSaldoAwalQty = 0, tSaldoAwalValue = 0;
    let tQtyIn = 0, tValueIn = 0;
    let tQtyOut = 0, tValueOut = 0;
    let tAkhirQty = 0, tAkhirValue = 0;

    summaryData.forEach(function (s) {
        tbody.append(`
            <tr>
                <td><strong>${s.location}</strong> — ${escHtml(s.label)}</td>
                <td class="text-center">${s.jumlah_artikel}</td>
                <td class="col-qty grp-saldo-awal">${fmt(s.saldo_awal_qty)}</td>
                <td class="col-money">${fmtRp(s.saldo_awal_value)}</td>
                <td class="col-qty grp-masuk">${fmt(s.total_qty_in)}</td>
                <td class="col-money">${fmtRp(s.total_value_in)}</td>
                <td class="col-qty grp-keluar">${fmt(s.total_qty_out)}</td>
                <td class="col-money">${fmtRp(s.total_value_out)}</td>
                <td class="col-qty grp-saldo-akhir">${fmt(s.saldo_akhir_qty)}</td>
                <td class="col-money">${fmtRp(s.saldo_akhir_value)}</td>
            </tr>
        `);

        tJumlah         += s.jumlah_artikel;
        tSaldoAwalQty   += s.saldo_awal_qty;
        tSaldoAwalValue += s.saldo_awal_value;
        tQtyIn          += s.total_qty_in;
        tValueIn        += s.total_value_in;
        tQtyOut         += s.total_qty_out;
        tValueOut       += s.total_value_out;
        tAkhirQty       += s.saldo_akhir_qty;
        tAkhirValue     += s.saldo_akhir_value;
    });

    $('#fsJumlahArtikel').text(tJumlah);
    $('#fsSaldoAwalQty').text(fmt(tSaldoAwalQty));
    $('#fsSaldoAwalValue').text(fmtRp(tSaldoAwalValue));
    $('#fsQtyIn').text(fmt(tQtyIn));
    $('#fsValueIn').text(fmtRp(tValueIn));
    $('#fsQtyOut').text(fmt(tQtyOut));
    $('#fsValueOut').text(fmtRp(tValueOut));
    $('#fsAkhirQty').text(fmt(tAkhirQty));
    $('#fsAkhirValue').text(fmtRp(tAkhirValue));

    tfoot.removeClass('d-none');
    feather.replace();
}

// ============================================================
// EXPAND / COLLAPSE ALL
// ============================================================
function toggleAllRows(expand) {
    if (!reportData) return;
    (reportMeta.locations || []).forEach(function (loc) {
        (reportData[loc] || []).forEach(function (art) {
            const rowKey = loc + '|' + art.artikel_code;
            expandedRows[rowKey] = expand;
            const sel = `.detail-row-${CSS.escape(rowKey)}`;
            if (expand) {
                $(`[data-rowkey="${rowKey}"]`).removeClass('collapsed');
                $(sel).removeClass('d-none');
            } else {
                $(`[data-rowkey="${rowKey}"]`).addClass('collapsed');
                $(sel).addClass('d-none');
            }
        });
    });
}

// ============================================================
// EXPORT EXCEL (SheetJS)
// ============================================================
function exportXlsx() {
    if (!reportData) return;

    showLoading('Menyiapkan file Excel...');

    setTimeout(function () {
        const wb = XLSX.utils.book_new();

        // ── Sheet: Ringkasan per Lokasi ──
        const summaryRows = [
            ['INVENTORY VALUATION — RINGKASAN PER LOKASI'],
            [`Periode: ${reportMeta.from_date} s/d ${reportMeta.to_date}`],
            [],
            ['Lokasi', 'Nama', 'Jml Artikel',
             'Saldo Awal Qty', 'Saldo Awal Nilai',
             'Masuk Qty', 'Masuk Nilai',
             'Keluar Qty', 'Keluar Nilai',
             'Saldo Akhir Qty', 'Saldo Akhir Nilai'],
        ];
        (summaryData || []).forEach(function (s) {
            summaryRows.push([
                s.location, s.label, s.jumlah_artikel,
                s.saldo_awal_qty, s.saldo_awal_value,
                s.total_qty_in, s.total_value_in,
                s.total_qty_out, s.total_value_out,
                s.saldo_akhir_qty, s.saldo_akhir_value,
            ]);
        });
        const wsSummary = XLSX.utils.aoa_to_sheet(summaryRows);
        wsSummary['!cols'] = [{wch:10},{wch:20},{wch:12},{wch:14},{wch:16},{wch:14},{wch:16},{wch:14},{wch:16},{wch:14},{wch:16}];
        XLSX.utils.book_append_sheet(wb, wsSummary, 'Ringkasan Lokasi');

        // ── Sheet: Detail per Artikel (dikelompokkan per lokasi) ──
        const detailRows = [
            ['INVENTORY VALUATION — DETAIL TRANSAKSI'],
            [`Periode: ${reportMeta.from_date} s/d ${reportMeta.to_date}`],
            [],
            ['Lokasi', 'Artikel Code', 'Deskripsi', 'UOM', 'Tanggal', 'No. Dokumen', 'Tipe',
             'Saldo Awal Qty', 'Saldo Awal Nilai',
             'IN Qty', 'IN Nilai', 'OUT Qty', 'OUT Nilai',
             'Saldo Akhir Qty', 'Saldo Akhir Nilai', 'Keterangan'],
        ];

        (reportMeta.locations || []).forEach(function (loc) {
            (reportData[loc] || []).forEach(function (art) {
                detailRows.push([
                    loc, art.artikel_code, art.artikel_desc, art.uom,
                    '', '', 'SALDO AWAL',
                    art.saldo_awal.qty, art.saldo_awal.value,
                    '', '', '', '',
                    '', '', ''
                ]);
                art.transaksi_in.forEach(function (t) {
                    detailRows.push([
                        loc, art.artikel_code, art.artikel_desc, art.uom,
                        t.tanggal, t.doc_number, t.movement_type,
                        '', '',
                        t.qty, t.total_value, '', '',
                        '', '', t.keterangan
                    ]);
                });
                art.transaksi_out.forEach(function (t) {
                    detailRows.push([
                        loc, art.artikel_code, art.artikel_desc, art.uom,
                        t.tanggal, t.doc_number, t.movement_type,
                        '', '',
                        '', '', t.qty, t.total_value,
                        '', '', t.keterangan
                    ]);
                });
                detailRows.push([
                    loc, art.artikel_code, `-- SALDO AKHIR ${art.artikel_desc} --`, art.uom,
                    '', '', '',
                    '', '',
                    art.summary.total_qty_in, art.summary.total_value_in,
                    art.summary.total_qty_out, art.summary.total_value_out,
                    art.summary.saldo_akhir_qty, art.summary.saldo_akhir_value, ''
                ]);
            });
        });

        const wsDetail = XLSX.utils.aoa_to_sheet(detailRows);
        wsDetail['!cols'] = [
            {wch:8},{wch:15},{wch:35},{wch:8},{wch:12},{wch:18},{wch:15},
            {wch:12},{wch:14},{wch:10},{wch:14},{wch:10},{wch:14},{wch:12},{wch:14},{wch:30}
        ];
        XLSX.utils.book_append_sheet(wb, wsDetail, 'Detail Transaksi');

        const fromStr = reportMeta.from_date.replace(/-/g,'');
        const toStr   = reportMeta.to_date.replace(/-/g,'');
        XLSX.writeFile(wb, `InventoryValuation_${fromStr}_${toStr}.xlsx`);
        hideLoading();
    }, 100);
}

// ============================================================
// INFO BAR / LOADING / FORMAT HELPERS
// ============================================================
function updateInfoBar() {
    if (!reportMeta) return;
    const jumlahArtikel = (reportMeta.locations || []).reduce((sum, loc) => sum + (reportData[loc] || []).length, 0);
    $('#badgePeriode').text(`Periode: ${reportMeta.from_date} s/d ${reportMeta.to_date}`);
    $('#badgeLokasi').text(`Lokasi: ${Object.values(reportMeta.loc_labels).join(', ')}`);
    $('#badgeJumlahArtikel').text(`${jumlahArtikel} artikel`);
    $('#infoBar').removeClass('d-none');
}

function updateBtnState(hasData) {
    $('#btnExportXlsx, #btnExpandAll, #btnCollapseAll').prop('disabled', !hasData);
}

function showLoading(msg) {
    $('#loadingText').text(msg || 'Memuat...');
    $('#loadingOverlay').addClass('show');
}
function hideLoading() {
    $('#loadingOverlay').removeClass('show');
}

function fmtRp(val) {
    if (val === null || val === undefined || val === '') return '—';
    const num = parseFloat(val);
    if (isNaN(num) || num === 0) return '—';
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
}

function fmt(val) {
    if (val === null || val === undefined || val === '') return '—';
    const num = parseFloat(val);
    if (isNaN(num) || num === 0) return '—';
    return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function movBadge(type, direction) {
    if (!type) return '<span class="badge badge-mv">—</span>';
    const cls = direction === 'in' ? 'mv-in' : 'mv-out';
    return `<span class="badge badge-mv ${cls}">${escHtml(type)}</span>`;
}

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>
@endsection