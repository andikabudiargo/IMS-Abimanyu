<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Response;
use App\Permission;
use DataTables;
use DB;

class UomConControllerv2 extends Controller
{
    private $title;
    public function __construct()
    {
        $this->title = "UOM Coversion";
    }
    
    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['uoms'] = DB::table('uom')->orderBy('name')->get();
        $data['supps'] = DB::table('third_party')
            ->where ('third_party_type','=','supp')
            ->orderBy('nama')
            ->get();   
        return view("uomConv2.index",$data);
    }

    public function create(Request $request)
{
    $data['title'] = "Create $this->title";
    $data['subtitle'] = "Create New $this->title";

    $data['articles'] = DB::table('article')
        ->where('status', '1')
        ->whereIn('article_type', ['CM1', 'CM2', 'CM3'])
        ->orderBy('article_desc')
        ->get();

    $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

    return view("uomConv2.create", $data);
}

    public function store(Request $request)
{
    $username = Auth::user()->username;

    $this->validate($request, [
        'article' => 'required',
        'supplier' => 'required',
        'unitFrom' => 'required',
        'unitTo' => 'required',
        'unitFactor' => 'required'
    ]);

    $articleCode = $request->article;
    $supplierCode = $request->supplier;

    $unitFrom = $request->unitFrom;
    $unitTo = explode('|', $request->unitTo)[0];
    $unitFactor = $request->unitFactor;

    DB::beginTransaction();

    try {

        // ambil detail article
        $article = DB::table('article')
            ->where('article_code', $articleCode)
            ->first();

        // ambil detail supplier
        $supplier = DB::table('third_party')
            ->where('kode', $supplierCode)
            ->where('third_party_type', 'supp')
            ->first();

        if (!$article || !$supplier) {
            throw new \Exception("Invalid article or supplier");
        }

        $exists = DB::table('uom_con_v2')
            ->where('article_code', $article->article_code)
            ->where('supplier_code', $supplier->kode)
            ->where('unit_from', $unitFrom)
            ->where('unit_to', $unitTo)
            ->exists();

        if ($exists) {

        DB::rollBack();

        return redirect()->back()->with([
            'alert' => 'warning',
            'message' => 'UOM Conversion sudah pernah dibuat'
            ]);
        }

        DB::table('uom_con_v2')
        ->insert([
             'article_code' => $article->article_code,
             'supplier_code' => $supplier->kode,
             'unit_from' => $unitFrom,
             'unit_to' => $unitTo,

             'article_desc' => $article->article_desc,
             'article_alternative_code' => $article->article_alternative_code,
             'supplier_name' => $supplier->nama,
             'unit_factor' => $unitFactor,

             'created_by' => $username,
             'updated_by' => $username,
             'created_at' => now(),
             'updated_at' => now()
       ]);

        DB::commit();

        return redirect()->back()->with([
            'alert' => 'success',
            'message' => 'UOM Conversion saved successfully'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return redirect()->back()->with([
            'alert' => 'warning',
            'message' => 'Failed: ' . $e->getMessage()
        ]);
    }
}

   public function edit(Request $request)
{
    $id = Crypt::decryptString($request->id);

    $data['title'] = "Edit $this->title";
    $data['subtitle'] = "Edit $this->title";

    $data['uomConv2'] = DB::table('uom_con_v2')
        ->leftJoin('uom', 'uom.code', '=', 'uom_con_v2.unit_from')
        ->where('uom_con_v2.id', $id)
        ->select(
            'uom_con_v2.*',
            'uom.uom_group'
        )
        ->first();

    $data['uoms'] = DB::table('uom')
        ->where('uom_group', $data['uomConv2']->uom_group)
        ->orderBy('name')
        ->get();

    return view('uomConv2.edit', $data);
}

public function update(Request $request, $id)
{
    $username = Auth::user()->username;
    $id = Crypt::decryptString($id);

    $this->validate($request, [
        'unitFactor' => 'required'
    ]);

    DB::beginTransaction();

    try {

        $uom = DB::table('uom_con_v2')
            ->where('id', $id)
            ->first();

        if (!$uom) {
            throw new \Exception("Data not found");
        }

        DB::table('uom_con_v2')
            ->where('id', $id)
            ->update([
                'unit_factor' => $request->unitFactor,
                'updated_by' => $username,
                'updated_at' => now()
            ]);

        DB::commit();

        return redirect()
            ->route('uomConsv2.index')
            ->with([
                'alert' => 'success',
                'message' => 'UOM Conversion successfully updated'
            ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return redirect()->back()->with([
            'alert' => 'warning',
            'message' => 'Failed: ' . $e->getMessage()
        ]);
    }
}

   public function destroy(Request $request)
{
    $username = Auth::user()->username;
    $id = Crypt::decryptString($request->id);

    $uom = DB::table('uom_con_v2')
        ->where('id', $id)
        ->first();

    if (!$uom) {
        return redirect()->back()->with([
            'alert' => 'warning',
            'message' => 'Data tidak ditemukan'
        ]);
    }

    $row_affected = DB::table('uom_con_v2')
        ->where('id', $id)
        ->delete();

    if ($row_affected > 0) {

        $title = "Delete $this->title";
        $alert = "success";
        $message = "$this->title berhasil dihapus. Article : {$uom->article_code}, Supplier : {$uom->supplier_code}, Unit From : {$uom->unit_from}, Unit To : {$uom->unit_to}";

        \LogActivity::addToLog(
            $title,
            "username: $username Status $message"
        );

        return redirect()->back()->with([
            'title' => $title,
            'alert' => $alert,
            'message' => $message
        ]);

    } else {

        $title = "Delete $this->title";
        $alert = "warning";
        $message = "$this->title gagal dihapus. Article : {$uom->article_code}, Supplier : {$uom->supplier_code}, Unit From : {$uom->unit_from}, Unit To : {$uom->unit_to}";

        \LogActivity::addToLog(
            $title,
            "username: $username Status $message"
        );

        return redirect()->back()->with([
            'title' => $title,
            'alert' => $alert,
            'message' => $message
        ]);
    }
}

   public function list(Request $request)
{
    $articleCode = $request->articleCode;
    $articleName = $request->articleName;
    $supplier = $request->supplier;
    $unitFrom = strtolower($request->unitFrom);
    $unitTo = strtolower($request->unitTo);

    $data = DB::table('uom_con_v2')
        ->where(function ($query) use (
            $articleCode,
            $articleName,
            $supplier,
            $unitFrom,
            $unitTo
        ) {

            if($articleCode){
                $query->where('article_alternative_code','ilike',"%{$articleCode}%");
            }

            if($articleName){
                $query->where('article_desc','ilike',"%{$articleName}%");
            }

            if($supplier){
                $query->where('supplier_code',$supplier);
            }

            if ($unitFrom) {
                $query->where('unit_from', 'ilike', "%{$unitFrom}%");
            }

            if ($unitTo) {
                $query->where('unit_to', 'ilike', "%{$unitTo}%");
            }
        })
        ->get();

    return Datatables::of($data)
        ->addColumn('action', function ($data) {

            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';

            $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

            if (Auth::user()->can('department-edit')) {
                $buttons .= '<a href="' .
                    route('uomConv2.edit', [
                        'id' => Crypt::encryptString($data->id)
                    ]) .
                    '" class="dropdown-item">
                        <i data-feather="file-text"></i>
                        Edit
                    </a>';
            }

            if (Auth::user()->can('department-delete')) {
                $buttons .= "<a href='javascript:;'
                        id='deleteButton'
                        class='dropdown-item'
                        data-toggle='modal'
                        data-target='#smallModal'
                        data-href='" .
                        route('uomConv2.destroy', [
                            'id' => Crypt::encryptString($data->id)
                        ]) .
                        "'>
                        <i data-feather='trash-2' class='feather-14-red'></i>
                        Delete
                    </a>";
            }

            $buttons .= '</div></div>';

            return $buttons;
        })
        ->rawColumns(['action'])
        ->make(true);
}

    public function uom(Request $request)
{
    // 1. cek apakah sudah pernah ada conversion (hanya untuk article aktif)
    $conversion = DB::table('uom_con_v2')
        ->join('article', 'article.article_code', '=', 'uom_con_v2.article_code')
        ->where('uom_con_v2.article_code', $request->article_code)
        ->where('article.status', 1)
        ->orderBy('uom_con_v2.id', 'asc')
        ->select('uom_con_v2.*')
        ->first();

    if ($conversion) {

        return response()->json([
            'has_conversion' => true,
            'uom' => $conversion->unit_to, // FIXED UNIT TO
            'uom_name' => DB::table('uom')
                ->where('code', $conversion->unit_to)
                ->value('name'),
        ]);
    }

    // 2. kalau belum ada conversion, ambil dari article (default), hanya yang aktif
    $article = DB::table('article')
        ->leftJoin('uom', 'uom.code', '=', 'article.uom')
        ->where('article.article_code', $request->article_code)
        ->where('article.status', 1)
        ->select(
            'article.uom',
            'uom.name as uom_name'
        )
        ->first();

    if (!$article) {
        return response()->json([
            'has_conversion' => false,
            'uom' => null,
            'uom_name' => null,
            'message' => 'Article tidak ditemukan atau tidak aktif',
        ], 404);
    }

    return response()->json([
        'has_conversion' => false,
        'uom' => $article->uom,
        'uom_name' => $article->uom_name
    ]);
}

}
