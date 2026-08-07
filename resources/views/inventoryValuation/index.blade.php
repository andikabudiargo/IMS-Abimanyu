@extends('layouts.app')
@section('title', 'Inventory Valuation Report')
@section('content')
@include('layouts.breadcrumb')

{{-- Loading Overlay --}}
<div id="loadingOverlay">
    <div class="text-center text-white">
        <div class="spinner-border text-light mb-2" style="width:3rem;height:3rem;" role="status"></div>
        <div id="loadingText" class="font-weight-bold">Memuat data...</div>
    </div>
</div>

<section id="inventory-valuation-index">

    {{-- ── Filter Card ── --}}
    <div class="card filter-card mb-1">
        <div class="card-body py-1">
            <div class="row align-items-end">

                {{-- Range Date — satu input, mode range, pakai helper global initDatePicker() --}}
                <div class="col-md-3 col-12 mb-1">
                    <label class="form-label mb-25 font-weight-bold" style="font-size:0.8rem;">
                        <i data-feather="calendar" class="mr-25" style="width:14px;height:14px;"></i>
                        Periode
                    </label>
                    <input type="text" id="rangeDate" name="rangeDate" class="form-control flatpickr-range"
                           placeholder="DD-MM-YYYY to DD-MM-YYYY" autocomplete="off">
                </div>

                {{-- Pilih Lokasi — pakai class select2, auto ke-init sama seperti halaman lain --}}
                <div class="col-md-4 col-12 mb-1">
                    <label class="form-label mb-25 font-weight-bold" style="font-size:0.8rem;">
                        <i data-feather="map-pin" class="mr-25" style="width:14px;height:14px;"></i>
                        Lokasi Gudang
                    </label>
                    <select id="selLocations" name="selLocations" class="select2 form-control" multiple>
                        @foreach($locations as $code => $label)
                            <option value="{{ $code }}" selected>{{ $code }} - {{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tombol --}}
                <div class="col-md-5 col-12 mb-1 d-flex align-items-end gap-50">
                    <button id="btnLoad" type="button" class="btn btn-primary btn-sm mr-50">
                        <i data-feather="search" class="mr-25" style="width:14px;height:14px;"></i>
                        Tampilkan
                    </button>
                    <button id="btnExportXlsx" type="button" class="btn btn-success btn-sm mr-50" disabled>
                        <i data-feather="download" class="mr-25" style="width:14px;height:14px;"></i>
                        Export Excel
                    </button>
                    <button id="btnExpandAll" type="button" class="btn btn-outline-secondary btn-sm mr-50" disabled>
                        <i data-feather="maximize-2" class="mr-25" style="width:14px;height:14px;"></i>
                        Expand All
                    </button>
                    <button id="btnCollapseAll" type="button" class="btn btn-outline-secondary btn-sm" disabled>
                        <i data-feather="minimize-2" class="mr-25" style="width:14px;height:14px;"></i>
                        Collapse All
                    </button>
                </div>
            </div>

            {{-- Info summary (muncul setelah load) --}}
            <div id="infoBar" class="d-none">
                <hr class="my-50">
                <div class="row">
                    <div class="col-12">
                        <span class="badge badge-light-primary mr-1" id="badgePeriode"></span>
                        <span class="badge badge-light-secondary mr-1" id="badgeLokasi"></span>
                        <span class="badge badge-light-info" id="badgeJumlahArtikel"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Table Card ── --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tblValuation" class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th rowspan="2" style="min-width:200px;">Artikel</th>
                            <th rowspan="2" style="min-width:60px;">UOM</th>
                            <th rowspan="2" style="min-width:80px;">Tgl</th>
                            <th rowspan="2" style="min-width:130px;">No. Dokumen</th>
                            <th rowspan="2" style="min-width:100px;">Tipe</th>
                            <th rowspan="2" style="min-width:60px;">Lokasi</th>
                            <th colspan="3" class="text-center" style="background-color:#5a52bf !important;">MASUK (IN)</th>
                            <th colspan="3" class="text-center" style="background-color:#d63031 !important;">KELUAR (OUT)</th>
                            <th colspan="3" class="text-center" style="background-color:#00b894 !important;">SALDO AKHIR</th>
                        </tr>
                        <tr>
                            {{-- IN --}}
                            <th class="col-qty"  style="background-color:#7367f0cc !important;">QTY</th>
                            <th class="col-money" style="background-color:#7367f0cc !important;">Harga</th>
                            <th class="col-money" style="background-color:#7367f0cc !important;">Nilai</th>
                            {{-- OUT --}}
                            <th class="col-qty"  style="background-color:#ea5455cc !important;">QTY</th>
                            <th class="col-money" style="background-color:#ea5455cc !important;">Harga</th>
                            <th class="col-money" style="background-color:#ea5455cc !important;">Nilai</th>
                            {{-- SALDO AKHIR --}}
                            <th class="col-qty"  style="background-color:#28c76fcc !important;">QTY</th>
                            <th class="col-money" style="background-color:#28c76fcc !important;">Avg Price</th>
                            <th class="col-money" style="background-color:#28c76fcc !important;">Nilai</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyValuation">
                        <tr class="no-data-row">
                            <td colspan="15">
                                <i data-feather="info" class="mr-1"></i>
                                Pilih filter dan klik <strong>Tampilkan</strong> untuk memuat data.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot id="tfootValuation" class="d-none">
                        <tr class="tfoot-total">
                            <td colspan="6" class="text-right">GRAND TOTAL</td>
                            <td class="col-qty"  id="ftTotalQtyIn"></td>
                            <td class="col-money"></td>
                            <td class="col-money" id="ftTotalValueIn"></td>
                            <td class="col-qty"  id="ftTotalQtyOut"></td>
                            <td class="col-money"></td>
                            <td class="col-money" id="ftTotalValueOut"></td>
                            <td class="col-qty"  id="ftSaldoAkhirQty"></td>
                            <td class="col-money"></td>
                            <td class="col-money" id="ftSaldoAkhirValue"></td>
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
     layouts.app (terbukti dari halaman actualFinishGoods
     yang pakai keduanya tanpa load manual sama sekali).
     Kalau di-load ulang di sini, file JS-nya ke-load 2x
     dan bikin plugin (terutama flatpickr) auto-init dobel
     — itu penyebab kalender kemarin cuma bisa pilih tgl 1.

     Satu-satunya library yang MEMANG belum ada secara
     global adalah SheetJS (xlsx), jadi itu saja yang
     tetap dimuat, taruh di dalam section 'scripts'.
════════════════════════════════════════════════ --}}

@section('styles')
<style>
    /* ── Filter Card ── */
    .filter-card { border-left: 4px solid #7367f0; }

    /* ── Artikel header row ── */
    .row-artikel-header {
        background-color: #f3f0ff !important;
        font-weight: 600;
        cursor: pointer;
    }
    .row-artikel-header td { border-top: 2px solid #7367f0 !important; }
    .row-artikel-header:hover { background-color: #e9e4ff !important; }

    /* ── Baris IN ── */
    .row-trans-in td:first-child { padding-left: 2.5rem; }
    .row-trans-in { background-color: #f6fffa !important; font-size: 0.85rem; }

    /* ── Baris OUT ── */
    .row-trans-out td:first-child { padding-left: 2.5rem; }
    .row-trans-out { background-color: #fff8f8 !important; font-size: 0.85rem; }

    /* ── Saldo rows ── */
    .row-saldo-awal  { background-color: #fffde7 !important; font-size: 0.85rem; }
    .row-saldo-akhir { background-color: #e8f5e9 !important; font-size: 0.85rem; font-weight: 600; }

    /* ── Subtotal row ── */
    .row-subtotal { background-color: #eef1fb !important; font-weight: 600; font-size: 0.85rem; }
    .row-subtotal td { border-top: 1px dashed #7367f0 !important; }

    /* ── Toggle icon ── */
    .toggle-icon { transition: transform 0.2s; display: inline-block; }
    .collapsed .toggle-icon { transform: rotate(-90deg); }

    /* ── Table ── */
    #tblValuation { font-size: 0.82rem; }
    #tblValuation th { background-color: #7367f0 !important; color: #fff; white-space: nowrap; }
    #tblValuation .col-money { text-align: right; white-space: nowrap; }
    #tblValuation .col-qty   { text-align: right; }

    /* ── Badge movement ── */
    .badge-receiving  { background-color: #28c76f; color: #fff; }
    .badge-transfer   { background-color: #00cfe8; color: #fff; }
    .badge-adjustment { background-color: #ff9f43; color: #fff; }
    .badge-supply     { background-color: #ea5455; color: #fff; }
    .badge-delivery   { background-color: #e83e8c; color: #fff; }
    .badge-ob         { background-color: #7367f0; color: #fff; }
    .badge-other      { background-color: #b0b8c1; color: #fff; }

    /* ── Loading overlay ── */
    #loadingOverlay {
        display: none;
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.35); z-index: 9999;
        align-items: center; justify-content: center;
    }
    #loadingOverlay.show { display: flex; }

    /* ── Summary total footer ── */
    .tfoot-total td { font-weight: 700; background-color: #7367f0 !important; color: #fff; }

    .no-data-row td { text-align: center; color: #aaa; padding: 2rem !important; }
</style>
@endsection

@section('scripts')
{{-- SheetJS: satu-satunya library baru yang benar-benar belum ada global --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script type="text/javascript">
// ============================================================
// STATE
// ============================================================
let reportData   = null;  // hasil JSON dari server
let reportMeta   = null;  // from_date, to_date, loc_labels
let expandedRows = {};    // { artikel_code: true/false }

let rangeDate    = document.querySelector('#rangeDate');
let btnLoad      = document.querySelector('#btnLoad');
let btnExportXlsx  = document.querySelector('#btnExportXlsx');
let btnExpandAll   = document.querySelector('#btnExpandAll');
let btnCollapseAll = document.querySelector('#btnCollapseAll');

// ── Date range picker — sama persis pola halaman lain (initDatePicker global) ──
initDatePicker(rangeDate, {
    minDate: "01/01/2010",
    maxDate: "31/12/2030",
    dateFormat: "d-m-Y",
    mode: "range"
});

// ── Default value awal: 1 bulan berjalan s/d hari ini ──
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

    // format flatpickr range: "01-08-2026 to 05-08-2026"
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
            reportData = res.data;
            reportMeta = { from_date: res.from_date, to_date: res.to_date, loc_labels: res.loc_labels, locations: res.locations };
            expandedRows = {};
            renderTable();
            updateInfoBar();
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
// RENDER TABLE
// ============================================================
function renderTable() {
    const tbody = $('#tbodyValuation');
    const tfoot = $('#tfootValuation');
    tbody.empty();

    if (!reportData || reportData.length === 0) {
        tbody.html('<tr class="no-data-row"><td colspan="15"><i data-feather="info" class="mr-1"></i>Tidak ada data untuk filter yang dipilih.</td></tr>');
        tfoot.addClass('d-none');
        feather.replace();
        updateBtnState(false);
        return;
    }

    let grandTotalQtyIn = 0, grandTotalValueIn = 0;
    let grandTotalQtyOut = 0, grandTotalValueOut = 0;
    let grandSaldoAkhirQty = 0, grandSaldoAkhirValue = 0;

    reportData.forEach(function (art) {
        const artCode = art.artikel_code;
        const isExpanded = expandedRows[artCode] !== false; // default expanded

        const arrowClass = isExpanded ? '' : 'collapsed';
        const detailClass = isExpanded ? '' : 'd-none';

        tbody.append(`
            <tr class="row-artikel-header ${arrowClass}" data-artcode="${artCode}">
                <td>
                    <span class="toggle-icon mr-50">&#9660;</span>
                    <strong>${artCode}</strong> &mdash; ${escHtml(art.artikel_desc)}
                </td>
                <td>${art.uom}</td>
                <td colspan="4"></td>
                <td class="col-qty">${fmt(art.saldo_awal.qty)}</td>
                <td class="col-money">${fmtRp(art.saldo_awal.avg_price)}</td>
                <td class="col-money">${fmtRp(art.saldo_awal.value)}</td>
                <td colspan="3"></td>
                <td class="col-qty">${fmt(art.summary.saldo_akhir_qty)}</td>
                <td class="col-money">${fmtRp(art.summary.avg_price_akhir)}</td>
                <td class="col-money">${fmtRp(art.summary.saldo_akhir_value)}</td>
            </tr>
        `);

        tbody.append(`
            <tr class="row-saldo-awal detail-row-${artCode} ${detailClass}">
                <td class="pl-4"><em>Saldo Awal</em></td>
                <td>${art.uom}</td>
                <td>—</td><td>—</td>
                <td><span class="badge badge-ob badge-sm">SALDO AWAL</span></td>
                <td>—</td>
                <td class="col-qty">${fmt(art.saldo_awal.qty)}</td>
                <td class="col-money">${fmtRp(art.saldo_awal.avg_price)}</td>
                <td class="col-money">${fmtRp(art.saldo_awal.value)}</td>
                <td colspan="3"></td>
                <td colspan="3"></td>
            </tr>
        `);

        art.transaksi_in.forEach(function (t) {
            tbody.append(`
                <tr class="row-trans-in detail-row-${artCode} ${detailClass}">
                    <td class="pl-4">&nbsp;</td>
                    <td>${art.uom}</td>
                    <td>${t.tanggal}</td>
                    <td><small>${escHtml(t.doc_number)}</small></td>
                    <td>${movBadge(t.movement_type)}</td>
                    <td><small>${t.location}</small></td>
                    <td class="col-qty">${fmt(t.qty)}</td>
                    <td class="col-money">${fmtRp(t.price)}</td>
                    <td class="col-money">${fmtRp(t.total_value)}</td>
                    <td colspan="3"></td>
                    <td colspan="3"></td>
                </tr>
            `);
        });

        art.transaksi_out.forEach(function (t) {
            tbody.append(`
                <tr class="row-trans-out detail-row-${artCode} ${detailClass}">
                    <td class="pl-4">&nbsp;</td>
                    <td>${art.uom}</td>
                    <td>${t.tanggal}</td>
                    <td><small>${escHtml(t.doc_number)}</small></td>
                    <td>${movBadge(t.movement_type)}</td>
                    <td><small>${t.location}</small></td>
                    <td colspan="3"></td>
                    <td class="col-qty">${fmt(t.qty)}</td>
                    <td class="col-money">${fmtRp(t.price)}</td>
                    <td class="col-money">${fmtRp(t.total_value)}</td>
                    <td colspan="3"></td>
                </tr>
            `);
        });

        tbody.append(`
            <tr class="row-subtotal detail-row-${artCode} ${detailClass}">
                <td class="text-right" colspan="6">Subtotal ${escHtml(art.artikel_desc)}</td>
                <td class="col-qty">${fmt(art.summary.total_qty_in)}</td>
                <td class="col-money"></td>
                <td class="col-money">${fmtRp(art.summary.total_value_in)}</td>
                <td class="col-qty">${fmt(art.summary.total_qty_out)}</td>
                <td class="col-money"></td>
                <td class="col-money">${fmtRp(art.summary.total_value_out)}</td>
                <td class="col-qty">${fmt(art.summary.saldo_akhir_qty)}</td>
                <td class="col-money">${fmtRp(art.summary.avg_price_akhir)}</td>
                <td class="col-money">${fmtRp(art.summary.saldo_akhir_value)}</td>
            </tr>
        `);

        grandTotalQtyIn      += art.summary.total_qty_in;
        grandTotalValueIn    += art.summary.total_value_in;
        grandTotalQtyOut     += art.summary.total_qty_out;
        grandTotalValueOut   += art.summary.total_value_out;
        grandSaldoAkhirQty   += art.summary.saldo_akhir_qty;
        grandSaldoAkhirValue += art.summary.saldo_akhir_value;
    });

    $('#ftTotalQtyIn').text(fmt(grandTotalQtyIn));
    $('#ftTotalValueIn').text(fmtRp(grandTotalValueIn));
    $('#ftTotalQtyOut').text(fmt(grandTotalQtyOut));
    $('#ftTotalValueOut').text(fmtRp(grandTotalValueOut));
    $('#ftSaldoAkhirQty').text(fmt(grandSaldoAkhirQty));
    $('#ftSaldoAkhirValue').text(fmtRp(grandSaldoAkhirValue));

    tfoot.removeClass('d-none');
    feather.replace();
    updateBtnState(true);

    $(document).off('click', '.row-artikel-header').on('click', '.row-artikel-header', function () {
        const artCode    = $(this).data('artcode');
        const isExpanded = !$(this).hasClass('collapsed');

        if (isExpanded) {
            $(this).addClass('collapsed');
            $(`.detail-row-${CSS.escape(artCode)}`).addClass('d-none');
            expandedRows[artCode] = false;
        } else {
            $(this).removeClass('collapsed');
            $(`.detail-row-${CSS.escape(artCode)}`).removeClass('d-none');
            expandedRows[artCode] = true;
        }
    });
}

// ============================================================
// EXPAND / COLLAPSE ALL
// ============================================================
function toggleAllRows(expand) {
    if (!reportData) return;
    reportData.forEach(function (art) {
        const artCode = art.artikel_code;
        expandedRows[artCode] = expand;
        if (expand) {
            $(`[data-artcode="${artCode}"]`).removeClass('collapsed');
            $(`.detail-row-${CSS.escape(artCode)}`).removeClass('d-none');
        } else {
            $(`[data-artcode="${artCode}"]`).addClass('collapsed');
            $(`.detail-row-${CSS.escape(artCode)}`).addClass('d-none');
        }
    });
}

// ============================================================
// EXPORT EXCEL (SheetJS)
// ============================================================
function exportXlsx() {
    if (!reportData || reportData.length === 0) return;

    showLoading('Menyiapkan file Excel...');

    setTimeout(function () {
        const wb = XLSX.utils.book_new();

        const summaryRows = [
            ['INVENTORY VALUATION REPORT'],
            [`Periode: ${reportMeta.from_date} s/d ${reportMeta.to_date}`],
            [`Lokasi: ${Object.values(reportMeta.loc_labels).join(', ')}`],
            [],
            ['Artikel Code', 'Deskripsi', 'UOM',
             'Saldo Awal Qty', 'Saldo Awal Avg Price', 'Saldo Awal Nilai',
             'Total IN Qty', 'Avg Price IN', 'Total IN Nilai',
             'Total OUT Qty', 'Avg Price OUT', 'Total OUT Nilai',
             'Saldo Akhir Qty', 'Avg Price Akhir', 'Saldo Akhir Nilai'],
        ];

        let gtQtyIn = 0, gtValIn = 0, gtQtyOut = 0, gtValOut = 0, gtSaldoQty = 0, gtSaldoVal = 0;

        reportData.forEach(function (art) {
            summaryRows.push([
                art.artikel_code, art.artikel_desc, art.uom,
                art.saldo_awal.qty, art.saldo_awal.avg_price, art.saldo_awal.value,
                art.summary.total_qty_in, art.summary.avg_price_in, art.summary.total_value_in,
                art.summary.total_qty_out, 0, art.summary.total_value_out,
                art.summary.saldo_akhir_qty, art.summary.avg_price_akhir, art.summary.saldo_akhir_value,
            ]);
            gtQtyIn    += art.summary.total_qty_in;
            gtValIn    += art.summary.total_value_in;
            gtQtyOut   += art.summary.total_qty_out;
            gtValOut   += art.summary.total_value_out;
            gtSaldoQty += art.summary.saldo_akhir_qty;
            gtSaldoVal += art.summary.saldo_akhir_value;
        });

        summaryRows.push([]);
        summaryRows.push(['GRAND TOTAL', '', '', '', '', '',
            gtQtyIn, '', gtValIn, gtQtyOut, '', gtValOut, gtSaldoQty, '', gtSaldoVal]);

        const wsSummary = XLSX.utils.aoa_to_sheet(summaryRows);
        wsSummary['!cols'] = [
            {wch:15},{wch:35},{wch:8},
            {wch:12},{wch:14},{wch:16},
            {wch:12},{wch:14},{wch:16},
            {wch:12},{wch:14},{wch:16},
            {wch:12},{wch:14},{wch:16},
        ];
        XLSX.utils.book_append_sheet(wb, wsSummary, 'Summary');

        const detailRows = [
            ['INVENTORY VALUATION - DETAIL TRANSAKSI'],
            [`Periode: ${reportMeta.from_date} s/d ${reportMeta.to_date}`],
            [],
            ['Artikel Code', 'Deskripsi', 'UOM', 'Tanggal', 'No. Dokumen', 'Tipe', 'Lokasi',
             'IN QTY', 'IN Price', 'IN Nilai', 'OUT QTY', 'OUT Price', 'OUT Nilai', 'Keterangan'],
        ];

        reportData.forEach(function (art) {
            detailRows.push([
                art.artikel_code, art.artikel_desc, art.uom,
                '—', '—', 'SALDO AWAL', '—',
                art.saldo_awal.qty, art.saldo_awal.avg_price, art.saldo_awal.value,
                '', '', '', ''
            ]);
            art.transaksi_in.forEach(function (t) {
                detailRows.push([
                    art.artikel_code, art.artikel_desc, art.uom,
                    t.tanggal, t.doc_number, t.movement_type, t.location,
                    t.qty, t.price, t.total_value, '', '', '', t.keterangan
                ]);
            });
            art.transaksi_out.forEach(function (t) {
                detailRows.push([
                    art.artikel_code, art.artikel_desc, art.uom,
                    t.tanggal, t.doc_number, t.movement_type, t.location,
                    '', '', '', t.qty, t.price, t.total_value, t.keterangan
                ]);
            });
            detailRows.push([
                art.artikel_code, `-- SUBTOTAL ${art.artikel_desc} --`, art.uom,
                '', '', '', '',
                art.summary.total_qty_in, '', art.summary.total_value_in,
                art.summary.total_qty_out, '', art.summary.total_value_out, ''
            ]);
            detailRows.push([]);
        });

        const wsDetail = XLSX.utils.aoa_to_sheet(detailRows);
        wsDetail['!cols'] = [
            {wch:15},{wch:35},{wch:8},{wch:12},{wch:18},{wch:15},{wch:8},
            {wch:10},{wch:14},{wch:16},{wch:10},{wch:14},{wch:16},{wch:30}
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
    if (!reportMeta || !reportData) return;
    $('#badgePeriode').text(`📅 ${reportMeta.from_date} s/d ${reportMeta.to_date}`);
    $('#badgeLokasi').text(`📦 ${Object.values(reportMeta.loc_labels).join(' | ')}`);
    $('#badgeJumlahArtikel').text(`🔢 ${reportData.length} artikel`);
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

function movBadge(type) {
    if (!type) return '<span class="badge badge-other badge-sm">—</span>';
    const t = type.toUpperCase();
    let cls = 'badge-other';
    if (t.includes('RECEIVING'))        cls = 'badge-receiving';
    else if (t.includes('TRANSFER'))    cls = 'badge-transfer';
    else if (t.includes('ADJUSTMENT'))  cls = 'badge-adjustment';
    else if (t.includes('SUPPLY'))      cls = 'badge-supply';
    else if (t.includes('DELIVERY'))    cls = 'badge-delivery';
    else if (t.includes('OPENING'))     cls = 'badge-ob';
    return `<span class="badge ${cls} badge-sm" style="font-size:0.7rem;">${escHtml(type)}</span>`;
}

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
</script>
@endsection