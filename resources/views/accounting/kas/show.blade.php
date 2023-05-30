@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
@include('partials.alert')
<section id="show">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $status }}</h4>
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
                            <input type="text" id="article" name="article" hidden>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="voucherNumber">Voucher Number</label>
                                    <input type="text" id="voucherNumber" name="voucherNumber" value="{{ $header->voucher_number }}" class="form-control" disabled/>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="vcDate">Date</label>
                                    <input type="text" id="vcDate" name="vcDate" value="{{ $header->voucher_date }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="period">Period</label>
                                    <input type="text" id="period" name="period" value="{{ $header->period }}" class="form-control" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="recFrom">Receive From</label>
                                    <input type="text" id="recFrom" name="recFrom" value="{{ $header->receive_name }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <div class="form-group">
                                        <label for="totalAmount">Amount</label>
                                        <input type="text" id="totalAmount" name="totalAmount" value="{{ number_format($header->amount) }}" class="form-control text-right numeral-mask" disabled/>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-10">
                                    <label class="form-label" for="note">Notes</label>
                                    <textarea type="text" id="note" name="note" class="form-control" rows="1" >{{ $header->note }}</textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                {{-- <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div> --}}
                <div class="card-body">
                    <table class="table-bordered" width="100%">
                        <thead>
                            <tr>
                                <th class="isian" style="width: 30%">
                                    <label>Account</label>
                                </th>
                                <th class="isian" style="">
                                    <label>Description</label>
                                </th>
                                <th class="isian" style="">
                                    <label>CC</label>
                                </th>
                                <th class="isian" style="width: 10%">
                                    <label>Debit</label>
                                </th>
                                <th class="isian" style="width: 10%">
                                    <label>Credit</label>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($details as $key =>$item)
                            <tr>
                                <td class="isian" style="width: 30%">
                                    {{ $item->account_name }}             
                                </td>
                                <td class="isian" style="">
                                    {{ $item->description }}
                                </td>
                                <td class="isian" style="">
                                    {{ $item->cost_center_name }}
                                </td>
                                <td class="isian text-right" style="width: 10%">
                                    {{ number_format($item->debit) }}
                                </td>
                                <td class="isian text-right" style="width: 10%">
                                    {{ number_format($item->credit) }}
                                </td>
                            </tr>
                            @endforeach
                            <tr>
                                <td class="isian" style="width: 30%">
                                </td>
                                <td class="isian">
                                </td>
                                <td class="isian">
                                    TOTAL
                                </td>
                                <td class="isian text-right" style="width: 10%">
                                    {{ number_format($total->total_debit) }}
                                </td>
                                <td class="isian text-right" style="width: 10%">
                                    {{ number_format($total->total_credit) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
</section>
@endsection
@section('styles')
<style>

    textarea {
        resize: none;
    }

    .mb-03{
        margin-bottom: 0.3rem;
    }
    
    label.titik-dua::after{
        content : ":"; 
        position : absolute;
        right : 1px;
    }

    td.isian{
        padding-right:10px;
        padding-left:10px;
    }

    th.isian{
        padding-right:10px;
        padding-left:10px;
    }

    td.isian-satu{
        padding-right:5px;
        padding-left:15px;
        width: 25%;border-top: 1px solid #ffffff !important;
        border-bottom: 1px solid #ffffff !important;
        border-left: 1px solid #ffffff !important;
    }

    td.disabled{
        background-color:#f8f8f8;
        color:black;
    }

    /* label.tanpa-padding{
        padding-top: 5px;
        padding-bottom: 0px;
    } */

    .totalLine{
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
</style>
@endsection
@section('scripts')
<script type="text/javascript">
    
</script>
@endsection