<?php

namespace App\Http\Controllers\Conversion;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Exports\PriceListExport;
use App\Imports\PriceListImport;
use Maatwebsite\Excel\Facades\Excel;
use DataTables;
use DB;

class PriceListController extends Controller
{
    private $title;

    public function __construct()
    {
        $this->title = "Price List";
    }

   public function getTableColoumn()
{
    $kolom = [
        ['data'=>'action',                    'name'=>'action',                    'title'=>'Action', 'orderable'=>false, 'searchable'=>false],
        ['data'=>'article_alternative_code',  'name'=>'article_alternative_code',  'title'=>'Article Code'],
        ['data'=>'article_desc',              'name'=>'article_desc',              'title'=>'Article Desc'],
        ['data'=>'customer_name',             'name'=>'customer_name',             'title'=>'Customer'],
        ['data'=>'sales_price',               'name'=>'sales_price',               'title'=>'Sales Price'],
        ['data'=>'material_price',            'name'=>'material_price',            'title'=>'Material Price'],
        ['data'=>'margin',                    'name'=>'margin',                    'title'=>'Margin'],
        ['data'=>'conversion_result',         'name'=>'conversion_result',         'title'=>'Conversion'],
        ['data'=>'created_by',                'name'=>'created_by',                'title'=>'Created By'],
        ['data'=>'pl_date',                   'name'=>'pl_date',                   'title'=>'Date'],
    ];
    return json_encode($kolom, true);
}

public function index(Request $request)
{
    $data['title']    = $this->title;
    $data['subtitle'] = $this->title;
    $data['kolom']    = $this->getTableColoumn();

    $data['customerList'] = DB::table('third_party')->orderBy('nama')->get(['kode', 'nama']);

    $conv = DB::table('conversion_setting')->where('status', '1')->orderByDesc('id')->first();
    $data['conversionValue'] = $conv ? (float) $conv->conversion_value : 0;

    return view('conversion.priceList.index', $data);
}

public function create(Request $request)
{
    $data['title']    = "Create " . $this->title;
    $data['subtitle'] = $this->title;

    $data['fgList'] = DB::table('article')
        ->where('article_type', 'FG')
        ->orderBy('article_alternative_code')
        ->get(['article_code', 'article_alternative_code', 'article_desc']);

    $conv = DB::table('conversion_setting')->where('status', '1')->orderByDesc('id')->first();
    $data['conversionValue'] = $conv ? (float) $conv->conversion_value : 0;

    return view('conversion.priceList.create', $data);
}

public function list(Request $request)
{
    $searchArticle  = strtolower((string) $request->searchArticle);
    $searchDesc     = strtolower((string) $request->searchDesc);
    $searchCustomer = (string) $request->searchCustomer;

    $query = DB::table('price_list_fg as f')
        ->leftJoin('article as a', 'a.article_code', '=', 'f.article_code')
        ->where('f.status', '1')
        ->where(function ($q) use ($searchArticle, $searchDesc, $searchCustomer) {
            $searchArticle  ? $q->where('a.article_alternative_code', 'ilike', '%' . $searchArticle . '%') : '';
            $searchDesc     ? $q->where('a.article_desc', 'ilike', '%' . $searchDesc . '%') : '';
            $searchCustomer ? $q->where('f.customer_code', $searchCustomer) : '';
        })
        ->select(
            'f.id', 'f.article_code', 'a.article_alternative_code', 'a.article_desc',
            'f.customer_name', 'f.pl_date', 'f.sales_price', 'f.material_price', 'f.margin',
            'f.conversion_value', 'f.conversion_result', 'f.created_by'
        )
        ->orderBy('a.article_alternative_code');

    $bisaEdit = Auth::user()->can('pricelist-edit');

    return DataTables::of($query)
        ->editColumn('pl_date',           fn($r) => date('d-m-Y', strtotime($r->pl_date)))
        ->editColumn('sales_price',       fn($r) => number_format($r->sales_price, 2))
        ->editColumn('material_price',    fn($r) => number_format($r->material_price, 2))
        ->editColumn('margin',            fn($r) => number_format($r->margin, 2))
        ->editColumn('conversion_result', fn($r) => number_format($r->conversion_result, 2))
        ->editColumn('customer_name',     fn($r) => $r->customer_name ?? '-')
        ->addColumn('action', function ($r) use ($bisaEdit) {
            $id = Crypt::encryptString($r->id);

            $buttons  = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>';
            $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

            $buttons .= '<a href="javascript:;" class="dropdown-item btn-detail" data-id="' . $r->id . '">
                            <i data-feather="list"></i><span>' . __('Detail') . '</span></a>';

               $buttons .= '<a href="javascript:;" class="dropdown-item btn-edit" data-id="' . $id . '">
                <i data-feather="edit-2"></i><span>' . __('Edit') . '</span></a>';

                $buttons .= "<form id='delete-form-{$r->id}' action='" . route('conversion.priceList.destroy') . "' method='POST' class='d-none'>
                                " . csrf_field() . "
                                <input type='hidden' name='id' value='{$id}'>
                            </form>
                            <a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                                data-confirm='Hapus Price List ini?|Data akan dinonaktifkan dan tidak muncul lagi di daftar.'
                                data-confirm-yes='document.getElementById(\"delete-form-{$r->id}\").submit();'
                                data-modal-id='{$r->id}'
                                data-url='" . route('conversion.priceList.destroy') . "'>
                                <i data-feather='trash-2' class='feather-14-red'></i><span class='text-danger'>" . __('Delete') . "</span></a>";

            $buttons .= '</div></div>';
            return $buttons;
        })
        ->rawColumns(['action'])
        ->make(true);
}

public function destroy(Request $request)
{
    $username = Auth::user()->username;
    $title    = "Delete $this->title";
    $id       = Crypt::decryptString($request->id);

    $row = DB::table('price_list_fg')->where('id', $id)->where('status', '1')->first();
    if (!$row) {
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => 'Data tidak ditemukan / sudah tidak aktif']);
    }

    DB::table('price_list_fg')->where('id', $id)->update([
        'status'     => '0',
        'updated_by' => $username,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $message = "$title untuk artikel {$row->article_code} berhasil dihapus";
    \LogActivity::addToLog($title, "username: $username deleted price list id $id");
    return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
}

    // ambil RM (bom_rm) + child part (bom_det) beserta harga rata-rata
    public function getBom(Request $request)
{
    return response()->json($this->buildBomPayload($request->article_code));
}

// dipakai bareng oleh getBom() (AJAX pilih artikel) dan importExcel()
private function buildBomPayload($fg): array
{
    $fgArticle = DB::table('article')->where('article_code', $fg)->first();
    if (!$fgArticle) {
        return ['status' => 0, 'message' => "Artikel '$fg' tidak ditemukan"];
    }
    $fgLabel = $fgArticle->article_alternative_code ?? $fg;

    $hdr = DB::table('bom_hdr')
        ->where('article_code', $fg)
        ->where('status', '!=', '5')
        ->orderByDesc('id')
        ->first();

    if (!$hdr) {
        return ['status' => 0, 'message' => "BOM aktif untuk $fgLabel tidak ditemukan"];
    }

    $customer = DB::table('third_party')->where('kode', $hdr->customer)->first();

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
        $type = strtoupper($m->article_type ?? '');
        $qty  = (float) $m->qty;

        if ($type === 'RMNP') {
            $price = 0;
            $lastReceivingDate = null;
        } else {
            $ap    = $this->avgPrice($m->article_code);
            $price = $ap['price'];
            $lastReceivingDate = $ap['last_date'];
        }

        $materials[] = [
            'article_code'             => $m->article_code,
            'article_alternative_code' => $m->article_alternative_code,
            'article_name'             => $m->article_desc,
            'article_type'             => $type,
            'source'                   => $m->source,
            'qty'                      => $qty,
            'unit_price'               => round($price, 4),
            'line_total'               => round($price * $qty, 2),
            'last_receiving_date'      => $lastReceivingDate,
        ];
    }

    return [
        'status' => 1,
        'fg' => [
            'article_code'             => $fg,
            'article_alternative_code' => $fgArticle->article_alternative_code ?? $fg,
            'article_name'             => $fgArticle->article_desc ?? $fg,
            'bom_code'                 => $hdr->bom_code,
            'customer_code'            => $hdr->customer ?? null,
            'customer_name'            => $customer->nama ?? null,
        ],
        'materials' => $materials,
    ];
}

    // weighted average bulan berjalan; kalau kosong mundur 1 bulan
    // weighted average bulan berjalan; kalau kosong, mundur bulan demi bulan
// sampai menemukan bulan terakhir yang punya data receiving.
// Batas maksimum mundur $maxMonthsBack bulan untuk mencegah loop tak berujung
// kalau artikel memang belum pernah ada receiving-nya sama sekali.
private function avgPrice($articleCode, int $maxMonthsBack = 24): array
{
    for ($i = 0; $i <= $maxMonthsBack; $i++) {
        $row = DB::selectOne("
            SELECT COALESCE(SUM(price*qty)/NULLIF(SUM(qty),0),0) AS avg_price,
                   COUNT(*) AS n,
                   MAX(created_at) AS last_date
            FROM receiving_det
            WHERE article_code = ?
              AND date_trunc('month', created_at) = date_trunc('month', CURRENT_DATE - (? || ' months')::interval)
        ", [$articleCode, $i]);

        if ($row && $row->n > 0) {
            return ['price' => (float) $row->avg_price, 'last_date' => $row->last_date];
        }
    }

    return ['price' => 0.0, 'last_date' => null];
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
        'customer_code'     => $fg['customer_code'] ?? null,
        'customer_name'     => $fg['customer_name'] ?? null,
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

    $history = DB::table('price_list_fg_hist')
        ->where('fg_id', $id)
        ->orderByDesc('changed_at')
        ->get();

    return response()->json(['status' => 1, 'fg' => $fg, 'materials' => $mats, 'history' => $history]);
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
    // ── catat kondisi SEBELUM diubah ke tabel log ──
    DB::table('price_list_fg_hist')->insert([
    'fg_id'                 => $id,
    'article_code'          => $row->article_code,
    'customer_code_old'     => $row->customer_code,
    'customer_name_old'     => $row->customer_name,
    'sales_price_old'       => $row->sales_price,
    'material_price_old'    => $row->material_price,
    'margin_old'            => $row->margin,
    'conversion_value_old'  => $row->conversion_value,   // ← TAMBAHKAN
    'conversion_result_old' => $row->conversion_result,
    'changed_by'            => $username,
    'changed_at'            => date('Y-m-d H:i:s'),
]);

   DB::table('price_list_fg')->where('id', $id)->update([
    'customer_code'     => $fg['customer_code'] ?? $row->customer_code,   // ← fallback ke data lama
    'customer_name'     => $fg['customer_name'] ?? $row->customer_name,   // ← fallback ke data lama
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

    public function exportExcelTemplate()
{
    return Excel::download(new PriceListExport(), 'template_price_list.xlsx');
}

public function importExcel(Request $request)
{
    $this->validate($request, ['file' => 'required|mimes:xls,xlsx'], ['required' => 'File is required']);

    // toCollection() selalu mengembalikan Collection-of-sheets, tidak seperti
    // Excel::import() yang ambigu kalau file punya lebih dari 1 sheet.
    $sheets = Excel::toCollection(new PriceListImport(), $request->file('file'));
    $rows   = $sheets->get(0, collect()); // sheet pertama = 'template'

    $dataDetail = [];
    $errors     = [];
    $usedCodes  = [];

    foreach ($rows as $i => $row) {
        $lineNo     = $i + 2; // baris asli di Excel (setelah heading)
        $code       = trim((string) ($row['article_code'] ?? ''));
        $salesPrice = $row['sales_price'] ?? null;

        if ($code === '') continue;

        $article = DB::table('article')
            ->where('article_alternative_code', $code)
            ->orWhere('article_code', $code)
            ->first();

        if (!$article) {
            $errors[] = "Baris $lineNo: artikel '$code' tidak ditemukan";
            continue;
        }

        if (in_array($article->article_code, $usedCodes)) {
            $errors[] = "Baris $lineNo: artikel '$code' duplikat, dilewati";
            continue;
        }

        $bom = $this->buildBomPayload($article->article_code);
        if ($bom['status'] != 1) {
            $errors[] = "Baris $lineNo: " . $bom['message'];
            continue;
        }

        $bom['fg']['sales_price'] = (float) preg_replace('/[^0-9.\-]/', '', (string) $salesPrice);
        $usedCodes[] = $article->article_code;
        $dataDetail[] = $bom;
    }

    return response()->json([
        'status'     => 1,
        'dataDetail' => $dataDetail,
        'errors'     => $errors,
    ]);
}
}