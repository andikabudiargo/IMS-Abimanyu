@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="basic-tabs-components">
    <div class="row match-height">
        <div class="col-xl-12 col-lg-12">
            <div class="card">
                <div class="card-header">
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="main-tab" data-toggle="tab" href="#main" aria-controls="main" role="tab" aria-selected="true">Main</a>
                        </li>
                        @foreach($sub_details as $key =>$sub_detail )
                            <li class="nav-item">
                                <a class="nav-link" id="profile-tab" data-toggle="tab" href="#rev{{ $key+1 }}" aria-controls="revisi{{ $key+1 }}" role="tab" aria-selected="false">Revision {{ $key+1 }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="main" aria-labelledby="main-tab" role="tabpanel">
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
                                                <div class="form-group col-md-3">
                                                    <label for="profInvoice">Prof Invoice</label>
                                                    <input type="text" id="profInvoice" name="profInvoice" class="form-control text-hitam disabled-el" value="{{ old('profInvoice', Session::get('details') ? Session::get('details')->proforma_inv_number :"") }}" disabled />
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label class="form-label" for="supplier">Supplier</label>
                                                    <select class="select2 form-control text-hitam disabled-el" id="supplier" name="supplier" disabled>
                                                        <option value="">All</option>
                                                        @foreach($supps as $val)
                                                            <option value="{{ $val->kode }}" {{ old('supplier',$details ? $details->supplier_id:"") == $val->kode ? 'selected' : '' }} >{{$val->kode}} - {{$val->nama}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-3">
                                                    <label class="form-label" for="poNumber">PO Number</label>
                                                    <select class="select2 form-control text-hitam disabled-el" id="poNumber" name="poNumber" disabled>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label class="form-label" for="recNumber">Rec.Number/LPB</label>
                                                    <select class="select2 form-control text-hitam disabled-el" id="recNumber" name="recNumber" disabled>
                                                    </select>
                                                </div>
                                            </div>
                                            <hr>
                                            {{-- <h4>Detail invoice</h4> --}}
                                            <div class="form-row">                                    
                                                <div class="form-group col-md-6 d-none">
                                                    <label for="suppCode">Supplier</label>
                                                    <input type="text" id="suppCode" name="suppCode" class="form-control text-hitam disabled-el" value="{{ old('suppCode',$details ? $details->supplier_id :"") }}" disabled required />
                                                </div>
                                                <div class="form-group col-md-3 d-none">
                                                    <label for="poNumberDet">PO Number</label>
                                                    <input type="text" id="poNumberDet" name="poNumberDet" class="form-control text-hitam disabled-el" value="{{ old('poNumberDet',$details ? $details->po_number : "") }}" disabled required/>
                                                </div>       
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="totalPO">Total PO</label>
                                                    <input type="text" id="totalPO" name="totalPO" class="form-control numeral-mask text-right text-hitam disabled-el" value="{{ old('totalPO') }}" disabled/>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="balance">Balance</label>
                                                    <input type="text" id="balance" name="balance" class="form-control numeral-mask text-right text-hitam disabled-el" value="{{ old('balance') }}" disabled/>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="recDate">Receive Date</label>
                                                    <input type="text" id="recDate" name="recDate" class="form-control text-hitam disabled-el" value="{{ old('recDate',$details ? $details->rec_date : "") }}" placeholder="DD-MM-YYYY" disabled/>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="dueDate">Due Date</label>
                                                    <input type="text" id="dueDate" name="dueDate" class="form-control text-hitam disabled-el" value="{{ old('dueDate',$details ? $details->due_date : "") }}" placeholder="DD-MM-YYYY" disabled/>
                                                </div>       
                                            </div>
                                            <hr>
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="currency">Currency*</label>
                                                    <select class="select2 form-control" id="currency" name="currency">
                                                        @foreach($currency as $val)
                                                        <option value="{{$val}}" {{ old('currency',$details ? $details->currency : "" ) == $val ? 'selected' : '' }} >{{$val}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="rate">Rate</label>
                                                    <input type="text" id="rate" name="rate" class="form-control numeral-mask text-right"/>
                                                </div>  
                                            </div>                         
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="invoiceNumber">Invoice Number</label>
                                                    <input type="text" id="invoiceNumber" name="invoiceNumber" class="form-control" value="{{ old('invoiceNumber',$details ? $details->inv_number :"") }}" required/>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="invoiceDate">Invoice Date</label>
                                                    <input type="text" id="invoiceDate" name="invoiceDate" class="form-control" value="{{ old('invoiceDate',$details ? $details->inv_date :"") }}" placeholder="DD-MM-YYYY" required/>
                                                </div> 
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="taxInvoiceNumber">Tax Invoice Number</label>
                                                    <input type="text" id="taxInvoiceNumber" name="taxInvoiceNumber" class="form-control" value="{{ old('taxInvoiceNumber',$details ? $details->tax_inv_number : "") }}" />
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="basisAmount">Basis Amount</label>
                                                    <input type="text" id="basisAmount" name="basisAmount" class="form-control numeral-mask text-right" value="{{ old('basisAmount',$details ? $details->basis_amount : "") }}" required/>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label class="form-label" for="accountBasisA">COA</label>
                                                    <select class="select2 form-control w-100" id="accountBasisA" name="accountBasisA">
                                                        <option value="">Choose option</option>
                                                        @foreach($accountBa as $val)
                                                            <option value="{{ $val->account }}" {{ old('account',$details ? $details->account_ba : "") == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="vat">VAT</label>
                                                    <input type="text" id="vat" name="vat" class="form-control numeral-mask text-right" value="{{ old('vat',$details ? $details->vat : "") }}" />
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-3">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input" id="pph23Check" name="pph23Check" {{ $details->pph23 ? 'checked' : '' }} />
                                                        <label class="custom-control-label" for="pph23Check">PPH23</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="{{ $details->pph23 ? '' : 'd-none'  }}" id="tipePPH23">
                                                <div class="form-row d-flex align-items-end">
                                                    <div class="form-group col-md-3">
                                                        <label for="pph23">PPH23</label>
                                                        <input type="text" id="pph23" name="pph23" class="form-control numeral-mask text-right" value="{{ old('pph23',$details ? $details->pph23 : "") }}" />
                                                    </div>
                                                    <div class="form-group col-md-6">
                                                        <div class="demo-inline-spacing">
                                                            <div class="custom-control custom-radio">
                                                                <input type="radio" id="sewa" name="pph23Type" value="sewa" class="custom-control-input" {{ old('pph23Type',$details->pph23_type) == 'sewa' ? 'checked' : '' }} checked />
                                                                <label class="custom-control-label" for="sewa">Sewa</label>
                                                            </div>
                                                            <div class="custom-control custom-radio">
                                                                <input type="radio" id="jasa" name="pph23Type" value="jasa" class="custom-control-input" {{ old('pph23Type',$details->pph23_type) == 'jasa' ? 'checked' : '' }} />
                                                                <label class="custom-control-label" for="jasa">Jasa</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="otherDeduct">Other Deductions</label>
                                                    <input type="text" id="otherDeduct" name="otherDeduct" class="form-control numeral-mask text-right" value="{{ old('otherDeduct',$details ? $details->other_deduction : "") }}" />
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-2">
                                                    <label for="grandTotal">Total</label>
                                                    <input type="text" id="grandTotal" name="grandTotal" class="form-control numeral-mask text-right" value="{{ old('grandTotal', ($details->basis_amount+$details->vat)-($details->pph23 + $details->other_deduction )) }}" />
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label class="form-label" for="account">COA</label>
                                                    <select class="select2 w-100" id="account" name="account">
                                                        <option value="">Choose option</option>
                                                        @foreach($accounts as $val)
                                                            <option value="{{ $val->account }}" {{ old('account',$details ? $details->account : "") == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-5">
                                                    <label class="form-label" for="note">Notes</label>
                                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" {{ $statusRevision ? 'required' : '' }} >{{ old('note',$details ? $details->note : "") }}</textarea>
                                                </div>
                                            </div>
                                            <br>
                                            <div class="form-row">
                                                <div class="col-md-12">
                                                    <button class="btn btn-warning" type="reset" id="cmdCancel" name="cmdCancel">Cancel</button>
                                                    <button class="btn btn-success" type="reset" id="cmdNew" name="cmdCancel">New</button>
                                                    @if($details->status == '1' || $details->status =='2' )
                                                        <button class="btn btn-primary" type="button" id="cmdSave" name="cmdSave">Update</button>
                                                    @endif
                                                    @if( $details->status =='2' )
                                                        @can('ap-posting')
                                                            <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting">Posting</button>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @foreach($sub_details as $key =>$sub_detail )
                            <div class="tab-pane" id="rev{{ $key+1 }}" aria-labelledby="revison{{ $key+1 }}-tab" role="tabpanel">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Status: <span>Revision {{ $key+1 }}</span></h4>
                                        <div class="heading-elements">
                                            <ul class="list-inline mb-0">
                                                <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                                            </ul>
                                        </div>    
                                    </div>
                                    <div class="card-content collapse show">
                                        <div class="card-body">
                                            <form autocomplete="off">
                                                @csrf
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
                                                        <label>AP Number</label> <small class="text-muted"> automatic</small>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->ap_number }}" disabled />
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label for="profInvoice">Prof Invoice</label>
                                                        <input type="text" id="profInvoice" name="profInvoice" class="form-control text-hitam disabled-el" value="{{ $sub_detail->proforma_inv_number }}" disabled />
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label class="form-label">Supplier</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->supplier_id }} - {{ $sub_detail->nama }}" disabled />
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
                                                        <label class="form-label">PO Number</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->po_number }}" disabled />
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label class="form-label">Rec.Number / LPB</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->rec_number }}" disabled />
                                                    </div>
                                                </div>
                                                <hr>
                                                {{-- <h4>Detail invoice</h4> --}}
                                                <div class="form-row">                                    
                                                    <div class="form-group col-md-6 d-none">
                                                        <label>Supplier</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->supplier_id }} - {{ $sub_detail->nama }}" disabled />
                                                    </div>
                                                    <div class="form-group col-md-3 d-none">
                                                        <label>PO Number</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->po_number }}" disabled />
                                                    </div>       
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>Total PO</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->due_date }}" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-2">
                                                        <label>Balance</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->due_date }}" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-2">
                                                        <label>Receive Date</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->rec_date }}" placeholder="DD-MM-YYYY" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-2">
                                                        <label>Due Date</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->due_date }}" placeholder="DD-MM-YYYY" disabled/>
                                                    </div>       
                                                </div>
                                                <hr>
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>Currency*</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->currency }}" disabled />
                                                    </div>
                                                    <div class="form-group col-md-2">
                                                        <label>Rate</label>
                                                        <input type="text" class="form-control numeral-mask text-right  text-hitam" value="{{ $sub_detail->kurs }}" disabled/>
                                                    </div>  
                                                </div>                         
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>Invoice Number</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->inv_number }}" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-2">
                                                        <label>Invoice Date</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->inv_date }}" placeholder="DD-MM-YYYY" disabled/>
                                                    </div> 
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>Tax Invoice Number</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->tax_inv_number }}" disabled />
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>Basis Amount</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->basis_amount }}" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label>VAT</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->vat }}" disabled />
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" {{ $sub_detail->pph23 ? 'checked' : '' }} disabled />
                                                            <label class="custom-control-label">PPH23</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="{{ $sub_detail->pph23 ? '' : 'd-none'  }}">
                                                    <div class="form-row d-flex align-items-end">
                                                        <div class="form-group col-md-3">
                                                            <label>PPH 23</label>
                                                            <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->pph23 }}" disabled />
                                                        </div>
                                                        <div class="form-group col-md-6">
                                                            <div class="demo-inline-spacing">
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" id="sewa" name="pph23Type" value="sewa" class="custom-control-input" {{ $sub_detail->pph23_type == 'sewa' ? 'checked' : '' }} disabled />
                                                                    <label class="custom-control-label" for="sewa">Sewa</label>
                                                                </div>
                                                                <div class="custom-control custom-radio">
                                                                    <input type="radio" id="jasa" name="pph23Type" value="jasa" class="custom-control-input" {{ $sub_detail->pph23_type == 'jasa' ? 'checked' : '' }} disabled />
                                                                    <label class="custom-control-label" for="jasa">Jasa</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>Other Deductions</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->other_deduction }}" disabled/>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>Total</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ ($sub_detail->basis_amount+$sub_detail->vat+$sub_detail->pph23) - $sub_detail->other_deduction }}" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label class="form-label" for="account">COA</label>
                                                        <select class="select2 w-100" disabled>
                                                            <option value="">Choose option</option>
                                                            @foreach($accounts as $val)
                                                                <option value="{{ $val->account }}" {{ $sub_detail->account == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-5">
                                                        <label class="form-label" for="note">Notes</label>
                                                        <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $sub_detail->note }}</textarea>
                                                    </div>
                                                </div>
                                                <br>
                                            </form>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                        @endforeach
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
        $.each(errors, function(key, value) {
            if (value)
            show_msg("Validasi Form", value, "warning");
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
            let basisAmount = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
            $("#pph23").val(basisAmount * 0.2);
            mask_thousand();
            $("#tipePPH23").removeClass("d-none");
            hitungTotal();
        }else{
            $("#pph23").val(0);
            $("#sewa").prop("checked", true);
            $("#tipePPH23").toggleClass("d-none");
            hitungTotal();
        }
    });

    hitungTotal = () => {
        let ba = parseInt($('#basisAmount').val().replace(/,/gi, '')) || 0;
        let vat = parseInt($('#vat').val().replace(/,/gi, '')) || 0;
        let pph23 = parseInt($('#pph23').val().replace(/,/gi, '')) || 0;
        let od = parseInt($('#otherDeduct').val().replace(/,/gi, '')) || 0;
        let total = ba? (ba+vat+pph23)-od : '';
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
                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#statusText').text(data.statusAp);
                    $('#apNumber').attr('disabled','disabled');
                    $('#cmdPosting').hide();
                    $('#cmdSave').hide();
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