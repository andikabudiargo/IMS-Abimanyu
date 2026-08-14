<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ActualFinishGoodsExport implements FromArray, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    protected $articles;
    protected $location;
    protected $locationName;

    public function __construct($articles, $location = null, $locationName = null)
    {
        $this->articles     = $articles;
        $this->location     = $location;
        $this->locationName = $locationName;
    }

    public function headings(): array
    {
        return [
            'No', 'Article Code', 'Article Desc',
            'Hasil Loading (info)', 'Stock WIP (info)',
            'Qty FG', 'Qty OT', 'Note',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no   = 0;

        foreach ($this->articles as $val) {
            $no++;

            $stockLoading = is_numeric($val->stock_loading ?? null) ? round((float) $val->stock_loading, 2) : 0;
            $stockWip     = is_numeric($val->stock_wip     ?? null) ? round((float) $val->stock_wip, 2)     : 0;

            $rows[] = [$no, $val->article_alternative_code, $val->article_desc, $stockLoading, $stockWip, null, null, null];
        }

        if (empty($rows)) {
            $rows[] = [1, '', '', '', '', '', '', ''];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
            'G' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $alphabet = $event->sheet->getHighestDataColumn();
                $totalRow = $event->sheet->getHighestDataRow();
                $sheet->getStyle('A1:' . $alphabet . $totalRow)->getFont()->setSize(10);
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);

                if ($this->locationName) {
                    $sheet->setCellValue('J1', 'Location:');
                    $sheet->setCellValue('K1', $this->locationName . ' (' . $this->location . ')');
                    $sheet->getStyle('J1')->getFont()->setBold(true);
                }
            },
        ];
    }
}