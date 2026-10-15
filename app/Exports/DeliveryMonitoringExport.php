<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DeliveryMonitoringExport implements WithMultipleSheets
{
    private $groups;

    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->groups as $kode => $g) {
            $sheets[] = new DeliveryMonitoringSheetExport($g, (string) $kode);
        }
        return $sheets ?: [new DeliveryMonitoringSheetExport(['articles' => [], 'dates' => [], 'matrix' => []], 'Sheet1')];
    }
}

class DeliveryMonitoringSheetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    private $g;
    private $title;

    public function __construct(array $g, string $title)
    {
        $this->g = $g;
        $this->title = substr(preg_replace('/[^A-Za-z0-9 _-]/', '', $title) ?: 'Sheet', 0, 31);
    }

    private function hasCustomerCol(): bool
    {
        return !empty($this->g['rowCustomer']);
    }

    public function array(): array
    {
        $rows = [];
        $remarks = ['plan' => 'Plan', 'outstanding' => 'Outstanding', 'matrix' => 'Actual', 'balance' => 'Balance'];
        $qtyFor = function ($remarkKey, $plan, $actual, $outstanding) {
            return $remarkKey == 'plan' ? $plan : ($remarkKey == 'matrix' ? $actual : ($remarkKey == 'balance' ? $actual - ($plan + $outstanding) : $outstanding));
        };
        foreach ($this->g['articles'] as $code => $name) {
            foreach ($remarks as $remarkKey => $remarkLabel) {
                $isFirst = $remarkKey == 'plan';
                $row = $this->hasCustomerCol()
                    ? [$isFirst ? ($this->g['rowCustomer'][$code] ?? '') : '', $isFirst ? $name : '']
                    : [$isFirst ? $name : ''];
                $row[] = $remarkLabel;
                $total = 0;
                foreach ($this->g['dates'] as $d) {
                    $plan = $this->g['plan'][$code][$d] ?? 0;
                    $actual = $this->g['matrix'][$code][$d] ?? 0;
                    $outstanding = $this->g['outstanding'][$code][$d] ?? 0;
                    $qty = $qtyFor($remarkKey, $plan, $actual, $outstanding);
                    $row[] = $qty;
                    $total += $qty;
                }
                $row[] = $total;
                $row[] = $isFirst ? round($this->g['totalKonversi'][$code] ?? 0, 8) : '';
                $rows[] = $row;
            }
        }
        if ($rows) {
            foreach ($remarks as $remarkKey => $remarkLabel) {
                $isFirst = $remarkKey == 'plan';
                $totalRow = $this->hasCustomerCol() ? ['', $isFirst ? 'TOTAL' : ''] : [$isFirst ? 'TOTAL' : ''];
                $totalRow[] = $remarkLabel;
                $grand = 0;
                foreach ($this->g['dates'] as $d) {
                    $planTotal = array_sum(array_column($this->g['plan'], $d));
                    $actualTotal = array_sum(array_column($this->g['matrix'], $d));
                    $outstandingTotal = array_sum(array_column($this->g['outstanding'] ?? [], $d));
                    $colTotal = $qtyFor($remarkKey, $planTotal, $actualTotal, $outstandingTotal);
                    $totalRow[] = $colTotal;
                    $grand += $colTotal;
                }
                $totalRow[] = $grand;
                $totalRow[] = $isFirst ? round(array_sum($this->g['totalKonversi'] ?? []), 8) : '';
                $rows[] = $totalRow;
            }
        }
        return $rows;
    }

    public function headings(): array
    {
        $head = $this->hasCustomerCol() ? ['Customer', 'Article'] : ['Article'];
        $head[] = 'Remarks';
        return array_merge($head, $this->g['dates'], ['Total', 'Total Konversi']);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $articleCol = $this->hasCustomerCol() ? 'B' : 'A';
                // kolom sebelum tanggal: [Customer,]Article,Remarks lalu +2 untuk Total & Total Konversi di akhir
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                    count($this->g['dates']) + ($this->hasCustomerCol() ? 5 : 4)
                );
                $blockCount = count($this->g['articles']) + ($this->g['articles'] ? 1 : 0);
                for ($i = 0; $i < $blockCount; $i++) {
                    $startRow = 2 + $i * 4;
                    $endRow = $startRow + 3;
                    if ($this->hasCustomerCol()) {
                        $sheet->mergeCells("A{$startRow}:A{$endRow}");
                    }
                    $sheet->mergeCells("{$articleCol}{$startRow}:{$articleCol}{$endRow}");
                    $sheet->mergeCells("{$lastCol}{$startRow}:{$lastCol}{$endRow}");
                }
            },
        ];
    }
}
