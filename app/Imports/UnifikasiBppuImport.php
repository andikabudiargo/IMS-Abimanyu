<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;

// Export BPPU dari portal pajak, dibaca by posisi kolom. Yang dipakai cuma kolom yang
// ditandai kuning: B = Masa Pajak | C = Nomor Pemotongan | D = Status | G = Jenis Pajak
// | H = Kode Objek Pajak | J = Nama | K = DPP (Rp) | L = PPh (Rp).
class UnifikasiBppuImport implements ToCollection
{
    public array $rows = [];

    public function collection($collection)
    {
        foreach ($collection as $i => $row) {
            if ($i === 0) {
                continue; // header row
            }
            $docNumber = trim((string) ($row[2] ?? ''));
            if ($docNumber === '') {
                continue;
            }
            $this->rows[] = [
                'masa_pajak' => trim((string) ($row[1] ?? '')),
                'doc_number' => $docNumber,
                'status' => trim((string) ($row[3] ?? '')),
                'jenis_pajak' => trim((string) ($row[6] ?? '')),
                'kode_objek_pajak' => trim((string) ($row[7] ?? '')),
                'nama' => trim((string) ($row[9] ?? '')),
                'dpp' => is_numeric($row[10] ?? null) ? (float) $row[10] : 0,
                'pph' => is_numeric($row[11] ?? null) ? (float) $row[11] : 0,
            ];
        }
    }
}
