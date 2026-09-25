<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

/**
 * Stock Report — sama dengan STO Report (kolom Opening / IN / OUT / Balance per
 * grup lokasi) tapi TANPA STO: filter langsung tanggal + lokasi, tanpa kolom
 * Hasil STO / Variance / Status / Akurasi / Nilai Persediaan / Consumption.
 *
 * Mewarisi StoReportController supaya konfigurasi kolom movement, agregasi,
 * filter artikel, dan drill-down dokumen SATU sumber dengan STO Report.
 * Grup CHEMICAL (005/006/009) dan WIP_FG_OT (012/007/008) ikut $locationGroups di parent.
 */
class StockReportController extends StoReportController
{
    public function __construct()
    {
        $this->title      = 'Stock Report';
        $this->moduleCode = 'STOCK_REPORT';
    }

    public function index()
    {
        $locations = DB::table('stock_location_master')
            ->whereIn('location_code', $this->supportedLocations)
            ->orderBy('location_name')
            ->get(['location_code', 'location_name']);

        return view('stockReport.index', [
            'title'     => $this->title,
            'subtitle'  => $this->title,
            'locations' => $locations,
        ]);
    }

    // "dd-mm-YYYY to dd-mm-YYYY" (atau satu tanggal) → [from, to, openingDate(Y-m-d, H-1 dari from)] | null
    private function parseRange($range)
    {
        $parts = explode(' to ', (string) $range);
        $from  = trim($parts[0] ?? '');
        $to    = trim($parts[1] ?? $from);
        $dt    = \DateTime::createFromFormat('d-m-Y', $from);
        if (!$from || !$to || !$dt || !\DateTime::createFromFormat('d-m-Y', $to)) return null;

        return [$from, $to, date('Y-m-d', strtotime($dt->format('Y-m-d') . ' -1 day'))];
    }

    public function data(Request $request)
    {
        $locationCode = $request->location_code;

        if (!in_array($locationCode, $this->supportedLocations)) {
            return response()->json(['status' => 0, 'message' => 'Lokasi ini belum didukung format reportnya.'], 422);
        }
        if (!($range = $this->parseRange($request->date_range))) {
            return response()->json(['status' => 0, 'message' => 'Rentang tanggal wajib diisi.'], 422);
        }
        [$dateFrom, $dateTo, $openingDate] = $range;

        $family = $this->resolveLocationFamily($locationCode);
        $anchor = $this->resolveLocationAnchor($locationCode);
        $cols   = $this->getColumnKeys($locationCode);

        $movements    = $this->aggregateMovements($family, $dateFrom, $dateTo, $locationCode);
        $allowedTypes = $this->locationArticleTypeMap[$anchor] ?? null;

        // artikel: yang ada movement di rentang + yang punya stok sekarang (sama seperti STO Report)
        $stockQuery = DB::table('warehouse_stock as ws')
            ->join('article as a', 'a.article_alternative_code', '=', 'ws.article_code')
            ->whereIn('ws.location_number', $family)
            ->where('ws.article_qty', '<>', 0);
        if ($allowedTypes) $stockQuery->whereIn('a.article_type', $allowedTypes);

        $moveCodes = $movements->keys();
        if ($allowedTypes && $moveCodes->isNotEmpty()) {
            $moveCodes = DB::table('article')->whereIn('article_code', $moveCodes)
                ->whereIn('article_type', $allowedTypes)->pluck('article_code');
        }

        $realCodes = $moveCodes->merge($stockQuery->pluck('a.article_code'))
            ->filter()->map(fn($c) => (string) $c)->unique()->values();

        $header = [
            'location_code' => $locationCode,
            'location_name' => DB::table('stock_location_master')->where('location_code', $locationCode)->value('location_name') ?? $locationCode,
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
            'has_children'  => count($family) > 1, // Balance bisa diklik → rincian per lokasi anak
        ];

        $columns = $this->buildColumnDefs($locationCode);
        $totals  = ['opening' => 0, 'closing' => 0];
        foreach (array_merge($cols['in'], $cols['out']) as $k) $totals[$k] = 0;

        if ($realCodes->isEmpty()) {
            return response()->json(['status' => 1, 'header' => $header, 'rows' => [], 'totals' => $totals, 'columns' => $columns]);
        }

        $articles = DB::table('article as a')
            ->leftJoin('third_party as tp', 'tp.kode', '=', 'a.third_party')
            ->whereIn('a.article_code', $realCodes)
            ->select('a.article_code', 'a.article_alternative_code', 'a.article_desc', 'a.uom',
                DB::raw('COALESCE(tp.nama, a.third_party) as supp_name'))
            ->get()->keyBy('article_code');

        $rows = collect();
        foreach ($realCodes as $rc) {
            $meta = $articles->get($rc);
            $mv   = $movements->get($rc);

            // configId null → adjustment TIDAK dikecualikan (beda dgn STO Report yang cutoff sebelum STO)
            $opening = round((float) $this->getOpeningBalance($rc, $openingDate, $anchor, null), 2);

            $inTotal = $outTotal = 0;
            $vals = [];
            foreach ($cols['in'] as $k)  { $v = $mv ? (float) ($mv->{$k} ?? 0) : 0; $vals[$k] = round($v, 2); $inTotal  += $v; }
            foreach ($cols['out'] as $k) { $v = $mv ? (float) ($mv->{$k} ?? 0) : 0; $vals[$k] = round($v, 2); $outTotal += $v; }

            $closing = round($opening + $inTotal - $outTotal, 2);
            if ($opening == 0 && $inTotal == 0 && $outTotal == 0 && $closing == 0) continue;

            $rows->push((object) array_merge([
                'article_code' => $rc,
                'alt_code'     => $meta->article_alternative_code ?? $rc,
                'article_desc' => $meta->article_desc ?? $rc,
                'supp'         => $meta->supp_name ?? '-',
                'uom'          => $meta->uom ?? '-',
                'opening'      => $opening,
            ], $vals, ['closing' => $closing]));
        }

        $rows = $rows->sortBy('article_desc')->values()->map(function ($r, $i) use (&$totals, $cols) {
            $r->no = $i + 1;
            foreach (array_merge(['opening'], $cols['in'], $cols['out'], ['closing']) as $k) {
                $totals[$k] = round($totals[$k] + $r->{$k}, 2);
            }
            return $r;
        });

        return response()->json(['status' => 1, 'header' => $header, 'rows' => $rows, 'totals' => $totals, 'columns' => $columns]);
    }

    // Modal Balance (lokasi punya child, mis. WIP): saldo akhir per lokasi anak, dari movement.
    public function balanceDetail(Request $request)
    {
        $locationCode = $request->location_code;
        $articleCode  = $request->article_code;

        if (!in_array($locationCode, $this->supportedLocations)) {
            return response()->json(['status' => 0, 'message' => 'Lokasi ini belum didukung format reportnya.'], 422);
        }
        if (!($range = $this->parseRange($request->date_range))) {
            return response()->json(['status' => 0, 'message' => 'Rentang tanggal wajib diisi.'], 422);
        }
        [$dateFrom, $dateTo, $openingDate] = $range;

        $names = DB::table('stock_location_master')
            ->whereIn('location_code', $this->resolveLocationFamily($locationCode))
            ->pluck('location_name', 'location_code');

        // Saldo per lokasi murni dari warehouse_movement (child tidak punya article qty di
        // warehouse_stock): SUM(plus - min) s/d tanggal akhir, filter dokumen batal sama dgn tabel.
        $rows = [];
        foreach ($names as $loc => $name) {
            $mv = $this->fetchFilteredMovementRows(
                [(string) $loc], $articleCode, '01-01-1900', $dateTo, null,
                'COALESCE(wm.movement_plus,0) - COALESCE(wm.movement_min,0)'
            );
            $qty = round(array_sum(array_column($mv, 'qty')), 2);
            if ($qty != 0) $rows[] = ['location' => $name, 'qty' => $qty];
        }

        return response()->json([
            'status' => 1,
            'rows'   => $rows,
            'total'  => round(array_sum(array_column($rows, 'qty')), 2),
        ]);
    }


    public function export(Request $request)
    {
        $res = $this->data($request);
        $d   = $res->getData(true);

        if (($d['status'] ?? 0) !== 1) {
            return back()->with('error', $d['message'] ?? 'Gagal export.');
        }

        // rows sudah berupa array (hasil serialisasi JSON)
        $h = $d['header'];
        $fileName = sprintf('Stock_Report_%s_%s_sd_%s_%s.xlsx',
            $h['location_code'], $h['date_from'], $h['date_to'], date('Ymd_His'));

        return \Excel::download(new \App\Exports\StockReportExport($d), $fileName);
    }

    // Drill-down angka opening/in/out → daftar dokumen (filter sama dgn STO Report).
    public function movementDetail(Request $request)
    {
        $locationCode = $request->location_code;
        $articleCode  = $request->article_code;
        $columnKey    = $request->column_key;

        if (!in_array($locationCode, $this->supportedLocations)) {
            return response()->json(['status' => 0, 'message' => 'Lokasi ini belum didukung format reportnya.'], 422);
        }
        if (!($range = $this->parseRange($request->date_range))) {
            return response()->json(['status' => 0, 'message' => 'Rentang tanggal wajib diisi.'], 422);
        }
        [$dateFrom, $dateTo, $openingDate] = $range;

        $family = $this->resolveLocationFamily($locationCode);

        if ($columnKey === 'opening') {
            $rows  = $this->buildOpeningBreakdown($articleCode, $family, $openingDate, null, $locationCode);
            $label = 'Opening Balance';
        } else {
            $groupConfig = $this->getGroupConfig($locationCode);
            $def = $groupConfig['in'][$columnKey] ?? $groupConfig['out'][$columnKey] ?? null;
            if (!$def) {
                return response()->json(['status' => 0, 'message' => 'Kolom tidak dikenali.'], 422);
            }
            $rows  = $this->fetchFilteredMovementRows($family, $articleCode, $dateFrom, $dateTo,
                array_map('strtoupper', $def['types']), "wm.{$def['qty']}");
            $label = $def['label'];
        }

        return response()->json([
            'status' => 1,
            'label'  => $label,
            'rows'   => $rows,
            'total'  => round(array_sum(array_column($rows, 'qty')), 2),
        ]);
    }
}
