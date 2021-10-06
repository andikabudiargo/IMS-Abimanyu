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
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label for="recNumber">Receiving Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="recNumber" name="recNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="recDate">Receiving Date*</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>                               
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <select class="select2 form-control" id="customer" name="customer" required>
                                        <option label=""></option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="soNumber">SO Number*</label>
                                    <select class="select2 form-control" id="soNumber" name="soNumber" required>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="docDate">Document Date*</label>
                                    <input type="text" id="docDate" name="docDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>                               
                                <div class="form-group col-md-3">
                                    <label for="docNumber">Document Number*</label>
                                    <input type="text" id="docNumber" name="docNumber" class="form-control disabled-el" required/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                    @can('receiving-posting')
                                        <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                                    @endcan
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
                                    <td class="" style="width: 25%">
                                        <label>Article Code</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Qty SO</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Qty</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>UOM</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Total Qty</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>      
                    <input type="text" id ="last_row_number" class="d-none" value="0">
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4 ">
                            <div class="form-group row mb-04">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua">Total Qty</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="grandTotalQty" class="col-sm-4 col-form-label titik-dua">Grand Total Qty</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="grandTotalQty" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('receivingRm.addArticle')
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

        $("#frmAdd").validate({
            invalidHandler: function(event, validator) {
            let errors = validator.numberOfInvalids();
            if (errors) {
                let message = errors == 1
                    ? 'You missed 1 field. It has been highlighted'
                    : 'You missed ' + errors + ' fields. They have been highlighted';
                $("#alert-message .alert-body").html(message);
                $("#alert-message").show();
                $("#alert-message").fadeTo(5000, 500).slideUp(500, function(){
                    $("#alert-message").slideUp(500);
                });
            } else {
                $("#alert-message").hide();
            }
        }
        }).settings.ignore = "";

        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $("#totalQtyFree").val(humanizeNumber(0));
        $("#grandTotalQty").val(humanizeNumber(0));


        $('#statusText').text('New');
        $('#recDate').val(currentDate);
        $('#cmdSave').show();
        $('#cmdPosting').hide();
    });

    docDate = $('#docDate');
    if (docDate.length) {
        docDate.flatpickr({
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

    $("#cmdCancel").click(function(){
        reloadPage();
    });

    $("#cmdNew").click(function(){
        reloadPage();
    });

    $("#cmdSave").click(function(){

        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let objQty= $('input[name="qty_rec[]"]');
            let objUom= $('select[name="uom[]"]');            
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row input[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleCode = $this.data("code");
                    let articleUom = $this.data("uom");
                    let articlePrice = $this.data("price");
                    let article=$this.val().split("|");
                    let plu=article[0];
                    let articleName=article[1];
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyUom=objUom.eq(i).val() || articleUom;
                                        
                    articles.push({
                        "article_code":articleCode,
                        "qty":qty,
                        "uom":qtyUom,
                        "price":articlePrice
                    });
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let docNumber = $('#docNumber').val();
                let docDate = $('#docDate').val();
                let soNumber = $('#soNumber').val();
                let supp = $('#customer').val();
                let recDate = $('#recDate').val();
                let note = $('#note').val();
            
                $.ajax({
                    type: "post",
                    url: "{{ route('receivingRm.store') }}",
                    data: {
                        docNumber:docNumber,
                        docDate:docDate,
                        soNumber:soNumber,
                        supp:supp,
                        recDate:recDate,
                        note:note,
                        articles:JSON.stringify(articles)
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
                            $('#recNumber').attr('disabled','disabled');
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
                            $('#recNumber').val(data.recNumber);
                            $('#cmdSave').hide();
                            $('#cmdCancel').hide();
                            $('#cmdPosting').show();
                            $('#recNumber').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
                            $('#customer').attr('disabled','disabled');
                            $('#soNumber').attr('disabled','disabled');
                            $('#docDate').attr('disabled','disabled');
                            $('#recDate').attr('disabled','disabled');
                            $('docNumber').attr('disabled','disabled');

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
            }else{
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    });

    $("#cmdPosting").click(function(){
        let objQty= $('input[name="qty_rec[]"]');
        let objUom= $('select[name="uom[]"]');
        let objQtyFree= $('input[name="qty_free[]"]');
        let objUomFree= $('select[name="uomFree[]"]');
        
        let recNumber = $('#recNumber').val();            
        $.ajax({
            type: "post",
            url: "{{ route('receiving.posting') }}",
            data: {
                recNumber:recNumber
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
                    $('#recNumber').attr('disabled','disabled');
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
                    $('#recNumber').attr('disabled','disabled');
                    $('#soNumber').attr('disabled','disabled');
                    $('#customer').attr('disabled','disabled');
                    $('#docDate').attr('disabled','disabled');
                    $('#recDate').attr('disabled','disabled');
                    $('docNumber').attr('disabled','disabled');
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

    function searchSo(obj,value) {
      $.ajax({
        url:"{{ route('receivingRm.list.so') }}",
        method:"GET",
        data:{
            value:value,
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).val('').trigger('change');
        },
        error: function (response) {
            //Error here
            Swal.fire("Warning","Get list SO failed","warning");
        }
      })
    }

    $('#customer').change(function(){
        let value= $(this).val();
        searchSo('soNumber',value);
    });

    let cloneCount=1;
    function add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,price) {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        $('#article_id'+ cloneCount).attr('data-code', article);
        $('#article_id'+ cloneCount).attr('data-uom', uom);
        $('#article_id'+ cloneCount).attr('data-price', price);
        $('#article_id'+ cloneCount).val(articleCode +" - " + articleDesc);
        $("#new_row"+ cloneCount).find('#qty_so').attr('id', 'qty_so'+ cloneCount);
        $('#qty_so'+ cloneCount).val(qtyPo);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        listUom('uom'+ cloneCount,uomGroup,uom);
        tombolPanah('qty_rec');
        mask_thousand_digit(3);
        hitungTotal();
        
    }

    function searchSoDet(value) {
        $.ajax({
            url:"{{ route('receivingRm.so.det') }}",
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
                        qtyPo=result[i].qty_order;
                        uomGroup=result[i].uom_group;
                        uom=result[i].uom;
                        price=result[i].price;
                        add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,price);
                    }
                }
                
            },
            error: function (response) {
                Swal.fire("Warning","Get detail SO failed","warning");
            }
        })
    }

    $('#soNumber').change(function(){
        let value= $(this).val();
        searchSoDet(value);
    })

    function listUom(obj,value,uom) {
      $.ajax({
        url:"{{ route('receivingRm.list.uom') }}",
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
        let objQtyRec= $('#article_row input[name="qty_rec[]"]');
        let objTotalQty= $('#article_row span[name="totalQty[]"]');
        
        objQtyRec.keyup(function() {
            let indexnya= objQtyRec.index(this);
            let qtyRec = parseInt(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let totalQty = qtyRec;
            objTotalQty.eq(indexnya).text(humanizeNumber(totalQty));
            hitungGrandTotal();
        });    

        objQtyFree.keyup(function() {
            let indexnya= objQtyRec.index(this);
            let qtyRec = parseInt(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let totalQty = qtyRec;
            objTotalQty.eq(indexnya).text(humanizeNumber(totalQty));
            hitungGrandTotal();
        });
            
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row input[name="article_id[]"]');
        let objQtyRec= $('#article_row input[name="qty_rec[]"]');
        let totalQty= 0;

        var arr = objQtyRec.map(function (i) {
            let qty = parseInt(objQtyRec.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            totalQty+= qty;
        }).get();
        grandTotalQty=totalQty;
        
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#grandTotalQty").val(humanizeNumber(grandTotalQty));
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