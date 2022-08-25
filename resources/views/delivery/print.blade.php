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
            /* font-family: Verdana, Arial, sans-serif; */
            font-family: 'Courier New', monospace;

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
                <br>Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta
            </td>
            <td valign="top" style="text-align:center"></td>
            <td width="30%" ></td>
        </tr>
    </table>
    <br>
    <table>
        <tr>
            <td width="60%"style="border: 1px solid #0c0c0c;padding-left:10px">
                <h2>DELIVERY NOTE</h2>
            </td>
            <td style="border: 1px solid #0c0c0c;padding-left:10px">
                No:<br>{{ $dnHdr->delivery_number }}<br>
                Status:{{ $statusDel }}
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td width="60%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                <strong> VENDOR </strong><br>
                    {{ $customers->nama }} <br>
                    {{ $customers->alamat_kirim_1 }} <br>
            </td>
            <td width="40%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                PO Number:<br>{{ $dnHdr->po_number }}
            </td>
        </tr>
    </table>
    <table style="table-layout:fixed;">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="5%">No</th>
            <th width="15%">Code</th>
            <th width="60%">Description</th>
            <th width="10%">Qty</th>
            <th width="10%">UOM</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr class="border-bottom">
                    <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                    <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                    <td class="border-bottom" align="right">{{ $val->uom }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @foreach ($totals as $val )
                <tr class="border-bottom">
                    <td class="border-bottom" align="left" colspan="4">Total</td>
                    <td class="border-bottom" align="right" >{{ number_format($val->qty) }}</td>
                </tr>
            @endforeach
        </tfoot>
        <tr>
            <td colspan="5">Note: </td>
        </tr>
    </table>

    <table width="100%" border="0">
        <tr><td colspan="2" height="100"></td></tr>
        <tr><td colspan="2" height="100"></td></tr>
        <tr>
            <td align="center">Created By</td>
            <td align="center">Checked By</td>
            <td align="center">Shipped By</td>
            <td align="center">Security By</td>
            <td align="center">Received By</td>
        </tr>
        <tr>
            <td align="center"></td>
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
            <td align="center"></td>
        </tr>
        <tr>
            <td align="center">( <u>{{ str_pad($dnHdr->created_by, 10, "_", STR_PAD_BOTH)  }}</u> )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td>
        </tr>
    </table>
        
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>