<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class StoReportExport implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    protected $header;
    protected $rows;
    protected $totals;
    protected $summary;

    protected $headerRowIndex;   // baris header tabel (untuk styling)
    protected $dataStartIndex;
    protected $totalRowIndex;
    protected $lastCol = 'Q';    // 17 kolom → A..Q

    public function __construct($header, $rows, $totals, $summary)
    {
        $this->header  = $header;
        $this->rows    = $rows;
        $this->totals  = $totals;
        $this->summary = $summary;
    }

    public function title(): string
    {
        return 'STO Report';
    }

    public function array(): array
    {
        $h = $this->header;
        $t = $this->totals;

        $out = [];

        // baris 1: judul
        $out[] = ['STO REPORT'];
        // baris 2: kosong
        $out[] = [];
        // baris 3-4: info
        $out[] = ['STO Code', $h['sto_code'], '', 'Periode', $h['periode']];
        $out[] = ['Lokasi', $h['location_code'] . ' — ' . $h['location_name'], '', 'Rentang', $h['date_from'] . ' s/d ' . $h['date_to']];
        // baris 5: kosong
        $out[] = [];

        // baris 6: header tabel
        $this->headerRowIndex = 6;
        $out[] = [
            'No', 'Alt. Code', 'Article Desc', 'Supp', 'UoM', 'Opening',
            'IN Receiving', 'IN Return Transfer', 'IN Replace Supplier',
            'OUT Supply Transfer', 'OUT Return Supplier', 'OUT DN Umum',
            'Balance', 'Hasil STO', 'Variance', 'Status', 'Akurasi %',
        ];

        $this->dataStartIndex = 7;

        // data
        foreach ($this->rows as $r) {
            $accPct = $this->rowAccuracy($r);
            $out[] = [
                $r->no,
                $r->alt_code,
                $r->article_desc,
                $r->supp,
                $r->uom,
                $this->n($r->opening),
                $this->n($r->in_receiving),
                $this->n($r->in_return_transfer),
                $this->n($r->in_replace_supplier),
                $this->n($r->out_supply_transfer),
                $this->n($r->out_return_supplier),
                $this->n($r->out_dn_umum),
                $this->n($r->closing),
                $r->qty_sto !== null ? $this->n($r->qty_sto) : '-',
                $r->variance !== null ? $this->n($r->variance) : '-',
                $r->sto_status,
                $accPct !== null ? $accPct : '-',
            ];
        }

        // baris total
        $this->totalRowIndex = $this->dataStartIndex + count($this->rows);
        $out[] = [
            '', '', '', '', 'TOTAL',
            $this->n($t['opening']),
            $this->n($t['in_receiving']),
            $this->n($t['in_return_transfer']),
            $this->n($t['in_replace_supplier']),
            $this->n($t['out_supply_transfer']),
            $this->n($t['out_return_supplier']),
            $this->n($t['out_dn_umum']),
            $this->n($t['closing']),
            $t['qty_sto'] !== null ? $this->n($t['qty_sto']) : '-',
            $t['variance'] !== null ? $this->n($t['variance']) : '-',
            '', '',
        ];

        return $out;
    }

    private function n($v)
    {
        return $v === null ? null : round((float) $v, 2);
    }

    private function rowAccuracy($r)
    {
        if ($r->qty_sto === null) return null;
        $b = (float) $r->closing;
        $s = (float) $r->qty_sto;
        if ($b == 0) return $s == 0 ? 100 : 0;
        $diffPct = abs($s - $b) / abs($b) * 100;
        if ($diffPct <= $this->summary['threshold_pct']) return 100;
        $acc = 100 - $diffPct;
        return $acc < 0 ? 0 : round($acc, 2);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,  'B' => 13, 'C' => 34, 'D' => 22, 'E' => 6,  'F' => 10,
            'G' => 13, 'H' => 16, 'I' => 17, 'J' => 18, 'K' => 17, 'L' => 12,
            'M' => 11, 'N' => 11, 'O' => 11, 'P' => 12, 'Q' => 10,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // judul
        $sheet->mergeCells("A1:{$this->lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // label info bold
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);
        $sheet->getStyle('D3:D4')->getFont()->setBold(true);

        // header tabel
        $hr = $this->headerRowIndex;
        $sheet->getStyle("A{$hr}:{$this->lastCol}{$hr}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4B6CB7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $hr    = $this->headerRowIndex;
                $start = $this->dataStartIndex;
                $total = $this->totalRowIndex;
                $last  = $this->lastCol;

                // border seluruh tabel (header s/d total)
                $sheet->getStyle("A{$hr}:{$last}{$total}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('C9D2DF');

                // baris total: bold + fill abu
                $sheet->getStyle("A{$total}:{$last}{$total}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9EDF3']],
                ]);

                // rata kanan kolom angka (F s/d Q) untuk data + total
                $sheet->getStyle("F{$start}:Q{$total}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // rata tengah No & Status
                $sheet->getStyle("A{$start}:A{$total}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("P{$start}:P{$total}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // freeze pane: header tabel tetap kelihatan saat scroll
                $sheet->freezePane('A' . ($hr + 1));

                // number format 2 desimal untuk kolom angka
                $sheet->getStyle("F{$start}:O{$total}")
                    ->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("Q{$start}:Q{$total}")
                    ->getNumberFormat()->setFormatCode('0.00');
            },
        ];
    }
}