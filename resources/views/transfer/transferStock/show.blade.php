@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
    $statusList = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
    $hasRev     = $revisions->count() > 0;
@endphp

<section id="show-index">

    {{-- ══════════════ TAB SELECTOR ══════════════ --}}
    @if($hasRev)
    <div class="rev-tabbar">
        <ul class="nav nav-tabs rev-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#pane-current" role="tab">
                    <i data-feather="file-text"></i>
                    <span>Current</span>
                    <span class="rev-chip rev-chip-live">Rev {{ $header->num_revision }}</span>
                </a>
            </li>
            @foreach($revisions->sortByDesc('num_revision') as $rev)
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#pane-rev{{ $rev->num_revision }}" role="tab">
                    <i data-feather="rotate-ccw"></i>
                    <span>Revisi {{ $rev->num_revision }}</span>
                    <span class="rev-chip">{{ \Carbon\Carbon::parse($rev->revised_at)->format('d M') }}</span>
                </a>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="tab-content">

    {{-- ══════════════════════════════════════════════ --}}
    {{-- PANE: CURRENT                                  --}}
    {{-- ══════════════════════════════════════════════ --}}
    <div class="tab-pane active" id="pane-current" role="tabpanel">

        @include('transfer.transferStock._diff-summary', [
            'diff'      => $diffs['current'] ?? null,
            'diffLabel' => 'Revisi ' . ($revisions->max('num_revision') ?? 0)
        ])

        {{-- ────── CARD HEADER ────── --}}
        <div class="form-row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Status: <span id="statusText">{{ $statusTr }}</span></h4>
                        <div class="heading-elements">
                            <ul class="list-inline mb-0">
                                <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-content collapse show">
                        <div class="card-body">
                            <form autocomplete="off">
                                @csrf
                                <div class="form-row">
                                    <div class="form-group col-md-2">
                                        <label>Transfer Number</label>
                                        <input type="text" value="{{ $header->tr_number }}" class="form-control" disabled />
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>Transfer Date</label>
                                        <input type="text" value="{{ $header->tr_date }}" class="form-control" disabled />
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label>Penerima</label>
                                        <input type="text" value="{{ $header->penerima }}" class="form-control" disabled />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label class="form-label">Location From</label>
                                        <input type="text" class="form-control" value="{{ $header->location_name }}" disabled />
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label class="form-label">Location To</label>
                                        <input type="text" class="form-control" value="{{ $header->location_name_to }}" disabled />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-8">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" rows="3" disabled>{{ $header->note }}</textarea>
                                    </div>
                                </div>
                            </form>

                            <div class="audit-strip">
                                <div class="audit-item">
                                    <div class="audit-ico bg-light-primary"><i data-feather="edit-3"></i></div>
                                    <div>
                                        <div class="audit-lbl">Created By</div>
                                        <div class="audit-val">{{ $header->created_name ?? $header->created_by ?? '-' }}</div>
                                        <div class="audit-sub">{{ $header->created_at ?? '-' }}</div>
                                    </div>
                                </div>
                                <div class="audit-item">
                                    <div class="audit-ico {{ $header->authorized_by ? 'bg-light-success' : 'bg-light-secondary' }}">
                                        <i data-feather="{{ $header->authorized_by ? 'check-circle' : 'clock' }}"></i>
                                    </div>
                                    <div>
                                        <div class="audit-lbl">Posted By</div>
                                        <div class="audit-val">{{ $header->authorized_name ?? $header->authorized_by ?? 'Belum diposting' }}</div>
                                        <div class="audit-sub">{{ $header->authorized_at ?? '-' }}</div>
                                    </div>
                                </div>
                                @if($header->num_revision > 0)
                                <div class="audit-item">
                                    <div class="audit-ico bg-light-warning"><i data-feather="rotate-ccw"></i></div>
                                    <div>
                                        <div class="audit-lbl">Last Updated By</div>
                                        <div class="audit-val">{{ $header->updated_by ?? '-' }}</div>
                                        <div class="audit-sub">{{ $header->updated_at ?? '-' }} &middot; Rev {{ $header->num_revision }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ────── CARD ARTICLE ────── --}}
        <div class="form-row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Article</h4></div>
                    <div class="card-body">
                        <hr>
                        <div class="container-list-item">
                            <div class="lebar-list-item">
                                <div class="form-row d-flex align-items-end d-none d-md-flex">
                                    <div class="col-md-3"><div class="form-group"><label class="font-weight-bold">Article Code</label></div></div>
                                    <div class="col-md-1"><div class="form-group"><label class="font-weight-bold text-right d-block">Min Package</label></div></div>
                                    <div class="col-md-1"><div class="form-group"><label class="font-weight-bold text-right d-block">QTY</label></div></div>
                                    <div class="col-md-1"><div class="form-group"><label class="font-weight-bold text-right d-block">UOM</label></div></div>
                                    @if($header->tr_type === 'Supply')
                                    <div class="col-md-2"><div class="form-group"><label class="font-weight-bold d-block">FG Target</label></div></div>
                                    @endif
                                    <div class="col-md-3"><div class="form-group"><label class="font-weight-bold d-block">Note</label></div></div>
                                </div>
                                <hr style="margin-top:0;">

                                @php $dc = $diffs['current'] ?? null; @endphp

                                @foreach($details as $item)
                                @php
                                    $rowCls = ''; $rowChg = null;
                                    if ($dc) {
                                        if (in_array($item->article_code, $dc['added'])) {
                                            $rowCls = 'row-added';
                                        } elseif (isset($dc['changed'][$item->article_code])) {
                                            $rowCls = 'row-changed';
                                            $rowChg = $dc['changed'][$item->article_code];
                                        }
                                    }
                                @endphp
                                <div class="tanda-baris {{ $rowCls }}">
                                    @if($rowCls)
                                    <span class="row-flag row-flag-{{ $rowCls === 'row-added' ? 'add' : 'mod' }}">
                                        {{ $rowCls === 'row-added' ? 'BARU' : 'DIUBAH' }}
                                    </span>
                                    @endif
                                    <div class="form-row d-flex align-items-center">
                                        <div class="col-md-3 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">Article</label>
                                                <input type="text" class="form-control"
                                                    value="{{ $item->article_alternative_code }} - {{ $item->article_desc }}" disabled />
                                            </div>
                                        </div>
                                        <div class="col-md-1 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">Min Package</label>
                                                <input type="text" class="form-control text-right font-weight-bold"
                                                    value="{{ number_format($item->min_package, 2) }}" disabled />
                                            </div>
                                        </div>
                                        <div class="col-md-1 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">QTY</label>
                                                <input type="text" class="form-control text-right"
                                                    value="{{ number_format($item->qty, 2) }}" disabled />
                                            </div>
                                        </div>
                                        <div class="col-md-1 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">UOM</label>
                                                <input type="text" class="form-control text-right"
                                                    value="{{ $item->uom }}" disabled />
                                            </div>
                                        </div>
                                        @if($header->tr_type === 'Supply')
                                        <div class="col-md-2 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">FG Target</label>
                                                <input type="text" class="form-control" value="{{ $item->fg_target ?? '-' }}" disabled />
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-md-3 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">Note</label>
                                                <input type="text" class="form-control" value="{{ $item->note }}" disabled />
                                            </div>
                                        </div>
                                    </div>
                                    @if($rowChg)
                                    <div class="row-diff">
                                        @foreach($rowChg as $field => $v)
                                        <span class="diff-pill diff-pill-sm">
                                            <span class="diff-pill-lbl">{{ strtoupper($field) }}</span>
                                            <span class="diff-old">{{ is_float($v['old']) ? number_format($v['old'],2) : Str::limit($v['old'],20) }}</span>
                                            <i data-feather="arrow-right"></i>
                                            <span class="diff-new">{{ is_float($v['new']) ? number_format($v['new'],2) : Str::limit($v['new'],20) }}</span>
                                        </span>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach

                                @if($dc)
                                    @foreach($dc['removed'] as $code => $gone)
                                    <div class="tanda-baris row-removed">
                                        <span class="row-flag row-flag-del">DIHAPUS</span>
                                        <div class="form-row d-flex align-items-center">
                                            <div class="col-md-3 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control"
                                                        value="{{ $gone->article_alternative_code }} - {{ $gone->article_desc }}" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control text-right"
                                                        value="{{ number_format($gone->min_package ?? 0, 2) }}" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control text-right"
                                                        value="{{ number_format($gone->qty, 2) }}" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control text-right" value="{{ $gone->uom }}" disabled />
                                                </div>
                                            </div>
                                            @if($header->tr_type === 'Supply')
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control" value="{{ $gone->fg_target ?? '-' }}" disabled />
                                                </div>
                                            </div>
                                            @endif
                                            <div class="col-md-3 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control" value="{{ $gone->note }}" disabled />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-end mt-75">
                            <div class="col-md-4">
                                <div class="form-group row mb-03">
                                    <label class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control text-right font-weight-bold"
                                            value="{{ $header->sum_row }}" disabled />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-row mt-75">
                            <div class="col-md-12">
                                <a href="{{ route('transferStock.index') }}" class="btn btn-light">Back</a>
                                <a href="{{ route('transferStock.print', ['id'=>Crypt::encryptString($header->id)]) }}"
                                    target="_blank" class="btn btn-primary">
                                    <i data-feather="printer"></i><span>{{ __("Print") }}</span>
                                </a>
                            </div>
                        </div>
                        <hr>
                        <div class="form-row card-statistics">
                            @foreach($approvalHistory as $val)
                            <div class="statistics-body">
                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                    <div class="media">
                                        <div class="avatar bg-light-{{ $val->status == true ? ($val->statusapprove == 1 ? 'success' : 'warning') : 'danger' }} mr-2">
                                            <div class="avatar-content">
                                                <i data-feather="{{ $val->status == true ? ($val->statusapprove == 1 ? 'check' : 'x') : 'x' }}" class="avatar-icon"></i>
                                            </div>
                                        </div>
                                        <div class="media-body my-auto">
                                            <h4 class="font-weight-bolder mb-0">
                                                {{ $val->status == true ? ($val->statusapprove == 1 ? 'Approve' : 'Decline') : 'Approve' }}-{{ $val->approval_order }}
                                            </h4>
                                            <p class="card-text mb-0">{{ $val->status == true ? $val->name : $val->petugas }}</p>
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

    {{-- ══════════════════════════════════════════════ --}}
    {{-- PANE: TIAP REVISI                              --}}
    {{-- ══════════════════════════════════════════════ --}}
    @foreach($revisions as $rev)
    @php
        $revDet = $revisionDetails->where('num_revision', $rev->num_revision);
        $dr     = $diffs[$rev->num_revision] ?? null;
    @endphp
    <div class="tab-pane" id="pane-rev{{ $rev->num_revision }}" role="tabpanel">

        {{-- banner alasan edit --}}
        <div class="rev-banner">
            <div class="rev-banner-ico"><i data-feather="rotate-ccw"></i></div>
            <div class="rev-banner-body">
                <div class="rev-banner-top">
                    <span class="rev-banner-title">Revisi {{ $rev->num_revision }}</span>
                    <span class="rev-banner-sep">&middot;</span>
                    <span class="rev-banner-meta">{{ $rev->revised_name ?? $rev->revised_by }}</span>
                    <span class="rev-banner-sep">&middot;</span>
                    <span class="rev-banner-meta">{{ $rev->revised_at }}</span>
                </div>
                <div class="rev-banner-reason">{{ $rev->edit_reason }}</div>
            </div>
            <div class="rev-banner-badge">Arsip</div>
        </div>

        @include('transfer.transferStock._diff-summary', [
            'diff'      => $dr,
            'diffLabel' => 'Revisi ' . ($rev->num_revision - 1)
        ])

        {{-- ────── CARD HEADER (REVISI) ────── --}}
        <div class="form-row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Status: <span>{{ $statusList[$rev->status] ?? '-' }}</span></h4>
                        <div class="heading-elements">
                            <ul class="list-inline mb-0">
                                <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-content collapse show">
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-2">
                                    <label>Transfer Number</label>
                                    <input type="text" value="{{ $rev->tr_number }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Transfer Date</label>
                                    <input type="text" value="{{ $rev->tr_date }}" class="form-control" disabled />
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Penerima</label>
                                    <input type="text" value="{{ $rev->penerima }}" class="form-control" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label">Location From</label>
                                    <input type="text" class="form-control" value="{{ $rev->location_name }}" disabled />
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label">Location To</label>
                                    <input type="text" class="form-control" value="{{ $rev->location_name_to }}" disabled />
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" rows="3" disabled>{{ $rev->note }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ────── CARD ARTICLE (REVISI) ────── --}}
        <div class="form-row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><h4 class="card-title">Article</h4></div>
                    <div class="card-body">
                        <hr>
                        <div class="container-list-item">
                            <div class="lebar-list-item">
                                <div class="form-row d-flex align-items-end d-none d-md-flex">
                                    <div class="col-md-3"><div class="form-group"><label class="font-weight-bold">Article Code</label></div></div>
                                    <div class="col-md-1"><div class="form-group"><label class="font-weight-bold text-right d-block">Min Package</label></div></div>
                                    <div class="col-md-1"><div class="form-group"><label class="font-weight-bold text-right d-block">QTY</label></div></div>
                                    <div class="col-md-1"><div class="form-group"><label class="font-weight-bold text-right d-block">UOM</label></div></div>
                                    @if($rev->tr_type === 'Supply')
                                    <div class="col-md-2"><div class="form-group"><label class="font-weight-bold d-block">FG Target</label></div></div>
                                    @endif
                                    <div class="col-md-3"><div class="form-group"><label class="font-weight-bold d-block">Note</label></div></div>
                                </div>
                                <hr style="margin-top:0;">

                                @forelse($revDet as $item)
                                @php
                                    $rowCls = ''; $rowChg = null;
                                    if ($dr) {
                                        if (in_array($item->article_code, $dr['added'])) {
                                            $rowCls = 'row-added';
                                        } elseif (isset($dr['changed'][$item->article_code])) {
                                            $rowCls = 'row-changed';
                                            $rowChg = $dr['changed'][$item->article_code];
                                        }
                                    }
                                @endphp
                                <div class="tanda-baris {{ $rowCls }}">
                                    @if($rowCls)
                                    <span class="row-flag row-flag-{{ $rowCls === 'row-added' ? 'add' : 'mod' }}">
                                        {{ $rowCls === 'row-added' ? 'BARU' : 'DIUBAH' }}
                                    </span>
                                    @endif
                                    <div class="form-row d-flex align-items-center">
                                        <div class="col-md-3 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">Article</label>
                                                <input type="text" class="form-control"
                                                    value="{{ $item->article_alternative_code }} - {{ $item->article_desc }}" disabled />
                                            </div>
                                        </div>
                                        <div class="col-md-1 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">Min Package</label>
                                                <input type="text" class="form-control text-right font-weight-bold"
                                                    value="{{ number_format($item->min_package ?? 0, 2) }}" disabled />
                                            </div>
                                        </div>
                                        <div class="col-md-1 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">QTY</label>
                                                <input type="text" class="form-control text-right"
                                                    value="{{ number_format($item->qty, 2) }}" disabled />
                                            </div>
                                        </div>
                                        <div class="col-md-1 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">UOM</label>
                                                <input type="text" class="form-control text-right" value="{{ $item->uom }}" disabled />
                                            </div>
                                        </div>
                                        @if($rev->tr_type === 'Supply')
                                        <div class="col-md-2 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">FG Target</label>
                                                <input type="text" class="form-control" value="{{ $item->fg_target ?? '-' }}" disabled />
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-md-3 col-12">
                                            <div class="form-group margin-nol">
                                                <label class="d-block d-md-none">Note</label>
                                                <input type="text" class="form-control" value="{{ $item->note }}" disabled />
                                            </div>
                                        </div>
                                    </div>
                                    @if($rowChg)
                                    <div class="row-diff">
                                        @foreach($rowChg as $field => $v)
                                        <span class="diff-pill diff-pill-sm">
                                            <span class="diff-pill-lbl">{{ strtoupper($field) }}</span>
                                            <span class="diff-old">{{ is_float($v['old']) ? number_format($v['old'],2) : Str::limit($v['old'],20) }}</span>
                                            <i data-feather="arrow-right"></i>
                                            <span class="diff-new">{{ is_float($v['new']) ? number_format($v['new'],2) : Str::limit($v['new'],20) }}</span>
                                        </span>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @empty
                                <div class="text-center text-muted py-2">Tidak ada detail pada revisi ini</div>
                                @endforelse

                                @if($dr)
                                    @foreach($dr['removed'] as $code => $gone)
                                    <div class="tanda-baris row-removed">
                                        <span class="row-flag row-flag-del">DIHAPUS</span>
                                        <div class="form-row d-flex align-items-center">
                                            <div class="col-md-3 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control"
                                                        value="{{ $gone->article_alternative_code }} - {{ $gone->article_desc }}" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control text-right"
                                                        value="{{ number_format($gone->min_package ?? 0, 2) }}" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control text-right"
                                                        value="{{ number_format($gone->qty, 2) }}" disabled />
                                                </div>
                                            </div>
                                            <div class="col-md-1 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control text-right" value="{{ $gone->uom }}" disabled />
                                                </div>
                                            </div>
                                            @if($rev->tr_type === 'Supply')
                                            <div class="col-md-2 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control" value="{{ $gone->fg_target ?? '-' }}" disabled />
                                                </div>
                                            </div>
                                            @endif
                                            <div class="col-md-3 col-12">
                                                <div class="form-group margin-nol">
                                                    <input type="text" class="form-control" value="{{ $gone->note }}" disabled />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-end mt-75">
                            <div class="col-md-4">
                                <div class="form-group row mb-03">
                                    <label class="col-sm-4 col-form-label titik-dua">Row(s)</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control text-right font-weight-bold"
                                            value="{{ $revDet->count() }}" disabled />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    </div>
</section>
@endsection

@section('styles')
<style>
    textarea { resize: none; }
    .mb-03 { margin-bottom: 0.3rem; }
    label.titik-dua::after { content: ":"; position: absolute; right: 1px; }
    .margin-nol { margin-bottom: 0.5rem; }

    /* ══ Tab bar ══ */
    .rev-tabbar {
        background: #fff;
        border: 1px solid #e4e8ee;
        border-radius: 6px;
        padding: 0 .5rem;
        margin-bottom: 1.2rem;
        box-shadow: 0 1px 2px rgba(34,41,47,.05);
    }
    .rev-tabs { border-bottom: 0; }
    .rev-tabs .nav-link {
        display: flex;
        align-items: center;
        gap: .45rem;
        border: 0;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        padding: .85rem .95rem;
        margin: 0;
        font-size: .8rem;
        font-weight: 600;
        color: #8b93a3;
        transition: color .15s ease, border-color .15s ease;
    }
    .rev-tabs .nav-link svg { width: 14px; height: 14px; }
    .rev-tabs .nav-link:hover { color: #5a6376; border-bottom-color: #dfe3ea; }
    .rev-tabs .nav-link.active { color: #7367f0; background: transparent; border-bottom-color: #7367f0; }
    .rev-chip {
        font-size: .62rem; font-weight: 700; letter-spacing: .03em;
        padding: .12rem .4rem; border-radius: 3px;
        background: #eef0f4; color: #7b8394;
    }
    .rev-tabs .nav-link.active .rev-chip { background: #ede9ff; color: #7367f0; }
    .rev-chip-live { background: #e5f8ed; color: #28c76f; }
    .rev-tabs .nav-link.active .rev-chip-live { background: #e5f8ed; color: #28c76f; }

    /* ══ Banner alasan edit ══ */
    .rev-banner {
        display: flex; align-items: flex-start; gap: .85rem;
        background: #fffdf6; border: 1px solid #f2e4c4;
        border-left: 3px solid #ff9f43;
        border-radius: 6px; padding: .9rem 1rem; margin-bottom: 1.2rem;
    }
    .rev-banner-ico {
        width: 32px; height: 32px; border-radius: 6px; flex-shrink: 0;
        background: #fff1e0; color: #ff9f43;
        display: flex; align-items: center; justify-content: center;
    }
    .rev-banner-ico svg { width: 15px; height: 15px; }
    .rev-banner-body { flex: 1; min-width: 0; }
    .rev-banner-top { display: flex; align-items: center; flex-wrap: wrap; gap: .4rem; margin-bottom: .3rem; }
    .rev-banner-title { font-size: .8rem; font-weight: 700; color: #2a3342; }
    .rev-banner-sep { color: #cbd2dd; font-size: .7rem; }
    .rev-banner-meta { font-size: .72rem; color: #8b93a3; }
    .rev-banner-reason { font-size: .84rem; color: #3d4655; line-height: 1.5; }
    .rev-banner-badge {
        flex-shrink: 0; align-self: center;
        font-size: .62rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
        padding: .2rem .5rem; border-radius: 3px;
        background: #f4f5f8; color: #9aa2b1; border: 1px solid #e6e9ef;
    }

    /* ══ Diff summary box ══ */
    .diff-box {
        background: #fff; border: 1px solid #e4e8ee; border-radius: 6px;
        margin-bottom: 1.2rem; overflow: hidden;
    }
    .diff-box-head {
        display: flex; align-items: center; gap: .45rem;
        padding: .6rem .9rem; background: #f7f9fb; border-bottom: 1px solid #eef1f5;
        font-size: .7rem; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; color: #8b93a3;
    }
    .diff-box-head svg { width: 13px; height: 13px; }
    .diff-box-body { display: flex; flex-wrap: wrap; gap: .45rem; padding: .8rem .9rem; }

    .diff-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .55rem; border-radius: 4px;
        background: #f4f5f8; border: 1px solid #e6e9ef;
        font-size: .72rem; line-height: 1.4;
    }
    .diff-pill svg { width: 11px; height: 11px; color: #a8b0bd; flex-shrink: 0; }
    .diff-pill-sm { font-size: .68rem; padding: .18rem .45rem; }
    .diff-pill-lbl {
        font-size: .6rem; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; color: #9aa2b1;
    }
    .diff-old { color: #ea5455; text-decoration: line-through; text-decoration-color: #f5b7b8; font-weight: 600; }
    .diff-new { color: #28c76f; font-weight: 700; }

    .diff-pill-add { background: #e9f9f0; border-color: #c8ecd8; color: #1f9d57; font-weight: 700; }
    .diff-pill-add svg { color: #28c76f; }
    .diff-pill-del { background: #fdeeee; border-color: #f7d2d3; color: #d13c3d; font-weight: 700; }
    .diff-pill-del svg { color: #ea5455; }
    .diff-pill-mod { background: #fff5e9; border-color: #f7e0c2; color: #d97d22; font-weight: 700; }
    .diff-pill-mod svg { color: #ff9f43; }

    /* ══ Penanda baris article ══ */
    .tanda-baris { position: relative; }
    .row-added, .row-changed, .row-removed {
        border-radius: 4px; padding: .4rem .5rem .3rem; margin-bottom: .3rem;
    }
    .row-added   { background: #f4fdf8; border-left: 3px solid #28c76f; }
    .row-changed { background: #fffbf5; border-left: 3px solid #ff9f43; }
    .row-removed { background: #fdf6f6; border-left: 3px solid #ea5455; opacity: .75; }
    .row-removed .form-control:disabled { text-decoration: line-through; color: #b64043; }

    .row-flag {
        position: absolute; top: -6px; right: 6px; z-index: 2;
        font-size: .55rem; font-weight: 800; letter-spacing: .09em;
        padding: .1rem .35rem; border-radius: 3px; color: #fff;
    }
    .row-flag-add { background: #28c76f; }
    .row-flag-mod { background: #ff9f43; }
    .row-flag-del { background: #ea5455; }

    .row-diff { display: flex; flex-wrap: wrap; gap: .3rem; padding: .1rem 0 .35rem .1rem; }

    /* ══ Audit strip ══ */
    .audit-strip {
        display: flex; flex-wrap: wrap; gap: 2rem;
        padding: .9rem 1rem; margin-top: .5rem;
        background: #f7f9fb; border: 1px solid #e3e7ee; border-radius: 6px;
    }
    .audit-item { display: flex; align-items: center; gap: .7rem; }
    .audit-ico {
        width: 34px; height: 34px; border-radius: 6px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .audit-ico svg { width: 16px; height: 16px; }
    .audit-lbl {
        font-size: .62rem; font-weight: 700; letter-spacing: .1em;
        text-transform: uppercase; color: #97a1b0; line-height: 1.3;
    }
    .audit-val { font-size: .82rem; font-weight: 700; color: #2a3342; line-height: 1.3; }
    .audit-sub { font-size: .68rem; color: #6b7688; line-height: 1.3; }

    @media screen and (min-device-width: 1200px) and (max-device-width: 1600px) {
        .lebar-list-item { width: 100%; }
        .container-list-item { max-width: 100%; overflow-x: auto; scrollbar-width: thin; margin-top: 7px; }
    }
    @media only screen and (min-width: 600px) and (max-width: 1200px) {
        .lebar-list-item { width: 200%; }
        .container-list-item { max-width: 100%; overflow-x: auto; scrollbar-width: thin; margin-top: 7px; }
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function () {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
            if (typeof feather !== 'undefined') feather.replace();
        });
    });
</script>
@endsection