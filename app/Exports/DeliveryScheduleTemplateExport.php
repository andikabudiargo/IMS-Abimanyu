<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DeliveryScheduleTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected $articles;

    public function __construct($articles)
    {
        $this->articles = $articles;
    }

    public function headings(): array
    {
        // schedule_date format: DD-MM-YYYY (text, not an Excel date cell)
        return ['article_code', 'schedule_date', 'qty'];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->articles as $article) {
            $rows[] = [$article->article_code, '', ''];
        }
        return $rows;
    }
}
