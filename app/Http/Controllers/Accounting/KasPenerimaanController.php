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

class KasPenerimaanController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Kas Penerimaan";
        $this->moduleCode = "KM";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'voucher_number','name'=>'voucher_number','title'=>'Voucher Number'],
            ['data'=>'voucher_date','name'=>'voucher_date','title'=>'Date'],
            ['data'=>'receive_name','name'=>'receive_name','title'=>'Receive From'],
            ['data'=>'amount','name'=>'amount','title'=>'Amount'],
            ['data'=>'period','name'=>'period','title'=>'Period'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
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
        $data['type'] = 'penerimaan';

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        return view("accounting.kas.index",$data);
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $data['type'] = 'penerimaan';
        
        $data['accounts'] = DB::table('accounts')
        ->orderBy('account')
        ->get();

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        return view("accounting.kas.create",$data);

    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $details = json_decode($request->details);
        $vcDate = $request->vcDate;
        $period = $request->period;
        $note = $request->note;
        $totalAmount= $request->totalAmount;
        $recFrom = $request->recFrom;
        $status = '1';
        $leadCode =$this->moduleCode;

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
                        'receive_from' =>$recFrom,
                        // 'paid_to' =>,
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
                            // 'reference' => ,
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
        ->leftJoin('accounts','accounts.account','kas_hdr.receive_from')
        ->select('kas_hdr.*',db::raw("concat(accounts.account,'-',description) as receive_name"))
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

        $status = ['NEW','AUTHORIEED','DELETED','CLOSED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("accounting.kas.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";
        $data['type'] = 'penerimaan';

        $data['header'] = DB::table('kas_hdr')
        ->leftJoin('accounts','accounts.account','kas_hdr.receive_from')
        ->select('kas_hdr.*',db::raw("concat(accounts.account,'-',description) as receive_name"))
        ->where('kas_hdr.id',$id)
        ->get()->first();

        $voucher_number = $data['header']->voucher_number;
            
        $data['details'] = DB::table('kas_det')
        ->leftJoin('accounts','accounts.account','kas_det.account')
        ->leftJoin('depts','depts.code','kas_det.cost_center')
        ->select('kas_det'.'.*'
            ,db::raw("concat(accounts.account,'-',accounts.description) as account_name")
            ,'depts.name as cost_center_name'
        )
        ->orderBy('id')
        ->get();

        $data['accounts'] = DB::table('accounts')
        ->orderBy('account')
        ->get();

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $status = ['NEW','AUTHORIEED','DELETED','CLOSED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("accounting.kas.edit",$data);
        
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
        $recFrom = $request->recFrom;
        $status = '1';
        $leadCode =$this->moduleCode;
        
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
                            'receive_from' =>$recFrom,
                            // 'paid_to' =>,
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
                            // 'reference' => ,
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

    public function otorisasi(Request $request)
    {
        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Authorized
        // 4 = Received
        // 5 = Canceled
        // 6 = closed
        // 7 = Revised

        $username =  Auth::user()->username;
        $poNumber = $request -> poNumber;
        $statusKM = 'Authorized';
        $status = '3';

        DB::beginTransaction();
        try {
                $row_affected=DB::table('purchase_order_hdr')
                ->where('po_number',$poNumber)
                ->update(
                    [
                        'status' => $status,
                        'authorized_by' => Auth::user()->username,
                        'authorized_at' => date('Y-m-d H:i:s')
                    ]
                );

                DB::commit();
                $alert  ="alert-success";
                $message  = "KM $poNumber is successfully Authorized";
                \LogActivity::addToLog('KM update ',"username: $username Status $message");
                return response()->json(array('statusPo' => $statusPo,'status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "KM $poNumber is failed to Authorize";
            \LogActivity::addToLog('KM update ',"username: $username Status $message");
            return response()->json(array('statusPo' => $statusPo,'status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;    
        $id=Crypt::decryptString($request->id);   
        $status = '3'; //DELETED
        
        /*
            status nya bukan 4 bisa di delete
            status 4 = closed
        */

        $vcNumber = DB::table('kas_hdr')->where('id',$id)->where('status','<>','4')->value('voucher_number');

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
        $vcType = $this->moduleCode;
        $fromDate = "";
        $toDate = "";

        if ($vcDate){
            $date = explode("to",$vcDate);
            $fromDate = trim($date[0]);
            $toDate = trim($date[1]);

            // if(count($date)>1){
            //     $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            //     $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            // }else{
            //     $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            //     $toDate = $fromDate; 
            // }
        }

        $data = DB::table('kas_hdr')
        ->leftJoin('accounts','accounts.account','kas_hdr.receive_from')
        ->where(function ($query) use ($seachVc,$vcDate,$fromDate,$toDate) {
            $seachVc ? $query->where('voucher_number','ilike','%'.$seachVc.'%') : '';
            $vcDate ? $query->whereBetween('voucher_date', [$fromDate, $toDate]) : '';
        })
        ->where('voucher_type',$vcType)
        ->where('kas_hdr.status','<>','3')
        ->select(
            'kas_hdr.*'
            ,'kas_hdr.status as statusku'
            ,db::raw("concat(accounts.account,'-',description) as receive_name"))
        ->orderBy('id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            // if (Auth::user()->can('bankPenerimaan-edit')) {
                $buttons .=     '<a href="'. route('kasPenerimaan.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            // }

            $buttons .=         '<a href="'. route('kasPenerimaan.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                        
            $buttons .=         '<a href="'. route('kasPenerimaan.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            
            
            // if (Auth::user()->can('kasPenerimaan-delete')) {
            if ($data->statusku != '4') {
                $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("kasPenerimaan.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Delete
                                </a>";
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['title'] ='Kas Masuk';
        
        $data['header']=DB::table('kas_hdr')
        ->where('id',$id)
        ->first();

        $vcNumber=$data['header']->voucher_number;
       
        $data['details']=DB::table('kas_det')
        ->where('voucher_number',$vcNumber)
        ->get();

        $data['total']=DB::table('kas_det')
        ->select(DB::raw("sum(credit) as total_credit"),DB::raw("sum(debit) as total_debit"))
        ->where('voucher_number',$vcNumber)
        ->first();

        return view('accounting.kas.print',$data);

        // view()->share($data);

        // $pdf = PDF::loadView('accounting.kas.print');
        // return $pdf->stream("$vcNumber.pdf");

    }

}
