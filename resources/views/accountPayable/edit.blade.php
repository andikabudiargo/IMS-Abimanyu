@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusEdit }}</span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" action="{{ route('ap.update') }}" method="post" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="apNumber">AP Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="apNumber" name="apNumber" class="form-control text-hitam disabled-el" value="{{ old('apNumber', $details->ap_number) }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier</label>
                                    <select class="select2 form-control" id="supplier" name="supplier">
                                        <option value="">All</option>
                                        @foreach($supps as $val)
                                            <option value="{{ $val->kode }}" {{ old('supplier',$details ? $details->supplier_id:"") == $val->kode ? 'selected' : '' }} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="poNumber">PO Number</label>
                                    <select class="select2 form-control" id="poNumber" name="poNumber">
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="recNumber">Rec.Number / LPB</label>
                                    <select class="select2 form-control" id="recNumber" name="recNumber">
                                    </select>
                                </div>
                            </div>
                            <h4>Detail invoice</h4>
                            <div class="form-row">                                    
                                <div class="form-group col-md-6">
                                    <label for="suppCode">Supplier</label>
                                    <input type="text" id="suppCode" name="suppCode" class="form-control disabled-el" value="{{ old('suppCode',$details ? $details->supplier_id :"") }}" disabled required />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="poNumberDet">PO Number</label>
                                    <input type="text" id="poNumberDet" name="poNumberDet" class="form-control disabled-el" value="{{ old('poNumberDet',$details ? $details->po_number : "") }}" disabled required/>
                                </div>       
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="recDate">Receive Date</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control disabled-el" value="{{ old('recDate') }}" placeholder="DD-MM-YYYY" disabled/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="dueDate">Due Date</label>
                                    <input type="text" id="dueDate" name="dueDate" class="form-control disabled-el" value="{{ old('dueDate') }}" placeholder="DD-MM-YYYY" disabled/>
                                </div>       
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="totalPO">Total PO</label>
                                    <input type="text" id="totalPO" name="totalPO" class="form-control numeral-mask text-right disabled-el" value="{{ old('totalPO') }}" disabled/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="balance">Balance</label>
                                    <input type="text" id="balance" name="balance" class="form-control numeral-mask text-right disabled-el" value="{{ old('balance') }}" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency">
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{ old('currency',$details ? $details->currency : "" ) == $val ? 'selected' : '' }} >{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="rate">Rate</label>
                                    <input type="text" id="rate" name="rate" class="form-control numeral-mask text-right"/>
                                </div>  
                            </div>                         
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="invoiceNumber">Invoice Number</label>
                                    <input type="text" id="invoiceNumber" name="invoiceNumber" class="form-control" value="{{ old('invoiceNumber',$details ? $details->inv_number :"") }}" required/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="invoiceDate">Invoice Date</label>
                                    <input type="text" id="invoiceDate" name="invoiceDate" class="form-control" value="{{ old('invoiceDate',$details ? $details->inv_date :"") }}" placeholder="DD-MM-YYYY" required/>
                                </div> 
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="taxInvoiceNumber">Tax Invoice Number</label>
                                    <input type="text" id="taxInvoiceNumber" name="taxInvoiceNumber" class="form-control" value="{{ old('taxInvoiceNumber',$details ? $details->tax_inv_number : "") }}" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="basisAmount">Basis Amount</label>
                                    <input type="text" id="basisAmount" name="basisAmount" class="form-control numeral-mask text-right" value="{{ old('basisAmount',$details ? $details->basis_amount : "") }}" required/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="vat">VAT</label>
                                    <input type="text" id="vat" name="vat" class="form-control numeral-mask text-right" value="{{ old('vat',$details ? $details->vat : "") }}" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="pph23Check" name="pph23Check" {{ old('pph23Check') == 't' ? 'checked' : '' }} />
                                        <label class="custom-control-label" for="pph23Check">PPH23</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-none " id="tipePPH23">
                                <div class="form-row d-flex align-items-end">
                                    <div class="form-group col-md-3">
                                        <label for="pph23">PPH 23</label>
                                        <input type="text" id="pph23" name="pph23" class="form-control numeral-mask text-right" value="{{ old('pph23',$details ? $details->pph23 : "") }}" />
                                    </div>
                                    <div class="form-group col-md-3">
                                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                            <label class="btn btn-outline-primary active">
                                                <input type="radio" name="pph23Type" id="sewa" value="sewa" {{ old('pph23Type') == 'sewa' ? 'checked' : '' }}  /> Sewa
                                            </label>
                                            <label class="btn btn-outline-primary">
                                                <input type="radio" name="pph23Type" id="jasa" value="jasa" {{ old('pph23Type') == 'jasa' ? 'checked' : '' }} /> Jasa
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="otherDeduct">Other Deductions</label>
                                    <input type="text" id="otherDeduct" name="otherDeduct" class="form-control numeral-mask text-right" value="{{ old('otherDeduct',$details ? $details->other_deduction : "") }}" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="grandTotal">Total</label>
                                    <input type="text" id="grandTotal" name="grandTotal" class="form-control numeral-mask text-right" value="{{ old('grandTotal') }}" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="account">COA</label>
                                    <select class="select2 w-100" id="account" name="account">
                                        <option value="">Choose option</option>
                                        @foreach($accounts as $val)
                                            <option value="{{ $val->account }}" {{ old('account',$details ? $details->account : "") == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                    @can('ap-posting')
                                        <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                                    @endcan
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('receiving.addArticle')
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');
    let poAda;
    let recAda
    let status ="{{ Session::get('status') ? Session::get('status'): '' }}";

    $(document).ready(function(){
        validateFormToast("frmAdd");

        let errors = "{{ $errors }}";
        errors=errors.replace(/[{[\]}]/g,'');
        errors=errors.replace(/&quot;/g,'').split(",");
        alert = "warning";
        title = "Validasi Form";
        $.each(errors, function(key, value) {
            if (value)
            show_msg(title, value, alert);
        });

        let supplierAda = "{{ $details->supplier_id }}";
        poAda = "{{ $details->po_number }}";
        recAda = "{{ $details->rec_number }}";
        console.log(supplierAda);
        if(supplierAda){
            $('#supplier').val(supplierAda).trigger('change');
            $('#recNumber').val(recAda).trigger('change');
        }

        mask_thousand();

    });

    $("#pph23Check").change(function() {
        if(this.checked) {
            $('#tipePPH23').removeClass("d-none");
        }else{
            $('#tipePPH23').toggleClass("d-none");
        }
    });

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
    
    invoiceDate = $('#invoiceDate');
    if (invoiceDate.length) {
        invoiceDate.flatpickr({
            dateFormat: "d-m-Y"
        });
    }

    recDate = $('#recDate');
    if (recDate.length) {
        recDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today"
        });
    }

    dueDate = $('#dueDate');
    if (dueDate.length) {
        dueDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today"
        });
    }
    
    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel").click(function(){
        reloadPage();
    });

    $("#cmdNew").click(function(){
        reloadPage();
    });

    $('#supplier').change(function(){
        let value= $(this).val();
        let obj = 'poNumber';
        $.ajax({
            url:"{{ route('ap.list.po') }}",
            method:"GET",
            data:{
                value:value,
            },
            success:function(result){
                $('#'+obj).html(result);
                poAda ? $('#'+obj).val(poAda).trigger('change'):$('#'+obj).val('').trigger('change');
            },
            error: function (response) {
                //Error here
                Swal.fire("Warning","Get list PO failed","warning");
            }
        })
    });

    $('#poNumber').change(function(){
        let value = $(this).val();
        let poDate = $(this).find(":selected").data("po-date");
        let obj = 'recNumber';
        $('#poDate').val(poDate);
        $.ajax({
            url:"{{ route('ap.list.rec') }}",
            method:"GET",
            data:{
                value:value,
            },
            success:function(result){
                $('#'+obj).html(result);
                recAda ? $('#'+obj).val(recAda).trigger('change'):$('#'+obj).val('').trigger('change');
            },
            error: function (response) {
                //Error here
                Swal.fire("Warning","Get list Rec failed","warning");
            }
        })
    });
  
    $('#recNumber').change(function(){
        let poNumber= $('#poNumber').val();
        let recNumber = $(this).val();
        if(recNumber && poNumber){
            $.ajax({
                url:"{{ route('ap.detail.rec') }}",
                method:"GET",
                data:{
                    poNumber:poNumber,
                },
                success:function(result){
                    $('#poNumberDet').val(result[0].po_number);
                    $('#suppCode').val(result[0].nama);

                    $('#totalPO').val(result[0].total_po);
                    $('#basisAmount').val(result[0].basis_amount);
                    $('#vat').val(result[0].basis_amount*(result[0].vat/100));
                    $('#dueDate').val(result[0].due_date);
                    $('#recDate').val(result[0].rec_date);
                    $('#balance').val(result[0].po_balance);
                    if (status != 'Saved'){
                        $('#currency').val(result[0].currency).trigger('change');
                        $('#rate').val(result[0].kurs);
                    }
                    hitungTotal();
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list SJ failed","warning");
                }
            })
        }
    });

    $("#cmdSave").click(function(){     
        if (!$("#frmAdd")[0].checkValidity()){
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit();
        }else{
            $('.disabled-el').removeAttr('disabled');
            $("#frmAdd").submit();
        }
    });

    $("#cmdPosting").click(function(){        
        let apNumber = $('#apNumber').val();            
        $.ajax({
            type: "post",
            url: "{{ route('ap.posting') }}",
            data: {
                apNumber:apNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    show_msg(data.title, data.message, data.alert);
                    $('#apNumber').attr('disabled','disabled');
                    $('#cmdSave').show();
                    $('#cmdPosting').hide();

                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#statusText').text(data.statusAp);
                    $('#apNumber').attr('disabled','disabled');                    
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