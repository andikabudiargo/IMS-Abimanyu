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
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusInv }}</span></h4>
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
                                    <label for="invNumber">Invoice Number</label> <small class="text-muted"> automatic </small>
                                    <input type="text" id="invNumber" name="invNumber" value="{{ $header->invoice_number }}" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="invDate">Invoice Date*</label>
                                    <input type="text" id="invDate" name="invDate" value="{{ $header->invoice_date }}" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <select class="select2 form-control" id="customer" name="customer" required disabled>
                                        <option value="">All</option>
                                        @foreach($customers as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->customer_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="soNumber">SO Number*</label>
                                    <select class="select2 form-control" id="soNumber" name="soNumber">
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
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
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
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('invoice.index') }}" class="btn btn-light">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusInv =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" >Update</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusInv =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" >Update</button>
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
                                            <div class="avatar bg-light-{{ $val->statusapprove == 1 ? 'success':'warning' }} mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="{{ $val->statusapprove == 1 ? 'check':'x' }}" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">{{ $val->statusapprove == 1 ? 'Approve':'Decline' }}-{{ $val->approval_order }}</h4>
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
@include('invoice.addArticle')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    
    $(document).ready(function(){
        
        searchSo('soNumber',customer.val());
        let detail = {!!  $detail !!};
        for (let i = 0; i < detail.length; i++) {
            article=detail[i].article_code;
            articleCode=detail[i].article_alternative_code;
            articleDesc=detail[i].article_desc;
            qtySo=detail[i].qty;
            uomGroup=detail[i].uom_group;
            uom=detail[i].uom;
            price=detail[i].price;
            priceService=detail[i].price_service;
            soCode=detail[i].so_number;
            dnNumberData=detail[i].dn_number;
            poNumber=detail[i].po_number;
            add_new_row(article,articleCode,articleDesc,qtySo,uomGroup,uom,price,priceService,soCode,dnNumberData,poNumber);
        }
        hitungTotal();
    });

    const approveBtn = document.querySelector('#cmdApprove');

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            let invNumber = $('#invNumber').val();
            approve(invNumber,'cmdApprove');
        },{ once:true});
    }

    approve = (invNumber,objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "POST",
            url: "{{ route('invoice.approve') }}",
            data: {
                invNumber:invNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#invNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#invNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');      
                    window.location.reload();                 
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    invDate = $('#invDate');
    if (invDate.length) {
        invDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today"
        });
    }

    recDate = $('#recDate');
    if (recDate.length) {
        recDate.flatpickr({
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
                    let poNumber = $this.data("po-number");
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
                            "dn_number":articleDnNumber,
                            "po_number":poNumber
                        });
                    }

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
                let invNumber = $('#invNumber').val();
                let note = $('#note').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('invoice.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        invNumber:invNumber,
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
                            $('#customer').attr('disabled','disabled');
                            // $('#cmdSave').attr('disabled','disabled');
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

    // $("#cmdPosting").click(function(){
        
    //     let recNumber = $('#recNumber').val();            
    //     $.ajax({
    //         type: "post",
    //         url: "{{ route('receiving.posting') }}",
    //         data: {
    //             recNumber:recNumber
    //         },
    //         dataType: "json",
    //         success: function(data) {
    //             if (data.status == 0 ){
    //                 let message="";
    //                 for(let i = 0; i < data.message.length; i++) {
    //                     message += "-"+data.message[i]+"<br>";                           
    //                 }
    //                 $("#alert-message-success").addClass(data.alert);
    //                 $("#alert-message-success .alert-body").html(message);
    //                 $("#alert-message-success").show();
    //                 $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
    //                     $("#alert-message-success").slideUp(500);
    //                 });
    //                 $('#recNumber').attr('disabled','disabled');
    //                 $('#cmdSave').show();
    //                 $('#cmdPosting').hide();

    //             }else{
    //                 $("#alert-message-success").addClass(data.alert);
    //                 $("#alert-message-success .alert-body").html(data.message);
    //                 $("#alert-message-success").show();
    //                 $("#alert-message-success").fadeTo(5000, 500).slideUp(500, function(){
    //                     $("#alert-message-success").slideUp(500);
    //                 });
    //                 $('#statusText').text(data.statusRec);
    //                 $('#cmdSave').hide();
    //                 $('#deleteButton').hide();
    //                 $('#cmdPosting').hide();
    //                 $('#recNumber').attr('disabled','disabled');
    //                 $('#poNumber').attr('disabled','disabled');
    //                 $('#addNewRow').attr('disabled','disabled');
                    
    //             }
    //         },
    //         error: function(error) {
    //             console.log(error);
    //         }
    //     });
            
        
    // });
    
    let cloneCount=0;
    function add_new_row(article,articleCode,articleDesc,qty,uomGroup,uom,price,priceJasa,soCode,dnNumber,poNumber) {
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
        $('#articleId'+ cloneCount).attr('data-po-number', poNumber);
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

    function listUom(obj,value,uom,uomSelect) {
      $.ajax({
        url:"{{ route('receiving.list.uom') }}",
        method:"GET",
        data:{
            value:value,
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val(uomSelect).trigger('change');            
        },
        error: function (response) {
            Swal.fire("Warning","Get list UOM failed","warning");
        }
      })
    }

    // function hitungTotal(){
    //     let objQtyRec= $('#article_row input[name="qty_rec[]"]');
    //     let objQtyFree= $('#article_row input[name="qty_free[]"]');
    //     let objTotalQty= $('#article_row span[name="totalQty[]"]');
        
    //     objQtyRec.keyup(function() {
    //         let indexnya= objQtyRec.index(this);
    //         let qtyRec = parseInt(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
    //         let qtyFree = parseInt(objQtyFree.eq(indexnya).val().replace(/,/gi, '') || 0); 
    //         let totalQty = qtyRec+qtyFree;
    //         objTotalQty.eq(indexnya).text(humanizeNumber(totalQty));
    //         hitungGrandTotal();
    //     });    

    //     objQtyFree.keyup(function() {
    //         let indexnya= objQtyRec.index(this);
    //         let qtyRec = parseInt(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
    //         let qtyFree = parseInt(objQtyFree.eq(indexnya).val().replace(/,/gi, '') || 0); 
    //         let totalQty = qtyRec+qtyFree;
    //         objTotalQty.eq(indexnya).text(humanizeNumber(totalQty));
    //         hitungGrandTotal();
    //     });
            
    // }

    // function hitungGrandTotal(){
    //     let objArticle = $('#article_row input[name="article_id[]"]');
    //     let objQtyRec= $('#article_row input[name="qty_rec[]"]');
    //     let objQtyFree= $('#article_row input[name="qty_free[]"]');
    //     let totalQty= 0;
    //     let totalQtyFree= 0;

    //     var arr = objQtyRec.map(function (i) {
    //         let qty = parseInt(objQtyRec.eq(i).val().replace(/,/gi, '')) || 0;
    //         let qtyFree = parseInt(objQtyFree.eq(i).val().replace(/,/gi, '')) || 0;
    //         totalQty+= qty;
    //         totalQtyFree+= qtyFree;
    //     }).get();
    //     grandTotalQty=totalQty+totalQtyFree;
        
    //     $("#totalRow").val(objArticle.length);
    //     $("#totalQTY").val(humanizeNumber(totalQty));
    //     $("#totalQtyFree").val(humanizeNumber(totalQtyFree));
    //     $("#grandTotalQty").val(humanizeNumber(grandTotalQty));
    // }

    // function hitungGrandTotalLoad(){
    //     let objArticle = $('#article_row input[name="article_id[]"]');
    //     let objQtyRec= $('#article_row input[name="qty_rec[]"]');
    //     let objQtyFree= $('#article_row input[name="qty_free[]"]');
    //     let objTotalQty= $('#article_row span[name="totalQty[]"]');
        
    //     let totalQty= 0;
    //     let totalQtyFree= 0;

    //     var arr = objQtyRec.map(function (i) {
    //         let qty = parseInt(objQtyRec.eq(i).val().replace(/,/gi, '')) || 0;
    //         let qtyFree = parseInt(objQtyFree.eq(i).val().replace(/,/gi, '')) || 0;
    //         totalQty+= qty;
    //         totalQtyFree+= qtyFree;
    //         objTotalQty.eq(i).text(humanizeNumber(qty+qtyFree));
    //     }).get();
    //     grandTotalQty=totalQty+totalQtyFree;
        
    //     $("#totalRow").val(objArticle.length);
    //     $("#totalQTY").val(humanizeNumber(totalQty));
    //     $("#totalQtyFree").val(humanizeNumber(totalQtyFree));
    //     $("#grandTotalQty").val(humanizeNumber(grandTotalQty));
    // }

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