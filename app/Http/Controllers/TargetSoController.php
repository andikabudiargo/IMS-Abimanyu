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
        ->get();

        $tsoCode = $data['headers'][0]->tso_code;
        $customer = $data['headers'][0]->customer_id;
                
        $data['details'] = DB::table('target_order_det')
        ->whereIn('target_order_det.tso_code', function($query) use ($tsoCode){
            $query->select('tso_code')->from('target_order_hdr')->where('origin_tso_code',$tsoCode);
        })
        ->leftJoin('article','article.article_code','=','target_order_det.article_code')
        ->leftJoin('uom','uom.code','target_order_det.uom')
        ->where('target_order_det.tso_code',$tsoCode)
        ->select('target_order_det'.'.*'
        ,'uom.uom_group as uom_group'
        ,DB::raw("concat(article.article_alternative_code,'-',article.article_desc) as article"))
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$tsoCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$tsoCode,$username);
                   
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED'];
        $statusTso = ['NEW','VALIDATED','APPROVED','','CANCELED'];
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

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$tsoCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$tsoCode,$username);
                   
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED'];
        $statusTso = ['NEW','VALIDATED','APPROVED','','CANCELED'];
        $data['statusPo'] = $statusTso[$data['header']->status-1];

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
        $tsoCode = DB::table('target_order_hdr')->where('id',$id)->where('status','1')->first();
        $tsoCode = $tsoCode->tso_code;
        $tsoStatus = $tsoCode->status;
        if ($tsoStatus == 1){
            $rowAffected = DB::table('target_order_hdr')->where('id',$id)->where('status','1')->delete();
        }else{
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','','5'=>'CANCELED'];
            $row_affected=DB::table('target_order_hdr')
            ->where('tso_code',$tsoCode)
            ->update(
                [
                    'status' => '3',
                    'authorized_by' => Auth::user()->username,
                    'authorized_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if($rowAffected>0){
            DB::table('target_order_det')->where('po_number',$tsoCode)->delete();
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
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('target_order_hdr')
        ->leftJoin('third_party','third_party.kode','target_order_hdr.customer_id')
        ->where(function ($query) use ($searchTso,$searchStatus,$tsoDate,$fromDate,$toDate,$searchCustomer) {
            $searchCustomer ? $query->where('customer_id',$searchCustomer) : '';
            $searchTso ? $query->where('tso_code','ilike','%'.$searchTso.'%') : '';
            $searchStatus ? $query->where('target_order_hdr.status',$searchStatus) : '';
            $tsoDate ? $query->whereBetween(DB::raw("to_date(tso_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
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
                if (Auth::user()->can('purchaseOrder-authorize')) {
                $buttons .=         '<a href="'. route('targetSo.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                }
            }

            if ( $data->status == '1' or $data->status == '2' ){
                if (Auth::user()->can('purchaseOrder-edit')) {
                $buttons .=         '<a href="'. route('targetSo.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            }

            // if (($data->status == '2') || ($data->status == '3') ){
            //     if (Auth::user()->can('purchaseOrder-revision')) {
            //         $buttons .=         '<a href="'. route('targetSo.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) .'" class="dropdown-item">
            //                                 <i data-feather="copy"></i>
            //                                 <span>'. __("Revision") .'</span>
            //                             </a>';
            //     }
            // }
            
            $buttons .=         '<a href="'. route('targetSo.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    <span>'. __("Print") .'</span>
                                </a>';
            
            $buttons .=         '<a href="'. route('targetSo.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    <span>'. __("Detail") .'</span>
                                </a>';

            // if ( $data->status == '1' or $data->status == '2' or $data->status == '3' ){
            //     if (Auth::user()->can('purchaseOrder-delete')) {
            //         $buttons .="<a href='javascript:;'
            //         class='dropdown-item' 
            //         data-size='sm'
            //         data-ajax-delete='true'
            //         data-confirm='Are You Sure want to Close?|This action can not be undone. Do you want to continue?' 
            //         data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
            //         data-modal-id='".$data->id."'
            //         id='deleteButton'
            //         data-url='". route('targetSo.clear', ['id'=>Crypt::encryptString($data->id)]) ."'>
            //         <i data-feather='x' class='feather-14-red'></i>
            //         <span>". __('Close') ."</span>
            //         </a>";
            //     }
            // }

            if ( $data->status == '1' ){
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

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('tso_code', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTso = ['NEW','VALIDATED','APPROVED','','CANCELED'];
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','','5'=>'CANCELED'];
            return '<span style="display: none;">'.$data->tso_code.'</span><a class="text-left badge d-block '.$badges[$data->status - 1].'" name="'.$data->tso_code.'" href="'. route('targetSo.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->tso_code.'</span></a>';
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTso = ['NEW','VALIDATED','APPROVED','','CANCELED'];
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
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('target_order_det')
        ->leftJoin('target_order_hdr','target_order_hdr.tso_code','target_order_det.tso_code')
        ->leftJoin('article','article.article_code','target_order_det.article_code')
        ->leftJoin('third_party','third_party.kode','article.third_party')
        ->leftJoin('uom','uom.code','target_order_det.uom')
        ->where(function ($query) use ($searchTso,$searchStatus,$tsoDate,$fromDate,$toDate,$searchCustomer) {
            $searchCustomer ? $query->where('customer_id',$searchCustomer) : '';
            $searchTso ? $query->where('tso_code','ilike','%'.$searchTso.'%') : '';
            $searchStatus ? $query->where('target_order_hdr.status',$searchStatus) : '';
            $tsoDate ? $query->whereBetween(DB::raw("to_date(tso_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('target_order_det.*'
        ,'target_order_hdr.*'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'third_party.nama as customer'
        ,'uom_group'
        ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_target,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_target,'999,999,999.999') end as qty_target")
        ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_forcast,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_forcast,'999,999,999.999') end as qty_forcast")
        )
        ->orderBy('target_order_det.id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTso = ['NEW','VALIDATED','APPROVED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTso[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $poOrigin=DB::table('purchase_order_hdr')->where('id',$id)->value('po_number');
        $numRevision = $request->nR ? $request->nR +1 : 1 ;
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
            updated_at
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
            '".date('Y-m-d H:i:s')."'
        from purchase_order_hdr where po_number = '$poOrigin'";

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
                    'revised_by'=>Auth::user()->username,
                    'revised_at'=> date('Y-m-d H:i:s'),
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
            $message  = "$title Revison PO: $poOrigin to $poNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            // return $this->showEdit(Crypt::encryptString($id));
            return redirect()->route('targetSo.edit', ['id'=>Crypt::encryptString($data->id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revison PO: $poOrigin to $poNew is failed to save";
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

}
