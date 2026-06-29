<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <title>{{ $hdr->adj_code }}</title>
    <style>
        @page { margin: 130px 25px 70px 25px; }

        header {
            position: fixed;
            top: -130px; left: 0; right: 0;
            height: 125px;
        }

        footer {
            position: fixed;
            bottom: -70px; left: 0; right: 0;
            height: 60px;
            font-size: x-small;
            border-top: 1px solid #ccc;
            padding-top: 4px;
        }

        * { font-family: Verdana, Arial, sans-serif; font-size: x-small; }

        h2 { font-size: small; }

        table { width: 100%; border-collapse: collapse; }

        .detail thead th {
            height: 28px;
            background-color: lightgray;
            padding-left: 6px;
            padding-right: 6px;
            border-top: 1px solid #aaa;
            border-bottom: 1px solid #aaa;
        }
        .detail tbody td {
            height: 18px;
            padding-left: 6px;
            padding-right: 6px;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail tfoot td {
            height: 22px;
            padding-left: 6px;
            padding-right: 6px;
            background-color: #f2f2f2;
            font-weight: bold;
            border-top: 1px solid #aaa;
        }

        .badge {
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: bold;
        }
        .badge-in  { background: #d4edda; color: #155724; }
        .badge-out { background: #f8d7da; color: #721c24; }

        .col-right { text-align: right; }
        .col-center { text-align: center; }

        .pagenum:before { content: counter(page); }

        .ttd-table td { border: none; text-align: center; padding-top: 8px; }
    </style>
</head>
<body>

{{-- ── HEADER ─────────────────────────────────────────────────────── --}}
<header>
    <table>
        <tr>
            <td width="20%" valign="middle">
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}"
                     alt="logo" style="width:55%;">
            </td>
            <td valign="middle" style="text-align:center;">
                <h2 style="margin:0;">Stock Adjustment</h2>
            </td>
            <td width="20%"></td>
        </tr>
    </table>
    <hr style="margin:4px 0;">
    <table>
        <tr>
            <td width="50%" valign="top">
                Number &nbsp;&nbsp;&nbsp;: {{ $hdr->adj_code }}<br>
                Date &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $hdr->adj_date }}&nbsp;&nbsp;
                    Periode: {{ $hdr->periode }}<br>
                Type &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $hdr->adj_type }}<br>
                Status &nbsp;&nbsp;&nbsp;:
                @php $labels = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED']; @endphp
                {{ $labels[$hdr->status] ?? '-' }}
            </td>
            <td width="50%" valign="top">
                Location &nbsp;: {{ $hdr->location_name }}<br>
                Direction &nbsp;:
                    <span class="badge {{ $hdr->direction === '+' ? 'badge-in' : 'badge-out' }}">
                        {{ $hdr->direction === '+' ? 'Stock In (+)' : 'Stock Out (−)' }}
                    </span><br>
                Note &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $hdr->description ?? '-' }}
            </td>
        </tr>
    </table>
</header>

{{-- ── FOOTER ─────────────────────────────────────────────────────── --}}
<footer>
    <table>
        <tr>
            <td width="40%">Created By &nbsp;: {{ $hdr->created_by }}</td>
            <td width="40%">Authorized By : {{ $hdr->authorized_by ?? '-' }}</td>
            <td width="20%" style="text-align:right;">
                Page: <span class="pagenum"></span>
            </td>
        </tr>
    </table>
</footer>

{{-- ── CONTENT ─────────────────────────────────────────────────────── --}}
<main>
    <table class="detail">
        <thead>
            <tr>
                <th width="4%"  align="center">No</th>
                <th width="11%" align="left">Article Code</th>
                <th align="left">Description</th>
                <th width="6%"  align="center">UoM</th>
                <th width="9%"  align="right">Stock Before</th>
                <th width="10%" align="right">Qty Adjustment</th>
                <th width="9%"  align="right">Stock After</th>
                <th width="14%" align="left">Notes</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 0; $totalQty = 0; @endphp
            @foreach($details as $val)
            @php $totalQty += $val->qty_adjustment; @endphp
            <tr>
                <td align="right">{{ ++$no }}</td>
                <td align="left">{{ $val->article_alternative_code }}</td>
                <td align="left">{{ $val->article_desc }}</td>
                <td align="center">{{ $val->uom }}</td>
                <td align="right">{{ number_format($val->stock_before, 2) }}</td>
                <td align="right"
                    style="font-weight:bold; color:{{ $hdr->direction === '+' ? '#155724' : '#721c24' }}">
                    {{ $hdr->direction === '+' ? '+' : '−' }}{{ number_format($val->qty_adjustment, 2) }}
                </td>
                <td align="right"
                    style="{{ $val->stock_after < 0 ? 'color:#721c24;font-weight:bold;' : '' }}">
                    {{ number_format($val->stock_after, 2) }}
                </td>
                <td align="left">{{ $val->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" align="right">Total Qty Adjustment</td>
                <td align="right">
                    {{ $hdr->direction === '+' ? '+' : '−' }}{{ number_format($totalQty, 2) }}
                </td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    {{-- TTD ---------------------------------------------------------------- --}}
    <br><br>
    <table class="ttd-table" style="margin-top:30px;">
        <tr>
            <td width="33%">
                Dibuat Oleh,<br><br><br><br>
                <u>{{ $hdr->created_by }}</u><br>
                <small>Created By</small>
            </td>
            <td width="33%">
                Diperiksa Oleh,<br><br><br><br>
                ____________________<br>
                <small>Checked By</small>
            </td>
            <td width="33%">
                Disetujui Oleh,<br><br><br><br>
                <u>{{ $hdr->authorized_by ?? '____________________' }}</u><br>
                <small>Authorized By</small>
            </td>
        </tr>
    </table>
</main>

</body>
</html>