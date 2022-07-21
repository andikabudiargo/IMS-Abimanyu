<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PO</title>
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

    .detail th {
        height: 30px;
    }
    .detail td {
        height: 20px;
    }
    .detail th, td {
        padding-left: 15px;
        padding-right: 15px;
        /*border-bottom: 1px solid #ddd;*/
    }

    #watermark {
        background: url('{{ asset('app-assets/images/icons/lunas-stamp.png') }}') center;
        background-size: 10px 10px;
        background-repeat: no-repeat;
        opacity: 0.1;
    }
      
</style>

</head>
<body>
{{-- @if($status == "3")
    <div id ="watermark">
@endif --}}

    <div class="header">
        <table width="100%" >
            <tr>
                <td align="left" style="width: 45%;">
                    <h2>PURCHASE ORDER</h2>
                    <table>
                        <tr>
                            <td></td>
                        </tr>
                    </table>
<pre>
Date          : {{ $poDate }}
PO Number     : {{ $poNumber }}
Term          : {{ $poTerm }} Days
Delivery Date : {{ $poDelDate }}
</pre>
                </td>
                <td style="width: 10%;"></td>
                <td align="center" style="width: 45%;" tyle="text-align:center;">
                    <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
                </td>
            </tr>
        </table>
    </div>
 
    <table>
        <tr>
            <td width="45%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                <strong> VENDOR </strong><br>
                @foreach ($suppliers as $val )
                    {{ $val->nama }} <br>
                    Fax:{{ $val->fax }}<br>
                    Phone:{{ $val->telepon }}<br>
                    Contact:{{ $val->nama_kontak }}<br>
                @endforeach
            </td>
            <td width="10%"></td>
            <td width="45%" style="border: 1px solid #0c0c0c;padding-left:10px">
                <strong>SHIP TO </strong><br>
                @foreach ($companies as $val)
                {{ $val }} <br>
                @endforeach
            </td>
        </tr>
    </table>
    <table class="detail" width="100%">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="5%">No</th>
            <th width="10%">Code</th>
            <th width="40%">Description</th>
            <th width="5%">Qty</th>
            <th width="5%">UOM</th>
            <th>Price</th>
            {{-- <th>PPN</th> --}}
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr style="border-bottom: 1px solid #ddd;">
                    <td scope="row" style="border-bottom: 1px solid #ddd;">{{ ++$no }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->article_alternative_code }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->article_desc }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->qty) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ $val->uom }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->price) }}</td>
                    {{-- <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->ppn) }}</td> --}}
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format(($val->qty*$val->price)+$val->ppn) }}</td>
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
        </tfoot>
    </table>
    <table width="100%">
        <tbody>
            <tr><td style="width: 65%;">Notes:</td></tr>
            <tr><td rowspan='6' style="width: 65%;">{{ $keterangan }}</td></tr>
            <tr><td >Subtotal</td><td>:</td></td><td align="right">{{ number_format($totals[0]->gross) }}</td></tr>
            <tr><td >Discount:</td><td>:</td><td align="right">{{ number_format($totals[0]->discount) }}</td></tr>
            <tr><td >PPN 11%:</td><td>:</td><td align="right">{{ number_format($totals[0]->ppn) }}</td></tr>
            <tr><td >Total:</td><td>:</td><td align="right">{{ number_format($totals[0]->netto) }}</td></tr>
        </tbody>
    </table>
    <table width="100%" border="0">
        <tr><td colspan="2" height="100"></td></tr>
        <tr><td colspan="2" height="100"></td></tr>
        <tr>
            <td align="center">Dibuat</td>
            <td align="center">Diperiksa</td>
            <td align="center">Mengetahui</td>
            <td align="center">Menyetujui</td>
        </tr>
        <tr>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>
        </tr>
        <tr>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>
        </tr>
        <tr>
            <td align="center">( _____________ )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td>
        </tr>
    </table>
    {{-- @if($status == '3')
    <table>
        <tr>
            <td align="center" style="width:30%;" style="">Authorization</td><td></td>
        </tr>
        <tr>
            <td align="center" style="height:50px"></td><td></td>
        </tr>
        <tr>
            <td align="center">(     {{ $approved }}     )</td><td></td>
        </tr>
    </table>
    @endif --}}
{{-- @if($status == "3")
</div>
@endif --}}
</body>
</html>