<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DB;

class StoReportController extends Controller
{
    private $title;
    private $moduleCode;

    private $supportedLocations = ['005', '006', '009'];

    // ══════════════════════════════════════════════
    // movement_desc patterns — disesuaikan dari hasil query DB
    // IN  dari gudang chemical:
    //   - RECEIVING   → barang masuk dari supplier
    //   - TRANSFER    → return chemical dari lokasi lain (movement_plus > 0)
    //   - SUPPLIER REPLACE → ganti barang dari supplier
    // OUT dari gudang chemical:
    //   - SUPPLY      → supply ke booth/WOS (movement_min > 0)
    //   - SUPPLIER RETURN → return ke supplier
    //   - DN UMUM     → delivery note umum
    // ══════════════════════════════════════════════
    private $descReceiving       = 'RECEIVING';        // IN  — movement_desc ILIKE 'RECEIVING%'
    private $descReturnTransfer  = 'TRANSFER';         // IN  — movement_desc ILIKE '%TRANSFER%' AND movement_plus > 0
    private $descSupplierReplace = 'SUPPLIER REPLACE'; // IN  — movement_desc ILIKE 'SUPPLIER REPLACE%'
    private $descSupplyOut       = 'SUPPLY';           // OUT — movement_desc ILIKE 'SUPPLY%' AND movement_min > 0
    private $descSupplierReturn  = 'SUPPLIER RETURN';  // OUT — movement_desc ILIKE 'SUPPLIER RETURN%'
    private $descDnUmum          = 'DN UMUM';          // OUT — movement_desc ILIKE 'DN UMUM%'

    // threshold akurasi: selisih <= 2% dari closing → dapat poin
    private $accuracyThresholdPercent = 2.0;

    public function __construct()
    {
        $this->title      = 'STO Report';
        $this->moduleCode = 'STO_REPORT';
    }

    // ══════════════════════════════════════════════
    // INDEX
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

    // ══════════════════════════════════════════════
    // GET LOCATIONS — AJAX
    // ══════════════════════════════════════════════
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

    // ══════════════════════════════════════════════
    // DATA — hitung & kembalikan baris report (JSON)
    // ══════════════════════════════════════════════
    public function data(Request $request)
    {
        $configId     = Crypt::decryptString($request->config_id);
        $locationCode = $request->location_code;

        if (!in_array($locationCode, $this->supportedLocations)) {
            return response()->json(['status' => 0, 'message' => 'Lokasi ini belum didukung format reportnya.'], 422);
        }

        $config = DB::table('sto_config')->where('config_id', $configId)->first();
        if (!$config) {
            return response()->json(['status' => 0, 'message' => 'STO tidak ditemukan.'], 404);
        }

        $mapping = DB::table('sto_config_mapping')
            ->where('config_id', $configId)
            ->where('target_type', 'LOCATION')
            ->where('target_ref', $locationCode)
            ->first();

        if (!$mapping) {
            return response()->json(['status' => 0, 'message' => 'Lokasi tidak terdaftar pada STO ini.'], 404);
        }

        $locationName = DB::table('stock_location_master')
            ->where('location_code', $locationCode)
            ->value('location_name') ?? $locationCode;

        // ── rentang tanggal ──
        [$dateFrom, $dateTo, $openingDate] = $this->resolveReportDateRange($config->periode, $mapping->sto_date ?? null);

        if ($request->filled('date_range')) {
            $parts = explode(' to ', $request->date_range);
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

        // ── agregasi movement ──
        $movements = $this->aggregateMovements($locationCode, $dateFrom, $dateTo);

        // ── hasil STO counting ──
        $stoResults = $this->aggregateStoResults($configId, $locationCode);

        // ── kandidat artikel: union dari movement + stok aktif + STO ──
        // Semua di-resolve ke article_code (real code)
        $stockCodes = DB::table('warehouse_stock')
            ->where('location_number', $locationCode)
            ->where('article_qty', '<>', 0)
            ->pluck('article_code');

        // stoResults keyed by alt_code → convert ke real_code dulu
        $stoAltCodes = $stoResults->keys();
        $stoRealCodes = DB::table('article')
            ->whereIn('article_alternative_code', $stoAltCodes)
            ->pluck('article_code');

        $realCodes = $movements->keys()
            ->merge($stockCodes)
            ->merge($stoRealCodes)
            ->filter()
            ->unique()
            ->values();

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
            return response()->json([
                'status'  => 1,
                'header'  => $header,
                'rows'    => [],
                'totals'  => $this->emptyTotals(),
                'summary' => $this->emptySummary($mapping->target_plan_loc ?? 98),
            ]);
        }

        // ── metadata artikel — JOIN sekali, deduplikasi by article_code ──
        // Kalau ada duplikasi alt_code di tabel article (lebih dari 1 real_code),
        // kita ambil yang PERTAMA saja (ORDER BY article_code) supaya tidak double baris.
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
            // deduplikasi: 1 real_code = 1 baris (article_code harusnya PK, aman)
            ->keyBy('article_code');

        // alt_code → real_code map (untuk lookup stoResults)
        $altToReal = $articles->mapWithKeys(fn($a) => [
            $a->article_alternative_code => $a->article_code
        ]);

        // ── susun baris ──
        $rows         = collect();
        $totalPoin    = 0;
        $totalArtikel = 0;

        // Pakai $realCodes sebagai iterator utama — sudah unik per real_code
        foreach ($realCodes as $rc) {
            $meta    = $articles->get($rc);
            $altCode = $meta->article_alternative_code ?? null;
            $mv      = $movements->get($rc);
            $stoRow  = $altCode ? $stoResults->get($altCode) : null;

            $opening = round((float) $this->getOpeningBalance($rc, $openingDate, $locationCode), 2);

            $inRcv  = $mv ? (float) $mv->in_receiving        : 0;
            $inRet  = $mv ? (float) $mv->in_return_transfer  : 0;
            $inRep  = $mv ? (float) $mv->in_replace_supplier : 0;
            $outSup = $mv ? (float) $mv->out_supply_transfer : 0;
            $outRet = $mv ? (float) $mv->out_return_supplier : 0;
            $outDn  = $mv ? (float) $mv->out_dn_umum         : 0;

            $totalIn  = $inRcv + $inRet + $inRep;
            $totalOut = $outSup + $outRet + $outDn;
            $closing  = round($opening + $totalIn - $totalOut, 2);

            // skip baris kosong total (tidak ada stok, tidak ada mutasi, tidak ada STO)
            if ($opening == 0 && $totalIn == 0 && $totalOut == 0 && $closing == 0 && !$stoRow) {
                continue;
            }

            // ── STO, Variance, Status, Akurasi ──
            if ($stoRow) {
                $stoQty    = round((float) $stoRow->qty_sto, 2);
                $stoStatus = $stoRow->count_status ?? 'INCOMPLETE';
            } else {
                $stoQty    = null;
                $stoStatus = 'INCOMPLETE';
            }

            $variance = $stoQty !== null ? round($stoQty - $closing, 2) : null;

            $accurate = false;
            if ($stoStatus === 'MATCH') {
                $accurate = true;
            } elseif ($stoStatus === 'RECOUNT' && $variance !== null) {
                if ($closing == 0) {
                    $accurate = ($stoQty == 0);
                } else {
                    $pctDiff  = abs($variance) / abs($closing) * 100;
                    $accurate = $pctDiff <= $this->accuracyThresholdPercent;
                }
            }
            // INCOMPLETE & NOT MATCH → false

            $totalArtikel++;
            if ($accurate) $totalPoin++;

            $rows->push((object) [
                'article_code'        => $rc,
                'alt_code'            => $altCode ?? $rc,
                'article_desc'        => $meta->article_desc ?? $rc,
                'supp'                => $meta->supp_name ?? '-',
                'uom'                 => $meta->uom ?? '-',
                'opening'             => $opening,
                'in_receiving'        => round($inRcv, 2),
                'in_return_transfer'  => round($inRet, 2),
                'in_replace_supplier' => round($inRep, 2),
                'out_supply_transfer' => round($outSup, 2),
                'out_return_supplier' => round($outRet, 2),
                'out_dn_umum'         => round($outDn, 2),
                'closing'             => $closing,
                'qty_sto'             => $stoQty,
                'variance'            => $variance,
                'sto_status'          => $stoStatus,
                'accurate'            => $accurate,
            ]);
        }

        $rows = $rows->sortBy('article_desc')->values();

        $totals = $this->emptyTotals();
        $no = 1;
        $rows = $rows->map(function ($r) use (&$no, &$totals) {
            $r->no = $no++;
            foreach (['opening','in_receiving','in_return_transfer','in_replace_supplier',
                      'out_supply_transfer','out_return_supplier','out_dn_umum','closing'] as $k) {
                $totals[$k] = round($totals[$k] + $r->{$k}, 2);
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

        $summary = [
            'total_artikel'  => $totalArtikel,
            'total_accurate' => $totalPoin,
            'total_not'      => $totalArtikel - $totalPoin,
            'accuracy_pct'   => $actAccuracy,
            'target_plan'    => $targetPlan,
            'is_meet_target' => $actAccuracy >= $targetPlan,
            'threshold_pct'  => $this->accuracyThresholdPercent,
        ];

        return response()->json([
            'status'  => 1,
            'header'  => $header,
            'rows'    => $rows,
            'totals'  => $totals,
            'summary' => $summary,
        ]);
    }

    // ══════════════════════════════════════════════
    // AGGREGATE MOVEMENT
    //
    // IN:
    //   Receiving       → movement_desc ILIKE 'RECEIVING%'      , movement_plus
    //   Return Transfer → movement_desc ILIKE '%TRANSFER%'       , movement_plus > 0
    //                     (exclude yang ILIKE 'SUPPLIER%' supaya SUPPLIER REPLACE tidak masuk)
    //   Supplier Replace→ movement_desc ILIKE 'SUPPLIER REPLACE%', movement_plus
    //
    // OUT:
    //   Supply Transfer → movement_desc ILIKE 'SUPPLY%'          , movement_min > 0
    //                     (exclude SUPPLIER RETURN supaya tidak overlap)
    //   Return Supplier → movement_desc ILIKE 'SUPPLIER RETURN%' , movement_min
    //   DN Umum         → movement_desc ILIKE 'DN UMUM%'         , movement_min
    //
    // Semua exclude movement_type ILIKE 'CANCEL %'
    // ══════════════════════════════════════════════
    private function aggregateMovements($locationCode, $dateFrom, $dateTo)
    {
        $pReceiving = $this->descReceiving . '%';           // 'RECEIVING%'
        $pTransfer  = '%' . $this->descReturnTransfer . '%'; // '%TRANSFER%'
        $pReplace   = $this->descSupplierReplace . '%';     // 'SUPPLIER REPLACE%'
        $pSupply    = $this->descSupplyOut . '%';           // 'SUPPLY%'
        $pSuppRet   = $this->descSupplierReturn . '%';      // 'SUPPLIER RETURN%'
        $pDnUmum    = $this->descDnUmum . '%';             // 'DN UMUM%'

        return DB::table('warehouse_movement as wm')
            ->select('wm.artikel_code')

            // ── IN ──
            // Receiving: desc ILIKE 'RECEIVING%'
            ->selectRaw(
                "SUM(CASE WHEN wm.movement_desc ILIKE ? THEN COALESCE(wm.movement_plus,0) ELSE 0 END) as in_receiving",
                [$pReceiving]
            )
            // Return Transfer: desc ILIKE '%TRANSFER%' AND bukan SUPPLIER REPLACE/RETURN AND movement_plus > 0
            ->selectRaw(
                "SUM(CASE WHEN wm.movement_desc ILIKE ?
                          AND wm.movement_desc NOT ILIKE ?
                          AND COALESCE(wm.movement_plus,0) > 0
                     THEN wm.movement_plus ELSE 0 END) as in_return_transfer",
                [$pTransfer, 'SUPPLIER%']
            )
            // Supplier Replace: desc ILIKE 'SUPPLIER REPLACE%'
            ->selectRaw(
                "SUM(CASE WHEN wm.movement_desc ILIKE ? THEN COALESCE(wm.movement_plus,0) ELSE 0 END) as in_replace_supplier",
                [$pReplace]
            )

            // ── OUT ──
            // Supply Transfer: desc ILIKE 'SUPPLY%' AND bukan 'SUPPLIER%' AND movement_min > 0
            ->selectRaw(
                "SUM(CASE WHEN wm.movement_desc ILIKE ?
                          AND wm.movement_desc NOT ILIKE ?
                          AND COALESCE(wm.movement_min,0) > 0
                     THEN wm.movement_min ELSE 0 END) as out_supply_transfer",
                [$pSupply, 'SUPPLIER%']
            )
            // Return Supplier: desc ILIKE 'SUPPLIER RETURN%'
            ->selectRaw(
                "SUM(CASE WHEN wm.movement_desc ILIKE ? THEN COALESCE(wm.movement_min,0) ELSE 0 END) as out_return_supplier",
                [$pSuppRet]
            )
            // DN Umum: desc ILIKE 'DN UMUM%'
            ->selectRaw(
                "SUM(CASE WHEN wm.movement_desc ILIKE ? THEN COALESCE(wm.movement_min,0) ELSE 0 END) as out_dn_umum",
                [$pDnUmum]
            )

            ->where('wm.location_number', $locationCode)
            ->where('wm.movement_type', 'not ilike', 'CANCEL %')
            ->whereRaw(
                "TO_DATE(wm.movement_date,'DD-MM-YYYY') BETWEEN TO_DATE(?,'DD-MM-YYYY') AND TO_DATE(?,'DD-MM-YYYY')",
                [$dateFrom, $dateTo]
            )
            ->groupBy('wm.artikel_code')
            ->get()
            ->keyBy('artikel_code');  // keyed by real article_code
    }

    // ══════════════════════════════════════════════
    // AGGREGATE STO RESULTS
    // Keyed by article_alternative_code
    // ══════════════════════════════════════════════
    private function aggregateStoResults($configId, $locationCode)
    {
        $rows = DB::table('sto_dtl as d')
            ->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id')
            ->where('h.config_id', $configId)
            ->where('h.target_type', 'LOCATION')
            ->where('h.target_ref', $locationCode)
            ->whereNotNull('d.article_code')
            ->select(
                'd.article_code as alt_code',  // sto_dtl.article_code = alternative code
                'd.qty_counter1',
                'd.qty_counter2',
                'd.qty_counter3',
                'd.count_status'
            )
            ->get();

        if ($rows->isEmpty()) return collect();

        return $rows->groupBy('alt_code')->map(function ($items) {
            $hasC1 = $items->contains(fn($r) => $r->qty_counter1 !== null);
            $hasC2 = $items->contains(fn($r) => $r->qty_counter2 !== null);
            $hasC3 = $items->contains(fn($r) => $r->qty_counter3 !== null);

            $qty = null;
            if ($hasC1)      $qty = $items->sum('qty_counter1');
            elseif ($hasC2)  $qty = $items->sum('qty_counter2');
            elseif ($hasC3)  $qty = $items->sum('qty_counter3');

            // status terburuk (INCOMPLETE > NOT MATCH > RECOUNT > MATCH)
            $priority = ['INCOMPLETE' => 0, 'NOT MATCH' => 1, 'RECOUNT' => 2, 'MATCH' => 3];
            $worst = $items->pluck('count_status')
                ->unique()
                ->sortBy(fn($s) => $priority[$s] ?? 99)
                ->first() ?? 'INCOMPLETE';

            return (object) [
                'qty_sto'      => $qty,
                'count_status' => $worst,
            ];
        });
    }

    // ══════════════════════════════════════════════
    // OPENING BALANCE
    // ══════════════════════════════════════════════
    private function getOpeningBalance($realCode, $openingDate, $location)
    {
        $row = DB::selectOne(
            "SELECT get_last_qty_new(?, ?, 'HO', ?) AS q",
            [$realCode, $openingDate, $location]
        );
        return $row ? (float) $row->q : 0;
    }

    // ══════════════════════════════════════════════
    // RESOLVE DATE RANGE
    // ══════════════════════════════════════════════
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

    private function emptyTotals()
    {
        return [
            'opening'             => 0,
            'in_receiving'        => 0,
            'in_return_transfer'  => 0,
            'in_replace_supplier' => 0,
            'out_supply_transfer' => 0,
            'out_return_supplier' => 0,
            'out_dn_umum'         => 0,
            'closing'             => 0,
            'qty_sto'             => null,
            'variance'            => null,
        ];
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
}