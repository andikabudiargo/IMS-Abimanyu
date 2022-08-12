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

class WorkingOrderSheetController extends Controller
{   
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "WOS";
        $this->moduleCode = "WO";
    }

    public function getTableColoumn()
    {
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'wo_code','name'=>'wo_code','title'=>'Wo Code'],
            ['data'=>'wo_shift','name'=>'wo_shift','title'=>'Shift'],
            ['data'=>'wo_group','name'=>'wo_group','title'=>'Group'],
            ['data'=>'start_time','name'=>'start_time','title'=>'Start Time'],
            ['data'=>'working_hour','name'=>'working_hour','title'=>'Working Hour'],
            ['data'=>'status','name'=>'status','title'=>'Status']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail()
    {
        $kolom=
        [
            ['data'=>'urutan','name'=>'urutan','title'=>'Urutan'],
            ['data'=>'wo_code','name'=>'wo_code','title'=>'Wo Code'],
            ['data'=>'so_code','name'=>'so_code','title'=>'So Code'],
            ['data'=>'wo_shift','name'=>'wo_shift','title'=>'Shift'],
            ['data'=>'wo_group','name'=>'wo_group','title'=>'Group'],
            ['data'=>'start_time','name'=>'start_time','title'=>'Start Time'],
            ['data'=>'working_hour','name'=>'working_hour','title'=>'Working Hour'],
            ['data'=>'so_qty','name'=>'so_qty','title'=>'So Qty'],
            ['data'=>'article_fg','name'=>'article_fg','title'=>'Article FG'],
            ['data'=>'article_fg_desc','name'=>'article_fg_desc','title'=>'Desc'],
            ['data'=>'article_rm','name'=>'article_rm','title'=>'Article RM'],
            ['data'=>'article_rm_desc','name'=>'article_rm_desc','title'=>'Desc'],
            ['data'=>'plan_time_loading','name'=>'plan_time_loading','title'=>'Plan Time'],
            ['data'=>'plan_qty_fresh','name'=>'plan_qty_fresh','title'=>'Plan Qty Frsh'],
            ['data'=>'plan_qty_repaint','name'=>'plan_qty_repaint','title'=>'Plan Qty Rep'],
            ['data'=>'plan_tag','name'=>'plan_tag','title'=>'Tag'],
            ['data'=>'note_hdr','name'=>'note_hdr','title'=>'Note']

        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['subtitle'] = "$this->title";

        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVE','4'=>'PROCESS','5'=>'CANCELED'];

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
        $data['oEdit']=false;
        $data['statusWo']='NEW';
               
        return view("workingOrderSheet.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);        
        $woDate = date("Y-m-d", strtotime($request->wosDate));
        $woShift = $request->shift;
        $woGroup = $request->group;
        $woTime = $request->wosTime;
        $workHour = $request->workHour;
        $note = $request->note;
        $status = '1';

        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])->count();
        });

        $validation = Validator::make($request->all(),$messages = [
            // 'wosDate'  => 'required',
            // 'wosGroup'  => 'required',
            // 'wosShift'  => 'required',
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
            $hasilUpdate = AppHelpers::resetCode($this->moduleCode);
            $woNumber = $this->getLastCode($this->moduleCode);
            DB::beginTransaction();
            try {
                    DB::table('wo_hdr')->insert([
                        'wo_code' =>$woNumber,
                        'original_wo_code' =>$woNumber,
                        'wo_date' => $woDate,
                        'wo_shift' => $woShift,
                        'wo_group' => $woGroup,
                        'start_time' => $woTime,
                        'working_hour'=> $workHour,
                        'num_revision' => 0,
                        'status' => $status,
                        'note' => $note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            "wo_code" => $woNumber,
                            "so_code" => $val->so_code,
                            "so_qty" => $val->qty_so,
                            "urutan" => $val->urutan,
                            "article_code" => $val->article_code,
                            "article_rm_code" => $val->article_rm,
                            "plan_time_loading" => $val->waktu,
                            "act_time_loading" => 0,
                            "plan_qty_fresh" => $val->qty_prod,
                            "plan_qty_repaint" => $val->qty_repaint,
                            "plan_tag" => $val->tag,
                            "act_qty_fresh" => 0,
                            "act_qty_repaint" => 0,
                            "act_tag" => 0,
                            "origin_tag" => $val->tag_asli,
                            "qty_ok" => 0,
                            "qty_repair" => 0,
                            "qty_repaint" => 0,
                            "created_by" => Auth::user()->username,
                            "status" => $val->status,
                            "created_at" => date('Y-m-d H:i:s')
                        ];
                    }

                    DB::table('wo_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $woNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $woNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'woNumber'=>$woNumber));
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
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('wo_hdr')
        ->where('id',$id)
        ->get()->first();

        $woCode = $data['header']->wo_code;

        $data['details'] = DB::table('wo_det')
        ->leftJoin('article','article.article_code','=','wo_det.article_code')
        ->where('wo_code',$woCode)
        // ->select('wo_det'.'.*','article_stock.article_qty as qty_stock', DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('urutan')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$woCode,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$woCode,$username);

        $data['oEdit']=true;

         // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED'];
         $statusWo = ['NEW','VALIDATED','APPROVED','PROCESS','CANCELED'];
         $data['statusWo'] = $statusWo[$data['header']->status-1];

        return view("workingOrderSheet.edit",$data);
        
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

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVE','4'=>'PROCESS','5'=>'CANCELED'];
        
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
        $username = Auth::user()->username;
        $searchWos = strtolower($request->searchWos);
        $searchStatus = $request->searchStatus;
        $wosDate = $request->wosdate;        
        $fromDate ="";
        $toDate = "";
        if ($wosDate){
            $date = explode("to",$wosDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }
        
        $data = DB::table('wo_hdr')
        ->where(function ($query) use ($searchWos,$searchStatus,$wosDate,$fromDate,$toDate) {
            $searchWos ? $query->where('wo_code','ilike','%'.$searchWos.'%') : '';
            $searchStatus ? $query->where('wo_hdr.status','=','%'.$searchStatus.'%') : '';
            $wosDate ? $query->whereBetween(DB::raw("to_date(wo_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->orderBy('wo_code')
        ->get(); 

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';

            if (Auth::user()->can('workingOrder-edit')) {
            $buttons .=         '<a href="'. route('workingOrderSheet.edit',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="file-text"></i>
                                    Edit
                                </a>';
            }

            $buttons .=         '<a href="'. route('workingOrderSheet.print',['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    Print
                                </a>';

            if($data->status != '3'){
                $buttons .= '<a href="javascript:;"
                                onclick="posting(\''.$data->wo_code.'\')" class="dropdown-item">
                                <i data-feather="arrow-down"></i>
                                    Posting
                            </a>';
            }
            
            $buttons .=         '<a href="'. route('workingOrderSheet.show',['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            if (Auth::user()->can('workingOrder-delete')) {
            $buttons .=         "<a href='javascript:;'
                                    id='deleteButton'
                                    class='dropdown-item'
                                    data-toggle='modal'
                                    data-target='#smallModal'
                                    data-href='". route("workingOrderSheet.destroy", ["id"=>$data->id]) ."'>
                                    <i data-feather='trash-2'></i>
                                    Cancel
                                </a>";
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            $status = ['NEW','VALIDATE','APPROVE','PROCESS','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $username = Auth::user()->username;
        $searchWos = strtolower($request->searchWos);
        $searchStatus = $request->searchStatus;
        $wosDate = $request->wosdate;        
        $fromDate ="";
        $toDate = "";
        if ($wosDate){
            $date = explode("to",$wosDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }
        
        $data = DB::table('wo_det')
        ->leftJoin('wo_hdr','wo_hdr.wo_code','wo_det.wo_code')
        ->leftJoin('article as fg','fg.article_code','wo_det.article_code')
        ->leftJoin('article as rm','rm.article_code','wo_det.article_code')
        ->where(function ($query) use ($searchWos,$searchStatus,$wosDate,$fromDate,$toDate) {
            $searchWos ? $query->where('wo_code','ilike','%'.$searchWos.'%') : '';
            $searchStatus ? $query->where('wo_hdr.status','=','%'.$searchStatus.'%') : '';
            $wosDate ? $query->whereBetween(DB::raw("to_date(wo_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('wo_det.*','wo_hdr.*'
        ,'fg.article_alternative_code as article_fg'
        ,'fg.article_desc as article_fg_desc'
        ,'rm.article_alternative_code as article_rm'
        ,'rm.article_desc as article_rm_desc'
        ,'wo_hdr.note as note_hdr'
        )
        ->orderBy('urutan')
        ->orderBy('wo_det.wo_code')
        ->get(); 
                        
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
