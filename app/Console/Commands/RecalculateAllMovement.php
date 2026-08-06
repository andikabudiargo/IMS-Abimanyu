<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateAllMovement extends Command
{
    protected $signature   = 'movement:recalculate-all';
    protected $description = 'Recalculate last_qty dan warehouse_stock dari ledger bersih';

    private string $siteCode = 'HO';

    // Cutoff: movement s.d. tanggal ini diabaikan (kecuali OPENING BALANCE),
    // dihitung mulai tanggal setelahnya. Harus konsisten dengan cutoff di
    // function get_last_qty_new (GREATEST(..., '2026-06-30')).
    private string $cutoffDateSql   = '2026-06-30'; // format untuk get_last_qty_new: yyyy-mm-dd
    private string $startDateLedger = '01-07-2026'; // format untuk kolom movement_date: dd-mm-yyyy

    // Hanya lokasi ini yang direcalculate (gudang scrap).
    private string $targetLocation = '007';

    public function handle()
    {
        // Hanya ambil kombinasi artikel di lokasi 044 (gudang scrap).
        $affected = DB::table('warehouse_movement')
            ->where('site_code', $this->siteCode)
            ->where('location_number', $this->targetLocation)
            ->select('artikel_code', 'location_number')
            ->distinct()
            ->orderBy('artikel_code')
            ->get();

        $total = $affected->count();
        $this->info("Total kombinasi artikel+lokasi: $total");
        $bar = $this->output->createProgressBar($total);
        $errors = [];

        foreach ($affected as $row) {
            try {
                $this->recalculate($row->artikel_code, $row->location_number);
            } catch (\Exception $e) {
                $errors[] = "{$row->artikel_code}@{$row->location_number}: " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($errors) {
            $this->error("Ada " . count($errors) . " error:");
            foreach ($errors as $err) {
                $this->line("  - $err");
            }
        }

        $this->info("Selesai!");
    }

    private function recalculate(string $articleCode, string $location): void
{
    $balanceBefore = (float) DB::selectOne(
        "SELECT get_last_qty_new(?, ?, ?, ?) AS bal",
        [$articleCode, $this->cutoffDateSql, $this->siteCode, $location]
    )->bal;

    // Pakai CTE: dedup revisi + filter status CANCEL & NEW
    $sql = "
        WITH acc AS (
            SELECT
                m.movement_code,
                m.movement_min,
                m.movement_plus,
                m.movement_date,
                CASE m.movement_type
                    WHEN 'RECEIVING'    THEN (SELECT status FROM receiving_hdr        WHERE rec_number      = m.movement_transnno LIMIT 1)
                    WHEN 'TRANSFER'     THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                    WHEN 'SUPPLY'       THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                    WHEN 'DELIVERY'     THEN (SELECT status FROM delivery_hdr         WHERE delivery_number = m.movement_transnno LIMIT 1)
                    WHEN 'RETURN'       THEN (SELECT status FROM dn_return_hdr        WHERE return_number   = m.movement_transnno LIMIT 1)
                    WHEN 'REPLACEMENT'  THEN (SELECT status FROM dn_replace_hdr       WHERE replace_number  = m.movement_transnno LIMIT 1)
                    WHEN 'ADJUSTMENT'   THEN (SELECT status FROM stock_adjustment_hdr WHERE adj_code        = m.movement_transnno LIMIT 1)
                    WHEN 'DN SEMENTARA' THEN (SELECT status FROM temporary_dn_hdr     WHERE tdn_number      = m.movement_transnno LIMIT 1)
                    WHEN 'DN UMUM'      THEN (SELECT status FROM dn_general_hdr        WHERE tdn_number      = m.movement_transnno LIMIT 1)
                    ELSE NULL
                END AS hdr_status,
                CASE
                    WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                         AND m.movement_plus = 0 AND m.movement_min = 0
                    THEN (SELECT CASE WHEN det.direction = '-' THEN -det.qty_adjustment ELSE det.qty_adjustment END
                          FROM stock_adjustment_det det
                          WHERE det.adj_code = m.movement_transnno AND det.article_code = m.artikel_code LIMIT 1)
                         * CASE WHEN m.movement_type = 'CANCEL ADJUSTMENT' THEN -1 ELSE 1 END
                    ELSE (m.movement_plus - m.movement_min)
                END AS net_value,
                ROW_NUMBER() OVER (
                    PARTITION BY m.artikel_code, m.movement_transnno, m.location_number
                    ORDER BY m.created_at DESC, m.movement_code DESC
                ) AS rn
            FROM warehouse_movement m
            WHERE m.artikel_code    = :art
              AND m.location_number = :loc
              AND m.site_code       = :site
              AND TO_DATE(m.movement_date,'DD-MM-YYYY') >= TO_DATE(:from,'DD-MM-YYYY')
              AND m.movement_type NOT LIKE 'CANCEL %'
              AND m.movement_type NOT LIKE 'DELETE%'
              AND m.movement_type NOT LIKE 'REVISI %'
              AND m.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
              AND NOT EXISTS (
                SELECT 1 FROM stock_adjustment_hdr h
                WHERE h.adj_code = m.movement_transnno AND h.adj_type = 'OPENING BALANCE'
              )
        )
        SELECT movement_code, net_value, movement_date
        FROM acc
        WHERE rn = 1
          AND hdr_status IS DISTINCT FROM '5'
        ORDER BY TO_DATE(movement_date,'DD-MM-YYYY'), movement_code
    ";

    $movements = DB::select($sql, [
        'art'  => $articleCode,
        'loc'  => $location,
        'site' => $this->siteCode,
        'from' => $this->startDateLedger,
    ]);

    if (empty($movements)) {
        DB::table('warehouse_stock')
            ->where('site_code',       $this->siteCode)
            ->where('article_code',    $articleCode)
            ->where('location_number', $location)
            ->update(['article_qty' => $balanceBefore]);
        return;
    }

    $running = $balanceBefore;
    foreach ($movements as $mov) {
        $running += (float) $mov->net_value;
        DB::table('warehouse_movement')
            ->where('movement_code', $mov->movement_code)
            ->update(['last_qty' => $running]);
    }

    DB::table('warehouse_stock')
        ->where('site_code',       $this->siteCode)
        ->where('article_code',    $articleCode)
        ->where('location_number', $location)
        ->update(['article_qty' => $running]);
}
}