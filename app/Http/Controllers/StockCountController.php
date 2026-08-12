<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Jobs\RecalcStoAccuracyJob;
use Illuminate\Support\Str;
use DataTables;
use DB;

class StockCountController extends Controller
{
    private $moduleCode = 'COUNT';

    // Lokasi yang generate sto_number otomatis per artikel (1 artikel = 1 sto_hdr),
    // SEKALIGUS wajib unik artikel secara GLOBAL per mapping (lintas nomor STO).
    // 005=Chemical, 006=Consumable, 042=Dead Stock Chemical, 049=Expired Consumable
    private $autoNumberLocations = ['005', '006', '042', '049'];

    private $locationArticleTypeMap = [
        '042' => ['CM1'],
        '009' => ['RMP', 'RMNP'],
        '007' => ['FG'],
        '008' => ['FG'],
        '006' => ['CM2', 'CM3', 'RMP', 'RMNP'],
        '005' => ['CM1'],
        '049' => ['CM1'],
    ];

    // lokasi yang juga harus include CPA berdasarkan group_of_material
private $locationGroupOfMaterialMap = [
    '006' => ['CPA'],
];

   
    private function isAutoNumber($targetRef)
    {
        return in_array($targetRef, $this->autoNumberLocations);
    }

    private $accountingUsername = 'leo';

// SESUDAH
// SESUDAH — pakai Spatie
private function isAccountingUser()
{
    $user = Auth::user();
    return $user->username === $this->accountingUsername
        || $user->hasRole('Superuser');
}

// role 'accounting' saat INPUT baris baru diarahkan ke slot DB counter1
private function dbRole($role)
{
    return $role === 'accounting' ? 'counter1' : $role;
}
 
    private function getTableColoumnAudit()
{
    $kolom = [
        ['data'=>'location',          'name'=>'target_name',       'title'=>'Lokasi/Partner'],
        ['data'=>'article_code',      'name'=>'article_code',      'title'=>'Article Code'],
        ['data'=>'article_desc',      'name'=>'article_desc',      'title'=>'Article Desc'],
        ['data'=>'min_package',       'name'=>'min_package',       'title'=>'Packing'],
        ['data'=>'qty_counter1',      'name'=>'qty_counter1',      'title'=>'Qty C1'],
        ['data'=>'qty_counter2',      'name'=>'qty_counter2',      'title'=>'Qty C2'],
        ['data'=>'qty_counter3',      'name'=>'qty_counter3',      'title'=>'Qty C3'],
        ['data'=>'qty_system',        'name'=>'qty_system',        'title'=>'Stock System'],
        ['data'=>'qty_variance',      'name'=>'qty_variance',      'title'=>'Variance'],
        ['data'=>'uom',               'name'=>'uom',               'title'=>'UOM'],
        ['data'=>'count_status',      'name'=>'count_status',      'title'=>'Status'],
        ['data'=>'sto_numbers_label', 'name'=>'sto_numbers_label', 'title'=>'STO Number'],
        ['data'=>'rincian',           'name'=>'rincian',           'title'=>'Rincian', 'orderable'=>false, 'searchable'=>false],
    ];
    return json_encode($kolom, true);
}

 // ══════════════════════════════════════════════
// KOLOM AUDIT DETAIL (mentah — tanpa system/variance/status)
// ══════════════════════════════════════════════
private function getTableColoumnAuditDetail()
{
    $kolom = [
        ['data'=>'sto_code',      'name'=>'c.sto_code',    'title'=>'STO Code'],
        ['data'=>'location',      'name'=>'target_name',   'title'=>'Lokasi/Partner'],
        ['data'=>'article_code',  'name'=>'article_code',  'title'=>'Article Code'],
        ['data'=>'article_desc',  'name'=>'article_desc',  'title'=>'Article Desc'],
        ['data'=>'min_package',   'name'=>'min_package',   'title'=>'Packing'],
        ['data'=>'qty_counter1',  'name'=>'qty_counter1',  'title'=>'Qty C1'],
        ['data'=>'qty_counter2',  'name'=>'qty_counter2',  'title'=>'Qty C2'],
        ['data'=>'qty_counter3',  'name'=>'qty_counter3',  'title'=>'Qty C3'],
        ['data'=>'uom',           'name'=>'uom',           'title'=>'UOM'],
        ['data'=>'sto_number',    'name'=>'h.sto_number',  'title'=>'STO Number'],
        ['data'=>'counter1_name', 'name'=>'counter1_name', 'title'=>'Counter 1'],
        ['data'=>'counter1_at',   'name'=>'counter1_at',   'title'=>'Counter 1 At'],
        ['data'=>'counter2_name', 'name'=>'counter2_name', 'title'=>'Counter 2'],
        ['data'=>'counter2_at',   'name'=>'counter2_at',   'title'=>'Counter 2 At'],
        ['data'=>'counter3_name', 'name'=>'counter3_name', 'title'=>'Counter 3'],
        ['data'=>'counter3_at',   'name'=>'counter3_at',   'title'=>'Counter 3 At'],
    ];
    return json_encode($kolom, true);
}
 
    // ══════════════════════════════════════════════
    // GENERATE STO NUMBER — ambil no_current mapping,
    // increment, lalu format periode/nomor
    // ══════════════════════════════════════════════
    private function generateStoNumber($mappingId)
    {
        $m = DB::table('sto_config_mapping')
            ->where('mapping_id', $mappingId)
            ->lockForUpdate()
            ->first();
 
        $config = DB::table('sto_config')->where('config_id', $m->config_id)->first();
 
        $noDari   = $m->no_dari   ?? 1;
        $noSampai = $m->no_sampai ?? 9999;
        $current  = (int) ($m->no_current ?? 0);
 
        // kalau belum pernah dipakai / masih di bawah no_dari, mulai dari no_dari
        $nextNo = ($current < $noDari) ? $noDari : $current + 1;
 
        if ($nextNo > $noSampai) {
            abort(422, "Nomor STO untuk target ini sudah mencapai batas maksimum ({$noSampai}). Hubungi Accounting untuk perluas range.");
        }
 
        DB::table('sto_config_mapping')
            ->where('mapping_id', $mappingId)
            ->update(['no_current' => $nextNo, 'updated_at' => date('Y-m-d H:i:s')]);
 
        $periode = str_replace('-', '/', substr($config->periode, 0, 7));
        return $periode . '/' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);
    }
 
    // ══════════════════════════════════════════════
    // CEK DUPLIKASI ARTIKEL — scope beda per tipe target:
    //  - LOCATION yang masuk $autoNumberLocations (005/006/042/049):
    //      unik GLOBAL per mapping (lintas nomor STO)
    //  - LOCATION lain:
    //      unik hanya dalam SATU sto_id (nomor STO) yang sama
    //  - PARTNER (SUPPLIER/CUSTOMER):
    //      unik per location_number yang sama (lintas nomor STO)
    // ══════════════════════════════════════════════
  private function isDuplicateArticle($m, $mappingId, $stoId, $article, $isManual, $articleDesc, $locationNumber = null, $excludeDtlId = null)
{
    $query = DB::table('sto_dtl as d')->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id');

    if ($m->target_type === 'LOCATION' && $this->isAutoNumber($m->target_ref)) {
        $query->where('h.mapping_id', $mappingId);
    } elseif ($m->target_type === 'LOCATION') {
        if (!$stoId) return false; // sto baru dibuat, belum ada baris apa pun di dalamnya
        $query->where('d.sto_id', $stoId);
    } else {
        // SUPPLIER/CUSTOMER: hanya dicek dalam kartu (sto) yang sama.
        // STO number beda meski lokasi sama → dianggap kartu berbeda, boleh input.
        if (!$stoId) return false;
        $query->where('d.sto_id', $stoId);
    }

    if ($excludeDtlId) $query->where('d.dtl_id', '!=', $excludeDtlId);

    if ($isManual) {
        $query->whereNull('d.article_code')->whereRaw('UPPER(d.article_desc) = ?', [strtoupper(trim($articleDesc ?? ''))]);
    } else {
        $query->where('d.article_code', $article);
    }

    return $query->exists();
}
 
    private function duplicateArticleMessage($m, $label)
    {
        if ($m->target_type === 'LOCATION' && $this->isAutoNumber($m->target_ref)) {
            return "Artikel {$label} sudah pernah diinput untuk gudang ini (berlaku lintas nomor STO).";
        }
        if ($m->target_type === 'LOCATION') {
            return "Artikel {$label} sudah ada di baris lain pada sheet yang sama.";
        }
        return "Artikel {$label} sudah pernah diinput untuk lokasi ini (partner sama).";
    }
 
    // ══════════════════════════════════════════════
    // GET AVAILABLE NUMBERS (mode NON-AUTO / non-consumable)
    // ══════════════════════════════════════════════
    public function getAvailableNumbers(Request $request)
    {
        $mappingId = Crypt::decryptString($request->mapping_id);
 
        $m = DB::table('sto_config_mapping as m')
            ->join('sto_config as c', 'c.config_id', '=', 'm.config_id')
            ->where('m.mapping_id', $mappingId)
            ->select('m.*', 'c.periode')
            ->first();
 
        if (!$m) return response()->json(['available' => []]);
 
        $noDari   = $m->no_dari   ?? 1;
        $noSampai = $m->no_sampai ?? 9999;
        $periodeDisplay = str_replace('-', '/', substr($m->periode, 0, 7));
 
        $usedNumbers = DB::table('sto_hdr')
            ->where('mapping_id', $mappingId)
            ->pluck('sto_number')
            ->map(function ($num) {
                $parts = explode('/', $num);
                return (int) end($parts);
            })
            ->toArray();
 
        $available = [];
        for ($n = $noDari; $n <= $noSampai; $n++) {
            if (!in_array($n, $usedNumbers)) {
                $available[] = [
                    'no'    => $n,
                    'label' => $periodeDisplay . '/' . str_pad($n, 4, '0', STR_PAD_LEFT),
                ];
            }
        }
 
        return response()->json([
            'available'       => $available,
            'no_dari'         => $noDari,
            'no_sampai'       => $noSampai,
            'periode_display' => $periodeDisplay,
            'range_label'     => $periodeDisplay . '/' . str_pad($noDari, 4, '0', STR_PAD_LEFT)
                                . ' – ' . $periodeDisplay . '/' . str_pad($noSampai, 4, '0', STR_PAD_LEFT),
        ]);
    }
 
    // ══════════════════════════════════════════════
    // UPDATE LINE
    // ══════════════════════════════════════════════
    public function updateLine(Request $request, $dtlId)
{
    $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
    if (!$dtl) {
        return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Baris tidak ditemukan.'],'alert'=>'error']);
    }


    // ✅ FIX: fetch stoHdr dari sto_id milik dtl
    $stoHdr = DB::table('sto_hdr')->where('sto_id', $dtl->sto_id)->first();
    if (!$stoHdr) {
        return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Header STO tidak ditemukan.'],'alert'=>'error']);
    }

    $userId       = Auth::id();
    $isAccounting = $this->isAccountingUser();

    $m = DB::table('sto_config_mapping')->where('mapping_id', $stoHdr->mapping_id)->first();

    $role = null;
    if (!$isAccounting) {
        $role = $this->resolveCounterRole($dtl, $userId);
        if (!$role) {
            $access = $this->checkAccess($stoHdr->mapping_id);
            if (!$access['ok']) {
                return response()->json(['status'=>0,'title'=>'Ditolak','message'=>[$access['message']],'alert'=>'error']);
            }
            $role = $access['role'];
        }
    }

    $isManual = filter_var($request->is_manual, FILTER_VALIDATE_BOOLEAN);
    $article  = $isManual ? null : $request->article;

    if ($isManual && trim((string) $request->uom) === '') {
        return response()->json(['status'=>0,'title'=>'Warning','message'=>['UOM wajib diisi untuk artikel manual.'],'alert'=>'warning']);
    }

    $lineLocationNumber = $m->target_type === 'LOCATION'
        ? $m->target_ref
        : ($request->location_number ?: $dtl->location_number);

    if ($m->target_type !== 'LOCATION' && !$lineLocationNumber) {
        return response()->json(['status'=>0,'title'=>'Warning','message'=>['Lokasi wajib dipilih untuk partner.'],'alert'=>'warning']);
    }

    if ($this->isDuplicateArticle($m, $stoHdr->mapping_id, $dtl->sto_id, $article, $isManual, $request->article_desc, $lineLocationNumber, $dtlId)) {
        $label = $article ?: $request->article_desc;
        return response()->json(['status'=>0,'title'=>'Warning','message'=>[$this->duplicateArticleMessage($m, $label)],'alert'=>'warning']);
    }

    $now = date('Y-m-d H:i:s');

    $updates = [
        'article_code'    => $article,
        'article_desc'    => $request->article_desc,
        'is_manual'       => $isManual,
        'uom'             => $request->uom,
        'min_package'     => $request->min_package !== '' && $request->min_package !== null ? $request->min_package : 0,
        'location_number' => $lineLocationNumber,
        'note'            => $request->note,
        'updated_at'      => $now,
    ];

    if ($isAccounting) {
        // Leo: bebas ubah qty di 3 slot counter sekaligus. Kosong = di-null-kan.
        $anyQty = false;
        foreach (['counter1', 'counter2', 'counter3'] as $c) {
            if (!$request->has("qty_{$c}")) continue;
            $raw = $request->input("qty_{$c}");
            if ($raw === '' || $raw === null) {
                $updates["qty_{$c}"] = null;
            } else {
                $val = (float) str_replace(',', '', $raw);
                if ($val > 0) $anyQty = true;
                $updates["qty_{$c}"] = $val;
            }
        }
        if (!$anyQty) {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['Minimal salah satu QTY counter harus lebih dari 0.'],'alert'=>'warning']);
        }
    } else {
        $qty = (float) str_replace(',', '', $request->qty);
        if ($qty <= 0) {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['QTY harus lebih dari 0.'],'alert'=>'warning']);
        }
        $updates["qty_{$role}"] = $qty;
    }

    DB::table('sto_dtl')->where('dtl_id', $dtlId)->update($updates);

  $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
$this->syncArticleStatus($m, $dtl);
$dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
    $this->recalcMappingProgress($stoHdr->mapping_id);
    $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $stoHdr->mapping_id)->value('target_act_loc');

    $myQty = $isAccounting ? $dtl->qty_counter1 : $dtl->{"qty_{$role}"};

    return response()->json([
        'status'  => 1,
        'title'   => 'Berhasil',
        'message' => 'Baris diperbarui.',
        'alert'   => 'success',
        'target_act_loc' => (float) $freshTargetAct,
        'row' => [
            'dtl_id'         => $dtl->dtl_id,
            'sto_number'     => $stoHdr->sto_number,
            'article_code'   => $dtl->article_code ?? 'OTHER',
            'article_desc'   => $dtl->article_desc,
            'uom'            => $dtl->uom,
            'min_package'    => $dtl->min_package,
            'my_qty'         => $myQty !== null ? number_format((float) $myQty, 2) : null,
            'qty_counter1'   => $dtl->qty_counter1,
            'qty_counter2'   => $dtl->qty_counter2,
            'qty_counter3'   => $dtl->qty_counter3,
            'count_status'   => $dtl->count_status,
            'note'           => $dtl->note,
            'location_number'=> $dtl->location_number,
            'location_name'  => $this->resolveLocationName($dtl->location_number),
        ],
    ]);
}
 
    // ══════════════════════════════════════════════
    // INDEX
    // ══════════════════════════════════════════════
    public function index(Request $request)
    {
        $userId = Auth::id();
        $allowedUserIds = [58, 75, 23, 163, 176];
        $isAcct = in_array($userId, $allowedUserIds);
        $today  = date('d-m-Y');
 
        $query = DB::table('sto_config_mapping as m')
            ->join('sto_config as h', 'h.config_id', '=', 'm.config_id')
            ->leftJoin('stock_location_master as l', function ($j) {
                $j->on('l.location_code', '=', 'm.target_ref')->where('m.target_type', '=', 'LOCATION');
            })
            ->leftJoin('third_party as tp', function ($j) {
                $j->on('tp.kode', '=', 'm.target_ref')->whereIn('m.target_type', ['SUPPLIER', 'CUSTOMER']);
            })
            // ── FIX: sebelumnya whereIn('h.status', [1,2]) — itu nyembunyikan
            // SEMUA target begitu config selesai (status=3). Sekarang cuma
            // exclude config yang CANCELED (5); SCHEDULED/ONGOING/COMPLETED tetap lolos. ──
            ->where('h.status', '!=', 5)
            ->select(
                'm.mapping_id', 'm.target_type', 'm.target_ref', 'm.sto_date',
                'm.finish_time', 'm.counter1_user', 'm.counter2_user',
                'h.sto_code', 'h.periode', 'h.status as config_status',
                DB::raw("COALESCE(l.location_name, tp.nama, m.target_ref) as target_name")
            );
 
        if (!$isAcct) {
            // non-accounting: hanya tampil kalau TANGGAL cocok hari ini
            // (terlepas dari sudah finish_time atau belum — aturan cuma tanggal)
            $query->where('m.sto_date', $today)
                  ->where(function ($q) use ($userId) {
                      $q->where('m.counter1_user', $userId)
                        ->orWhere('m.counter2_user', $userId)
                        ->orWhere('m.counter3_user', $userId);
                  });
        }
 
        $rows = $query->orderBy('m.sto_date')->orderBy('target_name')->get();
 
        $stoCodesForFilter = collect();
        $targetsForFilter  = collect();
        if ($isAcct) {
            $stoCodesForFilter = DB::table('sto_config')
                ->select('sto_code')->orderByDesc('config_id')->get();
 
            $locations = DB::table('stock_location_master')
                ->select(DB::raw("'LOCATION' as target_type"), 'location_code as target_ref', 'location_name as target_name')
                ->orderBy('location_name')->get();
 
            $thirdParties = DB::table('third_party')
                ->whereIn('third_party_type', ['supp', 'cust'])
                ->select(
                    DB::raw("CASE WHEN third_party_type = 'supp' THEN 'SUPPLIER' ELSE 'CUSTOMER' END as target_type"),
                    'kode as target_ref', 'nama as target_name'
                )->orderBy('nama')->get();
 
            $targetsForFilter = $locations->concat($thirdParties);
        }
 
       return view('stockCount.index', [
    'title'             => 'Stock Count',
    'rows'              => $rows,
    'isAcct'            => $isAcct,
    'today'             => $today,
    'kolomAudit'        => $isAcct ? $this->getTableColoumnAudit() : null,
    'kolomAuditDetail'  => $isAcct ? $this->getTableColoumnAuditDetail() : null, // ← tambahkan ini
    'stoCodesForFilter' => $stoCodesForFilter,
    'targetsForFilter'  => $targetsForFilter,
]);
    }

    private function resolveMovementDateRange($stoDateDmy)
{
    $dateTo = $stoDateDmy ?: date('d-m-Y');

    $parts = explode('-', $dateTo); // [dd, mm, yyyy]
    if (count($parts) === 3) {
        $dateFrom = '01-'.$parts[1].'-'.$parts[2];
    } else {
        $dateFrom = date('01-m-Y');
    }

    return [$dateFrom, $dateTo];
}
 
    // ══════════════════════════════════════════════
    // AUDIT LIST
    // ══════════════════════════════════════════════
    public function auditList(Request $request)
{
    $allowedUserIds = [58, 75, 23, 163, 176, 52, 66, 152, 187, 67, 53];
    if (!in_array(Auth::id(), $allowedUserIds)) {
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    $rawRows = $this->buildAuditRawRows($request);

    $grouped = $rawRows->groupBy(function ($row) {
        $key = $row->article_code ?: ('MANUAL-' . $row->dtl_id);
        return $row->location_number . '|' . $key;
    });

    $result = collect();
    foreach ($grouped as $items) {
        $first = $items->first();
        [$status, $qtySystem, $variance] = $this->resolveGroupStatus($items);
        $realItems = $items->filter(fn($r) => !$this->isPhantomRow($r));

        $result->push((object) [
            'location_number' => $first->location_number,
            'target_name'     => $first->target_name,
            'article_code'    => $first->article_code,
            'article_desc'    => $first->article_desc,
            'uom'             => $first->uom,
            'min_package'     => $first->min_package,
            'sto_date'        => $first->sto_date ?? null,   // ← tambahkan
            'qty_counter1'    => $realItems->isEmpty() ? (float) $first->qty_counter1 : $realItems->sum('qty_counter1'),
            'qty_counter2'    => $realItems->isEmpty() ? (float) $first->qty_counter2 : $realItems->sum('qty_counter2'),
            'qty_counter3'    => $realItems->isEmpty() ? (float) $first->qty_counter3 : $realItems->sum('qty_counter3'),
            'qty_system'      => $qtySystem,
            'qty_variance'    => $variance,
            'count_status'    => $status,
            'sto_numbers'     => $items->pluck('sto_number')->filter()->unique()->values()->all(),
            'contributors'    => $realItems->map(fn($r) => [
                'sto_number'    => $r->sto_number,
                'qty_counter1'  => $r->qty_counter1,
                'qty_counter2'  => $r->qty_counter2,
                'qty_counter3'  => $r->qty_counter3,
                'counter1_name' => $r->counter1_name,
                'counter2_name' => $r->counter2_name,
                'counter3_name' => $r->counter3_name,
            ])->values()->all(),
        ]);
    }

    if ($request->filled('searchStatus')) {
        $result = $result->where('count_status', $request->searchStatus)->values();
    }

    return DataTables::of($result)
        ->addColumn('location', fn($row) => $row->target_name)
        ->editColumn('article_code', fn($row) => $row->article_code ?? 'OTHER')
        ->editColumn('qty_counter1', fn($row) => $row->qty_counter1 !== null ? number_format((float) $row->qty_counter1, 2) : '-')
        ->editColumn('qty_counter2', fn($row) => $row->qty_counter2 !== null ? number_format((float) $row->qty_counter2, 2) : '-')
        ->editColumn('qty_counter3', fn($row) => $row->qty_counter3 !== null ? number_format((float) $row->qty_counter3, 2) : '-')
        ->editColumn('min_package', fn($row) => $row->min_package !== null ? number_format((float) $row->min_package, 2) : '-')
        // ── qty_system sekarang jadi link yang langsung buka modal Movement ──
      ->editColumn('qty_system', function ($row) {
    if ($row->qty_system === null) return '-';
    $val = number_format((float) $row->qty_system, 2);

    if (empty($row->article_code) || empty($row->location_number)) {
        return $val;
    }

    $realCode = $this->resolveRealArticleCode($row->article_code);
    if (!$realCode) {
        // artikel tidak ketemu di master, jangan bikin link yang bakal gagal
        return $val;
    }

    [$dateFrom, $dateTo] = $this->resolveMovementDateRange($row->sto_date);

    $url = route('warehouse.articlev2', [
        'code'          => $row->article_code,   // alternative code, buat filter tampilan list
        'real_code'     => $realCode,             // article_code asli, buat filter movement
        'location'      => $row->location_number,
        'open_movement' => 1,
        'date_from'     => $dateFrom,
        'date_to'       => $dateTo,
        'desc'          => $row->article_desc,
    ]);

    return '<a href="'.$url.'" target="_blank">'.$val.'</a>';
})
        ->editColumn('qty_variance', function ($row) {
            if ($row->qty_variance === null) return '-';
            $val = number_format((float) $row->qty_variance, 2);
            $cls = (float) $row->qty_variance > 0 ? 'text-success' : ((float) $row->qty_variance < 0 ? 'text-danger' : '');
            return "<span class='{$cls}'>{$val}</span>";
        })
        ->editColumn('count_status', function ($row) {
            $map = ['INCOMPLETE' => 'badge-secondary', 'NOT MATCH' => 'badge-danger', 'RECOUNT' => 'badge-warning', 'MATCH' => 'badge-success'];
            $cls = $map[$row->count_status] ?? 'badge-secondary';
            return '<span class="badge ' . $cls . '">' . $row->count_status . '</span>';
        })
        ->addColumn('sto_numbers_label', fn($row) => count($row->sto_numbers) ? implode(', ', $row->sto_numbers) : '-')
        ->addColumn('rincian', function ($row) {
            if (empty($row->contributors)) {
                return '<button type="button" class="btn btn-sm btn-outline-secondary" disabled>Rincian</button>';
            }
            $payload = e(json_encode($row->contributors));
            $label   = e($row->article_desc);
            return '<button type="button" class="btn btn-sm btn-outline-primary btn-rincian-sto" '
                 . 'data-article="'.$label.'" data-contributors="'.$payload.'">'
                 . '<i data-feather="list" style="width:12px;height:12px;" class="mr-25"></i>Rincian</button>';
        })
        ->rawColumns(['location', 'qty_system', 'qty_variance', 'count_status', 'rincian']) // ← tambahkan qty_system
        ->make(true);
}

// ══════════════════════════════════════════════
// AUDIT LIST DETAIL — mentah per baris, TANPA stock system/variance/status
// ══════════════════════════════════════════════
public function auditListDetail(Request $request)
{
    $allowedUserIds = [58, 75, 23, 163, 176];
    if (!in_array(Auth::id(), $allowedUserIds)) {
        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    $rows = $this->buildAuditRawRows($request);

    return DataTables::of($rows)
      ->editColumn('sto_code', function ($row) {
    if (empty($row->sto_code)) return '-';
    $url = route('stockTakingOrder.show', ['id' => Crypt::encryptString($row->config_id)]);
    return '<a href="'.$url.'" target="_blank">'.e($row->sto_code).'</a>';
})
        ->editColumn('sto_number', fn($row) => $row->sto_number ?? '-')
        ->addColumn('location', fn($row) => $row->target_name)
        ->editColumn('article_code', fn($row) => $row->article_code ?? 'OTHER')
        ->editColumn('qty_counter1', fn($row) => $row->qty_counter1 !== null ? number_format((float) $row->qty_counter1, 2) : '-')
        ->editColumn('qty_counter2', fn($row) => $row->qty_counter2 !== null ? number_format((float) $row->qty_counter2, 2) : '-')
        ->editColumn('qty_counter3', fn($row) => $row->qty_counter3 !== null ? number_format((float) $row->qty_counter3, 2) : '-')
        ->editColumn('min_package', fn($row) => $row->min_package !== null ? number_format((float) $row->min_package, 2) : '-')
        ->editColumn('counter1_name', fn($row) => $row->counter1_name ?? '-')
        ->editColumn('counter2_name', fn($row) => $row->counter2_name ?? '-')
        ->editColumn('counter3_name', fn($row) => $row->counter3_name ?? '-')
        ->editColumn('counter1_at', fn($row) => $row->counter1_at ? date('d-m-Y H:i', strtotime($row->counter1_at)) : '-')
        ->editColumn('counter2_at', fn($row) => $row->counter2_at ? date('d-m-Y H:i', strtotime($row->counter2_at)) : '-')
        ->editColumn('counter3_at', fn($row) => $row->counter3_at ? date('d-m-Y H:i', strtotime($row->counter3_at)) : '-')
        ->rawColumns(['location', 'sto_code'])
        ->make(true);
}
 
    // ══════════════════════════════════════════════
    // GUARD
    // ══════════════════════════════════════════════
    private function checkAccess($mappingId)
{
    $userId = Auth::id();
    $isAcct = $this->isAccountingUser();
    $today  = date('d-m-Y');

    $m = DB::table('sto_config_mapping as m')
        ->join('sto_config as h', 'h.config_id', '=', 'm.config_id')
        ->where('m.mapping_id', $mappingId)
        ->select('m.*', 'h.status as config_status', 'h.sto_code', 'h.periode')
        ->first();

    if (!$m) return ['ok' => false, 'message' => 'Target STO tidak ditemukan.'];

    if ($m->config_status == 5) {
        return ['ok' => false, 'message' => 'STO ini sudah dibatalkan (CANCELED).'];
    }

    if ($isAcct) {
        return ['ok' => true, 'role' => 'accounting', 'mapping' => $m];
    }

    if ($m->finish_time) {
        if ($m->counter1_user == $userId) return ['ok' => true, 'role' => 'counter1', 'mapping' => $m];
        if ($m->counter2_user == $userId) return ['ok' => true, 'role' => 'counter2', 'mapping' => $m];
        if (($m->counter3_user ?? null) == $userId) return ['ok' => true, 'role' => 'counter3', 'mapping' => $m];
        return ['ok' => false, 'message' => 'Anda tidak terdaftar sebagai counter untuk target ini.'];
    }

    if ($m->sto_date !== $today) {
        return ['ok' => false, 'message' => "Hari ini bukan tanggal STO untuk target ini ($m->sto_date)."];
    }
    if ($m->counter1_user == $userId) return ['ok' => true, 'role' => 'counter1', 'mapping' => $m];
    if ($m->counter2_user == $userId) return ['ok' => true, 'role' => 'counter2', 'mapping' => $m];
    if (($m->counter3_user ?? null) == $userId) return ['ok' => true, 'role' => 'counter3', 'mapping' => $m];
    return ['ok' => false, 'message' => 'Anda tidak terdaftar sebagai counter untuk target ini.'];
}
 
    private function resolveLocationName($code)
    {
        if (!$code) return null;
        return DB::table('stock_location_master')->where('location_code', $code)->value('location_name') ?? $code;
    }
 
    private function resolveCounterRole($record, $userId)
    {
        if ($record->counter1_user == $userId) return 'counter1';
        if ($record->counter2_user == $userId) return 'counter2';
        if (($record->counter3_user ?? null) == $userId) return 'counter3';
        return null;
    }
 
    // ══════════════════════════════════════════════
    // CREATE — tampilkan halaman input counting
    // ══════════════════════════════════════════════
    public function create($encMappingId)
    {
        $mappingId = Crypt::decryptString($encMappingId);
        $access    = $this->checkAccess($mappingId);
        if (!$access['ok']) abort(403, $access['message']);
 
        $m         = $access['mapping'];
        $isPartner = in_array($m->target_type, ['SUPPLIER', 'CUSTOMER']);
        $isAuto    = $this->isAutoNumber($m->target_ref); // 005/006/042/049
 
        $targetName = DB::table('sto_config_mapping as mm')
            ->leftJoin('stock_location_master as l', function ($j) {
                $j->on('l.location_code', '=', 'mm.target_ref')->where('mm.target_type', '=', 'LOCATION');
            })
            ->leftJoin('third_party as tp', function ($j) {
                $j->on('tp.kode', '=', 'mm.target_ref')->whereIn('mm.target_type', ['SUPPLIER', 'CUSTOMER']);
            })
            ->where('mm.mapping_id', $mappingId)
            ->selectRaw("COALESCE(l.location_name, tp.nama, mm.target_ref) as target_name")
            ->value('target_name');
 
        // ambil semua sto_hdr milik mapping ini (bisa banyak untuk non-auto)
        $allHdrs = DB::table('sto_hdr')
            ->where('mapping_id', $mappingId)
            ->orderBy('sto_id')
            ->get();
 
        // untuk auto: ambil semua dtl lintas semua hdr, group by sto_number
        // untuk non-auto: sama, accordion per sto_number
        $sheets = collect();
        foreach ($allHdrs as $hdr) {
            $dtls = DB::table('sto_dtl as d')
                ->leftJoin('stock_location_master as l', 'l.location_code', '=', 'd.location_number')
                ->where('d.sto_id', $hdr->sto_id)
                ->orderBy('d.dtl_id')
                ->select('d.*', 'l.location_name')
                ->get()
                ->map(function ($l) use ($access) {
                    $userId = Auth::id();
                    $l->my_qty = null;
                    if ($access['role'] === 'accounting')             $l->my_qty = $l->qty_counter1;
                    elseif ($l->counter1_user == $userId)             $l->my_qty = $l->qty_counter1;
                    elseif ($l->counter2_user == $userId)             $l->my_qty = $l->qty_counter2;
                    elseif (($l->counter3_user ?? null) == $userId)   $l->my_qty = $l->qty_counter3;
                    return $l;
                });
 
            $sheets->push([
                'hdr'   => $hdr,
                'lines' => $dtls,
            ]);
        }
 
        // update status config SCHEDULED → ONGOING saat pertama diakses counter
        if ($m->config_status == 1 && $access['role'] !== 'accounting') {
            DB::table('sto_config')->where('config_id', $m->config_id)
                ->where('status', 1)
                ->update(['status' => 2, 'updated_at' => date('Y-m-d H:i:s')]);
        }
 
        $locations = collect();
        if ($isPartner) {
            $locations = DB::table('stock_location_master')
                ->select('location_code', 'location_name')
                ->orderBy('location_name')->get();
        }
 
        return view('stockCount.create', [
            'title'        => 'Stock Count — '.$targetName,
            'mapping'      => $m,
            'targetName'   => $targetName,
            'sheets'       => $sheets,
            'accessRole'   => $access['role'],
            'encMappingId' => $encMappingId,
            'locations'    => $locations,
            'isPartner'    => $isPartner,
            'isAuto'       => $isAuto,
        ]);
    }
 
    // ══════════════════════════════════════════════
    // GET ARTICLES
    // ══════════════════════════════════════════════
    public function getArticles(Request $request)
    {
        $mappingId = Crypt::decryptString($request->mapping_id);
        $m = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->first();
        if (!$m) return response()->json([]);
 
        if ($m->target_type === 'LOCATION') {
            return response()->json($this->articlesByLocation($m->target_ref));
        }
        return response()->json($this->resolveArticlesForPartner($m->target_ref, $m->target_type));
    }
 
    private function articlesByLocation($locationCode)
    {
        $types = $this->locationArticleTypeMap[$locationCode] ?? null;
 
        $query = DB::table('warehouse_stock as ws')
            ->join('article as a', 'a.article_alternative_code', '=', 'ws.article_code')
            ->where('ws.location_number', $locationCode)
            ->select('a.article_alternative_code', 'a.article_desc', 'a.uom', 'a.min_package', 'a.article_type');
 
        if ($types) {
            $query->whereIn('a.article_type', $types);
        }
 
        $inStock = $query->orderBy('a.article_desc')->get();
        $inStockCodes = $inStock->pluck('article_alternative_code');
 
        $othersQuery = DB::table('article as a')
            ->whereNotIn('a.article_alternative_code', $inStockCodes)
            ->select('a.article_alternative_code', 'a.article_desc', 'a.uom', 'a.min_package', 'a.article_type');
 
        if ($types) {
            $othersQuery->whereIn('a.article_type', $types);
        }
 
        $others = $othersQuery->orderBy('a.article_desc')->get();
 
        return ['in_stock' => $inStock, 'others' => $others];
    }
 
    private function resolveArticlesForPartner($partnerCode, $partnerType)
    {
        // Cross-reference: SUPP ↔ CUST (hanya beda suffix belakang)
        // Contoh: API000001SUPP ↔ API000001CUST
        $counterCode = preg_replace('/(SUPP)$/i', 'CUST', $partnerCode);
        if ($counterCode === $partnerCode) {
            $counterCode = preg_replace('/(CUST)$/i', 'SUPP', $partnerCode);
        }
 
        $partnerCodes = array_unique(array_filter([$partnerCode, $counterCode]));
 
        $rows = DB::table('article as a')
            ->whereIn('a.third_party', $partnerCodes)
            ->select('a.article_alternative_code', 'a.article_desc', 'a.uom', 'a.min_package',
                     'a.article_type', 'a.third_party')
            ->orderBy('a.third_party')   // grouping visual: CUST dulu / SUPP dulu
            ->orderBy('a.article_desc')
            ->get();
 
        return ['in_stock' => $rows, 'others' => collect()];
    }
 
    // ══════════════════════════════════════════════
    // STORE LINE — auto number (005/006/042/049)
    // 1 artikel = 1 sto_hdr = 1 sto_number
    // ══════════════════════════════════════════════
    public function storeLine(Request $request)
    {
        $mappingId = Crypt::decryptString($request->mapping_id);
        $access    = $this->checkAccess($mappingId);
        if (!$access['ok']) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>[$access['message']],'alert'=>'error']);
        }
 
        $m      = $access['mapping'];
        $userId = Auth::id();
        $isAuto = $this->isAutoNumber($m->target_ref);
 
        $locationNumber = $m->target_type === 'LOCATION'
            ? $m->target_ref
            : ($request->location_number ?: null);
 
        if ($m->target_type !== 'LOCATION' && !$locationNumber) {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['Lokasi wajib dipilih untuk partner.'],'alert'=>'warning']);
        }
 
        $isManual = filter_var($request->is_manual, FILTER_VALIDATE_BOOLEAN);
        $article  = $isManual ? null : $request->article;
        $qty      = (float) str_replace(',', '', $request->qty);
        $confirmAccumulate = filter_var($request->confirm_accumulate, FILTER_VALIDATE_BOOLEAN);
 
        if ($qty <= 0) {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['QTY harus lebih dari 0.'],'alert'=>'warning']);
        }
 
        $now = date('Y-m-d H:i:s');
 
       // SESUDAH
if ($isAuto) {
    return $this->storeLineAuto($mappingId, $m, $access, $userId, $locationNumber, $isManual, $article, $request, $qty, $confirmAccumulate, $now);
}

// ── MODE SHEET: inline add artikel ke nomor STO yang sudah ada ──
$selectedNumber = $request->selected_number ?? null;
if (!$selectedNumber) {
    return response()->json(['status'=>0,'title'=>'Warning','message'=>['Nomor STO wajib diisi untuk mode sheet.'],'alert'=>'warning']);
}

return $this->storeLineInline($mappingId, $m, $access, $userId, $locationNumber, $isManual, $article, $request, $qty, $now, $selectedNumber);
 
    }
 
    // ── AUTO: 1 artikel = 1 sto_hdr ──
    private function storeLineAuto($mappingId, $m, $access, $userId, $locationNumber, $isManual, $article, $request, $qty, $confirmAccumulate, $now)
{
    return DB::transaction(function () use ($mappingId, $m, $access, $userId, $locationNumber, $isManual, $article, $request, $qty, $confirmAccumulate, $now) {

        $dbRole = $this->dbRole($access['role']); // accounting → counter1

        if ($isManual) {
            $existingDtl = DB::table('sto_dtl as d')
                ->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id')
                ->where('h.mapping_id', $mappingId)
                ->whereNull('d.article_code')
                ->whereRaw('UPPER(d.article_desc) = ?', [strtoupper(trim($request->article_desc))])
                ->select('d.*', 'h.sto_id', 'h.sto_number', 'h.status as hdr_status')
                ->first();
        } else {
            $existingDtl = DB::table('sto_dtl as d')
                ->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id')
                ->where('h.mapping_id', $mappingId)
                ->where('d.article_code', $article)
                ->select('d.*', 'h.sto_id', 'h.sto_number', 'h.status as hdr_status')
                ->first();
        }

        if (!$existingDtl) {
            $stoNumber = $this->generateStoNumber($mappingId);

            $stoId = DB::table('sto_hdr')->insertGetId([
                'sto_number'  => $stoNumber,
                'mapping_id'  => $mappingId,
                'config_id'   => $m->config_id,
                'target_type' => $m->target_type,
                'target_ref'  => $m->target_ref,
                'status'      => 1,
                'created_by'  => Auth::user()->username,
                'created_at'  => $now,
                'updated_by'  => Auth::user()->username,
                'updated_at'  => $now,
            ], 'sto_id');

            $dtlId = DB::table('sto_dtl')->insertGetId([
                'sto_id'          => $stoId,
                'article_code'    => $article,
                'article_desc'    => $request->article_desc,
                'is_manual'       => $isManual,
                'uom'             => $request->uom,
                'min_package'     => $request->min_package ?: null,
                'location_number' => $locationNumber,
                "qty_{$dbRole}"   => $qty,
                "{$dbRole}_user"  => $userId,
                "{$dbRole}_at"    => $now,
                'note'            => $request->note,
                'created_at'      => $now,
                'updated_at'      => $now,
            ], 'dtl_id');

          $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
$this->syncArticleStatus($m, $dtl);
$dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
            $this->recalcMappingProgress($mappingId);
            $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->value('target_act_loc');
            $myQty = $dtl->{"qty_{$dbRole}"};

            return response()->json([
                'status'         => 1,
                'title'          => 'Berhasil',
                'message'        => "Baris tersimpan dengan nomor $stoNumber.",
                'alert'          => 'success',
                'sto_number'     => $stoNumber,
                'target_act_loc' => (float) $freshTargetAct,
                'row' => [
                    'dtl_id'         => $dtl->dtl_id,
                    'sto_number'     => $stoNumber,
                    'article_code'   => $dtl->article_code ?? 'OTHER',
                    'article_desc'   => $dtl->article_desc,
                    'uom'            => $dtl->uom,
                    'min_package'    => $dtl->min_package,
                    'my_qty'         => $myQty !== null ? number_format((float)$myQty, 2) : null,
                    'qty_counter1'   => $dtl->qty_counter1,
                    'qty_counter2'   => $dtl->qty_counter2,
                    'qty_counter3'   => $dtl->qty_counter3,
                    'count_status'   => $dtl->count_status,
                    'note'           => $dtl->note,
                    'location_number'=> $dtl->location_number,
                    'location_name'  => $this->resolveLocationName($dtl->location_number),
                ],
            ]);
        }

        if ($existingDtl->hdr_status != 1) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Sheet artikel ini sudah dikunci.'],'alert'=>'error']);
        }

        $field     = "qty_{$dbRole}";
        $userField = "{$dbRole}_user";
        $atField   = "{$dbRole}_at";
        $ownerId   = $existingDtl->{$userField} ?? null;

        if ($ownerId && $ownerId != $userId) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Slot counter ini sudah diisi user lain. Gunakan Edit untuk mengubah.'],'alert'=>'error']);
        }

        if ($ownerId == $userId && !$confirmAccumulate) {
            $existingQty = (float) $existingDtl->{$field};
            return response()->json([
                'status'          => 0,
                'confirm_required' => true,
                'title'           => 'Sudah Pernah Diinput',
                'existing_qty'    => $existingQty,
                'add_qty'         => $qty,
                'new_total'       => $existingQty + $qty,
                'article_desc'    => $existingDtl->article_desc,
                'message'         => "Artikel \"{$existingDtl->article_desc}\" sudah Anda input qty {$existingQty}. Tambahkan {$qty} lagi (total ".($existingQty + $qty).")?",
                'alert'           => 'question',
            ]);
        }

        if ($ownerId == $userId) $qty = (float) $existingDtl->{$field} + $qty;

        DB::table('sto_dtl')->where('dtl_id', $existingDtl->dtl_id)->update([
            $field     => $qty,
            $userField => $userId,
            $atField   => $now,
            'updated_at' => $now,
        ]);
      $dtl = DB::table('sto_dtl')->where('dtl_id', $existingDtl->dtl_id)->first();
$this->syncArticleStatus($m, $dtl);
$dtl = DB::table('sto_dtl')->where('dtl_id', $dtl->dtl_id)->first();
        $this->recalcMappingProgress($mappingId);
        $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->value('target_act_loc');
        $myQty = $dtl->{"qty_{$dbRole}"};

        return response()->json([
            'status'         => 1,
            'title'          => 'Berhasil',
            'message'        => 'Baris diperbarui.',
            'alert'          => 'success',
            'sto_number'     => $existingDtl->sto_number,
            'target_act_loc' => (float) $freshTargetAct,
            'row' => [
                'dtl_id'         => $dtl->dtl_id,
                'sto_number'     => $existingDtl->sto_number,
                'article_code'   => $dtl->article_code ?? 'OTHER',
                'article_desc'   => $dtl->article_desc,
                'uom'            => $dtl->uom,
                'min_package'    => $dtl->min_package,
                'my_qty'         => $myQty !== null ? number_format((float)$myQty, 2) : null,
                'qty_counter1'   => $dtl->qty_counter1,
                'qty_counter2'   => $dtl->qty_counter2,
                'qty_counter3'   => $dtl->qty_counter3,
                'count_status'   => $dtl->count_status,
                'note'           => $dtl->note,
                'location_number'=> $dtl->location_number,
                'location_name'  => $this->resolveLocationName($dtl->location_number),
            ],
        ]);
    });
}

// ══════════════════════════════════════════════
// STORE LINE INLINE — tambah artikel ke sheet
// (nomor STO) yang sudah ada (non-auto)
// ══════════════════════════════════════════════
private function storeLineInline($mappingId, $m, $access, $userId, $locationNumber, $isManual, $article, $request, $qty, $now, $selectedNumber)
{
    return DB::transaction(function () use ($mappingId, $m, $access, $userId, $locationNumber, $isManual, $article, $request, $qty, $now, $selectedNumber) {

        $dbRole = $this->dbRole($access['role']);

        // ── resolve sto_number dari selected_number (int atau string penuh) ──
        // Frontend kirim string penuh mis. "2026/07/0001"
        $stoNumber = $selectedNumber;

        // cari sto_hdr yang matching
        $stoHdr = DB::table('sto_hdr')
            ->where('mapping_id', $mappingId)
            ->where('sto_number', $stoNumber)
            ->lockForUpdate()
            ->first();

        if (!$stoHdr) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Nomor STO tidak ditemukan atau bukan milik target ini.'],'alert'=>'error']);
        }

        // ── cek max 7 baris per sheet ──
        $currentCount = DB::table('sto_dtl')->where('sto_id', $stoHdr->sto_id)->count();
        if ($currentCount >= 7) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Sheet ini sudah mencapai batas 7 baris.'],'alert'=>'warning']);
        }

        // ── cek duplikat dalam sheet ini ──
        if ($this->isDuplicateArticle($m, $mappingId, $stoHdr->sto_id, $article, $isManual, $request->article_desc, $locationNumber)) {
            $label = $article ?: $request->article_desc;
            return response()->json(['status'=>0,'title'=>'Warning','message'=>[$this->duplicateArticleMessage($m, $label)],'alert'=>'warning']);
        }

        // ── validasi UOM untuk manual ──
        if ($isManual && trim((string) $request->uom) === '') {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['UOM wajib diisi untuk artikel manual.'],'alert'=>'warning']);
        }

        // ── insert baris baru ──
        $dtlId = DB::table('sto_dtl')->insertGetId([
            'sto_id'          => $stoHdr->sto_id,
            'article_code'    => $article,
            'article_desc'    => $request->article_desc,
            'is_manual'       => $isManual,
            'uom'             => $request->uom,
            'min_package'     => $request->min_package ?: null,
            'location_number' => $locationNumber,
            "qty_{$dbRole}"   => $qty,
            "{$dbRole}_user"  => $userId,
            "{$dbRole}_at"    => $now,
            'note'            => $request->note,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], 'dtl_id');

       $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
$this->syncArticleStatus($m, $dtl);
$dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();

        $this->recalcMappingProgress($mappingId);
        $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->value('target_act_loc');

        $myQty = $dtl->{"qty_{$dbRole}"};

        return response()->json([
            'status'         => 1,
            'title'          => 'Berhasil',
            'message'        => "Artikel ditambahkan ke sheet {$stoNumber}.",
            'alert'          => 'success',
            'sto_number'     => $stoNumber,
            'target_act_loc' => (float) $freshTargetAct,
            'row' => [
                'dtl_id'          => $dtl->dtl_id,
                'sto_id'          => $stoHdr->sto_id,
                'sto_number'      => $stoNumber,
                'article_code'    => $dtl->article_code ?? 'OTHER',
                'article_desc'    => $dtl->article_desc,
                'uom'             => $dtl->uom,
                'min_package'     => $dtl->min_package,
                'my_qty'          => $myQty !== null ? number_format((float)$myQty, 2) : null,
                'qty_counter1'    => $dtl->qty_counter1,
                'qty_counter2'    => $dtl->qty_counter2,
                'qty_counter3'    => $dtl->qty_counter3,
                'count_status'    => $dtl->count_status,
                'note'            => $dtl->note,
                'location_number' => $dtl->location_number,
                'location_name'   => $this->resolveLocationName($dtl->location_number),
            ],
        ]);
    });
}
 
    // ══════════════════════════════════════════════
    // STORE SHEET — non-auto, max 7 baris per sheet
    // ══════════════════════════════════════════════
    public function storeSheet(Request $request)
{
    $mappingId = Crypt::decryptString($request->mapping_id);
    $access    = $this->checkAccess($mappingId);
    if (!$access['ok']) {
        return response()->json(['status'=>0,'title'=>'Ditolak','message'=>[$access['message']],'alert'=>'error']);
    }

    $m      = $access['mapping'];
    $userId = Auth::id();
    $lines  = $request->lines ?? [];
    $isAuto = $this->isAutoNumber($m->target_ref);
    $dbRole = $this->dbRole($access['role']); // accounting → counter1

    $filled = array_filter($lines, fn($l) => !empty($l['article'] ?? $l['article_desc'] ?? '') && isset($l['qty']) && (float)str_replace(',','',$l['qty']) > 0);
    if (count($filled) === 0) {
        return response()->json(['status'=>0,'title'=>'Warning','message'=>['Minimal 1 baris harus diisi beserta QTY-nya.'],'alert'=>'warning']);
    }
    if (count($filled) > 7) {
        return response()->json(['status'=>0,'title'=>'Warning','message'=>['Maksimal 7 baris per sheet.'],'alert'=>'warning']);
    }

    $selectedNo = null;
    if (!$isAuto) {
        $selectedNo = (int) $request->selected_number;
        if (!$selectedNo) {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['Pilih Nomor STO terlebih dahulu.'],'alert'=>'warning']);
        }
    }

    $articlesSeen = [];
    foreach ($filled as $l) {
        $key = !empty($l['article']) ? $l['article'] : ('MANUAL::'.strtoupper(trim($l['article_desc'] ?? '')));
        if (in_array($key, $articlesSeen)) {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['Ada artikel duplikat dalam sheet ini.'],'alert'=>'warning']);
        }
        $articlesSeen[] = $key;
    }

    foreach ($filled as $l) {
        $isManualLine = filter_var($l['is_manual'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $articleLine  = $isManualLine ? null : ($l['article'] ?? null);
        $descLine     = $l['article_desc'] ?? '';
        $lineLocationNumber = $m->target_type === 'LOCATION' ? $m->target_ref : ($l['location_number'] ?? null);

        if ($m->target_type !== 'LOCATION' && !$lineLocationNumber) {
            $label = $articleLine ?: $descLine;
            return response()->json(['status'=>0,'title'=>'Warning','message'=>["Lokasi wajib dipilih untuk artikel: {$label}"],'alert'=>'warning']);
        }

        if ($this->isDuplicateArticle($m, $mappingId, null, $articleLine, $isManualLine, $descLine, $lineLocationNumber)) {
            $label = $articleLine ?: $descLine;
            return response()->json(['status'=>0,'title'=>'Warning','message'=>[$this->duplicateArticleMessage($m, $label)],'alert'=>'warning']);
        }
    }

    return DB::transaction(function () use ($mappingId, $m, $access, $userId, $filled, $isAuto, $selectedNo, $dbRole) {
        $now = date('Y-m-d H:i:s');

        if ($isAuto) {
            $stoNumber = $this->generateStoNumber($mappingId);
        } else {
            $mLock = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->lockForUpdate()->first();
            $noDari   = $mLock->no_dari   ?? 1;
            $noSampai = $mLock->no_sampai ?? 9999;

            if ($selectedNo < $noDari || $selectedNo > $noSampai) {
                return response()->json(['status'=>0,'title'=>'Warning','message'=>["Nomor harus antara {$noDari}-{$noSampai}."],'alert'=>'warning']);
            }

            $config  = DB::table('sto_config')->where('config_id', $m->config_id)->first();
            $periode = str_replace('-', '/', substr($config->periode, 0, 7));
            $stoNumber = $periode . '/' . str_pad($selectedNo, 4, '0', STR_PAD_LEFT);

            $exists = DB::table('sto_hdr')->where('mapping_id', $mappingId)->where('sto_number', $stoNumber)->exists();
            if ($exists) {
                return response()->json(['status'=>0,'title'=>'Warning','message'=>['Nomor ini baru saja dipakai user lain, silakan pilih nomor lain.'],'alert'=>'warning']);
            }
        }

        $stoId = DB::table('sto_hdr')->insertGetId([
            'sto_number'  => $stoNumber,
            'mapping_id'  => $mappingId,
            'config_id'   => $m->config_id,
            'target_type' => $m->target_type,
            'target_ref'  => $m->target_ref,
            'status'      => 1,
            'created_by'  => Auth::user()->username,
            'created_at'  => $now,
            'updated_by'  => Auth::user()->username,
            'updated_at'  => $now,
        ], 'sto_id');

        $savedLines = [];

        foreach ($filled as $l) {
            $isManual = filter_var($l['is_manual'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $article  = $isManual ? null : ($l['article'] ?? null);
            $qty      = (float) str_replace(',', '', $l['qty']);
            $locationNumber = $m->target_type === 'LOCATION' ? $m->target_ref : ($l['location_number'] ?? null);

            $dtlId = DB::table('sto_dtl')->insertGetId([
                'sto_id'          => $stoId,
                'article_code'    => $article,
                'article_desc'    => $l['article_desc'] ?? null,
                'is_manual'       => $isManual,
                'uom'             => $l['uom'] ?? null,
                'min_package'     => $l['min_package'] ?: null,
                'location_number' => $locationNumber,
                "qty_{$dbRole}"   => $qty,
                "{$dbRole}_user"  => $userId,
                "{$dbRole}_at"    => $now,
                'note'            => $l['note'] ?? null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ], 'dtl_id');

           $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
$this->syncArticleStatus($m, $dtl);
$dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
            $myQty = $dtl->{"qty_{$dbRole}"};

            $savedLines[] = [
                'dtl_id'         => $dtl->dtl_id,
                'sto_number'     => $stoNumber,
                'article_code'   => $dtl->article_code ?? 'OTHER',
                'article_desc'   => $dtl->article_desc,
                'uom'            => $dtl->uom,
                'min_package'    => $dtl->min_package,
                'my_qty'         => $myQty !== null ? number_format((float)$myQty, 2) : null,
                'qty_counter1'   => $dtl->qty_counter1,
                'qty_counter2'   => $dtl->qty_counter2,
                'qty_counter3'   => $dtl->qty_counter3,
                'count_status'   => $dtl->count_status,
                'note'           => $dtl->note,
                'location_number'=> $dtl->location_number,
                'location_name'  => $this->resolveLocationName($dtl->location_number),
            ];
        }

        $this->recalcMappingProgress($mappingId);
        $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->value('target_act_loc');

        return response()->json([
            'status'         => 1,
            'title'          => 'Berhasil',
            'message'        => "Sheet $stoNumber berhasil disimpan.",
            'alert'          => 'success',
            'sto_number'     => $stoNumber,
            'target_act_loc' => (float) $freshTargetAct,
            'lines'          => $savedLines,
        ]);
    });
}
 
    // ══════════════════════════════════════════════
    // resolveStatus, compareToSystem, getLastQty
    // ══════════════════════════════════════════════
    private function resolveStatus($dtl, $mapping)
{
    if ($dtl->is_manual || is_null($dtl->article_code)) {
        return ['MATCH', null, 0];
    }

    // SUPPLIER/CUSTOMER tidak punya konsep family — qty_system tetap dari
    // lokasi yang dipilih di baris itu sendiri (dtl->location_number),
    // dan status dihitung HANYA dari qty_counter1/2/3 baris ini (perilaku asli).
    if ($mapping->target_type !== 'LOCATION') {
        $qtySystem = $this->getLastQty($dtl->article_code, $dtl->location_number, $mapping->sto_date);
        return $this->resolveSingleRowStatus($dtl, $mapping, $qtySystem);
    }

    return $this->resolveFamilyArticleStatus($mapping, $dtl->article_code, false, null);
}

// Perilaku resolveStatus() ASLI (sebelum family) — cuma lihat qty_counter1/2/3
// di SATU baris ini, tidak gabung baris lain. Dipakai utk SUPPLIER/CUSTOMER.
private function resolveSingleRowStatus($dtl, $mapping, $qtySystem)
{
    if (!($mapping->is_blind ?? true)) {
        $qty = $dtl->qty_counter1 ?? $dtl->qty_counter2 ?? ($dtl->qty_counter3 ?? null);
        if (is_null($qty)) return ['INCOMPLETE', $qtySystem, null];
        $variance = round((float) $qty - (float) $qtySystem, 2);
        return [$variance == 0 ? 'MATCH' : 'RECOUNT', $qtySystem, $variance];
    }

    $activeQty = [];
    if (!empty($mapping->counter1_user)) $activeQty[] = $dtl->qty_counter1;
    if (!empty($mapping->counter2_user)) $activeQty[] = $dtl->qty_counter2;
    if (!empty($mapping->counter3_user)) $activeQty[] = $dtl->qty_counter3 ?? null;

    foreach ($activeQty as $q) {
        if (is_null($q)) return ['INCOMPLETE', $qtySystem, null];
    }

    $unique = array_unique(array_map(fn($q) => round((float) $q, 2), $activeQty));
    if (count($unique) > 1) return ['NOT MATCH', $qtySystem, null];

    $variance = round((float) $activeQty[0] - (float) $qtySystem, 2);
    return [$variance == 0 ? 'MATCH' : 'RECOUNT', $qtySystem, $variance];
}

// hitung qty_system tanpa membandingkan qty counter
private function resolveQtySystem($dtl, $mapping)
{
    if ($dtl->is_manual || is_null($dtl->article_code)) return null;
    if (empty($dtl->location_number)) return 0;
    return (float) $this->getLastQty($dtl->article_code, $dtl->location_number, $mapping->sto_date);
}

// ══════════════════════════════════════════════
// LOCATION FAMILY — parent + semua sibling child
// ══════════════════════════════════════════════
private $locationFamilyCache = [];

private function resolveLocationFamily($locationCode)
{
    if (isset($this->locationFamilyCache[$locationCode])) {
        return $this->locationFamilyCache[$locationCode];
    }

    $loc = DB::table('stock_location_master')->where('location_code', $locationCode)->first();
    if (!$loc) {
        return $this->locationFamilyCache[$locationCode] = [$locationCode];
    }

    if (!empty($loc->parent_location)) {
        $siblings = DB::table('stock_location_master')
            ->where('parent_location', $loc->parent_location)
            ->pluck('location_code')->toArray();
        $family = array_values(array_unique(array_merge([$loc->parent_location], $siblings)));
        return $this->locationFamilyCache[$locationCode] = $family;
    }

    $children = DB::table('stock_location_master')
        ->where('parent_location', $locationCode)
        ->pluck('location_code')->toArray();
    if (!empty($children)) {
        $family = array_values(array_unique(array_merge([$locationCode], $children)));
        return $this->locationFamilyCache[$locationCode] = $family;
    }

    return $this->locationFamilyCache[$locationCode] = [$locationCode];
}

// mapping-mapping (sto_config_mapping) lain di config yang sama, yang target_ref-nya
// satu keluarga dengan $mapping ini. Kalau standalone, hasilnya cuma dirinya sendiri.
private function resolveFamilyMappings($mapping)
{
    if ($mapping->target_type !== 'LOCATION') return collect([$mapping]);

    $family = $this->resolveLocationFamily($mapping->target_ref);
    if (count($family) <= 1) return collect([$mapping]);

    return DB::table('sto_config_mapping')
        ->where('config_id', $mapping->config_id)
        ->where('target_type', 'LOCATION')
        ->whereIn('target_ref', $family)
        ->get();
}

// gabungkan sto_dtl artikel yang sama, dari SEMUA sibling mapping dalam keluarga
private function collectFamilyDtlRows($mapping, $articleCode, $isManual, $articleDesc)
{
    $siblingMappingIds = $this->resolveFamilyMappings($mapping)->pluck('mapping_id');
    if ($siblingMappingIds->isEmpty()) return collect();

    $stoIds = DB::table('sto_hdr')->whereIn('mapping_id', $siblingMappingIds)->pluck('sto_id');
    if ($stoIds->isEmpty()) return collect();

    $query = DB::table('sto_dtl')->whereIn('sto_id', $stoIds);

    if ($isManual) {
        $query->whereNull('article_code')
              ->whereRaw('UPPER(article_desc) = ?', [strtoupper(trim($articleDesc ?? ''))]);
    } else {
        $query->where('article_code', $articleCode);
    }

    return $query->select('dtl_id', 'qty_counter1', 'qty_counter2', 'qty_counter3')->get();
}

private function resolveTolerancePercent($targetPlanLoc)
{
    $target = (float) ($targetPlanLoc ?? 0);
    if ($target <= 0 || $target >= 100) return 0;
    return round(100 - $target, 2);
}
 
    private function compareToSystem($qty, $dtl, $mapping, $qtySystem = null)
{
    if ($dtl->is_manual || is_null($dtl->article_code)) return ['MATCH', null, 0];

    if ($qtySystem === null) {
        $qtySystem = $this->resolveQtySystem($dtl, $mapping);
    }

    $variance = round((float) $qty - (float) $qtySystem, 2);
    return [($variance == 0 ? 'MATCH' : 'RECOUNT'), $qtySystem, $variance];
}
 
    private function getLastQty($article, $location, $stoDate)
{
    $realCode = DB::table('article')
        ->where('article_alternative_code', $article)
        ->value('article_code');
    if (!$realCode) return 0;

    $target = $stoDate
        ? \DateTime::createFromFormat('d-m-Y', $stoDate)
        : new \DateTime();
    if (!$target) return 0;
    $target->modify('-1 day');

    $family = $this->resolveLocationFamily($location);

    // standalone (tidak ada parent/child) → tetap pakai function lama,
    // supaya perilaku persis sama seperti sebelumnya, zero risk regresi.
    if (count($family) <= 1) {
        $row = DB::selectOne(
            "SELECT get_last_qty_new(?, ?, 'HO', ?) AS q",
            [$realCode, $target->format('Y-m-d'), $location]
        );
        return $row ? (float) $row->q : 0;
    }

    // punya keluarga (parent+child) → pakai function grouped
    $pgArray = '{' . implode(',', array_map(function ($c) {
        return '"' . str_replace('"', '\\"', $c) . '"';
    }, $family)) . '}';

    $row = DB::selectOne(
        "SELECT get_last_qty_new_grouped(?, ?, 'HO', ?::varchar[]) AS q",
        [$realCode, $target->format('Y-m-d'), $pgArray]
    );
    return $row ? (float) $row->q : 0;
}
 
    // ══════════════════════════════════════════════
    // DELETE LINE
    // ══════════════════════════════════════════════
    public function deleteLine(Request $request, $dtlId)
{
    $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
    if (!$dtl) return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Baris tidak ditemukan.'],'alert'=>'error']);

    $userId       = Auth::id();
    $isAccounting = $this->isAccountingUser();

    $role = $this->resolveCounterRole($dtl, $userId);
    if (!$role && !$isAccounting) {
        return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Anda tidak berwenang menghapus baris ini.'],'alert'=>'error']);
    }

    $now    = date('Y-m-d H:i:s');
    $stoHdr = DB::table('sto_hdr')->where('sto_id', $dtl->sto_id)->first();
    $m      = $stoHdr ? DB::table('sto_config_mapping')->where('mapping_id', $stoHdr->mapping_id)->first() : null; // ← tambah

    // Leo: paksa hapus SATU BARIS PENUH — semua qty 3 counter ikut hilang
    $forceWholeDelete = $isAccounting;

    $otherFilled = false;
    if (!$forceWholeDelete) {
        foreach (['counter1', 'counter2', 'counter3'] as $r) {
            if ($r === $role) continue;
            if (!is_null($dtl->{"qty_{$r}"} ?? null)) { $otherFilled = true; break; }
        }
    }

    if ($forceWholeDelete || !$otherFilled) {
    $deletedArticleCode = $dtl->article_code;
    $deletedIsManual    = (bool) $dtl->is_manual;
    $deletedArticleDesc = $dtl->article_desc;

    DB::table('sto_dtl')->where('dtl_id', $dtlId)->delete();

        $remainingDtl = DB::table('sto_dtl')->where('sto_id', $dtl->sto_id)->count();
       if ($remainingDtl === 0 && $stoHdr) {
    DB::table('sto_hdr')->where('sto_id', $dtl->sto_id)->delete();

    // Hanya decrement no_current kalau nomor yang dihapus ini
    // memang nomor TERAKHIR yang pernah digenerate untuk mapping ini.
    // Kalau bukan, jangan disentuh — mencegah nomor di depan (lebih besar)
    // ke-generate ulang dan tabrakan dengan nomor yang masih hidup.
    DB::transaction(function () use ($stoHdr) {
        $mapping = DB::table('sto_config_mapping')
            ->where('mapping_id', $stoHdr->mapping_id)
            ->lockForUpdate()
            ->first();

        // ambil bagian angka terakhir dari sto_number, mis. "2026/07/2022" -> 2022
        $parts     = explode('/', $stoHdr->sto_number);
        $deletedNo = (int) end($parts);

        // hanya decrement kalau ini betul2 nomor terakhir yg pernah digenerate
        // DAN tidak ada sto_hdr lain di mapping ini yang nomornya >= ini
        $hasHigherOrEqual = DB::table('sto_hdr')
            ->where('mapping_id', $stoHdr->mapping_id)
            ->exists(); // sisa sto_hdr lain (kalau ada) — cek manual di bawah lebih akurat

        if ($deletedNo == (int) $mapping->no_current) {
            DB::table('sto_config_mapping')
                ->where('mapping_id', $stoHdr->mapping_id)
                ->update(['no_current' => $deletedNo - 1, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    });
}

       
    $freshTargetAct = null;
    if ($stoHdr) {
        // sync sibling family utk artikel yg baru dihapus (kalau masih ada baris lain milik artikel ini di keluarga)
        if ($m && $m->target_type === 'LOCATION' && !$deletedIsManual && $deletedArticleCode) {
            $remainingFamilyRows = $this->collectFamilyDtlRows($m, $deletedArticleCode, false, null);
            if ($remainingFamilyRows->isNotEmpty()) {
                [$status, $qtySystem, $variance] = $this->resolveFamilyArticleStatus($m, $deletedArticleCode, false, null);
                DB::table('sto_dtl')->whereIn('dtl_id', $remainingFamilyRows->pluck('dtl_id'))->update([
                    'count_status' => $status, 'qty_system' => $qtySystem, 'qty_variance' => $variance, 'updated_at' => $now,
                ]);
            }
        }
        $this->recalcMappingProgress($stoHdr->mapping_id);
        $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $stoHdr->mapping_id)->value('target_act_loc');
    }

        return response()->json([
            'status'         => 1,
            'title'          => 'Berhasil',
            'message'        => $isAccounting ? 'Baris dihapus (semua qty counter ikut terhapus).' : 'Baris dihapus.',
            'alert'          => 'success',
            'whole_deleted'  => true,
            'sto_number'     => $stoHdr->sto_number ?? null,
            'target_act_loc' => $freshTargetAct !== null ? (float)$freshTargetAct : null,
        ]);
    }

    $field     = "qty_{$role}";
    $userField = "{$role}_user";
    $atField   = "{$role}_at";

   DB::table('sto_dtl')->where('dtl_id', $dtlId)->update([
    $field => null, $userField => null, $atField => null,
    'count_status' => 'INCOMPLETE', 'qty_system' => null, 'qty_variance' => null,
    'updated_at'   => $now,
]);

if ($m) {
    $dtlFresh = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
    $this->syncArticleStatus($m, $dtlFresh);
}

if ($stoHdr) {
    $this->recalcMappingProgress($stoHdr->mapping_id);
}
$freshTargetAct = $stoHdr ? DB::table('sto_config_mapping')->where('mapping_id', $stoHdr->mapping_id)->value('target_act_loc') : null;
$dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();

    return response()->json([
        'status'         => 1,
        'title'          => 'Berhasil',
        'message'        => 'Input Anda direset. Qty counter lain tetap tersimpan.',
        'alert'          => 'success',
        'whole_deleted'  => false,
        'target_act_loc' => $freshTargetAct !== null ? (float)$freshTargetAct : null,
        'row' => [
            'dtl_id'         => $dtl->dtl_id,
            'sto_number'     => $stoHdr->sto_number ?? null,
            'article_code'   => $dtl->article_code ?? 'OTHER',
            'article_desc'   => $dtl->article_desc,
            'uom'            => $dtl->uom,
            'min_package'    => $dtl->min_package,
            'my_qty'         => null,
            'count_status'   => $dtl->count_status,
            'note'           => $dtl->note,
            'location_number'=> $dtl->location_number,
            'location_name'  => $this->resolveLocationName($dtl->location_number),
        ],
    ]);
}
 
    // ══════════════════════════════════════════════
    // RECALC PROGRESS
    // ══════════════════════════════════════════════
   public function recalcMappingProgress($mappingId)
{
    $m = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->first();
    if (!$m) return;

    if ($m->target_type === 'LOCATION') {
        $family = $this->resolveLocationFamily($m->target_ref);
        if (count($family) > 1) {
            $this->recalcFamilyProgress($m->config_id, $family);
            return;
        }
    }

    $this->recalcSingleMappingProgress($m);
}

// ── perilaku LAMA, PERSIS tidak berubah — dipakai lokasi standalone & SUPPLIER/CUSTOMER ──
private function recalcSingleMappingProgress($m)
{
    $tolerance = $this->resolveTolerancePercent($m->target_plan_loc);
    $stoIds = DB::table('sto_hdr')->where('mapping_id', $m->mapping_id)->pluck('sto_id');

    $dtlRows = DB::table('sto_dtl as d')
        ->whereIn('d.sto_id', $stoIds)
        ->select('d.dtl_id', 'd.article_code', 'd.location_number', 'd.is_manual',
                 'd.qty_counter1', 'd.qty_counter2', 'd.qty_counter3')
        ->get();

    $grouped = $dtlRows->groupBy(function ($row) {
        $key = $row->article_code ? strtoupper($row->article_code) : ('MANUAL-'.$row->dtl_id);
        return $row->location_number.'|'.$key;
    });

    $total = 0; $accurate = 0;
    foreach ($grouped as $items) {
        $total++;
        if ($this->isGroupAccurate($items, $m, $tolerance)) $accurate++;
    }

    if ($m->target_type === 'LOCATION') {
        $countedCodes = $grouped->keys()
            ->map(fn($k) => explode('|', $k, 2)[1] ?? $k)
            ->reject(fn($k) => str_starts_with($k, 'MANUAL-'))
            ->map(fn($k) => strtoupper($k))->unique()->all();

        $periode = DB::table('sto_config')->where('config_id', $m->config_id)->value('periode');
        $periode = $periode ? substr($periode, 0, 7) : null;

        $phantoms = $this->buildPhantomArticlesForLocation($m, $countedCodes, $periode);
        foreach ($phantoms as $phantom) {
            $phantomQtySystem = (float) $this->getLastQty($phantom->article_code, $phantom->location_number, $m->sto_date);
            $total++;
            if ($phantomQtySystem == 0) $accurate++;
        }
    }

    $actLoc = $total > 0 ? round(($accurate / $total) * 100, 2) : 0;
    DB::table('sto_config_mapping')->where('mapping_id', $m->mapping_id)
        ->update(['target_act_loc' => $actLoc, 'updated_at' => date('Y-m-d H:i:s')]);

    $this->recalcConfigTargetAct($m->config_id);
}

// ── BARU: gabung seluruh sibling dalam satu keluarga jadi satu unit skor ──
private function recalcFamilyProgress($configId, array $family)
{
    $siblingMappings = DB::table('sto_config_mapping')
        ->where('config_id', $configId)
        ->where('target_type', 'LOCATION')
        ->whereIn('target_ref', $family)
        ->get();
    if ($siblingMappings->isEmpty()) return;

    $repMapping = $siblingMappings->first();
    $tolerance  = $this->resolveTolerancePercent($repMapping->target_plan_loc);
    $mappingIds = $siblingMappings->pluck('mapping_id');

    $stoIds = DB::table('sto_hdr')->whereIn('mapping_id', $mappingIds)->pluck('sto_id');

    $dtlRows = DB::table('sto_dtl as d')
        ->whereIn('d.sto_id', $stoIds)
        ->select('d.dtl_id', 'd.article_code', 'd.is_manual',
                 'd.qty_counter1', 'd.qty_counter2', 'd.qty_counter3')
        ->get();

    // ── group per ARTIKEL SAJA — keluarga dianggap satu unit lokasi ──
    $grouped = $dtlRows->groupBy(function ($row) {
        return $row->article_code ? strtoupper($row->article_code) : ('MANUAL-'.$row->dtl_id);
    });

    $total = 0; $accurate = 0;
    foreach ($grouped as $items) {
        $total++;
        if ($this->isFamilyGroupAccurate($items, $repMapping, $siblingMappings, $tolerance)) $accurate++;
    }

    $countedCodes = $grouped->keys()
        ->reject(fn($k) => str_starts_with($k, 'MANUAL-'))
        ->map(fn($k) => strtoupper($k))->unique()->all();

    $periode = DB::table('sto_config')->where('config_id', $configId)->value('periode');
    $periode = $periode ? substr($periode, 0, 7) : null;

    $phantoms = $this->buildPhantomArticlesForFamily($repMapping, $family, $countedCodes, $periode);
    foreach ($phantoms as $phantom) {
        $phantomQtySystem = (float) $this->getLastQty($phantom->article_code, $repMapping->target_ref, $repMapping->sto_date);
        $total++;
        if ($phantomQtySystem == 0) $accurate++;
    }

    $actLoc = $total > 0 ? round(($accurate / $total) * 100, 2) : 0;

    // ── skor yang sama ditulis ke SEMUA sibling mapping dalam keluarga ini ──
    DB::table('sto_config_mapping')->whereIn('mapping_id', $mappingIds)
        ->update(['target_act_loc' => $actLoc, 'updated_at' => date('Y-m-d H:i:s')]);

    $this->recalcConfigTargetAct($configId);
}

private function isFamilyGroupAccurate($items, $repMapping, $siblingMappings, $tolerance)
{
    $first = $items->first();
    if (empty($first->article_code)) return true;

    $qtySystem = (float) $this->getLastQty($first->article_code, $repMapping->target_ref, $repMapping->sto_date);

    if (!($repMapping->is_blind ?? true)) {
        $sum = $items->sum(fn($r) => (float) ($r->qty_counter1 ?? $r->qty_counter2 ?? $r->qty_counter3 ?? 0));
        $anyFilled = $items->contains(fn($r) => $r->qty_counter1 !== null || $r->qty_counter2 !== null || $r->qty_counter3 !== null);
        if (!$anyFilled) return false;
        return $this->withinTolerance($sum, $qtySystem, $tolerance);
    }

    $activeSlots = [];
    foreach (['1', '2', '3'] as $n) {
        if ($siblingMappings->contains(fn($sm) => !empty($sm->{"counter{$n}_user"}))) $activeSlots[] = $n;
    }
    if (empty($activeSlots)) $activeSlots = ['1', '2', '3'];

    $totals = [];
    foreach ($activeSlots as $n) {
        $field  = "qty_counter{$n}";
        $hasAny = $items->contains(fn($r) => $r->{$field} !== null);
        if (!$hasAny) return false;
        $totals[$n] = (float) $items->sum($field);
    }

    $unique = array_unique(array_map(fn($v) => round($v, 2), $totals));
    if (count($unique) > 1) return false;

    $counted = array_values($totals)[0];
    return $this->withinTolerance($counted, $qtySystem, $tolerance);
}

private function buildPhantomArticlesForFamily($repMapping, array $family, array $countedCodes, $periode)
{
    $movementQuery = DB::table('warehouse_movement as wm')
        ->join('article as a', 'a.article_code', '=', 'wm.artikel_code')
        ->whereIn('wm.location_number', $family)
        ->where('wm.movement_type', 'not ilike', 'CANCEL %')
        ->select('a.article_alternative_code as article_code')
        ->distinct();

    if ($periode) {
        $movementQuery->whereRaw("TO_CHAR(TO_DATE(wm.movement_date,'DD-MM-YYYY'), 'YYYY-MM') = ?", [$periode]);
    }

    $phantoms = collect();
    foreach ($movementQuery->get() as $sa) {
        if (!$sa->article_code) continue;
        if (in_array(strtoupper($sa->article_code), $countedCodes)) continue;
        $phantoms->push((object) ['article_code' => $sa->article_code, 'location_number' => $repMapping->target_ref]);
    }
    return $phantoms;
}

private function recalcConfigTargetAct($configId)
{
    $actGlobal = DB::table('sto_config_mapping')->where('config_id', $configId)->avg('target_act_loc');
    DB::table('sto_config')->where('config_id', $configId)
        ->update(['target_act' => round($actGlobal ?? 0, 2), 'updated_at' => date('Y-m-d H:i:s')]);
}

// ══════════════════════════════════════════════
// REFRESH QTY_SYSTEM — dipanggil terpisah dari recalcMappingProgress,
// karena ini MENGUBAH data transaksional (sto_dtl), bukan cuma skor.
// Setiap perubahan dicatat ke sto_qty_system_recalc_logs (before/after).
// ══════════════════════════════════════════════
public function refreshQtySystemForMapping($mappingId, $recalculatedBy = 'system', $source = 'manual', $includeFinished = false)
{
    $m = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->first();
    if (!$m) return ['checked' => 0, 'changed' => 0];

    $query = DB::table('sto_dtl as d')
        ->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id')
        ->where('h.mapping_id', $mappingId)
        ->whereNotNull('d.article_code') // manual tidak punya pembanding stock system
        ->select('d.*', 'h.status as hdr_status');

    // Default: JANGAN sentuh STO yang sudah FINISHED (status=2),
    // supaya rekap yang sudah ditutup tidak diam-diam berubah.
    // Pakai --include-finished kalau memang mau dipaksa.
    if (!$includeFinished) {
        $query->where('h.status', 1);
    }

    $dtlRows = $query->get();

    $checked = 0;
    $changed = 0;
    $now = date('Y-m-d H:i:s');

    DB::transaction(function () use ($dtlRows, $m, $mappingId, $recalculatedBy, $source, $now, &$checked, &$changed) {
        foreach ($dtlRows as $dtl) {
            $checked++;

            // resolveStatus() sudah menghitung ulang qty_system dari getLastQty()
            // secara fresh — kita cuma ambil hasilnya, belum ditulis ke DB.
            [$newStatus, $newQtySystem, $newVariance] = $this->resolveStatus($dtl, $m);

            $oldQtySystem = $dtl->qty_system;
            $isDifferent = round((float) $oldQtySystem, 2) !== round((float) $newQtySystem, 2);

            if (!$isDifferent) continue; // tidak ada perubahan, tidak perlu log/update

            $changed++;

            DB::table('sto_qty_system_recalc_logs')->insert([
                'dtl_id'           => $dtl->dtl_id,
                'mapping_id'       => $mappingId,
                'sto_id'           => $dtl->sto_id,
                'article_code'     => $dtl->article_code,
                'location_number'  => $dtl->location_number,
                'old_qty_system'   => $oldQtySystem,
                'new_qty_system'   => $newQtySystem,
                'old_qty_variance' => $dtl->qty_variance,
                'new_qty_variance' => $newVariance,
                'old_count_status' => $dtl->count_status,
                'new_count_status' => $newStatus,
                'recalculated_by'  => $recalculatedBy,
                'source'           => $source,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            DB::table('sto_dtl')->where('dtl_id', $dtl->dtl_id)->update([
                'qty_system'   => $newQtySystem,
                'qty_variance' => $newVariance,
                'count_status' => $newStatus,
                'updated_at'   => $now,
            ]);
        }
    });

    return ['checked' => $checked, 'changed' => $changed];
}

// Tentukan apakah satu grup artikel (gabungan lintas sto_number) AKURAT,
// dengan toleransi. Qty di-SUM dulu per slot counter, baru dibandingkan —
// bukan dinilai per baris/sto_number satu-satu.
private function isGroupAccurate($items, $mapping, $tolerance)
{
    $first = $items->first();

    // artikel manual tidak dibandingkan ke stock system → selama sudah
    // diinput, dianggap akurat (konsisten dengan compareToSystem() lama)
    if (empty($first->article_code)) {
        return true;
    }

    $qtySystem = (float) $this->getLastQty($first->article_code, $first->location_number, $mapping->sto_date);

    if (!($mapping->is_blind ?? true)) {
        $sum = $items->sum(fn($r) => (float) ($r->qty_counter1 ?? $r->qty_counter2 ?? $r->qty_counter3 ?? 0));
        $anyFilled = $items->contains(fn($r) => $r->qty_counter1 !== null || $r->qty_counter2 !== null || $r->qty_counter3 !== null);
        if (!$anyFilled) return false; // INCOMPLETE

        return $this->withinTolerance($sum, $qtySystem, $tolerance);
    }

    $activeSlots = [];
    foreach (['1', '2', '3'] as $n) {
        $userField = "counter{$n}_user";
        if (!empty($mapping->{$userField})) $activeSlots[] = $n;
    }
    if (empty($activeSlots)) $activeSlots = ['1', '2', '3'];

    $totals = [];
    foreach ($activeSlots as $n) {
        $field  = "qty_counter{$n}";
        $hasAny = $items->contains(fn($r) => $r->{$field} !== null);
        if (!$hasAny) return false; // INCOMPLETE
        $totals[$n] = (float) $items->sum($field);
    }

    $unique = array_unique(array_map(fn($v) => round($v, 2), $totals));
    if (count($unique) > 1) return false; // NOT MATCH — antar counter tidak sepakat

    $counted = array_values($totals)[0];
    return $this->withinTolerance($counted, $qtySystem, $tolerance);
}

private function withinTolerance($counted, $qtySystem, $tolerance)
{
    $variance = round($counted - $qtySystem, 2);
    if ($variance == 0) return true; // MATCH persis

    if ($tolerance <= 0) return false;
    if ($qtySystem == 0) return false; // hindari div-by-zero; variance dari basis 0 dianggap gagal

    $percent = abs($variance) / abs($qtySystem) * 100;
    return $percent <= $tolerance;
}
 
    // ══════════════════════════════════════════════
    // FINISH
    // ══════════════════════════════════════════════
    public function finish(Request $request)
    {
        $mappingId = Crypt::decryptString($request->mapping_id);
        $access    = $this->checkAccess($mappingId);
        if (!$access['ok']) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>[$access['message']],'alert'=>'error']);
        }
 
        $m      = $access['mapping'];
        $stoIds = DB::table('sto_hdr')->where('mapping_id', $mappingId)->pluck('sto_id');
 
        if ($stoIds->isEmpty()) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Belum ada baris yang diinput.'],'alert'=>'warning']);
        }
 
        $pending = DB::table('sto_dtl')->whereIn('sto_id', $stoIds)
            ->whereIn('count_status', ['INCOMPLETE', 'NOT MATCH'])->count();
        if ($pending > 0 && $access['role'] !== 'accounting') {
            return response()->json(['status'=>0,'title'=>'Belum Bisa Selesai','message'=>["Masih ada $pending baris berstatus INCOMPLETE/NOT MATCH."],'alert'=>'warning']);
        }
 
        $now = date('Y-m-d H:i:s');
        DB::table('sto_hdr')->whereIn('sto_id', $stoIds)->update(['status' => 2, 'updated_at' => $now]);
        DB::table('sto_config_mapping')->where('mapping_id', $mappingId)
            ->update(['finish_time' => $now, 'updated_at' => $now]);
 
        $maxFinish  = DB::table('sto_config_mapping')->where('config_id', $m->config_id)->whereNotNull('finish_time')->max('finish_time');
        $unfinished = DB::table('sto_config_mapping')->where('config_id', $m->config_id)->whereNull('finish_time')->count();
 
        $update = ['finish_time' => $maxFinish, 'updated_at' => $now];
        if ($unfinished == 0) $update['status'] = 3;
 
        DB::table('sto_config')->where('config_id', $m->config_id)->whereIn('status', [1, 2])->update($update);
 
        return response()->json(['status'=>1,'title'=>'Berhasil','message'=>'Target ditandai selesai.','alert'=>'success','redirect_url'=>route('stockCount.index')]);
    }

   

// ══════════════════════════════════════════════
// BASE QUERY MENTAH (dipakai auditList & auditListDetail)
// ══════════════════════════════════════════════
private function applyAuditFilters($query, Request $request)
{
    if ($request->filled('searchStoCode'))     $query->where('c.sto_code', $request->searchStoCode);
    if ($request->filled('searchPeriode'))     $query->where('c.periode', $request->searchPeriode);
    if ($request->filled('searchTarget'))      $query->where('m.target_ref', $request->searchTarget);
    if ($request->filled('searchArticleCode')) $query->where('d.article_code', 'ilike', '%'.$request->searchArticleCode.'%');
    if ($request->filled('searchStoNumber'))   $query->where('h.sto_number', 'ilike', '%'.$request->searchStoNumber.'%');

    if ($request->filled('searchDate')) {
        $parts = explode(' to ', $request->searchDate);
        $from  = trim($parts[0] ?? '');
        $to    = trim($parts[1] ?? $from);
        if ($from && $to) {
            $query->whereRaw(
                "TO_DATE(m.sto_date,'DD-MM-YYYY') BETWEEN TO_DATE(?,'DD-MM-YYYY') AND TO_DATE(?,'DD-MM-YYYY')",
                [$from, $to]
            );
        }
    }
    // NOTE: searchStatus TIDAK difilter di sini — status baru ada setelah
    // diakumulasi (lihat auditList()), jadi difilter belakangan di PHP.
}

private function buildAuditRawRows(Request $request)
{
    $query = DB::table('sto_dtl as d')
        ->join('sto_hdr as h', 'h.sto_id', '=', 'd.sto_id')
        ->join('sto_config_mapping as m', 'm.mapping_id', '=', 'h.mapping_id')
        ->join('sto_config as c', 'c.config_id', '=', 'm.config_id')
        ->leftJoin('stock_location_master as l', function ($j) {
            $j->on('l.location_code', '=', 'm.target_ref')->where('m.target_type', '=', 'LOCATION');
        })
        ->leftJoin('third_party as tp', function ($j) {
            $j->on('tp.kode', '=', 'm.target_ref')->whereIn('m.target_type', ['SUPPLIER', 'CUSTOMER']);
        })
        ->leftJoin('users as u1', 'u1.id', '=', 'd.counter1_user')
        ->leftJoin('users as u2', 'u2.id', '=', 'd.counter2_user')
        ->leftJoin('users as u3', 'u3.id', '=', 'd.counter3_user')
        ->select([
            'd.dtl_id', 'm.target_type', 'm.target_ref',
            'm.is_blind', 'm.sto_date',                 // ← tambahkan m.sto_date
            'm.counter1_user as map_counter1_user',
            'm.counter2_user as map_counter2_user',
            'm.counter3_user as map_counter3_user',
            DB::raw("COALESCE(l.location_name, tp.nama, m.target_ref) as target_name"),
            'c.sto_code', 'c.config_id', 'c.periode',
            'd.article_code', 'd.article_desc', 'd.min_package', 'd.uom',
            'd.location_number',
            'd.qty_counter1', 'd.qty_counter2', 'd.qty_counter3',
            'h.sto_number',
            'u1.name as counter1_name', 'd.counter1_at',
            'u2.name as counter2_name', 'd.counter2_at',
            'u3.name as counter3_name', 'd.counter3_at',
        ]);

    $this->applyAuditFilters($query, $request);

    $rows = $query->get();
    $rows = $this->appendPhantomArticlesForFilters($rows, $request);

    return $rows;
}

// ══════════════════════════════════════════════
// PHANTOM ROWS — artikel ada stok di lokasi, belum ada input STO sama sekali
// ══════════════════════════════════════════════
// ══════════════════════════════════════════════
// PHANTOM ROWS — enumerasi SEMUA lokasi yang match filter aktif,
// lalu munculkan artikel yang PUNYA STOK tapi belum pernah diinput STO
// ══════════════════════════════════════════════
private function appendPhantomArticlesForFilters($rows, Request $request)
{
    $mapQuery = DB::table('sto_config_mapping as m')
        ->join('sto_config as c', 'c.config_id', '=', 'm.config_id')
        ->where('m.target_type', 'LOCATION')
        ->select('m.mapping_id', 'm.target_ref', 'm.is_blind', 'm.sto_date',
                 'm.counter1_user', 'm.counter2_user', 'm.counter3_user',
                 'c.periode');

    // ── Filter IDENTIK dengan applyAuditFilters(), supaya phantom hanya
    // dienumerasi untuk lokasi yang benar2 relevan dengan filter aktif. ──
    if ($request->filled('searchStoCode')) $mapQuery->where('c.sto_code', $request->searchStoCode);
    if ($request->filled('searchPeriode')) $mapQuery->where('c.periode', $request->searchPeriode);
    if ($request->filled('searchTarget'))  $mapQuery->where('m.target_ref', $request->searchTarget);

    if ($request->filled('searchDate')) {
        $parts = explode(' to ', $request->searchDate);
        $from  = trim($parts[0] ?? '');
        $to    = trim($parts[1] ?? $from);
        if ($from && $to) {
            $mapQuery->whereRaw(
                "TO_DATE(m.sto_date,'DD-MM-YYYY') BETWEEN TO_DATE(?,'DD-MM-YYYY') AND TO_DATE(?,'DD-MM-YYYY')",
                [$from, $to]
            );
        }
    }

    $locationMappings = $mapQuery->get()->unique('target_ref');
    if ($locationMappings->isEmpty()) return $rows;

    $countedByLocation = $rows->groupBy('location_number')
        ->map(fn($items) => $items->pluck('article_code')->filter()
              ->map(fn($c) => strtoupper($c))->unique()->all());

    $allPhantoms = collect();
    foreach ($locationMappings as $m) {
        $counted = $countedByLocation->get($m->target_ref, []);
        $periode = $m->periode ? substr($m->periode, 0, 7) : null;
        $allPhantoms = $allPhantoms->concat($this->buildPhantomArticlesForLocation($m, $counted, $periode));
    }

    return $rows->concat($allPhantoms);
}

private function resolveRealArticleCode($alternativeCode)
{
    if (!$alternativeCode) return null;
    return DB::table('article')
        ->where('article_alternative_code', $alternativeCode)
        ->value('article_code');
}

private function buildPhantomArticlesForLocation($m, array $countedCodes, $periode = null)
{
    $targetRef    = $m->target_ref;
    $locationName = $this->resolveLocationName($targetRef);

    $movementQuery = DB::table('warehouse_movement as wm')
        ->join('article as a', 'a.article_code', '=', 'wm.artikel_code')
        ->where('wm.location_number', $targetRef)
        ->where('wm.movement_type', 'not ilike', 'CANCEL %')
        ->select('a.article_alternative_code as article_code', 'a.article_desc', 'a.uom', 'a.min_package')
        ->distinct();

    if ($periode) {
        $movementQuery->whereRaw(
            "TO_CHAR(TO_DATE(wm.movement_date,'DD-MM-YYYY'), 'YYYY-MM') = ?",
            [$periode]
        );
    }

    $movedArticles = $movementQuery->get();

    $phantoms = collect();
    foreach ($movedArticles as $sa) {
        if (!$sa->article_code) continue;
        if (in_array(strtoupper($sa->article_code), $countedCodes)) continue;

        $phantoms->push((object) [
            'dtl_id'            => 'phantom-'.$targetRef.'-'.$sa->article_code,
            'target_type'       => 'LOCATION',
            'target_ref'        => $targetRef,
            'is_blind'          => $m->is_blind ?? true,
            'sto_date'          => $m->sto_date ?? null,   // ← tambahkan
            'map_counter1_user' => $m->counter1_user,
            'map_counter2_user' => $m->counter2_user,
            'map_counter3_user' => $m->counter3_user ?? null,
            'target_name'       => $locationName,
            'sto_code'          => null,
            'config_id'         => null,
            'periode'           => null,
            'article_code'      => $sa->article_code,
            'article_desc'      => $sa->article_desc,
            'min_package'       => $sa->min_package,
            'uom'               => $sa->uom,
            'location_number'   => $targetRef,
            'qty_counter1'      => 0,
            'qty_counter2'      => 0,
            'qty_counter3'      => 0,
            'sto_number'        => null,
            'counter1_name' => null, 'counter1_at' => null,
            'counter2_name' => null, 'counter2_at' => null,
            'counter3_name' => null, 'counter3_at' => null,
        ]);
    }

    return $phantoms;
}

// ══════════════════════════════════════════════
// STATUS UTK GRUP (dipakai auditList, setelah akumulasi per lokasi+artikel)
// ══════════════════════════════════════════════
private function isPhantomRow($row)
{
    return is_string($row->dtl_id) && str_starts_with($row->dtl_id, 'phantom-');
}

private function resolveGroupStatus($items)
{
    $first = $items->first();

    if (empty($first->article_code)) {
        return ['MATCH', null, null];
    }

    $qtySystem = isset($first->stock_qty)
        ? (float) $first->stock_qty
        : (float) $this->getLastQty($first->article_code, $first->location_number, $first->sto_date);

    $realItems = $items->filter(fn($r) => !$this->isPhantomRow($r));

    if ($realItems->isEmpty()) {
        // Phantom: belum pernah diinput STO.
        // Kalau stock system juga 0 → tidak ada yang perlu dihitung → MATCH.
        if ($qtySystem == 0) {
            return ['MATCH', $qtySystem, 0];
        }
        return ['INCOMPLETE', $qtySystem, null];
    }


    $isBlind = $first->is_blind ?? true;

    if (!$isBlind) {
        $sum = $realItems->sum(fn($r) => (float) ($r->qty_counter1 ?? $r->qty_counter2 ?? $r->qty_counter3 ?? 0));
        $anyFilled = $realItems->contains(fn($r) => $r->qty_counter1 !== null || $r->qty_counter2 !== null || $r->qty_counter3 !== null);
        if (!$anyFilled) return ['INCOMPLETE', $qtySystem, null];
        $variance = round($sum - $qtySystem, 2);
        return [$variance == 0 ? 'MATCH' : 'RECOUNT', $qtySystem, $variance];
    }

    $activeSlots = [];
    foreach (['1', '2', '3'] as $n) {
        if (!empty($first->{"map_counter{$n}_user"})) $activeSlots[] = $n;
    }
    if (empty($activeSlots)) $activeSlots = ['1', '2', '3'];

    $totals = [];
    foreach ($activeSlots as $n) {
        $field  = "qty_counter{$n}";
        $hasAny = $realItems->contains(fn($r) => $r->{$field} !== null);
        if (!$hasAny) return ['INCOMPLETE', $qtySystem, null];
        $totals[$n] = (float) $realItems->sum($field);
    }

    $unique = array_unique(array_map(fn($v) => round($v, 2), $totals));
    if (count($unique) > 1) return ['NOT MATCH', $qtySystem, null];

    $counted  = array_values($totals)[0];
    $variance = round($counted - $qtySystem, 2);
    return [$variance == 0 ? 'MATCH' : 'RECOUNT', $qtySystem, $variance];
}

public function requestRecalcAccuracy(Request $request, $encConfigId)
{
    if (!$this->isAccountingUser()) {
        abort(403, 'Anda tidak memiliki akses untuk memicu recalculate.');
    }

    $configId = Crypt::decryptString($encConfigId);
    $config   = DB::table('sto_config')->where('config_id', $configId)->first();
    if (!$config) {
        return response()->json(['status' => 0, 'message' => 'Config tidak ditemukan.'], 404);
    }

    // ── validasi mapping_ids harus MILIK config ini, cegah orang input mapping_id config lain ──
    $requestedMappingIds = collect($request->input('mapping_ids', []))->map(fn($v) => (int) $v)->filter();

    if ($requestedMappingIds->isNotEmpty()) {
        $validMappingIds = DB::table('sto_config_mapping')
            ->where('config_id', $configId)
            ->whereIn('mapping_id', $requestedMappingIds)
            ->pluck('mapping_id');

        if ($validMappingIds->isEmpty()) {
            return response()->json(['status' => 0, 'message' => 'Target yang dipilih tidak valid.'], 422);
        }
        $mappingIds = $validMappingIds;
    } else {
        // kosong = semua mapping di config ini (perilaku lama)
        $mappingIds = DB::table('sto_config_mapping')->where('config_id', $configId)->pluck('mapping_id');
    }

    $refreshQty      = filter_var($request->refresh_qty_system, FILTER_VALIDATE_BOOLEAN);
    $includeFinished = filter_var($request->include_finished, FILTER_VALIDATE_BOOLEAN);
    $jobToken        = (string) Str::uuid();

    DB::table('sto_recalc_jobs')->insert([
        'job_token'        => $jobToken,
        'config_id'        => $configId,
        'mapping_ids'      => json_encode($mappingIds->values()->all()), // simpan mapping mana yg diproses
        'refresh_qty'      => $refreshQty,
        'include_finished' => $includeFinished,
        'requested_by'     => Auth::user()->username,
        'status'           => 'QUEUED',
        'total_mappings'   => $mappingIds->count(),
        'created_at'       => now(),
    ]);

    RecalcStoAccuracyJob::dispatch($mappingIds->values()->all(), $refreshQty, $includeFinished, Auth::user()->username, $jobToken);

    return response()->json([
        'status'    => 1,
        'message'   => 'Proses recalculate telah dimulai di background.',
        'job_token' => $jobToken,
    ]);
}

public function checkRecalcAccuracyStatus($jobToken)
{
    $job = DB::table('sto_recalc_jobs')->where('job_token', $jobToken)->first();
    if (!$job) return response()->json(['status' => 0, 'message' => 'Job tidak ditemukan.'], 404);

    return response()->json([
        'status'         => 1,
        'job_status'     => $job->status,
        'total_checked'  => $job->total_checked,
        'total_changed'  => $job->total_changed,
        'error_message'  => $job->error_message,
    ]);
}

// Hitung status gabungan satu artikel lintas seluruh sibling mapping dalam keluarga.
// Untuk lokasi standalone, ini otomatis "keluarga isi satu", jadi hasilnya = perilaku lama.
private function resolveFamilyArticleStatus($mapping, $articleCode, $isManual, $articleDesc, $qtySystem = null)
{
    if ($isManual || !$articleCode) {
        return ['MATCH', null, 0];
    }

    if ($qtySystem === null) {
        $qtySystem = $this->getLastQty($articleCode, $mapping->target_ref, $mapping->sto_date);
    }

    $rows = $this->collectFamilyDtlRows($mapping, $articleCode, $isManual, $articleDesc);
    if ($rows->isEmpty()) return ['INCOMPLETE', $qtySystem, null];

    $siblingMappings = $this->resolveFamilyMappings($mapping);

    if (!($mapping->is_blind ?? true)) {
        $sum = $rows->sum(fn($r) => (float) ($r->qty_counter1 ?? $r->qty_counter2 ?? $r->qty_counter3 ?? 0));
        $anyFilled = $rows->contains(fn($r) => $r->qty_counter1 !== null || $r->qty_counter2 !== null || $r->qty_counter3 !== null);
        if (!$anyFilled) return ['INCOMPLETE', $qtySystem, null];
        $variance = round($sum - $qtySystem, 2);
        return [$variance == 0 ? 'MATCH' : 'RECOUNT', $qtySystem, $variance];
    }

    $activeSlots = [];
    foreach (['1', '2', '3'] as $n) {
        if ($siblingMappings->contains(fn($sm) => !empty($sm->{"counter{$n}_user"}))) {
            $activeSlots[] = $n;
        }
    }
    if (empty($activeSlots)) $activeSlots = ['1', '2', '3'];

    $totals = [];
    foreach ($activeSlots as $n) {
        $field  = "qty_counter{$n}";
        $hasAny = $rows->contains(fn($r) => $r->{$field} !== null);
        if (!$hasAny) return ['INCOMPLETE', $qtySystem, null];
        $totals[$n] = (float) $rows->sum($field);
    }

    $unique = array_unique(array_map(fn($v) => round($v, 2), $totals));
    if (count($unique) > 1) return ['NOT MATCH', $qtySystem, null];

    $counted  = array_values($totals)[0];
    $variance = round($counted - $qtySystem, 2);
    return [$variance == 0 ? 'MATCH' : 'RECOUNT', $qtySystem, $variance];
}

// Dipanggil setelah insert/update/delete satu baris — recompute status artikel ini
// di level family, lalu TULIS ULANG count_status/qty_system/qty_variance ke SEMUA
// baris sto_dtl (lintas sibling mapping) yang artikelnya sama, biar tampilannya konsisten.
// Dipanggil setelah insert/update/delete satu baris — recompute & TULIS ULANG
// count_status/qty_system/qty_variance. LOCATION → broadcast ke seluruh
// keluarga (parent+child). SUPPLIER/CUSTOMER → cuma baris itu sendiri (lama).
private function syncArticleStatus($mapping, $dtl)
{
    $now = date('Y-m-d H:i:s');

    if ($dtl->is_manual || is_null($dtl->article_code)) {
        DB::table('sto_dtl')->where('dtl_id', $dtl->dtl_id)->update([
            'count_status' => 'MATCH', 'qty_system' => null, 'qty_variance' => 0, 'updated_at' => $now,
        ]);
        return;
    }

    if ($mapping->target_type === 'LOCATION') {
        [$status, $qtySystem, $variance] = $this->resolveFamilyArticleStatus($mapping, $dtl->article_code, false, null);
        $rows = $this->collectFamilyDtlRows($mapping, $dtl->article_code, false, null);
        if ($rows->isNotEmpty()) {
            DB::table('sto_dtl')->whereIn('dtl_id', $rows->pluck('dtl_id'))->update([
                'count_status' => $status, 'qty_system' => $qtySystem, 'qty_variance' => $variance, 'updated_at' => $now,
            ]);
        }
        return;
    }

    // SUPPLIER/CUSTOMER — per baris saja, seperti perilaku asli
    $qtySystem = $this->getLastQty($dtl->article_code, $dtl->location_number, $mapping->sto_date);
    [$status, , $variance] = $this->resolveSingleRowStatus($dtl, $mapping, $qtySystem);
    DB::table('sto_dtl')->where('dtl_id', $dtl->dtl_id)->update([
        'count_status' => $status, 'qty_system' => $qtySystem, 'qty_variance' => $variance, 'updated_at' => $now,
    ]);
}

}