@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
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
                                    <input type="text" id="orderNum" name="orderNum" class="form-control disabled-el" value="{{ $header->so_code }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-basic" value="{{ $header->so_date }}" placeholder="DD-MM-YYYY" required/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="salesman">Salesman*</label>
                                    <select class="select2 form-control" id="salesman" name="salesman" required>
                                        <option value="">Choose salesman</option>
                                        @foreach($employees as $val)
                                        {{-- <option value="{{$val->employee_id}}" {{ $val->employee_id == $header->salesman_code ? "selected" : ""}}>{{$val->employee_id}} - {{$val->name}}</option> --}}
                                        <option value="{{$val->username}}" {{ $val->username == $header->salesman_code ? "selected" : ""}}>{{$val->name}}</option>
                                        @endforeach
                                        
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="type">Type</label>
                                    <select class="select2 form-control" id="type" name="type" required>
                                        @foreach($types as $val)
                                        <option value="{{$val}}" {{ $val == $header->order_type ? "selected" : ""}}>{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        {{-- <option value="">All</option> --}}
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{ $val == $header->currency ? "selected" : ""}}>{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "ppn" name="ppn" value="{{ $header->ppn }}" maxlength="2" />
                                        <input type="text" class="form-control" id="pembilangNumber" name="pembilangNumber" value="{{ $header->dpp_lain_pembilang }}" hidden />
                                        <input type="text" class="form-control" id="penyebutNumber" name="penyebutNumber" value="{{ $header->dpp_lain_penyebut }}" hidden/>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="pph23">PPH23</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "pph23" name="pph23" value="{{ $header->pph23 }}" maxlength="2" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="poNumber">PO Number</label>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control text-uppercase" value="{{ $header->po_number }}" maxlength="40" autofocus required />
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
                            @php
                                $deliveryTypeOptions = ['PO','Jitcall','DI','Kanban','Other'];
                                $deliveryTypeSelected = in_array($header->delivery_type, $deliveryTypeOptions) ? $header->delivery_type : ($header->delivery_type ? 'Other' : 'PO');
                            @endphp
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="deliveryType">Delivery Type*</label>
                                    <select class="select2 form-control" id="deliveryType" name="deliveryType" required>
                                        @foreach($deliveryTypeOptions as $val)
                                        <option value="{{$val}}" {{ $val == $deliveryTypeSelected ? "selected" : ""}}>{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2" id="deliveryTypeOtherWrap" style="display:none;">
                                    <label for="deliveryTypeOther">Delivery Type (manual)*</label>
                                    <input type="text" id="deliveryTypeOther" name="deliveryTypeOther" class="form-control" maxlength="40" value="{{ $deliveryTypeSelected == 'Other' ? $header->delivery_type : '' }}" />
                                </div>
                                <div class="form-group col-md-2" id="deliveryDateWrap" style="display:none;">
                                    <label for="deliveryDate">Delivery Date*</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate" class="form-control flatpickr-basic" placeholder="DD-MM-YYYY" value="{{ $header->delivery_date ? date('d-m-Y', strtotime($header->delivery_date)) : '' }}" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('salesOrder.headerColumnOne')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                                @foreach ($detail as $key =>$item)
                                    <div id="new_row{{ $key }}" class="tanda-baris" >
                                        <div class="form-row">
                                            <div class="col-md-4 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="article_id" class="d-block d-md-none">Article Code</label>
                                                    <select class="form-control dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id">
                                                        <option value="" >Choose Article ...</option>
                                                        @foreach($articles as $val)
                                                            <option value="{{$val->article_code}}|{{$val->group}}|{{$val->qty}}|{{$val->uom1}}" {{$val->article_code ==$item->article_code ? "selected" : ""}}>{{$val->article_alternative_code}}-{{$val->article_desc}}</option>
                                                        @endforeach
                                                    </select>
                                                    {{-- <small class="text-muted" ><span id = "group" name="group[]">{{ $item->group }}</span></small> --}}
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qty_stock" class="d-block d-md-none">QTY Stock</label>
                                                    <input type="text" class="form-control text-right" id = "qty_stock" name="qty_stock[]" value="{{ $item->qty_stock ==0 ? 0 :$item->qty_stock*1 }}" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qty_order" class="d-block d-md-none">QTY Order</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control numeral-mask-digit text-right" id = "qty_order" name="qty_order[]" oninput='inputDecimal(this)' value="{{ number_format($item->qty,2) }}" maxlength="9" />
                                                        <div class="input-group-append">
                                                            <span class="input-group-text padding-nol" id ="uom" name="uom[]">{{ $item->uom }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="price" class="d-block d-md-none">Material Price</label>
                                                    <input type="text" class="form-control numeral-mask-digit text-right" id = "price" name="price[]" oninput='inputDecimal(this)' value="{{ $item->price }}"  maxlength="11">
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="priceJasa" class="d-block d-md-none">Service Prices</label>
                                                    <input type="text" class="form-control numeral-mask-digit text-right" id = "priceJasa" name="priceJasa[]" oninput='inputDecimal(this)' value="{{ $item->price_service }}" maxlength="11">
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="totalLine" class="d-block d-md-none">T.Material</label>
                                                    <input type="text" class="form-control numeral-mask-digit text-right" value="{{ number_format(($item->qty * $item->price),2) }}" id="totalLine" name="totalLine[]" disabled >
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="totalJasa" class="d-block d-md-none">T.Service</label>
                                                    <input type="text" class="form-control numeral-mask-digit text-right" value="{{ number_format(($item->qty * $item->price_service),2) }}" id="totalJasa" name="totalJasa[]" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="totalAll" class="d-block d-md-none">Total</label>
                                                    <input type="text" class="form-control numeral-mask-digit text-right" value="{{ number_format((($item->qty * $item->price)+($item->qty * $item->price_service)),2) }}" id="totalAll" name="totalAll[]" disabled >
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal()">
                                                        <i data-feather="trash-2" class="remove_button feather-24">
                                                        </i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <hr class="d-block d-md-none" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div> 
                    
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-7">
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
                        <div class="col-md-6">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-4 col-form-label titik-dua tanpa-padding">Bruto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalAmount" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="nilaiLainCheck" class="col-sm-4 col-form-label titik-dua">DPP Nilai Lain <span id="nilaiDppLain">{{ $header->dpp_lain_value  ? $header->dpp_lain_pembilang."/".$header->dpp_lain_penyebut : '' }}</span></label>
                                <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="nilaiLainCheck" name="nilaiLainCheck" {{ $header->dpp_lain_value >0 ? 'checked' : '' }}/>
                                        <label class="custom-control-label" for="nilaiLainCheck"></label>
                                    </div>
                                </div>    
                                <div class="col-sm-5">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit" oninput='inputDecimal(this)' value="{{ $header->dpp_lain_value>0 ? number_format($header->dpp_lain_value,2) : 0 }}" id="totalDppNilaiLain"  name="totalDppNilaiLain" disabled/>
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
                            <a href="{{ route('salesOrders.index') }}" class="btn btn-light">Back</a>
                            <a href="{{ route('salesOrder.print', ['id'=>Crypt::encryptString($header->id)]) }}" target="_blank" type="button" class="btn btn-primary">
                                <i data-feather="printer"></i>
                                <span>{{ __("Print") }}</span>
                            </a>
                            @if( $approveValidate ? $approveValidate[0]->validate : '')
                                <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                @if( strtoupper($statusSo) == 'NEW' or strtoupper($statusSo) == 'VALIDATED' )
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                                @endif
                            @else
                                @if( strtoupper($statusSo) == 'NEW' or strtoupper($statusSo) == 'VALIDATED')
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                                @endif
                            @endif
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

{{-- Modal List Price SO --}}
<div class="modal fade" id="modalListPriceSo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Price History: <span id="modalArticleSo"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-sm" id="modalTableDataSo">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SO Number</th>
                            <th>SO Date</th>
                            <th>Updated</th>
                            <th class="text-right">Price Material</th>
                            <th class="text-right">Price Service</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
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

/* Paksa teks article select2 selalu terlihat, walau modal aktif */
#article_row .select2-selection__rendered {
    color: #6e6b7b !important;
    opacity: 1 !important;
    visibility: visible !important;
}

/* Cegah modal meng-inert-kan konten di belakang sampai teks jadi transparan */
body.modal-open #article_row,
body.modal-open #article_row .select2-container {
    opacity: 1 !important;
    visibility: visible !important;
}
    

</style>
@endsection
@section('scripts')
<script>
$(function(){
    // Repaint teks select2 article — HANYA menulis teks, tidak menghapus apa pun
    function repaintArticleText(){
        $('#article_row select[name="article_id[]"]').each(function(){
            var $sel = $(this);
            if (!$sel.val()) return;                          // skip baris kosong
            var txt = $sel.find('option:selected').text();
            if (!txt) return;
            var s2 = $sel.data('select2');
            if (s2 && s2.$selection) {
                s2.$selection.find('.select2-selection__rendered')
                    .attr('title', txt).text(txt);
            }
        });
    }

    $(document).on('shown.bs.modal', '#modalListPriceSo', function(){
        repaintArticleText();
    });
    $(document).on('hidden.bs.modal', '#modalListPriceSo', function(){
        repaintArticleText();
    });
});
</script>
@include('salesOrder.addArticle')
<script type="text/javascript">
    let lockedAt = "{{ $lockDate }}";

    $('#deliveryDate').flatpickr({ dateFormat: "d-m-Y", allowInput:true });

    function toggleDeliveryFields(){
        let deliveryType = $('#deliveryType').val();
        $('#deliveryTypeOtherWrap').toggle(deliveryType === 'Other');
        $('#deliveryTypeOther').prop('required', deliveryType === 'Other');
        $('#deliveryDateWrap').toggle(deliveryType !== 'PO');
        $('#deliveryDate').prop('required', deliveryType !== 'PO');
    }

    $(document).ready(function(){
        validateFormToast("frmAdd");
        tombolPanah('qty_order');
        tombolPanah('price');
        // activate_angka();
        mask_thousand();
        splitArticle();
        hitungTotal();
        hitungGrandTotal();
        mask_thousand_digit(2);
        $('.sku-select-system').select2();
        toggleDeliveryFields();
    });

    $('#deliveryType').change(toggleDeliveryFields);

    statusSo = "{{ $statusSo }}";

    simpanData = (statusSimpan) =>{
        $('#cmdSave').attr('disabled','disabled');
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
            $('#cmdSave').removeAttr('disabled');
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let objQty= $('#article_row input[name="qty_order[]"]');
            let objPrice= $('#article_row input[name="price[]"]');
            let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
            let objUom= $('#article_row span[name="uom[]"]'); 
            let objGroup= $('#article_row input[name="group[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";
        
            $("#article_row select[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article=$this.val().split("|");
                    let articleName=$this.select2('data')[0].text;
                    let plu=article[0];
                    let inisial = articleName.substring(2,5); 
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let price=objPrice.eq(i).val().replace(/,/gi, '') || 0;
                    let priceJasa=objPriceJasa.eq(i).val().replace(/,/gi, '') || 0;
                    let uom=objUom.eq(i).text();
                    let group=objGroup.eq(i).val();
                    let cust=$('#cust').val().split("|");
                    let custName = $('#cust').select2('data')[0].text;
                    let customer=cust[1];
                    let obj = $.grep(articles, function(obj){
                        return obj.article_code === plu;
                    })[0];
                    if(obj) {
                        pesan +="Article "+articleName+" entered more than once !! <br>"; 
                        flag=1;
                    } else {
                        if ((plu!=='') && (qty> 0)){
                            articles.push({
                                "article_code":plu,
                                "qty":qty,
                                "uom":uom,
                                "price":price,
                                "price_service":priceJasa,
                                "group":group
                            });
                        }
                    } 
                
                    if (qty == 0){
                        pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                        flag=1;
                    }

                    if (inisial !== customer){
                        
                        pesan +="This article "+ articleName +" does not belong to the customer "+custName +" <br>"; 
                        flag=1;
                    }
                
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let orderNumber = $('#orderNum').val();
                let orderDate = $('#orderDate').val();
                let currency = $('#currency').val();
                let type = $('#type').val();
                let poNumber = $('#poNumber').val();
                let cust = $('#cust').val().split("|");
                let customer = cust[0];
                let salesman = $('#salesman').val();
                let ppn = $('#ppn').val().replace(/,/gi, '') || 0;
                let pph23 = $('#pph23').val().replace(/,/gi, '') || 0;
                let totalPpn = $('#totalPPN').val().replace(/,/gi, '') || 0;
                let totalPph = $('#totalPPH').val().replace(/,/gi, '') || 0;
                let note = $('#note').val();
                let approveLevel = $('#approveLevel').val();
                let aPembilangNumber = $('#pembilangNumber').val();
                let aPenyebutNumber = $('#penyebutNumber').val();
                let aTotalDppNilaiLain = $('#totalDppNilaiLain').val().replace(/,/gi, '') || 0;
                let deliveryType = $('#deliveryType').val();
                if (deliveryType === 'Other'){
                    deliveryType = $('#deliveryTypeOther').val();
                }
                let deliveryDate = $('#deliveryType').val() !== 'PO' ? $('#deliveryDate').val() : '';

                $.ajax({
                    type: "post",
                    url: "{{ route('salesOrder.update') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        orderNumber:orderNumber,
                        orderDate:orderDate,
                        currency:currency,
                        type:type,
                        poNumber:poNumber,
                        customer:customer,
                        salesman:salesman,
                        ppn:ppn,
                        pph23:pph23,
                        totalPph:totalPph,
                        totalPpn:totalPpn,
                        note:note,
                        approveLevel:approveLevel,
                        statusSimpan:statusSimpan,
                        pembilangNumber:aPembilangNumber,
                        penyebutNumber:aPenyebutNumber,
                        totalDppNilaiLain:aTotalDppNilaiLain,
                        deliveryType:deliveryType,
                        deliveryDate:deliveryDate
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            let message="";
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#poNumber').focus().select();
                            $('#orderNum').attr('disabled','disabled');
                            $('#cmdSave').removeAttr('disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#orderNum').attr('disabled','disabled');
                            $('#totalDppNilaiLain').prop('disabled',true);
                            reloadPage();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });

            }else{
                Swal.fire('Warning..',pesan,'warning');
                $('#cmdSave').removeAttr('disabled');
            }
        }
    }

    $("#cmdSave").click(function(){     
        simpanData('update');
    });

    $("#cmdApprove").click(function(){     
        simpanData('approve');
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection