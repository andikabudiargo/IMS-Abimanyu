<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Dipisah dari StockCountController supaya StoReportController (dan controller
 * lain yang butuh) pakai definisi family/anchor/adjustment yang PERSIS SAMA.
 * Jangan duplikat method-method ini lagi di controller lain — import trait ini.
 */
trait HasStoLocationFamily
{
    private $locationFamilyCache = [];
    private $locationParentCache = [];
    private $stoPeriodeCache     = [];
    private $adjDeltaCache       = [];

    // ══════════════════════════════════════════════
    // FAMILY — parent + semua sibling child
    // ══════════════════════════════════════════════
    protected function resolveLocationFamily($locationCode)
    {
        if (isset($this->locationFamilyCache[$locationCode])) {
            return $this->locationFamilyCache[$locationCode];
        }

        $loc = DB::table('stock_location_master')->where('location_code', $locationCode)->first();
        if (!$loc) {
            return $this->locationFamilyCache[$locationCode] = [$locationCode];
        }

        if (!empty($loc->parent_location)) {
            $siblings = DB::table('stock_location_master')
                ->where('parent_location', $loc->parent_location)
                ->pluck('location_code')->toArray();
            $family = array_values(array_unique(array_merge([$loc->parent_location], $siblings)));
            return $this->locationFamilyCache[$locationCode] = $family;
        }

        $children = DB::table('stock_location_master')
            ->where('parent_location', $locationCode)
            ->pluck('location_code')->toArray();
        if (!empty($children)) {
            $family = array_values(array_unique(array_merge([$locationCode], $children)));
            return $this->locationFamilyCache[$locationCode] = $family;
        }

        return $this->locationFamilyCache[$locationCode] = [$locationCode];
    }

    // child → parent-nya; parent/standalone → dirinya sendiri
    protected function resolveLocationAnchor($locationCode)
    {
        if ($locationCode === null || $locationCode === '') return $locationCode;
        if (array_key_exists($locationCode, $this->locationParentCache)) {
            return $this->locationParentCache[$locationCode];
        }
        $loc = DB::table('stock_location_master')->where('location_code', $locationCode)->first();
        $anchor = ($loc && !empty($loc->parent_location)) ? $loc->parent_location : $locationCode;
        return $this->locationParentCache[$locationCode] = $anchor;
    }

    // ══════════════════════════════════════════════
    // ADJUSTMENT PERIODE BERJALAN — dikecualikan dari qty_system/opening balance.
    // Adjustment dibuat SETELAH STO, jadi tidak boleh jadi pembanding.
    // ══════════════════════════════════════════════
    protected function resolveStoPeriode($configId)
    {
        if (!$configId) return null;
        if (array_key_exists($configId, $this->stoPeriodeCache)) {
            return $this->stoPeriodeCache[$configId];
        }

        $p = DB::table('sto_config')->where('config_id', $configId)->value('periode');
        $val = null;

        if ($p) {
            if (preg_match('/^(\d{4})-(\d{2})/', $p, $mt)) {
                $val = ['year' => (int) $mt[1], 'month' => (int) $mt[2]];
            } elseif (preg_match('/^(\d{2})-(\d{4})/', $p, $mt)) {
                $val = ['year' => (int) $mt[2], 'month' => (int) $mt[1]];
            }
        }

        return $this->stoPeriodeCache[$configId] = $val;
    }

    protected function sumAdjustmentDeltaForPeriode($realCode, array $locations, $periode)
    {
        if (!$periode || !$realCode || empty($locations)) return 0;

        $key = $realCode.'|'.implode(',', $locations).'|'.$periode['year'].'-'.$periode['month'];
        if (array_key_exists($key, $this->adjDeltaCache)) {
            return $this->adjDeltaCache[$key];
        }

        $delta = (float) DB::table('warehouse_movement as wm')
            ->join('stock_adjustment_hdr as sa', 'sa.adj_code', '=', 'wm.movement_transnno')
            ->where('wm.artikel_code', $realCode)
            ->whereIn('wm.location_number', $locations)
            ->whereIn('wm.movement_type', ['ADJUSTMENT', 'CANCEL ADJUSTMENT'])
            ->where('sa.periode', $periode['month'])
            ->whereRaw("RIGHT(sa.adj_date, 4) = ?", [(string) $periode['year']])
            ->sum(DB::raw('COALESCE(wm.movement_plus,0) - COALESCE(wm.movement_min,0)'));

        return $this->adjDeltaCache[$key] = $delta;
    }

    // ══════════════════════════════════════════════
    // BALANCE AT DATE — family-aware, adjustment-excluded.
    // Dipakai baik untuk qty_system (STO) maupun opening balance (Report).
    // ══════════════════════════════════════════════
    protected function resolveFamilyBalance($realCode, $location, $targetDate, $configId = null)
    {
        $family = $this->resolveLocationFamily($location);

        if (count($family) <= 1) {
            $row = DB::selectOne(
                "SELECT get_last_qty_new(?, ?, 'HO', ?) AS q",
                [$realCode, $targetDate, $location]
            );
        } else {
            $pgArray = '{' . implode(',', array_map(function ($c) {
                return '"' . str_replace('"', '\\"', $c) . '"';
            }, $family)) . '}';

            $row = DB::selectOne(
                "SELECT get_last_qty_new_grouped(?, ?, 'HO', ?::varchar[]) AS q",
                [$realCode, $targetDate, $pgArray]
            );
        }

        $qty = $row ? (float) $row->q : 0;

        if ($configId) {
            $periode = $this->resolveStoPeriode($configId);
            $qty -= $this->sumAdjustmentDeltaForPeriode($realCode, $family, $periode);
        }

        return $qty;
    }
}