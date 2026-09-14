<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gabungkan warehouse_stock + warehouse_movement dari lokasi CHILD ke lokasi
 * PARENT (stock_location_master.parent_location), untuk kasus di mana
 * parent_location sudah diisi (konsolidasi "diputuskan" secara struktur)
 * tapi data stok/movement historisnya belum benar-benar digabung -- beda
 * dengan migrasi SPRAY BOOTH (055-059) yang sudah tuntas ~2026-09-10.
 *
 * Kasus pemicu: GUDANG WIP (012) -- child 038/039/040/041/050-054 masih
 * punya baris warehouse_stock TERPISAH dengan qty riil (bukan sisa 0), dan
 * warehouse_movement historisnya juga masih tercatat di location_number
 * child, bukan di parent. TransferStockController::getStockLocation() SUDAH
 * fold ke parent untuk transaksi BARU, tapi histori lama tidak pernah
 * diikutkan -- jadi kalau get_last_qty_new()/checker dipanggil di level
 * parent, histori & stok yang masih nyangkut di child TIDAK PERNAH terhitung.
 *
 * SENGAJA TIDAK MENYENTUH stock_adjustment_hdr (OB) -- OB yang sudah
 * terlanjur diposting per-child (mis. WIP BUFFING 31-07-2026) dibiarkan apa
 * adanya. OB yang lebih baru di level PARENT (mis. GUDANG WIP 31-08-2026)
 * akan otomatis jadi anchor yang berlaku untuk query tanggal setelahnya,
 * begitu movement & stock sudah di-fold ke parent -- OB per-child jadi
 * "yatim" (tidak pernah dicari lagi karena tidak ada yang query pakai
 * location_code child), tapi tidak masalah karena OB parent yang lebih baru
 * sudah menggantikannya sebagai anchor.
 *
 * SETELAH command ini (--fix), WAJIB jalankan movement:recalculate-ledger
 * untuk parent (dan artikel-artikel yang terdampak) supaya warehouse_stock
 * akhir benar-benar konsisten dengan ledger gabungan yang baru -- command ini
 * HANYA memindahkan/menjumlahkan data mentah, TIDAK menghitung ulang ledger.
 *
 * Default: DRY RUN. Pakai --fix untuk benar-benar menjalankan.
 */
class ConsolidateChildLocationStock extends Command
{
    protected $signature = 'location:consolidate-children
                            {--parent= : location_code parent, mis. 012}
                            {--children= : Daftar location_code child, pisah koma. Kalau kosong, diambil otomatis dari stock_location_master.parent_location}
                            {--site=HO : site_code}
                            {--fix : Benar-benar jalankan (default: dry-run, cuma laporan). TIDAK auto-recalculate -- jalankan movement:recalculate-ledger setelahnya.}';

    protected $description = 'Gabungkan warehouse_stock + warehouse_movement dari lokasi child ke parent (untuk konsolidasi lokasi yang structure-nya sudah diputuskan tapi data historisnya belum di-fold)';

    public function handle()
    {
        $doFix   = (bool) $this->option('fix');
        $site    = (string) $this->option('site');
        $parent  = (string) $this->option('parent');

        if (!$parent) {
            $this->error('Wajib isi --parent=<location_code>');
            return 1;
        }

        $parentRow = DB::table('stock_location_master')->where('location_code', $parent)->first();
        if (!$parentRow) {
            $this->error("Lokasi parent '$parent' tidak ditemukan di stock_location_master.");
            return 1;
        }

        $childrenOpt = (string) $this->option('children');
        if (trim($childrenOpt) !== '') {
            $children = array_values(array_filter(array_map('trim', explode(',', $childrenOpt))));
        } else {
            $children = DB::table('stock_location_master')
                ->where('parent_location', $parent)
                ->pluck('location_code')
                ->all();
        }

        if (empty($children)) {
            $this->info("Tidak ada child location untuk parent '$parent'. Tidak ada yang perlu digabung.");
            return 0;
        }

        $this->info("Parent: {$parent} ({$parentRow->location_name})");
        $this->info('Children: ' . implode(', ', $children));

        // ── 1. Stok per artikel yang masih nyangkut di child ──
        $childStock = DB::table('warehouse_stock')
            ->where('site_code', $site)
            ->whereIn('location_number', $children)
            ->select('article_code', 'location_number', 'article_qty', 'avg_price', 'uom', 'dept_code')
            ->get();

        $byArticle = [];
        foreach ($childStock as $row) {
            $code = $row->article_code;
            if (!isset($byArticle[$code])) {
                $byArticle[$code] = ['qty' => 0.0, 'uom' => null, 'dept_code' => null, 'priceQtySum' => 0.0, 'priceWeighted' => 0.0];
            }
            $qty = (float) $row->article_qty;
            $byArticle[$code]['qty'] += $qty;
            $byArticle[$code]['uom'] = $byArticle[$code]['uom'] ?? $row->uom;
            $byArticle[$code]['dept_code'] = $byArticle[$code]['dept_code'] ?? $row->dept_code;
            if ($qty > 0) {
                $byArticle[$code]['priceQtySum'] += $qty;
                $byArticle[$code]['priceWeighted'] += $qty * (float) $row->avg_price;
            }
        }

        // ── 2. Baris movement historis yang masih di child ──
        $movementCount = DB::table('warehouse_movement')
            ->where('site_code', $site)
            ->whereIn('location_number', $children)
            ->count();

        $this->info('Artikel dengan stok di lokasi child: ' . count($byArticle));
        $this->info('Total qty yang akan digabung ke parent: ' . array_sum(array_column($byArticle, 'qty')));
        $this->info('Baris warehouse_movement historis di child yang akan dipindah ke parent: ' . $movementCount);

        if (!$doFix) {
            $this->comment('');
            $this->comment('Mode DRY-RUN -- belum ada yang diubah. Jalankan lagi dengan --fix untuk benar-benar menggabungkan.');
            $this->comment('Setelah --fix, WAJIB jalankan: php artisan movement:recalculate-ledger --location=' . $parent);
            if (count($byArticle) > 0) {
                $this->comment('Detail per artikel (qty yang akan ditambahkan ke parent):');
                foreach ($byArticle as $code => $d) {
                    $this->line("  - {$code}: +{$d['qty']}");
                }
            }
            return 0;
        }

        if (!$this->confirm(
            'Yakin gabungkan ' . count($byArticle) . ' artikel dan pindahkan ' . $movementCount
            . ' baris movement dari [' . implode(', ', $children) . "] ke parent {$parent}?",
            false
        )) {
            $this->comment('Dibatalkan.');
            return 0;
        }

        DB::beginTransaction();
        try {
            $stamp = now()->format('YmdHis');

            // ── Backup dulu sebelum ubah apa pun ──
            $this->backupTable('warehouse_stock', "warehouse_stock_bak_consolidate_{$stamp}", function ($q) use ($site, $children) {
                return $q->where('site_code', $site)->whereIn('location_number', $children);
            });
            $this->backupTable('warehouse_movement', "warehouse_movement_bak_consolidate_{$stamp}", function ($q) use ($site, $children) {
                return $q->where('site_code', $site)->whereIn('location_number', $children);
            });

            // ── Fold stok: tambahkan ke parent (insert kalau belum ada baris utk artikel itu, update kalau sudah) ──
            foreach ($byArticle as $code => $d) {
                if (abs($d['qty']) < 0.0000001) continue;

                $existing = DB::table('warehouse_stock')
                    ->where('site_code', $site)
                    ->where('article_code', $code)
                    ->where('location_number', $parent)
                    ->first();

                $avgPrice = $d['priceQtySum'] > 0 ? ($d['priceWeighted'] / $d['priceQtySum']) : 0;

                if ($existing) {
                    DB::table('warehouse_stock')
                        ->where('site_code', $site)
                        ->where('article_code', $code)
                        ->where('location_number', $parent)
                        ->update([
                            'article_qty' => DB::raw('coalesce(article_qty,0) + ' . $d['qty']),
                        ]);
                } else {
                    DB::table('warehouse_stock')->insert([
                        'site_code'       => $site,
                        'article_code'    => $code,
                        'location_number' => $parent,
                        'article_qty'     => $d['qty'],
                        'avg_price'       => $avgPrice,
                        'uom'             => $d['uom'] ?? 'PCS',
                        'dept_code'       => $d['dept_code'] ?? '',
                    ]);
                }
            }

            // ── Hapus baris warehouse_stock child (sudah dipindah ke parent) ──
            DB::table('warehouse_stock')
                ->where('site_code', $site)
                ->whereIn('location_number', $children)
                ->delete();

            // ── Pindahkan histori movement child -> parent ──
            DB::table('warehouse_movement')
                ->where('site_code', $site)
                ->whereIn('location_number', $children)
                ->update(['location_number' => $parent]);

            DB::commit();

            $this->info('Selesai. Backup di tabel:');
            $this->line("  - warehouse_stock_bak_consolidate_{$stamp}");
            $this->line("  - warehouse_movement_bak_consolidate_{$stamp}");
            $this->comment('');
            $this->warn('WAJIB jalankan sekarang: php artisan movement:recalculate-ledger --location=' . $parent);
            $this->warn('(cek dry-run dulu, pastikan tidak ada MISMATCH, baru --fix)');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Gagal, semua perubahan dibatalkan: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function backupTable(string $sourceTable, string $backupTable, callable $whereCallback): void
    {
        if (!Schema::hasTable($backupTable)) {
            DB::statement("CREATE TABLE {$backupTable} AS SELECT * FROM {$sourceTable} WHERE 1=0");
        }
        $rows = $whereCallback(DB::table($sourceTable))->get();
        if ($rows->isEmpty()) return;
        $rows->map(fn ($r) => (array) $r)->chunk(500)->each(function ($chunk) use ($backupTable) {
            DB::table($backupTable)->insert($chunk->all());
        });
    }
}
