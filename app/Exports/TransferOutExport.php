<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

use DB;

class TransferOutExport implements WithMultipleSheets
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

    public function collection()
    {

        return DB::table('import_stock_take_tmp')
        ->where('file_name','okihartantokeren')
        ->get();

    }

    public function headings(): array
    {
        return ["article_code","location_code", "qty"];
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

// class TransferOutExport implements FromCollection, WithHeadings,ShouldAutoSize
// {
//     /**
//     * @return \Illuminate\Support\Collection
//     */

//     public function collection()
//     {
//         return DB::table('import_stock_take_tmp')
//         ->where('file_name','okihartantokeren')
//         ->get();
//     }

//     public function headings(): array
//     {
//         return ["article_code","QTY"];
//     }
// }