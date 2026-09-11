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
    // MOVEMENT DETAIL (drill-down) — dipakai modal "klik angka opening/in/out
    // di tabel report -> lihat daftar dokumen di baliknya". Pakai filter
    // penuh yang sama dengan aggregateMovements(), supaya jumlah baris di
    // modal SELALU sinkron dengan angka yang ditampilkan di sel tabel.
    // ══════════════════════════════════════════════
    public function movementDetail(Request $request)
    {
        $configId     = Crypt::decryptString($request->config_id);
        $locationCode = $request->location_code;
        $articleCode  = $request->article_code;
        $columnKey    = $request->column_key;

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

        $family = $this->resolveLocationFamily($locationCode);

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

        if ($columnKey === 'opening') {
            $rows  = $this->buildOpeningBreakdown($articleCode, $family, $openingDate, $configId, $locationCode);
            $label = 'Opening Balance';
        } else {
            $groupConfig = $this->getGroupConfig($locationCode);
            $def = $groupConfig['in'][$columnKey] ?? $groupConfig['out'][$columnKey] ?? null;
            if (!$def) {
                return response()->json(['status' => 0, 'message' => 'Kolom tidak dikenali.'], 422);
            }
            $types = array_map('strtoupper', $def['types']);
            $rows  = $this->fetchFilteredMovementRows($family, $articleCode, $dateFrom, $dateTo, $types, "wm.{$def['qty']}");
            $label = $def['label'];
        }

        return response()->json([
            'status' => 1,
            'label'  => $label,
            'rows'   => $rows,
            'total'  => round(array_sum(array_column($rows, 'qty')), 2),
        ]);
    }

    /**
     * Breakdown "opening" — SENGAJA TIDAK merekonstruksi manual dari movement
     * (versi lama sempat begitu, tapi malah menampilkan baris movement basi/
     * phantom yang bikin bingung -- lihat diskusi di sesi ini). Aturannya
     * sekarang cuma dua kondisi:
     *
     * 1. Ada dokumen OPENING BALANCE yang POSTED dan tanggalnya PERSIS SAMA
     *    dengan $openingDate -> tampilkan SATU baris itu saja (dokumen ini
     *    memang didesain selalu up-to-date lewat absorbIntoLatestOpeningBalance()
     *    di StockAdjustmentController, jadi stock_after-nya sudah final,
     *    tidak perlu tambahan apa pun).
     * 2. Kalau tidak ada (baik karena tidak ada OB sama sekali, atau OB yang
     *    ada tanggalnya lebih tua / ada gap) -> JANGAN coba hitung manual.
     *    Cukup tampilkan satu baris berisi kode artikel yang jadi hyperlink
     *    ke halaman stock movement (WarehouseControllerv2::apiStockMovement,
     *    sumber yang sama dengan ArticleController::movement2 -- lebih
     *    teruji), di-deep-link supaya otomatis terbuka & ter-filter dari
     *    tanggal 1 sampai akhir bulan periode sebelumnya (satu bulan penuh
     *    sebelum $openingDate). Qty yang ditampilkan tetap angka get_last_qty_new
     *    yang sama seperti di sel tabel (cuma informasional, bukan hasil
     *    penjumlahan baris-baris di modal ini).
     */
    private function buildOpeningBreakdown($articleCode, array $family, $openingDate, $configId, $locationCode)
    {
        $anchor = DB::table('stock_adjustment_hdr as h')
            ->join('stock_adjustment_det as d', 'd.adj_code', '=', 'h.adj_code')
            ->where('h.adj_type', 'OPENING BALANCE')
            ->where('h.status', '4') // ST_POSTED, lihat StockAdjustmentController
            ->whereIn('h.location_code', $family)
            ->where('d.article_code', $articleCode)
            ->whereRaw("TO_DATE(h.adj_date,'dd-mm-yyyy') = TO_DATE(?,'YYYY-MM-DD')", [$openingDate])
            ->select('h.id', 'h.adj_code', 'h.adj_date', 'd.stock_after')
            ->first();

        if ($anchor) {
            return [[
                'date'       => $anchor->adj_date,
                'doc_number' => $anchor->adj_code,
                'doc_type'   => 'OPENING BALANCE',
                'qty'        => round((float) $anchor->stock_after, 2),
                'link'       => route('stockAdjustment.show', ['id' => Crypt::encryptString($anchor->id)]),
            ]];
        }

        $anchorLoc  = $this->resolveLocationAnchor($locationCode);
        $openingQty = round((float) $this->getOpeningBalance($articleCode, $openingDate, $anchorLoc, $configId), 2);

        $openingDt  = \DateTime::createFromFormat('Y-m-d', $openingDate);
        $monthStart = $openingDt ? $openingDt->format('01-m-Y') : null;
        $monthEnd   = $openingDt ? $openingDt->format('d-m-Y') : null;

        $article = DB::table('article')->where('article_code', $articleCode)
            ->select('article_alternative_code', 'article_desc')->first();
        $altCode = $article->article_alternative_code ?? $articleCode;

        $link = route('warehouse.articlev2', [
            'code'          => $altCode,
            'real_code'     => $articleCode,
            'location'      => $anchorLoc,
            'open_movement' => 1,
            'date_from'     => $monthStart,
            'date_to'       => $monthEnd,
            'desc'          => $article->article_desc ?? '',
        ]);

        return [[
            'date'       => null,
            'doc_number' => $altCode,
            'doc_type'   => 'Tidak ada OB — lihat movement sistem',
            'qty'        => $openingQty,
            'link'       => $link,
        ]];
    }

    /**
     * Ambil baris movement mentah (sudah difilter penuh: exclude
     * CANCEL/DELETE/REVISI/RETURN-CANCEL/RETURN-REVERSE + status dokumen
     * induk != CANCELED, sama persis dengan aggregateMovements()) untuk satu
     * artikel. $types null = semua tipe movement (dipakai untuk breakdown
     * opening); $qtyExpr = ekspresi SQL untuk qty per baris (nama kolom
     * movement_plus/movement_min biasa untuk kolom IN/OUT, atau
     * "movement_plus - movement_min" untuk versi signed/opening).
     */
    private function fetchFilteredMovementRows(array $family, $articleCode, $dateFrom, $dateTo, ?array $types, string $qtyExpr): array
    {
        $bind = ['dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'article' => $articleCode];

        $locPh = [];
        foreach (array_values($family) as $i => $loc) {
            $key = "loc{$i}";
            $bind[$key] = $loc;
            $locPh[] = ":{$key}";
        }

        $typeFilter = '';
        if ($types) {
            $typePh = [];
            foreach ($types as $i => $t) {
                $key = "type{$i}";
                $bind[$key] = strtoupper($t);
                $typePh[] = ":{$key}";
            }
            $typeFilter = "AND UPPER(wm.movement_type) IN (" . implode(',', $typePh) . ")";
        }

        $sql = "
        WITH ledger AS (
            SELECT wm.movement_code, wm.movement_date, wm.movement_transnno, wm.movement_type,
                   ($qtyExpr) as qty,
                   CASE wm.movement_type
                       WHEN 'RECEIVING'        THEN (SELECT id FROM receiving_hdr         WHERE rec_number      = wm.movement_transnno LIMIT 1)
                       WHEN 'TRANSFER'         THEN (SELECT id FROM transfer_stock_hdr    WHERE tr_number       = wm.movement_transnno LIMIT 1)
                       WHEN 'SUPPLY'           THEN (SELECT id FROM transfer_stock_hdr    WHERE tr_number       = wm.movement_transnno LIMIT 1)
                       WHEN 'DELIVERY'         THEN (SELECT id FROM delivery_hdr          WHERE delivery_number = wm.movement_transnno LIMIT 1)
                       WHEN 'RETURN'           THEN (SELECT id FROM dn_return_hdr         WHERE return_number   = wm.movement_transnno LIMIT 1)
                       WHEN 'REPLACEMENT'      THEN (SELECT id FROM dn_replace_hdr        WHERE replace_number  = wm.movement_transnno LIMIT 1)
                       WHEN 'DN SEMENTARA'     THEN (SELECT id FROM temporary_dn_hdr      WHERE tdn_number      = wm.movement_transnno LIMIT 1)
                       WHEN 'DN UMUM'          THEN (SELECT id FROM dn_general_hdr        WHERE tdn_number      = wm.movement_transnno LIMIT 1)
                       WHEN 'SUPPLIER RETURN'  THEN (SELECT id FROM supplier_return_hdr   WHERE return_number   = wm.movement_transnno LIMIT 1)
                       WHEN 'SUPPLIER REPLACE' THEN (SELECT id FROM supplier_replace_hdr  WHERE replace_number  = wm.movement_transnno LIMIT 1)
                       ELSE NULL
                   END AS doc_id,
                   CASE wm.movement_type
                       WHEN 'RECEIVING'    THEN (SELECT status FROM receiving_hdr        WHERE rec_number      = wm.movement_transnno LIMIT 1)
                       WHEN 'TRANSFER'     THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = wm.movement_transnno LIMIT 1)
                       WHEN 'SUPPLY'       THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = wm.movement_transnno LIMIT 1)
                       WHEN 'DELIVERY'     THEN (SELECT status FROM delivery_hdr         WHERE delivery_number = wm.movement_transnno LIMIT 1)
                       WHEN 'RETURN'       THEN (SELECT status FROM dn_return_hdr        WHERE return_number   = wm.movement_transnno LIMIT 1)
                       WHEN 'REPLACEMENT'  THEN (SELECT status FROM dn_replace_hdr       WHERE replace_number  = wm.movement_transnno LIMIT 1)
                       WHEN 'DN SEMENTARA' THEN (SELECT status FROM temporary_dn_hdr     WHERE tdn_number      = wm.movement_transnno LIMIT 1)
                       WHEN 'DN UMUM'      THEN (SELECT status FROM dn_general_hdr       WHERE tdn_number      = wm.movement_transnno LIMIT 1)
                       ELSE NULL
                   END AS hdr_status
            FROM warehouse_movement wm
            WHERE wm.artikel_code = :article
              AND wm.location_number IN (" . implode(',', $locPh) . ")
              AND TO_DATE(wm.movement_date,'DD-MM-YYYY') BETWEEN TO_DATE(:dateFrom,'DD-MM-YYYY') AND TO_DATE(:dateTo,'DD-MM-YYYY')
              $typeFilter
              AND wm.movement_type NOT ILIKE 'CANCEL %'
              AND wm.movement_type NOT ILIKE 'DELETE%'
              AND wm.movement_type NOT ILIKE 'REVISI %'
              AND wm.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
        )
        SELECT movement_date, movement_transnno, movement_type, qty, doc_id
        FROM ledger
        WHERE hdr_status IS DISTINCT FROM '5'
          AND COALESCE(qty,0) <> 0
        ORDER BY TO_DATE(movement_date,'DD-MM-YYYY'), movement_code";

        $raw = DB::select($sql, $bind);

        return collect($raw)->map(function ($r) {
            return [
                'date'       => $r->movement_date,
                'doc_number' => $r->movement_transnno,
                'doc_type'   => $r->movement_type,
                'qty'        => round((float) $r->qty, 2),
                'link'       => $this->documentLink($r->movement_type, $r->doc_id),
            ];
        })->values()->all();
    }

    /**
     * URL detail dokumen per movement_type, dipakai modal drill-down supaya
     * nomor dokumen bisa jadi hyperlink (buka tab baru). null kalau tipe
     * movement-nya tidak punya halaman detail yang dikenal, atau doc_id-nya
     * tidak ketemu (mis. baris movement lama yang dokumen induknya sudah
     * tidak ada).
     */
    private function documentLink($movementType, $docId)
    {
        if (!$docId) return null;

        $map = [
            'RECEIVING'        => 'receiving.show',
            'TRANSFER'         => 'transferStock.show',
            'SUPPLY'           => 'transferStock.show',
            'DELIVERY'         => 'delivery.show',
            'RETURN'           => 'dnReturn.show',
            'REPLACEMENT'      => 'dnReplace.show',
            'DN SEMENTARA'     => 'suratJalanSementara.show',
            'DN UMUM'          => 'dnGeneral.show',
            'SUPPLIER RETURN'  => 'supplierReturn.show',
            'SUPPLIER REPLACE' => 'supplierReplace.show',
        ];

        $routeName = $map[$movementType] ?? null;
        if (!$routeName) return null;

        return route($routeName, ['id' => Crypt::encryptString($docId)]);
    }

    // ══════════════════════════════════════════════
    // AGGREGATE MOVEMENT — family-aware (whereIn family, bukan single location).
    //
    // FILTER PENUH (disamakan dengan ArticleController::movement2()): exclude
    // bukan cuma 'CANCEL %', tapi juga 'DELETE%'/'REVISI %'/'RETURN-CANCEL'/
    // 'RETURN-REVERSE', DAN baris yang dokumen induknya SAAT INI berstatus
    // CANCELED (status '5') walau movement_type-nya sendiri masih normal.
    //
    // SENGAJA TIDAK pakai dedup "keep baris terakhir per (artikel,dokumen,
    // lokasi)" ala movement2/CheckStockAnomaly — itu punya bug terbukti:
    // salah membuang baris yang SAH kalau satu dokumen punya >1 baris untuk
    // artikel yang sama di lokasi yang sama (mis. BOM konsumsi RM yang sama
    // 2x dalam satu Actual Loading -> under-count). Modul-modul transaksi
    // sekarang (Receiving/SupplierReturn/SupplierReplace/TransferStock/
    // Delivery/ActualLoading/DnReturn/DnReplace/TemporaryDn/DnGeneral) sudah
    // dibetulkan supaya edit/revisi MENGHAPUS baris movement lama alih-alih
    // menyisakannya, jadi SUM polos di sini sudah aman untuk data baru; sisa
    // baris basi dari data historis lama tertangani lewat filter status
    // dokumen (hdr_status) di bawah.
    // ══════════════════════════════════════════════
    private function aggregateMovements(array $family, $dateFrom, $dateTo, $locationCode)
    {
        // group config tetap ditentukan dari lokasi yang DIPILIH user (anchor),
        // karena itu yang menentukan kolom in/out mana yang relevan
        $config = $this->getGroupConfig($locationCode);

        $selectParts = [];
        $bind        = ['dateFrom' => $dateFrom, 'dateTo' => $dateTo];
        $bindIdx     = 0;

        foreach (['in', 'out'] as $direction) {
            foreach (($config[$direction] ?? []) as $colKey => $def) {
                $qtyField = $def['qty'];
                $types    = array_map('strtoupper', $def['types']);

                $placeholders = [];
                foreach ($types as $t) {
                    $key = 't' . $bindIdx++;
                    $bind[$key] = $t;
                    $placeholders[] = ":{$key}";
                }
                $inList = implode(',', $placeholders);

                $selectParts[] = "SUM(CASE WHEN UPPER(kept.movement_type) IN ($inList) AND COALESCE(kept.$qtyField,0) > 0
                         THEN kept.$qtyField ELSE 0 END) as $colKey";
            }
        }

        if (empty($selectParts)) {
            return collect();
        }

        $locPlaceholders = [];
        foreach (array_values($family) as $i => $loc) {
            $key = 'loc' . $i;
            $bind[$key] = $loc;
            $locPlaceholders[] = ":{$key}";
        }
        $locIn = implode(',', $locPlaceholders);

        $sql = "
        WITH ledger AS (
            SELECT wm.artikel_code, wm.movement_type, wm.movement_plus, wm.movement_min,
                CASE wm.movement_type
                    WHEN 'RECEIVING'    THEN (SELECT status FROM receiving_hdr        WHERE rec_number      = wm.movement_transnno LIMIT 1)
                    WHEN 'TRANSFER'     THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = wm.movement_transnno LIMIT 1)
                    WHEN 'SUPPLY'       THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = wm.movement_transnno LIMIT 1)
                    WHEN 'DELIVERY'     THEN (SELECT status FROM delivery_hdr         WHERE delivery_number = wm.movement_transnno LIMIT 1)
                    WHEN 'RETURN'       THEN (SELECT status FROM dn_return_hdr        WHERE return_number   = wm.movement_transnno LIMIT 1)
                    WHEN 'REPLACEMENT'  THEN (SELECT status FROM dn_replace_hdr       WHERE replace_number  = wm.movement_transnno LIMIT 1)
                    WHEN 'DN SEMENTARA' THEN (SELECT status FROM temporary_dn_hdr     WHERE tdn_number      = wm.movement_transnno LIMIT 1)
                    WHEN 'DN UMUM'      THEN (SELECT status FROM dn_general_hdr       WHERE tdn_number      = wm.movement_transnno LIMIT 1)
                    ELSE NULL
                END AS hdr_status
            FROM warehouse_movement wm
            WHERE wm.location_number IN ($locIn)
              AND TO_DATE(wm.movement_date,'DD-MM-YYYY') BETWEEN TO_DATE(:dateFrom,'DD-MM-YYYY') AND TO_DATE(:dateTo,'DD-MM-YYYY')
              AND wm.movement_type NOT ILIKE 'CANCEL %'
              AND wm.movement_type NOT ILIKE 'DELETE%'
              AND wm.movement_type NOT ILIKE 'REVISI %'
              AND wm.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
        ),
        kept AS (
            SELECT * FROM ledger WHERE hdr_status IS DISTINCT FROM '5'
        )
        SELECT kept.artikel_code, " . implode(', ', $selectParts) . "
        FROM kept
        GROUP BY kept.artikel_code";

        return collect(DB::select($sql, $bind))->keyBy('artikel_code');
    }

    // ══════════════════════════════════════════════
    // AGGREGATE STO RESULTS — family-aware.
    // Ikut semua sibling mapping dalam config yang sama (child+parent), sama
    // seperti syncArticleStatus()/collectFamilyDtlRowsByLocation() di STO.
    //
    // Hanya ambil qty hasil hitung fisik (qty_counter1/2/3) dari sto_dtl --
    // TIDAK lagi ambil count_status. Match/tidaknya sekarang dihitung sendiri
    // oleh StoReportController berdasarkan qty ini vs closing versi report
    // sendiri (lihat buildReport()), bukan dipinjam dari verdict StockCount.
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
            ->select('d.article_code as alt_code', 'd.qty_counter1', 'd.qty_counter2', 'd.qty_counter3')
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

            return (object) ['qty_sto' => $qty];
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

            // ── Status dihitung SENDIRI oleh STO Report: qty hasil hitung fisik
            //    (qty_sto, dari sto_dtl) dibandingkan langsung dengan closing
            //    versi report ini sendiri -- BUKAN lagi dipinjam dari
            //    sto_dtl.count_status (verdict StockCount, yang bisa berbeda
            //    basis perhitungannya dari closing yang ditampilkan di sini).
            $stoQty   = ($stoRow && $stoRow->qty_sto !== null) ? round((float) $stoRow->qty_sto, 2) : null;
            $variance = $stoQty !== null ? round($stoQty - $closing, 2) : null;

            if ($stoQty === null) {
                $stoStatus = 'INCOMPLETE';
                $accurate  = false;
            } elseif ($closing == 0) {
                $accurate  = ($stoQty == 0);
                $stoStatus = $accurate ? 'MATCH' : 'NOT MATCH';
            } else {
                $accurate  = (abs($variance) / abs($closing) * 100) <= $this->accuracyThresholdPercent;
                $stoStatus = $accurate ? 'MATCH' : 'NOT MATCH';
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