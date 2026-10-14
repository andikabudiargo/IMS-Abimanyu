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
        $remarks = ['plan' => 'Plan', 'matrix' => 'Actual', 'balance' => 'Balance', 'cumulative' => 'Saldo Kumulatif'];
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
                    $cumBalance = $this->g['cumulative'][$code][$d] ?? 0;
                    $qty = $remarkKey == 'plan' ? $plan : ($remarkKey == 'matrix' ? $actual : ($remarkKey == 'cumulative' ? $cumBalance : $actual - $plan));
                    $row[] = $qty;
                    $total = $remarkKey == 'cumulative' ? $cumBalance : $total + $qty;
                }
                $row[] = $total;
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
                    $cumTotal = array_sum(array_column($this->g['cumulative'] ?? [], $d));
                    $colTotal = $remarkKey == 'plan' ? $planTotal : ($remarkKey == 'matrix' ? $actualTotal : ($remarkKey == 'cumulative' ? $cumTotal : $actualTotal - $planTotal));
                    $totalRow[] = $colTotal;
                    $grand = $remarkKey == 'cumulative' ? $cumTotal : $grand + $colTotal;
                }
                $totalRow[] = $grand;
                $rows[] = $totalRow;
            }
        }
        return $rows;
    }

    public function headings(): array
    {
        $head = $this->hasCustomerCol() ? ['Customer', 'Article'] : ['Article'];
        $head[] = 'Remarks';
        return array_merge($head, $this->g['dates'], ['Total']);
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
                $blockCount = count($this->g['articles']) + ($this->g['articles'] ? 1 : 0);
                for ($i = 0; $i < $blockCount; $i++) {
                    $startRow = 2 + $i * 4;
                    $endRow = $startRow + 3;
                    if ($this->hasCustomerCol()) {
                        $sheet->mergeCells("A{$startRow}:A{$endRow}");
                    }
                    $sheet->mergeCells("{$articleCol}{$startRow}:{$articleCol}{$endRow}");
                }
            },
        ];
    }
}
