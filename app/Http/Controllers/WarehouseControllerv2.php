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
        ->where(function($q) {
            $q->whereNull('parent_location')
              ->orWhere('parent_location', '');
        })
        ->orderBy('location_name')
        ->get();

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
                return Datatables::of(collect([]))->make(true);
            }

            $asofYmd   = \Carbon\Carbon::createFromFormat('d-m-Y', $asof)->format('Y-m-d');
            $asofParam = DB::getPdo()->quote($asofYmd);

            // ── Resolusi lokasi ──
            // User pilih parent (012) → cari semua child-nya, sum get_last_qty_new per child
            // User pilih child (038)  → langsung pakai child itu
            // User tidak pilih (ALL)  → sum semua lokasi via get_last_qty_new(..., NULL)

            if ($location) {
                $isParent = DB::table('stock_location_master')
                    ->where('location_code', $location)
                    ->where(function($q) {
                        $q->whereNull('parent_location')
                          ->orWhere('parent_location', '');
                    })
                    ->exists();

                if ($isParent) {
                    $childLocations = DB::table('stock_location_master')
                        ->where('parent_location', $location)
                        ->pluck('location_code');

                    // Parent 012 punya movement sendiri + movement di tiap child → jumlahkan semua
                    $allLocs = $childLocations->push($location);   // 038,039,040,041 + 012

                    $articleBasis = DB::table('warehouse_movement')
                        ->select('artikel_code as article_code')
                        ->where('site_code', 'HO')
                        ->whereIn('location_number', $allLocs)
                        ->groupBy('artikel_code');

                    $locSelects = $allLocs->map(function($loc) use ($asofParam) {
                        $locQ = DB::getPdo()->quote($loc);
                        return "COALESCE(get_last_qty_new(a_asof.article_code, $asofParam, 'HO', $locQ), 0)";
                    })->implode(' + ');

                    $qtyExpr = "($locSelects)";
                } else {
                    // User pilih child langsung
                    $locParam = DB::getPdo()->quote($location);

                    $articleBasis = DB::table('warehouse_movement')
                        ->select('artikel_code as article_code')
                        ->where('site_code', 'HO')
                        ->where('location_number', $location)
                        ->groupBy('artikel_code');

                    $qtyExpr = "get_last_qty_new(a_asof.article_code, $asofParam, 'HO', $locParam)";
                }

            } else {
                // ── FIX BUG #1 ──
                // ALL location (tanpa filter lokasi tapi ADA filter code/name/type/supp)
                // Sebelumnya blok ini tidak ada → $articleBasis & $qtyExpr undefined → Fatal Error.
                $articleBasis = DB::table('warehouse_movement')
                    ->select('artikel_code as article_code')
                    ->where('site_code', 'HO')
                    ->groupBy('artikel_code');

                // get_last_qty_new(article, date, site, NULL) → sum semua lokasi
                $qtyExpr = "get_last_qty_new(a_asof.article_code, $asofParam, 'HO', NULL)";
            }

            // ── FIX BUG #3 ──
            // Filter code/name/type/supp cukup diterapkan sekali di outer query bawah.
            // (INNER joinSub, jadi hasil identik tanpa perlu diulang di sini.)
            $stockSub = DB::table('article as a_asof')
                ->joinSub($articleBasis, 'basis', function ($j) {
                    $j->on('basis.article_code', '=', 'a_asof.article_code');
                })
                ->select(
                    'a_asof.article_code',
                    DB::raw("($qtyExpr) as article_qty")
                );

        } else {
            // Current stock → tetap dari warehouse_stock di parent
            $effectiveLocation = $location;
            if ($location) {
                // Kalau user pilih child → resolve ke parent
                $parent = DB::table('stock_location_master')
                    ->where('location_code', $location)
                    ->value('parent_location');
                if ($parent) $effectiveLocation = $parent;
            }

            $stockSub = DB::table('warehouse_stock')
                ->select('article_code', DB::raw('sum(article_qty) as article_qty'))
                ->where('site_code', 'HO')
                ->when($effectiveLocation, fn($q) => $q->where('location_number', $effectiveLocation))
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
                return Datatables::of(collect([]))->make(true);
            }

            $asofYmd   = \Carbon\Carbon::createFromFormat('d-m-Y', $asof)->format('Y-m-d');
            $asofParam = DB::getPdo()->quote($asofYmd);

            // ── Resolusi lokasi ──
            // User pilih parent (012) → cari semua child-nya, sum get_last_qty_new per child
            // User pilih child (038)  → langsung pakai child itu
            // User tidak pilih (ALL)  → sum semua lokasi via get_last_qty_new(..., NULL)

            if ($location) {
                $isParent = DB::table('stock_location_master')
                    ->where('location_code', $location)
                    ->where(function($q) {
                        $q->whereNull('parent_location')
                          ->orWhere('parent_location', '');
                    })
                    ->exists();

                if ($isParent) {
                    $childLocations = DB::table('stock_location_master')
                        ->where('parent_location', $location)
                        ->pluck('location_code');

                    // Parent 012 punya movement sendiri + movement di tiap child → jumlahkan semua
                    $allLocs = $childLocations->push($location);   // 038,039,040,041 + 012

                    $articleBasis = DB::table('warehouse_movement')
                        ->select('artikel_code as article_code')
                        ->where('site_code', 'HO')
                        ->whereIn('location_number', $allLocs)
                        ->groupBy('artikel_code');

                    $locSelects = $allLocs->map(function($loc) use ($asofParam) {
                        $locQ = DB::getPdo()->quote($loc);
                        return "COALESCE(get_last_qty_new(a_asof.article_code, $asofParam, 'HO', $locQ), 0)";
                    })->implode(' + ');

                    $qtyExpr = "($locSelects)";
                } else {
                    // User pilih child langsung
                    $locParam = DB::getPdo()->quote($location);

                    $articleBasis = DB::table('warehouse_movement')
                        ->select('artikel_code as article_code')
                        ->where('site_code', 'HO')
                        ->where('location_number', $location)
                        ->groupBy('artikel_code');

                    $qtyExpr = "get_last_qty_new(a_asof.article_code, $asofParam, 'HO', $locParam)";
                }

            } else {
                // ALL location → sum semua lokasi via get_last_qty_new(..., NULL)
                // ── FIX BUG #2 ── query $allChildLocs yang tidak terpakai sudah dihapus.
                $articleBasis = DB::table('warehouse_movement')
                    ->select('artikel_code as article_code')
                    ->where('site_code', 'HO')
                    ->groupBy('artikel_code');

                // Untuk ALL: get_last_qty_new(article, date, site, NULL) → sum semua lokasi
                $qtyExpr = "get_last_qty_new(a_asof.article_code, $asofParam, 'HO', NULL)";
            }

            // ── FIX BUG #3 ──
            // Filter code/name/type/supp cukup diterapkan sekali di outer query ($base) bawah.
            $stockSub = DB::table('article as a_asof')
                ->joinSub($articleBasis, 'basis', function ($j) {
                    $j->on('basis.article_code', '=', 'a_asof.article_code');
                })
                ->select(
                    'a_asof.article_code',
                    DB::raw("($qtyExpr) as article_qty")
                );

        } else {
            // Current stock → tetap dari warehouse_stock di parent
            $effectiveLocation = $location;
            if ($location) {
                // Kalau user pilih child → resolve ke parent
                $parent = DB::table('stock_location_master')
                    ->where('location_code', $location)
                    ->value('parent_location');
                if ($parent) $effectiveLocation = $parent;
            }

            $stockSub = DB::table('warehouse_stock')
                ->select('article_code', DB::raw('sum(article_qty) as article_qty'))
                ->where('site_code', 'HO')
                ->when($effectiveLocation, fn($q) => $q->where('location_number', $effectiveLocation))
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

            // Resolusi $location ke parent (accounting anchor) — sama persis dengan
            // logika di CheckStockAnomaly::handle(). Baris di stock_anomaly_log selalu
            // tersimpan dengan location_number hasil fold (parent untuk keluarga WIP),
            // jadi kalau user filter pakai child (mis. 038), query display di bawah
            // harus mencari 012, bukan 038 — kalau tidak, hasilnya selalu kosong
            // meski command barusan berhasil mendeteksi & menyimpan anomaly.
            $locationAnchor = $location;
            if ($location) {
                $parent = DB::table('stock_location_master')
                    ->where('location_code', $location)
                    ->value('parent_location');
                if ($parent) $locationAnchor = $parent;
            }

            $anomalies = DB::table('stock_anomaly_log as l')
                ->leftJoin('article as a', 'a.article_code', '=', 'l.article_id')
                ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'l.location_number')
                ->where('l.status', 'OPEN')
                ->when($locationAnchor, fn($q) => $q->where('l.location_number', $locationAnchor))
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
                // Info tambahan untuk UI: kasih tahu kalau filter lokasi yang dipilih
                // di-resolve ke parent, supaya user tidak bingung kenapa hasil
                // menampilkan lokasi lain dari yang dipilih di form.
                'location_resolved' => $locationAnchor !== $location ? $locationAnchor : null,
            ]);
        } catch (\Exception $e) {
            // ── FIX BUG #4 ──
            // Field debug (artisan output, params, error message, path file) sudah dihapus
            // dari response agar struktur server tidak bocor ke client.
            // Detail error tetap tercatat di log internal.
            \LogActivity::addToLog('Gagal cek stock abnormality: ' . substr($e->getMessage(), 0, 200));

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan.',
            ], 500);
        }
    }

    public function exportAnomaly()
    {
        return Excel::download(new StockAnomalyExport, 'stock_abnormality_' . date('YmdHis') . '.xlsx');
    }

    // ===== API MOBILE — Stock =====

/**
 * Helper: bangun subquery stok (current atau as-of tanggal tertentu),
 * dipakai bareng oleh apiList() dan apiSummary() supaya tidak duplikat.
 */
private function buildStockSub(?string $location, ?string $asof)
{
    if ($asof) {
        $asofYmd   = \Carbon\Carbon::createFromFormat('d-m-Y', $asof)->format('Y-m-d');
        $asofParam = DB::getPdo()->quote($asofYmd);

        if ($location) {
            $isParent = DB::table('stock_location_master')
                ->where('location_code', $location)
                ->where(function ($q) {
                    $q->whereNull('parent_location')->orWhere('parent_location', '');
                })
                ->exists();

            if ($isParent) {
                $childLocations = DB::table('stock_location_master')
                    ->where('parent_location', $location)
                    ->pluck('location_code');
                $allLocs = $childLocations->push($location);

                $articleBasis = DB::table('warehouse_movement')
                    ->select('artikel_code as article_code')
                    ->where('site_code', 'HO')
                    ->whereIn('location_number', $allLocs)
                    ->groupBy('artikel_code');

                $locSelects = $allLocs->map(function ($loc) use ($asofParam) {
                    $locQ = DB::getPdo()->quote($loc);
                    return "COALESCE(get_last_qty_new(a_asof.article_code, $asofParam, 'HO', $locQ), 0)";
                })->implode(' + ');

                $qtyExpr = "($locSelects)";
            } else {
                $locParam = DB::getPdo()->quote($location);
                $articleBasis = DB::table('warehouse_movement')
                    ->select('artikel_code as article_code')
                    ->where('site_code', 'HO')
                    ->where('location_number', $location)
                    ->groupBy('artikel_code');
                $qtyExpr = "get_last_qty_new(a_asof.article_code, $asofParam, 'HO', $locParam)";
            }
        } else {
            $articleBasis = DB::table('warehouse_movement')
                ->select('artikel_code as article_code')
                ->where('site_code', 'HO')
                ->groupBy('artikel_code');
            $qtyExpr = "get_last_qty_new(a_asof.article_code, $asofParam, 'HO', NULL)";
        }

        return DB::table('article as a_asof')
            ->joinSub($articleBasis, 'basis', fn($j) => $j->on('basis.article_code', '=', 'a_asof.article_code'))
            ->select('a_asof.article_code', DB::raw("($qtyExpr) as article_qty"));
    }

    $effectiveLocation = $location;
    if ($location) {
        $parent = DB::table('stock_location_master')->where('location_code', $location)->value('parent_location');
        if ($parent) $effectiveLocation = $parent;
    }

    return DB::table('warehouse_stock')
        ->select('article_code', DB::raw('sum(article_qty) as article_qty'))
        ->where('site_code', 'HO')
        ->when($effectiveLocation, fn($q) => $q->where('location_number', $effectiveLocation))
        ->groupBy('article_code');
}

private function buildStockBase(array $f)
{
    $stockSub = $this->buildStockSub($f['location'] ?? null, $f['asof'] ?? null);

    return DB::table('article')
        ->select(
            'article.article_code',
            'article.article_alternative_code as code',
            'article.article_desc as desc',
            DB::raw("coalesce(ucv.unit_to, article.uom) as uom"),
            'article.safety_stock',
            'article.min_package',
            'group_materials.name as group_name',
            'third_party.nama as cust',
            DB::raw("last_rec_date(article.article_code) as last_rec_date"),
            DB::raw("coalesce(stock.article_qty,0) as article_qty")
        )
        ->leftJoin('group_materials', 'group_materials.code', '=', 'article.group_of_material')
        ->leftJoin('third_party', 'third_party.kode', '=', 'article.third_party')
        ->leftJoin('uom_con_v2 as ucv', function ($j) {
            $j->on('ucv.article_code', '=', 'article.article_code')
              ->on('ucv.supplier_name', '=', 'article.third_party');
        })
        ->joinSub($stockSub, 'stock', fn($j) => $j->on('stock.article_code', '=', 'article.article_code'))
        ->where(function ($q) use ($f) {
            !empty($f['code']) ? $q->where('article.article_alternative_code', 'ilike', '%'.$f['code'].'%') : null;
            !empty($f['name']) ? $q->where('article.article_desc', 'ilike', '%'.$f['name'].'%') : null;
            !empty($f['type']) ? $q->where('article.article_alternative_code', 'ilike', $f['type'].'%') : null;
            !empty($f['supp']) ? $q->where('article.third_party', 'ilike', '%'.$f['supp'].'%') : null;

            if (($f['status'] ?? null) === 'critical') {
                $q->where('stock.article_qty', '<', DB::raw('coalesce(article.safety_stock,0)'));
            } elseif (($f['status'] ?? null) === 'save') {
                $q->where('stock.article_qty', '>=', DB::raw('coalesce(article.safety_stock,0)'));
            } elseif (($f['status'] ?? null) === 'empty') {
                $q->where('stock.article_qty', '<=', 0);
            }
            if (!empty($f['hideEmptyQty'])) {
                $q->where('stock.article_qty', '>', 0);
            }
        });
}

private function extractStockFilters(Request $request): array
{
    return [
        'code'         => $request->code,
        'name'         => $request->name,
        'type'         => $request->type,
        'supp'         => $request->supp,
        'status'       => $request->status,
        'location'     => $request->location,
        'hideEmptyQty' => $request->boolean('hideEmptyQty'),
        'asof'         => $request->asof, // format d-m-Y
    ];
}

// Dropdown lokasi (top-level saja, sama seperti web)
public function apiStockLocations(Request $request)
{
    $locs = DB::table('stock_location_master')
        ->where(function ($q) { $q->whereNull('parent_location')->orWhere('parent_location', ''); })
        ->orderBy('location_name')
        ->select('location_code', 'location_name')
        ->get();

    return response()->json(['status' => 1, 'locations' => $locs]);
}

// Kartu dashboard: total / save / critical / empty
public function apiStockSummary(Request $request)
{
    $f = $this->extractStockFilters($request);

    if ($f['asof'] && !($f['location'] || $f['code'] || $f['name'] || $f['type'] || $f['supp'])) {
        return response()->json(['status' => 0, 'message' => 'Untuk lihat stock per tanggal, pilih minimal 1 filter (Lokasi/Kode/Nama/Type/Supplier).']);
    }

    // status & hideEmptyQty tidak ikut filter dasar summary supaya angka kartu tetap utuh,
    // TAPI hideEmptyQty tetap relevan (toggle mempengaruhi semua angka termasuk total)
    $base = $this->buildStockBase([
        'code' => $f['code'], 'name' => $f['name'], 'type' => $f['type'], 'supp' => $f['supp'],
        'location' => $f['location'], 'asof' => $f['asof'], 'hideEmptyQty' => $f['hideEmptyQty'],
    ]);

    $total    = (clone $base)->count();
    $critical = (clone $base)->whereRaw('stock.article_qty < coalesce(article.safety_stock,0)')->count();
    $save     = (clone $base)->whereRaw('stock.article_qty >= coalesce(article.safety_stock,0)')->count();
    $empty    = (clone $base)->where('stock.article_qty', '<=', 0)->count();

    return response()->json(['status' => 1, 'total' => $total, 'save' => $save, 'critical' => $critical, 'empty' => $empty]);
}

// List stock (paginated)
public function apiStockList(Request $request)
{
    $f = $this->extractStockFilters($request);

    if ($f['asof'] && !($f['location'] || $f['code'] || $f['name'] || $f['type'] || $f['supp'])) {
        return response()->json(['status' => 0, 'message' => 'Untuk lihat stock per tanggal, pilih minimal 1 filter (Lokasi/Kode/Nama/Type/Supplier).']);
    }

    $perPage = min((int) $request->get('per_page', 25), 50);

    $paginator = $this->buildStockBase($f)
        ->orderBy('article.article_desc')
        ->paginate($perPage);

    $locationLabel = 'ALL';
    if ($f['location']) {
        $locationLabel = DB::table('stock_location_master')->where('location_code', $f['location'])->value('location_name') ?? 'ALL';
    }

    $data = collect($paginator->items())->map(function ($row) use ($locationLabel) {
        $safety   = (float) ($row->safety_stock ?? 0);
        $qty      = (float) $row->article_qty;
        $status   = $qty <= 0 ? 'empty' : ($qty < $safety ? 'critical' : 'save');

        return [
            'article_code'             => $row->article_code,
            'article_alternative_code' => $row->code,
            'article_desc'             => $row->desc,
            'uom'                      => $row->uom,
            'qty'                      => $qty,
            'safety_stock'             => $safety,
            'min_package'              => $row->min_package,
            'cust'                     => $row->cust,
            'group_name'               => $row->group_name,
            'last_rec_date'            => $row->last_rec_date,
            'location_label'           => $locationLabel,
            'status'                   => $status,
        ];
    });

    return response()->json([
        'status'       => 1,
        'data'         => $data->values(),
        'current_page' => $paginator->currentPage(),
        'last_page'    => $paginator->lastPage(),
        'total'        => $paginator->total(),
    ]);
}

// Movement per artikel (paginated)
public function apiStockMovement(Request $request)
{
    $articleCode = $request->articleCode;
    $location    = $request->location;
    $siteCode    = 'HO';
    $fromDate    = $request->fromDate ?: date('01-m-Y');
    $toDate      = $request->toDate   ?: date('d-m-Y');
    $inout       = $request->inout;
    $isGlobal    = empty($location);
    $page        = max(1, (int) $request->get('page', 1));
    $perPage     = min((int) $request->get('per_page', 30), 100);

    if (!$articleCode) {
        return response()->json(['status' => 0, 'message' => 'articleCode wajib diisi']);
    }

    // ── Resolusi lokasi MOVEMENT: parent → child, child/biasa → dirinya sendiri ──
    $locationList = [];
    if (!$isGlobal) {
        $childs = DB::table('stock_location_master')
            ->where('parent_location', $location)
            ->pluck('location_code')
            ->toArray();

        if (!empty($childs)) {
            $childs[] = $location;
            $locationList = $childs;
        } else {
            $locationList = [$location];
        }
    }

    // ── Saldo awal — reuse persis logic movement2() ──
    $opening   = $this->resolveOpeningBalance($articleCode, $location, $fromDate, $isGlobal, 0, $locationList);
    $saldoAwal = $opening['qty'];

    $bind = ['art' => $articleCode, 'site' => $siteCode, 'from' => $fromDate, 'to' => $toDate,
             'art_dir' => $articleCode, 'art_qty' => $articleCode];

    $whereLoc = '';
    if (!$isGlobal) {
        $ph = [];
        foreach ($locationList as $i => $loc) {
            $ph[] = ":loc$i";
            $bind["loc$i"] = $loc;
        }
        $whereLoc = "AND m.location_number IN (" . implode(',', $ph) . ")";
    }
    $locationCol = $isGlobal ? "'ALL'" : DB::getPdo()->quote($location);

    $inoutFilter = '';
    if ($inout === 'in')  $inoutFilter = "AND (b.movement_plus > 0 OR b.adj_direction = '+')";
    if ($inout === 'out') $inoutFilter = "AND (b.movement_min  > 0 OR b.adj_direction = '-')";

    // ── SQL PERSIS SAMA dengan movement2() ──
    $sqlku = "
    WITH ledger AS (
        SELECT m.*,
            CASE m.movement_type
                WHEN 'RECEIVING'    THEN (SELECT status FROM receiving_hdr        WHERE rec_number      = m.movement_transnno LIMIT 1)
                WHEN 'TRANSFER'     THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'SUPPLY'       THEN (SELECT status FROM transfer_stock_hdr   WHERE tr_number       = m.movement_transnno LIMIT 1)
                WHEN 'DELIVERY'     THEN (SELECT status FROM delivery_hdr         WHERE delivery_number = m.movement_transnno LIMIT 1)
                WHEN 'RETURN'       THEN (SELECT status FROM dn_return_hdr        WHERE return_number   = m.movement_transnno LIMIT 1)
                WHEN 'REPLACEMENT'  THEN (SELECT status FROM dn_replace_hdr       WHERE replace_number  = m.movement_transnno LIMIT 1)
                WHEN 'ADJUSTMENT'   THEN (SELECT status FROM stock_adjustment_hdr WHERE adj_code        = m.movement_transnno LIMIT 1)
                WHEN 'DN SEMENTARA' THEN (SELECT status FROM temporary_dn_hdr     WHERE tdn_number      = m.movement_transnno LIMIT 1)
                WHEN 'DN UMUM'      THEN (SELECT status FROM dn_general_hdr       WHERE tdn_number      = m.movement_transnno LIMIT 1)
                ELSE NULL
            END AS hdr_status,
            (CASE WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT') THEN
                (SELECT det.direction FROM stock_adjustment_det det
                 WHERE det.adj_code = m.movement_transnno AND det.article_code = :art_dir LIMIT 1)
            END) AS adj_direction,
            (CASE WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT') THEN
                (SELECT det.qty_adjustment FROM stock_adjustment_det det
                 WHERE det.adj_code = m.movement_transnno AND det.article_code = :art_qty LIMIT 1)
            END) AS adj_qty,
            CASE
                WHEN m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                     AND m.movement_plus = 0 AND m.movement_min = 0
                THEN (SELECT CASE WHEN det.direction = '-' THEN -det.qty_adjustment ELSE det.qty_adjustment END
                      FROM stock_adjustment_det det
                      WHERE det.adj_code = m.movement_transnno AND det.article_code = m.artikel_code LIMIT 1)
                     * CASE WHEN m.movement_type = 'CANCEL ADJUSTMENT' THEN -1 ELSE 1 END
                ELSE (m.movement_plus - m.movement_min)
            END AS net_value
        FROM warehouse_movement m
        WHERE m.artikel_code = :art
          AND m.site_code = :site
          $whereLoc
          AND TO_DATE(m.movement_date,'dd-mm-yyyy')
              BETWEEN TO_DATE(:from,'dd-mm-yyyy') AND TO_DATE(:to,'dd-mm-yyyy')
          AND m.movement_type NOT LIKE 'CANCEL %'
          AND m.movement_type NOT LIKE 'DELETE%'
          AND m.movement_type NOT LIKE 'REVISI %'
          AND m.movement_type NOT IN ('RETURN-CANCEL','RETURN-REVERSE')
          AND NOT (m.movement_type IN ('ADJUSTMENT','CANCEL ADJUSTMENT')
                   AND EXISTS (SELECT 1 FROM stock_adjustment_hdr h
                               WHERE h.adj_code = m.movement_transnno AND h.adj_type = 'OPENING BALANCE'))
    ),
    dedup AS (
        SELECT l.*,
            ROW_NUMBER() OVER (
                PARTITION BY l.artikel_code, l.movement_transnno, l.location_number
                ORDER BY l.created_at DESC, l.movement_code DESC
            ) AS rn
        FROM ledger l
        WHERE l.hdr_status IS DISTINCT FROM '5'
    ),
    kept AS (
        SELECT d.*, d.net_value AS counted_net
        FROM dedup d
        WHERE d.rn = 1
    ),
    base AS (
        SELECT k.*,
            $saldoAwal + SUM(k.counted_net) OVER (
                ORDER BY TO_DATE(k.movement_date,'dd-mm-yyyy'), k.movement_code
            ) AS balanceqty_calc,
            $saldoAwal + SUM(k.counted_net) OVER (
                ORDER BY TO_DATE(k.movement_date,'dd-mm-yyyy'), k.movement_code
            ) - k.counted_net AS last_qty_calc
        FROM kept k
    )
    SELECT
        b.movement_code, b.artikel_code, b.artikel_desc,
        b.movement_plus - b.movement_min AS qty,
        b.movement_price, b.movement_date, b.movement_desc, b.movement_type,
        b.movement_min, b.movement_plus, b.movement_transnno, b.partner_type,
        b.adj_direction, b.adj_qty, b.hdr_status,
        b.movement_to AS dest_code,
        $locationCol AS location_number,
        CASE
            WHEN b.partner_type = 'SUPP' THEN (SELECT nama FROM third_party WHERE kode = b.movement_from)
            ELSE (SELECT location_name FROM stock_location_master WHERE location_code = b.movement_from)
        END AS mv_from,
        CASE
            WHEN b.partner_type = 'CUST' THEN (SELECT nama FROM third_party WHERE kode = b.movement_to)
            ELSE (SELECT location_name FROM stock_location_master WHERE location_code = b.movement_to)
        END AS mv_to,
        b.balanceqty_calc AS balanceqty,
        b.last_qty_calc   AS last_qty,
        b.site_code, b.created_at,
        b.hdr_status AS trx_status
    FROM base b
    WHERE 1=1
    $inoutFilter
    ORDER BY TO_DATE(b.movement_date,'dd-mm-yyyy'), b.movement_code";

    $data = DB::select($sqlku, $bind);

    $artikelDesc = $data[0]->artikel_desc ?? null;
    if (!$artikelDesc) {
        $row = DB::table('article')->where('article_code', $articleCode)
            ->selectRaw("concat(article_alternative_code,'-',article_desc) as desc")->first();
        $artikelDesc = $row->desc ?? $articleCode;
    }

    $lastRow    = end($data); reset($data);
    $saldoAkhir = $lastRow ? (float) $lastRow->balanceqty : $saldoAwal;

    $totalIn = 0.0; $totalOut = 0.0;
    foreach ($data as $d) {
        [$in, $out] = $this->splitQty($d);
        $totalIn  += $in;
        $totalOut += $out;
    }

    // ── Bentuk baris JSON dari tiap movement (reuse splitQty, refMap, reklasifikasi type) ──
    $mapStatus = [
        '1'  => 'NEW',      '2'  => 'VALIDATE', '3'  => 'APPROVED', '4' => 'POSTED',
        '5'  => 'CANCELED', '7'  => 'REVISED',  '8'  => 'RECEIVED', '10' => 'REVISI',
    ];

    $rows = collect($data)->map(function ($d) use ($mapStatus) {
        [$in, $out] = $this->splitQty($d);

        $type = $d->movement_type;
        if (in_array($type, ['TRANSFER', 'SUPPLY'], true)) {
            if ((float) ($d->movement_min ?? 0) > 0) {
                $type = 'SUPPLY';
            } else {
                $dest = (string) ($d->dest_code ?? '');
                $type = in_array($dest, self::RETURN_LOCS, true) ? 'RETURN' : 'TRANSFER';
            }
        } elseif ($type === 'RETURN') {
            $type = 'DN RETURN';
        } elseif ($type === 'REPLACEMENT') {
            $type = 'DN REPLACEMENT';
        }

        $ref = $this->refInfo($d->movement_type, $d->movement_transnno);

return [
    'movement_date'     => $d->movement_date,
    'movement_type'     => $type,
    'movement_transnno' => $d->movement_transnno,
    'ref_openable'      => $ref['openable'],
    'ref_url'           => $ref['url'],
    'ref_enc_id'         => $ref['enc_id'],   // ← TAMBAH
    'ref_doc_kind'       => $ref['doc_kind'], // ← TAMBAH
    'mv_from'           => $d->mv_from,
    'mv_to'             => $d->mv_to,
    'inout'             => $in > 0 ? 'in' : ($out > 0 ? 'out' : ''),
    'qty_in'            => $in,
    'qty_out'           => $out,
    'opening'           => (float) $d->last_qty,
    'balance'           => (float) $d->balanceqty,
    'movement_desc'     => $d->movement_desc,
    'trx_status'        => $d->trx_status,
    'trx_status_label'  => $mapStatus[$d->trx_status] ?? null,
    'created_at'        => $d->created_at,
    'is_summary'        => false,
];
    });

    // ── Baris Saldo Awal & Saldo Akhir, taruh di ujung list (bukan dipaginate) ──
    $rowAwal = [
        'movement_date' => '', 'movement_type' => 'OPENING', 'movement_transnno' => $opening['adj_code'],
        'ref_openable' => false, 'ref_url' => null, 'mv_from' => null, 'mv_to' => null, 'inout' => '',
        'qty_in' => 0, 'qty_out' => 0, 'opening' => null, 'balance' => $saldoAwal,
        'movement_desc' => $opening['note'] ?: 'Saldo Awal', 'trx_status' => null, 'trx_status_label' => null,
        'created_at' => $opening['authorized_at'], 'is_summary' => true, 'summary_label' => 'SALDO AWAL',
    ];
    $rowAkhir = [
        'movement_date' => $toDate, 'movement_type' => 'CLOSING', 'movement_transnno' => null,
        'ref_openable' => false, 'ref_url' => null, 'mv_from' => null, 'mv_to' => null, 'inout' => '',
        'qty_in' => $totalIn, 'qty_out' => $totalOut, 'opening' => $saldoAwal, 'balance' => $saldoAkhir,
        'movement_desc' => 'Saldo Akhir ('.$toDate.')', 'trx_status' => null, 'trx_status_label' => null,
        'created_at' => null, 'is_summary' => true, 'summary_label' => 'SALDO AKHIR',
    ];

    // ── Pagination manual atas baris movement (Saldo Awal/Akhir tidak ikut dipaginate,
    //    selalu tampil — Awal di halaman 1, Akhir di halaman terakhir) ──
    $total    = $rows->count();
    $lastPage = max(1, (int) ceil($total / $perPage));
    $paged    = $rows->forPage($page, $perPage)->values();

    $out = [];
    if ($page === 1) $out[] = $rowAwal;
    foreach ($paged as $r) $out[] = $r;
    if ($page === $lastPage) $out[] = $rowAkhir;

    return response()->json([
        'status'        => 1,
        'artikel_desc'  => $artikelDesc,
        'data'          => $out,
        'current_page'  => $page,
        'last_page'     => $lastPage,
        'total'         => $total,
    ]);
}

/**
 * Versi non-HTML dari renderRefLink() — reuse $refMap yang sama,
 * tapi return url mentah + flag openable untuk konsumsi mobile.
 */
private function refInfo(?string $type, ?string $ref): array
{
    if (!$ref || !isset($this->refMap[$type])) {
        return ['openable' => false, 'url' => null, 'enc_id' => null, 'doc_kind' => null];
    }
    [$table, $col, $routeName] = $this->refMap[$type];
    $row = DB::table($table)->where($col, $ref)->select('id', 'status')->first();

    if (!$row || (string) $row->status === '5') {
        return ['openable' => false, 'url' => null, 'enc_id' => null, 'doc_kind' => null];
    }

    $encId = Crypt::encryptString($row->id);

    // Tabel transfer_stock_hdr menaungi TRANSFER, SUPPLY, dan RETURN (via RETURN_LOCS
    // reklasifikasi) — semuanya bisa dibuka via TransferDetailPage yang sudah ada di app.
    $docKind = ($table === 'transfer_stock_hdr') ? 'transfer' : null;

    return [
        'openable' => true,
        'url'      => route($routeName, ['id' => $encId]),
        'enc_id'   => $docKind ? $encId : null,   // hanya diisi kalau ada halaman in-app
        'doc_kind' => $docKind,                    // 'transfer' | null (nanti bisa 'receiving', dll)
    ];
}

}