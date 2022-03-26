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
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusSo }}</span></h4>
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
                                    <label for="orderNum">Order Number</label><small class="text-muted">  automatic</small>
                                    <input type="text" id="orderNum" name="orderNum" class="form-control disabled-el" value="{{ $header->so_code }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="orderDate">Order Date</label>
                                    <input type="text" id="orderDate" name="orderDate" class="form-control flatpickr-basic" value="{{ $header->so_date }}" placeholder="DD-MM-YYYY" />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="salesman">Salesman*</label>
                                    <select class="select2 form-control" id="salesman" name="salesman" required>
                                        <option value="">Choose salesman</option>
                                        @foreach($employees as $val)
                                        <option value="{{$val->employee_id}}" {{ $val->employee_id == $header->salesman_code ? "selected" : ""}}>{{$val->employee_id}} - {{$val->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="type">Type</label>
                                    <select class="select2 form-control" id="type" name="type" required>
                                        @foreach($types as $val)
                                        <option value="{{$val}}" {{ $val == $header->order_type ? "selected" : ""}}>{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        {{-- <option value="">All</option> --}}
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{ $val == $header->currency ? "selected" : ""}}>{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="poNumber">PO Number</label>
                                    <input type="text" id="poNumber" name="poNumber" class="form-control text-uppercase" value="{{ $header->po_number }}" maxlength="40" autofocus required />
                                </div>
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="cust">Customer</label>
                                    <select class="select2 form-control" id="cust" name="cust" disabled>
                                        <option value="">Choose customer</option>
                                        @foreach($custs as $val)
                                            <option value="{{$val->kode}}|{{$val->inisial}}" {{$val->kode == $header->customer_id ? "selected" : ""}}>{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="ppn">PPN</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "ppn" name="ppn" value="{{ $header->ppn }}" maxlength="2" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="pph23">PPH23</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control angka text-right" id = "pph23" name="pph23" value="{{ $header->pph23 }}" maxlength="2" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
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
                        <table class="" style="width:98%;table-layout:fixed;">
                            <tbody>
                                <tr>
                                    <td class="isian-satu" style="width: 40%">
                                        <label>Article Code</label>
                                    </td>
                                    {{-- <td class="isian" style="width: 15%;">
                                        <label>Group</label>
                                    </td> --}}
                                    <td class="isian" style="width: 5%">
                                        <label>Stock</label>
                                    </td>
                                    <td class="isian" style="width: 5%">
                                        <label>QTY</label>
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
                        @foreach ($detail as $key =>$item)
                            <div id="new_row{{ $key }}" class="tanda-baris" >
                                <table class="table-bordered" style="width: 98%;table-layout:fixed;">
                                    <tbody>
                                        <tr>
                                            <td class="isian-satu" style="width: 40%;">
                                                <select class="select2 form-control dynamicSelect sku-select-system" id="article_id{{ $key }}" name="article_id[]" data-dependent="article_id">
                                                    @foreach($articles as $val)
                                                        <option value="{{$val->article_code}}|{{$val->group}}|{{$val->qty}}|{{$val->uom1}}" {{$val->article_code ==$item->article_code ? "selected" : ""}}>{{$val->article_alternative_code}} | {{$val->article_desc}}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="isian disabled d-none" style="width: 15%;">
                                                <input type="text" class="form-control-plaintext" id = "group" name="group[]" value="{{ $item->group }}"  disabled>
                                            </td>
                                            <td class="isian disabled" style="width: 5%">
                                                <input type="text" class="form-control-plaintext text-right" id = "qty_stock" name="qty_stock[]" value="{{ $item->qty_stock ==0 ? 0 :$item->qty_stock }}" disabled>
                                            </td>
                                            <td class="isian" style="width: 5%">
                                                <input type="text" class="form-control-plaintext angka text-right" id = "qty_order" name="qty_order[]" value="{{ $item->qty }}" maxlength="9" />
                                            </td>
                                            <td class="isian disabled" style="width: 5%">
                                                <span class="" id = "uom" name="uom[]">{{ $item->uom }}</span>
                                            </td>
                                            <td class="isian" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "price" name="price[]" value="{{ $item->price }}"  maxlength="11">
                                            </td>
                                            <td class="isian" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" id = "priceJasa" name="priceJasa[]" value="{{ $item->price_service }}" maxlength="11">
                                            </td>
                                            <td class="isian disabled text-right" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" value="{{ number_format($item->qty * $item->price) }}" id="totalLine" name="totalLine[]" >
                                                {{-- <span id="totalLine" name="totalLine[]">{{ number_format($item->qty * $item->price) }}</span> --}}
                                            </td>
                                            <td class="isian disabled text-right" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" value="{{ number_format($item->qty * $item->price_service) }}" id="totalJasa" name="totalJasa[]" >
                                                {{-- <span id="totalJasa" name="totalJasa[]"></span> --}}
                                            </td>
                                            <td class="isian disabled text-right" style="width: 10%">
                                                <input type="text" class="form-control-plaintext numeral-mask text-right" value="{{ number_format(($item->qty * $item->price)+($item->qty * $item->price_service)) }}" id="totalAll" name="totalAll[]" >
                                            </td>
                                            <td class="isian text-center" style="width: 5%">
                                                <a onmouseover="this.style.cursor='pointer'" onclick="$(this).parents('.tanda-baris').remove();hitungGrandTotal()">
                                                    <i data-feather="trash-2" class="remove_button feather-24">
                                                    </i>
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>
                    <hr>
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
                    <hr/>
                    <br/>
                    <div class="form-row">
                        <div class="col-md-12">
                            <a href="{{ route('salesOrders.index') }}" class="btn btn-warning">Back</a>
                            <a href="{{ route('salesOrder.create') }}" class="btn btn-success">New</a>
                            @if( $approveValidate ? $approveValidate[0]->validate : '' )
                                <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->last_approval }}">
                                <button class="btn btn-primary" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                            @else
                                @if(!count($approveHistory))
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                                @endif
                            @endif
                        </div>
                    </div>
                    <br><br>
                    <div class="row">
                        @foreach($approveHistory as $val)
                            <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                <h6 class="">Approve-{{ $val->approval_order }}</h6>
                                <small>{{ $val->name }}</small>
                            </div>
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

</style>
@endsection
@section('scripts')
@include('salesOrder.addArticle')
<script type="text/javascript">
    let cloneCount=$('#last_row_number').val();
    $(document).ready(function(){           
        validateFormToast("frmAdd");
        tombolPanah('qty_order');
        tombolPanah('price');
        activate_angka();
        mask_thousand();
        splitArticle();
        hitungTotal();
        hitungGrandTotal();
    });

    simpanData = (statusSimpan) =>{
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            // ambil semua data article
            let objQty= $('#article_row input[name="qty_order[]"]');
            let objPrice= $('#article_row input[name="price[]"]');
            let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
            let objUom= $('#article_row span[name="uom[]"]'); 
            let objGroup= $('#article_row input[name="group[]"]'); 
            let articles = []; 
            let flag=0; 
            let pesan="";
        
            $("#article_row select[name='article_id[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let article=$this.val().split("|");
                    let articleName=$this.select2('data')[0].text;
                    let plu=article[0];
                    let inisial = articleName.substring(2,5); 
                    let qty=objQty.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                    let price=objPrice.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                    let priceJasa=objPriceJasa.eq(i).val().replace(/[^0-9]/gi, '') || 0;
                    let uom=objUom.eq(i).text();
                    let group=objGroup.eq(i).val();
                    let cust=$('#cust').val().split("|");
                    let custName = $('#cust').select2('data')[0].text;
                    let customer=cust[1];
                
                    //es6
                    // let obj = ingredient.find(obj => obj.plu == plu);

                    //jquery
                    //cek apakah article ada yang double input ato ngk
                    let obj = $.grep(articles, function(obj){
                        return obj.article_code === plu;
                    })[0];
                    
                    if(obj) {
                        pesan +="Article "+plu+" entered more than once !! <br>"; 
                        flag=1;
                    } else {
                        if ((plu!=='') && (qty> 0)){
                            articles.push({
                                "article_code":plu,
                                "qty":qty,
                                "uom":uom,
                                "price":price,
                                "price_service":priceJasa,
                                "group":group
                            });
                        }
                    } 
                
                    if (qty == 0){
                        pesan +="QTY of items "+ articleName +" cannot be 0 <br>"; 
                        flag=1;
                    }

                    if (inisial !== customer){
                        
                        pesan +="This article "+ articleName +" does not belong to the customer "+custName +" <br>"; 
                        flag=1;
                    }
                
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){
                let orderNumber = $('#orderNum').val();
                let orderDate = $('#orderDate').val();
                let currency = $('#currency').val();
                let type = $('#type').val();
                let poNumber = $('#poNumber').val();
                let cust = $('#cust').val().split("|");
                let customer = cust[0];
                let salesman = $('#salesman').val();
                let ppn = $('#ppn').val().replace(/[^0-9]/gi, '') || 0;
                let pph23 = $('#pph23').val().replace(/[^0-9]/gi, '') || 0;
                let totalPpn = $('#totalPPN').val().replace(/[^0-9]/gi, '') || 0;
                let totalPph = $('#totalPPH').val().replace(/[^0-9]/gi, '') || 0;
                let note = $('#note').val();
                let approveLevel = $('#approveLevel').val();
        
                $.ajax({
                    type: "post",
                    url: "{{ route('salesOrder.update') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        orderNumber:orderNumber,
                        orderDate:orderDate,
                        currency:currency,
                        type:type,
                        poNumber:poNumber,
                        customer:customer,
                        salesman:salesman,
                        ppn:ppn,
                        pph23:pph23,
                        totalPph:totalPph,
                        totalPpn:totalPpn,
                        note:note,
                        approveLevel:approveLevel,
                        statusSimpan:statusSimpan
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            let message="";
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            
                            $('#poNumber').focus().select();
                            $('#orderNum').attr('disabled','disabled');

                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#orderNum').attr('disabled','disabled');
                            reloadPage();
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
    }

    $("#cmdSave").click(function(){     
        simpanData('update');
    });

    $("#cmdApprove").click(function(){     
        simpanData('approve');
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection