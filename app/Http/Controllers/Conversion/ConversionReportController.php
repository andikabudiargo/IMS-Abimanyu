<?php

namespace App\Http\Controllers\Conversion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DataTables;
use DB;

class ConversionReportController extends Controller
{
    private $title;
    private $moduleCode;

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
     * Avg cost artikel dari receiving_det (weighted by qty), bulan berjalan;
     * kalau kosong mundur bulan demi bulan sampai maksimum $maxMonthsBack.
     * Sama persis dengan PriceListController::avgPrice() -- disengaja
     * disalin, bukan di-share, supaya kedua modul independen.
     */
    private function avgReceivingPrice(string $articleCode, int $maxMonthsBack = 24): float
    {
        for ($i = 0; $i <= $maxMonthsBack; $i++) {
            $row = DB::selectOne("
                SELECT COALESCE(SUM(price*qty)/NULLIF(SUM(qty),0),0) AS avg_price, COUNT(*) AS n
                FROM receiving_det
                WHERE article_code = ?
                  AND date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE - (? || ' months')::interval)
            ", [$articleCode, $i]);

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
    private function purchasePrice(string $articleCode): float
    {
        $bom = DB::table('bom_hdr')
            ->where('article_code', $articleCode)
            ->where('status', '!=', '5')
            ->orderByDesc('id')
            ->first();

        if (!$bom) {
            return $this->avgReceivingPrice($articleCode);
        }

        $rm = DB::table('bom_rm as b')
            ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
            ->where('b.bom_code', $bom->bom_code)
            ->select('b.article_code', 'a.article_type', 'b.qty')
            ->get();

        $det = DB::table('bom_det as b')
            ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
            ->where('b.bom_code', $bom->bom_code)
            ->whereIn('a.article_type', ['RMP', 'RMNP'])
            ->select('b.article_code', 'a.article_type', 'b.qty')
            ->get();

        $total = 0;
        foreach ($rm->concat($det) as $m) {
            $type = strtoupper($m->article_type ?? '');
            $qty  = (float) $m->qty;
            $price = $type === 'RMNP' ? 0 : $this->avgReceivingPrice($m->article_code);
            $total += $price * $qty;
        }

        return $total;
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
    private function dnRowsForPeriod(int $periode, int $tahun)
    {
        $start = sprintf('%04d-%02d-01', $tahun, $periode);
        $end   = date('Y-m-t', strtotime($start));

        return DB::select("
            SELECT
                dd.article_code,
                a.article_alternative_code,
                a.article_desc,
                a.uom,
                dd.delivery_number AS dn_number,
                dd.so_number,
                dh.delivery_date,
                tp.nama AS customer_name,
                dd.qty,
                COALESCE(sod.price, 0) AS price_unit,
                dd.qty * COALESCE(sod.price, 0) AS price_total
            FROM delivery_det dd
            JOIN delivery_hdr dh ON dh.delivery_number = dd.delivery_number
            LEFT JOIN sales_order_det sod ON sod.so_code = dd.so_number AND sod.article_code = dd.article_code
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
            $avgPurchase = $this->purchasePrice($articleCode);
            $conversion  = $convVal > 0 ? ($avgSelling - $avgPurchase) / $convVal : 0;

            $rows[] = [
                'article_code'             => $articleCode,
                'article_alternative_code' => $lines[0]->article_alternative_code ?? $articleCode,
                'article_desc'             => $lines[0]->article_desc ?? '',
                'uom'                      => $lines[0]->uom ?? '',
                'customer_names'           => implode(', ', array_keys($customerNames)),
                'total_qty'                => round($totalQty, 4),
                'avg_selling_price'        => round($avgSelling, 4),
                'avg_purchase_price'       => round($avgPurchase, 4),
                'conversion'               => round($conversion, 4),
            ];
        }

        return ['rows' => $rows, 'conversionValue' => $convVal, 'dnByArticle' => $grouped];
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

        $data = DB::table('conversion_report_hdr')
            ->select('*')
            ->when($request->reportCode, fn($q) => $q->where('report_code', 'ilike', '%'.$request->reportCode.'%'))
            ->when($request->reportName, fn($q) => $q->where('report_name', 'ilike', '%'.$request->reportName.'%'))
            ->when($request->tahun, fn($q) => $q->where('tahun', $request->tahun))
            ->orderByDesc('id')
            ->get();

        return Datatables::of($data)
            ->addColumn('action', function ($d) {
                $id = Crypt::encryptString($d->id);
                $buttons = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">';
                $buttons .= "<a href='".route('conversionReport.show', ['id' => $id])."' class='dropdown-item'>
                                <i data-feather='eye'></i> Detail</a>";
                $buttons .= "<a href='javascript:;' onclick='deleteReport(\"$id\",\"$d->report_code\")' class='dropdown-item'>
                                <i data-feather='trash-2' class='feather-14-red'></i> Delete</a>";
                $buttons .= '</div></div>';
                return $buttons;
            })
            ->addColumn('periode_label', function ($d) use ($months) {
                return ($months[$d->periode] ?? $d->periode).' '.$d->tahun;
            })
            ->rawColumns(['action'])
            ->make(true);
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

    /** AJAX -- dipanggil saat periode dipilih di form Create, live preview. */
    public function previewPeriod(Request $request)
    {
        $periode = (int) $request->periode;
        $tahun   = (int) $request->tahun;

        if (!$periode || !$tahun) {
            return response()->json(['status' => 0, 'message' => 'Periode belum lengkap.']);
        }

        $summary = $this->buildSummary($periode, $tahun);

        // dnByArticle dikirim juga supaya modal "info" di halaman Create bisa
        // menampilkan breakdown DN sebelum dokumennya disimpan (belum ada
        // report_det_id buat dipanggil lewat listDetailDn()).
        $dnByArticle = [];
        foreach ($summary['dnByArticle'] as $articleCode => $lines) {
            $dnByArticle[$articleCode] = array_map(fn($l) => [
                'dn_number'     => $l->dn_number,
                'so_number'     => $l->so_number,
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

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $periode  = (int) $request->periode;
        $tahun    = (int) $request->tahun;

        if (!$request->reportName || !$periode || !$tahun) {
            return redirect()->back()->withInput()->with('error', 'Nama, Periode, dan Tahun wajib diisi.');
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
                'created_by'            => $username,
                'updated_by'            => $username,
                'created_at'            => date('Y-m-d H:i:s'),
                'updated_at'            => date('Y-m-d H:i:s'),
            ]);

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
            ->get();

        return view('conversion.conversionReport.show', [
            'title'        => "Detail {$this->title}",
            'subtitle'     => "Detail {$this->title}",
            'header'       => $header,
            'periodeLabel' => ($months[$header->periode] ?? $header->periode).' '.$header->tahun,
            'details'      => $details,
            'id'           => $request->id,
        ]);
    }

    /** AJAX -- isi modal "info" per baris artikel: breakdown DN. */
    public function listDetailDn(Request $request)
    {
        $detId = $request->reportDetId;

        $data = DB::table('conversion_report_dn_det')
            ->where('report_det_id', $detId)
            ->orderByRaw("to_date(delivery_date, 'DD-MM-YYYY')")
            ->get();

        return Datatables::of($data)
            ->addColumn('dn_number_link', function ($d) {
                if (!$d->dn_number) return '-';
                $encId = DB::table('delivery_hdr')->where('delivery_number', $d->dn_number)->value('id');
                if (!$encId) return $d->dn_number;
                $url = route('delivery.show', ['id' => Crypt::encryptString($encId)]);
                return "<a href='{$url}' target='_blank'>{$d->dn_number}</a>";
            })
            ->rawColumns(['dn_number_link'])
            ->make(true);
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
        if ($header->created_by !== $username && !Auth::user()->can('conversionReport-delete')) {
            return redirect()->back()->with('error', 'Anda tidak berwenang menghapus data ini.');
        }

        DB::table('conversion_report_hdr')->where('id', $id)->delete();

        \LogActivity::addToLog("Delete {$this->title}", "username: {$username} deleted {$header->report_code}");
        return redirect()->route('conversionReport.index')->with('success', "{$header->report_code} berhasil dihapus.");
    }
}
