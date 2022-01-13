@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ Session::get('status') ? Session::get('status'): $status }}</span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form id="frmAdd" name="frmAdd" action="{{ route('apProforma.store') }}" method="post" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="piNumber">Proforma Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="piNumber" name="piNumber" class="form-control text-hitam disabled-el" value="{{ old('piNumber', Session::get('details') ? Session::get('details')->pi_number :"") }}" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="invoiceDate">Proforma Invoice Date</label>
                                    <input type="text" id="invoiceDate" name="invoiceDate" class="form-control" value="{{ old('invoiceDate',Session::get('details') ? Session::get('details')->inv_date :'') }}" placeholder="DD-MM-YYYY" required/>
                                </div> 
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier</label>
                                    <select class="select2 form-control" id="supplier" name="supplier">
                                        <option value="">All</option>
                                        @foreach($supps as $val)
                                            <option value="{{ $val->kode }}" {{ old('supplier',Session::get('details') ? Session::get('details')->supplier_id :"") == $val->kode ? 'selected' : '' }} >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="poNumber">PO Number</label>
                                    <select class="select2 form-control" id="poNumber" name="poNumber">
                                    </select>
                                </div>
                            </div>
                            <div class="form-row d-none">                                    
                                <div class="form-group col-md-6">
                                    <label for="suppCode">Supplier</label>
                                    <input type="text" id="suppCode" name="suppCode" class="form-control disabled-el" value="{{ old('suppCode') }}" disabled />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="poNumberDet">PO Number</label>
                                    <input type="text" id="poNumberDet" name="poNumberDet" class="form-control disabled-el" value="{{ old('poNumberDet') }}" disabled required/>
                                </div>       
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="totalPO">Total PO</label>
                                    <input type="text" id="totalPO" name="totalPO" class="form-control numeral-mask text-right disabled-el" value="{{ old('totalPO') }}" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency">
                                        @foreach($currency as $val)
                                        <option value="{{$val}}" {{ old('currency',Session::get('details') ? Session::get('details')->currency : '' ) == $val ? 'selected' : '' }} >{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="rate">Rate</label>
                                    <input type="text" id="rate" name="rate" value="{{ old('rate',Session::get('details') ? Session::get('details')->kurs :'') }}" class="form-control numeral-mask text-right"/>
                                </div>  
                            </div>                         
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="basisAmount">Basis Amount</label>
                                    <input type="text" id="basisAmount" name="basisAmount" class="form-control numeral-mask text-right" value="{{ old('basisAmount',Session::get('details') ? Session::get('details')->basis_amount : '') }}" required/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="vat">VAT</label>
                                    <input type="text" id="vat" name="vat" class="form-control numeral-mask text-right" value="{{ old('vat',Session::get('details') ? Session::get('details')->vat : '') }}" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="pph23Check" name="pph23Check" {{ old('pph23',Session::get('details') ? Session::get('details')->pph23 : '') ? 'checked' : '' }} />
                                        <label class="custom-control-label" for="pph23Check">PPH23</label>
                                    </div>
                                </div>
                            </div>
                            <div class="{{ Session::get('details') ? Session::get('details')->pph23 ? '' : 'd-none' :'d-none' }} " id="tipePPH23">
                                <div class="form-row d-flex align-items-end">
                                    <div class="form-group col-md-3">
                                        <label for="pph23">PPH 23</label>
                                        <input type="text" id="pph23" name="pph23" class="form-control numeral-mask text-right" value="{{ old('pph23',Session::get('details') ? Session::get('details')->pph23 : '') }}" />
                                    </div>
                                    <div class="form-group col-md-6">
                                        <div class="demo-inline-spacing">
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="sewa" name="pph23Type" value="sewa" class="custom-control-input" {{ old('pph23Type',Session::get('details') ? Session::get('details')->pph23_type : '') == 'sewa' ? 'checked' : '' }} checked />
                                                <label class="custom-control-label" for="sewa">Sewa</label>
                                            </div>
                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="jasa" name="pph23Type" value="jasa" class="custom-control-input" {{ old('pph23Type',Session::get('details') ? Session::get('details')->pph23_type : '') == 'jasa' ? 'checked' : '' }} />
                                                <label class="custom-control-label" for="jasa">Jasa</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="otherDeduct">Other Deductions</label>
                                    <input type="text" id="otherDeduct" name="otherDeduct" class="form-control numeral-mask text-right" value="{{ old('otherDeduct',Session::get('details') ? Session::get('details')->other_deduction : '') }}" />
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
                                    <select class="select2 form-control w-100" id="account" name="account">
                                        <option value="">Choose option</option>
                                        @foreach($accounts as $val)
                                            <option value="{{ $val->account }}" {{ old('account',Session::get('details') ? Session::get('details')->account : '') == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <br>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    @if( Session::get('status') != 'Saved' )
                                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                    @endif
                                    @can('ap-posting')
                                        @if( Session::get('status') == 'Saved' )
                                            <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                                        @endif
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
    let poAda;
    let status ="{{ Session::get('status') ? Session::get('status'): '' }}";
    // show_msg(data.title, data.message[i], data.alert);
    $(document).ready(function(){
        validateFormToast("frmAdd");
        let errors = "{{ $errors }}";
        errors=errors.replace(/[{[\]}]/g,'');
        errors=errors.replace(/&quot;/g,'').split(",");
        $.each(errors, function(key, value) {
            if (value)
            show_msg("Validasi Form", value, "warning");
        });

        let supplierAda = "{{ Session::get('details') ? Session::get('details')->supplier_id :"" }}";
        poAda = "{{ Session::get('details') ? Session::get('details')->po_number :"" }}";
        
        if(supplierAda){
            $('#supplier').val(supplierAda).trigger('change');
        }

        $('#invoiceDate').val(currentDate);
        mask_thousand();
    });

    $("#pph23Check").change(function() {
        if(this.checked) {
            let basisAmount = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#pph23").val(basisAmount * 0.2);
            mask_thousand();
            $("#tipePPH23").removeClass("d-none");
        }else{
            $("#pph23").val(0);
            $("#sewa").prop("checked", true);
            $("#tipePPH23").toggleClass("d-none");
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

    function reloadPage(){
        window.location.reload();
    }

    $("#cmdCancel").click(function(){
        reloadPage();
    });

    $("#cmdNew").click(function(){
        reloadPage();
    });

    kosongkanData = () =>{

        $('#piNumber').val("");
        $('#invoiceDate').val("");
        $('#supplier').val("").trigger("change");
        $('#poNumber').val("");
        $('#suppCode').val("");
        $('#poNumberDet').val("");
        $('#totalPO').val("");
        $('#currency').val("");
        $('#rate').val("");
        $('#basisAmount').val("");
        $('#pph23Check').prop('checked', false);
        $("#pph23").val(0);
        $("#sewa").prop("checked", true);
        $("#tipePPH23").toggleClass("d-none");
        $('#otherDeduct').val("");
        $('#grandTotal').val("");
        $('#account').val("").trigger("change");
        
        hitungTotal();
    }

    $('#supplier').change(function(){
        let value= $(this).val();
        let obj = 'poNumber';
        kosongkanData();
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
        let poNumber = $(this).val();
        if(poNumber){
            $.ajax({
                url:"{{ route('apProforma.po.detail') }}",
                method:"GET",
                data:{
                    poNumber:poNumber,
                },
                success:function(result){
                    $('#poNumberDet').val(poNumber);
                    $('#totalPO').val(result[0].total_po);
                    $('#basisAmount').val(result[0].total_po);
                    $('#vat').val(result[0].ppn);
                    $('#currency').val(result[0].currency).trigger('change');
                    $('#rate').val(result[0].kurs);
                    hitungTotal();
                    $('#basisAmount').focus();
                },
                error: function (response) {
                    Swal.fire("Warning","Get po detail failed","warning");
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