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
    // PENENTU KATEGORI = movement_type (bukan movement_desc)
    // movement_desc = isi bebas (nama transaksi), tidak bisa diandalkan untuk filter
    //
    // IN ke gudang chemical:
    //   movement_type = 'RECEIVING'        → Receiving dari supplier
    //   movement_type = 'TRANSFER'         → Return chemical dari booth/lokasi lain (movement_plus)
    //   movement_type = 'SUPPLIER REPLACE' → Penggantian barang dari supplier
    //
    // OUT dari gudang chemical:
    //   movement_type = 'SUPPLY'           → Supply ke booth/WOS (movement_min)
    //   movement_type = 'SUPPLIER RETURN'  → Return ke supplier
    //   movement_type = 'DN'               → DN Umum (sesuaikan jika berbeda)
    //
    // CANCEL prefix (mis. 'CANCEL SUPPLY', 'CANCEL TRANSFER') → diabaikan
    // ══════════════════════════════════════════════
    private $typeReceiving       = 'RECEIVING';         // IN
    private $typeReturnTransfer  = 'TRANSFER';          // IN  (movement_plus > 0)
    private $typeTransferOut = 'TRANSFER'; // OUT (movement_min > 0) — beda kondisi dari typeReturnTransfer
    private $typeSupplierReplace = 'SUPPLIER REPLACE';  // IN
    private $typeSupplyOut       = 'SUPPLY';            // OUT (movement_min > 0)
    private $typeSupplierReturn  = 'SUPPLIER RETURN';   // OUT
    private $typeDnUmum          = 'DN UMUM';                // OUT — SESUAIKAN jika perlu (DN UMUM, GI, dll)

    // threshold akurasi: selisih <= N% dari closing → dapat poin
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
    // DATA
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

        $movements  = $this->aggregateMovements($locationCode, $dateFrom, $dateTo);
        $stoResults = $this->aggregateStoResults($configId, $locationCode);

        $stockCodes = DB::table('warehouse_stock')
            ->where('location_number', $locationCode)
            ->where('article_qty', '<>', 0)
            ->pluck('article_code');

        $stoAltCodes  = $stoResults->keys();
        $stoRealCodes = DB::table('article')
            ->whereIn('article_alternative_code', $stoAltCodes)
            ->pluck('article_code');

        $realCodes = $movements->keys()
            ->merge($stockCodes)
            ->merge($stoRealCodes)
            ->filter()->unique()->values();

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

        // metadata artikel — 1 real_code = 1 baris, deduplikasi by article_code
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

            $opening = round((float) $this->getOpeningBalance($rc, $openingDate, $locationCode), 2);

            $inRcv  = $mv ? (float) $mv->in_receiving        : 0;
            $inRet  = $mv ? (float) $mv->in_return_transfer  : 0;
            $inRep  = $mv ? (float) $mv->in_replace_supplier : 0;
            $outSup = $mv ? (float) $mv->out_supply_transfer + (float) $mv->out_transfer : 0;
            $outRet = $mv ? (float) $mv->out_return_supplier : 0;
            $outDn  = $mv ? (float) $mv->out_dn_umum         : 0;

            $totalIn  = $inRcv + $inRet + $inRep;
            $totalOut = $outSup + $outRet + $outDn;
            $closing  = round($opening + $totalIn - $totalOut, 2);

            if ($opening == 0 && $totalIn == 0 && $totalOut == 0 && $closing == 0 && !$stoRow) {
                continue;
            }

            $stoQty    = $stoRow ? round((float) $stoRow->qty_sto, 2) : null;
            $stoStatus = $stoRow ? ($stoRow->count_status ?? 'INCOMPLETE') : 'INCOMPLETE';
            $variance  = $stoQty !== null ? round($stoQty - $closing, 2) : null;

            // akurasi
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

        return response()->json([
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
        ]);
    }

    // ══════════════════════════════════════════════
    // AGGREGATE MOVEMENT
    // Filter utama: movement_type (bukan movement_desc)
    // CANCEL prefix otomatis ter-exclude karena movement_type ILIKE 'CANCEL %'
    // akan tidak cocok dengan tipe spesifik di bawah.
    // ══════════════════════════════════════════════
    private function aggregateMovements($locationCode, $dateFrom, $dateTo)
    {
        return DB::table('warehouse_movement as wm')
            ->select('wm.artikel_code')

            // ── IN ──
            // Receiving: movement_type = 'RECEIVING' (exact, case-insensitive)
            ->selectRaw(
                "SUM(CASE WHEN UPPER(wm.movement_type) = UPPER(?) THEN COALESCE(wm.movement_plus,0) ELSE 0 END) as in_receiving",
                [$this->typeReceiving]
            )
            // Return Transfer: movement_type = 'TRANSFER', ambil movement_plus (masuk ke gudang)
            ->selectRaw(
                "SUM(CASE WHEN UPPER(wm.movement_type) = UPPER(?) AND COALESCE(wm.movement_plus,0) > 0
                     THEN wm.movement_plus ELSE 0 END) as in_return_transfer",
                [$this->typeReturnTransfer]
            )
            // Supplier Replace: movement_type = 'SUPPLIER REPLACE'
            ->selectRaw(
                "SUM(CASE WHEN UPPER(wm.movement_type) = UPPER(?) THEN COALESCE(wm.movement_plus,0) ELSE 0 END) as in_replace_supplier",
                [$this->typeSupplierReplace]
            )

            // ── OUT ──
            // Supply Transfer: movement_type = 'SUPPLY', ambil movement_min (keluar dari gudang)
            ->selectRaw(
                "SUM(CASE WHEN UPPER(wm.movement_type) = UPPER(?) AND COALESCE(wm.movement_min,0) > 0
                     THEN wm.movement_min ELSE 0 END) as out_supply_transfer",
                [$this->typeSupplyOut]
            )
            // Return Supplier: movement_type = 'SUPPLIER RETURN'
            ->selectRaw(
                "SUM(CASE WHEN UPPER(wm.movement_type) = UPPER(?) THEN COALESCE(wm.movement_min,0) ELSE 0 END) as out_return_supplier",
                [$this->typeSupplierReturn]
            )
            // DN Umum: movement_type = 'DN' (atau 'DN UMUM' — sesuaikan $typeDnUmum)
            ->selectRaw(
                "SUM(CASE WHEN UPPER(wm.movement_type) = UPPER(?) THEN COALESCE(wm.movement_min,0) ELSE 0 END) as out_dn_umum",
                [$this->typeDnUmum]
            )

            ->selectRaw(
    "SUM(CASE WHEN UPPER(wm.movement_type) = UPPER(?) AND COALESCE(wm.movement_min,0) > 0
         THEN wm.movement_min ELSE 0 END) as out_transfer",
    [$this->typeTransferOut]
)

            ->where('wm.location_number', $locationCode)
            // exclude semua tipe CANCEL (CANCEL SUPPLY, CANCEL TRANSFER, dll)
            ->where('wm.movement_type', 'not ilike', 'CANCEL %')
            ->whereRaw(
                "TO_DATE(wm.movement_date,'DD-MM-YYYY') BETWEEN TO_DATE(?,'DD-MM-YYYY') AND TO_DATE(?,'DD-MM-YYYY')",
                [$dateFrom, $dateTo]
            )
            ->groupBy('wm.artikel_code')
            ->get()
            ->keyBy('artikel_code');
    }

    // ══════════════════════════════════════════════
    // AGGREGATE STO RESULTS — keyed by alt_code
    // ══════════════════════════════════════════════
    private function aggregateStoResults($configId, $locationCode)
    {
        $rows = DB::table('sto_dtl as d')
            ->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id')
            ->where('h.config_id', $configId)
            ->where('h.target_type', 'LOCATION')
            ->where('h.target_ref', $locationCode)
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