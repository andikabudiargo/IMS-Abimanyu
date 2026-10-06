@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

{{-- Tabel & style disamakan dengan STO Report (sticky header + freeze 3 kolom kiri + zebra) --}}
<style>
#reportScroll { max-height: 68vh; overflow: auto; position: relative; }
#reportTable { border-collapse: separate; border-spacing: 0; width: 100%; margin-bottom: 0; border-top: 1px solid #e3e6ec; border-left: 1px solid #e3e6ec; }
#reportTable th, #reportTable td { box-sizing: border-box; border-right: 1px solid #e3e6ec; border-bottom: 1px solid #e3e6ec; padding: 4px 6px; white-space: nowrap; }
#reportTable thead th { position: sticky; z-index: 3; background: #eef2f7 !important; height: 30px; vertical-align: middle; }
#reportTable thead tr:first-child th  { top: 0; }
#reportTable thead tr:nth-child(2) th { top: 30px; }
#reportTable thead th.bg-light-primary { background: #e3ecfb !important; color: #4b6cb7; }
#reportTable thead th.bg-light-danger  { background: #fbe4e4 !important; color: #b74b4b; }
#reportTable .col-no, #reportTable .col-alt, #reportTable .col-desc { position: sticky; }
#reportTable .col-no   { left: 0;     min-width: 42px;  max-width: 42px;  }
#reportTable .col-alt  { left: 42px;  min-width: 100px; max-width: 100px; }
#reportTable .col-desc { left: 142px; min-width: 220px; max-width: 220px; white-space: normal; }
#reportTable tbody td.col-no, #reportTable tbody td.col-alt, #reportTable tbody td.col-desc { z-index: 2; }
#reportTable thead th.col-no, #reportTable thead th.col-alt, #reportTable thead th.col-desc { z-index: 5; background: #e4e9f1 !important; }
#reportTable tbody tr:nth-child(odd)  td { background: #ffffff !important; }
#reportTable tbody tr:nth-child(even) td { background: #f4f7fb !important; }
#reportTable tbody tr:hover td { background: #e8f1ff !important; }
#reportTable tfoot td { position: sticky; bottom: 0; background: #e9edf3 !important; z-index: 3; font-weight: bold; height: 34px; vertical-align: middle; padding: 6px 6px; border-top: 2px solid #c8cfda; }
</style>

<section>
    <div class="card">
        <div class="card-header"><h4 class="card-title">Filter Inventory Valuation</h4></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="repStoCode">STO Code <span class="text-danger">*</span></label>
                    <select class="form-control" id="repStoCode">
                        <option value="">-- Pilih STO --</option>
                        @foreach($stoList as $s)
                            <option value="{{ $s->enc_id }}">{{ $s->sto_code }} ({{ $s->periode }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="repLocation">Lokasi Gudang <span class="text-danger">*</span></label>
                    <select class="form-control" id="repLocation" disabled>
                        <option value="">-- Pilih STO dulu --</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="repDate">Rentang Tanggal <small class="text-muted">(opsional, default ikut periode STO)</small></label>
                    <input type="text" class="form-control flatpickr-range" id="repDate" placeholder="DD-MM-YYYY to DD-MM-YYYY">
                </div>
            </div>
            <button type="button" class="btn btn-primary" id="btnGenerate" disabled>Generate Report</button>
            <button type="button" class="btn btn-light" id="btnReset">Reset</button>
            <button type="button" class="btn btn-outline-secondary d-none float-right" id="btnPrint">Print</button>
        </div>
    </div>
</section>

<section>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Hasil Report</h4></div>
        <div class="card-body">
            <div id="reportHeaderInfo" class="mb-1 d-none">
                <div class="row" style="font-size:.85rem;">
                    <div class="col-md-6">
                        <strong>STO Code :</strong> <span id="hSto">-</span><br>
                        <strong>Lokasi   :</strong> <span id="hLoc">-</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Periode  :</strong> <span id="hPeriode">-</span><br>
                        <strong>Rentang  :</strong> <span id="hRange">-</span>
                    </div>
                </div>
                <hr class="mt-50">
            </div>
            <div id="reportEmpty" class="alert alert-warning">
                Pilih <strong>STO Code</strong> &amp; <strong>Lokasi</strong>, lalu klik <strong>Generate Report</strong>.
            </div>
            <div class="btn-group mb-1 d-none" role="group" id="viewSwitcher">
                <button type="button" class="btn btn-sm btn-outline-dark view-tab active" data-view="detail">Detail</button>
                <button type="button" class="btn btn-sm btn-outline-dark view-tab" data-view="summary">Summary</button>
            </div>
            <div class="d-none" id="reportScroll">
                <table class="table table-sm" id="reportTable" style="font-size:.78rem;">
                    <thead class="text-center" id="reportThead"></thead>
                    <tbody id="reportBody"></tbody>
                    <tfoot class="text-right" id="reportTfoot"></tfoot>
                </table>
            </div>
            <div class="d-none table-responsive" id="summaryWrap">
                <table class="table table-bordered table-sm" id="summaryTable" style="font-size:.82rem;">
                    <thead class="text-center" id="summaryThead"></thead>
                    <tbody id="summaryBody"></tbody>
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
                                <th class="text-right">Harga Satuan</th>
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
    $('#repStoCode, #repLocation').select2({ width: '100%' });
    initDatePicker(document.querySelector('#repDate'), {
        minDate: "01/01/2010", maxDate: "31/12/2030", dateFormat: "d-m-Y", mode: "range"
    });

    function fmt(v) {
        let n = parseFloat(v);
        if (v === null || v === undefined || isNaN(n)) return '-';
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function diffCls(v) { return v > 0 ? 'text-success' : (v < 0 ? 'text-danger' : ''); }

    function resetDisplay() {
        $('#reportBody').empty();
        $('#reportHeaderInfo, #reportScroll, #summaryWrap, #viewSwitcher, #btnPrint').addClass('d-none');
        $('#reportEmpty').removeClass('d-none');
    }

    $('#repStoCode').on('change', function () {
        let encId = $(this).val(), $loc = $('#repLocation');
        $loc.prop('disabled', true).html('<option value="">Memuat...</option>').trigger('change');
        resetDisplay();
        if (!encId) { $loc.html('<option value="">-- Pilih STO dulu --</option>').trigger('change'); return; }
        $.post("{{ route('stoReport.locations') }}", { config_id: encId }).done(function (rows) {
            if (!rows || !rows.length) { $loc.html('<option value="">Tidak ada lokasi didukung</option>').trigger('change'); return; }
            let opts = '<option value="">-- Pilih Lokasi --</option>';
            rows.forEach(function (r) { opts += '<option value="' + r.location_code + '">' + r.location_code + ' — ' + r.location_name + '</option>'; });
            $loc.html(opts).prop('disabled', false).trigger('change');
        }).fail(function () { Swal.fire('Error', 'Gagal memuat daftar lokasi.', 'error'); });
    });
    $('#repLocation').on('change', function () { $('#btnGenerate').prop('disabled', !$(this).val()); });

    $('#btnGenerate').on('click', function () {
        $(".loading-spinner-container").addClass("-show");
        $.post("{{ route('inventoryValuation.sto.data') }}", {
            config_id: $('#repStoCode').val(), location_code: $('#repLocation').val(), date_range: $('#repDate').val()
        }).done(function (res) {
            $(".loading-spinner-container").removeClass("-show");
            if (res.status !== 1) { Swal.fire('Ditolak', res.message || 'Gagal memuat report.', 'warning'); return; }
            render(res);
        }).fail(function (xhr) {
            $(".loading-spinner-container").removeClass("-show");
            Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan.', 'error');
        });
    });

    function render(res) {
        let h = res.header, t = res.totals;
        let dr = (res.columns && res.columns.in) || [], kr = (res.columns && res.columns.out) || [];

        let row1 = '<tr><th rowspan="2" class="col-no">No</th><th rowspan="2" class="col-alt">Alt. Code</th>'
            + '<th rowspan="2" class="col-desc">Article Desc</th><th rowspan="2">Supp</th><th rowspan="2">UoM</th>'
            + '<th rowspan="2">Harga Satuan</th><th rowspan="2">Saldo Awal</th>'
            + (dr.length ? '<th colspan="' + dr.length + '" class="bg-light-primary">DEBIT</th>' : '')
            + (kr.length ? '<th colspan="' + kr.length + '" class="bg-light-danger">KREDIT</th>' : '')
            + '<th rowspan="2">Saldo Akhir</th><th rowspan="2">Nilai Hasil STO</th><th rowspan="2">Selisih</th></tr>';
        let row2 = '<tr>';
        dr.forEach(function (c) { row2 += '<th class="bg-light-primary">' + c.label + '</th>'; });
        kr.forEach(function (c) { row2 += '<th class="bg-light-danger">' + c.label + '</th>'; });
        $('#reportThead').html(row1 + row2 + '</tr>');

        let body = '';
        if (!res.rows.length) {
            body = '<tr><td colspan="' + (10 + dr.length + kr.length) + '" class="text-center text-muted py-1">Tidak ada data untuk lokasi/periode ini.</td></tr>';
        }
        let drill = function (r, key, label) {
            return '<a href="javascript:;" class="mv-drill" data-article="' + r.article_code + '" data-alt="' + (r.alt_code || '')
                + '" data-col="' + key + '" data-label="' + label + '" data-price="' + (r.unit_value || 0) + '">' + fmt(r[key]) + '</a>';
        };
        res.rows.forEach(function (r) {
            let mv = '';
            dr.concat(kr).forEach(function (c) { mv += '<td class="text-right">' + drill(r, c.key, c.label) + '</td>'; });
            body += '<tr><td class="text-center col-no">' + r.no + '</td><td class="col-alt">' + (r.alt_code || '-') + '</td>'
                + '<td class="col-desc">' + (r.article_desc || '-') + '</td><td>' + (r.supp || '-') + '</td>'
                + '<td class="text-center">' + (r.uom || '-') + '</td>'
                + '<td class="text-right">' + fmt(r.unit_value) + '</td>'
                + '<td class="text-right">' + drill(r, 'opening', 'Saldo Awal') + '</td>' + mv
                + '<td class="text-right font-weight-bold">' + fmt(r.closing) + '</td>'
                + '<td class="text-right">' + fmt(r.valuation) + '</td>'
                + '<td class="text-right ' + diffCls(r.diff) + '">' + fmt(r.diff) + '</td></tr>';
        });
        $('#reportBody').html(body);

        let foot = '<td colspan="6" class="text-center">TOTAL</td><td>' + fmt(t.opening) + '</td>';
        dr.concat(kr).forEach(function (c) { foot += '<td>' + fmt(t[c.key]) + '</td>'; });
        foot += '<td>' + fmt(t.closing) + '</td><td>' + fmt(t.valuation) + '</td><td class="' + diffCls(t.diff) + '">' + fmt(t.diff) + '</td>';
        $('#reportTfoot').html('<tr>' + foot + '</tr>');

        // ── Summary: 1 baris total per lokasi (tanpa rincian artikel), kolom sama dgn Detail ──
        let sh = '<tr><th rowspan="2">Lokasi</th><th rowspan="2">Artikel</th><th rowspan="2">Saldo Awal</th>'
            + (dr.length ? '<th colspan="' + dr.length + '" class="bg-light-primary">DEBIT</th>' : '')
            + (kr.length ? '<th colspan="' + kr.length + '" class="bg-light-danger">KREDIT</th>' : '')
            + '<th rowspan="2">Saldo Akhir</th><th rowspan="2">Nilai Hasil STO</th><th rowspan="2">Selisih</th></tr><tr>';
        dr.forEach(function (c) { sh += '<th class="bg-light-primary">' + c.label + '</th>'; });
        kr.forEach(function (c) { sh += '<th class="bg-light-danger">' + c.label + '</th>'; });
        $('#summaryThead').html(sh + '</tr>');
        let sb = '<tr><td>' + h.location_code + ' — ' + h.location_name + '</td><td class="text-center">' + res.rows.length + '</td>'
            + '<td class="text-right">' + fmt(t.opening) + '</td>';
        dr.concat(kr).forEach(function (c) { sb += '<td class="text-right">' + fmt(t[c.key]) + '</td>'; });
        sb += '<td class="text-right font-weight-bold">' + fmt(t.closing) + '</td><td class="text-right">' + fmt(t.valuation) + '</td>'
            + '<td class="text-right ' + diffCls(t.diff) + '">' + fmt(t.diff) + '</td></tr>';
        $('#summaryBody').html(sb);

        $('#viewSwitcher').removeClass('d-none');
        setView('detail');

        $('#hSto').text(h.sto_code);
        $('#hLoc').text(h.location_code + ' — ' + h.location_name);
        $('#hPeriode').text(h.periode);
        $('#hRange').text(h.date_from + ' s/d ' + h.date_to);
        $('#reportHeaderInfo, #btnPrint').removeClass('d-none');
        $('#reportEmpty').addClass('d-none');
    }

    function setView(v) {
        $('.view-tab').removeClass('active').filter('[data-view="' + v + '"]').addClass('active');
        $('#reportScroll').toggleClass('d-none', v !== 'detail');
        $('#summaryWrap').toggleClass('d-none', v !== 'summary');
    }
    $('.view-tab').on('click', function () { setView($(this).data('view')); });

    // ── klik angka -> daftar dokumen (endpoint sama dgn STO Report), nilai = qty x harga satuan ──
    $(document).on('click', '.mv-drill', function () {
        let $el = $(this), price = parseFloat($el.data('price')) || 0, alt = $el.data('alt');
        let msg = function (cls, txt) { return '<tr><td colspan="5" class="text-center ' + cls + ' py-2">' + txt + '</td></tr>'; };
        $('#mvDetailTitle').text($el.data('label') + (alt ? ' — ' + alt : ''));
        $('#mvDetailBody').html(msg('text-muted', 'Memuat...'));
        $('#mvDetailTotalQty, #mvDetailTotal').text('0.00');
        $('#movementDetailModal').modal('show');

        $.post("{{ route('stoReport.movementDetail') }}", {
            config_id: $('#repStoCode').val(), location_code: $('#repLocation').val(),
            article_code: $el.data('article'), column_key: $el.data('col'), date_range: $('#repDate').val()
        }).done(function (res) {
            if (!res || res.status !== 1) { $('#mvDetailBody').html(msg('text-danger', (res && res.message) || 'Gagal memuat data.')); return; }
            if (!res.rows.length) { $('#mvDetailBody').html(msg('text-muted', 'Tidak ada baris pergerakan.')); return; }
            let body = '';
            res.rows.forEach(function (r) {
                let doc = r.link ? '<a href="' + r.link + '" target="_blank" rel="noopener">' + (r.doc_number || '-') + '</a>' : (r.doc_number || '-');
                body += '<tr><td>' + (r.date || '-') + '</td>'
                    + '<td>' + doc + ' <span class="text-muted" style="font-size:.7rem;">(' + (r.doc_type || '') + ')</span></td>'
                    + '<td class="text-right">' + fmt(r.qty) + '</td>'
                    + '<td class="text-right">' + fmt(price) + '</td>'
                    + '<td class="text-right">' + fmt(r.qty * price) + '</td></tr>';
            });
            $('#mvDetailBody').html(body);
            $('#mvDetailTotalQty').text(fmt(res.total));
            $('#mvDetailTotal').text(fmt(res.total * price));
        }).fail(function (xhr) {
            $('#mvDetailBody').html(msg('text-danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan.'));
        });
    });

    $('#btnReset').on('click', function () {
        $('#repStoCode').val('').trigger('change');
        $('#repDate').val('');
        resetDisplay();
    });

    $('#btnPrint').on('click', function () {
        let w = window.open('', '', 'width=1300,height=800');
        w.document.write('<html><head><title>Inventory Valuation</title><style>'
            + 'body{font-family:Arial,sans-serif;font-size:10px;padding:16px;}table{width:100%;border-collapse:collapse;}'
            + 'th,td{border:1px solid #555;padding:2px 4px;}thead{background:#ddd;}.text-right{text-align:right;}.text-center{text-align:center;}'
            + '</style></head><body><h3>Inventory Valuation</h3>' + $('#reportHeaderInfo').html() + $('#reportTable').prop('outerHTML') + '</body></html>');
        w.document.close(); w.focus();
        setTimeout(function () { w.print(); w.close(); }, 400);
    });
});
</script>
@endsection
