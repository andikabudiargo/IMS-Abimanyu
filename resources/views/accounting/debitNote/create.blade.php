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
                    <h4 class="card-title">Status: <span id="statusText">{{ $status }}</span></h4>
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
                            <input type="text" id="ppn" name="ppn" values="{{ $nilaiPPN }}" hidden>
                            <input type="text" id="pph23" name="ppn23" values="{{ $nilaiPPH }}" hidden>
                            <input type="text" class="form-control" id="pembilangNumber" name="pembilangNumber" hidden/>
                            <input type="text" class="form-control" id="penyebutNumber" name="penyebutNumber" hidden/>
                            <datalist id="articlesList">
                            </datalist>
                            <div class="form-group col-md-6">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="debitNnumber">Debit Note Number</label> <small class="text-muted"> automatic</small>
                                        <input type="text" id="debitNnumber" name="debitNnumber" class="form-control text-hitam disabled-el"  disabled />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="debitNDate">Invoice Date*</label>
                                        <input type="text" id="debitNDate" name="debitNDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-12">
                                        <label class="form-label" for="customer">Customer*</label>
                                        <select class="select2 form-control" id="customer" name="customer" required>
                                            <option value="">All</option>
                                            @foreach($customers as $val)
                                                <option value="{{$val->kode}}" data-coa = "{{ $val->account }}" data-coa-penjualan = "{{ $val->coa_penjualan }}" >{{$val->kode}} - {{$val->nama}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="accountPiutang">COA Piutang*</label>
                                        <input type="text" id="accountPiutang" name="accountPiutang" class="form-control disabled-el" value="{{ old('accountPiutang') }}" disabled />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="accountPenjualan">COA Penjualan*</label>
                                        <input type="text" id="accountPenjualan" name="accountPenjualan" class="form-control disabled-el" value="{{ old('accountPenjualan') }}" disabled />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label class="form-label" for="soNumber">SO Number</label>
                                        <input type="text" id="soNumber" name="soNumber" class="form-control disabled-el" value="{{ old('soNumber') }}" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label class="form-label" for="poNumber">PO Number</label>
                                        <input type="text" id="poNumber" name="poNumber" class="form-control disabled-el" value="{{ old('poNumber') }}" />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-12">
                                        <label class="form-label" for="note">Notes</label>
                                        <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                    </div>
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
                            @include('accounting.debitNote.headerColumn')
                            <div class="" id="article_row">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
                        <button class="btn btn-primary btn-prev" type="button" onclick="add_new_row();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block ">Add Article</span>
                        </button>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-7">
                            <div class="form-group row mb-03">
                                {{-- <label for="totalRow" class="col-sm-4 col-form-label titik-dua tanpa-padding">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled/>
                                </div> --}}
                            </div>
                            <div class="form-group row mb-03">
                                {{-- <label for="totalQTY" class="col-sm-4 col-form-label titik-dua tanpa-padding">Total QTY</label>
                                <div class="col-sm-5">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div> --}}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-4 col-form-label titik-dua tanpa-padding">DPP</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalAmount" disabled />
                                    <input type="hidden" class="form-control text-right font-weight-bold" id="totalAmountJasa" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="nilaiLainCheck" class="col-sm-4 col-form-label titik-dua">DPP Nilai Lain <span id="nilaiDppLain"></span></label>
                                <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="nilaiLainCheck" name="nilaiLainCheck" />
                                        <label class="custom-control-label" for="nilaiLainCheck"></label>
                                    </div>
                                </div>    
                                <div class="col-sm-5">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" oninput='inputDecimal(this)' id="totalDppNilaiLain"  name="totalDppNilaiLain" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-4 col-form-label titik-dua">PPN <span id="nilaiPPN"></span> </label>
                                <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="vatCheck" name="vatCheck" />
                                        <label class="custom-control-label" for="vatCheck"></label>
                                    </div>
                                </div>    
                                <div class="col-sm-5">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" oninput='inputDecimal(this)' id="totalPPN"  name="totalPPN" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPH" class="col-sm-4 col-form-label titik-dua">PPH23 <span id="nilaiPPH"></span> </label>
                                <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="pph23Check" name="pph23Check" />
                                        <label class="custom-control-label" for="pph23Check"></label>
                                    </div>
                                </div> 
                                <div class="col-sm-5">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el"  oninput='inputDecimal(this)' id="totalPPH" name="totalPPH" disabled/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalNetto" class="col-sm-4 col-form-label titik-dua tanpa-padding">Netto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalNetto" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            <a href="{{ route('debitNote.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-info" type="reset" id="cmdNew" name="cmdCancel">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            {{-- @can('receiving-posting') --}}
                                <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                            {{-- @endcan --}}
                        </div>
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

    .totalLine{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media screen 
    and (min-device-width: 1200px) 
    and (max-device-width: 1600px) 
    and (-webkit-min-device-pixel-ratio: 1) { 
        .lebar-list-item{
            width:150%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }

    @media only screen and (min-width: 600px)
    and (max-width: 1200px)
    {
        .lebar-list-item{
            width:200%;
        }
        .container-list-item{
            max-width:100%;
            overflow-x:auto;
            scrollbar-width: thin;
            margin-top:7px;
        }
    }
</style>
@endsection
@section('scripts')
@include('accounting.debitNote.addArticle')
<script type="text/javascript">    
    
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $('#debitNDate').val(currentDate);
        $('#cmdSave').show();
        $('#cmdPosting').hide();
        $("#vatCheck").prop("checked",false);
        $('#totalPPH').attr('disabled','disabled');
        showDetail='false';
        edit='false';
        $("#nilaiLainCheck").prop('checked',true).change();
    });

    debitNDate = $('#debitNDate');
    if (debitNDate.length) {
        debitNDate.flatpickr({
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
        let coa = $("#accountPiutang").val();
        if (coa){
            if (!$("#frmAdd")[0].checkValidity()){
                $("#frmAdd").submit();
            }else{ 
                $('#cmdSave').attr('disabled','disabled');
                $('.disabled-el').removeAttr('disabled');
                // ambil semua data article
                let objArticle = $('#article_row input[name="articleId[]"]');
                let objQty= $('#article_row input[name="qtyInv[]"]');
                let objPrice= $('#article_row input[name="price[]"]');
                let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
                let objUom= $('#article_row input[name="uom[]"]'); 
                let objSubTotal= $('#article_row input[name="subTotal[]"]'); 
                let articles = []; 
                let flag=0; 
                let pesan="";
    
                $("#article_row input[name='articleId[]']").map(function(i) {  
                    let $this=$(this);
                    if ($this.val()){
                        let articleCode = $this.val();
                        // let articleDesc = $this.eq(i).find(":selected").data("desc");
                        let articleUom = objUom.val();
                        let articleSoCode = $('#soNumber').val();
                        let poNumber = $('#poNumber').val();
                        let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                        let price=objPrice.eq(i).val().replace(/,/gi, '') || 0;
                        let priceJasa=objPriceJasa.eq(i).val().replace(/,/gi, '') || 0;
                        let subTotal=objSubTotal.eq(i).val().replace(/,/gi, '') || 0;
                        
                        if ((articleCode!=='') && (qty> 0)){
                            articles.push({
                                "article_code":articleCode,
                                "qty":qty,
                                "uom":articleUom,
                                "price":price,
                                "price_service":priceJasa,
                                "so_number":articleSoCode,
                                "po_number":poNumber
                            });
                        }
    
                        if (qty == 0){
                            pesan +="QTY of items "+ articleCode +" cannot be 0 <br>"; 
                            flag=1;
                        }

                        if (parseFloat(subTotal) == 0){
                            pesan +="Price  of items "+ articleCode +" cannot be 0 <br>"; 
                            flag=1;
                        }
                    }
                });
    
                if (articles.length == 0){
                    pesan +="Articles must be filled in completely <br>"; 
                    flag=1;
                }

                // console.log(articles);
                flag==1;
    
                if (flag==0){
                    let debitNDate = $('#debitNDate').val();
                    let customer = $('#customer').val();
                    let soNumber = $('#soNumber').val();
                    let poNumber = $('#poNumber').val();
                    let ppn = $('#ppn').val().replace(/,/gi, '');
                    let pph23 = $('#pph23').val().replace(/,/gi, '');
                    let totalPpn = $('#totalPPN').val().replace(/,/gi, '') || 0;
                    let totalPph = $('#totalPPH').val().replace(/,/gi, '') || 0;
                    let note = $('#note').val();
                    let fakturPajak =$('#fakturPajak').val();
                    let totalAmount = $('#totalAmount').val().replace(/,/gi, '') || 0;
                    let grandTotal = $('#totalNetto').val().replace(/,/gi, '') || 0;
                    let aPembilangNumber = $('#pembilangNumber').val();
                    let aPenyebutNumber = $('#penyebutNumber').val();
                    let aTotalDppNilaiLain = $('#totalDppNilaiLain').val().replace(/,/gi, '') || 0;
    
                    $.ajax({
                        type: "post",
                        url: "{{ route('debitNote.store') }}",
                        data: {
                            articles:JSON.stringify(articles),
                            debitNDate:debitNDate,
                            customer:customer,
                            ppn:ppn,
                            pph23:pph23,
                            totalPpn:totalPpn,
                            totalPph:totalPph,
                            note:note,
                            soNumber:soNumber,
                            poNumber:poNumber,
                            fakturPajak:fakturPajak,
                            totalAmount:totalAmount,
                            grandTotal:grandTotal,
                            pembilangNumber:aPembilangNumber,
                            penyebutNumber:aPenyebutNumber,
                            totalDppNilaiLain:aTotalDppNilaiLain
                        },
                        dataType: "json",
                        success: function(data) {
                            if (data.status == 0 ){
                                for(let i = 0; i < data.message.length; i++) {
                                    show_msg(data.title, data.message[i], data.alert);
                                }
                                $('#debitNnumber').attr('disabled','disabled');
                                $('#cmdSave').removeAttr('disabled');
                            }else{
                                show_msg(data.title, data.message, data.alert);
                                $('#debitNnumber').val(data.debitNnumber);
                                $('#totalAmount').attr('disabled','disabled');
                                $('#debitNnumber').attr('disabled','disabled');
                                $('#cmdSave').attr('disabled','disabled');
                                $('#customer').attr('disabled','disabled');
                                $('#totalPPN').attr('disabled','disabled');
                                $('#totalPPH').attr('disabled','disabled');
                                $('#accountPiutang').attr('disabled','disabled');
                                $('#accountPenjualan').attr('disabled','disabled');
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
        }else{
            Swal.fire("Warning","Customer belum memiliki COA Piutang","warning"); 
        }
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection