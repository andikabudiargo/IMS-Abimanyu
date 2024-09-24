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
            /* box-sizing: border-box; */
            page-break-after: always;
        }

        /** Paper sizes **/
        body.A3           .sheet { width: 297mm; height: 419mm }
        body.A3.landscape .sheet { width: 420mm; height: 296mm }
        body.A4           .sheet { width: 210mm; height: 296mm }
        body.A4A5           .sheet { width: 210mm; height: 148mm }
        body.A4.landscape .sheet { width: 297mm; height: 209mm }
        body.A5           .sheet { width: 148mm; height: 209mm }
        body.A5.landscape .sheet { width: 210mm; height: 147mm }
        body.A42page      .sheet { width: 210mm; height: 592mm }
        body.A43page      .sheet { width: 210mm; height: 888mm }
        body.A44page      .sheet { width: 210mm; height: 1184mm }

        /** Padding area **/
        .sheet.padding-10mm { padding: 10mm }
        .sheet.padding-5mm { padding: 5mm }
        .sheet.padding-15mm { padding: 15mm }
        .sheet.padding-20mm { padding: 20mm }
        .sheet.padding-25mm { padding: 25mm }

        /** For screen preview **/
        @media screen {
            /* body { background: #e0e0e0 } */
            .sheet {
                background: white;
                box-shadow: 0 .5mm 2mm rgba(0,0,0,.3);
                margin: 5mm;
            }
        }

        /** Fix for Chrome issue #273306 **/
        @media print {
            body.A3.landscape { width: 420mm }
            body.A3, body.A4.landscape { width: 297mm }
            body.A4, body.A5.landscape { width: 210mm }
            body.A5                    { width: 148mm }

           
        }

        .putih{
            color:white;
        }

        .header, .header-space{
                height: 140px;
        }

        .footer, .footer-space {
                height: 170px;
        }
        
        .header {
            position: fixed;
            top: 0;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
        }

        :root {
            /*half black*/
            --line-color: rgba(0, 0, 0, 0.8);
        }

        @media print {
            header, footer {
                position: fixed;
                top: 0;
            }
            
            footer {
                position: fixed;
                bottom: 0;
            }

            @page :footer {
                display: none
            }
            @page :header {
                display: none
            }

            .tanpa-padding{
                padding:0px;
            }

            .putih{
                color:white;
            }

        }
        
        * {
            font-family: Calibri,Arial, Helvetica, sans-serif;
        }

        table{
            font-family: Calibri,Arial, Helvetica, sans-serif;
        }
        
        table {
            width: 100%;
        }

        #tblContent{
            border: thin solid var(--line-color);
            border-collapse: collapse;
        }

        #tblContent  th {
            border: thin solid var(--line-color);
        }

        #tblContent  td {
            padding : 3px 10px 3px 10px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        #tblContent tr:last-child{
            border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        .border-atas{
            border: thin solid var(--line-color);
            border-collapse: collapse;
        }

        .tableHeader td{
            padding-bottom: 0px;
            padding-top: 0px;
        }

        .font-12{
            /* font-size:12pt; */
            font-size: medium;
        }

        .font-14{
            /* font-size:14pt; */
            font-size: medium;
        }

        .font-13{
            font-size:11pt;
            /* font-size: medium; */
        }

        .font-8{
            font-size:8pt;
            /* font-size: medium; */
        }

        .font-9{
            font-size:9pt;            /* font-size: medium; */
        }

        .font-16{
            font-size:16pt;
            /* font-size: medium; */
        }

        .font-small{
            font-size: small;
        }

        .tanpa-padding{
            padding:0px;
        }

        .huruf-tebal{
            font-weight: bold;
        }

        @media print {
            .hide-print {
                display: none;
            }
        }

        .tblContent{
            /* border: thin solid var(--line-color); */
            border-collapse: collapse;
        }

        .tblContent  td {
            padding : 3px 10px 3px 10px;
            border-bottom: thin solid var(--line-color);
            border-left: none;
            border-right: none;
        }

        .tblContent tr:last-child{
            border-bottom: thin solid var(--line-color);
            border-left: none;
            border-right: none;
        }

        .bordered{
            border: thin solid var(--line-color);
            border-collapse: collapse;
        }

        .bordered  td {
            padding : 3px 10px 3px 10px;
            border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        .bordered  th {
            padding : 3px 10px 3px 10px;
            border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
            background-color: rgb(183, 178, 178);
        }

    </style>
</head>
{{-- <body class="{{ (count($details) < 7) $jumlahBaris < 18 ? "A4A5" : "A4" }}"> --}}
{{-- <body class="{{ $jumlahBaris < 18 ? "A4A5" : "A4" }}"> --}}
<body class="{{ $ukuranKertas }}">
    <div class="row hide-print" style="margin-left:20px;margin-top:20px">
        <div class="col-md-12">
            <button class="btn btn-primary" type="button" id="cmdPrint" name="cmdPrint">Print</button>
        </div>
    </div>
    <div class="sheet padding-10mm">
        <div class="content">
            <h4 class="card-title">Detail</h4>
            <table class="tblContent" >
                <tbody>
                    <tr><td width="25%">Nama Asset</td><td >: {{ $header->asset_desc }}</td></tr>
                    <tr><td >Nomor Asset</td><td >: {{ $header->asset_number }}</td></tr>
                    <tr><td >Nomor Invoice</td><td >: {{ $header->invoice_number }}</td></tr>
                    <tr><td >Harga Beli</td><td >: {{ number_format($header->buying_price) }}</td></tr>
                    <tr><td >Qty</td><td >: {{ $header->qty }}</td></tr>
                    <tr><td >Departement</td><td >: {{ $header->dept_name }}</td></tr>
                    <tr><td >Supplier</td><td >: {{ $header->supplier_name }}</td></tr>
                    <tr><td >Akun Asset Tetap</td><td >: {{ $header->akun_aset_tetap_name }}</td></tr>
                    <tr><td >Status</td><td >: {{ ucfirst($header->status_beli) }}</td></tr>
                </tbody>
            </table>
            <br>
            @if( $header->penyusutan == '1')
                <h4 class="card-title">Penyusutan</h4>
                <table  class="tblContent" >
                    <tbody>
                        <tr><td width="25%">Metode Penyusutan</td><td >: {{ $header->metode_penyusutan }}</td></tr>
                        <tr><td >Nilai Penyusutan (Thn)</td><td >: {{ $header->nilai_penyusutan }}%</td></tr>
                        <tr><td >Masa Manfaat</td><td >: {{ $header->masa_manfaat }} Tahun</td></tr>
                        <tr><td >Tanggal Awal Penyusutan</td><td >: {{ date("d F Y", strtotime($header->tanggal_awal_penyusutan)) }}</td></tr>
                        <tr><td >Tanggal Akhir Penyusutan</td><td >: {{ date("d F Y", strtotime($header->tanggal_akhir_penyusutan))  }}</td></tr>
                        <tr><td >Penyusutan</td><td >: {{ number_format($header->akumulai_penyusutan) }}</td></tr>
                        <tr><td >Akun Akumulasi Penyusutan</td><td >: {{ $header->akun_akumulasi_penyusutan_name }}</td></tr>
                    </tbody>
                </table>
            @endif
            @if( $header->penyusutan == '1')
                <h4 class="card-title">SimulasiPenyusutan</h4>
                <table class="bordered" id="details" width="100%">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nilai Asset</th>
                            <th>Penyusutan</th>
                            <th>Nilai Buku</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details as $val )
                            <tr>
                                <td width="20%">{{ date("d-m-Y", strtotime($val->tanggal_asset)) }}</td>
                                <td >{{ number_format($val->nilai_asset) }}</td>
                                <td >{{ number_format($val->penyusutan) }}</td>
                                <td >{{ number_format($val->nilai_buku) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
<script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
<script type="text/javascript">

    $("#cmdPrint").click(function(){ 
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