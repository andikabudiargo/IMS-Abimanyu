<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use DataTables;
use DB;
use Excel;
use AppHelpers;
use App\Imports\StockAdjustmentImport;
use App\Exports\StockAdjustmentExport;

class StockAdjustmentController extends Controller
{
    private $title;
    private $moduleCode;

    public function __construct()
    {
        $this->title      = "Stock Adjustment";
        $this->moduleCode = "ADJ";
    }

    // =========================================================================
    //  COLUMN DEFINITIONS
    // =========================================================================

    public function getTableColumn()
    {
        $kolom = [
            ['data' => 'action',        'name' => 'action',        'title' => 'Action',        'orderable' => false, 'searchable' => false],
            ['data' => 'adj_code',      'name' => 'adj_code',      'title' => 'Adjustment Code'],
            ['data' => 'adj_date',      'name' => 'adj_date',      'title' => 'Date'],
            ['data' => 'adj_type',      'name' => 'adj_type',      'title' => 'Type'],
            ['data' => 'location_name', 'name' => 'location_name', 'title' => 'Location'],
            ['data' => 'description',   'name' => 'description',   'title' => 'Description'],
            ['data' => 'status',        'name' => 'status',        'title' => 'Status',        'orderable' => false, 'searchable' => false],
            ['data' => 'note',          'name' => 'note',          'title' => 'Note',          'visible' => false],
            ['data' => 'created_by',    'name' => 'created_by',    'title' => 'Created By'],
            ['data' => 'created_at',    'name' => 'created_at',    'title' => 'Created At'],
            ['data' => 'authorized_by', 'name' => 'authorized_by', 'title' => 'Authorized By', 'orderable' => false, 'searchable' => false],
            ['data' => 'authorized_at', 'name' => 'authorized_at', 'title' => 'Authorized At', 'orderable' => false, 'searchable' => false],
            ['data' => 'updated_at',    'name' => 'updated_at',    'title' => 'Updated At'],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColumnDetail()
    {
        $kolom = [
            ['data' => 'adj_code',                'name' => 'adj_code',                'title' => 'Adjustment Code'],
            ['data' => 'adj_date',                'name' => 'adj_date',                'title' => 'Date'],
            ['data' => 'adj_type',                'name' => 'adj_type',                'title' => 'Type'],
            ['data' => 'location_name',           'name' => 'location_name',           'title' => 'Location'],
            ['data' => 'description',             'name' => 'description',             'title' => 'Description'],
            ['data' => 'status',                  'name' => 'status',                  'title' => 'Status',           'orderable' => false, 'searchable' => false],
            ['data' => 'direction',               'name' => 'direction',               'title' => 'Direction (-/+)',   'orderable' => false, 'searchable' => false],
            ['data' => 'article_alternative_code','name' => 'article_alternative_code','title' => 'Article Code'],
            ['data' => 'uom',                     'name' => 'uom',                     'title' => 'UoM'],
            ['data' => 'stock_before',            'name' => 'stock_before',            'title' => 'Stock Before'],
            ['data' => 'qty_adjustment',          'name' => 'qty_adjustment',          'title' => 'Qty Adjustment'],
            ['data' => 'stock_after',             'name' => 'stock_after',             'title' => 'Stock After'],
            ['data' => 'notes',                   'name' => 'notes',                   'title' => 'Notes'],
            ['data' => 'created_by',              'name' => 'created_by',              'title' => 'Created By'],
            ['data' => 'created_at',              'name' => 'created_at',              'title' => 'Created At'],
            ['data' => 'authorized_by',           'name' => 'authorized_by',           'title' => 'Authorized By',    'orderable' => false, 'searchable' => false],
            ['data' => 'authorized_at',           'name' => 'authorized_at',           'title' => 'Authorized At',    'orderable' => false, 'searchable' => false],
        ];
        return json_encode($kolom, true);
    }

    // =========================================================================
    //  INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $data['title']       = $this->title;
        $data['subtitle']    = $this->title;
        $data['kolom']       = $this->getTableColumn();
        $data['kolomDetail'] = $this->getTableColumnDetail();
        $data['locations']   = DB::table('stock_location_master')->orderBy('location_name')->get();
        $data['status']      = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $data['types']       = ['SYSTEM CORRECTION','OPENING BALANCE','FOUND/UNRECORDED','OTHER'];

        return view("stockAdjustment.index", $data);
    }

    // =========================================================================
    //  DATATABLE — SUMMARY
    // =========================================================================

    public function list(Request $request)
{
    $user       = Auth::user();
    $username   = $user->username;
    $userDepts  = DB::table('user_dept')->where('username', $username)->pluck('dept')->toArray();
    $privileged = Auth::user()->hasAnyRole(['Superuser','accounting','finance']);
    $canPost    = Auth::user()->hasAnyRole(['Superuser','accounting']);
    $canCancel  = Auth::user()->hasAnyRole(['Superuser','accounting']); // hanya Superuser & accounting

    $searchAdj      = strtolower($request->searchAdj ?? '');
    $searchType     = $request->searchType;
    $searchStatus   = $request->searchStatus;
    $adjDate        = $request->adjDate;
    $searchLocation = $request->searchLocation;
    $searchDesc     = strtolower($request->searchDesc ?? '');

    [$fromDate, $toDate] = $this->parseDateRange($adjDate);

    $query = DB::table('stock_adjustment_hdr as h')
        ->leftJoin('stock_location_master as loc', 'loc.location_code', '=', 'h.location_code')
        ->where('h.status', '<>', '5')
        ->where(function ($q) use ($searchAdj, $searchType, $searchStatus, $adjDate, $fromDate, $toDate, $searchLocation, $searchDesc) {
            if ($searchAdj)      $q->where('h.adj_code',      'ilike', "%{$searchAdj}%");
            if ($searchType)     $q->where('h.adj_type',      $searchType);
            if ($searchStatus)   $q->where('h.status',        $searchStatus);
            if ($adjDate)        $q->whereBetween(DB::raw("to_date(h.adj_date,'DD-MM-YYYY')"), [$fromDate, $toDate]);
            if ($searchLocation) $q->where('h.location_code', $searchLocation);
            if ($searchDesc)     $q->where('h.description',   'ilike', "%{$searchDesc}%");
        });

    if (!$privileged) {
        $query->whereIn('loc.dept_code', $userDepts);
    }

    $data = $query->select('h.*', 'loc.location_name', 'loc.dept_code as loc_dept')
        ->orderBy('h.id', 'desc')
        ->get();

    return Datatables::of($data)
        ->addColumn('action', function ($row) use ($username, $privileged, $canPost, $canCancel, $userDepts) {
            $st        = $row->status;
            $isCreator = ($row->created_by === $username);

            // Edit & Delete hanya untuk status DRAFT/VALIDATED/APPROVED (1/2/3)
            $canEditDelete = false;
            if (in_array($st, ['1','2','3'])) {
                $canEditDelete = $isCreator || $privileged;
            }

            $enc = Crypt::encryptString($row->id);

            $btns  = '<div class="d-inline-flex">';
            $btns .= '<a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>';
            $btns .= '<div class="dropdown-menu dropdown-menu-right">';

            // ── POSTING — Superuser/accounting, status 1/2/3 ──────────────
            if ($canPost && in_array($st, ['1','2','3'])) {
                $btns .= "<a href='javascript:;'
                            class='dropdown-item text-success'
                            data-size='sm'
                            data-ajax-delete='true'
                            data-confirm='Yakin ingin posting?|Stok akan berubah setelah posting.'
                            data-confirm-yes='document.getElementById(\"delete-form-{$row->id}\").submit();'
                            data-modal-id='{$row->id}'
                            data-url='" . route('stockAdjustment.posting', ['id' => $enc]) . "'>
                            <i data-feather='check-circle' class='feather-14'></i>
                            <span>" . __('Posting') . "</span>
                          </a>";
            }

            // ── EDIT — creator atau privileged, belum posted/canceled ─────
            if ($canEditDelete) {
                $btns .= '<a href="' . route('stockAdjustment.edit', ['id' => $enc]) . '" class="dropdown-item">
                            <i data-feather="edit-2"></i>
                            <span>' . __('Edit') . '</span>
                          </a>';
            }

            // ── DETAIL — selalu tampil ────────────────────────────────────
            $btns .= '<a href="' . route('stockAdjustment.show', ['id' => $enc]) . '" class="dropdown-item">
                        <i data-feather="list"></i>
                        <span>' . __('Detail') . '</span>
                      </a>';

            // ── DELETE — creator atau privileged, belum posted/canceled ───
            if ($canEditDelete) {
                $btns .= "<a href='javascript:;'
                            class='dropdown-item text-danger'
                            data-size='sm'
                            data-ajax-delete='true'
                            data-confirm='Yakin ingin hapus?|Data tidak bisa dikembalikan.'
                            data-confirm-yes='document.getElementById(\"delete-form-{$row->id}\").submit();'
                            data-modal-id='{$row->id}'
                            data-url='" . route('stockAdjustment.destroy', ['id' => $enc]) . "'>
                            <i data-feather='trash-2' class='feather-14'></i>
                            <span>" . __('Delete') . "</span>
                          </a>";
            }

            // ── CANCEL — Superuser/accounting, HANYA status POSTED (4) ───
            if ($canCancel && $st === '4') {
                $btns .= "<a href='javascript:;'
                            class='dropdown-item text-danger'
                            id='cancelReasonButton'
                            data-toggle='modal'
                            data-target='#reasonModalCancel'
                            data-href='" . route('stockAdjustment.cancel', ['id' => $enc]) . "'>
                            <i data-feather='x-circle' class='feather-14'></i>
                            <span>" . __('Cancel') . "</span>
                          </a>";
            }

            // ── PRINT — selalu tampil ─────────────────────────────────────
            $btns .= '<a href="' . route('stockAdjustment.print', ['id' => $enc]) . '" target="_blank" class="dropdown-item">
                        <i data-feather="printer"></i>
                        <span>' . __('Print') . '</span>
                      </a>';

            $btns .= '</div></div>';
            return $btns;
        })
        ->addColumn('status', function ($row) {
            $badges = ['1'=>'badge-primary','2'=>'badge-info','3'=>'badge-warning','4'=>'badge-success','5'=>'badge-danger'];
            $labels = ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
            $b = $badges[$row->status] ?? 'badge-secondary';
            $l = $labels[$row->status] ?? '-';
            return "<div class='badge {$b}'>{$l}</div>";
        })
        ->rawColumns(['action','status'])
        ->make(true);
}

    // =========================================================================
    //  DATATABLE — DETAIL
    // =========================================================================

    public function listDetail(Request $request)
    {
        $user       = Auth::user();
        $userDepts  = DB::table('user_dept')->where('username', $user->username)->pluck('dept')->toArray();
        $privileged = Auth::user()->hasAnyRole(['Superuser','accounting','finance']);

        $searchAdj      = strtolower($request->searchAdj ?? '');
        $searchType     = $request->searchType;
        $searchStatus   = $request->searchStatus;
        $adjDate        = $request->adjDate;
        $searchLocation = $request->searchLocation;
        $searchDesc     = strtolower($request->searchDesc ?? '');

        [$fromDate, $toDate] = $this->parseDateRange($adjDate);

        $query = DB::table('stock_adjustment_det as d')
            ->leftJoin('stock_adjustment_hdr as h',   'h.adj_code',      '=', 'd.adj_code')
            ->leftJoin('article as a',                'a.article_code',  '=', 'd.article_code')
            ->leftJoin('stock_location_master as loc', 'loc.location_code','=', 'h.location_code')
            ->where('h.status', '<>', '5')
            ->where(function ($q) use ($searchAdj,$searchType,$searchStatus,$adjDate,$fromDate,$toDate,$searchLocation,$searchDesc) {
                if ($searchAdj)      $q->where('h.adj_code',     'ilike', "%{$searchAdj}%");
                if ($searchType)     $q->where('h.adj_type',     $searchType);
                if ($searchStatus)   $q->where('h.status',       $searchStatus);
                if ($adjDate)        $q->whereBetween(DB::raw("to_date(h.adj_date,'DD-MM-YYYY')"), [$fromDate, $toDate]);
                if ($searchLocation) $q->where('h.location_code',$searchLocation);
                if ($searchDesc)     $q->where('h.description',  'ilike', "%{$searchDesc}%");
            });

        if (!$privileged) {
            $query->whereIn('loc.dept_code', $userDepts);
        }

        $data = $query->select(
            'h.adj_code','h.adj_date','h.adj_type','h.description','h.status',
            'h.created_by','h.created_at','h.authorized_by','h.authorized_at',
            'd.id','d.article_code','d.direction','d.uom',
            'd.stock_before','d.qty_adjustment','d.stock_after','d.notes',
            'a.article_alternative_code','a.article_desc',
            'loc.location_name'
        )->orderBy('d.id')->get();

        return Datatables::of($data)
            ->addColumn('status', function ($row) {
                $badges = ['1'=>'badge-primary','2'=>'badge-info','3'=>'badge-warning','4'=>'badge-success','5'=>'badge-danger'];
                $labels = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
                return "<div class='badge ".($badges[$row->status]??'badge-secondary')."'>".($labels[$row->status]??'-')."</div>";
            })
            ->addColumn('direction', function ($row) {
                return $row->direction === '+'
                    ? "<span class='badge badge-success'>Stock In (+)</span>"
                    : "<span class='badge badge-danger'>Stock Out (−)</span>";
            })
            ->rawColumns(['status','direction'])
            ->make(true);
    }

    // =========================================================================
    //  CREATE
    // =========================================================================

    public function create(Request $request)
    {
        $user      = Auth::user();
        $userDepts = DB::table('user_dept')->where('username', $user->username)->pluck('dept')->toArray();
        $privileged = Auth::user()->hasAnyRole(['Superuser','accounting','finance']);

        $data['title']            = "Create $this->title";
        $data['subtitle']         = "Create $this->title";
        $data['oEdit']            = false;
        $data['currentDateValue'] = date('d-m-Y');

        $data['locations'] = DB::table('stock_location_master')
            ->when(!$privileged, fn($q) => $q->whereIn('dept_code', $userDepts))
            ->orderBy('location_name')->get();

        $data['types'] = ['SYSTEM CORRECTION','OPENING BALANCE','FOUND/UNRECORDED','OTHER'];

        return view("stockAdjustment.create", $data);
    }

    // =========================================================================
    //  STORE
    // =========================================================================

    public function store(Request $request)
{
    $username    = Auth::user()->username;
    $articles    = json_decode($request->articles);
    $adjDate     = $request->adjDate;
    $adjType     = $request->adjType;
    $location    = $request->location;
    $description = $request->description;
    $note        = $request->note;
    $periode     = $request->periode;
    $status      = '1';
    $title       = "Save $this->title";

    $errors = [];
    if (!$adjDate)        $errors[] = "Adjustment Date harus diisi.";
    if (!$adjType)        $errors[] = "Adjustment Type harus dipilih.";
    if (!$location)       $errors[] = "Location harus dipilih.";
    if (!$periode)        $errors[] = "Periode harus dipilih.";
    if (empty($articles)) $errors[] = "Artikel harus diisi.";

    if ($errors) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
    }

    $itemErrors = [];
    foreach ($articles as $val) {
        if ((float)$val->qty_adjustment == 0)
            $itemErrors[] = "Qty Adjustment untuk artikel {$val->article_code} tidak boleh 0.";
        if ((float)$val->stock_after < 0)
            $itemErrors[] = "Stock after untuk artikel {$val->article_code} tidak boleh negatif.";
    }
    if ($itemErrors) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>$itemErrors,'alert'=>'error']);
    }

    AppHelpers::resetCode($this->moduleCode);
    $adjCode = $this->getLastCode($this->moduleCode);

    DB::beginTransaction();
    try {
        DB::table('stock_adjustment_hdr')->insert([
            'adj_code'      => $adjCode,
            'adj_date'      => $adjDate,
            'adj_type'      => $adjType,
            'location_code' => $location,
            'description'   => $description,
            'note'          => $note,
            'periode'       => $periode,
            'direction'     => $this->summarizeDirection($articles), // ringkasan saja
            'status'        => $status,
            'created_by'    => $username,
            'updated_by'    => $username,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $dataSet = [];
        foreach ($articles as $val) {
            $dataSet[] = [
                'adj_code'       => $adjCode,
                'article_code'   => $val->article_code,
                'uom'            => $val->uom,
                'direction'      => $val->direction,   // per-artikel, dari hasil hitung frontend
                'stock_before'   => $val->stock_before,
                'qty_adjustment' => $val->qty_adjustment,
                'stock_after'    => $val->stock_after,
                'notes'          => $val->notes ?? null,
                'created_by'     => $username,
                'updated_by'     => $username,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];
        }
        DB::table('stock_adjustment_det')->insert($dataSet);

        DB::commit();
        $message = "$title $adjCode berhasil disimpan.";
        \LogActivity::addToLog($title, "username: $username | $message");

        return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','adjCode'=>$adjCode,'oEdit'=>true]);

    } catch (\Exception $e) {
        DB::rollBack();
        $message = "$title gagal disimpan: " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username | $message");
        return response()->json(['status'=>0,'title'=>$title,'message'=>[$message],'alert'=>'error']);
    }
}

    // =========================================================================
    //  SHOW (DETAIL)
    // =========================================================================

    public function show(Request $request)
    {
        $id       = Crypt::decryptString($request->id);
        $username = Auth::user()->username;

        $data['title']    = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('stock_adjustment_hdr as h')
            ->leftJoin('stock_location_master as loc','loc.location_code','=','h.location_code')
            ->where('h.id', $id)
            ->select('h.*','loc.location_name')
            ->first();

        if (!$data['header']) {
            return redirect()->back()->with(['title'=>'Detail','alert'=>'warning','message'=>'Data tidak ditemukan.']);
        }

        $data['details'] = DB::table('stock_adjustment_det as d')
            ->leftJoin('article as a','a.article_code','=','d.article_code')
            ->where('d.adj_code', $data['header']->adj_code)
            ->select('d.*','a.article_alternative_code','a.article_desc')
            ->orderBy('d.id')
            ->get();

        $data['encId']   = Crypt::encryptString($id);
        $data['canEdit'] = in_array($data['header']->status, ['1','2','3'])
                           && ($data['header']->created_by === $username || Auth::user()->hasAnyRole(['Superuser','accounting','finance']));
        $data['canPost'] = Auth::user()->hasAnyRole(['Superuser','accounting']);

        return view("stockAdjustment.show", $data);
    }

    // =========================================================================
    //  EDIT
    // =========================================================================

    public function edit(Request $request)
    {
        $id        = Crypt::decryptString($request->id);
        $username  = Auth::user()->username;
        $user      = Auth::user();
        $userDepts = DB::table('user_dept')->where('username', $username)->pluck('dept')->toArray();
        $privileged = Auth::user()->hasAnyRole(['Superuser','accounting','finance']);

        $header = DB::table('stock_adjustment_hdr')->where('id', $id)->first();

        if (!$header) {
            return redirect()->back()->with(['title'=>'Edit','alert'=>'warning','message'=>'Data tidak ditemukan.']);
        }

        // Cegah edit kalau sudah POSTED atau CANCELED
        if (in_array($header->status, ['4','5'])) {
            return redirect()->back()->with(['title'=>'Edit','alert'=>'warning','message'=>'Data sudah '.($header->status==='4'?'POSTED':'CANCELED').', tidak bisa diedit.']);
        }

        // Cegah edit kalau bukan creator atau bukan privileged
        if ($header->created_by !== $username && !$privileged) {
            return redirect()->back()->with(['title'=>'Edit','alert'=>'warning','message'=>'Anda tidak berwenang mengedit data ini.']);
        }

        $data['title']    = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";
        $data['header']   = $header;
        $data['encId']    = Crypt::encryptString($id);

        $data['details'] = DB::table('stock_adjustment_det as d')
            ->leftJoin('article as a','a.article_code','=','d.article_code')
            ->where('d.adj_code', $header->adj_code)
            ->select('d.*','a.article_alternative_code','a.article_desc',
                DB::raw("(select string_agg(unit_to,',' order by unit_from) from uom_con_v2 where article_code = d.article_code) as uom_member")
            )
            ->orderBy('d.id')
            ->get();

        $data['locations'] = DB::table('stock_location_master')
            ->when(!$privileged, fn($q) => $q->whereIn('dept_code', $userDepts))
            ->orderBy('location_name')->get();

        $data['types']            = ['SYSTEM CORRECTION','OPENING BALANCE','FOUND/UNRECORDED','OTHER'];
        $data['currentDateValue'] = date('d-m-Y');

        return view("stockAdjustment.edit", $data);
    }

    // =========================================================================
    //  UPDATE
    // =========================================================================

    public function update(Request $request)
{
    $username    = Auth::user()->username;
    $articles    = json_decode($request->articles);
    $adjCode     = $request->adjCode;
    $adjDate     = $request->adjDate;
    $adjType     = $request->adjType;
    $location    = $request->location;
    $description = $request->description;
    $note        = $request->note;
    $periode     = $request->periode;
    $title       = "Update $this->title";

    $currentStatus = DB::table('stock_adjustment_hdr')
        ->where('adj_code', $adjCode)->value('status');

    if (in_array($currentStatus, ['4','5'])) {
        return response()->json(['status'=>0,'title'=>$title,
            'message'=>['Data sudah '.($currentStatus==='4'?'POSTED':'CANCELED').', tidak bisa diubah.'],
            'alert'=>'error']);
    }

    $errors = [];
    if (!$adjDate)        $errors[] = "Adjustment Date harus diisi.";
    if (!$adjType)        $errors[] = "Adjustment Type harus dipilih.";
    if (!$location)       $errors[] = "Location harus dipilih.";
    if (!$periode)        $errors[] = "Periode harus dipilih.";
    if (empty($articles)) $errors[] = "Artikel harus diisi.";
    if ($errors) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
    }

    $itemErrors = [];
    foreach ($articles as $val) {
        if ((float)$val->qty_adjustment == 0)
            $itemErrors[] = "Qty Adjustment untuk artikel {$val->article_code} tidak boleh 0.";
        if ((float)$val->stock_after < 0)
            $itemErrors[] = "Stock after untuk artikel {$val->article_code} tidak boleh negatif.";
    }
    if ($itemErrors) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>$itemErrors,'alert'=>'error']);
    }

    DB::beginTransaction();
    try {
        DB::table('stock_adjustment_hdr')->where('adj_code', $adjCode)->update([
            'adj_date'      => $adjDate,
            'adj_type'      => $adjType,
            'location_code' => $location,
            'description'   => $description,
            'note'          => $note,
            'periode'       => $periode,
            'direction'     => $this->summarizeDirection($articles),
            'status'        => '1',
            'updated_by'    => $username,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $keepCodes = array_map(fn($v) => $adjCode . $v->article_code, $articles);
        DB::table('stock_adjustment_det')
            ->where('adj_code', $adjCode)
            ->whereNotIn(DB::raw("CONCAT(adj_code, article_code)"), $keepCodes)
            ->delete();

        foreach ($articles as $val) {
            DB::table('stock_adjustment_det')->updateOrInsert(
                ['adj_code'=>$adjCode, 'article_code'=>$val->article_code],
                [
                    'uom'            => $val->uom,
                    'direction'      => $val->direction,
                    'stock_before'   => $val->stock_before,
                    'qty_adjustment' => $val->qty_adjustment,
                    'stock_after'    => $val->stock_after,
                    'notes'          => $val->notes ?? null,
                    'updated_by'     => $username,
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]
            );
        }

        DB::commit();
        $message = "$title $adjCode berhasil diperbarui.";
        \LogActivity::addToLog($title, "username: $username | $message");
        return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','adjCode'=>$adjCode,'oEdit'=>true]);

    } catch (\Exception $e) {
        DB::rollBack();
        $message = "$title gagal: " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username | $message");
        return response()->json(['status'=>0,'title'=>$title,'message'=>[$message],'alert'=>'error']);
    }
}

// =========================================================================
//  POSTING — hanya superuser / accounting (backdate-safe)
// =========================================================================

public function posting(Request $request)
{
    $username = Auth::user()->username;
    $id       = Crypt::decryptString($request->id);
    $title    = "Posting $this->title";
    $siteCode = 'HO';

    if (!Auth::user()->hasAnyRole(['Superuser', 'accounting'])) {
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning',
            'message' => 'Anda tidak berwenang melakukan posting.']);
    }

    $hdr = DB::table('stock_adjustment_hdr')->where('id', $id)->first();
    if (!$hdr) {
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning',
            'message' => 'Data tidak ditemukan.']);
    }
    if ($hdr->status === '4') {
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning',
            'message' => 'Data sudah diposting.']);
    }
    if ($hdr->status === '5') {
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning',
            'message' => 'Data sudah CANCELED.']);
    }

    $adjCode = $hdr->adj_code;
    $location = $hdr->location_code;
    $adjDate = $hdr->adj_date;

    $adjDateYmd = $this->toYmd($adjDate);
    if (!$adjDateYmd) {
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning',
            'message' => "Format tanggal adjustment tidak valid: $adjDate"]);
    }

    $details = $this->getPostingDetails($adjCode, $siteCode, $location);
    if ($details->isEmpty()) {
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning',
            'message' => "$title $adjCode gagal: tidak ada detail artikel."]);
    }

    // ── VALIDASI BACKDATE per artikel, sesuai direction masing-masing ──
    $directionMap = [];
    foreach ($details as $d) {
        $directionMap[$d->article_code] = $d->direction;
    }

    $errors = $this->validateBackdateStock($details, $siteCode, $location, $adjDateYmd, $adjDate, $directionMap);
    if (!empty($errors)) {
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning',
            'message' => implode(' ', $errors)]);
    }

    DB::beginTransaction();
    try {
        $movementSeq = (int) DB::table('warehouse_movement')->max('movement_code');
        $dataSetMovement = [];

        foreach ($details as $val) {
            $movementSeq++;
            $dataSetMovement[] = $this->applyAdjustmentToStock(
                $val, $siteCode, $location, $adjDate, $adjDateYmd, $adjCode, $hdr, $username, $movementSeq
            );
        }

        DB::table('warehouse_movement')->insert($dataSetMovement);

        DB::table('stock_adjustment_hdr')->where('id', $id)->update([
            'status'        => '4',
            'authorized_by' => $username,
            'authorized_at' => date('Y-m-d H:i:s'),
            'updated_by'    => $username,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        DB::commit();

        $message = "$title $adjCode berhasil diposting.";
        \LogActivity::addToLog($title, "username: $username | $message");
        return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);

    } catch (\Exception $e) {
        DB::rollBack();
        $message = "$title $adjCode gagal: " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username | $message");
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
    }
}

/**
 * Terapkan satu baris detail adjustment ke warehouse_stock, article (cost),
 * dan siapkan baris warehouse_movement-nya.
 *
 * qty acuan (qtyNow) dihitung dari saldo HISTORIS pada adjDate (get_last_qty_new),
 * bukan dari article_qty current — supaya weighted-average cost dihitung dari
 * posisi stok yang benar secara kronologis. Sedangkan mutasi article_qty tetap
 * dijalankan sebagai delta (+/- qty) terhadap saldo current, karena delta itu
 * berlaku sama di titik manapun ia disisipkan secara kronologis.
 *
 * @return array baris siap-insert untuk warehouse_movement
 */
private function applyAdjustmentToStock(
    object $val,
    string $siteCode,
    string $location,
    string $adjDate,
    string $adjDateYmd,
    string $adjCode,
    object $hdr,
    string $username,
    int $movementSeq
): array {
    $qty       = (float) $val->qty_adjustment;
    $avgCost   = (float) $val->avg_cost;
    $direction = $val->direction;

    // pastikan baris warehouse_stock ada
    DB::table('warehouse_stock')->updateOrInsert(
        ['site_code' => $siteCode, 'article_code' => $val->article_code, 'location_number' => $location],
        ['dept_code' => $val->article_type, 'uom' => $val->article_uom]
    );

    // saldo & avg cost acuan: qty dari histori pada adjDate, avg cost dari current
    $qtyAtAdjDate = (float) DB::selectOne(
        "SELECT get_last_qty_new(?, ?, ?, ?) as qty",
        [$val->article_code, $adjDateYmd, $siteCode, $location]
    )->qty;

    $avgNow = (float) DB::table('warehouse_stock')
        ->where('site_code', $siteCode)
        ->where('article_code', $val->article_code)
        ->where('location_number', $location)
        ->value('avg_price') ?? 0;

    if ($avgCost <= 0) {
        $avgCost = $avgNow;
    }

    if ($direction === '+') {
        $qtyBaru = $qtyAtAdjDate + $qty;
        $avgBaru = $qtyBaru > 0
            ? (($qtyAtAdjDate * $avgNow) + ($qty * $avgCost)) / $qtyBaru
            : $avgNow;

        DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->update([
                'article_qty' => DB::raw("coalesce(article_qty,0) + $qty"),
                'avg_price'   => $avgBaru,
            ]);

        $movMin  = 0;
        $movPlus = $qty;
    } else {
        DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->update(['article_qty' => DB::raw("coalesce(article_qty,0) - $qty")]);

        $movMin  = $qty;
        $movPlus = 0;
    }

    if ($avgCost > 0) {
        DB::table('article')->where('article_code', $val->article_code)->update([
            'lastcost'   => $avgCost,
            'avgcost'    => $avgCost,
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    return [
        'movement_code'     => $movementSeq,
        'movement_date'     => $adjDate,
        'artikel_code'      => $val->article_code,
        'artikel_desc'      => $val->article_desc ?? '',
        'movement_min'      => $movMin,
        'movement_plus'     => $movPlus,
        'movement_price'    => $avgCost,
        'movement_transnno' => $adjCode,
        'movement_type'     => 'ADJUSTMENT',
        'movement_desc'     => trim("{$hdr->adj_type} ({$hdr->description})"),
        'partner_type'      => 'ADJ',
        'uom'               => $val->uom,
        'created_by'        => $username,
        'created_at'        => date('Y-m-d H:i:s'),
        'site_code'         => $siteCode,
        'location_number'   => $location,
        'movement_from'     => $direction === '-' ? $location : '-',
        'movement_to'       => $direction === '+' ? $location : '-',
        'last_qty'          => DB::raw(
            "get_last_qty_new('{$val->article_code}','$adjDateYmd','$siteCode','$location')"
            . ($direction === '+' ? " + $qty" : " - $qty")
        ),
    ];
}

// =========================================================================
//  CANCEL — hanya superuser / accounting (backdate-safe)
// =========================================================================

public function cancel(Request $request)
{
    $username  = Auth::user()->username;
    $id        = Crypt::decryptString($request->id);
    $title     = "Cancel $this->title";
    $siteCode  = 'HO';

    if (!Auth::user()->hasAnyRole(['Superuser','accounting'])) {
        return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Anda tidak berwenang melakukan cancel.']);
    }

    $reason = trim($request->reason ?? '');
    if (!$reason) {
        return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Cancel reason harus diisi.']);
    }

    $hdr = DB::table('stock_adjustment_hdr')->where('id', $id)->first();
    if (!$hdr) {
        return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Data tidak ditemukan.']);
    }
    if ($hdr->status !== '4') {
        return redirect()->back()->with(['title'=>$title,'alert'=>'warning',
            'message'=>'Hanya data berstatus POSTED yang bisa dicancel.']);
    }

    $adjCode    = $hdr->adj_code;
    $location   = $hdr->location_code;
    $adjDate    = $hdr->adj_date;
    $noteAsal   = $hdr->note ?? '';
    $cancelNote = "(Cancel by $username, Reason: $reason)";

    $adjDateYmd = $this->toYmd($adjDate);
    if (!$adjDateYmd) {
        return redirect()->back()->with(['title'=>$title,'alert'=>'warning',
            'message'=>"Format tanggal adjustment tidak valid: $adjDate"]);
    }

    $details = $this->getPostingDetails($adjCode, $siteCode, $location);
    if ($details->isEmpty()) {
        return redirect()->back()->with(['title'=>$title,'alert'=>'warning',
            'message'=>"$title $adjCode gagal: tidak ada detail artikel."]);
    }

    // ── validasi backdate: cancel membalik arah tiap artikel ──
    $directionMap = [];
    foreach ($details as $d) {
        $directionMap[$d->article_code] = $d->direction === '+' ? '-' : '+';
    }
    $errors = $this->validateBackdateStock($details, $siteCode, $location, $adjDateYmd, $adjDate, $directionMap);
    if (!empty($errors)) {
        return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>implode(' ', $errors)]);
    }

    DB::beginTransaction();
    try {
        $seq             = (int) DB::table('warehouse_movement')->max('movement_code');
        $dataSetMovement = [];

        foreach ($details as $val) {
            $qty       = (float) $val->qty_adjustment;
            $avgCost   = (float) $val->avg_cost;
            $direction = $val->direction; // arah adjustment ASLI

            DB::table('warehouse_stock')->updateOrInsert(
                ['site_code' => $siteCode, 'article_code' => $val->article_code, 'location_number' => $location],
                ['dept_code' => $val->article_type, 'uom' => $val->article_uom]
            );

            if ($direction === '+') {
                DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $location)
                    ->update(['article_qty' => DB::raw("coalesce(article_qty,0) - $qty")]);

                $movMin  = $qty;
                $movPlus = 0;
                $sign    = " - $qty";
            } else {
                DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $location)
                    ->update(['article_qty' => DB::raw("coalesce(article_qty,0) + $qty")]);

                $movMin  = 0;
                $movPlus = $qty;
                $sign    = " + $qty";
            }

            $seq++;
            $dataSetMovement[] = [
                'movement_code'     => $seq,
                'movement_date'     => $adjDate,
                'artikel_code'      => $val->article_code,
                'artikel_desc'      => $val->article_desc ?? '',
                'movement_min'      => $movMin,
                'movement_plus'     => $movPlus,
                'movement_price'    => $avgCost,
                'movement_transnno' => $adjCode,
                'movement_type'     => 'CANCEL ADJUSTMENT',
                'movement_desc'     => trim("Cancel: {$hdr->adj_type} {$hdr->description}; $reason"),
                'partner_type'      => 'ADJ',
                'uom'               => $val->uom,
                'created_by'        => $username,
                'created_at'        => date('Y-m-d H:i:s'),
                'site_code'         => $siteCode,
                'location_number'   => $location,
                'movement_from'     => $direction === '+' ? $location : '-',
                'movement_to'       => $direction === '-' ? $location : '-',
                'last_qty'          => DB::raw(
                    "get_last_qty_new('{$val->article_code}','$adjDateYmd','$siteCode','$location')" . $sign
                ),
            ];
        }

        if (!empty($dataSetMovement)) {
            DB::table('warehouse_movement')->insert($dataSetMovement);
        }

        DB::table('stock_adjustment_hdr')->where('id', $id)->update([
            'status'     => '5',
            'note'       => trim($noteAsal . '; ' . $cancelNote, '; '),
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::commit();

        $message = "$title $adjCode berhasil dicancel. Reason: $reason";
        \LogActivity::addToLog($title, "username: $username | $message");
        return redirect()->back()->with(['title'=>$title,'alert'=>'success','message'=>$message]);

    } catch (\Exception $e) {
        DB::rollBack();
        $message = "$title $adjCode gagal: " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username | $message");
        return redirect()->back()->with(['title'=>$title,'alert'=>'error','message'=>$message]);
    }
}

// =========================================================================
//  PRIVATE HELPERS — dipakai bersama oleh posting() & cancel()
// =========================================================================

/**
 * Konversi tanggal DD-MM-YYYY → YYYY-MM-DD.
 * Return null kalau format tidak valid.
 */
private function toYmd(?string $dmy): ?string
{
    if (!$dmy) return null;
    $dt = \DateTime::createFromFormat('d-m-Y', trim($dmy));
    return $dt ? $dt->format('Y-m-d') : null;
}

/**
 * Ambil detail adjustment + article_type, uom, dan avg_cost dari warehouse_stock.
 */
private function getPostingDetails(string $adjCode, string $siteCode, string $location)
{
    return DB::table('stock_adjustment_det as d')
        ->leftJoin('article as a', 'a.article_code', '=', 'd.article_code')
        ->where('d.adj_code', $adjCode)
        ->select(
            'd.*',
            'a.article_type',
            'a.article_desc',
            'a.article_alternative_code',
            'a.uom as article_uom',
            DB::raw("coalesce((select avg_price from warehouse_stock
                where site_code      = '$siteCode'
                and   article_code   = d.article_code
                and   location_number = '$location' limit 1), 0) as avg_cost")
        )
        ->get();
}

/**
 * Validasi backdate untuk arah pengurangan stok.
 *
 * Mengecek dua hal lewat get_last_qty_new:
 *   1. Saldo historis pada tanggal adjustment cukup untuk dikurangi.
 *   2. Sisipan backdate tidak membuat saldo minus di titik-titik movement
 *      yang sudah ada SETELAH tanggal adjustment (forward-looking).
 *
 * @param  $details     koleksi detail (punya article_code & qty_adjustment)
 * @param  $adjDateYmd  tanggal adjustment format Y-m-d
 * @param  $adjDateRaw  tanggal adjustment format asli (DD-MM-YYYY) untuk pesan
 * @param  $effDir      arah efektif terhadap stok: '-' (mengurangi)
 * @return array        daftar pesan error (kosong kalau lolos)
 */
private function validateBackdateStock($details, string $siteCode, string $location, string $adjDateYmd, string $adjDateRaw, array $directionMap): array
{
    $errors = [];

    foreach ($details as $val) {
        $qty = (float) $val->qty_adjustment;
        if ($qty <= 0) continue;

        $effDir = $directionMap[$val->article_code] ?? '+';
        if ($effDir !== '-') continue; // hanya penambahan → tidak berisiko minus

        $delta = -$qty;

        $saldoHistoris = (float) DB::selectOne(
            "SELECT get_last_qty_new(?, ?, ?, ?) as qty",
            [$val->article_code, $adjDateYmd, $siteCode, $location]
        )->qty;

        if (($saldoHistoris + $delta) < 0) {
            $errors[] = "Stok artikel {$val->article_code} pada $adjDateRaw hanya $saldoHistoris, "
                      . "tidak cukup untuk dikurangi $qty.";
            continue;
        }

        $minBal = DB::selectOne("
            WITH future_mov AS (
                SELECT TO_DATE(movement_date,'dd-mm-yyyy') as tgl,
                       movement_code,
                       (-movement_min + movement_plus) as d
                FROM warehouse_movement
                WHERE artikel_code     = ?
                  AND site_code        = ?
                  AND location_number  = ?
                  AND TO_DATE(movement_date,'dd-mm-yyyy') > TO_DATE(?, 'yyyy-mm-dd')
            ),
            running AS (
                SELECT tgl,
                       ?::numeric + ?::numeric + SUM(d) OVER (ORDER BY tgl, movement_code) as bal
                FROM future_mov
            )
            SELECT COALESCE(MIN(bal), ?::numeric + ?::numeric) as min_bal FROM running
        ", [
            $val->article_code, $siteCode, $location, $adjDateYmd,
            $saldoHistoris, $delta,
            $saldoHistoris, $delta,
        ]);

        if ($minBal && (float) $minBal->min_bal < 0) {
            $errors[] = "Adjustment artikel {$val->article_code} sebesar $qty pada $adjDateRaw "
                      . "akan menyebabkan stok minus pada transaksi setelahnya "
                      . "(saldo terendah: " . number_format((float)$minBal->min_bal, 2) . ").";
        }
    }

    return $errors;
}

private function summarizeDirection(array $articles): string
{
    $dirs = array_unique(array_map(fn($a) => $a->direction ?? '+', $articles));
    return count($dirs) === 1 ? $dirs[0] : 'MIXED';
}
    // =========================================================================
    //  DESTROY
    // =========================================================================

    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);

        $hdr = DB::table('stock_adjustment_hdr')->where('id', $id)->first();

        if (!$hdr) {
            return redirect()->back()->with(['title'=>'Delete','alert'=>'warning','message'=>'Data tidak ditemukan.']);
        }

        // Tidak bisa hapus kalau sudah POSTED atau CANCELED
        if (in_array($hdr->status, ['4','5'])) {
            return redirect()->back()->with(['title'=>'Delete','alert'=>'warning',
                'message'=>'Data sudah '.($hdr->status==='4'?'POSTED':'CANCELED').', tidak bisa dihapus.']);
        }

        // Hanya creator atau superacc
        if ($hdr->created_by !== $username && !Auth::user()->hasAnyRole(['Superuser','accounting','finance'])) {
            return redirect()->back()->with(['title'=>'Delete','alert'=>'warning','message'=>'Anda tidak berwenang menghapus data ini.']);
        }

        $adjCode = $hdr->adj_code;
        DB::table('stock_adjustment_hdr')->where('id', $id)->delete();
        // Detail ter-delete via CASCADE

        $message = "Delete $this->title $adjCode berhasil.";
        \LogActivity::addToLog("Delete $this->title", "username: $username | $message");
        return redirect()->back()->with(['title'=>"Delete $this->title",'alert'=>'success','message'=>$message]);
    }


    // =========================================================================
    //  STOCK BEFORE  (AJAX) — dipakai untuk 1 baris (add manual / lihat 1 artikel)
    // =========================================================================

    public function stockBefore(Request $request)
{
    $adjDate    = $request->adjDate;              // wajib dikirim dari frontend
    $siteCode   = 'HO';
    $adjDateYmd = $this->toYmd($adjDate);

    if (!$adjDateYmd) {
        return response()->json(['stock' => 0]);
    }

    $stock = (float) DB::selectOne(
        "SELECT get_last_qty_new(?, ?, ?, ?) as qty",
        [$request->article_code, $adjDateYmd, $siteCode, $request->location_code]
    )->qty;

    return response()->json(['stock' => $stock]);
}

    // =========================================================================
    //  STOCK BEFORE — BULK (AJAX, dipakai import Excel)
    //
    //  Kenapa perlu endpoint terpisah dari stockBefore() di atas: import bisa
    //  berisi ratusan/ribuan artikel sekaligus. Kalau tiap baris manggil
    //  stockBefore() satu-satu, itu jadi ratusan/ribuan HTTP request (berat).
    //  Endpoint ini menerima BANYAK article_code sekaligus dan mengembalikan
    //  semuanya dalam SATU query database (pakai unnest), jadi tetap ringan
    //  walau jumlah baris besar.
    // =========================================================================

    public function stockBeforeBulk(Request $request)
    {
        $adjDate      = $request->adjDate;
        $siteCode     = 'HO';
        $locationCode = $request->location_code;
        $articleCodes = $request->article_codes; // array of string

        $adjDateYmd = $this->toYmd($adjDate);

        if (!$adjDateYmd || !$locationCode || empty($articleCodes) || !is_array($articleCodes)) {
            return response()->json(['stocks' => (object) []]);
        }

        // unik-kan & buang yang kosong
        $articleCodes = array_values(array_unique(array_filter($articleCodes, fn($c) => trim((string) $c) !== '')));

        if (empty($articleCodes)) {
            return response()->json(['stocks' => (object) []]);
        }

        // bangun literal array Postgres: {"CODE1","CODE2",...}
        $pgArray = '{' . implode(',', array_map(function ($c) {
            return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $c) . '"';
        }, $articleCodes)) . '}';

        $rows = DB::select("
            SELECT ac.article_code,
                   get_last_qty_new(ac.article_code, ?, ?, ?) as qty
            FROM unnest(?::text[]) as ac(article_code)
        ", [$adjDateYmd, $siteCode, $locationCode, $pgArray]);

        $stocks = [];
        foreach ($rows as $r) {
            $stocks[$r->article_code] = (float) $r->qty;
        }

        return response()->json(['stocks' => $stocks]);
    }

    // =========================================================================
    //  AUTO-CODE
    // =========================================================================

    public function getLastCode(string $key): string
    {
        DB::table('master_code')->where('code_key', $key)->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by'  => Auth::user()->username,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $newCode = DB::table('master_code')->where('code_key', $key)->value('code_number');

        $months = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $month  = $months[date('n') - 1];
        $year   = date('Y');   // 2 digit → 26

        return "$key-ASN-$year-$month-" . str_pad($newCode, 4, '0', STR_PAD_LEFT);
        // → ADJ-ASN-26-VI-0001
    }

    // =========================================================================
    //  IMPORT / EXPORT
    // =========================================================================

    public function import(Request $request)   { return $this->importExcel($request); }
    public function exportExcel()              { return $this->export(); }

    public function importExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:xls,xlsx|max:5120', // maks 5 MB
        ]);

        $file         = $request->file('file');
        $namaFile     = Auth::user()->username . '_' . time();
        $locationCode = $request->location_code ?? '';
        $title        = "Import $this->title";

        // batas jumlah baris yang aman untuk dirender di frontend
        $maxRows = 2000;

        DB::table('import_adjustment_tmp')->where('file_name', $namaFile)->delete();

        try {
            Excel::import(new StockAdjustmentImport($namaFile), $file);
        } catch (\Exception $e) {
            DB::table('import_adjustment_tmp')->where('file_name', $namaFile)->delete();
            return response()->json(['status'=>0,'title'=>$title,
                'message'=>['Gagal membaca file: '.$e->getMessage()],'alert'=>'error']);
        }

        $rowCount = DB::table('import_adjustment_tmp')->where('file_name', $namaFile)->count();
        if ($rowCount > $maxRows) {
            DB::table('import_adjustment_tmp')->where('file_name', $namaFile)->delete();
            return response()->json(['status'=>0,'title'=>$title,
                'message'=>["File berisi $rowCount baris, melebihi batas maksimal $maxRows baris per import. Silakan pecah file menjadi beberapa bagian."],
                'alert'=>'error']);
        }

        $dataValidasi = DB::table('import_adjustment_tmp')
            ->leftJoin('article','article.article_alternative_code','=','import_adjustment_tmp.article_code')
            ->select('import_adjustment_tmp.article_code','import_adjustment_tmp.qty',
                DB::raw("concat(
                    case when article.article_code is null then concat('Article Code ', import_adjustment_tmp.article_code, ' tidak terdaftar. ') end,
                    case when coalesce(import_adjustment_tmp.qty,0)=0 then concat('Article ', import_adjustment_tmp.article_code, ' - Qty tidak boleh 0. ') end
                ) as error_notes"))
            ->where('import_adjustment_tmp.file_name', $namaFile)
            ->get();

        $dataNotes = $dataValidasi->filter(fn($v) => !empty(trim($v->error_notes ?? '')))
            ->map(fn($v) => [$v->error_notes])->values()->toArray();

        if (count($dataNotes) > 0) {
            DB::table('import_adjustment_tmp')->where('file_name', $namaFile)->delete();
            return response()->json(['status'=>0,'title'=>$title,'message'=>$dataNotes,'alert'=>'error',
                'pesan'=>'Ada error pada data yang diupload!','dataDetail'=>[]]);
        }

        // NOTE: stock_before di sini SENGAJA 0 — nilai stok historis yang
        // sebenarnya diambil belakangan oleh frontend lewat stockBeforeBulk(),
        // supaya query di sini tetap ringan (tidak perlu panggil
        // get_last_qty_new() untuk tiap baris di titik ini).
        $data = DB::table('import_adjustment_tmp')
            ->leftJoin('article','article.article_alternative_code','=','import_adjustment_tmp.article_code')
            ->select('article.article_code',
                DB::raw('import_adjustment_tmp.qty as qty_adjustment'),
                'article.uom',
                DB::raw("(select string_agg(unit_to,',' order by unit_from) from uom_con_v2 where article_code=article.article_code) as uom_member"),
                'import_adjustment_tmp.notes',
                DB::raw('0 as stock_before'))
            ->where('import_adjustment_tmp.file_name', $namaFile)
            ->get();

        DB::table('import_adjustment_tmp')->where('file_name', $namaFile)->delete();

        return response()->json(['status'=>1,'title'=>$title,'message'=>"$title berhasil diimport.",
            'alert'=>'success','pesan'=>'','dataDetail'=>$data]);
    }

    public function export()
    {
        return Excel::download(new StockAdjustmentExport, 'stock_adjustment_template.xlsx');
    }

    // =========================================================================
    //  PRINT (stub — buat sesuai kebutuhan)
    // =========================================================================

    public function print(Request $request)
    {
        $id  = Crypt::decryptString($request->id);
        $hdr = DB::table('stock_adjustment_hdr as h')
            ->leftJoin('stock_location_master as loc','loc.location_code','=','h.location_code')
            ->where('h.id',$id)->select('h.*','loc.location_name')->first();

        if (!$hdr) abort(404);

        $details = DB::table('stock_adjustment_det as d')
            ->leftJoin('article as a','a.article_code','=','d.article_code')
            ->where('d.adj_code',$hdr->adj_code)
            ->select('d.*','a.article_alternative_code','a.article_desc')
            ->orderBy('d.id')->get();

        $data = compact('hdr','details');
         return view('stockAdjustment.print', $data);  // buat view print terpisah
    }

    // =========================================================================
    //  PRIVATE HELPERS
    // =========================================================================

    private function parseDateRange(?string $trDate): array
    {
        $fromDate = $toDate = '';
        if ($trDate) {
            $parts = explode('to', $trDate);
            $fromDate = implode('/', array_reverse(explode('-', trim($parts[0]))));
            $toDate   = count($parts) > 1
                ? implode('/', array_reverse(explode('-', trim($parts[1]))))
                : $fromDate;
        }
        return [$fromDate, $toDate];
    }
}