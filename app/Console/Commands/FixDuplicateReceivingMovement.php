<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bersihkan baris warehouse_movement bertipe RECEIVING yang duplikat/basi.
 *
 * Latar belakang: ReceivingController::unPosting() versi lama (sebelum commit
 * 719600d8, "Update Receiving", 2026-09-04) insert baris reversal alih-alih
 * menghapus movement lama saat dokumen direvisi/diedit setelah posting.
 * doPosting() versi lama juga belum punya guard anti double-post. Akibatnya:
 * dokumen yang direvisi sebelum tanggal itu bisa punya movement RECEIVING
 * duplikat -- baris lama (sebelum edit) tidak pernah terhapus, ditambah baris
 * baru (setelah edit/repost). Kode SEKARANG sudah benar (3 lapis pengaman:
 * unPosting() hapus asli, update() kunci+hapus-sebelum-repost, doPosting()
 * guard anti double-post) -- command ini HANYA untuk membersihkan sisa data
 * lama, bukan memperbaiki kode.
 *
 * REVISI (setelah get_last_qty_new() dibetulkan & dedup-nya dihapus): versi
 * SEBELUMNYA command ini cuma menjumlahkan kolom movement_plus untuk cek
 * "sudah pas atau belum" -- meleset untuk baris basi berbentuk REVERSAL
 * (movement_plus=0, movement_min=qty) yang TIDAK PERNAH diikuti baris repost
 * baru (mis. artikel 1000379: baris +8 [asli, masih berlaku] + baris -8
 * [reversal basi, movement_min=8] -- movement_plus-nya cuma 8+0=8 = PAS SAMA
 * dengan "seharusnya", jadi versi lama menganggap AMAN padahal baris -8 itu
 * tetap memotong saldo ledger 8 unit). Sekarang pakai NET (movement_plus -
 * movement_min) per baris, bukan movement_plus doang -- baru baris seperti ini
 * ketahuan.
 *
 * Algoritma per grup (movement_transnno, artikel_code, location_number) yang
 * punya >1 baris RECEIVING:
 *   1. Hitung "seharusnya" = SUM(qty+qty_free terkonversi) dari receiving_det
 *      SAAT INI untuk artikel itu di dokumen itu (mengikuti rumus qtyBaseSql
 *      yang sama persis dengan doPosting()).
 *   2. Kalau NET semua baris movement (SUM(movement_plus - movement_min))
 *      sudah PAS sama dengan "seharusnya" -> tidak diapa-apakan (termasuk
 *      pola normal "reversal + repost lengkap" yang nett-nya sudah benar,
 *      atau "1 baris qty=0 + 1 baris qty penuh" -- bukan bug).
 *   3. Kalau tidak pas -> urutkan baris dari PALING BARU (movement_code
 *      DESC), jumlahkan NET (bukan cuma plus) dari situ sampai PAS mencapai
 *      "seharusnya". Baris yang terpakai (baru) -> disimpan. Sisanya (lebih
 *      lama, termasuk baris reversal basi yang net-nya negatif) -> dihapus.
 *      Baris yang net-nya benar-benar nol (plus=0 DAN min=0) tidak pernah
 *      disentuh (bukan bagian dari bug ini, tidak mempengaruhi total).
 *   4. Kalau tidak bisa pas PERSIS (jumlah dari baris terbaru melewati/tidak
 *      pernah mencapai "seharusnya") -> JANGAN tebak, masukkan daftar "perlu
 *      review manual", tidak dihapus otomatis.
 *
 * Default: DRY RUN (cuma laporan). Pakai --fix untuk benar-benar menghapus.
 *
 * CATATAN PENTING: --fix TIDAK LAGI memanggil ReceivingController::
 * recalculateFromDate() otomatis (dulu iya). Method itu cuma jumlah polos
 * movement_plus - movement_min dari SEMUA baris sejak --recalc-from TANPA
 * filter status cancel, TANPA exclude CANCEL %/DELETE%/REVISI %, TANPA
 * exclude baris ADJUSTMENT yang terikat OPENING BALANCE -- kelas bug yang
 * sama persis dengan yang sudah dibetulkan di get_last_qty_new(). Kalau
 * dipakai untuk 100+ kombinasi sekaligus, risikonya malah bikin salah lagi
 * (dobel-hitung OB, ikut hitung transaksi yang sudah di-cancel, dst).
 * Setelah --fix menghapus baris basi, jalankan SENDIRI:
 *   php artisan movement:recalculate-ledger
 * (command itu sudah pakai exclusion filter yang benar + sanity-check
 * terhadap get_last_qty_new -- lihat app/Console/Commands/
 * RecalculateArticleLocationLedger.php) untuk kombinasi yang terdampak.
 */
class FixDuplicateReceivingMovement extends Command
{
    protected $signature = 'receiving:fix-duplicate-movement
                            {--fix : Benar-benar hapus baris basi (default: dry-run, cuma laporan). TIDAK auto-recalculate -- jalankan movement:recalculate-ledger setelahnya.}';

    protected $description = 'Cari & (opsional, via --fix) bersihkan baris warehouse_movement RECEIVING duplikat/basi akibat bug unPosting() lama (sebelum 2026-09-04)';

    public function handle()
    {
        $doFix = (bool) $this->option('fix');

        $groups = DB::select("
            WITH dup_groups AS (
                SELECT movement_transnno, artikel_code, location_number, COUNT(*) AS jumlah_baris
                FROM warehouse_movement
                WHERE movement_type = 'RECEIVING'
                GROUP BY movement_transnno, artikel_code, location_number
                HAVING COUNT(*) > 1
            ),
            expected AS (
                SELECT
                    rec_number AS movement_transnno,
                    article_code AS artikel_code,
                    SUM(COALESCE(NULLIF(qty_conv,0),
                        (qty + qty_free) * COALESCE(NULLIF(conv_factor,0),1))) AS seharusnya
                FROM receiving_det
                GROUP BY rec_number, article_code
            )
            SELECT g.movement_transnno, g.artikel_code, g.location_number,
                   COALESCE(e.seharusnya, 0) AS seharusnya
            FROM dup_groups g
            LEFT JOIN expected e
                ON e.movement_transnno = g.movement_transnno
               AND e.artikel_code = g.artikel_code
            ORDER BY g.movement_transnno, g.artikel_code
        ");

        $this->info('Total grup terdeteksi (jumlah_baris > 1): ' . count($groups));

        $toDelete           = [];   // movement_code[]
        $toReview           = [];   // grup yang ambigu
        $affectedForRecalc  = [];   // key: artikel|lokasi

        foreach ($groups as $g) {
            $rows = DB::table('warehouse_movement')
                ->where('movement_transnno', $g->movement_transnno)
                ->where('artikel_code', $g->artikel_code)
                ->where('location_number', $g->location_number)
                ->where('movement_type', 'RECEIVING')
                ->orderBy('movement_code', 'desc') // paling baru dulu
                ->get(['movement_code', 'movement_plus', 'movement_min']);

            $target   = (float) $g->seharusnya;
            $totalNet = (float) $rows->sum(fn($r) => (float) $r->movement_plus - (float) $r->movement_min);

            if (abs($totalNet - $target) < 0.0001) {
                continue; // NET semua baris sudah pas -- termasuk pola normal 0+penuh
                          // atau reversal+repost yang lengkap, tidak disentuh
            }

            $running  = 0.0;
            $keep     = [];
            // Kalau target 0, "sudah pas" tercapai dari AWAL (tanpa perlu baris apa
            // pun) -- BUG lama: resolved cuma di-set TRUE di dalam loop SETELAH
            // nambah baris ke $keep, jadi target=0 tidak pernah ke-set resolved
            // (loop langsung break di baris pertama sebelum sempat masuk situ).
            // Akibatnya SEMUA grup "artikel sudah dihapus total dari dokumen"
            // (seharusnya=0) salah masuk daftar ambigu, padahal jelas: semua baris
            // non-nol yang ada memang basi semua.
            $resolved = abs($target) < 0.0001;

            foreach ($rows as $r) {
                $net = (float) $r->movement_plus - (float) $r->movement_min;
                if (abs($net) < 0.0001) continue; // baris plus=0 DAN min=0 -- tidak dihitung/disentuh

                if ($resolved) {
                    // target sudah tercapai sebelum baris (lebih lama) ini -> sisanya basi
                    // (termasuk baris reversal basi ber-NET negatif seperti kasus 1000379)
                    break;
                }

                $running += $net;
                $keep[]   = $r->movement_code;

                if (abs($running - $target) < 0.0001) {
                    $resolved = true;
                }
            }

            if (!$resolved) {
                $toReview[] = $g;
                continue;
            }

            foreach ($rows as $r) {
                $net = (float) $r->movement_plus - (float) $r->movement_min;
                if (abs($net) < 0.0001) continue;
                if (in_array($r->movement_code, $keep, true)) continue;
                $toDelete[] = $r->movement_code;
            }

            $affectedForRecalc[$g->artikel_code . '|' . $g->location_number] = true;
        }

        $this->info('Baris movement basi terdeteksi untuk dihapus: ' . count($toDelete));
        $this->info('Kombinasi artikel+lokasi yang perlu di-recalculate: ' . count($affectedForRecalc));
        $this->info('Grup AMBIGU (tidak bisa dipastikan, dilewati, perlu cek manual): ' . count($toReview));

        if (!empty($toReview)) {
            $this->warn('Daftar grup ambigu:');
            foreach ($toReview as $g) {
                $this->line("  - {$g->movement_transnno} / artikel {$g->artikel_code} / lokasi {$g->location_number} (seharusnya={$g->seharusnya})");
            }
        }

        if (!$doFix) {
            $this->comment('');
            $this->comment('Mode DRY-RUN (default) -- belum ada yang diubah. Jalankan lagi dengan --fix untuk benar-benar menghapus (recalculate dilakukan terpisah, lihat catatan di atas).');
            if (!empty($toDelete)) {
                $this->comment('movement_code yang akan dihapus:');
                $this->line(implode(', ', $toDelete));
            }
            return 0;
        }

        if (empty($toDelete)) {
            $this->info('Tidak ada baris yang perlu dihapus.');
            return 0;
        }

        if (!$this->confirm('Yakin hapus ' . count($toDelete) . ' baris movement basi? (' . count($affectedForRecalc) . ' kombinasi artikel+lokasi akan terdampak, perlu di-recalculate terpisah setelah ini)', false)) {
            $this->comment('Dibatalkan.');
            return 0;
        }

        DB::beginTransaction();
        try {
            // ── BACKUP dulu sebelum hapus. CREATE TABLE + INSERT ini ada DI
            //    DALAM transaksi yang sama dengan delete+recalculate di bawah
            //    (Postgres: DDL transaksional) -- jadi kalau ADA yang gagal,
            //    backup ini ikut ke-rollback juga, tapi itu aman: artinya
            //    delete-nya JUGA batal, jadi tidak ada data yang hilang tanpa
            //    backup. Backup ini baru benar-benar "hidup" begitu seluruh
            //    transaksi commit -- itulah momen delete-nya juga permanen. ──
            if (!\Illuminate\Support\Facades\Schema::hasTable('warehouse_movement_backup_dup_receiving')) {
                DB::statement('CREATE TABLE warehouse_movement_backup_dup_receiving AS SELECT * FROM warehouse_movement WHERE 1=0');
            }

            $backupRows = DB::table('warehouse_movement')->whereIn('movement_code', $toDelete)->get();
            DB::table('warehouse_movement_backup_dup_receiving')->insert(
                $backupRows->map(fn($r) => (array) $r)->all()
            );

            DB::table('warehouse_movement')->whereIn('movement_code', $toDelete)->delete();

            DB::commit();
            $this->info('Selesai. ' . count($toDelete) . ' baris dihapus (backup di tabel warehouse_movement_backup_dup_receiving).');
            $this->comment('');
            $this->comment(count($affectedForRecalc) . ' kombinasi artikel+lokasi TERDAMPAK, BELUM di-recalculate otomatis.');
            $this->comment('Jalankan: php artisan movement:recalculate-ledger  (lalu tambahkan --fix setelah dry-run-nya dicek)');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Gagal, semua perubahan dibatalkan: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
