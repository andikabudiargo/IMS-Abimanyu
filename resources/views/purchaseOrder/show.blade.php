@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusPo }}</span></h4>
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
                                    <label for="poNumber">Order Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control disabled-el" value="{{ $header->po_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="poType">PO Type*</label>
                                    <select class="select2 form-control" id="poType" name="poType" disabled required>
                                        <option value="std" {{ $header->order_type == 'std' ? "selected" : "" }}>Standard</option>
                                        <option value="sub" {{ $header->order_type == 'sub' ? "selected" : "" }}>Subcontracting</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" value="{{ $header->po_date }}" placeholder="DD-MM-YYYY" required disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="deliveryDate">Delivery Date</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate" class="form-control" value="{{ $header->delivery_date }}" placeholder="DD-MM-YYYY" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required>
                                        <option value="">All</option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->supplier_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-1">
                                    <label class="form-label" for="term">Term</label>
                                    <input type="text" class="form-control angka text-right" id = "term" name="term" value="{{ $header->termin }}" maxlength="4" />
                                </div>
                                <div class="form-group col-md-1 d-flex align-items-end" >
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" {{ $header->pkp == 'PKP' ? "checked" : "" }} id="pkp" name="pkp"/>
                                        <label class="custom-control-label" for="pkp">PKP</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id="ppn" name="ppn" value="{{ $header->ppn }}" maxlength="2" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{$val == $header->currency ? "selected" : ""}} >{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <div class="form-group">
                                        <label for="kurs">Kurs</label>
                                        <input type="text" id="kurs" name="kurs" class="form-control angka" value="{{ $header->kurs }}" maxlength="6" />
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-9">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }} </textarea>
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
                            @include('purchaseOrder.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                @foreach ($detail as $key =>$item)
                                    <div id="new_row{{ $key }}" class="tanda-baris" >
                                        <div class="form-row d-flex align-items-center">
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="pRequest" class="d-block d-md-none">Purchase Request</label>
                                                    <select class="select2 form-control dynamicSelect sku-select-system" id="pRequest{{ $key }}" name="pRequest[]" data-dependent="pRequest">
                                                        @foreach($prHeader as $val)
                                                            <option value="{{ $val->pr_number }}" {{ $val->pr_number == $item->pr_number ? "selected" :"" }} >{{ $val->pr_number }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="article_id" class="d-block d-md-none">Article</label>
                                                    <select class="select2 form-control dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id">
                                                        @foreach($articles as $val)
                                                            <option value="{{ $val->article_code }}|{{ $val->group }}|{{ $val->qty_stock }}|{{ $val->qty }}|{{ $val->uom1 }}|{{ $val->costprice }}" 
                                                                    data-uom-group="{{ $val->uom_group }}'" {{ $val->article_code == $item->article_code && $val->pr_number == $item->pr_number ? "selected" :"" }}>
                                                                    {{ $val->article_alternative_code }} - {{ $val->article_desc }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qty_stock" class="d-block d-md-none">Stock</label>
                                                    <input type="text" class="form-control {{ $item->uom_group  == 'PIECE' ? 'numeral-mask-satuan' : 'numeral-mask-digit' }} text-right" id="qty_stock" name="qty_stock[]" value="{{ $item->qty_stock == 0 ? 0 :$item->qty_stock }}" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qty_order" class="d-block d-md-none">QTY Order</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" class="form-control {{ $item->uom_group  == 'PIECE' ? 'numeral-mask-satuan' : 'numeral-mask-digit' }} text-right" id="qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="9" />
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id ="uom" name="uom[]">{{ $item->uom }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12 d-none">
                                                <div class="form-group margin-nol">
                                                    <label for="price" class="d-block d-md-none">Price</label>
                                                    <input type="text" class="form-control numeral-mask text-right" id= "price" name="price[]" value="{{ $item->old_price }}"  maxlength="11">
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="price" class="d-block d-md-none">Price</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" class="form-control numeral-mask text-right" id="newPrice" name="newPrice[]" value="{{ $item->price }}"  maxlength="11">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text cursor-pointer">
                                                                <a onmouseover="this.style.cursor='pointer'" 
                                                                    id="listPrice" name="listPrice[]" 
                                                                    data-toggle="tooltip" 
                                                                    data-placement="right" 
                                                                    title="List Price"
                                                                    onClick="listPrice('{{ $item->article_code }}','{{ $item->article_code }}','{{ $key }}')"
                                                                >
                                                                    <i data-feather="info" class="feather-24">
                                                                    </i>
                                                                </a>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="totalLine" class="d-block d-md-none">Total</label>
                                                    <input type="text" class="form-control numeral-mask text-right" value="{{ number_format($item->qty * $item->price) }}" id="totalLine" name="totalLine[]" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();">
                                                        <i data-feather="trash-2" class="remove_button feather-24">
                                                        </i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua">Total QTY</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-3 col-form-label titik-dua">Bruto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalAmount" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-3 col-form-label titik-dua">Discount </label>
                                <div class="col-sm-2" style="padding-right: 0rem;">
                                    <input type="text" class="form-control text-right font-weight-bold" id="persenDiscount" maxlength="2"/>
                                </div>
                                <div class="col-sm-4" style="padding-left: 0rem;">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalDiscount" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-3 col-form-label titik-dua">PPN <span id="nilaiPPN"></span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPN" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPH" class="col-sm-3 col-form-label titik-dua">PPH <span>22</span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPH" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalNetto" class="col-sm-3 col-form-label titik-dua">Netto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalNetto" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('purchaseOrders.index') }}" class="btn btn-success">Back</a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
@include('purchaseOrder.modalListPrice')
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
@include('purchaseOrder.addArticle')
<script type="text/javascript">
    let cloneCount={{ count($detail) }};
    $("input[type='text']").click(function () {
        $(this).select();
    });  

    $(document).ready(function(){           
        validateForm('frmAdd');
        tombolPanah('qty_order');
        tombolPanah('price');
        activate_angka();
        mask_thousand();
        splitArticle();
        isiListArticle();
        hitungTotal();
        hitungGrandTotal();
        // $('.sku-select-system').select2();
    });  
</script>
@endsection