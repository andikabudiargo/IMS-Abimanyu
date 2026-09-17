<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Sheet referensi kode COA + nama COA, hanya disertakan kalau kolom COA
 * dicentang di form Bulk Update Article.
 */
class ArticleBulkUpdateCoaRefSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    protected $accounts;

    public function __construct($accounts)
    {
        $this->accounts = $accounts;
    }

    public function title(): string
    {
        return 'Referensi COA';
    }

    public function headings(): array
    {
        return ['COA Code', 'COA Name'];
    }

    public function array(): array
    {
        return $this->accounts
            ->map(fn ($a) => [$a->account, $a->description])
            ->all();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:B1')->getFont()->setBold(true);
            },
        ];
    }
}
