<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Panduan pengisian kolom Cashflow Category, hanya disertakan kalau kolom
 * cashflow_category dicentang di form Bulk Update Article.
 */
class ArticleBulkUpdateCashflowGuideSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    public function title(): string
    {
        return 'Panduan Cashflow Category';
    }

    public function headings(): array
    {
        return ['Category', 'Definisi', 'Contoh Article'];
    }

    public function array(): array
    {
        return [
            [
                'Operation',
                'Arus kas dari aktivitas operasional/produksi sehari-hari: article yang dibeli untuk diproses, dijual, atau habis pakai dalam produksi.',
                'Raw Material (RM/RMP/RMNP), Finished Goods (FG), Consumable Material (CM1/CM2)',
            ],
            [
                'Investment',
                'Arus kas dari pembelian/penjualan aset jangka panjang yang dipakai sendiri perusahaan, bukan untuk dijual atau diproses lagi.',
                'General Asset (GA), Peralatan/Tools (PT), mesin, alat produksi',
            ],
            [
                'Financing',
                'Arus kas dari aktivitas pendanaan: pinjaman, sewa pembiayaan, atau kewajiban finansial terkait article tersebut. Jarang dipakai, konfirmasi ke Finance kalau ragu.',
                'Article yang terkait leasing/pembiayaan, bukan aset operasional biasa',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:C1')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getStyle('A1:C4')->getAlignment()->setWrapText(true);
            },
        ];
    }
}
