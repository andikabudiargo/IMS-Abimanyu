<?php

namespace App\Services;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Parser rekening koran BCA (scope: 1 bank, format "Laporan Mutasi Rekening").
 * Text extraction mengikuti urutan render PDF (TANGGAL KETERANGAN CBG MUTASI SALDO),
 * jadi CBG (kode cabang) kalau terisi akan ikut ke-capture di description.
 *
 * Ekstraksi teks pakai binary `pdftotext` (poppler-utils), bukan smalot/pdfparser --
 * statement BCA ternyata benar-benar terenkripsi (bukan cuma restricted-permission),
 * dan smalot/pdfparser murni-PHP tidak punya implementasi decrypt sama sekali (cuma
 * bisa skip pengecekannya, hasilnya "Missing catalog" karena isinya tetap acak).
 * pdftotext sudah battle-tested buat PDF ber-password-kosong begini.
 *
 * ponytail: heuristik baris transaksi vs baris noise (header/footer/catatan) adalah
 * "baru mulai nampung baris setelah header kolom TANGGAL/KETERANGAN/... ditemukan,
 * dan tutup transaksi saat baris tanggal baru atau header baru muncul". Belum pernah
 * dites terhadap PDF BCA multi-halaman asli — upgrade: tes dengan file nyata, lalu
 * sesuaikan daftar baris noise di isNoiseLine() kalau ada yang ke-skip/ke-ikut salah.
 */
class BcaStatementParser
{
    private const MONTHS = [
        'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4,
        'MEI' => 5, 'JUNI' => 6, 'JULI' => 7, 'AGUSTUS' => 8,
        'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
    ];

    public function parseFile(string $path): array
    {
        $binary = (new ExecutableFinder())->find('pdftotext');
        if (!$binary) {
            throw new \RuntimeException(
                "Binary 'pdftotext' tidak ditemukan di server. Install poppler-utils dulu, "
                . "mis. `sudo apt install poppler-utils` (Debian/Ubuntu) atau `sudo yum install poppler-utils` (CentOS/RHEL)."
            );
        }

        // -layout menjaga urutan kolom per baris tetap sesuai tampilan visual PDF,
        // penting supaya parseText() bisa baca TANGGAL/KETERANGAN/MUTASI berurutan.
        $process = new Process([$binary, '-layout', $path, '-']);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Gagal membaca PDF via pdftotext: ' . trim($process->getErrorOutput()));
        }

        return $this->parseText($process->getOutput());
    }

    /**
     * @return array{year:int|null, month:int|null, rows: array<int, array{stmt_date:string, description:string, amount:float, mutation_type:string, saldo:?float}>, summary: array{saldo_awal:?float, mutasi_cr:?float, count_cr:?int, mutasi_db:?float, count_db:?int, saldo_akhir:?float}}
     */
    public function parseText(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $year = $month = null;
        $rows = [];
        $current = null;
        $inTable = false;
        // Ringkasan di akhir laporan (SALDO AWAL/MUTASI CR/MUTASI DB/SALDO AKHIR),
        // dipakai sebagai checksum: bandingkan ke hasil parse baris transaksi.
        $summary = ['saldo_awal' => null, 'mutasi_cr' => null, 'count_cr' => null, 'mutasi_db' => null, 'count_db' => null, 'saldo_akhir' => null];

        $closeCurrent = function () use (&$current, &$rows) {
            if ($current !== null) {
                $current['description'] = trim(preg_replace('/\s+/', ' ', $current['description']));
                $rows[] = $current;
                $current = null;
            }
        };

        // Nominal+DB/CR+[saldo] di ujung baris -- dipakai baik di baris tanggal (kasus
        // umum: semua dalam 1 baris) maupun di baris lanjutan tanpa tanggal (kasus dari
        // screenshot: baris tanggal cuma mulai description, nominalnya nyusul di baris
        // berikutnya). Decimal 2 digit wajib supaya nomor referensi panjang (mis. nomor
        // rekening tujuan) tidak ke-salah-tangkap sebagai nominal.
        $amountTrailingRegex = '/^(.*?)\s*([\d,]+\.\d{2})\s+(DB|CR)(?:\s+([\d,]+\.\d{2}))?\s*$/i';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/PERIODE\s*:?\s*([A-Z]+)\s+(\d{4})/i', $line, $m)) {
                $month = self::MONTHS[strtoupper($m[1])] ?? null;
                $year = (int) $m[2];
                continue;
            }

            if (preg_match('/^(SALDO\s+AWAL|SALDO\s+AKHIR|MUTASI\s+CR|MUTASI\s+DB)\s*:\s*([\d.,]+)(?:\s+(\d+))?\s*$/i', $line, $m)) {
                $closeCurrent();
                $inTable = false;
                $key = strtoupper(preg_replace('/\s+/', ' ', $m[1]));
                $map = ['SALDO AWAL' => 'saldo_awal', 'SALDO AKHIR' => 'saldo_akhir', 'MUTASI CR' => 'mutasi_cr', 'MUTASI DB' => 'mutasi_db'];
                $summary[$map[$key]] = $this->toNumber($m[2]);
                if ($key === 'MUTASI CR' && isset($m[3])) {
                    $summary['count_cr'] = (int) $m[3];
                } elseif ($key === 'MUTASI DB' && isset($m[3])) {
                    $summary['count_db'] = (int) $m[3];
                }
                continue;
            }

            if (preg_match('/^TANGGAL\s+KETERANGAN/i', $line)) {
                $inTable = true;
                $closeCurrent();
                continue;
            }

            if (!$inTable) {
                continue;
            }

            // "Bersambung ke halaman berikut" cuma penanda potong halaman -- tabel
            // tetap lanjut di halaman berikutnya (dengan atau tanpa header diulang),
            // jadi transaksi berjalan ditutup tapi mode tabel TIDAK direset.
            if (preg_match('/^Bersambung ke halaman/i', $line)) {
                $closeCurrent();
                continue;
            }

            if ($this->isNoiseLine($line)) {
                $closeCurrent();
                $inTable = false;
                continue;
            }

            // Baris SALDO AWAL: tanggal + label + saldo (tanpa mutasi DB/CR).
            if (preg_match('/^(\d{2})\/(\d{2})\s+SALDO\s+AWAL\s+([\d.,]+)\s*$/i', $line, $m)) {
                $closeCurrent();
                continue; // saldo awal bukan transaksi yang perlu dicocokkan ke jurnal
            }

            // Baris tanggal baru -- mulai transaksi. Nominalnya bisa langsung ada di baris
            // ini (kasus umum) atau baru muncul di baris lanjutan (lihat regex di bawah).
            if (preg_match('/^(\d{2})\/(\d{2})\s*(.*)$/', $line, $m)) {
                $closeCurrent();
                [, $dd, $mm, $rest] = $m;
                $current = [
                    'stmt_date' => $this->toDate($dd, $mm, $year, $month),
                    'description' => $rest,
                    'amount' => null,
                    'mutation_type' => null,
                    'saldo' => null,
                ];
                if (preg_match($amountTrailingRegex, $rest, $am)) {
                    $current['description'] = $am[1];
                    $current['amount'] = $this->toNumber($am[2]);
                    $current['mutation_type'] = strtoupper($am[3]);
                    $current['saldo'] = isset($am[4]) && $am[4] !== '' ? $this->toNumber($am[4]) : null;
                }
                continue;
            }

            // Baris lanjutan (tanpa tanggal) milik transaksi berjalan: kalau nominalnya
            // belum ketemu, cek dulu apakah nominal ada di baris ini; kalau sudah ketemu
            // sebelumnya, baris ini cuma catatan tambahan (nama, nomor voucher, dst).
            if ($current !== null) {
                if ($current['amount'] === null && preg_match($amountTrailingRegex, $line, $am)) {
                    $current['description'] = trim($current['description'] . ' ' . $am[1]);
                    $current['amount'] = $this->toNumber($am[2]);
                    $current['mutation_type'] = strtoupper($am[3]);
                    $current['saldo'] = isset($am[4]) && $am[4] !== '' ? $this->toNumber($am[4]) : null;
                } else {
                    $current['description'] .= ' ' . $line;
                }
            }
        }
        $closeCurrent();

        return ['year' => $year, 'month' => $month, 'rows' => $rows, 'summary' => $summary];
    }

    private function isNoiseLine(string $line): bool
    {
        return (bool) preg_match('/^(HALAMAN|MATA UANG|CATATAN|Apabila nasabah|BCA berhak)/i', $line);
    }

    private function toNumber(string $raw): float
    {
        return (float) str_replace(',', '', $raw);
    }

    private function toDate(string $dd, string $mm, ?int $year, ?int $headerMonth): string
    {
        // Statement period bisa melewati pergantian tahun (statement Desember s/d Januari);
        // pakai bulan di baris untuk pilih tahun yg tepat relatif ke bulan header.
        $y = $year ?? (int) date('Y');
        if ($headerMonth !== null && (int) $mm !== $headerMonth) {
            $y = (int) $mm === 12 && $headerMonth === 1 ? $y - 1 : $y;
        }
        return sprintf('%04d-%02d-%02d', $y, (int) $mm, (int) $dd);
    }
}
