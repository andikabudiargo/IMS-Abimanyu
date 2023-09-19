@extends('layouts.app')
@section('title', 'Home')
@section('content')
@include('layouts.breadcrumb')
<section id="home">
    @if ( Session::get('firstLogin') == "success")
    <div class="form-row">
        <div class="col-md-12">
            <div class="card card-transparent">
                <div class="card-header"></div>
                <div class="card-body">
                    <h2 class="font-weight-bold">{{ $greeting }}, {{ strtoupper(Auth::user()->name) }}!</h2>
                    <h4>{{ $tanggal }}</h4>
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    
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
                                        <td class="text-right">{{ $val->customer }}</td>
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
            <!--/ Company Table Card -->
        </div>
    @endif
    @if( count($listPoHome)>0 )
        <div class="form-row">
            <!-- Company Table Card -->
            <div class="col-lg-12 col-12">
                <div class="card">
                    <div class="card-header">PO that must be approve </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
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
            <!--/ Company Table Card -->
        </div>
    @endif
    @if( count($listBomHome)>0 )
        <div class="form-row">
            <!-- Company Table Card -->
            <div class="col-lg-12 col-12">
                <div class="card">
                    <div class="card-header">BOM that must be approve </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
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
                                                href="{{ route('bom.show', ['id'=>Crypt::encryptString($val->id)]) }}"> 
                                                <i data-feather='list'></i>
                                                Detail
                                            </a>
                                            {{-- <a href='javascript:;'
                                                onclick="action(this)"
                                                id = 'btnDeclineBomHome{{ $key }}'
                                                class="btn btn-outline-danger btn-sm  buttonBomDecline-{{ $val->id }}"
                                                data-id-class-decline = "buttonBomDecline-{{ $val->id }}"
                                                data-id-class = "buttonBom-{{ $val->id }}"
                                                data-doc-number='{{ $val->bom_code }}'
                                                data-url='{{ route("bom.approve", ["bomNumber"=>$val->bom_code]) }}'>
                                                <i data-feather='x-circle'></i>
                                                Decline
                                            </a> --}}
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
            <!--/ Company Table Card -->
        </div>
    @endif
    @if( count($listPrHome)>0 )
        <div class="form-row">
            <!-- Company Table Card -->
            <div class="col-lg-12 col-12">
                <div class="card">
                    <div class="card-header">PR that must be approve </div>
                    <div class="card-body p-0">
                        <div class="tableFixHead">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>PR Number</th>
                                        <th>Order Type</th>
                                        {{-- <th>Department</th> --}}
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
                                        {{-- <td>
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <div class="font-weight-bolder">{{ $val->dept }}</div>
                                                </div>
                                            </div>
                                        </td> --}}
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
                                                        href="{{ route('purchaseRequest.show', ['id'=>Crypt::encryptString($val->id)]) }}"> 
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
            <!--/ Company Table Card -->
        </div>
    @endif
    @if( count($listSoHome)>0 )
        <div class="form-row">
            <!-- Company Table Card -->
            <div class="col-lg-12 col-12">
                <div class="card">
                    <div class="card-header">SO that must be approve </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
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
                                                href="{{ route('salesOrder.show', ['id'=>Crypt::encryptString($val->id)]) }}"> 
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
            <!--/ Company Table Card -->
        </div>
    @endif
    @if( count($listTsoHome)>0 )
        <div class="form-row">
            <!-- Company Table Card -->
            <div class="col-lg-12 col-12">
                <div class="card">
                    <div class="card-header">TSO that must be approve </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
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
                                                href="{{ route('targetSo.show', ['id'=>Crypt::encryptString($val->id)]) }}"> 
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
            <!--/ Company Table Card -->
        </div>
    @endif
    
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
    /* th,
    td {
    padding: 8px 16px;
    border: 1px solid #ccc;
    }
    th {
    background: #eee;
    } */
    
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
</script>
@endsection

