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
        body.A43pageLs    .sheet { width: 420mm; height: 630mm }
        body.A42pageLs    .sheet { width: 420mm; height: 600mm }

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
                height: 100px;
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
    <div class="sheet padding-5mm">
        <table>
            <thead>
                <tr>
                    <td>
                        <div class="header-space">
                            <table width="100%">
                                <tr>
                                    <td style="vertical-align: bottom;">
                                        <div class="huruf-tebal font-16" style="text-align:center">TRIAL BALANCE</div>
                                        <div class="huruf-tebal font-16" style="text-align:center">PT ABIMANYU SEKAR NUSANTARA</div>
                                        <div class="huruf-tebal font-14" style="text-align:center">PERIODE {{ $tanggal }}</div>
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
                            <table class="table table-condensed table-striped" border="1" style="border-collapse: collapse;">
                                <thead>
                                  <tr>
                                    <th rowspan="2" class="text-center" style="vertical-align: middle" width="30%">Akun</th>
                                    <th colspan="2" class="text-center">Saldo Awal</th>
                                    <th colspan="2" class="text-center">Pergerakan</th>
                                    <th colspan="2" class="text-center">Saldo Akhir</th>
                                  </tr>
                                  <tr>
                                    <th class="text-center">Debit</th>
                                    <th class="text-center">Credit</th>
                                    <th class="text-center">Debit</th>
                                    <th class="text-center">Credit</th>
                                    <th class="text-center">Debit</th>
                                    <th class="text-center">Credit</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  @foreach($groups as $keyGroup => $group)
                                      <tr class="parent" id ="row{{$keyGroup}}">
                                        <td colspan="7" class="judul-row{{$keyGroup}} doraemon">
                                          {{ $group ->group_name}}
                                        </td>
                                      </tr>
                                      <tr>
                                        @foreach($details as $keyDetail => $detail)
                                          @if($detail->group_data== $group->group_data)
                                            <tr class="child-row{{$keyGroup}}" >
                                              <td width="40%">{{ $detail->sub_group_name }} ({{ $detail->account }})</td>
                                              <td width="10%" class="text-left">Rp.{{ number_format($detail->opening_balance_debit) }} </td>
                                              <td width="10%" class="text-left">Rp.{{ number_format($detail->opening_balance_credit) }} </td>
                                              <td width="10%" class="text-left">Rp.{{ number_format($detail->pergerakan_debit) }} </td>
                                              <td width="10%" class="text-left">Rp.{{ number_format($detail->pergerakan_credit) }} </td>
                                              <td width="10%" class="text-left">Rp.{{ number_format($detail->saldo_akhir_debit) }} </td>
                                              <td width="10%" class="text-left">Rp.{{ number_format($detail->saldo_akhir_credit) }} </td>
                                            </tr>
                                          @endif
                                        @endforeach
                                  @endforeach
                                  <tr>
                                    <td width="40%"> Grand Total</td>
                                    <td width="10%" class="text-left">Rp.{{ number_format($total[0]->opening_balance_debit) }} </td>
                                    <td width="10%" class="text-left">Rp.{{ number_format($total[0]->opening_balance_credit) }} </td>
                                    <td width="10%" class="text-left">Rp.{{ number_format($total[0]->pergerakan_debit) }} </td>
                                    <td width="10%" class="text-left">Rp.{{ number_format($total[0]->pergerakan_credit) }} </td>
                                    <td width="10%" class="text-left">Rp.{{ number_format($total[0]->saldo_akhir_debit) }} </td>
                                    <td width="10%" class="text-left">Rp.{{ number_format($total[0]->saldo_akhir_credit) }} </td>
                                  </tr>
                                </tbody>
                                <tfoot>
                                </tfoot>
                            </table>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                <td>
                <div class="footer-space">
                </div>
                </td>
                </tr>
            </tfoot>
        </table>
    </div>
<script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
<script type="text/javascript">

    // $("#cmdPrint").click(function(){ 
        window.print();
        window.onafterprint = function () {
            window.close();
        }
        window.onfocus = function () { 
            setTimeout(function () { 
                window.close(); 
            }, 200); 
        }
    // });

</script>
</body>
</html>