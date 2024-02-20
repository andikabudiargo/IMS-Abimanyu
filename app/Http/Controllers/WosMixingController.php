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

class WosMixingController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "WOS Mixing";
        $this->moduleCode = "MIX";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'mix_number','name'=>'mix_number','title'=>'Mix Number'],
            ['data'=>'wos_number','name'=>'wos_number','title'=>'WOS Number'],
            ['data'=>'mix_date','name'=>'mix_date','title'=>'Date'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
            ['data'=>'posted_by','name'=>'posted_by','title'=>'Posted By'],
            ['data'=>'posted_at','name'=>'posted_at','title'=>'Posted At']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'mix_number','name'=>'mix_number','title'=>'MIX Number'],
            ['data'=>'wos_number','name'=>'wos_number','title'=>'WOS Number'],
            ['data'=>'mix_date','name'=>'mix_date','title'=>'Date'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty'],
            ['data'=>'qty_actual','name'=>'qty_actual','title'=>'Qty Actual'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
            ['data'=>'posted_by','name'=>'posted_by','title'=>'Posted By'],
            ['data'=>'posted_at','name'=>'posted_at','title'=>'Posted At']
            
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
    
        return view("wosMixing.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['oEdit']=false;

        return view("wosMixing.create",$data);

    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $mixDate = $request->mixDate;
        $note = $request->note;
        $status = '1';
        $leadCode = $this->moduleCode;
        $wosNumber =$request->wosNumber;
        
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];

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
            'mixDate'  => 'required'
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
            $hasilUpdate = AppHelpers::resetCode($leadCode);
            $mixNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                    $rowAffected = DB::table('wos_mixing_hdr')->insert([
                        'mix_number' => $mixNumber,
                        'wos_number' => $wosNumber,
                        'mix_date' => $mixDate,
                        'status' => $status,
                        'note' => $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'mix_number' => $mixNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'qty_actual' => $val->qtyAct,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    if ($rowAffected){
                        DB::table('wos_mixing_det')->insert($dataSet);
                    }

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $mixNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'mixNumber'=>$mixNumber,'oEdit'=>true));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $mixNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'mixNumber'=>$mixNumber));

            }
        }
    }

    public function posting(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];

        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $mixNumber = DB::table('wos_mixing_hdr')->where('id',$id)->where('status','3')->value('mix_number');
        $trType = $this->moduleCode;
        $siteCode = 'HO';
        $location ='WH';
        $status = '4';
        $todayDate = date('Y-m-d');
        $movementDate = date("d-m-Y");

        if ($mixNumber){
            $data = DB::table('wos_mixing_det')
            ->leftJoin('wos_mixing_hdr','wos_mixing_hdr.mix_number','wos_mixing_det.mix_number')
            ->leftJoin('article','article.article_code','wos_mixing_det.article_code')
            ->where('wos_mixing_det.mix_number',$mixNumber)
            ->where('wos_mixing_hdr.status','3')
            ->select('wos_mixing_det.*','article.article_type','article.uom as uom_article',
                DB::RAW("wos_mixing_det.qty_actual*uom_conversion(wos_mixing_det.uom,article.uom) as total_qty")
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

                //update qty nya ditambahkan dengan qty baru
                DB::table('article_stock')
                ->where('site_code',$siteCode)
                ->where('article_code',$val->article_code)
                ->where('location_number',$location)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty,0) - '.$val->total_qty)
                ]);
            }
                    
            
            $rowAffected=DB::table('wos_mixing_hdr')
            ->where('mix_number',$mixNumber)
            ->update(
                [   
                    'status' => $status,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            if ($rowAffected > 0){
                $movements = DB::table('wos_mixing_det')
                ->leftJoin('wos_mixing_hdr','wos_mixing_hdr.mix_number','wos_mixing_det.mix_number')
                ->leftJoin('article','article.article_code','wos_mixing_det.article_code')
                ->where('wos_mixing_det.mix_number',$mixNumber)
                ->where('wos_mixing_hdr.status','4')
                ->where('qty', '<>', 0)
                ->select(
                    DB::RAW("'$movementDate' as movement_date" )
                    ,'wos_mixing_det.article_code'
                    ,'article.article_desc'
                    ,DB::raw("0 as movement_plus")
                    ,DB::RAW("(uom_conversion(wos_mixing_det.uom,article.uom)*wos_mixing_det.qty_actual) as movement_min")
                    ,DB::raw(" 0 as movement_price ")
                    ,'wos_mixing_hdr.mix_number as movement_transnno'
                    ,DB::raw("'$trType' as movement_type")
                    ,'wos_mixing_hdr.note as movement_desc'
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
                $title ="Posting $this->title";
                $alert  ="success";
                $message  = "$title $mixNumber Successfully Posted";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            }else{
                $title ="Posting $this->title";
                $alert  ="warning";
                $message  = "$title $mixNumber Failed to Posting";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            }
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $mixNumber Failed to Posting";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function cancel(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];

        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $mixNumber = DB::table('wos_mixing_hdr')->where('id',$id)->where('status','4')->value('mix_number');
        $trType = $this->moduleCode;
        $siteCode = 'HO';
        $status = '5';
        $reason = "(Cancel by $username, Reason: $request->reason)";
        $rowAffected = 0;
        $location = 'WH';
        $todayDate = date('Y-m-d');
        $movementDate = date("d-m-Y");

        $data = DB::table('wos_mixing_det')
        ->leftJoin('wos_mixing_hdr','wos_mixing_hdr.mix_number','wos_mixing_det.mix_number')
        ->leftJoin('article','article.article_code','wos_mixing_det.article_code')
        ->where('wos_mixing_det.mix_number',$mixNumber)
        ->where('wos_mixing_hdr.status','4')
        ->select('wos_mixing_det.*','article.article_type','article.uom as uom_article',
            DB::RAW("wos_mixing_det.qty_actual*uom_conversion(wos_mixing_det.uom,article.uom) as total_qty")
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

            //update qty nya ditambahkan dengan qty baru
            $rowAffected = DB::table('article_stock')
            ->where('site_code',$siteCode)
            ->where('article_code',$val->article_code)
            ->where('location_number',$location)
            ->update([
                'article_qty' => DB::raw('coalesce(article_qty,0) + '.$val->total_qty)
            ]);

            //update qty nya ditambahkan dengan qty baru
            // $rowAffected = DB::table('article_stock')
            // ->where('site_code',$siteCode)
            // ->where('article_code',$val->article_code)
            // ->increment('article_qty', $val->total_qty);
        }
        
        if ($rowAffected > 0){
            DB::table('wos_mixing_hdr')
            ->where('mix_number',$mixNumber)
            ->update(
                [   
                    'status' => $status,
                    'note' => DB::raw("CONCAT(note,';','$reason')") ,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            $movements = DB::table('wos_mixing_det')
            ->leftJoin('wos_mixing_hdr','wos_mixing_hdr.mix_number','wos_mixing_det.mix_number')
            ->leftJoin('article','article.article_code','wos_mixing_det.article_code')
            ->where('wos_mixing_det.mix_number',$mixNumber)
            ->where('wos_mixing_hdr.status','5')
            ->where('qty', '<>', 0)
            ->select(
                DB::RAW("'$movementDate' as movement_date" )
                ,'wos_mixing_det.article_code'
                ,'article.article_desc'
                ,DB::raw("0 as movement_min")
                ,DB::RAW("(uom_conversion(wos_mixing_det.uom,article.uom)*wos_mixing_det.qty_actual) as movement_plus")
                ,DB::raw(" 0 as movement_price ")
                ,'wos_mixing_hdr.mix_number as movement_transnno'
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
                    'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') + ($val->movement_min+$val->movement_plus)")
                ];
            }

            DB::table('movement')->insert($dataSetMovement);

            DB::commit();
            $title ="Cancel $this->title";
            $alert  ="success";
            $message  = "$title $mixNumber Successfully Canceled";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }else{
            $title ="Cancel $this->title";
            $alert  ="warning";
            $message  = "$title $mixNumber Failed to Cancel";
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

        $data['header'] = DB::table('wos_mixing_hdr')
        ->where('id',$id)
        ->select('wos_mixing_hdr.*'
        ,DB::raw('(select count(*) from wos_mixing_det where mix_number = wos_mixing_hdr.mix_number) as sum_row')
        ,DB::raw('(select sum(qty) from wos_mixing_det where mix_number = wos_mixing_hdr.mix_number) as sum_qty'))
        ->get()->first();
        
        $mixNumber = $data['header']->mix_number;
        
        $data['details'] = DB::table('wos_mixing_det')
        ->leftJoin('article','article.article_code','=','wos_mixing_det.article_code')
        ->leftJoin('uom','uom.code','wos_mixing_det.uom')
        ->where('mix_number',$mixNumber)
        ->select('wos_mixing_det.*'
        ,'uom.uom_group as uom_group'
        ,DB::raw("concat(article.article_alternative_code,'-',article.article_desc) as article")
        )
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$mixNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$mixNumber,$username);
        
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED'];
        $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusTr'] = $statusTr[$data['header']->status-1];
       
        return view("wosMixing.show",$data);        
    }

    public function showEdit($key)
    {
        $id=Crypt::decryptString($key);
        $username =  Auth::user()->username;
        $data['title'] = "Input Actual";
        $data['subtitle'] = "Input Actual $this->title";

        $data['header'] = DB::table('wos_mixing_hdr')
        ->where('id',$id)
        ->get()->first();
        
        $mixNumber = $data['header']->mix_number;
        
        $data['details'] = DB::table('wos_mixing_det')
        ->leftJoin('article','article.article_code','=','wos_mixing_det.article_code')
        ->where('mix_number',$mixNumber)
        ->select('wos_mixing_det.*'
        ,'article.article_alternative_code as alternative'
        ,'article.article_desc as article_desc'
        ,DB::RAW("(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = wos_mixing_det.uom)
        ")
        )
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$mixNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$mixNumber,$username);
        
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusTr'] = $statusTr[$data['header']->status-1];

        $data['oEdit']=true;

        return view("wosMixing.edit",$data);
    }

    public function edit(Request $request)
    {
        return $this->showEdit($request->id);
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $mixNumber = $request->mixNumber;
        $wosNumber =$request->wosNumber;
        $mixDate = $request->mixDate;
        $trType = $this->moduleCode;
        $note = $request->note;
        $status = '1';
        $leadCode = $trType; 
              
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];

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
            'mixDate'  => 'required'
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
                $rowAffected=DB::table('wos_mixing_hdr')
                ->where('mix_number',$mixNumber)
                ->update(
                    [
                        'wos_number' => $wosNumber,
                        'mix_date' => $mixDate,
                        'status' => $status,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );

                $dataset=[];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        $mixNumber.$val->article_code
                    ];
                    
                }

                //Delete kalo article tidak ada di tr $mixNumber dan article nya $val->article_code
                //berdasarkan 2 kondisi
                DB::table('wos_mixing_det')
                    ->whereNotIn(DB::raw("CONCAT(mix_number,article_code)"),$dataSet)
                    ->where('mix_number',$mixNumber)
                    ->delete();

                foreach ($articles as $val) {
                    DB::table('wos_mixing_det')
                    ->updateOrInsert(
                        [
                            'mix_number' => $mixNumber,
                            'article_code' => $val->article_code
                        ],
                        [
                            'mix_number' => $mixNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'qty_actual' => $val->qtyAct,
                            'uom' => $val->uom,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                                        
                DB::commit();

                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$title $mixNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'mixNumber'=>$mixNumber,'oEdit'=>true));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert ="warning";
                $message  = "$title $mixNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'mixNumber'=>$mixNumber));
            }
        }

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $mixNumber = $request->mixNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$mixNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusMix = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('wos_mixing_hdr')
                ->where('mix_number',$mixNumber)
                ->update(
                    [
                        'status' => $statusMix,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $mixNumber,
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
                $message  = "$title $mixNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusMix,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'mixNumber'=>$mixNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $mixNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusMix,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'mixNumber'=>$mixNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $mixNumber = DB::table('wos_mixing_hdr')->where('id',$id)
        ->where('status','<>','4')
        ->where('status','<>','5')
        ->value('mix_number');
        $rowAffected = DB::table('wos_mixing_hdr')->where('mix_number',$mixNumber)->delete();
        
        if($rowAffected>0){
            DB::table('wos_mixing_det')->where('mix_number',$mixNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $mixNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $mixNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }
   
    public function list(Request $request)
    {
        $username = Auth::user()->username;
        $searchMix = strtolower($request->searchMix);
        $searchStatus = $request->searchStatus;
        $mixDate = $request->mixDate;
        $fromDate ="";
        $toDate = "";
        if ($mixDate){
            $date = explode("to",$mixDate);
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

        $data = DB::table('wos_mixing_hdr')
        ->where(function ($query) use ($searchMix,$searchStatus,$mixDate,$fromDate,$toDate) {
            $searchMix ? $query->where('mix_number','ilike','%'.$searchMix.'%') : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
            $mixDate ? $query->whereBetween(DB::raw("to_date(mix_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('wos_mixing_hdr.*'
        ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = wos_mixing_hdr.mix_number) as approval_by")
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
            
            if ( $data->status == '1' or $data->status == '2') {
                if (Auth::user()->can('wosMixing-approve')) {
                $buttons .=         '<a href="'. route('wosMixing.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                }
            }

            if ( $data->status == '3' ) {                
                if (Auth::user()->can('wosMixing-posting')) {
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('wosMixing.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i>
                    <span>". __('Posting') ."</span>
                    </a>";
                }
                
            }
            
            if ( $data->status == '1' or $data->status == '2' ){
                if (Auth::user()->can('wosMixing-edit')) {
                $buttons .=         '<a href="'. route('wosMixing.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Input Actual") .'</span>
                                    </a>';
                }
            }
  
            if ( $data->status == '4' ){
                if (Auth::user()->can('wosMixing-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                            id='cancelReasonButton'
                                            class='dropdown-item'
                                            data-toggle='modal'
                                            data-target='#reasonModalCancel'
                                            data-href='". route("wosMixing.cancel", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                            <i data-feather='corner-down-left' class='feather-14-red'></i>
                                            <span>". __('Cancel') ."</span>
                                        </a>";
                }
            }

            $buttons .= '<a href="'. route('wosMixing.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            <span>'. __("Detail") .'</span>
                        </a>';

            if ( $data->status != '4' and $data->status != '5' ){
                if (Auth::user()->can('wosMixing-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        data-url='". route('wosMixing.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
                }
            }
            
            $buttons .=     '<a href="'. route('wosMixing.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                <i data-feather="printer"></i>
                                <span>'. __("Print") .'</span>
                            </a>';

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('mix_number', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            // $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
            return '<span style="display: none;">'.$data->mix_number.'</span><a class="text-left badge d-block '.$badges[$data->status - 1].'" name="'.$data->mix_number.'" href="'. route('wosMixing.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->mix_number.'</span></a>';
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTr[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','mix_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchMix = strtolower($request->searchMix);
        $username = Auth::user()->username;
        $searchStatus = $request->searchStatus;
        $mixDate = $request->mixDate;
        $fromDate ="";
        $toDate = "";
        
        if ($mixDate){
            $date = explode("to",$mixDate);
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

        $data = DB::table('wos_mixing_det')
        ->leftJoin('wos_mixing_hdr','wos_mixing_hdr.mix_number','wos_mixing_det.mix_number')
        ->leftJoin('article','article.article_code','wos_mixing_det.article_code')
        ->leftJoin('uom','uom.code','wos_mixing_det.uom')
        ->where(function ($query) use ($searchMix,$searchStatus,$mixDate,$fromDate,$toDate) {
            $searchMix ? $query->where('mix_number','ilike','%'.$searchMix.'%') : '';
            $searchStatus ? $query->where('wos_mixing_hdr.status',$searchStatus) : '';
            $mixDate ? $query->whereBetween(DB::raw("to_date(mix_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('wos_mixing_det.*'
        ,'wos_mixing_hdr.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,'uom_group'
        ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = wos_mixing_hdr.mix_number) as approval_by")
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty,'999,999,999.999') end as qty")
        )
        ->orderBy('wos_mixing_det.id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusMix = ['NEW','VALIDATED','POSTED','APPROVED','DELETED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusMix[$data->status - 1]."</div>";
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
            
        $mixHdr=DB::table('wos_mixing_hdr')
        ->leftJoin('wo_hdr','wo_hdr.wo_code','wos_mixing_hdr.wos_number')
        ->select('wos_mixing_hdr.*','wo_hdr.wo_shift')
        ->where('wos_mixing_hdr.id',$id)
        ->first();

        $mixNumber=$mixHdr->mix_number;
        $wosNumber=$mixHdr->wos_number;
        $shift=$mixHdr->wo_shift;
    
        $data['details']=DB::table('wos_mixing_det')
        ->leftJoin('article_stock','article_stock.article_code','wos_mixing_det.article_code')
        ->leftJoin('article','article.article_code','wos_mixing_det.article_code')
        ->select('wos_mixing_det.*','article.article_alternative_code','article.article_desc',
        db::raw("coalesce(article_qty,0) as article_qty"))
        ->where('mix_number',$mixNumber)
        ->get();

        // $data['totals']=DB::select("SELECT *,(gross-discount)+ppn as netto from (
        //     select a.mix_number,authorized_by,validate_by,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(qty*price*b.ppn/100) as ppn from purchase_order_det a
        //     left join purchase_order_hdr b
        //     on a.mix_number = b.mix_number 
        //     where a.mix_number = '$mixNumber'
        //     group by a.po_number,authorized_by,validate_by) as oki");

        $data['keterangan']=$mixHdr->note;
        $data['mixNumber'] =$mixNumber;
        $data['wosNumber'] =$wosNumber;
        $data['shift'] =$shift;
        $data['mixDate'] =$mixHdr->mix_date;
        $data['postedBy'] =$mixHdr->posted_by;
        $data['no'] = 0;
        $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['status'] = $statusTr[$mixHdr->status-1];
        $data['createdBy'] = $mixHdr->created_by;

        $data['approved'] = DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_number',$mixNumber)
        ->orderBy('approval_order','desc')
        ->value('users.name');
        
        $data['title'] = $mixNumber;

        view()->share($data);

        $pdf = PDF::loadView('wosMixing.print');
        return $pdf->stream("$mixNumber.pdf");
    }

    public function articleMix(Request $request)
    {
        $woCode = $request->wosCode;
        $siteCode = 'HO';
        $location = 'WH';

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
                'uom' => 'PCS',
                'tone' => $val->tone
            ];
        }

        DB::table('wo_detail_temp')->insert($dataSet);

        /*
            pada saat wos mixing di ambil data di wos nya sesuai dengan tone yang ada di BOM
            BOM juga di grouping berdasarkan tone nya
        */

        $data=DB::select("SELECT
        article_code_det as article_code
        ,min_package 
        ,safety_stock
        ,sum(plan_qty_fresh * qty_bom) as total
        ,sum(plan_qty_fresh * qty_bom) as grand_total
        ,uom_article as uom
        --,(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = a.uom_bom)
        ,(select uom_group from uom where uom.code = uom_article) as uom_group
        ,(select third_party from article where article.article_code = article_code_det) as supp
        ,alternative
        ,article_desc
        from(
        select 
        bom_det.article_code as article_code_det
        ,wo_detail_temp.qty as plan_qty_fresh
        ,wo_detail_temp.uom as uom_order
        ,bom_det.qty * coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = bom_det.uom),1) as qty_bom
        ,bom_det.uom as uom_bom
        ,article.uom as uom_article
        ,bom_hdr.article_code 
        ,coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = article.uom),1) as factor_qty
        ,coalesce((select coalesce(min_package,1) from article where article_code = bom_det.article_code),1) as min_package 
        ,coalesce(article.safety_stock,0) as safety_stock 
        ,article_alternative_code as alternative
        ,article_desc
        from wo_detail_temp
        left join bom_hdr on bom_hdr.article_code=wo_detail_temp.article_code
        --left join bom_det on  bom_det.bom_code = bom_hdr.bom_code
        --left join (select bom_code,sum(qty) as qty,uom_con,uom,article_code from bom_det where bom_code in (select bom_code from bom_hdr where status = '3') group by bom_code,article_code,uom_con,uom) bom_det on  bom_det.bom_code = bom_hdr.bom_code and bom_det.tone = wo_detail_temp.tone
        left join (select bom_code,sum(qty) as qty,uom_con,uom,article_code,tone from bom_det where bom_code in (select bom_code from bom_hdr where status = '3') group by bom_code,article_code,uom_con,uom,tone) bom_det on  bom_det.bom_code = bom_hdr.bom_code and bom_det.tone = wo_detail_temp.tone
        left join article on article.article_code = bom_det.article_code
        where wo_detail_temp.code ='$randomCode'
        and bom_hdr.status = '3'
        order by article_alternative_code
        ) a
        group by article_code_det,alternative,article_desc,uom_article,min_package,safety_stock
        having sum(plan_qty_fresh * qty_bom) > 0
        order by alternative");

        // $data=DB::select("SELECT 
        // article_code_det as article_code
        // ,min_package 
        // ,sum(plan_qty_fresh * qty_bom) as total
        // ,sum(plan_qty_fresh * qty_bom) as grand_total
        // ,uom_bom as uom 
        // ,(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = a.uom_bom)
        // from(
        // select 
        // bom_det.article_code as article_code_det
        // ,wo_detail_temp.qty as plan_qty_fresh
        // ,wo_detail_temp.uom as uom_order
        // ,bom_det.qty * coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = wo_detail_temp.uom),1) as qty_bom
        // ,bom_det.uom as uom_bom
        // ,bom_hdr.article_code 
        // ,coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = wo_detail_temp.uom),1) as factor_qty
        // ,(select min_package from article where article_code = bom_det.article_code) as min_package 
        // from wo_detail_temp
        // left join bom_hdr on bom_hdr.article_code=wo_detail_temp.article_code
        // join bom_det on  bom_det.bom_code = bom_hdr.bom_code
        // where wo_detail_temp.code ='$randomCode'
        // and bom_hdr.status = '3'
        // ) a
        // group by article_code_det,uom_bom,min_package
        // order by article_code_det
        // ");

        if ($data){
            DB::table('wo_detail_temp')
                ->where('code',$randomCode)
                ->delete();
        }
        
        return response()->json($data);                        
    }
    
}

