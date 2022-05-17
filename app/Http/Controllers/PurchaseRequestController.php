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

class PurchaseRequestController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Purchase Request";
        $this->moduleCode = "PR";
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false,'searchable'=>false],
            ['data'=>'pr_number','name'=>'pr_number','title'=>'PR Number'],
            ['data'=>'order_type','name'=>'order_type','title'=>'PO Type'],
            ['data'=>'dept','name'=>'dept','title'=>'Department'],
            ['data'=>'date','name'=>'date','title'=>'PR Date'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
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
        $status = '1';
        $print_seq = 0;
        $poLeadCode = $orderType=='std' ? 'PR' : 'PRSUB'; 

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
            $hasilUpdate = AppHelpers::resetCode($poLeadCode);
            $prNumber = $this->getLastCode($poLeadCode);
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
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('purchase_request_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('purchase_request_det')
        ->leftJoin('article','article.article_code','=','purchase_request_det.article_code')
        ->where('pr_number',$data['header']->pr_number)
        ->orderBy('purchase_request_det.id')
        ->get();       

        $data['articles']= DB::table('article') 
        ->whereNotIn('article_type',['FG','RM'])
        ->orderBy('article_desc')
        ->get();   

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        return view("purchaseRequest.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['title'] = "Edit Purchase Request";
        $data['subtitle'] = "Edit Purchase Request";

        $data['header'] = DB::table('purchase_request_hdr')
        ->where('id',$id)
        ->get()->first();

        $orderType = $data['header']->order_type;

        $data['detail'] = DB::table('purchase_request_det')
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

        return view("purchaseRequest.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $prNumber = $request->prNumber;
        $orderType = $request->poType;
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
                            'order_type' => $orderType,
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
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $prNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prNumber'=>$prNumber));
            }
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
        // 3 = Authorized
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
            $statusPo = ['New','Validated','Authorized','Received','Canceled','Closed','PO'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPo[$data->status - 1]."</div>";
        })
        ->addColumn('order_type', function ($data) {
            if ($data->order_type == 'std'){
                return "<div class='badge badge-primary'>Standar</div>";
            }else{
                return "<div class='badge badge-info'>Subcontract</div>";
            }
        })
        ->addColumn('pr_number', function ($data) {
            return '<a href="'. route('purchaseRequest.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->pr_number.'</span></a>';
        })
        ->rawColumns(['action','order_type','status','pr_number'])
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
}
