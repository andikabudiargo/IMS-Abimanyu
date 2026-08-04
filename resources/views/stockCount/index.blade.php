@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
    $typeBadge = [
        'LOCATION' => ['label'=>'Lokasi','class'=>'badge-light-primary'],
        'SUPPLIER' => ['label'=>'Supplier','class'=>'badge-light-warning'],
        'CUSTOMER' => ['label'=>'Customer','class'=>'badge-light-info'],
    ];
@endphp
{{-- ════════════════════════════════════════════════
     TARGET STOCK COUNT SAYA
════════════════════════════════════════════════ --}}
<section id="stock-count-index">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">List Stock Count Saya</h4>
        </div>
        <div class="card-body">
            @if($rows->isEmpty())
                <div class="alert alert-warning mb-0">
                    Tidak ada list STO yang bisa diakses{{ $isAcct ? '' : ' hari ini' }}.
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover" style="font-size:.85rem;">
                    <thead class="thead-light">
                        <tr>
                            <th>Sumber</th>
                            <th>Target</th>
                            <th>STO Code</th>
                            <th>Periode</th>
                            <th>STO Date</th>
                            <th>Peran Saya</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                        @php
                            $tb = $typeBadge[$r->target_type] ?? ['label'=>$r->target_type,'class'=>'badge-light-secondary'];
                            $myRole = auth()->id() == $r->counter1_user ? 'Counter 1'
                                    : (auth()->id() == $r->counter2_user ? 'Counter 2' : 'Accounting (override)');
                            $encId = Crypt::encryptString($r->mapping_id);
                        @endphp
                        <tr>
                            <td><span class="badge {{ $tb['class'] }}">{{ $tb['label'] }}</span></td>
                            <td class="font-weight-bold">{{ $r->target_name }}</td>
                            <td>{{ $r->sto_code }}</td>
                            <td>{{ $r->periode }}</td>
                            <td>{{ $r->sto_date }}</td>
                            <td>{{ $myRole }}</td>
                            <td>
                                @if($r->finish_time)
                                    <span class="badge badge-success">Selesai</span>
                                @else
                                    <span class="badge badge-primary">Berjalan</span>
                                @endif
                            </td>
                            <td>
                                @if($r->finish_time)
                                <a href="{{ route('stockCount.create', ['id'=>$encId]) }}" class="btn btn-sm btn-outline-secondary">
                                    <i data-feather="eye" class="align-middle mr-25"></i>
                                    <span class="align-middle">Detail Count</span>
                                </a>
                                @else
                                <a href="{{ route('stockCount.create', ['id'=>$encId]) }}" class="btn btn-sm btn-primary">
                                    <i data-feather="edit-3" class="align-middle mr-25"></i>
                                    <span class="align-middle">Input Count</span>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</section>

{{-- ════════════════════════════════════════════════
     AUDIT — FILTER + SEMUA BARIS COUNTING
     (accounting/superuser saja)
════════════════════════════════════════════════ --}}
@if($isAcct)

<section id="stock-count-audit-filter">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Filter</h4>
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
                        <label for="auditStoCode">STO Code</label>
                        <select class="form-control" id="auditStoCode">
                            <option value="">All</option>
                            @foreach($stoCodesForFilter as $c)
                                <option value="{{ $c->sto_code }}">{{ $c->sto_code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="auditPeriode">Periode</label>
                        <input type="month" class="form-control" id="auditPeriode">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="auditDate">STO Date (range)</label>
                        <input type="text" class="form-control flatpickr-range" id="auditDate" placeholder="DD-MM-YYYY to DD-MM-YYYY">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="auditStatus">Status</label>
                        <select class="form-control" id="auditStatus">
                            <option value="">All</option>
                            <option value="MATCH">MATCH</option>
                            <option value="NOT MATCH">NOT MATCH</option>
                            <option value="RECOUNT">RECOUNT</option>
                            <option value="INCOMPLETE">INCOMPLETE</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="auditArticleCode">Article Code</label>
                        <input type="text" class="form-control text-uppercase" id="auditArticleCode" placeholder="FGAPI0067">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="auditStoNumber">STO Number</label>
                        <input type="text" class="form-control text-uppercase" id="auditStoNumber" placeholder="COUNT-2026-VII-...">
                    </div>
                    <div class="form-group col-md-3">
    <label for="auditTarget">Lokasi / Partner</label>
    <select class="form-control" id="auditTarget">
        <option value="">All</option>
        @php
            $grouped = $targetsForFilter->groupBy('target_type');
            $groupLabels = ['LOCATION' => 'Lokasi', 'SUPPLIER' => 'Supplier', 'CUSTOMER' => 'Customer'];
        @endphp
        @foreach(['LOCATION','SUPPLIER','CUSTOMER'] as $type)
            @if($grouped->has($type))
                <optgroup label="{{ $groupLabels[$type] }}">
                    @foreach($grouped[$type] as $t)
                        <option value="{{ $t->target_ref }}">{{ $t->target_name }}</option>
                    @endforeach
                </optgroup>
            @endif
        @endforeach
    </select>
</div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="btnAuditSearch">
                            <i data-feather="search" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Search</span>
                        </button>
                        <button type="button" class="btn btn-light" id="btnAuditReset">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="stock-count-audit">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">
                List Counting
            </h4>
            <div class="heading-elements">
                <ul class="list-inline mb-0">
                    <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                    <li><a href="javascript:;" id="reloadAuditTable"><i data-feather="rotate-cw"></i></a></li>
                </ul>
            </div>
        </div>
        <div class="card-content collapse show">
            <div class="card-body">
                <button type="button" class="btn btn-primary d-none" id="btnAuditDetail" name="btnAuditDetail"
                        data-toggle="tooltip" data-placement="right"
                        title="Tekan tombol untuk melihat data detail (mentah per baris, tanpa akumulasi)">
                    Detail
                </button>
                <button type="button" class="btn btn-primary d-none" id="btnAuditSummary" name="btnAuditSummary"
                        data-toggle="tooltip" data-placement="right"
                        title="Tekan tombol untuk melihat data summary (akumulasi per artikel per lokasi)">
                    Summary
                </button>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="card-datatable table-responsive pt-0">
                            <table id="auditDtlTable" class="table">
                                <thead class="thead-light"></thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif

@endsection

@section('scripts')
<script type="text/javascript">
@if($isAcct)

let auditMode = 'summary'; // mode aktif saat ini
let btnAuditSummary = $('#btnAuditSummary');
let btnAuditDetail  = $('#btnAuditDetail');

$(document).ready(function () {
    $('#auditStoCode, #auditStatus, #auditTarget').select2({ width: '100%' });
    initDatePicker(document.querySelector('#auditDate'), {
        minDate: "01/01/2010",
        maxDate: "31/12/2030",
        dateFormat: "d-m-Y",
        mode: "range"
    });

    $('#auditDate').val('{{ $today }}');

   $(document).on('click', '.btn-rincian-sto', function () {
    let article = $(this).data('article');
    let contributors = $(this).data('contributors'); // jQuery otomatis parse JSON

    if (!contributors || contributors.length === 0) {
        Swal.fire('Tidak Ada Rincian', 'Belum ada baris STO yang menyumbang qty ini.', 'info');
        return;
    }

    let fmt = function (v) {
        if (v === null || v === undefined || v === '') return '-';
        let n = parseFloat(v);
        return isNaN(n) ? '0.00' : n.toFixed(2);
    };

    let rows = contributors.map(function (c) {
        return '<tr>'
            + '<td>' + (c.sto_number ?? '-') + '</td>'
            + '<td class="text-right">' + fmt(c.qty_counter1) + '</td>'
            + '<td class="text-right">' + fmt(c.qty_counter2) + '</td>'
            + '<td class="text-right">' + fmt(c.qty_counter3) + '</td>'
            + '</tr>';
    }).join('');

    Swal.fire({
        title: 'Rincian Qty — ' + article,
        html: '<div class="table-responsive text-left">'
            + '<table class="table table-sm table-bordered mb-0">'
            + '<thead><tr><th>STO Number</th><th>C1</th><th>C2</th><th>C3</th></tr></thead>'
            + '<tbody>' + rows + '</tbody>'
            + '</table></div>',
        width: 500,
        confirmButtonText: 'Tutup'
    });
});

    dataSearchAudit('summary');

    $('#btnAuditSearch').on('click', function () {
        dataSearchAudit(auditMode);
    });

    $('#btnAuditReset').on('click', function () {
        $('#auditStoCode').val('').trigger('change');
        $('#auditPeriode').val('');
        $('#auditDate').val('');
        $('#auditStatus').val('').trigger('change');
        $('#auditArticleCode').val('');
        $('#auditStoNumber').val('');
        $('#auditTarget').val('').trigger('change');
        dataSearchAudit(auditMode);
    });

    $('#reloadAuditTable').on('click', function () {
        dataSearchAudit(auditMode);
    });

    btnAuditDetail.click(function () {
        dataSearchAudit('detail');
    });

    btnAuditSummary.click(function () {
        dataSearchAudit('summary');
    });

    function dataSearchAudit(mode) {
        btnAuditSummary.addClass('d-none');
        btnAuditDetail.addClass('d-none');
        $(".loading-spinner-container").addClass("-show");

        if (mode === 'detail') {
            loadAuditTableDetail();
        } else {
            loadAuditTableSummary();
        }
    }

    function destroyAuditTable() {
        if ($('#auditDtlTable tr').length > 0) {
            let t = $('#auditDtlTable').DataTable();
            t.destroy();
            $('#auditDtlTable tbody > tr, #auditDtlTable thead > tr').remove();
        }
    }

    function auditSearchPayload() {
        return {
            searchStoCode     : $('#auditStoCode').val(),
            searchPeriode     : $('#auditPeriode').val(),
            searchDate        : $('#auditDate').val(),
            searchStatus      : $('#auditStatus').val(),
            searchArticleCode : $('#auditArticleCode').val(),
            searchStoNumber   : $('#auditStoNumber').val(),
            searchTarget      : $('#auditTarget').val(),
        };
    }

    // ══ SUMMARY — akumulasi per artikel/lokasi, ada system/variance/status ══
    function loadAuditTableSummary() {
        destroyAuditTable();
        auditMode = 'summary';

        showDataTables({
            tableId       : "auditDtlTable",
            route         : "{{ route('stockCount.auditList') }}",
            kolom         : {!! $kolomAudit !!},
            dataSearch    : auditSearchPayload(),
            orderColumn   : [[11, 'desc']],
            excelFileName : 'stock_count_audit_summary',
            initComplete  : function () {
                let api = this.api();
                if (api.data().length > 0) {
                    btnAuditDetail.removeClass('d-none');
                    btnAuditSummary.addClass('d-none');
                }
                $(".loading-spinner-container").removeClass("-show");
                if (typeof feather !== 'undefined') feather.replace();
            }
        });
    }

    // ══ DETAIL — mentah per baris, tanpa system/variance/status ══
    function loadAuditTableDetail() {
        destroyAuditTable();
        auditMode = 'detail';

        showDataTables({
            tableId       : "auditDtlTable",
            route         : "{{ route('stockCount.auditListDetail') }}",
            kolom         : {!! $kolomAuditDetail !!},
            dataSearch    : auditSearchPayload(),
            orderColumn   : [[1, 'asc']],
            excelFileName : 'stock_count_audit_detail',
            initComplete  : function () {
                let api = this.api();
                if (api.data().length > 0) {
                    btnAuditSummary.removeClass('d-none');
                    btnAuditDetail.addClass('d-none');
                }
                $(".loading-spinner-container").removeClass("-show");
                if (typeof feather !== 'undefined') feather.replace();
            }
        });
    }
});
@endif

$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection