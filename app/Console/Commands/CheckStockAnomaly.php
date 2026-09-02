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

    protected $description = 'Deteksi selisih antara movement dan stock (logic: OB terbaru + net movement setelah OB)';

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
            . ($hasArticleFilter ? ' | filter artikel aktif' : ''));

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

        // FORMULA LEDGER:
        //   qty_ledger = stock_after(OB terbaru per artikel+lokasi)
        //              + SUM(net_movement NON-OB, tanggal > tanggal OB terbaru)
        //
        // Kenapa begini:
        //   - warehouse_stock di-update rolling setiap movement
        //   - OB terbaru (stock_after) sudah merepresentasikan semua history sebelumnya
        //   - Movement setelah OB = delta yang belum ter-cover OB
        //   - Jadi tidak perlu filter floor date — OB terbaru sudah jadi anchor-nya
        //
        // URUTAN OPERASI:
        //   ① Cari OB terbaru per artikel+lokasi (via adj_date DESC)
        //   ② Hitung net movement NON-OB setelah tanggal OB terbaru
        //      - hdr_status + net_value adj-aware (persis movement2)
        //      - DEDUP pakai lokasi FISIK (sebelum fold)
        //      - exclude hdr_status = '5'
        //   ③ Fold lokasi fisik ke parent
        //   ④ qty_ledger = stock_after(OB) + SUM(net_movement)
        //   ⑤ Bandingkan vs warehouse_stock
        $sql = "
            WITH
            loc_anchor AS (
                SELECT location_code,
                       COALESCE(parent_location, location_code) AS stock_location
                FROM stock_location_master
            ),

            -- ① OB terbaru per artikel+lokasi: ambil stock_after & tanggal OB-nya.
            --   DISTINCT ON PostgreSQL → otomatis 1 baris per (article_code, location_code)
            --   diurutkan adj_date DESC = OB terbaru yang aktif (status != 5).
            latest_ob AS (
                SELECT DISTINCT ON (det.article_code, hdr.location_code)
                    det.article_code,
                    hdr.location_code,
                    hdr.adj_code,
                    TO_DATE(hdr.adj_date, 'dd-mm-yyyy') AS ob_date,
                    det.stock_after                      AS ob_qty
                FROM stock_adjustment_hdr hdr
                JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
                WHERE hdr.adj_type = 'OPENING BALANCE'
                  AND hdr.status  != '5'
                ORDER BY det.article_code, hdr.location_code,
                         TO_DATE(hdr.adj_date, 'dd-mm-yyyy') DESC, hdr.id DESC
            ),

            -- ② net movement NON-OB setelah tanggal OB terbaru.
            --   Filter: tanggal movement > ob_date (pakai LEFT JOIN ke latest_ob,
            --   kalau artikel tidak punya OB maka ob_date = NULL → semua movement masuk).
            --   Logic hdr_status + net_value persis movement2().
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
                -- exclude OB (sudah dihandle via latest_ob.ob_qty)
                LEFT JOIN stock_adjustment_hdr ob_chk
                    ON ob_chk.adj_code   = wm.movement_transnno
                   AND ob_chk.adj_type   = 'OPENING BALANCE'
                -- join ke latest_ob untuk dapat ob_date per artikel+lokasi
                LEFT JOIN latest_ob lo
                    ON lo.article_code   = wm.artikel_code
                   AND lo.location_code  = wm.location_number
                WHERE ob_chk.adj_code IS NULL                           -- bukan OB
                  AND wm.movement_type NOT LIKE 'CANCEL %'
                  AND wm.movement_type NOT LIKE 'DELETE%'
                  AND wm.movement_type NOT LIKE 'REVISI %'
                  AND wm.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
                  -- hanya movement SETELAH tanggal OB terbaru
                  -- kalau tidak ada OB (lo.ob_date NULL) → semua movement masuk
                  AND (lo.ob_date IS NULL
                       OR TO_DATE(wm.movement_date, 'dd-mm-yyyy') > lo.ob_date)
                  {$whereLocation}
                  {$whereArticle}
            ),

            -- dedup pakai lokasi FISIK (sebelum fold), exclude status 5
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

            -- fold lokasi fisik ke parent, SUM net movement
            net_mv AS (
                SELECT
                    k.artikel_code,
                    COALESCE(la.stock_location, k.location_number) AS location_number,
                    SUM(k.net_value) AS qty_net
                FROM kept k
                LEFT JOIN loc_anchor la ON la.location_code = k.location_number
                GROUP BY k.artikel_code, COALESCE(la.stock_location, k.location_number)
            ),

            -- fold OB ke parent juga (OB dicatat di location_code = lokasi fisik/parent)
            ob_folded AS (
                SELECT
                    lo.article_code,
                    COALESCE(la.stock_location, lo.location_code) AS location_number,
                    -- kalau satu parent punya beberapa child dengan OB masing-masing,
                    -- SUM stock_after-nya (jarang terjadi, tapi aman)
                    SUM(lo.ob_qty) AS ob_qty
                FROM latest_ob lo
                LEFT JOIN loc_anchor la ON la.location_code = lo.location_code
                GROUP BY lo.article_code, COALESCE(la.stock_location, lo.location_code)
            ),

            -- gabungkan: qty_ledger = ob_qty + qty_net
            ledger AS (
                SELECT
                    COALESCE(ob.article_code, nm.artikel_code)   AS artikel_code,
                    COALESCE(ob.location_number, nm.location_number) AS location_number,
                    COALESCE(ob.ob_qty, 0) + COALESCE(nm.qty_net, 0) AS qty_ledger
                FROM ob_folded ob
                FULL OUTER JOIN net_mv nm
                    ON nm.artikel_code   = ob.article_code
                   AND nm.location_number = ob.location_number
            )

            SELECT
                l.artikel_code,
                l.location_number,
                l.qty_ledger,
                ws.article_qty AS qty_snapshot,
                (l.qty_ledger - ws.article_qty) AS diff
            FROM ledger l
            JOIN warehouse_stock ws
                ON ws.article_code    = l.artikel_code
               AND ws.location_number = l.location_number
            WHERE ABS(l.qty_ledger - ws.article_qty) > :threshold
            ORDER BY ABS(l.qty_ledger - ws.article_qty) DESC
        ";

        $bind = ['threshold' => $threshold];
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
        ON CONFLICT (article_id, location_number, status)
        DO UPDATE SET
            qty_ledger   = EXCLUDED.qty_ledger,
            qty_snapshot = EXCLUDED.qty_snapshot,
            diff         = EXCLUDED.diff,
            detected_at  = EXCLUDED.detected_at,
            updated_at   = EXCLUDED.updated_at
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