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
use App\Exports\BomExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\BomUpload;
use App\Exports\BomTemplateExport;

/*
    CR:
    untuk brand toto simpan data seperti biasa, seperti BOM yang lainnya
    jadi di remark pada saat save data supplierToto jadi selalu false

*/

date_default_timezone_set('Asia/Bangkok');

class BomController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    private $tones;
    private $sprayBooth;


    public function __construct()
    {
        $this->title = "Bill Of Material";
        $this->moduleCode = "BOM";
        $this->decimalPlaces = config('globalParam.decimal');
        $this->tones = ['t1'=>'Tone 1','t2'=>'Tone 2','t3'=>'Tone 3','t4'=>'Tone 4'];
        $this->sprayBooths = [
            'sp1'=>'Spray Booth 1',
            'sp1a'=>'Spray Booth 1 A',
            'sp1b'=>'Spray Booth 1 B',
            'sp1c'=>'Spray Booth 1 C',
            'sp2'=>'Spray Booth 2',
            'sp2a'=>'Spray Booth 2 A',
            'sp2b'=>'Spray Booth 2 B',
            'sp2c'=>'Spray Booth 2 C',
            'sp3'=>'Spray Booth 3',
            'sp3a'=>'Spray Booth 3 A',
            'sp3b'=>'Spray Booth 3 B',
            'sp3c'=>'Spray Booth 3 C',
            'sp4'=>'Spray Booth 4',
            'sp4a'=>'Spray Booth 4 A',
            'sp4b'=>'Spray Booth 4 B',
            'sp4c'=>'Spray Booth 4 C',
            'sbtoto'=>'Toto'
        ];
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'bom_code','name'=>'bom_code','title'=>'BOM Code'],
            ['data'=>'customer','name'=>'customer','title'=>'Customer'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'article_des','name'=>'article_des','title'=>'Article'],
            ['data'=>'part_no','name'=>'part_no','title'=>'Part No'],
            ['data'=>'model','name'=>'model','title'=>'Model'],
            ['data'=>'group_of_material','name'=>'group_of_material','title'=>'Group'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            // ['data'=>'tag','name'=>'tag','title'=>'Tag','visible'=>false],
            // ['data'=>'pass_rate','name'=>'pass_rate','title'=>'Pass Rate','visible'=>false],
            // ['data'=>'pass_thru','name'=>'pass_thru','title'=>'Pass Thru','visible'=>false],
            // ['data'=>'cycle_time','name'=>'cycle_time','title'=>'Cycle Time','visible'=>false],
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

        $statistic = db::select("SELECT count(*) as jumlah_bom,
        sum(case when status = '1' then 1 else 0 end) as jumlah_baru,
        sum(case when status = '2' then 1 else 0 end) as jumlah_validate,
        sum(case when status = '3' then 1 else 0 end) as jumlah_approve
        from bom_hdr where status <> '7'");

        $data['bomTotal'] = $statistic[0]->jumlah_bom;
        $data['bomBaru'] = $statistic[0]->jumlah_baru;
        $data['bomValidate'] = $statistic[0]->jumlah_validate;
        $data['bomApprove'] = $statistic[0]->jumlah_approve;
       
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'DELETED'];

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();
                        
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

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'DELETED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];

        $data['articles'] = DB::table('article')
        ->leftJoin('third_party','article.third_party','third_party.kode')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('article_type','FG')
        ->whereNotIn('article.article_code', function($query){
            $query->select(DB::raw("COALESCE(article_code,'blablabla')"))
            ->from('bom_hdr')
            ->whereIn('status',['1','2','3']);
        })
        ->select('article.*', 'third_party.nama as cust_name','group_materials.name as group')
        ->get();

        $data['articlesRm'] = DB::table('article')
        ->whereIn('article_type',['RMP','RMNP'])
        // ->whereNotIn('article.article_code', function($query){
        //     $query->select(DB::raw("COALESCE(article_code_rm,'blablabla')"))
        //     ->from('bom_hdr')
        //     ->whereIn('status',['1','2','3']);
        // })
        ->get();

        $data['boms'] = DB::table('bom_hdr')
        ->leftJoin('article','bom_hdr.article_code','article.article_code')
        ->leftJoin('third_party','bom_hdr.customer','third_party.kode')
        ->select('bom_hdr.*', 'third_party.nama as cust_name','article.article_desc','article.article_alternative_code')
        ->whereIn('bom_hdr.status',['2','3'])
        ->get();

        $data['posts'] = DB::table('bom_pos')
        ->orderBy('pos_name')
        ->get();

        $data['oEdit']=false;

        return view("bom.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $sprayBooth = json_decode($request -> sprayBooths);
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
        $partNo = $request->partNo;
        $model = $request->model;
        $supplierToto = $request->supplierToto;

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
                    // 'tag' => $tag,
                    // 'pass_rate' => $passRate,
                    // 'pass_thru' => $passThru,
                    // 'cycle_time' => $cycleTime,
                    'note' => $note,
                    'part_no' => $partNo,
                    'model' => $model,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'origin_bom_code' => $bomNumber
                ]);

                if ($supplierToto == 'false'){
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
                            'urutan' => $val->urutan,
                            'pos'=>$val->pos,
                            'tone'=>$val->tone
                        ];
                    }
                    DB::table('bom_det')->insert($dataSet);
                }


                if ($supplierToto == 'false'){
                    DB::table('bom_spray_booth')->where('bom_code',$bomNumber)->delete();
                    $dataSetSb = [];
                    foreach ($sprayBooth as $val) {
                        $dataSetSb[] = [
                            'bom_code' => $bomNumber,
                            'spray_booth' => $val->spray_booth,
                            'tone' => $val->tone,
                            'tack' => $val->tack,
                            'pass_rate' => $val->pass_rate,
                            'pass_thru' => $val->pass_thru,
                            'urutan' => $val->urutan,
                            'cycle_time' => $val->cycle_time,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            // 'stripping' => $val->stripping,
                        ];
                    }

                    DB::table('bom_spray_booth')->insert($dataSetSb);
                }

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
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

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
        ->leftJoin('bom_pos','bom_pos.pos_code','bom_det.pos')
        ->leftJoin('article','article.article_code','=','bom_det.article_code')
        ->leftJoin('uom','uom.code','bom_det.uom')
        ->leftJoin('article_types','article_types.code','=','bom_det.article_type')
        ->select('bom_det.*'
        ,'bom_pos.pos_name'
        ,'article.uom as original_uom'
        ,'uom.uom_group as uom_group'
        ,'article_types.name as type_name'
        ,DB::RAW("(select uom_conversion(bom_det.uom_con,article.uom) as factor_qty)")
        ,DB::raw("concat(article.article_alternative_code,'-',article.article_desc) as article")
        )
        // ->where('bom_det.bom_code',$bomNumber)
        ->orderBy('bom_det.tone')
        ->orderBy('bom_det.pos')
        ->orderBy('urutan')
        ->get();

        $data['sprayBooths'] = DB::table('bom_spray_booth')
        ->whereIn('bom_spray_booth.bom_code', function($query) use ($bomNumber){
            $query->select('bom_code')->from('bom_hdr')->where('origin_bom_code',$bomNumber);
        })
        ->select('bom_spray_booth.*')
        // ->where('bom_det.bom_code',$bomNumber)
        ->orderBy('bom_spray_booth.id')
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

        // $data['arrSprayBooth'] = ['sb1'=>'Spray Booth 1','sb1'=>'Spray Booth 1','sb2'=>'Spray Booth 2','sb3'=>'Spray Booth 3','sb4'=>'Spray Booth 4'];
        
        $data['arrSprayBooth'] = ['sb1'=>'Spraybooth 1','sb1a'=>'Spraybooth 1 A','sb1b'=>'Spraybooth 1 B','sb1c'=>'Spraybooth 1 C','sb2'=>'Spraybooth 2','sb2a'=>'Spraybooth 2 A','sb2b'=>'Spraybooth 2 B','sb2c'=>'Spraybooth 2 C','sb3'=>'Spraybooth 3','sb3a'=>'Spraybooth 3 A','sb3b'=>'Spraybooth 3 B','sb3c'=>'Spraybooth 3 C','sb4'=>'Spraybooth 4','sb4a'=>'Spraybooth 4 A','sb4b'=>'Spraybooth 4 B','sb4c'=>'Spraybooth 4 C','sbtoto'=>'Toto'];

        $data['arrTone'] = ['t1'=>'Tone 1','t2'=>'Tone 2','t3'=>'Tone 3','t4'=>'Tone 4'];

        $data['posts'] = DB::table('bom_pos')
        ->orderBy('pos_name')
        ->get();

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
        ->leftJoin('article','article.article_code','=','bom_det.article_code')
        ->leftJoin('third_party','third_party.kode','=','article.third_party')
        ->select('bom_det.*'
        ,'article.uom as original_uom'
        ,'uom.uom_group as uom_group'
        ,'article_types.name as type_name'
        ,'third_party.nama as brand'
        ,DB::RAW("(select uom_conversion(bom_det.uom_con,article.uom) as factor_qty)")
        ,DB::RAW("(select 
                        string_agg(concat(unit_to,';',(uom_conversion(a.unit_to,article.uom))),',' order by unit_from) as uom_member 
                        from uom_con a where unit_from = article.uom)")
        ,DB::RAW("(select string_agg(code,',' order by code) as uoms from uom )")
        )
        // ->orderBy('bom_det.id')
        ->orderBy('bom_det.tone')
        ->orderBy('bom_det.pos')
        ->orderBy('urutan')
        ->get();

        $data['sprayBooth'] = DB::table('bom_spray_booth')
        ->where('bom_code',$bomNumber)
        ->select('bom_spray_booth.*')
        ->orderBy('urutan')
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

        $data['articlesRm'] = DB::table('article')
        ->whereIn('article_type',['RM','RMP','RMNP'])
        ->get();

        $data['posts'] = DB::table('bom_pos')
        ->orderBy('pos_name')
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
        $sprayBooth = json_decode($request -> sprayBooths);
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
        $partNo =$request->partNo;
        $model =$request->model;
        $supplierToto = $request->supplierToto;

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
            $title="Update BOM";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {
                $row_affected=DB::table('bom_hdr')
                ->where('bom_code',$bomNumber)
                ->update(
                    [
                        // 'bom_code' => $bomNumber,
                        // 'customer' => $customer,
                        // 'article_code' => $articleCode,
                        // 'uom' => $uom,
                        'article_code_rm' => $articleCodeRm,
                        'group_of_material' => $group,
                        'status' => $status,
                        // 'tag' => $tag,
                        // 'pass_rate' => $passRate,
                        // 'pass_thru' => $passThru,
                        // 'cycle_time' => $cycleTime,
                        'note' => $note,
                        'part_no' => $partNo,
                        'model' => $model,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($supplierToto == 'false'){
                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $bomNumber.$val->article_code
                        ];                  
                    }
                    /*
                    Delete kalo article tidak ada di $bomNumber dan article nya $val->article_code
                    berdasarkan 2 kondisi
                    */
                    DB::table('bom_det')
                        // ->whereNotIn(DB::raw("CONCAT(bom_code,article_code)"),$dataSet)
                        ->where('bom_code',$bomNumber)
                        ->delete();

                    // foreach ($articles as $val) {
                    //     DB::table('bom_det')
                    //     ->updateOrInsert(
                    //         ['bom_code' => $bomNumber,'article_code' => $val->article_code],
                    //         [
                    //             'bom_code' => $bomNumber,
                    //             'article_code' => $val->article_code,
                    //             'qty' => $val->qty,
                    //             'uom' => $val->uom,
                    //             'uom_con' => $val->uom_con,
                    //             // 'cost_price' => $val->price,
                    //             'article_type' => $val->type,
                    //             'customer_code' => $val->customer_code,
                    //             // 'note' => $val->note,
                    //             'created_by' => Auth::user()->username,
                    //             'created_at' => date('Y-m-d H:i:s'),
                    //             'urutan' => $val->urutan,
                    //             'pos'=>$val->pos
                    //         ]
                    //     );
                    // }

                    foreach ($articles as $val) {
                        $idKu= DB::table('bom_det')
                        ->insertGetId([
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
                            'urutan' => $val->urutan,
                            'pos'=>$val->pos,
                            'tone'=>$val->tone,
                        ]);
                    }

                    DB::table('bom_spray_booth')->where('bom_code',$bomNumber)->delete();

                    $dataSetSb = [];
                    foreach ($sprayBooth as $val) {
                        $dataSetSb[] = [
                            'bom_code' => $bomNumber,
                            'spray_booth' => $val->spray_booth,
                            'tone' => $val->tone,
                            'tack' => $val->tack,
                            'pass_rate' => $val->pass_rate,
                            'pass_thru' => $val->pass_thru,
                            'cycle_time' => $val->cycle_time,
                            'urutan' => $val->urutan,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            // 'stripping' => $val->stripping,
                        ];
                    }

                    DB::table('bom_spray_booth')->insert($dataSetSb);
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
        $boms = DB::table('bom_hdr')->where('id',$id)->first();
        $bomCode = $boms->bom_code;
        $bomStatus = $boms->status;
        // $bom_code = DB::table('bom_hdr')->where('id',$id)->value('bom_code');
        if ($bomStatus!=3){
            $rowAffected = DB::table('bom_hdr')->where('bom_code',$bomCode)->where('status','<>','3')->delete();
            DB::table('bom_det')->where('bom_code',$bomCode)->delete();
            DB::table('bom_spray_booth')->where('bom_code',$bomCode)->delete();
        }else{
            $rowAffected = DB::table('bom_hdr')->where('bom_code',$bomCode)->update([
                'status' => 5,
                'note' => "Deleted at ". date('Y-m-d H:i:s') .", not active",
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        if($rowAffected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$this->title $bomCode is successfully deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
       }else{
            DB::rollBack();
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$this->title $bomCode is failed to delete";
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
        $cust = $request->cust;

        $data = DB::table('bom_hdr')
        ->leftJoin('article','article.article_code','bom_hdr.article_code')
        ->where(function ($query) use ($searchBom,$articleCode,$status,$cust) {
            $searchBom ? $query->where('bom_code','ilike','%'.$searchBom.'%') : '';
            $articleCode ? $query->where('bom_hdr.article_code','ilike','%'.$articleCode.'%') : '';
            $status ? $query->where('bom_hdr.status','=',$status) : '';
            $cust ? $query->where('bom_hdr.customer','=',$cust) : '';
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

        $bisaEdit = Auth::user()->can('bom-edit');
        $bisaRevisi = Auth::user()->can('bom-revision');
        $bisaDelete = Auth::user()->can('bom-delete');
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) use ($bisaDelete,$bisaEdit,$bisaRevisi) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if ($bisaEdit && $data->status != '3' && $data->status != '5') {
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
                if ($bisaRevisi) {

                    $buttons .=     "<a href='javascript:;'
                                        id='revisionReasonButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#reasonModalRevision'
                                        data-href='". route("bom.revision", ["id"=>Crypt::encryptString($data->id),"nR"=>$data->num_revision]) ."'>
                                        <i data-feather='corner-down-left' class='feather-14-red'></i>
                                        <span>". __('Revision') ."</span>
                                    </a>";

                    // $buttons .= '<a href="'. route('bom.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) .'" class="dropdown-item">
                    //                 <i data-feather="copy"></i>
                    //                 <span>'. __("Revision") .'</span>
                    //             </a>';
                }
            }
                
            if ($bisaDelete) {
                if ( $data->status != '5' ){
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
        ->leftJoin('article as a','a.article_code','bom_hdr.article_code_rm')
        ->select(
            'a.article_desc as article_desc_rm',
            'bom_hdr.*'
            ,'third_party.*'
            ,'article.*'
            ,'bom_hdr.note as note_hdr'
            ,'bom_hdr.created_at as tanggal_revisi'
        )
        ->where('bom_hdr.id',$id)
        ->first();


        // dd(  $data['bomHdr']);

        $bomNumber=$data['bomHdr']->bom_code;

        $data['title'] = "$bomNumber";
       
        $data['details']=DB::table('bom_det')
        ->leftJoin('article','article.article_code','bom_det.article_code')
        ->leftJoin('third_party','third_party.kode','article.third_party')
        ->select('bom_det.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,'third_party.nama')
        ->where('bom_code',$bomNumber)
        ->orderBy('bom_det.id')
        ->get();

        $username="";
        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$bomNumber,$username);
        
        $data['keterangan']=$data['bomHdr'] -> note;
        $data['bomNumber'] =$bomNumber;
        
        $data['status'] ='1';
        $data['no'] =0;

        $judulTone=DB::table('bom_det')
        ->select('tone')
        ->where('bom_code',$bomNumber)
        ->distinct('tone')
        ->orderBy('tone','asc')
        ->get();

        $arrTone = ['t1'=>'Tone 1','t2'=>'Tone 2','t3'=>'Tone 3','t4'=>'Tone 4'];
        $barisAll="";
        $barisSub="";
        $barisJudul="";
        $jumlahBaris=0;

        foreach($judulTone as $val){
            $tone = $val->tone ? $val->tone : '';
            
            // if($val->tone != null){
                // $barisTone = "<tr><td colspan='6' align='center' style='background-color:#51b3f0'>".strtoupper($arrTone[$tone])."</td> </tr>";
                $judulGroup=DB::table('bom_det')
                ->leftJoin('bom_pos','bom_pos.pos_code','bom_det.pos')
                ->select('pos_code','pos_name')
                ->where('bom_code',$bomNumber)
                ->where(function ($query) use ($tone) {
                    $tone ? $query->where('bom_det.tone',$tone) : '';
                })
                //->where('tone',$tone)
                ->distinct('pos_code','pos_name','bom_pos.urutan')
                ->orderBy('bom_pos.urutan','asc')
                ->get();

                if($val->tone != null){
                    $barisTone = "<tr><td colspan='6' align='center' style='background-color:#51b3f0'>".strtoupper($arrTone[$tone])."</td> </tr>";
                    $jumlahBaris++;
                }else{
                    $barisTone = ""; 
                }
            // }else{
            //     $barisTone = "";
            //     $judulGroup=DB::table('bom_det')
            //     ->leftJoin('bom_pos','bom_pos.pos_code','bom_det.pos')
            //     ->select('pos_code','pos_name')
            //     ->where('bom_code',$bomNumber)
            //     // ->where('tone','')
            //     ->distinct('pos_code','pos_name')
            //     ->orderBy('pos_name','desc')
            //     ->get();
            // }
            
            foreach($judulGroup as $val){
                $groupPos = $val->pos_code ? $val->pos_code : '';
                if($val->pos_code != null){
                    $barisJudul = "<tr><td colspan='6' align='center' style='background-color:yellow'>".strtoupper($val->pos_name)."</td> </tr>";
                    $isiJudul=DB::table('bom_det')
                    ->leftJoin('article','article.article_code','bom_det.article_code')
                    ->leftJoin('third_party','third_party.kode','article.third_party')
                    ->select('bom_det.*'
                    ,'article.article_alternative_code'
                    ,'article.article_desc'
                    ,'third_party.nama'
                    ,'bom_det.id as idku')
                    ->where('bom_code',$bomNumber)
                    ->where('bom_det.pos',$groupPos)
                    ->where(function ($query) use ($tone) {
                        $tone ? $query->where('bom_det.tone',$tone) : '';
                    })
                    // ->where('bom_det.tone',$tone)
                    ->orderBy('bom_det.id')
                    ->get();
                    $jumlahBaris++;
                }else{
                    $barisJudul = "";
                    $isiJudul=DB::table('bom_det')
                    ->leftJoin('article','article.article_code','bom_det.article_code')
                    ->leftJoin('third_party','third_party.kode','article.third_party')
                    ->select('bom_det.*'
                    ,'article.article_alternative_code'
                    ,'article.article_desc'
                    ,'third_party.nama'
                    ,'bom_det.id as idku')
                    ->where('bom_code',$bomNumber)
                    ->where(function ($query) use ($tone) {
                        $tone ? $query->where('tone',$tone) : '';
                    })
                    // ->where('bom_det.pos',$groupPos)
                    ->orderBy('bom_det.id')
                    ->get();
                }
                $barisIsiJudul='';
                foreach($isiJudul as $key=>$item){
                    $no = $key+1;
                    $barisIsiJudul .= "<tr >
                        <td class='detail-padding' align='center' scope='row' style='padding-left:3px;padding-right:3px'>$no</td>
                        <td class='detail-padding' align='left' style='padding-left:3px;padding-right:3px'>$item->article_desc</td>
                        <td class='detail-padding font-9' align='left' style='padding-left:3px;padding-right:3px'>$item->nama</td>
                        <td class='detail-padding' align='right' style='padding-left:3px;padding-right:3px'>$item->qty</td>
                        <td class='detail-padding' align='left' style='padding-left:3px;padding-right:3px'>$item->uom</td>
                        <td class='detail-padding' align='left' style='padding-left:3px;padding-right:3px'>$item->article_alternative_code</td>
                    </tr>";              
                    $jumlahBaris++;  
                }

                $barisSub .=$barisJudul.$barisIsiJudul;
            };

            $barisAll.= $barisTone.$barisSub;
            $barisSub="";
            $barisTone="";
        };

        
        // $barisAll="";
        // foreach($judulGroup as $val){
        //     $groupPos = $val->pos_code ? $val->pos_code : '';
        //     if($val->pos_code != null){
        //         $barisJudul = "<tr><td colspan='6' align='center' style='background-color:yellow'>".strtoupper($val->pos_name)."</td> </tr>";
        //         $isiJudul=DB::table('bom_det')
        //         ->leftJoin('article','article.article_code','bom_det.article_code')
        //         ->leftJoin('third_party','third_party.kode','article.third_party')
        //         ->select('bom_det.*'
        //         ,'article.article_alternative_code'
        //         ,'article.article_desc'
        //         ,'third_party.nama')
        //         ->where('bom_code',$bomNumber)
        //         ->where('bom_det.pos',$groupPos)
        //         ->orderBy('bom_det.id')
        //         ->get();
        //     }else{
        //         $barisJudul = "";
        //         $isiJudul=DB::table('bom_det')
        //         ->leftJoin('article','article.article_code','bom_det.article_code')
        //         ->leftJoin('third_party','third_party.kode','article.third_party')
        //         ->select('bom_det.*'
        //         ,'article.article_alternative_code'
        //         ,'article.article_desc'
        //         ,'third_party.nama')
        //         ->where('bom_code',$bomNumber)
        //         // ->where('bom_det.pos',$groupPos)
        //         ->orderBy('bom_det.id')
        //         ->get();
        //     }
        //     $barisIsiJudul='';
        //     foreach($isiJudul as $key=>$item){
        //         $no = $key+1;
        //         $barisIsiJudul .= "<tr >
        //             <td class='detail-padding' align='center' scope='row' style='padding-left:3px;padding-right:3px'>$no</td>
        //             <td class='detail-padding' align='left' style='padding-left:3px;padding-right:3px'>$item->article_desc</td>
        //             <td class='detail-padding font-10' align='left' style='padding-left:3px;padding-right:3px'>$item->nama</td>
        //             <td class='detail-padding' align='right' style='padding-left:3px;padding-right:3px'>$item->qty</td>
        //             <td class='detail-padding' align='left' style='padding-left:3px;padding-right:3px'>$item->uom</td>
        //             <td class='detail-padding' align='left' style='padding-left:3px;padding-right:3px'>$item->article_alternative_code</td>
        //         </tr>";
        //     }

        //     $barisAll = $barisAll.$barisJudul.$barisIsiJudul;
        // };
        
        $data['jumlahBaris'] = $jumlahBaris;

        $data['barisDetail']=$barisAll;

        // dd($barisAll);

        view()->share($data);

        $pdf = PDF::loadView('bom.print')->setPaper([0, 0, 595.28, 841.89], 'portrait');
        return $pdf->stream("PO_$bomNumber.pdf");

    }

    public function revision(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $bomOrigin=DB::table('bom_hdr')->where('id',$id)->value('bom_code');
        $numRevision = $request->nR ? $request->nR + 1 : 1 ;
        $bomNew = $bomOrigin.'-R'.$numRevision;
        $checkNewBom=DB::table('bom_hdr')->where('bom_code',$bomNew)->count();

        $reasonRequest = $request->reason;

        $reason = "(Revision by $username, $reasonRequest )";

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
            revised_at,
            part_no,
            model,
            article_code_rm,
            revision_reason
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
            '".date('Y-m-d H:i:s')."',
            part_no,
            model,
            article_code_rm,
            '$reasonRequest'
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
            updated_at,
            uom_con,
            urutan,
            pos,
            tone
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
            '".date('Y-m-d H:i:s')."',
            uom_con,
            urutan,
            pos,
            tone
        from bom_det where bom_code = '$bomOrigin' order by id";

        $sqlAprayBooth="INSERT into bom_spray_booth
        (
            bom_code,
            spray_booth,
            tone,
            tack,
            pass_rate,
            pass_thru,
            urutan,
            cycle_time,
            created_by,
            created_at
        )
        select 
            '$bomNew',
            spray_booth,
            tone,
            tack,
            pass_rate,
            pass_thru,
            urutan,
            cycle_time,
            '$username',
            '".date('Y-m-d H:i:s')."'
        from bom_spray_booth where bom_code = '$bomOrigin' order by id";

        $rowAffected =  DB::select($sqlHdr);
        if ($rowAffected){
            DB::select($sqlDet);
            DB::select($sqlAprayBooth);

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
            $message  = "$title Revision PO: $bomOrigin to $bomNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            // return $this->showEdit(Crypt::encryptString($id));
            return redirect()->route('bom.edit', ['id'=>Crypt::encryptString($id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision PO: $bomOrigin to $bomNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
        
    }

    public function exportBom(Request $request) 
    {
        $username =  Auth::user()->username;
        $searchBom = strtolower($request->searchBom);
        $articleCode = $request->articleCode;
        $status = $request->status;
        $filename = 'data_bom';
        return Excel::download(new BomExport($searchBom,$articleCode,$status), $filename.'.xlsx');
    }

    public function exportTemplate()
    {
		return Excel::download(new BomTemplateExport, 'bom_upload_template.xls');
	}

    public function uploadExcel(Request $request)
    {

        // validasi
		$this->validate($request, [
			'file' => 'required|mimes:xls,xlsx'
		]);
 
		// menangkap file excel
		$file = $request->file('file');
 
		// // membuat nama file unik
		$namaFile = rand().$file->getClientOriginalName();

        $data['filename']=$namaFile;
        db::table('bom_upload_tmp')->delete();
        db::table('bom_spray_booth_upload_tmp')->delete();
        db::table('bom_hdr_upload_tmp')->delete();

        Excel::import(new BomUpload($data), $file);

        // $dataValidasi = DB::table('import_stock_take_tmp')
        // ->leftJoin('article','article.article_alternative_code','import_stock_take_tmp.article_code')
        // ->select('import_stock_take_tmp.article_code'
        // ,'import_stock_take_tmp.qty'
        // ,DB::RAW("concat(
        //     case when import_stock_take_tmp.qty::text ~ '^[0-9.]+$' = false then concat('Urutan ',row_number() over(),': Qty salah - ',qty) end,
        //     case when article.article_code is null then concat('Urutan ',row_number() over(),': Article Code:',import_stock_take_tmp.article_code, ' tidak terdaftar') end
        //     ) as notes")
        // )
        // ->where('file_name', $namaFile)
        // ->get();

        // $dataNotes=[];
        // foreach ($dataValidasi as $val) {
        //     if($val->notes){
        //         $dataNotes[]= [$val->notes];
        //     }
        // } 

        // $title ="Import $this->title";
        // $pesan="";

        // if (count($dataNotes) > 0 ){
        //     $pesan .='Ada error pada data yang diupload, silahkan cek notes error!';
        //     $status = 0;
        //     $alert = "error";
        //     $message = $dataNotes;
        //     $data = "";

        // }else{

        //     // return redirect()->back()->with('success', 'Excel file imported successfully!');
        //     $data = db::table('import_stock_take_tmp')
        //     ->leftJoin('article','article.article_alternative_code','import_stock_take_tmp.article_code')
        //     ->select('article.article_code'
        //     ,'article.uom'
        //     ,'import_stock_take_tmp.qty'
        //     ,DB::RAW("(select string_agg(unit_to,',' order by unit_from) as uom_member from uom_con where unit_from = article.uom)"))
        //     ->where('file_name', $namaFile)
        //     ->get();    
            
        //     $status = 1;
        //     $alert = "success";
        //     $message  = "$title is successfully imported";

        // }

        $title = "Upload BOM from Excel";
        $pesan = "";
        $status = 1;
        $alert = "success";
        $message  = "$title is successfully imported";
                  
        $alert  ="success";
        $message  = "$title is successfully imported";

        return response()->json(array('status' => $status,'title' => $title, 'message' => $message,'alert' =>$alert,'dataDetail'=>$data,'pesan'=>$pesan));

        // return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'dataDetail'=>$data]);
    }

    public function indexUpload(Request $request)
    {
        $data['title'] = "Import BOM from Excel";     
        $data['kolom'] = $this->getTableColoumnUpload();               
        return view("bom.uploadExcel",$data);
    }

    public function getTableColoumnUpload(){
        $kolom=
        [
            // ['data'=>'bom_code','name'=>'bom_code','title'=>'BOM Code'],
            ['data'=>'customer','name'=>'customer','title'=>'Customer'],
            ['data'=>'article_code_fg','name'=>'article_code_fg','title'=>'Article FG'],
            ['data'=>'article_fg_des','name'=>'article_fg_des','title'=>'Article FG Desc'],
            ['data'=>'article_code_rm','name'=>'article_code_rm','title'=>'Article RM'],
            ['data'=>'article_rm_des','name'=>'article_rm_des','title'=>'Article RM Desc'],
            ['data'=>'part_no','name'=>'part_no','title'=>'Part No'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'errors','name'=>'errors','title'=>'Errors'],
        ];
        return json_encode($kolom, true);
    }

    public function listUploadExcel(Request $request)
    {
        $username =  Auth::user()->username;
        $searchBom = strtolower($request->searchBom);

        // $data= DB::table('bom_hdr_upload_tmp')
        // ->select('bom_hdr_upload_tmp.*'
        //     ,'rm.article_desc as article_rm_des'
        //     ,'fg.article_desc as article_fg_des'
        //     ,DB::RAW("concat(
        //             (select concat('Article sudah terdaftar di BOM No: ', bom_code,'|') from bom_hdr where article_code = (select article_code from article where article_alternative_code = article_code_fg) and status = '3'),
        //             case when (select count(*) from bom_hdr_upload_tmp bhut where bhut.article_code_fg = bom_hdr_upload_tmp.article_code_fg) > 1 then concat('Data FG lebih dari satu data header (',bom_hdr_upload_tmp.article_code_fg,'-',bom_hdr_upload_tmp.article_code_rm,')|') end,
        //             case when (select count(*) from third_party where customer = bom_hdr_upload_tmp.customer) = 0 then concat('Kode customer:',bom_hdr_upload_tmp.customer, ' tidak terdaftar','|') end,
        //             case when (select count(*) from bom_hdr_upload_tmp where article_code_fg not in (select article_code_fg from bom_spray_booth_upload_tmp)) > 0 then concat('Data Spray Booth kosong','|') end,
        //             case when fg.article_desc is null then concat('Article Code FG:',bom_hdr_upload_tmp.article_code_fg, ' tidak terdaftar','|') end,
        //             case when rm.article_desc is null then concat('Article Code RM:',bom_hdr_upload_tmp.article_code_rm, ' tidak terdaftar','|') end,

        //             (select string_agg((case when qty::text ~ '^[0-9.]+$' = false then concat('Qty salah (',qty,')') end),'|') as oki from bom_upload_tmp
        //             where article_code_fg = bom_hdr_upload_tmp.article_code_fg and (qty::text !~ '^[0-9.]+$')),

        //             (select string_agg(concat('UOM Con tidak terdaftar di ',article_code_fg,' Uom Con: ',uom_con,'|'),'') as oki from bom_upload_tmp
        //             where article_code_fg = bom_hdr_upload_tmp.article_code_fg 
        //             and uom_con not in (select code from uom)),

        //             (select string_agg(concat('UOM tidak terdaftar di ',article_code_fg,' Uom: ',uom,'|'),'') as oki from bom_upload_tmp
        //             where article_code_fg = bom_hdr_upload_tmp.article_code_fg 
        //             and uom not in (select code from uom)),

        //             (select string_agg(concat('POS tidak terdaftar di ',article_code_fg,' Pos: ',pos,'|'),'') as oki from bom_upload_tmp
        //             where article_code_fg = bom_hdr_upload_tmp.article_code_fg  
        //             and pos not in (select pos_code from bom_pos)),

        //             (select string_agg(concat('Tone tidak terdaftar di ',article_code_fg,' Tone: ',tone,'|'),'') as oki from bom_upload_tmp
        //             where article_code_fg = bom_hdr_upload_tmp.article_code_fg
        //             and tone not in ('t1','t2','t3','t4')),

        //             (select string_agg(concat('Spray Booth tidak terdaftar di ',article_code_fg,' SB: ',spray_booth,'|'),'') as oki from bom_spray_booth_upload_tmp
        //             where article_code_fg = bom_spray_booth_upload_tmp.article_code_fg
        //             and spray_booth not in ('sb1','sb2','sb3','sb4')),

        //             (select string_agg((case when tack::text ~ '^[0-9.]+$' = false then concat('Tack salah (',tack,')|') end),'') as oki from bom_spray_booth_upload_tmp bsbut
        //             where bsbut.article_code_fg = bom_hdr_upload_tmp.article_code_fg and (tack::text !~ '^[0-9.]+$')),

        //             (select string_agg((case when pass_rate::text ~ '^[0-9.]+$' = false then concat('Pass Rate salah (',pass_rate,')|') end),'') as oki from bom_spray_booth_upload_tmp bsbut
        //             where bsbut.article_code_fg = bom_hdr_upload_tmp.article_code_fg and (pass_rate::text !~ '^[0-9.]+$')), 

        //             (select string_agg((case when pass_thru::text ~ '^[0-9.]+$' = false then concat('Pass Thru salah (',pass_thru,')|') end),'') as oki from bom_spray_booth_upload_tmp bsbut
        //             where bsbut.article_code_fg = bom_hdr_upload_tmp.article_code_fg and (pass_thru::text !~ '^[0-9.]+$')),

        //             (select string_agg((case when cycle_time::text ~ '^[0-9.]+$' = false then concat('Cycle Time salah (',cycle_time,')|') end),'') as oki from bom_spray_booth_upload_tmp bsbut
        //             where bsbut.article_code_fg = bom_hdr_upload_tmp.article_code_fg and (cycle_time::text !~ '^[0-9.]+$'))
        //             ) as errors")
        // )
        // ->leftJoin('third_party','third_party.kode','bom_hdr_upload_tmp.customer')
        // ->leftJoin('article as rm','rm.article_alternative_code','bom_hdr_upload_tmp.article_code_rm')
        // ->leftJoin('article as fg','fg.article_alternative_code','bom_hdr_upload_tmp.article_code_fg')
        // ->get(); 

        $data = DB::table('bom_hdr_upload_tmp')
        ->select(
            'bom_hdr_upload_tmp.*',
            'rm.article_desc as article_rm_des',
            'fg.article_desc as article_fg_des',
            DB::raw("CONCAT(
                (select concat('Article sudah terdaftar di BOM No: ', bom_code,'|') from bom_hdr where article_code = (select article_code from article where article_alternative_code = article_code_fg) and status = '3'),
                case when (select count(*) from bom_hdr_upload_tmp bhut where bhut.article_code_fg = bom_hdr_upload_tmp.article_code_fg) > 1 then concat('Data FG lebih dari satu data header (',bom_hdr_upload_tmp.article_code_fg,'-',bom_hdr_upload_tmp.article_code_rm,')|') end,
                case when (select count(*) from third_party where customer = bom_hdr_upload_tmp.customer) = 0 then concat('Kode customer:',bom_hdr_upload_tmp.customer, ' tidak terdaftar','|') end,
                case when (select count(*) from bom_hdr_upload_tmp where article_code_fg not in (select article_code_fg from bom_spray_booth_upload_tmp)) > 0 then concat('Data Spray Booth kosong','|') end,
                case when fg.article_desc is null then concat('Article Code FG:',bom_hdr_upload_tmp.article_code_fg, ' tidak terdaftar','|') end,
                case when rm.article_desc is null then concat('Article Code RM:',bom_hdr_upload_tmp.article_code_rm, ' tidak terdaftar','|') end,
                COALESCE(qty_errors.errors, ''),
                COALESCE(uom_errors.errors, ''),
                COALESCE(uom_con_errors.errors, ''),
                COALESCE(pos_errors.errors, ''),
                COALESCE(tone_errors.errors, ''),
                COALESCE(spray_booth_errors.errors, ''),
                COALESCE(tack_errors.errors, ''),
                COALESCE(pass_rate_errors.errors, ''),
                COALESCE(pass_thru_errors.errors, ''),
                COALESCE(cycle_time_errors.errors, '')
                
            ) as errors")
        )
        ->leftJoin('third_party', 'third_party.kode', 'bom_hdr_upload_tmp.customer')
        ->leftJoin('article as rm', 'rm.article_alternative_code', 'bom_hdr_upload_tmp.article_code_rm')
        ->leftJoin('article as fg', 'fg.article_alternative_code', 'bom_hdr_upload_tmp.article_code_fg')

        ->leftJoin(DB::raw("(SELECT article_code_fg as qty_errors_fg,  
                    STRING_AGG(CASE WHEN qty::text !~ '^[0-9.]+$' THEN CONCAT('Qty salah (', qty, ')') END, '|') as errors
            FROM bom_upload_tmp 
            GROUP BY article_code_fg) as qty_errors"), 
            'qty_errors.qty_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')

        ->leftJoin(DB::raw("(SELECT article_code_fg as uom_errors_fg,
                    STRING_AGG(CASE WHEN uom NOT IN (SELECT code FROM uom) THEN CONCAT('UOM tidak terdaftar di ', article_code_fg, ' Uom: ', uom, '|') END, '') as errors
            FROM bom_upload_tmp 
            GROUP BY article_code_fg) as uom_errors"),
            'uom_errors.uom_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')
            
        ->leftJoin(DB::raw("(SELECT article_code_fg as uom_con_errors_fg,
                    STRING_AGG(CASE WHEN uom_con NOT IN (SELECT code FROM uom) THEN CONCAT('UOM Con tidak terdaftar di ', article_code_fg, ' Uom Con: ', uom_con, '|') END, '') as errors
            FROM bom_upload_tmp 
            GROUP BY article_code_fg) as uom_con_errors"),
            'uom_con_errors.uom_con_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')

        ->leftJoin(DB::raw("(SELECT article_code_fg as pos_errors_fg,
                    STRING_AGG(CASE WHEN pos NOT IN (SELECT pos_code FROM bom_pos) THEN CONCAT('POS tidak terdaftar di ', article_code_fg, ' Pos: ', pos, '|') END, '') as errors
            FROM bom_upload_tmp 
            GROUP BY article_code_fg) as pos_errors"),
            'pos_errors.pos_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')

        ->leftJoin(DB::raw("(SELECT article_code_fg as tone_errors_fg,
                    STRING_AGG(CASE WHEN tone NOT IN ('t1','t2','t3','t4') THEN CONCAT('Tone tidak terdaftar di ', bom_upload_tmp.article_code_fg, ' Tone: ', tone, '|') END, '') as errors
            FROM bom_upload_tmp 
            GROUP BY article_code_fg) as tone_errors"),
            'tone_errors.tone_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')

        ->leftJoin(DB::raw("(SELECT article_code_fg as spray_booth_errors_fg,
                    STRING_AGG(CASE WHEN spray_booth NOT IN ('sb1','sb2','sb3','sb4') THEN CONCAT('Spray Booth tidak terdaftar di ', bom_spray_booth_upload_tmp.article_code_fg, ' Spray Booth: ', spray_booth, '|') END, '') as errors
            FROM bom_spray_booth_upload_tmp 
            GROUP BY article_code_fg) as spray_booth_errors"),
            'spray_booth_errors.spray_booth_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')

        ->leftJoin(DB::raw("(SELECT article_code_fg as tack_errors_fg,  
                    STRING_AGG(CASE WHEN tack::text !~ '^[0-9.]+$' THEN CONCAT('Tack salah (', tack, ')|') END, '') as errors
            FROM bom_spray_booth_upload_tmp 
            GROUP BY article_code_fg) as tack_errors"), 
            'tack_errors.tack_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')
        
        ->leftJoin(DB::raw("(SELECT article_code_fg as pass_rate_errors_fg,  
                    STRING_AGG(CASE WHEN pass_rate::text !~ '^[0-9.]+$' THEN CONCAT('Pass Rate salah (', pass_rate, ')|') END, '') as errors
            FROM bom_spray_booth_upload_tmp 
            GROUP BY article_code_fg) as pass_rate_errors"), 
            'pass_rate_errors.pass_rate_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')

        ->leftJoin(DB::raw("(SELECT article_code_fg as pass_thru_errors_fg,  
                    STRING_AGG(CASE WHEN pass_thru::text !~ '^[0-9.]+$' THEN CONCAT('Pass Thru salah (', pass_thru, ')|') END, '') as errors
            FROM bom_spray_booth_upload_tmp 
            GROUP BY article_code_fg) as pass_thru_errors"), 
            'pass_thru_errors.pass_thru_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')
        
        ->leftJoin(DB::raw("(SELECT article_code_fg  as cycle_time_errors_fg,  
                    STRING_AGG(CASE WHEN cycle_time::text !~ '^[0-9.]+$' THEN CONCAT('Cycle Time salah (', cycle_time, ')|') END, '') as errors
            FROM bom_spray_booth_upload_tmp 
            GROUP BY article_code_fg) as cycle_time_errors"), 
            'cycle_time_errors.cycle_time_errors_fg', '=', 'bom_hdr_upload_tmp.article_code_fg')

        ->get();

        return Datatables::of($data)
        ->addColumn('errors', function ($data) {
            $listError = explode("|", $data->errors);
            $listErrors="";
            foreach ($listError as $value) {
                if($value == "") continue;
                $listErrors = $listErrors ."- " . $value . "<br>";
            }
            return $listErrors;
        })
        ->rawColumns(['errors'])
        ->make(true);
    }

    public function bomList(Request $request)
    {

        try {
            $bomCode = $request->bomCode;
            
            $data = DB::table('bom_det')
            ->select('bom_det.*'
                ,'article.article_alternative_code'
                ,'article.article_code as article_code_1'
                ,'article.article_desc as article_desc'
                ,'article.uom as original_uom'
                ,'article_types.name as type_name'
                ,'bom_pos.pos_name'
                ,'uom.uom_group'
                ,'third_party.nama'
                ,DB::RAW("(select uom_conversion(bom_det.uom_con,article.uom) as factor)")
                ,DB::RAW("(select 
                            string_agg(concat(unit_to,';',(uom_conversion(a.unit_to,article.uom))),',' order by unit_from) as uom_member 
                            from uom_con a where unit_from = article.uom)")
                ,DB::RAW("(select string_agg(code,',' order by code) as uoms from uom )")
            )
            ->leftJoin('bom_pos','bom_det.pos','=','bom_pos.pos_code')
            ->leftJoin('article','article.article_code','=','bom_det.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->leftJoin('article_types','article_types.code','=','article.article_type')
            ->leftJoin('third_party','third_party.kode','=','article.third_party')
            ->leftJoin('uom','uom.code','bom_det.uom')
            ->where('bom_code',$bomCode)
            ->orderBy('bom_det.tone')
            ->orderBy('bom_det.pos')
            ->orderBy('urutan')
            ->get();

            return response()->json(array('data' => $data,'tones' => $this->tones));
        
        } catch (Exception $e) {

            return response()->json(array('status' => 0, 'message' => 'Something went wrong.'));
        }
    }

    public function getSpayBooths(Request $request)
    {

        try {
            
            $bomNumber = $request->bomNumber;
            $data = DB::table('bom_spray_booth')
            ->where('bom_code',$bomNumber)
            ->select('bom_spray_booth.*')
            ->orderBy('urutan')
            ->get();

            return response()->json(array('data' => $data, 'status' => 1, 'message' => 'Success.'));
        
        } catch (Exception $e) {

            return response()->json(array('status' => 0, 'message' => 'Something went wrong.'));

        }

    }


}
