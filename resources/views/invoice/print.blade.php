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
        /* body.Letter           .sheet { width: 215mm; height: 296mm } */
        body.Letter           .sheet { width: 215mm; height: 300mm }
        /* body.Letter           .sheet { width: 230mm; height: 310mm } */
        body.Letter.landscape .sheet { width: 297mm; height: 215mm }

        /** Padding area **/
        .sheet.padding-10mm { padding: 10mm }
        .sheet.padding-5mm { padding: 5mm }
        .sheet.padding-3mm { padding: 3mm }
        .sheet.padding-15mm { padding: 15mm }
        .sheet.padding-20mm { padding: 20mm }
        .sheet.padding-25mm { padding: 25mm }
        .sheet.padding-8mm { padding: 8mm }

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
            /* --line-color: rgba(0, 0, 0, 0.8); */
            --line-color: rgba(0, 0, 0);
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
            /* font-family: Arial, Helvetica, sans-serif; */
        }

        table{
            font-family: Calibri,Arial, Helvetica, sans-serif;
            /* font-family: Arial, Helvetica, sans-serif; */
        }

        .arial{
            font-family: Arial, Helvetica, sans-serif;
            /* font-family: Arial, Helvetica, sans-serif; */
        }
        
        table {
            width: 100%;
        }

        #tblContent{
            /* border: thin solid var(--line-color); */
            border-collapse: collapse;
        }

        #tblContent  th {
            border: thin solid var(--line-color);
        }

        #tblContent  td {
            padding : 0px 2px 0px 4px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
            /* height: 25px; */
        }

        #tblContent tr:last-child{
            /* border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color); */
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

        #tblContent3{
            border: thin solid var(--line-color);
            border-collapse: collapse;
            border-top:none;
        }

        #tblContent3  td {
            padding : 0px 10px 0px 10px;
        }

        #tblContent4{
            border: thin solid var(--line-color);
            border-collapse: collapse;
            border-top:none;
        }

        #tblContent4  td {
            padding : 0px 10px 0px 10px;
        }
      
        #tblContent2{
            border: thin solid var(--line-color);
            border-collapse: collapse;
        }

        #tblContent2  th {
            border: thin solid var(--line-color);
        }

        #tblContent2  td {
            padding : 0px 10px 0px 5px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
            /* height: 25px; */
            font-size:11pt;
        }

        #tblContent2 tr:last-child{
            border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        .sub_div {
            position: absolute;
            /* margin-right: 8mm; */
            /* bottom: 18px; */
            padding-bottom:18px;
            bottom: 0px;
            background-color:white;
            width  : 803px;
            margin-left : 1.4mm;
        }

        .sub_div2 {
            position: absolute;
            /* margin-right: 8mm; */
            bottom: 55px;
            background-color:white;
            width  : 803px;
            margin-left : 1.4mm;
        }

        .sub_div3 {
            position: absolute;
            /* margin-right: 8mm; */
            bottom: 0px;
            background-color:white;
            width  : 803px;
            margin-left : 1.4mm;
        }

        .sub_div_tengah {
            /* border : thin solid var(--line-color); */
            height : 529px;
            width  : 803px;
            position: absolute;
            margin-left : 1.4mm;
            bottom: 355px;
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
                                    <td width="50%" style="padding-top:10px;padding-left:5px" >
                                        <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 20%;"> 
                                    </td>
                                </tr>
                                {{-- <tr>
                                    <td colspan="2">
                                        Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta
                                    </td>
                                </tr> --}}
                            </table>
                            <p style="margin-top:0px;margin-bottom:5px;padding:0 2px 0 2px" class="font-13">Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta<br>NPWP : 31.284.174.5-416.000</p>
                        </div>
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <table width="100%">
                            <tr>
                                <td width="65%" align="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px" >
                                    <h2 style="margin:0px">INVOICE</h2>
                                </td>
                                <td style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                    <b style="font-size:17px" >{{ $recHdr->invoice_number }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td width="65%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                    <strong> Customer: </strong><br>
                                    {{ $customers->nama }} <br>
                                    {{ $customers->alamat_kirim_1 }} <br>
                                    @if(strlen($customers->alamat_kirim_1)<69)
                                    <br>
                                    @endif
                                    <strong>No. NPWP : </strong> {{ $customers->npwp }}</strong>
                                </td>
                                <td width="38%" valign="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                    <strong>PO Number : </strong>{{ $listpo }}<br>
                                </td>
                            </tr>
                        </table>
                        <div style="padding: 0 2px 0 2px">
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
        <div class="sub_div_tengah">
            <table id="tblContent" class="font-14" style="table-layout:fixed;">
                <thead>
                    <tr style="height: 50px;">
                        <th width="6%">No</th>
                        <th width="50%" >Description</th>
                        <th width="10%" align="center">Qty</th>
                        <th width="12%">Material Price</th>
                        <th width="10.2%">Service Price</th>
                        <th width="16.9%">Total Material</th>
                        <th width="14.8%">Total Service</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($details as $val )
                        @if(count($details)>= 15 && count($details)<= 24)
                            {{-- <tr style="font-size: 11pt;height:23px"> --}}
                            <tr style="font-size: 10pt;height:19.5px">
                        @else
                            {{-- <tr style="font-size: 11pt;height:23px" class="isiTabel1"> --}}
                            <tr style="font-size: 11pt;height:23px">
                        @endif
                            <td style="border-right: 1px solid black;border-bottom: none;" align="center" scope="row" >{{ ++$no }}</td>
                            <td  style="border-right: 1px solid black;" align="left">{{ $val->article_desc }}</td>
                            <td  style="border-right: 1px solid black;" align="center">{{ fmod($val->qty, 1) !== 0.0 ? number_format($val->qty,2) : number_format($val->qty) }}</td>
                            <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format($val->price,2) }}</td>
                            <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format($val->price_service,2) }}</td>
                            <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format(($val->qty*$val->price),2) }}</td>
                            <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format(($val->qty*$val->price_service),2) }}</td>
                        </tr>
                    @endforeach
                    <?php $totalBaris = 40 ?>
                    @for ($i=1;$i< $totalBaris-(count($details));$i++)
                        {{-- @if(count($details)> 19)
                            <tr style="height:23px">
                        @else
                            <tr style="height:25px">
                        @endif --}}
                        <tr style="height:38px">
                            <td ></td>
                            <td ></td>
                            <td ></td>
                            <td ></td>
                            <td ></td>
                            <td ></td>
                            <td ></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        {{-- @if(count($details2)==0) --}}
        @if($duaHalaman=='no')
            <div class="sub_div">
                <table id="tblContent2" style="table-layout:fixed;">
                    <tbody>
                        @foreach ($totals as $val )   
                            <tr style="height:25px">
                                <td width="59.5%" colspan="4" rowspan="5" style="border-bottom: 1px solid black;">
                                    <table style="table-layout:fixed;">
                                        <tr>
                                            <td style="border-right: none;border-left: none;padding-right:0px" width="15%" valign="top"><b>Terbilang : </b></td>
                                            <td style="border-right: none;border-left: none;padding-left:0px"><i class="arial" style="font-size: 10pt;">{{ ucwords(strtolower($terbilang)) }}</i></td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="6%" colspan="" style="border: 1px solid #0c0c0c;">Subtotal</td>
                                <td width="11%" colspan="" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->total_material,2) }}</td>
                                <td width="9.4%" colspan="" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->total_service,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td width="5%" colspan="" style="border: 1px solid #0c0c0c;">DPP</td>
                                <td width="23.3%" colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->sub_total,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="" style="border: 1px solid #0c0c0c;">PPN {{ $nilaiPPN }}% </td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->ppn,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="" style="border: 1px solid #0c0c0c;">PPH 23</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ $val->pph23 ? '-'.number_format($val->pph23,2):'-' }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="" style="border: 1px solid #0c0c0c;">Total</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->grand_total,2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <table id="tblContent3">
                                <tr>
                                    <td class = "arial" valign="top" width="70%" colspan="5" style="font-size: 11pt;">
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
                                    <td class="arial" valign="top" colspan="2" align="center" style="font-size: 11pt;padding-left:0px">
                                        <br>
                                        Purwakarta, {{ $tanggalHariIni }} <br>
                                        <br><br><br><br><br><br><br>
                                        (&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Budi Mulyadi &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp)
                                    </td>
                                </tr>
                            </table>
                            
                        </tr>
                    </tbody>
                </table>
                <span class = "arial" style="font-size: 10pt;"><i>Lembar Asli untuk Penagihan kepada Customer</i></span><br>
                <span class = "arial" style="font-size: 10pt;"><i>Lembar Copy untuk Arsip</i></span>
            </div>
        @else
            <div class="sub_div2">
                <table id="tblContent2" style="table-layout:fixed;">
                    <tbody>
                        <tr>
                            <table id="tblContent4">
                                <tr>
                                    <td class="arial" valign="top"  align="center" >
                                    </td>
                                </tr>
                            </table>
                        </tr>
                    </tbody>
                </table>
                <table>
                    <tr>
                        <td>
                        </td>
                        <td align="right" width="10%"> Page 1 of 2</td>
                    </tr>
                </table>
            </div>
            <div class="sub_div3">
                <table id="" style="table-layout:fixed;">
                    <tbody>
                        <tr>
                            <table id="">
                                <tr>
                                    <td class="arial" valign="top"  align="center" >
                                    </td>
                                </tr>
                            </table>
                        </tr>
                    </tbody>
                </table>
                <table>
                    <tr>
                        <td align="right" width="10%" height="45"> </td>
                    </tr>
                </table>
            </div>
        @endif
    </div>

    {{-- @if(count($details2)>0) --}}
    @if($duaHalaman=='yes')
        <div class="sheet" style="padding:5mm 8mm 5mm 8mm">
            <table>
                <thead>
                    <tr>
                        <td>
                            <div class="header-space">
                                <table width="100%" class="font-13">
                                    <tr>
                                        <td width="50%" style="padding-top:10px;padding-left:5px" >
                                            <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 20%;"> 
                                        </td>
                                    </tr>
                                    {{-- <tr>
                                        <td colspan="2">
                                            Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta
                                        </td>
                                    </tr> --}}
                                </table>
                                <p style="margin-top:0px;margin-bottom:5px;padding:0 2px 0 2px" class="font-13">Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta<br>NPWP : 31.284.174.5-416.000</p>
                            </div>
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <table width="100%">
                                <tr>
                                    <td width="65%" align="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px" >
                                        <h2 style="margin:0px">INVOICE</h2>
                                    </td>
                                    <td style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                        <b style="font-size:17px" >{{ $recHdr->invoice_number }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="65%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                        <strong> Customer: </strong><br>
                                        {{ $customers->nama }} <br>
                                        {{ $customers->alamat_kirim_1 }} <br>
                                        @if(strlen($customers->alamat_kirim_1)<69)
                                        <br>
                                        @endif
                                        <strong>No. NPWP : </strong> {{ $customers->npwp }}</strong>
                                    </td>
                                    <td width="38%" valign="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                        <strong>PO Number : </strong>{{ $listpo }}<br>
                                    </td>
                                </tr>
                            </table>
                            <div style="padding: 0 2px 0 2px">
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
            <div class="sub_div_tengah">
                <table id="tblContent" class="font-14" style="table-layout:fixed;">
                    <thead>
                        <tr style="height: 50px;">
                            <th width="6%">No</th>
                            <th width="50%" >Description</th>
                            <th width="10%" align="center">Qty</th>
                            <th width="12%">Material Price</th>
                            <th width="12%">Service Price</th>
                            <th width="15%">Total Material</th>
                            <th width="15%">Total Service</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details2 as $val )
                            @if(count($details)> 19)
                                <tr style="font-size: 11pt;height:22px">
                            @else
                                <tr style="font-size: 11pt;height:23px">
                            @endif
                                <td style="border-right: 1px solid black;border-bottom: none;" align="center" scope="row" >{{ ++$no }}</td>
                                <td  style="border-right: 1px solid black;" align="left">{{ $val->article_desc }}</td>
                                <td  style="border-right: 1px solid black;" align="center">{{ number_format($val->qty) }}</td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format($val->price,2) }}</td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format($val->price_service,2) }}</td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format(($val->qty*$val->price),2) }}</td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right">{{ number_format(($val->qty*$val->price_service),2) }}</td>
                            </tr>
                            {{-- <tr style="font-size: 11pt;height:23px">
                                <td  style="border-right: 1px solid black;border-bottom: none;" align="center" scope="row" ></td>
                                <td  style="border-right: 1px solid black;" align="left"></td>
                                <td  style="border-right: 1px solid black;" align="center"></td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right"></td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right"></td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right"></td>
                                <td  style="border-right: 1px solid black;padding:0 3px 0 3px" align="right"></td>
                            </tr> --}}
                        @endforeach
                        <?php $totalBaris = 30 ?>
                        @for ($i=1;$i< $totalBaris-(count($details2));$i++)
                            <tr style="height:23px">
                                <td ></div></td>
                                <td ></td>
                                <td ></td>
                                <td ></td>
                                <td ></td>
                                <td ></td>
                                <td ></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <div class="sub_div">
                <table id="tblContent2" style="table-layout:fixed;">
                    <tbody>
                        @foreach ($totals as $val )   
                            <tr style="height:25px">
                                <td width="59%" colspan="4" rowspan="5" style="border-bottom: 1px solid black;">
                                    <table style="table-layout:fixed;">
                                        <tr>
                                            <td style="border-right: none;border-left: none;padding-right:0px" width="15%" valign="top"><b>Terbilang : </b></td>
                                            <td style="border-right: none;border-left: none;padding-left:0px"><i class="arial" style="font-size: 10pt;">{{ ucwords(strtolower($terbilang)) }}</i></td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="7.2%" colspan="" style="border: 1px solid #0c0c0c;">Subtotal</td>
                                <td width="9.5%" colspan="" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->total_material,2) }}</td>
                                <td width="9.4%" colspan="" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->total_service,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td width="6.7%" colspan="" style="border: 1px solid #0c0c0c;">DPP</td>
                                <td width="23.3%" colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->sub_total,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="" style="border: 1px solid #0c0c0c;">PPN {{ $nilaiPPN }}% </td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->ppn,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="" style="border: 1px solid #0c0c0c;">PPH 23</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ $val->pph23 ? '-'.number_format($val->pph23,2):'-' }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="" style="border: 1px solid #0c0c0c;">Total</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->grand_total,2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <table id="tblContent3">
                                <tr>
                                    <td class = "arial" valign="top" width="70%" colspan="5" style="font-size: 11pt;">
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
                                    <td class="arial" valign="top" colspan="2" align="center" style="font-size: 11pt;padding-left:0px">
                                        <br>
                                        Purwakarta, {{ $tanggalHariIni }} <br>
                                        <br><br><br><br><br><br><br>
                                        (&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Budi Mulyadi &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp)
                                    </td>
                                </tr>
                            </table>
                            
                        </tr>
                    </tbody>
                </table>
                <table>
                    <tr>
                        <td>
                            <span class = "arial" style="font-size: 10pt;"><i>Lembar Asli untuk Penagihan kepada Customer</i></span><br>
                            <span class = "arial" style="font-size: 10pt;"><i>Lembar Copy untuk Arsip</i></span>
                        </td>
                        <td align="right"  valign="top" width="10%">  Page 2 of 2</td>
                    </tr>
                </table>
                {{-- <span class = "arial" style="font-size: 10pt;"><i>Lembar Asli untuk Penagihan kepada Customer</i></span><br>
                <span class = "arial" style="font-size: 10pt;"><i>Lembar Copy untuk Arsip</i></span> --}}
            </div>
        </div>
    @endif
    <script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
    <script>
        let table = document.getElementById("tblContent");
        // alert(table.offsetHeight);
        // alert(table.clientHeight);
        // if (table.offsetHeight >= 1300){
        //     $('.isiTabel1').css({
        //         'font-size' : '10pt',
        //         'height' : '20px'
        //     });
        // }

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
    </script>
</body>
</html>