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

class DeliveryInstructionController extends Controller
{
    private $title;
    private $moduleCode;
    public function __construct()
    {
        $this->title = "Delivery Instruction";
        $this->moduleCode = "DI";
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'di_number','name'=>'di_number','title'=>'DI Number'],
            ['data'=>'supp_name','name'=>'supp_name','title'=>'Supplier'],
            ['data'=>'di_date','name'=>'di_date','title'=>'DI Date'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
        
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'di_number','name'=>'di_number','title'=>'DI Number'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'supplier_id','name'=>'supplier_id','title'=>'Supplier code'],
            ['data'=>'supp_name','name'=>'supp_name','title'=>'Supplier'],
            ['data'=>'di_date','name'=>'di_date','title'=>'DI Date'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qty','name'=>'qty','title'=>'Qty'],
            ['data'=>'uom','name'=>'uom','title'=>'UOM'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
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
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'','6'=>'','7'=>'REVISED','8'=>''];
            
        return view("deliveryInstruction.index",$data);
    }

    public function articleList(Request $request)
    {
        $username =  Auth::user()->username;
        $supplier = $request->supplier; 

        // status PO
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];


        // left join 
        //             (select po, article_code,sum(qty) as qty,price from (
        //                 select *,(select po_number from receiving_hdr 
        //                            where rec_number = a.rec_number) as po from receiving_det a where rec_number in (
        //                            select rec_number from receiving_hdr where status = '3' and po_number = '$po')
        //             ) z
        //         group by po, article_code,price) b
        //         on a.po_number = b.po and a.article_code = b.article_code

        $data= DB::table('purchase_order_det')
        ->leftJoin('article_stock','article_stock.article_code','purchase_order_det.article_code')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('purchase_order_hdr','purchase_order_hdr.po_number','purchase_order_det.po_number')
        ->leftJoin(DB::raw("(select po, article_code,sum(qty) as qty_rec,price from (
            select *,(select po_number from receiving_hdr 
            where rec_number = a.rec_number) as po from receiving_det a where rec_number in (
            select rec_number from receiving_hdr where status = '3' and po_number = 'purchase_order_det.po_number')
            ) z
            group by po, article_code,price) oki"),function($join){
               $join->on('purchase_order_det.po_number','oki.po');
            })
        ->whereIn('purchase_order_det.po_number', function ($query) use ($supplier) {
            $query->select('po_number')
            ->from('purchase_order_hdr')
            ->where('status','3')
            ->where('supplier_id',$supplier);
        })
        ->select(
        // 'purchase_order_det.*',
        // ,DB::raw("COALESCE(purchase_order_det.qty,0) - COALESCE(qty_rec,0) as qty_order")
        // ,'article_stock.article_qty as qty_stock'
        // ,'purchase_order_hdr.supplier_id'
        // ,'article.article_alternative_code'
        // ,'article.article_desc'
        DB::raw("distinct('purchase_order_det.article_code')")
        ,'purchase_order_det.article_code'
        ,'purchase_order_det.uom'
        ,'purchase_order_hdr.supplier_id'
        ,'article.article_alternative_code'
        ,'article.article_desc'
        ,DB::RAW("(select string_agg(distinct po_number,'|' order by po_number) as list_po from 
                    purchase_order_det as pod
                    where pod.article_code = purchase_order_det.article_code 
                    and pod.po_number 
                    in (select po_number from purchase_order_hdr where status = '3' and supplier_id = purchase_order_hdr.supplier_id))")
        )
        ->orderBy('article.article_desc')
        ->get();

        $output='';
        $output .='<option value="">Choose Article</option>';
        
        foreach ($data as $row){
            $output .='<option value="'.$row->article_code.'" 
                        data-list-po= "'.$row->list_po.'" 
                        data-detail="'.$row->article_code.'|'.$row->uom.'|'.$row->supplier_id.'" >
                        '.$row->article_alternative_code.' - '. $row->article_desc.'
                        </option>';
        }
        return $output;
    }

    public function qtyPo(Request $request)
    {
        $valPO = $request->valPO;
        $valArt = $request->valArt;

        $data = DB::table('purchase_order_det')
        ->leftJoin(DB::raw("(select po, article_code,sum(qty) as qty_rec,price from (
            select *,(select po_number from receiving_hdr 
            where rec_number = a.rec_number) as po from receiving_det a where rec_number in (
            select rec_number from receiving_hdr where status = '3' and po_number = 'purchase_order_det.po_number')
            ) z
            group by po, article_code,price) oki"),function($join){
               $join->on('purchase_order_det.po_number','oki.po');
        })
        ->select(DB::raw("COALESCE(qty_rec,0) as qty_rec")
        ,DB::raw("COALESCE(qty,0) as qty")
        ,DB::raw("COALESCE(purchase_order_det.qty,0) - COALESCE(qty_rec,0) as remain_qty")
        ,'uom'
        )
        ->where('purchase_order_det.po_number',$valPO)
        ->where('purchase_order_det.article_code',$valArt)
        ->first();

        // dd($data);

        return response()->json(array('data' => $data));

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
        $diNumber="$key-ASN/$year/$month/$newCode";
        
        return $diNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        return view("deliveryInstruction.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $diNumber = $request->diNumber;
        $diDate = $request->diDate;
        $deliveryDate = $request->deliveryDate;
        $supplier = $request->supplier;
        $note = $request->note;
        $status = '1';
        $leadCode = $this->moduleCode; 

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];

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
            'deliveryDate'  => 'required',
            'diDate'  => 'required',
            'supplier'  => 'required',
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
            $diNumber = $this->getLastCode($leadCode);
            DB::beginTransaction();
            try {
                    DB::table('delivery_instruction_hdr')->insert([
                        'di_number'=>$diNumber,
                        'origin_di_number'=>$diNumber,
                        'supplier_id'=>$supplier,
                        'di_date'=>$diDate,
                        'delivery_date'=>$deliveryDate,
                        'order_type'=>'',
                        'status'=>$status,
                        'note'=>$note,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'di_number' => $diNumber,
                            'po_number' => $val->po_number,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];
                    }

                    DB::table('delivery_instruction_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $diNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'diNumber'=>$diNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $diNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'diNumber'=>$diNumber));

            }
        }
    }

    public function show(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $username =  Auth::user()->username;
        $data['title'] = "Detail $this->title";
        $data['subtitle'] = "Detail $this->title";

        $data['headers'] = DB::table('purchase_order_hdr')
        ->where('origin_po_number', function($query) use ($id){
            $query->select('po_number')->from('purchase_order_hdr')->where('id',$id);
        })
        ->select('purchase_order_hdr.*'
        ,DB::raw("(select concat(kode,' - ',nama) from third_party where kode = purchase_order_hdr.supplier_id) as supp_name") 
        ,DB::raw('(select sum(qty) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_qty') 
        ,DB::raw('(select count(*) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_row')
        ,DB::raw('(select round(sum(qty*price)) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_amount')
        ,DB::raw('(select round(sum((qty*price)*purchase_order_hdr.discount/100)) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_discount')
        ,DB::raw('(select round(sum((qty*price)*purchase_order_hdr.ppn/100)) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_ppn')
        ,DB::raw('(select round(sum((qty*price)*purchase_order_hdr.pph22/100)) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_pph22')
        )
        ->orderBy('id')
        ->get();

        $diNumber = $data['headers'][0]->origin_po_number;
        
        $data['details'] = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->leftJoin('purchase_request_det', function($join) {
            $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
            ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        })
        ->leftJoin('uom','uom.code','=','purchase_order_det.uom')
        ->whereIn('purchase_order_det.po_number', function($query) use ($diNumber){
            $query->select('po_number')->from('purchase_order_hdr')->where('origin_po_number',$diNumber);
        })
        ->select('purchase_order_det'.'.*'
            ,'purchase_order_det.pr_number'
            ,'article_stock.article_qty as qty_stock'
            ,'uom.uom_group'
            , DB::raw('(SELECT name from group_materials where code = group_of_material) as group')
            ,DB::raw('concat(article_alternative_code,article_desc) as article')
        )
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$diNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$diNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'RVISED','8'=>'DECLINE'];
        $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','REVISED','DECLINE'];
        $data['statusPo'] = $statusPo[$data['headers'][0]->status-1];
        
        return view("deliveryInstruction.show",$data);        
    }

    public function detail(Request $request)
    {
        $diNumber=$request->poNumber;
        $detail = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->leftJoin('purchase_request_det', function($join) {
            $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
            ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        })
        ->leftJoin('uom','uom.code','=','purchase_order_det.uom')
        ->where('purchase_order_det.po_number',$diNumber)
        ->select('purchase_order_det'.'.*'
            ,'purchase_order_det.pr_number'
            ,'article_stock.article_qty as qty_stock'
            ,'uom.uom_group'
            , DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('id')
        ->get();

        return response()->json(array('status' => 0, 'data' => $detail));

    }

    public function showEdit($key)
    {
        $id=Crypt::decryptString($key);
        $username= Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('delivery_instruction_hdr')
        ->where('delivery_instruction_hdr.id',$id)
        ->get()->first();

        $diNumber = $data['header']->di_number;
        
        $data['prHeader'] = DB::table('purchase_request_det') 
        ->where('supp_code',$data['header']->supplier_id)
        ->where('po_number','=',$diNumber)
        ->orderBy('pr_number')
        ->distinct('pr_number')
        ->get();

        $data['articles'] = DB::table('purchase_request_det')
            ->leftJoin('article','article.article_code','=','purchase_request_det'.'.article_code')
            ->leftJoin('article_stock','article_stock.article_code','=','purchase_request_det'.'.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->leftJoin('uom','uom.code','=','purchase_request_det.uom')
            ->where('supp_code',$data['header']->supplier_id)
            ->where('po_number','=',$diNumber)
            // ->where('pr_number','=',$data['header']->pr_number)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select('purchase_request_det'.'.*'
                ,'article.article_alternative_code'
                ,'article.article_code as artikel_code'
                ,'article.article_desc'
                ,'article.costprice'
                ,'article_stock.article_qty as qty_stock'
                ,'purchase_request_det.uom as uom1'
                ,'uom.uom_group'
                ,'group_materials.name as group')
            ->get();

        $data['detail'] = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->leftJoin('purchase_request_det', function($join) {
            $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
            ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        })
        ->leftJoin('uom','uom.code','=','purchase_order_det.uom')
        ->where('purchase_order_det.po_number',$diNumber)
        ->select('purchase_order_det'.'.*'
            ,'purchase_order_det.pr_number'
            ,'article_stock.article_qty as qty_stock'
            ,'uom.uom_group'
            , DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
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

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$diNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$poNumber,$username);
                   
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
        $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','REVISED','DECLINE'];
        $data['statusPo'] = $statusPo[$data['header']->status-1];

        return view("deliveryInstruction.edit",$data);
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
    //         $message  = "$title Revison PO: $poOrigin to $poNew is successfully saved";
    //         \LogActivity::addToLog($title,"username: $username Status $message");
    //         return redirect()->route('deliveryInstruction.edit', ['id'=>Crypt::encryptString($data->id)]);
    //         // return $this->showEdit(Crypt::encryptString($id));
    //     }else{
    //         $title ="Save $this->title";
    //         $alert  ="warning";
    //         $message  = "$title Revison PO: $poOrigin to $poNew is failed to save";
    //         \LogActivity::addToLog($title,"username: $username Status $message");
    //         return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
    //     }
        
    // }

    
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
        
        $statusSimpan = $request->statusSimpan;
        if ( $statusSimpan == 'approve' ){
            $maxLevel = $request->maxLevel;
            $approveLevel  = $request->approveLevel;
            $status = $approveLevel === $maxLevel ? '3' : '2';
        }else{
            $status = '1';
        }       

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
        
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

            $alert ="warning";
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

                    if ( $statusSimpan == 'approve' ){
                        DB::table('approval_history')->insert([
                            'module_code' => $this->moduleCode,
                            'module_number' => $poNumber,
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
                    $message  = "$title $poNumber is successfully updated";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert ="warning";
                $message  = "$title $poNumber is failed to updated";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'prNumber'=>$poNumber));
            }
        }

    }

    // public function approve(Request $request)
    // {
    //     $username =  Auth::user()->username;
    //     $poNumber = $request->poNumber;
    //     $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$poNumber,$username);        
    //     $nextLevel = $statusLevelApproval[0]->next_level;
    //     $statusPo = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
    //     DB::beginTransaction();
    //     try {
    //             $row_affected=DB::table('purchase_order_hdr')
    //             ->where('po_number',$poNumber)
    //             ->update(
    //                 [
    //                     'status' => $statusPo,
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
    //                     'status' => 1,
    //                     'created_by' => Auth::user()->username,
    //                     'updated_by' => Auth::user()->username,
    //                     'created_at' => date('Y-m-d H:i:s'),
    //                     'updated_at' => date('Y-m-d H:i:s')
    //                 ]);
    //             }
                
    //             DB::commit();
    //             $title ="Approve $this->title";
    //             $alert  ="success";
    //             $message  = "$title $poNumber is successfully Approve-".$nextLevel;
    //             \LogActivity::addToLog($title,"username: $username Status $message");
    //             return response()->json(array('statusPo' => $statusPo,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         $title ="Approve $this->title";
    //         $alert  ="warning";
    //         $message  = "$title $poNumber is failed to Approve-".$nextLevel;
    //         \LogActivity::addToLog($title,"username: $username Status $message");
    //         return response()->json(array('statusPo' => $statusPo,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));
    //     }
    // }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $diNumber = DB::table('delivery_instruction_hdr')->where('id',$id)->value('di_number');
        $rowAffected = DB::table('delivery_instruction_hdr')->where('id',$id)->delete();
        if($rowAffected>0){
            DB::table('delivery_instruction_det')->where('di_number',$diNumber)->delete();
            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $diNumber Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $diNumber Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function list(Request $request)
    {
        $username = Auth::user()->username;
        $searchDi = strtolower($request->searchDi);
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $deliveryDate = $request->deliveryDate;

        $deliveryDate = $request->$deliveryDate;
        $fromDate ="";
        $toDate = "";
        if ($deliveryDate){
            $date = explode("to",$deliveryDate);
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
       
        $data = DB::table('delivery_instruction_hdr')
        ->where(function ($query) use ($searchDi,$searchSupplier,$searchStatus,$deliveryDate,$fromDate,$toDate) {
            $searchDi ? $query->where('di_number','ilike','%'.$searchDi.'%') : '';
            $searchSupplier ? $query->where('supplier_id',$searchSupplier) : '';
            $deliveryDate ? $query->whereBetween(DB::raw("delivery_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchStatus ? $query->where('status',$searchStatus) : '';
        })
        ->leftjoin('third_party','third_party.kode','delivery_instruction_hdr.supplier_id')
        ->select('delivery_instruction_hdr.*','third_party.nama as supp_name')
        ->orderBy('id')
        ->get(); 

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'','5'=>'','6'=>'','7'=>'REVISED','8'=>''];
    
        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            // if ( $data->status == '2' or $data->status == '1' ){
            //     if (Auth::user()->can('deliveryInstruction-edit')) {
            //     $buttons .=         '<a href="'. route('deliveryInstruction.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
            //                             <i data-feather="file-text"></i>
            //                             <span>'. __("Approve") .'</span>
            //                         </a>';
            //     }
            // }
            // if ( $data->status == '1' or $data->status == '2' ){
                if (Auth::user()->can('deliveryInstruction-edit')) {
                $buttons .=         '<a href="'. route('deliveryInstruction.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        <span>'. __("Edit") .'</span>
                                    </a>';
                }
            // }

            // if (($data->status == '2') || ($data->status == '3') ){
            //     if (Auth::user()->can('deliveryInstruction-revision')) {
            //         $buttons .=         '<a href="'. route('deliveryInstruction.revision', ['id'=>Crypt::encryptString($data->id),'nR'=>$data->num_revision]) .'" class="dropdown-item">
            //                                 <i data-feather="copy"></i>
            //                                 <span>'. __("Revision") .'</span>
            //                             </a>';
            //     }
            // }
            
            $buttons .=         '<a href="'. route('deliveryInstruction.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    <span>'. __("Print") .'</span>
                                </a>';
            
            $buttons .=         '<a href="'. route('deliveryInstruction.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    <span>'. __("Detail") .'</span>
                                </a>';

            if (Auth::user()->can('deliveryInstruction-delete')) {
                $buttons .=         "<a href='javascript:;'
                                    class='dropdown-item' 
                                    data-size='sm'
                                    data-ajax-delete='true'
                                    data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->id."\").submit();'
                                    data-modal-id='".$data->id."'
                                    data-url='". route('deliveryInstruction.destroy', ['id'=>Crypt::encryptString($data->id)]) ."'>
                                    <i data-feather='trash-2' class='feather-14-red'></i>
                                    <span>". __('Delete') ."</span>
                                </a>";
            }
 
            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('di_number', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $status = ['NEW','VALIDATED','APPROVED','','','','REVISED',''];
            // return '<div class="badge d-block '.$badges[$data->status - 1].'"><a name="'.$data->po_number.'" href="'. route('deliveryInstruction.show', ['id'=>Crypt::encryptString($data->idku)]) .'" ><span>'.$data->po_number.'</span></a></div>';
            return '<span style="display: none;">'.$data->di_number.'</span><a class="badge d-block '.$badges[$data->status - 1].'" name="'.$data->di_number.'" href="'. route('deliveryInstruction.show', ['id'=>Crypt::encryptString($data->id)]) .'" ><span>'.$data->di_number.'</span></a>';
        })

        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $status = ['NEW','VALIDATED','APPROVED','','','','REVISED',''];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$status[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','di_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {

        $username = Auth::user()->username;
        $searchDi = strtolower($request->searchDi);
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $deliveryDate = $request->deliveryDate;

        $deliveryDate = $request->$deliveryDate;
        $fromDate ="";
        $toDate = "";
        if ($deliveryDate){
            $date = explode("to",$deliveryDate);
            $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }
       
        $data = DB::table('delivery_instruction_det')
        ->leftJoin('delivery_instruction_hdr','delivery_instruction_hdr.di_number','delivery_instruction_det.di_number')
        ->leftJoin('article','article.article_code','delivery_instruction_det.article_code')
        ->where(function ($query) use ($searchDi,$searchSupplier,$searchStatus,$deliveryDate,$fromDate,$toDate) {
            $searchDi ? $query->where('di_number','ilike','%'.$searchDi.'%') : '';
            $searchSupplier ? $query->where('delivery_instruction_hdr.supplier_id',$searchSupplier) : '';
            $deliveryDate ? $query->whereBetween(DB::raw("delivery_instruction_hdr.delivery_date(tr_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
            $searchStatus ? $query->where('delivery_instruction_hdr.status',$searchStatus) : '';
        })
        ->leftjoin('third_party','third_party.kode','delivery_instruction_hdr.supplier_id')
        ->select('delivery_instruction_det.*'
        ,'delivery_instruction_hdr.*'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'third_party.nama as supp_name'
        // ,'uom_group'
        ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = delivery_instruction_det.di_number) as approval_by")
        )
        ->orderBy('delivery_instruction_det.id')
        ->get(); 

        return Datatables::of($data)
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','REVISED','DECLINE'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPo[$data->status - 1]."</div>";
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
            
        $diHdr=DB::table('delivery_instruction_hdr')
        ->where('id',$id)
        ->first();

        $diNumber=$diHdr->di_number;
    
        $data['details']=DB::table('delivery_instruction_det')
        ->leftJoin('article','article.article_code','delivery_instruction_det.article_code')
        ->where('di_number',$diNumber)
        ->get();

        $data['suppliers']=DB::table('third_party')
        ->where('kode',$diHdr -> supplier_id)
        ->get();

        $data['keterangan']=$diHdr -> note;
        $data['diNumber'] =$diNumber;
        $data['diDate'] =$diHdr -> di_date;
        $data['diDelDate'] =$diHdr -> delivery_date;
        $data['status'] = $diHdr->status;
        $data['no'] =0;

        $data['approved'] = DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_number',$diNumber)
        ->orderBy('approval_order','desc')
        ->value('users.name');

        view()->share($data);

        $pdf = PDF::loadView('deliveryInstruction.print');
        return $pdf->stream("PO_$diNumber.pdf");

    }

}
