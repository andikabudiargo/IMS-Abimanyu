<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Konsolidasi stock lokasi ANAK ke lokasi INDUK -- persis pola yang sudah
 * dipakai untuk SPRAY BOOTH (022/023/024 -> 055, dst, 2026-09-10, lihat
 * memory [[spraybooth-consolidation]]). Dipakai sekarang untuk WIP: 038 WIP
 * BUFFING, 039 WIP SANDING, 040 WIP TOUCH UP, 050 WIP BUFFING PLANT 1 ->
 * 012 GUDANG WIP.
 *
 * Kenapa perlu: read-side (CheckStockAnomaly.php, WarehouseControllerv2,
 * ArticleController::movement2) SUDAH generic fold anak->induk lewat
 * stock_location_master.parent_location, dan write-side (resolveStockLocation/
 * getStockLocation di TransferStockController/ActualLoadingController/
 * StockConsumptionController) SUDAH generic juga -- transaksi BARU otomatis
 * ke-post ke induk. Tapi stock LAMA yang ke-posting SEBELUM parent_location
 * di-set (atau sebelum kode fold ada) masih nyangkut di warehouse_stock milik
 * lokasi anak, tidak pernah dipindah. Command ini migrasi data satu kali:
 *   1. Backup warehouse_stock (anak+induk) & warehouse_movement (anak) dulu.
 *   2. SUM article_qty anak+induk per artikel -> tulis ke baris induk.
 *   3. Fold warehouse_movement.location_number anak -> induk (histori).
 *   4. Hapus baris warehouse_stock anak (sudah pindah semua ke induk).
 *   5. Recalculate ledger induk (last_qty + article_qty) pakai
 *      movement:recalculate-ledger --fix yang sudah ada, BUKAN logic baru --
 *      supaya hasil akhirnya konsisten dgn get_last_qty_new()/CheckStockAnomaly.
 *
 * Default: DRY RUN (cuma laporan, backup TIDAK dibuat). Pakai --fix untuk
 * benar-benar menulis.
 */
class ConsolidateWipChildLocations extends Command
{
    protected $signature = 'stock:consolidate-children
        {--parent=012 : location_code induk}
        {--fix : Benar-benar tulis perubahan (default: dry-run)}';

    protected $description = 'Konsolidasi warehouse_stock lokasi anak ke induk (mis. WIP sub-stage 038/039/040/050 -> 012), pola sama seperti spraybooth-consolidation';

    public function handle()
    {
        $parent = (string) $this->option('parent');
        $doFix  = (bool) $this->option('fix');

        $children = DB::table('stock_location_master')
            ->where('parent_location', $parent)
            ->pluck('location_code')
            ->all();

        if (empty($children)) {
            $this->warn("Tidak ada lokasi anak dengan parent_location = {$parent}. Tidak ada yang dikerjakan.");
            return 0;
        }

        $this->info("Induk: {$parent} | Anak: " . implode(', ', $children));

        $childStock = DB::table('warehouse_stock')
            ->whereIn('location_number', $children)
            ->get();

        $totalChildRows = $childStock->count();
        $totalChildQty  = $childStock->sum('article_qty');
        $this->info("Baris warehouse_stock di lokasi anak: {$totalChildRows} | total qty: {$totalChildQty}");

        if ($totalChildRows === 0) {
            $this->info('Tidak ada stock tersisa di lokasi anak. Selesai, tidak ada yang perlu dikonsolidasi.');
            return 0;
        }

        // Kelompokkan per (site_code, article_code) -- gabung sesama anak dulu.
        $bySite = [];
        foreach ($childStock as $row) {
            $key = $row->site_code . '|' . $row->article_code;
            $bySite[$key] = ($bySite[$key] ?? 0) + (float) $row->article_qty;
        }

        $this->info('Kombinasi site+artikel unik di lokasi anak: ' . count($bySite));

        if (!$doFix) {
            $this->comment('');
            $this->comment('Mode DRY-RUN -- belum ada yang diubah. Jalankan lagi dengan --fix untuk apply.');
            $this->comment('Ringkasan di atas menunjukkan skala perubahan yang akan terjadi.');
            return 0;
        }

        DB::beginTransaction();
        try {
            $this->backupBeforeConsolidate($parent, $children);

            $bar = $this->output->createProgressBar(count($bySite));
            $bar->start();

            $affectedArticles = [];
            foreach ($bySite as $key => $sumQty) {
                [$siteCode, $articleCode] = explode('|', $key, 2);
                $affectedArticles[$articleCode] = true;

                $sample = $childStock->first(fn ($r) => $r->site_code === $siteCode && $r->article_code === $articleCode);

                $existingParent = DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $articleCode)
                    ->where('location_number', $parent)
                    ->first();

                if ($existingParent) {
                    DB::table('warehouse_stock')
                        ->where('site_code', $siteCode)
                        ->where('article_code', $articleCode)
                        ->where('location_number', $parent)
                        ->update([
                            'article_qty' => DB::raw('coalesce(article_qty,0) + (' . $sumQty . ')'),
                        ]);
                } else {
                    DB::table('warehouse_stock')->insert([
                        'site_code'       => $siteCode,
                        'article_code'    => $articleCode,
                        'location_number' => $parent,
                        'article_qty'     => $sumQty,
                        'dept_code'       => $sample->dept_code ?? '',
                        'uom'             => $sample->uom ?? null,
                    ]);
                }
                $bar->advance();
            }
            $bar->finish();
            $this->line('');

            // Fold histori movement anak -> induk.
            $movedRows = DB::table('warehouse_movement')
                ->whereIn('location_number', $children)
                ->update(['location_number' => $parent]);
            $this->info("Baris warehouse_movement dipindah dari anak -> induk: {$movedRows}");

            // Hapus baris warehouse_stock anak (sudah pindah semua).
            $deletedRows = DB::table('warehouse_stock')
                ->whereIn('location_number', $children)
                ->delete();
            $this->info("Baris warehouse_stock anak dihapus: {$deletedRows}");

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal, semua perubahan di-rollback: ' . $e->getMessage());
            return 1;
        }

        // Recalculate ledger induk untuk semua artikel terdampak -- pakai
        // command yang sudah ada, supaya last_qty & article_qty akhir
        // dijamin konsisten dengan get_last_qty_new().
        $this->info('Menjalankan movement:recalculate-ledger --fix untuk artikel terdampak di lokasi ' . $parent . '...');
        foreach (array_keys($affectedArticles) as $articleCode) {
            Artisan::call('movement:recalculate-ledger', [
                '--article'  => $articleCode,
                '--location' => $parent,
                '--fix'      => true,
            ]);
            $this->line(Artisan::output());
        }

        $this->info('Selesai.');
        return 0;
    }

    private function backupBeforeConsolidate(string $parent, array $children): void
    {
        $suffix = now()->format('Ymd_His');
        $locs = array_merge([$parent], $children);
        $inList = "'" . implode("','", array_map(fn ($l) => str_replace("'", "''", $l), $locs)) . "'";

        $stockBackupTable = "_bak_warehouse_stock_wip_{$suffix}";
        if (!Schema::hasTable($stockBackupTable)) {
            DB::statement("CREATE TABLE {$stockBackupTable} AS SELECT * FROM warehouse_stock WHERE location_number IN ({$inList})");
        }

        $movementBackupTable = "_bak_wm_wip_fold_{$suffix}";
        $childInList = "'" . implode("','", array_map(fn ($l) => str_replace("'", "''", $l), $children)) . "'";
        if (!Schema::hasTable($movementBackupTable)) {
            DB::statement("CREATE TABLE {$movementBackupTable} AS SELECT * FROM warehouse_movement WHERE location_number IN ({$childInList})");
        }

        $this->info("Backup dibuat: {$stockBackupTable}, {$movementBackupTable}");
    }
}
