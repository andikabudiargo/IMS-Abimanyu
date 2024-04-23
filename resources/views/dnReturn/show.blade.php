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
                            @foreach( $headers as $key =>$header )
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active':'' }}" 
                                    id="tdn-tab" 
                                    data-toggle="tab" 
                                    href="#rev{{ $key }}" 
                                    aria-controls="revisi{{ $key }}" 
                                    role="tab" 
                                    aria-selected="false" 
                                    data-ajax-detail="true" 
                                    data-po-number="{{ $header->return_number }}">{{ $key == 0 ? 'Main':'Revision '.($key-1) }}</a>
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
                                                <label for="returnNumber">DN Return Number</label>
                                                <input type="text" id="returnNumber" name="returnNumber" class="form-control disabled-el" value="{{ $header2->return_number }}" disabled />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="returnDate">Delivery Date</label>
                                                <input type="text" id="returnDate" name="returnDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header2->return_date }}"required disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-8">
                                                <label class="form-label" for="cust">Customer</label>
                                                <select class="select2 form-control" id="cust" name="cust" disabled >
                                                    <option value=""></option>
                                                    @foreach($customers as $val)
                                                        <option value="{{$val->kode}}" {{$val->kode == $header2->customer_id ? "selected" : ""}} >{{$val->kode}} - {{$val->nama}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="dnNumber">Customer DN Number</label>
                                                <input type="text" id="dnNumber" name="dnNumber" class="form-control disabled-el" value="{{ $header->dn_number }}" disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-8">
                                                <label class="form-label" for="note">Notes</label>
                                                <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled >{{ $header2->note }}</textarea>
                                            </div>
                                        </div>
                                        @if($key!=0)
                                        <div class="form-row">
                                            <div class="form-group col-md-8">
                                                <label class="form-label" for="rReason">Revision reason</label>
                                                <textarea type="text" id="rReason" name="rReason" class="form-control" rows="1" disabled >{{ $header2->reason }}</textarea>
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
                                                    @if ($key !=0)
                                                        @foreach( $headers as $key1 => $oki )
                                                            @if ($key1 < $key and $key1!= 0 )
                                                                <th class="text-center">R-{{ $key1 }}</th>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        @foreach( $headers as $key1 => $oki )
                                                            @if ($key1 > $key and $key1!= 0 )
                                                                <th class="text-center">R-{{ $key1-1 }}</th>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach( $details as $item )
                                                @if($item->return_number === $header2->return_number )
                                                    <tr>
                                                        <td class="text-right"></td>
                                                        <td >{{ $item->article }}</td>
                                                        <td class="text-right">{{ number_format($item->qty,2) }} </td>
                                                        <td>{{ $item->uom }}</td>
                                                        @php
                                                            {{ $histori = explode("->",$item->notes);}}
                                                        @endphp 
                                                        
                                                        @if ($key !=0)
                                                            @foreach( $headers as $key1 => $oki )
                                                                @if ($key1 < $key and $key1!= 0)
                                                                    @if( $key1 < count($histori) )
                                                                        <td class="text-right">{{ number_format(intval($histori[$key1])) }}</td>
                                                                    @else
                                                                        <td class="text-right"></td>
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            @foreach( $headers as $key1 => $oki )
                                                                @if ($key1 > $key and $key1!= 0)
                                                                    @if( $key1 < count($histori) )
                                                                        <td class="text-right">{{ number_format(intval($histori[$key1])) }}</td>
                                                                    @else
                                                                        <td class="text-right"></td>
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        @endif
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
                                        <a href="{{ route('dnReturn.index') }}" class="btn btn-light">Back</a>
                                        {{-- <a href="{{ route('dnReturn.print',['id'=>Crypt::encryptString($header2->id)]) }}" target="_blank" class="btn btn-success">Print</a> --}}
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
    textarea {
        resize: none;
    }

    .main-table table {
        counter-reset: rowNumber;
        /* display: block;
        height: 500px;
        overflow-y: scroll; */
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

    #tableDetail th, #tableDetail td {
        padding: 0.4rem 0.6rem;
        vertical-align: middle;
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