<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Kas Masuk</title>
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
    /* th, td {
        padding-left: 15px;
        padding-right: 15px;
        border-bottom: 1px solid #ddd;
    } */
</style>

</head>
<body>
    <table width="100%" border="1">
        <tr>
            <td valign="top" colspan="2" style="text-align: center"><h2 class="grey">BUKTI KAS MASUK</h2>
                <h3>{{ $header->voucher_number }}</h3></td>
        </tr>
        <tr>
            <td width="10%">Tanggal</td><td>: {{ $header->voucher_date }}</td>
        </tr>
        <tr>
            <td width="10%">Dari</td><td>: {{ $header->receive_from }}</td>
        </tr>        
    </table>
    <table width="100%" border="1">
        <thead>
            <tr>
                <th width="5%">No Account</th>
                <th width="10%">Keterangan</th>
                <th width="40%">Debet</th>
                <th width="5%">Kredit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->account }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->description }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->debit) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->credit) }}</td>
                </tr>
            @endforeach
        </tbody>

        <tfoot>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="border-bottom: 1px solid #ddd;" align="left" colspan="2">Total</td>
                <td style="border-bottom: 1px solid #ddd;" align="right" >{{ number_format($total->total_debit) }}</td>
                <td style="border-bottom: 1px solid #ddd;" align="right" >{{ number_format($total->total_credit)}}</td>
            </tr>
        </tfoot>
    </table>
    <table width="100%" border="0">
        <tr><td colspan="2" height="100"></td></tr>
        <tr><td colspan="2" height="100"></td></tr>
        <tr>
            <td align="center">Dibuat oleh</td>
            <td align="center">Mengetahui</td>
            <td align="center">Direksi</td>
            <td align="center">Accounting</td>
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
            <td align="center">( _____________ )</td>
            <td align="center">( _____________  )</td>
        </tr>
    </table>

    <script>
        window.onload= function () {
            window.print();
            window.onafterprint = function () {
                window.close();
            }
            window.onfocus = function () { 
                setTimeout(function () { 
                    window.close(); 
                }, 200); 
            }
        }
    </script>
</body>
</html>