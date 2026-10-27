<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;

// Baca by posisi kolom (bukan heading row) -- dipakai untuk export Faktur Pajak Keluaran
// (PK) maupun Masukan (PM) dari portal pajak, layoutnya sama persis cuma beda judul kolom B
// (Nama Pembeli/Penjual): A = No. (kosong) | B = Nama Lawan Transaksi | C = NPWP/NIK/Paspor
// | D = Nomor Faktur Pajak | E = Tanggal Faktur Pajak | F = DPP (Harga Jual/Penggantian)
// | G = DPP Nilai Lain | H = PPN | I = PPnBM | J = Kode & Nomor Seri yang Diganti/Diretur
// | K = IsShowClearName. Tidak ada kolom Status/Kode/Jenis Pajak/Sifat di export ini --
// field itu dibiarkan kosong.
class PpnPkEqualisasiImport implements ToCollection
{
    public array $rows = [];

    public function collection($collection)
    {
        foreach ($collection as $i => $row) {
            if ($i === 0) {
                continue; // header row
            }
            $docNumber = trim((string) ($row[3] ?? ''));
            $nama = trim((string) ($row[1] ?? ''));
            if ($docNumber === '' && $nama === '') {
                continue; // baris kosong
            }
            $tanggal = $row[4] ?? null;
            $masaPajak = '';
            if ($tanggal instanceof \DateTimeInterface) {
                $masaPajak = $tanggal->format('m/Y');
            } elseif (is_numeric($tanggal)) {
                $masaPajak = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggal)->format('m/Y');
            } elseif (trim((string) $tanggal) !== '') {
                $ts = strtotime((string) $tanggal);
                $masaPajak = $ts !== false ? date('m/Y', $ts) : '';
            }
            $this->rows[] = [
                'masa_pajak' => $masaPajak,
                'doc_number' => $docNumber,
                'status' => '',
                'npwp' => trim((string) ($row[2] ?? '')),
                'nama' => $nama,
                'kode' => '',
                'jenis_pajak' => '',
                'sifat' => '',
                'dpp' => is_numeric($row[5] ?? null) ? (float) $row[5] : 0,
                'ppn' => is_numeric($row[7] ?? null) ? (float) $row[7] : 0,
            ];
        }
    }
}
