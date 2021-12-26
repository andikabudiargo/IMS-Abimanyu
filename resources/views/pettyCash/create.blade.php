@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="form-row">
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
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="pcNumber">Petty Cash Code</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="pcNumber" name="pcNumber" class="form-control disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="vocherNumber">Voucher Number</label>
                                    <input type="text" id="vocherNumber" name="vocherNumber" class="form-control" />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="periode">Period*</label>
                                    <select class="select2 form-control" id="periode" name="periode" required>
                                        <option value=""></option>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        @foreach($currency as $val)
                                        <option value="{{$val}}">{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <div class="form-group">
                                        <label for="kurs">Kurs</label>
                                        <input type="text" id="kurs" name="kurs" class="form-control angka" maxlength="6"  />
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-11">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-12">
                                    <a href="{{ route('pettyCashs.index') }}" class="btn btn-warning">Back</a>
                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
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
                <div class="card-body" >
                    <div>
                        <table class="" style="width:98%;table-layout: fixed;">
                            <tbody>
                                <tr>
                                    <td class="isian-satu" style="width: 15%">
                                        <label>Description</label>
                                    </td>
                                    <td class="">
                                        <label>CG</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Debit</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Credit</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Account</label>
                                    </td>
                                    <td class="isian d-none" style="width: 10%">
                                        <label>Account name</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>      
                    <div class="" id="item_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="0">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add</span>
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
@include('pettyCash.addArticle')
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
        validateForm('frmAdd');
        $('#orderDate').val(currentDate);
    });

    function keyUp(obj){
        $("#"+obj).keyup(function(){
            alert($(this).val());
            // $.ajax({
            //     type: "POST",
            //     url: "readCountry.php",
            //     data:'keyword='+$(this).val(),
            //     beforeSend: function(){
            //         $("#search-box").css("background","#FFF url(LoaderIcon.gif) no-repeat 165px");
            //     },
            //     success: function(data){
            //         $("#suggesstion-box").show();
            //         $("#suggesstion-box").html(data);
            //         $("#search-box").css("background","#FFF");
            //     }
            // });
	    });
    }
    

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
    
   
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel").click(function(){
        reloadPage();
    });

    $("#cmdNew").click(function(){
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

        $("#item_row select[name='pcDesc[]']").map(function(i) {  
		    let $this=$(this);
            if ($this.val()){
                let article=$this.val().split("|");
                let articleName=$this.select2('data')[0].text;
                let plu=article[0];
                let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
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
                    pesan +="Article "+articleName+" entered more than once !! <br>"; 
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
            let poType = $('#poType').val();
            let deliveryDate = $('#deliveryDate').val();
            let currency = $('#currency').val();
            let supp = $('#supplier').val();
            let tax = $('#tax').val();
            let term = $('#term').val() || 0;
            let kurs = $('#kurs').val() || 1;
            let ppn = $('#ppn').val().replace(/[^0-9]/gi, '') || 0;
            let totalPph = $('#totalPPH').val().replace(/[^0-9]/gi, '') || 0;
            let totalPpn = $('#totalPPN').val().replace(/[^0-9]/gi, '') || 0;
            let note = $('#note').val();
            let persenDiscount = $('#persenDiscount').val() || 0;

            $.ajax({
                type: "post",
                url: "{{ route('purchaseOrder.store') }}",
                data: {
                    articles:JSON.stringify(articles),
                    orderDate:orderDate,
                    poType:poType,
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
                        $('#poNumber').attr('disabled','disabled');

                    }else{
                        $("#alert-message-success").addClass(data.alert);
                        $("#alert-message-success .alert-body").html(data.message);
                        $("#alert-message-success").show();
                        $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                            $("#alert-message-success").slideUp(500);
                        });
                        $('#poNumber').attr('disabled','disabled');
                        $('#cmdSave').attr('disabled','disabled');
                        $('#addNewRow').attr('disabled','disabled');
                        
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

    let cloneCount=1;
    function add_new_row() {
        $("#item_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#item_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#pcDesc').attr('id', 'pcDesc'+ cloneCount);
        $("#new_row"+ cloneCount).find('#account').attr('id', 'account'+ cloneCount);
        keyUp('pcDesc'+ cloneCount);
        accList('account','account'+ cloneCount);
        $("#account"+cloneCount).select2();
        $('#remove_button').tooltip();
        tombolPanah('pcCashIn');
        tombolPanah('pcCashOut');
        activate_angka();
        mask_thousand();
        hitungTotal();
        hitungGrandTotal();
        $('[data-toggle="tooltip"]').tooltip();
    };

    function accList(dependent,obj) {
      $.ajax({
        url:"{{route('dynamic.dependent')}}",
        method:"POST",
        data:{
            dependent:dependent
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val('').trigger('change');
        }
      })
    }

    function hitungTotal(){
        let objQty= $('#item_row input[name="qty_order[]"]');
        let objNewPrice= $('#item_row input[name="newPrice[]"]');
        let objTotal= $('#item_row span[name="totalLine[]"]');
        
        objQty.keyup(function() {
            let indexnya= objQty.index(this);
            let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let newPrice = objNewPrice.eq(indexnya).val().replace(/[^0-9]/gi, '') ||0;
            let total = qty*newPrice;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            hitungGrandTotal();
        });    

        objNewPrice.keyup(function() {
            let indexnya= objNewPrice.index(this);
            let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let newPrice = objNewPrice.eq(indexnya).val().replace(/[^0-9]/gi, '')||0;
            let total = qty*newPrice;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            hitungGrandTotal();
        });    
    }

    function hitungGrandTotal(){
        let objArticle = $('#item_row select[name="pcDesc[]"]');
        let objQtyTiw= $('#item_row input[name="qty_order[]"]');
        let objQTY= $('#item_row input[name="qty_order[]"]');
        let objNewPrice= $('#item_row input[name="newPrice[]"]');
        let persenDiscount = $('#persenDiscount').val() || 0;
        let ppn= $('#ppn').val();
        let totalQty= 0;
        let totalAmount=0

        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/,/gi, '')) || 0;
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