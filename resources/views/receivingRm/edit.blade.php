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
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusRec }}</span></h4>
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
                                    <input type="text" id="recNumber" name="recNumber" class="form-control text-hitam disabled-el" value="{{ $header->rec_number }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="recDate">Receiving Date*</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->rec_date }}" required />
                                </div>                               
                            </div>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required disabled>
                                        <option label=""></option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->supplier_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="poNumber">PO Number*</label>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control text-hitam disabled-el" value="{{ $header->po_number }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="invDate">Invoice Date*</label>
                                    <input type="text" id="invDate" name="invDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->inv_date }}" required />
                                </div> 
                                <div class="form-group col-md-3">
                                    <label for="invNumber">Invoice Number</label>
                                    <input type="text" id="invNumber" name="invNumber" class="form-control disabled-el" value="{{ $header->inv_number }}" required/>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }} </textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12">
                                            <a href="{{ route('receivings.index') }}" class="btn btn-success">Back</a>
                                            <a href="{{ route('receiving.create') }}" class="btn btn-success">New</a>
                                            @if( $header->status != '3' && $header->status != '4')
                                                @can('receiving-delete')
                                                    <a href='javascript:;'
                                                        id='deleteButton'
                                                        class='btn btn-warning'
                                                        data-toggle='modal'
                                                        data-target='#smallModalCancel'
                                                        data-href='{{ route("receiving.destroy", ["id"=>$header->id]) }}'>
                                                        Cancel
                                                    </a>
                                                @endcan

                                                <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                                                @can('receiving-posting')
                                                    <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
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
                                    <td class="" style="width: 25%">
                                        <label>Article Code</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Qty PO</label>   
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Qty</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>UOM</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>Free Goods</label>
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
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                        {{-- @foreach ($detail as $key =>$item)
                            <div id="new_row{{ $key }}" class="tanda-baris" >
                                <table class="table-bordered" style="width: 98%;table-layout: fixed;">
                                    <tbody>
                                        <tr>
                                            <td class="isian disabled" style="width: 25%">
                                                <input type="text" class="form-control-plaintext text-hitam" id="article_id{{ $key }}" name="article_id[]" data-code="{{ $item->article_code }}" data-uom="{{ $item->uom_rec }}" value= "{{ $item->article_alternative_code  }} - {{ $item->article_desc }}" disabled>
                                            </td>
                                            <td class="isian" style="width: 5%">
                                                <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "qty_rec" name="qty_rec[]" value= "{{ $item->qty_rec  }}" maxlength="9">
                                            </td>
                                            <td class="isian" style="width: 5%">
                                                <select class="form-control" id="uom" name="uom[]">
                                                </select>
                                            </td>
                                            <td class="isian" style="width: 5%">
                                                <input type="text" class="form-control-plaintext numeral-mask-digit text-right" id = "qty_free" name="qty_free[]" maxlength="9" />
                                            </td>
                                            <td class="isian" style="width: 5%">
                                                <select class="form-control" id="uomFree" name="uomFree[]">
                                                </select>
                                            </td>
                                            <td class="isian disabled text-right" style="width: 5%">
                                                <span class="text-hitam" id="totalQty" name="totalQty[]"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endforeach --}}
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
                                <label for="totalQtyFree" class="col-sm-4 col-form-label titik-dua">Total Qty Free</label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQtyFree" disabled />
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
@include('receiving.addArticle')
@include('partials.delete-modal')
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

        let href;
        $(document).on('click', '#deleteButton', function(event) {
            event.preventDefault();
            href = $(this).data('href');
            $('#modalConfirmationCancel').attr("action", href);
        });

        let detail = {!!  $detail !!};
        for(let i=0;i<detail.length;i++){
            article = detail[i].article_code;
            articleCode = detail[i].article_alternative_code;
            articleDesc = detail[i].article_desc;
            qtyPo =  detail[i].qty;
            uomGroup =  detail[i].uom_group;
            uom =  detail[i].uomQty;
            qty =  detail[i].qty;
            uomQty =  detail[i].uom_rec;
            qtyFree =  detail[i].qty_free;
            uomFree =  detail[i].uom_free;
            price =  detail[i].price;
            add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,qty,uomQty,qtyFree,uomFree,price);
        }
            
    });

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
            let objQty= $('input[name="qty_rec[]"]');
            let objUom= $('select[name="uom[]"]');
            let objQtyFree= $('input[name="qty_free[]"]');
            let objUomFree= $('select[name="uomFree[]"]');
            
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
                    // let qty=objQty.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyUom=objUom.eq(i).val() || articleUom;
                    let qtyFree=objQtyFree.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyFreeUom=objUom.eq(i).val() || articleUom;
                    
                    articles.push({
                        "article_code":articleCode,
                        "qty":qty,
                        "uom":qtyUom,
                        "qty_free":qtyFree,
                        "uom_free":qtyFreeUom,
                        "price":price,
                    });
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let recNumber = $('#recNumber').val();
                let invNumber = $('#invNumber').val();
                let invDate = $('#invDate').val();
                let poNumber = $('#poNumber').val();
                let supp = $('#supplier').val();
                let recDate = $('#recDate').val();
                let note = $('#note').val();
            
                $.ajax({
                    type: "post",
                    url: "{{ route('receiving.update') }}",
                    data: {
                        recNumber:recNumber,
                        invNumber:invNumber,
                        invDate:invDate,
                        poNumber:poNumber,
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
                            // $('#recNumber').val(data.recNumber);
                            // $('#cmdSave').hide();
                            $('#deleteButton').hide();
                            $('#cmdPosting').show();
                            $('#recNumber').attr('disabled','disabled');
                            $('#poNumber').attr('disabled','disabled');
                            $('#supplier').attr('disabled','disabled');
                            
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
                    $('#poNumber').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');
                    
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
            
        
    });
    
    let cloneCount=1;
    function add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,qty,uomQty,qtyFree,uomFree,price) {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        $('#article_id'+ cloneCount).attr('data-code', article);
        $('#article_id'+ cloneCount).attr('data-uom', uom);
        $('#article_id'+ cloneCount).attr('data-price', price);
        $('#article_id'+ cloneCount).val(articleCode +" - " + articleDesc);
        $("#new_row"+ cloneCount).find('#qty_po').attr('id', 'qty_po'+ cloneCount);
        $('#qty_po'+ cloneCount).val("");
        $("#new_row"+ cloneCount).find('#qty_rec').attr('id', 'qty_rec'+ cloneCount);
        $('#qty_rec'+ cloneCount).val(qty);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        listUom('uom'+ cloneCount,uomGroup,uom,uomQty);
        $("#new_row"+ cloneCount).find('#qty_free').attr('id', 'qty_free'+ cloneCount);
        $('#qty_free'+ cloneCount).val(qtyFree);
        $("#new_row"+ cloneCount).find('#uomFree').attr('id', 'uomFree'+ cloneCount);
        listUom('uomFree'+ cloneCount,uomGroup,uom,uomFree);
        tombolPanah('qty_rec');
        tombolPanah('qty_free');
        mask_thousand_digit(3);
        hitungTotal();
        hitungGrandTotalLoad();
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

    function hitungTotal(){
        let objQtyRec= $('#article_row input[name="qty_rec[]"]');
        let objQtyFree= $('#article_row input[name="qty_free[]"]');
        let objTotalQty= $('#article_row span[name="totalQty[]"]');
        
        objQtyRec.keyup(function() {
            let indexnya= objQtyRec.index(this);
            let qtyRec = parseInt(objQtyRec.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0); 
            let qtyFree = parseInt(objQtyFree.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0); 
            let totalQty = qtyRec+qtyFree;
            objTotalQty.eq(indexnya).text(humanizeNumber(totalQty));
            hitungGrandTotal();
        });    

        objQtyFree.keyup(function() {
            let indexnya= objQtyRec.index(this);
            let qtyRec = parseInt(objQtyRec.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0); 
            let qtyFree = parseInt(objQtyFree.eq(indexnya).val().replace(/[^0-9]/gi, '') || 0); 
            let totalQty = qtyRec+qtyFree;
            objTotalQty.eq(indexnya).text(humanizeNumber(totalQty));
            hitungGrandTotal();
        });
            
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row input[name="article_id[]"]');
        let objQtyRec= $('#article_row input[name="qty_rec[]"]');
        let objQtyFree= $('#article_row input[name="qty_free[]"]');
        let totalQty= 0;
        let totalQtyFree= 0;

        var arr = objQtyRec.map(function (i) {
            let qty = parseInt(objQtyRec.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            let qtyFree = parseInt(objQtyFree.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            totalQty+= qty;
            totalQtyFree+= qtyFree;
        }).get();
        grandTotalQty=totalQty+totalQtyFree;
        
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalQtyFree").val(humanizeNumber(totalQtyFree));
        $("#grandTotalQty").val(humanizeNumber(grandTotalQty));
    }

    function hitungGrandTotalLoad(){
        let objArticle = $('#article_row input[name="article_id[]"]');
        let objQtyRec= $('#article_row input[name="qty_rec[]"]');
        let objQtyFree= $('#article_row input[name="qty_free[]"]');
        let objTotalQty= $('#article_row span[name="totalQty[]"]');
        
        let totalQty= 0;
        let totalQtyFree= 0;

        var arr = objQtyRec.map(function (i) {
            let qty = parseInt(objQtyRec.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            let qtyFree = parseInt(objQtyFree.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            totalQty+= qty;
            totalQtyFree+= qtyFree;
            objTotalQty.eq(i).text(humanizeNumber(qty+qtyFree));
        }).get();
        grandTotalQty=totalQty+totalQtyFree;
        
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#totalQtyFree").val(humanizeNumber(totalQtyFree));
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