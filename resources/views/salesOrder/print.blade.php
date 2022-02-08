<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>SO</title>
<style type="text/css">
    * {
        font-family: Verdana, Arial, sans-serif;
    }

    table{
        font-size: x-small;
    }
    
    tfoot tr td{
        /*font-weight: bold;*/
        font-size: medium;
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
        padding-left: 15px;
        padding-right: 15px;
        /*border-bottom: 1px solid #ddd;*/
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
            <td valign="top" style="text-align:center"><h2>SALES ORDER</h2></td>
            <td width="30%" ></td>
        </tr>
    </table>
    <table>
        <tr>
            <td width="45%" valign="top" >
                Order Number : {{ $soNumber }}<br>
                PO Number    : {{ $soPoNumber }}<br>
                Customer     : {{ $customers->nama }}
            </td>
            <td width="25%"></td>
            <td width="30%">
                Tanggal  : {{ $soDate }}<br>
                Salesman : {{ $soSalesman }}<br>
                Currency : {{ $soCurrency }}
            </td>
        </tr>
    </table>
    <table width="100%">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="2%">#</th>
            {{-- <th width="10%">Code</th> --}}
            <th width="20%">Description</th>
            <th width="5%">Qty</th>
            <th width="5%">Material Price</th>
            <th width="5%">Service Price</th>
            <th width="5%">Total Material</th>
            <th width="5%">Total Service</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr style="border-bottom: 1px solid #ddd;">
                    <td scope="row" style="border-bottom: 1px solid #ddd;">{{ ++$no }}</td>
                    {{-- <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->article_alternative_code }}</td> --}}
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->article_desc }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->qty) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->price) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->price_service) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format(($val->qty*$val->price)) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format(($val->qty*$val->price_service)) }}</td>
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            @foreach ($totals as $val )
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="border-bottom: 1px solid #ddd;" align="left" colspan="3">Total</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" >{{ number_format($val->qty) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" ></td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" >{{ number_format($val->ppn)}}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" class="gray">{{ number_format($val->netto)}}</td>
                </tr>
            @endforeach
            
            {{-- @foreach ($totalsls as $totalsl )
            <tr>
                <td colspan="3"></td>
                <td align="right">QTY</td>
                <td align="right" colspan="2">{{number_format($totalsl->qty)}}</td>
            </tr>
            <tr>
                <td colspan="3"></td>
                <td align="right">Subtotal</td>
                <td align="right" colspan="2">{{number_format($totalsl->subtotal)}}</td>
            </tr>
            <tr>
                <td colspan="3"></td>
                <td align="right">Disc</td>
                <td align="right" colspan="2">-{{number_format($totalsl->disc)}}</td>
            </tr>
            <tr>
                <td colspan="3"></td>
                <td align="right">Total</td>
                <td align="right" class="gray" colspan="2">{{number_format($totalsl->total)}}</td>
            </tr>
            @endforeach --}}

        </tfoot>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Sub Total</td>
            <td>{{ number_format($val->gross) }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>PPN</td>
            <td>{{ number_format($val->ppn) }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>PPH23</td>
            <td>-{{ number_format($val->pph23) }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Grand total</td>
            <td>-{{ number_format($val->netto) }}</td>
        </tr>
        <tr>
            <td colspan="7">Keterangan:<br> {{ $keterangan }}</td>
        </tr>
    </table>
    <table width="100%" border="0">
        <tr><td colspan="2" height="100"></td></tr>
        <tr><td colspan="2" height="100"></td></tr>
        <tr>
            <td class="text-center">Disetujui</td>
            <td class="text-center">Diperiksa</td>
            <td class="text-center">Disiapkan</td>
        </tr>
        <tr>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
        </tr>
        <tr>
            <td class="text-center"></td>
            <td class="text-center"></td>
            <td class="text-center"></td>
        </tr>
        <tr>
            <td class="text-center">( _____________ )</td>
            <td class="text-center">( _____________  )</td>
            <td class="text-center">( _____________  )</td>
        </tr>
    </table>
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>