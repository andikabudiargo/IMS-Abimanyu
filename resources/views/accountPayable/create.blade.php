@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="accountPayable-create">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12">
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
                        <form id="frmAdd" name="frmAdd" action="{{ route('ap.store') }}" method="post" autocomplete="off">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-row">
                                        <div class="form-group col-md-9">
                                            <label for="apNumber">AP Number</label> <small class="text-muted"> automatic</small>
                                            <input type="text" id="apNumber" name="apNumber" class="form-control text-hitam disabled-el" value="{{ old('apNumber') }}" disabled />
                                            <input type="hidden" id="recNumberSave" name="recNumberSave" class="form-control text-hitam disabled-el" value="" />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="apDate">Receive AP*</label>
                                            <input type="text" id="apDate" name="apDate" class="form-control" value="{{ old('apDate') }}" placeholder="DD-MM-YYYY" />
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-10">
                                            <label class="form-label" for="supplier">Supplier*</label>
                                            <select class="select2 form-control" id="supplier" name="supplier" required>
                                                <option value="">All</option>
                                                @foreach($supps as $val)
                                                    <option value="{{ $val->kode }}" data-term = "{{ $val->top_batas_1 }}" {{ old('supplier') == $val->kode ? 'selected' : '' }} >{{$val->kode}} - {{$val->nama}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="term">Term*</label>
                                            <input type="text" id="term" name="term" class="form-control" value="{{ old('term') }}" disabled />
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-8">
                                            <label class="form-label" for="poNumber">PO Number*</label>
                                            <select class="select2 form-control" id="poNumber" name="poNumber" required>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="currency">Currency*</label>
                                            <select class="select2 form-control" id="currency" name="currency" required>
                                                @foreach($currency as $val)
                                                <option value="{{$val}}" {{ old('currency') == $val ? 'selected' : '' }} >{{$val}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="rate">Rate*</label>
                                            <input type="text" id="rate" name="rate" value="{{ old('rate') }}" class="form-control numeral-mask text-right" required/>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="accountBasisA">COA Basis Amount*</label>
                                            <select class="select2 form-control w-100" id="accountBasisA" name="accountBasisA" required>
                                                <option value="">Choose option</option>
                                                @foreach($accountBa as $val)
                                                    <option value="{{ $val->account }}" {{ old('account') == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="invoiceNumber">Invoice Number*</label>
                                            <input type="text" id="invoiceNumber" name="invoiceNumber" class="form-control" value="" required/>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="invoiceDate">Invoice Date</label>
                                            <input type="text" id="invoiceDate" name="invoiceDate" class="form-control" placeholder="DD-MM-YYYY" value="" required/>
                                        </div> 
                                        <div class="form-group col-md-5">
                                            <label for="taxInvoiceNumber">Tax Invoice Number</label>
                                            <input type="text" id="taxInvoiceNumber" name="taxInvoiceNumber" class="form-control" value="" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="note">Notes</label>
                                            <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('note') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-row">
                                        <div class="col-sm-12">
                                            <p class="mb-0">List Rec.Number/LPB*</p>
                                            <div class="card-datatable table-responsive pt-0">
                                                <table class="table table-bordered" id="listOfLpb">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" width="10%">Check</th>
                                                            <th scope="col" width="30%">LPB Number</th>
                                                            <th scope="col" width="30%">Date</th>
                                                            <th scope="col" width="30%">DO Number</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <button class="btn btn-primary" type="button" id="cmdSubmit" name="cmdSubmit">Submit</button>
                                </div>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="col-sm-12">
                                    <p class="mb-0">Detail receiving</p>
                                    <div class="card-datatable table-responsive pt-0">
                                      <table class="table table-bordered" id="listOfRec">
                                          <thead>
                                            <tr>
                                                <th scope="col" width="20%">Article Code</th>
                                                <th scope="col" width="40%">Description</th>
                                                <th scope="col" width="10%">UOM</th>
                                                <th scope="col" width="10%">Qty</th>
                                                <th scope="col" width="10%">Price</th>
                                                <th scope="col" width="10%">Total</th>
                                            </tr>
                                          </thead>
                                          <tbody>
                                          </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-end mt-75">
                                <div class="col-md-8"></div>
                                <div class="col-md-4">
                                    <div class="form-group row mb-03">
                                        <label for="basisAmount" class="col-sm-4 col-form-label titik-dua">DPP</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold disabled-el" id="basisAmount" name="basisAmount" disabled />
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03 d-none">
                                        <label for="totalPPN" class="col-sm-4 col-form-label titik-dua">Discount </label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" oninput='inputDecimal(this)' id="totalDiscount" name="totalDiscount" />
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
                                        <label for="totalPPH23" class="col-sm-4 col-form-label titik-dua">PPH23 <span id="nilaiPPH23"></span> </label>
                                        <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="pph23Check" name="pph23Check" />
                                                <label class="custom-control-label" for="pph23Check"></label>
                                            </div>
                                        </div> 
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" oninput='inputDecimal(this)' id="totalPPH23" name="totalPPH23" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPH21" class="col-sm-4 col-form-label titik-dua">PPH21 <span id="nilaiPPH21"></span> </label>
                                        <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="pph21Check" name="pph21Check" />
                                                <label class="custom-control-label" for="pph21Check"></label>
                                            </div>
                                        </div> 
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" oninput='inputDecimal(this)' id="totalPPH21" name="totalPPH21" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPH42" class="col-sm-4 col-form-label titik-dua">PPH4(2) <span id="nilaiPPH42"></span> </label>
                                        <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="pph42Check" name="pph42Check" />
                                                <label class="custom-control-label" for="pph42Check"></label>
                                            </div>
                                        </div> 
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" oninput='inputDecimal(this)' id="totalPPH42" name="totalPPH42" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="grandTotal" class="col-sm-4 col-form-label titik-dua">Total</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="grandTotal" name="grandTotal" disabled/>
                                            <input type="hidden" class="form-control text-right font-weight-bold" id="grandTotalQty" name="grandTotalQty" disabled/>
                                        </div>
                                    </div>
                                </div>
                            </div>                           
                            <br>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('aps.index') }}" class="btn btn-light">Back</a>
                                    <button class="btn btn-info" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                    @if( Session::get('status') != 'Saved' )
                                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Save</button>
                                        {{-- <button class="btn btn-dark" type="button" id="cmdSavePrint" name="cmdSavePrint">Save&Print</button> --}}
                                    @endif
                                    {{-- @can('ap-posting')
                                        @if( Session::get('status') == 'Saved' )
                                            <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                                        @endif
                                    @endcan --}}
                                </div>
                            </div>
                        </form>
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
@include('accountPayable.script')
<script type="text/javascript">    
    $(document).ready(function(){
        validateFormToast("frmAdd");
        // let supplierAda = "{{ Session::get('details') ? Session::get('details')->supplier_id :"" }}";
        // let poAda = "{{ Session::get('details') ? Session::get('details')->po_number :"" }}";
        // if(supplierAda){
        //     $('#supplier').val(supplierAda).trigger('change');
        // }
        $('#cmdSubmit').attr('disabled','disabled');
        $('#apDate').val(currentDate);
        mask_thousand();
        showDetail='false';
        edit='false';
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
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#statusText').text(data.statusAp);
                    $('#apNumber').attr('disabled','disabled');                    
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