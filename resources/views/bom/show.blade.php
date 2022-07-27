@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="edit-form">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusBom }}</span></h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>    
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            @foreach( $headers as $key =>$header )
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active':'' }}" 
                                    id="po-tab" 
                                    data-toggle="tab" 
                                    href="#rev{{ $key }}" 
                                    aria-controls="revisi{{ $key }}" 
                                    role="tab" 
                                    aria-selected="false" 
                                    data-ajax-detail="true" 
                                    data-po-number="{{ $header->bom_code }}">{{ $key == 0 ? 'Main':'Revision '.$key }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach( $headers as $key =>$header2 )
                                <div class="tab-pane {{ $key == 0 ? 'active':'' }}" id="rev{{ $key }}" aria-labelledby="revison{{ $key }}-tab" role="tabpanel">
                                    <form id="frmAdd{{ $key }}" name="frmAdd{{ $key }}" autocomplete="off">
                                        <div class="form-row">
                                            <div class="col-md-3">
                                                <label for="bomNumber" class="form-label">BOM Number</label>
                                                <input type="text" id="bomNumber" name="bomNumber" class="form-control form-control-sm" value="{{ $header2->bom_code }}" disabled />
                                            </div>
                                        </div>  
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label class="form-label" for="articleCode">Article Finish Goods</label>
                                                <input type="text" id="customer" name="customer" class="form-control" value="{{ $header2->article }}"disabled />
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label class="form-label" for="articleCodeRm">Article Raw material*</label>
                                                <input type="text" id="articleCodeRm" name="articleCodeRm" value="{{ old('articleCodeRm',$header->article_rm) }}" class="form-control" disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="customer">Customer</label>
                                                <input type="text" id="customer" name="customer" value="{{ $header2->cust_name }}"class="form-control" disabled />
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="group">Group of material</label>
                                                <input type="text" id="group" name="group" class="form-control" disabled />
                                            </div>
                                            <div class="form-group col-md-1">
                                                <label for="uom">UOM</label>
                                                <input type="text" id="uom" name="uom" class="form-control" disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="partNo">Part No</label>
                                                <input type="text" id="partNo" name="partNo" value="{{ $header2->part_no }}" class="form-control" />
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="model">Model</label>
                                                <input type="text" id="model" name="model" value="{{ $header2->model }}" class="form-control" />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-2">
                                                <label for="tag">Tact</label>
                                                <input type="text" id="tag" name="tag" value="{{ old('tag',$header2->tag) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="passRate">Pass Rate</label>
                                                <input type="text" id="passRate" name="passRate" value="{{ old('passRate',$header2->pass_rate) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                            </div>
            
                                            <div class="form-group col-md-2">
                                                <label for="passThru">Pass trough</label>
                                                <div class="input-group input-group-merge">
                                                    <input type="text" id="passThru" name="passThru" value="{{ old('passThru',$header2->pass_thru) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="cycleTime">Cycle time buffing</label>
                                                <input type="text" id="cycleTime" name="cycleTime" value="{{ old('cycleTime',$header2->cycle_time) }}" class="form-control numeral-mask-digit" maxlength="5" disabled/>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-8">
                                                <label class="form-label" for="note">Notes</label>
                                                <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ old('note',$header2->note) }}</textarea>
                                            </div>
                                        </div>
                                    </form>
                                    <hr>
                                    <h4 class="card-title">Article</h4>
                                    <div class="table-responsive main-table">
                                        <table class="table table-bordered w-100" >
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Article Code</th>
                                                    <th class="text-right">QTY</th>
                                                    <th class="text-right">Uom</th>
                                                    <th >Qty Con.</th>
                                                    <th >Uom</th>
                                                    <th >Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach( $details as $key =>$item )
                                                @if($item->bom_code === $header2->bom_code )
                                                    <tr>
                                                        <td ></td>
                                                        <td >{{ $item->article }}</td>
                                                        <td class="text-right">{{ number_format($item->qty,$decimalPlaces) }}</td>
                                                        <td class="text-right">{{ $item->uom }}</td>
                                                        <td class="text-right">{{ number_format($item->qty*$item->factor_qty,$decimalPlaces) }}</td>
                                                        <td class="text-right">{{ $item->uom_con }}</td>
                                                        <td >{{ $item->type_name }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-end mt-75">
                                        <div class="col-md-4">
                                            <span>ROW : {{ $header2->sum_row }}</span> <br>
                                            <span>QTY : {{ $header2->sum_qty }}</span>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="table-responsive">
                                                <table class="table table-bordered w-100">
                                                    
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <a href="{{ route('boms.index') }}" class="btn btn-success">Back</a>
                                    <a href="{{ route('bom.print', ['id'=>Crypt::encryptString($header2->id)]) }}" target="_blank" type="button" class="btn btn-primary">
                                        <i data-feather="printer"></i>
                                        <span>{{ __("Print") }}</span>
                                    </a>
                                </div>
                            @endforeach
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
    </div>
</section>
@endsection
@section('styles')
<style>
    textarea {
        resize: none;
    }   

    table {
        counter-reset: rowNumber+1;
    }

    table tr:not(:first-child) {
        counter-increment: rowNumber;
    }

    table tr td:first-child::before {
        content: counter(rowNumber);
        min-width: 1em;
        margin-right: 0.5em;
    }
</style>
@endsection
@section('scripts')
@include('bom.addArticle')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        mask_thousand_digit(numberOfDecimalDigit);
        $('.sku-select-system').select2();
    });
       
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection