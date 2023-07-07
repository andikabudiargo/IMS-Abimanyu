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

        #tblContentBawah{
            border: thin solid var(--line-color);
            border-collapse: collapse;
        }

        #tblContentBawah  th {
            border: thin solid var(--line-color);
        }

        #tblContentBawah  td {
            padding : 3px 10px 3px 10px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }


    </style>
</head>
<body class="{{ (count($details)) < 7 ? "A4A5" : "A4" }}">
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
                                    {{-- <div class="huruf-tebal font-16" >SLIP PEMBAYARAN</div> --}}
                                    {{-- <div class="huruf-tebal font-14" style="text-align:center">{{ $apNumber }}</div> --}}                                    
                                    <table width="100%">
                                        <tr class="tanpa-padding">
                                            <td class="tanpa-padding font-16" width="70%"><b>SLIP PEMBAYARAN</b></td>
                                            <td class="tanpa-padding" width="30%"><img src="{{ asset('app-assets/images/logo/logo_po.png') }}" alt="logo" style="width: 70%;">  </td>
                                        </tr>
                                    </table>
                                    <table>
                                        <tr class="tanpa-padding">
                                            <td class="tanpa-padding font-14" width="15%">Receive AP</td>
                                            <td class="tanpa-padding font-14" width="50%">: {{ $apDate }}</td>
                                            <td class="tanpa-padding font-14" >Supplier</td>
                                            <td class="tanpa-padding font-14">: {{ $supplierName }}</td>
                                        </tr>
                                        <tr class="tanpa-padding">
                                            <td class="tanpa-padding font-14"></td>
                                            <td class="tanpa-padding font-14"></td>
                                            <td class="tanpa-padding font-14" >Due Date</td>
                                            <td class="tanpa-padding font-14">: {{ $top }}</td>
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
                        <table id="tblContent" class="font-small">
                            <thead>
                                <tr>
                                    <th align="center" width="5%">No</th>
                                    <th width="15%">No Invoice</th>
                                    <th width="40%">Keterangan</th>
                                    <th width="20%">No Akun</th>
                                    <th width="10%">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- @foreach ($details as $key=>$val ) --}}
                                    <tr >
                                        <td align="center">1</td>
                                        <td align="left">{{ $invNumber }}</td>
                                        <td align="left">{{ $accountName }}</td>
                                        <td align="left">{{ $accountBa }}</td>
                                        <td align="right">{{ number_format($basisAmount) }}</td>
                                    </tr>
                                {{-- @endforeach       --}}
                                                    
                                {{-- @if(count($details)>7) --}}
                                    <?php //$totalBaris = 16 ?>
                                {{-- @else --}}
                                    <?php $totalBaris = 7 ?>
                                {{-- @endif --}}

                                @for ($i=1;$i< $totalBaris-(count($details));$i++)
                                    <tr >
                                        <td align="right" class="putih" height="16"></td>
                                        <td align="left"></td>
                                        <td align="left"></td>
                                        <td align="right"></td>
                                        <td align="right"></td>
                                    </tr>
                                @endfor
                                <tr>
                                    {{-- <td  colspan="3" rowspan="3" class="border-atas" style="border-bottom: 1px solid black;"></td> --}}
                                    <td align="left" class="border-atas" rowspan="3" colspan="3"></td>
                                    <td  align="left" class="border-atas" >PPN</td>
                                    <td  align="right" class="border-atas" >{{ number_format($vat) }}</td>
                                </tr>
                                <tr>
                                    <td  align="left" class="border-atas" >Total</td>
                                    <td  align="right" class="border-atas" >{{ number_format($grandTotal) }}</td>
                                </tr>
                                <tr class="border-atas">
                                    
                                </tr>
                            </tbody>
                        </table>
                        <br>
                        
                        <table width="100%" id="tblContentBawah" border="1">
                            {{-- <tr><td colspan="5" height="3"></td></tr> --}}
                            <tr> 
                                <td align="center" width="20%">Disetujui<br></td>
                                <td align="center" width="20%">Diperiksa<br>(Mng Fin-Acc)</td>
                                <td align="center" width="20%">Diperiksa<br>(Finance Acc)</td>
                                <td align="center" width="20%">Diperiksa<br>(Purch)</td>
                                <td align="center" width="20%">Dibuat</td>
                            </tr>
                            <tr>
                                {{-- <td align="center" height="70">{{ $approval1 ? 'Approval 1':'' }}</td>
                                <td align="center">{{ $approval1 ? 'Approval 2':'' }}</td>
                                <td align="center">{{ $approval1 ? 'Approval 3':'' }}</td>
                                <td align="center">{{ $approval1 ? 'Approval 3':'' }}</td> --}}
                                <td align="center" height="70"></td>
                                <td align="center"></td>
                                <td align="center"></td>
                                <td align="center"></td>
                                <td align="center"></td>
                            </tr>
                            {{-- <tr>
                                <td align="center"></td>
                                <td align="center"  style="border-bottom: 1px solid black;">{{ $approval1 ? $approval1->name:'' }}</td>
                                <td align="center"></td>
                                <td align="center" style="border-bottom: 1px solid black;">  {{ $approval2 ? $approval2->name:'' }}  </td>
                                <td align="center"></td>
                                <td align="center" style="border-bottom: 1px solid black;">  {{ $approval3 ? $approval3->name:'' }}  </td>
                                <td align="center"></td>
                            </tr> --}}
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
<script>

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