<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SO - {{ $soNumber }}</title>
    <style type="text/css">

        html { 
            margin: 10px;
        }

        * {
            font-family: Verdana, Arial, sans-serif;
        }

        table{
            /* font-size: x-small; */
            font-size:10pt;
        }
        
        tfoot tr td{
            /*font-weight: bold;*/
            /* font-size: medium; */
        }
        
        .gray {
            background-color: lightgray;
            font-weight: bold;
        }

        table {
            width: 100%;
        }

        th {
            height: 30px;
        }
        td {
            height: 20px;
        }
        th, td {
            padding-left: 5px;
            padding-right: 5px;
            /*border-bottom: 1px solid #ddd;*/
        }

        .border-bottom{
            border-bottom: 1px solid #ddd;
        }

        .font-9{
            font-size:9pt;
            /* font-size: medium; */
        }

        .font-8{
            font-size:7pt;
        }

        .breakNow {
            page-break-inside:avoid;  
            page-break-after:always;    
        }

        .pagenum:before {
            content: counter(page);
        }

        #page-number:after {
            counter-increment: page_number;
            content: "Page " counter(page_number);
        }

        table tr.page-break{
            page-break-after:always
        } 

    </style>
</head>
<body>
    
    <header>
        {{-- <p class='pagenum'></p> --}}
        <table width="100%" border="0">
            <tr>
                <td width="30%" >
                    <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
                </td>
                <td valign="top" style="text-align:center"><h2>SALES ORDER</h2></td>
                <td width="30%" ></td>
            </tr>
        </table>
        <table width="100%" border="0" >
            <tr>
                <td width="70%" valign="top" >
                    Order Number : {{ $soNumber }}<br>
                    PO Number    : {{ $soPoNumber }}<br>
                    Customer     : {{ $customers->nama }}
                </td>
                {{-- <td width="25%"></td> --}}
                <td width="30%">
                    Tanggal  : {{ $soDate }}<br>
                    Salesman : {{ $soSalesman }}<br>
                    Currency : {{ $soCurrency }}
                </td>
            </tr>
        </table>
    </header>
    <main>
        <table style="table-layout:fixed" class="font-9">
            <thead style="background-color: lightgray;">
            <tr>
                <th width="5%">No</th>
                {{-- <th width="10%">Code</th> --}}
                <th width="40%">Description</th>
                <th width="10%">Qty</th>
                <th width="10%">Material Price</th>
                <th width="10%">Service Price</th>
                <th width="13%">Total Material</th>
                <th width="13%">Total Service</th>
                <th width="13%">Grand Total</th>
            </tr>
            </thead>
            <tbody>
                @foreach ($details as $val )
                    <tr class="border-bottom">
                        <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                        {{-- <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td> --}}
                        <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                        <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                        <td class="border-bottom" align="right">{{ number_format($val->price,2) }}</td>
                        <td class="border-bottom" align="right">{{ number_format($val->price_service,2) }}</td>
                        <td class="border-bottom" align="right">{{ number_format(($val->qty*$val->price),2) }}</td>
                        <td class="border-bottom" align="right">{{ number_format(($val->qty*$val->price_service),2) }}</td>
                        <td class="border-bottom" align="right">{{ number_format(($val->qty*$val->price)+($val->qty*$val->price_service),2) }}</td>
                    </tr>
                    
                @endforeach                                    
            </tbody>
            
            <tfoot>
                @foreach ($totals as $val )
                    <tr class="border-bottom">
                        <td class="border-bottom" align="left" colspan="2">Total</td>
                        <td class="border-bottom" align="right" >{{ number_format($val->qty) }}</td>
                        <td class="border-bottom" align="right" ></td>
                        <td class="border-bottom" align="right" ></td>
                        <td class="border-bottom" align="right" >{{ number_format($val->total_material,2) }}</td>
                        <td class="border-bottom" align="right" >{{ number_format($val->total_service,2) }}</td>
                        <td class="border-bottom" align="right" >{{ number_format(($val->total_service+$val->total_material),2) }}</td>
                    </tr>
                @endforeach
                
            </tfoot>

            {{-- @if($no % 20 == 0 )
                @for ($i = 0; $i <= 11; $i++)
                    <tr class="border-bottom">
                        <td colspan="9" ></td>
                    </tr>
                @endfor
                <div class="breakNow"></div>
            @endif --}}
    
            {{-- <tr>
                <td colspan="7"> </td>
            </tr>
            <tr>
                <td colspan="3" rowspan="4" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                        <br>
                        Alamat Kirim:{{ $customers->alamat_kirim_1 }}<br>
                        Note:{{ $keterangan }}<br>
                </td>
                <td></td>
                <td></td>
                <td></td>
                <td>Sub Total</td>
                <td align="right">{{ number_format($val->sub_total,2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td>PPN</td>
                <td align="right">{{ number_format($val->ppn,2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td>PPH23</td>
                <td align="right">{{ $val->pph23?'-':'' }}{{ number_format($val->pph23,2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td>Grand total</td>
                <td align="right">{{ number_format($val->grand_total,2) }}</td>
            </tr>
            <tr>
            </tr> --}}
        </table>
        {{-- <br><br> --}}
        {{-- <table width="100%">
            <tr> 
                <td align="center" width="10%"></td>
                <td align="center" width="20%">Dibuat oleh</td>
                <td align="center" width="10%"></td>
                <td align="center" width="20%">Diperiksa</td>
                <td align="center" width="10%"></td>
                <td align="center" width="20%">Mengetahui</td>
                <td align="center" width="10%"></td>
                <td align="center" width="20%">Menyetujui</td>
                <td align="center" width="10%"></td>
            </tr>
            <tr>
                <td align="center"></td>
                <td align="center" height="25">{{ $approval1 ? 'Approval 1':'' }}</td>
                <td align="center"></td>
                <td align="center">{{ $approval2 ? 'Approval 2':'' }}</td>
                <td align="center"></td>
                <td align="center">{{ $approval3 ? 'Approval 3':'' }}</td>
                <td align="center"></td>
                <td align="center">{{ $approval4 ? 'Approval 4':'' }}</td>
                <td align="center"></td>
            </tr>
            <tr>
                <td align="center"></td>
                <td align="center"  style="border-bottom: 1px solid black;">{{ $approval1 ? $approval1->name:'' }}</td>
                <td align="center"></td>
                <td align="center" style="border-bottom: 1px solid black;">  {{ $approval2 ? $approval2->name:'' }}  </td>
                <td align="center"></td>
                <td align="center" style="border-bottom: 1px solid black;">  {{ $approval3 ? $approval3->name:'' }}  </td>
                <td align="center"></td>
                <td align="center" style="border-bottom: 1px solid black;">  {{ $approval4 ? $approval4->name:'' }}  </td>
                <td align="center"></td>
            </tr>
        </table> --}}
    </main>

    @if($no % 26 == 0 )
        <div class="breakNow"></div>
    @endif
    <footer>
        <table>
            <tr>
                <td colspan="7"> </td>
            </tr>
            <tr>
                <td colspan="3" rowspan="4" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                        <br>
                        {{-- Syarat Bayar : {{ $customers->syarat_bayar }}<br>
                        Waktu Kirim : {{ $customers->syarat_kirim }}<br> --}}
                        Alamat Kirim:{{ $customers->alamat_kirim_1 }}<br>
                        Note:{{ $keterangan }}<br>
                </td>
                <td></td>
                <td></td>
                <td></td>
                <td>Sub Total</td>
                <td align="right">{{ number_format($val->sub_total,2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td>PPN</td>
                <td align="right">{{ number_format($val->ppn,2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td>PPH23</td>
                <td align="right">{{ $val->pph23?'-':'' }}{{ number_format($val->pph23,2) }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td>Grand total</td>
                <td align="right">{{ number_format($val->grand_total,2) }}</td>
            </tr>
            <tr>
                {{-- <td colspan="7">Keterangan:<br> {{ $keterangan }}</td> --}}
            </tr>
        </table>
        <br><br>
        <table width="100%">
            {{-- <tr><td colspan="5" height="3"></td></tr> --}}
            <tr> 
                <td align="center" width="10%"></td>
                <td align="center" width="20%">Dibuat oleh</td>
                <td align="center" width="10%"></td>
                <td align="center" width="20%">Diperiksa</td>
                <td align="center" width="10%"></td>
                <td align="center" width="20%">Mengetahui</td>
                <td align="center" width="10%"></td>
                <td align="center" width="20%">Menyetujui</td>
                <td align="center" width="10%"></td>
            </tr>
            <tr>
                <td align="center"></td>
                <td align="center" height="25">{{ $approval1 ? 'Approval 1':'' }}</td>
                <td align="center"></td>
                <td align="center">{{ $approval2 ? 'Approval 2':'' }}</td>
                <td align="center"></td>
                <td align="center">{{ $approval3 ? 'Approval 3':'' }}</td>
                <td align="center"></td>
                <td align="center">{{ $approval4 ? 'Approval 4':'' }}</td>
                <td align="center"></td>
            </tr>
            <tr>
                <td align="center"></td>
                <td align="center"  style="border-bottom: 1px solid black;">{{ $approval1 ? $approval1->name:'' }}</td>
                <td align="center"></td>
                <td align="center" style="border-bottom: 1px solid black;">  {{ $approval2 ? $approval2->name:'' }}  </td>
                <td align="center"></td>
                <td align="center" style="border-bottom: 1px solid black;">  {{ $approval3 ? $approval3->name:'' }}  </td>
                <td align="center"></td>
                <td align="center" style="border-bottom: 1px solid black;">  {{ $approval4 ? $approval4->name:'' }}  </td>
                <td align="center"></td>
            </tr>
        </table>
    </footer>
</body>
</html>