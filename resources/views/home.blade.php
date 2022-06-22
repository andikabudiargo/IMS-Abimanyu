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
    
</section>
@endsection

@section('styles')
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

