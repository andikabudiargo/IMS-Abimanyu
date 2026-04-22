<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <meta charset="utf-8" />
    <title>Transfer Out</title>
    <style>
        /** Define the margins of your page **/
        @page {
            margin: 115px 25px;
        }

        header {
            position: fixed;
            top: -115px;
            left: 0px;
            right: 0px;
            height: 120px;
            margin-bottom: 15px; /* Sesuaikan dengan tinggi footer */
        }

        footer {
            position: fixed; 
            bottom: -250px; 
            left: 0px; 
            right: 0px;
            height: 200px; 
        }

        /* main {
            margin-top: 15px;
            margin-bottom: 15px;
        } */

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
                    <th valign="top" style="text-align:center"><h2>Transfer Out</h2></th>
                    <th width="30%" ></th>
                </tr>
            </thead>
        </table>
        <table width="100%" border="0" >
            <tbody>
                <tr>
                    <td width="45%" valign="top" >
                        Number : {{ $trNumber }}<br>
                        Date   : {{ $trDate }}<br>
                        Location From  : {{ $locationFrom }}
                    </td>
                    <td width="10%"></td>
                    <td width="45%">
                        Trans. Status   : {{ $status }}<br>
                        Note   : {{ $keterangan }} <br>
                        {{-- Location To  : {{ $locationTo }} --}}
                    </td>
                </tr>
            </tbody>
        </table>
    </header>

    {{-- <footer>
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
    </footer> --}}
    <!-- Wrap the content of your PDF inside a main tag -->
    <main>
        <table>
            <thead style="background-color: lightgray;">
                <tr>
                    <th width="5%">No</th>
                    <th width="10%">Article</th>
                    {{-- <th width="35%">Description</th> --}}
                    <th>Description</th>
                    <th width="10%">Qty</th>
                    {{-- <th width="20%">Location To</th> --}}
                    <th width="10%">Uom</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($details as $val )
                    <tr class="border-bottom">
                        <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                        <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td>
                        <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                        <td class="border-bottom" align="right">{{ $val->qty*1 }}</td>
                        {{-- <td class="border-bottom" align="left">{{ $val->location_name }}</td> --}}
                        <td class="border-bottom" align="left">{{ $val->uom }}</td>
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
        <br><br>
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
    </main>
</body>
</html>
