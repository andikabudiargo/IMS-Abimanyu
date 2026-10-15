<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Sheet referensi kode COA + nama COA, hanya disertakan kalau kolom COA
 * dicentang di form Bulk Update Article.
 */
class ArticleBulkUpdateCoaRefSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{
    protected $accounts;

    public function __construct($accounts)
    {
        $this->accounts = $accounts;
    }

    public function title(): string
    {
        return 'Referensi COA';
    }

    public function headings(): array
    {
        return ['COA Code', 'COA Name'];
    }

    public function array(): array
    {
        return $this->accounts
            ->map(fn ($a) => [$a->account, $a->description])
            ->all();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A1:B1')->getFont()->setBold(true);

                // COA codes like "5.1" are valid numbers, so PhpSpreadsheet auto-detects
                // them as numeric and Excel re-renders using the OS locale's decimal
                // separator (e.g. "5,1" on id-ID), which users then copy into the Template
                // sheet as an invalid COA. Force the column to literal text so the code is
                // stored and displayed exactly as in the database, regardless of locale.
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A2:A' . max(2, $this->accounts->count() + 1))
                    ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

                foreach ($this->accounts->values() as $i => $account) {
                    $sheet->setCellValueExplicit('A' . ($i + 2), (string) $account->account, DataType::TYPE_STRING);
                }
            },
        ];
    }
}
