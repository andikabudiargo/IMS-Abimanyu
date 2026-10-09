<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style type="text/css">

        html {
            margin: 10px;
        }

        * {
            font-family: Verdana, Arial, sans-serif;
        }

        table{
            font-size: x-small;
        }

        table {
            width: 100%;
        }

        th {
            height: 30px;
        }
        td {
            height: 20px;
        }
        th, td {
            padding-left: 5px;
            padding-right: 5px;
        }

        .font-10 {
            font-size: 10px;
        }

        .font-9 {
            font-size: 9px;
        }

        .font-8 {
            font-size: 8px;
        }

        .header-padding{
            padding : 0 2px 0 2px;
        }

        .h-tengah{
            text-align:center;
        }

        .no-wrap{
            white-space: nowrap;
        }

        .border-garis{
            border-collapse: collapse;
        }

        .kotak-td{
            border: 1px solid rgb(9, 9, 9);
        }
    </style>
</head>
<body>
    <table width="100%" border="1" class="border-garis header-padding">
        <tr>
            <td width="30%" rowspan="4" class="no-wrap h-tengah">
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;">
            </td>
            <td width="40%" rowspan="4" class="no-wrap h-tengah" style="text-align:center"><h2>ACTUAL FINISH GOODS</h2>{{ $fgNumber }}</td>
            <td valign="" class="font-10 header-padding">Tanggal</td>
            <td valign="" class="font-10 header-padding">: {{ $header->fg_date_fmt }}</td>
        </tr>
        <tr>
            <td valign="" class="font-10 header-padding">Spray Booth</td>
            <td valign="" class="font-10 header-padding">: {{ $header->spray_booth_name }}</td>
        </tr>
        <tr>
            <td valign="" class="font-10 header-padding">Revisi</td>
            <td valign="" class="font-10 header-padding">: {{ $header->num_revision }}</td>
        </tr>
        <tr>
            <td valign="" class="font-10 header-padding">Note</td>
            <td valign="" class="font-10 header-padding">: {{ $header->note }}</td>
        </tr>
    </table>
    <table width="100%" border="1" class="font-8 border-garis header-padding" style="margin-top:3px">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="16%">Article Code</th>
                <th>Article Desc</th>
                <th width="10%">Qty FG</th>
                <th width="10%">Qty OT</th>
                <th width="20%">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $val)
                <tr>
                    <td align="right">{{ ++$no }}</td>
                    <td align="left">{{ $val->article_alternative_code ?? $val->article_code }}</td>
                    <td align="left">{{ $val->article_desc }}</td>
                    <td align="right">{{ number_format($val->qty_fg) }}</td>
                    <td align="right">{{ number_format($val->qty_ot) }}</td>
                    <td align="left">{{ $val->note }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table border="0" class="font-8 border-garis header-padding" style="margin-top:20px">
        <tr>
            <td align="center" class="kotak-td">Dibuat</td>
            <td align="center" class="kotak-td">Diperiksa</td>
            <td align="center" class="kotak-td">Disetujui</td>
        </tr>
        <tr>
            <td align="center" class="kotak-td" style="padding:30px"></td>
            <td align="center" class="kotak-td"></td>
            <td align="center" class="kotak-td"></td>
        </tr>
        <tr>
            <td align="center" class="kotak-td">{{ $header->created_by }}</td>
            <td align="center" class="kotak-td">Spv. Produksi</td>
            <td align="center" class="kotak-td">Manager Produksi</td>
        </tr>
    </table>
</body>
</html>
