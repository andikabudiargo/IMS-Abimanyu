<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

use DB;

class StockTakeExport implements FromCollection, WithHeadings,ShouldAutoSize,WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */

    public function collection()
    {
        return DB::table('article')
        ->leftJoin('article_stock','article_stock.article_code','article.article_code')
        ->select('article_alternative_code','article_desc','article_stock.article_qty')
        ->orderBy('article.article_code')
        ->get();
    }

    public function headings(): array
    {
        return ["Article_code", "article_desc","QTY"];
    }

    public function title(): string
    {
        return 'Stock';
    }
}