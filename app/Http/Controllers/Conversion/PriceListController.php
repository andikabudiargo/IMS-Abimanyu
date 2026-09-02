<?php

namespace App\Http\Controllers\Conversion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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

        $data['fgList'] = DB::table('article')
            ->where('article_type', 'FG')
            ->orderBy('article_alternative_code')
            ->get(['article_code', 'article_alternative_code', 'article_desc']);

        $conv = DB::table('conversion_setting')->where('status', '1')->orderByDesc('id')->first();
        $data['conversionValue'] = $conv ? (float) $conv->conversion_value : 0;

        return view('conversion.priceList.index', $data);
    }

    public function data(Request $request)
    {
        $q = DB::table('price_list_fg as f')
            ->leftJoin('article as a', 'a.article_code', '=', 'f.article_code')
            ->where('f.status', '1')
            ->select(
                'f.id', 'f.article_code', 'a.article_alternative_code', 'a.article_desc',
                'f.pl_date', 'f.sales_price', 'f.material_price', 'f.margin',
                'f.conversion_value', 'f.conversion_result', 'f.created_by'
            )
            ->orderBy('a.article_alternative_code');

        $bisaEdit = Auth::user()->can('pricelist-edit');

        return DataTables::of($q)
            ->editColumn('pl_date', fn($r) => date('d-m-Y', strtotime($r->pl_date)))
            ->editColumn('sales_price',       fn($r) => number_format($r->sales_price, 2))
            ->editColumn('material_price',    fn($r) => number_format($r->material_price, 2))
            ->editColumn('margin',            fn($r) => number_format($r->margin, 2))
            ->editColumn('conversion_result', fn($r) => number_format($r->conversion_result, 2))
            ->addColumn('action', function ($r) use ($bisaEdit) {
                $id = Crypt::encryptString($r->id);
                $buttons  = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                    <i data-feather="menu"></i>
                                </a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';
                $buttons .=     '<a href="javascript:;" class="dropdown-item btn-detail" data-id="'.$r->id.'">
                                    <i data-feather="list"></i> Detail
                                </a>';
                if ($bisaEdit) {
                    $buttons .= '<a href="javascript:;" class="dropdown-item btn-edit" data-id="'.$r->id.'">
                                    <i data-feather="file-text"></i> Edit
                                </a>';
                }
                $buttons .= '</div></div>';
                return $buttons;
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
            ->where('status', '!=', '5')
            ->orderByDesc('id')
            ->first();

        if (!$hdr) {
            return response()->json(['status' => 0, 'message' => "BOM aktif untuk $fg tidak ditemukan"]);
        }

        $fgArticle = DB::table('article')->where('article_code', $fg)->first();

        $rm = DB::table('bom_rm as b')
            ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
            ->where('b.bom_code', $hdr->bom_code)
            ->select('b.article_code', 'a.article_alternative_code', 'a.article_desc', 'a.article_type', 'b.qty', DB::raw("'RM' as source"))
            ->get();

        $det = DB::table('bom_det as b')
            ->leftJoin('article as a', 'a.article_code', '=', 'b.article_code')
            ->where('b.bom_code', $hdr->bom_code)
            ->whereIn('a.article_type', ['RMP', 'RMNP'])
            ->select('b.article_code', 'a.article_alternative_code', 'a.article_desc', 'a.article_type', 'b.qty', DB::raw("'DET' as source"))
            ->get();

        $materials = [];
        foreach ($rm->concat($det) as $m) {
            $type  = strtoupper($m->article_type ?? '');
            $qty   = (float) $m->qty;
            $price = ($type === 'RMNP') ? 0 : $this->avgPrice($m->article_code);
            $materials[] = [
                'article_code'             => $m->article_code,
                'article_alternative_code' => $m->article_alternative_code,
                'article_name'             => $m->article_desc,
                'article_type'             => $type,
                'source'                   => $m->source,
                'qty'                      => $qty,
                'unit_price'               => round($price, 4),
                'line_total'               => round($price * $qty, 2),
            ];
        }

        return response()->json([
            'status' => 1,
            'fg' => [
                'article_code'             => $fg,
                'article_alternative_code' => $fgArticle->article_alternative_code ?? $fg,
                'article_name'             => $fgArticle->article_desc ?? $fg,
                'bom_code'                 => $hdr->bom_code,
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

    private function calcMaterialPrice($mats)
    {
        $total = 0;
        foreach ($mats as $m) {
            $up  = (float) preg_replace('/[^0-9.\-]/', '', (string)($m['unit_price'] ?? 0));
            $qty = (float) ($m['qty'] ?? 0);
            $total += $up * $qty;
        }
        return $total;
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
            foreach ($items as $fg) {
                $salesPrice    = (float) preg_replace('/[^0-9.\-]/', '', (string)($fg['sales_price'] ?? 0));
                $mats          = $fg['materials'] ?? [];
                $materialPrice = $this->calcMaterialPrice($mats);
                $margin        = $salesPrice - $materialPrice;
                $convResult    = $convVal > 0 ? $margin / $convVal : 0;

                // nonaktifkan versi lama FG ini
                DB::table('price_list_fg')
                    ->where('article_code', $fg['article_code'])
                    ->where('status', '1')
                    ->update([
                        'status'     => '0',
                        'updated_by' => $username,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                $fgId = DB::table('price_list_fg')->insertGetId([
                    'article_code'      => $fg['article_code'],
                    'bom_code'          => $fg['bom_code'] ?? null,
                    'pl_date'           => date('Y-m-d'),
                    'sales_price'       => $salesPrice,
                    'material_price'    => $materialPrice,
                    'margin'            => $margin,
                    'conversion_value'  => $convVal,
                    'conversion_result' => $convResult,
                    'status'            => '1',
                    'created_by'        => $username,
                    'created_at'        => date('Y-m-d H:i:s'),
                ]);

                $this->insertMaterials($fgId, $mats, $username);
            }

            DB::commit();
            $title = "Save $this->title";
            \LogActivity::addToLog($title, "username: $username saved ".count($items)." FG");
            return redirect()->back()->with(['status' => 1, 'title' => $title, 'message' => 'Price list berhasil disimpan', 'alert' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            $title = "Save $this->title";
            \LogActivity::addToLog($title, "username: $username FAILED ".$e->getMessage());
            return redirect()->back()->with(['status' => 1, 'title' => $title, 'message' => 'Gagal simpan: '.$e->getMessage(), 'alert' => 'warning']);
        }
    }

    // detail (view only) — dipakai modal Detail
    public function show(Request $request)
    {
        $id = $request->id;

        $fg = DB::table('price_list_fg as f')
            ->leftJoin('article as a', 'a.article_code', '=', 'f.article_code')
            ->where('f.id', $id)
            ->select('f.*', 'a.article_alternative_code', 'a.article_desc')
            ->first();

        if (!$fg) {
            return response()->json(['status' => 0, 'message' => 'Data tidak ditemukan']);
        }

        $mats = DB::table('price_list_mat as m')
            ->leftJoin('article as a', 'a.article_code', '=', 'm.article_code')
            ->where('m.fg_id', $id)
            ->select('m.article_code', 'a.article_alternative_code', 'a.article_desc',
                     'm.article_type', 'm.source', 'm.qty', 'm.unit_price', 'm.line_total')
            ->get();

        return response()->json(['status' => 1, 'fg' => $fg, 'materials' => $mats]);
    }

    // edit — ambil data tersimpan buat form (bukan tarik ulang BOM)
    public function edit(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $fg = DB::table('price_list_fg as f')
            ->leftJoin('article as a', 'a.article_code', '=', 'f.article_code')
            ->where('f.id', $id)
            ->where('f.status', '1')
            ->select('f.*', 'a.article_alternative_code', 'a.article_desc')
            ->first();

        if (!$fg) {
            return response()->json(['status' => 0, 'message' => 'Data tidak ditemukan / bukan versi aktif']);
        }

        $mats = DB::table('price_list_mat as m')
            ->leftJoin('article as a', 'a.article_code', '=', 'm.article_code')
            ->where('m.fg_id', $id)
            ->select('m.article_code', 'a.article_alternative_code',
                     DB::raw('a.article_desc as article_name'),
                     'm.article_type', 'm.source', 'm.qty', 'm.unit_price')
            ->get();

        return response()->json(['status' => 1, 'fg' => $fg, 'materials' => $mats]);
    }

    // update-in-place ke baris aktif
    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);

        $items = is_string($request->items) ? json_decode($request->items, true) : $request->items;
        $fg    = is_array($items) ? ($items[0] ?? null) : null;

        if (!$fg) {
            return response()->json(['status' => 0, 'title' => 'Update '.$this->title, 'message' => 'No data', 'alert' => 'error']);
        }

        $row = DB::table('price_list_fg')->where('id', $id)->where('status', '1')->first();
        if (!$row) {
            return response()->json(['status' => 0, 'title' => 'Update '.$this->title, 'message' => 'Data tidak ditemukan / bukan versi aktif', 'alert' => 'error']);
        }

        $conv    = DB::table('conversion_setting')->where('status', '1')->orderByDesc('id')->first();
        $convVal = $conv ? (float) $conv->conversion_value : (float) $row->conversion_value;

        $salesPrice    = (float) preg_replace('/[^0-9.\-]/', '', (string)($fg['sales_price'] ?? 0));
        $mats          = $fg['materials'] ?? [];
        $materialPrice = $this->calcMaterialPrice($mats);
        $margin        = $salesPrice - $materialPrice;
        $convResult    = $convVal > 0 ? $margin / $convVal : 0;

        DB::beginTransaction();
        try {
            DB::table('price_list_fg')->where('id', $id)->update([
                'sales_price'       => $salesPrice,
                'material_price'    => $materialPrice,
                'margin'            => $margin,
                'conversion_value'  => $convVal,
                'conversion_result' => $convResult,
                'updated_by'        => $username,
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

            // refresh material lines
            DB::table('price_list_mat')->where('fg_id', $id)->delete();
            $this->insertMaterials($id, $mats, $username);

            DB::commit();
            $title = "Update $this->title";
            \LogActivity::addToLog($title, "username: $username updated FG id $id");
            return response()->json(['status' => 1, 'title' => $title, 'message' => 'Price list berhasil diupdate', 'alert' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            $title = "Update $this->title";
            \LogActivity::addToLog($title, "username: $username FAILED ".$e->getMessage());
            return response()->json(['status' => 0, 'title' => $title, 'message' => 'Gagal update: '.$e->getMessage(), 'alert' => 'warning']);
        }
    }

    private function insertMaterials($fgId, $mats, $username)
    {
        foreach ($mats as $m) {
            $up  = (float) preg_replace('/[^0-9.\-]/', '', (string)($m['unit_price'] ?? 0));
            $qty = (float) ($m['qty'] ?? 0);
            DB::table('price_list_mat')->insert([
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
}