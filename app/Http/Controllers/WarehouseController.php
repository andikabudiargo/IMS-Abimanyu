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

class WarehouseController extends Controller
{
    private $title;
    private $moduleCode;
    private $decimalPlaces;
    public function __construct()
    {
        $this->title = "WH";
        $this->moduleCode = "WH";
        $this->decimalPlaces = config('globalParam.decimal');
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'tr_number','name'=>'tr_number','title'=>'Tr Number'],
            ['data'=>'tr_date','name'=>'tr_date','title'=>'Date'],
            ['data'=>'tr_type','name'=>'tr_type','title'=>'Type'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'tr_number','name'=>'tr_number','title'=>'TSO Code'],
            ['data'=>'tr_date','name'=>'tr_date','title'=>'Date'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty Target'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date']
            
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnArticle(){
        $kolom=    
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=>false, 'searchable'=>false],
            ['data'=>'location_number','name'=>'location_number','title'=>'Location'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Code'],
            ['data'=>'critical_stock','name'=>'critical_stock','title'=>'Critical Stock'],
            ['data'=>'desc','name'=>'article_desc','title'=>'Name'],
            ['data'=>'cust','name'=>'third_party.nama','title'=>'Custs/Supp'],
            ['data'=>'costprice','name'=>'costprice','title'=>'Price'],
            ['data'=>'article_qty','name'=>'article_qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'safety_stock','name'=>'safety_stock','title'=>'Safety Stock'],
            ['data'=>'min_package','name'=>'min_package','title'=>'Min Package'],
            ['data'=>'last_rec_date','name'=>'last_rec_date','title'=>'Last Rec'],
            ['data'=>'group','name'=>'group_materials.name','title'=>'Group'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'note','name'=>'note','title'=>'Note']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnMovement(){
        $kolom=    
        [
            ['data'=>'location_number','name'=>'location_number','title'=>'Location'],
            ['data'=>'movement_code','name'=>'movement_code','title'=>'Code'],
            ['data'=>'movement_date','name'=>'movement_date','title'=>'Date'],
            ['data'=>'movement_type','name'=>'movement_type','title'=>'Type'],
            ['data'=>'movement_transnno','name'=>'movement_transnno','title'=>'Ref'],
            // ['data'=>'movement_price','name'=>'movement_price','title'=>'Price'],
            // ['data'=>'movement_min','name'=>'movement_min','title'=>'QTY Min'],
            // ['data'=>'movement_plus','name'=>'movement_plus','title'=>'QTY Plus'],
            ['data'=>'qty','name'=>'qty','title'=>'QTY'],
            ['data'=>'balanceqty','name'=>'balanceqty','title'=>'QTY Total'],
            ['data'=>'last_qty','name'=>'last_qty','title'=>'Last QTY'],
            ['data'=>'movement_desc','name'=> 'movement_desc','title'=>'Description'],
            ['data'=>'created_at','name'=> 'created_at','title'=>'Created At'],
            ['data'=>'urutan','name'=> 'urutan','title'=>'Runnng Number', 'searchable'=>false, 'visible'=>false]
        ];
        return json_encode($kolom, true);
    }

    public function article(Request $request)
    {
        $data['title'] = "Stock Article";

        $data['types'] = DB::table('article_types')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();
    
        $data['supps'] = DB::table('third_party')
        // ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();        

        $data['groups'] = DB::table('group_materials')
        ->where ('status','=',1)
        ->orderBy('name')
        ->get();

        $data['kolom'] = $this->getTableColoumnArticle();
        $data['kolomMovement'] = $this->getTableColoumnMovement();
        
        return view("warehouse.article",$data);
    }

    public function index(Request $request)
    {
        $data['title'] = "$this->title";
        $data['kolom'] = $this->getTableColoumn();
        $data['kolomDetail'] = $this->getTableColoumnDetail();
        
        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $data['type'] = ['TRIN'=>'TRANSFER IN','TROUT'=>'TRANSFER OUT'];
            
        return view("warehouse.index",$data);
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
        $poNumber="$key/$year/$month/$newCode";
        
        return $poNumber;
    }

    public function transferIn(Request $request)
    {
        $data['title'] = "$this->title Transfer In";
        $data['subtitle'] = "$this->title  Transfer In";
    
        return view("warehouse.transferIn",$data);
    }

    public function transferOut(Request $request)
    {
        $data['title'] = "$this->title  Transfer Out";
        $data['subtitle'] = "$this->title  Transfer Out";
        
        return view("warehouse.transferOut",$data);
    }

    
    public function posting(Request $request)
    {
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];

        $username =  Auth::user()->username;
        $trNumber = $request->trNumber;
        $trType = $request->trType;
        $statusRec ="POSTED";
        $status = '4';
        $authorizedBy = Auth::user()->username;
        $movementDate = date("d-m-Y");

        if ($trType =='TRIN'){
            // Update stock kalo article nya udah ada
            $sqlUpdate = "UPDATE warehouse_stock a set article_qty = COALESCE(a.article_qty,0) + COALESCE(b.qty,0)
            from (
            select art_code, (qty*factor_qty) as qty from 
            (
                select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = o.uom_tr and unit_to = article.uom) as factor_qty  from (
                select *,uom as uom_tr from transfer_det where tr_number in (
                select tr_number from transfer_hdr where tr_number = '$trNumber' and (status != '3' and status != '4'))) o
                left join article on article.article_code = o.article_code
            ) c
            ) b
            where a.article_code=b.art_code";

            //Insert ke stock kalo article nya belum ada
            $sqlInsert = "INSERT into warehouse_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
            select 'HO',art_code,article_type,'00',(qty*factor_qty) as qty,uom_tr from 
            (
                select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = z.uom_tr and unit_to = article.uom) as factor_qty from (
                select *,uom as uom_tr from transfer_det where tr_number in (
                select tr_number from transfer_hdr where tr_number = '$trNumber' and (status != '3' and status != '4'))) z
                left join article on article.article_code = z.article_code
                where article.article_code not in (select article_code from warehouse_stock)
            ) y";

            //Insert into table movement
            $sqlMovement = "INSERT into warehouse_movement
            (movement_date,artikel_code,artikel_desc,movement_min,movement_plus,movement_price,movement_transnno,movement_type,movement_desc)
            select 
            '$movementDate',
            article_code,
            (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
            0,
            qty,
            price,
            tr_number,
            '$trType',
            (select tr_number from transfer_hdr where tr_number=a.tr_number) as tr from transfer_det a where tr_number in (
            select tr_number from transfer_hdr where tr_number = '$trNumber' and status = '4' and qty <> 0)";
        }

        if ($trType =='TROUT'){
            // Update stock kalo article nya udah ada
            $sqlUpdate = "UPDATE warehouse_stock a set article_qty = COALESCE(a.article_qty,0) - COALESCE(b.qty,0)
            from (
            select art_code, (qty*factor_qty) as qty from 
            (
                select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = o.uom_tr and unit_to = article.uom) as factor_qty from (
                select *,uom as uom_tr from transfer_det where tr_number in (
                select tr_number from transfer_hdr where tr_number = '$trNumber' and (status != '3' and status != '4'))) o
                left join article on article.article_code = o.article_code
            ) c
            ) b
            where a.article_code=b.art_code";

            //Insert ke stock kalo article nya belum ada
            $sqlInsert = "INSERT into warehouse_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
            select 'HO',art_code,article_type,'00',(qty*factor_qty) as qty,uom_tr from 
            (
                select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = z.uom_tr and unit_to = article.uom) as factor_qty from (
                select *,uom as uom_tr from transfer_det where tr_number in (
                select tr_number from transfer_hdr where tr_number = '$trNumber' and (status != '3' and status != '4'))) z
                left join article on article.article_code = z.article_code
                where article.article_code not in (select article_code from warehouse_stock)
            ) y";

            //Insert into table movement
            $sqlMovement = "INSERT into warehouse_movement
            (movement_date,artikel_code,artikel_desc,movement_min,movement_plus,movement_price,movement_transnno,movement_type,movement_desc)
            select 
            '$movementDate',
            article_code,
            (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
            qty,
            0,
            price,
            tr_number,
            '$trType',
            (select tr_number from transfer_hdr where tr_number=a.tr_number) as tr from transfer_det a where tr_number in (
            select tr_number from transfer_hdr where tr_number = '$trNumber' and status = '4' and qty <> 0)";
        }
    
        DB::select($sqlUpdate);
        $rowAffected = DB::select($sqlInsert);
        
        if ($rowAffected > 0){
            DB::table('transfer_hdr')
            ->where('tr_number',$trNumber)
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

            DB::commit();
            $title ="Posting $this->title";
            $alert  ="success";
            $message  = "$title $trNumber Successfully Posted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            // return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber Failed to Posting";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
            // return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
        }
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $trDate = $request->trDate;
        $trType = $request->trType;
        $note = $request->note;
        $status = '1';
        $poLeadCode = $trType; 

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','5'=>'CANCELED'];

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
            'trDate'  => 'required'
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
            $hasilUpdate = AppHelpers::resetCode($poLeadCode);
            $trNumber = $this->getLastCode($poLeadCode);
            DB::beginTransaction();
            try {
                    DB::table('transfer_hdr')->insert([
                        'tr_number' => $trNumber,
                        'ref_number' => '' ,
                        'tr_date' => $trDate,
                        'tr_type' => $trType,
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
                            'tr_number' => $trNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('transfer_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $trNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $trNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));

            }
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('transfer_hdr')
        ->leftJoin('third_party','third_party.kode','transfer_hdr.customer_id')
        ->select('transfer_hdr.*'
        ,DB::raw("concat(third_party.kode,'-',third_party.nama) as customer")
        ,DB::raw('(select count(*) from transfer_det where tr_number = transfer_hdr.tr_number) as sum_row'))
        ->where('origin_tr_number', function($query) use ($id){
            $query->select('tr_number')->from('transfer_hdr')->where('id',$id);
        })
        ->get();

        $trNumber = $data['headers'][0]->tr_number;
        $customer = $data['headers'][0]->customer_id;
                
        $data['details'] = DB::table('transfer_det')
        ->whereIn('transfer_det.tr_number', function($query) use ($trNumber){
            $query->select('tr_number')->from('transfer_hdr')->where('origin_tr_number',$trNumber);
        })
        ->leftJoin('article','article.article_code','=','transfer_det.article_code')
        ->leftJoin('uom','uom.code','transfer_det.uom')
        ->where('transfer_det.tr_number',$trNumber)
        ->select('transfer_det'.'.*'
        ,'uom.uom_group as uom_group'
        ,DB::raw("concat(article.article_alternative_code,'-',article.article_desc) as article"))
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$trNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$trNumber,$username);
                   
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED'];
        $statusTso = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusTso'] = $statusTso[$data['headers'][0]->status-1];
        
        return view("warehouse.show",$data);        
    }

    public function detail(Request $request)
    {
        // $poNumber=$request->poNumber;
        // $detail = DB::table('purchase_order_det')
        // ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        // ->leftJoin('warehouse_stock','warehouse_stock.article_code','=','purchase_order_det.article_code')
        // ->leftJoin('purchase_request_det', function($join) {
        //     $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
        //     ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        // })
        // ->leftJoin('uom','uom.code','=','purchase_order_det.uom')
        // ->where('purchase_order_det.po_number',$poNumber)
        // ->select('purchase_order_det'.'.*'
        //     ,'purchase_order_det.pr_number'
        //     ,'warehouse_stock.article_qty as qty_stock'
        //     ,'uom.uom_group'
        //     , DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        // ->orderBy('id')
        // ->get();

        return response()->json(array('status' => 0, 'data' => $detail));

    }

    public function showEdit($key)
    {
        $id=Crypt::decryptString($key);
        $username =  Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('transfer_hdr')
        ->leftJoin('third_party','third_party.kode','transfer_hdr.customer_id')
        ->select('transfer_hdr.*',DB::raw("concat(third_party.kode,'-',third_party.nama) as customer"))
        ->where('transfer_hdr.id',$id)
        ->get()->first();

        $trNumber = $data['header']->tr_number;
        $customer = $data['header']->customer_id;

        $data['articles'] = DB::table('article')
            ->leftJoin('uom','uom.code','=','article.uom')
            ->where('third_party',$customer)
            ->whereIn('article_type',['FG'])
            ->orderBy('article_desc')
            ->get();
                
        $data['details'] = DB::table('transfer_det')
        ->leftJoin('article','article.article_code','=','transfer_det.article_code')
        ->where('transfer_det.tr_number',$trNumber)
        ->select('transfer_det'.'.*')
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$trNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$trNumber,$username);
                   
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'CANCELED'];
        $statusTso = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
        $data['statusPo'] = $statusTso[$data['header']->status-1];

        return view("warehouse.edit",$data);
    }

    public function edit(Request $request)
    {
        return $this->showEdit($request->id);
    }

    // public function revision(Request $request){
    //     $username =  Auth::user()->username;
    //     $id=Crypt::decryptString($request->id);
    //     $poOrigin=DB::table('purchase_order_hdr')->where('id',$id)->value('po_number');
    //     $numRevision = $request->nR ? $request->nR +1 : 1 ;
    //     $poNew = $poOrigin.'-R'.$numRevision;
    //     $checkNewPo=DB::table('purchase_order_hdr')->where('po_number',$poNew)->count();

    //     if ($checkNewPo > 0){
    //         $poNew = $poOrigin.'-R'.$numRevision+1;
    //     } 
                
    //     $sqlHdr = "INSERT into purchase_order_hdr 
    //     (
    //         po_number,
    //         origin_po_number,
    //         supplier_id,
    //         po_date,
    //         delivery_date,
    //         currency,
    //         authorized_by,
    //         authorized_at,
    //         validate_by,
    //         discount,
    //         kurs,
    //         pkp,
    //         ppn,
    //         pph22,
    //         termin,
    //         order_type,
    //         status,
    //         num_revision,
    //         revised_by,
    //         revised_at,
    //         note,
    //         created_by,
    //         updated_by,
    //         created_at,
    //         updated_at
    //     )
    //     select 
    //         '$poNew',
    //         '$poOrigin',
    //         supplier_id,
    //         po_date,
    //         delivery_date,
    //         currency,
    //         authorized_by,
    //         authorized_at,
    //         validate_by,
    //         discount,
    //         kurs,
    //         pkp,
    //         ppn,
    //         pph22,
    //         termin,
    //         order_type,
    //         '7',
    //         $numRevision,
    //         '$username',
    //         '".date('Y-m-d H:i:s')."',
    //         note,
    //         '$username',
    //         '$username',
    //         '".date('Y-m-d H:i:s')."',
    //         '".date('Y-m-d H:i:s')."'
    //     from purchase_order_hdr where po_number = '$poOrigin'";

    //     $sqlDet="INSERT into purchase_order_det
    //     (
    //         po_number,
    //         pr_number,
    //         article_code,
    //         qty,
    //         uom,
    //         old_price,
    //         price,
    //         ppn,
    //         pph22,
    //         created_by,
    //         updated_by,
    //         created_at,
    //         updated_at
    //     )
    //     select '$poNew',
    //         pr_number,
    //         article_code,
    //         qty,
    //         uom,
    //         old_price,
    //         price,
    //         ppn,
    //         pph22,
    //         '$username',
    //         '$username',
    //         '".date('Y-m-d H:i:s')."',
    //         '".date('Y-m-d H:i:s')."' 
    //     from purchase_order_det where po_number = '$poOrigin'";

    //     $rowAffected =  DB::select($sqlHdr);
    //     if ($rowAffected){
    //         DB::select($sqlDet);

    //         // status:
    //         // 1 = New
    //         // 2 = Validated
    //         // 3 = Authorized
    //         // 4 = Received
    //         // 5 = Canceled
    //         // 6 = closed
    //         // 7 = Revised

    //         DB::table('purchase_order_hdr')
    //         ->where('po_number',$poOrigin)
    //         ->update(
    //             [
    //                 'num_revision' => $numRevision,
    //                 'status' => '1',
    //                 'revised_by'=>Auth::user()->username,
    //                 'revised_at'=> date('Y-m-d H:i:s'),
    //                 'updated_by' => Auth::user()->username,
    //                 'updated_at' => date('Y-m-d H:i:s')
    //             ]
    //         );

    //         DB::table('approval_history')
    //         ->where('module_number',$poOrigin)
    //         ->update(
    //             [
    //                 'module_number' => $poNew,
    //                 'status' => '0',
    //                 'updated_by' => Auth::user()->username,
    //                 'updated_at' => date('Y-m-d H:i:s')
    //             ]
    //         );
            
    //         $title ="Save $this->title";
    //         $alert  ="success";
    //         $message  = "$title Revision PO: $poOrigin to $poNew is successfully saved";
    //         \LogActivity::addToLog($title,"username: $username Status $message");
    //         // return $this->showEdit(Crypt::encryptString($id));
    //         return redirect()->route('warehouse.edit', ['id'=>Crypt::encryptString($data->id)]);
    //     }else{
    //         $title ="Save $this->title";
    //         $alert  ="warning";
    //         $message  = "$title Revision PO: $poOrigin to $poNew is failed to save";
    //         \LogActivity::addToLog($title,"username: $username Status $message");
    //         return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
    //     }
        
    // }

    public function update(Request $request)
    {

        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $trNumber = $request->trNumber;
        $trDate = $request->trDate;
        $tsoName = $request->tsoName;
        $customer = $request->customer;
        $note = $request->note;
              
        $statusSimpan = $request->statusSimpan;
        if ( $statusSimpan == 'approve' ){
            $maxLevel = $request->maxLevel;
            $approveLevel  = $request->approveLevel;
            $status = $approveLevel === $maxLevel ? '3' : '2';
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
            'trDate'  => 'required',
            'tsoName'  => 'required',
            'customer'  => 'required',

        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }

            $alert ="warning";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));

        }else{
            DB::beginTransaction();
            try {
                    $row_affected=DB::table('transfer_hdr')
                    ->where('tr_number',$trNumber)
                    ->update(
                        [
                            'tr_number' => $trNumber,
                            'tso_name' => $tsoName ,
                            'status' => $status,
                            'note' => $note,
                            'updated_by' => Auth::user()->username,
                        ]
                    );

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $trNumber.$val->article_code
                        ];
                        
                    }

                    //Delete kalo article tidak ada di po $trNumber dan article nya $val->article_code
                    //berdasarkan 2 kondisi
                    DB::table('transfer_det')
                        ->whereNotIn(DB::raw("CONCAT(tr_number,article_code)"),$dataSet)
                        ->where('tr_number',$trNumber)
                        ->delete();
                                  
                    foreach ($articles as $val) {
                        DB::table('transfer_det')
                        ->updateOrInsert(
                            ['tr_number' => $trNumber,'article_code' => $val->article_code],
                            [
                            'tr_number' => $trNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                            ]
                        );
                    }
                   
                    if ( $statusSimpan == 'approve' ){
                        DB::table('approval_history')->insert([
                            'module_code' => $this->moduleCode,
                            'module_number' => $trNumber,
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

                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $trNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert ="warning";
                $message  = "$title $trNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prNumber'=>$trNumber));
            }
        }

    }

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $trNumber = $request->trNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$trNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusTso = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('transfer_hdr')
                ->where('tr_number',$trNumber)
                ->update(
                    [
                        'status' => $statusTso,
                        'authorized_by' => Auth::user()->username,
                        'authorized_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $trNumber,
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
                $message  = "$title $trNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$trNumber));
        }
    }

    // public function decline(Request $request)
    // {
    //     $username =  Auth::user()->username;
    //     $poNumber = $request->poNumber;
    //     $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$poNumber,$username);        
    //     $nextLevel = $statusLevelApproval[0]->next_level;
    //     $statusTso = '8';
                
    //     DB::beginTransaction();
    //     try {
    //             $row_affected=DB::table('purchase_order_hdr')
    //             ->where('po_number',$poNumber)
    //             ->update(
    //                 [
    //                     'status' => $statusTso,
    //                     'authorized_by' => Auth::user()->username,
    //                     'authorized_at' => date('Y-m-d H:i:s')
    //                 ]
    //             );

    //             if ($row_affected){
    //                 DB::table('approval_history')->insert([
    //                     'module_code' => $this->moduleCode,
    //                     'module_number' => $poNumber,
    //                     'username' => Auth::user()->username,
    //                     'approval_order' => $nextLevel,
    //                     'approval_date' => date('Y-m-d'),
    //                     'status' => 0,
    //                     'created_by' => Auth::user()->username,
    //                     'updated_by' => Auth::user()->username,
    //                     'created_at' => date('Y-m-d H:i:s'),
    //                     'updated_at' => date('Y-m-d H:i:s')
    //                 ]);
    //             }
                
    //             DB::commit();
    //             $title ="Decline $this->title";
    //             $alert  ="success";
    //             $message  = "$title $poNumber is successfully decline";
    //             \LogActivity::addToLog($title,"username: $username Status $message");
    //             return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$poNumber));

    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         $title ="Decline $this->title";
    //         $alert  ="warning";
    //         $message  = "$title $poNumber is failed to decline";
    //         \LogActivity::addToLog($title,"username: $username Status $message");
    //         return response()->json(array('statusPo' => $statusTso,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'trNumber'=>$poNumber));
    //     }
    // }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $trNumber = DB::table('transfer_hdr')->where('id',$id)->where('status','1')->first();
        $trNumber = $trNumber->tr_number;
        $tsoStatus = $trNumber->status;
        if ($tsoStatus == 1){
            $rowAffected = DB::table('transfer_hdr')->where('id',$id)->where('status','1')->delete();
        }else{
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','','5'=>'CANCELED'];
            $row_affected=DB::table('transfer_hdr')
            ->where('tr_number',$trNumber)
            ->update(
                [
                    'status' => '3',
                    'authorized_by' => Auth::user()->username,
                    'authorized_at' => date('Y-m-d H:i:s')
                ]
            );
        }
        if($rowAffected>0){
            DB::table('transfer_det')->where('po_number',$trNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $trNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $trNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }
    }

    // public function clear(Request $request)
    // {
    //     //memutihkan PO supaya tidak bisa di pakai lagi
    //     //status PO jadi closed
    //     $username =  Auth::user()->username;       
    //     $id=Crypt::decryptString($request->id);
    //     $po_number = DB::table('purchase_order_hdr')->where('id',$id)->value('po_number');
    //     $status = '6';
    //     DB::beginTransaction();
    //     try {
    //             $row_affected=DB::table('purchase_order_hdr')
    //             ->where('id',$id)
    //             ->update(
    //                 [
    //                     'status' => $status,
    //                     'updated_by' => Auth::user()->username,
    //                     'updated_at' => date('Y-m-d H:i:s')
    //                 ]
    //             );
                
    //             DB::commit();
    //             $title ="Clear $this->title";
    //             $alert  ="success";
    //             $message  = "$title $po_number Successfully Closed";
    //             \LogActivity::addToLog($title,"username: $username Status $message");
    //             return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         $title ="Clear $this->title";
    //         $alert  ="warning";
    //         $message  = "$title $po_number Failed to Close";
    //         \LogActivity::addToLog($title,"username: $username Status $message");
    //         return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
    //     }

    // }

    // public function priceList(Request $request)
    // {
    //     $articleCode = $request -> article;
    //     $listArticle = DB::table('purchase_order_det')
    //     ->leftJoin('purchase_order_hdr','purchase_order_hdr.po_number','purchase_order_det.po_number')
    //     ->where('article_code',$articleCode)
    //     ->select('purchase_order_det.po_number','po_date','price', 'purchase_order_hdr.created_at')
    //     ->orderBy('po_date','desc')
    //     ->where('status','<>','7')
    //     ->limit(10)
    //     ->get();

    //     return Response()->json($listArticle);

    // }

    public function list(Request $request)
    {
        $username = Auth::user()->username;
        $searchTr = strtolower($request->searchTr);
        $searchType = $request->searchType;
        $searchStatus = $request->searchStatus;
        $trDate = $request->trDate;
        $fromDate ="";
        $toDate = "";
        if ($trDate){
            $date = explode("to",$trDate);
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

        $data = DB::table('transfer_hdr')
        ->where(function ($query) use ($searchTr,$searchStatus,$trDate,$fromDate,$toDate,$searchType) {
            $searchType ? $query->where('tr_type',$searchType) : '';
            $searchTr ? $query->where('tr_number','ilike','%'.$searchTr.'%') : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
            $trDate ? $query->whereBetween(DB::raw("to_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->orderBy('id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            if ( $data->status == '2' or $data->status == '1') {
                // if (Auth::user()->can('purchaseOrder-authorize')) {
                $buttons .=         '<a href="'. route('warehouse.posting', ['trNumber'=>$data->tr_number,'trType'=>$data->tr_type]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        <span>'. __("Posting") .'</span>
                                    </a>';
                // }
            }
            
            if ( $data->status == '1' or $data->status == '2' ){
                if (Auth::user()->can('purchaseOrder-edit')) {
                $buttons .=         '<a href="'. route('warehouse.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('tr_number', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTso = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
            // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','','5'=>'CANCELED'];
            return '<span style="display: none;">'.$data->tr_number.'</span><a class="text-left badge d-block '.$badges[$data->status - 1].'" name="'.$data->tr_number.'" href="'. route('warehouse.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->tr_number.'</span></a>';
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTso = ['NEW','VALIDATED','APPROVED','POSTED','CANCELED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTso[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','tr_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {
        $searchTr = strtolower($request->searchTr);
        $username = Auth::user()->username;
        $searchType = $request->searchType;
        $searchStatus = $request->searchStatus;
        $trDate = $request->trDate;
        $fromDate ="";
        $toDate = "";
        
        if ($trDate){
            $date = explode("to",$trDate);
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

        $data = DB::table('transfer_det')
        ->leftJoin('transfer_hdr','transfer_hdr.tr_number','transfer_det.tr_number')
        ->leftJoin('article','article.article_code','transfer_det.article_code')
        ->leftJoin('uom','uom.code','transfer_det.uom')
        ->where(function ($query) use ($searchTr,$searchStatus,$trDate,$fromDate,$toDate,$searchType) {
            $searchType ? $query->where('tr_type',$searchType) : '';
            $searchTr ? $query->where('tr_number','ilike','%'.$searchTr.'%') : '';
            $searchStatus ? $query->where('transfer_hdr.status',$searchStatus) : '';
            $trDate ? $query->whereBetween(DB::raw("to_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('transfer_det.*'
        ,'transfer_hdr.*'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'uom_group'
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty,'999,999,999.999') end as qty")
        )
        ->orderBy('transfer_det.id')
        ->get(); 
       
        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusTso = ['NEW','VALIDATED','POSTED','APPROVED'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusTso[$data->status - 1]."</div>";
        })
        ->rawColumns(['status'])
        ->make(true);
    }

    public function listArticle(Request $request)
    {
        $code = strtolower($request->code);
        $name = strtolower($request->name);
        $group = strtolower($request->group);
        $supp = strtolower($request->supp);
        $type = strtolower($request->type);
        $status = $request->status;
        $qty = $request->qty;
        $operator = $request->opr;
       
        $data=DB::table('article')
        ->select('article.*'
        ,'costprice'
        ,'article.article_code as art_code'
        ,'article_alternative_code as code'
        ,'article_desc as desc'
        ,'article.uom'
        ,'quality'
        ,'note'
        ,'article.id'
        ,'group_materials.name as group'
        ,'third_party.nama as cust'
        // ,'article_stock.article_qty as article_qty'
        ,'safety_stock'
        ,'min_package'
        ,'uom.uom_group'
        ,'location_number'
        ,DB::raw("last_rec_date(article.article_code) as last_rec_date")     
        ,DB::raw("(select sum(movement_plus) - sum(movement_min) as total_qty from  movement where artikel_code = article.article_code group by artikel_code) as article_qty")
        // ,DB::raw("case when uom.uom_group = 'PIECE' then TO_CHAR(article_stock.article_qty,'999,999,999') else TO_CHAR(article_stock.article_qty,'999,999,999.99') end as article_qty"))
        )
        ->leftJoin('group_materials', 'group_materials.code', '=', 'article.group_of_material')
        ->leftJoin('third_party', 'third_party.kode', '=', 'article.third_party')
        ->leftJoin('article_stock', 'article_stock.article_code', '=', 'article.article_code')
        ->leftJoin('uom','uom.code','article.uom')
        ->where(function ($query) use ($code,$name,$group,$supp,$type,$operator,$qty,$status) {
            $code ? $query->where('article_alternative_code','ilike','%'.$code.'%') :'';
            $name ? $query->where('article_desc','ilike','%'.$name.'%') :'';
            $group ? $query->where('group_of_material','ilike','%'.$group.'%') :'';
            $supp ? $query->where('third_party','ilike','%'.$supp.'%') :'';
            $type ? $query->where('article_alternative_code','ilike',$type.'%') :'';
            $operator ? $query->where('article_stock.article_qty',$operator,(float)$qty) :'';
            if($status == 'critical'){
                $query->where('article_stock.article_qty','<',db::raw("safety_stock"));
            }else if ($status == 'save'){
                $query->where('article_stock.article_qty','>',db::raw("safety_stock"));
            }
        })->orderBy('article_desc')->get();
       
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
        
            $buttons .=         "<a href='javascript:;' onclick='movement(\"".$data->art_code."\",\"".$data->code."\",\"".preg_replace('/\'/','',$data->desc)."\")' class='dropdown-item'>
                                    <i data-feather='activity'></i>
                                    Movement
                                </a>";
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('article_qty', function ($data) {
            // $artilceQty = $data->uom_group =='PIECE' ? number_format($data->article_qty) : number_format($data->article_qty,3);
            if (fmod($data->article_qty,1) !== 0.00){
                $decimal = $this->decimalPlaces;
            }else{
                $decimal = 0;
            }
            $artilceQty = number_format($data->article_qty,$decimal);
            return $data->article_qty < 0 ? "<div class='text-red'>$artilceQty</div>" : "<div class='text-hitam'>$artilceQty</div>";
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-light-danger','badge-light-primary'];
            $statusCode = ['Freeze','Active'];
            return "<div class='badge badge-pill ".$badges[$data->status]."'>".$statusCode[$data->status]."</div>";
        })
        ->addColumn('critical_stock', function ($data) {

            if ($data->article_qty < $data->safety_stock){
                return "<div class='badge badge-pill badge-light-danger'>Critical</div>";
            }else{
                return "<div class='badge badge-pill badge-light-primary'>Save</div>";
            }
        })
        ->rawColumns(['action','status','article_qty','critical_stock'])
        ->make(true);
    }

}
