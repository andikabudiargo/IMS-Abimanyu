<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <title>{{ $hdr->adj_code }}</title>
    <style>
        @page { margin: 120px 25px 60px 25px; }

        header {
            position: fixed;
            top: -120px; left: 0; right: 0;
            height: 115px;
        }

        footer {
            position: fixed;
            bottom: -60px; left: 0; right: 0;
            height: 50px;
            font-size: x-small;
            border-top: 1px solid #ccc;
        }

        * { font-family: Verdana, Arial, sans-serif; }

        table { font-size: x-small; width: 100%; }

        .detail th {
            height: 30px;
            padding-left: 8px;
            padding-right: 8px;
            background-color: lightgray;
            border-top: 1px solid #aaa;
            border-bottom: 1px solid #aaa;
        }
        .detail td {
            height: 20px;
            padding-left: 8px;
            padding-right: 8px;
            border-bottom: 1px solid #e8e8e8;
        }
        .detail tfoot td {
            height: 22px;
            padding-left: 8px;
            padding-right: 8px;
            background-color: #f2f2f2;
            font-weight: bold;
            border-top: 1px solid #aaa;
        }
        .detail tbody tr:nth-child(even) td { background-color: #fafafa; }

        .c-in  { color: #155724; font-weight: bold; }
        .c-out { color: #721c24; font-weight: bold; }
        .c-neg { color: #721c24; font-weight: bold; }

        .pagenum:before { content: counter(page); }

        /* TTD */
        .ttd { width: 100%; margin-top: 40px; border-collapse: collapse; }
        .ttd td { border: none; text-align: center; padding-top: 6px; width: 33%; }
        .ttd-space { height: 45px; display: block; }
        .ttd-line { display: inline-block; min-width: 120px; border-bottom: 1px solid #333; }
        .ttd-role { font-size: x-small; color: #666; }

        /* Revision history */
        .rev-title { font-weight: bold; font-size: x-small; letter-spacing: .5px; color: #555; margin-bottom: 4px; }
        .rev th {
            height: 22px;
            padding-left: 6px; padding-right: 6px;
            background: #f2f2f2;
            border-top: 1px solid #aaa;
            border-bottom: 1px solid #aaa;
        }
        .rev td {
            height: 18px;
            padding-left: 6px; padding-right: 6px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
    </style>
</head>
<body>

@php
    $stLabel = [
        '1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED',
        '4'=>'POSTED','5'=>'CANCELED','6'=>'REVISED',
    ][$hdr->status] ?? '-';

    $totalIn = 0; $totalOut = 0;
    foreach ($details as $d) {
        if ($d->direction === '+') $totalIn  += (float)$d->qty_adjustment;
        else                       $totalOut += (float)$d->qty_adjustment;
    }
@endphp

{{-- ── HEADER ── --}}
<header>
    <table border="0">
        <tr>
            <td width="25%" valign="middle">
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}"
                     alt="logo" style="width:55%;">
            </td>
            <td valign="middle" style="text-align:center;">
                <h2 style="margin:0;">Stock Adjustment</h2>
            </td>
            <td width="25%"></td>
        </tr>
    </table>
    <hr style="margin:4px 0;">
    <table border="0">
        <tr>
            <td width="50%" valign="top">
                Number &nbsp;&nbsp;&nbsp;: {{ $hdr->adj_code }}
                @if(($hdr->rev_no ?? 0) > 0)
                    &nbsp;<b style="color:#856404;">[rev.{{ $hdr->rev_no }}]</b>
                @endif<br>
                Date &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $hdr->adj_date }}
                &nbsp;&nbsp; Periode : {{ $hdr->periode }}<br>
                Type &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $hdr->adj_type }}<br>
                Status &nbsp;&nbsp;&nbsp;: {{ $stLabel }}
            </td>
            <td width="50%" valign="top">
                Location &nbsp;: {{ $hdr->location_name }}<br>
                Desc. &nbsp;&nbsp;&nbsp;&nbsp;: {{ $hdr->description ?: '-' }}<br>
                @if($hdr->note)
                Note &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $hdr->note }}<br>
                @endif
            </td>
        </tr>
    </table>
</header>

{{-- ── FOOTER ── --}}
<footer>
    <table border="0">
        <tr>
            <td width="40%">Created By &nbsp;: {{ $hdr->created_by }}</td>
            <td width="40%">Posted By &nbsp;&nbsp;: {{ $hdr->authorized_by ?? '-' }}</td>
            <td width="20%" style="text-align:right;">Page: <span class="pagenum"></span></td>
        </tr>
    </table>
</footer>

{{-- ── CONTENT ── --}}
<main>
    <table class="detail">
        <thead>
            <tr>
                <th width="4%"  align="center">No</th>
                <th width="11%" align="left">Article Code</th>
                <th            align="left">Description</th>
                <th width="6%"  align="center">UoM</th>
                <th width="9%"  align="right">Stock Before</th>
                <th width="10%" align="right">Qty Adj.</th>
                <th width="9%"  align="right">Stock After</th>
                <th width="13%" align="left">Notes</th>
            </tr>
        </thead>
        <tbody>
        @php $no = 0; @endphp
        @foreach($details as $val)
        @php
            $isIn = $val->direction === '+';
            $sign = $isIn ? '+' : '−';
            $cls  = $isIn ? 'c-in' : 'c-out';
        @endphp
        <tr>
            <td align="right">{{ ++$no }}</td>
            <td>{{ $val->article_alternative_code }}</td>
            <td>{{ $val->article_desc }}</td>
            <td align="center">{{ $val->uom }}</td>
            <td align="right">{{ number_format($val->stock_before, 2) }}</td>
            <td align="right" class="{{ $cls }}">{{ $sign }}{{ number_format($val->qty_adjustment, 2) }}</td>
            <td align="right" class="{{ $val->stock_after < 0 ? 'c-neg' : '' }}">{{ number_format($val->stock_after, 2) }}</td>
            <td>{{ $val->notes ?: '-' }}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" align="right">Total Qty Adjustment</td>
                <td align="right">
                    @if($totalIn > 0)
                        <span class="c-in">+{{ number_format($totalIn, 2) }}</span>
                    @endif
                    @if($totalIn > 0 && $totalOut > 0) &nbsp;/&nbsp; @endif
                    @if($totalOut > 0)
                        <span class="c-out">−{{ number_format($totalOut, 2) }}</span>
                    @endif
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    {{-- TTD --}}
    <br><br>
    <table class="ttd">
        <tr>
            <td>
                Dibuat Oleh,
                <span class="ttd-space"></span>
                <div><span class="ttd-line">{{ $hdr->created_by }}</span></div>
                <div class="ttd-role">Created By</div>
            </td>
            <td>
                Diperiksa Oleh,
                <span class="ttd-space"></span>
                <div><span class="ttd-line">&nbsp;</span></div>
                <div class="ttd-role">Checked By</div>
            </td>
            <td>
                Disetujui Oleh,
                <span class="ttd-space"></span>
                <div><span class="ttd-line">{{ $hdr->authorized_by ?? '&nbsp;' }}</span></div>
                <div class="ttd-role">Authorized By</div>
            </td>
        </tr>
    </table>

    {{-- Revision History --}}
    @if(isset($revisions) && $revisions->isNotEmpty())
    <br>
    <hr style="border:none; border-top:1px dashed #ccc; margin:16px 0 8px;">
    <div class="rev-title">REVISION HISTORY</div>
    <table class="rev">
        <thead>
            <tr>
                <th width="4%"  align="center">Rev.</th>
                <th width="9%"  align="center">Action</th>
                <th width="22%" align="left">Reason</th>
                <th width="12%" align="left">By</th>
                <th width="14%" align="left">Date</th>
                <th align="left">Changes</th>
            </tr>
        </thead>
        <tbody>
        @foreach($revisions as $rev)
        <tr>
            <td align="center">{{ $rev->rev_no }}</td>
            <td align="center">{{ $rev->action ?? '-' }}</td>
            <td>{{ $rev->reason }}</td>
            <td>{{ $rev->revised_by }}</td>
            <td>{{ date('d-m-Y H:i', strtotime($rev->revised_at)) }}</td>
            <td>
                @foreach($rev->changes['header'] ?? [] as $c)
                    <b>{{ strtoupper($c['field']) }}</b>:
                    <s>{{ $c['from'] ?: '—' }}</s>&rarr;{{ $c['to'] ?: '—' }}&nbsp;
                @endforeach
                @foreach($rev->changes['detail'] ?? [] as $d)
                    [{{ $d['article_code'] }}]
                    @if($d['type'] === 'ADDED')
                        <b style="color:#155724">ditambah</b>
                    @elseif($d['type'] === 'REMOVED')
                        <b style="color:#721c24">dihapus</b>
                    @else
                        @foreach($d['fields'] as $f)
                            {{ $f['field'] }}: <s>{{ $f['from'] }}</s>&rarr;<b>{{ $f['to'] }}</b>&nbsp;
                        @endforeach
                    @endif
                @endforeach
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif

</main>
</body>
</html>