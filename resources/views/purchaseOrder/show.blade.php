@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="show">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusPo }}</h4>
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
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="poNumber">Order Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control disabled-el" value="{{ $header->po_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" value="{{ $header->po_date }}" placeholder="DD-MM-YYYY" disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="deliveryDate">Delivery Date</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate" class="form-control" value="{{ $header->delivery_date }}" placeholder="DD-MM-YYYY" disabled/>
                                </div>
                               
                                <div class="form-group col-md-2">
                                    <label for="tax">Tax*</label>
                                    <select class="select2 form-control" id="tax" name="tax" disabled>
                                        <option value="PKP" {{ $header->pkp == 'PKP' ? "selected" : "" }} >PKP</option>
                                        <option value="NONPKP" {{ $header->pkp == 'NONPKP' ? "selected" : "" }}>NON PKP</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "ppn" name="ppn" value="{{ $header->ppn }}" maxlength="2" disabled/>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" disabled>
                                        <option label=""></option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->supplier_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="term">TERM</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "term" name="term" value="{{ $header->termin }}" maxlength="4" disabled/>
                                        <div class="input-group-append">
                                            <span class="input-group-text">DAYS</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency" disabled>
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{$val == $header->currency ? "selected" : ""}} >{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="kurs">Kurs</label>
                                        <input type="text" id="kurs" name="kurs" class="form-control angka" value="{{ $header->kurs }}" maxlength="6" disabled/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-11">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ $header->note }} </textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12">
                                            <a href="{{ route('purchaseOrders.index') }}" class="btn btn-warning">Back</a>
                                            @if( $header->status == '1')
                                                @can('purchaseOrder-validate')
                                                    <button class="btn btn-primary" type="button" id="cmdValidate" name="cmdValidate">Validate</button>
                                                @endcan
                                            @endif
                                            @if( $header->status == '2')
                                                @can('purchaseOrder-authorize')
                                                    <button class="btn btn-primary" type="button" id="cmdAuthorized" name="cmdAuthorized">Auhorize</button>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
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
                    <div>
                        <table class="" style="width:98%;table-layout: fixed;">
                            <tbody>
                                <tr>
                                    <td class="isian-satu" style="width: 15%">
                                        <label>Purchase Request</label>
                                    </td>
                                    <td class="">
                                        <label>Article Code</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Stock</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>QTY</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>UOM</label>
                                    </td>
                                    <td class="isian d-none" style="width: 10%">
                                        <label>Price</label>
                                    </td>
                                    <td class="text-center d-none" style="width: 5%">
                                        <label>-</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Price</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Total</label>
                                    </td>
                                    <td class="isian text-center d-none" style="width: 5%">
                                        <label>-</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                        @foreach ($detail as $key =>$item)
                            <div id="new_row{{ $key }}" class="tanda-baris" >
                                <table class="table-bordered" style="width: 98%;table-layout: fixed;">
                                    <tbody>
                                        <tr>
                                            <td class="isian-satu" style="width: 20%">
                                                <select class="select2 form-control dynamicSelect sku-select-system" id="pRequest{{ $key }}" name="pRequest[]" data-dependent="pRequest" disabled>
                                                    @foreach($prHeader as $val)
                                                        <option value="{{ $val->pr_number }}" {{ $val->pr_number == $item->pr_number ? "selected" :"" }} >{{ $val->pr_number }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="" style="">
                                                <select class="select2 form-control dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id" disabled>
                                                    @foreach($articles as $val)
                                                        <option value="{{ $val->article_code }}|{{ $val->group }}|{{ $val->qty_stock }}|{{ $val->qty }}|{{ $val->uom1 }}|{{ $val->costprice }}" {{ $val->article_code == $item->article_code && $val->pr_number == $item->pr_number ? "selected" :"" }}>{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="isian disabled" style="width: 5%">
                                                <input type="text" class="form-control-plaintext text-right" id = "qty_stock" name="qty_stock[]" value="{{ $item->qty_stock ==0 ? 0 :$item->qty_stock }}" disabled>
                                            </td>
                                            <td class="isian" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="9" disabled/>
                                            </td>
                                            <td class="isian disabled" style="width: 5%">
                                                <span class="" id = "uom" name="uom[]">{{ $item->uom }}</span>
                                            </td>
                                            <td class="isian disabled d-none" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "price" name="price[]" value="{{ $item->old_price }}"  maxlength="11" disabled>
                                            </td>
                                            <td class="text-center d-none" style="width: 5%">
                                                <a onmouseover="this.style.cursor='pointer'" id="listPrice" name="listPrice[]" onClick="listPrice({{ $item->article_code }},'{{ $item->article_code }}')" disabled>
                                                    <i data-feather="info" class="feather-24">
                                                    </i>
                                                </a>
                                            </td>
                                            <td class="isian" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "newPrice" name="newPrice[]" value="{{ $item->price }}"  maxlength="11" disabled>
                                            </td>
                                            <td class="isian disabled text-right" style="width: 10%">
                                                <span class="totalLine" id="totalLine" name="totalLine[]">{{ number_format($item->qty * $item->price) }}</span>
                                            </td>
                                            <td class="isian text-center d-none" style="width: 5%">
                                                <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal()" >
                                                    <i data-feather="trash-2" class="remove_button feather-24">
                                                    </i>
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev d-none" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled />
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
                                    <input type="text" class="form-control text-right font-weight-bold" id="persenDiscount" maxlength="2" disabled/>
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
                </div>
            </div>
        </div>
    </div>
</section>
@include('salesOrder.addArticle')
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

    td.isian{
        padding-right:10px;
        padding-left:10px;
    }

    td.isian-satu{
        padding-right:5px;
        padding-left:15px;
        width: 25%;border-top: 1px solid #ffffff !important;
        border-bottom: 1px solid #ffffff !important;
        border-left: 1px solid #ffffff !important;
    }

    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

    label.tanpa-padding{
        padding-top: 5px;
        padding-bottom: 0px;
    }

    .totalLine{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        activate_angka();
        mask_thousand();
        hitungGrandTotal();
    });

    function hitungGrandTotal(){
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objQtyTiw= $('#article_row input[name="qty_order[]"]');
        let objQTY= $('#article_row input[name="qty_order[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let persenDiscount = $('#persenDiscount').val() || 0;
        let ppn= $('#ppn').val();
        let totalQty= 0;
        let totalAmount=0

        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            let price = parseInt(objPrice.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            totalQty+= qty;
            totalAmount+= qty*price;
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#nilaiPPN").text(ppn);
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount));
        $("#totalDiscount").val(humanizeNumber((totalAmount*parseInt(persenDiscount))/100));
        $("#totalPPN").val(humanizeNumber((parseInt(ppn)*totalAmount)/100));
        $("#totalPPH").val(0);
        $("#totalNetto").val(humanizeNumber((totalAmount+((parseInt(ppn)*totalAmount)/100))-((totalAmount*parseInt(persenDiscount))/100)));

    }

    $("#cmdValidate").click(function(){     
        let poNumber = $('#poNumber').val();
        console.log(poNumber);
        $.ajax({
            type: "get",
            url: "{{ route('purchaseOrder.validate') }}",
            data: {
                poNumber:poNumber,
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        message += "-"+data.message[i]+"<br>";                           
                    }
                    $("#alert-message-success").addClass(data.alert);
                    $("#alert-message-success .alert-body").html(message);
                    $("#alert-message-success").show();
                    $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                        $("#alert-message-success").slideUp(500);
                    });

                }else{
                    $("#alert-message-success").addClass(data.alert);
                    $("#alert-message-success .alert-body").html(data.message);
                    $("#alert-message-success").show();
                    $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                        $("#alert-message-success").slideUp(500);
                    });
                    $('#statusText').text(data.statusPo)
                    $('#cmdValidate').hide();
                    $('#cmdAuthorized').hide();
                    $('#cmdSave').hide();
                    $('#poNumber').attr('disabled','disabled');
                }
            },
            error: function(error) {
                console.log(error);
            }
        });

    });

    $("#cmdAuthorized").click(function(){     
        let poNumber = $('#poNumber').val();
        $.ajax({
            type: "get",
            url: "{{ route('purchaseOrder.authorize') }}",
            data: {
                poNumber:poNumber,
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        message += "-"+data.message[i]+"<br>";                           
                    }
                    $("#alert-message-success").addClass(data.alert);
                    $("#alert-message-success .alert-body").html(message);
                    $("#alert-message-success").show();
                    $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                        $("#alert-message-success").slideUp(500);
                    });

                }else{
                    $("#alert-message-success").addClass(data.alert);
                    $("#alert-message-success .alert-body").html(data.message);
                    $("#alert-message-success").show();
                    $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                        $("#alert-message-success").slideUp(500);
                    });
                    $('#statusText').text(data.statusPo)
                    $('#addNewRow').hide();
                    $('#cmdAuthorized').hide();
                    $('#cmdValidate').hide();
                    $('#cmdSave').hide();
                    $('#poNumber').attr('disabled','disabled');
                }
            },
            error: function(error) {
                console.log(error);
            }
        });

    });

       
</script>
@endsection