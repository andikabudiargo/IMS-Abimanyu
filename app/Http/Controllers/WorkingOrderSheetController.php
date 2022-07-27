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

class WorkingOrderSheetController extends Controller
{   
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "WOS";
        $this->moduleCode = "WOS";
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;

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
            
        return view("workingOrderSheet.index",$data);
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
        $month = date('n');
        $year = date('Y');
        $woNumber="$key/ASN/$year/$month/$newCode";
        
        return $woNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Input $this->title";
        $data['subtitle'] = "Input $this->title";
       
        return view("workingOrderSheet.create",$data);

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
        $prdDate = $request->prdDate;
        $prdDate = date("Y-m-d", strtotime($prdDate) );
        $shift = $request->shift;
        $group = $request->group;
        $note = $request->note;
        $status = '1';

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
            // 'woNumber'=>'required|unique:purchase_order_hdr,po_number',
            'prdDate'  => 'required',
            'shift'  => 'required',
            'group'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save Production";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode('PRD');
            $prdNumber = $this->getLastCode('PRD');
            DB::beginTransaction();
            try {
                    DB::table('production_hdr')->insert([
                        
                        'prod_code' => $prdNumber,
                        'prod_date' => $prdDate,
                        'prod_shift' => $shift,
                        'prod_group' => $group,
                        'status' => $status,
                        'note' =>$note ,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'prod_code' => $prdNumber,
                            'so_code' => $val->so_code,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('production_det')->insert($dataSet);

                    DB::commit();
                    $title ='Save Production';
                    $alert  ="success";
                    $message  = "$title $prdNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ='Save Production';
                $alert  ="warning";
                $message  = "$title $prdNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber));
            }
        }
    }

    public function posting(Request $request)
    {
        // status
        // 1. Draft
        // 2. Update
        // 3. Posted
        // 4. Cancel

        $username =  Auth::user()->username;
        $prdNumber = $request->prodNumber;
        $recType = "NORMAL";
        $statusRec ="Posted";
        $status = '3';
        $authorizedBy = Auth::user()->username;

        // Update stock kalo article nya udah ada
        $sqlUpdate = "UPDATE article_stock a set article_qty = COALESCE(a.article_qty,0)  + COALESCE(b.qty_prod,0)
        from (
            select art_code, qty_prod from 
            (
            select *,article.article_code as art_code,o.qty as qty_prod from (
            select * from production_det where prod_code in (
            select prod_code from production_hdr where prod_code = '$prdNumber' and (status != '3' and status != '4'))) o
            left join article on article.article_code = o.article_code
            ) c
        ) b
        where a.article_code=b.art_code";

        $sqlIsiTemp="INSERT into production_detail_temp
                    select prod_code,prod_code,article_code,qty from production_det where prod_code = '$prdNumber'";

        $sqlUpdateChemical = "UPDATE article_stock a set article_qty = COALESCE(a.article_qty,0) - COALESCE(b.qty_total,0)
        from (
            select article_code,qty_total from 
            (SELECT article.article_code,article_alternative_code,article_desc,article.uom,qty,qty_proses,qty_total ,article.article_type,(select name from article_types where code = article.article_type) as kelompok from (
            select article_code,sum(oki.qty) as qty,sum(mari.qty) as qty_proses,sum(oki.qty*mari.qty) as qty_total from (
            select * from bom_det where bom_code in (
            select bom_code from bom_hdr 
            left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
            where bom_hdr.article_code in (select article_code from production_detail_temp))) oki
            left join(
            select bom_code,qty from bom_hdr 
            left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
            where bom_hdr.article_code in (select article_code from production_detail_temp)) mari
            on oki.bom_code= mari.bom_code
            group by article_code) so
            left join article on article.article_code = so.article_code) as oki
        ) b
        where a.article_code=b.article_code";

        //Insert ke stock kalo article nya belum ada
        $sqlInsert = "INSERT into article_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
        select 'HO',art_code,art_type,'00', qty_prod,art_uom from 
        (
            select *,
            article.article_code as art_code,
            article.uom as art_uom,
            article.article_type as art_type,
            z.qty as qty_prod
            from (
            select * from production_det where prod_code in (
                select prod_code from production_hdr where prod_code = '$prdNumber' and (status != '3' and status != '4')
            )
            ) z
            left join article on article.article_code = z.article_code
            where article.article_code not in (select article_code from article_stock)
        ) y";


        $sqlInsertChemical = "INSERT into article_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
        select 'HO',article_code,article_type,'00', qty_total,uom from 
        (
            select 'HO',article_code,article_type,'00', qty_total,uom from 
            (SELECT article.article_code,article_alternative_code,article_desc,article.uom,qty,qty_proses,qty_total ,article.article_type,(select name from article_types where code = article.article_type) as kelompok from (
            select article_code,sum(oki.qty) as qty,sum(mari.qty) as qty_proses,sum(oki.qty*mari.qty) as qty_total from (
            select * from bom_det where bom_code in (
            select bom_code from bom_hdr 
            left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
            where bom_hdr.article_code in (select article_code from production_detail_temp))) oki
            left join(
            select bom_code,qty from bom_hdr 
            left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
            where bom_hdr.article_code in (select article_code from production_detail_temp)) mari
            on oki.bom_code= mari.bom_code
            group by article_code) so
            left join article on article.article_code = so.article_code
            and article.article_code not in (select article_code from article_stock)
            ) as oki
        ) y
        where article_code is not null";
        
        //Insert into table movement
        $sqlMovement = "INSERT into movement
        (movement_date,
        artikel_code,
        artikel_desc,
        movement_min,
        movement_plus,
        movement_price,
        movement_transnno,
        movement_type,
        movement_desc)
        select 
            now()::timestamp::date,
            article_code,
            (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
            0,
            qty,
            (select price from sales_order_det where so_code = a.so_code and article_code = a.article_code limit 1) as price,
            prod_code,
            'PRD',
            prod_code
        from production_det a 
        where prod_code in (
            select prod_code 
            from production_hdr 
            where prod_code = '$prdNumber' and status = '3' and qty <> 0
        )";

        $sqlMovementChemical = "INSERT into movement
        (movement_date,
        artikel_code,
        artikel_desc,
        movement_min,
        movement_plus,
        movement_price,
        movement_transnno,
        movement_type,
        movement_desc)
        select 
            now()::timestamp::date,
            article_code,
            (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
            qty_total,
            0,
            0,
            '$prdNumber',
            'PRDBOM',
            '$prdNumber'
            from (
            select 'HO',article_code,article_type,'00', qty_total,uom from 
            (SELECT article.article_code,article_alternative_code,article_desc,article.uom,qty,qty_proses,qty_total ,article.article_type,(select name from article_types where code = article.article_type) as kelompok from (
            select article_code,sum(oki.qty) as qty,sum(mari.qty) as qty_proses,sum(oki.qty*mari.qty) as qty_total from (
            select * from bom_det where bom_code in (
            select bom_code from bom_hdr 
            left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
            where bom_hdr.article_code in (select article_code from production_detail_temp))) oki
            left join(
            select bom_code,qty from bom_hdr 
            left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
            where bom_hdr.article_code in (select article_code from production_detail_temp)) mari
            on oki.bom_code= mari.bom_code
            group by article_code) so
            left join article on article.article_code = so.article_code) oki) a";

    
        DB::select($sqlUpdate);
        DB::select($sqlIsiTemp);
        DB::select($sqlUpdateChemical);
        DB::select($sqlInsertChemical);
        DB::table('production_detail_temp')->where('code',$prdNumber)->delete();

        $rowAffected = DB::select($sqlInsert);
        if ($rowAffected > 0){
            DB::table('production_hdr')
            ->where('prod_code',$prdNumber)
            ->update(
                [   
                    'status' => $status,
                    // 'authorized_by' => $authorizedBy,
                    // 'authorized_at' => date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            DB::select($sqlMovement);
            DB::select($sqlMovementChemical);

            DB::commit();
            $title ='Posting Production';
            $alert  ="success";
            $message  = "$title $prdNumber is successfully posted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber));

        }else{
            DB::rollBack();
            $title ='Posting Production';
            $alert  ="warning";
            $message  = "$title $prdNumber is failed to post";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prdNumber'=>$prdNumber));
        }

    }

    public function show(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Edit Working Order";
        $data['subtitle'] = "Edit Working Order";

        $data['header'] = DB::table('purchase_order_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->where('po_number',$data['header']->po_number)
        ->select('purchase_order_det'.'.*','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();       

        $data['articles']= DB::table('article') 
        ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        // ->where('third_party',$data['header']->customer_id)
        ->where('article_type','CM')
        ->orderBy('article_desc')
        ->select('article'.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group')
        ->get();   

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        return view("purchaseOrder.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Edit Production";
        $data['subtitle'] = "Edit Production";

        $data['header'] = DB::table('production_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('production_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->where('po_number',$data['header']->po_number)
        ->select('purchase_order_det'.'.*','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();       

        $data['articles']= DB::table('article') 
        ->leftJoin('article_stock','article_stock.article_code','=','article.article_code')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        // ->where('third_party',$data['header']->customer_id)
        ->where('article_type','CM')
        ->orderBy('article_desc')
        ->select('article'.'.*', 'article_stock.article_qty as qty','article.uom as uom1','group_materials.name as group')
        ->get();   

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        return view("purchaseOrder.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $woNumber = $request -> woNumber;
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
            // 'woNumber'=>'required|unique:purchase_order_hdr,po_number',
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
                    ->where('po_number',$woNumber)
                    ->update(
                        [
                            'po_number' => $woNumber,
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
                            'prepared_by' =>  '',
                            'discount' => $discount,
                            'pkp' => $tax,
                            'termin' =>$termin,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $woNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $woNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('purchase_order_det')
                        ->whereNotIn(DB::raw("CONCAT(po_number,article_code)"),$dataSet)
                        ->where('po_number',$woNumber)
                        ->delete();

                    foreach ($articles as $val) {
                        DB::table('purchase_order_det')
                        ->updateOrInsert(
                            ['po_number' => $woNumber,'article_code' => $val->article_code],
                            [
                            'po_number' => $woNumber,
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
                    }
                    
                    DB::commit();
                    $alert  ="alert-success";
                    $message  = "PO $woNumber is successfully updated";
                    \LogActivity::addToLog('PO update ',"username: $username Status $message");
                    return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $alert  ="alert-warning";
                $message  = "PO $woNumber is failed to updated";
                \LogActivity::addToLog('PO update ',"username: $username Status $message");
                return response()->json(array('status' => 1, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));
            }
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
            $message  = "SO $po_number Successfully Deleted";
            \LogActivity::addToLog('SO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "SO $po_number Failed to Delete";
            \LogActivity::addToLog('SO delete ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        // status
        // 1. Draft
        // 2. Update
        // 3. Posted
        // 4. Cancel

        $searchPrd = strtolower($request->searchPrd);
        $articleCode = $request->articleCode;

        $data = DB::table('production_hdr')
        ->where(function ($query) use ($searchPrd,$articleCode) {
            $searchPrd ? $query->where('bom_code','ilike','%'.$searchPrd.'%') : '';
            $articleCode ? $query->where('bom_hdr.article_code','ilike','%'.$articleCode.'%') : '';
        })
        ->orderBy('prod_code')
        ->get(); 

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            // if (Auth::user()->can('purchaseOrder-edit')) {
            // $buttons .=         '<a href="'. route('production.edit', ['id'=>$data->id]) .'" class="dropdown-item">
            //                         <i data-feather="file-text"></i>
            //                         Edit
            //                     </a>';
            $buttons .=         '<a href="'. route('production.print', ['id'=>$data->id]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';
            // }

            if($data->status != '3'){
                $buttons .= '<a href="javascript:;"
                                onclick="posting(\''.$data->prod_code.'\')" class="dropdown-item">
                                <i data-feather="arrow-down"></i>
                                    Posting
                            </a>';
            }
            
            // $buttons .=         '<a href="'. route('production.show', ['id'=>$data->id]) .'" class="dropdown-item">
            //                         <i data-feather="list"></i>
            //                         Detail
            //                     </a>';
                
            // if (Auth::user()->can('purchaseOrder-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("production.destroy", ["id"=>$data->id]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Cancel
                                </a>";
            // }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })

        // status
        // 1. Draft
        // 2. Update
        // 3. Posted
        // 4. Cancel

        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $status = ['Draft','Update','Posted','Cancel'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $articles = json_decode($request -> articles);

        DB::table('production_detail_temp')
        ->delete();

        $dataSet = [];
        $randomCode = rand();
        foreach ($articles as $val) {
            $dataSet[] = [
                'code' => $randomCode,
                'article_code' => $val->article_code,
                'qty' => $val->qty,
            ];
        }

        DB::table('production_detail_temp')->insert($dataSet);

        $data=DB::select("SELECT so.article_code 
                                ,article_alternative_code
                                ,article_desc
                                ,article.uom
                                ,qty
                                ,qty_proses
                                ,qty_total 
                                ,article.article_type
                                ,(select name from article_types where code = article.article_type) as kelompok
         FROM (SELECT 
            article_code_det as article_code
            ,sum(qty_bom) as qty
            ,sum(qty_order) as qty_proses
            ,sum(qty_order * qty_bom) as qty_total
            ,uom_bom as uom 
            from(
            select 
            bom_det.article_code as article_code_det
            ,production_detail_temp.qty as qty_order
            ,production_detail_temp.uom as uom_order
            ,bom_det.qty * coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = production_detail_temp.uom),1) as qty_bom
            ,bom_det.uom as uom_bom
            ,bom_hdr.article_code 
            ,coalesce((select unit_factor from uom_con where unit_from = bom_det.uom_con and unit_to = production_detail_temp.uom),1) as factor_qty
            ,(select min_package from article where article_code = bom_det.article_code) as min_package 
            from production_detail_temp
            left join bom_hdr on bom_hdr.article_code=production_detail_temp.article_code
            join bom_det on  bom_det.bom_code = bom_hdr.bom_code
            where production_detail_temp.code ='$randomCode'
            and bom_hdr.status = '3'
            ) a
            group by article_code_det,uom_bom
            order by article_code_det) AS so
            left join article on article.article_code = so.article_code"
        );

        // $data=DB::select("SELECT article_alternative_code,article_desc,article.uom,qty,qty_proses,qty_total ,article.article_type,(select name from article_types where code = article.article_type) as kelompok from (
        //     select article_code,sum(oki.qty) as qty,sum(mari.qty) as qty_proses,sum(oki.qty*mari.qty) as qty_total 
        //     from (
        //         select * from bom_det where bom_code in (
        //             select bom_code from bom_hdr 
        //             left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
        //             where bom_hdr.article_code in (select article_code from production_detail_temp)
        //         )) oki
        //             left join(
        //                 select bom_code,qty 
        //                 from bom_hdr 
        //                 left join production_detail_temp on bom_hdr.article_code = production_detail_temp.article_code
        //                 where bom_hdr.article_code in (select article_code from production_detail_temp)
        //             ) mari
        //     on oki.bom_code= mari.bom_code
        //     group by article_code) so
        //     left join article on article.article_code = so.article_code");

        DB::table('production_detail_temp')
        ->where('code',$randomCode)
        ->delete();
                        
        return Datatables::of($data)
        ->make(true);
    }

    public function print(Request $request)
    {
        $id = $request -> id;
        
        $prdHdr=DB::table('production_hdr')
        ->where('id',$id)
        ->first();

        $data['header']=DB::table('production_hdr')
        ->where('id',$id)
        ->first();

        $prdNumber=$prdHdr -> prod_code;
       
        $data['details']=DB::table('production_det')
        ->leftJoin('article','article.article_code','production_det.article_code')
        ->where('prod_code',$prdNumber)
        ->get();

        $data['totals']=DB::select("SELECT sum(qty) as total_qty from production_det where prod_code = '$prdNumber' group by prod_code");

        $data['prdNumber'] = $prdNumber;
        $data['prdDate'] = $prdHdr -> prod_date;
        $data['prdShift'] = $prdHdr -> prod_shift;
        $data['prdGroup'] = $prdHdr -> prod_group;
        $data['no'] = 0;

        view()->share($data);

        $pdf = PDF::loadView('production.print');
        return $pdf->stream("PRD_$prdNumber.pdf");

    }
}
