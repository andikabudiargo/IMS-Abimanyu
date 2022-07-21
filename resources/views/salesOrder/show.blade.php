@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="close-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusSo }}</span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <input type="text" id="article" name="article" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="orderNum">Order Number</label><small class="text-muted">  automatic</small>
                                    <input type="text" id="orderNum" name="orderNum" class="form-control disabled-el" value="{{ $header->so_code }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-basic" value="{{ $header->so_date }}" placeholder="DD-MM-YYYY" disabled/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="salesman">Salesman*</label>
                                    <select class="select2 form-control" id="salesman" name="salesman" required disabled >
                                        <option value="">Choose salesman</option>
                                        @foreach($employees as $val)
                                        <option value="{{$val->employee_id}}" {{ $val->employee_id == $header->salesman_code ? "selected" : ""}}>{{$val->employee_id}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="type">Type</label>
                                    <select class="select2 form-control" id="type" name="type" required disabled >
                                        @foreach($types as $val)
                                        <option value="{{$val}}" {{ $val == $header->order_type ? "selected" : ""}}>{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency</label>
                                    <select class="select2 form-control" id="currency" name="currency" required disabled >
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{ $val == $header->currency ? "selected" : ""}}>{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "ppn" name="ppn" value="{{ $header->ppn }}" maxlength="2" disabled />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="pph23">PPH23</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "pph23" name="pph23" value="{{ $header->pph23 }}" maxlength="2" disabled />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="poNumber">PO Number</label>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control text-uppercase" value="{{ $header->po_number }}" maxlength="40" autofocus required disabled />
                                </div>
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="cust">Customer</label>
                                    <select class="select2 form-control" id="cust" name="cust" disabled>
                                        <option value="">Choose customer</option>
                                        @foreach($custs as $val)
                                            <option value="{{$val->kode}}|{{$val->inisial}}" {{$val->kode == $header->customer_id ? "selected" : ""}}>{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled >{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive main-table">
                        <table class="table table-bordered w-100" >
                            <thead class="thead-dark">
                                <tr>
                                    <th>No</th>
                                    <th>Article Code</th>
                                    <th class="text-right">QTY Stock</th>
                                    <th class="text-right">QTY Order</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Price Jasa</th>
                                    <th class="text-right">T.Material</th>
                                    <th class="text-right">T.Service</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach( $detail as $key =>$val )
                                <tr>
                                    <td ></td>
                                    <td >{{ $val->article }} | {{ $val->article }}</td>
                                    <td class="text-right">{{ $val->uom_group =='PIECE' ? number_format($val->qty_stock ==0 ? 0 :$val->qty_stock) : number_format($val->qty_stock ==0 ? 0 :$val->qty_stock,$decimalPlaces) }}</td>
                                    <td class="text-right">{{ $val->uom_group =='PIECE' ? number_format($val->qty) : number_format($val->qty,$decimalPlaces) }} {{ $val->uom }}</td>
                                    <td class="text-right">{{ number_format($val->price) }}</td>
                                    <td class="text-right">{{ number_format($val->price_service) }}</td>
                                    <td class="text-right">{{ number_format($val->qty * $val->price) }}</td>
                                    <td class="text-right">{{ number_format($val->qty * $val->price_service) }}</td>
                                    <td class="text-right">{{ number_format(($val->qty * $val->price)+($val->qty * $val->price_service)) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <span>ROW : {{ number_format($header->sum_row) }}</span> <br>
                            <span>QTY(s) : {{ number_format($header->sum_qty,$decimalPlaces) }}</span>
                        </div>
                        <div class="col-md-4">
                            <div class="table-responsive">
                                <table class="table table-bordered w-100">
                                    <tbody>
                                        <tr>
                                            <td>Subtotal</td>
                                            <td class="text-right">{{ number_format($header->sum_amount) }}</td>
                                        </tr>
                                        <tr>
                                            <td>PPN {{ $header->ppn }}%</td>
                                            <td class="text-right">{{ number_format($header->sum_ppn) }}</td>
                                        </tr>
                                        <tr>
                                            <td>PPH23</td>
                                            <td class="text-right">{{ number_format($header->sum_pph23) }}</td>
                                        </tr>
                                        <tr>
                                            <td>NETTO</td>
                                            <td class="text-right">{{ number_format(($header->sum_amount+$header->sum_ppn)-$header->sum_pph23) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <a href="{{ route('salesOrders.index') }}" class="btn btn-success">Back</a>
                            <a href="{{ route('salesOrder.print', ['id'=>Crypt::encryptString($header->id)]) }}" target="_blank" type="button" class="btn btn-primary">
                                <i data-feather="printer"></i>
                                <span>{{ __("Print") }}</span>
                            </a>
                        </div>
                    </div>
                    <br>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approvalHistory as $val)
                            @if($val->status == true)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-success mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="check" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-danger mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="x" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
                                                <p class="card-text mb-0">{{ $val->petugas }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('styles')
<style>
    textarea {
        resize: none;
    }

    .mb-03{
        margin-bottom: 0.3rem;
    }
    
    .main-table table {
        counter-reset: rowNumber;
    }

    .main-table table tr > td:first-child{
        counter-increment: rowNumber;
    }

    .main-table table tr td:first-child::before {
        content: counter(rowNumber);
        min-width: 1em;
        margin-right: 0.5em;
    }


    

</style>
@endsection
@section('scripts')
@include('salesOrder.addArticle')
<script type="text/javascript">
    $(document).ready(function(){
        activate_angka();
        mask_thousand();
        splitArticle();
        hitungTotal();
        hitungGrandTotal();
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection