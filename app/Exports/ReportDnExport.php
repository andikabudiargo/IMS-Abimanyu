<?php

namespace App\Exports;

// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

use DB;


class ReportDnExport extends DefaultValueBinder implements FromView,ShouldAutoSize,WithColumnFormatting,WithEvents,WithCustomValueBinder
{
    protected $soNumber;

    function __construct($soNumber) {
        $this->soNumber = $soNumber;
    }
    /*https://docs.laravel-excel.com/3.1/exports/from-view.html*/

    // public function view(): View
    // {
    //     $soNumber=$this->soNumber;
    //     $soNumbers = explode(',',$soNumber);

    //     $barisIsiJudul='';
    //     $barisAll='';
    //     $jumlahBaris=0;
    //     $namaCustomer = "";

    //     $headerBySO = " <tr> 
    //                         <td colspan='9' align='center'> <strong>SO REPORT</strong></td>
    //                     </tr>";
    //                         // <tr><td valign=''></td><td valign=''></td><td></td></tr>";
    //                         // <tr><td valign=''></td><td valign='' ></td><td></td></tr>";

    //     $barisPemisah = "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";

    //     foreach($soNumbers as $key => $value){
    //         $headers=DB::select("SELECT DISTINCT ON (c.article_alternative_code) a.article_code, a.so_number,c.article_alternative_code, c.article_desc,a.delivery_number
    //         ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
    //         ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
    //         ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
    //         from delivery_det a 
    //         left join delivery_hdr b on b.delivery_number = a.delivery_number
    //         left join article c on c.article_code = a.article_code
    //         where a.so_number = '$value' 
    //         and b.status not in ('5','7','10')
    //         order by c.article_alternative_code"); 

    //         $barisAll1='';
    //         $salesOrders = DB::table('sales_order_hdr')
    //             ->leftJoin('third_party','third_party.kode','sales_order_hdr.customer_id')
    //             ->where('so_code',$value)
    //             ->select('sales_order_hdr.so_code','sales_order_hdr.so_date','sales_order_hdr.po_number','third_party.nama')
    //             ->orderBy('so_code')
    //             ->first();

    //         $namaCustomer = $salesOrders->nama;

    //         // $headerBySO = "<tr> <td colspan='5' align='center'> <strong>SO REPORT</strong></td></tr>
    //         //                 <tr><td valign=''></td><td valign=''></td><td></td></tr>
    //         //                 <tr><td valign=''></td><td valign='' ></td><td></td></tr>
    //         //                 <tr><td valign=''>SO Date</td><td valign=''>: ".$salesOrders->so_date."</td><td></td></tr>
    //         //                 <tr><td valign=''>No Order</td><td valign=''>: ".$salesOrders->so_code."</td><td></td></tr>
    //         //                 <tr><td valign=''>No PO</td><td valign=''>: ".$salesOrders->po_number."</td><td></td></tr>
    //         //                 <tr><td valign=''>Customer</td><td valign=''>: ".$salesOrders->nama."</td><td></td></tr>";

    //         foreach($headers as $val){
    //             $articleCode = $val->article_code;
    //             $articleDesc = htmlspecialchars($val->article_desc,ENT_QUOTES);
    //             $soNumber = $val->so_number;
    //             $articleAlternative = $val->article_alternative_code;
    //             $qtySo = $val->qty_so;
    //             $qtyDelivery = $val->qty_delivery;
    //             $qtySisa = $qtySo -$qtyDelivery;

    //             // $judul = $val->article_alternative_code." - ".$articleDesc;
                
    //             // $barisIsiJudul = "<tr>
    //             //                     <td valign=''>Customer</td><td valign=''>: $salesOrders->nama</td><td></td><td></td><td></td><td></td><td></tr>;
    //             //                 <tr>
    //             //                     <td></td>
    //             //                     <td align='center'>$articleAlternative - $articleDesc</td><td></td><td></td><td></td><td></td>
    //             //                 </tr>";
    //              $barisIsiJudul = "
    //                             <tr>
    //                                 <td></td>
    //                                 <td align='center'>$articleAlternative - $articleDesc</td><td></td><td></td><td></td><td></td>
    //                             </tr>";
    //             // $barisIsiJudul = "<tr><td>$articleAlternative</td><td>$soNumber</td><td colspan='3'>".strtoupper($judul)."</td>
    //             //                         <td > QTY SO : ".number_format($qtySo,2)."</td> </tr>";
    //             // $barisIsiJudul .= "<tr >
    //             //         <td>No</td>
    //             //         <td>Delivery Number</td>
    //             //         <td>Delivery Date</td>
    //             //         <td>QTY Delivery</td>
    //             //     </tr>";
                
    //             $isiJudul=DB::select("SELECT a.article_code, c.article_alternative_code, c.article_desc,a.delivery_number
    //             ,b.delivery_date
    //             ,TO_DATE(b.delivery_date,'dd-mm-yyyy') as date_delivery
    //             ,a.qty
    //             ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
    //             ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
    //             ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
    //             ,(select invoice_number from invoice_det where dn_number = a.delivery_number limit 1) as invoice_number
    //             from delivery_det a 
    //             left join delivery_hdr b on b.delivery_number = a.delivery_number
    //             left join article c on c.article_code = a.article_code
    //             where a.so_number = '$soNumber' and a.article_code = '$articleCode'
    //             and b.status not in ('5','7','10')
    //             order by date_delivery,c.article_alternative_code");

    //             // dd($isiJudul);

    //             $jumlahBaris++;

    //             $barisIsiJudul .= "<tr >
    //                     <td align='left'>No.</td>
    //                     <td>Delivery Number</td>
    //                     <td>Delivery Date</td>
    //                     <td>Qty delivery</td>
    //                     <td>SO Date</td>
    //                     <td>No Order</td>
    //                     <td>No PO</td>
    //                     <td>Invoice No.</td>
    //                     <td> Qty SO : ".number_format($qtySo,2)."</td> 
    //                 </tr>";

    //             foreach($isiJudul as $key=>$item){
    //                 $no = $key+1;
    //                 $barisIsiJudul .= "<tr >
    //                     <td align='left'>$no</td>
    //                     <td>$item->delivery_number</td>
    //                     <td>$item->delivery_date</td>
    //                     <td align='left'>$item->qty</td>
    //                     <td>$salesOrders->so_date</td>
    //                     <td>$salesOrders->so_code</td>
    //                     <td>$salesOrders->po_number</td>
    //                     <td>$item->invoice_number</td>
    //                     <td></td>
    //                 </tr>";
    //                 $jumlahBaris++;
    //             }

    //             // <td align='left'>".number_format($item->qty,2,'.',',')."</td>
                
    //             $barisTotal = "<tr><td></td><td></td><td></td>
    //                             <td align='left'>$qtyDelivery</td>
    //                             <td></td><td></td><td></td><td></td>
    //                             <td> Qty Sisa : ".number_format($qtySisa,2)."</td>
    //                         </tr>";

    //             $barisAll1 .= $barisPemisah.$barisIsiJudul.$barisTotal;
    //         }; 

    //         // $barisAll .= $headerBySO.$barisAll1;
    //         $barisAll .= $barisAll1;
    //     }

    //     // $soNumber = implode(',', $soNumbers);
        
    //     // $headers=DB::select("SELECT DISTINCT ON (c.article_alternative_code) a.article_code, a.so_number,c.article_alternative_code, c.article_desc,a.delivery_number
    //     // ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
    //     // ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
    //     // ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
    //     // from delivery_det a 
    //     // left join delivery_hdr b on b.delivery_number = a.delivery_number
    //     // left join article c on c.article_code = a.article_code
    //     // --where a.so_number = '$soNumber' 
    //     // where a.so_number in ($formatted) 
    //     // and b.status not in ('5','7','10')
    //     // order by c.article_alternative_code");
        
    //     // $barisIsiJudul='';
    //     // $barisAll='';
    //     // $jumlahBaris=0;

    //     // foreach($headers as $val){
    //     //     $articleCode = $val->article_code;
    //     //     $articleDesc = htmlspecialchars($val->article_desc,ENT_QUOTES);
    //     //     $soNumber = $val->so_number;
    //     //     $articleAlternative = $val->article_alternative_code;
    //     //     $qtySo = $val->qty_so;
    //     //     $qtyDelivery = $val->qty_delivery;
    //     //     $qtySisa = $qtySo -$qtyDelivery;

    //     //     $judul = $val->article_alternative_code." - ".$articleDesc;
            
    //     //     $barisIsiJudul = "<tr>
    //     //                         <td></td><td align='center'>$articleAlternative - $articleDesc</td><td></td><td></td><td></td><td></td>
    //     //                     </tr>";
    //     //     // $barisIsiJudul = "<tr><td>$articleAlternative</td><td>$soNumber</td><td colspan='3'>".strtoupper($judul)."</td>
    //     //     //                         <td > QTY SO : ".number_format($qtySo,2)."</td> </tr>";
    //     //     // $barisIsiJudul .= "<tr >
    //     //     //         <td>No</td>
    //     //     //         <td>Delivery Number</td>
    //     //     //         <td>Delivery Date</td>
    //     //     //         <td>QTY Delivery</td>
    //     //     //     </tr>";
            
    //     //     $isiJudul=DB::select("SELECT a.article_code, c.article_alternative_code, c.article_desc,a.delivery_number
    //     //     ,b.delivery_date
    //     //     ,TO_DATE(b.delivery_date,'dd-mm-yyyy') as date_delivery
    //     //     ,a.qty
    //     //     ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
    //     //     ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
    //     //     ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
    //     //     ,(select invoice_number from invoice_det where dn_number = a.delivery_number limit 1) as invoice_number
    //     //     from delivery_det a 
    //     //     left join delivery_hdr b on b.delivery_number = a.delivery_number
    //     //     left join article c on c.article_code = a.article_code
    //     //     where a.so_number = '$soNumber' and a.article_code = '$articleCode'
    //     //     and b.status not in ('5','7','10')
    //     //     order by date_delivery,b.delivery_number");

    //     //     $jumlahBaris++;

    //     //     $barisIsiJudul .= "<tr >
    //     //             <td align='left'>No.</td>
    //     //             <td>Delivery Number</td>
    //     //             <td>Delivery Date</td>
    //     //             <td>Qty delivery</td>
    //     //             <td>Invoice No.</td>
    //     //             <td> Qty SO : ".number_format($qtySo,2)."</td> 
    //     //         </tr>";

    //     //     foreach($isiJudul as $key=>$item){
    //     //         $no = $key+1;
    //     //         $barisIsiJudul .= "<tr >
    //     //             <td align='left'>$no</td>
    //     //             <td>$item->delivery_number</td>
    //     //             <td>$item->delivery_date</td>
    //     //             <td align='left'>$item->qty</td>
    //     //             <td>$item->invoice_number</td>
    //     //             <td></td>
    //     //         </tr>";
    //     //         $jumlahBaris++;
    //     //     }

    //     //     // <td align='left'>".number_format($item->qty,2,'.',',')."</td>
            
    //     //     $barisTotal = "<tr><td></td><td></td><td></td>
    //     //                     <td align='left'>$qtyDelivery</td>
    //     //                     <td></td>
    //     //                     <td > Qty Sisa : ".number_format($qtySisa,2)."</td>
    //     //                 </tr>";
            
    //     //     $barisAll .= $barisIsiJudul.$barisTotal;
    //     // }; 

    //     // <td align='left'>".number_format($qtyDelivery,2,'.',',')."</td>

    //     // $salesOrders = DB::table('sales_order_hdr')
    //     // ->leftJoin('third_party','third_party.kode','sales_order_hdr.customer_id')
    //     // ->where('so_code',$soNumber)
    //     // ->select('sales_order_hdr.so_code','sales_order_hdr.po_number','third_party.nama')
    //     // ->orderBy('so_code')
    //     // ->first();
    //     $customer = "<tr>
    //                     <td valign=''>Customer</td><td valign=''>: $namaCustomer</td><td></td><td></td><td></td><td></td><td>
    //                 </tr>";

    //     $data['barisDetail']=$headerBySO.$customer.$barisAll;
    //     // $data['barisDetail']=$barisAll;
    //     // $data['soNumber'] = $salesOrders->so_code;
    //     // $data['poNumber'] = $salesOrders->po_number;
    //     // $data['customer'] = $salesOrders->nama;
        
    //     return view('delivery.printReportSoAccExcel', $data);

    // }

     public function view(): View
    {
        $soNumber=$this->soNumber;
        $soNumbers = explode(',',$soNumber);
        $soNumberArr = implode("','", $soNumbers);

        $barisIsiJudul='';
        $barisAll='';
        $jumlahBaris=0;
        $namaCustomer = "";

        $headerBySO = " <tr> 
                            <td colspan='10' align='center'> <strong>SO REPORT</strong></td>
                        </tr>";

        $barisPemisah = "<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";

        // foreach($soNumbers as $key => $value){

            $headers=DB::select("SELECT DISTINCT ON (c.article_alternative_code) a.article_code, a.so_number,c.article_alternative_code, c.article_desc,a.delivery_number
            ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
            ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
            ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
            from delivery_det a 
            left join delivery_hdr b on b.delivery_number = a.delivery_number
            left join article c on c.article_code = a.article_code
            where a.so_number in ('$soNumberArr') 
            and b.status not in ('5','7','10')
            order by c.article_alternative_code");


            $barisAll1='';
            $salesOrders = DB::table('sales_order_hdr')
                ->leftJoin('third_party','third_party.kode','sales_order_hdr.customer_id')
                ->where('so_code',$headers[0]->so_number)
                ->select('sales_order_hdr.so_code','sales_order_hdr.so_date','sales_order_hdr.po_number','third_party.nama')
                ->orderBy('so_code')
                ->first();

            $namaCustomer = $salesOrders->nama;
            
            $akumQtySo=0;
            $assrSo="";
            $totalSO = 0;

            foreach($headers as $val){
                $articleCode = $val->article_code;
                $articleDesc = htmlspecialchars($val->article_desc,ENT_QUOTES);
                $soNumber = $val->so_number;
                $articleAlternative = $val->article_alternative_code;
                // $qtySo = $val->qty_so;
                $qtyDelivery=0;
                // $qtyDelivery = $val->qty_delivery;
                // $qtySisa = $qtySo -$qtyDelivery;
                
                $barisIsiJudulDetail ="";
                $akumQtySisa=0;
                
                $isiJudul=DB::select("SELECT a.article_code
                ,c.article_alternative_code
                ,c.article_desc
                ,a.delivery_number
                ,b.delivery_date
                ,b.so_number
                ,b.po_number
                ,(select so_date from sales_order_hdr soh where so_code = b.so_number)
                ,TO_DATE(b.delivery_date,'dd-mm-yyyy') as date_delivery
                ,a.qty
                ,a.qty_so
                --,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code and so_code = b.so_number)) as qty_so_asli 
                ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
                --,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
                ,(select invoice_number from invoice_det where dn_number = a.delivery_number limit 1) as invoice_number
                from delivery_det a 
                left join delivery_hdr b on b.delivery_number = a.delivery_number
                left join article c on c.article_code = a.article_code
                where a.so_number in ('$soNumberArr') and a.article_code = '$articleCode'
                and b.status not in ('5','7','10')
                order by date_delivery,c.article_alternative_code");

                $jumlahBaris++;
                $assrSo="";
                foreach($isiJudul as $key=>$item){
                    // $akumQtySo=$akumQtySo+$item->qty_so;
                    $qtyDelivery = $qtyDelivery+$item->qty;
                    $qtySisaDetail = $item->qty_so-$item->qty;
                    $assrSo .= "'".$item->so_number."',"; 

                    $no = $key+1;
                    $barisIsiJudulDetail .= "<tr >
                        <td align='left'>$no</td>
                        <td>$item->delivery_number</td>
                        <td align='left'>$item->qty_so</td>
                        <td>$item->delivery_date</td>
                        <td align='left'>$item->qty</td>
                        <td align='left'>$qtySisaDetail</td>
                        <td>$item->so_date</td>
                        <td>$item->so_number</td>
                        <td>$item->po_number</td>
                        <td>$item->invoice_number</td>
                        <td></td>
                    </tr>";

                    $jumlahBaris++;
                }

                $assrSo = substr($assrSo, 0, -1);
                $totalSO = DB::select("select sum(qty) as qty_so from sales_order_det where so_code in ($assrSo) and article_code = '$articleCode'");
                $akumQtySo = $totalSO[0]->qty_so;

                $barisIsiJudul = "
                                <tr>
                                    <td></td>
                                    <td align='left'>$articleAlternative - $articleDesc</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>Qty SO : ".number_format($akumQtySo,2)."</td>
                                </tr>";

                    $barisIsiJudul .= "<tr >
                            <td align='left'>No.</td>
                            <td>Delivery Number</td>
                            <td>Qty SO</td>
                            <td>Delivery Date</td>
                            <td>Qty delivery</td>
                            <td>Qty Sisa</td>
                            <td>SO Date</td>
                            <td>No Order</td>
                            <td>No PO</td>
                            <td>Invoice No.</td>
                        </tr>";

                $akumQtySisa = $akumQtySo-$qtyDelivery;

                $barisTotal = "<tr><td></td><td></td><td></td><td></td>
                                <td align='left'>$qtyDelivery</td>
                                <td></td><td></td><td></td><td></td>
                                <td> Qty Sisa : ".number_format($akumQtySisa,2)."</td>
                            </tr>";

                // $barisAll1 .= $barisPemisah.$barisIsiJudul.$barisIsiJudulDetail.$barisTotal;
                $barisAll1 .= $barisIsiJudul.$barisIsiJudulDetail.$barisTotal;
            }; 
            $barisAll .= $barisAll1;
        // }

        $customer = "<tr>
                        <td valign=''>Customer</td><td valign=''>: $namaCustomer</td><td></td><td></td><td></td><td></td><td>
                    </tr>";

        $data['barisDetail']=$headerBySO.$customer.$barisAll;
        
        return view('delivery.printReportSoAccExcel', $data);

    }

    public function columnFormats(): Array
    {
        return [
            // 'D' => NumberFormat::FORMAT_NUMBER_00,
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        // Cek jika nilai adalah nomor panjang (contoh: lebih dari 10 digit)
        if (is_numeric($value) && strlen((string)$value) > 10) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }

        // Untuk kolom tertentu yang harus selalu text
        // if (in_array($cell->getColumn(), ['A', 'B', 'C'])) {
        //     $cell->setValueExplicit($value, DataType::TYPE_STRING);
        //     return true;
        // }

        return parent::bindValue($cell, $value);
    }
    

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $alphabet   = $event->sheet->getHighestDataColumn();
                $totalRow   = $event->sheet->getHighestDataRow();
                $cellRange  = 'A1:'.$alphabet.$totalRow;
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(10);
                // $cellRange = 'A1:W1'; // All headers
                // $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
                // Atau untuk seluruh kolom
                $event->sheet->getStyle('H')->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);
            },
        ];
    }

    
    /*supaya ada border nya*/
    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function(AfterSheet $event) {

    //             $alphabet   = $event->sheet->getHighestDataColumn();
    //             $totalRow   = $event->sheet->getHighestDataRow();
    //             $cellRange  = 'A6:'.$alphabet.$totalRow;

    //             $event->sheet->getStyle($cellRange)->applyFromArray([
    //                 'borders' => [
    //                     'allBorders' => [
    //                         'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
    //                     ],
    //                 ],
    //             ])->getAlignment()->setWrapText(true);

    //         },
    //     ];
    // }

}


// class ReportDnExport implements FromCollection, WithHeadings,ShouldAutoSize,WithTitle
// {
//     /**
//     * @return \Illuminate\Support\Collection
//     */

//     protected $soNumber;

//     function __construct($soNumber) {
//         $this->soNumber = $soNumber;
//     }

//     public function collection()
//     {

//         // return DB::table('article')
//         // ->leftJoin('article_stock','article_stock.article_code','article.article_code')
//         // ->select('article_alternative_code','article_desc','article_stock.article_qty')
//         // ->orderBy('article.article_code')
//         // ->get();
//         $soNumber = $this->soNumber;
//         // $soNumber = 'SO/ASN/22/12/2571';

//         $results = DB::select("SELECT a.article_code, c.article_alternative_code, c.article_desc,a.delivery_number
//         , b.delivery_date,a.qty
//         ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
//         ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code)) as qty_delivery
//         ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code) as sisa_so
//         from delivery_det a 
//         left join delivery_hdr b on b.delivery_number = a.delivery_number
//         left join article c on c.article_code = a.article_code
//         where a.so_number = '$soNumber' 
//         order by a.article_code,b.delivery_date");

//         return collect($results); 
//     }

//     public function headings(): array
//     {
//         return ["article_code", "article_desc","qty"];
//     }

//     public function title(): string
//     {
//         return 'report_so';
//     }
// }