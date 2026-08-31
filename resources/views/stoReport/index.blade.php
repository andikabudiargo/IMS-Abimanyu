@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

{{-- ════════════════════════════════════════════════
     FILTER
════════════════════════════════════════════════ --}}
<section id="sto-report-filter">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Filter STO Report</h4>
            <div class="heading-elements">
                <ul class="list-inline mb-0">
                    <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                </ul>
            </div>
        </div>
        <div class="card-content collapse show">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="repStoCode">STO Code <span class="text-danger">*</span></label>
                        <select class="form-control" id="repStoCode">
                            <option value="">-- Pilih STO --</option>
                            @foreach($stoList as $s)
                                <option value="{{ $s->enc_id }}" data-periode="{{ $s->periode }}">
                                    {{ $s->sto_code }} ({{ $s->periode }})
                                </option>
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
                        <label for="repDate">Rentang Tanggal
                            <small class="text-muted">(opsional, default ikut periode STO)</small>
                        </label>
                        <input type="text" class="form-control flatpickr-range" id="repDate"
                               placeholder="DD-MM-YYYY to DD-MM-YYYY">
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="btnGenerate" disabled>
                            <i data-feather="bar-chart-2" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Generate Report</span>
                        </button>
                        <button type="button" class="btn btn-light" id="btnReset">Reset</button>
                        <span class="float-right">
                            <button type="button" class="btn btn-outline-secondary d-none" id="btnPrint">
                                <i data-feather="printer" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle">Print</span>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════
     SUMMARY AKURASI (muncul setelah generate)
════════════════════════════════════════════════ --}}
<section id="sto-report-summary" class="d-none">
    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar bg-light-primary p-50 mr-1" style="border-radius:8px;">
                        <i data-feather="layers" class="font-medium-4 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumTotal">0</h5>
                        <small class="text-muted">Total Artikel</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar bg-light-success p-50 mr-1" style="border-radius:8px;">
                        <i data-feather="check-circle" class="font-medium-4 text-success"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumAccurate">0</h5>
                        <small class="text-muted">Akurat (dapat poin)</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar bg-light-danger p-50 mr-1" style="border-radius:8px;">
                        <i data-feather="x-circle" class="font-medium-4 text-danger"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumNot">0</h5>
                        <small class="text-muted">Tidak Akurat</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar p-50 mr-1" id="sumAccuracyAvatar" style="border-radius:8px;background:#eee;">
                        <i data-feather="percent" class="font-medium-4" id="sumAccuracyIcon"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumAccuracyPct">0.00%</h5>
                        <small class="text-muted">Akurasi &bull; Target: <span id="sumTarget">98%</span></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════
     REPORT TABLE
════════════════════════════════════════════════ --}}
<section id="sto-report-result">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Hasil Report</h4>
        </div>
        <div class="card-body">

            {{-- info header --}}
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

            <div class="table-responsive d-none" id="reportTableWrap">
                <table class="table table-bordered table-sm" id="reportTable" style="font-size:.78rem;">
                    <thead class="text-center align-middle">
                        <tr>
                            <th rowspan="2" style="vertical-align:middle;white-space:nowrap;">No</th>
                            <th rowspan="2" style="vertical-align:middle;white-space:nowrap;">Alt. Code</th>
                            <th rowspan="2" style="vertical-align:middle;">Article Desc</th>
                            <th rowspan="2" style="vertical-align:middle;">Supp</th>
                            <th rowspan="2" style="vertical-align:middle;">UoM</th>
                            <th rowspan="2" style="vertical-align:middle;white-space:nowrap;">Opening</th>
                            {{-- IN --}}
                            <th colspan="3" class="bg-light-primary">IN</th>
                            {{-- OUT --}}
                            <th colspan="3" class="bg-light-danger">OUT</th>
                            {{-- Balance & STO --}}
                            <th rowspan="2" style="vertical-align:middle;white-space:nowrap;">Balance</th>
                            <th rowspan="2" style="vertical-align:middle;white-space:nowrap;">Hasil STO</th>
                            <th rowspan="2" style="vertical-align:middle;white-space:nowrap;">Variance</th>
                            <th rowspan="2" style="vertical-align:middle;">Status</th>
                            <th rowspan="2" style="vertical-align:middle;">Akurasi</th>
                        </tr>
                        <tr>
                            <th class="bg-light-primary" style="white-space:nowrap;">Receiving</th>
                            <th class="bg-light-primary" style="white-space:nowrap;">Return Transfer</th>
                            <th class="bg-light-primary" style="white-space:nowrap;">Replace Supplier</th>
                            <th class="bg-light-danger" style="white-space:nowrap;">Supply Transfer</th>
                            <th class="bg-light-danger" style="white-space:nowrap;">Return Supplier</th>
                            <th class="bg-light-danger" style="white-space:nowrap;">DN Umum</th>
                        </tr>
                    </thead>
                    <tbody id="reportBody"></tbody>
                    <tfoot class="font-weight-bold text-right" style="background:#f8f8f8;">
                        <tr>
                            <td colspan="5" class="text-center">TOTAL</td>
                            <td id="tOpening">-</td>
                            <td id="tInRcv">-</td>
                            <td id="tInRet">-</td>
                            <td id="tInRep">-</td>
                            <td id="tOutSup">-</td>
                            <td id="tOutRet">-</td>
                            <td id="tOutDn">-</td>
                            <td id="tBalance">-</td>
                            <td id="tSto">-</td>
                            <td id="tVariance">-</td>
                            <td colspan="2" class="text-center">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>
</section>

@endsection

@section('scripts')
<script type="text/javascript">
$(document).ready(function () {

    $('#repStoCode, #repLocation').select2({ width: '100%' });

    initDatePicker(document.querySelector('#repDate'), {
        minDate: "01/01/2010",
        maxDate: "31/12/2030",
        dateFormat: "d-m-Y",
        mode: "range"
    });

    // ── helpers ──
    function fmt(v, dash) {
        if (v === null || v === undefined) return dash ? '-' : '0.00';
        let n = parseFloat(v);
        if (isNaN(n)) return dash ? '-' : '0.00';
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function statusBadge(s) {
        let map = {
            'MATCH'      : ['badge-success',   'MATCH'],
            'RECOUNT'    : ['badge-warning',   'RECOUNT'],
            'NOT MATCH'  : ['badge-danger',    'NOT MATCH'],
            'INCOMPLETE' : ['badge-secondary', 'INCOMPLETE'],
        };
        let d = map[s] || ['badge-secondary', s];
        return '<span class="badge ' + d[0] + '">' + d[1] + '</span>';
    }

    function accuracyBadge(accurate, hasData) {
        if (!hasData) return '<span class="badge badge-light-secondary">N/A</span>';
        return accurate
            ? '<span class="badge badge-light-success"><i data-feather="check" style="width:10px;height:10px;"></i> Akurat</span>'
            : '<span class="badge badge-light-danger"><i data-feather="x" style="width:10px;height:10px;"></i> Tidak</span>';
    }

    // ── pilih STO → load lokasi ──
    $('#repStoCode').on('change', function () {
        let encId = $(this).val();
        let $loc  = $('#repLocation');

        $loc.prop('disabled', true).html('<option value="">Memuat...</option>').trigger('change');
        $('#btnGenerate').prop('disabled', true);
        resetDisplay();

        if (!encId) {
            $loc.html('<option value="">-- Pilih STO dulu --</option>').trigger('change');
            return;
        }

        $.post("{{ route('stoReport.locations') }}", { config_id: encId })
            .done(function (rows) {
                if (!rows || rows.length === 0) {
                    $loc.html('<option value="">Tidak ada lokasi didukung</option>').prop('disabled', true).trigger('change');
                    return;
                }
                let opts = '<option value="">-- Pilih Lokasi --</option>';
                rows.forEach(function (r) {
                    opts += '<option value="' + r.location_code + '"'
                          + ' data-sto-date="' + (r.sto_date || '') + '"'
                          + ' data-plan="' + (r.target_plan_loc || 98) + '">'
                          + r.location_code + ' — ' + r.location_name + '</option>';
                });
                $loc.html(opts).prop('disabled', false).trigger('change');
            })
            .fail(function () {
                Swal.fire('Error', 'Gagal memuat daftar lokasi.', 'error');
                $loc.html('<option value="">-- Pilih Lokasi --</option>').prop('disabled', true).trigger('change');
            });
    });

    $('#repLocation').on('change', function () {
        $('#btnGenerate').prop('disabled', !$(this).val());
    });

    // ── generate ──
    $('#btnGenerate').on('click', function () {
        let encId = $('#repStoCode').val();
        let loc   = $('#repLocation').val();

        if (!encId || !loc) {
            Swal.fire('Warning', 'STO Code & Lokasi wajib dipilih.', 'warning');
            return;
        }

        $(".loading-spinner-container").addClass("-show");

        $.post("{{ route('stoReport.data') }}", {
            config_id     : encId,
            location_code : loc,
            date_range    : $('#repDate').val()
        })
        .done(function (res) {
            $(".loading-spinner-container").removeClass("-show");

            if (res.status !== 1) {
                Swal.fire('Ditolak', res.message || 'Gagal memuat report.', 'warning');
                return;
            }
            renderReport(res);
        })
        .fail(function (xhr) {
            $(".loading-spinner-container").removeClass("-show");
            let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan.';
            Swal.fire('Error', msg, 'error');
        });
    });

    // ── render ──
    function renderReport(res) {
        let h = res.header;
        let s = res.summary;
        let t = res.totals;

        // info header
        $('#hSto').text(h.sto_code);
        $('#hLoc').text(h.location_code + ' — ' + h.location_name);
        $('#hPeriode').text(h.periode);
        $('#hRange').text(h.date_from + ' s/d ' + h.date_to);
        $('#reportHeaderInfo').removeClass('d-none');

        // summary cards
        $('#sumTotal').text(s.total_artikel);
        $('#sumAccurate').text(s.total_accurate);
        $('#sumNot').text(s.total_not);
        $('#sumAccuracyPct').text(s.accuracy_pct.toFixed(2) + '%');
        $('#sumTarget').text(s.target_plan.toFixed(2) + '%');

        let metTarget = s.is_meet_target;
        $('#sumAccuracyAvatar')
            .removeClass('bg-light-success bg-light-danger')
            .addClass(metTarget ? 'bg-light-success' : 'bg-light-danger');
        $('#sumAccuracyIcon')
            .removeClass('text-success text-danger')
            .addClass(metTarget ? 'text-success' : 'text-danger');

        $('#sto-report-summary').removeClass('d-none');

        // tabel body
        let body = '';
        if (!res.rows || res.rows.length === 0) {
            body = '<tr><td colspan="17" class="text-center text-muted py-1">Tidak ada data untuk lokasi/periode ini.</td></tr>';
        } else {
            res.rows.forEach(function (r) {
                let varCls  = '';
                let varVal  = '-';
                if (r.variance !== null) {
                    varVal  = fmt(r.variance);
                    varCls  = r.variance > 0 ? 'text-success' : (r.variance < 0 ? 'text-danger' : '');
                }

                body += '<tr>'
                    + '<td class="text-center">' + r.no + '</td>'
                    + '<td style="white-space:nowrap;">' + (r.alt_code || '-') + '</td>'
                    + '<td>' + (r.article_desc || '-') + '</td>'
                    + '<td>' + (r.supp || '-') + '</td>'
                    + '<td class="text-center">' + (r.uom || '-') + '</td>'
                    + '<td class="text-right">' + fmt(r.opening) + '</td>'
                    + '<td class="text-right">' + fmt(r.in_receiving) + '</td>'
                    + '<td class="text-right">' + fmt(r.in_return_transfer) + '</td>'
                    + '<td class="text-right">' + fmt(r.in_replace_supplier) + '</td>'
                    + '<td class="text-right">' + fmt(r.out_supply_transfer) + '</td>'
                    + '<td class="text-right">' + fmt(r.out_return_supplier) + '</td>'
                    + '<td class="text-right">' + fmt(r.out_dn_umum) + '</td>'
                    + '<td class="text-right font-weight-bold">' + fmt(r.closing) + '</td>'
                    + '<td class="text-right">' + (r.qty_sto !== null ? fmt(r.qty_sto) : '<span class="text-muted">-</span>') + '</td>'
                    + '<td class="text-right ' + varCls + '">' + varVal + '</td>'
                    + '<td class="text-center">' + statusBadge(r.sto_status) + '</td>'
                    + '<td class="text-center">' + accuracyBadge(r.accurate, r.qty_sto !== null) + '</td>'
                    + '</tr>';
            });
        }
        $('#reportBody').html(body);

        // footer totals
        $('#tOpening').text(fmt(t.opening));
        $('#tInRcv').text(fmt(t.in_receiving));
        $('#tInRet').text(fmt(t.in_return_transfer));
        $('#tInRep').text(fmt(t.in_replace_supplier));
        $('#tOutSup').text(fmt(t.out_supply_transfer));
        $('#tOutRet').text(fmt(t.out_return_supplier));
        $('#tOutDn').text(fmt(t.out_dn_umum));
        $('#tBalance').text(fmt(t.closing));
        $('#tSto').text(t.qty_sto !== null ? fmt(t.qty_sto) : '-');
        let tvc = t.variance !== null && t.variance < 0 ? 'text-danger' : (t.variance > 0 ? 'text-success' : '');
        $('#tVariance').removeClass('text-danger text-success').addClass(tvc).text(t.variance !== null ? fmt(t.variance) : '-');

        $('#reportEmpty').addClass('d-none');
        $('#reportTableWrap').removeClass('d-none');
        $('#btnPrint').removeClass('d-none');

        if (typeof feather !== 'undefined') feather.replace();
    }

    // ── reset ──
    function resetDisplay() {
        $('#reportBody').empty();
        $('#reportHeaderInfo').addClass('d-none');
        $('#reportTableWrap').addClass('d-none');
        $('#btnPrint').addClass('d-none');
        $('#reportEmpty').removeClass('d-none');
        $('#sto-report-summary').addClass('d-none');
    }

    $('#btnReset').on('click', function () {
        $('#repStoCode').val('').trigger('change');
        $('#repLocation').html('<option value="">-- Pilih STO dulu --</option>').prop('disabled', true).trigger('change');
        $('#repDate').val('');
        $('#btnGenerate').prop('disabled', true);
        resetDisplay();
    });

    // ── print ──
    $('#btnPrint').on('click', function () {
        let info  = $('#reportHeaderInfo').html();
        let cards = $('#sto-report-summary').html();
        let table = $('#reportTable').prop('outerHTML');
        let w = window.open('', '', 'width=1200,height=800');
        w.document.write('<html><head><title>STO Report</title>');
        w.document.write('<style>'
            + 'body{font-family:Arial,sans-serif;font-size:10px;padding:16px;}'
            + 'table{width:100%;border-collapse:collapse;margin-top:10px;}'
            + 'th,td{border:1px solid #555;padding:2px 4px;}'
            + 'thead{background:#ddd;}'
            + '.text-right{text-align:right;}.text-center{text-align:center;}'
            + '.badge{padding:1px 4px;border-radius:3px;font-size:9px;}'
            + '.badge-success{background:#28c76f;color:#fff;}'
            + '.badge-warning{background:#ff9f43;color:#fff;}'
            + '.badge-danger{background:#ea5455;color:#fff;}'
            + '.badge-secondary{background:#82868b;color:#fff;}'
            + '.badge-light-success{background:#d4edda;color:#155724;}'
            + '.badge-light-danger{background:#f8d7da;color:#721c24;}'
            + '.badge-light-secondary{background:#e2e3e5;color:#383d41;}'
            + '.bg-light-primary{background:#e8f0fe;}'
            + '.bg-light-danger{background:#fde8e8;}'
            + 'hr{margin:6px 0;} .font-weight-bold{font-weight:bold;}'
            + '.text-success{color:#28c76f;}.text-danger{color:#ea5455;}'
            + '</style></head><body>');
        w.document.write('<h3 style="margin-bottom:4px;">STO Report</h3>');
        w.document.write('<div>' + info + '</div>');
        w.document.write('<div style="margin-bottom:6px;">' + cards + '</div>');
        w.document.write(table);
        w.document.write('</body></html>');
        w.document.close();
        w.focus();
        setTimeout(function () { w.print(); w.close(); }, 400);
    });
});

$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection