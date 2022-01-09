@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="bs-stepper wizard-modern tab-disbursement">
        <div class="bs-stepper-header">
            <div class="step" data-target="#filterData">
                <button type="button" class="step-trigger">
                    <span class="bs-stepper-box">
                        <i data-feather="file-text" class="font-medium-3"></i>
                    </span>
                    <span class="bs-stepper-label">
                        <span class="bs-stepper-title">Filter data</span>
                        <span class="bs-stepper-subtitle">Filter data by Supplier</span>
                    </span>
                </button>
            </div>
            <div class="line">
                <i data-feather="chevron-right" class="font-medium-2"></i>
            </div>
            <div class="step" data-target="#listPayment">
                <button type="button" class="step-trigger">
                    <span class="bs-stepper-box">
                        <i data-feather="user" class="font-medium-3"></i>
                    </span>
                    <span class="bs-stepper-label">
                        <span class="bs-stepper-title">List Payment</span>
                        <span class="bs-stepper-subtitle">List invoice to be payment</span>
                    </span>
                </button>
            </div>
        </div>
        <div class="bs-stepper-content">
            <div id="filterData" class="content">
                <div class="content-header">
                    <h5 class="mb-0">Filter data</h5>
                </div>
                <form>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label" for="supplier">Supplier</label>
                            <select class="select2 form-control" id="supplier" name="supplier">
                                <option value="">All</option>
                                @foreach($supps as $val)
                                    <option value="{{ $val->kode }}" >{{$val->kode}} - {{$val->nama}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="invDate">Invoice Date</label>
                            <input type="text" id="invDate" name="invDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
                        </div>

                        <div class="col-md-3 form-group">
                            <label for="dueDate">Due Date</label>
                            <input type="text" id="dueDate" name="dueDate" class="form-control flatpickr-range" placeholder="YYYY-MM-DD to YYYY-MM-DD" />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="col-md-12"> 
                            <button type="button" class="btn btn-primary" id ="cmdSubmitFilter" name="cmdSubmitFilter">Submit</button>
                        </div>
                    </div>
                    <div class="form-group col-md-12">
                        <div class="row" style="min-height:200px">
                            <div class="col-sm-12">
                            <div class="card-datatable table-responsive pt-0">
                                <table id="tblInvoiceList" class="table w-100">
                                <thead class="thead-light">
                                </thead>
                                </table>
                            </div>
                            </div>
                        </div>  
                    </div>
                </form> 
                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary btn-next" id="btnDetailPayment">
                        <span class="align-middle d-sm-inline-block d-none">Next</span>
                        <i data-feather="arrow-right" class="align-middle ml-sm-25 ml-0"></i>
                    </button>
                </div> 
            </div>
            <div id="listPayment" class="content">
                <div class="content-header">
                    <h5 class="mb-0">List Payment</h5>
                    <small></small>
                </div>
                <form>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="paymentDate">PaymentDate</label>
                            <input type="text" id="paymentDate" name="paymentDate" class="form-control" placeholder="DD-MM-YYYY" required/>
                        </div> 
                    </div>
                </form>
                <div class="row">
                    <div class="form-group col-md-12">
                        <div class="row">
                            <div class="col-sm-12">
                            <div class="card-datatable table-responsive pt-0">
                                <table id="tblApList" class="table w-100">
                                <thead class="thead-light">
                                </thead>
                                </table>
                            </div>
                            </div>
                        </div>  
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button class="btn btn-primary btn-prev">
                        <i data-feather="arrow-left" class="align-middle mr-sm-25 mr-0"></i>
                        <span class="align-middle d-sm-inline-block d-none">Previous</span>
                    </button>
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
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');

    var bsStepper = document.querySelectorAll('.bs-stepper')
        ,tabDisbursement = document.querySelector('.tab-disbursement');
    if (typeof bsStepper !== undefined && bsStepper !== null) {
        for (var el = 0; el < bsStepper.length; ++el) {
        bsStepper[el].addEventListener('show.bs-stepper', function (event) {
            var index = event.detail.indexStep;
            var numberOfSteps = $(event.target).find('.step').length - 1;
            var line = $(event.target).find('.step');

            for (var i = 0; i < index; i++) {
            line[i].classList.add('crossed');

            for (var j = index; j < numberOfSteps; j++) {
                line[j].classList.remove('crossed');
            }
            }
            if (event.detail.to == 0) {
            for (var k = index; k < numberOfSteps; k++) {
                line[k].classList.remove('crossed');
            }
            line[0].classList.remove('crossed');
            }
        });
        }
    }

    if (typeof tabDisbursement !== undefined && tabDisbursement !== null) {
        var modernStepper = new Stepper(tabDisbursement, {
            linear: false
        });
        $(tabDisbursement)
        .find('.btn-next')
        .on('click', function () {
            modernStepper.next();
        });
        $(tabDisbursement)
        .find('.btn-prev')
        .on('click', function () {
            modernStepper.previous();
        });

    }

    $(document).ready(function(){
        $('#paymentDate').val(currentDate);
    });

    rangePickr = $('.flatpickr-range');
    if (rangePickr.length) {
        rangePickr.flatpickr({
        dateFormat: "d-m-Y",
        mode: 'range'
        });
    }

    paymentDate = $('#paymentDate');
    if (paymentDate.length) {
        paymentDate.flatpickr({
            dateFormat: "d-m-Y",
            minDate: "today"
        });
    }

    $("#cmdSubmitFilter").click(function(e){
        let isidata = $('#tblInvoiceList tr').length;
        // alert(isidata);
        if (isidata >0){
            let table= $('#tblInvoiceList').DataTable();
            table.destroy();
            $('#tblInvoiceList tbody > tr').remove();
            let celli="";
            $("#tblInvoiceList thead > tr").remove();
        }

        let invDate = $("#invDate").val();
        let dueDate = $("#dueDate").val();
        let supplier = $("#supplier").val();

        $(function(){
            var oTable =$("#tblInvoiceList").DataTable({
                ajax:{
                    url:'{{ route("disbursement.list.invoice")}}',
                    data:{
                        invDate:invDate,
                        dueDate:dueDate,
                        supplier:supplier
                    }
                },
                processing: true,
                serverSide: true,
                buttons: true,
                dom: '<"d-flex w-100"<l><"#mydiv.d-flex ml-auto text-right"f>>tips',
                lengthMenu: [
                [ 10, 25, 50, -1 ],
                [ '10', '25', '50', 'all' ]
                ],
                language: {
                    paginate: {
                        // remove previous & next text from pagination
                        previous: '&nbsp;',
                        next: '&nbsp;'
                    }
                },
                columnDefs: [
                    { width: '10%', targets: 0 },
                    { className: 'text-right','targets': [ 6,7,8,9,10 ] }
                ],
                drawCallback: function( settings ) {
                    feather.replace({
                            width: 14,
                            height: 14
                    });
                },
                order: [[ 0, 'asc' ]],
                bDestroy: true, //pakai ini supaya bisa di load berulang2
                // scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
                select: {
                    style: 'multi',
                    selector: 'td:first-child'
                },
                initComplete: function(settings, json) {
                    let api = new $.fn.dataTable.Api(settings);
                    let header = api.column(0).header();
                    $(header).html('<input id="selectAll" name="selectAll" value="1" type="checkbox">');
                    $("#selectAll").on( "click", function(e) {
                        if ($(this).is( ":checked" )) {
                            $(".select-checkbox").each(function() {
                                this.checked=true;
                            });
                            // oTable.rows(  ).select();        
                        } else {
                            $(".select-checkbox").each(function() {
                                this.checked=false;
                            });
                            // oTable.rows(  ).deselect(); 
                        }
                    });
                },
                columns: [
                    { data: 'select_orders', name: 'select_orders',title: 'Check', searchable: false, orderable: false },
                    { data: 'supplier_id', name: 'supplier_id',title:'Supplier' },
                    { data: 'inv_number', name: 'inv_number',title:'Invoice Number' },
                    { data: 'inv_date', name: 'inv_date',title:'Invoice Date' },
                    { data: 'rec_date', name: 'rec_date',title:'Receipt Date' },
                    { data: 'due_date', name: 'due_date',title:'Due Date' },
                    { data: 'basis_amount', name: 'basis_amount',title:'Basis Amount',render: $.fn.dataTable.render.number(',','.') },
                    { data: 'vat', name: 'vat',title:'VAT',render: $.fn.dataTable.render.number(',','.') },
                    { data: 'pph23', name: 'pph23',title:'PPH23',render: $.fn.dataTable.render.number(',','.') },
                    { data: 'other_deduction', name: 'other_deduction',title:'Other Deduction',render: $.fn.dataTable.render.number(',','.') },
                    { data: 'total', name: 'total',title:'Total',render: $.fn.dataTable.render.number(',','.') },

                ],
            });
        });
        

    });

    $("#btnDetailPayment").click(function(e){        
        let apNumber = "";
        $('input[name="apCheck[]"]').each(function () {
            if (this.checked)
            apNumber += $(this).val() + ',';
        });
        detaiPayment(apNumber);
    });

    function detaiPayment(apNumber) {
        let isidata = $('#tblAptList tr').length;
        if (isidata >0){
            let table= $('#tblAptList').DataTable();
            table.destroy();
            $('#tblAptList tbody > tr').remove();
            let celli="";
            $("#tblAptList thead > tr").remove();
        }
        $(function(){
            let aTable =$("#tblAptList").DataTable({
                ajax: {
                    url:'{{ route("deliveryPlan.listArticle")}}',
                    data: {
                        apNumber:apNumber
                    }
                },
                processing: true,
                serverSide: true,
                buttons: true,
                dom: '<"d-flex w-100"<l><"#mydiv.d-flex ml-auto text-right"f>>tips',
                lengthMenu: [
                [ 10, 25, 50, -1 ],
                [ '10', '25', '50', 'all' ]
                ],
                language: {
                paginate: {
                    // remove previous & next text from pagination
                    previous: '&nbsp;',
                    next: '&nbsp;'
                }
                },
                columnDefs: [
                    { width: '10%', targets: 0 },
                    { className: 'text-right','targets': [ 4 ] },
                ],
                drawCallback: function( settings ) {
                feather.replace({
                        width: 14,
                        height: 14
                });
                },
                order: [[ 0, 'asc' ]],
                bDestroy: true, //pakai ini supaya bisa di load berulang2
                // scrollX: true, //pakai ini supaya waktu responsive  bisa di scroll horizontal
                select: {
                    style: 'multi',
                    selector: 'td:first-child'
                },
                initComplete: function(settings, json) {
                    let api = new $.fn.dataTable.Api(settings);
                    let header = api.column(0).header();
                    $(header).html('<input id="selectAllArticle" name="selectAllArticle" value="1" type="checkbox">');
                    $("#selectAllArticle").on( "click", function(e) {
                        if ($(this).is( ":checked" )) {
                            $(".select-checkbox-article").each(function() {
                                this.checked=true;
                            });
                            // aTable.rows(  ).select();        
                        } else {
                            $(".select-checkbox-article").each(function() {
                                this.checked=false;
                            });
                            // aTable.rows(  ).deselect(); 
                        }
                    });
                },
                columns: [
                    { data: 'select_orders', name: 'select_orders',title: 'Check', searchable: false, orderable: false },
                    { data: 'supplier_id', name: 'supplier_id',title:'Supplier' },
                    { data: 'inv_number', name: 'inv_number',title:'Invoice Number' },
                    { data: 'inv_date', name: 'inv_date',title:'Invoice Date' },
                    { data: 'rec_date', name: 'rec_date',title:'Receipt Date' },
                    { data: 'due_date', name: 'due_date',title:'Due Date' },
                    { data: 'total', name: 'total',title:'Total',render: $.fn.dataTable.render.number(',','.') },
                ],
            });
        });

    }
   
    hitungTotal = () => {
        let ba = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
        let vat = parseInt($('#vat').val().replace(/,/gi, '')) || 0;
        let pph23 = parseInt($('#pph23').val().replace(/,/gi, '')) || 0;
        let od = parseInt($('#otherDeduct').val().replace(/,/gi, '')) || 0;
        let total = ba? (ba+vat+pph23)-od : '';
        console.log(total);
        $('#grandTotal').val(total);
        mask_thousand();
    }

    $("#basisAmount,#vat,#pph23,#otherDeduct").keyup(function(){
        hitungTotal();
    })
         
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel").click(function(){
        reloadPage();
    });

    $("#cmdNew").click(function(){
        reloadPage();
    });
  

    $("#cmdPosting").click(function(){        
        let piNumber = $('#piNumber').val();            
        $.ajax({
            type: "post",
            url: "{{ route('apProforma.posting') }}",
            data: {
                piNumber:piNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    show_msg(data.title, data.message, data.alert);
                    $('#piNumber').attr('disabled','disabled');
                    $('#cmdSave').show();
                    // $('#cmdPosting').hide();
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#statusText').text(data.statusAp);
                    $('#piNumber').attr('disabled','disabled');                    
                    $('#cmdPosting').hide();
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