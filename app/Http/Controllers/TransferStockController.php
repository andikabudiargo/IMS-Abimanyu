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
    use Excel;
    use App\Imports\TransferOutImport;
    use App\Exports\TransferOutExport;

        /*

        Simplifikasi Transfer In dan Out jadi Transfer Stock

        */

        class TransferStockController extends Controller
        {
            private $title;
            private $moduleCode;
            private $ngRmLocation = '037';   // Gudang NG RM
            private $siteCode     = 'HO';
            public function __construct()
            {
                $this->title = "Stock Transfer";
                $this->moduleCode = "TRF";
                
            }

            public function getTableColoumn()
        {
            $kolom =
            [
                ['data'=>'action','name'=>'action','title'=>'Action','orderable'=>false,'searchable'=>false],
                ['data'=>'tr_number','name'=>'tr_number','title'=>'Transfer Number'],
                ['data'=>'tr_date','name'=>'tr_date','title'=>'Date'],
                ['data'=>'tr_type','name'=>'tr_type','title'=>'Type','orderable'=>false,'searchable'=>false],
                ['data'=>'status','name'=>'status','title'=>'Status'],
                ['data'=>'location_name','name'=>'location_name','title'=>'Location From'],
                ['data'=>'location_name_to','name'=>'location_name_to','title'=>'Location To'],
                ['data'=>'note','name'=>'note','title'=>'Note'],
                ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
                ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
                ['data'=>'updated_by','name'=>'updated_by','title'=>'Approved By','orderable'=>false,'searchable'=>false],
            ];
            return json_encode($kolom, true);
        }

        public function getTableColoumnDetail()
        {
            $kolom =
            [
                ['data'=>'tr_number','name'=>'tr_number','title'=>'Transfer Number'],
                ['data'=>'tr_date','name'=>'tr_date','title'=>'Date'],
                ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
                ['data'=>'article_desc','name'=>'article_desc','title'=>'Article Desc'],
                ['data'=>'qty','name'=>'qty','title'=>'Qty'],
                ['data'=>'uom','name'=>'uom','title'=>'UOM'],
                ['data'=>'note','name'=>'note','title'=>'Note'],
                ['data'=>'status','name'=>'status','title'=>'Status'],
                ['data'=>'location_name','name'=>'location_name','title'=>'Location From'],
                ['data'=>'location_name_to','name'=>'location_name_to','title'=>'Location To'],
                ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
                ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
                ['data'=>'updated_by','name'=>'updated_by','title'=>'Approved By','orderable'=>false,'searchable'=>false],
                ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
                ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
            ];
            return json_encode($kolom, true);
        }

        public function getLastCode($key, $trDate = null, $username = null)
{
    $username = $username ?? optional(Auth::user())->username ?? 'system-migration';
    $months   = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];

    if (empty($trDate)) {
        $refDate = now();
    } else {
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trDate)) {
                $refDate = \Carbon\Carbon::createFromFormat('Y-m-d', $trDate);
            } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $trDate)) {
                $refDate = \Carbon\Carbon::createFromFormat('d-m-Y', $trDate);
            } else {
                $refDate = \Carbon\Carbon::parse($trDate);
            }
        } catch (\Exception $e) {
            $refDate = now();
        }
    }

    $year  = $refDate->year;
    $month = $refDate->month;
    $isCurrentPeriod = ($year === now()->year && $month === now()->month);

    if ($isCurrentPeriod) {
        $newCode = DB::selectOne("
            UPDATE master_code
               SET code_number = code_number + 1, updated_by = ?, updated_at = now()
             WHERE code_key = ?
         RETURNING code_number
        ", [$username, $key])->code_number;
    } else {
        // ── BACKDATE: lock per (key + tahun + bulan) supaya MAX+1 atomik ──
        $lockKey = sprintf('%s-backdate-%d-%d', $key, $year, $month);
        DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$lockKey]);

        $prefixLike = sprintf('%s/%d/%s/%%', $key, $year, $months[$month - 1]);

        $maxSeq = (int) DB::selectOne("
            SELECT COALESCE(MAX( (split_part(tr_number, '/', 4))::int ), 0) AS mx
              FROM transfer_stock_hdr
             WHERE tr_number LIKE ?
        ", [$prefixLike])->mx;

        $newCode = $maxSeq + 1;
    }

    return sprintf('%s/%s/%s/%04d', $key, $year, $months[$month - 1], $newCode);
}

private function lockMovementSequence(): void
{
    // Key WAJIB sama persis di semua controller yang insert warehouse_movement.
    DB::select("SELECT pg_advisory_xact_lock(hashtext('warehouse_movement_code'))");
}

private function recalculateAvgPrice(string $articleCode, string $location): void
{
    $movements = DB::table('warehouse_movement')
        ->where('artikel_code', $articleCode)
        ->where('location_number', $location)
        ->where('site_code', $this->siteCode)
        ->orderBy(DB::raw("TO_DATE(movement_date,'DD-MM-YYYY')"), 'asc')
        ->orderBy('movement_code', 'asc')
        ->select('movement_min', 'movement_plus', 'movement_price')
        ->get();

    $qty = 0.0;
    $avg = 0.0;

    foreach ($movements as $m) {
        $plus = (float) $m->movement_plus;
        $min  = (float) $m->movement_min;

        if ($plus > 0) {
            $price  = (float) $m->movement_price;
            $newQty = $qty + $plus;
            $avg    = $newQty > 0 ? (($qty * $avg) + ($plus * $price)) / $newQty : $avg;
            $qty    = $newQty;
        }
        if ($min > 0) {
            $qty -= $min; // keluar tidak mengubah avg
        }
    }

    DB::table('warehouse_stock')
        ->where('site_code', $this->siteCode)
        ->where('article_code', $articleCode)
        ->where('location_number', $location)
        ->update(['avg_price' => $avg]);
}
            public function index(Request $request)
        {
            $user      = Auth::user();
            $username  = $user->username;
            $userDepts = DB::table('user_dept')
                            ->where('username', $username)
                            ->pluck('dept')
                            ->toArray();

            $data['title']       = "$this->title";
            $data['subtitle']    = "$this->title";
            $data['kolom']       = $this->getTableColoumn();
            $data['kolomDetail'] = $this->getTableColoumnDetail();

            $data['locations'] = DB::table('stock_location_master')
                ->orderBy('location_name')
                ->get();

            $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];

            $baseSelect = [
            'transfer_stock_hdr.*',
            'locFrom.location_name as location_name',
            'locTo.location_name as location_name_to',
        ];

            // ===== OUTSTANDING IN =====
            // Transfer masuk ke gudang dept saya → saya yang harus posting
            // approve_dept di-snapshot saat store = dept_code gudang tujuan
            $data['outstandingIn'] = DB::table('transfer_stock_hdr')
                ->leftJoin('stock_location_master as locFrom', 'locFrom.location_code', '=', 'transfer_stock_hdr.location_from')
                ->leftJoin('stock_location_master as locTo',   'locTo.location_code',   '=', 'transfer_stock_hdr.location_to')
                ->whereIn('transfer_stock_hdr.status', ['1', '2'])
                ->whereIn('transfer_stock_hdr.approve_dept', $userDepts)
                ->select($baseSelect)
                ->orderBy('transfer_stock_hdr.created_at', 'asc')
                ->get()
                ->map(function ($row) {
            $created = \Carbon\Carbon::parse($row->created_at);
            $seconds = $created->diffInSeconds(now(), false); // false = boleh negatif
            // guard kalau ada clock skew kecil → anggap 0
            if ($seconds < 0) $seconds = 0;
            $row->age_seconds = $seconds; // simpan untuk footer "terlama"
            $aging = $this->formatAging($seconds);
            $row->aging_label = $aging['label'];
            $row->aging_level = $aging['level'];
            return $row;
        });

            $data['outstandingInCount'] = $data['outstandingIn']->count();

            // ===== OUTSTANDING OUT =====
            // Transfer keluar dari gudang dept saya → menunggu diposting dept penerima
            // locFrom.dept_code = dept saya, tapi approve_dept BUKAN dept saya (hindari overlap dengan IN)
            $data['outstandingOut'] = DB::table('transfer_stock_hdr')
                ->leftJoin('stock_location_master as locFrom', 'locFrom.location_code', '=', 'transfer_stock_hdr.location_from')
                ->leftJoin('stock_location_master as locTo',   'locTo.location_code',   '=', 'transfer_stock_hdr.location_to')
                ->whereIn('transfer_stock_hdr.status', ['1', '2'])
                ->where(function ($q) use ($userDepts, $username) {
                    $q->whereIn('locFrom.dept_code', $userDepts)
                    ->orWhere('transfer_stock_hdr.created_by', $username);
                })
                ->whereNotIn('transfer_stock_hdr.approve_dept', $userDepts) // hindari duplikat dengan IN
                ->select($baseSelect)
                ->orderBy('transfer_stock_hdr.created_at', 'asc')
                ->get()
            ->map(function ($row) {
            $created = \Carbon\Carbon::parse($row->created_at);
            $seconds = $created->diffInSeconds(now(), false); // false = boleh negatif
            // guard kalau ada clock skew kecil → anggap 0
            if ($seconds < 0) $seconds = 0;
            $row->age_seconds = $seconds; // simpan untuk footer "terlama"
            $aging = $this->formatAging($seconds);
            $row->aging_label = $aging['label'];
            $row->aging_level = $aging['level'];
            return $row;
        });

            $data['outstandingOutCount'] = $data['outstandingOut']->count();

            return view("transfer/transferStock.index", $data);
        }

    private function processPosting(string $trNumber, string $username): array
    {
        $hdrQ = DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->first();
        if (!$hdrQ) {
            return ['success' => false, 'message' => ["Transfer $trNumber tidak ditemukan"]];
        }

        try {
            $lines = $this->resolveTransferLines($hdrQ);
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => [$e->getMessage()]];
        }

        $movementDate = \Carbon\Carbon::createFromFormat('d-m-Y', $hdrQ->tr_date)->format('d-m-Y');
        $locationFrom = $hdrQ->location_from;   // lokasi FISIK (bisa 010x)
        $locationTo   = $hdrQ->location_to;     // lokasi FISIK (bisa 010x)
        $trType       = ($hdrQ->tr_type === 'SUPPLY') ? 'SUPPLY' : 'TRANSFER';

        // ── Resolusi lokasi AKUNTANSI (pool jika lokasi punya parent) ──
        $stockFrom = $this->getStockLocation($locationFrom);
        $stockTo   = $this->getStockLocation($locationTo);

        $this->lockMovementSequence();
        $seq             = (int) DB::table('warehouse_movement')->max('movement_code');
        $dataSetMovement = [];

        // ===== KELUAR dari gudang asal =====
        foreach ($lines['out'] as $line) {
            $price = $this->getAvgPrice($line['article_code'], $stockFrom);

            $dataSetMovement[] = $this->buildMovement(
                ++$seq, $hdrQ, $line, $trType, 'min',
                $stockFrom,                 // location_number = POOL (akuntansi)
                $locationFrom, $locationTo, // movement_from/to = FISIK (audit)
                $price, $this->movementDesc($hdrQ->note, $line), $username, $movementDate
            );
        }

        // ===== MASUK ke gudang tujuan =====
        foreach ($lines['in'] as $line) {
            // #4: FG→RM di gudang NG RM → harga RM diambil dari avg RM (gudang 009)
            $isFgToNgRm = ($locationTo === $this->ngRmLocation);
            $priceLoc   = $isFgToNgRm ? '009' : $stockFrom;
            $price      = $this->getAvgPrice($line['article_code'], $priceLoc);

            $dataSetMovement[] = $this->buildMovement(
                ++$seq, $hdrQ, $line, $trType, 'plus',
                $stockTo,                   // location_number = POOL (akuntansi)
                $locationFrom, $locationTo, // movement_from/to = FISIK (audit)
                $price, $this->movementDesc($hdrQ->note, $line), $username, $movementDate
            );
        }

        if (!empty($dataSetMovement)) {
            DB::table('warehouse_movement')->insert($dataSetMovement);
        }

        // Recalculate berdasarkan lokasi AKUNTANSI (pool).
        // Kasus 010A→010B: stockFrom=stockTo=012 → recalc 012 sekali, net zero.
        $affected = [];
        foreach (array_merge($lines['out'], $lines['in']) as $line) {
            $affected[$line['article_code'].'|'.$stockFrom] = ['article' => $line['article_code'], 'loc' => $stockFrom];
            $affected[$line['article_code'].'|'.$stockTo]   = ['article' => $line['article_code'], 'loc' => $stockTo];
        }
        foreach ($affected as $a) {
            $this->recalculateMovementAndStock($a['article'], $a['loc'], $hdrQ->tr_date);
        }

        return ['success' => true, 'message' => "Transfer $trNumber berhasil diposting"];
    }

        private function formatAging(float $seconds): array
        {
            $seconds = (int) abs($seconds);

            if ($seconds < 60) {
                return ['label' => $seconds . ' detik', 'level' => 'success'];   // hijau
            } elseif ($seconds < 3600) {
                $m = floor($seconds / 60);
                return ['label' => $m . ' menit', 'level' => 'success'];
            } elseif ($seconds < 86400) {
                $h = floor($seconds / 3600);
                return ['label' => $h . ' jam', 'level' => 'warning'];            // kuning
            } elseif ($seconds < 259200) { // < 3 hari
                $d = floor($seconds / 86400);
                return ['label' => $d . ' hari', 'level' => 'warning'];
            } else {
                $d = floor($seconds / 86400);
                return ['label' => $d . ' hari', 'level' => 'danger'];            // merah
            }
        }

            public function create(Request $request)
        {
            $user       = Auth::user();
            $userDepts  = DB::table('user_dept')->where('username', $user->username)->pluck('dept')->toArray();
            $privileged = $user->hasAnyRole(['Superuser','accounting','finance']);

            $data['title']    = "Create $this->title";
            $data['subtitle'] = "Create $this->title";
            $data['oEdit']    = false;

          // Location From: gudang milik dept user + gudang umum (011), privileged -> semua
$data['locationsFrom'] = DB::table('stock_location_master')
    ->whereNotIn('location_code', ['038', '039'])   // ← TAMBAH INI
    ->when(!$privileged, function ($q) use ($userDepts) {
        $q->where(function ($sub) use ($userDepts) {
            $sub->whereIn('dept_code', $userDepts)
                ->orWhere('location_code', '011');
        });
    })
    ->orderBy('location_name')
    ->get();

// Location To: semua gudang + sertakan article_type untuk filter di JS
$data['locationsTo'] = DB::table('stock_location_master')
    ->whereNotIn('location_code', ['038', '039'])   // ← TAMBAH INI
    ->orderBy('location_name')
    ->select(
        'location_code',
        'location_name',
        'dept_code',
        'location_type',
        'article_type'
    )
    ->get()
    ->map(function ($loc) {
        $loc->article_type = $this->parsePostgresArray($loc->article_type);
        return $loc;
    });

            $data['thirdParties'] = DB::table('third_party')->orderBy('nama')->get();

            return view("transfer/transferStock.create", $data);
        }

        public function createTransferProgrammatically(array $payload, bool $manageTransaction = true): array
    {
        $username     = Auth::user()->username;
        $trDate       = $payload['trDate']       ?? null;
        $locationCode = $payload['locationFrom'] ?? null;
        $locationTo   = $payload['locationTo']   ?? null;
        $note         = $payload['note']         ?? null;
        $penerima     = $payload['penerima']     ?? null;
        $refNumber    = $payload['refNumber']    ?? '';
        $articles     = $payload['articles']     ?? [];
        $poLeadCode   = $this->moduleCode;   // 'TRF'
        $status       = '1';

        if (!$trDate)       return ['success'=>false,'trNumber'=>null,'message'=>'Transfer Date harus diisi'];
        if (!$locationCode) return ['success'=>false,'trNumber'=>null,'message'=>'Location From harus dipilih'];
        if (!$locationTo)   return ['success'=>false,'trNumber'=>null,'message'=>'Location To harus dipilih'];
        if ($locationTo === $locationCode)
                            return ['success'=>false,'trNumber'=>null,'message'=>'Location From dan Location To tidak boleh sama'];
        if (empty($articles)) return ['success'=>false,'trNumber'=>null,'message'=>'Artikel harus diisi'];

        $locToType   = DB::table('stock_location_master')->where('location_code', $locationTo)->value('location_type');
        $trType      = ($locToType === 'booth') ? 'SUPPLY' : 'TRANSFER';
        $approveDept = DB::table('stock_location_master')->where('location_code', $locationTo)->value('dept_code');

        $runner = function () use (
            $poLeadCode, $trDate, $locationCode, $locationTo, $status, $refNumber,
            $penerima, $note, $trType, $approveDept, $articles, $username
        ) {
            DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$poLeadCode]);

            AppHelpers::resetCode($poLeadCode);
            $trNumber = $this->getLastCode($poLeadCode, $trDate, $username);

            DB::table('transfer_stock_hdr')->insert([
                'tr_number'    => $trNumber,
                'ref_number'   => $refNumber,
                'tr_date'      => $trDate,
                'status'       => $status,
                'penerima'     => $penerima,
                'note'         => $note,
                'tr_type'      => $trType,
                'location_from'=> $locationCode,
                'location_to'  => $locationTo,
                'approve_dept' => $approveDept,
                'created_by'   => $username,
                'updated_by'   => $username,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);

            $dataSet = [];
            foreach ($articles as $val) {
                $ac  = is_array($val) ? ($val['article_code'] ?? null) : ($val->article_code ?? null);
                $qty = is_array($val) ? ($val['qty']  ?? 0)    : ($val->qty  ?? 0);
                $uom = is_array($val) ? ($val['uom']  ?? null) : ($val->uom  ?? null);
                $nt  = is_array($val) ? ($val['note'] ?? null) : ($val->note ?? null);

                $dataSet[] = [
                    'tr_number'    => $trNumber,
                    'article_code' => $ac,
                    'qty'          => $qty,
                    'uom'          => $uom,
                    'note'         => $nt,
                    'created_by'   => $username,
                    'updated_by'   => $username,
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ];
            }
            DB::table('transfer_stock_det')->insert($dataSet);

            $postResult = $this->processPosting($trNumber, $username);
            if (!$postResult['success']) {
                $msg = is_array($postResult['message']) ? implode(' | ', $postResult['message']) : $postResult['message'];
                throw new \RuntimeException($msg);
            }

            \LogActivity::addToLog("Save $this->title", "username: $username Status Save $trNumber (auto) is successfully saved");
            return $trNumber;
        };

        if ($manageTransaction) {
            DB::beginTransaction();
            try {
                $trNumber = $runner();
                DB::commit();
                return ['success'=>true,'trNumber'=>$trNumber,'message'=>"Transfer $trNumber berhasil dibuat"];
            } catch (\Throwable $e) {
                DB::rollBack();
                return ['success'=>false,'trNumber'=>null,'message'=>$e->getMessage()];
            }
        }

        $trNumber = $runner();
        return ['success'=>true,'trNumber'=>$trNumber,'message'=>"Transfer $trNumber berhasil dibuat"];
    }

    public function store(Request $request)
    {
        $username = Auth::user()->username;
        $title    = "Save $this->title";

        $payload = [
            'trDate'       => $request->trDate,
            'locationFrom' => $request->locationFrom,
            'locationTo'   => $request->locationTo,
            'note'         => $request->note,
            'penerima'     => $request->penerima,
            'articles'     => json_decode($request->articles, true) ?? [],
        ];

        $result = $this->createTransferProgrammatically($payload, true);

        if (!$result['success']) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>(array) $result['message'],'alert'=>'error']);
        }

        $trNumber = $result['trNumber'];
        $message  = "$title $trNumber is successfully saved";
        return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','trNumber'=>$trNumber,'oEdit'=>true]);
    }

        public function store1(Request $request)
        {
            $username     = Auth::user()->username;
            $articles     = json_decode($request->articles);
            $trDate       = $request->trDate;
            $trType       = $this->moduleCode;
            $note         = $request->note;
            $status       = '1';
            $poLeadCode   = $trType;
            $penerima = $request->penerima;
            $locationCode = $request->locationFrom;
            $locationTo   = $request->locationTo;

            $title = "Save $this->title";

            // ---- Validasi dasar ----
            $errors = [];
            if (!$trDate)        $errors[] = "Transfer Date harus diisi";
            if (!$locationCode)  $errors[] = "Location From harus dipilih";
            if (!$locationTo)    $errors[] = "Location To harus dipilih";
            if ($locationTo && $locationCode && $locationTo === $locationCode)
                                $errors[] = "Location From dan Location To tidak boleh sama";
            if (empty($articles)) $errors[] = "Artikel harus diisi";

            if ($errors) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
            }

            // ---- Cek location_type dari & tujuan ----
            $locFromType = DB::table('stock_location_master')
                ->where('location_code', $locationCode)
                ->value('location_type');

            $locToType = DB::table('stock_location_master')
                ->where('location_code', $locationTo)
                ->value('location_type');

        // ---- Tentukan tr_type ----
        if ($locToType === 'booth') {
            $trType = 'SUPPLY';
        } else {
            $trType = 'TRANSFER';
        }
        // ---- Validasi stok ----
            // Hanya gudang Consumable (006) yang divalidasi ketat, gudang lain boleh over-stock
        //  $strictStockLocation = '006';

            //$overStock = [];
            //foreach ($articles as $val) {
            //  $onhand = DB::table('warehouse_stock')
                //    ->where('article_code', $val->article_code)
                //  ->where('location_number', $locationCode)
                    //->sum('article_qty');

                //$reserved = DB::table('transfer_stock_det as d')
                //  ->join('transfer_stock_hdr as h','h.tr_number','=','d.tr_number')
                // ->where('d.article_code', $val->article_code)
                // ->where('h.location_from', $locationCode)
                    //->whereIn('h.status', ['1','2','3'])
                // ->sum(DB::raw("d.qty * coalesce(uom_conversion(d.uom,(select uom from article where article_code = d.article_code)),1)"));

                //$available = $onhand - $reserved;

                //$qtyBase = DB::selectOne(
                //  "select ? * coalesce(uom_conversion(?, (select uom from article where article_code = ?)),1) as q",
                // [$val->qty, $val->uom, $val->article_code]
                //)->q;

                //if ($locationCode === $strictStockLocation && $qtyBase > $available) {
                //  $overStock[] = "Qty {$val->article_code} ($qtyBase) melebihi stok available ($available) di gudang $locationCode";
                //}
            //}
            //if ($overStock) {
            //  return response()->json(['status'=>0,'title'=>$title,'message'=>$overStock,'alert'=>'error']);
            //}

            // ---- Snapshot dept approver ----
            $approveDept = DB::table('stock_location_master')
                ->where('location_code', $locationTo)
                ->value('dept_code');


            DB::beginTransaction();

            try {
                   DB::select("SELECT pg_advisory_xact_lock(hashtext(?))", [$poLeadCode]);
            
            $hasilUpdate = AppHelpers::resetCode($poLeadCode);
        $trNumber = $this->getLastCode(
        $poLeadCode,
        $trDate,
        Auth::user()->username
    );
    
                DB::table('transfer_stock_hdr')->insert([
                    'tr_number'    => $trNumber,
                    'ref_number'   => '',
                    'tr_date'      => $trDate,
                    'status'       => $status,
                    'penerima'     => $penerima,
                    'note'         => $note,
                    'tr_type'      => $trType,       // ← supply / return / mutasi
                    'location_from'=> $locationCode,
                    'location_to'  => $locationTo,
                    'approve_dept' => $approveDept,
                    'created_by'   => $username,
                    'updated_by'   => $username,
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

                $dataSet = [];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        'tr_number'    => $trNumber,
                        'article_code' => $val->article_code,
                        'qty'          => $val->qty,
                        'uom'          => $val->uom,
                        'note'         => $val->note,
                        'created_by'   => $username,
                        'updated_by'   => $username,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ];
                }

                DB::table('transfer_stock_det')->insert($dataSet);

                // ===== LANGSUNG POSTING =====
                $postResult = $this->processPosting($trNumber, $username);
                if (!$postResult['success']) {
                    DB::rollBack();
                    return response()->json(['status'=>0,'title'=>$title,'message'=>(array) $postResult['message'],'alert'=>'error']);
                }


                DB::commit();
                $message = "$title $trNumber is successfully saved";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','trNumber'=>$trNumber,'oEdit'=>true]);

            } catch (\Exception $e) {
                DB::rollBack();
                $message = "$title is failed to save";
                \LogActivity::addToLog($title, "username: $username Status $message - ".$e->getMessage());
                return response()->json(['status'=>0,'title'=>$title,'message'=>[$message],'alert'=>'error']);
            }
        }


        public function posting(Request $request)
    {
        $user     = Auth::user();
        $username = $user->username;
        $id       = Crypt::decryptString($request->id);
        $title    = "Posting $this->title";

        $hdrQ = DB::table('transfer_stock_hdr')->where('id', $id)->first();

        if (!$hdrQ) {
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => 'Data tidak ditemukan']);
        }
        if ($hdrQ->status == '4') {
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => "$title gagal: sudah diposting"]);
        }
        if ($hdrQ->status == '5') {
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => "$title gagal: sudah dicancel"]);
        }
        if (!($user->hasAnyRole(['Superuser', 'accounting']) || $user->can('transferOut-posting'))) {
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => 'Anda tidak berwenang posting']);
        }

        $trNumber = $hdrQ->tr_number;

        $rowAffected = DB::table('transfer_stock_hdr')
            ->where('tr_number', $trNumber)
            ->update([
                'status'     => '4',
                'authorized_by' => $username,
                'authorized_at' => date('Y-m-d H:i:s'),
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if ($rowAffected) {
            $message = "$title $trNumber Successfully Posted";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);
        }

        $message = "$title $trNumber Failed to Posted";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
    }

        private function reverseStock(object $hdrQ, string $username, string $reasonLabel): array
    {
        try {
            $lines = $this->resolveTransferLines($hdrQ);
        } catch (\RuntimeException $e) {
            return ['success' => false, 'message' => [$e->getMessage()]];
        }

        $trNumber     = $hdrQ->tr_number;
        $locationFrom = $hdrQ->location_from;
        $locationTo   = $hdrQ->location_to;
        $baseType     = ($hdrQ->tr_type === 'SUPPLY') ? 'SUPPLY' : 'TRANSFER';

        // Resolusi lokasi AKUNTANSI — WAJIB sama persis dengan processPosting
        $stockFrom = $this->getStockLocation($locationFrom);
        $stockTo   = $this->getStockLocation($locationTo);

        // Hapus movement asli
        DB::table('warehouse_movement')
            ->where('movement_transnno', $trNumber)
            ->where('movement_type', $baseType)
            ->delete();

        // Recalculate dari lokasi pool yang benar
        $affected = [];
        foreach (array_merge($lines['out'], $lines['in']) as $line) {
            $affected[$line['article_code'].'|'.$stockFrom] =
                ['article_code' => $line['article_code'], 'location' => $stockFrom];
            $affected[$line['article_code'].'|'.$stockTo] =
                ['article_code' => $line['article_code'], 'location' => $stockTo];
        }

        foreach ($affected as $a) {
            $this->recalculateMovementAndStock(
                $a['article_code'],
                $a['location'],
                $hdrQ->tr_date   // sudah DD-MM-YYYY, JANGAN dikonversi
            );
        }

        return ['success' => true, 'message' => "Stock $trNumber berhasil di-reverse"];
    }

       // ===== HELPER METHODS =====

    private function getAvgPrice(string $articleCode, string $location): float
    {
        return (float) DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->value('avg_price') ?? 0;
    }

    // Cache resolusi lokasi dalam satu request
    private array $stockLocationCache = [];

    /**
     * Resolusi lokasi stok akuntansi.
     * Jika lokasi punya parent (mis. 010A parent = 012) → stok bergerak di parent.
     * Jika tidak → stok bergerak di lokasi itu sendiri.
     */
    private function getStockLocation(string $locationCode): string
    {
        if (array_key_exists($locationCode, $this->stockLocationCache)) {
            return $this->stockLocationCache[$locationCode];
        }

        $parent = DB::table('stock_location_master')
            ->where('location_code', $locationCode)
            ->value('parent_location');

        return $this->stockLocationCache[$locationCode] = ($parent ?: $locationCode);
    }

    /**
     * Pastikan baris warehouse_stock ada untuk kombinasi site/article/location.
     */
    private function ensureStockRow(string $articleCode, string $location, ?string $deptCode, ?string $uom): void
{
    $exists = DB::table('warehouse_stock')
        ->where('site_code', $this->siteCode)
        ->where('article_code', $articleCode)
        ->where('location_number', $location)
        ->exists();

    if (!$exists) {
        DB::table('warehouse_stock')->insert([
            'site_code'       => $this->siteCode,
            'article_code'    => $articleCode,
            'location_number' => $location,
            'dept_code'       => $deptCode ?? '',
            'uom'             => $uom ?? 'PCS',
            'article_qty'     => 0,
        ]);
    }
}

    private function stockQuery(string $articleCode, string $location)
    {
        return DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location);
    }

    private function kurangiStock(string $articleCode, string $location, ?string $deptCode, ?string $uom, float $qty): void
{
    $this->ensureStockRow($articleCode, $location, $deptCode, $uom);

    $this->stockQuery($articleCode, $location)
        ->update(['article_qty' => DB::raw('coalesce(article_qty,0) - ' . $qty)]);
}

    private function tambahStock(string $articleCode, string $location, ?string $deptCode, ?string $uom, float $qtyMasuk, float $hargaMasuk): void
{
    $this->ensureStockRow($articleCode, $location, $deptCode, $uom);

    $current = $this->stockQuery($articleCode, $location)
        ->select(
            DB::raw('coalesce(article_qty,0) as qty_lama'),
            DB::raw('coalesce(avg_price,0) as avg_lama')
        )
        ->first();

    $qtyLama = (float) $current->qty_lama;
    $avgLama = (float) $current->avg_lama;
    $qtyBaru = $qtyLama + $qtyMasuk;
    $avgBaru = $qtyBaru > 0
        ? (($qtyLama * $avgLama) + ($qtyMasuk * $hargaMasuk)) / $qtyBaru
        : $avgLama;

    $this->stockQuery($articleCode, $location)
        ->update([
            'article_qty' => DB::raw('coalesce(article_qty,0) + ' . $qtyMasuk),
            'avg_price'   => $avgBaru,
        ]);
}

    // ===== tambah stock TANPA hitung ulang avg_price (untuk reverse/cancel) =====
    private function tambahStockTanpaAvg(string $articleCode, string $location, ?string $deptCode, ?string $uom, float $qty): void
{
    $this->ensureStockRow($articleCode, $location, $deptCode, $uom);

    $this->stockQuery($articleCode, $location)
        ->update(['article_qty' => DB::raw('coalesce(article_qty,0) + ' . $qty)]);
}

/**
     * Inti cancel transfer stock: reverse stok + movement, set status 5.
     * @param bool $manageTransaction  false = ikut transaksi pemanggil.
     * @param bool $enforceAuth        false = lewati cek role (dipakai pemanggil internal spt AFG).
     */
    public function cancelTransferProgrammatically(string $trNumber, string $reasonLabel = 'Cancel', bool $manageTransaction = true, bool $enforceAuth = true): array
    {
        $user     = Auth::user();
        $username = $user->username;
        $title    = "Cancel $this->title";

        $hdrQ = DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->first();
        if (!$hdrQ) {
            return ['success'=>false,'message'=>'Data tidak ditemukan'];
        }
        if ($hdrQ->status == '5') {
            return ['success'=>true,'message'=>"$trNumber sudah dicancel"]; // idempotent
        }

        if ($enforceAuth) {
            $isCreator = ($hdrQ->created_by === $username);
            if ($hdrQ->status == '4') {
                if (!($user->hasAnyRole(['Superuser','accounting']) || $user->can('transferOut-posting'))) {
                    return ['success'=>false,'message'=>'Anda tidak berwenang cancel transfer yang sudah diposting'];
                }
            } else {
                if (!($isCreator || $user->hasAnyRole(['Superuser','accounting']))) {
                    return ['success'=>false,'message'=>'Anda tidak berwenang cancel transfer ini'];
                }
            }
        }

        $reason = "($reasonLabel by $username)";

        $runner = function () use ($hdrQ, $trNumber, $username, $reason) {
            $reverse = $this->reverseStock($hdrQ, $username, 'Cancel');
            if (!$reverse['success']) {
                $msg = is_array($reverse['message']) ? implode(' | ', $reverse['message']) : $reverse['message'];
                throw new \RuntimeException($msg);
            }

            $newNote = trim(($hdrQ->note ?? '') . ';' . $reason);
            DB::table('transfer_stock_hdr')
                ->where('tr_number', $trNumber)
                ->update([
                    'status'     => '5',
                    'note'       => $newNote,
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            \LogActivity::addToLog("Cancel $this->title", "username: $username Status Cancel $trNumber Successfully Canceled");
        };

        if ($manageTransaction) {
            DB::beginTransaction();
            try {
                $runner();
                DB::commit();
                return ['success'=>true,'message'=>"$trNumber Successfully Canceled"];
            } catch (\Throwable $e) {
                DB::rollBack();
                return ['success'=>false,'message'=>$e->getMessage()];
            }
        }

        $runner();
        return ['success'=>true,'message'=>"$trNumber Successfully Canceled"];
    }

    public function cancel(Request $request)
    {
        $id    = Crypt::decryptString($request->id);
        $title = "Cancel $this->title";

        $trNumber = DB::table('transfer_stock_hdr')->where('id', $id)->value('tr_number');
        if (!$trNumber) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
        }

        $res = $this->cancelTransferProgrammatically($trNumber, 'Cancel', true, true);
        if (!$res['success']) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>(array)$res['message'],'alert'=>'warning']);
        }
        return response()->json(['status'=>1,'title'=>$title,'message'=>$res['message'],'alert'=>'success']);
    }

         public function cancel1(Request $request)
{
    $user     = Auth::user();
    $username = $user->username;
    $id       = Crypt::decryptString($request->id);
    $title    = "Cancel $this->title";

    $hdrQ = DB::table('transfer_stock_hdr')->where('id', $id)->first();
    if (!$hdrQ) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
    }
    if ($hdrQ->status == '5') {
        return response()->json(['status'=>0,'title'=>$title,'message'=>["$title gagal: sudah dicancel"],'alert'=>'warning']);
    }

    $trNumber  = $hdrQ->tr_number;
    $isCreator = ($hdrQ->created_by === $username);

    // Otoritas: status 4 (POSTED) butuh super/acc, selain itu cukup pembuat atau super/acc
    if ($hdrQ->status == '4') {
        if (!($user->hasAnyRole(['Superuser','accounting']) || $user->can('transferOut-posting'))) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Anda tidak berwenang cancel transfer yang sudah diposting'],'alert'=>'warning']);
        }
    } else {
        if (!($isCreator || $user->hasAnyRole(['Superuser','accounting']))) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Anda tidak berwenang cancel transfer ini'],'alert'=>'warning']);
        }
    }

    $reason = "(Cancel by $username)";

    DB::beginTransaction();
    try {
        // Reverse stok & movement (semua status non-cancel sudah punya efek stok)
        $reverse = $this->reverseStock($hdrQ, $username, 'Cancel');
        if (!$reverse['success']) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>$title,'message'=>(array) $reverse['message'],'alert'=>'warning']);
        }

        $newNote = trim(($hdrQ->note ?? '') . ';' . $reason);

    DB::table('transfer_stock_hdr')
        ->where('tr_number', $trNumber)
        ->update([
            'status'     => '5',
            'note'       => $newNote,          // ← aman, tanpa DB::raw/interpolasi
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::commit();
        $message = "$title $trNumber Successfully Canceled";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success']);

    } catch (\Exception $e) {
        DB::rollBack();
        $message = "$title $trNumber Failed: " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status'=>0,'title'=>$title,'message'=>[$message],'alert'=>'error']);
    }
}

            public function show(Request $request)
        {
            $id       = Crypt::decryptString($request->id);
            $username = Auth::user()->username;

            $data['title']    = "Detail $this->title";
            $data['subtitle'] = "Detail $this->title";

           $data['header'] = DB::table('transfer_stock_hdr')
                ->leftJoin('stock_location_master as locFrom', 'locFrom.location_code', '=', 'transfer_stock_hdr.location_from')
                ->leftJoin('stock_location_master as locTo',   'locTo.location_code',   '=', 'transfer_stock_hdr.location_to')
                ->leftJoin('users as uCreate', 'uCreate.username', '=', 'transfer_stock_hdr.created_by')
                ->leftJoin('users as uAuth',   'uAuth.username',   '=', 'transfer_stock_hdr.authorized_by')
                ->where('transfer_stock_hdr.id', $id)
                ->select(
                    'transfer_stock_hdr.*',
                    'locFrom.location_name',
                    'locTo.location_name as location_name_to',
                    'uCreate.name as created_name',
                    'uAuth.name as authorized_name',
                    DB::raw('(select count(*) from transfer_stock_det where tr_number = transfer_stock_hdr.tr_number) as sum_row'),
                    DB::raw('(select sum(qty)   from transfer_stock_det where tr_number = transfer_stock_hdr.tr_number) as sum_qty')
                )
                ->first();

            if (!$data['header']) {
                return redirect()->back()->with(['title'=>'Detail','alert'=>'warning','message'=>'Data tidak ditemukan']);
            }

            $trNumber = $data['header']->tr_number;

            $data['details'] = DB::table('transfer_stock_det')
                ->leftJoin('article', 'article.article_code', '=', 'transfer_stock_det.article_code')
                ->where('transfer_stock_det.tr_number', $trNumber)
                ->select(
                    'transfer_stock_det.*',
                    'article.article_alternative_code',
                    'article.article_desc',
                    'article.min_package'
                )
                ->orderBy('transfer_stock_det.id')
                ->get();

               $data['revisions'] = DB::table('transfer_stock_hdr_hist')
            ->leftJoin('users', 'users.username', '=', 'transfer_stock_hdr_hist.revised_by')
            ->leftJoin('stock_location_master as locFrom', 'locFrom.location_code', '=', 'transfer_stock_hdr_hist.location_from')
            ->leftJoin('stock_location_master as locTo',   'locTo.location_code',   '=', 'transfer_stock_hdr_hist.location_to')
            ->where('transfer_stock_hdr_hist.tr_number', $trNumber)
            ->select(
                'transfer_stock_hdr_hist.*',
                'users.name as revised_name',
                'locFrom.location_name as location_name',
                'locTo.location_name as location_name_to'
            )
            ->orderBy('num_revision')
            ->get();

        $data['revisionDetails'] = DB::table('transfer_stock_det_hist')
            ->leftJoin('article', 'article.article_code', '=', 'transfer_stock_det_hist.article_code')
            ->where('transfer_stock_det_hist.tr_number', $trNumber)
            ->select(
                'transfer_stock_det_hist.*',
                'article.article_alternative_code',
                'article.article_desc',
                'article.min_package'
            )
            ->orderBy('num_revision')
            ->orderBy('transfer_stock_det_hist.id')
            ->get();

            // ===== Susun diff: tiap versi dibandingkan dengan versi sebelumnya =====
        $revs = $data['revisions'];
        $diffs = [];

        // Revisi N vs Revisi N-1
        foreach ($revs as $rev) {
            $prev = $revs->firstWhere('num_revision', $rev->num_revision - 1);
            if (!$prev) {
                $diffs[$rev->num_revision] = null;   // Revisi 1 = kondisi awal
                continue;
            }
            $diffs[$rev->num_revision] = $this->buildDiff(
                $prev,
                $rev,
                $data['revisionDetails']->where('num_revision', $prev->num_revision),
                $data['revisionDetails']->where('num_revision', $rev->num_revision)
            );
        }

        // Current vs revisi tertinggi
        $lastRev = $revs->sortByDesc('num_revision')->first();
        $diffs['current'] = $lastRev
            ? $this->buildDiff(
                $lastRev,
                $data['header'],
                $data['revisionDetails']->where('num_revision', $lastRev->num_revision),
                $data['details']
              )
            : null;

        $data['diffs'] = $diffs;

            $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $trNumber, $username);
            $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $trNumber, $username);

            $statusTr        = ['NEW', 'VALIDATED', 'APPROVED', 'POSTED', 'CANCELED'];
            $data['statusTr'] = $statusTr[$data['header']->status - 1];

            return view("transfer/transferStock.show", $data);
        }

            public function showEdit($key, $editReason = null)
        {
            $id       = Crypt::decryptString($key);
            $username = Auth::user()->username;
            $user     = Auth::user();
            $userDepts  = DB::table('user_dept')->where('username', $username)->pluck('dept')->toArray();
            $privileged = $user->hasAnyRole(['Superuser','accounting','finance']);

            $data['title']    = "Edit $this->title";
            $data['subtitle'] = "Edit $this->title";
            $data['oEdit']    = true;

            $data['header'] = DB::table('transfer_stock_hdr')->where('id', $id)->first();

            if (!$data['header']) {
                return redirect()->back()->with(['title'=>'Edit','alert'=>'warning','message'=>'Data tidak ditemukan']);
            }

            $trNumber = $data['header']->tr_number;

            $data['details'] = DB::table('transfer_stock_det')
                ->leftJoin('article','article.article_code','=','transfer_stock_det.article_code')
                ->where('transfer_stock_det.tr_number', $trNumber)
                ->select(
                    'transfer_stock_det.*',
                    'article.article_alternative_code',
                    'article.article_desc',
                    DB::raw("(select string_agg(unit_to,',' order by unit_from) from uom_con_v2 where article_code = transfer_stock_det.article_code) as uom_member")
                )
                ->orderBy('transfer_stock_det.id')
                ->get();

       $data['locationsFrom'] = DB::table('stock_location_master')
    ->whereNotIn('location_code', ['038', '039'])   // ← TAMBAH INI
    ->when(!$privileged, function ($q) use ($userDepts) {
        $q->where(function ($sub) use ($userDepts) {
            $sub->whereIn('dept_code', $userDepts)
                ->orWhere('location_code', '011');
        });
    })
    ->orderBy('location_name')
    ->get();

$data['locationsTo'] = DB::table('stock_location_master')
    ->whereNotIn('location_code', ['038', '039'])   // ← TAMBAH INI
    ->orderBy('location_name')
    ->select(
        'location_code',
        'location_name',
        'dept_code',
        'location_type',
        'article_type'
    )
    ->get()
    ->map(function ($loc) {
        $loc->article_type = $this->parsePostgresArray($loc->article_type);
        return $loc;
    });

            $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $trNumber, $username);
            $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $trNumber, $username);

            $statusTr         = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
            $data['statusTr'] = $statusTr[$data['header']->status - 1];
            $data['editReason'] = $editReason;          // ← TAMBAH

            return view("transfer/transferStock.edit", $data);
        }

            public function edit(Request $request)
            {
                return $this->showEdit($request->id, $request->editReason);
            }

            /**
     * Bandingkan dua versi dokumen (header + detail).
     * Return: ['header'=>[...], 'added'=>[...], 'removed'=>[...], 'changed'=>[...], 'has'=>bool]
     */
    private function buildDiff($oldHdr, $newHdr, $oldDet, $newDet): array
    {
        $headerDiff = [];
        $fields = [
            'tr_date'       => 'Transfer Date',
            'penerima'      => 'Penerima',
            'location_name' => 'Location From',
            'location_name_to' => 'Location To',
            'note'          => 'Notes',
        ];

        foreach ($fields as $col => $label) {
            $o = trim((string) ($oldHdr->$col ?? ''));
            $n = trim((string) ($newHdr->$col ?? ''));
            if ($o !== $n) {
                $headerDiff[] = ['label' => $label, 'old' => $o ?: '-', 'new' => $n ?: '-'];
            }
        }

        $oldMap = collect($oldDet)->keyBy('article_code');
        $newMap = collect($newDet)->keyBy('article_code');

        $added = $removed = $changed = [];

        foreach ($newMap as $code => $n) {
            if (!$oldMap->has($code)) {
                $added[] = $code;
                continue;
            }
            $o = $oldMap[$code];
            $c = [];
            if ((float) $o->qty !== (float) $n->qty) {
                $c['qty'] = ['old' => (float) $o->qty, 'new' => (float) $n->qty];
            }
            if (trim((string) $o->uom) !== trim((string) $n->uom)) {
                $c['uom'] = ['old' => $o->uom, 'new' => $n->uom];
            }
            if (trim((string) $o->note) !== trim((string) $n->note)) {
                $c['note'] = ['old' => $o->note ?: '-', 'new' => $n->note ?: '-'];
            }
            if (trim((string) ($o->fg_target ?? '')) !== trim((string) ($n->fg_target ?? ''))) {
                $c['fg_target'] = ['old' => $o->fg_target ?: '-', 'new' => $n->fg_target ?: '-'];
            }
            if ($c) $changed[$code] = $c;
        }

        foreach ($oldMap as $code => $o) {
            if (!$newMap->has($code)) $removed[$code] = $o;
        }

        return [
            'header'  => $headerDiff,
            'added'   => $added,
            'removed' => $removed,
            'changed' => $changed,
            'has'     => (bool) ($headerDiff || $added || $removed || $changed),
        ];
    }

            
public function update(Request $request)
{
    $user         = Auth::user();
    $username     = $user->username;
    $articles     = json_decode($request->articles);
    $trNumber     = $request->trNumber;
    $trDate       = $request->trDate;
    $note         = $request->note;
    $penerima     = $request->penerima;
    $locationCode = $request->locationFrom;
    $locationTo   = $request->locationTo;
    $editReason   = $request->editReason;

    $title = "Save $this->title";

    // ── Ambil header lama ──
    $hdr = DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->first();
    if (!$hdr) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
    }

    // ── GUARD: hanya NEW yang boleh diedit. POSTED harus Cancel. ──
    if ($hdr->status != '1') {
        $map = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $st  = $map[$hdr->status] ?? $hdr->status;
        $extra = ($hdr->status == '4') ? ' Lakukan Cancel dulu untuk mengoreksi.' : '';
        return response()->json([
            'status'=>0,'title'=>$title,
            'message'=>["Transfer berstatus $st, hanya dokumen NEW yang bisa diedit.$extra"],
            'alert'=>'error',
        ]);
    }

    // ── Validasi dasar ──
    $errors = [];
    if (!$trDate)       $errors[] = "Transfer Date harus diisi";
    if (!$locationCode) $errors[] = "Location From harus dipilih";
    if (!$locationTo)   $errors[] = "Location To harus dipilih";
    if ($locationTo && $locationCode && $locationTo === $locationCode)
                        $errors[] = "Location From dan Location To tidak boleh sama";
    if (empty($articles)) $errors[] = "Artikel harus diisi";
    if ($errors) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
    }

    // ── tr_type & approver (dihitung dari tujuan baru) ──
    $locToType   = DB::table('stock_location_master')->where('location_code', $locationTo)->value('location_type');
    $trType      = ($locToType === 'booth') ? 'SUPPLY' : 'TRANSFER';
    $approveDept = DB::table('stock_location_master')->where('location_code', $locationTo)->value('dept_code');

    DB::beginTransaction();
    try {
        // ── 0) Snapshot history sebelum diubah ──
        $rev = $this->snapshotHistory($hdr, $username, $editReason);

        // ── 1) REVERSE dulu, MASIH pakai header LAMA (tr_date & lokasi lama) ──
        //    Ini membersihkan seluruh movement dokumen + recalc dari tanggal LAMA.
        $reverse = $this->reverseStock($hdr, $username, 'Edit');
        if (!$reverse['success']) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>$title,'message'=>(array)$reverse['message'],'alert'=>'error']);
        }

        // ── 2) Sinkron detail (hapus-insert) ──
        DB::table('transfer_stock_det')->where('tr_number', $trNumber)->delete();
        foreach ($articles as $val) {
            DB::table('transfer_stock_det')->insert([
                'tr_number'    => $trNumber,
                'article_code' => $val->article_code,
                'qty'          => $val->qty,          // qty MENTAH (sesuai keputusanmu)
                'uom'          => $val->uom,
                'note'         => $val->note ?? null,
                'fg_target'    => $val->fg_target ?? null,
                'created_by'   => $username,
                'updated_by'   => $username,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        // ── 3) Update header. Status TETAP NEW. Tanpa authorized_*. ──
        DB::table('transfer_stock_hdr')
            ->where('tr_number', $trNumber)
            ->update([
                'tr_date'       => $trDate,
                'tr_type'       => $trType,
                'status'        => '1',           // ← tetap NEW
                'num_revision'  => $rev,
                'note'          => $note,
                'penerima'      => $penerima,
                'location_from' => $locationCode,
                'location_to'   => $locationTo,
                'approve_dept'  => $approveDept,
                'updated_by'    => $username,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

        // ── 4) REPOST via jalur yang SAMA dengan store() (tr_date BARU) ──
        //    processPosting membaca header fresh → resolveTransferLines (FG→037),
        //    movementDesc, harga terkini — semua konsisten dengan store.
        $postResult = $this->processPosting($trNumber, $username);
        if (!$postResult['success']) {
            DB::rollBack();
            return response()->json(['status'=>0,'title'=>$title,'message'=>(array)$postResult['message'],'alert'=>'error']);
        }

        DB::commit();

        $message = "$title $trNumber is successfully updated";
        \LogActivity::addToLog($title, "username: $username Status $message");

        return response()->json([
            'status'       => 1,
            'title'        => $title,
            'message'      => $message,
            'alert'        => 'success',
            'trNumber'     => $trNumber,
            'oEdit'        => true,
            'redirect_url' => route('transferStock.show', ['id' => Crypt::encryptString($hdr->id)]),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        $message = "$title $trNumber is failed to update";
        \LogActivity::addToLog($title, "username: $username Status $message - " . $e->getMessage());
        return response()->json(['status'=>0,'title'=>$title,'message'=>[$message, $e->getMessage()],'alert'=>'error']);
    }
}


// ==============================================================
// [2] METHOD BARU: recalculateMovementAndStock()
//     Recalculate last_qty semua movement setelah tr_date
//     untuk artikel + lokasi tertentu, lalu update warehouse_stock
// ==============================================================
 
/**
 * Recalculate last_qty di warehouse_movement mulai dari tanggal tertentu,
 * lalu update warehouse_stock ke saldo terkini.
 *
 * @param string $articleCode
 * @param string $location
 * @param string $fromDate  format Y-m-d (tanggal tr_date yang diedit)
 */
private function recalculateMovementAndStock(string $articleCode, string $location, string $fromDate): void
{
    // ── Normalisasi $fromDate ke YYYY-MM-DD apapun format masuknya ──
    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $fromDate)) {
        $fromDate = \Carbon\Carbon::createFromFormat('d-m-Y', $fromDate)->format('Y-m-d');
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
        // sudah benar
    } else {
        $fromDate = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
    }

    $balanceBefore = (float) DB::selectOne(
        "SELECT get_last_qty_new(?, TO_CHAR(TO_DATE(?, 'YYYY-MM-DD') - INTERVAL '1 day', 'YYYY-MM-DD'), ?, ?) AS bal",
        [$articleCode, $fromDate, $this->siteCode, $location]
    )->bal;

    $movements = DB::table('warehouse_movement')
        ->where('artikel_code', $articleCode)
        ->where('location_number', $location)
        ->where('site_code', $this->siteCode)
        ->where(DB::raw("TO_DATE(movement_date, 'DD-MM-YYYY')"), '>=',
            DB::raw("TO_DATE('$fromDate', 'YYYY-MM-DD')"))   // ← FIX: dari DD-MM-YYYY jadi YYYY-MM-DD
        ->whereNotIn('movement_type', ['RETURN-CANCEL', 'RETURN-REVERSE'])
        ->where('movement_type', 'NOT LIKE', 'CANCEL %')
        ->where('movement_type', 'NOT LIKE', 'DELETE%')
        ->where('movement_type', 'NOT LIKE', 'REVISI %')
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('stock_adjustment_hdr')
              ->whereColumn('stock_adjustment_hdr.adj_code', 'warehouse_movement.movement_transnno')
              ->where('stock_adjustment_hdr.adj_type', 'OPENING BALANCE');
        })
        ->orderBy(DB::raw("TO_DATE(movement_date, 'DD-MM-YYYY')"), 'asc')
        ->orderBy('movement_code', 'asc')
        ->select('movement_code', 'movement_min', 'movement_plus')
        ->get();

    if ($movements->isEmpty()) {
        $this->updateWarehouseStock($articleCode, $location, $balanceBefore);
        $this->recalculateAvgPrice($articleCode, $location);
        return;
    }

    $running = $balanceBefore;
    foreach ($movements as $mov) {
        $running = $running - (float)$mov->movement_min + (float)$mov->movement_plus;
        DB::table('warehouse_movement')
            ->where('movement_code', $mov->movement_code)
            ->update(['last_qty' => $running]);
    }

    $latestLastQty = (float) DB::table('warehouse_movement')
        ->where('artikel_code', $articleCode)
        ->where('location_number', $location)
        ->where('site_code', $this->siteCode)
        ->orderBy(DB::raw("TO_DATE(movement_date, 'DD-MM-YYYY')"), 'desc')
        ->orderBy('movement_code', 'desc')
        ->value('last_qty');

    $this->updateWarehouseStock($articleCode, $location, $latestLastQty);
    $this->recalculateAvgPrice($articleCode, $location);
}
 
 
// ==============================================================
// [3] METHOD BARU: updateWarehouseStock()
//     Update article_qty di warehouse_stock
// ==============================================================
 
private function updateWarehouseStock(string $articleCode, string $location, float $qty): void
{
    DB::table('warehouse_stock')
        ->where('site_code', $this->siteCode)
        ->where('article_code', $articleCode)
        ->where('location_number', $location)
        ->update(['article_qty' => $qty]);
}
 
 
// ==============================================================
// [4] METHOD BARU: toBaseQty()
//     Konversi qty dari UOM input ke UOM base artikel
// ==============================================================
 
private function toBaseQty(string $articleCode, float $qty, string $uom): float
{
    $result = DB::selectOne(
        "SELECT ? * COALESCE(uom_conversion(?, (SELECT uom FROM article WHERE article_code = ?)), 1) AS q",
        [$qty, $uom, $articleCode]
    );
    return (float) ($result->q ?? $qty);
}
 
 
// ==============================================================
// [5] METHOD BARU: getArticleDesc()
//     Ambil deskripsi artikel
// ==============================================================
 
private function getArticleDesc(string $articleCode): string
{
    return (string) DB::table('article')
        ->where('article_code', $articleCode)
        ->value('article_desc');
}


            public function approve(Request $request)
            {
                $username =  Auth::user()->username;
                $trNumber = $request->trNumber;
                $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$trNumber,$username);        
                $nextLevel = $statusLevelApproval[0]->next_level;
                $statusTso = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                        
                DB::beginTransaction();
                try {
                        $row_affected=DB::table('transfer_hdr')
                        ->where('tr_number',$trNumber)
                        ->update(
                            [
                                'status' => $statusTso,
                                'updated_by' => Auth::user()->username,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );

                        if ($row_affected){
                            DB::table('approval_history')->insert([
                                'module_code' => $this->moduleCode,
                                'module_number' => $trNumber,
                                'username' => Auth::user()->username,
                                'approval_order' => $nextLevel,
                                'approval_date' => date('Y-m-d'),
                                'status' => 1,
                                'created_by' => Auth::user()->username,
                                'updated_by' => Auth::user()->username,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ]);
                        }
                        
                        DB::commit();
                        $title ="Approve $this->title";
                        $alert  ="success";
                        $message  = "$title $trNumber is successfully Approve-".$nextLevel;
                        \LogActivity::addToLog($title,"username: $username Status $message");
                        return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));

                } catch (Exception $e) {
                    DB::rollBack();
                    $title ="Approve $this->title";
                    $alert  ="warning";
                    $message  = "$title $trNumber is failed to Approve-".$nextLevel;
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
                }
            }

            public function destroy(Request $request)
        {
            $user     = Auth::user();
            $username = $user->username;
            $id       = Crypt::decryptString($request->id);
            $title    = "Delete $this->title";

            $hdrQ = DB::table('transfer_stock_hdr')->where('id', $id)->first();
            if (!$hdrQ) {
                return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Data tidak ditemukan']);
            }
            if ($hdrQ->status == '5') {
                return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>"$title gagal: sudah dicancel"]);
            }

            $trNumber  = $hdrQ->tr_number;
            $isCreator = ($hdrQ->created_by === $username);

            // Karena store langsung menjalankan stok/movement, SEMUA status (1..4)
            // sudah punya efek stok → selalu perlu reverse.
            // Status 4 butuh otoritas super/acc; status 1/2/3 cukup pembuat atau super/acc.
            if ($hdrQ->status == '4') {
                if (!($user->hasAnyRole(['Superuser','accounting']) || $user->can('transferOut-posting'))) {
                    return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Anda tidak berwenang menghapus transfer yang sudah diposting']);
                }
            } else {
                if (!($isCreator || $user->hasAnyRole(['Superuser','accounting']))) {
                    return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>'Anda tidak berwenang menghapus transfer ini']);
                }
            }

            $reason = "(Delete by $username)";

            DB::beginTransaction();
            try {
                // ===== Reverse stok & movement (untuk SEMUA status non-cancel) =====
                $reverse = $this->reverseStock($hdrQ, $username, 'Delete');
                if (!$reverse['success']) {
                    DB::rollBack();
                    return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>implode(' | ', (array) $reverse['message'])]);
                }

                // ===== Set status → CANCELED (5), data tetap ada =====
              $newNote = trim(($hdrQ->note ?? '') . ';' . $reason);

    DB::table('transfer_stock_hdr')
        ->where('tr_number', $trNumber)
        ->update([
            'status'     => '5',
            'note'       => $newNote,          // ← aman
            'updated_by' => $username,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

                DB::commit();
                $message = "$title $trNumber Successfully Canceled";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return redirect()->back()->with(['title'=>$title,'alert'=>'success','message'=>$message]);

            } catch (\Exception $e) {
                DB::rollBack();
                $message = "$title $trNumber Failed: " . $e->getMessage();
                \LogActivity::addToLog($title, "username: $username Status $message");
                return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>$message]);
            }
        }

            
            public function list(Request $request)
{
    $user       = Auth::user();
    $username   = $user->username;
    $userDepts  = DB::table('user_dept')->where('username', $username)->pluck('dept')->toArray();
    $privileged = $user->hasAnyRole(['Superuser', 'accounting', 'finance']);
    $canPosting = $user->hasAnyRole(['Superuser', 'accounting']) || $user->can('transferOut-posting');

    $searchTr     = strtolower((string) $request->searchTr);
    $searchStatus = $request->searchStatus;
    $searchType   = $request->searchType;
    $trDate       = $request->trDate;
    $transferFrom = $request->transferFrom;
    $transferTo   = $request->transferTo;

    // Format tanggal dd-mm-yyyy (sama seperti listDetail)
    $fromDate = "";
    $toDate   = "";
    if ($trDate) {
        $date = explode("to", $trDate);
        if (count($date) > 1) {
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate   = implode("/", array_reverse(explode("-", trim($date[1]))));
        } else {
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate   = $fromDate;
        }
    }

    $query = DB::table('transfer_stock_hdr')
        ->leftJoin('stock_location_master as locFrom', 'locFrom.location_code', '=', 'transfer_stock_hdr.location_from')
        ->leftJoin('stock_location_master as locTo',   'locTo.location_code',   '=', 'transfer_stock_hdr.location_to')
        ->where(function ($q) use ($searchTr, $searchStatus, $searchType, $trDate, $fromDate, $toDate, $transferFrom, $transferTo) {
            $searchTr     ? $q->where('transfer_stock_hdr.tr_number', 'ilike', '%' . $searchTr . '%') : '';
            $searchStatus ? $q->where('transfer_stock_hdr.status', $searchStatus) : '';
            $searchType   ? $q->where('transfer_stock_hdr.tr_type', $searchType) : '';
            $trDate       ? $q->whereBetween(DB::raw("to_date(transfer_stock_hdr.tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $transferFrom ? $q->where('transfer_stock_hdr.location_from', $transferFrom) : '';
            $transferTo   ? $q->where('transfer_stock_hdr.location_to', $transferTo) : '';
        });

    // Visibilitas: privileged lihat semua, selain itu hanya yg terkait dept/creator
    if (!$privileged) {
        $query->where(function ($q) use ($userDepts, $username) {
            $q->whereIn('locFrom.dept_code', $userDepts)
              ->orWhereIn('transfer_stock_hdr.approve_dept', $userDepts)
              ->orWhere('transfer_stock_hdr.created_by', $username);
        });
    }

    $query->select(
        'transfer_stock_hdr.id',
        'transfer_stock_hdr.tr_number',
        'transfer_stock_hdr.tr_date',
        'transfer_stock_hdr.tr_type',
        'transfer_stock_hdr.status',
        'transfer_stock_hdr.note',
        'transfer_stock_hdr.created_by',
        'transfer_stock_hdr.created_at',
        'transfer_stock_hdr.updated_by',
        'transfer_stock_hdr.updated_at',
        'locFrom.location_name as location_name',
        'locTo.location_name as location_name_to'
    )->orderBy('transfer_stock_hdr.created_at', 'desc');

    return DataTables::of($query)
        ->addColumn('action', function ($row) use ($username, $canPosting) {
            $encId     = Crypt::encryptString($row->id);
            $isCreator = ($row->created_by === $username);
            $st        = $row->status;

            $buttons  = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>';
            $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

            // DETAIL — selalu
            $buttons .= '<a href="' . route('transferStock.show', ['id' => $encId]) . '" class="dropdown-item">
                            <i data-feather="eye"></i><span>' . __('Detail') . '</span></a>';

            // EDIT — belum posted/canceled
            if (!in_array($st, ['4', '5'])) {
                $buttons .= '<a href="' . route('transferStock.edit', ['id' => $encId]) . '" class="dropdown-item">
                                <i data-feather="edit-2"></i><span>' . __('Edit') . '</span></a>';
            }

            // CANCEL — status 4 butuh otoritas khusus, selain itu cukup creator/privileged
            if ($st != '5' && ($isCreator || $canPosting)) {
                $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                                data-confirm='Batalkan Transfer ini?|Stok yang sudah dipindahkan akan dikembalikan.'
                                data-confirm-yes='document.getElementById(\"delete-form-{$row->id}\").submit();'
                                data-modal-id='{$row->id}'
                                data-url='" . route('transferStock.cancel', ['id' => $encId]) . "'>
                                <i data-feather='x-circle' class='feather-14-red'></i><span>" . __('Cancel') . "</span></a>";
            }

            // PRINT
            $buttons .= '<a href="' . route('transferStock.print', ['id' => $encId]) . '" target="_blank" class="dropdown-item">
                            <i data-feather="printer"></i><span>' . __('Print') . '</span></a>';

            $buttons .= '</div></div>';
            return $buttons;
        })
        ->editColumn('status', function ($row) {
            $badges   = ['badge-primary', 'badge-info', 'badge-warning', 'badge-success', 'badge-danger'];
            $statusTr = ['NEW', 'VALIDATED', 'APPROVED', 'POSTED', 'CANCELED'];
            $idx      = $row->status - 1;
            return "<div class='badge {$badges[$idx]}'>{$statusTr[$idx]}</div>";
        })
        ->rawColumns(['action', 'status'])
        ->make(true);
}

            public function listDetail(Request $request)
        {
            $user     = Auth::user();
            $userDepts = DB::table('user_dept')->where('username',$user->username)->pluck('dept')->toArray(); // <— SESUAIKAN
            $privileged = $user->hasAnyRole(['Superuser','accounting','finance']);                                  // <— SESUAIKAN

            $searchTr     = strtolower($request->searchTr);
            $searchStatus = $request->searchStatus;
            $trDate       = $request->trDate;
            $transferFrom = $request->transferFrom;
            $transferTo   = $request->transferTo;

            $fromDate = "";
            $toDate   = "";
            if ($trDate){
                $date = explode("to",$trDate);
                if(count($date) > 1){
                    $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                    $toDate   = implode("/", array_reverse(explode("-", trim($date[1]))));
                }else{
                    $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                    $toDate   = $fromDate;
                }
            }

            $query = DB::table('transfer_stock_det')
            ->leftJoin('transfer_stock_hdr','transfer_stock_hdr.tr_number','=','transfer_stock_det.tr_number')
            ->leftJoin('article','article.article_code','=','transfer_stock_det.article_code')
            ->leftJoin('uom','uom.code','=','transfer_stock_det.uom')
            ->leftJoin('stock_location_master as locFrom','locFrom.location_code','=','transfer_stock_hdr.location_from')
            ->leftJoin('stock_location_master as locTo','locTo.location_code','=','transfer_stock_hdr.location_to')
            ->where(function ($q) use ($searchTr,$searchStatus,$trDate,$fromDate,$toDate,$transferFrom,$transferTo) {
                $searchTr     ? $q->where('transfer_stock_det.tr_number','ilike','%'.$searchTr.'%') : '';
                $searchStatus ? $q->where('transfer_stock_hdr.status',$searchStatus) : '';
                $trDate       ? $q->whereBetween(DB::raw("to_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
                $transferTo   ? $q->where('transfer_stock_hdr.location_to',$transferTo) : '';
                $transferFrom ? $q->where('transfer_stock_hdr.location_from',$transferFrom) : '';
            });

            $query->where('transfer_stock_hdr.status', '<>', '5');

            if (!$privileged) {
                $query->where(function($q) use ($userDepts) {
                    $q->whereIn('locFrom.dept_code', $userDepts)             // <— SESUAIKAN kolom dept
                    ->orWhereIn('transfer_stock_hdr.approve_dept', $userDepts);
                });
            }

            $data = $query->select(
                'transfer_stock_hdr.tr_number'
                ,'transfer_stock_hdr.tr_date'
                ,'transfer_stock_hdr.status'
                ,'transfer_stock_hdr.created_by'
                ,'transfer_stock_hdr.created_at'
                ,'transfer_stock_hdr.updated_by'
                ,'transfer_stock_hdr.updated_at'
                ,'transfer_stock_det.id'
                ,'transfer_stock_det.qty'
                ,'transfer_stock_det.uom'
                ,'transfer_stock_det.note'
                ,'transfer_stock_det.article_code'
                ,'article.article_alternative_code'
                ,'article.article_desc'
                ,'uom.uom_group'
                ,'locFrom.location_name as location_name'        // Location From
                ,'locTo.location_name as location_name_to'       // Location To
                ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = transfer_stock_hdr.tr_number) as approval_by")
            )
            ->orderBy('transfer_stock_det.id')
            ->get();

            return Datatables::of($data)
            ->addColumn('status', function ($data) {
                $badges   = ['badge-primary','badge-info','badge-warning','badge-success','badge-danger','badge-dark','badge-secondary','badge-danger'];
                $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
                return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTr[$data->status - 1]."</div>";
            })
            ->rawColumns(['status'])
            ->make(true);
        }
            
            public function print(Request $request)
        {
            $id = Crypt::decryptString($request->id);

            $data['companies'] = DB::table('company')
                ->where('code', 'ASN')
                ->select('name as nama', 'address as alamat',
                    DB::raw('(select region_name from regions where region_code = city::integer) as kota'), 'tlp')
                ->first();

            $trHdr = DB::table('transfer_stock_hdr')
                ->leftJoin('stock_location_master as locFrom', 'locFrom.location_code', '=', 'transfer_stock_hdr.location_from')
                ->leftJoin('stock_location_master as locTo',   'locTo.location_code',   '=', 'transfer_stock_hdr.location_to')
                ->where('transfer_stock_hdr.id', $id)
                ->select(
                    'transfer_stock_hdr.*',
                    'locFrom.location_name as location_from_name',
                    'locTo.location_name as location_to_name'
                )
                ->first();

            if (!$trHdr) {
                return redirect()->back()->with(['title'=>'Print','alert'=>'warning','message'=>'Data tidak ditemukan']);
            }

            $trNumber = $trHdr->tr_number;

            $data['details'] = DB::table('transfer_stock_det')
                ->leftJoin('article', 'article.article_code', '=', 'transfer_stock_det.article_code')
                ->leftJoin('article as fgArt', 'fgArt.article_code', '=', 'transfer_stock_det.fg_target')
                ->where('transfer_stock_det.tr_number', $trNumber)
                ->select(
                    'transfer_stock_det.*',
                    'article.article_alternative_code',
                    'article.article_desc',
                    'fgArt.article_alternative_code as fg_alt_code',
                    'fgArt.article_desc as fg_desc'
                )
                ->orderBy('transfer_stock_det.id')
                ->get();

            $data['trNumber']      = $trNumber;
            $data['trDate']        = $trHdr->tr_date;
            $data['trType']        = $trHdr->tr_type;
            $data['locationFrom']  = $trHdr->location_from_name;
            $data['locationTo']    = $trHdr->location_to_name;
            $data['keterangan']    = $trHdr->note;
            $data['status']        = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'][$trHdr->status - 1];
            $data['createdBy']     = $trHdr->created_by;
            $data['no']            = 0;

            $data['approved'] = DB::table('approval_history')
                ->leftJoin('users', 'users.username', '=', 'approval_history.username')
                ->where('module_number', $trNumber)
                ->orderBy('approval_order', 'desc')
                ->value('users.name');

            view()->share($data);

            $pdf = PDF::loadView('transfer.transferStock.print');
            return $pdf->stream("$trNumber.pdf");
        }

            public function articleTso(Request $request)
            {
                $woCode = $request->tsoCode;
                $articles = DB::table('wo_det')
                ->where('wo_code',$woCode)
                ->where('so_code','<>','other')
                ->get();

                $dataSet = [];
                $randomCode = rand();
                foreach ($articles as $val) {
                    $dataSet[] = [
                        'code' => $randomCode,
                        'article_code' => $val->article_code,
                        //yang dihitung datanya cuma yang fresh yang repaint tidak motong chemical lagi 
                        //'qty' => $val->plan_qty_fresh+$val->plan_qty_repaint
                        'qty' => $val->plan_qty_fresh,
                        'uom' => 'PCS'
                    ];
                }

                DB::table('wo_detail_temp')->insert($dataSet);

                $data=DB::select("SELECT 
                article_code_det as article_code
                ,min_package 
                ,sum(qty_order * qty_bom) as total
                ,sum(qty_order * qty_bom) as grand_total
                ,uom_bom as uom 
                ,(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = a.uom_bom)
                from(
                select 
                bom_det.article_code as article_code_det
                ,wo_detail_temp.qty as qty_order
                ,wo_detail_temp.uom as uom_order
                ,bom_det.qty * coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = wo_detail_temp.uom),1) as qty_bom
                ,bom_det.uom as uom_bom
                ,bom_hdr.article_code 
                ,coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = wo_detail_temp.uom),1) as factor_qty
                ,(select min_package from article where article_code = bom_det.article_code) as min_package 
                from wo_detail_temp
                left join bom_hdr on bom_hdr.article_code=wo_detail_temp.article_code
                join bom_det on  bom_det.bom_code = bom_hdr.bom_code
                where wo_detail_temp.code ='$randomCode'
                and bom_hdr.status = '3'
                ) a
                group by article_code_det,uom_bom,min_package
                order by article_code_det
                ");

                if ($data){
                    DB::table('wo_detail_temp')
                        ->where('code',$randomCode)
                        ->delete();
                }
                
                return response()->json($data);                        
            }

            public function importExcel(Request $request)
            {

                // validasi
                $this->validate($request, [
                    'file' => 'required|mimes:xls,xlsx'
                ]);
        
                // menangkap file excel
                $file = $request->file('file');
        
                // // membuat nama file unik
                $namaFile = rand().$file->getClientOriginalName();
        
                // // upload ke folder file_siswa di dalam folder public
                // $file->move('file_siswa',$namaFile);
                // import data
                // Excel::import(new SiswaImport, public_path('/file_siswa/'.$namaFile));

                $data['filename']=$namaFile;
                db::table('import_stock_take_tmp')->delete();

                Excel::import(new TransferOutImport($data), $file);

                $dataValidasi = DB::table('import_stock_take_tmp')
                ->leftJoin('article','article.article_alternative_code','import_stock_take_tmp.article_code')
                ->select('import_stock_take_tmp.article_code'
                ,'import_stock_take_tmp.qty'
                ,DB::RAW("concat(
                    case when import_stock_take_tmp.qty::text ~ '^[0-9.]+$' = false then concat('Urutan ',row_number() over(),': Qty salah - ',qty) end,
                    case when article.article_code is null then concat('Urutan ',row_number() over(),': Article Code:',import_stock_take_tmp.article_code, ' tidak terdaftar') end,
                    case when (select location_code from goods_location_master a where a.location_code = import_stock_take_tmp.location_code) is null then concat('Urutan ',row_number() over(),': Location Code:',import_stock_take_tmp.location_code, ' tidak terdaftar') end
                    ) as notes")
                )
                ->where('file_name', $namaFile)
                ->get();

                $dataNotes=[];
                foreach ($dataValidasi as $val) {
                    if($val->notes){
                        $dataNotes[]= [$val->notes];
                    }
                } 

                $title ="Import $this->title";
                $pesan="";

                if (count($dataNotes) > 0 ){
                    $pesan .='Ada error pada data yang diupload, silahkan cek notes error!';
                    $status = 0;
                    $alert = "error";
                    $message = $dataNotes;
                    $data = "";

                }else{

                    // return redirect()->back()->with('success', 'Excel file imported successfully!');

                    $data = db::table('import_stock_take_tmp')
                    ->leftJoin('article','article.article_alternative_code','import_stock_take_tmp.article_code')
                    ->select('article.article_code'
                    ,'location_code'
                    ,'article.uom'
                    ,'import_stock_take_tmp.qty'
                    ,DB::RAW("(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = article.uom)"))
                    ->where('file_name', $namaFile)
                    ->get();
                                
                    $status = 1;
                    $alert = "success";
                    $message  = "$title is successfully imported";

                }
                        
                // $alert  ="success";
                // $message  = "$title is successfully imported";

                return response()->json(array('status' => $status,'title' => $title, 'message' => $message,'alert' =>$alert,'dataDetail'=>$data,'pesan'=>$pesan));

                // return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'dataDetail'=>$data]);
            }

            public function export()
            {
                return Excel::download(new TransferOutExport, 'transfer_out_template.xls');
            }


            // public function posting(Request $request)
            // {
            //     // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
            //     $username =  Auth::user()->username;
            //     $id=Crypt::decryptString($request->id);
            //     // $trNumber = DB::table('transfer_hdr')->where('id',$id)->where('status','3')->value('tr_number');
            //     $hdrQ = DB::table('transfer_hdr')->where('id',$id)->where('status','3')->first();
            //     $trNumber = $hdrQ->tr_number;
            //     $lastStatus = $hdrQ->status;    
            //     $trType = $this->moduleCode;
            //     $siteCode = 'HO';
            //     $location ='WH';
            //     $status = '4';
            //     $todayDate = date('Y-m-d');
            //     // $movementDate = date("d-m-Y");

            //     if ($lastStatus!=4){
            //         if ($trNumber){
            //             $data = DB::table('transfer_det')
            //             ->leftJoin('transfer_hdr','transfer_hdr.tr_number','transfer_det.tr_number')
            //             ->leftJoin('article','article.article_code','transfer_det.article_code')
            //             ->where('transfer_det.tr_number',$trNumber)
            //             // ->where('transfer_hdr.status','3')
            //             ->select('transfer_det.*','article.article_type','article.uom as uom_article',
            //                 DB::RAW("transfer_det.qty*coalesce(uom_conversion(transfer_det.uom,article.uom),1) as total_qty")
            //             )
            //             ->get();

            //             foreach($data as $val){
            //                 //insert article code kalo belum ada di tabel item_stock
            //                 DB::table('article_stock')
            //                 ->updateOrInsert(
            //                     [ 'site_code' =>$siteCode,
            //                         'article_code' => $val->article_code,
            //                         'location_number'=>$location
            //                     ],
            //                     [
            //                         'dept_code'=>$val->article_type,
            //                         'uom'=>$val->uom_article
            //                     ]
            //                 );

            //                 //update qty nya ditambahkan dengan qty baru
            //                 DB::table('article_stock')
            //                 ->where('site_code',$siteCode)
            //                 ->where('article_code',$val->article_code)
            //                 ->where('location_number',$location)
            //                 ->update([
            //                     'article_qty' => DB::raw('coalesce(article_qty,0) - '.$val->total_qty)
            //                 ]);

            //                 //update qty nya ditambahkan dengan qty baru
            //                 // $rowAffected = DB::table('article_stock')
            //                 // ->where('site_code',$siteCode)
            //                 // ->where('article_code',$val->article_code)
            //                 // ->decrement('article_qty', $val->total_qty);
            //             }
                                
                        
            //             $rowAffected = DB::table('transfer_hdr')
            //             ->where('tr_number',$trNumber)
            //             ->update(
            //                 [   
            //                     'status' => $status,
            //                     'updated_by' => Auth::user()->username,
            //                     'updated_at' => date('Y-m-d H:i:s')
            //                 ]
            //             );
                        
            //             if ($rowAffected > 0){

            //                 /*
            //                     CR dari abimnanyu
            //                     perubahan, untuk movement date mengikuti tanggald dari tr_date bukan current date
            //                 */

            //                 $movements = DB::table('transfer_det')
            //                 ->leftJoin('transfer_hdr','transfer_hdr.tr_number','transfer_det.tr_number')
            //                 ->leftJoin('article','article.article_code','transfer_det.article_code')
            //                 ->where('transfer_det.tr_number',$trNumber)
            //                 ->where('transfer_hdr.status','4')
            //                 ->where('qty', '<>', 0)
            //                 ->select(
            //                     // DB::RAW("now()::timestamp::date as movement_date" )
            //                     'transfer_hdr.tr_date as movement_date'
            //                     // DB::RAW("'$movementDate' as movement_date")
            //                     ,'transfer_det.article_code'
            //                     ,'article.article_desc'
            //                     ,DB::raw("0 as movement_plus")
            //                     ,DB::RAW("coalesce((uom_conversion(transfer_det.uom,article.uom)*transfer_det.qty),1) as movement_min")
            //                     ,DB::raw(" 0 as movement_price ")
            //                     ,'transfer_hdr.tr_number as movement_transnno'
            //                     ,DB::raw("'$trType' as movement_type")
            //                     ,'transfer_hdr.note as movement_desc'
            //                 )
            //                 ->get();
                            
            //                 $dataSetMovement = [];
            //                 foreach ($movements as $val) {
            //                     $dataSetMovement[] = [
            //                         'movement_date' => $val->movement_date,
            //                         'artikel_code' => $val->article_code,
            //                         'artikel_desc' => $val->article_desc,
            //                         'movement_min' => $val->movement_min,
            //                         'movement_plus' => $val->movement_plus,
            //                         'movement_price' => $val->movement_price,
            //                         'movement_transnno' => $val->movement_transnno,
            //                         'movement_type' => $val->movement_type,
            //                         'movement_desc' => $val->movement_desc,
            //                         'created_by' => Auth::user()->username,
            //                         'created_at' => date('Y-m-d H:i:s'),
            //                         'site_code' => $siteCode,
            //                         'location_number' => $location,
            //                         'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') - ($val->movement_min+$val->movement_plus)")
            //                     ];
            //                 }

            //                 DB::table('movement')->insert($dataSetMovement);

            //                 DB::commit();
            //                 $title ="Posting $this->title";
            //                 $alert  ="success";
            //                 $message  = "$title $trNumber Successfully Posted";
            //                 \LogActivity::addToLog($title,"username: $username Status $message");
            //                 return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            //                 // return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
            //             }else{
            //                 $title ="Posting $this->title";
            //                 $alert  ="warning";
            //                 $message  = "$title $trNumber Failed to Posting";
            //                 \LogActivity::addToLog($title,"username: $username Status $message");
            //                 return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            //                 // return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
            //             }
            //         }else{
            //             $title ="Posting $this->title";
            //             $alert  ="warning";
            //             $message  = "$title $trNumber Failed to Posting";
            //             \LogActivity::addToLog($title,"username: $username Status $message");
            //             return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            //         }
            //     }else{
            //         $title ="Posting $this->title";
            //         $alert  ="warning";
            //         $message  = "$title $trNumber Failed to Posting, Already posted";
            //         \LogActivity::addToLog($title,"username: $username Status $message");
            //         return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            //     }
            // }


          public function articleByLocation(Request $r)
{
    $locationFrom = $r->location;
    $locationTo   = $r->location_to;

    $allowedTypes = [];
    if ($locationTo) {
        $pgArray = DB::table('stock_location_master')
            ->where('location_code', $locationTo)
            ->value('article_type');

        $allowedTypes = $this->parsePostgresArray($pgArray);
    }

    $query = DB::table('article as a')
        ->leftJoin('warehouse_stock as s', function ($join) use ($locationFrom) {
            $join->on('s.article_code', '=', 'a.article_code')
                 ->where('s.location_number', $locationFrom)
                 ->where('s.site_code', $this->siteCode);
        })
        ->leftJoin('uom_con_v2 as u', 'u.article_code', '=', 'a.article_code')
        ->where('a.status', '1');   // ← GANTI sesuai nama kolom & value "aktif" yang benar

    if (!empty($allowedTypes)) {
        $query->where(function ($q) use ($allowedTypes) {
            $q->whereIn('a.article_type', $allowedTypes)
              ->orWhereIn('a.group_of_material', $allowedTypes);
        });
    }

    return $query->select(
        'a.article_code',
        'a.article_alternative_code',
        'a.article_desc',
        'a.article_type',
        'a.group_of_material',
        DB::raw('coalesce(s.article_qty, 0) as qty'),
        'u.unit_to as uom'
    )
    ->distinct()
    ->orderBy('a.article_alternative_code')
    ->get();
}

/**
 * Parse PostgreSQL array string "{RMP,RMNP}" → ['RMP','RMNP']
 * Return [] jika null/kosong → berarti tidak ada restriction
 */
private function parsePostgresArray(?string $pgArray): array
{
    if (empty($pgArray) || $pgArray === '{}') return [];
    return array_filter(array_map('trim', explode(',', trim($pgArray, '{}'))));
}

        public function checkLocationType(Request $request)
        {
            $loc = DB::table('stock_location_master')
                ->where('location_code', $request->location_code)
                ->select('location_type')
                ->first();

            return response()->json([
                'location_type' => $loc ? $loc->location_type : null
            ]);
        }

        public function fgByRm(Request $request)
        {
            $fgList = DB::table('bom_rm as br')
                ->join('bom_hdr as bh', 'bh.bom_code', '=', 'br.bom_code')
                ->join('article as a', 'a.article_code', '=', 'bh.article_code')
                ->where('br.article_code', $request->article_code)
                ->whereIn('bh.status', ['1', '2', '3'])
                ->select(
                    'bh.article_code as fg_code',
                    'a.article_alternative_code as fg_alt_code',  // ← tambah
                    'a.article_desc as fg_name'
                )
                ->distinct()
                ->orderBy('a.article_alternative_code')
                ->get();

            return response()->json($fgList);
        }

        /**
         * Ambil detail transfer + info article.
         */
        private function getTransferDetails(string $trNumber)
{
    return DB::table('transfer_stock_det')
        ->leftJoin('article', 'article.article_code', '=', 'transfer_stock_det.article_code')
        ->where('transfer_stock_det.tr_number', $trNumber)
        ->select(
            'transfer_stock_det.*',
            'article.article_type',
            'article.article_desc',
            'article.article_alternative_code',
            'article.uom as article_uom',
            DB::raw('coalesce(transfer_stock_det.qty, 0) as total_qty'),
            DB::raw("coalesce(
                transfer_stock_det.uom,
                (select unit_to from uom_con_v2 where article_code = transfer_stock_det.article_code limit 1),
                article.uom,
                'PCS'
            ) as stock_uom")
        )
        ->get();
}

        /**
         * Komponen RM dari sebuah FG (bom_hdr -> bom_rm), sudah diakumulasi per article_code.
         */
        private function getRmComponents(string $fgArticleCode)
        {
            return DB::table('bom_hdr as bh')
                ->join('bom_rm as br', 'br.bom_code', '=', 'bh.bom_code')
                ->join('article as a', 'a.article_code', '=', 'br.article_code')
                ->where('bh.article_code', $fgArticleCode)
                ->where('bh.status', '3')
                ->groupBy('br.article_code', 'a.article_type', 'a.article_desc',
                        'a.article_alternative_code', 'br.uom')
                ->select(
                    'br.article_code',
                    'a.article_type',
                    'a.article_desc',
                    'a.article_alternative_code',
                    'br.uom',
                    DB::raw('sum(coalesce(br.qty,0)) as qty_per_fg')
                )
                ->get();
        }

        /**
         * Terjemahkan detail transfer jadi dua daftar line:
         *  - out : yang dikurangi dari location_from
         *  - in  : yang ditambahkan ke location_to (hasil konversi RM jika FG -> Gudang NG RM)
         *
         * Line: ['article_code','article_type','article_desc','uom','qty','note']
         * Sudah diakumulasi per article_code di masing-masing sisi.
         *
         * @throws \RuntimeException jika FG tidak punya BOM RM aktif.
         */
        private function resolveTransferLines($hdrQ): array
        {
            $details = $this->getTransferDetails($hdrQ->tr_number);

            if ($details->isEmpty()) {
                throw new \RuntimeException("Transfer {$hdrQ->tr_number} gagal: tidak ada detail");
            }

            $out = [];
            $in  = [];

            $push = function (array &$bag, $code, $type, $desc, $uom, $qty, $note = null) {
                if ($qty <= 0) return;
                if (!isset($bag[$code])) {
                    $bag[$code] = [
                        'article_code' => $code,
                        'article_type' => $type,
                        'article_desc' => $desc ?? '',
                        'uom'          => $uom,
                        'qty'          => 0,
                        'notes'        => [],
                    ];
                }
                $bag[$code]['qty'] += $qty;
                if ($note) $bag[$code]['notes'][] = $note;
            };

            foreach ($details as $val) {
                $qtyBase = (float) $val->total_qty;

                // sisi keluar: selalu article aslinya
                $push($out, $val->article_code, $val->article_type, $val->article_desc,
                    $val->stock_uom, $qtyBase);

                $isFgToNgRm = ($val->article_type === 'FG' && $hdrQ->location_to === $this->ngRmLocation);

                if (!$isFgToNgRm) {
                    $push($in, $val->article_code, $val->article_type, $val->article_desc,
                        $val->stock_uom, $qtyBase);
                    continue;
                }

                // FG -> Gudang NG RM: pecah jadi komponen RM
                $rmComponents = $this->getRmComponents($val->article_code);

                if ($rmComponents->isEmpty()) {
                    throw new \RuntimeException(
                        "Article FG {$val->article_alternative_code} tidak punya BOM RM aktif (status 3), "
                        . "tidak bisa dikonversi ke Gudang NG RM"
                    );
                }

                foreach ($rmComponents as $rm) {
    $push(
        $in,
        $rm->article_code,
        $rm->article_type,
        $rm->article_desc,
        $rm->uom ?? $val->stock_uom ?? 'PCS',   // ← fallback terakhir
        $qtyBase * (float) $rm->qty_per_fg,
        "{$val->article_alternative_code} x {$qtyBase}"
    );
}
            }

            return ['out' => array_values($out), 'in' => array_values($in)];
        }

        /**
         * Bangun satu baris movement.
         */
       private function buildMovement(
    int $seq, $hdrQ, array $line, string $movementType, string $direction,
    string $locationNumber, string $movementFrom, string $movementTo,
    float $price, string $desc, string $username, string $movementDate  // ← rename dari $todayDate
): array {

    if (empty($locationNumber)) {
        throw new \RuntimeException(
            "buildMovement: location_number kosong untuk artikel {$line['article_code']}, "
            . "movement_type=$movementType, transnno={$hdrQ->tr_number}"
        );
    }

    $qty  = $line['qty'];
    $sign = ($direction === 'plus') ? '+' : '-';

    return [
        'movement_code'     => $seq,
        'movement_date'     => \Carbon\Carbon::createFromFormat('d-m-Y', $hdrQ->tr_date)->format('d-m-Y'),
        'artikel_code'      => $line['article_code'],
        'artikel_desc'      => $line['article_desc'],
        'movement_min'      => ($direction === 'min')  ? $qty : 0,
        'movement_plus'     => ($direction === 'plus') ? $qty : 0,
        'movement_price'    => $price,
        'movement_transnno' => $hdrQ->tr_number,
        'movement_type'     => $movementType,
        'movement_desc'     => $desc,
        'movement_from'     => $movementFrom,
        'movement_to'       => $movementTo,
        'partner_type'      => 'LOC',
        'created_by'        => $username,
        'created_at'        => date('Y-m-d H:i:s'),
        'site_code'         => $this->siteCode,
        'location_number'   => $locationNumber,
        // sementara — ditimpa recalculateMovementAndStock; tetap pakai movementDate biar konsisten
        'last_qty'          => DB::raw(
            "get_last_qty_new('{$line['article_code']}','$movementDate','{$this->siteCode}','$locationNumber') $sign $qty"
        ),
    ];
}

        /**
         * Susun deskripsi movement dari note header + jejak konversi.
         */
        private function movementDesc(?string $baseNote, array $line): string
        {
            $desc = (string) ($baseNote ?? '');
            if (!empty($line['notes'])) {
                $desc .= ' [Konversi dari ' . implode(', ', array_unique($line['notes'])) . ']';
            }
            return trim($desc);
        }

        /**
     * Simpan kondisi dokumen saat ini ke tabel history sebelum diubah.
     * Return nomor revisi yang baru dipakai.
     */
    private function snapshotHistory($hdr, string $username, ?string $reason = null): int
{
        $rev = (int) ($hdr->num_revision ?? 0) + 1;

        DB::table('transfer_stock_hdr_hist')->insert([
            'tr_number'     => $hdr->tr_number,
            'num_revision'  => $rev,
            'tr_date'       => $hdr->tr_date,
            'tr_type'       => $hdr->tr_type,
            'status'        => $hdr->status,
            'note'          => $hdr->note,
            'penerima'      => $hdr->penerima,
            'location_from' => $hdr->location_from,
            'location_to'   => $hdr->location_to,
            'approve_dept'  => $hdr->approve_dept,
            'edit_reason'   => $reason ?? '-',
            'revised_by'    => $username,
            'revised_at'    => date('Y-m-d H:i:s'),
        ]);

        $details = DB::table('transfer_stock_det')
            ->where('tr_number', $hdr->tr_number)
            ->get();

        $rows = [];
        foreach ($details as $d) {
            $rows[] = [
                'tr_number'    => $hdr->tr_number,
                'num_revision' => $rev,
                'article_code' => $d->article_code,
                'qty'          => $d->qty,
                'uom'          => $d->uom,
                'note'         => $d->note,
                'fg_target'    => $d->fg_target ?? null,
            ];
        }
        if ($rows) DB::table('transfer_stock_det_hist')->insert($rows);

        return $rev;
    }

        }

