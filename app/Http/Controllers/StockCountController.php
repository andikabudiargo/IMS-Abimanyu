<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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
        '008' => ['008'],
        '006' => ['CM2', 'CM3'],
        '005' => ['CM1'],
        '042' => ['CM1'],
        '049' => ['CM1'],
    ];

   
    private function isAutoNumber($targetRef)
    {
        return in_array($targetRef, $this->autoNumberLocations);
    }
 
    private function getTableColoumnAudit()
    {
        $kolom = [
            ['data'=>'location',      'name'=>'target_name',   'title'=>'Lokasi/Partner'],
            ['data'=>'article_code',  'name'=>'article_code',  'title'=>'Article Code'],
            ['data'=>'article_desc',  'name'=>'article_desc',  'title'=>'Article Desc'],
            ['data'=>'min_package',   'name'=>'min_package',   'title'=>'Packing'],
            ['data'=>'qty_counter1',  'name'=>'qty_counter1',  'title'=>'Qty C1'],
            ['data'=>'qty_counter2',  'name'=>'qty_counter2',  'title'=>'Qty C2'],
            ['data'=>'qty_counter3',  'name'=>'qty_counter3',  'title'=>'Qty C3'],
            ['data'=>'qty_system',    'name'=>'qty_system',    'title'=>'Stock System'],
            ['data'=>'qty_variance',  'name'=>'qty_variance',  'title'=>'Variance'],
            ['data'=>'uom',           'name'=>'uom',           'title'=>'UOM'],
            ['data'=>'count_status',  'name'=>'count_status',  'title'=>'Status'],
            ['data'=>'sto_number',    'name'=>'sto_number',    'title'=>'STO Number'],
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
            // partner: dedup per lokasi yang sama; tanpa lokasi tidak bisa dedup dengan aman
            if (!$locationNumber) return false;
            $query->where('h.mapping_id', $mappingId)
                  ->where('d.location_number', $locationNumber);
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
 
        $userId = Auth::id();
 
        $stoHdr = DB::table('sto_hdr')->where('sto_id', $dtl->sto_id)->first();
        if (!$stoHdr || $stoHdr->status != 1) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Sheet ini sudah dikunci, tidak bisa diedit.'],'alert'=>'error']);
        }
 
        $m = DB::table('sto_config_mapping')->where('mapping_id', $stoHdr->mapping_id)->first();
 
        // role dipakai untuk tentukan kolom qty_/user_/at_ yang diupdate
        // (bug lama: $role tidak pernah didefinisikan sebelum dipakai jadi $field)
        $role = $this->resolveCounterRole($dtl, $userId);
        if (!$role) {
            $access = $this->checkAccess($stoHdr->mapping_id);
            if (!$access['ok']) {
                return response()->json(['status'=>0,'title'=>'Ditolak','message'=>[$access['message']],'alert'=>'error']);
            }
            $role = $access['role'];
        }
 
        // ── FIX: $isManual & $article sebelumnya tidak pernah didefinisikan di sini,
        // sehingga setiap edit baris diam-diam menimpa article_code/is_manual jadi NULL ──
        $isManual = filter_var($request->is_manual, FILTER_VALIDATE_BOOLEAN);
        $article  = $isManual ? null : $request->article;
        $qty      = (float) str_replace(',', '', $request->qty);
 
        if ($qty <= 0) {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['QTY harus lebih dari 0.'],'alert'=>'warning']);
        }
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
 
        $field = "qty_{$role}";
        $now   = date('Y-m-d H:i:s');
 
        DB::table('sto_dtl')->where('dtl_id', $dtlId)->update([
            'article_code'    => $article,
            'article_desc'    => $request->article_desc,
            'is_manual'       => $isManual,
            'uom'             => $request->uom,
            'min_package'     => $request->min_package !== '' && $request->min_package !== null ? $request->min_package : 0,
            'location_number' => $lineLocationNumber,
            $field            => $qty,
            'note'            => $request->note,
            'updated_at'      => $now,
        ]);
 
        $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
        [$status, $qtySystem, $variance] = $this->resolveStatus($dtl, $m);
        DB::table('sto_dtl')->where('dtl_id', $dtlId)->update([
            'count_status' => $status,
            'qty_system'   => $qtySystem,
            'qty_variance' => $variance,
            'updated_at'   => $now,
        ]);
        $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
 
        $this->recalcMappingProgress($stoHdr->mapping_id);
        $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $stoHdr->mapping_id)->value('target_act_loc');
        $myQty = $dtl->{$field};
 
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
            'stoCodesForFilter' => $stoCodesForFilter,
            'targetsForFilter'  => $targetsForFilter,
        ]);
    }
 
    // ══════════════════════════════════════════════
    // AUDIT LIST
    // ══════════════════════════════════════════════
    public function auditList(Request $request)
    {
        $allowedUserIds = [58, 75, 23, 163, 176]; // ← isi sesuai kebutuhan
 
        if (!in_array(Auth::id(), $allowedUserIds)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
 
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
                'd.dtl_id', 'm.target_type',
                DB::raw("COALESCE(l.location_name, tp.nama, m.target_ref) as target_name"),
                'd.article_code', 'd.article_desc', 'd.min_package', 'd.uom',
                'd.qty_counter1', 'd.qty_counter2', 'd.qty_counter3', 'd.qty_system', 'd.qty_variance',
                'd.count_status', 'h.sto_number',
                'u1.name as counter1_name', 'd.counter1_at',
                'u2.name as counter2_name', 'd.counter2_at',
                'u3.name as counter3_name', 'd.counter3_at',
            ]);
 
        if ($request->filled('searchStoCode'))    $query->where('c.sto_code', $request->searchStoCode);
        if ($request->filled('searchPeriode'))    $query->where('c.periode', $request->searchPeriode);
        if ($request->filled('searchTarget'))     $query->where('m.target_ref', $request->searchTarget);
        if ($request->filled('searchStatus'))     $query->where('d.count_status', $request->searchStatus);
        if ($request->filled('searchArticleCode')) $query->where('d.article_code', 'ilike', '%'.$request->searchArticleCode.'%');
        if ($request->filled('searchStoNumber'))  $query->where('h.sto_number', 'ilike', '%'.$request->searchStoNumber.'%');
 
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
 
        return DataTables::of($query)
            ->addColumn('location', fn($row) => $row->target_name)
            ->editColumn('article_code', fn($row) => $row->article_code ?? 'OTHER')
            ->editColumn('qty_counter1', fn($row) => $row->qty_counter1 !== null ? number_format((float)$row->qty_counter1, 2) : '-')
            ->editColumn('qty_counter2', fn($row) => $row->qty_counter2 !== null ? number_format((float)$row->qty_counter2, 2) : '-')
            ->editColumn('qty_counter3', fn($row) => $row->qty_counter3 !== null ? number_format((float)$row->qty_counter3, 2) : '-')
            ->editColumn('qty_system',   fn($row) => $row->qty_system   !== null ? number_format((float)$row->qty_system,   2) : '-')
            ->editColumn('qty_variance', function ($row) {
                if ($row->qty_variance === null) return '-';
                $val = number_format((float)$row->qty_variance, 2);
                $cls = (float)$row->qty_variance > 0 ? 'text-success' : ((float)$row->qty_variance < 0 ? 'text-danger' : '');
                return "<span class='{$cls}'>{$val}</span>";
            })
            ->editColumn('count_status', function ($row) {
                $map = ['INCOMPLETE'=>'badge-secondary','NOT MATCH'=>'badge-danger','RECOUNT'=>'badge-warning','MATCH'=>'badge-success'];
                $cls = $map[$row->count_status] ?? 'badge-secondary';
                return '<span class="badge '.$cls.'">'.$row->count_status.'</span>';
            })
            ->editColumn('counter1_name', fn($row) => $row->counter1_name ?? '-')
            ->editColumn('counter2_name', fn($row) => $row->counter2_name ?? '-')
            ->editColumn('counter3_name', fn($row) => $row->counter3_name ?? '-')
            ->editColumn('counter1_at', fn($row) => $row->counter1_at ? date('d-m-Y H:i', strtotime($row->counter1_at)) : '-')
            ->editColumn('counter2_at', fn($row) => $row->counter2_at ? date('d-m-Y H:i', strtotime($row->counter2_at)) : '-')
            ->editColumn('counter3_at', fn($row) => $row->counter3_at ? date('d-m-Y H:i', strtotime($row->counter3_at)) : '-')
            ->rawColumns(['location', 'qty_variance', 'count_status'])
            ->make(true);
    }
 
    // ══════════════════════════════════════════════
    // GUARD
    // ══════════════════════════════════════════════
    private function checkAccess($mappingId)
    {
        $userId = Auth::id();
        $isAcct = Auth::user()->hasRole('accounting');
        $today  = date('d-m-Y');
 
        $m = DB::table('sto_config_mapping as m')
            ->join('sto_config as h', 'h.config_id', '=', 'm.config_id')
            ->where('m.mapping_id', $mappingId)
            ->select('m.*', 'h.status as config_status', 'h.sto_code', 'h.periode')
            ->first();
 
        if (!$m) return ['ok' => false, 'message' => 'Target STO tidak ditemukan.'];
 
        // hanya CANCELED yang benar-benar diblokir total; SCHEDULED/ONGOING/COMPLETED tetap lolos
        if ($m->config_status == 5) {
            return ['ok' => false, 'message' => 'STO ini sudah dibatalkan (CANCELED).'];
        }
 
        if ($isAcct) {
            return ['ok' => true, 'role' => 'accounting', 'mapping' => $m];
        }
 
        // ── target ini SUDAH selesai → boleh lihat read-only kapan saja, tanpa syarat tanggal ──
        if ($m->finish_time) {
            if ($m->counter1_user == $userId) return ['ok' => true, 'role' => 'counter1', 'mapping' => $m];
            if ($m->counter2_user == $userId) return ['ok' => true, 'role' => 'counter2', 'mapping' => $m];
            if (($m->counter3_user ?? null) == $userId) return ['ok' => true, 'role' => 'counter3', 'mapping' => $m];
            return ['ok' => false, 'message' => 'Anda tidak terdaftar sebagai counter untuk target ini.'];
        }
 
        // ── belum selesai → aturan lama: harus tanggal STO hari ini ──
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
 
        if ($isAuto) {
            return $this->storeLineAuto($mappingId, $m, $access, $userId, $locationNumber, $isManual, $article, $request, $qty, $confirmAccumulate, $now);
        }
 
        return response()->json(['status'=>0,'title'=>'Error','message'=>['Gunakan storeSheet untuk gudang ini.'],'alert'=>'error']);
    }
 
    // ── AUTO: 1 artikel = 1 sto_hdr ──
    private function storeLineAuto($mappingId, $m, $access, $userId, $locationNumber, $isManual, $article, $request, $qty, $confirmAccumulate, $now)
    {
        return DB::transaction(function () use ($mappingId, $m, $access, $userId, $locationNumber, $isManual, $article, $request, $qty, $confirmAccumulate, $now) {
 
            // cari sto_hdr untuk artikel ini (1 artikel = 1 hdr, dedup global per mapping)
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
                // ── artikel baru → buat sto_hdr baru + dtl ──
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
                    'sto_id'                  => $stoId,
                    'article_code'            => $article,
                    'article_desc'            => $request->article_desc,
                    'is_manual'               => $isManual,
                    'uom'                     => $request->uom,
                    'min_package'             => $request->min_package ?: null,
                    'location_number'         => $locationNumber,
                    "qty_{$access['role']}"   => $qty,
                    "{$access['role']}_user"  => $userId,
                    "{$access['role']}_at"    => $now,
                    'note'                    => $request->note,
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ], 'dtl_id');
 
                $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
                [$status, $qtySystem, $variance] = $this->resolveStatus($dtl, $m);
                DB::table('sto_dtl')->where('dtl_id', $dtlId)->update([
                    'count_status' => $status,
                    'qty_system'   => $qtySystem,
                    'qty_variance' => $variance,
                    'updated_at'   => $now,
                ]);
                $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
                $this->recalcMappingProgress($mappingId);
                $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->value('target_act_loc');
                $myQty = $dtl->{"qty_{$access['role']}"};
 
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
                        'count_status'   => $dtl->count_status,
                        'note'           => $dtl->note,
                        'location_number'=> $dtl->location_number,
                        'location_name'  => $this->resolveLocationName($dtl->location_number),
                    ],
                ]);
            }
 
            // ── artikel sudah ada → update slot counter ──
            if ($existingDtl->hdr_status != 1) {
                return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Sheet artikel ini sudah dikunci.'],'alert'=>'error']);
            }
 
            $field     = "qty_{$access['role']}";
            $userField = "{$access['role']}_user";
            $atField   = "{$access['role']}_at";
            $ownerId   = $existingDtl->{$userField} ?? null;
 
            if ($ownerId && $ownerId != $userId) {
                return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Slot counter ini sudah diisi user lain.'],'alert'=>'error']);
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
            [$status, $qtySystem, $variance] = $this->resolveStatus($dtl, $m);
            DB::table('sto_dtl')->where('dtl_id', $dtl->dtl_id)->update([
                'count_status' => $status, 'qty_system' => $qtySystem, 'qty_variance' => $variance, 'updated_at' => $now,
            ]);
            $dtl = DB::table('sto_dtl')->where('dtl_id', $dtl->dtl_id)->first();
            $this->recalcMappingProgress($mappingId);
            $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->value('target_act_loc');
            $myQty = $dtl->{"qty_{$access['role']}"};
 
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
                    'count_status'   => $dtl->count_status,
                    'note'           => $dtl->note,
                    'location_number'=> $dtl->location_number,
                    'location_name'  => $this->resolveLocationName($dtl->location_number),
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
        if ($access['role'] === 'accounting') {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Accounting tidak melakukan input counting.'],'alert'=>'warning']);
        }
 
        $m      = $access['mapping'];
        $userId = Auth::id();
        $lines  = $request->lines ?? [];
        $isAuto = $this->isAutoNumber($m->target_ref);
 
        $filled = array_filter($lines, fn($l) => !empty($l['article'] ?? $l['article_desc'] ?? '') && isset($l['qty']) && (float)str_replace(',','',$l['qty']) > 0);
        if (count($filled) === 0) {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['Minimal 1 baris harus diisi beserta QTY-nya.'],'alert'=>'warning']);
        }
        if (count($filled) > 7) {
            return response()->json(['status'=>0,'title'=>'Warning','message'=>['Maksimal 7 baris per sheet.'],'alert'=>'warning']);
        }
 
        // ── WAJIB pilih nomor manual untuk non-auto ──
        $selectedNo = null;
        if (!$isAuto) {
            $selectedNo = (int) $request->selected_number;
            if (!$selectedNo) {
                return response()->json(['status'=>0,'title'=>'Warning','message'=>['Pilih Nomor STO terlebih dahulu.'],'alert'=>'warning']);
            }
        }
 
        // ── duplikat DALAM sheet yang sama ──
        $articlesSeen = [];
        foreach ($filled as $l) {
            $key = !empty($l['article']) ? $l['article'] : ('MANUAL::'.strtoupper(trim($l['article_desc'] ?? '')));
            if (in_array($key, $articlesSeen)) {
                return response()->json(['status'=>0,'title'=>'Warning','message'=>['Ada artikel duplikat dalam sheet ini.'],'alert'=>'warning']);
            }
            $articlesSeen[] = $key;
        }
 
        // ── duplikat TERHADAP data tersimpan sebelumnya (scope tergantung tipe target) ──
        foreach ($filled as $l) {
            $isManualLine = filter_var($l['is_manual'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $articleLine  = $isManualLine ? null : ($l['article'] ?? null);
            $descLine     = $l['article_desc'] ?? '';
            // partner: lokasi per baris dari form; LOCATION: pakai target_ref langsung
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
 
        return DB::transaction(function () use ($mappingId, $m, $access, $userId, $filled, $isAuto, $selectedNo) {
            $now = date('Y-m-d H:i:s');
 
            if ($isAuto) {
                $stoNumber = $this->generateStoNumber($mappingId);
            } else {
                // ── lock mapping row, validasi nomor pilihan user ──
                // FIX: kolom sebenarnya no_dari/no_sampai (sama seperti dipakai generateStoNumber()
                // & getAvailableNumbers()) — sebelumnya salah baca no_start/no_end yang tidak ada
                // di tabel, sehingga validasi range selalu fallback ke 1-9999 (tidak berfungsi).
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
                // partner: location_number per baris; LOCATION: target_ref
                $locationNumber = $m->target_type === 'LOCATION' ? $m->target_ref : ($l['location_number'] ?? null);
 
                $dtlId = DB::table('sto_dtl')->insertGetId([
                    'sto_id'                  => $stoId,
                    'article_code'            => $article,
                    'article_desc'            => $l['article_desc'] ?? null,
                    'is_manual'               => $isManual,
                    'uom'                     => $l['uom'] ?? null,
                    'min_package'             => $l['min_package'] ?: null,
                    'location_number'         => $locationNumber,
                    "qty_{$access['role']}"   => $qty,
                    "{$access['role']}_user"  => $userId,
                    "{$access['role']}_at"    => $now,
                    'note'                    => $l['note'] ?? null,
                    'created_at'              => $now,
                    'updated_at'              => $now,
                ], 'dtl_id');
 
                $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
                [$status, $qtySystem, $variance] = $this->resolveStatus($dtl, $m);
                DB::table('sto_dtl')->where('dtl_id', $dtlId)->update([
                    'count_status' => $status, 'qty_system' => $qtySystem, 'qty_variance' => $variance, 'updated_at' => $now,
                ]);
                $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
                $myQty = $dtl->{"qty_{$access['role']}"};
 
                $savedLines[] = [
                    'dtl_id'         => $dtl->dtl_id,
                    'sto_number'     => $stoNumber,
                    'article_code'   => $dtl->article_code ?? 'OTHER',
                    'article_desc'   => $dtl->article_desc,
                    'uom'            => $dtl->uom,
                    'min_package'    => $dtl->min_package,
                    'my_qty'         => $myQty !== null ? number_format((float)$myQty, 2) : null,
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
        if (!($mapping->is_blind ?? true)) {
            $qty = $dtl->qty_counter1 ?? $dtl->qty_counter2 ?? ($dtl->qty_counter3 ?? null);
            if (is_null($qty)) return ['INCOMPLETE', null, null];
            return $this->compareToSystem($qty, $dtl, $mapping);
        }
 
        $activeQty = [];
        if (!empty($mapping->counter1_user)) $activeQty[] = $dtl->qty_counter1;
        if (!empty($mapping->counter2_user)) $activeQty[] = $dtl->qty_counter2;
        if (!empty($mapping->counter3_user)) $activeQty[] = $dtl->qty_counter3 ?? null;
 
        foreach ($activeQty as $q) {
            if (is_null($q)) return ['INCOMPLETE', null, null];
        }
 
        // ── bulatkan ke 2 desimal sebelum dibandingkan, hindari false-mismatch akibat floating point ──
        $unique = array_unique(array_map(fn($q) => round((float) $q, 2), $activeQty));
        if (count($unique) > 1) return ['NOT MATCH', null, null];
 
        return $this->compareToSystem($activeQty[0], $dtl, $mapping);
    }
 
    private function compareToSystem($qty, $dtl, $mapping)
    {
        if ($dtl->is_manual || is_null($dtl->article_code)) return ['MATCH', null, 0];
        if (empty($dtl->location_number)) {
            $variance = round((float)$qty - 0, 2);
            return [($variance == 0 ? 'MATCH' : 'RECOUNT'), 0, $variance];
        }
        $qtySystem = (float) $this->getLastQty($dtl->article_code, $dtl->location_number, $mapping->sto_date);
        $variance  = round((float)$qty - $qtySystem, 2);
        return [($variance == 0 ? 'MATCH' : 'RECOUNT'), $qtySystem, $variance];
    }
 
    private function getLastQty($article, $location, $stoDate)
    {
        return (float) (DB::table('warehouse_stock as ws')
            ->join('article as a', 'a.article_code', '=', 'ws.article_code')
            ->where('a.article_alternative_code', $article)
            ->where('ws.location_number', $location)
            ->value('ws.article_qty') ?? 0);
    }
 
    // ══════════════════════════════════════════════
    // DELETE LINE
    // ══════════════════════════════════════════════
    public function deleteLine(Request $request, $dtlId)
    {
        $dtl = DB::table('sto_dtl')->where('dtl_id', $dtlId)->first();
        if (!$dtl) return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Baris tidak ditemukan.'],'alert'=>'error']);
 
        $userId = Auth::id();
 
        $role = $this->resolveCounterRole($dtl, $userId);
        if (!$role) {
            return response()->json(['status'=>0,'title'=>'Ditolak','message'=>['Anda tidak berwenang menghapus baris ini.'],'alert'=>'error']);
        }
 
        // cek apakah ADA slot counter LAIN (selain milik saya) yang sudah terisi
        $otherFilled = false;
        foreach (['counter1', 'counter2', 'counter3'] as $r) {
            if ($r === $role) continue;
            if (!is_null($dtl->{"qty_{$r}"} ?? null)) { $otherFilled = true; break; }
        }
 
        $now    = date('Y-m-d H:i:s');
        $stoHdr = DB::table('sto_hdr')->where('sto_id', $dtl->sto_id)->first();
 
        if (!$otherFilled) {
            DB::table('sto_dtl')->where('dtl_id', $dtlId)->delete();
 
            // kalau hdr ini sudah tidak punya dtl lagi → hapus hdr juga & rollback no_current
            $remainingDtl = DB::table('sto_dtl')->where('sto_id', $dtl->sto_id)->count();
            if ($remainingDtl === 0 && $stoHdr) {
                DB::table('sto_hdr')->where('sto_id', $dtl->sto_id)->delete();
                // rollback no_current supaya nomor bisa dipakai lagi
                DB::table('sto_config_mapping')
                    ->where('mapping_id', $stoHdr->mapping_id)
                    ->decrement('no_current');
            }
 
            $freshTargetAct = null;
            if ($stoHdr) {
                $this->recalcMappingProgress($stoHdr->mapping_id);
                $freshTargetAct = DB::table('sto_config_mapping')->where('mapping_id', $stoHdr->mapping_id)->value('target_act_loc');
            }
 
            return response()->json([
                'status'         => 1,
                'title'          => 'Berhasil',
                'message'        => 'Baris dihapus.',
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
    private function recalcMappingProgress($mappingId)
    {
        $m = DB::table('sto_config_mapping')->where('mapping_id', $mappingId)->first();
        if (!$m) return;
 
        // hitung dari semua hdr milik mapping ini
        $stoIds = DB::table('sto_hdr')->where('mapping_id', $mappingId)->pluck('sto_id');
        $total  = DB::table('sto_dtl')->whereIn('sto_id', $stoIds)->count();
        $match  = DB::table('sto_dtl')->whereIn('sto_id', $stoIds)->where('count_status', 'MATCH')->count();
        $actLoc = $total > 0 ? round(($match / $total) * 100, 2) : 0;
 
        DB::table('sto_config_mapping')->where('mapping_id', $mappingId)
            ->update(['target_act_loc' => $actLoc, 'updated_at' => date('Y-m-d H:i:s')]);
 
        $actGlobal = DB::table('sto_config_mapping')->where('config_id', $m->config_id)->avg('target_act_loc');
        DB::table('sto_config')->where('config_id', $m->config_id)
            ->update(['target_act' => round($actGlobal ?? 0, 2), 'updated_at' => date('Y-m-d H:i:s')]);
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
}