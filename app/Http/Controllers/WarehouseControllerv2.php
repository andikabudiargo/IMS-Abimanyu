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
use PDF;
use AppHelpers;
use Approval;
use Artisan;
use Excel;
use App\Exports\StockAnomalyExport;

class WarehouseControllerv2 extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Stock";
        $this->moduleCode = "Stock";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumnArticle(){
        $kolom=    
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false, 'searchable'=>false],
            ['data'=>'location_number','name'=>'location_number','title'=>'Location'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Code'],
            ['data'=>'desc','name'=>'article_desc','title'=>'Name'],
            ['data'=>'critical_stock','name'=>'critical_stock','title'=>'Critical Stock'],
            ['data'=>'cust','name'=>'third_party.nama','title'=>'Custs/Supp'],
            ['data'=>'article_qty','name'=>'article_qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'safety_stock','name'=>'safety_stock','title'=>'Safety Stock'],
            ['data'=>'min_package','name'=>'min_package','title'=>'Min Package'],
            ['data'=>'last_rec_date','name'=>'last_rec_date','title'=>'Last Rec'],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnMovement()
{
    $kolom = [
        ['data'=>'movement_date','name'=>'movement_date','title'=>'Date'],
        ['data'=>'mv_from','name'=>'mv_from','title'=>'From'],
        ['data'=>'mv_to','name'=>'mv_to','title'=>'To'],
        ['data'=>'inout','name'=>'inout','title'=>'Transaction'],
        ['data'=>'movement_type','name'=>'movement_type','title'=>'Type'],
        ['data'=>'movement_transnno','name'=>'movement_transnno','title'=>'Ref'],
        ['data'=>'last_qty','name'=>'last_qty','title'=>'Opening','className'=>'text-right'],
        ['data'=>'qty_in','name'=>'qty_in','title'=>'QTY In','className'=>'text-right','searchable'=>false],
        ['data'=>'qty_out','name'=>'qty_out','title'=>'QTY Out','className'=>'text-right','searchable'=>false],
        ['data'=>'balanceqty','name'=>'balanceqty','title'=>'Balance','className'=>'text-right'],
        ['data'=>'trx_status','name'=>'trx_status','title'=>'Status'],
        ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
        ['data'=>'movement_desc','name'=>'movement_desc','title'=>'Description'],
        ['data'=>'urutan','name'=>'urutan','title'=>'Running Number','searchable'=>false,'visible'=>false],
    ];
    return json_encode($kolom, true);
}

// Alias supaya view lama yang masih panggil versi global tidak error.
public function getTableColoumnMovementGlobal()
{
    return $this->getTableColoumnMovement();
}

    public function article(Request $request)
    {
        $data['title'] = "Stock Article v2";

        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();
    
        $data['supps'] = DB::table('third_party')
        // ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();      
        
         $data['locs'] = DB::table('stock_location_master')
        ->orderBy('location_name')
        ->get();      

        //$data['groups'] = DB::table('group_materials')
        //->where ('status','=',1)
        //->orderBy('name')
        //->get();

        $data['kolom'] = $this->getTableColoumnArticle();
        $data['kolomMovement'] = $this->getTableColoumnMovement();
        $data['kolomMovementGlobal'] = $this->getTableColoumnMovementGlobal();
        
        return view("warehouse.articlev2",$data);
    }


    public function listArticle(Request $request)
{
    $code     = strtolower($request->code);
    $name     = strtolower($request->name);
    $group    = strtolower($request->group);
    $supp     = strtolower($request->supp);
    $type     = strtolower($request->type);
    $status   = $request->status;
    $qty      = $request->qty;
    $operator = $request->opr;
    $location = $request->location;
    $hideEmptyQty = $request->hideEmptyQty;
    $asof     = $request->asof;

    // label lokasi
    $locationLabel = $location
        ? DB::table('stock_location_master')
            ->where('location_code', $location)
            ->value('location_name')
        : 'ALL';
    $locationLabel = $locationLabel ?: 'ALL';

    // ── GUARD: mode as-of wajib ada filter (lokasi / kode / nama / type / supp) ──
   if ($asof) {
    $hasFilter = $location || $code || $name || $type || $supp;
    if (!$hasFilter) {
        return Datatables::of(collect([]))->make(true); // (di summary: return 0 semua)
    }

    $asofYmd   = \Carbon\Carbon::createFromFormat('d-m-Y', $asof)->format('Y-m-d');
    $locParam  = $location ? DB::getPdo()->quote($location) : "NULL";
    $asofParam = DB::getPdo()->quote($asofYmd);

    // basis: HANYA artikel yang punya jejak di warehouse_stock pada lokasi ini
    // (kalau lokasi kosong, ambil semua artikel yang punya stock di mana pun)
    $articleBasis = DB::table('warehouse_stock as ws')
        ->select('ws.article_code')
        ->when($location, fn($q) => $q->where('ws.location_number', $location))
        ->groupBy('ws.article_code');

    $stockSub = DB::table('article as a_asof')
        ->joinSub($articleBasis, 'basis', function ($j) {
            $j->on('basis.article_code', '=', 'a_asof.article_code');
        })
        ->select(
            'a_asof.article_code',
            DB::raw("get_last_qty_new(a_asof.article_code, $asofParam, 'HO', $locParam) as article_qty")
        )
        ->when($code, fn($q) => $q->where('a_asof.article_alternative_code', 'ilike', '%'.$code.'%'))
        ->when($name, fn($q) => $q->where('a_asof.article_desc', 'ilike', '%'.$name.'%'))
        ->when($type, fn($q) => $q->where('a_asof.article_alternative_code', 'ilike', $type.'%'))
        ->when($supp, fn($q) => $q->where('a_asof.third_party', 'ilike', '%'.$supp.'%'));
    } else {
        $stockSub = DB::table('warehouse_stock')
            ->select('article_code', DB::raw('sum(article_qty) as article_qty'))
            ->when($location, function ($q) use ($location) {
                $q->where('location_number', $location);
            })
            ->groupBy('article_code');
    }

    $data = DB::table('article')
        ->select(
            'article.*',
            'costprice',
            'article.article_code as art_code',
            'article.article_alternative_code as code',
            'article.article_desc as desc',
            DB::raw("coalesce(ucv.unit_to, article.uom) as uom"),
            'quality',
            'note',
            'article.id',
            'group_materials.name as group',
            'third_party.nama as cust',
            'safety_stock',
            'min_package',
            'uom.uom_group',
            DB::raw("'".addslashes($locationLabel)."' as location_number"),
            DB::raw("last_rec_date(article.article_code) as last_rec_date"),
            DB::raw("coalesce(stock.article_qty,0) as article_qty")
        )
        ->leftJoin('group_materials', 'group_materials.code', '=', 'article.group_of_material')
        ->leftJoin('third_party', 'third_party.kode', '=', 'article.third_party')
        ->leftJoin('uom_con_v2 as ucv', function ($j) {
            $j->on('ucv.article_code', '=', 'article.article_code')
              ->on('ucv.supplier_name', '=', 'article.third_party');
        })
        ->joinSub($stockSub, 'stock', function ($j) {
            $j->on('stock.article_code', '=', 'article.article_code');
        })
        ->leftJoin('uom', 'uom.code', 'article.uom')
        ->where(function ($query) use ($code, $name, $group, $supp, $type, $operator, $qty, $status, $hideEmptyQty) {
            $code  ? $query->where('article.article_alternative_code', 'ilike', '%'.$code.'%') : '';
            $name  ? $query->where('article.article_desc', 'ilike', '%'.$name.'%') : '';
            $group ? $query->where('article.group_of_material', 'ilike', '%'.$group.'%') : '';
            $supp  ? $query->where('article.third_party', 'ilike', '%'.$supp.'%') : '';
            $type  ? $query->where('article.article_alternative_code', 'ilike', $type.'%') : '';
            $operator ? $query->where('stock.article_qty', $operator, (float)$qty) : '';

            if ($status == 'critical') {
                $query->where('stock.article_qty', '<', DB::raw('coalesce(safety_stock,0)'));
            } else if ($status == 'save') {
                $query->where('stock.article_qty', '>=', DB::raw('coalesce(safety_stock,0)'));
            } else if ($status == 'empty') {
                $query->where('stock.article_qty', '<=', 0);
            }
            if ($hideEmptyQty) {
                $query->where('stock.article_qty', '>', 0);
            }
        })
        ->orderBy('article_desc')
        ->get();

    return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons  = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .= '<div class="dropdown-menu dropdown-menu-right">';
            $buttons .= "<a href='javascript:;' onclick='movement("
                . json_encode($data->art_code, JSON_HEX_APOS) . ","
                . json_encode($data->code,     JSON_HEX_APOS) . ","
                . json_encode($data->desc,     JSON_HEX_APOS) . ")' class='dropdown-item'>
                <i data-feather='activity'></i>
                Movement
            </a>";
            $buttons .= '</div></div>';
            return $buttons;
        })
        ->addColumn('article_qty', function ($data) {
            $artilceQty = number_format((float) $data->article_qty, 2);
            return $data->article_qty < 0
                ? "<div class='text-red'>$artilceQty</div>"
                : "<div class='text-hitam'>$artilceQty</div>";
        })
        ->addColumn('status', function ($data) {
            $badges     = ['badge-light-danger', 'badge-light-primary'];
            $statusCode = ['Freeze', 'Active'];
            return "<div class='badge badge-pill ".$badges[$data->status]."'>".$statusCode[$data->status]."</div>";
        })
        ->addColumn('critical_stock', function ($data) {
            $safety = (float) ($data->safety_stock ?? 0);
            if ($data->article_qty < $safety) {
                return "<div class='badge badge-pill badge-light-danger'>Critical</div>";
            }
            return "<div class='badge badge-pill badge-light-primary'>Save</div>";
        })
        ->rawColumns(['action', 'status', 'article_qty', 'critical_stock'])
        ->make(true);
}

public function summary(Request $request)
{
    $code     = strtolower($request->code);
    $name     = strtolower($request->name);
    $group    = strtolower($request->group);
    $supp     = strtolower($request->supp);
    $type     = strtolower($request->type);
    $qty      = $request->qty;
    $operator = $request->opr;
    $location = $request->location;
    $hideEmptyQty = $request->hideEmptyQty;
    $asof     = $request->asof;

  if ($asof) {
    $hasFilter = $location || $code || $name || $type || $supp;
    if (!$hasFilter) {
        return Datatables::of(collect([]))->make(true); // (di summary: return 0 semua)
    }

    $asofYmd   = \Carbon\Carbon::createFromFormat('d-m-Y', $asof)->format('Y-m-d');
    $locParam  = $location ? DB::getPdo()->quote($location) : "NULL";
    $asofParam = DB::getPdo()->quote($asofYmd);

    // basis: HANYA artikel yang punya jejak di warehouse_stock pada lokasi ini
    // (kalau lokasi kosong, ambil semua artikel yang punya stock di mana pun)
    $articleBasis = DB::table('warehouse_stock as ws')
        ->select('ws.article_code')
        ->when($location, fn($q) => $q->where('ws.location_number', $location))
        ->groupBy('ws.article_code');

    $stockSub = DB::table('article as a_asof')
        ->joinSub($articleBasis, 'basis', function ($j) {
            $j->on('basis.article_code', '=', 'a_asof.article_code');
        })
        ->select(
            'a_asof.article_code',
            DB::raw("get_last_qty_new(a_asof.article_code, $asofParam, 'HO', $locParam) as article_qty")
        )
        ->when($code, fn($q) => $q->where('a_asof.article_alternative_code', 'ilike', '%'.$code.'%'))
        ->when($name, fn($q) => $q->where('a_asof.article_desc', 'ilike', '%'.$name.'%'))
        ->when($type, fn($q) => $q->where('a_asof.article_alternative_code', 'ilike', $type.'%'))
        ->when($supp, fn($q) => $q->where('a_asof.third_party', 'ilike', '%'.$supp.'%'));
} else {
        $stockSub = DB::table('warehouse_stock')
            ->select('article_code', DB::raw('sum(article_qty) as article_qty'))
            ->when($location, function ($q) use ($location) {
                $q->where('location_number', $location);
            })
            ->groupBy('article_code');
    }

    $base = DB::table('article')
        ->joinSub($stockSub, 'stock', function ($j) {
            $j->on('stock.article_code', '=', 'article.article_code');
        })
        ->where(function ($query) use ($code, $name, $group, $supp, $type, $operator, $qty, $hideEmptyQty) {
            $code  ? $query->where('article.article_alternative_code', 'ilike', '%'.$code.'%') : '';
            $name  ? $query->where('article.article_desc', 'ilike', '%'.$name.'%') : '';
            $group ? $query->where('article.group_of_material', 'ilike', '%'.$group.'%') : '';
            $supp  ? $query->where('article.third_party', 'ilike', '%'.$supp.'%') : '';
            $type  ? $query->where('article.article_alternative_code', 'ilike', $type.'%') : '';
            $operator ? $query->where('stock.article_qty', $operator, (float)$qty) : '';

            if ($hideEmptyQty) {
                $query->where('stock.article_qty', '>', 0);
            }
        });

    $total    = (clone $base)->count();
    $critical = (clone $base)->whereRaw('stock.article_qty <  coalesce(article.safety_stock,0)')->count();
    $save     = (clone $base)->whereRaw('stock.article_qty >= coalesce(article.safety_stock,0)')->count();
    $empty    = (clone $base)->where('stock.article_qty', '<=', 0)->count();

    return response()->json(compact('total','save','critical','empty'));
}

public function runCheck(Request $request)
{
    $threshold = (float) $request->input('threshold', 0.01);
    $location  = $request->input('location');
    $code      = $request->input('code');
    $name      = $request->input('name');
    $type      = $request->input('type');
    $supp      = $request->input('supp');

    try {
        $params = ['--threshold' => $threshold];
        if ($location) $params['--location'] = $location;
        if ($code)     $params['--code']     = $code;
        if ($name)     $params['--name']     = $name;
        if ($type)     $params['--type']     = $type;
        if ($supp)     $params['--supp']     = $supp;

        Artisan::call('stock:check-anomaly', $params);

        // DEBUG: tangkap output command asli
        $artisanOutput = Artisan::output();

        $anomalies = DB::table('stock_anomaly_log as l')
            ->leftJoin('article as a', 'a.article_code', '=', 'l.article_id')
            ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'l.location_number')
            ->where('l.status', 'OPEN')
            ->when($location, fn($q) => $q->where('l.location_number', $location))
            ->when($code, fn($q) => $q->where('a.article_alternative_code', 'ilike', "%$code%"))
            ->when($name, fn($q) => $q->where('a.article_desc', 'ilike', "%$name%"))
            ->orderByDesc('l.diff')
            ->select('l.*', 'a.article_alternative_code', 'a.article_desc',
                DB::raw("coalesce(loc.location_name, l.location_number) as location_name"))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $anomalies,
            'checked_at' => now()->format('d-m-Y H:i'),
            'debug_artisan_output' => $artisanOutput,   // <-- DEBUG, hapus nanti
            'debug_params_sent' => $params,               // <-- DEBUG, hapus nanti
        ]);
    } catch (\Exception $e) {
        \LogActivity::addToLog('Gagal cek stock anomaly: ' . substr($e->getMessage(), 0, 200));

        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan.',
            'debug_error' => $e->getMessage(),              // <-- DEBUG, hapus nanti
            'debug_file'  => $e->getFile() . ':' . $e->getLine(), // <-- DEBUG, hapus nanti
        ], 500);
    }
}

public function exportAnomaly()
{
    return Excel::download(new StockAnomalyExport, 'stock_anomaly_' . date('YmdHis') . '.xlsx');
}

}
