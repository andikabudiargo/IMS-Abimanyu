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
 *
 * PENTING — beda sengaja dari StoReportController::buildReport():
 * unit_value yang dihasilkan buildReport() pakai avgDnValues()/avgReceivingValues()
 * (bulan report dulu, kalau kosong mundur bulan demi bulan). Untuk Inventory
 * Valuation, harga satuan dihitung RATA-RATA SATU TAHUN PENUH (tahun dari periode
 * report), BUKAN per bulan + fallback mundur — lihat applyYearlyUnitValue() dan
 * avg*Yearly() di bawah. Method avg* milik StoReportController private, jadi tidak
 * bisa dipakai ulang; versi tahunan ini sengaja berdiri sendiri di sini.
 */
class InventoryValuationStoController extends StoReportController
{
    protected $indexView = 'inventoryValuation.sto';
    protected $includeLedgerArticles = true;

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
            $this->applyYearlyUnitValue($res, $loc);
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

        $totals = array_fill_keys(array_merge($keys, ['debit', 'kredit', 'valuation', 'diff', 'consumption_value']), 0);

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
            if ($r->consumption_value !== null) {
                $totals['consumption_value'] = round($totals['consumption_value'] + $r->consumption_value, 2);
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

    // ══════════════════════════════════════════════
    // BARU — harga satuan RATA-RATA SATU TAHUN PENUH, khusus Inventory
    // Valuation. Menimpa unit_value/valuation/consumption_value hasil
    // buildReport() (yang masih pakai logika bulan-dulu-lalu-mundur bulan
    // demi bulan dari StoReportController) SEBELUM toValuation() mengalikan
    // qty x unit_value. Kalau role tidak berhak lihat valuation, buildReport()
    // sudah set unit_value=0/valuation=null untuk semua baris — tidak perlu
    // diapa-apakan lagi di sini.
    // ══════════════════════════════════════════════
    private function applyYearlyUnitValue(array &$res, string $loc): void
    {
        if (empty($res['can_see_valuation']) || $res['rows']->isEmpty()) {
            return;
        }

        $year   = $this->reportYear($res['header']);
        $anchor = $this->resolveLocationAnchor($loc);
        $isWip  = $anchor === '012';
        $group  = $this->getLocationGroup($loc);

        $codes = $res['rows']->pluck('article_code')->map(fn($c) => (string) $c)->unique()->values()->all();
        $types = DB::table('article')->whereIn('article_code', $codes)->pluck('article_type', 'article_code');

        $fgCodes    = collect($codes)->filter(fn($c) => strtoupper($types[$c] ?? '') === 'FG')->values()->all();
        $otherCodes = collect($codes)->diff($fgCodes)->values()->all();

        $fgAvg    = $isWip ? $this->avgBomRmValuesYearly($fgCodes, $year) : $this->avgDnValuesYearly($fgCodes, $year);
        $otherAvg = $this->avgReceivingValuesYearly($otherCodes, $year);

        $res['rows'] = $res['rows']->map(function ($r) use ($fgAvg, $otherAvg, $types, $group) {
            $code      = (string) $r->article_code;
            $isFg      = strtoupper($types[$code] ?? '') === 'FG';
            $unitValue = $isFg ? ($fgAvg[$code] ?? 0.0) : ($otherAvg[$code] ?? 0.0);

            $r->unit_value = round($unitValue, 4);
            $r->valuation  = $r->qty_sto !== null ? round($unitValue * $r->qty_sto, 2) : null;

            if ($group === 'CHEMICAL' && $r->consumption_qty !== null) {
                $r->consumption_value = round($unitValue * $r->consumption_qty, 2);
            }

            return $r;
        })->values();
    }

    // Tahun dipakai buat window rata-rata = tahun dari date_to periode report ini.
    private function reportYear(array $header): int
    {
        if (!empty($header['date_to']) && preg_match('/-(\d{4})$/', $header['date_to'], $m)) {
            return (int) $m[1];
        }
        return (int) date('Y');
    }

    // Versi tahunan avgDnValues() — SUM satu tahun penuh (Jan-Des $year), tanpa
    // mundur bulan demi bulan kalau kosong.
    private function avgDnValuesYearly(array $codes, int $year): array
    {
        if (!$codes) return [];

        $start = sprintf('%04d-01-01', $year);
        $end   = sprintf('%04d-12-31', $year);
        $in    = implode(',', array_fill(0, count($codes), '?'));

        $rows = DB::select("
            SELECT dd.article_code, SUM(dd.qty * COALESCE(sod.price,0)) AS val, SUM(dd.qty) AS q
            FROM delivery_det dd
            JOIN delivery_hdr dh ON dh.delivery_number = dd.delivery_number
            CROSS JOIN LATERAL (SELECT to_date(dh.delivery_date, 'DD-MM-YYYY') AS dt) d
            LEFT JOIN sales_order_det sod ON sod.so_code = dd.so_number AND sod.article_code = dd.article_code
            WHERE dd.article_code IN ($in)
              AND d.dt BETWEEN ?::date AND ?::date
              AND dh.status NOT IN ('5','7')
            GROUP BY dd.article_code",
            array_merge($codes, [$start, $end]));

        $out = [];
        foreach ($rows as $r) {
            $out[$r->article_code] = (float) $r->q > 0 ? (float) $r->val / (float) $r->q : 0.0;
        }
        return $out;
    }

    // Versi tahunan avgReceivingValues() — SUM satu tahun penuh, fresh dari
    // receiving_det (bukan warehouse_stock.avg_price), tanpa mundur bulan.
    private function avgReceivingValuesYearly(array $codes, int $year): array
    {
        if (!$codes) return [];

        $start = sprintf('%04d-01-01', $year);
        $end   = sprintf('%04d-12-31', $year);
        $in    = implode(',', array_fill(0, count($codes), '?'));

        $rows = DB::select("
            SELECT article_code, SUM(price*qty) AS val,
                   SUM(qty * COALESCE(NULLIF(conv_factor,0),1)) AS q
            FROM receiving_det
            WHERE article_code IN ($in) AND created_at::date BETWEEN ?::date AND ?::date
            GROUP BY article_code",
            array_merge($codes, [$start, $end]));

        $out = [];
        foreach ($rows as $r) {
            if ((float) $r->q == 0.0) continue;
            $out[$r->article_code] = (float) $r->val / (float) $r->q;
        }
        return $out;
    }

    // Versi tahunan avgBomRmValues() — harga FG = SUM (bom_rm.qty x harga receiving
    // RM tahunan) dari BOM aktif terbaru FG tsb.
    private function avgBomRmValuesYearly(array $fgCodes, int $year): array
    {
        if (!$fgCodes) return [];

        $in = implode(',', array_fill(0, count($fgCodes), '?'));
        $rows = DB::select("
            WITH rm AS (
                SELECT h.id, h.article_code AS fg, r.article_code AS rm, r.qty
                FROM bom_hdr h JOIN bom_rm r ON r.bom_code = h.bom_code
                WHERE h.article_code IN ($in) AND h.status NOT IN ('5','7')
                UNION ALL
                SELECT h.id, h.article_code, h.article_code_rm, 1
                FROM bom_hdr h
                WHERE h.article_code IN ($in) AND h.status NOT IN ('5','7')
                  AND h.article_code_rm IS NOT NULL AND h.article_code_rm <> ''
                  AND NOT EXISTS (SELECT 1 FROM bom_rm r WHERE r.bom_code = h.bom_code)
            )
            SELECT fg, rm, qty FROM rm
            WHERE id = (SELECT MAX(id) FROM rm x WHERE x.fg = rm.fg)",
            array_merge($fgCodes, $fgCodes));

        $rmCodes = array_values(array_unique(array_map(fn($r) => (string) $r->rm, $rows)));
        $rmPrice = $this->avgReceivingValuesYearly($rmCodes, $year);

        $out = [];
        foreach ($rows as $r) {
            $out[$r->fg] = ($out[$r->fg] ?? 0.0) + (float) $r->qty * ($rmPrice[$r->rm] ?? 0.0);
        }
        return $out;
    }
}