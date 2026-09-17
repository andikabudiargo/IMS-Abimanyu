<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ArticleBulkUpdateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    private $columns;

    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return array_merge(['article_code'], $this->columns);
    }
}
