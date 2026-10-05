<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/*
    Blok tanda tangan standar (Disetujui / Diperiksa / Dibuat) di pojok
    kanan bawah sheet, sejajar dengan 4 kolom terakhir sebelum Status:
    Nominal, Biaya Administrasi, PPH23, Total. Dipakai sama oleh AP & AR
    Payment Planning Excel export supaya layoutnya konsisten.
*/
trait ApprovalBlock
{
    private function drawApprovalBlock(Worksheet $sheet, int $labelRow, int $idxNominal, int $idxBiayaAdmin, int $idxPph23, int $idxTotal)
    {
        $cNominal = Coordinate::stringFromColumnIndex($idxNominal);
        $cBiaya   = Coordinate::stringFromColumnIndex($idxBiayaAdmin);
        $cPph23   = Coordinate::stringFromColumnIndex($idxPph23);
        $cTotal   = Coordinate::stringFromColumnIndex($idxTotal);

        $sigFrom = $labelRow + 1;
        $sigTo   = $sigFrom + 3; // 4 baris kosong buat tanda tangan
        $nameRow = $sigTo + 1;

        $sheet->setCellValue("{$cNominal}{$labelRow}", 'Disetujui');
        $sheet->mergeCells("{$cBiaya}{$labelRow}:{$cPph23}{$labelRow}");
        $sheet->setCellValue("{$cBiaya}{$labelRow}", 'Diperiksa');
        $sheet->setCellValue("{$cTotal}{$labelRow}", 'Dibuat');

        $sheet->mergeCells("{$cNominal}{$sigFrom}:{$cNominal}{$sigTo}");
        $sheet->mergeCells("{$cBiaya}{$sigFrom}:{$cBiaya}{$sigTo}");
        $sheet->mergeCells("{$cPph23}{$sigFrom}:{$cPph23}{$sigTo}");
        $sheet->mergeCells("{$cTotal}{$sigFrom}:{$cTotal}{$sigTo}");

        $sheet->setCellValue("{$cNominal}{$nameRow}", 'Budi Mulyadi');
        $sheet->setCellValue("{$cBiaya}{$nameRow}", 'Yorin Asali');
        $sheet->setCellValue("{$cPph23}{$nameRow}", 'Nopi Yulianingsih');
        $sheet->setCellValue("{$cTotal}{$nameRow}", 'Hanna Syifa K.');

        $range = "{$cNominal}{$labelRow}:{$cTotal}{$nameRow}";
        $sheet->getStyle($range)->applyFromArray([
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("{$cNominal}{$labelRow}:{$cTotal}{$labelRow}")->getFont()->setBold(true);
        $sheet->getStyle("{$cNominal}{$nameRow}:{$cTotal}{$nameRow}")->getFont()->setBold(true);

        return $nameRow;
    }
}
