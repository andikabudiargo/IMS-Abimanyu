@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText"></span></h4>
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
                                    <label for="apNumber">Invoice Number</label> <small class="text-muted"> automatic</small>
                                    <input type="text" id="apNumber" name="apNumber" class="form-control text-hitam disabled-el"  disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="supplier">Supplier</label>
                                    <select class="select2 form-control" id="supplier" name="supplier">
                                        <option value="">All</option>
                                        @foreach($supps as $val)
                                            <option value="{{$val->kode}}" >{{$val->kode}} - {{$val->nama}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="poNumber">PO Number</label>
                                    <select class="select2 form-control" id="poNumber" name="poNumber">
                                    </select>
                                </div>
                                {{-- <div class="form-group col-md-2">
                                    <label for="poDate">PO Date</label>
                                    <input type="text" id="poDate" name="poDate" class="form-control" placeholder="DD-MM-YYYY" disabled/>
                                </div> --}}
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
                                    <input type="text" id="suppCode" name="suppCode" class="form-control" />
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="poNumberDet">PO Number</label>
                                    <input type="text" id="poNumberDet" name="poNumberDet" class="form-control"/>
                                </div>       
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="recDate">Receive Date</label>
                                    <input type="text" id="recDate" name="recDate" class="form-control disabled-el" placeholder="DD-MM-YYYY" disabled/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="dueDate">Due Date</label>
                                    <input type="text" id="dueDate" name="dueDate" class="form-control disabled-el" placeholder="DD-MM-YYYY" disabled/>
                                </div>       
                            </div>
                                    
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="totalPO">Total PO</label>
                                    <input type="text" id="totalPO" name="totalPO" class="form-control numeral-mask text-right disabled-el" disabled/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="balance">Balance</label>
                                    <input type="text" id="balance" name="balance" class="form-control numeral-mask text-right disabled-el" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="invoiceNumber">Invoice Number</label>
                                    <input type="text" id="invoiceNumber" name="invoiceNumber" class="form-control" />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="invoiceDate">Invoice Date</label>
                                    <input type="text" id="invoiceDate" name="invoiceDate" class="form-control" placeholder="DD-MM-YYYY" />
                                </div> 
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="taxInvoiceNumber">Tax Invoice Number</label>
                                    <input type="text" id="taxInvoiceNumber" name="taxInvoiceNumber" class="form-control" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="basisAmount">Basis Amount</label>
                                    <input type="text" id="basisAmount" name="basisAmount" class="form-control numeral-mask text-right" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="vat">VAT</label>
                                    <input type="text" id="vat" name="vat" class="form-control numeral-mask text-right" />
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
                            <div class="form-row d-none d-flex align-items-end" id="tipePPH23">
                                <div class="form-group col-md-3">
                                    <label for="pph23">PPH 23</label>
                                    <input type="text" id="pph23" name="pph23" class="form-control numeral-mask text-right" />
                                </div>
                                <div class="col-md-3">
                                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                        <label class="btn btn-outline-primary active">
                                            <input type="radio" name="radio_options" id="sewa" checked /> Sewa
                                        </label>
                                        <label class="btn btn-outline-primary">
                                            <input type="radio" name="radio_options" id="jasa" /> Jasa
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="otherDeduct">Other Deductions</label>
                                    <input type="text" id="otherDeduct" name="otherDeduct" class="form-control numeral-mask text-right" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="grandTotal">Total</label>
                                    <input type="text" id="grandTotal" name="grandTotal" class="form-control numeral-mask text-right" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-12">
                                    <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                    @can('receiving-posting')
                                        <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                                    @endcan
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label for="currency">Currency*</label>
                                    <select class="select2 form-control" id="currency" name="currency" required>
                                        @foreach($currency as $val)
                                        <option value="{{$val}}">{{$val}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="rate">Rate</label>
                                    <input type="text" id="rate" name="rate" class="form-control"/>
                                </div>  
                            </div>                         
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="group">COA</label>
                                    <select class="select2 w-100" id="group" name="group">
                                        <option value="">All</option>
                                        @foreach($accounts as $val)
                                            <option value="{{ $val->account }}">{{ $val->account}} - {{ $val->description }}</option>
                                        @endforeach
                                    </select>
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
    // show_msg(data.title, data.message[i], data.alert);
    $(document).ready(function(){
        validateFormToast("frmAdd");
        $('#statusText').text('New');
        $('#invoiceDate').val(currentDate);
        $('#cmdSave').show();
        $('#cmdPosting').hide();
    });

    $("#pph23Check").change(function() {
        if(this.checked) {
            $('#tipePPH23').removeClass("d-none");
        }else{
            $('#tipePPH23').toggleClass("d-none");
        }
    });

    $("#basisAmount,#vat,#pph23,#otherDeduct").keyup(function(){
        let ba = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
        let vat = parseInt($('#vat').val().replace(/,/gi, '')) || 0;
        let pph23 = parseInt($('#pph23').val().replace(/,/gi, '')) || 0;
        let od = parseInt($('#otherDeduct').val().replace(/,/gi, '')) || 0;
        let total = (ba+vat+pph23)-od;
        console.log(total);
        $('#grandTotal').val(humanizeNumber(total));
    })
    
    invoiceDate = $('#invoiceDate');
    if (invoiceDate.length) {
        invoiceDate.flatpickr({
            dateFormat: "d-m-Y"
        });
    }

    invDate = $('#invDate');
    if (invDate.length) {
        invDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today"
        });
    }

    doDate = $('#doDate');
    if (doDate.length) {
        doDate.flatpickr({
            dateFormat: "d-m-Y",
            maxDate: "today"
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
                $('#'+obj).val('').trigger('change');
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
                $('#'+obj).val('').trigger('change');
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
                    

                    let ba = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
                    let vat = parseInt($('#vat').val().replace(/,/gi, '')) || 0;
                    let pph23 = parseInt($('#pph23').val().replace(/,/gi, '')) || 0;
                    let od = parseInt($('#otherDeduct').val().replace(/,/gi, '')) || 0;
                    let total = (ba+vat+pph23)-od;

                    $('#grandTotal').val(humanizeNumber(total));

                    mask_thousand();
                },
                error: function (response) {
                    //Error here
                    Swal.fire("Warning","Get list SJ failed","warning");
                }
            })
        }
    });
        
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection