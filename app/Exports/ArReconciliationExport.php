<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ArReconciliationExport implements FromArray, WithTitle, WithColumnWidths, WithEvents
{
    protected $rows;
    protected $totals;
    protected $openingBalance;
    protected $closingBalance;
    protected $year;
    protected $basis;

    const TOTAL_COLS = 6;

    public function __construct(array $rows, array $totals, $openingBalance, $closingBalance, $year, $basis)
    {
        $this->rows           = $rows;
        $this->totals         = $totals;
        $this->openingBalance = $openingBalance;
        $this->closingBalance = $closingBalance;
        $this->year           = $year;
        $this->basis          = $basis;
    }

    public function title(): string
    {
        return 'AR Reconciliation';
    }

    public function array(): array
    {
        $additionLabel = $this->basis === 'delivery' ? 'Delivery Note' : 'AR Invoice';

        $out = [];
        $out[] = ['AR RECONCILIATION'];
        $out[] = ['Tahun: ' . $this->year . ' | Basis: ' . $additionLabel];
        $out[] = [];
        $out[] = ['Periode', 'DPP', $additionLabel, 'Debit Note', 'Pengurang (Bank/Kas)', 'Balance'];
        $out[] = ['Saldo Awal', '', '', '', '', $this->openingBalance];

        foreach ($this->rows as $r) {
            $out[] = [$r['label'], $r['dpp'], $r['addition'], $r['debitNote'], $r['payment'], $r['balance']];
        }

        $out[] = ['TOTAL', $this->totals['dpp'], $this->totals['addition'], $this->totals['debitNote'], $this->totals['payment'], $this->closingBalance];

        return $out;
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 18, 'C' => 18, 'D' => 18, 'E' => 20, 'F' => 18];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(self::TOTAL_COLS);

                $headerRow = 4;
                $dataStart = 5;
                $grandRow  = $dataStart + count($this->rows) + 1;

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->getStyle('A2')->getFont()->setBold(true);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                    'font'      => ['bold' => true],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F7']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A{$headerRow}:{$lastCol}{$grandRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('C9D2DF');

                $sheet->getStyle("A{$grandRow}:{$lastCol}{$grandRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9EDF3']],
                ]);

                foreach (['B', 'C', 'D', 'E', 'F'] as $c) {
                    $sheet->getStyle("{$c}{$dataStart}:{$c}{$grandRow}")
                        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("{$c}{$dataStart}:{$c}{$grandRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }
}
