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

        =====================================================
        CATATAN REVISI MOVEMENT LOG (baca ini dulu sebelum ubah alur edit/delete)
        =====================================================
        Masalah lama: setiap update() SELALU reverse (insert baris CANCEL TRANSFER)
        lalu processPosting() lagi (insert baris baru), walau transfer masih status
        NEW/VALIDATE/APPROVED (belum pernah "resmi" diposting user lain). Akibatnya
        history warehouse_movement numpuk 3x tiap kali diedit, dan running balance
        (get_last_qty_new) jadi lompat-lompat karena baca banyak baris net-zero.

        Aturan baru:
        - Status 1/2/3 (NEW/VALIDATE/APPROVED) = masih draft, belum resmi.
          -> Edit: koreksi stok langsung (silentReverseStock) TANPA insert baris
             reversal, lalu hapus & tulis ulang movement transfer ini saja
             (purgeMovement + processPosting). Hasilnya cuma 1 set baris movement
             yang mencerminkan kondisi TERAKHIR, tidak ada jejak bolak-balik.
          -> Delete: silentReverseStock + purgeMovement + hard delete baris
             header/detail. Karena belum pernah resmi, tidak perlu status CANCELED,
             cukup hilang beneran.
        - Status 4 (POSTED) = sudah resmi.
          -> TIDAK bisa ganti tanggal, lokasi, atau menambah baris artikel baru.
          -> Hanya bisa adjust qty artikel yang SUDAH ada, lewat updatePostedQty().
             Perubahan itu dicatat sebagai selisih (delta) saja dengan tipe
             'ADJUSTMENT TRANSFER', bukan reverse+repost qty penuh.
          -> Delete tetap full reverse + log resmi + status -> CANCELED (5), karena
             ini pembatalan transaksi yang sudah pernah dianggap final.
        - Status 5 (CANCELED) = final, tidak bisa diapa-apakan lagi.
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

        $seq             = $this->nextMovementSeq();
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
        $todayDate    = date('Y-m-d');
        $locationFrom = $hdrQ->location_from;
        $locationTo   = $hdrQ->location_to;
        $baseType     = ($hdrQ->tr_type === 'SUPPLY') ? 'SUPPLY' : 'TRANSFER';
        $trType       = 'CANCEL ' . $baseType;
        $reason       = "($reasonLabel by $username)";

        $seq             = $this->nextMovementSeq();
        $dataSetMovement = [];

        // ===== Tarik balik dari gudang tujuan =====
        foreach ($lines['in'] as $line) {
            $price = $this->getAvgPrice($line['article_code'], $locationTo);

            $this->kurangiStock($line['article_code'], $locationTo,
                                $line['article_type'], $line['uom'], $line['qty']);

            $dataSetMovement[] = $this->buildMovement(
                ++$seq, $hdrQ, $line, $trType, 'min',
                $locationTo, $locationTo, $locationFrom,
                $price, $this->movementDesc($reason, $line), $username, $todayDate
            );
        }

        // ===== Kembalikan ke gudang asal =====
        foreach ($lines['out'] as $line) {
            $price = $this->getAvgPrice($line['article_code'], $locationFrom);

            $this->tambahStockTanpaAvg($line['article_code'], $locationFrom,
                                       $line['article_type'], $line['uom'], $line['qty']);

            $dataSetMovement[] = $this->buildMovement(
                ++$seq, $hdrQ, $line, $trType, 'plus',
                $locationFrom, $locationTo, $locationFrom,
                $price, $this->movementDesc($reason, $line), $username, $todayDate
            );
        }

        if (!empty($dataSetMovement)) {
            DB::table('warehouse_movement')->insert($dataSetMovement);
        }

        return ['success' => true, 'message' => "Stock $trNumber berhasil di-reverse"];
    }

        /**
         * Reverse stok saja TANPA insert baris movement.
         * Dipakai untuk koreksi/hapus transfer yang masih DRAFT (status 1/2/3) —
         * karena belum pernah "resmi" diposting user lain, tidak perlu jejak
         * reversal di warehouse_movement, cukup stok dikembalikan.
         */
        private function silentReverseStock(object $hdrQ): void
    {
        $lines = $this->resolveTransferLines($hdrQ);

        // Tarik balik dari gudang tujuan
        foreach ($lines['in'] as $line) {
            $this->kurangiStock($line['article_code'], $hdrQ->location_to,
                                $line['article_type'], $line['uom'], $line['qty']);
        }

        // Kembalikan ke gudang asal
        foreach ($lines['out'] as $line) {
            $this->tambahStockTanpaAvg($line['article_code'], $hdrQ->location_from,
                                       $line['article_type'], $line['uom'], $line['qty']);
        }
    }

        /**
         * Hapus seluruh baris warehouse_movement milik satu transfer.
         * Dipakai saat draft (status 1/2/3) dikoreksi/dihapus, karena movement-nya
         * belum pernah "resmi" — jadi ditulis ulang bersih, bukan ditumpuk.
         */
        private function purgeMovement(string $trNumber): void
    {
        DB::table('warehouse_movement')->where('movement_transnno', $trNumber)->delete();
    }

        /**
         * Ambil nomor movement_code berikutnya secara aman dari race condition
         * (kunci baris tertinggi selama transaksi berjalan).
         */
        private function nextMovementSeq(): int
    {
        return (int) DB::table('warehouse_movement')->lockForUpdate()->max('movement_code');
    }

        // ===== HELPER METHODS =====

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
    private function ensureStockRow(string $articleCode, string $location, string $deptCode, string $uom): void
    {
        DB::table('warehouse_stock')->updateOrInsert(
            ['site_code' => $this->siteCode, 'article_code' => $articleCode, 'location_number' => $location],
            ['dept_code' => $deptCode, 'uom' => $uom]
        );
    }

    private function stockQuery(string $articleCode, string $location)
    {
        return DB::table('warehouse_stock')
            ->where('site_code', $this->siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location);
    }

    private function kurangiStock(string $articleCode, string $location, string $deptCode, string $uom, float $qty): void
    {
        $this->ensureStockRow($articleCode, $location, $deptCode, $uom);

        $this->stockQuery($articleCode, $location)
            ->update(['article_qty' => DB::raw('coalesce(article_qty,0) - ' . $qty)]);
    }

    private function tambahStock(string $articleCode, string $location, string $deptCode, string $uom, float $qtyMasuk, float $hargaMasuk): void
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
    private function tambahStockTanpaAvg(string $articleCode, string $location, string $deptCode, string $uom, float $qty): void
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

        $hdrQ = DB::table('transfer_stock_hdr')->where('id', $id)->where('status', '4')->first();

        if (!$hdrQ) {
            return redirect()->back()->with([
                'title'   => $title,
                'alert'   => 'warning',
                'message' => "$title gagal: data tidak ditemukan atau status bukan POSTED",
            ]);
        }

        if (!($user->hasAnyRole(['Superuser', 'accounting']) || $user->can('transferOut-posting'))) {
            return redirect()->back()->with([
                'title'   => $title,
                'alert'   => 'warning',
                'message' => 'Anda tidak berwenang melakukan cancel',
            ]);
        }

        $trNumber = $hdrQ->tr_number;
        $reason   = "(Cancel by $username, Reason: $request->reason)";

        DB::beginTransaction();
        try {
            $reverse = $this->reverseStock($hdrQ, $username, "Cancel, Reason: $request->reason");
            if (!$reverse['success']) {
                DB::rollBack();
                return redirect()->back()->with([
                    'title'   => $title,
                    'alert'   => 'warning',
                    'message' => implode(' | ', (array) $reverse['message']),
                ]);
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
            return redirect()->back()->with(['title' => $title, 'alert' => 'success', 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            $message = "$title $trNumber Failed: " . $e->getMessage();
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => 'warning', 'message' => $message]);
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
            // ← TAMBAH: dipakai view untuk kunci field tanggal/lokasi & sembunyikan "tambah baris" saat POSTED
            $data['isPosted']   = ($data['header']->status == '4');

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

            /**
             * Edit PENUH: tanggal, lokasi, tambah/hapus baris artikel.
             * HANYA boleh selama transfer masih DRAFT (status 1/2/3).
             * Kalau status sudah POSTED (4), pakai updatePostedQty() saja.
             */
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
            $editReason   = $request->editReason;        // ← TAMBAH

            $title = "Save $this->title";

            // ===== Ambil header lama =====
            $hdr = DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->first();
            if (!$hdr) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
            }
            if ($hdr->status == '5') {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Transfer sudah dicancel, tidak bisa diedit.'],'alert'=>'error']);
            }
            if ($hdr->status == '4') {
                // Sudah resmi POSTED: tanggal/lokasi/baris tidak boleh diubah lagi lewat sini.
                return response()->json(['status'=>0,'title'=>$title,
                    'message'=>['Transfer sudah POSTED. Tanggal, lokasi, dan baris artikel tidak bisa diubah. Gunakan menu "Adjust Qty" untuk mengoreksi jumlah.'],
                    'alert'=>'error']);
            }

            // ===== Validasi dasar =====
            $errors = [];
            if (!$editReason)    $errors[] = "Alasan edit harus diisi";   // ← TAMBAH
            if (!$trDate)        $errors[] = "Transfer Date harus diisi";
            if (!$locationCode)  $errors[] = "Location From harus dipilih";
            if (!$locationTo)    $errors[] = "Location To harus dipilih";
            if ($locationTo && $locationCode && $locationTo === $locationCode)
                                $errors[] = "Location From dan Location To tidak boleh sama";
            if (empty($articles)) $errors[] = "Artikel harus diisi";
            if ($errors) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>$errors,'alert'=>'error']);
            }

            // ===== tr_type & approver =====
            $locToType = DB::table('stock_location_master')->where('location_code', $locationTo)->value('location_type');
            $trType    = ($locToType === 'booth') ? 'SUPPLY' : 'TRANSFER';
            $approveDept = DB::table('stock_location_master')->where('location_code', $locationTo)->value('dept_code');

            DB::beginTransaction();
            try {

                // ===== 0) Snapshot kondisi lama (tetap dicatat sebagai riwayat draft) =====
                $rev = $this->snapshotHistory($hdr, $username, $editReason);

                // ===== 1) Koreksi stok dari kondisi lama TANPA insert baris reversal =====
                // (status masih draft → belum pernah "resmi", jadi tidak perlu jejak CANCEL)
                $this->silentReverseStock($hdr);
                $this->purgeMovement($trNumber);

                // ===== 2) Update header (status di-reset dulu ke 1, nanti processPosting jadikan 4) =====
                DB::table('transfer_stock_hdr')
                    ->where('tr_number', $trNumber)
                    ->update([
                        'tr_date'       => $trDate,
                        'tr_type'       => $trType,
                        'status'        => '1',
                        'num_revision'  => $rev,
                        'note'          => $note,
                        'penerima'      => $penerima,
                        'location_from' => $locationCode,
                        'location_to'   => $locationTo,
                        'approve_dept'  => $approveDept,
                        'updated_by'    => $username,
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]);

                // ===== 3) Reset approval history =====
                DB::table('approval_history')
                    ->where('module_code', $this->moduleCode)
                    ->where('module_number', $trNumber)
                    ->delete();

                // ===== 4) Sinkron detail: hapus semua lalu tulis ulang sesuai input =====
                DB::table('transfer_stock_det')
                    ->where('tr_number', $trNumber)
                    ->delete();

                foreach ($articles as $val) {
                    DB::table('transfer_stock_det')->insert([
                        'tr_number'   => $trNumber,
                        'article_code'=> $val->article_code,
                        'qty'         => $val->qty,
                        'uom'         => $val->uom,
                        'note'        => $val->note ?? null,
                        'fg_target'   => $val->fg_target ?? null,
                        'created_by'  => $username,
                        'updated_by'  => $username,
                        'created_at'  => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                }

                // ===== 5) Posting ulang dengan kondisi baru (1 set movement bersih) =====
                $postResult = $this->processPosting($trNumber, $username);
                if (!$postResult['success']) {
                    DB::rollBack();
                    return response()->json(['status'=>0,'title'=>$title,'message'=>(array) $postResult['message'],'alert'=>'error']);
                }

                DB::commit();
                $message = "$title $trNumber is successfully updated & reposted";
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
                \LogActivity::addToLog($title, "username: $username Status $message - ".$e->getMessage());
                return response()->json(['status'=>0,'title'=>$title,'message'=>[$message],'alert'=>'error']);
            }
        }

        /**
         * Adjust QTY khusus transfer yang sudah POSTED (status 4).
         * - Tidak bisa ganti tr_date, location_from, location_to.
         * - Tidak bisa menambah artikel baru (hanya artikel yang sudah ada di transfer ini).
         * - Hanya selisih (delta) qty yang diproses ke stok & dicatat sebagai movement
         *   bertipe 'ADJUSTMENT TRANSFER', bukan reverse+repost qty penuh.
         *
         * Payload: trNumber, editReason, articles: [{article_code, qty}, ...]
         * (qty di sini adalah qty BARU untuk artikel tsb, bukan delta)
         */
        public function updatePostedQty(Request $request)
        {
            $user       = Auth::user();
            $username   = $user->username;
            $trNumber   = $request->trNumber;
            $editReason = $request->editReason;
            $articles   = json_decode($request->articles);
            $title      = "Adjust Qty $this->title";

            $hdr = DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->first();
            if (!$hdr) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
            }
            if ($hdr->status != '4') {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Transfer bukan status POSTED, gunakan menu Edit biasa.'],'alert'=>'error']);
            }
            if (!$editReason) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Alasan penyesuaian harus diisi'],'alert'=>'error']);
            }
            if (empty($articles)) {
                return response()->json(['status'=>0,'title'=>$title,'message'=>['Artikel harus diisi'],'alert'=>'error']);
            }

            $existing = DB::table('transfer_stock_det')
                ->where('tr_number', $trNumber)
                ->get()
                ->keyBy('article_code');

            // Tidak boleh menambah artikel baru pada transfer yang sudah POSTED
            $notFound = [];
            foreach ($articles as $a) {
                if (!$existing->has($a->article_code)) {
                    $notFound[] = $a->article_code;
                }
            }
            if ($notFound) {
                return response()->json(['status'=>0,'title'=>$title,
                    'message'=>['Tidak bisa menambah artikel baru pada transfer yang sudah POSTED: '.implode(', ', $notFound)],
                    'alert'=>'error']);
            }

            DB::beginTransaction();
            try {
                $rev = $this->snapshotHistory($hdr, $username, $editReason);
                $seq = $this->nextMovementSeq();
                $todayDate = date('Y-m-d');
                $adjustedAny = false;

                foreach ($articles as $a) {
                    $old   = $existing[$a->article_code];
                    $qtyNew = (float) $a->qty;
                    $qtyOld = (float) $old->qty;
                    $delta  = $qtyNew - $qtyOld;

                    if (abs($delta) < 0.0000001) {
                        continue; // tidak berubah, skip
                    }
                    $adjustedAny = true;

                    $artType = DB::table('article')->where('article_code', $a->article_code)->value('article_type');
                    $uom     = $old->uom;
                    $qtyAbs  = abs($delta);

                    if ($delta > 0) {
                        // qty transfer nambah: tambah pengurangan di lokasi asal, tambah penambahan di lokasi tujuan
                        $price = $this->getAvgPrice($a->article_code, $hdr->location_from);
                        $this->kurangiStock($a->article_code, $hdr->location_from, $artType, $uom, $qtyAbs);
                        $this->tambahStock($a->article_code, $hdr->location_to, $artType, $uom, $qtyAbs, $price);
                    } else {
                        // qty transfer berkurang: kembalikan sebagian dari tujuan ke asal
                        $this->kurangiStock($a->article_code, $hdr->location_to, $artType, $uom, $qtyAbs);
                        $this->tambahStockTanpaAvg($a->article_code, $hdr->location_from, $artType, $uom, $qtyAbs);
                    }

                    DB::table('transfer_stock_det')
                        ->where('tr_number', $trNumber)
                        ->where('article_code', $a->article_code)
                        ->update([
                            'qty'        => $qtyNew,
                            'updated_by' => $username,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                    $price = $this->getAvgPrice($a->article_code, $hdr->location_from);
                    $desc  = "Adjust qty $qtyOld -> $qtyNew (rev $rev, alasan: $editReason)";
                    $line  = ['article_code'=>$a->article_code,'article_desc'=>$old->note ?? '','qty'=>$qtyAbs,'uom'=>$uom];

                    // Baris keluar dari lokasi asal
                    DB::table('warehouse_movement')->insert($this->buildMovement(
                        ++$seq, $hdr, $line, 'ADJUSTMENT TRANSFER', 'min',
                        $hdr->location_from, $hdr->location_from, $hdr->location_to,
                        $price, $desc, $username, $todayDate
                    ));

                    // Baris masuk ke lokasi tujuan
                    DB::table('warehouse_movement')->insert($this->buildMovement(
                        ++$seq, $hdr, $line, 'ADJUSTMENT TRANSFER', 'plus',
                        $hdr->location_to, $hdr->location_from, $hdr->location_to,
                        $price, $desc, $username, $todayDate
                    ));
                }

                if (!$adjustedAny) {
                    DB::rollBack();
                    return response()->json(['status'=>0,'title'=>$title,'message'=>['Tidak ada perubahan qty'],'alert'=>'warning']);
                }

                DB::table('transfer_stock_hdr')
                    ->where('tr_number', $trNumber)
                    ->update([
                        'num_revision' => $rev,
                        'updated_by'   => $username,
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);

                DB::commit();
                $message = "$title $trNumber berhasil disesuaikan";
                \LogActivity::addToLog($title, "username: $username Status $message");
                return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','trNumber'=>$trNumber]);

            } catch (\Exception $e) {
                DB::rollBack();
                $message = "$title $trNumber gagal disesuaikan";
                \LogActivity::addToLog($title, "username: $username Status $message - ".$e->getMessage());
                return response()->json(['status'=>0,'title'=>$title,'message'=>[$message.': '.$e->getMessage()],'alert'=>'error']);
            }
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

            /**
             * Hapus transfer.
             * - Status 1/2/3 (DRAFT, belum resmi): koreksi stok TANPA jejak reversal,
             *   hapus movement transfer ini, lalu HARD DELETE header & detail.
             * - Status 4 (POSTED, sudah resmi): tetap full reverse + log resmi,
             *   status diubah jadi CANCELED (5), data tetap ada untuk audit.
             */
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

            DB::beginTransaction();
            try {
                if (in_array($hdrQ->status, ['1', '2', '3'])) {
                    // ===== DRAFT: belum pernah resmi -> koreksi stok tanpa jejak, hapus beneran =====
                    $this->silentReverseStock($hdrQ);
                    $this->purgeMovement($trNumber);

                    DB::table('approval_history')
                        ->where('module_code', $this->moduleCode)
                        ->where('module_number', $trNumber)
                        ->delete();

                    DB::table('transfer_stock_det')->where('tr_number', $trNumber)->delete();
                    DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->delete();

                    DB::commit();
                    $message = "$title $trNumber Successfully Deleted";
                    \LogActivity::addToLog($title, "username: $username Status $message");
                    return redirect()->back()->with(['title'=>$title,'alert'=>'success','message'=>$message]);
                }

                // ===== POSTED: sudah resmi -> full reverse + log resmi + status CANCELED =====
                $reason  = "(Delete by $username)";
                $reverse = $this->reverseStock($hdrQ, $username, 'Delete');
                if (!$reverse['success']) {
                    DB::rollBack();
                    return redirect()->back()->with(['title'=>$title,'alert'=>'warning','message'=>implode(' | ', (array) $reverse['message'])]);
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
            $user     = Auth::user();
            $username = $user->username;

            $userDepts = DB::table('user_dept')->where('username', $username)->pluck('dept')->toArray(); // <— SESUAIKAN
            $privileged = $user->hasAnyRole(['Superuser','accounting','finance']);   // untuk LIHAT semua data
            $isSuperAcc = $user->hasAnyRole(['Superuser','accounting']);             // untuk hak edit/delete/approve

            $searchTr      = strtolower($request->searchTr);
            $searchStatus  = $request->searchStatus;
            $trDate        = $request->trDate;
            $transferFrom  = $request->transferFrom;
            $transferTo    = $request->transferTo;

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

            $query = DB::table('transfer_stock_hdr')
            ->leftJoin('stock_location_master as locFrom','locFrom.location_code','=','transfer_stock_hdr.location_from')
            ->leftJoin('stock_location_master as locTo','locTo.location_code','=','transfer_stock_hdr.location_to')
            ->where(function ($q) use ($searchTr,$searchStatus,$trDate,$fromDate,$toDate,$transferFrom,$transferTo) {
                $searchTr     ? $q->where('transfer_stock_hdr.tr_number','ilike','%'.$searchTr.'%') : '';
                $searchStatus ? $q->where('transfer_stock_hdr.status',$searchStatus) : '';
                $trDate       ? $q->whereBetween(DB::raw("to_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
                $transferTo   ? $q->where('transfer_stock_hdr.location_to',$transferTo) : '';
                $transferFrom ? $q->where('transfer_stock_hdr.location_from',$transferFrom) : '';
            });

            $query->where('transfer_stock_hdr.status', '<>', '5');

            if (!$privileged) {
                $query->where(function($q) use ($userDepts) {
                    $q->whereIn('locFrom.dept_code', $userDepts)              // <— SESUAIKAN kolom dept
                    ->orWhereIn('transfer_stock_hdr.approve_dept', $userDepts);
                });
            }

            $data = $query->select(
                'transfer_stock_hdr.*'
                ,'locFrom.location_name as location_name'
                ,'locFrom.dept_code as loc_from_dept'      // <— SESUAIKAN
                ,'locTo.location_name as location_name_to'
                ,'locTo.dept_code as loc_to_dept'          // <— SESUAIKAN
                ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = transfer_stock_hdr.tr_number) as approval_by")
            )
            
            ->orderBy('transfer_stock_hdr.id')
            ->get();

            return Datatables::of($data)
            ->addColumn('action', function ($data) use ($username, $userDepts, $isSuperAcc) {

                $isCreator  = ($data->created_by === $username);
                $isDestDept = in_array($data->approve_dept, $userDepts);  // dept-nya = gudang tujuan
                $st         = $data->status;

                // Hak edit/delete: status 1/2 -> pembuat atau super/acc; status 3/4 -> hanya super/acc
                $canEditDelete = false;
                if (in_array($st, ['1','2']))      $canEditDelete = $isCreator || $isSuperAcc;
                elseif (in_array($st, ['3','4']))  $canEditDelete = $isSuperAcc;

                $buttons  = '<div class="d-inline-flex">
                                <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown"><i data-feather="menu"></i></a>';
                $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

                // POSTING: status 1/2
                if (in_array($st, ['1','2']) && ($isDestDept || $isSuperAcc)) {
                    $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                        data-confirm='Are You Sure want to post This number?'
                        data-confirm-yes='document.getElementById(\"delete-form-".$data->id."\").submit();'
                        data-modal-id='".$data->id."'
                        data-url='". route('transferStock.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                        <i data-feather='check' class='feather-14-red'></i><span>". __('Posting') ."</span></a>";
                }

          // EDIT (penuh, hanya untuk draft 1/2/3)
            if ($canEditDelete && $st != '4') {
                $buttons .= "<a href='javascript:;' class='dropdown-item edit-with-reason'
                                data-href='". route('transferStock.edit', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                <i data-feather='file-text'></i><span>". __('Edit') ."</span></a>";
            }

                // ADJUST QTY: status 4 (posted) -> hanya super/acc, buka form khusus qty saja
                if ($st == '4' && $isSuperAcc) {
                    $buttons .= "<a href='javascript:;' class='dropdown-item edit-with-reason'
                                    data-href='". route('transferStock.edit', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='sliders'></i><span>". __('Adjust Qty') ."</span></a>";
                }

                // CANCEL: status 4 (posted) -> hanya super/acc
                if ($st == '4' && $isSuperAcc) {
                    $buttons .= "<a href='javascript:;' id='cancelReasonButton' class='dropdown-item'
                                    data-toggle='modal' data-target='#reasonModalCancel'
                                    data-href='". route("transferStock.cancel", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='corner-down-left' class='feather-14-red'></i><span>". __('Cancel') ."</span></a>";
                }

                // DETAIL (selalu)
                $buttons .= '<a href="'. route('transferStock.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="list"></i><span>'. __("Detail") .'</span></a>';

                // DELETE (status 5 tidak boleh)
                if ($canEditDelete && $st != '5') {
                    $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                                    data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?'
                                    data-confirm-yes='document.getElementById(\"delete-form-".$data->id."\").submit();'
                                    data-modal-id='".$data->id."'
                                    data-url='". route('transferStock.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='trash-2' class='feather-14-red'></i><span>". __('Delete') ."</span></a>";
                }

                // PRINT (selalu)
                $buttons .= '<a href="'. route('transferStock.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                <i data-feather="printer"></i><span>'. __("Print") .'</span></a>';

                $buttons .= '</div></div>';
                return $buttons;
            })
            ->addColumn('tr_type', function ($data) use ($privileged, $userDepts) {
            if ($privileged) {
                $type = 'TRF';
            } elseif (in_array($data->loc_from_dept, $userDepts)) {
                $type = 'OUT';
            } elseif (in_array($data->approve_dept, $userDepts)) {
                $type = 'IN';
            } else {
                $type = 'TRF';
            }

            if ($type === 'IN') {
                // barang masuk ke gudang dept user
                return "<span class='badge badge-success'><i data-feather='arrow-down-left' class='feather-14 mr-25'></i>IN</span>";
            } elseif ($type === 'OUT') {
                // barang keluar dari gudang dept user
                return "<span class='badge badge-danger'><i data-feather='arrow-up-right' class='feather-14 mr-25'></i>OUT</span>";
            }
            // privileged / netral
            return "<span class='badge badge-secondary'><i data-feather='repeat' class='feather-14 mr-25'></i>TRF</span>";
        })
            ->addColumn('status', function ($data) {
                $badges   = ['badge-primary','badge-info','badge-warning','badge-success','badge-danger','badge-dark','badge-secondary','badge-danger'];
                $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
                return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTr[$data->status - 1]."</div>";
            })
            ->rawColumns(['action','status','tr_number','tr_type'])
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
                    DB::raw("coalesce(transfer_stock_det.uom,
                        (select unit_to from uom_con_v2 where article_code = transfer_stock_det.article_code limit 1)
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
                        $rm->uom ?? $val->stock_uom,
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
            ];
        }
        if ($rows) DB::table('transfer_stock_det_hist')->insert($rows);

        return $rev;
    }

        }