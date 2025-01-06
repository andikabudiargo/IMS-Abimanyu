<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

use DB;

class SafetyStockExport implements FromCollection, WithHeadings,ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
        return DB::table('import_stock_take_tmp')
        ->where('file_name','okihartantokeren')
        ->get();
    }

    public function headings(): array
    {
        return ["article_code","safety_stock"];
    }
}