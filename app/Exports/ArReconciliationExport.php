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

    const TOTAL_COLS_WITH_DN = 6;
    const TOTAL_COLS_NO_DN   = 5;

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

    private function showDebitNote(): bool
    {
        return $this->basis !== 'delivery';
    }

    public function array(): array
    {
        $additionLabel = $this->basis === 'delivery' ? 'Delivery Note' : 'AR Invoice';
        $showDn = $this->showDebitNote();

        $out = [];
        $out[] = ['AR RECONCILIATION'];
        $out[] = ['Tahun: ' . $this->year . ' | Basis: ' . $additionLabel];
        $out[] = [];
        $out[] = $showDn
            ? ['Periode', 'DPP', $additionLabel, 'Debit Note', 'Pengurang (Bank/Kas)', 'Balance']
            : ['Periode', 'DPP', $additionLabel, 'Pengurang (Bank/Kas)', 'Balance'];
        $out[] = $showDn ? ['Saldo Awal', '', '', '', '', $this->openingBalance] : ['Saldo Awal', '', '', '', $this->openingBalance];

        foreach ($this->rows as $r) {
            $out[] = $showDn
                ? [$r['label'], $r['dpp'], $r['addition'], $r['debitNote'], $r['payment'], $r['balance']]
                : [$r['label'], $r['dpp'], $r['addition'], $r['payment'], $r['balance']];
        }

        $out[] = $showDn
            ? ['TOTAL', $this->totals['dpp'], $this->totals['addition'], $this->totals['debitNote'], $this->totals['payment'], $this->closingBalance]
            : ['TOTAL', $this->totals['dpp'], $this->totals['addition'], $this->totals['payment'], $this->closingBalance];

        return $out;
    }

    public function columnWidths(): array
    {
        return $this->showDebitNote()
            ? ['A' => 20, 'B' => 18, 'C' => 18, 'D' => 18, 'E' => 20, 'F' => 18]
            : ['A' => 20, 'B' => 18, 'C' => 18, 'D' => 20, 'E' => 18];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex($this->showDebitNote() ? self::TOTAL_COLS_WITH_DN : self::TOTAL_COLS_NO_DN);

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

                $numberCols = $this->showDebitNote() ? ['B', 'C', 'D', 'E', 'F'] : ['B', 'C', 'D', 'E'];
                foreach ($numberCols as $c) {
                    $sheet->getStyle("{$c}{$dataStart}:{$c}{$grandRow}")
                        ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    $sheet->getStyle("{$c}{$dataStart}:{$c}{$grandRow}")
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            },
        ];
    }
}
