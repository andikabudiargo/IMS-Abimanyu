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
        // Jika dipanggil dari Artisan tidak ada Auth::user()
        $username = $username ?? optional(Auth::user())->username ?? 'system-migration';

        DB::table('master_code')
            ->where('code_key', $key)
            ->update([
                'code_number' => DB::raw('code_number + 1'),
                'updated_by'  => $username,
                'updated_at'  => now()
            ]);

        $newCode = DB::table('master_code')
            ->where('code_key', $key)
            ->value('code_number');

        $months = [
            'I', 'II', 'III', 'IV', 'V', 'VI',
            'VII', 'VIII', 'IX', 'X', 'XI', 'XII'
        ];

        // ==========================
        // Parsing tanggal
        // ==========================
        if (empty($trDate)) {

            $refDate = now();

        } else {

            try {

                // Format dari database: 2026-07-07
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trDate)) {

                    $refDate = \Carbon\Carbon::createFromFormat('Y-m-d', $trDate);

                }
                // Format dari form: 07-07-2026
                elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $trDate)) {

                    $refDate = \Carbon\Carbon::createFromFormat('d-m-Y', $trDate);

                }
                // Format lain
                else {

                    $refDate = \Carbon\Carbon::parse($trDate);

                }

            } catch (\Exception $e) {

                $refDate = now();

            }
        }

        $month = $months[$refDate->month - 1];
        $year  = $refDate->year;

        return sprintf(
            '%s/%s/%s/%04d',
            $key,
            $year,
            $month,
            $newCode
        );
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

        $todayDate    = date('Y-m-d');
        $locationFrom = $hdrQ->location_from;
        $locationTo   = $hdrQ->location_to;
        $trType       = ($hdrQ->tr_type === 'SUPPLY') ? 'SUPPLY' : 'TRANSFER';

        $seq             = (int) DB::table('warehouse_movement')->max('movement_code');
        $dataSetMovement = [];

        // ===== KELUAR dari gudang asal =====
        foreach ($lines['out'] as $line) {
            $price = $this->getAvgPrice($line['article_code'], $locationFrom);

            $this->kurangiStock($line['article_code'], $locationFrom,
                                $line['article_type'], $line['uom'], $line['qty']);

            $dataSetMovement[] = $this->buildMovement(
                ++$seq, $hdrQ, $line, $trType, 'min',
                $locationFrom, $locationFrom, $locationTo,
                $price, $this->movementDesc($hdrQ->note, $line), $username, $todayDate
            );
        }

        // ===== MASUK ke gudang tujuan =====
        foreach ($lines['in'] as $line) {
            $price = $this->getAvgPrice($line['article_code'], $locationFrom);

            $this->tambahStock($line['article_code'], $locationTo,
                               $line['article_type'], $line['uom'], $line['qty'], $price);

            $dataSetMovement[] = $this->buildMovement(
                ++$seq, $hdrQ, $line, $trType, 'plus',
                $locationTo, $locationFrom, $locationTo,
                $price, $this->movementDesc($hdrQ->note, $line), $username, $todayDate
            );
        }

        if (!empty($dataSetMovement)) {
            DB::table('warehouse_movement')->insert($dataSetMovement);
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
                ->when(!$privileged, function ($q) use ($userDepts) {
                    $q->where(function ($sub) use ($userDepts) {
                        $sub->whereIn('dept_code', $userDepts)
                            ->orWhere('location_code', '011');   // gudang umum selalu muncul
                    });
                })
                ->orderBy('location_name')
                ->get();

            // Location To: semua gudang (boleh tujuan dept lain)
            $data['locationsTo'] = DB::table('stock_location_master')
                ->orderBy('location_name')
                ->get();

            $data['thirdParties'] = DB::table('third_party')->orderBy('nama')->get();

            return view("transfer/transferStock.create", $data);
        }

        public function store(Request $request)
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

            $hasilUpdate = AppHelpers::resetCode($poLeadCode);
        $trNumber = $this->getLastCode(
        $poLeadCode,
        $trDate,
        Auth::user()->username
    );

            DB::beginTransaction();
            try {
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

        public function postingNew(Request $request)
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

    // Hapus movement asli — tidak perlu CANCEL karena movement-nya hilang total
    DB::table('warehouse_movement')
        ->where('movement_transnno', $trNumber)
        ->where('movement_type', $baseType)
        ->delete();

    // Finalisasi stock via recalculate
    $affected = [];
    foreach (array_merge($lines['out'], $lines['in']) as $line) {
        $affected[$line['article_code'].'|'.$locationFrom] =
            ['article_code' => $line['article_code'], 'location' => $locationFrom];
        $affected[$line['article_code'].'|'.$locationTo] =
            ['article_code' => $line['article_code'], 'location' => $locationTo];
    }

    foreach ($affected as $a) {
        $this->recalculateMovementAndStock(
            $a['article_code'],
            $a['location'],
            date('Y-m-d', strtotime($hdrQ->tr_date))
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

    /**
     * Pastikan baris warehouse_stock ada untuk kombinasi site/article/location.
     */
    private function ensureStockRow(string $articleCode, string $location, ?string $deptCode, ?string $uom): void
{
    DB::table('warehouse_stock')->updateOrInsert(
        ['site_code' => $this->siteCode, 'article_code' => $articleCode, 'location_number' => $location],
        ['dept_code' => $deptCode ?? '', 'uom' => $uom ?? 'PCS']
    );
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

         public function cancel(Request $request)
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

        DB::table('transfer_stock_hdr')
            ->where('tr_number', $trNumber)
            ->update([
                'status'     => '5',
                'note'       => DB::raw("CONCAT(note,';','$reason')"),
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
            ->when(!$privileged, function ($q) use ($userDepts) {
                $q->where(function ($sub) use ($userDepts) {
                    $sub->whereIn('dept_code', $userDepts)
                        ->orWhere('location_code', '011');
                });
            })
            ->orderBy('location_name')
            ->get();

            $data['locationsTo'] = DB::table('stock_location_master')
                ->orderBy('location_name')
                ->get();

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
 
    // ── Ambil header lama ──────────────────────────────────────
    $hdr = DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->first();
    if (!$hdr) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
    }
    if ($hdr->status == '5') {
        return response()->json(['status'=>0,'title'=>$title,'message'=>['Transfer sudah dicancel, tidak bisa diedit.'],'alert'=>'error']);
    }
 
    // ── Validasi dasar ────────────────────────────────────────
    $errors = [];
    //if (!$editReason)   $errors[] = "Alasan edit harus diisi";
    if (!$trDate)       $errors[] = "Transfer Date harus diisi";
    if (!$locationCode) $errors[] = "Location From harus dipilih";
    if (!$locationTo)   $errors[] = "Location To harus dipilih";
    if ($locationTo && $locationCode && $locationTo === $locationCode)
                        $errors[] = "Location From dan Location To tidak boleh sama";
    if (empty($articles)) $errors[] = "Artikel harus diisi";
    if ($errors) {
        return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
    }
 
    // ── tr_type & approver ────────────────────────────────────
    $locToType   = DB::table('stock_location_master')->where('location_code', $locationTo)->value('location_type');
    $trType      = ($locToType === 'booth') ? 'SUPPLY' : 'TRANSFER';
    $approveDept = DB::table('stock_location_master')->where('location_code', $locationTo)->value('dept_code');
 
    DB::beginTransaction();
    try {
        // ── 0) Snapshot history sebelum diubah ───────────────
        $rev = $this->snapshotHistory($hdr, $username, $editReason);
 
        // ── 1) Hitung diff artikel lama vs baru ──────────────
        $oldDetails = DB::table('transfer_stock_det')
            ->where('tr_number', $trNumber)
            ->get()
            ->keyBy('article_code');
 
        $newDetails = collect($articles)->keyBy('article_code');
 
        // Kategori diff
        $removed = $oldDetails->filter(fn($o) => !$newDetails->has($o->article_code));
        $added   = $newDetails->filter(fn($n) => !$oldDetails->has($n->article_code));
        $changed = $newDetails->filter(function($n) use ($oldDetails) {
            if (!$oldDetails->has($n->article_code)) return false;
            $o = $oldDetails[$n->article_code];
            return (float)$o->qty !== (float)$n->qty || $o->uom !== $n->uom;
        });
        $same = $newDetails->filter(function($n) use ($oldDetails, $changed) {
            return $oldDetails->has($n->article_code) && !$changed->has($n->article_code);
        });
 
        // Tanggal movement (format dd-mm-yyyy untuk warehouse_movement)
        $movementDate = date('d-m-Y', strtotime($hdr->tr_date));
 
        // Artikel yang perlu direcalculate (kumpulkan dulu, eksekusi di akhir)
        // format: [['article_code'=>..., 'location'=>...], ...]
        $toRecalc = [];
 
        // ── 2) REMOVED: update movement → artikel pengganti pertama yg added
        //    Kalau tidak ada pengganti, update qty jadi 0 (movement tetap ada)
        //    Logika: pakai artikel pertama dari $added sebagai pengganti movement lama,
        //    sisanya ($added lebih dari 1) akan INSERT movement baru
        $addedList  = $added->values();   // re-index
        $addedUsed  = collect();           // track added yg sudah dipakai sebagai pengganti
 
        foreach ($removed as $oldArt) {
            $pengganti = $addedList->first(fn($a) => !$addedUsed->contains('article_code', $a->article_code));
 
            if ($pengganti) {
                // Resolusi lines untuk artikel pengganti
                $fakeHdr = (object)[
                    'tr_number'   => $trNumber,
                    'location_from' => $locationCode,
                    'location_to'   => $locationTo,
                    'tr_type'       => $trType,
                    'note'          => $note,
                ];
                $newQtyBase = $this->toBaseQty($pengganti->article_code, $pengganti->qty, $pengganti->uom);
                $oldQtyBase = $this->toBaseQty($oldArt->article_code, $oldArt->qty, $oldArt->uom);
 
               // Update movement MIN (location_from) → ganti article + qty
DB::table('warehouse_movement')
    ->where('movement_transnno', $trNumber)
    ->where('artikel_code', $oldArt->article_code)
    ->where('location_number', $locationCode)
    ->where('movement_min', '>', 0)
    ->where('movement_type', $trType)   // ← TAMBAH
    ->update([
        'artikel_code'  => $pengganti->article_code,
        'artikel_desc'  => $this->getArticleDesc($pengganti->article_code),
        'movement_min'  => $newQtyBase,
        'movement_desc' => $note ?? '',
    ]);

// Update movement PLUS (location_to) → ganti article + qty
DB::table('warehouse_movement')
    ->where('movement_transnno', $trNumber)
    ->where('artikel_code', $oldArt->article_code)
    ->where('location_number', $locationTo)
    ->where('movement_plus', '>', 0)
    ->where('movement_type', $trType)   // ← TAMBAH
    ->update([
        'artikel_code'  => $pengganti->article_code,
        'artikel_desc'  => $this->getArticleDesc($pengganti->article_code),
        'movement_plus' => $newQtyBase,
        'movement_desc' => $note ?? '',
    ]);
 
                // Tandai added ini sudah dipakai
                $addedUsed->push(['article_code' => $pengganti->article_code]);
 
                // Recalculate: artikel lama (dikembalikan) + artikel baru (dikurangi)
                $toRecalc[] = ['article_code' => $oldArt->article_code,    'location' => $locationCode];
                $toRecalc[] = ['article_code' => $oldArt->article_code,    'location' => $locationTo];
                $toRecalc[] = ['article_code' => $pengganti->article_code, 'location' => $locationCode];
                $toRecalc[] = ['article_code' => $pengganti->article_code, 'location' => $locationTo];
 
            } else {
                // Tidak ada pengganti → set qty movement jadi 0
                // (stok dikembalikan via recalculate)
               DB::table('warehouse_movement')
    ->where('movement_transnno', $trNumber)
    ->where('artikel_code', $oldArt->article_code)
    ->whereIn('location_number', [$locationCode, $locationTo])
    ->where('movement_type', $trType)
    ->delete();
 
                $toRecalc[] = ['article_code' => $oldArt->article_code, 'location' => $locationCode];
                $toRecalc[] = ['article_code' => $oldArt->article_code, 'location' => $locationTo];
            }
        }
 
        // ── 3) ADDED yang belum dipakai sebagai pengganti → INSERT movement baru ──
        $seq = (int) DB::table('warehouse_movement')->max('movement_code');
 
        $remainingAdded = $addedList->filter(
            fn($a) => !$addedUsed->contains('article_code', $a->article_code)
        );
 
        foreach ($remainingAdded as $newArt) {
            $qtyBase  = $this->toBaseQty($newArt->article_code, $newArt->qty, $newArt->uom);
            $artType  = DB::table('article')->where('article_code', $newArt->article_code)->value('article_type');
            $artDesc  = $this->getArticleDesc($newArt->article_code);
            $price    = $this->getAvgPrice($newArt->article_code, $locationCode);
 
            $lineArr = [
                'article_code' => $newArt->article_code,
                'article_type' => $artType,
                'article_desc' => $artDesc,
                'uom'          => $newArt->uom,
                'qty'          => $qtyBase,
                'notes'        => [],
            ];
 
            // Movement MIN (location_from)
            DB::table('warehouse_movement')->insert(
                $this->buildMovement(
                    ++$seq, $hdr, $lineArr, $trType, 'min',
                    $locationCode, $locationCode, $locationTo,
                    $price, $note ?? '', $username,
                    date('Y-m-d', strtotime($hdr->tr_date))
                )
            );
 
            // Movement PLUS (location_to)
            $priceIn = $this->getAvgPrice($newArt->article_code, $locationCode);
            DB::table('warehouse_movement')->insert(
                $this->buildMovement(
                    ++$seq, $hdr, $lineArr, $trType, 'plus',
                    $locationTo, $locationCode, $locationTo,
                    $priceIn, $note ?? '', $username,
                    date('Y-m-d', strtotime($hdr->tr_date))
                )
            );
 
            $toRecalc[] = ['article_code' => $newArt->article_code, 'location' => $locationCode];
            $toRecalc[] = ['article_code' => $newArt->article_code, 'location' => $locationTo];
        }
 
        // ── 4) CHANGED: update qty di movement yang sudah ada ──────────────────
       foreach ($changed as $newArt) {
    $newQtyBase = $this->toBaseQty($newArt->article_code, $newArt->qty, $newArt->uom);

    DB::table('warehouse_movement')
        ->where('movement_transnno', $trNumber)
        ->where('artikel_code', $newArt->article_code)
        ->where('location_number', $locationCode)
        ->where('movement_min', '>', 0)
        ->where('movement_type', $trType)   // ← TAMBAH
        ->update([
            'movement_min'  => $newQtyBase,
            'movement_desc' => $note ?? '',
        ]);

    DB::table('warehouse_movement')
        ->where('movement_transnno', $trNumber)
        ->where('artikel_code', $newArt->article_code)
        ->where('location_number', $locationTo)
        ->where('movement_plus', '>', 0)
        ->where('movement_type', $trType)   // ← TAMBAH
        ->update([
            'movement_plus' => $newQtyBase,
            'movement_desc' => $note ?? '',
        ]);

    $toRecalc[] = ['article_code' => $newArt->article_code, 'location' => $locationCode];
    $toRecalc[] = ['article_code' => $newArt->article_code, 'location' => $locationTo];
}
 
        // ── 5) Recalculate last_qty movement + warehouse_stock ─────────────────
        // Deduplicate toRecalc sebelum eksekusi
        $toRecalcUnique = collect($toRecalc)
            ->unique(fn($r) => $r['article_code'] . '|' . $r['location'])
            ->values();
 
        foreach ($toRecalcUnique as $item) {
            $this->recalculateMovementAndStock(
                $item['article_code'],
                $item['location'],
                $hdr->tr_date   // tanggal mulai recalculate (format Y-m-d)
            );
        }
 
        // ── 6) Sinkron transfer_stock_det ─────────────────────────────────────
        DB::table('transfer_stock_det')->where('tr_number', $trNumber)->delete();
        foreach ($articles as $val) {
            DB::table('transfer_stock_det')->insert([
                'tr_number'    => $trNumber,
                'article_code' => $val->article_code,
                'qty'          => $val->qty,
                'uom'          => $val->uom,
                'note'         => $val->note ?? null,
                'fg_target'    => $val->fg_target ?? null,
                'created_by'   => $username,
                'updated_by'   => $username,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
        }
 
        // ── 7) Update header ──────────────────────────────────────────────────
        DB::table('transfer_stock_hdr')
            ->where('tr_number', $trNumber)
            ->update([
                'tr_date'        => $trDate,
                'tr_type'        => $trType,
                'status'         => '4',        // tetap POSTED
                'num_revision'   => $rev,
                'note'           => $note,
                'penerima'       => $penerima,
                'location_from'  => $locationCode,
                'location_to'    => $locationTo,
                'approve_dept'   => $approveDept,
                'authorized_by'  => $username,
                'authorized_at'  => date('Y-m-d H:i:s'),
                'updated_by'     => $username,
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
 
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
    // 1) Hitung balance SEBELUM fromDate
    //    Pakai get_last_qty_new() dengan tanggal = fromDate - 1 hari
    //    tapi EXCLUDE movement dari tr_number ini (sudah diupdate di atas)
    //    Karena get_last_qty_new() baca semua movement s/d tanggal,
    //    kita ambil balance s/d (fromDate - 1) saja → tidak ada overlap
 
    $balanceBefore = (float) DB::selectOne(
        "SELECT get_last_qty_new(?, TO_CHAR(TO_DATE(?, 'YYYY-MM-DD') - INTERVAL '1 day', 'YYYY-MM-DD'), ?, ?) AS bal",
        [$articleCode, $fromDate, $this->siteCode, $location]
    )->bal;
 
    // 2) Ambil semua movement artikel+lokasi ini mulai dari fromDate,
    //    urut sama persis dengan get_last_qty_new(): date ASC, movement_code ASC
    $movements = DB::table('warehouse_movement')
        ->where('artikel_code', $articleCode)
        ->where('location_number', $location)
        ->where('site_code', $this->siteCode)
        ->where(DB::raw("TO_DATE(movement_date, 'DD-MM-YYYY')"), '>=',
            DB::raw("TO_DATE('$fromDate', 'YYYY-MM-DD')"))
        ->orderBy(DB::raw("TO_DATE(movement_date, 'DD-MM-YYYY')"), 'asc')
        ->orderBy('movement_code', 'asc')
        ->select('movement_code', 'movement_min', 'movement_plus')
        ->get();
 
    if ($movements->isEmpty()) {
        // Tidak ada movement setelah fromDate → update warehouse_stock ke balanceBefore
        $this->updateWarehouseStock($articleCode, $location, $balanceBefore);
        return;
    }
 
    // 3) Hitung ulang running last_qty dan UPDATE tiap baris
    $running = $balanceBefore;
    foreach ($movements as $mov) {
        $running = $running - (float)$mov->movement_min + (float)$mov->movement_plus;
 
        DB::table('warehouse_movement')
            ->where('movement_code', $mov->movement_code)
            ->update(['last_qty' => $running]);
    }
 
    // 4) Update warehouse_stock → saldo akhir = last_qty movement terakhir
    //    Tapi harus ambil last_qty dari movement PALING AKHIR secara keseluruhan
    //    (bukan hanya yang >= fromDate), karena mungkin ada movement lebih baru
    $latestLastQty = (float) DB::table('warehouse_movement')
        ->where('artikel_code', $articleCode)
        ->where('location_number', $location)
        ->where('site_code', $this->siteCode)
        ->orderBy(DB::raw("TO_DATE(movement_date, 'DD-MM-YYYY')"), 'desc')
        ->orderBy('movement_code', 'desc')
        ->value('last_qty');
 
    $this->updateWarehouseStock($articleCode, $location, $latestLastQty);
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


        public function updateNew(Request $request)
        {
            $user         = Auth::user();
            $username     = $user->username;
            $articles     = json_decode($request->articles);
            $trNumber     = $request->trNumber;
            $trDate       = $request->trDate;
            $note         = $request->note;
            $status       = '1'; // edit selalu reset ke NEW
            $locationCode = $request->locationFrom;
            $locationTo   = $request->locationTo;

            $title = "Save $this->title";

            // ===== Ambil header & cek status boleh edit =====
            $hdr = DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->first();

            if (!$hdr) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
            }

            // status 4 (POSTED) / 5 (CANCELED) tidak boleh diedit
            if (in_array($hdr->status, ['4', '5'])) {
                $msg = $hdr->status == '4'
                    ? 'Transfer sudah diposting, tidak bisa diedit. Lakukan cancel terlebih dahulu.'
                    : 'Transfer sudah dicancel, tidak bisa diedit.';
                return response()->json(['status'=>0,'title'=>$title,'message'=>[$msg],'alert'=>'error']);
            }

            // ===== Validasi dasar (sama seperti store) =====
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

            // ===== Tentukan tr_type berdasarkan location tujuan =====
            $locToType = DB::table('stock_location_master')
                ->where('location_code', $locationTo)
                ->value('location_type');

            $trType = ($locToType === 'booth') ? 'SUPPLY' : 'TRANSFER';

            // ===== Validasi stok (available = onhand - reserved, kecuali transfer ini) =====
            // Hanya gudang Consumable (006) yang divalidasi ketat, gudang lain boleh over-stock
            // ← disamakan dengan aturan di store()
            $strictStockLocation = '006';

            $overStock = [];
            foreach ($articles as $val) {
                $onhand = DB::table('warehouse_stock')
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $locationCode)
                    ->sum('article_qty');

                $reserved = DB::table('transfer_stock_det as d')
                    ->join('transfer_stock_hdr as h','h.tr_number','=','d.tr_number')
                    ->where('d.article_code', $val->article_code)
                    ->where('h.location_from', $locationCode)
                    ->where('h.tr_number', '<>', $trNumber)
                    ->whereIn('h.status', ['1','2','3'])
                    ->sum(DB::raw("d.qty * coalesce(uom_conversion(d.uom,(select uom from article where article_code = d.article_code)),1)"));

                $available = $onhand - $reserved;

                $qtyBase = DB::selectOne(
                    "select ? * coalesce(uom_conversion(?, (select uom from article where article_code = ?)),1) as q",
                    [$val->qty, $val->uom, $val->article_code]
                )->q;

                if ($locationCode === $strictStockLocation && $qtyBase > $available) {
                    $overStock[] = "Qty {$val->article_code} ($qtyBase) melebihi stok available ($available) di gudang $locationCode";
                }
            }
            if ($overStock) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>$overStock,'alert'=>'error']);
            }

            // ===== Snapshot dept approver (gudang tujuan) =====
            $approveDept = DB::table('stock_location_master')
                ->where('location_code', $locationTo)
                ->value('dept_code');

            DB::beginTransaction();
            try {
                // ----- Update header -----
                DB::table('transfer_stock_hdr')
                    ->where('tr_number', $trNumber)
                    ->update([
                        'tr_date'       => $trDate,
                        'tr_type'       => $trType,
                        'status'        => $status,
                        'note'          => $note,
                        'location_from' => $locationCode,
                        'location_to'   => $locationTo,
                        'approve_dept'  => $approveDept,
                        'updated_by'    => $username,
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);

                // ----- Reset approval history (isi dokumen berubah) -----
                DB::table('approval_history')
                    ->where('module_code', $this->moduleCode)
                    ->where('module_number', $trNumber)
                    ->delete();

                // ----- Sinkronkan detail: hapus yang tidak ada di input -----
                $keep = [];
                foreach ($articles as $val) {
                    $keep[] = $trNumber . $val->article_code;
                }

                DB::table('transfer_stock_det')
                    ->whereNotIn(DB::raw("CONCAT(tr_number, article_code)"), $keep)
                    ->where('tr_number', $trNumber)
                    ->delete();

                // ----- Upsert detail (termasuk fg_target) -----
                foreach ($articles as $val) {
                    DB::table('transfer_stock_det')->updateOrInsert(
                        ['tr_number' => $trNumber, 'article_code' => $val->article_code],
                        [
                            'qty'        => $val->qty,
                            'uom'        => $val->uom,
                            'note'       => $val->note ?? null,
                            'fg_target'  => $val->fg_target ?? null,
                            'updated_by' => $username,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }

                DB::commit();

                $message = "$title $trNumber is successfully updated";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','trNumber'=>$trNumber,'oEdit'=>true]);

            } catch (\Exception $e) {
                DB::rollBack();
                $message = "$title $trNumber is failed to update";
                \LogActivity::addToLog($title, "username: $username Status $message - ".$e->getMessage());
                return response()->json(['status'=>0,'title'=>$title,'message'=>[$message],'alert'=>'error']);
            }
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
                DB::table('transfer_stock_hdr')
                    ->where('tr_number', $trNumber)
                    ->update([
                        'status'     => '5',
                        'note'       => DB::raw("CONCAT(note,';','$reason')"),
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

            public function destroyOld(Request $request)
        {
            $username = Auth::user()->username;
            $id = Crypt::decryptString($request->id);

            $trNumber = DB::table('transfer_stock_hdr')->where('id', $id)
                ->where('status', '<>', '4')
                ->where('status', '<>', '5')
                ->value('tr_number');

            $rowAffected = DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->delete();

            if ($rowAffected > 0) {
                DB::table('transfer_stock_det')->where('tr_number', $trNumber)->delete();
                $title   = "Delete $this->title";
                $alert   = "success";
                $message = "$title $trNumber Successfully Deleted";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
            } else {
                $title   = "Delete $this->title";
                $alert   = "warning";
                $message = "$title $trNumber Failed to Delete";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
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


            public function articleByLocation(Request $r){
            return DB::table('stock as s')
                ->join('uom_con_v2 as u', 's.article_code', '=', 'u.article_code')
                ->where('s.location_code', $r->location)
                ->where('s.qty', '>', 0)
                ->select('s.article_code', 's.qty', 'u.uom_to as uom') // <-- uom_to
                ->get();
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
    float $price, string $desc, string $username, string $todayDate
): array {

    // GUARD: location_number tidak boleh kosong
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
                'movement_date'     => date('d-m-Y', strtotime($hdrQ->tr_date)),
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
                'last_qty'          => DB::raw(
                    "get_last_qty_new('{$line['article_code']}','$todayDate','{$this->siteCode}','$locationNumber') $sign $qty"
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
    private function snapshotHistory($hdr, string $username, string $reason): int
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
            'edit_reason'   => $reason,
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

