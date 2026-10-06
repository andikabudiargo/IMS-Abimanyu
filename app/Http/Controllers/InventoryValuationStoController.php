<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Inventory Valuation versi STO Report: tabel & aturan sama persis (lokasi, periode,
 * movement), tapi in/out jadi Debit/Kredit dan angkanya nilai (qty x harga satuan
 * yang sama dengan kolom Nilai Persediaan STO Report), bukan qty.
 * Daftar lokasi memakai route stoReport.locations.
 */
class InventoryValuationStoController extends StoReportController
{
    protected $indexView = 'inventoryValuation.sto';

    public function __construct()
    {
        parent::__construct();
        $this->title = 'Inventory Valuation';
    }

    public function data(Request $request)
    {
        $result = $this->buildReport(Crypt::decryptString($request->config_id), $request->location_code, $request->date_range);

        if ($result['status'] === 0) {
            return response()->json(['status' => 0, 'message' => $result['message']], $result['code'] ?? 422);
        }

        $cols = $this->getColumnKeys($request->location_code);
        $keys = array_merge(['opening'], $cols['in'], $cols['out'], ['closing']);

        $totals = array_fill_keys($keys, 0);
        $totals['valuation'] = null;
        $totals['diff']      = null;

        $rows = $result['rows']->map(function ($r) use ($keys, &$totals) {
            foreach ($keys as $k) {
                $r->{$k} = round((float) $r->{$k} * (float) $r->unit_value, 2);
                $totals[$k] = round($totals[$k] + $r->{$k}, 2);
            }
            // Selisih nilai: hasil STO (nilai) - saldo (nilai); null bila belum dihitung STO
            $r->diff = $r->valuation !== null ? round($r->valuation - $r->closing, 2) : null;
            if ($r->valuation !== null) {
                $totals['valuation'] = round(($totals['valuation'] ?? 0) + $r->valuation, 2);
                $totals['diff']      = round(($totals['diff'] ?? 0) + $r->diff, 2);
            }
            return $r;
        })->values();

        return response()->json([
            'status'  => 1,
            'header'  => $result['header'],
            'rows'    => $rows,
            'totals'  => $totals,
            'columns' => $result['columns'],
        ]);
    }
}
