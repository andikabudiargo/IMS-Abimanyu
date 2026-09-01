<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Traits\HasStoLocationFamily;
use DB;

class StoReportController extends Controller
{
    use HasStoLocationFamily; // ← BARU: family/anchor/adjustment logic sama persis dgn StockCountController

    private $title;
    private $moduleCode;

    // Semua lokasi yang didukung format report-nya (gabungan dari semua grup)
    private $supportedLocations = ['005', '006', '009', '012', '007', '008'];

    // ══════════════════════════════════════════════
    // GRUP LOKASI
    // ══════════════════════════════════════════════
    private $locationGroups = [
        'CHEMICAL'   => ['005', '006', '009'],
        'WIP_FG_OT'  => ['012', '007', '008'], // WIP, Finish Goods, OT
    ];

    // ══════════════════════════════════════════════
    // BARU: article_type valid per lokasi — DISALIN 1:1 dari
    // StockCountController::$locationArticleTypeMap supaya report tidak
    // menghitung artikel yang secara definisi tidak relevan untuk lokasi ini
    // (mis. artikel FG ikut ke-agregasi di lokasi chemical 006).
    //
    // PENTING: kalau map di StockCountController berubah, update juga di sini.
    // Idealnya dipindah ke satu config/service bersama — lihat catatan di akhir file.
    // ══════════════════════════════════════════════
    private $locationArticleTypeMap = [
        '042' => ['CM1'],
        '009' => ['RMP', 'RMNP'],
        '007' => ['FG'],
        '008' => ['FG'],
        '006' => ['CM2', 'CM3', 'RMP', 'RMNP'],
        '005' => ['CM1'],
        '049' => ['CM1'],
        // '012' (WIP parent) sengaja tidak dibatasi types-nya di StockCountController
        // (phantomArticleTypeMap-nya cuma FG), jadi di sini juga dibiarkan null → semua tipe.
    ];

    // ══════════════════════════════════════════════
    // KONFIGURASI KATEGORI MOVEMENT PER GRUP (tidak berubah)
    // ══════════════════════════════════════════════
    private $movementConfig = [
        'CHEMICAL' => [
            'in' => [
                'in_receiving'        => ['label' => 'Receiving',        'types' => ['RECEIVING'],        'qty' => 'movement_plus'],
                'in_return_transfer'  => ['label' => 'Return',  'types' => ['TRANSFER'],         'qty' => 'movement_plus'],
                'in_replace_supplier' => ['label' => 'Supplier Replace', 'types' => ['SUPPLIER REPLACE'], 'qty' => 'movement_plus'],
            ],
            'out' => [
                'out_supply_transfer' => ['label' => 'Supply', 'types' => ['SUPPLY', 'TRANSFER'], 'qty' => 'movement_min'],
                'out_return_supplier' => ['label' => 'Return Supplier', 'types' => ['SUPPLIER RETURN'],    'qty' => 'movement_min'],
                'out_dn_umum'         => ['label' => 'DN Umum',         'types' => ['DN UMUM'],            'qty' => 'movement_min'],
            ],
        ],
        'WIP_FG_OT' => [
            'in' => [
                'in_transfer'        => ['label' => 'Transfer In',     'types' => ['TRANSFER'], 'qty' => 'movement_plus'],
                'in_return_customer' => ['label' => 'Return Customer', 'types' => ['RETURN'],    'qty' => 'movement_plus'],
            ],
            'out' => [
                'out_transfer'     => ['label' => 'Transfer Out', 'types' => ['TRANSFER'],      'qty' => 'movement_min'],
                'out_delivery'     => ['label' => 'Delivery',     'types' => ['DELIVERY'],      'qty' => 'movement_min'],
                'out_dn_umum'      => ['label' => 'DN Umum',      'types' => ['DN UMUM'],       'qty' => 'movement_min'],
                'out_dn_sementara' => ['label' => 'DN Sementara', 'types' => ['DN SEMENTARA'],  'qty' => 'movement_min'],
                'out_replacement'  => ['label' => 'Replacement',  'types' => ['REPLACEMENT'],   'qty' => 'movement_min'],
            ],
        ],
    ];

    private $accuracyThresholdPercent = 2.0;

    public function __construct()
    {
        $this->title      = 'STO Report';
        $this->moduleCode = 'STO_REPORT';
    }

    // ══════════════════════════════════════════════
    // HELPER GRUP LOKASI
    // ══════════════════════════════════════════════
    private function getLocationGroup($locationCode)
    {
        foreach ($this->locationGroups as $group => $codes) {
            if (in_array($locationCode, $codes, true)) {
                return $group;
            }
        }
        return null;
    }

    private function getGroupConfig($locationCode)
    {
        $group = $this->getLocationGroup($locationCode);
        return $this->movementConfig[$group] ?? ['in' => [], 'out' => []];
    }

    private function getColumnKeys($locationCode)
    {
        $config = $this->getGroupConfig($locationCode);
        return [
            'in'  => array_keys($config['in']),
            'out' => array_keys($config['out']),
        ];
    }

    // ══════════════════════════════════════════════
    // INDEX (tidak berubah)
    // ══════════════════════════════════════════════
    public function index()
    {
        $stoList = DB::table('sto_config as h')
            ->whereIn('h.status', [1, 2, 3])
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('sto_config_mapping as m')
                  ->whereColumn('m.config_id', 'h.config_id')
                  ->where('m.target_type', 'LOCATION')
                  ->whereIn('m.target_ref', $this->supportedLocations);
            })
            ->orderByDesc('h.config_id')
            ->select('h.config_id', 'h.sto_code', 'h.periode', 'h.sto_type')
            ->get()
            ->map(function ($r) {
                $r->enc_id = Crypt::encryptString($r->config_id);
                return $r;
            });

        return view('stoReport.index', [
            'title'              => $this->title,
            'subtitle'           => $this->title,
            'stoList'            => $stoList,
            'supportedLocations' => $this->supportedLocations,
        ]);
    }

    public function getStoLocations(Request $request)
    {
        $configId = Crypt::decryptString($request->config_id);

        $rows = DB::table('sto_config_mapping as m')
            ->join('sto_config as h', 'h.config_id', '=', 'm.config_id')
            ->leftJoin('stock_location_master as l', 'l.location_code', '=', 'm.target_ref')
            ->where('m.config_id', $configId)
            ->where('m.target_type', 'LOCATION')
            ->whereIn('m.target_ref', $this->supportedLocations)
            ->select(
                'm.mapping_id',
                'm.target_ref as location_code',
                'm.sto_date',
                'm.target_plan_loc',
                'h.periode',
                DB::raw('COALESCE(l.location_name, m.target_ref) as location_name')
            )
            ->orderBy('location_name')
            ->get();

        return response()->json($rows);
    }

    public function data(Request $request)
    {
        $configId     = Crypt::decryptString($request->config_id);
        $locationCode = $request->location_code;

        $result = $this->buildReport($configId, $locationCode, $request->date_range);

        if ($result['status'] === 0) {
            return response()->json(
                ['status' => 0, 'message' => $result['message']],
                $result['code'] ?? 422
            );
        }

        return response()->json([
            'status'  => 1,
            'header'  => $result['header'],
            'rows'    => $result['rows']->values(),
            'totals'  => $result['totals'],
            'summary' => $result['summary'],
            'columns' => $result['columns'],
        ]);
    }

    // ══════════════════════════════════════════════
    // AGGREGATE MOVEMENT — BARU: family-aware (whereIn family, bukan single location)
    // ══════════════════════════════════════════════
    private function aggregateMovements(array $family, $dateFrom, $dateTo, $locationCode)
    {
        // group config tetap ditentukan dari lokasi yang DIPILIH user (anchor),
        // karena itu yang menentukan kolom in/out mana yang relevan
        $config = $this->getGroupConfig($locationCode);

        $query = DB::table('warehouse_movement as wm')->select('wm.artikel_code');

        foreach (['in', 'out'] as $direction) {
            foreach (($config[$direction] ?? []) as $colKey => $def) {
                $qtyField      = $def['qty'];
                $types         = array_map('strtoupper', $def['types']);
                $placeholders  = implode(',', array_fill(0, count($types), '?'));

                $query->selectRaw(
                    "SUM(CASE WHEN UPPER(wm.movement_type) IN ($placeholders) AND COALESCE(wm.$qtyField,0) > 0
                         THEN wm.$qtyField ELSE 0 END) as $colKey",
                    $types
                );
            }
        }

        $query->whereIn('wm.location_number', $family) // ← BARU: dulu ->where('wm.location_number', $locationCode)
            ->where('wm.movement_type', 'not ilike', 'CANCEL %')
            ->whereRaw(
                "TO_DATE(wm.movement_date,'DD-MM-YYYY') BETWEEN TO_DATE(?,'DD-MM-YYYY') AND TO_DATE(?,'DD-MM-YYYY')",
                [$dateFrom, $dateTo]
            )
            ->groupBy('wm.artikel_code');

        return $query->get()->keyBy('artikel_code');
    }

    // ══════════════════════════════════════════════
    // AGGREGATE STO RESULTS — BARU: family-aware.
    // Dulu hanya baca sto_dtl dari sto_hdr yang h.target_ref = $locationCode.
    // Sekarang ikut semua sibling mapping dalam config yang sama (child+parent),
    // sama seperti syncArticleStatus()/collectFamilyDtlRowsByLocation() di STO.
    // ══════════════════════════════════════════════
    private function aggregateStoResults($configId, array $family)
    {
        $siblingMappingIds = DB::table('sto_config_mapping')
            ->where('config_id', $configId)
            ->where('target_type', 'LOCATION')
            ->whereIn('target_ref', $family)
            ->pluck('mapping_id');

        if ($siblingMappingIds->isEmpty()) return collect();

        $rows = DB::table('sto_dtl as d')
            ->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id')
            ->whereIn('h.mapping_id', $siblingMappingIds)
            ->whereNotNull('d.article_code')
            ->select('d.article_code as alt_code', 'd.qty_counter1', 'd.qty_counter2', 'd.qty_counter3', 'd.count_status')
            ->get();

        if ($rows->isEmpty()) return collect();

        return $rows->groupBy('alt_code')->map(function ($items) {
            $hasC1 = $items->contains(fn($r) => $r->qty_counter1 !== null);
            $hasC2 = $items->contains(fn($r) => $r->qty_counter2 !== null);
            $hasC3 = $items->contains(fn($r) => $r->qty_counter3 !== null);

            $qty = null;
            if ($hasC1)     $qty = $items->sum('qty_counter1');
            elseif ($hasC2) $qty = $items->sum('qty_counter2');
            elseif ($hasC3) $qty = $items->sum('qty_counter3');

            $priority = ['INCOMPLETE' => 0, 'NOT MATCH' => 1, 'RECOUNT' => 2, 'MATCH' => 3];
            $worst = $items->pluck('count_status')->unique()
                ->sortBy(fn($s) => $priority[$s] ?? 99)->first() ?? 'INCOMPLETE';

            return (object) ['qty_sto' => $qty, 'count_status' => $worst];
        });
    }

    // ══════════════════════════════════════════════
    // OPENING BALANCE — BARU: pakai resolveFamilyBalance() dari trait
    // (family-aware + exclude adjustment periode berjalan, sama seperti getLastQty()
    // di StockCountController; dulu di sini adjustment TIDAK dikecualikan sama sekali).
    // ══════════════════════════════════════════════
    private function getOpeningBalance($realCode, $openingDate, $location, $configId)
    {
        return $this->resolveFamilyBalance($realCode, $location, $openingDate, $configId);
    }

    private function resolveReportDateRange($periode, $stoDate)
    {
        [$year, $month] = $this->parsePeriode($periode);
        $dateFrom = sprintf('01-%02d-%04d', $month, $year);

        if ($stoDate && preg_match('/^\d{2}-\d{2}-\d{4}$/', $stoDate)) {
            $dateTo = $stoDate;
        } else {
            $lastDay = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
            $dateTo  = sprintf('%02d-%02d-%04d', $lastDay, $month, $year);
        }

        $openingDate = date('Y-m-d', strtotime(sprintf('%04d-%02d-01', $year, $month) . ' -1 day'));
        return [$dateFrom, $dateTo, $openingDate];
    }

    private function parsePeriode($periode)
    {
        if (preg_match('/^(\d{4})-(\d{2})/', $periode, $m)) return [(int) $m[1], (int) $m[2]];
        if (preg_match('/^(\d{2})-(\d{4})/', $periode, $m)) return [(int) $m[2], (int) $m[1]];
        return [(int) date('Y'), (int) date('n')];
    }

    private function emptyTotals($locationCode = null)
    {
        $totals = ['opening' => 0];

        if ($locationCode) {
            $cols = $this->getColumnKeys($locationCode);
            foreach (array_merge($cols['in'], $cols['out']) as $key) {
                $totals[$key] = 0;
            }
        }

        $totals['closing']  = 0;
        $totals['qty_sto']  = null;
        $totals['variance'] = null;

        return $totals;
    }

    private function emptySummary($targetPlan = 98)
    {
        return [
            'total_artikel'  => 0,
            'total_accurate' => 0,
            'total_not'      => 0,
            'accuracy_pct'   => 0,
            'target_plan'    => $targetPlan,
            'is_meet_target' => false,
            'threshold_pct'  => $this->accuracyThresholdPercent,
        ];
    }

    private function buildColumnDefs($locationCode)
    {
        $config = $this->getGroupConfig($locationCode);
        $defs   = ['in' => [], 'out' => []];

        foreach (['in', 'out'] as $direction) {
            foreach (($config[$direction] ?? []) as $key => $def) {
                $defs[$direction][] = ['key' => $key, 'label' => $def['label']];
            }
        }

        return $defs;
    }

    // ══════════════════════════════════════════════
    // CORE — bangun report (dipakai data() & export())
    // ══════════════════════════════════════════════
    private function buildReport($configId, $locationCode, $dateRange = null)
    {
        if (!in_array($locationCode, $this->supportedLocations)) {
            return ['status' => 0, 'message' => 'Lokasi ini belum didukung format reportnya.', 'code' => 422];
        }

        $config = DB::table('sto_config')->where('config_id', $configId)->first();
        if (!$config) {
            return ['status' => 0, 'message' => 'STO tidak ditemukan.', 'code' => 404];
        }

        $mapping = DB::table('sto_config_mapping')
            ->where('config_id', $configId)
            ->where('target_type', 'LOCATION')
            ->where('target_ref', $locationCode)
            ->first();

        if (!$mapping) {
            return ['status' => 0, 'message' => 'Lokasi tidak terdaftar pada STO ini.', 'code' => 404];
        }

        $locationName = DB::table('stock_location_master')
            ->where('location_code', $locationCode)
            ->value('location_name') ?? $locationCode;

        // ── BARU: resolve family & anchor sekali di awal, dipakai di semua langkah ──
        $family = $this->resolveLocationFamily($locationCode);
        $anchor = $this->resolveLocationAnchor($locationCode);

        [$dateFrom, $dateTo, $openingDate] = $this->resolveReportDateRange($config->periode, $mapping->sto_date ?? null);

        if ($dateRange) {
            $parts = explode(' to ', $dateRange);
            $from  = trim($parts[0] ?? '');
            $to    = trim($parts[1] ?? $from);
            if ($from && $to) {
                $dateFrom = $from;
                $dateTo   = $to;
                $dt = \DateTime::createFromFormat('d-m-Y', $from);
                if ($dt) {
                    $openingDate = date('Y-m-d', strtotime($dt->format('Y-m-d') . ' -1 day'));
                }
            }
        }

        $cols       = $this->getColumnKeys($locationCode);
        $columnDefs = $this->buildColumnDefs($locationCode);

        $movements  = $this->aggregateMovements($family, $dateFrom, $dateTo, $locationCode);
        $stoResults = $this->aggregateStoResults($configId, $family);

        // ── BARU: article_type filter, konsisten dengan locationArticleTypeMap di STO ──
        $allowedTypes = $this->locationArticleTypeMap[$anchor] ?? null;

        $stockQuery = DB::table('warehouse_stock as ws')
            ->join('article as a', 'a.article_alternative_code', '=', 'ws.article_code')
            ->whereIn('ws.location_number', $family) // ← BARU: family, dulu single location
            ->where('ws.article_qty', '<>', 0);

        if ($allowedTypes) {
            $stockQuery->whereIn('a.article_type', $allowedTypes); // ← BARU
        }

        $stockCodes = $stockQuery->pluck('a.article_code');

        $stoAltCodes  = $stoResults->keys();
        $stoRealCodesQuery = DB::table('article')->whereIn('article_alternative_code', $stoAltCodes);
        if ($allowedTypes) {
            $stoRealCodesQuery->whereIn('article_type', $allowedTypes); // ← BARU
        }
        $stoRealCodes = $stoRealCodesQuery->pluck('article_code');

        $movementRealCodes = $movements->keys();
        if ($allowedTypes) {
            // movement query tidak join article, filter type-nya belakangan di sini
            $movementRealCodes = DB::table('article')
                ->whereIn('article_code', $movementRealCodes)
                ->whereIn('article_type', $allowedTypes)
                ->pluck('article_code');
        }

        $realCodes = $movementRealCodes
            ->merge($stockCodes)
            ->merge($stoRealCodes)
            ->filter()
            ->map(fn($c) => (string) $c)
            ->unique()->values();

        $header = [
            'sto_code'        => $config->sto_code,
            'sto_type'        => $config->sto_type,
            'periode'         => $config->periode,
            'location_code'   => $locationCode,
            'location_name'   => $locationName,
            'date_from'       => $dateFrom,
            'date_to'         => $dateTo,
            'sto_date'        => $mapping->sto_date ?? null,
            'target_plan_loc' => $mapping->target_plan_loc ?? 98,
        ];

        if ($realCodes->isEmpty()) {
            return [
                'status'  => 1,
                'header'  => $header,
                'rows'    => collect(),
                'totals'  => $this->emptyTotals($locationCode),
                'summary' => $this->emptySummary($mapping->target_plan_loc ?? 98),
                'columns' => $columnDefs,
            ];
        }

        $articles = DB::table('article as a')
            ->leftJoin('third_party as tp', 'tp.kode', '=', 'a.third_party')
            ->whereIn('a.article_code', $realCodes)
            ->select(
                'a.article_code',
                'a.article_alternative_code',
                'a.article_desc',
                'a.uom',
                DB::raw('COALESCE(tp.nama, a.third_party) as supp_name')
            )
            ->orderBy('a.article_code')
            ->get()
            ->keyBy('article_code');

        $rows         = collect();
        $totalPoin    = 0;
        $totalArtikel = 0;

        foreach ($realCodes as $rc) {
            $meta    = $articles->get($rc);
            $altCode = $meta->article_alternative_code ?? null;
            $mv      = $movements->get($rc);
            $stoRow  = $altCode ? $stoResults->get($altCode) : null;

            // ── BARU: family-aware + adjustment-excluded, via trait ──
            $opening = round((float) $this->getOpeningBalance($rc, $openingDate, $anchor, $configId), 2);

            $inTotal  = 0;
            $outTotal = 0;
            $moveVals = [];

            foreach ($cols['in'] as $key) {
                $val = $mv ? (float) ($mv->{$key} ?? 0) : 0;
                $moveVals[$key] = round($val, 2);
                $inTotal += $val;
            }
            foreach ($cols['out'] as $key) {
                $val = $mv ? (float) ($mv->{$key} ?? 0) : 0;
                $moveVals[$key] = round($val, 2);
                $outTotal += $val;
            }

            $closing = round($opening + $inTotal - $outTotal, 2);

            if ($opening == 0 && $inTotal == 0 && $outTotal == 0 && $closing == 0 && !$stoRow) {
                continue;
            }

            $stoQty    = $stoRow ? round((float) $stoRow->qty_sto, 2) : null;
            $stoStatus = $stoRow ? ($stoRow->count_status ?? 'INCOMPLETE') : 'INCOMPLETE';
            $variance  = $stoQty !== null ? round($stoQty - $closing, 2) : null;

            $accurate = false;
            if ($stoStatus === 'MATCH') {
                $accurate = true;
            } elseif ($stoStatus === 'RECOUNT' && $variance !== null) {
                if ($closing == 0) {
                    $accurate = ($stoQty == 0);
                } else {
                    $accurate = (abs($variance) / abs($closing) * 100) <= $this->accuracyThresholdPercent;
                }
            }

            $totalArtikel++;
            if ($accurate) $totalPoin++;

            $rowData = array_merge([
                'article_code' => $rc,
                'alt_code'     => $altCode ?? $rc,
                'article_desc' => $meta->article_desc ?? $rc,
                'supp'         => $meta->supp_name ?? '-',
                'uom'          => $meta->uom ?? '-',
                'opening'      => $opening,
            ], $moveVals, [
                'closing'      => $closing,
                'qty_sto'      => $stoQty,
                'variance'     => $variance,
                'sto_status'   => $stoStatus,
                'accurate'     => $accurate,
            ]);

            $rows->push((object) $rowData);
        }

        $rows = $rows->sortBy('article_desc')->values();

        $totals  = $this->emptyTotals($locationCode);
        $sumKeys = array_merge(['opening'], $cols['in'], $cols['out'], ['closing']);

        $no = 1;
        $rows = $rows->map(function ($r) use (&$no, &$totals, $sumKeys) {
            $r->no = $no++;
            foreach ($sumKeys as $k) {
                $totals[$k] = round(($totals[$k] ?? 0) + ($r->{$k} ?? 0), 2);
            }
            if ($r->qty_sto !== null) {
                $totals['qty_sto'] = round(($totals['qty_sto'] ?? 0) + $r->qty_sto, 2);
            }
            if ($r->variance !== null) {
                $totals['variance'] = round(($totals['variance'] ?? 0) + $r->variance, 2);
            }
            return $r;
        });

        $targetPlan  = (float) ($mapping->target_plan_loc ?? 98);
        $actAccuracy = $totalArtikel > 0 ? round($totalPoin / $totalArtikel * 100, 2) : 0;

        return [
            'status'  => 1,
            'header'  => $header,
            'rows'    => $rows,
            'totals'  => $totals,
            'summary' => [
                'total_artikel'  => $totalArtikel,
                'total_accurate' => $totalPoin,
                'total_not'      => $totalArtikel - $totalPoin,
                'accuracy_pct'   => $actAccuracy,
                'target_plan'    => $targetPlan,
                'is_meet_target' => $actAccuracy >= $targetPlan,
                'threshold_pct'  => $this->accuracyThresholdPercent,
            ],
            'columns' => $columnDefs,
        ];
    }

    public function export(Request $request)
    {
        $configId     = Crypt::decryptString($request->config_id);
        $locationCode = $request->location_code;

        $result = $this->buildReport($configId, $locationCode, $request->date_range);

        if ($result['status'] === 0) {
            return back()->with('error', $result['message']);
        }

        $h = $result['header'];
        $fileName = 'STO_Report_' . $h['sto_code'] . '_' . $h['location_code'] . '.xlsx';
        $fileName = preg_replace('/[\/\\\\?%*:|"<>]/', '-', $fileName);

        return \Excel::download(
            new \App\Exports\StoReportExport(
                $result['header'],
                $result['rows'],
                $result['totals'],
                $result['summary'],
                $result['columns']
            ),
            $fileName
        );
    }
}

/**
 * CATATAN SELANJUTNYA (opsional, ga wajib sekarang):
 * $locationArticleTypeMap masih double-maintained (di sini & di StockCountController).
 * Kalau mau bener2 satu sumber kebenaran, pindahin array itu ke trait
 * HasStoLocationFamily juga (atau config/db table), lalu kedua controller
 * baca dari situ. Sekarang saya biarkan terpisah supaya diff-nya kebaca jelas
 * dan gampang di-review dulu.
 */