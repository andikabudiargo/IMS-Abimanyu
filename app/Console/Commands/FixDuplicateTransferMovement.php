<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bersihkan baris warehouse_movement bertipe TRANSFER/SUPPLY yang
 * duplikat/basi akibat double-posting di TransferStockController.
 *
 * Latar belakang: ditemukan lewat investigasi terpisah jauh sebelum command
 * ini dibuat (waktu itu: 18 dokumen / 186 grup artikel+lokasi / ~3226.63 qty
 * total kelebihan, system-wide) -- BEDA root cause dari bug RECEIVING yang
 * sudah dibersihkan FixDuplicateReceivingMovement.php (yang itu pola
 * reversal-tidak-lengkap dari unPosting() lama; ini pola dokumen ke-post DUA
 * KALI PERSIS IDENTIK, jarak created_at cuma beberapa detik -- kemungkinan
 * double-click submit atau retry tanpa guard). Terbukti nyata lewat kasus
 * artikel 1007547 @ 005, dokumen TRF/2026/VIII/0453: transfer_stock_det cuma
 * 1 baris (qty=1.3), tapi warehouse_movement ada 2 baris SUPPLY -1.3 identik
 * (created_at beda 4 detik) -- baru ketahuan setelah dedup keep-latest
 * dihapus dari get_last_qty_new() (dulu dedup itu "kebetulan" menutupi bug
 * ini dengan membuang salah satu baris).
 *
 * PENGECUALIAN: dokumen dengan location_to = '037' (Gudang NG RM) TIDAK
 * dicek -- transfer FG ke lokasi itu dipecah jadi banyak baris movement
 * komponen RM by BOM (1 baris transfer_stock_det bisa menghasilkan BANYAK
 * baris warehouse_movement untuk artikel BERBEDA-BEDA, lihat
 * TransferStockController::resolveTransferLines()) -- pola "1 det = 1
 * movement" tidak berlaku di sini, jadi TIDAK aman dideteksi otomatis
 * dengan command ini (soalnya artikel di movement bisa saja bukan artikel
 * yang ada di det, false positive).
 *
 * Algoritma per grup (movement_transnno, artikel_code, location_number,
 * movement_type) yang punya >1 baris TRANSFER/SUPPLY:
 *   1. "seharusnya" = SUM(qty) dari transfer_stock_det SAAT INI untuk artikel
 *      itu di dokumen itu (qty di det TIDAK dikonversi, dipakai apa adanya --
 *      lihat TransferStockController::getTransferDetails()).
 *   2. Tentukan arah grup ini OUT (dari lokasi, movement_min dominan) atau IN
 *      (ke lokasi, movement_plus dominan) dari data movement-nya sendiri --
 *      target NET jadi -seharusnya (OUT) atau +seharusnya (IN).
 *   3. Kalau NET semua baris (SUM(plus-min)) sudah PAS sama dengan target ->
 *      tidak diapa-apakan.
 *   4. Kalau tidak pas -> urutkan baris dari PALING BARU (movement_code DESC),
 *      jumlahkan NET dari situ sampai PAS mencapai target. Baris yang
 *      terpakai (baru) -> disimpan. Sisanya (lebih lama, basi) -> dihapus.
 *      Baris yang net-nya benar-benar nol (plus=0 DAN min=0) tidak disentuh.
 *   5. Kalau tidak bisa pas PERSIS -> masuk daftar "perlu review manual",
 *      tidak dihapus otomatis.
 *
 * Default: DRY RUN. Pakai --fix untuk benar-benar menghapus (TIDAK
 * auto-recalculate -- jalankan movement:recalculate-ledger sendiri sesudahnya
 * untuk kombinasi artikel+lokasi yang terdampak).
 */
class FixDuplicateTransferMovement extends Command
{
    protected $signature = 'transfer:fix-duplicate-movement
                            {--fix : Benar-benar hapus baris basi (default: dry-run, cuma laporan). TIDAK auto-recalculate -- jalankan movement:recalculate-ledger setelahnya.}';

    protected $description = 'Cari & (opsional, via --fix) bersihkan baris warehouse_movement TRANSFER/SUPPLY duplikat akibat double-posting TransferStockController';

    private const NG_RM_LOCATION = '037';

    public function handle()
    {
        $doFix = (bool) $this->option('fix');

        $groups = DB::select("
            WITH dup_groups AS (
                SELECT movement_transnno, artikel_code, location_number, movement_type,
                       COUNT(*) AS jumlah_baris,
                       SUM(movement_plus) AS total_plus,
                       SUM(movement_min)  AS total_min
                FROM warehouse_movement
                WHERE movement_type IN ('TRANSFER','SUPPLY')
                GROUP BY movement_transnno, artikel_code, location_number, movement_type
                HAVING COUNT(*) > 1
            ),
            expected AS (
                SELECT tr_number AS movement_transnno, article_code AS artikel_code,
                       SUM(COALESCE(qty,0)) AS seharusnya
                FROM transfer_stock_det
                GROUP BY tr_number, article_code
            ),
            ngrm_docs AS (
                SELECT tr_number FROM transfer_stock_hdr WHERE location_to = ?
            )
            SELECT g.movement_transnno, g.artikel_code, g.location_number, g.movement_type,
                   g.jumlah_baris, g.total_plus, g.total_min,
                   COALESCE(e.seharusnya, 0) AS seharusnya
            FROM dup_groups g
            LEFT JOIN expected e
                ON e.movement_transnno = g.movement_transnno AND e.artikel_code = g.artikel_code
            WHERE g.movement_transnno NOT IN (SELECT tr_number FROM ngrm_docs)
            ORDER BY g.movement_transnno, g.artikel_code
        ", [self::NG_RM_LOCATION]);

        $this->info('Total grup terdeteksi (jumlah_baris > 1, di luar dokumen ke Gudang NG RM): ' . count($groups));

        $toDelete          = [];
        $toReview          = [];
        $affectedForRecalc = [];

        foreach ($groups as $g) {
            $rows = DB::table('warehouse_movement')
                ->where('movement_transnno', $g->movement_transnno)
                ->where('artikel_code', $g->artikel_code)
                ->where('location_number', $g->location_number)
                ->where('movement_type', $g->movement_type)
                ->orderBy('movement_code', 'desc')
                ->get(['movement_code', 'movement_plus', 'movement_min']);

            $seharusnya = (float) $g->seharusnya;
            $isOutLeg   = (float) $g->total_min > (float) $g->total_plus;
            $target     = $isOutLeg ? -$seharusnya : $seharusnya;

            $totalNet = (float) $rows->sum(fn($r) => (float) $r->movement_plus - (float) $r->movement_min);

            if (abs($totalNet - $target) < 0.0001) {
                continue; // NET sudah pas -- tidak disentuh
            }

            $running  = 0.0;
            $keep     = [];
            $resolved = abs($target) < 0.0001;

            foreach ($rows as $r) {
                $net = (float) $r->movement_plus - (float) $r->movement_min;
                if (abs($net) < 0.0001) continue;

                if ($resolved) break;

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
        $this->info('Kombinasi artikel+lokasi yang TERDAMPAK (perlu recalculate terpisah): ' . count($affectedForRecalc));
        $this->info('Grup AMBIGU (tidak bisa dipastikan, dilewati, perlu cek manual): ' . count($toReview));

        if (!empty($toReview)) {
            $this->warn('Daftar grup ambigu:');
            foreach ($toReview as $g) {
                $this->line("  - {$g->movement_transnno} / artikel {$g->artikel_code} / lokasi {$g->location_number} / {$g->movement_type} (seharusnya={$g->seharusnya}, total_plus={$g->total_plus}, total_min={$g->total_min})");
            }
        }

        if (!$doFix) {
            $this->comment('');
            $this->comment('Mode DRY-RUN (default) -- belum ada yang diubah. Jalankan lagi dengan --fix untuk benar-benar menghapus.');
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
            if (!Schema::hasTable('warehouse_movement_backup_dup_transfer')) {
                DB::statement('CREATE TABLE warehouse_movement_backup_dup_transfer AS SELECT * FROM warehouse_movement WHERE 1=0');
            }

            $backupRows = DB::table('warehouse_movement')->whereIn('movement_code', $toDelete)->get();
            DB::table('warehouse_movement_backup_dup_transfer')->insert(
                $backupRows->map(fn($r) => (array) $r)->all()
            );

            DB::table('warehouse_movement')->whereIn('movement_code', $toDelete)->delete();

            DB::commit();
            $this->info('Selesai. ' . count($toDelete) . ' baris dihapus (backup di tabel warehouse_movement_backup_dup_transfer).');
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
