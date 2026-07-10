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

class TemporaryDnController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    private $siteCode;   // baru
    private $locationFg; // baru
    public function __construct()
    {
        $this->title = "Temporary DN";
        $this->moduleCode = "DN-UMUM";
        $this->decimalPlaces = config('globalParam.decimal');
        $this->siteCode = 'HO';   // baru - samakan dengan DeliveryController
        $this->locationFg = '007'; // baru - gudang FG, samakan dengan DeliveryController
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'tdn_number','name'=>'tdn_number','title'=>'Number'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'so_number','name'=>'so_number','title'=>'SO Number'],
            ['data'=>'delivery_number','name'=>'delivery_number','title'=>'DN Number'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'perihal','name'=>'perihal','title'=>'Perihal'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'updated_so_by','name'=>'updated_so_by','title'=>'Update SO By'],
            ['data'=>'updated_so_at','name'=>'updated_so_at','title'=>'Update SO Date'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'tdn_number','name'=>'tdn_number','title'=>'Delivery Number'],
            ['data'=>'so_number','name'=>'so_number','title'=>'SO Number'],
            ['data'=>'delivery_number','name'=>'delivery_number','title'=>'DN Number'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            // ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Description'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'perihal','name'=>'perihal','title'=>'Perihal'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'updated_so_by','name'=>'updated_so_by','title'=>'Update SO By'],
            ['data'=>'updated_so_at','name'=>'updated_so_at','title'=>'Update SO Date'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $username =  Auth::user()->username;
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['status'] = ['1'=>'OPEN','2'=>'SO','3'=>'CLOSED'];

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("temporaryDn.index",$data);
    }

    public function getLastCode($key)
    {
        DB::table('master_code')
        ->where('code_key',$key)
        ->update([
            'code_number' => DB::raw('code_number + 1'),
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $newCode = DB::table('master_code')
        ->where('code_key',$key)
        ->value('code_number'); 

        $newCode = str_pad($newCode,5,"0", STR_PAD_LEFT);
        $month = str_pad(date('n'),2,"0", STR_PAD_LEFT);
        $year = date('y');
        $prNumber="$key-$year-$month-$newCode";
        
        return $prNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $username =  Auth::user()->username;
        
        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // $data['articleList']= DB::table('article')
        // ->whereIn('article_type',['FG'])
        // ->orderBy('article_desc')
        // ->get();  
        
        $data['currentDate'] = date('d-m-Y');
        
        return view("temporaryDn.create",$data);
    }

    public function store(Request $request)
{
    $username     = Auth::user()->username;
    $articles     = json_decode($request->articles);
    $customerId   = $request->customerId;
    $deliveryDate = $request->deliveryDate;
    $perihal      = $request->perihal;
    $note         = $request->note;
    $status       = '1';
    $tDnNumber    = '';
    $leadCode     = $this->moduleCode;

    Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
        $query  = DB::table($parameters[0]);
        $column = $query->getGrammar()->wrap($parameters[1]);
        return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
    });

    $validation = Validator::make($request->all(), [
        'deliveryDate' => 'required',
        'customerId'   => 'required',
    ], [
        'required' => 'The field is required.',
    ]);

    $error_array = [];

    if ($validation->fails()) {
        foreach ($validation->messages()->getMessages() as $field_name => $messages) {
            $error_array[] = $messages;
        }
        return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
    }

    // Cek stock dulu sebelum ambil nomor & insert apapun (validasi awal saja).
    // Pemotongan stok TIDAK dilakukan di sini - itu terjadi saat posting (klik Print).
    //$stockErrors = $this->checkStockTdn($articles);
    //if (!empty($stockErrors)) {
      //  return response()->json(['status' => 0, 'message' => $stockErrors, 'alert' => 'warning']);
    //}

    $hasilUpdate = AppHelpers::resetCode($leadCode);
    $tDnNumber   = $this->getLastCode($leadCode);

    DB::beginTransaction();
    try {
        DB::table('temporary_dn_hdr')->insert([
            'tdn_number'        => $tDnNumber,
            'customer_id'       => $customerId,
            'delivery_date'     => $deliveryDate,
            'perihal'           => $perihal,
            'note'              => $note,
            'origin_tdn_number' => $tDnNumber,
            'status'            => $status,
            'created_by'        => $username,
            'updated_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        $dataSet = [];
        foreach ($articles as $val) {
            $dataSet[] = [
                'tdn_number'   => $tDnNumber,
                'article_code' => $val->article_code,
                'qty'          => $val->qty,
                'uom'          => $val->uom,
                'created_by'   => $username,
                'updated_by'   => $username,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
        }
        DB::table('temporary_dn_det')->insert($dataSet);

        // CATATAN: posting stok DIHAPUS dari sini. Stok dipotong saat klik Print
        // via method posting() supaya konsisten dengan alur receiving.

        DB::commit();

        $title   = "Save $this->title";
        $alert   = "success";
        $message = "$title $tDnNumber is successfully saved";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => $alert, 'tDnNumber' => $tDnNumber]);

    } catch (\Exception $e) {
        DB::rollBack();
        $title   = "Save $this->title";
        $alert   = "warning";
        $message = "$title $tDnNumber is failed to saved";
        \LogActivity::addToLog($title, "username: $username Status $message - Error: " . $e->getMessage());
        return response()->json(['status' => 0, 'title' => $title, 'message' => $message, 'alert' => $alert, 'tDnNumber' => $tDnNumber]);
    }
}

/**
 * Cek ketersediaan stok untuk sekumpulan article, tanpa mengubah apapun.
 * Dipanggil terpisah dari postingTdn() supaya validasi bisa dilakukan
 * SEBELUM nomor TDN diambil (menghindari nomor kepakai sia-sia kalau stok kurang).
 */
private function checkStockTdn($articles)
{
    $siteCode = $this->siteCode;
    $location = $this->locationFg;
    $stockErrors = [];

    foreach ($articles as $val) {
        $articleInfo = DB::table('article')
            ->where('article_code', $val->article_code)
            ->select('article_desc', 'article_alternative_code', 'article_type', 'uom')
            ->first();
        
           if (!$articleInfo) {
       $stockErrors[] = "Article {$val->article_code} tidak ditemukan";
       continue;
   }

        $totalQty = $val->qty; // qty asli, tanpa uom_conversion

        $stockNow = DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->value('article_qty') ?? 0;
        

        if ($stockNow < $totalQty) {
            $stockErrors[] = "Stock {$articleInfo->article_alternative_code} - {$articleInfo->article_desc} tidak cukup (stock: {$stockNow}, butuh: {$totalQty})";
        }
    }

    return $stockErrors;
}

/**
 * Memotong stok gudang FG dan mencatat warehouse_movement untuk sebuah TDN.
 * Dipanggil dari dalam transaction store(). Tidak melakukan validasi stok
 * lagi di sini - itu tanggung jawab checkStockTdn() yang dipanggil sebelumnya.
 */
/**
 * Posting stok untuk TDN. Dipanggil saat klik tombol Print (mirip receiving).
 *
 * GUARD anti double-potong:
 * Sebelum memotong stok, dicek apakah sudah pernah ada record warehouse_movement
 * bertipe 'TDN' untuk nomor ini. Kalau sudah ada, stok TIDAK dipotong lagi -
 * method tetap mengembalikan sukses supaya dokumen tetap bisa dicetak ulang.
 * Ini yang membuat klik Print berkali-kali AMAN: dokumen tercetak tiap kali,
 * tapi stok hanya berkurang sekali.
 */
public function posting(Request $request)
{
    $username     = Auth::user()->username;
    $tDnNumber    = $request->tDnNumber;
    $siteCode     = $this->siteCode;
    $location     = $this->locationFg;

    // Ambil header untuk validasi keberadaan & tanggal
    $tDnHdr = DB::table('temporary_dn_hdr')->where('tdn_number', $tDnNumber)->first();

    if (!$tDnHdr) {
        return response()->json([
            'status' => 0, 'title' => "Posting $this->title",
            'message' => "$tDnNumber tidak ditemukan", 'alert' => 'warning',
        ]);
    }

    $idKu        = Crypt::encryptString($tDnHdr->id);
    $deliveryDate = $tDnHdr->delivery_date;

    // ── GUARD: sudah pernah diposting? ──
    $sudahPosting = DB::table('warehouse_movement')
        ->where('movement_transnno', $tDnNumber)
        ->where('movement_type', 'DN SEMENTARA') // sebelumnya: 'TDN'
        ->count();

    if ($sudahPosting > 0) {
        // Sudah pernah dipotong stok. JANGAN potong lagi.
        // Tetap balikan sukses supaya dokumen bisa dicetak ulang.
        return response()->json([
            'status' => 1, 'title' => "Posting $this->title",
            'message' => "$tDnNumber sudah diposting sebelumnya (cetak ulang)",
            'alert' => 'success', 'tDnNumber' => $tDnNumber, 'idKu' => $idKu,
        ]);
    }

    // Ambil detail article dari TDN
    $articles = DB::table('temporary_dn_det')
        ->where('tdn_number', $tDnNumber)
        ->get();

    // Cek stok sebelum potong (jaga-jaga stok berubah sejak TDN dibuat)
   // $stockErrors = $this->checkStockTdn($articles);
    //if (!empty($stockErrors)) {
      //  return response()->json([
        //    'status' => 0, 'title' => "Posting $this->title",
          //  'message' => $stockErrors, 'alert' => 'warning', 'tDnNumber' => $tDnNumber,
        //]);
    //}

    DB::beginTransaction();
    try {
        $this->postingTdn($tDnNumber, $articles, $username, $deliveryDate);

        DB::commit();

        $title   = "Posting $this->title";
        $alert   = "success";
        $message = "$title $tDnNumber successfully posted";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json([
            'status' => 1, 'title' => $title, 'message' => $message,
            'alert' => $alert, 'tDnNumber' => $tDnNumber, 'idKu' => $idKu,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        $title   = "Posting $this->title";
        $alert   = "warning";
        $message = "$title $tDnNumber failed to post";
        \LogActivity::addToLog($title, "username: $username Status $message - Error: " . $e->getMessage());
        return response()->json([
            'status' => 0, 'title' => $title, 'message' => $message,
            'alert' => $alert, 'tDnNumber' => $tDnNumber, 'idKu' => $idKu,
        ]);
    }
}

/**
 * Worker: potong stok gudang FG + catat warehouse_movement.
 * TIDAK melakukan guard di sini - guard dilakukan oleh caller (posting()).
 * Dipanggil dari dalam transaction.
 */
private function postingTdn($tDnNumber, $articles, $username, $deliveryDate)
{
    $siteCode = $this->siteCode;
    $location = $this->locationFg;

    $movementSet = [];

    // Manual sequence: karena kolom movement_code sudah tidak punya sequence
    // yang valid di database (nextval mengarah ke sequence yang tidak ada),
    // nomor movement_code di-generate sendiri dari MAX(movement_code)+1.
    // Pola sama seperti ReceivingController::posting2()/cancel()/unPosting().
    $seq = (int) DB::table('warehouse_movement')->max('movement_code');

    foreach ($articles as $val) {
        $articleInfo = DB::table('article')
            ->where('article_code', $val->article_code)
            ->select('article_desc', 'article_type', 'uom')
            ->first();

        if (!$articleInfo) {
            throw new \Exception("Article {$val->article_code} tidak ditemukan");
        }

        $totalQty = $val->qty;

        DB::table('warehouse_stock')
            ->updateOrInsert(
                [
                    'site_code'       => $siteCode,
                    'article_code'    => $val->article_code,
                    'location_number' => $location,
                ],
                [
                    'dept_code' => $articleInfo->article_type,
                    'uom'       => $articleInfo->uom,
                ]
            );

        DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->update([
                'article_qty' => DB::raw('coalesce(article_qty,0) - ' . $totalQty),
            ]);

        $lastQtyAfter = DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->value('article_qty') ?? 0;

        $seq++; // increment manual, bukan nextval()

        $movementSet[] = [
            'movement_code'     => $seq,
            'movement_date'     => $deliveryDate,
            'artikel_code'      => $val->article_code,
            'artikel_desc'      => $articleInfo->article_desc,
            'movement_min'      => $totalQty,
            'movement_plus'     => 0,
            'movement_price'    => 0,
            'movement_transnno' => $tDnNumber,
            'movement_type'     => 'DN SEMENTARA',
            'movement_desc'     => $tDnNumber,
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $siteCode,
            'location_number'   => $location,
            'last_qty'          => $lastQtyAfter,
        ];
    }

    if (!empty($movementSet)) {
        DB::table('warehouse_movement')->insert($movementSet);
    }
}

     public function storePosting(Request $request)
    {
        $username    = Auth::user()->username;
        $articles    = json_decode($request->articles);
        $customerId  = $request->customerId;
        $deliveryDate = $request->deliveryDate;
        $perihal     = $request->perihal;
        $note        = $request->note;
        $status      = '1';
        $tDnNumber   = '';
        $leadCode    = $this->moduleCode;
        $siteCode    = $this->siteCode;
        $location    = $this->locationFg;
        $todayDate   = date('Y-m-d');
 
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query  = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });
 
        $validation = Validator::make($request->all(), [
            'deliveryDate' => 'required',
            'customerId'   => 'required',
        ], [
            'required' => 'The field is required.',
        ]);
 
        $error_array = [];
 
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
            return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
        }
 
        // CEK STOCK untuk semua article sebelum proses apapun
        $stockErrors = [];
        foreach ($articles as $val) {
            $articleInfo = DB::table('article')
                ->where('article_code', $val->article_code)
                ->select('article_desc', 'article_alternative_code', 'article_type', 'uom')
                ->first();
 
            $totalQty = DB::selectOne(
                "SELECT (? * uom_conversion(?, ?)) as total_qty",
                [$val->qty, $val->uom, $articleInfo->uom]
            )->total_qty;
 
            $stockNow = DB::table('warehouse_stock')
                ->where('site_code', $siteCode)
                ->where('article_code', $val->article_code)
                ->where('location_number', $location)
                ->value('article_qty') ?? 0;
 
            if ($stockNow < $totalQty) {
                $stockErrors[] = "Stock {$articleInfo->article_alternative_code} - {$articleInfo->article_desc} tidak cukup (stock: {$stockNow}, butuh: {$totalQty})";
            }
        }
 
        if (!empty($stockErrors)) {
            return response()->json(['status' => 0, 'message' => $stockErrors, 'alert' => 'warning']);
        }
 
        $hasilUpdate = AppHelpers::resetCode($leadCode);
        $tDnNumber   = $this->getLastCode($leadCode);
 
        DB::beginTransaction();
        try {
            DB::table('temporary_dn_hdr')->insert([
                'tdn_number'        => $tDnNumber,
                'customer_id'       => $customerId,
                'delivery_date'     => $deliveryDate,
                'perihal'           => $perihal,
                'note'              => $note,
                'origin_tdn_number' => $tDnNumber,
                'status'            => $status,
                'created_by'        => $username,
                'updated_by'        => $username,
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);
 
            $dataSet      = [];
            $movementSet  = [];
 
            foreach ($articles as $val) {
                $articleInfo = DB::table('article')
                    ->where('article_code', $val->article_code)
                    ->select('article_desc', 'article_alternative_code', 'article_type', 'uom')
                    ->first();
 
                $totalQty = DB::selectOne(
                    "SELECT (? * uom_conversion(?, ?)) as total_qty",
                    [$val->qty, $val->uom, $articleInfo->uom]
                )->total_qty;
 
                $dataSet[] = [
                    'tdn_number'   => $tDnNumber,
                    'article_code' => $val->article_code,
                    'qty'          => $val->qty,
                    'uom'          => $val->uom,
                    'created_by'   => $username,
                    'updated_by'   => $username,
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ];
 
                // Pastikan row ada di warehouse_stock
                DB::table('warehouse_stock')
                    ->updateOrInsert(
                        [
                            'site_code'       => $siteCode,
                            'article_code'    => $val->article_code,
                            'location_number' => $location,
                        ],
                        [
                            'dept_code' => $articleInfo->article_type,
                            'uom'       => $articleInfo->uom,
                        ]
                    );
 
                // Kurangi stock FG
                DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $location)
                    ->update([
                        'article_qty' => DB::raw('coalesce(article_qty,0) - ' . $totalQty),
                    ]);
 
                // Ambil last_qty setelah dikurangi untuk movement
                $lastQtyAfter = DB::table('warehouse_stock')
                    ->where('site_code', $siteCode)
                    ->where('article_code', $val->article_code)
                    ->where('location_number', $location)
                    ->value('article_qty') ?? 0;
 
                $movementSet[] = [
                    'movement_date'     => $deliveryDate,
                    'artikel_code'      => $val->article_code,
                    'artikel_desc'      => $articleInfo->article_desc,
                    'movement_min'      => $totalQty,
                    'movement_plus'     => 0,
                    'movement_price'    => 0,
                    'movement_transnno' => $tDnNumber,
                    'movement_type'     => 'TDN',
                    'movement_desc'     => $tDnNumber,
                    'created_by'        => $username,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'site_code'         => $siteCode,
                    'location_number'   => $location,
                    'last_qty'          => $lastQtyAfter,
                ];
            }
 
            DB::table('temporary_dn_det')->insert($dataSet);
            DB::table('warehouse_movement')->insert($movementSet);
 
            DB::commit();
 
            $title   = "Save $this->title";
            $alert   = "success";
            $message = "$title $tDnNumber is successfully saved";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => $alert, 'tDnNumber' => $tDnNumber]);
 
        } catch (Exception $e) {
            DB::rollBack();
            $title   = "Save $this->title";
            $alert   = "warning";
            $message = "$title $tDnNumber is failed to saved";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return response()->json(['status' => 0, 'title' => $title, 'message' => $message, 'alert' => $alert, 'tDnNumber' => $tDnNumber]);
        }
    }

    public function storeOld(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $customerId = $request->customerId;
        $deliveryDate = $request->deliveryDate;
        $perihal = $request->perihal;
        $note = $request->note;
        $status = '1';
        $tDnNumber ='';
        $leadCode = $this->moduleCode;
                
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "PO Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'deliveryDate'  => 'required',
            'customerId'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';

        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }

            $alert ="warning";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));

        }else{
            $hasilUpdate = AppHelpers::resetCode($leadCode);
            $tDnNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                DB::table('temporary_dn_hdr')->insert([
                    'tdn_number' => $tDnNumber,
                    'customer_id' => $customerId,
                    'delivery_date' => $deliveryDate,
                    'perihal' => $perihal,
                    'note' => $note,
                    'origin_tdn_number' => $tDnNumber,
                    'status' => $status,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $dataSet = [];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        'tdn_number' => $tDnNumber,
                        'article_code' => $val->article_code,
                        'qty' => $val->qty,
                        'uom' => $val->uom,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }

                DB::table('temporary_dn_det')->insert($dataSet);

                DB::commit();
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$title $tDnNumber is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $tDnNumber is failed to saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('temporary_dn_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'temporary_dn_hdr.customer_id')
        ->where('origin_tdn_number', function($query) use ($id){
            $query->select('tdn_number')->from('temporary_dn_hdr')->where('id',$id);
        })
        ->where('temporary_dn_hdr.status','!=','4')
        ->select('temporary_dn_hdr.*'
        ,DB::raw('(select sum(qty) from temporary_dn_det where tdn_number = temporary_dn_hdr.tdn_number) as sum_qty') 
        ,DB::raw('(select count(*) from temporary_dn_det where tdn_number = temporary_dn_hdr.tdn_number) as sum_row')
        ,DB::raw("concat(kode,'-',nama) as customer_name")
        )
        ->orderBy('id')
        ->get(); 

        $tDnNumber = $data['headers'][0]->tdn_number;
        
        $data['details'] = DB::table('temporary_dn_det')
        ->whereIn('temporary_dn_det.tdn_number', function($query) use ($tDnNumber){
            $query->select('tdn_number')->from('temporary_dn_hdr')->where('origin_tdn_number',$tDnNumber);
        })
        ->leftJoin('article','article.article_code','=','temporary_dn_det.article_code')
        ->select('temporary_dn_det'.'.*'
            ,DB::raw("concat(article_alternative_code,'-',article_desc) as article")
            ,DB::raw("(select STRING_AGG( (qty::real)::text,' -> ' ORDER BY tdn_number) AS main from temporary_dn_det p where article_code = temporary_dn_det.article_code and tdn_number like '$tDnNumber%' ) as notes")
        )
        ->orderBy('temporary_dn_det.tdn_number')
        ->orderBy('temporary_dn_det.id')
        ->get();       

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $status = ['OPEN','SALES ORDER','CLOSED','CANCELED'];
        $data['status'] = $status[$data['headers'][0]->status-1];

        return view("temporaryDn.show",$data);
        
    }

    public function edit(Request $request)
    {   
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('temporary_dn_hdr')
        ->where('id',$id)
        ->first();
    
        $tDnNumber = $data['header']->tdn_number;
        $custCode = $data['header']->customer_id;

        $data['details'] = DB::table('temporary_dn_det')
        ->where('tdn_number',$tDnNumber)
        ->orderBy('id')
        ->get(); 

        $dataQuery= DB::table('article') 
        ->whereIn('article.article_code', function($query) use ($custCode) {
            $query->select('article_code')
            ->from('bom_hdr') 
            ->where('status','3')
            ->where('customer',$custCode);
        })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->orderBy('article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose article</option>';

        foreach ($dataQuery as $row){
            $output .='<option value="'.$row->article_code.'" data-uom="'.$row->uom.'">'.$row->article_alternative_code.'-'. $row->article_desc.'</option>';
        }


        $data['articles'] = $output;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // dd($data['customers']);
        // dd($data['title']);

        $status = ['OPEN','SALES ORDER','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        
        return view("temporaryDn.edit",$data);
        
    }

    public function update(Request $request)
{
    $username =  Auth::user()->username;
    $articles = json_decode($request -> articles);
    $tDnNumber = $request->tDnNumber;
    $customerId = $request->customerId;
    $deliveryDate = $request->deliveryDate;
    $perihal = $request->perihal;
    $note = $request->note;

    $messages = [
        'required' => 'The field is required.',
        'unique' => 'The code has already been taken',
        // 'iunique' => "PO Number has already been taken",
    ];

    Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
        $query = DB::table($parameters[0]);
        $column = $query->getGrammar()->wrap($parameters[1]);
        return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
    });

    $validation = Validator::make($request->all(),$messages = [
        'deliveryDate'  => 'required',
        'customerId'  => 'required',
    ]);

    $error_array = array();
    $success_output = '';

    if ($validation->fails()){
        foreach ($validation->messages()->getMessages() as $field_name => $messages){
            $error_array[] = $messages;
        }

        $title="Save Purchase Request";
        $alert ="error";
        return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
    }

    // ── GUARD: tolak edit jika TDN sudah diposting (stok sudah dipotong) ──
    // Sama seperti createDn()/destroy(), penandanya adalah keberadaan movement 'DN SEMENTARA'.
    // Kalau sudah diposting, mengubah qty di sini akan bikin qty dokumen tidak sinkron
    // dengan stok yang sudah terpotong. User harus Cancel dulu kalau mau mengubah.
    $sudahPosting = DB::table('warehouse_movement')
        ->where('movement_transnno', $tDnNumber)
        ->where('movement_type', 'DN SEMENTARA')
        ->count();

    if ($sudahPosting > 0) {
        $title   = "Update $this->title";
        $alert   = "warning";
        $message = "TDN $tDnNumber sudah diposting, tidak bisa diedit. Silakan Cancel terlebih dahulu jika ingin mengubah.";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert' => $alert,'tDnNumber' => $tDnNumber));
    }

    DB::beginTransaction();
    try {
        $row_affected=DB::table('temporary_dn_hdr')
        ->where('tdn_number',$tDnNumber)
        ->update(
            [
                'customer_id' => $customerId,
                'delivery_date' => $deliveryDate,
                'perihal' => $perihal,
                'note' => $note,
                'updated_by' => $username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        $dataSet = [];
        foreach ($articles as $val) {
            $dataSet[] = [
                $tDnNumber.$val->article_code
            ];
        }

        // Delete kalo article tidak ada di $tDnNumber dan article nya $val->article_code
        DB::table('temporary_dn_det')
            ->whereNotIn(DB::raw("CONCAT(tdn_number,article_code)"),$dataSet)
            ->where('tdn_number',$tDnNumber)
            ->delete();

        foreach ($articles as $val) {
            DB::table('temporary_dn_det')
            ->updateOrInsert(
                ['tdn_number' => $tDnNumber,'article_code' => $val->article_code],
                [
                    'tdn_number' => $tDnNumber,
                    'article_code' => $val->article_code,
                    'qty' => $val->qty,
                    'uom' => $val->uom,
                    'created_by' => $username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }

        DB::commit();

        $title ="Save $this->title";
        $alert ="success";
        $message = "$title $tDnNumber is successfully updated";
        \LogActivity::addToLog($title,"username: $username Status $message");
        return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));

    } catch (\Exception $e) {
        DB::rollBack();
        $title ="Save $this->title";
        $alert  ="warning";
        $message  = "$title $tDnNumber is failed to updated";
        \LogActivity::addToLog($title,"username: $username Status $message - Error: " . $e->getMessage());
        return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));
    }
}
    


    public function updateOld(Request $request)
    {

        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $tDnNumber = $request->tDnNumber;
        $customerId = $request->customerId;
        $deliveryDate = $request->deliveryDate;
        $perihal = $request->perihal;
        $note = $request->note;
               
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "PO Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'deliveryDate'  => 'required',
            'customerId'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            
            $title="Save Purchase Request";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {               
                $row_affected=DB::table('temporary_dn_hdr')
                ->where('tdn_number',$tDnNumber)
                ->update(
                    [
                        'customer_id' => $customerId,
                        'delivery_date' => $deliveryDate,
                        'perihal' => $perihal,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                $dataset=[];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        $tDnNumber.$val->article_code
                    ];
                    
                }

                /*
                    Delete kalo article tidak ada di $tDnNumber dan article nya $val->article_code
                    berdasarkan 2 kondisi
                */

                DB::table('temporary_dn_det')
                    ->whereNotIn(DB::raw("CONCAT(tdn_number,article_code)"),$dataSet)
                    ->where('tdn_number',$tDnNumber)
                    ->delete();

                foreach ($articles as $val) {
                    DB::table('temporary_dn_det')
                    ->updateOrInsert(
                        ['tdn_number' => $tDnNumber,'article_code' => $val->article_code],
                        [
                            'tdn_number' => $tDnNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                
                DB::commit();

                $title ="Save $this->title";
                $alert ="success";
                $message = "$title $tDnNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));

            } catch (Exception $e) {
                
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $tDnNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));
            }
        }
    }

    public function updateSo(Request $request)
    {   
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Update SO $this->title";
        $data['subtitle'] = "Update SO $this->title";

        $data['header'] = DB::table('temporary_dn_hdr')
        ->select('temporary_dn_hdr.*'
        ,DB::raw('(select sum(qty) from temporary_dn_det where tdn_number = temporary_dn_hdr.tdn_number) as sum_qty') 
        ,DB::raw('(select count(*) from temporary_dn_det where tdn_number = temporary_dn_hdr.tdn_number) as sum_row')
        )
        ->where('id',$id)
        ->first();
    
        $tDnNumber = $data['header']->tdn_number;
        $custCode = $data['header']->customer_id;
        $soNumber = $data['header']->so_number;

        $data['details'] = DB::table('temporary_dn_det')
        ->leftJoin('article','article.article_code','=','temporary_dn_det.article_code')
        ->select('temporary_dn_det'.'.*'
            ,DB::raw("concat(article_alternative_code,'-',article_desc) as article")
        )
        ->where('tdn_number',$tDnNumber)
        ->orderBy('id')
        ->get(); 

        $dataQuery= DB::table('article') 
        ->whereIn('article.article_code', function($query) use ($custCode) {
            $query->select('article_code')
            ->from('bom_hdr') 
            ->where('status','3')
            ->where('customer',$custCode);
        })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->orderBy('article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose article</option>';

        foreach ($dataQuery as $row){
            $output .='<option value="'.$row->article_code.'" data-uom="'.$row->uom.'">'.$row->article_alternative_code.'-'. $row->article_desc.'</option>';
        }

        $data['articles'] = $output;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        /*
            16/4/2024
            kalo begini tidak bisa pak, karena SO yng sudah dibuat DN tapi belum close juga kan masih bisa di pakai pak jadi harus muncul.
            yang jangan muncul itu yang status SO nya sudah close semua

            1 SO Boleh ada di beberapa Temporary DN

        */

        $data['soNumbers'] = DB::table('sales_order_hdr')
        ->where ('customer_id','=',$custCode)
        // ->whereNotIn('so_code', function($query) use ($custCode,$soNumber) {
        //     $query->select(db::raw("coalesce(so_number,'')"))
        //     ->from('temporary_dn_hdr') 
        //     ->where('customer_id',$custCode)
        //     ->where('so_number','<>',$soNumber);
        // })
        ->whereIn("status",['2','3'])
        ->where(db::raw("(SELECT count(*) from sales_order_det where so_code = sales_order_hdr.so_code and status = '1')"),">",0)
        ->orderBy('id')
        ->get();

        $status = ['OPEN','SALES ORDER','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("temporaryDn.updateSo",$data);
        
    }

    public function updateSoUpdate(Request $request)
    {
        $username =  Auth::user()->username;
        $tDnNumber = $request->tDnNumber;
        $customerId = $request->customerId;
        $soNumber = $request->soNumber;
                       
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "PO Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'soNumber'  => 'required',
            'tDnNumber'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save Purchase Request";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {               
                $row_affected=DB::table('temporary_dn_hdr')
                ->where('tdn_number',$tDnNumber)
                ->update(
                    [
                        'so_number' => $soNumber,
                        'status' => '2',
                        'updated_so_by' => Auth::user()->username,
                        'updated_so_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                DB::commit();

                $title ="Update SO $this->title";
                $alert ="success";
                $message = "$title $tDnNumber, $soNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));

            } catch (Exception $e) {
                
                DB::rollBack();
                $title ="Update SO $this->title";
                $alert  ="warning";
                $message  = "$title $tDnNumber, $soNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tDnNumber'=>$tDnNumber));
            }
        }
    }

    public function destroy(Request $request)
{
    $id        = Crypt::decryptString($request->id);
    $username  = Auth::user()->username;
    $siteCode  = $this->siteCode;
    $location  = $this->locationFg;

    $tDnHdr    = DB::table('temporary_dn_hdr')->where('id', $id)->first();
    $tDnNumber = $tDnHdr->tdn_number;
    $soNumber  = $tDnHdr->so_number;

    // Cek apakah TDN ini pernah diposting (stok pernah dipotong).
    // Kalau belum pernah, cancel TIDAK boleh mengembalikan stok
    // (karena tidak ada yang dipotong -> mengembalikan malah bikin stok kelebihan).
    $sudahPosting = DB::table('warehouse_movement')
        ->where('movement_transnno', $tDnNumber)
        ->where('movement_type', 'DN SEMENTARA')
        ->count();

    DB::beginTransaction();
    try {
        $rowAffected = DB::table('temporary_dn_hdr')
            ->where('id', $id)
            ->update([
                'status'            => '4',
                'tdn_number'        => $tDnNumber . "(C)",
                'so_number'         => $soNumber . "(C)",
                'origin_tdn_number' => $tDnNumber . "(C)",
                'reason'            => "Cancel",
                'updated_by'        => $username,
                'updated_at'        => date('Y-m-d H:i:s'),
            ]);

        if ($rowAffected > 0) {
            DB::table('temporary_dn_det')
                ->where('tdn_number', $tDnNumber)
                ->update([
                    'tdn_number' => $tDnNumber . "(C)",
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            // Kembalikan stok HANYA jika sebelumnya sudah diposting.
            if ($sudahPosting > 0) {
                $details = DB::table('temporary_dn_det as d')
                    ->leftJoin('article', 'article.article_code', '=', 'd.article_code')
                    ->where('d.tdn_number', $tDnNumber . "(C)")
                    ->select(
                        'd.article_code',
                        'd.qty',
                        'd.uom',
                        'article.article_desc',
                        'article.article_type',
                        'article.uom as uom_article',
                        'd.qty as total_qty' // sebelumnya: DB::raw("(d.qty * uom_conversion(d.uom, article.uom))")
                    )
                    ->get();

                    $seq = (int) DB::table('warehouse_movement')->max('movement_code');

                $reverseMovements = [];
                foreach ($details as $det) {
                    // Kembalikan stock ke warehouse_stock
                    DB::table('warehouse_stock')
                        ->where('site_code', $siteCode)
                        ->where('article_code', $det->article_code)
                        ->where('location_number', $location)
                        ->update([
                            'article_qty' => DB::raw('coalesce(article_qty,0) + ' . $det->total_qty),
                        ]);

                    $lastQtyAfter = DB::table('warehouse_stock')
                        ->where('site_code', $siteCode)
                        ->where('article_code', $det->article_code)
                        ->where('location_number', $location)
                        ->value('article_qty') ?? 0;

                         $seq++;
                    $reverseMovements[] = [
                         'movement_code'     => $seq,
                        'movement_date'     => date('d-m-Y'),
                        'artikel_code'      => $det->article_code,
                        'artikel_desc'      => $det->article_desc,
                        'movement_min'      => 0,
                        'movement_plus'     => $det->total_qty,
                        'movement_price'    => 0,
                        'movement_transnno' => $tDnNumber . "(C)",
                        'movement_type'     => 'CANCEL DN SEMENTARA', // sebelumnya: 'TDN-CANCEL'
                        'movement_desc'     => "Cancel TDN: $tDnNumber",
                        'created_by'        => $username,
                        'created_at'        => date('Y-m-d H:i:s'),
                        'site_code'         => $siteCode,
                        'location_number'   => $location,
                        'last_qty'          => $lastQtyAfter,
                    ];
                }

                if (!empty($reverseMovements)) {
                    DB::table('warehouse_movement')->insert($reverseMovements);
                }
            }

            DB::commit();

            $title   = "Delete $this->title";
            $alert   = "success";
            $message = "$title $tDnNumber Successfully Canceled";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);

        } else {
            DB::rollBack();
            $title   = "Delete $this->title";
            $alert   = "warning";
            $message = "$title $tDnNumber Failed to Cancel";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }

    } catch (\Exception $e) {
        DB::rollBack();
        $title   = "Delete $this->title";
        $alert   = "warning";
        $message = "$title $tDnNumber Failed to Cancel";
        \LogActivity::addToLog($title, "username: $username Status $message - Error: " . $e->getMessage());
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
    }
}

    public function destroyOld(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;       
        $tDnHdr = DB::table('temporary_dn_hdr')->where('id',$id)->first();

        $tDnNumber = $tDnHdr->tdn_number;
        $soNumber = $tDnHdr->so_number;

        DB::beginTransaction();
        try {
                $rowAffected=DB::table('temporary_dn_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => '4',
                        'tdn_number' => $tDnNumber."(C)",
                        'so_number' => $soNumber."(C)",
                        'origin_tdn_number' => $tDnNumber."(C)",
                        'reason' => "Cancel",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if($rowAffected>0){
                    DB::table('temporary_dn_det')
                    ->where('tdn_number',$tDnNumber)
                    ->update(
                    [
                        'tdn_number' => $tDnNumber."(C)",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                    );
                    DB::commit();
                    $title ="Delete $this->title";
                    $alert  ="success";
                    $message  = "$title $tDnNumber Successfully Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }else{
                    DB::rollBack();
                    $title ="Delete $this->title";
                    $alert  ="warning";
                    $message  = "$title $tDnNumber Failed to Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

                }

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $tDnNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function closed(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $tDnNumber = DB::table('temporary_dn_hdr')->where('id',$id)->value('tdn_number');
        $status = '3';
        DB::beginTransaction();
        try {
                $row_affected=DB::table('temporary_dn_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                DB::commit();
                $title ="Close $this->title";
                $alert  ="success";
                $message  = "$title $tDnNumber Successfully Closed";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Close $this->title";
            $alert  ="warning";
            $message  = "$title $tDnNumber Failed to Close";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function createDn(Request $request)
{
    $username  = Auth::user()->username;
    $id        = Crypt::decryptString($request->id);
    $siteCode  = $this->siteCode;
    $location  = $this->locationFg;

    $tDnHeader = DB::table('temporary_dn_hdr')->where('id', $id)->first();
    $tDnNumber = $tDnHeader->tdn_number;
    $dnDate    = $tDnHeader->delivery_date;
    $soNumber  = $tDnHeader->so_number;
    $poNumber  = DB::table('sales_order_hdr')->where('so_code', $soNumber)->value('po_number');
    $dnNew     = "";
    $pesan     = "";

    // Cek apakah semua article di TDN ada di SO
    $cekArticle = DB::table('temporary_dn_det as a')
        ->leftJoin('temporary_dn_hdr as b', 'a.tdn_number', 'b.tdn_number')
        ->leftJoin('sales_order_det as c', function ($join) {
            $join->on('c.so_code', '=', 'b.so_number');
            $join->on('c.article_code', '=', 'a.article_code');
        })
        ->where('a.tdn_number', $tDnNumber)
        ->whereNull('c.article_code')
        ->count();

    // Cek apakah qty SO masih ada
    $cekSelisihQuery = DB::select("SELECT count(*) as jumlah from
        (select *,
        (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)) as qty_so,
        coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code group by article_code),0) as qty_delivery,
        (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)-
        coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code group by article_code),0)) as qty_selisih
        from temporary_dn_det a
        left join temporary_dn_hdr b on a.tdn_number = b.tdn_number
        where a.tdn_number = '$tDnNumber'
        ) as oki
        where qty_selisih <= 0");

    $adaSelisih = $cekSelisihQuery[0]->jumlah;

    if ($cekArticle > 0) {
        $pesan .= ", Article di TDN tidak ada di SO";
    }
    if ($adaSelisih > 0) {
        $pesan .= ", QTY SO sudah habis";
    }

    if ($pesan != '') {
        $title   = "Create DN $this->title";
        $alert   = "warning";
        $message = "$title dari $tDnNumber Gagal: $pesan";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
    }

    // ── GUARD: pastikan TDN sudah pernah diposting (stok sudah dipotong) ──
    // createDn() TIDAK memotong stok, hanya relabel movement 'DN SEMENTARA' -> 'Delivery'.
    // Kalau TDN belum pernah diposting (belum ada movement 'DN SEMENTARA'), maka:
    //  - tidak ada yang bisa direlabel, DAN
    //  - DN baru akan berstatus POSTED (4) tanpa stok pernah dipotong -> stok kelebihan.
    // Jadi harus ditolak: user wajib Print/posting dulu.
    $sudahPosting = DB::table('warehouse_movement')
        ->where('movement_transnno', $tDnNumber)
        ->where('movement_type', 'DN SEMENTARA')
        ->count();

    if ($sudahPosting == 0) {
        $title   = "Create DN $this->title";
        $alert   = "warning";
        $message = "$title dari $tDnNumber gagal: TDN belum diposting. Silakan Print (posting) terlebih dahulu sebelum membuat DN.";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
    }

    $periodNomor = (int) explode('-', $dnDate)[1];
    $dnNew       = app('App\Http\Controllers\DeliveryController')->getLastCode('DN', $periodNomor);

    DB::beginTransaction();
    try {
        // Insert delivery_hdr dari data TDN
        $sqlHdr = "INSERT into delivery_hdr
            (delivery_number, origin_delivery_number, delivery_date, customer_id, so_number, po_number, status, note, created_by, updated_by, created_at, updated_at)
            select
            '$dnNew', '$dnNew', delivery_date, customer_id, so_number, '$poNumber', '4', note, '$username', '$username', '" . date('Y-m-d H:i:s') . "', '" . date('Y-m-d H:i:s') . "'
            from temporary_dn_hdr where tdn_number = '$tDnNumber'";

        $sqlDet = "INSERT into delivery_det
            (delivery_number, article_code, so_number, po_number, qty, uom, created_by, created_at, qty_so)
            select
            '$dnNew',
            a.article_code,
            '$soNumber',
            '$poNumber',
            a.qty,
            a.uom,
            '$username',
            '" . date('Y-m-d H:i:s') . "',
            (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)
            - coalesce((select sum(qty) from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code),0)) as qty_so
            from temporary_dn_det a
            left join temporary_dn_hdr b on b.tdn_number = a.tdn_number
            where a.tdn_number = '$tDnNumber'";

        $rowAffected = DB::select($sqlHdr);

        if ($rowAffected !== false) {
            DB::select($sqlDet);

            // Relabel movement TDN -> DN baru. TIDAK potong stok lagi.
            // 'DN SEMENTARA' cocok dengan yang ditulis postingTdn(); target 'Delivery'
            // (kapital awal) agar konsisten dengan DeliveryController::posting().
            DB::table('warehouse_movement')
                ->where('movement_transnno', $tDnNumber)
                ->where('movement_type', 'DN SEMENTARA')
                ->update([
                    'movement_transnno' => $dnNew,
                    'movement_type'     => 'Delivery',
                    'movement_desc'     => "Generate dari DN Sementara dengan Nomor: $tDnNumber",
                ]);

            DB::table('temporary_dn_hdr')
                ->where('tdn_number', $tDnNumber)
                ->update([
                    'status'          => '3',
                    'delivery_number' => $dnNew,
                    'updated_by'      => $username,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);

            $idDelivery = DB::table('delivery_hdr')->where('delivery_number', $dnNew)->value('id');
            $idKu       = Crypt::encryptString($idDelivery);

            DB::commit();

            $title   = "Create DN $this->title";
            $alert   = "success";
            $message = "$title $dnNew from $tDnNumber Successfully Created";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message, 'idDelivery' => $idKu]);

        } else {
            DB::rollBack();
            $noAkhirDn = explode('/', $dnNew)[4];
            DB::table('master_code')
                ->where('code_key', 'DN')
                ->where('code_number', $noAkhirDn)
                ->update([
                    'code_number' => $noAkhirDn - 1,
                    'updated_by'  => $username,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

            $title   = "Create DN $this->title";
            $alert   = "warning";
            $message = "$title $dnNew from $tDnNumber Failed - Insert HDR Error";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }

    } catch (\Exception $e) {
        DB::rollBack();
        $title   = "Create DN $this->title";
        $alert   = "warning";
        $message = "$title $dnNew from $tDnNumber Failed - Exception: " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
    }
}

    public function createDnOld(Request $request)
    {

        /*
            pada saat pembuatan DN dari temporary cek dulu QTY SO nya apakah sudah sesuai atau belum
            dengan kedaan pasa saat DN dibuat
            Cek apakah article yang ada di temporary semua ada di SO ?
            Cek apakah qty temporary melebihi sisa SO

        */

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $tDnHeader = DB::table('temporary_dn_hdr')->where('id',$id)->first();
        $tDnNumber = $tDnHeader->tdn_number; 
        $dnDate = $tDnHeader->delivery_date;
        $soNumber = $tDnHeader->so_number;
        $poNumber = db::table('sales_order_hdr')->where('so_code',$soNumber)->value('po_number');
        $dnNew ="";
        $pesan ="";
        $hasilPosting = "";
        $idDelivery = "";
        $idKu = "";

        /*
            Cek apakah article yang ada di temporary semua ada di SO ?
        */

        $cekArticle = DB::table('temporary_dn_det as a')
        ->leftJoin('temporary_dn_hdr as b','a.tdn_number','b.tdn_number')
        ->leftJoin('sales_order_det as c', function ($join) {
            $join->on('c.so_code', '=', 'b.so_number');
            $join->on('c.article_code', '=', 'a.article_code');
        })
        ->where('a.tdn_number',$tDnNumber)
        ->where('c.article_code','=',null)
        ->count();

        /*
            Cek apakah qty SO masih ada atau sudah kosong
        */

        $cekSelisihQuery = DB::select("SELECT count(*) as jumlah from
        (select *, 
        (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)) as qty_so,
        coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code group by article_code),0) as qty_delivery,
        (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)-
        coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number and status not in ('5','7')) and article_code = a.article_code group by article_code),0)) as qty_selisih
        from temporary_dn_det a
        left join temporary_dn_hdr b on a.tdn_number = b.tdn_number
        where a.tdn_number = '$tDnNumber'
        ) as oki
        where qty_selisih <= 0
        ");

        $adaSelisih = $cekSelisihQuery[0]->jumlah;

        if ($cekArticle > 0 ){
            $pesan .= ", Article di DN tidak ada di SO";
        }

        if ($adaSelisih > 0 ){
            $pesan .= ", QTY SO sudah habis";
        }

        // dd($pesan);

        if($pesan == ''){

            $periodNomor=(int)explode('-', $dnDate)[1];
            $dnNew = app('App\Http\Controllers\DeliveryController')->getLastCode('DN',$periodNomor);
            
            DB::beginTransaction();
            try {
                $sqlHdr = "INSERT into delivery_hdr 
                (
                    delivery_number,
                    origin_delivery_number,
                    delivery_date,
                    customer_id,
                    so_number,
                    po_number,
                    status,
                    note,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at
                )
                select 
                '$dnNew',
                '$dnNew',
                delivery_date,
                customer_id,
                so_number,
                '$poNumber',
                '1',
                note,
                '$username',
                '$username',
                '".date('Y-m-d H:i:s')."',
                '".date('Y-m-d H:i:s')."'
                from temporary_dn_hdr where tdn_number = '$tDnNumber'";
            
                $sqlDet="INSERT into delivery_det
                (
                    delivery_number,
                    article_code,
                    so_number,
                    po_number,
                    qty,
                    uom,
                    created_by,
                    created_at,
                    qty_so
                )
                select 
                    '$dnNew',
                    article_code,
                    '$soNumber',
                    '$poNumber',
                    qty,
                    uom,
                    '$username',
                    '".date('Y-m-d H:i:s')."',
                    (select coalesce((select qty from sales_order_det where so_code = b.so_number and article_code = a.article_code),0)-
                    coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = b.so_number) and article_code = a.article_code group by article_code),0)) as qty_so
                from temporary_dn_det a
                left join temporary_dn_hdr b on b.tdn_number = a.tdn_number
                where a.tdn_number = '$tDnNumber'";

                $rowAffected =  DB::select($sqlHdr);
                if ($rowAffected){
                    DB::select($sqlDet);
                    $row_affected=DB::table('temporary_dn_hdr')
                    ->where('tdn_number',$tDnNumber)
                    ->update(
                        [
                            'status' => '3',
                            'delivery_number' => $dnNew,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $idDelivery = db::table('delivery_hdr')->where('delivery_number',$dnNew)->value('id');

                    $idKu = Crypt::encryptString($idDelivery);

                    $hasilPosting = app('App\Http\Controllers\DeliveryController')->postingFromOther($idDelivery);
                    
                    DB::commit();
                    $title ="Create DN $this->title";
                    $alert  ="success";
                    $message  = "$title $dnNew from $tDnNumber Successfully Create DN";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'hasilPosting'=>$hasilPosting,'idDelivery' => $idKu]);
                }else{
                    DB::rollBack();
                    // 'DN/ASN/24/04/2379
                    $noAkhirDn = explode('/', $dnNew)[4];
                    $row_affected=DB::table('master_code')
                    ->where('code_key','DN')
                    ->where('code_number',$noAkhirDn)
                    ->update(
                        [
                            'code_number' => $noAkhirDn-1,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $title ="Create DN $this->title";
                    $alert  ="warning";
                    $message  = "$title $dnNew from $tDnNumber Failed Create DN Insert to HDR";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'hasil'=>$hasilPosting,'idDelivery' => $idKu]);
                }
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Create DN $this->title";
                $alert  ="warning";
                $message  = "$title $dnNew from $tDnNumber Failed2 Create DN Error Query";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'hasil'=>$hasilPosting,'idDelivery' => $idKu]);
            }
        }else{
            $title ="Create DN $this->title";
            $alert  ="warning";
            $message  = "$title $dnNew from $tDnNumber Failed3 Create DN Warning message";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message.$pesan,'hasil'=>$hasilPosting,'idDelivery' => $idKu]);
        }
    }

    public function list(Request $request)
    {
        // status:
        // 1 = Open
        // 2 = Sales Order
        // 3 = Closed
        // 4 = Canceled
        
        $username =  Auth::user()->username;
        $searchDn = strtolower($request->searchDn);
        $searchStatus = $request->searchStatus;
        $deliveryDate = $request->deliveryDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate ="";
        $toDate = "";       
 
        if ($deliveryDate){
            $date = explode("to",$deliveryDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
            // $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            // $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('temporary_dn_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'temporary_dn_hdr.customer_id')
        ->where(function ($query) use ($searchDn,$searchStatus,$deliveryDate,$fromDate,$toDate,$searchCustomer) {
            $searchDn ? $query->where('temporary_dn_hdr.tdn_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('temporary_dn_hdr.status',$searchStatus) : '';
            $deliveryDate ? $query->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchCustomer ? $query->where('temporary_dn_hdr.customer_id',$searchCustomer) : '';
        })
        ->where('temporary_dn_hdr.status','!=','4')
        ->select('temporary_dn_hdr.*',DB::raw("concat(kode,'-',nama) as customer_name"))
        ->orderBy('id')
        ->get(); 
             
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ($data->status == '1') {
            $buttons .= '<a href="'. route('suratJalanSementara.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="file-text"></i>
                               Edit
                        </a>';
            }

            if ($data->status == '1' || $data->status == '2') {
                $buttons .= '<a href="'. route('suratJalanSementara.updateSo', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="file-text"></i>
                                   Update SO
                            </a>';
            }

            $buttons .= '<a href="'. route('suratJalanSementara.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                            <i data-feather="printer"></i>
                            Print
                        </a>';

            $buttons .= '<a href="'. route('suratJalanSementara.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            Detail
                         </a>';

            if ( $data->status == '2' ){
            // if (Auth::user()->can('purchaseOrder-delete')) {
                $buttons .="<a href='javascript:;'
                class='dropdown-item' 
                data-size='sm'
                data-ajax-delete='true'
                data-confirm='Are You Sure want to Create Delivery Note?' 
                data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                data-modal-id='".$data->id."'
                id='deleteButton'
                data-url='". route('suratJalanSementara.createDn', ['id'=>Crypt::encryptString($data->id)]) ."'>
                <i data-feather='file-text'></i>
                <span>". __('Create DN') ."</span>
                </a>";
            // }
            }
                
            if ($data->status != '3') {
                $buttons .= "<a href='javascript:;'
                                class='dropdown-item' 
                                data-size='sm'
                                data-ajax-delete='true'
                                data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                data-modal-id='".$data->id."'
                                id='deleteButton'
                                data-url='". route('suratJalanSementara.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                <span>". __('Cancel') ."</span>
                            </a>";
            }
            
            // if ( $data->status == '2' ){
            //     // if (Auth::user()->can('purchaseOrder-delete')) {
            //         $buttons .="<a href='javascript:;'
            //         class='dropdown-item' 
            //         data-size='sm'
            //         data-ajax-delete='true'
            //         data-confirm='Are You Sure want to Close?|This action can not be undone. Do you want to continue?' 
            //         data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
            //         data-modal-id='".$data->id."'
            //         id='deleteButton'
            //         data-url='". route('suratJalanSementara.close', ['id'=>Crypt::encryptString($data->id)]) ."'>
            //         <i data-feather='x' class='feather-14-red'></i>
            //         <span>". __('Close') ."</span>
            //         </a>";
            //     // }
            // }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $statusPr = ['OPEN','SO','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPr[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','tdn_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $username =  Auth::user()->username;
        $searchDn = strtolower($request->searchDn);
        $searchStatus = $request->searchStatus;
        $deliveryDate = $request->deliveryDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate ="";
        $toDate = "";

        if ($deliveryDate){
            $date = explode("to",$deliveryDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }      
    
        $data = DB::table('temporary_dn_det')
        ->leftJoin('temporary_dn_hdr','temporary_dn_hdr.tdn_number','temporary_dn_det.tdn_number')
        ->leftJoin('article','article.article_code','temporary_dn_det.article_code')
        ->leftJoin('third_party','third_party.kode','temporary_dn_hdr.customer_id')
        ->where(function ($query) use ($searchDn,$searchStatus,$deliveryDate,$fromDate,$toDate,$searchCustomer) {
            $searchDn ? $query->where('temporary_dn_hdr.tdn_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('temporary_dn_hdr.status',$searchStatus) : '';
            $deliveryDate ? $query->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchCustomer ? $query->where('temporary_dn_hdr.customer_id',$searchCustomer) : '';
        })
        ->where('temporary_dn_hdr.status','!=','4')
        ->select('temporary_dn_det.*'
            ,'article_alternative_code'
            ,'article.article_desc'
            ,'temporary_dn_hdr.status'
            ,'temporary_dn_hdr.delivery_date'
            ,'temporary_dn_hdr.perihal'
            ,'temporary_dn_hdr.note'
            ,'temporary_dn_hdr.updated_so_by'
            ,'temporary_dn_hdr.updated_so_at'
            ,'temporary_dn_hdr.so_number'
            ,'temporary_dn_hdr.delivery_number'
            ,'temporary_dn_hdr.delivery_date'
            ,'third_party.nama as customer_name'    
        )
        ->orderBy('id')
        ->orderBy('tdn_number')
        ->get(); 
             
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $statusPr = ['OPEN','SO','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPr[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function print(Request $request)
    {
        // $data['title'] = "Print $this->title";
        // $data['subtitle'] = "Print $this->title";
        $id=Crypt::decryptString($request->id);
                
        $tDnHdr=DB::table('temporary_dn_hdr')
        ->where('temporary_dn_hdr.id',$id)
        ->first();

        $data['tDnHdr']=DB::table('temporary_dn_hdr')
        ->where('temporary_dn_hdr.id',$id)
        ->first();

        $tDnNumber=$tDnHdr->tdn_number;

        $data['details']=DB::table('temporary_dn_det')
        ->leftJoin('article','article.article_code','temporary_dn_det.article_code')
        ->select('article_alternative_code'
        ,'article_desc'
        ,'temporary_dn_det.qty'
        ,'temporary_dn_det.uom'
        ,DB::raw("(select STRING_AGG( (qty::real)::text,' -> ' ORDER BY tdn_number) AS main from (select * from temporary_dn_det p where article_code = temporary_dn_det.article_code and tdn_number like '$tDnNumber%' limit 2) sub) as notes")
        )
        ->where('tdn_number',$tDnNumber)
        ->orderBy('temporary_dn_det.id')
        ->get();

        $data['tDnNumber'] =$tDnNumber;
        $data['tDnDate'] =$tDnHdr->delivery_date;
        $data['tDnNote'] =$tDnHdr->note;
        
        $statusPr = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PO'];
        $data['prStatus'] = $statusPr[$tDnHdr->status-1];

        $data['no'] =0;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->where('kode',$tDnHdr->customer_id)
        ->orderBy('nama')
        ->first();

        $data['title'] =$tDnNumber;

        return view('temporaryDn.print',$data);
       
        // view()->share($data);

        // $pdf = PDF::loadView('temporaryDn.print');
        // return $pdf->stream("TDN_$tDnNumber.pdf");

    }

   public function getArticle(Request $request){
    $custCode = $request->custCode;
    $siteCode = $this->siteCode;   // 'HO'
    $location = $this->locationFg; // '007'

    $data = DB::table('article')
        ->whereIn('article.article_code', function($query) use ($custCode) {
            $query->select('article_code')
                ->from('bom_hdr')
                ->where('status','3')
                ->where('customer',$custCode);
        })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->select(
            'article.article_code',
            'article.article_alternative_code',
            'article.article_desc',
            'article.uom',
            DB::raw("(coalesce((select article_qty from warehouse_stock where site_code = '$siteCode' and article_code = article.article_code and location_number = '$location'),0)) as stock_fg")
        )
        ->orderBy('article_desc')
        ->get();

    $output = '<option value="">Choose article</option>';
    foreach ($data as $row){
        $output .= '<option value="'.$row->article_code.'" data-uom="'.$row->uom.'" data-stock="'.$row->stock_fg.'">'
                 . $row->article_alternative_code.'-'.$row->article_desc.'</option>';
    }
    return $output;
}

    public function getArticleOld(Request $request){
        
        $custCode = $request->custCode;
        $data= DB::table('article') 
        ->whereIn('article.article_code', function($query) use ($custCode) {
            $query->select('article_code')
            ->from('bom_hdr') 
            ->where('status','3')
            ->where('customer',$custCode);
        })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->orderBy('article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose article</option>';

        foreach ($data as $row){
            $output .='<option value="'.$row->article_code.'" data-uom="'.$row->uom.'">'.$row->article_alternative_code.'-'. $row->article_desc.'</option>';
        }

        return $output;

    }


}
