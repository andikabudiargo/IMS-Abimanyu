@extends('layouts.app')
@section('title', 'Home')
@section('content')
@include('layouts.breadcrumb')
<section id="home">

    {{-- ===== Row 1: Delivery Performance (8) + Greeting (4) ===== --}}
    <div class="form-row">
        <div class="col-lg-8 col-12">
            <div class="card h-100 mb-0" style="border-left:4px solid #7367F0;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-light-primary p-50 mr-1" style="border-radius:8px;">
                            <i data-feather="target" class="font-medium-3 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="card-title mb-0 d-flex align-items-center">
                                Delivery Performance
                            </h4>
                            <small class="text-muted">Delivery vs Target SO &mdash; <span id="saMonthLabel">{{ $salesAchievement['monthLabel'] }}</span></small>
                        </div>
                    </div>
                    <div class="d-flex align-items-end flex-wrap" style="gap:.5rem;">
                        <div>
                            <label class="mb-0 small d-block">Periode</label>
                            <select id="saPeriode" class="form-control form-control-sm">
                                @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $m)
                                    <option value="{{ $i + 1 }}" {{ $salesAchievement['periode'] == $i + 1 ? 'selected' : '' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-0 small d-block">Tahun</label>
                            <select id="saTahun" class="form-control form-control-sm">
                                @for($y = (int) date('Y') + 1; $y >= (int) date('Y') - 3; $y--)
                                    <option value="{{ $y }}" {{ $salesAchievement['tahun'] == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <button type="button" id="saApply" class="btn btn-primary btn-sm">
                            <i data-feather="filter"></i> Terapkan
                        </button>
                        <a href="{{ $salesAchievement['targetSoUrl'] }}" id="saTargetSoLink" class="btn btn-outline-primary btn-sm">
                            <i data-feather="external-link"></i> Target SO
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="saEmpty" class="text-center text-muted py-2" style="{{ $salesAchievement['hasTarget'] ? 'display:none;' : '' }}">
                        <i data-feather="info" class="mr-25"></i> Belum ada Target SO yang APPROVED untuk periode <span id="saEmptyLabel">{{ $salesAchievement['monthLabel'] }}</span>.
                    </div>
                    <div id="saContent" style="{{ $salesAchievement['hasTarget'] ? '' : 'display:none;' }}">
                        <div class="d-flex justify-content-between align-items-center mb-50">
                            <span class="font-weight-bold"><i data-feather="truck" class="font-medium-1 mr-25"></i> Qty Delivery</span>
                            <span class="font-weight-bold"><span id="saAchievedQty">{{ number_format($salesAchievement['achievedQty'], 0) }}</span>
                                <span class="text-muted font-weight-normal">/ <span id="saTargetQty">{{ number_format($salesAchievement['targetQty'], 0) }}</span> PCS Target</span>
                            </span>
                        </div>
                        <div class="progress mb-2" style="height:20px;border-radius:10px;">
                            <div class="progress-bar {{ $salesAchievement['qtyPct'] >= 100 ? 'bg-success' : 'bg-primary' }}" role="progressbar" id="saProgressBar"
                                 style="width: {{ min($salesAchievement['qtyPct'], 100) }}%;"
                                 aria-valuenow="{{ $salesAchievement['qtyPct'] }}" aria-valuemin="0" aria-valuemax="100">
                                <span id="saProgressText">{{ number_format($salesAchievement['qtyPct'], 1) }}%</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-body d-flex align-items-center p-1">
                                        <div class="avatar bg-light-secondary p-50 mr-1" style="border-radius:8px;">
                                            <i data-feather="flag" class="font-medium-3 text-secondary"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0 font-weight-bolder" id="saTargetConversion">{{ number_format($salesAchievement['targetConversion'], 2) }}</h4>
                                            <small class="text-muted">Target Konversi (Painting)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="card border shadow-none mb-0" style="border-color:#28c76f33 !important;background:#28c76f0d;">
                                    <div class="card-body d-flex align-items-center p-1">
                                        <div class="avatar bg-light-success p-50 mr-1" style="border-radius:8px;">
                                            <i data-feather="trending-up" class="font-medium-3 text-success"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0 font-weight-bolder text-success" id="saAchievedConversion">{{ number_format($salesAchievement['achievedConversion'], 2) }}</h4>
                                            <small class="text-muted">Konversi Painting Tercapai (<span id="saConvPct">{{ number_format($salesAchievement['conversionPct'], 1) }}</span>%)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="card h-100 mb-0">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center">
                    <img class="rounded-circle" src="{{ Auth::user()->filename ? asset(Auth::user()->filename) : asset('app-assets/images/avatars/default.png') }}"
                         onerror="this.src='{{ asset('app-assets/images/avatars/default.png') }}';"
                         alt="avatar" height="72" width="72">
                    <h4 class="mt-1 mb-0 font-weight-bold">{{ $greeting }}, {{ Auth::user()->name }}</h4>
                    @if($deptNames)
                        <p class="text-muted mb-0">{{ $deptNames }}</p>
                    @endif
                    <small class="text-muted">{{ $tanggal }}</small>
                    @if (session('status'))
                        <div class="alert alert-success w-100 mt-1 mb-0 py-50" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Row 2: Action Center ===== --}}
    <div class="form-row">
        <div class="col-12">
            <h4 class="font-weight-bold mb-0">Action Center</h4>
            <p class="text-muted">Quick access to approve or reject submission.</p>
            <div style="width:48px;height:3px;background:#7367F0;border-radius:2px;" class="mb-1"></div>

            @if($actionCenterCount == 0)
                <div class="text-muted mb-1">
                    <i data-feather="check-circle" class="font-small-4 mr-25"></i> Tidak ada approval yang perlu diproses saat ini.
                </div>
            @endif

            @if( count($listPoHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acPo" role="button" aria-expanded="false" aria-controls="acPo">
                    <div class="d-flex align-items-center">
                        <i data-feather="shopping-cart" class="mr-1"></i>
                        <strong>PO Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listPoHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acPo">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Supplier</th>
                                        <th>PO Number</th>
                                        <th>PO Date</th>
                                        <th>Amount</th>
                                        <th>Created By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listPoHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->supplier_id }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->po_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->po_date }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">{{ number_format($val->po_amount) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailPoHome{{ $key }}"
                                                name="cmdDetailPoHome{{ $key }}"
                                                href="{{ route('purchaseOrder.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'btnDeclinePoHome{{ $key }}'
                                                class="btn btn-outline-danger btn-sm  buttonPoDecline-{{ $val->id }}"
                                                data-id-class = "buttonPo-{{ $val->id }}"
                                                data-id-class-decline = "buttonPoDecline-{{ $val->id }}"
                                                data-doc-number='{{ $val->po_number }}'
                                                data-url='{{ route("purchaseOrder.decline", ["poNumber"=>$val->po_number]) }}'>
                                                <i data-feather='x-circle'></i>
                                                Decline
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonPoHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonPo-{{ $val->id }}"
                                                data-id-class = "buttonPo-{{ $val->id }}"
                                                data-id-class-decline = "buttonPoDecline-{{ $val->id }}"
                                                data-doc-number='{{ $val->po_number }}'
                                                data-url='{{ route("purchaseOrder.approve", ["poNumber"=>$val->po_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listBomHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acBom" role="button" aria-expanded="false" aria-controls="acBom">
                    <div class="d-flex align-items-center">
                        <i data-feather="layers" class="mr-1"></i>
                        <strong>BOM Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listBomHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acBom">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Customer</th>
                                        <th>Bom Number</th>
                                        <th>Article FG</th>
                                        <th>Article RM</th>
                                        <th>Approved</th>
                                        <th>Created By</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listBomHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->customer_name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->bom_code }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->article_fg }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->article_rm }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailBomHome{{ $key }}"
                                                name="cmdDetailBomHome{{ $key }}"
                                                href="{{ route('bom.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonBomHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonBom-{{ $val->id }}"
                                                data-id-class-decline = "buttonBomDecline-{{ $val->id }}"
                                                data-id-class = "buttonBom-{{ $val->id }}"
                                                data-doc-number='{{ $val->bom_code }}'
                                                data-url='{{ route("bom.approve", ["bomNumber"=>$val->bom_code]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listPrHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acPr" role="button" aria-expanded="false" aria-controls="acPr">
                    <div class="d-flex align-items-center">
                        <i data-feather="file-text" class="mr-1"></i>
                        <strong>PR Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listPrHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acPr">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>PR Number</th>
                                        <th>Order Type</th>
                                        <th>Pr Date</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Note</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listPrHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->pr_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->order_type }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->date }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <a class="btn btn-outline-info btn-sm"
                                                        id="cmdDetailPrHome{{ $key }}"
                                                        name="cmdDetailPrHome{{ $key }}"
                                                        href="{{ route('purchaseRequest.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                        <i data-feather='list'></i>
                                                        Detail
                                                    </a>
                                                    <a href='javascript:;'
                                                        onclick="action(this)"
                                                        id = 'buttonPrHome{{ $key }}'
                                                        class="btn btn-outline-success btn-sm buttonPr-{{ $val->id }}"
                                                        data-id-class = "buttonPr-{{ $val->id }}"
                                                        data-doc-number='{{ $val->pr_number }}'
                                                        data-url='{{ route("purchaseRequest.approve", ["prNumber"=>$val->pr_number]) }}'>
                                                        <i data-feather='check-circle'></i>
                                                        Approve
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listSoHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acSo" role="button" aria-expanded="false" aria-controls="acSo">
                    <div class="d-flex align-items-center">
                        <i data-feather="shopping-bag" class="mr-1"></i>
                        <strong>SO Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listSoHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acSo">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>SO Code</th>
                                        <th>SO Date</th>
                                        <th>PO Number</th>
                                        <th>Customer</th>
                                        <th>Note</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listSoHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->so_code }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->so_date }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->po_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->customer_name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailSoHome{{ $key }}"
                                                name="cmdDetailSoHome{{ $key }}"
                                                href="{{ route('salesOrder.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonSoHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonSo-{{ $val->id }}"
                                                data-id-class = "buttonSo-{{ $val->id }}"
                                                data-doc-number='{{ $val->so_code }}'
                                                data-url='{{ route("salesOrder.approve", ["soCode"=>$val->so_code]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listTsoHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acTso" role="button" aria-expanded="false" aria-controls="acTso">
                    <div class="d-flex align-items-center">
                        <i data-feather="calendar" class="mr-1"></i>
                        <strong>TSO Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listTsoHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acTso">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>TSO Code</th>
                                        <th>Desc</th>
                                        <th>Date</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Note</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listTsoHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->tso_code }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->tso_name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->tso_date }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailTsoHome{{ $key }}"
                                                name="cmdDetailTsoHome{{ $key }}"
                                                href="{{ route('targetSo.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonTsoHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonTso-{{ $val->id }}"
                                                data-id-class = "buttonTso-{{ $val->id }}"
                                                data-doc-number='{{ $val->tso_code }}'
                                                data-url='{{ route("targetSo.approve", ["tsoCode"=>$val->tso_code]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listDnHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acDn" role="button" aria-expanded="false" aria-controls="acDn">
                    <div class="d-flex align-items-center">
                        <i data-feather="truck" class="mr-1"></i>
                        <strong>Delivery Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listDnHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acDn">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>DN Number</th>
                                        <th>PO Number</th>
                                        <th>Date</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Note</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listDnHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->delivery_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->po_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->delivery_date }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailDnHome{{ $key }}"
                                                name="cmdDetailDnHome{{ $key }}"
                                                href="{{ route('delivery.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonDnHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonDn-{{ $val->id }}"
                                                data-id-class = "buttonDn-{{ $val->id }}"
                                                data-doc-number='{{ $val->delivery_number }}'
                                                data-url='{{ route("delivery.notif.approve", ["dnNumber"=>$val->delivery_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listRecHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acRec" role="button" aria-expanded="false" aria-controls="acRec">
                    <div class="d-flex align-items-center">
                        <i data-feather="package" class="mr-1"></i>
                        <strong>Receiving Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listRecHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acRec">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Rec Number</th>
                                        <th>PO Number</th>
                                        <th>Rec Date</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Note</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listRecHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->rec_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->po_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->rec_date }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailRecHome{{ $key }}"
                                                name="cmdDetailRecHome{{ $key }}"
                                                href="{{ route('receiving.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonRecHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonRec-{{ $val->id }}"
                                                data-id-class = "buttonRec-{{ $val->id }}"
                                                data-doc-number='{{ $val->rec_number }}'
                                                data-url='{{ route("receiving.notif.approve", ["recNumber"=>$val->rec_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listBkHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acBk" role="button" aria-expanded="false" aria-controls="acBk">
                    <div class="d-flex align-items-center">
                        <i data-feather="arrow-up-circle" class="mr-1"></i>
                        <strong>Bank Keluar Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listBkHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acBk">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Voucher Number</th>
                                        <th>Note</th>
                                        <th>Description</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listBkHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->voucher_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->description }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailBkHome{{ $key }}"
                                                name="cmdDetailBkHome{{ $key }}"
                                                href="{{ route('bankKeluar.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonBkHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonBk-{{ $val->id }}"
                                                data-id-class = "buttonBk-{{ $val->id }}"
                                                data-doc-number='{{ $val->voucher_number }}'
                                                data-url='{{ route("bankKeluar.notif.approve", ["vcNumber"=>$val->voucher_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listBmHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acBm" role="button" aria-expanded="false" aria-controls="acBm">
                    <div class="d-flex align-items-center">
                        <i data-feather="arrow-down-circle" class="mr-1"></i>
                        <strong>Bank Masuk Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listBmHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acBm">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Voucher Number</th>
                                        <th>Note</th>
                                        <th>Description</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listBmHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->voucher_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->description }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailBmHome{{ $key }}"
                                                name="cmdDetailBmHome{{ $key }}"
                                                href="{{ route('bankPenerimaan.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonBmHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonBm-{{ $val->id }}"
                                                data-id-class = "buttonBm-{{ $val->id }}"
                                                data-doc-number='{{ $val->voucher_number }}'
                                                data-url='{{ route("bankPenerimaan.notif.approve", ["vcNumber"=>$val->voucher_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listKmHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acKm" role="button" aria-expanded="false" aria-controls="acKm">
                    <div class="d-flex align-items-center">
                        <i data-feather="dollar-sign" class="mr-1"></i>
                        <strong>Kas Masuk Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listKmHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acKm">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Voucher Number</th>
                                        <th>Note</th>
                                        <th>Description</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listKmHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->voucher_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->description }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailKmHome{{ $key }}"
                                                name="cmdDetailKmHome{{ $key }}"
                                                href="{{ route('kasPenerimaan.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonKmHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonKm-{{ $val->id }}"
                                                data-id-class = "buttonKm-{{ $val->id }}"
                                                data-doc-number='{{ $val->voucher_number }}'
                                                data-url='{{ route("kasPenerimaan.notif.approve", ["vcNumber"=>$val->voucher_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listKkHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acKk" role="button" aria-expanded="false" aria-controls="acKk">
                    <div class="d-flex align-items-center">
                        <i data-feather="minus-circle" class="mr-1"></i>
                        <strong>Kas Keluar Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listKkHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acKk">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Voucher Number</th>
                                        <th>Total</th>
                                        <th>Note</th>
                                        <th>Description</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listKkHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->voucher_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ number_format($val->amount,2) }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->description }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailKkHome{{ $key }}"
                                                name="cmdDetailKkHome{{ $key }}"
                                                href="{{ route('kasKeluar.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonKkHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonKk-{{ $val->id }}"
                                                data-id-class = "buttonKk-{{ $val->id }}"
                                                data-doc-number='{{ $val->voucher_number }}'
                                                data-url='{{ route("kasKeluar.notif.approve", ["vcNumber"=>$val->voucher_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listGjHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acGj" role="button" aria-expanded="false" aria-controls="acGj">
                    <div class="d-flex align-items-center">
                        <i data-feather="book" class="mr-1"></i>
                        <strong>General Journal Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listGjHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acGj">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Voucher Number</th>
                                        <th>Note</th>
                                        <th>Description</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listGjHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->voucher_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->description }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailGjHome{{ $key }}"
                                                name="cmdDetailGjHome{{ $key }}"
                                                href="{{ route('jurnalUmum.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonGjHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonGj-{{ $val->id }}"
                                                data-id-class = "buttonGj-{{ $val->id }}"
                                                data-doc-number='{{ $val->voucher_number }}'
                                                data-url='{{ route("jurnalUmum.notif.approve", ["vcNumber"=>$val->voucher_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listApHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acAp" role="button" aria-expanded="false" aria-controls="acAp">
                    <div class="d-flex align-items-center">
                        <i data-feather="file" class="mr-1"></i>
                        <strong>Invoice Supplier Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listApHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acAp">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>AP Number</th>
                                        <th>AP Date</th>
                                        <th>PO Number</th>
                                        <th>Note</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listApHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->ap_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->ap_date }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->po_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailApHome{{ $key }}"
                                                name="cmdDetailApHome{{ $key }}"
                                                href="{{ route('accountPayable.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonApHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonAp-{{ $val->id }}"
                                                data-id-class = "buttonAp-{{ $val->id }}"
                                                data-doc-number='{{ $val->ap_number }}'
                                                data-url='{{ route("accountPayable.notif.approve", ["apNumber"=>$val->ap_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listArHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acAr" role="button" aria-expanded="false" aria-controls="acAr">
                    <div class="d-flex align-items-center">
                        <i data-feather="file-plus" class="mr-1"></i>
                        <strong>Invoice Customer Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listArHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acAr">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Invoice Number</th>
                                        <th>Invoece Date</th>
                                        <th>SO Number</th>
                                        <th>Note</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listArHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->invoice_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->invoice_date }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->so_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailArHome{{ $key }}"
                                                name="cmdDetailArHome{{ $key }}"
                                                href="{{ route('invoice.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonArHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonAr-{{ $val->id }}"
                                                data-id-class = "buttonAr-{{ $val->id }}"
                                                data-doc-number='{{ $val->invoice_number }}'
                                                data-url='{{ route("invoice.notif.approve", ["invNumber"=>$val->invoice_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if( count($listDebNoteHome)>0 )
            <div class="card mb-1 border-0 shadow-sm action-center-item">
                <div class="card-header d-flex justify-content-between align-items-center collapsed" data-toggle="collapse" href="#acDebitNote" role="button" aria-expanded="false" aria-controls="acDebitNote">
                    <div class="d-flex align-items-center">
                        <i data-feather="file-minus" class="mr-1"></i>
                        <strong>Debit Note Needs to be Approved</strong>
                        <div class="badge badge-pill badge-light-primary ml-1">{{ count($listDebNoteHome) }}</div>
                    </div>
                    <i data-feather="chevron-down" class="ac-chevron"></i>
                </div>
                <div class="collapse" id="acDebitNote">
                    <div class="card-body">
                        <div class="table-responsive" style="max-height:300px">
                            <table class="table" width="100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>DN Number</th>
                                        <th>DN Date</th>
                                        <th>PO Number</th>
                                        <th>Note</th>
                                        <th>Approved</th>
                                        <th>Created_by</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listDebNoteHome as $key=>$val)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $key+1 }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->dn_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->dn_date }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->po_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->note }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            #Approved: {{ $val->current_level }} of {{ $val->max_level }}
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->created_by }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->status }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a class="btn btn-outline-info btn-sm"
                                                id="cmdDetailDebitNoteHome{{ $key }}"
                                                name="cmdDetailDebitNoteHome{{ $key }}"
                                                href="{{ route('debitNote.edit', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'buttonDebitNoteHome{{ $key }}'
                                                class="btn btn-outline-success btn-sm buttonAp-{{ $val->id }}"
                                                data-id-class = "buttonAp-{{ $val->id }}"
                                                data-doc-number='{{ $val->dn_number }}'
                                                data-url='{{ route("debitNote.notif.approve", ["debitNnumber"=>$val->dn_number]) }}'>
                                                <i data-feather='check-circle'></i>
                                                Approve
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- ===== Row 3: Information Center ===== --}}
    <div class="form-row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <strong><i data-feather="info" class="font-small-4 mr-25"></i> Information Center</strong>
                </div>
                <div class="card-body">

                            @if( $listCriticalStock && $criticalStockCount>0 )
                            <div class="form-row">
                                <div class="col-lg-12 col-12">
                                    <div class="card border-danger">
                                        <div class="card-header" style="color:#EA5455">
                                            <strong>
                                                Critical Stock Alert
                                                <div class="badge badge-pill badge-danger">{{ $criticalStockCount }}</div>
                                            </strong>
                                            <div class="ml-auto">
                                                <a class="btn btn-outline-primary btn-sm" href="{{ route('warehouse.articlev2') }}">
                                                    <i data-feather='package'></i>
                                                    Lihat Stock
                                                </a>
                                                <a class="btn btn-danger btn-sm" href="{{ route('purchaseRequest.create') }}">
                                                    <i data-feather='plus-circle'></i>
                                                    Buat PR
                                                </a>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                           <div class="table-responsive" style="max-height:300px; overflow-y:auto;">
                <table class="table table-hover mb-0" width="100%">
                    <thead style="position: sticky; top: 0; z-index: 10; background: #fff;">
                        <tr>
                            <th>No</th>
                            <th>Location</th>
                            <th>Code</th>
                            <th>Article</th>
                            <th>Supplier/Customer</th>
                            <th>Stock</th>
                            <th>Safety Stock</th>
                            <th>UOM</th>
                            <th>Min Package</th>
                            <th>Action</th>
                        </tr>
                    </thead>
               <tbody>
                @php
                    $lastLocation = '';
                @endphp

                @foreach($listCriticalStock as $key => $val)

                    @if($lastLocation != $val->location_name)
                        <tr style="background:#e8f4fd;">
                            <td colspan="10" class="font-weight-bold text-primary">
                                {{ $val->location_name }}
                            </td>
                        </tr>

                        @php
                            $lastLocation = $val->location_name;
                        @endphp
                    @endif

                    <tr>
                        <td>{{ $key+1 }}</td>

                        <td class="font-weight-bold">{{ $val->location_name }}</td>

                        <td class="font-weight-bold">
                            {{ $val->code }}
                        </td>

                        <td class="font-weight-bold">
                            {{ $val->name }}
                        </td>

                        <td>{{ $val->supplier_name }}</td>

                        <td class="text-right text-danger font-weight-bolder">
                            {{ number_format($val->stock_qty) }}
                        </td>

                        <td class="text-right">
                            {{ number_format($val->safety_stock) }}
                        </td>

                        <td>{{ $val->uom }}</td>

                        <td class="text-right">{{ number_format($val->min_package) }}</td>

                        <td>
                            <a class="btn btn-outline-info btn-sm"
                               href="{{ route('warehouse.article') }}?code={{ $val->code }}">
                                <i data-feather="eye"></i>
                                Detail
                            </a>
                        </td>
                    </tr>

                @endforeach
            </tbody>
            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                            @endif

                            @if( $outstandingTransferInCount>0 )
                            <div class="form-row">
                                <div class="col-lg-12 col-12">
                                    <div class="card border-warning">
                                        <div class="card-header" style="color:#d98a0b">
                                            <strong>
                                                Transfer Stock Perlu Diposting
                                                <div class="badge badge-pill badge-warning">{{ $outstandingTransferInCount }}</div>
                                            </strong>
                                            <div class="ml-auto">
                                                <a class="btn btn-outline-primary btn-sm" href="{{ route('transferStock.index') }}">
                                                    <i data-feather='list'></i>
                                                    Lihat Semua Transfer
                                                </a>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table" width="100%" id="tblTransferPerluDiposting">
                                                    <thead>
                                                        <tr>
                                                            <th>No</th>
                                                            <th>Transfer Number</th>
                                                            <th>Date</th>
                                                            <th>From</th>
                                                            <th>To</th>
                                                            <th>Created By</th>
                                                            <th>Penerima</th>
                                                            <th>Pending</th>
                                                            <th data-orderable="false" data-searchable="false">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($outstandingTransferIn as $key=>$val)
                                                        <tr>
                                                            <td>{{ $key+1 }}</td>
                                                            <td class="font-weight-bolder">{{ $val->tr_number }}</td>
                                                            <td>{{ $val->tr_date }}</td>
                                                            <td>{{ $val->location_name }}</td>
                                                            <td class="font-weight-bolder">{{ $val->location_name_to }}</td>
                                                            <td>{{ $val->created_by }}</td>
                                                            <td>{{ $val->penerima }}</td>
                                                            <td data-order="{{ $val->age_seconds ?? 0 }}">
                                                                <span class="badge badge-{{ $val->aging_level }}">{{ $val->aging_label }}</span>
                                                            </td>
                                                            <td>
                                                                <a class="btn btn-outline-info btn-sm"
                                                                    href="{{ route('transferStock.show', ['id'=>Crypt::encryptString($val->id)]) }}">
                                                                    <i data-feather='list'></i>
                                                                    Detail
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if( $outstandingPoCount>0 )
                            <div class="form-row">
                                <div class="col-lg-12 col-12">
                                    <div class="card border-warning">
                                        <div class="card-header" style="color:#d98a0b">
                                            <strong>Outstanding PO <div class="badge badge-pill badge-warning">{{ $outstandingPoCount }}</div></strong>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive" style="max-height:300px">
                                                <table class="table" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>No</th>
                                                            <th>PR Number</th>
                                                            <th>PO Number</th>
                                                            <th>Dept</th>
                                                            <th>Supplier</th>
                                                            <th>PO Date</th>
                                                            <th>Status</th>
                                                            <th>Current Approval</th>
                                                            <th>Need Approval</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($outstandingPo as $key=>$val)
                                                        <tr>
                                                            <td>{{ $key+1 }}</td>
                                                            <td>
                                                                <a href="{{ route('purchaseRequest.edit', ['id'=>Crypt::encryptString($val->pr_id)]) }}">{{ $val->pr_number }}</a>
                                                            </td>
                                                            <td>{{ $val->po_number }}</td>
                                                            <td>{{ $val->dept_name }}</td>
                                                            <td>{{ $val->supplier_name }}</td>
                                                            <td>{{ $val->po_date }}</td>
                                                            <td><div class="badge badge-info">{{ $val->status_label }}</div></td>
                                                            <td class="text-right">{{ $val->current_level }} of {{ $val->max_level }}</td>
                                                            <td>{{ $val->need_approval_names ?? '-' }}</td>
                                                            <td>
                                                                <a class="btn btn-outline-info btn-sm"
                                                                    href="{{ route('purchaseOrder.edit', ['id'=>Crypt::encryptString($val->po_id)]) }}">
                                                                    <i data-feather='list'></i>
                                                                    Detail
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if(count($listBom)>0)
                            <div class="form-row">
                                <div class="col-lg-12 col-12">
                                    <div class="card">
                                        <div class="card-header" style="color:#2FA07E"><strong>BOM has been approved for the past two weeks <div class="badge badge-pill badge-info"> {{ count($listBom) }}</div></strong></div>
                                        <div class="card-body" >
                                            <div class="tableFixHead" >
                                                <table class="table">
                                                    <thead>
                                                        <tr>
                                                            <th>Bom</th>
                                                            <th>Article FG</th>
                                                            <th>Article Desc</th>
                                                            <th>Customer</th>
                                                            <th>Customer Name</th>
                                                            <th>Note</th>
                                                            <th>Created At</th>
                                                            <th>Updated At</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($listBom as $key=>$val)
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div>
                                                                        <div class="font-weight-bolder">{{ $val->bom_code }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div>
                                                                        <div class="font-weight-bolder">{{ $val->article_code }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div>
                                                                        <div class="font-weight-bolder">{{ $val->article_name }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="">{{ $val->customer }}</td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div>
                                                                        <div class="font-weight-bolder">{{ $val->customer_name }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div>
                                                                        <div class="font-weight-bolder">{{ $val->note }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div>
                                                                        <div class="font-weight-bolder">{{ $val->created_at }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div>
                                                                        <div class="font-weight-bolder">{{ $val->updated_at }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                </div>
            </div>
        </div>
    </div>

</section>
@endsection

@section('styles')
<style>

    .tableFixHead {
        overflow-y: auto; /* make the table scrollable if height is more than 200 px  */
        height: 300px; /* gives an initial height of 200px to the table */
    }
    .tableFixHead thead th {
        position: sticky; /* make the table heads sticky */
        top: 0px; /* table head will be placed from the top of the table and sticks to it */
    }
    table {
        border-collapse: collapse; /* make the table borders collapse to each other */
        width: 100%;
    }

    .action-center-item .card-header {
        cursor: pointer;
        background-color: #7367F0;
        color: #fff;
        border-radius: .357rem;
    }
    .action-center-item .card-header.collapsed {
        border-radius: .357rem;
    }
    .action-center-item .ac-chevron {
        transition: transform .2s ease;
        transform: rotate(180deg);
    }
    .action-center-item .card-header.collapsed .ac-chevron {
        transform: rotate(0deg);
    }
    .action-center-item .badge-light-primary {
        color: #7367F0;
        background-color: #fff;
    }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    if ("{{ Session::get('firstLogin') }}" == "success"){
        setTimeout(function () {
            show_msg('👋 {{ $greeting }},{{  strtoupper(Auth::user()->name) }}!', 'You have successfully logged in to IMS!', 'success');
            "{{ Session::forget('firstLogin') }}";
        }, 3000);
    }

    @if($outstandingTransferInCount>0)
    $('#tblTransferPerluDiposting').DataTable({
        dom: "<'row'<'col-sm-6'B><'col-sm-6'f>>rt<'row'<'col-sm-6'l'><'col-sm-6'p>>",
        order: [[7, 'desc']],
        lengthMenu: [[10, 50, 100, -1], [10, 50, 100, 'All']],
        pageLength: 10,
        language: { search: '', searchPlaceholder: 'Cari...' },
        buttons: [
            {
                extend: 'excelHtml5',
                text: feather.icons['file-text'].toSvg({ class: 'font-small-4 mr-50' }) + 'Export Excel',
                className: 'btn btn-outline-primary btn-sm',
                filename: 'transfer_stock_perlu_diposting',
                exportOptions: { columns: [0,1,2,3,4,5,6,7] }
            }
        ]
    });
    @endif

    action=(me)=>{
        let meId=me.getAttribute('id'),
        meDocNumber=me.getAttribute("data-doc-number"),
        meUrl=me.getAttribute("data-url"),
        meClassId = me.getAttribute("data-id-class");
        meClassIdDecline = me.getAttribute("data-id-class-decline");

        fetch(meUrl, {
            method: "GET",
            headers: {"Content-type": "application/json;charset=UTF-8"}
        })
        .then(response => response.json())
        .then((responseData) => {
            const ele = document.getElementsByClassName(meClassId);
            if (ele){
                for (let i=0; i< ele.length; i++ ) {
                    const idButtoHide = document.getElementById(ele[i].id);
                    if (idButtoHide){
                        idButtoHide.classList.add('d-none');
                    }
                }
            }

            const eleDecline = document.getElementsByClassName(meClassIdDecline);
            if (eleDecline){
                for (let i=0; i< eleDecline.length; i++ ) {
                    const idButtoHideDecline = document.getElementById(eleDecline[i].id);
                    if (idButtoHideDecline){
                        idButtoHideDecline.classList.add('d-none');
                    }
                }
            }

            show_msg(responseData.title, responseData.message, responseData.alert);
        })
        .catch(err => console.log(err));
    }

    // ---- Widget Sales Achievement: filter periode/tahun ----
    (function () {
        const URL_SALES_ACHIEVEMENT = "{{ route('home.salesAchievement') }}";
        const $btn = $('#saApply');
        if (!$btn.length) return;

        function humanizeSa(n, decimals) {
            n = parseFloat(n) || 0;
            return n.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        }

        $btn.on('click', function () {
            const periode = $('#saPeriode').val();
            const tahun   = $('#saTahun').val();

            $btn.prop('disabled', true);
            $.get(URL_SALES_ACHIEVEMENT, { periode: periode, tahun: tahun }, function (res) {
                if (!res || res.status !== 1) return;
                const d = res.data;

                $('#saMonthLabel, #saEmptyLabel').text(d.monthLabel);
                $('#saTargetSoLink').attr('href', d.targetSoUrl);

                if (!d.hasTarget) {
                    $('#saContent').hide();
                    $('#saEmpty').show();
                    $('#saQtyBadge').hide();
                    return;
                }

                $('#saEmpty').hide();
                $('#saContent').show();
                $('#saQtyBadge').show();

                $('#saAchievedQty').text(humanizeSa(d.achievedQty, 0));
                $('#saTargetQty').text(humanizeSa(d.targetQty, 0));
                $('#saTargetConversion').text(humanizeSa(d.targetConversion, 2));
                $('#saAchievedConversion').text(humanizeSa(d.achievedConversion, 2));
                $('#saConvPct').text(humanizeSa(d.conversionPct, 1));
                $('#saQtyBadgeText').text(humanizeSa(d.qtyPct, 1) + '% Qty');

                const pct = Math.min(d.qtyPct, 100);
                const $bar = $('#saProgressBar');
                $bar.css('width', pct + '%').attr('aria-valuenow', d.qtyPct);
                $('#saProgressText').text(humanizeSa(d.qtyPct, 1) + '%');

                $bar.removeClass('bg-success bg-primary').addClass(d.qtyPct >= 100 ? 'bg-success' : 'bg-primary');
                $('#saQtyBadge').removeClass('badge-success badge-info badge-warning')
                    .addClass(d.qtyPct >= 100 ? 'badge-success' : (d.qtyPct >= 75 ? 'badge-info' : 'badge-warning'));
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    })();
</script>
@endsection
