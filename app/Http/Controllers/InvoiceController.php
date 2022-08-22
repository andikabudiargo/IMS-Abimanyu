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

class InvoiceController extends Controller
{

    private $title;
    public function __construct()
    {
        $this->title = "Invoice";
    }

    public function index(Request $request)
    {
        $data['title'] = $this->title;

        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $data['status'] = ['1'=>'Draft','2'=>'Update','3'=>'Posting','4'=>'Cancel'];
            
        return view("invoice.index",$data);
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
        
        $data['customers'] = DB::table('third_party')
        ->where ('third_party_type','=','cust')
        ->orderBy('nama')
        ->get();

        return view("invoice.create",$data);
    }

    public function dnDetail(Request $request)
    {
        $so = $request->soNumber;
        $dn = $request->dnNumber;
        $data = DB::select("SELECT 
            a.article_code,
            article_alternative_code,
            article_desc,
            a.qty,
            uom_group,
            a.uom,
            price,
            price_service,
            so_number,
            delivery_number
            from delivery_det a 
            left join sales_order_det b on b.so_code = a.so_number  and a.article_code = b.article_code
            left join uom on uom.code=a.uom
            left join article on article.article_code = a.article_code
            where 
            delivery_number = '$dn' 
            and so_number = '$so'");

        return response()->json($data);
    }

    // public function dnDetail(Request $request)
    // {
    //     $so = $request->value;
    //     $data['dnHdr']=DB::table('delivery_hdr')
    //     ->where('so_number',$id)
    //     ->get();

    //     return response()->json($data);
    // }


    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $articles = json_decode($request -> articles);
        $invDate = $request->invDate;
        $customer = $request->customer;
        $ppn = $request->ppn;
        $pph23 = $request->pph23;
        $totalPpn = $request->totalPpn;
        $totalPph = $request->totalPph;
        $note = $request->note;
        $status = '1';
        $gudang = 'false';
        $kurs = 1;

        // status:
        // 1 = New
        // 2 = Validated
        // 3 = Approved
        // 4 = Received
        // 5 = Canceled
        // 6 = Closed
        // 7 = Paid

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
            // // 'orderNumber' => 'required',
            // 'orderDate'  => 'required',
            // 'currency'  => 'required',
            // 'type'  => 'required',
            // 'customer'  => 'required',
        ]);
        
        $error_array = array();
        $success_output = '';
        // return $validation;
        if ($validation->fails()){
            foreach ($validation->messages()->getMessages() as $field_name => $messages){
                $error_array[] = $messages;
            }
            $title="Save Invoice";
            $alert ="error";
            return response()->json(array('status' => 0,'title' => $title, 'message' => $error_array,'alert' =>$alert));
        }else{
            $hasilUpdate = AppHelpers::resetCode('INV');
            $invCode = $this->getLastCode('INV');
            DB::beginTransaction();
            try {
                DB::table('invoice_hdr')->insert([
                    'invoice_number' => $invCode,
                    'invoice_date' => $invDate,
                    'customer_id' => $customer,
                    'so_number' => '',
                    'po_number' => '',
                    'dn_number' => '',
                    'dpp' => 0,
                    'other_admin' => 0 ,
                    'discount' => 0,
                    'ppn' => $ppn,
                    'pph23' => $pph23,
                    'npwp' => "",
                    'payment_term' => 0 ,
                    'payment_terms' => '',
                    'account_number' => '',
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
                        'invoice_number' => $invCode,
                        'article_code' => $val->article_code,
                        'so_number' => $val->so_number,
                        'dn_number' => $val->dn_number,
                        'qty' => $val->qty,
                        'uom' => $val->uom,
                        'price' => $val->price,
                        'price_service' => $val->price_service,
                        'ppn' => ($val->price*$val->qty) * $ppn/100,
                        'pph23' => ($val->price_service*$val->qty) * $pph23/100,
                        'created_by' => Auth::user()->username,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                }

                DB::table('invoice_det')->insert($dataSet);

                DB::commit();
                $title ='Save Invoice';
                $alert  ="success";
                $message  = "$title $invCode is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 1,'title' => $title, 'message' => $message,'alert'=>$alert,'invNumber'=>$invCode));

            } catch (Exception $e) {
                DB::rollBack();
                $title ='Save Invoice';
                $alert  ="warning";
                $message  = "$title $invCode is failed to save";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return response()->json(array('status' => 0,'title' => $title, 'message' => $message,'alert'=>$alert,'invNumber'=>$invCode));
            }
        }
    }

    public function show(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Details $this->title";
        $data['subtitle'] = "Details $this->title";

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

        $statusRec = ['Draft','Update','Posting','Cancel'];
        $data['statusRec'] = $statusRec[$data['header']->status-1];

        return view("invoice.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=Crypt::decryptString($request->id);
        $data['title'] = "Edit $this->title";
        $data['subtitle'] = "Edit $this->title";

        $data['header'] = DB::table('invoice_hdr')
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

        $statusRec = ['Draft','Update','Posting','Cancel'];
        $data['statusRec'] = $statusRec[$data['header']->status-1];

        return view("invoice.edit",$data);
        
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

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

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
            $alert ="alert-danger";
            return response()->json(array('status' => 0, 'message' => $error_array,'alert' =>$alert));
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
                    $alert  ="alert-success";
                    $message  = "Rec $recNumber is successfully updated";
                    \LogActivity::addToLog('Rec update ',"username: $username Status $message");
                    return response()->json(array('statusRec' => $statusRec,'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));

            } catch (Exception $e) {
                DB::rollBack();
                $alert  ="alert-warning";
                $message  = "Rec $recNumber is failed to updated";
                \LogActivity::addToLog('Rec update ',"username: $username Status $message");
                return response()->json(array('statusRec' => $statusRec,'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
            }
        }

    }

    public function posting(Request $request)
    {
        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $username =  Auth::user()->username;
        $recNumber = $request->recNumber;
        $recType = "NORMAL";
        $statusRec ="Posting";
        $status = '3';
        $authorizedBy = Auth::user()->username;

        // Update stock kalo article nya udah ada
        $sqlUpdate = "UPDATE article_stock a set article_qty = COALESCE(a.article_qty,0)  + COALESCE(b.qty,0)
        from (
        select art_code, (qty*factor_qty)+(qty_free*factor_free) as qty from 
        (
            select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = o.uom_rec and unit_to = article.uom) as factor_qty,(select unit_factor from uom_con where unit_from = o.uom_free and unit_to = article.uom) as factor_free  from (
            select * from receiving_det where rec_number in (
            select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) o
            left join article on article.article_code = o.article_code
        ) c
        ) b
        where a.article_code=b.art_code";

        //Insert ke stock kalo article nya belum ada
        $sqlInsert = "INSERT into article_stock (site_code,article_code,dept_code,location_number,article_qty,uom)
        select 'HO',art_code,article_type,'00',(qty*factor_qty)+(qty_free*factor_free) as qty,uom from 
        (
            select *,article.article_code as art_code,(select unit_factor from uom_con where unit_from = z.uom_rec and unit_to = article.uom) as factor_qty,(select unit_factor from uom_con where unit_from = z.uom_free and unit_to = article.uom) as factor_free  from (
            select * from receiving_det where rec_number in (
            select rec_number from receiving_hdr where rec_number = '$recNumber' and (status != '3' and status != '4'))) z
            left join article on article.article_code = z.article_code
            where article.article_code not in (select article_code from article_stock)
        ) y";

        //Insert into table movement
        $sqlMovement = "INSERT into movement
        (movement_date,artikel_code,artikel_desc,movement_min,movement_plus,movement_price,movement_transnno,movement_type,movement_desc)
        select 
        now()::timestamp::date,
        article_code,
        (select concat(article_alternative_code,'-',article_desc) from article where article_code = a.article_code) as article_desc,
        0,
        qty,
        price,
        rec_number,
        'REC',
        (select po_number from receiving_hdr where rec_number=a.rec_number) as po from receiving_det a where rec_number in (
        select rec_number from receiving_hdr where rec_number = '$recNumber' and status = '3' and qty <> 0)";
    
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
            $alert  ="alert-success";
            $message  = "Posting Rec $recNumber Successfully Posting";
            \LogActivity::addToLog('Posting Rec ',"username: $username Status $message");
            return response()->json(array('statusRec' => $statusRec,'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
        }else{
            $alert  ="alert-warning";
            $message  = "Posting Rec $recNumber Failed to Posting";
            \LogActivity::addToLog('Posting Rec ',"username: $username Status $message");
            return response()->json(array('statusRec' => $statusRec,'status' => 1, 'message' => $message,'alert'=>$alert,'recNumber'=>$recNumber));
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
        $invNumber = $poHdr->inv_number;
        $note = $poHdr->note;

        $rowAffected=DB::table('receiving_hdr')
        ->where('rec_number',$recNumber)
        ->update(
            [   
                'rec_number' => $recNumber."(C)",
                'inv_number' => $invNumber."(C)",
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

            $alert  ="alert-success";
            $message  = "Rec $recNumber Successfully Cancel";
            \LogActivity::addToLog('Rec cancel ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);  
        }else{
            $alert  ="alert-warning";
            $message  = "Rec $recNumber Failed to Cancel";
            \LogActivity::addToLog('Rec cancel ',"username: $username Status $message");
            return redirect()->back()->with(['alert'=>$alert,'message'=> $message]);
        }
    }

    public function list(Request $request)
    {
        // status:
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $searchInv = strtolower($request->searchInv);
        $searchSo = strtolower($request->searchSo);
        $searchCustomer = $request->searchCustomer; 
        $searchStatus = $request->searchStatus;
        $recDate = $request->recDate;       

        $filter='';
        
        // $filter.="lower(a.rec_type) = 'normal' and ";

        // if ($searchRec !='' ){
        //     $filter.="lower(a.rec_number) like '%$searchRec%' and ";
        // }

        // if ($searchPo !='' ){
        //     $filter.="lower(a.po_number) like '%$searchPo%' and ";
        // }

        // if ($searchInv !='' ){
        //     $filter.="lower(a.inv_number) like '%$searchInv%' and ";
        // }

        // if ($searchSupplier  != '' ){
        //     $filter.="supplier_id = '$searchSupplier' and ";            
        // }

        // if ($searchStatus  != '' ){
        //     $filter.="status = '$searchStatus' and ";            
        // }

        // if ($recDate  != '' ){
        //     $date = explode("to",$recDate);
        //     $date1=trim($date[0]);
        //     $date2=trim($date[1]);
        //     $filter.= "to_date(rec_date, 'DD/MM/YYYY')  BETWEEN to_date('$date1', 'DD/MM/YYYY') and to_date('$date2', 'DD/MM/YYYY') and ";
        // }

        
        // if ($filter !=''){
        //     $filter=" where ".substr($filter,0,-4);
        // }

        // $data = DB::select("SELECT id,inv_number,rec_number,rec_date,po_number,inv_date,
        // (select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name ,prepared_by,authorized_by,status
        // from receiving_hdr a $filter");

        // $data=DB::select("SELECT *,delivery_date,(select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name,(gross-discount)+ppn as netto from (
        //     select b.status,b.id,a.po_number,supplier_id,po_date,delivery_date,pkp,termin,authorized_by,prepared_by,uom,sum(qty) as qty,sum(qty*price) as gross,sum(discount) as discount,sum(a.ppn) as ppn from purchase_order_det a
        //     left join receiving_hdr b
        //     on a.po_number = b.po_number 
        //     $filter
        //     group by b.id,a.po_number,supplier_id,po_date,delivery_date,pkp,termin,authorized_by,prepared_by,uom,b.status) as oki");
        
        // $data=DB::table('receiving_hdr')->get();

        $data = DB::select("SELECT *,
        (select concat(kode,'-',nama) from third_party where kode = customer_id limit 1) as customer_name 
        from invoice_hdr a $filter");

        return Datatables::of($data)
        ->addColumn('action', function ($data) {
            $buttons = '<div class="d-inline-flex">
                            <a class="pr-1 dropdown-toggle hide-arrow text-primary" data-toggle="dropdown">
                                <i data-feather="menu"></i>
                            </a>';
            $buttons .=     '<div class="dropdown-menu dropdown-menu-right">';
            if (($data->status != '3') && ($data->status != '4')){
                if (Auth::user()->can('receiving-edit')) {
                $buttons .=         '<a href="'. route('invoice.edit',  ['id'=>Crypt::encryptString($data->id)]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                }
            }
            // if ($data->status == '3'){
                $buttons .=         '<a href="'. route('invoice.print', ['id'=>$data->id]) .'" target="_blank" class="dropdown-item">
                                        <i data-feather="printer"></i>
                                        Print
                                    </a>';

            // }
            $buttons .=         '<a href="'. route('invoice.show', ['id'=>$data->id]) .'" class="dropdown-item">
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
                                        data-href='". route("invoice.destroy", ["id"=>$data->id]) ."'>
                                        <i data-feather='trash-2'></i>
                                        Cancel
                                    </a>";
                }
            }
            $buttons .=     '</div>
                        </div>';

            return $buttons;
            })
        ->addColumn('status', function ($data) {
            $statusRec = ['Draft','Update','Posting','Cancel'];
            return $statusRec[$data->status - 1];
        })
        ->rawColumns(['action','status'])
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
        
        $invHdr=DB::table('invoice_hdr')
        ->where('id',$id)
        ->first();

        $data['recHdr']=DB::table('invoice_hdr')
        ->where('id',$id)
        ->first();

        $invNumber=$invHdr -> invoice_number;
       
        $data['details']=DB::table('invoice_det')
        ->leftJoin('article','article.article_code','invoice_det.article_code')
        ->where('invoice_number',$invNumber)
        ->get();


        $data['listpo']=DB::select("SELECT 
        distinct((select po_number from sales_order_hdr where so_code = a.so_number)) as po_number 
        from invoice_det a where invoice_number = '$invNumber'");

        $data['totals']=DB::select("SELECT *,(total_material+total_service) as sub_total,((total_material+total_service+ppn)-pph23) as grand_total from (
            select 
            a.invoice_number,
            sum(qty) as qty,
            -- sum(qty*price) + sum(qty*price_service) as gross,
            sum(qty*price) as total_material,
            sum(qty*price_service) as total_service,
            sum(a.ppn) as ppn,
            sum(a.pph23) as pph23 
            from invoice_det a
            left join invoice_hdr b
            on a.invoice_number = b.invoice_number 
            where a.invoice_number = '$invNumber'
            group by a.invoice_number) as oki");

        $data['customers']=DB::table('third_party')
        ->where('kode',$invHdr -> customer_id)
        ->first();
        
        $data['status'] ='1';
        $data['no'] = 0 ;

        view()->share($data);

        $pdf = PDF::loadView('invoice.print');
        return $pdf->stream("PO_$invNumber.pdf");

    }

    public function listDn(Request $request)
    {
        $so= $request->value;      
        $output="";

        $data= DB::table("delivery_hdr") 
        ->where("so_number",$so)
        // ->where("status","3")
        ->orderBy("so_number")
        ->select("delivery_number","so_number","po_number")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option value="'.$row->delivery_number.'">'.$row->delivery_number.'</option>';            
        }        
        
        return $output;
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
            $output .='<option value="'.$row->so_code.'">'.$row->so_code. ' - ' .$row->po_number.'</option>';            
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
}
