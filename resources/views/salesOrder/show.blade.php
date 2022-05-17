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
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('salesOrder.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                                @foreach ($detail as $key =>$item)
                                    <div id="new_row{{ $key }}" class="tanda-baris" >
                                        <div class="form-row ">
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label class="d-block d-md-none">Status</label>
                                                    <div class="custom-control custom-switch custom-control-inline">
                                                        <input type="checkbox" class="custom-control-input soClose" id="status{{ $key }}" name ="status[]" {{  $item->status == '1' ? 'checked' :'' }} disabled/>
                                                        <label  class="custom-control-label" for="status{{ $key }}"><span id="lblClose_status{{ $key }}">{{  $item->status == '1' ? 'Open' :'Closed' }}</span></label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                                                    <select class="select2 form-control dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id" disabled >
                                                        @foreach($articles as $val)
                                                            <option value="{{$val->article_code}}|{{$val->group}}|{{$val->qty}}|{{$val->uom1}}" {{$val->article_code ==$item->article_code ? "selected" : ""}} >{{$val->article_alternative_code}} | {{$val->article_desc}}</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted" ><span id = "group" name="group[]">{{ $item->group }}</span></small></p>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qty_stock" class="d-block d-md-none">QTY Stock</label>
                                                    <input type="text" class="form-control text-right" id = "qty_stock" name="qty_stock[]" value="{{ $item->qty_stock ==0 ? 0 :$item->qty_stock }}" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qty_order" class="d-block d-md-none">QTY Order</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control numeral-mask text-right" id = "qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="9" disabled />
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id ="uom" name="uom[]">{{ $item->uom }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="price" class="d-block d-md-none">Price</label>
                                                    <input type="text" class="form-control numeral-mask text-right" id = "price" name="price[]" value="{{ $item->price }}"  maxlength="11" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="priceJasa" class="d-block d-md-none">Price Jasa</label>
                                                    <input type="text" class="form-control numeral-mask text-right" id = "priceJasa" name="priceJasa[]" value="{{ $item->price_service }}" maxlength="11" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="totalLine" class="d-block d-md-none">T.Material</label>
                                                    <input type="text" class="form-control numeral-mask text-right" value="{{ number_format($item->qty * $item->price) }}" id="totalLine" name="totalLine[]" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="totalJasa" class="d-block d-md-none">T.Service</label>
                                                    <input type="text" class="form-control numeral-mask text-right" value="{{ number_format($item->qty * $item->price_service) }}" id="totalJasa" name="totalJasa[]" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="totalAll" class="d-block d-md-none">Total</label>
                                                    <input type="text" class="form-control numeral-mask text-right" value="{{ number_format(($item->qty * $item->price)+($item->qty * $item->price_service)) }}" id="totalAll" name="totalAll[]" disabled />
                                                </div>
                                            </div>
                                        </div>
                                        <hr class="d-block d-md-none" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua tanpa-padding">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua tanpa-padding">Total QTY</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-4 col-form-label titik-dua tanpa-padding">Bruto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalAmount" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-4 col-form-label titik-dua tanpa-padding">PPN <span id="nilaiPPN"></span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPN" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPH" class="col-sm-4 col-form-label titik-dua tanpa-padding">PPH23 <span id="nilaiPPH23"></span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPH" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalNetto" class="col-sm-4 col-form-label titik-dua tanpa-padding">Netto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalNetto" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr/>
                    <br/>
                    <div class="form-row">
                        <div class="col-md-12">
                            <a href="{{ route('salesOrders.index') }}" class="btn btn-success">Back</a>
                        </div>
                    </div>
                    <br>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approveHistory as $val)
                            <div class="statistics-body">
                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                    <div class="media">
                                        <div class="avatar bg-light-success mr-2">
                                            <div class="avatar-content">
                                                <i data-feather="check" class="avatar-icon"></i>
                                            </div>
                                        </div>
                                        <div class="media-body my-auto">
                                            <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}/{{ $val->approval_number }}</h4>
                                            <p class="card-text mb-0">{{ $val->name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <br><br>
                    {{-- <div class="row">
                        @foreach($approveHistory as $val)
                            <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                <h6 class="">Approve-{{ $val->approval_order }}</h6>
                                <small>{{ $val->name }}</small>
                            </div>
                        @endforeach
                    </div> --}}
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
    
    label.titik-dua::after{
        content : ":"; 
        position : absolute;
        right : 1px;
    }

    label.tanpa-padding{
        padding-top: 0px;
        padding-bottom: 0px;
    }

    input.tanpa-padding{
        padding: 0;
    }


    @media screen 
    and (min-device-width: 1200px) 
    and (max-device-width: 1600px) 
    and (-webkit-min-device-pixel-ratio: 1) { 
        .lebar-list-item{
            width:150%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px)
    {
        .lebar-list-item{
            width:200%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
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