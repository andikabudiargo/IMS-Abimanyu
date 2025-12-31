<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

use DB;

class TransferInExport implements WithMultipleSheets
{
    protected $thirdPartyId;
    
    public function __construct($thirdPartyId = null)
    {
        $this->thirdPartyId = $thirdPartyId;
    }

    public function sheets(): array
    {
        return [
            'Sheet1' => new FirstSheetExport($this->thirdPartyId),
            'Sheet2' => new SecondSheetExport(),
        ];
    }
}

class FirstSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    protected $thirdPartyId;

    public function __construct($thirdPartyId = null)
    {
        $this->thirdPartyId = $thirdPartyId;
    }

    public function collection()
    {
        $thircPartyId = $this->thirdPartyId;

        $data= DB::table('article') 
            ->whereIn('article.article_code', function($query) use ($thircPartyId) {
                $query->select('article_code')
                ->from('article_supplier') 
                ->where('supplier_code',$thircPartyId);
            })
            ->orderBy('article_alternative_code')
            ->select('article_alternative_code as article_code','article_desc as article_desc',db::raw("'' as location_code"),db::raw("'' as qty"))
            ->get();
        return $data;

        // return DB::table('import_stock_take_tmp')
        //     ->where('file_name', 'okihartantokeren')
        //     ->get(['article_code', 'location_code', 'qty']);
    }

    public function headings(): array
    {
        return ["article_code","article_desc","location_code", "qty"];
    }

    public function title(): string
    {
        return 'article';
    }
}

class SecondSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{

    public function collection()
    {
        // You can add different data for the second sheet
        return DB::table('goods_location_master')
            ->orderBy('location_name')
            ->get(['location_name', 'location_code']);
    }

    public function headings(): array
    {
        return ["location_name", "location_code"];
    }

    public function title(): string
    {
        return 'master_location';
    }
}