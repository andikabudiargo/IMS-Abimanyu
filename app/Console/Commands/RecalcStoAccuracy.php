<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StoAccuracyRecalcService;
use DB;

class RecalcStoAccuracy extends Command
{
    protected $signature = 'sto:recalc-accuracy
                        {--config= : config_id spesifik, recalc semua mapping di dalamnya}
                        {--mapping=* : mapping_id spesifik, bisa lebih dari satu}
                        {--all : recalc SEMUA mapping di semua config (hati-hati, bisa lama)}
                        {--refresh-qty-system : ikut refresh qty_system/qty_variance/count_status di sto_dtl (ada history log)}
                        {--include-finished : dipakai bareng --refresh-qty-system, ikut sertakan STO yang sudah FINISHED}';

    protected $description = 'Recalculate target_act_loc & target_act, opsional refresh qty_system (dengan history log)';

    public function handle(StoAccuracyRecalcService $service)
    {
        $mappingIds = collect();

        if ($this->option('all')) {
            $mappingIds = DB::table('sto_config_mapping')->pluck('mapping_id');
        }
        if ($configId = $this->option('config')) {
            $mappingIds = $mappingIds->concat($service->resolveMappingIdsForConfig($configId));
        }
        foreach ($this->option('mapping') as $mid) {
            $mappingIds->push((int) $mid);
        }
        $mappingIds = $mappingIds->unique()->values();

        if ($mappingIds->isEmpty()) {
            $this->error('Tidak ada mapping_id yang dipilih. Pakai --config=, --mapping=, atau --all.');
            return 1;
        }

        $bar = $this->output->createProgressBar($mappingIds->count());
        $bar->start();

        $result = $service->recalcMappingIds(
            $mappingIds,
            $this->option('refresh-qty-system'),
            $this->option('include-finished'),
            'artisan:sto:recalc-accuracy',
            'cli',
            fn() => $bar->advance()
        );

        $bar->finish();
        $this->newLine();

        if ($this->option('refresh-qty-system')) {
            $this->info("Qty system: {$result['total_changed']}/{$result['total_checked']} baris berubah.");
        }
        $this->info('Selesai. Config yang ter-refresh: ' . implode(', ', $result['affected_config_ids']));

        return 0;
    }
}