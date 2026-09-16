<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class ConversionReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithStyles
{
    protected $rows;

    /** @param array $rows baris hasil ConversionReportController::buildSummary()['rows'] */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function collection(): Collection
    {
        return collect($this->rows)->values()->map(function ($r, $i) {
            return [
                $i + 1,
                $r['article_alternative_code'] ?? $r['article_code'],
                $r['article_desc'] ?? '',
                $r['customer_names'] ?? '',
                $r['uom'] ?? '',
                $r['total_qty'] ?? 0,
                $r['avg_selling_price'] ?? 0,
                $r['avg_purchase_price'] ?? 0,
                $r['conversion'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No', 'Article Code', 'Article Desc', 'Customer', 'UOM',
            'Qty', 'Avg Selling Price', 'Avg Purchase Price', 'Conversion',
        ];
    }

    public function title(): string
    {
        return 'Conversion Report';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2F5496'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
