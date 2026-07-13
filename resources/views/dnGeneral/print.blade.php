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

        body.letter  .sheet { width: 210mm; height: 279mm }
        body.letter2 .sheet { width: 210mm; height: 148mm }

        .sheet.padding-5mm { padding: 0mm 5mm 5mm 5mm }

        @media screen {
            body { background: #e0e0e0 }
            .sheet {
                background: white;
                box-shadow: 0 .5mm 2mm rgba(0,0,0,.3);
                margin: 5mm;
            }
        }

        @media print {
            body.letter  { width: 210mm }
            body.letter2 { width: 210mm }

            @page :footer { display: none }
            @page :header { display: none }

            .hide-print { display: none; }
            .tanpa-padding { padding: 0px; }
            .putih { color: white; }
        }

        * { font-family: Calibri, Arial, Helvetica, sans-serif; }
        table { width: 100%; font-family: Calibri, Arial, Helvetica, sans-serif; }

        :root { --line-color: rgba(0,0,0); }

        #tblContent { border: thin solid var(--line-color); border-collapse: collapse; }
        #tblContent th { border: thin solid var(--line-color); }
        #tblContent td {
            padding: 0px 10px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }
        #tblContent tr:last-child {
            border-bottom: thin solid var(--line-color);
        }

        .header-space { height: 232px; }
        .footer-space  { height: 170px; }

        .tanpa-padding { padding: 0px; }
        .huruf-tebal   { font-weight: bold; }
        .putih         { color: white; }

        .font-10  { font-size: 10pt; }
        .font-11  { font-size: 11pt; }
        .font-14  { font-size: medium; }
        .font-20  { font-size: 20pt; }
        .font-small { font-size: small; }
    </style>
</head>
<body class="{{ count($details) < 5 ? 'letter2' : 'letter' }}">

    <div class="hide-print" style="margin-left:20px; margin-top:20px">
        <button class="btn btn-primary" type="button" id="cmdPrint">Print</button>
    </div>

    <div class="sheet padding-5mm">
        <table>
            <thead>
                <tr>
                    <td>
                        <div class="header-space">
                            <br>
                            {{-- Logo & perusahaan --}}
                            <table width="100%" style="border: thin solid var(--line-color); padding-left:10px">
                                <tr>
                                    <td width="20%">
                                        <img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width:100%">
                                    </td>
                                    <td width="5%"></td>
                                    <td width="50%" align="center" class="font-small">
                                        PT. ABIMANYU SEKAR NUSANTARA<br>
                                        Kp. Karang Mulya RT.014/005<br>
                                        Desa Cikopo, Bungursari Purwakarta
                                    </td>
                                    <td width="5%"></td>
                                    <td width="30%"></td>
                                </tr>
                            </table>

                            {{-- Info header DN General --}}
                            <table style="border-left: thin solid var(--line-color); border-right: thin solid var(--line-color); padding-left:10px" class="font-10 tanpa-padding">
                                <tr>
                                    <td colspan="4" align="center" class="font-20 huruf-tebal">
                                        SURAT JALAN UMUM
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" valign="top">
                                        <table>
                                            <tr>
                                                <td width="20%" valign="top" class="tanpa-padding font-14">Kepada</td>
                                                <td class="tanpa-padding font-14">: {{ $customer ? $customer->nama : '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td width="20%" valign="top" class="tanpa-padding font-14">
                                                    <div style="height:48px; display:table-cell; vertical-align:top;">Alamat</div>
                                                </td>
                                                <td class="tanpa-padding font-14">: {{ $customer ? $customer->alamat_kirim_1 : '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td width="20%" valign="top" class="tanpa-padding font-14">No. Polisi</td>
                                                <td class="tanpa-padding font-14">: </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="50%" valign="top">
                                        <table>
                                            <tr>
                                                <td width="30%" valign="top" class="tanpa-padding font-14">Nomor</td>
                                                <td class="tanpa-padding font-14">: {{ $dnHdr->tdn_number }}</td>
                                            </tr>
                                            <tr>
                                                <td width="30%" valign="top" class="tanpa-padding font-14">Tanggal Kirim</td>
                                                <td class="tanpa-padding font-14">: {{ $dnHdr->delivery_date }}</td>
                                            </tr>
                                            <tr>
                                                <td width="30%" valign="top" class="tanpa-padding font-14">Perihal</td>
                                                <td class="tanpa-padding font-14">: {{ $dnHdr->perihal }}</td>
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
                            <table id="tblContent" class="font-11">
                                <thead>
                                    <tr>
                                        <td width="5%"  align="center">No</td>
                                        <td width="15%" align="center">Code</td>
                                        <td width="55%" align="center">Description</td>
                                        <td width="10%" align="center">Qty</td>
                                        <td width="10%" align="center">UOM</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($details as $val)
                                        <tr>
                                            <td align="center">
                                                <div style="height:25px; display:table-cell; vertical-align:middle; text-align:center;">
                                                    {{ ++$no }}
                                                </div>
                                            </td>
                                            <td align="left">{{ $val->article_alternative_code }}</td>
                                            <td align="left">{{ $val->article_desc }}</td>
                                            <td align="right">{{ number_format($val->qty) }}</td>
                                            <td align="left">{{ $val->uom }}</td>
                                        </tr>
                                    @endforeach

                                    @php $totalBaris = count($details) > 5 ? 20 : 5; @endphp

                                    @for ($i = 1; $i <= $totalBaris - count($details); $i++)
                                        <tr>
                                            <td class="putih"><div style="height:25px;"></div></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @endfor

                                    <tr style="border: thin solid var(--line-color)">
                                        <td colspan="6">Catatan: {{ $dnHdr->note }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            {{-- Tanda tangan --}}
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
                                    <td align="center">_____________</td>
                                    <td align="center">_____________</td>
                                    <td align="center">_____________</td>
                                    <td align="center">_____________</td>
                                    <td align="center">_____________</td>
                                </tr>
                                <tr>
                                    <td align="left" style="padding-left:20px">Date:</td>
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
                    <td><div class="footer-space"></div></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
    <script>
        document.getElementById('cmdPrint').addEventListener('click', function () {
            window.print();
            window.onafterprint = function () { window.close(); };
            window.onfocus = function () { setTimeout(function () { window.close(); }, 200); };
        });
    </script>
</body>
</html>