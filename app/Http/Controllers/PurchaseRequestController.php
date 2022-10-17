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

class PurchaseRequestController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Purchase Request";
        $this->moduleCode = "PR";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'pr_number','name'=>'pr_number','title'=>'PR Number'],
            ['data'=>'order_type','name'=>'order_type','title'=>'Order Type'],
            ['data'=>'dept','name'=>'dept','title'=>'Department'],
            ['data'=>'date','name'=>'date','title'=>'PR Date'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
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
            ['data'=>'pr_number','name'=>'pr_number','title'=>'PR Number'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Description'],
            ['data'=>'qtyku','name'=>'qtyku','title'=>'Qty'],
            ['data'=>'qty_po','name'=>'qty_po','title'=>'Qty PO'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'supp_code','name'=>'supp_code','title'=>'Supplier'],
            ['data'=>'supp_name','name'=>'supp_name','title'=>'Supplier Name'],
            ['data'=>'order_type','name'=>'order_type','title'=>'Order Type'],
            ['data'=>'dept_name','name'=>'dept_name','title'=>'Department'],
            ['data'=>'date','name'=>'date','title'=>'PR Date'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'noteku','name'=>'noteku','title'=>'Main Note'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
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
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSE",'7'=>'PO'];
            
        return view("purchaseRequest.index",$data);
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
        $prNumber="$key/$month/$year-$newCode";
        
        return $prNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        
        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['currentDate'] = date('d-m-Y');
        
        return view("purchaseRequest.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $orderDate = $request->orderDate;
        $orderType = $request->poType;
        $dept = $request->dept;
        $note = $request->note;
        $tsoCode = $request->tsoCode;
        $status = '1';
        $print_seq = 0;
        switch ($orderType) { 
            case 'std':
                $prLeadCode = 'PR';
                break;
            case 'sub':
                $prLeadCode = 'PRSUB';
                break;
            case 'tso':
                $prLeadCode = 'PRTSO';
                break;
            case 'rm':
                $prLeadCode = 'PRRM';
            break;
            default:
            $prLeadCode = 'PR';
        }
        
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
            // 'prNumber'=>'required|unique:purchase_request_hdr,po_number',
            'orderDate'  => 'required',
            'dept'  => 'required',
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
            $hasilUpdate = AppHelpers::resetCode($prLeadCode);
            $prNumber = $this->getLastCode($prLeadCode);
            DB::beginTransaction();
            try {
                    DB::table('purchase_request_hdr')->insert([
                        'pr_number' => $prNumber,
                        'dept' => $dept,
                        'date' => $orderDate,
                        'order_type' => $orderType,
                        'status' => $status,
                        'note' =>  $note,
                        'authorized_by' => '',
                        'prepared_by' =>  '',
                        'print_seq' => $print_seq,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'pr_number' => $prNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'supp_code' => $val->supp,
                            'note' => $val->note,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    if($orderType == 'tso'){
                        DB::table('target_order_hdr')
                        ->where('tso_code',$tsoCode)
                        ->update(
                            [
                                'pr_number' => $prNumber,
                                'updated_by' => Auth::user()->username,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }

                    DB::table('purchase_request_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $prNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prNumber'=>$prNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $prNumber is failed to saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prNumber'=>$prNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('purchase_request_hdr')
        ->where('id',$id)
        ->select('purchase_request_hdr.*'
        ,DB::raw('(select sum(qty) from purchase_request_det where pr_number = purchase_request_hdr.pr_number) as sum_qty') 
        ,DB::raw('(select count(*) from purchase_request_det where pr_number = purchase_request_hdr.pr_number) as sum_row')
        )
        ->get()->first();       

        $prNumber = $data['header']->pr_number;

        $data['details'] = DB::table('purchase_request_det')
        ->leftJoin('uom','uom.code','=','purchase_request_det.uom')
        ->leftJoin('article','article.article_code','=','purchase_request_det.article_code')
        ->where('pr_number',$data['header']->pr_number)
        ->select('purchase_request_det'.'.*'
            ,'uom.uom_group'
            ,DB::raw("concat(article_alternative_code,'-',article_desc) as article")
        )
        ->orderBy('purchase_request_det.id')
        ->get();       

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$prNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$prNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSE",'7'=>'PO'];
        $statusPr = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PO'];
        $data['statusPr'] = $statusPr[$data['header']->status-1];

        return view("purchaseRequest.show",$data);
        
    }

    public function edit(Request $request)
    {   
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit Purchase Request";
        $data['subtitle'] = "Edit Purchase Request";

        $data['header'] = DB::table('purchase_request_hdr')
        ->where('id',$id)
        ->get()->first();

        $prNumber = $data['header']->pr_number;
        $orderType = $data['header']->order_type;
        $data['details'] = DB::table('purchase_request_det')
        ->leftJoin('uom','uom.code','=','purchase_request_det.uom')
        ->where('pr_number',$data['header']->pr_number)
        ->orderBy('purchase_request_det.id')
        ->get(); 

        $data['articles']= DB::table('article')
        // ->whereNotIn('article_type',['FG','RM'])
        ->where(function($query) use ($orderType)  {
            $orderType=='std' ? $query->whereNotIn('article_type',['FG']) : $query->whereIn('article_type',['FG']);
         })
        ->orderBy('article_desc')
        ->get();   

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$prNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$prNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSE",'7'=>'PO'];
        $statusPr = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PO'];
        $data['statusPr'] = $statusPr[$data['header']->status-1];

        return view("purchaseRequest.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $prNumber = $request->prNumber;
        // $orderType = $request->poType;
        $orderDate = $request->orderDate;
        $dept = $request->dept;
        $note = $request->note;
        $status = '1';
        $print_seq = 0;

        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Authorized
        // 4 = Received
        // 5 = Canceled
        // 6 = closed
        
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
            // 'prNumber'=>'required|unique:purchase_request_hdr,po_number',
            // 'orderNumber' => 'required',
            'orderDate'  => 'required',
            'dept'  => 'required',
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
                    $row_affected=DB::table('purchase_request_hdr')
                    ->where('pr_number',$prNumber)
                    ->update(
                        [
                            'dept' => $dept,
                            'date' => $orderDate,
                            'status' => $status,
                            // 'order_type' => $orderType,
                            'note' =>  $note,
                            'authorized_by' => '',
                            'prepared_by' =>  '',
                            'print_seq' => $print_seq,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $prNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $prNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('purchase_request_det')
                        ->whereNotIn(DB::raw("CONCAT(pr_number,article_code)"),$dataSet)
                        ->where('pr_number',$prNumber)
                        ->delete();

                    foreach ($articles as $val) {
                        DB::table('purchase_request_det')
                        ->updateOrInsert(
                            ['pr_number' => $prNumber,'article_code' => $val->article_code],
                            [
                                'pr_number' => $prNumber,
                                'article_code' => $val->article_code,
                                'qty' => $val->qty,
                                'uom' => $val->uom,
                                'supp_code' => $val->supp,
                                'note' => $val->note,
                                'created_by' => Auth::user()->username,
                                'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                    }
                    
                    DB::commit();

                    $title ="Save $this->title";
                    $alert ="success";
                    $message = "$title $prNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prNumber'=>$prNumber));

            } catch (Exception $e) {
                
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $prNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prNumber'=>$prNumber));
            }
        }
    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $prNumber = $request->prNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$prNumber,$username);        
        // dd($statusLevelApproval);
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusPr = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSE",'7'=>'PO'];
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('purchase_request_hdr')
                ->where('pr_number',$prNumber)
                ->update(
                    [
                        'status' => $statusPr,
                        'authorized_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $prNumber,
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
                $message  = "$title $prNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusPr,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'$prNumber'=>$prNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $prNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusPr,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'$prNumber'=>$prNumber));
        }
    }

    public function destroy(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;       
        $pr_number = DB::table('purchase_request_hdr')->where('id',$id)->where('status','1')->value('pr_number');
        $rowAffected = DB::table('purchase_request_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('purchase_request_det')->where('pr_number',$pr_number)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $pr_number Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $pr_number Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Approved
        // 4 = Received
        // 5 = Canceled
        // 6 = closed
        // 7 = po

        $seachPr = strtolower($request->seachPr);
        $orderType = strtolower($request->orderType);
        $searchStatus = $request->searchStatus;
        $requestDate = $request->requestDate;
        $fromDate ="";
        $toDate = "";
        if ($requestDate){
            $date = explode("to",$requestDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('purchase_request_hdr')
        ->where(function ($query) use ($orderType,$seachPr,$searchStatus,$requestDate,$fromDate,$toDate) {
            $orderType ? $query->where('order_type',$orderType) : '';
            $seachPr ? $query->where('pr_number','ilike','%'.$seachPr.'%') : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
            $requestDate ? $query->whereBetween(DB::raw("to_date(date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('purchase_request_hdr.*',DB::raw("(select concat(code,'-',name) from depts where code = purchase_request_hdr.dept limit 1) as dept_name"))
        ->orderBy('id')
        ->get(); 
             
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ( $data->status == '2' or $data->status == '1') {
                if (Auth::user()->can('purchaseRequest-edit')) {
                $buttons .=     '<a href="'. route('purchaseRequest.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="check"></i>
                                    <span>'. __("Approve") .'</span>
                                </a>';
                }
            }

            if (Auth::user()->can('purchaseRequest-edit')) {
            $buttons .=         '<a href="'. route('purchaseRequest.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            // $buttons .=         '<a href="'. route('purchaseRequest.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
            //                         <i data-feather="printer"></i>
            //                         Print
            //                     </a>';
            }
            $buttons .=         '<a href="'. route('purchaseRequest.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            if (Auth::user()->can('purchaseRequest-delete') && ($data->status == '1' || $data->status =='2')) {
                $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        id='deleteButton'
                                        data-url='". route('purchaseRequest.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PO'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPo[$data->status - 1]."</div>";
        })
        ->addColumn('order_type', function ($data) {
            switch($data->order_type) {
                case 'std':
                    return "<div class='badge badge-primary'>Standar</div>";
                    break;
                case 'rm':
                    return "<div class='badge badge-info'>Raw Material</div>";
                    break;
                case 'tso':
                    return "<div class='badge badge-success'>Target SO</div>";
                    break;
                case 'sub':
                    return "<div class='badge badge-warning'>Subcontract</div>";
                    break;
                default:
                    return "<div class='badge badge-primary'>Standar</div>";
            } 
        })
        ->addColumn('pr_number', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            return '<span style="display: none;">'.$data->id.'</span><a class="badge d-block '.$badges[$data->status - 1].'" name="'.$data->pr_number.'" href="'. route('purchaseRequest.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->pr_number.'</span></a>';
            // return '<a href="'. route('purchaseRequest.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->pr_number.'</span></a>';
        })
        ->rawColumns(['action','order_type','status','pr_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $seachPr = strtolower($request->seachPr);
        $orderType = strtolower($request->orderType);
        $searchStatus = $request->searchStatus;
        $requestDate = $request->requestDate;
        $fromDate ="";
        $toDate = "";

        if ($requestDate){
            $date = explode("to",$requestDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('purchase_request_det')
        ->leftJoin('purchase_request_hdr','purchase_request_hdr.pr_number','purchase_request_det.pr_number')
        ->leftJoin('article','article.article_code','purchase_request_det.article_code')
        ->leftJoin('third_party','third_party.kode','purchase_request_det.supp_code')
        ->leftJoin('uom','uom.code','purchase_request_det.uom')
        ->where(function ($query) use ($orderType,$seachPr,$searchStatus,$requestDate,$fromDate,$toDate) {
            $orderType ? $query->where('order_type',$orderType) : '';
            $seachPr ? $query->where('purchase_request_det.pr_number','ilike','%'.$seachPr.'%') : '';
            $searchStatus ? $query->where('purchase_request_hdr.status',$searchStatus) : '';
            $requestDate ? $query->whereBetween(DB::raw("to_date(purchase_request_hdr.date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('purchase_request_det.*'
        ,DB::raw("(select concat(code,'-',name) from depts where code = purchase_request_hdr.dept limit 1) as dept_name")
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'purchase_request_hdr.status as statusku'
        ,'purchase_request_hdr.order_type'
        ,'purchase_request_hdr.date'
        ,'purchase_request_hdr.note as noteku'
        ,'third_party.nama as supp_name'
        ,'uom_group'
        ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty,'999,999,999.999') end as qtyku")
        ,DB::raw("(select concat(code,'-',name) from depts where code = purchase_request_hdr.dept limit 1) as dept_name")
        ,DB::raw("(select case when uom_group = 'PIECE' then TO_CHAR(qty,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty,'999,999,999.999') end from purchase_order_det where po_number = purchase_request_det.po_number and pr_number=purchase_request_det.pr_number and article_code=purchase_request_det.article_code) as qty_po")
        )
        ->orderBy('id')
        ->orderBy('pr_number')
        ->get(); 
             
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PO','PO'];

            if($data->statusku == 0){
                return "<div class='badge ".$badges[0]."'>".$statusPo[0]."</div>";
            }else{
                return "<div class='badge ".$badges[$data->statusku-1]."'>".$statusPo[$data->statusku-1]."</div>";
            }
            
        })
        ->addColumn('order_type', function ($data) {
            if ($data->order_type == 'std'){
                return "<div class='badge badge-primary'>Standar</div>";
            }else{
                return "<div class='badge badge-info'>Subcontract</div>";
            }
        })
        ->rawColumns(['order_type','status'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['companies']=DB::table('company')
        ->where('code','ASN')
        ->select('name as nama', 'address as alamat', DB::RAW('(select region_name from regions where region_code = city::integer)  as kota'),'tlp')
        ->get()->first();
                
        $poHdr=DB::table('purchase_request_hdr')
        ->where('id',$id)
        ->first();

        $prNumber=$poHdr -> po_number;
       

        $data['details']=DB::table('purchase_request_det')
        ->leftJoin('article','article.article_code','purchase_request_det.article_code')
        ->where('po_number',$prNumber)
        ->get();

        $data['totals']=DB::select("SELECT *,(gross-discount)+ppn as netto from (
            select a.po_number,authorized_by,prepared_by,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from purchase_request_det a
            left join purchase_request_hdr b
            on a.po_number = b.po_number 
            where a.po_number = '$prNumber'
            group by a.po_number,authorized_by,prepared_by) as oki");

        $data['suppliers']=DB::table('third_party')
        ->where('kode',$poHdr -> supplier_id)
        ->get();

        $data['keterangan']=$poHdr -> note;
        $data['prNumber'] =$prNumber;
        $data['poDate'] =$poHdr -> po_date;
        $data['poTerm'] =$poHdr -> termin;
        $data['poDelDate'] =$poHdr -> delivery_date;
        
        $data['status'] ='1';
        $data['no'] =1;

        view()->share($data);

        $pdf = PDF::loadView('purchaseRequest.print');
        return $pdf->stream("PO_$prNumber.pdf");

    }

    public function articleTso(Request $request)
    {
        $tsoCode = $request->tsoCode;
        $articles = DB::table('target_order_det')
        ->where('tso_code',$tsoCode)
        ->get();

        $dataSet = [];
        $randomCode = rand();
        foreach ($articles as $val) {
            $dataSet[] = [
                'code' => $randomCode,
                'article_code' => $val->article_code,
                'qty' => $val->qty_target //untuk perhitungan pakai yang qty_target sudah di konfirmasi ke bu ifah
                // 'qty' => $val->qty_forcast
            ];
        }

        DB::table('production_detail_temp')->insert($dataSet);

        $data=DB::select("SELECT 
        article_code_det as article_code
        ,min_package 
        ,sum(qty_order * qty_bom) as total
        ,ceil(sum(qty_order * qty_bom)/min_package) * min_package as grand_total
        ,uom_order as uom 
        from(
        select 
        bom_det.article_code as article_code_det
        ,production_detail_temp.qty as qty_order
        ,production_detail_temp.uom as uom_order
        ,bom_det.qty * coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = production_detail_temp.uom),1) as qty_bom
        ,bom_det.uom as uom_bom
        ,bom_hdr.article_code 
        ,coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = production_detail_temp.uom),1) as factor_qty
        ,(select coalesce(min_package,1) from article where article_code = bom_det.article_code) as min_package 
        from production_detail_temp
        left join bom_hdr on bom_hdr.article_code=production_detail_temp.article_code
        join bom_det on  bom_det.bom_code = bom_hdr.bom_code
        where production_detail_temp.code ='$randomCode'
        and bom_hdr.status = '3'
        ) a
        group by article_code_det,uom_order,min_package
        order by article_code_det
        ");

        // $data=DB::select("SELECT 
        //     article.article_code
        //     ,article_alternative_code
        //     ,article_desc
        //     ,article.uom
        //     ,qty,qty_proses
        //     ,qty_total
        //     ,article.article_type
        //     ,min_package
        //     ,qty_total/min_package as total_baru
        //     ,ceil(qty_total/min_package) as round
        //     ,ceil(qty_total/min_package) * min_package  as grand_total
        //     ,(select name from article_types where code = article.article_type) as kelompok 
        //     from (
        //     select article_code,sum(oki.qty) as qty
        //         ,sum(mari.qty) as qty_proses
        //         ,sum(oki.qty*mari.qty) as qty_total 
        //         from (
        //         select * from bom_det where bom_code in (
        //         select bom_code from bom_hdr 
        //             left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
        //             where bom_hdr.article_code in (select article_code from production_detail_temp))) oki
        //             left join(
        //                 select bom_code,qty from bom_hdr 
        //                 left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
        //                 where bom_hdr.article_code in (select article_code from production_detail_temp where code  = '$randomCode' )
        //             ) mari
        //             on oki.bom_code= mari.bom_code
        //                 group by article_code) so
        //         left join article on article.article_code = so.article_code");

        if ($data){
            DB::table('production_detail_temp')
                ->where('code',$randomCode)
                ->delete();
        }
        
        return response()->json($data);                        
    }
}
