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

class ReceivingRmController extends Controller
{
    private $title;
    public function __construct()
    {
        $this->title = "Receiving RM";
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
            ['data'=>'prepared_by','name'=>'prepared_by','title'=>'Prepared By'],
            ['data'=>'authorized_by','name'=>'authorized_by','title'=>'Authorized By'],
            ['data'=>'status','name'=>'status','title'=>'Status']
        ];
        return json_encode($kolom, true);
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;
        $data['custs'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $data['status'] = ['1'=>'Draft','2'=>'Update','3'=>'Posting','4'=>'Cancel'];
        $data['kolom'] = $this->getTableColoumn();
            
        return view("receivingRm.index",$data);
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
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("receivingRm.create",$data);
    }

    public function soDetail(Request $request)
    {
        $so = $request->value;
        $data = DB::select("SELECT 
                a.*,
                a.article_code,
                article_alternative_code,
                article_desc,uom_group, 
                (COALESCE(a.qty,0)-COALESCE(b.qty,0)) as qty_order
                from sales_order_det a
                left join uom on uom.code=a.uom
                left join article on article.article_code = a.article_code
                left join 
                    (select so, article_code,sum(qty) as qty,price from (
                        select *,(select po_number from receiving_hdr where rec_number = a.rec_number) as so from receiving_det a where rec_number in (
                        select rec_number from receiving_hdr where status = '3' and po_number = '$so')
                    ) z
                group by so, article_code,price) b
                on a.so_code = b.so and a.article_code = b.article_code
                where so_code = '$so'");

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $docNumber = $request->docNumber;
        $docDate = $request->docDate;
        $soNumber = $request->soNumber;
        $customer = $request->supp;
        $recDate = $request->recDate;
        $note = $request->note;
        $articles = json_decode($request->articles);
        $recType = "RM";
        $statusRec ="Draft";
        $status = '1';
        $authorizedBy = "";

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel
        
        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "Invoice : $docNumber has already been taken on PO : $soNumber",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($soNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$soNumber])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            'docNumber'=>'required|iunique:receiving_hdr,do_number,po_number',
            'recDate'  => 'required',
            'docDate'  => 'required',
            'soNumber'  => 'required',
            // 'customer'  => 'required',
        ],$customMessages);
        
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
            $hasilUpdate = AppHelpers::resetCode('REC');
            $recNumber = $this->getLastCode('REC');
            DB::beginTransaction();
            try {
                    DB::table('receiving_hdr')->insert([
                        'rec_number' => $recNumber,
                        'do_number' => $docNumber,
                        'inv_number' => $docNumber,
                        'inv_date' => $docDate,
                        'po_number' => $soNumber,
                        'supplier_id' => $customer,
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

                    $dataSet = [];
                    foreach ($articles as $val) {
                        $dataSet[] = [
                            'rec_number' => $recNumber,
                            'article_code' => $val->article_code,
                            'qty' => $val->qty,
                            'uom_rec' => $val->uom,
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
                    return response()->json(array('statusRec' => $statusRec, 'title' => $title, 'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
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
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Detil $this->title";
        $data['subtitle'] = "Detil $this->title";

        $data['header'] = DB::table('receiving_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('receiving_det')
        ->leftJoin('article','article.article_code','=','receiving_det.article_code')
        ->leftJoin('uom','receiving_det.uom_rec','uom.code')
        ->where('receiving_det.rec_number',$data['header']->rec_number)
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

        $statusRec = ['DRAFT','UPDATED','POSTED','CANCEL'];
        $data['statusRec'] = $statusRec[$data['header']->status-1];

        return view("receivingRm.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('receiving_hdr')
        ->where('id',$id)
        ->get()->first();

        $data['detail'] = DB::table('receiving_det')
        ->leftJoin('article','article.article_code','=','receiving_det.article_code')
        ->leftJoin('uom','receiving_det.uom_rec','uom.code')
        ->where('receiving_det.rec_number',$data['header']->rec_number)
        ->orderBy('receiving_det.id')
        // ->select('receiving_det.article_code')
        ->get();       

        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        $data['uoms'] = DB::table('uom')
        ->orderBy('name')
        ->get();

        // $data['status'] = ['1'=>'DRAFT','2'=>'UPDATED','3'=>'POSTED','4'=>'CANCELED'];
        $statusRec = ['DRAFT','UPDATED','POSTED','CANCEL'];
        $data['statusRec'] = $statusRec[$data['header']->status-1];

        return view("receivingRm.edit",$data);
        
    }

    public function update(Request $request)
    {
        $username =  Auth::user()->username;
        $recNumber = $request->recNumber;
        $docNumber = $request->docNumber;
        $docDate = $request->docDate;
        $soNumber = $request->soNumber;
        $customer = $request->supp;
        $recDate = $request->recDate;
        $note = $request->note;
        $articles = json_decode($request->articles);
        $recType = "RM";
        $statusRec ="Update";
        $status = '2';
        $authorizedBy = "";

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $customMessages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "Invoice : $docNumber has already been taken on PO : $soNumber",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($soNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$soNumber])->count();
        });
        
        $validation = Validator::make($request->all(),$messages = [
            // 'docNumber'=>'required|iunique:receiving_hdr,inv_number,po_number',
            'recDate'  => 'required',
            'poNumber'  => 'required',
            // 'customer'  => 'required',
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
                            'inv_number' => $docNumber,
                            'inv_date' => $docDate,
                            'po_number' => $docNumber,
                            'supplier_id' => $customer,
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

                    //Delete kalo article tidak ada di rece $recNumber dan article nya $val->article_code
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
        // $statusRec = ['DRAFT','UPDATED','POSTED','CANCEL'];
        $username =  Auth::user()->username;
        $recNumber = $request->recNumber;
        $recType = "RM";
        $statusRec ="Posting";
        $status = '3';
        $authorizedBy = Auth::user()->username;
        $movementDate = date("d-m-Y");

        // Update stock kalo article nya udah ada
        $sqlUpdate = "UPDATE article_stock a set article_qty = COALESCE(a.article_qty,0)  + COALESCE(b.qty,0)
        from (
        select art_code, (qty*factor_qty) as qty from 
        (
            select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = o.uom_rec and unit_to = article.uom) as factor_qty from (
            select * from receiving_det where rec_number in (
            select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) o
            left join article on article.article_code = o.article_code
        ) c
        ) b
        where a.article_code=b.art_code";

        //Insert ke stock kalo article nya belum ada
        $sqlInsert = "INSERT into article_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
        select 'HO',art_code,article_type,'00',(qty*factor_qty) as qty,uom from 
        (
            select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = z.uom_rec and unit_to = article.uom) as factor_qty from (
            select * from receiving_det where rec_number in (
            select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) z
            left join article on article.article_code = z.article_code
            where article.article_code not in (select article_code from article_stock)
        ) y";

        //update table movement
        $sqlMovement = "INSERT into movement
        (movement_date,artikel_code,artikel_desc,movement_min,movement_plus,movement_price,movement_transnno,movement_type,movement_desc)
        select 
        '$movementDate',
        article_code,
        (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
        0,
        qty,
        price,
        rec_number,
        'REC',
        (select po_number from receiving_hdr where rec_number=a.rec_number) as po from receiving_det a where rec_number in 
        (select rec_number from receiving_hdr where rec_number = '$recNumber' and status = '3' and qty <> 0)";
    
        DB::select($sqlUpdate);
        $rowAffected = DB::select($sqlInsert);
        
        if ($rowAffected > 0){
            DB::table('receiving_hdr')
            ->where('rec_number',$recNumber)
            ->update(
                [   
                    'status' => $status,
                    'authorized_by' => $authorizedBy,
                    'authorized_at' => date('Y-m-d H:i:s'),
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            DB::select($sqlMovement);

            DB::commit();
            $title ="Posting $this->title";
            $alert  ="success";
            $message  = "$title $recNumber Successfully Posting";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
        }else{
            $title ="Posting $this->title";
            $alert  ="warning";
            $message  = "$title $recNumber Failed to Posting";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return response()->json(array('statusRec' => $statusRec,'status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
        }
    }

    public function destroy(Request $request)
    {
        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $username =  Auth::user()->username;       
        $id = $request->id;
        $status = "4";

        $poHdr= DB::table('receiving_hdr')
        ->where('id',$id)
        ->get()->first();

        $recNumber = $poHdr->rec_number;
        $docNumber = $poHdr->inv_number;
        $note = $poHdr->note;

        $rowAffected=DB::table('receiving_hdr')
        ->where('rec_number',$recNumber)
        ->update(
            [   
                'rec_number' => $recNumber."(C)",
                'inv_number' => $docNumber ? $docNumber."(C)" :$docNumber ,
                'status' => $status,
                'note' => $note." (Cancel)",
                'updated_by' => Auth::user()->username,
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        if($rowAffected>0){
            DB::table('receiving_det')
            ->where('rec_number',$recNumber)
            ->update(
                [   
                    'rec_number' => $recNumber."(C)",
                    'updated_by' => Auth::user()->username,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );

            $title ="Cancel $this->title";
            $alert  ="success";
            $message  = "$title $recNumber Successfully Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);  
        }else{
            $title ="Cancel $this->title";
            $alert  ="warning";
            $message  = "$title $recNumber Failed to Cancel";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'title' => $title,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        // status:
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $searchRec = strtolower($request->searchRec);
        $searchPo = strtolower($request->searchPo);
        $searchInv = strtolower($request->searchInv);
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $recDate = $request->recDate;
       

        $filter='';
        
        $filter.="lower(a.rec_type) = 'rm' and ";

        if ($searchRec !='' ){
            $filter.="lower(a.rec_number) like '%$searchRec%' and ";
        }

        if ($searchPo !='' ){
            $filter.="lower(a.po_number) like '%$searchPo%' and ";
        }

        if ($searchInv !='' ){
            $filter.="lower(a.inv_number) like '%$searchInv%' and ";
        }

        if ($searchSupplier  != '' ){
            $filter.="supplier_id = '$searchSupplier' and ";            
        }

        if ($searchStatus  != '' ){
            $filter.="status = '$searchStatus' and ";            
        }

        if ($recDate  != '' ){
            $date = explode("to",$recDate);
            $date1=trim($date[0]);
            $date2=trim($date[1]);
            $filter.= "to_date(rec_date, 'DD/MM/YYYY')  BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
        }

        
        if ($filter !=''){
            $filter=" where ".substr($filter,0,-4);
        }

        $data = DB::select("SELECT id
        ,inv_number
        ,rec_number
        ,rec_date
        ,po_number
        ,inv_date,
        (select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name 
        ,prepared_by
        ,authorized_by
        ,status
        from receiving_hdr a $filter");

        // $data=DB::select("SELECT *,delivery_date,(select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name,(gross-discount)+ppn as netto from (
        //     select b.status,b.id,a.po_number,supplier_id,po_date,delivery_date,pkp,termin,authorized_by,prepared_by,uom,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from purchase_order_det a
        //     left join purchase_order_hdr b
        //     on a.po_number = b.po_number 
        //     $filter
        //     group by b.id,a.po_number,supplier_id,po_date,delivery_date,pkp,termin,authorized_by,prepared_by,uom,b.status) as oki");
        
        // $data=DB::table('purchase_order_hdr')->get();

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('receiving-edit')) {
                $buttons .=         '<a href="'. route('receivingRm.edit', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                $buttons .=         '<a href="'. route('receivingRm.print', ['id'=>Crypt::encryptString($data->id)]) .'" target="_blank" class="dropdown-item">
                                        <i data-feather="printer"></i>
                                        Print
                                    </a>';
                }
            }
            $buttons .=         '<a href="'. route('receivingRm.show', ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                    <i data-feather="list"></i>
                                    Detail
                                </a>';
                
            if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('receiving-delete')) {
                $buttons .=         "<a href='javascript:;'
                                        id='deleteButton'
                                        class='dropdown-item'
                                        data-toggle='modal'
                                        data-target='#smallModalCancel'
                                        data-href='". route("receivingRm.destroy", ["id"=>Crypt::encryptString($data->id)]) ."'>
                                        <i data-feather='trash-2' class='feather-14-red'></i>
                                        Cancel
                                    </a>";
                }
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })

        ->addColumn('rec_number', function ($data) {
            $badges=['badge-primary','badge-info','badge-success','badge-warning','badge-danger','badge-dark','badge-secondary'];
            return '<span class="d-none">'.$data->rec_number.'</span><a class="badge d-block '.$badges[$data->status - 1].'" href="'. route('receivingRm.show', ['id'=>Crypt::encryptString($data->id)]) .'" >'.$data->rec_number.'</a>';
        })
        ->addColumn('status', function ($data) {
            $statusRec = ['DRAFT','UPDATED','POSTED','CANCEL'];
            return $statusRec[$data->status - 1];
        })
        ->rawColumns(['action','status','rec_number'])
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
            select a.po_number,authorized_by,prepared_by,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from purchase_order_det a
            left join purchase_order_hdr b
            on a.po_number = b.po_number 
            where a.po_number = '$poNumber'
            group by a.po_number,authorized_by,prepared_by) as oki");

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

        $pdf = PDF::loadView('receivingRm.print');
        return $pdf->stream("PO_$poNumber.pdf");

    }

    public function listSo(Request $request)
    {
        $supp= $request->value;      
        $output="";

        $data= DB::table("sales_order_hdr") 
        ->where("customer_id",$supp)
        // ->where("status","3")
        ->orderBy("so_code")
        ->select("so_code")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->so_code.'">'.$row->so_code.'</option>';            
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

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->code.'">'.$row->code.'</option>';            
        }        
        
        return $output;
    }
}
