@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: {{ $status }}</h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            @foreach( $headers as $key => $header )
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active':'' }}"
                                       id="sr-tab"
                                       data-toggle="tab"
                                       href="#rev{{ $key }}"
                                       aria-controls="revisi{{ $key }}"
                                       role="tab"
                                       aria-selected="false">{{ $key == 0 ? 'Main':'Revision '.($key-1) }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach( $headers as $key => $header2 )
                                <div class="tab-pane {{ $key == 0 ? 'active':'' }}" id="rev{{ $key }}" aria-labelledby="revison{{ $key }}-tab" role="tabpanel">
                                    <form id="frmAdd" name="frmAdd" autocomplete="off">
                                        @csrf
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="returnNumber">Supplier Return Number</label>
                                                <input type="text" id="returnNumber" name="returnNumber" class="form-control disabled-el" value="{{ $header2->return_number }}" disabled />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="returnDate">Return Date</label>
                                                <input type="text" id="returnDate" name="returnDate" class="form-control" value="{{ $header2->return_date }}" disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label class="form-label" for="supplier">Supplier</label>
                                                <select class="select2 form-control" id="supplier" name="supplier" disabled>
                                                    <option value=""></option>
                                                    @foreach($suppliers as $val)
                                                        <option value="{{$val->kode}}" {{$val->kode == $header2->supplier_id ? "selected" : ""}}>{{$val->kode}} - {{$val->nama}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="location">Location</label>
                                                <input type="text" id="location" class="form-control" value="{{ $header2->location_number }}" disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="note">Notes</label>
                                                <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled>{{ $header2->note }}</textarea>
                                            </div>
                                        </div>
                                        @if($key != 0)
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="rReason">Revision reason</label>
                                                <textarea type="text" id="rReason" name="rReason" class="form-control" rows="1" disabled>{{ $header2->reason }}</textarea>
                                            </div>
                                        </div>
                                        @endif
                                    </form>
                                    <hr>
                                    <div class="table-responsive main-table">
                                        <table class="table table-bordered w-100" id="tableDetail">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Article</th>
                                                    <th class="text-right">QTY</th>
                                                    <th class="text-left">UOM</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach( $details as $item )
                                                @if($item->return_number === $header2->return_number)
                                                    <tr>
                                                        <td class="text-right"></td>
                                                        <td>{{ $item->article }}</td>
                                                        <td class="text-right">{{ number_format($item->qty,2) }}</td>
                                                        <td>{{ $item->uom }}</td>
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
                                    </div>
                                    <hr>
                                    <div class="mt-75">
                                        <a href="{{ route('supplierReturn.index') }}" class="btn btn-light">Back</a>
                                        <a href="{{ route('supplierReturn.print',['id'=>Crypt::encryptString($header2->id)]) }}" target="_blank" class="btn btn-success">Print</a>
                                    </div>
                                </div>
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
    textarea { resize: none; }

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

    #tableDetail th, #tableDetail td {
        padding: 0.4rem 0.6rem;
        vertical-align: middle;
    }
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){
        activate_angka();
        mask_thousand();
    });

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
</script>
@endsection