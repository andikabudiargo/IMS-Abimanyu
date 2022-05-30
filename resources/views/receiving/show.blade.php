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
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="recNumber">Receiving Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="recNumber" name="recNumber" class="form-control text-hitam disabled-el" value="{{ $header->rec_number }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="recDate">Receiving Date*</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control text-hitam" placeholder="DD-MM-YYYY" value="{{ $header->rec_date }}" disabled />
                                </div>                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control text-hitam" id="supplier" name="supplier" disabled>
                                        <option value="">All</option>
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
                                <div class="form-group col-md-3 d-none">
                                    <label for="invNumber">Invoice Number</label>
                                    <input type="text" id="invNumber" name="invNumber" class="form-control text-hitam disabled-el" value="{{ $header->inv_number }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control text-hitam" rows="1" disabled>{{ $header->note }} </textarea>
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
                    @include('receiving.headerColumn')
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                        <input type="text" id ="last_row_number" class="d-none" value="">
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
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            <div class="form-row">
                                <div class="col-12">
                                    <a href="{{ route('receivings.index') }}" class="btn btn-warning">Back</a>
                                    {{-- <a href="{{ route('receiving.create') }}" class="btn btn-success">New</a> --}}
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
@endsection
@section('styles')
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){           
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
            add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,qty,uomQty,qtyFree,uomFree);
        }
            
    });

    recDate = $('#recDate');
    if (recDate.length) {
        recDate.flatpickr({
            dateFormat: "d-m-Y",
        });
    }

    let cloneCount=1;
    function add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,qty,uomQty,qtyFree,uomFree) {
        $("#article_row").append($("#new_row").clone().html());
        cloneCount++;
        $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
        $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
        $('#article_id'+ cloneCount).attr('data-code', article);
        $('#article_id'+ cloneCount).attr('data-uom', uom);
        $('#article_id'+ cloneCount).val(articleCode +" - " + articleDesc);
        $("#new_row"+ cloneCount).find('#qty_po').attr('id', 'qty_po'+ cloneCount);
        $('#qty_po'+ cloneCount).val("");
        $("#new_row"+ cloneCount).find('#qty_rec').attr('id', 'qty_rec'+ cloneCount);
        $('#qty_rec'+ cloneCount).val(qty);
        $('#qty_rec'+ cloneCount).attr('disabled','disabled');
        $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
        listUom('uom'+ cloneCount,uomGroup,uom,uomQty);
        $('#uom'+ cloneCount).attr('disabled','disabled');
        $("#new_row"+ cloneCount).find('#qty_free').attr('id', 'qty_free'+ cloneCount);
        $('#qty_free'+ cloneCount).val(qtyFree);
        $('#qty_free'+ cloneCount).attr('disabled','disabled');
        $("#new_row"+ cloneCount).find('#uomFree').attr('id', 'uomFree'+ cloneCount);
        listUom('uomFree'+ cloneCount,uomGroup,uom,uomFree);
        $('#uomFree'+ cloneCount).attr('disabled','disabled');
        $("#new_row"+ cloneCount).find('#totalQty').attr('id', 'totalQty'+ cloneCount);
        $('#totalQty'+ cloneCount).text(parseFloat(qty)+parseFloat(qtyFree));
        mask_thousand_digit(numberOfDecimalDigit);
        hitungTotal();
        hitungGrandTotalLoad();

        if ( uomGroup === 'PIECE' ){
            $('#qty_rec'+ cloneCount).removeClass("numeral-mask-digit");
            $('#qty_rec'+ cloneCount).addClass("numeral-mask-satuan");
            $('#qty_free'+ cloneCount).removeClass("numeral-mask-digit");
            $('#qty_free'+ cloneCount).addClass("numeral-mask-satuan");
            mask_thousand_satuan();
        }else{
            $('#qty_rec'+ cloneCount).removeClass("numeral-mask-satuan");
            $('#qty_rec'+ cloneCount).addClass("numeral-mask-digit");
            $('#qty_free'+ cloneCount).removeClass("numeral-mask-satuan");
            $('#qty_free'+ cloneCount).addClass("numeral-mask-digit");
            mask_thousand_digit(numberOfDecimalDigit);
        }
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
            let qty = parseInt(objQtyRec.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
            let qtyFree = parseInt(objQtyFree.eq(i).val().replace(/[^0-9]/gi, '')) || 0;
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
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection