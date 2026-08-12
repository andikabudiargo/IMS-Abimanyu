<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style type="text/css">
        @page { margin: 0; size: A4; }
        * { box-sizing: border-box; font-family: Calibri, Arial, Helvetica, sans-serif; }
        body { margin: 0; }
        .arial { font-family: Arial, Helvetica, sans-serif; }

        :root { --line-color: #0c0c0c; }

        /* ── Halaman ─────────────────────────────────────────────── */
        /* Tiap .sheet = satu halaman A4 penuh, disusun sbg flex-column
           supaya area tabel bisa flex:1 (mengisi sisa) dan blok bawah
           menempel di dasar tanpa position:absolute. */
        .sheet {
            width: 210mm;
            min-height: 296mm;
            padding: 5mm 8mm;
            margin: 0;
            position: relative;
            display: flex;
            flex-direction: column;
            page-break-after: always;
            page-break-inside: avoid;
        }
        .sheet:last-child { page-break-after: auto; }

        /* Cegah tabel & baris pecah antar halaman saat print */
        #tblContent, #tblContent2 { page-break-inside: auto; }
        #tblContent tr, #tblContent2 tr { page-break-inside: avoid; }
        #tblContent thead { display: table-header-group; }

        /* Preview di layar */
        @media screen {
            body { background: #e0e0e0; }
            .sheet {
                background: #fff;
                box-shadow: 0 .5mm 2mm rgba(0,0,0,.3);
                margin: 5mm;
            }
        }

        @media print {
            .sheet { height: 297mm; overflow: hidden; }
        }

        @media print {
            .hide-print { display: none; }
            .sheet { box-shadow: none; margin: 0; }
        }

        /* ── Kop / header (diulang tiap halaman) ─────────────────── */
        .kop img { width: 20%; }
        .kop p { margin: 6px 0 8px; padding: 0 2px; font-size: 11pt; }

        .info-inv { width: 100%; border-collapse: collapse; }
        .info-inv td { border: 1px solid var(--line-color); padding: 3px 5px; }

        /* ── Tabel item ──────────────────────────────────────────── */
        /* Area tabel tumbuh mengisi sisa tinggi halaman (flex:1). */
        .area-tabel { flex: 1 1 auto; display: flex; }

        #tblContent {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid var(--line-color);  /* garis penutup bawah */
            table-layout: fixed;
        }
        #tblContent th { border: 1px solid var(--line-color); padding: 4px; }
        #tblContent td {
            padding: 3px 10px;
            border-left: 1px solid var(--line-color);
            border-right: 1px solid var(--line-color);
        }
        /* Baris terakhir juga digaris bawah, jaga-jaga */
        #tblContent tbody tr:last-child td { border-bottom: 2px solid var(--line-color); }

        /* ── Blok totals (hanya halaman terakhir) ────────────────── */
        .blok-bawah { flex: 0 0 auto; margin-top: 4px; }

        #tblTotal { width: 100%; border-collapse: collapse; border: 1px solid var(--line-color); table-layout: fixed; }
        #tblTotal td { padding: 2px 10px; border: 1px solid var(--line-color); }

        /* Tabel totals — lebar penuh menyamai tabel item */
        #tblContent2 {
            width: 100%;
            border: thin solid var(--line-color);
            border-collapse: collapse;
            table-layout: fixed;
        }
        #tblContent2 th { border: thin solid var(--line-color); }
        #tblContent2 td {
            padding: 0px 10px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }
        /* Kolom label & nilai totals: nowrap, lebar cukup agar teks tidak terpotong */
        #tblContent2 td.lbl-total { white-space: nowrap; width: 22%; padding-left: 6px; padding-right: 4px; }
        #tblContent2 td.val-total { white-space: nowrap; width: 16%; padding-left: 6px; padding-right: 6px; }
        #tblContent2 tr:last-child {
            border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        .catatan { font-size: 11pt; }
        .footnote { font-size: 10pt; }
    </style>
</head>
<body>
    <div class="row hide-print" style="margin-left:20px;margin-top:20px">
        <div class="col-md-12">
            <button class="btn btn-primary" type="button" id="cmdPrint" name="cmdPrint">Print</button>
        </div>
    </div>

    @php
        // Kapasitas baris per halaman (terverifikasi via render A4).
        $perHalamanPenuh    = $capacityFull ?? 33;  // halaman TANPA totals
        $perHalamanTerakhir = $capacityLast ?? 18;  // halaman DENGAN totals

        $items = collect($details ?? [])->merge($details2 ?? [])->values();
        $total = $items->count();

        // Bagi item ke halaman. Halaman terakhir (yg memuat blok totals)
        // dibatasi $perHalamanTerakhir agar totals + Note + Page tidak terpotong.
        $distribusi = [];
        if ($total <= $perHalamanTerakhir) {
            $distribusi[] = $total;
        } else {
            $idx = 0;
            while (($total - $idx) > $perHalamanTerakhir) {
                $remaining = $total - $idx;
                if ($remaining <= $perHalamanPenuh) {
                    // sisa muat 1 halaman penuh, tapi masih perlu halaman totals →
                    // pindahkan (sisa - kapasitas terakhir) ke halaman ini
                    $take = $remaining - $perHalamanTerakhir;
                } else {
                    $take = $perHalamanPenuh;
                }
                $distribusi[] = $take;
                $idx += $take;
            }
            $distribusi[] = $total - $idx;
        }

        // Ubah distribusi (jumlah per halaman) jadi array koleksi item.
        $halaman = [];
        $mulai = 0;
        foreach ($distribusi as $jml) {
            $halaman[] = $items->slice($mulai, $jml)->values();
            $mulai += $jml;
        }
        if (empty($halaman)) { $halaman[] = collect(); }

        $jmlHalaman = count($halaman);
        $nomor = 0;
    @endphp

    @foreach ($halaman as $h => $baris)
        @php $isLast = ($h === $jmlHalaman - 1); @endphp
        <div class="sheet">

            {{-- ===== KOP + INFO INVOICE (tiap halaman) ===== --}}
            <div class="kop" style="flex:0 0 auto;">
                <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo">
                <p>Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta<br>NPWP : 31.284.174.5-416.000</p>

                <table class="info-inv">
                    <tr>
                        <td width="60%" align="center"><h2 style="margin:0">INVOICE</h2></td>
                        <td><b style="font-size:17px">{{ $recHdr->invoice_number }}</b></td>
                    </tr>
                    <tr>
                        <td width="60%" valign="top">
                            <strong>Customer:</strong><br>
                            {{ $customers->nama }}<br>
                            {{ $customers->alamat_kirim_1 }}<br>
                            @if(strlen($customers->alamat_kirim_1) < 69)<br>@endif
                            <strong>No. NPWP :</strong> {{ $customers->npwp }}
                        </td>
                        <td width="38%" valign="top" style="font-size:12px">
                            <strong style="font-size:15px">PO Number : </strong>{{ $listpo }}
                        </td>
                    </tr>
                </table>
            </div>

            {{-- ===== TABEL ITEM (flex:1, mengisi sisa) ===== --}}
            <div class="area-tabel" style="margin-top:6px;">
                <table id="tblContent">
                    <thead>
                        <tr style="height:35px;">
                            <th width="4.5%">No</th>
                            <th width="51.5%">Description</th>
                            <th width="8.5%">Qty</th>
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
                        @foreach ($baris as $val)
                            <tr style="font-size:11pt;">
                                <td align="center">{{ ++$nomor }}</td>
                                <td align="left">{{ $val->article_desc }}</td>
                                <td align="center">{{ fmod($val->qty,1) !== 0.0 ? number_format($val->qty,2) : number_format($val->qty) }}</td>
                                @if($printType=='1')
                                    <td align="right">{{ number_format($val->price,2) }}</td>
                                    <td align="right">{{ number_format($val->qty*$val->price,2) }}</td>
                                @else
                                    <td align="right">{{ number_format($val->price_service,2) }}</td>
                                    <td align="right">{{ number_format($val->qty*$val->price_service,2) }}</td>
                                @endif
                            </tr>
                        @endforeach

                        @php
                            // Baris kosong pengisi supaya tabel penuh sampai bawah (opsi A).
                            $kapasitas = $isLast ? $perHalamanTerakhir : $perHalamanPenuh;
                            $kosong = max(0, $kapasitas - $baris->count());
                        @endphp
                        @for ($i = 0; $i < $kosong; $i++)
                            <tr style="height:23px;">
                                <td></td><td></td><td></td><td></td><td></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            {{-- ===== BLOK BAWAH ===== --}}
            @if($isLast)
                {{-- Totals + Note + tanda tangan: hanya halaman terakhir (struktur asli) --}}
                <div class="blok-bawah">
                    <table id="tblContent2">
                        <tbody>
                            @foreach ($totals as $val )
                                <tr style="height:25px">
                                    <td colspan="3" rowspan="5" style="border-bottom: 1px solid black;">
                                        <table>
                                            <tr>
                                                <td style="border-right: none;border-left: none;padding-right:0px;white-space:nowrap;" valign="top"><b>Terbilang : </b></td>
                                                <td style="border-right: none;border-left: none;padding-left:0px"><i class="arial" style="font-size: 10pt;">{{ ucwords(strtolower($terbilang)) }}</i></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="lbl-total" style="border: 1px solid #0c0c0c;padding-left:10px">Selling Price</td>
                                    <td class="val-total" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->sub_total,2) }}</td>
                                </tr>
                                <tr style="height:25px">
                                    <td class="lbl-total" style="border: 1px solid #0c0c0c;padding-left:10px">VAT Object </td>
                                    <td class="val-total" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->dpp_lain_value,2) }}</td>
                                </tr>
                                <tr style="height:25px">
                                    <td class="lbl-total" style="border: 1px solid #0c0c0c;padding-left:10px">VAT {{ $nilaiPPN }}% </td>
                                    <td class="val-total" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->ppn,2) }}</td>
                                </tr>
                                <tr style="height:25px">
                                    <td class="lbl-total" style="border: 1px solid #0c0c0c;padding-left:10px">WHT 23</td>
                                    <td class="val-total" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ $val->pph23 ? '-'.number_format($val->pph23,2):'-' }}</td>
                                </tr>
                                <tr style="height:25px">
                                    <td class="lbl-total" style="border: 1px solid #0c0c0c;padding-left:10px">Total Bill</td>
                                    <td class="val-total" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->grand_total,2) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class = "arial" valign="top" width="60%" colspan="3" style="border-right: 1px solid white;font-size: 11pt;white-space:nowrap;">
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
                    <table style="width:100%;">
                        <tr>
                            <td>
                                <span class = "arial" style="font-size: 10pt;"><i>Lembar Asli untuk Penagihan kepada Customer</i></span><br>
                                <span class = "arial" style="font-size: 10pt;"><i>Lembar Copy untuk Arsip</i></span>
                            </td>
                            <td align="right" valign="top" style="white-space:nowrap;width:140px;">Page {{ $h+1 }} of {{ $jmlHalaman }}</td>
                        </tr>
                    </table>
                </div>
            @else
                {{-- Halaman non-terakhir: hanya label halaman --}}
                <div class="blok-bawah">
                    <table style="width:100%;">
                        <tr>
                            <td></td>
                            <td align="right" width="10%">Page {{ $h+1 }} of {{ $jmlHalaman }}</td>
                        </tr>
                    </table>
                </div>
            @endif

        </div>
    @endforeach

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