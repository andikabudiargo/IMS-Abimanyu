@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $statusPr }}</h4>
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
                                    data-po-number="{{ $header->pr_number }}">{{ $key == 0 ? 'Main':'Revision '.$key }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach( $headers as $key =>$header2 )
                                <div class="tab-pane {{ $key == 0 ? 'active':'' }}" id="rev{{ $key }}" aria-labelledby="revison{{ $key }}-tab" role="tabpanel">
                                    <form id="frmAdd" name="frmAdd" autocomplete="off">
                                        @csrf
                                        <input type="text" id="article" name="article" hidden>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="prNumber">Request Number</label> <small class="text-muted"> automatic</small>
                                                <input type="text" id="prNumber" name="prNumber" class="form-control disabled-el" value="{{ $header2->pr_number }}" disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-2">
                                                <label class="form-label" for="poType">PO Type*</label>
                                                <select class="select2 form-control" id="poType" name="poType" required disabled >
                                                    <option value="std" {{ $header2->order_type == 'tso' ? "selected" : ""}}>Target SO</option>
                                                    <option value="std" {{ $header2->order_type == 'std' ? "selected" : ""}}>Standard</option>
                                                    <option value="sub" {{ $header2->order_type == 'rm' ? "selected" : ""}}>Raw Material</option>
                                                    <option value="sub" {{ $header2->order_type == 'sub' ? "selected" : ""}}>Subcontracting</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="orderDate">Order Date*</label>
                                                <input type="text" id="orderDate" name="orderDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header2->date }}"required disabled />
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label class="form-label" for="dept">Department*</label>
                                                <select class="select2 form-control" id="dept" name="dept" required disabled >
                                                    <option value=""></option>
                                                    @foreach($depts as $val)
                                                        <option value="{{$val->code}}" {{$val->code == $header2->dept ? "selected" : ""}}>{{$val->code}} - {{$val->name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @if($header2->order_type == 'tso')
                                        <div class="form-row" id="tsoBox">
                                            <div class="form-group col-md-2">
                                                <label for="stockDate">Stock Date</label>
                                                <input type="text" id="stockDate" name="stockDate" class="form-control disabled-el" placeholder="DD-MM-YYYY" value="{{ date_format(date_create($header2->stock_date),'d-m-Y') }}" disabled/>
                                            </div>
                                            <div class="form-group col-md-5">
                                                <label for="tsoCode">Target SO Number</label>
                                                <input type="text" id="tsoCode" name="tsoCode" class="form-control disabled-el" value="{{ $header2->tso_code }}" disabled/>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label class="form-label" for="note">Notes</label>
                                                <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled >{{ $header2->note }}</textarea>
                                            </div>
                                        </div>
                                    </form>
                                    <hr>               
                                    <div class="table-responsive main-table">
                                        <table class="table table-bordered w-100" >
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Article</th>
                                                    <th class="text-right">QTY</th>
                                                    <th class="text-left">UOM</th>
                                                    {{-- <th class="text-left">History</th> --}}
                                                    @foreach( $headers as $key =>$header2 )
                                                        <th class="text-left">R-{{ $key }}</th>
                                                    @endforeach
                                                    <th class="text-right">Note</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach( $details as $key =>$item )
                                                @if($item->pr_number === $header2->pr_number )
                                                    <tr>
                                                        <td ></td>
                                                        <td >{{ $item->article }}</td>
                                                        <td class="text-right">{{ number_format($item->qty) }} </td>
                                                        <td>{{ $item->uom }}</td>
                                                        {{-- <td class="text-left">{{ $item->notes }}</td> --}}
                                                        @php
                                                            $histori = explode("->",$item->notes);
                                                        @endphp
                                                        @foreach( $headers as $key =>$header2 )
                                                            <td class="text-left">{{ $histori[$key] }}</td>
                                                        @endforeach
                                                        <td class="text-right">{{ $item->note }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-end mt-75">
                                        <div class="col-md-4">
                                            <span>ROW : {{ $header2->sum_row }}</span> <br>
                                            <span>QTY : {{ number_format($header2->sum_qty) }}</span>
                                        </div>
                                        <div class="col-md-4">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="mt-75">
                                        <a href="{{ route('purchaseRequests.index') }}" class="btn btn-warning">Back</a>
                                        <a href="{{ route('purchaseRequest.print',['id'=>Crypt::encryptString($header2->id)]) }}" target="_blank" class="btn btn-success">Print</a>
                                    </div>
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

    .text-merah{
        color:red;
    }

</style>
@endsection
@section('scripts')
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        activate_angka();
        mask_thousand();
    });
       
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection