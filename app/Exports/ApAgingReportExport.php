<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ApAgingReportExport implements FromArray, WithHeadings, WithStyles, WithTitle, WithColumnFormatting, ShouldAutoSize
{
    protected $rows;
    protected $grand;
    protected $cutoffDate;

    public function __construct(array $rows, array $grand, string $cutoffDate)
    {
        $this->rows       = $rows;
        $this->grand      = $grand;
        $this->cutoffDate = $cutoffDate;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->rows as $r) {
            $data[] = [
                $r['supplier_name'],
                $r['belum_jatuh_tempo'],
                $r['d1_30'],
                $r['d31_60'],
                $r['d61_90'],
                $r['d90plus'],
                $r['total_overdue'],
                $r['total_hutang'],
            ];
        }

        $data[] = [
            'GRAND TOTAL',
            $this->grand['belum_jatuh_tempo'],
            $this->grand['d1_30'],
            $this->grand['d31_60'],
            $this->grand['d61_90'],
            $this->grand['d90plus'],
            $this->grand['total_overdue'],
            $this->grand['total_hutang'],
        ];

        return $data;
    }

    public function headings(): array
    {
        return [
            'Supplier',
            'Belum Jatuh Tempo',
            '1 - 30 Hari',
            '31 - 60 Hari',
            '61 - 90 Hari',
            '> 90 Hari',
            'Total Overdue',
            'Total Hutang',
        ];
    }

    public function title(): string
    {
        return 'AP Aging ' . $this->cutoffDate;
    }

    public function columnFormats(): array
    {
        $fmt = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

        return [
            'B' => $fmt, 'C' => $fmt, 'D' => $fmt,
            'E' => $fmt, 'F' => $fmt, 'G' => $fmt, 'H' => $fmt,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->rows) + 2;

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('EEF2F7');

        $sheet->getStyle('A' . $lastRow . ':H' . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $lastRow . ':H' . $lastRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E9EDF3');

        return [];
    }
}
