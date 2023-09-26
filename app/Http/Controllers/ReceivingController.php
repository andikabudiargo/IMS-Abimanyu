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

class ReceivingController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "Receiving";
        $this->moduleCode = "REC";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action', 'orderable'=> false,'searchable'=>false],
            ['data'=>'rec_number','name'=>'rec_number','title'=>'Rec Number'],
            ['data'=>'rec_date','name'=>'rec_date','title'=>'Rec Date'],
            ['data'=>'inv_number','name'=>'inv_number','title'=>'Invoice Number'],
            ['data'=>'inv_date','name'=>'inv_date','title'=>'Inv Date'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'supp_name','name'=>'supp_name','title'=>'Supplier'],
            // ['data'=>'prepared_by','name'=>'prepared_by','title'=>'Prepared By'],
            // ['data'=>'authorized_by','name'=>'authorized_by','title'=>'Authorized By'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'approval_by','name'=>'approval_by','title'=>'Approved By']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'nama_dept','name'=>'nama_dept','title'=>'Departemen'],
            ['data'=>'rec_number','name'=>'rec_number','title'=>'Rec Number'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Desc'],
            ['data'=>'qty','name'=>'qty','title'=>'qty'],
            ['data'=>'qty_free','name'=>'qty_free','title'=>'qty Free'],
            ['data'=>'uom_rec','name'=>'uom_rec','title'=>'uom'],
            ['data'=>'price','name'=>'price','title'=>'Price'],
            ['data'=>'total_dpp','name'=>'total_dpp','title'=>'Total Tanpa PPN'],
            ['data'=>'rec_date','name'=>'rec_date','title'=>'Rec Date'],
            // ['data'=>'inv_number','name'=>'inv_number','title'=>'Invoice Number'],
            // ['data'=>'inv_date','name'=>'inv_date','title'=>'Invoice Date'],
            ['data'=>'do_number','name'=>'do_number','title'=>'DO Number'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'supp_name','name'=>'supp_name','title'=>'Supplier'],
            // ['data'=>'prepared_by','name'=>'prepared_by','title'=>'Prepared By'],
            // ['data'=>'authorized_by','name'=>'authorized_by','title'=>'Authorized By'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'approval_by','name'=>'approval_by','title'=>'Approved By'],
            ['data'=>'article_type_name','name'=>'article_type_name','title'=>'Keterangan'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
            
        return view("receiving.index",$data);
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
        $month = $months[date('n')-1];
        $year = date('Y');
        $number="$key-ASN/$year/$month/$newCode";
        
        return $number;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['oEdit']=false;

        return view("receiving.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $doNumber = $request->doNumber;
        $doDate = $request->doDate;
        $invNumber = $request->invNumber;
        $invDate = $request->invDate;
        $poNumber = $request->poNumber;
        $supplier = $request->supp;
        $recDate = $request->recDate;
        $note = $request->note;
        $articles = json_decode($request->articles);
        $recType = "NORMAL";
        $statusRec ="New";
        $status = '1';
        $authorizedBy = "";
        $leadCode = $this->moduleCode;

        // $data['status'] = ['1'=>'NEW','2'=>'UPDATED','3'=>'POSTED','4'=>'CANCELED'];
        
        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "DO Number :  $doNumber has already been taken on PO : $poNumber",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            // 'invNumber'=>'required|iunique:receiving_hdr,inv_number,po_number',
            // 'invDate'  => 'required',
            'doNumber'=>'required|iunique:receiving_hdr,do_number,po_number',
            'doDate'  => 'required',
            // 'recDate'  => 'required',
            // 'poNumber'  => 'required',
        ],$customMessages);
        
        $error_array = array();
        $success_output = '';
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode($leadCode);
            $recNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                    $idKu = DB::table('receiving_hdr')->insertGetId([
                        'rec_number' => $recNumber,
                        'do_number' => $doNumber,
                        'do_date' => $doDate,
                        'inv_number' => $invNumber,
                        'inv_date' => $invDate,
                        'po_number' => $poNumber,
                        'supplier_id' => $supplier,
                        'rec_date' => $recDate,
                        'authorized_by' => $authorizedBy,
                        'prepared_by' => Auth::user()->username,
                        'rec_type' => $recType,
                        'status' => $status,
                        'note' => $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $idKu = Crypt::encryptString($idKu);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'rec_number' => $recNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom_rec' => $val->uom,
                            'qty_free' => $val->qty_free,
                            'uom_free' => $val->uom_free,
                            'price' => $val->price,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('receiving_det')->insert($dataSet);

                    DB::commit();
                    $title = "Save $this->title";
                    $alert  ="success";
                    $message  = "$title $recNumber is successfully saved";
                    $statusRec  = $statusRec;
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('statusRec' => $statusRec, 'title' => $title, 'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber,'idKu'=>$idKu));

            } catch (Exception $e) {
                DB::rollBack();
                $title = "Save $this->title";
                $alert  ="warning";
                $message  = "$title $recNumber is failed to save";
                $statusRec = 'FAILED';
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusRec' => $statusRec, 'title' => $title, 'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Details $this->title";
        $data['subtitle'] = "Details $this->title";

        $data['header'] = DB::table('receiving_hdr')
        ->where('id',$id)
        ->get()->first();

        $recNumber = $data['header']->rec_number;

        $data['detail'] = DB::table('receiving_det')
        ->leftJoin('article','article.article_code','=','receiving_det.article_code')
        ->leftJoin('uom','receiving_det.uom_rec','uom.code')
        ->where('receiving_det.rec_number',$recNumber)
        ->where('receiving_det.qty','>',0)
        ->orderBy('receiving_det.id')
        // ->select('receiving_det.article_code')
        ->get();       

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$recNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$recNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $statusRec = ['NEW','VALIDATE','APPROVED','POSTED','CANCELED'];

        $data['statusRec'] = $statusRec[$data['header']->status-1];

        return view("receiving.show",$data);
        
    }

    public function edit(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('receiving_hdr')
        ->where('id',$id)
        ->get()->first();

        $recNumber = $data['header']->rec_number;

        $data['detail'] = DB::table('receiving_det')
        ->leftJoin('article','article.article_code','=','receiving_det.article_code')
        ->leftJoin('uom','receiving_det.uom_rec','uom.code')
        ->where('receiving_det.rec_number',$recNumber)
        ->orderBy('receiving_det.id')
        // ->select('receiving_det.article_code')
        ->get();       

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$recNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$recNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $statusRec = ['NEW','VALIDATE','APPROVED','POSTED','CANCELED'];
        $data['statusRec'] = $statusRec[$data['header']->status-1];

        $data['oEdit']=true;

        return view("receiving.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $recNumber = $request->recNumber;
        $doNumber = $request->doNumber;
        $doDate = $request->doDate;
        $invNumber = $request->invNumber;
        $invDate = $request->invDate;
        $poNumber = $request->poNumber;
        $supplier = $request->supp;
        $recDate = $request->recDate;
        $note = $request->note;
        $articles = json_decode($request->articles);
        $recType = "NORMAL";
        $statusRec ="Update";
        $status = '2';
        $authorizedBy = "";

        // $data['status'] = ['1'=>'NEW','2'=>'UPDATED','3'=>'POSTED','4'=>'CANCELED'];

        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "Invoice : $invNumber has already been taken on PO : $poNumber",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            // 'invNumber'=>'required|iunique:receiving_hdr,inv_number,po_number',
            'recDate'  => 'required',
            'poNumber'  => 'required',
            // 'supplier'  => 'required',
        ],$customMessages);
                
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Update $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            DB::beginTransaction();
            try {
                    $row_affected=DB::table('receiving_hdr')
                    ->where('rec_number',$recNumber)
                    ->update(
                        [   
                            'do_number' => $doNumber,
                            'do_date' => $doDate,
                            'inv_number' => $invNumber,
                            'inv_date' => $invDate,
                            'po_number' => $poNumber,
                            'supplier_id' => $supplier,
                            'rec_date' => $recDate,
                            'authorized_by' => $authorizedBy,
                            'prepared_by' => Auth::user()->username,
                            'rec_type' => $recType,
                            'status' => $status,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $recNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('receiving_det')
                        ->whereNotIn(DB::raw("CONCAT(rec_number,article_code)"),$dataSet)
                        ->where('rec_number',$recNumber)
                        ->delete();
                                  
                    foreach ($articles as $val) {
                        DB::table('receiving_det')
                        ->updateOrInsert(
                            ['rec_number' => $recNumber,'article_code' => $val->article_code],
                            [
                                'rec_number' => $recNumber,
                                'article_code' => $val->article_code,
                                'qty' => $val->qty,
                                'uom_rec' => $val->uom,
                                'qty_free' => $val->qty_free,
                                'uom_free' => $val->uom_free,
                                'price' => $val->price,
                                'updated_by' => Auth::user()->username,
                                'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }
                                                                
                    DB::commit();
                    $title ="Update $this->title";
                    $alert  ="success";
                    $message  = "$title $recNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('statusRec' => $statusRec,'status' => 1, 'title' => $title,'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Update $this->title";
                $alert  ="warning";
                $message  = "$title $recNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusRec' => $statusRec,'status' => 1, 'title' => $title,'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
            }
        }
    }

    public function posting(Request $request)
    {
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $recNumber = DB::table('receiving_hdr')->where('id',$id)->where('status','=','3')->value('rec_number');
        $recType = "NORMAL";
        $siteCode = 'HO';
        $location ='WH';
        $status = '4';
        $moduleCode = $this->moduleCode;
        $todayDate = date('Y-m-d');
        // $rowAffected = 0;
            
        if ($recNumber){
            $data = DB::table('receiving_det')
            ->leftJoin('receiving_hdr','receiving_hdr.rec_number','receiving_det.rec_number')
            ->leftJoin('article','article.article_code','receiving_det.article_code')
            ->where('receiving_det.rec_number',$recNumber)
            ->where('receiving_hdr.status','3')
            ->select('receiving_det.*'
            ,'article.article_type'
            ,'article.uom as uom_article'
            ,DB::RAW("average_cost(receiving_det.article_code,'$siteCode','$location','$moduleCode') as average_cost")
            ,DB::RAW("(receiving_det.qty*uom_conversion(receiving_det.uom_rec,article.uom))+(receiving_det.qty_free*uom_conversion(receiving_det.uom_rec,article.uom)) as total_qty")
            )
            ->get();

            foreach($data as $val){
                //insert article code kalo belum ada di tabel item_stock
                // if( $val->total_qty > 0 ){
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
                    $rowAffected = DB::table('article_stock')
                    ->where('site_code',$siteCode)
                    ->where('article_code',$val->article_code)
                    ->where('location_number',$location)
                    ->update([
                        'article_qty' => DB::raw('coalesce(article_qty,0) + '.$val->total_qty)
                    ]);

                    if ($rowAffected > 0){
                        DB::table('article')
                        ->where('article_code',$val->article_code)
                        ->update(
                        [   
                            'lastcost' => $val->price,
                            'avgcost' =>  $val->average_cost,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                        );
                    }
                // }

                // $rowAffected = DB::table('article_stock')
                // ->where('site_code',$siteCode)
                // ->where('article_code',$val->article_code)
                // ->increment('article_qty', $val->total_qty);

            }
                    
            $rowAffected = DB::table('receiving_hdr')
            ->where('rec_number',$recNumber)
            ->update(
                [   
                    'status' => $status,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            if ($rowAffected > 0){
                $movements = DB::table('receiving_det')
                ->leftJoin('receiving_hdr','receiving_hdr.rec_number','receiving_det.rec_number')
                ->leftJoin('article','article.article_code','receiving_det.article_code')
                ->where('receiving_det.rec_number',$recNumber)
                ->where('receiving_hdr.status','4')
                ->where('qty', '<>', 0)
                ->select(
                    DB::RAW("now()::timestamp::date as movement_date" )
                    ,'receiving_det.article_code'
                    ,'article.article_desc'
                    ,DB::raw("0 as movement_min")
                    ,DB::RAW("(uom_conversion(receiving_det.uom_rec,article.uom)*receiving_det.qty) as movement_plus")
                    ,DB::raw("receiving_det.price as movement_price ")
                    ,'receiving_hdr.rec_number as movement_transnno'
                    ,DB::raw("'$moduleCode' as movement_type")
                    ,'receiving_hdr.po_number as movement_desc'
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
                        'last_qty' => DB::raw("get_last_qty('$val->article_code','$todayDate','$siteCode','$location') + ($val->movement_min+$val->movement_plus)")
                    ];
                }

                DB::table('movement')->insert($dataSetMovement);

                DB::commit();
                $title ="Posting $this->title";
                $alert  ="success";
                $message  = "$title $recNumber Successfully Posted";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                // return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
            }else{
                $title ="Posting $this->title";
                $alert  ="warning";
                $message  = "$title $recNumber Failed to Posting";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
                // return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
            }
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $recNumber Failed to Posting";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    public function cancel(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $recNumber = DB::table('receiving_hdr')->where('id',$id)->where('status','4')->value('rec_number');
        $recType = "NORMAL";
        $siteCode = 'HO';
        $location ='WH';
        $status = '5';
        $moduleCode = $this->moduleCode;
        $reason = "(Cancel by $username, Reason: $request->reason)";
        $todayDate = date('Y-m-d');

        $data = DB::table('receiving_det')
        ->leftJoin('receiving_hdr','receiving_hdr.rec_number','receiving_det.rec_number')
        ->leftJoin('article','article.article_code','receiving_det.article_code')
        ->where('receiving_det.rec_number',$recNumber)
        ->where('receiving_hdr.status','4')
        ->select('receiving_det.*'
        ,'article.article_type'
        ,'article.uom as uom_article'
        ,DB::RAW("average_cost(receiving_det.article_code,'$siteCode','$location','$moduleCode') as average_cost")
        ,DB::RAW("(receiving_det.qty*uom_conversion(receiving_det.uom_rec,article.uom))+(receiving_det.qty_free*uom_conversion(receiving_det.uom_rec,article.uom)) as total_qty")
        )
        ->get();

        foreach($data as $val){
            //insert article code kalo belum ada di tabel item_stock
            DB::table('article_stock')
            ->updateOrInsert(
                [ 'site_code' =>$siteCode,
                  'article_code' => $val->article_code,
                  'location_number'=>$location
                ],
                [
                  'dept_code'=>$val->article_type,
                  'uom'=>$val->uom_article
                ]
            );

            $rowAffected = DB::table('article_stock')
            ->where('site_code',$siteCode)
            ->where('article_code',$val->article_code)
            ->where('location_number',$location)
            ->update([
                'article_qty' => DB::raw('coalesce(article_qty,0) - '.$val->total_qty)
            ]);

            //update qty nya ditambahkan dengan qty baru
            // $rowAffected = DB::table('article_stock')
            // ->where('site_code',$siteCode)
            // ->where('article_code',$val->article_code)
            // ->decrement('article_qty', $val->total_qty);

            if ($rowAffected){
                DB::table('article')
                ->where('article_code',$val->article_code)
                ->update(
                [   
                    'lastcost' => $val->price,
                    'avgcost' =>  $val->average_cost,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
                );
            }
        }
        
        if ($rowAffected > 0){
            DB::table('receiving_hdr')
            ->where('rec_number',$recNumber)
            ->update(
                [   
                    'status' => $status,
                    'po_number'=>DB::raw("CONCAT(po_number,';','(C)')") ,
                    'do_number'=>DB::raw("CONCAT(do_number,';','(C)')") ,
                    'note' => DB::raw("CONCAT(note,';','$reason')") ,
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            $movements = DB::table('receiving_det')
            ->leftJoin('receiving_hdr','receiving_hdr.rec_number','receiving_det.rec_number')
            ->leftJoin('article','article.article_code','receiving_det.article_code')
            ->where('receiving_det.rec_number',$recNumber)
            ->where('receiving_hdr.status','5')
            ->where('qty', '<>', 0)
            ->select(
                DB::RAW("now()::timestamp::date as movement_date" )
                ,'receiving_det.article_code'
                ,'article.article_desc'
                ,DB::raw("0 as movement_plus")
                ,DB::RAW("(uom_conversion(receiving_det.uom_rec,article.uom)*receiving_det.qty) as movement_min")
                ,DB::raw(" 0 as movement_price ")
                ,'receiving_hdr.rec_number as movement_transnno'
                ,DB::raw("'$moduleCode' as movement_type")
                ,'receiving_hdr.note as movement_desc'
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

            DB::commit();
            $title ="Cancel $this->title";
            $alert  ="success";
            $message  = "$title $recNumber Successfully Canceled";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }else{
            $title ="Cancel $this->title";
            $alert  ="warning";
            $message  = "$title $recNumber Failed to Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }
  
    public function destroy(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $status = "5";
        $recNumber = DB::table('receiving_hdr')->where('id',$id)
        ->where('status','<>','4')
        ->where('status','<>','5')
        ->value('rec_number');

        $rowAffected = DB::table('receiving_hdr')->where('rec_number',$recNumber)->delete();

        if($rowAffected>0){
            DB::table('receiving_det')->where('rec_number',$recNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $recNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $recNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $searchRec = strtolower($request->searchRec);
        $searchPo = strtolower($request->searchPo);
        $searchInv = strtolower($request->searchInv);
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $recDate = $request->recDate;
        $fromDate ="";
        $toDate = "";
        if ($recDate){
            $date = explode("to",$recDate);
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

        $data = DB::table('receiving_hdr')
        ->where(function ($query) use ($searchRec,$searchPo,$searchInv,$searchSupplier,$searchStatus,$recDate,$fromDate,$toDate) {
            $searchPo ? $query->where('po_number','ilike','%'.$searchPo.'%') : '';
            $searchInv ? $query->where('inv_number','ilike','%'.$searchInv.'%') : '';
            $searchSupplier ? $query->where('supplier_id','ilike','%'.$searchSupplier.'%') : '';
            $searchRec ? $query->where('rec_number','ilike','%'.$searchRec.'%') : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
            $recDate ? $query->whereBetween(DB::raw("to_date(rec_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('receiving_hdr.*'
        ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = receiving_hdr.rec_number) as approval_by")
        ,DB::raw("(select concat(kode,'-',nama) from third_party where kode = receiving_hdr.supplier_id limit 1) as supp_name")
        )
        ->orderBy('id')
        ->get(); 
        
        // $data = DB::select("SELECT id,inv_number,rec_number,rec_date,po_number,inv_date,
        // (select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name ,prepared_by,authorized_by,status
        // from receiving_hdr a $filter");

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if ( $data->status == '1' or $data->status == '2') {
                if (Auth::user()->can('receiving-approve')) {
                $buttons .=         '<a href="'. route('receiving.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                }
            }

            if ( $data->status == '3' ) {                
                if (Auth::user()->can('receiving-posting')) {
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to post This number?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                    data-modal-id='".$data->id."'
                    data-url='". route('receiving.posting', ['id'=>Crypt::encryptString($data->id)]) ."'>
                    <i data-feather='check' class='feather-14-red'></i>
                    <span>". __('Posting') ."</span>
                    </a>";
                }   
            }

            if (($data->status == '1') OR ($data->status == '2')){
                if (Auth::user()->can('receiving-edit')) {
                $buttons .=         '<a href="'. route('receiving.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            }

            if ( $data->status == '4' ){
                if (Auth::user()->can('receiving-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                            id='cancelReasonButton'
                                            class='dropdown-item'
                                            data-toggle='modal'
                                            data-target='#reasonModalCancel'
                                            data-href='". route("receiving.cancel", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                            <i data-feather='corner-down-left' class='feather-14-red'></i>
                                            <span>". __('Cancel') ."</span>
                                        </a>";
                }
            }
            
            if ( $data->status != '4' and $data->status != '5' ){
                if (Auth::user()->can('receiving-delete')) {
                    $buttons .=         "<a href='javascript:;'
                                        class='dropdown-item' 
                                        data-size='sm'
                                        data-ajax-delete='true'
                                        data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                        data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                        data-modal-id='".$data->id."'
                                        data-url='". route('receiving.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        <span>". __('Delete') ."</span>
                                    </a>";
                }
            }

            // if ($data->status == '4'){
                $buttons .=         "<a href='". route('receiving.print', ['id'=>Crypt::encryptString($data->id)]) ."' target='_blank' class='dropdown-item'>
                                        <i data-feather='printer'></i>
                                        <span>". __('Print') ."</span>
                                    </a>";

            // }

            $buttons .=         '<a href="'. route('receiving.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('rec_number', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            return '<span class="d-none">'.$data->id.'</span><a class="badge d-block '.$badges[$data->status - 1].'" href="'. route('receiving.show', ['id'=>Crypt::encryptString($data->id)]) .'" >'.$data->rec_number.'</a>';
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $statusRec = ['NEW','VALIDATE','APPROVE','POSTED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusRec[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','rec_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $searchRec = strtolower($request->searchRec);
        $searchPo = strtolower($request->searchPo);
        $searchInv = strtolower($request->searchInv);
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $recDate = $request->recDate;
        $fromDate ="";
        $toDate = "";
        if ($recDate){
            $date = explode("to",$recDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('receiving_det')
        ->leftJoin('receiving_hdr','receiving_hdr.rec_number','receiving_det.rec_number')
        ->leftJoin('article','article.article_code','receiving_det.article_code')
        ->leftJoin('article_types','article_types.code','article.article_type')
        ->leftJoin('uom','uom.code','receiving_det.uom_rec')
        ->where(function ($query) use ($searchRec,$searchPo,$searchInv,$searchSupplier,$searchStatus,$recDate,$fromDate,$toDate) {
            $searchPo ? $query->where('po_number','ilike','%'.$searchPo.'%') : '';
            $searchInv ? $query->where('inv_number','ilike','%'.$searchInv.'%') : '';
            $searchSupplier ? $query->where('supplier_id','ilike','%'.$searchSupplier.'%') : '';
            $searchRec ? $query->where('rec_number','ilike','%'.$searchRec.'%') : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
            $recDate ? $query->whereBetween(DB::raw("to_date(rec_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->where('receiving_det.qty','>',0)
        ->select('receiving_det.*'
        ,'receiving_hdr.*'
        ,'article_alternative_code'
        ,'article_desc'
        ,'article_types.name as article_type_name'
        ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty,'999,999,999.99') end as qty")
        ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty_free,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty_free,'999,999,999.99') end as qty_free")
        ,DB::raw("TO_CHAR(price*qty,'999,999,999') as total_dpp")
        ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = receiving_hdr.rec_number) as approval_by")
        ,DB::raw("(select concat(kode,'-',nama) from third_party where kode = receiving_hdr.supplier_id limit 1) as supp_name")
        ,DB::raw("(select (select name from depts where code = dept) as nama_dept from purchase_request_hdr where pr_number in (select pr_number from purchase_request_det where po_number = receiving_hdr.po_number) limit 1)")
        )
        ->orderBy('receiving_det.id')
        ->get(); 
        
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $statusRec = ['NEW','UPDATED','APPROVED','POSTED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusRec[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['companies']=DB::table('company')
        ->where('code','ASN')
        ->select('name as nama', 'address as alamat', DB::RAW('(select region_name from regions where region_code = city::integer)  as kota'),'tlp')
        ->get()->first();
                
        $recHdr=DB::table('receiving_hdr')
        ->where('id',$id)
        ->first();

        $data['recHdr']=DB::table('receiving_hdr')
        ->where('id',$id)
        ->first();

        $recNumber=$recHdr -> rec_number;
       
        $data['details']=DB::table('receiving_det')
        ->leftJoin('article','article.article_code','receiving_det.article_code')
        ->where('rec_number',$recNumber)
        ->where('qty','>',0)
        ->get();

        $data['totals']=DB::select("SELECT * from (
            select a.rec_number,authorized_by,prepared_by,sum(qty) as qty from receiving_det a
            left join receiving_hdr b
            on a.rec_number = b.rec_number 
            where a.rec_number = '$recNumber'
            group by a.rec_number,authorized_by,prepared_by) as oki");

        $data['suppliers']=DB::table('third_party')
        ->where('kode',$recHdr -> supplier_id)
        ->get();

        $data['approved'] = DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_number',$recNumber)
        ->orderBy('approval_order','desc')
        ->value('users.name');
        
        $statusRec = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['status'] = $statusRec[$recHdr ->status-1];

        $data['no'] =0;

        $data['title'] =$recNumber;

        return view('receiving.print',$data);

        // view()->share($data);

        // $pdf = PDF::loadView('receiving.print');
        // return $pdf->stream("PO_$recNumber.pdf");

    }

    public function listPo(Request $request)
    {
        $supp= $request->value;      
        $output="";

        $data= DB::table("purchase_order_hdr") 
        ->where("supplier_id",$supp)
        ->where("status","3")
        ->orderBy("po_number")
        ->select("po_number")
        ->get();          

        if (count($data)>0){
            $output .='<option value="Choose PO">Choose PO</option>';            
            foreach ($data as $row){
                $output .='<option value="'.$row->po_number.'">'.$row->po_number.'</option>';            
            }
        }
        return $output;
    }

    public function poDetail(Request $request)
    {
        $po = $request->value;
        $data = DB::select("SELECT 
                a.*,
                a.article_code,
                article_alternative_code,
                article_desc,uom_group, 
                (COALESCE(a.qty,0)-COALESCE(b.qty,0)) as qty_order
                from purchase_order_det a
                left join uom on uom.code=a.uom
                left join article on article.article_code = a.article_code
                left join 
                    (select po, article_code,sum(qty) as qty,price from (
                        select *,(select po_number from receiving_hdr 
                                   where rec_number = a.rec_number) as po from receiving_det a where rec_number in (
                                   select rec_number from receiving_hdr where status <> '5' and po_number = '$po')
                    ) z
                group by po, article_code,price) b
                on a.po_number = b.po and a.article_code = b.article_code
                where po_number = '$po'
                order by a.id");

        return response()->json($data);
    }

    public function listUom(Request $request)
    {
        $uomGroup = $request->value;      
        $output="";

        $data= DB::table("uom")
        ->where(function ($query) use ($uomGroup) {
            $uomGroup ? $query->where('uom_group',$uomGroup) : '';
        })
        ->orderBy("code")
        ->select("code","name","uom_group")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->code.'" data-uom-group="'.$row->uom_group.'">'.$row->code.'</option>';            
        }
        return $output;
    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $recNumber = $request->recNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$recNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusRec = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('receiving_hdr')
                ->where('rec_number',$recNumber)
                ->update(
                    [
                        'status' => $statusRec,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $recNumber,
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
                $message  = "$title $recNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $recNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
        }
    }


      // public function posting(Request $request)
    // {
    //     // $data['status'] = ['1'=>'NEW','2'=>'UPDATED','3'=>'POSTED','4'=>'CANCELED'];
    //     // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];

    //     $username =  Auth::user()->username;
    //     $recNumber = $request->recNumber;
    //     $recType = "NORMAL";
    //     $statusRec ="POSTED";
    //     $siteCode = 'HO';
    //     $location ='WH';
    //     $status = '3';
    //     $authorizedBy = Auth::user()->username;

    //     // Update stock kalo article nya udah ada
    //     $sqlUpdate = "UPDATE article_stock a set article_qty = COALESCE(a.article_qty,0)  + COALESCE(b.qty,0)
    //     from (
    //     select art_code, (qty*factor_qty)+(qty_free*factor_free) as qty from 
    //     (
    //         select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = o.uom_rec and unit_to = article.uom) as factor_qty,(select unit_factor from uom_con where unit_from = o.uom_free and unit_to = article.uom) as factor_free  from (
    //         select * from receiving_det where rec_number in (
    //         select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) o
    //         left join article on article.article_code = o.article_code
    //     ) c
    //     ) b
    //     where a.article_code=b.art_code";

    //     //Insert ke stock kalo article nya belum ada
    //     $sqlInsert = "INSERT into article_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
    //     select 'HO',art_code,article_type,'00',(qty*factor_qty)+(qty_free*factor_free) as qty,uom from 
    //     (
    //         select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = z.uom_rec and unit_to = article.uom) as factor_qty,(select unit_factor from uom_con where unit_from = z.uom_free and unit_to = article.uom) as factor_free  from (
    //         select * from receiving_det where rec_number in (
    //         select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) z
    //         left join article on article.article_code = z.article_code
    //         where article.article_code not in (select article_code from article_stock)
    //     ) y";

    //     //Insert into table movement
    //     $sqlMovement = "INSERT into movement
    //     (movement_date,artikel_code,artikel_desc,movement_min,movement_plus,movement_price,movement_transnno,movement_type,movement_desc)
    //     select 
    //     now()::timestamp::date,
    //     article_code,
    //     (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
    //     0,
    //     qty,
    //     price,
    //     rec_number,
    //     'REC',
    //     (select po_number from receiving_hdr where rec_number=a.rec_number) as po from receiving_det a where rec_number in (
    //     select rec_number from receiving_hdr where rec_number = '$recNumber' and status = '3' and qty <> 0)";
    
    //     DB::select($sqlUpdate);
    //     $rowAffected = DB::select($sqlInsert);
        
    //     if ($rowAffected > 0){
    //         DB::table('receiving_hdr')
    //         ->where('rec_number',$recNumber)
    //         ->update(
    //             [   
    //                 'status' => $status,
    //                 'authorized_by' => $authorizedBy,
    //                 'authorized_at' => date('Y-m-d H:i:s'),
    //                 'updated_by' => Auth::user()->username,
    //                 'updated_at' => date('Y-m-d H:i:s')
    //             ]
    //         );

    //         DB::select($sqlMovement);

    //         DB::commit();
    //         $title ="Posting $this->title";
    //         $alert  ="success";
    //         $message  = "$title $recNumber Successfully Posting";
    //         \LogActivity::addToLog($title,"username: $username Status $message");
    //         return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
    //     }else{
    //         $title ="Posting $this->title";
    //         $alert  ="warning";
    //         $message  = "$title $recNumber Failed to Posting";
    //         \LogActivity::addToLog($title,"username: $username Status $message");
    //         return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
    //     }
    // }

}
