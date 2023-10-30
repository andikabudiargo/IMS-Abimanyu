<?php

namespace App\Http\Controllers\Accounting;

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

class BankKeluarController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Bank Pembayaran";
        $this->moduleCode = "BK";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'voucher_number','name'=>'voucher_number','title'=>'Voucher Number'],
            ['data'=>'voucher_date','name'=>'voucher_date','title'=>'Date'],
            ['data'=>'supplier_name','name'=>'supplier_name','title'=>'Paid To'],
            // ['data'=>'description','name'=>'description','title'=>'Paid To'],
            ['data'=>'amount','name'=>'amount','title'=>'Amount'],
            ['data'=>'period','name'=>'period','title'=>'Period'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'statusku','name'=>'statusku','title'=>'Status'],
            ['data'=> 'approval_by','name'=> 'approval_by','title'=>'Approved By'],
            ['data'=> 'approval_at','name'=> 'approval_at','title'=>'Approved At'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'voucher_number','name'=>'voucher_number','title'=>'Voucher Number'],
            ['data'=>'account_number','name'=>'account_number','title'=>'Account Number'],
            ['data'=>'reference','name'=>'reference','title'=>'Reference'],
            ['data'=>'amount','name'=>'amount','title'=>'Amount'],           
            ['data'=>'voucher_date','name'=>'voucher_date','title'=>'Date'],
            ['data'=>'currency','name'=>'currency','title'=>'Currency'],
            ['data'=>'kurs','name'=>'kurs','title'=>'Kurs'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'order_no','name'=>'order_no','title'=>'Urutan'],
            ['data'=>'decription','name'=>'decription','title'=>'Desciption'],
            ['data'=>'debit','name'=>'debit','title'=>'Debit account'],
            ['data'=>'credit','name'=>'credit','title'=>'Credit account'],
            ['data'=>'amount','name'=>'amount','title'=>'Amount'],
            ['data'=>'memo','name'=>'memo','title'=>'Memo'],
            ['data'=>'auth_by','name'=>'auth_by','title'=>'Auth By'],
            ['data'=>'prep_by','name'=>'prep_by','title'=>'Prep By']
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

        $newCode = str_pad($newCode,4,"0",STR_PAD_LEFT);
        $month = str_pad(date('n'),2,"0",STR_PAD_LEFT);
        $year = date('y');
        $code="$key/$month/$year/$newCode";
        return $code;
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['type'] = 'pembayaran';

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        $status = ['NEW','VALIDATED','APPROVED','','DELETED','CLOSED'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED'];

        return view("accounting.bankKeluar.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['type'] = 'pembayaran';
        
        $data['suppliers'] = DB::table('third_party')
        ->where('third_party_type','supp')
        ->orderBy('nama')
        ->get();

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        return view("accounting.bankKeluar.create",$data);

    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $details = json_decode($request->details);
        $vcDate = $request->vcDate;
        $period = $request->period;
        $note = $request->note;
        $totalAmount= $request->totalAmount;
        $paidTo = $request->paidTo;
        $status = '1';
        $leadCode =$this->moduleCode;
        $paidToDesc = $request->paidToDesc;

        // dd($details);
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "KM Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            // 'poNumber'=>'required|unique:purchase_order_hdr,po_number',
            // 'pcNumber'  => 'required',
            'period'  => 'required'
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
            $vcNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                    DB::table('kas_hdr')->insert([
                        'voucher_number' =>$vcNumber,
                        'voucher_type' =>$leadCode,
                        'voucher_date' =>$vcDate,
                        // 'receive_from' =>$recFrom,
                        'paid_to' => $paidTo,
                        'description' => $paidToDesc,
                        'amount' =>$totalAmount,
                        'period' =>$period,
                        'year' =>date('Y'),                        
                        'note' => $note,
                        'status' => $status,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($details as $val) {
                        $dataSet[] = [
                            'voucher_number' => $vcNumber,
                            'account' => $val->account,
                            'description' => $val->description,
                            'cost_center' => $val->cc,
                            'debit' => $val->debit,
                            'credit' => $val->credit,
                            'reference' => $val->reference,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('kas_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $vcNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'vcNumber'=>$vcNumber));
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$vcNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'pcNumber'=>$vcNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('kas_hdr')
        ->leftJoin('third_party','third_party.kode','kas_hdr.paid_to')
        ->select('kas_hdr.*'
        // ,'description as supplier_name'
        // ,db::raw("case when description != null then description else concat(third_party.kode,'-',third_party.nama) end as supplier_name")
        ,db::raw("case when description != '' then kas_hdr.description else third_party.nama end as supplier_name")
        )
        ->where('kas_hdr.id',$id)
        ->get()->first();

        $vcNumber = $data['header']->voucher_number;
            
        $data['details'] = DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        ->select('kas_det'.'.*'
            ,db::raw("concat(accounts.account,'-',accounts.description) as account_name")
            ,'depts.name as cost_center_name'
        )
        ->where('voucher_number',$vcNumber)
        ->orderBy('id')
        ->get();

        $data['total']=DB::table('kas_det')
        ->select(DB::raw("sum(credit) as total_credit"),DB::raw("sum(debit) as total_debit"))
        ->where('voucher_number',$vcNumber)
        ->first();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$vcNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$vcNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'DELETED','6'=>'CLOSED'];
        $status = ['NEW','VALIDATED','APPROVED','','DELETED','CLOSED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("accounting.bankKeluar.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";
        $data['type'] = 'pembayaran';

        $data['header'] = DB::table('kas_hdr')
        // ->leftJoin('third_party','third_party.kode','kas_hdr.paid_to')
        // ->select('kas_hdr.*',db::raw("concat(third_party.kode,'-',third_party.nama) as supplier_name"))
        ->where('kas_hdr.id',$id)
        ->get()->first();

        $vcNumber = $data['header']->voucher_number;
            
        $data['details'] = DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        ->select('kas_det'.'.*'
            ,db::raw("concat(accounts.account,'-',accounts.description) as account_name")
            ,'depts.name as cost_center_name'
        )
        ->where('voucher_number',$vcNumber)
        ->orderBy('id')
        ->get();

        $data['suppliers'] = DB::table('third_party')
        ->where('third_party_type','supp')
        ->orderBy('nama')
        ->get();

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$vcNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$vcNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'CANCELED','6'=>'CLOSED'];
        $status = ['NEW','VALIDATED','APPROVED','','CANCELED','CLOSED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("accounting.bankKeluar.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $details = json_decode($request->details);
        $vcNumber = $request->vcNumber;
        $vcDate = $request->vcDate;
        $period = $request->period;
        $note = $request->note;
        $totalAmount= $request->totalAmount;
        $paidTo = $request->paidTo;
        $status = '1';
        $leadCode =$this->moduleCode;
        $paidToDesc = $request->paidToDesc;
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            // 'iunique' => "KM Number has already been taken",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            // 'poNumber'=>'required|unique:purchase_order_hdr,po_number',
            // 'pcNumber'  => 'required',
            'period'  => 'required'
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

                    $row_affected=DB::table('kas_hdr')
                    ->where('voucher_number',$vcNumber)
                    ->update(
                        [
                            'voucher_date' =>$vcDate,
                            // 'receive_from' =>$recFrom,
                            'paid_to' =>$paidTo,
                            'description' =>$paidToDesc,
                            'amount' =>$totalAmount,
                            'period' =>$period,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    DB::table('kas_det')
                        ->where('voucher_number',$vcNumber)
                        ->delete();

                    $dataSet = [];
                    foreach ($details as $val) {
                        $dataSet[] = [
                            'voucher_number' => $vcNumber,
                            'account' => $val->account,
                            'description' => $val->description,
                            'cost_center' => $val->cc,
                            'debit' => $val->debit,
                            'credit' => $val->credit,
                            'reference' => $val->reference,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('kas_det')->insert($dataSet);

                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $vcNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'vcNumber'=>$vcNumber));
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$vcNumber is failed update";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'pcNumber'=>$vcNumber));
            }
        }

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $vcNumber = $request->vcNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$vcNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $status = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'','5'=>'DELETED','6'=>"CLOSED"];
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('kas_hdr')
                ->where('voucher_number',$vcNumber)
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
                        'module_number' => $vcNumber,
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

                $listInvoice=DB::table('kas_det')
                ->where('voucher_number',$vcNumber)
                ->pluck('reference')->toArray();
                
                //update invoice jadi paid
                // ['1'=>'DRAFT','2'=>'VALIDATED','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','6'=>'CLOSED','6'=>'PAID'];
                if($status == '3'){
                    DB::table('ap_invoice')
                    ->whereIn('ap_number',$listInvoice)
                    ->update(
                        [   
                            'status' =>'6',
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]
                    );
                }
                
                DB::commit();
                $title ="Approve $this->title";
                $alert  ="success";
                $message  = "$title $vcNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'$vcNumber'=>$vcNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $vcNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => $status,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'$vcNumber'=>$vcNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;    
        $id=Crypt::decryptString($request->id);   
        $status = '5'; //DELETED
        
        /*
            status nya bukan 6 bisa di delete
            status 6 = closed
        */

        $vcNumber = DB::table('kas_hdr')->where('id',$id)->where('status','<>','6')->value('voucher_number');

        $rowAffected=DB::table('kas_hdr')
        ->where('voucher_number',$vcNumber)
        ->update(
            [
                'status' =>$status,
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );
        
        // $rowAffected = DB::table('kas_hdr')->where('id',$id)->delete();

        if($rowAffected>0){
            // DB::table('kas_det')->where('voucher_number',$vcNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$vcNumber Successfully Deleted";
            \LogActivity::addToLog('KM delete ',"username: $username Status $message");
            // return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$vcNumber Failed to Delete";
            \LogActivity::addToLog('KM delete ',"username: $username Status $message");
            // return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        $seachVc = strtolower($request->seachVc);
        $vcDate = $request->vcDate;
        $period = $request->period;
        $year = $request->year;
        $vcType = $this->moduleCode;
        $fromDate = "";
        $toDate = "";
        $searchStatus=$request->searchStatus;

        if ($vcDate){
            $date = explode("to",$vcDate);
            $fromDate = trim($date[0]);
            $toDate = trim($date[1]);

            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('kas_hdr')
        ->leftJoin('third_party','third_party.kode','kas_hdr.paid_to')
        ->where(function ($query) use ($seachVc,$vcDate,$fromDate,$toDate,$period,$year,$searchStatus) {
            $seachVc ? $query->where('voucher_number','ilike','%'.$seachVc.'%') : '';
            $vcDate ? $query->whereBetween(DB::raw("to_date(voucher_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $period ? $query->where('period', $period) : '';
            $year ? $query->where('year', $year) : '';
            $searchStatus ? $query->where('kas_hdr.status', $searchStatus) : '';
        })
        ->where('voucher_type',$vcType)
        ->where('kas_hdr.status','<>','5')
        ->select(
            'kas_hdr.*'
            ,'kas_hdr.status as statusku'
            // ,db::raw("concat(third_party.kode,'-',third_party.nama) as supplier_name")
            ,db::raw("case when description != '' then kas_hdr.description else third_party.nama end as supplier_name")
            ,db::raw("(select (select name from users where username = z.username) from approval_history z where module_number = kas_hdr.voucher_number order by approval_order desc limit 1) as approval_by")
            ,db::raw("(select to_char(approval_date::date, 'DD-MM-YYYY') from approval_history z where module_number = kas_hdr.voucher_number order by approval_order desc limit 1) as approval_at")
        )
        ->orderBy('id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ( $data->statusku == '2' or $data->statusku == '1') {
                // if (Auth::user()->can('bankKeluar-approve')) {
                $buttons .=     '<a href="'. route('bankKeluar.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="check"></i>
                                    <span>'. __("Approve") .'</span>
                                </a>';
                // }
            }
            
            // if (Auth::user()->can('bankKeluar-edit')) {
                if ( $data->statusku == '2' or $data->statusku == '1') {
                $buttons .=     '<a href="'. route('bankKeluar.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
                }
            // }

            $buttons .=         '<a href="'. route('bankKeluar.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                        
            $buttons .=         '<a href="'. route('bankKeluar.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            
            
            // if (Auth::user()->can('bankKeluar-delete')) {
            if ($data->statusku != '5') {
                $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("bankKeluar.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='trash-2' class='feather-14-red'></i>
                                    Delete
                                </a>";
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('statusku', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $status = ['NEW','VALIDATED','APPROVED','','DELETED','CLOSED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','statusku'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['title'] ='Bank Keluar';
        
        $data['header'] = DB::table('kas_hdr')
        // ->leftJoin('third_party','third_party.kode','kas_hdr.paid_to')
        ->select('kas_hdr.*'
        ,'description as supplier_name'
        // ,db::raw("concat(third_party.kode,'-',third_party.nama) as supplier_name")
        )
        ->where('kas_hdr.id',$id)
        ->get()->first();


        $vcNumber=$data['header']->voucher_number;
       
        $data['details']=DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->select('kas_det.*','accounts.description as account_name')
        ->where('voucher_number',$vcNumber)
        ->orderBy('kas_det.id')
        ->orderBy('credit')
        ->get();

        $data['total']=DB::table('kas_det')
        ->select(DB::raw("sum(credit) as total_credit"),DB::raw("sum(debit) as total_debit"))
        ->where('voucher_number',$vcNumber)
        ->first();

        $data['costCenter']=DB::table('kas_det')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        ->where('voucher_number',$vcNumber)
        ->distinct('depts.name')
        ->pluck('depts.name')->implode(',');

        $data['approval1']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',1)
        ->first();

        $data['approval2']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',2)
        ->first();

        $data['approval3']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',3)
        ->first();

        $data['approval4']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$vcNumber)
        ->where('approval_order',4)
        ->first();

        return view('accounting.bankKeluar.print',$data);

        // view()->share($data);

        // $pdf = PDF::loadView('accounting.bankKeluar.print');
        // return $pdf->stream("$vcNumber.pdf");

    }

    public function getInvoiceAmount(Request $request)
    {
        $refNumber = $request->vRef;
        $amount = db::table('ap_invoice')
        ->where('inv_number',$refNumber)
        ->select(db::raw("grand_total as amount"))
        ->value('amount');

        return response()->json(array('amount' => $amount));
    }

}
