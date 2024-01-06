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
                            {{-- <input type="text" id="poNumberi" name="poNumberi" hidden> --}}
                            <input type="text" id="ppn" name="ppn" values="{{ $nilaiPPN }}" hidden>
                            <input type="text" id="pph23" name="ppn23" values="{{ $nilaiPPH }}" hidden>
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="invNumber">Invoice Number</label> <small class="text-muted"> automatic</small>
                                            <input type="text" id="invNumber" name="invNumber" class="form-control text-hitam disabled-el"  disabled />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="invDate">Invoice Date*</label>
                                            <input type="text" id="invDate" name="invDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="customer">Customer*</label>
                                            <select class="select2 form-control" id="customer" name="customer" required>
                                                <option value="">All</option>
                                                @foreach($customers as $val)
                                                    <option value="{{$val->kode}}" data-coa = "{{ $val->account }}" >{{$val->kode}} - {{$val->nama}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="accountPiutang">COA Piutang*</label>
                                            <input type="text" id="accountPiutang" name="accountPiutang" class="form-control disabled-el" value="{{ old('accountPiutang') }}" disabled />
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="soNumber">SO Number*</label>
                                            <select class="select2 form-control" id="soNumber" name="soNumber" required>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        {{-- <div class="form-group col-md-5">
                                            <label class="form-label" for="dnNumber">DN Number*</label>
                                            <select class="select2 form-control" id="dnNumber" name="dnNumber" required>
                                            </select>
                                        </div> --}}
                                        {{-- <div class="form-group col-md-6">
                                            <label for="fakturPajak">Faktur pajak*</label>
                                            <input type="text" id="fakturPajak" name="fakturPajak" class="form-control" />
                                        </div> --}}
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="note">Notes</label>
                                            <textarea type="text" id="note" name="note" class="form-control" rows="1" ></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-row">
                                        <div class="col-sm-12">
                                            <p class="mb-0">List DN*</p>
                                            <div class="card-datatable table-responsive pt-0">
                                                <table class="table table-bordered" id="listOfDn">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" width="10%">Check</th>
                                                            <th scope="col" width="30%">DN Number</th>
                                                            <th scope="col" width="30%">Date</th>
                                                            <th scope="col" width="30%">PO Number</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-row">
                                        <div class="col-md-12">
                                            <button class="btn btn-primary" type="button" id="cmdSubmit" name="cmdSubmit">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
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
                    @include('invoice.headerColumn')
                    <input type="text" id ="last_row_number" class="d-none" value="0">
                    
                    <div class="" id="articleRow" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                    </div>

                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px" hidden>
                    </div>
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
                                <div class="col-sm-5">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalAmount" class="col-sm-4 col-form-label titik-dua tanpa-padding">DPP</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalAmount" disabled />
                                    <input type="hidden" class="form-control text-right font-weight-bold" id="totalAmountJasa" disabled />
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
                            {{-- <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button> --}}
                            <a href="{{ route('invoice.index') }}" class="btn btn-light">Back</a>
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


</style>
@endsection
@section('scripts')
@include('invoice.addArticle')
<script type="text/javascript">
    
    let currentDate = todayDate('dd-mm-yyyy');    
    
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $("#totalQtyFree").val(humanizeNumber(0));
        $("#grandTotalQty").val(humanizeNumber(0));
        $('#invDate').val(currentDate);
        $('#cmdSave').show();
        $('#cmdPosting').hide();
        $("#vatCheck").prop("checked",false);
        $('#totalPPH').attr('disabled','disabled');
        showDetail='false';
        edit='false';
    });

    invDate = $('#invDate');
    if (invDate.length) {
        invDate.flatpickr({
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
        let coa = $("accountPiutang").val();
        if (coa){
            if (!$("#frmAdd")[0].checkValidity()){
                $("#frmAdd").submit();
            }else{ 
                $('#cmdSave').attr('disabled','disabled');
                $('.disabled-el').removeAttr('disabled');
                // ambil semua data article
                let objQty= $('#article_row input[name="qtyInv[]"]');
                let objPrice= $('#article_row input[name="price[]"]');
                let objPriceJasa= $('#article_row input[name="priceJasa[]"]');
                let objUom= $('#article_row span[name="uom[]"]'); 
                let articles = []; 
                let flag=0; 
                let pesan="";
    
                $("#article_row input[name='articleId[]']").map(function(i) {  
                    let $this=$(this);
                    if ($this.val()){
                        let articleCode = $this.data("code");
                        let articleDesc = $this.data("desc");
                        let articleUom = $this.data("uom");
                        let articleSoCode = $this.data("so-code");
                        let articleDnNumber = $this.data("dn-number");
                        let poNumber = $this.data("po-number");
                        let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                        let price=objPrice.eq(i).val().replace(/,/gi, '') || 0;
                        let priceJasa=objPriceJasa.eq(i).val().replace(/,/gi, '') || 0;
                        
                        if ((articleCode!=='') && (qty> 0)){
                            articles.push({
                                "article_code":articleCode,
                                "qty":qty,
                                "uom":articleUom,
                                "price":price,
                                "price_service":priceJasa,
                                "so_number":articleSoCode,
                                "dn_number":articleDnNumber,
                                "po_number":poNumber
                            });
                        }
    
                        if (qty == 0){
                            pesan +="QTY of items "+ articleDesc +" cannot be 0 <br>"; 
                            flag=1;
                        }
    
                        // if (inisial !== customer){
                        //     pesan +="This article "+ articleName +" does not belong to the customer "+custName +" <br>"; 
                        //     flag=1;
                        // }
                    }
                });
    
                if (articles.length == 0){
                    pesan +="Articles must be filled in completely <br>"; 
                    flag=1;
                }
    
                if (flag==0){
    
                    let invDate = $('#invDate').val();
                    let customer = $('#customer').val();
                    let soNumber = $('#soNumber').val();
                    let dnNumber = $('#dnNumber').val();
                    let poNumber = $('#poNumber').val();
                    let ppn = $('#ppn').val().replace(/,/gi, '');
                    let pph23 = $('#pph23').val().replace(/,/gi, '');
                    let totalPpn = $('#totalPPN').val().replace(/,/gi, '') || 0;
                    let totalPph = $('#totalPPH').val().replace(/,/gi, '') || 0;
                    let note = $('#note').val();
                    let fakturPajak =$('#fakturPajak').val();
                    let totalAmount = $('#totalAmount').val().replace(/,/gi, '') || 0;
                    let grandTotal = $('#totalNetto').val().replace(/,/gi, '') || 0;
    
                    $.ajax({
                        type: "post",
                        url: "{{ route('invoice.store') }}",
                        data: {
                            articles:JSON.stringify(articles),
                            invDate:invDate,
                            customer:customer,
                            ppn:ppn,
                            pph23:pph23,
                            totalPpn:totalPpn,
                            totalPph:totalPph,
                            note:note,
                            soNumber:soNumber,
                            dnNumber:dnNumber,
                            poNumber:poNumber,
                            fakturPajak:fakturPajak,
                            totalAmount:totalAmount,
                            grandTotal:grandTotal
                        },
                        dataType: "json",
                        success: function(data) {
                            if (data.status == 0 ){
                                for(let i = 0; i < data.message.length; i++) {
                                    show_msg(data.title, data.message[i], data.alert);
                                }
                                $('#invNumber').attr('disabled','disabled');
                                $('#cmdSave').removeAttr('disabled');
                            }else{
                                show_msg(data.title, data.message, data.alert);
                                $('#invNumber').val(data.invNumber);
                                $('#invNumber').attr('disabled','disabled');
                                $('#cmdSave').attr('disabled','disabled');
                                $('#totalPPN').attr('disabled','disabled');
                                $('#totalPPH').attr('disabled','disabled');
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