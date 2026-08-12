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
        body.Letter           .sheet { width: 215mm; height: 300mm }
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

        .putih{ color:white; }
        .header, .header-space{ height: 125px; }
        .footer, .footer-space { height: 170px; }
        .header { position: fixed; top: 0; }
        .footer { position: fixed; bottom: 0; }

        :root { --line-color: rgba(0, 0, 0); }

        @media print {
            header, footer { position: fixed; top: 0; }
            footer { position: fixed; bottom: 0; }
            @page :footer { display: none }
            @page :header { display: none }
            .tanpa-padding{ padding:0px; }
            .hide-print { display: none; }
            .putih1{ color:white !important; }
            .fprint p{ color:white !important; }
        }

        * { font-family: Calibri,Arial, Helvetica, sans-serif; }
        table{ font-family: Calibri,Arial, Helvetica, sans-serif; }
        .arial{ font-family: Arial, Helvetica, sans-serif; }
        table { width: 100%; }

        /* ── Tabel item (#tblContent) ──────────────────────────────── */
        #tblContent{ border-collapse: collapse; }
        #tblContent  th { border: thin solid var(--line-color); }
        #tblContent  td {
            padding : 0px 10px 0px 10px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }
        /* FIX: sebelumnya rule ini kosong/di-comment sehingga tabel item
           tidak pernah punya garis penutup di bawah, membuat kotak tabel
           terlihat "bocor" di kiri-kanan-bawah. Diaktifkan supaya tabel
           selalu tampak sebagai satu kotak utuh, baik saat ditutup oleh
           box totals (mode satu halaman) maupun saat berdiri sendiri di
           halaman tanpa totals (halaman 1 dari 2 halaman). */
        #tblContent tr:last-child td{
            border-bottom: thin solid var(--line-color);
        }

        #tblContent1{ border-collapse: collapse; }

        .tableHeader td{ padding-bottom: 0px; padding-top: 0px; }

        /* FIX: .font-12 dan .font-14 sebelumnya di-override ke keyword
           "medium" sehingga ukurannya identik walau nama class beda.
           Dikembalikan ke nilai pt asli. */
        .font-12{ font-size:12pt; }
        .font-14{ font-size:14pt; }
        .font-13{ font-size:11pt; }
        .font-16{ font-size:16pt; }
        .font-small{ font-size: small; }

        .tanpa-padding{ padding:0px; }
        .huruf-tebal{ font-weight: bold; }

        /* ── Box totals (#tblContent2) ─────────────────────────────── */
        #tblContent2{ border: thin solid var(--line-color); border-collapse: collapse; }
        #tblContent2  th { border: thin solid var(--line-color); }
        #tblContent2  td {
            padding : 0px 10px 0px 10px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }
        #tblContent2 tr:last-child{
            border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        .sub_div {
            position: absolute;
            bottom: 18px;
            background-color:white;
            width  : 803px;
            margin-left : 1.4mm;
        }

        .sub_div_tengah {
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

    {{--
        Ringkasan alur:
        - $duaHalaman == 'no'  → satu halaman. $details berisi SEMUA item.
                                  Totals + note + ttd tampil di bawah tabel item
                                  (overlay .sub_div, posisi tetap dari bawah kertas).
        - $duaHalaman == 'yes' → dua halaman. $details berisi item halaman 1
                                  (dibatasi controller, idealnya konsisten ~30 baris),
                                  $details2 berisi sisanya untuk halaman 2.
                                  Halaman 1 TIDAK ada totals, cuma nomor halaman.
                                  Totals + note + ttd pindah ke halaman 2.

        Kapasitas baris ($totalBaris) untuk tiap tabel item HARDCODE, bukan
        dihitung dinamis, karena jumlah baris per halaman pada sistem ini
        sudah dibatasi tetap oleh controller (bukan bervariasi bebas).
        Kalau nanti batas baris di controller berubah, angka $totalBaris di
        bawah ini yang perlu disesuaikan (tinggal render lalu lihat apakah
        baris terakhir/garis bawah tabel pas nempel ke elemen di bawahnya —
        box totals untuk mode 'no', atau teks "Page 1 of 2" untuk mode 'yes').
    --}}

    {{-- ══════════════════ HALAMAN 1 ══════════════════ --}}
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
                                <td width="60%" align="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px" >
                                    <h2 style="margin:0px">INVOICE</h2>
                                </td>
                                <td style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                    <b style="font-size:17px" >{{ $recHdr->invoice_number }}</b>
                                </td>
                            </tr>
                            <tr>
                                <td width="60%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                    <strong> Customer: </strong><br>
                                    {{ $customers->nama }} <br>
                                    {{ $customers->alamat_kirim_1 }} <br>
                                    @if(strlen($customers->alamat_kirim_1)<69)
                                    <br>
                                    @endif
                                    <strong>No. NPWP : </strong> {{ $customers->npwp }}</strong>
                                </td>
                                <td width="38%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px;font-size:12px">
                                    <strong style="font-size:15px">PO Number : </strong>{{ $listpo }}<br>
                                </td>
                            </tr>
                        </table>
                        <div style="padding: 0 2px 0 2px"></div>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                <td>
                <div class="footer-space"></div>
                </td>
                </tr>
            </tfoot>
        </table>

        <div class="sub_div_tengah">
            <table id="tblContent" class="font-14" style="table-layout:fixed;">
                <thead>
                    <tr style="height: 35px;">
                        <th width="4.5%">No</th>
                        <th width="51.5%">Description</th>
                        <th width="8.5%" align="center">Qty</th>
                        @if($printType=='1')
                        <th width="12%">Price</th>
                        <th width="15%">Total</th>
                        @else
                        <th width="12%">Service Price</th>
                        <th width="15%">Total Service</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($details as $val )
                        <tr style="font-size: 11pt;height:23px">
                            <td align="center" scope="row">{{ ++$no }}</td>
                            <td align="left">{{ $val->article_desc }}</td>
                            <td align="center">{{ fmod($val->qty, 1) !== 0.0 ? number_format($val->qty,2) : number_format($val->qty) }}</td>
                            @if($printType=='1')
                            <td align="right">{{ number_format($val->price,2) }}</td>
                            <td align="right">{{ number_format(($val->qty*$val->price),2) }}</td>
                            @else
                            <td align="right">{{ number_format($val->price_service,2) }}</td>
                            <td align="right">{{ number_format(($val->qty*$val->price_service),2) }}</td>
                            @endif
                        </tr>
                    @endforeach

                    {{--
                        - $duaHalaman=='no'  : totals menutupi bagian bawah tabel ini
                          (overlay .sub_div), kapasitas dijaga 27 baris supaya baris
                          terakhir pas tertutup box totals.
                        - $duaHalaman=='yes' : halaman ini tidak ada box totals,
                          kapasitas 33 baris supaya penuh mendekati teks "Page 1 of 2".
                          Kalau masih ada gap atau malah numbuk teksnya, tinggal
                          naik/turunkan angka 33 ini saja.
                    --}}
                    <?php $totalBaris = $duaHalaman=='yes' ? 38 : 27; ?>
                    @for ($i=1; $i < $totalBaris-(count($details)); $i++)
                        <tr style="height:23px">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        @if($duaHalaman=='no')
            {{-- Satu halaman saja: totals + note + ttd langsung di sini --}}
            <div class="sub_div">
                <table id="tblContent2" style="table-layout:fixed;">
                    <tbody>
                        @foreach ($totals as $val )
                            <tr style="height:25px">
                                <td colspan="3" rowspan="5" style="border-bottom: 1px solid black;">
                                    <table style="table-layout:fixed;">
                                        <tr>
                                            <td style="border-right: none;border-left: none;padding-right:0px" width="15%" valign="top"><b>Terbilang : </b></td>
                                            <td style="border-right: none;border-left: none;padding-left:0px"><i class="arial" style="font-size: 10pt;">{{ ucwords(strtolower($terbilang)) }}</i></td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="10.6%" style="border: 1px solid #0c0c0c;padding-left:10px">Selling Price</td>
                                <td width="13.9%" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->sub_total,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td style="border: 1px solid #0c0c0c;padding-left:10px">VAT Object </td>
                                <td align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->dpp_lain_value,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td style="border: 1px solid #0c0c0c;padding-left:10px">VAT {{ $nilaiPPN }}% </td>
                                <td align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->ppn,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td style="border: 1px solid #0c0c0c;padding-left:10px">WHT 23</td>
                                <td align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ $val->pph23 ? '-'.number_format($val->pph23,2):'-' }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td style="border: 1px solid #0c0c0c;padding-left:10px">Total Bill</td>
                                <td align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->grand_total,2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="arial" valign="top" width="60%" colspan="3" style="border-right: 1px solid white;font-size: 11pt;">
                                Note:<br>
                                <span style="font-size: 11pt;">
                                Please transfer to our account <br>
                                Mohon transfer ke rekening kami	<br>
                                Bank BCA No. Rek : <b>6785577888</b><br>
                                Cabang KC Purwakarta<br>
                                a.n PT. Abimanyu Sekar Nusantara<br><br>
                                Attention/ perhatian<br></span>
                                <span style="font-size: 11pt;">
                                - Faktur ini berlaku sebagai Kwitansi.<br>
                                - Pembayaran dengan Cheque / Bilyet atau Wesel dianggap lunas setelah melalui Clearing
                                </span>
                            </td>
                            <td class="arial" valign="top" colspan="2" align="center" style="font-size: 11pt;">
                                <br>
                                Purwakarta, {{ $tanggalHariIni }} <br>
                                <br><br><br><br><br><br><br>
                                (&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Budi Mulyadi &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp)
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table>
                    <tr>
                        <td>
                            <span class="arial" style="font-size: 10pt;"><i>Lembar Asli untuk Penagihan kepada Customer</i></span><br>
                            <span class="arial" style="font-size: 10pt;"><i>Lembar Copy untuk Arsip</i></span>
                        </td>
                        <td align="right" valign="top" style="white-space:nowrap;width:110px;">Page 1 of 1</td>
                    </tr>
                </table>
            </div>
        @else
            {{-- Dua halaman: halaman 1 hanya nomor halaman, totals pindah ke halaman 2 --}}
            <div class="sub_div">
                <table>
                    <tr>
                        <td></td>
                        <td align="right" valign="top" style="white-space:nowrap;width:110px;">Page 1 of 2</td>
                    </tr>
                </table>
            </div>
        @endif
    </div>

    {{-- ══════════════════ HALAMAN 2 (hanya jika $duaHalaman=='yes') ══════════════════ --}}
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
                                    <td width="60%" align="center" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px" >
                                        <h2 style="margin:0px">INVOICE</h2>
                                    </td>
                                    <td style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                        <b style="font-size:17px" >{{ $recHdr->invoice_number }}</b>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="60%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px">
                                        <strong> Customer: </strong><br>
                                        {{ $customers->nama }} <br>
                                        {{ $customers->alamat_kirim_1 }} <br>
                                        @if(strlen($customers->alamat_kirim_1)<69)
                                        <br>
                                        @endif
                                        <strong>No. NPWP : </strong> {{ $customers->npwp }}</strong>
                                    </td>
                                    <td width="38%" valign="top" style="border: 1px solid #0c0c0c;padding-left:5px;padding-right:5px;font-size:12px">
                                        <strong style="font-size:15px">PO Number : </strong>{{ $listpo }}<br>
                                    </td>
                                </tr>
                            </table>
                            <div style="padding: 0 2px 0 2px"></div>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                    <td>
                    <div class="footer-space"></div>
                    </td>
                    </tr>
                </tfoot>
            </table>

            <div class="sub_div_tengah">
                <table id="tblContent" class="font-14" style="table-layout:fixed;">
                    <thead>
                        <tr style="height: 35px;">
                            <th width="4.5%">No</th>
                            <th width="51.5%">Description</th>
                            <th width="8.5%" align="center">Qty</th>
                            @if($printType=='1')
                            <th width="12%">Price</th>
                            <th width="15%">Total</th>
                            @else
                            <th width="12%">Service Price</th>
                            <th width="15%">Total Service</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details2 as $val )
                            <tr style="font-size: 11pt;height:23px">
                                <td align="center" scope="row">{{ ++$no }}</td>
                                <td align="left">{{ $val->article_desc }}</td>
                                <td align="center">{{ fmod($val->qty, 1) !== 0.0 ? number_format($val->qty,2) : number_format($val->qty) }}</td>
                                @if($printType=='1')
                                <td align="right">{{ number_format($val->price,2) }}</td>
                                <td align="right">{{ number_format(($val->qty*$val->price),2) }}</td>
                                @else
                                <td align="right">{{ number_format($val->price_service,2) }}</td>
                                <td align="right">{{ number_format(($val->qty*$val->price_service),2) }}</td>
                                @endif
                            </tr>
                        @endforeach

                        {{-- Halaman 2 SELALU menampilkan totals di bawahnya, jadi
                             kapasitasnya sama seperti mode satu-halaman (27 baris). --}}
                        <?php $totalBaris2 = 27; ?>
                        @for ($i=1; $i < $totalBaris2-(count($details2)); $i++)
                            <tr style="height:23px">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
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
                                <td colspan="3" rowspan="5" style="border-bottom: 1px solid black;">
                                    <table style="table-layout:fixed;">
                                        <tr>
                                            <td style="border-right: none;border-left: none;padding-right:0px" width="15%" valign="top"><b>Terbilang : </b></td>
                                            <td style="border-right: none;border-left: none;padding-left:0px"><i class="arial" style="font-size: 10pt;">{{ ucwords(strtolower($terbilang)) }}</i></td>
                                        </tr>
                                    </table>
                                </td>
                                <td width="10.6%" style="border: 1px solid #0c0c0c;padding-left:10px">Selling Price</td>
                                <td width="13.9%" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->sub_total,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td style="border: 1px solid #0c0c0c;padding-left:10px">VAT Object </td>
                                <td align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->dpp_lain_value,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td style="border: 1px solid #0c0c0c;padding-left:10px">VAT {{ $nilaiPPN }}% </td>
                                <td align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->ppn,2) }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td style="border: 1px solid #0c0c0c;padding-left:10px">WHT 23</td>
                                <td align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ $val->pph23 ? '-'.number_format($val->pph23,2):'-' }}</td>
                            </tr>
                            <tr style="height:25px">
                                <td style="border: 1px solid #0c0c0c;padding-left:10px">Total Bill</td>
                                <td align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->grand_total,2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="arial" valign="top" width="60%" colspan="3" style="border-right: 1px solid white;font-size: 11pt;">
                                Note:<br>
                                <span style="font-size: 11pt;">
                                Please transfer to our account <br>
                                Mohon transfer ke rekening kami	<br>
                                Bank BCA No. Rek : <b>6785577888</b><br>
                                Cabang KC Purwakarta<br>
                                a.n PT. Abimanyu Sekar Nusantara<br><br>
                                Attention/ perhatian<br></span>
                                <span style="font-size: 11pt;">
                                - Faktur ini berlaku sebagai Kwitansi.<br>
                                - Pembayaran dengan Cheque / Bilyet atau Wesel dianggap lunas setelah melalui Clearing
                                </span>
                            </td>
                            <td class="arial" valign="top" colspan="2" align="center" style="font-size: 11pt;">
                                <br>
                                Purwakarta, {{ $tanggalHariIni }} <br>
                                <br><br><br><br><br><br><br>
                                (&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp Budi Mulyadi &nbsp&nbsp&nbsp&nbsp&nbsp&nbsp)
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table>
                    <tr>
                        <td>
                            <span class="arial" style="font-size: 10pt;"><i>Lembar Asli untuk Penagihan kepada Customer</i></span><br>
                            <span class="arial" style="font-size: 10pt;"><i>Lembar Copy untuk Arsip</i></span>
                        </td>
                        <td align="right" valign="top" style="white-space:nowrap;width:110px;">Page 2 of 2</td>
                    </tr>
                </table>
            </div>
        </div>
    @endif

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
    </script>
</body>
</html>