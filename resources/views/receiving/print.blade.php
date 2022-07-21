<!doctype html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>REC</title>
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
            <td valign="top" style="text-align:center"><h2>LEMBAR PENERIMAAN BARANG</h2></td>
            <td width="30%" ></td>
        </tr>
    </table>
    <table width="100%" border="0" >
        <tr>
            <td width="45%" valign="top" >
                Rec. Number : {{ $recHdr->rec_number }}<br>
                PO Number   : {{ $recHdr->po_number }}<br>
                Rec. Date   : {{ $recHdr->rec_date }}                
            </td>
            <td width="15%"></td>
            <td width="40%">
                Customer   : {{ $suppliers[0]->nama }}<br>
                DO Number  : {{ $recHdr->do_number }}<br>
                DO Date    : {{ $recHdr->do_date }}
            </td>
        </tr>
    </table>
    <table style="table-layout:fixed;">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="5%">#</th>
            <th width="10%">Code</th>
            <th width="45%">Description</th>
            <th width="10%">Qty</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr class="border-bottom">
                    <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @foreach ($totals as $val )
                <tr class="border-bottom">
                    <td class="border-bottom" align="left" colspan="3">Total</td>
                    <td class="border-bottom" align="right" >{{ number_format($val->qty) }}</td>
                </tr>
            @endforeach
            <tr class="border-bottom">
                <td class="border-bottom" align="left" colspan="3" style="border:none">Status:{{ $status }}</td>
            </tr>
        </tfoot>
    </table>
    <table width="100%" border="0">
        <tr><td colspan="2" height="100"></td></tr>
        <tr><td colspan="2" height="100"></td></tr>
        <tr>
            <td align="center">Authorized By</td>
            <td align="center">Prepared By</td>
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
            <td align="center"> {{ $approved }} </td>
            <td align="center"> {{ $recHdr->created_by }} </td>
        </tr>
    </table>
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>