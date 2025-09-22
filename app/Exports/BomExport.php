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


class BomExport implements FromView,ShouldAutoSize,WithColumnFormatting,WithEvents
{
    protected $searchBom;
    protected $articleCode;
    protected $status;

    function __construct($searchBom,$articleCode,$status) {
        $this->searchBom = $searchBom;
        $this->articleCode = $articleCode;
        $this->status = $status;
    }
    /*https://docs.laravel-excel.com/3.1/exports/from-view.html*/

    public function view(): View
    {
        $searchBom=$this->searchBom;
        $articleCode=$this->articleCode;
        $status= $this->status;

        $filter="";
        $filter2="";

        if ($searchBom){
            $filter.= "and bom_code ilike '%$searchBom%' "; 
        }
        
        if ($articleCode){
            $filter.= "and article_code = '$articleCode' "; 
        }

        if ($status){
            $filter.= "and status = '$status' "; 
        }        

        $headers=DB::select("SELECT d.bom_code
        ,num_revision
        ,(select concat(article_alternative_code,'-',article_desc) from article where article_code = bom_header.article_code) as article_finish_good
        ,(select concat(article_alternative_code,'-',article_desc) from article where article_code = bom_header.article_code_rm) as article_raw_material
        ,(select concat(kode,'-',nama) from third_party where kode = customer) as customer
        ,group_of_material
        ,bom_header.uom
        ,part_no
        ,model
        ,bom_header.note
        ,spray_booth_1
        ,spray_booth_2
        ,spray_booth_3
        ,tone_1
        ,tone_2
        ,tone_3
        ,tack_1
        ,tack_2
        ,tack_3
        ,pass_rate_1
        ,pass_rate_2
        ,pass_rate_3
        ,pass_trough_1
        ,pass_trough_2
        ,pass_trough_3
        ,cycle_time_buffing_1
        ,cycle_time_buffing_2
        ,cycle_time_buffing_3,
        d.* from 
        (select bom_code,
        max(case when urutan = '1' then article_code else '' end) as article_Code_1,
        max(case when urutan = '2' then article_code else '' end) as article_Code_2,
        max(case when urutan = '3' then article_code else '' end) as article_Code_3,
        max(case when urutan = '4' then article_code else '' end) as article_Code_4,
        max(case when urutan = '1' then tone else '' end) as tone_d_1,
        max(case when urutan = '2' then tone else '' end) as tone_d_2,
        max(case when urutan = '3' then tone else '' end) as tone_d_3,
        max(case when urutan = '4' then tone else '' end) as tone_d_4,
        max(case when urutan = '1' then pos else '' end) as pos_1,
        max(case when urutan = '2' then pos else '' end) as pos_2,
        max(case when urutan = '3' then pos else '' end) as pos_3,
        max(case when urutan = '4' then pos else '' end) as pos_4,
        max(case when urutan = '1' then qty else 0 end) as qty_1,
        max(case when urutan = '2' then qty else 0 end) as qty_2,
        max(case when urutan = '3' then qty else 0 end) as qty_3,
        max(case when urutan = '4' then qty else 0 end) as qty_4,
        max(case when urutan = '1' then uom else '' end) as uom_1,
        max(case when urutan = '2' then uom else '' end) as uom_2,
        max(case when urutan = '3' then uom else '' end) as uom_3,
        max(case when urutan = '4' then uom else '' end) as uom_4,
        max(case when urutan = '1' then uom_con else '' end) as uom_con_1,
        max(case when urutan = '2' then uom_con else '' end) as uom_con_2,
        max(case when urutan = '3' then uom_con else '' end) as uom_con_3,
        max(case when urutan = '4' then uom_con else '' end) as uom_con_4
        from 
        (select bom_code,
        replace(tone,'t','') as tone
        ,(select pos_name from bom_pos where pos_code =pos) as pos
        ,(select concat(article_alternative_code,'-',article_desc) from article where article_code = bom_det.article_code) as article_code
        ,qty,bom_det.uom,bom_det.uom_con,
        RANK () OVER ( 
                PARTITION BY article_code, bom_code
                ORDER BY urutan
            ) urutan 
        from bom_det 
        where bom_code in (select bom_code from bom_hdr where status <> '7' $filter)
        --where bom_det.bom_code in ('BOM022400001739')
        --where bom_det.bom_code in ('BOM072200000088')
        order by article_code) a
        group by bom_code,article_code) d
        left join (
        select 
        bom_code,
        max(case when urutan = '1' then replace(spray_booth,'sb','Booth ') else '' end) as spray_booth_1,
        max(case when urutan = '2' then replace(spray_booth,'sb','Booth ') else '' end) as spray_booth_2,
        max(case when urutan = '3' then replace(spray_booth,'sb','Booth ') else '' end) as spray_booth_3,
        max(case when urutan = '1' then replace(tone,'t','Tone ')  else '' end) as tone_1,
        max(case when urutan = '2' then replace(tone,'t','Tone ') else '' end) as tone_2,
        max(case when urutan = '3' then replace(tone,'t','Tone ') else '' end) as tone_3,
        max(case when urutan = '1' then tack else 0 end) as tack_1,
        max(case when urutan = '2' then tack else 0 end) as tack_2,
        max(case when urutan = '3' then tack else 0 end) as tack_3,
        max(case when urutan = '1' then pass_rate else 0 end) as pass_rate_1,
        max(case when urutan = '2' then pass_rate else 0 end) as pass_rate_2,
        max(case when urutan = '3' then pass_rate else 0 end) as pass_rate_3,
        max(case when urutan = '1' then pass_thru else 0 end) as pass_trough_1,
        max(case when urutan = '2' then pass_thru else 0 end) as pass_trough_2,
        max(case when urutan = '3' then pass_thru else 0 end) as pass_trough_3,
        max(case when urutan = '1' then cycle_time else 0 end) as cycle_time_buffing_1,
        max(case when urutan = '2' then cycle_time else 0 end) as cycle_time_buffing_2,
        max(case when urutan = '2' then cycle_time else 0 end) as cycle_time_buffing_3
        from bom_spray_booth where 
        bom_code in (select bom_code from bom_hdr where status <> '7')
        --and bom_code in ('BOM022400001737','BOM032400001750','BOM022400001741','BOM022400001739')
        group by bom_code
        order by bom_code) spray_booth on spray_booth.bom_code = d.bom_code
        left join (select * from bom_hdr where status <> '7') bom_header on bom_header.bom_code = d.bom_code
        where d.bom_code in (select bom_code from bom_hdr where status <> '7')
        --and d.bom_code in ('BOM022400001739')
        --and pos_4 <> ''
        order by bom_header.article_code_rm,bom_header.article_code
        ");
        
        $barisIsiJudul='';
        $barisAll='';
        $jumlahBaris=0;

        // $barisHeader = "<tr>
        //                 <td>Bom Code</td>
        //                 <td>Num Revision</td>
        //                 <td>Article Finish Good</td>
        //                 <td>Article Raw Material</td>
        //                 <td>Customer</td>
        //                 <td>Group Of Material</td>
        //                 <td>Bom Header</td>
        //                 <td>Part No</td>
        //                 <td>Model</td>
        //                 <td>Note</td>
        //                 <td>Spray Booth 1</td>
        //                 <td>Spray Booth 2</td>
        //                 <td>Spray Booth 3</td>
        //                 <td>Tone 1</td>
        //                 <td>Tone 2</td>
        //                 <td>Tone 3</td>
        //                 <td>Tack 1</td>
        //                 <td>Tack 2</td>
        //                 <td>Tack 3</td>
        //                 <td>Pass Rate 1</td>
        //                 <td>Pass Rate 2</td>
        //                 <td>Pass Rate 3</td>
        //                 <td>Pass Trough 1</td>
        //                 <td>Pass Trough 2</td>
        //                 <td>Pass Trough 3</td>
        //                 <td>Cycle Time Buffing 1</td>
        //                 <td>Cycle Time Buffing 2</td>
        //                 <td>Cycle Time Buffing 3</td>
        //                 <td>Article Code 1</td>
        //                 <td>Article Code 2</td>
        //                 <td>Article Code 3</td>
        //                 <td>Article Code 4</td>
        //                 <td>Tone 1</td>
        //                 <td>Tone 2</td>
        //                 <td>Tone 3</td>
        //                 <td>Tone 4</td>
        //                 <td>Pos 1</td>
        //                 <td>Pos 2</td>
        //                 <td>Pos 3</td>
        //                 <td>Pos 4</td>
        //                 <td>Qty 1</td>
        //                 <td>Qty 2</td>
        //                 <td>Qty 3</td>
        //                 <td>Qty 4</td>
        //                 <td>Uom 1</td>
        //                 <td>Uom 2</td>
        //                 <td>Uom 3</td>
        //                 <td>Uom 4</td>
        //                 <td>Uom Con 1</td>
        //                 <td>Uom Con 2</td>
        //                 <td>Uom Con 3</td>
        //                 <td>Uom Con 4</td>
        //                 </tr>";
                                    
        // foreach($headers as $val){
            
        //     $bom_code = $val->bom_code;
        //     $num_revision = $val->num_revision;
        //     $article_finish_good = $val->article_finish_good;
        //     $article_raw_material = $val->article_raw_material;
        //     $customer = $val->customer;
        //     $group_of_material = $val->group_of_material;
        //     $uom = $val->uom;
        //     $part_no = $val->part_no;
        //     $model = $val->model;
        //     $note = $val->note;
        //     $spray_booth_1 = $val->spray_booth_1;
        //     $spray_booth_2 = $val->spray_booth_2;
        //     $spray_booth_3 = $val->spray_booth_3;
        //     $tone_1 = $val->tone_1;
        //     $tone_2 = $val ->tone_2;
        //     $tone_3 = $val ->tone_3;
        //     $tack_1 = $val->tack_1;
        //     $tack_2 = $val->tack_2;
        //     $tack_3 = $val->tack_3;
        //     $pass_rate_1 = $val ->pass_rate_1;
        //     $pass_rate_2 = $val ->pass_rate_2;
        //     $pass_rate_3 = $val ->pass_rate_3;
        //     $pass_trough_1 = $val ->pass_trough_1;
        //     $pass_trough_2 = $val ->pass_trough_2;
        //     $pass_trough_3 = $val ->pass_trough_3;
        //     $cycle_time_buffing_1= $val ->cycle_time_buffing_1;
        //     $cycle_time_buffing_2= $val ->cycle_time_buffing_2;
        //     $cycle_time_buffing_3= $val ->cycle_time_buffing_3;
        //     $article_code_1 = $val->article_code_1;
        //     $article_code_2 = $val->article_code_2;
        //     $article_code_3 = $val->article_code_3;
        //     $article_code_4 = $val->article_code_4;
        //     $tone1 = $val->tone_d_1;
        //     $tone2 = $val->tone_d_2;
        //     $tone3 = $val->tone_d_3;
        //     $tone4 = $val->tone_d_4;
        //     $pos1 = $val->pos_1;
        //     $pos2 = $val->pos_2;
        //     $pos3 = $val->pos_3;
        //     $pos4 = $val->pos_4;
        //     $qty1 = $val->qty_1;
        //     $qty2 = $val->qty_2;
        //     $qty3 = $val->qty_3;
        //     $qty4 = $val->qty_4;
        //     $uom1 = $val->uom_1;
        //     $uom2 = $val->uom_2;
        //     $uom3 = $val->uom_3;
        //     $uom4 = $val->uom_4;
        //     $uom_con_1 = $val->uom_con_1;
        //     $uom_con_2 = $val->uom_con_2;
        //     $uom_con_3 = $val->uom_con_3;
        //     $uom_con_4 = $val->uom_con_4;
            
        //     $barisIsi = "<tr>
        //                         <td>$bom_code</td>
        //                         <td>$num_revision</td>
        //                         <td>$article_finish_good</td>
        //                         <td>$article_raw_material</td>
        //                         <td>$customer</td>
        //                         <td>$group_of_material</td>
        //                         <td>$uom</td>
        //                         <td>$part_no</td>
        //                         <td>$model</td>
        //                         <td>$note</td>
        //                         <td>$spray_booth_1</td>
        //                         <td>$spray_booth_2</td>
        //                         <td>$spray_booth_3</td>
        //                         <td>$tone_1</td>
        //                         <td>$tone_2</td>
        //                         <td>$tone_3</td>
        //                         <td>$tack_1</td>
        //                         <td>$tack_2</td>
        //                         <td>$tack_3</td>
        //                         <td>$pass_rate_1</td>
        //                         <td>$pass_rate_2</td>
        //                         <td>$pass_rate_3</td>
        //                         <td>$pass_trough_1</td>
        //                         <td>$pass_trough_2</td>
        //                         <td>$pass_trough_3</td>
        //                         <td>$cycle_time_buffing_1</td>
        //                         <td>$cycle_time_buffing_2</td>
        //                         <td>$cycle_time_buffing_3</td>
        //                         <td>$article_code_1</td>
        //                         <td>$article_code_2</td>
        //                         <td>$article_code_3</td>
        //                         <td>$article_code_4</td>
        //                         <td>$tone1</td>
        //                         <td>$tone2</td>
        //                         <td>$tone3</td>
        //                         <td>$tone4</td>
        //                         <td>$pos1</td>
        //                         <td>$pos2</td>
        //                         <td>$pos3</td>
        //                         <td>$pos4</td>
        //                         <td>$qty1</td>
        //                         <td>$qty2</td>
        //                         <td>$qty3</td>
        //                         <td>$qty4</td>
        //                         <td>$uom1</td>
        //                         <td>$uom2</td>
        //                         <td>$uom3</td>
        //                         <td>$uom4</td>
        //                         <td>$uom_con_1</td>
        //                         <td>$uom_con_2</td>
        //                         <td>$uom_con_3</td>
        //                         <td>$uom_con_4</td>
        //                     </tr>";
                                    
        //     $barisAll .= $barisIsi;
        // }; 

        // $barisAll = $barisHeader.$barisAll;
              
        // $data['barisDetail']=$barisAll;
        $data['headers']=$headers;       
        return view('bom.exportToExcel', $data);
    }

    public function columnFormats(): Array
    {
        return [
            // 'D' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            // AfterSheet::class    => function(AfterSheet $event) {
            //     $alphabet   = $event->sheet->getHighestDataColumn();
            //     $totalRow   = $event->sheet->getHighestDataRow();
            //     $cellRange  = 'A1:'.$alphabet.$totalRow;

            //     $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(10);
            //     // $cellRange = 'A1:W1'; // All headers
            //     // $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
            // },
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