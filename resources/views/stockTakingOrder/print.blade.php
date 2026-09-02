<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>STO {{ $hdr->sto_code }}</title>
    <style>
        @page { margin: 24px 26px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            color: #2b2f38;
            margin: 0;
        }

        /* ── HEADER ── */
        .doc-header {
            width: 100%;
            border-bottom: 2px solid #2b2f38;
            padding-bottom: 9px;
            margin-bottom: 12px;
        }
        .doc-header table { width: 100%; border-collapse: collapse; }
        .doc-title { font-size: 16px; font-weight: bold; color: #2b2f38; }
        .doc-sub { font-size: 9px; color: #6b7280; margin-top: 2px; }
        .doc-status {
            font-size: 9px; font-weight: bold; letter-spacing: .5px;
            padding: 4px 12px; border-radius: 3px; text-transform: uppercase;
            border: 1px solid;
        }

        /* ── INFO GRID ── */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td {
            padding: 6px 10px;
            border: 1px solid #dfe2e8;
            vertical-align: top;
        }
        .info-label {
            font-size: 7.5px; font-weight: bold; text-transform: uppercase;
            letter-spacing: .4px; color: #9aa0ab; margin-bottom: 2px;
        }
        .info-value { font-size: 10.5px; font-weight: bold; color: #2b2f38; }

        /* ── METRIC STRIP ── */
        .metric-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .metric-table td {
            border: 1px solid #dfe2e8;
            padding: 7px 8px;
            text-align: center;
            width: 16.66%;
        }
        .metric-table .m-label {
            font-size: 7px; font-weight: bold; text-transform: uppercase;
            letter-spacing: .4px; color: #9aa0ab;
        }
        .metric-table .m-value { font-size: 14px; font-weight: bold; margin-top: 3px; }
        .m-total   .m-value { color: #2b2f38; }
        .m-match   .m-value { color: #1e7e34; }
        .m-notmatch .m-value { color: #c62828; }
        .m-recount .m-value { color: #b7791f; }
        .m-incomplete .m-value { color: #6b7280; }

        /* ── SECTION TITLE ── */
        .section-title {
            font-size: 11px; font-weight: bold; color: #2b2f38;
            margin: 0 0 6px; padding-bottom: 4px;
            border-bottom: 1px solid #dfe2e8;
        }

        /* ── MAIN TABLE ── */
        table.mapping-table { width: 100%; border-collapse: collapse; }
        table.mapping-table thead th {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            font-size: 7.5px; font-weight: bold; text-transform: uppercase;
            letter-spacing: .3px; color: #4b5563;
            padding: 6px 5px; text-align: left;
        }
        table.mapping-table tbody td {
            border: 1px solid #e5e7eb;
            padding: 5px;
            font-size: 8.5px;
            vertical-align: middle;
        }
        table.mapping-table tbody tr.group-parent-row td {
            background: #f8f9fb;
            font-weight: bold;
        }
        table.mapping-table tbody tr.group-child-row td {
            background: #fdfdfe;
        }
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-muted  { color: #9aa0ab; }
        .text-success { color: #1e7e34; }
        .text-danger  { color: #c62828; }
        .text-warning { color: #b7791f; }

        .accuracy-value { font-size: 10px; font-weight: bold; }
        .acc-good { color: #1e7e34; }
        .acc-mid  { color: #b7791f; }
        .acc-bad  { color: #c62828; }

        .counter-line { font-size: 8px; line-height: 1.4; }

        .doc-footer {
            margin-top: 16px; padding-top: 6px;
            border-top: 1px solid #dfe2e8;
            font-size: 7.5px; color: #9aa0ab;
        }
        .doc-footer table { width: 100%; }

        .indent-child { padding-left: 16px !important; color: #4b5563; }
    </style>
</head>
<body>

    {{-- ══════════ HEADER ══════════ --}}
    <div class="doc-header">
        <table>
            <tr>
                <td style="width: 70%; vertical-align: middle;">
                    <div class="doc-title">Stock Taking Order — {{ $hdr->sto_code }}</div>
                    <div class="doc-sub">{{ $stoTypes[$hdr->sto_type] ?? $hdr->sto_type }} &middot; Periode {{ $hdr->periode }}</div>
                </td>
                <td style="width: 30%; text-align: right; vertical-align: middle;">
                    @php
                        $statusColors = [
                            1 => ['bg' => '#e7f1ff', 'fg' => '#3b7ddd'],
                            2 => ['bg' => '#ece9ff', 'fg' => '#7367f0'],
                            3 => ['bg' => '#e3f6ec', 'fg' => '#28a745'],
                            5 => ['bg' => '#fdecec', 'fg' => '#ea5455'],
                        ];
                        $sc = $statusColors[$hdr->status] ?? ['bg' => '#eef0f3', 'fg' => '#6b7688'];
                        $statusLabel = $status[$hdr->status] ?? $hdr->status;
                    @endphp
                    <span class="doc-status" style="background:{{ $sc['bg'] }}; color:{{ $sc['fg'] }}; border-color:{{ $sc['fg'] }};">
                        {{ $statusLabel }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ══════════ INFO GRID ══════════ --}}
    <table class="info-table">
        <tr>
            <td style="width: 16.6%;">
                <div class="info-label">STO Code</div>
                <div class="info-value">{{ $hdr->sto_code }}</div>
            </td>
            <td style="width: 16.6%;">
                <div class="info-label">Periode</div>
                <div class="info-value">{{ $hdr->periode }}</div>
            </td>
            <td style="width: 16.6%;">
                <div class="info-label">STO Type</div>
                <div class="info-value">{{ $stoTypes[$hdr->sto_type] ?? $hdr->sto_type }}</div>
            </td>
            <td style="width: 16.6%;">
                <div class="info-label">Akurasi Plan</div>
                <div class="info-value">{{ number_format($hdr->target_plan, 2) }}%</div>
            </td>
            <td style="width: 16.6%;">
                <div class="info-label">Akurasi Actual</div>
                <div class="info-value">{{ number_format($hdr->target_act, 2) }}%</div>
            </td>
            <td style="width: 16.6%;">
                <div class="info-label">Finish Time</div>
                <div class="info-value">{{ $hdr->finish_time ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <div class="info-label">Created By</div>
                <div class="info-value">{{ $hdr->created_name ?? $hdr->created_by }}</div>
            </td>
            <td colspan="3">
                <div class="info-label">Notes</div>
                <div class="info-value">{{ $hdr->notes ?? '-' }}</div>
            </td>
        </tr>
    </table>

    {{-- ══════════ METRIC STRIP ══════════ --}}
    @php
        $totalLines      = collect($mappings)->sum('total_lines');
        $totalMatch      = collect($mappings)->sum('match_lines');
        $totalNotMatch   = collect($mappings)->sum('notmatch_lines');
        $totalRecount    = collect($mappings)->sum('recount_lines');
        $totalRecountTol = collect($mappings)->sum('recount_in_tolerance');
        $totalIncomplete = collect($mappings)->sum('incomplete_lines');
        $pctDone         = (float) $hdr->target_act;
    @endphp
    <table class="metric-table">
        <tr>
            <td class="m-total">
                <div class="m-label">Total Baris</div>
                <div class="m-value">{{ $totalLines }}</div>
            </td>
            <td class="m-match">
                <div class="m-label">Match</div>
                <div class="m-value">{{ $totalMatch }}</div>
            </td>
            <td class="m-notmatch">
                <div class="m-label">Not Match</div>
                <div class="m-value">{{ $totalNotMatch }}</div>
            </td>
            <td class="m-recount">
                <div class="m-label">Recount</div>
                <div class="m-value">{{ $totalRecount }}</div>
            </td>
            <td class="m-incomplete">
                <div class="m-label">Incomplete</div>
                <div class="m-value">{{ $totalIncomplete }}</div>
            </td>
            <td class="m-total">
                <div class="m-label">Akurasi Global</div>
                <div class="m-value">{{ number_format($pctDone, 2) }}%</div>
            </td>
        </tr>
    </table>

    {{-- ══════════ MAPPING TABLE ══════════ --}}
    <div class="section-title">Target &amp; Progress Counting</div>

    @if(count($mappings) === 0)
        <p class="text-muted">Belum ada target yang dimapping.</p>
    @else
        @php
            $groupedMappings = collect($mappings)->groupBy(function ($m) {
                return $m->parent_location ?: '__no_parent__';
            });

            $accClass = function ($pct) {
                if ($pct >= 98) return 'acc-good';
                if ($pct >= 50) return 'acc-mid';
                return 'acc-bad';
            };
        @endphp

        <table class="mapping-table">
            <thead>
                <tr>
                    <th style="width: 3%;">#</th>
                    <th style="width: 24%;">Target</th>
                    <th style="width: 8%;">STO Date</th>
                    <th style="width: 16%;">Counter</th>
                    <th style="width: 7%;" class="text-center">Total</th>
                    <th style="width: 7%;" class="text-center">Match</th>
                    <th style="width: 8%;" class="text-center">Not Match</th>
                    <th style="width: 8%;" class="text-center">Recount</th>
                    <th style="width: 8%;" class="text-center">Incomplete</th>
                    <th style="width: 8%;" class="text-center">Akurasi</th>
                    <th style="width: 10%;">Finish Time</th>
                </tr>
            </thead>
            <tbody>
                @php $rowNo = 0; @endphp
                @foreach($groupedMappings as $parentKey => $group)
                    @if($parentKey === '__no_parent__')
                        {{-- baris biasa --}}
                        @foreach($group as $m)
                            @php
                                $rowNo++;
                                $pct = (float) $m->target_act_loc;
                            @endphp
                            <tr>
                                <td class="text-center text-muted">{{ $rowNo }}</td>
                                <td><strong>{{ $m->target_name }}</strong></td>
                                <td>{{ $m->sto_date }}</td>
                                <td>
                                    <div class="counter-line">1. {{ $m->counter1_name }}</div>
                                    @if($m->counter2_name)<div class="counter-line">2. {{ $m->counter2_name }}</div>@endif
                                    @if($m->counter3_name)<div class="counter-line">3. {{ $m->counter3_name }}</div>@endif
                                </td>
                                <td class="text-center"><strong>{{ $m->total_lines }}</strong></td>
                                <td class="text-center text-success"><strong>{{ $m->match_lines }}</strong></td>
                                <td class="text-center text-danger"><strong>{{ $m->notmatch_lines }}</strong></td>
                                <td class="text-center text-warning">
                                    <strong>{{ $m->recount_lines }}</strong>
                                    @if($m->recount_in_tolerance > 0)
                                        <div style="font-size:6.5px; color:#9aa0ab;">({{ $m->recount_in_tolerance }} tol.)</div>
                                    @endif
                                </td>
                                <td class="text-center text-muted">{{ $m->incomplete_lines }}</td>
                                <td class="text-center">
                                    <span class="accuracy-value {{ $accClass($pct) }}">{{ number_format($pct, 1) }}%</span>
                                </td>
                                <td>{{ $m->finish_time ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @else
                        {{-- group parent + child --}}
                        @php
                            $rowNo++;
                            $parentName = $group->first()->parent_location_name ?? $parentKey;
                            $gTotal      = $group->sum('total_lines');
                            $gMatch      = $group->sum('match_lines');
                            $gNotMatch   = $group->sum('notmatch_lines');
                            $gRecount    = $group->sum('recount_lines');
                            $gRecountTol = $group->sum('recount_in_tolerance');
                            $gIncomplete = $group->sum('incomplete_lines');
                            $gAccurate   = $gMatch + $gRecountTol;
                            $gPct        = $gTotal > 0 ? round(($gAccurate / $gTotal) * 100, 2) : 0;
                            $gFinishTime = $group->pluck('finish_time')->filter()->sort()->last();
                        @endphp
                        <tr class="group-parent-row">
                            <td class="text-center text-muted">{{ $rowNo }}</td>
                            <td><strong>{{ $parentName }}</strong> <span class="text-muted">({{ $group->count() }} sub-lokasi)</span></td>
                            <td class="text-muted">-</td>
                            <td class="text-muted">-</td>
                            <td class="text-center">{{ $gTotal }}</td>
                            <td class="text-center text-success">{{ $gMatch }}</td>
                            <td class="text-center text-danger">{{ $gNotMatch }}</td>
                            <td class="text-center text-warning">
                                {{ $gRecount }}
                                @if($gRecountTol > 0)
                                    <div style="font-size:6.5px; color:#9aa0ab;">({{ $gRecountTol }} tol.)</div>
                                @endif
                            </td>
                            <td class="text-center text-muted">{{ $gIncomplete }}</td>
                            <td class="text-center">
                                <span class="accuracy-value {{ $accClass($gPct) }}">{{ number_format($gPct, 1) }}%</span>
                            </td>
                            <td>{{ $gFinishTime ?? '-' }}</td>
                        </tr>
                        @foreach($group as $m)
                            <tr class="group-child-row">
                                <td class="text-center text-muted">&middot;</td>
                                <td class="indent-child">{{ $m->target_name }}</td>
                                <td>{{ $m->sto_date }}</td>
                                <td>
                                    <div class="counter-line">1. {{ $m->counter1_name }}</div>
                                    @if($m->counter2_name)<div class="counter-line">2. {{ $m->counter2_name }}</div>@endif
                                    @if($m->counter3_name)<div class="counter-line">3. {{ $m->counter3_name }}</div>@endif
                                </td>
                                <td class="text-center">{{ $m->total_lines }}</td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center text-muted">-</td>
                                <td>{{ $m->finish_time ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ══════════ FOOTER ══════════ --}}
    <div class="doc-footer">
        <table>
            <tr>
                <td style="width:50%;">Dicetak oleh: {{ $printedBy }} &middot; {{ $printedAt }}</td>
                <td style="width:50%; text-align:right;">IMS &mdash; integrated Management System</td>
            </tr>
        </table>
    </div>

</body>
</html>