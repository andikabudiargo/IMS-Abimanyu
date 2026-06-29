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
class StockAdjustmentExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new StockAdjustmentTemplateSheet(),
            new StockAdjustmentArticleMasterSheet(),
        ];
    }
}

// ═══════════════════════════════════════════════════════════
//  SHEET 1 — Template input (kosong, siap diisi user)
// ═══════════════════════════════════════════════════════════
class StockAdjustmentTemplateSheet implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithTitle,
    WithStyles,
    WithColumnWidths
{
    public function collection(): Collection
    {
        // Kembalikan collection kosong — user yang isi
        return collect([]);
    }

    public function headings(): array
    {
        return [
            'article_code',     // Article Alternative Code (kode yang mudah dibaca)
            'qty_adjustment',   // Selalu positif; direction ditentukan di header form
            'notes',            // Opsional — keterangan per baris
        ];
    }

    public function title(): string
    {
        return 'template';
    }

    public function styles(Worksheet $sheet): array
    {
        // Header row styling: background biru gelap, teks putih, bold
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
            'B' => 18,  // qty_adjustment
            'C' => 35,  // notes
        ];
    }
}

// ═══════════════════════════════════════════════════════════
//  SHEET 2 — Master artikel (referensi untuk user)
// ═══════════════════════════════════════════════════════════
class StockAdjustmentArticleMasterSheet implements
    FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithTitle,
    WithStyles
{
    public function collection(): Collection
    {
        return DB::table('article')
            ->where('status', 1)
            ->orderBy('article_alternative_code')
            ->get(['article_alternative_code', 'article_desc', 'uom'])
            ->map(fn($row) => [
                'article_code' => $row->article_alternative_code,
                'article_desc' => $row->article_desc,
                'uom'          => $row->uom,
            ]);
    }

    public function headings(): array
    {
        return [
            'article_code',
            'article_desc',
            'uom',
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