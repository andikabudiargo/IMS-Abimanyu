<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TSO</title>
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
            border-collapse: collapse;
        }

        th {
            height: 30px;
        }
        td {
            /* height: 20px; */
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
            background: url("{{ public_path('app-assets/images/logo/logo_po.png') }}") center;
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
            <td valign="top" style="text-align:center"><h2>TARGET SALES ORDER</h2></td>
            <td width="30%" ></td>
        </tr>
    </table>
    <br>
    <table width="100%" border="0" >
        <tr>
            <td width="55%" valign="top" >
                <table style="padding-bottom:0px" >
                    <tr><td width="20%" >TSO Number</td><td>:{{ $tsoNumber }}</td></tr>
                    <tr><td>Tso Name</td><td>:{{ $tsoName }}</td></tr>
                    <tr><td>Date</td><td>:{{ $tsoDate }}</td></tr>
                </table>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <table style="padding-bottom:0px" >
                    <tr><td width="30%" >Created By</td><td>:{{ $createdBy }}</td></tr>
                    <tr><td>Status</td><td>:{{ $status }}</td></tr>
                    <tr><td valign="top" >Note</td><td valign="top">:{{ $keterangan }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <table style="table-layout:fixed;">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="5%">No</th>
            <th width="12%">Article Code</th>
            <th width="58%">Article Desc</th>
            <th width="10%">Qty Target</th>
            <th width="10%">Qty Forcast</th>
            <th width="5%">Uom</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr class="border-bottom">
                    <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->qty_target) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->qty_forcast) }}</td>
                    <td class="border-bottom" align="center">{{ $val->uom }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @foreach ($totals as $val )
                <tr class="border-bottom">
                    <td class="border-bottom" align="left" colspan="3">Total</td>
                    <td class="border-bottom" align="right" >{{ number_format($val->total_target)}}</td>
                    <td class="border-bottom" align="right" >{{ number_format($val->total_forcast)}}</td>
                    <td class="border-bottom" align="left"></td>
                </tr>
            @endforeach
        </tfoot>
        <tr>
            <td colspan="7"> </td>
        </tr>
        <tr>
            {{-- <td colspan="7">Keterangan:<br> {{ $keterangan }}</td> --}}
        </tr>
    </table>
    <table width="100%" border="0">
        <tr><td colspan="2" height="100"></td></tr>
        <tr><td colspan="2" height="100"></td></tr>
        <tr>
            <td align="center">Created By</td>
            <td align="center">Approved By</td>
        </tr>
        <tr>
            <td align="center"></td>
            <td align="center"></td>
        </tr>
        <tr>
            <td align="center"></td>
            <td align="center"></td>
        </tr>
        <tr>
            <td align="center">( {{ $createdBy }} )</td>
            <td align="center">( {{ $approved }} )</td>
        </tr>
    </table>
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>