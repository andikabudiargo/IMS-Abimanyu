<?php
// app/Services/StoAccuracyRecalcService.php

namespace App\Services;

use App\Http\Controllers\StockCountController;
use DB;

class StoAccuracyRecalcService
{
    public function recalcMappingIds($mappingIds, bool $refreshQty = false, bool $includeFinished = false, string $recalculatedBy = 'system', string $source = 'manual', ?callable $onProgress = null): array
    {
        $controller = new StockCountController();
        $affectedConfigIds = collect();
        $totalChanged = 0;
        $totalChecked = 0;

        foreach ($mappingIds as $mappingId) {
            $configId = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->value('config_id');
            if ($configId) $affectedConfigIds->push($configId);

            $controller->recalcMappingProgress($mappingId);

            if ($refreshQty) {
                $result = $controller->refreshQtySystemForMapping($mappingId, $recalculatedBy, $source, $includeFinished);
                $totalChanged += $result['changed'];
                $totalChecked += $result['checked'];
            }

            if ($onProgress) $onProgress($mappingId);
        }

        return [
            'affected_config_ids' => $affectedConfigIds->unique()->values()->all(),
            'total_checked'       => $totalChecked,
            'total_changed'       => $totalChanged,
        ];
    }

    public function resolveMappingIdsForConfig($configId): \Illuminate\Support\Collection
    {
        return DB::table('sto_config_mapping')->where('config_id', $configId)->pluck('mapping_id');
    }
}