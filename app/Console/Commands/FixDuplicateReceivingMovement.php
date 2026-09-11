<?php

namespace App\Console\Commands;

use App\Http\Controllers\ReceivingController;
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
 * Algoritma per grup (movement_transnno, artikel_code, location_number) yang
 * punya >1 baris RECEIVING:
 *   1. Hitung "seharusnya" = SUM(qty+qty_free terkonversi) dari receiving_det
 *      SAAT INI untuk artikel itu di dokumen itu (mengikuti rumus qtyBaseSql
 *      yang sama persis dengan doPosting()).
 *   2. Kalau total semua baris movement (movement_plus) sudah PAS sama dengan
 *      "seharusnya" -> tidak diapa-apakan (ini termasuk pola normal "1 baris
 *      qty=0 + 1 baris qty penuh" yang muncul di banyak dokumen SEHAT, bukan
 *      cuma yang duplikat -- jangan dianggap bug).
 *   3. Kalau lebih besar -> urutkan baris dari PALING BARU (movement_code
 *      DESC), jumlahkan qty non-nol dari situ sampai PAS mencapai
 *      "seharusnya". Baris yang terpakai (baru) -> disimpan. Sisanya (lebih
 *      lama) -> dihapus. Baris qty=0 tidak pernah disentuh (bukan bagian dari
 *      bug ini, tidak mempengaruhi total).
 *   4. Kalau tidak bisa pas PERSIS (jumlah dari baris terbaru melewati/tidak
 *      pernah mencapai "seharusnya") -> JANGAN tebak, masukkan daftar "perlu
 *      review manual", tidak dihapus otomatis.
 *
 * Default: DRY RUN (cuma laporan). Pakai --fix untuk benar-benar menghapus +
 * recalculate last_qty/warehouse_stock/avg_cost artikel+lokasi yang kena.
 */
class FixDuplicateReceivingMovement extends Command
{
    protected $signature = 'receiving:fix-duplicate-movement
                            {--fix : Benar-benar hapus baris basi + recalculate (default: dry-run, cuma laporan)}
                            {--recalc-from=2000-01-01 : Tanggal awal recalculate ledger per artikel+lokasi yang kena (format YYYY-MM-DD)}';

    protected $description = 'Cari & (opsional, via --fix) bersihkan baris warehouse_movement RECEIVING duplikat/basi akibat bug unPosting() lama (sebelum 2026-09-04)';

    public function handle()
    {
        $doFix       = (bool) $this->option('fix');
        $recalcFrom  = (string) $this->option('recalc-from');

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
                ->get(['movement_code', 'movement_plus']);

            $target       = (float) $g->seharusnya;
            $totalNonZero = (float) $rows->sum(fn($r) => (float) $r->movement_plus);

            if (abs($totalNonZero - $target) < 0.0001) {
                continue; // sudah pas -- termasuk pola normal 0+penuh, tidak disentuh
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
                $qty = (float) $r->movement_plus;
                if ($qty <= 0) continue; // baris qty=0 tidak dihitung/disentuh

                if ($resolved) {
                    // target sudah tercapai sebelum baris (lebih lama) ini -> sisanya basi
                    break;
                }

                $running += $qty;
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
                $qty = (float) $r->movement_plus;
                if ($qty <= 0) continue;
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
            $this->comment('Mode DRY-RUN (default) -- belum ada yang diubah. Jalankan lagi dengan --fix untuk benar-benar menghapus + recalculate.');
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

        if (!$this->confirm('Yakin hapus ' . count($toDelete) . ' baris movement basi dan recalculate ' . count($affectedForRecalc) . ' kombinasi artikel+lokasi?', false)) {
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

            /** @var ReceivingController $receivingController */
            $receivingController = app(ReceivingController::class);

            foreach (array_keys($affectedForRecalc) as $key) {
                [$artikel, $lokasi] = explode('|', $key);
                $receivingController->recalculateFromDatePublic($artikel, $lokasi, $recalcFrom);
            }

            DB::commit();
            $this->info('Selesai. ' . count($toDelete) . ' baris dihapus (backup di tabel warehouse_movement_backup_dup_receiving), ' . count($affectedForRecalc) . ' kombinasi artikel+lokasi di-recalculate.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Gagal, semua perubahan dibatalkan: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
