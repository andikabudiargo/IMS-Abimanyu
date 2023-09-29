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
                                <div class="form-group col-md-2 d-none">
                                    <label class="form-label" for="poType">PO Type*</label>
                                    <select class="select2 form-control" id="poType" name="poType" disabled required>
                                        <option value="std" {{ $header->order_type == 'std' ? "selected" : "" }}>Standard</option>
                                        <option value="sub" {{ $header->order_type == 'sub' ? "selected" : "" }}>Subcontracting</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" value="{{ $header->po_date }}" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="deliveryDate">Delivery Date</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate" class="form-control" value="{{ $header->delivery_date }}" placeholder="DD-MM-YYYY" required />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control disabled-el" id="supplier" name="supplier" disabled required>
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
                                <div class="form-group col-md-2 d-none">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id="ppn" name="ppn" value="{{ $header->ppn }}" maxlength="2" />
                                        <input type="text" class="form-control angka text-right" id="pph23" name="pph23" value="{{ $header->pph22 }}" maxlength="2" />
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
                                <div class="form-group col-md-8">
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
                    <form action="">
                        <div class="form-row">
                            <div class="col-md-4 col-12">
                                <div class="form-group margin-nol">
                                    <label class="form-label" for="prSelect">Purchase Request</label>
                                    <select class="dynamicSelect form-control select2 " id="prSelect" name="prSelect" title="Apabila sudah ada di daftar akan disabled">
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                    <hr>
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('purchaseOrder.headerColumn')
                            <div class="" id="article_row" style="max-height: 25rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                @foreach ($detail as $key =>$item)
                                    <div id="new_row{{ $key }}" class="tanda-baris" >
                                        <div class="form-row d-flex align-items-center">
                                            <div class="col-md-2 col-12" style="max-width: 10.66667%;padding-right:2px;">
                                                <div class="form-group margin-nol">
                                                    <label for="prNumber" class="d-block d-md-none">PR Number</label>
                                                    <input type="text" class="form-control disabled-el" id = "prNumber" name="prNumber[]" value="{{ $item->pr_number }}" disabled style="font-size:0.8rem;padding-right: 0.4rem;padding-left: 0.4rem;" />
                                                </div>
                                            </div>
                                            <div class="col-md-5 col-12" style="max-width: 38.66667%;padding-right:2px;padding-left:2px;">
                                                <div class="form-group margin-nol">
                                                    <label for="articleDesc" class="d-block d-md-none">Article</label>
                                                    <input type="text" class="form-control disabled-el" id = "articleDesc" name="articleDesc[]" value="{{ $item->article_alternative_code }} - {{ $item->article_desc }}" data-toggle="tooltip" data-placement="top" title="{{ $item->article_desc }}" disabled style="font-size:0.9rem" />
                                                    <input type="hidden" class="form-control disabled-el" id = "articleId" name="articleId[]" value="{{ $item->article_code }}">
                                                    <input type="hidden" class="form-control disabled-el" id = "pRequest" name="pRequest[]" value="{{ $item->pr_number }}">
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12" style="padding-right:2px;padding-left:2px;">
                                                <div class="form-group margin-nol">
                                                    <label for="qty_stock" class="d-block d-md-none">Stock</label>
                                                    <input type="text" class="form-control {{ $item->uom_group  == 'PIECE' ? 'numeral-mask-satuan' : 'numeral-mask-digit' }} text-right" id="qty_stock" name="qty_stock[]" value="{{ $item->qty_stock == 0 ? 0 :$item->qty_stock*1 }}" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-12" style="max-width: 10.66667%;padding-right:2px;padding-left:2px;">
                                                <div class="form-group margin-nol">
                                                    <label for="qtyOrder" class="d-block d-md-none">QTY</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" class="form-control {{ $item->uom_group  == 'PIECE' ? 'numeral-mask-satuan' : 'numeral-mask-digit' }} text-right" id="qtyOrder" name="qtyOrder[]" value="{{ $item->qty*1 }}" maxlength="9" />
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
                                            <div class="col-md-2 col-12" style="max-width: 12.66667%;padding-right:2px;padding-left:2px;">
                                                <div class="form-group margin-nol">
                                                    <label for="price" class="d-block d-md-none">Price</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" class="form-control numeral-mask-digit text-right" oninput='inputDecimal(this)' id="newPrice" name="newPrice[]" value="{{ number_format((float) $item->price,2) }}"  maxlength="20">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text cursor-pointer">
                                                                <a onmouseover="this.style.cursor='pointer'" 
                                                                    id="listPrice" name="listPrice[]" 
                                                                    data-toggle="tooltip" 
                                                                    data-placement="right" 
                                                                    title="List Price"
                                                                    onClick="listPrice('{{ $item->article_code }}','{{ $item->article_code }}','{{ $key }}')">
                                                                    <i data-feather="info" class="feather-24">
                                                                    </i>
                                                                </a>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-12" style="max-width: 10.66667%;padding-right:2px;padding-left:2px;">
                                                <div class="form-group margin-nol">
                                                    <label for="totalLine" class="d-block d-md-none">Total</label>
                                                    <input type="text" class="form-control numeral-mask text-right" value="{{ number_format($item->qty * $item->price) }}" id="totalLine" name="totalLine[]" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12" style="max-width:3%;">
                                                <div class="form-group margin-nol text-center">
                                                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();disabledEnabledSelect2()">
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
                    {{-- <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div> --}}
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua">Total QTY</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-satuan" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-4">
                                    <label for="totalAmount" class="col-form-label titik-dua">Sub Total</label>
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit" id="totalAmount" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-4">
                                    <label for="totalDiscount" class="col-form-label titik-dua">Discount </label>
                                </div>
                                <div class="col-sm-2" style="padding-right: 0rem;">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit" id="persenDiscount" value="{{ $header->discount }}" maxlength="5"/>
                                </div>
                                <div class="col-sm-4" style="padding-left: 0rem;">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit" id="totalDiscount" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-4">
                                    <label for="totalDpp" class="col-form-label titik-dua">DPP</label>
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit" id="totalDpp" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-4">
                                    <label for="totalPPN" class="col-form-label titik-dua">PPN <span id="nilaiPPN"></span> </label>
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold angka-dua-decimal" id="totalPPN" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-4">
                                    <label for="totalPPH" class="col-form-label titik-dua">PPH23 <span id="nilaiPPH"></span> </label>
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit" id="totalPPH" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <div class="col-sm-2"></div>
                                <div class="col-sm-4">
                                    <label for="totalNetto" class="col-form-label titik-dua">Total</label>
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit" id="totalNetto" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('purchaseOrders.index') }}" class="btn btn-light">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-danger" type="button" id="cmdDecline" name="cmdDecline">Decline</button>
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusPo =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" >Update</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusPo =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate" >Update</button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
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
@include('purchaseOrder.modalListPrice')
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
@include('purchaseOrder.addArticle')
<script type="text/javascript">
    const updateBtn = document.querySelector('#cmdUpdate');
    const approveBtn = document.querySelector('#cmdApprove');
    const declineBtn = document.querySelector('#cmdDecline');
    let cloneCount={{ count($detail) }};

    $(document).ready(function(){           
        validateForm('frmAdd');
        tombolPanah('qtyOrder');
        tombolPanah('price');
        $('.sku-select-system').select2();
        let suppCode = $(this).val();
        changeselect('pRequest','prSelect',"{{ $header->supplier_id }}");
        setTimeout(() => { 
            // disabledEnabledSelect2();
        }, 500);

        activate_angka();
        mask_thousand();
        hitungTotal();
        hitungGrandTotal();
        mask_thousand_satuan();
        mask_thousand_digit(2);
        
    });
    
    if (updateBtn) {
        updateBtn.addEventListener('click',() =>{
            updateData('update');
        });
    }

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            updateData('approve');
        },{ once:true});
    }

    if (declineBtn) {
        declineBtn.addEventListener('click',() =>{
            declineData('decline');
        },{ once:true});
    }

    
    prSelect.change(function(e){        
        let prNumber = $(this).val();
        let suppCode = $('#supplier').val();
        changeSelectPr(suppCode,prNumber);
    });
               
</script>
@endsection