@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

{{-- ════════════════════════════════════════════════
     STYLE: sticky header + freeze kolom customer + zebra
     (pola sama seperti STO Report)
════════════════════════════════════════════════ --}}
<style>
#agingScroll {
    max-height: 68vh;
    overflow: auto;
    position: relative;
}
#agingTable {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    margin-bottom: 0;
}
#agingTable th, #agingTable td {
    box-sizing: border-box;
    border-right: 1px solid #e3e6ec;
    border-bottom: 1px solid #e3e6ec;
    padding: 4px 6px;
    white-space: nowrap;
}
#agingTable { border-top: 1px solid #e3e6ec; border-left: 1px solid #e3e6ec; }

#agingTable thead th {
    position: sticky;
    top: 0;
    z-index: 3;
    background: #eef2f7 !important;
    height: 34px;
    vertical-align: middle;
}

#agingTable .col-customer { position: sticky; left: 0; min-width: 220px; max-width: 220px;
    white-space: normal; background: inherit; z-index: 2; }
#agingTable thead th.col-customer { z-index: 5; background: #e4e9f1 !important; }

#agingTable tbody tr:nth-child(odd)  td { background: #ffffff !important; }
#agingTable tbody tr:nth-child(even) td { background: #f4f7fb !important; }
#agingTable tbody tr:hover td { background: #e8f1ff !important; }

#agingTable td.bucket-31, #agingTable th.bucket-31 { background: #fff3cd !important; }
#agingTable td.bucket-61, #agingTable th.bucket-61 { background: #ffe0b2 !important; }
#agingTable td.bucket-90, #agingTable th.bucket-90 { background: #f8d7da !important; }

#agingTable tfoot td {
    position: sticky;
    bottom: 0;
    background: #e9edf3 !important;
    z-index: 3;
    font-weight: bold;
    height: 34px;
    vertical-align: middle;
    border-top: 2px solid #c8cfda;
}
</style>

{{-- ════════════════════════════════════════════════
     FILTER
════════════════════════════════════════════════ --}}
<section id="aging-filter">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Filter AR Aging Report</h4>
            <div class="heading-elements">
                <ul class="list-inline mb-0">
                    <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                </ul>
            </div>
        </div>
        <div class="card-content collapse show">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="repCutoff">Per Tanggal (Cut-off) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control flatpickr-single" id="repCutoff"
                               placeholder="DD-MM-YYYY">
                        <small class="text-muted">Umur piutang dihitung terhadap tanggal ini.</small>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="repInvoiceDate">Rentang Tanggal Invoice
                            <small class="text-muted">(opsional)</small>
                        </label>
                        <input type="text" class="form-control flatpickr-range" id="repInvoiceDate"
                               placeholder="DD-MM-YYYY to DD-MM-YYYY">
                    </div>
                    <div class="form-group col-md-5">
                        <label for="repCustomer">Customer <small class="text-muted">(opsional, boleh lebih dari satu)</small></label>
                        <select class="form-control" id="repCustomer" multiple>
                            @foreach($customers as $c)
                                <option value="{{ $c->kode }}">{{ $c->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="btnGenerate">
                            <i data-feather="bar-chart-2" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Generate Report</span>
                        </button>
                        <button type="button" class="btn btn-light" id="btnReset">Reset</button>
                        <span class="float-right">
                            <button type="button" class="btn btn-outline-success d-none mr-50" id="btnExport">
                                <i data-feather="download" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Export</span>
                            </button>
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
     SUMMARY CARDS
════════════════════════════════════════════════ --}}
<section id="aging-summary" class="d-none">
    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar bg-light-primary p-50 mr-1" style="border-radius:8px;">
                        <i data-feather="users" class="font-medium-4 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumCustomerCount">0</h5>
                        <small class="text-muted">Customer Outstanding</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar bg-light-secondary p-50 mr-1" style="border-radius:8px;">
                        <i data-feather="dollar-sign" class="font-medium-4 text-secondary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumTotalPiutang">0</h5>
                        <small class="text-muted">Total Piutang</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar bg-light-danger p-50 mr-1" style="border-radius:8px;">
                        <i data-feather="alert-triangle" class="font-medium-4 text-danger"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumOverduePct">0%</h5>
                        <small class="text-muted">% Overdue &bull; <span id="sumOverdueValue">0</span></small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar bg-light-warning p-50 mr-1" style="border-radius:8px;">
                        <i data-feather="file-text" class="font-medium-4 text-warning"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumDraftBalance">0</h5>
                        <small class="text-muted">Info: Balance Invoice DRAFT (tidak masuk aging)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════
     REPORT TABLE
════════════════════════════════════════════════ --}}
<section id="aging-result">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Hasil AR Aging</h4>
        </div>
        <div class="card-body">

            <div id="agingHeaderInfo" class="mb-1 d-none" style="font-size:.85rem;">
                <strong>Per Tanggal:</strong> <span id="hCutoff">-</span>
                <hr class="mt-50">
            </div>

            <div id="agingEmpty" class="alert alert-warning">
                Pilih <strong>Per Tanggal (Cut-off)</strong>, lalu klik <strong>Generate Report</strong>.
            </div>

            <div class="d-none" id="agingScroll">
                <table class="table table-sm text-right" id="agingTable" style="font-size:.8rem;">
                    <thead class="text-center">
                        <tr>
                            <th class="col-customer text-left">Customer</th>
                            <th>Belum Jatuh Tempo</th>
                            <th>1 - 30 Hari</th>
                            <th class="bucket-31">31 - 60 Hari</th>
                            <th class="bucket-61">61 - 90 Hari</th>
                            <th class="bucket-90">&gt; 90 Hari</th>
                            <th>Total Overdue</th>
                            <th>Total Piutang</th>
                            <th>% Overdue</th>
                        </tr>
                    </thead>
                    <tbody id="agingBody"></tbody>
                    <tfoot>
                        <tr>
                            <td class="col-customer text-left">GRAND TOTAL</td>
                            <td id="tBelum">0</td>
                            <td id="tD1_30">0</td>
                            <td class="bucket-31" id="tD31_60">0</td>
                            <td class="bucket-61" id="tD61_90">0</td>
                            <td class="bucket-90" id="tD90plus">0</td>
                            <td id="tOverdue">0</td>
                            <td id="tTotal">0</td>
                            <td id="tPct">0%</td>
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

    $('#repCustomer').select2({ width: '100%', placeholder: 'Semua customer' });

    // Cut-off: single date, default hari ini
    initDatePicker(document.querySelector('#repCutoff'), {
        minDate: "01/01/2010",
        maxDate: "31/12/2030",
        dateFormat: "d-m-Y",
        defaultDate: new Date()
    });

    // Rentang tanggal invoice: opsional, range
    initDatePicker(document.querySelector('#repInvoiceDate'), {
        minDate: "01/01/2010",
        maxDate: "31/12/2030",
        dateFormat: "d-m-Y",
        mode: "range"
    });

    function fmt(v) {
        let n = parseFloat(v);
        if (isNaN(n)) return '0';
        return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function resetDisplay() {
        $('#agingBody').empty();
        $('#agingHeaderInfo').addClass('d-none');
        $('#agingScroll').addClass('d-none');
        $('#aging-summary').addClass('d-none');
        $('#btnPrint').addClass('d-none');
        $('#btnExport').addClass('d-none');
        $('#agingEmpty').removeClass('d-none');
    }

    $('#btnGenerate').on('click', function () {
        let cutoff = $('#repCutoff').val();
        if (!cutoff) {
            Swal.fire('Warning', 'Tanggal Cut-off wajib diisi.', 'warning');
            return;
        }

        $(".loading-spinner-container").addClass("-show");

        $.post("{{ route('arAging.data') }}", {
            cutoffDate       : cutoff,
            invoiceDateRange : $('#repInvoiceDate').val(),
            customer         : $('#repCustomer').val()
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

    function renderReport(res) {
        $('#hCutoff').text(res.cutoff);
        $('#agingHeaderInfo').removeClass('d-none');

        let body = '';
        if (!res.rows || res.rows.length === 0) {
            body = '<tr><td colspan="9" class="text-center text-muted py-1">Tidak ada piutang outstanding untuk filter ini.</td></tr>';
        } else {
            res.rows.forEach(function (r) {
                let pctCls = r.pct_overdue >= 50 ? 'text-danger' : (r.pct_overdue > 0 ? 'text-warning' : 'text-success');
                body += '<tr>'
                    + '<td class="col-customer text-left">' + r.customer_name + '</td>'
                    + '<td>' + fmt(r.belum_jatuh_tempo) + '</td>'
                    + '<td>' + fmt(r.d1_30) + '</td>'
                    + '<td class="bucket-31">' + fmt(r.d31_60) + '</td>'
                    + '<td class="bucket-61">' + fmt(r.d61_90) + '</td>'
                    + '<td class="bucket-90">' + fmt(r.d90plus) + '</td>'
                    + '<td>' + fmt(r.total_overdue) + '</td>'
                    + '<td class="font-weight-bold">' + fmt(r.total_piutang) + '</td>'
                    + '<td class="' + pctCls + ' font-weight-bold">' + r.pct_overdue.toFixed(1) + '%</td>'
                    + '</tr>';
            });
        }
        $('#agingBody').html(body);

        let g = res.grand;
        $('#tBelum').text(fmt(g.belum_jatuh_tempo));
        $('#tD1_30').text(fmt(g.d1_30));
        $('#tD31_60').text(fmt(g.d31_60));
        $('#tD61_90').text(fmt(g.d61_90));
        $('#tD90plus').text(fmt(g.d90plus));
        $('#tOverdue').text(fmt(g.total_overdue));
        $('#tTotal').text(fmt(g.total_piutang));
        $('#tPct').text(g.pct_overdue.toFixed(1) + '%');

        $('#sumCustomerCount').text(res.rows.length);
        $('#sumTotalPiutang').text(fmt(g.total_piutang));
        $('#sumOverduePct').text(g.pct_overdue.toFixed(1) + '%');
        $('#sumOverdueValue').text(fmt(g.total_overdue));
        $('#sumDraftBalance').text(fmt(res.draftBalance));
        $('#aging-summary').removeClass('d-none');

        $('#agingEmpty').addClass('d-none');
        $('#agingScroll').removeClass('d-none');
        $('#btnPrint').removeClass('d-none');
        $('#btnExport').removeClass('d-none');

        if (typeof feather !== 'undefined') feather.replace();
    }

    $('#btnReset').on('click', function () {
        $('#repInvoiceDate').val('');
        $('#repCustomer').val(null).trigger('change');
        resetDisplay();
    });

    $('#btnPrint').on('click', function () {
        let info  = $('#agingHeaderInfo').html();
        let cards = $('#aging-summary').html();
        let table = $('#agingTable').prop('outerHTML');
        let w = window.open('', '', 'width=1200,height=800');
        w.document.write('<html><head><title>AR Aging Report</title>');
        w.document.write('<style>'
            + 'body{font-family:Arial,sans-serif;font-size:10px;padding:16px;}'
            + 'table{width:100%;border-collapse:collapse;margin-top:10px;}'
            + 'th,td{border:1px solid #555;padding:2px 4px;}'
            + 'thead{background:#ddd;}'
            + 'tbody tr:nth-child(even) td{background:#f4f7fb;-webkit-print-color-adjust:exact;}'
            + '.text-right{text-align:right;}.text-left{text-align:left;}.text-center{text-align:center;}'
            + '.font-weight-bold{font-weight:bold;}'
            + '.text-danger{color:#ea5455;}.text-warning{color:#ff9f43;}.text-success{color:#28c76f;}'
            + '</style></head><body>');
        w.document.write('<h3 style="margin-bottom:4px;">AR Aging Report</h3>');
        w.document.write('<div>' + info + '</div>');
        w.document.write('<div style="margin-bottom:6px;">' + cards + '</div>');
        w.document.write(table);
        w.document.write('</body></html>');
        w.document.close();
        w.focus();
        setTimeout(function () { w.print(); w.close(); }, 400);
    });

    $('#btnExport').on('click', function () {
        let $f = $('<form>', { method: 'POST', action: "{{ route('arAging.export') }}" });
        $f.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
        $f.append($('<input>', { type: 'hidden', name: 'cutoffDate', value: $('#repCutoff').val() }));
        $f.append($('<input>', { type: 'hidden', name: 'invoiceDateRange', value: $('#repInvoiceDate').val() }));
        ($('#repCustomer').val() || []).forEach(function (c) {
            $f.append($('<input>', { type: 'hidden', name: 'customer[]', value: c }));
        });
        $('body').append($f);
        $f.submit();
        $f.remove();
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
});
</script>
@endsection