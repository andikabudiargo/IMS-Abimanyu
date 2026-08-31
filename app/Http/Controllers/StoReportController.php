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

    // ══════════════════════════════════════════════
    // LOKASI YANG PAKAI FORMAT TABEL INI (chemical)
    // 005 = Chemical, 006 = Consumable, 009 = RM
    // lokasi lain formatnya beda → ditangani modul/method terpisah nanti
    // ══════════════════════════════════════════════
    private $supportedLocations = ['005', '006', '009'];

    // ══════════════════════════════════════════════
    // NILAI movement_desc — SESUAIKAN dengan yg tersimpan di warehouse_movement
    // (dipakai pakai ILIKE, jadi case-insensitive)
    // ══════════════════════════════════════════════
    private $descReceiving       = 'RECEIVING';        // IN  (movement_plus)
    private $descTransfer        = 'TRF';              // arah ditentukan plus/min
    private $descSupplierReplace = 'SUPPLIER REPLACE'; // IN  (movement_plus)
    private $descSupplierReturn  = 'SUPPLIER RETURN';  // OUT (movement_min)
    private $descDnUmum          = 'DN UMUM';          // OUT (movement_min)

    // ══════════════════════════════════════════════
    // THRESHOLD AKURASI — 98% artinya selisih <= 2% dari closing dianggap AKURAT
    // ══════════════════════════════════════════════
    private $accuracyThresholdPercent = 2.0; // selisih max 2% → dapat poin

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

        // ── hasil STO counting per artikel (alt_code → qty hasil count) ──
        // Ambil qty_counter tergantung mapping is_blind:
        //   blind   → rata-rata counter yang konsisten (atau counter1 jika semua sama)
        //   non-blind → counter1 saja
        // Untuk simpelnya di report: ambil avg(coalesce(c1,c2,c3)) yg sudah tersimpan
        // di sto_dtl. count_status juga kita bawa.
        $stoResults = $this->aggregateStoResults($configId, $locationCode, $mapping);

        // ── kandidat artikel ──
        $stockCodes = DB::table('warehouse_stock')
            ->where('location_number', $locationCode)
            ->where('article_qty', '<>', 0)
            ->pluck('article_code');

        $realCodes = $movements->keys()
            ->merge($stockCodes)
            ->merge($stoResults->keys())   // artikel yang ada di STO tapi mungkin tidak ada movement
            ->filter()->unique()->values();

        $header = [
            'sto_code'         => $config->sto_code,
            'sto_type'         => $config->sto_type,
            'periode'          => $config->periode,
            'location_code'    => $locationCode,
            'location_name'    => $locationName,
            'date_from'        => $dateFrom,
            'date_to'          => $dateTo,
            'sto_date'         => $mapping->sto_date ?? null,
            'target_plan_loc'  => $mapping->target_plan_loc ?? 98,
        ];

        if ($realCodes->isEmpty()) {
            return response()->json([
                'status'  => 1,
                'header'  => $header,
                'rows'    => [],
                'totals'  => $this->emptyTotals(),
                'summary' => $this->emptySummary(),
            ]);
        }

        // ── metadata artikel + supplier ──
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
            ->get()
            ->keyBy('article_code');

        // alt_code → real_code map (buat lookup stoResults yang keyed by alt_code)
        $altToReal = $articles->mapWithKeys(fn($a) => [$a->article_alternative_code => $a->article_code]);

        // ── susun baris ──
        $rows        = collect();
        $totalPoin   = 0; // jumlah artikel yang dapat poin akurasi
        $totalArtikel = 0;

        foreach ($realCodes as $rc) {
            $meta   = $articles->get($rc);
            $altCode = $meta->article_alternative_code ?? $rc;
            $mv     = $movements->get($rc);
            $stoRow = $stoResults->get($altCode); // keyed by alt_code

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

            // buang baris benar-benar kosong
            if ($opening == 0 && $totalIn == 0 && $totalOut == 0 && $closing == 0 && !$stoRow) {
                continue;
            }

            // ── kolom STO, Variance, Status, Akurasi ──
            if ($stoRow) {
                $stoQty      = round((float) $stoRow->qty_sto, 2);
                $stoStatus   = $stoRow->count_status ?? 'INCOMPLETE';
            } else {
                $stoQty      = null;   // tidak ada data STO → INCOMPLETE
                $stoStatus   = 'INCOMPLETE';
            }

            // variance = STO − closing (balance sistem)
            $variance = $stoQty !== null ? round($stoQty - $closing, 2) : null;

            // akurasi: dapat poin jika:
            //   - status MATCH (variance = 0), ATAU
            //   - status RECOUNT tapi selisih dalam threshold (default 2% dari closing)
            //   - INCOMPLETE = tidak dapat poin
            $accurate = false;
            if ($stoStatus === 'MATCH') {
                $accurate = true;
            } elseif ($stoStatus === 'RECOUNT' && $variance !== null) {
                if ($closing == 0) {
                    // basis 0: kalau STO juga 0 = akurat, else tidak
                    $accurate = ($stoQty == 0);
                } else {
                    $pctDiff = abs($variance) / abs($closing) * 100;
                    $accurate = $pctDiff <= $this->accuracyThresholdPercent;
                }
            }

            $totalArtikel++;
            if ($accurate) $totalPoin++;

            $rows->push((object) [
                'article_code'        => $rc,
                'alt_code'            => $altCode,
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
            // qty_sto & variance: total hanya jika ada nilainya (null di-skip)
            if ($r->qty_sto !== null) {
                $totals['qty_sto'] = round(($totals['qty_sto'] ?? 0) + $r->qty_sto, 2);
            }
            if ($r->variance !== null) {
                $totals['variance'] = round(($totals['variance'] ?? 0) + $r->variance, 2);
            }
            return $r;
        });

        // ── summary akurasi keseluruhan ──
        $targetPlan  = (float) ($mapping->target_plan_loc ?? 98);
        $actAccuracy = $totalArtikel > 0 ? round($totalPoin / $totalArtikel * 100, 2) : 0;

        $summary = [
            'total_artikel'   => $totalArtikel,
            'total_accurate'  => $totalPoin,
            'total_not'       => $totalArtikel - $totalPoin,
            'accuracy_pct'    => $actAccuracy,
            'target_plan'     => $targetPlan,
            'is_meet_target'  => $actAccuracy >= $targetPlan,
            'threshold_pct'   => $this->accuracyThresholdPercent,
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
    // AGGREGATE STO RESULTS — ambil hasil count per artikel di lokasi ini
    // Qty STO = avg dari counter yang konsisten (atau counter1 jika semua sama).
    // Untuk report: pakai qty_counter1 sebagai representasi utama,
    // fallback ke counter2/3 jika counter1 null.
    // Keyed by article_alternative_code.
    // ══════════════════════════════════════════════
    private function aggregateStoResults($configId, $locationCode, $mapping)
    {
        // Ambil semua sto_dtl artikel di lokasi ini (bisa lintas sto_hdr/sto_number)
        $rows = DB::table('sto_dtl as d')
            ->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id')
            ->join('article as a', 'a.article_alternative_code', '=', 'd.article_code')
            ->where('h.config_id', $configId)
            ->where('h.target_type', 'LOCATION')
            ->where('h.target_ref', $locationCode)
            ->whereNotNull('d.article_code')
            ->select(
                'd.article_code as alt_code',
                'd.qty_counter1',
                'd.qty_counter2',
                'd.qty_counter3',
                'd.count_status'
            )
            ->get();

        if ($rows->isEmpty()) return collect();

        // Group per alt_code, SUM qty per counter slot (lintas sto_number)
        return $rows->groupBy('alt_code')->map(function ($items) {
            // pakai logika yang sama dengan StockCountController:
            // representasi qty = counter1 jika ada, else counter2, else counter3
            // (untuk display laporan, bukan validasi akurasi counting)
            $c1 = $items->whereNotNull('qty_counter1')->sum('qty_counter1');
            $c2 = $items->whereNotNull('qty_counter2')->sum('qty_counter2');
            $c3 = $items->whereNotNull('qty_counter3')->sum('qty_counter3');

            $hasC1 = $items->contains(fn($r) => $r->qty_counter1 !== null);
            $hasC2 = $items->contains(fn($r) => $r->qty_counter2 !== null);
            $hasC3 = $items->contains(fn($r) => $r->qty_counter3 !== null);

            // qty_sto = counter1 jika ada, else counter2, else counter3
            $qty = null;
            if ($hasC1) $qty = $c1;
            elseif ($hasC2) $qty = $c2;
            elseif ($hasC3) $qty = $c3;

            // count_status: ambil status terburuk dari semua baris artikel ini
            $statuses = $items->pluck('count_status')->unique()->values()->all();
            $priority = ['INCOMPLETE' => 0, 'NOT MATCH' => 1, 'RECOUNT' => 2, 'MATCH' => 3];
            $worstStatus = collect($statuses)
                ->sortBy(fn($s) => $priority[$s] ?? 99)
                ->first() ?? 'INCOMPLETE';

            return (object) [
                'qty_sto'      => $qty,
                'count_status' => $worstStatus,
            ];
        });
    }

    // ══════════════════════════════════════════════
    // AGGREGATE MOVEMENT
    // ══════════════════════════════════════════════
    private function aggregateMovements($locationCode, $dateFrom, $dateTo)
    {
        $pReceiving = $this->descReceiving . '%';
        $pTransfer  = '%' . $this->descTransfer . '%';
        $pReplace   = $this->descSupplierReplace . '%';
        $pReturn    = $this->descSupplierReturn . '%';
        $pDnUmum    = $this->descDnUmum . '%';

        return DB::table('warehouse_movement as wm')
            ->select('wm.artikel_code')
            ->selectRaw("SUM(CASE WHEN wm.movement_desc ILIKE ? THEN COALESCE(wm.movement_plus,0) ELSE 0 END) as in_receiving",       [$pReceiving])
            ->selectRaw("SUM(CASE WHEN wm.movement_desc ILIKE ? AND COALESCE(wm.movement_plus,0) > 0 THEN wm.movement_plus ELSE 0 END) as in_return_transfer",  [$pTransfer])
            ->selectRaw("SUM(CASE WHEN wm.movement_desc ILIKE ? THEN COALESCE(wm.movement_plus,0) ELSE 0 END) as in_replace_supplier", [$pReplace])
            ->selectRaw("SUM(CASE WHEN wm.movement_desc ILIKE ? AND COALESCE(wm.movement_min,0)  > 0 THEN wm.movement_min  ELSE 0 END) as out_supply_transfer", [$pTransfer])
            ->selectRaw("SUM(CASE WHEN wm.movement_desc ILIKE ? THEN COALESCE(wm.movement_min,0)  ELSE 0 END) as out_return_supplier", [$pReturn])
            ->selectRaw("SUM(CASE WHEN wm.movement_desc ILIKE ? THEN COALESCE(wm.movement_min,0)  ELSE 0 END) as out_dn_umum",         [$pDnUmum])
            ->where('wm.location_number', $locationCode)
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

    private function emptySummary()
    {
        return [
            'total_artikel'  => 0,
            'total_accurate' => 0,
            'total_not'      => 0,
            'accuracy_pct'   => 0,
            'target_plan'    => 98,
            'is_meet_target' => false,
            'threshold_pct'  => $this->accuracyThresholdPercent,
        ];
    }
}