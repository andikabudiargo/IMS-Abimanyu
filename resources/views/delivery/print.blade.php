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
            /* box-sizing: border-box; */
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
                height: 215px;
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

        :root {
            /*half black*/
            --line-color: rgba(0, 0, 0, 0.8);
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
            border: thin solid var(--line-color);
            border-collapse: collapse;
        }

        #tblContent  th {
            border: thin solid var(--line-color);
        }

        #tblContent  td {
            padding : 3px 10px 3px 10px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        #tblContent tr:last-child{
            border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
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

        .font-13{
            font-size:11pt;
            /* font-size: medium; */
        }

        .font-16{
            font-size:16pt;
            /* font-size: medium; */
        }

        .font-small{
            font-size: small;
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
                        <table width="100%" style="border: thin solid var(--line-color);padding-left:10px">
                            <tr>
                                <td width="30%">
                                    <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 100%;"> 
                                </td>
                                <td width="20%"></td>
                                <td width="50%" style="vertical-align: bottom;">
                                    <div class="huruf-tebal font-16" style="padding-right:10px">DELIVERY NOTE</div>
                                    <br>
                                    <table>
                                        <tr class="tanpa-padding">
                                            <td class="tanpa-padding font-14" width="50"></td>
                                            <td class="tanpa-padding font-14">Nomor</td>
                                            <td class="tanpa-padding font-14">: {{ $dnHdr->delivery_number }}</td>
                                        </tr>
                                        <tr class="tanpa-padding">
                                            <td class="tanpa-padding font-14"></td>
                                            <td class="tanpa-padding font-14">No.PO#</td>
                                            <td class="tanpa-padding font-14">: {{ $dnHdr->po_number }}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <table style="border-left: thin solid var(--line-color);border-right: thin solid var(--line-color);padding-left:10px" class="font-13 tanpa-padding">
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
                                            <td class="tanpa-padding">No mobil</td><td class="tanpa-padding">:</td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="50%" valign="top" style="border-left: thin solid var(--line-color);padding-left:5px" class="font-small">
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
                            <thead>
                                <tr>
                                    <td width="5%" align="center">No</td>
                                    <td width="15%" align="center">Code</td>
                                    <td width="60%" align="center">Description</td>
                                    <td width="10%" align="center">Qty</td>
                                    <td width="10%" align="center">UOM</td>
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
                                        <td align="right" class="putih" height="16"></td>
                                        <td align="left"></td>
                                        <td align="left"></td>
                                        <td align="right"></td>
                                        <td align="left"></td>
                                    </tr>
                                @endfor
                                                        
                                <tr style="border: thin solid var(--line-color)">
                                    <td colspan="5">Description: </td>
                                </tr>
                            </tbody>
                        </table>
                        <table width="100%">
                            <tr><td colspan="5" height="3"></td></tr>
                            <tr>
                                <td align="center">Created By</td>
                                <td align="center">Checked By</td>
                                <td align="center">Shipped By</td>
                                <td align="center">Security By</td>
                                <td align="center">Received By</td>
                            </tr>
                            <tr>
                                <td align="center" height="25"></td>
                                <td align="center"></td>
                                <td align="center"></td>
                                <td align="center"></td>
                                <td align="center"></td>
                            </tr>
                            <tr>
                                {{-- <td align="center">(<u>{{ str_pad($dnHdr->created_by, 10, "_", STR_PAD_BOTH)  }}</u>)</td> --}}
                                <td align="center">  _____________  </td>
                                <td align="center">  _____________  </td>
                                <td align="center">  _____________  </td>
                                <td align="center">  _____________  </td>
                                <td align="center">  _____________  </td>
                            </tr>
                            <tr>
                                <td align="left" style="padding-left:20px">Date: </td>
                                <td align="left" style="padding-left:20px">Date:</td>
                                <td align="left" style="padding-left:20px">Date:</td>
                                <td align="left" style="padding-left:20px">Date:</td>
                                <td align="left" style="padding-left:20px">Date:</td>
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
    // window.onload= function () {
    //     window.print();
    //     window.onafterprint = function () {
    //         window.close();
    //     }
    //     window.onfocus = function () { 
    //         setTimeout(function () { 
    //             window.close(); 
    //         }, 200); 
    //     }
    // }
</script>
</body>
</html>