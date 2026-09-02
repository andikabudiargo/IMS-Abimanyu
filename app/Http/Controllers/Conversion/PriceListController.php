<?php

namespace App\Http\Controllers\Conversion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use DataTables;
use DB;

class PriceListController extends Controller
{
    private $title;

    public function __construct()
    {
        $this->title = "Price List";
    }

    public function index(Request $request)
    {
        $data['title']    = $this->title;
        $data['subtitle'] = $this->title;

        // FG list buat Select2 (kalau kebanyakan, ubah ke Select2 ajax)
        $data['fgList'] = DB::table('article')
    ->where('article_type', 'FG')
    ->orderBy('alternative_code')
    ->get(['article_code', 'alternative_code', 'article_desc']);

        // conversion value aktif
        $conv = DB::table('conversion_setting')->where('status', '1')->orderByDesc('id')->first();
        $data['conversionValue'] = $conv ? (float) $conv->conversion_value : 0;

        return view('conversion.priceList.index', $data);
    }

    public function data(Request $request)
    {
        $q = DB::table('price_list as p')
            ->select(
                'p.id', 'p.pl_number', 'p.pl_date', 'p.conversion_value',
                'p.created_by', 'p.created_at',
                DB::raw('(SELECT COUNT(*) FROM price_list_fg f WHERE f.pl_number = p.pl_number) as total_fg')
            )
            ->where('p.status', '1')
            ->orderByDesc('p.id');

        return DataTables::of($q)
            ->editColumn('pl_date', fn($r) => date('d-m-Y', strtotime($r->pl_date)))
            ->addColumn('action', function ($r) {
                return '<button class="btn btn-sm btn-info btn-detail" data-pl="'.$r->pl_number.'">Detail</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // ambil RM (bom_rm) + child part (bom_det) beserta harga rata-rata
    public function getBom(Request $request)
    {
        $fg = $request->article_code;

        $hdr = DB::table('bom_hdr')
            ->where('article_code', $fg)
            ->where('status', '1')
            ->orderByDesc('id')
            ->first();

        if (!$hdr) {
            return response()->json(['status' => 0, 'message' => "BOM aktif untuk $fg tidak ditemukan"]);
        }

        $fgArticle = DB::table('article')->where('article_code', $fg)->first();

      // RM dari bom_rm
$rm = DB::table('bom_rm as b')
    ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
    ->where('b.bom_code', $hdr->bom_code)
    ->select('b.article_code', 'a.alternative_code', 'a.article_desc', 'a.article_type', 'b.qty', DB::raw("'RM' as source"))
    ->get();

// child part dari bom_det
$det = DB::table('bom_det as b')
    ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
    ->where('b.bom_code', $hdr->bom_code)
    ->whereIn('a.article_type', ['RMP', 'RMNP'])
    ->select('b.article_code', 'a.alternative_code', 'a.article_desc', 'a.article_type', 'b.qty', DB::raw("'DET' as source"))
    ->get();

$materials = [];
foreach ($rm->concat($det) as $m) {
    $type  = strtoupper($m->article_type ?? '');
    $qty   = (float) $m->qty;
    $price = ($type === 'RMNP') ? 0 : $this->avgPrice($m->article_code); // join pakai article_code
    $materials[] = [
        'article_code'     => $m->article_code,       // buat simpan/join, hidden di UI
        'alternative_code' => $m->alternative_code,   // yang tampil
        'article_name'     => $m->article_desc,
        'article_type'     => $type,
        'source'           => $m->source,
        'qty'              => $qty,
        'unit_price'       => round($price, 4),
        'line_total'       => round($price * $qty, 2),
    ];
}

$fgArticle = DB::table('article')->where('article_code', $fg)->first();

return response()->json([
    'status' => 1,
    'fg' => [
        'article_code'     => $fg,
        'alternative_code' => $fgArticle->alternative_code ?? $fg,
        'article_name'     => $fgArticle->article_desc ?? $fg,
        'bom_code'         => $hdr->bom_code,
    ],
    'materials' => $materials,
]);
    }

    // weighted average bulan berjalan; kalau kosong mundur 1 bulan
    private function avgPrice($articleCode)
    {
        $cur = DB::selectOne("
            SELECT COALESCE(SUM(price*qty)/NULLIF(SUM(qty),0),0) AS avg_price, COUNT(*) AS n
            FROM receiving_det
            WHERE article_code = ?
              AND date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE)
        ", [$articleCode]);

        if ($cur && $cur->n > 0) {
            return (float) $cur->avg_price;
        }

        $prev = DB::selectOne("
            SELECT COALESCE(SUM(price*qty)/NULLIF(SUM(qty),0),0) AS avg_price, COUNT(*) AS n
            FROM receiving_det
            WHERE article_code = ?
              AND date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE - INTERVAL '1 month')
        ", [$articleCode]);

        return $prev ? (float) $prev->avg_price : 0.0;
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;

        $this->validate($request, ['items' => 'required'], ['required' => 'Data is required']);

        $items = is_string($request->items) ? json_decode($request->items, true) : $request->items;
        if (empty($items)) {
            return redirect()->back()->with(['status' => 1, 'title' => 'Save '.$this->title, 'message' => 'No data', 'alert' => 'warning']);
        }

        $conv    = DB::table('conversion_setting')->where('status', '1')->orderByDesc('id')->first();
        $convVal = $conv ? (float) $conv->conversion_value : 0;

        DB::beginTransaction();
        try {
            // numbering pakai advisory lock
            DB::statement("SELECT pg_advisory_xact_lock(hashtext('price_list'))");
            $prefix = 'PL'.date('ym');
            $last   = DB::table('price_list')->where('pl_number', 'like', $prefix.'%')->max('pl_number');
            $seq    = $last ? ((int) substr($last, -4)) + 1 : 1;
            $plNo   = $prefix.str_pad($seq, 4, '0', STR_PAD_LEFT);

            DB::table('price_list')->insert([
                'pl_number'        => $plNo,
                'pl_date'          => date('Y-m-d'),
                'conversion_value' => $convVal,
                'status'           => '1',
                'created_by'       => $username,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            foreach ($items as $fg) {
                $salesPrice = (float) preg_replace('/[^0-9.\-]/', '', (string)($fg['sales_price'] ?? 0));
                $mats       = $fg['materials'] ?? [];

                $materialPrice = 0;
                foreach ($mats as $m) {
                    $up  = (float) preg_replace('/[^0-9.\-]/', '', (string)($m['unit_price'] ?? 0));
                    $qty = (float) ($m['qty'] ?? 0);
                    $materialPrice += $up * $qty;
                }
                $margin     = $salesPrice - $materialPrice;
                $convResult = $convVal > 0 ? $margin / $convVal : 0;

                $fgId = DB::table('price_list_fg')->insertGetId([
                    'pl_number'         => $plNo,
                    'article_code'      => $fg['article_code'],
                    'bom_code'          => $fg['bom_code'] ?? null,
                    'sales_price'       => $salesPrice,
                    'material_price'    => $materialPrice,
                    'margin'            => $margin,
                    'conversion_value'  => $convVal,
                    'conversion_result' => $convResult,
                    'created_by'        => $username,
                    'created_at'        => date('Y-m-d H:i:s'),
                ]);

                foreach ($mats as $m) {
                    $up  = (float) preg_replace('/[^0-9.\-]/', '', (string)($m['unit_price'] ?? 0));
                    $qty = (float) ($m['qty'] ?? 0);
                    DB::table('price_list_mat')->insert([
                        'pl_number'    => $plNo,
                        'fg_id'        => $fgId,
                        'article_code' => $m['article_code'],
                        'article_type' => $m['article_type'] ?? null,
                        'source'       => $m['source'] ?? null,
                        'qty'          => $qty,
                        'unit_price'   => $up,
                        'line_total'   => $up * $qty,
                        'created_by'   => $username,
                        'created_at'   => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            DB::commit();
            $title = "Save $this->title";
            \LogActivity::addToLog($title, "username: $username saved $plNo");
            return redirect()->back()->with(['status' => 1, 'title' => $title, 'message' => "$plNo berhasil disimpan", 'alert' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            $title = "Save $this->title";
            \LogActivity::addToLog($title, "username: $username FAILED ".$e->getMessage());
            return redirect()->back()->with(['status' => 1, 'title' => $title, 'message' => 'Gagal simpan: '.$e->getMessage(), 'alert' => 'warning']);
        }
    }
}