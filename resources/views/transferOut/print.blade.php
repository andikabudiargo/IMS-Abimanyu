<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta charset="utf-8" />
    <title>Transfer Out</title>
    <style>
        /** Define the margins of your page **/
        @page {
            margin: 100px 25px;
        }

        header {
            position: fixed;
            top: -100px;
            left: 0px;
            right: 0px;
            height: 100px;
            /* font-size: 20px !important; */
            /** Extra personal styles **/
            /* background-color: #008B8B; */
            /* color: white; */
            /* text-align: center; */
            /* line-height: 35px; */
        }

        footer {
            position: fixed; 
            bottom: -250px; 
            left: 0px; 
            right: 0px;
            height: 200px; 
            /* font-size: 20px !important; */
            /** Extra personal styles **/
            /* background-color: #008B8B; */
            /* color: white; */
            /* text-align: center; */
            /* line-height: 35px; */
        }

        .pagenum:before {
            content: counter(page);
        }

        * {
            font-family: Verdana, Arial, sans-serif;
        }

        table{
            font-size: x-small;
        }
        
        tfoot tr td{
            font-size: medium;
        }

        .gray {
            background-color: lightgray;
            font-weight: bold;
        }

        table {
            width: 100%;
        }

        .detail th {
            height: 30px;
        }
        .detail td {
            height: 20px;
        }
        .detail > th, td {
            padding-left: 15px;
            padding-right: 15px;
            border-bottom: 1px solid #ddd;
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
                    <th valign="top" style="text-align:center"><h2>Transfer In</h2></th>
                    <th width="30%" ></th>
                </tr>
            </thead>
        </table>
        <table width="100%" border="0" >
            <tbody>
                <tr>
                    <td width="45%" valign="top" >
                        Number : {{ $trNumber }}<br>
                        Date   : {{ $trDate }}
                    </td>
                    <td width="10%"></td>
                    <td width="45%">
                        Rec. Status   : {{ $status }}<br>
                        Note   : {{ $keterangan }}
                    </td>
                </tr>
            </tbody>
        </table>
    </header>

    <footer>
        <table width="100%" border="0" >
            <tbody>
                <tr>
                    <td width="25%" valign="top" >
                        Created By :{{ $createdBy }} <br>    
                    </td>
                    <td width="25%">
                        Approved By : {{ $approved }}
                    </td>
                    <td width="10%" class='text-right'>
                        Page: <span class="pagenum"></span>
                    </td>
                </tr>
            </tbody>
        </table>
    </footer>
    <!-- Wrap the content of your PDF inside a main tag -->
    <main>
        <table>
            <thead style="background-color: lightgray;">
                <tr>
                    <th width="5%">No</th>
                    <th width="10%">Article</th>
                    <th width="45%">Description</th>
                    <th width="10%">Qty</th>
                    <th width="10%">Uom</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($details as $val )
                    <tr class="border-bottom">
                        <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                        <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                        <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                        <td class="border-bottom" align="right">{{ number_format($val->qty,4) }}</td>
                        <td class="border-bottom" align="right">{{ $val->uom }}</td>
                    </tr>
                @endforeach
            </tbody>
            {{-- <tfoot>
                @foreach ($totals as $val )
                    <tr class="border-bottom">
                        <td class="border-bottom" align="left" colspan="3">Total</td>
                        <td class="border-bottom" align="right" >{{ number_format($val->qty,4) }}</td>
                    </tr>
                @endforeach
            </tfoot> --}}
        </table>
    </main>
</body>
</html>
