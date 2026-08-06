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
    private string $targetLocation = '006';

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
        // Saldo per cutoff (30 Juni 2026): OB (berapapun tanggalnya) + net movement
        // sampai batas GREATEST(ob_date, cutoff) -> yaitu function get_last_qty_new
        // yang sudah menerapkan cutoff. Kalau tidak ada OB, ini otomatis jadi 0.
        $balanceBefore = (float) DB::selectOne(
            "SELECT get_last_qty_new(?, ?, ?, ?) AS bal",
            [$articleCode, $this->cutoffDateSql, $this->siteCode, $location]
        )->bal;

        $movements = DB::table('warehouse_movement')
            ->where('artikel_code',    $articleCode)
            ->where('location_number', $location)
            ->where('site_code',        $this->siteCode)
            ->where(DB::raw("TO_DATE(movement_date,'DD-MM-YYYY')"), '>=',
                DB::raw("TO_DATE('{$this->startDateLedger}','DD-MM-YYYY')"))
            ->where('movement_type', 'NOT LIKE', 'CANCEL %')
            ->where('movement_type', 'NOT LIKE', 'DELETE%')
            ->where('movement_type', 'NOT LIKE', 'REVISI %')
            ->whereNotIn('movement_type', ['RETURN-CANCEL', 'RETURN-REVERSE'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('stock_adjustment_hdr')
                  ->whereColumn('stock_adjustment_hdr.adj_code', 'warehouse_movement.movement_transnno')
                  ->where('stock_adjustment_hdr.adj_type', 'OPENING BALANCE');
            })
            ->orderBy(DB::raw("TO_DATE(movement_date,'DD-MM-YYYY')"), 'asc')
            ->orderBy('movement_code', 'asc')
            ->select('movement_code', 'movement_min', 'movement_plus')
            ->get();

        if ($movements->isEmpty()) {
            // Tidak ada movement setelah cutoff -> saldo warehouse_stock = saldo per cutoff.
            DB::table('warehouse_stock')
                ->where('site_code',       $this->siteCode)
                ->where('article_code',    $articleCode)
                ->where('location_number', $location)
                ->update(['article_qty' => $balanceBefore]);
            return;
        }

        $running = $balanceBefore;
        foreach ($movements as $mov) {
            $running += (float)$mov->movement_plus - (float)$mov->movement_min;
            DB::table('warehouse_movement')
                ->where('movement_code', $mov->movement_code)
                ->update(['last_qty' => $running]);
        }

        // Ambil dari $running terakhir (hasil loop di atas), BUKAN query ulang
        // tanpa filter ke warehouse_movement. Query ulang tanpa filter tipe
        // berisiko mengambil movement CANCEL/DELETE/REVISI/RETURN-* terakhir
        // yang last_qty-nya tidak pernah di-update di loop ini (stale value).
        DB::table('warehouse_stock')
            ->where('site_code',       $this->siteCode)
            ->where('article_code',    $articleCode)
            ->where('location_number', $location)
            ->update(['article_qty' => $running]);
    }
}