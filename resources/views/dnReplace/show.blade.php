@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
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
                        <form id="frmAdd" name="frmAdd" autocomplete="off">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="replaceNumber">Replace Number</label>
                                    <input type="text" id="replaceNumber" name="replaceNumber" class="form-control text-hitam disabled-el" value="{{ $header->replace_number }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="replaceDate">Replace Date</label>
                                    <input type="text" id="replaceDate" name="replaceDate" class="form-control text-hitam" placeholder="DD-MM-YYYY" value="{{ $header->replace_date }}" disabled />
                                </div>                               
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="customer">Customer</label>
                                    <input type="text" id="customer" name="customer" class="form-control text-hitam disabled-el" value="{{ $header->customer_name }}"  disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="returnNumber">Return Number</label>
                                    <input type="text" id="returnNumber" name="returnNumber" class="form-control text-hitam disabled-el" value="{{ $header->return_number }}"  disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="dnNumber">Customer DN Number</label>
                                    <input type="text" id="dnNumber" name="dnNumber" class="form-control disabled-el" value="{{ $header->dn_number }}" disabled/>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control text-hitam" rows="1" disabled>{{ $header->note }} </textarea>
                                </div>
                            </div>
                        </form>
                        <hr>               
                        <div class="table-responsive main-table">
                            <table class="table table-bordered w-100" id="tableDetail" >
                                <thead class="thead-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Article Code</th>
                                        <th class="text-right">Tot. Qty Return</th>
                                        <th class="text-right">Sisa Qty Return</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-left">UOM</th>                                        
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach( $details as $item )
                                    <tr>
                                        <td class="text-right"></td>
                                        <td >{{ $item->article }}</td>
                                        <td class="text-right">{{ number_format($item->tot_qty_return,0) }} </td>
                                        <td class="text-right">{{ number_format($item->qty_return,0) }} </td>
                                        <td class="text-right">{{ number_format($item->qty,0) }} </td>
                                        <td>{{ $item->uom }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-end mt-75">
                            <div class="col-md-4">
                                <span>ROW : {{ $header->sum_row }}</span> <br>
                                <span>QTY : {{ number_format($header->sum_qty) }}</span>
                            </div>
                            <div class="col-md-4">
                            </div>
                        </div>
                        <hr>
                        <div class="mt-75">
                            <a href="{{ route('dnReplace.index') }}" class="btn btn-light">Back</a>
                            <a href="{{ route('dnReplace.print',['id'=>Crypt::encryptString($header->id)]) }}" target="_blank" class="btn btn-success">Print</a>
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

    #tableDetail th, #tableDetail td {
        padding: 0.4rem 0.6rem;
        vertical-align: middle;
    }
    

</style>
@endsection
@section('scripts')
<script type="text/javascript">
    $(document).ready(function(){           
        
    });
    
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection