@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusRec }}</span></h4>
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
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="recNumber">Receiving Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="recNumber" name="recNumber" class="form-control text-hitam disabled-el" value="{{ $header->rec_number }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="recDate">Receiving Date*</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->rec_date }}" required disabled/>
                                </div>                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required disabled>
                                        <option value=""></option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->supplier_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="poNumber">PO Number*</label>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control text-hitam disabled-el" value="{{ $header->po_number }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="doDate">DO Date*</label>
                                    <input type="text" id="doDate" name="doDate" class="form-control" value="{{ $header->do_date }}" placeholder="DD-MM-YYYY" required />
                                </div>                               
                                <div class="form-group col-md-3">
                                    <label for="doNumber">DO Number*</label>
                                    <input type="text" id="doNumber" name="doNumber" class="form-control disabled-el" value="{{ $header->do_number }}" required/>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="invDate">Invoice Date*</label>
                                    <input type="text" id="invDate" name="invDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->inv_date }}" />
                                </div> 
                                <div class="form-group col-md-3 d-none">
                                    <label for="invNumber">Invoice Number</label>
                                    <input type="text" id="invNumber" name="invNumber" class="form-control disabled-el" value="{{ $header->inv_number }}" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }} </textarea>
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
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('receiving.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin">
                                <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
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
                            <div class="form-row">
                                <div class="col-12">
                                    <a href="{{ route('receivings.index') }}" class="btn btn-light">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusRec =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusRec =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                        @endif
                                    @endif
                                    
                                    {{-- @if( $header->status != '3' && $header->status != '4')
                                        @can('receiving-delete')
                                            <a href='javascript:;'
                                                id='deleteButton'
                                                class='btn btn-warning'
                                                data-toggle='modal'
                                                data-target='#smallModalCancel'
                                                data-href='{{ route("receiving.destroy", ["id"=>Crypt::encryptString($header->id)]) }}'>
                                                Cancel
                                            </a>
                                        @endcan
                                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                                        @can('receiving-posting')
                                            <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                                        @endcan
                                    @endif --}}
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
@include('receiving.addArticle')
@endsection
@section('styles')
@endsection
@section('scripts')
<script type="text/javascript">
    const approveBtn = $('#cmdApprove');
    $(document).ready(function(){          
        validateFormToast("frmAdd");
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
            qtyPo =  detail[i].qty_po*1;
            uomGroup =  detail[i].uom_group;
            uom =  detail[i].uomQty;
            qty =  detail[i].qty*1;
            uomQty =  detail[i].uom_rec;
            qtyFree =  detail[i].qty_free;
            uomFree =  detail[i].uom_free;
            price =  detail[i].price;
            uom_group = detail[i].uom_group;
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

    approveBtn.click(function(){
        let recNumber = $('#recNumber').val();
        approve(recNumber,'cmdApprove');
    });

    $("#cmdNew").click(function(){
        reloadPage();
    });

    $("#cmdUpdate").click(function(){
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
                    // let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyUom=objUom.eq(i).val() || articleUom;
                    let qtyFree=objQtyFree.eq(i).val().replace(/,/gi, '') || 0;
                    let qtyFreeUom=objUom.eq(i).val() || articleUom;
                    
                    if ( (qty > qtyPo) && (qty != 0)  ){
                        pesan +=`Articles : ${article} QTY Rec > QTY PO <br>`; 
                        flag=1;
                    }

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

            if ( $("#grandTotalQty").val() == 0 ){
                pesan +="Total Qty cannot be 0 <br>"; 
                flag=1;
            }

            if (flag==0){
                let recNumber = $('#recNumber').val();
                let doNumber = $('#doNumber').val();
                let doDate = $('#doDate').val();
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
                        doNumber:doNumber,
                        doDate:doDate,
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
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#recNumber').attr('disabled','disabled');
                            $('#cmdSave').show();
                            $('#cmdPosting').hide();
                        }else{
                            show_msg(data.title, data.message, data.alert);
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

    // $("#cmdPosting").click(function(){
    //     let objQty= $('input[name="qty_rec[]"]');
    //     let objUom= $('select[name="uom[]"]');
    //     let objQtyFree= $('input[name="qty_free[]"]');
    //     let objUomFree= $('select[name="uomFree[]"]');
        
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
    //                 for(let i = 0; i < data.message.length; i++) {
    //                     show_msg(data.title, data.message[i], data.alert);
    //                 }
    //                 $('#recNumber').attr('disabled','disabled');
    //                 $('#cmdSave').show();
    //                 $('#cmdPosting').hide();

    //             }else{
    //                 show_msg(data.title, data.message, data.alert);
    //                 $('#statusText').text(data.statusRec);
    //                 $('#cmdSave').hide();
    //                 $('#deleteButton').hide();
    //                 $('#cmdPosting').hide();
    //                 $('#recNumber').attr('disabled','disabled');
    //                 $('#poNumber').attr('disabled','disabled');
    //                 $('#supplier').attr('disabled','disabled');
    //                 $('#invDate').attr('disabled','disabled');
    //                 $('#recDate').attr('disabled','disabled');
    //                 $('#invNumber').attr('disabled','disabled');
    //                 objQty.attr('disabled','disabled');
    //                 objUom.attr('disabled','disabled');
    //                 objQtyFree.attr('disabled','disabled');
    //                 objUomFree.attr('disabled','disabled');                    
    //             }
    //         },
    //         error: function(error) {
    //             console.log(error);
    //         }
    //     });
            
        
    // });
    
    let cloneCount=0;
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
        $('#qty_po'+ cloneCount).val(qtyPo);
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
        hitungTotal();
        hitungGrandTotalLoad();
        mask_thousand_digit(numberOfDecimalDigit);

        // if ( uomGroup === 'PIECE' ){
        //     $('#qty_rec'+ cloneCount).removeClass("numeral-mask-digit");
        //     $('#qty_rec'+ cloneCount).addClass("numeral-mask-satuan");
        //     $('#qty_free'+ cloneCount).removeClass("numeral-mask-digit");
        //     $('#qty_free'+ cloneCount).addClass("numeral-mask-satuan");
        //     mask_thousand_satuan();
        // }else{
        //     $('#qty_rec'+ cloneCount).removeClass("numeral-mask-satuan");
        //     $('#qty_rec'+ cloneCount).addClass("numeral-mask-digit");
        //     $('#qty_free'+ cloneCount).removeClass("numeral-mask-satuan");
        //     $('#qty_free'+ cloneCount).addClass("numeral-mask-digit");
        //     mask_thousand_digit(numberOfDecimalDigit);
        // }

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

    function hitungGrandTotalLoad(){
        let objArticle = $('#article_row input[name="article_id[]"]');
        let objQtyRec= $('#article_row input[name="qty_rec[]"]');
        let objQtyFree= $('#article_row input[name="qty_free[]"]');
        let objTotalQty= $('#article_row span[name="totalQty[]"]');
        
        let totalQty= 0;
        let totalQtyFree= 0;

        var arr = objQtyRec.map(function (i) {
            let qty = parseInt(objQtyRec.eq(i).val().replace(/,/gi, '')) || 0;
            let qtyFree = parseInt(objQtyFree.eq(i).val().replace(/,/gi, '')) || 0;
            totalQty+= qty;
            totalQtyFree+= qtyFree;
            objTotalQty.eq(i).text(humanizeNumber(qty+qtyFree));
        }).get();
        grandTotalQty=totalQty+totalQtyFree;
        
        $("#totalRow").val(objArticle.length);
        $("#totalQTY").val(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
        $("#totalQtyFree").val(totalQtyFree.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
        $("#grandTotalQty").val(grandTotalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
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