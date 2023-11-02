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

class TargetSoController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Target SO";
        $this->moduleCode = "TSO";
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'tso_code','name'=>'tso_code','title'=>'TSO Code'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'tso_name','name'=>'tso_name','title'=>'Name'],
            ['data'=>'tso_date','name'=>'tso_date','title'=>'Date'],
            ['data'=>'customer','name'=>'customer','title'=>'Customer'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'tso_code','name'=>'tso_code','title'=>'TSO Code'],
            ['data'=>'tso_name','name'=>'tso_name','title'=>'Name'],
            ['data'=>'tso_date','name'=>'tso_date','title'=>'Date'],
            ['data'=>'customer','name'=>'customer','title'=>'Customer'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty_target','name'=>'qty_target','title'=>'Qty Target'],
            ['data'=>'qty_forcast','name'=>'qty_forcast','title'=>'Qty Forcast'],
            ['data'=>'qty_actual','name'=>'qty_actual','title'=>'Qty Actual'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            // ['data'=>'approval_by','name'=>'approval_by','title'=>'Approved By'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['customer'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'CANCELED'];

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
            
        return view("targetSo.index",$data);
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
        $poNumber="$key/$year/$month/$newCode";
        
        return $poNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        
        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['oEdit']=false;

        return view("targetSo.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $tsoDate = $request->tsoDate;
        $tsoName = $request->tsoName;
        $customer = 'none';
        $note = $request->note;
        $status = '1';
        $poLeadCode = $this->moduleCode; 

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'CANCELED'];

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
            'tsoName'  => 'required',
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
            $tsoCode = $this->getLastCode($poLeadCode);
            DB::beginTransaction();
            try {
                    DB::table('target_order_hdr')->insert([
                        'tso_code' => $tsoCode,
                        'origin_tso_code'=>$tsoCode,
                        'tso_name' => $tsoName ,
                        'tso_date' => $tsoDate,
                        'customer_id' => $customer,
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
                            'tso_code' => $tsoCode,
                            'article_code' => $val->article_code,
                            'qty_target' => $val->qtyTarget,
                            'qty_forcast' => $val->qtyForcast,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('target_order_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $tsoCode is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tsoCode'=>$tsoCode,'oEdit'=>true));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $tsoCode is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tsoCode'=>$tsoCode));

            }
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('target_order_hdr')
        ->leftJoin('third_party','third_party.kode','target_order_hdr.customer_id')
        ->select('target_order_hdr.*'
        ,DB::raw("concat(third_party.kode,'-',third_party.nama) as customer")
        ,DB::raw('(select count(*) from target_order_det where tso_code = target_order_hdr.tso_code) as sum_row'))
        ->where('origin_tso_code', function($query) use ($id){
            $query->select('tso_code')->from('target_order_hdr')->where('id',$id);
        })
        ->orderBy('id')
        ->get();

        $tsoCode = $data['headers'][0]->origin_tso_code;
        $customer = $data['headers'][0]->customer_id;
                
        $data['details'] = DB::table('target_order_det')
        ->whereIn('target_order_det.tso_code', function($query) use ($tsoCode){
            $query->select('tso_code')->from('target_order_hdr')->where('origin_tso_code',$tsoCode);
        })
        ->leftJoin('article','article.article_code','=','target_order_det.article_code')
        ->leftJoin('uom','uom.code','target_order_det.uom')
        // ->where('target_order_det.tso_code',$tsoCode)
        ->select('target_order_det'.'.*'
        ,'uom.uom_group as uom_group'
        ,DB::raw("concat(article.article_alternative_code,'-',article.article_desc) as article")
        ,DB::raw("(select STRING_AGG( (qty_target::real)::text,' -> ' ORDER BY tso_code) AS main from target_order_det p where article_code = target_order_det.article_code and tso_code like '$tsoCode%' ) as notes"))
        ->orderBy('id')
        ->get();
            
        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$tsoCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$tsoCode,$username);
                   
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED','','7'=>'REVISED'];
        $statusTso = ['NEW','VALIDATED','APPROVED','','CANCELED','','REVISED'];
        $data['statusTso'] = $statusTso[$data['headers'][0]->status-1];
        
        return view("targetSo.show",$data);        
    }

    public function detail(Request $request)
    {
        $poNumber=$request->poNumber;
        $detail = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->leftJoin('purchase_request_det', function($join) {
            $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
            ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        })
        ->leftJoin('uom','uom.code','=','purchase_order_det.uom')
        ->where('purchase_order_det.po_number',$poNumber)
        ->select('purchase_order_det'.'.*'
            ,'purchase_order_det.pr_number'
            ,'article_stock.article_qty as qty_stock'
            ,'uom.uom_group'
            , DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();

        return response()->json(array('status' => 0, 'data' => $detail));

    }

    public function showEdit($key)
    {
        $id=Crypt::decryptString($key);
        $username =  Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('target_order_hdr')
        ->leftJoin('third_party','third_party.kode','target_order_hdr.customer_id')
        ->select('target_order_hdr.*',DB::raw("concat(third_party.kode,'-',third_party.nama) as customer"))
        ->where('target_order_hdr.id',$id)
        ->get()->first();

        $tsoCode = $data['header']->tso_code;
        $customer = $data['header']->customer_id;

        // $data['articles'] = DB::table('article')
        //     ->leftJoin('uom','uom.code','=','article.uom')
        //     // ->where('third_party',$customer)
        //     ->whereIn('article_type',['FG'])
        //     ->orderBy('article_desc')
        //     ->get();
                
        $data['details'] = DB::table('target_order_det')
        ->leftJoin('article','article.article_code','=','target_order_det.article_code')
        ->where('target_order_det.tso_code',$tsoCode)
        ->select('target_order_det'.'.*')
        ->orderBy('id')
        // ->limit(10)
        ->get();

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$tsoCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$tsoCode,$username);
                   
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED'];
        $statusTso = ['NEW','VALIDATED','APPROVED','','CANCELED'];
        $data['statusTso'] = $statusTso[$data['header']->status-1];

        return view("targetSo.edit",$data);
    }

    public function edit(Request $request)
    {
        return $this->showEdit($request->id);
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $tsoCode = $request->tsoCode;
        $tsoDate = $request->tsoDate;
        $tsoName = $request->tsoName;
        $customer = $request->customer;
        $note = $request->note;
        $status = '1';
              
        // $statusSimpan = $request->statusSimpan;
        // if ( $statusSimpan == 'approve' ){
        //     $maxLevel = $request->maxLevel;
        //     $approveLevel  = $request->approveLevel;
        //     $status = $approveLevel === $maxLevel ? '3' : '2';
        // }else{
        //     $status = '1';
        // }       

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
        
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
            'tsoDate'  => 'required',
            'tsoName'  => 'required',
            'customer'  => 'required',

        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }

            $alert ="warning";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));

        }else{
            DB::beginTransaction();
            try {
                    $row_affected=DB::table('target_order_hdr')
                    ->where('tso_code',$tsoCode)
                    ->update(
                        [
                            'tso_code' => $tsoCode,
                            'tso_name' => $tsoName ,
                            'status' => $status,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $tsoCode.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $tsoCode dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('target_order_det')
                        ->whereNotIn(DB::raw("CONCAT(tso_code,article_code)"),$dataSet)
                        ->where('tso_code',$tsoCode)
                        ->delete();
                                  
                    foreach ($articles as $val) {
                        DB::table('target_order_det')
                        ->updateOrInsert(
                            ['tso_code' => $tsoCode,'article_code' => $val->article_code],
                            [
                            'tso_code' => $tsoCode,
                            'article_code' => $val->article_code,
                            'qty_target' => $val->qtyTarget,
                            'qty_forcast' => $val->qtyForcast,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                    }
                   
                    // if ( $statusSimpan == 'approve' ){
                    //     DB::table('approval_history')->insert([
                    //         'module_code' => $this->moduleCode,
                    //         'module_number' => $tsoCode,
                    //         'username' => Auth::user()->username,
                    //         'approval_order' => $approveLevel,
                    //         'approval_date' => date('Y-m-d'),
                    //         'status' => 1,
                    //         'created_by' => Auth::user()->username,
                    //         'updated_by' => Auth::user()->username,
                    //         'created_at' => date('Y-m-d H:i:s'),
                    //         'updated_at' => date('Y-m-d H:i:s')
                    //     ]);
                    // }
                                            
                    DB::commit();

                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $tsoCode is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tsoCode'=>$tsoCode));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert ="warning";
                $message  = "$title $tsoCode is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prNumber'=>$tsoCode));
            }
        }

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $tsoCode = $request->tsoCode;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$tsoCode,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusTso = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'CANCELED'];
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('target_order_hdr')
                ->where('tso_code',$tsoCode)
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
                        'module_number' => $tsoCode,
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

                if ($statusTso == '3'){
                    
                    $tsoHdr=DB::table('target_order_hdr')->where('tso_code',$tsoCode)->first();
                    $tsoOrigin = $tsoHdr->tso_code;
                    $prNumber = $tsoHdr->pr_number;
                    $reasonPr = $tsoHdr->note;

                    /*tidak jadi dari TSO untuk revisi dari PR*/
                    // if ( $prNumber ){
                    //     $tsoCode = $this->revisionPrFromTso($tsoOrigin,$reasonPr);
                    // }
                }
                
                DB::commit();
                $title ="Approve $this->title";
                $alert  ="success";
                $message  = "$title $tsoCode is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tsoCode'=>$tsoCode));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $tsoCode is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'tsoCode'=>$tsoCode));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        // $tsoCode = DB::table('target_order_hdr')->where('id',$id)->where('status','1')->first();
        $tsoQuery = DB::table('target_order_hdr')->where('id',$id)->first();
        $tsoCode = $tsoQuery->tso_code;
        $tsoStatus = $tsoQuery->status;
        if ($tsoStatus != 3 ){
            // $rowAffected = DB::table('target_order_hdr')->where('id',$id)->where('status','1')->delete();
            $rowAffected = DB::table('target_order_hdr')->where('id',$id)->delete();
            DB::table('target_order_det')->where('tso_code',$tsoCode)->delete();
        }else{
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','','5'=>'CANCELED'];
            $rowAffected = DB::table('target_order_hdr')
            ->where('tso_code',$tsoCode)
            ->update(
                [
                    'status' => '5',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }

        if($rowAffected>0){
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $tsoCode Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $tsoCode Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        $username = Auth::user()->username;
        $searchTso = strtolower($request->searchTso);
        $searchCustomer = $request->searchCustomer;
        $searchStatus = $request->searchStatus;
        $tsoDate = $request->tsoDate;
        $fromDate ="";
        $toDate = "";
        if ($tsoDate){
            $date = explode("to",$tsoDate);
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

        $data = DB::table('target_order_hdr')
        ->leftJoin('third_party','third_party.kode','target_order_hdr.customer_id')
        ->where(function ($query) use ($searchTso,$searchStatus,$tsoDate,$fromDate,$toDate,$searchCustomer) {
            $searchCustomer ? $query->where('customer_id',$searchCustomer) : '';
            $searchTso ? $query->where('tso_code','ilike','%'.$searchTso.'%') : '';
            $searchStatus ? $query->where('target_order_hdr.status',$searchStatus) : '';
            $tsoDate ? $query->whereBetween(DB::raw("to_date(tso_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->where('status','!=','7')
        ->select('target_order_hdr.*'
        ,'third_party.nama as customer'
        )
        ->orderBy('target_order_hdr.id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            if ( $data->status == '2' or $data->status == '1') {
                if (Auth::user()->can('targetSo-authorize')) {
                $buttons .=         '<a href="'. route('targetSo.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                }
            }

            if ( $data->status == '1' or $data->status == '2' ){
                if (Auth::user()->can('targetSo-edit')) {
                $buttons .=         '<a href="'. route('targetSo.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            }

            if (($data->status == '2') || ($data->status == '3') ){
                if (Auth::user()->can('targetSo-revision')) {
                    $buttons .=     "<a href='javascript:;'
                                        id='revisionReasonButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#reasonModalRevision'
                                        data-href='". route("targetSo.revision", ["id"=>Crypt::encryptString($data->id),"nR"=>$data->num_revision]) ."'>
                                        <i data-feather='corner-down-left' class='feather-14-red'></i>
                                        <span>". __('Revision') ."</span>
                                    </a>";
                }
            }

            $buttons .=         '<a href="'. route('targetSo.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    <span>'. __("Detail") .'</span>
                                </a>';
            
            $buttons .=         '<a href="'. route('targetSo.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    <span>'. __("Print") .'</span>
                                </a>';
            
            if ( $data->status <> '3' ){
                if (Auth::user()->can('purchaseOrder-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        id='deleteButton'
                                        data-url='". route('targetSo.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
                }
            }

            if ( $data->status == '3' ){
                if (Auth::user()->can('purchaseOrder-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        id='deleteButton'
                                        data-url='". route('targetSo.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Cancel') ."</span>
                                    </a>";
                }
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('tso_code', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            // $statusTso = ['NEW','VALIDATED','APPROVED','','CANCELED'];
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','','5'=>'CANCELED',,'7'=>'REVISED'];
            return '<span style="display: none;">'.$data->tso_code.'</span><a class="text-left badge d-block '.$badges[$data->status - 1].'" name="'.$data->tso_code.'" href="'. route('targetSo.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->tso_code.'</span></a>';
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTso = ['NEW','VALIDATED','APPROVED','','CANCELED','','REVISED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTso[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','tso_code'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchTso = strtolower($request->searchTso);
        $username = Auth::user()->username;
        $searchCustomer = $request->searchCustomer;
        $searchStatus = $request->searchStatus;
        $tsoDate = $request->tsoDate;
        $fromDate ="";
        $toDate = "";
        
        if ($tsoDate){
            $date = explode("to",$tsoDate);
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

        $data = DB::table('target_order_det')
        ->leftJoin('target_order_hdr','target_order_hdr.tso_code','target_order_det.tso_code')
        ->leftJoin('article','article.article_code','target_order_det.article_code')
        ->leftJoin('third_party','third_party.kode','article.third_party')
        ->leftJoin('uom','uom.code','target_order_det.uom')
        ->where(function ($query) use ($searchTso,$searchStatus,$tsoDate,$fromDate,$toDate,$searchCustomer) {
            $searchCustomer ? $query->where('customer_id',$searchCustomer) : '';
            $searchTso ? $query->where('target_order_det.tso_code','ilike','%'.$searchTso.'%') : '';
            $searchStatus ? $query->where('target_order_hdr.status',$searchStatus) : '';
            $tsoDate ? $query->whereBetween(DB::raw("to_date(tso_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('target_order_det.*'
        ,'target_order_hdr.*'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'third_party.nama as customer'
        ,'uom_group'
        ,'qty_target'
        ,'qty_forcast'
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_target,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_target,'999,999,999.999') end as qty_target")
        //,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_forcast,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_forcast,'999,999,999.999') end as qty_forcast")
        )
        ->orderBy('target_order_det.id')
        ->get(); 
       
        return Datatables::of($data)
        // ->addColumn('status', function ($data) {
        //     $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
        //     $statusTso = ['NEW','VALIDATED','APPROVED'];
        //     return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTso[$data->status - 1]."</div>";
        // })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $tsoHdr=DB::table('target_order_hdr')->where('id',$id)->first();
        $tsoOrigin = $tsoHdr->tso_code;
        // $prNumber = $tsoHdr->prNumber;
        $numRevision = $request->nR ? $request->nR +1 : 1 ;
        $tsoNew = $tsoOrigin.'-R'.$numRevision;
        $checkNewTso=DB::table('target_order_hdr')->where('tso_code',$tsoNew)->count();

        $reason = "(Revision by $username)";

        // $reason = "(Revision by $username, Reason: $request->reason)";
        // $reasonPr = $request->reason;

        if ($checkNewTso > 0){
            $tsoNew = $tsoOrigin.'-R'.$numRevision+1;
        } 

        /*
            pada saat revisi kalo sudah jadi PR, PR nya juga di revisi
            Kalau  PR nya sudah jadi PO, PO nya juga di Revisi
        */
                
        $sqlHdr = "INSERT into target_order_hdr 
        (
            tso_code,
            origin_tso_code,
            po_number,
            tso_name,
            tso_date,
            customer_id,
            status,
            note,
            num_revision,
            revised_by,
            revised_at,
            created_by,
            updated_by,
            created_at,
            updated_at,
            pr_number,
            reason
        )

        select 
            '$tsoNew',
            '$tsoOrigin',
            po_number,
            tso_name,
            tso_date,
            customer_id,
            '7',
            note,
            $numRevision,
            '$username',
            '".date('Y-m-d H:i:s')."',
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."',
            pr_number,
            '$reason'
        from target_order_hdr where tso_code = '$tsoOrigin'";

        $sqlDet="INSERT into target_order_det
        (
            tso_code,
            po_number,
            article_code,
            qty_target,
            qty_forcast,
            qty_actual,
            uom,
            created_by,
            updated_by,
            created_at,
            updated_at
        )
        select '$tsoNew',
            po_number,
            article_code,
            qty_target,
            qty_forcast,
            qty_actual,
            uom,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."' 
        from target_order_det where tso_code = '$tsoOrigin'
        order by id";

        $rowAffected =  DB::select($sqlHdr);
        if ($rowAffected){
            DB::select($sqlDet);

            DB::table('target_order_hdr')
            ->where('tso_code',$tsoOrigin)
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
            ->where('module_number',$tsoOrigin)
            ->update(
                [
                    'module_number' => $tsoNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision Tso: $tsoOrigin to $tsoNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->route('targetSo.edit', ['id'=>Crypt::encryptString($id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision Tso: $tsoOrigin to $tsoNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }       
    }
 
    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['companies']=DB::table('company')
        ->where('code','ASN')
        ->select('name as nama'
        ,'address as alamat'
        , DB::RAW('(select region_name from regions where region_code = city::integer)  as kota')
        ,'tlp')
        ->get()->first();
            
        $tSoHdr=DB::table('target_order_hdr')
        ->where('id',$id)
        ->first();

        $tSoNumber=$tSoHdr -> tso_code;
    
        $data['details']=DB::table('target_order_det')
        ->leftJoin('article','article.article_code','target_order_det.article_code')
        ->where('tso_code',$tSoNumber)
        ->orderBy('target_order_det.id')
        ->get();

        $data['totals']=DB::select("
            select tso_code
            ,sum(qty_target) as total_target
            ,sum(qty_forcast) as total_forcast
            from target_order_det
            where tso_code = '$tSoNumber'
            group by tso_code");

        $data['keterangan']=$tSoHdr->note;
        $data['tsoNumber']=$tSoNumber;
        $data['tsoName']=$tSoHdr->tso_name;
        $data['tsoDate']=$tSoHdr->tso_date;
        $data['createdBy']=$tSoHdr->created_by;

        $status = ['NEW','VALIDATED','APPROVED','','CANCELED'];
        $data['status'] = $status[$tSoHdr ->status-1];
        $data['no']=0;

        $data['approved'] = DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_number',$tSoNumber)
        ->orderBy('approval_order','desc')
        ->value('users.name');

        view()->share($data);

        $pdf = PDF::loadView('targetSo.print');
        return $pdf->stream("TSO_$tSoNumber.pdf");

    }

    public function listItemByCustomer(Request $request)
    {
        $customer = $request->customer;
        $data = DB::table('article')
        ->where('third_party',$customer)
        ->where('article_type','FG')
        ->whereIn('article.article_code', function($query) {
            $query->select('article_code')->from('bom_hdr')->where('status','3');
        })
        ->orderBy('article_alternative_code')
        ->get();

        return response()->json(array('data' => $data));
    }

    public function revisionPrFromTso($tsoCode,$reason){

        $username =  Auth::user()->username;
        $siteCode = 'HO';
        $location = 'WH';
        $reasonRequest = $reason;
        $reason = "(Revision from TSO : $tsoCode, by $username, $reason)";

        $prNumber = DB::table('target_order_hdr')
        ->where('tso_code',$tsoCode)
        ->value('pr_number');

        if ($prNumber){

            $prOrigin = $prNumber;
            $prHdr = DB::table('purchase_request_hdr')
            ->where('pr_number',$prNumber)
            ->first(); 
    
            $stockDate = $prHdr->stock_date;
                            
            $numRevision = $prHdr->num_revision ? $prHdr->num_revision+1 : 1 ;
            $prNew = $prOrigin.'-R'.$numRevision;
            
            $checkNewPr=DB::table('purchase_request_hdr')
            ->where('pr_number',$prNew)->count();
    
            if ($checkNewPr > 0){
                $prNew = $prOrigin.'-R'.($numRevision+1);
            }
        
            //header nya di clone, jadi yang laam pake tanda revisi
            $sqlHdr = "INSERT into purchase_request_hdr 
            (
                pr_number,
                dept,
                date,
                authorized_by,
                prepared_by,
                order_type,
                status,
                note,
                print_seq,
                created_by,
                updated_by,
                created_at,
                updated_at,
                stock_date,
                tso_code,
                origin_pr_number,
                num_revision,
                revised_by,
                revised_at,
                reason
            )
            select 
                '$prNew',
                dept,
                date,
                authorized_by,
                prepared_by,
                order_type,
                '8',
                note,
                print_seq,
                '$username',
                '$username',
                '".date('Y-m-d H:i:s')."',
                '".date('Y-m-d H:i:s')."',
                stock_date,
                tso_code,
                '$prOrigin',
                $numRevision,
                '$username',
                '".date('Y-m-d H:i:s')."',
                '$reasonRequest'
            from purchase_request_hdr where pr_number = '$prOrigin'";

            // CONCAT(note,', $reason'),


            /*Dilengkapi dengan dengan RM*/
            $sqlDet="INSERT into purchase_request_det
            (
                pr_number,
                article_code,
                qty,
                uom,
                supp_code,
                qty_hitung,
                qty_stock,
                created_by,
                created_at
            )
            select 
            '$prNumber' as pr_number
            ,article_code
            ,qty
            ,uom
            ,supp_code
            ,qty_hitung
            ,qty_stock
            ,'$username'
            ,now()
            from 
            (select 
            alternative
            ,'$prNumber' as pr_number
            ,article_code
            ,ceil(((sum(qty_target * qty_bom)-qty_stock)+safety_stock)/min_package) * min_package as qty
            ,mari.uom
            ,(select third_party from article where article_code = mari.article_code) as supp_code
            ,ceil(((sum(qty_target * qty_bom)-qty_stock)+safety_stock)/min_package) * min_package as qty_hitung
            ,qty_stock
            ,'$username'
            ,now()
            from 
            (
            select 
            bom_code
            ,oki.article_bom as article_code
            ,(select article_alternative_code from article where article_code = oki.article_bom) as alternative
            ,(select sum(qty_target) from target_order_det where tso_code = '$tsoCode' and target_order_det.article_code = oki.article_code_fg group by target_order_det.article_code) as qty_target
            ,qty 
            ,article.uom as uom
            ,uom_con
            ,nilai_konversi
            ,qty_hasil_konversi as qty_bom
            ,(select sum(qty_target) from target_order_det where tso_code = '$tsoCode' and target_order_det.article_code = oki.article_code_fg group by target_order_det.article_code) * qty_hasil_konversi  as qty_total_order
            ,coalesce(min_package,1) as min_package
            ,coalesce(safety_stock,0) as safety_stock
            ,get_last_qty(oki.article_bom,'$stockDate','$siteCode','$location') as qty_stock
            from 
            (
            select bom_code
            ,bom_det.article_code as article_bom
            ,(select article_code from bom_hdr where bom_code = bom_det.bom_code) as article_code_fg
            ,qty
            ,bom_det.uom
            ,uom_con
            ,coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = article.uom),1) as nilai_konversi
            ,qty * coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = article.uom),1) as qty_hasil_konversi
            from bom_det 
            left join article on article.article_code = bom_det.article_code
            where bom_code in 
            (
            select bom_code
            from (select article_code,qty_target as qty from target_order_det where tso_code = '$tsoCode') as production_detail_temp
            left join bom_hdr on bom_hdr.article_code=production_detail_temp.article_code
            where bom_hdr.status = '3'
            )
            ) as oki
            left join article on article.article_code = oki.article_bom
            order by alternative
            ) as mari 
            group by mari.article_code,alternative,mari.safety_stock,mari.qty_stock,mari.min_package,mari.uom
            having (ceil(((sum(qty_target * qty_bom)-qty_stock)+safety_stock)/min_package) * min_package) > 0
            union
            select 
            alternative
            ,'$prNumber' as pr_number
            ,article_code
            ,ceil(((sum(qty_target * qty_bom)-qty_stock)+safety_stock)/min_package) * min_package as qty
            ,mari.uom
            ,(select third_party from article where article_code = mari.article_code) as supp_code
            ,ceil(((sum(qty_target * qty_bom)-qty_stock)+safety_stock)/min_package) * min_package as qty_hitung
            ,qty_stock
            ,'$username'
            ,now()
            from 
            (
            select 
            bom_code
            ,oki.article_code_rm as article_code
            ,(select article_alternative_code from article where article_code = oki.article_code_rm) as alternative
            ,(select sum(qty_target) from target_order_det where tso_code = '$tsoCode' and target_order_det.article_code = oki.article_code_fg group by target_order_det.article_code) as qty_target
            ,qty 
            ,article.uom as uom
            ,uom_con
            ,nilai_konversi
            ,qty_hasil_konversi as qty_bom
            ,(select sum(qty_target) from target_order_det where tso_code = '$tsoCode' and target_order_det.article_code = oki.article_code_fg group by target_order_det.article_code) * qty_hasil_konversi  as qty_total_order
            ,coalesce(min_package,1) as min_package
            ,coalesce(safety_stock,0) as safety_stock
            ,get_last_qty(oki.article_code_rm,'$stockDate','$siteCode','$location') as qty_stock
            from 
            (
            select 
            article_alternative_code
            ,bom_code
            ,bom_hdr.article_code_rm as article_code_rm
            ,bom_hdr.article_code as article_code_fg
            ,1 as qty
            ,article.uom as uom
            ,article.uom as uom_con
            ,1 as nilai_konversi
            ,1 as qty_hasil_konversi
            from (select article_code,qty_target as qty from target_order_det where tso_code = '$tsoCode') as production_detail_temp
            left join bom_hdr on bom_hdr.article_code=production_detail_temp.article_code
            left join article on article.article_code = bom_hdr.article_code_rm
            where bom_hdr.status = '3'
            and article_alternative_code is not null
            and article.article_type = 'RMP'
            order by article_alternative_code
            ) as oki
            left join article on article.article_code = oki.article_code_rm
            order by alternative
            ) as mari 
            group by mari.article_code,alternative,mari.safety_stock,mari.qty_stock,mari.min_package,mari.uom
            having (ceil(((sum(qty_target * qty_bom)-qty_stock)+safety_stock)/min_package) * min_package) > 0) ijo
            order by alternative";


            /*tanpa RM
            $sqlDet="INSERT into purchase_request_det
            (
                pr_number,
                article_code,
                qty,
                uom,
                supp_code,
                qty_hitung,
                qty_stock,
                created_by,
                created_at
            )
            select 
            '$prNumber' as pr_number
            ,article_code
            ,ceil(((sum(qty_target * qty_bom)-qty_stock)+safety_stock)/min_package) * min_package as qty
            ,mari.uom
            ,(select third_party from article where article_code = mari.article_code) as supp_code
            ,ceil(((sum(qty_target * qty_bom)-qty_stock)+safety_stock)/min_package) * min_package as qty_hitung
            ,qty_stock
            ,'$username'
            ,now()
            from 
            (
                select 
                bom_code
                ,oki.article_bom as article_code
                ,(select article_alternative_code from article where article_code = oki.article_bom) as alternative
                ,(select qty_target from target_order_det where tso_code = '$tsoCode' and target_order_det.article_code = oki.article_code_fg ) as qty_target
                ,qty 
                --,oki.uom
                ,article.uom as uom
                ,uom_con
                ,nilai_konversi
                ,qty_hasil_konversi as qty_bom
                ,(select qty_target from target_order_det where tso_code = '$tsoCode' and target_order_det.article_code = oki.article_code_fg ) * qty_hasil_konversi  as qty_total_order
                ,coalesce(min_package,1) as min_package
                ,coalesce(safety_stock,0) as safety_stock
                ,get_last_qty(oki.article_bom,'$stockDate','$siteCode','$location') as qty_stock
                from 
                (
                    select bom_code
                    ,bom_det.article_code as article_bom
                    ,(select article_code from bom_hdr where bom_code = bom_det.bom_code) as article_code_fg
                    ,qty
                    ,uom
                    ,uom_con
                    ,coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = bom_det.uom),1) as nilai_konversi
                    ,qty * coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = bom_det.uom),1) as qty_hasil_konversi
                    from bom_det where bom_code in 
                    (
                        select bom_code
                        from (select article_code,qty_target as qty from target_order_det where tso_code = '$tsoCode') as production_detail_temp
                        left join bom_hdr on bom_hdr.article_code=production_detail_temp.article_code
                        where bom_hdr.status = '3'
                    )
                ) as oki
                left join article on article.article_code = oki.article_bom
                order by alternative
            ) as mari 
            group by mari.article_code,alternative,mari.safety_stock,mari.qty_stock,mari.min_package,mari.uom
            having (ceil(((sum(qty_target * qty_bom)-qty_stock)+safety_stock)/min_package) * min_package) > 0
            order by alternative";
            */
    
            $rowAffected =  DB::select($sqlHdr);
            
            if ($rowAffected){
                //update PR lama diisi dengan nomor yang baru
                $oki = DB::table('purchase_request_det')
                ->where('pr_number',$prOrigin)
                ->update([
                    'pr_number' => $prNew
                ]);                

                // if ($oki){
                // Update isi dari PR detail dengan data yang baru hitung ulang
                    DB::select($sqlDet);
                // }
        
                //update PR isi jumlah revisi nya
                DB::table('purchase_request_hdr')
                ->where('pr_number',$prOrigin)
                ->update(
                    [
                        'num_revision' => $numRevision,
                        'status' => '1',
                        // 'note'=> DB::raw("CONCAT(note,', $reason')"),
                        'revised_by'=>Auth::user()->username,
                        'revised_at'=> date('Y-m-d H:i:s'),
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
    
                //update history approve PR yang lama  jadi tidak aktif
                DB::table('approval_history')
                ->where('module_number',$prOrigin)
                ->update(
                    [
                        'module_number' => $prNew,
                        'status' => '0',
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                //cari apakah PR sudah jadi PO
                $listPO = DB::table("purchase_request_det")
                ->where("pr_number",$prOrigin)
                ->where("po_number","<>",null)
                ->distinct('po_number')
                ->get();

                if(count($listPO)> 1){
                    foreach($listPO as $val){
                        $this->revisionPoFromPr($val->po_number,$prOrigin,$reason);
                    }
                }
                  
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$title Revision PR from TSO: $prOrigin to $prNew TSO:$tsoCode is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return 'success';
            }else{
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title Revision PR from TSO: $prOrigin to $prNew TSO:$tsoCode is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return 'failed';
            }       
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision PR from TSO, PR:$prNumber not found";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return 'failed';
        }

    }

    public function revisionPoFromPr($poNumber,$prNumber,$reason){
        $username =  Auth::user()->username;
        $poOrigin = $poNumber;
        $reasonRequest = $reason;
        $reason = "(Revision from PR : $prNumber, by $username, $reason)";

        $poHdr = DB::table('purchase_order_hdr')
        ->where('po_number',$poOrigin)
        ->get(); 

        $prNumber = DB::table('purchase_request_det')
        ->where('po_number',$poOrigin)
        ->first();

        $prNumber = $prNumber->pr_number;

        $checkNewPo=DB::table('purchase_order_hdr')->where('po_number',$poNew)->count();
        $numRevision = $poHdr->num_revision ? $poHdr->num_revision+1 : 1 ;
        $poNew = $poOrigin.'-R'.$numRevision;
        $checkNewPo=DB::table('purchase_order_hdr')->where('po_number',$poNew)->count();

        if ($checkNewPo > 0){
            $poNew = $poOrigin.'-R'.$numRevision+1;
        } 
                
        $sqlHdr = "INSERT into purchase_order_hdr 
        (
            po_number,
            origin_po_number,
            supplier_id,
            po_date,
            delivery_date,
            currency,
            authorized_by,
            authorized_at,
            validate_by,
            discount,
            kurs,
            pkp,
            ppn,
            pph22,
            termin,
            order_type,
            status,
            num_revision,
            revised_by,
            revised_at,
            note,
            created_by,
            updated_by,
            created_at,
            updated_at,
            reason
        )
        select 
            '$poNew',
            '$poOrigin',
            supplier_id,
            po_date,
            delivery_date,
            currency,
            authorized_by,
            authorized_at,
            validate_by,
            discount,
            kurs,
            pkp,
            ppn,
            pph22,
            termin,
            order_type,
            '7',
            $numRevision,
            '$username',
            '".date('Y-m-d H:i:s')."',
            note,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."',
            '$reasonRequest'
        from purchase_order_hdr where po_number = '$poOrigin'";

        // CONCAT(note,', $reason'),

        $sqlDet="INSERT into purchase_order_det
        (
            po_number,
            pr_number,
            article_code,
            qty,
            uom,
            old_price,
            price,
            ppn,
            pph22,
            created_by,
            updated_by,
            created_at,
            updated_at
        )
        select '$poNew',
            pr_number,
            article_code,
            qty,
            uom,
            old_price,
            price,
            ppn,
            pph22,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."' 
        from purchase_order_det where po_number = '$poOrigin'";

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

            DB::table('purchase_order_hdr')
            ->where('po_number',$poOrigin)
            ->update(
                [
                    'num_revision' => $numRevision,
                    'status' => '1',
                    // 'note'=> DB::raw("CONCAT(note,', $reason')"),
                    'revised_by'=>Auth::user()->username,
                    'revised_at'=> date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            //update qty sesuai dengan yang sudah di update di PR
            DB::table('purchase_order_det')
            ->where('po_number',$poOrigin)
            ->update(
                [
                    'qty' => DB::RAW("purchase_request_det.pr_number = purchase_order_det.pr_number and 
                    purchase_request_det.article_code = purchase_order_det.article_code
                    and purchase_request_det.po_number = purchase_order_det.po_number"),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            DB::table('approval_history')
            ->where('module_number',$poOrigin)
            ->update(
                [
                    'module_number' => $poNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision PO From PR PO: $poOrigin to $poNew PR:$prNumber is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return "success";
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision PO From PR  PO: $poOrigin to $poNew PR:$prNumber is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return "failed";
        }
        
    }
}
