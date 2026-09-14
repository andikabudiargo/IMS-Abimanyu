<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recalculate warehouse_movement.last_qty + warehouse_stock.article_qty dari
 * ledger, per (artikel_code, location_number), setelah get_last_qty_new()
 * dibetulkan (lihat scratch_get_last_qty_new_fix.sql).
 *
 * Kenapa command ini perlu: selama get_last_qty_new() masih bug, SEMUA
 * OPENING BALANCE yang diposting lewat StockAdjustmentController::qtyAt()
 * berpotensi menghitung delta yang salah (baseline "saldo current" yang
 * dibaca salah), sehingga warehouse_stock TIDAK benar-benar terkoreksi ke
 * stock_after yang diinput petugas -- meski stock_after itu sendiri kemungkinan
 * besar benar (hasil hitung fisik). Membetulkan function saja tidak
 * memperbaiki data yang SUDAH terlanjur posting dengan delta salah itu.
 *
 * Strategi rekalkulasi per kombinasi (artikel, lokasi):
 *   1. Ambil semua baris warehouse_movement setelah cutoff migrasi (30 Juni
 *      2026), dengan exclusion filter DAN status-cancel-per-modul PERSIS SAMA
 *      seperti get_last_qty_new() versi baru (supaya konsisten -- SQL-nya
 *      sengaja disalin dari situ, bukan ditulis ulang dari nol).
 *   2. Dikelompokkan per TANGGAL (bukan per baris). Kalau di tanggal itu ada
 *      baris OPENING BALANCE yang masih berlaku (bukan yang di-cancel), OB
 *      ITU jadi nilai akhir untuk SELURUH hari itu -- baris TRANSFER/SUPPLY/
 *      RECEIVING lain di tanggal yang SAMA (sebelum ATAU sesudah OB itu
 *      diposting) diabaikan sepenuhnya, dianggap sudah termasuk dalam hasil
 *      stock opname (keputusan bisnis, dikonfirmasi user 2026-09-14). SYSTEM
 *      CORRECTION (adj_type lain, BUKAN OPENING BALANCE) TIDAK ikut aturan
 *      ini -- tetap dihitung sebagai net_value biasa. Kalau TIDAK ada OB di
 *      tanggal itu, baru "anchor" running balance di-reset dengan memanggil
 *      get_last_qty_new(artikel, tanggal-sebelumnya, ...), lalu baris-baris
 *      hari itu dijumlah biasa.
 *   3. Sebagai pengaman, SETIAP tanggal (bukan cuma pas pergantian) dicocokkan
 *      hasil jalan manual vs get_last_qty_new() untuk tanggal itu sendiri --
 *      kalau tidak cocok (selisih > 0.01), dilaporkan sebagai MISMATCH supaya
 *      dicek manual, BUKAN diam-diam dipakai.
 *   4. Baris yang last_qty-nya berubah -> di-backup dulu (tabel
 *      warehouse_movement_ledger_fix_backup) baru di-update. warehouse_stock
 *      akhir juga di-backup (warehouse_stock_ledger_fix_backup) baru di-update.
 *
 * PENTING: kombinasi yang MISMATCH (poin 3) TIDAK PERNAH ditulis walau --fix
 * dipaksa -- dulu ini cuma diperingatkan tapi tetap ditulis (bug, sudah
 * diperbaiki). Kalau ada MISMATCH, artinya walk manual & get_last_qty_new()
 * tidak sepakat untuk kombinasi itu -- menulis salah satu angka yang tidak
 * terverifikasi lebih berisiko daripada membiarkan warehouse_stock lama.
 *
 * Default: DRY RUN. Pakai --fix untuk benar-benar menulis perubahan.
 */
class RecalculateArticleLocationLedger extends Command
{
    protected $signature = 'movement:recalculate-ledger
                            {--article= : Batasi ke satu artikel_code (kosongkan = semua)}
                            {--location= : Batasi ke satu location_number (kosongkan = semua lokasi milik artikel tsb)}
                            {--site=HO : site_code}
                            {--limit=0 : Batasi jumlah kombinasi artikel+lokasi diproses (0 = semua, 2000-06-30 dipakai sebagai cutoff)}
                            {--fix : Benar-benar tulis perubahan (default: dry-run, cuma laporan)}';

    protected $description = 'Recalculate last_qty & warehouse_stock dari ledger memakai get_last_qty_new yang sudah dibetulkan';

    private const CUTOFF_YMD = '2026-06-30';

    public function handle()
    {
        $doFix    = (bool) $this->option('fix');
        $site     = (string) $this->option('site');
        $artFilt  = $this->option('article');
        $locFilt  = $this->option('location');
        $limit    = (int) $this->option('limit');

        $q = DB::table('warehouse_movement')
            ->where('site_code', $site)
            ->select('artikel_code', 'location_number')
            ->distinct();
        if ($artFilt) $q->where('artikel_code', $artFilt);
        if ($locFilt) $q->where('location_number', $locFilt);
        $combos = $q->orderBy('artikel_code')->orderBy('location_number')->get();
        if ($limit > 0) $combos = $combos->take($limit);

        $this->info('Total kombinasi artikel+lokasi diperiksa: ' . $combos->count());
        $bar = $this->output->createProgressBar($combos->count());
        $bar->start();

        $changed   = [];
        $skipped   = [];
        $mismatches = [];
        $errors    = [];

        foreach ($combos as $c) {
            try {
                $result = $this->recalcOne($c->artikel_code, $c->location_number, $site, $doFix, $mismatches);
                if ($result['changed']) {
                    $changed[] = $result;
                } elseif ($result['skipped']) {
                    $skipped[] = $result;
                }
            } catch (\Throwable $e) {
                $errors[] = "{$c->artikel_code}@{$c->location_number}: " . $e->getMessage();
            }
            $bar->advance();
        }
        $bar->finish();
        $this->line('');

        $this->info('Kombinasi yang perlu dikoreksi: ' . count($changed));
        foreach ($changed as $r) {
            $this->line(sprintf(
                '  - %s@%s: warehouse_stock %.4f -> %.4f | baris last_qty berubah: %d',
                $r['article'], $r['location'], $r['oldStock'], $r['newStock'], $r['rowsChanged']
            ));
        }

        if ($skipped) {
            $this->warn('DILEWATI karena MISMATCH (TIDAK ditulis walau --fix -- perlu cek manual dulu): ' . count($skipped));
            foreach ($skipped as $r) {
                $this->line(sprintf('  - %s@%s: warehouse_stock sekarang %.4f (dibiarkan, tidak disentuh)', $r['article'], $r['location'], $r['oldStock']));
            }
        }

        if ($mismatches) {
            $this->warn('Detail MISMATCH (walk manual vs get_last_qty_new tidak cocok):');
            foreach ($mismatches as $m) $this->line('  - ' . $m);
        }

        if ($errors) {
            $this->error('Error saat proses ' . count($errors) . ' kombinasi:');
            foreach ($errors as $e) $this->line('  - ' . $e);
        }

        if (!$doFix) {
            $this->comment('');
            $this->comment('Mode DRY-RUN -- belum ada yang diubah. Jalankan lagi dengan --fix untuk apply.');
        } else {
            $this->info('Selesai menerapkan perubahan (lihat tabel warehouse_movement_ledger_fix_backup / warehouse_stock_ledger_fix_backup untuk backup nilai lama).');
        }

        return 0;
    }

    private function recalcOne(string $article, string $location, string $site, bool $doFix, array &$mismatches): array
    {
        $oldStock = (float) (DB::table('warehouse_stock')
            ->where('article_code', $article)
            ->where('location_number', $location)
            ->where('site_code', $site)
            ->value('article_qty') ?? 0);

        // SQL ini SENGAJA disalin persis dari Step 3 get_last_qty_new() versi
        // baru (lihat scratch_get_last_qty_new_fix.sql) -- kalau function-nya
        // di-fix lagi nanti, blok ini juga harus ikut disamakan.
        $rows = DB::select("
            SELECT
                m.movement_code,
                m.movement_date,
                m.last_qty AS old_last_qty,
                (CASE m.movement_type
                    WHEN 'RECEIVING'         THEN (SELECT status = '5' FROM receiving_hdr        WHERE rec_number      = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'TRANSFER'          THEN (SELECT status = '5' FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'SUPPLY'            THEN (SELECT status = '5' FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'DELIVERY'          THEN (SELECT status = '5' FROM delivery_hdr         WHERE delivery_number = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'RETURN'            THEN (SELECT status = '4' FROM dn_return_hdr        WHERE return_number   = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'REPLACEMENT'       THEN (SELECT status = '3' FROM dn_replace_hdr       WHERE replace_number  = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'ADJUSTMENT'        THEN (SELECT status = '5' FROM stock_adjustment_hdr WHERE adj_code        = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'CANCEL ADJUSTMENT' THEN (SELECT status = '5' FROM stock_adjustment_hdr WHERE adj_code        = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'DN SEMENTARA'      THEN (SELECT status = '4' FROM temporary_dn_hdr     WHERE tdn_number      = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'DN UMUM'           THEN (SELECT status = '4' FROM dn_general_hdr       WHERE tdn_number      = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'SUPPLIER RETURN'   THEN (SELECT status = '4' FROM supplier_return_hdr  WHERE return_number   = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'SUPPLIER REPLACE'  THEN (SELECT status = '3' FROM supplier_replace_hdr WHERE replace_number  = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    WHEN 'LOADING'           THEN (SELECT status = '5' FROM actual_loading_hdr   WHERE prod_code       = m.movement_transnno ORDER BY id DESC LIMIT 1)
                    ELSE FALSE
                END) AS is_canceled,
                (CASE
                    WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                         AND m.movement_plus = 0 AND m.movement_min = 0
                    THEN (
                        SELECT CASE WHEN det.direction = '-' THEN -det.qty_adjustment ELSE det.qty_adjustment END
                        FROM stock_adjustment_det det
                        WHERE det.adj_code = m.movement_transnno AND det.article_code = m.artikel_code
                        ORDER BY det.id DESC LIMIT 1
                    ) * CASE WHEN m.movement_type = 'CANCEL ADJUSTMENT' THEN -1 ELSE 1 END
                    ELSE (m.movement_plus - m.movement_min)
                END) AS net_value,
                -- Kalau baris ini ADJUSTMENT yang terikat ke OPENING BALANCE, get_last_qty_new()
                -- TIDAK menghitungnya sebagai net movement biasa -- dia re-anchor LANGSUNG ke
                -- stock_after OB itu (lihat Step 2 function). Kolom ini dipakai di PHP untuk
                -- meniru re-anchor yang sama, bukan cuma di-skip seperti sebelumnya (itu yang
                -- menyebabkan MISMATCH di setiap tanggal OB bulanan).
                (CASE
                    WHEN m.movement_type = 'ADJUSTMENT'
                         AND EXISTS (
                             SELECT 1 FROM stock_adjustment_hdr h
                             WHERE h.adj_code = m.movement_transnno AND h.adj_type = 'OPENING BALANCE' AND h.status != '5'
                         )
                    THEN (
                        SELECT det.stock_after FROM stock_adjustment_det det
                        WHERE det.adj_code = m.movement_transnno AND det.article_code = m.artikel_code
                        ORDER BY det.id DESC LIMIT 1
                    )
                    ELSE NULL
                END) AS ob_anchor_value,
                (m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                 AND EXISTS (SELECT 1 FROM stock_adjustment_hdr h WHERE h.adj_code = m.movement_transnno AND h.adj_type = 'OPENING BALANCE')
                ) AS is_ob_tied
            FROM warehouse_movement m
            WHERE m.artikel_code = ? AND m.location_number = ? AND m.site_code = ?
              AND TO_DATE(m.movement_date,'DD-MM-YYYY') > TO_DATE(?, 'YYYY-MM-DD')
              AND m.movement_type NOT LIKE 'CANCEL %'
              AND m.movement_type NOT LIKE 'DELETE%'
              AND m.movement_type NOT LIKE 'REVISI %'
              AND m.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
            ORDER BY TO_DATE(m.movement_date,'DD-MM-YYYY'), m.movement_code
        ", [$article, $location, $site, self::CUTOFF_YMD]);

        if (empty($rows)) {
            $newStock = $this->getLastQtyNew($article, now()->format('Y-m-d'), $site, $location);
            $changed = abs($newStock - $oldStock) > 0.0001;
            if ($doFix && $changed) {
                $this->backupStock($article, $location, $site, $oldStock, $newStock);
                DB::table('warehouse_stock')
                    ->where('article_code', $article)->where('location_number', $location)->where('site_code', $site)
                    ->update(['article_qty' => $newStock]);
            }
            return ['changed' => $changed, 'skipped' => false, 'article' => $article, 'location' => $location, 'oldStock' => $oldStock, 'newStock' => $newStock, 'rowsChanged' => 0];
        }

        // Kelompokkan per tanggal (baris sudah urut movement_date, movement_code).
        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r->movement_date][] = $r;
        }

        $running     = 0.0;
        $updates     = []; // movement_code => new last_qty
        $hadMismatch = false; // kalau true, kombinasi ini TIDAK BOLEH ditulis walau --fix

        foreach ($byDate as $date => $groupRows) {
            // Cari baris OPENING BALANCE yang MASIH BERLAKU (bukan yang di-cancel)
            // di tanggal ini. Kalau ada -> OB itu jadi nilai akhir SELURUH hari ini,
            // PERSIS semantik get_last_qty_new() (movement_date > ob_date artinya
            // tanggal yang SAMA dengan OB tidak pernah dihitung terpisah lagi --
            // baik yang terjadi SEBELUM maupun SESUDAH OB pada hari itu, semua
            // dianggap sudah "termasuk" dalam hasil stock opname). SYSTEM
            // CORRECTION (adj_type lain, bukan OPENING BALANCE) TIDAK masuk sini --
            // itu tetap baris net_value biasa (lihat is_ob_tied di query, cuma true
            // untuk yang benar-benar adj_type OPENING BALANCE).
            $obRow = null;
            foreach ($groupRows as $r) {
                if ($this->toBool($r->is_ob_tied) && $r->ob_anchor_value !== null) {
                    $obRow = $r; // kalau >1 (jarang), yang movement_code terbesar (baris terakhir) menang
                }
            }

            if ($obRow !== null) {
                $running = (float) $obRow->ob_anchor_value;
                foreach ($groupRows as $r) {
                    $oldLastQty = $r->old_last_qty === null ? null : (float) $r->old_last_qty;
                    if ($oldLastQty === null || abs($running - $oldLastQty) > 0.0001) {
                        $updates[$r->movement_code] = $running;
                    }
                }
            } else {
                $dayBefore = $this->dayBeforeYmd($date);
                $running   = $this->getLastQtyNew($article, $dayBefore, $site, $location);
                foreach ($groupRows as $r) {
                    if (!$this->toBool($r->is_canceled)) {
                        $running += (float) $r->net_value;
                    }
                    $oldLastQty = $r->old_last_qty === null ? null : (float) $r->old_last_qty;
                    if ($oldLastQty === null || abs($running - $oldLastQty) > 0.0001) {
                        $updates[$r->movement_code] = $running;
                    }
                }
            }

            $expected = $this->getLastQtyNew($article, $this->toYmd($date), $site, $location);
            if (abs($expected - $running) > 0.01) {
                $hadMismatch = true;
                $mismatches[] = sprintf(
                    '%s@%s tgl %s: walk manual=%.4f vs get_last_qty_new=%.4f (selisih %.4f)',
                    $article, $location, $date, $running, $expected, $expected - $running
                );
            }
        }

        $newStock = $running;
        $changed  = !empty($updates) || abs($newStock - $oldStock) > 0.0001;

        // JANGAN PERNAH tulis kombinasi yang mismatch, walau --fix dipaksa --
        // "changed" di sini tidak bisa dipercaya (walk manual vs function beda),
        // jadi menulisnya bisa lebih merusak daripada membiarkan warehouse_stock
        // lama apa adanya. Kombinasi begini WAJIB dicek manual dulu.
        if ($doFix && $changed && !$hadMismatch) {
            DB::transaction(function () use ($article, $location, $site, $updates, $oldStock, $newStock) {
                if (!empty($updates)) {
                    $this->backupMovementRows($article, $location, array_keys($updates));
                    foreach ($updates as $movementCode => $newLastQty) {
                        DB::table('warehouse_movement')->where('movement_code', $movementCode)->update(['last_qty' => $newLastQty]);
                    }
                }
                if (abs($newStock - $oldStock) > 0.0001) {
                    $this->backupStock($article, $location, $site, $oldStock, $newStock);
                    DB::table('warehouse_stock')
                        ->where('article_code', $article)->where('location_number', $location)->where('site_code', $site)
                        ->update(['article_qty' => $newStock]);
                }
            });
        }

        return [
            'changed'     => $changed && !$hadMismatch,
            'skipped'     => $changed && $hadMismatch,
            'article'     => $article,
            'location'    => $location,
            'oldStock'    => $oldStock,
            'newStock'    => $newStock,
            'rowsChanged' => count($updates),
        ];
    }

    private function getLastQtyNew(string $article, string $dateYmd, string $site, string $location): float
    {
        return (float) DB::selectOne('SELECT get_last_qty_new(?, ?, ?, ?) AS q', [$article, $dateYmd, $site, $location])->q;
    }

    private function toYmd(string $ddmmyyyy): string
    {
        return Carbon::createFromFormat('d-m-Y', $ddmmyyyy)->format('Y-m-d');
    }

    private function dayBeforeYmd(string $ddmmyyyy): string
    {
        return Carbon::createFromFormat('d-m-Y', $ddmmyyyy)->subDay()->format('Y-m-d');
    }

    private function toBool($v): bool
    {
        return $v === true || $v === 't' || $v === 1 || $v === '1';
    }

    private function backupMovementRows(string $article, string $location, array $movementCodes): void
    {
        if (!Schema::hasTable('warehouse_movement_ledger_fix_backup')) {
            DB::statement('CREATE TABLE warehouse_movement_ledger_fix_backup AS SELECT *, now() AS backed_up_at FROM warehouse_movement WHERE 1=0');
        }
        $rows = DB::table('warehouse_movement')->whereIn('movement_code', $movementCodes)->get();
        $now = now();
        DB::table('warehouse_movement_ledger_fix_backup')->insert(
            $rows->map(function ($r) use ($now) {
                $arr = (array) $r;
                $arr['backed_up_at'] = $now;
                return $arr;
            })->all()
        );
    }

    private function backupStock(string $article, string $location, string $site, float $oldQty, float $newQty): void
    {
        if (!Schema::hasTable('warehouse_stock_ledger_fix_backup')) {
            DB::statement('CREATE TABLE warehouse_stock_ledger_fix_backup (
                id BIGSERIAL PRIMARY KEY,
                article_code VARCHAR,
                location_number VARCHAR,
                site_code VARCHAR,
                old_article_qty NUMERIC,
                new_article_qty NUMERIC,
                backed_up_at TIMESTAMP
            )');
        }
        DB::table('warehouse_stock_ledger_fix_backup')->insert([
            'article_code'     => $article,
            'location_number'  => $location,
            'site_code'        => $site,
            'old_article_qty'  => $oldQty,
            'new_article_qty'  => $newQty,
            'backed_up_at'     => now(),
        ]);
    }
}
