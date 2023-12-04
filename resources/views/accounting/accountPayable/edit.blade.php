@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="accountPayable-edit">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12">
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
                        <form id="frmAdd" name="frmAdd"  method="post" autocomplete="off">
                            <input type="hidden" id="apId" name="apId" class="form-control text-hitam disabled-el" value="{{ Crypt::encryptString($id) }}" />
                            @csrf
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-row">
                                        <div class="form-group col-md-7">
                                            <label for="apNumber">AP Number</label> <small class="text-muted"> automatic</small>
                                            <input type="text" id="apNumber" name="apNumber" class="form-control text-hitam disabled-el" value="{{ $header->ap_number }}" disabled />
                                            <input type="hidden" id="recNumberSave" name="recNumberSave" class="form-control text-hitam disabled-el" value="{{ $recNumbers }}" />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="apDate">Receive AP*</label>
                                            <input type="text" id="apDate" name="apDate" class="form-control" value="{{ old('apDate',$header->ap_date)  }}" placeholder="DD-MM-YYYY" />
                                        </div> 
                                        <div class="form-group col-md-2">
                                            <label for="period">Period*</label>
                                            <select class="select2 form-control disabled-el" id="period" name="period" disabled>
                                                <option value=""></option>
                                                @for ($i = 1; $i <= 12; $i++)
                                                    <option value="{{ $i }}" {{$i == $header->period ? "selected" : ""}}>{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-10">
                                            <label class="form-label" for="supplier">Supplier*</label>
                                            <select class="select2 form-control" id="supplier" name="supplier" required>
                                                <option value="">All</option>
                                                @foreach($supps as $val)
                                                    <option value="{{ $val->kode }}" data-term = "{{ $val->top_batas_1 }}" data-coa = "{{ $val->account }}"  {{ old('supplier',$header->supplier_id) == $val->kode ? 'selected' : '' }} >{{$val->kode}} - {{$val->nama}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="term">Term*</label>
                                            <input type="text" id="term" name="term" class="form-control" value="{{ old('term',$header->top_batas_1) }}" disabled />
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-10">
                                            <label for="accountHutang">COA Hutang*</label>
                                            <input type="text" id="accountHutang" name="accountHutang" class="form-control disabled-el" value="{{ old('accountHutang',$header->account_total) }}" disabled />
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
                                                <option value="{{$val}}" {{ old('currency',$header->currency) == $val ? 'selected' : '' }} >{{$val}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="rate">Rate*</label>
                                            <input type="text" id="rate" name="rate" value="{{ old('rate',$header->kurs) }}" class="form-control numeral-mask text-right" required/>
                                        </div>
                                    </div>
                                    {{-- <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="accountBasisA">COA Basis Amount*</label>
                                            <select class="select2 form-control w-100" id="accountBasisA" name="accountBasisA" required>
                                                <option value="">Choose option</option>
                                                @foreach($accountBa as $val)
                                                    <option value="{{ $val->account }}" {{ old('account',$header->account_ba) == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div> --}}
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="invoiceNumber">Invoice Number*</label>
                                            <input type="text" id="invoiceNumber" name="invoiceNumber" class="form-control" value="{{ old('invoiceNumber',$header->inv_number) }}" required/>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="invoiceDate">Invoice Date</label>
                                            <input type="text" id="invoiceDate" name="invoiceDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ old('invoiceDate',$header->inv_date) }}" />
                                        </div> 
                                        <div class="form-group col-md-5">
                                            <label for="taxInvoiceNumber">Tax Invoice Number</label>
                                            <input type="text" id="taxInvoiceNumber" name="taxInvoiceNumber" class="form-control" value="{{ old('taxInvoiceNumber',$header->tax_inv_number) }}" />
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="note">Notes</label>
                                            <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ old('note',$header->note) }}</textarea>
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
                                    <br>
                                    <div class="form-row">
                                        <div class="col-md-12">
                                            <button class="btn btn-primary" type="button" id="cmdSubmit" name="cmdSubmit">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="form-row">
                                <div class="col-sm-12">
                                    <p class="mb-0">Detail receiving</p>
                                    <div class="card-datatable table-responsive pt-0">
                                    <table class="table table-bordered" id="listOfRec" style="table-layout: fixed;" width="100%">
                                        <thead>
                                            <tr>
                                                <th scope="col" width="20%" align="center" style="padding:5px;text-align:center;">Account</th>
                                                <th scope="col" width="12%" style="padding:5px;text-align:center;">Article</th>
                                                <th scope="col" width="" style="padding:5px;text-align:center;">Description</th>
                                                <th scope="col" width="5%" style="padding:5px;text-align:center;">Dept</th>
                                                <th scope="col" width="5%" style="padding:5px;text-align:center;">UOM</th>
                                                <th scope="col" width="8%" style="padding:5px;text-align:center;">Qty</th>
                                                <th scope="col" width="10%" style="padding:5px;text-align:center;">Price</th>
                                                <th scope="col" width="15%" style="padding:5px;text-align:center;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <br>
                            <div class="form-row">
                                <div class="col-sm-12">
                                    <p class="mb-0">Add item</p>
                                    <div class="card-datatable table-responsive pt-0">
                                      <table class="table table-bordered" id="addItem" width="100%">
                                            <thead>
                                                <tr>
                                                    <th scope="col" width="25%">Account</th>
                                                    <th scope="col" width="30%">Description</th>
                                                    <th scope="col" width="20%">CC</th>
                                                    <th scope="col" width="10%">Amount</th>
                                                    <th scope="col" width="5%">-</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-sm-12 mt-75">
                                    <button class="btn btn-primary btn-prev" type="button" id="addNewRow" onclick="add_new_row();hitungGrandTotal();">
                                        <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                                    </button>
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
                                            <input type="hidden" class="form-control text-right font-weight-bold disabled-el" id="basisAmountA" name="basisAmountA" />
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03 d-none">
                                        <label for="totalDiscount" class="col-sm-4 col-form-label titik-dua">Discount </label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask disabled-el" id="totalDiscount" name="totalDiscount" />
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPN" class="col-sm-4 col-form-label titik-dua">PPN <span id="nilaiPPN">{{ $header->vat >0 ? $nilaiPPN."%" : '' }}</span> </label>
                                        <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="vatCheck" name="vatCheck" {{ $header->vat >0 ? 'checked' : '' }}/>
                                                <label class="custom-control-label" for="vatCheck"></label>
                                            </div>
                                        </div>    
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control text-right font-weight-bold   disabled-el numeral-mask-digit" id="totalPPN" name="totalPPN" oninput='inputDecimal(this)' value="{{ $header->vat>0 ? number_format($header->vat,2) : 0 }}"  {{ $header->vat > 0 ? '' : 'disabled' }} {{ $header->vat > 0 ? 'required' : '' }}/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPH23" class="col-sm-4 col-form-label titik-dua">PPH23 <span id="nilaiPPH23">{{ $header->pph23_type == 'PPH23' ? $nilaiPPH23."%" : '' }}</span> </label>
                                        <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="pph23Check" name="pph23Check" {{ $header->pph23_type == 'PPH23' ?'checked' : '' }}/>
                                                <label class="custom-control-label" for="pph23Check"></label>
                                            </div>
                                        </div> 
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalPPH23" name="totalPPH23" oninput='inputDecimal(this)' value="{{ $header->pph23_type == 'PPH23' ? number_format($header->pph23,2) : 0 }}" {{ $header->pph23_type == 'PPH23' ? '' : 'disabled' }} {{ $header->pph23_type == 'PPH23' ? 'required' : '' }} />
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPH21" class="col-sm-4 col-form-label titik-dua">PPH21 <span id="nilaiPPH21">{{ $header->pph23_type == 'PPH21' ? $nilaiPPH21."%" : '' }}</span> </label>
                                        <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="pph21Check" name="pph21Check" {{ $header->pph23_type == 'PPH21' ?'checked' : '' }}/>
                                                <label class="custom-control-label" for="pph21Check"></label>
                                            </div>
                                        </div> 
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalPPH21" name="totalPPH21" oninput='inputDecimal(this)' value="{{ $header->pph23_type == 'PPH21' ? number_format($header->pph23,2) : 0 }}" {{ $header->pph23_type == 'PPH21' ? '' : 'disabled' }} {{ $header->pph23_type == 'PPH21' ? 'required' : '' }}/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPH42" class="col-sm-4 col-form-label titik-dua">PPH4(2) <span id="nilaiPPH42">{{ $header->pph23_type == 'PPH42' ? $nilaiPPH42."%" : '' }}</span> </label>
                                        <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="pph42Check" name="pph42Check" {{ $header->pph23_type == 'PPH42' ?'checked' : '' }}/>
                                                <label class="custom-control-label" for="pph42Check"></label>
                                            </div>
                                        </div> 
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalPPH42" name="totalPPH42" oninput='inputDecimal(this)' value="{{ $header->pph23_type == 'PPH42' ? number_format($header->pph23,2) : 0 }}" {{ $header->pph23_type == 'PPH42' ? '' : 'disabled' }} {{ $header->pph23_type == 'PPH42' ? 'required' : '' }}/>
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
                                    <a href="{{ route('accountPayable.index') }}" class="btn btn-light">< Back</a>
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

                                    @if( $status =='APPROVED')
                                        <button class="btn btn-primary" type="button" id="cmdPosting" name="cmdPosting" >Posting</button>
                                    @endif
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
    .padding-none{
        padding:0px 5px 0px 5px;
    }
</style>
@endsection
@section('scripts')
@include('accounting.accountPayable.script')
<script type="text/javascript">

    $(document).ready(function(){
        validateFormToast("frmAdd");
        isiCoa('list_coa');

        setTimeout(function () {
            $(".loading-spinner-container").addClass("-show");
        }, 500);
        timerId= setInterval(() => checkVariable(), 1000);

        mask_thousand();
        mask_thousand_digit(2);
        edit='true';
        dariEdit='true';
        poAda ="{{ $header->po_number }}";
        // $('#supplier').val("{{ $header->supplier_id }}").trigger('change');
        showDetail='false';       

    });

    function checkVariable() {
        if (listCoa.length > 0) {
            clearInterval(timerId);
            $('#supplier').val("{{ $header->supplier_id }}").trigger('change');
            let apDetails = @json($apDetails);
            for(i=0;i<apDetails.length;i++){
                add_new_row_edit(apDetails[i].account,apDetails[i].description,apDetails[i].cost_center,apDetails[i].debit);
            }
            $(".loading-spinner-container").removeClass("-show");
        }
    }

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

    $("#cmdApprove").click(function(){    
        let apNumber = $('#apNumber').val();
        $.ajax({
            type: "post",
            url: "{{ route('aps.approve') }}",
            data: {
                apNumber:apNumber
            },
            dataType: "json",
            success: function(data) {
                if (data.status == 0 ){
                    let message="";
                    for(let i = 0; i < data.message.length; i++) {
                        show_msg(data.title, data.message[i], data.alert);
                    }
                    $('#apNumber').attr('disabled','disabled');

                }else{
                    show_msg(data.title, data.message, data.alert);
                    $('#apNumber').attr('disabled','disabled');
                    $('#cmdApprove').attr('disabled','disabled');
                    $('#addNewRow').attr('disabled','disabled');  
                    $('#cmdUpdate').attr('disabled','disabled');
                    location.reload();       
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