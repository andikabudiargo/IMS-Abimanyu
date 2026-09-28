<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ArticleBulkUpdateTemplateSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    protected $columns;
    protected $cashflowCategoryValues;

    public function __construct(array $columns, array $cashflowCategoryValues = [])
    {
        $this->columns = $columns;
        $this->cashflowCategoryValues = $cashflowCategoryValues;
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

                $cfIndex = array_search('cashflow_category', $this->headings());
                if ($cfIndex !== false && !empty($this->cashflowCategoryValues)) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cfIndex + 1);
                    $list = '"' . implode(',', $this->cashflowCategoryValues) . '"';

                    for ($row = 2; $row <= 1000; $row++) {
                        $validation = $event->sheet->getDelegate()->getCell("{$col}{$row}")->getDataValidation();
                        $validation->setType(DataValidation::TYPE_LIST);
                        $validation->setErrorStyle(DataValidation::STYLE_STOP);
                        $validation->setAllowBlank(true);
                        $validation->setShowDropDown(true);
                        $validation->setShowErrorMessage(true);
                        $validation->setErrorTitle('Nilai tidak valid');
                        $validation->setError('Pilih salah satu dari: ' . implode(', ', $this->cashflowCategoryValues));
                        $validation->setFormula1($list);
                    }
                }
            },
        ];
    }
}
