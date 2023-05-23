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

        $data['currency'] = ['IDR','USD'];

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
            // 'iunique' => "PO Number has already been taken",
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
                $message  = "PC $vcNumber is failed to save";
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

        $status = ['NEW','AUTHORIEED','DELETED'];
        $data['status'] = $status[$data['header']->status-1];

        return view("accounting.kas.show",$data);
        
    }

    public function showEdit($key){
        $id=$key;
        $data['title'] = "Edit Petty Cash";
        $data['subtitle'] = "Edit Petty Cash";

        $data['header'] = DB::table('bankPenerimaan_hdr')
        ->where('id',$id)
        ->get()->first();

        $vcNumber = $data['header']->pc_number;
        
        $data['detail'] = DB::table('bankPenerimaan_det')
        ->where('pc_number',$vcNumber)
        ->orderBy('id')
        ->get();       
                
        return view("bankPenerimaan.edit",$data);
    }

    public function edit(Request $request)
    {
        $id=$request->id;
        return $this->showEdit($id);
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=$request->id;
        $poOrigin = $request->poNumber;
        $numRevision = $request->numRevision ? $request->numRevision +1 : 1 ;
        $poNew = $poOrigin.'-R'.$numRevision;
        
        $sqlHdr = "INSERT into purchase_order_hdr 
        (
            po_number,
            origin_po_number,
            supplier_id,
            po_date,
            delivery_date,
            currency,
            authorized_by,
            authorized_at,
            validate_by,
            discount,
            kurs,
            pkp,
            ppn,
            pph22,
            termin,
            order_type,
            status,
            num_revision,
            revised_by,
            revised_at,
            note,
            created_by,
            updated_by,
            created_at,
            updated_at
        )
        select 
            '$poNew',
            '$poOrigin',
            supplier_id,
            po_date,
            delivery_date,
            currency,
            authorized_by,
            authorized_at,
            validate_by,
            discount,
            kurs,
            pkp,
            ppn,
            pph22,
            termin,
            order_type,
            '7',
            $numRevision,
            '$username',
            '".date('Y-m-d H:i:s')."',
            note,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."'
        from purchase_order_hdr where po_number = '$poOrigin'";

        $sqlDet="INSERT into purchase_order_det
        (
            po_number,
            pr_number,
            article_code,
            qty,
            uom,
            old_price,
            price,
            ppn,
            pph22,
            created_by,
            updated_by,
            created_at,
            updated_at
        )
        select '$poNew',
            pr_number,
            article_code,
            qty,
            uom,
            old_price,
            price,
            ppn,
            pph22,
            '$username',
            '$username',
            '".date('Y-m-d H:i:s')."',
            '".date('Y-m-d H:i:s')."' 
        from purchase_order_det where po_number = '$poOrigin'";

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

            DB::table('purchase_order_hdr')
            ->where('po_number',$poOrigin)
            ->update(
                [
                    'num_revision' => $numRevision,
                    'revised_by'=>Auth::user()->username,
                    'revised_at'=> date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            // DB::table('purchase_request_det')
            // ->where('po_number',$poOrigin)
            // ->update(
            //     [
            //         'po_number' => $poNew,
            //         'updated_by' => Auth::user()->username,
            //         'updated_at' => date('Y-m-d H:i:s')
            //     ]
            // );

            // $idBaru = DB::table('purchase_order_hdr')->where('po_number',$poNew)->value('id');
            $alert  ="alert-success";
            $message  = "Revision PO: $poOrigin to $poNew is successfully saved";
            \LogActivity::addToLog('SO save ',"username: $username Status $message");
            return $this->showEdit($id);
        }else{
            $alert  ="alert-warning";
            $message  = "Revision PO: $poOrigin to $poNew is successfully failed";
            \LogActivity::addToLog('PO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $poNumber = $request -> poNumber;
        $poType = $request -> poType;
        $articles = json_decode($request -> articles);
        $orderDate = $request->orderDate;
        $deliveryDate = $request->deliveryDate;
        $currency = $request->currency;
        $supplier = $request->supplier;
        $tax = $request->tax;
        $ppn = $request->ppn;
        $termin = $request -> term;
        $pph = 0;
        $kurs = $request -> kurs;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $discount = $request->discount;
        $note = $request->note;
        $status = '1';

        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Authorized
        // 4 = Received
        // 5 = Canceled
        // 6 = closed
        // 7 = Revised
        
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
            // 'poNumber'=>'required|unique:purchase_order_hdr,po_number',
            // 'orderNumber' => 'required',
            'orderDate'  => 'required',
            'currency'  => 'required',
            'supplier'  => 'required',
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
                    $row_affected=DB::table('purchase_order_hdr')
                    ->where('po_number',$poNumber)
                    ->update(
                        [
                            'po_number' => $poNumber,
                            'supplier_id' => $supplier,
                            'po_date' => $orderDate,
                            'delivery_date' =>$deliveryDate,
                            'currency' => $currency,
                            'kurs' => $kurs,
                            'ppn' => $ppn,
                            'pph22' => $pph,
                            'status' => $status,
                            'note' =>  $note,
                            'authorized_by' => '',
                            'validate_by' =>  '',
                            'discount' => $discount,
                            'pkp' => $tax,
                            'termin' =>$termin,
                            'order_type' => $poType,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $poNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('purchase_order_det')
                        ->whereNotIn(DB::raw("CONCAT(po_number,article_code)"),$dataSet)
                        ->where('po_number',$poNumber)
                        ->delete();

                                  
                    foreach ($articles as $val) {
                        DB::table('purchase_order_det')
                        ->updateOrInsert(
                            ['po_number' => $poNumber,'article_code' => $val->article_code],
                            [
                            'po_number' => $poNumber,
                            'pr_number' => $val->pRequest,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'old_price' => $val->price,
                            'price' => $val->newPrice,
                            'ppn' => $totalPpn,
                            'pph22' => $totalPph,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        
                        DB::table('purchase_request_det')
                        ->where('pr_number',$val->pRequest)
                        ->where('article_code',$val->article_code)
                        ->where('supp_code',$supplier)
                        ->update(
                            [
                            'po_number' => $poNumber,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );

                        DB::table('purchase_request_hdr')
                        ->where('pr_number',$val->pRequest)
                        ->update(
                            [
                            'status' => 7,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                    }
                   
                    //update purchase_request_det kalo ada article yang di hapus di PO, jadi kolom po_number di null kan
                    DB::table('purchase_request_det')
                    ->whereNotIn(DB::raw("CONCAT(pr_number,po_number,article_code)"), function($query) use ($poNumber) {
                        $query->select(DB::raw("CONCAT(pr_number,po_number,article_code)"))
                        ->from('purchase_order_det') 
                        ->where('po_number',$poNumber);
                    })
                    ->where('po_number',$poNumber)
                    ->update(
                        [
                            'po_number' => null,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );
                                            
                    DB::commit();
                    $alert  ="alert-success";
                    $message  = "PO $poNumber is successfully updated";
                    \LogActivity::addToLog('PO update ',"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $alert  ="alert-warning";
                $message  = "PO $poNumber is failed to updated";
                \LogActivity::addToLog('PO update ',"username: $username Status $message");
                return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));
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
        $statusPo = 'Authorized';
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
                $message  = "PO $poNumber is successfully Authorized";
                \LogActivity::addToLog('PO update ',"username: $username Status $message");
                return response()->json(array('statusPo' => $statusPo,'status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "PO $poNumber is failed to Authorize";
            \LogActivity::addToLog('PO update ',"username: $username Status $message");
            return response()->json(array('statusPo' => $statusPo,'status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));
        }
    }

    public function validasi(Request $request)
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
        $statusPo = 'Validated';
        $status = '2';

        DB::beginTransaction();
        try {
                $row_affected=DB::table('purchase_order_hdr')
                ->where('po_number',$poNumber)
                ->update(
                    [
                        'status' => $status,
                        'validate_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                                        
                DB::commit();
                $alert  ="alert-success";
                $message  = "PO $poNumber is successfully Validated";
                \LogActivity::addToLog('PO update ',"username: $username Status $message");
                return response()->json(array('statusPo' => $statusPo,'status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "PO $poNumber is failed to Validate";
            \LogActivity::addToLog('PO update ',"username: $username Status $message");
            return response()->json(array('statusPo' => $statusPo,'status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id = $request->id;
        $po_number = DB::table('purchase_order_hdr')->where('id',$id)->where('status','1')->value('po_number');
        $rowAffected = DB::table('purchase_order_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('purchase_order_det')->where('po_number',$po_number)->delete();
            $alert  ="alert-success";
            $message  = "PO $po_number Successfully Deleted";
            \LogActivity::addToLog('PO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "PO $po_number Failed to Delete";
            \LogActivity::addToLog('PO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function clear(Request $request)
    {
        //memutihkan PO supaya tidak bisa di pakai lagi
        //status PO jadi closed
        DB::beginTransaction();
        try {
                $row_affected=DB::table('purchase_order_hdr')
                ->where('po_number',$poNumber)
                ->update(
                    [
                        'status' => $status,
                        'validate_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                DB::commit();
                $alert  ="alert-success";
                $message  = "PO $poNumber is successfully Cleared";
                \LogActivity::addToLog('PO update ',"username: $username Status $message");
                return response()->json(array('statusPo' => $statusPo,'status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $alert  ="alert-warning";
            $message  = "PO $poNumber is failed to Clear";
            \LogActivity::addToLog('PO update ',"username: $username Status $message");
            return response()->json(array('statusPo' => $statusPo,'status' => 1, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));
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
        ->select('kas_hdr.*',db::raw("concat(accounts.account,'-',description) as receive_name"))
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
                // $buttons .=     '<a href="'. route('kasPenerimaan.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                //                     <i data-feather="file-text"></i>
                //                     Edit
                //                 </a>';
            // }
                        
            // $buttons .=         '<a href="'. route('kasPenerimaan.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
            //                         <i data-feather="printer"></i>
            //                         Print
            //                     </a>';
            
            $buttons .=         '<a href="'. route('kasPenerimaan.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
            
            // if (Auth::user()->can('kasPenerimaan-delete')) {
            //     $buttons .=         "<a href='javascript:;'
            //                         id='deleteButton'
            //                         class='dropdown-item'
            //                         data-toggle='modal'
            //                         data-target='#smallModal'
            //                         data-href='". route("kasPenerimaan.destroy", ['id'=>Crypt::encryptString($data->id)]) ."'>
            //                         <i data-feather='trash-2'></i>
            //                         Delete
            //                     </a>";
            // }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
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
        
        $poHdr=DB::table('purchase_order_hdr')
        ->where('id',$id)
        ->first();

        $poNumber=$poHdr -> po_number;
       

        $data['details']=DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','purchase_order_det.article_code')
        ->where('po_number',$poNumber)
        ->get();

        $data['totals']=DB::select("SELECT *,(gross-discount)+ppn as netto from (
            select a.po_number,authorized_by,validate_by,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from purchase_order_det a
            left join purchase_order_hdr b
            on a.po_number = b.po_number 
            where a.po_number = '$poNumber'
            group by a.po_number,authorized_by,validate_by) as oki");

        $data['suppliers']=DB::table('third_party')
        ->where('kode',$poHdr -> supplier_id)
        ->get();

        $data['keterangan']=$poHdr -> note;
        $data['poNumber'] =$poNumber;
        $data['poDate'] =$poHdr -> po_date;
        $data['poTerm'] =$poHdr -> termin;
        $data['poDelDate'] =$poHdr -> delivery_date;
        
        $data['status'] ='1';
        $data['no'] =1;

        view()->share($data);

        $pdf = PDF::loadView('bankPenerimaan.print');
        return $pdf->stream("PO_$poNumber.pdf");

    }

}
