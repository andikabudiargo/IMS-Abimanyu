@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

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

#agingTable .col-supplier { position: sticky; left: 0; min-width: 220px; max-width: 220px;
    white-space: normal; background: inherit; z-index: 2; }
#agingTable thead th.col-supplier { z-index: 5; background: #e4e9f1 !important; }

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

#agingTable .col-no { position: sticky; left: 0; min-width: 40px; max-width: 40px;
    text-align: center; background: inherit; z-index: 2; }
#agingTable thead th.col-no { z-index: 5; background: #e4e9f1 !important; }
#agingTable .col-supplier { left: 40px; }
#agingTable thead th.col-supplier { left: 40px; }

.aging-clickable { cursor: pointer; text-decoration: underline dotted; }
.aging-clickable:hover { background: #d7e8ff !important; }
</style>

<section id="aging-filter">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Filter AP Aging Report</h4>
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
                        <label for="repCutoff">Per Tanggal (Cut-off) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control flatpickr-single" id="repCutoff"
                               placeholder="DD-MM-YYYY">
                        <small class="text-muted">Umur hutang dihitung terhadap tanggal ini.</small>
                    </div>
                    <div class="form-group col-md-8">
                        <label for="repSupplier">Supplier <small class="text-muted">(opsional, boleh lebih dari satu)</small></label>
                        <select class="form-control" id="repSupplier" multiple>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->kode }}">{{ $s->nama }}</option>
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

<section id="aging-summary" class="d-none">
    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar bg-light-primary p-50 mr-1" style="border-radius:8px;">
                        <i data-feather="users" class="font-medium-4 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumSupplierCount">0</h5>
                        <small class="text-muted">Supplier Outstanding</small>
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
                        <h5 class="mb-0 font-weight-bold" id="sumTotalHutang">0</h5>
                        <small class="text-muted">Total Hutang</small>
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
                        <small class="text-muted">Info: AP DRAFT (tidak masuk aging)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="aging-result">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Hasil AP Aging</h4>
        </div>
        <div class="card-body">

            <div id="agingHeaderInfo" class="mb-1 d-none" style="font-size:.85rem;">
                <strong>Per Tanggal:</strong> <span id="hCutoff">-</span>
                <hr class="mt-50">
            </div>

            <div id="agingEmpty" class="alert alert-warning d-none">
                Pilih <strong>Per Tanggal (Cut-off)</strong>, lalu klik <strong>Generate Report</strong>.
            </div>

            <div class="d-none" id="agingScroll">
                <table class="table table-sm text-right" id="agingTable" style="font-size:.8rem;">
                    <thead class="text-center">
                        <tr>
                            <th class="col-no">No</th>
                            <th class="col-supplier text-left">Supplier</th>
                            <th>Belum Jatuh Tempo</th>
                            <th>1 - 30 Hari</th>
                            <th class="bucket-31">31 - 60 Hari</th>
                            <th class="bucket-61">61 - 90 Hari</th>
                            <th class="bucket-90">&gt; 90 Hari</th>
                            <th>Total Overdue</th>
                            <th>Total Hutang</th>
                            <th>% Overdue</th>
                        </tr>
                    </thead>
                    <tbody id="agingBody"></tbody>
                    <tfoot>
                        <tr>
                            <td class="col-no"></td>
                            <td class="col-supplier text-left">GRAND TOTAL</td>
                            <td class="aging-clickable" id="tBelum" data-bucket="belum_jatuh_tempo">0</td>
                            <td class="aging-clickable" id="tD1_30" data-bucket="d1_30">0</td>
                            <td class="bucket-31 aging-clickable" id="tD31_60" data-bucket="d31_60">0</td>
                            <td class="bucket-61 aging-clickable" id="tD61_90" data-bucket="d61_90">0</td>
                            <td class="bucket-90 aging-clickable" id="tD90plus" data-bucket="d90plus">0</td>
                            <td class="aging-clickable" id="tOverdue" data-bucket="total_overdue">0</td>
                            <td class="aging-clickable" id="tTotal" data-bucket="total_hutang">0</td>
                            <td id="tPct">0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>
</section>

<div class="modal fade" id="agingDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="agingDetailTitle">Detail AP</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="agingDetailLoading" class="text-center py-2 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <table class="table table-sm table-striped" id="agingDetailTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. AP</th>
                            <th>No. Invoice</th>
                            <th>AP Date</th>
                            <th>Invoice Date</th>
                            <th>TOP</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-right">Nilai</th>
                        </tr>
                    </thead>
                    <tbody id="agingDetailBody"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-right font-weight-bold">Total</td>
                            <td class="text-right font-weight-bold" id="agingDetailTotal">0</td>
                        </tr>
                    </tfoot>
                </table>
                <div id="agingDetailEmpty" class="text-center text-muted py-2 d-none">Tidak ada data.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
$(document).ready(function () {

    $('#repSupplier').select2({ width: '100%', placeholder: 'Semua supplier' });

    initDatePicker(document.querySelector('#repCutoff'), {
        minDate: "01/01/2010",
        maxDate: "31/12/2030",
        dateFormat: "d-m-Y",
        defaultDate: new Date()
    });

    function fmt(v) {
        let n = parseFloat(v);
        if (isNaN(n)) return '0';
        return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    let lastFilters = null;

    function resetDisplay() {
        $('#agingBody').empty();
        $('#agingHeaderInfo').addClass('d-none');
        $('#agingScroll').addClass('d-none');
        $('#aging-summary').addClass('d-none');
        $('#btnPrint').addClass('d-none');
        $('#btnExport').addClass('d-none');
        $('#agingEmpty').removeClass('d-none');
    }

    function generateReport() {
        let cutoff = $('#repCutoff').val();
        if (!cutoff) {
            Swal.fire('Warning', 'Tanggal Cut-off wajib diisi.', 'warning');
            return;
        }

        lastFilters = {
            cutoffDate : cutoff,
            supplier   : $('#repSupplier').val()
        };

        $(".loading-spinner-container").addClass("-show");

        $.post("{{ route('apAging.data') }}", lastFilters)
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
    }

    $('#btnGenerate').on('click', generateReport);

    function renderReport(res) {
        $('#hCutoff').text(res.cutoff);
        $('#agingHeaderInfo').removeClass('d-none');

        let body = '';
        if (!res.rows || res.rows.length === 0) {
            body = '<tr><td colspan="10" class="text-center text-muted py-1">Tidak ada hutang outstanding untuk filter ini.</td></tr>';
        } else {
            res.rows.forEach(function (r, idx) {
                let pctCls = r.pct_overdue >= 50 ? 'text-danger' : (r.pct_overdue > 0 ? 'text-warning' : 'text-success');
                let sc = r.supplier_code;
                function cell(cls, bucket, val) {
                    return '<td class="' + cls + ' aging-clickable" data-supplier="' + sc + '" data-bucket="' + bucket + '">' + fmt(val) + '</td>';
                }
                body += '<tr>'
                    + '<td class="col-no">' + (idx + 1) + '</td>'
                    + '<td class="col-supplier text-left">' + r.supplier_name + '</td>'
                    + cell('', 'belum_jatuh_tempo', r.belum_jatuh_tempo)
                    + cell('', 'd1_30', r.d1_30)
                    + cell('bucket-31', 'd31_60', r.d31_60)
                    + cell('bucket-61', 'd61_90', r.d61_90)
                    + cell('bucket-90', 'd90plus', r.d90plus)
                    + cell('', 'total_overdue', r.total_overdue)
                    + cell('font-weight-bold', 'total_hutang', r.total_hutang)
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
        $('#tTotal').text(fmt(g.total_hutang));
        $('#tPct').text(g.pct_overdue.toFixed(1) + '%');

        $('#sumSupplierCount').text(res.rows.length);
        $('#sumTotalHutang').text(fmt(g.total_hutang));
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

    let bucketTitles = {
        belum_jatuh_tempo: 'Belum Jatuh Tempo',
        d1_30: '1 - 30 Hari',
        d31_60: '31 - 60 Hari',
        d61_90: '61 - 90 Hari',
        d90plus: '> 90 Hari',
        total_overdue: 'Total Overdue',
        total_hutang: 'Total Hutang'
    };

    $('#agingTable').on('click', 'td.aging-clickable', function () {
        if (!lastFilters) return;

        let bucket   = $(this).data('bucket');
        let supplier = $(this).data('supplier') || '';

        $('#agingDetailTitle').text('Detail AP - ' + (bucketTitles[bucket] || bucket) + (supplier ? ' - ' + $(this).closest('tr').find('.col-supplier').text() : ' (Semua Supplier)'));
        $('#agingDetailBody').empty();
        $('#agingDetailTotal').text('0');
        $('#agingDetailEmpty').addClass('d-none');
        $('#agingDetailLoading').removeClass('d-none');
        $('#agingDetailModal').modal('show');

        $.post("{{ route('apAging.detail') }}", {
            cutoffDate   : lastFilters.cutoffDate,
            supplier     : lastFilters.supplier,
            supplierCode : supplier,
            bucket       : bucket
        })
        .done(function (res) {
            $('#agingDetailLoading').addClass('d-none');
            if (res.status !== 1) {
                Swal.fire('Error', res.message || 'Gagal memuat detail.', 'warning');
                return;
            }
            if (!res.rows || res.rows.length === 0) {
                $('#agingDetailEmpty').removeClass('d-none');
                return;
            }
            let body = '';
            res.rows.forEach(function (row, idx) {
                body += '<tr>'
                    + '<td>' + (idx + 1) + '</td>'
                    + '<td><a href="' + row.ap_link + '" target="_blank">' + row.ap_number + '</a></td>'
                    + '<td>' + (row.inv_number || '-') + '</td>'
                    + '<td>' + row.ap_date + '</td>'
                    + '<td>' + row.inv_date + '</td>'
                    + '<td>' + row.term + '</td>'
                    + '<td>' + row.jatuh_tempo + '</td>'
                    + '<td class="text-right">' + fmt(row.balance) + '</td>'
                    + '</tr>';
            });
            $('#agingDetailBody').html(body);
            $('#agingDetailTotal').text(fmt(res.total));
        })
        .fail(function (xhr) {
            $('#agingDetailLoading').addClass('d-none');
            let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan.';
            Swal.fire('Error', msg, 'error');
        });
    });

    $('#btnReset').on('click', function () {
        $('#repSupplier').val(null).trigger('change');
        resetDisplay();
        generateReport();
    });

    $('#btnPrint').on('click', function () {
        let info  = $('#agingHeaderInfo').html();
        let cards = $('#aging-summary').html();
        let table = $('#agingTable').prop('outerHTML');
        let w = window.open('', '', 'width=1200,height=800');
        w.document.write('<html><head><title>AP Aging Report</title>');
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
        w.document.write('<h3 style="margin-bottom:4px;">AP Aging Report</h3>');
        w.document.write('<div>' + info + '</div>');
        w.document.write('<div style="margin-bottom:6px;">' + cards + '</div>');
        w.document.write(table);
        w.document.write('</body></html>');
        w.document.close();
        w.focus();
        setTimeout(function () { w.print(); w.close(); }, 400);
    });

    $('#btnExport').on('click', function () {
        let $f = $('<form>', { method: 'POST', action: "{{ route('apAging.export') }}" });
        $f.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
        $f.append($('<input>', { type: 'hidden', name: 'cutoffDate', value: $('#repCutoff').val() }));
        ($('#repSupplier').val() || []).forEach(function (c) {
            $f.append($('<input>', { type: 'hidden', name: 'supplier[]', value: c }));
        });
        $('body').append($f);
        $f.submit();
        $f.remove();
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    if (!$('#repCutoff').val()) {
        let d = new Date();
        let dd = String(d.getDate()).padStart(2, '0');
        let mm = String(d.getMonth() + 1).padStart(2, '0');
        $('#repCutoff').val(dd + '-' + mm + '-' + d.getFullYear());
    }
    generateReport();
});
</script>
@endsection
