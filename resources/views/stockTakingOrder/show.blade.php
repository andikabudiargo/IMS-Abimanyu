@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
    $statusMap   = $status;
    $statusBadge = [
        1 => 'badge-info',
        2 => 'badge-primary',
        3 => 'badge-success',
        5 => 'badge-danger',
        6 => 'badge-warning',
        7 => 'badge-warning',
        10 => 'badge-warning',
    ];
    $typeBadge = [
        'LOCATION' => ['label' => 'Lokasi',   'class' => 'badge-light-primary'],
        'SUPPLIER' => ['label' => 'Supplier', 'class' => 'badge-light-warning'],
        'CUSTOMER' => ['label' => 'Customer', 'class' => 'badge-light-info'],
    ];
@endphp

<style>
    #sto-show .label-xs {
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: #6b7688;
        margin-bottom: .2rem;
    }
    #sto-show .value-md {
        font-size: .95rem;
        font-weight: 600;
        color: #2b2f38;
    }
    #sto-show .card-header {
        background: #fafbfc;
        border-bottom: 1px solid #ebedf1;
    }
    #sto-show .card-header .card-title {
        font-size: 1rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    #sto-show .metric-card {
        border-radius: 8px;
        border: 1px solid #eceff3;
        height: 100%;
        transition: box-shadow .15s ease;
    }
    #sto-show .metric-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    #sto-show .metric-card .metric-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.1;
    }
    #sto-show .metric-card .metric-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
    }
    #sto-show .table-mapping th {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6b7688;
        white-space: nowrap;
        border-top: none;
        background: #fafbfc;
    }
    #sto-show .table-mapping td {
        vertical-align: middle;
        font-size: .82rem;
    }
    #sto-show .counter-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        font-size: .75rem;
        white-space: nowrap;
    }
    #sto-show .counter-chip .chip-badge {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .62rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
    }
    #sto-show .counter-chip.c1 .chip-badge { background: #7367f0; }
    #sto-show .counter-chip.c2 .chip-badge { background: #00cfe8; }
    #sto-show .counter-chip.c3 .chip-badge { background: #ff9f43; }
    #sto-show .counter-empty {
        font-size: .75rem;
        color: #b4b7bd;
        font-style: italic;
    }
    #sto-show .progress-cell {
        min-width: 110px;
    }
</style>

<section id="sto-show">
    <div class="form-row">

        {{-- ════ HEADER INFO ════ --}}
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="card-title">
                        <i data-feather="clipboard" style="width:18px;height:18px;"></i>
                        {{ $hdr->sto_code }}
                        <span class="badge {{ $statusBadge[$hdr->status] ?? 'badge-secondary' }} ml-50">
                            {{ $statusMap[$hdr->status] ?? '-' }}
                        </span>
                    </h4>
                    <div class="heading-elements">
                        <ul class="list-inline mb-0">
                            <li><a data-action="collapse"><i data-feather="chevron-down"></i></a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-content collapse show">
                    <div class="card-body pb-50">
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <div class="label-xs">STO Code</div>
                                <div class="value-md">{{ $hdr->sto_code }}</div>
                            </div>
                            <div class="form-group col-md-2">
                                <div class="label-xs">Periode</div>
                                <div class="value-md">{{ $hdr->periode }}</div>
                            </div>
                            <div class="form-group col-md-2">
                                <div class="label-xs">STO Type</div>
                                <div class="value-md">{{ $stoTypes[$hdr->sto_type] ?? $hdr->sto_type }}</div>
                            </div>
                            <div class="form-group col-md-2">
                                <div class="label-xs">Target Plan</div>
                                <div class="value-md">{{ number_format($hdr->target_plan, 2) }}%</div>
                            </div>
                            <div class="form-group col-md-2">
                                <div class="label-xs">Target Act</div>
                                <div class="value-md">{{ number_format($hdr->target_act, 2) }}%</div>
                            </div>
                            <div class="form-group col-md-2">
                                <div class="label-xs">Finish Time</div>
                                <div class="value-md">{{ $hdr->finish_time ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <div class="label-xs">Created By</div>
                                <div>{{ $hdr->created_name ?? $hdr->created_by }}</div>
                            </div>
                            @if($hdr->notes)
                            <div class="form-group col-md-9">
                                <div class="label-xs">Notes</div>
                                <div>{{ $hdr->notes }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════ MAPPING + PROGRESS ════ --}}
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="card-title">
                        <i data-feather="bar-chart-2" style="width:18px;height:18px;"></i>
                        Target &amp; Progress Counting
                    </h4>
                </div>
                <div class="card-body">

                    @if(count($mappings) === 0)
                        <div class="alert alert-warning mb-0">
                            <i data-feather="alert-triangle" class="mr-50" style="width:16px;height:16px;"></i>
                            Belum ada target yang dimapping.
                        </div>
                    @else

                    {{-- ringkasan keseluruhan --}}
                    @php
                        $totalLines      = collect($mappings)->sum('total_lines');
                        $totalMatch      = collect($mappings)->sum('match_lines');
                        $totalNotMatch   = collect($mappings)->sum('notmatch_lines');
                        $totalRecount    = collect($mappings)->sum('recount_lines');
                        $totalIncomplete = collect($mappings)->sum('incomplete_lines');
                        $pctDone = $totalLines > 0 ? round(($totalMatch / $totalLines) * 100) : 0;
                    @endphp

                    <div class="row mb-1">
                        <div class="col-lg-3 col-md-6 mb-1 mb-lg-0">
                            <div class="card metric-card mb-0">
                                <div class="card-body py-75 px-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="metric-label" style="color:#6b7688;">Total Baris</span>
                                        <span class="metric-value">{{ $totalLines }}</span>
                                    </div>
                                    <div class="progress mt-50" style="height:5px;">
                                        <div class="progress-bar bg-success" style="width:{{ $pctDone }}%"></div>
                                    </div>
                                    <div style="font-size:.68rem;color:#6b7688;margin-top:3px;">{{ $pctDone }}% MATCH</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6 col-6 mb-1 mb-lg-0">
                            <div class="card metric-card mb-0" style="border-color:#cfe6d8;">
                                <div class="card-body py-75 px-1 text-center">
                                    <div class="metric-label" style="color:#1f8a54;">Match</div>
                                    <div class="metric-value text-success">{{ $totalMatch }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6 col-6 mb-1 mb-lg-0">
                            <div class="card metric-card mb-0" style="border-color:#f5c2c7;">
                                <div class="card-body py-75 px-1 text-center">
                                    <div class="metric-label" style="color:#c0392b;">Not Match</div>
                                    <div class="metric-value text-danger">{{ $totalNotMatch }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6 col-6">
                            <div class="card metric-card mb-0" style="border-color:#f3e2c4;">
                                <div class="card-body py-75 px-1 text-center">
                                    <div class="metric-label" style="color:#d98a0b;">Recount</div>
                                    <div class="metric-value text-warning">{{ $totalRecount }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-6">
                            <div class="card metric-card mb-0">
                                <div class="card-body py-75 px-1 text-center">
                                    <div class="metric-label" style="color:#6b7688;">Incomplete</div>
                                    <div class="metric-value text-secondary">{{ $totalIncomplete }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-1">

                    {{-- tabel mapping per baris (bisa campur lokasi/partner) --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-mapping mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Sumber</th>
                                    <th>Target</th>
                                    <th>STO Date</th>
                                    <th>Counter</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center text-success">Match</th>
                                    <th class="text-center text-danger">Not Match</th>
                                    <th class="text-center text-warning">Recount</th>
                                    <th class="text-center text-secondary">Incomplete</th>
                                    <th class="text-center">Progress</th>
                                    <th>Finish Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mappings as $i => $m)
                                @php
                                    $pct = $m->total_lines > 0
                                        ? round(($m->match_lines / $m->total_lines) * 100)
                                        : 0;
                                    $barColor = $pct == 100 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                                    $tb = $typeBadge[$m->target_type] ?? ['label'=>$m->target_type,'class'=>'badge-light-secondary'];
                                    $counter3Name = $m->counter3_name ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><span class="badge {{ $tb['class'] }}">{{ $tb['label'] }}</span></td>
                                    <td class="font-weight-bold">{{ $m->target_name }}</td>
                                    <td>{{ $m->sto_date }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="counter-chip c1">
                                                <span class="chip-badge">1</span>
                                                {{ $m->counter1_name }}
                                            </span>
                                            @if($m->counter2_name)
                                                <span class="counter-chip c2 mt-25">
                                                    <span class="chip-badge">2</span>
                                                    {{ $m->counter2_name }}
                                                </span>
                                            @endif
                                            @if($counter3Name)
                                                <span class="counter-chip c3 mt-25">
                                                    <span class="chip-badge">3</span>
                                                    {{ $counter3Name }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center font-weight-bold">{{ $m->total_lines }}</td>
                                    <td class="text-center text-success font-weight-bold">{{ $m->match_lines }}</td>
                                    <td class="text-center text-danger font-weight-bold">{{ $m->notmatch_lines }}</td>
                                    <td class="text-center text-warning font-weight-bold">{{ $m->recount_lines }}</td>
                                    <td class="text-center text-secondary">{{ $m->incomplete_lines }}</td>
                                    <td class="progress-cell">
                                        <div class="d-flex align-items-center gap-1">
                                            <div class="progress flex-grow-1" style="height:7px;">
                                                <div class="progress-bar {{ $barColor }}" style="width:{{ $pct }}%"></div>
                                            </div>
                                            <small class="text-muted ml-25">{{ $pct }}%</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($m->finish_time)
                                            <span class="text-success">{{ $m->finish_time }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <hr>
                    <div class="form-row mt-75">
                        <div class="col-md-12">
                            <a href="{{ route('stockTakingOrder.index') }}" class="btn btn-light">Back</a>
                            @if(in_array($hdr->status, [1, 2]))
                            <a href="{{ route('stockTakingOrder.edit', ['id' => Crypt::encryptString($hdr->config_id)]) }}"
                               class="btn btn-warning">
                                <i data-feather="edit-2" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle">Edit</span>
                            </a>
                            <button type="button" class="btn btn-danger"
                                    onclick="cancelConfig('{{ Crypt::encryptString($hdr->config_id) }}')">
                                <i data-feather="x-circle" class="align-middle mr-sm-25 mr-0"></i>
                                <span class="align-middle">Cancel</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection

@section('scripts')
<script>

$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection