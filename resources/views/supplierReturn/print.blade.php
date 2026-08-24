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

        body.A4           .sheet { width: 210mm; height: 296mm }
        body.letter        .sheet { width: 210mm; height: 279mm }
        body.letter2       .sheet { width: 210mm; height: 148mm }

        .sheet.padding-5mm { padding: 0mm 5mm 5mm 5mm }

        @media screen {
            body { background: #e0e0e0 }
            .sheet {
                background: white;
                box-shadow: 0 .5mm 2mm rgba(0,0,0,.3);
                margin: 5mm;
            }
        }

        .putih{ color:white; }

        .header, .header-space{ height: 190px; }
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
            .putih{ color:white; }
            .hide-print { display: none; }
        }

        * { font-family: Calibri,Arial, Helvetica, sans-serif; }
        table { width: 100%; font-family: Calibri,Arial, Helvetica, sans-serif; }

        #tblContent{ border: thin solid var(--line-color); border-collapse: collapse; }
        #tblContent th { border: thin solid var(--line-color); }
        #tblContent td { padding : 0px 10px 0px 10px; border-bottom: none; border-left: thin solid var(--line-color); border-right: thin solid var(--line-color); }
        #tblContent tr:last-child{ border-bottom: thin solid var(--line-color); border-left: thin solid var(--line-color); border-right: thin solid var(--line-color); }

        .tableHeader td{ padding-bottom: 0px; padding-top: 0px; }

        .font-12{ font-size: medium; }
        .font-14{ font-size: medium; }
        .font-13{ font-size:11pt; }
        .font-16{ font-size:16pt; }
        .font-small{ font-size: small; }
        .tanpa-padding{ padding:0px; }
        .huruf-tebal{ font-weight: bold; }

        .company-name { font-size: 12pt; font-weight: bold; text-align: center; }
        .company-address { font-size: 9pt; text-align: center; line-height: 1.3; }
        .doc-title { font-size: 18pt; font-weight: bold; text-align: center; padding: 8px 0; }
        .info-label { width: 90px; }
        .border-outer { border: thin solid var(--line-color); }
        .border-bottom-only { border-bottom: thin solid var(--line-color); }
    </style>
</head>
<body class="{{ (count($details)) < 5 ? "letter2" : "letter" }}">
    <div class="row hide-print" style="margin-left:20px;margin-top:20px">
        <div class="col-md-12">
            <button class="btn btn-primary" type="button" id="cmdPrint" name="cmdPrint">Print</button>
        </div>
    </div>
    <div class="sheet padding-5mm">
        <table>
            <thead>
                <tr>
                    <td>
                        <div class="header-space">
                            <br>
                            <table width="100%" class="border-outer" style="padding:8px 10px">
                                <tr>
                                    <td width="20%" valign="middle">
                                        <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 100%;">
                                    </td>
                                    <td width="80%" valign="middle">
                                        <div class="company-name">PT. ABIMANYU SEKAR NUSANTARA</div>
                                        <div class="company-address">
                                            Kp. Karang Mulya RT.014/005<br>
                                            Desa Cikopo, Bungursari Purwakarta
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" class="border-outer" style="border-top:0">
                                <tr>
                                    <td class="doc-title border-bottom-only">SURAT JALAN RETURN</td>
                                </tr>
                            </table>

                            <table width="100%" class="border-outer font-13" style="border-top:0;padding:8px 10px" cellpadding="2">
                                <tr>
                                    <td width="50%" valign="top">
                                        <table>
                                            <tr>
                                                <td class="info-label tanpa-padding">Kepada</td>
                                                <td class="tanpa-padding" width="15">:</td>
                                                <td class="tanpa-padding huruf-tebal">{{ $suppliers ? $suppliers->nama : '' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="info-label tanpa-padding" valign="top">Alamat</td>
                                                <td class="tanpa-padding" valign="top">:</td>
                                                <td class="tanpa-padding">{{ $suppliers ? ($suppliers->alamat ?? '-') : '-' }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="50%" valign="top">
                                        <table>
                                            <tr>
                                                <td class="info-label tanpa-padding">Nomor</td>
                                                <td class="tanpa-padding" width="15">:</td>
                                                <td class="tanpa-padding">{{ $tDnNumber }}</td>
                                            </tr>
                                            <tr>
                                                <td class="info-label tanpa-padding">Tanggal Kirim</td>
                                                <td class="tanpa-padding">:</td>
                                                <td class="tanpa-padding">{{ $tDnDate }}</td>
                                            </tr>
                                            <tr>
                                                <td class="info-label tanpa-padding">Location</td>
                                                <td class="tanpa-padding">:</td>
                                                <td class="tanpa-padding">{{ $locationNumber }}</td>
                                            </tr>
                                            <tr>
                                                <td class="info-label tanpa-padding">Status</td>
                                                <td class="tanpa-padding">:</td>
                                                <td class="tanpa-padding">{{ $status }}</td>
                                            </tr>
                                        </table>
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
                                    @foreach ($details as $val)
                                        <tr>
                                            <td align="center">
                                                <div style="height:35px;display: table-cell;vertical-align: middle;text-align: center;">{{ ++$no }}</div>
                                            </td>
                                            <td align="left">{{ $val->article_alternative_code }}</td>
                                            <td align="left">{{ $val->article_desc }}</td>
                                            <td align="right">{{ number_format($val->qty) }}</td>
                                            <td align="left">{{ $val->uom }}</td>
                                        </tr>
                                    @endforeach

                                    @if ((count($details)) > 4)
                                        <?php $totalBaris = 19 ?>
                                    @else
                                        <?php $totalBaris = 4 ?>
                                    @endif

                                    @for ($i = 1; $i <= $totalBaris - (count($details)); $i++)
                                        <tr>
                                            <td align="right" class="putih"><div style="height:35px;"></div></td>
                                            <td align="left"></td>
                                            <td align="left"></td>
                                            <td align="right"></td>
                                            <td align="left"></td>
                                        </tr>
                                    @endfor

                                    <tr style="border: thin solid var(--line-color)">
                                        <td colspan="5">Catatan: {{ $tDnNote }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            <table width="100%">
                                <tr><td colspan="5" height="3"></td></tr>
                                <tr>
                                    <td align="center">Created By</td>
                                    <td align="center">Checked By</td>
                                    <td align="center">Shipped By</td>
                                    <td align="center">Security</td>
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
                        <div class="footer-space"></div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
    <script>
        $("#cmdPrint").click(function () {
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