<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DeliveryScheduleTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        // schedule_date format: DD-MM-YYYY (text, not an Excel date cell)
        return ['so_code', 'article_code', 'schedule_date', 'qty'];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->rows as $row) {
            $rows[] = [$row->so_code, $row->article_code, $row->schedule_date, ''];
        }
        return $rows;
    }
}
