@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="apRecon-filter">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Filter AP Reconciliation</h4>
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
                            <input type="radio" id="basisAp" name="reconBasis" class="custom-control-input" value="ap" checked>
                            <label class="custom-control-label" for="basisAp">AP Invoice (ap_date)</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="basisReceiving" name="reconBasis" class="custom-control-input" value="receiving">
                            <label class="custom-control-label" for="basisReceiving">Receiving (rec_date)</label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="btnGenerate">
                            <i data-feather="bar-chart-2" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Generate Report</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="apRecon-result">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Hasil AP Reconciliation</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered text-right" id="reconTable" style="font-size:.85rem;">
                    <thead class="text-center">
                        <tr>
                            <th class="text-left">Periode</th>
                            <th>DPP</th>
                            <th id="thAddition">Penambah<br>(Receiving)</th>
                            <th>Pengurang<br>(Bank/Kas)</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody id="reconBody">
                        <tr><td colspan="5" class="text-center text-muted py-2">Pilih tahun lalu klik Generate Report.</td></tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td class="text-left">TOTAL</td>
                            <td id="tDpp">0</td>
                            <td id="tAddition">0</td>
                            <td id="tPayment">0</td>
                            <td id="tBalance">0</td>
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

    function fmt(v) {
        let n = parseFloat(v);
        if (isNaN(n)) return '0';
        return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function generateReport() {
        let basis = $('input[name=reconBasis]:checked').val();
        $('#thAddition').html(basis === 'receiving' ? 'Penambah<br>(Receiving)' : 'Penambah<br>(AP Invoice)');

        $(".loading-spinner-container").addClass("-show");

        $.post("{{ route('apReconciliation.data') }}", {
            year: $('#reconYear').val(),
            basis: basis
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
    }

    function renderReport(res) {
        let body = '';
        body += '<tr class="font-weight-bold" style="background:#f4f7fb;">'
            + '<td class="text-left">Saldo Awal</td>'
            + '<td>-</td><td>-</td><td>-</td>'
            + '<td>' + fmt(res.openingBalance) + '</td>'
            + '</tr>';

        res.rows.forEach(function (r) {
            body += '<tr>'
                + '<td class="text-left">' + r.label + '</td>'
                + '<td>' + fmt(r.dpp) + '</td>'
                + '<td>' + fmt(r.addition) + '</td>'
                + '<td>' + fmt(r.payment) + '</td>'
                + '<td class="font-weight-bold">' + fmt(r.balance) + '</td>'
                + '</tr>';
        });
        $('#reconBody').html(body);

        let t = res.totals;
        $('#tDpp').text(fmt(t.dpp));
        $('#tAddition').text(fmt(t.addition));
        $('#tPayment').text(fmt(t.payment));
        $('#tBalance').text(fmt(res.closingBalance));
    }

    $('#btnGenerate').on('click', generateReport);
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    generateReport();
});
</script>
@endsection
