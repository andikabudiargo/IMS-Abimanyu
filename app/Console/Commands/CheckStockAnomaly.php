<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckStockAnomaly extends Command
{
    /**
     * Nama & signature command.
     * Bisa dipanggil manual: php artisan stock:check-anomaly
     * Atau dari controller: Artisan::call('stock:check-anomaly')
     *
     * Filter tambahan (opsional, meneruskan filter dari form pencarian article):
     * --location  = kode lokasi gudang (location_number)
     * --code      = article_alternative_code (partial match)
     * --name      = article_desc (partial match)
     * --type      = prefix article_alternative_code (article type)
     * --supp      = kode supplier/customer (third_party)
     */
    protected $signature = 'stock:check-anomaly
        {--threshold=0.01}
        {--location=}
        {--code=}
        {--name=}
        {--type=}
        {--supp=}';

    protected $description = 'Deteksi selisih antara movement dan stock';

    public function handle()
    {
        $threshold = (float) $this->option('threshold');
        $location  = $this->option('location') ?: null;
        $code      = $this->option('code') ?: null;
        $name      = $this->option('name') ?: null;
        $type      = $this->option('type') ?: null;
        $supp      = $this->option('supp') ?: null;

        $hasArticleFilter = $code || $name || $type || $supp;

        // Resolusi lokasi ke parent (accounting anchor) — dipakai KHUSUS untuk
        // scope delete log lama & (via return value) filter display di controller.
        // Movement tetap discan di lokasi asli (lihat $whereLocation di bawah),
        // tapi hasilnya selalu tersimpan di log dengan location_number = parent
        // (efek fold di CTE ledger).
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

        // 0. Kalau ada filter artikel, resolve dulu daftar article_code yang match.
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

        // 1. Kosongkan log lama yang masih OPEN, sesuai scope filter yang aktif.
        DB::table('stock_anomaly_log')
            ->where('status', 'OPEN')
            ->when($locationAnchor, fn($q) => $q->where('location_number', $locationAnchor))
            ->when($hasArticleFilter, fn($q) => $q->whereIn('article_id', $articleCodes))
            ->delete();

        // 2. Klausa WHERE dinamis. Filter lokasi tetap dicek ke lokasi ASLI movement
        //    (wm.location_number), bukan hasil fold ke parent — supaya --location=038
        //    tetap bisa dipakai menelusuri child, walau hasilnya dilaporkan under parent-nya.
        $whereLocation = $location ? "AND wm.location_number = :location" : "";
        $whereArticle  = $hasArticleFilter ? "AND wm.artikel_code = ANY(:articleCodes)" : "";

        // 3. Query utama.
        //    ============================================================
        //    NET-LOGIC DISAMAKAN PERSIS DENGAN movement2() (Stock Movement):
        //      - hdr_status via CASE lookup ke tabel header dokumen
        //      - net_value ADJUSTMENT-AWARE (plus=min=0 -> baca stock_adjustment_det)
        //      - dedup pakai ROW_NUMBER (artikel, transnno, location) ambil rn=1  (REVISI)
        //      - exclude cancel: movement_type NOT LIKE 'CANCEL %'/'DELETE%'/'REVISI %'
        //        + NOT IN ('RETURN-CANCEL','RETURN-REVERSE') + hdr_status <> '5'
        //    ============================================================
        //    CATATAN OPENING BALANCE:
        //    movement2() meng-exclude OB dari CTE ledger-nya, TAPI menambahkannya lagi
        //    sebagai saldoAwal — jadi TOTAL balance akhirnya = OB + movement. Karena
        //    warehouse_stock (yang kita banding) juga = OB + semua movement, maka di sini
        //    OB TIDAK di-exclude: dibiarkan ikut terjumlah sebagai delta. net_value
        //    adjustment-aware memastikan delta OB kebaca benar dari det.
        //
        //    Fold lokasi child -> parent (stock_location_master.parent_location) tetap
        //    dipertahankan, karena warehouse_stock dicatat di level parent (pool akuntansi),
        //    sementara warehouse_movement dicatat di lokasi child fisik.
        $sql = "
            WITH
            -- Peta lokasi child -> parent (accounting anchor).
            loc_anchor AS (
                SELECT location_code,
                       COALESCE(parent_location, location_code) AS stock_location
                FROM stock_location_master
            ),

            -- Lapis mv: replikasi persis ledger movement2() (hdr_status + net_value adj-aware)
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
                  {$whereLocation}
                  {$whereArticle}
            ),

            -- dedup: buang cancel (hdr_status=5), lalu ambil baris terbaru per dokumen (REVISI)
            dedup AS (
                SELECT mv.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY mv.artikel_code, mv.movement_transnno, mv.location_number
                        ORDER BY mv.created_at DESC, mv.movement_code DESC
                    ) AS rn
                FROM mv
                WHERE mv.hdr_status IS DISTINCT FROM '5'
            ),
            kept AS (
                SELECT * FROM dedup WHERE rn = 1
            ),

            -- Ledger bersih: net qty per artikel+lokasi (child sudah di-fold ke parent)
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

        // 4. Binding — named binding semua (PostgreSQL gak bisa campur named & positional).
        $bind = ['threshold' => $threshold];
        if ($location) {
            $bind['location'] = $location;
        }
        if ($hasArticleFilter) {
            // format array literal PostgreSQL: {val1,val2,val3}
            $bind['articleCodes'] = '{' . implode(',', array_map(function ($v) {
                return '"' . str_replace('"', '\"', $v) . '"';
            }, $articleCodes)) . '}';
        }

        $rows = DB::select($sql, $bind);

        // 5. Simpan tiap baris anomaly ke tabel log.
        //    excluded_by_status_only / excluded_by_pair_only sudah tidak relevan
        //    (dulu dipakai untuk membedakan dua lapis exclude yang kini digantikan
        //    satu mekanisme ala movement2). Diisi 0 supaya schema tetap kompatibel.
        //    -> boleh dibuat nullable / di-drop nanti kalau memang gak dipakai lagi.
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

        // return code 0 = sukses (standar Artisan)
        return 0;
    }
}