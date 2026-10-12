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

    public function array(): array
    {
        $rows = [];
        foreach ($this->g['articles'] as $code => $name) {
            $row = [$name];
            $total = 0;
            foreach ($this->g['dates'] as $d) {
                $qty = $this->g['matrix'][$code][$d] ?? 0;
                $row[] = $qty;
                $total += $qty;
            }
            $row[] = $total;
            $rows[] = $row;
        }
        if ($rows) {
            $totalRow = ['TOTAL'];
            $grand = 0;
            foreach ($this->g['dates'] as $d) {
                $colTotal = array_sum(array_column($this->g['matrix'], $d));
                $totalRow[] = $colTotal;
                $grand += $colTotal;
            }
            $totalRow[] = $grand;
            $rows[] = $totalRow;
        }
        return $rows;
    }

    public function headings(): array
    {
        return array_merge(['Article'], $this->g['dates'], ['Total']);
    }

    public function title(): string
    {
        return $this->title;
    }
}
