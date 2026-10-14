<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

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

class DeliveryMonitoringSheetExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
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
        $remarks = ['plan' => 'Plan', 'matrix' => 'Actual', 'balance' => 'Balance'];
        foreach ($this->g['articles'] as $code => $name) {
            foreach ($remarks as $remarkKey => $remarkLabel) {
                $row = $this->hasCustomerCol() ? [$this->g['rowCustomer'][$code] ?? '', $name] : [$name];
                $row[] = $remarkLabel;
                $total = 0;
                foreach ($this->g['dates'] as $d) {
                    $plan = $this->g['plan'][$code][$d] ?? 0;
                    $actual = $this->g['matrix'][$code][$d] ?? 0;
                    $qty = $remarkKey == 'plan' ? $plan : ($remarkKey == 'matrix' ? $actual : $actual - $plan);
                    $row[] = $qty;
                    $total += $qty;
                }
                $row[] = $total;
                $rows[] = $row;
            }
        }
        if ($rows) {
            foreach ($remarks as $remarkKey => $remarkLabel) {
                $totalRow = $this->hasCustomerCol() ? ['', 'TOTAL'] : ['TOTAL'];
                $totalRow[] = $remarkLabel;
                $grand = 0;
                foreach ($this->g['dates'] as $d) {
                    $planTotal = array_sum(array_column($this->g['plan'], $d));
                    $actualTotal = array_sum(array_column($this->g['matrix'], $d));
                    $colTotal = $remarkKey == 'plan' ? $planTotal : ($remarkKey == 'matrix' ? $actualTotal : $actualTotal - $planTotal);
                    $totalRow[] = $colTotal;
                    $grand += $colTotal;
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
}
