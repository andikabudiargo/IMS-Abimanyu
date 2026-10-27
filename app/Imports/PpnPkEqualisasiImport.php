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
            $rawTanggal = $row[4] ?? null;
            $tanggalObj = null;
            if ($rawTanggal instanceof \DateTimeInterface) {
                $tanggalObj = $rawTanggal;
            } elseif (is_numeric($rawTanggal)) {
                $tanggalObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawTanggal);
            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) $rawTanggal, $m)) {
                // String ISO kadang ikut terbawa jam+offset timezone ("...T00:00:00+07:00")
                // -- ambil bagian tanggalnya langsung, JANGAN lewat strtotime(): itu
                // mengonversi ke UTC dulu baru date() format ulang pakai timezone default
                // server, bisa mundur 1 hari padahal jamnya memang selalu 00:00 di zona asal.
                $tanggalObj = \DateTime::createFromFormat('Y-m-d', "{$m[1]}-{$m[2]}-{$m[3]}");
            } elseif (trim((string) $rawTanggal) !== '') {
                $ts = strtotime((string) $rawTanggal);
                $tanggalObj = $ts !== false ? (new \DateTime())->setTimestamp($ts) : null;
            }
            $this->rows[] = [
                'masa_pajak' => $tanggalObj ? $tanggalObj->format('m/Y') : '',
                'tanggal' => $tanggalObj ? $tanggalObj->format('d-m-Y') : '',
                'doc_number' => $docNumber,
                'status' => '',
                'npwp' => trim((string) ($row[2] ?? '')),
                'nama' => $nama,
                'kode' => '',
                'jenis_pajak' => '',
                'sifat' => '',
                'dpp' => is_numeric($row[5] ?? null) ? (float) $row[5] : 0,
                'dpp_lain' => is_numeric($row[6] ?? null) ? (float) $row[6] : 0,
                'ppn' => is_numeric($row[7] ?? null) ? (float) $row[7] : 0,
            ];
        }
    }
}
