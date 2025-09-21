<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style type="text/css">
    @page { margin: 110px 10px 10px 10px; }
    body { margin: 10px; }

    header { 
        position: fixed; 
        top: -80px; 
        left: 10px; 
        right: 10px; 
        height: 50px; 
    }
    
    footer {
        position: fixed; 
        bottom: 1%; 
        left: 10px; 
        right: 10px;
        height: 180px; 
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


    * {
        font-family: Verdana, Arial, sans-serif;
    }

    table{
        /* font-size: x-small; */
        font-size: 10pt;
    }
    
    tfoot tr td{
        /*font-weight: bold;*/
        font-size: medium;
    }
    .gray {
        background-color: lightgray;
        font-weight: bold;
    }

   
    th {
        height: 20px;
    }
    td {
        height: 12px;
    }

    th, td {
        padding-left: 15px;
        padding-right: 15px;
        /*border-bottom: 1px solid #ddd;*/
    }

    table.oki td {
        border: none;
    }

    table.oki {
        border: none;
    }

    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
    }

    .font-10 {
        font-size: 10pt;
    }

    .font-20 {
        font-size: 20px;
    }

    .font-9 {
        font-size: 9pt;
    }

    .font-8 {
        font-size: 8pt;
    }

    .header-padding{
        padding : 0 2px 0 2px;
    }

    .detail-padding-bawah{
        padding : 0 5px 0 5px;
        border:none;
    }

    .h-tengah{
        text-align:center;
    }

    .no-wrap{
        white-space: nowrap;
    }

    .huruf-tebal{
        font-weight: bold;
    }

    .tanpa-padding{
        padding:0px;
    }
    
    .font-small{
        font-size: small;
    }

    /* #tblContent{
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
    } */

    /* #tblContent tr:last-child{
        border-bottom: thin solid var(--line-color);
        border-left: thin solid var(--line-color);
        border-right: thin solid var(--line-color);
    } */

    .isi-data{
        border-bottom-color:white;
        font-size: 8pt;
        padding : 3px 10px 3px 10px;
    }

    .isi-data-judul{
        font-size: 8pt;
        padding : 0px 0px 0px 0px;
    }

    
    .isi-data-bawah{
        border-bottom-color:black;
        font-size: 8pt;
        padding : 3px 10px 3px 10px;
    }

    .border-atas{
        border: thin solid var(--line-color);
        border-collapse: collapse;
    }
    
  
</style>
</head>
<body>
    <header>
        <table width="100%" class="oki">
            <tbody>
                <tr>
                    <td style="vertical-align: bottom;padding:0px">
                        <div class="huruf-tebal font-16" style="text-align:center">BUKTI BANK KELUAR</div>
                        <div class="huruf-tebal font-14" style="text-align:center">{{ $header->voucher_number }}</div>
                        <br>
                        <table width="100%" class="oki" >
                            <tr class="tanpa-padding">
                                <td class="tanpa-padding font-14" width="10%">Tanggal</td>
                                <td class="tanpa-padding font-14" width="43%">: {{ $header->voucher_date }}</td>
                                <td class="tanpa-padding font-14" width="10%">Departemen</td><td class="font-8">: {{ $costCenter }}</td>
                            </tr>
                            <tr class="tanpa-padding">
                                <td class="tanpa-padding font-14" width="10%">Kepada</td>
                                <td class="tanpa-padding font-8" width="43%">: {{ $header->supplier_name }}</td>
                                <td class="tanpa-padding font-14" width="10%">Halaman</td><td>: <span class="pagenum"></span></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </header>
    <main>
        <table width="100%"> 
            <thead style="font-size: 9pt;">
                <tr>
                    <th class="isi-data-judul" width="10%">No Account</th>
                    <th class="isi-data-judul" width="15%">Account Name</th>
                    <th class="isi-data-judul" width="15%">Referensi</th>
                    <th class="isi-data-judul" width="">Keterangan</th>
                    <th class="isi-data-judul" width="13%">Debet</th>
                    <th class="isi-data-judul" width="13%">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($details as $val )
                    <tr >
                        <td align="left" class="isi-data">{{ $val->account }}</td>
                        <td align="left" class="isi-data">{{ $val->account_name }}</td>
                        <td align="left" class="isi-data">{{ $val->reference }}</td>
                        <td align="left" class="isi-data">{{ $val->description }}</td>
                        <td align="right" class="isi-data">{{ number_format($val->debit,2) }}</td>
                        <td align="right" class="isi-data">{{ number_format($val->credit,2) }}</td>
                    </tr>
                @endforeach      
                                    
                {{-- @if(count($details)>7)
                    <?php //$totalBaris = 16 ?>
                @else
                    <?php //$totalBaris = 7 ?>
                @endif --}}

                <?php $totalBaris = 20 ?>

                @for ($i=1;$i< $totalBaris-(count($details));$i++)
                    <tr >
                        {{-- <td align="right" class="putih" height="16"></td> --}}
                        <td style="border-right: 1px solid black;" class="isi-data"><div style="height:25px;"></div></td>
                        <td align="left" class="isi-data"></td>
                        <td align="left" class="isi-data"></td>
                        <td align="right" class="isi-data"></td>
                        <td align="right" class="isi-data"></td>
                        <td align="right" class="isi-data"></td>
                    </tr>
                @endfor
                <tr >
                    <td class="isi-data-bawah" style="border-right: 1px solid black;" class="isi-data"></td>
                    <td class="isi-data-bawah" align="left" class="isi-data"></td>
                    <td class="isi-data-bawah" align="left" class="isi-data"></td>
                    <td class="isi-data-bawah" align="right" class="isi-data"></td>
                    <td class="isi-data-bawah" align="right" class="isi-data"></td>
                    <td class="isi-data-bawah" align="right" class="isi-data"></td>
                </tr>
                <tr>
                    <td  align="left"  ></td>
                    <td  align="left"  ></td>
                    <td  align="left"  ></td>
                    <td  align="left"  >Total</td>
                    <td  align="right"  >{{ number_format($total->total_debit,2) }}</td>
                    <td  align="right"  >{{ number_format($total->total_credit,2)}}</td>
                </tr>
                <tr>
                    <td  align="left" colspan="6">Note: {{ $header->note }}</td>
                </tr>                                
            </tbody>
        </table>
        <br>
        <table width="100%">
            <tr> 
                <td align="center" style="border-color: white;" width="20%">Dibuat oleh</td>
                <td align="center" style="border-color: white;" width="5%"></td>
                <td align="center" style="border-color: white;" width="20%">Diperiksa</td>
                <td align="center" style="border-color: white;" width="5%"></td>
                <td align="center" style="border-color: white;" width="20%">Mengetahui</td>
                <td align="center" style="border-color: white;" width="5%"></td>
                <td align="center" style="border-color: white;" width="20%">Menyetujui</td>
                <td align="center" style="border-color: white;" width="5%"></td>
            </tr>
            <tr>
                <td align="center" style="border-color: white;height='25'">{{ $approval1 ? 'Approval 1':'' }}</td>
                <td align="center" style="border-color: white;"><br><br></td>
                <td align="center" style="border-color: white;">{{ $approval2 ? 'Approval 2':'' }}</td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-color: white;">{{ $approval3 ? 'Approval 3':'' }}</td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-color: white;">{{ $approval4 ? 'Approval 4':'' }}</td>
                <td align="center" style="border-color: white;"></td>
            </tr>
            <tr>
                <td align="center" style="border-bottom: 1px solid black;border-left: white;border-right: white;">{{ $approval1 ? $approval1->name:'' }}</td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-bottom: 1px solid black;border-left: white;border-right: white;">  {{ $approval2 ? $approval2->name:'' }}  </td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-bottom: 1px solid black;border-left: white;border-right: white;">  {{ $approval3 ? $approval3->name:'' }}  </td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-bottom: 1px solid black;;border-left: white;border-right: white;">  {{ $approval4 ? $approval4->name:'' }}  </td>
                <td align="center" style="border-color: white;"></td>
            </tr>
        </table>
    </main>
    <footer>
        {{-- <table width="100%">
            <tr> 
                <td align="center" style="border-color: white;" width="20%">Dibuat oleh</td>
                <td align="center" style="border-color: white;" width="5%"></td>
                <td align="center" style="border-color: white;" width="20%">Diperiksa</td>
                <td align="center" style="border-color: white;" width="5%"></td>
                <td align="center" style="border-color: white;" width="20%">Mengetahui</td>
                <td align="center" style="border-color: white;" width="5%"></td>
                <td align="center" style="border-color: white;" width="20%">Menyetujui</td>
                <td align="center" style="border-color: white;" width="5%"></td>
            </tr>
            <tr>
                <td align="center" style="border-color: white;height='25'"><br><br><br>{{ $approval1 ? 'Approval 1':'' }}</td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-color: white;">{{ $approval2 ? 'Approval 2':'' }}</td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-color: white;">{{ $approval3 ? 'Approval 3':'' }}</td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-color: white;">{{ $approval4 ? 'Approval 4':'' }}</td>
                <td align="center" style="border-color: white;"></td>
            </tr>
            <tr>
                <td align="center"  style="border-bottom: 1px solid black;border-left: white;border-right: white;">{{ $approval1 ? $approval1->name:'' }}</td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-bottom: 1px solid black;border-left: white;border-right: white;">  {{ $approval2 ? $approval2->name:'' }}  </td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-bottom: 1px solid black;border-left: white;border-right: white;">  {{ $approval3 ? $approval3->name:'' }}  </td>
                <td align="center" style="border-color: white;"></td>
                <td align="center" style="border-bottom: 1px solid black;;border-left: white;border-right: white;">  {{ $approval4 ? $approval4->name:'' }}  </td>
                <td align="center" style="border-color: white;"></td>
            </tr>
        </table> --}}
    </footer>

<script src="{{ asset('app-assets/vendors/js/vendors.min.js') }}"></script>
<script>
</script>
</body>
</html>