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

class PurchaseOrderController extends Controller
{
    private $title;
    private $moduleCode;
    private $nilaiPpn;
    private $nilaiPph23;
    private $nilaiPph21;
    private $nilaiPph42;
    private $lockDate;
    private $lockDateIndex;
    private $ppnPenyebut;
    private $ppnPembilang;

    public function __construct()
    {
        $this->title = "Purchase Order";
        $this->moduleCode = "PO";
        
        $this->nilaiPpn  = Attributes::getLastPpn()['ppnValue'];
        $this->ppnPembilang = Attributes::getLastPpn()['pembilang'];
        $this->ppnPenyebut = Attributes::getLastPpn()['penyebut'];

        // $this->nilaiPpn = DB::table('attributes')
        // ->where('attr_id','mainppn')
        // ->value('attr_value');

        $this->nilaiPph23 = DB::table('attributes')
        ->where('attr_id','mainpph23')
        ->value('attr_value');

        $this->nilaiPph21 = DB::table('attributes')
        ->where('attr_id','mainpph21')
        ->value('attr_value');

        $this->nilaiPph42 = DB::table('attributes')
        ->where('attr_id','mainpph42')
        ->value('attr_value');

        $lockDateHelper = AppHelpers::lockDate($this->moduleCode);
        $this->lockDate = $lockDateHelper[0];
        $this->lockDateIndex = $lockDateHelper[1];
        
    }

    public function getTableColoumn(){
        $kolom=
        [
            ['data'=>'action','name'=>'action','title'=>'action','orderable'=> false,'searchable'=>false],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'po_number_1','name'=>'po_number_1','title'=>'PO Number','orderable'=> false,'searchable'=>false,'visible'=>false],            
            ['data'=>'num_revision','name'=>'num_revision','title'=>'Revision'],
            ['data'=>'status','name'=>'status','title'=>'Status'],
            ['data'=>'supp_name','name'=>'supp_name','title'=>'Supplier'],
            ['data'=>'po_date','name'=>'po_date','title'=>'PO Date'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            ['data'=>'validate_by','name'=>'validate_by','title'=>'Prepared By'],
            ['data'=>'authorized_by','name'=>'authorized_by','title'=>'Authorized By'],
            ['data'=>'pkp','name'=>'pkp','title'=>'Tax'],
            ['data'=>'termin','name'=>'termin','title'=>'Termin'],            
            ['data'=>'qty','name'=>'qty','title'=>'QTY'],
            ['data'=>'gross','name'=>'gross','title'=>'Bruto'],
            ['data'=>'discount','name'=>'discount','title'=>'Discount'],
            ['data'=>'ppn','name'=>'ppn','title'=>'PPN'],
            ['data'=>'netto','name'=>'netto','title'=>'Netto'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            ['data'=> 'created_by', 'name'=> 'created_by','title'=>'Created By'],
            ['data'=> 'created_at', 'name'=> 'created_at','title'=>'Created At'],
            ['data'=>'approval_by','name'=>'approval_by','title'=>'Approved By']
        ];
        return json_encode($kolom, true);
    }

    public function getTableColoumnDetail(){
        $kolom=
        [
            ['data'=>'nama_dept','name'=>'nama_dept','title'=>'Departemen'],
            ['data'=>'po_date','name'=>'po_date','title'=>'PO Date'],
            ['data'=>'delivery_date','name'=>'delivery_date','title'=>'Delivery Date'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'pr_number','name'=>'pr_number','title'=>'PR Number'],
            ['data'=>'supp_name','name'=>'supp_name','title'=>'Supplier'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qtyku','name'=>'qtyku','title'=>'Qty'],
            ['data'=>'article_uom','name'=>'article_uom','title'=>'UOM'],
            ['data'=>'price','name'=>'price','title'=>'Price'],
            ['data'=>'total_dpp','name'=>'total_dpp','title'=>'Total Tanpa PPN'],
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
            ['data'=>'article_type_name','name'=>'article_type_name','title'=>'Keterangan'],
            ['data'=>'note','name'=>'note','title'=>'Note'],
            // ['data'=>'supplier_id','name'=>'supplier_id','title'=>'Supplier code'],
            // ['data'=>'approval_by','name'=>'approval_by','title'=>'Approved By'],
            // ['data'=>'created_by','name'=>'created_by','title'=>'Created By'],
            // ['data'=>'created_at','name'=>'created_at','title'=>'Created Date'],
            // ['data'=>'updated_by','name'=>'updated_by','title'=>'Updated By'],
            // ['data'=>'updated_at','name'=>'updated_at','title'=>'Updated Date'],
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

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','8'=>'DECLINE'];

        $data['lockDate'] = $this->lockDateIndex;

        
            
        return view("purchaseOrder.index",$data);
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
        $poNumber="$key-ASN/$year/$month/$newCode";
        
        return $poNumber;
    }

    public function create(Request $request)
    {
        $data['title'] = "Create $this->title";
        $data['subtitle'] = "Create $this->title";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        // ->where('top_batas_1',"<>",30)
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['cekReceiving'] = 0;

        $data['lockDate'] = $this->lockDate;

        $data['ppnValue']  = $this->nilaiPpn;
        $data['ppnPenyebut'] = $this->ppnPenyebut;
        $data['ppnPembilang'] = $this->ppnPembilang;

        return view("purchaseOrder.create",$data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request->articles);
        $orderDate = $request->orderDate;
        $poType = $request->poType; 
        $deliveryDate = $request->deliveryDate;
        $currency = $request->currency;
        $supplier = $request->supplier;
        $tax = $request->tax;
        $ppn = $tax ? $request->ppn : 0;
        $termin = $request -> term;
        $pph = 0;
        $kurs = $request -> kurs;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $discount = $request->discount;
        $note = $request->note;
        $status = '1';
        $poLeadCode = $poType=='std' ? 'PO' : 'POSUB'; 

        $dppLainValue=is_null($request->totalDppNilaiLain) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalDppNilaiLain);
        $dppPembilang = $request->pembilangNumber;
        $dppPenyebut = $request->penyebutNumber;

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
           
            $title="Save $this->title";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));

        }else{
            $hasilUpdate = AppHelpers::resetCode($poLeadCode);
            $poNumber = $this->getLastCode($poLeadCode);
            DB::beginTransaction();
            try {
                    DB::table('purchase_order_hdr')->insert([
                        'po_number' => $poNumber,
                        'origin_po_number' => $poNumber,
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
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'dpp_lain_value' => $dppLainValue,
                        'dpp_lain_pembilang' => $dppPembilang,
                        'dpp_lain_penyebut' => $dppPenyebut
                    ]);
                    
                    $dppLainBagi = $dppLainValue ? $dppPembilang/$dppPenyebut : 1;
                    $dataSet = [];
                    foreach ($articles as $val) {
                        
                        if($dppLainValue){
                            $ppnFinal =  (($val->newPrice*$val->qty)*$dppLainBagi) * $ppn/100;
                        }else{
                            $ppnFinal =  (($val->newPrice*$val->qty)) * $ppn/100;
                        }

                        $dataSet[] = [
                            'po_number' => $poNumber,
                            'pr_number' => $val->pRequest,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            'old_price' => $val->price,
                            'price' => $val->newPrice,
                            'ppn' => round($ppnFinal),
                            // 'ppn' => round(($val->qty*$val->newPrice)*($ppn/100)),
                            'pph22' => $totalPph,
                            'created_by' => Auth::user()->username,
                            'created_at' => date('Y-m-d H:i:s'),
                        ];

                        DB::table('purchase_request_det')
                        ->where('pr_number',$val->pRequest)
                        ->where('article_code',$val->article_code)
                        // ->where('supp_code',$supplier)
                        ->update([
                            'po_number' => $poNumber,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);

                        DB::table('purchase_request_hdr')
                        ->where('pr_number',$val->pRequest)
                        ->update([
                            'status' => 7,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }

                    DB::table('purchase_order_det')->insert($dataSet);

                    DB::commit();
                    $title ="Save $this->title";
                    $alert  ="success";
                    $message  = "$title $poNumber is successfully saved";
                    \LogActivity::addToLog($title,"username: $username Status $message");
                    return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $title ="Save $this->title";
                $alert  ="warning";
                $message  = "$title $poNumber is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

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
        ,DB::raw('(select sum(qty*price) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_amount')
        ,DB::raw('(select sum((qty*price)*purchase_order_hdr.discount/100) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_discount')
        ,DB::raw('(select case when purchase_order_hdr.dpp_lain_value > 0 then round(purchase_order_hdr.dpp_lain_value*purchase_order_hdr.ppn/100) else round(sum(((qty*price)-((qty*price)*purchase_order_hdr.discount/100))*purchase_order_hdr.ppn/100)) end as ppn from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_ppn')
        ,DB::raw('(select sum(((qty*price)-((qty*price)*purchase_order_hdr.discount/100))*purchase_order_hdr.pph22/100) from purchase_order_det where po_number = purchase_order_hdr.po_number) as sum_pph22')
        )
        ->orderBy('id')
        ->get();
        

        $poNumber = $data['headers'][0]->origin_po_number;
        
        $data['details'] = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        // ->leftJoin('purchase_request_det', function($join) {
        //     $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
        //     ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        // })
        ->leftJoin('uom','uom.code','=','purchase_order_det.uom')
        ->whereIn('purchase_order_det.po_number', function($query) use ($poNumber){
            $query->select('po_number')->from('purchase_order_hdr')->where('origin_po_number',$poNumber);
        })
        ->select('purchase_order_det'.'.*'
            ,'purchase_order_det.pr_number'
            ,'article_stock.article_qty as qty_stock'
            ,'article.uom as article_uom'
            ,'uom.uom_group'
            , DB::raw('(SELECT name from group_materials where code = group_of_material) as group')
            ,DB::raw("concat(article_alternative_code,'-',article_desc) as article")
        )
        ->orderBy('id')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$poNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$poNumber,$username);

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'RVISED','8'=>'DECLINE'];
        $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','REVISED','DECLINE'];
        $data['statusPo'] = $statusPo[$data['headers'][0]->status-1];

        $data['ppnValue']  = $data['headers'][0]->ppn;
        $data['ppnPenyebut'] = $data['headers'][0]->dpp_lain_penyebut;
        $data['ppnPembilang'] = $data['headers'][0]->dpp_lain_pembilang;
        
        return view("purchaseOrder.show",$data);        
    }

    public function detail(Request $request)
    {
        $poNumber=$request->poNumber;
        $detail = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        ->leftJoin('purchase_request_det', function($join) {
            $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
            ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        })
        ->leftJoin('uom','uom.code','=','purchase_order_det.uom')
        ->where('purchase_order_det.po_number',$poNumber)
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
        $username =  Auth::user()->username;
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('purchase_order_hdr')
        // ->leftJoin('purchase_request_det','purchase_order_hdr.po_number','purchase_request_det.po_number')
        ->where('purchase_order_hdr.id',$id)
        ->get()->first();

        $poNumber = $data['header']->po_number;
        
        $data['prHeader'] = DB::table('purchase_request_det') 
        ->where('supp_code',$data['header']->supplier_id)
        ->where('po_number','=',$poNumber)
        ->orderBy('pr_number')
        ->distinct('pr_number')
        ->get();

        $data['articles'] = DB::table('purchase_request_det')
        ->leftJoin('article','article.article_code','=','purchase_request_det'.'.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_request_det'.'.article_code')
        ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
        ->leftJoin('uom','uom.code','=','purchase_request_det.uom')
        ->where('supp_code',$data['header']->supplier_id)
        ->where('po_number','=',$poNumber)
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
        // ->orderBy('purchase_request_det.id')
        ->get();

        $data['detail'] = DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','=','purchase_order_det.article_code')
        ->leftJoin('article_stock','article_stock.article_code','=','purchase_order_det.article_code')
        // ->leftJoin('purchase_request_det', function($join) {
        //     $join->on('purchase_request_det.po_number','purchase_order_det.po_number')
        //     ->on('purchase_request_det.article_code','purchase_order_det.article_code');
        // })
        ->leftJoin('uom','uom.code','=','purchase_order_det.uom')
        ->where('purchase_order_det.po_number',$poNumber)
        ->select('purchase_order_det'.'.*'
            ,'article.article_alternative_code'
            ,'article.article_code as artikel_code'
            ,'article.article_desc'
            ,'purchase_order_det.pr_number'
            ,'article_stock.article_qty as qty_stock'
            ,'uom.uom_group'
            , DB::raw('(SELECT name from group_materials where code = group_of_material) as group'))
        ->orderBy('purchase_order_det.id')
        ->get();       

        $data['cekReceiving'] = DB::table('receiving_hdr')
        ->where('po_number',$poNumber)
        ->whereNotIn('status',['5','7'])
        ->count();

        // dd($data['detail']);

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        $data['approvalHistory'] = Approval::approvalHistory($this->moduleCode,$poNumber,$username);
        $data['approveValidate'] = Approval::approveValidate($this->moduleCode,$poNumber,$username);
                   
        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
        $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','REVISED','DECLINE'];
        $data['statusPo'] = $statusPo[$data['header']->status-1];

        $data['lockDate'] = $this->lockDate;

        $data['ppnValue']  = $data['header']->ppn;
        $data['ppnPenyebut'] = $data['header']->dpp_lain_penyebut;
        $data['ppnPembilang'] = $data['header']->dpp_lain_pembilang;

        return view("purchaseOrder.edit",$data);
    }

    public function edit(Request $request)
    {
        return $this->showEdit($request->id);
    }

    public function revision(Request $request){
        $username =  Auth::user()->username;
        $id=Crypt::decryptString($request->id);
        $poOrigin=DB::table('purchase_order_hdr')->where('id',$id)->value('po_number');
        $numRevision = $request->nR ? $request->nR +1 : 1 ;
        $poNew = $poOrigin.'-R'.$numRevision;
        $checkNewPo=DB::table('purchase_order_hdr')->where('po_number',$poNew)->count();
        $reasonRequest = $request->reason;
        $reason = "(Revision by $username, Reason: $reasonRequest)";

        if ($checkNewPo > 0){
            $numRevision = $numRevision+1;
            $poNew = $poOrigin.'-R'.$numRevision;
        }        
                
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
            updated_at,
            reason,
            dpp_lain_value,
            dpp_lain_pembilang,
            dpp_lain_penyebut
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
            '".date('Y-m-d H:i:s')."',
            '$reasonRequest',
            dpp_lain_value,
            dpp_lain_pembilang,
            dpp_lain_penyebut
        from purchase_order_hdr where po_number = '$poOrigin'";

        // regexp_replace(CONCAT(note,', $reason'),', ',''),

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
                    'status' => '1',
                    // 'note'=> DB::raw("regexp_replace(CONCAT(note,', $reason'),', ','')"),
                    'revised_by'=>Auth::user()->username,
                    'revised_at'=> date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            DB::table('approval_history')
            ->where('module_number',$poOrigin)
            ->update(
                [
                    'module_number' => $poNew,
                    'status' => '0',
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
            
            $title ="Save $this->title";
            $alert  ="success";
            $message  = "$title Revision PO: $poOrigin to $poNew is successfully saved";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->route('purchaseOrder.edit', ['id'=>Crypt::encryptString($id)]);
            // return $this->showEdit(Crypt::encryptString($id));
        }else{
            $title ="Save $this->title";
            $alert  ="warning";
            $message  = "$title Revision PO: $poOrigin to $poNew is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
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
        $ppn = $tax ? $request->ppn : 0;
        $termin = $request -> term;
        $pph = 0;
        $kurs = $request -> kurs;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $discount = $request->discount;
        $note = $request->note;

        $dppLainValue=is_null($request->totalDppNilaiLain) ? 0 : preg_replace('/[^0-9.]+/', '', $request->totalDppNilaiLain);
        $dppPembilang = $request->pembilangNumber;
        $dppPenyebut = $request->penyebutNumber;
        
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
                            'updated_at' => date('Y-m-d H:i:s'),
                            'dpp_lain_value' => $dppLainValue,
                            'dpp_lain_pembilang' => $dppPembilang,
                            'dpp_lain_penyebut' => $dppPenyebut
                        ]
                    );

                    /*
                        update PR supaya isi PO nya di kosongkan dulu
                    */

                    $dataset=[];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            $poNumber.$val->article_code.$val->pRequest
                        ];

                        DB::table('purchase_request_det')
                        ->where('pr_number',$val->pRequest)
                        ->where('article_code',$val->article_code)
                        ->where('po_number',$poNumber)
                        ->where('supp_code',$supplier)
                        ->update(
                            [
                            'po_number' => '',
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        
                    }

                    /*
                        Delete kalo article tidak ada di po $poNumber dan article nya $val->article_code
                        berdasarkan 2 kondisi
                        Tambah kondisinya berdasarkan no PR jadi pr_number+poNumber+article_code
                    */

                    DB::table('purchase_order_det')
                        ->whereNotIn(DB::raw("CONCAT(po_number,article_code,pr_number)"),$dataSet)
                        ->where('po_number',$poNumber)
                        ->delete();
                                  
                    $dppLainBagi = $dppLainValue ? $dppPembilang/$dppPenyebut : 1;

                    foreach ($articles as $val) {
                        
                        if($dppLainValue){
                            $ppnFinal =  (($val->newPrice*$val->qty)*$dppLainBagi) * $ppn/100;
                        }else{
                            $ppnFinal =  (($val->newPrice*$val->qty)) * $ppn/100;
                        }

                        DB::table('purchase_order_det')
                        ->updateOrInsert(
                            ['po_number' => $poNumber
                            ,'article_code' => $val->article_code
                            ,'pr_number' => $val->pRequest
                            ],
                            [
                            'po_number' => $poNumber,
                            'pr_number' => $val->pRequest,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom' => $val->uom,
                            // 'old_price' => $val->price,
                            'price' => $val->newPrice,
                            'ppn' => round($ppnFinal),
                            // 'ppn' => round(($val->qty*$val->newPrice)*($ppn/100)),
                            'pph22' => $totalPph,
                            'updated_by' => Auth::user()->username,
                            'updated_at' => date('Y-m-d H:i:s')
                            ]
                        );

                        // update juga di PR kalau ada yang di delete PR nya maka no PO di PR tesebut juga di delete
                        
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

                        /*
                            PR nya berubah status jadi sudah dibikin PO
                        */

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

                    /*
                        cek apakah PO sudah di receiving atau belum
                        Kalau sudah di receiving,update harga di receiving nya
                        status receiving = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'POSTED','5'=>'CANCELED','7'=>'REVISED','10'=>'REVISI'];
                    */

                    $cekReceiving = DB::table('receiving_hdr')
                    ->where('po_number',$poNumber)
                    // ->where('status','4')
                    ->whereNotIn('status',['5','7'])
                    ->get();


                    //Update LPB berdasarkan PR
                    foreach($cekReceiving as $cek){
                        $recNumberFromList = $cek->rec_number;
                        // cek apakah ada harga yang beda di detail rec
                        $queryCek = "SELECT *,a.price
                        ,(select price from purchase_order_det where po_number = '$poNumber' and article_code = a.article_code and pr_number = a.pr_number limit 1) as po_price 
                        from receiving_det a 
                        where 
                        a.rec_number= '$recNumberFromList' and qty > 0
                        and (select price from purchase_order_det where po_number = '$poNumber' and article_code = a.article_code and pr_number = a.pr_number limit 1) <> a.price";

                        $queryCekHasil = DB::select($queryCek);

                        //kalau ada harga yang beda harganya di update
                        if(count($queryCekHasil)){
                            foreach($queryCekHasil as $item){
                                DB::table('receiving_det')
                                ->where('rec_number',$item->rec_number)
                                ->where('article_code',$item->article_code)
                                ->where('pr_number',$item->pr_number)
                                ->update(
                                    [
                                    'price' => $item->po_price,
                                    'updated_by' => Auth::user()->username,
                                    'updated_at' => date('Y-m-d H:i:s')
                                    ]
                                );
                            }

                            //proses ulang untuk masuk ke kas ke BB

                            $hasilUpdate = $this->reInsertRecIntoKas($recNumberFromList);

                            $title ="Update receiving dari PO";
                            $message = "PO Number = $poNumber, Rec Number = $recNumberFromList";
                            \LogActivity::addToLog($title,"username: $username Status $message");
                        }
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

    public function approve(Request $request)
    {
        $username =  Auth::user()->username;
        $poNumber = $request->poNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$poNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusPo = $statusLevelApproval[0]->next_level == $statusLevelApproval[0]->max_level ? '3' :'2';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('purchase_order_hdr')
                ->where('po_number',$poNumber)
                ->update(
                    [
                        'status' => $statusPo,
                        'authorized_by' => Auth::user()->username,
                        'authorized_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $poNumber,
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
                $message  = "$title $poNumber is successfully Approve-".$nextLevel;
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusPo,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Approve $this->title";
            $alert  ="warning";
            $message  = "$title $poNumber is failed to Approve-".$nextLevel;
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusPo,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));
        }
    }

    public function decline(Request $request)
    {
        $username =  Auth::user()->username;
        $poNumber = $request->poNumber;
        $statusLevelApproval = Approval::approvalLevelPosition($this->moduleCode,$poNumber,$username);        
        $nextLevel = $statusLevelApproval[0]->next_level;
        $statusPo = '8';
                
        DB::beginTransaction();
        try {
                $row_affected=DB::table('purchase_order_hdr')
                ->where('po_number',$poNumber)
                ->update(
                    [
                        'status' => $statusPo,
                        'authorized_by' => Auth::user()->username,
                        'authorized_at' => date('Y-m-d H:i:s')
                    ]
                );

                if ($row_affected){
                    DB::table('approval_history')->insert([
                        'module_code' => $this->moduleCode,
                        'module_number' => $poNumber,
                        'username' => Auth::user()->username,
                        'approval_order' => $nextLevel,
                        'approval_date' => date('Y-m-d'),
                        'status' => 0,
                        'created_by' => Auth::user()->username,
                        'updated_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                
                DB::commit();
                $title ="Decline $this->title";
                $alert  ="success";
                $message  = "$title $poNumber is successfully decline";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('statusPo' => $statusPo,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Decline $this->title";
            $alert  ="warning";
            $message  = "$title $poNumber is failed to decline";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusPo' => $statusPo,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'poNumber'=>$poNumber));
        }
    }

    public function destroy(Request $request)
    {
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        // $po_number = DB::table('purchase_order_hdr')->where('id',$id)->where('status','1')->value('po_number');
        // $rowAffected = DB::table('purchase_order_hdr')->where('id',$id)->where('status','1')->delete();

        $po_number = DB::table('purchase_order_hdr')->where('id',$id)->where('status','1')->value('po_number');
        $rowAffected = DB::table('purchase_order_hdr')->where('id',$id)->where('status','1')->delete();

        $urutanPo = (int)explode('/',$po_number)[3];
        $urutanPoSebelum = (int)explode('/',$po_number)[3] -1;

        if($rowAffected>0){
            DB::table('purchase_order_det')->where('po_number',$po_number)->delete();
            db::table('master_code')
            ->where('code_key','PO')
            ->where('code_number',$urutanPo)
            ->update([
                'code_number' => $urutanPoSebelum
            ]);

            $title ="Delete $this->title";
            $alert  ="success";
            $message  = "$title $po_number Successfully Deleted";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);  
        }else{
            $title ="Delete $this->title";
            $alert  ="warning";
            $message  = "$title $po_number Failed to Delete";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function clear(Request $request)
    {
        //memutihkan PO supaya tidak bisa di pakai lagi
        //status PO jadi closed
        $username =  Auth::user()->username;       
        $id=Crypt::decryptString($request->id);
        $po_number = DB::table('purchase_order_hdr')->where('id',$id)->value('po_number');
        $status = '6';
        DB::beginTransaction();
        try {
                $row_affected=DB::table('purchase_order_hdr')
                ->where('id',$id)
                ->update(
                    [
                        'status' => $status,
                        'updated_by' => Auth::user()->username,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                );
                
                DB::commit();
                $title ="Clear $this->title";
                $alert  ="success";
                $message  = "$title $po_number Successfully Closed";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);

        } catch (Exception $e) {
            DB::rollBack();
            $title ="Clear $this->title";
            $alert  ="warning";
            $message  = "$title $po_number Failed to Close";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['title' => $title,'alert'=>$alert,'message'=> $message]);
        }

    }

    public function priceList(Request $request)
    {
        $articleCode = $request -> article;
        // $listArticle = DB::table('purchase_order_det')
        // ->leftJoin('purchase_order_hdr','purchase_order_hdr.po_number','purchase_order_det.po_number')
        // ->where('article_code',$articleCode)
        // ->select('purchase_order_det.po_number','po_date','price', 'purchase_order_hdr.created_at', 'purchase_order_hdr.updated_at')
        // ->orderBy(db::raw("TO_DATE(po_date,'dd-mm-yyyy')"),'asc')
        // ->where('status','<>','7')
        // ->limit(10)
        // ->get();

        $listArticle = DB::select("SELECT * from 
            (select purchase_order_det.po_number, po_date, price, purchase_order_hdr.created_at, to_char(purchase_order_hdr.updated_at,'dd-mm-yyyy') as updated_at 
            from purchase_order_det 
            left join purchase_order_hdr on purchase_order_hdr.po_number = purchase_order_det.po_number 
            where article_code = '$articleCode' 
            and status <> '7' 
            order by TO_DATE(po_date,'dd-mm-yyyy') desc limit 10) oki
            order by TO_DATE(po_date,'dd-mm-yyyy') asc");

        return Response()->json($listArticle);

    }

    public function list(Request $request)
    {
        $searchPo = strtolower($request->searchPo);
        $username = Auth::user()->username;
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $orderDate = $request->orderDate;
       
        $filter='';
        
        if ($searchPo !='' ){
            $filter.="lower(a.po_number) like '%$searchPo%' and ";
        }

        if ($searchSupplier  != '' ){
            $filter.="supplier_id = '$searchSupplier' and ";            
        }

        if ($searchStatus  != '' ){
            $filter.="status = '$searchStatus' and ";            
        }
        
        $filter.="status <> '7' and ";
             
        if ($orderDate  != '' ){
            $date = explode("to",$orderDate);
            if(count($date)==2){
                $date1=trim($date[0]);
                $date2=trim($date[1]);
            }else{
                $date1=$orderDate;
                $date2=$orderDate;
            }
            
            $filter.= "to_date(po_date, 'DD/MM/YYYY')  BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
        }
        
        if ($filter !=''){
            $filter=" where ".substr($filter,0,-4);
        }

        $data=DB::select("SELECT *,oki.id as idku,
        (select note from purchase_order_hdr where po_number = oki.po_number) as note,
        -- case when uom_group = 'PIECE' then TO_CHAR(qtyku,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qtyku,'999,999,999.999') end as qty,
        qtyku as qty,
        grossku as gross,
        discountku as discount,
        ppnku as ppn,
        -- TO_CHAR(grossku,'999,999,999') as gross,
        -- TO_CHAR(discountku,'999,999,999') as discount,
        -- TO_CHAR(ppnku,'999,999,999') as ppn,
        delivery_date,
        (select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name,
        (grossku-discountku)+ppnku as netto,
        -- TO_CHAR((grossku-discountku)+ppnku,'999,999,999') as netto,
        --query apakah user berhak untuk approve atau tidak
        (SELECT username = '$username' as validate from (
            select username,approval_order,
            (select max(approval_number) from approval_master where module_code = a.module_code ) as max_level,
            COALESCE((select max(approval_order) from approval_history
            where module_code = a.module_code
            and module_number = oki.po_number),'0') as current_level
            from approval_level a 
            where module_code = '".$this->moduleCode."' and username = '$username') b
            where approval_order = current_level+1
        ) as statusku
        from (
            select 
                b.created_by,
                b.created_at,
                b.status,b.id,
                a.po_number,
                a.po_number as po_number_1,
                supplier_id,
                po_date,
                delivery_date,
                pkp,
                termin,
                authorized_by,
                validate_by,
                sum(qty) as qtyku,
                sum(qty*price) as grossku,
                sum(discount) as discountku,
                sum(a.ppn) as ppnku,
                b.num_revision,
                (select STRING_AGG((select name from users where username = z.username), ' -> ' ORDER BY approval_order) AS main from approval_history z where module_number = a.po_number) as approval_by
            from purchase_order_det a
            left join purchase_order_hdr b
            on a.po_number = b.po_number    
            $filter
            group by b.id,a.po_number,supplier_id,po_date,delivery_date,pkp,termin,authorized_by,validate_by,b.created_by,b.created_at,b.status,b.num_revision
        ) as oki
        order by oki.id desc");

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];

        $lockDateToDate = date('Y-m-d',strtotime($this->lockDate));

        // $poDate = date('Y-m-d', strtotime('04-01-2024'));
        $bisaEdit = Auth::user()->can('purchaseOrder-edit');
        $bisaDelete = Auth::user()->can('purchaseOrder-delete');
        $bisaAuthorize = Auth::user()->can('purchaseOrder-authorize');

        return Datatables::of($data)
        ->addColumn('action', function ($data) use($lockDateToDate,$bisaAuthorize,$bisaEdit,$bisaDelete) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            
            // if ( $data->statusku and ($data->status == '2' or $data->status == '1') ){
            if ( $data->status == '2' or $data->status == '1' ){
                if ($bisaAuthorize) {
                $buttons .=         '<a href="'. route('purchaseOrder.edit', ['id'=>Crypt::encryptString($data->idku)]) .'" class="dropdown-item">
                                        <i data-feather="check"></i>
                                        <span>'. __("Approve") .'</span>
                                    </a>';
                }
            }
            if ( $data->status == '1' or $data->status == '2' ){
                $poDate = date('Y-m-d', strtotime($data->po_date));
                if($poDate>=$lockDateToDate){
                    if ($bisaEdit) {
                    $buttons .=         '<a href="'. route('purchaseOrder.edit', ['id'=>Crypt::encryptString($data->idku)]) .'" class="dropdown-item">
                                            <i data-feather="file-text"></i>
                                            <span>'. __("Edit") .'</span>
                                        </a>';
                    }
                }
            }
            if (($data->status == '2') || ($data->status == '3') ){
                // if( $data->order_type == 'tso' and $data->status_tso != 3 ){
                //     $buttons .= "<a href='javascript:void(0);'
                //                     data-url='". route('purchaseRequest.warning',['tsoCode'=>$data->tso_code]) ."'
                //                     data-size='sm'
                //                     data-ajax-popup='true'
                //                     data-title='Warning'
                //                     class='dropdown-item'>
                //                     <i data-feather='corner-down-left' class='feather-14-red'></i>
                //                     <span>". __('Revision') ."</span>
                //                 </a>";
                // }else{
                    $poDate = date('Y-m-d', strtotime($data->po_date));
                    if($poDate>$lockDateToDate){
                        $buttons .= "<a href='javascript:;'
                                        id='revisionReasonButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#reasonModalRevision'
                                        data-href='". route('purchaseOrder.revision', ['id'=>Crypt::encryptString($data->idku),'nR'=>$data->num_revision]) ."'>
                                        <i data-feather='corner-down-left' class='feather-14-red'></i>
                                        <span>". __('Revision') ."</span>
                                    </a>";
                    }
                // }

                // if (Auth::user()->can('purchaseOrder-revision')) {
                //     $buttons .=         '<a href="'. route('purchaseOrder.revision', ['id'=>Crypt::encryptString($data->idku),'nR'=>$data->num_revision]) .'" class="dropdown-item">
                //                             <i data-feather="copy"></i>
                //                             <span>'. __("Revision") .'</span>
                //                         </a>';
                // }
            }
            
            $buttons .=         '<a href="'. route('purchaseOrder.print', ['id'=>Crypt::encryptString($data->idku)]) .'" target="_blank" class="dropdown-item">
                                    <i data-feather="printer"></i>
                                    <span>'. __("Print") .'</span>
                                </a>';
            
            $buttons .=         '<a href="'. route('purchaseOrder.show', ['id'=>Crypt::encryptString($data->idku)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    <span>'. __("Detail") .'</span>
                                </a>';

            if ( $data->status == '1' or $data->status == '2' or $data->status == '3' ){
                if ($bisaDelete) {
                    $buttons .="<a href='javascript:;'
                    class='dropdown-item' 
                    data-size='sm'
                    data-ajax-delete='true'
                    data-confirm='Are You Sure want to Close?|This action can not be undone. Do you want to continue?' 
                    data-confirm-yes='document.getElementById(\""."delete-form-".$data->idku."\").submit();'
                    data-modal-id='".$data->idku."'
                    id='deleteButton'
                    data-url='". route('purchaseOrder.clear', ['id'=>Crypt::encryptString($data->idku)]) ."'>
                    <i data-feather='x' class='feather-14-red'></i>
                    <span>". __('Close') ."</span>
                    </a>";
                }
            }

            if ( $data->status == '1' ){
                $poDate = date('Y-m-d', strtotime($data->po_date));
                if($poDate>$lockDateToDate){
                    if ($bisaDelete) {
                        $buttons .=         "<a href='javascript:;'
                                            class='dropdown-item' 
                                            data-size='sm'
                                            data-ajax-delete='true'
                                            data-confirm='Are You Sure want to Delete?|This action can not be undone. Do you want to continue?' 
                                            data-confirm-yes='document.getElementById(\""."delete-form-".$data->idku."\").submit();'
                                            data-modal-id='".$data->idku."'
                                            id='deleteButton'
                                            data-url='". route('purchaseOrder.destroy', ['id'=>Crypt::encryptString($data->idku)]) ."'>
                                            <i data-feather='trash-2' class='feather-14-red'></i>
                                            <span>". __('Delete') ."</span>
                                        </a>";
                    }
                }
            }

            $buttons .=     '</div>
                        </div>';

            return $buttons;
        })
        ->addColumn('po_number', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','REVISED','DECLINE'];
            // return '<div class="badge d-block '.$badges[$data->status - 1].'"><a name="'.$data->po_number.'" href="'. route('purchaseOrder.show', ['id'=>Crypt::encryptString($data->idku)]) .'" ><span>'.$data->po_number.'</span></a></div>';
            return '<span style="display: none;">'.$data->po_number.'</span><a class="badge d-block '.$badges[$data->status - 1].'" name="'.$data->po_number.'" href="'. route('purchaseOrder.show', ['id'=>Crypt::encryptString($data->idku)]) .'" ><span>'.$data->po_number.'</span></a>';
        })
        ->addColumn('status', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
            $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','REVISED','DECLINE'];
            return "<div class='badge ".$badges[$data->status - 1]."'>".$statusPo[$data->status - 1]."</div>";
        })
        ->rawColumns(['action','status','po_number'])
        ->make(true);
    }

    public function listDetail(Request $request)
    {

        $searchPo = strtolower($request->searchPo);
        $username = Auth::user()->username;
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $orderDate = $request->orderDate;
        $fromDate ="";
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
            // $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            // $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('purchase_order_det')
        ->leftJoin('purchase_order_hdr','purchase_order_hdr.po_number','purchase_order_det.po_number')
        ->leftJoin('purchase_request_hdr','purchase_request_hdr.pr_number','purchase_order_det.pr_number')
        ->leftJoin('depts','depts.code','purchase_request_hdr.dept')
        ->leftJoin('article','article.article_code','purchase_order_det.article_code')
        ->leftJoin('article_types','article_types.code','article.article_type')
        ->leftJoin('third_party','third_party.kode','purchase_order_hdr.supplier_id')
        ->leftJoin('uom','uom.code','purchase_order_det.uom')
        ->where(function ($query) use ($searchPo,$searchStatus,$orderDate,$fromDate,$toDate,$searchSupplier) {
            $searchSupplier ? $query->where('purchase_order_hdr.supplier_id',$searchSupplier) : '';
            $searchPo ? $query->where('purchase_order_det.po_number','ilike','%'.$searchPo.'%') : '';
            $searchStatus ? $query->where('purchase_order_hdr.status',$searchStatus) : $query->whereNotIn('purchase_order_hdr.status',['5','6','7','8']);
            $orderDate ? $query->whereBetween(DB::raw("to_date(purchase_order_hdr.po_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        ->select('purchase_order_det.*'
        ,'purchase_order_hdr.*'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'third_party.nama as supp_name'
        ,'uom_group'
        ,'purchase_order_hdr.status as statusku'
        ,'article_types.name as article_type_name'
        ,'qty as qtyku'
        ,DB::raw("price*qty*purchase_order_hdr.ppn/100 as total_ppn")
        ,DB::raw("price*qty as total_dpp")
        ,DB::raw("price*qty*purchase_order_hdr.pph22/100 as total_pph22")
        ,DB::raw("(((price*qty)-discount)+(price*qty*purchase_order_hdr.ppn/100)-(price*qty*purchase_order_hdr.pph22)) as grand_total")
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty,'999,999,999') else TO_CHAR(qty,'999,999,999.99') end as qtyku")
        // ,DB::raw("TO_CHAR(price*qty*purchase_order_hdr.ppn/100,'999,999,999') as total_ppn")
        // ,DB::raw("TO_CHAR(price*qty,'999,999,999') as total_dpp")
        // ,DB::raw("TO_CHAR(price*qty*purchase_order_hdr.pph22/100,'999,999,999') as total_pph22")
        // ,DB::raw("TO_CHAR((((price*qty)-discount)+(price*qty*purchase_order_hdr.ppn/100)-(price*qty*purchase_order_hdr.pph22)),'999,999,999') as grand_total")
        ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = purchase_order_det.po_number) as approval_by")
        ,'depts.name as nama_dept'
        ,'article.uom as article_uom'

        )
        ->orderBy('purchase_order_det.id')
        ->get(); 

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
            
        return Datatables::of($data)
        // ->addColumn('statusku', function ($data) {
        //     if ($data->statusku>0){
        //         $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
        //         $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','REVISED','DECLINE'];
        //         return "<div class='badge ".$badges[$data->statusku - 1]."'>".$statusPo[$data->statusku - 1]."</div>";
        //     }
        // })
        // ->rawColumns(['status'])
        ->make(true);
    }

    public function print(Request $request)
    {
        $id=Crypt::decryptString($request->id);

        $data['companies']=DB::table('company')
        ->where('code','ASN')
        ->select('name as nama', 'address as alamat', DB::RAW('(select region_name from regions where region_code = city::integer)  as kota'),'tlp')
        ->get()->first();
            
        $poHdr=DB::table('purchase_order_hdr')
        ->where('id',$id)
        ->first();

        $poNumber=$poHdr -> po_number;
    
        $data['details']=DB::table('purchase_order_det')
        ->leftJoin('article','article.article_code','purchase_order_det.article_code')
        ->where('po_number',$poNumber)
        ->get();

        $nilaiPpn = $poHdr->ppn;
        $nilaiPph23 = $this->nilaiPph23;

        $data['totals']=DB::select("SELECT *
        ,gross 
        ,(gross*nilai_discount/100) as discount 
        ,case when dpp_lain > 0 then (gross-(gross*nilai_discount/100))+round((dpp_lain*nilai_ppn/100))-((gross-(gross*nilai_discount/100))*nilai_pph23/100)
        else (gross-(gross*nilai_discount/100))+round(((gross-(gross*nilai_discount/100))*nilai_ppn/100))-((gross-(gross*nilai_discount/100))*nilai_pph23/100)end as netto
        --,(gross-(gross*nilai_discount/100))+((gross-(gross*nilai_discount/100))*nilai_ppn/100)-((gross-(gross*nilai_discount/100))*nilai_pph23/100) as netto 
        ,(gross-(gross*nilai_discount/100)) as dpp
        ,case when dpp_lain > 0 then round(dpp_lain*nilai_ppn/100) else round((gross-(gross*nilai_discount/100))*nilai_ppn/100) end as ppn
        --,(gross-(gross*nilai_discount/100))*nilai_ppn/100 as ppn 
        ,(gross-(gross*nilai_discount/100))*nilai_pph23/100 as pph23 
        ,'$nilaiPpn' as angka_ppn
        ,'$nilaiPph23' as angka_pph23       
        from (
            select a.po_number
            ,authorized_by
            ,validate_by
            ,sum(qty) as qty
            ,sum(qty*price) as gross
            ,max(b.discount) as nilai_discount
            ,max(b.ppn) as nilai_ppn
            ,max(b.pph22) as nilai_pph23
            ,max(b.dpp_lain_value) as dpp_lain
            ,max(b.dpp_lain_pembilang) as pembilang
            ,max(b.dpp_lain_penyebut) as penyebut
            -- ,sum(qty*price*b.ppn/100) as ppn 
            -- ,sum(qty*price*b.pph22/100) as pph23 
            from purchase_order_det a
            left join purchase_order_hdr b
            on a.po_number = b.po_number 
            where a.po_number = '$poNumber'
            group by a.po_number,authorized_by,validate_by) as oki");

        $data['suppliers']=DB::table('third_party')
        ->where('kode',$poHdr -> supplier_id)
        ->get();

        $data['keterangan']=$poHdr ->note;
        $data['poNumber'] =$poNumber;
        $data['poDate'] =$poHdr -> po_date;
        $data['poTerm'] =$poHdr -> termin;
        $data['poDelDate'] =$poHdr -> delivery_date;
        
        $data['status'] = $poHdr->status;
        $data['no'] =0;

        // $poNumber = 'PO-ASN/2022/V/8';

        $data['approved'] = DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_number',$poNumber)
        ->orderBy('approval_order','desc')
        ->value('users.name');


        $data['approval1']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$poNumber)
        ->where('approval_order',1)
        ->first();

        $data['approval2']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$poNumber)
        ->where('approval_order',2)
        ->first();

        $data['approval3']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$poNumber)
        ->where('approval_order',3)
        ->first();

        $data['approval4']=DB::table('approval_history')
        ->leftJoin('users','users.username','approval_history.username')
        ->where('module_code',$this->moduleCode)
        ->where('module_number',$poNumber)
        ->where('approval_order',4)
        ->first();


        view()->share($data);

        $pdf = PDF::loadView('purchaseOrder.print');
        return $pdf->stream("PO_$poNumber.pdf");

    }

    public function listArticleByPr(Request $request)
    {
        $prNumber = $request->prNumber;
        $suppCode = $request->suppCode;
        /* 
            Permintaan dari bu ifah tidak usah di filter by supplier
            11 04 2022 permintaan batal dari bu Yorin, jadi tetap di filter
            ->where($field,$code)
            ->where('po_number','=',null)
        */
        
        $data= DB::table('purchase_request_det') 
            ->leftJoin('article','article.article_code','=','purchase_request_det.article_code')
            ->leftJoin('article_stock','article_stock.article_code','=','purchase_request_det.article_code')
            ->leftJoin('group_materials','group_materials.code','=','article.group_of_material')
            ->leftJoin('article_supplier','article_supplier.article_code','=','purchase_request_det.article_code')
            ->leftJoin('uom','uom.code','=','purchase_request_det.uom')            
            ->where('article_supplier.supplier_code',$suppCode)
            // ->whereIn('article_supplier.supplier_code', function($query) use ($suppCode){
            //     $query->select('kode')->from('third_pary')->where('third_party_type','supp')->where('kode',$suppCode);
            // })
            ->where('pr_number','=',$prNumber)
            ->orderBy('article.article_desc')
            ->distinct('article.article_desc')
            ->select(DB::raw("concat(article.article_alternative_code,' - ',article.article_desc) as article_description")
            ,'article.article_code as artikel_code'
            ,'article.article_desc','article.costprice'
            ,'article_stock.article_qty as qty_stock'
            ,'purchase_request_det.uom as uom1'
            ,'group_materials.name as group'
            ,'uom.uom_group'
            // ,'purchase_request_det.qty'
            ,DB::raw("(SELECT price as last_price from purchase_order_det where article_code = purchase_request_det.article_code and updated_at is not null and po_number not like '%-R%' order by updated_at desc limit 1) as last_price")
            ,DB::raw("(select coalesce(sum(qty),0) from purchase_order_det 
                where article_code = purchase_request_det.article_code 
                and pr_number = purchase_request_det.pr_number
                and po_number in (select po_number from purchase_order_hdr where status not in ('5','6','7','8'))
                ) as qty_po")
            //qty yang dikeluarkan adalah qty sisa dari PR dikurangi qty yang sudah di order
            ,DB::raw("purchase_request_det.qty - (select coalesce(sum(qty),0) from purchase_order_det 
                where article_code = purchase_request_det.article_code 
                and pr_number = purchase_request_det.pr_number
                and po_number in (select po_number from purchase_order_hdr where status not in ('5','6','7','8'))
                ) as qty")
            )
            ->get();

        return response()->json(array('data' => $data));

    }

    public function report(Request $request)
    {
        $data['title'] = "$this->title Report";
        $data['kolom'] = $this->getTableColoumnReport();

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
            
        return view("purchaseOrder.report",$data);
    }

    public function getTableColoumnReport(){
        $kolom=
        [
            ['data'=>'dept_name','name'=>'dept_name','title'=>'Departement'],
            ['data'=>'po_date','name'=>'po_date','title'=>'TGL PO'],
            ['data'=>'po_number','name'=>'po_number','title'=>'PO Number'],
            ['data'=>'pr_number','name'=>'pr_number','title'=>'PR Number'],
            ['data'=>'supp_kode','name'=>'supp_kode','title'=>'Kode Supp'],
            ['data'=>'supp_name','name'=>'supp_name','title'=>'Nama Supplier'],
            ['data'=>'article_alternative_code','name'=>'article_alternative_code','title'=>'Article code'],
            ['data'=>'article_desc','name'=>'article_desc','title'=>'Article desc'],
            ['data'=>'qtyku','name'=>'qtyku','title'=>'Qty PO'],
            ['data'=>'qty_lpb','name'=>'qty_lpb','title'=>'Qty LPB'],
            ['data'=>'balance','name'=>'balance','title'=>'Balance'],
            ['data'=>'article_uom','name'=>'article_uom','title'=>'STN'],
            // ['data'=>'uom','name'=>'uom','title'=>'STN'],
            ['data'=>'price','name'=>'price','title'=>'Harga'],
            ['data'=>'total_dpp','name'=>'total_dpp','title'=>'Total Harga'],
            ['data'=>'note','name'=>'note','title'=>'Keterangan'],
            ['data'=>'date_period','name'=>'date_period','title'=>'date_period','visible'=>false],
        ];
        return json_encode($kolom, true);
    }

    public function listReport(Request $request)
    {

        /*
            23-1-2026
            Permintaan dari ibu Silvana untuk PO yang sudah closed (status 6) tetap ditampilkan

        */
        
        $searchPo = $request->searchPo;
        $username = Auth::user()->username;
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $orderDate = $request->orderDate;
        $fromDate ="";
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
            // $fromDate = implode("/", array_reverse(explode("-", trim($date[0]))));
            // $toDate = implode("/", array_reverse(explode("-", trim($date[1]))));
        }

        $data = DB::table('purchase_order_det')
        ->leftJoin('purchase_order_hdr','purchase_order_hdr.po_number','purchase_order_det.po_number')
        ->leftJoin('purchase_request_hdr','purchase_request_hdr.pr_number','purchase_order_det.pr_number')
        ->leftJoin('article','article.article_code','purchase_order_det.article_code')
        ->leftJoin('article_types','article_types.code','article.article_type')
        ->leftJoin('third_party','third_party.kode','purchase_order_hdr.supplier_id')
        ->leftJoin('uom','uom.code','purchase_order_det.uom')
        ->where(function ($query) use ($searchPo,$searchStatus,$orderDate,$fromDate,$toDate,$searchSupplier) {
            $searchSupplier ? $query->where('purchase_order_hdr.supplier_id',$searchSupplier) : '';
            $searchPo ? $query->whereIn('purchase_order_det.po_number',$searchPo) : '';
            $searchStatus ? $query->where('purchase_order_hdr.status',$searchStatus) : '';
            $orderDate ? $query->whereBetween(DB::raw("to_date(purchase_order_hdr.po_date,'DD-MM-YYYY')"), [$fromDate, $toDate]) : '';
        })
        // ->whereNotIn('purchase_order_hdr.status',['5','6','7','8'])
        // ->whereNotIn('purchase_order_hdr.status',['5','7','8'])
        ->whereIn('purchase_order_hdr.status',['1','2','3','4','6'])
        ->select('purchase_order_det.*'
        ,db::raw("(select sum(qty) from receiving_det where rec_number in (select rec_number from receiving_hdr where po_number = purchase_order_det.po_number and status not in ('5','7')) and article_code = purchase_order_det.article_code group by article_code) as qty_lpb")
        ,db::raw("purchase_order_det.qty-coalesce((select sum(qty) from receiving_det where rec_number in (select rec_number from receiving_hdr where po_number = purchase_order_det.po_number and status not in ('5','7')) and article_code = purchase_order_det.article_code group by article_code),0) as balance")
        ,'purchase_order_hdr.*'
        ,'article_alternative_code'
        ,'article.article_desc'
        ,'third_party.nama as supp_name'
        ,'third_party.kode as supp_kode'
        // ,'uom_group'
        // ,'purchase_order_hdr.status as statusku'
        // ,'article_types.name as article_type_name'
        ,'purchase_order_det.qty as qtyku'
        // ,DB::raw("case when uom_group = 'PIECE' then TO_CHAR(qty,'999,999,999') when uom_group <> 'PIECE' then TO_CHAR(qty,'999,999,999.99') end as qtyku")
        // ,DB::raw("TO_CHAR(price*qty*purchase_order_hdr.ppn/100,'999,999,999') as total_ppn")
        // ,DB::raw("TO_CHAR(price*qty,'999,999,999') as total_dpp")
        ,DB::raw("purchase_order_det.price*purchase_order_det.qty as total_dpp")
        // ,DB::raw("TO_CHAR(price,'999,999,999') as price")
        // ,DB::raw("TO_CHAR(price*qty*purchase_order_hdr.pph22/100,'999,999,999') as total_pph22")
        // ,DB::raw("TO_CHAR((((price*qty)-discount)+(price*qty*purchase_order_hdr.ppn/100)-(price*qty*purchase_order_hdr.pph22)),'999,999,999') as grand_total")
        // ,DB::raw("(select STRING_AGG((select name from users where username = a.username), ' -> ' ORDER BY approval_order) AS main from approval_history a where module_number = purchase_order_det.po_number) as approval_by")
        // ,'depts.name as nama_dept'
        ,DB::raw("(select name from depts where code = purchase_request_hdr.dept limit 1) as dept_name")
        ,DB::RAW("to_date(po_date,'dd-mm-yyyy') as date_period")
        ,'article.uom as article_uom'
        )
        ->orderBy('purchase_order_det.id')
        ->get(); 

        // $data['status'] = ['1'=>'NEW','2'=>'VALIDATE','3'=>'APPROVED','4'=>'RECEIVED','5'=>'CANCELED','6'=>'CLOSED','7'=>'REVISED','8'=>'DECLINE'];
            
        return Datatables::of($data)
        // ->addColumn('statusku', function ($data) {
        //     if ($data->statusku>0){
        //         $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary','badge-danger'];            
        //         $statusPo = ['NEW','VALIDATED','APPROVED','RECEIVED','CANCELED','CLOSED','REVISED','DECLINE'];
        //         return "<div class='badge ".$badges[$data->statusku - 1]."'>".$statusPo[$data->statusku - 1]."</div>";
        //     }
        // })
        // ->rawColumns(['status'])
        ->make(true);
    }

    public function reInsertRecIntoKas($recNumber)
    {
        /*
        kalau sudah ada di BB maka di edit semua datanya
        kalau belum ada di abaikan saja
        */
        $sudahAdaDiPembukuan = DB::table('kas_hdr')->where('voucher_number',$recNumber)->count();
        if ($sudahAdaDiPembukuan > 0){
            DB::table('kas_det')->where('voucher_number',$recNumber)->delete();
            DB::table('kas_hdr')->where('voucher_number',$recNumber)->delete();

            DB::statement("INSERT into kas_hdr (voucher_number,voucher_type,voucher_date,receive_from,amount,period,year,note,status,created_by,updated_by,created_at,updated_at,description)
            select rec_number as voucher_number
            ,'REC' as voucher_type
            ,do_date as voucher_date
            ,supplier_id as receive_from
            ,(select sum((qty+qty_free)*price) from receiving_det where rec_number = receiving_hdr.rec_number) as amount
            ,substring(do_date,4,2)::integer as period
            ,substring(do_date,7) as year,note
            ,'3' as status
            ,created_by
            ,updated_by
            ,now()
            ,now()
            ,rec_number as description 
            from receiving_hdr
            where status = '4'
            and rec_number in (select rec_number
            from receiving_det
            left join article on article.article_code = receiving_det.article_code
            where article_type in ('RMP','CM1','CM2','RM'))
            and rec_number = '$recNumber'
            order by created_at");

            DB::statement("INSERT into kas_det (voucher_number,account,description,debit,created_by,updated_by,created_at,updated_at,cost_center) 
            select rec_number as voucher_number
            ,case when article_type='RMP' then '1100.31' when article_type='RM' then '1100.31' when article_type='CM1' then '1100.32.1' when article_type='CM2' then '1100.32.2' else '' end as account
            ,concat(rec_number,' ',article_desc) 
            ,(qty+qty_free)*price as debit
            ,receiving_det.created_by
            ,receiving_det.updated_by
            ,now()
            ,now()
            ,'003' as cost_center
            from receiving_det
            left join article on article.article_code = receiving_det.article_code
            where article_type in ('RMP','CM1','CM2','RM')
            and (qty+qty_free) > 0
            and rec_number in (select rec_number from receiving_hdr where status = '4' and rec_number = '$recNumber')
            order by receiving_det.created_at");
        }
        return 'selesai';
    }

}
