@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="edit-form">
    <div class="form-row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Status: <span id="statusText">{{ $statusPrd }}</span></h4>
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
                                    id="wo-tab" 
                                    data-toggle="tab" 
                                    href="#rev{{ $key }}" 
                                    aria-controls="revisi{{ $key }}" 
                                    role="tab" 
                                    aria-selected="false" 
                                    data-ajax-detail="true" 
                                    data-wo-code="{{ $header->prod_code }}">{{ $key == 0 ? 'Main':'Revision '.$key }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content">
                            @foreach( $headers as $key =>$header2 )
                                <div class="tab-pane {{ $key == 0 ? 'active':'' }}" id="rev{{ $key }}" aria-labelledby="revison{{ $key }}-tab" role="tabpanel">
                                    <form id="frmAdd{{ $key }}" name="frmAdd{{ $key }}" autocomplete="off">
                                        <div class="form-row">
                                            <div class="form-group col-md-2">
                                                <label for="prdNumber">Production Number</label>
                                                <input type="text" id="prdNumber" name="prdNumber" value="{{ $header2->prod_code  }}" class="form-control form-control-sm disabled-el" disabled />
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-2">
                                                <label for="wosDate">Date</label>
                                                <input type="text" id="wosDate" name="wosDate" value="{{ $header2->prod_date  }}" class="form-control"  placeholder="DD-MM-YYYY" disabled />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="shift">Shift</label>
                                                <select class="select2 form-control" id="shift" name="shift" disabled>
                                                    <option value=""></option>
                                                    <option value="pagi" {{ $header2->prod_shift == 'pagi' ? "selected" : "" }} >Pagi</option>
                                                    <option value="siang" {{ $header2->prod_shift == 'siang' ? "selected" : "" }} >Siang</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="group">Group</label>
                                                <select class="select2 form-control" id="group" name="group" disabled>
                                                    <option value=""></option>
                                                    <option value="A" {{ $header2->prod_group == 'A' ? "selected" : "" }} >A</option>
                                                    <option value="B" {{ $header2->prod_group == 'B' ? "selected" : "" }} >B</option>
                                                    <option value="C" {{ $header2->prod_group == 'C' ? "selected" : "" }} >C</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="wosTime">Start Time</label>
                                                <input type="text" id="wosTime" name="wosTime" value="{{ $header2->start_time  }}" class="form-control"  placeholder="HH:MM" disabled />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="workingHour">Working Hour</label>
                                                <input type="text" id="workingHour" name="workingHour" value="{{ $header2->working_hour  }}" class="form-control numeral-mask-satuan text-right" maxlength="2" disabled />
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="efficiency">Efficiency</label>
                                                <input type="text" id="efficiency" name="efficiency" value="{{ $header2->efficiency ? $header->efficiency : '95' }}" class="form-control numeral-mask-satuan text-right" maxlength="3" disabled />
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col-md-10">
                                                <label class="form-label" for="note">Notes</label>
                                                <textarea type="text" id="note" name="note" value="{{ $header2->note  }}" class="form-control" rows="1" disabled></textarea>
                                            </div>
                                        </div>
                                    </form>
                                    <hr>
                                    <h4 class="card-title">Article</h4>
                                    <div class="table-responsive main-table">
                                        <table class="table table-bordered w-100" >
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th >Urutan</th>
                                                    <th >So Number</th>
                                                    <th >Article Code</th>
                                                    <th class="text-right">Qty SO</th>
                                                    <th class="text-right">Qty Fresh</th>
                                                    <th class="text-right">Qty Repaint</th>
                                                    <th class="text-left">Waktu</th>
                                                    <th class="text-right">Tag</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            @foreach( $details as $key =>$item )
                                                @if($item->prod_code === $header2->prod_code )
                                                    <tr>
                                                        <td >{{ $item->urutan }}</td>
                                                        <td >{{ $item->so_code }}</td>
                                                        <td >{{ $item->article }}</td>
                                                        <td class="text-right">{{ number_format($item->so_qty) }}</td>
                                                        <td class="text-right">{{ number_format($item->act_qty_fresh) }}</td>
                                                        <td class="text-right">{{ number_format($item->act_qty_repaint) }}</td>
                                                        <td class="text-left">{{ $item->act_time_loading }}</td>
                                                        <td class="text-right">{{ $item->act_tag }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="col-md-10" style="padding-left:0">
                                        <div class="table-responsive main-table mt-75">
                                            <table class="table table-bordered w-100" >
                                                <tr>
                                                    <td rowspan="3">Total Tag</td>
                                                    <td>Waktu tersedia <span id="sumWorkHour"></span> x3600"x{{ $header2->efficiency ? $header->efficiency : '95' }}%</td>
                                                    <td class="text-right" id="sumTimeRequired{{ $key }}">{{ number_format($header2->sum_time_required) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Waktu Dibutuhkan</td>
                                                    <td class="text-right" id="sumAvailableTime{{ $key }}">{{ number_format($header2->sum_available_time) }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Sisa Waktu</td>
                                                    <td class="text-right" id="sumRemainTime{{ $key }}">{{ number_format($header2->sum_time_required-$header2->sum_available_time-10) }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                    <br>
                                    <a href="{{ route('workingOrderSheets.index') }}" class="btn btn-light">Back</a>
                                    <a href="{{ route('workingOrderSheet.print', ['id'=>Crypt::encryptString($header2->id)]) }}" target="_blank" type="button" class="btn btn-primary">
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
</style>
@endsection
@section('scripts')
{{-- @include('bom.addArticle') --}}
<script type="text/javascript">
    let currentDate = todayDate('dd-mm-yyyy');    
    $(document).ready(function(){           
        mask_thousand_digit(numberOfDecimalDigit);
    });
       
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

</script>
@endsection