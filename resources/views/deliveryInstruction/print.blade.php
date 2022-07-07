<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DI</title>
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
        <table width="100%">
            <tr>
                <td align="left" style="width: 45%;vertical-align:bottom">
                    <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
                </td>
                <td style="vertical-align: bottom;font-size: large;"><h2>DELIVERY INSTRUCTION</h2></td>
                {{-- <td align="center" style="width: 45%;" tyle="text-align:center;">
                    
                    <pre>
                    Date: {{ $diDate }}
                    PO Number: {{ $diNumber }}
                    Delivery Date : {{ $diDelDate }}
                    </pre>
                    
                </td> --}}
            </tr>
        </table>
    </div>
 
    <table>
        <tr>
            <td width="45%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                <strong> SHIPPING ADDRESS </strong><br>
                @foreach ($companies as $val)
                {{ $val }} <br>
                @endforeach
                
            </td>
            <td width="10%"></td>
            <td width="45%" style="border: 1px solid #0c0c0c;padding-left:10px;vertical-align:top">
                Date: {{ $diDate }}<br>
                {{-- PO Number: {{ $diNumber }}<br> --}}
                Supplier Name : {{ $diNumber }}<br>
                Delivery Date : {{ $diDelDate }}
                {{-- <strong>SHIP TO </strong><br>
                @foreach ($suppliers as $val )
                    {{ $val->nama }} <br>
                    Fax:{{ $val->fax }}<br>
                    Phone:{{ $val->telepon }}<br>
                    Contact:{{ $val->nama_kontak }}<br>
                @endforeach --}}
            </td>
        </tr>
    </table>
    <table class="detail" width="100%">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="6%">No. PO</th>
            <th width="18%" >Description</th>
            <th width="3%">Request</th>
            <th width="3%">Satuan</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->po_number }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->article_desc }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->qty,4) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->uom }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            {{-- @foreach ($totals as $val )
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="border-bottom: 1px solid #ddd;" align="left" colspan="3">Total</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" >{{ number_format($val->qty) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" ></td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" >{{ number_format($val->ppn)}}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" class="gray">{{ number_format($val->netto)}}</td>
                </tr>
            @endforeach --}}
        </tfoot>
    </table>
    <table width="100%">
        <tbody>
            <tr><td style="width: 65%;">Notes:</td></tr>
            <tr><td rowspan='6' style="width: 65%;">{{ $keterangan }}</td></tr>
        </tbody>
    </table>
    @if($status == '3')
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
    @endif
{{-- @if($status == "3")
</div>
@endif --}}
</body>
</html>