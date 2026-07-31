@extends('layouts.app')
@section('title', $title)
@section('content')
@include('layouts.breadcrumb')

@php
    $statusMap   = $status;
    $statusBadge = [
        1 => ['label' => $statusMap[1] ?? 'SCHEDULED', 'bg' => '#e7f1ff', 'fg' => '#3b7ddd'],
        2 => ['label' => $statusMap[2] ?? 'ONGOING',   'bg' => '#ece9ff', 'fg' => '#7367f0'],
        3 => ['label' => $statusMap[3] ?? 'COMPLETED', 'bg' => '#e3f6ec', 'fg' => '#28a745'],
        5 => ['label' => $statusMap[5] ?? 'CANCELED',  'bg' => '#fdecec', 'fg' => '#ea5455'],
    ];
    $sb = $statusBadge[$hdr->status] ?? ['label' => $statusMap[$hdr->status] ?? '-', 'bg' => '#eef0f3', 'fg' => '#6b7688'];

    $typeBadge = [
        'LOCATION' => ['label' => 'Lokasi',   'class' => 'badge-light-primary'],
        'SUPPLIER' => ['label' => 'Supplier', 'class' => 'badge-light-warning'],
        'CUSTOMER' => ['label' => 'Customer', 'class' => 'badge-light-info'],
    ];
@endphp

<style>
    /* ══════════ STO SHOW — enterprise polish ══════════ */
    #sto-show .card { border: 1px solid #ebedf1; border-radius: 10px; }

    /* — HERO HEADER — */
    #sto-show .sto-hero {
        background: linear-gradient(180deg, #fbfbfd 0%, #f6f7f9 100%);
        border-bottom: 1px solid #ebedf1;
        padding: 1.35rem 1.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 10px 10px 0 0;
    }
    #sto-show .sto-hero .hero-left { display: flex; align-items: center; gap: .85rem; }
    #sto-show .sto-hero .hero-icon {
        width: 44px; height: 44px; border-radius: 10px;
        background: #ece9ff; color: #7367f0;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    #sto-show .sto-hero .hero-code {
        font-size: 1.15rem; font-weight: 700; color: #2b2f38; line-height: 1.2;
    }
    #sto-show .sto-hero .hero-sub { font-size: .75rem; color: #8b909a; margin-top: 1px; }
    #sto-show .status-pill {
        display: inline-flex; align-items: center;
        font-size: .7rem; font-weight: 700; letter-spacing: .05em;
        padding: .3rem .7rem; border-radius: 999px; text-transform: uppercase;
    }

    /* — INFO GRID — */
    #sto-show .info-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 0;
        padding: 1.5rem 1.75rem 1.25rem;
    }
    #sto-show .info-grid .info-cell {
        padding: 0 1.25rem;
        border-left: 1px solid #eef0f3;
    }
    #sto-show .info-grid .info-cell:first-child { padding-left: 0; border-left: none; }
    #sto-show .info-label {
        font-size: .68rem; font-weight: 700; letter-spacing: .07em;
        text-transform: uppercase; color: #9aa0ab; margin-bottom: .4rem;
    }
    #sto-show .info-value { font-size: 1rem; font-weight: 700; color: #2b2f38; line-height: 1.25; }
    #sto-show .info-meta {
        display: flex; flex-wrap: wrap; gap: 2.5rem;
        padding: 0 1.75rem 1.5rem;
        border-top: 1px dashed #eef0f3; margin: 0 1.75rem;
        padding-top: 1.1rem;
    }
    #sto-show .info-meta .info-value { font-size: .9rem; font-weight: 600; }

    /* — SECTION HEAD — */
    #sto-show .section-head {
        display: flex; align-items: center; gap: .6rem;
        padding: 1.15rem 1.75rem; border-bottom: 1px solid #ebedf1;
    }
    #sto-show .section-head .sh-icon {
        width: 34px; height: 34px; border-radius: 8px;
        background: #eef6ff; color: #3b7ddd;
        display: flex; align-items: center; justify-content: center;
    }
    #sto-show .section-head .sh-title { font-size: 1rem; font-weight: 700; color: #2b2f38; }

    /* — METRIC STRIP — */
    #sto-show .metric-wrap { padding: 1.5rem 1.75rem 0; }
    #sto-show .metric-card {
        border-radius: 10px; border: 1px solid #eceff3; height: 100%;
        padding: 1rem 1.1rem; transition: box-shadow .15s ease, transform .15s ease;
        background: #fff;
    }
    #sto-show .metric-card:hover { box-shadow: 0 4px 14px rgba(45,50,80,.08); transform: translateY(-2px); }
    #sto-show .metric-card .m-label {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .07em; color: #9aa0ab;
    }
    #sto-show .metric-card .m-value { font-size: 1.7rem; font-weight: 700; line-height: 1.1; }
    #sto-show .metric-card.m-total .m-value { color: #2b2f38; }
    #sto-show .metric-card.m-accent { border-top: 3px solid transparent; }
    #sto-show .metric-card.m-match    { border-top-color: #28a745; }
    #sto-show .metric-card.m-notmatch { border-top-color: #ea5455; }
    #sto-show .metric-card.m-recount  { border-top-color: #ff9f43; }
    #sto-show .metric-card.m-incomplete { border-top-color: #b4b7bd; }

    /* — TABLE — */
    #sto-show .table-wrap { padding: 1.5rem 1.75rem; }
    #sto-show .table-mapping { margin-bottom: 0; }
    #sto-show .table-mapping thead th {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .05em; color: #9aa0ab; white-space: nowrap;
        border-top: none; border-bottom: 2px solid #eef0f3;
        background: #fafbfc; padding: .7rem .75rem;
    }
    #sto-show .table-mapping tbody td { vertical-align: middle; font-size: .82rem; padding: .8rem .75rem; }
    #sto-show .table-mapping tbody tr { border-bottom: 1px solid #f2f3f5; }
    #sto-show .table-mapping tbody tr:hover { background: #fafbff; }

    #sto-show .counter-chip { display: inline-flex; align-items: center; gap: .4rem; font-size: .78rem; white-space: nowrap; }
    #sto-show .counter-chip .chip-badge {
        width: 20px; height: 20px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .62rem; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    #sto-show .counter-chip.c1 .chip-badge { background: #7367f0; }
    #sto-show .counter-chip.c2 .chip-badge { background: #00cfe8; }
    #sto-show .counter-chip.c3 .chip-badge { background: #ff9f43; }

    #sto-show .progress-cell { min-width: 120px; }
    #sto-show .foot-actions { padding: 1.25rem 1.75rem; border-top: 1px solid #ebedf1; }

    @media (max-width: 991.98px) {
        #sto-show .info-grid { grid-template-columns: repeat(2, 1fr); gap: 1.25rem 0; }
        #sto-show .info-grid .info-cell { border-left: none; padding-left: 0; }
    }
</style>

<section id="sto-show">

    {{-- ════ HEADER CARD ════ --}}
    <div class="card">
        <div class="sto-hero">
            <div class="hero-left">
                <div class="hero-icon"><i data-feather="clipboard"></i></div>
                <div>
                    <div class="hero-code">{{ $hdr->sto_code }}</div>
                    <div class="hero-sub">{{ $stoTypes[$hdr->sto_type] ?? $hdr->sto_type }} &middot; Periode {{ $hdr->periode }}</div>
                </div>
            </div>
            <span class="status-pill" style="background:{{ $sb['bg'] }};color:{{ $sb['fg'] }};">{{ $sb['label'] }}</span>
        </div>

        <div class="info-grid">
            <div class="info-cell">
                <div class="info-label">STO Code</div>
                <div class="info-value">{{ $hdr->sto_code }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Periode</div>
                <div class="info-value">{{ $hdr->periode }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">STO Type</div>
                <div class="info-value">{{ $stoTypes[$hdr->sto_type] ?? $hdr->sto_type }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Target Plan</div>
                <div class="info-value">{{ number_format($hdr->target_plan, 2) }}%</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Target Act</div>
                <div class="info-value">{{ number_format($hdr->target_act, 2) }}%</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Finish Time</div>
                <div class="info-value">{{ $hdr->finish_time ?? '-' }}</div>
            </div>
        </div>

        <div class="info-meta">
            <div>
                <div class="info-label">Created By</div>
                <div class="info-value">{{ $hdr->created_name ?? $hdr->created_by }}</div>
            </div>
            @if($hdr->notes)
            <div style="flex:1;min-width:200px;">
                <div class="info-label">Notes</div>
                <div class="info-value">{{ $hdr->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ════ MAPPING + PROGRESS ════ --}}
    <div class="card">
        <div class="section-head">
            <div class="sh-icon"><i data-feather="bar-chart-2" style="width:18px;height:18px;"></i></div>
            <div class="sh-title">Target &amp; Progress Counting</div>
        </div>

        @if(count($mappings) === 0)
            <div class="table-wrap">
                <div class="alert alert-warning mb-0">
                    <i data-feather="alert-triangle" class="mr-50" style="width:16px;height:16px;"></i>
                    Belum ada target yang dimapping.
                </div>
            </div>
        @else

        @php
            $totalLines      = collect($mappings)->sum('total_lines');
            $totalMatch      = collect($mappings)->sum('match_lines');
            $totalNotMatch   = collect($mappings)->sum('notmatch_lines');
            $totalRecount    = collect($mappings)->sum('recount_lines');
            $totalIncomplete = collect($mappings)->sum('incomplete_lines');
            $pctDone = $totalLines > 0 ? round(($totalMatch / $totalLines) * 100) : 0;
        @endphp

        {{-- metric strip --}}
        <div class="metric-wrap">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-1 mb-lg-0">
                    <div class="metric-card m-total">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="m-label">Total Baris</span>
                            <span class="m-value">{{ $totalLines }}</span>
                        </div>
                        <div class="progress mt-1" style="height:6px;border-radius:6px;">
                            <div class="progress-bar bg-success" style="width:{{ $pctDone }}%"></div>
                        </div>
                        <div style="font-size:.7rem;color:#9aa0ab;margin-top:5px;font-weight:600;">{{ $pctDone }}% MATCH</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-6 mb-1 mb-lg-0">
                    <div class="metric-card m-accent m-match text-center">
                        <div class="m-label">Match</div>
                        <div class="m-value text-success mt-25">{{ $totalMatch }}</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-6 mb-1 mb-lg-0">
                    <div class="metric-card m-accent m-notmatch text-center">
                        <div class="m-label">Not Match</div>
                        <div class="m-value text-danger mt-25">{{ $totalNotMatch }}</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-6">
                    <div class="metric-card m-accent m-recount text-center">
                        <div class="m-label">Recount</div>
                        <div class="m-value text-warning mt-25">{{ $totalRecount }}</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 col-6">
                    <div class="metric-card m-accent m-incomplete text-center">
                        <div class="m-label">Incomplete</div>
                        <div class="m-value text-secondary mt-25">{{ $totalIncomplete }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- table --}}
        <div class="table-wrap">
            <div class="table-responsive">
                <table class="table table-hover table-mapping">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Sumber</th>
                            <th>Target</th>
                            <th>STO Date</th>
                            <th>Counter</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Match</th>
                            <th class="text-center">Not Match</th>
                            <th class="text-center">Recount</th>
                            <th class="text-center">Incomplete</th>
                            <th class="text-center">Progress</th>
                            <th>Finish Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mappings as $i => $m)
                        @php
                            $pct = $m->total_lines > 0 ? round(($m->match_lines / $m->total_lines) * 100) : 0;
                            $barColor = $pct == 100 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                            $tb = $typeBadge[$m->target_type] ?? ['label'=>$m->target_type,'class'=>'badge-light-secondary'];
                            $counter3Name = $m->counter3_name ?? null;
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td><span class="badge {{ $tb['class'] }}">{{ $tb['label'] }}</span></td>
                            <td class="font-weight-bold">{{ $m->target_name }}</td>
                            <td>{{ $m->sto_date }}</td>
                            <td>
                                <div class="d-flex flex-column" style="gap:.35rem;">
                                    <span class="counter-chip c1"><span class="chip-badge">1</span>{{ $m->counter1_name }}</span>
                                    @if($m->counter2_name)
                                        <span class="counter-chip c2"><span class="chip-badge">2</span>{{ $m->counter2_name }}</span>
                                    @endif
                                    @if($counter3Name)
                                        <span class="counter-chip c3"><span class="chip-badge">3</span>{{ $counter3Name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center font-weight-bold">{{ $m->total_lines }}</td>
                            <td class="text-center text-success font-weight-bold">{{ $m->match_lines }}</td>
                            <td class="text-center text-danger font-weight-bold">{{ $m->notmatch_lines }}</td>
                            <td class="text-center text-warning font-weight-bold">{{ $m->recount_lines }}</td>
                            <td class="text-center text-secondary">{{ $m->incomplete_lines }}</td>
                            <td class="progress-cell">
                                <div class="d-flex align-items-center" style="gap:.5rem;">
                                    <div class="progress flex-grow-1" style="height:7px;border-radius:6px;">
                                        <div class="progress-bar {{ $barColor }}" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $pct }}%</small>
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
        </div>
        @endif

        <div class="foot-actions">
            <a href="{{ route('stockTakingOrder.index') }}" class="btn btn-light">Back</a>
            @if(in_array($hdr->status, [1, 2]))
            <a href="{{ route('stockTakingOrder.edit', ['id' => Crypt::encryptString($hdr->config_id)]) }}" class="btn btn-warning">
                <i data-feather="edit-2" class="align-middle mr-sm-25 mr-0"></i>
                <span class="align-middle">Edit</span>
            </a>
            <button type="button" class="btn btn-danger" onclick="cancelConfig('{{ Crypt::encryptString($hdr->config_id) }}')">
                <i data-feather="x-circle" class="align-middle mr-sm-25 mr-0"></i>
                <span class="align-middle">Cancel</span>
            </button>
            @endif
        </div>
    </div>

</section>
@endsection

@section('scripts')
<script>
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@endsection