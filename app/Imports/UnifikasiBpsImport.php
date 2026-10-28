<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;

// Export BPS dari portal pajak (layout BPS di LAYOUTS), dibaca by posisi kolom (0-based). Yang dipakai cuma
// kolom yang ditandai kuning:
// BPPU: B Masa Pajak | C Nomor Pemotongan | D Status | G Jenis Pajak | H Kode Objek Pajak
//       | J Nama | K DPP | L PPh
// BPS:  B Masa Pajak | C Nomor Pemotongan | F Nama Pemotong | G Kode Objek Pajak | J DPP | K PPh
class UnifikasiBpsImport implements ToCollection
{
    const LAYOUTS = [
        'bppu' => ['masa_pajak' => 1, 'doc_number' => 2, 'status' => 3, 'jenis_pajak' => 6, 'kode_objek_pajak' => 7, 'nama' => 9, 'dpp' => 10, 'pph' => 11],
        'bps' => ['masa_pajak' => 1, 'doc_number' => 2, 'status' => null, 'jenis_pajak' => null, 'kode_objek_pajak' => 6, 'nama' => 5, 'dpp' => 9, 'pph' => 10],
    ];

    public array $rows = [];
    private array $cols;

    public function __construct(string $group = 'bps')
    {
        $this->cols = self::LAYOUTS[$group];
    }

    public function collection($collection)
    {
        foreach ($collection as $i => $row) {
            if ($i === 0) {
                continue; // header row
            }
            $get = fn ($k) => $this->cols[$k] === null ? '' : trim((string) ($row[$this->cols[$k]] ?? ''));
            if ($get('doc_number') === '') {
                continue;
            }
            $num = fn ($k) => is_numeric($row[$this->cols[$k]] ?? null) ? (float) $row[$this->cols[$k]] : 0;
            $this->rows[] = [
                'masa_pajak' => $get('masa_pajak'),
                'doc_number' => $get('doc_number'),
                'status' => $get('status'),
                'jenis_pajak' => $get('jenis_pajak'),
                'kode_objek_pajak' => $get('kode_objek_pajak'),
                'nama' => $get('nama'),
                'dpp' => $num('dpp'),
                'pph' => $num('pph'),
            ];
        }
    }
}
