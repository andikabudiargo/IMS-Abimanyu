<?php

namespace App\Services;

/**
 * Parser rekening koran BCA (scope: 1 bank), format CSV export KlikBCA Bisnis
 * "Informasi Rekening - Mutasi Rekening": kolom Tanggal Transaksi, Keterangan,
 * Cabang, Jumlah ("<nominal> DB|CR" dalam 1 sel), Saldo. Saldo bisa negatif
 * (akun overdraft), ditangani apa adanya karena cast (float) sudah paham minus.
 *
 * Sengaja pindah dari parsing PDF (lihat riwayat): PDF BCA ternyata benar-benar
 * terenkripsi dan butuh pure-PHP decrypt yang ribet, sementara CSV export sudah
 * terstruktur rapi per kolom -- tidak perlu regex tebak-tebakan kolom seperti PDF.
 */
class BcaStatementParser
{
    public function parseFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Gagal membuka file CSV.');
        }

        try {
            return $this->parseHandle($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array{year:int|null, month:int|null, rows: array<int, array{stmt_date:string, description:string, amount:float, mutation_type:string, saldo:?float}>, summary: array{saldo_awal:?float, mutasi_cr:?float, count_cr:?int, mutasi_db:?float, count_db:?int, saldo_akhir:?float}}
     */
    private function parseHandle($handle): array
    {
        $year = null;
        $endYear = null;
        $endMonth = null;
        $rows = [];
        $summary = ['saldo_awal' => null, 'mutasi_cr' => null, 'count_cr' => null, 'mutasi_db' => null, 'count_db' => null, 'saldo_akhir' => null];

        while (($cols = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($cols === [null] || $cols === false) {
                continue; // baris kosong
            }

            $first = trim((string) $cols[0]);

            // "Periode : 01/01/2026 - 31/01/2026" -- ambil tahun awal & akhir periode
            // (jarang, tapi kalau periode custom melewati pergantian tahun, tahun akhir
            // beda dari tahun awal; dipakai toDate() buat milih tahun yg tepat per baris).
            if ($year === null && preg_match('/Periode\s*:\s*\d{2}\/\d{2}\/(\d{4})\s*-\s*\d{2}\/(\d{2})\/(\d{4})/i', $first, $m)) {
                $year = (int) $m[1];
                $endMonth = (int) $m[2];
                $endYear = (int) $m[3];
                continue;
            }

            // Baris transaksi: Tanggal Transaksi(dd/mm), Keterangan, Cabang, Jumlah, Saldo.
            if (count($cols) >= 5 && preg_match('/^(\d{2})\/(\d{2})$/', $first, $dm)) {
                $jumlah = trim((string) $cols[3]);
                if (!preg_match('/^([\d,]+\.\d{2})\s+(DB|CR)$/i', $jumlah, $am)) {
                    continue; // format tak dikenal, skip baris ini daripada salah tangkap
                }
                $rowYear = ($endYear !== null && $endYear !== $year && (int) $dm[2] === $endMonth) ? $endYear : $year;
                $rows[] = [
                    'stmt_date' => $this->toDate($dm[1], $dm[2], $rowYear),
                    'description' => trim((string) $cols[1]),
                    'amount' => $this->toNumber($am[1]),
                    'mutation_type' => strtoupper($am[2]),
                    'saldo' => $this->toNumber((string) $cols[4]),
                ];
                continue;
            }

            // Ringkasan penutup: "Saldo Awal : <n>" / "Saldo Akhir : <n>" (1 kolom),
            // "Mutasi Debet : <n>","<count>" / "Mutasi Kredit : <n>","<count>" (2 kolom).
            if (preg_match('/^(Saldo Awal|Saldo Akhir|Mutasi Debet|Mutasi Kredit)\s*:\s*([\-\d,]+\.\d{2})\s*$/i', $first, $m)) {
                $map = ['saldo awal' => 'saldo_awal', 'saldo akhir' => 'saldo_akhir', 'mutasi debet' => 'mutasi_db', 'mutasi kredit' => 'mutasi_cr'];
                $key = $map[strtolower($m[1])];
                $summary[$key] = $this->toNumber($m[2]);
                if ($key === 'mutasi_db' && isset($cols[1])) {
                    $summary['count_db'] = (int) $cols[1];
                } elseif ($key === 'mutasi_cr' && isset($cols[1])) {
                    $summary['count_cr'] = (int) $cols[1];
                }
                continue;
            }
        }

        return ['year' => $year, 'month' => null, 'rows' => $rows, 'summary' => $summary];
    }

    private function toNumber(string $raw): float
    {
        return (float) str_replace(',', '', $raw);
    }

    private function toDate(string $dd, string $mm, ?int $year): string
    {
        $y = $year ?? (int) date('Y');
        return sprintf('%04d-%02d-%02d', $y, (int) $mm, (int) $dd);
    }
}
