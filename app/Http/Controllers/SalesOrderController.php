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

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Sales Order";

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['employees'] = DB::table('employees')
        ->where('job_position','05')
        ->get();

        $data['types'] = ['NEW','REPEAT'];

        // $data['status'] = ['NEW','PROCESS','SENT'];

        $data['status'] = ['1'=>'NEW','2'=>'PROCESS','3'=>'SENT'];

            
        return view("salesOrder.index",$data);
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
        ->where('code_key','SO')
        ->value('code_number'); 

        $month = date('m');
        $year = date('y');
        $soNumber="$key/ASN/$month/$year/$newCode";
        
        return $soNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Sales Order";
        $data['subtitle'] = "Create Sales Order";
        
        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['employees'] = DB::table('employees')
        ->where('job_position','05')
        ->get();

        $data['types'] = ['NEW','REPEAT'];
        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        // $data['orderNumber'] = $this->getLastCode('SO');

        return view("salesOrder.create",$data);
    }

    public function articleCodeCreate(Request $request){
        $customer = $request->customer;
        $leadingCode = 'FG';

        $lastCode = DB::table('article')
        ->where('third_party','=',$customer)
        ->orderBy('article_alternative_code','DESC')->first();

        if (!$lastCode){
            $newCode = '00001';
        }else{
            $newCode = str_pad(substr($lastCode->article_alternative_code,5)+1, 5, "0", STR_PAD_LEFT);
        }

        $artilceCode = DB::table('third_party')
        ->where('kode',$customer)
        ->select(DB::raw("CONCAT('$leadingCode',inisial,'$newCode') AS new_code"))->value('new_code');

        return  Response()->json($artilceCode);
    
    }

    public function store(Request $request)
    {
        
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $orderDate = $request->orderDate;
        $currency = $request->currency;
        $type = $request->type;
        $poNumber = $request->poNumber;
        $customer = $request->customer;
        $salesman = $request->salesman;
        $ppn = $request->ppn;
        $pph = 0;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $note = $request->note;
        $status = '1';
        $gudang = 'false';
        $kurs = 1;

        // return $articles;

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
            'poNumber'=>'required|unique:sales_order_hdr,po_number',
            // 'orderNumber' => 'required',
            'orderDate'  => 'required',
            'currency'  => 'required',
            'type'  => 'required',
            'customer'  => 'required',
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
            $soCode = $this->getLastCode('SO');
            DB::beginTransaction();
            try {
                    DB::table('sales_order_hdr')->insert([
                        'so_code' => $soCode,
                        'po_number' => $poNumber,
                        'customer_id' => $customer,
                        'salesman_code' => $salesman ,
                        'so_date' => $orderDate,
                        'currency' => $currency,
                        'kurs' => $kurs,
                        'ppn' => $ppn,
                        'pph23' => $pph,
                        'order_type' => $type,
                        'status' => $status,
                        'gudang' => $gudang ,
                        'note' =>  $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'so_code' => $soCode,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'price' => $val->price,
                            'ppn' => $totalPpn,
                            'pph23' => $totalPph,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('sales_order_det')->insert($dataSet);

                    DB::commit();
                    $alert  ="alert-success";
                    $message  = "SO $soCode is successfully saved";
                    \LogActivity::addToLog('SO save ',"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'soNumber'=>$soCode));

            } catch (Exception $e) {
                DB::rollBack();
                $alert  ="alert-warning";
                $message  = "SO $soCode is failed to save";
                \LogActivity::addToLog('SO save ',"username: $username Status $message");
                return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'soNumber'=>$soCode));
            }
        }
    }

    public function show(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Detail Sales Order";
        $data['subtitle'] = "Detail Sales Order";

        $data['header'] = DB::table('sales_order_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('sales_order_det')
        ->leftJoin('article','article.article_code','=','sales_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','sales_order_det.article_code')
        ->where('so_code',$data['header']->so_code)
        ->select('sales_order_det'.'.*','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();       

        $data['articles']= DB::table('article') 
        ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('third_party',$data['header']->customer_id)
        ->orderBy('article_desc')
        ->select('article'.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group')
        ->get();   

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['employees'] = DB::table('employees')
        ->where('job_position','05')
        ->get();

        $data['types'] = ['NEW','REPEAT'];
        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        return view("salesOrder.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Edit Sales Order";
        $data['subtitle'] = "Edit Sales Order";

        $data['header'] = DB::table('sales_order_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('sales_order_det')
        ->leftJoin('article','article.article_code','=','sales_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','sales_order_det.article_code')
        ->where('so_code',$data['header']->so_code)
        ->select('sales_order_det'.'.*','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();       

        $data['articles']= DB::table('article') 
        ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->where('third_party',$data['header']->customer_id)
        ->orderBy('article_desc')
        ->select('article'.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group')
        ->get();   

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['employees'] = DB::table('employees')
        ->where('job_position','05')
        ->get();

        $data['types'] = ['NEW','REPEAT'];
        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        return view("salesOrder.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $orderNumber = $request->orderNumber;
        $articles = json_decode($request -> articles);
        $orderDate = $request->orderDate;
        $currency = $request->currency;
        $type = $request->type;
        $poNumber = $request->poNumber;
        $customer = $request->customer;
        $salesman = $request->salesman;
        $ppn = $request->ppn;
        $pph = 0;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $note = $request->note;
        $status = '1';
        $gudang = 'false';
        $kurs = 1;
        
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
            // 'poNumber'=>'required|unique:sales_order_hdr,po_number',
            'orderNumber' => 'required',
            'orderDate'  => 'required',
            'currency'  => 'required',
            'type'  => 'required',
            'customer'  => 'required',
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
                    $row_affected=DB::table('sales_order_hdr')
                    ->where('so_code',$orderNumber)
                    ->update(
                        [
                            'po_number' => $poNumber,
                            'customer_id' => $customer,
                            'salesman_code' => $salesman ,
                            'so_date' => $orderDate,
                            'currency' => $currency,
                            'kurs' => $kurs,
                            'ppn' => $ppn,
                            'pph23' => $pph,
                            'order_type' => $type,
                            'status' => $status,
                            'gudang' => $gudang ,
                            'note' =>  $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $orderNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $orderNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('sales_order_det')
                        ->whereNotIn(DB::raw("CONCAT(so_code,article_code)"),$dataSet)
                        ->where('so_code',$orderNumber)
                        ->delete();

                    foreach ($articles as $val) {
                        DB::table('sales_order_det')
                        ->updateOrInsert(
                            ['so_code' => $orderNumber,'article_code' => $val->article_code],
                            [
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'price' => $val->price,
                            'ppn' => $totalPpn,
                            'pph23' => $totalPph,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }
                    
                    DB::commit();
                    $alert  ="alert-success";
                    $message  = "SO $orderNumber is successfully updated";
                    \LogActivity::addToLog('SO save ',"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'soNumber'=>$orderNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $alert  ="alert-warning";
                $message  = "SO $orderNumber is failed to updated";
                \LogActivity::addToLog('SO save ',"username: $username Status $message");
                return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'soNumber'=>$orderNumber));
            }
        }

    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;
        $id = $request->id;
        $so_code = DB::table('sales_order_hdr')->where('id',$id)->where('status','1')->value('so_code');
        $rowAffected = DB::table('sales_order_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('sales_order_det')->where('so_code',$so_code)->delete();
            $alert  ="alert-success";
            $message  = "SO $so_code Successfully Deleted";
            \LogActivity::addToLog('SO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "SO $so_code Failed to Delete";
            \LogActivity::addToLog('SO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        $searchOrder = strtolower($request->searchOrder);
        $seachPo = strtolower($request->seachPo);
        $searchCustomer = $request->searchCustomer;
        $searchSalesman = $request->searchSalesman;
        $searchType = $request->searchType;
        $searchStatus = $request->searchStatus;
        $orderDate = $request->orderDate;
        $fromDate = "";

        if ($orderDate){
            $date = explode("to",$orderDate);
            // $date1=trim($date[0]);
            // $date2=trim($date[1]);
            // $fromDate = date($date1);
            // $toDate = date($date2);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }      

        $data=DB::table('sales_order_hdr')
        ->select('sales_order_hdr.*','third_party.nama as cust_name')
        ->leftJoin('third_party', 'third_party.kode', '=', 'sales_order_hdr.customer_id')
        ->where(function ($query) use ($seachPo,$searchOrder,$searchCustomer,$searchSalesman,$searchType,$searchStatus,$fromDate) {
            $seachPo ? $query->where('po_number','ilike','%'.$seachPo.'%') :'';
            $searchOrder ? $query->where('so_code','ilike','%'.$searchOrder.'%') :'';
            $searchCustomer ? $query->where('customer_id',$searchCustomer) :'';
            $searchSalesman ? $query->where('salesman_code',$searchSalesman) :'';
            $searchType ? $query->where('order_type',$searchType) :'';
            $searchStatus ? $query->where('status',$searchStatus) :'';
            $fromDate ? $query->whereBetween('sales_order_hdr.created_at', [$fromDate, $toDate]):'';
        })->get();

        // $filter='';
        
        // if ($seachPo !='' ){
        //     $filter.="lower(po_number) like '%$seachPo%' and ";
        // }

        // if ($searchOrder !='' ){
        //     $filter.="lower(so_number) like '%$searchOrder%' and ";
        // }

        // if ($searchCustomer  != '' ){
        //     $filter.="customer_id = '$searchCustomer' and ";            
        // }

        // if ($searchSalesman  != '' ){
        //     $filter.="salesman_code = '$searchSalesman' and ";            
        // }

        // if ($searchType  != '' ){
        //     $filter.="order_type = '$searchType' and ";            
        // }

        // if ($searchStatus  != '' ){
        //     $filter.="status = '$searchStatus' and ";            
        // }

        // if ($orderDate  != '' ){
        //     $date = explode("to",$orderDate);
        //     $date1=trim($date[0]);
        //     $date2=trim($date[1]);
        //     $filter.= "to_date(so_date, 'DD/MM/YYYY') BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
        // }

        // if ($filter !=''){
        //     $filter=" where ".substr($filter,0,-4);
        // }

        // $data = DB::select("SELECT * FROM sales_order_hdr $filter");


        
        // $data=DB::table('sales_order_hdr')->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (Auth::user()->can('salesOrder-edit')) {
            $buttons .=         '<a href="'. route('salesOrder.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            $buttons .=         '<a href="'. route('salesOrder.show', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
            }
            if (Auth::user()->can('salesOrder-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("salesOrder.destroy", ["id"=>$data->id]) ."'>
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

   
}
