<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use DataTables;

class LocationController extends Controller
{
    /* ============================================================
     |  MASTER OPSI
     ============================================================ */
    private function locationTypes()
    {
        return [
            'area'    => 'Area / Gudang',
            'wip'     => 'WIP (Work In Process)',
            'booth'   => 'Spray Booth',
            'transit' => 'Transit/Temporary',
            'dept'    => 'Departemen',
        ];
    }

    private function articleTypes()
    {
        return [
            'RM'        => 'RM — Raw Material',
            'FG'        => 'FG — Finish Goods',
            'OT'        => 'OT — Others',
            'WIP'       => 'WIP',
            'CM'        => 'CM — Chemical',
            'CM1'       => 'CM1 — Chemical Grup 1',
            'CONS'      => 'CONS — Consumable',
            'SPAREPART' => 'Sparepart',
        ];
    }

    private function getDepts()
    {
        return DB::table('depts')->select('code', 'name')->orderBy('code')->get();
    }

    /**
     * Decode article_type menjadi array PHP dengan aman. Kolom di DB pernah
     * diisi lewat dua cara berbeda (JSON string dari UI lama, atau literal
     * array Postgres '{RM,FG}' dari input manual/migrasi) -- decoder ini
     * menerima keduanya.
     */
    private function decodeArticleType($val)
    {
        if (is_null($val) || $val === '') return [];
        if (is_array($val)) return $val;

        $decoded = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (is_string($val) && strlen($val) >= 2 && $val[0] === '{' && substr($val, -1) === '}') {
            $inner = trim(substr($val, 1, -1));
            return $inner === '' ? [] : array_map('trim', explode(',', $inner));
        }

        return [$val];
    }

    /* ============================================================
     |  INDEX
     ============================================================ */
    public function index(Request $request)
    {
        return view('location.index', [
            'title'         => 'Master Location',
            'subtitle'      => 'Master Location',
            'locationTypes' => $this->locationTypes(),
            'depts'         => $this->getDepts(),
        ]);
    }

    /* ============================================================
     |  DATATABLE
     ============================================================ */
    public function list(Request $request)
    {
        $q = DB::table('stock_location_master as l')
            ->leftJoin('stock_location_master as p', 'p.location_code', '=', 'l.parent_location')
            ->select(
                'l.id', 'l.location_code', 'l.location_name', 'l.location_type',
                'l.article_type', 'l.dept_code', 'l.parent_location', 'l.status', 'l.pic', 'l.note',
                'l.created_by', 'l.updated_by', 'l.created_at', 'l.updated_at',
                'p.location_name as parent_location_name'
            )
            ->selectRaw('coalesce((select sum(ws.article_qty) from warehouse_stock ws
                          where ws.location_number = l.location_code), 0) as stock_qty');

        if ($request->filled('code')) {
            $q->where('l.location_code', 'ilike', '%' . $request->code . '%');
        }
        if ($request->filled('name')) {
            $q->where('l.location_name', 'ilike', '%' . $request->name . '%');
        }
        if ($request->filled('type')) {
            $q->where('l.location_type', $request->type);
        }
        if ($request->filled('dept')) {
            $q->where('l.dept_code', $request->dept);
        }

        $q->orderBy('l.location_code');

        return DataTables::of($q)
            ->addColumn('action', function ($row) {
                return $this->actionButtons($row);
            })
            ->editColumn('stock_qty', function ($row) {
                return (float) $row->stock_qty;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    private function actionButtons($row)
    {
        $eid = Crypt::encryptString($row->id);

        $edit = '<a href="' . route('location.edit', ['id' => $eid]) . '" class="dropdown-item">
                    <i data-feather="file-text"></i>
                    Edit
                 </a>';

        $del = "<a href='javascript:;'
                    id='deleteButton'
                    class='dropdown-item'
                    data-toggle='modal'
                    data-target='#smallModal'
                    data-href='" . route('location.destroy', ['id' => $eid]) . "'
                    data-code='" . e($row->location_code) . "'
                    data-stock='" . (float) $row->stock_qty . "'>
                    <i data-feather='trash-2' class='feather-14-red'></i>
                    Delete
                 </a>";

        return '<div class="d-inline-flex">
                    <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                        <i data-feather="menu"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">' . $edit . $del . '</div>
                </div>';
    }

    /* ============================================================
     |  CREATE
     ============================================================ */
    public function create(Request $request)
    {
        return view('location.create', [
            'title'         => 'Create Location',
            'subtitle'      => 'Create New Location',
            'locationTypes' => $this->locationTypes(),
            'articleTypes'  => $this->articleTypes(),
            'depts'         => $this->getDepts(),
            'parents'       => DB::table('stock_location_master')
                                    ->whereNull('parent_location')
                                    ->orderBy('location_code')
                                    ->get(['location_code', 'location_name']),
        ]);
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;

        $messages = [
            'required' => 'The field is required.',
            'iunique'  => 'The code has already been taken',
        ];

        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query  = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $this->validate($request, [
            'location_code'   => 'required|max:10|iunique:stock_location_master,location_code',
            'location_name'   => 'required|max:150',
            'location_type'   => 'nullable|max:30',
            'dept_code'       => 'nullable|max:10',
            'parent_location' => 'nullable|max:10',
            'pic'             => 'nullable|max:100',
            'note'            => 'nullable|max:500',
        ], $messages);

        $code   = strtoupper(trim($request->location_code));
        $parent = $request->location_kind === 'child' ? ($request->parent_location ?: null) : null;

        if ($parent === $code) {
            return redirect()->back()->withInput()
                ->with(['alert' => 'alert-warning', 'message' => 'Parent location tidak boleh sama dengan location code.']);
        }

        $articleArr  = array_filter((array) $request->input('article_type', []));
        $articleJson = count($articleArr) ? json_encode(array_values($articleArr)) : null;

        DB::beginTransaction();
        try {
            DB::table('stock_location_master')->insert([
                'location_code'   => $code,
                'location_name'   => strtoupper(trim($request->location_name)),
                'location_type'   => $request->location_type ?: null,
                'article_type'    => $articleJson,
                'dept_code'       => $request->dept_code ?: null,
                'parent_location' => $parent,
                'status'          => $request->status ?: '1',
                'pic'             => $request->pic ?: null,
                'note'            => $request->note,
                'created_by'      => $username,
                'updated_by'      => $username,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            DB::commit();

            $alert   = 'alert-success';
            $message = "Location {$code} is successfully saved";
            \LogActivity::addToLog('Location save', "username: {$username} Status {$message}");
            return redirect()->route('location.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $alert   = 'alert-warning';
            $message = "Location {$code} is failed to save";
            \LogActivity::addToLog('Location save', "username: {$username} Status {$message} - " . $e->getMessage());
            return redirect()->back()->withInput()->with(['alert' => $alert, 'message' => $message]);
        }
    }

    /* ============================================================
     |  EDIT
     ============================================================ */
    public function edit(Request $request)
    {
        $id  = Crypt::decryptString($request->id);
        $loc = DB::table('stock_location_master')->where('id', $id)->first();

        if (!$loc) {
            return redirect()->route('location.index')
                ->with(['alert' => 'alert-warning', 'message' => 'Data tidak ditemukan.']);
        }

        return view('location.edit', [
            'title'         => 'Edit Location',
            'subtitle'      => 'Edit Location',
            'encId'         => $request->id,
            'loc'           => $loc,
            'locArticleType'=> $this->decodeArticleType($loc->article_type),
            'locationTypes' => $this->locationTypes(),
            'articleTypes'  => $this->articleTypes(),
            'depts'         => $this->getDepts(),
            'parents'       => DB::table('stock_location_master')
                                    ->whereNull('parent_location')
                                    ->where('location_code', '!=', $loc->location_code)
                                    ->orderBy('location_code')
                                    ->get(['location_code', 'location_name']),
        ]);
    }

    public function update(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);

        $loc = DB::table('stock_location_master')->where('id', $id)->first();
        if (!$loc) {
            return redirect()->route('location.index')
                ->with(['alert' => 'alert-warning', 'message' => 'Data tidak ditemukan.']);
        }

        $this->validate($request, [
            'location_name'   => 'required|max:150',
            'location_type'   => 'nullable|max:30',
            'dept_code'       => 'nullable|max:10',
            'parent_location' => 'nullable|max:10',
            'pic'             => 'nullable|max:100',
            'note'            => 'nullable|max:500',
        ], ['required' => 'The field is required.']);

        // location_code TIDAK PERNAH diambil dari request -- kode sengaja
        // dikunci setelah dibuat, tidak bisa diedit lewat form ini.
        $code   = $loc->location_code;
        $parent = $request->location_kind === 'child' ? ($request->parent_location ?: null) : null;

        if ($parent === $code) {
            return redirect()->back()->withInput()
                ->with(['alert' => 'alert-warning', 'message' => 'Parent location tidak boleh sama dengan location code.']);
        }

        // Guard: tidak boleh jadi child dari salah satu anaknya sendiri (cegah loop).
        if ($parent) {
            $isOwnChild = DB::table('stock_location_master')
                ->where('parent_location', $code)
                ->where('location_code', $parent)
                ->exists();
            if ($isOwnChild) {
                return redirect()->back()->withInput()
                    ->with(['alert' => 'alert-warning', 'message' => 'Parent tidak boleh salah satu sub-lokasi dari lokasi ini.']);
            }
        }

        $articleArr  = array_filter((array) $request->input('article_type', []));
        $articleJson = count($articleArr) ? json_encode(array_values($articleArr)) : null;

        DB::beginTransaction();
        try {
            DB::table('stock_location_master')->where('id', $id)->update([
                'location_name'   => strtoupper(trim($request->location_name)),
                'location_type'   => $request->location_type ?: null,
                'article_type'    => $articleJson,
                'dept_code'       => $request->dept_code ?: null,
                'parent_location' => $parent,
                'status'          => $request->status ?: '1',
                'pic'             => $request->pic ?: null,
                'note'            => $request->note,
                'updated_by'      => $username,
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

            DB::commit();

            $alert   = 'alert-success';
            $message = "Location {$code} is successfully updated";
            \LogActivity::addToLog('Location update', "username: {$username} Status {$message}");
            return redirect()->route('location.index')->with(['alert' => $alert, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            $alert   = 'alert-warning';
            $message = "Location {$code} is failed to update";
            \LogActivity::addToLog('Location update', "username: {$username} Status {$message} - " . $e->getMessage());
            return redirect()->back()->withInput()->with(['alert' => $alert, 'message' => $message]);
        }
    }

    /* ============================================================
     |  DESTROY
     ============================================================ */
    public function destroy(Request $request)
    {
        $username = Auth::user()->username;
        $id       = Crypt::decryptString($request->id);
        $loc      = DB::table('stock_location_master')->where('id', $id)->first();

        if (!$loc) {
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => 'Data tidak ditemukan.']);
        }

        // Guard: masih punya sub-lokasi.
        $hasChild = DB::table('stock_location_master')
            ->where('parent_location', $loc->location_code)
            ->exists();
        if ($hasChild) {
            $message = "Location {$loc->location_code} tidak bisa dihapus — masih memiliki sub-lokasi.";
            \LogActivity::addToLog('Location delete', "username: {$username} Status {$message}");
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => $message]);
        }

        // Guard: masih ada stock tercatat di lokasi ini.
        $stockQty = (float) DB::table('warehouse_stock')
            ->where('location_number', $loc->location_code)
            ->sum('article_qty');
        if (abs($stockQty) > 0.0001) {
            $message = "Location {$loc->location_code} tidak bisa dihapus — masih ada stock tercatat ({$stockQty}).";
            \LogActivity::addToLog('Location delete', "username: {$username} Status {$message}");
            return redirect()->back()->with(['alert' => 'alert-warning', 'message' => $message]);
        }

        DB::table('stock_location_master')->where('id', $id)->delete();

        $message = "Location {$loc->location_code} berhasil dihapus.";
        \LogActivity::addToLog('Location delete', "username: {$username} Status {$message}");
        return redirect()->back()->with(['alert' => 'alert-success', 'message' => $message]);
    }
}
