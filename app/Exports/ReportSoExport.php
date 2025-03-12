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

// class ReportSoExport implements FromCollection, WithHeadings,ShouldAutoSize,WithTitle
class ReportSoExport implements FromView,ShouldAutoSize,WithColumnFormatting,WithEvents
{
       
    protected $seachPo;
    protected $searchOrder;
    protected $searchCustomer;
    protected $orderDate;
    function __construct($searchOrder,$seachPo,$searchCustomer,$orderDate) {
        $this->searchOrder = $searchOrder;
        $this->seachPo = $seachPo;
        $this->searchCustomer = $searchCustomer;
        $this->orderDate = $orderDate;
    }

    // public function collection()
    public function view(): View
    {
        $searchOrder = $this->searchOrder;
        $seachPo = strtolower($this->seachPo);
        $searchCustomer = $this->searchCustomer;
        $orderDate = $this->orderDate;
        // $orderDate = "01-09-2024 to 12-03-2025";
        $fromDate = "";
        $toDate = "";

        if ($orderDate){
            $date = explode("to",$orderDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }      

        $results = DB::table('sales_order_det')
        ->leftJoin('sales_order_hdr','sales_order_hdr.so_code','sales_order_det.so_code')
        ->leftJoin('third_party','third_party.kode','sales_order_hdr.customer_id')
        ->leftJoin('article','article.article_code','sales_order_det.article_code')
        ->leftJoin('uom','uom.code','sales_order_det.uom')
        ->where(function ($query) use ($seachPo,$searchOrder,$searchCustomer,$fromDate,$toDate) {
            $seachPo ? $query->where('po_number','ilike','%'.$seachPo.'%') :'';
            $searchOrder ? $query->whereIn('sales_order_hdr.so_code',$searchOrder) : '';
            $searchCustomer ? $query->where('customer_id',$searchCustomer) :'';
            $fromDate ? $query->whereBetween(DB::raw("to_date(so_date,'DD-MM-YYYY')"), [$fromDate, $toDate]):'';
        })
        ->where('sales_order_hdr.so_code','<>',null)
        ->whereNotIn('sales_order_hdr.status',['5','8'])
        ->where('sales_order_det.status',['1'])
        ->select('sales_order_det.*'
        ,'sales_order_det.status as status_det'
        ,'sales_order_det.qty as qty_det'
        ,'sales_order_hdr.po_number'
        ,'sales_order_hdr.status as statusku'
        ,'sales_order_hdr.so_code'
        ,'sales_order_hdr.so_date'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'third_party.kode as customer_code'
        ,'third_party.nama as customer'
        ,'sales_order_det.ppn as ppn_price'
        ,'sales_order_det.id as id_det'
        ,db::raw("(select sum(qty) from delivery_det a
        left join delivery_hdr b on a.delivery_number=b.delivery_number 
        where a.so_number = sales_order_hdr.so_code and a.article_code = sales_order_det.article_code 
        and b.status not in ('5','7')  group by article_code) as qty_kirim")
        // ,db::raw("(coalesce((select sum(qty) from delivery_det a
        // left join delivery_hdr b on a.delivery_number=b.delivery_number 
        // where a.so_number = sales_order_hdr.so_code and a.article_code = sales_order_det.article_code 
        // and status <> '5' group by article_code),0)-sales_order_det.qty) as balance")
        // ,db::raw("case when sales_order_det.status = '0' then 0 else (coalesce((select sum(qty) from delivery_det a
        // left join delivery_hdr b on a.delivery_number=b.delivery_number 
        // where a.so_number = sales_order_hdr.so_code and a.article_code = sales_order_det.article_code 
        // and b.status not in ('5','7')  group by article_code),0)-sales_order_det.qty) end as balance")
        // ,'sales_order_hdr.status as statusKu'
        // ,'uom_group'
        // ,'qty_target'
        // ,'qty_forcast'
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_target,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_target,'999,999,999.999') end as qty_target")
        //,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_forcast,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_forcast,'999,999,999.999') end as qty_forcast")
        ,DB::RAW("to_date(so_date,'dd-mm-yyyy') as date_period")
        ,'sales_order_hdr.note'
        )
        // ->where(db::raw("case when sales_order_det.status = '0' then 0 else (coalesce((select sum(qty) from delivery_det a
        // left join delivery_hdr b on a.delivery_number=b.delivery_number 
        // where a.so_number = sales_order_hdr.so_code and a.article_code = sales_order_det.article_code 
        // and b.status not in ('5','7')  group by article_code),0)-sales_order_det.qty) end"),'!=',0)
        ->where('article.article_desc',"<>",'')
        // ->orderBy('sales_order_det.id')
        ->get(); 

        // // return DB::table('article')
        // // ->leftJoin('article_stock','article_stock.article_code','article.article_code')
        // // ->select('article_alternative_code','article_desc','article_stock.article_qty')
        // // ->orderBy('article.article_code')
        // // ->get();
        // $soNumber = $this->soNumber;
        // // $soNumber = 'SO/ASN/22/12/2571';

        // $results = DB::select("SELECT a.article_code, c.article_alternative_code, c.article_desc,a.delivery_number
        // , b.delivery_date,a.qty
        // ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
        // ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code)) as qty_delivery
        // ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code) as sisa_so
        // from delivery_det a 
        // left join delivery_hdr b on b.delivery_number = a.delivery_number
        // left join article c on c.article_code = a.article_code
        // where a.so_number = '$soNumber' 
        // order by a.article_code,b.delivery_date");

        $barisIsiJudul='';
        $barisAll='';
        $jumlahBaris=0;

        $barisHeader = "<tr>
                        <td>Customer Code</td>
                        <td>Customer</td>
                        <td>No PO</td>
                        <td>No SO</td>
                        <td>Tanggal SO</td>
                        <td>Article code</td>
                        <td>Article desc</td>
                        <td>Qty SO</td>
                        <td>Pengiriman</td>
                        <td>Sisa Order</td>
                        <td>Note</td>
                        <td>Date Period</td>
                        </tr>";

        foreach($results as $val){
            $balance = 0;
            if($val->status_det == 0){
                $balance = 0;
            }else{
                $balance = $val->qty_kirim-$val->qty_det;    
            }

                $barisIsi = "<tr>
                <td>$val->customer_code</td>
                <td>$val->customer</td>
                <td>$val->po_number</td>
                <td>$val->so_code</td>
                <td>$val->so_date</td>
                <td>$val->article_alternative_code</td>
                <td>$val->article_desc</td>
                <td>$val->qty</td>
                <td>$val->qty_kirim</td>
                <td>$balance</td>
                <td>$val->note</td>
                <td>$val->date_period</td>
            </tr>";
            if($balance <> 0){
                $barisAll .= $barisIsi;
            }
        }

        $barisAll = $barisHeader.$barisAll;
              
        $data['barisDetail']=$barisAll;       
        return view('salesOrder.exportToExcel', $data);

        // return collect($results); 
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

    // public function headings(): array
    // {
    //     return ["Customer Code", "Customer", "No PO", "No SO", "Tanggal SO", "Article code", "Article desc", "Qty SO", "Pengiriman", "Sisa Order", "Note", "date_period"];
    // }

    // public function title(): string
    // {
    //     return 'report_so';
    // }
}