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

class WorkingOrderSheetController extends Controller
{   
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "WOS";
        $this->moduleCode = "WO";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'wo_code','name'=>'wo_code','title'=>'Wo Code'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'wo_date','name'=>'wo_date','title'=>'Wo Date'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'wo_shift','name'=>'wo_shift','title'=>'Shift'],
            ['data'=>'wo_group','name'=>'wo_group','title'=>'Group'],
            ['data'=>'start_time','name'=>'start_time','title'=>'Start Time'],
            ['data'=>'working_hour','name'=>'working_hour','title'=>'Working Hour'],
            ['data'=>'efficiency','name'=>'efficiency','title'=>'Efficiency'],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'urutan','name'=>'urutan','title'=>'Urutan'],
            ['data'=>'wo_code','name'=>'wo_code','title'=>'Wo Code'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'so_code','name'=>'so_code','title'=>'So Code'],
            ['data'=>'wo_shift','name'=>'wo_shift','title'=>'Shift'],
            ['data'=>'wo_group','name'=>'wo_group','title'=>'Group'],
            ['data'=>'start_time','name'=>'start_time','title'=>'Start Time'],
            ['data'=>'working_hour','name'=>'working_hour','title'=>'Working Hour'],
            ['data'=>'efficiency','name'=>'efficiency','title'=>'Efficiency'],
            ['data'=>'so_qty','name'=>'so_qty','title'=>'So Qty'],
            ['data'=>'article_fg','name'=>'article_fg','title'=>'Article FG'],
            ['data'=>'article_fg_desc','name'=>'article_fg_desc','title'=>'Desc'],
            ['data'=>'article_rm','name'=>'article_rm','title'=>'Article RM'],
            ['data'=>'article_rm_desc','name'=>'article_rm_desc','title'=>'Desc'],
            ['data'=>'plan_time_loading','name'=>'plan_time_loading','title'=>'Plan Time'],
            ['data'=>'plan_qty_fresh','name'=>'plan_qty_fresh','title'=>'Plan Qty Frsh'],
            ['data'=>'plan_qty_repaint','name'=>'plan_qty_repaint','title'=>'Plan Qty Rep'],
            ['data'=>'plan_tag','name'=>'plan_tag','title'=>'Tag'],
            ['data'=>'note_hdr','name'=>'note_hdr','title'=>'Note']

        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['subtitle'] = "$this->title";

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED'];

        return view("workingOrderSheet.index",$data);
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
        $woNumber="$key/ASN/$year/$month/$newCode";
        
        return $woNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Input $this->title";
        $data['subtitle'] = "Input $this->title";
        $data['oEdit']=false;
        $data['statusWo']='NEW';
               
        return view("workingOrderSheet.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);        
        $woDate = date("Y-m-d", strtotime($request->wosDate));
        $woShift = $request->shift;
        $woGroup = $request->group;
        $woTime = $request->wosTime;
        $workHour = $request->workHour;
        $efficiency = $request->efficiency;
        $note = $request->note;
        $status = '1';
        $oEdit = true;

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            // 'wosDate'  => 'required',
            // 'wosGroup'  => 'required',
            // 'wosShift'  => 'required',
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
            $hasilUpdate = AppHelpers::resetCode($this->moduleCode);
            $woNumber = $this->getLastCode($this->moduleCode);
            DB::beginTransaction();
            try {
                    DB::table('wo_hdr')->insert([
                        'wo_code' =>$woNumber,
                        'original_wo_code' =>$woNumber,
                        'wo_date' => $woDate,
                        'wo_shift' => $woShift,
                        'wo_group' => $woGroup,
                        'start_time' => $woTime,
                        'working_hour'=> $workHour,
                        'efficiency' => $efficiency,
                        'num_revision' => 0,
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
                            "wo_code" => $woNumber,
                            "so_code" => $val->so_code,
                            "so_qty" => $val->qty_so,
                            "urutan" => $val->urutan,
                            "article_code" => $val->article_code,
                            "article_rm_code" => $val->article_rm,
                            "plan_time_loading" => $val->waktu,
                            "act_time_loading" => 0,
                            "plan_qty_fresh" => $val->qty_prod,
                            "plan_qty_repaint" => $val->qty_repaint,
                            "plan_tag" => $val->tag,
                            "act_qty_fresh" => 0,
                            "act_qty_repaint" => 0,
                            "act_tag" => 0,
                            "origin_tag" => $val->tag_asli,
                            "qty_ok" => 0,
                            "qty_repair" => 0,
                            "qty_repaint" => 0,
                            "created_by" => Auth::user()->username,
                            "status" => $val->status,
                            "created_at" => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('wo_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $woNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'wosNumber'=>$woNumber,'oEdit'=>$oEdit));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $woNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'wosNumber'=>$woNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('wo_hdr')
        ->where('original_wo_code', function($query) use ($id){
            $query->select('wo_code')->from('wo_hdr')->where('id',$id);
        })
        ->select('wo_hdr.*'
        ,DB::raw("(working_hour*3600*(efficiency/100))/30 as sum_time_required")
        ,DB::raw("(select sum(plan_tag) from wo_det where wo_code=wo_hdr.wo_code) as sum_available_time  ")
        )
        ->orderBy('id')
        ->get();

        $woCode = $data['headers'][0]->wo_code;

        $data['details'] = DB::table('wo_det')
        ->leftJoin('article','article.article_code','=','wo_det.article_code')
        ->whereIn('wo_det.wo_code', function($query) use ($woCode){
            $query->select('wo_code')->from('wo_hdr')->where('original_wo_code',$woCode);
        })
        ->select('wo_det'.'.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,DB::raw("case when so_code ='other' then wo_det.article_code else concat(article.article_alternative_code,' - ',article.article_desc) end as article")
        )
        ->orderBy('urutan')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$woCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$woCode,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'',6=>'',7=>'REVISED'];
        $statusWo = ['NEW','VALIDATED','APPROVED','','','','REVISED'];
        $data['statusWo'] = $statusWo[$data['headers'][0]->status-1];

        return view("workingOrderSheet.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('wo_hdr')
        ->where('id',$id)
        ->get()->first();

        $woCode = $data['header']->wo_code;

        $data['details'] = DB::table('wo_det')
        ->leftJoin('article','article.article_code','=','wo_det.article_code')
        ->where('wo_code',$woCode)
        ->select('wo_det'.'.*'
        , 'article.article_alternative_code'
        ,'article.article_desc')
        ->orderBy('urutan')
        ->get();

        // dd($data['details']);

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$woCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$woCode,$username);

        $data['oEdit']=true;

         // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'CANCELED','6'=>'CLOSED'];
         $statusWo = ['NEW','VALIDATED','APPROVED','PROCESS','CANCELED'];
         $data['statusWo'] = $statusWo[$data['header']->status-1];

        return view("workingOrderSheet.edit",$data);
        
    }

    public function update(Request $request)
    {

        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);        
        $woDate = date("Y-m-d", strtotime($request->wosDate));
        $woShift = $request->shift;
        $woGroup = $request->group;
        $woTime = $request->wosTime;
        $workHour = $request->workHour;
        $efficiency = $request->efficiency;
        $note = $request->note;
        $oEdit = true;
        $woNumber = $request->wosNumber;

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'PROCESS','5'=>'CANCELED'];
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "WOS Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            // 'wosDate'  => 'required',
            // 'wosGroup'  => 'required',
            // 'wosShift'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
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
                    $row_affected=DB::table('wo_hdr')
                    ->where('wo_code',$woNumber)
                    ->update(
                        [
                            'wo_date' => $woDate,
                            'wo_shift' => $woShift,
                            'wo_group' => $woGroup,
                            'start_time' => $woTime,
                            'working_hour'=> $workHour,
                            'efficiency' => $efficiency,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    DB::table('wo_det')
                    ->where('wo_code',$woNumber)
                    ->delete();

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            "wo_code" => $woNumber,
                            "so_code" => $val->so_code,
                            "so_qty" => $val->qty_so,
                            "urutan" => $val->urutan,
                            "article_code" => $val->article_code,
                            "article_rm_code" => $val->article_rm,
                            "plan_time_loading" => $val->waktu,
                            "act_time_loading" => 0,
                            "plan_qty_fresh" => $val->qty_prod,
                            "plan_qty_repaint" => $val->qty_repaint,
                            "plan_tag" => $val->tag,
                            "act_qty_fresh" => 0,
                            "act_qty_repaint" => 0,
                            "act_tag" => 0,
                            "origin_tag" => $val->tag_asli,
                            "qty_ok" => 0,
                            "qty_repair" => 0,
                            "qty_repaint" => 0,
                            "created_by" => Auth::user()->username,
                            "status" => $val->status,
                            "created_at" => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('wo_det')->insert($dataSet);

                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $woNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'wosNumber'=>$woNumber,'oEdit'=>$oEdit));
    
            } catch (Exception $e) {

                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$title $woNumber is failed to update";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'wosNumber'=>$woNumber,'oEdit'=>$oEdit));
            }
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $woNumber = DB::table('wo_hdr')->where('id',$id)->where('status','1')->value('wo_code');
        $rowAffected = DB::table('wo_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('wo_det')->where('wo_code',$woNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $woNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $woNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $woOrigin=DB::table('wo_hdr')->where('id',$id)->value('wo_code');
        $numRevision = $request->nR ? $request->nR +1 : 1 ;
        $woNew = $woOrigin.'-R'.$numRevision;
        $checkNewPo=DB::table('wo_hdr')->where('wo_code',$woNew)->count();

        if ($checkNewPo > 0){
            $woNew = $woOrigin.'-R'.$numRevision;
        } 
                
        $sqlHdr = "INSERT into wo_hdr 
        (
            wo_code,
            original_wo_code,
            wo_date,
            wo_shift,
            wo_group,
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
            '$woNew',
            '$woOrigin',
            wo_date,
            wo_shift,
            wo_group,
            start_time,
            working_hour,
            efficiency,
            $numRevision,
            '7',
            note,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."'
        from wo_hdr where wo_code = '$woOrigin'";

        $sqlDet="INSERT into wo_det
        (
            wo_code,
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
            created_by,
            updated_by,
            created_at,
            updated_at
        )
        select '$woNew',
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
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."' 
        from wo_det where wo_code = '$woOrigin'";

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

            DB::table('wo_hdr')
            ->where('wo_code',$woOrigin)
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
            ->where('module_number',$woOrigin)
            ->update(
                [
                    'module_number' => $woNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision PO: $woOrigin to $woNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->route('workingOrderSheet.edit', ['id'=>Crypt::encryptString($id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision PO: $woOrigin to $woNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {   
        $username = Auth::user()->username;
        $searchWos = strtolower($request->searchWos);
        $searchStatus = $request->searchStatus;
        $wosDate = $request->wosDate;        
        $fromDate ="";
        $toDate = "";
        
        if ($wosDate){
            $date = explode("to",$wosDate);
            if(count($date)>1){
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("-", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("-", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }

            // $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            // $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }
        
        $data = DB::table('wo_hdr')
        ->where(function ($query) use ($searchWos,$searchStatus,$wosDate,$fromDate,$toDate) {
            $searchWos ? $query->where('wo_code','ilike','%'.$searchWos.'%') : '';
            $searchStatus ? $query->where('wo_hdr.status','=',$searchStatus) : '';
            $wosDate ? $query->whereBetween(DB::raw("wo_date"), [$fromDate, $toDate]) : '';
        })
        ->where('status','<>','7')
        ->orderBy('wo_code')
        ->get(); 



        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            if ( $data->status == '1' or $data->status == '2') {
                if (Auth::user()->can('workingOrder-approve')) {
                $buttons .=         '<a href="'. route('workingOrderSheet.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                }
            }

            if ( $data->status == '1' or $data->status == '2' ){
                if (Auth::user()->can('workingOrder-edit')) {
                $buttons .=         '<a href="'. route('workingOrderSheet.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                }
            }
            
            if (($data->status == '2') || ($data->status == '3') ){
                if (Auth::user()->can('workingOrder-revision')) {
                    $buttons .=         '<a href="'. route('workingOrderSheet.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) .'" class="dropdown-item">
                                            <i data-feather="copy"></i>
                                            <span>'. __("Revision") .'</span>
                                        </a>';
                }
            }

            $buttons .=         '<a href="'. route('workingOrderSheet.print',['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            
            // $buttons .=         '<a href="'. route('workingOrderSheet.show',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
            //                         <i data-feather="list"></i>
            //                         Detail
            //                     </a>';
                
            if ( $data->status == '1' ){
                if (Auth::user()->can('workingOrder-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        data-url='". route('workingOrderSheet.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
                }
            }
           
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('wo_code', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            // $statusTr = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED','CLOSED','REVISED',];
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED'];
            return '<span style="display: none;">'.$data->wo_code.'</span><a class="text-left badge d-block '.$badges[$data->status - 1].'" name="'.$data->wo_code.'" href="'. route('workingOrderSheet.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->wo_code.'</span></a>';
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger']; 
            $status = ['NEW','VALIDATED','APPROVED','PROCESS','CANCELED','','REVISION'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','wo_code'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $username = Auth::user()->username;
        $searchWos = strtolower($request->searchWos);
        $searchStatus = $request->searchStatus;
        $wosDate = $request->wosdate;        
        $fromDate ="";
        $toDate = "";
        if ($wosDate){
            $date = explode("to",$wosDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }
        
        $data = DB::table('wo_det')
        ->leftJoin('wo_hdr','wo_hdr.wo_code','wo_det.wo_code')
        ->leftJoin('article as fg','fg.article_code','wo_det.article_code')
        ->leftJoin('article as rm','rm.article_code','wo_det.article_code')
        ->where(function ($query) use ($searchWos,$searchStatus,$wosDate,$fromDate,$toDate) {
            $searchWos ? $query->where('wo_code','ilike','%'.$searchWos.'%') : '';
            $searchStatus ? $query->where('wo_hdr.status','=','%'.$searchStatus.'%') : '';
            $wosDate ? $query->whereBetween(DB::raw("to_date(wo_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->where('wo_hdr.status','<>','7')
        ->select('wo_det.*','wo_hdr.*'
        ,'fg.article_alternative_code as article_fg'
        ,'fg.article_desc as article_fg_desc'
        ,'rm.article_alternative_code as article_rm'
        ,'rm.article_desc as article_rm_desc'
        ,'wo_hdr.note as note_hdr'
        )
        ->orderBy('urutan')
        ->orderBy('wo_det.wo_code')
        ->get(); 
                        
        return Datatables::of($data)
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['header']=DB::table('wo_hdr')
        ->where('id',$id)
        ->select('wo_hdr.*'
        ,DB::raw("(SELECT sum(plan_tag) as total_tag from wo_det where wo_code = wo_hdr.wo_code) as total_tag")
        )
        ->first();

        $woNumber=$data['header'] -> wo_code;
       
        $data['details']=DB::table('wo_det')
        ->leftJoin('article','article.article_code','wo_det.article_code')
        ->select('wo_det.*'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,DB::raw("(SELECT article_qty from article_stock where article_code = wo_det.article_rm_code and site_code ='HO' and location_number='WH') as qty_rm")
        )
        ->where('wo_code',$woNumber)
        ->get();

        // $data['totals']=DB::select("SELECT sum(plan_tag) as total_tag from wo_det where wo_code = '$woNumber'");

        $data['woNumber'] = $woNumber;
        $data['no'] = 0;

        $data['title'] = $woNumber;


        view()->share($data);

        $pdf = PDF::loadView('workingOrderSheet.print');
        return $pdf->stream("$woNumber.pdf");

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $woNumber = $request->wosNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$woNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $status = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('wo_hdr')
                ->where('wo_code',$woNumber)
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
                        'module_number' => $woNumber,
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
                $message  = "$title $woNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusWo' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $woNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusWo' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));
        }
    }
}
