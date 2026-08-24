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
                            <table width="100%" style="border: thin solid var(--line-color);padding-left:10px">
                                <tr>
                                    <td width="30%">
                                        <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 100%;">
                                    </td>
                                    <td width="20%"></td>
                                    <td width="50%" style="vertical-align: bottom;">
                                        <div class="huruf-tebal font-16" style="padding-right:10px">SURAT JALAN RETUR SUPPLIER</div>
                                        <br>
                                        <table>
                                            <tr class="tanpa-padding">
                                                <td class="tanpa-padding font-14" width="50"></td>
                                                <td class="tanpa-padding font-14" width="70">Nomor</td>
                                                <td class="tanpa-padding font-14">: {{ $tDnNumber }}</td>
                                            </tr>
                                            <tr class="tanpa-padding">
                                                <td class="tanpa-padding font-14"></td>
                                                <td class="tanpa-padding font-14">Status</td>
                                                <td class="tanpa-padding font-14">: {{ $status }}</td>
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
                                                <td width="35%" class="tanpa-padding">Tanggal</td>
                                                <td class="tanpa-padding">: {{ $tDnDate }}</td>
                                            </tr>
                                            <tr>
                                                <td class="tanpa-padding">Location</td>
                                                <td class="tanpa-padding">: {{ $locationNumber }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="50%" valign="top" style="border-left: thin solid var(--line-color);padding-left:5px" class="font-small">
                                        <strong>Kepada Yth.</strong><br>
                                        {{ $suppliers ? $suppliers->nama : '' }} <br>
                                        {{ $suppliers ? ($suppliers->alamat ?? '') : '' }} <br>
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
                                        <td colspan="5">Description: {{ $tDnNote }}</td>
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
                                    <td align="center">Received By (Supplier)</td>
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