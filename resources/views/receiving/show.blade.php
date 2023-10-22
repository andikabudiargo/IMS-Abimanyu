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
                        <ul class="nav nav-tabs" role="tablist">
                            @foreach( $headers as $key =>$header )
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active':'' }}" 
                                    id="po-tab" 
                                    data-toggle="tab" 
                                    href="#rev{{ $key }}" 
                                    aria-controls="revisi{{ $key }}" 
                                    role="tab" 
                                    aria-selected="false" 
                                    data-ajax-detail="true" 
                                    data-dn-number="{{ $header->rec_number }}">{{ $key == 0 ? 'Main':'Revision '.($key-1) }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach( $headers as $key =>$header2 )
                                <div class="tab-pane {{ $key == 0 ? 'active':'' }}" id="rev{{ $key }}" aria-labelledby="revison{{ $key }}-tab" role="tabpanel">
                                    <form id="frmAdd" name="frmAdd" autocomplete="off">
                                        @csrf
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="recNumber">Receiving Number</label> <small class="text-muted"> automatic</small>
                                                <input type="text" id="recNumber" name="recNumber" class="form-control text-hitam disabled-el" value="{{ $header2->rec_number }}"  disabled />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="recDate">Receiving Date*</label>
                                                <input type="text" id="recDate" name="recDate" class="form-control text-hitam" placeholder="DD-MM-YYYY" value="{{ $header2->rec_date }}" disabled />
                                            </div>                               
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="supplier">Supplier*</label>
                                                <select class="select2 form-control text-hitam" id="supplier" name="supplier" disabled>
                                                    <option value="">All</option>
                                                    @foreach($supps as $val)
                                                        <option value="{{$val->kode}}" {{$val->kode == $header2->supplier_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label class="form-label" for="poNumber">PO Number*</label>
                                                <input type="text" id="poNumber" name="poNumber" class="form-control text-hitam disabled-el" value="{{ $header2->po_number }}"  disabled />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="doDate">DO Date*</label>
                                                <input type="text" id="doDate" name="doDate" class="form-control" value="{{ $header2->do_date }}" placeholder="DD-MM-YYYY" required />
                                            </div>                               
                                            <div class="form-group col-md-3">
                                                <label for="doNumber">DO Number*</label>
                                                <input type="text" id="doNumber" name="doNumber" class="form-control disabled-el" value="{{ $header2->do_number }}" required/>
                                            </div>
                                            <div class="form-group col-md-3 d-none">
                                                <label for="invNumber">Invoice Number</label>
                                                <input type="text" id="invNumber" name="invNumber" class="form-control text-hitam disabled-el" value="{{ $header2->inv_number }}" disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-8">
                                                <label class="form-label" for="note">Notes</label>
                                                <textarea type="text" id="note" name="note" class="form-control text-hitam" rows="1" disabled>{{ $header2->note }} </textarea>
                                            </div>
                                        </div>
                                        @if($key!=0)
                                        <div class="form-row">
                                            <div class="form-group col-md-8">
                                                <label class="form-label" for="note">Revision reason | by: {{ $header2->revised_by }} | at: {{ date('d-m-Y h:m:s',strtotime($header2->revised_at))   }} </label>
                                                <textarea type="text" id="rReason" name="rReason" class="form-control" rows="1" disabled >{{ $header2->reason }}</textarea>
                                            </div>
                                        </div>
                                        @endif
                                    </form>
                                    <hr>               
                                    <div class="table-responsive main-table">
                                        <table class="table table-bordered w-100" id="tableDetail" >
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Article Code</th>
                                                    <th class="text-right">QTY</th>
                                                    <th class="text-left">UOM</th>
                                                    <th class="text-right">QTY Free</th>
                                                    <th class="text-left">UOM</th>
                                                    <th class="text-right">Qty Total</th>
                                                    @if ($key !=0)
                                                        @foreach( $headers as $key1 => $oki )
                                                            @if ($key1 < $key and $key1!= 0 )
                                                                <th class="text-center">R-{{ $key1 }}</th>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        @foreach( $headers as $key1 => $oki )
                                                            @if ($key1 > $key and $key1!= 0 )
                                                                <th class="text-center">R-{{ $key1-1 }}</th>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach( $details as $item )
                                                @if($item->rec_number === $header2->rec_number )
                                                    <tr>
                                                        <td class="text-right"></td>
                                                        <td >{{ $item->article }}</td>
                                                        <td class="text-right">{{ number_format($item->qty) }} </td>
                                                        <td>{{ $item->uom_rec }}</td>
                                                        <td class="text-right">{{ number_format($item->qty_free) }} </td>
                                                        <td>{{ $item->uom_free }}</td>
                                                        <td class="text-right">{{ number_format($item->qty+$item->qty_free) }} </td>
                                                        @php
                                                            {{ $histori = explode("->",$item->notes);}}
                                                        @endphp 
                                                        @if ($key !=0)
                                                            @foreach( $headers as $key1 => $oki )
                                                                @if ($key1 < $key and $key1!= 0)
                                                                    @if( $key1 < count($histori) )
                                                                        <td class="text-right">{{ number_format(intval($histori[$key1])) }}</td>
                                                                    @else
                                                                        <td class="text-right"></td>
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            @foreach( $headers as $key1 => $oki )
                                                                @if ($key1 > $key and $key1!= 0)
                                                                    @if( $key1 < count($histori) )
                                                                        <td class="text-right">{{ number_format(intval($histori[$key1])) }}</td>
                                                                    @else
                                                                        <td class="text-right"></td>
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-end mt-75">
                                        <div class="col-md-4">
                                            <span>ROW : {{ $header2->sum_row }}</span> <br>
                                            <span>QTY : {{ number_format($header2->sum_qty) }}</span>
                                        </div>
                                        <div class="col-md-4">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mt-75">
                                        <a href="{{ route('receivings.index') }}" class="btn btn-light">Back</a>
                                        <a href="{{ route('receiving.print',['id'=>Crypt::encryptString($header2->id)]) }}" target="_blank" class="btn btn-success">Print</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <hr>
                        <div class="form-row card-statistics">
                            @foreach($approvalHistory as $val)
                                @if($val->status == true)
                                    <div class="statistics-body">
                                        <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                            <div class="media">
                                                <div class="avatar bg-light-success mr-2">
                                                    <div class="avatar-content">
                                                        <i data-feather="check" class="avatar-icon"></i>
                                                    </div>
                                                </div>
                                                <div class="media-body my-auto">
                                                    <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}</h4>
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
    </div>
</section>
{{-- @include('receiving.addArticle') --}}
@endsection
@section('styles')
<style>

    textarea {
        resize: none;
    }

    .main-table table {
        counter-reset: rowNumber;
    }

    .main-table table tr > td:first-child{
        counter-increment: rowNumber;
    }

    .main-table table tr td:first-child::before {
        content: counter(rowNumber);
        min-width: 1em;
        margin-right: 0.5em;
    }

    .text-merah{
        color:red;
    }

    #tableDetail th, #tableDetail td {
        padding: 0.4rem 0.6rem;
        vertical-align: middle;
    }
    

</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){           
        // 
        // for(let i=0;i<detail.length;i++){
        //     article = detail[i].article_code;
        //     articleCode = detail[i].article_alternative_code;
        //     articleDesc = detail[i].article_desc;
        //     qtyPo =  detail[i].qty;
        //     uomGroup =  detail[i].uom_group;
        //     uom =  detail[i].uomQty;
        //     qty =  detail[i].qty;
        //     uomQty =  detail[i].uom_rec;
        //     qtyFree =  detail[i].qty_free;
        //     uomFree =  detail[i].uom_free;
        //     add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,qty,uomQty,qtyFree,uomFree);
        // }            
    });

    // recDate = $('#recDate');
    // if (recDate.length) {
    //     recDate.flatpickr({
    //         dateFormat: "d-m-Y",
    //     });
    // }

    // let cloneCount=1;
    // function add_new_row(article,articleCode,articleDesc,qtyPo,uomGroup,uom,qty,uomQty,qtyFree,uomFree) {
    //     $("#article_row").append($("#new_row").clone().html());
    //     cloneCount++;
    //     $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
    //     $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
    //     $('#article_id'+ cloneCount).attr('data-code', article);
    //     $('#article_id'+ cloneCount).attr('data-uom', uom);
    //     $('#article_id'+ cloneCount).val(articleCode +" - " + articleDesc);
    //     $("#new_row"+ cloneCount).find('#qty_po').attr('id', 'qty_po'+ cloneCount);
    //     $('#qty_po'+ cloneCount).val("");
    //     $("#new_row"+ cloneCount).find('#qty_rec').attr('id', 'qty_rec'+ cloneCount);
    //     $('#qty_rec'+ cloneCount).val(qty);
    //     $('#qty_rec'+ cloneCount).attr('disabled','disabled');
    //     $("#new_row"+ cloneCount).find('#uom').attr('id', 'uom'+ cloneCount);
    //     listUom('uom'+ cloneCount,uomGroup,uom,uomQty);
    //     $('#uom'+ cloneCount).attr('disabled','disabled');
    //     $("#new_row"+ cloneCount).find('#qty_free').attr('id', 'qty_free'+ cloneCount);
    //     $('#qty_free'+ cloneCount).val(qtyFree);
    //     $('#qty_free'+ cloneCount).attr('disabled','disabled');
    //     $("#new_row"+ cloneCount).find('#uomFree').attr('id', 'uomFree'+ cloneCount);
    //     listUom('uomFree'+ cloneCount,uomGroup,uom,uomFree);
    //     $('#uomFree'+ cloneCount).attr('disabled','disabled');
    //     $("#new_row"+ cloneCount).find('#totalQty').attr('id', 'totalQty'+ cloneCount);
    //     $('#totalQty'+ cloneCount).text(parseFloat(qty)+parseFloat(qtyFree));
    //     // mask_thousand_digit(numberOfDecimalDigit);
    //     hitungTotal();
    //     hitungGrandTotalLoad();

    //     if (qty == Math.floor(qty)){
    //         $('#qty_rec'+ cloneCount).removeClass("numeral-mask-digit");
    //         $('#qty_rec'+ cloneCount).addClass("numeral-mask-satuan");
    //         $('#qty_free'+ cloneCount).removeClass("numeral-mask-digit");
    //         $('#qty_free'+ cloneCount).addClass("numeral-mask-satuan");
    //         mask_thousand_satuan();
    //     }else{
    //         $('#qty_rec'+ cloneCount).removeClass("numeral-mask-satuan");
    //         $('#qty_rec'+ cloneCount).addClass("numeral-mask-digit");
    //         $('#qty_free'+ cloneCount).removeClass("numeral-mask-satuan");
    //         $('#qty_free'+ cloneCount).addClass("numeral-mask-digit");
    //         mask_thousand_digit(numberOfDecimalDigit);    
    //     }
       
    //     // if ( uomGroup === 'PIECE' ){
    //     //     $('#qty_rec'+ cloneCount).removeClass("numeral-mask-digit");
    //     //     $('#qty_rec'+ cloneCount).addClass("numeral-mask-satuan");
    //     //     $('#qty_free'+ cloneCount).removeClass("numeral-mask-digit");
    //     //     $('#qty_free'+ cloneCount).addClass("numeral-mask-satuan");
    //     //     mask_thousand_satuan();
    //     // }else{
    //     //     $('#qty_rec'+ cloneCount).removeClass("numeral-mask-satuan");
    //     //     $('#qty_rec'+ cloneCount).addClass("numeral-mask-digit");
    //     //     $('#qty_free'+ cloneCount).removeClass("numeral-mask-satuan");
    //     //     $('#qty_free'+ cloneCount).addClass("numeral-mask-digit");
    //     //     mask_thousand_digit(numberOfDecimalDigit);
    //     // }
    // }

    // function listUom(obj,value,uom,uomSelect) {
    //   $.ajax({
    //     url:"{{ route('receiving.list.uom') }}",
    //     method:"GET",
    //     data:{
    //         value:value,
    //     },
    //     success:function(result){
    //         $('#'+obj).html(result);
    //         $('#'+obj).val(uomSelect).trigger('change');            
    //     },
    //     error: function (response) {
    //         Swal.fire("Warning","Get list UOM failed","warning");
    //     }
    //   })
    // }

    // function hitungTotal(){
    //     let objQtyRec= $('#article_row input[name="qty_rec[]"]');
    //     let objQtyFree= $('#article_row input[name="qty_free[]"]');
    //     let objTotalQty= $('#article_row span[name="totalQty[]"]');
    //     let objQtyPo= $('#article_row input[name="qty_po[]"]');
        
    //     objQtyRec.keyup(function() {
    //         let indexnya= objQtyRec.index(this);
    //         let qtyRec = parseFloat(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
    //         let qtyFree = parseFloat(objQtyFree.eq(indexnya).val().replace(/,/gi, '') || 0); 
    //         let totalQty = qtyRec+qtyFree;
    //         let qtyPo = parseFloat(objQtyPo.eq(indexnya).val().replace(/,/gi, '') || 0); 
    //         let uomGroup = objQtyRec.eq(indexnya).data('uom-group');
    //         if ( qtyRec > qtyPo ){
    //             objQtyRec.eq(indexnya).delay(3000).css("background-color","rgba(255,0,0, 0.5)");
    //         }else{
    //             objQtyRec.eq(indexnya).delay(3000).css("background-color","");
    //         }
    //         objTotalQty.eq(indexnya).text(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit})); 
    //         hitungGrandTotal();
    //     });    

    //     objQtyFree.keyup(function() {
    //         let indexnya= objQtyRec.index(this);
    //         let qtyRec = parseFloat(objQtyRec.eq(indexnya).val().replace(/,/gi, '') || 0); 
    //         let qtyFree = parseFloat(objQtyFree.eq(indexnya).val().replace(/,/gi, '') || 0); 
    //         let totalQty = qtyRec+qtyFree;
    //         let uomGroup = objQtyFree.eq(indexnya).data('uom-group');
    //         objTotalQty.eq(indexnya).text(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
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
    //         let qty = parseFloat(objQtyRec.eq(i).val().replace(/,/gi, '')) || 0;
    //         let qtyFree = parseFloat(objQtyFree.eq(i).val().replace(/,/gi, '')) || 0;
    //         totalQty+= qty;
    //         totalQtyFree+= qtyFree;
    //     }).get();
    //     grandTotalQty=totalQty+totalQtyFree;
    //     $("#totalRow").val(objArticle.length);
    //     $("#totalQTY").val(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
    //     $("#totalQtyFree").val(totalQtyFree.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
    //     $("#grandTotalQty").val(grandTotalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
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
    //     $("#totalQTY").val(totalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
    //     $("#totalQtyFree").val(totalQtyFree.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
    //     $("#grandTotalQty").val(grandTotalQty.toLocaleString(undefined, {maximumFractionDigits:numberOfDecimalDigit}));
    // }
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection