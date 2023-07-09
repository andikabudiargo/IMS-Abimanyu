<!doctype html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
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

        table.utama, table.utama th {
            border: 1px solid black;
            border-collapse: collapse;
            border-spacing: -1px;
        }

        /* table.utama, table.utama td {
            border-collapse: collapse;
            border-right: 1px solid black;
            border-left: 1px solid black;
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
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 70%;"> 
            </td></td>
        </tr>
        <tr>
            <td colspan="2">
                Kp. Karang Mulya RT 014 RW 005 Cikopo Bungursari Kab. Purwakarta
            </td>
        </tr>
    </table>
    
    <table>
        <tr>
            <td>NPWP : 31.284.174.5-416.000</td>
        </tr>
        <tr>
             <td width="60%"style="border: 1px solid #0c0c0c;padding-left:10px;text-align: center;">
                <h2>INVOICE</h2>
            </td>
            <td style="border: 1px solid #0c0c0c;padding-left:10px">
                <b style="font-size:17px">{{ $recHdr->invoice_number }}</b>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td width="60%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                <br>
                <strong> Customer: </strong><br>
                {{ $customers->nama }} <br>
                {{ $customers->alamat_kirim_1 }} <br>
            </td>
            <td width="40%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                <br>
                <strong>PO Number : </strong>{{ $listpo }}<br>
                {{-- @foreach ($listpo as $val)
                    {{ $val->po_number }}
                @endforeach
                <br> --}}
                <strong>No FP : </strong>{{ $recHdr->invoice_number }}
            </td>
        </tr>
    </table>
    <table class="utama" style="table-layout:fixed;">
        <thead>
            <tr>
                <th width="5%">No</th>
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
                <tr >
                    <td style="border-right: 1px solid black;" align="center" scope="row" style="border-bottom: none;">{{ ++$no }}</td>
                    {{-- <td  align="left">{{ $val->article_alternative_code }}</td> --}}
                    <td  style="border-right: 1px solid black;" align="left">{{ $val->article_desc }}</td>
                    <td  style="border-right: 1px solid black;" align="right">{{ number_format($val->qty) }}</td>
                    <td  style="border-right: 1px solid black;" align="right">{{ number_format($val->price) }}</td>
                    <td  style="border-right: 1px solid black;" align="right">{{ number_format($val->price_service) }}</td>
                    <td  style="border-right: 1px solid black;" align="right">{{ number_format(($val->qty*$val->price)) }}</td>
                    <td  style="border-right: 1px solid black;" align="right">{{ number_format(($val->qty*$val->price_service)) }}</td>
                </tr>
            @endforeach
            
            <?php $totalBaris = 20 ?>

            @for ($i=1;$i< $totalBaris-(count($details));$i++)
                <tr >
                    <td style="border-right: 1px solid black;" class="putih" height="16"></td>
                    <td style="border-right: 1px solid black;" ></td>
                    <td style="border-right: 1px solid black;" ></td>
                    <td style="border-right: 1px solid black;" ></td>
                    <td style="border-right: 1px solid black;" ></td>
                    <td style="border-right: 1px solid black;" ></td>
                    <td style="border-right: 1px solid black;" ></td>
                </tr>
            @endfor
            <tr>
                <td  align="left"  style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                <td  align="left"  style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
                <td  align="right" style="border-bottom: 1px solid black;border-right: 1px solid black;"></td>
            </tr>
        </tbody>
        <tfoot>
            @foreach ($totals as $val )            
                <tr>
                    <td colspan="4" rowspan="4" style="border-bottom: 1px solid black;"><b>Terbilang : </b><i>{{ ucwords(strtolower($terbilang)) }}</i> </td>
                    <td colspan="" style="border: 1px solid #0c0c0c;padding-left:10px">DPP</td>
                    <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->sub_total) }}</td>
                </tr>
                <tr>
                    <td colspan="" style="border: 1px solid #0c0c0c;padding-left:10px">PPN {{ $nilaiPPN }}% </td>
                    <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->ppn) }}</td>
                </tr>
                <tr>
                    <td colspan="" style="border: 1px solid #0c0c0c;padding-left:10px">PPH 23</td>
                    <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ $val->pph23 ? '-'.number_format($val->pph23):'' }}</td>
                </tr>
                <tr>
                    <td colspan="" style="border: 1px solid #0c0c0c;padding-left:10px">Total</td>
                    <td colspan="2" align="right" style="border: 1px solid #0c0c0c;padding-left:10px">{{ number_format($val->grand_total) }}</td>
                </tr>
            @endforeach
            <tr>
                <td valign="top" width="60%" colspan="5">
                    <br>
                    Note:<br>
                    Please transfer to our account <br>	
                    Mohon transfer ke rekening kami	<br>
                    Bank BCA No. Rek : <b>6785577888</b><br>
                    Cabang KC Purwakarta<br>
                    a.n PT. Abimanyu Sekar Nusantara<br><br>
                    Attention/ perhatian<br>
                    - Faktur ini berlaku sebagai Kwitansi.<br>
                    - Pembayaran dengan Cheque / Bilyet atau Wesel dianggap lunas setelah melalui Clearing
    
                </td>
                <td valign="top" colspan="2" align="center" width="30%">
                    <br>
                    Purwakarta, {{ $tanggalHariIni }} <br>
                    <br><br><br><br><br>
                    ( Budi Mulyadi )<br> 
                </td>
            </tr>
        </tfoot>
    </table>
    <span style="font-size: x-small;"><i>Lembar Asli untuk Penagihan kepada Customer</i></span><br>
    <span style="font-size: x-small;"><i>Lembar Copy untuk Arsip</i></span>
    {{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>