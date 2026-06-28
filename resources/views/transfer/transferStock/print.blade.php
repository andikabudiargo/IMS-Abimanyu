<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <title>{{ $trNumber }}</title>
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

        .detail th { height: 30px; }
        .detail td { height: 20px; }
        .detail th, .detail td {
            padding-left: 8px;
            padding-right: 8px;
            border-bottom: 1px solid #ddd;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: x-small;
            font-weight: bold;
        }
        .badge-supply   { background: #d1ecf1; color: #0c5460; }
        .badge-return   { background: #fff3cd; color: #856404; }
        .badge-mutasi   { background: #e2e3e5; color: #383d41; }
        .badge-rework   { background: #fde8d8; color: #7d3200; }
        .badge-qcpassed { background: #d4edda; color: #155724; }
        .badge-otscrap  { background: #f8d7da; color: #721c24; }

        .pagenum:before { content: counter(page); }
    </style>
</head>
<body>

<header>
    <table border="0">
        <tr>
            <td width="25%" valign="middle">
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}"
                    alt="logo" style="width:55%;">
            </td>
            <td valign="middle" style="text-align:center;">
                <h2 style="margin:0;">Stock Transfer</h2>
            </td>
            <td width="25%"></td>
        </tr>
    </table>
    <hr style="margin:4px 0;">
    <table border="0">
        <tr>
            <td width="50%" valign="top">
                Number &nbsp;&nbsp;&nbsp;: {{ $trNumber }}<br>
                Date &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $trDate }}<br>
                Status &nbsp;&nbsp;&nbsp;: {{ $status }}
                
            </td>
            <td width="50%" valign="top">
                From &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $locationFrom }}<br>
                To &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $locationTo }}<br>
                Note &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $keterangan }}
            </td>
        </tr>
    </table>
</header>

<footer>
    <table border="0">
        <tr>
            <td width="40%">Created By: {{ $createdBy }}</td>
            <td width="40%">Approved By: {{ $approved }}</td>
            <td width="20%" style="text-align:right;">
                Page: <span class="pagenum"></span>
            </td>
        </tr>
    </table>
</footer>

<main>
    <table class="detail">
        <thead style="background-color: lightgray;">
            <tr>
                <th width="4%"  align="center">No</th>
                <th width="12%" align="left">Article Code</th>
                <th align="left">Description</th>
                <th width="8%"  align="right">Qty</th>
                <th width="7%"  align="left">UOM</th>
                @if($trType === 'Supply')
                <th width="15%" align="left">FG Target</th>
                @endif
                <th width="15%" align="left">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $val)
            <tr>
                <td align="right">{{ ++$no }}</td>
                <td align="left">{{ $val->article_alternative_code }}</td>
                <td align="left">{{ $val->article_desc }}</td>
                <td align="right">{{ $val->qty * 1 }}</td>
                <td align="left">{{ $val->uom }}</td>
                @if($trType === 'Supply')
                <td align="left">
                    {{ $val->fg_alt_code ? $val->fg_alt_code.' - '.$val->fg_desc : '-' }}
                </td>
                @endif
                <td align="left">{{ $val->note }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</main>

</body>
</html>