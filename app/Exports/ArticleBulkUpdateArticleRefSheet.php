<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Sheet referensi (read-only, tidak dibaca ulang saat import) berisi daftar
 * kode article + nama article, supaya user tidak perlu buka halaman lain
 * untuk cari kode yang mau diisi di sheet Template.
 */
class ArticleBulkUpdateArticleRefSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    protected $articles;

    public function __construct($articles)
    {
        $this->articles = $articles;
    }

    public function title(): string
    {
        return 'Referensi Article';
    }

    public function headings(): array
    {
        return ['Article Code', 'Article Name'];
    }

    public function array(): array
    {
        return $this->articles
            ->map(fn ($a) => [$a->article_alternative_code, $a->article_desc])
            ->all();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:B1')->getFont()->setBold(true);
            },
        ];
    }
}
