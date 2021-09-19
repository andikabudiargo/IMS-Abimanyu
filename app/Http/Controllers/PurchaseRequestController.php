<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Response;
use App\Permission;
use DataTables;
use DB;
use PDF;
use AppHelpers;

class PurchaseRequestController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Purchase Request";

        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Authorized
        // 4 = Received
        // 5 = Canceled
        // 6 = closed
        // 7 = po

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'AUTHORIZED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSE",'7'=>'PO'];
            
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
        $data['title'] = "Create Purchase Request";
        $data['subtitle'] = "Create Purchase Request";
        
        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['depts'] = DB::table('depts')
        ->orderBy('name')
        ->get();

        return view("purchaseRequest.create",$data);
    }

    public function store(Request $request)
    {
        
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $orderDate = $request->orderDate;
        $dept = $request->dept;
        $note = $request->note;
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
            $alert ="alert-danger";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode('PO');
            $prNumber = $this->getLastCode('PR');
            DB::beginTransaction();
            try {
                    DB::table('purchase_request_hdr')->insert([
                        'number' => $prNumber,
                        'dept' => $dept,
                        'date' => $orderDate,
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
                    $alert  ="alert-success";
                    $message  = "PR $prNumber is successfully saved";
                    \LogActivity::addToLog('PR save ',"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'prNumber'=>$prNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $alert  ="alert-warning";
                $message  = "PR $prNumber is failed to save";
                \LogActivity::addToLog('PR save ',"username: $username Status $message");
                return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'prNumber'=>$prNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Detail Purchase Request";
        $data['subtitle'] = "Detail Purchase Request";

        $data['header'] = DB::table('purchase_request_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('purchase_request_det')
        ->leftJoin('article','article.article_code','=','purchase_request_det.article_code')
        ->where('pr_number',$data['header']->number)
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
        $id=$request->id;
        $data['title'] = "Edit Purchase Request";
        $data['subtitle'] = "Edit Purchase Request";

        $data['header'] = DB::table('purchase_request_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('purchase_request_det')
        ->where('pr_number',$data['header']->number)
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

        return view("purchaseRequest.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $prNumber = $request->prNumber;
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
            $alert ="alert-danger";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {
                    $row_affected=DB::table('purchase_request_hdr')
                    ->where('number',$prNumber)
                    ->update(
                        [
                            'dept' => $dept,
                            'date' => $orderDate,
                            'status' => $status,
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
                    $alert  ="alert-success";
                    $message  = "PR $prNumber is successfully updated";
                    \LogActivity::addToLog('PR update ',"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'prNumber'=>$prNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $alert  ="alert-warning";
                $message  = "PR $prNumber is failed to updated";
                \LogActivity::addToLog('PR update ',"username: $username Status $message");
                return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'prNumber'=>$prNumber));
            }
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id = $request->id;
        $po_number = DB::table('purchase_request_hdr')->where('id',$id)->where('status','1')->value('number');
        $rowAffected = DB::table('purchase_request_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('purchase_request_det')->where('pr_number',$po_number)->delete();
            $alert  ="alert-success";
            $message  = "PR $po_number Successfully Deleted";
            \LogActivity::addToLog('SO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "PR $po_number Failed to Delete";
            \LogActivity::addToLog('PR delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
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
        $searchStatus = $request->searchStatus;
        $requestDate = $request->requestDate;
       
        $filter='';
        
        if ($seachPr !='' ){
            $filter.="lower(a.number) like '%$seachPr%' and ";
        }

        if ($searchStatus  != '' ){
            $filter.="status = '$searchStatus' and ";            
        }

        if ($requestDate  != '' ){
            $date = explode("to",$requestDate);
            $date1=trim($date[0]);
            $date2=trim($date[1]);
            $filter.= "to_date(date, 'DD/MM/YYYY')  BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
        }

        
        if ($filter !=''){
            $filter=" where ".substr($filter,0,-4);
        }

        $data=DB::select("SELECT *,(select concat(code,'-',name) from depts where code = dept limit 1) as dept_name from purchase_request_hdr $filter");
        
        // $data=DB::table('purchase_request_hdr')->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('purchaseRequest-edit')) {
            $buttons .=         '<a href="'. route('purchaseRequest.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            $buttons .=         '<a href="'. route('purchaseRequest.print', ['id'=>$data->id]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            }
            $buttons .=         '<a href="'. route('purchaseRequest.show', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            if (Auth::user()->can('purchaseRequest-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("purchaseRequest.destroy", ["id"=>$data->id]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Delete
                                </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        ->addColumn('group_id', function ($user) {
            return '';
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id = $request -> id;

        $data['companies']= array(
            "nama"=> "PT ABIMANYU SEKAR NUSANTARA",
            "alamat"=> "KP. KARANG MULYA RT 014 RW 005 DESA CIKOPO",
            "kota" => "KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT",
            "tlp" =>  ""
        );
        
        $data['suppliers']=array(
            'nama'=>'PT ABIMANYU SEKAR NUSANTARA',
            'alamat'=>'KP. KARANG MULYA RT 014 RW 005 DESA CIKOPO',
            'kota' =>'KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT',
            'tlp' => ''
        );
        
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
