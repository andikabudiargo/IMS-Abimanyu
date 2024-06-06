@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusDel }}</span></h4>
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
                                <div class="form-group col-md-3">
                                    <label for="dnNumber">Delivery Note Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="dnNumber" name="dnNumber" class="form-control text-hitam disabled-el" value="{{ $header->delivery_number }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="dnDate">Delivery Date*</label>
                                    <input type="text" id="dnDate" name="dnDate" class="form-control disabled-el" placeholder="DD-MM-YYYY" value="{{ $header->delivery_date }}" required />
                                </div>   
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="poNumberHdr">PO Number</label>
                                    <input type="text" id="poNumberHdr" name="poNumberHdr" class="form-control disabled-el" value="{{ $header->po_number }}" disabled />
                                </div>                            
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <select class="select2 form-control" id="customer" name="customer" required>
                                        @foreach($customers as $val)
                                            <option value="{{$val->kode}}" {{$val->kode == $header->customer_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="soNumber">SO Number*</label>
                                    <select class="select2 form-control" id="soNumber" name="soNumber" required>
                                    </select>
                                    {{-- <input type="text" id="soNumber" name="soNumber" class="form-control" value="{{ $header->so_number }}" data-po-number="{{ $header->po_number }}" required disabled/> --}}
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-9">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-9">
                                    <label class="form-label" for="note">Revision reason</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ $header->reason }}</textarea>
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
                    @include('delivery.headerColumn')
                    <form id="articleRowFrm" name="articleRowFrm" autocomplete="off">
                        <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                            <input type="text" id ="last_row_number" class="d-none" value="{{ count($detail) }}">
                        </div>
                    </form>
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
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-md-12">
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('delivery.index') }}" class="btn btn-light">Back</a>
                                    @if( $approveValidate ? $approveValidate[0]->validate : '')
                                        <input type="text" id ="approveLevel" name ="approveLevel" class="d-none" value="{{ $approveValidate[0]->next_level }}">
                                        <input type="text" id ="maxLevel" name ="maxLevel" class="d-none" value="{{ $approveValidate[0]->max_level }}">
                                        <button class="btn btn-success" type="button" id="cmdApprove" name="cmdApprove">Approve</button>
                                        @if( $statusDel =='REVISI')
                                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave" >Update</button>
                                        @endif
                                    @else
                                        @if( !$approveValidate && $statusDel =='REVISI')
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('partials.delete-modal')
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
@include('delivery.addArticle')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    let fromEdit = 'true';
    let lockedAt = "{{ $lockDate }}";
    const approveBtn = document.querySelector('#cmdApprove');

    if (approveBtn) {
        approveBtn.addEventListener('click',() =>{
            let dnNumber = $('#dnNumber').val();
            approve(dnNumber,'cmdApprove');
        },{ once:true});
    }

    approve = (dnNumber,objButton) => {
        $('#'+objButton).attr('disabled','disabled');
        $.ajax({
            type: "POST",
            url: "{{ route('delivery.approve') }}",
            data: {
                dnNumber:dnNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#dnNumber').attr('disabled','disabled');
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#dnNumber').attr('disabled','disabled');
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

    $(document).ready(function(){    
        searchSo('soNumber',"{!! $header->customer_id !!}","{!! $header->so_number !!}");
        setTimeout(() => {
            fromEdit = 'false';
        }, 10000);

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
            qtyDel = detail[i].qty;
            uomGroup =  detail[i].uom_group;
            uom = detail[i].uom;
            soCode = detail[i].so_number;
            poNumber = detail[i].po_number;
            qtySo = detail[i].qty_so;
            add_new_row_edit(article,articleCode,articleDesc,qtyDel,uomGroup,uom,soCode,poNumber,qtySo);
        }

        // supaya yang sisa SO nya juga keluar datanya bukan hanya yang di delivery saja
        let detailSo = {!! $detailSo !!};
        for(let i=0;i<detailSo.length;i++){
            article = detailSo[i].article_code;
            articleCode = detailSo[i].article_alternative_code;
            articleDesc = detailSo[i].article_desc;
            qtyDel = 0;
            uomGroup =  detailSo[i].uom_group;
            uom = detailSo[i].uom;
            soCode = detailSo[i].so_number;
            poNumber = detailSo[i].po_number;
            qtySo = detailSo[i].qty_so;
            add_new_row_edit(article,articleCode,articleDesc,qtyDel,uomGroup,uom,soCode,poNumber,qtySo);
        }
        
    });

    dnDate = $('#dnDate');
    if (dnDate.length) {
        dnDate.flatpickr({
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
            $('.disabled-el').removeAttr('disabled');
            let objQtySo= $('#article_row input[name="qtySo[]"]');
            let objQty= $('#article_row input[name="qtyInv[]"]');
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
                    let poNumber = $this.data("po-number");
                    let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
                    let qtySo=objQtySo.eq(i).val().replace(/,/gi, '') || 0;
                    
                    if ((articleCode!=='') && (qty> 0)){
                        articles.push({
                            "article_code":articleCode,
                            "qty":qty,
                            "uom":articleUom,
                            "so_number":articleSoCode,
                            "po_number":poNumber,
                            "qty_so":qtySo
                        });
                    }

                    // console.log(articles);
                    // if (qty == 0){
                    //     pesan +="QTY of items "+ articleDesc +" cannot be 0 <br>"; 
                    //     flag=1;
                    // }
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){

                let dnDate = $('#dnDate').val();
                let customer = $('#customer').val();
                let soNumber = $('#soNumber').val();
                let poNumber = $('#soNumber').find(":selected").data("po-number");
                // let poNumber = $('#poNumberHdr').val();
                let note = $('#note').val();
                let dnNumber = $('#dnNumber').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('delivery.update') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        dnDate:dnDate,
                        customer:customer,
                        soNumber:soNumber,
                        poNumber:poNumber,
                        dnNumber:dnNumber,
                        note:note
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#dnNumber').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#dnNumber').val(data.dnNumber);
                            $('#dnNumber').attr('disabled','disabled');
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