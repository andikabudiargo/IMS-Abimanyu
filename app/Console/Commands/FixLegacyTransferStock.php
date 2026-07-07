<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TransferStockController;

class FixLegacyTransferStock extends Command
{
    protected $signature = 'transfer-stock:fix-legacy 
                            {--deploy-date= : Tanggal deploy skema baru (2026-07-07 10:14:33), transfer dibuat SEBELUM ini akan diproses}
                            {--dry-run : Tampilkan saja tanpa eksekusi}';

    protected $description = 'Proses stock/movement untuk transfer lama yang dibuat sebelum skema baru (store langsung posting)';

    public function handle()
    {
        $deployDate = $this->option('deploy-date');
        $dryRun     = $this->option('dry-run');

        if (!$deployDate) {
            $this->error('Wajib isi --deploy-date=2026-07-07 10:14:33 (waktu deploy kode baru)');
            return 1;
        }

        // Ambil transfer yang:
        // - dibuat SEBELUM deploy (masih pakai alur lama)
        // - statusnya belum POSTED (4) atau CANCELED (5)
        // - BELUM ADA movement sama sekali untuk tr_number ini (indikasi stock belum pernah diproses)
        $legacyTransfers = DB::table('transfer_stock_hdr')
            ->where('created_at', '<', $deployDate)
            ->whereIn('status', ['1', '2', '3'])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('warehouse_movement')
                  ->whereColumn('warehouse_movement.movement_transnno', 'transfer_stock_hdr.tr_number');
            })
            ->get();

        $this->info("Ditemukan {$legacyTransfers->count()} transfer lama yang belum diproses stock/movement-nya.");

        if ($legacyTransfers->isEmpty()) {
            return 0;
        }

        $this->table(
            ['tr_number', 'status', 'created_at', 'location_from', 'location_to'],
            $legacyTransfers->map(fn($t) => [$t->tr_number, $t->status, $t->created_at, $t->location_from, $t->location_to])
        );

        if ($dryRun) {
            $this->warn('DRY RUN — tidak ada perubahan dieksekusi.');
            return 0;
        }

        if (!$this->confirm('Lanjutkan memproses stock/movement untuk semua transfer di atas?')) {
            return 0;
        }

        // Panggil processPosting() via reflection karena method-nya private
        $controller = app(TransferStockController::class);
        $reflection = new \ReflectionClass($controller);
        $method     = $reflection->getMethod('processPosting');
        $method->setAccessible(true);

        $success = 0;
        $failed  = [];

        foreach ($legacyTransfers as $trf) {
            DB::beginTransaction();
            try {
                $result = $method->invoke($controller, $trf->tr_number, 'system-migration');

                if ($result['success']) {
                    DB::commit();
                    $success++;
                    $this->line("✓ {$trf->tr_number} berhasil diproses.");
                } else {
                    DB::rollBack();
                    $failed[] = $trf->tr_number . ': ' . implode(', ', (array) $result['message']);
                    $this->error("✗ {$trf->tr_number} gagal: " . implode(', ', (array) $result['message']));
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $failed[] = $trf->tr_number . ': ' . $e->getMessage();
                $this->error("✗ {$trf->tr_number} error: " . $e->getMessage());
            }
        }

        $this->info("Selesai. Berhasil: $success, Gagal: " . count($failed));
        if ($failed) {
            $this->warn('Daftar yang gagal:');
            foreach ($failed as $f) $this->line("  - $f");
        }

        return 0;
    }
}