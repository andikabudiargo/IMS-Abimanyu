<?php

namespace App\Http\Controllers\Transfer;

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
use App\Imports\TransferInImport;
use App\Exports\TransferInExport;

/*

    27-10-2025
    Perubahan metode transfer in yang baru

*/

class TransferInController extends Controller
{
    private $title;
    private $moduleCode;
    private $lockDate;
    private $lockDateIndex;
    public function __construct()
    {
        $this->title = "Transfer In";
        $this->moduleCode = "TRIN";

        $lockDate1 = DB::table('application_lock')
        ->where('code_key',$this->moduleCode)
        ->where('status','1')
        ->value('lock_date');

        $lastDatePrevMonth = date('t-m-Y', strtotime('-1 months'));
        $lastDatePrevMonth = date('t-m-Y', strtotime('-1 months',strtotime('05-11-2023')));
        $firstDayCurrentMonth = date('1-m-Y');
        $firstDayCurrentMonth = date('1-m-Y', strtotime('05-11-2023'));
        $prevmonth = date('M Y 1', strtotime('-1 months'));
        
        /*
        jika tanggal hari ini lebih kecil dari lockdate maka
        min date nya adalah tanggal akhir dari bulan sebelumnya
        kalau tanggal hari ini lebi besar dari lockdate maka 
        tanggal minimum nya adalah tanngal awal di bulan ini
        */

        $todayDate = date('d-m-Y');
        $lockDateHere = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        $lockDateAt = date('d-m-Y', strtotime("+1 day", strtotime($lockDateHere)));

        // dd(date('t-m-Y', strtotime($lockDateAt)));

        if ($todayDate < $lockDateAt ){
            $firstDatePrevMonth = date('1-m-Y', strtotime("-1 months",strtotime($lockDateHere)));
            $lockDateAt = $firstDatePrevMonth;
        }else{
            $lockDateAt = date('1-m-Y', strtotime($lockDateAt));
        }

        // $lockDateHere = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        // $lockDateAt = date('d-m-Y', strtotime("+1 day", strtotime($lockDateHere)));
        $this->lockDate = $lockDateAt;

        $lockDateHereIndex = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        $lockDateAtIndex = date('d-m-Y', strtotime($lockDateHere));
        $this->lockDateIndex = $lockDateAtIndex;
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'tr_number','name'=>'tr_number','title'=>'Tr Number'],
            ['data'=>'reference_no','name'=>'reference_no','title'=>'Reference'],
            ['data'=>'tr_date','name'=>'tr_date','title'=>'Date'],
            ['data'=>'tr_type','name'=>'tr_type','title'=>'Type'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'third_party_name','name'=>'third_party_name','title'=>'Supplier/Customer'],
            ['data'=>'location_name','name'=>'location_name','title'=>'Location From'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'approval_by','name'=>'approval_by','title'=>'Approved By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'approved_at','name'=>'approved_at','title'=>'Approved At']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'tr_number','name'=>'tr_number','title'=>'Tr number'],
            ['data'=>'reference_no','name'=>'reference_no','title'=>'Reference'],
            ['data'=>'tr_date','name'=>'tr_date','title'=>'Date'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'third_party_name','name'=>'third_party_name','title'=>'Third Party Name'],
            ['data'=>'location_name_from','name'=>'location_name_from','title'=>'Location From'],
            ['data'=>'location_name_to','name'=>'location_name_to','title'=>'Location To'],
            ['data'=>'approval_by','name'=>'approval_by','title'=>'Approved By'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date']
            
        ];
        return json_encode($kolom, true);
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
        $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        $month = $months[date('n')-1];
        $year = date('Y');
        $codeNumber="$key/$year/$month/$newCode";
        
        return $codeNumber;
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['subtitle'] = "$this->title";

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];

        $data['thirdParties'] = DB::table('third_party')
        ->orderBy('nama')
        ->get();

        $data['locations'] = DB::table('goods_location_master')
        ->orderBy('location_name')
        ->get();

        $data['lockDate'] = $this->lockDateIndex;
    
        return view("transfer.transferIn.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['oEdit']=false;

        $data['lockDate'] = $this->lockDate;

        $data['locations'] = DB::table('goods_location_master')
        ->orderBy('location_name')
        ->get();

        $locationTo = "<option value=''>None</option>";
        foreach ($data['locations'] as $key => $val) {
            $locationTo .= "<option value='$val->location_code'>$val->location_name</option>";
        }

        $data['locationTo'] = $locationTo;

        $data['thirdParties'] = DB::table('third_party')
        ->orderBy('nama')
        ->get();

        return view("transfer.transferIn.create",$data);

    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $trDate = $request->trDate;
        $trType = $this->moduleCode;
        $note = $request->note;
        $status = '1';
        $poLeadCode = $trType; 

        $locationCode = $request->locationCode;
        $referenceNo = $request->referenceNo;
        $thirdParty = $request->thirdParty;

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'CANCELED'];

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'trDate'  => 'required'
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
           
            $title="Save $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));

        }else{
            $hasilUpdate = AppHelpers::resetCode($poLeadCode);
            $trNumber = $this->getLastCode($poLeadCode);
            DB::beginTransaction();
            try {
                    $rowAffected = DB::table('transfer_hdr')->insert([
                        'tr_number' => $trNumber,
                        'ref_number' => '' ,
                        'tr_date' => $trDate,
                        'tr_type' => $trType,
                        'status' => $status,
                        'note' => $note,
                        'created_by' => $username,
                        'updated_by' => $username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'reference_no' => $referenceNo,
                        'location_code' => $locationCode,
                        'third_party' => $thirdParty
                    ]);

                    $dataSet = [];
                    $dataSetLocation = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'tr_number' => $trNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'note' => $val->note,
                            'created_by' => $username,
                            'updated_by' => $username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'location_to' => $val->locationTo,
                            'third_party' => $val->thirdParty
                        ];

                        DB::table('goods_location')
                        ->updateOrInsert(
                            ['article_code' => $val->article_code],
                            [
                                'location_code' => $locationCode,
                                'article_code' => $val->article_code,
                                'updated_by' => Auth::user()->username,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );

                        // $dataSetLocation[] = [
                        //     'location_code' => $locationCode,
                        //     'article_code' => $val->article_code,
                        //     'created_by' => $username,
                        //     'updated_by' => $username,
                        //     'created_at' => date('Y-m-d H:i:s'),
                        //     'updated_at' => date('Y-m-d H:i:s')
                        // ];
                    }

                    if ($rowAffected){
                        DB::table('transfer_det')->insert($dataSet);
                    }

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $trNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber,'oEdit'=>true));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $trNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));

            }
        }
    }

    public function posting(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        // $trNumber = DB::table('transfer_hdr')->where('id',$id)->where('status','3')->value('tr_number');
        $hdrQ = DB::table('transfer_hdr')->where('id',$id)->where('status','3')->first();
        $trNumber = $hdrQ->tr_number; 
        $lastStatus = $hdrQ->status; 
        $trType = $this->moduleCode;
        $todayDate = date('Y-m-d');
        $siteCode = 'HO';
        $location ='WH';
        $status = '4';
        // $movementDate = date("d-m-Y");

        if ($lastStatus!=4){
            if ($trNumber){
                $data = DB::table('transfer_det')
                ->leftJoin('transfer_hdr','transfer_hdr.tr_number','transfer_det.tr_number')
                ->leftJoin('article','article.article_code','transfer_det.article_code')
                ->where('transfer_det.tr_number',$trNumber)
                // ->where('transfer_hdr.status','3')
                ->select('transfer_det.*','article.article_type','article.uom as uom_article',
                    DB::RAW("transfer_det.qty*coalesce(uom_conversion(transfer_det.uom,article.uom),1) as total_qty")
                )
                ->get();
    
    
                foreach($data as $val){
                    //insert article code kalo belum ada di tabel item_stock
                    DB::table('article_stock')
                    ->updateOrInsert(
                        [ 'site_code' =>$siteCode,
                            'article_code' => $val->article_code,
                            'location_number'=>$location
                        ],
                        [
                            'dept_code'=>$val->article_type,
                            'uom'=>$val->uom_article
                        ]
                    );
    
                    $qtyBefore = DB::table('article_stock')
                    ->where('site_code',$siteCode)
                    ->where('article_code',$val->article_code)
                    ->where('location_number',$location)
                    ->value(DB::raw('coalesce(article_qty,0)'));
    
                    $totalQty = (float) $qtyBefore + (float) $val->total_qty;
    
                    //update qty nya ditambahkan dengan qty baru
                    DB::table('article_stock')
                    ->where('site_code',$siteCode)
                    ->where('article_code',$val->article_code)
                    ->where('location_number',$location)
                    ->update([
                        // 'article_qty' => DB::raw('coalesce(article_qty,0) + '.$val->total_qty)
                        'article_qty' => $totalQty
                    ]);
    
                    // $rowAffected = DB::table('article_stock')
                    // ->where('site_code',$siteCode)
                    // ->where('article_code',$val->article_code)
                    // ->increment('article_qty', $val->total_qty);
                }
                        
                
                $rowAffected= DB::table('transfer_hdr')
                ->where('tr_number',$trNumber)
                ->update(
                    [   
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
    
                if ($rowAffected > 0){
                    $movements = DB::table('transfer_det')
                    ->leftJoin('transfer_hdr','transfer_hdr.tr_number','transfer_det.tr_number')
                    ->leftJoin('article','article.article_code','transfer_det.article_code')
                    ->where('transfer_det.tr_number',$trNumber)
                    ->where('transfer_hdr.status','4')
                    ->where('qty', '<>', 0)
                    ->select(
                        // DB::RAW("now()::timestamp::date as movement_date" )
                        'transfer_hdr.tr_date as movement_date'
                        // DB::RAW("'$movementDate' as movement_date")
                        ,'transfer_det.article_code'
                        ,'article.article_desc'
                        ,DB::raw("0 as movement_min")
                        ,DB::RAW("coalesce((uom_conversion(transfer_det.uom,article.uom)*transfer_det.qty),1) as movement_plus")
                        ,DB::raw(" 0 as movement_price ")
                        ,'transfer_hdr.tr_number as movement_transnno'
                        ,DB::raw("'$trType' as movement_type")
                        ,'transfer_hdr.note as movement_desc'
                    )
                    ->get();
                    
                    $dataSetMovement = [];
                    foreach ($movements as $val) {
                        $dataSetMovement[] = [
                            'movement_date' => $val->movement_date,
                            'artikel_code' => $val->article_code,
                            'artikel_desc' => $val->article_desc,
                            'movement_min' => $val->movement_min,
                            'movement_plus' => $val->movement_plus,
                            'movement_price' => $val->movement_price,
                            'movement_transnno' => $val->movement_transnno,
                            'movement_type' => $val->movement_type,
                            'movement_desc' => $val->movement_desc,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'site_code' => $siteCode,
                            'location_number' => $location,
                            'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') + ($val->movement_min+$val->movement_plus)")
                        ];
                    }
    
                    DB::table('movement')->insert($dataSetMovement);
    
                    DB::commit();
                    $title ="Posting $this->title";
                    $alert  ="success";
                    $message  = "$title $trNumber Successfully Posted";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }else{
                    $title ="Posting $this->title";
                    $alert  ="warning";
                    $message  = "$title $trNumber Failed to Posting";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }
            }else{
                $title ="Posting $this->title";
                $alert  ="warning";
                $message  = "$title $trNumber Failed to Posting";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            }
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber Failed to Posting, already posted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function cancel(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $trNumber = DB::table('transfer_hdr')->where('id',$id)->where('status','4')->value('tr_number');
        $trType = $this->moduleCode;
        $siteCode = 'HO';
        $status = '5';
        $reason = "(Cancel by $username, Reason: $request->reason)";
        $authorizedBy = Auth::user()->username;
        $rowAffected = 0;
        $location = 'WH';
        $todayDate = date('Y-m-d');
        // $movementDate = date("d-m-Y");

        $data = DB::table('transfer_det')
        ->leftJoin('transfer_hdr','transfer_hdr.tr_number','transfer_det.tr_number')
        ->leftJoin('article','article.article_code','transfer_det.article_code')
        ->where('transfer_det.tr_number',$trNumber)
        // ->where('transfer_hdr.status','4')
        ->select('transfer_det.*','article.article_type','article.uom as uom_article',
            DB::RAW("transfer_det.qty*coalesce(uom_conversion(transfer_det.uom,article.uom),1) as total_qty")
        )
        ->get();

        foreach($data as $val){
            //insert article code kalo belum ada di tabel item_stock
            DB::table('article_stock')
            ->updateOrInsert(
                [ 'site_code' =>$siteCode,
                    'article_code' => $val->article_code,
                    'location_number'=>$location
                ],
                [
                    'dept_code'=>$val->article_type,
                    'uom'=>$val->uom_article
                ]
            );

            //update qty nya dikurang dengan qty baru
            $rowAffected = DB::table('article_stock')
            ->where('site_code',$siteCode)
            ->where('article_code',$val->article_code)
            ->where('location_number',$location)
            ->update([
                // 'article_qty' => DB::raw('coalesce(article_qty,0) '.$val->total_qty ? - $val->total_qty  : '')
                'article_qty' => DB::raw('coalesce(article_qty,0) - '.$val->total_qty)
            ]);
            
            // $rowAffected = DB::table('article_stock')
            // ->where('site_code',$siteCode)
            // ->where('article_code',$val->article_code)
            // ->decrement('article_qty', $val->total_qty);
        }
        
        if ($rowAffected > 0){
            DB::table('transfer_hdr')
            ->where('tr_number',$trNumber)
            ->update(
                [   
                    'status' => $status,
                    'note' => DB::raw("CONCAT(note,';','$reason')") ,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            /*
                CR dari abimnanyu
                perubahan, untuk movement date mengikuti tanggald dari tr_date bukan current date
            */

            $movements = DB::table('transfer_det')
            ->leftJoin('transfer_hdr','transfer_hdr.tr_number','transfer_det.tr_number')
            ->leftJoin('article','article.article_code','transfer_det.article_code')
            ->where('transfer_det.tr_number',$trNumber)
            ->where('transfer_hdr.status','5')
            ->where('qty', '<>', 0)
            ->select(
                // DB::RAW("now()::timestamp::date as movement_date" )
                'transfer_hdr.tr_date as movement_date'
                // DB::RAW("'$movementDate' as movement_date")
                ,'transfer_det.article_code'
                ,'article.article_desc'
                ,DB::raw("0 as movement_plus")
                ,DB::RAW("coalesce((uom_conversion(transfer_det.uom,article.uom)*transfer_det.qty),1) as movement_min")
                ,DB::raw(" 0 as movement_price ")
                ,'transfer_hdr.tr_number as movement_transnno'
                ,DB::raw("'$trType' as movement_type")
                ,DB::raw("'$reason' as movement_desc")
            )
            ->get();
            
            $dataSetMovement = [];
            foreach ($movements as $val) {
                $dataSetMovement[] = [
                    'movement_date' => $val->movement_date,
                    'artikel_code' => $val->article_code,
                    'artikel_desc' => $val->article_desc,
                    'movement_min' => $val->movement_min,
                    'movement_plus' => $val->movement_plus,
                    'movement_price' => $val->movement_price,
                    'movement_transnno' => $val->movement_transnno,
                    'movement_type' => $val->movement_type,
                    'movement_desc' => $val->movement_desc,
                    'created_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'site_code' => $siteCode,
                    'location_number' => $location,
                    'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') - ($val->movement_min+$val->movement_plus)")
                ];
            }

            DB::table('movement')->insert($dataSetMovement);

            DB::commit();
            $title ="Cancel $this->title";
            $alert  ="success";
            $message  = "$title $trNumber Successfully Canceled";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }else{
            $title ="Cancel $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber Failed to Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('transfer_hdr')
        ->where('id',$id)
        ->where('tr_type',$this->moduleCode)
        ->select('transfer_hdr.*'
        ,DB::raw('(select count(*) from transfer_det where tr_number = transfer_hdr.tr_number) as sum_row')
        ,DB::raw('(select sum(qty) from transfer_det where tr_number = transfer_hdr.tr_number) as sum_qty'))
        ->get()->first();
        
        $trNumber = $data['header']->tr_number;
        
        $data['details'] = DB::table('transfer_det')
        ->leftJoin('goods_location_master','goods_location_master.location_code','=','transfer_det.location_to')
        ->leftJoin('article','article.article_code','=','transfer_det.article_code')
        ->leftJoin('uom','uom.code','transfer_det.uom')
        ->where('tr_number',$trNumber)
        ->select('transfer_det.*'
        ,'goods_location_master.location_name'
        ,'uom.uom_group as uom_group'
        ,DB::raw("concat(article.article_alternative_code,'-',article.article_desc) as article")
        )
        ->orderBy('transfer_det.id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$trNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$trNumber,$username);
        
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED'];
        $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusTr'] = $statusTr[$data['header']->status-1];

        $data['locations'] = DB::table('goods_location_master')
        ->orderBy('location_name')
        ->get();

        $locationTo = "<option value=''>None</option>";
        foreach ($data['locations'] as $key => $val) {
            $locationTo .= "<option value='$val->location_code'>$val->location_name</option>";
        }

        $data['locationTo'] = $locationTo;

        $data['thirdParties'] = DB::table('third_party')
        ->orderBy('nama')
        ->get();
       
        return view("transfer.transferIn.show",$data);        
    }

    public function showEdit($key)
    {
        $id=Crypt::decryptString($key);
        $username =  Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('transfer_hdr')
        ->where('id',$id)
        ->where('tr_type',$this->moduleCode)
        ->get()->first();
        
        $trNumber = $data['header']->tr_number;
        
        $data['details'] = DB::table('transfer_det')
        ->where('tr_number',$trNumber)
        ->select('transfer_det.*'
        ,DB::RAW("(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = transfer_det.uom)")
        )
        ->orderBy('id')
        ->get();

        $data['locations'] = DB::table('goods_location_master')
        ->orderBy('location_name')
        ->get();

        $locationTo = "<option value=''>None</option>";
        foreach ($data['locations'] as $key => $val) {
            $locationTo .= "<option value='$val->location_code'>$val->location_name</option>";
        }

        $data['locationTo'] = $locationTo;

        $data['thirdParties'] = DB::table('third_party')
        ->orderBy('nama')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$trNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$trNumber,$username);
        
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusTr'] = $statusTr[$data['header']->status-1];

        $data['oEdit']=true;

        $data['lockDate'] = $this->lockDate;

        return view("transfer.transferIn.edit",$data);
    }

    public function edit(Request $request)
    {
        return $this->showEdit($request->id);
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $trNumber = $request->trNumber;
        $trDate = $request->trDate;
        $trType = $this->moduleCode;
        $note = $request->note;
        $status = '1';
        $poLeadCode = $trType; 

        $locationCode = $request->locationCode;
        $referenceNo = $request->referenceNo;
        $thirdParty = $request->thirdParty;
              
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'CANCELED'];

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            'trDate'  => 'required'
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
           
            $title="Save $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));

        }else{
            DB::beginTransaction();
            try {
                $rowAffected=DB::table('transfer_hdr')
                ->where('tr_number',$trNumber)
                ->update(
                    [
                        'ref_number' => '' ,
                        'tr_date' => $trDate,
                        'tr_type' => $trType,
                        'status' => $status,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'location_code' => $locationCode,
                        'third_party' => $thirdParty
                    ]
                );

                $dataset=[];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        $trNumber.$val->article_code
                    ];
                    
                }

                //Delete kalo article tidak ada di tr $trNumber dan article nya $val->article_code
                //berdasarkan 2 kondisi
                DB::table('transfer_det')
                    ->whereNotIn(DB::raw("CONCAT(tr_number,article_code)"),$dataSet)
                    ->where('tr_number',$trNumber)
                    ->delete();

                foreach ($articles as $val) {
                    DB::table('transfer_det')
                    ->updateOrInsert(
                        ['tr_number' => $trNumber,'article_code' => $val->article_code],
                        [
                            'tr_number' => $trNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'location_to' => $val->locationTo,
                            'third_party' => $val->thirdParty,
                            'note' => $val->note
                        ]
                    );

                    DB::table('goods_location')
                    ->updateOrInsert(
                        ['article_code' => $val->article_code],
                        [
                            'location_code' => $locationCode,
                            'article_code' => $val->article_code,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                                        
                DB::commit();

                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$title $trNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber,'oEdit'=>true));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert ="warning";
                $message  = "$title $trNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
            }
        }

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $trNumber = $request->trNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$trNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusTr = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('transfer_hdr')
                ->where('tr_number',$trNumber)
                ->update(
                    [
                        'status' => $statusTr,
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
                return response()->json(array('statusTr' => $statusTr,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusTr' => $statusTr,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $trNumber = DB::table('transfer_hdr')->where('id',$id)
        ->where('status','<>','4')
        ->where('status','<>','5')
        ->value('tr_number');
        $rowAffected = DB::table('transfer_hdr')->where('tr_number',$trNumber)->delete();
        
        if($rowAffected>0){
            DB::table('transfer_det')->where('tr_number',$trNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $trNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }
   
    public function list(Request $request)
    {
        $username = Auth::user()->username;
        $searchTr = strtolower($request->searchTr);
        $searchType = $request->searchType;
        $searchStatus = $request->searchStatus;
        $trDate = $request->trDate;
        $trType = $this->moduleCode;
        $transferFrom = $request->transferFrom;
        $transferTo = $request->transferTo;
        $thirdParty = $request->thirdParty;

        $fromDate ="";
        $toDate = "";
        if ($trDate){
            $date = explode("to",$trDate);
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

        $data = DB::table('transfer_hdr')
        ->leftJoin('goods_location_master','goods_location_master.location_code','=','transfer_hdr.location_code')
        ->leftJoin('third_party','third_party.kode','=','transfer_hdr.third_party')
        ->where(function ($query) use ($searchTr,$searchStatus,$trDate,$fromDate,$toDate,$searchType,$transferFrom,$transferTo,$thirdParty){ 
            $searchType ? $query->where('tr_type',$searchType) : '';
            $searchTr ? $query->where('tr_number','ilike','%'.$searchTr.'%') : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
            $trDate ? $query->whereBetween(DB::raw("to_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $thirdParty ? $query->where('third_party',$thirdParty) : '';

            if($transferTo){
                $query->whereIn("tr_number", function($query1) use ($transferTo) {
                    $query1->select("tr_number")
                    ->from('transfer_det')
                    ->where('location_to',$transferTo);
                });
            }

            $transferFrom ? $query->where('transfer_hdr.location_code',$transferFrom) : '';
        })
        ->select('transfer_hdr.*','goods_location_master.location_name','third_party.nama as third_party_name'
        ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = transfer_hdr.tr_number) as approval_by")
        ,DB::raw("(select location_name from goods_location_master where location_code = transfer_hdr.location_code limit 1)")
        ,DB::raw("(select created_at as approved_at from approval_history where module_number = transfer_hdr.tr_number order by approval_order desc limit 1)")
        )
        // ->leftJoin('goods_location','goods_location.location_code','transfer_hdr.location_code')
        ->where('tr_type',$trType)
        ->orderBy('transfer_hdr.id')
        ->get(); 
        

        $lockDateToDate = date('Y-m-d',strtotime($this->lockDate));
        // $trDate = date('Y-m-d', strtotime('30-12-2023'));
        
        // dd($trDate ." > ". $lockDateToDate);
        // dd($trDate > $lockDateToDate);
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) use($lockDateToDate) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            if ( $data->status == '1' or $data->status == '2') {
                if (Auth::user()->can('transferIn-approve')) {
                $buttons .=         '<a href="'. route('transferIn.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                }
            }

            if ( $data->status == '3' ) {                
                if (Auth::user()->can('transferIn-posting')) {
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('transferIn.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i>
                    <span>". __('Posting') ."</span>
                    </a>";
                }
                
            }
            
            if ( $data->status == '1' or $data->status == '2' ){
                if (Auth::user()->can('transferIn-edit')) {
                    $trDateDataTables = date('Y-m-d', strtotime($data->tr_date));
                    if($trDateDataTables>$lockDateToDate){
                        $buttons .=         '<a href="'. route('transferIn.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                                <i data-feather="file-text"></i>
                                                <span>'. __("Edit") .'</span>
                                            </a>';
                    }
                }
            }
  
            if ( $data->status == '4' ){
                if (Auth::user()->can('transferIn-delete')) {
                    $trDateDataTables = date('Y-m-d', strtotime($data->tr_date));
                    if($trDateDataTables>$lockDateToDate){
                        $buttons .=         "<a href='javascript:;'
                                                id='cancelReasonButton'
                                                class='dropdown-item'
                                                data-toggle='modal'
                                                data-target='#reasonModalCancel'
                                                data-href='". route("transferIn.cancel", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                                <i data-feather='corner-down-left' class='feather-14-red'></i>
                                                <span>". __('Cancel') ."</span>
                                            </a>";
                    }
                }

                // if (Auth::user()->can('transferIn-delete')) {
                //     $buttons .="<a href='javascript:;'
                //     class='dropdown-item' 
                //     data-size='sm'
                //     data-ajax-delete='true'
                //     data-confirm='Are You Sure want to Cancel?|This action can not be undone. Do you want to continue?' 
                //     data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                //     data-modal-id='".$data->id."'
                //     data-url='". route('transferIn.cancel', ['id'=>Crypt::encryptString($data->id)]) ."'>
                //     <i data-feather='corner-down-left' class='feather-14-red'></i>
                //     <span>". __('Cancel') ."</span>
                //     </a>";
                // }
            }

            $buttons .= '<a href="'. route('transferIn.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            <span>'. __("Detail") .'</span>
                        </a>';

            if ( $data->status != '4' and $data->status != '5' ){
                if (Auth::user()->can('transferIn-delete')) {
                    $trDateDataTables = date('Y-m-d', strtotime($data->tr_date));
                    if($trDateDataTables>$lockDateToDate){
                        $buttons .=         "<a href='javascript:;'
                                            class='dropdown-item' 
                                            data-size='sm'
                                            data-ajax-delete='true'
                                            data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                            data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                            data-modal-id='".$data->id."'
                                            data-url='". route('transferIn.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                            <i data-feather='trash-2' class='feather-14-red'></i>
                                            <span>". __('Delete') ."</span>
                                        </a>";
                    }
                }
            }
            
            $buttons .=     '<a href="'. route('transferIn.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                <i data-feather="printer"></i>
                                <span>'. __("Print") .'</span>
                            </a>';

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        // ->addColumn('tr_number', function ($data) {
        //     $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
        //     // $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        //     // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        //     return '<span style="display: none;">'.$data->tr_number.'</span><a class="text-left badge d-block '.$badges[$data->status - 1].'" name="'.$data->tr_number.'" href="'. route('transferIn.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->tr_number.'</span></a>';
        // })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-warning','badge-success','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTr[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','tr_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchTr = strtolower($request->searchTr);
        $username = Auth::user()->username;
        $searchType = $request->searchType;
        $searchStatus = $request->searchStatus;
        $trDate = $request->trDate;
        $trType = $this->moduleCode;
        $transferFrom = $request->transferFrom;
        $transferTo = $request->transferTo;
        $thirdParty = $request->thirdParty;

        $fromDate ="";
        $toDate = "";
        
        if ($trDate){
            $date = explode("to",$trDate);
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

        $data = DB::table('transfer_det')
        ->leftJoin('transfer_hdr','transfer_hdr.tr_number','transfer_det.tr_number')
        ->leftJoin('goods_location_master as glm1','glm1.location_code','=','transfer_hdr.location_code')
        ->leftJoin('goods_location_master as glm2','glm2.location_code','=','transfer_det.location_to')
        ->leftJoin('article','article.article_code','transfer_det.article_code')
        ->leftJoin('third_party','third_party.kode','=','transfer_hdr.third_party')
        ->leftJoin('uom','uom.code','transfer_det.uom')
        ->where(function ($query) use ($searchTr,$searchStatus,$trDate,$fromDate,$toDate,$searchType,$transferFrom,$transferTo,$thirdParty) {
            $searchType ? $query->where('tr_type',$searchType) : '';
            $searchTr ? $query->where('transfer_det.tr_number','ilike','%'.$searchTr.'%') : '';
            $searchStatus ? $query->where('transfer_hdr.status',$searchStatus) : '';
            $trDate ? $query->whereBetween(DB::raw("to_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $thirdParty ? $query->where('transfer_det.third_party',$thirdParty) : '';
            $transferTo ? $query->Where('transfer_det.location_to',$transferTo) : '';
            $transferFrom ? $query->where('transfer_hdr.location_code',$transferFrom) : '';
        })
        ->where('tr_type',$trType)
        ->select('transfer_det.*'
            ,'glm1.location_name as location_name_from'
            ,'glm2.location_name as location_name_to' 
            ,'third_party.nama as third_party_name'
            ,'transfer_hdr.*'
            ,'article.article_alternative_code'
            ,'article.article_desc'
            ,'uom_group'
            ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = transfer_hdr.tr_number) as approval_by")
            ,DB::raw("(select location_name from goods_location_master where location_code = (select location_code from goods_location where article_code = transfer_det.article_code limit 1) limit 1)")
            // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty,'999,999,999.999') end as qty")
        )
        ->orderBy('transfer_det.id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTr = ['NEW','VALIDATED','POSTED','APPROVED','DELETED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTr[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }
    
    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['companies']=DB::table('company')
        ->where('code','ASN')
        ->select('name as nama', 'address as alamat', DB::RAW('(select region_name from regions where region_code = city::integer)  as kota'),'tlp')
        ->get()->first();
            
        $trHdr=DB::table('transfer_hdr')
        ->leftJoin('goods_location_master','goods_location_master.location_code','=','transfer_hdr.location_code')
        ->leftJoin('third_party','third_party.kode','=','transfer_hdr.third_party')
        ->select('transfer_hdr.*','third_party.nama as third_party_name','goods_location_master.location_name')
        ->where('transfer_hdr.id',$id)
        ->first();

        $trNumber=$trHdr->tr_number;
    
        $data['details']=DB::table('transfer_det')
        ->leftJoin('goods_location_master','goods_location_master.location_code','=','transfer_det.location_to')
        ->leftJoin('article','article.article_code','transfer_det.article_code')
        ->where('tr_number',$trNumber)
        ->get();

        // $data['totals']=DB::select("SELECT *,(gross-discount)+ppn as netto from (
        //     select a.tr_number,authorized_by,validate_by,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(qty*price*b.ppn/100) as ppn from purchase_order_det a
        //     left join purchase_order_hdr b
        //     on a.tr_number = b.tr_number 
        //     where a.tr_number = '$trNumber'
        //     group by a.po_number,authorized_by,validate_by) as oki");

        $data['keterangan']=$trHdr->note;
        $data['trNumber'] =$trNumber;
        $data['trDate'] =$trHdr->tr_date;
        $data['no'] = 0;
        $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['status'] = $statusTr[$trHdr->status-1];
        $data['createdBy'] = $trHdr->created_by;
        $data['thirdParty'] = $trHdr->third_party_name;
        $data['locationFrom'] = $trHdr->location_name;

        $data['approved'] = DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_number',$trNumber)
        ->orderBy('approval_order','desc')
        ->value('users.name');

        $data['thirdParties'] = DB::table('third_party')
        ->orderBy('nama')
        ->get();
        
        view()->share($data);

        $pdf = PDF::loadView('transfer.transferIn.print');
        return $pdf->stream("$trNumber.pdf");

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
            case when article.article_code is null then concat('Urutan ',row_number() over(),': Article Code:',import_stock_take_tmp.article_code, ' tidak terdaftar') end
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
		return Excel::download(new TransferInExport, 'transfer_in_template.xls');
	}

}
