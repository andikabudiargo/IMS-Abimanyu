@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusPo }}</span></h4>
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
                                    data-po-number="{{ $header->po_number }}">{{ $key == 0 ? 'Main':'Revision '.$key }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach( $headers as $key =>$header2 )
                                <div class="tab-pane {{ $key == 0 ? 'active':'' }}" id="rev{{ $key }}" aria-labelledby="revison{{ $key }}-tab" role="tabpanel">
                                    <form id="frmAdd{{ $key }}" name="frmAdd{{ $key }}" autocomplete="off">
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="poNumber{{ $key }}">Order Number</label> <small class="text-muted"> automatic</small>
                                                <input type="text" id="poNumber{{ $key }}" name="poNumber{{ $key }}" class="form-control disabled-el" value="{{$header2->po_number }}" disabled />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label class="form-label" for="poType{{ $key }}">PO Type*</label>
                                                <select class="select2 form-control" id="poType{{ $key }}" name="poType{{ $key }}" disabled>
                                                    <option value="std" {{$header2->order_type == 'std' ? "selected" : "" }}>Standard</option>
                                                    <option value="sub" {{$header2->order_type == 'sub' ? "selected" : "" }}>Subcontracting</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="orderDate{{ $key }}">Order Date*</label>
                                                <input type="text" id="orderDate{{ $key }}" name="orderDate{{ $key }}" class="form-control" value="{{$header2->po_date }}" placeholder="DD-MM-YYYY" disabled/>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="deliveryDate{{ $key }}">Delivery Date</label>
                                                <input type="text" id="deliveryDate{{ $key }}" name="deliveryDate{{ $key }}" class="form-control" value="{{$header2->delivery_date }}" placeholder="DD-MM-YYYY" disabled/>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-5">
                                                <label class="form-label" for="supplier{{ $key }}">Supplier*</label>
                                                <input type="text" id="supplier{{ $key }}" name="supplier{{ $key }}" class="form-control" value="{{ $header2->supp_name }}" disabled/>
                                            </div>
                                            <div class="form-group col-md-1">
                                                <label class="form-label" for="term{{ $key }}">Term</label>
                                                <input type="text" class="form-control angka text-right" id="term{{ $key }}" name="term{{ $key }}" value="{{$header2->termin }}" maxlength="4" disabled/>
                                            </div>
                                            <div class="form-group col-md-1 d-flex align-items-end" >
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" {{$header2->pkp == 'PKP' ? "checked" : "" }} id="pkp{{ $key }}" name="pkp{{ $key }}" disabled/>
                                                    <label class="custom-control-label" for="pkp{{ $key }}">PKP</label>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label" for="ppn{{ $key }}">PPN</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control angka text-right" id="ppn{{ $key }}" name="ppn{{ $key }}" value="{{$header2->ppn }}" maxlength="2" disabled/>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group col-md-2 d-none">
                                                <label for="currency{{ $key }}">Currency*</label>
                                                <input type="text" id="currency{{ $key }}" name="currency{{ $key }}" class="form-control" value="{{ $header2->currency }}" disabled/>
                                            </div>
                                            <div class="form-group col-md-2 d-none">
                                                <div class="form-group">
                                                    <label for="kurs{{ $key }}">Kurs</label>
                                                    <input type="text" id="kurs{{ $key }}" name="kurs{{ $key }}" class="form-control angka" value="{{$header2->kurs }}" maxlength="6" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-9">
                                                <label class="form-label" for="note{{ $key }}">Notes</label>
                                                <textarea type="text" id="note{{ $key }}" name="note{{ $key }}" class="form-control" rows="1" disabled>{{$header2->note }} </textarea>
                                            </div>
                                        </div>
                                    </form>
                                    <hr>
                                    <h4 class="card-title">Article</h4>
                                    <div class="table-responsive main-table">
                                        <table class="table table-bordered w-100" >
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Purchase Request</th>
                                                    <th>Article Code</th>
                                                    <th class="text-right">Stock</th>
                                                    <th class="text-right">QTY</th>
                                                    <th class="text-right">Price</th>
                                                    <th class="text-right">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach( $details as $key =>$item )
                                                @if($item->po_number === $header2->po_number )
                                                    <tr>
                                                        <td ></td>
                                                        <td >{{ $item->pr_number }}</td>
                                                        <td >{{ $item->article }}</td>
                                                        <td class="text-right">{{ $item->uom_group =='PIECE' ? number_format($item->qty_stock) : number_format($item->qty_stock,3) }} {{ $item->uom }}</td>
                                                        <td class="text-right">{{ $item->uom_group =='PIECE' ? number_format($item->qty) : number_format($item->qty,3) }} {{ $item->uom }}</td>
                                                        <td class="text-right">{{ number_format($item->price) }}</td>
                                                        <td class="text-right">{{ number_format($item->qty * $item->price) }}</td>
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
                                                    <tbody>
                                                        <tr>
                                                            <td>Subtotal</td>
                                                            <td class="text-right">{{ number_format($header2->sum_amount) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Discount</td>
                                                            <td class="text-right">{{ number_format($header2->discount) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>PPN {{ $header2->ppn }}%</td>
                                                            <td class="text-right">{{ number_format($header2->sum_ppn) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>PPH22</td>
                                                            <td class="text-right">{{ number_format($header2->sum_pph22) }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td>NETTO</td>
                                                            <td class="text-right">{{ number_format(($header2->sum_amount-$header2->sum_discount)+$header2->sum_ppn) }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <a href="{{ route('purchaseOrders.index') }}" class="btn btn-success">Back</a>
                                    <a href="{{ route('purchaseOrder.print', ['id'=>Crypt::encryptString($header2->id)]) }}" target="_blank" type="button" class="btn btn-primary">
                                        <i data-feather="printer"></i>
                                        <span>{{ __("Print") }}</span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <hr>
                        <div class="form-row card-statistics">
                            @php
                                $ketemu = "false";
                            @endphp
                            @foreach($approveLevel as $val)
                                
                                @php
                                    $ketemu = "false";
                                @endphp
                                @foreach($approveHistory as $val2)
                                    @if( $val2->name == $val->name && $val2->approval_order == $val->approval_order )
                                    <div class="statistics-body">
                                        <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                            <div class="media">
                                                <div class="avatar bg-light-success mr-2">
                                                    <div class="avatar-content">
                                                        <i data-feather="check" class="avatar-icon"></i>
                                                    </div>
                                                </div>
                                                <div class="media-body my-auto">
                                                    <h4 class="font-weight-bolder mb-0">Approve-{{ $val2->approval_order }}/{{ $val2->approval_number }}</h4>
                                                    <p class="card-text mb-0">{{ $val2->name }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @php
                                        $ketemu = "true";
                                    @endphp
                                    @endif
                                @endforeach
                                @if( $ketemu == 'false')
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
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                    
                            @endforeach
{{-- 
                            @foreach($approveHistory as $val)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar bg-light-success mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="check" class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">Approve-{{ $val->approval_order }}/{{ $val->approval_number }}</h4>
                                                <p class="card-text mb-0">{{ $val->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach --}}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@include('purchaseOrder.modalListPrice')
@endsection
@section('styles')
<style>
    .main-table table {
        counter-reset: rowNumber;
    }

    .main-table table tr > td:first-child{
        counter-increment: rowNumber;
    }

    .main-table table tr td:first-child::before {
        content: counter(rowNumber);
        min-width: 1em;
        margin-right: 0.5em;
    }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){           
        activate_angka();
        mask_thousand();
    });
</script>
@endsection