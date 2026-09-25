<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel Stock Report. $data = hasil StockReportController::data() (array):
 * header, rows, totals, columns (kolom IN/OUT dinamis per grup lokasi).
 */
class StockReportExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    private $data;
    private $tableHeaderRow = 5;
    private $lastRow;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Stock Report';
    }

    public function array(): array
    {
        $h    = $this->data['header'];
        $cols = $this->data['columns'];
        $mv   = [];
        foreach (['in' => 'IN', 'out' => 'OUT'] as $dir => $prefix) {
            foreach ($cols[$dir] as $c) $mv[] = ['key' => $c['key'], 'label' => $prefix . ' ' . $c['label']];
        }

        $out = [
            ['STOCK REPORT'],
            ['Lokasi', $h['location_code'] . ' — ' . $h['location_name']],
            ['Rentang', $h['date_from'] . ' s/d ' . $h['date_to']],
            [],
            array_merge(['No', 'Alt. Code', 'Article Desc', 'Supp', 'Min Package', 'UoM', 'Opening'], array_column($mv, 'label'), ['Safety Stock', 'Balance']),
        ];

        foreach ($this->data['rows'] as $r) {
            $row = [$r['no'], $r['alt_code'], $r['article_desc'], $r['supp'], $r['min_package'], $r['uom'], $r['opening']];
            foreach ($mv as $c) $row[] = $r[$c['key']];
            $row[] = $r['safety_stock'];
            $row[] = $r['closing'];
            $out[] = $row;
        }

        $t   = $this->data['totals'];
        $tot = ['TOTAL', '', '', '', '', '', $t['opening']];
        foreach ($mv as $c) $tot[] = $t[$c['key']];
        $tot[] = '';
        $tot[] = $t['closing'];
        $out[] = $tot;

        $this->lastRow = count($out);
        return $out;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A5:' . $sheet->getHighestColumn() . '5')->getFont()->setBold(true);
        $sheet->getStyle('A' . $this->lastRow . ':' . $sheet->getHighestColumn() . $this->lastRow)->getFont()->setBold(true);
        $sheet->getStyle('G6:' . $sheet->getHighestColumn() . $this->lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        return [];
    }
}
