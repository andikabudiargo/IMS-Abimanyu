<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ArticleBulkUpdateTemplateSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    protected $columns;

    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public function title(): string
    {
        return 'Template';
    }

    public function headings(): array
    {
        return array_merge(['article_code'], $this->columns);
    }

    public function array(): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $lastCol = $event->sheet->getHighestDataColumn();
                $event->sheet->getDelegate()->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
            },
        ];
    }
}
