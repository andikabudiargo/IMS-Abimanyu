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
use App\Http\Controllers\AttributeController as Attributes;

class SalesOrderController extends Controller
{
    private $title;
    private $moduleCode;
    private $lockDate;
    private $lockDateIndex;
    private $nilaiPpn;
    private $ppnPenyebut;
    private $ppnPembilang;

    public function __construct()
    {
        $this->title = "Sales Order";
        $this->moduleCode = "SO";
        $lockDate1 = DB::table('application_lock')
        ->where('code_key',$this->moduleCode)
        ->where('status','1')
        ->value('lock_date');

        $todayDate = date('d-m-Y');
        $lockDateHere = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        $lockDateAt = date('d-m-Y', strtotime("+1 day", strtotime($lockDateHere)));

        if ($todayDate < $lockDateAt ){
            $firstDatePrevMonth = date('1-m-Y', strtotime("-1 months",strtotime($lockDateHere)));
            $lockDateAt = $firstDatePrevMonth;
        }else{
            $lockDateAt = date('1-m-Y', strtotime($lockDateAt));
        }

        $this->lockDate = $lockDateAt;

        $lockDateHereIndex = $lockDate1 ? $lockDate1 : '2023-01-01' ;
        $lockDateAtIndex = date('d-m-Y', strtotime($lockDateHere));
        $this->lockDateIndex = $lockDateAtIndex;

        $this->nilaiPpn  = Attributes::getLastPpn()['ppnValue'];
        $this->ppnPembilang = Attributes::getLastPpn()['pembilang'];
        $this->ppnPenyebut = Attributes::getLastPpn()['penyebut'];
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
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Num Revision'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created At','visible'=>false],
            // ['data'=>'detail','name'=>'detail','title'=>'Detail'],

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
            ['data'=>'statusKu','name'=>'statusKu','title'=>'Status'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
            ['data'=>'tanggal_so','name'=>'tanggal_so','title'=>'Tanggal SO', 'visible'=>false],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        $data['kolomDetailDn'] = $this->getTableColoumnDetailDn();

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['employees'] = DB::table('employees')
        ->where('job_position','05')
        ->get();

        $data['types'] = ['NEW','REPEAT'];
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','6'=>"CLOSED",'7'=>'PAID'];

        $data['lockDate'] = $this->lockDateIndex;

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

        // $ppnDate = date('d-m-Y');
        $data['ppnValue']  = $this->nilaiPpn;
        $data['ppnPenyebut'] = $this->ppnPenyebut;
        $data['ppnPembilang'] = $this->ppnPembilang; 

        $data['lockDate'] = $this->lockDateIndex;

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

        /*
            12/10/2023
            Ibu Natasya
            Permintaan update untuk price pakai 2 digit koma

        */
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $orderDate = $request->orderDate;
        $currency = $request->currency;
        $type = $request->type;
        $poNumber = $request->poNumber;
        $customer = $request->customer;
        $salesman = $request->salesman;
        // $ppn = $request->ppn;
        // $pph23 = $request->pph23;
        // $totalPpn = $request->totalPpn;
        // $totalPph = $request->totalPph;
        $ppn = is_null($request->ppn) ? 0 : preg_replace('/[^0-9.]+/', '', $request->ppn);
        $pph23 = is_null($request->pph23) ? 0 : preg_replace('/[^0-9.]+/', '', $request->pph23);
        $totalPpn = is_null($request->totalPpn) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPpn);
        $totalPph = is_null($request->totalPph) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPph);

        $note = $request->note;
        $status = '1';
        $gudang = 'false';
        $kurs = 1;

        $dppLainValue=is_null($request->totalDppNilaiLain) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalDppNilaiLain);
        $dppPembilang = $request->pembilangNumber;
        $dppPenyebut = $request->penyebutNumber;

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
            $hasilUpdate = AppHelpers::resetCode('SO');
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
                    'updated_at' => date('Y-m-d H:i:s'),
                    'origin_so_code' => $soCode,
                    'dpp_lain_value' => $dppLainValue,
                    'dpp_lain_pembilang' => $dppPembilang,
                    'dpp_lain_penyebut' => $dppPenyebut
                ]);

                $dppLainBagi = $dppLainValue ? $dppPembilang/$dppPenyebut : 1;

                $dataSet = [];
                $ppnFinal = '';
                foreach ($articles as $val) {                    
                    if($dppLainValue){
                        $ppnFinal =  (($val->price*$val->qty)+($val->price_service*$val->qty)*$dppLainBagi) * $ppn/100;
                    }else{
                        $ppnFinal =  (($val->price*$val->qty)+($val->price_service*$val->qty)) * $ppn/100;
                    }
                    $dataSet[] = [
                        'so_code' => $soCode,
                        'article_code' => $val->article_code,
                        'qty' => $val->qty,
                        'uom' => $val->uom,
                        'price' => $val->price,
                        'price_service' => $val->price_service,
                        'ppn' => round($ppnFinal),
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

        $data['headers'] = DB::table('sales_order_hdr')
        ->select('sales_order_hdr.*'
        ,DB::raw("(select concat(kode,' - ',nama) from third_party where kode = sales_order_hdr.customer_id) as supp_name") 
        ,DB::raw('(select sum(qty) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_qty') 
        ,DB::raw('(select count(*) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_row')
        ,DB::raw('(select (sum((qty*price) + (qty*price_service))) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_amount')
        ,DB::raw('(select (sum(((qty*price) + (qty*price_service))*sales_order_hdr.ppn/100)) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_ppn')
        ,DB::raw('(select (sum(((qty*price_service))*sales_order_hdr.pph23/100)) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_pph23')
        )
        ->where('origin_so_code', function($query) use ($id){
            $query->select('so_code')->from('sales_order_hdr')->where('id',$id);
        })
        ->where('status','<>','5')
        ->orderBy('id')
        ->get();

        $soCode = $data['headers'][0]->so_code;

        $data['details'] = DB::table('sales_order_det')
        ->leftJoin('article','article.article_code','=','sales_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','sales_order_det.article_code')
        ->leftJoin('uom','uom.code','=','sales_order_det.uom')
        ->whereIn('sales_order_det.so_code', function($query) use ($soCode){
            $query->select('so_code')->from('sales_order_hdr')->where('origin_so_code',$soCode);
        })
        ->select('sales_order_det'.'.*'
        ,'sales_order_det.status as status_detail'
        // ,DB::raw('round(sales_order_det.qty) as qty')
        ,DB::raw('sales_order_det.qty as qty')
        ,'article_stock.article_qty as qty_stock'
        ,'uom.uom_group'
        , DB::raw('(SELECT name from group_materials where code = group_of_material) as group')
        , DB::raw("concat(article_alternative_code,'-',article_desc) as article")
        , DB::raw("(select STRING_AGG( (qty::real)::text,' -> ' ORDER BY so_code) AS main from sales_order_det p where article_code = sales_order_det.article_code and so_code like '$soCode%' ) as notes")
        )
        ->orderBy('id')
        ->get();

        // $data['header'] = DB::table('sales_order_hdr')
        // ->select('sales_order_hdr.*'
        // ,DB::raw("(select concat(kode,' - ',nama) from third_party where kode = sales_order_hdr.customer_id) as supp_name") 
        // ,DB::raw('(select sum(qty) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_qty') 
        // ,DB::raw('(select count(*) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_row')
        // ,DB::raw('(select (sum((qty*price) + (qty*price_service))) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_amount')
        // ,DB::raw('(select (sum(((qty*price) + (qty*price_service))*sales_order_hdr.ppn/100)) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_ppn')
        // ,DB::raw('(select (sum(((qty*price_service))*sales_order_hdr.pph23/100)) from sales_order_det where so_code = sales_order_hdr.so_code) as sum_pph23')
        // )
        // ->where('id',$id)
        // ->get()->first();

        // $soCode = $data['header']->so_code;

        // $data['detail'] = DB::table('sales_order_det')
        // ->leftJoin('article','article.article_code','=','sales_order_det.article_code')
        // ->leftJoin('article_stock','article_stock.article_code','=','sales_order_det.article_code')
        // ->leftJoin('uom','uom.code','=','sales_order_det.uom')
        // ->where('so_code',$soCode)
        // ->select('sales_order_det'.'.*'
        // ,DB::raw('round(sales_order_det.qty) as qty')
        // ,'article_stock.article_qty as qty_stock'
        // ,'uom.uom_group'
        // , DB::raw('(SELECT name from group_materials where code = group_of_material) as group')
        // , DB::raw("concat(article_alternative_code,'-',article_desc) as article")
        // )
        // ->orderBy('id')
        // ->get();

        // dd($data['detail']);

        // $data['articles']= DB::table('article') 
        // ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
        // ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        // ->where('third_party',$data['header']->customer_id)
        // ->orderBy('article_desc')
        // ->select('article'.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group')
        // ->get();   

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

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSED",'7'=>'PAID','7'=>'REVISED'];
        $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID','REVISED'];
        $data['statusSo'] = $statusSo[$data['headers'][0]->status-1];

        $data['ppnPembilang'] = $data['headers'][0]->dpp_lain_pembilang;
        $data['ppnPenyebut'] = $data['headers'][0]->dpp_lain_penyebut;


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
        ->select('sales_order_det'.'.*'
        // ,DB::raw('round(sales_order_det.qty) as qty')
        ,DB::raw('sales_order_det.qty as qty')
        ,'article_stock.article_qty as qty_stock'
        , DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();       

        $customerId=$data['header']->customer_id;

        $data['articles']= DB::table('article') 
        ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->whereIn('article.article_code', function($query) use ($customerId){
            $query->select('article_supplier.article_code')->from('article_supplier')->where('article_supplier.supplier_code', $customerId);
        })
        // ->where('third_party',$data['header']->customer_id)
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

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSED",'7'=>'PAID','8'=>'REVISED'];
        $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID','REVISED'];
        $data['statusSo'] = $statusSo[$data['header']->status-1];

        $data['lockDate'] = $this->lockDateIndex;

        $data['ppnPenyebut'] = $data['header']->dpp_lain_penyebut;
        $data['ppnPembilang'] = $data['header']->dpp_lain_pembilang; 

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

        $customerId= $data['header']->customer_id;
        $data['articles']= DB::table('article') 
        ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->whereIn('article.article_code', function($query) use ($customerId){
            $query->select('article_supplier.article_code')->from('article_supplier')->where('article_supplier.supplier_code', $customerId);
        })
        //->where('third_party',$data['header']->customer_id)
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

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSED",'7'=>'PAID','8'=>'REVISED'];
        $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID','REVISED'];
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
        // $ppn = $request->ppn;
        // $pph23 = $request->pph23;
        // $totalPpn = $request->totalPpn;
        // $totalPph = $request->totalPph;

        $ppn = is_null($request->ppn) ? 0 : preg_replace('/[^0-9.]+/', '', $request->ppn);
        $pph23 = is_null($request->pph23) ? 0 : preg_replace('/[^0-9.]+/', '', $request->pph23);
        $totalPpn = is_null($request->totalPpn) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPpn);
        $totalPph = is_null($request->totalPph) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalPph);

        $note = $request->note;
        $gudang = 'false';
        $kurs = 1;
        $modulCode = $this->moduleCode;
        $approveLevel = $request->approveLevel;
        $statusSimpan = $request->statusSimpan;

        $dppLainValue=is_null($request->totalDppNilaiLain) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalDppNilaiLain);
        $dppPembilang = $request->pembilangNumber;
        $dppPenyebut = $request->penyebutNumber;

        // status:
        // 1 = New
        // 2 = Updated
        // 3 = Approved
        // 4 = Received
        // 5 = Canceled
        // 6 = Closed
        // 7 = Paid

        //satus simpan belum tahu untuk apa
        if($statusSimpan == 'approve'){
            $maxApproval = DB::table('approval_master')
            ->where('module_code',$modulCode)
            ->value('approval_number');
            $status = $maxApproval == $approveLevel ? '3': $status = '2';
        }else{
            $dataSo = DB::table('sales_order_hdr')
            ->where('so_code',$orderNumber)
            ->get()->first();

            $status = $dataSo -> status;
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
                        'updated_at' => date('Y-m-d H:i:s'),
                        'dpp_lain_value' => $dppLainValue,
                        'dpp_lain_pembilang' => $dppPembilang,
                        'dpp_lain_penyebut' => $dppPenyebut
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
                
                $dppLainBagi = $dppLainValue ? $dppPembilang/$dppPenyebut : 1;

                DB::table('sales_order_det')
                    ->whereNotIn(DB::raw("CONCAT(so_code,article_code)"),$dataSet)
                    ->where('so_code',$orderNumber)
                    ->delete();

                foreach ($articles as $val) {

                    if($dppLainValue){
                        $ppnFinal =  (($val->price*$val->qty)+($val->price_service*$val->qty)*$dppLainBagi) * $ppn/100;
                    }else{
                        $ppnFinal =  (($val->price*$val->qty)+($val->price_service*$val->qty)) * $ppn/100;
                    }

                    DB::table('sales_order_det')
                    ->updateOrInsert(
                        ['so_code' => $orderNumber,'article_code' => $val->article_code],
                        [
                        'article_code' => $val->article_code,
                        'qty' => $val->qty,
                        'uom' => $val->uom,
                        'price' => $val->price,
                        'price_service' => $val->price_service,
                        'ppn' => round($ppnFinal),
                        'pph23' => ($val->price_service*$val->qty) * $pph23/100,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                }

                /*
                    Kalau ada penambahan article, status nya akan kosong, jadi harus di update statusnya jadi open (1)
                */

                DB::table('sales_order_det')
                ->where('so_code',$orderNumber)
                ->where('status',null)
                ->update(
                    [
                        'status' => "1",
                        'created_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s')                    
                    ]
                );

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

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $soCode = $request->soCode;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$soCode,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusSo = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';

        // $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID'];
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('sales_order_hdr')
                ->where('so_code',$soCode)
                ->update(
                    [
                        'status' => $statusSo,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $soCode,
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
                $message  = "$title $soCode is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusSo' => $statusSo,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'soCode'=>$soCode));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $soCode is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusSo' => $statusSo,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'soCode'=>$soCode));
        }
    }

    public function updateClose(Request $request)
    {
        $username =  Auth::user()->username;
        $orderNumber = $request->orderNumber;
        $articles = json_decode($request -> articles);

        /*
            status : 
            1 : Open
            0 : Closed
        */

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
        $so_code = DB::table('sales_order_hdr')->where('id',$id)->value('so_code');
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
            'sales_order_hdr.id'
            ,'sales_order_hdr.so_code'
            ,'sales_order_hdr.po_number'
            ,'sales_order_hdr.customer_id'
            ,'sales_order_hdr.salesman_code'
            ,'sales_order_hdr.so_date'
            ,'sales_order_hdr.order_type'
            ,'sales_order_hdr.status'
            ,'sales_order_hdr.note'
            ,'sales_order_hdr.num_revision'
            ,'sales_order_hdr.created_at'
            // 'sales_order_hdr.*'
            ,'sales_order_hdr.so_code as so_code_1'
            ,'third_party.nama'
            ,'third_party.nama as cust_name'

         )
        ->leftJoin('third_party', 'third_party.kode', '=', 'sales_order_hdr.customer_id')
        ->whereNotIn('sales_order_hdr.status',['5','8'])
        ->where(function ($query) use ($seachPo,$searchOrder,$searchCustomer,$searchSalesman,$searchType,$searchStatus,$fromDate,$toDate) {
            $seachPo ? $query->where('po_number','ilike','%'.$seachPo.'%') :'';
            $searchOrder ? $query->where('so_code','ilike','%'.$searchOrder.'%') :'';
            $searchCustomer ? $query->where('customer_id',$searchCustomer) :'';
            $searchSalesman ? $query->where('salesman_code',$searchSalesman) :'';
            $searchType ? $query->where('order_type',$searchType) :'';
            $searchStatus ? $query->where('sales_order_hdr.status',$searchStatus) :'';
            $fromDate ? $query->whereBetween(DB::raw("to_date(so_date,'DD-MM-YYYY')"), [$fromDate, $toDate]):'';
        })->get();

        $lockDateToDate = date('Y-m-d',strtotime($this->lockDate));
        $bisaEdit = Auth::user()->can('salesOrder-edit');
        $bisaDelete = Auth::user()->can('salesOrder-delete');

        return Datatables::of($data)
        ->addColumn('action', function ($data) use($lockDateToDate,$bisaDelete,$bisaEdit) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            // if (Auth::user()->can('salesOrder-edit') and ($data->status == 1 or $data->status == 2)) {
            if ($bisaEdit and ($data->status == 1 or $data->status == 2)) {
            $buttons .=         '<a href="'. route('salesOrder.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="check"></i>
                                    <span>'. __("Approve") .'</span>
                                </a>';
            }

            // if (Auth::user()->can('salesOrder-edit') and ($data->status == 1 or $data->status == 2)) {
            // if (Auth::user()->can('salesOrder-edit') and ($data->status == 1)) {
            //dibukain dulu agar bisa di edit walaupun belum apporoved
            // if (Auth::user()->can('salesOrder-edit') and ($data->status == 1 or $data->status== 2)) {
            if ($bisaEdit and ($data->status == 1 or $data->status== 2)) {
                $soDate = date('Y-m-d', strtotime($data->so_date));
                if($soDate>$lockDateToDate){
                    $buttons .=         '<a href="'. route('salesOrder.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                            <i data-feather="file-text"></i>
                                            <span>'. __("Edit") .'</span>
                                        </a>';
                }
            }

            if (($data->status == '2') || ($data->status == '3') ){
                // if (Auth::user()->can('salesOrder-revision')) {
                $soDate = date('Y-m-d', strtotime($data->so_date));
                if($soDate>$lockDateToDate){
                    $buttons .=     "<a href='javascript:;'
                                        id='revisionReasonButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#reasonModalRevision'
                                        data-href='". route("salesOrder.revision", ["id"=>Crypt::encryptString($data->id),"nR"=>$data->num_revision]) ."'>
                                        <i data-feather='corner-down-left' class='feather-14-red'></i>
                                        <span>". __('Revision') ."</span>
                                    </a>";
                }
                // }
            }
            
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

            // if (Auth::user()->can('salesOrder-delete') and  ($data->status == 1 or $data->status == 2 or $data->status == 3)) {
            if ($bisaDelete and  ($data->status == 1 or $data->status == 2 or $data->status == 3)) {
                $soDate = date('Y-m-d', strtotime($data->so_date));
                if($soDate>$lockDateToDate){
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
            }
            if ($bisaDelete) {
                $buttons .=     '<a href="'. route('salesOrder.close', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="x-circle"></i>
                                    <span>'. __("Close") .'</span>
                                </a>';
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        // ->addColumn('so_code', function ($data) {
        //     $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
        //     return '<span style="display: none;">'.$data->so_code.'</span><a class="badge d-block '.$badges[$data->status - 1].'" href="'. route('salesOrder.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->so_code.'</span></a>';
        // })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-secondary'];
            $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID','REVISED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusSo[$data->status - 1]."</div>";
        })

        // ->addColumn('detail', function ($data) {
        //     if($data->qty_kirim <> 0){
        //         return '<a href="javascript:void(0);" onclick="detailDelivery(\''.$data->article_code.'\',\''.$data->so_code.'\',\''.preg_replace("/\"/"," ",$data->article_desc).'\')">
        //         <span>Detail</span>
        //         </a>';
        //     }else{
        //         return '';
        //     }
        // })

        ->rawColumns(['action','status','so_code','detail'])
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
            $searchStatus ? $query->where('sales_order_hdr.status',$searchStatus) :'';
            $fromDate ? $query->whereBetween(DB::raw("to_date(so_date,'DD-MM-YYYY')"), [$fromDate, $toDate]):'';
        })
        ->where('sales_order_hdr.so_code','<>',null)
        ->whereNotIn('sales_order_hdr.status',['5','8'])
        ->select(
        // 'sales_order_det.*'
        // ,'sales_order_hdr.*'
        'sales_order_det.qty'
        ,'sales_order_det.uom'
        ,'sales_order_det.price'
        ,'sales_order_det.price_service'
        ,'sales_order_hdr.so_code as so_code_1'
        ,'sales_order_hdr.po_number'
        ,'sales_order_hdr.so_date'
        ,'sales_order_hdr.ppn'
        ,'sales_order_hdr.order_type'
        ,'sales_order_hdr.created_by'
        ,'sales_order_hdr.created_at'
        ,'sales_order_hdr.updated_by'
        ,'sales_order_hdr.updated_at'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'third_party.nama as customer'
        ,'sales_order_det.ppn as ppn_price'
        ,'employees.name as salesman'
        ,'sales_order_det.id as id_det'
        ,'sales_order_hdr.status as statusKu'
        ,db::raw("to_date(sales_order_hdr.so_date,'dd-mm-yyyy') as tanggal_so")
        // ,'uom_group'
        // ,'qty_target'
        // ,'qty_forcast'
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_target,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_target,'999,999,999.999') end as qty_target")
        //,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_forcast,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_forcast,'999,999,999.999') end as qty_forcast")
        )
        ->orderBy('sales_order_det.id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('statusKu', function ($data) {
            if($data->statusKu){
                $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID','REVISED'];
                return $statusSo[$data->statusKu - 1];
            }else{
                return "No Status";
            }
        })
        ->rawColumns(['statusKu'])
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
        ->orderBy('sales_order_det.id')
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
        $data['soNote'] = $soHdr -> note; 
        
        // $statusSo = ['New','Validated','Approved','Received','Canceled','Closed','Paid'];
        $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID','REVISED'];

        $data['approval1']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$soNumber)
        ->where('approval_order',1)
        ->first();

        $data['approval2']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$soNumber)
        ->where('approval_order',2)
        ->first();

        $data['approval3']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$soNumber)
        ->where('approval_order',3)
        ->first();

        $data['approval4']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$soNumber)
        ->where('approval_order',4)
        ->first();

        $data['status'] = $statusSo[$soHdr->status - 1];
        $data['no'] = 0;

        view()->share($data);

        $pdf = PDF::loadView('salesOrder.print');
        return $pdf->stream("SO_$soNumber.pdf");

    }

    public function getTableColoumnReport(){
        $kolom=
        [
            ['data'=>'customer_code','name'=>'customer_code','title'=>'Customer Code'],
            ['data'=>'customer','name'=>'customer','title'=>'Customer'],
            ['data'=>'po_number','name'=>'po_number','title'=>'No PO'],
            ['data'=>'so_code','name'=>'so_code','title'=>'No SO'],
            ['data'=>'so_date','name'=>'so_date','title'=>'Tanggal SO'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty SO'],
            ['data'=>'qty_kirim','name'=>'qty_kirim','title'=>'Pengiriman'],
            ['data'=>'balance','name'=>'balance','title'=>'Sisa Order'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'date_period','name'=>'date_period','title'=>'date_period','visible'=>false],
            ['data'=>'detail','name'=>'detail','title'=>'Detail'],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetailDn(){
        $kolom=    
        [
            ['data'=>'delivery_number','name'=>'delivery_number','title'=>'Delivery Number'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'qty','name'=>'qty','title'=>'QTY'],
            ['data'=>'statusku','name'=>'statusku','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note']
        ];
        return json_encode($kolom, true);
    }

    public function report(Request $request)
    {
        $data['title'] = "$this->title Report";
        $data['kolom'] = $this->getTableColoumnReport();
        $data['kolomDetailDn'] = $this->getTableColoumnDetailDn();
        

        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("salesOrder.report",$data);
    }

    public function listReport(Request $request)
    {
        $searchOrder = $request->searchOrder;
        $seachPo = strtolower($request->seachPo);
        $searchCustomer = $request->searchCustomer;
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
        ->where(function ($query) use ($seachPo,$searchOrder,$searchCustomer,$fromDate,$toDate) {
            $seachPo ? $query->where('po_number','ilike','%'.$seachPo.'%') :'';
            $searchOrder ? $query->whereIn('sales_order_hdr.so_code',$searchOrder) : '';
            $searchCustomer ? $query->where('customer_id',$searchCustomer) :'';
            $fromDate ? $query->whereBetween(DB::raw("to_date(so_date,'DD-MM-YYYY')"), [$fromDate, $toDate]):'';
        })
        ->where('sales_order_hdr.so_code','<>',null)
        ->whereNotIn('sales_order_hdr.status',['5','8'])
        ->where('sales_order_det.status',['1'])
        ->select('sales_order_det.*'
        ,'sales_order_hdr.po_number'
        ,'sales_order_hdr.status as statusku'
        ,'sales_order_hdr.so_code'
        ,'sales_order_hdr.so_date'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'third_party.kode as customer_code'
        ,'third_party.nama as customer'
        ,'sales_order_det.ppn as ppn_price'
        ,'sales_order_det.id as id_det'
        ,db::raw("(select sum(qty) from delivery_det a
        left join delivery_hdr b on a.delivery_number=b.delivery_number 
        where a.so_number = sales_order_hdr.so_code and a.article_code = sales_order_det.article_code 
        and b.status not in ('5','7')  group by article_code) as qty_kirim")
        // ,db::raw("(coalesce((select sum(qty) from delivery_det a
        // left join delivery_hdr b on a.delivery_number=b.delivery_number 
        // where a.so_number = sales_order_hdr.so_code and a.article_code = sales_order_det.article_code 
        // and status <> '5' group by article_code),0)-sales_order_det.qty) as balance")
        ,db::raw("case when sales_order_det.status = '0' then 0 else (coalesce((select sum(qty) from delivery_det a
        left join delivery_hdr b on a.delivery_number=b.delivery_number 
        where a.so_number = sales_order_hdr.so_code and a.article_code = sales_order_det.article_code 
        and b.status not in ('5','7')  group by article_code),0)-sales_order_det.qty) end as balance")
        // ,'sales_order_hdr.status as statusKu'
        // ,'uom_group'
        // ,'qty_target'
        // ,'qty_forcast'
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_target,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_target,'999,999,999.999') end as qty_target")
        //,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_forcast,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_forcast,'999,999,999.999') end as qty_forcast")
        ,DB::RAW("to_date(so_date,'dd-mm-yyyy') as date_period")
        ,'sales_order_hdr.note'
        )
        ->where(db::raw("case when sales_order_det.status = '0' then 0 else (coalesce((select sum(qty) from delivery_det a
        left join delivery_hdr b on a.delivery_number=b.delivery_number 
        where a.so_number = sales_order_hdr.so_code and a.article_code = sales_order_det.article_code 
        and b.status not in ('5','7')  group by article_code),0)-sales_order_det.qty) end"),'!=',0)
        ->where('article.article_desc',"<>",'')
        ->orderBy('sales_order_det.id')
        ->get(); 
    
        return Datatables::of($data)
        ->addColumn('detail', function ($data) {
            if($data->qty_kirim <> 0){
                return '<a href="javascript:void(0);" onclick="detailDelivery(\''.$data->article_code.'\',\''.$data->so_code.'\',\''.preg_replace("/\"/"," ",$data->article_desc).'\')">
                <span>Detail</span>
                </a>';
            }else{
                return '';
            }
        })
        ->rawColumns(['detail'])
        ->make(true);
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $soOrigin=DB::table('sales_order_hdr')->where('id',$id)->value('so_code');
        $numRevision = $request->nR ? $request->nR +1 : 1 ;
        $soNew = $soOrigin.'-R'.$numRevision;
        $checkNewSo=DB::table('sales_order_hdr')->where('so_code',$soNew)->count();
        $reasonRequest = $request->reason;
        $reason = $reasonRequest;

        if ($checkNewSo > 0){
            $soNew = $soOrigin.'-R'.$numRevision+1;
        }        
                
        $sqlHdr = "INSERT into sales_order_hdr 
        (
            so_code,
            origin_so_code,
            po_number,
            customer_id,
            salesman_code,
            so_date,
            currency,
            kurs,
            ppn,
            pph23,
            order_type,
            status,
            gudang,
            note,
            created_by,
            updated_by,
            created_at,
            updated_at,
            num_revision,
            revised_by,
            revised_at,
            reason,
            dpp_lain_value,
            dpp_lain_pembilang,
            dpp_lain_penyebut
        )
        select 
            '$soNew',
            '$soOrigin',
            po_number,
            customer_id,
            salesman_code,
            so_date,
            currency,
            kurs,
            ppn,
            pph23,
            order_type,
            '8',
            gudang,
            note,
            created_by,
            '$username',
            created_at,
            '".date('Y-m-d H:i:s')."',
            $numRevision,
            '$username',
            '".date('Y-m-d H:i:s')."',
            '$reasonRequest',
            dpp_lain_value,
            dpp_lain_pembilang,
            dpp_lain_penyebut
        from sales_order_hdr where so_code = '$soOrigin'";

        $sqlDet="INSERT into sales_order_det
        (
            so_code,
            article_code,
            qty,
            uom,
            price,
            ppn,
            pph23,
            created_by,
            updated_by,
            created_at,
            updated_at,
            price_service,
            status
        )
        select '$soNew',
            article_code,
            qty,
            uom,
            price,
            ppn,
            pph23,
            created_by,
            '$username',
            created_at,
            '".date('Y-m-d H:i:s')."',
            price_service,
            status
        from sales_order_det where so_code = '$soOrigin'";

        $rowAffected =  DB::select($sqlHdr);
        if ($rowAffected){
            DB::select($sqlDet);
                           
            // $statusSo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','PAID','REVISED'];
            // $statusSo = ['1'=>'NEW','2'=>'VALIDATED','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'PAID','8'=>'REVISED'];

            DB::table('sales_order_hdr')
            ->where('so_code',$soOrigin)
            ->update(
                [
                    'num_revision' => $numRevision,
                    'status' => '1',
                    'revised_by'=>Auth::user()->username,
                    'revised_at'=> date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );

            DB::table('approval_history')
            ->where('module_number',$soOrigin)
            ->update(
                [
                    'module_number' => $soNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision SO: $soOrigin to $soNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->route('salesOrder.edit', ['id'=>Crypt::encryptString($id)]);
            // return $this->showEdit(Crypt::encryptString($id));
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision SO: $soOrigin to $soNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function listReportDetailDn(Request $request)
    {
        $artCode = $request->artCode;
        $soNumber = $request->soNumber;

       $data = DB::table('delivery_det')
        ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','delivery_det.delivery_number')
        ->where('delivery_hdr.so_number',$soNumber)
        ->where('delivery_det.article_code',$artCode)
        ->whereNotIn('delivery_hdr.status',['5','7'])
        ->select('delivery_det.*','delivery_hdr.status as statusku','delivery_hdr.delivery_date','delivery_hdr.note')
        ->orderBy('delivery_det.id')
        ->get(); 
    
        return Datatables::of($data)
        ->addColumn('statusku', function ($data) {
            $statusDel = ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','','RECEIVED','','REVISI'];
            return $statusDel[$data->statusku - 1];
        })

        ->rawColumns(['statusku'])
        ->make(true);
    }

    
}
