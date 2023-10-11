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

class DeliveryReceiptController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Delivery Received";
        $this->moduleCode = "DR";
    }

    public function getTableColoumn()
    {
        // $kolom=
        // [
        //     ['data'=>'action','name'=>'action','title'=>'action', 'orderable'=> false,'searchable'=>false],
        //     ['data'=>'dr_number','name'=>'dr_number','title'=>'DR Number'],
        //     ['data'=>'dr_date','name'=>'dr_date','title'=>'DR Date'],
        //     ['data'=>'delivery_number','name'=>'delivery_number','title'=>'DN Number'],
        //     ['data'=>'delivery_date','name'=>'delivery_date','title'=>'DN Date'],
        //     ['data'=>'submittedBy','name'=>'submittedBy','title'=>'Submitted By'],
        //     ['data'=>'submitted_at','name'=>'submitted_at','title'=>'Submitted At'],
        //     ['data'=>'receivedBy','name'=>'receivedBy','title'=>'Received By'],
        //     ['data'=>'dr_date','name'=>'dr_date','title'=>'Received At'],            
        //     ['data'=>'status','name'=>'status','title'=>'Status'],
        //     ['data'=>'note','name'=>'note','title'=>'Note'],
        //     ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
        //     ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
        // ];

        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action', 'orderable'=> false,'searchable'=>false],
            ['data'=>'so_number','name'=>'so_number','title'=>'SO Number'],
            ['data'=>'delivery_number','name'=>'delivery_number','title'=>'DN Number'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'DN Date'],
            ['data'=>'nama','name'=>'nama','title'=>'Customer'],
            ['data'=>'statusKu','name'=>'statusKu','title'=>'Status'],
            ['data'=>'dr_number','name'=>'dr_number','title'=>'DR Number'],
            ['data'=>'dr_date','name'=>'dr_date','title'=>'DR Date'],
            ['data'=>'invoice_number','name'=>'invoice_number','title'=>'Invoice Number'],
            ['data'=>'receivedBy','name'=>'receivedBy','title'=>'Received By'],
            ['data'=>'dr_date','name'=>'dr_date','title'=>'Received At'],            
            ['data'=>'submittedBy','name'=>'submittedBy','title'=>'Submitted By'],
            ['data'=>'submitted_at','name'=>'submitted_at','title'=>'Submitted At'],
            ['data'=>'notesku','name'=>'notesku','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['kolom'] = $this->getTableColoumn();
        $status = $request->statusKu;       
        $data['status'] = ['0'=>'POSTED','1'=>'RECEIVED','2'=>'SUBMITTED','5'=>'CANCELED'];
        $data['statusKu'] = $status ? $status :'0';

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();
            
        return view("dnReceipt.index",$data);
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
        // $months = ['01', '02', '03','04','05', '06', '07', '08','09','10','11','12'];
        $month = $months[date('n')-1];
        $year = date('y');
        $code="$key/ASN/$year/$month/$newCode";
        
        return $code;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $statusKu = $request->status;

        $dn = DB::table('delivery_hdr')->where('id',$id)->first();
        
        $data['users'] = DB::table('users')
        ->where ('status','=','1')
        ->orderBy('name')
        ->get();

        $data['drNumber'] ="";
        $data['dnNumber'] =$dn->delivery_number;
        $data['dnDate'] = $dn->delivery_date;    
        $data['statusKu'] = $statusKu;   

        return view("dnReceipt.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $deliveryDate = $request->deliveryDate;
        $dnNumber = $request->dnNumber;
        // $submitAt = $request->submitAt ? date('Y-m-d', strtotime($request->submitAt)) : '';
        // $submitBy = $request->submitBy;
        $receiveBy = $request->receiveBy;
        $receiveAt = $request->receiveAt ? date('Y-m-d', strtotime($request->receiveAt)) : '';
        $note = $request->note;
        $status = '1';
       
        // $data['status'] = ['1'=>'RECEIVED','2'=>'SUBMITED','3'=>'','4'=>'','5'=>'CANCELED'];

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
            'dnNumber' => 'required',
            // 'submitAt' => 'required',
            // 'submitBy' => 'required',
            'receiveBy' => 'required',
            'receiveAt' => 'required'
        ]);
        
        $error_array = array();
        $success_output = '';
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save  $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode($this->moduleCode);
            $drNumber = $this->getLastCode($this->moduleCode);
            DB::beginTransaction();
            try {
                    $rowAffected = DB::table('dn_receipt')->insertGetId([
                        'dr_number' => $drNumber,
                        'dr_date' => $receiveAt,
                        'delivery_number' => $dnNumber,
                        'delivery_date' => $deliveryDate ,
                        // 'submitted_at' => $submitAt,
                        // 'submitted_by' => $submitBy,
                        'received_by' => $receiveBy,
                        'status' => $status,
                        'note' => $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    if ($rowAffected){
                        DB::table('delivery_hdr')
                        ->where('delivery_number',$dnNumber)
                        ->update(
                            [
                                'status'=> '8',
                                'updated_by' => Auth::user()->username,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        \LogActivity::addToLog('Delivery update',"username: $username Status Delivery update by receipt DN");
                    }

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $drNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->route('dnReceipt.index')->with(['alert'=>$alert,'message'=> $message,'drNumber'=> $drNumber]);

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $drNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->route('dnReceipt.index')->with(['alert'=>$alert,'message'=> $message,'drNumber'=> $drNumber]);
            }
        }
    }

    public function edit(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $statusKu = $request->status;
        
        $data['users'] = DB::table('users')
        ->where ('status','=','1')
        ->orderBy('name')
        ->get();

        $data['dnReceipt'] = DB::table('dn_receipt')
        ->where ('id','=',$id)
        ->first();

        $data['drDate'] = date('d-m-Y', strtotime($data['dnReceipt']->dr_date));
        $data['statusKu'] = $statusKu;
        
        return view("dnReceipt.edit",$data);
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $drNumber= $request->drNumber;
        $submitAt = $request->submitAt ? date('Y-m-d', strtotime($request->submitAt)) : '';
        $submitBy = $request->submitBy;
        $note = $request->note;
        $status = '2';
        $statusKu = $request->statusKu;

        $data['status'] = ['0'=>'POSTED','1'=>'RECEIVED','2'=>'SUBMITTED','5'=>'CANCELED'];

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
            'submitAt' => 'required',
            'submitBy' => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Submit  $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            
            DB::beginTransaction();
            try {
                    $rowAffected = DB::table('dn_receipt')
                    ->where('dr_number',$drNumber)
                    ->update(
                        [
                            'submitted_at' => $submitAt,
                            'submitted_by' => $submitBy,
                            'status' => $status,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                     
                    DB::commit();
                    $title ="Submit $this->title";
                    $alert  ="success";
                    $message  = "$title $drNumber is successfully submitted";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->route('dnReceipt.index',['statusKu'=>$statusKu])->with(['alert'=>$alert,'message'=> $message,'drNumber'=> $drNumber,'statusKu'=>$statusKu]);

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Submit $this->title";
                $alert  ="warning";
                $message  = "$title $drNumber is failed to sybmit";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->route('dnReceipt.index',['statusKu'=>$statusKu])->with(['alert'=>$alert,'message'=> $message,'drNumber'=> $drNumber,'statusKu'=>$statusKu]);
            }
        }
    }
  
    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $drNumber = DB::table('dn_receipt')->where('id',$id)->where('status','1')->first();

        $rowAffected = DB::table('dn_receipt')
        ->where('dr_number',$drNumber->dr_number)
        ->update(
            [
                'status'=> '2',
                'delivery_number' => $drNumber->delivery_number.'(C)',
                'note'  => $drNumber->note.'(C)',
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        if($rowAffected>0){

            DB::table('delivery_hdr')
            ->where('delivery_number',$drNumber->delivery_number)
            ->update(
                [
                    'status'=> '4',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            \LogActivity::addToLog('Delivery update',"username: $username Status Delivery Canceled receipt DN");

            $title ="Cancel $this->title";
            $alert  ="success";
            $message  = "$title $drNumber->dr_number Successfully Canceled";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $drNumber->dr_number Failed to Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        $searchDn = strtolower($request->searchDn);
        $searchStatus = $request->searchStatus;
        $drDate = $request->drDate;
        $dnDate = $request->dnDate;
        $customer =$request->customer;
        $searchStatusDn = '';

        if($searchStatus == '0'){
            $searchStatusDn = '4';
        } 

        $fromDate ="";
        $toDate = "";
        $fromDateDn ="";
        $toDateDn = "";
 
        if ($drDate){
            $date = explode("to",$drDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        if ($dnDate){
            $dateDn = explode("to",$dnDate);
            if(count($dateDn)>1){
                $fromDateDn = implode("/", array_reverse(explode("-", trim($dateDn[0]))));
                $toDateDn = implode("/", array_reverse(explode("-", trim($dateDn[1]))));
            }else{
                $fromDateDn = implode("/", array_reverse(explode("-", trim($dateDn[0]))));
                $toDateDn = $fromDateDn; 
            }
        }

        $data = DB::table('delivery_hdr')
        ->leftJoin('third_party','third_party.kode','delivery_hdr.customer_id')
        ->leftJoin('dn_receipt','dn_receipt.delivery_number','delivery_hdr.delivery_number')
        ->leftJoin('invoice_hdr','invoice_hdr.dn_number','invoice_hdr.dn_number')
        ->leftJoin('users as a','dn_receipt.received_by','a.username')
        ->leftJoin('users as b','dn_receipt.submitted_by','b.username')
        ->where(function ($query) use ($searchDn,$drDate,$searchStatus,$fromDate,$toDate,$searchStatusDn,$dnDate,$fromDateDn,$toDateDn,$customer) {
            $searchDn ? $query->where('delivery_hdr.delivery_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('dn_receipt.status',$searchStatus) : '';
            $drDate ? $query->whereBetween(DB::raw("to_date(dn_receipt.dr_date,'YYYY-MM-DD')"), [$fromDate, $toDate]) : '';
            $searchStatusDn ? $query->where('delivery_hdr.status',$searchStatusDn) : '';
            $dnDate ? $query->whereBetween(DB::raw("to_date(delivery_hdr.delivery_date,'DD-MM-YYYY')"), [$fromDateDn, $toDateDn]) : '';
            $customer ? $query->where('delivery_hdr.customer_id',$customer) : '';
        })
        ->whereIn('delivery_hdr.status',['4','8'])
        ->select('delivery_hdr.*'
        ,'dn_receipt.dr_number'
        ,'dn_receipt.dr_number'
        ,'b.name as submittedBy'
        ,'a.name as receivedBy'
        ,'dn_receipt.status as statusKu'
        ,'dn_receipt.id as idku'
        ,'dn_receipt.note as notesku'
        ,'invoice_hdr.invoice_number'
        ,'nama'
        ,db::raw("to_char(dn_receipt.submitted_at, 'DD-MM-YYYY') as submitted_at")
        ,db::raw("to_char(to_date(dn_receipt.dr_date,'YYYY-MM-DD'), 'DD-MM-YYYY') as dr_date")        
        // ,'delivery_hdr.delivery_number as delivery_number_1'
        // ,DB::raw("concat(kode,'-',nama) as customer_name")
        )
        ->orderBy('id')
        ->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            // if ( $data->status == '1' ){
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ( $data->status == '4') {

                if (Auth::user()->can('dnReceipt-create')) {
                    // $buttons .= '<a href="'. route('dnReceipt.create', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                    //             <i data-feather="file-text"></i>
                    //             <span>'. __("Receive") .'</span>
                    //         </a>';
                    $buttons .= '<a href="javascript:void(0);" onclick="receiveDr(\''.Crypt::encryptString($data->id).'\')" class="dropdown-item">
                            <i data-feather="file-text"></i>
                            <span>'. __("Receive") .'</span>
                    </a>';
                    
                }
                
            }

            if ($data->statusKu == '1'){
                if (Auth::user()->can('dnReceipt-edit')) {
                // $buttons .=         '<a href="'. route('dnReceipt.edit', ['id'=>Crypt::encryptString($data->idku)]) .'" class="dropdown-item">
                //                         <i data-feather="file-text"></i>
                //                         Submit
                //                     </a>';
                    $buttons .= '<a href="javascript:void(0);" onclick="submitDr(\''.Crypt::encryptString($data->idku).'\')" class="dropdown-item">
                        <i data-feather="check"></i>
                        <span>'. __("Submit") .'</span>
                    </a>';
                }
            }
                
            // if (Auth::user()->can('dnReceipt-delete')) {
            //     $buttons .=         "<a href='javascript:;'
            //                         class='dropdown-item' 
            //                         data-size='sm'
            //                         data-ajax-delete='true'
            //                         data-confirm='Are You Sure want to Cancel?|This action can not be undone. Do you want to continue?' 
            //                         data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
            //                         data-modal-id='".$data->id."'
            //                         data-url='". route('dnReceipt.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
            //                         <i data-feather='trash-2' class='feather-14-red'></i>
            //                         <span>". __('Cancel') ."</span>
            //                     </a>";
            // }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
            // }
        })
        ->addColumn('statusKu', function ($data) {
            $badges=['badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];
            // $data['status'] = ['1'=>'Received','2'=>'Submited','3'=>'','4'=>'','5'=>'CANCELED'];
            $statusList = ['RECEIVED','SUBMITTED','','','CANCELED'];
            if ($data->statusKu){
                return "<div class='badge ".$badges[$data->statusKu - 1]."'>".$statusList[$data->statusKu - 1]."</div>";
            }else{
                return "<div class='badge badge-primary'>POSTED</div>";
            }
            
        })
        ->rawColumns(['action','statusKu'])
        ->make(true);
    }

}
