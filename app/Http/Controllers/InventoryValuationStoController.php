<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Inventory Valuation versi STO Report: tabel & aturan sama persis (lokasi, periode,
 * movement), tapi in/out jadi Debit/Kredit dan angkanya nilai (qty x harga satuan
 * yang sama dengan kolom Nilai Persediaan STO Report), bukan qty.
 *
 * - Lokasi boleh banyak (multi-select); Summary menampilkan satu baris per lokasi.
 * - Referensi STO opsional. Tanpa STO: rentang tanggal wajib, dihitung on the fly dari
 *   movement, Nilai Hasil STO & Selisih = 0.
 * Daftar lokasi per STO memakai route stoReport.locations; drill dokumen pakai stoReport.movementDetail.
 */
class InventoryValuationStoController extends StoReportController
{
    protected $indexView = 'inventoryValuation.sto';

    public function __construct()
    {
        parent::__construct();
        $this->title = 'Inventory Valuation';
    }

    public function index()
    {
        $names = DB::table('stock_location_master')->whereIn('location_code', $this->supportedLocations)
            ->pluck('location_name', 'location_code');

        $all = collect($this->supportedLocations)->map(fn($c) => ['code' => $c, 'name' => $names[$c] ?? $c])
            ->sortBy('name')->values();

        return parent::index()->with('allLocations', $all);
    }

    public function data(Request $request)
    {
        $configId = $request->filled('config_id') ? Crypt::decryptString($request->config_id) : null;

        if (!$configId && !$request->filled('date_range')) {
            return response()->json(['status' => 0, 'message' => 'Tanpa referensi STO, rentang tanggal wajib diisi.'], 422);
        }

        $locs = array_values(array_intersect((array) $request->locations, $this->supportedLocations));
        if (!$locs) {
            return response()->json(['status' => 0, 'message' => 'Pilih minimal satu lokasi gudang.'], 422);
        }

        $out = [];
        $errors = [];
        foreach ($locs as $loc) {
            $res = $this->buildReport($configId, $loc, $request->date_range);
            if ($res['status'] === 0) {
                $errors[] = $loc . ': ' . $res['message'];
                continue;
            }
            $out[] = $this->toValuation($res, $loc, (bool) $configId);
        }

        if (!$out) {
            return response()->json(['status' => 0, 'message' => implode(' | ', $errors)], 422);
        }

        return response()->json(['status' => 1, 'locations' => $out, 'errors' => $errors]);
    }

    // Ubah hasil buildReport (qty) jadi nilai (qty x harga satuan) + total debit/kredit.
    private function toValuation(array $res, string $loc, bool $withSto): array
    {
        $cols = $this->getColumnKeys($loc);
        $keys = array_merge(['opening'], $cols['in'], $cols['out'], ['closing']);

        $totals = array_fill_keys(array_merge($keys, ['debit', 'kredit', 'valuation', 'diff']), 0);

        $rows = $res['rows']->map(function ($r) use ($keys, $cols, $withSto, &$totals) {
            foreach ($keys as $k) {
                $r->{$k} = round((float) $r->{$k} * (float) $r->unit_value, 2);
                $totals[$k] = round($totals[$k] + $r->{$k}, 2);
            }
            // Tanpa referensi STO: Nilai Hasil STO & Selisih = 0. Dengan STO: null kalau artikel belum dihitung.
            if (!$withSto) {
                $r->valuation = 0;
                $r->diff      = 0;
            } else {
                $r->diff = $r->valuation !== null ? round($r->valuation - $r->closing, 2) : null;
            }
            if ($r->valuation !== null) {
                $totals['valuation'] = round($totals['valuation'] + $r->valuation, 2);
                $totals['diff']      = round($totals['diff'] + $r->diff, 2);
            }
            foreach ($cols['in'] as $k)  { $totals['debit']  = round($totals['debit']  + $r->{$k}, 2); }
            foreach ($cols['out'] as $k) { $totals['kredit'] = round($totals['kredit'] + $r->{$k}, 2); }
            return $r;
        })->values();

        return [
            'header'  => $res['header'],
            'rows'    => $rows,
            'totals'  => $totals,
            'columns' => $res['columns'],
        ];
    }
}
