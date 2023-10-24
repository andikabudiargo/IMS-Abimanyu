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
        body.A4A5         .sheet { width: 210mm; height: 148mm }
        body.A4.landscape .sheet { width: 297mm; height: 209mm }
        body.A5           .sheet { width: 148mm; height: 209mm }
        body.A5.landscape .sheet { width: 210mm; height: 147mm }
        body.Letter           .sheet { width: 215mm; height: 296mm }
        body.Letter.landscape .sheet { width: 297mm; height: 215mm }

        /** Padding area **/
        .sheet.padding-10mm { padding: 10mm }
        .sheet.padding-5mm { padding: 5mm }
        .sheet.padding-3mm { padding: 3mm }
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
                height: 125px;
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

            .hide-print {
                display: none;
            }


            .putih1{
                color:white !important;
            }

            .fprint p{
                color:white !important;
            }

        }
        
        * {
            font-family: Calibri,Arial, Helvetica, sans-serif;
        }

        table{
            font-family: Calibri,Arial, Helvetica, sans-serif;
        }

        .arial{
            font-family: Arial, Helvetica, sans-serif;
            /* font-family: Arial, Helvetica, sans-serif; */
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
            height: 25px;
        }

        #tblContent tr:last-child{
            border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        #tblContent1{
            /* border: thin solid var(--line-color); */
            border-collapse: collapse;
        }

        #tblContent1  td {
            /* padding : 3px 10px 3px 10px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-top: thin solid var(--line-color);
            border-right: thin solid var(--line-color); */
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
<body class="Letter">
    <div class="row hide-print" style="margin-left:20px;margin-top:20px">
        <div class="col-md-12">
            <button class="btn btn-primary" type="button" id="cmdPrint" name="cmdPrint">Print</button>
        </div>
    </div>
<div class="sheet" style="padding:5mm 8mm 5mm 8mm">
    <table>
        <thead>
            <tr>
                <td>
                    <div class="header-space">
                        <table width="100%" class="font-13">
                            <tr>
                                <td width="30%" >
                                    <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 20%;"> 
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta
                                </td>
                            </tr>
                        </table>
                        <p style="margin-top:0px;margin-bottom:5px;padding:0 2px 0 2px">NPWP : 31.284.174.5-416.000</p>                     
                    </div>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <table width="100%">
                        <tr>
                            <td width="60%" align="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px" >
                                <h2 style="margin:0px">INVOICE</h2>
                            </td>
                            <td style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                <b style="font-size:17px" >{{ $recHdr->invoice_number }}</b>
                            </td>
                        </tr>
                        <tr>
                            <td width="64%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                <strong> Customer: </strong><br>
                                {{ $customers->nama }} <br>
                                {{ $customers->alamat_kirim_1 }} <br>
                                <strong>No. NPWP : </strong> {{ $customers->npwp }}
                            </td>
                            <td width="36%" valign="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                
                                <strong>PO Number : </strong>{{ $listpo }}<br>
                                {{-- <strong>No FP : </strong>{{ $recHdr->faktur_pajak }} --}}
                                {{-- <br><p></p> --}}
                            </td>
                        </tr>
                    </table>
                    <div style="padding: 0 2px 0 2px">
                    <table id="tblContent" class="font-14" style="table-layout:fixed;">
                        <thead>
                            <tr style="line-height: 25px;">
                                <th width="5%">No</th>
                                <th width="50%" >Description</th>
                                <th width="10%" align="center">Qty</th>
                                <th width="12%">Material Price</th>
                                <th width="12%">Service Price</th>
                                <th width="15%">Total Material</th>
                                <th width="15%">Total Service</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($details as $val )
                            <tr style="font-size: 11pt">
                                <td style="border-right: 1px solid black;border-bottom: none;" align="center" scope="row" >{{ ++$no }}</td>
                                {{-- <td  align="left">{{ $val->article_alternative_code }}</td> --}}
                                <td  style="border-right: 1px solid black;" align="left">{{ $val->article_desc }}</td>
                                <td  style="border-right: 1px solid black;" align="center">{{ number_format($val->qty) }}</td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format($val->price,2) }}</td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format($val->price_service,2) }}</td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format(($val->qty*$val->price),2) }}</td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format(($val->qty*$val->price_service),2) }}</td>
                            </tr>
                                                       
                        @endforeach
                        
                        <?php $totalBaris = 13 ?>

                        @for ($i=1;$i< $totalBaris-(count($details));$i++)
                            <tr >
                                {{-- <td style="border-right: 1px solid black;" class="putih" height="25"></td>
                                <td style="border-right: 1px solid black;"  style="color:white !important"></td> --}}
                                <td style="border-right: 1px solid black;" ><div style="height:35px;"></div></td>
                                <td style="border-right: 1px solid black;" ></td>
                                <td style="border-right: 1px solid black;" ></td>
                                <td style="border-right: 1px solid black;" ></td>
                                <td style="border-right: 1px solid black;" ></td>
                                <td style="border-right: 1px solid black;" ></td>
                                <td style="border-right: 1px solid black;" ></td>
                            </tr>
                        @endfor
                        <tr>
                            <td  align="left"  style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                            <td  align="left"  style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                            <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                            <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                            <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                            <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                            <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                        </tr>
                        </tbody>
                        <tfoot>
                            @foreach ($totals as $val )            
                                <tr>
                                    {{-- <td colspan="4" rowspan="4" style="border-bottom: 1px solid black;"><b>Terbilang : </b><i>{{ ucwords(strtolower($terbilang)) }}</i> </td> --}}
                                    <td colspan="4" rowspan="4" style="border-bottom: 1px solid black;">
                                        <table style="table-layout:fixed;">
                                            <tr>
                                                <td style="border-right: none;border-left: none;padding-right:0px" width="15%" valign="top"><b>Terbilang : </b></td>
                                                <td style="border-right: none;border-left: none;padding-left:0px"><i class="arial" style="font-size: 10pt;">{{ ucwords(strtolower($terbilang)) }}</i></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td colspan="" style="border: 1px solid #0c0c0c;padding-left:10px">DPP</td>
                                    <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->sub_total,2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="" style="border: 1px solid #0c0c0c;padding-left:10px">PPN {{ $nilaiPPN }}% </td>
                                    <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->ppn,2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="" style="border: 1px solid #0c0c0c;padding-left:10px">PPH 23</td>
                                    <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ $val->pph23 ? '-'.number_format($val->pph23,2):'-' }}</td>
                                </tr>
                                <tr>
                                    <td colspan="" style="border: 1px solid #0c0c0c;padding-left:10px">Total</td>
                                    <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->grand_total,2) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class = "arial" valign="top" width="60%" colspan="5" style="border-right: 1px solid white;font-size: 11pt;">
                                    Note:<br>
                                    <span style="font-size: 11pt;">
                                    Please transfer to our account <br>	
                                    Mohon transfer ke rekening kami	<br>
                                    Bank BCA No. Rek : <b>6785577888</b><br>
                                    Cabang KC Purwakarta<br></span>
                                    <span style="font-size: 11pt;">
                                    a.n PT. Abimanyu Sekar Nusantara<br><br>
                                    Attention/ perhatian<br>
                                    - Faktur ini berlaku sebagai Kwitansi.<br>
                                    - Pembayaran dengan Cheque / Bilyet atau Wesel dianggap lunas setelah melalui Clearing
                                    </span>
                                </td>
                                <td class = "arial" valign="top" colspan="2" align="center" style="font-size: 11pt;">
                                    <br>
                                        Purwakarta, {{ $tanggalHariIni }} <br>
                                        <br><br><br><br><br><br><br>
                                        (&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Budi Mulyadi &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp)
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    </div>
                    <span class = "arial" style="font-size: 10pt;"><i>Lembar Asli untuk Penagihan kepada Customer</i></span><br>
                    <span class = "arial" style="font-size: 10pt;;"><i>Lembar Copy untuk Arsip</i></span>
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
<script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
<script>
    $("#cmdPrint").click(function(){ 
        window.print();
        window.onafterprint = function () {
            window.close();
        }
        window.onfocus = function () { 
            setTimeout(function () { 
                window.close(); 
            }, 200); 
        }
    });
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