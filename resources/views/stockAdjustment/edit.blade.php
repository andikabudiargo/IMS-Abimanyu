@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

<section id="adj-edit">
    <div class="form-row">

        {{-- ── HEADER CARD ─────────────────────────────────────────────── --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        Status:
                        @php
                            $badges = ['1'=>'badge-primary','2'=>'badge-info','3'=>'badge-warning','4'=>'badge-success','5'=>'badge-danger'];
                            $labels = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
                        @endphp
                        <span class="badge {{ $badges[$header->status] ?? 'badge-secondary' }}">
                            {{ $labels[$header->status] ?? '-' }}
                        </span>
                    </h4>
                    <input type="hidden" id="oEdit" value="1">
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
                                <div class="form-group col-md-3">
                                    <label for="adjCode">Adjustment Code</label>
                                    <input type="text" id="adjCode" name="adjCode"
                                        class="form-control disabled-el"
                                        value="{{ $header->adj_code }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="adjDate">Adjustment Date *</label>
                                    <input type="text" id="adjDate" name="adjDate"
                                        class="form-control" placeholder="DD-MM-YYYY"
                                        value="{{ $header->adj_date }}" required />
                                </div>
                                <div class="form-group col-md-1">
                                    <label class="form-label" for="periode">Periode *</label>
                                    <select class="select2 form-control" id="periode" name="periode">
                                        <option value=""></option>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}" {{ $header->periode == $i ? 'selected' : '' }}>
                                                {{ $i }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="adjType">Adjustment Type *</label>
                                    <select class="select2 form-control" id="adjType" name="adjType">
                                        <option value=""></option>
                                        @foreach($types as $val)
                                            <option value="{{ $val }}" {{ $header->adj_type === $val ? 'selected' : '' }}>
                                                {{ $val }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="location">Location *</label>
                                    <select class="select2 form-control" id="location" name="location">
                                        <option value=""></option>
                                        @foreach($locations as $val)
                                            <option value="{{ $val->location_code }}"
                                                {{ $header->location_code === $val->location_code ? 'selected' : '' }}>
                                                {{ $val->location_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="description">Description</label>
                                    <input type="text" id="description" name="description"
                                        class="form-control" maxlength="255"
                                        value="{{ $header->description }}" />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="form-label d-block">Direction *</label>
                                    <div class="btn-group btn-group-toggle" id="directionToggle" data-toggle="buttons">
                                        <label class="btn btn-outline-success {{ $header->direction === '+' ? 'active' : '' }}" id="btnDirPlus">
                                            <input type="radio" name="direction" id="dirPlus" value="+"
                                                {{ $header->direction === '+' ? 'checked' : '' }} autocomplete="off">
                                            Stock In &nbsp;(+)
                                        </label>
                                        <label class="btn btn-outline-danger {{ $header->direction === '-' ? 'active' : '' }}" id="btnDirMinus">
                                            <input type="radio" name="direction" id="dirMinus" value="-"
                                                {{ $header->direction === '-' ? 'checked' : '' }} autocomplete="off">
                                            Stock Out (−)
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-25">
                                        Direction berlaku untuk semua artikel dalam transaksi ini.
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── ARTICLE CARD ─────────────────────────────────────────────── --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Article Detail</h4>
                </div>
                <div class="card-body">
                    <div class="container-list-item">
                        <div class="lebar-list-item">
                            @include('stockAdjustment.headerColumn')
                            <div id="article_row"
                                style="max-height:22rem;overflow-x:hidden;scrollbar-width:thin;">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <button class="btn btn-primary" type="button" id="addNewRow"
                            onclick="add_new_row(); hitungGrandTotal();">
                            <i data-feather="plus" class="align-middle mr-sm-25 mr-0"></i>
                            <span class="align-middle d-sm-inline-block d-none">Add Article</span>
                        </button>
                    </div>

                    <div class="d-flex justify-content-between align-items-end mt-75">
                        <div class="col-md-4">
                            <div class="form-group row mb-03">
                                <label for="totalRow" class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control text-right font-weight-bold"
                                        id="totalRow" disabled />
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('stockAdjustment.show', ['id' => $encId]) }}" class="btn btn-light">
                                <i data-feather="arrow-left" class="mr-25"></i> Back
                            </a>
                            <button class="btn btn-info" type="button" id="cmdNew"
                                onclick="window.location.reload()">New</button>
                            <button class="btn btn-primary" type="button" id="cmdSave">Save</button>
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
    .mb-03   { margin-bottom: 0.3rem; }
    label.titik-dua::after { content:":"; position:absolute; right:1px; }
</style>
@endsection

@section('scripts')
@include('stockAdjustment.addArticle')
<script>
    /* existing rows dari DB */
    const existingRows = {!! json_encode($details) !!};

    document.querySelector('#cmdSave').addEventListener('click', () => {
        simpanData(true);   // oEdit = true → pakai route update
    });

    $(document).ready(function () {
        isiArticle('trArticle');

        $('#location').on('change', function () { refreshStockOnRows(); });
        $('#cmdNew,#cmdCancel').on('click', function () { window.location.reload(); });

        // Load existing rows setelah dataArticle siap
        let waitTimer = setInterval(() => {
            if (dataArticle !== null) {
                clearInterval(waitTimer);
                existingRows.forEach(row => {
                    add_new_row_edit(
                        row.article_code,
                        row.qty_adjustment,
                        row.uom,
                        row.uom_member,
                        row.notes,
                        row.stock_before,
                        '{{ $header->direction }}'
                    );
                });
                // refresh stock after rows loaded
                if ($('#location').val()) refreshStockOnRows();
            }
        }, 300);
    });

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
</script>
@endsection