<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryValuationController extends Controller
{
    // Lokasi yang di-support oleh report ini
    const SUPPORTED_LOCATIONS = ['005', '006', '009'];

    // Label nama gudang
    const LOCATION_LABELS = [
        '005' => 'CHEMICAL',
        '006' => 'CONSUMABLE',
        '009' => 'RAW MATERIAL',
    ];

    // Movement type yang dianggap sebagai MASUK ke gudang (IN)
    const MOVEMENT_IN_TYPES  = ['RECEIVING', 'ADJUSTMENT', 'TRANSFER', 'OPENING BALANCE'];

    // Movement type yang dianggap sebagai KELUAR dari gudang (OUT)
    const MOVEMENT_OUT_TYPES = ['SUPPLY', 'DELIVERY', 'TRANSFER'];

    /**
     * Halaman utama report
     */
    public function index()
    {
        $locations = self::LOCATION_LABELS;
        return view('inventoryValuation.index', compact('locations'));
    }

    /**
     * Endpoint utama: ambil data report (dipanggil via AJAX)
     *
     * PENTING: data dihitung PER LOKASI (bukan digabung lintas lokasi).
     * Ini memperbaiki dua masalah:
     *   1. Saldo awal jadi akurat per lokasi (tidak lagi tercampur ketika
     *      lebih dari satu lokasi dipilih sekaligus).
     *   2. Memungkinkan dibuat "Ringkasan per Lokasi" tanpa perlu query
     *      terpisah, karena tinggal menjumlahkan hasil per artikel per lokasi.
     */
    public function getData(Request $request)
    {
        $fromDate  = $request->input('from_date');   // format: dd-mm-yyyy
        $toDate    = $request->input('to_date');      // format: dd-mm-yyyy
        $locations = $request->input('locations', self::SUPPORTED_LOCATIONS);
        $siteCode  = 'HO'; // sesuaikan jika multi-site

        // Validasi lokasi hanya yang di-support
        $locations = array_values(array_intersect((array) $locations, self::SUPPORTED_LOCATIONS));
        if (empty($locations)) {
            return response()->json(['error' => 'Lokasi tidak valid'], 422);
        }

        $dataPerLokasi    = [];   // key = location code, value = array artikel
        $summaryPerLokasi = [];   // array agregat per lokasi (tanpa detail artikel)

        foreach ($locations as $loc) {
            $articles = $this->getActiveArticles([$loc], $fromDate, $toDate, $siteCode);

            $rows = [];

            $sSaldoAwalQty   = 0; $sSaldoAwalValue = 0;
            $sQtyIn          = 0; $sValueIn        = 0;
            $sQtyOut         = 0; $sValueOut       = 0;
            $sAkhirQty       = 0; $sAkhirValue     = 0;

            foreach ($articles as $art) {
                $artCode = $art->artikel_code;

                // ── Saldo Awal (khusus lokasi ini saja)
                $saldoAwal = $this->resolveSaldoAwal($artCode, [$loc], $fromDate, $siteCode);

                // ── Transaksi IN / OUT (khusus lokasi ini saja)
                $transIn  = $this->getTransaksiIn($artCode, [$loc], $fromDate, $toDate, $siteCode);
                $transOut = $this->getTransaksiOut($artCode, [$loc], $fromDate, $toDate, $siteCode);

                $totalQtyIn   = collect($transIn)->sum('qty');
                $totalValueIn = collect($transIn)->sum('total_value');
                $avgPriceIn   = $totalQtyIn > 0 ? $totalValueIn / $totalQtyIn : 0;

                $totalQtyOut   = collect($transOut)->sum('qty');
                $totalValueOut = collect($transOut)->sum('total_value');

                // Saldo akhir qty = Saldo Awal + Masuk − Keluar
                $saldoAkhirQty = $saldoAwal['qty'] + $totalQtyIn - $totalQtyOut;

                // Saldo akhir value (WACC: saldo awal value + value in, dirata-rata, dikali qty akhir)
                $totalPoolQty   = $saldoAwal['qty'] + $totalQtyIn;
                $totalPoolValue = $saldoAwal['value'] + $totalValueIn;
                $avgPricePool   = $totalPoolQty > 0 ? $totalPoolValue / $totalPoolQty : 0;
                $saldoAkhirValue = $saldoAkhirQty * $avgPricePool;

                // Avg price akhir: referensi dari warehouse_stock
                $avgPriceAkhir = $this->getAvgPriceStock($artCode, [$loc], $siteCode);

                $rows[] = [
                    'artikel_code' => $artCode,
                    'artikel_desc' => $art->artikel_desc,
                    'uom'          => $art->uom,
                    'saldo_awal'   => [
                        'qty'       => round($saldoAwal['qty'], 4),
                        'avg_price' => round($saldoAwal['avg_price'], 4),
                        'value'     => round($saldoAwal['value'], 2),
                    ],
                    'transaksi_in'  => array_map([$this, 'mapTrx'], $transIn),
                    'transaksi_out' => array_map([$this, 'mapTrx'], $transOut),
                    'summary' => [
                        'total_qty_in'      => round($totalQtyIn, 4),
                        'total_value_in'    => round($totalValueIn, 2),
                        'avg_price_in'      => round($avgPriceIn, 4),
                        'total_qty_out'     => round($totalQtyOut, 4),
                        'total_value_out'   => round($totalValueOut, 2),
                        'saldo_akhir_qty'   => round($saldoAkhirQty, 4),
                        'avg_price_akhir'   => round($avgPriceAkhir, 4),
                        'saldo_akhir_value' => round($saldoAkhirValue, 2),
                    ],
                ];

                $sSaldoAwalQty   += $saldoAwal['qty'];
                $sSaldoAwalValue += $saldoAwal['value'];
                $sQtyIn          += $totalQtyIn;
                $sValueIn        += $totalValueIn;
                $sQtyOut         += $totalQtyOut;
                $sValueOut       += $totalValueOut;
                $sAkhirQty       += $saldoAkhirQty;
                $sAkhirValue     += $saldoAkhirValue;
            }

            $dataPerLokasi[$loc] = $rows;

            $summaryPerLokasi[] = [
                'location'          => $loc,
                'label'             => self::LOCATION_LABELS[$loc] ?? $loc,
                'jumlah_artikel'    => count($rows),
                'saldo_awal_qty'    => round($sSaldoAwalQty, 4),
                'saldo_awal_value'  => round($sSaldoAwalValue, 2),
                'total_qty_in'      => round($sQtyIn, 4),
                'total_value_in'    => round($sValueIn, 2),
                'total_qty_out'     => round($sQtyOut, 4),
                'total_value_out'   => round($sValueOut, 2),
                'saldo_akhir_qty'   => round($sAkhirQty, 4),
                'saldo_akhir_value' => round($sAkhirValue, 2),
            ];
        }

        return response()->json([
            'data'               => $dataPerLokasi,
            'summary_per_lokasi' => $summaryPerLokasi,
            'from_date'          => $fromDate,
            'to_date'            => $toDate,
            'locations'          => $locations,
            'loc_labels'         => array_intersect_key(self::LOCATION_LABELS, array_flip($locations)),
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    private function mapTrx($t): array
    {
        return [
            'tanggal'       => $t->tanggal,
            'doc_number'    => $t->doc_number,
            'movement_type' => $t->movement_type,
            'qty'           => round($t->qty, 4),
            'price'         => round($t->price, 4),
            'total_value'   => round($t->total_value, 2),
            'keterangan'    => $t->keterangan,
            'location'      => $t->location,
        ];
    }

    /**
     * Ambil semua artikel aktif yang relevan dengan lokasi & periode
     */
    private function getActiveArticles(array $locations, string $fromDate, string $toDate, string $siteCode)
    {
        $locPlaceholders = implode(',', array_fill(0, count($locations), '?'));

        $sql = "
            SELECT
                a.article_code      AS artikel_code,
                a.article_desc      AS artikel_desc,
                COALESCE(a.uom, '-') AS uom
            FROM article a
            WHERE a.article_code IN (
                -- Dari warehouse_movement dalam periode
                SELECT DISTINCT wm.artikel_code
                FROM warehouse_movement wm
                WHERE wm.site_code = ?
                  AND wm.location_number IN ({$locPlaceholders})
                  AND TO_DATE(wm.movement_date, 'DD-MM-YYYY')
                      BETWEEN TO_DATE(?, 'DD-MM-YYYY') AND TO_DATE(?, 'DD-MM-YYYY')
                UNION
                -- Dari warehouse_stock yang punya saldo
                SELECT DISTINCT ws.article_code
                FROM warehouse_stock ws
                WHERE ws.site_code = ?
                  AND ws.location_number IN ({$locPlaceholders})
                  AND ws.article_qty <> 0
            )
            ORDER BY a.article_desc
        ";

        $bindings = array_merge(
            [$siteCode], $locations,       // untuk warehouse_movement
            [$fromDate, $toDate],          // date range
            [$siteCode], $locations        // untuk warehouse_stock
        );

        return DB::select($sql, $bindings);
    }

    /**
     * Resolve saldo awal artikel per kumpulan lokasi (biasanya dipanggil dengan 1 lokasi).
     * Logic:
     *   1. Cari opening balance (stock_adjustment adj_type='OPENING BALANCE') untuk bulan sebelum fromDate
     *   2. Jika tidak ada, gunakan last_qty dari movement terakhir sebelum fromDate
     *   3. Jika tidak ada sama sekali (sebelum Juli 2026 atau tidak ada data), return 0
     */
    private function resolveSaldoAwal(string $artCode, array $locations, string $fromDate, string $siteCode): array
    {
        $dtFrom     = \DateTime::createFromFormat('d-m-Y', $fromDate);
        $floorDate  = \DateTime::createFromFormat('d-m-Y', '01-07-2026'); // tidak ada data sebelum ini

        $prevDay    = clone $dtFrom;
        $prevDay->modify('-1 day');
        $prevDayStr = $prevDay->format('d-m-Y');

        $prevMonth  = (int) $dtFrom->format('m') - 1;
        $prevYear   = (int) $dtFrom->format('Y');
        if ($prevMonth <= 0) { $prevMonth = 12; $prevYear--; }

        $locPlaceholders = implode(',', array_fill(0, count($locations), '?'));

        // ── Coba Opening Balance dari stock_adjustment
        $obSql = "
            SELECT
                COALESCE(SUM(det.stock_after), 0) AS ob_qty
            FROM stock_adjustment_hdr hdr
            JOIN stock_adjustment_det det ON det.adj_code = hdr.adj_code
            WHERE hdr.adj_type = 'OPENING BALANCE'
              AND hdr.status != '5'
              AND hdr.location_code IN ({$locPlaceholders})
              AND CAST(hdr.periode AS INTEGER) = ?
              AND EXTRACT(YEAR FROM TO_DATE(hdr.adj_date, 'DD-MM-YYYY')) = ?
              AND det.article_code = ?
        ";
        $obRow = DB::selectOne($obSql, array_merge($locations, [$prevMonth, $prevYear, $artCode]));

        if ($obRow && $obRow->ob_qty > 0) {
            $avgPrice = $this->getAvgPriceStock($artCode, $locations, $siteCode);
            return [
                'qty'       => (float) $obRow->ob_qty,
                'avg_price' => $avgPrice,
                'value'     => (float) $obRow->ob_qty * $avgPrice,
                'source'    => 'opening_balance',
            ];
        }

        if ($prevDay < $floorDate) {
            return ['qty' => 0, 'avg_price' => 0, 'value' => 0, 'source' => 'zero'];
        }

        $fallbackSql = "
            SELECT
                COALESCE(SUM(last_movement.last_qty), 0) AS saldo_qty
            FROM (
                SELECT DISTINCT ON (artikel_code, location_number)
                    artikel_code,
                    location_number,
                    last_qty
                FROM warehouse_movement
                WHERE site_code = ?
                  AND artikel_code = ?
                  AND location_number IN ({$locPlaceholders})
                  AND TO_DATE(movement_date, 'DD-MM-YYYY') <= TO_DATE(?, 'DD-MM-YYYY')
                ORDER BY artikel_code, location_number,
                         TO_DATE(movement_date, 'DD-MM-YYYY') DESC,
                         movement_code DESC
            ) AS last_movement
        ";
        $fallbackRow = DB::selectOne($fallbackSql, array_merge(
            [$siteCode, $artCode], $locations, [$prevDayStr]
        ));

        $saldoQty = $fallbackRow ? (float) $fallbackRow->saldo_qty : 0;
        $avgPrice = $saldoQty > 0 ? $this->getAvgPriceStock($artCode, $locations, $siteCode) : 0;

        return [
            'qty'       => $saldoQty,
            'avg_price' => $avgPrice,
            'value'     => $saldoQty * $avgPrice,
            'source'    => 'last_movement',
        ];
    }

    /**
     * Transaksi MASUK (IN) dalam periode
     */
    private function getTransaksiIn(string $artCode, array $locations, string $fromDate, string $toDate, string $siteCode): array
    {
        $locPlaceholders = implode(',', array_fill(0, count($locations), '?'));

        $sql = "
            SELECT
                wm.movement_date                AS tanggal,
                wm.movement_transnno            AS doc_number,
                wm.movement_type                AS movement_type,
                wm.movement_plus                AS qty,
                COALESCE(wm.movement_price, 0)  AS price,
                wm.movement_plus * COALESCE(wm.movement_price, 0) AS total_value,
                COALESCE(wm.movement_desc, '')  AS keterangan,
                wm.location_number              AS location,
                wm.movement_code
            FROM warehouse_movement wm
            WHERE wm.site_code = ?
              AND wm.artikel_code = ?
              AND wm.movement_plus > 0
              AND (
                    wm.location_number IN ({$locPlaceholders})
                 OR
                    wm.movement_to IN ({$locPlaceholders})
              )
              AND TO_DATE(wm.movement_date, 'DD-MM-YYYY')
                  BETWEEN TO_DATE(?, 'DD-MM-YYYY') AND TO_DATE(?, 'DD-MM-YYYY')
              AND wm.movement_type NOT IN ('CANCEL RECEIVING', 'CANCEL ADJUSTMENT', 'CANCEL TRANSFER')
            ORDER BY TO_DATE(wm.movement_date, 'DD-MM-YYYY'), wm.movement_code
        ";

        return DB::select($sql, array_merge(
            [$siteCode, $artCode],
            $locations,
            $locations,
            [$fromDate, $toDate]
        ));
    }

    /**
     * Transaksi KELUAR (OUT) dalam periode
     */
    private function getTransaksiOut(string $artCode, array $locations, string $fromDate, string $toDate, string $siteCode): array
    {
        $locPlaceholders = implode(',', array_fill(0, count($locations), '?'));

        $sql = "
            SELECT
                wm.movement_date                AS tanggal,
                wm.movement_transnno            AS doc_number,
                wm.movement_type                AS movement_type,
                wm.movement_min                 AS qty,
                COALESCE(wm.movement_price, 0)  AS price,
                wm.movement_min * COALESCE(wm.movement_price, 0) AS total_value,
                COALESCE(wm.movement_desc, '')  AS keterangan,
                wm.location_number              AS location,
                wm.movement_code
            FROM warehouse_movement wm
            WHERE wm.site_code = ?
              AND wm.artikel_code = ?
              AND wm.movement_min > 0
              AND wm.location_number IN ({$locPlaceholders})
              AND TO_DATE(wm.movement_date, 'DD-MM-YYYY')
                  BETWEEN TO_DATE(?, 'DD-MM-YYYY') AND TO_DATE(?, 'DD-MM-YYYY')
              AND wm.movement_type NOT IN ('CANCEL RECEIVING', 'CANCEL ADJUSTMENT', 'CANCEL TRANSFER')
            ORDER BY TO_DATE(wm.movement_date, 'DD-MM-YYYY'), wm.movement_code
        ";

        return DB::select($sql, array_merge(
            [$siteCode, $artCode],
            $locations,
            [$fromDate, $toDate]
        ));
    }

    /**
     * Ambil avg_price terkini dari warehouse_stock
     */
    private function getAvgPriceStock(string $artCode, array $locations, string $siteCode): float
    {
        $locPlaceholders = implode(',', array_fill(0, count($locations), '?'));

        $row = DB::selectOne("
            SELECT
                CASE
                    WHEN SUM(article_qty) > 0
                    THEN SUM(article_qty * COALESCE(avg_price, 0)) / SUM(article_qty)
                    ELSE AVG(COALESCE(avg_price, 0))
                END AS weighted_avg
            FROM warehouse_stock
            WHERE site_code = ?
              AND article_code = ?
              AND location_number IN ({$locPlaceholders})
        ", array_merge([$siteCode, $artCode], $locations));

        return $row ? (float) $row->weighted_avg : 0;
    }

    /**
     * Export data ke format yang bisa langsung dipakai JS (sama dengan getData tapi untuk export)
     */
    public function export(Request $request)
    {
        return $this->getData($request);
    }
}