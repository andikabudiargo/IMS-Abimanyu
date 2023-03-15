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
        body.A4           .sheet { width: 210mm; height: 296mm }
        body.A4A5           .sheet { width: 210mm; height: 148mm } */
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

        .putih{
            color:white;
        }

        .header, .header-space{
                height: 190px;
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

            @page :footer {
                display: none
            }
            @page :header {
                display: none
            }

            .tanpa-padding{
                padding:0px;
            }

            .putih{
                color:white;
            }

        }
        
        * {
            font-family: Calibri,Arial, Helvetica, sans-serif;
        }

        table{
            font-family: Calibri,Arial, Helvetica, sans-serif;
        }
        
        table {
            width: 100%;
        }

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

        .tableHeader td{
            padding-bottom: 0px;
            padding-top: 0px;
        }

        .font-12{
            /* font-size:12pt; */
            font-size: medium;
        }

        .font-14{
            /* font-size:14pt; */
            font-size: medium;
        }

        .font-16{
            /* font-size:16pt; */
            font-size: medium;
        }

        .tanpa-padding{
            padding:0px;
        }

        .huruf-tebal{
            font-weight: bold;
        }

    </style>
</head>
<body class="{{ (count($details)) < 7 ? "A4A5" : "A4" }}">
<div class="sheet padding-5mm">
    <table>
        <thead>
            <tr>
                <td>
                    <div class="header-space">
                        <br>
                        <table width="100%" style="border: 1px solid #0c0c0c;padding-left:10px">
                            <tr>
                                <td width="30%" class="font-12 " >
                                    <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
                                </td>
                                <td  width="50%" >
                                    {{-- <h3 class="padding:0px;">DELIVERY NOTE</h3> --}}
                                    <div class="huruf-tebal">DELIVERY NOTE</div>
                                    <table>
                                        <tr class="tanpa-padding">
                                            <td class="font-14 tanpa-padding">Nomor</td>
                                            <td class="font-14 tanpa-padding">: {{ $dnHdr->delivery_number }}</td>
                                        </tr>
                                        <tr class="tanpa-padding">
                                            <td class="font-14 tanpa-padding">No.PO#</td>
                                            <td class="font-14 tanpa-padding">: {{ $dnHdr->po_number }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <table style="border-left: 1px solid #0c0c0c;border-right: 1px solid #0c0c0c;padding-left:10px" class="font-14 tanpa-padding">
                            <tr>
                                <td width="50%" valign="top">
                                    <table>
                                        <tr>
                                            <td width="25%" class="tanpa-padding">SO Number</td><td class="tanpa-padding">: {{ $dnHdr->so_number }}</td>
                                        </tr>
                                        <tr>
                                            <td class="tanpa-padding">Tanggal</td><td class="tanpa-padding">: {{ $dnHdr->delivery_date }}</td>
                                        </tr>
                                        <tr>
                                            <td class="tanpa-padding">Jam</td><td class="tanpa-padding">: {{ date('H:i:s') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="tanpa-padding">No Mobil</td><td class="tanpa-padding">:</td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="50%" valign="top" style="border-left: 1px solid #0c0c0c;padding-left:5px" >
                                    <strong>Kepada Yth.</strong><br>
                                        {{ $customers->nama }} <br>
                                        {{ $customers->alamat_kirim_1 }} <br>
                                    
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div class="content">
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
                                    <tr >
                                        <td align="center">{{ ++$no }}</td>
                                        <td align="left">{{ $val->article_alternative_code }}</td>
                                        <td align="left">{{ $val->article_desc }}</td>
                                        <td align="right">{{ number_format($val->qty) }}</td>
                                        <td align="left">{{ $val->uom }}</td>
                                    </tr>
                                @endforeach      
                                                    
                                
                                @if(count($details)>7)
                                    <?php $totalBaris = 16 ?>
                                @else
                                    <?php $totalBaris = 7 ?>
                                @endif

                                @for ($i=1;$i< $totalBaris-(count($details));$i++)
                                    <tr >
                                        <td align="right" class="putih" height="20"></td>
                                        <td align="left"></td>
                                        <td align="left"></td>
                                        <td align="right"></td>
                                        <td align="left"></td>
                                    </tr>
                                @endfor
                                                        
                                <tr style="border: 1px solid #0c0c0c;padding-left:10px">
                                    <td align="right" colspan="3" style="border-right:none">Total Qty :</td>
                                    <td align="right" style="border-left:none;border-right:none"> {{ number_format($totals[0]->qty) }}</td>
                                    <td style="border-left:none"></td>
                                </tr>
                                
                                <tr>
                                    <td colspan="5">Note: </td>
                                </tr>
                            </tbody>
                        </table>
                        <table width="100%">
                            <tr><td colspan="5" height="10"></td></tr>
                            <tr>
                                <td align="center">Created By</td>
                                <td align="center">Checked By</td>
                                <td align="center">Shipped By</td>
                                <td align="center">Security By</td>
                                <td align="center">Received By</td>
                            </tr>
                            <tr>
                                <td align="center" height="20"></td>
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
                    </div>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
            <td>
            <div class="footer-space">
            </div>
            </td>
            </tr>
        </tfoot>
    </table>
</div>
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