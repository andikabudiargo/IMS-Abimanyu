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
use Maatwebsite\Excel\Facades\Excel;

class StockTakingOrderController extends Controller
{
    private $title;
    private $moduleCode;

    public function __construct()
    {
        $this->title      = "Stock Taking Order";
        $this->moduleCode = "STO";
    }

    private function resolveTolerancePercent($targetPlanLoc)
{
    $target = (float) ($targetPlanLoc ?? 0);
    if ($target <= 0 || $target >= 100) return 0;
    return round(100 - $target, 2);
}

    // ══════════════════════════════════════════════
    // STATUS  (Opsi B)
    // 1 = SCHEDULED | 2 = ONGOING | 3 = COMPLETED | 5 = CANCELED
    // ══════════════════════════════════════════════
    private function statusList()
    {
        return [
            '1' => 'SCHEDULED',
            '2' => 'ONGOING',
            '3' => 'COMPLETED',
            '5' => 'CANCELED',
        ];
    }

     private function stoTypeList()
    {
        return [
            'Annual'    => 'Annual (Tahunan)',
            'MONTHLY'    => 'Monthly (Bulanan/Reguler)',
            'CYCLE'      => 'Cycle Count (Harian/Mingguan)',
            'SPECIAL'    => 'Special Case (Insidental)',
            'BY_PARTNER' => 'By Partner (Supplier/Customer)',
        ];
    }

    private function statusBadge($status)
    {
        $map = [
            '1' => '<span class="badge badge-info">SCHEDULED</span>',
            '2' => '<span class="badge badge-primary">ONGOING</span>',
            '3' => '<span class="badge badge-success">COMPLETED</span>',
            '5' => '<span class="badge badge-danger">CANCELED</span>',
        ];
        return $map[$status] ?? $status;
    }

    // ══════════════════════════════════════════════
    // KOLOM TABLE
    // ══════════════════════════════════════════════
     public function getTableColoumn()
    {
        $kolom =
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'sto_code','name'=>'sto_code','title'=>'STO Code'],
            ['data'=>'sto_type','name'=>'sto_type','title'=>'STO Type'],
            ['data'=>'periode','name'=>'periode','title'=>'Periode'],
            ['data'=>'target_plan','name'=>'target_plan','title'=>'Akurasi Plan'],
            ['data'=>'target_act','name'=>'target_act','title'=>'Akurasi Actual'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'finish_time','name'=>'finish_time','title'=>'Finish Time'],
            ['data'=>'notes','name'=>'notes','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created by'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created at'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated by'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated at'],
        ];
        return json_encode($kolom, true);
    }
 
    // ══════════════════════════════════════════════
    // KOLOM TABLE DETAIL — tambah 'target_type'
    // ══════════════════════════════════════════════
    public function getTableColoumnDetail()
{
    $kolom =
    [
        ['data'=>'target_type','name'=>'target_type','title'=>'Tipe'],
        ['data'=>'date','name'=>'date','title'=>'STO Date'],
        ['data'=>'periode','name'=>'periode','title'=>'Periode'],
        ['data'=>'location','name'=>'location','title'=>'Target'],
        ['data'=>'counter_user_1','name'=>'counter_user_1','title'=>'Counter 1'],
        ['data'=>'counter_user_2','name'=>'counter_user_2','title'=>'Counter 2'],
        ['data'=>'counter_user_3','name'=>'counter_user_3','title'=>'Counter 3'],
        ['data'=>'target_plan_loc','name'=>'target_plan_loc','title'=>'Akurasi Plan'],
        ['data'=>'target_act_loc','name'=>'target_act_loc','title'=>'Akurasi Act'],
        ['data'=>'note','name'=>'note','title'=>'Note'],
        ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated by'],
        ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated at'],
    ];
    return json_encode($kolom, true);
}

    // ══════════════════════════════════════════════
    // GENERATE CODE (pola master_code)
    // Output: STO-2026-VII-15
    // ══════════════════════════════════════════════
    public function getLastCode($key)
    {
        DB::table('master_code')
            ->where('code_key', $key)
            ->update([
                'code_number' => DB::raw('code_number + 1'),
                'updated_by'  => Auth::user()->username,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        $newCode = DB::table('master_code')
            ->where('code_key', $key)
            ->value('code_number');

        $months = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $month  = $months[date('n') - 1];
        $year   = date('Y');

        return "$key-ASN-$year-$month-$newCode";
    }

    public function index(Request $request)
    {
        $data['title']       = "$this->title";
        $data['subtitle']    = "$this->title";
        $data['kolom']       = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['status']      = $this->statusList();
        $data['stoTypes']    = $this->stoTypeList();
 
        $data['locations'] = DB::table('stock_location_master')
            ->select('location_code', 'location_name')
            ->orderBy('location_name')->get();
 
        $data['users'] = DB::table('users')
            ->select('id', 'name', 'username')
            ->orderBy('name')->get();
 
        return view("stockTakingOrder.index", $data);
    }
 
    // ══════════════════════════════════════════════
    // GET PARTNERS  (AJAX — dropdown supplier/customer)
    // type: 'supp' | 'cust'
    // ══════════════════════════════════════════════
   
    public function getPartners(Request $request)
    {
        $type = $request->type; // 'supp' | 'cust'
        $rows = DB::table('third_party')
            ->select('kode', 'nama')
            ->where('third_party_type', $type)
            ->orderBy('nama')
            ->get();
 
        return response()->json($rows);
    }
 
    /**
     * Map target_type UI → nilai simpan.
     * UI kirim: 'LOCATION' | 'SUPPLIER' | 'CUSTOMER'
     */
    private function normalizeTargetType($t)
    {
        $t = strtoupper($t ?? '');
        return in_array($t, ['LOCATION', 'SUPPLIER', 'CUSTOMER']) ? $t : 'LOCATION';
    }

    // ══════════════════════════════════════════════
    // LIST (DataTables — header)
    // ══════════════════════════════════════════════
    public function list(Request $request)
    {
        $query = DB::table('sto_config as h')
            ->leftJoin('users as uc', 'uc.username', '=', 'h.created_by')
            ->leftJoin('users as uu', 'uu.username', '=', 'h.updated_by')
            ->select([
                'h.config_id',
                'h.sto_code',
                'h.sto_type',
                'h.periode',
                'h.target_plan',
                'h.target_act',
                'h.status',
                'h.finish_time',
                'h.notes',
                'h.created_by',
                'h.created_at',
                'h.updated_by',
                'h.updated_at',
            ]);
 
        if ($request->filled('searchCode')) {
            $query->where('h.sto_code', 'ilike', '%' . $request->searchCode . '%');
        }
        if ($request->filled('searchPeriode')) {
            $query->where('h.periode', $request->searchPeriode);
        }
        if ($request->filled('searchStatus')) {
            $query->where('h.status', $request->searchStatus);
        }
        if ($request->filled('searchStoType')) {
            $query->where('h.sto_type', $request->searchStoType);
        }
        if ($request->filled('searchDate')) {
            $parts = explode(' to ', $request->searchDate);
            $from  = trim($parts[0] ?? '');
            $to    = trim($parts[1] ?? $from);
            if ($from && $to) {
                $query->whereExists(function ($q) use ($from, $to) {
                    $q->select(DB::raw(1))
                      ->from('sto_config_mapping as mm')
                      ->whereColumn('mm.config_id', 'h.config_id')
                      ->whereRaw("TO_DATE(mm.sto_date,'DD-MM-YYYY') BETWEEN TO_DATE(?, 'DD-MM-YYYY') AND TO_DATE(?, 'DD-MM-YYYY')", [$from, $to]);
                });
            }
        }
 
        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $encId  = Crypt::encryptString($row->config_id);
                $isAcct = Auth::user()->hasAnyRole(['Superuser', 'accounting']);
                $st     = $row->status;
 
                $buttons  = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';
 
                // DETAIL (selalu)
                $buttons .= '<a href="' . route('stockTakingOrder.show', ['id'=>$encId]) . '" class="dropdown-item">
                                <i data-feather="eye"></i><span>' . __('Detail') . '</span></a>';
 
                // EDIT — hanya saat SCHEDULED/ONGOING
                if (in_array($st, [1, 2])) {
                    $buttons .= '<a href="' . route('stockTakingOrder.edit', ['id'=>$encId]) . '" class="dropdown-item">
                                    <i data-feather="edit-2"></i><span>' . __('Edit') . '</span></a>';
                }
 
                // CANCEL — hanya saat SCHEDULED/ONGOING, dan hanya accounting/superuser
                if (in_array($st, [1, 2]) && $isAcct) {
                    $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                                    data-confirm='Batalkan STO ini?|STO akan dibatalkan dan tidak bisa digunakan lagi.'
                                    data-confirm-yes='document.getElementById(\"delete-form-{$row->config_id}\").submit();'
                                    data-modal-id='{$row->config_id}'
                                    data-url='" . route('stockTakingOrder.cancel', ['id'=>$encId]) . "'>
                                    <i data-feather='x-circle' class='feather-14-red'></i><span>" . __('Cancel') . "</span></a>";
                }
 
                $buttons .= '</div></div>';
                return $buttons;
            })
            ->editColumn('sto_type', function ($row) {
                $map = $this->stoTypeList();
                return $map[$row->sto_type] ?? $row->sto_type;
            })
            ->editColumn('status', fn($row) => $this->statusBadge($row->status))
            ->editColumn('target_plan', fn($row) => $row->target_plan !== null ? number_format($row->target_plan, 2) . '%' : '-')
            ->editColumn('target_act',  fn($row) => $row->target_act  !== null ? number_format($row->target_act, 2) . '%'  : '-')
            ->editColumn('finish_time', fn($row) => $row->finish_time ?? '-')
            ->rawColumns(['action', 'status'])
            ->make(true);
    }
 
    // ══════════════════════════════════════════════
    // LIST DETAIL (DataTables — per baris mapping)
    // date range & sto_type filter ditambahkan; target_type disertakan
    // ══════════════════════════════════════════════
    public function listDetail(Request $request)
{
    $query = DB::table('sto_config_mapping as m')
        ->join('sto_config as h', 'h.config_id', '=', 'm.config_id')
        ->leftJoin('stock_location_master as l', function ($j) {
            $j->on('l.location_code', '=', 'm.target_ref')
              ->where('m.target_type', '=', 'LOCATION');
        })
        ->leftJoin('third_party as tp', function ($j) {
            $j->on('tp.kode', '=', 'm.target_ref')
              ->whereIn('m.target_type', ['SUPPLIER', 'CUSTOMER']);
        })
        ->leftJoin('users as u1', 'u1.id', '=', 'm.counter1_user')
        ->leftJoin('users as u2', 'u2.id', '=', 'm.counter2_user')
        ->leftJoin('users as u3', 'u3.id', '=', 'm.counter3_user')
        ->select([
            'm.target_type',
            'm.sto_date as date',
            'h.periode',
            DB::raw("COALESCE(l.location_name, tp.nama, m.target_ref) as location"),
            'u1.name as counter_user_1',
            'u2.name as counter_user_2',
            'u3.name as counter_user_3',
            'm.target_plan_loc',
            'm.target_act_loc',
            'm.finish_time',
            'm.notes as note',
            'm.updated_by',
            'm.updated_at',
        ]);

    if ($request->filled('searchCode'))    $query->where('h.sto_code', 'ilike', '%' . $request->searchCode . '%');
    if ($request->filled('searchPeriode')) $query->where('h.periode', $request->searchPeriode);
    if ($request->filled('searchStatus'))  $query->where('h.status', $request->searchStatus);
    if ($request->filled('searchStoType')) $query->where('h.sto_type', $request->searchStoType);

    if ($request->filled('searchDate')) {
        $parts = explode(' to ', $request->searchDate);
        $from  = trim($parts[0] ?? '');
        $to    = trim($parts[1] ?? $from);
        if ($from && $to) {
            $query->whereRaw("TO_DATE(m.sto_date,'DD-MM-YYYY') BETWEEN TO_DATE(?, 'DD-MM-YYYY') AND TO_DATE(?, 'DD-MM-YYYY')", [$from, $to]);
        }
    }

    return DataTables::of($query)
        ->editColumn('target_type', function ($row) {
            $map = ['LOCATION'=>'Lokasi','SUPPLIER'=>'Supplier','CUSTOMER'=>'Customer'];
            return $map[$row->target_type] ?? $row->target_type;
        })
        ->editColumn('target_plan_loc', fn($row) => $row->target_plan_loc !== null ? number_format($row->target_plan_loc, 2) . '%' : '-')
        ->editColumn('target_act_loc',  fn($row) => $row->target_act_loc  !== null ? number_format($row->target_act_loc, 2) . '%'  : '-')
        ->editColumn('finish_time',     fn($row) => $row->finish_time ?? '-')
        ->make(true);
}

    // ══════════════════════════════════════════════
    // CREATE
    // ══════════════════════════════════════════════
     public function create()
    {
        $data['title']    = "$this->title";
        $data['subtitle'] = "Create $this->title";
        $data['oEdit']    = false;
        $data['stoTypes'] = $this->stoTypeList();
 
        $data['locations'] = DB::table('stock_location_master')
            ->select('location_code', 'location_name')
            ->orderBy('location_name')->get();
 
        $data['users'] = DB::table('users')
            ->select('id', 'name', 'username')
            ->orderBy('name')->get();
 
        return view("stockTakingOrder.create", $data);
    }

    // ══════════════════════════════════════════════
    // STORE
    // ══════════════════════════════════════════════
    public function store(Request $request)
    {
        $mappings = json_decode($request->mappings, true);
        $stoType  = $request->sto_type;
 
        $flag = 0; $pesan = [];
 
        if (empty($request->periode)) { $pesan[] = "Periode wajib diisi."; $flag = 1; }
        if (empty($stoType))          { $pesan[] = "STO Type wajib dipilih."; $flag = 1; }
        if (empty($mappings) || !is_array($mappings) || count($mappings) === 0) {
            $pesan[] = "Minimal satu target harus dimapping."; $flag = 1;
        }
 
        // satu periode + sto_type tidak boleh punya config aktif
        $existing = DB::table('sto_config')
            ->where('periode', $request->periode)
            ->where('sto_type', $stoType)
            ->whereIn('status', [1, 2])
            ->first();
        if ($existing) {
            $pesan[] = "Sudah ada STO ($existing->sto_code) tipe $stoType aktif untuk periode $request->periode.";
            $flag = 1;
        }
 
        // validasi per baris (basis per baris)
        if (is_array($mappings)) {
            $refSeen = [];
            foreach ($mappings as $i => $m) {
                $rowNo = $i + 1;
                $ttype = $this->normalizeTargetType($m['target_type'] ?? '');
 
                if (empty($m['target_ref'])) {
                    $label = $ttype === 'LOCATION' ? 'Lokasi' : 'Partner';
                    $pesan[] = "$label baris $rowNo wajib dipilih."; $flag = 1;
                }
                if (empty($m['sto_date'])) { $pesan[] = "STO Date baris $rowNo wajib diisi."; $flag = 1; }
                if (empty($m['counter1'])) { $pesan[] = "Counter 1 baris $rowNo wajib dipilih."; $flag = 1; }
                if (!empty($m['counter2']) && !empty($m['counter1']) && $m['counter1'] == $m['counter2']) {
                    $pesan[] = "Counter 1 dan Counter 2 baris $rowNo tidak boleh sama."; $flag = 1;
                } if (!empty($m['counter3']) && !empty($m['counter1']) && $m['counter1'] == $m['counter3']) {
    $pesan[] = "Counter 1 dan Counter 3 baris $rowNo tidak boleh sama."; $flag = 1;
}
if (!empty($m['counter3']) && !empty($m['counter2']) && $m['counter2'] == $m['counter3']) {
    $pesan[] = "Counter 2 dan Counter 3 baris $rowNo tidak boleh sama."; $flag = 1;
}
                // dedup: kombinasi type+ref (lokasi & partner boleh sama kode tapi beda tipe)
                if (!empty($m['target_ref'])) {
                    $key = $ttype . '|' . $m['target_ref'];
                    if (in_array($key, $refSeen)) { $pesan[] = "Target baris $rowNo duplikat."; $flag = 1; }
                    $refSeen[] = $key;
                }
                $tp = isset($m['target_plan']) ? (float)$m['target_plan'] : 0;
                if ($tp < 0 || $tp > 100) { $pesan[] = "Target Akurasi baris $rowNo harus 0–100."; $flag = 1; }
            }
        }
 
        if ($flag == 1) {
            return response()->json(['status'=>0,'title'=>'Validasi Gagal','message'=>$pesan,'alert'=>'warning']);
        }
 
        DB::beginTransaction();
        try {
            $stoCode = $this->getLastCode($this->moduleCode);
            $targetPlanGlobal = round(collect($mappings)->avg(fn($m) => (float)($m['target_plan'] ?? 0)), 2);
 
            $configId = DB::table('sto_config')->insertGetId([
                'sto_code'    => $stoCode,
                'sto_type'    => $stoType,
                'periode'     => $request->periode,
                'target_plan' => $targetPlanGlobal,
                'target_act'  => 0,
                'status'      => 1,
                'finish_time' => null,
                'notes'       => $request->notes,
                'created_by'  => Auth::user()->username,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_by'  => Auth::user()->username,
                'updated_at'  => date('Y-m-d H:i:s'),
            ], 'config_id');
 
            foreach ($mappings as $m) {
                $ttype = $this->normalizeTargetType($m['target_type'] ?? '');
               DB::table('sto_config_mapping')->insert([
    'config_id'       => $configId,
    'target_type'     => $ttype,
    'target_ref'      => $m['target_ref'],
    'location_number' => $ttype === 'LOCATION' ? $m['target_ref'] : null,
    'sto_date'        => $m['sto_date'],
    'no_dari'         => $m['no_dari'],       // ← tambah
    'no_sampai'       => $m['no_sampai'],     // ← tambah
    'finish_time'     => null,
    'counter1_user'   => $m['counter1'],
    'counter2_user'   => !empty($m['counter2']) ? $m['counter2'] : null,
    'counter3_user'   => !empty($m['counter3']) ? $m['counter3'] : null,
'is_blind'        => filter_var($m['is_blind'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'target_plan_loc' => (float)($m['target_plan'] ?? 0),
    'target_act_loc'  => 0,
    'notes'           => $m['note'] ?? null,
    'updated_by'      => Auth::user()->username,
    'updated_at'      => date('Y-m-d H:i:s'),
]);
            }
 
            DB::commit();
 
            return response()->json([
                'status'       => 1,
                'title'        => 'Berhasil',
                'message'      => "STO $stoCode berhasil dibuat.",
                'alert'        => 'success',
                'sto_code'     => $stoCode,
                'redirect_url' => route('stockTakingOrder.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>'Error','message'=>[$e->getMessage()],'alert'=>'error']);
        }
    }

    // ══════════════════════════════════════════════
    // SHOW
    // ══════════════════════════════════════════════
     public function show($id)
{
    $configId = Crypt::decryptString($id);

    $hdr = DB::table('sto_config as h')
        ->leftJoin('users as uc', 'uc.username', '=', 'h.created_by')
        ->select('h.*', 'uc.name as created_name')
        ->where('h.config_id', $configId)
        ->first();

    if (!$hdr) abort(404);

    $mappings = DB::table('sto_config_mapping as m')
        ->leftJoin('stock_location_master as l', function ($j) {
            $j->on('l.location_code', '=', 'm.target_ref')
              ->where('m.target_type', '=', 'LOCATION');
        })
        ->leftJoin('third_party as tp', function ($j) {
            $j->on('tp.kode', '=', 'm.target_ref')
              ->whereIn('m.target_type', ['SUPPLIER', 'CUSTOMER']);
        })
        ->leftJoin('users as u1', 'u1.id', '=', 'm.counter1_user')
        ->leftJoin('users as u2', 'u2.id', '=', 'm.counter2_user')
        ->leftJoin('users as u3', 'u3.id', '=', 'm.counter3_user')
        ->select(
            'm.mapping_id',
            'm.target_type',
            'm.target_ref',
            'm.sto_date',
            'm.finish_time',
            'm.target_plan_loc',
            'm.target_act_loc',
            'm.notes',
            'u1.name as counter1_name',
            'u2.name as counter2_name',
            'u3.name as counter3_name',
            DB::raw("COALESCE(l.location_name, tp.nama, m.target_ref) as target_name"),

            DB::raw("(
                SELECT COUNT(*) FROM sto_dtl d
                JOIN sto_hdr sh ON sh.sto_id = d.sto_id
                WHERE sh.target_type = m.target_type AND sh.target_ref = m.target_ref
                  AND sh.config_id = m.config_id
            ) as total_lines"),
            DB::raw("(
                SELECT COUNT(*) FROM sto_dtl d
                JOIN sto_hdr sh ON sh.sto_id = d.sto_id
                WHERE sh.target_type = m.target_type AND sh.target_ref = m.target_ref
                  AND sh.config_id = m.config_id AND d.count_status = 'MATCH'
            ) as match_lines"),
            DB::raw("(
                SELECT COUNT(*) FROM sto_dtl d
                JOIN sto_hdr sh ON sh.sto_id = d.sto_id
                WHERE sh.target_type = m.target_type AND sh.target_ref = m.target_ref
                  AND sh.config_id = m.config_id AND d.count_status = 'NOT MATCH'
            ) as notmatch_lines"),
            DB::raw("(
                SELECT COUNT(*) FROM sto_dtl d
                JOIN sto_hdr sh ON sh.sto_id = d.sto_id
                WHERE sh.target_type = m.target_type AND sh.target_ref = m.target_ref
                  AND sh.config_id = m.config_id AND d.count_status = 'RECOUNT'
            ) as recount_lines"),
            // ── BARU: dari total recount_lines, berapa yang masuk toleransi (dianggap akurat) ──
            DB::raw("(
                SELECT COUNT(*) FROM sto_dtl d
                JOIN sto_hdr sh ON sh.sto_id = d.sto_id
                WHERE sh.target_type = m.target_type AND sh.target_ref = m.target_ref
                  AND sh.config_id = m.config_id AND d.count_status = 'RECOUNT'
                  AND d.qty_system IS NOT NULL AND d.qty_system <> 0
                  AND ABS(d.qty_variance) / ABS(d.qty_system) * 100 <=
                      CASE WHEN m.target_plan_loc > 0 AND m.target_plan_loc < 100
                           THEN 100 - m.target_plan_loc ELSE 0 END
            ) as recount_in_tolerance"),
            DB::raw("(
                SELECT COUNT(*) FROM sto_dtl d
                JOIN sto_hdr sh ON sh.sto_id = d.sto_id
                WHERE sh.target_type = m.target_type AND sh.target_ref = m.target_ref
                  AND sh.config_id = m.config_id AND d.count_status = 'INCOMPLETE'
            ) as incomplete_lines")
        )
        ->where('m.config_id', $configId)
        ->orderBy('m.sto_date')
        ->orderBy('target_name')
        ->get();

    $data = [
        'title'    => $this->title,
        'subtitle' => "Detail $this->title",
        'hdr'      => $hdr,
        'mappings' => $mappings,
        'status'   => $this->statusList(),
        'stoTypes' => $this->stoTypeList(),
    ];

    return view("stockTakingOrder.show", $data);
}

    // ══════════════════════════════════════════════
    // EDIT
    // ══════════════════════════════════════════════
    public function edit($id)
    {
        $configId = Crypt::decryptString($id);
 
        $hdr = DB::table('sto_config')
            ->where('config_id', $configId)
            ->whereIn('status', [1, 2])
            ->first();
 
        if (!$hdr) abort(403, 'Hanya STO berstatus SCHEDULED/ONGOING yang bisa diedit.');
 
        $data = [
            'title'     => $this->title,
            'subtitle'  => "Edit $this->title",
            'oEdit'     => true,
            'hdr'       => $hdr,
            'stoTypes'  => $this->stoTypeList(),
            'locations' => DB::table('stock_location_master')->select('location_code','location_name')->orderBy('location_name')->get(),
            'users'     => DB::table('users')->select('id','name','username')->orderBy('name')->get(),
        ];
 
        return view("stockTakingOrder.create", $data);
    }
 
    // ══════════════════════════════════════════════
    // UPDATE
    // ══════════════════════════════════════════════
    public function update(Request $request)
    {
        $configId = Crypt::decryptString($request->config_id);
        $mappings = json_decode($request->mappings, true);
 
        $hdr = DB::table('sto_config')
            ->where('config_id', $configId)
            ->whereIn('status', [1, 2])
            ->first();
 
        if (!$hdr) {
    return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Hanya STO SCHEDULED/ONGOING yang bisa diedit.'],'alert'=>'error']);
}

$flag = 0; $pesan = []; $refSeen = [];

// ── validasi standar per baris ──
if (is_array($mappings)) {
    foreach ($mappings as $i => $m) {
        $rowNo = $i + 1;
        $ttype = $this->normalizeTargetType($m['target_type'] ?? '');
        if (empty($m['target_ref'])) {
            $label = $ttype === 'LOCATION' ? 'Lokasi' : 'Partner';
            $pesan[] = "$label baris $rowNo wajib dipilih."; $flag = 1;
        }
        if (empty($m['sto_date'])) { $pesan[] = "STO Date baris $rowNo wajib diisi."; $flag = 1; }
        if (empty($m['counter1'])) { $pesan[] = "Counter 1 baris $rowNo wajib dipilih."; $flag = 1; }
        if (!empty($m['counter2']) && !empty($m['counter1']) && $m['counter1'] == $m['counter2']) {
            $pesan[] = "Counter 1 dan Counter 2 baris $rowNo tidak boleh sama."; $flag = 1;
        }
        if (!empty($m['target_ref'])) {
            $key = $ttype . '|' . $m['target_ref'];
            if (in_array($key, $refSeen)) { $pesan[] = "Target baris $rowNo duplikat."; $flag = 1; }
            $refSeen[] = $key;
        }
        $tp = isset($m['target_plan']) ? (float)$m['target_plan'] : 0;
        if ($tp < 0 || $tp > 100) { $pesan[] = "Target Akurasi baris $rowNo harus 0–100."; $flag = 1; }
    }
} else {
    $pesan[] = "Mapping tidak valid."; $flag = 1;
}

// ── guard: baris yang sudah punya progress counting tidak boleh ganti tipe/target ──
$progressCombos = DB::table('sto_hdr')
    ->where('config_id', $configId)
    ->select('target_type', 'target_ref')
    ->distinct()
    ->get()
    ->map(fn($r) => $r->target_type . '|' . $r->target_ref)
    ->toArray();

$existingRows = DB::table('sto_config_mapping')
    ->where('config_id', $configId)
    ->get()
    ->keyBy('mapping_id');

if (is_array($mappings)) {
    foreach ($mappings as $i => $m) {
        $mappingId = !empty($m['mapping_id']) ? (int)$m['mapping_id'] : null;
        if (!$mappingId || !isset($existingRows[$mappingId])) continue;

        $old    = $existingRows[$mappingId];
        $oldKey = $old->target_type . '|' . $old->target_ref;
        $ttype  = $this->normalizeTargetType($m['target_type'] ?? '');

        if (in_array($oldKey, $progressCombos)
            && ($old->target_type !== $ttype || $old->target_ref !== $m['target_ref'])) {
            $rowNo = $i + 1;
            $pesan[] = "Baris $rowNo: target sudah punya progress counting, tipe/targetnya tidak bisa diubah.";
            $flag = 1;
        }
    }
}

if ($flag == 1) {
    return response()->json(['status'=>0,'title'=>'Validasi Gagal','message'=>$pesan,'alert'=>'warning']);
}

DB::beginTransaction();
try {
            $targetPlanGlobal = round(collect($mappings)->avg(fn($m) => (float)($m['target_plan'] ?? 0)), 2);
 
            DB::table('sto_config')
                ->where('config_id', $configId)
                ->update([
                    'target_plan' => $targetPlanGlobal,
                    'notes'       => $request->notes,
                    'updated_by'  => Auth::user()->username,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
 
           $existingIds  = DB::table('sto_config_mapping')
    ->where('config_id', $configId)
    ->pluck('mapping_id')
    ->toArray();

$submittedIds = [];

foreach ($mappings as $m) {
    $ttype = $this->normalizeTargetType($m['target_type'] ?? '');

    $payload = [
        'target_type'     => $ttype,
        'target_ref'      => $m['target_ref'],
        'location_number' => $ttype === 'LOCATION' ? $m['target_ref'] : null,
        'sto_date'        => $m['sto_date'],
        'no_dari'         => $m['no_dari'],
        'no_sampai'       => $m['no_sampai'],
        'counter1_user'   => $m['counter1'],
        'counter2_user'   => !empty($m['counter2']) ? $m['counter2'] : null,
        'counter3_user'   => !empty($m['counter3']) ? $m['counter3'] : null,
        'is_blind'        => filter_var($m['is_blind'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'target_plan_loc' => (float)($m['target_plan'] ?? 0),
        'notes'           => $m['note'] ?? null,
        'updated_by'      => Auth::user()->username,
        'updated_at'      => date('Y-m-d H:i:s'),
    ];

    $mappingId = !empty($m['mapping_id']) ? (int)$m['mapping_id'] : null;

    if ($mappingId && in_array($mappingId, $existingIds)) {
        // baris lama → UPDATE saja, finish_time & target_act_loc tetap terjaga
        DB::table('sto_config_mapping')
            ->where('mapping_id', $mappingId)
            ->where('config_id', $configId) // guard: pastikan milik config ini
            ->update($payload);
        $submittedIds[] = $mappingId;
    } else {
        // baris baru → INSERT
        $payload['config_id']      = $configId;
        $payload['finish_time']    = null;
        $payload['target_act_loc'] = 0;
        $submittedIds[] = DB::table('sto_config_mapping')->insertGetId($payload, 'mapping_id');
    }
}

// baris yang dihapus user dari form → DELETE
$toDelete = array_diff($existingIds, $submittedIds);
if (!empty($toDelete)) {
    DB::table('sto_config_mapping')
        ->where('config_id', $configId)
        ->whereIn('mapping_id', $toDelete)
        ->delete();
}
 
            DB::commit();
 
            return response()->json([
                'status'       => 1,
                'title'        => 'Berhasil',
                'message'      => "STO $hdr->sto_code berhasil diupdate.",
                'alert'        => 'success',
                'redirect_url' => route('stockTakingOrder.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>'Error','message'=>[$e->getMessage()],'alert'=>'error']);
        }
    }

    // ══════════════════════════════════════════════
    // CANCEL  (SCHEDULED/ONGOING → CANCELED)
    // ══════════════════════════════════════════════
    public function cancel(Request $request)
    {
        $configId = Crypt::decryptString($request->id);

        $hdr = DB::table('sto_config')
            ->where('config_id', $configId)
            ->whereIn('status', [1, 2])
            ->first();

        if (!$hdr) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['STO tidak ditemukan atau sudah tidak aktif.'],'alert'=>'error']);
        }

        // blok cancel bila sudah ada counting
        $used = DB::table('sto_hdr')->where('config_id', $configId)->exists();
        if ($used) {
            return response()->json([
                'status'  => 0,
                'title'   => 'Tidak Bisa Dibatalkan',
                'message' => ['STO ini sudah dipakai Stock Count. Tidak bisa dibatalkan.'],
                'alert'   => 'warning',
            ]);
        }

        DB::table('sto_config')
            ->where('config_id', $configId)
            ->update([
                'status'     => 5,
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'status'  => 1,
            'title'   => 'Berhasil',
            'message' => "STO $hdr->sto_code dibatalkan.",
            'alert'   => 'success',
        ]);
    }

    // ══════════════════════════════════════════════
    // HELPER — dipanggil dari modul Stock Count
    // Transisi status + hitung target_act + finish_time
    // ══════════════════════════════════════════════

    /**
     * Panggil saat counter pertama input di config ini.
     * SCHEDULED (1) → ONGOING (2).
     */
    public function markOngoing($configId)
    {
        DB::table('sto_config')
            ->where('config_id', $configId)
            ->where('status', 1)
            ->update([
                'status'     => 2,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Recalculate realisasi (target_act) per lokasi & global.
     * target_act_loc = (jumlah baris MATCH / total baris) * 100 di lokasi tsb.
     * Dipanggil setiap kali ada perubahan di sto_dtl.
     */
   public function recalcTargetAct($configId)
{
    $mappings = DB::table('sto_config_mapping')
        ->where('config_id', $configId)
        ->get();

    foreach ($mappings as $m) {
        $tolerance = $this->resolveTolerancePercent($m->target_plan_loc);

        $base = DB::table('sto_dtl as d')
            ->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id')
            ->where('h.config_id', $configId)
            ->where('h.target_type', $m->target_type)
            ->where('h.target_ref', $m->target_ref);

        $total = (clone $base)->count();

        $accurate = (clone $base)
            ->where(function ($q) use ($tolerance) {
                $q->where('d.count_status', 'MATCH');
                if ($tolerance > 0) {
                    $q->orWhere(function ($q2) use ($tolerance) {
                        $q2->where('d.count_status', 'RECOUNT')
                           ->whereNotNull('d.qty_system')
                           ->where('d.qty_system', '<>', 0)
                           ->whereRaw('ABS(d.qty_variance) / ABS(d.qty_system) * 100 <= ?', [$tolerance]);
                    });
                }
            })
            ->count();

        $actLoc = $total > 0 ? round(($accurate / $total) * 100, 2) : 0;

        DB::table('sto_config_mapping')
            ->where('mapping_id', $m->mapping_id)
            ->update(['target_act_loc' => $actLoc, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    $actGlobal = DB::table('sto_config_mapping')
        ->where('config_id', $configId)
        ->avg('target_act_loc');

    DB::table('sto_config')
        ->where('config_id', $configId)
        ->update(['target_act' => round($actGlobal ?? 0, 2), 'updated_at' => date('Y-m-d H:i:s')]);
}

      public function markMappingFinished($mappingId)
    {
        $now = date('Y-m-d H:i:s');
 
        $map = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->first();
        if (!$map) return;
 
        // isi finish_time baris bila belum
        if (empty($map->finish_time)) {
            DB::table('sto_config_mapping')
                ->where('mapping_id', $mappingId)
                ->update(['finish_time' => $now, 'updated_at' => $now]);
        }
 
        $configId = $map->config_id;
 
        // header.finish_time = baris terakhir (MAX)
        $maxFinish = DB::table('sto_config_mapping')
            ->where('config_id', $configId)
            ->whereNotNull('finish_time')
            ->max('finish_time');
 
        // apakah semua baris sudah selesai?
        $unfinished = DB::table('sto_config_mapping')
            ->where('config_id', $configId)
            ->whereNull('finish_time')
            ->count();
 
        $update = [
            'finish_time' => $maxFinish,
            'updated_at'  => $now,
        ];
        // semua rampung → COMPLETED
        if ($unfinished == 0) {
            $update['status'] = 3;
        }
 
        DB::table('sto_config')
            ->where('config_id', $configId)
            ->whereIn('status', [1, 2])
            ->update($update);
    }

    /**
     * Tandai selesai: ONGOING (2) → COMPLETED (3), isi finish_time.
     * Dipanggil manual (tombol Finalize accounting) atau otomatis
     * saat semua target_act_loc >= target_plan_loc.
     */
    public function markCompleted($configId)
    {
        DB::table('sto_config')
            ->where('config_id', $configId)
            ->whereIn('status', [1, 2])
            ->update([
                'status'      => 3,
                'finish_time' => date('Y-m-d H:i:s'),
                'updated_by'  => Auth::user()->username,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
    }

   public function getMappings(Request $request)
{
    $configId = Crypt::decryptString($request->config_id);

    $mappings = DB::table('sto_config_mapping as m')
        ->leftJoin('stock_location_master as l', function ($j) {
            $j->on('l.location_code', '=', 'm.target_ref')
              ->where('m.target_type', '=', 'LOCATION');
        })
        ->leftJoin('third_party as tp', function ($j) {
            $j->on('tp.kode', '=', 'm.target_ref')
              ->whereIn('m.target_type', ['SUPPLIER', 'CUSTOMER']);
        })
        ->where('m.config_id', $configId)
        ->orderBy('m.mapping_id')
        ->select([
            'm.mapping_id',
            'm.target_type',
            'm.target_ref',
            'm.sto_date',
            'm.no_dari',
            'm.no_sampai',
            'm.counter1_user',
            'm.counter2_user',
            'm.counter3_user',
            'm.is_blind',
            'm.target_plan_loc',
            'm.finish_time',
            'l.location_name',
            'tp.nama',
            DB::raw("EXISTS (
                SELECT 1 FROM sto_hdr sh
                WHERE sh.config_id = m.config_id
                  AND sh.target_type = m.target_type
                  AND sh.target_ref = m.target_ref
            ) as has_progress"),
        ])
        ->get();

    $mappings = $mappings->map(function ($m) {
        $m->label        = $m->location_name ?? $m->nama ?? $m->target_ref;
        $m->is_blind     = in_array($m->is_blind, [true, 1, '1', 't', 'true'], true);
        $m->has_progress = in_array($m->has_progress, [true, 1, '1', 't', 'true'], true);
        return $m;
    });

    return response()->json($mappings);
}
}