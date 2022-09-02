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
        th, td {
            padding-left: 5px;
            padding-right: 5px;
            /*border-bottom: 1px solid #ddd;*/
        }

        /* .border-bottom{
            border-bottom: 1px solid #ddd;
        } */

        #watermark {
            background: url('{{ asset('assets/img/lunas-stamp.png') }}') center;
            background-size: 10px 10px;
            background-repeat: no-repeat;
            opacity: 0.1;
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


        /* td {
            white-space: nowrap;
        } */
    </style>
</head>
<body>
    {{-- @if($status == "B")
        <div id ="watermark">
    @endif --}}
    <table width="100%" border="1" class="border-garis header-padding">
        <tr>
            <td width="30%" rowspan="5" class="no-wrap h-tengah" >
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
            </td>
            <td width="40%" rowspan="5" class="no-wrap h-tengah" style="text-align:center"><h2>WORK ORDER SHEET</h2>{{ $prdNumber }}</td>
            <td valign="" class="font-10 header-padding" >No Doc</td>
            <td valign="" class="font-10 header-padding" >: PPC-01.01-FM</td>
        </tr>
        <tr>            
            <td valign="" class="font-10 header-padding" >Date Used</td>
            <td valign="" class="font-10 header-padding" >: Monday,April 15, 2019</td>
        </tr>
        <tr>            
            <td valign="" class="font-10 header-padding" >Date Of Rev</td>
            <td valign="" class="font-10 header-padding" >: 15 Juni 2021</td>
        </tr>
        <tr>            
            <td valign="" class="font-10 header-padding" >Rev</td>
            <td valign="" class="font-10 header-padding" >: 2</td>
        </tr>
        <tr>            
            <td valign="" class="font-10 header-padding" >Page</td>
            <td valign="" class="font-10 header-padding" >: 1/1</td>
        </tr>
        <tr>
            <td colspan="4" valign="top">
                <table>
                    <tr>
                        <td width="5%" valign="top">Tanggal</td>
                        <td width="20%">: {{ $header->prod_date }}</td>
                        <td width="5%">Shift</td>
                        <td >: {{ ucfirst($header->prod_shift) }} </td>
                    </tr>
                    <tr>
                        <td width="5%" valign="top">Rev</td>
                        <td >: {{ $header->num_revision }}</td>
                        <td width="5%">Group</td>
                        <td >: {{ $header->prod_group }}</td>
                    </tr>   
                </table>
            </td>
        </tr>
    </table>
    <table width="100%" border="1" class="font-8 border-garis header-padding" style="margin-top:3px">
        <thead >
            <tr>
                <th rowspan="2" width="4%">No</th>
                <th rowspan="2" width="8%">Part FG</th>
                <th rowspan="2" width="20%">Part Name</th>
                <th rowspan="2" width="8%">Part RM</th>
                <th rowspan="2" width="6%">Plan Jam Loading</th>
                <th rowspan="2" width="6%">Act Jam Loading</th>
                <th rowspan="2" width="4%">Stock RM</th>
                <th colspan="2" width="4%">Qty</th>
                <th rowspan="2" width="4%">Qty Tag</th>
                <th rowspan="2" width="4%">Act Tag</th>
                <th colspan="2" width="4%">Qty</th>
                <th rowspan="2" width="4%">Ok</th>
                <th rowspan="2" width="5%">Repair</th>
                <th rowspan="2" width="5%">Repaint</th>
                <th rowspan="2" >Remarks </th>
            </tr>
            <tr>
                <th width="4%">Frs</th>
                <th width="4%">Rpn</th>
                <th width="4%">Frs</th>
                <th width="4%">Rpn</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr class="border-bottom">
                    <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                    <td class="border-bottom" align="left">{{ $val->plan_time_loading }}</td>
                    <td class="border-bottom" align="left">{{ $val->act_time_loading }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->qty_rm) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->plan_qty_fresh) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->plan_qty_repaint) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->act_tag) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->plan_tag) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->act_qty_fresh) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->act_qty_repaint) }}</td>
                    <td class="border-bottom" align="right"></td>
                    <td class="border-bottom" align="right"></td>
                    <td class="border-bottom" align="right"></td>
                    <td class="border-bottom" align="right"></td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table width="100%" border="1" class="font-8 border-garis header-padding" style="margin-top:3px">
        <tr>
            <td rowspan="2" colspan="3">Total tag</td>
            <td >{{ $header->working_hour }}</td>
            <td >x 3600" x 95% = </td>
            <td colspan="2"></td>
            <td >Waktu tersedia</td>
            <td colspan="6">{{ ($header->working_hour*3600*0.95)/30 }}</td>
            <td rowspan="2">Note: OT loading semua setingan WOS</td>
        </tr>
        <tr>
            <td colspan="2" >Waktu Dibutuhkan</td>
            <td colspan="2" >{{ $header->total_tag }}</td>
            <td >Sisa waktu</td>
            <td colspan="6">{{ (($header->working_hour*3600*0.95)/30)-$header->total_tag-10 }}</td>
        </tr>
    </table>
   
    <table border="0" class="font-8 border-garis header-padding" style="margin-top:3px">
        <tr>
            <td rowspan="3" width="25%" class="font-10"  style="border: 1px solid rgb(9, 9, 9);">
                Plan={{ ($header->working_hour*3600*0.95)/30 }}<br><br>
                Actual=<br><br>
                Hasil Performance ( % ) =
            </td>
            <td rowspan="3" width="25%">

            </td>
            <td align="center" class="kotak-td">Dibuat</td>
            <td align="center" class="kotak-td">Disetujui</td>
            <td align="center" class="kotak-td">Dilaporkan</td>
            <td align="center" class="kotak-td">Disetujui</td>
        </tr>
        <tr >
            <td align="center" class="kotak-td" style="padding:20px"></td>
            <td align="center" class="kotak-td"></td>
            <td align="center" class="kotak-td"></td>
            <td align="center" class="kotak-td"></td>
        </tr>
        <tr>
            <td align="center" class="kotak-td">PPIC</td>
            <td align="center" class="kotak-td">Spv.PPIC</td>
            <td align="center" class="kotak-td">Prod.Foreman</td>
            <td align="center" class="kotak-td">Prod.SPV</td>
        </tr>
    </table>
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>