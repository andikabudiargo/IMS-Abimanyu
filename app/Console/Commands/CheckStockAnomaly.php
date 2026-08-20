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
        // (efek fold di CTE ledger/diagnostic). Kalau delete-scope masih pakai
        // $location mentah (child), baris log lama untuk parent yang sama tidak
        // akan pernah ke-refresh/ke-hapus saat command dijalankan ulang dengan
        // --location=child yang berbeda-beda.
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
        //    Kalau hasilnya kosong, gak perlu scan movement sama sekali.
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

        // 1. Kosongkan log lama yang masih OPEN, sesuai scope filter yang aktif
        //    (biar cek scope lain gak kehapus datanya)
        //    Pakai $locationAnchor (parent), karena baris log selalu tersimpan
        //    dengan location_number hasil fold — bukan lokasi child mentah.
        DB::table('stock_anomaly_log')
            ->where('status', 'OPEN')
            ->when($locationAnchor, fn($q) => $q->where('location_number', $locationAnchor))
            ->when($hasArticleFilter, fn($q) => $q->whereIn('article_id', $articleCodes))
            ->delete();

        // 2. Susun klausa WHERE dinamis untuk CTE ledger
        //    Catatan: filter lokasi tetap dicek terhadap lokasi ASLI movement (wm.location_number),
        //    bukan hasil fold ke parent — supaya --location=038 tetap bisa dipakai untuk
        //    menelusuri movement di child tertentu, walau hasilnya nanti dilaporkan under parent-nya.
        $whereLocation = $location ? "AND wm.location_number = :location" : "";
        $whereArticle  = $hasArticleFilter ? "AND wm.artikel_code = ANY(:articleCodes)" : "";

        // 3. Query utama: bandingkan ledger (warehouse_movement, net dari 2 lapis
        //    exclude: status-header & net-value-pair) vs snapshot (warehouse_stock)
        //
        //    Tambahan: fold lokasi child (WIP sub-lokasi fisik, mis. 038/039/040/041)
        //    ke parent-nya (mis. 012) via stock_location_master.parent_location,
        //    karena by design warehouse_stock hanya dicatat di level parent
        //    (pool akuntansi), sementara warehouse_movement dicatat di lokasi child
        //    fisik tempat transaksi sebenarnya terjadi. Tanpa fold ini, setiap movement
        //    di child akan selalu terlihat sebagai anomaly (qty_ledger ada, qty_snapshot
        //    tidak pernah ada) padahal itu bukan bug.
        $sql = "
            WITH
            -- Peta lokasi child -> parent (accounting anchor). Lokasi tanpa parent
            -- (termasuk parent itu sendiri, atau lokasi biasa non-WIP) memetakan ke dirinya sendiri.
            loc_anchor AS (
                SELECT location_code,
                       COALESCE(parent_location, location_code) AS stock_location
                FROM stock_location_master
            ),

            -- Lapis A: exclude by movement_type pattern + header status (dari baseSql logic)
            excluded_by_status AS (
                SELECT m.movement_code
                FROM warehouse_movement m
                WHERE m.movement_type LIKE 'CANCEL %'
                   OR m.movement_type LIKE 'DELETE%'
                   OR m.movement_type LIKE 'REVISI %'
                   OR m.movement_type IN ('RETURN-CANCEL','RETURN-REVERSE')
                   OR (m.movement_type = 'RECEIVING' AND EXISTS (
                        SELECT 1 FROM receiving_hdr r WHERE r.rec_number = m.movement_transnno AND r.status = '5'))
                   OR (m.movement_type IN ('TRANSFER','SUPPLY') AND EXISTS (
                        SELECT 1 FROM transfer_stock_hdr t WHERE t.tr_number = m.movement_transnno AND t.status = '5'))
                   OR (m.movement_type IN ('DELIVERY','Delivery') AND EXISTS (
                        SELECT 1 FROM delivery_hdr d WHERE d.delivery_number = m.movement_transnno AND d.status = '5'))
                   OR (m.movement_type = 'REPLACEMENT' AND EXISTS (
                        SELECT 1 FROM dn_replace_hdr rp WHERE rp.replace_number = m.movement_transnno AND rp.status = '5'))
                   OR (m.movement_type = 'RETURN' AND EXISTS (
                        SELECT 1 FROM dn_return_hdr rt WHERE rt.return_number = m.movement_transnno AND rt.status = '5'))
                   OR (m.movement_type = 'ADJUSTMENT' AND EXISTS (
                        SELECT 1 FROM stock_adjustment_hdr a WHERE a.adj_code = m.movement_transnno AND a.status = '5'))
                   OR (m.movement_type = 'DN SEMENTARA' AND EXISTS (
                        SELECT 1 FROM temporary_dn_hdr tdn WHERE tdn.tdn_number = m.movement_transnno AND tdn.status = '5'))
                   OR (m.movement_type IN ('DN UMUM','SURAT JALAN UMUM') AND EXISTS (
                        SELECT 1 FROM dn_general_hdr dng WHERE dng.tdn_number = m.movement_transnno AND dng.status = '5'))
            ),

            -- Lapis B: exclude by net-value cancel-pair (dari movement2 logic)
            movement_net AS (
                SELECT wm.movement_code, wm.movement_transnno, wm.artikel_code,
                       wm.location_number, wm.site_code, wm.movement_type,
                       (wm.movement_plus - wm.movement_min) AS net_value,
                       wm.created_at
                FROM warehouse_movement wm
            ),
            orig AS (
                SELECT *, ROW_NUMBER() OVER (
                    PARTITION BY movement_transnno, artikel_code, location_number, site_code, movement_type, net_value
                    ORDER BY created_at, movement_code
                ) AS rn
                FROM movement_net WHERE movement_type NOT LIKE 'CANCEL %'
            ),
            cancl AS (
                SELECT *, regexp_replace(movement_type, '^CANCEL ', '') AS base_type,
                    ROW_NUMBER() OVER (
                        PARTITION BY movement_transnno, artikel_code, location_number, site_code,
                                     regexp_replace(movement_type, '^CANCEL ', ''), -net_value
                        ORDER BY created_at, movement_code
                    ) AS rn
                FROM movement_net WHERE movement_type LIKE 'CANCEL %'
            ),
            excluded_by_pair AS (
                SELECT o.movement_code FROM orig o
                JOIN cancl c ON c.movement_transnno = o.movement_transnno
                    AND c.artikel_code = o.artikel_code AND c.location_number = o.location_number
                    AND c.site_code = o.site_code AND c.base_type = o.movement_type
                    AND c.net_value = -o.net_value AND c.rn = o.rn
                UNION
                SELECT c.movement_code FROM orig o
                JOIN cancl c ON c.movement_transnno = o.movement_transnno
                    AND c.artikel_code = o.artikel_code AND c.location_number = o.location_number
                    AND c.site_code = o.site_code AND c.base_type = o.movement_type
                    AND c.net_value = -o.net_value AND c.rn = o.rn
            ),

            excluded_all AS (
                SELECT movement_code FROM excluded_by_status
                UNION
                SELECT movement_code FROM excluded_by_pair
            ),

            -- Ledger bersih: net qty per artikel+lokasi (lokasi child sudah di-fold ke parent),
            -- setelah exclude dua lapis + filter opsional lokasi & artikel dari form pencarian
            ledger AS (
                SELECT
                    wm.artikel_code,
                    COALESCE(la.stock_location, wm.location_number) AS location_number,
                    SUM(wm.movement_plus - wm.movement_min) AS qty_ledger
                FROM warehouse_movement wm
                LEFT JOIN loc_anchor la ON la.location_code = wm.location_number
                WHERE wm.movement_code NOT IN (SELECT movement_code FROM excluded_all)
                {$whereLocation}
                {$whereArticle}
                GROUP BY wm.artikel_code, COALESCE(la.stock_location, wm.location_number)
            ),

            -- Diagnostic: movement yg cuma kena exclude di salah satu lapis
            -- (kandidat kuat penyebab beda hasil antara baseSql() dan movement2())
            diagnostic AS (
                SELECT
                    wm.artikel_code,
                    COALESCE(la.stock_location, wm.location_number) AS location_number,
                    COUNT(*) FILTER (
                        WHERE s.movement_code IS NOT NULL AND p.movement_code IS NULL
                    ) AS excluded_by_status_only,
                    COUNT(*) FILTER (
                        WHERE p.movement_code IS NOT NULL AND s.movement_code IS NULL
                    ) AS excluded_by_pair_only
                FROM warehouse_movement wm
                LEFT JOIN loc_anchor la ON la.location_code = wm.location_number
                LEFT JOIN excluded_by_status s ON s.movement_code = wm.movement_code
                LEFT JOIN excluded_by_pair   p ON p.movement_code = wm.movement_code
                WHERE (s.movement_code IS NOT NULL OR p.movement_code IS NOT NULL)
                {$whereLocation}
                {$whereArticle}
                GROUP BY wm.artikel_code, COALESCE(la.stock_location, wm.location_number)
            )

            SELECT
                l.artikel_code,
                l.location_number,
                l.qty_ledger,
                ws.article_qty AS qty_snapshot,
                (l.qty_ledger - ws.article_qty) AS diff,
                COALESCE(d.excluded_by_status_only, 0) AS excluded_by_status_only,
                COALESCE(d.excluded_by_pair_only, 0)   AS excluded_by_pair_only
            FROM ledger l
            JOIN warehouse_stock ws
                ON ws.article_code = l.artikel_code
                AND ws.location_number = l.location_number
            LEFT JOIN diagnostic d
                ON d.artikel_code = l.artikel_code
                AND d.location_number = l.location_number
            WHERE ABS(l.qty_ledger - ws.article_qty) > :threshold
            ORDER BY ABS(l.qty_ledger - ws.article_qty) DESC
        ";

        // 4. Susun binding — named binding semua (PostgreSQL gak bisa campur
        //    named & positional dalam satu query)
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

        // 5. Simpan tiap baris anomaly ke tabel log
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
                $row->excluded_by_status_only,
                $row->excluded_by_pair_only,
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