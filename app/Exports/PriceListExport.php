<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;
use DB;

// ═══════════════════════════════════════════════════════════
//  MAIN EXPORT — MultipleSheets wrapper
// ═══════════════════════════════════════════════════════════
class PriceListExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new PriceListTemplateSheet(),
            new PriceListArticleMasterSheet(),
        ];
    }
}

// ═══════════════════════════════════════════════════════════
//  SHEET 1 — Template input (kosong, siap diisi user)
// ═══════════════════════════════════════════════════════════
class PriceListTemplateSheet implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithTitle,
    WithStyles,
    WithColumnWidths
{
    public function collection(): Collection
    {
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'article_code',   // Article Alternative Code
            'sales_price',
        ];
    }

    public function title(): string
    {
        return 'template';
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

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // article_code
            'B' => 18,  // sales_price
        ];
    }
}

// ═══════════════════════════════════════════════════════════
//  SHEET 2 — Master artikel FG (referensi untuk user)
// ═══════════════════════════════════════════════════════════
class PriceListArticleMasterSheet implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithTitle,
    WithStyles
{
    public function collection(): Collection
    {
        return DB::table('article')
            ->where('article_type', 'FG')
            ->orderBy('article_alternative_code')
            ->get(['article_alternative_code', 'article_desc'])
            ->map(fn($row) => [
                'article_code' => $row->article_alternative_code,
                'article_desc' => $row->article_desc,
            ]);
    }

    public function headings(): array
    {
        return [
            'article_code',
            'article_desc',
        ];
    }

    public function title(): string
    {
        return 'master_article';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF375623'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}