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
    ];
    $typeBadge = [
        'LOCATION' => ['label' => 'Lokasi',   'class' => 'badge-light-primary'],
        'SUPPLIER' => ['label' => 'Supplier', 'class' => 'badge-light-warning'],
        'CUSTOMER' => ['label' => 'Customer', 'class' => 'badge-light-info'],
    ];
@endphp

<section id="sto-show">
    <div class="form-row">

        {{-- ════ HEADER INFO ════ --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
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
                                <label class="text-muted" style="font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">STO Code</label>
                                <div class="font-weight-bold">{{ $hdr->sto_code }}</div>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="text-muted" style="font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">Periode</label>
                                <div class="font-weight-bold">{{ $hdr->periode }}</div>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="text-muted" style="font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">STO Type</label>
                                <div class="font-weight-bold">{{ $stoTypes[$hdr->sto_type] ?? $hdr->sto_type }}</div>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="text-muted" style="font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">Target Plan</label>
                                <div class="font-weight-bold">{{ number_format($hdr->target_plan, 2) }}%</div>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="text-muted" style="font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">Target Act</label>
                                <div class="font-weight-bold">{{ number_format($hdr->target_act, 2) }}%</div>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="text-muted" style="font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">Finish Time</label>
                                <div class="font-weight-bold">{{ $hdr->finish_time ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label class="text-muted" style="font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">Created By</label>
                                <div>{{ $hdr->created_name ?? $hdr->created_by }}</div>
                            </div>
                            @if($hdr->notes)
                            <div class="form-group col-md-6">
                                <label class="text-muted" style="font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;">Notes</label>
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
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Target &amp; Progress Counting</h4>
                </div>
                <div class="card-body">

                    @if(count($mappings) === 0)
                        <div class="alert alert-warning">Belum ada target yang dimapping.</div>
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
                        <div class="col-md-3">
                            <div class="card border mb-0" style="border-radius:6px;">
                                <div class="card-body py-75 px-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7688;">Total Baris</span>
                                        <span class="font-weight-bold" style="font-size:1.1rem;">{{ $totalLines }}</span>
                                    </div>
                                    <div class="progress mt-50" style="height:5px;">
                                        <div class="progress-bar bg-success" style="width:{{ $pctDone }}%"></div>
                                    </div>
                                    <div style="font-size:.68rem;color:#6b7688;margin-top:3px;">{{ $pctDone }}% MATCH</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border mb-0" style="border-radius:6px;border-color:#cfe6d8 !important;">
                                <div class="card-body py-75 px-1 text-center">
                                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#1f8a54;">MATCH</div>
                                    <div class="font-weight-bold text-success" style="font-size:1.3rem;">{{ $totalMatch }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border mb-0" style="border-radius:6px;border-color:#f5c2c7 !important;">
                                <div class="card-body py-75 px-1 text-center">
                                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#c0392b;">NOT MATCH</div>
                                    <div class="font-weight-bold text-danger" style="font-size:1.3rem;">{{ $totalNotMatch }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border mb-0" style="border-radius:6px;border-color:#f3e2c4 !important;">
                                <div class="card-body py-75 px-1 text-center">
                                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#d98a0b;">RECOUNT</div>
                                    <div class="font-weight-bold text-warning" style="font-size:1.3rem;">{{ $totalRecount }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border mb-0" style="border-radius:6px;">
                                <div class="card-body py-75 px-1 text-center">
                                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7688;">INCOMPLETE</div>
                                    <div class="font-weight-bold text-secondary" style="font-size:1.3rem;">{{ $totalIncomplete }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-1">

                    {{-- tabel mapping per baris (bisa campur lokasi/partner) --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" style="font-size:.8rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Sumber</th>
                                    <th>Target</th>
                                    <th>STO Date</th>
                                    <th>Counter 1</th>
                                    <th>Counter 2</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center text-success">MATCH</th>
                                    <th class="text-center text-danger">NOT MATCH</th>
                                    <th class="text-center text-warning">RECOUNT</th>
                                    <th class="text-center text-secondary">INCOMPLETE</th>
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
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td><span class="badge {{ $tb['class'] }}">{{ $tb['label'] }}</span></td>
                                    <td class="font-weight-bold">{{ $m->target_name }}</td>
                                    <td>{{ $m->sto_date }}</td>
                                    <td>
                                        {{ $m->counter1_name }}
                                    </td>
                                    <td>
                                        @if($m->counter2_name)
                                            {{ $m->counter2_name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center font-weight-bold">{{ $m->total_lines }}</td>
                                    <td class="text-center text-success font-weight-bold">{{ $m->match_lines }}</td>
                                    <td class="text-center text-danger font-weight-bold">{{ $m->notmatch_lines }}</td>
                                    <td class="text-center text-warning font-weight-bold">{{ $m->recount_lines }}</td>
                                    <td class="text-center text-secondary">{{ $m->incomplete_lines }}</td>
                                    <td style="min-width:100px;">
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