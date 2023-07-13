<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $title }}</title>
<style type="text/css">

    @page { margin: 10px; }
    body { margin: 10px;border: 1px solid black; }

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
        font-size: 10px;
    }

    .font-9 {
        font-size: 9px;
    }

    .font-8 {
        font-size: 8px;
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

    footer {
        position: fixed; 
        bottom: 3%; 
        left: 10px; 
        right: 10px;
        height: 180px; 
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
                <img src="{{ public_path('app-assets/images/logo/logo_po.png') }}" alt="logo" width="180%" height="180%" /> 
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
            <td valign="" width="10%" class="font-10 header-padding">{{ $bomHdr->model }}</td>
            <td valign="" width="4%" class="font-10 header-padding">No Revisi</td>
            <td valign="" width="5%" class="font-10 header-padding">1</td>
        </tr>
        <tr>
            <td valign="" class="font-10 header-padding">Part No</td>
            <td valign="" class="font-10 header-padding">{{ $bomHdr->part_no }}</td>
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
                    <td class="detail-padding" align="left">{{ $val->article_desc }}</td>
                    <td class="detail-padding" align="left">{{ $val->nama }}</td>
                    <td class="detail-padding" align="right">{{ $val->qty }}</td>
                    <td class="detail-padding" align="left">{{ $val->uom }}</td>
                    <td class="detail-padding" align="left">{{ $val->article_alternative_code }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer>
        
        {{-- <text class="font-9" style="padding-left:10px;">Note:{{ $bomHdr->note_hdr }}</p> --}}
        {{-- <h4 class="font-9" style="padding-left:10px;">No Revisi:</h4>
        <h4 class="font-9" style="padding-left:10px;">Engineering:</h4> --}}
        <textarea class="font-9" type="text" style="height:20%;padding-left:10px;padding-bottom:10px;border:none">Note:<br>{{ $bomHdr->note_hdr }}<br></textarea>
        <table style="border:none;">
            {{-- <tr>
                <td colspan="2" style="border:none;">
                    <textarea class="font-9" type="text" style="padding-left:10px;padding-bottom:10px;border:none">Note:<br>{{ $bomHdr->note_hdr }}<br></textarea>
                </td>
            </tr> --}}
            <tr>
                <td align="Left" class="detail-padding-bawah">No Revisi</td>
                <td align="Left" class="detail-padding-bawah">:{{ $bomHdr->num_revision }}</td>
            </tr>
            <tr>
                <td align="left" class="detail-padding-bawah">Enginering</td>
                <td align="left" class="detail-padding-bawah">:10 Juni 2022</td>
            </tr>
        </table>
        <table width="100%" border="0">
            <tr>
                <td width="10%" align="center">Prepared By</td>
                <td width="10%" align="center">Checked By</td>
                <td width="10%" align="center">Approved By</td>
                <td width="10%" align="center">Produksi</td>
                <td width="10%" align="center">PPIC</td>
                <td width="10%" align="center">Marketing</td>
            </tr>
            <tr>
                <td align="center" style="height: 40px;"></td>
                <td align="center" style="height: 40px;"></td>
                <td align="center" style="height: 40px;"></td>
                <td align="center" style="height: 40px;"></td>
                <td align="center" style="height: 40px;"></td>
                <td align="center" style="height: 40px;"></td>
            </tr>
            <tr>
                @foreach($approvalHistory as $val)
                    @if($val->status == true)
                        <td align="center">{{ $val->name }}</td>
                    @else
                        <td align="center"></td>
                    @endif
                @endforeach
            </tr>
        </table> 
    </footer>
    
{{-- @if($poNumber == "oki")
</div>
@endif --}}
</body>
</html>