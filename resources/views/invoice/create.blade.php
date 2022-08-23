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
                    <h4 class="card-title">Status: <span id="statusText"></span></h4>
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
                            {{-- <input type="text" id="article" name="article" hidden> --}}
                            <input type="text" id="ppn" name="ppn"  values="10" hidden>
                            <input type="text" id="pph23" name="ppn23" values="2" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="invNumber">Invoice Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="invNumber" name="invNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="invDate">Invoice Date*</label>
                                    <input type="text" id="invDate" name="invDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <select class="select2 form-control" id="customer" name="customer" required>
                                        <option value="">All</option>
                                        @foreach($customers as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="soNumber">SO Number*</label>
                                    <select class="select2 form-control" id="soNumber" name="soNumber" required>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="dnNumber">DN Number*</label>
                                    <select class="select2 form-control" id="dnNumber" name="dnNumber" >
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-12">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
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
                                    <td class="" style="width: 39%">
                                        <label>Article Code</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Qty</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>UOM</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Material Price</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>Service Price</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>T.Material</label>
                                    </td>
                                    <td class="isian" style="width: 10%">
                                        <label>T.Service</label>
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
                    <input type="text" id ="last_row_number" class="d-none" value="0">
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                    </div>
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
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            {{-- <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button> --}}
                            <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            {{-- @can('receiving-posting') --}}
                                <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                            {{-- @endcan --}}
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>
@include('invoice.addArticle')
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
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $("#totalQtyFree").val(humanizeNumber(0));
        $("#grandTotalQty").val(humanizeNumber(0));


        $('#statusText').text('New');
        $('#invDate').val(currentDate);
        $('#cmdSave').show();
        $('#cmdPosting').hide();
    });

    invDate = $('#invDate');
    if (invDate.length) {
        invDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today"
        });
    }

    
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdNew").click(function(){
        reloadPage();
    });

    $("#cmdSave").click(function(){    
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let objQty= $('#article_row input[name="qtyInv[]"]');
            let objPrice= $('#article_row input[name="price[]"]');
            let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
            let objUom= $('#article_row span[name="uom[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row input[name='articleId[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleCode = $this.data("code");
                    let articleDesc = $this.data("desc");
                    let articleUom = $this.data("uom");
                    let articleSoCode = $this.data("so-code");
                    let articleDnNumber = $this.data("dn-number");
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let price=objPrice.eq(i).val().replace(/,/gi, '') || 0;
                    let priceJasa=objPriceJasa.eq(i).val().replace(/,/gi, '') || 0;
                    
                    if ((articleCode!=='') && (qty> 0)){
                        articles.push({
                            "article_code":articleCode,
                            "qty":qty,
                            "uom":articleUom,
                            "price":price,
                            "price_service":priceJasa,
                            "so_number":articleSoCode,
                            "dn_number":articleDnNumber
                        });
                    }

                    // console.log(articles);
                                    
                    if (qty == 0){
                        pesan +="QTY of items "+ articleDesc +" cannot be 0 <br>"; 
                        flag=1;
                    }
                    // if (inisial !== customer){
                    //     pesan +="This article "+ articleName +" does not belong to the customer "+custName +" <br>"; 
                    //     flag=1;
                    // }
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){

                let invDate = $('#invDate').val();
                let customer = $('#customer').val()
                let ppn = $('#ppn').val().replace(/,/gi, '') || 10;
                let pph23 = $('#pph23').val().replace(/,/gi, '') || 2;
                let totalPpn = $('#totalPPN').val().replace(/,/gi, '') || 0;
                let totalPph = $('#totalPPH').val().replace(/,/gi, '') || 0;
                let note = $('#note').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('invoice.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        invDate:invDate,
                        customer:customer,
                        ppn:ppn,
                        pph23:pph23,
                        totalPpn:totalPpn,
                        totalPph:totalPph,
                        note:note
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }

                            $('#invNumber').attr('disabled','disabled');

                        }else{
                            show_msg(data.title, data.message, data.alert);

                            $('#invNumber').val(data.invNumber);
                            $('#invNumber').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
                        }
                        
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });

            }else{
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    });

    $("#cmdPosting").click(function(){
        let objQty= $('input[name="qty_inv[]"]');
        let objUom= $('select[name="uom[]"]');
        let objQtyFree= $('input[name="qty_free[]"]');
        let objUomFree= $('select[name="uomFree[]"]');
        
        let invNumber = $('#invNumber').val();            
        $.ajax({
            type: "post",
            url: "{{ route('receiving.posting') }}",
            data: {
                invNumber:invNumber
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
                    $('#invNumber').attr('disabled','disabled');
                    $('#cmdSave').show();
                    $('#cmdPosting').hide();

                }else{
                    $("#alert-message-success").addClass(data.alert);
                    $("#alert-message-success .alert-body").html(data.message);
                    $("#alert-message-success").show();
                    $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
                        $("#alert-message-success").slideUp(500);
                    });
                    $('#statusText').text(data.statusRec);
                    $('#cmdSave').hide();
                    $('#deleteButton').hide();
                    $('#cmdPosting').hide();
                    $('#invNumber').attr('disabled','disabled');
                    $('#soNumber').attr('disabled','disabled');
                    $('#customer').attr('disabled','disabled');
                    $('#invDate').attr('disabled','disabled');
                    $('#invDate').attr('disabled','disabled');
                    $('#invNumber').attr('disabled','disabled');
                    objQty.attr('disabled','disabled');
                    objUom.attr('disabled','disabled');
                    objQtyFree.attr('disabled','disabled');
                    objUomFree.attr('disabled','disabled');
                    
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
             
    });

    

    $('#customer').change(function(){
        let value= $(this).val();
        searchSo('soNumber',value);
    });

    let cloneCount=0;
    function add_new_row(article,articleCode,articleDesc,qty,uomGroup,uom,price,priceJasa,soCode,dnNumber) {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtyInv').attr('id', 'qtyInv'+ cloneCount);
        $("#new_row"+ cloneCount).find('#totalLine').attr('id', 'totalLine'+ cloneCount);
        $("#new_row"+ cloneCount).find('#totalJasa').attr('id', 'totalJasa'+ cloneCount);
        $("#new_row"+ cloneCount).find('#subTotal').attr('id', 'subTotal'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        $("#new_row"+ cloneCount).find('#price').attr('id', 'price'+ cloneCount);
        $("#new_row"+ cloneCount).find('#priceJasa').attr('id', 'priceJasa'+cloneCount);
        $('#articleId'+ cloneCount).attr('data-code', article);
        $('#articleId'+ cloneCount).attr('data-desc', articleDesc);
        $('#articleId'+ cloneCount).attr('data-uom', uom);
        $('#articleId'+ cloneCount).attr('data-price', price);
        $('#articleId'+ cloneCount).attr('data-price-service', priceJasa);
        $('#articleId'+ cloneCount).attr('data-so-code', soCode);
        $('#articleId'+ cloneCount).attr('data-dn-number', dnNumber);
        // $('#articleId'+ cloneCount).val(articleCode +" - " + articleDesc);
        $('#articleId'+ cloneCount).val(articleDesc);
        $('#price'+ cloneCount).val(price);
        $('#priceJasa'+ cloneCount).val(priceJasa);
        $('#qtyInv'+ cloneCount).val(qty);
        $('#uom'+ cloneCount).val(uom);
        $('#totalLine'+ cloneCount).text(humanizeNumber(qty*price));
        $('#totalJasa'+ cloneCount).text(humanizeNumber(qty*priceJasa));
        $('#subTotal'+ cloneCount).text(humanizeNumber((qty*price)+(qty*priceJasa)));
        tombolPanah('qtyInv');
        mask_thousand();
        hitungTotal();
        hitungGrandTotal();
        
    }

    

    function searchDnDet(dnNumber,soNumber) {
        $.ajax({
            url:"{{ route('invoice.dn.det') }}",
            method:"GET",
            data:{
                soNumber:soNumber,
                dnNumber:dnNumber
            },
            success:function(result){                
                if(result.length > 0 ){
                    for (let i = 0; i < result.length; i++) {
                        article=result[i].article_code;
                        articleCode=result[i].article_alternative_code;
                        articleDesc=result[i].article_desc;
                        qtySo=result[i].qty;
                        uomGroup=result[i].uom_group;
                        uom=result[i].uom;
                        price=result[i].price;
                        priceService=result[i].price_service;
                        soCode=result[i].so_number;
                        dnNumber=result[i].delivery_number;
                        add_new_row(article,articleCode,articleDesc,qtySo,uomGroup,uom,price,priceService,soCode,dnNumber);
                    }
                }
                
            },
            error: function (response) {
                Swal.fire("Warning","Get detail PO failed","warning");
            }
        })
    }

    $('#soNumber').change(function(){
        let value= $(this).val();
        searchDn('dnNumber',value);
    })

    $('#dnNumber').change(function(){
        let dn = $(this).val();
        let so = $('#soNumber').val();
        searchDnDet(dn,so);
    })

    function listUom(obj,value,uom) {
      $.ajax({
        url:"{{ route('invoice.list.uom') }}",
        method:"GET",
        data:{
            value:value,
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val(uom).trigger('change');            
        },
        error: function (response) {
            Swal.fire("Warning","Get list UOM failed","warning");
        }
      })
    }
    
    function hitungTotal(){
        let objQtyInv= $('#article_row input[name="qtyInv[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objTotal= $('#article_row span[name="totalLine[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let objTotalJasa= $('#article_row span[name="totalJasa[]"]');
        let objSubTotal= $('#article_row span[name="subTotal[]"]');
                
        objQtyInv.keyup(function() {

            let indexnya= objQtyInv.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/,/gi, '') ||0;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '') ||0;
            let total = qty*price;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            objTotalJasa.eq(indexnya).text(humanizeNumber(totalJasa));
            objSubTotal.eq(indexnya).text(humanizeNumber(total+totalJasa));
            hitungGrandTotal();

        });

        objPrice.keyup(function() {
            let indexnya= objPrice.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/,/gi, '')||0;
            let total = qty*price;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '')||0;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            objTotalJasa.eq(indexnya).text(humanizeNumber(totalJasa));
            objSubTotal.eq(indexnya).text(humanizeNumber(total+totalJasa));
            hitungGrandTotal();
        });    

        objPriceJasa.keyup(function() {
            let indexnya= objPrice.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            let price = objPrice.eq(indexnya).val().replace(/,/gi, '')||0;
            let total = qty*price;
            let priceJasa = objPriceJasa.eq(indexnya).val().replace(/,/gi, '')||0;
            let totalJasa = qty*priceJasa;
            objTotal.eq(indexnya).text(humanizeNumber(total));
            objTotalJasa.eq(indexnya).text(humanizeNumber(totalJasa));
            objSubTotal.eq(indexnya).text(humanizeNumber(total+totalJasa));
            hitungGrandTotal();
        });

    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row select[name="articleId[]"]');
        let objQtyTiw= $('#article_row input[name="qtyInv[]"]');
        let objQTY= $('#article_row input[name="qtyInv[]"]');
        let objPrice= $('#article_row input[name="price[]"]');
        let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
        let ppn= $('#ppn').val() || 10;
        let pph23= $('#pph23').val() || 2;
        let totalQty= 0;
        let totalAmount=0
        let totalAmountJasa=0
        let totalAmountMaterial=0

        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/,/gi, '')) || 0;
            let price = parseInt(objPrice.eq(i).val().replace(/,/gi, '')) || 0;
            let priceJasa = parseInt(objPriceJasa.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
            totalAmount+= (qty*price)+(qty*priceJasa);
            totalAmountMaterial+= (qty*price)+(qty*priceJasa);
            totalAmountJasa+= (qty*priceJasa);
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#nilaiPPN").text(ppn+"%");
        $("#nilaiPPH23").text(pph23+"%");
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalAmount").val(humanizeNumber(totalAmount));
        $("#totalPPN").val(humanizeNumber((parseInt(ppn)*totalAmountMaterial)/100));
        $("#totalPPH").val("-"+humanizeNumber((pph23*totalAmountJasa)/100));
        $("#totalNetto").val(humanizeNumber(totalAmount+((parseInt(ppn)*totalAmount)/100)-((pph23*totalAmountJasa)/100)));
    
    }

    // function tombolPanah(objname){
    //     // function kalo mau pindah filed dari atas ke bawah atau sebaliknya
    //     let obj = $('input[name="'+objname+'[]"]');
    //     obj.keyup(function(e) {
    //         indexnya= obj.index(this);
    //         indexnya=parseInt(indexnya);
    //         if (e.keyCode == 38) {
    //             //panah atas
    //             indexTarget = indexnya-1;
    //             obj.eq(indexTarget).focus().select();
    //             return false;
    //         }
    //         if (e.keyCode == 40) {
    //             //panah bawah
    //             indexTarget = indexnya+1;
    //             obj.eq(indexTarget).focus().select();
    //             return false;
    //         }
    //     });
    // }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection