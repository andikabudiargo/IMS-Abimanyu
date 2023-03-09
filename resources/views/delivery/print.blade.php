<!doctype html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style type="text/css">

        html { 
            margin: 10px;
        }

        /** For screen preview **/
        @media screen {
            body { 
                width: 200mm; height: 280mm ;
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
                size: 8.5in 6in landscape; 
                margin: 0 !important;
                margin-left:10px;
            }

            /* @page {
                size:  auto; 
                margin: 0 !important;
                margin-left:10px;
            } */

            p { page-break-after: always; }

            body { 
                width: 200mm; 
                height: 280mm;
                margin:0 !important;
            }
        }

        body { 
            /* font-family: Courier New,Courier,Lucida Sans Typewriter,Lucida Typewriter,monospace;  */
            font-family: Calibri,Arial, Helvetica, sans-serif;
            /* background-color: aqua; */
            width: 200mm; height: 280mm ;
        } 
        
        * {
            /* font-family: Verdana, Arial, sans-serif; */
            /* font-family: 'Courier New', monospace; */

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

        .border-bottom{
            border-bottom: 1px solid #ddd;
        }

        .border-header{
            border: 1px solid black;
            border-collapse: collapse; 
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

        /* td {
            white-space: nowrap;
        } */

        #tblContent{
            /* font-family: verdana,arial,sans-serif; */
            /* font-size:14pt; */
            color:#333333;
            border-width: 1px;
            border-color: #666666;
            border-collapse: collapse;
        }

        #tblContent  th {
            border-width: 1px;
            border-style: solid;
            border-color: #666666;
            background-color: #dedede;
        }

        #tblContent  td {
            border-width: 1px;
            background-color: #ffffff;
            padding : 3px 10px 3px 10px;
            border-bottom: none;
            border-left: 1px solid black;
            border-right: 1px solid black;
        }

        #tblContent tr:last-child{
            border-bottom: 1px solid black;
            border-left: 1px solid black;
            border-right: 1px solid black;
        }

    </style>
    </head>
<body>
    {{-- @if($status == "B")
        <div id ="watermark">
    @endif --}}
    <div style="border: 1px solid #0c0c0c;">
        <table width="100%" style="border: 1px solid #0c0c0c;padding-left:10px">
            <tr>
                <td width="30%" class="font-12 " >
                    <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
                </td>
                <td  width="50%" >
                    <h3 class="padding:0px;">DELIVERY NOTE</h3>
                    <table>
                        <tr>
                            <td class="font-14 ">Nomor</td>
                            <td class="font-14 ">: {{ $dnHdr->delivery_number }}</td>
                        </tr>
                        <tr>
                            <td class="font-14 ">No.PO#</td>
                            <td class="font-14 ">: {{ $dnHdr->po_number }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table>
            <tr>
                <td width="50%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                    <table style="border-spacing: 0;border-collapse: collapse;">
                        <tr>
                            <td class="font-14 " width="25%">SO Number</td><td>: {{ $dnHdr->so_number }}</td>
                        </tr>
                        <tr>
                            <td class="font-14 ">Tanggal</td><td>: {{ $dnHdr->delivery_date }}</td>
                        </tr>
                        <tr>
                            <td class="font-14 ">Jam</td><td>: {{ date('H:i:s') }}</td>
                        </tr>
                        <tr>
                            <td class="font-14 ">No Mobil</td><td>:</td>
                        </tr>
                    </table>
                </td>
                <td width="50%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                    <strong>Kepada Yth.</strong><br>
                        {{ $customers->nama }} <br>
                        {{ $customers->alamat_kirim_1 }} <br>
                    
                </td>
            </tr>
        </table>
        {{-- <table style="table-layout:fixed;"> --}}
        <table id="tblContent" class="font-14">
            <thead style="background-color: lightgray;">
            <tr>
                <th width="5%" class="border-header">No</th>
                <th width="15%" class="border-header">Code</th>
                <th width="60%" class="border-header">Description</th>
                <th width="10%" class="border-header">Qty</th>
                <th width="10%" class="border-header">UOM</th>
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
                        <td class="border-bottom" align="right" colspan="3">Total Qty</td>
                        <td class="border-bottom" align="left" colspan="2"> : {{ number_format($val->qty) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="5">Note: </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <table width="100%">
        <tr><td colspan="2" height="25"></td></tr>
        <tr>
            <td align="center">Created By</td>
            <td align="center">Checked By</td>
            <td align="center">Shipped By</td>
            <td align="center">Security By</td>
            <td align="center">Received By</td>
        </tr>
        <tr>
            <td align="center" height="50"></td>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>
            <td align="center"></td>
        </tr>
        <tr>
            {{-- <td align="center">(<u>{{ str_pad($dnHdr->created_by, 10, "_", STR_PAD_BOTH)  }}</u>)</td> --}}
            <td align="center">( _________ )</td>
            <td align="center">( _________ )</td>
            <td align="center">( _________ )</td>
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
        window.onfocus = function () { 
            setTimeout(function () { 
                window.close(); 
            }, 200); 
        }
    }

</script>
</body>
</html>