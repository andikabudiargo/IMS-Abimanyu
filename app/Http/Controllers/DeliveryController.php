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
use App\Exports\ReportDnExport;
use Maatwebsite\Excel\Facades\Excel;

class DeliveryController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Delivery";
        $this->moduleCode = "DN";
    }

    public function getTableColoumn()
    {
        $kolom=
        [

            ['data'=> 'action', 'name'=> 'action','title'=>'action', 'orderable'=> false, 'searchable'=> false],
            ['data'=> 'delivery_number', 'name'=> 'delivery_number','title'=>'Delivery Number'],
            ['data'=> 'delivery_number_1', 'name'=> 'delivery_number_1','title'=>'Delivery Number','visible'=>false],
            ['data'=> 'status', 'name'=> 'status','title'=>'Status'],
            ['data'=> 'delivery_date', 'name'=> 'delivery_date','title'=>'Date'],
            ['data'=> 'so_number', 'name'=> 'so_number','title'=>'SO Number'],
            ['data'=> 'po_number', 'name'=> 'po_number','title'=>'PO Number'],
            ['data'=> 'customer_name', 'name'=> 'customer_name','title'=>'Customer'],
            ['data'=> 'num_revision', 'name'=> 'num_revision','title'=>'Revision'],            
            ['data'=> 'note', 'name'=> 'note','title'=>'Note'],
            ['data'=> 'created_by', 'name'=> 'created_by','title'=>'Created By'],
            ['data'=> 'created_at', 'name'=> 'created_at','title'=>'Created At']
            
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'delivery_number','name'=>'delivery_number','title'=>'PR Number'],
            ['data'=>'po_date','name'=>'po_date','title'=>'PO Date'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qtyku','name'=>'qtyku','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'price','name'=>'price','title'=>'Price'],
            ['data'=>'discount','name'=>'discount','title'=>'Discount'],
            ['data'=>'total_ppn','name'=>'total_ppn','title'=>'PPN'],
            ['data'=>'total_pph22','name'=>'total_pph22','title'=>'PPH22'],
            ['data'=>'grand_total','name'=>'grand_total','title'=>'Grand Total'],
            ['data'=>'currency','name'=>'currency','title'=>'Currency'],
            ['data'=>'kurs','name'=>'kurs','title'=>'Kurs'],
            ['data'=>'ppn','name'=>'ppn','title'=>'PPN'],
            ['data'=>'pph22','name'=>'pph22','title'=>'PPH22'],
            ['data'=>'pkp','name'=>'pkp','title'=>'PKP'],
            ['data'=>'termin','name'=>'termin','title'=>'Termin'],
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'supplier_id','name'=>'supplier_id','title'=>'Supplier code'],
            ['data'=>'supp_name','name'=>'supp_name','title'=>'Supplier'],
            ['data'=>'approval_by','name'=>'approval_by','title'=>'Approved By'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','8'=>'RECEIVED'];
        $data['status'] = ['1'=>'NEW','3'=>'APPROVED','4'=>'POSTED','8'=>'RECEIVED','10'=>'REVISI'];
        $data['statusKu'] = '1';
            
        return view("delivery.index",$data);
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
        // $months = ['I', 'II', 'III','IV','V', 'VI', 'VII', 'VIII','IX','X','XI','XII'];
        $months = ['01', '02', '03','04','05', '06', '07', '08','09','10','11','12'];
        $month = $months[date('n')-1];
        $year = date('y');
        $code="$key/ASN/$year/$month/$newCode";
        
        return $code;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        
        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("delivery.create",$data);
    }

    public function soDetail(Request $request)
    {
        $so = $request->value;
        $data = DB::table('sales_order_det as a')
        ->leftJoin('article','article.article_code','=','a.article_code')
        ->leftJoin('sales_order_hdr','sales_order_hdr.so_code','=','a.so_code')
        ->leftJoin('uom','a.uom','uom.code')
        ->select('a.*'
        ,'article.*'
        ,'sales_order_hdr.po_number'
        ,DB::RAW("(coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = a.so_code and status not in ('5','7','10')) and article_code = a.article_code group by article_code),0)) as qty_delivery")
        ,DB::RAW("(a.qty - coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = a.so_code and status not in ('5','7','10')) and article_code = a.article_code group by article_code),0)) as qty_so")
        )
        ->where('a.so_code',$so)
        ->orderBy('a.id')
        ->get();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $dnDate = $request->dnDate;
        $customer = $request->customer;
        $soNumber = $request->soNumber;
        $poNumber = $request->poNumber;
        $note = $request->note;
        $status = '1';
        $gudang = 'false';
        $kurs = 1;

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','8'=>'RECEIVED','10'=>'REVISI'];

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
            // 'dnNumber' => 'required',
            'dnDate'  => 'required',
            'customer'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save  $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode($this->moduleCode);
            $dnCode = $this->getLastCode($this->moduleCode);
            DB::beginTransaction();
            try {
                    $id = DB::table('delivery_hdr')->insertGetId([
                        'delivery_number' => $dnCode,
                        'origin_delivery_number' => $dnCode,
                        'delivery_date' => $dnDate,
                        'customer_id' => $customer,
                        'so_number' => $soNumber,
                        'po_number' => $poNumber,
                        'status' => $status,
                        'note' =>  $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'delivery_number' => $dnCode,
                            'article_code' => $val->article_code,
                            'so_number' => $val->so_number,
                            'po_number' => $val->po_number,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'qty_so'=>$val->qty_so,
                        ];
                    }

                    DB::table('delivery_det')->insert($dataSet);

                    $id = Crypt::encryptString($id);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $dnCode is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnCode,'id'=>$id));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $dnCode is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnCode,'id'=>''));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username; 
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('delivery_hdr')
        ->select('delivery_hdr.*'
        ,DB::raw('(select sum(qty) from delivery_det where delivery_number = delivery_hdr.delivery_number) as sum_qty') 
        ,DB::raw('(select count(*) from delivery_det where delivery_number = delivery_hdr.delivery_number) as sum_row')
        )
        ->where('origin_delivery_number', function($query) use ($id){
            $query->select('delivery_number')->from('delivery_hdr')->where('id',$id);
        })
        ->where('status','<>','5')
        ->orderBy('id')
        ->get();

        $dnNumber = $data['headers'][0]->delivery_number;

        $data['details'] = DB::table('delivery_det')
        ->whereIn('delivery_det.delivery_number', function($query) use ($dnNumber){
            $query->select('delivery_number')->from('delivery_hdr')->where('origin_delivery_number',$dnNumber);
        })
        ->leftJoin('uom','uom.code','=','delivery_det.uom')
        ->leftJoin('article','article.article_code','=','delivery_det.article_code')
        ->select('delivery_det'.'.*'
            ,'uom.uom_group'
            ,DB::raw("concat(article_alternative_code,'-',article_desc) as article")
            ,DB::raw("(select STRING_AGG( (qty::real)::text,' -> ' ORDER BY delivery_number) AS main from delivery_det p where article_code = delivery_det.article_code and delivery_number like '$dnNumber%' ) as notes")
        )
        ->orderBy('delivery_det.id')
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$dnNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$dnNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','8'=>'RECEIVED'];
        $statusDel = ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','REVISED','RECEIVED','','REVISI'];
        $data['statusDel'] = $statusDel[$data['headers'][0]->status-1];

        return view("delivery.show",$data);
        
    }

    public function edit(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);

        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('delivery_hdr')
        ->where('id',$id)
        ->get()->first();

        $dnNumber = $data['header']->delivery_number;
        $soNumber = $data['header']->so_number;

        $data['detailSo'] = DB::table('sales_order_det as a')
        ->leftJoin('article','article.article_code','=','a.article_code')
        ->leftJoin('sales_order_hdr','sales_order_hdr.so_code','=','a.so_code')
        ->leftJoin('uom','a.uom','uom.code')
        ->select('a.*'
        ,'article.*'
        ,'a.so_code as so_number'
        ,'sales_order_hdr.po_number'
        ,DB::RAW("(coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = a.so_code) and article_code = a.article_code group by article_code),0)) as qty_delivery")
        ,DB::RAW("(a.qty - coalesce((select sum(qty) as qty_delivery from delivery_det where delivery_number in (select delivery_number from delivery_hdr where so_number = a.so_code) and article_code = a.article_code group by article_code),0)) as qty_so")
        )
        ->whereNotIn('a.article_code', function($query) use($dnNumber){
            $query->select('article_code')
            ->from('delivery_det')
            ->where('delivery_number',$dnNumber);
        })
        ->where('a.so_code',$soNumber)
        ->orderBy('a.id')
        ->get();

        $data['detail'] = DB::table('delivery_det')
        ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','delivery_det.delivery_number')
        ->leftJoin('article','article.article_code','=','delivery_det.article_code')
        ->leftJoin('uom','delivery_det.uom','uom.code')
        ->select(
            'delivery_det.*'
            ,'article.*'
            ,'uom.*'
            ,DB::RAW("(select sum(qty) from sales_order_det a where a.so_code = delivery_det.so_number and a.article_code = delivery_det.article_code group by a.article_code) - delivery_det.qty as qty_so")
        )
        ->where('delivery_det.delivery_number',$dnNumber)
        ->orderBy('delivery_det.id')
        ->get();

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$dnNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$dnNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','8'=>'RECEIVED'];
        $statusDel = ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','REVISED','RECEIVED','','REVISI'];
        $data['statusDel'] = $statusDel[$data['header']->status-1];

        return view("delivery.edit",$data);        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;       
        // $id=Crypt::decryptString($request->id);
        $articles = json_decode($request -> articles);
        $dnDate=$request->dnDate;
        $customer=$request->customer;
        $soNumber=$request->soNumber;
        $poNumber = $request->poNumber;
        $dnNumber=$request->dnNumber;
        $note=$request->note;
        // $status = '2';
        
        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "Invoice : $dnNumber has already been taken on PO : $poNumber",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            // 'dnNumber'=>'required|iunique:delivery_hdr,inv_number,po_number',
            'dnDate'  => 'required',
            'dnNumber'  => 'required',
            // 'supplier'  => 'required',
        ],$customMessages);
                
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Update  $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {
                    $row_affected=DB::table('delivery_hdr')
                    ->where('delivery_number',$dnNumber)
                    ->update(
                        [   
                        'delivery_date' => $dnDate,
                        'customer_id' => $customer,
                        'so_number' => $soNumber,
                        'po_number' => $poNumber,
                        // 'status' => $status,
                        'note' =>  $note,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $dnNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('delivery_det')
                        ->whereNotIn(DB::raw("CONCAT(delivery_number,article_code)"),$dataSet)
                        ->where('delivery_number',$dnNumber)
                        ->delete();
                                  
                    foreach ($articles as $val) {
                        DB::table('delivery_det')
                        ->updateOrInsert(
                            ['delivery_number' => $dnNumber,'article_code' => $val->article_code],
                            [
                                'delivery_number' => $dnNumber,
                                'article_code' => $val->article_code,
                                'so_number' => $val->so_number,
                                'po_number' => $val->po_number,
                                'qty' => $val->qty,
                                'uom' => $val->uom,
                                'updated_by' => Auth::user()->username,
                                'updated_at' => date('Y-m-d H:i:s'),
                                'qty_so'=>$val->qty_so
                            ]
                        );
                    }
                                                                
                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $dnNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert ="warning";
                $message  = "$title $dnNumber is failed to update";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnNumber));
            }
        }
    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $dnNumber = $request->dnNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$dnNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusDel = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'10';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('delivery_hdr')
                ->where('delivery_number',$dnNumber)
                ->update(
                    [
                        'status' => $statusDel,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $dnNumber,
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
                $message  = "$title $dnNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusDel,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $dnNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusDel,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnNumber));
        }
    }

    public function posting(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','7'=>'REVISED'];
        // $dnNumber = DB::table('delivery_hdr')->where('id',$id)->where('status','=','3')->value('delivery_number');
        // $id = DB::table('delivery_hdr')->where('delivery_number',$dnNumber)->value('id');
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        // dd($request->id);
        // $id=$request->id;
        
        $dnNumber = $request->dnNumber;
        $dnNumber = DB::table('delivery_hdr')->where('id',$id)->value('delivery_number');
        $recType = "NORMAL";
        $siteCode = 'HO';
        $location ='WH';
        $status = '4';
        $moduleCode = $this->moduleCode;
        $todayDate = date('Y-m-d');
        $dariNew = $request->dariNew;
        
        if ($dnNumber){
            $data = DB::table('delivery_det')
            ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','delivery_det.delivery_number')
            ->leftJoin('article','article.article_code','delivery_det.article_code')
            ->where('delivery_det.delivery_number',$dnNumber)
            // ->where('delivery_hdr.status','3')
            // ->where('delivery_hdr.status','1')
            ->select('delivery_det.*'
            ,'article.article_type'
            ,'article.uom as uom_article'
            ,DB::RAW("(delivery_det.qty*uom_conversion(delivery_det.uom,article.uom)) as total_qty")
            )->get();

            foreach($data as $val){
                //insert article code kalo belum ada di tabel item_stock
                DB::table('article_stock')
                ->updateOrInsert(
                    [ 'site_code' =>$siteCode,
                      'article_code' => $val->article_code,
                      'location_number'=> $location
                    ],
                    [
                      'dept_code'=>$val->article_type,
                      'uom'=>$val->uom_article,
                    ]
                );

                //update qty nya ditambahkan dengan qty baru
                DB::table('article_stock')
                ->where('site_code',$siteCode)
                ->where('article_code',$val->article_code)
                ->where('location_number',$location)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty,0) - '.$val->total_qty)
                ]);
            }
                    
            
            $rowAffected = DB::table('delivery_hdr')
            ->where('delivery_number',$dnNumber)
            ->update(
                [   
                    'status' => $status,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            if ($rowAffected > 0){
                $movements = DB::table('delivery_det')
                ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','delivery_det.delivery_number')
                ->leftJoin('article','article.article_code','delivery_det.article_code')
                ->where('delivery_det.delivery_number',$dnNumber)
                ->where('delivery_hdr.status','4')
                ->where('qty', '<>', 0)
                ->select(
                    // DB::RAW("now()::timestamp::date as movement_date" )
                    'delivery_hdr.delivery_date as movement_date'
                    ,'delivery_det.article_code'
                    ,'article.article_desc'
                    ,DB::raw("0 as movement_plus")
                    ,DB::RAW("(uom_conversion(delivery_det.uom,article.uom)*delivery_det.qty) as movement_min")
                    ,DB::raw("0 as movement_price ")
                    ,'delivery_hdr.delivery_number as movement_transnno'
                    ,DB::raw("'$moduleCode' as movement_type")
                    ,'delivery_hdr.delivery_number as movement_desc'
                )
                ->get();
                
                $dataSetMovement = [];
                foreach ($movements as $val) {
                    $dataSetMovement[] = [
                        'movement_date' => $val->movement_date,
                        'artikel_code' => $val->article_code,
                        'artikel_desc' => $val->article_desc,
                        'movement_min' => $val->movement_min,
                        'movement_plus' => $val->movement_plus,
                        'movement_price' => $val->movement_price,
                        'movement_transnno' => $val->movement_transnno,
                        'movement_type' => $val->movement_type,
                        'movement_desc' => $val->movement_desc,
                        'created_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'site_code' => $siteCode,
                        'location_number' => $location,
                        'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') - ($val->movement_min+$val->movement_plus)")
                    ];
                }

                DB::table('movement')->insert($dataSetMovement);

                $idKu = Crypt::encryptString($id);

                DB::commit();
                $title ="Posting $this->title";
                $alert  ="success";
                $message  = "$title $dnNumber Successfully Posted";
                \LogActivity::addToLog($title,"username: $username Status $message");
                
                if($dariNew=='true'){
                    return response()->json(array('statusDel' => $status, 'title' => $title, 'status' => 1, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnNumber,'idKu'=>$idKu));
                }else{
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message,'idKu'=>$idKu]);
                }

            }else{
                $title ="Posting $this->title";
                $alert  ="warning";
                $message  = "$title $dnNumber Failed to Posting";
                \LogActivity::addToLog($title,"username: $username Status $message");
                if($dariNew=='true'){
                    return response()->json(array('statusDel' => $status, 'title' => $title, 'status' => 0, 'message' => $message,'alert'=>$alert,'dnNumber'=>$dnNumber,'idKu'=>$idKu));
                }else{
                    return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                }                
            }
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $dnNumber Failed to Posting";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function unPosting($dnNumber)
    {
        $username =  Auth::user()->username;
        $dnNumber = $dnNumber;
        $recType = "NORMAL";
        $siteCode = 'HO';
        $location ='WH';
        
        $moduleCode = $this->moduleCode;
        $todayDate = date('Y-m-d');
                
        if ($dnNumber){
            $data = DB::table('delivery_det')
            ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','delivery_det.delivery_number')
            ->leftJoin('article','article.article_code','delivery_det.article_code')
            ->where('delivery_det.delivery_number',$dnNumber)
            // ->where('delivery_hdr.status','1')
            ->select('delivery_det.*'
            ,'article.article_type'
            ,'article.uom as uom_article'
            ,DB::RAW("(delivery_det.qty*uom_conversion(delivery_det.uom,article.uom)) as total_qty")
            )->get();

            foreach($data as $val){
                //insert article code kalo belum ada di tabel item_stock
                DB::table('article_stock')
                ->updateOrInsert(
                    [ 'site_code' =>$siteCode,
                      'article_code' => $val->article_code,
                      'location_number'=> $location
                    ],
                    [
                      'dept_code'=>$val->article_type,
                      'uom'=>$val->uom_article,
                    ]
                );

                //update qty nya ditambahkan dengan qty baru
                DB::table('article_stock')
                ->where('site_code',$siteCode)
                ->where('article_code',$val->article_code)
                ->where('location_number',$location)
                ->update([
                    'article_qty' => DB::raw('coalesce(article_qty,0) + '.$val->total_qty)
                ]);
            }
                               
            $movements = DB::table('delivery_det')
            ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','delivery_det.delivery_number')
            ->leftJoin('article','article.article_code','delivery_det.article_code')
            ->where('delivery_det.delivery_number',$dnNumber)
            // ->where('delivery_hdr.status','1')
            ->where('qty', '<>', 0)
            ->select(
                // DB::RAW("now()::timestamp::date as movement_date" )
                'delivery_hdr.delivery_date as movement_date'
                ,'delivery_det.article_code'
                ,'article.article_desc'
                ,DB::RAW("(uom_conversion(delivery_det.uom,article.uom)*delivery_det.qty) as movement_plus")
                ,DB::raw("0 as movement_min")
                ,DB::raw("0 as movement_price ")
                ,'delivery_hdr.delivery_number as movement_transnno'
                ,DB::raw("'$moduleCode' as movement_type")
                ,'delivery_hdr.delivery_number as movement_desc'
            )
            ->get();
            
            $dataSetMovement = [];
            foreach ($movements as $val) {
                $dataSetMovement[] = [
                    'movement_date' => $val->movement_date,
                    'artikel_code' => $val->article_code,
                    'artikel_desc' => $val->article_desc,
                    'movement_min' => $val->movement_min,
                    'movement_plus' => $val->movement_plus,
                    'movement_price' => $val->movement_price,
                    'movement_transnno' => $val->movement_transnno,
                    'movement_type' => $val->movement_type,
                    'movement_desc' => $val->movement_desc."(Revision)",
                    'created_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'site_code' => $siteCode,
                    'location_number' => $location,
                    'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') + ($val->movement_min+$val->movement_plus)")
                ];
            }

            DB::table('movement')->insert($dataSetMovement);
            return 'true';

        }else{
            return 'false';
        }
    }

    public function destroy(Request $request)
    {
       
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','8'=>'RECEIVED'];

        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $status = "5";

        $poHdr= DB::table('delivery_hdr')
        ->where('id',$id)
        ->get()->first();

        $dnNumber = $poHdr->delivery_number;
        $soNumber = $poHdr->so_number;
        $note = $poHdr->note;

        $rowAffected=DB::table('delivery_hdr')
        ->where('delivery_number',$dnNumber)
        ->update(
            [   
                'delivery_number' => $dnNumber."(C)",
                'origin_delivery_number' => $dnNumber."(C)",
                'so_number' => $soNumber."(C)",
                'status' => $status,
                'note' => $note." (Cancel)",
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        if($rowAffected>0){
            DB::table('delivery_det')
            ->where('delivery_number',$dnNumber)
            ->update(
                [   
                    'delivery_number' => $dnNumber."(C)",
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            $title ="Cancel $this->title";
            $alert  ="success";
            $message  = "$title $dnNumber Successfully Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);  
        }else{
            $title ="Cancel $this->title";
            $alert  ="warning";
            $message  = "$title $dnNumber Failed to Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);
        }
    }

    public function revision(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        // $dnOrigin=DB::table('delivery_hdr')->where('id',$id)->value('delivery_number');
        $deliveries=DB::table('delivery_hdr')->where('id',$id)->first();
        $dnOrigin=$deliveries->delivery_number;
        $dnStatus=$deliveries->status;

        // $statusDel = ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','','RECEIVED','','REVISI'];
        
        $numRevision = $request->nR ? $request->nR +1 : 1 ;
        $numRevisionName = '-R'.$numRevision;
        $dnNew = $dnOrigin.$numRevisionName;
        $checkNewDn=DB::table('delivery_hdr')->where('delivery_number',$dnNew)->count();
        $reason = $request->reason;

        if ($checkNewDn > 0){
            $dnNew = $dnOrigin.'-R'.$numRevision+1;
        } 
                
        $sqlHdr = "INSERT into delivery_hdr 
        (
            delivery_number,
            delivery_date,
            customer_id,
            so_number,
            po_number,
            approved_by,
            approved_at,
            status,
            note,
            created_by,
            updated_by,
            created_at,
            updated_at,
            origin_delivery_number,
            num_revision,
            revised_by,
            revised_at,
            reason
        )
        select 
            '$dnNew',
            delivery_date,
            customer_id,
            so_number,
            po_number,
            approved_by,
            approved_at,
            '7',
            note,
            created_by,
            '$username',
            created_at,
            '".date('Y-m-d H:i:s')."',
            '$dnOrigin',
            $numRevision,
            '$username',
            '".date('Y-m-d H:i:s')."',
            '$reason'
        from delivery_hdr where delivery_number = '$dnOrigin'";

        $sqlDet="INSERT into delivery_det
        (
            delivery_number,
            article_code,
            so_number,
            po_number,
            qty,
            uom,
            created_by,
            created_at,
            updated_by,
            updated_at,
            qty_so
        )
        select 
            '$dnNew',
            article_code,
            concat(so_number,'$numRevisionName'),
            concat(po_number,'$numRevisionName'),
            qty,
            uom,
            '$username',
            '".date('Y-m-d H:i:s')."',
            '$username',
            '".date('Y-m-d H:i:s')."',
            qty_so
        from delivery_det where delivery_number = '$dnOrigin'";

        $rowAffected =  DB::select($sqlHdr);
        if ($rowAffected){
            DB::select($sqlDet);

            // status:
            // 1 = New
            // 2 = Validated
            // 3 = Authorized
            // 4 = Received
            // 5 = Canceled
            // 6 = closed
            // 7 = Revised

            $rowAffected = DB::table('delivery_hdr')
            ->where('delivery_number',$dnOrigin)
            ->update(
                [
                    'num_revision' => $numRevision,
                    'status' => '10', //Revisi
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            if($rowAffected){
                if($dnStatus == '4'){
                    $this->unPosting($dnOrigin);
                }
            }

            DB::table('approval_history')
            ->where('module_number',$dnOrigin)
            ->update(
                [
                    'module_number' => $dnNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision DN: $dnOrigin to $dnNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->route('delivery.edit', ['id'=>Crypt::encryptString($id)]);
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision DN: $dnOrigin to $dnNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
        
    }

    public function list(Request $request)
    {
        $searchDn = strtolower($request->searchDn);
        $searchSo = strtolower($request->searchSo);
        $searchCustomer = $request->searchCustomer; 
        $searchStatus = $request->searchStatus;
        $requestDate = $request->dnDate;       

        $fromDate ="";
        $toDate = "";
 
        if ($requestDate){
            $date = explode("to",$requestDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('delivery_hdr')
        ->leftJoin('third_party','third_party.kode','delivery_hdr.customer_id')
        ->where(function ($query) use ($searchDn,$searchSo,$searchCustomer,$searchStatus,$requestDate,$fromDate,$toDate) {
            $searchDn ? $query->where('delivery_number','ilike','%'.$searchDn.'%') : '';
            $searchSo ? $query->where('so_number','ilike','%'.$searchSo.'%') : '';
            $searchStatus ? $query->where('delivery_hdr.status',$searchStatus) : '';
            $searchCustomer ? $query->where('delivery_hdr.customer_id',$searchCustomer) : '';
            $requestDate ? $query->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->where('delivery_hdr.status','!=','7')
        ->select('delivery_hdr.*'
        ,'delivery_hdr.delivery_number as delivery_number_1'
        ,DB::raw("concat(kode,'-',nama) as customer_name")
        ,DB::raw("(select count(*) from invoice_det a where a.dn_number = delivery_hdr.delivery_number
        and invoice_number in (select invoice_number from invoice_hdr where status not in  ('5','7','10'))
        ) as sudah_di_bayar")
        )
        ->orderBy('id')
        ->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            /*
                dari bu lupi 25/9/2023 3:06 pm
                pak @oki hartanto terkait pembuatan surat jlan : 
                1. tolong dalam satu menu create sudah ada tampilan save dan print (jadi pada saat sudah disave bisa langsung print tanpa harus loading ke awal )
                2. kondisi potong stock saat print (jadi print itu sudah sama dengan post) 
                3. untuk prosedur approve kita lewatkan dulu pak, kita jalanin pengecekkan surat jalannya pake hard copy, tidak pake sistem dulu
            */
            
            // if (($data->status == '10')){
            //     if (Auth::user()->can('delivery-edit')) {
            //     $buttons .=         '<a href="'. route('delivery.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
            //                             <i data-feather="file-text"></i>
            //                             Edit
            //                         </a>';
            //     }
            // }

            if (($data->status == '10')){
                if (Auth::user()->can('delivery-edit')) {
                $buttons .=         '<a href="'. route('delivery.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        Approve
                                    </a>';
                }
            }

            if ( $data->status == '1' || $data->status == '3' ) {                
                if (Auth::user()->can('delivery-posting')) {
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('delivery.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i>
                    <span>". __('Posting') ."</span>
                    </a>";
                }   
            }

            if ((($data->status == '1') || ($data->status == '2') || ($data->status == '3') || ($data->status == '4')) && ($data->sudah_di_bayar == 0)){
                $buttons .= "<a href='javascript:;'
                                id='revisionReasonButton'
                                class='dropdown-item'
                                data-toggle='modal'
                                data-target='#reasonModalRevision'
                                data-href='". route('delivery.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) ."'>
                                <i data-feather='corner-down-left' class='feather-14-red'></i>
                                <span>". __('Revision') ."</span>
                            </a>";            
            }

            if ($data->status == '4'){
                $buttons .=         '<a href="'. route('delivery.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                        <i data-feather="printer"></i>
                                        Print
                                    </a>';
            }

            $buttons .=         '<a href="'. route('delivery.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            if (($data->status != '3') && ($data->status != '4')&& ($data->status != '8')){
                if (Auth::user()->can('delivery-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModalCancel'
                                        data-href='". route("delivery.destroy",  ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        Cancel
                                    </a>";
                }
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })

        // ->addColumn('delivery_number', function ($data) {
        //     $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-success','badge-success','badge-success'];            
        //     $statusDel = ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','','RECEIVED','','REVISI'];
        //      // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','8'=>'RECEIVED','10'=>'REVISI'];
        //     return '<span style="display: none;">'.$data->delivery_number.'</span><a class="text-left badge d-block '.$badges[$data->status - 1].'" name="'.$data->delivery_number.'" href="'. route('delivery.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->delivery_number.'</span></a>';
        // })

        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-success','badge-success','badge-success'];            
            $statusDel = ['NEW','VALIDATE','APPROVED','POSTED','CANCELED','','','RECEIVED','','REVISI'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusDel[$data->status - 1]."</div>";
        })

        ->rawColumns(['action','status','delivery_number'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['companies']= array(
            "nama"=> "PT ABIMANYU SEKAR NUSANTARA",
            "alamat"=> "KP. KARANG MULYA RT 014 RW 005 DESA CIKOPO",
            "kota" => "KEC. BUNGURSARI KAB. PURWAKARTA JAWA BARAT",
            "tlp" =>  ""
        );
                
        $dnHdr=DB::table('delivery_hdr')
        ->where('id',$id)
        ->first();

        $data['dnHdr']=DB::table('delivery_hdr')
        ->where('id',$id)
        ->first();

        $dnNumber=$dnHdr -> delivery_number;
        $data['dnNumberQr'] = strtr(base64_encode($dnNumber), '+/=', '-_,');
        $data['dnNumberQr1'] = base64_decode(strtr($data['dnNumberQr'], '-_,', '+/='));
             
        $data['title'] =$dnNumber;

        $statusDel = ['NEW','VALIDATE','APPROVED','','','PAID','REVISED','RECEIVED','','REVISI'];
        $data['statusDel'] = $statusDel[$data['dnHdr']->status-1];
       
        $data['details']=DB::table('delivery_det')
        ->leftJoin('article','article.article_code','delivery_det.article_code')
        ->where('delivery_number',$dnNumber)
        ->get();


        $data['totals']=DB::select("SELECT * from (
            select 
            a.delivery_number,
            sum(qty) as qty 
            from delivery_det a
            left join delivery_hdr b
            on a.delivery_number = b.delivery_number 
            where a.delivery_number = '$dnNumber'
            group by a.delivery_number) as oki");

        $data['customers']=DB::table('third_party')
        ->where('kode',$dnHdr -> customer_id)
        ->first();
        
        $data['status'] ='1';
        $data['no'] = 0 ;
        
        return view('delivery.print',$data);

        // view()->share($data);

        // $pdf = PDF::loadView('delivery.print');
        // return $pdf->stream("DN_$dnNumber.pdf");

    }

    public function listSo(Request $request)
    {
        $cust= $request->value;
        $output="";

        $data= DB::table("sales_order_hdr") 
        ->where("customer_id",$cust)
        ->where("status","3")
        ->orderBy("so_code")
        ->select("so_code","po_number")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->so_code.'" data-po-number="'.$row->po_number.'">'.$row->so_code. ' | ' .$row->po_number.'</option>';            
        }        
        
        return $output;
    }

    public function listUom(Request $request)
    {
        $uomGroup = $request->value;      
        $output="";

        $data= DB::table("uom") 
        ->where("uom_group",$uomGroup)
        ->orderBy("code")
        ->select("code","name")
        ->get();          

        // $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->code.'">'.$row->code.'</option>';            
        }        
        
        return $output;
    }

    public function getTableColoumnReport()
    {
        $kolom=
        [
            ['data'=>'delivery_number','name'=>'delivery_number','title'=>'DN Number'],
            ['data'=>'so_number','name'=>'so_number','title'=>'SO Number'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty','name'=>'qty','title'=>'Delivery Qty'],
            ['data'=>'date_period','name'=>'date_period','title'=>'date_period','visible'=>false],
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnReportAcc()
    {
        $kolom=
        [
            ['data'=>'ppn','name'=>'ppn','title'=>'Sts'],
            ['data'=>'delivery_number','name'=>'delivery_number','title'=>'DN Number'],
            ['data'=>'so_number','name'=>'so_number','title'=>'SO Number'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'customer_name','name'=>'customer_name','title'=>'Customer'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty','name'=>'qty','title'=>'Delivery Qty'],
            ['data'=>'price','name'=>'price','title'=>'Price'],
            ['data'=>'price_service','name'=>'price_service','title'=>'Service Price'],
            ['data'=>'grand_total','name'=>'grand_total','title'=>'Grand Total'],
            ['data'=>'invoice_number','name'=>'invoice_number','title'=>'Invoice Number'],
        ];
        return json_encode($kolom, true);
    }

    public function report(Request $request)
    {
        $data['title'] = "$this->title Report";

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();
        $data['kolom'] = $this->getTableColoumnReport();
        
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','8'=>'RECEIVED'];
            
        return view("delivery.report",$data);
    }

    public function reportAcc(Request $request)
    {
        $data['title'] = "$this->title Report";

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->select('third_party.kode','third_party.nama')
        ->orderBy('nama')
        ->get();

        $data['salesOrders'] = DB::table('sales_order_hdr')
        ->leftJoin('third_party','third_party.kode','sales_order_hdr.customer_id')
        ->where ('status','<>','5')
        ->whereIn('so_code', function($query){
            $query->select('so_number')
            ->from('delivery_hdr')
            ->whereNotIn('status',['5','7']);
        })
        ->select('sales_order_hdr.so_code','sales_order_hdr.po_number','third_party.nama')
        ->orderBy('so_code')
        ->get();

        $data['kolom'] = $this->getTableColoumnReportAcc();
        
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','8'=>'RECEIVED'];
            
        return view("delivery.reportAcc",$data);
    }

    public function listReport(Request $request)
    {
        $searchDn = strtolower($request->searchDn);
        $searchCustomer = $request->searchCustomer; 
        $searchStatus = $request->searchStatus;
        $requestDate = $request->dnDate;       

        $fromDate ="";
        $toDate = "";
 
        if ($requestDate){
            $date = explode("to",$requestDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('delivery_det')
        ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','delivery_det.delivery_number')
        ->leftJoin('third_party','third_party.kode','delivery_hdr.customer_id')
        // ->leftJoin('invoice_hdr','invoice_hdr.dn_number','invoice_hdr.dn_number')
        ->leftJoin('article','article.article_code','delivery_det.article_code')
        ->where(function ($query) use ($searchDn,$searchCustomer,$searchStatus,$requestDate,$fromDate,$toDate) {
            $searchDn ? $query->where('delivery_number','ilike','%'.$searchDn.'%') : '';
            $searchStatus ? $query->where('delivery_hdr.status',$searchStatus) : '';
            $searchCustomer ? $query->where('delivery_hdr.customer_id',$searchCustomer) : '';
            $requestDate ? $query->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->whereNotIn('delivery_hdr.status',['5','7'])
        ->select(
        'article.article_desc'
        ,'article.article_alternative_code'    
        ,'delivery_hdr.delivery_date'
        ,'delivery_det.delivery_number'
        ,'delivery_det.qty'
        ,'delivery_det.so_number'
        ,'delivery_det.po_number'
        ,'third_party.nama as customer_name'
        ,DB::RAW("to_date(delivery_date,'dd-mm-yyyy') as date_period")
        )
        ->orderBy('delivery_det.id')
        ->get();

        return Datatables::of($data)
        ->make(true);

    }

    public function listReportAcc(Request $request)
    {
        $searchDn = strtolower($request->searchDn);
        $searchSo = $request->searchSo;
        $searchCustomer = $request->searchCustomer; 
        $searchStatus = $request->searchStatus;
        $requestDate = $request->dnDate;       

        $fromDate ="";
        $toDate = "";
 
        if ($requestDate){
            $date = explode("to",$requestDate);
            if(count($date)>1){
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
            }else{
                $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
                $toDate = $fromDate; 
            }
        }

        $data = DB::table('delivery_det')
        ->leftJoin('delivery_hdr','delivery_hdr.delivery_number','delivery_det.delivery_number')
        ->leftJoin('third_party','third_party.kode','delivery_hdr.customer_id')
        // ->leftJoin('invoice_hdr','invoice_hdr.dn_number','invoice_hdr.dn_number')
        ->leftJoin('article','article.article_code','delivery_det.article_code')
        ->where(function ($query) use ($searchDn,$searchCustomer,$searchStatus,$requestDate,$fromDate,$toDate,$searchSo) {
            $searchDn ? $query->where('delivery_number','ilike','%'.$searchDn.'%') : '';
            $searchSo ? $query->where('delivery_det.so_number',$searchSo) : '';
            $searchStatus ? $query->where('delivery_hdr.status',$searchStatus) : '';
            $searchCustomer ? $query->where('delivery_hdr.customer_id',$searchCustomer) : '';
            $requestDate ? $query->whereBetween(DB::raw("to_date(delivery_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->whereNotIn('delivery_hdr.status',['5','7'])
        ->select(
        'article.article_desc'    
        ,'article.article_alternative_code'  
        ,'delivery_hdr.delivery_date'
        ,'delivery_det.delivery_number'
        ,'delivery_det.qty'
        ,'delivery_det.so_number'
        ,'delivery_det.po_number'
        ,'third_party.nama as customer_name'
        // ,'invoice_hdr.invoice_number'
        ,DB::RAW("(Select invoice_number from invoice_det a where a.dn_number = delivery_det.delivery_number and a.article_code = delivery_det.article_code) as invoice_number")
        ,DB::RAW("(Select price from sales_order_det a where a.so_code = delivery_det.so_number and a.article_code = delivery_det.article_code) as price")
        ,DB::RAW("(Select price_service from sales_order_det a where a.so_code = delivery_det.so_number and a.article_code = delivery_det.article_code) as price_service")
        ,DB::RAW("(Select coalesce(price,0)+coalesce(price_service,0) from sales_order_det a where a.so_code = delivery_det.so_number and a.article_code = delivery_det.article_code) * delivery_det.qty as grand_total")
        ,DB::RAW("(Select case when ppn> 0 then 'PPN' else '' end from sales_order_det a where a.so_code = delivery_det.so_number and a.article_code = delivery_det.article_code) as ppn")
        )
        ->orderBy('delivery_det.id')
        ->get();

        return Datatables::of($data)
        ->make(true);

    }

    public function reportSoAcc(Request $request)
    {
        $data['title'] = "Report SO";

        $data['salesOrders'] = DB::table('sales_order_hdr')
        ->leftJoin('third_party','third_party.kode','sales_order_hdr.customer_id')
        ->where ('status','<>','5')
        ->whereIn('so_code', function($query){
            $query->select('so_number')
            ->from('delivery_hdr')
            ->whereNotIn('status',['5','7','10']);
        })
        ->select('sales_order_hdr.so_code','sales_order_hdr.po_number','third_party.nama')
        ->orderBy('so_code')
        ->get();

        return view("delivery.reportSoAcc",$data);
    }

    public function printReportSo(Request $request)
    {
        $data['title'] = "Report SO";
        $soNumber=$request->so_code;

        // $soNumber = 'SO/ASN/22/12/2571';
        
        $headers=DB::select("SELECT DISTINCT ON (c.article_alternative_code) a.article_code, a.so_number,c.article_alternative_code, c.article_desc,a.delivery_number
        ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
        ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
        ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
        from delivery_det a 
        left join delivery_hdr b on b.delivery_number = a.delivery_number
        left join article c on c.article_code = a.article_code
        where a.so_number = '$soNumber' 
        and b.status not in ('5','7','10')
        order by c.article_alternative_code");
        
        $barisIsiJudul='';
        $barisAll='';
        $jumlahBaris=0;

        foreach($headers as $kunci=>$val){
            $articleCode = $val->article_code;
            $articleDesc = $val->article_desc;
            $articleAlternative = $val->article_alternative_code;
            $qtySo = $val->qty_so;
            $qtyDelivery = $val->qty_delivery;
            $qtySisa = $qtySo -$qtyDelivery;

            $judul = $val->article_alternative_code." - ".$articleDesc;
            $barisIsiJudul = "<tr><td colspan='3' align='left' style='background-color:white;border-right-color:white'>".strtoupper($judul)."</td>
                                    <td align='right' style='background-color:white;'> Qty SO:".number_format($qtySo,2)."</td> </tr>";
            $barisIsiJudul .= "<tr >
                    <td class='detail-padding' align='left' scope='row' style='padding-left:5px;padding-right:3px' width='5%'>No</td>
                    <td class='detail-padding' align='left' style='padding-left:5px;padding-right:3px'>Delivery Number</td>
                    <td class='detail-padding  align='left' style='padding-left:5px;padding-right:3px'>Delivery Date</td>
                    <td class='detail-padding' align='left' style='padding-left:5px;padding-right:3px'>Qty Delivery</td>
                </tr>";
            
            $isiJudul=DB::select("SELECT a.article_code, c.article_alternative_code, c.article_desc,a.delivery_number
            , b.delivery_date,a.qty
            ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) as qty_so 
            ,ceil((select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10')))) as qty_delivery
            ,ceil((select sum(qty) from sales_order_det where so_code = a.so_number and article_code = a.article_code)) - (select sum(qty) from delivery_det where so_number = a.so_number and article_code = a.article_code and delivery_det.delivery_number in (select delivery_number from delivery_hdr where status not in ('5','7','10'))) as sisa_so
            from delivery_det a 
            left join delivery_hdr b on b.delivery_number = a.delivery_number
            left join article c on c.article_code = a.article_code
            where a.so_number = '$soNumber' and a.article_code = '$articleCode'
            and b.status not in ('5','7','10')
            order by a.article_code,b.delivery_date");
            $jumlahBaris++;
            foreach($isiJudul as $key=>$item){
                $no = $key+1;
                $barisIsiJudul .= "<tr >
                    <td class='detail-padding' align='left' scope='row' style='padding-left:5px;padding-right:3px' width='5%'>$no</td>
                    <td class='detail-padding' align='left' style='padding-left:5px;padding-right:3px'>$item->delivery_number</td>
                    <td class='detail-padding  align='left' style='padding-left:5px;padding-right:3px'>$item->delivery_date</td>
                    <td class='detail-padding' align='left' style='padding-left:5px;padding-right:3px'>".number_format($item->qty,2)."</td>
                </tr>";
                $jumlahBaris++;
            }
            $barisTotal = "<tr><td colspan='3' style='background-color:white;border-right-color:white;'></td>
                                <td align='left' style='background-color:white;border-left-color:white;padding-left:5px;padding-right:3px'>
                                <div style='float:left;width:50%;'>".number_format($qtyDelivery,2)."</div>
                                <div style='float:right;width:50%;'>Qty Sisa:".number_format($qtySisa,2)."</div>
                                </td> 
                            </tr>";
            
            end($headers);
            $pemisah = "<tr><td colspan='4' style='border-right-color:white;border-left-color:white;'></td> </tr>";
            if ($kunci === key($headers)) {
                $pemisah = "<tr><td colspan='4' style='border-right-color:white;border-left-color:white;border-bottom-color:white'></td> </tr>";
            }

            $barisTotal = $barisTotal.$pemisah;
            // $barisTotal = "<tr><td colspan='4' align='right' style='background-color:white'>QTY SO:".number_format($qtySo,2)."    |    Qty Delivery:".number_format($qtyDelivery)."     |     Qty Sisa:".number_format($qtySisa)." </td> </tr>";
            
            $barisAll .= $barisIsiJudul.$barisTotal;
        };

        $salesOrders = DB::table('sales_order_hdr')
        ->leftJoin('third_party','third_party.kode','sales_order_hdr.customer_id')
        ->where('so_code',$soNumber)
        ->select('sales_order_hdr.so_code','sales_order_hdr.po_number','third_party.nama')
        ->orderBy('so_code')
        ->first();
              
        $data['barisDetail']=$barisAll;
        $data['soNumber'] = $salesOrders->so_code;
        $data['poNumber'] = $salesOrders->po_number;
        $data['customer'] = $salesOrders->nama;
        $data['jumlahBaris'] = $jumlahBaris;

        // dd($barisAll);
        view()->share($data);
        $pdf = PDF::loadView('delivery.printReportSoAcc')->setPaper([0, 0, 595.28, 841.89], 'portrait');
        return $pdf->stream("Report_$soNumber.pdf");
    }

    public function exportSo(Request $request) 
    {
        $soNumber = $request->so_code;
        $filename = str_replace('/','_', $soNumber);
        // $soNumber = 'SO/ASN/22/12/2571';
        return Excel::download(new ReportDnExport($soNumber), $filename.'.xlsx');
    }

}
