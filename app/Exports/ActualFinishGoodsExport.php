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


class ActualFinishGoodsExport implements FromView,ShouldAutoSize,WithColumnFormatting,WithEvents
{
    protected $wosNumber;
    protected $prdNumber;

    function __construct($wosNumber,$prdNumber) {
        $this->wosNumber = $wosNumber;
        $this->prdNumber = $prdNumber;
    }

    /*https://docs.laravel-excel.com/3.1/exports/from-view.html*/

    public function view(): View
    {
        $wosNumber=$this->wosNumber;
        $prdNumber=$this->prdNumber;
        // $wosNumber = 'WO/ASN/2024/2/17';
        
        // $headers=DB::select("SELECT 
        // wo_code
        // ,wo_det.article_code
        // ,urutan
        // ,case when so_code ='other' then plan_qty_fresh else act_qty_fresh end as qty_fresh
        // ,act_qty_repaint
        // ,act_tag 
        // ,article_alternative_code
        // ,article_desc
        // ,case when wo_det.so_code ='other' then wo_det.article_code else article_alternative_code end as article_code_1
        // from wo_det 
        // left join article on article.article_code = wo_det.article_code
        // where wo_code = '$wosNumber' order by urutan");

        $details = DB::table('production_det')
        ->leftJoin('article','article.article_code','=','production_det.article_code')
        ->where('prod_code',$prdNumber)
        ->where('so_code','!=','other')
        ->select('production_det.*'
        ,DB::RAW("
        concat(article.article_alternative_code,article.article_desc) as article")
        ,'article.article_alternative_code'
        ,'article.article_desc')
        ->orderBy('id')
        ->get();

        $barisIsi = "";
        $barisIsiJudul = "<tr >
            <td>No</td>
            <td>prod_code</td>
            <td>sales_order</td>
            <td>article_code</td>
            <td>article_desc</td>
            <td>qty_finish_goods</td>
        </tr>";
        // <td > Qty SO : ".number_format($qtySo,2)."</td> 

        foreach($details as $val){
            $prdCode = $prdNumber;
            $soCode = $val->so_code;
            $articleCode = $val->article_alternative_code;
            $articleDesc = $val->article_desc;
            $urutan = $val->urutan;
                        
            $barisIsi .= "<tr>
                <td>$urutan</td>
                <td>$prdCode</td>
                <td>$soCode</td>
                <td>$articleCode</td>
                <td>$articleDesc</td>
                <td>0</td>
            </tr>";
            
        }; 

        $data['barisDetail']=$barisIsiJudul.$barisIsi;
        
        return view('production.actualLoading.templateExcel', $data);

    }

    public function columnFormats(): Array
    {
        return [
            // 'E' => NumberFormat::FORMAT_NUMBER_00,
            // 'F' => NumberFormat::FORMAT_NUMBER_00,
            // 'G' => NumberFormat::FORMAT_NUMBER_00,
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