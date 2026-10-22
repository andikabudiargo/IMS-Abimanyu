<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;

// Baca by posisi kolom (bukan heading row) -- file export portal pajak kolom A selalu
// kosong lalu B..K = Masa Pajak | Nomor Dokumen | Status | NPWP | Nama | Kode | Jenis Pajak
// | Sifat | DPP | Nilai Pajak (PPN), sama di semua jenis export-nya (bukti potong, faktur).
class PpnPkEqualisasiImport implements ToCollection
{
    public array $rows = [];

    public function collection($collection)
    {
        foreach ($collection as $i => $row) {
            if ($i === 0) {
                continue; // header row
            }
            $masaPajak = trim((string) ($row[1] ?? ''));
            $docNumber = trim((string) ($row[2] ?? ''));
            if ($masaPajak === '' && $docNumber === '') {
                continue; // baris kosong
            }
            $this->rows[] = [
                'masa_pajak' => $masaPajak,
                'doc_number' => $docNumber,
                'status' => trim((string) ($row[3] ?? '')),
                'npwp' => trim((string) ($row[4] ?? '')),
                'nama' => trim((string) ($row[5] ?? '')),
                'kode' => trim((string) ($row[6] ?? '')),
                'jenis_pajak' => trim((string) ($row[7] ?? '')),
                'sifat' => trim((string) ($row[8] ?? '')),
                'dpp' => is_numeric($row[9] ?? null) ? (float) $row[9] : 0,
                'ppn' => is_numeric($row[10] ?? null) ? (float) $row[10] : 0,
            ];
        }
    }
}
