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
        return ['No', 'Article Code', 'Article Desc', 'Max FG (info)', 'Qty Fresh', 'Qty Repaint', 'Note'];
    }

    public function array(): array
{
    $rows = [];
    $no   = 0;

    foreach ($this->articles as $val) {
        $no++;
        $maxFg = isset($val->max_fg) ? round((float) $val->max_fg, 2) : 0;

        $rows[] = [$no, $val->article_alternative_code, $val->article_desc, $maxFg, null, null, null];
    }

    if (empty($rows)) {
        $rows[] = [1, '', '', '', '', '', ''];
    }

    return $rows;
}

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_00,
            'E' => NumberFormat::FORMAT_NUMBER_00,
            'F' => NumberFormat::FORMAT_NUMBER_00,
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
                $sheet->getStyle('A1:G1')->getFont()->setBold(true);

                if ($this->boothName) {
                    $sheet->setCellValue('I1', 'Spray Booth:');
                    $sheet->setCellValue('J1', $this->boothName . ' (' . $this->sprayBooth . ')');
                    $sheet->getStyle('I1')->getFont()->setBold(true);
                }
            },
        ];
    }
}