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
        background: url('{{asset('assets/img/lunas-stamp.png')}}') center;
        background-size: 10px 10px;
        background-repeat: no-repeat;
        opacity: 0.1;
      }
</style>

</head>
<body>
{{-- @if($status == "B")
    <div id ="watermark">
@endif --}}

    <table width="100%" border="0">
        <tr>
            <td valign="top" colspan="4"><h2>PURCHASE ORDER</h2></td>
        </tr>
        <tr>
            <td width="20%">PO Number</td>
            <td width="25%">: {{ $poNumber }}</td>
            <td width="10%"></td>
            <td width="45%" rowspan="4" style="text-align:center;">
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
            </td>
        </tr>
        <tr>
            <td width="10%">PO Date</td><td>: {{ $poDate }}</td><td width="10%"></td>
        </tr>
        <tr>
            <td width="10%">Term</td><td>: {{ $poTerm }} Days</td><td width="10%"></td>
        </tr>
        <tr>
            <td width="10%">Delivery Date</td><td>: {{ $poDelDate }}</td><td width="10%"></td>
        </tr>
        
    </table>
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
    <table width="100%">
    <thead style="background-color: lightgray;">
      <tr>
        <th width="5%">No</th>
        <th width="10%">Code</th>
        <th width="40%">Description</th>
        <th width="5%">Qty</th>
        <th>Price</th>
        <th>PPN</th>
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
                <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->price) }}</td>
                <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->ppn) }}</td>
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
            <td colspan="7">Keterangan:<br> {{ $keterangan }}</td>
        </tr>
    </table>
    {{-- <table width="100%" border="0">
        <tr><td colspan="2" height="100"></td></tr>
        <tr><td colspan="2" height="100"></td></tr>
        <tr>
            <td align="center">Pengirim</td>
            <td align="center">Penerima</td>
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
            <td align="center">( _____________ )</td>
            <td align="center">( _____________  )</td>
        </tr>
    </table> --}}
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>