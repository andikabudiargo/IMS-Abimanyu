<!doctype html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style type="text/css">
        html { 
            margin: 10px;
        }

        @page { 
            /*size: 148.5mm 94mm; 
            margin-left: 20mm; 
            margin-top: 20mm; */
            /* size: A4; */
            size: 210mm 148.5mm;
            margin:0;
            mergin-left:10px;
        }

        .print:last-child {
            page-break-after: auto;
        }

        * For screen preview *
        @media screen {
            body { 
                width: 210mm; 
                height: 148.5mm;
                margin:0;
            }
        }

        /** Fix for Chrome issue #273306 **/
        @media print {
            .hidden-print {
                display: none !important;
            }

            @page
            {
                /* size: 8.5in 11in;  */
                /* size: 8.5in 6in;  */
                /* size: A5; */
                size: 210mm 148.5mm ;
                /* margin: 0 !important; */
                margin-left:10px;
            }

            p { page-break-after: always; }

            html, body { 
                height: 210mm;
                width: 148.5mm;
                margin:0 !important;
            }
        }

        body { 
            /* font-family: Courier New,Courier,Lucida Sans Typewriter,Lucida Typewriter,monospace;  */
            /* font-family: Calibri,Arial, Helvetica, sans-serif; */
            /* background-color: aqua; */
            height: 210mm;
            width: 148.5mm;
        } 
        
        * {
            /* font-family: Verdana, Arial, sans-serif; */
            font-family: 'Courier New', monospace;
        }

        table{
            /* font-size: x-small; */
            font-size: medium;
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

        /* th {
            height: 30px;
        }
        td {
            height: 20px;
        } */

        th, td {
            padding-left: 5px;
            padding-right: 5px;
            /*border-bottom: 1px solid #ddd;*/
        }

        .border-header{
            border: 1px solid black;
            border-collapse: collapse; 
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

        .{
            padding : 0 2px 0 2px;
        }

        .font-12 {
            font-size: 10px;
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

    </style>
    </head>
<body>
    {{-- @if($status == "B")
        <div id ="watermark">
    @endif --}}
    <table width="100%" border="0">
        <tr>
            <td width="30%" >
                {{-- <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;">  --}}
                <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 70%;"> 
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
    <table style="table-layout:fixed;" id="okiTable">
        <thead style="background-color: lightgray;">
            <tr >
                <th width="5%" class="border-header">No</th>
                <th width="10%" class="border-header">Code</th>
                <th width="45%" class="border-header">Description</th>
                <th width="10%" class="border-header">Qty</th>
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
        <tr><td colspan="2" height="10"></td></tr>
        <tr>
            <td align="center">Authorized By</td>
            <td align="center">Prepared By</td>
        </tr>
        <tr>
            <td align="center" height="30"></td>
            <td align="center" height="30"></td>
        </tr>
        <tr>
            {{-- <td align="center"> {{ $approved }} </td>
            <td align="center"> {{ $recHdr->created_by }} </td> --}}
            <td align="center">( _________ )</td>
            <td align="center">( _________ )</td>
        </tr>
    </table>
{{-- @if($poNumber == "oki")
</div>
@endif --}}
<script>
    window.onload= function () {
        window.print();
        window.onafterprint = function () {
            window.close();
        }
    }
</script>
</body>
</html>