<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\StockCountController;
use DB;

class RecalcStoAccuracy extends Command
{
    // Contoh pakai:
    //   php artisan sto:recalc-accuracy --config=2
    //   php artisan sto:recalc-accuracy --mapping=34
    //   php artisan sto:recalc-accuracy --config=2 --mapping=34   (bisa gabung, mapping override)
    //   php artisan sto:recalc-accuracy --all                     (SEMUA config, hati-hati kalau data banyak)
    protected $signature = 'sto:recalc-accuracy
                            {--config= : config_id spesifik, recalc semua mapping di dalamnya}
                            {--mapping=* : mapping_id spesifik, bisa lebih dari satu}
                            {--all : recalc SEMUA mapping di semua config (hati-hati, bisa lama)}';

    protected $description = 'Recalculate target_act_loc & target_act setelah perbaikan data qty_system/movement secara manual';

    public function handle()
    {
        $controller = new StockCountController();

        $mappingIds = collect();

        if ($this->option('all')) {
            $mappingIds = DB::table('sto_config_mapping')->pluck('mapping_id');
        }

        if ($configId = $this->option('config')) {
            $mappingIds = $mappingIds->concat(
                DB::table('sto_config_mapping')->where('config_id', $configId)->pluck('mapping_id')
            );
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

        $affectedConfigIds = collect();

        foreach ($mappingIds as $mappingId) {
            $configId = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->value('config_id');
            if ($configId) $affectedConfigIds->push($configId);

            $controller->recalcMappingProgress($mappingId);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // recalcMappingProgress sudah update target_act (global) tiap kali dipanggil,
        // tapi kalau ada beberapa mapping dalam 1 config, cukup dipanggil sekali per config
        // sudah cukup karena avg-nya dihitung ulang dari semua mapping di config yg sama.
        $this->info('Selesai. Config yang ter-refresh: ' . $affectedConfigIds->unique()->values()->implode(', '));

        return 0;
    }
}