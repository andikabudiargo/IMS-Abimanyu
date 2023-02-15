<!doctype html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style type="text/css">
        @page { margin: 0 }
        body { margin: 0 }
            .sheet {
            margin: 0;
            overflow: hidden;
            position: relative;
            box-sizing: border-box;
            page-break-after: always;
        }

        /** Paper sizes **/
        body.A3           .sheet { width: 297mm; height: 419mm }
        body.A3.landscape .sheet { width: 420mm; height: 296mm }
        /* body.A4           .sheet { width: 210mm; height: 296mm } */
        body.A4           .sheet { width: 210mm; height: 148mm }
        body.A4.landscape .sheet { width: 297mm; height: 209mm }
        body.A5           .sheet { width: 148mm; height: 209mm }
        body.A5.landscape .sheet { width: 210mm; height: 147mm }

        /** Padding area **/
        .sheet.padding-10mm { padding: 10mm }
        .sheet.padding-5mm { padding: 5mm }
        .sheet.padding-15mm { padding: 15mm }
        .sheet.padding-20mm { padding: 20mm }
        .sheet.padding-25mm { padding: 25mm }

        /** For screen preview **/
        @media screen {
            body { background: #e0e0e0 }
            .sheet {
                background: white;
                box-shadow: 0 .5mm 2mm rgba(0,0,0,.3);
                margin: 5mm;
            }
        }

        /** Fix for Chrome issue #273306 **/
        @media print {
            body.A3.landscape { width: 420mm }
            body.A3, body.A4.landscape { width: 297mm }
            body.A4, body.A5.landscape { width: 210mm }
            body.A5                    { width: 148mm }
        }

        .header, .header-space{
                height: 170px;
        }

        .footer, .footer-space {
                height: 170px;
        }
        
        .header {
            position: fixed;
            top: 0;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
        }

        @media print {
            header, footer {
                position: fixed;
                top: 0;
            }
            
            footer {
                position: fixed;
                bottom: 0;
            }

        }
        
        * {
            font-family: "Calibri";
            /* font-family: Calibri,sans-serif,Verdana,Arial; */
            /* font-family: 'Courier New', monospace; */
        }

        table{
            font-size: medium;
        }
        
        table {
            width: 100%;
        }


        #tblContent{
            /* font-family: verdana,arial,sans-serif; */
            font-size:16px;
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

        .tableHeader td{
            padding-bottom: 0px;
            padding-top: 0px;
        }
         
    </style>
    </head>
<body class="A4">
{{-- <section class="sheet padding-5mm"> --}}
    <table class="sheet padding-5mm">
        <thead><tr><td>
            <div class="header-space">
                <table width="100%" border="0">
                    <tr>
                        <td width="30%" >
                            <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 90%;"> 
                        </td>
                        <td valign="top" style="text-align:center"><h3>LEMBAR PENERIMAAN BARANG</h3></td>
                        <td width="30%" ></td>
                    </tr>
                </table>
                <table width="100%" border="0" >
                    <tr>
                        <td width="45%" valign="top" >
                            <table class="tableHeader">
                                <tr>
                                    <td width="30%">Rec. Number </td><td>: {{ $recHdr->rec_number }}</td>
                                </tr>
                                <tr>
                                    <td>PO Number </td><td>: {{ $recHdr->po_number }}</td>
                                </tr>
                                <tr>
                                    <td>Rec. Date </td><td>: {{ $recHdr->rec_date }}</td>
                                </tr>
                            </table>
                        </td>
                        <td width="5%"></td>
                        <td width="45%">
                            <table class="tableHeader">
                                <tr>
                                    <td width="30%">Customer </td><td>: {{ $suppliers[0]->nama }}</td>
                                </tr>
                                <tr>
                                    <td>DO Number </td><td>: {{ $recHdr->do_number }}</td>
                                </tr>
                                <tr>
                                    <td>DO Date </td><td>: {{ $recHdr->do_date }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </td></tr></thead>
        <tbody><tr><td>
            <div class="content">
                <table id="tblContent">
                    <thead>
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
                        @foreach ($details as $val )
                            <tr class="border-bottom">
                                <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                                <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                            </tr>
                        @endforeach
                        @foreach ($details as $val )
                            <tr class="border-bottom">
                                <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                                <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                            </tr>
                        @endforeach
                        @foreach ($details as $val )
                            <tr class="border-bottom">
                                <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                                <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                            </tr>
                        @endforeach
                        @foreach ($details as $val )
                            <tr class="border-bottom">
                                <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                                <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                            </tr>
                        @endforeach
                        @foreach ($details as $val )
                            <tr class="border-bottom">
                                <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                                <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                                <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <table width="100%" border="0">
                    <tr colspan="2" class="border-bottom">
                        <td class="border-bottom" align="left" colspan="3">Total</td>
                        <td class="border-bottom" align="right" >{{ number_format($totals[0]->qty) }}</td>
                    </tr>
                    <tr colspan="2" class="border-bottom">
                        <td class="border-bottom" align="left" colspan="3" style="border:none">Status:{{ $status }}</td>
                    </tr>
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
            </div>
        </td></tr></tbody>
        <tfoot><tr><td>
            <div class="footer-space">
            </div>
        </td></tr></tfoot>
    </table>
{{-- </section> --}}
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