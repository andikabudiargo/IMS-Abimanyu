<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ApPaymentScheduleExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected $rows;
    protected $grand;
    protected $daysInMonth;
    protected $periodStart;

    public function __construct(array $rows, array $grand, int $daysInMonth, string $periodStart)
    {
        $this->rows        = $rows;
        $this->grand       = $grand;
        $this->daysInMonth = $daysInMonth;
        $this->periodStart = $periodStart;
    }

    // Supplier + Opening + hari-hari + Total/Paid/Balance/Outstanding
    private function lastColumnIndex(): int
    {
        return 2 + $this->daysInMonth + 4;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->rows as $r) {
            $line = [$r['supplier_name'], $r['opening']];
            for ($d = 1; $d <= $this->daysInMonth; $d++) {
                $line[] = $r['days']['d' . $d];
            }
            $line[] = $r['total'];
            $line[] = $r['paid'];
            $line[] = $r['balance'];
            $line[] = $r['outstanding'];
            $data[] = $line;
        }

        $grandLine = ['GRAND TOTAL', $this->grand['opening']];
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $grandLine[] = $this->grand['d' . $d];
        }
        $grandLine[] = $this->grand['total'];
        $grandLine[] = $this->grand['paid'];
        $grandLine[] = $this->grand['balance'];
        $grandLine[] = $this->grand['outstanding'];
        $data[] = $grandLine;

        return $data;
    }

    public function headings(): array
    {
        $h = ['Supplier', 'Opening'];
        for ($d = 1; $d <= $this->daysInMonth; $d++) {
            $h[] = (string) $d;
        }
        $h[] = 'Total';
        $h[] = 'Paid';
        $h[] = 'Balance';
        $h[] = 'Outstanding';
        return $h;
    }

    public function title(): string
    {
        return 'AP Payment Schedule ' . $this->periodStart;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->rows) + 2;
        $firstNumCol = Coordinate::stringFromColumnIndex(2);
        $lastCol     = Coordinate::stringFromColumnIndex($this->lastColumnIndex());

        $sheet->getStyle("{$firstNumCol}2:{$lastCol}{$lastRow}")
            ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastCol}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('EEF2F7');

        $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRow}:{$lastCol}{$lastRow}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E9EDF3');

        return [];
    }
}
