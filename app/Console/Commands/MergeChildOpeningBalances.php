<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gabungkan OPENING BALANCE yang dulu dipecah per lokasi ANAK (mis. WIP
 * sub-stage 038/039/040/050, sebelum ada fold ke induk 012) jadi SATU OB
 * gabungan di lokasi INDUK.
 *
 * Kenapa perlu (ditemukan 2026-09-18/21 lewat CheckStockAnomaly + MISMATCH
 * report dari movement:recalculate-ledger): get_last_qty_new() memilih OB
 * TERBARU per (article_code, hdr.location_code) TANPA fold -- kalau OB
 * aslinya tersimpan location_code='038' dst, dia TIDAK PERNAH ketemu waktu
 * dipanggil dengan location='012' (parent). CheckStockAnomaly.php sendiri
 * SUDAH fold OB lewat ob_folded CTE (makanya widget-nya sempat kelihatan
 * "benar" tapi get_last_qty_new()/movement:recalculate-ledger tidak).
 * RecalculateArticleLocationLedger (walk manual) juga tidak bisa handle >1 OB
 * di tanggal yang sama di 1 lokasi -- dia cuma ambil satu, buang sisanya.
 *
 * Root fix: satu OB gabungan per (artikel, tanggal) di lokasi induk, OB anak
 * lama di-CANCEL (status=5) supaya tidak dihitung dobel oleh CheckStockAnomaly
 * (yang masih fold+sum OB anak). get_last_qty_new()/CheckStockAnomaly tidak
 * peduli status hdr utk exclude dari net-movement (EXISTS ke adj_code, bukan
 * status), jadi aman.
 *
 * FIX SUSULAN (2026-09-21, ditemukan lewat MISMATCH report
 * movement:recalculate-ledger): "walk manual" di RecalculateArticleLocationLedger
 * TIDAK baca stock_adjustment_hdr sama sekali -- dia deteksi "hari ini ada OB"
 * murni dari baris warehouse_movement yang is_ob_tied. Baris movement OB ANAK
 * yang lama (3 per kombinasi, sudah di-fold ke induk oleh stock:consolidate-
 * children sebelumnya) TETAP ADA di warehouse_movement walau header OB-nya
 * sudah di-CANCEL di sini -- walk manual masih "ketemu" 3 baris itu dan salah
 * pilih (last-one-wins), padahal get_last_qty_new() sendiri sudah benar
 * (baca stock_adjustment_det langsung, tidak peduli movement). Makanya baris
 * movement OB anak yang lama WAJIB dihapus juga di sini -- OB gabungan yang
 * baru TIDAK perlu baris movement pengganti (get_last_qty_new() tidak
 * butuh itu).
 *
 * Default: DRY RUN. Pakai --fix untuk benar-benar menulis.
 */
class MergeChildOpeningBalances extends Command
{
    protected $signature = 'stock:merge-child-ob
        {--parent=012 : location_code induk}
        {--children=038,039,040,050 : location_code anak, pisah koma}
        {--fix : Benar-benar tulis perubahan (default: dry-run)}';

    protected $description = 'Gabungkan OPENING BALANCE anak (per lokasi, sebelum fold) jadi satu OB gabungan di lokasi induk';

    public function handle()
    {
        $parent   = (string) $this->option('parent');
        $children = array_filter(array_map('trim', explode(',', (string) $this->option('children'))));
        $doFix    = (bool) $this->option('fix');
        $username = 'system-merge-ob';

        $childObs = DB::select("
            SELECT det.article_code, hdr.adj_date, hdr.location_code, hdr.adj_code,
                   det.stock_after, det.uom, hdr.id AS hdr_id
            FROM stock_adjustment_hdr hdr
            JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
            WHERE hdr.adj_type = 'OPENING BALANCE'
              AND hdr.status = '4'
              AND hdr.location_code = ANY(?)
            ORDER BY det.article_code, hdr.adj_date
        ", ['{' . implode(',', $children) . '}']);

        $grouped = [];
        foreach ($childObs as $row) {
            $key = $row->article_code . '|' . $row->adj_date;
            $grouped[$key]['article_code'] = $row->article_code;
            $grouped[$key]['adj_date']     = $row->adj_date;
            $grouped[$key]['uom']          = $row->uom;
            $grouped[$key]['rows'][]       = $row;
            $grouped[$key]['sum']          = ($grouped[$key]['sum'] ?? 0) + (float) $row->stock_after;
        }

        // Hanya yang benar-benar kepecah di >1 lokasi anak -- kalau cuma 1
        // baris, tidak ada yang perlu digabung (biarkan apa adanya).
        $toMerge = array_filter($grouped, fn ($g) => count($g['rows']) > 1);

        $this->info('Kombinasi (artikel, tanggal) yang perlu digabung: ' . count($toMerge));
        $totalOldRows = array_sum(array_map(fn ($g) => count($g['rows']), $toMerge));
        $this->info('Total baris OB anak yang akan dibatalkan: ' . $totalOldRows);

        if (!$doFix) {
            $this->comment('');
            $this->comment('Mode DRY-RUN -- belum ada yang diubah. Contoh 5 kombinasi pertama:');
            foreach (array_slice($toMerge, 0, 5, true) as $g) {
                $rincian = implode(', ', array_map(fn ($r) => "{$r->location_code}={$r->stock_after}", $g['rows']));
                $this->line("  - {$g['article_code']} @ {$g['adj_date']}: {$rincian} -> gabungan {$g['sum']}");
            }
            $this->comment('Jalankan lagi dengan --fix untuk apply.');
            return 0;
        }

        DB::beginTransaction();
        try {
            $this->backupBeforeMerge($children);

            // Satu header baru per TANGGAL (bisa banyak artikel dalam satu
            // header, persis pola dokumen OB normal di aplikasi).
            $byDate = [];
            foreach ($toMerge as $g) {
                $byDate[$g['adj_date']][] = $g;
            }

            $bar = $this->output->createProgressBar(count($toMerge));
            $bar->start();

            foreach ($byDate as $adjDate => $groups) {
                $periode = (int) explode('-', $adjDate)[1];
                $adjCode = 'ADJ-MERGE-' . $parent . '-' . str_replace('-', '', $adjDate);

                // Kalau sudah pernah dijalankan sebelumnya (re-run), jangan
                // duplikat insert hdr/det -- TAPI tetap jalankan langkah
                // cancel-OB-lama + hapus-movement-lama di bawah (idempotent,
                // aman diulang), soalnya itu yang paling sering ketinggalan
                // kalau run sebelumnya sempat gagal di tengah/belum lengkap.
                $headerAlreadyExists = DB::table('stock_adjustment_hdr')->where('adj_code', $adjCode)->exists();
                if ($headerAlreadyExists) {
                    $this->warn("Header {$adjCode} sudah ada -- lewati insert, tapi tetap jalankan cancel+cleanup movement.");
                } else {
                    DB::table('stock_adjustment_hdr')->insert([
                        'adj_code'      => $adjCode,
                        'adj_date'      => $adjDate,
                        'adj_type'      => 'OPENING BALANCE',
                        'location_code' => $parent,
                        'description'   => 'Gabungan OB lokasi anak (migrasi konsolidasi WIP)',
                        'note'          => 'Auto-generated: gabungan OB ' . implode('/', $children) . " tgl {$adjDate}, sebelum di-fold ke {$parent}",
                        'periode'       => $periode,
                        'direction'     => '+',
                        'status'        => '4',
                        'rev_no'        => 0,
                        'created_by'    => $username,
                        'updated_by'    => $username,
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);
                }

                $detRows = [];
                foreach ($groups as $g) {
                    if (!$headerAlreadyExists) {
                        $detRows[] = [
                            'adj_code'       => $adjCode,
                            'article_code'   => $g['article_code'],
                            'uom'            => $g['uom'],
                            'direction'      => $g['sum'] >= 0 ? '+' : '-',
                            'stock_before'   => 0,
                            'qty_adjustment' => abs($g['sum']),
                            'stock_after'    => $g['sum'],
                            'notes'          => 'Gabungan: ' . implode(', ', array_map(fn ($r) => "{$r->location_code}={$r->stock_after}", $g['rows'])),
                            'created_by'     => $username,
                            'updated_by'     => $username,
                            'created_at'     => date('Y-m-d H:i:s'),
                            'updated_at'     => date('Y-m-d H:i:s'),
                        ];
                    }

                    // Batalkan OB anak lama -- supaya CheckStockAnomaly (yang
                    // masih fold+sum OB anak) tidak menghitung dobel dengan
                    // OB gabungan yang baru ini. Idempotent (UPDATE ke status
                    // yang sama tidak masalah kalau diulang).
                    $oldHdrIds   = collect($g['rows'])->pluck('hdr_id')->unique()->all();
                    $oldAdjCodes = collect($g['rows'])->pluck('adj_code')->unique()->all();

                    DB::table('stock_adjustment_hdr')
                        ->whereIn('id', $oldHdrIds)
                        ->update([
                            'status'     => '5',
                            'note'       => DB::raw("COALESCE(note,'') || ' (Auto-CANCELED: digabung ke {$adjCode} di lokasi {$parent})'"),
                            'updated_by' => $username,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                    // Hapus baris warehouse_movement yang terkait OB anak
                    // lama (sudah di-fold ke $parent oleh stock:consolidate-
                    // children) -- WAJIB, supaya walk manual RecalculateArticleLocationLedger
                    // tidak lagi salah deteksi "hari ini ada OB" dari baris
                    // basi ini. OB gabungan baru tidak butuh baris pengganti.
                    // Idempotent (WHERE tidak match lagi kalau sudah terhapus).
                    DB::table('warehouse_movement')
                        ->where('artikel_code', $g['article_code'])
                        ->where('location_number', $parent)
                        ->where('movement_type', 'ADJUSTMENT')
                        ->whereIn('movement_transnno', $oldAdjCodes)
                        ->delete();
                }

                if (!empty($detRows)) {
                    DB::table('stock_adjustment_det')->insert($detRows);
                }

                $bar->advance(count($groups));
            }
            $bar->finish();
            $this->line('');

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal, semua perubahan di-rollback: ' . $e->getMessage());
            return 1;
        }

        $this->info('Selesai. OB anak lama dibatalkan (status=5), OB gabungan baru dibuat di lokasi ' . $parent . '.');
        $this->comment('Jalankan movement:recalculate-ledger --location=' . $parent . ' --fix setelah ini untuk menulis ulang article_qty yang terdampak.');
        return 0;
    }

    private function backupBeforeMerge(array $children): void
    {
        $suffix = now()->format('Ymd_His');
        $inList = "'" . implode("','", array_map(fn ($l) => str_replace("'", "''", $l), $children)) . "'";

        $hdrBackup = "_bak_stock_adjustment_hdr_ob_merge_{$suffix}";
        if (!Schema::hasTable($hdrBackup)) {
            DB::statement("CREATE TABLE {$hdrBackup} AS
                SELECT hdr.* FROM stock_adjustment_hdr hdr
                WHERE hdr.adj_type = 'OPENING BALANCE' AND hdr.location_code IN ({$inList})");
        }

        $detBackup = "_bak_stock_adjustment_det_ob_merge_{$suffix}";
        if (!Schema::hasTable($detBackup)) {
            DB::statement("CREATE TABLE {$detBackup} AS
                SELECT det.* FROM stock_adjustment_det det
                JOIN stock_adjustment_hdr hdr ON hdr.adj_code = det.adj_code
                WHERE hdr.adj_type = 'OPENING BALANCE' AND hdr.location_code IN ({$inList})");
        }

        // Backup baris warehouse_movement OB anak (sudah di-fold ke induk
        // sebelumnya) SEBELUM dihapus -- adj_code-nya masih milik OB anak
        // yang tersimpan di stock_adjustment_hdr location_code IN (anak).
        $wmBackup = "_bak_wm_ob_merge_{$suffix}";
        if (!Schema::hasTable($wmBackup)) {
            DB::statement("CREATE TABLE {$wmBackup} AS
                SELECT wm.* FROM warehouse_movement wm
                WHERE wm.movement_type = 'ADJUSTMENT'
                  AND wm.movement_transnno IN (
                      SELECT hdr.adj_code FROM stock_adjustment_hdr hdr
                      WHERE hdr.adj_type = 'OPENING BALANCE' AND hdr.location_code IN ({$inList})
                  )");
        }

        $this->info("Backup dibuat: {$hdrBackup}, {$detBackup}, {$wmBackup}");
    }
}
