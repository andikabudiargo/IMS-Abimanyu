@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="add-index">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusTso }}</span></h4>
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
                                    data-po-number="{{ $header->tso_code }}">{{ $key == 0 ? 'Main':'Revision '.($key-1) }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach( $headers as $key =>$header2 )
                                <div class="tab-pane {{ $key == 0 ? 'active':'' }}" id="rev{{ $key }}" aria-labelledby="revison{{ $key }}-tab" role="tabpanel">
                                    <form id="frmAdd{{ $key }}" name="frmAdd{{ $key }}" autocomplete="off">
                                        <input type="text" id="article" name="article" hidden>
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label for="tsoCode">Target SO Number</label> <small class="text-muted"> automatic</small>
                                                <input type="text" id="tsoCode" name="tsoCode" class="form-control disabled-el" value="{{ $header2->tso_code }}" disabled />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="tsoDate">Date*</label>
                                                <input type="text" id="tsoDate" name="tsoDate" class="form-control" placeholder="DD-MM-YYYY" value="{{ $header2->tso_date }}" disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-5">
                                                <label for="tsoName">Target SO Name*</label>
                                                <input type="text" id="tsoName" name="tsoName" class="form-control" value="{{ $header2->tso_name }}" disabled />
                                            </div>
                                        </div>
                                        {{-- <div class="form-row">
                                            <div class="form-group col-md-5">
                                                <label class="form-label" for="customer">Customer*</label>
                                                <input type="text" id="customer" name="customer" class="form-control" value="{{ $header2->customer }}" disabled />
                                            </div>
                                        </div> --}}
                                        <div class="form-row">
                                            <div class="form-group col-md-5">
                                                <label class="form-label" for="note">Notes</label>
                                                <textarea type="text" id="note" name="note" class="form-control" rows="1" disabled >{{ $header2->note }}</textarea>
                                            </div>
                                        </div>
                                        @if($key!=0)
                                        <div class="form-row">
                                            <div class="form-group col-md-7">
                                                <label class="form-label" for="note">Revision reason</label>
                                                <textarea type="text" id="rReason" name="rReason" class="form-control" rows="1" disabled >{{ $header2->reason }}</textarea>
                                            </div>
                                        </div>
                                        @endif
                                    </form>
                                    <hr>
                                    <h4 class="card-title">Article</h4>
                                    <div class="table-responsive main-table">
                                        <table class="table table-bordered w-100" >
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>No</th>
                                                    <th width="20%">Article Code</th>
                                                    <th class="text-right">Qty Target</th>
                                                    <th class="text-right">Qty Forcast</th>

                                                    @if($key ==0)
                                                        <th class="text-left">Balance</th>
                                                    @endif

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
                                                    {{-- <th class="text-right">Notes</th> --}}
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach( $details as $item )
                                                @if($item->tso_code === $header2->tso_code )
                                                    <tr>
                                                        <td ></td>
                                                        <td width="20%">{{ $item->article }}</td>
                                                        <td class="text-right">{{ number_format($item->qty_target) }} </td>
                                                        <td class="text-right">{{ number_format($item->qty_forcast) }} </td>
                                                        @php
                                                            {{ $histori = explode("->",$item->notes);}}
                                                        @endphp 

                                                        @if ($key ==0)
                                                            <td class="text-right">{{ number_format(intval($item->qty_target -$histori[count($histori)-1])) }}</td>
                                                        @endif

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
                                                        {{-- <td class="text-right">{{ $item->notes }} </td> --}}
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-end mt-75">
                                        <div class="col-md-4">
                                            <span>ROW : {{ $header2->sum_row }}</span>
                                        </div>
                                        <div class="col-md-4">
                                        </div>
                                    </div>
                                    <br>
                                    <a href="{{ route('targetSo.index') }}" class="btn btn-light">Back</a>
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
                                                <div class="avatar bg-light-{{ $val->statusapprove == 1 ? 'success':'warning' }} mr-2">
                                                    <div class="avatar-content">
                                                        <i data-feather="{{ $val->statusapprove == 1 ? 'check':'x' }}" class="avatar-icon"></i>
                                                    </div>
                                                </div>
                                                <div class="media-body my-auto">
                                                    <h4 class="font-weight-bolder mb-0">{{ $val->statusapprove == 1 ? 'Approve':'Decline' }}-{{ $val->approval_order }}</h4>
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
                                                        <i data-feather="minus" class="avatar-icon"></i>
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
                        {{-- <hr>
                        <div class="form-row card-statistics">
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
                            @endforeach
                        </div> --}}

                        {{-- <div class="form-row card-statistics">
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
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('styles')
<style>
    .main-table table {
        counter-reset: rowNumber;
        display: block;
        height: 500px;
        overflow-y: scroll;
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
        mask_thousand_satuan();
    });
</script>
@endsection