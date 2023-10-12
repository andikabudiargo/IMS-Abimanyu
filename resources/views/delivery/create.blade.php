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
                            <input type="hidden" id="idDn" name="idDn" class="form-control" />
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="dnNumber">Delivery Note Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="dnNumber" name="dnNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="dnDate">Delivery Date*</label>
                                    <input type="text" id="dnDate" name="dnDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="poNumberHdr">PO Number</label>
                                    <input type="text" id="poNumberHdr" name="poNumberHdr" class="form-control" disabled />
                                </div>                          
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <select class="select2 form-control" id="customer" name="customer" required>
                                        <option value=""></option>
                                        @foreach($customers as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="soNumber">SO Number*</label>
                                    <select class="select2 form-control" id="soNumber" name="soNumber" required>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-9">
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
                    @include('delivery.headerColumn')
                    <input type="text" id ="last_row_number" class="d-none" value="0">
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua tanpa-padding">Row(s)</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua tanpa-padding">Total QTY</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            {{-- <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button> --}}
                            {{-- <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button> --}}
                            <a href="{{ route('delivery.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            <button class="btn btn-dark" type="button" id="cmdPrint" name="cmdPrint">Print</button>
                            {{-- @can('receiving-posting') --}}
                                {{-- <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button> --}}
                            {{-- @endcan --}}
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>
@include('delivery.addArticle')
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
        $('#statusText').text('New');
        $('#dnDate').val(currentDate);
        $('#cmdSave').show();
        // $('#cmdPosting').hide();
        $('#cmdPrint').hide();
    });

    dnDate = $('#dnDate');
    if (dnDate.length) {
        dnDate.flatpickr({
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
            $("#cmdSave").attr('disabled','disabled');
            $('.disabled-el').removeAttr('disabled');
            let objQtySo= $('#article_row input[name="qtySo[]"]');
            let objQty= $('#article_row input[name="qtyInv[]"]');
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
                    let poNumber = $this.data("po-number");
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qtySo=objQtySo.eq(i).val().replace(/,/gi, '') || 0;
                    
                    if ((articleCode!=='') && (qty> 0)){
                        articles.push({
                            "article_code":articleCode,
                            "qty":qty,
                            "uom":articleUom,
                            "so_number":articleSoCode,
                            "po_number":poNumber,
                            "qty_so":qtySo
                        });
                    }

                    if (parseInt(qty) > parseInt(qtySo)){
                        pesan +="Items "+ articleDesc +"-"+qty+"-"+qtySo+" QTY Delivery is higher than QTY SO<br>"; 
                        flag=1;
                    }
                    
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){

                let dnDate = $('#dnDate').val();
                let customer = $('#customer').val();
                let soNumber = $('#soNumber').val();
                let poNumber = $('#soNumber').find(":selected").data("po-number");
                let note = $('#note').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('delivery.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        dnDate:dnDate,
                        customer:customer,
                        soNumber:soNumber,
                        poNumber:poNumber,
                        note:note
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#dnNumber').attr('disabled','disabled');
                            $('#cmdSave').removeAttr('disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#dnNumber').val(data.dnNumber);
                            $('#statusText').val('NEW');
                            $('#idDn').val(data.id);
                            $('#dnNumber').attr('disabled','disabled');
                            // $('#cmdSave').attr('disabled','disabled');
                            $('#cmdSave').hide();
                            $('#cmdPrint').show();
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

    $("#cmdPrint").click(function(){
        /* Posting langdung print*/
        let objQty= $('input[name="qtyInv[]"]');
        let objUom= $('select[name="uom[]"]');       
        let dnNumber = $('#dnNumber').val();   
        let idDn = $('#idDn').val();  
        console.log(idDn);  
        let dariNew = 'true';     
        $.ajax({
            type: "post",
            url: "{{ route('delivery.posting') }}",
            data: {
                dnNumber:dnNumber,
                id:idDn,
                dariNew:dariNew
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#dnNumber').attr('disabled','disabled');
                    $('#cmdSave').show();
                    $('#cmdPrint').hide();

                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#cmdSave').hide();
                    // $('#deleteButton').hide();
                    $('#statusText').text('POSTED');
                    $('#cmdPrint').hide();
                    $('#dnNumber').attr('disabled','disabled');
                    $('#soNumber').attr('disabled','disabled');
                    $('#customer').attr('disabled','disabled');
                    $('#dnDate').attr('disabled','disabled');
                    objQty.attr('disabled','disabled');
                    objUom.attr('disabled','disabled');

                    let id = data.idKu;
                    let url = "{{ route('delivery.print', ['id'=>':id']) }}";
                    url = url.replace('%3Aid', id);
                    window.open(url, '_blank');
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    });

    function searchSo(obj,value) {
        if(value){
            $.ajax({
                url:"{{ route('delivery.list.so') }}",
                method:"GET",
                data:{
                    value:value,
                },
                success:function(result){
                    $('#'+obj).html(result);
                    // $('#'+obj).val('').trigger('change');
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list SO failed","warning");
                }
            })
        }
    }

    $('#customer').change(function(){
        let value= $(this).val();
        $("#poNumberHdr").val('');
        if(value){
            searchSo('soNumber',value);
        }
    });

    let cloneCount=1;
    function add_new_row(article,articleCode,articleDesc,qtySo,uomGroup,uom,price,priceJasa,soCode,poNumber) {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#qtySo').attr('id', 'qtySo'+ cloneCount);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        $("#new_row"+ cloneCount).find('#articleId').attr('id', 'articleId'+ cloneCount);
        $('#articleId'+ cloneCount).attr('data-code', article);
        $('#articleId'+ cloneCount).attr('data-desc', articleDesc);
        $('#articleId'+ cloneCount).attr('data-uom', uom);
        $('#articleId'+ cloneCount).attr('data-price', price);
        $('#articleId'+ cloneCount).attr('data-price-service', priceJasa);
        $('#articleId'+ cloneCount).attr('data-so-code', soCode);
        $('#articleId'+ cloneCount).attr('data-po-number', poNumber);
        $('#articleId'+ cloneCount).attr('data-so-qty', qtySo);
        $('#articleId'+ cloneCount).val(articleCode+'-'+articleDesc);
        $('#uom'+ cloneCount).val(uom);
        $('#qtySo'+ cloneCount).val(qtySo*1);
        tombolPanah('qtyInv');
        mask_thousand();
        hitungTotal();
        cekQty();
    }

    function searchSoDet(value) {
        if(value){
            $.ajax({
                url:"{{ route('delivery.so.det') }}",
                method:"GET",
                data:{
                    value:value,
                },
                success:function(result){
                    if (cloneCount > 1){
                        $("#article_row").empty();
                        cloneCount=1;
                    }

                    if(result.length > 0 ){
                        for (let i = 0; i < result.length; i++) {
                            article=result[i].article_code;
                            articleCode=result[i].article_alternative_code;
                            articleDesc=result[i].article_desc;
                            qtySo=result[i].qty_so;
                            uomGroup=result[i].uom_group;
                            uom=result[i].uom;
                            price=result[i].price;
                            priceService=result[i].price_service;
                            soCode=result[i].so_code;
                            poNumber=result[i].po_number;
                            add_new_row(article,articleCode,articleDesc,qtySo,uomGroup,uom,price,priceService,soCode,poNumber);
                        }
                    }
                    
                },
                error: function (response) {
                    Swal.fire("Warning","Get detail SO failed","warning");
                }
            })
        }
    }

    $('#soNumber').change(function(){
        let value= $(this).val();
        $("#poNumberHdr").val('');
        if(value){
            let poNumber = $(this).find(":selected").data("po-number");
            $("#poNumberHdr").val(poNumber);
            searchSoDet(value);
        }
    })
    
    function hitungTotal(){
        let objQtyInv= $('#article_row input[name="qtyInv[]"]');
        objQtyInv.keyup(function() {
            let indexnya= objQtyInv.index(this);
            let qty = objQtyInv.eq(indexnya).val().replace(/,/gi, '') || 0; 
            hitungGrandTotal();
        });
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row input[name="articleId[]"]');
        let objQtyTiw= $('#article_row input[name="qtyInv[]"]');
        let objQTY= $('#article_row input[name="qtyInv[]"]');
        let totalQty=0;
        var arr = objQtyTiw.map(function (i) {
            let qty = parseInt(objQTY.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
        }).get();
        
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(humanizeNumber(totalQty));
    }

    function cekQty(){
        let objQtySo= $('#article_row input[name="qtySo[]"]');
        let objQtyDel= $('#article_row input[name="qtyInv[]"]');

        objQtyDel.keyup(function() {
            let indexnya= objQtyDel.index(this);
            let qtyDel = parseFloat(objQtyDel.eq(indexnya).val().replace(/,/gi, '') || 0);
            let qtySo = parseFloat(objQtySo.eq(indexnya).val().replace(/,/gi, '') || 0); 
            if ( qtyDel > qtySo ){
                objQtyDel.eq(indexnya).delay(3000).css("background-color","rgba(255,0,0, 0.5)");
            }else{
                objQtyDel.eq(indexnya).delay(3000).css("background-color","");
            }
            hitungGrandTotal();
        });    
    }

    // $("#cmdPosting").click(function(){
    //     let objQty= $('input[name="qtyInv[]"]');
    //     let objUom= $('select[name="uom[]"]');       
    //     let dnNumber = $('#dnNumber').val();            
    //     $.ajax({
    //         type: "post",
    //         url: "{{ route('delivery.posting') }}",
    //         data: {
    //             dnNumber:dnNumber
    //         },
    //         dataType: "json",
    //         success: function(data) {
    //             if (data.status == 0 ){
    //                 let message="";
    //                 for(let i = 0; i < data.message.length; i++) {
    //                     show_msg(data.title, data.message[i], data.alert);
    //                 }
    //                 $('#dnNumber').attr('disabled','disabled');
    //                 $('#cmdSave').show();
    //                 $('#cmdPosting').hide();

    //             }else{
    //                 show_msg(data.title, data.message, data.alert);

    //                 // $('#statusText').text(data.statusRec);
    //                 $('#cmdSave').hide();
    //                 $('#deleteButton').hide();
    //                 $('#cmdPosting').hide();
    //                 $('#dnNumber').attr('disabled','disabled');
    //                 $('#soNumber').attr('disabled','disabled');
    //                 $('#customer').attr('disabled','disabled');
    //                 $('#dnDate').attr('disabled','disabled');
    //                 objQty.attr('disabled','disabled');
    //                 objUom.attr('disabled','disabled');
                    
    //             }
    //         },
    //         error: function(error) {
    //             console.log(error);
    //         }
    //     });
             
    // });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection