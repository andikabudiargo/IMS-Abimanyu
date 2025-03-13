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
                            <input type="hidden" id="idRec" name="idRec" class="form-control" />
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-2 d-none">
                                    <label class="form-label" for="recType">Receive type</label>
                                    <select class="select2 form-control" id="recType" name="recType" required disabled>
                                        <option label="recPo">By PO</option>
                                        <option label="recSo">By SO</option>
                                        <option label="recFree">Free Input</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="recNumber">Receiving Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="recNumber" name="recNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="recDate">Receiving Date*</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                                </div>                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required>
                                        <option value=""></option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="poNumber">PO Number*</label>
                                    <select class="select2 form-control" id="poNumber" name="poNumber" required>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="doDate">DO Date*</label>
                                    <input type="text" id="doDate" name="doDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>                               
                                <div class="form-group col-md-3">
                                    <label for="doNumber">DO Number*</label>
                                    <input type="text" id="doNumber" name="doNumber" class="form-control disabled-el" required/>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="invDate">Invoice Date*</label>
                                    <input type="text" id="invDate" name="invDate" class="form-control" placeholder="DD-MM-YYYY" />
                                </div>                               
                                <div class="form-group col-md-3 d-none">
                                    <label for="invNumber">Invoice Number*</label>
                                    <input type="text" id="invNumber" name="invNumber" class="form-control disabled-el" />
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
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('receiving.headerColumn') 
                            <input type="text" id ="last_row_number" class="d-none" value="0">
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
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
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            {{-- <button class="btn btn-success" type="reset" id="cmdNew" name="cmdNew">New</button> --}}
                            {{-- <button class="btn btn-dark" type="reset" id="cmdPrint" name="cmdPrint">Print</button> --}}
                            {{-- <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button> --}}
                            {{-- @can('receiving-posting')
                                <button class="btn btn-dark" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                            @endcan --}}
                            <a href="{{ route('receivings.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            <button class="btn btn-dark" type="button" id="cmdPrint" name="cmdPrint">Print</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('receiving.addArticle')
@endsection
@section('styles')
@endsection
@section('scripts')
<script type="text/javascript">
    dariEdit = 'false';
    let lockedAt = "{{ $lockDate }}";
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $("#totalQtyFree").val(humanizeNumber(0));
        $("#grandTotalQty").val(humanizeNumber(0));
        $('#statusText').text('New');
        $('#recDate').val(currentDate);
        $('#cmdSave').show();
        // $('#cmdPosting').hide();
        $('#cmdPrint').hide();
    });

    invDate = $('#invDate');
    if (invDate.length) {
        invDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today"
        });
    }

    doDate = $('#doDate');
    if (doDate.length) {
        doDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today",
            minDate:lockedAt
        });
    }

    recDate = $('#recDate');
    if (recDate.length) {
        recDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today",
            minDate:lockedAt
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
            // ambil semua data article
            let objQty= $('input[name="qty_rec[]"]');
            let objUom= $('select[name="uom[]"]');
            let objQtyFree= $('input[name="qty_free[]"]');
            let objUomFree= $('select[name="uomFree[]"]');
            let objQtyPo= $('input[name="qty_po[]"]');
            
            let articles = []; 
            let flag=0; 
            let pesan="";

            $("#article_row input[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let articleCode = $this.data("code");
                    let articleUom = $this.data("uom");
                    let articlePrice = $this.data("price");
                    let prNumber = $this.data("prnumber");
                    let article=$this.val().split("|");
                    let plu=article[0];
                    let articleName=article[1];
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyUom=objUom.eq(i).val() || articleUom;
                    let qtyFree=objQtyFree.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyFreeUom=objUom.eq(i).val() || articleUom;
                    let qtyPo=objQtyPo.eq(i).val().replace(/,/gi, '') || 0;

                    if ( (parseFloat(qty) > parseFloat(qtyPo)) && (parseFloat(qty) != 0)  ){
                        pesan +=`Articles : ${article} QTY Rec > QTY PO <br>`; 
                        flag=1;
                    }

                    articles.push({
                        "article_code":articleCode,
                        "qty":qty,
                        "uom":qtyUom,
                        "qty_free":qtyFree,
                        "uom_free":qtyFreeUom,
                        "price":articlePrice,
                        "pr_number":prNumber,
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
                let invNumber = $('#invNumber').val()||0;
                let invDate = $('#invDate').val();
                let doNumber = $('#doNumber').val();
                let doDate = $('#doDate').val();
                let poNumber = $('#poNumber').val();
                let supp = $('#supplier').val();
                let recDate = $('#recDate').val();
                let note = $('#note').val();
            
                $.ajax({
                    type: "post",
                    url: "{{ route('receiving.store') }}",
                    data: {
                        invNumber:invNumber,
                        invDate:invDate,
                        doNumber:doNumber,
                        doDate:doDate,
                        poNumber:poNumber,
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
                            $('#cmdSave').removeAttr('disabled');
                            // $('#cmdPosting').hide();
                            // $('#cmdPrint').hide();

                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#statusText').text(data.statusRec);
                            $('#recNumber').val(data.recNumber);
                            $('#cmdSave').hide();
                            $('#cmdCancel').hide();
                            // $('#cmdPosting').show();
                            // $('#cmdPrint').show();
                            $('#recNumber').attr('disabled','disabled');
                            $('#cmdSave').attr('disabled','disabled');
                            $('#supplier').attr('disabled','disabled');
                            $('#poNumber').attr('disabled','disabled');
                            $('#invDate').attr('disabled','disabled');
                            $('#recDate').attr('disabled','disabled');
                            $('#invNumber').attr('disabled','disabled');

                            $('#statusText').val('NEW');
                            $('#idRec').val(data.idKu);

                            $('#cmdSave').hide();
                            $('#cmdPrint').show();

                            // objQty.attr('disabled','disabled');
                            // objUom.attr('disabled','disabled');
                            // objQtyFree.attr('disabled','disabled');
                            // objUomFree.attr('disabled','disabled');

                            // let id = data.idKu;
                            // let url = "{{ route('receiving.print', ['id'=>':id']) }}";
                            // url = url.replace('%3Aid', id);
                            // window.open(url, '_blank');
                            // reloadPage();
                            
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            }else{
                $('#cmdSave').removeAttr('disabled');
                // $('#cmdPosting').hide();
                $('#cmdPrint').hide();
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    });
  
    $('#supplier').change(function(){
        let value= $(this).val();
        searchPo('poNumber',value);
    });

    let cloneCount=0;
    function add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,price,qtyRec,prNumber) {
        prNumber= prNumber == null ? '':prNumber
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        $('#article_id'+ cloneCount).attr('data-code', article);
        $('#article_id'+ cloneCount).attr('data-uom', uom);
        $('#article_id'+ cloneCount).attr('data-price', price);
        $('#article_id'+ cloneCount).attr('data-prnumber', prNumber);
        $('#article_id'+ cloneCount).val(articleCode +" - " + articleDesc);
        $("#new_row"+ cloneCount).find('#qty_po').attr('id', 'qty_po'+ cloneCount);
        $('#qty_po'+ cloneCount).val(qtyPo*1);
        $("#new_row"+ cloneCount).find('#qty_rec').attr('id', 'qty_rec'+ cloneCount);
        $('#qty_rec'+ cloneCount).val(qtyRec);
        $('#qty_rec'+ cloneCount).attr('data-uom-group', uomGroup);
        $("#new_row"+ cloneCount).find('#qty_free').attr('id', 'qty_free'+ cloneCount);
        $('#qty_free'+ cloneCount).val(qtyRec);
        $('#qty_free'+ cloneCount).attr('data-uom-group', uomGroup);
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        listUom('uom'+ cloneCount,'uomFree'+ cloneCount,uomGroup,uom);
        $("#new_row"+ cloneCount).find('#uomFree').attr('id', 'uomFree'+ cloneCount);
        qtyRec === 0 ? $('#qty_rec'+ cloneCount).attr('disabled','disabled') : '';
        qtyRec === 0 ? $('#qty_free'+ cloneCount).attr('disabled','disabled') : '';
        // listUom('uomFree'+ cloneCount,uomGroup,uom);
        tombolPanah('qty_rec');
        tombolPanah('qty_free');
        mask_thousand_digit(2);
        hitungTotal();
    }

    $('#poNumber').change(function(){
        let value= $(this).val();
        searchPoDet(value,'false');
    })   
    
    function hitungTotal(){
        let objQtyRec= $('#article_row input[name="qty_rec[]"]');
        let objQtyFree= $('#article_row input[name="qty_free[]"]');
        let objTotalQty= $('#article_row span[name="totalQty[]"]');
        let objQtyPo= $('#article_row input[name="qty_po[]"]');
        
        objQtyRec.keyup(function() {
            let indexnya= objQtyRec.index(this);
            let qtyRec = parseFloat(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let qtyFree = parseFloat(objQtyFree.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let totalQty = qtyRec+qtyFree;
            let qtyPo = parseFloat(objQtyPo.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let uomGroup = objQtyRec.eq(indexnya).data('uom-group');
            if ( qtyRec > qtyPo ){
                objQtyRec.eq(indexnya).delay(3000).css("background-color","rgba(255,0,0, 0.5)");
            }else{
                objQtyRec.eq(indexnya).delay(3000).css("background-color","");
            }
            objTotalQty.eq(indexnya).text(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit})); 
            hitungGrandTotal();
        });    

        objQtyFree.keyup(function() {
            let indexnya= objQtyRec.index(this);
            let qtyRec = parseFloat(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let qtyFree = parseFloat(objQtyFree.eq(indexnya).val().replace(/,/gi, '') || 0); 
            let totalQty = qtyRec+qtyFree;
            let uomGroup = objQtyFree.eq(indexnya).data('uom-group');
            objTotalQty.eq(indexnya).text(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
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
            let qty = parseFloat(objQtyRec.eq(i).val().replace(/,/gi, '')) || 0;
            let qtyFree = parseFloat(objQtyFree.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
            totalQtyFree+= qtyFree;
        }).get();
        grandTotalQty=totalQty+totalQtyFree;
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
        $("#totalQtyFree").val(totalQtyFree.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
        $("#grandTotalQty").val(grandTotalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
    }

    $("#cmdPrint").click(function(){
        /* Posting langsung print*/
        $("#cmdPrint").prop('disabled',true);
        $('#cmdSave').hide();
        $('#cmdPrint').hide();
        let objQty= $('input[name="qty_rec[]"]');
        let objUom= $('select[name="uom[]"]');
        let objQtyFree= $('input[name="qty_free[]"]');
        let objUomFree= $('select[name="uomFree[]"]');
        let objQtyPo= $('input[name="qty_po[]"]');    
        let recNumber = $('#recNumber').val();   
        let idRec = $('#idRec').val();  
        let dariNew = 'true';     
        $.ajax({
            type: "post",
            url: "{{ route('receiving.posting') }}",
            data: {
                recNumber:recNumber,
                id:idRec,
                dariNew:dariNew
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#recNumber').attr('disabled','disabled');
                    $('#cmdSave').show();
                    $('#cmdPrint').hide();
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#cmdSave').hide();
                    // $('#deleteButton').hide();
                    $('#statusText').text('POSTED');
                    $('#cmdPrint').hide();
                    $('#recNumber').attr('disabled','disabled');
                    $('#soNumber').attr('disabled','disabled');
                    $('#customer').attr('disabled','disabled');
                    $('#dnDate').attr('disabled','disabled');

                    $('#cmdSave').hide();
                    $('#cmdPrint').hide();

                    objQty.attr('disabled','disabled');
                    objUom.attr('disabled','disabled');
                    objQtyFree.attr('disabled','disabled');
                    objUomFree.attr('disabled','disabled');

                    let id = data.idKu;
                    let url = "{{ route('receiving.print', ['id'=>':id']) }}";
                    url = url.replace('%3Aid', id);
                    window.open(url, '_blank');
                    reloadPage();
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection