<?php

namespace App\Http\Controllers\Production;

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

class ActualLoadingController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Actual Loading";
        $this->moduleCode = "PRD";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'prod_code','name'=>'prod_code','title'=>'Prod. Code'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'prod_date','name'=>'prod_date','title'=>'Prod. Date'],
            ['data'=>'wo_code','name'=>'wo_code','title'=>'WOS. Code'],
            ['data'=>'wo_date','name'=>'wo_date','title'=>'WOS. Date'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'prod_shift','name'=>'wo_shift','title'=>'Shift'],
            ['data'=>'prod_group','name'=>'wo_group','title'=>'Group'],
            ['data'=>'start_time','name'=>'start_time','title'=>'Start Time'],
            ['data'=>'working_hour','name'=>'working_hour','title'=>'Working Hour'],
            ['data'=>'efficiency','name'=>'efficiency','title'=>'Efficiency']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'prod_code','name'=>'prod_code','title'=>'Prod. Number'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'prod_date','name'=>'prod_date','title'=>'Prod. Date'],
            ['data'=>'wo_code','name'=>'wo_code','title'=>'WOS. Code'],
            ['data'=>'wo_date','name'=>'wo_date','title'=>'WOS. Date'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'prod_shift','name'=>'wo_shift','title'=>'Shift'],
            ['data'=>'prod_group','name'=>'wo_group','title'=>'Group'],
            ['data'=>'start_time','name'=>'start_time','title'=>'Start Time'],
            ['data'=>'working_hour','name'=>'working_hour','title'=>'Working Hour'],
            ['data'=>'efficiency','name'=>'efficiency','title'=>'Efficiency'],
            ['data'=>'article_code_fg','name'=>'article_code_fg','title'=>'Article Code'],
            ['data'=>'article_desc_fg','name'=>'article_desc_fg','title'=>'Article Desc'],
            ['data'=>'article_code_rm','name'=>'article_code_rm','title'=>'Article Code RM'],
            ['data'=>'article_desc_rm','name'=>'article_desc_rm','title'=>'Article Desc RM'],
            ['data'=>'plan_qty_fresh','name'=>'plan_qty_fresh','title'=>'Plan Qty Fresh'],
            ['data'=>'act_qty_fresh','name'=>'act_qty_fresh','title'=>'Act Qty Fresh'],
            ['data'=>'act_qty_repaint','name'=>'act_qty_repaint','title'=>'Act Qty Repaint']            
        ];

        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['subtitle'] = "$this->title";

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'='REVISED'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED'];
            
        return view("production.actualLoading.index",$data);
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
        $month = date('n');
        $year = date('Y');
        $prdNumber="$key/$year/$month/$newCode";
        
        return $prdNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Input $this->title";
        $data['subtitle'] = "Input $this->title";

        $data['listWo'] = DB::table('wo_hdr')
        ->where('status','=','3')
        ->whereNotIn('wo_hdr.wo_code', function($query) {
            $query->select('wo_code')->from('production_hdr')->where('status','<>','5');
        })
        ->select('wo_hdr.*',DB::raw("to_char(wo_date, 'DD-MM-YYYY') as tanggal"))
        ->get();

        $data['statusPrd'] = 'NEW';
        $data['oEdit'] = false;
               
        return view("production.actualLoading.create",$data);

    }

    public function articleCodeCreate(Request $request){
        $customer = $request->customer;
        $leadingCode = 'FG';

        $lastCode = DB::table('article')
        ->where('third_party','=',$customer)
        ->orderBy('article_alternative_code','DESC')->first();

        if (!$lastCode){
            $newCode = '00001';
        }else{
            $newCode = str_pad(substr($lastCode->article_alternative_code,5)+1, 5, "0", STR_PAD_LEFT);
        }

        $artilceCode = DB::table('third_party')
        ->where('kode',$customer)
        ->select(DB::raw("CONCAT('$leadingCode',inisial,'$newCode') AS new_code"))->value('new_code');

        return  Response()->json($artilceCode);
    
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $prdNumber = $request->prdNumber;
        $wosNumber = $request->wosNumber;
        $wosTime = $request->wosTime;
        $workHour = $request->workHour;
        $efficiency = $request->efficiency;
        $note = $request->note;
        $prdDate = date("Y-m-d");
        $status = '1';
        $oEdit = true;

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
            // 'woNumber'=>'required|unique:purchase_order_hdr,wo_code',
            'wosNumber'  => 'required' 
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save Production";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode($this->moduleCode);
            $prdNumber = $this->getLastCode($this->moduleCode);

            $sqlHdr = "INSERT into production_hdr 
            (
                prod_code,
                wo_code,
                original_prod_code,
                prod_date,
                prod_shift,
                prod_group,
                start_time,
                working_hour,
                efficiency,
                num_revision,
                status,
                note,
                created_by,
                updated_by,
                created_at,
                updated_at
            )
            select 
                '$prdNumber',
                wo_code,
                '$prdNumber',
                wo_date,
                wo_shift,
                wo_group,
                '$wosTime',
                $workHour,
                $efficiency,
                0,
                '1',
                note,
                '$username',
                '$username',
                '".date('Y-m-d H:i:s')."',
                '".date('Y-m-d H:i:s')."'
            from wo_hdr where wo_code = '$wosNumber'";

            $sqlDet="INSERT into production_det
            (
                prod_code,
                so_code,
                so_qty,
                urutan,
                article_code,
                article_rm_code,
                plan_time_loading,
                plan_qty_fresh,
                plan_qty_repaint,
                plan_tag,
                origin_tag,
                qty_ok,
                qty_repair,
                qty_repaint,
                note,
                created_by,
                updated_by,
                created_at,
                updated_at
            )
            select '$prdNumber',
                so_code,
                so_qty,
                urutan,
                article_code,
                article_rm_code,
                plan_time_loading,
                plan_qty_fresh,
                plan_qty_repaint,
                plan_tag,
                origin_tag,
                qty_ok,
                qty_repair,
                qty_repaint,
                note,
                '$username',
                '$username',
                '".date('Y-m-d H:i:s')."',
                '".date('Y-m-d H:i:s')."' 
            from wo_det where wo_code = '$wosNumber'";

            DB::beginTransaction();
            try {

                $rowAffected =  DB::select($sqlHdr);
                if ($rowAffected){
                    DB::select($sqlDet);
                }

                $dataSet = [];
                foreach ($articles as $val) {
                    DB::table('production_det')
                    ->where("prod_code", $prdNumber)
                    ->where("urutan", $val->urutan)
                    ->where("so_code",$val->so_code)
                    ->where("article_code",$val->article_code)
                    ->where("article_rm_code",$val->article_rm)
                    ->update(
                        [
                            "act_time_loading" => $val->act_waktu,
                            "act_qty_fresh" => $val->act_qty_prod,
                            "act_qty_repaint" => $val->act_qty_repaint,
                            "act_tag" => $val->act_tag,
                        ]
                    );
                }

                DB::commit();
                $title ='Save Production';
                $alert  ="success";
                $message  = "$title $prdNumber is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber,'oEdit'=>$oEdit));

            } catch (Exception $e) {
                DB::rollBack();
                $title ='Save Production';
                $alert  ="warning";
                $message  = "$title $prdNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber,'oEdit'=>$oEdit));
            }
        }
    }

    public function posting(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $prdNumber = DB::table('production_hdr')
                    ->where('id',$id)
                    ->where('status','=','3')
                    ->value('prod_code');
        $siteCode = 'HO';
        $location ='WH';
        $status = '4';
        $moduleCode = $this->moduleCode;
        $todayDate = date('Y-m-d');

        // $status = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','REVISED','INPUT FG','POSTED FG'];
        
        if ($prdNumber){
            $data = DB::table('production_det')
            ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
            ->leftJoin('article','article.article_code','production_det.article_code')
            ->where('production_det.prod_code',$prdNumber)
            ->where('production_hdr.status','3')
            ->where('production_det.so_code','<>','other')
            ->select('production_det.*'
            ,'article.article_type'
            ,'article.uom as uom_article'
            // ,'production_det.act_qty_fresh as total_qty'
            ,DB::raw("production_det.act_qty_fresh + production_det.act_qty_repaint as total_qty") //dari bu lupi qty yang potong RM adalah act_fresh + act_repaint
            )
            ->get();

            foreach($data as $val){
                //insert article code kalo belum ada di tabel item_stock
                DB::table('article_stock')
                ->updateOrInsert(
                    [ 'site_code' =>$siteCode,
                      'article_code' => $val->article_code,
                      'location_number'=> $location
                    ],
                    [
                      'dept_code'=>$val->article_type,
                      'uom'=>$val->uom_article,
                    ]
                );
                
                DB::table('article_stock')
                ->updateOrInsert(
                    [ 'site_code' =>$siteCode,
                      'article_code' => $val->article_rm_code,
                      'location_number'=> $location
                    ],
                    [
                      'dept_code'=>$val->article_type,
                      'uom'=>$val->uom_article,
                    ]
                );

                //update qty nya ditambahkan dengan qty baru FG
                // $rowAffectedFg = DB::table('article_stock')
                // ->where('site_code',$siteCode)
                // ->where('article_code',$val->article_code)
                // ->where('location_number',$location)
                // ->update([
                //     'article_qty' => DB::raw('coalesce(article_qty,0) + '.$val->total_qty)
                // ]);

                /*
                    update qty nya ditambahkan dengan qty baru RM
                    yang di potong stock nya hanya yang RM
                */
                $rowAffectedRm = DB::table('article_stock')
                ->where('site_code',$siteCode)
                ->where('article_code',$val->article_rm_code)
                ->where('location_number',$location)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty,0) - '.$val->total_qty)
                ]);
            }
                    
            if ($rowAffectedRm > 0){
                DB::table('production_hdr')
                ->where('prod_code',$prdNumber)
                ->update(
                    [   
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                // $movementFg = DB::table('production_det')
                // ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
                // ->leftJoin('article','article.article_code','production_det.article_code')
                // ->where('production_det.prod_code',$prdNumber)
                // ->where('production_hdr.status','4')
                // ->where('act_qty_fresh', '<>', 0)
                // ->where('production_det.so_code','<>','other')
                // ->select(
                //     DB::RAW("now()::timestamp::date as movement_date" )
                //     ,'production_det.article_code'
                //     ,'article.article_desc'
                //     ,DB::raw("0 as movement_min")
                //     ,DB::RAW("(production_det.act_qty_fresh) as movement_plus")
                //     ,DB::raw("0 as movement_price ")
                //     ,'production_hdr.prod_code as movement_transnno'
                //     ,DB::raw("'$moduleCode' as movement_type")
                //     ,'production_hdr.wo_code as movement_desc'
                // )
                // ->get();
                
                // $dataSetMovementFg = [];
                // foreach ($movementFg as $val) {
                //     $dataSetMovementFg[] = [
                //         'movement_date' => $val->movement_date,
                //         'artikel_code' => $val->article_code,
                //         'artikel_desc' => $val->article_desc,
                //         'movement_min' => $val->movement_min,
                //         'movement_plus' => $val->movement_plus,
                //         'movement_price' => $val->movement_price,
                //         'movement_transnno' => $val->movement_transnno,
                //         'movement_type' => $val->movement_type,
                //         'movement_desc' => $val->movement_desc,
                //         'created_by' => Auth::user()->username,
                //         'created_at' => date('Y-m-d H:i:s'),
                //         'site_code' => $siteCode,
                //         'location_number' => $location,
                //         'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') + ($val->movement_min+$val->movement_plus)")
                //     ];
                // }

                // DB::table('movement')->insert($dataSetMovementFg);

                $movementRm = DB::table('production_det')
                ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
                ->leftJoin('article','article.article_code','production_det.article_rm_code')
                ->where('production_det.prod_code',$prdNumber)
                ->where('production_hdr.status','4')
                ->where('act_qty_fresh', '<>', 0)
                ->where('act_qty_repaint', '<>', 0)
                ->where('production_det.so_code','<>','other')
                ->select(
                    DB::RAW("now()::timestamp::date as movement_date" )
                    ,'production_det.article_rm_code'
                    ,'article.article_desc'
                    // ,DB::raw("(production_det.act_qty_fresh) as movement_min")
                    ,DB::RAW("(production_det.act_qty_fresh+act_qty_repaint) as movement_min")
                    ,DB::RAW("0 as movement_plus")
                    ,DB::RAW("0 as movement_price ")
                    ,'production_hdr.prod_code as movement_transnno'
                    ,DB::RAW("'$moduleCode' as movement_type")
                    ,'production_hdr.wo_code as movement_desc'
                )
                ->get();
                
                $dataSetMovementRm = [];
                foreach ($movementRm as $val) {
                    $dataSetMovementRm[] = [
                        'movement_date' => $val->movement_date,
                        'artikel_code' => $val->article_rm_code,
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
                        'last_qty' => DB::raw("get_last_qty('$val->article_rm_code','$todayDate','$siteCode','$location') - ($val->movement_min+$val->movement_plus)")
                    ];
                }

                DB::table('movement')->insert($dataSetMovementRm);

                DB::commit();
                $title ="Posting $this->title";
                $alert  ="success";
                $message  = "$title $prdNumber Successfully Posted";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            }else{
                $title ="Posting $this->title";
                $alert  ="warning";
                $message  = "$title $prdNumber Failed to Posting";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            }
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $prdNumber Failed to Posting";
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

        $data['headers'] = DB::table('production_hdr')
        ->where('original_prod_code', function($query) use ($id){
            $query->select('prod_code')->from('production_hdr')->where('id',$id);
        })
        ->select('production_hdr.*'
        ,DB::raw("(working_hour*3600*(efficiency/100))/30 as sum_time_required")
        ,DB::raw("(select sum(plan_tag) from production_det where prod_code=production_hdr.prod_code) as sum_available_time")
        )
        ->orderBy('id')
        ->get();

        $prdNumber = $data['headers'][0]->prod_code;

        $data['details'] = DB::table('production_det')
        ->leftJoin('article','article.article_code','=','production_det.article_code')
        ->whereIn('production_det.prod_code', function($query) use ($prdNumber){
            $query->select('prod_code')->from('production_hdr')->where('original_prod_code',$prdNumber);
        })
        ->select('production_det'.'.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,DB::raw("case when so_code ='other' then production_det.article_code else concat(article.article_alternative_code,' - ',article.article_desc) end as article")
        )
        ->orderBy('urutan')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$prdNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$prdNumber,$username);

        $data['oEdit']=true;
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'INPUT FG'];
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED'];
        // $statusPrd = ['NEW','VALIDATED','APPROVED','POSTED','','CANCELED'];
        $statusPrd = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','REVISED','INPUT FG','POSTED FG'];
        $data['statusPrd'] = $statusPrd[$data['headers'][0]->status-1];

        return view("production.actualLoading.show",$data);
        
    }

    public function wosDetail(Request $request)
    {
        $woCode = $request->wosNumber;
        $data = DB::table('wo_det')
        ->leftJoin('article','article.article_code','=','wo_det.article_code')
        ->where('wo_code',$woCode)
        // ->where('so_code','<>','other')
        ->select('wo_det'.'.*'
        ,DB::raw("case when so_code ='other' then wo_det.article_code else concat(article.article_alternative_code,' - ',article.article_desc) end as article")
        , 'article.article_alternative_code'
        ,'article.article_desc')
        ->orderBy('urutan')
        ->get();

        return response()->json($data);

    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('production_hdr')
        ->where('id',$id)
        ->get()->first();

        $prdNumber = $data['header']->prod_code;

        $data['details'] = DB::table('production_det')
        ->leftJoin('article','article.article_code','=','production_det.article_code')
        ->where('prod_code',$prdNumber)
        ->select('production_det.*'
        ,DB::RAW("concat(article.article_alternative_code,article.article_desc) as article")
        ,'article.article_alternative_code'
        ,'article.article_desc')
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$prdNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$prdNumber,$username);

        $data['oEdit']=true;

         // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED'];
        $statusPrd = ['NEW','VALIDATED','APPROVED','POSTED','','CANCELED'];
        $data['statusPrd'] = $statusPrd[$data['header']->status-1];

        return view("production.actualLoading.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $prdNumber = $request->prdNumber;
        $wosNumber = $request->wosNumber;
        $wosTime = $request->wosTime;
        $workHour = $request->workHour;
        $efficiency = $request->efficiency;
        $note = $request->note;
        $prdDate = date("Y-m-d");
        $oEdit = true;

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
            'wosNumber'  => 'required' 
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $alert ="alert-danger";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {
                $row_affected=DB::table('production_hdr')
                ->where('prod_code',$prdNumber)
                ->update(
                    [
                        'start_time' => $wosTime,
                        'working_hour'=> $workHour,
                        'efficiency' => $efficiency,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                $dataSet = [];
                foreach ($articles as $val) {
                    DB::table('production_det')
                    ->where("prod_code", $prdNumber)
                    ->where("urutan", $val->urutan)
                    ->where("so_code",$val->so_code)
                    ->where("article_code",$val->article_code)
                    ->where("article_rm_code",$val->article_rm)
                    ->update(
                        [
                            "act_time_loading" => $val->act_waktu,
                            "act_qty_fresh" => $val->act_qty_prod,
                            "act_qty_repaint" => $val->act_qty_repaint,
                            "act_tag" => $val->act_tag,
                        ]
                    );
                }
                                    
                DB::commit();
                $title ="Update $this->title";
                $alert  ="success";
                $message  = "$title $prdNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber,'oEdit'=>$oEdit));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$title $prdNumber is failed to update";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber,'oEdit'=>$oEdit));
            }
        }

    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $prdNumber = DB::table('production_hdr')->where('id',$id)->where('status','1')->value('prod_code');
        $rowAffected = DB::table('production_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('production_det')->where('prod_code',$prdNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $prdNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $prdNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        $searchPrd = strtolower($request->searchPrd);
        $searchWos = strtolower($request->searchWos);
        $prdDate = $request->prdDate;
        $wosDate = $request->wosDate;
        $searchStatus = $request->searchStatus;

        $fromDate ="";
        $toDate = "";

        $fromDate1 ="";
        $toDate1 = "";

        if ($wosDate){
            $date = explode("to",$wosDate);
            if(count($date)>1){
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("-", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        if ($prdDate){
            $date1 = explode("to",$prdDate);
            if(count($date1)>1){
                $fromDate1 = implode("-", array_reverse(explode("-", trim($date1[0]))));
                $toDate1 = implode("-", array_reverse(explode("-", trim($date1[1]))));
            }else{
                $fromDate1 = implode("-", array_reverse(explode("-", trim($date1[0]))));
                $toDate1 = $fromDate1; 
            }
        }

        $data = DB::table('production_hdr')
        ->leftJoin('wo_hdr','wo_hdr.wo_code','production_hdr.wo_code')
        ->where(function ($query) use ($searchPrd,$searchWos,$wosDate,$prdDate,$fromDate,$fromDate1,$toDate,$toDate1) {
            $searchPrd ? $query->where('prod_code','ilike','%'.$searchPrd.'%') : '';
            $searchWos ? $query->where('wo_code','ilike','%'.$searchWos.'%') : '';
            $wosDate ? $query->whereBetween(DB::raw("wo_date"), [$fromDate, $toDate]) : '';
            $prdDate ? $query->whereBetween(DB::raw("prod_date"), [$fromDate1, $toDate1]) : '';
        })
        ->where('production_hdr.status','<>', '7')
        ->select('production_hdr.*','wo_hdr.wo_date')
        ->orderBy('prod_code')
        ->get(); 

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('actualLoading-edit')) {
                if($data->status == '1'){
                    $buttons .=         '<a href="'. route('production.actualLoading.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                }
                
            }

            if ( $data->status == '2' or $data->status == '1' ){
                // if (Auth::user()->can('actualLoading-approve')) {
                    $buttons .=     '<a href="'. route('production.actualLoading.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                // }
            }

            $buttons .=         '<a href="'. route('production.actualLoading.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';

            if (Auth::user()->can('actualLoading-posting')) {
                if($data->status == '3'){
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('production.actualLoading.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i>
                    <span>". __('Posting') ."</span>
                    </a>";
                }
            }

            // if (Auth::user()->can('actualLoading-revision')) {
            //     if (($data->status == '2') || ($data->status == '3') ){
            //         $buttons .=         '<a href="'. route('production.actualLoading.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) .'" class="dropdown-item">
            //                                 <i data-feather="copy"></i>
            //                                 <span>'. __("Revision") .'</span>
            //                             </a>';
            //     }
            // }
            
            $buttons .=         '<a href="'. route('production.actualLoading.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            // if (Auth::user()->can('actualLoading-delete')) {
            //     if ( $data->status == '1' ){
            //         if (Auth::user()->can('workingOrder-delete')) {
            //             $buttons .=         "<a href='javascript:;'
            //                                 class='dropdown-item' 
            //                                 data-size='sm'
            //                                 data-ajax-delete='true'
            //                                 data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
            //                                 data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
            //                                 data-modal-id='".$data->id."'
            //                                 data-url='". route('production.actualLoading.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
            //                                 <i data-feather='trash-2' class='feather-14-red'></i>
            //                                 <span>". __('Delete') ."</span>
            //                             </a>";
            //         }
            //     }
            // }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })

        ->addColumn('status', function ($data) {
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'INPUT FG','9'=>'POSTED FG'];
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-primary','badge-warning'];
            $status = ['NEW','VALIDATED','APPROVED ACT LOADING','POSTED WO','CANCELED','CLOSED','REVISED','INPUT FG','POSTED FG'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchPrd = strtolower($request->searchPrd);
        $searchWos = strtolower($request->searchWos);
        $prdDate = $request->prdDate;
        $wosDate = $request->wosDate;
        $searchStatus = $request->searchStatus;

        $fromDate ="";
        $toDate = "";

        $fromDate1 ="";
        $toDate1 = "";

        if ($wosDate){
            $date = explode("to",$wosDate);
            if(count($date)>1){
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("-", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        if ($prdDate){
            $date1 = explode("to",$prdDate);
            if(count($date1)>1){
                $fromDate1 = implode("-", array_reverse(explode("-", trim($date1[0]))));
                $toDate1 = implode("-", array_reverse(explode("-", trim($date1[1]))));
            }else{
                $fromDate1 = implode("-", array_reverse(explode("-", trim($date1[0]))));
                $toDate1 = $fromDate1; 
            }
        }

        $data = DB::table('production_det')
        ->leftJoin('production_hdr','production_hdr.prod_code','production_det.prod_code')
        ->leftJoin('wo_hdr','wo_hdr.wo_code','production_hdr.wo_code')
        ->leftJoin('article as a','a.article_code','production_det.article_code')
        ->leftJoin('article as b','b.article_code','production_det.article_rm_code')
        ->where(function ($query) use ($searchPrd,$searchWos,$wosDate,$prdDate,$fromDate,$fromDate1,$toDate,$toDate1) {
            $searchPrd ? $query->where('production_det.prod_code','ilike','%'.$searchPrd.'%') : '';
            $searchWos ? $query->where('production_hdr.wo_code','ilike','%'.$searchWos.'%') : '';
            $wosDate ? $query->whereBetween(DB::raw("wo_hdr.wo_date"), [$fromDate, $toDate]) : '';
            $prdDate ? $query->whereBetween(DB::raw("production_hdr.prod_date"), [$fromDate1, $toDate1]) : '';
        })
        ->where('production_hdr.status','<>', '7')
        ->where('production_det.so_code','<>', 'other')
        ->select('production_det.*'
        ,'production_hdr.*'
        ,'wo_hdr.wo_date'
        ,'a.article_alternative_code as article_code_fg'
        ,'a.article_desc as article_desc_fg'
        ,'b.article_alternative_code as article_code_rm'
        ,'b.article_desc as article_desc_rm'
        )
        ->orderBy('production_det.prod_code')
        ->orderBy('urutan')
        ->get(); 
                        
        return Datatables::of($data)
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['header']=DB::table('production_hdr')
        ->where('id',$id)
        ->select('production_hdr.*'
        ,DB::raw("(SELECT sum(act_tag) as total_tag from production_det where wo_code = production_hdr.wo_code) as total_tag")
        )
        ->first();

        $prdNumber=$data['header'] -> prod_code;
       
        $data['details']=DB::table('production_det')
        ->leftJoin('article','article.article_code','production_det.article_code')
        ->select('production_det.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,DB::raw("(SELECT article_qty from article_stock where article_code = production_det.article_rm_code and site_code ='HO' and location_number='WH') as qty_rm")
        )
        ->where('prod_code',$prdNumber)
        ->orderBy('urutan','asc')
        ->get();

        // $data['totals']=DB::select("SELECT sum(plan_tag) as total_tag from wo_det where wo_code = '$prdNumber'");

        $data['prdNumber'] = $prdNumber;
        $data['no'] = 0;

        $data['title'] = $prdNumber;


        view()->share($data);

        $pdf = PDF::loadView('production.actualLoading.print');
        return $pdf->stream("$prdNumber.pdf");

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $prdNumber = $request->prdNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$prdNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $status = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('production_hdr')
                ->where('prod_code',$prdNumber)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $prdNumber,
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
                $message  = "$title $prdNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusWo' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $prdNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusWo' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber));
        }
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $prdOrigin=DB::table('production_hdr')->where('id',$id)->value('prod_code');
        $numRevision = $request->nR ? $request->nR +1 : 1 ;
        $prdNew = $prdOrigin.'-R'.$numRevision;
        $checkNewPrd=DB::table('production_hdr')->where('prod_code',$prdNew)->count();

        if ($checkNewPrd > 0){
            $prdNew = $prdOrigin.'-R'.$numRevision;
        } 
                
        $sqlHdr = "INSERT into production_hdr 
        (
            prod_code,
            wo_code,
            original_prod_code,
            prod_date,
            prod_shift,
            prod_group,
            start_time,
            working_hour,
            num_revision,
            status,
            note,
            created_by,
            updated_by,
            created_at,
            updated_at
        )
        select 
            '$prdNew',
            wo_code,
            '$prdOrigin',
            prod_date,
            prod_shift,
            prod_group,
            start_time,
            working_hour,
            $numRevision,
            '7',
            note,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."'
        from production_hdr where prod_code = '$prdOrigin'";

        $sqlDet="INSERT into production_det
        (
            prod_code,
            so_code,
            so_qty,
            urutan,
            article_code,
            article_rm_code,
            plan_time_loading,
            act_time_loading,
            plan_qty_fresh,
            plan_qty_repaint,
            plan_tag,
            act_qty_fresh,
            act_qty_repaint,
            act_tag,
            origin_tag,
            qty_ok,
            qty_repair,
            qty_repaint,
            note,
            status,
            created_by,
            updated_by,
            created_at,
            updated_at
        )
        select '$prdNew',
            so_code,
            so_qty,
            urutan,
            article_code,
            article_rm_code,
            plan_time_loading,
            act_time_loading,
            plan_qty_fresh,
            plan_qty_repaint,
            plan_tag,
            act_qty_fresh,
            act_qty_repaint,
            act_tag,
            origin_tag,
            qty_ok,
            qty_repair,
            qty_repaint,
            note,
            status,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."' 
        from production_det where prod_code = '$prdOrigin'";

        $rowAffected =  DB::select($sqlHdr);
        if ($rowAffected){
            DB::select($sqlDet);

            // status:
            // 1 = New
            // 2 = Validated
            // 3 = Authorized
            // 4 = Received
            // 5 = Canceled
            // 6 = closed
            // 7 = Revised

            DB::table('production_hdr')
            ->where('prod_code',$prdOrigin)
            ->update(
                [
                    'num_revision' => $numRevision,
                    'status' => '1',
                    'revised_by'=>Auth::user()->username,
                    'revised_at'=> date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            DB::table('approval_history')
            ->where('module_number',$prdOrigin)
            ->update(
                [
                    'module_number' => $prdNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision PRD: $prdOrigin to $prdNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->route('production.actualLoading.edit', ['id'=>Crypt::encryptString($id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision PRD: $prdOrigin to $prdNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }
}
