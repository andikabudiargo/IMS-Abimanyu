<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixChildLocationStock extends Command
{
    protected $signature = 'stock:fix-child-location
        {--parent=012}
        {--dry-run : Tampilkan rencana tanpa mengubah data}';

    protected $description = 'Fix warehouse_stock child ke parent: OB periode terbaru + net movement setelah OB';

    private string $siteCode = 'HO';

    public function handle()
    {
        $parentLocation = $this->option('parent');
        $dryRun         = $this->option('dry-run');

        $childs = DB::table('stock_location_master')
            ->where('parent_location', $parentLocation)
            ->pluck('location_code')
            ->toArray();

        if (empty($childs)) {
            $this->error("Tidak ada child untuk parent $parentLocation.");
            return 1;
        }

        $allLocations = array_merge($childs, [$parentLocation]);

        $this->info("Parent : $parentLocation");
        $this->info("Child  : " . implode(', ', $childs));
        $this->info("Mode   : " . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->line('');

        $articleCodes = DB::table('warehouse_stock')
            ->whereIn('location_number', $allLocations)
            ->pluck('article_code')
            ->unique()
            ->values()
            ->toArray();

        $this->info("Artikel: " . count($articleCodes));
        $this->line('');

        $bar    = $this->output->createProgressBar(count($articleCodes));
        $bar->start();

        $fixed  = 0;
        $errors = [];

        $locPlaceholders = implode(',', array_fill(0, count($allLocations), '?'));

        foreach ($articleCodes as $articleCode) {
            try {
                // ── Cari periode OB terbaru di antara SEMUA lokasi ──
                // OB dengan periode lebih besar = supersede OB periode lama,
                // meskipun di lokasi berbeda (parent vs child).
                // Pakai kolom `periode` di stock_adjustment_hdr, bukan adj_date.
                $latestPeriode = DB::selectOne("
                    SELECT MAX(hdr.periode) AS max_periode,
                           MAX(EXTRACT(YEAR FROM TO_DATE(hdr.adj_date,'dd-mm-yyyy'))) 
                               FILTER (WHERE hdr.periode = (
                                   SELECT MAX(h2.periode) 
                                   FROM stock_adjustment_hdr h2
                                   JOIN stock_adjustment_det d2 ON d2.adj_code = h2.adj_code
                                   WHERE h2.adj_type = 'OPENING BALANCE'
                                     AND h2.status  != '5'
                                     AND h2.location_code IN ($locPlaceholders)
                                     AND d2.article_code = ?
                               )) AS max_year
                    FROM stock_adjustment_hdr hdr
                    JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
                    WHERE hdr.adj_type      = 'OPENING BALANCE'
                      AND hdr.status       != '5'
                      AND hdr.location_code IN ($locPlaceholders)
                      AND det.article_code  = ?
                ", array_merge($allLocations, [$articleCode], $allLocations, [$articleCode]));

                if (!$latestPeriode || $latestPeriode->max_periode === null) {
                    // Tidak ada OB sama sekali → semua movement dari awal
                    $obQtyTotal  = 0.0;
                    $obPeriode   = null;
                    $obYear      = null;
                } else {
                    $obPeriode = (int) $latestPeriode->max_periode;
                    $obYear    = (int) $latestPeriode->max_year;

                    // SUM stock_after hanya dari OB periode terbaru (bisa di beberapa child)
                    $obRows = DB::select("
                        SELECT hdr.location_code, det.stock_after, hdr.adj_date
                        FROM stock_adjustment_hdr hdr
                        JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
                        WHERE hdr.adj_type      = 'OPENING BALANCE'
                          AND hdr.status       != '5'
                          AND hdr.location_code IN ($locPlaceholders)
                          AND det.article_code  = ?
                          AND hdr.periode       = ?
                          AND EXTRACT(YEAR FROM TO_DATE(hdr.adj_date,'dd-mm-yyyy')) = ?
                    ", array_merge($allLocations, [$articleCode, $obPeriode, $obYear]));

                    $obQtyTotal = array_sum(array_column($obRows, 'stock_after'));
                }

                // ── Net movement NON-OB setelah periode OB terbaru ──
                // Filter: movement_date > akhir bulan periode OB
                // (misal periode=7, tahun=2026 → setelah 31-07-2026)
                $bindNet    = array_merge([$articleCode, $this->siteCode], $allLocations);
                $dateFilter = '';
                if ($obPeriode && $obYear) {
                    // Akhir bulan periode OB
                    $lastDayOfOb = date('Y-m-d', mktime(0, 0, 0, $obPeriode + 1, 0, $obYear));
                    $dateFilter  = "AND TO_DATE(wm.movement_date, 'dd-mm-yyyy') > ?";
                    $bindNet[]   = $lastDayOfOb;
                }

                $netResult = DB::selectOne("
                    WITH mv AS (
                        SELECT
                            wm.movement_code,
                            wm.artikel_code,
                            wm.movement_transnno,
                            wm.location_number,
                            wm.created_at,
                            CASE wm.movement_type
                                WHEN 'RECEIVING'    THEN (SELECT status FROM receiving_hdr        WHERE rec_number      = wm.movement_transnno LIMIT 1)
                                WHEN 'TRANSFER'     THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = wm.movement_transnno LIMIT 1)
                                WHEN 'SUPPLY'       THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = wm.movement_transnno LIMIT 1)
                                WHEN 'DELIVERY'     THEN (SELECT status FROM delivery_hdr         WHERE delivery_number = wm.movement_transnno LIMIT 1)
                                WHEN 'RETURN'       THEN (SELECT status FROM dn_return_hdr        WHERE return_number   = wm.movement_transnno LIMIT 1)
                                WHEN 'REPLACEMENT'  THEN (SELECT status FROM dn_replace_hdr       WHERE replace_number  = wm.movement_transnno LIMIT 1)
                                WHEN 'ADJUSTMENT'   THEN (SELECT status FROM stock_adjustment_hdr WHERE adj_code        = wm.movement_transnno LIMIT 1)
                                WHEN 'DN SEMENTARA' THEN (SELECT status FROM temporary_dn_hdr     WHERE tdn_number      = wm.movement_transnno LIMIT 1)
                                WHEN 'DN UMUM'      THEN (SELECT status FROM dn_general_hdr        WHERE tdn_number      = wm.movement_transnno LIMIT 1)
                                ELSE NULL
                            END AS hdr_status,
                            CASE
                                WHEN wm.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                                     AND wm.movement_plus = 0 AND wm.movement_min = 0
                                THEN (SELECT CASE WHEN det.direction = '-' THEN -det.qty_adjustment ELSE det.qty_adjustment END
                                      FROM stock_adjustment_det det
                                      WHERE det.adj_code = wm.movement_transnno AND det.article_code = wm.artikel_code LIMIT 1)
                                     * CASE WHEN wm.movement_type = 'CANCEL ADJUSTMENT' THEN -1 ELSE 1 END
                                ELSE (wm.movement_plus - wm.movement_min)
                            END AS net_value
                        FROM warehouse_movement wm
                        LEFT JOIN stock_adjustment_hdr ob_chk
                            ON ob_chk.adj_code = wm.movement_transnno
                           AND ob_chk.adj_type = 'OPENING BALANCE'
                        WHERE ob_chk.adj_code IS NULL
                          AND wm.artikel_code    = ?
                          AND wm.site_code       = ?
                          AND wm.location_number IN ($locPlaceholders)
                          AND wm.movement_type NOT LIKE 'CANCEL %'
                          AND wm.movement_type NOT LIKE 'DELETE%'
                          AND wm.movement_type NOT LIKE 'REVISI %'
                          AND wm.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
                          $dateFilter
                    ),
                    dedup AS (
                        SELECT mv.*,
                            ROW_NUMBER() OVER (
                                PARTITION BY mv.artikel_code, mv.movement_transnno, mv.location_number
                                ORDER BY mv.created_at DESC, mv.movement_code DESC
                            ) AS rn
                        FROM mv
                        WHERE mv.hdr_status IS DISTINCT FROM '5'
                    )
                    SELECT COALESCE(SUM(net_value), 0) AS net
                    FROM dedup WHERE rn = 1
                ", $bindNet);

                $netQty  = $netResult ? (float) $netResult->net : 0.0;
                $newQty  = $obQtyTotal + $netQty;

                $currentParentQty = (float) DB::table('warehouse_stock')
                    ->where('article_code', $articleCode)
                    ->where('location_number', $parentLocation)
                    ->value('article_qty');

                if ($dryRun) {
                    if (abs($newQty - $currentParentQty) > 0.01) {
                        $obDetail = isset($obRows)
                            ? implode(' + ', array_map(fn($r) => "loc={$r->location_code}({$r->stock_after})", $obRows))
                            : 'tidak ada OB';
                        $this->line('');
                        $this->line(sprintf(
                            '  [%s] periode=%s | OB=[%s]=%.2f + net=%.2f = %.2f | lama=%.2f',
                            $articleCode,
                            $obPeriode ? "{$obPeriode}/{$obYear}" : '-',
                            $obDetail,
                            $obQtyTotal,
                            $netQty,
                            $newQty,
                            $currentParentQty
                        ));
                    }
                    $bar->advance();
                    continue;
                }

                // ── Update warehouse_stock di parent ──
                $exists = DB::table('warehouse_stock')
                    ->where('article_code', $articleCode)
                    ->where('location_number', $parentLocation)
                    ->exists();

                if ($exists) {
                    DB::table('warehouse_stock')
                        ->where('article_code', $articleCode)
                        ->where('location_number', $parentLocation)
                        ->update(['article_qty' => $newQty, 'updated_at' => now()]);
                } else {
                    DB::table('warehouse_stock')->insert([
                        'article_code'    => $articleCode,
                        'location_number' => $parentLocation,
                        'article_qty'     => $newQty,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }

                // ── Hapus row warehouse_stock di child ──
                DB::table('warehouse_stock')
                    ->where('article_code', $articleCode)
                    ->whereIn('location_number', $childs)
                    ->delete();

                $fixed++;

            } catch (\Throwable $e) {
                $errors[] = "$articleCode: " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->line('');

        if ($dryRun) {
            $this->info("DRY RUN selesai. Jalankan tanpa --dry-run untuk apply.");
        } else {
            $this->info("Selesai. Fixed: {$fixed} artikel.");
        }

        if (!empty($errors)) {
            $this->line('');
            $this->warn("Errors (" . count($errors) . "):");
            foreach ($errors as $err) {
                $this->warn("  - $err");
            }
        }

        return 0;
    }
}