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

class BomController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Bill Of Material";
        $this->moduleCode = "BOM";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'bom_code','name'=>'bom_code','title'=>'BOM Code'],
            ['data'=>'customer','name'=>'customer','title'=>'Customer'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'article_des','name'=>'article_des','title'=>'Article'],
            ['data'=>'group_of_material','name'=>'group_of_material','title'=>'Group'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'approval_by','name'=>'approval_by','title'=>'Approval By'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated At'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['articles'] = DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('article_type','FG')
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();
       
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'DELETED'];
                        
        return view("bom.index",$data);
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

        $newCode = str_pad($newCode,8,"0", STR_PAD_LEFT);
        $month = str_pad(date('n'),2,"0", STR_PAD_LEFT);
        $year = date('y');
        $bomNumber="$key$month$year$newCode";
        
        return $bomNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";

        $data['articles'] = DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('article_type','FG')
        ->whereNotIn('article.article_code', function($query){
            $query->select('article_code')
            ->from('bom_hdr');
        })
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();

        $data['articlesRm'] = DB::table('article')
        ->where('article_type','RM')
        ->whereNotIn('article.article_code', function($query){
            $query->select('article_code_rm')
            ->from('bom_hdr');
        })
        ->get();

        $data['oEdit']=false;

        return view("bom.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $articleCode = $request->articleCode;
        $articleCodeRm = $request->articleCodeRm;
        $customer = $request->customer;
        $group = $request->group;
        $uom = $request->uom;
        $tag = $request->tag;
        $passRate = $request->passRate;
        $passThru = $request->passThru;
        $cycleTime = $request->cycleTime;
        $note = $request->note;

        $status = '1';
        $print_seq = 0;

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
            'articleCode'=>'required|unique:bom_hdr,article_code',
            'customer'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save BOM";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $bomNumber = $this->getLastCode('BOM');
            DB::beginTransaction();
            try {
                    $id = DB::table('bom_hdr')->insertGetId([
                        'bom_code' => $bomNumber,
                        'customer' => $customer,
                        'article_code' => $articleCode,
                        'article_code_rm' => $articleCodeRm,
                        'uom' => $uom,
                        'group_of_material' => $group,
                        'status' => $status,
                        'tag' => $tag,
                        'pass_rate' => $passRate,
                        'pass_thru' => $passThru,
                        'cycle_time' => $cycleTime,
                        'note' => $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'origin_bom_code' => $bomNumber
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'bom_code' => $bomNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'uom_con' => $val->uom_con,
                            // 'cost_price' => $val->price,
                            'article_type' => $val->type,
                            'customer_code' => $val->customer_code,
                            // 'note' => $val->note,
                            'status' => '1',
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('bom_det')->insert($dataSet);

                    DB::commit();
                    $title ='Save BOM';
                    $alert  ="success";
                    $message  = "$title $bomNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber,'oEdit'=>true));
            } catch (Exception $e) {
                DB::rollBack();
                $title ='Save BOM';
                $alert  ="warning";
                $message  = "$title $bomNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['headers'] = DB::table('bom_hdr')
        ->leftJoin('third_party','bom_hdr.customer','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','bom_hdr.group_of_material')
        ->where('origin_bom_code', function($query) use ($id){
            $query->select('bom_code')->from('bom_hdr')->where('id',$id);
        })
        ->select('bom_hdr.*'
        ,'third_party.nama as cust_name'
        ,'group_materials.name as group'
        ,DB::raw("(select concat(kode,' - ',nama) from third_party where kode = bom_hdr.customer) as cust_name")
        ,DB::raw('(select sum(qty) from bom_det where bom_code = bom_hdr.bom_code) as sum_qty') 
        ,DB::raw('(select count(*) from bom_det where bom_code = bom_hdr.bom_code) as sum_row')
        ,DB::raw("(select 
                    concat(article.article_alternative_code,'-',article.article_desc)
                from article where article_code = bom_hdr.article_code limit 1) as article")
        ,DB::raw("(select 
                    concat(article.article_alternative_code,'-',article.article_desc)
                from article where article_code = bom_hdr.article_code_rm limit 1) as article_rm")
        )
        ->orderBy('id')
        ->get();    

        $bomNumber =  $data['headers'][0]->bom_code;

        $data['details'] = DB::table('bom_det')
        ->whereIn('bom_det.bom_code', function($query) use ($bomNumber){
            $query->select('bom_code')->from('bom_hdr')->where('origin_bom_code',$bomNumber);
        })
        ->leftJoin('article','article.article_code','=','bom_det.article_code')
        ->leftJoin('uom','uom.code','bom_det.uom')
        ->leftJoin('article_types','article_types.code','=','bom_det.article_type')
        ->select('bom_det.*'
        ,'uom.uom_group as uom_group'
        ,'article_types.name as type_name'
        ,DB::raw("concat(article.article_alternative_code,'-',article.article_desc) as article")
        )
        ->orderBy('bom_det.id')
        ->get();

        $data['articleHeader']= DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('article.third_party',$data['headers'][0]->customer)
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();   

        // $data['articles'] = DB::table('article') 
        // ->leftJoin('article_types','article_types.code','=','article.article_type')
        // ->leftJoin('uom','uom.code','article.uom')
        // // ->whereNotIn('article_type',['FG','RM'])
        // ->orderBy('article_desc')
        // ->select('article.*','uom.uom_group as uom_group','article_types.name as type_name')
        // ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$bomNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$bomNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
        $statusPr = ['NEW','VALIDATE','APPROVED','RECEIVED','DELETED','CLOSED','REVISED','DECLINE'];
        $data['statusBom'] = $statusPr[$data['headers'][0]->status-1];

        return view("bom.show",$data);
        
    }

    public function edit(Request $request)
    {
        return $this->showEdit($request->id);
    }

    public function showEdit($key)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($key);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('bom_hdr')
        ->leftJoin('third_party','bom_hdr.customer','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','bom_hdr.group_of_material')
        ->select('bom_hdr.*'
        ,'third_party.nama as cust_name'
        ,'group_materials.name as group'
        ,DB::raw("(select 
                    concat(article.article_alternative_code,'-',article.article_desc)
                from article where article_code = bom_hdr.article_code limit 1) as article")
        ,DB::raw("(select 
                    concat(article.article_alternative_code,'-',article.article_desc)
                from article where article_code = bom_hdr.article_code_rm limit 1) as article_rm")
        )
        ->where('bom_hdr.id',$id)
        ->get()->first();

        $bomNumber = $data['header']->bom_code;

        $data['detail'] = DB::table('bom_det')
        ->where('bom_code',$bomNumber)
        ->leftJoin('uom','uom.code','bom_det.uom')
        ->leftJoin('article_types','article_types.code','=','bom_det.article_type')
        ->select('bom_det.*'
        ,'uom.uom_group as uom_group'
        ,'article_types.name as type_name'
        ,DB::RAW("(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = bom_det.uom)")
        ,DB::RAW("(select string_agg(code,',' order by code) as uoms from uom )")
        )
        ->orderBy('bom_det.id')
        ->get();

        // $data['articleHeader']= DB::table('article')
        // ->leftJoin('third_party','article.third_party','third_party.kode')
        // ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        // ->where('article.third_party',$data['header']->customer)
        // ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        // ->get();   

        $data['articles'] = DB::table('article') 
        ->leftJoin('article_types','article_types.code','=','article.article_type')
        ->leftJoin('uom','uom.code','article.uom')
        ->whereNotIn('article_type',['FG','RM'])
        ->orderBy('article_desc')
        ->select('article.*','uom.uom_group as uom_group','article_types.name as type_name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$bomNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$bomNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
        $statusPr = ['NEW','VALIDATE','APPROVED','RECEIVED','DELETED','CLOSED','REVISED','DECLINE'];
        $data['statusBom'] = $statusPr[$data['header']->status-1];

        return view("bom.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $bomNumber = $request -> bomNumber;
        $articles = json_decode($request -> articles);
        $articleCode = $request->articleCode;
        $customer = $request->customer;
        $group = $request->group;
        $uom = $request->uom;
        $tag = $request->tag;
        $passRate = $request->passRate;
        $passThru = $request->passThru;
        $cycleTime = $request->cycleTime;
        $note = $request->note;

        $status = '1';
        $print_seq = 0;
        
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
            // 'articleCode'=>'required|unique:bom_hdr,article_code',
            'customer'  => 'required',
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
                    $row_affected=DB::table('bom_hdr')
                    ->where('bom_code',$bomNumber)
                    ->update(
                        [
                            'bom_code' => $bomNumber,
                            'customer' => $customer,
                            'article_code' => $articleCode,
                            'uom' => $uom,
                            'group_of_material' => $group,
                            'status' => $status,
                            'tag' => $tag,
                            'pass_rate' => $passRate,
                            'pass_thru' => $passThru,
                            'cycle_time' => $cycleTime,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $bomNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $bomNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('bom_det')
                        ->whereNotIn(DB::raw("CONCAT(bom_code,article_code)"),$dataSet)
                        ->where('bom_code',$bomNumber)
                        ->delete();

                    foreach ($articles as $val) {
                        DB::table('bom_det')
                        ->updateOrInsert(
                            ['bom_code' => $bomNumber,'article_code' => $val->article_code],
                            [
                                'bom_code' => $bomNumber,
                                'article_code' => $val->article_code,
                                'qty' => $val->qty,
                                'uom' => $val->uom,
                                'uom_con' => $val->uom_con,
                                // 'cost_price' => $val->price,
                                'article_type' => $val->type,
                                'customer_code' => $val->customer_code,
                                // 'note' => $val->note,
                                'created_by' => Auth::user()->username,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                    }
                    
                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $bomNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber,'oEdit'=>true));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$title $bomNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber));
            }
        }
    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $bomNumber = $request->bomNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$bomNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusBom = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
             
        DB::beginTransaction();
        try {
                $row_affected=DB::table('bom_hdr')
                ->where('bom_code',$bomNumber)
                ->update(
                    [
                        'status' => $statusBom,
                        'authorized_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $bomNumber,
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
                $message  = "$title $bomNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusBom' => $statusBom,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $bomNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusBom' => $statusBom,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'bomNumber'=>$bomNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $bom_code = DB::table('bom_hdr')->where('id',$id)->value('bom_code');
        $rowAffected = DB::table('bom_hdr')->where('id',$id)->update([            
            'status' => 5,
            'note' => "Deleted at ". date('Y-m-d H:i:s') .", not active",
            'updated_by' => Auth::user()->username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        if($rowAffected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$this->title $bom_code is successfully deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
       }else{
            DB::rollBack();
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$this->title $bom_code is failed to delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);            
        }
    }

    public function list(Request $request)
    {
       // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
        $username =  Auth::user()->username;
        $searchBom = strtolower($request->searchBom);
        $articleCode = $request->articleCode;
        $status = $request->status;

        $data = DB::table('bom_hdr')
        ->leftJoin('article','article.article_code','bom_hdr.article_code')
        ->where(function ($query) use ($searchBom,$articleCode,$status) {
            $searchBom ? $query->where('bom_code','ilike','%'.$searchBom.'%') : '';
            $articleCode ? $query->where('bom_hdr.article_code','ilike','%'.$articleCode.'%') : '';
            $status ? $query->where('bom_hdr.status','=',$status) : '';
        })
        ->where('bom_hdr.status','<>','7')
        ->select('bom_hdr.*'
        ,DB::raw("CONCAT(article.article_alternative_code,'-',article.article_desc) as article_des")
        ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = bom_hdr.bom_code) as approval_by")
        ,DB::raw("(SELECT username = '$username' as validate from (
            select username,approval_order,
            (select max(approval_number) from approval_master where module_code = a.module_code ) as max_level,
            COALESCE((select max(approval_order) from approval_history
            where module_code = a.module_code
            and module_number = bom_hdr.bom_code),'0') as current_level
            from approval_level a 
            where module_code = '".$this->moduleCode."' and username = '$username') b
            where approval_order = current_level+1
            ) as statusku")
        )
        ->orderBy('bom_code')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('bom-edit') && $data->status != '3') {
            $buttons .=         '<a href="'. route('bom.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }

            if ( $data->statusku and ($data->status == '2' or $data->status == '1') ){
                
                $buttons .= '<a href="'. route('bom.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                <i data-feather="check"></i>
                                <span>'. __("Approve") .'</span>
                            </a>';
            }

            $buttons .=         '<a href="'. route('bom.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            $buttons .=         '<a href="'. route('bom.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
            if (($data->status == '2') || ($data->status == '3') ){
                if (Auth::user()->can('bom-revision')) {
                    $buttons .= '<a href="'. route('bom.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) .'" class="dropdown-item">
                                    <i data-feather="copy"></i>
                                    <span>'. __("Revision") .'</span>
                                </a>';
                }
            }
                
            if (Auth::user()->can('bom-delete')) {
                $buttons .= "<a href='javascript:;'
                                class='dropdown-item' 
                                data-size='sm'
                                data-ajax-delete='true'
                                data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                data-modal-id='".$data->id."'
                                id='deleteButton'
                                data-url='". route('bom.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                <span>". __('Delete') ."</span>
                            </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        
        ->addColumn('bom_code', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];
            // $statusBo = ['NEW','VALIDATED','APPROVED','RECEIVED','DELETED','CLOSED','REVISED','DECLINE'];
            return '<span style="display: none;">'.$data->bom_code.'</span>
                    <a class="badge d-block '.$badges[$data->status - 1].'" href="'. route('bom.show', ['id'=>Crypt::encryptString($data->id)]) .'" >
                    <span>'.$data->bom_code.'</span>
                    </a>';
        })

        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $status = ['NEW','VALIDATE','APPROVED','RECEIVED','DELETED','CLOSED','REVISED','DECLINE'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','bom_code'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        
        $data['bomHdr']=DB::table('bom_hdr')
        ->leftJoin('third_party','third_party.kode','bom_hdr.customer')
        ->leftJoin('article','article.article_code','bom_hdr.article_code')
        ->where('bom_hdr.id',$id)
        ->first();

        $bomNumber=$data['bomHdr']->bom_code;

        $data['title'] = "$bomNumber";
       
        $data['details']=DB::table('bom_det')
        ->leftJoin('article','article.article_code','bom_det.article_code')
        ->where('bom_code',$bomNumber)
        ->get();
        $username="";
        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$bomNumber,$username);
        
        $data['keterangan']=$data['bomHdr'] -> note;
        $data['bomNumber'] =$bomNumber;
        
        $data['status'] ='1';
        $data['no'] =0;

        view()->share($data);

        $pdf = PDF::loadView('bom.print')->setPaper([0, 0, 595.28, 841.89], 'portrait');
        return $pdf->stream("PO_$bomNumber.pdf");

    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $bomOrigin=DB::table('bom_hdr')->where('id',$id)->value('bom_code');
        $numRevision = $request->nR ? $request->nR + 1 : 1 ;
        $bomNew = $bomOrigin.'-R'.$numRevision;
        $checkNewBom=DB::table('bom_hdr')->where('bom_code',$bomNew)->count();

        if ($checkNewBom > 0){
            $bomNew = $bomOrigin.'-R'.($numRevision+1);
        }
                
        $sqlHdr = "INSERT into bom_hdr 
        (
            bom_code,
            customer,
            article_code,
            uom,
            group_of_material,
            status,
            note,
            tag,
            pass_rate,
            pass_thru,
            cycle_time,
            created_by,
            updated_by,
            created_at,
            updated_at,
            origin_bom_code,
            num_revision,
            authorized_by,
            revised_by,
            revised_at
        )
        select 
            '$bomNew',
            customer,
            article_code,
            uom,
            group_of_material,
            '7',
            note,
            tag,
            pass_rate,
            pass_thru,
            cycle_time,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."',
            '$bomOrigin',
            $numRevision,
            authorized_by,
            '$username',
            '".date('Y-m-d H:i:s')."'
        from bom_hdr where bom_code = '$bomOrigin'";

        $sqlDet="INSERT into bom_det
        (
            bom_code,
            article_code,
            qty,
            uom,
            cost_price,
            article_type,
            customer_code,
            status,
            note,
            created_by,
            updated_by,
            created_at,
            updated_at 
        )
        select 
            '$bomNew',
            article_code,
            qty,
            uom,
            cost_price,
            article_type,
            customer_code,
            status,
            note,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."' 
        from bom_det where bom_code = '$bomOrigin'";

        $rowAffected =  DB::select($sqlHdr);
        if ($rowAffected){
            DB::select($sqlDet);

            DB::table('bom_hdr')
            ->where('bom_code',$bomOrigin)
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
            ->where('module_number',$bomOrigin)
            ->update(
                [
                    'module_number' => $bomNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revison PO: $bomOrigin to $bomNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            // return $this->showEdit(Crypt::encryptString($id));
            return redirect()->route('bom.edit', ['id'=>Crypt::encryptString($id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revison PO: $bomOrigin to $bomNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
        
    }
}
