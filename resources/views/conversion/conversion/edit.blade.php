@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    {{-- <h4 class="card-title">Status: New</h4> --}}
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
                                    <label for="cNumber">Conversion Number</label>
                                    <input type="text" id="cNumber" name="cNumber" class="form-control" value="{{ $header->conversion_code }}" disabled/>
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="cValue">Conversion Value</label>
                                    <input type="text" id="cValue" name="cValue"  value="{{ number_format($conversionVal) }}" class="form-control numeral-mask-digit text-right" 
                                    data-toggle="tooltip" 
                                    data-placement="bottom" 
                                    title="Ubah nilai konversi di menu conversion -> setting"
                                    readonly/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label for="cName">Conversion Name*</label>
                                    <input type="text" id="cName" name="cName" class="form-control" value="{{ $header->conversion_name }}" required/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-7">
                                    <label class="form-label" for="cNote">Notes</label>
                                    <textarea type="text" id="cNote" name="cNote" class="form-control" rows="1" >{{ $header->note }}</textarea>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="customerCode">Customer</label>
                                    <select class="select2 form-control disabled-el" id="customerCode" name="customerCode" >
                                        <option value=""></option>
                                        @foreach($customers as $val)
                                        <option value="{{ $val->kode }}">{{ $val->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="deliveryDate">Delivery Date</label>
                                    <input type="text" id="deliveryDate" name="deliveryDate" class="form-control flatpickr-range" placeholder="DD-MM-YYYY" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button class="btn btn-primary" type="button" id="cmdSubmit" name="cmdSubmit">Submit</button>
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
                    <h4 class="card-title">Detail data</h4>
                </div>
                <div class="card-body" >
                    <div class="col-12">
                        <div class="card-datatable table-responsive pt-0">
                            <table id="listTable" class="display" style="width:100%">
                                <thead>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">List Article</h4>
                </div>
                <div class="card-body" >
                    <form id="frmDeliveryNote" name="frmDeliveryNote" autocomplete="off">
                        <div class="form-group col-md-4" style="padding-left:0">
                            <label for="deliveryNumber">Delivery Note</label>
                            <select class="select2 form-control disabled-el" id="deliveryNumber" name="deliveryNumber" >
                            </select>
                        </div>
                    </form>
                    <hr>
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('conversion.conversion.headerColumn')
                            <div class="" id="item_row" style="max-height: 25rem;overflow-x: hidden;scrollbar-width: thin;margin-top:7px">
                                <input type="text" id ="last_row_number" class="d-none" value="0">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-5">
                            <div class="form-group row mb-03">
                                <label for="totalConversion" class="col-sm-5 col-form-label titik-dua">Total harga konversi</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control text-right font-weight-bold" id="totalConversion" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('conversion.index') }}" class="btn btn-light">Back</a>
                            <button class="btn btn-info" type="reset" id="cmdNew" name="cmdCancel">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('conversion.conversion.addArticle')
@endsection
@section('styles')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/jquery-ui.css') }}">
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

    td.nopadding{
        padding-right:0px;
        padding-left:0px;
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
<script src="{{ asset('assets/js/ui.1.13.0.jquery-ui.js') }}"></script>
<script type="text/javascript">
    const dnSelect = $("#deliveryNumber")
    const deliveryDate = $('#deliveryDate');
    let currentDate = todayDate('dd-mm-yyyy');
    let inEdit = 'true';
    let inShow = 'false';

    // if (deliveryDate.length) {
    //  deliveryDate.flatpickr({
    //         dateFormat: "d-m-Y",
    //         // maxDate: "today"
    //     });
    // }

    rangePickr = $('.flatpickr-range');
    if (rangePickr.length) {
        rangePickr.flatpickr({
            dateFormat: "d-m-Y",
            mode: 'range'
        });
    }

    $(document).ready(function(){           
        validateFormToast('frmAdd');
        let details = {!! $details !!};
        for(let i=0;i<details.length;i++){
            add_new_row(details[i].delivery_number,details[i].customer_id,details[i].customer_name,details[i].artikel_code,details[i].article_description,details[i].purchase_price,details[i].selling_price,details[i].conversion_total);
        }

        feather.replace({
            width: 14,
            height: 14
        });    

        // $("#totalConversion").val('');
        mask_thousand_digit(2);
    });

    $("#cmdSubmit").click(function(){
        let customerCode = $('#customerCode').val();
        let deliveryDate = $('#deliveryDate').val();
        let pesan = "";
        let flag = 0;

        if (customerCode && !deliveryDate){
            pesan += "Delivery Date harus diisi<br>";
            flag = 1;
        }

        // if (!customerCode && !deliveryDate){
        //     pesan += "Customer dan Delivery Date harus diisi<br>";
        //     flag = 1;
        // }

        if (flag == 0){
            $.ajax({
                type: "get",
                url: "{{ route('conversion.get.dn') }}",
                data: {
                    customerCode:customerCode,
                    deliveryDate:deliveryDate
                },
                success: function(data) {
                    $('#deliveryNumber').html(data);
                    $("#deliveryNumber").val("").trigger('change');
                    disabledEnabledSelect2()
                },
                error: function(error) {
                    console.log(error);     
                }
            });
        }else{
            show_msg('Warning', pesan, 'warning');
        }

    });
    
    dnSelect.change(function(e){        
        let dnNumber = $(this).val();
        changeSelectDn(dnNumber);
    });

    function changeSelectDn(dnNumber) {
        if (dnNumber){
            $.ajax({
                url:"{{route('conversion.get.list.article')}}",
                method:"POST",
                data:{
                    dnNumber:dnNumber,
                },
                success:function(result){
                    // console.log(result.data)
                    for(let i=0;i<result.data.length;i++){
                        add_new_row(result.data[i].delivery_number,result.data[i].customer_id,result.data[i].customer_name,result.data[i].artikel_code,result.data[i].article_description);
                    }
                    disabledEnabledSelect2();
                    dnSelect.val("").trigger("change");
                }
            });
        }
    }

    function disabledEnabledSelect2(){
        let arrValueSelected = $("#item_row input[name='aDnNumber[]']").map(function(){return $(this).val();}).get();
        arrValueSelected = Array.from(new Set(arrValueSelected));
        dnSelect.find("option").removeAttr('disabled',true).trigger("chosen:updated");
        arrValueSelected.forEach((key, index) => {
            dnSelect.find("option[value='" + key + "']").attr('disabled',true).trigger("chosen:updated");
        });
    }
       
    $("#cmdSave").click(function(){
        if (!$("#frmAdd")[0].checkValidity()){
            $("#frmAdd").submit();
        }else{ 
            $('.disabled-el').removeAttr('disabled');
            let flag = 0;
            let pesan ='';
            let customerCode = $('#customerCode').val();
            let cName = $('#cName').val();
            let cNumber = $('#cNumber').val();
            let cNote = $('#cNote').val();
                    
            if (cName == ''){
                pesan +="Nama konversi harus diisi<br>";
                flag = '1';
            }

            // if (customerCode== ''){
            //     pesan +="Customer harus diisi<br>";
            //     flag = '1';
            // }

            let objPurchasePrice = $('#item_row input[name="aPurchasePrice[]"]')
            let objSellligPrice = $('#item_row input[name="aSellingPrice[]"]')
            let objConversion = $('#cValue').val()
            let objArticleCode = $('#item_row input[name="aArticleCode[]"]')
            let objArticleDescription = $('#item_row input[name="aArticleDescription[]"]')
            let objDnNumber = $('#item_row input[name="aDnNumber[]"]')
            let objCustomerCode = $('#item_row input[name="aCustomerCode[]"]')
            let objConversionTotal = $('#item_row input[name="aConversion[]"]')
            let details = []
        
            
            $("#item_row input[name='aArticleCode[]']").map(function(i) {  
                let $this=$(this);
                if ($this.val()){
                    let aDnNumber = objDnNumber.eq(i).val();
                    let aSellingPrice = objSellligPrice.eq(i).val().replace(/,/gi, '') || 0
                    let aPurchasePrice = objPurchasePrice.eq(i).val().replace(/,/gi, '') || 0
                    let aConversion = objConversion.replace(/,/gi, '') || 0
                    let aConversionTotal = objConversionTotal.eq(i).val().replace(/,/gi, '') || 0
                    let aArticleCode = objArticleCode.eq(i).val()
                    let aCustomerCode = objCustomerCode.eq(i).val()

                    if ( aSellingPrice == 0 ) {
                        pesan +=`Selling price ${objArticleDescription.val()}, cannot be empty ! <br>`; 
                        flag=1;
                    }

                    details.push({
                        'dn_number':aDnNumber,
                        'customer_id':aCustomerCode,
                        'article_code':aArticleCode,
                        'purchase_price':aPurchasePrice,
                        'selling_price':aSellingPrice,
                        'conversion':aConversion,
                        'conversion_total':aConversionTotal
                    })
                }
            })

            if (details.length == 0){
                    pesan +="Articles must be filled in completely <br>"; 
                    flag=1;
            }

            if (flag == 0){
                $.ajax({
                    type: "post",
                    url: "{{ route('conversion.store') }}",
                    data: {
                        details:JSON.stringify(details),
                        cNumber:cNumber,
                        cName:cName,
                        cNote:cNote
                    },
                    dataType: "json",
                    success: function(data) {
                        if (data.status == 0 ){
                            let message="";
                            for(let i = 0; i < data.message.length; i++) {
                                show_msg(data.title, data.message[i], data.alert);
                            }
                            $('#cNumber').attr('disabled','disabled');
                            $('.disabled-el').attr('disabled','disabled');
                        }else{
                            show_msg(data.title, data.message, data.alert);
                            $('#cNumber').val(data.cNumber);
                            $('.disabled-el').attr('disabled','disabled');
                            window.location.reload();
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
    })

    $("#cmdNew").click(function(){
        window.location.reload();
    });
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection