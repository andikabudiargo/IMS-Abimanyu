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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StoReportExport implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    protected $header;
    protected $rows;
    protected $totals;
    protected $summary;

    // definisi kolom movement dinamis: ['in' => [['key'=>..,'label'=>..], ...], 'out' => [...]]
    protected $columns;
    protected $inCols;
    protected $outCols;

    // ── indeks kolom (1-based), dihitung dari jumlah kolom movement ──
    protected $idxNo            = 1;
    protected $idxAltCode       = 2;
    protected $idxArticleDesc   = 3;
    protected $idxSupp          = 4;
    protected $idxUom           = 5;
    protected $idxOpening       = 6;
    protected $idxInStart;
    protected $idxInEnd;
    protected $idxOutStart;
    protected $idxOutEnd;
    protected $idxBalance;
    protected $idxHasilSto;
    protected $idxVariance;
    protected $idxStatus;
    protected $idxAkurasi;
    protected $totalCols;

    protected $lastCol;          // huruf kolom terakhir, dihitung dinamis

    protected $headerRowTop;     // baris grup header (IN/OUT) — untuk styling
    protected $headerRowBottom;  // baris label kolom
    protected $dataStartIndex;
    protected $totalRowIndex;

    public function __construct($header, $rows, $totals, $summary, $columns = null)
    {
        $this->header  = $header;
        $this->rows    = $rows;
        $this->totals  = $totals;
        $this->summary = $summary;

        // fallback ke struktur lama (grup CHEMICAL) kalau columns tidak dikirim,
        // supaya tetap kompatibel kalau ada pemanggil lama yang belum di-update.
        $this->columns = $columns ?: [
            'in' => [
                ['key' => 'in_receiving',        'label' => 'IN Receiving'],
                ['key' => 'in_return_transfer',  'label' => 'IN Return Transfer'],
                ['key' => 'in_replace_supplier', 'label' => 'IN Replace Supplier'],
            ],
            'out' => [
                ['key' => 'out_supply_transfer', 'label' => 'OUT Supply Transfer'],
                ['key' => 'out_return_supplier', 'label' => 'OUT Return Supplier'],
                ['key' => 'out_dn_umum',          'label' => 'OUT DN Umum'],
            ],
        ];

        $this->inCols  = $this->columns['in']  ?? [];
        $this->outCols = $this->columns['out'] ?? [];

        // ── hitung indeks kolom dinamis ──
        $this->idxInStart  = $this->idxOpening + 1;
        $this->idxInEnd    = $this->idxInStart + count($this->inCols) - 1;
        $this->idxOutStart = $this->idxInEnd + 1;
        $this->idxOutEnd   = $this->idxOutStart + count($this->outCols) - 1;
        $this->idxBalance  = $this->idxOutEnd + 1;
        $this->idxHasilSto = $this->idxBalance + 1;
        $this->idxVariance = $this->idxHasilSto + 1;
        $this->idxStatus   = $this->idxVariance + 1;
        $this->idxAkurasi  = $this->idxStatus + 1;
        $this->totalCols   = $this->idxAkurasi;

        $this->lastCol = Coordinate::stringFromColumnIndex($this->totalCols);
    }

    public function title(): string
    {
        return 'STO Report';
    }

    private function colLetter(int $idx): string
    {
        return Coordinate::stringFromColumnIndex($idx);
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

        // ── baris 6: header grup (No/Alt/Desc/... statis, IN / OUT digabung) ──
        $this->headerRowTop = 6;
        $rowTop = array_fill(0, $this->totalCols, '');
        $rowTop[$this->idxNo - 1]          = 'No';
        $rowTop[$this->idxAltCode - 1]     = 'Alt. Code';
        $rowTop[$this->idxArticleDesc - 1] = 'Article Desc';
        $rowTop[$this->idxSupp - 1]        = 'Supp';
        $rowTop[$this->idxUom - 1]         = 'UoM';
        $rowTop[$this->idxOpening - 1]     = 'Opening';
        if (count($this->inCols) > 0) {
            $rowTop[$this->idxInStart - 1] = 'IN';
        }
        if (count($this->outCols) > 0) {
            $rowTop[$this->idxOutStart - 1] = 'OUT';
        }
        $rowTop[$this->idxBalance - 1]  = 'Balance';
        $rowTop[$this->idxHasilSto - 1] = 'Hasil STO';
        $rowTop[$this->idxVariance - 1] = 'Variance';
        $rowTop[$this->idxStatus - 1]   = 'Status';
        $rowTop[$this->idxAkurasi - 1]  = 'Akurasi %';
        $out[] = $rowTop;

        // ── baris 7: label kolom IN/OUT individual (kolom statis dibiarkan kosong, nanti di-merge vertikal) ──
        $this->headerRowBottom = 7;
        $rowBottom = array_fill(0, $this->totalCols, '');
        foreach ($this->inCols as $i => $c) {
            $rowBottom[$this->idxInStart - 1 + $i] = $c['label'];
        }
        foreach ($this->outCols as $i => $c) {
            $rowBottom[$this->idxOutStart - 1 + $i] = $c['label'];
        }
        $out[] = $rowBottom;

        $this->dataStartIndex = 8;

        // ── data ──
        foreach ($this->rows as $r) {
            $accPct = $this->rowAccuracy($r);

            $row = array_fill(0, $this->totalCols, '');
            $row[$this->idxNo - 1]          = $r->no;
            $row[$this->idxAltCode - 1]     = $r->alt_code;
            $row[$this->idxArticleDesc - 1] = $r->article_desc;
            $row[$this->idxSupp - 1]        = $r->supp;
            $row[$this->idxUom - 1]         = $r->uom;
            $row[$this->idxOpening - 1]     = $this->n($r->opening);

            foreach ($this->inCols as $i => $c) {
                $row[$this->idxInStart - 1 + $i] = $this->n($r->{$c['key']} ?? 0);
            }
            foreach ($this->outCols as $i => $c) {
                $row[$this->idxOutStart - 1 + $i] = $this->n($r->{$c['key']} ?? 0);
            }

            $row[$this->idxBalance - 1]  = $this->n($r->closing);
            $row[$this->idxHasilSto - 1] = $r->qty_sto !== null ? $this->n($r->qty_sto) : '-';
            $row[$this->idxVariance - 1] = $r->variance !== null ? $this->n($r->variance) : '-';
            $row[$this->idxStatus - 1]   = $r->sto_status;
            $row[$this->idxAkurasi - 1]  = $accPct !== null ? $accPct : '-';

            $out[] = $row;
        }

        // ── baris total ──
        $this->totalRowIndex = $this->dataStartIndex + count($this->rows);

        $rowTotal = array_fill(0, $this->totalCols, '');
        // label "TOTAL" ditaruh di kolom UoM (sama seperti posisi lama, sebelum kolom Opening)
        $rowTotal[$this->idxUom - 1]    = 'TOTAL';
        $rowTotal[$this->idxOpening - 1] = $this->n($t['opening']);

        foreach ($this->inCols as $i => $c) {
            $rowTotal[$this->idxInStart - 1 + $i] = $this->n($t[$c['key']] ?? 0);
        }
        foreach ($this->outCols as $i => $c) {
            $rowTotal[$this->idxOutStart - 1 + $i] = $this->n($t[$c['key']] ?? 0);
        }

        $rowTotal[$this->idxBalance - 1]  = $this->n($t['closing']);
        $rowTotal[$this->idxHasilSto - 1] = $t['qty_sto'] !== null ? $this->n($t['qty_sto']) : '-';
        $rowTotal[$this->idxVariance - 1] = $t['variance'] !== null ? $this->n($t['variance']) : '-';
        // Status & Akurasi dibiarkan kosong di baris total

        $out[] = $rowTotal;

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
        $widths = [];
        $widths[$this->colLetter($this->idxNo)]          = 5;
        $widths[$this->colLetter($this->idxAltCode)]     = 13;
        $widths[$this->colLetter($this->idxArticleDesc)] = 34;
        $widths[$this->colLetter($this->idxSupp)]        = 22;
        $widths[$this->colLetter($this->idxUom)]         = 6;
        $widths[$this->colLetter($this->idxOpening)]     = 10;

        foreach ($this->inCols as $i => $c) {
            $widths[$this->colLetter($this->idxInStart + $i)] = 16;
        }
        foreach ($this->outCols as $i => $c) {
            $widths[$this->colLetter($this->idxOutStart + $i)] = 16;
        }

        $widths[$this->colLetter($this->idxBalance)]  = 12;
        $widths[$this->colLetter($this->idxHasilSto)] = 11;
        $widths[$this->colLetter($this->idxVariance)] = 11;
        $widths[$this->colLetter($this->idxStatus)]   = 12;
        $widths[$this->colLetter($this->idxAkurasi)]  = 10;

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        // judul (posisi fix baris 1)
        $sheet->mergeCells("A1:{$this->lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // label info (posisi fix baris 3-4)
        $sheet->getStyle('A3:A4')->getFont()->setBold(true);
        $sheet->getStyle('D3:D4')->getFont()->setBold(true);

        // header tabel (merge + styling) ditangani di registerEvents
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last  = $this->lastCol;

                // ── deteksi baris header grup: cari sel kolom A yang isinya 'No' ──
                $hrTop = null;
                $highest = $sheet->getHighestRow();
                for ($r = 1; $r <= $highest; $r++) {
                    if (trim((string) $sheet->getCell("A{$r}")->getValue()) === 'No') {
                        $hrTop = $r;
                        break;
                    }
                }
                if (!$hrTop) return;   // header tak ketemu, jangan styling apa-apa

                $hrBottom = $hrTop + 1;  // baris label kolom IN/OUT
                $start    = $hrBottom + 1;
                $total    = $highest;    // baris TOTAL = baris terakhir

                // ══════════════════════════════════════════════
                // MERGE HEADER — samain kayak tampilan UI (rowspan
                // untuk kolom statis, colspan untuk grup IN/OUT)
                // ══════════════════════════════════════════════
                $staticCols = [
                    $this->idxNo, $this->idxAltCode, $this->idxArticleDesc,
                    $this->idxSupp, $this->idxUom, $this->idxOpening,
                    $this->idxBalance, $this->idxHasilSto, $this->idxVariance,
                    $this->idxStatus, $this->idxAkurasi,
                ];
                foreach ($staticCols as $idx) {
                    $col = $this->colLetter($idx);
                    $sheet->mergeCells("{$col}{$hrTop}:{$col}{$hrBottom}");
                }

                if (count($this->inCols) > 0) {
                    $colFrom = $this->colLetter($this->idxInStart);
                    $colTo   = $this->colLetter($this->idxInEnd);
                    $sheet->mergeCells("{$colFrom}{$hrTop}:{$colTo}{$hrTop}");
                }
                if (count($this->outCols) > 0) {
                    $colFrom = $this->colLetter($this->idxOutStart);
                    $colTo   = $this->colLetter($this->idxOutEnd);
                    $sheet->mergeCells("{$colFrom}{$hrTop}:{$colTo}{$hrTop}");
                }

                // ── styling header (2 baris: grup + label) ──
                $sheet->getStyle("A{$hrTop}:{$last}{$hrBottom}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                // warna beda buat grup IN (biru muda) & OUT (merah muda), samain kayak bg-light-primary/bg-light-danger di UI
                $sheet->getStyle("A{$hrTop}:{$last}{$hrBottom}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4B6CB7');

                if (count($this->inCols) > 0) {
                    $colFrom = $this->colLetter($this->idxInStart);
                    $colTo   = $this->colLetter($this->idxInEnd);
                    $sheet->getStyle("{$colFrom}{$hrTop}:{$colTo}{$hrBottom}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3ECFB');
                    $sheet->getStyle("{$colFrom}{$hrTop}:{$colTo}{$hrBottom}")
                        ->getFont()->getColor()->setRGB('4B6CB7');
                }
                if (count($this->outCols) > 0) {
                    $colFrom = $this->colLetter($this->idxOutStart);
                    $colTo   = $this->colLetter($this->idxOutEnd);
                    $sheet->getStyle("{$colFrom}{$hrTop}:{$colTo}{$hrBottom}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FBE4E4');
                    $sheet->getStyle("{$colFrom}{$hrTop}:{$colTo}{$hrBottom}")
                        ->getFont()->getColor()->setRGB('B74B4B');
                }

                // ── border seluruh tabel (dari baris grup header s/d baris TOTAL) ──
                $sheet->getStyle("A{$hrTop}:{$last}{$total}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('C9D2DF');

                // ── baris total: bold + fill abu ──
                $sheet->getStyle("A{$total}:{$last}{$total}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9EDF3']],
                ]);

                // ── alignment kolom numerik (Opening s/d Variance) ──
                $numFrom = $this->colLetter($this->idxOpening);
                $numTo   = $this->colLetter($this->idxVariance);
                $sheet->getStyle("{$numFrom}{$start}:{$numTo}{$total}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("A{$start}:A{$total}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $statusCol = $this->colLetter($this->idxStatus);
                $sheet->getStyle("{$statusCol}{$start}:{$statusCol}{$total}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $akurasiCol = $this->colLetter($this->idxAkurasi);
                $sheet->getStyle("{$akurasiCol}{$start}:{$akurasiCol}{$total}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ── freeze TEPAT di bawah header (2 baris header) ──
                $sheet->freezePane('A' . $start);

                // ── number format ──
                $sheet->getStyle("{$numFrom}{$start}:{$numTo}{$total}")
                    ->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("{$akurasiCol}{$start}:{$akurasiCol}{$total}")
                    ->getNumberFormat()->setFormatCode('0.00');
            },
        ];
    }
}