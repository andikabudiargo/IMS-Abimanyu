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

class DnReturnController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "DN Return";
        $this->moduleCode = "DN-RETURN";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
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
            ['data'=>'return_number','name'=>'return_number','title'=>'Return Number'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Description'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
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
        $username =  Auth::user()->username;
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        // $data['status'] = ['1'=>'OPEN','2'=>'SO','3'=>'CLOSED'];
        $data['status'] = ['1'=>'OPEN','3'=>'CLOSED'];

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("dnReturn.index",$data);
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
        $prNumber="$key-$year-$month-$newCode";
        
        return $prNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        $username =  Auth::user()->username;
        
        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // $data['articleList']= DB::table('article')
        // ->whereIn('article_type',['FG'])
        // ->orderBy('article_desc')
        // ->get();  
        
        $data['currentDate'] = date('d-m-Y');
        
        return view("dnReturn.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $customerId = $request->customerId;
        $returnDate = $request->returnDate;
        $note = $request->note;
        $status = '1';
        $returnNumber ='';
        $leadCode = $this->moduleCode;
                
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
            'returnDate'  => 'required',
            'customerId'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';

        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }

            $alert ="warning";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));

        }else{
            $hasilUpdate = AppHelpers::resetCode($leadCode);
            $returnNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                DB::table('dn_return_hdr')->insert([
                    'return_number' => $returnNumber,
                    'customer_id' => $customerId,
                    'return_date' => $returnDate,
                    'note' => $note,
                    'origin_return_number' => $returnNumber,
                    'status' => $status,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                $dataSet = [];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        'return_number' => $returnNumber,
                        'article_code' => $val->article_code,
                        'qty' => $val->qty,
                        'uom' => $val->uom,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }

                DB::table('dn_return_det')->insert($dataSet);

                DB::commit();
                $title ="Save $this->title";
                $alert  ="success";
                $message  = "$title $returnNumber is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'returnNumber'=>$returnNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $returnNumber is failed to saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'returnNumber'=>$returnNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('dn_return_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'dn_return_hdr.customer_id')
        ->where('origin_return_number', function($query) use ($id){
            $query->select('return_number')->from('dn_return_hdr')->where('id',$id);
        })
        ->where('dn_return_hdr.status','!=','4')
        ->select('dn_return_hdr.*'
        ,DB::raw('(select sum(qty) from dn_return_det where return_number = dn_return_hdr.return_number) as sum_qty') 
        ,DB::raw('(select count(*) from dn_return_det where return_number = dn_return_hdr.return_number) as sum_row')
        ,DB::raw("concat(kode,'-',nama) as customer_name")
        )
        ->orderBy('id')
        ->get(); 

        $returnNumber = $data['headers'][0]->return_number;
        
        $data['details'] = DB::table('dn_return_det')
        ->whereIn('dn_return_det.return_number', function($query) use ($returnNumber){
            $query->select('return_number')->from('dn_return_hdr')->where('origin_return_number',$returnNumber);
        })
        ->leftJoin('article','article.article_code','=','dn_return_det.article_code')
        ->select('dn_return_det'.'.*'
            ,DB::raw("concat(article_alternative_code,'-',article_desc) as article")
            ,DB::raw("(select STRING_AGG( (qty::real)::text,' -> ' ORDER BY return_number) AS main from dn_return_det p where article_code = dn_return_det.article_code and return_number like '$returnNumber%' ) as notes")
        )
        ->orderBy('dn_return_det.return_number')
        ->orderBy('dn_return_det.id')
        ->get();       

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $status = ['OPEN','SALES ORDER','CLOSED','CANCELED'];
        $data['status'] = $status[$data['headers'][0]->status-1];

        return view("dnReturn.show",$data);
        
    }

    public function edit(Request $request)
    {   
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('dn_return_hdr')
        ->where('id',$id)
        ->first();
    
        $returnNumber = $data['header']->return_number;
        $custCode = $data['header']->customer_id;

        $data['details'] = DB::table('dn_return_det')
        ->where('return_number',$returnNumber)
        ->orderBy('id')
        ->get(); 

        $dataQuery= DB::table('article') 
        // ->whereIn('article.article_code', function($query) use ($custCode) {
        //     $query->select('article_code')
        //     ->from('bom_hdr') 
        //     ->where('status','3')
        //     ->where('customer',$custCode);
        // })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->orderBy('article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose article</option>';

        foreach ($dataQuery as $row){
            $output .='<option value="'.$row->article_code.'" data-uom="'.$row->uom.'">'.$row->article_alternative_code.'-'. $row->article_desc.'</option>';
        }


        $data['articles'] = $output;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // dd($data['customers']);
        // dd($data['title']);

        $status = ['OPEN','SALES ORDER','CLOSED','CANCELED'];
        $data['status'] = $status[$data['header']->status-1];

        
        return view("dnReturn.edit",$data);
        
    }
    
    public function update(Request $request)
    {

        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $returnNumber = $request->returnNumber;
        $customerId = $request->customerId;
        $returnDate = $request->returnDate;
        $note = $request->note;
               
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
            'returnDate'  => 'required',
            'customerId'  => 'required',
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
                $row_affected=DB::table('dn_return_hdr')
                ->where('return_number',$returnNumber)
                ->update(
                    [
                        'customer_id' => $customerId,
                        'return_date' => $returnDate,
                        'note' => $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                $dataset=[];
                foreach ($articles as $val) {
                    $dataSet[] = [
                        $returnNumber.$val->article_code
                    ];
                    
                }

                /*
                    Delete kalo article tidak ada di $returnNumber dan article nya $val->article_code
                    berdasarkan 2 kondisi
                */

                DB::table('dn_return_det')
                    ->whereNotIn(DB::raw("CONCAT(return_number,article_code)"),$dataSet)
                    ->where('return_number',$returnNumber)
                    ->delete();

                foreach ($articles as $val) {
                    DB::table('dn_return_det')
                    ->updateOrInsert(
                        ['return_number' => $returnNumber,'article_code' => $val->article_code],
                        [
                            'return_number' => $returnNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }
                
                DB::commit();

                $title ="Save $this->title";
                $alert ="success";
                $message = "$title $returnNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'returnNumber'=>$returnNumber));

            } catch (Exception $e) {
                
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $returnNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'returnNumber'=>$returnNumber));
            }
        }
    }

    public function destroy(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;       
        $tDnHdr = DB::table('dn_return_hdr')->where('id',$id)->first();

        $returnNumber = $tDnHdr->return_number;
        
        DB::beginTransaction();
        try {
                $rowAffected=DB::table('dn_return_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => '4',
                        'return_number' => $returnNumber."(C)",
                        'origin_return_number' => $returnNumber."(C)",
                        'reason' => "Cancel",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if($rowAffected>0){
                    DB::table('dn_return_det')
                    ->where('return_number',$returnNumber)
                    ->update(
                    [
                        'return_number' => $returnNumber."(C)",
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                    );
                    DB::commit();
                    $title ="Delete $this->title";
                    $alert  ="success";
                    $message  = "$title $returnNumber Successfully Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }else{
                    DB::rollBack();
                    $title ="Delete $this->title";
                    $alert  ="warning";
                    $message  = "$title $returnNumber Failed to Delete";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

                }

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $returnNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function closed(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $returnNumber = DB::table('dn_return_hdr')->where('id',$id)->value('return_number');
        $status = '3';
        DB::beginTransaction();
        try {
                $row_affected=DB::table('dn_return_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                DB::commit();
                $title ="Close $this->title";
                $alert  ="success";
                $message  = "$title $returnNumber Successfully Closed";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Close $this->title";
            $alert  ="warning";
            $message  = "$title $returnNumber Failed to Close";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        // status:
        // 1 = Open
        // 2 = 
        // 3 = Closed
        // 4 = Canceled
        
        $username =  Auth::user()->username;
        $searchDn = strtolower($request->searchDn);
        $searchStatus = $request->searchStatus;
        $returnDate = $request->returnDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate ="";
        $toDate = "";       
 
        if ($returnDate){
            $date = explode("to",$returnDate);
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

        $data = DB::table('dn_return_hdr')
        ->leftJoin('third_party', 'third_party.kode', '=', 'dn_return_hdr.customer_id')
        ->where(function ($query) use ($searchDn,$searchStatus,$returnDate,$fromDate,$toDate,$searchCustomer) {
            $searchDn ? $query->where('dn_return_hdr.return_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('dn_return_hdr.status',$searchStatus) : '';
            $returnDate ? $query->whereBetween(DB::raw("to_date(return_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchCustomer ? $query->where('dn_return_hdr.customer_id',$searchCustomer) : '';
        })
        ->where('dn_return_hdr.status','!=','4')
        ->select('dn_return_hdr.*',DB::raw("concat(kode,'-',nama) as customer_name"))
        ->orderBy('id')
        ->get(); 
             
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ($data->status == '1') {
            $buttons .= '<a href="'. route('dnReturn.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="file-text"></i>
                               Edit
                        </a>';
            }

            
            $buttons .= '<a href="'. route('dnReturn.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                            <i data-feather="printer"></i>
                            Print
                        </a>';

            $buttons .= '<a href="'. route('dnReturn.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                            <i data-feather="list"></i>
                            Detail
                         </a>';
                
            // if ($data->status == '1') {
                $buttons .= "<a href='javascript:;'
                                class='dropdown-item' 
                                data-size='sm'
                                data-ajax-delete='true'
                                data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                data-modal-id='".$data->id."'
                                id='deleteButton'
                                data-url='". route('dnReturn.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                <i data-feather='trash-2' class='feather-14-red'></i>
                                <span>". __('Cancel') ."</span>
                            </a>";
            // }
            
            // if ( $data->status == '2' ){
            //     // if (Auth::user()->can('purchaseOrder-delete')) {
            //         $buttons .="<a href='javascript:;'
            //         class='dropdown-item' 
            //         data-size='sm'
            //         data-ajax-delete='true'
            //         data-confirm='Are You Sure want to Close?|This action can not be undone. Do you want to continue?' 
            //         data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
            //         data-modal-id='".$data->id."'
            //         id='deleteButton'
            //         data-url='". route('dnReturn.close', ['id'=>Crypt::encryptString($data->id)]) ."'>
            //         <i data-feather='x' class='feather-14-red'></i>
            //         <span>". __('Close') ."</span>
            //         </a>";
            //     // }
            // }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $statusPr = ['OPEN','','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPr[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','return_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $username =  Auth::user()->username;
        $searchDn = strtolower($request->searchDn);
        $searchStatus = $request->searchStatus;
        $returnDate = $request->returnDate;
        $searchCustomer = $request->searchCustomer;
        $fromDate ="";
        $toDate = "";

        if ($returnDate){
            $date = explode("to",$returnDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }      
    
        $data = DB::table('dn_return_det')
        ->leftJoin('dn_return_hdr','dn_return_hdr.return_number','dn_return_det.return_number')
        ->leftJoin('article','article.article_code','dn_return_det.article_code')
        ->leftJoin('third_party','third_party.kode','dn_return_hdr.customer_id')
        ->where(function ($query) use ($searchDn,$searchStatus,$returnDate,$fromDate,$toDate,$searchCustomer) {
            $searchDn ? $query->where('dn_return_hdr.return_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('dn_return_hdr.status',$searchStatus) : '';
            $returnDate ? $query->whereBetween(DB::raw("to_date(return_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchCustomer ? $query->where('dn_return_hdr.customer_id',$searchCustomer) : '';
        })
        ->where('dn_return_hdr.status','!=','4')
        ->select('dn_return_det.*'
            ,'article_alternative_code'
            ,'article.article_desc'
            ,'dn_return_hdr.status'
            ,'dn_return_hdr.return_date'
            ,'dn_return_hdr.note'
            ,'third_party.nama as customer_name'    
        )
        ->orderBy('id')
        ->orderBy('return_number')
        ->get(); 
             
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $statusPr = ['OPEN','SO','CLOSED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPr[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $data['title'] = "Print $this->title";
        $data['subtitle'] = "Print $this->title";
        $id=Crypt::decryptString($request->id);
                
        $tDnHdr=DB::table('dn_return_hdr')
        ->where('dn_return_hdr.id',$id)
        ->first();

        $data['tDnHdr']=DB::table('dn_return_hdr')
        ->where('dn_return_hdr.id',$id)
        ->first();

        $returnNumber=$tDnHdr->return_number;

        $data['details']=DB::table('dn_return_det')
        ->leftJoin('article','article.article_code','dn_return_det.article_code')
        ->select('article_alternative_code'
        ,'article_desc'
        ,'dn_return_det.qty'
        ,'dn_return_det.uom'
        ,DB::raw("(select STRING_AGG( (qty::real)::text,' -> ' ORDER BY return_number) AS main from (select * from dn_return_det p where article_code = dn_return_det.article_code and return_number like '$returnNumber%' limit 2) sub) as notes")
        )
        ->where('return_number',$returnNumber)
        ->orderBy('dn_return_det.id')
        ->get();

        $data['tDnNumber'] =$returnNumber;
        $data['tDnDate'] =$tDnHdr->return_date;
        $data['tDnNote'] =$tDnHdr->note;
        
        $statusPr = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PO'];
        $data['prStatus'] = $statusPr[$tDnHdr->status-1];

        $data['no'] =0;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->where('kode',$tDnHdr->customer_id)
        ->orderBy('nama')
        ->first();

        return view('dnReturn.print',$data);

    }

    public function getArticle(Request $request){
        
        $custCode = $request->custCode;
        $data= DB::table('article') 
        ->whereIn('article.article_code', function($query) use ($custCode) {
            $query->select('article_code')
            ->from('bom_hdr') 
            ->where('status','3')
            ->where('customer',$custCode);
        })
        ->where('third_party',$custCode)
        ->where('article_type','FG')
        ->orderBy('article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose article</option>';

        foreach ($data as $row){
            $output .='<option value="'.$row->article_code.'" data-uom="'.$row->uom.'">'.$row->article_alternative_code.'-'. $row->article_desc.'</option>';
        }

        return $output;

    }


}
