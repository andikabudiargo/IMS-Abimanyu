@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<style>
#scheduleScroll {
    max-height: 68vh;
    overflow: auto;
    position: relative;
}
#scheduleTable {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    margin-bottom: 0;
}
#scheduleTable th, #scheduleTable td {
    box-sizing: border-box;
    border-right: 1px solid #e3e6ec;
    border-bottom: 1px solid #e3e6ec;
    padding: 5px 8px;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
#scheduleTable { border-top: 1px solid #e3e6ec; border-left: 1px solid #e3e6ec; }

#scheduleTable thead th {
    position: sticky;
    top: 0;
    z-index: 3;
    background: #eef2f7 !important;
    height: 42px;
    vertical-align: middle;
}

#scheduleTable .col-customer { position: sticky; left: 0; min-width: 220px; max-width: 220px;
    white-space: normal; background: inherit; z-index: 2; }
#scheduleTable thead th.col-customer { z-index: 5; background: #e4e9f1 !important; }

#scheduleTable tbody tr:nth-child(odd)  td { background: #ffffff !important; }
#scheduleTable tbody tr:nth-child(even) td { background: #f4f7fb !important; }
#scheduleTable tbody tr:hover td { background: #e8f1ff !important; }

#scheduleTable tfoot td {
    position: sticky;
    bottom: 0;
    background: #e9edf3 !important;
    z-index: 3;
    font-weight: bold;
    height: 34px;
    vertical-align: middle;
    border-top: 2px solid #c8cfda;
}

#scheduleTable .col-no { position: sticky; left: 0; min-width: 40px; max-width: 40px;
    text-align: center; background: inherit; z-index: 2; }
#scheduleTable thead th.col-no { z-index: 5; background: #e4e9f1 !important; }
#scheduleTable .col-customer { left: 40px; }
#scheduleTable thead th.col-customer { left: 40px; }

/* Header tanggal: angka besar + label hari kecil di bawahnya */
#scheduleTable thead th .dnum { display:block; font-size:13px; font-weight:700; color:#1f2733; line-height:1.1; }
#scheduleTable thead th .dow  { display:block; font-size:10px; font-weight:600; color:#8a94a6; }

/* Akhir pekan (Sabtu & Minggu) diarsir */
#scheduleTable td.col-weekend  { background:#fff6f0 !important; }
#scheduleTable tbody tr:nth-child(even) td.col-weekend { background:#fdeee2 !important; }
#scheduleTable thead th.col-weekend { background:#fde9df !important; }
#scheduleTable thead th.col-weekend .dnum { color:#b45309; }
#scheduleTable thead th.col-weekend .dow  { color:#c8813f; }
#scheduleTable tfoot td.col-weekend { background:#f7e6d8 !important; }

/* Baris lunas penuh */
#scheduleTable tbody tr.row-paid td { color:#15803d !important; font-weight:600; }
/* Sel lunas: background hijau lembut + centang */
#scheduleTable tbody td.cell-paid { color:#15803d !important; font-weight:600; background:#e9f7ef !important; }
#scheduleTable tbody td.cell-paid::after { content:"\2713"; font-size:10px; margin-left:5px; opacity:.8; }

.schedule-clickable { cursor: pointer; text-decoration: underline dotted; }
.schedule-clickable:hover { background: #d7e8ff !important; }
</style>

<section id="schedule-filter">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Filter AR Payment Schedule</h4>
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
                        <label for="repMonth">Bulan <span class="text-danger">*</span></label>
                        <select class="form-control" id="repMonth">
                            @php
                                $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                                $curMonth = (int) date('n');
                            @endphp
                            @foreach ($months as $num => $name)
                                <option value="{{ $num }}" {{ $num == $curMonth ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="repYear">Tahun <span class="text-danger">*</span></label>
                        <select class="form-control" id="repYear">
                            @php $curYear = (int) date('Y'); @endphp
                            @for ($y = 2023; $y <= $curYear + 1; $y++)
                                <option value="{{ $y }}" {{ $y == $curYear ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group col-md-7">
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
                        <small class="text-muted d-block mb-1">
                            <strong>Opening</strong> = saldo invoice yang jatuh tempo sebelum awal bulan terpilih.
                            Kolom tanggal = invoice yang jatuh tempo pada tanggal itu di bulan terpilih.
                            <strong>Outstanding</strong> = bagian yang sampai hari ini masih lewat jatuh tempo &amp; belum dibayar.
                        </small>
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

<section id="schedule-summary" class="d-none">
    <div class="row">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-none">
                <div class="card-body d-flex align-items-center p-1">
                    <div class="avatar bg-light-primary p-50 mr-1" style="border-radius:8px;">
                        <i data-feather="users" class="font-medium-4 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold" id="sumCustomerCount">0</h5>
                        <small class="text-muted">Customer</small>
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
                        <h5 class="mb-0 font-weight-bold" id="sumTotalSchedule">0</h5>
                        <small class="text-muted">Total Jadwal Tagih</small>
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
                        <h5 class="mb-0 font-weight-bold" id="sumTotalPaid">0</h5>
                        <small class="text-muted">Total Paid (periode ini)</small>
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
                        <h5 class="mb-0 font-weight-bold" id="sumTotalOutstanding">0</h5>
                        <small class="text-muted">Outstanding (lewat jatuh tempo)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="schedule-result">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="card-title mb-0">Hasil AR Payment Schedule</h4>
            <div style="font-size:11.5px; color:#6b7688;">
                <span class="mr-2"><span style="display:inline-block;width:11px;height:11px;border-radius:3px;background:#e9f7ef;border:1px solid #15803d;vertical-align:-1px;"></span> Lunas</span>
                <span class="mr-2"><span style="display:inline-block;width:11px;height:11px;border-radius:3px;background:#fff;border:1px solid #dc2626;vertical-align:-1px;"></span> Lewat jatuh tempo</span>
                <span><span style="display:inline-block;width:11px;height:11px;border-radius:3px;background:#fde9df;border:1px solid #b45309;vertical-align:-1px;"></span> Akhir pekan</span>
            </div>
        </div>
        <div class="card-body">

            <div id="scheduleHeaderInfo" class="mb-1 d-none" style="font-size:.85rem;">
                <strong>Periode:</strong> <span id="hPeriod">-</span>
                <hr class="mt-50">
            </div>

            <div id="scheduleEmpty" class="alert alert-warning d-none">
                Pilih <strong>Bulan</strong> &amp; <strong>Tahun</strong>, lalu klik <strong>Generate Report</strong>.
            </div>

            <div class="d-none" id="scheduleScroll">
                <table class="table table-sm text-right" id="scheduleTable" style="font-size:.8rem;">
                    <thead class="text-center"></thead>
                    <tbody id="scheduleBody"></tbody>
                    <tfoot></tfoot>
                </table>
            </div>

        </div>
    </div>
</section>

<div class="modal fade" id="scheduleDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scheduleDetailTitle">Detail Invoice</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="scheduleDetailLoading" class="text-center py-2 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <table class="table table-sm table-striped" id="scheduleDetailTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No. Invoice</th>
                            <th>Customer</th>
                            <th>Invoice Date</th>
                            <th>TOP</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-right">Nilai</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleDetailBody"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-right font-weight-bold">Total</td>
                            <td class="text-right font-weight-bold" id="scheduleDetailTotal">0</td>
                        </tr>
                    </tfoot>
                </table>
                <div id="scheduleDetailEmpty" class="text-center text-muted py-2 d-none">Tidak ada data.</div>
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

    $('#repCustomer').select2({ width: '100%', placeholder: 'Semua customer' });

    // Blank kalau 0 -- tabel jadwal ini punya banyak sekali sel kosong
    // (tiap customer cuma jatuh tempo di 1-2 tanggal), angka nol di
    // mana-mana cuma bikin susah dibaca.
    function fmt(v) {
        let n = parseFloat(v);
        if (!n) return '';
        return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }
    function fmtAlways(v) {
        let n = parseFloat(v);
        if (isNaN(n)) n = 0;
        return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    let lastFilters = null;

    function resetDisplay() {
        $('#scheduleBody').empty();
        $('#scheduleHeaderInfo').addClass('d-none');
        $('#scheduleScroll').addClass('d-none');
        $('#schedule-summary').addClass('d-none');
        $('#btnPrint').addClass('d-none');
        $('#btnExport').addClass('d-none');
        $('#scheduleEmpty').removeClass('d-none');
    }

    function generateReport() {
        lastFilters = {
            month    : $('#repMonth').val(),
            year     : $('#repYear').val(),
            customer : $('#repCustomer').val()
        };

        $(".loading-spinner-container").addClass("-show");

        $.post("{{ route('arPaymentSchedule.data') }}", lastFilters)
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

    const DOW = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    // Sabtu (6) / Minggu (0) -> ditandai sebagai akhir pekan
    function isWeekend(year, month, day) {
        let g = new Date(year, month - 1, day).getDay();
        return g === 0 || g === 6;
    }

    function buildFrame(daysInMonth, year, month) {
        let h = '<tr>'
            + '<th class="col-no">No</th>'
            + '<th class="col-customer text-left">Customer</th>'
            + '<th class="schedule-clickable" data-bucket="opening">Opening</th>';
        for (let d = 1; d <= daysInMonth; d++) {
            let wk = isWeekend(year, month, d);
            let g = new Date(year, month - 1, d).getDay();
            h += '<th class="schedule-clickable' + (wk ? ' col-weekend' : '') + '" data-bucket="d' + d + '" data-customer="">'
                + '<span class="dnum">' + d + '</span><span class="dow">' + DOW[g] + '</span></th>';
        }
        h += '<th class="schedule-clickable" data-bucket="total" data-customer="">Total</th>'
            + '<th>Paid</th>'
            + '<th>Balance</th>'
            + '<th class="schedule-clickable" data-bucket="outstanding" data-customer="">Outstanding</th>'
            + '</tr>';
        $('#scheduleTable thead').html(h);

        let f = '<tr>'
            + '<td class="col-no"></td>'
            + '<td class="col-customer text-left">GRAND TOTAL</td>'
            + '<td class="schedule-clickable" id="tOpening" data-bucket="opening" data-customer="">0</td>';
        for (let d = 1; d <= daysInMonth; d++) {
            f += '<td class="schedule-clickable' + (isWeekend(year, month, d) ? ' col-weekend' : '') + '" id="tD' + d + '" data-bucket="d' + d + '" data-customer="">0</td>';
        }
        f += '<td class="schedule-clickable" id="tTotal" data-bucket="total" data-customer="">0</td>'
            + '<td id="tPaid">0</td>'
            + '<td id="tBalance">0</td>'
            + '<td class="schedule-clickable" id="tOutstanding" data-bucket="outstanding" data-customer="">0</td>'
            + '</tr>';
        $('#scheduleTable tfoot').html(f);
    }

    function renderReport(res) {
        buildFrame(res.daysInMonth, res.year, res.month);

        $('#hPeriod').text($('#repMonth option:selected').text() + ' ' + res.year);
        $('#scheduleHeaderInfo').removeClass('d-none');

        let body = '';
        if (!res.rows || res.rows.length === 0) {
            body = '<tr><td colspan="' + (res.daysInMonth + 7) + '" class="text-center text-muted py-1">Tidak ada tagihan untuk periode ini.</td></tr>';
        } else {
            res.rows.forEach(function (r, idx) {
                let cc = r.customer_code;
                // sel hijau kalau ada nilai tapi sisa (remaining) sudah lunas
                function cell(bucket, val, cls, remain) {
                    let paid = parseFloat(val) > 0 && remain !== undefined && Math.abs(parseFloat(remain)) < 1;
                    let c = (cls || '') + (paid ? ' cell-paid' : '');
                    return '<td class="' + c + ' schedule-clickable" data-customer="' + cc + '" data-bucket="' + bucket + '">' + fmt(val) + '</td>';
                }
                let paidOff = parseFloat(r.total) > 0 && Math.abs(parseFloat(r.balance)) < 1;
                let row = '<tr' + (paidOff ? ' class="row-paid"' : '') + '>'
                    + '<td class="col-no">' + (idx + 1) + '</td>'
                    + '<td class="col-customer text-left">' + r.customer_name + '</td>'
                    + cell('opening', r.opening, '', r.opening_remain);
                for (let d = 1; d <= res.daysInMonth; d++) {
                    row += cell('d' + d, r.days['d' + d], isWeekend(res.year, res.month, d) ? 'col-weekend' : '', r.days_remain['d' + d]);
                }
                row += cell('total', r.total, 'font-weight-bold')
                    + '<td>' + fmt(r.paid) + '</td>'
                    + '<td class="font-weight-bold">' + fmt(r.balance) + '</td>'
                    + cell('outstanding', r.outstanding, 'text-danger font-weight-bold')
                    + '</tr>';
                body += row;
            });
        }
        $('#scheduleBody').html(body);

        let g = res.grand;
        $('#tOpening').text(fmtAlways(g.opening));
        for (let d = 1; d <= res.daysInMonth; d++) {
            $('#tD' + d).text(fmtAlways(g['d' + d]));
        }
        $('#tTotal').text(fmtAlways(g.total));
        $('#tPaid').text(fmtAlways(g.paid));
        $('#tBalance').text(fmtAlways(g.balance));
        $('#tOutstanding').text(fmtAlways(g.outstanding));

        $('#sumCustomerCount').text(res.rows.length);
        $('#sumTotalSchedule').text(fmtAlways(g.total));
        $('#sumTotalPaid').text(fmtAlways(g.paid));
        $('#sumTotalOutstanding').text(fmtAlways(g.outstanding));
        $('#schedule-summary').removeClass('d-none');

        $('#scheduleEmpty').addClass('d-none');
        $('#scheduleScroll').removeClass('d-none');
        $('#btnPrint').removeClass('d-none');
        $('#btnExport').removeClass('d-none');

        if (typeof feather !== 'undefined') feather.replace();
    }

    function bucketTitle(bucket) {
        if (bucket === 'opening') return 'Opening Balance';
        if (bucket === 'total') return 'Total';
        if (bucket === 'outstanding') return 'Outstanding (Lewat Jatuh Tempo)';
        if (bucket && bucket.charAt(0) === 'd') return 'Jatuh Tempo Tanggal ' + bucket.substring(1);
        return bucket;
    }

    $('#scheduleTable').on('click', 'td.schedule-clickable, th.schedule-clickable', function () {
        if (!lastFilters) return;

        let bucket   = $(this).data('bucket');
        let customer = $(this).data('customer') || '';

        $('#scheduleDetailTitle').text('Detail Invoice - ' + bucketTitle(bucket) + (customer ? ' - ' + $(this).closest('tr').find('.col-customer').text() : ' (Semua Customer)'));
        $('#scheduleDetailBody').empty();
        $('#scheduleDetailTotal').text('0');
        $('#scheduleDetailEmpty').addClass('d-none');
        $('#scheduleDetailLoading').removeClass('d-none');
        $('#scheduleDetailModal').modal('show');

        $.post("{{ route('arPaymentSchedule.detail') }}", {
            month        : lastFilters.month,
            year         : lastFilters.year,
            customer     : lastFilters.customer,
            customerCode : customer,
            bucket       : bucket
        })
        .done(function (res) {
            $('#scheduleDetailLoading').addClass('d-none');
            if (res.status !== 1) {
                Swal.fire('Error', res.message || 'Gagal memuat detail.', 'warning');
                return;
            }
            if (!res.rows || res.rows.length === 0) {
                $('#scheduleDetailEmpty').removeClass('d-none');
                return;
            }
            let body = '';
            res.rows.forEach(function (row, idx) {
                body += '<tr>'
                    + '<td>' + (idx + 1) + '</td>'
                    + '<td><a href="' + row.invoice_link + '" target="_blank">' + row.invoice_number + '</a></td>'
                    + '<td>' + row.customer_name + '</td>'
                    + '<td>' + row.invoice_date + '</td>'
                    + '<td>' + row.term + '</td>'
                    + '<td>' + row.jatuh_tempo + '</td>'
                    + '<td class="text-right">' + fmtAlways(row.balance) + '</td>'
                    + '</tr>';
            });
            $('#scheduleDetailBody').html(body);
            $('#scheduleDetailTotal').text(fmtAlways(res.total));
        })
        .fail(function (xhr) {
            $('#scheduleDetailLoading').addClass('d-none');
            let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan.';
            Swal.fire('Error', msg, 'error');
        });
    });

    $('#btnReset').on('click', function () {
        $('#repCustomer').val(null).trigger('change');
        $('#repMonth').val(new Date().getMonth() + 1);
        $('#repYear').val(new Date().getFullYear());
        resetDisplay();
        generateReport();
    });

    $('#btnPrint').on('click', function () {
        let info  = $('#scheduleHeaderInfo').html();
        let cards = $('#schedule-summary').html();
        let table = $('#scheduleTable').prop('outerHTML');
        let w = window.open('', '', 'width=1200,height=800');
        w.document.write('<html><head><title>AR Payment Schedule</title>');
        w.document.write('<style>'
            + 'body{font-family:Arial,sans-serif;font-size:9px;padding:16px;}'
            + 'table{width:100%;border-collapse:collapse;margin-top:10px;}'
            + 'th,td{border:1px solid #555;padding:2px 3px;}'
            + 'thead{background:#ddd;}'
            + 'tbody tr:nth-child(even) td{background:#f4f7fb;-webkit-print-color-adjust:exact;}'
            + '.text-right{text-align:right;}.text-left{text-align:left;}.text-center{text-align:center;}'
            + '.font-weight-bold{font-weight:bold;}.text-danger{color:#ea5455;}'
            + '.col-weekend{background:#fdeee2 !important;-webkit-print-color-adjust:exact;}'
            + '.cell-paid{background:#e9f7ef !important;color:#15803d;font-weight:bold;-webkit-print-color-adjust:exact;}'
            + '.cell-paid::after{content:"\\2713";margin-left:4px;}'
            + '.dow{display:block;font-size:7px;color:#888;}'
            + '</style></head><body>');
        w.document.write('<h3 style="margin-bottom:4px;">AR Payment Schedule</h3>');
        w.document.write('<div>' + info + '</div>');
        w.document.write('<div style="margin-bottom:6px;">' + cards + '</div>');
        w.document.write(table);
        w.document.write('</body></html>');
        w.document.close();
        w.focus();
        setTimeout(function () { w.print(); w.close(); }, 400);
    });

    $('#btnExport').on('click', function () {
        let $f = $('<form>', { method: 'POST', action: "{{ route('arPaymentSchedule.export') }}" });
        $f.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
        $f.append($('<input>', { type: 'hidden', name: 'month', value: $('#repMonth').val() }));
        $f.append($('<input>', { type: 'hidden', name: 'year', value: $('#repYear').val() }));
        ($('#repCustomer').val() || []).forEach(function (c) {
            $f.append($('<input>', { type: 'hidden', name: 'customer[]', value: c }));
        });
        $('body').append($f);
        $f.submit();
        $f.remove();
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    generateReport();
});
</script>
@endsection
