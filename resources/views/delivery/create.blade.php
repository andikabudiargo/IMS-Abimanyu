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
                    <h4 class="card-title">Status: <span id="statusText"></span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                            <input type="hidden" id="idDn" name="idDn" class="form-control" />
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
                                    <input type="text" id="dnNumber" name="dnNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="dnDate">Delivery Date*</label>
                                    <input type="text" id="dnDate" name="dnDate" class="form-control" placeholder="DD-MM-YYYY" required />
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="poNumberHdr">PO Number</label>
                                    <input type="text" id="poNumberHdr" name="poNumberHdr" class="form-control" disabled />
                                </div>                          
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="customer">Customer*</label>
                                    <select class="select2 form-control" id="customer" name="customer" required>
                                        <option value=""></option>
                                        @foreach($customers as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="soNumber">SO Number*</label>
                                    <select class="select2 form-control" id="soNumber" name="soNumber" required>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="osNumber">OS/JTC</label>
                                    <input type="text" id="osNumber" name="osNumber" class="form-control" />
                                </div>  
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-9">
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
                    @include('delivery.headerColumn')
                    <input type="text" id ="last_row_number" class="d-none" value="0">
                    <form id="articleRowFrm" name="articleRowFrm" autocomplete="off">
                    <div class="" id="article_row" style="max-height: 18rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                    </div>
                    </form>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua tanpa-padding">Row(s)</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalRow" disabled />
                                </div>
                            </div>
                            <div class="form-group row mb-03">
                                <label for="totalQTY" class="col-sm-4 col-form-label titik-dua tanpa-padding">Total QTY</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalQTY" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="form-row">
                        <div class="col-12">
                            {{-- <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button> --}}
                            {{-- <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button> --}}
                            <a href="{{ route('delivery.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                            <button class="btn btn-dark" type="button" id="cmdPrint" name="cmdPrint">Print</button>
                            {{-- @can('receiving-posting') --}}
                                {{-- <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button> --}}
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
@include('delivery.addArticle')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    let fromEdit = 'false';
    let lockedAt = "{{ $lockDate }}";
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $("#totalRow").val(0);
        $("#totalQTY").val(humanizeNumber(0));
        $('#statusText').text('New');
        $('#dnDate').val(currentDate);
        $('#cmdSave').show();
        // $('#cmdPosting').hide();
        $('#cmdPrint').hide();
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

    checkBeforeSave = () => {
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $("#cmdSave").attr('disabled','disabled');
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
                            // "qty":qty,
                            // "uom":articleUom,
                            // "so_number":articleSoCode,
                            // "po_number":poNumber,
                            // "qty_so":qtySo
                        });
                    }

                    if (parseInt(qty) > parseInt(qtySo)){
                        pesan +="Items "+ articleDesc +"-"+qty+"-"+qtySo+" QTY Delivery is higher than QTY SO<br>"; 
                        flag=1;
                    }
                    
                }
            });

            if (articles.length == 0){
                pesan +="Articles must be filled in completely <br>"; 
                flag=1;
            }

            if (flag==0){

                // let dnDate = $('#dnDate').val();
                // let customer = $('#customer').val();
                // let soNumber = $('#soNumber').val();
                // let poNumber = $('#soNumber').find(":selected").data("po-number");
                // let note = $('#note').val();
                // let osNumber = $('#osNumber').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('delivery.preStore') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        // dnDate:dnDate,
                        // customer:customer,
                        // soNumber:soNumber,
                        // poNumber:poNumber,
                        // note:note,
                        // osNumber:osNumber

                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#dnNumber').attr('disabled','disabled');
                            $('#cmdSave').removeAttr('disabled');
                        }else{

                            Swal.fire({
                                width: 1050,
                                title: '<strong>Terdapat Article serupa yang belum dikirim di SO Berikut:</strong>',
                                icon: 'info',
                                html: data.table,
                                showDenyButton: true,
                                denyButtonText: 'Batal',
                                confirmButtonText: 'Lanjutkan Save',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    saveData();
                                    // Swal.fire('Saved!', '', 'success')
                                } else if (result.isDenied) {
                                    $('#cmdSave').removeAttr('disabled');
                                    // Swal.fire('Changes are not saved', '', 'info')
                                }
                            })
                            
                            // show_msg(data.title, data.message, data.alert);
                            // $('#dnNumber').val(data.dnNumber);
                            // $('#statusText').val('NEW');
                            // $('#idDn').val(data.id);
                            // $('#dnNumber').attr('disabled','disabled');
                            // // $('#cmdSave').attr('disabled','disabled');
                            // $('#cmdSave').hide();
                            // $('#cmdPrint').show();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });

            }else{
                $('#cmdSave').removeAttr('disabled');
                $('#cmdPrint').hide();
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    }

    saveData = () => {
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $("#cmdSave").attr('disabled','disabled');
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

                    if (parseInt(qty) > parseInt(qtySo)){
                        pesan +="Items "+ articleDesc +"-"+qty+"-"+qtySo+" QTY Delivery is higher than QTY SO<br>"; 
                        flag=1;
                    }
                    
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
                let note = $('#note').val();
                let osNumber = $('#osNumber').val();

                $.ajax({
                    type: "post",
                    url: "{{ route('delivery.store') }}",
                    data: {
                        articles:JSON.stringify(articles),
                        dnDate:dnDate,
                        customer:customer,
                        soNumber:soNumber,
                        poNumber:poNumber,
                        note:note,
                        osNumber:osNumber

                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#dnNumber').attr('disabled','disabled');
                            $('#cmdSave').removeAttr('disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#dnNumber').val(data.dnNumber);
                            $('#statusText').val('NEW');
                            $('#idDn').val(data.id);
                            $('#dnNumber').attr('disabled','disabled');
                            // $('#cmdSave').attr('disabled','disabled');
                            $('#cmdSave').hide();
                            $('#cmdPrint').show();
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });

            }else{
                $('#cmdSave').removeAttr('disabled');
                $('#cmdPrint').hide();
                Swal.fire('Warning..',pesan,'warning');
            }
        }
    }

    $("#cmdSave").click(function(){

        checkBeforeSave();

        // if (!$("#frmAdd")[0].checkValidity()){
        //     $("#frmAdd").submit();
        // }else{ 
        //     $("#cmdSave").attr('disabled','disabled');
        //     $('.disabled-el').removeAttr('disabled');
        //     let objQtySo= $('#article_row input[name="qtySo[]"]');
        //     let objQty= $('#article_row input[name="qtyInv[]"]');
        //     let objUom= $('#article_row span[name="uom[]"]'); 
        //     let articles = []; 
        //     let flag=0; 
        //     let pesan="";

        //     $("#article_row input[name='articleId[]']").map(function(i) {  
        //         let $this=$(this);
        //         if ($this.val()){
        //             let articleCode = $this.data("code");
        //             let articleDesc = $this.data("desc");
        //             let articleUom = $this.data("uom");
        //             let articleSoCode = $this.data("so-code");
        //             let poNumber = $this.data("po-number");
        //             let qty=objQty.eq(i).val().replace(/,/gi, '') || 0;
        //             let qtySo=objQtySo.eq(i).val().replace(/,/gi, '') || 0;
                    
        //             if ((articleCode!=='') && (qty> 0)){
        //                 articles.push({
        //                     "article_code":articleCode,
        //                     "qty":qty,
        //                     "uom":articleUom,
        //                     "so_number":articleSoCode,
        //                     "po_number":poNumber,
        //                     "qty_so":qtySo
        //                 });
        //             }

        //             if (parseInt(qty) > parseInt(qtySo)){
        //                 pesan +="Items "+ articleDesc +"-"+qty+"-"+qtySo+" QTY Delivery is higher than QTY SO<br>"; 
        //                 flag=1;
        //             }
                    
        //         }
        //     });

        //     if (articles.length == 0){
        //         pesan +="Articles must be filled in completely <br>"; 
        //         flag=1;
        //     }

        //     if (flag==0){

        //         let dnDate = $('#dnDate').val();
        //         let customer = $('#customer').val();
        //         let soNumber = $('#soNumber').val();
        //         let poNumber = $('#soNumber').find(":selected").data("po-number");
        //         let note = $('#note').val();
        //         let osNumber = $('#osNumber').val();

        //         $.ajax({
        //             type: "post",
        //             url: "{{ route('delivery.store') }}",
        //             data: {
        //                 articles:JSON.stringify(articles),
        //                 dnDate:dnDate,
        //                 customer:customer,
        //                 soNumber:soNumber,
        //                 poNumber:poNumber,
        //                 note:note,
        //                 osNumber:osNumber

        //             },
        //             dataType: "json",
        //             success: function(data) {
        //                 if (data.status == 0 ){
        //                     for(let i = 0; i < data.message.length; i++) {
        //                         show_msg(data.title, data.message[i], data.alert);
        //                     }
        //                     $('#dnNumber').attr('disabled','disabled');
        //                     $('#cmdSave').removeAttr('disabled');
        //                 }else{
        //                     show_msg(data.title, data.message, data.alert);
        //                     $('#dnNumber').val(data.dnNumber);
        //                     $('#statusText').val('NEW');
        //                     $('#idDn').val(data.id);
        //                     $('#dnNumber').attr('disabled','disabled');
        //                     // $('#cmdSave').attr('disabled','disabled');
        //                     $('#cmdSave').hide();
        //                     $('#cmdPrint').show();
        //                 }
        //             },
        //             error: function(error) {
        //                 console.log(error);
        //             }
        //         });

        //     }else{
        //         $('#cmdSave').removeAttr('disabled');
        //         $('#cmdPrint').hide();
        //         Swal.fire('Warning..',pesan,'warning');
        //     }
        // }

    });

    $("#cmdPrint").click(function(){
        /* Posting langdung print*/
        $("#cmdPrint").attr('disabled','disabled');
        $('#cmdPrint').hide();
        $('#cmdSave').hide();
        let objQty= $('input[name="qtyInv[]"]');
        let objUom= $('select[name="uom[]"]');       
        let dnNumber = $('#dnNumber').val();   
        let idDn = $('#idDn').val();
        let dariNew = 'true';     
        $.ajax({
            type: "post",
            url: "{{ route('delivery.posting') }}",
            data: {
                dnNumber:dnNumber,
                id:idDn,
                dariNew:dariNew
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#dnNumber').attr('disabled','disabled');
                    $('#cmdSave').show();
                    $('#cmdPrint').hide();

                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#cmdSave').hide();
                    // $('#deleteButton').hide();
                    $('#statusText').text('POSTED');
                    $('#cmdPrint').hide();
                    $('#dnNumber').attr('disabled','disabled');
                    $('#soNumber').attr('disabled','disabled');
                    $('#customer').attr('disabled','disabled');
                    $('#dnDate').attr('disabled','disabled');
                    objQty.attr('disabled','disabled');
                    objUom.attr('disabled','disabled');

                    let id = data.idKu;
                    let url = "{{ route('delivery.print', ['id'=>':id']) }}";
                    url = url.replace('%3Aid', id);
                    window.open(url, '_blank');
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