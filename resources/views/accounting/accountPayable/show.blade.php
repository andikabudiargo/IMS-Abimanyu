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
                        <form id="frmAdd" name="frmAdd" action="{{ route('ap.update',['id'=>Crypt::encryptString($id)]) }}" method="post" autocomplete="off">
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
                                            <label for="apDate">Receive AP</label>
                                            <input type="text" id="apDate" name="apDate" class="form-control" value="{{ $header->ap_date  }}" placeholder="DD-MM-YYYY" disabled>
                                        </div> 
                                        <div class="form-group col-md-2">
                                            <label for="period">Period</label>
                                            <input type="text" id="period" name="period" class="form-control" value="{{ $header->period  }}" placeholder="DD-MM-YYYY" disabled/>
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-10">
                                            <label class="form-label" for="supplier">Supplier*</label>
                                            <select class="select2 form-control" id="supplier" name="supplier" disabled>
                                                <option value="">All</option>
                                                @foreach($supps as $val)
                                                    <option value="{{ $val->kode }}" {{ $header->supplier_id == $val->kode ? 'selected' : '' }} >{{$val->kode}} - {{$val->nama}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="term">Term</label>
                                            <input type="text" id="term" name="term" class="form-control" value="{{ $header->top_batas_1 }}" disabled />
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-10">
                                            <label for="accountHutang">COA Hutang</label>
                                            <input type="text" id="accountHutang" name="accountHutang" class="form-control disabled-el" value="{{ $header->account_total }}" disabled />
                                        </div> 
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-8">
                                            <label class="form-label" for="poNumber">PO Number*</label>
                                            <input type="text" class="form-control font-weight-bold disabled-el" id="poNumber" name="poNumber" value="{{ $header->po_number  }}" disabled />
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="currency">Currency*</label>
                                            <select class="select2 form-control" id="currency" name="currency" disabled>
                                                @foreach($currency as $val)
                                                <option value="{{$val}}" {{ $header->currency == $val ? 'selected' : '' }} >{{$val}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="rate">Rate*</label>
                                            <input type="text" id="rate" name="rate" value="{{ $header->kurs }}" class="form-control numeral-mask text-right" disabled/>
                                        </div>
                                    </div>
                                    {{-- <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="accountBasisA">COA Basis Amount*</label>
                                            <select class="select2 form-control w-100" id="accountBasisA" name="accountBasisA" disabled>
                                                <option value="">Choose option</option>
                                                @foreach($accountBa as $val)
                                                    <option value="{{ $val->account }}" {{ $header->account_ba == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div> --}}
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="invoiceNumber">Invoice Number*</label>
                                            <input type="text" id="invoiceNumber" name="invoiceNumber" class="form-control" value="{{ $header->inv_number }}" disabled/>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="invoiceDate">Invoice Date</label>
                                            <input type="text" id="invoiceDate" name="invoiceDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header->inv_date }}"  disabled/>
                                        </div> 
                                        <div class="form-group col-md-5">
                                            <label for="taxInvoiceNumber">Tax Invoice Number</label>
                                            <input type="text" id="taxInvoiceNumber" name="taxInvoiceNumber" class="form-control" value="{{ $header->tax_inv_number }}"  disabled/>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <label class="form-label" for="note">Notes</label>
                                            <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ $header->note }}</textarea>
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
                                                            <th scope="col" width="30%">LPB Number</th>
                                                            <th scope="col" width="30%">Date</th>
                                                            <th scope="col" width="30%">DO Number</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($listRec as $val)
                                                            <tr>
                                                                <td>{{ $val->rec_number }}</td>
                                                                <td>{{ $val->do_date }}</td>
                                                                <td>{{ $val->do_number }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-sm-12">
                                    <p class="mb-0">Detail receiving</p>
                                    <div class="card-datatable table-responsive pt-0">
                                        <table class="table table-bordered" id="listOfRec">
                                            <thead>
                                                <tr>
                                                    <th scope="col" width="30%">Account</th>
                                                    <th scope="col" width="20%">Article Code</th>
                                                    <th scope="col" width="30%">Description</th>
                                                    <th scope="col" width="5%">Dept</th>
                                                    <th scope="col" width="10%">UOM</th>
                                                    <th scope="col" width="8%">Qty</th>
                                                    <th scope="col" width="10%">Price</th>
                                                    <th scope="col" width="12%">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($detailRec as $item)
                                                <tr>
                                                    <td>{{ $item->account }}</td>
                                                    <td>{{ $item->article }}</td>
                                                    <td>{{ $item->desc }}</td>
                                                    <td>{{ $item->dept }}</td>
                                                    <td>{{ $item->uom }}</td>
                                                    <td class="text-right">{{ number_format($item->qty,2) }}</td>
                                                    <td class="text-right">{{ number_format($item->price,2) }}</td>
                                                    <td class="text-right">{{ number_format($item->total,2) }}</td>
                                                </tr>
                                                @endforeach
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
                                                    <th width="25%">Account</th>
                                                    <th width="30%">Description</th>
                                                    <th width="20%">CC</th>
                                                    <th width="10%">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($apDetails as $val)
                                                    <tr>
                                                        <td width="25%">{{ $val->account }}</td>
                                                        <td width="30%">{{ $val->description }}</td>
                                                        <td width="20%">{{ $val->name }}</td>
                                                        <td width="10%">{{ number_format($val->debit,2) }}</td>
                                                    </tr>
                                                @endforeach
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
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="basisAmount" name="basisAmount" value="{{ number_format($header->basis_amount,2) }}" disabled />
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03 d-none">
                                        <label for="totalPPN" class="col-sm-4 col-form-label titik-dua">Discount </label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalDiscount" name="totalDiscount" value="{{ number_format($header->total_discount,2) }}" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="totalPPN" class="col-sm-4 col-form-label titik-dua">PPN <span id="nilaiPPN"></span> </label>
                                        <div class="col-sm-1" style="padding-right: 0rem;display: flex;align-items: center;">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="vatCheck" name="vatCheck" {{ $header->vat ? 'checked':'' }} disabled/>
                                                <label class="custom-control-label" for="vatCheck"></label>
                                            </div>
                                        </div>    
                                        <div class="col-sm-5">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalPPN"  name="totalPPN" value="{{ number_format($header->vat,2) }}" disabled/>
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
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalPPH23" name="totalPPH23" value="{{ $header->pph23_type == 'PPH23' ? number_format($header->pph23,2) : 0 }}" disabled />
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
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalPPH21" name="totalPPH21" value="{{ $header->pph23_type == 'PPH21' ? number_format($header->pph23,2) : 0 }}" disabled/>
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
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="totalPPH42" name="totalPPH42" value="{{ $header->pph23_type == 'PPH42' ? number_format($header->pph23,2) : 0 }}" disabled/>
                                        </div>
                                    </div>
                                    <div class="form-group row mb-03">
                                        <label for="grandTotal" class="col-sm-4 col-form-label titik-dua">Total</label>
                                        <div class="col-sm-6">
                                            <input type="text" class="form-control text-right font-weight-bold numeral-mask-digit disabled-el" id="grandTotal" name="grandTotal" value="{{ number_format($header->grand_total,2) }}" disabled/>
                                        </div>
                                    </div>
                                </div>
                            </div>                           
                            <br>
                            <div class="form-row">
                                <div class="col-md-12">
                                    <a href="{{ route('accountPayable.index') }}" class="btn btn-light">Back</a>
                                </div>
                            </div>
                        </form>
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
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('styles')
<style>
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){
        mask_thousand();
        mask_thousand_digit(2);
        edit='true';
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection



{{-- @extends('layouts.app')
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
                        @foreach($sub_details as $key =>$sub_detail )
                            <li class="nav-item">
                                <a class="nav-link {{ $key == 0 ? 'active':'' }}" id="profile-tab" data-toggle="tab" href="#rev{{ $key }}" aria-controls="revisi{{ $key }}" role="tab" aria-selected="false">{{ $key == 0 ? 'Main':'Revision '.$key }}</a>
                            </li>
                        @endforeach
                    </ul>
                    <div class="tab-content">
                        @foreach($sub_details as $key =>$sub_detail )
                            <div class="tab-pane {{ $key == 0 ? 'active':'' }}" id="rev{{ $key }}" aria-labelledby="revison{{ $key }}-tab" role="tabpanel">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Status: <span> {{ $key == 0 ? 'Main':'Revision '.$key }}</span></h4>
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
                                                        <label class="form-label">Rec.Number/LPB</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->rec_number }}" disabled />
                                                    </div>
                                                </div>
                                                <hr>
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
                                                    <div class="form-group col-md-4">
                                                        <label class="form-label" for="accountBasisA">COA</label>
                                                        <select class="select2 form-control w-100" id="accountBasisA" name="accountBasisA" disabled>
                                                            <option value="">Choose option</option>
                                                            @foreach($accountBa as $val)
                                                                <option value="{{ $val->account }}" {{ $sub_detail->account_ba == $val->account ? 'selected' : '' }}>{{ $val->account }} - {{ $val->description }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>VAT</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->vat }}" disabled />
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label class="form-label" for="accounVat">COA*</label>
                                                        <select class="select2 form-control w-100" id="accounVat" name="accounVat" required disabled>
                                                            <option value="1100.73">1100.73 - PPN MASUKAN (SUPPLIER)</option>
                                                        </select>
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
                                                        <label>Other Deductions</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->other_deduction }}" disabled/>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>Total</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ ($sub_detail->basis_amount+$sub_detail->vat+$sub_detail->pph23) - $sub_detail->other_deduction }}" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-4">
                                                        <label class="form-label" for="account">COA*</label>
                                                        <select class="select2 form-control w-100" id="account" name="account" required disabled>
                                                            <option value="2000.11">2000.11 - HUTANG USAHA (SUPPLIER)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-row">
                                                    <div class="form-group col-md-5">
                                                        <label class="form-label" for="note">Notes</label>
                                                        <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $sub_detail->note }}</textarea>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                        @endforeach
                        <div class="form-row">
                            <div class="col-md-12">
                                <a href="{{ route('aps.index') }}" class="btn btn-light">Back</a>
                            </div>
                        </div>
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
    $(document).ready(function(){
        mask_thousand();
    });
           
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection --}}