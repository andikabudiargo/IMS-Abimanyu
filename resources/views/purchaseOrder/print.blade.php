<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PO</title>
<style type="text/css">
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

    .detail th {
        height: 30px;
    }
    .detail td {
        height: 20px;
    }
    .detail th, td {
        padding-left: 15px;
        padding-right: 15px;
        /*border-bottom: 1px solid #ddd;*/
    }

    #watermark {
        background: url('{{ asset('app-assets/images/icons/lunas-stamp.png') }}') center;
        background-size: 10px 10px;
        background-repeat: no-repeat;
        opacity: 0.1;
    }
      
</style>
</head>
<body>
{{-- @if($status == "3")
    <div id ="watermark">
@endif --}}
    <div class="header">
        <table width="100%">
            <tr>
                <td align="left" style="width: 35%;padding:0px">
                    <h2>PURCHASE ORDER</h2>
                    <table width="100%" style="padding-left:0px" >
                        <tr>
                            <td style="width: 40%;padding:0px">Date</td><td>:{{ $poDate }}</td>
                        </tr>
                        <tr>
                            <td style="width: 40%;padding:0px">PO Number</td><td>:{{ $poNumber }}</td>
                        </tr>
                        <tr>
                            <td style="width: 40%;padding:0px">Term</td><td>:{{ $poTerm }}</td>
                        </tr>
                        <tr>
                            <td style="width: 40%;padding:0px">Delivery Date</td><td>:{{ $poDelDate }}</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 10%;"></td>
                <td align="center" style="width: 45%;" tyle="text-align:center;">
                    <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 60%;"> 
                </td>
            </tr>
        </table>
    </div>
    <table>
        <tr>
            <td width="45%" valign="top" style="border: 1px solid #0c0c0c;padding-left:10px">
                <strong> VENDOR </strong><br>
                @foreach ($suppliers as $val )
                    {{ $val->nama }} <br>
                    Fax:{{ $val->fax }}<br>
                    Phone:{{ $val->telepon }}<br>
                    Contact:{{ $val->nama_kontak }}<br>
                @endforeach
            </td>
            <td width="10%"></td>
            <td width="45%" style="border: 1px solid #0c0c0c;padding-left:10px">
                <strong>SHIP TO </strong><br>
                @foreach ($companies as $val)
                {{ $val }} <br>
                @endforeach
            </td>
        </tr>
    </table>
    <table class="detail" width="100%">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="5%">No</th>
            <th width="10%">Code</th>
            <th width="40%">Description</th>
            <th width="5%">Qty</th>
            <th width="5%">UOM</th>
            <th>Price</th>
            {{-- <th>PPN</th> --}}
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr style="border-bottom: 1px solid #ddd;">
                    <td scope="row" style="border-bottom: 1px solid #ddd;">{{ ++$no }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->article_alternative_code }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="left">{{ $val->article_desc }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->qty,2) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ $val->uom }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->price,2) }}</td>
                    {{-- <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format($val->ppn) }}</td> --}}
                    <td style="border-bottom: 1px solid #ddd;" align="right">{{ number_format(($val->qty*$val->price),2) }}</td>
                </tr>
            @endforeach
        </tbody>
        {{-- <tfoot>
            @foreach ($totals as $val )
                <tr style="border-bottom: 1px solid #ddd;">
                    <td style="border-bottom: 1px solid #ddd;" align="left" colspan="3"></td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" >{{ number_format($val->qty) }}</td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" ></td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" ></td>
                    <td style="border-bottom: 1px solid #ddd;" align="right" class="gray">{{ number_format($val->dpp)}}</td>
                </tr>
            @endforeach
        </tfoot> --}}
    </table>
    <table width="100%">
        <tbody>
            <tr>
                <td valign="top" style="border: 1px solid #0c0c0c;padding-left:10px;width: 65%;font-size: 10px;">
                Notes:{{ $keterangan }}<br><br>
                {{-- Notes:<br> --}}
                1. Surat jalan harus mencantumkan No. PO<br>
                2. Barang harus diterima di gudang kami paling lambat jam 15:30 WIB<br>
                3. Pembayaran sesuai dengan schedule yang telah ditentukan perusahaan<br>
                4. Pada saat penagihan harap lampirkan<br>
                   - 2 Rangkap surat jalan<br>
                   - 2 Rangkap tanda terima barang<br>
                   - 2 Rangkap Invoice<br>
                5. Pada saat penagihan harap lampirkan tanda terima Barang<br>
                6. Jatuh tempo invoice dihitung dari tanggal terima invoice <br>
                7. Penerimaan invoice dari senin s/d kamis, waktu penerimaan paling lambat pukul 15.00 WIB
                </td>
                <td valign="top" align="right" style="padding-right:0px">
                    <table width="100%">
                        <tbody>
                            <tr><td  width="60%">Subtotal</td><td style="padding:0px">:</td></td><td align="right">{{ number_format($totals[0]->gross,2) }}</td></tr>
                            <tr><td >Discount {{ $totals[0]->nilai_discount }}%</td><td style="padding:0px">:</td><td align="right">{{ number_format($totals[0]->discount,2) }}</td></tr>
                            <tr><td >DPP</td><td style="padding:0px">:</td></td><td align="right">{{ number_format($totals[0]->dpp,2) }}</td></tr>
                            <tr><td >PPN {{ $totals[0]->angka_ppn }}%</td><td style="padding:0px">:</td><td align="right">{{ number_format($totals[0]->ppn,2) }}</td></tr>
                            <tr><td >PPH23 {{ $totals[0]->angka_pph23 }}%</td><td style="padding:0px">:</td><td align="right">{{ number_format($totals[0]->pph23,2) }}</td></tr>
                            <tr><td >Total:</td><td style="padding:0px">:</td><td align="right">{{ number_format($totals[0]->netto,2) }}</td></tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            {{-- <tr><td rowspan='6' style="width: 65%;">{{ $keterangan }}</td></tr> --}}
        </tbody>
    </table>
    <table width="100%" border="0" cellspacing="20">
        <tr><td colspan="2" height="100"></td></tr>
        {{-- <tr><td colspan="2" height="100"></td></tr> --}}
        <tr>
            <td align="center" width="25%">Dibuat</td>
            <td align="center" width="25%">Diperiksa</td>
            <td align="center" width="25%">Mengetahui</td>
            <td align="center" width="25%">Menyetujui</td>
        </tr>
        <tr>
            <td align="center" height="20">{{ $approval1 ? 'Approval 1':'' }}</td>
            <td align="center">{{ $approval2 ? 'Approval 2':'' }}</td>
            <td align="center">{{ $approval3 ? 'Approval 3':'' }}</td>
            <td align="center">{{ $approval4 ? 'Approval 4':'' }}</td>
        </tr>
        <tr>
            <td align="center"  style="border-bottom: 1px solid black;">{{ $approval1 ? $approval1->name:'' }}</td>
            <td align="center" style="border-bottom: 1px solid black;">{{ $approval2 ? $approval2->name:'' }}  </td>
            <td align="center" style="border-bottom: 1px solid black;">{{ $approval3 ? $approval3->name:'' }}  </td>
            <td align="center" style="border-bottom: 1px solid black;">{{ $approval4 ? $approval4->name:'' }}  </td>
            {{-- <td align="center">( _____________ )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td>
            <td align="center">( _____________  )</td> --}}
        </tr>
    </table>
   
{{-- @if($status == "3")
</div>
@endif --}}
</body>
</html>