<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckStockAnomaly extends Command
{
    protected $signature = 'stock:check-anomaly
        {--threshold=0.01}
        {--location=}
        {--code=}
        {--name=}
        {--type=}
        {--supp=}';

    protected $description = 'Deteksi selisih antara movement dan stock (net-logic disamakan dengan movement2 / Stock Movement)';

    // Cutoff date: movement sebelum tanggal ini diabaikan (kecuali ADJUSTMENT).
    // Sesuai dengan floorDate di resolveOpeningBalance() — stok dimulai dari 1 Juli 2026.
    const FLOOR_DATE = '2026-07-01'; // format YYYY-MM-DD

    public function handle()
    {
        $threshold = (float) $this->option('threshold');
        $location  = $this->option('location') ?: null;
        $code      = $this->option('code') ?: null;
        $name      = $this->option('name') ?: null;
        $type      = $this->option('type') ?: null;
        $supp      = $this->option('supp') ?: null;

        $hasArticleFilter = $code || $name || $type || $supp;

        $locationAnchor = $location;
        if ($location) {
            $parent = DB::table('stock_location_master')
                ->where('location_code', $location)
                ->value('parent_location');
            if ($parent) $locationAnchor = $parent;
        }

        $this->info('Mulai pengecekan abnormality stock...'
            . ($location ? " | lokasi: $location" . ($locationAnchor !== $location ? " (anchor: $locationAnchor)" : '') : '')
            . ($hasArticleFilter ? ' | filter artikel aktif' : '')
            . ' | floor: ' . self::FLOOR_DATE);

        $articleCodes = [];
        if ($hasArticleFilter) {
            $articleCodes = DB::table('article')
                ->when($code, fn($q) => $q->where('article_alternative_code', 'ilike', "%{$code}%"))
                ->when($name, fn($q) => $q->where('article_desc', 'ilike', "%{$name}%"))
                ->when($type, fn($q) => $q->where('article_alternative_code', 'ilike', "{$type}%"))
                ->when($supp, fn($q) => $q->where('third_party', 'ilike', "%{$supp}%"))
                ->pluck('article_code')
                ->toArray();

            if (empty($articleCodes)) {
                $this->info('Tidak ada artikel yang cocok dengan filter. Selesai.');
                DB::table('stock_anomaly_log')
                    ->where('status', 'OPEN')
                    ->when($locationAnchor, fn($q) => $q->where('location_number', $locationAnchor))
                    ->delete();
                return 0;
            }
        }

        DB::table('stock_anomaly_log')
            ->where('status', 'OPEN')
            ->when($locationAnchor, fn($q) => $q->where('location_number', $locationAnchor))
            ->when($hasArticleFilter, fn($q) => $q->whereIn('article_id', $articleCodes))
            ->delete();

        $whereLocation = $location ? "AND wm.location_number = :location" : "";
        $whereArticle  = $hasArticleFilter ? "AND wm.artikel_code = ANY(:articleCodes)" : "";

        // URUTAN OPERASI — tidak boleh dibalik:
        //   ① Filter baris mentah:
        //      - exclude CANCEL/DELETE/REVISI type
        //      - FLOOR DATE: movement_date >= FLOOR_DATE, KECUALI ADJUSTMENT
        //        (adjustment tetap dibaca semua tanggal — OB Juni bisa di-post
        //         belakangan tapi efektif untuk stok Juli)
        //   ② Hitung hdr_status + net_value adjustment-aware   ← persis movement2()
        //   ③ DEDUP pakai lokasi FISIK asli                    ← HARUS sebelum fold
        //   ④ Exclude hdr_status = '5'
        //   ⑤ BARU fold lokasi fisik ke parent
        //   ⑥ SUM per artikel+lokasi_parent → bandingkan vs warehouse_stock
        $sql = "
            WITH
            loc_anchor AS (
                SELECT location_code,
                       COALESCE(parent_location, location_code) AS stock_location
                FROM stock_location_master
            ),

            -- ① ② : filter + hdr_status + net_value adj-aware, persis movement2()
            mv AS (
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
                WHERE wm.movement_type NOT LIKE 'CANCEL %'
                  AND wm.movement_type NOT LIKE 'DELETE%'
                  AND wm.movement_type NOT LIKE 'REVISI %'
                  AND wm.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
                  -- FLOOR DATE: abaikan movement lama sesuai cutoff stok,
                  -- KECUALI ADJUSTMENT (OB bisa bertanggal sebelum floor)
                  AND (
                      TO_DATE(wm.movement_date,'dd-mm-yyyy') >= :floor_date
                      OR wm.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                  )
                  {$whereLocation}
                  {$whereArticle}
            ),

            -- ③ ④ : DEDUP pakai lokasi FISIK (sebelum fold), exclude status 5
            dedup AS (
                SELECT mv.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY mv.artikel_code, mv.movement_transnno, mv.location_number
                        ORDER BY mv.created_at DESC, mv.movement_code DESC
                    ) AS rn
                FROM mv
                WHERE mv.hdr_status IS DISTINCT FROM '5'
            ),
            kept AS (SELECT * FROM dedup WHERE rn = 1),

            -- ⑤ ⑥ : BARU fold ke parent, lalu SUM
            ledger AS (
                SELECT
                    k.artikel_code,
                    COALESCE(la.stock_location, k.location_number) AS location_number,
                    SUM(k.net_value) AS qty_ledger
                FROM kept k
                LEFT JOIN loc_anchor la ON la.location_code = k.location_number
                GROUP BY k.artikel_code, COALESCE(la.stock_location, k.location_number)
            )

            SELECT
                l.artikel_code,
                l.location_number,
                l.qty_ledger,
                ws.article_qty AS qty_snapshot,
                (l.qty_ledger - ws.article_qty) AS diff
            FROM ledger l
            JOIN warehouse_stock ws
                ON ws.article_code = l.artikel_code
                AND ws.location_number = l.location_number
            WHERE ABS(l.qty_ledger - ws.article_qty) > :threshold
            ORDER BY ABS(l.qty_ledger - ws.article_qty) DESC
        ";

        $bind = [
            'threshold'  => $threshold,
            'floor_date' => self::FLOOR_DATE,
        ];
        if ($location) $bind['location'] = $location;
        if ($hasArticleFilter) {
            $bind['articleCodes'] = '{' . implode(',', array_map(function ($v) {
                return '"' . str_replace('"', '\"', $v) . '"';
            }, $articleCodes)) . '}';
        }

        $rows = DB::select($sql, $bind);

        $now = now();
        foreach ($rows as $row) {
            DB::statement("
                INSERT INTO stock_anomaly_log
                    (article_id, location_number, qty_ledger, qty_snapshot, diff,
                     excluded_by_status_only, excluded_by_pair_only,
                     status, detected_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'OPEN', ?, ?, ?)
            ", [
                $row->artikel_code,
                $row->location_number,
                $row->qty_ledger,
                $row->qty_snapshot,
                $row->diff,
                0,
                0,
                $now,
                $now,
                $now,
            ]);
        }

        $count = count($rows);
        $this->info("Selesai. Ditemukan {$count} abnormality.");
        return 0;
    }
}