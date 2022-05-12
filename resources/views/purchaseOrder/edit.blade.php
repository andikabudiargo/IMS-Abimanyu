@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusPo }}</span></h4>
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
                            <input type="text" id="article" name="article" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="poNumber">Order Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control disabled-el" value="{{ $header->po_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label class="form-label" for="poType">PO Type*</label>
                                    <select class="select2 form-control" id="poType" name="poType" disabled required>
                                        <option value="std" {{ $header->order_type == 'std' ? "selected" : "" }}>Standard</option>
                                        <option value="sub" {{ $header->order_type == 'sub' ? "selected" : "" }}>Subcontracting</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date*</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control" value="{{ $header->po_date }}" placeholder="DD-MM-YYYY" required disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="deliveryDate">Delivery Date</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate" class="form-control" value="{{ $header->delivery_date }}" placeholder="DD-MM-YYYY" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="supplier">Supplier*</label>
                                    <select class="select2 form-control" id="supplier" name="supplier" required>
                                        <option value="">All</option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->supplier_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-1">
                                    <label class="form-label" for="term">Term</label>
                                    <input type="text" class="form-control angka text-right" id = "term" name="term" value="{{ $header->termin }}" maxlength="4" />
                                </div>
                                <div class="form-group col-md-1 d-flex align-items-end" >
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" {{ $header->pkp == 'PKP' ? "checked" : "" }} id="pkp" name="pkp"/>
                                        <label class="custom-control-label" for="pkp">PKP</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id="ppn" name="ppn" value="{{ $header->ppn }}" maxlength="2" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{$val == $header->currency ? "selected" : ""}} >{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2 d-none">
                                    <div class="form-group">
                                        <label for="kurs">Kurs</label>
                                        <input type="text" id="kurs" name="kurs" class="form-control angka" value="{{ $header->kurs }}" maxlength="6" />
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-9">
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
                            @include('purchaseOrder.headerColumn')
                            <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                @foreach ($detail as $key =>$item)
                                    <div id="new_row{{ $key }}" class="tanda-baris" >
                                        <div class="form-row d-flex align-items-center">
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="pRequest" class="d-block d-md-none">Purchase Request</label>
                                                    <select class="form-control dynamicSelect sku-select-system" id="pRequest{{ $key }}" name="pRequest[]" data-dependent="pRequest">
                                                        @foreach($prHeader as $val)
                                                            <option value="{{ $val->pr_number }}" {{ $val->pr_number == $item->pr_number ? "selected" :"" }} >{{ $val->pr_number }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="article_id" class="d-block d-md-none">Article</label>
                                                    <select class="form-control dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id">
                                                        @foreach($articles as $val)
                                                            <option value="{{ $val->article_code }}|{{ $val->group }}|{{ $val->qty_stock }}|{{ $val->qty }}|{{ $val->uom1 }}|{{ $val->costprice }}" 
                                                                    data-uom-group="{{ $val->uom_group }}'" {{ $val->article_code == $item->article_code && $val->pr_number == $item->pr_number ? "selected" :"" }}>
                                                                    {{ $val->article_alternative_code }} - {{ $val->article_desc }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qty_stock" class="d-block d-md-none">Stock</label>
                                                    <input type="text" class="form-control {{ $item->uom_group  == 'PIECE' ? 'numeral-mask-satuan' : 'numeral-mask-digit' }} text-right" id="qty_stock" name="qty_stock[]" value="{{ $item->qty_stock == 0 ? 0 :$item->qty_stock }}" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="qty_order" class="d-block d-md-none">QTY Order</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" class="form-control {{ $item->uom_group  == 'PIECE' ? 'numeral-mask-satuan' : 'numeral-mask-digit' }} text-right" id="qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="9" />
                                                        <div class="input-group-append">
                                                            <span class="input-group-text" id ="uom" name="uom[]">{{ $item->uom }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12 d-none">
                                                <div class="form-group margin-nol">
                                                    <label for="price" class="d-block d-md-none">Price</label>
                                                    <input type="text" class="form-control numeral-mask text-right" id= "price" name="price[]" value="{{ $item->old_price }}"  maxlength="11">
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="price" class="d-block d-md-none">Price</label>
                                                    <div class="input-group input-group-merge">
                                                        <input type="text" class="form-control numeral-mask text-right" id="newPrice" name="newPrice[]" value="{{ $item->price }}"  maxlength="11">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text cursor-pointer">
                                                                <a onmouseover="this.style.cursor='pointer'" 
                                                                    id="listPrice" name="listPrice[]" 
                                                                    data-toggle="tooltip" 
                                                                    data-placement="right" 
                                                                    title="List Price"
                                                                    onClick="listPrice('{{ $item->article_code }}','{{ $item->article_code }}','{{ $key }}')">
                                                                    <i data-feather="info" class="feather-24">
                                                                    </i>
                                                                </a>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <label for="totalLine" class="d-block d-md-none">Total</label>
                                                    <input type="text" class="form-control numeral-mask text-right" value="{{ number_format($item->qty * $item->price) }}" id="totalLine" name="totalLine[]" disabled>
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal();">
                                                        <i data-feather="trash-2" class="remove_button feather-24">
                                                        </i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua">Total QTY</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-3 col-form-label titik-dua">Bruto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalAmount" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-3 col-form-label titik-dua">Discount </label>
                                <div class="col-sm-2" style="padding-right: 0rem;">
                                    <input type="text" class="form-control text-right font-weight-bold" id="persenDiscount" maxlength="2"/>
                                </div>
                                <div class="col-sm-4" style="padding-left: 0rem;">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalDiscount" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-3 col-form-label titik-dua">PPN <span id="nilaiPPN"></span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPN" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPH" class="col-sm-3 col-form-label titik-dua">PPH <span>22</span> </label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalPPH" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalNetto" class="col-sm-3 col-form-label titik-dua">Netto</label>
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
                                    <a href="{{ route('purchaseOrders.index') }}" class="btn btn-warning">Back</a>
                                    <a href="{{ route('purchaseOrder.create') }}" class="btn btn-success">New</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-primary" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                    @else
                                        @if(!count($approveHistory))
                                            <button class="btn btn-primary" type="button" id="cmdUpdate" name="cmdUpdate">Update</button>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <hr>
                    <div class="form-row card-statistics">
                        @foreach($approveHistory as $val)
                            <div class="statistics-body">
                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                    <div class="media">
                                        <div class="avatar bg-light-success mr-2">
                                            <div class="avatar-content">
                                                <i data-feather="check" class="avatar-icon"></i>
                                            </div>
                                        </div>
                                        <div class="media-body my-auto">
                                            <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}/{{ $val->approval_number }}</h4>
                                            <p class="card-text mb-0">{{ $val->name }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('purchaseOrder.modalListPrice')
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
@include('purchaseOrder.addArticle')
<script type="text/javascript">
    const updateBtn = document.querySelector('#cmdUpdate'); 
    const approveBtn = document.querySelector('#cmdApprove'); 

    updateBtn.addEventListener('click',() =>{
        updateData('update');
    },{ once:true});

    // approveBtn.addEventListener('click',() =>{
    //     updateData('approve');
    // },{ once:true});

    let cloneCount={{ count($detail) }};
    $("input[type='text']").click(function () {
        $(this).select();
    });  

    $(document).ready(function(){           
        validateForm('frmAdd');
        tombolPanah('qty_order');
        tombolPanah('price');
        activate_angka();
        mask_thousand();
        splitArticle();
        isiListArticle();
        hitungTotal();
        hitungGrandTotal();
        $('.sku-select-system').select2();
    });

    // orderDate = $('#orderDate');
    // if (orderDate.length) {
    //     orderDate.flatpickr({
    //         dateFormat: "d-m-Y",
    //     });
    // }

    // deliveryDate = $('#deliveryDate');
    // if (deliveryDate.length) {
    //     deliveryDate.flatpickr({
    //         dateFormat: "d-m-Y",
    //         minDate: currentDate
    //     });
    // }

    // $('#pkp').change(function() {
    //     if ($(this).is(':checked')) {
    //         $('#ppn').val("{{ $attribute['ppn'] }}");
    //         $("#nilaiPPN").text("{{ $attribute['ppn'] }}%");
    //         $('#ppn').removeAttr('disabled');
    //         hitungGrandTotal();
    //     }else{
    //         $('#ppn').val(0);
    //         $('#ppn').attr('disabled','disabled');
    //         hitungGrandTotal();
    //     }
    // });

    // $('#persenDiscount,#ppn').on('keyup', function() {
    //     hitungGrandTotal();
    // })
    
    // function reloadPage(){
    //     window.location.reload();
    // }

    // $("#cmdCancel,#cmdNew").click(function(){
    //     reloadPage();
    // });

    // $("#cmdSave").click(function(){
    //     if (!$("#frmAdd")[0].checkValidity()){
    //         $("#frmAdd").submit();
    //     }else{  
    //         $('.disabled-el').removeAttr('disabled');
    //         // ambil semua data article
    //         let objQty= $('input[name="qty_order[]"]');
    //         let objPrice= $('input[name="price[]"]');
    //         let objNewPrice= $('input[name="newPrice[]"]');
    //         let objUom= $('span[name="uom[]"]'); 
    //         let objpr= $('select[name="pRequest[]"]'); 
    //         let articles = []; 
    //         let flag=0; 
    //         let pesan="";
            
    //         $("#article_row select[name='article_id[]']").map(function(i) {  
    //             let $this=$(this);
    //             if ($this.val()){
    //                 let article=$this.val().split("|");
    //                 let articleName=$this.select2('data')[0].text;
    //                 let plu=article[0];
    //                 let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
    //                 let newPrice=objNewPrice.eq(i).val().replace(/[^0-9]/gi, '') || 0;
    //                 let price=objPrice.eq(i).val().replace(/[^0-9]/gi, '') || 0;
    //                 let pRequest=objpr.eq(i).val();
    //                 let uom=objUom.eq(i).text();
    //                 let supp=$('#supplier').val();
    //                 let suppName = $('#supplier').select2('data')[0].text;
    //                 let supplier=supp;
                
    //                 //es6
    //                 // let obj = ingredient.find(obj => obj.plu == plu);

    //                 //jquery
    //                 //cek apakah article ada yang double input ato ngk
    //                 let obj = $.grep(articles, function(obj){
    //                     return obj.article_code === plu;
    //                 })[0];
                    
    //                 if(obj) {
    //                     pesan +="Article "+plu+" entered more than once !! <br>"; 
    //                     flag=1;
    //                 } else {
    //                     if ((plu!=='') && (qty> 0)){
    //                         articles.push({
    //                             "article_code":plu,
    //                             "qty":qty,
    //                             "uom":uom,
    //                             "price":price,
    //                             "newPrice":newPrice,
    //                             "pRequest":pRequest
    //                         });
    //                     }
    //                 } 
                
    //                 if (qty == 0){
    //                     pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
    //                     flag=1;
    //                 }
                
    //             }
    //         });

    //         if (articles.length == 0){
    //             pesan +="Articles must be filled in completely <br>"; 
    //             flag=1;
    //         }

    //         if (flag==0){

    //             let orderDate = $('#orderDate').val();
    //             let poType = $('#poType').val();
    //             let deliveryDate = $('#deliveryDate').val();
    //             let currency = $('#currency').val();
    //             let supp = $('#supplier').val();
    //             let term = $('#term').val()||0;
    //             let kurs = $('#kurs').val()||1;
    //             let ppn = $('#ppn').val().replace(/[^0-9]/gi, '') || 0;
    //             let totalPph = $('#totalPPH').val().replace(/[^0-9]/gi, '') || 0;
    //             let totalPpn = $('#totalPPN').val().replace(/[^0-9]/gi, '') || 0;
    //             let note = $('#note').val();
    //             let persenDiscount = $('#persenDiscount').val() || 0;
    //             let poNumber = $('#poNumber').val();

    //             $.ajax({
    //                 type: "post",
    //                 url: "{{ route('purchaseOrder.update') }}",
    //                 data: {
    //                     articles:JSON.stringify(articles),
    //                     poNumber:poNumber,
    //                     orderDate:orderDate,
    //                     poType:poType,
    //                     deliveryDate:deliveryDate,
    //                     currency:currency,                
    //                     supplier:supp,
    //                     tax:tax,
    //                     ppn:ppn,
    //                     term:term,
    //                     totalPph:totalPph,
    //                     totalPpn:totalPpn,
    //                     kurs:kurs,
    //                     note:note,
    //                     discount:persenDiscount
    //                 },
    //                 dataType: "json",
    //                 success: function(data) {
    //                     if (data.status == 0 ){
    //                         for(let i = 0; i < data.message.length; i++) {
    //                             show_msg(data.title, data.message[i], data.alert);
    //                         }
    //                         $('#poNumber').attr('disabled','disabled');
    //                     }else{
    //                         show_msg(data.title, data.message, data.alert);
    //                         $('#poNumber').attr('disabled','disabled');
    //                         $('#cmdSave').attr('disabled','disabled');
    //                         $('#addNewRow').attr('disabled','disabled');
    //                         $('#poNumber').val(data.poNumber);
    //                     }
                        
    //                 },
    //                 error: function(error) {
    //                     console.log(error);
    //                 }
    //             });

    //         }else{
    //             Swal.fire('Warning..',pesan,'warning');
    //         }
    //     }
    // });
    
    
    // function add_new_row() {    
    //     let supplier = $('#supplier');
    //     let supp = supplier.val();
    //     let poType = $('#poType').val();
    //     if (supp){            
    //         $("#article_row").append($("#new_row").clone().html());
    //         cloneCount++;
    //         $("#article_row").find('#baru').attr('id', 'new_row'+ cloneCount);
    //         $("#new_row"+ cloneCount).find('#article_id').attr('id', 'article_id'+ cloneCount);
    //         $("#new_row"+ cloneCount).find('#pRequest').attr('id', 'pRequest'+ cloneCount);
    //         poType =='std' ? changeselect('pRequest','pRequest'+ cloneCount,supp,'') : changeselect('pRequest_sub','pRequest'+ cloneCount,supp,'');
    //         $("#article_id"+cloneCount).select2();
    //         $("#pRequest"+cloneCount).select2();
    //         $('#remove_button').tooltip();
    //         tombolPanah('qty_order');
    //         tombolPanah('newPrice');
    //         activate_angka();
    //         mask_thousand();
    //         // splitArticle();
    //         isiListArticle();
    //         hitungTotal();
    //         hitungGrandTotal();
    //         $('[data-toggle="tooltip"]').tooltip();
    //     }else{
    //         Swal.fire({
    //             title: 'Warning',
    //             text: "Choose Supplier",
    //             icon: 'warning',
    //             confirmButtonColor: '#3085d6',
    //             confirmButtonText: 'OK'
    //         }).then((result) => {
    //             if (result.isConfirmed) {
    //                 supplier.select2('open');
    //             }
    //         })
    //     }
    // };

    // function isiListArticle(){
    //     // split article with delimiter |
    //     let objPrequest = $('#article_row select[name="pRequest[]"]');
        
    //     objPrequest.change(function(e){        
    //         let objIndex = objPrequest.index(this);
    //         let prNumber = objPrequest.eq(objIndex).val();
    //         let supp = $('#supplier').val();
    //         let poType = $('#poType').val();
    //         poType =='std' ? changeSelectArticle('searchFromPr',objIndex,supp,prNumber) : changeSelectArticle('searchFromPr_sub',objIndex,supp,prNumber);
    //         splitArticle();
	// 	});
    // }

    // function changeSelectArticle(dependent,objIndex,value,prNumber) {
    //     let objArticle = $('#article_row select[name="article_id[]"]');
    //     $.ajax({
    //         url:"{{route('dynamic.dependent')}}",
    //         method:"POST",
    //         data:{
    //             value:value,
    //             prNumber:prNumber,
    //             dependent:dependent
    //         },
    //         success:function(result){
    //             objArticle.eq(objIndex).html(result);
    //             objArticle.eq(objIndex).select2();
    //             // objArticle.eq(objIndex).trigger('change');
    //         }
    //     })
    // }

    // function changeselect(dependent,obj,value,type) {
    //   $.ajax({
    //     url:"{{route('dynamic.dependent')}}",
    //     method:"POST",
    //     data:{
    //         value:value,
    //         type:type,
    //         dependent:dependent
    //     },
    //     success:function(result){
    //         $('#'+obj).html(result);
    //         // $('#'+obj).val('').trigger('change');
    //     }
    //   })
    // }

    // function splitArticle(){
    //     // split article with delimiter |
    //     let objArticle = $('#article_row select[name="article_id[]"]');
    //     let objStock= $('#article_row input[name="qty_stock[]"]');
    //     let objUom= $('#article_row span[name="uom[]"]'); 
    //     let objQty= $('#article_row input[name="qty_order[]"]');
    //     let objPrice= $('#article_row input[name="price[]"]');
    //     let objNewPrice= $('#article_row input[name="newPrice[]"]');
    //     let objListPrice= $('#article_row a[name="listPrice[]"]');
    //     let objTotal= $('#article_row input[name="totalLine[]"]');
    //     objArticle.change(function(e){        
    //         //     0            1           2         3       4        5             6
    //         // article_code.'|'group.'|'qty_stock.'|'qty.'|'uom1.'|'costprice.'|'last_price.'"
    //         let objIndex = objArticle.index(this);
    //         let detail = objArticle.eq(objIndex).val();
    //         let detailText = objArticle.eq(objIndex).select2('data')[0].text;
    //         let arrDetail = detail.split("|");
    //         let uomGroup = objArticle.eq(objIndex).find(":selected").data("uom-group");

    //         objListPrice.eq(objIndex).attr('onClick', 'listPrice('+arrDetail[0]+',"'+detailText+'");');
    //         objStock.eq(objIndex).val(humanizeNumber(arrDetail[2]||0));
    //         objUom.eq(objIndex).text(arrDetail[4]);
    //         objPrice.eq(objIndex).val(humanizeNumber(arrDetail[5]||0));
    //         objNewPrice.eq(objIndex).val(humanizeNumber(arrDetail[6]||0));
    //         objArticle.eq(objIndex).select2('open');
    //         if (detail){
    //             setTimeout(() => {
    //                 objQty.eq(objIndex).focus().select();
    //             }, 5);
    //         }

    //         objTotal.eq(objIndex).val(humanizeNumber((arrDetail[3]||0)*(arrDetail[6]||0)));
    //         hitungGrandTotal();

    //         if ( uomGroup === 'PIECE' ){
    //             objQty.removeClass("numeral-mask-digit");
    //             objQty.addClass("numeral-mask-satuan");
    //             mask_thousand_satuan();
    //         }else{
    //             objQty.removeClass("numeral-mask-satuan");
    //             objQty.addClass("numeral-mask-digit");
    //             mask_thousand_digit(numberOfDecimalDigit);
    //         }

	// 	});
    // }

    // function listPrice(article,desc){
    //     $("#modalTableData tbody> tr").remove();
    //     $.ajax({
    //         dataType: 'json',
    //         type:'GET',
    //         url: "{{ route('purchaseOrder.price.list') }}",
    //         data: { article:article },
    //         success: function(data) {
    //             if(data.length > 0 ){
    //                 let html = '';
    //                 for(let i=0;i<data.length;i++){
    //                     html += '<tr>';
    //                     html += '<td>'+data[i].po_number+'</td>';
    //                     html += '<td>'+data[i].po_date+'</td>';
    //                     html += '<td class="text-right">'+humanizeNumber(data[i].price)+'</td>';
    //                     html += '</tr>';
    //                 }
    //                 $('#modalTableData tbody').append(html);
    //             }                
    //         },
    //         error: function(data) {
    //             swal.fire("Warning","Error data","warning");
    //         }
    //     });
    //     $('#modalArticle').text(desc);
    //     $('#modalListPrice').modal('show'); 
    // }

    // function hitungTotal(){
    //     let objQty= $('#article_row input[name="qty_order[]"]');
    //     let objNewPrice= $('#article_row input[name="newPrice[]"]');
    //     let objTotal= $('#article_row span[name="totalLine[]"]');
        
    //     objQty.keyup(function() {
    //         let indexnya= objQty.index(this);
    //         let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
    //         let newPrice = objNewPrice.eq(indexnya).val().replace(/[^0-9]/gi, '') ||0;
    //         let total = qty*newPrice;
    //         objTotal.eq(indexnya).text(humanizeNumber(total));
    //         hitungGrandTotal();
    //     });    

    //     objNewPrice.keyup(function() {
    //         let indexnya= objNewPrice.index(this);
    //         let qty = objQty.eq(indexnya).val().replace(/,/gi, '') || 0; 
    //         let newPrice = objNewPrice.eq(indexnya).val().replace(/[^0-9]/gi, '')||0;
    //         let total = qty*newPrice;
    //         objTotal.eq(indexnya).text(humanizeNumber(total));
    //         hitungGrandTotal();
    //     });    
    // }

    // function hitungGrandTotal(){
    //     let objArticle = $('#article_row select[name="article_id[]"]');
    //     let objQtyTiw= $('#article_row input[name="qty_order[]"]');
    //     let objQTY= $('#article_row input[name="qty_order[]"]');
    //     let objNewPrice= $('#article_row input[name="newPrice[]"]');
    //     let persenDiscount = $('#persenDiscount').val() || 0;
    //     let ppn= $('#ppn').val();
    //     let totalQty= 0;
    //     let totalAmount=0

    //     let qty = objQTY.map(function(){return $(this).val();}).get();
    //     let price = objNewPrice.map(function(){return $(this).val();}).get();
        
    //     totalQty = sumFromArray(qty);
    //     totalAmount = sumFromArray(qty,price);
        
    //     $("#totalRow").val(objArticle.length);
    //     $("#nilaiPPN").text(ppn+"%");
    //     $("#totalQTY").val(humanizeNumber(totalQty));
    //     $("#totalAmount").val(humanizeNumber(totalAmount));
    //     $("#totalDiscount").val(humanizeNumber((totalAmount*parseInt(persenDiscount))/100));
    //     $("#totalPPN").val(humanizeNumber((parseInt(ppn)*totalAmount)/100));
    //     $("#totalPPH").val(0);
    //     $("#totalNetto").val(humanizeNumber((totalAmount+((parseInt(ppn)*totalAmount)/100))-((totalAmount*parseInt(persenDiscount))/100)));

    // }
      
    // $.ajaxSetup({
    //     headers: {
    //         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //     }
    // });

</script>
@endsection