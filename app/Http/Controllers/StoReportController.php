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

    // Semua lokasi yang didukung format report-nya (gabungan dari semua grup)
    private $supportedLocations = ['005', '006', '009', '012', '007', '008'];

    // ══════════════════════════════════════════════
    // GRUP LOKASI
    // Tiap grup punya kategori IN/OUT movement_type yang berbeda,
    // makanya di-pisah biar report bisa nampilin kolom yang relevan
    // per grup tanpa saling ganggu.
    // ══════════════════════════════════════════════
    private $locationGroups = [
        'CHEMICAL'   => ['005', '006', '009'],
        'WIP_FG_OT'  => ['012', '007', '008'], // WIP, Finish Goods, OT
    ];

    // ══════════════════════════════════════════════
    // KONFIGURASI KATEGORI MOVEMENT PER GRUP
    //
    // PENENTU KATEGORI = movement_type (bukan movement_desc)
    // movement_desc = isi bebas (nama transaksi), tidak bisa diandalkan untuk filter
    //
    // Tiap kolom:
    //   'label' => nama tampilan
    //   'types' => daftar movement_type yang match (dibandingkan UPPER(), jadi case-insensitive)
    //   'qty'   => kolom qty yang dipakai: movement_plus (IN) atau movement_min (OUT)
    //
    // CANCEL prefix (mis. 'CANCEL SUPPLY', 'CANCEL TRANSFER') selalu diabaikan
    // lewat filter global movement_type NOT ILIKE 'CANCEL %'.
    //
    // Catatan: movement_type 'TRANSFER' dipakai baik untuk IN maupun OUT
    // (transfer masuk pakai movement_plus, transfer keluar pakai movement_min) —
    // jadi satu movement_type bisa muncul di 2 kolom berbeda, dibedakan dari
    // qty field mana yang dipakai dan diisi.
    // ══════════════════════════════════════════════
    private $movementConfig = [
        'CHEMICAL' => [
            'in' => [
                'in_receiving'        => ['label' => 'Receiving',        'types' => ['RECEIVING'],        'qty' => 'movement_plus'],
                'in_return_transfer'  => ['label' => 'Return',  'types' => ['TRANSFER'],         'qty' => 'movement_plus'],
                'in_replace_supplier' => ['label' => 'Supplier Replace', 'types' => ['SUPPLIER REPLACE'], 'qty' => 'movement_plus'],
            ],
            'out' => [
                // Digabung SUPPLY + TRANSFER (movement_min) jadi 1 kolom, sesuai perilaku asli
                // sebelum refactor (dulu outSup = out_supply_transfer + out_transfer di PHP).
                'out_supply_transfer' => ['label' => 'Supply', 'types' => ['SUPPLY', 'TRANSFER'], 'qty' => 'movement_min'],
                'out_return_supplier' => ['label' => 'Return Supplier', 'types' => ['SUPPLIER RETURN'],    'qty' => 'movement_min'],
                'out_dn_umum'         => ['label' => 'DN Umum',         'types' => ['DN UMUM'],            'qty' => 'movement_min'],
            ],
        ],

        // WIP (012), Finish Goods (007), OT (008)
        'WIP_FG_OT' => [
            'in' => [
                'in_transfer'        => ['label' => 'Transfer In',     'types' => ['TRANSFER'], 'qty' => 'movement_plus'],
                'in_return_customer' => ['label' => 'Return Customer', 'types' => ['RETURN'],    'qty' => 'movement_plus'],
            ],
            'out' => [
                'out_transfer'     => ['label' => 'Transfer Out', 'types' => ['TRANSFER'],      'qty' => 'movement_min'],
                'out_delivery'     => ['label' => 'Delivery',     'types' => ['DELIVERY','Delivery'],      'qty' => 'movement_min'],
                'out_dn_umum'      => ['label' => 'DN Umum',      'types' => ['DN UMUM'],       'qty' => 'movement_min'],
                'out_dn_sementara' => ['label' => 'DN Sementara', 'types' => ['DN SEMENTARA'],  'qty' => 'movement_min'],
                'out_replacement'  => ['label' => 'Replacement',  'types' => ['REPLACEMENT'],   'qty' => 'movement_min'],
            ],
        ],
    ];

    // threshold akurasi: selisih <= N% dari closing → dapat poin
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

        $result = $this->buildReport($configId, $locationCode, $request->date_range);

        if ($result['status'] === 0) {
            return response()->json(
                ['status' => 0, 'message' => $result['message']],
                $result['code'] ?? 422
            );
        }

        // rows/totals sudah collection/array → json-kan apa adanya
        return response()->json([
            'status'  => 1,
            'header'  => $result['header'],
            'rows'    => $result['rows']->values(),
            'totals'  => $result['totals'],
            'summary' => $result['summary'],
            'columns' => $result['columns'], // definisi kolom in/out dinamis, dipakai FE buat render header tabel
        ]);
    }

    // ══════════════════════════════════════════════
    // AGGREGATE MOVEMENT — dinamis berdasarkan konfigurasi grup lokasi
    // ══════════════════════════════════════════════
    private function aggregateMovements($locationCode, $dateFrom, $dateTo)
    {
        $config = $this->getGroupConfig($locationCode);

        $query = DB::table('warehouse_movement as wm')->select('wm.artikel_code');

        foreach (['in', 'out'] as $direction) {
            foreach (($config[$direction] ?? []) as $colKey => $def) {
                $qtyField  = $def['qty']; // movement_plus | movement_min
                $types     = array_map('strtoupper', $def['types']);
                $placeholders = implode(',', array_fill(0, count($types), '?'));

                $query->selectRaw(
                    "SUM(CASE WHEN UPPER(wm.movement_type) IN ($placeholders) AND COALESCE(wm.$qtyField,0) > 0
                         THEN wm.$qtyField ELSE 0 END) as $colKey",
                    $types
                );
            }
        }

        $query->where('wm.location_number', $locationCode)
            // exclude semua tipe CANCEL (CANCEL SUPPLY, CANCEL TRANSFER, dll)
            ->where('wm.movement_type', 'not ilike', 'CANCEL %')
            ->whereRaw(
                "TO_DATE(wm.movement_date,'DD-MM-YYYY') BETWEEN TO_DATE(?,'DD-MM-YYYY') AND TO_DATE(?,'DD-MM-YYYY')",
                [$dateFrom, $dateTo]
            )
            ->groupBy('wm.artikel_code');

        return $query->get()->keyBy('artikel_code');
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
        // NOTE: parameter ke-3 'HO' dari get_last_qty_new() dipertahankan apa
        // adanya sesuai kode lama. Kalau ternyata parameter itu spesifik untuk
        // grup CHEMICAL saja (bukan berlaku umum), perlu dicek ke definisi
        // function get_last_qty_new_grouped / get_last_qty_new di database
        // untuk lokasi WIP/FG/OT.
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

    // ══════════════════════════════════════════════
    // TOTALS / SUMMARY KOSONG — dinamis per lokasi
    // ══════════════════════════════════════════════
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

    // ══════════════════════════════════════════════
    // DEFINISI KOLOM (buat header tabel di FE) — dinamis per lokasi
    // ══════════════════════════════════════════════
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
    // return: ['status'=>1, 'header'=>..., 'rows'=>..., 'totals'=>..., 'summary'=>..., 'columns'=>...]
    //         atau ['status'=>0, 'message'=>..., 'code'=>...]
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

            $opening = round((float) $this->getOpeningBalance($rc, $openingDate, $locationCode), 2);

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
                $result['columns'] // export perlu tahu kolom mana yang aktif untuk lokasi ini
            ),
            $fileName
        );
    }
}