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

class DnReturnController extends Controller
{
   private $title;
    private $moduleCode;
    private $decimalPlaces;
    private $siteCode;   // baru
    private $locationWip; // baru
    private $mvType;      // baru - label movement konsisten
    public function __construct()
    {
        $this->title = "DN Return";
        $this->moduleCode = "DN-RETURN";
        $this->decimalPlaces = config('globalParam.decimal');
        $this->siteCode   = 'HO';
        $this->locationWip = '012';        // Gudang WIP
        $this->mvType     = 'RETURN';   // dipakai konsisten di store/update/destroy
    }

    public function getTableColoumn()
{
    $kolom =
    [
        ['data'=>'action','name'=>'action','title'=>'Action','orderable'=>false,'searchable'=>false],
        ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
        ['data'=>'dn_number','name'=>'dn_number','title'=>'Customer DN Number'],
        ['data'=>'replace_number','name'=>'replace_number','title'=>'Replace Number'],
        ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer Code'],
        ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
        ['data'=>'status','name'=>'status','title'=>'Status'],
        ['data'=>'reconciliation','name'=>'reconciliation','title'=>'Reconciliation','orderable'=>false,'searchable'=>false],
        ['data'=>'note','name'=>'note','title'=>'Note'],
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
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'], //0
            ['data'=>'dn_number','name'=>'dn_number','title'=>'Customer DN Number'], //1
            ['data'=>'replace_number','name'=>'replace_number','title'=>'Replace Number'], //2
            ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer Code'], //4
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'], //5
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'], //6
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Description'], //7
            ['data'=>'qty','name'=>'qty','title'=>'Qty'], //8
            ['data'=>'uom','name'=>'uom','title'=>'UOM'], //9
            ['data'=>'status','name'=>'status','title'=>'Status'], //10
            ['data'=>'note','name'=>'note','title'=>'Note'], //11
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'], //12
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'], //13
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'], //14
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'], //15
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $username =  Auth::user()->username;
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        // $data['status'] = ['1'=>'OPEN','2'=>'SO','3'=>'CLOSED'];
        $data['status'] = ['1'=>'OPEN','3'=>'CLOSED'];

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("dnReturn.index",$data);
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

    /**
 * Validasi qty retur untuk tiap article:
 *  - qty > 0
 *  - qty retur <= (total terkirim dari SO ini) - (total sudah diretur di return lain yang masih aktif)
 * $excludeReturn: nomor return yang sedang diedit (dikecualikan dari perhitungan "sudah diretur"),
 *                 null saat store.
 * Mengembalikan array pesan error (kosong = lolos).
 */
private function checkReturnQty($articles, $soNumber, $excludeReturn = null)
{
    $errors = [];

    foreach ($articles as $val) {
        $articleInfo = DB::table('article')
            ->where('article_code', $val->article_code)
            ->select('article_desc', 'article_alternative_code')
            ->first();

        if (!$articleInfo) {
            $errors[] = "Article {$val->article_code} tidak ditemukan";
            continue;
        }

        if ($val->qty <= 0) {
            $errors[] = "Qty {$articleInfo->article_alternative_code} harus lebih dari 0";
            continue;
        }

        // total terkirim dari SO ini (DN valid)
        $qtyDelivered = DB::table('delivery_det')
            ->leftJoin('delivery_hdr', 'delivery_hdr.delivery_number', '=', 'delivery_det.delivery_number')
            ->where('delivery_det.so_number', $soNumber)
            ->where('delivery_det.article_code', $val->article_code)
            ->whereNotIn('delivery_hdr.status', ['5', '7', '10'])
            ->sum('delivery_det.qty');

        // total sudah diretur (return aktif, status bukan cancel 4), kecuali return yang sedang diedit
        $qtyReturned = DB::table('dn_return_det')
            ->leftJoin('dn_return_hdr', 'dn_return_hdr.return_number', '=', 'dn_return_det.return_number')
            ->where('dn_return_hdr.so_number', $soNumber)
            ->where('dn_return_det.article_code', $val->article_code)
            ->where('dn_return_hdr.status', '!=', '4')
            ->when($excludeReturn, function ($q) use ($excludeReturn) {
                $q->where('dn_return_hdr.return_number', '!=', $excludeReturn);
            })
            ->sum('dn_return_det.qty');

        $sisaBisaRetur = $qtyDelivered - $qtyReturned;

        if ($val->qty > $sisaBisaRetur) {
            $errors[] = "Retur {$articleInfo->article_alternative_code} - {$articleInfo->article_desc} melebihi sisa (terkirim: {$qtyDelivered}, sudah diretur: {$qtyReturned}, sisa: {$sisaBisaRetur}, diminta: {$val->qty})";
        }
    }

    return $errors;
}

/**
 * Cek apakah sebuah DN Return sudah punya DN Replace aktif.
 * "Aktif" = ada dn_replace_hdr dengan return_number ini yang status-nya
 * bukan CANCELED (3). Kalau ada, return tidak boleh di-cancel.
 */
private function punyaReplaceAktif($returnNumber)
{
    return DB::table('dn_replace_hdr')
        ->where('return_number', $returnNumber)
        ->whereNotIn('status', ['3'])   // 3 = CANCELED di modul replace
        ->exists();
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
        
        return view("dnReturn.create",$data);
    }

    public function store(Request $request)
{
    $username   = Auth::user()->username;
    $articles   = json_decode($request->articles);
    $customerId = $request->customerId;
    $returnDate = $request->returnDate;
    $dnNumber   = $request->dnNumber;
    $note       = $request->note;
    $soNumber   = $request->soNumber;
    $status     = '1';
    $returnNumber = '';
    $leadCode   = $this->moduleCode;

    $siteCode  = $this->siteCode;
    $location  = $this->locationWip;
    $todayDate = date('Y-m-d');
    $trType    = $this->mvType;

    Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
        $query  = DB::table($parameters[0]);
        $column = $query->getGrammar()->wrap($parameters[1]);
        return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
    });

    $validation = Validator::make($request->all(), [
        'returnDate' => 'required',
        'customerId' => 'required',
    ], ['required' => 'The field is required.']);

    $error_array = [];
    if ($validation->fails()) {
        foreach ($validation->messages()->getMessages() as $field_name => $messages) {
            $error_array[] = $messages;
        }
        return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
    }

    // ── VALIDASI QTY RETUR (cegah retur > terkirim / retur ganda) ──
    //$qtyErrors = $this->checkReturnQty($articles, $soNumber, null);
    //if (!empty($qtyErrors)) {
      //  return response()->json(['status' => 0, 'message' => $qtyErrors, 'alert' => 'warning']);
    //}

    $hasilUpdate  = AppHelpers::resetCode($leadCode);
    $returnNumber = $this->getLastCode($leadCode);

    DB::beginTransaction();
    try {
        DB::table('dn_return_hdr')->insert([
            'return_number'        => $returnNumber,
            'customer_id'          => $customerId,
            'return_date'          => $returnDate,
            'note'                 => $note,
            'origin_return_number' => $returnNumber,
            'status'               => $status,
            'created_by'           => $username,
            'updated_by'           => $username,
            'created_at'           => date('Y-m-d H:i:s'),
            'updated_at'           => date('Y-m-d H:i:s'),
            'so_number'            => $soNumber,
            'dn_number'            => $dnNumber,
        ]);

        $dataSet = [];
        foreach ($articles as $val) {
            $dataSet[] = [
                'return_number' => $returnNumber,
                'article_code'  => $val->article_code,
                'qty'           => $val->qty,
                'uom'           => $val->uom,
                'created_by'    => $username,
                'updated_by'    => $username,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ];
        }
        DB::table('dn_return_det')->insert($dataSet);

        // ── POSTING: tambah stok WIP (012) + movement masuk ──
       $this->postingReturn($returnNumber, $username, $returnDate, $soNumber, $note, $customerId);

        DB::commit();
        $title   = "Save $this->title";
        $alert   = "success";
        $message = "$title $returnNumber is successfully saved & posted";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => $alert, 'returnNumber' => $returnNumber]);

    } catch (\Exception $e) {
        DB::rollBack();
        $title   = "Save $this->title";
        $alert   = "warning";
        $message = "$title $returnNumber is failed to saved";
        \LogActivity::addToLog($title, "username: $username Status $message - " . $e->getMessage());
        return response()->json(['status' => 0, 'title' => $title, 'message' => $message, 'alert' => $alert, 'returnNumber' => $returnNumber]);
    }
}

/**
 * Tambah stok WIP + catat movement masuk untuk sebuah return.
 * Dipanggil dari dalam transaction. Qty asli (tanpa uom_conversion),
 * konsisten dengan modul DN/TDN.
 */
private function postingReturn($returnNumber, $username, $returnDate, $soNumber, $note, $customerId)
{
    $siteCode  = $this->siteCode;
    $location  = $this->locationWip;
    $todayDate = date('Y-m-d');
    $trType    = $this->mvType;

    $detail = DB::table('dn_return_det')
        ->leftJoin('article', 'article.article_code', '=', 'dn_return_det.article_code')
        ->where('dn_return_det.return_number', $returnNumber)
        ->where('dn_return_det.qty', '<>', 0)
        ->select(
            'dn_return_det.*',
            'article.article_type',
            'article.article_desc',
            'article.uom as uom_article',
            'dn_return_det.qty as total_qty'
        )
        ->get();

    $seq = (int) DB::table('warehouse_movement')->max('movement_code');
    $movementSet = [];

    foreach ($detail as $val) {
        if (!$val->article_type) {
            throw new \Exception("Article {$val->article_code} tidak ditemukan di master article");
        }

        $qtyBase = (float) $val->total_qty;

        DB::table('warehouse_stock')->updateOrInsert(
            ['site_code' => $siteCode, 'article_code' => $val->article_code, 'location_number' => $location],
            ['dept_code' => $val->article_type, 'uom' => $val->uom_article]
        );

        $avgLama = (float) (DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->value('avg_price') ?? 0);

        DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->update(['article_qty' => DB::raw('coalesce(article_qty,0) + ' . $qtyBase)]);

        $seq++;
        $movementSet[] = [
            'movement_code'     => $seq,
            'movement_date'     => date('d-m-Y', strtotime($returnDate)),
            'artikel_code'      => $val->article_code,
            'artikel_desc'      => $val->article_desc ?? '',
            'movement_min'      => 0,
            'movement_plus'     => $qtyBase,
            'movement_price'    => $avgLama,
            'movement_transnno' => $returnNumber,
            'movement_type'     => $trType,
            'movement_desc'     => ($note ?? '') . " (Retur dari SO {$soNumber})",
            'movement_from'     => $customerId,  // barang datang DARI customer
            'movement_to'       => $location,    // masuk KE WIP (012)
            'partner_type'      => 'CUST',
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $siteCode,
            'location_number'   => $location,
            'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$location') + $qtyBase"),
        ];
    }

    if (!empty($movementSet)) {
        DB::table('warehouse_movement')->insert($movementSet);
    }
}

/**
 * Reverse stok WIP + catat movement keluar (pembalikan) untuk sebuah return.
 * Dipakai oleh update() (sebelum re-post) dan destroy() (cancel).
 * $suffix: label tambahan movement_type, mis. 'REVERSE' atau 'CANCEL'.
 * $descPrefix: prefix movement_desc.
 */
private function reverseReturn($returnNumber, $username, $returnDate, $soNumber, $suffix, $descPrefix, $customerId)
{
    $siteCode  = $this->siteCode;
    $location  = $this->locationWip;
    $todayDate = date('Y-m-d');
    $trType    = $this->mvType;

    $detail = DB::table('dn_return_det')
        ->leftJoin('article', 'article.article_code', '=', 'dn_return_det.article_code')
        ->where('dn_return_det.return_number', $returnNumber)
        ->where('dn_return_det.qty', '<>', 0)
        ->select(
            'dn_return_det.*',
            'article.article_desc',
            'dn_return_det.qty as total_qty'
        )
        ->get();

    $seq = (int) DB::table('warehouse_movement')->max('movement_code');
    $movementSet = [];

    foreach ($detail as $val) {
        $qtyBase = (float) $val->total_qty;

        $avgLama = (float) (DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->value('avg_price') ?? 0);

        DB::table('warehouse_stock')
            ->where('site_code', $siteCode)
            ->where('article_code', $val->article_code)
            ->where('location_number', $location)
            ->update(['article_qty' => DB::raw('coalesce(article_qty,0) - ' . $qtyBase)]);

        $seq++;
        $movementSet[] = [
            'movement_code'     => $seq,
            'movement_date'     => date('d-m-Y', strtotime($returnDate)),
            'artikel_code'      => $val->article_code,
            'artikel_desc'      => $val->article_desc ?? '',
            'movement_min'      => $qtyBase,   // pembalikan: keluar dari WIP
            'movement_plus'     => 0,
            'movement_price'    => $avgLama,
            'movement_transnno' => $returnNumber,
            'movement_type'     => $trType . '-' . $suffix,
            'movement_desc'     => "$descPrefix: {$returnNumber} (SO {$soNumber})",
            'movement_from'     => $location,    // keluar DARI WIP (012)
            'movement_to'       => $customerId,  // kembali KE customer
            'partner_type'      => 'CUST',
            'created_by'        => $username,
            'created_at'        => date('Y-m-d H:i:s'),
            'site_code'         => $siteCode,
            'location_number'   => $location,
            'last_qty'          => DB::raw("get_last_qty_new('{$val->article_code}','$todayDate','$siteCode','$location') - $qtyBase"),
        ];
    }

    if (!empty($movementSet)) {
        DB::table('warehouse_movement')->insert($movementSet);
    }
}


    public function storeOld(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $customerId = $request->customerId;
        $returnDate = $request->returnDate;
        $dnNumber = $request->dnNumber;
        $note = $request->note;
        $status = '1';
        $returnNumber ='';
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
            'returnDate'  => 'required',
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
            $returnNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                DB::table('dn_return_hdr')->insert([
                    'return_number' => $returnNumber,
                    'customer_id' => $customerId,
                    'return_date' => $returnDate,
                    'note' => $note,
                    'origin_return_number' => $returnNumber,
                    'status' => $status,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'dn_number' => $dnNumber
                ]);

                $dataSet = [];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        'return_number' => $returnNumber,
                        'article_code' => $val->article_code,
                        'qty' => $val->qty,
                        'uom' => $val->uom,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }

                DB::table('dn_return_det')->insert($dataSet);

                DB::commit();
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$title $returnNumber is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'returnNumber'=>$returnNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $returnNumber is failed to saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'returnNumber'=>$returnNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('dn_return_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'dn_return_hdr.customer_id')
        ->where('origin_return_number', function($query) use ($id){
            $query->select('return_number')->from('dn_return_hdr')->where('id',$id);
        })
        ->where('dn_return_hdr.status','!=','4')
        ->select('dn_return_hdr.*'
        ,DB::raw('(select sum(qty) from dn_return_det where return_number = dn_return_hdr.return_number) as sum_qty') 
        ,DB::raw('(select count(*) from dn_return_det where return_number = dn_return_hdr.return_number) as sum_row')
        ,DB::raw("concat(kode,'-',nama) as customer_name")
        )
        ->orderBy('id')
        ->get(); 

        $returnNumber = $data['headers'][0]->return_number;
        
        $data['details'] = DB::table('dn_return_det')
        ->whereIn('dn_return_det.return_number', function($query) use ($returnNumber){
            $query->select('return_number')->from('dn_return_hdr')->where('origin_return_number',$returnNumber);
        })
        ->leftJoin('article','article.article_code','=','dn_return_det.article_code')
        ->select('dn_return_det'.'.*'
            ,DB::raw("concat(article_alternative_code,'-',article_desc) as article")
            ,DB::raw("(select STRING_AGG( (qty::real)::text,' -> ' ORDER BY return_number) AS main from dn_return_det p where article_code = dn_return_det.article_code and return_number like '$returnNumber%' ) as notes")
        )
        ->orderBy('dn_return_det.return_number')
        ->orderBy('dn_return_det.id')
        ->get();       

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $status = ['OPEN','','CLOSED','CANCELED'];
        $data['status'] = $status[$data['headers'][0]->status-1];

        return view("dnReturn.show",$data);
        
    }

    public function edit(Request $request)
    {   
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('dn_return_hdr')
        ->where('id',$id)
        ->first();
    
        $returnNumber = $data['header']->return_number;
        $custCode = $data['header']->customer_id;

        $data['details'] = DB::table('dn_return_det')
        ->where('return_number',$returnNumber)
        ->orderBy('id')
        ->get(); 

        $dataQuery= DB::table('article') 
        // ->whereIn('article.article_code', function($query) use ($custCode) {
        //     $query->select('article_code')
        //     ->from('bom_hdr') 
        //     ->where('status','3')
        //     ->where('customer',$custCode);
        // })
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

        $status = ['OPEN','','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        
        return view("dnReturn.edit",$data);
        
    }
    
    public function updateOld(Request $request)
    {

        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $returnNumber = $request->returnNumber;
        $dnNumber = $request->dnNumber;
        $customerId = $request->customerId;
        $returnDate = $request->returnDate;
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
            'returnDate'  => 'required',
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
                $row_affected=DB::table('dn_return_hdr')
                ->where('return_number',$returnNumber)
                ->update(
                    [
                        'customer_id' => $customerId,
                        'return_date' => $returnDate,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'dn_number' => $dnNumber
                    ]
                );

                $dataset=[];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        $returnNumber.$val->article_code
                    ];
                    
                }

                /*
                    Delete kalo article tidak ada di $returnNumber dan article nya $val->article_code
                    berdasarkan 2 kondisi
                */

                DB::table('dn_return_det')
                    ->whereNotIn(DB::raw("CONCAT(return_number,article_code)"),$dataSet)
                    ->where('return_number',$returnNumber)
                    ->delete();

                foreach ($articles as $val) {
                    DB::table('dn_return_det')
                    ->updateOrInsert(
                        ['return_number' => $returnNumber,'article_code' => $val->article_code],
                        [
                            'return_number' => $returnNumber,
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
                $message = "$title $returnNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'returnNumber'=>$returnNumber));

            } catch (Exception $e) {
                
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $returnNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'returnNumber'=>$returnNumber));
            }
        }
    }

    public function update(Request $request)
{
    $username     = Auth::user()->username;
    $articles     = json_decode($request->articles);
    $returnNumber = $request->returnNumber;
    $dnNumber     = $request->dnNumber;
    $customerId   = $request->customerId;
    $returnDate   = $request->returnDate;
    $note         = $request->note;
    $soNumber     = $request->soNumber;

    Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
        $query  = DB::table($parameters[0]);
        $column = $query->getGrammar()->wrap($parameters[1]);
        return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
    });

    $validation = Validator::make($request->all(), [
        'returnDate' => 'required',
        'customerId' => 'required',
    ]);

    $error_array = [];
    if ($validation->fails()) {
        foreach ($validation->messages()->getMessages() as $field_name => $messages) {
            $error_array[] = $messages;
        }
        return response()->json(['status' => 0, 'message' => $error_array, 'alert' => 'warning']);
    }

    // ── GUARD status: hanya boleh edit saat OPEN (1) ──
    $hdr = DB::table('dn_return_hdr')->where('return_number', $returnNumber)->first();
    if (!$hdr) {
        return response()->json(['status' => 0, 'title' => "Edit $this->title", 'message' => "Return $returnNumber tidak ditemukan", 'alert' => 'warning']);
    }
    if ($hdr->status != '1') {
        return response()->json(['status' => 0, 'title' => "Edit $this->title", 'message' => "Return $returnNumber tidak bisa diedit (status bukan OPEN). Cancel dulu jika perlu.", 'alert' => 'warning', 'returnNumber' => $returnNumber]);
    }

     // ── GUARD: tolak edit jika sudah ada DN Replace aktif (walau baru sebagian) ──
    if ($this->punyaReplaceAktif($returnNumber)) {
        return response()->json([
            'status'  => 0,
            'title'   => "Edit $this->title",
            'message' => "Return $returnNumber tidak bisa diedit: sudah ada DN Replace. Cancel DN Replace-nya dulu.",
            'alert'   => 'warning',
            'returnNumber' => $returnNumber,
        ]);
    }

    // ── VALIDASI QTY RETUR (kecualikan return ini sendiri dari hitungan "sudah diretur") ──
   // $qtyErrors = $this->checkReturnQty($articles, $soNumber, $returnNumber);
    //if (!empty($qtyErrors)) {
      //  return response()->json(['status' => 0, 'message' => $qtyErrors, 'alert' => 'warning']);
    //}

     $customerLama = $hdr->customer_id;

    DB::beginTransaction();
    try {
      // 1. REVERSE stok & movement lama (hanya kalau memang pernah diposting)
        if ($this->wasPosted($returnNumber)) {
            $this->reverseReturn($returnNumber, $username, $returnDate, $soNumber, 'REVERSE', 'Reversal edit', $customerLama);
        }

        // 2. UPDATE header
        DB::table('dn_return_hdr')
            ->where('return_number', $returnNumber)
            ->update([
                'customer_id' => $customerId,
                'return_date' => $returnDate,
                'note'        => $note,
                'dn_number'   => $dnNumber,
                'so_number'   => $soNumber,
                'updated_by'  => $username,
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        // 3. UPDATE detail (hapus yang dibuang, upsert sisanya)
        $dataSet = [];
        foreach ($articles as $val) {
            $dataSet[] = [$returnNumber . $val->article_code];
        }

        DB::table('dn_return_det')
            ->whereNotIn(DB::raw("CONCAT(return_number, article_code)"), $dataSet)
            ->where('return_number', $returnNumber)
            ->delete();

        foreach ($articles as $val) {
            DB::table('dn_return_det')->updateOrInsert(
                ['return_number' => $returnNumber, 'article_code' => $val->article_code],
                [
                    'qty'        => $val->qty,
                    'uom'        => $val->uom,
                    'updated_by' => $username,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_by' => $username,
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );
        }

        // 4. RE-POST stok & movement baru
        $this->postingReturn($returnNumber, $username, $returnDate, $soNumber, $note, $customerId);

        DB::commit();
        $title   = "Save $this->title";
        $alert   = "success";
        $message = "$title $returnNumber is successfully updated";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status' => 1, 'title' => $title, 'message' => $message, 'alert' => $alert, 'returnNumber' => $returnNumber]);

    } catch (\Exception $e) {
        DB::rollBack();
        $title   = "Save $this->title";
        $alert   = "warning";
        $message = "$title $returnNumber is failed to updated: " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username Status $message");
        return response()->json(['status' => 0, 'title' => $title, 'message' => $message, 'alert' => $alert, 'returnNumber' => $returnNumber]);
    }
}

    public function destroyOld(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;       
        $tDnHdr = DB::table('dn_return_hdr')->where('id',$id)->first();

        $returnNumber = $tDnHdr->return_number;
        
        DB::beginTransaction();
        try {
                $rowAffected=DB::table('dn_return_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => '4',
                        'return_number' => $returnNumber."(C)",
                        'origin_return_number' => $returnNumber."(C)",
                        'reason' => "Cancel",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if($rowAffected>0){
                    DB::table('dn_return_det')
                    ->where('return_number',$returnNumber)
                    ->update(
                    [
                        'return_number' => $returnNumber."(C)",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                    );
                    DB::commit();
                    $title ="Delete $this->title";
                    $alert  ="success";
                    $message  = "$title $returnNumber Successfully Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }else{
                    DB::rollBack();
                    $title ="Delete $this->title";
                    $alert  ="warning";
                    $message  = "$title $returnNumber Failed to Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

                }

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $returnNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function destroy(Request $request)
{
    $id           = Crypt::decryptString($request->id);
    $username     = Auth::user()->username;
    $tDnHdr       = DB::table('dn_return_hdr')->where('id', $id)->first();
    $returnNumber = $tDnHdr->return_number;
    $returnDate   = $tDnHdr->return_date;
    $soNumber     = $tDnHdr->so_number ?? '';
    $customerId    = $tDnHdr->customer_id;   // ← tambahkan
    $currentStatus = $tDnHdr->status;

    // ── GUARD: kalau sudah CANCELED (4), jangan reverse lagi (cegah kurang stok ganda) ──
    if ($currentStatus == '4') {
        $title   = "Delete $this->title";
        $alert   = "warning";
        $message = "$title $returnNumber sudah dibatalkan sebelumnya.";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
    }

      // ── GUARD: tolak cancel jika return sudah punya DN Replace aktif ──
    if ($this->punyaReplaceAktif($returnNumber)) {
        $title   = "Delete $this->title";
        $alert   = "warning";
        $message = "$title $returnNumber gagal: sudah ada DN Replace untuk return ini. Cancel DN Replace-nya terlebih dahulu.";
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
    }

    DB::beginTransaction();
    try {
       // 1. REVERSE stok & movement (hanya kalau memang pernah diposting)
        if ($this->wasPosted($returnNumber)) {
            $this->reverseReturn($returnNumber, $username, $returnDate, $soNumber, 'CANCEL', 'Cancel', $customerId);
        }

        // 2. Cancel header & detail
        $rowAffected = DB::table('dn_return_hdr')
            ->where('id', $id)
            ->update([
                'status'               => '4',
                'return_number'        => $returnNumber . '(C)',
                'origin_return_number' => $returnNumber . '(C)',
                'reason'               => 'Cancel',
                'updated_by'           => $username,
                'updated_at'           => date('Y-m-d H:i:s'),
            ]);

        if ($rowAffected > 0) {
            DB::table('dn_return_det')
                ->where('return_number', $returnNumber)
                ->update([
                    'return_number' => $returnNumber . '(C)',
                    'updated_by'    => $username,
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

            DB::commit();
            $title   = "Delete $this->title";
            $alert   = "success";
            $message = "$title $returnNumber Successfully Deleted";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        } else {
            DB::rollBack();
            $title   = "Delete $this->title";
            $alert   = "warning";
            $message = "$title $returnNumber Failed to Delete";
            \LogActivity::addToLog($title, "username: $username Status $message");
            return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
        }

    } catch (\Exception $e) {
        DB::rollBack();
        $title   = "Delete $this->title";
        $alert   = "warning";
        $message = "$title $returnNumber Failed to Delete: " . $e->getMessage();
        \LogActivity::addToLog($title, "username: $username Status $message");
        return redirect()->back()->with(['title' => $title, 'alert' => $alert, 'message' => $message]);
    }
}

    public function closed(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $returnNumber = DB::table('dn_return_hdr')->where('id',$id)->value('return_number');
        $status = '3';
        DB::beginTransaction();
        try {
                $row_affected=DB::table('dn_return_hdr')
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
                $message  = "$title $returnNumber Successfully Closed";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Close $this->title";
            $alert  ="warning";
            $message  = "$title $returnNumber Failed to Close";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
{
    // status: 1=OPEN, 3=CLOSED, 4=CANCELED

    $username       = Auth::user()->username;
    $searchDn       = strtolower($request->searchDn);
    $searchStatus   = $request->searchStatus;
    $returnDate     = $request->returnDate;
    $searchCustomer = $request->searchCustomer;
    $fromDate = "";
    $toDate   = "";

    if ($returnDate) {
        $date = explode("to", $returnDate);
        if (count($date) > 1) {
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate   = implode("/", array_reverse(explode("-", trim($date[1]))));
        } else {
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate   = $fromDate;
        }
    }

    $data = DB::table('dn_return_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'dn_return_hdr.customer_id')
        ->where(function ($query) use ($searchDn, $searchStatus, $returnDate, $fromDate, $toDate, $searchCustomer) {
            $searchDn ? $query->where('dn_return_hdr.return_number', 'ilike', '%' . $searchDn . '%') : '';
            $searchStatus ? $query->where('dn_return_hdr.status', $searchStatus) : '';
            $returnDate ? $query->whereBetween(DB::raw("to_date(return_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchCustomer ? $query->where('dn_return_hdr.customer_id', $searchCustomer) : '';
        })
        ->where('dn_return_hdr.status', '!=', '4')
        ->select(
            'dn_return_hdr.*',
            'nama as customer_name',
            // jumlah DN Replace aktif (status bukan 3=CANCELED) untuk return ini
            DB::raw("(select count(*) from dn_replace_hdr r where r.return_number = dn_return_hdr.return_number and r.status not in ('3')) as ada_replace"),
            // nomor DN Replace yang mau ditampilkan: prioritaskan yang masih AKTIF
            // (status bukan CANCELED); kalau semua replace-nya sudah CANCELED,
            // fallback ke yang paling terakhir dibuat -- supaya tetap ada jejak,
            // bukan langsung kosong padahal riwayatnya ada.
            DB::raw("(
                select replace_number from dn_replace_hdr r
                where r.return_number = dn_return_hdr.return_number
                order by (r.status <> '3') desc, r.id desc
                limit 1
            ) as replace_number"),
            DB::raw("(
    select id
    from dn_replace_hdr r
    where r.return_number = dn_return_hdr.return_number
    order by (r.status <> '3') desc, r.id desc
    limit 1
) as replace_id")
        )
        ->orderBy('id')
        ->get();

    return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $adaReplace = $data->ada_replace > 0;

            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .= '<div class="dropdown-menu dropdown-menu-right">';

            // ── EDIT: hanya saat OPEN (1) dan belum ada replace ──
            if ($data->status == '1') {
                if ($adaReplace) {
                    $buttons .= "<span class='dropdown-item text-muted' style='font-size:11px;cursor:not-allowed;'>
                                    <i data-feather='info'></i>
                                    Edit tidak bisa: sudah ada DN Replace
                                 </span>";
                } else {
                    $buttons .= '<a href="' . route('dnReturn.edit', ['id' => Crypt::encryptString($data->id)]) . '" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                }
            }

            // ── DETAIL: selalu tersedia ──
            $buttons .= '<a href="' . route('dnReturn.show', ['id' => Crypt::encryptString($data->id)]) . '" class="dropdown-item">
                            <i data-feather="list"></i>
                            Detail
                         </a>';
            
              // ── PRINT: selalu tersedia ──
            $buttons .= '<a href="' . route('dnReturn.print', ['id' => Crypt::encryptString($data->id)]) . '" target="_blank" class="dropdown-item">
                            <i data-feather="printer"></i>
                            Print
                         </a>';

            // ── CANCEL: tolak jika sudah ada replace aktif ──
            if ($adaReplace) {
                $buttons .= "<span class='dropdown-item text-muted' style='font-size:11px;cursor:not-allowed;'>
                                <i data-feather='info'></i>
                                Cancel tidak bisa: sudah ada DN Replace
                             </span>";
            } else {
                $buttons .= "<a href='javascript:;'
                                class='dropdown-item'
                                data-size='sm'
                                data-ajax-delete='true'
                                data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?'
                                data-confirm-yes='document.getElementById(\"" . "delete-form-" . $data->id . "\").submit();'
                                data-modal-id='" . $data->id . "'
                                id='deleteButton'
                                data-url='" . route('dnReturn.destroy', ['id' => Crypt::encryptString($data->id)]) . "'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                <span>" . __('Cancel') . "</span>
                            </a>";
            }

            $buttons .= '</div></div>';

            return $buttons;
        })
        ->addColumn('status', function ($data) {
            $badges   = ['badge-primary', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger', 'badge-dark', 'badge-secondary', 'badge-secondary'];
            $statusPr = ['OPEN', '', 'CLOSED', 'CANCELED'];
            return "<div class='badge " . $badges[$data->status - 1] . "'>" . $statusPr[$data->status - 1] . "</div>";
        })
       ->editColumn('replace_number', function ($data) {
    if (!$data->replace_number) {
        return '-';
    }

    return '<a href="' . route('dnReplace.show', [
            'id' => Crypt::encryptString($data->replace_id)
        ]) . '" target="_blank">'
        . e($data->replace_number) .
        '</a>';
})

->addColumn('reconciliation', function ($data) {

    return '<button
        type="button"
        class="btn btn-sm btn-outline-info btn-reconciliation"
        data-id="'.Crypt::encryptString($data->id).'">
        <i data-feather="git-merge"></i>
        Reconciliation
    </button>';

})
        ->rawColumns(['action', 'status', 'return_number', 'replace_number',  'reconciliation'])
        ->make(true);
}

public function reconciliation(Request $request)
{
    $id = Crypt::decryptString($request->id);

    $header = DB::table('dn_return_hdr as h')
        ->leftJoin('third_party as c', 'c.kode', '=', 'h.customer_id')
        ->select(
            'h.*',
            'c.nama as customer_name'
        )
        ->where('h.id', $id)
        ->first();

    if (!$header) {
        return response()->json([
            'success' => false,
            'message' => 'DN Return not found.'
        ], 404);
    }

    /*
    |--------------------------------------------------------------------------
    | Replace History
    |--------------------------------------------------------------------------
    */

   /*
    |--------------------------------------------------------------------------
    | Replace History (termasuk CANCELED, supaya jejaknya terlihat)
    |--------------------------------------------------------------------------
    */

    $statusReplaceMap = [
        '1' => ['label' => 'OPEN',     'badge' => 'info'],
        '2' => ['label' => 'CLOSED',   'badge' => 'success'],
        '3' => ['label' => 'CANCELED', 'badge' => 'danger'],
    ];

    $replaceHeaders = DB::table('dn_replace_hdr as h')
        ->select(
            'h.id',
            'h.replace_number',
            'h.status',
            'h.created_by',
            'h.created_at',
            DB::raw('(select coalesce(sum(d.qty),0) from dn_replace_det d
                      where d.replace_number = h.replace_number) as qty_total')
        )
        ->where('h.return_number', $header->return_number)
        ->orderBy('h.created_at')
        ->get()
        ->map(function ($row) use ($statusReplaceMap) {

            $row->id_encrypt   = Crypt::encryptString($row->id);

            $st = $statusReplaceMap[$row->status] ?? ['label' => 'UNKNOWN', 'badge' => 'secondary'];

            $row->status_label = $st['label'];
            $row->status_badge = $st['badge'];
            $row->is_canceled  = ($row->status == '3');

            return $row;
        });

    /*
    |--------------------------------------------------------------------------
    | Qty Replace
    |--------------------------------------------------------------------------
    */

    $replaceQty = DB::table('dn_replace_det as d')
        ->join('dn_replace_hdr as h', 'h.replace_number', '=', 'd.replace_number')
        ->select(
            'd.return_number',
            'd.article_code',
            DB::raw('SUM(d.qty) qty_replace')
        )
        ->where('h.status', '!=', '3')
        ->groupBy(
            'd.return_number',
            'd.article_code'
        );

    /*
    |--------------------------------------------------------------------------
    | Detail
    |--------------------------------------------------------------------------
    */

    $details = DB::table('dn_return_det as rd')
        ->leftJoinSub($replaceQty, 'rp', function ($join) {

            $join->on('rp.return_number', '=', 'rd.return_number')
                ->on('rp.article_code', '=', 'rd.article_code');

        })
        ->leftJoin('article as a', 'a.article_code', '=', 'rd.article_code')
        ->select(
            'rd.article_code',
            'a.article_alternative_code',
            'a.article_desc',
            'rd.uom',
            DB::raw('rd.qty qty_return'),
            DB::raw('COALESCE(rp.qty_replace,0) qty_replace'),
            DB::raw('(rd.qty-COALESCE(rp.qty_replace,0)) qty_remaining')
        )
        ->where('rd.return_number', $header->return_number)
        ->orderBy('a.article_alternative_code')
        ->get();

    $totalReturn = 0;
    $totalReplace = 0;

    foreach ($details as $row) {

        $totalReturn += $row->qty_return;
        $totalReplace += $row->qty_replace;

        if ($row->qty_replace == 0) {

            $row->status = 'Open';
            $row->badge = 'info';

        } elseif ($row->qty_remaining == 0) {

            $row->status = 'Match';
            $row->badge = 'success';

        } elseif ($row->qty_remaining > 0) {

            $row->status = number_format($row->qty_remaining) . ' Remaining';
            $row->badge = 'warning';

        } else {

            $row->status = 'Over Replace';
            $row->badge = 'danger';

        }
    }

    return response()->json([

        'success' => true,

        'header' => [

            'return_number' => $header->return_number,
            'dn_number' => $header->dn_number,
            'return_date' => $header->return_date,
            'customer_code' => $header->customer_id,
            'customer_name' => $header->customer_name,

            'total_return' => $totalReturn,
            'total_replace' => $totalReplace,
            'remaining' => $totalReturn - $totalReplace

        ],

        'replace' => $replaceHeaders,

        'details' => $details

    ]);
}

    public function listOld(Request $request)
    {
        // status:
        // 1 = Open
        // 2 = 
        // 3 = Closed
        // 4 = Canceled
        
        $username =  Auth::user()->username;
        $searchDn = strtolower($request->searchDn);
        $searchStatus = $request->searchStatus;
        $returnDate = $request->returnDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate ="";
        $toDate = "";       
 
        if ($returnDate){
            $date = explode("to",$returnDate);
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

        $data = DB::table('dn_return_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'dn_return_hdr.customer_id')
        ->where(function ($query) use ($searchDn,$searchStatus,$returnDate,$fromDate,$toDate,$searchCustomer) {
            $searchDn ? $query->where('dn_return_hdr.return_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('dn_return_hdr.status',$searchStatus) : '';
            $returnDate ? $query->whereBetween(DB::raw("to_date(return_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchCustomer ? $query->where('dn_return_hdr.customer_id',$searchCustomer) : '';
        })
        ->where('dn_return_hdr.status','!=','4')
        ->select('dn_return_hdr.*'
        // ,DB::raw("concat(kode,'-',nama) as customer_name")
        ,'nama as customer_name'
        )
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
            $buttons .= '<a href="'. route('dnReturn.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="file-text"></i>
                               Edit
                        </a>';
            }

            
            // $buttons .= '<a href="'. route('dnReturn.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
            //                 <i data-feather="printer"></i>
            //                 Print
            //             </a>';

            $buttons .= '<a href="'. route('dnReturn.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            Detail
                         </a>';
                
            // if ($data->status == '1') {
                $buttons .= "<a href='javascript:;'
                                class='dropdown-item' 
                                data-size='sm'
                                data-ajax-delete='true'
                                data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                data-modal-id='".$data->id."'
                                id='deleteButton'
                                data-url='". route('dnReturn.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                <span>". __('Cancel') ."</span>
                            </a>";
            // }
            
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
            //         data-url='". route('dnReturn.close', ['id'=>Crypt::encryptString($data->id)]) ."'>
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
            $statusPr = ['OPEN','','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPr[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','return_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $username =  Auth::user()->username;
        $searchDn = strtolower($request->searchDn);
        $searchStatus = $request->searchStatus;
        $returnDate = $request->returnDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate ="";
        $toDate = "";

        if ($returnDate){
            $date = explode("to",$returnDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }      
    
        $data = DB::table('dn_return_det')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_return_det.return_number')
        ->leftJoin('article','article.article_code','dn_return_det.article_code')
        ->leftJoin('third_party','third_party.kode','dn_return_hdr.customer_id')
        ->where(function ($query) use ($searchDn,$searchStatus,$returnDate,$fromDate,$toDate,$searchCustomer) {
            $searchDn ? $query->where('dn_return_hdr.return_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('dn_return_hdr.status',$searchStatus) : '';
            $returnDate ? $query->whereBetween(DB::raw("to_date(return_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchCustomer ? $query->where('dn_return_hdr.customer_id',$searchCustomer) : '';
        })
        ->where('dn_return_hdr.status','!=','4')
        ->select('dn_return_det.*'
    ,'article_alternative_code'
    ,'article.article_desc'
    ,'dn_return_hdr.status'
    ,'dn_return_hdr.return_date'
    ,'dn_return_hdr.note'
    ,'dn_return_hdr.customer_id'
    ,'dn_return_hdr.dn_number'
    ,'dn_return_hdr.so_number'
    ,'third_party.nama as customer_name'
)
        ->orderBy('id')
        ->orderBy('return_number')
        ->get(); 
             
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $statusPr = ['OPEN','','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPr[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    /**
 * Apakah return ini SAAT INI masih "secara stock" ter-posting?
 *
 * Dihitung dari NET qty (pola sama dengan DnReplaceController::wasPosted()):
 * total movement_plus bertipe 'RETURN' dikurangi total movement_min dari
 * movement pembalikan ('RETURN-REVERSE' / 'RETURN-CANCEL').
 *
 * Perlu karena dokumen lama hasil storeOld() TIDAK pernah posting stok.
 * Kalau dokumen itu sekarang diedit/dicancel, reverseReturn() akan mengurangi
 * stok WIP yang tidak pernah ditambah -> phantom minus.
 */
private function wasPosted($returnNumber)
{
    $masuk = (float) DB::table('warehouse_movement')
        ->where('movement_transnno', $returnNumber)
        ->where('movement_type', $this->mvType)
        ->sum('movement_plus');

    $keluar = (float) DB::table('warehouse_movement')
        ->where('movement_transnno', $returnNumber)
        ->whereIn('movement_type', [$this->mvType . '-REVERSE', $this->mvType . '-CANCEL'])
        ->sum('movement_min');

    return $masuk > $keluar;
}

    public function print(Request $request)
    {
        $id = Crypt::decryptString($request->id);

        $tDnHdr = DB::table('dn_return_hdr')
            ->where('dn_return_hdr.id', $id)
            ->first();

        if (!$tDnHdr) {
            abort(404, "DN Return tidak ditemukan");
        }

        $returnNumber = $tDnHdr->return_number;

        $data['title']    = "Print $this->title";
        $data['subtitle'] = "Print $this->title";
        $data['tDnHdr']   = $tDnHdr;

        $data['details'] = DB::table('dn_return_det')
            ->leftJoin('article', 'article.article_code', '=', 'dn_return_det.article_code')
            ->where('dn_return_det.return_number', $returnNumber)
            ->select(
                'dn_return_det.article_code',
                'article.article_alternative_code',
                'article.article_desc',
                'dn_return_det.qty',
                'dn_return_det.uom'
            )
            ->orderBy('dn_return_det.id')
            ->get();

        $data['tDnNumber'] = $returnNumber;
        $data['tDnDate']   = $tDnHdr->return_date;
        $data['tDnNote']   = $tDnHdr->note;
        $data['soNumber']  = $tDnHdr->so_number ?? '-';
        $data['dnNumber']  = $tDnHdr->dn_number ?? '-';

        // status DN Return: 1=OPEN, 3=CLOSED, 4=CANCELED (sama dgn list/show)
        $statusPr = ['OPEN', '', 'CLOSED', 'CANCELED'];
        $data['status'] = $statusPr[$tDnHdr->status - 1] ?? 'UNKNOWN';

        $data['no'] = 0;

        $data['customers'] = DB::table('third_party')
            ->where('third_party_type', '=', 'cust')
            ->where('kode', $tDnHdr->customer_id)
            ->first();

        return view('dnReturn.print', $data);
    }

    public function getArticle(Request $request){
        
        $custCode = $request->custCode;
        $data= DB::table('article') 
        // ->whereIn('article.article_code', function($query) use ($custCode) {
        //     $query->select('article_code')
        //     ->from('bom_hdr') 
        //     ->where('status','3')
        //     ->where('customer',$custCode);
        // })
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

    public function listSo(Request $request)
{
    $custCode = $request->value;
    $output = '<option value=""></option>';

    $data = DB::table('sales_order_hdr')
        ->where('customer_id', $custCode)
        ->where('status', '3')                  // full approve
        ->whereIn('so_code', function($q){      // hanya SO yang punya DN terkirim
            $q->select('so_number')->from('delivery_hdr')
              ->whereNotIn('status', ['5','7','10']);
        })
        ->orderBy('so_code')
        ->select('so_code','po_number')
        ->get();

    foreach ($data as $row) {
        $output .= '<option value="'.$row->so_code.'" data-po-number="'.$row->po_number.'">'
                 . $row->so_code.' | '.$row->po_number.'</option>';
    }
    return $output;
}

public function articleBySo(Request $request)
{
    $soCode = $request->value;

    $data = DB::table('delivery_det')
        ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','=','delivery_det.delivery_number')
        ->leftJoin('article','article.article_code','=','delivery_det.article_code')
        ->where('delivery_det.so_number', $soCode)
        ->whereNotIn('delivery_hdr.status', ['5','7','10'])   // hanya DN valid/terkirim
        ->select(
            'delivery_det.article_code',
            'article.article_alternative_code',
            'article.article_desc',
            'article.uom',
            DB::raw('sum(delivery_det.qty) as qty_delivered')  // total dari semua DN SO ini
        )
        ->groupBy('delivery_det.article_code','article.article_alternative_code','article.article_desc','article.uom')
        ->having(DB::raw('sum(delivery_det.qty)'), '>', 0)
        ->orderBy('article.article_desc')
        ->get();

    $output = '<option value="">Choose article</option>';
    foreach ($data as $row) {
        $output .= '<option value="'.$row->article_code.'"'
                 . ' data-uom="'.$row->uom.'"'
                 . ' data-qty-delivered="'.$row->qty_delivered.'">'
                 . $row->article_alternative_code.'-'.$row->article_desc.'</option>';
    }
    return response()->json(['options' => $output]);
}


}
