<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PR</title>
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
        
        tfoot tr td{
            /*font-weight: bold;*/
            /* font-size: medium; */
        }
        .gray {
            background-color: lightgray;
            font-weight: bold;
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
        .tdKu {
            height: 10px;
        }
        th, td {
            padding-left: 5px;
            padding-right: 5px;
            /*border-bottom: 1px solid #ddd;*/
        }

        .border-bottom{
            border-bottom: 1px solid #ddd;
        }

        #watermark {
            background: url('{{ asset('assets/img/lunas-stamp.png') }}') center;
            background-size: 10px 10px;
            background-repeat: no-repeat;
            opacity: 0.1;
        }

        /* td {
            white-space: nowrap;
        } */
    </style>
</head>
<body>
    {{-- @if($status == "B")
        <div id ="watermark">
    @endif --}}
    <table width="100%" border="0">
        <tr>
            <td width="30%" >
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
            </td>
            <td valign="top" style="text-align:center"><h2>PO REQUEST</h2></td>
            <td width="30%" ></td>
        </tr>
    </table>
    <table width="100%" border="0" >
        <tr>
            <td width="10%" class="tdKu">Nomor</td>
            <td class="tdKu">: {{ $prNumber }}</td>
        </tr>
        <tr>
            <td class="tdKu">Permintaan</td>
            <td class="tdKu">: {{ $prRequest }}</td>
        </tr>
        <tr>
            <td class="tdKu">Tanggal</td>
            <td class="tdKu">: {{ $prDate }}</td>
        </tr>
        <tr>
            <td class="tdKu">Status</td>
            <td class="tdKu">: {{ $prStatus }}</td>
        </tr>
    </table>
    <table style="table-layout:fixed;">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="5%">No</th>
            <th width="10%">Kode barang</th>
            <th width="40%">Description</th>
            <th width="10%">QTY</th>
            <th width="10%">Uom</th>
            <th width="15%">Note</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr class="border-bottom">
                    <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                    <td class="border-bottom" align="left">{{ $val->uom }}</td>
                    <td class="border-bottom" align="left">{{ $val->notes }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="font-size: x-small;">Note:{{ $prNote }}</p>
    <table width="100%" border="0">
        <tr><td colspan="2" height="100"></td></tr>
        <tr><td colspan="2" height="100"></td></tr>
        <tr>
            <td align="center">Dibuat</td>
            <td align="center">Diperiksa</td>
            <td align="center">Disetujui</td>
        </tr>
        <tr>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>
        </tr>
        <tr>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>
        </tr>
        <tr>
            <td align="center">( _____________ )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td>
        </tr>
    </table>
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>