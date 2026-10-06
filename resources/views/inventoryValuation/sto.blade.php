@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

{{-- Tabel & style disamakan dengan STO Report (sticky header + freeze 3 kolom kiri + zebra) --}}
<style>
.rt-scroll { max-height: 68vh; overflow: auto; position: relative; }
.rt { border-collapse: separate; border-spacing: 0; width: 100%; margin-bottom: 0; border-top: 1px solid #e3e6ec; border-left: 1px solid #e3e6ec; }
.rt th, .rt td { box-sizing: border-box; border-right: 1px solid #e3e6ec; border-bottom: 1px solid #e3e6ec; padding: 4px 6px; white-space: nowrap; }
.rt thead th { position: sticky; z-index: 3; background: #eef2f7 !important; height: 30px; vertical-align: middle; }
.rt thead tr:first-child th  { top: 0; }
.rt thead tr:nth-child(2) th { top: 30px; }
.rt thead th.bg-light-primary { background: #e3ecfb !important; color: #4b6cb7; }
.rt thead th.bg-light-danger  { background: #fbe4e4 !important; color: #b74b4b; }
.rt .col-no, .rt .col-alt, .rt .col-desc { position: sticky; }
.rt .col-no   { left: 0;     min-width: 42px;  max-width: 42px;  }
.rt .col-alt  { left: 42px;  min-width: 100px; max-width: 100px; }
.rt .col-desc { left: 142px; min-width: 220px; max-width: 220px; white-space: normal; }
.rt tbody td.col-no, .rt tbody td.col-alt, .rt tbody td.col-desc { z-index: 2; }
.rt thead th.col-no, .rt thead th.col-alt, .rt thead th.col-desc { z-index: 5; background: #e4e9f1 !important; }
.rt tbody tr:nth-child(odd)  td { background: #ffffff !important; }
.rt tbody tr:nth-child(even) td { background: #f4f7fb !important; }
.rt tbody tr:hover td { background: #e8f1ff !important; }
.rt tfoot td { position: sticky; bottom: 0; background: #e9edf3 !important; z-index: 3; font-weight: bold; height: 34px; vertical-align: middle; padding: 6px 6px; border-top: 2px solid #c8cfda; }
</style>

<section>
    <div class="card">
        <div class="card-header"><h4 class="card-title">Filter Inventory Valuation</h4></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="repStoCode">Referensi STO <small class="text-muted">(opsional)</small></label>
                    <select class="form-control" id="repStoCode">
                        <option value="">-- Tanpa referensi STO --</option>
                        @foreach($stoList as $s)
                            <option value="{{ $s->enc_id }}">{{ $s->sto_code }} ({{ $s->periode }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="repLocation">Lokasi Gudang <span class="text-danger">*</span> <small class="text-muted">(bisa lebih dari satu)</small></label>
                    <select class="form-control" id="repLocation" multiple></select>
                </div>
                <div class="form-group col-md-4">
                    <label for="repDate">Rentang Tanggal <span class="text-danger" id="dateReq">*</span>
                        <small class="text-muted" id="dateHint">(wajib tanpa referensi STO)</small></label>
                    <input type="text" class="form-control flatpickr-range" id="repDate" placeholder="DD-MM-YYYY to DD-MM-YYYY">
                </div>
            </div>
            <button type="button" class="btn btn-primary" id="btnGenerate">Generate Report</button>
            <button type="button" class="btn btn-light" id="btnReset">Reset</button>
            <button type="button" class="btn btn-outline-secondary d-none float-right" id="btnPrint">Print</button>
        </div>
    </div>
</section>

<section>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Hasil Report</h4></div>
        <div class="card-body">
            <div id="reportHeaderInfo" class="mb-1 d-none" style="font-size:.85rem;">
                <strong>Referensi :</strong> <span id="hSto">-</span> &nbsp;|&nbsp;
                <strong>Periode :</strong> <span id="hPeriode">-</span> &nbsp;|&nbsp;
                <strong>Rentang :</strong> <span id="hRange">-</span>
                <hr class="mt-50">
            </div>
            <div id="reportEmpty" class="alert alert-warning">
                Pilih <strong>Lokasi</strong> (dan rentang tanggal bila tanpa referensi STO), lalu klik <strong>Generate Report</strong>.
            </div>
            <div id="reportErrors" class="alert alert-danger d-none"></div>
            <div class="btn-group mb-1 d-none" role="group" id="viewSwitcher">
                <button type="button" class="btn btn-sm btn-outline-dark view-tab active" data-view="detail">Detail</button>
                <button type="button" class="btn btn-sm btn-outline-dark view-tab" data-view="summary">Summary</button>
            </div>
            <div class="d-none" id="detailWrap"></div>
            <div class="d-none table-responsive" id="summaryWrap">
                <table class="table table-bordered table-sm" id="summaryTable" style="font-size:.82rem;">
                    <thead class="text-center">
                        <tr>
                            <th>Lokasi</th><th>Artikel</th><th>Saldo Awal</th><th class="bg-light-primary">Debit</th>
                            <th class="bg-light-danger">Kredit</th><th>Saldo Akhir</th><th>Nilai Hasil STO</th><th>Selisih</th>
                        </tr>
                    </thead>
                    <tbody id="summaryBody"></tbody>
                    <tfoot id="summaryFoot"></tfoot>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- MODAL — daftar dokumen di balik nilai (klik angka Saldo Awal / Debit / Kredit) --}}
<div class="modal fade" id="movementDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mvDetailTitle">Detail Pergerakan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" style="font-size:.8rem;">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Dokumen</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Harga Satuan (Rata-rata)</th>
                                <th class="text-right">Nilai</th>
                            </tr>
                        </thead>
                        <tbody id="mvDetailBody"></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-right">Total</th>
                                <th class="text-right" id="mvDetailTotalQty">0.00</th>
                                <th></th>
                                <th class="text-right" id="mvDetailTotal">0.00</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
$(document).ready(function () {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('#repStoCode').select2({ width: '100%' });
    $('#repLocation').select2({ width: '100%', closeOnSelect: false, placeholder: 'Pilih gudang' });
    initDatePicker(document.querySelector('#repDate'), {
        minDate: "01/01/2010", maxDate: "31/12/2030", dateFormat: "d-m-Y", mode: "range"
    });

    // Layout menjalankan $('.select2').select2() tiap modal terbuka; selector itu ikut kena container
    // hasil render select2 (juga ber-class "select2") sehingga filter jadi rusak/hilang. Matikan utk modal ini.
    $('#movementDetailModal').off('shown.bs.modal');

    const ALL_LOCATIONS = @json($allLocations);
    let lastRes = null;

    function fmt(v) {
        let n = parseFloat(v);
        if (v === null || v === undefined || isNaN(n)) return '-';
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function diffCls(v) { return v > 0 ? 'text-success' : (v < 0 ? 'text-danger' : ''); }
    function esc(s) { return $('<div>').text(s === null || s === undefined ? '' : s).html(); }

    function resetDisplay() {
        lastRes = null;
        $('#detailWrap').empty();
        $('#summaryBody, #summaryFoot').empty();
        $('#reportHeaderInfo, #detailWrap, #summaryWrap, #viewSwitcher, #reportErrors, #btnPrint').addClass('d-none');
        $('#reportEmpty').removeClass('d-none');
    }

    function fillLocations(list, preselectAll) {
        let opts = '';
        list.forEach(function (l) { opts += '<option value="' + l.code + '"' + (preselectAll ? ' selected' : '') + '>' + l.code + ' — ' + esc(l.name) + '</option>'; });
        $('#repLocation').html(opts).trigger('change');
    }
    fillLocations(ALL_LOCATIONS, false);

    // ── STO opsional: tanpa STO -> semua gudang & tanggal wajib; dengan STO -> gudang milik STO ──
    $('#repStoCode').on('change', function () {
        let encId = $(this).val();
        resetDisplay();
        $('#dateReq, #dateHint').toggleClass('d-none', !!encId);
        if (!encId) { fillLocations(ALL_LOCATIONS, false); return; }
        $('#repLocation').prop('disabled', true).html('<option>Memuat...</option>').trigger('change');
        $.post("{{ route('stoReport.locations') }}", { config_id: encId }).done(function (rows) {
            fillLocations((rows || []).map(function (r) { return { code: r.location_code, name: r.location_name }; }), true);
        }).fail(function () {
            Swal.fire('Error', 'Gagal memuat daftar lokasi.', 'error');
            fillLocations([], false);
        }).always(function () { $('#repLocation').prop('disabled', false); });
    });

    $('#btnGenerate').on('click', function () {
        let encId = $('#repStoCode').val(), locs = $('#repLocation').val() || [], range = $('#repDate').val();
        if (!locs.length) { Swal.fire('Warning', 'Pilih minimal satu lokasi gudang.', 'warning'); return; }
        if (!encId && !range) { Swal.fire('Warning', 'Tanpa referensi STO, rentang tanggal wajib diisi.', 'warning'); return; }

        $(".loading-spinner-container").addClass("-show");
        $.post("{{ route('inventoryValuation.sto.data') }}", { config_id: encId, locations: locs, date_range: range })
        .done(function (res) {
            $(".loading-spinner-container").removeClass("-show");
            if (res.status !== 1) { Swal.fire('Ditolak', res.message || 'Gagal memuat report.', 'warning'); return; }
            render(res);
        }).fail(function (xhr) {
            $(".loading-spinner-container").removeClass("-show");
            Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan.', 'error');
        });
    });

    function drill(r, key, label, loc) {
        return '<a href="javascript:;" class="mv-drill" data-article="' + r.article_code + '" data-alt="' + esc(r.alt_code || '')
            + '" data-loc="' + loc + '" data-col="' + key + '" data-label="' + esc(label) + '" data-price="' + (r.unit_value || 0) + '">' + fmt(r[key]) + '</a>';
    }

    // satu tabel detail per lokasi (kolom debit/kredit beda per grup gudang)
    function buildLocationBlock(L) {
        let h = L.header, t = L.totals, loc = h.location_code;
        let dr = (L.columns && L.columns.in) || [], kr = (L.columns && L.columns.out) || [];

        let row1 = '<tr><th rowspan="2" class="col-no">No</th><th rowspan="2" class="col-alt">Alt. Code</th>'
            + '<th rowspan="2" class="col-desc">Article Desc</th><th rowspan="2">Supp</th><th rowspan="2">UoM</th>'
            + '<th rowspan="2" title="Harga rata-rata tertimbang qty, bukan harga transaksi per dokumen">Harga Satuan<br>(Rata-rata)</th><th rowspan="2">Saldo Awal</th>'
            + (dr.length ? '<th colspan="' + dr.length + '" class="bg-light-primary">DEBIT</th>' : '')
            + (kr.length ? '<th colspan="' + kr.length + '" class="bg-light-danger">KREDIT</th>' : '')
            + '<th rowspan="2">Saldo Akhir</th><th rowspan="2">Nilai Hasil STO</th><th rowspan="2">Selisih</th></tr>';
        let row2 = '<tr>';
        dr.forEach(function (c) { row2 += '<th class="bg-light-primary">' + esc(c.label) + '</th>'; });
        kr.forEach(function (c) { row2 += '<th class="bg-light-danger">' + esc(c.label) + '</th>'; });
        row2 += '</tr>';

        let body = '';
        if (!L.rows.length) {
            body = '<tr><td colspan="' + (10 + dr.length + kr.length) + '" class="text-center text-muted py-1">Tidak ada data.</td></tr>';
        }
        L.rows.forEach(function (r) {
            let mv = '';
            dr.concat(kr).forEach(function (c) { mv += '<td class="text-right">' + drill(r, c.key, c.label, loc) + '</td>'; });
            body += '<tr><td class="text-center col-no">' + r.no + '</td><td class="col-alt">' + esc(r.alt_code || '-') + '</td>'
                + '<td class="col-desc">' + esc(r.article_desc || '-') + '</td><td>' + esc(r.supp || '-') + '</td>'
                + '<td class="text-center">' + esc(r.uom || '-')
                    + (r.uom_conv ? '<br><small class="text-muted">' + esc(r.uom_conv) + '</small>' : '') + '</td>'
                + '<td class="text-right">' + fmt(r.unit_value) + '</td>'
                + '<td class="text-right">' + drill(r, 'opening', 'Saldo Awal', loc) + '</td>' + mv
                + '<td class="text-right font-weight-bold">' + fmt(r.closing) + '</td>'
                + '<td class="text-right">' + fmt(r.valuation) + '</td>'
                + '<td class="text-right ' + diffCls(r.diff) + '">' + fmt(r.diff) + '</td></tr>';
        });

        let foot = '<td colspan="6" class="text-center">TOTAL</td><td>' + fmt(t.opening) + '</td>';
        dr.concat(kr).forEach(function (c) { foot += '<td>' + fmt(t[c.key]) + '</td>'; });
        foot += '<td>' + fmt(t.closing) + '</td><td>' + fmt(t.valuation) + '</td><td class="' + diffCls(t.diff) + '">' + fmt(t.diff) + '</td>';

        return '<h6 class="mt-1">' + esc(loc + ' — ' + h.location_name) + '</h6>'
            + '<div class="rt-scroll mb-2"><table class="table table-sm rt" style="font-size:.78rem;">'
            + '<thead class="text-center">' + row1 + row2 + '</thead><tbody>' + body + '</tbody>'
            + '<tfoot class="text-right"><tr>' + foot + '</tr></tfoot></table></div>';
    }

    function render(res) {
        lastRes = res;
        let locs = res.locations, h0 = locs[0].header;

        $('#detailWrap').html(locs.map(buildLocationBlock).join(''));

        // ── Summary: satu baris per lokasi + grand total ──
        let g = { n: 0, opening: 0, debit: 0, kredit: 0, closing: 0, valuation: 0, diff: 0 };
        let sb = '';
        locs.forEach(function (L) {
            let t = L.totals;
            sb += '<tr><td>' + esc(L.header.location_code + ' — ' + L.header.location_name) + '</td><td class="text-center">' + L.rows.length + '</td>'
                + '<td class="text-right">' + fmt(t.opening) + '</td><td class="text-right">' + fmt(t.debit) + '</td>'
                + '<td class="text-right">' + fmt(t.kredit) + '</td><td class="text-right font-weight-bold">' + fmt(t.closing) + '</td>'
                + '<td class="text-right">' + fmt(t.valuation) + '</td><td class="text-right ' + diffCls(t.diff) + '">' + fmt(t.diff) + '</td></tr>';
            g.n += L.rows.length;
            ['opening', 'debit', 'kredit', 'closing', 'valuation', 'diff'].forEach(function (k) { g[k] += parseFloat(t[k]) || 0; });
        });
        $('#summaryBody').html(sb);
        $('#summaryFoot').html('<tr class="font-weight-bold" style="background:#e9edf3;"><td class="text-center">TOTAL</td><td class="text-center">' + g.n + '</td>'
            + '<td class="text-right">' + fmt(g.opening) + '</td><td class="text-right">' + fmt(g.debit) + '</td>'
            + '<td class="text-right">' + fmt(g.kredit) + '</td><td class="text-right">' + fmt(g.closing) + '</td>'
            + '<td class="text-right">' + fmt(g.valuation) + '</td><td class="text-right ' + diffCls(g.diff) + '">' + fmt(g.diff) + '</td></tr>');

        $('#hSto').text(h0.sto_code);
        $('#hPeriode').text(h0.periode);
        $('#hRange').text(h0.date_from + ' s/d ' + h0.date_to);
        if (res.errors && res.errors.length) { $('#reportErrors').html(res.errors.map(esc).join('<br>')).removeClass('d-none'); }
        else { $('#reportErrors').addClass('d-none'); }

        $('#reportHeaderInfo, #viewSwitcher, #btnPrint').removeClass('d-none');
        $('#reportEmpty').addClass('d-none');
        setView('detail');
    }

    function setView(v) {
        $('.view-tab').removeClass('active').filter('[data-view="' + v + '"]').addClass('active');
        $('#detailWrap').toggleClass('d-none', v !== 'detail');
        $('#summaryWrap').toggleClass('d-none', v !== 'summary');
    }
    $('.view-tab').on('click', function () { setView($(this).data('view')); });

    // ── klik angka -> daftar dokumen (endpoint sama dgn STO Report), nilai = qty x harga satuan ──
    $(document).on('click', '.mv-drill', function () {
        let $el = $(this), price = parseFloat($el.data('price')) || 0, alt = $el.data('alt');
        let msg = function (cls, txt) { return '<tr><td colspan="5" class="text-center ' + cls + ' py-2">' + txt + '</td></tr>'; };
        $('#mvDetailTitle').text($el.data('label') + (alt ? ' — ' + alt : '') + ' (' + $el.data('loc') + ')');
        $('#mvDetailBody').html(msg('text-muted', 'Memuat...'));
        $('#mvDetailTotalQty, #mvDetailTotal').text('0.00');
        $('#movementDetailModal').modal('show');

        $.post("{{ route('stoReport.movementDetail') }}", {
            config_id: $('#repStoCode').val(), location_code: $el.data('loc'),
            article_code: $el.data('article'), column_key: $el.data('col'), date_range: $('#repDate').val()
        }).done(function (res) {
            if (!res || res.status !== 1) { $('#mvDetailBody').html(msg('text-danger', esc((res && res.message) || 'Gagal memuat data.'))); return; }
            if (!res.rows.length) { $('#mvDetailBody').html(msg('text-muted', 'Tidak ada baris pergerakan.')); return; }
            let body = '';
            res.rows.forEach(function (r) {
                let doc = r.link ? '<a href="' + r.link + '" target="_blank" rel="noopener">' + esc(r.doc_number || '-') + '</a>' : esc(r.doc_number || '-');
                body += '<tr><td>' + esc(r.date || '-') + '</td>'
                    + '<td>' + doc + ' <span class="text-muted" style="font-size:.7rem;">(' + esc(r.doc_type || '') + ')</span></td>'
                    + '<td class="text-right">' + fmt(r.qty) + '</td>'
                    + '<td class="text-right">' + fmt(price) + '</td>'
                    + '<td class="text-right">' + fmt(r.qty * price) + '</td></tr>';
            });
            $('#mvDetailBody').html(body);
            $('#mvDetailTotalQty').text(fmt(res.total));
            $('#mvDetailTotal').text(fmt(res.total * price));
        }).fail(function (xhr) {
            $('#mvDetailBody').html(msg('text-danger', esc((xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan.')));
        });
    });

    $('#btnReset').on('click', function () {
        $('#repStoCode').val('').trigger('change');
        $('#repDate').val('');
        resetDisplay();
    });

    $('#btnPrint').on('click', function () {
        let view = $('#summaryWrap').hasClass('d-none') ? $('#detailWrap').html() : $('#summaryWrap').html();
        let w = window.open('', '', 'width=1300,height=800');
        w.document.write('<html><head><title>Inventory Valuation</title><style>'
            + 'body{font-family:Arial,sans-serif;font-size:10px;padding:16px;}table{width:100%;border-collapse:collapse;margin-bottom:12px;}'
            + 'th,td{border:1px solid #555;padding:2px 4px;}thead{background:#ddd;}.text-right{text-align:right;}.text-center{text-align:center;}'
            + '</style></head><body><h3>Inventory Valuation</h3>' + $('#reportHeaderInfo').html() + view + '</body></html>');
        w.document.close(); w.focus();
        setTimeout(function () { w.print(); w.close(); }, 400);
    });
});
</script>
@endsection
