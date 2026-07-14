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

    $siteCode     = 'HO';
    $todayDate    = date('Y-m-d');
    $locationFrom = $hdrQ->location_from;
    $locationTo   = $hdrQ->location_to;
    $isSupply     = ($hdrQ->tr_type === 'SUPPLY');
    $trType       = $isSupply ? 'SUPPLY' : 'TRANSFER';

    $data = DB::table('transfer_stock_det')
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

    if ($data->isEmpty()) {
        return ['success' => false, 'message' => ["Transfer $trNumber gagal diposting: tidak ada detail"]];
    }

    // ===== VALIDASI STOK (hanya gudang 006 yang ketat) =====
   // $strictStockLocation = '006';
    //$overStock = [];
    //foreach ($data as $val) {
      //  $qtyBase = (float) $val->total_qty;

        //$onhand = (float) DB::table('warehouse_stock')
          //  ->where('site_code', $siteCode)
           // ->where('article_code', $val->article_code)
           // ->where('location_number', $locationFrom)
           // ->sum('article_qty');

        //$reserved = (float) DB::table('transfer_stock_det as d')
          //  ->join('transfer_stock_hdr as h', 'h.tr_number', '=', 'd.tr_number')
           // ->where('d.article_code', $val->article_code)
           // ->where('h.location_from', $locationFrom)
           // ->where('h.tr_number', '<>', $trNumber)
           // ->whereIn('h.status', ['1', '2', '3'])
           // ->sum(DB::raw("d.qty * coalesce(uom_conversion(d.uom,(select uom from article where article_code = d.article_code)),1)"));

        //$available = $onhand - $reserved;

        //if ($locationFrom === $strictStockLocation && $qtyBase > $available) {
          //  $overStock[] = "Qty {$val->article_alternative_code} ({$qtyBase}) melebihi stok available ({$available}) di gudang asal";
        //}
    //}
    //if ($overStock) {
      //  return ['success' => false, 'message' => $overStock];
    //}

    // ===== GENERATE MOVEMENT =====
    $seq             = (int) DB::table('warehouse_movement')->max('movement_code');
    $dataSetMovement = [];

    // ===== PROSES SEMUA ARTIKEL (tanpa konversi RM->FG) =====
    foreach ($data as $val) {
        $qtyBase     = (float) $val->total_qty;
        $hargaPindah = $this->getAvgPrice($siteCode, $val->article_code, $locationFrom);

        $this->kurangiStock($siteCode, $val->article_code, $locationFrom, $val->article_type, $val->stock_uom, $qtyBase);
        $this->tambahStock($siteCode, $val->article_code, $locationTo, $val->article_type, $val->stock_uom, $qtyBase, $hargaPindah);

        // keluar dari gudang asal
        $seq++;
        $dataSetMovement[] = [
            'movement_code'     => $seq,
            'movement_date'     => date('d-m-Y', strtotime($hdrQ->tr_date)),
            'artikel_code'      => $val->article_code,
            'artikel_desc'      => $val->article_desc ?? '',
            'movement_min'      => $qtyBase,
            'movement_plus'     => 0,
            'movement_price'    => $hargaPindah,
            'movement_transnno' => $trNumber,
            'movement_type'     => $trType,
            'movement_desc'     => $hdrQ->note,
            'movement_from'     => $locationFrom,
            'movement_to'       => $locationTo,
            'partner_type'      => 'LOC',
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $siteCode,
            'location_number'   => $locationFrom,
            'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$locationFrom') - $qtyBase"),
        ];

        // masuk ke gudang tujuan
        $seq++;
        $dataSetMovement[] = [
            'movement_code'     => $seq,
            'movement_date'     => date('d-m-Y', strtotime($hdrQ->tr_date)),
            'artikel_code'      => $val->article_code,
            'artikel_desc'      => $val->article_desc ?? '',
            'movement_min'      => 0,
            'movement_plus'     => $qtyBase,
            'movement_price'    => $hargaPindah,
            'movement_transnno' => $trNumber,
            'movement_type'     => $trType,
            'movement_desc'     => $hdrQ->note,
            'movement_from'     => $locationFrom,
            'movement_to'       => $locationTo,
            'partner_type'      => 'LOC',
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $siteCode,
            'location_number'   => $locationTo,
            'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$locationTo') + $qtyBase"),
        ];
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
    $trNumber     = $hdrQ->tr_number;
    $isSupply     = ($hdrQ->tr_type === 'SUPPLY');
    $baseType     = $isSupply ? 'SUPPLY' : 'TRANSFER';
    $trType       = 'CANCEL ' . $baseType;
    $siteCode     = 'HO';
    $todayDate    = date('Y-m-d');
    $locationFrom = $hdrQ->location_from;
    $locationTo   = $hdrQ->location_to;
    $reason       = "($reasonLabel by $username)";

    $data = DB::table('transfer_stock_det')
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

    if ($data->isEmpty()) {
        return ['success' => false, 'message' => ["Transfer $trNumber gagal: tidak ada detail"]];
    }

    $seq             = (int) DB::table('warehouse_movement')->max('movement_code');
    $dataSetMovement = [];

    // ===== REVERSE SEMUA ARTIKEL (tanpa konversi RM->FG) =====
    foreach ($data as $val) {
        $qtyBase     = (float) $val->total_qty;
        $hargaPindah = $this->getAvgPrice($siteCode, $val->article_code, $locationTo);

        $this->tambahStockTanpaAvg($siteCode, $val->article_code, $locationFrom, $val->article_type, $val->stock_uom, $qtyBase);
        $this->kurangiStock($siteCode, $val->article_code, $locationTo, $val->article_type, $val->stock_uom, $qtyBase);

        $seq++;
        $dataSetMovement[] = [
            'movement_code'     => $seq,
            'movement_date'     => date('d-m-Y', strtotime($hdrQ->tr_date)),
            'artikel_code'      => $val->article_code,
            'artikel_desc'      => $val->article_desc ?? '',
            'movement_min'      => 0,
            'movement_plus'     => $qtyBase,
            'movement_price'    => $hargaPindah,
            'movement_transnno' => $trNumber,
            'movement_type'     => $trType,
            'movement_desc'     => $reason,
            'movement_from'     => $locationTo,
            'movement_to'       => $locationFrom,
            'partner_type'      => 'LOC',
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $siteCode,
            'location_number'   => $locationFrom,
            'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$locationFrom') + $qtyBase"),
        ];

        $seq++;
        $dataSetMovement[] = [
            'movement_code'     => $seq,
            'movement_date'     => date('d-m-Y', strtotime($hdrQ->tr_date)),
            'artikel_code'      => $val->article_code,
            'artikel_desc'      => $val->article_desc ?? '',
            'movement_min'      => $qtyBase,
            'movement_plus'     => 0,
            'movement_price'    => $hargaPindah,
            'movement_transnno' => $trNumber,
            'movement_type'     => $trType,
            'movement_desc'     => $reason,
            'movement_from'     => $locationTo,
            'movement_to'       => $locationFrom,
            'partner_type'      => 'LOC',
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $siteCode,
            'location_number'   => $locationTo,
            'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$locationTo') - $qtyBase"),
        ];
    }

    if (!empty($dataSetMovement)) {
        DB::table('warehouse_movement')->insert($dataSetMovement);
    }

    return ['success' => true, 'message' => "Stock $trNumber berhasil di-reverse"];
}

    // ===== HELPER METHODS =====

    private function getAvgPrice(string $siteCode, string $articleCode, string $location): float
    {
        return (float) DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->value('avg_price') ?? 0;
    }

    private function kurangiStock(string $siteCode, string $articleCode, string $location, string $deptCode, string $uom, float $qty): void
    {
        DB::table('warehouse_stock')->updateOrInsert(
            ['site_code' => $siteCode, 'article_code' => $articleCode, 'location_number' => $location],
            ['dept_code' => $deptCode, 'uom' => $uom]
        );

        DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->update(['article_qty' => DB::raw('coalesce(article_qty,0) - ' . $qty)]);
    }

    private function tambahStock(string $siteCode, string $articleCode, string $location, string $deptCode, string $uom, float $qtyMasuk, float $hargaMasuk): void
    {
        DB::table('warehouse_stock')->updateOrInsert(
            ['site_code' => $siteCode, 'article_code' => $articleCode, 'location_number' => $location],
            ['dept_code' => $deptCode, 'uom' => $uom]
        );

        $current = DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
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

        DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->update([
                'article_qty' => DB::raw('coalesce(article_qty,0) + ' . $qtyMasuk),
                'avg_price'   => $avgBaru,
            ]);
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

        $trNumber     = $hdrQ->tr_number;
        $isSupply = ($hdrQ->tr_type === 'SUPPLY');       // penentu reverse konversi
        $baseType = $isSupply ? 'SUPPLY' : 'TRANSFER';
        $trType   = 'CANCEL ' . $baseType;               // label: CANCEL TRANSFER / CANCEL SUPPLY
        $siteCode     = 'HO';
        $status       = '5'; // CANCELED
        $todayDate    = date('Y-m-d');
        $locationFrom = $hdrQ->location_from;
        $locationTo   = $hdrQ->location_to;
        $reason       = "(Cancel by $username, Reason: $request->reason)";

        DB::beginTransaction();
        try {
            // ===== AMBIL SEMUA DETAIL (sama seperti posting) =====
            $data = DB::table('transfer_stock_det')
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

            if ($data->isEmpty()) {
                DB::rollBack();
                return redirect()->back()->with([
                    'title'   => $title,
                    'alert'   => 'warning',
                    'message' => "$title $trNumber gagal: tidak ada detail",
                ]);
            }

            $seq             = (int) DB::table('warehouse_movement')->max('movement_code');
            $dataSetMovement = [];

            // ===== REVERSE SEMUA ARTIKEL (tanpa konversi RM->FG) =====
foreach ($data as $val) {
    $qtyBase     = (float) $val->total_qty;
    $hargaPindah = $this->getAvgPrice($siteCode, $val->article_code, $locationTo);

    // Kembalikan stock ke gudang asal (+)
    $this->tambahStockTanpaAvg(
        $siteCode, $val->article_code, $locationFrom,
        $val->article_type, $val->stock_uom, $qtyBase
    );

    // Kurangi stock di gudang tujuan (-)
    $this->kurangiStock(
        $siteCode, $val->article_code, $locationTo,
        $val->article_type, $val->stock_uom, $qtyBase
    );

    // Movement: balik MASUK ke gudang asal
    $seq++;
    $dataSetMovement[] = [
        'movement_code'     => $seq,
        'movement_date'     => date('d-m-Y', strtotime($hdrQ->tr_date)),
        'artikel_code'      => $val->article_code,
        'artikel_desc'      => $val->article_desc ?? '',
        'movement_min'      => 0,
        'movement_plus'     => $qtyBase,
        'movement_price'    => $hargaPindah,
        'movement_transnno' => $trNumber,
        'movement_type'     => $trType,
        'movement_desc'     => $reason,
        'movement_from'     => $locationTo,
        'movement_to'       => $locationFrom,
        'partner_type'      => 'LOC',
        'created_by'        => $username,
        'created_at'        => date('Y-m-d H:i:s'),
        'site_code'         => $siteCode,
        'location_number'   => $locationFrom,
        'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$locationFrom') + $qtyBase"),
    ];

    // Movement: balik KELUAR dari gudang tujuan
    $seq++;
    $dataSetMovement[] = [
        'movement_code'     => $seq,
        'movement_date'     => date('d-m-Y', strtotime($hdrQ->tr_date)),
        'artikel_code'      => $val->article_code,
        'artikel_desc'      => $val->article_desc ?? '',
        'movement_min'      => $qtyBase,
        'movement_plus'     => 0,
        'movement_price'    => $hargaPindah,
        'movement_transnno' => $trNumber,
        'movement_type'     => $trType,
        'movement_desc'     => $reason,
        'movement_from'     => $locationTo,
        'movement_to'       => $locationFrom,
        'partner_type'      => 'LOC',
        'created_by'        => $username,
        'created_at'        => date('Y-m-d H:i:s'),
        'site_code'         => $siteCode,
        'location_number'   => $locationTo,
        'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$locationTo') - $qtyBase"),
    ];
}

            // ===== UPDATE STATUS HEADER -> CANCELED =====
            DB::table('transfer_stock_hdr')
                ->where('tr_number', $trNumber)
                ->update([
                    'status'     => $status,
                    'note'       => DB::raw("CONCAT(note,';','$reason')"),
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // ===== INSERT SEMUA MOVEMENT =====
            if (!empty($dataSetMovement)) {
                DB::table('warehouse_movement')->insert($dataSetMovement);
            }

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

    // ===== HELPER: tambah stock TANPA hitung ulang avg_price (untuk reverse/cancel) =====
    private function tambahStockTanpaAvg(string $siteCode, string $articleCode, string $location, string $deptCode, string $uom, float $qty): void
    {
        DB::table('warehouse_stock')->updateOrInsert(
            ['site_code' => $siteCode, 'article_code' => $articleCode, 'location_number' => $location],
            ['dept_code' => $deptCode, 'uom' => $uom]
        );

        DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $articleCode)
            ->where('location_number', $location)
            ->update(['article_qty' => DB::raw('coalesce(article_qty,0) + ' . $qty)]);
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
            ->where('transfer_stock_hdr.id', $id)
            ->select(
                'transfer_stock_hdr.*',
                'locFrom.location_name',
                'locTo.location_name as location_name_to',
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

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode, $trNumber, $username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode, $trNumber, $username);

        $statusTr        = ['NEW', 'VALIDATED', 'APPROVED', 'POSTED', 'CANCELED'];
        $data['statusTr'] = $statusTr[$data['header']->status - 1];

        return view("transfer/transferStock.show", $data);
    }

        public function showEdit($key)
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

        return view("transfer/transferStock.edit", $data);
    }

        public function edit(Request $request)
        {
            return $this->showEdit($request->id);
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

        $title = "Save $this->title";

        // ===== Ambil header lama =====
        $hdr = DB::table('transfer_stock_hdr')->where('tr_number', $trNumber)->first();
        if (!$hdr) {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Data tidak ditemukan'],'alert'=>'error']);
        }
        if ($hdr->status == '5') {
            return response()->json(['status'=>0,'title'=>$title,'message'=>['Transfer sudah dicancel, tidak bisa diedit.'],'alert'=>'error']);
        }

        // ===== Validasi dasar =====
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

        // ===== tr_type & approver =====
        $locToType = DB::table('stock_location_master')->where('location_code', $locationTo)->value('location_type');
        $trType    = ($locToType === 'booth') ? 'SUPPLY' : 'TRANSFER';
        $approveDept = DB::table('stock_location_master')->where('location_code', $locationTo)->value('dept_code');

        DB::beginTransaction();
        try {
            // ===== 1) Reverse posting lama (kalau sudah POSTED) =====
        $reverse = $this->reverseStock($hdr, $username, 'Edit');
            if (!$reverse['success']) {
                DB::rollBack();
                return response()->json(['status'=>0,'title'=>$title,'message'=>(array) $reverse['message'],'alert'=>'error']);
            }

            // ===== 2) Update header (status di-reset dulu ke 1, nanti processPosting jadikan 4) =====
            DB::table('transfer_stock_hdr')
                ->where('tr_number', $trNumber)
                ->update([
                    'tr_date'       => $trDate,
                    'tr_type'       => $trType,
                    'status'        => '1',
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

            // ===== 4) Sinkron detail =====
            $keep = [];
            foreach ($articles as $val) {
                $keep[] = $trNumber . $val->article_code;
            }
          // ===== 4) Sinkron detail =====
DB::table('transfer_stock_det')
    ->where('tr_number', $trNumber)
    ->delete();

foreach ($articles as $val) {
    DB::table('transfer_stock_det')->insert([
        '           tr_number'   => $trNumber,
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
            // ===== 5) Posting ulang (validasi stok ada di dalam processPosting) =====
            $postResult = $this->processPosting($trNumber, $username);
            if (!$postResult['success']) {
                DB::rollBack();
                return response()->json(['status'=>0,'title'=>$title,'message'=>(array) $postResult['message'],'alert'=>'error']);
            }

            DB::commit();
            $message = "$title $trNumber is successfully updated & reposted";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status'=>1,'title'=>$title,'message'=>$message,'alert'=>'success','trNumber'=>$trNumber,'oEdit'=>true]);

        } catch (\Exception $e) {
            DB::rollBack();
            $message = "$title $trNumber is failed to update";
            \LogActivity::addToLog($title, "username: $username Status $message - ".$e->getMessage());
            return response()->json(['status'=>0,'title'=>$title,'message'=>[$message],'alert'=>'error']);
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

            // APPROVE: status 1/2, BUKAN pembuat, dan (dept tujuan atau super/acc)
        // if (in_array($st, ['1','2']) && !$isCreator && ($isDestDept || $isSuperAcc)) {
            //   $buttons .= '<a href="'. route('transferOut.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
            //                 <i data-feather="file-text"></i><span>'. __("Approve") .'</span></a>';
            //}

            // POSTING: status 3
            //if ($st == '3' && Auth::user()->can('transferOut-posting')) {
            //  $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                //    data-confirm='Are You Sure want to post This number?'
                //  data-confirm-yes='document.getElementById(\"delete-form-".$data->id."\").submit();'
                    //data-modal-id='".$data->id."'
                    //data-url='". route('transferOut.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    //<i data-feather='check' class='feather-14-red'></i><span>". __('Posting') ."</span></a>";
            //}

            // POSTING: status 3
            if (in_array($st, ['1','2']) && ($isDestDept || $isSuperAcc)) {
                $buttons .= "<a href='javascript:;' class='dropdown-item' data-size='sm' data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?'
                    data-confirm-yes='document.getElementById(\"delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('transferStock.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i><span>". __('Posting') ."</span></a>";
            }

            // EDIT
            if ($canEditDelete) {
                $buttons .= '<a href="'. route('transferStock.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="file-text"></i><span>'. __("Edit") .'</span></a>';
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

    }

