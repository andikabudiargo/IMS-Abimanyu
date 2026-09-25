<?php

namespace App\Http\Controllers\Conversion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DataTables;
use DB;
use Approval;

class ConversionReportController extends Controller
{
    private $title;
    private $moduleCode;

    // 1 NEW, 2 VALIDATED, 3 APPROVED, 5 CANCELED, 8 REVISED -- persis pola Sales Order
    private $statusLabel = [
        1 => 'NEW',
        2 => 'VALIDATED',
        3 => 'APPROVED',
        5 => 'CANCELED',
        8 => 'REVISED',
    ];

    private $statusBadge = [
        1 => 'badge-primary',
        2 => 'badge-info',
        3 => 'badge-success',
        5 => 'badge-danger',
        8 => 'badge-secondary',
    ];

    public function __construct()
    {
        $this->title = 'Conversion Report';
        $this->moduleCode = 'CVR';
    }

    // =========================================================================
    //  NUMBERING
    // =========================================================================

    private function getLastCode(): string
    {
        DB::table('master_code')->where('code_key', $this->moduleCode)->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by'  => Auth::user()->username,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $newCode = DB::table('master_code')->where('code_key', $this->moduleCode)->value('code_number');
        $months  = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];

        return sprintf(
            '%s-ASN-%s-%s-%s',
            $this->moduleCode, date('Y'), $months[date('n') - 1], str_pad($newCode, 4, '0', STR_PAD_LEFT)
        );
    }

    // =========================================================================
    //  CONVERSION VALUE (dari Conversion Setting, sama seperti Price List)
    // =========================================================================

    private function activeConversionValue(): float
    {
        $conv = DB::table('conversion_setting')->where('status', '1')->orderByDesc('id')->first();
        return $conv ? (float) $conv->conversion_value : 0;
    }

    /**
 * FG dengan group_of_material MAKLON/MKL: diproses pihak luar (maklon),
 * jadi bom_det tidak dihitung sebagai biaya (dianggap tidak ada), dan
 * hasil konversinya selalu masuk Non Painting terlepas dari UOM.
 */
private function isMaklon(string $articleCode): bool
{
    $group = DB::table('article')->where('article_code', $articleCode)->value('group_of_material');
    return in_array(strtoupper(trim($group ?? '')), ['MAKLON', 'MKL']);
}

    /**
     * Avg cost artikel dari receiving_det (weighted by qty), anchor ke bulan
     * $periode/$tahun kalau diisi (mis. Conversion Report periode Juli 2026
     * -> anchor Juli 2026, BUKAN bulan berjalan saat dokumen dibuat/diedit);
     * kalau kosong mundur bulan demi bulan sampai maksimum $maxMonthsBack.
     * $periode/$tahun null -> fallback ke bulan berjalan (dipakai pemanggil
     * lain yang memang tidak terikat periode, mis. PriceListController-style
     * lookup "harga terbaru").
     */
    private function avgReceivingPrice(string $articleCode, ?int $periode = null, ?int $tahun = null, int $maxMonthsBack = 24): float
    {
        $anchor = ($periode && $tahun)
            ? \DateTime::createFromFormat('Y-n-j', "{$tahun}-{$periode}-1")
            : new \DateTime('today');

        for ($i = 0; $i <= $maxMonthsBack; $i++) {
            $monthStart = (clone $anchor)->modify("-{$i} month")->modify('first day of this month');
            $monthEnd   = (clone $monthStart)->modify('last day of this month');

            $row = DB::selectOne("
                SELECT COALESCE(SUM(d.price*d.qty)/NULLIF(SUM(d.qty),0),0) AS avg_price, COUNT(*) AS n
                FROM receiving_det d
                JOIN receiving_hdr h ON h.rec_number = d.rec_number
                WHERE d.article_code = ?
                  AND d.qty > 0 AND d.price > 0
                  AND to_date(h.rec_date,'DD-MM-YYYY') BETWEEN ?::date AND ?::date
            ", [$articleCode, $monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')]);

            if ($row && $row->n > 0) {
                return (float) $row->avg_price;
            }
        }
        return 0.0;
    }

    /**
     * Purchase price satu artikel: kalau punya BOM aktif, pakai total biaya
     * material (RM+DET) seperti Price List. Kalau tidak punya BOM (artikel
     * dijual apa adanya, bukan hasil produksi), pakai avg receiving artikel
     * itu sendiri.
     */
    private function purchasePrice(string $articleCode, ?int $periode = null, ?int $tahun = null): float
{
    $bom = DB::table('bom_hdr')
        ->where('article_code', $articleCode)
        ->where('status', '3')
        ->orderByDesc('id')
        ->first();

    // BOM lagi direvisi / belum full approved (baris utama status 1/2) -> pakai
    // snapshot revisi terakhir (status 7 = versi approved sebelumnya), bukan
    // dianggap "tanpa BOM".
    if (!$bom) {
        $bom = DB::table('bom_hdr as s')
            ->join('bom_hdr as o', 'o.bom_code', '=', 's.origin_bom_code')
            ->where('s.article_code', $articleCode)
            ->where('s.status', '7')
            ->whereIn('o.status', ['1', '2'])
            ->orderByDesc('s.num_revision')
            ->orderByDesc('s.id')
            ->select('s.bom_code')
            ->first();
    }

    if (!$bom) {
        return $this->avgReceivingPrice($articleCode, $periode, $tahun);
    }

    $rm = DB::table('bom_rm as b')
        ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
        ->where('b.bom_code', $bom->bom_code)
        ->select('b.article_code', 'a.article_type', 'b.qty')
        ->get();

    $det = $this->isMaklon($articleCode)
        ? collect()
        : DB::table('bom_det as b')
            ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
            ->where('b.bom_code', $bom->bom_code)
            ->whereIn('a.article_type', ['RMP', 'RMNP'])
            ->select('b.article_code', 'a.article_type', 'b.qty')
            ->get();

    $total = 0;
    foreach ($rm->concat($det) as $m) {
        $type = strtoupper($m->article_type ?? '');
        $qty  = (float) $m->qty;
        $price = $type === 'RMNP' ? 0 : $this->avgReceivingPrice($m->article_code, $periode, $tahun);
        $total += $price * $qty;
    }

    return $total;
}

    /**
     * Cek apakah periode+tahun sudah dipakai dokumen lain yang masih aktif.
     * CANCELED (5) dan REVISED/snapshot (8) dikecualikan -- boleh dibuat ulang
     * untuk periode yang sama kalau dokumen sebelumnya sudah dibatalkan.
     * $excludeId dipakai saat update supaya dokumen tidak bentrok dgn dirinya sendiri.
     */
    /**
     * Target konversi (painting) periode/tahun, sama persis dengan widget
     * Sales Achievement di Home. Di-snapshot ke conversion_report_hdr.target_conversion
     * saat store/update; public supaya bisa dipakai buat backfill report lama.
     */
    public function targetConversionFor(int $periode, int $tahun): float
    {
        return (float) app(\App\Http\Controllers\HomeController::class)
            ->buildSalesAchievement($periode, $tahun)['targetConversion'];
    }

    private function periodeAlreadyUsed(int $periode, int $tahun, ?int $excludeId = null): ?string
    {
        $existing = DB::table('conversion_report_hdr')
            ->where('periode', $periode)
            ->where('tahun', $tahun)
            ->whereNotIn('status', [5, 8])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->first();

        return $existing ? $existing->report_code : null;
    }

    // =========================================================================
    //  AGREGASI DELIVERY PER PERIODE (dipakai preview & store)
    // =========================================================================

    /**
     * Ambil semua baris DN (article-level) dalam periode, sudah di-join
     * harga SO + nama customer. Dipakai untuk membangun breakdown per DN
     * DAN agregat per artikel dari sumber data yang SAMA (supaya tidak
     * mungkin numbernya beda antara ringkasan & breakdown).
     */
    private function periodeBounds(int $periode, int $tahun): array
    {
        $start = sprintf('%04d-%02d-01', $tahun, $periode);
        $end   = date('Y-m-t', strtotime($start));
        return [$start, $end];
    }

    private function dnRowsForPeriod(int $periode, int $tahun)
    {
        [$start, $end] = $this->periodeBounds($periode, $tahun);

        // price_unit = sales_order_det.price + price_service (harga jual + jasa).
        // Purchase/material price (RM) TIDAK dikurangi di sini; itu baru
        // dikurangkan di level agregat lewat kolom "Conversion"
        // (avg_selling - avg_purchase)/conversion_value, lihat buildSummary().
        // price_unit/price_total di breakdown DN ini sengaja tetap angka jual
        // kotor apa adanya, supaya mencerminkan nilai transaksi DN yang sebenarnya.
        return DB::select("
            SELECT
                dd.article_code,
                a.article_alternative_code,
                a.article_desc,
                a.uom,
                dd.delivery_number AS dn_number,
                dd.so_number,
                sh.id AS so_id,
                dh.id AS delivery_id,
                dh.delivery_date,
                tp.nama AS customer_name,
                dd.qty,
                (COALESCE(sod.price, 0) + COALESCE(sod.price_service, 0)) AS price_unit,
                dd.qty * (COALESCE(sod.price, 0) + COALESCE(sod.price_service, 0)) AS price_total
            FROM delivery_det dd
            JOIN delivery_hdr dh ON dh.delivery_number = dd.delivery_number
            LEFT JOIN sales_order_det sod ON sod.so_code = dd.so_number AND sod.article_code = dd.article_code
            LEFT JOIN sales_order_hdr sh ON sh.so_code = dd.so_number
            LEFT JOIN article a ON a.article_code = dd.article_code
            LEFT JOIN third_party tp ON tp.kode = dh.customer_id
            WHERE to_date(dh.delivery_date, 'DD-MM-YYYY') BETWEEN ?::date AND ?::date
              AND dh.status NOT IN ('5','7')
            ORDER BY dd.article_code, to_date(dh.delivery_date, 'DD-MM-YYYY')
        ", [$start, $end]);
    }

    /**
     * Kelompokkan baris DN per article_code, hitung qty total, avg selling
     * (weighted by qty), dan siapkan purchase price + conversion.
     * Return: ['rows' => [...ringkasan per artikel], 'dnByArticle' => [...breakdown]]
     */
    private function buildSummary(int $periode, int $tahun): array
    {
        $dnRows = $this->dnRowsForPeriod($periode, $tahun);
        $convVal = $this->activeConversionValue();

        $grouped = [];
        foreach ($dnRows as $r) {
            $grouped[$r->article_code][] = $r;
        }

        $rows = [];
        foreach ($grouped as $articleCode => $lines) {
            $totalQty       = 0;
            $totalValue     = 0;
            $customerNames  = [];

            foreach ($lines as $l) {
                $totalQty      += (float) $l->qty;
                $totalValue    += (float) $l->price_total;
                if ($l->customer_name) $customerNames[$l->customer_name] = true;
            }

           $avgSelling  = $totalQty > 0 ? $totalValue / $totalQty : 0;
$avgPurchase = $this->purchasePrice($articleCode, $periode, $tahun);
$conversion  = $convVal > 0 ? (($avgSelling - $avgPurchase) * $totalQty) / $convVal : 0;

// Painting = artikel UOM PCS/SET; MAKLON selalu Non Painting apapun UOM-nya.
$uom        = $lines[0]->uom ?? '';
$isPainting = !$this->isMaklon($articleCode) && in_array(strtoupper(trim($uom)), ['PCS', 'SET']);

$rows[] = [
    'article_code'             => $articleCode,
    'article_alternative_code' => $lines[0]->article_alternative_code ?? $articleCode,
    'article_desc'             => $lines[0]->article_desc ?? '',
    'uom'                      => $uom,
    'customer_names'           => implode(', ', array_keys($customerNames)),
    'total_qty'                => round($totalQty, 4),
    'avg_selling_price'        => round($avgSelling, 4),
    'avg_purchase_price'       => round($avgPurchase, 4),
    'total_selling_value'      => round($totalValue, 4),
    'total_purchase_value'     => round($avgPurchase * $totalQty, 4),
    'conversion'               => round($conversion, 4),
    'is_painting'              => $isPainting,
    'conversion_painting'      => round($isPainting ? $conversion : 0, 4),
    'conversion_non_painting'  => round($isPainting ? 0 : $conversion, 4),
];
        }

        return ['rows' => $rows, 'conversionValue' => $convVal, 'dnByArticle' => $grouped];
    }

    /**
     * Hapus det/dn_det lama (kalau ada) lalu insert ulang dari hasil buildSummary().
     * Dipakai bareng oleh store() dan update() (edit sebelum approve) supaya
     * kedua jalur itu selalu menghasilkan snapshot data yang sama persis.
     */
    private function rebuildDetail(int $reportId, array $summary): void
    {
        $oldDetIds = DB::table('conversion_report_det')->where('report_id', $reportId)->pluck('id');
        if ($oldDetIds->isNotEmpty()) {
            DB::table('conversion_report_dn_det')->whereIn('report_det_id', $oldDetIds)->delete();
            DB::table('conversion_report_det')->where('report_id', $reportId)->delete();
        }

        foreach ($summary['rows'] as $row) {
            $detId = DB::table('conversion_report_det')->insertGetId([
                'report_id'          => $reportId,
                'article_code'       => $row['article_code'],
                'customer_names'     => $row['customer_names'],
                'uom'                => $row['uom'],
                'total_qty'          => $row['total_qty'],
                'avg_selling_price'  => $row['avg_selling_price'],
                'avg_purchase_price' => $row['avg_purchase_price'],
                'conversion'         => $row['conversion'],
                'created_at'         => date('Y-m-d H:i:s'),
            ]);

            $dnRows = $summary['dnByArticle'][$row['article_code']] ?? [];
            if (!empty($dnRows)) {
                DB::table('conversion_report_dn_det')->insert(array_map(function ($l) use ($detId) {
                    return [
                        'report_det_id' => $detId,
                        'dn_number'     => $l->dn_number,
                        'so_number'     => $l->so_number,
                        'customer_name' => $l->customer_name,
                        'delivery_date' => $l->delivery_date,
                        'qty'           => $l->qty,
                        'price_unit'    => $l->price_unit,
                        'price_total'   => $l->price_total,
                        'created_at'    => date('Y-m-d H:i:s'),
                    ];
                }, $dnRows));
            }
        }
    }

    /**
     * Rebuild ringkasan per artikel dari data DN yang SUDAH TERSIMPAN
     * (conversion_report_det + conversion_report_dn_det) milik satu report,
     * dibatasi ke sub-range tanggal delivery. Dipakai oleh filter tanggal di
     * halaman Show -- sengaja pakai snapshot yang tersimpan (bukan tarik
     * ulang dari delivery_det) supaya angka yang ditampilkan/di-export selalu
     * konsisten dengan dokumen yang sudah disimpan, walau data delivery live
     * sudah berubah sejak report dibuat. avg_purchase_price ikut snapshot
     * (tidak berubah per sub-range, sama seperti listDetailDn()).
     */
    private function rangeSummaryForReport(int $reportId, string $start, string $end): array
    {
        $header = DB::table('conversion_report_hdr')->where('id', $reportId)->first();
        if (!$header) {
            return ['rows' => [], 'conversionValue' => 0];
        }
        $convVal = (float) $header->conversion_value_used;

        $lines = DB::select("
            SELECT
                d.id AS det_id,
                d.article_code,
                a.article_alternative_code,
                a.article_desc,
                d.uom,
                d.avg_purchase_price,
                dn.customer_name,
                dn.qty,
                dn.price_unit,
                dn.price_total,
                dn.delivery_date
            FROM conversion_report_det d
            JOIN conversion_report_dn_det dn ON dn.report_det_id = d.id
            LEFT JOIN article a ON a.article_code = d.article_code
            WHERE d.report_id = ?
              AND to_date(dn.delivery_date, 'DD-MM-YYYY') BETWEEN ?::date AND ?::date
            ORDER BY d.article_code, to_date(dn.delivery_date, 'DD-MM-YYYY')
        ", [$reportId, $start, $end]);

        $grouped = [];
        foreach ($lines as $l) {
            $grouped[$l->article_code][] = $l;
        }

        $rows = [];
        foreach ($grouped as $articleCode => $group) {
            $totalQty      = 0;
            $totalValue    = 0;
            $conversion    = 0;
            $customerNames = [];
            $avgPurchase   = (float) $group[0]->avg_purchase_price;

            foreach ($group as $l) {
                $qty = (float) $l->qty;
                $totalQty   += $qty;
                $totalValue += (float) $l->price_total;
                $conversion += $convVal > 0 ? ((((float) $l->price_unit) - $avgPurchase) * $qty) / $convVal : 0;
                if ($l->customer_name) $customerNames[$l->customer_name] = true;
            }

           $avgSelling = $totalQty > 0 ? $totalValue / $totalQty : 0;
$uom        = $group[0]->uom ?? '';
$isPainting = !$this->isMaklon($articleCode) && in_array(strtoupper(trim($uom)), ['PCS', 'SET']);

$rows[] = [
    'det_id'                   => $group[0]->det_id,
    'article_code'             => $articleCode,
    'article_alternative_code' => $group[0]->article_alternative_code ?? $articleCode,
    'article_desc'             => $group[0]->article_desc ?? '',
    'uom'                      => $uom,
    'customer_names'           => implode(', ', array_keys($customerNames)),
    'total_qty'                => round($totalQty, 4),
    'avg_selling_price'        => round($avgSelling, 4),
    'avg_purchase_price'       => round($avgPurchase, 4),
    'total_selling_value'      => round($totalValue, 4),
    'total_purchase_value'     => round($avgPurchase * $totalQty, 4),
    'conversion'               => round($conversion, 4),
    'is_painting'              => $isPainting,
    'conversion_painting'      => round($isPainting ? $conversion : 0, 4),
    'conversion_non_painting'  => round($isPainting ? 0 : $conversion, 4),
];
        }

        return ['rows' => $rows, 'conversionValue' => $convVal];
    }

    /**
     * Clamp start/end request ke batas periode dokumen -- range tanggal di
     * halaman Show tidak boleh keluar dari bulan periode yang tersimpan.
     */
    private function clampRangeToPeriode(Request $request, $header): array
    {
        [$minDate, $maxDate] = $this->periodeBounds((int) $header->periode, (int) $header->tahun);

        $start = $request->start ?: $minDate;
        $end   = $request->end ?: $maxDate;
        if ($start < $minDate) $start = $minDate;
        if ($end > $maxDate)   $end = $maxDate;
        if ($end < $start)     $end = $start;

        return [$start, $end, $minDate, $maxDate];
    }

    /** AJAX -- dipanggil saat filter range tanggal diterapkan di halaman Show. */
    public function showRangeFilter(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $header = DB::table('conversion_report_hdr')->where('id', $id)->first();
        if (!$header) {
            return response()->json(['status' => 0, 'message' => 'Data tidak ditemukan.']);
        }

        [$start, $end, $minDate, $maxDate] = $this->clampRangeToPeriode($request, $header);

        $summary = $this->rangeSummaryForReport((int) $id, $start, $end);
        $rows = $summary['rows'];

        return response()->json([
            'status'  => 1,
            'rows'    => $rows,
            'start'   => $start,
            'end'     => $end,
            'minDate' => $minDate,
            'maxDate' => $maxDate,
            'totals'  => [
                'article'     => count($rows),
                'qty'         => round(array_sum(array_column($rows, 'total_qty')), 2),
                'conversion'  => round(array_sum(array_column($rows, 'conversion')), 2),
                'painting'    => round(array_sum(array_column($rows, 'conversion_painting')), 2),
                'nonPainting' => round(array_sum(array_column($rows, 'conversion_non_painting')), 2),
            ],
        ]);
    }

    /** Export Excel dari tabel Article Detail di halaman Show, sesuai range tanggal yang dipilih. */
    public function exportRange(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $header = DB::table('conversion_report_hdr')->where('id', $id)->first();
        if (!$header) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        [$start, $end] = $this->clampRangeToPeriode($request, $header);

        $summary = $this->rangeSummaryForReport((int) $id, $start, $end);

        $rangeTag = date('dmY', strtotime($start)).'-'.date('dmY', strtotime($end));
        $fileName = 'Conversion_Report_'.$header->report_code.'_'.$rangeTag.'.xlsx';

        return \Excel::download(new \App\Exports\ConversionReportExport($summary['rows']), $fileName);
    }

    // =========================================================================
    //  INDEX / LIST
    // =========================================================================

    public function getTableColoumn(): string
    {
        return json_encode([
            ['data' => 'action',      'name' => 'action',      'title' => 'Action', 'orderable' => false, 'searchable' => false],
            ['data' => 'report_code', 'name' => 'report_code', 'title' => 'Report Number'],
            ['data' => 'report_name', 'name' => 'report_name', 'title' => 'Name'],
            ['data' => 'periode_label','name' => 'periode_label','title' => 'Periode', 'orderable' => false, 'searchable' => false],
            ['data' => 'status_label','name' => 'status_label','title' => 'Status', 'orderable' => false, 'searchable' => false],
            ['data' => 'total_article',       'name' => 'total_article',       'title' => 'Total Article', 'orderable' => false, 'searchable' => false],
            ['data' => 'total_non_painting',  'name' => 'total_non_painting',  'title' => 'Non Painting', 'orderable' => false, 'searchable' => false],
            ['data' => 'total_painting',      'name' => 'total_painting',      'title' => 'Painting', 'orderable' => false, 'searchable' => false],
            ['data' => 'total_conversion',    'name' => 'total_conversion',    'title' => 'Total Konversi', 'orderable' => false, 'searchable' => false],
            ['data' => 'target_conversion',   'name' => 'target_conversion',   'title' => 'Target Konversi', 'orderable' => false, 'searchable' => false],
            ['data' => 'selisih_conversion',  'name' => 'selisih_conversion',  'title' => 'Selisih', 'orderable' => false, 'searchable' => false],
            ['data' => 'note',        'name' => 'note',        'title' => 'Note'],
            ['data' => 'created_by',  'name' => 'created_by',  'title' => 'Created By'],
            ['data' => 'created_at',  'name' => 'created_at',  'title' => 'Created At'],
        ], true);
    }

    public function index()
    {
        return view('conversion.conversionReport.index', [
            'title'    => $this->title,
            'subtitle' => $this->title,
            'kolom'    => $this->getTableColoumn(),
        ]);
    }

    public function list(Request $request)
    {
        $months = ['', 'January','February','March','April','May','June','July','August','September','October','November','December'];

        // status 8 (REVISED) adalah snapshot arsip revisi, bukan dokumen aktif --
        // disembunyikan dari list utama, tetap bisa dilihat lewat riwayat revisi di halaman Edit.
        // Total Article & Total Konversi per report -- agregat dari conversion_report_det,
        // di-join sebagai subquery supaya list tetap satu baris per report_hdr.
        // Painting/Non Painting dipecah dari UOM (PCS/SET = painting), persis aturan
        // yang sama dipakai buildSummary() -- conversion_report_det tidak menyimpan
        // kolom split-nya sendiri, jadi dihitung ulang di sini dari uom+conversion.
        $agg = DB::table('conversion_report_det as crd')
    ->leftJoin('article as a', 'a.article_code', '=', 'crd.article_code')
    ->select('crd.report_id',
        DB::raw('COUNT(*) as total_article'),
        DB::raw("COALESCE(SUM(CASE WHEN UPPER(TRIM(crd.uom)) IN ('PCS','SET')
                    AND COALESCE(UPPER(TRIM(a.group_of_material)),'') NOT IN ('MAKLON','MKL')
                  THEN crd.conversion ELSE 0 END),0) as total_painting"),
        DB::raw("COALESCE(SUM(CASE WHEN UPPER(TRIM(crd.uom)) IN ('PCS','SET')
                    AND COALESCE(UPPER(TRIM(a.group_of_material)),'') NOT IN ('MAKLON','MKL')
                  THEN 0 ELSE crd.conversion END),0) as total_non_painting"),
        DB::raw('COALESCE(SUM(crd.conversion),0) as total_conversion'))
    ->groupBy('crd.report_id');

        $data = DB::table('conversion_report_hdr as h')
            ->leftJoinSub($agg, 'agg', 'agg.report_id', '=', 'h.id')
            ->select('h.*',
                DB::raw('COALESCE(agg.total_article,0) as total_article'),
                DB::raw('COALESCE(agg.total_painting,0) as total_painting'),
                DB::raw('COALESCE(agg.total_non_painting,0) as total_non_painting'),
                DB::raw('COALESCE(agg.total_conversion,0) as total_conversion'),
                DB::raw('COALESCE(h.target_conversion,0) as target_conversion'))
            ->where('h.status', '!=', 8)
            ->when($request->reportCode, fn($q) => $q->where('h.report_code', 'ilike', '%'.$request->reportCode.'%'))
            ->when($request->reportName, fn($q) => $q->where('h.report_name', 'ilike', '%'.$request->reportName.'%'))
            ->when($request->tahun, fn($q) => $q->where('h.tahun', $request->tahun))
            ->orderByDesc('h.id')
            ->get();

        return Datatables::of($data)
            ->editColumn('total_painting', fn($d) => number_format((float) $d->total_painting, 2))
            ->editColumn('total_non_painting', fn($d) => number_format((float) $d->total_non_painting, 2))
            ->editColumn('total_conversion', fn($d) => number_format((float) $d->total_conversion, 2))
            ->editColumn('target_conversion', fn($d) => number_format((float) $d->target_conversion, 2))
            // Selisih = Konversi Painting - Target Konversi (target hanya painting; dihitung, tidak disimpan); merah kalau di bawah target.
            ->addColumn('selisih_conversion', function ($d) {
                $s = (float) $d->total_painting - (float) $d->target_conversion;
                $cls = $s < 0 ? 'text-danger' : 'text-success';
                return "<span class='$cls'>".number_format($s, 2).'</span>';
            })
            ->addColumn('action', function ($d) {
                $id = Crypt::encryptString($d->id);
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">';
                $buttons .= "<a href='".route('conversionReport.show', ['id' => $id])."' class='dropdown-item'>
                                <i data-feather='eye'></i> Detail</a>";

                // NEW (1)  -> Edit + Delete
                // VALIDATED/APPROVED (2,3) -> Revisi + Cancel
                if ($d->status == 1) {
                    $buttons .= "<a href='".route('conversionReport.edit', ['id' => $id])."' class='dropdown-item'>
                                    <i data-feather='edit'></i> Edit</a>";
                    $buttons .= "<a href='javascript:;' onclick='deleteReport(\"$id\",\"$d->report_code\")' class='dropdown-item'>
                                    <i data-feather='trash-2' class='feather-14-red'></i> Delete</a>";
                }
                if (in_array($d->status, [2, 3])) {
                    $buttons .= "<a href='".route('conversionReport.edit', ['id' => $id])."' class='dropdown-item'>
                                    <i data-feather='rotate-ccw'></i> Revisi</a>";
                    $buttons .= "<a href='javascript:;' onclick='cancelReport(\"$id\",\"$d->report_code\")' class='dropdown-item'>
                                    <i data-feather='slash'></i> Cancel</a>";
                }
                $buttons .= '</div></div>';
                return $buttons;
            })
            ->addColumn('periode_label', function ($d) use ($months) {
                return ($months[$d->periode] ?? $d->periode).' '.$d->tahun;
            })
            ->addColumn('status_label', function ($d) {
                $label = $this->statusLabel[$d->status] ?? $d->status;
                $class = $this->statusBadge[$d->status] ?? 'badge-secondary';
                return "<div class='badge badge-pill $class'>$label</div>";
            })
            ->rawColumns(['action', 'status_label', 'selisih_conversion'])
            ->make(true);
    }

    /**
     * Data chart kurva konversi per bulan untuk tahun yang difilter.
     * Report berstatus CANCELED (5) / REVISED (8) tidak ikut dihitung --
     * hanya dokumen aktif yang merepresentasikan konversi riil.
     * Bulan yang tidak punya Conversion Report tetap tampil dengan nilai 0.
     */
    public function chart(Request $request)
    {
        $tahun = (int) ($request->tahun ?: date('Y'));

        $perArticle = DB::table('conversion_report_hdr as h')
            ->leftJoin('conversion_report_det as d', 'd.report_id', '=', 'h.id')
            ->where('h.tahun', $tahun)
            ->whereNotIn('h.status', [5, 8])
            ->select('h.periode',
                DB::raw('COUNT(d.id) as total_article'),
                DB::raw('COALESCE(SUM(d.conversion),0) as total_conversion'))
            ->groupBy('h.periode')
            ->get()
            ->keyBy('periode');

        // Total Delivery = jumlah DN unik (bisa dipakai lintas artikel) yang menyusun
        // report bulan itu -- di-query terpisah supaya join ke dn_det tidak
        // menggandakan (fan-out) SUM(d.conversion) di atas.
        $perDelivery = DB::table('conversion_report_hdr as h')
            ->join('conversion_report_det as d', 'd.report_id', '=', 'h.id')
            ->join('conversion_report_dn_det as dn', 'dn.report_det_id', '=', 'd.id')
            ->where('h.tahun', $tahun)
            ->whereNotIn('h.status', [5, 8])
            ->select('h.periode', DB::raw('COUNT(DISTINCT dn.dn_number) as total_delivery'))
            ->groupBy('h.periode')
            ->get()
            ->keyBy('periode');

        $monthLabels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        $labels = $totalArticle = $totalDelivery = $totalConversion = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[]         = $monthLabels[$m - 1];
            $totalArticle[]   = (int) ($perArticle[$m]->total_article ?? 0);
            $totalConversion[]= round((float) ($perArticle[$m]->total_conversion ?? 0), 2);
            $totalDelivery[]  = (int) ($perDelivery[$m]->total_delivery ?? 0);
        }

        return response()->json([
            'tahun'           => $tahun,
            'labels'          => $labels,
            'totalArticle'    => $totalArticle,
            'totalDelivery'   => $totalDelivery,
            'totalConversion' => $totalConversion,
            'sumArticle'      => array_sum($totalArticle),
            'sumDelivery'     => array_sum($totalDelivery),
            'sumConversion'   => round(array_sum($totalConversion), 2),
            'minYear'         => 2024,
        ]);
    }

    // =========================================================================
    //  CREATE
    // =========================================================================

    public function create()
    {
        return view('conversion.conversionReport.create', [
            'title'    => "Create {$this->title}",
            'subtitle' => "Create {$this->title}",
        ]);
    }

    /** AJAX -- dipanggil saat periode dipilih di form Create/Edit, live preview. */
    public function previewPeriod(Request $request)
    {
        $periode = (int) $request->periode;
        $tahun   = (int) $request->tahun;
        // excludeId dikirim halaman Edit supaya dokumen yang sedang diedit tidak
        // dianggap bentrok dengan periode/tahun dirinya sendiri.
        $excludeId = null;
        if ($request->excludeId) {
            try {
                $excludeId = (int) Crypt::decryptString($request->excludeId);
            } catch (\Exception $e) {
                $excludeId = null;
            }
        }

        if (!$periode || !$tahun) {
            return response()->json(['status' => 0, 'message' => 'Periode belum lengkap.']);
        }

        $duplicateOf = $this->periodeAlreadyUsed($periode, $tahun, $excludeId);
        if ($duplicateOf) {
            return response()->json([
                'status'  => 0,
                'message' => "Periode ini sudah dipakai dokumen {$duplicateOf}. Satu periode hanya boleh punya satu Conversion Report aktif.",
                'duplicate' => true,
            ]);
        }

        $summary = $this->buildSummary($periode, $tahun);

        // dnByArticle dikirim juga supaya modal "info" di halaman Create bisa
        // menampilkan breakdown DN sebelum dokumennya disimpan (belum ada
        // report_det_id buat dipanggil lewat listDetailDn()).
        $dnByArticle = [];
        foreach ($summary['dnByArticle'] as $articleCode => $lines) {
            $dnByArticle[$articleCode] = array_map(fn($l) => [
                'dn_number'     => $l->dn_number,
                'dn_url'        => $l->delivery_id ? route('delivery.show', ['id' => Crypt::encryptString($l->delivery_id)]) : null,
                'so_number'     => $l->so_number,
                'so_url'        => $l->so_id ? route('salesOrder.show', ['id' => Crypt::encryptString($l->so_id)]) : null,
                'customer_name' => $l->customer_name,
                'delivery_date' => $l->delivery_date,
                'qty'           => (float) $l->qty,
                'price_unit'    => (float) $l->price_unit,
                'price_total'   => (float) $l->price_total,
            ], $lines);
        }

        return response()->json([
            'status' => 1,
            'rows'   => $summary['rows'],
            'conversionValue' => $summary['conversionValue'],
            'dnByArticle' => $dnByArticle,
        ]);
    }

    /** Export Excel dari preview periode (dipanggil setelah data delivery ditarik di halaman Create). */
    public function exportPreview(Request $request)
    {
        $periode = (int) $request->periode;
        $tahun   = (int) $request->tahun;

        if (!$periode || !$tahun) {
            return redirect()->back()->with('error', 'Periode belum lengkap.');
        }

        $summary = $this->buildSummary($periode, $tahun);
        $months  = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
        $fileName = 'Conversion_Report_'.($months[$periode] ?? $periode).'_'.$tahun.'_'.date('Ymd_His').'.xlsx';

        return \Excel::download(new \App\Exports\ConversionReportExport($summary['rows']), $fileName);
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $periode  = (int) $request->periode;
        $tahun    = (int) $request->tahun;

        if (!$request->reportName || !$periode || !$tahun) {
            return redirect()->back()->withInput()->with('error', 'Nama, Periode, dan Tahun wajib diisi.');
        }

        if ($duplicateOf = $this->periodeAlreadyUsed($periode, $tahun)) {
            return redirect()->back()->withInput()->with('error', "Periode ini sudah dipakai dokumen {$duplicateOf}. Satu periode hanya boleh punya satu Conversion Report aktif.");
        }

        // Dihitung ULANG di server saat submit (bukan percaya payload JS),
        // supaya angka yang tersimpan selalu berdasarkan data delivery
        // TERKINI di periode itu -- konsisten dengan preview yang barusan
        // dilihat user, dan tidak bisa dimanipulasi dari client.
        $summary = $this->buildSummary($periode, $tahun);

        if (empty($summary['rows'])) {
            return redirect()->back()->withInput()->with('error', 'Tidak ada data Delivery pada periode tersebut.');
        }

        DB::beginTransaction();
        try {
            $reportCode = $this->getLastCode();

            $reportId = DB::table('conversion_report_hdr')->insertGetId([
                'report_code'           => $reportCode,
                'report_name'           => $request->reportName,
                'periode'               => $periode,
                'tahun'                 => $tahun,
                'note'                  => $request->note,
                'conversion_value_used' => $summary['conversionValue'],
                'target_conversion'     => $this->targetConversionFor($periode, $tahun),
                'status'                => 1,
                'num_revision'          => 0,
                'created_by'            => $username,
                'updated_by'            => $username,
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);

            $this->rebuildDetail($reportId, $summary);

            DB::commit();
            \LogActivity::addToLog("Create {$this->title}", "username: {$username} created {$reportCode}");
            return redirect()->route('conversionReport.index')->with('success', "{$reportCode} berhasil dibuat.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Gagal membuat Conversion Report: '.$e->getMessage());
        }
    }

    // =========================================================================
    //  SHOW
    // =========================================================================

    public function show(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $header = DB::table('conversion_report_hdr')->where('id', $id)->first();
        if (!$header) {
            return redirect()->route('conversionReport.index')->with('error', 'Data tidak ditemukan.');
        }

        $months = ['', 'January','February','March','April','May','June','July','August','September','October','November','December'];

       $details = DB::table('conversion_report_det as d')
    ->leftJoin('article as a', 'a.article_code', '=', 'd.article_code')
    ->where('d.report_id', $id)
    ->select('d.*', 'a.article_alternative_code', 'a.article_desc')
    ->orderBy('d.article_code')
    ->get()
    ->map(function ($d) {
        $d->is_painting = !$this->isMaklon($d->article_code)
            && in_array(strtoupper(trim($d->uom)), ['PCS', 'SET']);
        return $d;
    });

        $username = Auth::user()->username;
        [$periodeStart, $periodeEnd] = $this->periodeBounds((int) $header->periode, (int) $header->tahun);

        return view('conversion.conversionReport.show', [
            'title'           => "Detail {$this->title}",
            'subtitle'        => "Detail {$this->title}",
            'header'          => $header,
            'periodeLabel'    => ($months[$header->periode] ?? $header->periode).' '.$header->tahun,
            'statusLabel'     => $this->statusLabel[$header->status] ?? $header->status,
            'details'         => $details,
            'id'              => $request->id,
            'periodeStart'    => $periodeStart,
            'periodeEnd'      => $periodeEnd,
            'approvalHistory' => Approval::approvalHistory($this->moduleCode, $header->report_code, $username),
        ]);
    }

    // =========================================================================
    //  EDIT / UPDATE / APPROVE (satu endpoint, dispatcher -- pola Sales Order)
    // =========================================================================

    public function edit(Request $request)
    {
        $id = Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        $header = DB::table('conversion_report_hdr')->where('id', $id)->first();
        if (!$header) {
            return redirect()->route('conversionReport.index')->with('error', 'Data tidak ditemukan.');
        }
        if ($header->status == 5) {
            return redirect()->route('conversionReport.index')->with('error', 'Data sudah CANCELED, tidak bisa diedit.');
        }
        if ($header->status == 8) {
            return redirect()->route('conversionReport.index')->with('error', 'Ini snapshot hasil revisi, tidak bisa diedit langsung.');
        }

        $months = ['', 'January','February','March','April','May','June','July','August','September','October','November','December'];

       $details = DB::table('conversion_report_det as d')
    ->leftJoin('article as a', 'a.article_code', '=', 'd.article_code')
    ->where('d.report_id', $id)
    ->select('d.*', 'a.article_alternative_code', 'a.article_desc')
    ->orderBy('d.article_code')
    ->get()
    ->map(function ($d) {
        $d->is_painting = !$this->isMaklon($d->article_code)
            && in_array(strtoupper(trim($d->uom)), ['PCS', 'SET']);
        return $d;
    });

        // riwayat revisi: semua baris (termasuk yang sekarang) yang berbagi
        // origin_report_code yang sama -- persis pola pengelompokan SO.
        $originCode = $header->origin_report_code ?: $header->report_code;
        $revisions = DB::table('conversion_report_hdr')
            ->where(function ($q) use ($originCode) {
                $q->where('report_code', $originCode)->orWhere('origin_report_code', $originCode);
            })
            ->where('status', 8)
            ->orderByDesc('num_revision')
            ->get();

        return view('conversion.conversionReport.edit', [
            'title'            => "Edit {$this->title}",
            'subtitle'         => "Edit {$this->title}",
            'header'           => $header,
            'details'          => $details,
            'revisions'        => $revisions,
            'periodeLabel'     => ($months[$header->periode] ?? $header->periode).' '.$header->tahun,
            'statusLabel'      => $this->statusLabel[$header->status] ?? $header->status,
            'id'               => $request->id,
            // NEW (1) -> boleh Edit (siapa saja yang bisa buka halaman ini, bukan
            // cuma pembuatnya -- selaras dengan tombol Edit di menu index yang
            // juga tidak membatasi berdasarkan created_by).
            // VALIDATED/APPROVED (2,3) -> boleh Revisi & Cancel.
            'canEdit'          => $header->status == 1,
            'canRevise'        => in_array($header->status, [2, 3]),
            'canCancel'        => in_array($header->status, [2, 3]),
            'approvalHistory'  => Approval::approvalHistory($this->moduleCode, $header->report_code, $username),
            'approveValidate'  => Approval::approveValidate($this->moduleCode, $header->report_code, $username),
        ]);
    }

    /**
     * Dispatcher: statusSimpan == 'approve' -> catat approval_history & naikkan
     * status (persis SalesOrderController::update()); selain itu -> update biasa
     * (nama/periode/tahun/note), data delivery ditarik ulang dari periode terkini.
     */
    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $statusSimpan = $request->statusSimpan;
        $approveLevel = $request->approveLevel;

        $header = DB::table('conversion_report_hdr')->where('id', $id)->first();
        if (!$header) {
            return response()->json(['status' => 0, 'title' => "Update {$this->title}", 'message' => ['Data tidak ditemukan.'], 'alert' => 'error']);
        }

        if ($statusSimpan == 'approve') {
            $maxApproval = DB::table('approval_master')->where('module_code', $this->moduleCode)->value('approval_number');
            $status = $maxApproval == $approveLevel ? 3 : 2;

            DB::beginTransaction();
            try {
                DB::table('conversion_report_hdr')->where('id', $id)->update([
                    'status'     => $status,
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('approval_history')->insert([
                    'module_code'    => $this->moduleCode,
                    'module_number'  => $header->report_code,
                    'username'       => $username,
                    'approval_order' => $approveLevel,
                    'approval_date'  => date('Y-m-d'),
                    'status'         => 1,
                    'created_by'     => $username,
                    'updated_by'     => $username,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);

                DB::commit();
                $message = "{$header->report_code} berhasil di-approve (level {$approveLevel}).";
                \LogActivity::addToLog("Approve {$this->title}", "username: {$username} | {$message}");
                return response()->json(['status' => 1, 'title' => "Approve {$this->title}", 'message' => $message, 'alert' => 'success']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 0, 'title' => "Approve {$this->title}", 'message' => ['Gagal approve: '.$e->getMessage()], 'alert' => 'error']);
            }
        }

        // ── update biasa (bukan approve) -- hanya dokumen NEW yang boleh diedit ──
        if ($header->status != 1) {
            return response()->json(['status' => 0, 'title' => "Update {$this->title}", 'message' => ['Data ini tidak bisa diedit.'], 'alert' => 'error']);
        }

        $periode = (int) $request->periode;
        $tahun   = (int) $request->tahun;
        if (!$request->reportName || !$periode || !$tahun) {
            return response()->json(['status' => 0, 'title' => "Update {$this->title}", 'message' => ['Nama, Periode, dan Tahun wajib diisi.'], 'alert' => 'error']);
        }

        if ($duplicateOf = $this->periodeAlreadyUsed($periode, $tahun, $id)) {
            return response()->json(['status' => 0, 'title' => "Update {$this->title}", 'message' => ["Periode ini sudah dipakai dokumen {$duplicateOf}. Satu periode hanya boleh punya satu Conversion Report aktif."], 'alert' => 'error']);
        }

        // Rebuild TOTAL dari data delivery TERKINI di periode ini -- inilah
        // "recalculate": kalau user tarik ulang di tanggal lain (data delivery
        // sudah berubah/bertambah sejak draft awal dibuat), angka yang tersimpan
        // otomatis mengikuti data riil terbaru, bukan snapshot lama.
        $summary = $this->buildSummary($periode, $tahun);
        if (empty($summary['rows'])) {
            return response()->json(['status' => 0, 'title' => "Update {$this->title}", 'message' => ['Tidak ada data Delivery pada periode tersebut.'], 'alert' => 'error']);
        }

        DB::beginTransaction();
        try {
            DB::table('conversion_report_hdr')->where('id', $id)->update([
                'report_name'           => $request->reportName,
                'periode'               => $periode,
                'tahun'                 => $tahun,
                'note'                  => $request->note,
                'conversion_value_used' => $summary['conversionValue'],
                'target_conversion'     => $this->targetConversionFor($periode, $tahun),
                'updated_by'            => $username,
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);

            $this->rebuildDetail($id, $summary);

            DB::commit();
            $message = "{$header->report_code} berhasil diperbarui.";
            \LogActivity::addToLog("Update {$this->title}", "username: {$username} | {$message}");
            return response()->json(['status' => 1, 'title' => "Update {$this->title}", 'message' => $message, 'alert' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'title' => "Update {$this->title}", 'message' => ['Gagal update: '.$e->getMessage()], 'alert' => 'error']);
        }
    }

    /**
     * Revisi dokumen yang sudah VALIDATED/APPROVED -- snapshot baris sekarang
     * (hdr+det+dn_det) ke report_code baru berstatus REVISED (8), lalu reset
     * baris asli ke status 1 (NEW) supaya bisa diedit & di-approve ulang.
     * Persis pola SalesOrderController::revision().
     */
    public function revision(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $reason = trim((string) $request->reason);

        $header = DB::table('conversion_report_hdr')->where('id', $id)->first();
        if (!$header) {
            return redirect()->back()->with(['alert' => 'warning', 'message' => 'Data tidak ditemukan.']);
        }
        if (!in_array($header->status, [2, 3])) {
            return redirect()->back()->with(['alert' => 'warning', 'message' => 'Dokumen ini tidak bisa direvisi pada status sekarang.']);
        }
        if ($reason === '') {
            return redirect()->back()->with(['alert' => 'warning', 'message' => 'Alasan revisi harus diisi.']);
        }

        $origin = $header->report_code;
        $numRevision = ((int) $request->nR) ? ((int) $request->nR) + 1 : 1;
        $newCode = $origin.'-R'.$numRevision;
        if (DB::table('conversion_report_hdr')->where('report_code', $newCode)->exists()) {
            $numRevision++;
            $newCode = $origin.'-R'.$numRevision;
        }

        DB::beginTransaction();
        try {
            $newId = DB::table('conversion_report_hdr')->insertGetId([
                'report_code'           => $newCode,
                'origin_report_code'    => $header->origin_report_code ?: $origin,
                'report_name'           => $header->report_name,
                'periode'               => $header->periode,
                'tahun'                 => $header->tahun,
                'note'                  => $header->note,
                'conversion_value_used' => $header->conversion_value_used,
                'target_conversion'     => $header->target_conversion,
                'status'                => 8,
                'num_revision'          => $numRevision,
                'reason'                => $reason,
                'revised_by'            => $username,
                'revised_at'            => date('Y-m-d H:i:s'),
                'created_by'            => $header->created_by,
                'updated_by'            => $username,
                'created_at'            => $header->created_at,
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);

            // salin det + dn_det ke id baru
            $oldDets = DB::table('conversion_report_det')->where('report_id', $id)->get();
            foreach ($oldDets as $det) {
                $newDetId = DB::table('conversion_report_det')->insertGetId([
                    'report_id'          => $newId,
                    'article_code'       => $det->article_code,
                    'customer_names'     => $det->customer_names,
                    'uom'                => $det->uom,
                    'total_qty'          => $det->total_qty,
                    'avg_selling_price'  => $det->avg_selling_price,
                    'avg_purchase_price' => $det->avg_purchase_price,
                    'conversion'         => $det->conversion,
                    'created_at'         => date('Y-m-d H:i:s'),
                ]);

                $dnRows = DB::table('conversion_report_dn_det')->where('report_det_id', $det->id)->get();
                if ($dnRows->isNotEmpty()) {
                    DB::table('conversion_report_dn_det')->insert($dnRows->map(function ($dn) use ($newDetId) {
                        return [
                            'report_det_id' => $newDetId,
                            'dn_number'     => $dn->dn_number,
                            'so_number'     => $dn->so_number,
                            'customer_name' => $dn->customer_name,
                            'delivery_date' => $dn->delivery_date,
                            'qty'           => $dn->qty,
                            'price_unit'    => $dn->price_unit,
                            'price_total'   => $dn->price_total,
                            'created_at'    => date('Y-m-d H:i:s'),
                        ];
                    })->all());
                }
            }

            // reset dokumen asli supaya bisa diedit & di-approve ulang dari awal
            DB::table('conversion_report_hdr')->where('id', $id)->update([
                'num_revision' => $numRevision,
                'status'       => 1,
                'revised_by'   => $username,
                'revised_at'   => date('Y-m-d H:i:s'),
                'updated_by'   => $username,
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            // arsipkan approval_history lama ke bawah report_code snapshot
            DB::table('approval_history')
                ->where('module_code', $this->moduleCode)
                ->where('module_number', $origin)
                ->update([
                    'module_number' => $newCode,
                    'status'        => 0,
                    'updated_by'    => $username,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

            DB::commit();
            \LogActivity::addToLog("Revisi {$this->title}", "username: {$username} | Revisi {$origin} to {$newCode}");
            return redirect()->route('conversionReport.edit', ['id' => Crypt::encryptString($id)])
                ->with(['alert' => 'success', 'message' => "Revisi {$origin} ke {$newCode} berhasil disimpan."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with(['alert' => 'warning', 'message' => 'Gagal menyimpan revisi: '.$e->getMessage()]);
        }
    }

    // =========================================================================
    //  CANCEL
    // =========================================================================

    public function cancel(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);
        $reason = trim((string) $request->reason);

        $header = DB::table('conversion_report_hdr')->where('id', $id)->first();
        if (!$header) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }
        if (!in_array($header->status, [2, 3])) {
            return redirect()->back()->with('error', 'Hanya dokumen VALIDATED/APPROVED yang bisa di-cancel.');
        }
        if ($reason === '') {
            return redirect()->back()->with('error', 'Alasan cancel harus diisi.');
        }

        DB::table('conversion_report_hdr')->where('id', $id)->update([
            'status'        => 5,
            'cancel_reason' => $reason,
            'canceled_by'   => $username,
            'canceled_at'   => date('Y-m-d H:i:s'),
            'updated_by'    => $username,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        \LogActivity::addToLog("Cancel {$this->title}", "username: {$username} canceled {$header->report_code}");
        return redirect()->route('conversionReport.index')->with('success', "{$header->report_code} berhasil di-cancel.");
    }

    /**
     * AJAX -- isi modal "info" per baris artikel: breakdown DN (JSON polos,
     * dirender manual di client biar bisa ada link SO + baris total).
     * Konversi per DN = ((price_unit - avg_purchase) * qty) / conversion_value;
     * di-SUM seluruh DN = konversi artikel.
     */
    public function listDetailDn(Request $request)
    {
        $detId = $request->reportDetId;

        $det = DB::table('conversion_report_det as d')
            ->join('conversion_report_hdr as h', 'h.id', '=', 'd.report_id')
            ->where('d.id', $detId)
            ->select('d.avg_purchase_price', 'd.uom', 'h.conversion_value_used')
            ->first();

        $avgPurchase = (float) ($det->avg_purchase_price ?? 0);
        $convVal     = (float) ($det->conversion_value_used ?? 0);

        $data = DB::table('conversion_report_dn_det')
            ->where('report_det_id', $detId)
            ->orderByRaw("to_date(delivery_date, 'DD-MM-YYYY')")
            ->get();

        // Cache lookup id delivery/SO supaya tidak query berulang untuk nomor sama.
        $dnIds = [];
        $soIds = [];
        $rows  = [];
        $tQty = 0; $tPrice = 0; $tConv = 0;

        foreach ($data as $i => $d) {
            $conv = $convVal > 0
                ? ((((float) $d->price_unit) - $avgPurchase) * ((float) $d->qty)) / $convVal
                : 0;

            if ($d->dn_number && !array_key_exists($d->dn_number, $dnIds)) {
                $dnIds[$d->dn_number] = DB::table('delivery_hdr')->where('delivery_number', $d->dn_number)->value('id');
            }
            if ($d->so_number && !array_key_exists($d->so_number, $soIds)) {
                $soIds[$d->so_number] = DB::table('sales_order_hdr')->where('so_code', $d->so_number)->value('id');
            }
            $dnId = $d->dn_number ? ($dnIds[$d->dn_number] ?? null) : null;
            $soId = $d->so_number ? ($soIds[$d->so_number] ?? null) : null;

            $rows[] = [
                'no'            => $i + 1,
                'dn_number'     => $d->dn_number,
                'dn_url'        => $dnId ? route('delivery.show', ['id' => Crypt::encryptString($dnId)]) : null,
                'so_number'     => $d->so_number,
                'so_url'        => $soId ? route('salesOrder.show', ['id' => Crypt::encryptString($soId)]) : null,
                'customer_name' => $d->customer_name,
                'qty'           => (float) $d->qty,
                'price_unit'    => (float) $d->price_unit,
                'price_total'   => (float) $d->price_total,
                'conversion'    => round($conv, 4),
            ];

            $tQty   += (float) $d->qty;
            $tPrice += (float) $d->price_total;
            $tConv  += $conv;
        }

        return response()->json([
            'status' => 1,
            'rows'   => $rows,
            'totals' => [
                'qty'         => round($tQty, 4),
                'price_total' => round($tPrice, 4),
                'conversion'  => round($tConv, 4),
            ],
        ]);
    }

    // =========================================================================
    //  DELETE
    // =========================================================================

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id = Crypt::decryptString($request->id);

        $header = DB::table('conversion_report_hdr')->where('id', $id)->first();
        if (!$header) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }
        if ($header->status != 1) {
            return redirect()->back()->with('error', 'Hanya dokumen berstatus NEW yang bisa dihapus.');
        }
        if ($header->created_by !== $username && !Auth::user()->can('conversionReport-delete')) {
            return redirect()->back()->with('error', 'Anda tidak berwenang menghapus data ini.');
        }

        DB::table('conversion_report_hdr')->where('id', $id)->delete();

        \LogActivity::addToLog("Delete {$this->title}", "username: {$username} deleted {$header->report_code}");
        return redirect()->route('conversionReport.index')->with('success', "{$header->report_code} berhasil dihapus.");
    }
}
