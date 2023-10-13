<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SO - {{ $soNumber }}</title>
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
            <td valign="top" style="text-align:center"><h2>SALES ORDER</h2></td>
            <td width="30%" ></td>
        </tr>
    </table>
    <table width="100%" border="0" >
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
    <table style="table-layout:fixed;">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="5%">No</th>
            {{-- <th width="10%">Code</th> --}}
            <th width="45%">Description</th>
            <th width="10%">Qty</th>
            <th width="10%">Material Price</th>
            <th width="10%">Service Price</th>
            <th width="10%">Total Material</th>
            <th width="10%">Total Service</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr class="border-bottom">
                    <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                    {{-- <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td> --}}
                    <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->price,2) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->price_service,2) }}</td>
                    <td class="border-bottom" align="right">{{ number_format(($val->qty*$val->price),2) }}</td>
                    <td class="border-bottom" align="right">{{ number_format(($val->qty*$val->price_service),2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @foreach ($totals as $val )
                <tr class="border-bottom">
                    <td class="border-bottom" align="left" colspan="2">Total</td>
                    <td class="border-bottom" align="right" >{{ number_format($val->qty) }}</td>
                    <td class="border-bottom" align="right" ></td>
                    <td class="border-bottom" align="right" ></td>
                    <td class="border-bottom" align="right" >{{ number_format($val->total_material,2) }}</td>
                    <td class="border-bottom" align="right" >{{ number_format($val->total_service,2) }}</td>
                </tr>
            @endforeach
        </tfoot>
        <tr>
            <td colspan="7"> </td>
        </tr>
        <tr>
            <td colspan="3" rowspan="4" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                    <br>
                    {{-- Syarat Bayar : {{ $customers->syarat_bayar }}<br>
                    Waktu Kirim : {{ $customers->syarat_kirim }}<br> --}}
                    Alamat Kirim:{{ $customers->alamat_kirim_1 }}<br>
                    Note:{{ $keterangan }}<br>
            </td>
            <td></td>
            <td></td>
            <td>Sub Total</td>
            <td align="right">{{ number_format($val->sub_total,2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>PPN</td>
            <td align="right">{{ number_format($val->ppn,2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>PPH23</td>
            <td align="right">{{ $val->pph23?'-':'' }}{{ number_format($val->pph23,2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>Grand total</td>
            <td align="right">{{ number_format($val->grand_total,2) }}</td>
        </tr>
        <tr>
            {{-- <td colspan="7">Keterangan:<br> {{ $keterangan }}</td> --}}
        </tr>
    </table>
    <table width="100%" border="0" cellspacing="20">
        <tr><td colspan="2" height="80"></td></tr>
        <tr><td colspan="2" height="80"></td></tr>
        <tr>
            <td align="center">Dibuat</td>
            <td align="center">Diperiksa</td>
            <td align="center">Disetujui</td>
            <td align="center">Mengetahui</td>
        </tr>
        <tr>
            <td align="center" height="15">{{ $approval1 ? 'Approval 1':'' }}</td>
            <td align="center">{{ $approval2 ? 'Approval 2':'' }}</td>
            <td align="center">{{ $approval3 ? 'Approval 3':'' }}</td>
            <td align="center">{{ $approval4 ? 'Approval 4':'' }}</td>
        </tr>
        <tr>
            <td align="center"  style="border-bottom: 1px solid black;">{{ $approval1 ? $approval1->name:'' }}</td>
            <td align="center" style="border-bottom: 1px solid black;">{{ $approval2 ? $approval2->name:'' }}  </td>
            <td align="center" style="border-bottom: 1px solid black;">{{ $approval3 ? $approval3->name:'' }}  </td>
            <td align="center" style="border-bottom: 1px solid black;">{{ $approval4 ? $approval4->name:'' }}  </td>
            {{-- <td align="center">( _____________ )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td> --}}
        </tr>
    </table>
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>