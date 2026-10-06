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

class ConversionReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithStyles, \Maatwebsite\Excel\Concerns\WithColumnFormatting
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
            $qty          = $r['total_qty'] ?? 0;
            $avgSelling   = $r['avg_selling_price'] ?? 0;
            $avgPurchase  = $r['avg_purchase_price'] ?? 0;
            $totalSelling = $r['total_selling_value']  ?? ($avgSelling * $qty);
            $totalPurch   = $r['total_purchase_value'] ?? ($avgPurchase * $qty);
            $isPainting   = $r['is_painting'] ?? in_array(strtoupper(trim($r['uom'] ?? '')), ['PCS', 'SET']);
            $conversion   = $r['conversion'] ?? 0;
            $qtyTarget    = $r['qty_target'] ?? 0;
            $targetConv   = $r['target_conversion'] ?? 0;
            $convPerUnit  = $r['conversion_per_unit'] ?? 0;
            $qtySelisih   = $r['qty_selisih'] ?? ($qty - $qtyTarget);

            return [
                $i + 1,
                $r['article_alternative_code'] ?? $r['article_code'],
                $r['article_desc'] ?? '',
                $r['customer_names'] ?? '',
                $r['uom'] ?? '',
                $qty,
                $qtyTarget,
                $qtySelisih,
                $qtyTarget > 0 ? round($qty / $qtyTarget, 4) : '',
                $qtyTarget > 0 ? round($qtySelisih / $qtyTarget, 4) : '',
                $avgSelling,
                $avgPurchase,
                $totalSelling,
                $totalPurch,
                $convPerUnit,
                $isPainting ? 0 : $conversion,
                $isPainting ? $conversion : 0,
                $targetConv,
                $r['selisih_conversion'] ?? (($isPainting ? $conversion : 0) - $targetConv),
                isset($r['pct_tercapai']) ? $r['pct_tercapai'] / 100 : '',
                isset($r['pct_selisih']) ? $r['pct_selisih'] / 100 : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No', 'Article Code', 'Article Desc', 'Customer', 'UOM',
            'Qty', 'Qty Target', 'Qty Selisih', '% Qty Tercapai', '% Qty Selisih', 'Avg Selling Price', 'Avg Purchase Price',
            'Total Selling (Qty x Avg)', 'Total Purchase (Qty x Avg)',
            'Harga Konversi /PCS', 'Konversi Non Painting', 'Konversi Painting', 'Konversi Target',
            'Selisih', '% Tercapai', '% Selisih',
        ];
    }

    public function columnFormats(): array
    {
        return ['O' => '0.00000000', 'I' => '0.00%', 'J' => '0.00%', 'T' => '0.00%', 'U' => '0.00%']; // Harga Konversi /PCS
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
