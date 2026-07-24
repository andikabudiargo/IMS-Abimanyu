@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
    $stMap = [
        '1' => ['label' => 'DRAFT',     'badge' => 'badge-primary'],
        '2' => ['label' => 'VALIDATED', 'badge' => 'badge-info'],
        '3' => ['label' => 'APPROVED',  'badge' => 'badge-warning'],
        '4' => ['label' => 'POSTED',    'badge' => 'badge-success'],
        '5' => ['label' => 'CANCELED',  'badge' => 'badge-danger'],
        '6' => ['label' => 'REVISED',   'badge' => 'badge-warning'],
    ];
    $st      = (string) $header->status;
    $stLabel = $stMap[$st]['label'] ?? '-';
    $stBadge = $stMap[$st]['badge'] ?? 'badge-secondary';

    $isDraft   = in_array($st, ['1','2','3']);
    $isLive    = in_array($st, ['4','6']);
    $isPending = $st === '6';

    // Arah ringkasan header (MIXED, +, -)
    $dirMap = [
        '+' => ['icon' => 'trending-up',   'cls' => 'badge-light-success', 'text' => 'Stock In (+)'],
        '-' => ['icon' => 'trending-down', 'cls' => 'badge-light-danger',  'text' => 'Stock Out (−)'],
    ];
    $dirLabel = isset($dirMap[$header->direction]) ? $dirMap[$header->direction] : ['icon' => 'shuffle', 'cls' => 'badge-light-warning', 'text' => 'Mixed'];
    $totalActualBalance = $details->sum('stock_after');
@endphp

<section id="adj-show">
    <div class="form-row">

        {{-- ── STATUS PENDING (REVISED belum diposting ulang) ─────────── --}}
        @if($isPending)
        <div class="col-12">
            <div class="alert alert-warning d-flex align-items-start p-1 mb-1">
                <i data-feather="clock" class="mr-75 mt-25 flex-shrink-0"></i>
                <div>
                    <strong>Menunggu Posting Revisi.</strong>
                    Angka di halaman ini adalah nilai revisi ke-<strong>{{ $header->rev_no }}</strong>
                    yang <strong>belum tercermin di stok</strong>.
                    Jalankan <em>Posting Revisi</em> dari halaman list untuk menerapkannya.
                </div>
            </div>
        </div>
        @endif

        {{-- ── HEADER CARD ──────────────────────────────────────────────── --}}
        <div class="col-md-12">
            <div class="card mb-1">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center flex-wrap" style="gap:.5rem">
                        <h4 class="card-title mb-0">{{ $header->adj_code }}</h4>
                        <span class="badge {{ $stBadge }}">{{ $stLabel }}</span>
                        @if($header->rev_no > 0)
                            <span class="badge badge-light-warning">Revision No. {{ $header->rev_no }}</span>
                        @endif
                    </div>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>

                <div class="card-content collapse show">
                    <div class="card-body pb-75">
                        <form autocomplete="off">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Adjustment Code</label>
                                    <input type="text" class="form-control disabled-el"
                                        value="{{ $header->adj_code }}" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Date</label>
                                    <input type="text" class="form-control disabled-el"
                                        value="{{ $header->adj_date }}" disabled />
                                </div>
                                <div class="form-group col-md-1">
                                    <label>Periode</label>
                                    <input type="text" class="form-control disabled-el"
                                        value="{{ $header->periode }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Adjustment Type</label>
                                    <input type="text" class="form-control disabled-el"
                                        value="{{ $header->adj_type }}" disabled />
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Location</label>
                                    <input type="text" class="form-control disabled-el"
                                        value="{{ $header->location_name }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Description</label>
                                    <input type="text" class="form-control disabled-el"
                                        value="{{ $header->description }}" disabled />
                                </div>
                                @if($header->note)
                                <div class="form-group col-md-6">
                                    <label>Note</label>
                                    <textarea class="form-control disabled-el" rows="1"
                                        disabled>{{ $header->note }}</textarea>
                                </div>
                                @endif
                            </div>

                            {{-- Meta: dibuat & diotorisasi --}}
                            <div class="form-row">
                                <div class="col-12">
                                    <small class="text-muted">
                                        Dibuat oleh <strong>{{ $header->created_by }}</strong>
                                        pada {{ date('d-m-Y H:i', strtotime($header->created_at)) }}
                                        @if($header->authorized_by)
                                            &nbsp;·&nbsp;
                                            Diposting oleh <strong>{{ $header->authorized_by }}</strong>
                                            pada {{ date('d-m-Y H:i', strtotime($header->authorized_at)) }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── DETAIL CARD ──────────────────────────────────────────────── --}}
        <div class="col-md-12">
            <div class="card mb-1">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Article Detail</h4>
                    <small class="text-muted">{{ $details->count() }} artikel</small>
                </div>
                <div class="card-body">

                    <div class="container-list-item">
                        <div class="lebar-list-item">

                            {{-- Header kolom --}}
                            <div class="form-row d-none d-md-flex align-items-end">
                                <div class="col-md-4"><label class="font-weight-bold mb-50">Article</label></div>
                                <div class="col-md-1 text-center"><label class="font-weight-bold mb-50">UoM</label></div>
                                <div class="col-md-1 text-right"><label class="font-weight-bold mb-50">Dir.</label></div>
                                <div class="col-md-1 text-right"><label class="font-weight-bold mb-50">Stock Before</label></div>
                                <div class="col-md-1 text-right"><label class="font-weight-bold mb-50">Qty Adj.</label></div>
                                <div class="col-md-1 text-right"><label class="font-weight-bold mb-50">Saldo Akhir</label></div>
                                <div class="col-md-3"><label class="font-weight-bold mb-50">Notes</label></div>
                            </div>
                            <hr class="mt-0 mb-50">

                            {{-- Rows --}}
                            @foreach($details as $det)
                            @php
                                $isPlus = $det->direction === '+';
                            @endphp
                            <div class="tanda-baris mb-50">
                                <div class="form-row d-flex align-items-center">

                                    <div class="col-md-4 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Article</label>
                                            <input type="text" class="form-control disabled-el"
                                                value="{{ $det->article_alternative_code }} — {{ $det->article_desc }}"
                                                disabled />
                                        </div>
                                    </div>

                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">UoM</label>
                                            <input type="text" class="form-control disabled-el text-center"
                                                value="{{ $det->uom }}" disabled />
                                        </div>
                                    </div>

                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Direction</label>
                                            <div class="text-center">
                                                @if($isPlus)
                                                    <span class="badge badge-light-success">+</span>
                                                @else
                                                    <span class="badge badge-light-danger">−</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Stock Before</label>
                                            <input type="text" class="form-control disabled-el text-right"
                                                value="{{ number_format($det->stock_before, 2) }}" disabled />
                                        </div>
                                    </div>

                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Qty Adjustment</label>
                                            <input type="text"
                                                class="form-control disabled-el text-right font-weight-bold
                                                    {{ $isPlus ? 'text-success' : 'text-danger' }}"
                                                value="{{ ($isPlus ? '+' : '−') . number_format($det->qty_adjustment, 2) }}"
                                                disabled />
                                        </div>
                                    </div>

                                    <div class="col-md-1 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Saldo Akhir</label>
                                            <input type="text"
                                                class="form-control disabled-el text-right
                                                    {{ $det->stock_after < 0 ? 'text-danger font-weight-bold' : '' }}"
                                                value="{{ number_format($det->stock_after, 2) }}" disabled />
                                        </div>
                                    </div>

                                    <div class="col-md-3 col-12">
                                        <div class="form-group margin-nol">
                                            <label class="d-block d-md-none">Notes</label>
                                            <input type="text" class="form-control disabled-el"
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
<div class="row mb-75">
    <div class="col-md-8">
        <div class="form-row">
            <div class="form-group row col-md-6 mb-03">
                <label class="col-sm-6 col-form-label titik-dua">Row(s)</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control text-right font-weight-bold"
                        value="{{ $details->count() }}" disabled />
                </div>
            </div>
            <div class="form-group row col-md-6 mb-03">
                <label class="col-sm-6 col-form-label titik-dua">Total Actual Balance</label>
                <div class="col-sm-6">
                    <input type="text"
                        class="form-control text-right font-weight-bold {{ $totalActualBalance < 0 ? 'text-danger' : '' }}"
                        value="{{ number_format($totalActualBalance, 2) }}" disabled />
                </div>
            </div>
        </div>
    </div>
</div>

                    <hr>

                    {{-- Action buttons --}}
                    <div class="form-row mt-75">
                        <div class="col-12 d-flex flex-wrap" style="gap:.5rem">
                            <a href="{{ route('stockAdjustment.index') }}" class="btn btn-light">
                                <i data-feather="arrow-left" class="mr-25"></i> Back
                            </a>

                            {{-- Edit — hanya draft --}}
                            @if($isDraft && ($canEdit ?? false))
                            <a href="{{ route('stockAdjustment.edit', ['id' => $encId]) }}"
                               class="btn btn-warning">
                                <i data-feather="edit-2" class="mr-25"></i> Edit
                            </a>
                            @endif

                            {{-- Revisi — sudah diposting --}}
                            @if($isLive && ($canRevise ?? false))
                            <a href="{{ route('stockAdjustment.edit', ['id' => $encId]) }}"
                               class="btn btn-warning">
                                <i data-feather="edit-3" class="mr-25"></i> Revisi
                            </a>
                            @endif

                            {{-- Posting baru — draft --}}
                            @if($isDraft && ($canPost ?? false))
                            <a href="javascript:;" class="btn btn-success"
                               data-size="sm" data-ajax-delete="true"
                               data-confirm="Yakin ingin posting?|Stok akan berubah setelah posting."
                               data-confirm-yes="document.getElementById('post-form-{{ $header->id }}').submit();"
                               data-modal-id="{{ $header->id }}"
                               data-url="{{ route('stockAdjustment.posting', ['id' => $encId]) }}">
                                <i data-feather="check-circle" class="mr-25"></i> Posting
                            </a>
                            <form id="post-form-{{ $header->id }}" method="POST"
                                  action="{{ route('stockAdjustment.posting', ['id' => $encId]) }}"
                                  class="d-none">@csrf</form>
                            @endif

                            {{-- Posting Revisi — status 6 --}}
                            @if($st === '6' && ($canPost ?? false))
                            <a href="javascript:;" class="btn btn-success"
                               data-size="sm" data-ajax-delete="true"
                               data-confirm="Posting revisi ini?|Hanya SELISIH terhadap posting sebelumnya yang diterapkan ke stok."
                               data-confirm-yes="document.getElementById('post-form-{{ $header->id }}').submit();"
                               data-modal-id="{{ $header->id }}"
                               data-url="{{ route('stockAdjustment.posting', ['id' => $encId]) }}">
                                <i data-feather="refresh-cw" class="mr-25"></i> Posting Revisi
                            </a>
                            @if(!$isDraft)
                            <form id="post-form-{{ $header->id }}" method="POST"
                                  action="{{ route('stockAdjustment.posting', ['id' => $encId]) }}"
                                  class="d-none">@csrf</form>
                            @endif
                            @endif

                            <a href="{{ route('stockAdjustment.print', ['id' => $encId]) }}"
                               target="_blank" class="btn btn-primary">
                                <i data-feather="printer" class="mr-25"></i> Print
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── REVISION HISTORY ─────────────────────────────────────────── --}}
        @if(isset($revisions) && $revisions->isNotEmpty())
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        Revision History
                        <span class="badge badge-light-warning ml-50">{{ $revisions->count() }}</span>
                    </h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body">
                        <ul class="timeline">
                        @foreach($revisions as $rev)
                        @php $isCancel = $rev->action === 'CANCEL'; @endphp
                        <li class="timeline-item">
                            <span class="timeline-point timeline-point-{{ $isCancel ? 'danger' : 'warning' }} timeline-point-indicator"></span>
                            <div class="timeline-event">
                                <div class="d-flex justify-content-between flex-sm-row flex-column mb-sm-0 mb-1">
                                    <div class="d-flex align-items-center" style="gap:.5rem">
                                        <span class="badge {{ $isCancel ? 'badge-light-danger' : 'badge-light-warning' }}">
                                            {{ $rev->action }}
                                        </span>
                                        <h6 class="mb-0">Rev.{{ $rev->rev_no }} — {{ $rev->revised_by }}</h6>
                                    </div>
                                    <span class="timeline-event-time">
                                        {{ date('d-m-Y H:i', strtotime($rev->revised_at)) }}
                                    </span>
                                </div>

                                <p class="mb-75 mt-25">
                                    <i data-feather="message-square" class="feather-12 mr-25 text-muted"></i>
                                    <em>{{ $rev->reason }}</em>
                                </p>

                                {{-- Perubahan header --}}
                                @foreach($rev->changes['header'] ?? [] as $c)
                                <div class="d-flex align-items-center mb-25" style="gap:.5rem;font-size:.8125rem">
                                    <span class="badge badge-light-secondary">{{ strtoupper($c['field']) }}</span>
                                    <span class="text-danger"><s>{{ $c['from'] ?: '—' }}</s></span>
                                    <i data-feather="arrow-right" class="feather-12 text-muted"></i>
                                    <span class="text-success">{{ $c['to'] ?: '—' }}</span>
                                </div>
                                @endforeach

                                {{-- Perubahan detail --}}
                                @if(!empty($rev->changes['detail']))
                                <div class="mt-50" style="font-size:.8125rem">
                                    @foreach($rev->changes['detail'] as $d)
                                    <div class="d-flex align-items-start mb-25 flex-wrap" style="gap:.375rem">
                                        <code class="text-muted" style="font-size:.75rem">{{ $articleMap[$d['article_code']] ?? $d['article_code'] }}</code>

                                        @if($d['type'] === 'ADDED')
                                            <span class="badge badge-light-success">Ditambah</span>
                                            <span class="text-muted">
                                                {{ $d['after']['qty_adjustment'] ?? '-' }}
                                                {{ $d['after']['direction'] ?? '' }}
                                                {{ $d['after']['uom'] ?? '' }}
                                            </span>

                                        @elseif($d['type'] === 'REMOVED')
                                            <span class="badge badge-light-danger">Dihapus</span>
                                            <span class="text-muted">
                                                {{ $d['before']['qty_adjustment'] ?? '-' }}
                                                {{ $d['before']['direction'] ?? '' }}
                                                {{ $d['before']['uom'] ?? '' }}
                                            </span>

                                        @else
                                            <span class="badge badge-light-secondary">Diubah</span>
                                            @foreach($d['fields'] as $f)
                                            <span>
                                                <span class="text-muted">{{ $f['field'] }}:</span>
                                                <span class="text-danger"><s>{{ $f['from'] }}</s></span>
                                                <i data-feather="arrow-right" class="feather-10 text-muted"></i>
                                                <span class="text-success">{{ $f['to'] }}</span>
                                            </span>
                                            @endforeach
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                            </div>
                        </li>
                        @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- /.form-row --}}
</section>

@endsection

@section('styles')
<style>
    .badge-light-success { background: rgba(40,199,111,.12); color:#28c76f; }
    .badge-light-danger  { background: rgba(234,84,85,.12);  color:#ea5455; }
    .badge-light-warning { background: rgba(255,159,67,.12); color:#ff9f43; }
    .badge-light-secondary { background: rgba(130,134,139,.12); color:#82868b; }
    .text-success { color:#28c76f !important; }
    .text-danger  { color:#ea5455 !important; }
    textarea { resize: none; }
    .mb-03   { margin-bottom: .3rem; }
    .margin-nol { margin-bottom: .5rem; }
    label.titik-dua::after { content:":"; position:absolute; right:1px; }

    @media screen and (min-width:1200px) and (max-width:1600px) {
        .lebar-list-item     { width:100%; }
        .container-list-item { max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
    @media only screen and (min-width:600px) and (max-width:1200px) {
        .lebar-list-item     { width:200%; }
        .container-list-item { max-width:100%; overflow-x:auto; scrollbar-width:thin; margin-top:7px; }
    }
</style>
@endsection