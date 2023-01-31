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

class PettyCashController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "Petty Cash";

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Authorized
        // 4 = Received
        // 5 = Canceled
        // 6 = closed

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'AUTHORIZED','4'=>'RECEIVED','5'=>'CANCELED','6'=>"CLOSE"];
            
        return view("pettyCash.index",$data);
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
        $code="$key-ASN/$year/$month/$newCode";
        return $code;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create Petty Cash";
        $data['subtitle'] = "Create Petty Cash";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['pettyCash']= DB::table('pettycash_det') 
        ->orderBy('description')
        ->distinct('description')
        ->pluck('description');

        return view("pettyCash.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $details = json_decode($request->details);
        // $pcNumber = $request->pcNumber;
        $voucherNumber = $request->voucherNumber;
        $pcDate = $request->pcDate;
        $period = $request->period;
        $currency = $request->currency;
        $kurs = $request->kurs;
        $note = $request->note;
        $status = '1';
        $pcLeadCode ='PC';
        
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
            $alert ="alert-danger";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode($pcLeadCode);
            $pcNumber = $this->getLastCode($pcLeadCode);
            DB::beginTransaction();
            try {
                    DB::table('pettycash_hdr')->insert([
                        'pc_number' => $pcNumber,
                        'voucher_number' => $voucherNumber,
                        'pc_date' => $pcDate,
                        'period' => $period,
                        'year' => date('Y'),
                        'currency' => $currency,
                        'kurs' => $kurs,
                        // 'department' =>,
                        'note' => $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($details as $val) {
                        $dataSet[] = [
                            'pc_number' => $pcNumber,
                            'description' => $val->description,
                            'cg' => $val->cg ,
                            'cash_in' => $val->cash_in,
                            'cash_out' => $val->cash_out,
                            'account' => $val->account,
                            'created_by' => Auth::user()->username,
                            'updated_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('pettycash_det')->insert($dataSet);

                    DB::commit();
                    $title ='Save Petty Cash';
                    $alert  ="success";
                    $message  = "$title $pcNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'pcNumber'=>$pcNumber));
            } catch (Exception $e) {
                DB::rollBack();
                $title ='Save Petty Cash';
                $alert  ="warning";
                $message  = "PC $pcNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'pcNumber'=>$pcNumber));
            }
        }
    }

    public function show(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Detail Petty Cash";
        $data['subtitle'] = "Detail Petty Cash";

        $data['header'] = DB::table('purchase_order_hdr')
        // ->leftJoin('purchase_request_det','purchase_order_hdr.po_number','purchase_request_det.po_number')
        ->where('purchase_order_hdr.id',$id)
        ->get()->first();

        // $poNumber = explode("-",$data['header']->origin_po_number);
        // $poNumber = $poNumber[0].'-'.$poNumber[1];

        $poNumber = $data['header']->po_number;
        
        $data['prHeader'] = DB::table('purchase_request_det') 
        // ->where('supp_code',$data['header']->supplier_id)
        ->where('po_number','=',$poNumber)
        ->orderBy('pr_number')
        ->distinct('pr_number')
        ->get();

        $data['articles'] = DB::table('purchase_request_det')
            ->leftJoin('article','article.article_code','=','purchase_request_det'.'.article_code')
            ->leftJoin('article_stock','article_stock.article_code','=','purchase_request_det'.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            // ->where('supp_code',$data['header']->supplier_id)
            ->where('po_number','=',$poNumber)
            // ->where('pr_number','=',$data['header']->pr_number)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select('purchase_request_det'.'.*','article.article_alternative_code','article.article_code as artikel_code','article.article_desc','article.costprice','article_stock.article_qty as qty_stock','purchase_request_det.uom as uom1','group_materials.name as group')
            ->get();

        $data['detail'] = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->leftJoin('purchase_request_det', function($join) {
            $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
            ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        })
        ->where('purchase_order_det.po_number',$poNumber)
        ->select('purchase_order_det'.'.*','purchase_request_det.pr_number','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();       

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Authorized
        // 4 = Received
        // 5 = Canceled
        // 6 = closed
        // 7 = Revised

        $statusPo = ['New','Validated','Authorized','Received','Canceled','Closed','Revised'];
        $data['statusPo'] = $statusPo[$data['header']->status-1];

        return view("pettyCash.show",$data);
        
    }

    public function showEdit($key){
        $id=$key;
        $data['title'] = "Edit Petty Cash";
        $data['subtitle'] = "Edit Petty Cash";

        $data['header'] = DB::table('pettycash_hdr')
        ->where('id',$id)
        ->get()->first();

        $pcNumber = $data['header']->pc_number;
        
        $data['detail'] = DB::table('pettycash_det')
        ->where('pc_number',$pcNumber)
        ->orderBy('id')
        ->get();       
                
        return view("pettyCash.edit",$data);
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

    public function priceList(Request $request)
    {
        $articleCode = $request -> article;
        $listArticle = DB::table('purchase_order_det')
        ->leftJoin('purchase_order_hdr','purchase_order_hdr.po_number','purchase_order_det.po_number')
        ->where('article_code',$articleCode)
        ->select('purchase_order_det.po_number','po_date','price', 'purchase_order_hdr.created_at')
        ->orderBy('created_at','desc')
        ->limit(10)
        ->get();

        return Response()->json($listArticle);

    }

    public function list(Request $request)
    {
    
        $seachPc = strtolower($request->seachPc);
        $pcDate = $request->pcDate;
       

        $filter='';
        
        if ($seachPc !='' ){
            $filter.="lower(a.pc_number) like '%$seachPc%' and ";
        }        
        
        if ($pcDate  != '' ){
            $date = explode("to",$pcDate);
            $date1=trim($date[0]);
            $date2=trim($date[1]);
            $filter.= "to_date(created_at, 'DD/MM/YYYY')  BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
        }

        
        if ($filter !=''){
            $filter=" where ".substr($filter,0,-4);
        }

        $data=DB::select("SELECT * ,
        (select sum(cash_in) from pettycash_det where pc_number = a.pc_number) as cash_in,
        (select sum(cash_out) from pettycash_det where pc_number = a.pc_number) as cash_out
        from pettycash_hdr a
        $filter");        

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            if (Auth::user()->can('pettyCash-edit')) {
                $buttons .=         '<a href="'. route('pettyCash.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }
                        
            $buttons .=         '<a href="'. route('pettyCash.print', ['id'=>$data->id]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            
            $buttons .=         '<a href="'. route('pettyCash.show', ['id'=>$data->id]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
            
            if (Auth::user()->can('pettyCash-delete')) {
                $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("pettyCash.destroy", ["id"=>$data->id]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Delete
                                </a>";
            }

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

        $pdf = PDF::loadView('pettyCash.print');
        return $pdf->stream("PO_$poNumber.pdf");

    }

}
