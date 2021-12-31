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

class AccountPayableController extends Controller
{
    public function index(Request $request)
    {
        $data['title'] = "List invoice";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel

        $data['status'] = ['1'=>'Draft','2'=>'Update','3'=>'Posting','4'=>'Cancel'];
            
        return view("AccountPayable.index",$data);
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
        $data['title'] = "Create Invoice";
        $data['subtitle'] = "Create Invoice";
        
        $data['supps'] = DB::table('third_party')
        ->where ('third_party_type','=','supp')
        ->orderBy('nama')
        ->get();

        $data['currency'] = ['IDR','USD'];

        $data['accounts'] = DB::table('accounts')
        ->get();

        $data['invoiceNumber']="";

        return view("accountPayable.create",$data);
    }

 
    public function listSj(Request $request)
    {
        $supp= $request->value;      
        $output="";

        $data= DB::table("receiving_hdr") 
        ->where("supplier_id",$supp)
        ->where("status","3")
        ->orderBy("do_number")
        ->select("do_number","do_date")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option data-do-date="'.$row->do_date.'" value="'.$row->do_number.'">'.$row->do_number.'</option>';            
        }        
        
        return $output;
    }

    public function listPo(Request $request)
    {
        $supp= $request->value;      
        $output="";

        $data= DB::table("purchase_order_hdr") 
        ->where("supplier_id",$supp)
        ->whereIn('po_number', function($query) use ($supp) {
            $query->select('po_number')
            ->from('receiving_hdr') 
            ->where('supplier_id',$supp);
        })
        ->where("status","3")
        ->orderBy("po_number")
        ->select("po_number","po_date")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option data-po-date="'.$row->po_date.'" value="'.$row->po_number.'">'.$row->po_number.'</option>';            
        }        
        
        return $output;
    }

    public function listRec(Request $request)
    {
        $poNumber= $request->value;      
        $output="";

        $data= DB::table("receiving_hdr") 
        ->where("po_number",$poNumber)
        ->where("status","3")
        ->orderBy("rec_number")
        ->select("rec_number","do_date")
        ->get();          

        $output .='<option value=""></option>';            
        foreach ($data as $row){
            $output .='<option data-do-date="'.$row->do_date.'" value="'.$row->rec_number.'">'.$row->rec_number.'</option>';            
        }        
        
        return $output;
    }

    public function detailRec(Request $request){
        $poNumber = $request->poNumber;
        $data = DB::select("SELECT 
                            a.*
                            ,b.nama
                            ,(select round(sum(qty*price)) as total_po from purchase_order_det where po_number= a.po_number) as total_po 
                            ,round((select sum(qty*price) from receiving_det where rec_number = a.rec_number)) as basis_amount
                            ,(select ppn from purchase_order_hdr where po_number =a.po_number) as vat
                            ,(select pkp from purchase_order_hdr where po_number =a.po_number) as pkp
                            ,(select currency from purchase_order_hdr where po_number =a.po_number) as currency
                            ,(select kurs from purchase_order_hdr where po_number =a.po_number) as kurs
                            ,to_char(to_date(rec_date,'dd-mm-yyyy')+(select termin from purchase_order_hdr where po_number = a.po_number),'dd-mm-yyyy') as due_date
                            ,((select sum(qty*price) from receiving_det where po_number = a.po_number) - (select sum(qty*price) from purchase_order_det where po_number = a.po_number)) as po_balance
                            from receiving_hdr a
                            left join third_party b on b.kode = a.supplier_id
                            where po_number = '$poNumber'");
         return response()->json($data);
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
                        select *,(select po_number from receiving_hdr where rec_number = a.rec_number) as po from receiving_det a where rec_number in (
                        select rec_number from receiving_hdr where status = '3')
                    ) z
                group by po, article_code,price) b
                on a.po_number = b.po and a.article_code = b.article_code
                where po_number = '$po'");

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $username =  Auth::user()->username;
        $suppCode = $request->input('suppCode');
        $poNumber = $request->input('poNumberDet');
        $recNumber = $request->input('recNumber');
        $recDate = $request->input('recDate');
        $dueDate = $request->input('dueDate');
        $currency = $request->input('currency');
        $rate = is_null($request->input('rate')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('rate'));
        $invoiceNumber= $request->input('invoiceNumber');
        $invoiceDate= $request->input('invoiceDate');
        $taxInvoiceNumber= $request->input('taxInvoiceNumber');
        $basisAmount = is_null($request->input('basisAmount')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('basisAmount'));
        $vat = is_null($request->input('vat')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('vat'));
        $pph23= is_null($request->input('pph23')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('pph23'));
        $pph23Type= is_null($request->input('pph23'))? "":$request->input('pph23Type');
        $otherDeduct = is_null($request->input('otherDeduct')) ? 0 : preg_replace('/[^0-9.]+/', '', $request->input('otherDeduct'));
        $account= $request->input('account');
        $status = '1';
        $authorizedBy = "";
        $note="";

        // status
        // 1. Draft
        // 2. Update
        // 3. Posting
        // 4. Cancel
        
        $messages = [
            'required' => 'The field is required.',
            'unique' => 'The code has already been taken', 
            'iunique' => "Invoice : $recNumber has already exist",
        ];
        
        Validator::extend('iunique', function ($attribute, $value, $parameters, $validator) use ($poNumber) {
            $query = DB::table($parameters[0]);
            $column = $query->getGrammar()->wrap($parameters[1]);
            $column2 = $query->getGrammar()->wrap($parameters[2]);
            return !$query->whereRaw("lower({$column}) = lower(?)", [$value])
                          ->whereRaw("lower({$column2}) = lower(?)", [$poNumber])->count();
        });

        $rule = [
            'poNumberDet'  => 'required',
            'recNumber'=>'required|iunique:ap_invoice,inv_number,po_number',
            // 'doDate'  => 'required',
        ];

        $this->validate($request,$rule,$messages);

        $hasilUpdate = AppHelpers::resetCode('INV');
        $invoiceNumber = $this->getLastCode('INV');
        DB::beginTransaction();
        try {
                DB::table('ap_invoice')->insert([
                    'inv_number' => $invoiceNumber,
                    'tax_inv_number' => $taxInvoiceNumber,
                    'old_inv_number' => $invoiceNumber,
                    'inv_date' => $invoiceDate,
                    'rec_number' => $recNumber,
                    'po_number' => $poNumber,
                    'supplier_id' => $suppCode,
                    'rec_date' => $recDate,
                    'due_date' => $dueDate,
                    'currency' => $currency,
                    'kurs' => $rate,
                    'basis_amount' => $basisAmount,
                    'vat' => $vat,
                    'pph23' => $pph23,
                    'pph23_type' => $pph23Type,
                    'other_deduction' => $otherDeduct,
                    'account' => $account,
                    'prepared_by' => Auth::user()->username,
                    'status' => $status,
                    'note' => $note,
                    'created_by' => Auth::user()->username,
                    'updated_by' => Auth::user()->username,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                DB::commit();
                $title ='Save Invoice';
                $alert  ="success";
                $message  = "$title $invoiceNumber is successfully saved";
                \LogActivity::addToLog($title,"username: $username Status $message");
                return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'invoiceNumber'=>$invoiceNumber));

        } catch (Exception $e) {
            DB::rollBack();
            $title ='Save Invoice';
            $alert  ="warning";
            $message  = "*Invoice $invoiceNumber is failed to save";
            \LogActivity::addToLog($title,"username: $username Status $message");
            return redirect()->back()->with(array('title' => $title, 'message' => $message,'alert'=>$alert,'invoiceNumber'=>$invoiceNumber));
        }
        
    }

    public function show(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Details Receiving";
        $data['subtitle'] = "Details Receiving";

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

        return view("receiving.show",$data);
        
    }

    public function edit(Request $request)
    {
        $id=$request->id;
        $data['title'] = "Edit Receiving";
        $data['subtitle'] = "Edit Receiving";

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

        $searchRec = strtolower($request->searchRec);
        $searchPo = strtolower($request->searchPo);
        $searchInv = strtolower($request->searchInv);
        $searchSupplier = $request->searchSupplier;
        $searchStatus = $request->searchStatus;
        $recDate = $request->recDate;
       

        $filter='';
        
        $filter.="lower(a.rec_type) = 'normal' and ";

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

        $data = DB::select("SELECT id,inv_number,rec_number,rec_date,po_number,inv_date,
        (select concat(kode,'-',nama) from third_party where kode = supplier_id limit 1) as supp_name ,prepared_by,authorized_by,status
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
                $buttons .=         '<a href="'. route('receiving.edit', ['id'=>$data->id]) .'" class="dropdown-item">
                                        <i data-feather="file-text"></i>
                                        Edit
                                    </a>';
                $buttons .=         '<a href="'. route('receiving.print', ['id'=>$data->id]) .'" target="_blank" class="dropdown-item">
                                        <i data-feather="printer"></i>
                                        Print
                                    </a>';
                }
            }
            $buttons .=         '<a href="'. route('receiving.show', ['id'=>$data->id]) .'" class="dropdown-item">
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
                                        data-href='". route("receiving.destroy", ["id"=>$data->id]) ."'>
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

        $pdf = PDF::loadView('receiving.print');
        return $pdf->stream("PO_$poNumber.pdf");

    }

    // public function listPo(Request $request)
    // {
    //     $supp= $request->value;      
    //     $output="";

    //     $data= DB::table("purchase_order_hdr") 
    //     ->where("supplier_id",$supp)
    //     ->where("status","3")
    //     ->orderBy("po_number")
    //     ->select("po_number")
    //     ->get();          

    //     $output .='<option value=""></option>';            
    //     foreach ($data as $row){
    //         $output .='<option value="'.$row->po_number.'">'.$row->po_number.'</option>';            
    //     }        
        
    //     return $output;
    // }

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
