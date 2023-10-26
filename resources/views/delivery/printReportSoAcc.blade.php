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
        bottom: 3%; 
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

    #watermark {
        background: url('{{asset('assets/img/lunas-stamp.png')}}') center;
        background-size: 10px 10px;
        background-repeat: no-repeat;
        opacity: 0.1;
    }

    

   
</style>
</head>
<body>
    <header>
        {{-- <p class='pagenum'></p> --}}
        <table width="100%" class="oki">
            <tbody>
                <tr>
                    <td colspan="3" align='right' class="font-10">Halaman: <span class="pagenum"></span></td>
                </tr>
                <tr>
                    <td colspan="3" align='center' class="font-10"> <strong>SO REPORT</strong></td>
                </tr>
                <tr>
                    <td valign="" width="3%" class="font-10 header-padding">No Order</td>
                    <td valign="" width="30%" class="font-10 header-padding">: {{ $soNumber }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td valign="" width="3%" class="font-10 header-padding">No PO</td>
                    <td valign="" width="30%" class="font-10 header-padding">: {{ $poNumber }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td valign="" width="3%" class="font-10 header-padding">Customer</td>
                    <td valign="" colspan ="2" width="30%" class="font-10 header-padding">: {{ $customer }}</td>
                </tr>
            </tbody>
        </table>
    </header>
    <main>
        <table width="100%" class="font-8">
            <tbody>
                {!! $barisDetail !!}                
            </tbody>
        </table>
    </main>
    {{-- @if( $jumlahBaris > 32 )
        <div class="breakNow"></div>
    @endif --}}
</body>
</html>