@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
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
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="recNumber">Receiving Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="recNumber" name="recNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="recDate">Receiving Date*</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <select class="select2 form-control" id="customer" name="customer" required>
                                        <option value="">All</option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
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
                            <div class="form-row">
                                <div class="form-group col-md-8">
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
                    @include('receivingRm.headerColumn')
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
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            {{-- <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Clear</button> --}}
                            <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            @can('receiving-posting')
                                <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                            @endcan
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
    const currentDate = "{{ $currentDateValue }}";   
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
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

            if ( $("#grandTotalQty").val() == 0 ){
                pesan +="Total Qty cannot be 0 <br>"; 
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
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#recNumber').attr('disabled','disabled');
                            $('#cmdSave').show();
                            $('#cmdPosting').hide();

                        }else{
                            show_msg(data.title, data.message, data.alert);
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
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#recNumber').attr('disabled','disabled');
                    $('#cmdSave').show();
                    $('#cmdPosting').hide();

                }else{
                    show_msg(data.title, data.message, data.alert);
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

    let cloneCount=0;
    function add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,price,qtyRec) {
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
        $("#new_row"+ cloneCount).find('#qty_rec').attr('id', 'qty_rec'+ cloneCount);
        $('#qty_rec'+ cloneCount).val(qty);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        qtyRec === 0 ? $('#qty_rec'+ cloneCount).attr('disabled','disabled') : '';
        qtyRec === 0 ? $('#qty_free'+ cloneCount).attr('disabled','disabled') : '';
        listUom('uom'+ cloneCount,uomGroup,uom);
        tombolPanah('qty_rec');
        mask_thousand_digit(numberOfDecimalDigit);
        hitungTotal();
        uomChange();

        if ( uomGroup === 'PIECE' ){
            $('#qty_rec'+ cloneCount).removeClass("numeral-mask-digit");
            $('#qty_rec'+ cloneCount).addClass("numeral-mask-satuan");
            $('#qty_so'+ cloneCount).removeClass("numeral-mask-digit");
            $('#qty_so'+ cloneCount).addClass("numeral-mask-satuan");
            mask_thousand_satuan();
        }else{
            $('#qty_rec'+ cloneCount).removeClass("numeral-mask-satuan");
            $('#qty_rec'+ cloneCount).addClass("numeral-mask-digit");
            $('#qty_so'+ cloneCount).removeClass("numeral-mask-satuan");
            $('#qty_so'+ cloneCount).addClass("numeral-mask-digit");
            mask_thousand_digit(numberOfDecimalDigit);
        }
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
                        qtySo=result[i].qty_order;
                        qty=qtySo <= 0 ? 0 :'';
                        uomGroup=result[i].uom_group;
                        uom=result[i].uom;
                        price=result[i].price;
                        add_new_row(article,articleCode,articleDesc,qtySo,uomGroup,uom,price,qty);
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
        url:"{{ route('receiving.list.uom') }}",
        method:"GET",
        data:{
            value:value,
        },
        success:function(result){
            $('#'+obj).html(result);
            $('#'+obj).select2();
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
    }

    function hitungGrandTotal(){
        let objArticle = $('#article_row input[name="article_id[]"]');
        let objQtyRec= $('#article_row input[name="qty_rec[]"]');
        let totalQty= 0;

        var arr = objQtyRec.map(function (i) {
            let qty = parseInt(objQtyRec.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
        }).get();
        grandTotalQty=totalQty;
        
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(humanizeNumber(totalQty));
        $("#grandTotalQty").val(humanizeNumber(grandTotalQty));
    }

    function uomChange(){
        // split article with delimiter
        let objUom= $('#article_row select[name="uom[]"]');
        let objQty= $('#article_row input[name="qty_rec[]"]');

        objUom.change(function(e){   
            let objIndex = objUom.index(this);
            let uomGroup = objUom.eq(objIndex).find(":selected").data("uom-group");

            if ( uomGroup === 'PIECE' ){
                objQty.eq(objIndex).removeClass("numeral-mask-digit");
                objQty.eq(objIndex).addClass("numeral-mask-satuan");
                mask_thousand_satuan();
            }else{
                objQty.eq(objIndex).removeClass("numeral-mask-satuan");
                objQty.eq(objIndex).addClass("numeral-mask-digit");
                mask_thousand_digit(numberOfDecimalDigit);
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