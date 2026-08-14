<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ActualLoadingTemplateSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    protected $articles;
    protected $sprayBooth;
    protected $boothName;

    public function __construct($articles, $sprayBooth = null, $boothName = null)
    {
        $this->articles   = $articles;
        $this->sprayBooth = $sprayBooth;
        $this->boothName  = $boothName;
    }

    public function title(): string
    {
        return 'Input Loading';
    }

    public function headings(): array
    {
        return [
            'No', 'Article Code', 'Article Desc',
            'Stock RM Booth (info)', 'Stock WIP (info)',
            'Qty Fresh', 'Qty Repaint', 'Note',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no   = 0;

        foreach ($this->articles as $val) {
            $no++;

            $stockRm  = is_numeric($val->stock_rm_fresh   ?? null) ? round((float) $val->stock_rm_fresh, 2)   : 0;
            $stockWip = is_numeric($val->stock_fg_repaint ?? null) ? round((float) $val->stock_fg_repaint, 2) : 0;

            $rows[] = [$no, $val->article_alternative_code, $val->article_desc, $stockRm, $stockWip, null, null, null];
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

                $alphabet  = $event->sheet->getHighestDataColumn();
                $totalRow  = $event->sheet->getHighestDataRow();
                $sheet->getStyle('A1:' . $alphabet . $totalRow)->getFont()->setSize(10);
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);

                if ($this->boothName) {
                    $sheet->setCellValue('J1', 'Spray Booth:');
                    $sheet->setCellValue('K1', $this->boothName . ' (' . $this->sprayBooth . ')');
                    $sheet->getStyle('J1')->getFont()->setBold(true);
                }
            },
        ];
    }
}