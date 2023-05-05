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

class SalesOrderController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Sales Order";
        $this->moduleCode = "SO";
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action', 'orderable'=>false, 'searchable'=>false],
            ['data'=>'so_code','name'=>'so_code','title'=>'SO Code'],
            ['data'=>'so_code_1','name'=>'so_code_1','title'=>'SO Code','visible'=>false],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'customer_id','name'=>'customer_id','title'=>'Customer'],
            ['data'=>'cust_name','name'=>'cust_name','title'=>'Name'],
            ['data'=>'salesman_code','name'=>'salesman_code','title'=>'Salesman'],
            ['data'=>'so_date','name'=>'so_date','title'=>'Date'],
            ['data'=>'order_type','name'=>'order_type','title'=>'Type'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note']

        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'so_code_1','name'=>'so_code_1','title'=>'SO Code'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'so_date','name'=>'so_date','title'=>'Date'],
            ['data'=>'customer','name'=>'customer','title'=>'Customer'],
            ['data'=>'salesman','name'=>'salesman','title'=>'Salesman'],
            ['data'=>'ppn','name'=>'ppn','title'=>'PPN'],
            ['data'=>'order_type','name'=>'order_type','title'=>'Order Type'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'price','name'=>'price','title'=>'Price'],
            ['data'=>'price_service','name'=>'price_service','title'=>'Price Service'],
            ['data'=>'ppn_price','name'=>'ppn_price','title'=>'PPN'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
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

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['employees'] = DB::table('employees')
        ->where('job_position','05')
        ->get();

        $data['types'] = ['NEW','REPEAT'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSED",'7'=>'PAID'];

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
        ->where('code_key',$key)
        ->value('code_number'); 

        $month = date('m');
        $year = date('y');
        $soNumber="$key/ASN/$year/$month/$newCode";
        
        return $soNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        
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

        $data['attribute'] = DB::table('attributes')
        ->where('attr_name','main')
        ->pluck('attr_value','attr_code');

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
        $pph23 = $request->pph23;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $note = $request->note;
        $status = '1';
        $gudang = 'false';
        $kurs = 1;

        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Approved
        // 4 = Received
        // 5 = Canceled
        // 6 = Closed
        // 7 = Paid

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
            $title="Save SO";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
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
                        'pph23' => $pph23,
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
                            'price_service' => $val->price_service,
                            'ppn' => ($val->price*$val->qty) * $ppn/100,
                            'pph23' => ($val->price_service*$val->qty) * $pph23/100,
                            'status' => $status,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('sales_order_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $soCode is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'soNumber'=>$soCode));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $soCode is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'soNumber'=>$soCode));
            }
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
    
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['header'] = DB::table('sales_order_hdr')
        ->select('sales_order_hdr.*'
        ,DB::raw("(select concat(kode,' - ',nama) from third_party where kode = sales_order_hdr.customer_id) as supp_name") 
        ,DB::raw('(select sum(qty) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_qty') 
        ,DB::raw('(select count(*) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_row')
        ,DB::raw('(select round(sum((qty*price) + (qty*price_service))) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_amount')
        ,DB::raw('(select round(sum(((qty*price) + (qty*price_service))*sales_order_hdr.ppn/100)) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_ppn')
        ,DB::raw('(select round(sum(((qty*price) + (qty*price_service))*sales_order_hdr.pph23/100)) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_pph23')
        )
        ->where('id',$id)
        ->get()->first();

        $soCode = $data['header']->so_code;

        $data['detail'] = DB::table('sales_order_det')
        ->leftJoin('article','article.article_code','=','sales_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','sales_order_det.article_code')
        ->leftJoin('uom','uom.code','=','sales_order_det.uom')
        ->where('so_code',$soCode)
        ->select('sales_order_det'.'.*'
        ,DB::raw('round(sales_order_det.qty) as qty')
        ,'article_stock.article_qty as qty_stock'
        ,'uom.uom_group'
        , DB::raw('(SELECT name from group_materials where code = group_of_material) as group')
        , DB::raw("concat(article_alternative_code,'-',article_desc) as article")
        )
        ->orderBy('id')
        ->get();

        // dd($data['detail']);

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

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$soCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$soCode,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSED",'7'=>'PAID'];
        $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID'];
        $data['statusSo'] = $statusSo[$data['header']->status-1];

        return view("salesOrder.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;

        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('sales_order_hdr')
        ->where('id',$id)
        ->get()->first();

        $soCode = $data['header']->so_code;

        $data['detail'] = DB::table('sales_order_det')
        ->leftJoin('article','article.article_code','=','sales_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','sales_order_det.article_code')
        ->where('so_code',$data['header']->so_code)
        ->select('sales_order_det'.'.*',DB::raw('round(sales_order_det.qty) as qty'),'article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
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

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$soCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$soCode,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSED",'7'=>'PAID'];
        $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID'];
        $data['statusSo'] = $statusSo[$data['header']->status-1];

        return view("salesOrder.edit",$data);
        
    }

    public function close(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $modulCode = $this->moduleCode;

        $data['title'] = "Close $this->title";
        $data['subtitle'] = "Close $this->title";

        $data['header'] = DB::table('sales_order_hdr')
        ->where('id',$id)
        ->get()->first();

        $soCode = $data['header']->so_code;

        $data['detail'] = DB::table('sales_order_det')
        ->leftJoin('article','article.article_code','=','sales_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','sales_order_det.article_code')
        ->where('so_code',$data['header']->so_code)
        ->select('sales_order_det'.'.*',DB::raw('round(sales_order_det.qty) as qty'),'article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
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

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$soCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$soCode,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSED",'7'=>'PAID'];
        $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID'];
        $data['statusSo'] = $statusSo[$data['header']->status-1];

        return view("salesOrder.close",$data);
        
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
        $pph23 = $request->pph23;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $note = $request->note;
        $gudang = 'false';
        $kurs = 1;
        $modulCode = $this->moduleCode;
        $approveLevel = $request->approveLevel;
        $statusSimpan = $request->statusSimpan;

        // status:
        // 1 = New
        // 2 = Updated
        // 3 = Approved
        // 4 = Received
        // 5 = Canceled
        // 6 = Closed
        // 7 = Paid

        if($statusSimpan == 'approve'){
            $maxApproval = DB::table('approval_master')
            ->where('module_code',$modulCode)
            ->value('approval_number');
            $status = $maxApproval == $approveLevel ? '3': $status = '2';
        }else{
            $status = '1';
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

            $title="Update SO";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
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
                        'pph23' => $pph23,
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
                        'price_service' => $val->price_service,
                        'ppn' => ($val->price*$val->qty) * $ppn/100,
                        'pph23' => ($val->price_service*$val->qty) * $pph23/100,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }

                if ( $statusSimpan == 'approve' ){
                    DB::table('approval_history')->insert([
                        'module_code' => $modulCode,
                        'module_number' => $orderNumber,
                        'username' => Auth::user()->username,
                        'approval_order' => $approveLevel,
                        'approval_date' => date('Y-m-d'),
                        'status' => 1,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                
                DB::commit();
                $title ="Update $this->title";
                $alert  ="success";
                $message  = "$title $orderNumber is successfully updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'soNumber'=>$orderNumber));
            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$title $orderNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'soNumber'=>$orderNumber));
            }
        }

    }

    public function updateClose(Request $request)
    {
        $username =  Auth::user()->username;
        $orderNumber = $request->orderNumber;
        $articles = json_decode($request -> articles);

        DB::beginTransaction();
        try {
        
            foreach ($articles as $val) {
                DB::table('sales_order_det')
                ->where('so_code',$val->so_code)
                ->where('article_code',$val->article_code)
                ->update(
                    [
                    'status' => $val->status,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
            }
            
            DB::commit();
            $title ="Close $this->title";
            $alert  ="success";
            $message  = "$title $orderNumber is successfully closed";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'soNumber'=>$orderNumber));
            
        } catch (Exception $e) {
            DB::rollBack();
            $title ="Close $this->title";
            $alert  ="warning";
            $message  = "$title $orderNumber is failed to close";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'soNumber'=>$orderNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $so_code = DB::table('sales_order_hdr')->where('id',$id)->where('status','1')->value('so_code');
        $rowAffected = DB::table('sales_order_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('sales_order_det')->where('so_code',$so_code)->delete();
            $title ="Delete $this->title";
            $alert ="success";
            $message  = "$title $so_code Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $so_code Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
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
        $toDate = "";

        if ($orderDate){
            $date = explode("to",$orderDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }      

        $data=DB::table('sales_order_hdr')
        ->select(
            'sales_order_hdr.*'
            ,'sales_order_hdr.so_code as so_code_1'
            ,'third_party.nama as cust_name'

         )
        ->leftJoin('third_party', 'third_party.kode', '=', 'sales_order_hdr.customer_id')
        ->where(function ($query) use ($seachPo,$searchOrder,$searchCustomer,$searchSalesman,$searchType,$searchStatus,$fromDate,$toDate) {
            $seachPo ? $query->where('po_number','ilike','%'.$seachPo.'%') :'';
            $searchOrder ? $query->where('so_code','ilike','%'.$searchOrder.'%') :'';
            $searchCustomer ? $query->where('customer_id',$searchCustomer) :'';
            $searchSalesman ? $query->where('salesman_code',$searchSalesman) :'';
            $searchType ? $query->where('order_type',$searchType) :'';
            $searchStatus ? $query->where('status',$searchStatus) :'';
            $fromDate ? $query->whereBetween(DB::raw("to_date(so_date,'DD-MM-YYYY')"), [$fromDate, $toDate]):'';
        })->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            if (Auth::user()->can('salesOrder-edit') and ($data->status == 1 or $data->status == 2)) {
            $buttons .=         '<a href="'. route('salesOrder.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="check"></i>
                                    <span>'. __("Approve") .'</span>
                                </a>';
            }

            // if (Auth::user()->can('salesOrder-edit') and ($data->status == 1 or $data->status == 2)) {
            if (Auth::user()->can('salesOrder-edit') and ($data->status == 1)) {
            $buttons .=         '<a href="'. route('salesOrder.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    <span>'. __("Edit") .'</span>
                                </a>';
            }

            // if (($data->status == '2') || ($data->status == '3') ){
            //     if (Auth::user()->can('salesOrder-revision')) {
            //         $buttons .=     "<a href='javascript:;'
            //                             id='revisionReasonButton'
            //                             class='dropdown-item'
            //                             data-toggle='modal'
            //                             data-target='#reasonModalRevision'
            //                             data-href='". route("salesOrder.revision", ["id"=>Crypt::encryptString($data->id),"nR"=>$data->num_revision]) ."'>
            //                             <i data-feather='corner-down-left' class='feather-14-red'></i>
            //                             <span>". __('Revision') ."</span>
            //                         </a>";
            //     }
            // }
            
            $buttons .=         '<a href="'. route('salesOrder.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    <span>'. __("Detail") .'</span>
                                </a>';

            if ( $data->status == 3){
            $buttons .=         '<a href="'. route('salesOrder.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                <i data-feather="printer"></i>
                                    <span>'. __("Print") .'</span>
                                </a>';
            }
            if (Auth::user()->can('salesOrder-delete') and  ($data->status == 1 or $data->status == 2 or $data->status == 3)) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("salesOrder.destroy", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='trash-2' class='feather-14-red'></i>
                                    <span>". __('Delete') ."</span>
                                </a>";
            }
            if (Auth::user()->can('salesOrder-delete') ) {

                $buttons .=     '<a href="'. route('salesOrder.close', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="x-circle"></i>
                                    <span>'. __("Close") .'</span>
                                </a>';
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('so_code', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            return '<span style="display: none;">'.$data->so_code.'</span><a class="badge d-block '.$badges[$data->status - 1].'" href="'. route('salesOrder.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->so_code.'</span></a>';
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusSo[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','so_code'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchOrder = strtolower($request->searchOrder);
        $seachPo = strtolower($request->seachPo);
        $searchCustomer = $request->searchCustomer;
        $searchSalesman = $request->searchSalesman;
        $searchType = $request->searchType;
        $searchStatus = $request->searchStatus;
        $orderDate = $request->orderDate;
        $fromDate = "";
        $toDate = "";

        if ($orderDate){
            $date = explode("to",$orderDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }      

        $data = DB::table('sales_order_det')
        ->leftJoin('sales_order_hdr','sales_order_hdr.so_code','sales_order_det.so_code')
        ->leftJoin('third_party','third_party.kode','sales_order_hdr.customer_id')
        ->leftJoin('article','article.article_code','sales_order_det.article_code')
        ->leftJoin('uom','uom.code','sales_order_det.uom')
        ->leftJoin('employees','employees.employee_id','sales_order_hdr.salesman_code')
        ->where(function ($query) use ($seachPo,$searchOrder,$searchCustomer,$searchSalesman,$searchType,$searchStatus,$fromDate,$toDate) {
            $seachPo ? $query->where('po_number','ilike','%'.$seachPo.'%') :'';
            $searchOrder ? $query->where('sales_order_hdr.so_code','ilike','%'.$searchOrder.'%') :'';
            $searchCustomer ? $query->where('customer_id',$searchCustomer) :'';
            $searchSalesman ? $query->where('salesman_code',$searchSalesman) :'';
            $searchType ? $query->where('order_type',$searchType) :'';
            $searchStatus ? $query->where('status',$searchStatus) :'';
            $fromDate ? $query->whereBetween(DB::raw("to_date(so_date,'DD-MM-YYYY')"), [$fromDate, $toDate]):'';
        })
        ->select('sales_order_det.*'
        ,'sales_order_hdr.*'
        ,'sales_order_hdr.so_code as so_code_1'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'third_party.nama as customer'
        ,'sales_order_det.ppn as ppn_price'
        ,'employees.name as salesman'
        // ,'uom_group'
        // ,'qty_target'
        // ,'qty_forcast'
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_target,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_target,'999,999,999.999') end as qty_target")
        //,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_forcast,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_forcast,'999,999,999.999') end as qty_forcast")
        )
        ->orderBy('sales_order_det.id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID'];
            return $statusSo[$data->status - 1];
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function print(Request $request)
    {
        
        $id=Crypt::decryptString($request->id);

        $company=DB::table('company')
        ->where('code','ASN')
        ->first();

        $data['companies']= array(
            "nama"=> $company -> name,
            "alamat"=> $company -> address,
            "kota" => "KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT",
            "tlp" =>  ""
        );
                
        $soHdr=DB::table('sales_order_hdr')
        ->where('id',$id)
        ->first();

        // $supplier=DB::table('third_party')
        // ->where('kode',$soHdr -> customer_id)
        // ->first();

        // $data['suppliers']=array(
        //     'nama'=>$supplier -> nama,
        //     'alamat'=>$supplier -> alamat_tagih,
        //     'kota' =>'KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT',
        //     'tlp' => $supplier -> hp
        // );

        $soNumber=$soHdr -> so_code;
       
        $data['details']=DB::table('sales_order_det')
        ->leftJoin('article','article.article_code','sales_order_det.article_code')
        ->where('so_code',$soNumber)
        ->get();

        $data['totals']=DB::select("SELECT *,(total_material+total_service) as sub_total,((total_material+total_service+ppn)-pph23) as grand_total from (
            select 
            a.so_code,
            sum(qty) as qty,
            -- sum(qty*price) + sum(qty*price_service) as gross,
            sum(qty*price) as total_material,
            sum(qty*price_service) as total_service,
            sum(a.ppn) as ppn,
            sum(a.pph23) as pph23 
            from sales_order_det a
            left join sales_order_hdr b
            on a.so_code = b.so_code 
            where a.so_code = '$soNumber'
            group by a.so_code) as oki");

        $data['customers']=DB::table('third_party')
        ->where('kode',$soHdr -> customer_id)
        ->first();

        $data['keterangan']= $soHdr -> note;
        $data['soNumber'] = $soNumber;
        $data['soDate'] = $soHdr -> so_date; 
        $data['soSalesman'] = $soHdr -> salesman_code; 
        $data['soCurrency'] = $soHdr -> currency; 
        $data['soPoNumber'] = $soHdr -> po_number; 
        
        $statusSo = ['New','Validated','Approved','Received','Canceled','Closed','Paid'];

        $data['status'] = $statusSo[$soHdr->status - 1];
        $data['no'] = 0;

        view()->share($data);

        $pdf = PDF::loadView('salesOrder.print');
        return $pdf->stream("SO_$soNumber.pdf");

    }

}
