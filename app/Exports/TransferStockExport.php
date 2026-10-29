<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

// Transfer Stock only needs article_code and qty; location is chosen on the form, not the template.
class TransferStockExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle
{
    public function collection()
    {
        return collect();
    }

    public function headings(): array
    {
        return ["article_code", "qty"];
    }

    public function title(): string
    {
        return 'article';
    }
}
