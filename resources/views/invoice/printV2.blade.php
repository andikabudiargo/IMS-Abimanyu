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

        /* ── FIX: kolom tblContent & tblContent2 disamakan lewat colgroup
           (lihat markup <colgroup> di kedua tabel). Class .col-* di bawah
           tidak dipakai untuk lebar lagi, cukup sebagai dokumentasi. ── */

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

        /* ── FIX: .font-12 dan .font-14 sebelumnya di-override ke keyword
           "medium" (nilai lebar konstan browser, tidak sesuai namanya),
           sehingga besarnya sama dengan .font-12 walau class-nya beda.
           Dikembalikan ke nilai pt asli supaya proporsional & konsisten. ── */
        .font-12{
            font-size:12pt;
        }

        .font-14{
            font-size:14pt;
        }

        .font-13{
            font-size:11pt;
        }

        .font-16{
            font-size:16pt;
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
                                <td width="46%" align="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px" >
                                    <h2 style="margin:0px">INVOICE</h2>
                                </td>
                                <td style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                    <b style="font-size:17px" >{{ $recHdr->invoice_number }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td width="46%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                    <strong> Customer: </strong><br>
                                    {{ $customers->nama }} <br>
                                    {{ $customers->alamat_kirim_1 }} <br>
                                    @if(strlen($customers->alamat_kirim_1)<60)
                                    <br>
                                    @endif
                                    <strong>No. NPWP : </strong> {{ $customers->npwp }}</strong>
                                </td>
                                <td width="38%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px;font-size:13px">
                                    <strong style="font-size:15px">PO Number : </strong>{{ $listpo }}<br>
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
                {{-- ── FIX: colgroup tunggal yang jadi acuan lebar kolom.
                     Sum = 100% persis (5+42+8+10+8.5+14+12.5=100), dan
                     colgroup yang SAMA dipakai lagi di tblContent2 di bawah
                     supaya kolom item & kolom total dijamin sejajar. ── --}}
                <colgroup>
                    <col style="width:5%">
                    <col style="width:42%">
                    <col style="width:8%">
                    <col style="width:10%">
                    <col style="width:8.5%">
                    <col style="width:14%">
                    <col style="width:12.5%">
                </colgroup>
                <thead>
                    <tr style="height: 32px;font-size:11pt;">
                        <th>No</th>
                        <th>Description</th>
                        <th align="center">Qty</th>
                        <th>Material Price</th>
                        <th>Service Price</th>
                        <th>Total Material</th>
                        <th>Total Service</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($details as $val )
                        @if(count($details)>= 15 && count($details)<= 24)
                            <tr style="font-size: 10pt;height:18.5px">
                        @else
                            <tr style="font-size: 11pt;height:21px">
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
                   @for ($i = count($details) + 1; $i <= 22; $i++)
    <tr style="height:21px">
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
        @if($duaHalaman=='no')
            <div class="sub_div">
                <table id="tblContent2" style="table-layout:fixed;">
                    {{-- ── FIX: colgroup identik dengan tblContent di atas.
                         Kolom 1-3 (No+Description+Qty = 55%) dipakai untuk
                         sel "Terbilang" (colspan=3). Kolom 4-5 (Material
                         Price+Service Price = 18.5%) untuk label. Kolom 6-7
                         (Total Material+Total Service = 26.5%) untuk nilai.
                         Karena colgroup sama persis, garis vertikal tabel
                         total otomatis lurus dengan garis tabel item. ── --}}
                    <colgroup>
                        <col style="width:5%">
                        <col style="width:42%">
                        <col style="width:8%">
                        <col style="width:8.5%">
                        <col style="width:10%">
                        <col style="width:14%">
                        <col style="width:12.5%">
                    </colgroup>
                    <tbody>
                        @foreach ($totals as $val )   
                            <tr style="height:25px">
                                <td colspan="3" rowspan="6" style="border-bottom: 1px solid black;">
                                    <table style="table-layout:fixed;">
                                        <tr>
                                            <td style="border-right: none;border-left: none;padding-right:0px" width="16%" valign="top"><b>Terbilang : </b></td>
                                            <td style="border-right: none;border-left: none;padding-left:0px"><i class="arial" style="font-size: 10pt;">{{ ucwords(strtolower($terbilang)) }}</i></td>
                                        </tr>
                                    </table>
                                </td>
                                <td colspan="2" style="border: 1px solid #0c0c0c;">Subtotal</td>
                                <td align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->total_material,2) }}</td>
                                <td align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->total_service,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">Selling Price</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->sub_total,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">VAT Object</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->dpp_lain_value,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">VAT {{ $nilaiPPN }}% </td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->ppn,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">WHT 23</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ $val->pph23 ? '-'.number_format($val->pph23,2):'-' }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">Total Bill</td>
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
     </div>

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
                                    <td width="46%" align="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px" >
                                        <h2 style="margin:0px">INVOICE</h2>
                                    </td>
                                    <td style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                        <b style="font-size:17px" >{{ $recHdr->invoice_number }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="46%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                        <strong> Customer: </strong><br>
                                        {{ $customers->nama }} <br>
                                        {{ $customers->alamat_kirim_1 }} <br>
                                        @if(strlen($customers->alamat_kirim_1)<60)
                                        <br>
                                        @endif
                                        <strong>No. NPWP : </strong> {{ $customers->npwp }}</strong>
                                    </td>
                                    <td width="38%" valign="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px;font-size:13px">
                                        <strong style="font-size:15px">PO Number : </strong>{{ $listpo }}<br>
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
                    <colgroup>
                        <col style="width:5%">
                        <col style="width:42%">
                        <col style="width:8%">
                        <col style="width:10%">
                        <col style="width:8.5%">
                        <col style="width:14%">
                        <col style="width:12.5%">
                    </colgroup>
                    <thead>
                        <tr style="height: 32px;font-size:11pt;">
                            <th>No</th>
                            <th>Description</th>
                            <th align="center">Qty</th>
                            <th>Material Price</th>
                            <th>Service Price</th>
                            <th>Total Material</th>
                            <th>Total Service</th>
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
                        @endforeach
                        <?php $totalBaris = 30 ?>
                        @for ($i=1;$i< $totalBaris-(count($details2));$i++)
                            <tr style="height:23px">
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
            <div class="sub_div">
                <table id="tblContent2" style="table-layout:fixed;">
                    <colgroup>
                        <col style="width:5%">
                        <col style="width:42%">
                        <col style="width:8%">
                        <col style="width:8.5%">
                        <col style="width:10%">
                        <col style="width:14%">
                        <col style="width:12.5%">
                    </colgroup>
                    <tbody>
                        @foreach ($totals as $val )   
                            <tr style="height:25px">
                                <td colspan="3" rowspan="6" style="border-bottom: 1px solid black;">
                                    <table style="table-layout:fixed;">
                                        <tr>
                                            <td style="border-right: none;border-left: none;padding-right:0px" width="16%" valign="top"><b>Terbilang : </b></td>
                                            <td style="border-right: none;border-left: none;padding-left:0px"><i class="arial" style="font-size: 10pt;">{{ ucwords(strtolower($terbilang)) }}</i></td>
                                        </tr>
                                    </table>
                                </td>
                                <td colspan="2" style="border: 1px solid #0c0c0c;">Subtotal</td>
                                <td align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->total_material,2) }}</td>
                                <td align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->total_service,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">Selling Price</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->sub_total,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">VAT Object</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->dpp_lain_value,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">VAT {{ $nilaiPPN }}% </td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ number_format($val->ppn,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">WHT 23</td>
                                <td colspan="2" align="right" style="border: 1px solid #0c0c0c;">{{ $val->pph23 ? '-'.number_format($val->pph23,2):'-' }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td colspan="2" style="border: 1px solid #0c0c0c;">Total Bill</td>
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
            </div>
        </div>
    @endif
    <script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
    <script>
        let table = document.getElementById("tblContent");

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