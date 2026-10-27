@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<style>
#reconTable tbody tr:nth-child(odd)  td { background: #ffffff; }
#reconTable tbody tr:nth-child(even) td { background: #f4f7fb; }
#reconTable tbody tr:hover td { background: #e8f1ff !important; }
#reconTable tbody tr.row-opening td { background: #eef2f7 !important; }
.recon-clickable { cursor: pointer; text-decoration: underline dotted; }
.recon-clickable:hover { background: #d7e8ff !important; }
.balance-pos { color: #1e8449; font-weight: bold; }
.balance-neg { color: #c0392b; font-weight: bold; }
</style>

<section id="arRecon-filter">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Filter AR Reconciliation</h4>
        </div>
        <div class="card-content collapse show">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="reconYear">Tahun <span class="text-danger">*</span></label>
                        <select class="form-control" id="reconYear">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-9">
                        <label class="d-block">Basis Penambah</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="basisInvoice" name="reconBasis" class="custom-control-input" value="invoice" checked>
                            <label class="custom-control-label" for="basisInvoice">AR Invoice (invoice_date)</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="basisDelivery" name="reconBasis" class="custom-control-input" value="delivery">
                            <label class="custom-control-label" for="basisDelivery">Delivery Note (delivery_date)</label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="btnGenerate">
                            <i data-feather="bar-chart-2" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Generate Report</span>
                        </button>
                        <span class="float-right">
                            <button type="button" class="btn btn-outline-success mr-50" id="btnExport">
                                <i data-feather="download" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Export Excel</span>
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="btnExportPdf">
                                <i data-feather="file-text" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle d-sm-inline-block d-none">Export PDF</span>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="arRecon-result">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Hasil AR Reconciliation</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered text-right" id="reconTable" style="font-size:.85rem;">
                    <thead class="text-center">
                        <tr>
                            <th rowspan="2" class="align-middle text-left">Periode</th>
                            <th rowspan="2" class="align-middle">DPP</th>
                            <th id="thPenambah" colspan="2">Penambah</th>
                            <th rowspan="2" class="align-middle">Pengurang<br>(Bank/Kas)</th>
                            <th rowspan="2" class="align-middle">Balance</th>
                            <th rowspan="2" class="align-middle" id="thOtherBalance">Balance (basis lain)</th>
                            <th rowspan="2" class="align-middle">Control</th>
                        </tr>
                        <tr>
                            <th id="thAddition">Delivery Note</th>
                            <th id="thDebitNote">Debit Note</th>
                        </tr>
                    </thead>
                    <tbody id="reconBody">
                        <tr><td colspan="8" class="text-center text-muted py-2">Pilih tahun lalu klik Generate Report.</td></tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td class="text-left">TOTAL</td>
                            <td id="tDpp">0</td>
                            <td id="tAddition">0</td>
                            <td id="tDebitNote">0</td>
                            <td id="tPayment">0</td>
                            <td id="tBalance">0</td>
                            <td id="tOtherBalance">0</td>
                            <td id="tControl">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="reconDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reconDetailTitle">Detail</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="reconDetailLoading" class="text-center py-2 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <table class="table table-sm table-striped" id="reconDetailTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Dokumen</th>
                            <th>Tipe</th>
                            <th>Tanggal</th>
                            <th class="text-right">Nilai</th>
                        </tr>
                    </thead>
                    <tbody id="reconDetailBody"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right font-weight-bold">Total</td>
                            <td class="text-right font-weight-bold" id="reconDetailTotal">0</td>
                        </tr>
                    </tfoot>
                </table>
                <div id="reconDetailEmpty" class="text-center text-muted py-2 d-none">Tidak ada data.</div>
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

    function fmt(v) {
        let n = parseFloat(v);
        if (isNaN(n)) return '0';
        return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function balanceClass(v) {
        return parseFloat(v) < 0 ? 'balance-neg' : 'balance-pos';
    }

    function currentFilters() {
        return { year: $('#reconYear').val(), basis: $('input[name=reconBasis]:checked').val() };
    }

    function generateReport() {
        let f = currentFilters();
        $('#thAddition').text(f.basis === 'delivery' ? 'Delivery Note' : 'AR Invoice');

        $(".loading-spinner-container").addClass("-show");

        $.post("{{ route('arReconciliation.data') }}", f)
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

    function cell(cls, col, month, val) {
        return '<td class="' + cls + ' recon-clickable" data-col="' + col + '" data-month="' + (month === null ? '' : month) + '">' + fmt(val) + '</td>';
    }

    let basisLabel = { invoice: 'AR Invoice', delivery: 'Delivery Note' };

    function renderReport(res) {
        let showDn = res.showDebitNote !== false;
        $('#thPenambah').attr('colspan', showDn ? 2 : 1);
        $('#thDebitNote, #tDebitNote').toggle(showDn);
        $('#thOtherBalance').text('Balance ' + (basisLabel[res.otherBasis] || res.otherBasis));

        let body = '';
        let openingControl = res.openingBalance - res.otherOpeningBalance;
        body += '<tr class="row-opening font-weight-bold">'
            + '<td class="text-left">Saldo Awal</td>'
            + '<td>-</td><td>-</td>' + (showDn ? '<td>-</td>' : '') + '<td>-</td>'
            + '<td class="' + balanceClass(res.openingBalance) + '">' + fmt(res.openingBalance) + '</td>'
            + '<td>' + fmt(res.otherOpeningBalance) + '</td>'
            + '<td class="' + balanceClass(openingControl) + '">' + fmt(openingControl) + '</td>'
            + '</tr>';

        res.rows.forEach(function (r) {
            body += '<tr>'
                + '<td class="text-left">' + r.label + '</td>'
                + '<td>' + fmt(r.dpp) + '</td>'
                + cell('', 'addition', r.month, r.addition)
                + (showDn ? cell('', 'debitNote', r.month, r.debitNote) : '')
                + cell('', 'payment', r.month, r.payment)
                + '<td class="' + balanceClass(r.balance) + '">' + fmt(r.balance) + '</td>'
                + '<td>' + fmt(r.otherBalance) + '</td>'
                + '<td class="' + balanceClass(r.control) + '">' + fmt(r.control) + '</td>'
                + '</tr>';
        });
        $('#reconBody').html(body);

        let t = res.totals;
        $('#tDpp').text(fmt(t.dpp));
        $('#tAddition').text(fmt(t.addition));
        $('#tDebitNote').text(fmt(t.debitNote));
        $('#tPayment').text(fmt(t.payment));
        $('#tBalance').removeClass('balance-pos balance-neg').addClass(balanceClass(res.closingBalance)).text(fmt(res.closingBalance));
        $('#tOtherBalance').text(fmt(res.otherClosingBalance));
        $('#tControl').removeClass('balance-pos balance-neg').addClass(balanceClass(res.controlClosing)).text(fmt(res.controlClosing));
    }

    let colLabels = { addition: 'Penambah', debitNote: 'Debit Note', payment: 'Pengurang (Bank/Kas)' };

    $('#reconTable').on('click', 'td.recon-clickable', function () {
        let col   = $(this).data('col');
        let month = $(this).data('month');
        let f = currentFilters();

        $('#reconDetailTitle').text('Detail ' + (colLabels[col] || col) + ' - ' + $(this).closest('tr').find('td:first').text());
        $('#reconDetailBody').empty();
        $('#reconDetailTotal').text('0');
        $('#reconDetailEmpty').addClass('d-none');
        $('#reconDetailLoading').removeClass('d-none');
        $('#reconDetailModal').modal('show');

        $.post("{{ route('arReconciliation.detail') }}", {
            year: f.year, basis: f.basis, month: month, column: col
        })
        .done(function (res) {
            $('#reconDetailLoading').addClass('d-none');
            if (res.status !== 1) {
                Swal.fire('Error', res.message || 'Gagal memuat detail.', 'warning');
                return;
            }
            if (!res.rows || res.rows.length === 0) {
                $('#reconDetailEmpty').removeClass('d-none');
                return;
            }
            let body = '';
            res.rows.forEach(function (row, idx) {
                let docCell = row.link
                    ? '<a href="' + row.link + '" target="_blank">' + row.doc + '</a>'
                    : row.doc;
                body += '<tr>'
                    + '<td>' + (idx + 1) + '</td>'
                    + '<td>' + docCell + (row.reference ? ' (' + row.reference + ')' : '') + '</td>'
                    + '<td>' + row.doc_type + '</td>'
                    + '<td>' + row.doc_date + '</td>'
                    + '<td class="text-right">' + fmt(row.amount) + '</td>'
                    + '</tr>';
            });
            $('#reconDetailBody').html(body);
            $('#reconDetailTotal').text(fmt(res.total));
        })
        .fail(function (xhr) {
            $('#reconDetailLoading').addClass('d-none');
            let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan.';
            Swal.fire('Error', msg, 'error');
        });
    });

    $('#btnGenerate').on('click', generateReport);

    $('#btnExport').on('click', function () {
        let f = currentFilters();
        let $f = $('<form>', { method: 'POST', action: "{{ route('arReconciliation.export') }}" });
        $f.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
        $f.append($('<input>', { type: 'hidden', name: 'year', value: f.year }));
        $f.append($('<input>', { type: 'hidden', name: 'basis', value: f.basis }));
        $('body').append($f);
        $f.submit();
        $f.remove();
    });

    $('#btnExportPdf').on('click', function () {
        let f = currentFilters();
        let $f = $('<form>', { method: 'POST', action: "{{ route('arReconciliation.exportPdf') }}", target: '_blank' });
        $f.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
        $f.append($('<input>', { type: 'hidden', name: 'year', value: f.year }));
        $f.append($('<input>', { type: 'hidden', name: 'basis', value: f.basis }));
        $('body').append($f);
        $f.submit();
        $f.remove();
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    generateReport();
});
</script>
@endsection
