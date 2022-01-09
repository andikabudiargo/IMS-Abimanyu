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
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label class="form-label">Supplier</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->supplier_id }} - {{ $sub_detail->nama }}" disabled />
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label class="form-label">PO Number</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->po_number }}" disabled />
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label class="form-label">Rec.Number / LPB</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->rec_number }}" disabled />
                                                    </div>
                                                </div>
                                                <div class="form-row">                                    
                                                    <div class="form-group col-md-6">
                                                        <label>Supplier</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->supplier_id }} - {{ $sub_detail->nama }}" disabled />
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label>PO Number</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->po_number }}" disabled />
                                                    </div>       
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
                                                        <label>Receive Date</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->rec_date }}" placeholder="DD-MM-YYYY" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label>Due Date</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->due_date }}" placeholder="DD-MM-YYYY" disabled/>
                                                    </div>       
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
                                                        <label>Total PO</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->due_date }}" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label>Balance</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->due_date }}" disabled/>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-2">
                                                        <label>Currency*</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->currency }}" disabled />
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label>Rate</label>
                                                        <input type="text" class="form-control numeral-mask text-right  text-hitam" value="{{ $sub_detail->kurs }}" disabled/>
                                                    </div>  
                                                </div>                         
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
                                                        <label>Invoice Number</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->inv_number }}" disabled/>
                                                    </div>
                                                    <div class="form-group col-md-3">
                                                        <label>Invoice Date</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->inv_date }}" placeholder="DD-MM-YYYY" disabled/>
                                                    </div> 
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label>Tax Invoice Number</label>
                                                        <input type="text" class="form-control text-hitam" value="{{ $sub_detail->tax_inv_number }}" disabled />
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
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
                                                    <div class="form-group col-md-3">
                                                        <label>Other Deductions</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ $sub_detail->other_deduction }}" disabled/>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-3">
                                                        <label>Total</label>
                                                        <input type="text" class="form-control numeral-mask text-right text-hitam" value="{{ ($sub_detail->basis_amount+$sub_detail->vat+$sub_detail->pph23) - $sub_detail->other_deduction }}" disabled/>
                                                    </div>
                                                </div>
                                                <div class="form-row">
                                                    <div class="form-group col-md-6">
                                                        <label class="form-label" for="account">COA</label>
                                                        <select class="select2 w-100" disabled>
                                                            <option value="">Choose option</option>
                                                            @foreach($accounts as $val)
                                                                <option value="{{ $val->account }}" {{ $sub_detail->account == $val->account ? 'selected' : '' }}>{{ $val->account}} - {{ $val->description }}</option>
                                                            @endforeach
                                                        </select>
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
    $(document).ready(function(){
        mask_thousand();
    });
   
        
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection