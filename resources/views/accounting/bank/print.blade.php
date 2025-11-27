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

            .hide-print {
                display: none;
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
            padding : 2px 5px 2px 5px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        #tblContent tr:last-child{
            border-bottom: thin solid var(--line-color);
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }


        #tblContent2{
            border: thin solid var(--line-color);
            border-collapse: collapse;
        }

        #tblContent2  th {
            border: thin solid var(--line-color);
        }

        #tblContent2  td {
            padding : 3px 10px 3px 10px;
            border-bottom: none;
            border-left: thin solid var(--line-color);
            border-right: thin solid var(--line-color);
        }

        #tblContent2 tr:last-child{
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

        .font-8 {
            font-size: 8pt;
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

        .sub_div {
            position: absolute;
            /* margin-right: 8mm; */
            bottom: 0px;
            background-color:white;
            width  : 787.7px;
            margin-left : 0.8mm;
        }

        .sub_div_2 {
            position: absolute;
            /* margin-right: 8mm; */
            bottom: 0px;
            background-color:white;
            width  : 787.7px;
            margin-left : 0.8mm;
            border-top: thin solid var(--line-color);
        }

        .sub_div_3 {
            position: absolute;
            /* margin-right: 8mm; */
            bottom: 0px;
            background-color:white;
            width  : 787.7px;
            margin-left : 0.8mm;
            /* border-top: thin solid var(--line-color); */
        }

    </style>
</head>
{{-- <body class="{{ (count($details)) < 7 ? "A4A5" : "A4" }}"> --}}
<body class="A4">
<div class="row hide-print" style="margin-left:20px;margin-top:20px">
    <div class="col-md-12">
        <button class="btn btn-primary" type="button" id="cmdPrint" name="cmdPrint">Print</button>
    </div>
</div>

@foreach($details as  $keyDetail =>$detail)
    <div class="sheet padding-5mm">
        
        <table>
            <thead>
                <tr>
                    <td>
                        <div class="header-space">
                            {{-- <br> --}}
                            <table width="100%">
                                <tr>
                                    <td style="vertical-align: bottom;">
                                        <div class="huruf-tebal font-16" style="text-align:center">BUKTI BANK MASUK</div>
                                        <div class="huruf-tebal font-14" style="text-align:center">{{ $header->voucher_number }}</div>
                                        <br>
                                        <table width="100%">
                                            <tr class="tanpa-padding">
                                                <td class="tanpa-padding font-14" width="10%">Tanggal</td>
                                                <td class="tanpa-padding font-14" width="35%">: {{ $header->voucher_date }}</td>
                                                <td style="text-align:right">Departemen</td><td>: {{ $costCenter }}</td>
                                            </tr>
                                            <tr class="tanpa-padding">
                                                <td class="tanpa-padding font-14">Dari</td>
                                                <td class="tanpa-padding font-14">: {{ $header->receive_name }}</td>
                                                <td></td><td></td>
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
                            <table id="tblContent" class="font-small" class="font-small" style="table-layout:fixed;">
                                <thead>
                                    <tr>
                                        <th width="10%">No Account</th>
                                        <th width="20%">Account Name</th>
                                        <th width="15%">Referensi</th>
                                        <th width="20%">Keterangan</th>
                                        <th width="13%">Debet</th>
                                        <th width="13%">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($detail as $val )
                                        <tr >
                                            <td align="left">{{ $val->account }}</td>
                                            <td align="left">{{ $val->account_name }}</td>
                                            <td align="left">{{ $val->reference }}</td>
                                            <td align="left">{{ $val->description }}</td>
                                            <td align="right">{{ number_format($val->debit,2) }}</td>
                                            <td align="right">{{ number_format($val->credit,2) }}</td>
                                        </tr>
                                    @endforeach      
                                                        
                                    {{-- @if(count($details)>7)
                                        <?php //$totalBaris = 16 ?>
                                    @else
                                        <?php //$totalBaris = 7 ?>
                                    @endif --}}
                                    
                                    <?php $totalBaris = 29 ?>

                                    @for ($i=1;$i<$totalBaris-(count($details));$i++)
                                        <tr >
                                            <td style="border-right: 1px solid black;" ><div style="height:25px;"></div></td>
                                            <td align="left"></td>
                                            <td align="right"></td>
                                            <td align="right"></td>
                                            <td align="right"></td>
                                        </tr>
                                    @endfor

                                </tbody>
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

        @if($totalPage==$page[$keyDetail])

            <div class="sub_div">
                <table id="tblContent2" class="font-small" style="table-layout:fixed;">
                    <tr>
                        <td  align="left" width="9.2%"class="border-atas" ></td>
                        <td  align="left" width="20.8%" class="border-atas" ></td>
                        <td  align="left" width="15%" class="border-atas" ></td>
                        <td  align="left" width="21%" class="border-atas" >Total</td>
                        <td  align="right" width="12.7%" class="border-atas" >{{ number_format($total->total_debit,2) }}</td>
                        <td  align="right" width="12.7%" class="border-atas" >{{ number_format($total->total_credit,2)}}</td>
                    </tr>
                    <tr class="border-atas">
                        <td  align="left" class="border-atas" colspan="6">Note: {{ $header->note }}</td>
                    </tr>
                </table>
                <br>
                <table width="100%">
                    <tr> 
                        <td align="center" width="20%">Dibuat oleh</td>
                        <td align="center" width="5%"></td>
                        <td align="center" width="20%">Diperiksa</td>
                        <td align="center" width="5%"></td>
                        <td align="center" width="20%">Mengetahui</td>
                        <td align="center" width="5%"></td>
                        <td align="center" width="20%">Menyetujui</td>
                        <td align="center" width="5%"></td>
                    </tr>
                    <tr>
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
                <br>
                <table>
                    <tr>
                        <td>
                        </td>
                        <td align="right" width="10%"></td>
                    </tr>
                </table>
                <br>
            </div>

            @if($totalPage>1)

                <div class="sub_div_3">
                    <table>
                        <tr>
                            <td>
                            </td>
                            <td align="right" width="10%"> Page {{ $page[$keyDetail] }} of {{ $totalPage }}</td>
                        </tr>
                    </table>
                    <br>
                </div>
                
            @endif
        @else
            <div class="sub_div_2">
                <table>
                    <tr>
                        <td>
                        </td>
                        <td align="right" width="10%"> Page {{ $page[$keyDetail] }} of {{ $totalPage }}</td>
                    </tr>
                </table>
                <br>
            </div>
        @endif
    </div>
@endforeach

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