@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<style>
#planTableScroll { max-height: 68vh; overflow: auto; position: relative; width: 100%; }
#planTable { margin-bottom: 0; }
#planTable th, #planTable td { white-space: nowrap; font-size: .82rem; vertical-align: middle; }
#planTable td.col-note { white-space: normal; max-width: 220px; }
#planTable thead th { position: sticky; top: 0; z-index: 2; background: #eef2f7; }
#planTable tfoot td { position: sticky; bottom: 0; font-weight: bold; background: #eef2f7; }
#planTable .fee-input { width: 110px; text-align: right; }
.status-badge { font-size: .72rem; padding: .3em .6em; }
tr.row-hold td { background: #fff5f5 !important; }
tr.row-to_be_paid td { background: #f0f7ff !important; }
tr.row-paid td { background: #f0fbf4 !important; color: #15803d; }
</style>

<section id="plan-filter">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Filter AP Payment Planning</h4>
            <div class="heading-elements">
                <ul class="list-inline mb-0">
                    <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                </ul>
            </div>
        </div>
        <div class="card-content collapse show">
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="repMonth">Bulan</label>
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
                        <label for="repYear">Tahun</label>
                        <select class="form-control" id="repYear">
                            @php $curYear = (int) date('Y'); @endphp
                            @for ($y = 2023; $y <= $curYear + 1; $y++)
                                <option value="{{ $y }}" {{ $y == $curYear ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="repStatus">Status</label>
                        <select class="form-control" id="repStatus">
                            <option value="">Semua</option>
                            <option value="pending">Pending</option>
                            <option value="hold">Hold</option>
                            <option value="to_be_paid">To Be Paid</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
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
                            <i data-feather="search" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Generate</span>
                        </button>
                        <button type="button" class="btn btn-light" id="btnReset">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="plan-result">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h4 class="card-title mb-0">Hasil AP Payment Planning</h4>
            <div id="planActions" class="d-none">
                <span class="mr-2 text-muted" id="planSelectedCount">0 dipilih</span>
                <button type="button" class="btn btn-sm btn-outline-danger" id="btnHold">Tandai Hold</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnToBePaid">Tandai To Be Paid</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPending">Kembalikan ke Pending</button>
            </div>
        </div>
        <div class="card-body">
            <div id="planEmpty" class="alert alert-warning d-none">Tidak ada data untuk filter ini.</div>
            <div class="d-none" id="planTableWrap">
              <div id="planTableScroll">
                <table class="table table-sm table-bordered" id="planTable">
                    <thead class="text-center">
                        <tr>
                            <th><input type="checkbox" id="chkAll"></th>
                            <th>No</th>
                            <th>Supplier</th>
                            <th>Invoice Date</th>
                            <th>Invoice Number</th>
                            <th>Receive AP</th>
                            <th>Due Date</th>
                            <th>Voucher Number</th>
                            <th>Note</th>
                            <th class="text-right">Nominal</th>
                            <th class="text-right">Biaya Administrasi</th>
                            <th class="text-right">PPH23</th>
                            <th class="text-right">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="planBody"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="9" class="text-right">GRAND TOTAL</td>
                            <td class="text-right" id="gNominal">0</td>
                            <td class="text-right" id="gBiaya">0</td>
                            <td class="text-right" id="gPph23">0</td>
                            <td class="text-right" id="gTotal">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
              </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script type="text/javascript">
$(document).ready(function () {

    $('#repSupplier').select2({ width: '100%', placeholder: 'Semua supplier' });
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function fmt(v) {
        let n = parseFloat(v);
        if (isNaN(n)) n = 0;
        return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function statusBadge(row) {
        let map = {
            pending:    ['secondary', 'Pending'],
            hold:       ['danger', 'Hold'],
            to_be_paid: ['primary', 'To Be Paid'],
            paid:       ['success', 'Paid'],
        };
        let [cls, label] = map[row.status] || ['secondary', row.status];
        let title = row.status === 'hold' && row.hold_reason ? row.hold_reason : label;
        return '<span class="badge badge-' + cls + ' status-badge" title="' + $('<div>').text(title).html() + '">' + label + '</span>';
    }

    function filters() {
        return {
            month: $('#repMonth').val(),
            year: $('#repYear').val(),
            status: $('#repStatus').val(),
            supplier: $('#repSupplier').val(),
        };
    }

    function load() {
        $(".loading-spinner-container").addClass("-show");
        $.post("{{ route('apPaymentPlanning.data') }}", filters())
        .done(function (res) {
            $(".loading-spinner-container").removeClass("-show");
            render(res);
        })
        .fail(function (xhr) {
            $(".loading-spinner-container").removeClass("-show");
            Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan.', 'error');
        });
    }

    function render(res) {
        $('#planActions').addClass('d-none');
        if (!res.rows || res.rows.length === 0) {
            $('#planTableWrap').addClass('d-none');
            $('#planEmpty').removeClass('d-none');
            return;
        }
        $('#planEmpty').addClass('d-none');
        $('#planTableWrap').removeClass('d-none');

        let body = '';
        res.rows.forEach(function (r, idx) {
            let disabled = r.status === 'paid' ? 'disabled' : '';
            body += '<tr class="row-' + r.status + '" data-ref="' + r.ap_number + '">'
                + '<td class="text-center"><input type="checkbox" class="rowChk" value="' + r.ap_number + '" ' + disabled + '></td>'
                + '<td>' + (idx + 1) + '</td>'
                + '<td>' + r.supplier_name + '</td>'
                + '<td>' + r.invoice_date + '</td>'
                + '<td><a href="' + r.ap_link + '" target="_blank">' + r.ap_number + '</a><br><small class="text-muted">' + (r.inv_number || '') + '</small></td>'
                + '<td>' + (r.receive_ap || '-') + '</td>'
                + '<td>' + r.due_date + '</td>'
                + '<td>' + (r.voucher_number || '-') + '</td>'
                + '<td class="col-note">' + (r.note || '') + '</td>'
                + '<td class="text-right">' + fmt(r.nominal) + '</td>'
                + '<td class="text-right"><input type="number" class="form-control form-control-sm fee-input feeInput" data-ref="' + r.ap_number + '" value="' + (r.biaya_administrasi || 0) + '" ' + (disabled || r.status === 'pending' ? 'disabled' : '') + '></td>'
                + '<td class="text-right">' + fmt(r.pph23) + '</td>'
                + '<td class="text-right font-weight-bold rowTotal">' + fmt(r.total) + '</td>'
                + '<td class="text-center">' + statusBadge(r) + '</td>'
                + '</tr>';
        });
        $('#planBody').html(body);

        $('#gNominal').text(fmt(res.grand.nominal));
        $('#gBiaya').text(fmt(res.grand.biaya_administrasi));
        $('#gPph23').text(fmt(res.grand.pph23));
        $('#gTotal').text(fmt(res.grand.total));

        if (typeof feather !== 'undefined') feather.replace();
    }

    $('#btnGenerate').on('click', load);
    $('#btnReset').on('click', function () {
        $('#repSupplier').val(null).trigger('change');
        $('#repStatus').val('');
        $('#repMonth').val(new Date().getMonth() + 1);
        $('#repYear').val(new Date().getFullYear());
        load();
    });

    $('#chkAll').on('change', function () {
        $('.rowChk:not(:disabled)').prop('checked', $(this).is(':checked'));
        toggleActions();
    });
    $('#planTable').on('change', '.rowChk', toggleActions);

    function toggleActions() {
        let n = $('.rowChk:checked').length;
        $('#planSelectedCount').text(n + ' dipilih');
        $('#planActions').toggleClass('d-none', n === 0);
    }

    function selectedRefs() {
        return $('.rowChk:checked').map(function () { return $(this).val(); }).get();
    }

    function mark(action, extra) {
        let refs = selectedRefs();
        if (refs.length === 0) return;
        $.post("{{ route('apPaymentPlanning.mark') }}", Object.assign({ refs: refs, action: action }, extra || {}))
        .done(function (res) {
            if (res.status !== 1) {
                Swal.fire('Ditolak', res.message || 'Gagal menyimpan.', 'warning');
                return;
            }
            load();
        })
        .fail(function (xhr) {
            Swal.fire('Error', (xhr.responseJSON && xhr.responseJSON.message) || 'Terjadi kesalahan.', 'error');
        });
    }

    $('#btnHold').on('click', function () {
        Swal.fire({
            title: 'Hold Reason',
            input: 'textarea',
            inputPlaceholder: 'Kenapa invoice ini di-hold?',
            showCancelButton: true,
            confirmButtonText: 'Tandai Hold',
            cancelButtonText: 'Batal',
            inputValidator: (v) => !v && 'Hold Reason wajib diisi',
        }).then((result) => {
            if (result.isConfirmed) mark('hold', { hold_reason: result.value });
        });
    });

    $('#btnToBePaid').on('click', function () {
        Swal.fire({
            title: 'Tandai To Be Paid',
            text: 'Biaya administrasi bisa diisi/diedit belakangan langsung di tabel.',
            showCancelButton: true,
            confirmButtonText: 'Tandai To Be Paid',
            cancelButtonText: 'Batal',
        }).then((result) => {
            if (result.isConfirmed) mark('to_be_paid');
        });
    });

    $('#btnPending').on('click', function () {
        mark('pending');
    });

    $('#planTable').on('change', '.feeInput', function () {
        let $input = $(this);
        let ref = $input.data('ref');
        let fee = parseFloat($input.val()) || 0;
        $.post("{{ route('apPaymentPlanning.updateFee') }}", { ref: ref, biaya_administrasi: fee })
        .done(function (res) {
            if (res.status !== 1) {
                Swal.fire('Ditolak', res.message || 'Gagal menyimpan.', 'warning');
                return;
            }
            load();
        });
    });

    load();
});
</script>
@endsection
