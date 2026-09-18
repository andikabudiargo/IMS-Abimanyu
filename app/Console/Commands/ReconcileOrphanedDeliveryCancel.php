<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaiki data yang sudah terlanjur rusak akibat bug di
 * DeliveryController::destroy()/revision() (diperbaiki 2026-09-18): gate
 * reverse-stock dulu cuma cek status == '4' (POSTED), jadi DN yang sudah
 * lanjut ke status lain (mis. '8' RECEIVED) sebelum dibatalkan/direvisi
 * lolos tanpa pernah di-reverse -- delivery_hdr sudah di-rename/di-supersede,
 * tapi baris warehouse_movement ASLI (masih pakai nomor LAMA) tidak pernah
 * dihapus. Setiap pengecekan is_canceled di seluruh sistem (get_last_qty_new,
 * CheckStockAnomaly, grid Movement) mencari header lewat
 * "WHERE nomor = movement_transnno" -- karena headernya sudah ganti nomor/
 * disupersede, movement itu dianggap hidup SELAMANYA.
 *
 * DUA POLA ORPHAN yang dicari:
 *   A. CANCEL: delivery_hdr di-rename ke "{nomor}(C)" + status=5, tapi
 *      warehouse_movement masih ada dgn movement_transnno = nomor ASLI
 *      (sebelum di-rename).
 *   B. REVISI: delivery_hdr origin statusnya '10' (REVISI, nomornya TIDAK
 *      di-rename) dan sudah punya dokumen revisi penerus (origin_delivery_
 *      number = nomor origin), tapi warehouse_movement origin masih ada --
 *      berarti qty lama origin ganda dihitung bersama qty baru hasil revisi.
 *
 * FIX: hapus baris warehouse_movement yang orphan itu (backup dulu), lalu
 * panggil `movement:recalculate-ledger --fix` per artikel+lokasi yang
 * terdampak (reuse command yang sudah ada & floor-aware, bukan menulis ulang
 * logic recalculate).
 *
 * Default: DRY RUN (cuma laporan). Pakai --fix untuk benar-benar menulis.
 */
class ReconcileOrphanedDeliveryCancel extends Command
{
    protected $signature = 'delivery:reconcile-orphaned-cancel {--fix : Benar-benar tulis perubahan (default: dry-run)}';

    protected $description = 'Cari & perbaiki DN yang sudah dibatalkan/direvisi tapi movement aslinya belum pernah di-reverse (bug status-gate DeliveryController)';

    public function handle()
    {
        $doFix = (bool) $this->option('fix');

        $canceledOrphans = $this->findCanceledOrphans();
        $revisionOrphans = $this->findRevisionOrphans();

        $this->info('Pola A (CANCEL, nomor di-rename "(C)") -- ditemukan: ' . count($canceledOrphans));
        foreach ($canceledOrphans as $row) {
            $this->line("  - {$row->canceled_number}  (nomor asli: {$row->original_number}, canceled_at: {$row->updated_at})");
        }

        $this->info('Pola B (REVISI, origin belum ke-reverse) -- ditemukan: ' . count($revisionOrphans));
        foreach ($revisionOrphans as $row) {
            $this->line("  - {$row->delivery_number}  (revisi ke: {$row->rev_number})");
        }

        $affectedPairs = [];

        foreach ($canceledOrphans as $row) {
            $affectedPairs = array_merge($affectedPairs, $this->processOrphan($row->original_number, $doFix));
        }
        foreach ($revisionOrphans as $row) {
            $affectedPairs = array_merge($affectedPairs, $this->processOrphan($row->delivery_number, $doFix));
        }

        if (!$doFix) {
            $this->comment('');
            $this->comment('Mode DRY-RUN -- belum ada yang diubah. Jalankan lagi dengan --fix untuk apply.');
            return 0;
        }

        // Dedup pasangan artikel+lokasi, lalu recalculate pakai command yang sudah ada.
        $unique = collect($affectedPairs)->unique(fn ($p) => $p['article'] . '|' . $p['location'])->values();
        $this->info("Menjalankan movement:recalculate-ledger --fix untuk {$unique->count()} kombinasi artikel+lokasi terdampak...");

        foreach ($unique as $p) {
            Artisan::call('movement:recalculate-ledger', [
                '--article'  => $p['article'],
                '--location' => $p['location'],
                '--fix'      => true,
            ]);
            $this->line(Artisan::output());
        }

        $this->info('Selesai.');
        return 0;
    }

    /** Pola A: header sudah di-rename "(C)" + status CANCELED (5), movement asli (nomor lama) masih ada. */
    private function findCanceledOrphans()
    {
        return DB::select("
            SELECT dh.delivery_number AS canceled_number,
                   regexp_replace(dh.delivery_number, '\\(C\\)$', '') AS original_number,
                   dh.updated_at
            FROM delivery_hdr dh
            WHERE dh.delivery_number LIKE '%(C)'
              AND dh.status = '5'
              AND EXISTS (
                  SELECT 1 FROM warehouse_movement wm
                  WHERE wm.movement_transnno = regexp_replace(dh.delivery_number, '\\(C\\)$', '')
                    AND wm.movement_type = 'DELIVERY'
              )
            ORDER BY dh.updated_at DESC
        ");
    }

    /** Pola B: origin status REVISI (10, nomor TIDAK di-rename), sudah punya penerus, movement origin masih ada. */
    private function findRevisionOrphans()
    {
        return DB::select("
            SELECT origin.delivery_number,
                   (SELECT rev.delivery_number FROM delivery_hdr rev
                    WHERE rev.origin_delivery_number = origin.delivery_number
                    ORDER BY rev.id DESC LIMIT 1) AS rev_number
            FROM delivery_hdr origin
            WHERE origin.status = '10'
              AND EXISTS (
                  SELECT 1 FROM delivery_hdr rev WHERE rev.origin_delivery_number = origin.delivery_number
              )
              AND EXISTS (
                  SELECT 1 FROM warehouse_movement wm
                  WHERE wm.movement_transnno = origin.delivery_number
                    AND wm.movement_type = 'DELIVERY'
              )
            ORDER BY origin.updated_at DESC
        ");
    }

    /**
     * Hapus movement orphan milik $dnNumber (backup dulu kalau --fix), kembalikan
     * daftar (article, location) yang perlu di-recalculate.
     */
    private function processOrphan(string $dnNumber, bool $doFix): array
    {
        $rows = DB::table('warehouse_movement')
            ->where('movement_transnno', $dnNumber)
            ->where('movement_type', 'DELIVERY')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $pairs = $rows->map(fn ($r) => ['article' => $r->artikel_code, 'location' => $r->location_number])->all();

        if ($doFix) {
            $this->backupMovementRows($rows);
            DB::table('warehouse_movement')
                ->where('movement_transnno', $dnNumber)
                ->where('movement_type', 'DELIVERY')
                ->delete();
            $this->line("  Terhapus {$rows->count()} baris movement orphan untuk {$dnNumber}");
        }

        return $pairs;
    }

    private function backupMovementRows($rows): void
    {
        if (!Schema::hasTable('warehouse_movement_orphan_cancel_backup')) {
            DB::statement('CREATE TABLE warehouse_movement_orphan_cancel_backup AS SELECT *, now() AS backed_up_at FROM warehouse_movement WHERE 1=0');
        }
        $now = now();
        DB::table('warehouse_movement_orphan_cancel_backup')->insert(
            $rows->map(function ($r) use ($now) {
                $arr = (array) $r;
                $arr['backed_up_at'] = $now;
                return $arr;
            })->all()
        );
    }
}
