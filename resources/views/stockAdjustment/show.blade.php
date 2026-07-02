@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
    $statusBadge = ['1'=>'badge-primary','2'=>'badge-info','3'=>'badge-warning','4'=>'badge-success','5'=>'badge-danger'];
    $statusLabel = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
    $st          = $header->status;
@endphp

<section id="adj-show">
    <div class="form-row">

           {{-- ── HEADER CARD ─────────────────────────────────────────────── 
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center flex-wrap gap-50">
                        <h4 class="card-title mb-0 mr-1">{{ $header->adj_code }}</h4>
                        <span class="badge {{ $statusBadge[$st] ?? 'badge-secondary' }} mr-50">
                            {{ $statusLabel[$st] ?? '-' }}
                        </span>
                        @if($header->direction === '+')
                            <span class="badge badge-light-success">
                                <i data-feather="trending-up" class="feather-14 mr-25"></i> Stock In (+)
                            </span>
                        @else
                            <span class="badge badge-light-danger">
                                <i data-feather="trending-down" class="feather-14 mr-25"></i> Stock Out (−)
                            </span>
                        @endif
                    </div>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div> --}}
 
                <div class="card-content collapse show">
                    <div class="card-body">
                        <form autocomplete="off">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Adjustment Code</label>
                                    <input type="text" class="form-control" value="{{ $header->adj_code }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Date</label>
                                    <input type="text" class="form-control" value="{{ $header->adj_date }}" disabled />
                                </div>
                                <div class="form-group col-md-1">
                                    <label>Periode</label>
                                    <input type="text" class="form-control" value="{{ $header->periode }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Adjustment Type</label>
                                    <input type="text" class="form-control" value="{{ $header->adj_type }}" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Location</label>
                                    <input type="text" class="form-control" value="{{ $header->location_name }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Description</label>
                                    <input type="text" class="form-control" value="{{ $header->description }}" disabled />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── DETAIL CARD ──────────────────────────────────────────────── --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article</h4>
                </div>
                <div class="card-body">
                    <hr>
                    <div class="container-list-item">
                        <div class="lebar-list-item">

                            {{-- Header kolom --}}
                            <div class="form-row d-flex align-items-end d-none d-md-flex">
                                <div class="col-md-6 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold">Article Code</label></div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold d-block text-center">UoM</label></div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold d-block text-right">Stock Before</label></div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold d-block text-right">Actual Balance</label></div>
                                </div>
                                <div class="col-md-1 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold d-block text-right">Qty Adjustment</label></div>
                                </div>
                                <div class="col-md-2 col-12 d-none d-md-block">
                                    <div class="form-group"><label class="font-weight-bold d-block">Notes</label></div>
                                </div>
                            </div>
                            <hr style="margin-top:0;">

                            {{-- Detail rows --}}
                            @foreach($details as $det)
                            <div class="tanda-baris">
                                <div class="form-row d-flex align-items-center">

                                    {{-- Article --}}
                                    <div class="col-md-6 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Article</label>
                                            <input type="text" class="form-control"
                                                value="{{ $det->article_alternative_code }} - {{ $det->article_desc }}"
                                                disabled />
                                        </div>
                                    </div>

                                    {{-- UoM --}}
                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">UoM</label>
                                            <input type="text" class="form-control text-center"
                                                value="{{ $det->uom }}" disabled />
                                        </div>
                                    </div>

                                    {{-- Stock Before --}}
                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Stock Before</label>
                                            <input type="text" class="form-control text-right"
                                                value="{{ number_format($det->stock_before, 2) }}" disabled />
                                        </div>
                                    </div>

                                    {{-- Qty Adjustment --}}
                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Qty Adjustment</label>
                                            <input type="text"
                                                class="form-control text-right font-weight-bold
                                                    {{ $header->direction === '+' ? 'text-success' : 'text-danger' }}"
                                                value="{{ ($header->direction === '+' ? '+' : '−') . number_format($det->qty_adjustment, 2) }}"
                                                disabled />
                                        </div>
                                    </div>

                                    {{-- Stock After --}}
                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Stock After</label>
                                            <input type="text"
                                                class="form-control text-right {{ $det->stock_after < 0 ? 'text-danger font-weight-bold' : '' }}"
                                                value="{{ number_format($det->stock_after, 2) }}"
                                                disabled />
                                        </div>
                                    </div>

                                    {{-- Notes --}}
                                    <div class="col-md-2 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Notes</label>
                                            <input type="text" class="form-control"
                                                value="{{ $det->notes ?? '' }}" disabled />
                                        </div>
                                    </div>

                                </div>
                            </div>
                            @endforeach

                        </div>
                    </div>
                    <hr>

                    {{-- Totals --}}
                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold"
                                        value="{{ $details->count() }}" disabled />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>

                    {{-- Action buttons --}}
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('stockAdjustment.index') }}" class="btn btn-light">Back</a>

                            @if(in_array($st, ['1','2','3']) && $canEdit)
                            <a href="{{ route('stockAdjustment.edit', ['id' => $encId]) }}" class="btn btn-warning">
                                <i data-feather="edit-2"></i> Edit
                            </a>
                            @endif

                            @if(in_array($st, ['1','2','3']) && $canPost)
                            <a href="javascript:;" class="btn btn-success"
                                data-size="sm" data-ajax-delete="true"
                                data-confirm="Yakin ingin posting?|Stok akan berubah setelah posting. Tidak bisa dibatalkan."
                                data-confirm-yes="document.getElementById('post-form-{{ $header->id }}').submit();"
                                data-modal-id="{{ $header->id }}"
                                data-url="{{ route('stockAdjustment.posting', ['id' => $encId]) }}">
                                <i data-feather="check-circle"></i> Posting
                            </a>
                            <form id="post-form-{{ $header->id }}" method="POST"
                                action="{{ route('stockAdjustment.posting', ['id' => $encId]) }}" class="d-none">
                                @csrf
                            </form>
                            @endif

                            <a href="{{ route('stockAdjustment.print', ['id' => $encId]) }}"
                                target="_blank" class="btn btn-primary">
                                <i data-feather="printer"></i> Print
                            </a>
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
    .badge-light-success { background-color: rgba(40,199,111,.12); color: #28c76f; }
    .badge-light-danger  { background-color: rgba(234,84,85,.12);  color: #ea5455; }
    textarea  { resize: none; }
    .mb-03    { margin-bottom: 0.3rem; }
    .margin-nol { margin-bottom: 0.5rem; }
    label.titik-dua::after { content: ":"; position: absolute; right: 1px; }
    .text-success { color: #28c76f !important; }
    .text-danger  { color: #ea5455 !important; }

    @media screen and (min-device-width: 1200px) and (max-device-width: 1600px) {
        .lebar-list-item    { width: 100%; }
        .container-list-item { max-width: 100%; overflow-x: auto; scrollbar-width: thin; margin-top: 7px; }
    }
    @media only screen and (min-width: 600px) and (max-width: 1200px) {
        .lebar-list-item    { width: 200%; }
        .container-list-item { max-width: 100%; overflow-x: auto; scrollbar-width: thin; margin-top: 7px; }
    }
</style>
@endsection