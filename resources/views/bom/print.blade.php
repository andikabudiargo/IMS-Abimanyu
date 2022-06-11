<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style type="text/css">

    @page { margin: 10px; }
    body { margin: 10px; }

    * {
        font-family: Verdana, Arial, sans-serif;
    }
    table{
        font-size: x-small;
    }
    
    tfoot tr td{
        /*font-weight: bold;*/
        font-size: medium;
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
        padding-left: 15px;
        padding-right: 15px;
        /*border-bottom: 1px solid #ddd;*/
    }

    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
    }

    .font-10 {
        font-size: 9px;
    }

    .font-8 {
        font-size: 8px;
    }

    .header-padding{
        padding : 0 2px 0 2px;
    }

    .detail-padding{
        padding : 0 5px 0 5px;
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

    footer {
        position: fixed; 
        bottom: 10px; 
        left: 0px; 
        right: 0px;
        height: 100px; 
    }
</style>

</head>
<body>
{{-- @if($status == "B")
    <div id ="watermark">
@endif --}}
    <table width="100%" border="0">
        <tr>
            <td width="6%" rowspan="4" class="no-wrap h-tengah">
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" width="200%" height="200%" /> 
            </td>
            <td valign="middle" colspan="4" rowspan="2" class="header-padding h-tengah" ><h2>BILL OF MATERIALS</h2></td>
            <td valign="" class="font-10 header-padding" >No Dokumen</td>
            <td valign="" class="font-10 header-padding" >: ENG-02.08-FM</td>
        </tr>
        <tr>            
            <td valign="" class="font-10 header-padding" >Tgl Berlaku</td>
            <td valign="" class="font-10 header-padding" >: 25 Nov 2021</td>
        </tr>
        <tr>
            <td valign="" width="3%" class="font-10 header-padding">Part Name</td>
            <td valign="" width="10%" class="font-10 header-padding">{{ $bomHdr->article_desc }}</td>
            <td valign="" width="3%" class="font-10 header-padding">Model</td>
            <td valign="" width="10%" class="font-10 header-padding"></td>
            <td valign="" width="4%" class="font-10 header-padding">No Revisi</td>
            <td valign="" width="5%" class="font-10 header-padding"></td>
        </tr>
        <tr>
            <td valign="" class="font-10 header-padding">Part No</td>
            <td valign="" class="font-10 header-padding">{{ $bomHdr->article_alternative_code }}</td>
            <td valign="" class="font-10 header-padding">Customer</td>
            <td valign="" class="font-10 header-padding">{{ $bomHdr->nama }}</td>
            <td valign="" class="font-10 header-padding">Halaman</td>
            <td valign="" class="font-10 header-padding"></td>
        </tr>        
    </table>
    
    <table width="100%">
        <thead style="background-color: lightgray;">
        <tr>
            <th width="5%" >No</th>
            <th width="40%" >Material</th>
            <th width="10%" >Brand</th>
            <th width="5%" >Consumption</th>
            <th width="5%" >Unit</th>
            <th>Kode Barang</th>
        </tr>
        </thead>
        <tbody>
            @foreach ($details as $val )
                <tr >
                    <td class="detail-padding" scope="row" >{{ ++$no }}</td>
                    <td class="detail-padding"  align="left">{{ $val->article_desc }}</td>
                    <td class="detail-padding"  align="left">{{ $val->article_alternative_code }}</td>
                    <td class="detail-padding"  align="right">{{ $val->qty }}</td>
                    <td class="detail-padding"  align="left">{{ $val->uom }}</td>
                    <td class="detail-padding"  align="left">{{ $val->article_alternative_code }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer>
        <table width="100%" border="0">
            <tr>
                <td align="center">Approve1</td>
                <td align="center">Approve2</td>
                <td align="center">Approve3</td>
                <td align="center">Approve4</td>
                <td align="center">Approve5</td>
                <td align="center">Approve6</td>
            </tr>
            <tr>
                <td align="center" rowspan="2"></td>
                <td align="center" rowspan="2"></td>
                <td align="center" rowspan="2"></td>
                <td align="center" rowspan="2"></td>
                <td align="center" rowspan="2"></td>
                <td align="center" rowspan="2"></td>
            </tr>
            <tr>
            </tr>
            <tr>
                <td align="center">( ________ )</td>
                <td align="center">( ________ )</td>
                <td align="center">( ________ )</td>
                <td align="center">( ________ )</td>
                <td align="center">( ________ )</td>
                <td align="center">( ________ )</td>
            </tr>
        </table> 
    </footer>

    
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>