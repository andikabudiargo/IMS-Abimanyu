@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="edit-index">
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
                        <form id="frmAdd" name="frmAdd"  autocomplete="off">
                            @csrf
                            <input type="text" id="ppn" name="ppn" values="{{ $nilaiPPN }}" hidden>
                            <input type="text" id="pph23" name="ppn23" values="{{ $nilaiPPH }}" hidden>
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="invNumber">Invoice Number</label> <small class="text-muted"> automatic </small>
                                            <input type="text" id="invNumber" name="invNumber" value="{{ $header->invoice_number }}" class="form-control text-hitam disabled-el"  disabled />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="invDate">Invoice Date*</label>
                                            <input type="text" id="invDate" name="invDate" value="{{ $header->invoice_date }}" class="form-control" placeholder="DD-MM-YYYY" required />
                                        </div>                               
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="customer">Customer*</label>
                                            <select class="select2 form-control" id="customer" name="customer" required disabled>
                                                <option value="">All</option>
                                                @foreach($customers as $val)
                                                    <option value="{{$val->kode}}" data-coa = "{{ $val->account }}" {{$val->kode == $header->customer_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="accountPiutang">COA Piutang*</label>
                                            <input type="text" id="accountPiutang" name="accountPiutang" class="form-control disabled-el" value="{{ old('accountPiutang',$header->account_piutang) }}" disabled />
                                        </div> 
                                        <div class="form-group col-md-6">
                                            <label for="sendingDate">Sending Date</label>
                                            <input type="text" id="sendingDate" name="sendingDate" class="form-control" value="{{ old('sendingDate',$header->sending_date) }}" placeholder="DD-MM-YYYY"/>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="soNumber">SO Number*</label>
                                            <input type="text" id="soNumber" name="soNumber" value="{{ $header->so_number }}" class="form-control" disabled />
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="note">Notes</label>
                                            <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
                                        </div>
                                    </div>
                                    {{-- <div class="form-row">
                                        <div class="form-group col-md-5">
                                            <label class="form-label" for="dnNumber">DN Number*</label>
                                            <input type="text" id="dnNumber" name="dnNumber" value="{{ $header->dn_number }}" class="form-control" disabled /> --}}
                                            {{-- <select class="select2 form-control" id="dnNumber" name="dnNumber" >
                                            </select> --}}
                                        {{-- </div> --}}
                                        {{-- <div class="form-group col-md-6">
                                            <label for="fakturPajak">Faktur pajak*</label>
                                            <input type="text" id="fakturPajak" name="fakturPajak" value="{{ $header->faktur_pajak }}" class="form-control" required />
                                        </div> --}}
                                    {{-- </div> --}}
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
                    @include('invoice.headerColumn')

                    <div class="" id="articleRow" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                    </div>

                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px" hidden>
                        <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                    </div>
                    <div class="d-flex justify-content-between align-items-end mt-75 ml-75">
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
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalAmount" value="{{ $header->grand_total>0 ?  number_format($header->grand_total,2) : 0 }}"disabled />
                                    <input type="hidden" class="form-control text-right font-weight-bold" id="totalAmountJasa"/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPN" class="col-sm-4 col-form-label titik-dua">PPN <span id="nilaiPPN"></span> </label>
                                <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="vatCheck" name="vatCheck" {{ $header->total_ppn >0 ? 'checked' : '' }} />
                                        <label class="custom-control-label" for="vatCheck"></label>
                                    </div>
                                </div>    
                                <div class="col-sm-5">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" oninput='inputDecimal(this)' id="totalPPN"  name="totalPPN" value="{{ $header->total_ppn>0 ? number_format($header->total_ppn,2) : 0 }}"  {{ $header->total_ppn > 0 ? '' : 'disabled' }} {{ $header->total_ppn > 0 ? 'required' : '' }}/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalPPH" class="col-sm-4 col-form-label titik-dua">PPH23 <span id="nilaiPPH"></span> </label>
                                <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="pph23Check" name="pph23Check" name="vatCheck" {{ $header->total_pph >0 ? 'checked' : '' }}/>
                                        <label class="custom-control-label" for="pph23Check"></label>
                                    </div>
                                </div> 
                                <div class="col-sm-5">
                                    <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" oninput='inputDecimal(this)' id="totalPPH" name="totalPPH" value="{{ $header->total_pph>0 ? number_format($header->total_pph,2) : 0 }}"  {{ $header->total_pph > 0 ? '' : 'disabled' }} {{ $header->total_pph > 0 ? 'required' : '' }}/>
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalNetto" class="col-sm-4 col-form-label titik-dua tanpa-padding">Netto</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right numeral-mask-digit font-weight-bold" id="totalNetto" value="{{ $header->grand_total>0 ? $header->grand_total : 0 }}" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>
                    <div class="form-row">
                        <div class="col-md-12">
                            <a href="{{ route('invoice.index') }}" class="btn btn-light">Back</a>
                            @if( $approveValidate ? $approveValidate[0]->validate : '')
                                <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                            {{-- @if( $status =='DRAFT') --}}
                                <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" >Update</button>
                            {{-- @endif --}}
                            @else
                                {{-- @if( !$approveValidate && $status =='DRAFT') --}}
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" >Update</button>
                                {{-- @endif --}}
                            @endif

                            {{-- @if( $status =='APPROVED')
                                <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting" >Posting</button>
                            @endif --}}
                        </div>
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
                    {{-- <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('invoice.index') }}" class="btn btn-light">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusInv =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" >Update</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusInv =='NEW')
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" >Update</button>
                                        @endif
                                    @endif
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
                    </div> --}}
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
    sNilaiPPN = "{{ $header->ppn }}";
    console.log(`Nilai PPN : ${sNilaiPPN}%`);
    
    $(document).ready(function(){
        validateFormToast('frmAdd');
        let detail = {!!  $detail !!};
        let summary = {!!  $summary !!};
        
        for (let i = 0; i < detail.length; i++) {
            article=detail[i].article_code;
            articleCode=detail[i].article_alternative_code;
            articleDesc=detail[i].article_desc;
            qtySo=detail[i].qty;
            uomGroup=detail[i].uom_group;
            uom=detail[i].uom;
            price=detail[i].price;
            priceService=detail[i].price_service;
            soCode=detail[i].so_number;
            dnNumberData=detail[i].dn_number;
            poNumber=detail[i].po_number;
            add_new_row(article,articleCode,articleDesc,qtySo,uomGroup,uom,price,priceService,soCode,dnNumberData,poNumber);
        }

        if(summary.length > 0 ){
            for (let a = 0; a < summary.length; a++) {
                article=summary[a].article_code;
                articleCode=summary[a].article_alternative_code;
                articleDesc=summary[a].article_desc;
                qtyDn=summary[a].qty;
                uomGroup=summary[a].uom_group;
                uom=summary[a].uom;
                price=summary[a].price;
                priceService=summary[a].price_service;
                soCode=summary[a].so_number;
                add_new_row_summary(article,articleCode,articleDesc,qtyDn,uomGroup,uom,price,priceService,soCode);
            }
        }

        // $('#totalPPH').attr('disabled','disabled');
        hitungTotal();
        edit='true';
        showDetail='false';
        let soNumber = '{{ $soNumber }}';
        searchDn(soNumber);
        $("#nilaiPPN").text(sNilaiPPN+'%');
        $("#nilaiPPH").text(sNilaiPPH+'%');

    });

    const approveBtn = document.querySelector('#cmdApprove');

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            let invNumber = $('#invNumber').val();
            approve(invNumber,'cmdApprove');
        },{ once:true});
    }

    approve = (invNumber,objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "POST",
            url: "{{ route('invoice.approve') }}",
            data: {
                invNumber:invNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#invNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#invNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');      
                    window.location.reload();                 
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }

    const invDate = $('#invDate');
    if (invDate.length) {
        invDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today"
        });
    }

    const sendingDate = $('#sendingDate');
    if (sendingDate.length) {
        sendingDate.flatpickr({
            dateFormat: "d-m-Y",
            // maxDate: "today"
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
                let customer = $('#customer').val()
                let soNumber = $('#soNumber').val()
                let dnNumber = $('#dnNumber').val()
                let ppn = $('#ppn').val().replace(/,/gi, '');
                let pph23 = $('#pph23').val().replace(/,/gi, '');
                let totalPpn = $('#totalPPN').val().replace(/,/gi, '') || 0;
                let totalPph = $('#totalPPH').val().replace(/,/gi, '') || 0;
                let invNumber = $('#invNumber').val();
                let note = $('#note').val();
                let fakturPajak =$('#fakturPajak').val();
                let totalAmount = $('#totalAmount').val().replace(/,/gi, '') || 0;
                let grandTotal = $('#totalNetto').val().replace(/,/gi, '') || 0;
                let sendingDate = $('#sendingDate').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('invoice.update') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        invNumber:invNumber,
                        invDate:invDate,
                        customer:customer,
                        ppn:ppn,
                        pph23:pph23,
                        totalPpn:totalPpn,
                        totalPph:totalPph,
                        note:note,
                        soNumber:soNumber,
                        dnNumber:dnNumber,
                        fakturPajak:fakturPajak,
                        totalAmount:totalAmount,
                        grandTotal:grandTotal,
                        sendingDate:sendingDate
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#invNumber').attr('disabled','disabled');
                            $('#totalAmount').attr('disabled','disabled');
                            $('#customer').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#invNumber').val(data.invNumber);
                            $('#totalAmount').attr('disabled','disabled');
                            $('#invNumber').attr('disabled','disabled');
                            $('#customer').attr('disabled','disabled');
                            $('#totalPPN').attr('disabled','disabled');
                            $('#totalPPH').attr('disabled','disabled');
                            // $('#cmdSave').attr('disabled','disabled');
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
           
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection