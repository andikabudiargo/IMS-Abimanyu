@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')
<section id="edit-form">
    <div class="form-row">
        {{-- ====================== HEADER CARD ====================== --}}
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

                        {{-- TAB per Revisi --}}
                        <ul class="nav nav-tabs" role="tablist">
                            @foreach($headers as $key => $header)
                                <li class="nav-item">
                                    <a class="nav-link {{ $key == 0 ? 'active' : '' }}"
                                       data-toggle="tab"
                                       href="#rev{{ $key }}"
                                       role="tab"
                                       aria-selected="{{ $key == 0 ? 'true' : 'false' }}"
                                       data-po-number="{{ $header->bom_code }}">
                                        {{ $key == 0 ? 'Main' : 'Revision ' . $key }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content">
                            @foreach($headers as $key => $header2)
                                <div class="tab-pane {{ $key == 0 ? 'active' : '' }}"
                                     id="rev{{ $key }}" role="tabpanel">

                                    <form id="frmAdd{{ $key }}" autocomplete="off">

                                        {{-- BOM Number --}}
                                        <div class="form-row mt-1">
                                            <div class="form-group col-md-3">
                                                <label class="form-label">BOM Number</label>
                                                <input type="text" class="form-control form-control-sm"
                                                       value="{{ $header2->bom_code }}" disabled />
                                            </div>
                                        </div>

                                        {{-- Article FG + UOM --}}
                                        <div class="form-row">
                                            <div class="form-group col-md-5">
                                                <label class="form-label">Article Finish Goods</label>
                                                <input type="text" class="form-control"
                                                       value="{{ $header2->article }}" disabled />
                                            </div>
                                           <div class="form-group col-md-1">
    <label>UOM</label>
    <input type="text" class="form-control"
           value="{{ $header2->article_uom }}" disabled />
</div>
                                        </div>

                                        {{-- Raw Material (tepat di bawah Article FG, sama seperti create/edit) --}}
@php
    $uomFgShow = strtoupper(trim($header2->article_uom ?? ''));
    $rmForTab  = collect($rawMaterials)->where('bom_code', $header2->bom_code)->values();
    $firstRm   = $rmForTab->first();
@endphp

@if($uomFgShow === 'SET')
    {{-- MODE MULTI (uom == SET): baris per RM + UOM + QTY, disabled --}}
    @if($rmForTab->isEmpty())
        <p class="text-muted mb-0">No raw material data</p>
    @else
        @foreach($rmForTab as $rmItem)
            <div class="form-row">
                <div class="form-group col-md-4">
                    @if($loop->first)
                        <label class="form-label">Article Raw Material</label>
                    @endif
                    <input type="text" class="form-control"
                           value="{{ $rmItem->article_alternative_code }} - {{ $rmItem->article_desc }}"
                           disabled />
                </div>
                <div class="form-group col-md-1">
                    @if($loop->first)
                        <label>Qty</label>
                    @endif
                    <input type="text" class="form-control text-right"
                           value="{{ number_format($rmItem->qty ?? 1, $decimalPlaces) }}"
                           disabled />
                </div>
                <div class="form-group col-md-1">
                    @if($loop->first)
                        <label>UOM</label>
                    @endif
                    <input type="text" class="form-control"
                           value="{{ $rmItem->uom ?? '' }}"
                           disabled />
                </div>
                
            </div>
        @endforeach
    @endif
@else
    {{-- MODE TUNGGAL: satu field + UOM + Qty di sebelahnya --}}
    <div class="form-row">
        <div class="form-group col-md-4">
            <label class="form-label">Article Raw Material</label>
            <input type="text" class="form-control"
                   value="{{ $firstRm ? $firstRm->article_alternative_code . ' - ' . $firstRm->article_desc : '-' }}"
                   disabled />
        </div>
         <div class="form-group col-md-1">
            <label>Qty</label>
            <input type="text" class="form-control text-right"
                   value="{{ number_format($firstRm->qty ?? 1, $decimalPlaces) }}"
                   disabled />
        </div>
        <div class="form-group col-md-1">
            <label>UOM</label>
            <input type="text" class="form-control"
                   value="{{ $firstRm->uom ?? '' }}"
                   disabled />
        </div>
    </div>
@endif

                                        {{-- Customer --}}
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Customer</label>
                                                <input type="text" class="form-control"
                                                       value="{{ $header2->cust_name }}" disabled />
                                            </div>
                                        </div>

                                        {{-- Part No & Model --}}
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Part No</label>
                                                <input type="text" class="form-control"
                                                       value="{{ $header2->part_no }}" disabled />
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label>Model</label>
                                                <input type="text" class="form-control"
                                                       value="{{ $header2->model }}" disabled />
                                            </div>
                                        </div>

                                        {{-- Notes --}}
                                        <div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label>Notes</label>
                                                <textarea class="form-control" rows="1"
                                                          disabled>{{ $header2->note }}</textarea>
                                            </div>
                                        </div>

                                        {{-- Revision Reason (hanya muncul jika bukan Main) --}}
                                        @if($key > 0)
                                            <div class="form-row">
                                                <div class="form-group col-md-6">
                                                    <label>Revision Reason</label>
                                                    <input type="text" class="form-control"
                                                           value="{{ $header2->revision_reason }}" disabled />
                                                </div>
                                            </div>
                                        @endif

                                    </form>

                                    <hr>

                                    {{-- ====================== SPRAY BOOTH TABLE ====================== --}}
                                    <h4 class="card-title">Spray Booth</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm w-100">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Spray Booth</th>
                                                    <th>Tone</th>
                                                    <th class="text-right">Tack</th>
                                                    <th class="text-right">Pass Rate</th>
                                                    <th class="text-right">Pass Thru</th>
                                                    <th class="text-right">Cycle Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $sbNo = 1; @endphp
                                                @foreach($sprayBooths as $item1)
                                                    @if($item1->bom_code === $header2->bom_code)
                                                        <tr>
                                                            <td>{{ $sbNo++ }}</td>
                                                            <td>{{ $item1->spray_booth ? $arrSprayBooth[$item1->spray_booth] : '' }}</td>
                                                            <td>{{ $item1->tone ? $arrTone[$item1->tone] : '' }}</td>
                                                            <td class="text-right">{{ $item1->tack }}</td>
                                                            <td class="text-right">{{ $item1->pass_rate }}</td>
                                                            <td class="text-right">{{ $item1->pass_thru }}</td>
                                                            <td class="text-right">{{ $item1->cycle_time }}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                                @if($sbNo == 1)
                                                    <tr>
                                                        <td colspan="7" class="text-center text-muted">
                                                            No spray booth data
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>

                                    <hr>

                                    {{-- ====================== ARTICLE TABLE ====================== --}}
                                    <h4 class="card-title">Article</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm w-100">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>No</th>
                                                    <th>Tone</th>
                                                    <th>POS</th>
                                                    <th>Article Code</th>
                                                    <th>Log. UOM</th>
                                                    <th class="text-right">QTY</th>
                                                    <th>BOM UOM</th>
                                                    <th class="text-right">QTY Con.</th>
                                                    <th>UOM Con.</th>
                                                    <th>Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php $artNo = 1; @endphp
                                                @foreach($details as $item)
                                                    @if($item->bom_code === $header2->bom_code)
                                                        <tr>
                                                            <td>{{ $artNo++ }}</td>
                                                            <td>{{ $item->tone ? $arrTone[$item->tone] : '' }}</td>
                                                            <td>{{ $item->pos_name }}</td>
                                                            <td>{{ $item->article }}</td>
                                                            <td>{{ $item->original_uom }}</td>
                                                            <td class="text-right">
                                                                {{ number_format($item->qty, $decimalPlaces) }}
                                                            </td>
                                                            <td>{{ $item->uom }}</td>
                                                            <td class="text-right">
                                                                {{ number_format($item->qty * $item->factor_qty, $decimalPlaces) }}
                                                            </td>
                                                            <td>{{ $item->uom_con }}</td>
                                                            <td>{{ $item->type_name }}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                                @if($artNo == 1)
                                                    <tr>
                                                        <td colspan="10" class="text-center text-muted">
                                                            No article data
                                                        </td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>

                                    {{-- Summary Row & QTY --}}
                                    <div class="d-flex justify-content-between align-items-end mt-75 mb-1">
                                        <div>
                                            <span class="font-weight-bold">ROW:</span> {{ $header2->sum_row }} &nbsp;|&nbsp;
                                            <span class="font-weight-bold">QTY:</span> {{ $header2->sum_qty }}
                                        </div>
                                    </div>

                                    <hr>

                                    {{-- Action Buttons --}}
                                    <div class="form-row">
                                        <div class="col-md-12">
                                            <a href="{{ route('boms.index') }}"
                                               class="btn btn-light">Back</a>
                                            <a href="{{ route('bom.print', ['id' => Crypt::encryptString($header2->id)]) }}"
                                               target="_blank" class="btn btn-primary">
                                                <i data-feather="printer"></i>
                                                <span>{{ __('Print') }}</span>
                                            </a>
                                        </div>
                                    </div>

                                </div>{{-- end tab-pane --}}
                            @endforeach
                        </div>{{-- end tab-content --}}

                        <hr>

                        {{-- ====================== APPROVAL HISTORY ====================== --}}
                        <div class="form-row card-statistics">
                            @foreach($approvalHistory as $val)
                                <div class="statistics-body">
                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                        <div class="media">
                                            <div class="avatar {{ $val->status ? 'bg-light-success' : 'bg-light-danger' }} mr-2">
                                                <div class="avatar-content">
                                                    <i data-feather="{{ $val->status ? 'check' : 'x' }}"
                                                       class="avatar-icon"></i>
                                                </div>
                                            </div>
                                            <div class="media-body my-auto">
                                                <h4 class="font-weight-bolder mb-0">
                                                    Approve-{{ $val->approval_order }}
                                                </h4>
                                                <p class="card-text mb-0">
                                                    {{ $val->status ? $val->name : $val->petugas }}
                                                </p>
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
    </div>
</section>
@endsection

@section('styles')
<style>
    textarea { resize: none; }


</style>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function () {
        mask_thousand_digit(numberOfDecimalDigit);
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection