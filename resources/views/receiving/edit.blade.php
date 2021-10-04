@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: New</h4>
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
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" value="{{ $header->po_date }}" placeholder="DD-MM-YYYY"  required/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="deliveryDate">Delivery Date</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate" class="form-control" value="{{ $header->delivery_date }}" placeholder="DD-MM-YYYY" />
                                </div>
                               
                                <div class="form-group col-md-2">
                                    <label for="tax">Tax*</label>
                                    <select class="select2 form-control" id="tax" name="tax" required>
                                        <option value="PKP" {{ $header->pkp == 'PKP' ? "selected" : "" }} >PKP</option>
                                        <option value="NONPKP" {{ $header->pkp == 'NONPKP' ? "selected" : "" }}>NON PKP</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "ppn" name="ppn" value="{{ $header->ppn }}" maxlength="2" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required>
                                        <option label=""></option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->supplier_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="term">TERM</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "term" name="term" value="{{ $header->termin }}" maxlength="4" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">DAYS</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{$val == $header->currency ? "selected" : ""}} >{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="kurs">Kurs</label>
                                        <input type="text" id="kurs" name="kurs" class="form-control angka" value="{{ $header->kurs }}" maxlength="6" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-11">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }} </textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12">
                                            <a href="{{ route('purchaseOrders.index') }}" class="btn btn-warning">Cancel</a>
                                            <a href="{{ route('purchaseOrder.create') }}" class="btn btn-success">New</a>
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                                            {{-- <button class="btn btn-primary" type="button" id="cmdValidate" name="cmdValidate">Validate</button>
                                            <button class="btn btn-primary" type="button" id="cmdAuthorized" name="cmdAuthorized">Auhorized</button> --}}
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
                                    <td class="text-center" style="width: 5%">
                                        <label>-</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Price</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Total</label>
                                    </td>
                                    <td class="isian text-center" style="width: 5%">
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
                                            <td class="isian-satu" style="width: 15%">
                                                <select class="select2 form-control dynamicSelect sku-select-system" id="pRequest{{ $key }}" name="pRequest[]" data-dependent="pRequest">
                                                    @foreach($prHeader as $val)
                                                        <option value="{{ $val->pr_number }}" {{ $val->pr_number == $item->pr_number ? "selected" :"" }} >{{ $val->pr_number }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="" style="">
                                                <select class="select2 form-control dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id">
                                                    @foreach($articles as $val)
                                                        <option value="{{ $val->article_code }}|{{ $val->group }}|{{ $val->qty_stock }}|{{ $val->qty }}|{{ $val->uom1 }}|{{ $val->costprice }}" {{ $val->article_code == $item->article_code && $val->pr_number == $item->pr_number ? "selected" :"" }}>{{ $val->article_alternative_code }} - {{ $val->article_desc }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="isian disabled" style="width: 5%">
                                                <input type="text" class="form-control-plaintext text-right" id = "qty_stock" name="qty_stock[]" value="{{ $item->qty_stock ==0 ? 0 :$item->qty_stock }}" disabled>
                                            </td>
                                            <td class="isian" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="9" />
                                            </td>
                                            <td class="isian disabled" style="width: 5%">
                                                <span class="" id = "uom" name="uom[]">{{ $item->uom }}</span>
                                            </td>
                                            <td class="isian disabled d-none" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "price" name="price[]" value="{{ $item->old_price }}"  maxlength="11">
                                            </td>
                                            <td class="text-center" style="width: 5%">
                                                <a onmouseover="this.style.cursor='pointer'" id="listPrice" name="listPrice[]" onClick="listPrice({{ $item->article_code }},'{{ $item->article_code }}')">
                                                    <i data-feather="info" class="feather-24">
                                                    </i>
                                                </a>
                                            </td>
                                            <td class="isian" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "newPrice" name="newPrice[]" value="{{ $item->price }}"  maxlength="11">
                                            </td>
                                            <td class="isian disabled text-right" style="width: 10%">
                                                <span class="totalLine" id="totalLine" name="totalLine[]">{{ number_format($item->qty * $item->price) }}</span>
                                            </td>
                                            <td class="isian text-center" style="width: 5%">
                                                <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal()">
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
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
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
                </div>
            </div>
        </div>
    </div>
</section>
<div class="modal fade text-left bisa-geser" id="modalListPrice" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4>List price</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5><span class="semi-bold" id='modalArticle'></span></h5>
                <div class="table-responsive">
                    <table class="table" id='modalTableData'>
                        <thead>
                            <tr>
                                <td>PO Number</td>
                                <td>Date</td>
                                <td>Price</td>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@include('purchaseOrder.addArticle')
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
    $("input[type='text']").click(function () {
        $(this).select();
    });  
    $(document).ready(function(){           
        validateForm('frmAdd');
        $('#orderDate').val(currentDate);
        tombolPanah('qty_order');
        tombolPanah('price');
        activate_angka();
        mask_thousand();
        splitArticle();
        isiListArticle();
        hitungTotal();
    });

    orderDate = $('#orderDate');
    if (orderDate.length) {
        orderDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }

    deliveryDate = $('#deliveryDate');
    if (deliveryDate.length) {
        deliveryDate.flatpickr({
            dateFormat: "d-m-Y",
            minDate: currentDate
        });
    }

    $('#tax').on('change', function() {
        let tax = $(this).val();
        if (tax == 'PKP'){
            $('#ppn').val(10);
            $('#ppn').removeAttr('disabled');
        }else{
            $('#ppn').val(0);
            $('#ppn').attr('disabled','disabled');
            hitungGrandTotal();
        }
    })

    $('#persenDiscount,#ppn').on('keyup', function() {
        hitungGrandTotal();
    })
    
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel,#cmdNew").click(function(){
        reloadPage();
    });

    $("#cmdSave").click(function(){     
        $('.disabled-el').removeAttr('disabled');
        // ambil semua data article
        let objQty= $('input[name="qty_order[]"]');
        let objPrice= $('input[name="price[]"]');
        let objNewPrice= $('input[name="newPrice[]"]');
        let objUom= $('span[name="uom[]"]'); 
        let objpr= $('select[name="pRequest[]"]'); 
        let articles = []; 
        let flag=0; 
        let pesan="";
        
        $("#article_row select[name='article_id[]']").map(function(i) {  
		    let $this=$(this);
            if ($this.val()){
                let article=$this.val().split("|");
                let articleName=$this.select2('data')[0].text;
                let plu=article[0];
                let qty=objQty.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                let newPrice=objNewPrice.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                let price=objPrice.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                let pRequest=objpr.eq(i).val();
                let uom=objUom.eq(i).text();
                let supp=$('#supplier').val();
                let suppName = $('#supplier').select2('data')[0].text;
                let supplier=supp;
            
                //es6
                // let obj = ingredient.find(obj => obj.plu == plu);

                //jquery
                //cek apakah article ada yang double input ato ngk
                let obj = $.grep(articles, function(obj){
                    return obj.article_code === plu;
                })[0];
                
                if(obj) {
                    pesan +="Article "+plu+" entered more than once !! <br>"; 
                    flag=1;
                } else {
                    if ((plu!=='') && (qty> 0)){
                        articles.push({
                            "article_code":plu,
                            "qty":qty,
                            "uom":uom,
                            "price":price,
                            "newPrice":newPrice,
                            "pRequest":pRequest
                        });
                    }
                } 
            
                if (qty == 0){
                    pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                    flag=1;
                }
            
            }
        });

        if (articles.length == 0){
			pesan +="Articles must be filled in completely <br>"; 
			flag=1;
		}

        if (flag==0){

            let orderDate = $('#orderDate').val();
            let poNumber = $('#poNumber').val();
            let currency = $('#currency').val();
            let supps = $('#supplier').val();
            let supp = supps;
            let tax = $('#tax').val();
            let deliveryDate = $('#deliveryDate').val();
            let term = $('#term').val()||0;
            let kurs = $('#kurs').val()||1;
            let ppn = $('#ppn').val().replace(/[^0-9]/gi, '') || 0;
            let totalPph = $('#totalPPH').val().replace(/[^0-9]/gi, '') || 0;
            let totalPpn = $('#totalPPN').val().replace(/[^0-9]/gi, '') || 0;
            let note = $('#note').val();
            let persenDiscount = $('#persenDiscount').val() || 0;

            $.ajax({
                type: "post",
                url: "{{ route('purchaseOrder.update') }}",
                data: {
                    articles:JSON.stringify(articles),
                    poNumber:poNumber,
                    orderDate:orderDate,
                    deliveryDate:deliveryDate,
                    currency:currency,                
                    supplier:supp,
                    tax:tax,
                    ppn:ppn,
                    term:term,
                    totalPph:totalPph,
                    totalPpn:totalPpn,
                    kurs:kurs,
                    note:note,
                    discount:persenDiscount
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
                        $('#poNumber').attr('disabled','disabled');
                    }
                    
                },
                error: function(error) {
                    console.log(error);
                }
            });

        }else{
            Swal.fire('Warning..',pesan,'warning');
        }
    
    });
    
    let cloneCount=$('#last_row_number').val();
    function add_new_row() {    
        let supplier = $('#supplier');
        let supp = supplier.val();
        if (supp){            
            $("#article_row").append($("#new_row").clone().html());
            cloneCount++;
            $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
            $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
            $("#new_row"+ cloneCount).find('#pRequest').attr('id', 'pRequest'+ cloneCount);
            changeselect('pRequest','pRequest'+ cloneCount,supp,'');
            $("#article_id"+cloneCount).select2();
            $("#pRequest"+cloneCount).select2();
            $('#remove_button').tooltip();
            tombolPanah('qty_order');
            tombolPanah('newPrice');
            activate_angka();
            mask_thousand();
            splitArticle();
            isiListArticle();
            hitungTotal();
            hitungGrandTotal();
        }else{
            Swal.fire({
                title: 'Warning',
                text: "Choose Supplier",
                icon: 'warning',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    supplier.select2('open');
                }
            })
        }
    };

    function splitArticle(){
        // split article with delimiter |
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objStock= $('#article_row input[name="qty_stock[]"]');
        let objUom= $('#article_row span[name="uom[]"]'); 
        let objQty= $('#article_row input[name="qty_order[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objNewPrice= $('#article_row input[name="newPrice[]"]');
        let objListPrice= $('#article_row a[name="listPrice[]"]');
        objArticle.change(function(e){        
            // article_code.'|'group.'|'qty_stock.'|'qty.'|'uom1.'|'costprice.'"
            let objIndex = objArticle.index(this);
            let detail = objArticle.eq(objIndex).val();
            let detailText = objArticle.eq(objIndex).select2('data')[0].text;
            let arrDetail = detail.split("|");
            objListPrice.eq(objIndex).attr('onClick', 'listPrice('+arrDetail[0]+',"'+detailText+'");');
            objStock.eq(objIndex).val(humanizeNumber(arrDetail[2]||0));
            objUom.eq(objIndex).text(arrDetail[4]);
            objPrice.eq(objIndex).val(humanizeNumber(arrDetail[5]||0));
            objNewPrice.eq(objIndex).val(humanizeNumber(arrDetail[5]||0));
            objArticle.eq(objIndex).select2('open');
            if (detail){
                setTimeout(() => {
                    objQty.eq(objIndex).focus().select();
                }, 5);
            }
		});
    }

    function listPrice(article,desc){
        $("#modalTableData tbody> tr").remove();
        $.ajax({
            dataType: 'json',
            type:'GET',
            url: "{{ route('purchaseOrder.price.list') }}",
            data: { article:article },
            success: function(data) {
                if(data.length > 0 ){
                    let html = '';
                    for(let i=0;i<data.length;i++){
                        html += '<tr>';
                        html += '<td>'+data[i].po_number+'</td>';
                        html += '<td>'+data[i].po_date+'</td>';
                        html += '<td class="text-right">'+humanizeNumber(data[i].price)+'</td>';
                        html += '</tr>';
                    }
                    $('#modalTableData tbody').append(html);
                }                
            },
            error: function(data) {
                swal.fire("Warning","Error data","warning");
            }
        });
        $('#modalArticle').text(desc);
        $('#modalListPrice').modal('show'); 
    }

    function hitungTotal(){
        let objQty= $('#article_row input[name="qty_order[]"]');
        let objNewPrice= $('#article_row input[name="newPrice[]"]');
        let objTotal= $('#article_row span[name="totalLine[]"]');
        
        objQty.keyup(function() {
            let indexnya= objQty.index(this);
            let qty = objQty.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0; 
            let newPrice = objNewPrice.eq(indexnya).val().replace(/[^0-9]/gi, '') ||0;
            let total = qty*newPrice;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            hitungGrandTotal();
        });    

        objNewPrice.keyup(function() {
            let indexnya= objNewPrice.index(this);
            let qty = objQty.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0; 
            let newPrice = objNewPrice.eq(indexnya).val().replace(/[^0-9]/gi, '')||0;
            let total = qty*newPrice;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            hitungGrandTotal();
        });    
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row select[name="article_id[]"]');
        let objQtyTiw= $('#article_row input[name="qty_order[]"]');
        let objQTY= $('#article_row input[name="qty_order[]"]');
        let objNewPrice= $('#article_row input[name="newPrice[]"]');
        let persenDiscount = $('#persenDiscount').val() || 0;
        let ppn= $('#ppn').val();
        let totalQty= 0;
        let totalAmount=0

        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            let newPrice = parseInt(objNewPrice.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            totalQty+= qty;
            totalAmount+= qty*newPrice;
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#nilaiPPN").text(ppn+"%");
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount));
        $("#totalDiscount").val(humanizeNumber((totalAmount*parseInt(persenDiscount))/100));
        $("#totalPPN").val(humanizeNumber((parseInt(ppn)*totalAmount)/100));
        $("#totalPPH").val(0);
        $("#totalNetto").val(humanizeNumber((totalAmount+((parseInt(ppn)*totalAmount)/100))-((totalAmount*parseInt(persenDiscount))/100)));

    }

    function isiListArticle(){
        // split article with delimiter |
        let objPrequest = $('#article_row select[name="pRequest[]"]');
        
        objPrequest.change(function(e){        
            let objIndex = objPrequest.index(this);
            let prNumber = objPrequest.eq(objIndex).val();
            let supp = $('#supplier').val();
            changeSelectArticle('searchFromPr',objIndex,supp,prNumber);
            splitArticle();
		});
    }

    function changeSelectArticle(dependent,objIndex,value,prNumber) {
        let objArticle = $('#article_row select[name="article_id[]"]');
        $.ajax({
            url:"{{route('dynamic.dependent')}}",
            method:"POST",
            data:{
                value:value,
                prNumber:prNumber,
                dependent:dependent
            },
            success:function(result){
                objArticle.eq(objIndex).html(result);
                objArticle.eq(objIndex).select2();
                // objArticle.eq(objIndex).trigger('change');
            }
        })
    }

    function changeselect(dependent,obj,value,type) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            value:value,
            type:type,
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            // $('#'+obj).val('').trigger('change');
        }
      })
    }

    function tombolPanah(objname){
        // function kalo mau pindah filed dari atas ke bawah atau sebaliknya
        let obj = $('input[name="'+objname+'[]"]');
        obj.keyup(function(e) {
            indexnya= obj.index(this);
            indexnya=parseInt(indexnya);
            if (e.keyCode == 38) {
                //panah atas
                indexTarget = indexnya-1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
            if (e.keyCode == 40) {
                //panah bawah
                indexTarget = indexnya+1;
                obj.eq(indexTarget).focus().select();
                return false;
            }
        });
    }
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection