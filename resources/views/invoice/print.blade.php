<!doctype html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>INV</title>
    <style type="text/css">

        html { 
            margin: 10px;
        }

        * {
            font-family: Verdana, Arial, sans-serif;
        }

        table{
            font-size: x-small;
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

        #watermark {
            background: url('{{ asset('assets/img/lunas-stamp.png') }}') center;
            background-size: 10px 10px;
            background-repeat: no-repeat;
            opacity: 0.1;
        }

        /* td {
            white-space: nowrap;
        } */
    </style>
    </head>
<body>
    {{-- @if($status == "B")
        <div id ="watermark">
    @endif --}}
    <table width="100%" border="0">
        <tr>
            <td width="30%" >
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
                <br>Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta
            </td>
            <td valign="top" style="text-align:center"></td>
            <td width="30%" ></td>
        </tr>
    </table>
    <br>
    {{-- <h6>Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta</h6> --}}
    NPWP : 31.284.174.5-416.000
    <table>
        <tr>
            <td width="60%"style="border: 1px solid #0c0c0c;padding-left:10px">
                <h2>INVOICE</h2>
            </td>
            <td style="border: 1px solid #0c0c0c;padding-left:10px">
                No:<br>{{ $recHdr->invoice_number }}
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td width="60%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                <strong> VENDOR </strong><br>
                
                    {{ $customers->nama }} <br>
                    {{ $customers->alamat_kirim_1 }} <br>
                
            </td>
            <td width="40%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                <strong>PO Number </strong><br>
                @foreach ($listpo as $val)
                    {{ $val->po_number }} ,
                @endforeach
            </td>
        </tr>
    </table>
    <table style="table-layout:fixed;">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="5%">No</th>
            {{-- <th width="10%">Code</th> --}}
            <th width="45%">Description</th>
            <th width="10%">Qty</th>
            <th width="10%">Material Price</th>
            <th width="10%">Service Price</th>
            <th width="10%">Total Material</th>
            <th width="10%">Total Service</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr class="border-bottom">
                    <td scope="row" class="border-bottom" align="right">{{ ++$no }}</td>
                    {{-- <td class="border-bottom" align="left">{{ $val->article_alternative_code }}</td> --}}
                    <td class="border-bottom" align="left">{{ $val->article_desc }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->qty) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->price) }}</td>
                    <td class="border-bottom" align="right">{{ number_format($val->price_service) }}</td>
                    <td class="border-bottom" align="right">{{ number_format(($val->qty*$val->price)) }}</td>
                    <td class="border-bottom" align="right">{{ number_format(($val->qty*$val->price_service)) }}</td>
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
                    <td class="border-bottom" align="right" >{{ number_format($val->total_material)}}</td>
                    <td class="border-bottom" align="right" >{{ number_format($val->total_service)}}</td>
                </tr>
            @endforeach
        </tfoot>
        <tr>
            <td colspan="7"> </td>
        </tr>
        <tr>
            <td colspan="5" style="border: 1px solid #0c0c0c;padding-left:10px">Jumlah harga jual/ Dasar Pengenaan Pajak</td>
            <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->sub_total) }}</td>
        </tr>
        <tr>
            <td colspan="5" style="border: 1px solid #0c0c0c;padding-left:10px">10% Pajak Pertambahan Nilai ( PPN )</td>
            <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->ppn) }}</td>
        </tr>
        <tr>
            <td colspan="5" style="border: 1px solid #0c0c0c;padding-left:10px">Potongan Pajak PPH 23</td>
            <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">-{{ number_format($val->pph23) }}</td>
        </tr>
        <tr>
            <td colspan="5" style="border: 1px solid #0c0c0c;padding-left:10px">Grand total</td>
            <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->grand_total) }}</td>
        </tr>
        <tr>
            {{-- <td colspan="7">Keterangan:<br> {{ $keterangan }}</td> --}}
        </tr>
    </table>
    <table>
        <tr>
            <td valign="top" width="60%">
                Please transfer to our account <br>	
                Mohon transfer ke rekening kami	<br>
	            Bank BCA No. Rek : 6785577888<br>
	            Cabang KC Purwakarta<br>
	            a.n PT. Abimanyu Sekar Nusantara<br><br>
                Attention/ perhatian<br>
                - Faktur ini berlaku sebagai Kwitansi.<br>
                - Pembayaran dengan Cheque / Bilyet atau Wesel dianggap lunas setelah melalui Clearing

            </td>
            <td valign="top" width="10%"></td>
            <td valign="top" width="30%">
                Purwakarta, {{ date("d M Y") }} <br>
                Budi Mulyadi <br><br><br>
                Direktur
            </td>

        </tr>
    </table>
    
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>