<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;

use DB;


class ReportDnExport implements FromView,ShouldAutoSize,WithColumnFormatting,WithEvents
{
    protected $soNumber;

    function __construct($soNumber) {
        $this->soNumber = $soNumber;
    }
    /*https://docs.laravel-excel.com/3.1/exports/from-view.html*/

    public function view(): View
    {
        $soNumber=$this->soNumber;
        // $soNumber = 'SO/ASN/22/12/2571';
        
        $headers=DB::select("SELECT DISTINCT ON (c.article_alternative_code) a.article_code, a.so_number,c.article_alternative_code, c.article_desc,a.delivery_number
        ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
        ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
        ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
        from delivery_det a 
        left join delivery_hdr b on b.delivery_number = a.delivery_number
        left join article c on c.article_code = a.article_code
        where a.so_number = '$soNumber' 
        and b.status not in ('5','7','10')
        order by c.article_alternative_code");
        
        $barisIsiJudul='';
        $barisAll='';
        $jumlahBaris=0;

        foreach($headers as $val){
            $articleCode = $val->article_code;
            $articleDesc = $val->article_desc;
            $soNumber = $val->so_number;
            $articleAlternative = $val->article_alternative_code;
            $qtySo = $val->qty_so;
            $qtyDelivery = $val->qty_delivery;
            $qtySisa = $qtySo -$qtyDelivery;

            $judul = $val->article_alternative_code." - ".$articleDesc;
            
            $barisIsiJudul = "<tr>
                                <td></td><td align='center'>$articleAlternative - $articleDesc</td><td></td><td></td><td></td><td></td>
                            </tr>";
            // $barisIsiJudul = "<tr><td>$articleAlternative</td><td>$soNumber</td><td colspan='3'>".strtoupper($judul)."</td>
            //                         <td > QTY SO : ".number_format($qtySo,2)."</td> </tr>";
            // $barisIsiJudul .= "<tr >
            //         <td>No</td>
            //         <td>Delivery Number</td>
            //         <td>Delivery Date</td>
            //         <td>QTY Delivery</td>
            //     </tr>";
            
            $isiJudul=DB::select("SELECT a.article_code, c.article_alternative_code, c.article_desc,a.delivery_number
            ,b.delivery_date
            ,TO_DATE(b.delivery_date,'dd-mm-yyyy') as date_delivery
            ,a.qty
            ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
            ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
            ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
            ,(select invoice_number from invoice_det where dn_number = a.delivery_number limit 1) as invoice_number
            from delivery_det a 
            left join delivery_hdr b on b.delivery_number = a.delivery_number
            left join article c on c.article_code = a.article_code
            where a.so_number = '$soNumber' and a.article_code = '$articleCode'
            and b.status not in ('5','7','10')
            order by date_delivery,b.delivery_number");

            $jumlahBaris++;

            $barisIsiJudul .= "<tr >
                    <td align='left'>No.</td>
                    <td>Delivery Number</td>
                    <td>Delivery Date</td>
                    <td>Qty delivery</td>
                    <td>Invoice No.</td>
                    <td> Qty SO : ".number_format($qtySo,2)."</td> 
                </tr>";

            foreach($isiJudul as $key=>$item){
                $no = $key+1;
                $barisIsiJudul .= "<tr >
                    <td align='left'>$no</td>
                    <td>$item->delivery_number</td>
                    <td>$item->delivery_date</td>
                    <td align='left'>$item->qty</td>
                    <td>$item->invoice_number</td>
                    <td></td>
                </tr>";
                $jumlahBaris++;
            }

            // <td align='left'>".number_format($item->qty,2,'.',',')."</td>
            
            $barisTotal = "<tr><td></td><td></td><td></td>
                            <td align='left'>$qtyDelivery</td>
                            <td></td>
                            <td > Qty Sisa : ".number_format($qtySisa,2)."</td>
                        </tr>";
            
            $barisAll .= $barisIsiJudul.$barisTotal;
        }; 

        // <td align='left'>".number_format($qtyDelivery,2,'.',',')."</td>

        $salesOrders = DB::table('sales_order_hdr')
        ->leftJoin('third_party','third_party.kode','sales_order_hdr.customer_id')
        ->where('so_code',$soNumber)
        ->select('sales_order_hdr.so_code','sales_order_hdr.po_number','third_party.nama')
        ->orderBy('so_code')
        ->first();
              
        $data['barisDetail']=$barisAll;
        $data['soNumber'] = $salesOrders->so_code;
        $data['poNumber'] = $salesOrders->po_number;
        $data['customer'] = $salesOrders->nama;
        
        return view('delivery.printReportSoAccExcel', $data);

    }

    public function columnFormats(): Array
    {
        return [
            // 'D' => NumberFormat::FORMAT_NUMBER_00,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
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