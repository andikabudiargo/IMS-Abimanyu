<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <style>
        /** Define the margins of your page **/
        @page {
            margin: 120px 25px;
        }

        header {
            position: fixed;
            top: -115px;
            left: 0px;
            right: 0px;
            height: 100px;
        }

        footer {
            position: fixed; 
            bottom: -100px; 
            left: 0px; 
            right: 0px;
            height: 20px; 
        }

        .pagenum:before {
            content: counter(page);
        }

        * {
            font-family: Verdana, Arial, sans-serif;
        }

        table{
            font-size: 10px;
        }
        
        tfoot tr td{
            font-size: x-small;
        }

        .gray {
            background-color: lightgray;
            font-weight: bold;
        }

        table {
            width: 100%;
        }


        #detail th, #detail  td {
            border:1px solid #000000;
            padding:5px;
        }

        table {
            border-collapse:collapse;
            table-layout:fixed
        }

        .footer{
            border-top:1px solid #000000;
            border-left:1px solid #000000;
            border-right:1px solid #000000;
            padding:5px;
        }

        .last-footer{
            border-bottom:1px solid #000000;
            padding:5px;
        }

        .text-center{
            text-align: center
        }

    </style>
</head>
<body>
    <!-- Define header and footer blocks before your content -->
    <header>
        <table width="100%" border="0">
            <thead>
                <tr>
                    <th width="30%" style="text-align:left">
                        <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
                    </th>
                    <th valign="top" style="text-align:center"><h3>BON PERSIAPAN CHEMICAL DAN BAHAN BAKU</h3></th>
                    <th width="30%" ></th>
                </tr>
            </thead>
        </table>
        <table width="100%" border="0" style="margin-top:5px">
            <tbody>
                <tr>
                    <td width="10%">Doc Number</td>
                    <td width="20%">: {{ $mixNumber }}</td>
                    <td width="45%"></td>
                    <td width="5%">Date</td>
                    <td width="20%">: {{ $mixDate }}</td>
                </tr>
                <tr>
                    <td width="10%">Wos Number</td>
                    <td width="20%">: {{ $wosNumber }}</td>
                    <td width="45%"></td>
                    <td width="5%">Shift</td>
                    <td width="20%" style="text-transform: capitalize;">: {{ $shift }}</td>
                </tr>
                <tr>
                    <td width="10%">Status</td>
                    <td width="20%">: {{ $status }}</td>
                    <td width="45%"></td>
                    <td width="5%">Page</td>
                    <td width="20%">: <span class="pagenum"></span></td>
                </tr>
            </tbody>
        </table>
    </header>
    <footer>
        {{-- <span class="pagenum"></span> --}}
    </footer>
    <!-- Wrap the content of your PDF inside a main tag -->
    <main>
        <table width="100%" style="table-layout: fixed;" id="detail">
            <thead style="background-color: lightgray;">
                <tr>
                    <th width="2%">No</th>
                    <th width="6%">Part No.</th>
                    <th width="20%">Nama Part</th>
                    <th width="5%">Stock</th>
                    <th width="5%">Consump.</th>
                    <th width="5%">Supply</th>
                    <th width="5%">Actual</th>
                    <th width="5%">Return</th>
                    <th width="5%">Sisa</th>
                    <th width="7%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($details as $val )
                    <tr class="border-bottom">
                        <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                        <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                        <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                        {{-- <td class="border-bottom" align="right">{{ number_format($val->article_qty*1,2)*1 }}</td> --}}
                        <td class="border-bottom" align="right">{{ number_format($val->article_qty*1,2) }}</td>
                        <td class="border-bottom" align="right">{{ number_format($val->qty*1,2)*1  }}</td>
                        {{-- <td class="border-bottom" align="right">{{ (fmod($val->qty, 1) !== 0.0) ? number_format($val->qty,4,",",".")  : number_format($val->qty) }}</td> --}}
                        <td class="border-bottom" align="right"></td>
                        <td class="border-bottom" align="right">{{ number_format($val->qty_actual*1,2)*1 }}</td>
                        {{-- <td class="border-bottom" align="right">{{ is_float($val->qty_actual) ? number_format($val->qty_actual,4,",",".") : number_format($val->qty_actual) }}</td> --}}
                        <td class="border-bottom" align="right"></td>
                        <td class="border-bottom" align="right"></td>
                        <td class="border-bottom" align="right"></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                {{-- @foreach ($totals as $val ) --}}
                    {{-- <tr class="border-bottom">
                        <td class="border-bottom" align="left" colspan="3">Total</td>
                        <td class="border-bottom" align="right" >{{ number_format($val->qty,4) }}</td>
                    </tr> --}}
                {{-- @endforeach --}}
            </tfoot>
        </table>

        <br>
        <br>

        <table width="100%" id="footer" >
            <tbody class="text-center">
                <tr>
                    <td width="40%"></td>
                    <td width="15%" class="footer">
                        Disiapkan
                    </td>
                    <td width="15%" class="footer">
                        Disetujui
                    </td>
                    <td width="15%" class="footer">
                        Disetujui
                    </td>
                    <td width="15%" class="footer last-footer">
                        Diterima
                    </td>
                </tr>
                <tr>
                    <td style='height:50px'></td>
                    <td class="footer"></td>
                    <td class="footer"></td>
                    <td class="footer"></td>
                    <td class="footer last-footer"></td>
                </tr>
                <tr>
                    <td></td>
                    <td  class="footer last-footer">
                        Logistic
                    </td>
                    <td  class="footer last-footer">
                        Spv. Logistic
                    </td>
                    <td class="footer last-footer">
                        Spv. Produksi
                    </td>
                    <td  class="footer last-footer">
                        Produksi
                    </td>
                </tr>
            </tbody>
        </table>
    </main>
</body>
</html>
